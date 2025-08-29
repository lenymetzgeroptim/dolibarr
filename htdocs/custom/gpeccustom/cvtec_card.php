<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 *   	\file       cvtec_card.php
 *		\ingroup    gpeccustom
 *		\brief      Page to create/edit/view cvtec
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
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/ongletdonneesrh.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/gpeccustom/class/cvtec.class.php');
dol_include_once('/gpeccustom/lib/gpeccustom_cvtec.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("gpeccustom@gpeccustom", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOST('lineid', 'int');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

// Initialize technical objects
$object = new CVTec($db);
$extrafields = new ExtraFields($db);
$userField = new UserField($db);
$object_static = new OngletDonneesRH($db);
$diroutputmassaction = $conf->gpeccustom->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('cvteccard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

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

$employee = new User($db);
$employee->fetch($object->fk_user);
$object_static->fetch(15);
$table_element = 'donneesrh_'.$object_static->ref;
// $element_type = $table_element;
// $extrafields->fetch_name_optionals_label($table_element, false);
$userField->table_element = $table_element;


$userField->id = $object->fk_user;
$userField->fetch_optionals();

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('gpeccustom', 'cvtec', 'read');
	$permissiontoadd = $user->hasRight('gpeccustom', 'cvtec', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('gpeccustom', 'cvtec', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('gpeccustom', 'cvtec', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('gpeccustom', 'cvtec', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->gpeccustom->multidir_output[isset($object->entity) ? $object->entity : 1].'/cvtec';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("gpeccustom")) {
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

	$backurlforlist = dol_buildpath('/gpeccustom/cvtec_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/gpeccustom/cvtec_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'GPECCUSTOM_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'GPECCUSTOM_MYOBJECT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid = 'cvtec'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("CVTec");
$help_url = '';
llxHeader('', $title, $help_url);

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("CVTec")), '', 'object_'.$object->picto);

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
	print load_fiche_titre($langs->trans("CVTec"), '', 'object_'.$object->picto);

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
	$head = cvtecPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("CVTec"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteCVTec'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$text = $langs->trans('ConfirmActionCVTec', $object->ref);
		/*if (isModEnabled('notification'))
		{
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('MYOBJECT_CLOSE', $object->socid, $object);
		}*/

		$formquestion = array();

		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
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
	$linkback = '<a href="'.dol_buildpath('/gpeccustom/cvtec_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') ? ':'.getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($permissiontoadd) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
	*/
	$morehtmlref .= '</div>';


	// dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	if($user->hasRight("user", "user", "read") || $user->admin) {
		$employee = new User($db);
		$employee->fetch($object->fk_user);
	
		$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$employee->id.'&output=file&file='.urlencode(dol_sanitizeFileName($employee->getFullName($langs).'.vcf')).'" class="refid" rel="noopener" rel="noopener">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard").' ('.$langs->trans("AddToContacts").')', 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';
	
		$urltovirtualcard = '/user/virtualcard.php?id='.((int) $employee->id);
		$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->trans("PublicVirtualCardUrl").' - '.$employee->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');
		// print $morehtmlref;
		print '<div class="inline-block floatleft valignmiddle maxwidth750 marginbottomonly refid refidpadding">CVTEC_236<div class="refidno"></div></div>';
		dol_banner_tab($employee, 'id', '', $user->hasRight("user", "user", "read") || $user->admin, 'rowid', 'ref', '');
	}else{
		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}
	$object_static->fetch(15);
$table_element = 'donneesrh_'.$object_static->ref;
// $element_type = $table_element;
// $extrafields->fetch_name_optionals_label($table_element, false);
$userField->table_element = $table_element;


$userField->id = $object->fk_user;
$userField->fetch_optionals();

	print '<div class="fichecenter">';
	print '<div class="">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
		//test 
		print '<tr class="field_description"><td class="titlefield fieldname_description tdtop">';
		print '<table class="nobordernopadding centpercent">';
		print '<tr><td class="nowrap"><span>Diplôme</span>';
		print '<span class="classfortooltip" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Dernier diplôme le plus élevé"><span class="fas fa-info-circle  em088 opacityhigh" style=" vertical-align: middle; cursor: help"></span></span>';
		print '</td>';
		print '<td class="right">';
		print '</td>';
		print '</tr></table></td>';
		print '<td class="valuefield fieldname_description wordbreak">';
		print '<span class="select2-search-choice-dolibarr noborderoncategories" style="font-size: 0.9em;">';
		print $userField->array_options['options_diplome'];
		print '</span>';
		print '</td></tr>';

		print '<tr class="field_description"><td class="titlefield fieldname_description tdtop">';
		print '<table class="nobordernopadding centpercent">';
		print '<tr><td class="nowrap">Expérience professionnelle depuis</td>';
		print '<td class="right">';
		print '</td>';
		print '</tr></table></td>';
		print '<td class="valuefield fieldname_description wordbreak">';
		print '<span class="select2-search-choice-dolibarr noborderoncategories" style="font-size: 0.9em;">';
		print dol_print_date($userField->array_options['options_exprienceprofessionnelledepuis']);
		print '</span>';
		print '</td></tr>';
		if($userField->array_options['options_expriencenucleairedepuis'] != '') {
			print '<tr class="field_description"><td class="titlefield fieldname_description tdtop">';
			print '<table class="nobordernopadding centpercent">';
			print '<tr><td class="nowrap">Expéience nucléaire depuis</td>';
			print '<td class="right">';
			print '</td>';
			print '</tr></table></td>';
			print '<td class="valuefield fieldname_description2 wordbreak">';
			print '<span class="select2-search-choice-dolibarr noborderoncategories" style="font-size: 0.9em;">';
			print dol_print_date($userField->array_options['options_expriencenucleairedepuis']);
			print '</span>';
			print '</td></tr>';
		}
		
		
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
	// print '<table class="border centpercent tableforfield">';
	// 	print '<tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">Dates </div></td></tr>';

		print '<tr class="field_description"><td class="titlefield fieldname_description tdtop">';
		print '<table class="nobordernopadding centpercent">';
		print '<tr><td class="nowrap">Expérience professionnelle depuis</td>';
		print '<td class="right">';
		print '</td>';
		print '</tr></table></td>';
		print '<td class="valuefield fieldname_description wordbreak">';
		print '<span class="select2-search-choice-dolibarr noborderoncategories" style="font-size: 0.9em;">';
		print dol_print_date($userField->array_options['options_exprienceprofessionnelledepuis']);
		print '</span>';
		print '</td></tr>';
		if($userField->array_options['options_expriencenucleairedepuis'] != '') {
			print '<tr class="field_description"><td class="titlefield fieldname_description tdtop">';
			print '<table class="nobordernopadding centpercent">';
			print '<tr><td class="nowrap">Expéience nucléaire depuis</td>';
			print '<td class="right">';
			print '</td>';
			print '</tr></table></td>';
			print '<td class="valuefield fieldname_description2 wordbreak">';
			print '<span class="select2-search-choice-dolibarr noborderoncategories" style="font-size: 0.9em;">';
			print dol_print_date($userField->array_options['options_expriencenucleairedepuis']);
			print '</span>';
			print '</td></tr>';
		}
	
		
		// print '</table>';

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
		<input type="hidden" name="id" value="' . $object->id.'">';

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


	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}

			// Back to draft
			// if ($object->status == $object::STATUS_VALIDATED) {
			// 	print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// }

			// print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			// if ($object->status == $object::STATUS_DRAFT) {
			// 	if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
			// 		print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// 	} else {
			// 		$langs->load("errors");
			// 		print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
			// 	}
			// }

			// // Clone
			// if ($permissiontoadd) {
			// 	print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid)?'&socid='.$object->socid:'').'&action=clone&token='.newToken(), '', $permissiontoadd);
			// }

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
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete, $params);
		}
		print '</div>'."\n";
	}

	// var_dump($object->getLastUserEvalJob($object->fk_user, 'all'));
	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfright">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->gpeccustom->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('gpeccustom:CVTec', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		// $linktoelem = $form->showLinkToObjectBlock($object, null, array('cvtec'));
		// $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

		print '<br><br>';
		print '</div><div class="fichehalfleft">';


		// print '<table class="centpercent notopnoleftnoright table-fiche-title"><tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">Complément d\'informations</div></td></tr></table>';
		print '<div class="box divboxtable boxdraggable" id="boxto_info">';
		print '<table summary="boxtableinfo" width="100%" class="noborder boxtable">';
		print '<tr class="liste_titre box_titre"><th colspan="1">';
		print '<div class="tdoverflowmax400 maxwidth250onsmartphone float">Complément d\'information - en cours</div><div class="nocellnopadd boxclose floatright nowraponall">';
		// print '<span class="fas fa-arrows-alt opacitymedium boxhandle hideonsmartphone cursormove marginleftonly ui-sortable-handle" style="" title="Déplacer le widget"></span>';
		// print '<span class="fas fa-times opacitymedium boxclose cursorpointer marginleftonly" style="" title="Supprimer le widget du tableau de bord" rel="x:y" id="imgclose198"></span><input type="hidden" id="boxlabelentry198" value="Statistiques de la base">';
		print '</div></th></tr>';
		print '<tr class="nohover"><td class="tdwidgetstate">';
		if(!empty($object->getLastUserJob($object->fk_user))) {
			print '<a class="boxstatsindicator thumbstat nobold nounderline" style="text-decoration: none !important;">';
			print '<div class="boxstats">';
			print '<span class="boxstatstext" title="Dernier poste occupé">Dernier poste occupé</span><br>';
			foreach($object->getLastUserJob($object->fk_user) as $key => $values) {
				foreach($values as $date_start => $vals) {
					foreach($vals as $date_end => $value) {
						$value = explode('_', $value);
						if($date_end == '') {
						print ' <span style="color:rgb(10, 20, 100);font-size:0.9em;">Depuis '.dol_print_date($date_start);
						// }
						print '</span>';
						// var_dump(dol_print_date($date_start));
						print '<a href="'.DOL_URL_ROOT.'/custom/gpeccustom/position_card.php?id='.$value[2].'" class="">';
						// print '<div class="boxstats">';
						print '<br><span class="boxstatstext" title="'.$value[0].'">'.$value[0].'</span>';
					
						// print '<span class="'.$object->picto.'" style=""></span>'.sizeof($object->getLastUserJob($object->fk_user)).'</span>';
						print '</a><br>';
						}
						
					}
				}
			}
			// print '<br>';
		
			// print '</div>';
					// Poste antérieur
			include DOL_DOCUMENT_ROOT.'/custom/gpeccustom/core/tpl/extrafields_view.tpl.php';
		
			print '<br><br><span class="boxstatsindicator" style="align-content: center;display: block;">';
			print '<span class="fas fa-user-cog  infobox-adherent inline-block" style=""></span> '.sizeof($object->getLastUserJob($object->fk_user)).'</span>';
			print '</div></a>';
			
			
		}
	
		if(!empty($object->getLastUserEvalJob($object->fk_user, 'last'))) {
			print '<a class="boxstatsindicator thumbstat nobold nounderline" href="'.DOL_URL_ROOT.'/custom/gpeccustom/cvtec_card.php?id='.$object->id.'" style="text-decoration: none !important;">';
			print '<div class="boxstats">';
			print '<span class="boxstatstext" title="Dernier poste occupé">Dernier poste évalué</span><br>';
			foreach($object->getLastUserEvalJob($object->fk_user, 'last') as $key => $values) {
				foreach($values as $date_eval => $value) {
					$value = explode('_', $value);
					print ' <span style="color:rgb(10, 20, 100);font-size:0.9em;">Le '.dol_print_date($date_eval);
					print '<a href="'.DOL_URL_ROOT.'/custom/gpeccustom/evaluation_card.php?id='.$value[1].'" class="">';
					print '<br><span class="boxstatstext" title="'.$value[0].'" >'.$value[0].'</span>';
					print '</a>';
				}
			}
			
			print '<div class="">';
			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield centpercent">';
			
			print '<tr class="collapse_poste_eval" id="collapse_poste_eval">';
			print '<td colspan="2" id="dis_card" onclick="showOptions()">';
			print '<span id="cursorpointer" class="cursorpointer far fa-plus-square"></span>';
			print '<span id="cursorpointer2" class="cursorpointer far fa-minus-square" style="display:none;">';
			print '</span>&nbsp;<strong>Postes évalués</strong>';
			print '</td></tr>';
		
			print '<tr id="evalPost" style="display:none;">';
			print '<td class="valuefield_card ">';
			if(!empty($object->getLastUserEvalJob($object->fk_user, 'all'))) {
				print '<a class="boxstatsindicator thumbstat nobold nounderline" style="text-decoration: none !important;">';
				// print '<div class="boxstats">';
				// print '<span class="boxstatstext" title="Dernier poste occupé">Dernier poste évalué</span><br>';
				foreach($object->getLastUserEvalJob($object->fk_user, 'all') as $date_eval => $values) {
					foreach($values as $fk_job => $value) {
						$value = explode('_', $value);
						
						print '<a href="'.DOL_URL_ROOT.'/custom/gpeccustom/evaluation_card.php?id='.$value[1].'" class="">';
						print '<br><span class="boxstatstext" title="'.$value[0].'" >'.$value[0].'</span>';
						print ' - <span style="color:rgb(10, 20, 100);font-size:0.9em;">Le '.dol_print_date($date_eval);
						print '</span>';
						print '</a>';
					}
				}
			}
			print '</td></tr>';
			print '</table>';
			print '</div>';
			// print '</div>';
			print '<br><span class="boxstatsindicator" style="align-content: center;display: block;">';			
			print '<span class="fas fa-layer-group  infobox-adherent inline-block" style=""></span> '.sizeof($object->getLastUserEvalJob($object->fk_user, 'all')).'</span>';
			print '</div></a>';
		}

		//Js display hide cvtec relative values 
		print '<script>function showOptions() {
			var x = document.getElementById("evalPost");
			var element = document.getElementById("cursorpointer");
			var element2 = document.getElementById("cursorpointer2");
			 
			if (x.style.display === "none") {
			  x.style.display = "block";
			  // element.classList.toggle("cursorpointer far fa-minus-square");
			} else {
			  x.style.display = "none";
			  // element.classList.toggle("cursorpointer far fa-plus-square");
			}
		  
			if (element.style.display === "none") {
			  element2.style.display = "none";
			  element.style.display = "";
			} else {
			  element.style.display = "none";
			  element2.style.display = "";
			}
		  
		  }
		</script>';

		print '<a href="#" class="boxstatsindicator thumbstat nobold nounderline">';
		print '<div class="boxstats">';
		print '<span class="boxstatstext" title="Formations">Formations</span>';
		print '<br><span class="boxstatsindicator">';
		print '<span class="fas fa-user infobox-adherent inline-block" style=""></span> 182</span>';
		print '</div></a>';

		print '<a href="#" class="boxstatsindicator thumbstat nobold nounderline">';
		print '<div class="boxstats">';
		print '<span class="boxstatstext" title="Contrat">Contrat</span>';
		print '<br><span class="boxstatsindicator">';
		print '<span class="fas fa-user infobox-adherent inline-block" style=""></span> 182</span>';
		print '</div></a>';
	
		print '</td>';
		print '</tr>';
		
		print '</table>';
		print '</div>';
	

		
		
		// print $userField->array_options['options_diplome'];
		// print '<br><br>';
		// print '<span style="padding: 0px; padding-right: 3px;">Expérience professionnelle depuis</span>';
		// print '<br>';
		// print dol_print_date($userField->array_options['options_exprienceprofessionnelledepuis']);
		// print '<br><br>';
		// print'<span style="padding: 0px; padding-right: 3px;">rExpéience nucléaire depuis</span>';
		// print '<br>';
		// print dol_print_date($userField->array_options['options_expriencenucleairedepuis']);
		// print '<br>';

		// print '<table class="border centpercent tableforfield">';
		// print '<tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">Dates </div></td></tr>';

		// print '<tr class="field_description"><td class="titlefield fieldname_description tdtop">';
		// print '<table class="nobordernopadding centpercent">';
		// print '<tr><td class="nowrap">Expérience professionnelle depuis</td>';
		// print '<td class="right">';
		// print '</td>';
		// print '</tr></table></td>';
		// print '<td class="valuefield fieldname_description wordbreak">';
		// print dol_print_date($userField->array_options['options_exprienceprofessionnelledepuis']);
		// print '</td></tr>';
		// print '<tr class="field_description"><td class="titlefield fieldname_description tdtop">';
		// print '<table class="nobordernopadding centpercent">';
		// print '<tr><td class="nowrap">Expéience nucléaire depuis</td>';
		// print '<td class="right">';
		// print '</td>';
		// print '</tr></table></td>';
		// print '<td class="valuefield fieldname_description2 wordbreak">';
		// print dol_print_date($userField->array_options['options_expriencenucleairedepuis']);
		// print '</td></tr>';
		// print '</table>';
		// $MAXEVENT = 10;

		// $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/gpeccustom/cvtec_agenda.php', 1).'?id='.$object->id);

		// // List of actions on element
		// include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		// $formactions = new FormActions($db);
		// $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	
		?>

<script>
// function showOptions() {
//   var x = document.getElementById("evalPost");
//   var element = document.getElementById("cursorpointer");
//   var element2 = document.getElementById("cursorpointer2");
   
//   if (x.style.display === "none") {
//     x.style.display = "block";
// 	// element.classList.toggle("cursorpointer far fa-minus-square");
//   } else {
//     x.style.display = "none";
// 	// element.classList.toggle("cursorpointer far fa-plus-square");
//   }

//   if (element.style.display === "none") {
// 	element2.style.display = "none";
// 	element.style.display = "";
//   } else {
// 	element.style.display = "none";
// 	element2.style.display = "";
//   }

// }

</script>
<?php



		global $user;
		$now = dol_now();
		$date_startmonth = GETPOST('date_startmonth', 'int');
		$date_startday = GETPOST('date_startday', 'int');
		$date_startyear = GETPOST('date_startyear', 'int');
		$date_endmonth = GETPOST('date_endmonth', 'int');
		$date_endday = GETPOST('date_endday', 'int');
		$date_endyear = GETPOST('date_endyear', 'int');

		$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
		$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);


		if (empty($date_start)) {
			$date_start = dol_time_plus_duree($now, -7, 'y');
		}

		if (empty($date_end)) {
			$date_end = dol_time_plus_duree($now, 1, 'y');
		}
		print '<div class="fichecenter"><div class="fichehalfleft">';
		/**
		 * action
		 */
		$skillJobsdata = $object->getskillJobAvgGraph($arr_skill_jobs, $object->fk_user, $date_start, $date_end);

		if (sizeof($skillJobsdata) > 0) {
			foreach ($skillJobsdata as $val) {

				$avrgnote[$val->fk_job][$val->date_eval] = $val;
				$alltotaljs[$val->fk_job] = $val->fk_job;
				$arrSkillJobs[] = $val->fk_job;

				$dataUsers[$val->fk_job][dol_print_date($val->date_eval, '%Y')] = array('label' => $val->job_label, 'year' => dol_print_date($val->date_eval, '%Y'), 'avg' => $val->avrgnote);
			}

			$skillJobs = empty($arr_skill_jobs) ? $arrSkillJobs : $arr_skill_jobs;

			//default colors for dataseries
			$datacolors = array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF');

			foreach ($dataUsers as $key => $values) {
				foreach ($values as $val) {

					//call to dynamic colors code generation function if not default colors code
					// array_push($datacolors, random_color());
					if (!empty($arr_skill_jobs) || $filter_fk_user > 0) {
						//datseries dynamic reconstruncting 
						foreach ($skillJobs as $jobs) {
							$avgjobs[$val['year']][] = $key == $jobs ? array('year' => $val['year'], 'avg_' . $jobs => $val['avg']) : array('avg-' . $jobs => 0.0);


							$label = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
							$words = explode(" ", $label);
							$acronym = "";

							foreach ($words as $w) {
								$acronym .= mb_substr($w, 0, 5);
								$acronym .= " ";
								$key == $jobs ? $labeljs[$key] =  ucfirst($acronym) : null;
							}
						}
					} elseif (empty($arr_skill_jobs) && ($filter_fk_user == -1 || $filter_fk_user == '')) {
						$label = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
						$words = explode(" ", $label);
						$acronym = "";

						foreach ($words as $w) {
							$acronym .= mb_substr($w, 0, 5);
							$acronym .= ". ";

							$dataseriesforavfjob[$key] = array(ucfirst($acronym), $val['avg']);
						}
					}
				}
			}


			//list of user's jobs and skills
			$dataskillJobsall = $object->getAvrSkillJobs($filter_fk_user);
			foreach ($dataskillJobsall as $val) {
				$arrskilljobs[$val->fk_job] = $val->job_label;
			}



			$totaljs = sizeof($alltotaljs);

			ksort($avgjobs);


			foreach ($avgjobs as $key => $val) {
				$flattenarr[] = call_user_func_array('array_merge', $val);
			}


			foreach ($flattenarr as $p) {
				$sizearrs[] = sizeof($p);
				//make position 0 (first value) in subarray
				array_unshift($p, $p['year']);
				unset($p['year']);
				ksort(array_keys($p));

				$data[] = $p;
			}

			//control position order of values in array for graph data and filter null duplicates values
			foreach ($data as $k => $values) {
				$empty = empty(array_filter($values, function ($a) {
					return $a !== null;
				}));

				foreach ($values as $key => $value) {
					if (!$empty) {
						$arr[$k][0] = $values[0];
						if ("avg-" == substr($key, 0, 4) || "avg_" == substr($key, 0, 4)) {
							$str = substr($key, strrpos($key, '_'));
							$str2 = substr($key, strrpos($key, '-'));

							$num = preg_replace("/[^0-9]/", '', $str);
							$num2 = preg_replace("/[^0-9]/", '', $str2);
							$nums[$num] = $num;

							if ($value > 0) {
								$arr2[$k][$num] = $value;
							}

							foreach ($nums as $n) {
								$arr[$k][$n] = $arr2[$k][$n] == null ? 0 : $arr2[$k][$n];
							}
						}
					}
				}
			}


			foreach ($arr as $vals) {
				$nbval[] = sizeof($vals);
				$nbvalues = max($nbval);

				$dataseriesforavfjob[] = array_values($vals);
			}
		}



		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';	


		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';

		print  '<tr class="liste_titre">';
		print  '<td>' . $langs->trans("Cartographie") . ' - ' . $langs->trans("Moyenne des compétences par emploi") . '</td>';

		print '<td class="right">';
		if (!empty(array_filter($arr_skill_jobs)) && sizeof(array_filter($arr_skill_jobs)) > 0) {
			print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill_jobs)) . ' emploi(s) séléctionné(s)">' . sizeof($arr_skill_jobs) . '</span>';
		}
		
		print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgjs1"></span></td>';
		print  '</tr>';

		if ($conf->use_javascript_ajax) {
			print  '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimgjs1").click(function() {
							jQuery("#idfilterjs1").toggle();
						});
					});
					</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject" id="idfilterjs1">'; // hideobject is to start hidden
			print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id='.$object->id.'&save_lastsearch_values=1">';
			print  '<input type="hidden" name="token" value="' . newToken() . '">';
			print  '<input type="hidden" name="action" value="refresh_js">';
			print  '<input type="hidden" name="page_y" value="">';

			print  $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);


			print '<br/>';
			print '<div>';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print $form->multiselectarray('arr_skill_jobs',  $arrskilljobs,  $arr_skill_jobs, '', '', '', '', '65%', '', '', 'Emploi');
			// print  ' &nbsp; ';
			print '</div>';
			
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
			print  '</form>';
			print  '</div>';

			// 	print  '</div>';
			print '</td>';
			print '</tr>';

			print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

			include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

			$WIDTH = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '360' : '360';
			$HEIGHT = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '300' : '300';
			$NBLEG = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? 2 : 1;

			$dolgraph = new DolGraph();
			$mesg = $dolgraph->isGraphKo();
			if (!$mesg && $action = 'refresh_js') {
				$dolgraph->SetData(array_values($dataseriesforavfjob));
				$dolgraph->SetDataColor(array_values($datacolors));
				$dolgraph->setLegend(array_values(array_unique(array_filter($labeljs))));

				if (!empty($arr_skill_jobs) || ($filter_fk_user != -1 && $filter_fk_user != '')) {
					$dolgraph->setShowLegend($NBLEG);
					// $dolgraph->setShowPercent(1);
					$dolgraph->SetHeight($HEIGHT);
					$dolgraph->SetWidth($WIDTH);
					// $dolgraph->SetType(array('lines'));
					$dolgraph->SetType(array('bars'));
				} else {
					// $dolgraph->setShowLegend(2);
					// $dolgraph->setShowPercent(2);
					$dolgraph->SetHeight('300');
					$dolgraph->SetWidth($WIDTH);
					$dolgraph->SetType(array('polar'));
				}


				$dolgraph->draw('idgraphavgjobskill');
				print $dolgraph->show($totaljs ? 0 : 1);

				print '</td></tr>';
			}
			print  '<tr class="liste_total">';
			print  '<td>' . $langs->trans("Total des emplois évalués") . '</td>';
			print  '<td class="right">' . $totaljs . '</td>';
			print  '</tr>';

			print  '</table>';
			print  '</div>';
			// print  '<br>';
		}
		if (empty($conf->use_javascript_ajax)) {
			$langs->load("errors");
			print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}

		print '</div><div class="fichehalfright">';

		/**
		 * actions 
		 */
		$skillData = $object->getskillAvgGraph($arr_skill, $object->fk_user, $date_skill_start, $date_skill_end);

		if (sizeof($skillData) > 0) {
		foreach ($skillData as $val) {

			$avgskillnote[$val->fk_skill][$val->date_eval] = $val;
			$alltotalskill[$val->fk_skill] = $val->fk_skill;
			$arrSkill[] = $val->fk_skill;
			$arrskill[$val->fk_skill] = $val->skill_label;

			$dataSkill[$val->fk_skill][dol_print_date($val->date_eval, '%Y')] = array('label' => $val->skill_label, 'year' => dol_print_date($val->date_eval, '%Y'), 'avg' => $val->avgskillnote);
		}

		$skillids = empty($arr_skill) ? $arrSkill : $arr_skill;

		//default colors for dataseries
		$datacolors = array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF');

		foreach ($dataSkill as $key => $values) {
			foreach ($values as $val) {

				//call to dynamic colors code generation function if not default colors code
				// array_push($datacolors, random_color());
				if (!empty($arr_skill) || $skill_fk_user > 0) {

					foreach ($skillids as $skillid) {
						$avgskill[$val['year']][] = $key == $skillid ? array('year' => $val['year'], 'avg_' . $skillid => $val['avg']) : array('avg-' . $skillid => 0.0);


						$labels = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
						$words = explode(" ", $labels);
						$acronym = "";

						foreach ($words as $w) {
							$acronym .= mb_substr($w, 0, 5);
							$acronym .= " ";
							$key == $skillid ? $labelskill[$key] =  ucfirst($acronym) : null;
						}
					}
				} elseif (empty($arr_skill) && ($skill_fk_user == -1 || $skill_fk_user == '')) {
					$labelskill = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
					$words = explode(" ", $labelskill);
					$acronym = "";

					foreach ($words as $w) {
						$acronym .= mb_substr($w, 0, 5);
						$acronym .= ". ";

						$dataseriesforavgskill[$key] = array(ucfirst($acronym), $val['avg']);
					}
				}
			}
		}


		// list of user's jobs and skills
		$dataskillall = $object->getAvrSkill($object->fk_user);
		foreach ($dataskillall as $val) {
			$arrskillall[$val->fk_skill] = $val->skill_label;
		}


		$totalskill = sizeof($alltotalskill);

		ksort($avgskill);


		foreach ($avgskill as $key => $val) {
			$flattenarrskill[] = call_user_func_array('array_merge', $val);
		}


		foreach ($flattenarrskill as $p) {
			$sizearrs[] = sizeof($p);
			//make position 0 (first value) in subarray
			array_unshift($p, $p['year']);
			unset($p['year']);
			ksort(array_keys($p));

			$skdata[] = $p;
		}

		//control position order of values in array for graph data and delete duplicates if they are null
		foreach ($skdata as $k => $values) {
			$empty = empty(array_filter($values, function ($a) {
				return $a !== null;
			}));

			foreach ($values as $key => $value) {
				if (!$empty) {
					$arrsk[$k][0] = $values[0];
					if ("avg-" == substr($key, 0, 4) || "avg_" == substr($key, 0, 4)) {
						$str = substr($key, strrpos($key, '_'));
						$str2 = substr($key, strrpos($key, '-'));

						$num = preg_replace("/[^0-9]/", '', $str);
						$num2 = preg_replace("/[^0-9]/", '', $str2);
						$nums[$num] = $num;

						if ($value > 0) {
							$arrsk2[$k][$num] = $value;
						}

						foreach ($nums as $n) {
							$arrsk[$k][$n] = $arrsk2[$k][$n] == null ? 0 : $arrsk2[$k][$n];
						}
					}
				}
			}
		}


		foreach ($arrsk as $vals) {
			$nbval[] = sizeof($vals);
			$nbvalues = max($nbval);

			$dataseriesforavgskill[] = array_values($vals);
		}
		}

		/**
		* view 
		*/
		print '</div><div class="fichetwothirdright">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';

		print  '<tr class="liste_titre">';
		print  '<td>' . $langs->trans("Cartographie") . ' - ' . $langs->trans("Moyenne des compétences") . '</td>';

		print '<td class="right">';
		if (!empty(array_filter($arr_skill)) && sizeof(array_filter($arr_skill)) > 0) {
			print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill)) . ' compétence(s) séléctionné(s)">' . sizeof($arr_skill) . '</span>';
		}
		
		print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgskill"></span></td>';
		print  '</tr>';

		if ($conf->use_javascript_ajax) {
			print  '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimgskill").click(function() {
							jQuery("#idfilterskill").toggle();
						});
					});
					</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject" id="idfilterskill">'; // hideobject is to start hidden
			print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
			print  '<input type="hidden" name="token" value="' . newToken() . '">';
			print  '<input type="hidden" name="action" value="refresh_skill">';
			print  '<input type="hidden" name="page_y" value="">';

			print  $form->selectDate($date_skill_start, 'date_skill_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_skill_end, 'date_skill_end', 0, 0, 0, '', 1, 0);

			// print '<div class="right">';
			// print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			// print '<span class="fas fa-chart-bars" name="bars" title="Affichage en barres"></span>';
			// print '&nbsp;&nbsp;';
			// print '<span class="fas fa-chart-line" name="lines" title="Affichage en courbes"></span>';
			// print '</div>';

			print '<br/>';
			print '<div>';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print $form->multiselectarray('arr_skill',  $arrskillall,  $arr_skill, '', '', '', '', '65%', '', '', 'Compétence');
			// print  ' &nbsp; ';
			print '</div>';
			
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
			print  '</form>';
			print  '</div>';

			// 	print  '</div>';
			print '</td>';
			print '</tr>';

			print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

			include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

			$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '360' : '360';
			$HEIGHT = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '300' : '300';
			$NBLEG = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? 2 : 1;

			$dolgraph = new DolGraph();
			$mesg = $dolgraph->isGraphKo();
			if (!$mesg && $action = 'refresh_js') {
				$dolgraph->SetData(array_values($dataseriesforavgskill));
				$dolgraph->SetDataColor(array_values($datacolors));
				$dolgraph->setLegend(array_values(array_unique(array_filter($labelskill))));

				if (!empty($arr_skill) || ($skill_fk_user != -1 && $skill_fk_user != '')) {
					$dolgraph->setShowLegend($NBLEG);
					// $dolgraph->setShowPercent(1);
					$dolgraph->SetHeight($HEIGHT);
					$dolgraph->SetWidth($WIDTH);
					// $dolgraph->SetType(array('lines'));
					$dolgraph->SetType(array('bars'));
				} else {
					$dolgraph->setShowLegend(2);
							$dolgraph->setShowPercent(2);
					$dolgraph->SetHeight('300');
					$dolgraph->SetWidth($WIDTH);
					$dolgraph->SetType(array('polar'));
				}


				$dolgraph->draw('idgraphavgskill');
				print $dolgraph->show($totalskill ? 0 : 1);

				print '</td></tr>';
			}
			print  '<tr class="liste_total">';
			print  '<td>' . $langs->trans("Total des compétences évalués") . '</td>';
			print  '<td class="right">' . $totalskill . '</td>';
			print  '</tr>';

			print  '</table>';
			print  '</div>';
			// print  '<br>';
		}
		if (empty($conf->use_javascript_ajax)) {
			$langs->load("errors");
			print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}
		print '</div></div>';
		print '<div class="fichecenter">';

		/**
		 * actions
		 */
		$nbonskills = $object->getSkillEvaluated($object->fk_user, $arr_val_skill, $date_val_start, $date_val_end, 'all_users', 'on_evaluation');
		$nbvalonskills = $object->getSkillEvaluated($object->fk_user, $arr_val_skill, $date_val_start, $date_val_end,  'validate_users', 'on_evaluation');
		$nboffskills = $object->getSkillEvaluated($object->fk_user, $arr_val_skill, $date_val_start, $date_val_end, 'all_users', 'off_evaluation');


		foreach ($nbvalonskills as $key => $val) {
			$nbValSkills[$val->label][$val->fk_user] += count($val->fk_user);
		}

		foreach ($nbonskills as $key => $val) {
			$nbOnSkills[$val->label][$val->fk_user] += count($val->fk_user);
		}

		foreach ($nboffskills as $key => $val) {
			$nbOffSkills[$val->label][$val->fk_user] += count($val->fk_user);
		}
		//   var_dump($nbOnSkills);
		if (!empty($nboffskills)) {
			foreach ($nboffskills as $key => $val) {
				$label = str_replace([" de ", " d'", " des ", " en ", " et ", " le ", " la ", " les ", " du "], '- ', $val->label);
				$words = explode(" ", $label);
				$acronym = "";

				foreach ($words as $w) {
					$acronym .= mb_substr($w, 0, 5);
					$acronym .= ". ";
					$labelvaljs[$val->label] =  ucfirst($acronym);
				}
				$labels = array("Nb comp. de l'emploi/collaborateur affécté", "Nb comp. de l'emploi/collaborateur évalué sur l'emploi", "Nb comp. par collaborateur < seuil requis dans l'emploi");
				$datacolors = array("rgb(60, 147, 183, 0.9)", "rgb(137, 86, 161, 0.9)", "rgb(250, 190, 80, 0.9)");
				// var_dump($val->label.'--'.array_sum($nbOffSkills[$val->label]));
				// var_dump($val->label.'_'.sizeof($nbOffSkills[$val->label]));
				// print '<a class="butAction" href="card.php?rowid='.$id.'&action=edit&token='.newToken().'".link("https://developer.mozilla.org/")>'.$langs->trans("Modify").'</a>'."\n";
				$nbSkills[$val->label] = array($labelvaljs[$val->label], array_sum($nbOffSkills[$val->label]), array_sum($nbOnSkills[$val->label]), array_sum($nbValSkills[$val->label]));
			}
		} else {
			foreach ($nbonskills as $key => $val) {
				$label = str_replace([" de ", " d'", " des ", " en ", " et ", " le ", " la ", " les ", " du "], '- ', $val->label);
				$words = explode(" ", $label);
				$acronym = "";

				foreach ($words as $w) {
					$acronym .= mb_substr($w, 0, 5);
					$acronym .= ". ";
					$labelvaljs[$val->label] =  ucfirst($acronym);
				}
				$labels = array("Nb compétences par collaborateur sur un poste évalué", "Nb compétences par collaborateur < seuil requis");
				$datacolors = array("rgb(137, 86, 161, 0.9)", "rgb(250, 190, 80, 0.9)");
				// var_dump($val->label.'--'.array_sum($nbOffSkills[$val->label]));
				// var_dump($val->label.'_'.sizeof($nbOffSkills[$val->label]));
				// print '<a class="butAction" href="card.php?rowid='.$id.'&action=edit&token='.newToken().'".link("https://developer.mozilla.org/")>'.$langs->trans("Modify").'</a>'."\n";
				$nbSkills[$val->label] = array($labelvaljs[$val->label], array_sum($nbOnSkills[$val->label]), array_sum($nbValSkills[$val->label]));
			}
		}


		/**
		* view 
		*/
		//  print '</div><div class="fichetwothirdright">';
		// print '</div></div>';
		// print '<div class="fichecenter">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';

		print  '<tr class="liste_titre">';
		print  '<td>' . $langs->trans("Statistics") . ' - ' . $langs->trans("Nombre de Compétence/Emploi/Evaluation") . '</td>';

		print '<td class="right">';
		if (!empty(array_filter($arr_val_skill)) && sizeof(array_filter($arr_val_skill)) > 0) {
			print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_val_skill)) . ' compétence(s) séléctionnée(s)">' . sizeof($arr_val_skill) . '</span>';
		}

		print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgvalskill"></span>';

		print ' &nbsp; ';

		if ($mode == 'off' || $mode == '') {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=on">';
			print '<span class="fas fa-ellipsis-v" title="Cliquer pour afficher toutes les compétences - avec possibilité de filtrer sur la période des emplois exercés"></span>';
			print '</a>';
		}
		if ($mode == 'on') {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=off">';
			print '<span class="fas fa-solid fa-banas fa-ellipsis-h" title="Cliquer pour afficher les dernières compétences relatives à des emplois en cours"></span>';
			print '</a>';
		}
		print ' &nbsp; ';
		print '</td>';
		print  '</tr>';

		if ($conf->use_javascript_ajax) {
			print  '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimgvalskill").click(function() {
							jQuery("#idfiltervalskill").toggle();
						});
					});
					</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject" id="idfiltervalskill">'; // hideobject is to start hidden
			print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
			print  '<input type="hidden" name="token" value="' . newToken() . '">';
			print  '<input type="hidden" name="action" value="refresh_val_skill">';
			print  '<input type="hidden" name="page_y" value="">';

			print  $form->selectDate($date_val_start, 'date_val_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_val_end, 'date_val_end', 0, 0, 0, '', 1, 0);


			print '<br/>';
			print '<div>';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print $form->multiselectarray('arr_val_skill',  $arrskill,  $arr_val_skill, '', '', '', '', '65%', '', '', 'Compétence');
			// print  ' &nbsp; ';
			print '</div>';
			
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
			print  '</form>';
			print  '</div>';

			// 	print  '</div>';
			print '</td>';
			print '</tr>';

			print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

			include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

			$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '1220' : '1220';
			$HEIGHT = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '500' : '500';
			$NBLEG = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? 2 : 1;

			$dolgraph = new DolGraph();
			$mesg = $dolgraph->isGraphKo();
			if (!$mesg) {
				$dolgraph->SetData(array_values($nbSkills));
				//  #FF0202
				$dolgraph->SetDataColor($datacolors);
				$dolgraph->setLegend($labels);


				//if (!empty($arr_val_skill) || ($object->fk_user != -1 && $object->fk_user != '')) {
					// $dolgraph->setShowLegend($NBLEG);
					// $dolgraph->setShowPercent(1);
				// 	$dolgraph->SetHeight($HEIGHT);
				// 	$dolgraph->SetWidth($WIDTH);
				// 	// $dolgraph->SetType(array('lines'));
				// 	$dolgraph->SetType(array('bars'));
				// } else {
					// $dolgraph->setShowLegend(2);
					$dolgraph->SetHeight('500');
					$dolgraph->SetWidth($WIDTH);
					$dolgraph->SetType(array('bars'));
				// }

				$dolgraph->draw('idgraphvalskill');
				print $dolgraph->show($totalskill ? 0 : 1);

				print '</td></tr>';
			}
			print  '<tr class="liste_total">';
			print  '<td>' . $langs->trans("Total des compétences évaluées") . '</td>';
			print  '<td class="right">' . $totalskill . '</td>';
			print  '</tr>';

			print  '</table>';
			print  '</div>';
			// print  '<br>';
		}
		if (empty($conf->use_javascript_ajax)) {
			$langs->load("errors");
			print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}

		print '</div>';
 		?>
		<style>

			@media(min-width: 992px) {
			.row-cols-lg-3>* {
				flex:0 0 auto;
				width:33.33333333%
			}
			.col-lg-3 {
				flex:0 0 auto;
				width:25%
			}
			.col-lg-9 {
				flex:0 0 auto;
				width:75%
			}
			.col-lg-10 {
				flex:0 0 auto;
				width:83.33333333%
			}
			.col-lg-12 {
				flex:0 0 auto;
				width:100%
			}
			}
			@media(min-width: 1200px) {
			.col-xl-3 {
				flex:0 0 auto;
				width:25%
			}
			.col-xl-9 {
				flex:0 0 auto;
				width:75%
			}
			}


			article .head-title,
			.h1 {
			margin:0 0 25px
			}
			article .head-title h1,
			article .head-title .h1 {
			margin-bottom:15px !important;
			margin-top:20px
			}

			@media(min-width: 992px) {
			article {
				padding:0 0 1rem 4rem
			}
			}
			.ft-c-gray {
			color:#a7b3c5
			}
			article .sumup {
			margin-top:2rem
			}

			.content-wrapper {
			padding-top: 1.6rem;
			}

			article p>a:hover,
			article p>strong>a:hover {
			text-decoration:underline
			}


			@media(min-width: 768px) {
			ol,
			.spaced-list-item {
				padding-left:40px;
				list-style:none
			}
			.follow-icons {
				text-align:right;
				position:absolute;
				right:0
			}
			}

			.tdwidgetstate{
				padding: 0px 0px 0px 0px!important;
			}

			.boxstats {
				width: 44.3%;
			}

			.trextrafieldseparator {
				float: left;
				display: contents;
			}

			tr.nohover .valuefield_card {
				background: aliceblue !important;
			} 

			.valuefield_card {
				position: absolute;
				text-align: center;
				background: aliceblue!important;
				z-index: 2;
				height: auto!important;
			}
			ul, li{
				list-style-type: none;
				padding: 0px;
				/* background: aliceblue; */
			}
			
			table.noborder.boxtable tr td {
				height: inherit;
			}

			.fa-minus-square{
				color:red;
			}

			.dis_card {
				/* content: ""; */
  /* position: absolute; */
				border-style: solid!important;
				border-width: 0px 6px 5px 6px!important;
				border-color: transparent transparent black transparent!important;
			}
						

		</style>
		<?php
		// print '<strong>Parcours professionnel</strong>';
		// print '<br><br>';
	
	//test option
		print '<div class="col-lg-9">';
		print '<article>';
		
		print '<div class="content-wrapper">';
		print '<h2 class="anchor" id="toc-1">';
		print 'Parcours Optim';
		print '</h2>';
		if(!empty($object->getLastUserJob($object->fk_user))) {
			foreach($object->getLastUserJob($object->fk_user) as $key => $values) {
				foreach($values as $date_start => $vals) {
					foreach($vals as $date_end => $value) {
						$end_date = $date_end != '' ? ' à '. dol_print_date($date_end, '%B %Y') : ' à aujourd\'hui ';
						$value = explode('_', $value);
						$label = $value[0];
						$description = $value[1];
						print '<h2 class="anchor" id="toc-2" style="color: #2980b9;font-size: 1.2em;">'.dol_print_date($date_start, '%B %Y').''.$end_date.''.' - OPTIM Industries</h2>';
						// print '<p>&nbsp;:</p>';
						print '<ul class="spaced-list-item">';
			
						print '<li><i class="fa fa-arrow-circle-right" style="color:#198754;"></i>';
						print  '<a href="'.DOL_URL_ROOT.'/custom/gpeccustom/position_card.php?id='.$value[2].'&save_lastsearch_values=1"><strong>';
						print  $label;
						print '</strong></a></li>';
						print '<br>';
						print  nl2br($description);
						print '</ul>';
					}
				}
			}
		}else{
			print $langs->trans("NoRecordFound");
		}
		
		print '</div>';

		print '</article>';
		print '</div>';
	
		print '<div class="col-lg-9">';
		print '<article>';
		
		print '<div class="content-wrapper">';
		print '<h2 class="anchor" id="toc-1">';
		print 'Parcours antérieur';
		print '</h2>';
		if($userField->array_options['options_dernierdiplomelepluseleve'] != null || trim($userField->array_options['options_dernierdiplomelepluseleve']) != '') {
			print $userField->array_options['options_dernierdiplomelepluseleve'];
		}else{
			print $langs->trans("NoRecordFound");
		}
		print '</div>';

		print '</article>';
		print '</div>';
	}	
	

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'cvtec';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->gpeccustom->dir_output;
	$trackid = 'cvtec'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}


// End of page
llxFooter();
$db->close();
