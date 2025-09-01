<?php
//============================================================+
// File name   : tcpdf_custom.php
// Author      : Soufiane Fadel
// -------------------------------------------------------------------
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with TCPDF. If not, see
// <http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// Description :
//   This is a PHP class for generating PDF documents without requiring external extensions.
//
// NOTE:
//   This class was originally derived in 2002 from the Public
//   Domain FPDF class by Olivier Plathey (http://www.fpdf.org),
//   but now is almost entirely rewritten and contains thousands of
//   new lines of code and hundreds new features.
//
// Main features:
//  * no external libraries are required for the basic functions;
//  * all standard page formats, custom page formats, custom margins and units of measure;
//  * UTF-8 Unicode and Right-To-Left languages;
//  * TrueTypeUnicode, TrueType, Type1 and CID-0 fonts;
//  * font subsetting;
//  * methods to publish some XHTML + CSS code, Javascript and Forms;
//  * images, graphic (geometric figures) and transformation methods;
//  * supports JPEG, PNG and SVG images natively, all images supported by GD (GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM) and all images supported via ImageMagick (http://www.imagemagick.org/www/formats.html)
//  * 1D and 2D barcodes: CODE 39, ANSI MH10.8M-1983, USD-3, 3 of 9, CODE 93, USS-93, Standard 2 of 5, Interleaved 2 of 5, CODE 128 A/B/C, 2 and 5 Digits UPC-Based Extension, EAN 8, EAN 13, UPC-A, UPC-E, MSI, POSTNET, PLANET, RMS4CC (Royal Mail 4-state Customer Code), CBC (Customer Bar Code), KIX (Klant index - Customer index), Intelligent Mail Barcode, Onecode, USPS-B-3200, CODABAR, CODE 11, PHARMACODE, PHARMACODE TWO-TRACKS, Datamatrix, QR-Code, PDF417;
//  * JPEG and PNG ICC profiles, Grayscale, RGB, CMYK, Spot Colors and Transparencies;
//  * automatic page header and footer management;
//  * document encryption up to 256 bit and digital signature certifications;
//  * transactions to UNDO commands;
//  * PDF annotations, including links, text and file attachments;
//  * text rendering modes (fill, stroke and clipping);
//  * multiple columns mode;
//  * no-write page regions;
//  * bookmarks, named destinations and table of content;
//  * text hyphenation;
//  * text stretching and spacing (tracking);
//  * automatic page break, line break and text alignments including justification;
//  * automatic page numbering and page groups;
//  * move and delete pages;
//  * page compression (requires php-zlib extension);
//  * XOBject Templates;
//  * Layers and object visibility.
//	* PDF/A-1b support
//============================================================+

include_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';
 require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once TCPDF_PATH.'tcpdf.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/cvtec.class.php';
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * @class TCPDFCustom extends TCPDF
 * PHP class for generating PDF documents without requiring external extensions.
 * TCPDF project (http://www.tcpdf.org) has been originally derived in 2002 from the Public Domain FPDF class by Olivier Plathey (http://www.fpdf.org), but now is almost entirely rewritten.<br>
 * @IgnoreAnnotation("public")
 * @IgnoreAnnotation("pre")
 */
class TCPDFCustom extends TCPDF
{

    protected $object;
	private $leftBandText;

    public function __construct($object) {
        parent::__construct();
        $this->object = $object;
		// $this->leftBandText = $leftBandText;
    }

	// Method to set the left band text
    public function setLeftBandText($text) {
        $this->leftBandText = $text;
    }

    /**
	 * This method is used to render the page footer.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
	 * @public
	 */
	public function Footer()
	{
        global $mysoc, $conf, $langs;

        $outputlangs = $langs;
      // First line of company infos
        $line1 = "";
        $line2 = "";
        $line3 = "";
        $line4 = "";

        if ($mysoc->name) {
			$line1 .= ($line1 ? " - " : "").$outputlangs->transnoentities("RegisteredOffice").": ".$mysoc->name;
		}
		// Address
		if ($mysoc->address) {
			$line1 .= ($line1 ? " - " : "").str_replace("\n", ", ", $mysoc->address);
		}
		// Zip code
		if ($mysoc->zip) {
			$line1 .= ($line1 ? " - " : "").$mysoc->zip;
		}
		// Town
		if ($mysoc->town) {
			$line1 .= ($line1 ? " " : "").$mysoc->town;
		}
		// Country
		if ($mysoc->country) {
			$line1 .= ($line1 ? ", " : "").$mysoc->country;
		}
		// Phone
		if ($mysoc->phone) {
			$line2 .= ($line2 ? " - " : "").$outputlangs->transnoentities("Phone").": ".$mysoc->phone;
		}
		// Fax
		if ($mysoc->fax) {
			$line2 .= ($line2 ? " - " : "").$outputlangs->transnoentities("Fax").": ".$mysoc->fax;
		}

		// URL
		if ($mysoc->url) {
			$line2 .= ($line2 ? " - " : "").$mysoc->url;
		}
		// Email
		if ($mysoc->email) {
			$line2 .= ($line2 ? " - " : "").$mysoc->email;
		}

        // Line 3 of company infos
        // Juridical status
        if (!empty($mysoc->forme_juridique_code) && $mysoc->forme_juridique_code) {
            $line3 .= ($line3 ? " - " : "").$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($mysoc->forme_juridique_code));
        }
        // Capital
        if (!empty($mysoc->capital)) {
            $tmpamounttoshow = price2num($mysoc->capital); // This field is a free string or a float
            if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) {
                $line3 .= ($line3 ? " - " : "").$outputlangs->transnoentities("CapitalOf", price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
            } elseif (!empty($mysoc->capital)) {
                $line3 .= ($line3 ? " - " : "").$outputlangs->transnoentities("CapitalOf", $mysoc->capital, $outputlangs);
            }
        }

        // Prof Id 1
	if (!empty($mysoc->idprof1) && $mysoc->idprof1 && ($mysoc->country_code != 'FR' || !$mysoc->idprof2)) {
		$field = $outputlangs->transcountrynoentities("ProfId1", $mysoc->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line3 .= ($line3 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($mysoc->idprof1);
	}
	// Prof Id 2
	if (!empty($mysoc->idprof2) && $mysoc->idprof2) {
		$field = $outputlangs->transcountrynoentities("ProfId2", $mysoc->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line3 .= ($line3 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($mysoc->idprof2);
	}

	// Line 4 of company infos
	// Prof Id 3
	if (!empty($mysoc->idprof3) && $mysoc->idprof3) {
		$field = $outputlangs->transcountrynoentities("ProfId3", $mysoc->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($mysoc->idprof3);
	}
	// Prof Id 4
	if (!empty($mysoc->idprof4) && $mysoc->idprof4) {
		$field = $outputlangs->transcountrynoentities("ProfId4", $mysoc->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($mysoc->idprof4);
	}
	// Prof Id 5
	if (!empty($mysoc->idprof5) && $mysoc->idprof5) {
		$field = $outputlangs->transcountrynoentities("ProfId5", $mysoc->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($mysoc->idprof5);
	}
	// Prof Id 6
	if (!empty($mysoc->idprof6) &&  $mysoc->idprof6) {
		$field = $outputlangs->transcountrynoentities("ProfId6", $mysoc->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($mysoc->idprof6);
	}
	// IntraCommunautary VAT
	if (!empty($mysoc->tva_intra)  && $mysoc->tva_intra != '') {
		$line4 .= ($line4 ? " - " : "").$outputlangs->transnoentities("VATIntraShort").": ".$outputlangs->convToOutputCharset($mysoc->tva_intra);
	}

        $this->SetY(-15);

        // Ajouter un trait de séparation
        $this->SetLineStyle(array('width' => 0.1, 'color' => array(207,208,203)));
        $this->Line(10, $this->GetY(), 200, $this->GetY());

        // Position pour le texte du pied de page après le trait
        $this->SetY(-12);

        // Définir la police et le style
        $this->SetFont('helvetica', '', 8);
        
        // Texte personnalisé
        // $footerText = 'Société par actions simplifiée (SAS) - Capital de 101 000 € - SIRET: 391 004 322 00080';
        $companyName = 'NAF-APE: 7112B - RCS/RM: 391 004 322 R.C.S Lyon - Numéro TVA: FR29391004322';
        $revenue = 'Société par actions simplifiée (SAS) - Capital de 101 000 € - SIRET: 391 004 322 00080';

        // Contenu du pied de page
        $footerHtml = '
        <div style="text-align: center; font-size: 7px;">
            <span>' . $line1 . '</span> - <span>' . $line2 . '</span>
            <span>' . $line3 . '</span> - <span>' . $line4 . '</span>
        </div>';
      
        // Ajouter le HTML au pied de page
        $this->writeHTMLCell(0, 0, '', '', $footerHtml, 0, 1, 0, true, 'C', true);

        // Numéro de page, à droite
        // $this->SetY(-10);
        // $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
		 // Positionner le texte du numéro de page
		 $this->SetY(-15); // Positionner à 15mm du bas de la page
		 $this->SetX(2.5);  // Positionner à 5mm de la gauche pour se placer à l'intérieur de la bande
         $this->SetFont('helvetica', 'B', 10); // Texte en gras
		 $this->SetTextColor(255,255,255);
        $page_number = $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        $this->Cell(0, 10, '' . $page_number, 0, 0, 'F');
      
	}

    
	/**
	 * This method is used to render the page header.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
	 * @public
	 */
	public function Header()
	{
		global $conf, $user, $db, $langs;
        $outputlangs = $langs;
        $now = dol_now();
        $pdf = $this;
        // Add a background image on document only if good setup of const
        // if (!empty($conf->global->MAIN_USE_BACKGROUND_ON_PDF) && ($conf->global->MAIN_USE_BACKGROUND_ON_PDF != '-1')) {		// Warning, this option make TCPDF generation being crazy and some content disappeared behind the image
		$filepath = $conf->mycompany->dir_output.'/logos/logo-optim-ind-1.png';
  
		if (file_exists($filepath)) {
            $logo_margin_left = 10;
            $logo_margin_top = 10;
			$logo_width = 30;
            // Ajout du logo
            $pdf->Image($filepath, $logo_margin_left, $logo_margin_top, $logo_width, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
			// $pdf->Image($filepath, (isset($conf->global->MAIN_USE_BACKGROUND_ON_PDF_X) ? $conf->global->MAIN_USE_BACKGROUND_ON_PDF_X : 0), (isset($conf->global->MAIN_USE_BACKGROUND_ON_PDF_Y) ? $conf->global->MAIN_USE_BACKGROUND_ON_PDF_Y : 0), 0, 20);
			$pdf->SetPageMark(); // This option avoid to have the images missing on some pages
			
			// Positionner le texte à droite du logo
			$this->SetXY($logo_margin_left + $logo_width + 10, $logo_margin_top); 
            // Définir la police pour le texte d'en-tête
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetTextColor(10, 10, 80);
            $jobtitleHtml =  pdfBuildThirdpartyNameCV($this->object, $outputlangs, "last");
               
            $pdf->writeHTMLCell(0, 0, '', '', $jobtitleHtml, 0, 1, 0, true, 'R', true);
            $date = '
            <br /><div style="text-align: right; font-size: 8px;">
                <span>Date : ' . dol_print_date($now, '%d-%m-%Y'). '</span>
            </div>';
            $pdf->writeHTMLCell(0, 0, '', '', $date, 0, 1, 0, true, 'R', true);
            // Ajouter un espace après le titre
            // $pdf->SetY($this->GetY() + 3);
           
             // Ajouter un trait de séparation
            $this->SetLineStyle(array('width' => 0.1, 'color' => array(207,208,203)));
            $this->Line(10, $this->GetY(), 200, $this->GetY());
			// $this->setCustomMargins(30, 20, 20); // Adjust as needed
           
			//draw left magin
            $this->DrawLeftMargin();
		}
	}

     // draw margin left in blue color
     public function DrawLeftMargin() {
	
		$bandWidth = 10; // Largeur de la bande en mm
        $pageHeight = $this->getPageHeight();
        // $text = 'Texte de la bande'; // Le texte que vous souhaitez afficher
		$text = $this->leftBandText;
		// Dessiner la bande bleue en arrière-plan
		$this->SetFillColor(40,80,139); // Couleur bleue
        $this->Rect(0, 0, $bandWidth, $pageHeight, 'F'); // Dessiner un rectangle à gauche

        // Calculer la hauteur totale du texte
        $this->SetFont('helvetica', 'B', 12); // Texte en gras
		$this->SetTextColor(248,250,249);
		$this->SetFontSpacing(1.5); // Espacement de 1.5 points entre chaque caractère
        $textWidth = $this->getStringWidth($text);
        $textHeight = $this->getFontSize(); // Hauteur du texte

        // Calculer la position pour centrer le texte verticalement
        $textY = ($pageHeight - $textWidth) / 2; // Position Y pour centrer verticalement
        $textX = $bandWidth / 3; // Position X pour centrer horizontalement
		// var_dump($textY);
        // Ajuster la position du texte pour l'orientation verticale

        $this->SetXY($textX, $textY);
	
		
        // Rotation du texte pour l'orienter verticalement
        $this->StartTransform();
        $this->Rotate(90, $textX, $textY);
        $this->Text($textX, $textY, $text);
        $this->StopTransform();

        // // Déplacer la position du contenu principal pour éviter la bande
        $this->SetX($bandWidth); // Positionner le contenu principal à droite de la bande
    }

	// Method to set custom margins
    public function setCustomMargins($topMargin, $leftMargin, $rightMargin) {
        $this->SetMargins($leftMargin, $topMargin, $rightMargin);
    }

     // Surcharger la méthode AddPage pour dessiner la marge bleue
     public function AddPage($orientation='', $format='', $keepmargins=false, $tocpage=false) {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
		// $this->setCustomMargins(30, 20, 20); // Adjust as needed
        // $this->DrawLeftMargin();
		
    }

	// Draw an ellipse
    function Ellipse($x, $y, $rx, $ry, $style = 'D')
    {
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $lx = 4 / 3 * (M_SQRT2 - 1) * $rx;
        $ly = 4 / 3 * (M_SQRT2 - 1) * $ry;
        $k = $this->k;
        $h = $this->h;
        $this->_out(sprintf('q %.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $rx) * $k,
            ($h - $y) * $k,
            ($x + $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x + $lx) * $k,
            ($h - ($y - $ry)) * $k,
            $x * $k,
            ($h - ($y - ($ry))) * $k
        ));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $lx) * $k,
            ($h - ($y - ($ry))) * $k,
            ($x - $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x - $rx) * $k,
            ($h - $y) * $k
        ));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x - $lx) * $k,
            ($h - ($y + $ry)) * $k,
            $x * $k,
            ($h - ($y + $ry)) * $k
        ));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c %s Q',
            ($x + $lx) * $k,
            ($h - ($y + $ry)) * $k,
            ($x + $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x + $rx) * $k,
            ($h - $y) * $k,
            $op
        ));
    }

    // Draw a filled circle by calling the Ellipse function
    function FilledCircle($x, $y, $r, $style = 'DF')
    {
        $this->Ellipse($x, $y, $r, $r, $style);
    }

   // Function to draw a brace at given coordinates
   public function DrawBrace($x1, $y1, $x2, $y2)
   {
	   // Width of the brace
	   $braceWidth = 5;
	   
	   // Calculate mid points for the brace
	   $midY = ($y1 + $y2) / 2;
	   $controlPointY1 = $midY - ($y2 - $y1) / 4;
	   $controlPointY2 = $midY + ($y2 - $y1) / 4;
	   
	   // Set line width and color
	//    $this->SetLineWidth(0.5);
	//    $this->SetDrawColor(0, 0, 0);
	   
	   // Draw the upper part of the brace
	//    $this->Bezier($x1, $y1, $x1 + $braceWidth, $y1, $x1 + $braceWidth, $controlPointY1, $x2, $midY);
	   
	//    // Draw the lower part of the brace
	//    $this->Bezier($x2, $midY, $x1 + $braceWidth, $controlPointY2, $x1 + $braceWidth, $y2, $x1, $y2);
   }


    // Function to draw a bezier curve
    function Bezier($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.3F %.3F m %.3F %.3F %.3F %.3F %.3F %.3F c', $x0 * $this->k, ($h - $y0) * $this->k, $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }
}