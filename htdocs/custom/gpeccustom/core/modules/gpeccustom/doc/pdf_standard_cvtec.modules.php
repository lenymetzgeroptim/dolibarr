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
require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/job.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/skill.class.php';
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
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "cvtec"));

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
				$pdf = pdf_getInstanceCustom($object, $this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
			
				$pdf->SetAutoPageBreak(1, 10);

				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					// $pdf->SetTopMargin(30); // Définir une marge supérieure suffisante
					// $pdf->SetMargins(PDF_MARGIN_LEFT, 30, PDF_MARGIN_RIGHT); // Peut aussi définir pour les autres marges
					// $pdf->SetHeaderMargin(10); // Peut être ajusté selon la taille de l'en-tête
					// $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
					// $pdf->SetTopMargin(20); // Assurez-vous que cette marge est suffisante
					// $pdf->SetMargins(20, 20, PDF_MARGIN_RIGHT); // Marge de gauche augmentée pour la bande
					// $pdf->SetHeaderMargin(10);
					// $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
			
					$pdf->setPrintHeader(true);
					$pdf->setPrintFooter(true);
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
				$pdf->SetSubject($outputlangs->transnoentities("PdfTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

									
				// Configuration de la police
				$pdf->SetFont('helvetica', '', 12);
				$pdf->startPageGroup();
			
			
					$inputArray = $object->getLastUserJob($object->fk_user);
					// $data = $object->getLastUserJob($object->fk_user);
					$colors = $this->generateColors($inputArray);
					$allJobs = $this->transformToChartData($inputArray);
					
					$chartData = $this->checkMultipleJobOverlaps($allJobs);

					// $this->generateBarChart($pdf, $data, $colors, $originX = 50, $originY = 150, $barWidth = 15, $spaceBetweenBars = 10, $barMaxHeight = 50, $labelRotationAngle = 45);
					// // // New page
					// // $pdf->AddPage();
									
					// $this->generatePieChart($pdf, $data, $colors, $centerX = 105, $centerY = 100, $radius = 50);
					// // New page
					$pdf->setCustomMargins(30, 20, 20); // Adjust as needed for the section title
					$pdf->SetDrawColor(128, 128, 128);
					$pdf->setLeftBandText('PROFIL TECHNIQUE');
					$pdf->AddPage();
					// Set chart position and size
					$chartX = 30;
					$chartY = 30;
					$chartWidth = 140;
					// $chartHeight = 170;
					$competenciesObj = $object->getAvrSkill($object->fk_user);
					$competencies = $this->convertCompetenciesToArray($competenciesObj);
					$circlesData = [
						['date' => 1980, 'text' => 'Année Naissance'],
						['date' => 1995, 'text' => 'Expérience Depuis'],
						['date' => 2020, 'text' => 'En milieu nucléaire Depuis']
					];
					
					// Generate the competency chart
					// $this->AddSectionTitle('Compétences', $pdf);
					// $this->drawTableWithChart($pdf, $chartData, $colors, $text, 'bar');
					$this->drawProfileContent($pdf, $circlesData, $competencies, $colors, $chartText);
					// Add a new page
					$pdf->setCustomMargins(30, 20, 20); // Adjust as needed for the section title
					$pdf->setLeftBandText('COMPETENCES');
					$pdf->AddPage();
					$this->AddSectionTitle('Compétences', $pdf);
					$this->generateCompetencyChartDetailed($pdf, $competencies, $chartX, $chartY, $chartWidth, $chartHeight);
					
					// $pdf->AddPage();
				
				$pdf->setCustomMargins(30, 20, 20); // Adjust as needed for the section title
				// $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
				$pdf->setLeftBandText('EXPERIENCE');
				// $pdf->SetAutoPageBreak(TRUE, 15);
				// New page
				$pdf->AddPage();

				// Table simulation to know the height of the title line
				// $pdf->startTransaction();
				// $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				// $pdf->rollbackTransaction(true);
				// $nexY = $tab_top + $this->tabTitleHeight;

					// $html .= $this->_blocTableExperience($object, $pdf,$outputlangs, $outputlangsbis, );
		
					// Add a section title and content
					$this->AddSectionTitle('Expérience Professionnelle', $pdf);
					$pdf->Ln(5); // Line break
					// Set colors and fonts for the job title
					$pdf->SetFont('helvetica', 'B', 16);
					$pdf->SetTextColor(34, 34, 34); // Dark Gray
					// $pdf->Cell(0, 10, $jobTitle, 0, 1, 'L', 0, '', 0, false, 'T', 'M');

					// Set colors and fonts for the date
					$pdf->SetFont('helvetica', 'I', 10);
					$pdf->SetTextColor(100, 100, 100); // Light Gray
					// $pdf->Cell(0, 5, $date, 0, 1, 'L', 0, '', 0, false, 'T', 'M');

					// Set colors and fonts for the description
					$pdf->SetFont('helvetica', '', 14);
					$pdf->SetTextColor(50, 50, 50); // Medium Gray
					// Add some space after each job experience
					//  $pdf->Ln(1);
					// Définit les marges
					$footerHeight = 0; 
					$pageHeight = $pdf->getPageHeight();
					$availableHeight = $pageHeight - $footerHeight;
					$style = '<style>
					.cv-title {
						font-size: 11px;
						text-decoration: underline;
						text-decoration-thickness: 6px;
						font-weight: bold;
						color:#847b72;
					}
					.cv-section-title {
						font-size: 16px;
						font-weight: bold;
						color: #333;
					}

					h3 {
						font-size: 14px;
						font-weight: bold;
					}
					.period {
						font-size: 11px;
						color: #555;
					}
					.description {
						font-size: 11px;
						color: #333;
						text-align:justify;
						margin-left: 200px!important;
					}
					</style>';
					if(!empty($object->getLastUserJob($object->fk_user))) {
						foreach($object->getLastUserJob($object->fk_user) as $key => $values) {
							foreach($values as $date_start => $vals) {
								foreach($vals as $date_end => $value) {
									$end_date = $date_end != '' ? ' à '. dol_print_date($date_end, '%B %Y') : ' à aujourd\'hui ';
									$value = explode('_', $value);
									$jobTitle = $value[0];
									$description = $value[1];
									$date = dol_print_date($date_start, '%B %Y').''.$end_date;
									// $description_with_dashes = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA1-\xAC\xAE-\xFF\x{0100}-\x{017F}\x{2018}\x{2019}]+/u', '', nl2br($description));
									// $description_with_dashes = str_replace('<br />', '<br />-', $description_with_dashes);
									$description_with_dashes = $this->addDashesIfNotExists($description);
									//  $pdf->MultiCell(0, 5, $description, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
									$html = $this->generateJobHTML($jobTitle, $date, $description);
									// Calculer la hauteur du bloc HTML
									$blockHeight = $pdf->getStringHeight(0, strip_tags($html)); // Estimation simple basée sur le texte brut
									// Espace restant sur la page
									$remainingSpace = $availableHeight - $pdf->GetY();
								
									// Si l'espace est insuffisant, ajouter une nouvelle page
									if ($blockHeight > $remainingSpace) {
										$pdf->AddPage();

									}
									$html .= $style;
									// $pdf->MultiCell(0, 5, $html, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
									// Insérer le contenu HTML
									$pdf->writeHTML($html, true, false, true, false, '');
									$pdf->Ln(5); // Line break
										
								}
										
							}
						}		
					}								
					
					// $pdf->SetXY($this->marge_gauche, $tab_top + 2);
					// $pdf->writeHTML($html);
				
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
	
	/**
	 * 
	 */
	public function drawProfileContent($pdf, $circles, $competencies, $colors, $chartText) {
		// Define margins and space between blocks
		$margin = 22; 
		$spaceBetweenBlocks = 4; 
	
		// // Set initial margins
		// $pdf->SetMargins($margin, $margin, $margin);

		// Set initial margins for the first page
		$topMarginFirstPage = 33; 
		$pdf->SetMargins($margin, $topMarginFirstPage, $margin);
	
		// Draw concentric circles
		$startX = $margin + 20; 
		$startY = $topMarginFirstPage + 20; 
		$this->drawConcentricCircles($pdf, $startX, $startY, $circles);
	
		// Move Y position after circles
		$currentY = $pdf->GetY() + $spaceBetweenBlocks;
		if ($currentY + 140 > $pdf->getPageHeight() - $pdf->getFooterMargin()) {
			$pdf->AddPage();
			$currentY = $margin; // Reset Y position for new page
		}

		// Draw the chart
		$chartWidth = $pdf->getPageWidth() - $margin * 2; 
		
		$chartHeight = 10; 
		// Draw skills chart (list)
		$this->generateCompetencyChart($pdf, $competencies, 5, $currentY + 2, $chartWidth + 17, $chartHeight, 1);
		
		// la position Y après le graphique précédent
		$currentY = $pdf->GetY() + $spaceBetweenBlocks;
		
		// si la nouvelle position dépasse la page
		if ($currentY + 90 > $pdf->getPageHeight() - $pdf->getFooterMargin()) {
			$pdf->AddPage();
			$currentY = $margin; // Réinitialiser la position Y pour la nouvelle page
		}

		// saut de ligne de 5 mm
		$currentY += 12;

		// dimensions du graphique
		$graphWidth = $pdf->getPageWidth() - $margin * 2; 
		$graphHeight = 100; 

		// skills bar graph
		$this->generateSkillBarGraph($pdf, $competencies, $colors, $margin, $currentY, $graphWidth, $graphHeight);
	}

	/**
	* Draws concentric circles with the inner circle's peripheral marked based on the number of years.
	* Each circle can have its own date and associated text.
	* 
	* @param TCPDF $pdf TCPDF instance.
	* @param float $x Center X coordinate for the first circle.
	* @param float $y Center Y coordinate for the circles.
	* @param array $circles Array of circle data, where each element is an associative array with 'date' and 'text'.
	*/
	public function drawConcentricCircles($pdf, $x, $y, $circles) {
		// Define the properties of the circles (Diameter and thickness)
		$outerDiameter = 22;
		$outerThickness = 0.5; 
		$innerDiameter = 14; 
		$innerThickness = 3; 
		$totalYears = 40; 

		// Draw the background band
		$pdf->SetFillColor(248, 250, 249); 
		$pdf->Rect($x - 20, $y - $outerRadius - 22, 180, $outerDiameter + 15, 'F');
		
		// Loop through the circles data and draw each circle
		$currentX = $x;
		foreach ($circles as $circle) {
			$date = $circle['date'];
			$text = $circle['text'];

			// Calculate number of years based on the provided date.
			$now = dol_now();
			$numberOfYears = dol_print_date($now, '%Y') - $date;

			// Convert diameters to radii
			$outerRadius = $outerDiameter / 2;
			$innerRadius = $innerDiameter / 2;

			// Calculate the percentage of the circle to be marked
			$percentageMarked = min($numberOfYears / $totalYears, 1); 

			// Calculate the number of degrees to draw based on the percentage
			$totalDegrees = 360;
			$markedDegrees = $percentageMarked * $totalDegrees;


			// Draw the outer circle
			$pdf->SetDrawColor(207, 208, 203);
			$pdf->SetLineWidth($outerThickness);
			$pdf->Ellipse($currentX, $y, $outerRadius, $outerRadius);

			// Draw the inner circle 
			$pdf->SetDrawColor(207, 222, 241);
			$pdf->SetLineWidth($innerThickness);
			$pdf->Ellipse($currentX, $y, $innerRadius, $innerRadius);

			// Draw the marked peripheral of the inner circle
			$step = 1; 
			$currentAngle = 0;
			// Color for the marked part 
			$markedColor = [207, 222, 241]; 
			// Color for the unmarked part (light gray for example)
			$unmarkedColor = [200, 200, 200]; 

			// Draw the marked segment of the inner circle
			$pdf->SetLineWidth($innerThickness);
			$pdf->SetDrawColor($markedColor[0], $markedColor[1], $markedColor[2]);

			// to draw arc and mark colored arc
			while ($currentAngle < $markedDegrees) {
				// Calculate start and end points of the segment
				$startX = $currentX + $innerRadius * cos(deg2rad($currentAngle));
				$startY = $y + $innerRadius * sin(deg2rad($currentAngle));
				$endX = $currentX + $innerRadius * cos(deg2rad($currentAngle + $step));
				$endY = $y + $innerRadius * sin(deg2rad($currentAngle + $step));

				// Draw the segment
				$pdf->Line($startX, $startY, $endX, $endY);

				$currentAngle += $step;
			}

			// Draw the remaining unmarked segment
			$pdf->SetDrawColor($unmarkedColor[0], $unmarkedColor[1], $unmarkedColor[2]);

			while ($currentAngle < $totalDegrees) {
				// Calculate start and end points of the segment
				$startX = $currentX + $innerRadius * cos(deg2rad($currentAngle));
				$startY = $y + $innerRadius * sin(deg2rad($currentAngle));
				$endX = $currentX + $innerRadius * cos(deg2rad($currentAngle + $step));
				$endY = $y + $innerRadius * sin(deg2rad($currentAngle + $step));

				// Draw the segment
				$pdf->Line($startX, $startY, $endX, $endY);

				$currentAngle += $step;
			}

			// Draw the custom text above the circle
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->SetTextColor(132, 123, 114); 

			// Calculate text position (centered above the circle)
			$textWidth = $pdf->GetStringWidth($text);
			$textX = $currentX - ($textWidth / 2);
			$textY = $y - ($innerRadius + 5); 

			$pdf->SetXY($textX, $textY - 6);
			$pdf->Cell($textWidth, -15, $text, 0, 0, 'C');
			$pdf->Ln();

			// Draw the year text below the circle
			$pdf->SetFont('helvetica', 'B', 11);
			$pdf->SetTextColor(41, 79, 141); 
			$yearText = $date;
			$yearTextWidth = $pdf->GetStringWidth($yearText);
			$yearTextX = $currentX - ($yearTextWidth / 2);

			$pdf->SetXY($yearTextX, $y + $innerRadius - 10);
			$pdf->Cell($yearTextWidth, 0, $yearText, 0, 0, 'C');

			// Move X coordinate for the next circle and adjust the gap between circle
			$currentX += 60; 
		}
	}

	/**
	 * Generates a horizontal bar chart with competencies listed on the left and bars on the right.
	 * 
	 * @param TCPDF $pdf TCPDF instance.
	 * @param array $competencies Data for the chart (grouped by domain with skills and levels).
	 * @param array $colors Colors for the chart.
	 * @param int $x X position for the chart.
	 * @param int $y Y position for the chart.
	 * @param int $width Width of the chart.
	 * @param int $height Height of the chart.
	 */
	public function generateSkillBarGraph($pdf, $competencies, $colors, $x, $y, $width, $height) {
		// Define chart parameters
		$barHeight = 2; 
		$barSpacing = 1; 
		$maxBarWidth = $width * 0.45; 
		$fontSize = 7; 
		$backgroundColor = [227, 243, 251]; 

		// Flatten competencies into a single array with domain and skill info
		$flatSkills = [];
		foreach ($competencies as $domain => $skills) {
			foreach ($skills as $skill) {
				$flatSkills[] = [
					'name' => $skill['name'],
					'level' => $skill['level'],
					'domain' => $domain
				];
			}
		}

		// Find maximum level to scale bars
		$maxLevel = max(array_column($flatSkills, 'level'));

		$currentX = $x; 
		$currentY = $y; 

		$pdf->SetFont('helvetica', '', $fontSize);

		// Calculate available height considering the footer margin
		$availableHeight = $pdf->getPageHeight() - $pdf->getFooterMargin() - $y - 15;

		// Draw the background for the entire chart area, limited to the available height
		// $chartHeight = min($height, $availableHeight);
		// $pdf->SetFillColor($backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
		// $pdf->Rect($x, $y, $width + 80, $chartHeight, 'F'); 

		foreach ($flatSkills as $skill) {
			$competencyName = $skill['name'];
			$level = $skill['level'];

			// Determine the width of the bar based on the level
			$barWidth = ($level / $maxLevel) * $maxBarWidth;

			// Set color for the background of the bar
			$pdf->SetFillColor(207,208,203);
			$pdf->Rect($currentX + 100, $currentY, $maxBarWidth, $barHeight, 'F'); 

			// Set color for the bar
			$domainColor = isset($colors[$skill['domain']]) ? $colors[$skill['domain']] : [100,140,188]; 
			$pdf->SetFillColor($domainColor[0], $domainColor[1], $domainColor[2]);

			// Draw the competency name
			$pdf->SetFont('helvetica', 'B', $fontSize);
			$pdf->SetTextColor(25, 34, 73);
			$pdf->SetXY($currentX, $currentY);
			$pdf->Cell(10, $barHeight, $competencyName, 0, 0, 'L');

			// Draw the colored bar on top of the background
			$pdf->SetXY($currentX + 100, $currentY); // 100 units padding between text and bar
			$pdf->Rect($currentX + 100, $currentY, $barWidth, $barHeight, 'F');

			// Move to the next bar position
			$currentY += $barHeight + $barSpacing;

			// Check if we need to move to a new page if the currentY exceeds the available height
			if ($currentY + $barHeight > $y + $height) {
				$pdf->AddPage();
				$currentY = $y; 
			}
		}
	}

	// Fonction to generate list of skills by domain
	public function generateCompetencyChart($pdf, $competencies, $chartX, $chartY, $chartWidth, $chartHeight, $scale = 1)
	{
		$pdf->SetFont('helvetica', '', 8 * $scale);
		$pdf->SetTextColor(0, 0, 0); 

		// Marges
		$marginLeft = 25; 
		$marginRight = 5; 
		$columnWidth = ($chartWidth - 5 - 5) / 3;
		//scale is a parameter to enlarge (-, +) the width of list
		$lineHeight = 10 * $scale; 
		$sectionMargin = 5 * $scale; 
		$domainTitleFontSize = 9 * $scale;
		$competencyFontSize = 8 * $scale;
		
		// Initiale column witdh
		$currentY = $chartY + 18; 
		$columnsX = [$marginLeft, $marginLeft + $columnWidth, $marginLeft + 2 * $columnWidth];
		$columnsY = [$currentY, $currentY, $currentY];
		$columnIndex = 0; 

		foreach ($competencies as $domain => $skills) {
			// Calcul de la hauteur totale du bloc (titre du domaine + toutes les compétences)
			$domainTitleHeight = $pdf->GetStringHeight($columnWidth, $domain);
			$totalSkillsHeight = count($skills) * $lineHeight;
			$blockHeight = $totalSkillsHeight + $domainTitleHeight + $sectionMargin;

			// Vérifiez si la colonne actuelle a assez d'espace pour le bloc
			$availableHeight = $pdf->getPageHeight() - $pdf->getFooterMargin() - $columnsY[$columnIndex] - 10;
			if ($blockHeight > $availableHeight) {
				// Si non, passer à la page suivante et réinitialiser Y pour toutes les colonnes
				$pdf->AddPage();
				$columnsY = [$chartY + 18, $chartY + 18, $chartY + 18];
			}

			// Position X et Y actuelles pour cette colonne
			$currentX = $columnsX[$columnIndex];
			$currentY = $columnsY[$columnIndex];

			// Domain title
			$pdf->SetFont('helvetica', 'B', $domainTitleFontSize);
			$pdf->SetTextColor(25, 34, 73); // Couleur pour le titre du domaine
			$pdf->MultiCell($columnWidth, $lineHeight - 5, $domain, 0, 'L', 0, 1, $currentX, $currentY);

			// Mettre à jour la position Y après le titre du domaine
			$currentY += $domainTitleHeight + $sectionMargin;

			// Draw skills and description
			foreach ($skills as $competency) {
				$competencyName = $competency['name'];
				$competencyDescription = $competency['description'];

				// Draw skills
				$pdf->SetFont('helvetica', '', $competencyFontSize);
				$pdf->SetTextColor(0, 0, 0); // Noir pour le texte des compétences
				$pdf->MultiCell($columnWidth, $lineHeight - 2, $competencyName, 0, 'L', 0, 1, $currentX, $currentY);

				// Draw description
				if (!empty($competencyDescription)) {
					$pdf->SetFont('helvetica', '', $competencyFontSize - 2 * $scale);
					$pdf->MultiCell($columnWidth, $lineHeight, $competencyDescription, 0, 'L', 0, 1, $currentX, $currentY + $lineHeight);
					$currentY += $lineHeight;
				}

				$currentY += $lineHeight;
			}

			// Mise à jour de la position Y pour la prochaine section de cette colonne
			$columnsY[$columnIndex] = $currentY + $sectionMargin;

			// Passer à la colonne suivante
			$columnIndex = ($columnIndex + 1) % 3;
		}
	}

	
	

	// generate HTML
	public function generateJobHTML($jobTitle, $date, $description) {
		$description_with_dashes = $this->addDashesIfNotExists($description);
		return '
			<p  class="cv-title"><span>'.$date.'</span> - <span>'.$jobTitle.'</span> </p>
			<p class="description"><span>'. $description_with_dashes .'</span></p>
		';
	}

	// Function to add dashes after newlines if not already present
	// Cleaning the text. conversion to html and line break.
	public function addDashesIfNotExists($text) {
		 // Split text into lines
		
		 $lines = explode("\n ", $text);
		
		 foreach ($lines as &$line) {
			 // Check if the line starts with a dash, a point, a number followed by a point, or the specific bullet point
			 if (!preg_match('/^\s*[-.•⦁\d]/u', $line)) {
				 $line = ' &nbsp;' . $line;
			 }
		 }
	
		 // Join lines and convert newlines to <br>
		return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA1-\xAC\xAE-\xFF\x{0100}-\x{017F}\x{2018}\x{2019}]+/u', '', nl2br(implode("\n ", $lines)));
	}

	 // Function to add a section title
	 public function AddSectionTitle($title, $pdf) {
		$pdf->Ln(5); // Line break
		$pdf->SetX(12); // margin left
		$pdf->SetLineStyle(array('width' => 0.2, 'color' => array(41,79,141))); // Blue color
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 180, $pdf->GetY()); // Draw the line
        // Set title font
        $pdf->SetFont('helvetica', 'B', 14);
        // Set title color
        $pdf->SetTextColor(41,79,141); // Blue color
		
        // Add title with a border line
        $pdf->Cell(0, 10, $title, 0, 1, 'L');
		$pdf->SetX(12); //margin left
        // Draw a border line under the title
        $pdf->SetLineStyle(array('width' => 0.2, 'color' => array(41,79,141))); // Blue color
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 180, $pdf->GetY()); // Draw the line
        $pdf->Ln(2); // Line break
    }

	/**
	 * Draws a bar chart on the TCPDF instance.
	 * 
	 * @param TCPDF $pdf TCPDF instance.
	 * @param array $data Data for the bar chart.
	 * @param array $colors Colors for the bars.
	 * @param int $x X-coordinate of the chart.
	 * @param int $y Y-coordinate of the chart.
	 * @param int $width Width of the chart.
	 * @param int $height Height of the chart.
	 */
	public function generateBarChart($pdf, $data, $colors, $x, $y, $width, $height) {
		$barWidth = ($width / count($data)) * 0.6; // Width of each bar, adjusted to fit within chart width
		$spaceBetweenBars = ($width / count($data)) * 0.4; // Space between bars
		$maxValue = max($data);
		$barMaxHeight = $height - 20; // Max height for bars (leave space for labels)
		$gridLineCount = 5; // Number of grid lines to draw

		// Draw background with a gradient
		$pdf->SetFillColor(245, 245, 245); // Light gray background for the chart area
		$pdf->Rect($x - 10, $y - $barMaxHeight - 15, $width + 20, $height + 20, 'F'); // Background with padding

		// Add a subtle gradient
		$pdf->SetFillColor(240, 240, 240); // Slightly different gray for gradient effect
		$pdf->Rect($x - 10, $y - $barMaxHeight - 15, $width + 20, $height, 'F'); // Draw gradient background
		
		// Draw Y-axis grid lines
		for ($i = 0; $i <= $gridLineCount; $i++) {
			$yPosition = $y - ($i * $barMaxHeight / $gridLineCount);
			$valueLabel = ($maxValue / $gridLineCount) * $i;

			// Draw horizontal grid line
			$pdf->Line($x, $yPosition, $x + $width, $yPosition);

			// Label the Y-axis
			$pdf->SetXY($x - 10, $yPosition - 3);
			$pdf->Cell(10, 6, number_format($valueLabel, 0), 0, 0, 'R');
		}
		
		$pdf->SetFont('helvetica', '', 8); // Regular text

		// Draw X-axis and bars
		foreach ($data as $label => $value) {
			$barHeight = ($value / $maxValue) * $barMaxHeight;

			// Set the color of the bar
			$color = isset($colors[$label]) ? $colors[$label] : [0, 0, 0]; // Default to black
			$pdf->SetFillColor($color[0], $color[1], $color[2]);

			// Draw the bar
			$pdf->Rect($x, $y - $barHeight, $barWidth, $barHeight, 'DF');

			// Add shadow to the bar
			$pdf->SetFillColor(0, 0, 0, 20); // Semi-transparent black for shadow
			$pdf->Rect($x + 1, $y - $barHeight + 1, $barWidth, 2, 'F'); // Shadow effect

			// Rotate and display the truncated labels below each bar
			$pdf->StartTransform();
			$pdf->Rotate(45, $x + ($barWidth / 2), $y + 10);
			$pdf->Text($x, $y + 5, substr($label, 0, 4));
			$pdf->StopTransform();

			// Display the values above the bars
			$pdf->SetXY($x, $y - $barHeight - 10);

			// Move the x-coordinate for the next bar
			$x += $barWidth + $spaceBetweenBars;
		}

		// Draw X-axis grid line
		$pdf->Line($x - $width, $y, $x, $y); // Draw X-axis line at the bottom of the bars
	}

	/**
     * Draws a stacked bar chart on the TCPDF instance.
     * 
     * @param TCPDF $pdf TCPDF instance.
     * @param array $data Data for the bar chart.
     * @param array $colors Colors for the bar chart.
     * @param int $x X-coordinate of the top-left corner of the chart.
     * @param int $y Y-coordinate of the top-left corner of the chart.
     * @param int $width Width of the chart.
     * @param int $height Height of the chart.
     */
    public function generateBarEmpChart($pdf, $data, $colors, $x, $y, $width, $height) {
        $maxValue = 0;
        foreach ($data as $label => $years) {
            $totalYears = array_sum($years);
            if ($totalYears > $maxValue) {
                $maxValue = $totalYears;
            }
        }

        $barWidth = $width / count($data);
        $pdf->SetFont('helvetica', '', 8); // Regular text

        foreach ($data as $label => $years) {
            $currentX = $x;
            $pdf->SetXY($currentX, $y + $height + 2);
            $pdf->Cell($barWidth, 5, $label, 0, 0, 'C');

            $currentHeight = 0;
            foreach ($years as $job => $year) {
                $barHeight = ($year / $maxValue) * $height;
                $color = isset($colors[$job]) ? $colors[$job] : [0, 0, 0];
                $pdf->SetFillColor($color[0], $color[1], $color[2]);
                $pdf->Rect($currentX, $y + $height - $currentHeight - $barHeight, $barWidth, $barHeight, 'F');
                $currentHeight += $barHeight;
            }
            $x += $barWidth;
        }
    }

	/**
	 * Draws a pie chart on the TCPDF instance.
	 * 
	 * @param TCPDF $pdf TCPDF instance.
	 * @param array $data Data for the pie chart.
	 * @param array $colors Colors for the pie chart.
	 * @param int $centerX X-coordinate of the center of the chart.
	 * @param int $centerY Y-coordinate of the center of the chart.
	 * @param int $radius Radius of the chart.
	 */
	public function generatePieChart($pdf, $data, $colors, $centerX, $centerY, $radius) {
		$total = array_sum($data);
		$startAngle = 0;

		foreach ($data as $label => $value) {
			$sliceAngle = ($value / $total) * 360;
			$endAngle = $startAngle + $sliceAngle;

			// Set color for the slice
			$color = isset($colors[$label]) ? $colors[$label] : [0, 0, 0];
			$pdf->SetFillColor($color[0], $color[1], $color[2]);

			// Draw the slice
			$pdf->PieSector($centerX, $centerY, $radius, $startAngle, $endAngle, 'FD', false, 0, 2);

			// Update the start angle for the next slice
			$startAngle += $sliceAngle;
		}

		$pdf->SetFont('helvetica', '', 8); // Regular text
		// Optional: Add labels outside the pie chart
		$startAngle = 0;
		foreach ($data as $label => $value) {
			$sliceAngle = ($value / $total) * 360;
			$endAngle = $startAngle + $sliceAngle;

			// Calculate label position
			$midAngle = $startAngle + ($sliceAngle / 2);
			$labelX = $centerX + ($radius * 1.2 * cos(deg2rad($midAngle)));
			$labelY = $centerY + ($radius * 1.2 * sin(deg2rad($midAngle)));

			// Draw the label
			$pdf->SetTextColor(0, 0, 0);
			$pdf->Text($labelX, $labelY, $label);

			// Update the start angle
			$startAngle += $sliceAngle;
		}
	}


	/**
	 * Draws a chart and descriptive text in a styled table layout.
	 * 
	 * @param TCPDF $pdf TCPDF instance.
	 * @param array $chartData Data for the chart (start and end dates for jobs).
	 * @param array $colors Colors for the chart.
	 * @param string $text Text to display next to the chart.
	 * @param int $marginTop Margin at the top of the table.
	 */
	public function drawTableWithChart($pdf, $chartData, $colors, $text, $marginTop = 20) {
		// Store original margins
        list($originalLeftMargin, $originalTopMargin, $originalRightMargin) = [$pdf->getMargins()['left'], $pdf->getMargins()['top'], $pdf->getMargins()['right']];
		// Define dimensions and positions
		$tableWidth = 200; // Width of the table
		$margin = 20; // Margin size
		$columnWidth = ($tableWidth - $margin * 2) / 2; // Width of each column with margins
		$chartHeight = 30; // Height of the chart
		$chartWidth = $columnWidth - 2; // Width of the chart
		$spaceBetween = 5; // Space between columns
	
		$imgicon = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/erp/custom/gpeccustom/img/daily-job.png");
						
	

		// Set margins
		$pdf->SetMargins($margin, $marginTop, $margin);

		// Draw text description
		$pdf->SetXY($margin, $marginTop);
		$pdf->SetFont('helvetica', '', 11);
		$pdf->MultiCell($columnWidth, 10, $text, 0, 'L', 0, 1, '', '', true, 0, true, 0, true, 0, false);

		// Draw legend
		$pdf->SetFont('helvetica', '', 10); // Legend text
		$legendY = $pdf->GetY();
		$iconSize = 7; // Size of the icon
		foreach ($colors as $jobData => $color) {
			$jobData = explode('_', $jobData);
			$job = $jobData[0];
			// Draw color square
			$pdf->SetFillColor($color[0], $color[1], $color[2]);
			// $pdf->Rect($margin + 1, $legendY, $iconSize, $iconSize, 'F');
			$pdf->Rect($margin + 2, $legendY + 2, 6, 2, 'F'); // Adjusted size for better visibility
			// Draw the image/icon
			// $pdf->Image('@' . $imgicon, $margin + 1, $legendY, 7, 7, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
		
			// Draw job title and color
			$pdf->SetX($margin + 10); // Adjust X position for text
			$pdf->Cell(0, 5, "$job - date", 0, 1, 'L');
			$legendY += 5; // Move down for next item
		}

		// Adjust Y position to align with the text description and legend
		$chartY = $legendY + 20; // Ensure the chart starts below the legend

		// Draw the chart
		$chartX = $margin + $columnWidth + $spaceBetween; // X position for the chart
		$pdf->SetXY($chartX, $chartY);

		$this->generateHorizontalBarChart($pdf, $chartData, $colors, $chartX, $chartY, $chartWidth, $chartHeight);
	 	// Restore original margins after drawing the chart and table
        $pdf->SetMargins($originalLeftMargin, $originalTopMargin, $originalRightMargin);
	}


	private function generateHorizontalBarChart($pdf, $chartData, $colors, $chartX, $chartY, $chartWidth, $chartHeight) {
		$months = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jui', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
		$years = array_keys($chartData);
		rsort($years); // Ensure years are sorted in descending order
		$numMonths = count($months) - 1; // Adjust for empty first element
		$numYears = count($years);
	
		$baseBarHeight = 5; // Base height for each bar in mm
		$barWidth = $chartWidth / $numMonths; // Width of each month
	
		// Draw the background of the chart area
		$pdf->SetFillColor(255, 255, 255); // White background for the chart
		$pdf->Rect($chartX, $chartY, $chartWidth, $chartHeight, 'F');
	
		// Draw the border of the chart (only left and bottom)
		$pdf->SetDrawColor(200, 200, 200); // Light gray border
		$pdf->Line($chartX, $chartY, $chartX, $chartY + $chartHeight);
		$pdf->Line($chartX, $chartY + $chartHeight, $chartX + $chartWidth, $chartY + $chartHeight);
	
		// Draw the X-axis (months) and vertical lines
		$currentX = $chartX;
		$pdf->SetFont('helvetica', '', 8);
		$pdf->SetDrawColor(230, 230, 230); // Color of vertical lines
		foreach ($months as $month) {
			if (!empty($month)) {
				$pdf->Text($currentX + ($barWidth / 2) - 5, $chartY + $chartHeight + 5, $month);
			}
			$pdf->Line($currentX, $chartY, $currentX, $chartY + $chartHeight);
			$currentX += $barWidth;
		}
	
		// Calculate the maximum number of overlapping jobs for each year
		$yearSpaces = [];
		$maxBarHeight = 0;
	
		foreach ($years as $year) {
			$jobs = isset($chartData[$year]) ? $chartData[$year] : [];
			$maxOverlap = 0;
	
			// Determine the maximum number of overlapping jobs
			foreach ($jobs as $job) {
				$overlappingJobs = 0;
				$startDate = new DateTime($job['start']);
				$endDate = new DateTime($job['end']);
	
				foreach ($jobs as $otherJob) {
					if ($job !== $otherJob) {
						$otherStartDate = new DateTime($otherJob['start']);
						$otherEndDate = new DateTime($otherJob['end']);
						if ($startDate <= $otherEndDate && $endDate >= $otherStartDate) {
							$overlappingJobs++;
						}
					}
				}
	
				$maxOverlap = max($maxOverlap, $overlappingJobs + 1); // +1 for the job itself
			}
	
			// Calculate the space needed for the maximum overlap
			$barHeight = $baseBarHeight * $maxOverlap;
			$yearSpaces[$year] = $barHeight; // Store space required for this year
			$maxBarHeight = max($maxBarHeight, $barHeight); // Keep track of the max bar height
		}
	
		// Draw the Y-axis (years) with dynamic spacing
		$currentY = $chartY + $chartHeight;
		$pdf->SetFont('helvetica', '', 8);
		foreach ($years as $year) {
			$space = $yearSpaces[$year] ?? $baseBarHeight; // Default to baseBarHeight if no specific space
			$pdf->Text($chartX - 10, $currentY - ($space / 2) - 3, $year);
			$currentY -= $space;
		}
	
		// Draw the bars
		$currentY = $chartY + $chartHeight;
		foreach ($years as $year) {
			$currentX = $chartX;
	
			// Get the data for the year
			$yearData = isset($chartData[$year]) ? $chartData[$year] : [];
			$barHeight = $yearSpaces[$year] ?? $baseBarHeight;
	
			foreach ($yearData as $jobData) {
				if (isset($jobData['jobs'])) {
					$jobs = $jobData['jobs'];
					$numJobs = count($jobs);
					$sectionHeight = $barHeight / max(1, $numJobs); // Evenly distribute space among jobs
	
					foreach ($jobs as $jobIndex => $job) {
						$jobStart = new DateTime($job['start']);
						$jobEnd = new DateTime($job['end']);
						$color = isset($colors[$job['job']]) ? $colors[$job['job']] : [0, 0, 0];
	
						$pdf->SetFillColor($color[0], $color[1], $color[2]);
	
						$startMonth = $jobStart->format('n') - 1;
						$endMonth = $jobEnd->format('n');
	
						// Calculate the start and end position of the bar
						if ($year == $jobStart->format('Y') && $year == $jobEnd->format('Y')) {
							$startX = $chartX + ($startMonth * $barWidth);
							$endX = $chartX + (($endMonth + 1) * $barWidth);
						} elseif ($year == $jobStart->format('Y')) {
							$startX = $chartX + ($startMonth * $barWidth);
							$endX = $chartX + (12 * $barWidth);
						} elseif ($year == $jobEnd->format('Y')) {
							$startX = $chartX;
							$endX = $chartX + (($endMonth + 1) * $barWidth);
						} else {
							$startX = $chartX;
							$endX = $chartX + (12 * $barWidth);
						}
	
						// Draw the bar segment for this job
						$segmentY = $currentY - (($jobIndex + 1) * $sectionHeight);
	
						$pdf->Rect($startX, $segmentY, $endX - $startX, $sectionHeight - 1, 'DF');
	
						// Optional: Add job label in the bar
						// Uncomment if needed
						
						$pdf->SetTextColor(0, 0, 0); // Black text color
						$pdf->SetFont('helvetica', 'B', 7);
						$text = $job['job'];
						$textWidth = $pdf->GetStringWidth($text);
	
						// Calculate the horizontal position to center the text
						$textX = $startX + (($endX - $startX) / 2) - ($textWidth / 2);
	
						// Calculate the vertical position to center the text
						$textY = $segmentY + ($sectionHeight / 2) - 2;
	
						// Draw the centered text
						$pdf->Text($textX, $textY, $text);
						
					}
				}
			}
	
			$currentY -= $barHeight;
		}
	}
	

	/**
	 * Get job name from job data.
	 * 
	 * @param array $jobData Data for a specific job.
	 * @return string Job name.
	 */
	private function getJobNameFromData($jobData) {
		// Assume each jobData includes a 'job' key for the job name
		
		$value = explode('_', $jobData['job']);
		$job = $value[0];
		return isset($jobData['job']) ? $job : 'Emploi non identifié';
	} 

	/**
	 * Generates an array of unique colors for each job label in the input array.
	 *
	 * @param array $inputArray The input array with the format [id_job][dte_start][date_end] => job_label
	 * @return array An array of colors indexed by job label
	 */
	function generateColors($inputArray) {
		$colors = [];
		$defaultColors = [
			[109, 139, 40],  // Green
			[248, 203, 69],  // Yellow
			[227, 136, 49],  // Orange
			[32,13,2,255],     // Brown
			[100, 100, 100], // Gray
			[255, 0, 0],     // Red
			[0, 255, 0],     // Lime
			[0, 0, 255],     // Blue
			[255, 255, 0],   // Yellow
			[0, 255, 255],   // Cyan
			[255, 0, 255],   // Magenta
			[192, 192, 192], // Silver
			[128, 128, 128], // Gray
			[128, 0, 0],     // Maroon
			[128, 128, 0],   // Olive
			[0, 128, 0],     // Green
			[128, 0, 128],   // Purple
			[0, 128, 128],   // Teal
			[0, 0, 128],     // Navy
		];

		// Keep track of assigned colors to avoid duplicates
		$assignedColors = [];

		foreach ($inputArray as $jobDetails) {
			foreach ($jobDetails as $startDate => $endDateAndLabel) {
				foreach ($endDateAndLabel as $endDate => $jobData) {
					$jobData = explode('_', $jobData);
					$jobLabel = $jobData[0];
					if (!isset($colors[$jobLabel])) {
						// Assign the next available color
						foreach ($defaultColors as $color) {
							$colorKey = implode(',', $color);
							if (!isset($assignedColors[$colorKey])) {
								$colors[$jobLabel] = $color;
								$assignedColors[$colorKey] = true;
								break;
							}
						}
					}
				}
			}
		}

		return $colors;
	}

	/**
	 * @param array $inputArray The input array with the format [id_job][dte_start][date_end] => job_label
	 * 
	 * @return array 
	 */
	function checkMultipleJobOverlaps($chartData) {
		$overlaps = [];
	
		// foreach ($chartData as $year => $jobs) {
		// 	$allPeriods = [];
	
		// 	foreach ($jobs as $jobData) {
		// 		$start = $jobData['start'];
		// 		$end = $jobData['end'];
		// 		$job = $jobData['job'];
	
		// 		$allPeriods[] = [
		// 			'start' => $start,
		// 			'end' => $end,
		// 			'job' => $job
		// 		];

				
		// 		$allPeriods2[] = [
		// 			'start' => $start,
		// 			'end' => $end,
		// 			'job' => $job
		// 		];
		// 	}
	
		// 	// Sort periods by start date
		// 	rsort($allPeriods, function($a, $b) {
		// 		return $a['start'] <=> $b['start'];
		// 	});

		// 	// Identify overlaps
		// 	$activeJobs = [];
		// 	foreach ($allPeriods as $period) {
		// 		$start = $period['start'];
		// 		$end = $period['end'];
		// 		$job = $period['job'];
	
		// 		// Remove jobs that ended before the current period starts
		// 		foreach ($activeJobs as $key => $activeJob) {
		// 			if ($activeJob['end'] < $start) {
		// 				unset($activeJobs[$key]);
		// 			}
		// 		}
	
		// 		// Add current period to active jobs
		// 		$activeJobs[] = $period;
			
		// 		// Check if more than one job is active at the same time
		// 		if (count($activeJobs) > 1) {
		// 			$overlapStart = max(array_column($activeJobs, 'start'));
		// 			$overlapEnd = min(array_column($activeJobs, 'end'));
	
		// 			// Filter jobs that are part of the overlap
		// 			$overlapJobs = array_filter($activeJobs, function($activeJob) use ($overlapStart, $overlapEnd) {
		// 				return $activeJob['start'] <= $overlapEnd && $activeJob['end'] >= $overlapStart;
		// 			});
	
		// 			// Extract job details
		// 			$jobDetails = array_map(function($job) {
		// 				return [
		// 					'job' => $job['job'],
		// 					'start' => $job['start'],
		// 					'end' => $job['end']
		// 				];
		// 			}, $overlapJobs);

		// 			$overlaps[$year][] = [
		// 				'start' => $overlapStart,
		// 				'end' => $overlapEnd,
		// 				'jobs' => $jobDetails
		// 			];
		// 		}
		// 	}
			
		// }

		// Merge overlaps for each period within the same year
		// foreach ($overlaps as $year => &$yearOverlaps) {
		// 	$mergedOverlaps = [];
		// 	foreach ($yearOverlaps as $overlap) {
		// 		$start = $overlap['start'];
		// 		$end = $overlap['end'];
		// 		$jobs = $overlap['jobs'];
	
		// 		$found = false;
		// 		foreach ($mergedOverlaps as &$mergedOverlap) {
		// 			if ($mergedOverlap['start'] == $start && $mergedOverlap['end'] == $end) {
		// 				$mergedOverlap['jobs'] = array_unique(array_merge($mergedOverlap['jobs'], $jobs), SORT_REGULAR);
		// 				$found = true;
		// 				break;
		// 			}
		// 		}
	
		// 		if (!$found) {
		// 			$mergedOverlaps[$year] = [
		// 				'start' => $start,
		// 				'end' => $end,
		// 				'jobs' => $jobs
		// 			];
		// 		}
		// 	}
		// 	$overlaps[$year] = $mergedOverlaps;
		// }

		// Initialize arrays to store non-overlapping jobs across all years
			$nonOverlappingJobs = [];

			foreach ($chartData as $year => $jobs) {
						$allPeriods = [];

						foreach ($jobs as $jobData) {
							$start = $jobData['start'];
							$end = $jobData['end'];
							$job = $jobData['job'];
							
							$allPeriods[] = [
								'start' => $start,
								'end' => $end,
								'job' => $job
							];
						}

						// Sort periods by start date
						usort($allPeriods, function($a, $b) {
							return $a['start'] <=> $b['start'];
						});

						$jobsByYear = [];

				// Group jobs by year
				foreach ($allPeriods as $current) {
					$startYear = (new DateTime($current['start']))->format('Y');
					
					if (!isset($jobsByYear[$startYear])) {
						$jobsByYear[$startYear] = [];
					}
					
					$jobsByYear[$startYear][] = $current;
				}

				// Step 2: Check overlaps within each year and combine duplicate jobs
				foreach ($jobsByYear as $year => $jobs) {
					// Extract job details
					$combinedJobs = [];
					foreach ($jobs as $job) {
						$jobName = $job['job'];
						$startDate = $job['start'];
						$endDate = $job['end'];

						if (isset($combinedJobs[$jobName])) {
							// Update the start date if it's earlier
							if ($combinedJobs[$jobName]['start'] > $startDate) {
								$combinedJobs[$jobName]['start'] = $startDate;
							}

							// Update the end date if it's later
							if ($combinedJobs[$jobName]['end'] < $endDate) {
								$combinedJobs[$jobName]['end'] = $endDate;
							}
						} else {
							$combinedJobs[$jobName] = [
								'job' => $jobName,
								'start' => $startDate,
								'end' => $endDate
							];
						}
					}

					// Convert the associative array back to a regular array of job details
					$jobDetails = array_values($combinedJobs);

					// Add job details to the final result array
					$nonOverlappingJobs[$year][$year] = [
						'jobs' => $jobDetails
					];
				}
			}


			// var_dump($nonOverlappingJobs[2019][2019]);

				
			// var_dump($nonOverlappingJobs);
				// Combine all jobs with their respective dates regardless of overlap
			// foreach ($allJobs as $year => $jobs) {
			//     foreach ($jobs as $job) {
			//         $overlaps[$year][] = [
			//             'start' => $job['start'],
			//             'end' => $job['end'],
			//             'jobs' => [$job]
			//         ];
			//     }
			// }

			
			// $mergedData = [$nonOverlappingJobs, $overlaps];
			// $mergedChartData = $this->flattenArray($mergedData);
			// var_dump($nonOverlappingJobs);
		
		return $nonOverlappingJobs;
	}

	/**
	 * Flatten a 3D array to a 2D array while preserving the top-level keys.
	 *
	 * @param array $inputArray The input 3D array to flatten.
	 *
	 * @return array The flattened 2D array with the first array's keys preserved.
	 */
	public function flattenArray($inputArray) {
		$flattenedArray = [];

		foreach ($inputArray as $firstKey => $innerArray) {
			// foreach ($innerArray as $secondKey => $subArray) {
				foreach ($innerArray as $itemKey => $item) {
					// If item is an array, merge it; otherwise, add it as is
					if (is_array($item)) {
						$flattenedArray[$itemKey][$itemKey] = $item;
					} else {
						$flattenedArray[$itemKey][$itemKey] = $item;
					}
				}
			// }
		}

		return $flattenedArray;
	}


	/**
	 * Transforms the input array into the chart data format.
	 *
	 * @param array $inputArray The input array with the format [fk_job][dte_start][date_end] => job_label
	 * @return array The formatted chart data array
	 */
	function transformToChartData($inputArray) {
		$chartData = [];
		$now = dol_now();
		$now = dol_print_date($now, '%Y-%m-%d');
		foreach ($inputArray as $id => $jobDetails) {
			foreach ($jobDetails as $startDate => $endDateAndLabel) {
				foreach ($endDateAndLabel as $endDate => $jobData) {
					$jobData = explode('_', $jobData);
					$jobLabel = $jobData[0];
					// Parse dates and job label
					$startYear = date('Y', strtotime($startDate));
					$endDate == null ? $endDate = $now : $endDate;
					$endYear = date('Y', strtotime($endDate));
					// Format data for each year involved in the job duration
					for ($year = $startYear; $year <= $endYear; $year++) {
						$formattedStartDate = ($year == $startYear) ? $startDate : "$year-01-01";
						$formattedEndDate = ($year == $endYear) ? $endDate : "$year-12-31";
						// var_dump($startDate);
						// var_dump($endDate);
						// var_dump($jobLabel);
						$chartData[$year][] = [
							'start' => $formattedStartDate,
							'end' => $formattedEndDate,
							'job' => $jobLabel
						];
					}
				}
			}
		}

		return $chartData;
	}

	public function generateCompetencyChartDetailed($pdf, $competencies, $chartX, $chartY, $chartWidth, $chartHeight, $scale = 0.9, $dotSize = 2)
	{
		// Levels 1 to 4
		$levels = [1, 2, 3, 4]; 
		$numLevels = count($levels);

		$spaceAfterTitle = 20; 
		$chartY += $spaceAfterTitle;
		$chartX = $chartX + 2;

		// Adjust sizes and spacings based on the scale
		$levelSpacing = $chartWidth / ($numLevels + 20); 
		$levelSpacing *= $scale;
		$chartWidth *= $scale;
		$chartHeight *= $scale;
		$dotSize *= $scale;
		$arrowWidth = 8 * $scale; 
		$arrowHeight = 6 * $scale; 

		$labelWidth = $chartWidth * 0.70;  // 40% of width for labels
		$dotAreaWidth = $chartWidth * 0.20;  // 25% of width for dots
		$domainAreaWidth = $chartWidth * 0.60; // 35% of width for domain braces

		// Set font for labels
		$pdf->SetFont('helvetica', '', 10 * $scale);
		$pdf->SetTextColor(0, 0, 0); // Black color for text

		// Calculate the minimum spacing between points
		$minPointSpacing = 10 * $scale; 

		$currentY = $chartY;
		$domainIndex = 0;
		foreach ($competencies as $domain => $skills) {
			// Calculate the actual height needed for the skills in this domain block
			$totalSkillsHeight = count($skills) * $minPointSpacing;
			$blockHeight = $totalSkillsHeight + 30; 

			// Check if the remaining space on the page is enough for this domain block
			$availableHeight = $pdf->getPageHeight() - $pdf->getFooterMargin() - $currentY - 22;
			if ($blockHeight > $availableHeight) {
				$pdf->AddPage(); // Add a new page if not enough space
				$currentY = $chartY - 20; // Reset Y position for the new page
				
				// Optional: Adjust the Y position to account for the text domain
				$currentY += 20; // Adjust the margin for the domain title on new page
			}

			$startY = $currentY;

			// Set the background color for the domain block
			$pdf->SetFillColor(207,222,241); 
			$pdf->Rect($chartX - 10, $startY, $chartWidth + 8, $blockHeight, 'F'); 

			// Set the font style for skills
			$pdf->SetFillColor(36, 31, 27);
			$pdf->SetFont('helvetica', '', 11 * $scale);

			foreach ($skills as $index => $competency) {
				$competencyName = $competency['name'];
				$competencyLevel = $competency['level'];

				// Draw competency label on the Y-axis, aligned with the points
				$yPos = $currentY + ($index * $minPointSpacing);
				// Draw the rectangle (arrow) before the competency name
				$arrowX = $chartX - $arrowWidth;
				$pdf->SetFillColor(132,123,114); 
				$pdf->Rect($arrowX, $yPos + 4, $arrowWidth - 3, $arrowHeight - 4, 'F'); 
				$pdf->MultiCell($labelWidth, 2, $competencyName, 0, 'L', 0, 1, $arrowX + $arrowWidth + 1, $yPos + 2);
				// Draw dots for each level
				for ($level = 1; $level <= $numLevels; $level++) {
					$dotX = $chartX + ($level * $levelSpacing);
					$dotY = $yPos;

					if ($level <= $competencyLevel) {
						// Colored dots
						$pdf->SetFillColor(41,79,141);
					} else {
						// Unfilled dots
						$pdf->SetFillColor(255, 255, 255); 
					}

					// Draw the dot
					$pdf->FilledCircle($dotX + 100 * $scale, $dotY + 4, $dotSize - 0.1); 
				}
			}

			// Calculate the centered position for the domain text
			$textHeight = $pdf->GetStringHeight($domainAreaWidth, $domain); // Height of the domain text
			$centerY = $startY + ($totalSkillsHeight / 2) - ($textHeight / 2);

			// Set the vertical padding based on the number of lines
			$padding = 15; // Adjust this value to add extra vertical padding
			$pdf->SetY($centerY - $padding); 

			// Draw centered domain text
			$pdf->SetFont('helvetica', 'B', 11 * $scale);
			$pdf->SetTextColor(25, 34, 73);
			$pdf->MultiCell($domainAreaWidth - 12, 5, $domain, 0, 'C', 0, 1, $chartX + $labelWidth + $dotAreaWidth, $centerY);

			$endY = $currentY + count($skills) * $minPointSpacing;

			// Update current Y for the next domain
			$currentY = $endY + 15; // Reduced space for the next domain

			// Increment domain index for the next block background color
			$domainIndex++;
		}
	}


	
	public function convertCompetenciesToArray($competencyObjects)
	{
		$competencies = [];

		foreach ($competencyObjects as $competencyObject) {
			// Extract the domain, skill label, and rank order
			$domain = $competencyObject->domaine;
			$skillName = $competencyObject->skill_label;
			$level = (int)$competencyObject->rankorder; // Convert to integer if necessary

			// Split domain by commas, & or 'et'
			$domain = $this->splitDomain($domain);

			// Initialize the domain if it doesn't exist in the array
			if (!isset($competencies[$domain])) {
				$competencies[$domain] = [];
			}
			// else{
			// 	$competencies['Autres'] = [];
			// }

			// Append the skill to the appropriate domain
			$competencies[$domain][] = [
				'name' => $skillName,
				'level' => $level
			];
		}

		return $competencies;
	}

	private function splitDomain($domain)
	{
		// Define patterns to look for in the domain
		$patterns = ["/,/", "/&/ ", "/\bet\b/ "];
	
		// Replace the patterns with a newline character
		$domain = preg_replace($patterns, " \n", $domain);
	
		return $domain;
	}
}
