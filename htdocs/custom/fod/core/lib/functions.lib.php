<?php
/* Copyright (C) 2000-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Christophe Combelles		<ccomb@free.fr>
 * Copyright (C) 2005-2019	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2018	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2021	Alexandre Spangaro			<aspangaro@open-dsi.fr>
 * Copyright (C) 2014		Cédric GROSS				<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2018-2021  Frédéric France             <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Thibault Foucart            <support@ptibogxiv.net>
 * Copyright (C) 2020       Open-Dsi         			<support@open-dsi.fr>
 * Copyright (C) 2021       Gauthier VERDOL         	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021       Lény Metzger                <leny-07@hotmail.fr>
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
 *	\file			htdocs/core/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */

include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';




/**
 *  Show tab footer of a card.
 *  Note: $object->next_prev_filter can be set to restrict select to find next or previous record by $form->showrefnav.
 *
 *  @param	Object	$object			Object to show
 *  @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link
 *  @param	string	$morehtml  		More html content to output just before the nav bar
 *  @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
 *  @param	string	$fieldid   		Nom du champ en base a utiliser pour select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 *  @param	string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
 *  @param	string	$morehtmlref  	More html to show after the ref (see $morehtmlleft for before)
 *  @param	string	$moreparam  	More param to add in nav link url.
 *	@param	int		$nodbprefix		Do not include DB prefix to forge table name
 *	@param	string	$morehtmlleft	More html code to show before the ref (see $morehtmlref for after)
 *	@param	string	$morehtmlstatus	More html code to show under navigation arrows
 *  @param  int     $onlybanner     Put this to 1, if the card will contains only a banner (this add css 'arearefnobottom' on div)
 *	@param	string	$morehtmlright	More html code to show before navigation arrows
 *  @return	void
 */
function dol_banner_tab_fod($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $onlybanner = 0, $morehtmlright = '', $lienfod = 1) {
	global $conf, $form, $user, $langs, $db;

	$error = 0;

	$maxvisiblephotos = 1;
	$showimage = 1;
	$entity = (empty($object->entity) ? $conf->entity : $object->entity);
	$showbarcode = empty($conf->barcode->enabled) ? 0 : (empty($object->barcode) ? 0 : 1);
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) {
		$showbarcode = 0;
	}
	$modulepart = 'unknown';

	if ($showimage) {
		if ($modulepart != 'unknown') {
			$phototoshow = '';
			// Check if a preview file is available
			if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick")) {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir_output = (empty($conf->$modulepart->multidir_output[$entity]) ? $conf->$modulepart->dir_output : $conf->$modulepart->multidir_output[$entity])."/";
				if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice'))) {
					$subdir = get_exdir($object->id, 2, 0, 1, $object, $modulepart);
					$subdir .= ((!empty($subdir) && !preg_match('/\/$/', $subdir)) ? '/' : '').$objectref; // the objectref dir is not included into get_exdir when used with level=2, so we add it at end
				} else {
					$subdir = get_exdir($object->id, 0, 0, 1, $object, $modulepart);
				}
				if (empty($subdir)) {
					$subdir = 'errorgettingsubdirofobject'; // Protection to avoid to return empty path
				}

				$filepath = $dir_output.$subdir."/";

				$filepdf = $filepath.$objectref.".pdf";
				$relativepath = $subdir.'/'.$objectref.'.pdf';

				// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
				$fileimage = $filepdf.'_preview.png';
				$relativepathimage = $relativepath.'_preview.png';

				$pdfexists = file_exists($filepdf);

				// If PDF file exists
				if ($pdfexists) {
					// Conversion du PDF en image png si fichier png non existant
					if (!file_exists($fileimage) || (filemtime($fileimage) < filemtime($filepdf))) {
						if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS)) {		// If you experience trouble with pdf thumb generation and imagick, you can disable here.
							include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
							$ret = dol_convert_file($filepdf, 'png', $fileimage, '0'); // Convert first page of PDF into a file _preview.png
							if ($ret < 0) {
								$error++;
							}
						}
					}
				}

				if ($pdfexists && !$error) {
					$heightforphotref = 80;
					if (!empty($conf->dol_optimize_smallscreen)) {
						$heightforphotref = 60;
					}
					// If the preview file is found
					if (file_exists($fileimage)) {
						$phototoshow = '<div class="photoref">';
						$phototoshow .= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
						$phototoshow .= '</div>';
					}
				}
			} elseif (!$phototoshow) { // example if modulepart = 'societe' or 'photo'
				$phototoshow .= $form->showphoto($modulepart, $object, 0, 0, 0, 'photoref', 'small', 1, 0, $maxvisiblephotos);
			}

			if ($phototoshow) {
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">';
				$morehtmlleft .= $phototoshow;
				$morehtmlleft .= '</div>';
			}
		}

		if (empty($phototoshow)) {      // Show No photo link (picto of object)
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">';
			if ($object->element == 'action') {
				$width = 80;
				$cssclass = 'photorefcenter';
				$nophoto = img_picto('No photo', 'title_agenda');
			} else {
				$width = 14;
				$cssclass = 'photorefcenter';
				$picto = $object->picto;
				if ($object->element == 'project' && !$object->public) {
					$picto = 'project'; // instead of projectpub
				}
				$nophoto = img_picto('', 'object_'.$picto);
			}
			$morehtmlleft .= '<!-- No photo to show -->';
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
			$morehtmlleft .= $nophoto;
			$morehtmlleft .= '</div></div>';

			$morehtmlleft .= '</div>';
		}
	}

	if ($showbarcode) {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$form->showbarcode($object, 100, 'photoref').'</div>';
	}

	$tmptxt = $object->getLibStatut(6);
	if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) {
		$tmptxt = $object->getLibStatut(5);
	}
	$morehtmlstatus .= $tmptxt;

    if ($lienfod == 1){
        $morehtmlstatus .= '<br>';
        $morehtmlstatus .= '<a class="butAction" href="/erp/custom/fod/fod_card.php?id='.$object->id.'" style="margin-top: 5px;">Voir FOD</a>';
    }

	// Client Site
	if (!empty($object->client_site)) {
		$tiers = new Societe($db);
		$tiers->fetch($object->client_site);
		$morehtmlref .= '<div class="refidno" style="font-size: 0.8em;padding-top: 0px;">'.$langs->trans('ClientSite').' : '.$tiers->getNomUrl(1, 'company', 16).'</div>';
	}

	// Installation
	if (!empty($object->installation)) {
		$morehtmlref .= '<div class="refidno" style="font-size: 0.8em;padding-top: 0px;">'.$langs->trans('Installation').' : '.$object->installation.'</div>';
	}

	// Etat Installtion
	if (!empty($object->etat_installation)) {
		$morehtmlref .= '<div class="refidno" style="font-size: 0.8em;padding-top: 0px;">'.$langs->trans('EtatInstallation').' : '.$object->fields['etat_installation']['arrayofkeyval'][$object->etat_installation].(!empty($object->commentaire_etat_installation) ? ' : '.$object->commentaire_etat_installation : '').'</div>';
	}

	// Activité
	if (!empty($object->activite)) {
		$morehtmlref .= '<div class="refidno" style="font-size: 0.8em;padding-top: 0px;">'.$langs->trans('Activite').' : '.$object->activite.'</div>';
	}

	print '<div class="'.($onlybanner ? 'arearefnobottom ' : 'arearef ').'heightref valignmiddle centpercent">';
	$form_fod = new Extendedform($db);
	print $form_fod->showrefnav_fod($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
	print '</div>';
	print '<div class="underrefbanner clearboth"></div>';
}


function get_fleche_navigation($page, $file, $options = '', $nextpage = 0, $betweenarrows = '', $afterarrows = '', $limit = -1, $totalnboflines = 0, $hideselectlimit = 0, $beforearrows = '')
 {
     global $conf, $langs;
	 $out = '';
  
     $out .= '<div class="pagination"><ul>';
     if ($beforearrows) {
		$out .= '<li class="paginationbeforearrows">';
		$out .= $beforearrows;
		$out .= '</li>';
     }
     if ((int) $limit > 0 && empty($hideselectlimit)) {
         $pagesizechoices = '10:10,15:15,20:20,30:30,40:40,50:50,100:100,250:250,500:500,1000:1000,5000:5000,25000:25000';
         //$pagesizechoices.=',0:'.$langs->trans("All");     // Not yet supported
         //$pagesizechoices.=',2:2';
         if (!empty($conf->global->MAIN_PAGESIZE_CHOICES)) {
             $pagesizechoices = $conf->global->MAIN_PAGESIZE_CHOICES;
         }
  
         $out .= '<li class="pagination">';
         $out .= '<select class="flat selectlimit" name="limit" title="'.dol_escape_htmltag($langs->trans("MaxNbOfRecordPerPage")).'">';
         $tmpchoice = explode(',', $pagesizechoices);
         $tmpkey = $limit.':'.$limit;
         if (!in_array($tmpkey, $tmpchoice)) {
             $tmpchoice[] = $tmpkey;
         }
         $tmpkey = $conf->liste_limit.':'.$conf->liste_limit;
         if (!in_array($tmpkey, $tmpchoice)) {
             $tmpchoice[] = $tmpkey;
         }
         asort($tmpchoice, SORT_NUMERIC);
         foreach ($tmpchoice as $val) {
             $selected = '';
             $tmp = explode(':', $val);
             $key = $tmp[0];
             $val = $tmp[1];
             if ($key != '' && $val != '') {
                 if ((int) $key == (int) $limit) {
                     $selected = ' selected="selected"';
                 }
                 $out .= '<option name="'.$key.'"'.$selected.'>'.dol_escape_htmltag($val).'</option>'."\n";
             }
         }
         $out .= '</select>';
         if ($conf->use_javascript_ajax) {
			$out .= '<!-- JS CODE TO ENABLE select limit to launch submit of page -->
                     <script>
                     jQuery(document).ready(function () {
                         jQuery(".selectlimit").change(function() {
                             console.log("Change limit. Send submit");
                             $(this).parents(\'form:first\').submit();
                         });
                     });
                     </script>
                 ';
         }
         $out .= '</li>';
     }
     if ($page > 0) {
		$out .= '<li class="pagination paginationpage paginationpageleft"><a class="paginationprevious" href="'.$file.'?page='.($page - 1).$options.'"><i class="fa fa-chevron-left" title="'.dol_escape_htmltag($langs->trans("Previous")).'"></i></a></li>';
     }
     if ($betweenarrows) {
		$out .= '<!--<div class="betweenarrows nowraponall inline-block">-->';
		$out .= $betweenarrows;
		$out .= '<!--</div>-->';
     }
     if ($nextpage > 0) {
		$out .= '<li class="pagination paginationpage paginationpageright"><a class="paginationnext" href="'.$file.'?page='.($page + 1).$options.'"><i class="fa fa-chevron-right" title="'.dol_escape_htmltag($langs->trans("Next")).'"></i></a></li>';
     }
     if ($afterarrows) {
		$out .= '<li class="paginationafterarrows">';
		$out .= $afterarrows;
		$out .= '</li>';
     }
     $out .= '</ul></div>'."\n";
	 return $out;
 }

/**
 *      Return a PDF instance object. We create a FPDI instance that instantiate TCPDF.
 *
 *      @param	string		$format         Array(width,height). Keep empty to use default setup.
 *      @param	string		$metric         Unit of format ('mm')
 *      @param  string		$pagetype       'P' or 'l'
 *      @return TCPDF						PDF object
 */
function pdf_getInstance_custom($format = '', $metric = 'mm', $pagetype = 'P')
{
	global $conf;

	// Define constant for TCPDF
	if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
		define('K_TCPDF_EXTERNAL_CONFIG', 1); // this avoid using tcpdf_config file
		define('K_PATH_CACHE', DOL_DATA_ROOT.'/admin/temp/');
		define('K_PATH_URL_CACHE', DOL_DATA_ROOT.'/admin/temp/');
		dol_mkdir(K_PATH_CACHE);
		define('K_BLANK_IMAGE', '_blank.png');
		define('PDF_PAGE_FORMAT', 'A4');
		define('PDF_PAGE_ORIENTATION', 'P');
		define('PDF_CREATOR', 'TCPDF');
		define('PDF_AUTHOR', 'TCPDF');
		define('PDF_HEADER_TITLE', 'TCPDF Example');
		define('PDF_HEADER_STRING', "by Dolibarr ERP CRM");
		define('PDF_UNIT', 'mm');
		define('PDF_MARGIN_HEADER', 5);
		define('PDF_MARGIN_FOOTER', 10);
		define('PDF_MARGIN_TOP', 27);
		define('PDF_MARGIN_BOTTOM', 25);
		define('PDF_MARGIN_LEFT', 15);
		define('PDF_MARGIN_RIGHT', 15);
		define('PDF_FONT_NAME_MAIN', 'helvetica');
		define('PDF_FONT_SIZE_MAIN', 10);
		define('PDF_FONT_NAME_DATA', 'helvetica');
		define('PDF_FONT_SIZE_DATA', 8);
		define('PDF_FONT_MONOSPACED', 'courier');
		define('PDF_IMAGE_SCALE_RATIO', 1.25);
		define('HEAD_MAGNIFICATION', 1.1);
		define('K_CELL_HEIGHT_RATIO', 1.25);
		define('K_TITLE_MAGNIFICATION', 1.3);
		define('K_SMALL_RATIO', 2 / 3);
		define('K_THAI_TOPCHARS', true);
		define('K_TCPDF_CALLS_IN_HTML', true);
		if (!empty($conf->global->TCPDF_THROW_ERRORS_INSTEAD_OF_DIE)) {
			define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
		} else {
			define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
		}
	}

	// Load TCPDF
	require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/TCPDFCustom.class.php';

	// We need to instantiate tcpdi object (instead of tcpdf) to use merging features. But we can disable it (this will break all merge features).
	if (empty($conf->global->MAIN_DISABLE_TCPDI)) {
		require_once TCPDI_PATH.'tcpdi.php';
	}

	//$arrayformat=pdf_getFormat();
	//$format=array($arrayformat['width'],$arrayformat['height']);
	//$metric=$arrayformat['unit'];

	$pdfa = false; // PDF-1.3
	if (!empty($conf->global->PDF_USE_A)) {
		$pdfa = $conf->global->PDF_USE_A; 	// PDF/A-1 ou PDF/A-3
	}

	$pdf = new TCPDFCustom($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);

	// Protection and encryption of pdf
	if (!empty($conf->global->PDF_SECURITY_ENCRYPTION)) {
		/* Permission supported by TCPDF
		- print : Print the document;
		- modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';
		- copy : Copy or otherwise extract text and graphics from the document;
		- annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);
		- fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;
		- extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);
		- assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;
		- print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.
		- owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.
		*/

		// For TCPDF, we specify permission we want to block
		$pdfrights = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_RIGHTS) ?json_decode($conf->global->PDF_SECURITY_ENCRYPTION_RIGHTS, true) : array('modify', 'copy')); // Json format in llx_const

		// Password for the end user
		$pdfuserpass = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_USERPASS) ? $conf->global->PDF_SECURITY_ENCRYPTION_USERPASS : '');

		// Password of the owner, created randomly if not defined
		$pdfownerpass = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_OWNERPASS) ? $conf->global->PDF_SECURITY_ENCRYPTION_OWNERPASS : null);

		// For encryption strength: 0 = RC4 40 bit; 1 = RC4 128 bit; 2 = AES 128 bit; 3 = AES 256 bit
		$encstrength = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_STRENGTH) ? $conf->global->PDF_SECURITY_ENCRYPTION_STRENGTH : 0);

		// Array of recipients containing public-key certificates ('c') and permissions ('p').
		// For example: array(array('c' => 'file://../examples/data/cert/tcpdf.crt', 'p' => array('print')))
		$pubkeys = (!empty($conf->global->PDF_SECURITY_ENCRYPTION_PUBKEYS) ?json_decode($conf->global->PDF_SECURITY_ENCRYPTION_PUBKEYS, true) : null); // Json format in llx_const

		$pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass, $encstrength, $pubkeys);
	}

	return $pdf;
}