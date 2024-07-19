<?php
/* Copyright (C) 2008-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2016	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Charles-Fr BENKE	<charles.fr@benke.fr>
 * Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes		<bafbes@gmail.com>
 * Copyright (C) 2016-2017	Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019-2022  Frédéric France     <frederic.france@netlogic.fr>
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
 */

/**
 *	\file       htdocs/core/class/html.formfile.class.php
 *  \ingroup    core
 *	\brief      File of class to offer components to list and upload files
 */


/**
 *	Class to offer components to list and upload files
 */
class ExtendedFormFile extends FormFile
{

	/**
	 *      Return a string to show the box with list of available documents for object.
	 *      This also set the property $this->numoffiles
	 *
	 *      @param      string				$modulepart         Module the files are related to ('propal', 'facture', 'facture_fourn', 'mymodule', 'mymodule:MyObject', 'mymodule_temp', ...)
	 *      @param      string				$modulesubdir       Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into a subdir of module.
	 *      @param      string				$filedir            Directory to scan (must not end with a /). Example: '/mydolibarrdocuments/facture/FAYYMM-1234'
	 *      @param      string				$urlsource          Url of origin page (for return)
	 *      @param      int|string[]        $genallowed         Generation is allowed (1/0 or array list of templates)
	 *      @param      int					$delallowed         Remove is allowed (1/0)
	 *      @param      string				$modelselected      Model to preselect by default
	 *      @param      integer				$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
	 *      @param      integer				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
	 *      @param      int					$iconPDF            Deprecated, see getDocumentsLink
	 * 		@param		int					$notused	        Not used
	 * 		@param		integer				$noform				Do not output html form tags
	 * 		@param		string				$param				More param on http links
	 * 		@param		string				$title				Title to show on top of form. Example: '' (Default to "Documents") or 'none'
	 * 		@param		string				$buttonlabel		Label on submit button
	 * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
	 * 		@param		string				$morepicto			Add more HTML content into cell with picto
	 *      @param      Object              $object             Object when method is called from an object card.
	 *      @param		int					$hideifempty		Hide section of generated files if there is no file
	 *      @param      string              $removeaction       (optional) The action to remove a file
	 *      @param		string				$tooltipontemplatecombo		Text to show on a tooltip after the combo list of templates
	 * 		@return		string              					Output string with HTML array of documents (might be empty string)
	 */
	public function showdocuments($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $forcenomultilang = 0, $iconPDF = 0, $notused = 0, $noform = 0, $param = '', $title = '', $buttonlabel = '', $codelang = '', $morepicto = '', $object = null, $hideifempty = 0, $removeaction = 'remove_file', $tooltipontemplatecombo = '', $filter = '')
	{
		global $dolibarr_main_url_root;

		// Deprecation warning
		if (!empty($iconPDF)) {
			dol_syslog(__METHOD__.": passing iconPDF parameter is deprecated", LOG_WARNING);
		}

		global $langs, $conf, $user, $hookmanager;
		global $form;

		$reshook = 0;
		if (is_object($hookmanager)) {
			$parameters = array(
				'modulepart'=>&$modulepart,
				'modulesubdir'=>&$modulesubdir,
				'filedir'=>&$filedir,
				'urlsource'=>&$urlsource,
				'genallowed'=>&$genallowed,
				'delallowed'=>&$delallowed,
				'modelselected'=>&$modelselected,
				'allowgenifempty'=>&$allowgenifempty,
				'forcenomultilang'=>&$forcenomultilang,
				'noform'=>&$noform,
				'param'=>&$param,
				'title'=>&$title,
				'buttonlabel'=>&$buttonlabel,
				'codelang'=>&$codelang,
				'morepicto'=>&$morepicto,
				'hideifempty'=>&$hideifempty,
				'removeaction'=>&$removeaction
			);
			$reshook = $hookmanager->executeHooks('showDocuments', $parameters, $object); // Note that parameters may have been updated by hook
			// May report error
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}
		}
		// Remode default action if $reskook > 0
		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// For backward compatibility
		if (!empty($iconPDF)) {
			return $this->getDocumentsLink($modulepart, $modulesubdir, $filedir);
		}

		// Add entity in $param if not already exists
		if (!preg_match('/entity\=[0-9]+/', $param)) {
			$param .= ($param ? '&' : '').'entity='.(!empty($object->entity) ? $object->entity : $conf->entity);
		}

		$printer = 0;
		// The direct print feature is implemented only for such elements
		if (in_array($modulepart, array('contract', 'facture', 'supplier_proposal', 'propal', 'proposal', 'order', 'commande', 'expedition', 'commande_fournisseur', 'expensereport', 'delivery', 'ticket'))) {
			$printer = (!empty($user->rights->printing->read) && !empty($conf->printing->enabled)) ?true:false;
		}

		$hookmanager->initHooks(array('formfile'));

		// Get list of files
		$file_list = null;
		if (!empty($filedir)) {
			$file_list = dol_dir_list($filedir, 'files', 0, $filter, '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
		}
		if ($hideifempty && empty($file_list)) {
			return '';
		}

		$out = '';
		$forname = 'builddoc';
		$headershown = 0;
		$showempty = 0;
		$i = 0;

		$out .= "\n".'<!-- Start show_document -->'."\n";
		//print 'filedir='.$filedir;

		if (preg_match('/massfilesarea_/', $modulepart)) {
			$out .= '<div id="show_files"><br></div>'."\n";
			$title = $langs->trans("MassFilesArea").' <a href="" id="togglemassfilesarea" ref="shown">('.$langs->trans("Hide").')</a>';
			$title .= '<script nonce="'.getNonce().'">
				jQuery(document).ready(function() {
					jQuery(\'#togglemassfilesarea\').click(function() {
						if (jQuery(\'#togglemassfilesarea\').attr(\'ref\') == "shown")
						{
							jQuery(\'#'.$modulepart.'_table\').hide();
							jQuery(\'#togglemassfilesarea\').attr("ref", "hidden");
							jQuery(\'#togglemassfilesarea\').text("('.dol_escape_js($langs->trans("Show")).')");
						}
						else
						{
							jQuery(\'#'.$modulepart.'_table\').show();
							jQuery(\'#togglemassfilesarea\').attr("ref","shown");
							jQuery(\'#togglemassfilesarea\').text("('.dol_escape_js($langs->trans("Hide")).')");
						}
						return false;
					});
				});
				</script>';
		}

		$titletoshow = $langs->trans("Documents");
		if (!empty($title)) {
			$titletoshow = ($title == 'none' ? '' : $title);
		}

		$submodulepart = $modulepart;

		// modulepart = 'nameofmodule' or 'nameofmodule:NameOfObject'
		$tmp = explode(':', $modulepart);
		if (!empty($tmp[1])) {
			$modulepart = $tmp[0];
			$submodulepart = $tmp[1];
		}

		$addcolumforpicto = ($delallowed || $printer || $morepicto);
		$colspan = (4 + ($addcolumforpicto ? 1 : 0));
		$colspanmore = 0;

		// Show table
		if ($genallowed) {
			$modellist = array();

			if ($modulepart == 'company') {
				$showempty = 1; // can have no template active
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
					$modellist = ModeleThirdPartyDoc::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'propal') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
					$modellist = ModelePDFPropales::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'supplier_proposal') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_proposal/modules_supplier_proposal.php';
					$modellist = ModelePDFSupplierProposal::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'commande') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
					$modellist = ModelePDFCommandes::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'expedition') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
					$modellist = ModelePdfExpedition::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'reception') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/reception/modules_reception.php';
					$modellist = ModelePdfReception::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'delivery') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/delivery/modules_delivery.php';
					$modellist = ModelePDFDeliveryOrder::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'ficheinter') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
					$modellist = ModelePDFFicheinter::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'facture') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
					$modellist = ModelePDFFactures::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'contract') {
				$showempty = 1; // can have no template active
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
					$modellist = ModelePDFContract::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'project') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
					$modellist = ModelePDFProjects::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'project_task') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';
					$modellist = ModelePDFTask::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'product') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
					$modellist = ModelePDFProduct::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'product_batch') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';
					$modellist = ModelePDFProductBatch::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'stock') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_stock.php';
					$modellist = ModelePDFStock::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'movement') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_movement.php';
					$modellist = ModelePDFMovement::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'export') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
					$modellist = ModeleExports::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'commande_fournisseur' || $modulepart == 'supplier_order') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
					$modellist = ModelePDFSuppliersOrders::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'facture_fournisseur' || $modulepart == 'supplier_invoice') {
				$showempty = 1; // can have no template active
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
					$modellist = ModelePDFSuppliersInvoices::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'supplier_payment') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_payment/modules_supplier_payment.php';
					$modellist = ModelePDFSuppliersPayments::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'remisecheque') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';
					$modellist = ModeleChequeReceipts::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'donation') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
					$modellist = ModeleDon::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'member') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_cards.php';
					$modellist = ModelePDFCards::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'agenda' || $modulepart == 'actions') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/action/modules_action.php';
					$modellist = ModeleAction::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'expensereport') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
					$modellist = ModeleExpenseReport::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'unpaid') {
				$modellist = '';
			} elseif ($modulepart == 'user') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/user/modules_user.class.php';
					$modellist = ModelePDFUser::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'usergroup') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/usergroup/modules_usergroup.class.php';
					$modellist = ModelePDFUserGroup::liste_modeles($this->db);
				}
			} else {
				// For normalized standard modules
				$file = dol_buildpath('/core/modules/'.$modulepart.'/modules_'.strtolower($submodulepart).'.php', 0);
				if (file_exists($file)) {
					$res = include_once $file;
				} else {
					// For normalized external modules.
					$file = dol_buildpath('/'.$modulepart.'/core/modules/'.$modulepart.'/modules_'.strtolower($submodulepart).'.php', 0);
					$res = include_once $file;
				}

				$class = 'ModelePDF'.ucfirst($submodulepart);

				if (class_exists($class)) {
					$modellist = call_user_func($class.'::liste_modeles', $this->db);
				} else {
					dol_print_error($this->db, "Bad value for modulepart '".$modulepart."' in showdocuments (class ".$class." for Doc generation not found)");
					return -1;
				}
			}

			// Set headershown to avoid to have table opened a second time later
			$headershown = 1;

			if (empty($buttonlabel)) {
				$buttonlabel = $langs->trans('Generate');
			}

			if ($conf->browser->layout == 'phone') {
				$urlsource .= '#'.$forname.'_form'; // So we switch to form after a generation
			}
			if (empty($noform)) {
				$out .= '<form action="'.$urlsource.'" id="'.$forname.'_form" method="post">';
			}
			$out .= '<input type="hidden" name="action" value="builddoc">';
			$out .= '<input type="hidden" name="page_y" value="">';
			$out .= '<input type="hidden" name="token" value="'.newToken().'">';

			$out .= load_fiche_titre($titletoshow, '', '');
			$out .= '<div class="div-table-responsive-no-min">';
			$out .= '<table class="liste formdoc noborder centpercent">';

			$out .= '<tr class="liste_titre">';

			$out .= '<th colspan="'.$colspan.'" class="formdoc liste_titre maxwidthonsmartphone center">';

			// Model
			if (!empty($modellist)) {
				asort($modellist);
				$out .= '<span class="hideonsmartphone">'.$langs->trans('Model').' </span>';
				if (is_array($modellist) && count($modellist) == 1) {    // If there is only one element
					$arraykeys = array_keys($modellist);
					$modelselected = $arraykeys[0];
				}
				$morecss = 'minwidth75 maxwidth200';
				if ($conf->browser->layout == 'phone') {
					$morecss = 'maxwidth100';
				}
				$out .= $form->selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss);
				if ($conf->use_javascript_ajax) {
					$out .= ajax_combobox('model');
				}
				$out .= $form->textwithpicto('', $tooltipontemplatecombo, 1, 'help', 'marginrightonly', 0, 3, '', 0);
			} else {
				$out .= '<div class="float">'.$langs->trans("Files").'</div>';
			}

			// Language code (if multilang)
			if (($allowgenifempty || (is_array($modellist) && count($modellist) > 0)) && getDolGlobalInt('MAIN_MULTILANGS') && !$forcenomultilang && (!empty($modellist) || $showempty)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
				$formadmin = new FormAdmin($this->db);
				$defaultlang = ($codelang && $codelang != 'auto') ? $codelang : $langs->getDefaultLang();
				$morecss = 'maxwidth150';
				if ($conf->browser->layout == 'phone') {
					$morecss = 'maxwidth100';
				}
				$out .= $formadmin->select_language($defaultlang, 'lang_id', 0, null, 0, 0, 0, $morecss);
			} else {
				$out .= '&nbsp;';
			}

			// Button
			$genbutton = '<input class="button buttongen reposition nomargintop nomarginbottom" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
			$genbutton .= ' type="submit" value="'.$buttonlabel.'"';
			if (!$allowgenifempty && !is_array($modellist) && empty($modellist)) {
				$genbutton .= ' disabled';
			}
			$genbutton .= '>';
			if ($allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') {
				$langs->load("errors");
				$genbutton .= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
			}
			if (!$allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') {
				$genbutton = '';
			}
			if (empty($modellist) && !$showempty && $modulepart != 'unpaid') {
				$genbutton = '';
			}
			$out .= $genbutton;
			$out .= '</th>';

			if (!empty($hookmanager->hooks['formfile'])) {
				foreach ($hookmanager->hooks['formfile'] as $module) {
					if (method_exists($module, 'formBuilddocLineOptions')) {
						$colspanmore++;
						$out .= '<th></th>';
					}
				}
			}
			$out .= '</tr>';

			// Execute hooks
			$parameters = array('colspan'=>($colspan + $colspanmore), 'socid'=>(isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id'=>(isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart'=>$modulepart);
			if (is_object($hookmanager)) {
				$reshook = $hookmanager->executeHooks('formBuilddocOptions', $parameters, $GLOBALS['object']);
				$out .= $hookmanager->resPrint;
			}
		}

		// Get list of files
		if (!empty($filedir)) {
			$link_list = array();
			if (is_object($object)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
				$link = new Link($this->db);
				$sortfield = $sortorder = null;
				$res = $link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
			}

			$out .= '<!-- html.formfile::showdocuments -->'."\n";

			// Show title of array if not already shown
			if ((!empty($file_list) || !empty($link_list) || preg_match('/^massfilesarea/', $modulepart))
				&& !$headershown) {
				$headershown = 1;
				$out .= '<div class="titre">'.$titletoshow.'</div>'."\n";
				$out .= '<div class="div-table-responsive-no-min">';
				$out .= '<table class="noborder centpercent" id="'.$modulepart.'_table">'."\n";
			}

			// Loop on each file found
			if (is_array($file_list)) {
				// Defined relative dir to DOL_DATA_ROOT
				$relativedir = '';
				if ($filedir) {
					$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
					$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
				}

				// Get list of files stored into database for same relative directory
				if ($relativedir) {
					completeFileArrayWithDatabaseInfo($file_list, $relativedir);

					//var_dump($sortfield.' - '.$sortorder);
					if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
						$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
					}
				}

				foreach ($file_list as $file) {
					// Define relative path for download link (depends on module)
					$relativepath = $file["name"]; // Cas general
					if ($modulesubdir) {
						$relativepath = $modulesubdir."/".$file["name"]; // Cas propal, facture...
					}
					if ($modulepart == 'export') {
						$relativepath = $file["name"]; // Other case
					}

					$out .= '<tr class="oddeven">';

					$documenturl = DOL_URL_ROOT.'/document.php';
					if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
						$documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper
					}

					// Show file name with link to download
					$imgpreview = $this->showPreview($file, $modulepart, $relativepath, 0, $param);

					$out .= '<td class="minwidth200 tdoverflowmax300">';
					if ($imgpreview) {
						$out .= '<span class="spanoverflow widthcentpercentminusx valignmiddle">';
					} else {
						$out .= '<span class="spanoverflow">';
					}
					$out .= '<a class="documentdownload paddingright" href="'.$documenturl.'?modulepart='.$modulepart.'&file='.urlencode($relativepath).($param ? '&'.$param : '').'"';

					$mime = dol_mimetype($relativepath, '', 0);
					if (preg_match('/text/', $mime)) {
						$out .= ' target="_blank" rel="noopener noreferrer"';
					}
					$out .= ' title="'.dol_escape_htmltag($file["name"]).'"';
					$out .= '>';
					$out .= img_mime($file["name"], $langs->trans("File").': '.$file["name"]);
					$out .= dol_trunc($file["name"], 150);
					$out .= '</a>';
					$out .= '</span>'."\n";
					$out .= $imgpreview;
					$out .= '</td>';

					// Show file size
					$size = (!empty($file['size']) ? $file['size'] : dol_filesize($filedir."/".$file["name"]));
					$out .= '<td class="nowraponall right">'.dol_print_size($size, 1, 1).'</td>';

					// Show file date
					$date = (!empty($file['date']) ? $file['date'] : dol_filemtime($filedir."/".$file["name"]));
					$out .= '<td class="nowrap right">'.dol_print_date($date, 'dayhour', 'tzuser').'</td>';

					// Show share link
					$out .= '<td class="nowraponall">';
					if (!empty($file['share'])) {
						// Define $urlwithroot
						$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
						$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
						//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

						//print '<span class="opacitymedium">'.$langs->trans("Hash").' : '.$file['share'].'</span>';
						$forcedownload = 0;
						$paramlink = '';
						if (!empty($file['share'])) {
							$paramlink .= ($paramlink ? '&' : '').'hashp='.$file['share']; // Hash for public share
						}
						if ($forcedownload) {
							$paramlink .= ($paramlink ? '&' : '').'attachment=1';
						}

						$fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');

						$out .= '<a href="'.$fulllink.'" target="_blank" rel="noopener">'.img_picto($langs->trans("FileSharedViaALink"), 'globe').'</a> ';
						$out .= '<input type="text" class="quatrevingtpercentminusx width75 nopadding small" id="downloadlink'.$file['rowid'].'" name="downloadexternallink" title="'.dol_escape_htmltag($langs->trans("FileSharedViaALink")).'" value="'.dol_escape_htmltag($fulllink).'">';
						$out .= ajax_autoselect('downloadlink'.$file['rowid']);
					} else {
						//print '<span class="opacitymedium">'.$langs->trans("FileNotShared").'</span>';
					}
					$out .= '</td>';

					// Show picto delete, print...
					if ($delallowed || $printer || $morepicto) {
						$out .= '<td class="right nowraponall">';
						if ($delallowed) {
							$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
							$out .= '<a class="reposition" href="'.$tmpurlsource.((strpos($tmpurlsource, '?') === false) ? '?' : '&').'action='.urlencode($removeaction).'&token='.newToken().'&file='.urlencode($relativepath);
							$out .= ($param ? '&'.$param : '');
							//$out.= '&modulepart='.$modulepart; // TODO obsolete ?
							//$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
							$out .= '">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						}
						if ($printer) {
							$out .= '<a class="marginleftonly reposition" href="'.$urlsource.(strpos($urlsource, '?') ? '&' : '?').'action=print_file&token='.newToken().'&printer='.urlencode($modulepart).'&file='.urlencode($relativepath);
							$out .= ($param ? '&'.$param : '');
							$out .= '">'.img_picto($langs->trans("PrintFile", $relativepath), 'printer.png').'</a>';
						}
						if ($morepicto) {
							$morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
							$out .= $morepicto;
						}
						$out .= '</td>';
					}

					if (is_object($hookmanager)) {
						$addcolumforpicto = ($delallowed || $printer || $morepicto);
						$colspan = (4 + ($addcolumforpicto ? 1 : 0));
						$colspanmore = 0;
						$parameters = array('colspan'=>($colspan + $colspanmore), 'socid'=>(isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id'=>(isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart'=>$modulepart, 'relativepath'=>$relativepath);
						$res = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
						if (empty($res)) {
							$out .= $hookmanager->resPrint; // Complete line
							$out .= '</tr>';
						} else {
							$out = $hookmanager->resPrint; // Replace all $out
						}
					}
				}

				$this->numoffiles++;
			}
			// Loop on each link found
			if (is_array($link_list)) {
				$colspan = 2;

				foreach ($link_list as $file) {
					$out .= '<tr class="oddeven">';
					$out .= '<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
					$out .= '<a data-ajax="false" href="'.$file->url.'" target="_blank" rel="noopener noreferrer">';
					$out .= $file->label;
					$out .= '</a>';
					$out .= '</td>';
					$out .= '<td class="right">';
					$out .= dol_print_date($file->datea, 'dayhour');
					$out .= '</td>';
					// for share link of files
					$out .= '<td></td>';
					if ($delallowed || $printer || $morepicto) {
						$out .= '<td></td>';
					}
					$out .= '</tr>'."\n";
				}
				$this->numoffiles++;
			}

			if (count($file_list) == 0 && count($link_list) == 0 && $headershown) {
				$out .= '<tr><td colspan="'.(3 + ($addcolumforpicto ? 1 : 0)).'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>'."\n";
			}
		}

		if ($headershown) {
			// Affiche pied du tableau
			$out .= "</table>\n";
			$out .= "</div>\n";
			if ($genallowed) {
				if (empty($noform)) {
					$out .= '</form>'."\n";
				}
			}
		}
		$out .= '<!-- End show_document -->'."\n";
		//return ($i?$i:$headershown);
		return $out;
	}

}
