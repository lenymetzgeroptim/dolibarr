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
 *  \file       core/modules/ot/doc/pdf_standard.modules.php
 *  \ingroup    ot
 *  \brief      File of class to generate document from standard template
 */

dol_include_once('/constat/core/modules/constat/modules_constat.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/constat/lib/pdf.lib.php';


/**
 *	Class to manage PDF template standard_ot
 */
class pdf_standard_constat extends ModelePDFConstat
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
	 * e.g.: PHP ≥ 7.0 = array(7, 0)
	 */
	public $phpmin = array(7, 0);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

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
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

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
		if (getDolGlobalInt('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			global $outputlangsbis;
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
		}

		$nblines = (is_array($object->lines) ? count($object->lines) : 0);

		$hidetop = 0;
		if (getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE')) {
			$hidetop = getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE');
		}

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		/*
		if (getDolGlobalInt('MAIN_GENERATE_MYOBJECT_WITH_PICTURE'))) {
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++)
			{
				if (empty($object->lines[$i]->fk_product)) continue;

				//var_dump($objphoto->ref);exit;
				if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
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
							if (!getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES'))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
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


		
		if ($conf->constat->dir_output.'/constat') {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->constat->dir_output.'/constat';
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->constat->dir_output.'/constat/'.$objectref;
				$file = $dir."/".$objectref.".pdf";
			}
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}



			if (file_exists($dir)) {
				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new facture lines content after hook
				$nblines = (is_array($object->lines) ? count($object->lines) : 0);

				// Create pdf instance
				$pdf = pdf_getInstanceCustomConstat($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(true);
					$pdf->setPrintFooter(true);
					$pdf->SetMargins(10, 35, 10); 
					$pdf->SetAutoPageBreak(TRUE, 15);
         		
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->multidir_output[$object->entity].'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Constat"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Constat")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				// Set certificate
				$cert = empty($user->conf->CERTIFICATE_CRT) ? '' : $user->conf->CERTIFICATE_CRT;
				// If user has no certificate, we try to take the company one
				if (!$cert) {
					$cert = getDolGlobalString('CERTIFICATE_CRT');
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


				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				
				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 90 + $top_shift;
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);
				$tab_height = 130 - $top_shift;
				$tab_height_newpage = 150;
				if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
					$tab_height_newpage -= $top_shift;
				}

				$nexY = $tab_top - 1;

				// Display notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				// Extrafields in note
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}

				$pagenb = $pdf->getPage();
				if ($notetoshow) {
					$tab_top -= 2;

					$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					$pageposbeforenote = $pagenb;

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->startTransaction();

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					// Description
					$pageposafternote = $pdf->getPage();
					$posyafter = $pdf->GetY();

					if ($pageposafternote > $pageposbeforenote) {
						$pdf->rollbackTransaction(true);

						// prepare pages to receive notes
						while ($pagenb < $pageposafternote) {
							$pdf->AddPage();
							$pagenb++;
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							}
							// $this->_pagefoot($pdf,$object,$outputlangs,1);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
						}

						// back to start
						$pdf->setPage($pageposbeforenote);
						$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
						$pageposafternote = $pdf->getPage();

						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {	// There is no space left for total+free text
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
							//$posyafter = $tab_top_newpage;
						}


						// apply note frame to previous pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);


							$pdf->SetDrawColor(128, 128, 128);
							// Draw note frame
							if ($i > $pageposbeforenote) {
								$height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
								$pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
							} else {
								$height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
								$pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);
							}

							// Add footer
							$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						$height_note = $posyafter - $tab_top_newpage;
						$pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
					} else // No pagebreak
					{
						$pdf->commitTransaction();
						$posyafter = $pdf->GetY();
						$height_note = $posyafter - $tab_top;
						$pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);


						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							}

							$posyafter = $tab_top_newpage;
						}
					}

					$tab_height = $tab_height - $height_note;
					$tab_top = $posyafter + 6;
				} else {
					$height_note = 0;
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Table simulation to know the height of the title line
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				$pdf->rollbackTransaction(true);

				$nexY = $tab_top + $this->tabTitleHeight;

				// Loop on each lines
				$pageposbeforeprintlines = $pdf->getPage();
				$pagenb = $pageposbeforeprintlines;
				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					// Define size of image if we need it
					$imglinesize = array();
					if (!empty($realpatharray[$i])) {
						$imglinesize = pdf_getSizeForImage($realpatharray[$i]);
					}

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;

					if ($this->getColumnStatus('photo')) {
						// We start with Photo of product line
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// If photo too high, we moved completely on new page
							$pdf->AddPage('', '', true);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							$pdf->setPage($pageposbefore + 1);

							$curY = $tab_top_newpage;

							// Allows data in the first page if description is long enough to break in multiples pages
							if (getDolGlobalInt('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}
						}

						if (!empty($this->cols['photo']) && isset($imglinesize['width']) && isset($imglinesize['height'])) {
							$pdf->Image($realpatharray[$i], $this->getColumnContentXStart('photo'), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300); // Use 300 dpi
							// $pdf->Image does not increase value return by getY, so we save it manually
							$posYAfterImage = $curY + $imglinesize['height'];
						}
					}

					// Description of product line
					if ($this->getColumnStatus('desc')) {
						$pdf->startTransaction();

						$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
						$pageposafter = $pdf->getPage();

						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							$pdf->rollbackTransaction(true);
							$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.

							$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

							$pageposafter = $pdf->getPage();
							$posyafter = $pdf->GetY();
							//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) {
										$pdf->useTemplate($tplidx);
									}
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								// We found a page break
								// Allows data in the first page if description is long enough to break in multiples pages
								if (getDolGlobalInt('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
									$showpricebeforepagebreak = 1;
								} else {
									$showpricebeforepagebreak = 0;
								}
							}
						} else // No pagebreak
						{
							$pdf->commitTransaction();
						}
					}

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par defaut

					// Quantity
					// Enough for 6 chars
					if ($this->getColumnStatus('qty')) {
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $curY, 'qty', $qty);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Extrafields
					if (!empty($object->lines[$i]->array_options)) {
						foreach ($object->lines[$i]->array_options as $extrafieldColKey => $extrafieldValue) {
							if ($this->getColumnStatus($extrafieldColKey)) {
								$extrafieldValue = $this->getExtrafieldContent($object->lines[$i], $extrafieldColKey, $outputlangs);
								$this->printStdColumnContent($pdf, $curY, $extrafieldColKey, $extrafieldValue);
								$nexY = max($pdf->GetY(), $nexY);
							}
						}
					}


					$parameters = array(
						'object' => $object,
						'i' => $i,
						'pdf' =>& $pdf,
						'curY' =>& $curY,
						'nexY' =>& $nexY,
						'outputlangs' => $outputlangs,
						'hidedetails' => $hidedetails
					);
					$reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this); // Note that $object may have been modified by hook


					$sign = 1;
					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
					if ($prev_progress > 0 && !empty($object->lines[$i]->situation_percent)) { // Compute progress from previous situation
						if (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) {
							$tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
						} else {
							$tvaligne = $sign * $object->lines[$i]->total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
						}
					} else {
						if (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) {
							$tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva;
						} else {
							$tvaligne = $sign * $object->lines[$i]->total_tva;
						}
					}

					$localtax1ligne = $object->lines[$i]->total_localtax1;
					$localtax2ligne = $object->lines[$i]->total_localtax2;
					$localtax1_rate = $object->lines[$i]->localtax1_tx;
					$localtax2_rate = $object->lines[$i]->localtax2_tx;
					$localtax1_type = $object->lines[$i]->localtax1_type;
					$localtax2_type = $object->lines[$i]->localtax2_type;

					if ($object->remise_percent) {
						$tvaligne -= ($tvaligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax1ligne -= ($localtax1ligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax2ligne -= ($localtax2ligne * $object->remise_percent) / 100;
					}

					$vatrate = (string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
						&& (!empty($localtax1_rate) || !empty($localtax2_rate))) { // and there is local tax
						$localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
						$localtax1_type = isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
						$localtax2_type = isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
					}

					// retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0) {
						if (empty($this->localtax1[$localtax1_type][$localtax1_rate])) {
							$this->localtax1[$localtax1_type][$localtax1_rate] = $localtax1ligne;
						} else {
							$this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
						}
					}
					if ($localtax2_type && $localtax2ligne != 0) {
						if (empty($this->localtax2[$localtax2_type][$localtax2_rate])) {
							$this->localtax2[$localtax2_type][$localtax2_rate] = $localtax2ligne;
						} else {
							$this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;
						}
					}

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
						$vatrate .= '*';
					}
					if (!isset($this->tva[$vatrate])) {
						$this->tva[$vatrate] = 0;
					}
					$this->tva[$vatrate] += $tvaligne;

					$nexY = max($nexY, $posYAfterImage);


					// Add line
					if (getDolGlobalInt('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1', 'color'=>array(80, 80, 80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY, $this->page_largeur - $this->marge_droite, $nexY);
						$pdf->SetLineStyle(array('dash'=>0));
					}

				

				}

				// Show square


				// Display infos area
				//$posy = $this->drawInfoTable($pdf, $object, $bottomlasttab, $outputlangs);

				// Display total zone
				//$posy = $this->drawTotalTable($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Display payment area
				/*
				if (($deja_regle || $amount_credit_notes_included || $amount_deposits_included) && !getDolGlobalInt('INVOICE_NO_PAYMENT_DETAILS')))
				{
					$posy = $this->drawPaymentsTable($pdf, $object, $posy, $outputlangs);
				}
				*/
			
				// Connexion à la base de données
				

				// Récupération des données de l'objet
				$actionsimmediates = intval($object->actionsimmediates);
				$infoclient = intval($object->infoclient);
				$recurent = intval($object->recurent);
				$recurent_display = $recurent == 1 ? "Oui" : "Non";


				// Formater la date dans le format "Y-m-d"
				$formattedDateEmetteur = dol_print_date($object->emetteur_date, '%Y-%m-%d');
				$formattedDateActionImmediate = dol_print_date($object->actionsimmediates_date, '%Y-%m-%d');
				$formattedDateInfoClient = dol_print_date($object->infoclient_date, '%Y-%m-%d');
				$formattedDateAccordClient = dol_print_date($object->accordclient_date, '%Y-%m-%d');
				$formattedDateControleClient = dol_print_date($object->controleclient_date, '%Y-%m-%d');
				$formattedDateCloture = dol_print_date($object->cloture_date, '%Y-%m-%d');


				// Correspondances des valeurs
				$sujetLabels = $object->getAllSujet();
				$typeLabels = $object->getAllType();
				$statusLabels = $object->field['status']['arrayofkeyval'];
				$impact_map = $object->getAllImpact();
				$processus_map = $object->getAllProcessus();
				$rubrique_map = $object->getAllRubrique();

				// Conversion des IDs en noms lisibles
				$sujet_nom = isset($sujetLabels[$object->sujet]) ? $sujetLabels[$object->sujet] : 'Inconnu';
				$type_constat_nom = isset($typeLabels[$object->type_constat]) ? $typeLabels[$object->type_constat] : 'Inconnu';
				$status_nom = isset($statusLabels[$object->status]) ? $statusLabels[$object->status] : 'Inconnu';

				$impact_ids = isset($object->impact) ? explode(',', $object->impact) : [];
				$rubrique_ids = isset($object->rubrique) ? explode(',', $object->rubrique) : [];
				$processus_ids = isset($object->processus) ? explode(',', $object->processus) : [];
				$impacts = array_map(function($id) use ($impact_map) {
					return isset($impact_map[$id]) ? $impact_map[$id] : $id;
				}, $impact_ids);

				$rubriques = array_map(function($id) use ($rubrique_map) {
					return isset($rubrique_map[$id]) ? $rubrique_map[$id] : $id;
				}, $rubrique_ids);

				$processus = array_map(function($id) use ($processus_map) {
					return isset($processus_map[$id]) ? $processus_map[$id] : $id;
				}, $processus_ids);
				// Générer la chaîne des noms en les séparant par une virgule
				$impact_display = implode(', ', $impacts);
				$rubrique_display = implode(', ', $rubriques);
				$processus_display = implode(', ', $processus);
				$impact_display_safe = htmlspecialchars($impact_display, ENT_QUOTES, 'UTF-8');



				// Requête pour récupérer la référence du projet
				$sql = "SELECT p.rowid, p.ref, p.title";
				$sql .= " FROM " . MAIN_DB_PREFIX . "projet AS p";
				$sql .= " WHERE p.rowid IN (".$object->fk_project.")";

				// Exécuter la requête projet
				$resql = $db->query($sql);

				// Vérifiez si la requête a échoué
				if ($resql === false) {
					echo 'SQL Error: ' . $db->lasterror();
					exit;
				}

				// Vérifiez si des lignes sont retournées
				while($projet = $resql->fetch_object()) {
					$ref_projet = htmlspecialchars($projet->ref);
					$intitule_projet = htmlspecialchars($projet->title);
					$projet_combined .= htmlspecialchars($ref_projet).' - '.htmlspecialchars($intitule_projet).' / ';
				}
				$projet_combined = rtrim($projet_combined, ' / ');



				$sql = "SELECT com.rowid, com.ref";
				$sql .= " FROM " . MAIN_DB_PREFIX . "commande AS com";
				$sql .= " WHERE com.rowid IN (".$object->num_commande.")";

				// Exécuter la requête site
				$resql = $db->query($sql);

				// Vérifiez si la requête a échoué
				if ($resql === false) {
					echo 'SQL Error: ' . $db->lasterror();
					exit;
				}

				// Vérifiez si des lignes sont retournées
				while($commande = $resql->fetch_object()) {
					$ref_commande .= htmlspecialchars($commande->ref)." / ";
				}
				$ref_commande = rtrim($ref_commande, ' / ');



				// Requête pour récupérer le nom du site
				$sql = "SELECT s.rowid, s.nom";
				$sql .= " FROM " . MAIN_DB_PREFIX . "societe AS s";
				$sql .= " WHERE s.rowid = ".$object->site;

				// Exécuter la requête site
				$resql = $db->query($sql);

				// Vérifiez si la requête a échoué
				if ($resql === false) {
					echo 'SQL Error: ' . $db->lasterror();
					exit;
				}

				// Vérifiez si des lignes sont retournées
				if ($resql->num_rows > 0) {
					$site = $resql->fetch_object();
					if ($site) {
						$nom_site = htmlspecialchars($site->nom);
					} 
				}

			

			$user_static = new User($db);
			$user_static->fetch($object->fk_user_creat);
			$firstnameEmetteur = htmlspecialchars($user_static->firstname);
			$lastnameEmetteur = htmlspecialchars($user_static->lastname);

			// Décoder les entités HTML dans la description
			$description_impact = html_entity_decode($object->description_impact, ENT_QUOTES, 'UTF-8');

			// Remplacer les balises <div> par des nouvelles lignes
			$description_impact = str_replace(['<div>', '</div>'], "\n", $description_impact);

			// Traiter les balises <ul> et <li> pour la description
			$description_impact = preg_replace_callback('/<ul>(.*?)<\/ul>/is', function($matches) {
				// Convertir chaque élément de la liste
				return "\n" . preg_replace('/<li>(.*?)<\/li>/is', '• $1', $matches[1]) . "\n"; // Ajouter des puces
			}, $description_impact);

			// Supprimer toutes les autres balises HTML et retirer les espaces
			$description_impact = strip_tags($description_impact);
			$description_impact = preg_replace("/\n\s*\n+/", "\n", $description_impact); // Retirer les lignes vides
			$description_impact_safe = nl2br(htmlspecialchars(trim($description_impact), ENT_QUOTES, 'UTF-8'));


			// Décoder les entités HTML dans la description
			$description_constat = html_entity_decode($object->description_constat, ENT_QUOTES, 'UTF-8');

			// Remplacer les balises <div> par des nouvelles lignes
			$description_constat = str_replace(['<div>', '</div>'], "\n", $description_constat);

			// Traiter les balises <ul> et <li> pour la description
			$description_constat = preg_replace_callback('/<ul>(.*?)<\/ul>/is', function($matches) {
				// Convertir chaque élément de la liste
				return "\n" . preg_replace('/<li>(.*?)<\/li>/is', '• $1', $matches[1]) . "\n"; // Ajouter des puces
			}, $description_constat);

			// Supprimer toutes les autres balises HTML et retirer les espaces
			$description_constat = strip_tags($description_constat);
			$description_constat = preg_replace("/\n\s*\n+/", "\n", $description_constat); // Retirer les lignes vides
			$description_constat_safe = nl2br(htmlspecialchars(trim($description_constat), ENT_QUOTES, 'UTF-8'));



			// Traitement de l'action immédiate
			$actionsimmediates_commentaire = html_entity_decode($object->actionsimmediates_commentaire, ENT_QUOTES, 'UTF-8');

			// Remplacer les balises <div> par des nouvelles lignes
			$actionsimmediates_commentaire = str_replace(['<div>', '</div>'], "\n", $actionsimmediates_commentaire);

			// Traiter les balises <ul> et <li> pour l'action immédiate
			$actionsimmediates_commentaire = preg_replace_callback('/<ul>(.*?)<\/ul>/is', function($matches) {
				// Convertir chaque élément de la liste
				return "\n" . preg_replace('/<li>(.*?)<\/li>/is', '• $1', $matches[1]) . "\n"; // Ajouter des puces
			}, $actionsimmediates_commentaire);

			// Supprimer toutes les autres balises HTML et retirer les espaces
			$actionsimmediates_commentaire = strip_tags($actionsimmediates_commentaire);
			$actionsimmediates_commentaire = preg_replace("/\n\s*\n+/", "\n", $actionsimmediates_commentaire); // Retirer les lignes vides
			$actionsimmediates_commentaire_safe = nl2br(htmlspecialchars(trim($actionsimmediates_commentaire), ENT_QUOTES, 'UTF-8'));
			$actionsimmediates_display = $actionsimmediates == 1 ? "Oui" : "Non";


			// Traitement des informations client
			$infoclientcomm = html_entity_decode($object->infoclient_commentaire, ENT_QUOTES, 'UTF-8');

			// Remplacer les balises <div> par des nouvelles lignes
			$infoclientcomm = str_replace(['<div>', '</div>'], "\n", $infoclientcomm);

			// Traiter les balises <ul> et <li> pour les informations client
			$infoclientcomm = preg_replace_callback('/<ul>(.*?)<\/ul>/is', function($matches) {
				// Convertir chaque élément de la liste
				return "\n" . preg_replace('/<li>(.*?)<\/li>/is', '• $1', $matches[1]) . "\n"; // Ajouter des puces
			}, $infoclientcomm);

			// Supprimer toutes les autres balises HTML et retirer les espaces
			$infoclientcomm = strip_tags($infoclientcomm);
			$infoclientcomm = preg_replace("/\n\s*\n+/", "\n", $infoclientcomm); // Retirer les lignes vides
			$infoclientcomm_safe = nl2br(htmlspecialchars(trim($infoclientcomm), ENT_QUOTES, 'UTF-8'));
			$infoclient_display = $infoclient == 1 ? "Oui" : "Non";

			// Décoder les entités HTML dans la description
			$analyseracine = html_entity_decode($object->analyse_cause_racine, ENT_QUOTES, 'UTF-8');

			// Remplacer les balises <div> par des nouvelles lignes
			$analyseracine = str_replace(['<div>', '</div>'], "\n", $analyseracine);

			// Traiter les balises <ul> et <li> pour la description
			$analyseracine = preg_replace_callback('/<ul>(.*?)<\/ul>/is', function($matches) {
				// Convertir chaque élément de la liste
				return "\n" . preg_replace('/<li>(.*?)<\/li>/is', '• $1', $matches[1]) . "\n"; // Ajouter des puces
			}, $analyseracine);

			// Supprimer toutes les autres balises HTML et retirer les espaces
			$analyseracine = strip_tags($analyseracine);
			$analyseracine = preg_replace("/\n\s*\n+/", "\n", $analyseracine); // Retirer les lignes vides
			$analyseracine_safe = nl2br(htmlspecialchars(trim($analyseracine), ENT_QUOTES, 'UTF-8'));


			$accordclient = intval($object->accordclient);
				
			// Traitement des informations client
			$accordClientcomm = html_entity_decode($object->accordclient_commentaire, ENT_QUOTES, 'UTF-8');

			// Remplacer les balises <div> par des nouvelles lignes
			$accordClientcomm = str_replace(['<div>', '</div>'], "\n", $accordClientcomm);

			// Traiter les balises <ul> et <li> pour les informations client
			$accordClientcomm = preg_replace_callback('/<ul>(.*?)<\/ul>/is', function($matches) {
				// Convertir chaque élément de la liste
				return "\n" . preg_replace('/<li>(.*?)<\/li>/is', '• $1', $matches[1]) . "\n"; // Ajouter des puces
			}, $accordClientcomm);

			// Supprimer toutes les autres balises HTML et retirer les espaces
			$accordClientcomm = strip_tags($accordClientcomm);
			$accordClientcomm = preg_replace("/\n\s*\n+/", "\n", $accordClientcomm); // Retirer les lignes vides
			$accordClientcomm_safe = nl2br(htmlspecialchars(trim($accordClientcomm), ENT_QUOTES, 'UTF-8'));
			$accordClient_display = intval($accordclient) === 1 ? "Oui" : "Non";


			$controleclient = intval($object->controleclient);
				
			// Traitement des informations client
			$controleClientcomm = html_entity_decode($object->controleclient_commentaire, ENT_QUOTES, 'UTF-8');

			// Remplacer les balises <div> par des nouvelles lignes
			$controleClientcomm = str_replace(['<div>', '</div>'], "\n", $controleClientcomm);

			// Traiter les balises <ul> et <li> pour les informations client
			$controleClientcomm = preg_replace_callback('/<ul>(.*?)<\/ul>/is', function($matches) {
				// Convertir chaque élément de la liste
				return "\n" . preg_replace('/<li>(.*?)<\/li>/is', '• $1', $matches[1]) . "\n"; // Ajouter des puces
			}, $controleClientcomm);

			// Supprimer toutes les autres balises HTML et retirer les espaces
			$controleClientcomm = strip_tags($controleClientcomm);
			$controleClientcomm = preg_replace("/\n\s*\n+/", "\n", $controleClientcomm); // Retirer les lignes vides
			$controleClientcomm_safe = nl2br(htmlspecialchars(trim($controleClientcomm), ENT_QUOTES, 'UTF-8'));
			$controleClient_display = intval($controleclient) === 1 ? "Oui" : "Non";
			

			$sql = "SELECT e.fk_target, e.fk_source, a.status";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_element as e";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actions_action as a ON e.fk_target = a.rowid";
			$sql .= " WHERE e.fk_source = $object->id AND e.sourcetype = 'constat' ";

			$result = $db->query($sql);

			$is_all_sold = true;  // Assumption: all actions are sold

			if ($result) {
				$nume = $db->num_rows($result);
				$i = 0;
				while ($i < $nume) {
					$obj = $db->fetch_object($result);
					$selectedelement[$obj->fk_source][$obj->fk_target] = $obj; 
					$status[] = $obj->status; 
					
					// Check if any status is not equal to '3'
					if ($obj->status !== '3') {
						$is_all_sold = false;
					}
					
					$i++;
				}
			} else {
				dol_print_error($db);
			}


			// Génération du tableau HTML
			$html = '
			<table border="0" cellpadding="5" cellspacing="0" width="100%"><table border="0" cellpadding="5" cellspacing="0" width="100%">
				<tr><td colspan="4"><strong> IDENTIFICATION DE L\'EMETTEUR </strong></td></tr>
				<tr>
					<td><strong>Émeteur</strong></td>
					<td>' . $firstnameEmetteur . ' ' . $lastnameEmetteur . '</td>
					<td><strong>Site</strong></td>
					<td>' . nl2br(htmlspecialchars($nom_site)) . '</td>
				</tr>
				<tr>
					<td><strong>Date de création</strong></td>
					<td>' . nl2br(htmlspecialchars($formattedDateEmetteur)) . '</td>
					<td rowspan="2" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong>Affaire</strong></td>
					<td rowspan="2" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . nl2br($projet_combined ) . '</td>
				</tr>
				<tr>
					<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong>Commande</strong></td>
					<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . nl2br(htmlspecialchars($ref_commande)) . '</td>
					
				</tr>
			</table>';


			$html .= '
			<table border="0" cellpadding="5" cellspacing="0" width="100%"><table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr><td colspan="2"><strong> OBJET DU CONSTAT </strong></td></tr>
			<tr>
					<td><strong>Sujet</strong></td>
					<td style="color: rgb(40, 80, 139);">' . nl2br(htmlspecialchars($sujet_nom)) .  '</td>
			</tr>
			</table>';



			$html .= '
			<table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
					<td><strong>Description détaillée du constat (Quoi, Qui, Où, Quand, Comment, Combien, Pourquoi) : </strong></td>
			</tr>
			<tr>
					<td>' . $description_constat_safe . '</td>
			</tr>
			</table>';


			$html .= '
			<table border="0" cellpadding="5" cellspacing="0" width="100%">
				<tr>
					<td><strong>Impact</strong></td>
					<td style="color: rgb(40, 80, 139);">' . $impact_display_safe . '</td>
				</tr>
				<tr>
					<td colspan="2"><strong>Description impact</strong></td>
				</tr>
				<tr>
					<td colspan="2" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $description_impact_safe . '</td>
				</tr>
				<tr>
					<td><strong>Type de constat</strong></td>
					<td style ="color: rgb(40, 80, 139);">' . $type_constat_nom . '</td>
				</tr>
				<tr>
					<td><strong>Rubrique</strong></td>
					<td style ="color: rgb(40, 80, 139);">' . $rubrique_display . '</td>
				</tr>
				<tr>
					<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong>Processus</strong></td>
					<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139); color: rgb(40, 80, 139);">' . $processus_display . '</td>
				</tr>
			</table>';
				

				// Vérification de l'affichage d'actionsimmediates_commentaire
				if ($actionsimmediates == 1) {
					$html .= '
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td colspan="2"><strong> ACTION(S) IMMEDIATE(S)</strong></td>
							<td colspan="2" style="color: rgb(40, 80, 139);">' . $actionsimmediates_display . '</td>
						</tr>
						<tr>
							<td><strong>Mise en place le : </strong></td>
							<td>'.$formattedDateActionImmediate.'</td>
							<td><strong>Par : </strong></td>
							<td>'.$object->actionsimmediates_par.'</td>
						</tr>
						<tr>
							<td colspan="4"><strong>Détails de l\'action immédiate : </strong></td>
						</tr>
						<tr>
							<td colspan="4" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $actionsimmediates_commentaire_safe . '</td>
						</tr>
					</table>';
				}
				else {
					$html .= '
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td colspan="2"><strong> ACTION(S) IMMEDIATE(S)</strong></td>
							<td colspan="2" style="color: rgb(40, 80, 139);">' . $actionsimmediates_display . '</td>					
						</tr>
						<tr>
							<td colspan="4"><strong>Détails de l\'action immédiate : </strong></td>
						</tr>
						<tr>
							<td colspan="4" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $actionsimmediates_commentaire_safe . '</td>
						</tr>
					</table>';
				}

				


				if ($infoclient == 1) {
					$html .= '
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td><strong> INFORMATION CLIENT REQUISE </strong></td>
							<td style="color: rgb(40, 80, 139);">' . $infoclient_display . '</td>
						</tr>
					</table>
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td><strong>Le : </strong></td>
							<td>'.$formattedDateInfoClient.'</td>
							<td><strong>Par : </strong></td>
							<td>'.$object->infoclient_par.'</td>
							<td><strong>Visa : </strong></td>	
							<td></td>	
						</tr>
						<tr>
							<td colspan="6"><strong>Commentaire : </strong></td>
						</tr>
						<tr>
							<td colspan="6" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $infoclientcomm_safe . '</td>
						</tr>
					</table>';
				}
				else {
					$html .= '
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td><strong> INFORMATION CLIENT REQUISE </strong></td>
							<td style="color: rgb(40, 80, 139);">' . $infoclient_display . '</td>
						</tr>
					</table>
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td colspan="6"><strong>Commentaire : </strong></td>
						</tr>
						<tr>
							<td colspan="6" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $infoclientcomm_safe . '</td>
						</tr>
					</table>';
				}

				// $html .= '<table border="0" cellpadding="5" cellspacing="0" width="100%">
							
				// 				<tr>
				// 					<td><strong>Validation du service Q3SE :</strong></td>
				// 					<td style="color: rgb(40, 80, 139);"></td>
				// 				</tr>
				// 			<br></br>
				// 		</table>';
				

				$html .= '
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
							<td><strong> ANALYSE DE CAUSE RACINE (Le choix de la méthode est laissé à l\'appréciation du Service Q3SE) </strong></td>
					</tr>
					<tr>
							<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $analyseracine_safe . '</td>
					</tr>
				</table>

				<table border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong> CONSTAT RECURRENT </strong></td>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139); color: rgb(40, 80, 139);">' . $recurent_display . '</td>
					</tr>
				</table>';
				

				$pdf->setY($pdf->getY() - 10);
				$pdf->writeHTML($html, true, false, true, false, '');
				if ($pdf->getPage() == 1) { 
					$pdf->addPage();
				}


				$sql = "SELECT ee.fk_target";
				$sql .= " FROM " . MAIN_DB_PREFIX . "element_element AS ee";
				$sql .= " WHERE ee.sourcetype = 'constat'";
				$sql .= " AND ee.fk_source = " . intval($object->id);
				$sql .= " AND ee.targettype = 'actions_action'";

				// Exécution de la requête
				$resql = $db->query($sql);

				if ($resql === false) {
					echo 'SQL Error: ' . $db->lasterror();
					exit;
				}

				// Récupération des IDs des actions
				$action_ids = [];
				while ($row = $db->fetch_object($resql)) {
					$action_ids[] = $row->fk_target;
				}

				// Si aucune action n'est liée, on affiche un message
				if (empty($action_ids)) {
					$html = '
							<table border="0" cellpadding="5" cellspacing="0" width="100%">
								<tr><td><strong> ACTIONS CORRECTIVES ET PREVENTIVES (C/P) </strong></td></tr>
								<tr>Aucune action préventive ou corréctive n\'est liée à ce constat.</tr>
							</table>';
				} else {
					// Étape 2 : Requête pour récupérer les détails des actions
					$action_ids_str = implode(',', $action_ids);
					$sql_actions = "SELECT * FROM " . MAIN_DB_PREFIX . "actions_action AS a";
					$sql_actions .= " WHERE a.rowid IN ($action_ids_str)";

					$resql_actions = $db->query($sql_actions);

					if ($resql_actions === false) {
						echo 'SQL Error: ' . $db->lasterror();
						exit;
					}


						// Générer le tableau des actions
					$html = '<table border="0" cellpadding="5" cellspacing="0" width="100%">
								<tr><td colspan="5"><strong> ACTIONS CORRECTIVES ET PREVENTIVES (C/P) </strong></td></tr>
								<thead>
									<tr>
										<th style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: left;"><strong>Numéro</strong></th>
										<th style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: left;"><strong>Référence</strong></th>
										<th style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: center;"><strong>Intervenant</strong></th>
										<th style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: center;"><strong>Délai</strong></th>
										<th style="border-bottom: 0.2pt solid #000; text-align: center;"><strong>C/P</strong></th>
									</tr>
								</thead>
								<tbody>';

					while ($action = $db->fetch_object($resql_actions)) {
						// Extraction des informations de l'action
						if($action->intervenant > 0) {
							$user_static->fetch($action->intervenant);
							$firstname = htmlspecialchars($user_static->firstname);
							$lastname = htmlspecialchars($user_static->lastname);
						}
						else {
							$firstname = '';
							$lastname = '';
						}

						$action_rowid = htmlspecialchars($action->rowid);
						$action_ref = htmlspecialchars($action->ref);
						$action_date_eche = htmlspecialchars($action->date_eche);
						$action_CP = htmlspecialchars($action->CP);

						$action_CP_map = [
							1 => 'P',
							2 => 'C',
							3 => 'C/P'
						];

						$action_CP_show= isset($action_CP_map[$action->CP]) ? $action_CP_map[$action->CP] : '';

						// Affichage des informations dans une ligne du tableau
						$html .= '
						<tr>
							<td style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: left;">' . $action_rowid . '</td>
							<td style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: left;">' . $action_ref . '</td>
							<td style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: center;">' . $firstname . ' ' . $lastname . '</td>
							<td style="border-bottom: 0.2pt solid #000; border-right: 0.2pt solid #000; text-align: center;">' . $action_date_eche . '</td>
							<td style="border-bottom: 0.2pt solid #000; text-align: center;">' . $action_CP_show . '</td>
						</tr>';
					}

				$html .= '
					</tbody></table>';

				}

				
				if (intval($accordclient) === 1) {
					$html .= '
						<table border="0" cellpadding="5" cellspacing="0" width="100%">
							<tr>
								<td><strong> ACCORD CLIENT REQUIS SUR TRAITEMENT </strong></td>
								<td style="color: rgb(40, 80, 139);">' . $accordClient_display . '</td>
							</tr>
						</table>';
					$html .= '
						<table border="0" cellpadding="5" cellspacing="0" width="100%">
							<tr>
								<td><strong>Le : </strong></td>
								<td>'.$formattedDateAccordClient.'</td>
								<td><strong>Par : </strong></td>
								<td>'.$object->accordclient_par.'</td>
								<td><strong>Visa : </strong></td>	
								<td></td>	
							</tr>
							<tr>
								<td colspan="6"><strong>Commentaire : </strong></td>
							</tr>
							<tr>
								<td colspan="6" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $accordClientcomm_safe . '</td>
							</tr>
						</table>';
				} 
				else {
					$html .= '
						<table border="0" cellpadding="5" cellspacing="0" width="100%">
							<tr>
								<td><strong> ACCORD CLIENT REQUIS SUR TRAITEMENT </strong></td>
								<td style="color: rgb(40, 80, 139);">' . $accordClient_display . '</td>
							</tr>
						</table>';
					$html .= '
						<table border="0" cellpadding="5" cellspacing="0" width="100%">
							<tr>
								<td colspan="6"><strong>Commentaire : </strong></td>
							</tr>
							<tr>
								<td colspan="6" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $accordClientcomm_safe . '</td>
							</tr>
						</table>';
				}


				// Section pour les actions soldées
				$html .= '
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr><td colspan="2"><strong> SUIVI DES ACTIONS </strong></td></tr>
					<tr>
						<td><strong>Action soldées</strong></td>';
						
				// Check if all actions are sold (status = 3)
				if ($is_all_sold) {
					$html .= '<td style="color: rgb(40, 80, 139);">Oui</td>';
				} else {
					$html .= '<td style="color: rgb(40, 80, 139);">Non</td>';
				}

				$html .= '</tr></table>';

				$html .='
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong>Efficacité à court terme des actions :</strong></td>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139) ; color: rgb(40, 80, 139);">	
							<label>
								<input type="checkbox" name="efficacite" value="oui"> Oui
							</label>
							<label>
								<input type="checkbox" name="efficacite" value="non"> Non
							</label>
						</td>
					</tr>
				</table>';


				if (intval($controleclient) === 1) {
					$html .= '
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td><strong> CONTRÔLE CLIENT </strong></td>
							<td style="color: rgb(40, 80, 139);">' . $controleClient_display . '</td>
						</tr>
					</table>';
					$html .= '
						<table border="0" cellpadding="5" cellspacing="0" width="100%">
							<tr>
								<td><strong>Le : </strong></td>
								<td>'.$formattedDateControleClient.'</td>
								<td><strong>Par : </strong></td>
								<td>'.$object->controleclient_par.'</td>
								<td><strong>Visa : </strong></td>	
								<td></td>	
							</tr>
							<tr>
								<td colspan="6"><strong>Commentaire : </strong></td>
							</tr>
							<tr>
								<td colspan="6" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $controleClientcomm_safe . '</td>
							</tr>
						</table>';
				}
				else {
					$html .= '
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td><strong> CONTRÔLE CLIENT </strong></td>
							<td style="color: rgb(40, 80, 139);">' . $controleClient_display . '</td>
						</tr>
					</table>';
					$html .= '
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td colspan="6"><strong>Commentaire : </strong></td>
						</tr>
						<tr>
							<td colspan="6" style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">' . $controleClientcomm_safe . '</td>
						</tr>
					</table>';
				}


				
				$html .= '
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
						<td><strong> CLÔTURE DE LA FICHE DE CONSTAT </strong></td>
					</tr>
				</table>
				<br></br>
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong>Le : </strong></td>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">'.$formattedDateCloture.'</td>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong>Par : </strong></td>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);">'.$object->controleclient_par.'</td>
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"><strong>Visa : </strong></td>		
						<td style="border-bottom: 0.5pt solid #000; border-bottom-color: rgb(40, 80, 139);"></td>
					</tr>
				</table>';
	

				$pdf->writeHTML($html, true, false, true, false, '');
				$this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
			

				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
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

				dolChmod($file);

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





	private function renderHtmlFieldsWithHeader($pdf, $description_constat, $actionsimmediates_commentaire, $object, $outputlangs, $outputlangsbis, $max_lines = 39, $default_font_size = 12) {
		// Initialisation des compteurs
		$cpt = 0;
		$pagenb = 1;
	
		// Convertir les contenus HTML en lignes
		$linesDescription = explode('<br>', nl2br($description_constat));
		$linesAction = explode('<br>', nl2br($actionsimmediates_commentaire));
	
		// Vérification des contenus avant la fusion
		//var_dump("Description Const. :", $linesDescription);
		//var_dump("Action Imm. Comm. :", $linesAction);
	
		// Fusionner les deux tableaux de lignes
		$allLines = array_merge($linesDescription, $linesAction);
	
		// Vérification après la fusion
		//ump("All lines after merge:", $allLines);
	
		// Décomposer les paragraphes longs en lignes de taille fixe
		$lineLength = 100; // Nombre de caractères par ligne pour découper les longs paragraphes
		$formattedLines = [];
		
		foreach ($allLines as $ligne) {
			if (strlen($ligne) > $lineLength) {
				// Si une ligne est trop longue, la découper en sous-lignes
				$formattedLines = array_merge($formattedLines, str_split($ligne, $lineLength));
			} else {
				$formattedLines[] = $ligne;
			}
		}
	
		// Parcourir chaque ligne
		foreach ($formattedLines as $ligne) {
			// Vérifier si le nombre de lignes a atteint la limite maximale
			if ($cpt >= $max_lines) {
				// Remise à zéro du compteur de lignes
				$cpt = 0;
	
				// Ajouter une nouvelle page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}
				$pdf->AddPage();
	
				// // Insérer l'en-tête
				// $top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
				// $pdf->SetFont('', '', $default_font_size - 1);
				// $pdf->SetTextColor(0, 0, 0);
			}
	
			// Log de la ligne courante
			//var_dump($ligne);
	
			// Écrire la ligne dans le PDF
			$pdf->writeHTML($ligne);
			$cpt++; // Incrémenter le compteur de lignes
	
			// Log pour le débogage
			//var_dump($cpt); // Affiche le compteur de lignes
		}
	}
	



	/**
	 *  Nombre de y dans la page 
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	private function checkPageBreak(&$pdf, &$object, $showaddress, $outputlangs) {
		// Si la position Y dépasse 270, on ajoute une nouvelle page
		if ($pdf->getY() > 270) {
			$pdf->AddPage();
			// $this->_pagehead($pdf, $object, $showaddress, $outputlangs);  // Réaffiche l'en-tête à chaque nouvelle page
			$pdf->SetY(10);  // Réinitialise la position Y
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
	


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies", "constat@constat"));

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
				//var_dump($logodir);
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

		

		$pdf->SetXY($posx, $posy);
		$textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		/*if ($object->status == $object::STATUS_DRAFT) {
			$pdf->SetTextColor(128, 0, 0);
			$textref .= ' - '.$outputlangs->transnoentities("NotValidated");
		}*/
		$pdf->MultiCell($w, 4, $textref, '', 'L');

		$posy += 4;
		if (!empty($object->fk_project)) {
			$projet = New Project($this->db);
			$projet->fetch($object->fk_project);
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Affaires")." : ".(empty($projet->ref) ? '' : $projet->ref), '', 'L');
		}

		$posy += 10;  // Ajuste la hauteur pour le titre
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetTextColor(0, 40, 95);

		$title = $outputlangs->transnoentities("Fiche de constat");
		$pdf->SetXY(($this->page_largeur / 2) - ($pdf->GetStringWidth($title) / 2) - 5, $posy);

		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && is_object($outputlangsbis)) {
			$title .= ' - ' . $outputlangsbis->transnoentities("Fiche de constat");
		}

		$pdf->MultiCell($pdf->GetStringWidth($title) + 10, 8, $title, '', 'C');

		// Déplacer la ligne d'une ligne plus bas
		$posy += 8;  // Augmentez cette valeur si nécessaire pour déplacer la ligne plus bas

		// Ajouter la ligne horizontale
		$pdf->SetXY($this->marge_gauche, $posy);  // Positionner la ligne
		$pdf->WriteHTML('<hr>');
		
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
		
	}

	/**
	 *  Define Array Column Field
	 *
	 *  @param	object			$object    		common object
	 *  @param	Translate		$outputlangs    langs
	 *  @param	int			   $hidedetails		Do not show line details
	 *  @param	int			   $hidedesc		Do not show desc
	 *  @param	int			   $hideref			Do not show ref
	 *  @return	void
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
			'width' => (!getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH') ? 20 : getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH')), // in mm
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

		if (getDolGlobalInt('MAIN_GENERATE_INVOICES_WITH_PICTURE') && !empty($this->atleastonephoto)) {
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

		if (!getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
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
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
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
