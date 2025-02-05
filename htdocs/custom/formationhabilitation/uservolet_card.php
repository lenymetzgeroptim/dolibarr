<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 METZGER Leny <l.metzger@optim-industries.fr>
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
 *    \file       uservolet_card.php
 *    \ingroup    formationhabilitation
 *    \brief      Page to create/edit/view uservolet
 */


// General defined Options
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');					// Force use of CSRF protection with tokens even for GET
//if (! defined('MAIN_AUTHENTICATION_MODE')) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('MAIN_LANG_DEFAULT'))        define('MAIN_LANG_DEFAULT', 'auto');					// Force LANG (language) to a particular value
//if (! defined('MAIN_SECURITY_FORCECSP'))   define('MAIN_SECURITY_FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');					// Disable browser notification
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOLOGIN'))                  define('NOLOGIN', '1');						// Do not use login - if this page is public (can be called outside logged session). This includes the NOIPCHECK too.
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  		// Do not load ajax.lib.php library
//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// Do not load html.form.class.php
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// Do not load and show top and left menu
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOSESSION'))                define('NOSESSION', '1');						// On CLI mode, no need to use web sessions
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
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
dol_include_once('/formationhabilitation/class/uservolet.class.php');
dol_include_once('/formationhabilitation/lib/formationhabilitation_uservolet.lib.php');
dol_include_once('/formationhabilitation/class/habilitation.class.php');
dol_include_once('/formationhabilitation/class/formation.class.php');
dol_include_once('/formationhabilitation/class/autorisation.class.php');
dol_include_once('/formationhabilitation/class/userhabilitation.class.php');
dol_include_once('/formationhabilitation/class/userformation.class.php');
dol_include_once('/formationhabilitation/class/userautorisation.class.php');
dol_include_once('/formationhabilitation/class/volet.class.php');

// Load translation files required by the page
$langs->loadLangs(array("formationhabilitation@formationhabilitation", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOST('lineid', 'int');
$addlinkid   = GETPOST('addlinkid', 'int');
$dellinkid   = GETPOST('dellinkid', 'int');

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
$object = new UserVolet($db);
$extrafields = new ExtraFields($db);
$usergroup = new UserGroup($db);
$diroutputmassaction = $conf->formationhabilitation->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); // Note that conf->hooks_modules contains array


// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
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
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$volet = new Volet($db);
$volet->fetch($object->fk_volet); 

if($volet->typevolet == 1) {
	$objectline = new UserFormation($db);
	$addlink = 'formation';
}
elseif($volet->typevolet == 2) {
	$objectline = new UserHabilitation($db);
	$addlink = 'habilitation';
}
elseif($volet->typevolet == 3) {
	$objectline = new UserAutorisation($db);
	$addlink = 'autorisation';
}

$objectparentline = new UserVolet($db);

// There is several ways to check permission.
$permissiontoread = $user->hasRight('formationhabilitation', 'uservolet', 'readall') || ($object->fk_user == $user->id && $user->hasRight('formationhabilitation', 'uservolet', 'read'));
$permissiontoadd = $user->hasRight('formationhabilitation', 'uservolet', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('formationhabilitation', 'uservolet', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_VALIDATION0);
$permissiontoaddline = $user->rights->formationhabilitation->userformation->write;
$permissiontoreadcost = $user->rights->formationhabilitation->formation->readcout;
$permissiontoreadline = $permissiontoread;

if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET1 > 0) {
	$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET1);
	$permissiontovalidate1 = array_key_exists($usergroup->id, $usergroup->listGroupsForUser($user->id, false));
}

if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET2 > 0) {
	$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET2);
	$permissiontovalidate2 = array_key_exists($usergroup->id, $usergroup->listGroupsForUser($user->id, false));
}

if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET3 > 0) {
	$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET3);
	$permissiontovalidate3 = array_key_exists($usergroup->id, $usergroup->listGroupsForUser($user->id, false));
}

if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET4 > 0) {
	$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET4);
	$permissiontovalidate4 = array_key_exists($usergroup->id, $usergroup->listGroupsForUser($user->id, false));
}

$variableName = 'FORMTIONHABILITATION_APPROBATIONVOLET'.$object->fk_volet;
$approbationRequire = $conf->global->$variableName;
$approbationRequireArray = explode(',', $conf->global->$variableName);

if($object->status == $object::STATUS_VALIDATION0) {
	$permissiontovalidate = $permissiontovalidate1;
}
elseif($object->status == $object::STATUS_VALIDATION1) {
	$permissiontovalidate = $permissiontovalidate2;
}
elseif($object->status == $object::STATUS_VALIDATION2) {
	$permissiontovalidate = $permissiontovalidate3;
}
elseif($object->status == $object::STATUS_VALIDATION3) {
	$permissiontovalidate = $permissiontovalidate4;
}
elseif($object->status == $object::STATUS_VALIDATION_WITHOUT_USER) {
	$permissiontovalidate = $user->id == $object->fk_user;
}

if($object->status < $object::STATUS_VALIDATION1 && strpos($approbationRequire, '2') !== false) { // Il y a l'approbation 2
	$next_status = $object::STATUS_VALIDATION1;
}
elseif($object->status < $object::STATUS_VALIDATION2 && strpos($approbationRequire, '3') !== false) { // Il y a l'approbation 3
	$next_status = $object::STATUS_VALIDATION2;
}
elseif($object->status < $object::STATUS_VALIDATION3 && strpos($approbationRequire, '4') !== false) { // Il y a l'approbation 4
	$next_status = $object::STATUS_VALIDATION3;
}
elseif($object->status < $object::STATUS_VALIDATION_WITHOUT_USER && strpos($approbationRequire, '5') !== false) { // Il y a l'approbation du collaborateur
	$next_status = $object::STATUS_VALIDATION_WITHOUT_USER;
}
elseif($object->status < $object::STATUS_VALIDATED) {
	$next_status = $object::STATUS_VALIDATED;
}

$validation_before = 1;
if($object->status == $object::STATUS_VALIDATION0) {
	$validation_before = 0;
}
elseif($object->status == $object::STATUS_VALIDATION1 && strpos($approbationRequire, '1') === false) { // Il n'y a pas eu l'approbation 1
	$validation_before = 0;
}
elseif($object->status == $object::STATUS_VALIDATION2 && strpos($approbationRequire, '1') === false && strpos($approbationRequire, '2') === false) { // Il n'y a pas eu l'approbation 1, ni 2
	$validation_before = 0;
}
elseif($object->status == $object::STATUS_VALIDATION3 && strpos($approbationRequire, '1') === false && strpos($approbationRequire, '2') === false && strpos($approbationRequire, '3') === false) { // Il n'y a pas eu l'approbation 1, ni 2, ni 3
	$validation_before = 0;
}

$permissiontolinkandunlink = $object->status < $object::STATUS_VALIDATION_WITHOUT_USER && $permissiontovalidate && !$validation_before;
$permissiontorefuse = $permissiontovalidate && !$permissiontolinkandunlink;


$upload_dir = $conf->formationhabilitation->multidir_output[isset($object->entity) ? $object->entity : 1].'/uservolet';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_VALIDATION0) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("formationhabilitation")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}

include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline_init.tpl.php';

// // Definition of array of fields for columns
// $arrayfields = array();
// foreach ($objectline->fields as $key => $val) {
// 	// If $val['visible']==0, then we never show the field
// 	if (!empty($val['visible'])) {
// 		$visible = (int) dol_eval($val['visible'], 1);
// 		$arrayfields['t.'.$key] = array(
// 			'label'=>$val['label'],
// 			'checked'=>(($visible < 0) ? 0 : 1),
// 			'enabled'=>(abs($visible) != 3 && dol_eval($val['enabled'], 1)),
// 			'position'=>$val['position'],
// 			'help'=> isset($val['help']) ? $val['help'] : ''
// 		);
// 	}
// }
// // Extra fields
// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

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

	$backurlforlist = dol_buildpath('/formationhabilitation/uservolet_list.php', 1);

	if($action != 'updateline' && $action != 'updatedatefinvalidite' && $action != 'updatecoutpedagogique' && $action != 'updatecoutmobilisation'){
		if (empty($backtopage) || ($cancel && empty($id))) {
			if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
				if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
					$backtopage = $backurlforlist;
				} else {
					$backtopage = dol_buildpath('/formationhabilitation/uservolet_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
				}
			}
		}
	}

	$triggermodname = 'FORMATIONHABILITATION_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

	if($action == 'update') {
		$object->oldcopy = clone $object;

		if($object->status == $object::STATUS_VALIDATED && GETPOST('status') != $object::STATUS_VALIDATED) {
			$object->date_valid_intervenant = '';
			$object->fk_user_valid_intervenant = '';
			$object->fk_action_valid_intervenant = '';
		}
		if($object->status >= $object::STATUS_VALIDATION3 && GETPOST('status') < $object::STATUS_VALIDATION3) {
			$object->date_valid_employeur = '';
			$object->fk_user_valid_employeur = '';
			$object->fk_action_valid_employeur = '';
		}
	}

	if($action == 'edit' && $object->status == $object::STATUS_VALIDATED) {
		$action = '';
	}
	
	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_uservolet.inc.php';
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	//include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	// include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if($volet->typevolet == 1) {
		if(GETPOST('fk_formation') > 0) {
			$formation_static = new Formation($db);
			$formation_static->fetch(GETPOST('fk_formation'));
		}
	
		if(GETPOST('fk_user') > 0) {
			$user_static = new User($db);
			$user_static->fetch(GETPOST('fk_user'));
		}
	
		include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userformation.inc.php';
	}
	elseif($volet->typevolet == 2) {
		if(GETPOST('fk_habilitation') > 0) {
			$habilitation_static = new Habilitation($db);
			$habilitation_static->fetch(GETPOST('fk_habilitation'));
		}
	
		if(GETPOST('fk_user') > 0) {
			$user_static = new User($db);
			$user_static->fetch(GETPOST('fk_user'));
		}

		include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userhabilitation.inc.php';
	}
	elseif($volet->typevolet == 3) {
		if(GETPOST('fk_autorisation') > 0) {
			$autorisation_static = new Autorisation($db);
			$autorisation_static->fetch(GETPOST('fk_autorisation'));
		}
	
		if(GETPOST('fk_user') > 0) {
			$user_static = new User($db);
			$user_static->fetch(GETPOST('fk_user'));
		}
	
		include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userautorisation.inc.php';
	}

	// if ($action == 'set_thirdparty' && $permissiontoadd) {
	// 	$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	// }
	// if ($action == 'classin' && $permissiontoadd) {
	// 	$object->setProject(GETPOST('projectid', 'int'));
	// }

	// // Actions to send emails
	// $triggersendname = 'FORMATIONHABILITATION_MYOBJECT_SENTBYMAIL';
	// $autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	// $trackid = 'uservolet'.$object->id;
	// include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

unset($arrayfields['t.formateur']);
unset($objectline->fields['fk_user']);
unset($arrayfields['t.fk_user']);
if(!$permissiontoreadcost) {
    unset($objectline->fields['cout_pedagogique']);
    unset($objectline->fields['cout_mobilisation']);
    unset($objectline->fields['cout_annexe']);
    unset($objectline->fields['cout_total']);
    unset($arrayfields['t.cout_pedagogique']);
    unset($arrayfields['t.cout_mobilisation']);
    unset($arrayfields['t.cout_annexe']);
    unset($arrayfields['t.cout_total']);
}
if($object->fk_volet != 7) {
	unset($object->fields['qualif_pro']);
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("UserVolet")." - ".$langs->trans('Card');
//$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewObject", $langs->transnoentitiesnoconv("UserVolet"));
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-formationhabilitation page-card');

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

	print load_fiche_titre($title, '', 'object_'.$object->picto);

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
	print load_fiche_titre($langs->trans("UserVolet"), '', 'object_'.$object->picto);

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
	unset($object->fields['fk_user']);
	unset($object->fields['fk_volet']);
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
	$head = uservoletPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("UserVolet"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete (using preloaded confirm popup)
	if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteUserVolet'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
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

	// Confirmation to validate1
	if ($action == 'validate1') {
		$formquestion = array();
		if($volet->typevolet == 2 || $volet->typevolet == 3) {
			$formquestion[] = array('label'=>$langs->trans('DateDebutVolet') ,'type'=>'date', 'name'=>'date_debut_volet', 'value'=>$object->datedebutvolet);
		}
		$formquestion[] = array('label'=>$langs->trans('ClotureOtherVolet') ,'type'=>'checkbox', 'name'=>'close_volet', 'value'=>$object->cloture);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateUserVolet'), $langs->trans('ConfirmValidateUserVolet'), 'confirm_validate1', $formquestion, 0, 1);
	}

	if ($action == 'refuse') {
		$formquestion = array(
			array('label'=>$langs->trans('MotifRefus') ,'type'=>'textarea', 'name'=>'motif_refus', 'value'=>'')
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('RefuseUserVolet'), $langs->trans('ConfirmRefuseUserVolet'), 'confirm_refuse', $formquestion, 0, 1);
	}

	// Confirmation to validate4
	// if ($action == 'validate4') {
	// 	$listUserVolet = $object->getActiveUserVolet(0);
	// 	$formquestion = array();
	// 	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateUserVolet'), (sizeof($listUserVolet) > 0 ? $langs->trans('ConfirmValidateUserVoletWithClose') : $langs->trans('ConfirmValidateUserVolet')), 'confirm_validate4', $formquestion, 0, 1);
	// }

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$text = $langs->trans('ConfirmActionUserVolet', $object->ref);
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
	$linkback = '<a href="'.dol_buildpath('/formationhabilitation/uservolet_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

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


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak='datedebutvolet';	// We change column just before this field
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
			// if (empty($user->socid)) {
			// 	print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			// }

			// Back to draft
			// if ($object->status == $object::STATUS_VALIDATED) {
			// 	print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// }

			// Modify
			if ($object->status != $object::STATUS_VALIDATED && $permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
			}

			if (($permissiontovalidate1 && $object->status == $object::STATUS_VALIDATION0) || ($permissiontovalidate2 && $object->status == $object::STATUS_VALIDATION1) || ($permissiontovalidate3 && $object->status == $object::STATUS_VALIDATION2) || $permissiontovalidate4 && ($object->status == $object::STATUS_VALIDATION3) || $object->status == $object::STATUS_VALIDATION_WITHOUT_USER || $object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction($langs->trans('GeneratePDF'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_genererPdf&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			// Validate n°1
			// if ($object->status == $object::STATUS_VALIDATION0) {
			// 	$variableName = 'FORMTIONHABILITATION_APPROBATIONVOLET'.$object->fk_volet;
			// 	$approbationRequire = $conf->global->$variableName;
			// 	$approbationRequireArray = explode(',', $conf->global->$variableName);
			// 	if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					
			// 		if(sizeof($approbationRequireArray) == 1) {
			// 			if(strpos($approbationRequire, '5') !== false) {
			// 				print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $user->id == $object->fk_user);
			// 			}
			// 			else {
			// 				$permissionName = 'permissiontovalidate'.($approbationRequireArray[0]+1);
			// 				print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate4&token='.newToken(), '', $$permissionName);		
			// 			}
			// 		}
			// 		elseif(strpos($approbationRequire, '1') !== false) { // Il y a l'approbation 2
			// 			print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate1&token='.newToken(), '', $permissiontovalidate1);
			// 		}
			// 		elseif(strpos($approbationRequire, '2') !== false) { // Il y a l'approbation 3
			// 			print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate2&confirm=yes&token='.newToken(), '', $permissiontovalidate2);
			// 		}
			// 		elseif(strpos($approbationRequire, '3') !== false) { // Il y a l'approbation 4
			// 			print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate3&confirm=yes&token='.newToken(), '', $permissiontovalidate3);
			// 		}
			// 		elseif(strpos($approbationRequire, '4') !== false) {
			// 			print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate4&token='.newToken(), '', $permissiontovalidate4);
			// 		}
			// 	} else {
			// 		$langs->load("errors");
			// 		print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
			// 	}
			// }

			// Validate n°1
			if ($object->status == $object::STATUS_VALIDATION0) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate1&token='.newToken(), '', $permissiontovalidate1);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Validate n°2
			if ($object->status == $object::STATUS_VALIDATION1) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontovalidate2);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Validate n°3
			if ($object->status == $object::STATUS_VALIDATION2) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontovalidate3);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Validate n°4
			if ($object->status == $object::STATUS_VALIDATION3) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontovalidate4);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Validate User
			if ($object->status == $object::STATUS_VALIDATION_WITHOUT_USER) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $user->id == $object->fk_user);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}

			if ($object->status < $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('Refuse'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=refuse&token='.newToken(), '', $permissiontorefuse);
			}

			/*
			// Disable / Enable
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

			// Delete (with preloaded confirm popup)
			$deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
			$buttonId = 'action-delete-no-ajax';
			if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can use preloaded confirm if not jmobile
				$deleteUrl = '';
				$buttonId = 'action-delete';
			}
			$params = array();
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
		}
		print '</div>'."\n";
	}


	// // Select mail models is same action as presend
	// if (GETPOST('modelselected')) {
	// 	$action = 'presend';
	// }

	// if ($action != 'presend') {
	// 	print '<div class="fichecenter"><div class="fichehalfleft">';
	// 	print '<a name="builddoc"></a>'; // ancre

	// 	$includedocgeneration = 1;

	// 	// Documents
	// 	if ($includedocgeneration) {
	// 		$objref = dol_sanitizeFileName($object->ref);
	// 		$relativepath = $objref.'/'.$objref.'.pdf';
	// 		$filedir = $conf->formationhabilitation->dir_output.'/'.$object->element.'/'.$objref;
	// 		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	// 		$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
	// 		$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
	// 		print $formfile->showdocuments('formationhabilitation:UserVolet', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
	// 	}

	// 	// Show links to link elements
	// 	$linktoelem = $form->showLinkToObjectBlock($object, null, array('uservolet'));
	// 	$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


	// 	print '</div><div class="fichehalfright">';

	// 	$MAXEVENT = 10;

	// 	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/formationhabilitation/uservolet_agenda.php', 1).'?id='.$object->id);

	// 	// List of actions on element
	// 	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	// 	$formactions = new FormActions($db);
	// 	$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

	// 	print '</div></div>';
	// }

	// //Select mail models is same action as presend
	// if (GETPOST('modelselected')) {
	// 	$action = 'presend';
	// }

	// // Presend form
	// $modelmail = 'uservolet';
	// $defaulttopic = 'InformationMessage';
	// $diroutput = $conf->formationhabilitation->dir_output;
	// $trackid = 'uservolet'.$object->id;

	// include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

		/*
	 * Lines
	 */

	 if (!empty($object->table_element_line)) {
		if($objectline->element == 'userformation'){
			$objectclass = 'UserFormation';
			$objectlabel = 'UserFormation';
		}
		elseif($objectline->element == 'userhabilitation'){
			$objectclass = 'UserHabilitation';
			$objectlabel = 'UserHabilitation';
		}
		elseif($objectline->element == 'userautorisation'){
			$objectclass = 'UserAutorisation';
			$objectlabel = 'UserAutorisation';
		}

		// Show object lines linked
		$result = $object->getLinkedLinesArray();
		if($permissiontolinkandunlink) {
			$enableunlink = 1; 
		}
		else {
			$enableunlink = 0; 
		}
		$enablelink = 0; 
		$disableedit = 1;
		$disableremove = 1;

		print '	<form name="addline" id="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">';
    	print '<input type="hidden" id="fk_user" name="fk_user" value="' . $object->fk_user.'">';
		if($action == 'editline') {
			print '<input type="hidden" name="action" value="updateline">';
		}
		elseif($action == 'edit_domaineapplication') {
			print '<input type="hidden" name="action" value="updatedomaineapplication">';
		}
		else {
			print '<input type="hidden" name="action" value="addline">';
		}

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		$title = $langs->trans('ListOfsLinkedObject', $langs->transnoentitiesnoconv($objectlabel.'UserVolet'));
		print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, sizeof($objectparentline->lines), $nbtotalofrecords, $objectline->picto, 0, '', '', 0, 0, 0, 1);
	

		print '<div class="">';
		//if (!empty($object->lines) || ($object->status == $object::STATUS_VALIDATION0 && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		//}

		//if (!empty($object->lines)) {
			$object->printObjectLinkedLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/custom/formationhabilitation/core/tpl');
		//}

		// Form to add new line
		// if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
		// 	if ($action != 'editline') {
		// 		// Add products/services form

		// 		$parameters = array();
		// 		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		// 		if ($reshook < 0) {
		// 			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		// 		}
		// 		if (empty($reshook)) {
		// 			$object->formAddObjectLine(1, $mysoc, $soc);
		// 		}
		// 	}
		// }

		if (sizeof($object->lines) == 0) {
			$colspan = 0;
			foreach ($arrayfields as $key => $val) {
				if (!empty($val['checked'])) {
					$colspan++;
				}
			}

			print '<tr>';
			print '<td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td>';
			if($permissiontolinkandunlink) {
				print '<td class="linecollink center width20"></div>';
			}
			// print '<td class="linecoledit center width20"></div>';
			// print '<td class="linecoldelete center width20"></div>';
			print '</tr>';
		}

		//if (!empty($object->lines) || ($object->status == $object::STATUS_VALIDATION0 && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		//}
		print '</div>';

		print "</form>\n";



		// Show object lines no linked
		$result = $object->getNoLinkedLinesArray();
		if($permissiontolinkandunlink) {
			$enablelink = 1; 
		}
		else {
			$enablelink = 0; 
		}
		$enableunlink = 0; 
		$disableedit = 1;
		$disableremove = 1;
		
		print '	<form name="addline" id="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';
    	print '<input type="hidden" id="fk_user" name="fk_user" value="' . $object->fk_user.'">';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		$title = $langs->trans('ListOfsUnlinkedObject', $langs->transnoentitiesnoconv($objectlabel.'UserVolet'));
		print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, sizeof($objectparentline->lines), $nbtotalofrecords, $objectline->picto, 0, '', '', 0, 0, 0, 1);
	

		print '<div class="">';
		//if (!empty($object->lines) || ($object->status == $object::STATUS_VALIDATION0 && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		//}

		//if (!empty($object->lines)) {
			$object->printObjectLinkedLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/custom/formationhabilitation/core/tpl');
		//}

		// Form to add new line
		// if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
		// 	if ($action != 'editline') {
		// 		// Add products/services form

		// 		$parameters = array();
		// 		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		// 		if ($reshook < 0) {
		// 			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		// 		}
		// 		if (empty($reshook)) {
		// 			$object->formAddObjectLine(1, $mysoc, $soc);
		// 		}
		// 	}
		// }

		if (sizeof($object->lines) == 0) {
			// foreach ($arrayfields as $key => $val) {
			// 	if (!empty($val['checked'])) {
			// 		$colspan++;
			// 	}
			// }

			print '<tr>';
			print '<td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td>';
			if($permissiontolinkandunlink) {
				print '<td class="linecollink center width20"></div>';
			}
			// print '<td class="linecoledit center width20"></div>';
			// print '<td class="linecoldelete center width20"></div>';
			print '</tr>';
		}

		//if (!empty($object->lines) || ($object->status == $object::STATUS_VALIDATION0 && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		//}
		print '</div>';

		print "</form>\n";
	}
}

// End of page
llxFooter();
$db->close();
