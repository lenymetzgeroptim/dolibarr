<?php
// Activer l'affichage des erreurs temporairement pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Faure Louis <l.faure@optim-industries.fr>
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
 *   	\file       ot_card.php
 *		\ingroup    ot
 *		\brief      Page to create/edit/view ot
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("MAIN_SECURITY_FORCECSP"))   define('MAIN_SECURITY_FORCECSP', 'none');	// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification
//if (! defined('NOSESSION'))     		     define('NOSESSION', '1');				    // Disable session


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; 
$tmp2 = realpath(__FILE__); 
$i = strlen($tmp) - 1; 
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}


// Commentons temporairement ces lignes pour le debug
// ini_set('display_errors',0);
// ini_set('display_startup_errors', 0);
// error_reporting(0);

// Required files and initializations
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/ot.class.php';


dol_include_once('/ot/class/ot.class.php');
dol_include_once('/ot/lib/ot_ot.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("ot@ot", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php'));
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

// Initialize technical objects
$object = new Ot($db);
$object->fetch($id);

// Check user permissions based on project contacts
$isUserInContacts = $object->isUserInProjectContacts($user->id);
$isUserManager = $object->isUserProjectManager($user->id);


$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->ot->dir_output.'/temp/massgeneration/'.$user->id;
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';

// Permission checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('ot', 'ot', 'read');
	$permissiontoadd = $user->hasRight('ot', 'ot', 'write');
	$permissiontodelete = $user->hasRight('ot', 'ot', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('ot', 'ot', 'write');
	$permissiondellink = $user->hasRight('ot', 'ot', 'write');
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1;
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->ot->multidir_output[isset($object->entity) ? $object->entity : 1].'/ot';

if (!isModEnabled("ot")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/ot/ot_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/ot/ot_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'OT_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

	if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->validate($user);
		if ($result > 0) {
			// Génération automatique du PDF après validation
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
					}
					
					$model = 'standard_ot';
					$hidedetails = 0;
					$hidedesc = 0;
					$hideref = 0;
					
					$ret = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($ret < 0) {
						setEventMessages($object->error, $object->errors, 'warnings');
					} else {
						setEventMessages($langs->trans('PDFGeneratedSuccessfully'), null, 'mesgs');
					}
				}
				
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

	// Actions cancel, add, update, update_extras, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	if ($action == 'confirm_archive' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setArchive($user);
		if ($result > 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->generateDocument($model, $outputlangs, 0, 0, 0);
				if ($ret < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Ot");
$help_url = '';
$array_js = array('/ot/js/ot.js.php');
llxHeader("", $title, $help_url, '', '', '', $array_js, '', '', 'mod-ot page-card');

print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/custom/ot/css/ot.css', 1).'">';


// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Ot")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($backtopagejsfields) {
		print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
	}
	if ($dol_openinpopup) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
	}

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Ot"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}



// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = otPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Ot"), -1, $object->picto, 0, '', '', 0, '', 1);
	

	$formconfirm = '';
	
	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOt'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

    if($action == 'confirm_genererDocConstat' && $confirm == 'yes') {
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            if (method_exists($object, 'generateDocument') && !$error) {
                $outputlangs = $langs;
                $newlang = '';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
                    $newlang = GETPOST('lang_id', 'aZ09');
                }
                if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                    $newlang = $object->thirdparty->default_lang;
                }
                if (!empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }
				
                $model = 'standard_ot';
 
                $retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                if ($retgen < 0) {
                    setEventMessages($object->error, $object->errors, 'warnings');
                }
            }
        }
    }

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$text = $langs->trans('ConfirmActionOt', $object->ref);
		/*if (isModEnabled('notification'))
		{
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('MYOBJECT_CLOSE', $object->socid, $object);
		}*/

		$formquestion = array();
	
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
			
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/ot/ot_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	//------------------------------------------------------------------------------------------------
	// L'ORGANIGRAMME :
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				if (empty($reshook))
					$object->formAddObjectLine(1, $mysoc, $soc);
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}

	//------------------------------------------------------------------------------------------------------------------------------------------------
	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send button - commented out
			/*
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}
			*/

            // Back to draft
            if ($isUserInContacts && $isUserManager) {
                if ($object->status == $object::STATUS_VALIDATED) {
                    print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
                }
            }   

            //Modify
            if ($isUserInContacts && $isUserManager && $object->status != $object::STATUS_ARCHIVED) {
                print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
            }

			// Validate
            if ($isUserInContacts && $isUserManager) {
                if ($object->status == $object::STATUS_DRAFT) {
                    if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
                        print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
                    } else {
                        $langs->load("errors");
                        print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
                    }
                }
            }
            
            // Archive
            if ($isUserInContacts && $isUserManager) {     
                if ($object->status == $object::STATUS_VALIDATED) {
                    print dolGetButtonAction('', $langs->trans('Archiver'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_archive&confirm=yes&token='.newToken(), '', $permissiontoadd);
                }
            }

            // Générer PDF - uniquement en statut brouillon
            if ($object->status == $object::STATUS_DRAFT) {
                print dolGetButtonAction('', $langs->trans('générer PDF'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_genererDocConstat&confirm=yes&token='.newToken(), '', $permissiontoadd);
            }
			
                /*//passé au  Status Cancel
                if ($user->rights->constat->constat->ResponsableQ3SE) {
                    if ($object->status == $object::STATUS_EN_COURS) {
                        print dolGetButtonAction('', $langs->trans('passé au status cancel'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setCancel&confirm=yes&token='.newToken(), '', $permissiontoadd);
                        
                    }
                }*/
			

			// Clone
			/*if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid)?'&socid='.$object->socid:'').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}*/

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/


			// Delete
			$params = array();
			// print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete, $params);
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

//------------------------------------------------------------------------------------------------------------------------------------------------------------	


// Récupérer les utilisateurs avec leurs qualifications dans le back puis conversion dans le json
if ($object->id > 0) {
    try {
        // Récupérer les utilisateurs avec leurs qualifications dans le back puis conversion dans le json
        $usersWithQualifications = $object->getAllUsersWithQualifications();
        if (is_array($usersWithQualifications) && isset($usersWithQualifications['error'])) {
            throw new Exception("Erreur getAllUsersWithQualifications: " . $usersWithQualifications['error']);
        }
        $userjson = json_encode($usersWithQualifications);

        // Récupérer les contacts du projet
        $projectContacts = $object->getProjectContacts();
        if (is_array($projectContacts) && isset($projectContacts['error'])) {
            throw new Exception("Erreur getProjectContacts: " . $projectContacts['error']);
        }
        $data = json_encode($projectContacts);

        // Récupérer les données des cellules
        $cellData = $object->getCellsData();

        // DEBUG : Vérifier le contenu des sous-traitants
        echo "<!-- DEBUG SUBCONTRACTORS: ";
        foreach ($cellData as $cell) {
            if (is_array($cell) && isset($cell['type']) && $cell['type'] === 'soustraitantlist') {
                echo "Found subcontractors: " . print_r($cell['subcontractors'], true);
                break;
            }
        }
        echo " -->";

        $cellDataJson = json_encode($cellData);

        $otId = $object->id;

    } catch (Exception $e) {
        echo "Erreur détectée: " . $e->getMessage();
        echo "<br>Trace: " . $e->getTraceAsString();
        exit;
    }
} else {
    // Si pas d'objet, initialiser des valeurs par défaut
    $userjson = json_encode([]);
    $data = json_encode([]);
    $cellDataJson = json_encode([]);
    $otId = 0;
}

// Variables globales à passer au JavaScript
$hasOTWriteRights = $user->hasRight('ot', 'ot', 'write') ? 'true' : 'false';
$isUserProjectManager = $object->isUserProjectManager($user->id) ? 'true' : 'false';

print '

<div class="container-fluid">
        <div class="row justify-content-center">
            <div>
                <div>
                    <div class="card-header">
                        <h4>Organigramme de Travail</h4>
                    </div>
                    <div class="card-body">
                        <div class="card-container">
                            <!-- Colonne 1 -->
                            <div class="card-column">
                                <div class="card" data-role="Responsable Affaire" id="card-1">
                                    <div class="card-body">
                                        <p><strong>Responsable Affaire</strong></p>
                                        <p class="name">Nom et Prénom</p>
                                        <p class="telephone">num téléphone</p>
                                        <p>Coordonnées : X = 1, Y = 1</p>
                                    </div>
                                    
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle">Ajouter</button>
                                    <div class="dropdown-content">
                                        <button class="add-card-button" data-column="1" data-type="card">Ajouter une carte</button>
                                        <button class="add-card-button" data-column="1" data-type="list">Ajouter une liste</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne 2 -->
                            <div class="card-column">
                                <div class="card" data-role="Responsable Q3SE" id="card-2">
                                    <div class="card-body">
                                        <p><strong>Responsable Q3SE</strong></p>
                                        <p class="name">Nom et Prénom</p>
                                         <p class="telephone">num téléphone</p>
                                        <p>Coordonnées : X = 2, Y = 1</p>
                                    </div>
                                    
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle">Ajouter</button>
                                    <div class="dropdown-content">
                                        <button class="add-card-button" data-column="2" data-type="card">Ajouter une carte</button>
										
                                        <button class="add-card-button" data-column="2" data-type="list">Ajouter une liste</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne 3 -->
                            <div class="card-column">
                                <div class="card" data-role="PCR Referent" id="card-3">
                                    <div class="card-body">
                                        <p><strong>PCR Referent</strong></p>
                                        <p class="name">Nom et Prénom</p>
                                        <p class="telephone">num téléphone</p>
                                        <p>Coordonnées : X = 3, Y = 1</p>
                                    </div>
                                    
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle">Ajouter</button>
                                    <div class="dropdown-content">
                                        <button id="save-data-button-add-card" class="add-card-button" data-column="3" data-type="card">Ajouter une carte</button>
                                        <button id="save-data-button-add-liste" class="add-card-button" data-column="3" data-type="list">Ajouter une liste</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="card-columns" class="card-columns"></div>	
                    </div>
                    <div style="text-align: center; margin-top: 10px;">
                    </div>
                    <div class="supplier-section">
                    </div>
                </div>
            </div>
        </div>
    </div>
';

if ($permissiontoread) {
    print '<script>
    window.cellData = ' . $cellDataJson . ';
    window.otId = ' . json_encode($otId) . ';
    window.userdata = ' . json_encode($userdata) . ';
    window.userjson = ' . $userjson . ';
    window.status = ' . json_encode($object->status) . ';
    window.isUserProjectManager = ' . json_encode($isUserManager) . ';
    window.hasOTWriteRights = ' . $hasOTWriteRights . ';
    window.jsdata = ' . $data . ';
    </script>';

    print '<script src="'.dol_buildpath('/custom/ot/js/liste_card_dynamique.js', 1).'?v='.time().'"></script>';
}

// Ajouter une section pour la popup de création d'OT si l'utilisateur a les droits
if ($user->hasRight('ot', 'ot', 'write')) {
    ?>
    <script>
    // Script pour la popup de création d'OT depuis un projet
    $(document).ready(function() {
        // Ajouter un bouton "Créer OT" si on est sur une page de projet
        if (typeof projectId !== 'undefined' && projectId > 0) {
            // Créer le bouton de création d'OT
            var createOTButton = $('<button type="button" class="btn btn-primary" id="createOTButton">Créer un OT</button>');
            
            // Ajouter le bouton à l'interface (adapter selon votre structure HTML)
            $('.project-actions, .tabsAction').first().append(createOTButton);
            
            // Gérer le clic sur le bouton
            $('#createOTButton').click(function(e) {
                e.preventDefault();
                
                // Créer la popup de confirmation
                var popup = document.createElement("div");
                popup.style.position = "fixed";
                popup.style.top = "50%";
                popup.style.left = "50%";
                popup.style.transform = "translate(-50%, -50%)";
                popup.style.backgroundColor = "#fff";
                popup.style.border = "1px solid #ccc";
                popup.style.padding = "30px";
                popup.style.zIndex = "1000";
                popup.style.boxShadow = "0 4px 8px rgba(0,0,0,0.1)";
                popup.style.borderRadius = "5px";
                popup.style.textAlign = "center";
                popup.style.minWidth = "400px";
                popup.innerHTML = `
                    <h3 style="margin-bottom: 20px; color: #333;">Créer un OT</h3>
                    <p style="margin-bottom: 30px; color: #666; line-height: 1.5;">Voulez-vous créer un OT pour ce projet ?</p>
                    <div style="display: flex; justify-content: center; gap: 15px;">
                        <button type="button" id="confirmCreateOT" style="
                            background-color: rgb(40, 80, 139);
                            color: white;
                            border: 1px solid rgb(40, 80, 139);
                            padding: 8px 16px;
                            border-radius: 3px;
                            cursor: pointer;
                            font-size: 13px;
                            font-weight: bold;
                            text-transform: uppercase;
                            min-width: 80px;
                        ">Confirmer</button>
                        <button type="button" id="cancelCreateOT" style="
                            background-color: rgb(40, 80, 139);
                            color: white;
                            border: 1px solid rgb(40, 80, 139);
                            padding: 8px 16px;
                            border-radius: 3px;
                            cursor: pointer;
                            font-size: 13px;
                            font-weight: bold;
                            text-transform: uppercase;
                            min-width: 80px;
                        ">Annuler</button>
                    </div>
                `;
                
                // Ajouter un overlay
                var overlay = document.createElement("div");
                overlay.style.position = "fixed";
                overlay.style.top = "0";
                overlay.style.left = "0";
                overlay.style.width = "100%";
                overlay.style.height = "100%";
                overlay.style.backgroundColor = "rgba(0,0,0,0.5)";
                overlay.style.zIndex = "999";
                
                document.body.appendChild(overlay);
                document.body.appendChild(popup);

                document.getElementById("confirmCreateOT").addEventListener("click", function() {
                    // Rediriger vers la création d'OT
                    document.body.removeChild(popup);
                    document.body.removeChild(overlay);
                    window.location.href = "<?php echo dol_buildpath('/ot/ot_card.php', 1); ?>?action=create&projectid=" + projectId;
                });

                document.getElementById("cancelCreateOT").addEventListener("click", function() {
                    // Fermer la popup
                    document.body.removeChild(popup);
                    document.body.removeChild(overlay);
                });
            });
        }
    });
    </script>
    <?php
}

//----  --------------------------------------------------------------------------------------------------------------------------------------------------------
	// if ($action != 'presend') {
	// 	print '<div class="fichecenter"><div class="fichehalfleft">';
	// 	print '<a name="builddoc"></a>'; // ancre

	// 	$includedocgeneration = 1;

	// 	// Documents
	//     if ($includedocgeneration) {
	// 		$objref = dol_sanitizeFileName($object->ref);
	// 		$relativepath = $objref.'/'.$objref.'.pdf';
	// 		$filedir = $conf->ot->dir_output.'/'.$object->element.'/'.$objref;
	// 		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	// 		$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
	// 		$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
	// 		print $formfile->showdocuments('ot:Ot', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
	// 	}

	// 	// Show links to link elements
	// 	//$tableauContactProj = $form->showLinkedObjectBlock($object->showCard($object->fk_project), $linktoelem, 'contactProj');
	// 	$linktoelem = $form->showLinkToObjectBlock($object, null, array('ot'));	
	// 	$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
		


	// 	print '</div><div class="fichehalfright">';

	// 	$MAXEVENT = 10;

	// 	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/ot/ot_agenda.php', 1).'?id='.$object->id);

	// 	// List of actions on element

	// 	$formactions = new FormActions($db);
	// 	$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
	// 	//$tableauContactProj = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

	// 	print '</div></div>';
	// }

	
	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'ot';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->ot->dir_output;
	$trackid = 'ot'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

	
}

// End of page
llxFooter();
$db->close();