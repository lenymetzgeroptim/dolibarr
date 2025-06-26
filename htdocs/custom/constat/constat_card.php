<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Faure Louis
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
 *   	\file       constat_card.php
 *		\ingroup    constat
 *		\brief      Page to create/edit/view constat
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
require_once DOL_DOCUMENT_ROOT.'/custom/constat/class/html.form.class.php';

dol_include_once('/constat/class/constat.class.php');
dol_include_once('/constat/lib/constat_constat.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("constat@constat", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOST('lineid', 'int');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');


// Initialize technical objects
$object = new Constat($db);
//$actionRef = $object->listActionRef();
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->constat->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('constatcard', 'globalcard')); // Note that conf->hooks_modules contains array

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

// Gestion du responsable d'affaire
$is_responsable_affaire = 0; 
if($object->id) {
	$projet = new Project($db);

	foreach(explode(",", $object->fk_project) as $project_id) {
		$projet->fetch($project_id);
		$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
		if (in_array($user->id, $liste_chef_projet)) {
			$is_responsable_affaire = 1; 
			break;
		}
	}
}

// Est-ce que des champs obligatoire sont non renseignés ? 
$fields_null = '';
foreach($object->fields as $key => $val) {
	if(!$object->$key && $val['notnull_validate']) {
		if($key == 'cout_total' && $object->$key === 0) continue;
		$fields_null .= $langs->trans($val['label']).", ";
	}
}
$fields_null = rtrim($fields_null, ', ');

$label_button_action_validate = ($fields_null ? $langs->trans('ConstatFieldsNullMendatory', $fields_null) : '');
if ($object->status == $object::STATUS_EN_COURS) {
	$label_button_action_validate .= $label_button_action_validate && !empty($object->getActionsUnsold()) ? '<br>' : '';
	$label_button_action_validate .= !empty($object->getActionsUnsold()) ? $langs->trans('ConstatActionNoSolde') : '';
}

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks+
$enablepermissioncheck = 1;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('constat', 'constat', 'readall') || ($user->hasRight('constat', 'constat', 'read') && (($user->id == $object->fk_user_creat || $user->id == $object->fk_user) || $is_responsable_affaire || $user->hasRight('constat', 'constat', 'complete_q3se')));
	$permissiontoadd = $user->hasRight('constat', 'constat', 'writeall') || $user->hasRight('constat', 'constat', 'write');
	$permissiontodelete = $user->hasRight('constat', 'constat', 'delete') || (($user->id == $object->fk_user_creat || $user->id == $object->fk_user) && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('constat', 'constat', 'writeall') || $user->hasRight('constat', 'constat', 'write');
	//$permissiondellink = $user->hasRight('constat', 'constat', 'write'); // Used by the include of actions_dellink.inc.php

	if($object->status == $object::STATUS_DRAFT) {
		$permissiontoupdate = $user->admin || $user->id == $object->fk_user_creat || $user->id == $object->fk_user;
		$permissiontovalidate = $user->admin || $user->id == $object->fk_user_creat || $user->id == $object->fk_user;
	}
	elseif($object->status == $object::STATUS_VALIDATED) {
		$permissiontoupdate = $user->admin || $is_responsable_affaire;
		$permissiontovalidate = $user->admin || $is_responsable_affaire;
	}
	elseif($object->status == $object::STATUS_EN_COURS) {
		$permissiontoupdate = $user->hasRight('constat', 'constat', 'complete_q3se');
		$permissiontovalidate = $user->hasRight('constat', 'constat', 'complete_q3se');
	}
	else {
		$permissiontoupdate = 0;
		$permissiontovalidate = 0;
	}
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	//$permissiondellink = 1;
	$permissiontoupdate = 1;
	$permissiontovalidate = 1;
}

$upload_dir = $conf->constat->multidir_output[isset($object->entity) ? $object->entity : 1].'/constat';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object->id, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("constat")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}

// Permet d'avoir les noms complet dans les multiselect
$conf->global->MAIN_DISABLE_TRUNC = 1;


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

	$backurlforlist = dol_buildpath('/constat/constat_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/constat/constat_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'CONSTAT_CONSTAT_MODIFY'; // Name of trigger action code to execute when we modify record

	include_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';



	if($action == 'update') {
		$object->oldcopy = clone $object;
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/custom/constat/core/actions_addupdatedelete.inc.php';
	// include DOL_DOCUMENT_ROOT.'/core//actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	//include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

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
	
	if ($action == 'setPrise' && $confirm == 'yes'){
	
		$subject = '[OPTIM Industries] Notification automatique constat vérifié ';
	
		$from = 'erp@optim-industries.fr';
		
		// Si la requête a réussi
		if ($result) {
			$to = ''; // Initialisation de la chaîne d'emails
			while ($obj = $db->fetch_object($result)) {
				$email = $obj->email;
				// Ajoute l'email à la liste
				if (!empty($email)) {
					$to .= $email . ", ";
				}
			}
		}
	
		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Resp. Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
			}
		}
	
		// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
		$sql = "SELECT lastname, firstname FROM llx_user WHERE rowid = ".$object->fk_user_creat;
		$resql = $db->query($sql);
		$creator_name = "";
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				$creator = $db->fetch_object($resql);
				$creator_name = $creator->firstname . ' ' . $creator->lastname;
			}
		}
	
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
	
		$to = rtrim($to, ", ");
		$message = $langs->transnoentitiesnoconv(" Bonjour, le constat ".$link." créé par ". $creator_name. " a été vérifié. Veuillez compléter votre partie et passer au statut suivant. Cordialement, votre système de notification.");

	
		$cmail = new CMailFile($subject, $to, $from, $message, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$object->id);
		
		// Send mail
		$res = $cmail->sendfile();
		if($res) {
			 setEventMessages($langs->trans("EmailSend"), null, 'warning');
			// header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			// exit;
			print '<script>
			window.location.replace("'.$_SERVER["PHP_SELF"]."?id=".$object->id.'");
			</script>';
		} 		
	
	}

	if ($action == 'confirmsetCloture' && $confirm == 'yes'){

		$subject = '[OPTIM Industries] Notification automatique constat classé';
	
		$from = 'erp@optim-industries.fr';
		
		$projet = new Project($db);
		$projet->fetch($object->fk_project);
		$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
	
		// Sélectionne les emails des utilisateurs dont les IDs sont dans $liste_chef_projet
		$sql = "SELECT email FROM " . MAIN_DB_PREFIX . "user WHERE rowid IN (" . implode(",", $liste_chef_projet) . ")";
		$result = $db->query($sql);
	
		// Si la requête a réussi
		if ($result) {
			$to = ''; // Initialisation de la chaîne d'emails
			while ($obj = $db->fetch_object($result)) {
				$email = $obj->email;
				// Ajoute l'email à la liste
				if (!empty($email)) {
					$tochef .= $email . ", ";
				}
			}
		}
	
		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
			}
		}
		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Resp. Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$torespQ3 .= ", ";
			}
		}
		$emeteur = New User($db);
		$emeteur->fetch($object->fk_user_creat);
		
		if(!empty($emeteur->email)){
			$toemeteur = $emeteur->email;
		}
	
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
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
	
		$to .= $tochef;
		$to .= $toemeteur;
		$to .= $torespQ3;
		$to = rtrim($to, ", ");
		
		$msg = $langs->transnoentitiesnoconv("Bonjour, le constat ". $link. " créé par ". $creator_name." a été clôturé. Cordialement, Votre système de notification." );
	
		$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$object->id);
		
		// Send mail
		$res = $cmail->sendfile();
		if($res) {
			setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
			print '<script>
			window.location.replace("'.$_SERVER["PHP_SELF"]."?id=".$object->id.'");
			</script>';
		} 
	}

	if ($action == 'setSolde' && $confirm == 'yes'){


		$subject = '[OPTIM Industries] Notification automatique  constat soldé';

		$from = 'erp@optim-industries.fr';
		
		$projet = new Project($db);
		$projet->fetch($object->fk_project);
		$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');

		// Sélectionne les emails des utilisateurs dont les IDs sont dans $liste_chef_projet
		$sql = "SELECT email FROM " . MAIN_DB_PREFIX . "user WHERE rowid IN (" . implode(",", $liste_chef_projet) . ")";
		$result = $db->query($sql);

		// Si la requête a réussi
		if ($result) {
			$to = ''; // Initialisation de la chaîne d'emails
			while ($obj = $db->fetch_object($result)) {
				$email = $obj->email;
				// Ajoute l'email à la liste
				if (!empty($email)) {
					$tochef .= $email . ", ";
				}
			}
		}

			$user_group = New UserGroup($db);
		$user_group->fetch('', 'Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
					
			}
		}

		$emeteur = New User($db);
		$emeteur->fetch($object->fk_user_creat);
		
		if(!empty($emeteur->email)){
		$toemeteur = $emeteur->email;
			}	

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
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
        $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
        $link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';

		$to .= $tochef;
		$to .= $toemeteur;
		$to = rtrim($to, ", ");
		$msg =  $langs->transnoentitiesnoconv("Le constat  ".$link." créé par " .$creator_name. " est à classé par le service Q3SE votre system d'information");
		$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$object->id);
		
		// Send mail
		$res = $cmail->sendfile();
		if($res) {
			setEventMessages($langs->trans("EmailSend"), null, 'mesgs');	
		} else {
			setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
			print '<script>
			window.location.replace("'.$_SERVER["PHP_SELF"]."?id=".$object->id.'");
			</script>';
		}	
	}
	
	if ($action == 'setSolde' && $confirm == 'yes'){
	
		$subject = '[OPTIM Industries] Notification automatique constat soldé ';

		$from = 'erp@optim-industries.fr';
		
		// Si la requête a réussi
		if ($result) {
			$to = ''; // Initialisation de la chaîne d'emails
			while ($obj = $db->fetch_object($result)) {
				$email = $obj->email;
				// Ajoute l'email à la liste
				if (!empty($email)) {
					$to .= $email . ", ";
				}
			}
		}

		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Resp. Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				
	
			}
		}

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
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
        $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
        $link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';

		
		$to = rtrim($to, ", ");
		$message = $langs->transnoentitiesnoconv("Bonjour, le constat ".$link." créé par ".$creator_name." a été soldé. Le constat est donc terminé. Veuillez le passer au statut clôturé pour qu'il ne puisse être modifié. Cordialement, Votre système de notification.");
		//$msg = 'test notif ( a ne pas prendre en compte si reçu )';
		$cmail = new CMailFile($subject, $to, $from, $message, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$object->id);
		
		// Send mail
		$res = $cmail->sendfile();
		if($res) {
			 setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
			// header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			// exit;
			print '<script>
			window.location.replace("'.$_SERVER["PHP_SELF"]."?id=".$object->id.'");
			</script>';
		} 		
	
	}

	// if( $action == 'setPrise'  && $confirm == 'yes' ){
	// 	$object->updatePrise();

	// 		$object->actionmsg = $langs->transnoentitiesnoconv("CONSTAT_PRISEInDolibarrr", $object->ref);
	// 		// Call trigger
	// 		$result = $object->call_trigger('CONSTAT_PRISE', $user);
	// 		if ($result < 0) {
	// 			$error++;
	// 		}
	// 		// End call triggers
		

	// }

	if ($action == 'confirm_setencours' && $confirm == 'yes' && $permissiontovalidate && empty($label_button_action_validate)) {
		$result = $object->setEnCours($user);

		if ($result >= 0) {
			$object->actionmsg = $langs->transnoentitiesnoconv("CONSTAT_EN_COURSInDolibarr", $object->ref);
			
			// Call trigger
			$result = $object->call_trigger('CONSTAT_EN_COURS', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
		
		if(!$error) {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			exit;
		}
	}

	if ($action == 'confirm_close' && $confirm == 'yes' && $permissiontovalidate && empty($label_button_action_validate)) {
		$result = $object->close($user);

		if ($result >= 0) {
			$object->actionmsg2 = $langs->transnoentitiesnoconv("CONSTAT_CLOTUREInDolibarr", $object->ref);
			// Call trigger
			$result = $object->call_trigger('CONSTAT_CLOTURE', $user);
			
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
		
		if(!$error) {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			exit;
		}
	}

	// if( $action == 'setSolde'  && $confirm == 'yes' ){
	// 	$object->updateSolde();

	// 		$object->actionmsg2 = $langs->transnoentitiesnoconv("CONSTAT_SOLDEEInDolibarr", $object->ref);
			
	// 		// Call trigger
	// 		$result = $object->call_trigger('CONSTAT_SOLDEE', $user);
	// 		if ($result < 0) {
	// 			$error++;
	// 		}
	// 		// End call triggers
		

	// }

	// if( $action == 'setCloture'  && $confirm == 'yes' ){
	// 	$object->updateCloture();
			
	// 	$object->actionmsg2 = $langs->transnoentitiesnoconv("CONSTAT_CLOTUREInDolibarr", $object->ref);
	// 	// Call trigger
	// 	$result = $object->call_trigger('CONSTAT_CLOTURE', $user);
		
	// 	if ($result < 0) {
	// 		$error++;
	// 	}
	// 	// End call triggers
			

	// }

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
				
                $model = 'standard_constat';
 
                $retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                if ($retgen < 0) {
                    setEventMessages($object->error, $object->errors, 'warnings');
                }
            }
        }
    }

	// Actions to send emails
	$triggersendname = 'CONSTAT_CONSTAT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_CONSTAT_TO';
	$trackid = 'constat'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$actionForm = new actionsForm($db);
$actionForm = new actionsForm($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Constat");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'constat');

// $resp = $object->getAgencesBySoc();

// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Constat")), '', 'object_'.$object->picto);

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

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/custom/constat/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");
	
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Constat"), '', 'object_'.$object->picto);

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

	// Étape 1 : Sauvegarder l'état initial des champs
	// $original_fields = $object->fields;

	// // Étape 2 : Masquer les champs pour le premier appel
	// $fields_to_hide_first = [
	// 	'description_impact',
	// 	'cout_total',
	// 	'cloture_date',
	// 	'actionsimmediates',
	// 	'actionsimmediates_commentaire',
	// 	'analyse_cause_racine',
	// 	'recurent',
	// 	'infoclient',
	// 	'infoclient_commentaireinfoclient_commentaire',
	// 	'accordclient',
	// 	'accordclient_commentaire',
	// 	'controleclient',
	// 	'controleclient_commentaire',
	// 	'commentaire_emetteur',
	// 	'commentaire_resp_aff',
	// 	'commentaire_resp_q3se',
	// 	'commentaire_serv_q3se'
	// ];

	// foreach ($fields_to_hide_first as $field) {
	// 	if (isset($object->fields[$field])) {
	// 		$object->fields[$field]['enabled'] = '0';  // Pour ne pas le gérer
	// 		$object->fields[$field]['visible'] = 0;   // Pour ne pas l'afficher
	// 	}
	// }

	foreach ($object->fields as $key => $val) {
		$object->fields[$key]['visible'] = dol_eval($val['visible'], 1);
	}

	if($object->status == $object::STATUS_DRAFT) {
		$detail_open = $object->fields['label']['label_separation'];
	}
	elseif($object->status == $object::STATUS_VALIDATED) {
		$detail_open = $object->fields['num_commande']['label_separation'];
	}
	elseif($object->status == $object::STATUS_EN_COURS) {
		$detail_open = $object->fields['analyse_cause_racine']['label_separation'];
	}

	print '<table class="border centpercent tableforfieldedit">'."\n";

	include DOL_DOCUMENT_ROOT.'/custom/constat/core/tpl/commonfields_edit.tpl.php';

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// if ( ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1) || $user->rights->constat->constat->ServiceQ3SE || $user->rights->constat->constat->ResponsableQ3SE) {
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';
	// }
	
	
	// $object->fields = $original_fields;

	
	// $fields_to_hide_second = [
	// 	'ref',
	// 	'label',
	// 	'date_eche',
	// 	'type_constat',
	// 	'fk_project',
	// 	'num_commande',
	// 	'site',
	// 	'sujet',
	// 	'description_constat'
	// ];

	// foreach ($fields_to_hide_second as $field) {
	// 	if (isset($object->fields[$field])) {
	// 		$object->fields[$field]['enabled'] = '0';  
	// 		$object->fields[$field]['visible'] = 0;   
	// 	}
	// }
	
	// include DOL_DOCUMENT_ROOT.'/custom/constat/core/tpl/commonfields_edit.tpl.php';


	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = constatPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Constat"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteConstat'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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
		$text = $langs->trans('ConfirmActionConstat', $object->ref);

	
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
	$linkback = '<a href="'.dol_buildpath('/constat/constat_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	
	$morehtmlref = '<div class="instruction">';
	$morehtmlref .= $object->labelStatusExplication[$object->status];
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';

	// Common attributes
	if($object->status == $object::STATUS_DRAFT) {
		$keyforbreak = '';
	}
	elseif($object->status == $object::STATUS_VALIDATED) {
		$keyforbreak = 'num_commande';
	}
	else {
		$keyforbreak = 'analyse_cause_racine';
	}

	if(!$object->commentaire_emetteur) unset($object->fields['commentaire_emetteur']);
	if(!$object->commentaire_resp_aff) unset($object->fields['commentaire_resp_aff']);
	if(!$object->commentaire_resp_q3se) unset($object->fields['commentaire_resp_q3se']);
	if(!$object->commentaire_serv_q3se) unset($object->fields['commentaire_serv_q3se']);
	if($object->actionsimmediates != 1) unset($object->fields['actionsimmediates_date']);
	if($object->actionsimmediates != 1) unset($object->fields['actionsimmediates_par']);
	//if($object->actionsimmediates != true) unset($object->fields['actionsimmediates_commentaire']);
	if($object->infoclient != 1) unset($object->fields['infoclient_date']);
	if($object->infoclient != 1) unset($object->fields['infoclient_par']);
	//if($object->infoclient != true) unset($object->fields['infoclient_commentaire']);
	if($object->accordclient != 1) unset($object->fields['accordclient_date']);
	if($object->accordclient != 1) unset($object->fields['accordclient_par']);
	//if($object->accordclient != true) unset($object->fields['accordclient_commentaire']);
	if($object->controleclient != 1) unset($object->fields['controleclient_date']);
	if($object->controleclient != 1) unset($object->fields['controleclient_par']);
	//if($object->controleclient != true) unset($object->fields['controleclient_commentaire']);

	foreach ($object->fields as $key => $val) {
		$object->fields[$key]['visible'] = dol_eval($val['visible'], 1);
	}

	print '<table class="border centpercent tableforfield">'."\n";

	include DOL_DOCUMENT_ROOT.'/custom/constat/core/tpl/commonfields_view.tpl.php';

	print '<table class="border centpercent tableforfield">'."\n";

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


	// print"<script> // Fonction pour afficher une popup temporaire
	// function showPopupMessage(message, type = 'info', duration = 10000) {
	// 	let popupContainer = document.getElementById('popup-container');
	// 	if (!popupContainer) {
	// 		popupContainer = document.createElement('div');
	// 		popupContainer.id = 'popup-container';
	// 		popupContainer.style.position = 'fixed';
	// 		popupContainer.style.top = '50px';
	// 		popupContainer.style.left = '50%';
	// 		popupContainer.style.transform = 'translateX(-50%)';
	// 		popupContainer.style.zIndex = '1000';
	// 		popupContainer.style.display = 'flex';
	// 		popupContainer.style.flexDirection = 'column';
	// 		popupContainer.style.alignItems = 'center';
	// 		document.body.appendChild(popupContainer);
	// 	}

	// 	let popup = document.createElement('div');
	// 	popup.className = 'popup-message';
	// 	popup.textContent = message;
	// 	popup.style.background = '#4CAF50';
	// 	popup.style.color = '#fff';
	// 	popup.style.fontWeight = 'bold';
	// 	popup.style.padding = '10px 20px';
	// 	popup.style.margin = '5px';
	// 	popup.style.borderRadius = '5px';
	// 	popup.style.boxShadow = '0px 0px 10px rgba(0, 0, 0, 0.1)';
	// 	popup.style.opacity = '1';
	// 	popup.style.transition = 'opacity 0.5s ease-in-out';

	// 	popupContainer.appendChild(popup);

	// 	setTimeout(() => {
	// 		popup.style.opacity = '0';
	// 		setTimeout(() => popup.remove(), 500);
	// 	}, duration);
	// }
	// </script>";

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Modifier
			if ($object->status != $object::STATUS_CLOTURE && $object->status != $object::STATUS_CANCELED) {
				print dolGetButtonAction('', $langs->trans('Modifier / Compléter'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&origin='.$origin.'&originid='.$originid.'&token='.newToken(), '', $permissiontoupdate);
			}

			// Valider
			if ($object->status == $object::STATUS_DRAFT && $permissiontovalidate) {
				// print "<script>showPopupMessage('L\'émetteur doit remplir les champs en gras pour valider le constat. ', 'error');</script>";
				print dolGetButtonAction($label_button_action_validate, $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', empty($label_button_action_validate));
			}
			elseif($object->status == $object::STATUS_VALIDATED && $permissiontovalidate) {
				print dolGetButtonAction($label_button_action_validate, $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setencours&confirm=yes&token='.newToken(), '', empty($label_button_action_validate));
			}
			elseif ($object->status == $object::STATUS_EN_COURS && $permissiontovalidate) {
				print dolGetButtonAction($label_button_action_validate, $langs->trans('Close'), 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_close&confirm=yes&token=' . newToken(), '', empty($label_button_action_validate));
			}
			
			
			// Check if "client info" is unchecked (si_info_client == false)
			// if ($object->infoclient == 0) {
			// 	// Passé au Status En Cours
			// 	if ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1 || $user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 		if ($object->status == $object::STATUS_VALIDATED) {
			// 			// Check if the necessary fields are filled
			// 			if ($object->type_constat != null && 
			// 				!empty($object->array_options['options_impact']) && 
			// 				!empty($object->array_options['options_rubrique']) && 
			// 				!empty($object->array_options['options_processusconcern'])) {
			// 				print dolGetButtonAction('', $langs->trans('passer au status en cours'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setEnCours&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// 			}
			// 		}
			// 	}
			// } else {
			// 	// Passé au Status Vérifié
			// 	if ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1 || $user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 		if ($object->status == $object::STATUS_VALIDATED) {
			// 			print dolGetButtonAction('', $langs->trans('passer au status vérifié'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setPrise&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// 		}
			// 	}
			// 	// if ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1 || $user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 	// 	if ($object->status == $object::STATUS_PRISE) {
						
			// 	// 		if ($object->type_constat != null && 
			// 	// 			!empty($object->array_options['options_impact']) && 
			// 	// 			!empty($object->array_options['options_rubrique']) && 
			// 	// 			!empty($object->array_options['options_processusconcern'])) {
			// 	// 			print dolGetButtonAction('', $langs->trans('passer au status en cours'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setEnCours&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// 	// 		}
			// 	// 	}
			// 	// }
			// }

			// if ( $user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 	if ($object->status == $object::STATUS_SOLDEE) {
			// 		print dolGetButtonAction('', $langs->trans('retourne au status en cours'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setEnCours&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// 	}	
			// }

			// if ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1 || $user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 	if ($object->status == $object::STATUS_VALIDATED) {
			// 		print "<script>showPopupMessage('Pour faire évoluer le statut, le Responsable d’Affaire doit soit cocher \'Information Client Requise\' pour passer au statut Vérifié, soit remplir tous les champs en gras pour passer directement au statut En cours. ', 'error');</script>";
			// 	}	
			// }

			// if ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1 || $user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 	if ($object->status == $object::STATUS_PRISE ) {
			// 		print "<script>showPopupMessage('Le constat est vérifié, informé le client (si nécessaire) puis complété les champs en gras pour passé au statut \'En Cours\', 'error');</script>";
			// 	}
			// }	

			// Passer au Status Soldé
			// if ($is_exist === false) {
			// 	// print "<script>showPopupMessage('Le constat ne peut être soldé tant qu'il y a des actions en cours  ', 'error');</script>";
			// }	
			
			// if ($is_exist === true) {
			// 	if ($user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 		if ($object->status == $object::STATUS_EN_COURS) {
			// 			if ($object->analyse_cause_racine != null) {
			// 				print dolGetButtonAction(
			// 					'',
			// 					$langs->trans('passer au status soldé'),
			// 					'default',
			// 					$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=setSolde&confirm=yes&token=' . newToken(),
			// 					'',
			// 					$permissiontoadd
			// 				);
			// 			}
			// 		}
			// 	}
			// }
			//passé au  Status Clôturé
			// if ($user->rights->constat->constat->ResponsableQ3SE  || $user->rights->constat->constat->ServiceQ3SE) {
			// 	if ($object->status != $object::STATUS_CLOTURE) {
			// 		print dolGetButtonAction('', $langs->trans('classer le constat'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setCloture&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// 		// $object->updateCloture();
					
			// 	}
			// }

			// if ($user->rights->constat->constat->ResponsableQ3SE || $user->rights->constat->constat->ServiceQ3SE) {
			// 	error_log("Statut actuel : " . $object->status);
			// 	error_log("Valeur de STATUS_CLASSE : " . $object::STATUS_CLASSE);
			
			// 	// Afficher le bouton seulement si le constat N'EST PAS encore classé
			// 	if ($object->status != $object::STATUS_CLASSE) { 
			// 		$url = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setClasse&confirm=yes&token='.newToken();
			// 		print '<a href="#" onclick="confirmClasser(\'' . $url . '\')" class="butAction">' . $langs->trans('Classer le constat') . '</a>';
			// 	}
	
			// }
			
			// if ($user->rights->constat->constat->ResponsableQ3SE  || $user->rights->constat->constat->ServiceQ3SE) {
			// 	if ($object->status == $object::STATUS_EN_COURS) {
			// 		print "<script>showPopupMessage('Le constat est en cours, veuillez passer au statut Soldé lorsque toutes les actions seront soldées ainsi que les champs en gras complété. ', 'error');</script>";
			// 	}
			// }

					
			// Créer action
			if ($object->status == $object::STATUS_EN_COURS && $permissiontoupdate){
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/actions/action_card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans("Créer action").'</a>';
			}
		
			//généré pdf constat
			if ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_EN_COURS) {
				print dolGetButtonAction('', $langs->trans('générer PDF'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_genererDocConstat&confirm=yes&token='.newToken(), '', $permissiontoupdate);
			}
			
			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete);
		}
		print '</div>'."\n";
	}

	$projet = new Project($db);
	$projet->fetch($object->fk_project);
	$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
	
	// Sélectionne les emails des utilisateurs dont les IDs sont dans $liste_chef_projet
	$sql = "SELECT email FROM " . MAIN_DB_PREFIX . "user WHERE rowid IN (" . implode(",", $liste_chef_projet) . ")";
	$result = $db->query($sql);

	// Si la requête a réussi
	if ($result) {
		$to = ''; // Initialisation de la chaîne d'emails
		while ($obj = $db->fetch_object($result)) {
			$email = $obj->email;
			// Ajoute l'email à la liste
			if (!empty($email)) {
				$to .= $email . ", ";

			}
		}
		$to = rtrim($to, ", ");
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
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->constat->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('constat:Constat', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		//  $linktoelem = $form->showLinkToObjectBlock($object, null, array('constat'));
		


		print '</div><div class="fichehalfright">';
		$somethingshown = $actionForm->showLinkedObjectBlock($object, $linktoelem);
		$MAXEVENT = 10;

		//$arrconstats = $object->getActionsByConstat();
			
		// foreach ($selectedelement as $element) {
		// 	$targetId = $element->fk_target;
		
			
		// 	$sql = "SELECT ac.status "; 
		// 	$sql .= "FROM ".MAIN_DB_PREFIX."actions_action as ac ";
		// 	$sql .= "WHERE ac.rowid = $targetId ";

		// 	$resultStatus = $db->query($sql);

		// 	if ($resultStatus) {
		// 		$statusObj = $db->fetch_object($resultStatus);
		// 		if($statusObj !== 3) {
		// 			$test = false;
		// 		}
				
				
		// 	}
		// }

		
		/*$sql .= "JOIN ".MAIN_DB_PREFIX."element_element as e ON ac.rowid = e.fk_target AND e.targettype = 'actions_action' ";
			$sql .= "JOIN ".MAIN_DB_PREFIX."constat_constat as co ON e.fk_source = co.rowid AND e.sourcetype = 'constat' ";
			$sql .= "WHERE ac.status = 3 AND e.fk_source = $id ";
			$sql .= "ORDER BY e.targettype";
			// Execute the query
			
			var_dump($result);
			// Check if all actions have status = 3
			$allStatusThree = false;
			$result = $db->query($sql);

			if ($result) {
					$num = $db->num_rows($result);
					$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if ($obj->status == 3) {
					$allStatusThree = true;
					// break;
					// var_dump($allStatusThree);
				}
			}
		}*/

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/constat/constat_agenda.php', 1).'?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		//$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}
	
	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}


	// Presend form
	$modelmail = 'constat';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->constat->dir_output;
	$trackid = 'constat'.$object->id;


	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	
	
}

// End of page
llxFooter();
$db->close();
