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
 * Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *  \file       core/modules/fod/doc/pdf_standard.modules.php
 *  \ingroup    fod
 *  \brief      File of class to generate document from standard template
 */

dol_include_once('/fod/core/modules/fod/modules_fod.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to manage PDF template standard_fod
 */
class pdf_standard_fod extends ModelePDFFod
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
		$this->name = "standard";
		$this->description = $langs->trans('DocumentModelStandardPDF');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

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
	 *  @param		Object		$object				Object to generate
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
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			global $outputlangsbis;
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies", "fod@fod"));
		}

		$nblines = (is_array($object->lines) ? count($object->lines) : 0);

		$hidetop = 0;
		if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
			$hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
		}

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		/*
		if (!empty($conf->global->MAIN_GENERATE_FOD_WITH_PICTURE))
		{
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++)
			{
				if (empty($object->lines[$i]->fk_product)) continue;

				//var_dump($objphoto->ref);exit;
				if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
					$pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
					$pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
				} else {
					$pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product'); // default
					$pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/"; // alternative
				}

				$arephoto = false;
				foreach ($pdir as $midir)
				{
					if (!$arephoto)
					{
						$dir = $conf->product->dir_output.'/'.$midir;

						foreach ($objphoto->liste_photos($dir, 1) as $key => $obj)
						{
							if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
							{
								if ($obj['photo_vignette'])
								{
									$filename = $obj['photo_vignette'];
								} else {
									$filename = $obj['photo'];
								}
							} else {
								$filename = $obj['photo'];
							}

							$realpath = $dir.$filename;
							$arephoto = true;
							$this->atleastonephoto = true;
						}
					}
				}

				if ($realpath && $arephoto) $realpatharray[$i] = $realpath;
			}
		}
		*/

		//if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

		if ($conf->fod->dir_output.'/fod') {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->fod->dir_output.'/fod';
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->fod->dir_output.'/fod/'.$objectref;
				$file = $dir."/".$objectref.".pdf";

				$num = 0;
				$filecount = 0;
				$files = scandir($dir);
				if($object->status == Fod::STATUS_CLOTURE && !array_search($objectref."_cloture.pdf", $files)){
					$file = $dir."/".$objectref."_cloture.pdf";
				}
				elseif ($files){
					while (array_search(str_replace($dir.'/', '', $file), $files)){
						$num++;
						$file = $dir."/".$objectref.'('.$num.')'.".pdf";
					}
				}
			}
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new facture lines content after hook
				$nblines = (is_array($object->lines) ? count($object->lines) : 0);

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

				// Set path to the background PDF File
				if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->multidir_output[$object->entity].'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

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
						'Reason' => 'FOD',
						'ContactInfo' => $this->emetteur->email
					);
					$pdf->setSignature($cert, $cert, $this->emetteur->name, '', 2, $info);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;

				$top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);


				// Tableau dates
				$html = '<table style="border-collapse: collapse; border: 0.5px solid #001b40; padding: 5px;">
							<tr>
								<td rowspan="2" style="border: 0.5px solid  #001b40; text-align: center; vertical-align: middle;"><strong>'."PERIODE DE L'INTERVENTION :</strong></td>".
								'<td style="border: 0.5px solid  #001b40; text-align: center;"> Début : </td>
								<td style="border: 0.5px solid  #001b40; text-align: center;"> Fin : </td>
								<td rowspan="2" colspan="2" style="border: 0.5px solid  #001b40;"> Extension période <br/> => nouvelle date de fin : <strong>'. dol_print_date($object->date_fin_prolong, '%d/%m/%Y').'</strong></td>
							</tr>
							<tr>'.
								'<td style="border: 0.5px solid  #001b40; text-align: center;">'.dol_print_date($object->date_debut, '%d/%m/%Y').'</td>
								<td style="border: 0.5px solid #001b40; text-align: center;">'.dol_print_date($object->date_fin, '%d/%m/%Y').'</td>
							</tr>
						</table>
				';
				$pdf->writeHTML($html);

				// Titre "Données d'entrées"
				$html = '<span style="text-align: center;">'."<strong>DONNEES D'ENTREES :</strong></span>".'<hr color="#00285e">';
				$pdf->writeHTML($html);

				// Tableau données d'entrées
				if($object->debit_dose_estime >= 1.6 || $object->debit_dose_max >= 1.6) {
					$fod_orange = 'OUI';
				}
				else {
					$fod_orange = 'NON';
				}
				$html = '<table style="padding: 5px;">
							<tbody>
								<tr>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;">NOMBRE INTERVENANT</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->effectif.'</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;"></td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>VALEURS</strong></td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>Unités</strong></td>
									<td colspan="2" rowspan="2" style="border: 0.5px solid  #001b40; text-align: center;">Si DED &gt;= 2mSv/h ou sur CNPE &gt;= 1,6 mSv :<br>pas de CDD (dont alternant), intérimaire</td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">DEBIT DE DOSE<br>MOYEN</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->debit_dose_estime.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>mSv/h</strong></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">DED max<br>(poste ou trajet)</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->debit_dose_max.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>mSv/h</strong></td>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center; font-size: 15px;"><strong>FOD ORANGE <span style="color: orange">'.$fod_orange.'</span></strong></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">DUREE<br>INTERVENTION<br>(COLLECTIVE)</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><br><br>'.$object->duree_intervention.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong><br>h</strong></td>
									<td colspan="2" rowspan="3" style="border: 0.5px solid  #001b40;">Commentaires : '.$object->commentaire_fod.'</td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">Coef. d`exposition</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['coef_exposition'], 'coef_exposition', $object->coef_exposition, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>/</strong></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">PROPRETE<br>RADIOLOGIQUE</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['prop_radiologique'], 'prop_radiologique', $object->prop_radiologique, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>/</strong></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">Risques / Coactivité</td>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['risques'], 'risques', $object->risques, '', '', '', 0).'</td>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;">'.$object->commentaire_risque.'</td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">RI en présence</td>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['ri'], 'ri', $object->ri, '', '', '', 0).'</td>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;">'.$object->commentaire_ri.'</td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">REX<br>OPTIM Industries</td>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['rex'], 'rex', $object->rex, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">Référence REX : </td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->ref_rex.'</td>
								</tr>
							</tbody>
						</table>';
				$pdf->writeHTML($html);


				// Titre "Evalutation prévisionnelle"
				$html = '<span style="text-align: center;">'."<strong>EVALUATION PREVISIONNELLE :</strong></span>".'<hr color="#00285e">';
				$pdf->writeHTML($html);

				// Tableau Evalutation prévisionnelle
				$html = '<table style="padding: 5px;">
							<tbody>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;">DOSE INDIVIDUELLE<br>MOYENNE</td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->GetDoseIndividuelleMoyenne().'</td>
								<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>mSv</strong></td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">Valeur<br>informative</td>
							</tr>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>DOSE<br>COLLECTIVE</strong></td>
								<td style="border: 0.5px solid  #001b40; text-align: center; font-size: 15px;"><strong>'.$object->GetDoseCollectivePrevisionnelle().'</strong></td>
								<td style="border: 0.5px solid  #001b40; text-align: center; font-size: 15px;"><strong>H.mSv</strong></td>
								<td rowspan="3" style="border: 0.5px solid  #001b40; text-align: center; font-size: 15px;"><strong>Valeurs<br>limites<br>à ne pas <br>dépasser</strong></td>
							</tr>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>DOSE INDIVIDUELLE<br>MAXI : CONTRAINTE<br>DE DOSE</strong></td>
								<td style="border: 0.5px solid  #001b40; text-align: center; font-size: 15px;"><strong>'.$object->GetDoseIndividuelleMax().'</strong></td>
								<td style="border: 0.5px solid  #001b40; text-align: center; font-size: 15px;"><strong>mSv</strong></td>
							</tr>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>Objectif propreté</strong></td>
								<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['objectif_proprete'], 'objectif_proprete', $object->objectif_proprete, '', '', '', 0).(!empty($object->com_objectif_proprete) ? ' : '.$object->com_objectif_proprete : '').'</td>
							</tr>
							</tbody>
						</table>';
				$pdf->writeHTML($html);
				$html = '<table style="padding: 5px;">
							<tbody>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>ENJEU RADIOLOGIQUE</strong></td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->GetEnjeuRadiologique().' : '.$object->showOutputField($object->fields['aoa'], 'aoa', $object->aoa, '', '', '', 0).'</td>
							</tr>
							</tbody>
						</table>';
				$pdf->writeHTML($html);

				$pcr = New ExtendedUser($this->db);
				$rsr = New ExtendedUser($this->db);
				$raf = New ExtendedUser($this->db);
				$pcr->fetch($object->fk_user_pcr);
				$rsr->fetch($object->fk_user_rsr);
				$raf->fetch($object->fk_user_raf);
				$html = '<table style="padding: 5px;">
							<tbody>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;">RSR</td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">RAF</td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">PCR</td>
							</tr>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;">'.$rsr->firstname." ".$rsr->lastname.'</td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">'.$raf->firstname." ".$raf->lastname.'</td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">'.$pcr->firstname." ".$pcr->lastname.'</td>
							</tr>
							</tbody>
						</table>';
				$pdf->writeHTML($html);

				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				// Titre "Optimisation"
				$html = '<span style="text-align: center;">'."<strong>OPTIMISATION :</strong></span>".'<hr color="#00285e">';
				$pdf->writeHTML($html);

				if($object->fields['dc_optimise']['arrayofkeyval'][$object->dc_optimise] == 'Oui'){
					$dc_optimise = $object->GetDoseCollectivePrevisionnelleOptimise();
				}
				else{
					$dc_optimise = '';
				}

				if($object->fields['cdd_optimise']['arrayofkeyval'][$object->cdd_optimise] == 'Oui'){
					$cdd_optimise = $object->GetDoseIndividuelleMaxOptimise();
				}
				else{
					$cdd_optimise = '';
				}

				if(!empty($object->effectif_optimise)){
					if ($object->effectif_optimise >= $object->effectif){
						$effectif_optimise = 'Non';
					}
					else {
						$effectif_optimise = 'Oui';
					}
				}
				elseif ($object->aoa != 1){
					$effectif_optimise = 'Non';
				}
				else $effectif_optimise = '';
				
				if(!empty($object->duree_intervention_optimise)){
					if ($object->duree_intervention_optimise >= $object->duree_intervention){
						$duree_intervention_optimise = 'Non';
					}
					else {
						$duree_intervention_optimise = 'Oui';
					}
				}
				elseif ($object->aoa != 1){
					$duree_intervention_optimise = 'Non';
				}
				else $duree_intervention_optimise = '';

				// Tableau Optimisation
				$html = '<table style="padding: 5px;">
							<tbody>
								<tr>
									<td colspan="2" style="border: 0.5px solid  #001b40; text-align: center;"></td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>Valeur optimisée</strong></td>
									<td colspan="3" style="border: 0.5px solid  #001b40; text-align: center;"></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">DED moyen optimisé</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['ded_optimise'], 'ded_optimise', $object->ded_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['debit_dose_estime_optimise'], 'debit_dose_estime_optimise', $object->debit_dose_estime_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>mSv/h</strong></td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">Consigne RP</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['consignes_rp'], 'consignes_rp', $object->consignes_rp, '', '', '', 0).'</td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">DED max optimisé</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['ded_max_optimise'], 'ded_max_optimise', $object->ded_max_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['debit_dose_max_optimise'], 'debit_dose_max_optimise', $object->debit_dose_max_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>mSv/h</strong></td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">EPI spécifique</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['epi_specifique'], 'epi_specifique', $object->epi_specifique, '', '', '', 0).'</td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">DC optimisée</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['dc_optimise'], 'dc_optimise', $object->dc_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$dc_optimise.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>H.mSv</strong></td>
									<td colspan="2" rowspan="5" style="border: 0.5px solid  #001b40; text-align: center;"></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">DI max : CdD</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['cdd_optimise'], 'cdd_optimise', $object->cdd_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$cdd_optimise.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>mSv</strong></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">Propreté Rad</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['prop_rad_optimise'], 'prop_rad_optimise', $object->prop_rad_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->showOutputField($object->fields['prop_radiologique_optimise'], 'prop_radiologique_optimise', $object->prop_radiologique_optimise, '', '', '', 0).'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">Effectif</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$effectif_optimise.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->effectif_optimise.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"></td>
								</tr>
								<tr>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'."Durée d'intervention</td>".
									'<td style="border: 0.5px solid  #001b40; text-align: center;">'.$duree_intervention_optimise.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->duree_intervention_optimise.'</td>
									<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>h</strong></td>
								</tr>
								<tr>
									<td colspan="6" rowspan="2" style="border: 0.5px solid  #001b40;">Commentaires : '.$object->commentaire_aoa.'</td>
								</tr>
								<tr>
								</tr>
							</tbody>
						</table>';
				$pdf->writeHTML($html);
				$html = '<table style="padding: 5px;">
							<tbody>
							<tr>
								<td style="border: 0.5px solid  #001b40; text-align: center;"><strong>'."Réf. document client (Numéro d'IZ...) : ".'</strong></td>
								<td style="border: 0.5px solid  #001b40; text-align: center;">'.$object->ref_doc_client.'</td>
							</tr>
							</tbody>
						</table>';
				$pdf->writeHTML($html);

				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}





				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				// Titre "Intervenants"
				$html = '<span style="text-align: center;">'."<strong>Désignation des intervenants et prise en compte du prévisionnel par les intervenants :</strong></span>".'<hr color="#00285e">';
				$pdf->writeHTML($html);

				// Tableau Intervenants
				$i = 0;
				$tab = 0;
				while ($i < count($object->listIntervenantsForFod())){
					if($tab > 0 && $tab%2 == 0){
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						$top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->MultiCell(0, 3, ''); // Set interline to 3
						$pdf->SetTextColor(0, 0, 0);
					}
					
					$liste_intervenants = array_slice($object->listIntervenantsForFod(), $tab*5, 5);
					$i += count($liste_intervenants);
					$html = '';

					$html .= '<table style="padding: 5px;"><tbody>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>Nom</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.$intervenant->lastname.'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>Prénom</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.$intervenant->firstname.'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>Contrat</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$user_fod = New Fod_user($this->db);
						$id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
						$user_fod->fetch($id);
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.$user_fod->showOutputField($user_fod->fields['contrat'], 'contrat', $user_fod->contrat, '', '', '', 0).'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>Durée de Contrat si CDD<br>ou INTERIM (jours travaillés)</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$user_fod = New Fod_user($this->db);
						$id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
						$user_fod->fetch($id);
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.$user_fod->duree_contrat.'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>DI Max (CDD/ET)<br>Prorata temporis (mSv)</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$user_ = New ExtendedUser($this->db);
						$user_->fetch($intervenant->id);
						$user_fod = New Fod_user($this->db);
						$id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
						$user_fod->fetch($id);
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.(!empty($user_fod->duree_contrat) ? $user_->getDoseMaxFod($object) : "").'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>'."Date d'entrée sur l'activité".'</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$user_fod = New Fod_user($this->db);
						$id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
						$user_fod->fetch($id);
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.dol_print_date($user_fod->date_entree).'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>'."Visa intervenant avant activité".'</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$user_fod = New Fod_user($this->db);
						$id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
						$user_fod->fetch($id);
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.$user_fod->showOutputField($user_fod->fields['visa'], 'visa', $user_fod->visa, '', '', '', 0).($user_fod->visa == 1 ? ' : '.dol_print_date($user_fod->date_visa) : '').'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '<tr><td style="border: 0.5px solid  #001b40; text-align: center;"><strong>'."Date de sortie de l'activité si<br>avant la fin de la période".'</strong></td>'; 
					foreach($liste_intervenants as $intervenant){
						$user_fod = New Fod_user($this->db);
						$id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
						$user_fod->fetch($id);
						if (!empty($object->date_fin_prolong)){
							$date = $object->date_fin_prolong;
						}
						else {
							$date = $object->date_fin;
						} 
						$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;">'.($user_fod->date_sortie < $date ? dol_print_date($user_fod->date_sortie) : '/').'</td>';
					}
					if ($tab > 0 && count($liste_intervenants) < 5) {
						$td = 0;
						while ($td < 5 - count($liste_intervenants)){
							$html .= '<td style="border: 0.5px solid  #001b40; text-align: center;"></td>';
							$td++;
						}
					}
					$html .= '</tr>';

					$html .= '</tbody></table><br><br>';
					$pdf->writeHTML($html);
					$tab++;

				}


				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				// BILAN lorsque la FOD est clôturée
				if ($object->status == Fod::STATUS_CLOTURE){
					// New page
					$pdf->AddPage();
					if (!empty($tplidx)) {
						$pdf->useTemplate($tplidx);
					}
					$pagenb++;
					$top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->MultiCell(0, 3, ''); // Set interline to 3
					$pdf->SetTextColor(0, 0, 0);

					$html = '<h1 style="text-align: center;">'. "Bilan radiologique de l'intervention</h1><br/>";
					$html .= '<span style="text-align: center;">'."<strong>Statut des doses individuelles (RSR)</strong></span>".'<hr color="#00285e">';
					$pdf->writeHTML($html);

					$html = '<table style="width: 100%; margin: auto; border-collapse: collapse;">';
					// Question 1 
					$key = 'q1_doses_individuelles';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; width: 20%; text-align: center; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					// Question 2 
					$key = 'q2_doses_individuelles';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; width: 20%; text-align: center; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					// Question 3 
					$key = 'q3_doses_individuelles';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; width: 20%; text-align: center; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					$html .= '</table><br/><br/>';
					$pdf->writeHTML($html);

					// Dose collective (automatique)
					$html = '<span style="text-align: center;">'."<strong>Récapitulatif de la dose collective relevée</strong></span>".'<hr color="#00285e">';
					$pdf->writeHTML($html);
					$html = '<table style="width: 100%; margin: auto; border-collapse: collapse;">';
					$html .= '<tr>';
					$html .= '<td style="border: 0.5px solid  #001b40; text-align: center; width: 50%;">';
					$html .= '<strong>Dose collective réalisée</strong>';
					$html .= '</td>';
					$html .= '<td style="border: 0.5px solid  #001b40; width: 50%; text-align: center;"><strong>';
					$html .= $object->GetDoseCollectiveReel().' H.mSv';
					$html .= '</strong></td></tr>';
					$html .= '</table><br/><br/>';
					$pdf->writeHTML($html);

					// Statut de la dose collective (RSR)
					$html = '<span style="text-align: center;">'."<strong>Statut de la dose collective (RSR)</strong></span>".'<hr color="#00285e">';
					$pdf->writeHTML($html);
					$html = '<table style="width: 100%; margin: auto; border-collapse: collapse;">';
					// Question 1 
					$key = 'q1_dose_collective';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					// Question 2 
					$key = 'q2_dose_collective';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #007ac6;"><strong>'; 
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					$html .= '</table><br/><br/>';
					$pdf->writeHTML($html);

					// Statut de la contamination et  propreté radiologique (RSR)
					$html = '<span style="text-align: center;">'."<strong>Statut de la contamination et propreté radiologique (RSR)</strong></span>".'<hr color="#00285e">';
					$pdf->writeHTML($html);
					$html = '<table style="width: 100%; margin: auto; border-collapse: collapse;">';
					// Question 1 
					$key = 'q1_contamination';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					// Question 2 
					$key = 'q2_contamination';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					$html .= '</table><br/><br/>';
					$pdf->writeHTML($html);

					// Rapprochement SISERI (PCR)
					$html = '<span style="text-align: center;">'."<strong>Rapprochement SISERI (PCR)</strong></span>".'<hr color="#00285e">';
					$pdf->writeHTML($html);
					$html = '<table style="width: 100%; margin: auto; border-collapse: collapse;">';
					// Question 1 
					$key = 'q1_siseri';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					$html .= '</table><br/><br/>';
					$pdf->writeHTML($html);

					// RETOUR D'EXPERIENCE (Intervenants / RSR / RA / PCR)
					$html = '<span style="text-align: center;">'."<strong>RETOUR D'EXPERIENCE (Intervenants / RSR / RA / PCR)</strong></span>".'<hr color="#00285e">';
					$pdf->writeHTML($html);
					$html = '<table style="width: 100%; margin: auto; border-collapse: collapse;">';
					// REX intervenants
					$user_fod = New Fod_user($db);
					foreach($object->listIntervenantsForFod() as $intervenant){
						$key = 'rex_intervenant';
						$user_fod_id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
						$user_fod->fetch($user_fod_id);
						$val = $user_fod->fields[$key];
						$value = $user_fod->$key;
						$html .= '<tr class="field_'.$key.'"><td';
						$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
						$html .= $intervenant->firstname.' '.$intervenant->lastname.'</td>';
						$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
						$html .= $user_fod->showOutputField($val, $key, $value, '', '', '', 0);
						$html .= '</strong></td>';

						$key = 'com_rex';
						$val = $user_fod->fields[$key];
						$value = $user_fod->$key;
						$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
						$html .= $user_fod->showOutputField($val, $key, $value, '', '', '', 0);
						$html .= '</strong></td>';
						$html .= '</tr>';
					}

					// Question 2 
					$key = 'rex_rsr';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';

					$key = 'com_rex_rsr';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					//$html .= '</table><br/><br/>';
					

					// Question 3 
					$key = 'rex_ra';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';

					$key = 'com_rex_ra';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';

					// Question 4 
					$key = 'rex_pcr';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';

					$key = 'com_rex_pcr';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';

					// Question 5
					$key = 'rex_rd';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';

					$key = 'com_rex_rd';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					$html .= '</table><br/><br/>';
					$pdf->writeHTML($html);

					// Bilan et Vérification en radioprotection (PCR)
					$html = '<span style="text-align: center;">'."<strong>Bilan et Vérification en radioprotection (PCR)</strong></span>".'<hr color="#00285e">';
					$pdf->writeHTML($html);
					$html = '<table style="width: 100%; margin: auto; border-collapse: collapse;">';
					// Question 1 
					$key = 'q1_radiopotection';
					$val = $object->fields[$key];
					$value = $object->$key;
					$html .= '<tr class="field_'.$key.'"><td';
					$html .= ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 50%;">';
					$html .= $langs->trans($val['label']).'</td>';
					$html .= '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 50%; background-color: #007ac6;"><strong>';
					$html .= $object->showOutputField($val, $key, $value, '', '', '', 0);
					$html .= '</strong></td>';
					$html .= '</tr>';
					$html .= '</table><br/><br/>';
					$pdf->writeHTML($html);


					// Pagefoot
					$this->_pagefoot($pdf, $object, $outputlangs);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
				}





				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

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
		return parent::liste_modeles($db, $maxfilenamelength); // TODO: Change the autogenerated stub
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
	 *  @param	Tcpdf			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies", "fod@fod"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == $object::STATUS_DRAFT && (!empty($conf->global->FACTURE_DRAFT_WATERMARK))) {
			  pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FACTURE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 50;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) {
					$logodir = $conf->mycompany->multidir_output[$object->entity];
				}
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
					$logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
				} else {
					$logo = $logodir.'/logos/'.$this->emetteur->logo;
				}
				if (is_readable($logo)) {
					$height = pdf_getHeightForLogo($logo);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			} else {
				$text = $this->emetteur->name;
				$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}



		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetTextColor(0, 0, 60);

		$textref = $outputlangs->transnoentities("ClientSite")." : ".$outputlangs->convToOutputCharset($object->client_site);
		$pdf->SetXY(($this->page_largeur/2) - ($pdf->GetStringWidth($textref)/2) - 2, $posy);
		$pdf->MultiCell($pdf->GetStringWidth($textref) + 4, 4, $textref, '', 'L');

		$pdf->SetXY($posx, $posy);
		$textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		/*if ($object->status == $object::STATUS_DRAFT) {
			$pdf->SetTextColor(128, 0, 0);
			$textref .= ' - '.$outputlangs->transnoentities("NotValidated");
		}*/
		$pdf->MultiCell($w, 4, $textref, '', 'L');

		$posy += 4;
		$textref = $outputlangs->transnoentities("Installation")." : ".$outputlangs->convToOutputCharset($object->installation);
		$pdf->SetXY(($this->page_largeur/2) - ($pdf->GetStringWidth($textref)/2) - 2, $posy);
		$pdf->MultiCell($pdf->GetStringWidth($textref) + 4, 4, $textref, '', 'L');
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($w, 4, $outputlangs->transnoentities("Ind")." : ".$object->indice, '', 'L');

		$posy += 4;
		$textref = $outputlangs->transnoentities("EtatInstallation")." : ".$outputlangs->convToOutputCharset($object->fields['etat_installation']['arrayofkeyval'][$object->etat_installation]).$outputlangs->convToOutputCharset($object->commentaire_etat_installation);
		$pdf->SetXY(($this->page_largeur/2) - ($pdf->GetStringWidth($textref)/2) - 2, $posy);
		$pdf->MultiCell($pdf->GetStringWidth($textref) + 4, 4, $textref, '', 'L');
		if (!empty($object->fk_project)) {
			$projet = New Project($this->db);
			$projet->fetch($object->fk_project);
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Affaires")." : ".(empty($projet->ref) ? '' : $projet->ref), '', 'L');
		}

		$posy += 4;
		$textref = $outputlangs->transnoentities("Activite")." : ".$outputlangs->convToOutputCharset($object->activite);
		$pdf->SetXY(($this->page_largeur/2) - ($pdf->GetStringWidth($textref)/2) - 2, $posy);
		$pdf->MultiCell($pdf->GetStringWidth($textref) + 4, 4, $textref, '', 'L');

		$posy += 6;
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetTextColor(0, 40, 95);
		$title = $outputlangs->transnoentities("PdfTitle");
		$pdf->SetXY(($this->page_largeur/2) - ($pdf->GetStringWidth($title)/2) - 5, $posy);
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && is_object($outputlangsbis)) {
			$title .= ' - ';
			$title .= $outputlangsbis->transnoentities("PdfTitle");
		}
		$pdf->MultiCell($pdf->GetStringWidth($title)+10, 8, $title, '', 'C');

		$pdf->WriteHTML('<hr color="#00285e">');
		
		/*if (!empty($conf->global->PDF_SHOW_PROJECT)) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$outputlangs->load("projects");
				$posy += 3;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefProject")." : ".(empty($object->project->ref) ? '' : $object->project->ref), '', 'R');
			}
		}*/

		/*$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);

		$title = $outputlangs->transnoentities("Date");
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && is_object($outputlangsbis)) {
			$title .= ' - '.$outputlangsbis->transnoentities("Date");
		}
		$pdf->MultiCell($w, 3, $title." : ".dol_print_date($object->date, "day", false, $outputlangs), '', 'R');*/

		/*if ($object->thirdparty->code_client) {
			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}*/

		// Get contact
		/*if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
			$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0) {
				$usertmp = new User($this->db);
				$usertmp->fetch($arrayidcontact[0]);
				$posy += 4;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 3, $langs->transnoentities("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
			}
		}*/

		/*$posy += 1;
		$top_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY()) {
			$top_shift = $pdf->getY() - $current_y;
		}*/

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}

			$hautcadre = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;


			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

			// If BILLING contact defined on invoice, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'BILLING');
			if (count($arrayidcontact) > 0) {
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			// Recipient name
			if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			if (is_object($thirdparty)) {
				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
			}

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0, 0, 0);
		return $top_shift;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
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
}
