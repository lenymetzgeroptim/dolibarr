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
 *  \file       core/modules/gpeccustom/doc/pdf_standard.modules.php
 *  \ingroup    gpeccustom
 *  \brief      File of class to generate document from standard template
 */

dol_include_once('/gpeccustom/core/modules/gpeccustom/modules_cvtec.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/skill.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/cvtec.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/lib/pdf.lib.php';


/**
 *	Class to manage PDF template standard_cvtec
 */
class pdf_standard_cvtec extends ModelePDFCVTec
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
		global $conf, $langs, $mysoc, $user;

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

		if ($conf->gpeccustom->dir_output.'/cvtec') {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->gpeccustom->dir_output.'/cvtec';
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->gpeccustom->dir_output.'/cvtec/'.$objectref;
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
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->multidir_output[$object->entity].'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

				 //get data
				//  $cvtec=$object->getUserBackground();
				// $cvtec = pdfBuildThirdpartyNameCV($object, $outputlangs, "all");
				 

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
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
						'Reason' => 'CVTEC',
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

				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);
				

				// $tab_top = 90 + $top_shift;
				// $tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);
				// $tab_height = 130 - $top_shift;
				// $tab_height_newpage = 150;
				// if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
				// 	$tab_height_newpage -= $top_shift;
				// }
				$tab_top = 30;
				
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
								// $this->_pagehead($pdf, $object, 0, $outputlangs);
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
							// $this->_pagehead($pdf, $object, 0, $outputlangs);
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
								// $this->_pagehead($pdf, $object, 0, $outputlangs);
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
				//  $this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);
				
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
							// var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
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
		
					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == $pageposbeforeprintlines) {
							// $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis, $object);
						} else {
							// $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code, $outputlangsbis, $object);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							// $this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
					}

					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == $pageposafter) {
							// $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis, $object);
						} else {
							// $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code, $outputlangsbis, $object);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							// $this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}

				// Show square
				if ($pagenb == $pageposbeforeprintlines) {
					// $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, $hidetop, 0, $object->multicurrency_code, $outputlangsbis, $object);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					// $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code, $outputlangsbis, $object);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

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

				$pdf->SetXY($this->marge_gauche, $tab_top + 2);
					// $pdf->SetFont('', '', $default_font_size - 1);
					// $pdf->MultiCell(0, 6, ''); // Set interline to 3
					// $pdf->SetTextColor(0, 0, 0);
					$html = '';
					$html .= $this->_blocJobSkillHtml();
					$pdf->SetXY($this->marge_gauche, $tab_top + 2);
					$html .= $this->_blocFieldActivitiesHtml($object);
					
					
					$totalPageCount = $pdf->getNumPages();
					 $totalCaraLines = strlen($html) / $totalPageCount;

					 $linesarr = preg_split('/\n|\r/',$html);
					 $numnewlines = count($linesarr); 
					 $totalLines = $numnewlines / $totalPageCount;
					 $arr2 = str_split($html, 30590);
					 $i = 0;
					
					 foreach($arr2 as $html2) {
						$i++;
						if($i == $totalCaraLines) {
							$this->_pagefoot($pdf, $object, $outputlangs);
							if (method_exists($pdf, 'AliasNbPages')) {
								$pdf->AliasNbPages();
							}
							$pdf->AddPage();
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							$pagenb++;
							// $top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
							$pdf->SetFont('', '', $default_font_size - 1);
							$pdf->SetTextColor(0, 0, 0);
						}
						
						
					 }
					 $pdf->writeHTML($html);
				//Pagefoot
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
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null, $object)
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
			// $titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			// if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			// 	$titre .= ' - '.$outputlangsbis->transnoentities("AmountInCurrency", $outputlangsbis->transnoentitiesnoconv("Currency".$currency));
			// }
			// $titre .= "test";
			$userData = $object->getUserCVData($arr_job, $arr_skill, $arr_level);
			foreach($userData as $cv) {
				if($object->fk_user == $cv->userid) {
					$arrids[] = $cv;
				}
			}
	
			global $db;
			foreach($arrids as $ids) {
				$arr[$ids->userid][$ids->job_label][$ids->skill_id] = $ids->skill_label.'_'.$ids->eval.'_'.$ids->required_eval;
			}
			
			foreach($arr as $userids => $data) {
				foreach($data as $key => $vals) { 
					$arrprofil[$userids] = array('job' => implode("\n", array_keys($data)), 'skill' => implode("\n", $vals));
				}
			}

			
			// // var_dump(pdfBuildUserSkillsCV($arrprofil, $outputlangs));
			$titre .=  pdfBuildUserSkillsCV($arrprofil, $outputlangs);
			$pdf->SetXY($this->marge_gauche, $tab_top + 2);
			$pdf->SetFont('', 'B', $default_font_size - 3);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->tabTitleHeight, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
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

	/**
	 * 
	 */
	public function _blocJobSkillHtml()
	{
		$html = '<div class="table_component" role="region" tabindex="0" style="overflow: auto;width: 100%;line-height: 120%;">'.
							'<table style="border-right-color:#ffffff;border-left-color:#ffffff;height: 100%;width: 100%;table-layout: fixed;border-collapse: collapse;border-spacing: 0px;text-align: center;">'.
								// '<caption style="caption-side: top;text-align: left;">Table 1</caption>'.
								'<thead>
									<tr>
										<th colspan="3" style="height: 22px;font-size: 1.5em;text-align: left;border-right-color:#ffffff;border-left-color:#ffffff;border: 1px solid #dededf;background-color: #ffffff;color: #0a1464;padding:5px;"><strong>DATES CLÉS</strong></th>
									</tr>'.
								'</thead>
								<tbody>
									<tr>
										<td style="border-right-color:#dededf;border-left-color:#ffffff;border-top-color:#ffffff;border: 1px solid #dededf;background-color: #eceff1;color: #000000;padding: 5px;">Année naissance</td>
										<td style="border-right-color:#dededf;border-left-color:#ffffff;border-top-color:#ffffff;border: 1px solid #dededf;background-color: #eceff1;color: #000000;padding: 5px;">Expérience Professionnelle depuis</td>
										<td style="border-right-color:#ffffff;border-left-color:#ffffff;border-top-color:#ffffff;border: 1px solid #dededf;background-color: #eceff1;color: #000000;padding: 5px;">Expérience en milieu nucléaire depuis</td>
									</tr>
									<tr>
										<td style="border-right-color:#dededf;background-color: #ffffff;color: #000000;padding: 5px;">12</td>
										<td style="border-right-color:#dededf;background-color: #ffffff;color: #000000;padding: 5px;"></td>
										<td style="background-color: #ffffff;color: #000000;padding: 5px;"></td>
										
									</tr>
								</tbody>
							</table>
						</div>
				';
		
				return $html;
		
	}

	/**
	 * 
	 */
	public function _blocFieldActivitiesHtml($object)
	{
		global $db;
		$userData = $object->getUserCVData($arr_job, $arr_skill, $arr_level);
		$i =0;
			foreach($userData as $cv) {
				if($object->fk_user == $cv->userid) {
					$arrids[] = $cv;
				}
			}
	
			
			foreach($arrids as $ids) {
				$ids->eval >= $ids->required_eval ? $color = '#c3e6cb;' : $color = '#bd4147;';
				$arr[$ids->userid][$ids->job_label][$ids->skill_id] = array('skill' => $ids->skill_label, 'eval' => $ids->eval, 'requis' => $ids->required_eval, 'color' => $color);
			}

			
		
			$html .= '<span colspan="3" style="height: 22px;font-size: 1.5em;text-align: left;border-right-color:#ffffff;border-left-color:#ffffff;border: 1px solid #dededf;background-color: #ffffff;color: #0a1464;padding:5px;"><strong>COMPÉTENCES LIÉES A LA MISSION</strong></span>
					 <br>
			';

			foreach($arr as $userids => $data) {
				
				foreach($data as $key => $vals) { 
					
					
					// $arrprofil[$userids] = array('job' => implode("\n", array_keys($data)), 'skill' => implode("\n", $vals));
					$html .= '<div class="table_component" role="region" tabindex="0" style="overflow: auto;width: 100%;padding-top:15px;">
								<table class="noborder centpercent">
									<thead>
										<tr>
											<th colspan="3" style="height: 28px;font-size: 1.5em;text-align: left;
											border-style: inset;
											background-color: #ffffff;color: #0a1464;padding:5px;"><strong>'.$key.'</strong></th>
										</tr>
									</thead>
									<tbody>
										<tr class="liste_titre">
											<th style="width:50%;text-align:center;border-right:1px solid #dededf;border-left-color:#ffffff;border-top-color:#ffffff;background-color: #ffffff;color: #000000;padding: 5px;" class="liste_titre"></th>
											<th style="width:20%;text-align:center; border-right-color:#dededf;border-left-color:#ffffff;border-top-color:#ffffff;border: 1px solid #dededf;background-color: #eceff1;color: #000000;padding: 5px;" class="liste_titre">Niveau de l\'employée pour cette compétence</th>
											<th style="width:20%;text-align:center;border-right-color:#dededf;border-left-color:#ffffff;border-top-color:#ffffff;border: 1px solid #dededf;background-color: #eceff1;color: #000000;padding: 5px;" class="liste_titre">Niveau requis pour cet emploi</th>
											<th style="width:10%;text-align:center;border-right-color:#dededf;border-left-color:#ffffff;border-top-color:#ffffff;border: 1px solid #dededf;background-color: #eceff1;color: #000000;padding: 5px;" class="liste_titre">Résultat</th>
										</tr>
					';
						foreach($vals as $val) { 
							$skills[] = $val['skill'];
					sizeof($skills) > 30 ? $mod = sizeof($skills)%68 == 0 : false;
					sizeof($skills) > 32 ? $modheader = sizeof($skills)%72 == 0 : false;
					if($modheader || sizeof($skills) == 35) {
						$html .= '<br>test header position<br>';
					}
					if($mod || sizeof($skills) == 30) {
						$html .= '<br>test Footer position<br>';
					}
							$html .= '<tr>
										<td>
											<ul>
											<li>' . $val['skill'] . '</li>
											</ul>
										</td>
										<td align="center">
											<span title="'.$val['skill'].'" class="radio_js_bloc_number TNote_1">'.$val['eval'].'</span>
										</td> 
										<td align="center">
										<span title="'.$val['skill'].'" class="radio_js_bloc_number TNote_1">'.$val['requis'].'</span>
										</td>
										<td align="center">
										<div class="square">
										 	<span style="align-items: center;display: flex;justify-content: space-between;line-height: 0.8;text-align: center;width:15px;
											background-color: '.$val['color'].' box-sizing: border-box;border: 5px solid #3097D1;">&nbsp;&nbsp;&nbsp;</span>
										  </div>
										</td>
									</tr>
							';
						}
					$html .=      '</tbody>
								</table>
							</div>
					';
				}
			}
			
			// <span style="align-items: center;display: flex;justify-content: space-between;line-height: 0.8;text-align: center;width:15px;
			// background-color: '.$val['color'].' box-sizing: border-box;border: 5px solid #3097D1;">&nbsp;&nbsp;&nbsp;</span>
			return $html;
		
	}

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
		$employee = new User($this->db);
		$employee->fetch($object->fk_user);
		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == $object::STATUS_DRAFT && getDolGlobalString('GPECCUSTOM_DRAFT_WATERMARK')) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', dol_escape_htmltag(getDolGlobalString('GPECCUSTOM_DRAFT_WATERMARK')));
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) {
					$logodir = $conf->mycompany->multidir_output[$object->entity];
				}
				if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
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

		$pdf->SetFont('', 'B', $default_font_size + 4);
		$pdf->SetXY($posx, $posy - 5);
		$pdf->SetTextColor(10, 10, 80);
		// $title = $outputlangs->transnoentities("CURRICULUM VITAE");
		$title .= pdfBuildThirdpartyNameCV($object, $outputlangs, "last");
		if (getDolGlobalInt('MAIN_ODT_AS_PDF') && is_object($outputlangsbis)) {
			// $title .= ' - ';
			$title .= pdfBuildThirdpartyNameCV($object, $outputlangs, "last");
		}
		$pdf->MultiCell($w, 5, $title, '', 'C');

		$pdf->SetFont('', 'B', $default_font_size + 2);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		// $textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		// $textref = $employee->photo;
		$textref = " ";
		// if ($object->statut == $object::STATUS_DRAFT) {
		// 	$pdf->SetTextColor(128, 0, 0);
		// 	$textref .= ' - '.$outputlangs->transnoentities("NotValidated");
		// }
		$pdf->MultiCell($w, 4, $textref, '', 'R');

		$posy += 1;
		$pdf->SetFont('', '', $default_font_size - 2);

		// if ($object->ref_client) {
		// 	$posy += 4;
		// 	$pdf->SetXY($posx, $posy);
		// 	$pdf->SetTextColor(0, 0, 60);
		// 	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefCustomer")." : ".$outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		// }

		// if (getDolGlobalInt('PDF_SHOW_PROJECT_TITLE')) {
		// 	$object->fetch_projet();
		// 	if (!empty($object->project->ref)) {
		// 		$posy += 3;
		// 		$pdf->SetXY($posx, $posy);
		// 		$pdf->SetTextColor(0, 0, 60);
		// 		$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Project")." : ".(empty($object->project->title) ? '' : $object->projet->title), '', 'R');
		// 	}
		// }

		// if (getDolGlobalInt('PDF_SHOW_PROJECT')) {
		// 	$object->fetch_projet();
		// 	if (!empty($object->project->ref)) {
		// 		$outputlangs->load("projects");
		// 		$posy += 3;
		// 		$pdf->SetXY($posx, $posy);
		// 		$pdf->SetTextColor(0, 0, 60);
		// 		$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefProject")." : ".(empty($object->project->ref) ? '' : $object->project->ref), '', 'R');
		// 	}
		// }

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$now = dol_now();
		$title = $outputlangs->transnoentities("Date");
		if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			$title .= ' - '.$outputlangsbis->transnoentities("Date");
		}
		$pdf->MultiCell($w, 3, $title." : ".dol_print_date($now, "day", false, $outputlangs), '', 'R');

		// if ($object->thirdparty->code_client) {
		// 	$posy += 3;
		// 	$pdf->SetXY($posx, $posy);
		// 	$pdf->SetTextColor(0, 0, 60);
		// 	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		// }

		// Get contact
		// if (getDolGlobalInt('DOC_SHOW_FIRST_SALES_REP')) {
		// 	$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
		// 	if (count($arrayidcontact) > 0) {
		// 		$usertmp = new User($this->db);
		// 		$usertmp->fetch($arrayidcontact[0]);
		// 		$posy += 4;
		// 		$pdf->SetXY($posx, $posy);
		// 		$pdf->SetTextColor(0, 0, 60);
		// 		$pdf->MultiCell($w, 3, $langs->transnoentities("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
		// 	}
		// }

		$posy += 1;

		$top_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY()) {
			$top_shift = $pdf->getY() - $current_y;
		}

		// if ($showaddress) {
		// 	// Sender properties
		// 	// $carac_emetteur = pdf_build_address_cv($outputlangs, $object, $object, '', 0, 'target', $object);

		// 	// Show sender
		// 	// $posy = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
		// 	// $posy += $top_shift;
		// 	// $posx = $this->marge_gauche;
		// 	// if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) {
		// 	// 	$posx = $this->page_largeur - $this->marge_droite - 80;
		// 	// }

		// 	// $hautcadre = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
		// 	// $widthrecbox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;


		// 	// Show sender frame
		// 	// $pdf->SetTextColor(0, 0, 0);
		// 	// $pdf->SetFont('', '', $default_font_size - 2);
		// 	// $pdf->SetXY($posx, $posy - 5);
		// 	// titre emetteur
		// 	// $pdf->MultiCell(66, 5, $outputlangs->transnoentities("")."", 0, 'L');
		// 	// $pdf->SetXY($posx, $posy);
		// 	// $pdf->SetFillColor(230, 230, 230);
		// 	// $pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
		// 	// $pdf->SetTextColor(0, 0, 60);

		// 	// Show sender name
		// 	// $pdf->SetXY($posx + 2, $posy + 3);
		// 	// $pdf->SetFont('', 'B', $default_font_size);
			
		// 	// $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($employee->firstname.' '.$employee->lastname), 0, 'L');
		// 	// $posy = $pdf->getY();

		// 	// Show sender information
		// 	// $pdf->SetXY($posx + 2, $posy);
		// 	// $pdf->SetFont('', '', $default_font_size - 1);
		// 	// $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

		// 	// If BILLING contact defined on invoice, we use it
		// 	$usecontact = false;
		// 	// $arrayidcontact = $object->getIdContact('external', 'BILLING');
		// 	// if (count($arrayidcontact) > 0) {
		// 	// 	$usecontact = true;
		// 	// 	$result = $object->fetch_contact($arrayidcontact[0]);
		// 	// }
		// 	// Recipient name
		// 	// if ($object->contact->socid != $object->thirdparty->id && getDolGlobalInt('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
		// 	// 	$thirdparty = $object->contact;
		// 	// } else {
		// 	// 	$thirdparty = $object->thirdparty;
		// 	// }
			
		// 	// if (is_object($object)) {
		// 	// 	$carac_client_name = pdfBuildThirdpartyNameCV($object, $outputlangs, "all");
		// 	// }
		
		// 	// $carac_client = pdf_build_address_cv($outputlangs, $object, $object, ($usecontact ? $object->contact : ''), $usecontact, 'target', $object);
		// 	$carac_client = '';
		// 	// Show recipient
		// 	$widthrecbox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 100;
		// 	if ($this->page_largeur < 210) {
		// 		$widthrecbox = 84; // To work with US executive format
		// 	}
		// 	$posy = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
		// 	$posy += $top_shift;
		// 	$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
		// 	if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) {
		// 		$posx = $this->marge_gauche;
		// 	}

		// 	// Show recipient frame
		// 	// $pdf->SetTextColor(0, 0, 0);
		// 	// $pdf->SetTextColor(0, 0, 60);
		// 	// $pdf->SetFont('', '', $default_font_size);
		// 	// $pdf->SetXY($posx + 2, $posy - 5);
		// 	// $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Parcours professionnel"), 0, 'L');
		// 	// $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

		// 	// Show recipient name
		// 	// $pdf->SetXY($posx + 2, $posy + 3);
		// 	// $pdf->SetFont('', 'B', $default_font_size);
		// 	// $pdf->MultiCell($widthrecbox, 5, $carac_client_name, 0, 'L');
			

		// 	// $posy = $pdf->getY();

		// 	// // Show recipient information
		// 	// $pdf->SetFont('', '', $default_font_size - 1);
		// 	// $pdf->SetXY($posx + 2, $posy);
		// 	// $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		// }

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
		$showdetails = !getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 0 : getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');
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
		// $this->cols['d'] = array( 'textkey' => pdfBuildThirdpartyNameCV($object, $outputlangs, "last"));
		$rank = 0; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Compétence', // use lang key is usefull in somme case with module
				'align' => 'C',
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
		// $rank = $rank + 10;
		// $this->cols['photo'] = array(
		// 	'rank' => $rank,
		// 	'width' => (!getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH') ? 20 : getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH')), // in mm
		// 	'status' => false,
		// 	'title' => array(
		// 		'textkey' => 'Photo',
		// 		'label' => ' '
		// 	),
		// 	'content' => array(
		// 		'padding' => array(0, 0, 0, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		// 	),
		// 	'border-left' => false, // remove left line separator
		// );

		// if (getDolGlobalInt('MAIN_GENERATE_INVOICES_WITH_PICTURE') && !empty($this->atleastonephoto)) {
		// 	$this->cols['photo']['status'] = true;
		// }


		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => 80, // in mm
			'title' => array(
				'textkey' => 'Description'
			),
			'border-left' => true, // add left line separator
		);

		if (!getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
			$this->cols['vat']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['subprice'] = array(
			'rank' => $rank,
			'width' => 30, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Niveau de l\'employée pour cette compétence'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => 30, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Niveau requis pour cet emploi'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['progress'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Résultat'
			),
			'border-left' => true, // add left line separator
		);

		if ($this->situationinvoice) {
			$this->cols['progress']['status'] = true;
		}

		// $rank = $rank + 10;
		// $this->cols['unit'] = array(
		// 	'rank' => $rank,
		// 	'width' => 11, // in mm
		// 	'status' => false,
		// 	'title' => array(
		// 		'textkey' => 'Unit'
		// 	),
		// 	'border-left' => true, // add left line separator
		// );
		// if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		// 	$this->cols['unit']['status'] = true;
		// }

		// $rank = $rank + 10;
		// $this->cols['discount'] = array(
		// 	'rank' => $rank,
		// 	'width' => 13, // in mm
		// 	'status' => false,
		// 	'title' => array(
		// 		'textkey' => 'ReductionShort'
		// 	),
		// 	'border-left' => true, // add left line separator
		// );
		// if ($this->atleastonediscount) {
		// 	$this->cols['discount']['status'] = true;
		// }

		// $rank = $rank + 1000; // add a big offset to be sure is the last col because default extrafield rank is 100
		// $this->cols['totalexcltax'] = array(
		// 	'rank' => $rank,
		// 	'width' => 26, // in mm
		// 	'status' => true,
		// 	'title' => array(
		// 		'textkey' => 'TotalHT'
		// 	),
		// 	'border-left' => true, // add left line separator
		// );

		// Add extrafields cols
		// if (!empty($object->lines)) {
		// 	$line = reset($object->lines);
		// 	$this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
		// }

		// $parameters = array(
		// 	'object' => $object,
		// 	'outputlangs' => $outputlangs,
		// 	'hidedetails' => $hidedetails,
		// 	'hidedesc' => $hidedesc,
		// 	'hideref' => $hideref
		// );

		// $reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this); // Note that $object may have been modified by hook
		// if ($reshook < 0) {
		// 	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		// } elseif (empty($reshook)) {
		// 	$this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
		// } else {
		// 	$this->cols = $hookmanager->resArray;
		// }
	}
}
