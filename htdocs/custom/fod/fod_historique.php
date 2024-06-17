<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *   	\file       fod_historique.php
 *		\ingroup    fod
 *		\brief      Page to view historique of Fod
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
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

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
dol_include_once('/fod/class/fod.class.php');
dol_include_once('/fod/lib/fod_fod.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("fod@fod", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Initialize technical objects
$object = new Fod($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Vérifie si l'utilisateur actif est un intervenant 
/*$user_interv = 0;
foreach($object->intervenants as $intervenant){
	if ($intervenant->id == $userid){
		$user_interv = 1;
		break;
	}
}*/

// Vérifie si l'utilisateur actif est un RA du projet
$projet = New Project($db);
$projet->fetch($object->fk_project);
$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
if(in_array($user->id, $liste_chef_projet)){
	$userRA = 1;
}
else $userRA = 0;

// Vérifie si l'utilisateur actif est un PCR
$user_group = New UserGroup($db);
$user_group->fetch(11);
$liste_PCR = $user_group->listUsersForGroup();
$userPCR = 0;
foreach($liste_PCR as $pcr){
	if ($pcr->id == $user->id){
		$userPCR = 1;
		break;
	}
}

$permissiontoread = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user->admin || $userRA || $userPCR;
$permissiontoGenerateHistoriqueFod = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user->admin || $userRA || $userPCR;	

// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
include DOL_DOCUMENT_ROOT.'/custom/fod/core/actions_addupdatedelete.inc.php';

// Security check
if (empty($conf->fod->enabled)) {
	accessforbidden('Module non activé');
}
if (!$permissiontoread) accessforbidden();


/*
 * View
 */

$title = $langs->trans("Fod");
$help_url = '';
llxHeader('', $title, $help_url);

if($id > 0 && $permissiontoread){
    $object->fetch($id);
    $head = fodPrepareHead($object);
	print dol_get_fiche_head($head, 'historique', $langs->trans("Fod"), -1, $object->picto);

    print $object->historique;
	print '</div>';

	// Generer Document historique FOD
	print dolGetButtonAction($langs->trans('GenererDoc'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_genererHistoriqueFod&confirm=yes&token='.newToken(), '', $permissiontoGenerateHistoriqueFod);
}

// End of page
llxFooter();
$db->close();
