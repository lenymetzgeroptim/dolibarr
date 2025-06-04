<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       remonteessse_card.php
 *		\ingroup    remonteessse
 *		\brief      Page to create/edit/view remonteessse
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
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
dol_include_once('/remonteessse/class/remonteessse.class.php');
dol_include_once('/remonteessse/lib/remonteessse_remonteessse.lib.php');
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

// Load translation files required by the page
$langs->loadLangs(array("remonteessse@remonteessse", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'remonteesssecard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Remonteessse($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->remonteessse->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('remonteesssecard', 'globalcard')); // Note that conf->hooks_modules contains array

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

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$see_all_user = 0;
$user_group = New UserGroup($db);
$user_group->fetch('', 'Q3SE');
$liste_user = $user_group->listUsersForGroup('', 1);
$user_group->fetch('', 'Direction');
$liste_user1 = $user_group->listUsersForGroup('', 1);
$user_group->fetch('', 'Responsable Affaires');
$liste_user2 = $user_group->listUsersForGroup('', 1);
if(in_array($user->id, $liste_user) || in_array($user->id, $liste_user1) || in_array($user->id, $liste_user2)){
	$see_all_user = 1;
}
// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 1;
if ($enablepermissioncheck) {
	$permissionpriseencompte = $user->rights->remonteessse->remonteessse->prisencompte;
	$permissiontraitement = $permissionpriseencompte;
	$permissiontocloture = $permissionpriseencompte;
	$permissiontoread = $user->rights->remonteessse->remonteessse->read_all || $object->fk_user == $user->id;
	$permissiontoadd = $user->rights->remonteessse->remonteessse->read; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->rights->remonteessse->remonteessse->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT && $object->fk_user == $user->id);
	$permissionnote = $user->rights->remonteessse->remonteessse->read; // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->rights->remonteessse->remonteessse->read; // Used by the include of actions_dellink.inc.php
	if($object->status == $object::STATUS_DRAFT){
		$permissiontomodify = $user->id == $object->fk_user || $user->admin;
	}
	else {
		$permissiontomodify = $permissionpriseencompte;
	}
	$permissiontovalidate = $user->id == $object->fk_user || $user->admin || $see_all_user; 
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->remonteessse->multidir_output[isset($object->entity) ? $object->entity : 1].'/remonteessse';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->remonteessse->enabled)) accessforbidden();
if (!$permissiontoread && $id > 0) accessforbidden();

if(!$see_all_user){
	$object->fields['fk_user']['type'] = 'integer:User:user/class/user.class.php:0:(rowid:IN:'.$user->id;
	if(!empty($object->fk_user)) {
		$object->fields['fk_user']['type'] .= ', '.$object->fk_user;
	}
	$object->fields['fk_user']['type'] .= ')';
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

	$backurlforlist = dol_buildpath('/remonteessse/remonteessse_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/remonteessse/remonteessse_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'REMONTEESSSE_REMONTEESSSE_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	if($action == "update" && !empty($permissiontomodify) && ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_CLOTURE) && !$cancel){
		foreach ($object->fields as $key => $val) {
			if($key != "genre" && $key != "type" && $key != "mise_enplace" && $key != "impact" && $key != "commentaire" && $key != "numero_action"){
				continue;
			}
			if($key == 'commentaire' || $key == 'numero_action'){
				if(($key == 'commentaire' && GETPOST('mise_enplace', 'alphanohtml') == 2 && GETPOST($key, 'alphanohtml') == '') || ($key == 'numero_action' && GETPOST('mise_enplace', 'alphanohtml') == 1 && GETPOST($key, 'alphanohtml') == '')){
					$error++;
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
				}
			}
			elseif (GETPOST($key, 'alphanohtml') == '' || GETPOST($key, 'alphanohtml') == 0) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
			}
		}
	}
	if($action == "update" && !empty($permissiontomodify) && ($object->status == $object::STATUS_PRISENCOMPTE || $object->status == $object::STATUS_CLOTURE) && !$cancel){
		if(empty(GETPOST('detail_traitement', 'alphanohtml'))){
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('DetailTraitement')), null, 'errors');
		}
		if(empty(GETPOST('date_traitement', 'alphanohtml'))){
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('DateTraitement')), null, 'errors');
		}
	}
	// Action validate object
	if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontovalidate) {
		$result = $object->validate($user);
		if ($result >= 0) {
			// Define output language
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}
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
	if ($action == 'confirm_priseencompte' && $confirm == 'yes' && $permissionpriseencompte) {
		$result = $object->priseencompte($user);
		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}
	if ($action == 'confirm_priseencompte_annulation' && $confirm == 'yes' && $permissionpriseencompte) {
		$result = $object->priseencompte_annulation($user);
		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}
	if ($action == 'confirm_cloture' && $confirm == 'yes' && $permissiontocloture) {
		if($object->fields['antenne']['arrayofkeyval'][$object->antenne] == 'VDR-Nord'){
			$key = 'REMONTEES_SSE_VDRNORD';
			$val_const = $conf->global->REMONTEES_SSE_VDRNORD.'- '.$object->libelle.'<br/>';
		}
		elseif($object->fields['antenne']['arrayofkeyval'][$object->antenne] == 'Grand-Ouest'){
			$key = 'REMONTEES_SSE_GRANDOUEST';
			$val_const = $conf->global->REMONTEES_SSE_GRANDOUEST.'- '.$object->libelle.'<br/>';
		}
		elseif($object->fields['antenne']['arrayofkeyval'][$object->antenne] == 'Sud-Est'){
			$key = 'REMONTEES_SSE_SUDEST';
			$val_const = $conf->global->REMONTEES_SSE_SUDEST.'- '.$object->libelle.'<br/>';
		}
		$result = dolibarr_set_const($db, $key, $val_const, 'chaine', 0, '', $conf->entity);
		if ($result < 0) {
			$error++;
		}

		if(!$error){
			$result = $object->cloture($user);
		}

		if ($result >= 0) {
			//header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			//exit;
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}
	// Actions to send emails
	$triggersendname = 'REMONTEESSSE_REMONTEESSSE_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_REMONTEESSSE_TO';
	$trackid = 'remonteessse'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("RemonteesSse");
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
		accessforbidden($langs->trans('NotEnoughPermissions'), 0, 1);
		exit;
	}

	print load_fiche_titre($langs->trans("NewObject2", $langs->transnoentitiesnoconv("RemonteesSse")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";
	unset($object->fields['type']);
	unset($object->fields['genre']);
	unset($object->fields['mise_enplace']);
	unset($object->fields['numero_action']);
	unset($object->fields['commentaire']);
	unset($object->fields['impact']);
	unset($object->fields['date_traitement']);
	unset($object->fields['detail_traitement']);
	// Common attributes
	include DOL_DOCUMENT_ROOT.'/custom/remonteessse/core/tpl/commonfields_add.tpl.php';

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
	print load_fiche_titre($langs->trans("RemonteesSse"), '', 'object_'.$object->picto);

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

	if($object->status == $object::STATUS_DRAFT){
		unset($object->fields['type']);
		unset($object->fields['genre']);
		unset($object->fields['mise_enplace']);
		unset($object->fields['numero_action']);
		unset($object->fields['commentaire']);
		unset($object->fields['impact']);
		unset($object->fields['date_traitement']);
		unset($object->fields['detail_traitement']);
	}
	elseif($object->status == $object::STATUS_VALIDATED){
		/*unset($object->fields['libelle']);
		unset($object->fields['date_remontee']);
		unset($object->fields['fk_user']);
		unset($object->fields['fk_project']);
		unset($object->fields['antenne']);
		unset($object->fields['site']);
		unset($object->fields['lieu']);
		unset($object->fields['description']);
		unset($object->fields['prop_amelioration']);
		unset($object->fields['date_traitement']);
		unset($object->fields['detail_traitement']);*/
		$object->fields['type']['notnull'] = 1;
		$object->fields['genre']['notnull'] = 1;
		$object->fields['mise_enplace']['notnull'] = 1;
		$object->fields['impact']['notnull'] = 1;
		$object->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php:0:fk_statut=1';
	}
	elseif($object->status == $object::STATUS_PRISENCOMPTE){
		/*unset($object->fields['libelle']);
		unset($object->fields['date_remontee']);
		unset($object->fields['fk_user']);
		unset($object->fields['fk_project']);
		unset($object->fields['antenne']);
		unset($object->fields['site']);
		unset($object->fields['lieu']);
		unset($object->fields['description']);
		unset($object->fields['prop_amelioration']);
		unset($object->fields['type']);
		unset($object->fields['genre']);
		unset($object->fields['mise_enplace']);
		unset($object->fields['numero_action']);
		unset($object->fields['commentaire']);
		unset($object->fields['impact']);*/
		$object->fields['date_traitement']['notnull'] = 1;
		$object->fields['detail_traitement']['notnull'] = 1;
		$object->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php:0:fk_statut=1';
	}
	elseif($object->status == $object::STATUS_CLOTURE){
		$object->fields['type']['notnull'] = 1;
		$object->fields['genre']['notnull'] = 1;
		$object->fields['mise_enplace']['notnull'] = 1;
		$object->fields['impact']['notnull'] = 1;
		$object->fields['date_traitement']['notnull'] = 1;
		$object->fields['detail_traitement']['notnull'] = 1;
		$object->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php:0:fk_statut=1';
	}
	// Common attributes
	include DOL_DOCUMENT_ROOT.'/custom/remonteessse/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = remonteesssePrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("RemonteesSse"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteRemonteesSse'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation validate (collaborateur)
	if ($action == 'validate') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateRemonteesSse'), $langs->trans('ConfirmValidateRemonteesSse'), 'confirm_validate', '', 0, 1);
	}
	// Confirmation prise en compte (QSE)
	if ($action == 'priseencompte') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('PriseEnCompteRemonteesSse'), $langs->trans('ConfirmPriseEnCompteRemonteesSse'), 'confirm_priseencompte', '', 0, 1);
	}
	// Confirmation annulation (QSE)
	if ($action == 'priseencompte_annulation') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CancelRemonteesSse'), $langs->trans('ConfirmCancelRemonteesSse'), 'confirm_priseencompte_annulation', '', 0, 1);
	}
	// Confirmation clotûre (QSE)
	if ($action == 'cloture' && !empty($object->date_traitement) && !empty($object->detail_traitement)) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ClotureRemonteesSse'), $langs->trans('ConfirmClotureRemonteesSse'), 'confirm_cloture', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx') {
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
	$linkback = '<a href="'.dol_buildpath('/remonteessse/remonteessse_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->projet->enabled)) {
	 $langs->load("projects");
	 $morehtmlref .= '<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd) {
	 //if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
	 $morehtmlref .= ' : ';
	 if ($action == 'classify') {
	 //$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref .= '<input type="hidden" name="action" value="classin">';
	 $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref .= '</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'type';	// We change column just before this field
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
			
			//if ($object->status != $object::STATUS_CLOTURE) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontomodify);
			//}

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken(), '', $permissiontovalidate);
			}

			// Prise en compte
			if($object->status == $object::STATUS_VALIDATED && !empty($object->fields['mise_enplace']['arrayofkeyval'][$object->mise_enplace])){
				print dolGetButtonAction($langs->trans('Prendre en compte'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=priseencompte&token='.newToken(), '', $permissionpriseencompte);
			}

			// Annuler
			/*if ($object->status == $object::STATUS_VALIDATED && $object->fields['mise_enplace']['arrayofkeyval'][$object->mise_enplace] == "Non") {
				print dolGetButtonAction($langs->trans('Annuler'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=priseencompte_annulation&token='.newToken(), '', $permissionpriseencompte);
			}*/

			// cloturer
			if ($object->status == $object::STATUS_PRISENCOMPTE && !empty($object->date_traitement) && !empty($object->detail_traitement)) {
				print dolGetButtonAction($langs->trans('Clôturer'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=cloture&token='.newToken(), '', $permissiontocloture);
			}

			// Clone
			//print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid)?'&socid='.$object->socid:'').'&action=clone&token='.newToken(), '', $permissiontoadd);

			// Delete
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete);
		}
		print '</div>'."\n";
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		
		/*print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 0;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->formationhabilitation->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('formationhabilitation:User_habilitation', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('user_habilitation'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);*/


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-list-alt imgforviewmode', dol_buildpath('/formationhabilitation/user_habilitation_agenda.php', 1).'?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}
}

// End of page
llxFooter();
$db->close();
