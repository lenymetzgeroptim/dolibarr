<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 *   	\file       action_card.php
 *		\ingroup    actions
 *		\brief      Page to create/edit/view action
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
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/actions/class/html.form.class.php';
dol_include_once('/actions/class/action.class.php');
dol_include_once('/actions/lib/actions_action.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("actions@actions", "other"));

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
$origin    =  GETPOST('origin', 'alpha');
$originid  = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int'));    // For backward compatibility

if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

// Initialize technical objects
$object = new Action($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->actions->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('actioncard', 'globalcard')); // Note that conf->hooks_modules contains array

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

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('actions', 'action', 'read');
	$permissiontoadd = $user->hasRight('actions', 'action', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('actions', 'action', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('actions', 'action', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('actions', 'action', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->actions->multidir_output[isset($object->entity) ? $object->entity : 1].'/action';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("actions")) {
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

	$backurlforlist = dol_buildpath('/actions/action_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/actions/action_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__').'&origin='.$origin.'&origin_id='.$originid.'';
				if($id != '' && !empty($origin) && !empty($originid)) {
					$object->deleteObjectLinked($origin, $originid);
					$object->add_object_linked($origin, $originid);
				}
			}
		}
	}


	if($action == 'update') {
		$object->oldcopy = clone $object;
	}
	
	//ajout de l'intervenant dans le groupe "action pilote" 
	//if ($user->id )

	
	$triggermodname = 'ACTIONS_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

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
	$triggersendname = 'ACTIONS_MYOBJECT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid = 'action'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
	$result = $object->deleteObjectLinked($origin, $originid);
}

/*
 * View
 */
//var_dump($object->alert);
$form = new Form($db);
$actionForm = new actionsForm($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Action");
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

	print load_fiche_titre($langs->trans("Nouvelle Action", $langs->transnoentitiesnoconv("Action")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="origin" value="'.$origin.'">';
	print '<input type="hidden" name="originid" value="'.$originid.'">';
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
// Reference
print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td></tr>';
	// Common attributes
	include DOL_DOCUMENT_ROOT.'/custom/actions/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	if ($element == 'constat') {
		$element = 'custom/constat';
		$subelement = 'constat';
	}
	dol_include_once('/custom/constat/class/constat.class.php');
	$srcobject = new Constat($db);
	$srcobject->fetch($originid);
	if (!empty($origin) && !empty($originid) && is_object($srcobject)) {

		print "\n<!-- ".$classname." info -->";
		print "\n";
		
		print '<input type="hidden" name="origin"         value="'.$srcobject->element.'">';
		print '<input type="hidden" name="originid"       value="'.$srcobject->id.'">';

		switch ($classname) {
			case 'Constat':
				$newclassname = 'Constat';
				break;
			default:
				$newclassname = $classname;
		}

		print '<tr><td>'.$langs->trans('Constat').'</td><td>'.$srcobject->getNomUrl(1).'</td></tr>';
	}

	print '</table>'."\n";

	print dol_get_fiche_end();

	// print $form->buttonsSaveCancel("Create");
	print $form->buttonsSaveCancel("CreateDraft");
	

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Action"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<input type="hidden" name="origin" value="'.$origin.'">';
	print '<input type="hidden" name="originid" value="'.$originid.'">';

	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/custom/actions/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/custom/actions/tpl/extrafields_edit.tpl.php';
	
	
	if ($element == 'constat') {
		$element = 'custom/constat';
		$subelement = 'constat';
	}
	dol_include_once('/custom/constat/class/constat.class.php');
	$srcobject = new Constat($db);
	$srcobject->fetch($originid);
	if (!empty($origin) && !empty($originid) && is_object($srcobject)) {

		print "\n<!-- ".$classname." info -->";
		print "\n";
		
		print '<input type="hidden" name="origin"         value="'.$srcobject->element.'">';
		print '<input type="hidden" name="originid"       value="'.$srcobject->id.'">';

		switch ($classname) {
			case 'Constat':
				$newclassname = 'Constat';
				break;
			default:
				$newclassname = $classname;
		}

		print '<tr><td>'.$langs->trans('Constat').'</td><td>'.$srcobject->getNomUrl(1).'</td></tr>';
	}
	
	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';

}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = actionPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Action"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&origin='.$origin.'&originid='.$originid, $langs->trans('DeleteAction'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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

	// Encours confirmation
	if( $action == 'setEnCours'){

		$object->updateEnCours();

		$object->actionmsg2 = $langs->transnoentitiesnoconv("ACTIONS_EN_COURSInDolibarr", $object->ref);

		// Call trigger
		$result = $object->call_trigger('ACTIONS_EN_COURS', $user);
		if ($result < 0) {
			$error++;
		}

		
	}

	// Clone confirmation
	if( $action == 'setSolde'  && $confirm == 'yes' ){

		$object->updateSolde();

		$object->actionmsg2 = $langs->transnoentitiesnoconv("ACTIONS_SOLDEEInDolibarr", $object->ref);

		// Call trigger
		$result = $object->call_trigger('ACTIONS_SOLDEE', $user);
		if ($result < 0) {
			$error++;
		}

		
	}

	
	if( $action == 'setAttSolde'  && $confirm == 'yes' ){

		$object->updateAttSolde();

		$object->actionmsg2 = $langs->transnoentitiesnoconv("ACTIONS_ATT_SOLDEEInDolibarr", $object->ref);

		// Call trigger
		$result = $object->call_trigger('ACTIONS_ATT_SOLDEE', $user);
		if ($result < 0) {
			$error++;
		}

	}

	if( $action == 'setClasse'  && $confirm == 'yes' ){

		$object->updateClasse();

		$object->actionmsg2 = $langs->transnoentitiesnoconv("ACTIONS_CLASSEInDolibarr", $object->ref);

		// Call trigger
		$result = $object->call_trigger('ACTIONS_CLASSE', $user);
		if ($result < 0) {
			$error++;
		}

		
	}

	//envoie notification de fin d'une action au service Q3SE et au pilote
	
	if ($action == 'setSolde' && $confirm == 'yes') {
		$subject = '[OPTIM Industries] Notification automatique action soldé ';
		$from = 'erp@optim-industries.fr';
	
		$pilote = new User($db);
		$pilote->fetch();
	
		$emails = [];
	
		// Vérification de l'email du pilote
		if (!empty($pilote->email) && filter_var($pilote->email, FILTER_VALIDATE_EMAIL)) {
			$emails[] = $pilote->email;
		}
	
		// Fonction pour ajouter des emails valides à la liste
		function addValidEmails($user_group, $group_name, &$emails)
		{
			$user_group->fetch('', $group_name);
			$liste_utilisateur = $user_group->listUsersForGroup();
			foreach ($liste_utilisateur as $qualite) {
				if (!empty($qualite->email) && filter_var($qualite->email, FILTER_VALIDATE_EMAIL)) {
					$emails[] = $qualite->email;
				}
			}
		}
	
		// Récupération des emails des groupes "Q3SE" et "Resp. Q3SE"
		$user_group = new UserGroup($db);
		addValidEmails($user_group, 'Q3SE', $emails);
		addValidEmails($user_group, 'Resp. Q3SE', $emails);
	
		// Suppression des doublons
		$emails = array_unique($emails);
		$to = implode(", ", $emails);
	
		// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
		$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
		$resql_creator = $db->query($sql_creator);
		$creator_name = "";
	
		if ($resql_creator) {
			if ($db->num_rows($resql_creator) > 0) {
				$creator = $db->fetch_object($resql_creator);
				$creator_name = $creator->firstname . ' ' . $creator->lastname;
			}
		}
	
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
		$link = '<a href="' . $urlwithroot . '/custom/actions/action_card.php?id=' . $object->id . '">' . $object->ref . '</a>';
	
		$msg = $langs->transnoentitiesnoconv("Bonjour, Nous vous informons que l'action " . $link . " créé par " . $creator_name . " a été marquée comme soldée. Cordialement, Votre système de notification");
	
		$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track' . '_' . $object->id);
	
		// Send mail
		$res = $cmail->sendfile();
		if ($res) {
			setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
			print '<script>
				window.location.replace("' . $_SERVER["PHP_SELF"] . "?id=" . $object->id . '");
			</script>';
		} else {
			setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
		}
	}


	//envoie notification au pilote si action est de retour au status en cours
			
	if ($action == 'setEnCours' && $confirm == 'oui') {
		$subject = '[OPTIM Industries] Notification automatique action en cours';
		$from = 'erp@optim-industries.fr';

		$pilote = new User($db);
		$pilote->fetch($object->intervenant);

		$emails = [];

		// Vérification de l'email du pilote
		if (!empty($pilote->email) && filter_var($pilote->email, FILTER_VALIDATE_EMAIL)) {
			$emails[] = $pilote->email;
		}

		// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
		$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
		$resql_creator = $db->query($sql_creator);
		$creator_name = "";

		if ($resql_creator && $db->num_rows($resql_creator) > 0) {
			$creator = $db->fetch_object($resql_creator);
			$creator_name = $creator->firstname . ' ' . $creator->lastname;
		}

		// Suppression des doublons
		$emails = array_unique($emails);
		$to = implode(", ", $emails);

		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
		$link = '<a href="' . $urlwithroot . '/custom/actions/action_card.php?id=' . $object->id . '">' . $object->ref . '</a>';

		$msg = $langs->transnoentitiesnoconv("Bonjour, Nous vous informons que l'action " . $link . " créée par " . $creator_name . " est désormais en cours. Cordialement, Votre système de notification");

		$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track' . '_' . $object->id);

		// Envoi du mail
		$res = $cmail->sendfile();
		if ($res) {
			setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
			print '<script>
				window.location.replace("' . $_SERVER["PHP_SELF"] . "?id=" . $object->id . '");
			</script>';
		} else {
			setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
		}
	}
	
	//notification de l'action en attente de validation de solde
	if ($action == 'setAttSolde') {
		$subject = '[OPTIM Industries] Notification automatique - Une action en attente de validation de solde';
		$from = 'erp@optim-industries.fr';
	
		$emails = [];
	
		// Fonction pour ajouter des emails valides à la liste
		function addValidEmails($user_group, $group_name, &$emails)
		{
			$user_group->fetch('', $group_name);
			$liste_utilisateur = $user_group->listUsersForGroup();
			foreach ($liste_utilisateur as $qualite) {
				if (!empty($qualite->email) && filter_var($qualite->email, FILTER_VALIDATE_EMAIL)) {
					$emails[] = $qualite->email;
				}
			}
		}
	
		// Récupération des emails des groupes "Q3SE" et "Resp. Q3SE"
		$user_group = new UserGroup($db);
		addValidEmails($user_group, 'Q3SE', $emails);
		addValidEmails($user_group, 'Resp. Q3SE', $emails);
	
		// Suppression des doublons
		$emails = array_unique($emails);
		$to = implode(", ", $emails);
	
		// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
		$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
		$resql_creator = $db->query($sql_creator);
		$creator_name = "";
	
		if ($resql_creator && $db->num_rows($resql_creator) > 0) {
			$creator = $db->fetch_object($resql_creator);
			$creator_name = $creator->firstname . ' ' . $creator->lastname;
		}
	
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
		$link = '<a href="' . $urlwithroot . '/custom/actions/action_card.php?id=' . $object->id . '">' . $object->ref . '</a>';
	
		$msg = $langs->transnoentitiesnoconv("Bonjour, Nous vous informons que l'action " . $link . " créée par " . $creator_name . " est en attente de validation pour être marquée comme soldée. Veuillez indiquer si cette action est terminée ou non. Cordialement, Votre système de notification");
	
		$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track' . '_' . $object->id);
	
		// Envoi du mail
		$res = $cmail->sendfile();
		if ($res) {
			setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
			print '<script>
				window.location.replace("' . $_SERVER["PHP_SELF"] . "?id=" . $object->id . '");
			</script>';
		} else {
			setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
		}
	}

	
	
	//notification de l'action en attente de validation de solde
	if ($action == 'setEnCours') {
		$currentDateTime = date('Y-m-d H:i:s');
		$dueDateTime = $object->date_eche;
	
		if ($currentDateTime > $dueDateTime) {
			$subject = '[OPTIM Industries] Notification automatique - Relance Action en cours';
			$from = 'erp@optim-industries.fr';
			$emails = [];
	
			// Récupération de l'email du pilote
			$pilote = new User($db);
			$pilote->fetch($object->intervenant);
			if (!empty($pilote->email) && filter_var($pilote->email, FILTER_VALIDATE_EMAIL)) {
				$emails[] = $pilote->email;
			}
	
			// Suppression des doublons
			$emails = array_unique($emails);
			$to = implode(", ", $emails);
	
			// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
			$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
			$resql_creator = $db->query($sql_creator);
			$creator_name = "";
	
			if ($resql_creator && $db->num_rows($resql_creator) > 0) {
				$creator = $db->fetch_object($resql_creator);
				$creator_name = $creator->firstname . ' ' . $creator->lastname;
			}
	
			global $dolibarr_main_url_root;
			$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
			$link = '<a href="' . $urlwithroot . '/custom/actions/action_card.php?id=' . $object->id . '">' . $object->ref . '</a>';
	
			$msg = $langs->transnoentitiesnoconv("Bonjour, Nous vous rappelons que l'action " . $link . " créée par " . $creator_name . " est toujours en cours et a dépassé la date d'échéance. Cordialement, Votre système de notification");
	
			$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track' . '_' . $object->id);
	
			// Envoi du mail
			$res = $cmail->sendfile();
			if ($res) {
				setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
			}
		}
	}
	


	// mail pour relance action en cours
	if ($action == 'setRelance') {
		$currentDateTime = date('Y-m-d H:i:s');
		$dueDateTime = $object->date_eche;
	
		if ($currentDateTime > $dueDateTime) {
			$subject = '[OPTIM Industries] Notification automatique - Relance Action en cours';
			$from = 'erp@optim-industries.fr';
			$emails = [];
	
			// Récupération de l'email du pilote
			$pilote = new User($db);
			$pilote->fetch($object->intervenant);
			if (!empty($pilote->email) && filter_var($pilote->email, FILTER_VALIDATE_EMAIL)) {
				$emails[] = $pilote->email;
			}
	
			// Suppression des doublons
			$emails = array_unique($emails);
			$to = implode(", ", $emails);
	
			// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
			$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
			$resql_creator = $db->query($sql_creator);
			$creator_name = "";
	
			if ($resql_creator && $db->num_rows($resql_creator) > 0) {
				$creator = $db->fetch_object($resql_creator);
				$creator_name = $creator->firstname . ' ' . $creator->lastname;
			}
	
			global $dolibarr_main_url_root;
			$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
			$link = '<a href="' . $urlwithroot . '/custom/actions/action_card.php?id=' . $object->id . '">' . $object->ref . '</a>';
	
			$msg = $langs->transnoentitiesnoconv("Bonjour, Nous vous rappelons que l'action " . $link . " créée par " . $creator_name . " n'est pas encore terminée. Cordialement, Votre système de notification");
	
			$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track' . '_' . $object->id);
	
			// Envoi du mail
			$res = $cmail->sendfile();
			if ($res) {
				setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
				print '<script>window.location.replace("' . $_SERVER["PHP_SELF"] . "?id=" . $object->id . '");</script>';
			} else {
				setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
			}
		}
	}
	


	
	$action ='';

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$text = $langs->trans('ConfirmActionAction', $object->ref);
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
	$linkback = '<a href="'.dol_buildpath('/actions/action_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

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
	$keyforbreak='date_eche';	// We change column just before this field
	
	//unset($object->fields['date_eche']);				// Hide field already shown in banner
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

	print"<script> // Fonction pour afficher une popup temporaire
	function showPopupMessage(message, type = 'info', duration = 10000) {
		let popupContainer = document.getElementById('popup-container');
		if (!popupContainer) {
			popupContainer = document.createElement('div');
			popupContainer.id = 'popup-container';
			popupContainer.style.position = 'fixed';
			popupContainer.style.top = '50px';
			popupContainer.style.left = '50%';
			popupContainer.style.transform = 'translateX(-50%)';
			popupContainer.style.zIndex = '1000';
			popupContainer.style.display = 'flex';
			popupContainer.style.flexDirection = 'column';
			popupContainer.style.alignItems = 'center';
			document.body.appendChild(popupContainer);
		}

		let popup = document.createElement('div');
		popup.className = 'popup-message';
		popup.textContent = message;
		popup.style.background = '#4CAF50';
		popup.style.color = '#fff';
		popup.style.fontWeight = 'bold';
		popup.style.padding = '10px 20px';
		popup.style.margin = '5px';
		popup.style.borderRadius = '5px';
		popup.style.boxShadow = '0px 0px 10px rgba(0, 0, 0, 0.1)';
		popup.style.opacity = '1';
		popup.style.transition = 'opacity 0.5s ease-in-out';

		popupContainer.appendChild(popup);

		setTimeout(() => {
			popup.style.opacity = '0';
			setTimeout(() => popup.remove(), 500);
		}, duration);
	}
	</script>";
	
	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}
		//var_dump($object->table_element);
		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}

			// Back to draft
			if($user->rights->actions->action->ServiceQ3SE){
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
				}
			}
			
			
			/*if (($object->status != $object::STATUS_SOLDEE && !in_array('intervenant', $user->rights->actions->action)) ||($object->status == $object::STATUS_SOLDEE && in_array('intervenant', $user->rights->actions->action)) ) {
				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&origin='.$origin.'&originid='.$originid.'&token='.newToken(), '', $permissiontoadd);
			}*/

			if (!($object->status == $object::STATUS_SOLDEE && in_array('intervenant', $user->rights->actions->action)) && $object->status != $object::STATUS_CANCELED) {
				print dolGetButtonAction('', $langs->trans('Modifier/Compléter'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&origin='.$origin.'&originid='.$originid.'&token='.newToken(), '', $permissiontoadd);
			}
				//print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&origin='.$origin.'&originid='.$originid.'&token='.newToken(), '', $permissiontoadd);
		//var_dump($object->module);
			if($user->rights->actions->action->ServiceQ3SE){
				// Validate
				if ($object->status == $object::STATUS_DRAFT) {
					if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
						print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
					} else {
						$langs->load("errors");
						print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
					}
				}
			}
			if($user->rights->actions->action->ServiceQ3SE){
				if ($object->status == $object::STATUS_ATT_SOLDEE) {
					
					print dolGetButtonAction('', $langs->trans('passer au status soldé'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setSolde&confirm=yes&token='.newToken(), '', $permissiontoadd);
	
				}
			}
			
			if($user->rights->actions->action->ServiceQ3SE){
				if ($object->status == $object::STATUS_ATT_SOLDEE) {
					print dolGetButtonAction('', $langs->trans('Retourner au status en cours'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setEnCours&confirm=oui&token='.newToken(), '', $permissiontoadd);		
				}
			}

			if($user->rights->actions->action->ServiceQ3SE){
				if ($object->status == $object::STATUS_EN_COURS) {
					print dolGetButtonAction('', $langs->trans('Relancer le pilote'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setRelance&confirm=yes&token='.newToken(), '', $permissiontoadd);		
				}
			}

			if($user->rights->actions->action->intervenant || $user->rights->actions->action->ServiceQ3SE){
				//passé au status attente solde
				if ($object->status == $object::STATUS_EN_COURS) {
					if($object->avancement == '5'){
					print dolGetButtonAction('', $langs->trans('passer au status validation soldé'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setAttSolde&confirm=yes&token='.newToken(), '', $permissiontoadd);	
					}	
				}
			}

			if($user->rights->actions->action->ServiceQ3SE){
				if ($object->status == $object::STATUS_EN_COURS || $object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_DRAFT ){
					print dolGetButtonAction('', $langs->trans('lier a un constat'), 'default', '/erp/custom/actions/action_constat_list.php?idaction='.$object->id.'&action=setCloture&confirm=yes&token='.newToken(), '', $permissiontoadd);
				}	
			}	

			if($user->rights->actions->action->ServiceQ3SE){
			//passé au status en cours
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('passer au status en cours'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setEnCours&confirm=yes&token='.newToken(), '', $permissiontoadd);
				}
					
				//passé au status classé(annulé)
				if ($object->status == $object::STATUS_EN_COURS || $object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Annuler cette action'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setClasse&confirm=yes&token='.newToken(), '', $permissiontoadd);	
					print "<script>showPopupMessage('Le pilote peut passer son action au status \'attente  soldée\' que si son avancement est à 100% ', 'error');</script>";	
				}

				if ($object->status != $object::STATUS_CANCELED) {
					if($object->eff_act !=null){
						print dolGetButtonAction('', $langs->trans('Classer cette action'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setClasse&confirm=yes&token='.newToken(), '', $permissiontoadd);		
					}
				}

				if($object->status == $object::STATUS_SOLDEE){
					print "<script>showPopupMessage('Veuilliez évaluer l\'action', 'error');</script>";
				}
			}

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

			
		}
		print '</div>'."\n";
	}
	 
	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		/*if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->actions->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('actions:Action', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}*/

		/*include DOL_DOCUMENT_ROOT.'/custom/core/tpl/linkedobjectblock.tpl.php';
		$object->fetchObjectLinked2();
	 	var_dump($object);*/

		//Show links to link elements
		
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('action'));
		$somethingshown = $actionForm->showLinkedObjectBlock($object, $linktoelem);
	
		// // Show links to link elements
		// $linktoelem = $form->showLinkToObjectBlock($object, null, array('action'));

		//  $compatibleImportElementsList = false;
		// // // if ($usercancreate && $object->statut == Action::STATUS_DRAFT) {
		// 	$compatibleImportElementsList = array('constat', 'actions_action'); // import from linked elements
		// // // }
		//  $somethingshown = $actionForm->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);
		
		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/actions/action_agenda.php', 1).'?id='.$object->id);

		/*// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
	*/
		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'action';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->actions->dir_output;
	$trackid = 'action'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}


// End of page
llxFooter();
$db->close();
