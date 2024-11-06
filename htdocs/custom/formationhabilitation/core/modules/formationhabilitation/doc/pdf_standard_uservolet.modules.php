<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand        <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file       core/modules/formationhabilitation/doc/pdf_standard.modules.php
 *  \ingroup    formationhabilitation
 *  \brief      File of class to generate document from standard template
 */

dol_include_once('/formationhabilitation/core/modules/formationhabilitation/modules_uservolet.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';


/**
 *	Class to manage PDF template standard_uservolet
 */
class pdf_standard_uservolet extends ModelePDFUserVolet
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int     Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe Object that emits
	 */
	public $emetteur;

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;


	/**
	 * @var array of document table columns
	 */
	public $cols;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "user";
		$this->description = $langs->trans('DocumentModelStandardPDF');
		$this->update_main_doc_field = 0; // Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 10;
		$this->marge_droite = 115;
		$this->marge_haute = 10;
		$this->marge_basse = 158;

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1; // used for notes ans other stuff


		$this->tabTitleHeight = 5; // default height

		//  Use new system for position of columns, view  $this->defineColumnField()

		$this->tva = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
		$this->situationinvoice = false;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		UserVolet		$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int         	    			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager;
		$volet = new Volet($this->db);
		$volet->fetch($object->fk_volet); 

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "user", "dict"));

		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			global $outputlangsbis;
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
		}

		$hidetop = 0;
		if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
			$hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
		}

		if ($conf->user->dir_output) {
			// Definition of $dir and $file : // LENYTODO
			$objref = dol_sanitizeFileName($object->ref);
			$now = dol_now();
			$user_static = new User($db);
			$user_static->fetch($object->fk_user);
			$volet = new Volet($this->db);
			$volet->fetch($object->fk_volet);
			$dir = $conf->formationhabilitation->dir_output.'/'.$object->element.'/'.$objref;
			$file = $dir."/".$user_static->lastname."_".$volet->nommage."_".dol_print_date($now, "%Y%m%d").".pdf";
			// Appeler la fonction pour obtenir un chemin de fichier unique
			$file = $this->getUniqueFilename($file);
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				// Set certificate
				$cert = empty($user->conf->CERTIFICATE_CRT) ? '' : $user->conf->CERTIFICATE_CRT;
				// If user has no certificate, we try to take the company one
				if (!$cert) {
					$cert = empty($conf->global->CERTIFICATE_CRT) ? '' : $conf->global->CERTIFICATE_CRT;
				}
				// If a certificate is found
				if ($cert) {
					$info = array(
						'Name' => $this->emetteur->name,
						'Location' => getCountry($this->emetteur->country_code, 0),
						'Reason' => 'MYOBJECT',
						'ContactInfo' => $this->emetteur->email
					);
					$pdf->setSignature($cert, $cert, $this->emetteur->name, '', 2, $info);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// New page
				$pdf->AddPage();
				$pagenb++;

				// Head
				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				
				// Body
				$pdf->SetFont('', '', $default_font_size - 1);
				//$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				if($volet->model == 1) {
					$this->pagebodyidentity($pdf, $object, $outputlangs, $outputlangsbis);
				}
				elseif($volet->model == 2) {
					$this->pagebodyformation($pdf, $object, $outputlangs, $outputlangsbis);
				}
				elseif($volet->model == 3) {
					$this->pagebodyentreprise($pdf, $object, $outputlangs, $outputlangsbis);
				}
				elseif($volet->model == 4) {
					$this->pagebodyhabilitation($pdf, $object, $outputlangs, $outputlangsbis, $pagenb);
				}
				elseif($volet->model == 5) {
					$this->pagebodymedical($pdf, $object, $outputlangs, $outputlangsbis);
				}
				elseif($volet->model == 6) {
					$this->pagebodyautorisation($pdf, $object, $outputlangs, $outputlangsbis);
				}

				// Footer
				$this->_pagefoot($pdf, $object, $outputlangs);

				if($volet->model == 6) {
					$autorisationFile = $conf->formationhabilitation->dir_output.'/'.$object->element.'/Autorisation.pdf';
					if (!file_exists($autorisationFile)) {
						$pdf->AddPage();
						$pagenb++;
						$pagecount = $pdf->setSourceFile($autorisationFile);
						$pdf->useTemplate($pdf->importPage(1));
					}
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

					$this->result = array('fullpath'=>$file);

					return 1; // No error
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		return parent::liste_modeles($db, $maxfilenamelength); 
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		tcpdf			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @param		Translate	$outputlangsbis	Langs object bis
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && is_object($outputlangsbis)) {
				$titre .= ' - '.$outputlangsbis->transnoentities("AmountInCurrency", $outputlangsbis->transnoentitiesnoconv("Currency".$currency));
			}

			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->tabTitleHeight, 'F', null, explode(',', $conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter


		$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);

		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + $this->tabTitleHeight, $this->page_largeur - $this->marge_droite, $tab_top + $this->tabTitleHeight); // line takes a position y in 2nd parameter and 4th parameter
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs, $db;

		$user_static = new User($db);
		$user_static->fetch($object->fk_user);

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$volet = new Volet($this->db);
		$volet->fetch($object->fk_volet); 

		$userField = new UserField($this->db);
		$userField->id = $user_static->id;
		$userField->table_element = 'donneesrh_Medecinedutravail';
		$userField->fetch_optionals();

		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->writeHTML('<h4 style="text-align: center; border: 1px black solid;">VOLET '.($volet->numero ? $volet->numero.' - ' : '- ').$volet->label.'</h2>');
		if($volet->model != 1) {
			$pdf->SetFont('', '', $default_font_size);
			$pdf->writeHTML('<p style="border-bottom: 1px black solid;">NOM : '.$user_static->lastname.'</p>');
			$pdf->writeHTML('<p style="border-bottom: 1px black solid;">PRENOMS : '.$user_static->firstname." ".$userField->array_options['options_secondprenom'].'</p>');
		}
		$pdf->SetFont('', 'B', $default_font_size);
		if(!empty($userField->array_options['options_carnetdaccsn'])) {
			$pdf->writeHTML('<p style="border-bottom: 1px black solid;">CARNET D\'ACCES N° : '.$userField->array_options['options_carnetdaccsn'].'</p>');
		}
		//return $top_shift;
	}

	/**
	 *  Show Body for identity uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function pagebodyidentity(&$pdf, $object, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs, $db;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetTextColor(0, 0, 60);

		$user_static = new User($db);
		$user_static->fetch($object->fk_user);
		$extrafields = new Extrafields($this->db);
		$table_element = 'donneesrh_Etatcivil';
		$extrafields->fetch_name_optionals_label($table_element, false);
		$userField = new UserField($this->db);
		$userField->id = $user_static->id;
		$userField->table_element = 'donneesrh_Etatcivil';
		$userField->fetch_optionals();

		$gender = '';
		if($langs->transnoentities($user_static->gender) == 'man') {
			$gender = 'M';
		}
		elseif($langs->transnoentities($user_static->gender) == 'women') {
			$gender = 'F';
		}

		$departement = $extrafields->showOutputField('departementnaissance', $userField->array_options['options_departementnaissance'], '', $table_element);

		$pdf->SetFont('', '', $default_font_size);
		$pdf->writeHTML('<p style="">NOM : '.$user_static->lastname.'</p><br>');
		$pdf->writeHTML('<p style="">NOM (de jeune fille) : '.$userField->array_options['options_nomdejeunefille'].'</p><br>');
		$pdf->writeHTML('<p style="">PRENOMS : '.$user_static->firstname." ".$userField->array_options['options_secondprenom'].'</p><br>');
		$pdf->writeHTML('<p style="">Né(e) le : '.dol_print_date($userField->array_options['options_datedenaissance'], "%d/%m/%Y").'</p>');
		$pdf->Rect(60, 65, 28, 35, '', array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))));
		$pdf->writeHTML('<p style="">à : '.$userField->array_options['options_lieunaissance'].'</p>');
		$pdf->writeHTML('<p style="">Département : '.$departement.'</p><br>');
		$pdf->writeHTML('<p style="">SEXE (M.F.) : '.$gender.'</p><br>');


		$userField->table_element = 'donneesrh_Medecinedutravail';
		$userField->fetch_optionals();

		$pdf->writeHTML('<p style="">DELIVRE LE : '.dol_print_date($userField->array_options['options_datedattribution'], "%d/%m/%Y").'</p><br>');

		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->setCellPadding(1.3);
		$pdf->MultiCell(85, 0, 'Ce carnet d\'accès est affecté exclusivement à son titulaire pour toute sa vie professionnelle. Il doit être conservé par son titulaire en cas de changement d\'employeur', array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 'C', false, 1, '', '', true, 0, false);
	}

	/**
	 *  Show Body for formation uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function pagebodyformation(&$pdf, $object, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs, $db;

		$nb_formation = 0;
		$nb_formation_max = 8;
		$formation = new Formation($db);
		$volet = new Volet($this->db);
		$societe = new Societe($this->db);
		$volet->fetch($object->fk_volet); 
		$object->getLinkedLinesArray();
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell(0, 1, '');
		$html = "";
		foreach($object->lines as $userformation) {
			$formation->fetch($userformation->fk_formation);
			if($userformation->fk_societe > 0) {
				$societe->fetch($userformation->fk_societe);
			}

			if($nb_formation % $nb_formation_max == 0) {
				if($nb_formation > 0) {
					$pdf->AddPage();
					$pagenb++;

					// Head
					$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor(0, 0, 60);
					$pdf->MultiCell(0, 1, '');
				}
				$html .= 
				'<table cellpadding="2" cellspacing="0" border="1" align="center" style="font-size: 7pt">
					<thead>
						<tr>
							<td colspan="5" style="font-size: 8pt">'.$volet->longlabel.'</td>
						</tr>
						<tr>
							<td width="30%" style="font-size: 6pt"><div style="font-size:4pt">&nbsp;</div><strong>DESIGNATION DE LA FORMATION</strong></td>
							<td width="18%" style="font-size: 6pt"><div style="font-size:4.5pt">&nbsp;</div><strong>DATE DE DELIVRANCE</strong></td>
							<td width="23%" style="font-size: 6pt"><div style="font-size:4.5pt">&nbsp;</div><strong>ORGANISME DE FORMATION</strong></td>
							<td width="17%" style="font-size: 6pt"><div style="font-size:5.5pt">&nbsp;</div><strong>N° DE CERTIFICAT</strong></td>';
							if($volet->numero == 2 || $volet->numero == 3) {
								$html .= '<td width="12%" style="font-size: 4pt"><strong>ORGANISME DE FORMATION AGREE SCN/CSQ N°</strong></td>';
							}
							else {
								$html .= '<td width="12%" style="font-size: 4pt"><strong>ORGANISME DE FORMATION CERTIF CEFRI N°</strong></td>';
							}
					$html .= 
						'</tr>
					</thead>';
			}

			$html .= 
			'<tr>
				<td width="30%" style="font-size: 6pt">'.$formation->label.'</td>
				<td width="18%">'.dol_print_date($userformation->date_fin_formation, '%d/%m/%Y').'</td>
				<td width="23%">'.$societe->name.'</td>
				<td width="17%">'.$userformation->numero_certificat.'</td>';
				if($volet->numero == 2 || $volet->numero == 3) {
					$html .= '<td width="12%">'.($userformation->fk_societe > 0 ? $societe->array_options['options_num_certif_scncsq'] : '').'</td>';
				}
				else {
					$html .= '<td width="12%">'.($userformation->fk_societe > 0 ? $societe->array_options['options_num_certif_cefri'] : '').'</td>';
				}
			$html .= '</tr>';

			$nb_formation++;

			if($nb_formation % $nb_formation_max == 0) {
				$html .= 
					'</tbody>
				</table>';
				$pdf->writeHTML($html, true, false, false, false, '');
				$html = "";
			}
		}

		if($nb_formation % $nb_formation_max != 0) {
			$html .= 
					'</tbody>
				</table>';
			$pdf->writeHTML($html, true, false, false, false, '');
		}
	}

	/**
	 *  Show Body for entreprise uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet	$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function pagebodyentreprise(&$pdf, $object, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs, $db;

		$societe = new Societe($db);
		$organisme_certifiant = new Societe($db);
		$user_static = new User($db);
		$user_static->fetch($object->fk_user);
		if($user_static->array_options['options_fk_employeur']) {
			$societe->fetch($user_static->array_options['options_fk_employeur']);
			if($societe->array_options['options_organismecertifiant']) {
				$organisme_certifiant->fetch($societe->array_options['options_organismecertifiant']);
			}
		}

		$chaine_coupee = wordwrap($societe->address, 30, "<br><br>", false);
		$interim = $user_static->array_options['options_interim'];
		$default_font_size = pdf_getPDFFontSize($outputlangs) - 2;
	
		$pdf->SetTextColor(0, 0, 60);
		$pdf->setCellHeightRatio(0.6);
		$pdf->SetFont('', '', $default_font_size);

		$pdf->writeHTML('<p style="">EMPLOYEUR : '.($interim ? '' : $societe->name).'</p><br>');
		$pdf->writeHTML('<p style="">SIRET : '.($interim ? '' : $societe->idprof2).'</p><br>');
		$pdf->writeHTML('<p style="">ADRESSE : <br><br>'.($interim ? '' : $chaine_coupee.'<br><br>'.$societe->zip.', '.$societe->town).'<br></p><br>');
		$pdf->writeHTML('<p style="">TEL : '.($interim ? '' : $societe->phone).'</p><br>');
		$pdf->writeHTML('<p style="">ORGANISME CERTIFIANT : '.($interim ? '' : $organisme_certifiant->name).'</p><br>');
		$pdf->writeHTML('<p style="border-bottom: 1px black solid;">N° CERTIF : '.($interim ? '' : $societe->array_options['options_num_certif_cefri']).'</p><br>');

		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->writeHTML('(Pour les Intérimaires)<br><br>');

		$pdf->SetFont('', '', $default_font_size);
		$pdf->writeHTML('<p style="">EMPLOYEUR UTILISATRICE : <br><br>'.($interim ? $societe->name : '').'</p><br><br>');
		$pdf->writeHTML('<p style="">SIRET : '.($interim ? $societe->idprof2 : '').'</p><br>');
		$pdf->writeHTML('<p style="">ADRESSE : <br><br>'.($interim ? $chaine_coupee.'<br><br>'.$societe->zip.', '.$societe->town : '').'<br></p><br>');
		$pdf->writeHTML('<p style="">TEL : '.($interim ? $societe->phone : '').'</p><br>');
		$pdf->writeHTML('<p style="">ORGANISME CERTIFIANT : '.($interim ? $organisme_certifiant->name : '').'</p><br>');
		$pdf->writeHTML('<p style="">N° CERTIF : '.($interim ? $societe->array_options['options_num_certif_cefri'] : '').'</p><br>');

		$pdf->setCellHeightRatio(1.25);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$cachet = '<h3>CACHET<h3>'.$this->getSocieteCachet($pdf, $object, $outputlangs, $outputlangsbis);
		$pdf->writeHTMLCell(40, 30, 55, 45, (!$interim ? $cachet : ''), array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 0, false, true, "C");
		$pdf->writeHTMLCell(40, 30, 55, ($interim ? 85 : 95), '', array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 0, false, true, "C");

	}

	/**
	 *  Show Body for habilitation uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function pagebodyhabilitation(&$pdf, $object, $outputlangs, $outputlangsbis = null, $pagenb)
	{
		global $conf, $langs, $db;

		$user_static = new User($db);
		$user_static->fetch($object->fk_user);
		$nb_habilitation = 0;
		$nb_habilitation_max = 7;
		$habilitation = new Habilitation($db);
		$volet = new Volet($this->db);
		$volet->fetch($object->fk_volet); 
		$domaineapplicationInfo = $volet->getAllDomaineApplication();
		//$arrayhabilitations = $userhabilitation->getHabilitationsByUser($object->id, $object->fk_volet);
		$object->getLinkedLinesArray();
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor(0, 0, 60);

		$x = $pdf->getX();
		$y = $pdf->getY();
		//var_dump($pdf->GetLineWidth());
		$pdf->writeHTMLCell(85, 8, $x, $y, '<span>QUALIFICATION PROFESSIONNELLE (Métier) : <span><br><span align="center"><strong>'.$user_static->job."</strong></span>", array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 1, false, true, '', false);
		
					
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell(0, 1, '');
		$html = "";
		foreach($object->lines as $userhabilitation) {
			$habilitation->fetch($userhabilitation->fk_habilitation);

			if($nb_habilitation % $nb_habilitation_max == 0) {
				if($nb_habilitation > 0) {
					// Footer
					$this->writeSignature($pdf, $object, $outputlangs, $outputlangsbis);

					$pdf->AddPage();
					$pagenb++;

					// Head
					$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);

					$pdf->writeHTMLCell(85, 8, $x, $y, '<span>QUALIFICATION PROFESSIONNELLE (Métier) : <span><br><span align="center"><strong>'.$user_static->job."</strong></span>", array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 1, false, true, '', false);

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor(0, 0, 60);
					$pdf->MultiCell(0, 1, '');
				}
				$html .= 
				'<table cellpadding="2" cellspacing="0" border="1" align="center">
					<thead>
						<tr>
							<td width="30%" style="font-size: 7pt"><strong>HABILITATION</strong></td>
							<td width="30%" style="font-size: 7pt"><strong>FIN DE VALIDITE</strong></td>
							<td width="40%" style="font-size: 7pt"><strong>DOMAINE D\'APPLICATION</strong></td>
						</tr>
					</thead>
					<tbody>';
			}

			$html .= 
			'<tr>
				<td width="30%" style="font-size: 7pt">'.$habilitation->label.'</td>
				<td width="30%" style="font-size: 7pt">'.dol_print_date($userhabilitation->date_fin_habilitation, '%d/%m/%Y').'</td>
				<td width="40%" style="font-size: 7pt">'.$domaineapplicationInfo[$userhabilitation->domaineapplication].'</td>
			</tr>';

			$nb_habilitation++;

			if($nb_habilitation % $nb_habilitation_max == 0) {
				$html .= 
					'</tbody>
				</table>';
				$pdf->writeHTML($html, true, false, false, false, '');
				$html = "";
			}
		}

		if($nb_habilitation % $nb_habilitation_max != 0) {
			$html .= 
					'</tbody>
				</table>';
			$pdf->writeHTML($html, true, false, false, false, '');
		}

		//$pdf->SetFont('', 'B', $default_font_size - 2);
		$this->writeSignature($pdf, $object, $outputlangs, $outputlangsbis);
	}

	/**
	 *  Show Body for medicale uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function pagebodymedical(&$pdf, $object, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs, $db;

		$societe = new Societe($db);
		$societe->fetch(157);
		
		$extrafields = new Extrafields($this->db);
		$table_element = 'donneesrh_Medecinedutravail';
		$extrafields->fetch_name_optionals_label($table_element, false);
		$userField = new UserField($this->db);
		$userField->id = $object->fk_user;
		$userField->table_element = $table_element;
		$userField->fetch_optionals();
		$default_font_size = pdf_getPDFFontSize($outputlangs) - 2;

		$medecinedutravailid = $userField->array_options['options_medecinedutravail'];
		$medecinedutravail = new Societe($db);
		$medecinedutravail->fetch($medecinedutravailid);
		$user_static = new User($db);
		$user_static->fetch($object->fk_user);
		$site_nucleaire = $medecinedutravail->array_options['options_servicehabilitsitenuclaire'];
		$interim = $user_static->array_options['options_interim'];

		$docteur = new Contact($db);
		$docteur->fetch($userField->array_options['options_docteur']);
		$docteurname = "Dr ".$docteur->lastname." ".$docteur->firstname;

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', '', $default_font_size);
		
		$pdf->setCellHeightRatio(0.8);
		$pdf->writeHTML('<p style="">NOM DU MEDECIN DU TRAVAIL : '.($interim ? '' : $docteurname).'</p><br><br>');
		$pdf->setCellHeightRatio(0.6);
		$pdf->writeHTML('<p style="">NOM DU SERVICE DE<br><br>MEDECINE DU TRAVAIL<br><br>DE L\'EMPLOYEUR CHARGE<br><br>DU SUIVI MEDICAL : <br>');
		$pdf->setCellHeightRatio(1);
		$pdf->writeHTML('<p style="">'.($interim ? '' : $medecinedutravail->name).'</p>');
		$pdf->writeHTML('<p style="">'.($interim ? '' : $medecinedutravail->address).'</p>');
		$pdf->writeHTML('<p style="">'.($interim ? '' : $medecinedutravail->zip.', '.$medecinedutravail->town).'</p>');
		$pdf->writeHTML('<p style="">TEL : '.($interim ? '' : $medecinedutravail->phone).'</p><br>');
		$pdf->setCellHeightRatio(0.6);

		$x = $pdf->getX();
		$y = $pdf->getY();
		$pdf->writeHTML('<p style="border-bottom: 1px black solid;">SERVICE HABILITE SITE NUCLEAIRE : </p><br>');
		$xafter = $pdf->getX();
		$yafter = $pdf->getY();
		$pdf->SetXY($x + 55, $y);
		$pdf->Cell(7, 0, 'Oui', 0, 0, 'R', false, '', 0, false, 'T', 'T');
		$pdf->CheckBox('yes', 3, (!$interim && $site_nucleaire ? true : false), array('fillColor'=>array(190, 190, 190), 'readonly'=>'true'));
		$pdf->Cell(7, 0, 'Non', 0, 0, 'R', false, '', 0, false, 'T', 'T');
		$pdf->CheckBox('no', 3, (!$interim && !$site_nucleaire ? true : false), array('fillColor'=>array(190, 190, 190), 'readonly'=>'true'));
		$pdf->writeHTML('<br>');

		$pdf->SetXY($xafter, $yafter);

		$pdf->setCellHeightRatio(0.8);
		$pdf->writeHTML('<p style="">*NOM DU MEDECIN DU TRAVAIL : '.($interim ? $docteurname : '').'</p><br><br>');
		$pdf->setCellHeightRatio(0.6);
		$pdf->writeHTML('<p style="">NOM DU SERVICE DE<br><br>MEDECINE DU TRAVAIL<br><br>DE L\'EMPLOYEUR CHARGE<br><br>DU SUIVI MEDICAL : <br>');
		$pdf->setCellHeightRatio(1);
		$pdf->writeHTML('<p style="">'.($interim ? $medecinedutravail->name : '').'</p>');
		$pdf->writeHTML('<p style="">'.($interim ? $medecinedutravail->address : '').'</p>');
		$pdf->writeHTML('<p style="">'.($interim ? $medecinedutravail->zip.', '.$medecinedutravail->town : '').'</p>');
		$pdf->writeHTML('<p style="">TEL : '.($interim ? $medecinedutravail->phone : '').'</p><br>');
		$pdf->setCellHeightRatio(0.6);
		
		$x = $pdf->getX();
		$y = $pdf->getY();
		$pdf->writeHTML('<p style="border-bottom: 1px black solid;">SERVICE HABILITE SITE NUCLEAIRE : </p><br>');
		$xafter = $pdf->getX();
		$yafter = $pdf->getY();
		$pdf->SetXY($x + 55, $y);
		$pdf->Cell(7, 0, 'Oui', 0, 0, 'R', false, '', 0, false, 'T', 'T');
		$pdf->CheckBox('yes_interim', 3, ($interim && $site_nucleaire ? true : false), array('fillColor'=>array(190, 190, 190), 'readonly'=>'true'));
		$pdf->Cell(7, 0, 'Non', 0, 0, 'R', false, '', 0, false, 'T', 'T');
		$pdf->CheckBox('no_interim', 3, ($interim && !$site_nucleaire ? true : false), array('fillColor'=>array(190, 190, 190), 'readonly'=>'true'));
		$pdf->writeHTML('<br>');

		$pdf->SetXY($xafter, $yafter);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->writeHTML('(*) Partie à renseigner par l\'entreprise utilisatrice dans le cas où l\'employeur est une entreprise de travail temporaire');

		$pdf->setCellHeightRatio(1.25);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$cachet = '<h3>CACHET<h3>'.$this->getSocieteCachet($pdf, $object, $outputlangs, $outputlangsbis);
		$pdf->writeHTMLCell(40, 30, 55, 50, (!$interim ? $cachet : ''), array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 0, false, true, "C");
		$pdf->writeHTMLCell(40, 30, 55, ($interim ? 92 : 92), '', array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 0, false, true, "C");

	}

	/**
	 *  Show Body for autorisation uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function pagebodyautorisation(&$pdf, $object, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs, $db, $pagenb;

		$nb_autorisation = 0;
		$nb_autorisation_max = 7;
		$autorisation = new Autorisation($db);
		$volet = new Volet($this->db);
		$volet->fetch($object->fk_volet); 
		//$arrayautorisations = $userautorisation->getAutorisationsByUser($object->fk_user, $object->fk_volet);
		$domaineapplicationInfo = $volet->getAllDomaineApplication();
		$object->getLinkedLinesArray();
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor(0, 0, 60);

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor(0, 0, 60);

		// $x = $pdf->getX();
		// $y = $pdf->getY();
		// $pdf->writeHTMLCell(85, 8, $x, $y, '<span>QUALIFICATION PROFESSIONNELLE (Métier) : <span><br><span align="center"><strong>'.$user_static->job."</strong></span>", array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 1, false, true, '', false);
		
					
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell(0, 1, '');
		$html = "";
		foreach($object->lines as $userautorisation) {
			if($nb_autorisation % $nb_autorisation_max == 0) {
				$autorisation->fetch($userautorisation->fk_autorisation);

				if($nb_autorisation > 0) {
					// Footer
					$this->writeSignature($pdf, $object, $outputlangs, $outputlangsbis);

					// Ajout de la page avec la liste des autorisations
					$pdf->AddPage();
					$pagenb++;
					$pagecount = $pdf->setSourceFile($conf->formationhabilitation->dir_output.'/'.$object->element.'/Autorisation.pdf');
					$pdf->useTemplate($pdf->importPage(1));

					$pdf->AddPage();
					$pagenb++;

					// Head
					$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);

					//$pdf->writeHTMLCell(85, 8, $x, $y, '<span>QUALIFICATION PROFESSIONNELLE (Métier) : <span><br><span align="center"><strong>'.$user_static->job."</strong></span>", array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 1, false, true, '', false);

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor(0, 0, 60);
					$pdf->MultiCell(0, 1, '');
				}
				$html .= 
				'<table cellpadding="2" cellspacing="0" border="1" align="center">
					<thead>
						<tr>
							<td width="30%" style="font-size: 7pt"><strong>AUTORISATION</strong></td>
							<td width="30%" style="font-size: 7pt"><strong>FIN DE VALIDITE</strong></td>
							<td width="40%" style="font-size: 7pt"><strong>DOMAINE D\'APPLICATION</strong></td>
						</tr>
					</thead>
					<tbody>';
			}

			$html .= 
			'<tr>
				<td width="30%" style="font-size: 7pt">'.$autorisation->label.'</td>
				<td width="30%" style="font-size: 7pt">'.dol_print_date($userautorisation->date_fin_autorisation, '%d/%m/%Y').'</td>
				<td width="40%" style="font-size: 7pt">'.$domaineapplicationInfo[$userautorisation->domaineapplication].'</td>
			</tr>';

			$nb_autorisation++;

			if($nb_autorisation % $nb_autorisation_max == 0) {
				$html .= 
					'</tbody>
				</table>';
				$pdf->writeHTML($html, true, false, false, false, '');
				$html = "";
			}
		}

		if($nb_autorisation % $nb_autorisation_max != 0) {
			$html .= 
					'</tbody>
				</table>';
			$pdf->writeHTML($html, true, false, false, false, '');
		}

		//$pdf->SetFont('', 'B', $default_font_size - 2);
		$this->writeSignature($pdf, $object, $outputlangs, $outputlangsbis);
	}

	/**
	 *  Show Body for autorisation uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function writeSignature(&$pdf, $object, $outputlangs, $outputlangsbis = null)
	{
		global $db; 

		$x = $pdf->getX();
		$y = $pdf->getY();
		$societe = new Societe($db);
		$societe->fetch(157);
		$user_static = new User($db);
		$user_static->fetch($object->fk_user);

		$cachet1 = '<span>Date : '.dol_print_date(dol_now(), '%d/%m/%Y').'</span><br><p style="font-size: 6.5pt">L\'habilitation est soumise<br>au renouvellement de<br>l\'aptitude médicale et à la date la plus restrictive</p><br>';
		$pdf->writeHTMLCell(30, 35, $x, $y, $cachet1, array('LTB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 0, false, true, "C");

		//$pdf->setCellHeightRatio(1.25);
		//$pdf->SetFont('', 'B', $default_font_size - 2);
		$cachet2 = '<p style="font-size: 6px:">Cachet et signature employeur ou entreprise utilisatrice</p>'.$this->getSocieteCachet($pdf, $object, $outputlangs, $outputlangsbis, 6.5);
		$pdf->writeHTMLCell(25, 35, $x + 30, $y, $cachet2, array('RTB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 0, false, true, "C");
		//$pdf->setCellHeightRatio(1);
		//$pdf->SetFont('', '', $default_font_size);

		$signature = $user_static->firstname." ".$user_static->lastname;
		$cachet3 = '<p>Signature intervenant</p>'.$signature;
		$pdf->writeHTMLCell(30, 35, $x + 55, $y, $cachet3, array('RTB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))), 0, false, true, "C");
	}

	/**
	 *  Show Body for autorisation uservolet
	 *
	 *  @param	Tcpdf		$pdf     		Object PDF
	 *  @param  UserVolet		$object     	Object to show
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function getSocieteCachet(&$pdf, $object, $outputlangs, $outputlangsbis = null, $font_size = 7.5)
	{
		global $db; 

		$societe = new Societe($db);
		$user_static = new User($db);
		$user_static->fetch($object->fk_user);
		$societe->fetch($user_static->array_options['options_fk_employeur']);

		return '<p style="font-size: '.($font_size - 1.5).'px"><span style="font-size: '.$font_size.'px"><strong>'.$societe->name."</strong></span><br>".$societe->address."<br>".$societe->zip." ".$societe->town."<br> Tél. : ".$societe->phone."<br>Fax : ".$societe->phone."<br>SIRET ".$societe->idprof2." - APE ".$societe->idprof3."<br>Code TVA : ".$societe->tva_intra."</p>";
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	UserVolet		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		return 0;
		//return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}

	/**
	 *  Define Array Column Field
	 *
	 *  @param	object			$object    		common object
	 *  @param	Translate		$outputlangs    langs
	 *  @param	int			   $hidedetails		Do not show line details
	 *  @param	int			   $hidedesc		Do not show desc
	 *  @param	int			   $hideref			Do not show ref
	 *  @return	null
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(1, 0.5, 1, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for content
		$this->defaultTitlesFieldsStyle = array(
			'align' => 'C', // R,C,L
			'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		/*
		 * For exemple
		$this->cols['theColKey'] = array(
			'rank' => $rank, // int : use for ordering columns
			'width' => 20, // the column width in mm
			'title' => array(
				'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				'label' => ' ', // the final label : used fore final generated text
				'align' => 'L', // text alignement :  R,C,L
				'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L', // text alignement :  R,C,L
				'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
		);
		*/

		$rank = 0; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is usefull in somme case with module
				'align' => 'L',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
				'padding' => array(1, 0.5, 1, 1.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
		);

		// PHOTO
		$rank = $rank + 10;
		$this->cols['photo'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Photo',
				'label' => ' '
			),
			'content' => array(
				'padding' => array(0, 0, 0, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if (!empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE) && !empty($this->atleastonephoto)) {
			$this->cols['photo']['status'] = true;
		}


		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => 16, // in mm
			'title' => array(
				'textkey' => 'VAT'
			),
			'border-left' => true, // add left line separator
		);

		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
			$this->cols['vat']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['subprice'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'PriceUHT'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => 16, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Qty'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['progress'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Progress'
			),
			'border-left' => true, // add left line separator
		);

		if ($this->situationinvoice) {
			$this->cols['progress']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['unit'] = array(
			'rank' => $rank,
			'width' => 11, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Unit'
			),
			'border-left' => true, // add left line separator
		);
		if (!empty($conf->global->PRODUCT_USE_UNITS)) {
			$this->cols['unit']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['discount'] = array(
			'rank' => $rank,
			'width' => 13, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'ReductionShort'
			),
			'border-left' => true, // add left line separator
		);
		if ($this->atleastonediscount) {
			$this->cols['discount']['status'] = true;
		}

		$rank = $rank + 1000; // add a big offset to be sure is the last col because default extrafield rank is 100
		$this->cols['totalexcltax'] = array(
			'rank' => $rank,
			'width' => 26, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'TotalHT'
			),
			'border-left' => true, // add left line separator
		);

		// Add extrafields cols
		if (!empty($object->lines)) {
			$line = reset($object->lines);
			$this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
		}

		$parameters = array(
			'object' => $object,
			'outputlangs' => $outputlangs,
			'hidedetails' => $hidedetails,
			'hidedesc' => $hidedesc,
			'hideref' => $hideref
		);

		$reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this); // Note that $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} elseif (empty($reshook)) {
			$this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
		} else {
			$this->cols = $hookmanager->resArray;
		}
	}

	function getUniqueFilename($fullpath) {
		$path_info = pathinfo($fullpath);
		$i = 1;
	
		// Vérifier si le fichier existe
		while (file_exists($fullpath)) {
			// Générer un nouveau nom avec un suffixe (ex: document_1.pdf, document_2.pdf, etc.)
			$fullpath = $path_info['dirname'] . '/' . $path_info['filename'] . '_' . $i . '.' . $path_info['extension'];
			$i++;
		}
		return $fullpath;
	}
}
