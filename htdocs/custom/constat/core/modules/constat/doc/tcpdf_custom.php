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
require_once DOL_DOCUMENT_ROOT.'/custom/constat/class/constat.class.php';
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
        //$companyName = 'NAF-APE: 7112B - RCS/RM: 391 004 322 R.C.S Lyon - Numéro TVA: FR29391004322';
        //$revenue = 'Société par actions simplifiée (SAS) - Capital de 101 000 € - SIRET: 391 004 322 00080';

        // Contenu du pied de page
        /*$footerHtml = '
        <div style="text-align: center; font-size: 7px;">
            <span>' . $line1 . '</span> - <span>' . $line2 . '</span>
            <span>' . $line3 . '</span> - <span>' . $line4 . '</span>
        </div>';*/
      
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
         global $conf, $user, $db, $langs, $object;
         $outputlangs = $langs;
         $pdf = $this;
     
         // Chargement du logo
         $filepath = $conf->mycompany->dir_output . '/logos/logo-optim-ind-1.png';
     
         $fk_project = intval($object->fk_project);
         // Requête pour récupérer la référence du projet
         $sql = "SELECT p.rowid, p.ref";
         $sql .= " FROM " . MAIN_DB_PREFIX . "projet AS p";
         $sql .= " WHERE p.rowid = " . $fk_project;
     
         // Exécuter la requête projet
         $resql = $db->query($sql);
     
         // Vérifiez si la requête a échoué
         if ($resql === false) {
             echo 'SQL Error: ' . $db->lasterror();
             exit;
         }
     
         $ref_projet = '';
         // Récupérer le nom du site associé à l'objet
         if ($resql->num_rows > 0) {
             $projet = $resql->fetch_object();
             if ($projet) {
                 $ref_projet = htmlspecialchars($projet->ref);
             } 
         }
     
         // Ajouter le logo si le fichier existe
         if (file_exists($filepath)) {
             $logo_margin_left = 10;
             $logo_margin_top = 10;
             $logo_width = 30;
     
             // Ajout du logo
             $pdf->Image($filepath, $logo_margin_left, $logo_margin_top, $logo_width, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
             $pdf->SetPageMark(); // Cette option évite la disparition du logo sur certaines pages
     
             // Positionner le texte à droite du logo (Ref et nom du site)
             $this->SetXY($logo_margin_left + $logo_width + 10, $logo_margin_top);
             $pdf->SetFont('helvetica', 'B', 8);
             $pdf->SetTextColor(0, 0, 0);
     
             // Créer le contenu du header avec HTML
             $header_text = '
             <table style="width: 100%; font-size: 8px;">
                 <tr>
                     <td colspan="2" style="text-align: center; padding-bottom: 5px;">
                         <span style="font-size: 10px; font-weight: bold;">OPTIM INDUSTRIES</span>
                     </td>
                     <td style="text-align: left; width: 25%;">
                         <div style="text-align: left;">Ref : ' . $object->ref . '</div>
                     </td>
                 </tr>
                 <tr>
                     <td colspan="2" style="text-align: center; padding-bottom: 5px;">
                         <span style="font-size: 8px; color: rgb(10, 10, 80);">FICHE DE CONSTAT</span>
                     </td>
                     <td style="text-align: left; width: 25%;">
                         <div style="text-align: left;">Affaire : ' . $ref_projet . '</div>
                     </td>
                 </tr>
             </table>';
            
             // Écrire le contenu dans le PDF
             $pdf->writeHTMLCell(0, 0, '', '', $header_text, 0, 1, 0, true, 'C', true);
     
             // Ajouter un trait de séparation
             $this->SetLineStyle(array('width' => 0.1, 'color' => array(40, 80, 139)));
             $this->Line(10, $this->GetY() + 10, 200, $this->GetY() + 10); // Trait sur toute la largeur
     
             // Ajuster la position Y pour le contenu suivant
             $this->SetY($this->GetY() + 5); // Ajustez cette valeur pour éviter le chevauchement
         }
     }
     

     
     
    

}