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
 *   	\file       feuilledetemps_card.php
 *		\ingroup    feuilledetemps
 *		\brief      Page to create/edit/view feuilledetemps
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedTask.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedHoliday.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/feuilledetemps.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/lib/feuilledetemps.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/projet_task_time_heure_sup.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/projet_task_time_other.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/deplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/silae.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/regul.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/observationcompta.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedProjet.class.php';
dol_include_once('/feuilledetemps/class/feuilledetemps.class.php');
dol_include_once('/feuilledetemps/lib/feuilledetemps_feuilledetemps.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("feuilledetemps@feuilledetemps", "holidaycustom@holidaycustom", "other", 'projects', 'users', 'companies'));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'feuilledetempscard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$massaction = GETPOST('massaction', 'aZ09');
$mode = GETPOST("mode", 'alpha');
$taskid = GETPOST('taskid', 'int');
$lineid   = GETPOST('lineid', 'int');

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'perweekcard';

$mine = 0;
if ($mode == 'mine') {
	$mine = 1;
}

$socid = 0;

// Initialize technical objects
$object = new FeuilleDeTemps($db);
$extrafields = new ExtraFields($db);
$task = new extendedTask($db);
$projet = New Project($db);
$holiday = new extendedHoliday($db);

$diroutputmassaction = $conf->feuilledetemps->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('feuilledetempscard', 'globalcard')); // Note that conf->hooks_modules contains array

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

$usertoprocess = new User($db);
$usertoprocess->fetch($object->fk_user);

// Check for right
$list_resp_task = $object->listApprover1;
if(in_array($user->id, $list_resp_task[0])){
	$userIsResp = 1;
	if($list_resp_task[1][$user->id] == 0){
		$resp_pas_valide = 1;
	}
	else {
		$resp_pas_valide = 0;
	}
}
else {
	$userIsResp = 0;
}

$list_resp_projet = $object->listApprover2;
if(in_array($user->id, $list_resp_projet[0])){
	$userIsRespProjet = 1;
}
else {
	$userIsRespProjet = 0;
}

$userIsInHierarchy = 0;
if($user->rights->feuilledetemps->feuilledetemps->readHierarchy) {
	$user_hierarchy = $user->getAllChildIds();
	if(in_array($usertoprocess->id, $user_hierarchy)) {
		$userIsInHierarchy = 1;
	}
}

$permissionToVerification = $user->rights->feuilledetemps->feuilledetemps->modify_verification;
$permissiontoread = $user->rights->feuilledetemps->feuilledetemps->read || $userIsInHierarchy || $user->admin || ($userIsResp || $userIsRespProjet || $user->id == $object->fk_user) ;
$permissiontoadd = $userIsResp || $userIsRespProjet || $user->admin || (empty($list_resp_task[0]) && empty($list_resp_projet[0]) && $user->id == $object->fk_user || $permissionToVerification); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->feuilledetemps->feuilledetemps->delete;
$permissionnote = $permissiontoadd; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $permissiontoadd; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->feuilledetemps->multidir_output[isset($object->entity) ? $object->entity : 1].'/feuilledetemps';
$permissiontoaddline = $permissionToVerification;

$displayVerification = ($object->status == $object::STATUS_VERIFICATION || $object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_EXPORTED) && $permissionToVerification;

// Security check (enable the most restrictive one)
if (empty($conf->feuilledetemps->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();


// Gestion des droits de modification des inputs en fonction du statut de la feuille de temps
$modifier_jour_conges = 1;
if($object->status == FeuilleDeTemps::STATUS_DRAFT && $user->id == $usertoprocess->id){
	$modifier = 1;
	$modifier_jour_conges = 0;
}
elseif(($object->status == FeuilleDeTemps::STATUS_DRAFT) && $permissionToVerification){
	$modifier = 1;
}
elseif($object->status == FeuilleDeTemps::STATUS_APPROBATION1 && $userIsResp && $resp_pas_valide) {
	$modifier = 1;
}
elseif($object->status == FeuilleDeTemps::STATUS_APPROBATION2 && $userIsRespProjet) {
	$modifier = 1;
}
elseif($permissionToVerification && $object->status != FeuilleDeTemps::STATUS_VALIDATED && $object->status != FeuilleDeTemps::STATUS_EXPORTED) {
	$modifier = 1;
}
// elseif($object->status != FeuilleDeTemps::STATUS_VALIDATED && $user->rights->feuilledetemps->feuilledetemps->modify) {
// 	$modifier = 1;
// }
else {
	$modifier = 0;
}

// Gestion des dates
$month = dol_print_date($object->date_debut, '%m');
$year = dol_print_date($object->date_debut, '%Y');

$firstdaytoshow = dol_time_plus_duree($object->date_debut, -$conf->global->JOUR_ANTICIPES, 'd');
$firstdaytoshowgmt = dol_mktime(0, 0, 0, dol_print_date($firstdaytoshow, '%m'), dol_print_date($firstdaytoshow, '%d'), dol_print_date($firstdaytoshow, '%Y'), 'gmt');
$lastdaytoshow = $object->date_fin;

$first_day_month = $object->date_debut; 

$nb_jour =  num_between_day($firstdaytoshow, $lastdaytoshow+3600) + 1; 
$ecart_jour = num_between_day($firstdaytoshow, $object->date_debut + 3600);
$month_now = date('m');

for ($idw = 0; $idw < $nb_jour; $idw++) {
	$dayinloopfromfirstdaytoshow = dol_time_plus_duree($firstdaytoshow, $idw, 'd');
	$dayinloopfromfirstdaytoshow_array[$idw] = $dayinloopfromfirstdaytoshow; 
}

$timeSpentWeek = $object->timeDoneByWeek($object->fk_user);
$timeHoliday = $object->timeHolidayWeek($object->fk_user);
$is_semaine_anticipe = 0;
$addcolspan = 0;


// Gestion des types de déplacement de l'utilisateur
$extrafields->fetch_name_optionals_label('donneesrh_Deplacement');
$userField_deplacement = new UserField($db);
$userField_deplacement->id = $object->fk_user;
$userField_deplacement->table_element = 'donneesrh_Deplacement';
$userField_deplacement->fetch_optionals();

$userInDeplacement = 0;
$typeDeplacement = 'none';
$userInGrandDeplacement = 0;
if(!empty($userField_deplacement->array_options['options_d_1']) || !empty($userField_deplacement->array_options['options_d_2']) || !empty($userField_deplacement->array_options['options_d_3']) || !empty($userField_deplacement->array_options['options_d_4'])) {
	$userInDeplacement = 1;
	$typeDeplacement = 'petitDeplacement';
}
if(!empty($userField_deplacement->array_options['options_gd1']) || !empty($userField_deplacement->array_options['options_gd3']) || !empty($userField_deplacement->array_options['options_gd4'])) {
	$userInGrandDeplacement = 1;
	$typeDeplacement = 'grandDeplacement';
}

// Gestion des repas de l'utilisateur
$userRepas = 0;
if($userField_deplacement->array_options['options_panier1'] == '1') {
	$userRepas = 1;
}
elseif($userField_deplacement->array_options['options_panier2'] == '1') {
	$userRepas = 2;
}

// Nombre d'heures par semaine à faire et avant de pouvoir avoir des hs
$extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
$userField = new UserField($db);
$userField->id = $usertoprocess->id;
$userField->table_element = 'donneesrh_Positionetcoefficient';
$userField->fetch_optionals();

$heure_semaine = (!empty($userField->array_options['options_pasdroitrtt']) ?  $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
$heure_semaine = (!empty($userField->array_options['options_horairehebdomadaire']) ? $userField->array_options['options_horairehebdomadaire'] : $heure_semaine);
$heure_semaine_hs = (!empty($userField->array_options['options_pasdroitrtt']) ? $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);


// Gestion des congés et des jours feriés
$all_holiday_validate = 1;
for ($idw = 0; $idw < $nb_jour; $idw++) {
	$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0*
	$dayinloopfromfirstdaytoshowgmt = dol_time_plus_duree($firstdaytoshowgmt, 24*$idw, 'h'); // $firstdaytoshow is a date with hours = 0

	$isavailable[$dayinloopfromfirstdaytoshow] = $holiday->verifDateHolidayForTimestamp($usertoprocess->id, $dayinloopfromfirstdaytoshow, Holiday::STATUS_APPROVED2, array(4));
	$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow] = $holiday->verifDateHolidayForTimestamp($usertoprocess->id, $dayinloopfromfirstdaytoshow, array(Holiday::STATUS_DRAFT, Holiday::STATUS_VALIDATED, Holiday::STATUS_APPROVED2,  Holiday::STATUS_APPROVED1), array(4));	

	if (!$isavailable[$dayinloopfromfirstdaytoshow]['morning'] && !$isavailable[$dayinloopfromfirstdaytoshow]['afternoon'] && !$holiday->holidayTypeNeedHour($isavailable[$dayinloopfromfirstdaytoshow]['code'])) {
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidayallday';
	} elseif(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim'){
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidayallday';
	} elseif (!$isavailable[$dayinloopfromfirstdaytoshow]['morning']) {
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidaymorning';
	} elseif (!$isavailable[$dayinloopfromfirstdaytoshow]['afternoon']) {
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidayafternoon';
	} 

	if (!$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['morning'] && !$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['afternoon']) {
		$css_holiday[$dayinloopfromfirstdaytoshow] .= ' conges'.$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut'].'allday';
	} elseif (!$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['morning']) {
		$css_holiday[$dayinloopfromfirstdaytoshow] .= ' conges'.$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut'].'morning';
	} elseif (!$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['afternoon']) {
		$css_holiday[$dayinloopfromfirstdaytoshow] .= ' conges'.$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut'].'afternoon';
	}

	if($all_holiday_validate && $holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statutfdt'] == 1) {
		$all_holiday_validate = 0;
	}

	if (dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Sam' || dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {	// This is a day is not inside the setup of working days, so we use a week-end css.
		$css[$dayinloopfromfirstdaytoshow] .= ' weekend';
	}

	if($dayinloopfromfirstdaytoshow < $first_day_month){
		$css[$dayinloopfromfirstdaytoshow] .= ' before';
	}

	$test = num_public_holiday($dayinloopfromfirstdaytoshowgmt, $dayinloopfromfirstdaytoshowgmt + 86400, $mysoc->country_code, 0, 0, 0, 0);
	if ($test) {
		$isavailable[$dayinloopfromfirstdaytoshow] = array('morning'=>false, 'afternoon'=>false, 'morning_reason'=>'public_holiday', 'afternoon_reason'=>'public_holiday');
		$css[$dayinloopfromfirstdaytoshow] .= ' public_holiday'; 
	}
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

	$backurlforlist = dol_buildpath('/feuilledetemps/feuilledetemps_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/feuilledetemps/feuilledetemps_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'FEUILLEDETEMPS_FEUILLEDETEMPS_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/actions.inc.php';
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

	if ($action == 'savehs00' && $permissionToVerification) {
		$db->begin();

		$regul = new Regul($db);
		$resregul = $regul->fetchWithoutId($object->date_debut, $usertoprocess->id);
		$regulHeureSup00 = GETPOST('regulHeureSup00') * 3600;
		
		$new_value = formatValueForAgenda('double', $regulHeureSup00 / 3600);
		$old_value = formatValueForAgenda('double', $regul->heure_sup00 / 3600);
		$modification = ($old_value != $new_value ? "<strong>Regul Heure Sup 0%</strong> : $old_value ➔ $new_value" : '');

		$regul->heure_sup00 = $regulHeureSup00;
		
		if($resregul > 0) {
			$res = $regul->update($user);
		}
		elseif ($resregul == 0) {
			$regul->date = $first_day_month;
			$regul->fk_user = $usertoprocess->id;

			$result = $regul->create($user);
		}

		if($res < 0) {
			$error++;
		}
		
		if (!$error) {
			$db->commit();

			if($modification != '') {
				$object->actiontypecode = 'AC_FDT_VERIF';
				$object->actionmsg2 = "Mise à jour des données de vérification de la feuille de temps $object->ref";
				$object->actionmsg = $modification;
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			setEventMessages($regul->error, $regul->errors, 'warnings');
			$action = 'ediths00';
		}
	}

	if ($action == 'savehs25' && $permissionToVerification) {
		$db->begin();

		$regul = new Regul($db);
		$resregul = $regul->fetchWithoutId($object->date_debut, $usertoprocess->id);
		$regulHeureSup25 = GETPOST('regulHeureSup25') * 3600;
		
		$new_value = formatValueForAgenda('double', $regulHeureSup25 / 3600);
		$old_value = formatValueForAgenda('double', $regul->heure_sup25 / 3600);
		$modification = ($old_value != $new_value ? "<strong>Regul Heure Sup 25%</strong> : $old_value ➔ $new_value" : '');

		$regul->heure_sup25 = $regulHeureSup25;
		
		if($resregul > 0) {
			$res = $regul->update($user);
		}
		elseif ($resregul == 0) {
			$regul->date = $first_day_month;
			$regul->fk_user = $usertoprocess->id;

			$result = $regul->create($user);
		}


		if($res < 0) {
			$error++;
		}
		
		if (!$error) {
			$db->commit();

			if($modification != '') {
				$object->actiontypecode = 'AC_FDT_VERIF';
				$object->actionmsg2 = "Mise à jour des données de vérification de la feuille de temps $object->ref";
				$object->actionmsg = $modification;
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			setEventMessages($regul->error, $regul->errors, 'warnings');
			$action = 'ediths25';
		}
	}

	if ($action == 'savehs50' && $permissionToVerification) {
		$db->begin();

		$regul = new Regul($db);
		$resregul = $regul->fetchWithoutId($object->date_debut, $usertoprocess->id);
		$regulHeureSup50 = GETPOST('regulHeureSup50') * 3600;
		
		$new_value = formatValueForAgenda('double', $regulHeureSup50 / 3600);
		$old_value = formatValueForAgenda('double', $regul->heure_sup50 / 3600);
		$modification = ($old_value != $new_value ? "<strong>Regul Heure Sup 50%</strong> : $old_value ➔ $new_value" : '');

		$regul->heure_sup50 = $regulHeureSup50;

		if($resregul > 0) {
			$res = $regul->update($user);
		}
		elseif ($resregul == 0) {
			$regul->date = $first_day_month;
			$regul->fk_user = $usertoprocess->id;

			$result = $regul->create($user);
		}

		if($res < 0) {
			$error++;
		}
		
		if (!$error) {
			$db->commit();

			if($modification != '') {
				$object->actiontypecode = 'AC_FDT_VERIF';
				$object->actionmsg2 = "Mise à jour des données de vérification de la feuille de temps $object->ref";
				$object->actionmsg = $modification;
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			setEventMessages($regul->error, $regul->errors, 'warnings');
			$action = 'ediths50';
		}
	}

	if ($action == 'savevalidator1' && !empty($user->rights->feuilledetemps->changeappro)) {
		$db->begin();

		$object->oldcopy = dol_clone($object);
		$emailTo = '';
		$userstatic = new User($db);
		$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_MODIFYInDolibarr", $object->ref);
		$object->actionmsg = '';
		$modification_validation = '';

		$approver1id = GETPOST('fk_user_approbation1');
		$list_validation1 = $object->listApprover('', 1);

		if (empty($approver1id) && empty($object->listApprover2)) {
			setEventMessages("Il ne peut pas y avoir aucun approbateur", "", 'errors');
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&token='.newToken());
			exit;
		}

		// 1ere étape : Supprimer les 1er validateur nécéssaire
		foreach($list_validation1[2] as $id_user => $user_static){
			if(!in_array($id_user, $approver1id)){
				$res = $object->deleteTaskValidation($id_user, 1);
				
				$prenom = $user_static->firstname;
				$nom = $user_static->lastname;
				$modification_validation .= '<li>Suppression de '.$prenom.' '.$nom.'</li>';

				if($res < 0) {
					$error++;
				}
			}
		}

		// 2e étape : On ajoute les 1er validateur nécéssaire
		foreach($approver1id as $id_user){
			if($id_user > 0 && !array_key_exists($id_user, $list_validation1[0])){
				$res = $object->createTaskValidation($id_user, 1, 1); 
				$userstatic->fetch($id_user);

				$prenom = $userstatic->firstname;
				$nom = $userstatic->lastname;
				$modification_validation .= '<li>Ajout de '.$prenom.' '.$nom.'</li>';

				if(!empty($userstatic->email)){
					$emailTo .= $userstatic->email.', ';
				}

				if($res < 0) {
					$error++;
				}
			}
		}

		if (!$error && !empty($emailTo) && $object->status == FeuilleDeTemps::STATUS_APPROBATION1) {
			$emailTo = rtrim($emailTo, ", ");

			global $dolibarr_main_url_root;
			$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
			$from = 'erp@optim-industries.fr';
			$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
			$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">'.$object->ref.'</a>';
			$heure_manquante = '';
			// if($object->semaineHeuresManquantes()){
			// 	$heure_manquante = '<p style="color: red">Celle-ci contient une ou plusieurs semaines à moins de 35h</p>';
			// }
			$message = $langs->transnoentitiesnoconv("EMailTextFDTApprobation", $link, $heure_manquante);

			$mail = new CMailFile($subject, $emailTo, $from, $message, array(), array(), array(), '', '', 0, 1);

			// Sending the email
			$result = $mail->sendfile();

			if (!$result) {
				setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
				$action = '';
			}
		}

		if (!$error) {	
			if($modification_validation) {
				$object->actionmsg .= '<strong>1ère validation</strong>:<ul>'.$modification_validation."</ul><br/>";
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}

			$db->commit();
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'warnings');
			$action = 'editvalidator1';
		}
	}

	if ($action == 'savevalidator2' && !empty($user->rights->feuilledetemps->changeappro)) {
		$db->begin();

		$object->oldcopy = dol_clone($object);
		$emailTo = '';
		$userstatic = new User($db);
		$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_MODIFYInDolibarr", $object->ref);
		$object->actionmsg = '';
		$modification_validation = '';

		$approver2id = GETPOST('fk_user_approbation2');
		$list_validation2 = $object->listApprover('', 2);

		if (empty($approver2id) && empty($object->listApprover1)) {
			setEventMessages("Il ne peut pas y avoir aucun approbateur", "", 'errors');
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&token='.newToken());
			exit;
		}

		// 1ere étape : Supprimer les 2eme validateur nécéssaire
		foreach($list_validation2[2] as $id_user => $user_static){
			if(!in_array($id_user, $approver2id)){
				$res = $object->deleteTaskValidation($id_user, 2);
				
				$prenom = $user_static->firstname;
				$nom = $user_static->lastname;
				$modification_validation .= '<li>Suppression de '.$prenom.' '.$nom.'</li>';

				if($res < 0) {
					$error++;
				}
			}
		}

		// 2e étape : On ajoute les 2eme validateur nécéssaire
		foreach($approver2id as $id_user){
			if($id_user > 0 && !array_key_exists($id_user, $list_validation2[0])){
				$res = $object->createTaskValidation($id_user, 1, 2); 
				$userstatic->fetch($id_user);

				$prenom = $userstatic->firstname;
				$nom = $userstatic->lastname;
				$modification_validation .= '<li>Ajout de '.$prenom.' '.$nom.'</li>';

				if(!empty($userstatic->email)){
					$emailTo .= $userstatic->email.', ';
				}

				if($res < 0) {
					$error++;
				}
			}
		}

		if (!$error && !empty($emailTo) && $object->status == FeuilleDeTemps::STATUS_APPROBATION2) {
			$emailTo = rtrim($emailTo, ", ");

			global $dolibarr_main_url_root;
			$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
			$from = 'erp@optim-industries.fr';
			$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
			$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">'.$object->ref.'</a>';
			$heure_manquante = '';
			// if($object->semaineHeuresManquantes()){
			// 	$heure_manquante = '<p style="color: red">Celle-ci contient une ou plusieurs semaines à moins de 35h</p>';
			// }
			$message = $langs->transnoentitiesnoconv("EMailTextFDTApprobation2", $link, $heure_manquante);

			$mail = new CMailFile($subject, $emailTo, $from, $message, array(), array(), array(), '', '', 0, 1);

			// Sending the email
			$result = $mail->sendfile();

			if (!$result) {
				setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
				$action = '';
			}
		}

		if (!$error) {	
			if($modification_validation) {
				$object->actionmsg .= '<strong>2ème validation</strong>:<ul>'.$modification_validation."</ul><br/>";
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}

			$db->commit();
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'warnings');
			$action = 'editvalidator2';
		}
	}

	// Actions to send emails
	$triggersendname = 'FEUILLEDETEMPS_FEUILLEDETEMPS_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_FEUILLEDETEMPS_TO';
	$trackid = 'feuilledetemps'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

include DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/tpl/actions_timesheet.tpl.php';


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("FeuilleDeTemps");
$help_url = '';

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	llxHeader('', $title, $help_url, '', '', '', array(''), '', '', '', '<div id="id-right"><!-- Begin div class="fiche" --><div class="fiche tab">');

	print load_fiche_titre($langs->trans("FeuilleDeTemps"), '', 'object_'.$object->picto);

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
	unset($object->fields['observation']);	
	unset($object->fields['prime_astreinte']);	
	unset($object->fields['prime_exceptionnelle']);	
	unset($object->fields['prime_objectif']);	
	unset($object->fields['prime_variable']);	
	unset($object->fields['prime_amplitude']);	
	include DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	llxHeader('', $title, $help_url, '', '', '', array('/core/js/timesheet.js', '/custom/feuilledetemps/core/js/timesheet.js' , '/custom/feuilledetemps/core/js/parameters.php' , '	includes/node_modules/html2canvas/dist/html2canvas.min.js'), '', '', 'classforhorizontalscrolloftabs feuilledetemps');
	//print '<body onresize="redimenssion()">';

	$res = $object->fetch_optionals();

	$head = feuilledetempsPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto, '', '', '');

	$formconfirm = '';

	if ($action == 'delete' && $permissiontodelete) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Delete'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	if ($massaction == 'validate1' && $userIsResp && $resp_pas_valide) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Valider'), 'Voulez vous valider la feuille de temps ?', 'confirm_validate1', '', 0, 1);
	}
	if ($massaction == 'validate2'  && $userIsRespProjet) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Valider'), 'Voulez vous valider la feuille de temps ?', 'confirm_validate2', '', 0, 1);
	}
	if ($massaction == 'verification'  && $permissionToVerification) {
		$question = 'Voulez vous valider la feuille de temps ?';
		$question .= (!GETPOST('all_holiday_validate', 'int') ? '<br><span style="color: #be0000; font-size: initial;"><strong>⚠ Certains congés ne sont pas validés</strong></span>' : '');
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Valider'), $question, 'confirm_verification', '', 0, 1);
	}

	if ($action == 'sendMail'  && $permissionToVerification) {
		$values = array($usertoprocess->id => $usertoprocess->firstname.' '.$usertoprocess->lastname);
		foreach($object->listApprover1[2] as $id => $user_static) {
			$values[$id] = $user_static->firstname.' '.$user_static->lastname;
		}
		foreach($object->listApprover2[2] as $id => $user_static) {
			$values[$id] = $user_static->firstname.' '.$user_static->lastname;
		}
		$formquestion = array();
		$formquestion[] = array('type'=>'multiselect', 'name'=>'sendMailTo', 'label' => 'Destinataire', 'morecss' => 'ml20 minwidth200', 'default' => '', 'values' => $values);
		$formquestion[] = array('label'=>'Message à l\'emetteur de le feuille de temps', 'type'=>'html', 'name'=>'sendMailContent');
		$formconfirm = $object->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('sendMail'), '', 'confirm_sendMail', $formquestion, 0, 0, 500, 1000);
	}

	if ($massaction == 'refus'  && (($userIsResp && $resp_pas_valide) || $userIsRespProjet || ($object->status == $object::STATUS_VERIFICATION && $permissionToVerification))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Refuser'), '', 'confirm_setdraft', array(array('label'=>'Raison du refus', 'type'=>'text', 'name'=>'raison_refus')), 0, 1);
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
	$linkback = '<a href="'.dol_buildpath('/feuilledetemps/feuilledetemps_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	
	$morehtmlref = '<span class="info_fdt inline-block">';
	if($userInDeplacement) {
		$morehtmlref .= 'D1 = '.$userField_deplacement->array_options['options_d_1'].', D2 = '.$userField_deplacement->array_options['options_d_2'].' D3 = '.$userField_deplacement->array_options['options_d_3'].' D4 = '.$userField_deplacement->array_options['options_d_4'];
		$morehtmlref .= '<br><br>';
	}
	if($userInGrandDeplacement) {
		$morehtmlref .= 'GD1 = '.$userField_deplacement->array_options['options_gd1'].', GD2 = '.$userField_deplacement->array_options['options_gd2'].', GD3 = '.$userField_deplacement->array_options['options_gd3'].', GD4 = '.$userField_deplacement->array_options['options_gd4'];
		$morehtmlref .= '<br><br>';
	}	
	$morehtmlref .= 'Les heures de route ne doivent pas être pointées';
	$morehtmlref .= '</span>';
	
	$buttonAction = '';
	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		$buttonAction .= '<span class="tabsAction center">'."\n";
		$parameters = array();

		if($modifier || $permissionToVerification) {
			$buttonAction .= '<input onclick="disableNullInput()" type="submit" class="button button-save button-save-fdt" name="save" form="feuilleDeTempsForm" value="'.dol_escape_htmltag($langs->trans("Save")).'" style="height: 100%;">';
		}

		// Validate
		if ($object->status == $object::STATUS_APPROBATION1) {
			$buttonAction .= dolGetButtonAction('1ère validation', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&massaction=validate1&token='.newToken(), '', $userIsResp && $resp_pas_valide);
			$buttonAction .= dolGetButtonAction($langs->trans('Refus'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&massaction=refus&token='.newToken(), '', $userIsResp && $resp_pas_valide);
		}
		elseif ($object->status == $object::STATUS_APPROBATION2) {
			$buttonAction .= dolGetButtonAction('2ème validation', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&massaction=validate2&token='.newToken(), '', $userIsRespProjet);
			$buttonAction .= dolGetButtonAction($langs->trans('Refus'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&massaction=refus&token='.newToken(), '', $userIsRespProjet);
		}
		elseif ($object->status == $object::STATUS_VERIFICATION) {
			if($permissionToVerification) {
				$buttonAction .= '<a onclick="screenFDT(\''.$_SERVER['PHP_SELF'].'?id='.$object->id.'&massaction=verification&all_holiday_validate='.$all_holiday_validate.'&token='.newToken().'\', \''.$object->ref.'_'.str_replace(array("'", " "), "", $usertoprocess->lastname).'_'.str_replace(array("'", " "), "", $usertoprocess->firstname).'\')" class="butAction classfortooltip" aria-label="Vérification" title="Vérification">Vérification</a>';
			}
			$buttonAction .= dolGetButtonAction($langs->trans('Refus'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&massaction=refus&token='.newToken(), '', $permissionToVerification);
		}

		
		// if ($object->status == $object::STATUS_VALIDATED && $permissionToVerification) {
		// 	$buttonAction .= '<a onclick="screenFDT(\''.$_SERVER['PHP_SELF'].'?id='.$object->id.'&token='.newToken().'\', \''.$object->ref.'_'.$usertoprocess->lastname.'_'.$usertoprocess->firstname.'\')" class="butAction classfortooltip" aria-label="Screen" title="Screen">Screen</a>';
		// }

		if ($object->status == $object::STATUS_VERIFICATION) {
			$buttonAction .= dolGetButtonAction('Envoyer Mail', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=sendMail&token='.newToken(), '', $permissionToVerification, array('attr' => array('target' => '_blank')));
		}

		$buttonAction .= dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', ($permissionToVerification && $object->status != FeuilleDeTemps::STATUS_VALIDATED) || (($user->admin || $user->rights->feuilledetemps->feuilledetemps->modify) && $object->status == FeuilleDeTemps::STATUS_VALIDATED));

		// Delete (need delete permission, or if draft, just need create/modify permission)
		$buttonAction .= dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete);
	
		$buttonAction .= '</span>'."\n";
	}

	dol_banner_tab_custom($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 0, '', $buttonAction);

	// Common attributes
	$keyforbreak = 'prime_astreinte';	// We change column just before this field
	$keyforbreak1 = 'prime_astreinte';	// We change column just before this field
	$keyforbreak2 = 'prime_variable';	// We change column just before this field
	unset($object->fields['observation']);	
	if(!$displayVerification) {
		unset($object->fields['prime_astreinte']);	
		unset($object->fields['prime_exceptionnelle']);	
		unset($object->fields['prime_objectif']);	
		unset($object->fields['prime_variable']);	
		unset($object->fields['prime_amplitude']);	
	}
	include DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';
	print dol_get_fiche_end();
	print '</div>';

	if($action == 'editvalidator1' || $action == 'editvalidator2') {
		print '</form>';
	}

	print '<div class="fiche tab">';

	$form = new Form($db);
	$formother = new FormOther($db);
	$formcompany = new FormCompany($db);
	$formproject = new FormProjets($db);
	$projectstatic = new ExtendedProjet($db);
	$project = new Project($db);
	$thirdpartystatic = new Societe($db);
	$taskstatic = new Task($db);

	$onlyopenedproject = -1; // or -1

	$morewherefilter = ' AND (t.dateo <= "'.$db->idate($lastdaytoshow).'" OR t.dateo IS NULL) AND (t.datee >= "'.$db->idate($firstdaytoshow).'" OR t.datee IS NULL)';

	$tasksarray = $taskstatic->getTasksArray(0, 0, ($project->id ? $project->id : 0), $socid, 0, $search_project_ref, $onlyopenedproject, $morewherefilter, ($object->fk_user ? $object->fk_user : 0), 0, $extrafields); // We want to see all tasks of open project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
	if ($morewherefilter) {	// Get all task without any filter, so we can show total of time spent for not visible tasks
		$tasksarraywithoutfilter = $taskstatic->getTasksArray(0, 0, ($project->id ? $project->id : 0), $socid, 0, '', $onlyopenedproject, '', ($object->fk_user ? $object->fk_user : 0)); // We want to see all tasks of open project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
	}
	$projectsrole = $taskstatic->getUserRolesForProjectsOrTasks($usertoprocess, 0, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
	$tasksrole = $taskstatic->getUserRolesForProjectsOrTasks(0, $usertoprocess, ($project->id ? $project->id : 0), 0, $onlyopenedproject);

	if($modifier && $action != 'ediths00' && $action != 'ediths25' && $action != 'ediths50') {
		print '<form id="feuilleDeTempsForm" name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="addtime">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
		print '<input type="hidden" name="mode" value="'.$mode.'">';
	}
	elseif(($action == 'ediths00' || $action == 'ediths25' || $action == 'ediths50') && $permissionToVerification) {
		print '<form id="feuilleDeTempsForm" name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
		print '<input type="hidden" name="mode" value="'.$mode.'">';

		if($action == 'ediths00') {
			print '<input type="hidden" name="action" value="savehs00">';
		}
		elseif($action == 'ediths25') {
			print '<input type="hidden" name="action" value="savehs25">';
		}
		elseif($action == 'ediths50') {
			print '<input type="hidden" name="action" value="savehs50">';
		}
	}

	print '<div class="clearboth"></div>';

	// Affichage des primes
	// if($displayVerification) {
	// 	print '<table class="tagtable liste">';
	// 		print '<tr class="liste_titre">';
	// 			print '<th class="center bold fieldname_prime_astreinte">';
	// 				print $langs->trans($object->fields['prime_astreinte']['label']);
	// 			print '</th>';
	// 			print '<th class="center bold fieldname_prime_exceptionnelle">';
	// 				print $langs->trans($object->fields['prime_exceptionnelle']['label']);
	// 			print '</th>';
	// 			print '<th class="center bold fieldname_prime_objectif">';
	// 				print $langs->trans($object->fields['prime_objectif']['label']);
	// 			print '</th>';
	// 			print '<th class="center bold fieldname_prime_variable">';
	// 				print $langs->trans($object->fields['prime_variable']['label']);
	// 			print '</th>';
	// 			print '<th class="center bold fieldname_prime_amplitude">';
	// 				print $langs->trans($object->fields['prime_amplitude']['label']);
	// 			print '</th>';
	// 		print '</tr>';

	// 		print '<tr class="nostrong">';
	// 			print '<td class="center valuefield fieldname_prime_astreinte">';
	// 				print $object->showInputField($object->fields['prime_astreinte'], 'prime_astreinte', $object->prime_astreinte, '', '', '', 0);
	// 			print '</td>';
	// 			print '<td class="center valuefield fieldname_prime_exceptionnelle">';
	// 				print $object->showInputField($object->fields['prime_exceptionnelle'], 'prime_exceptionnelle', $object->prime_exceptionnelle, '', '', '', 0);
	// 			print '</td>';
	// 			print '<td class="center valuefield fieldname_prime_objectif">';
	// 				print $object->showInputField($object->fields['prime_objectif'], 'prime_objectif', $object->prime_objectif, '', '', '', 0);
	// 			print '</td>';
	// 			print '<td class="center valuefield fieldname_prime_variable">';
	// 				print $object->showInputField($object->fields['prime_variable'], 'prime_variable', $object->prime_variable, '', '', '', 0);
	// 			print '</td>';
	// 			print '<td class="center valuefield fieldname_prime_amplitude">';
	// 				print $object->showInputField($object->fields['prime_amplitude'], 'prime_amplitude', $object->prime_amplitude, '', '', '', 0);
	// 			print '</td>';
	// 		print '</tr>';
	// 	print '</table>';
	// }
	
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'" id="tablelines_fdt">'."\n";

	print '<thead>';
	print '<tr class="liste_titre card fixed" style="height: 79px !important; top: 0px;">';
	print '<th class="fixed" colspan="2" style="min-width: 500px;"><button type="button" title="Plein écran" id="fullScreen" name="fullScreen" class="nobordertransp button_search_x"><span class="fa fa-expand"></span></button>';
	print $usertoprocess->getNomUrl(1);
	print '</th>';


	// Affichage des jours de la semaine
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

		if(dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
			print '<th style="min-width: 90px; border-left: 1px solid var(--colortopbordertitle1) !important; border-right: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></th>';
		}

		print '<th width="9%" style="min-width: 90px" align="center" class="bold hide'.$idw.'">';
		print dol_print_date($dayinloopfromfirstdaytoshow, '%a');
		print '<br>'.dol_print_date($dayinloopfromfirstdaytoshow, 'dayreduceformat').'</th>';

	}
	print '<th class="total_title fixed" width="9%" style="min-width: 90px"><strong>TOTAL</strong></th>';
	print '</tr>';
	

	// Affichage de la ligne avec le total de chaque semaine
	print '<tr class="liste_titre fixed">';
	print '<th class="fixed" colspan="'.(2 + $addcolspan).'"></th>';

	$semaine = 1;
	for ($idw = 0; $idw < $nb_jour; $idw++) {			
		$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];
		$ecart_lundi = ecart_lundi($tmpday);
		$weekNumber = date("W", $tmpday);
	
		if ($idw == 0) {
			$taille = 7-$ecart_lundi;
		}
		elseif (dol_print_date($tmpday, '%a') == 'Lun' && $nb_jour - $idw < 7 && $idw-$ecart_lundi > 23 && dol_print_date($lastdaytoshow, '%a') != 'Dim'){
			$taille = $nb_jour - $idw;
		}
		elseif (dol_print_date($tmpday, '%a') == 'Lun' && $idw != 0) {	
			$taille = 7;
			$date = dol_time_plus_duree($tmpday, 7, 'd');

			if($first_day_month == $tmpday){
				print '<th style="min-width: 90px; border-left: 1px solid var(--colortopbordertitle1); border-bottom: none; border-top: none !important; z-index:1;" width="9%"></th>';
			}
			elseif($first_day_month > $tmpday && $first_day_month < $date){
				$taille++;
				$idw--;
			}
		}

		$premier_jour = $idw;
		$dernier_jour = $idw+$taille-1;

		print '<th class="liste_total_semaine_'.$semaine.'" align="center" colspan='.$taille.'><strong>Semaine '.$weekNumber.' : <span class="totalSemaine" name="totalSemaine'.$weekNumber.'" id="totalSemaine'.$semaine.'_'.$premier_jour.'_'.$dernier_jour.'">&nbsp</span></strong></th>';
		$semaine++;
		$idw += $taille - 1;
	}
	print '<th class="total_week fixed"></th>';
	print "</tr>";


	// Affichage de la ligne des congés
	$holiday = new extendedHoliday($db);
	$typeleaves = $holiday->getTypesNoCP(-1, -1);
	$arraytypeleaves = array();
	foreach ($typeleaves as $key => $val) {
		$labeltoshow = $val['code'];
		$arraytypeleaves[$val['rowid']] = $labeltoshow;
	}	
		
	$conges_texte = $holiday->getArrayHoliday($usertoprocess->id, 0, 1);

	print '<tr class="nostrong liste_titre fixed conges">';
		print '<th colspan="2" class="fixed">';
		if($displayVerification) {
			print '<input type="checkbox"'.(!$modifier ? 'disabled' : '').' id="selectAllHoliday" onclick="toggleCheckboxesHoliday(this)"> ';
		}
		print '<strong>Congés</strong>';
		print $form->textwithpicto('', $conges_texte);
		print '</th>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
				print '<th style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></th>';
			}

			if(!empty($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['rowid'])) {
				$holiday->fetch((int)$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['rowid']);
				$numberDay = (num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) ? num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) : 1);
				$droit_rtt = $holiday->holidayTypeDroitRTT();
				
				if(!empty($holiday->array_options['options_hour'])) {
					$durationHoliday = $holiday->array_options['options_hour'];
				}
				else {
					$nbDay = floor(num_open_day($holiday->date_debut_gmt, $holiday->date_fin_gmt, 0, 1, $holiday->halfday));
					$duration_hour = (dol_print_date($holiday->date_fin, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt']) ? $nbDay * 7 * 3600 : $nbDay * $conf->global->HEURE_JOUR * 3600);
					if(!empty($userField->array_options['options_pasdroitrtt']) && ($holiday->halfday == 1 || $holiday->halfday == -1)) {
						$duration_hour += 3.5 * 3600;
					}
					elseif(in_array($holiday->fk_type, $droit_rtt) && ($holiday->halfday == 1 || $holiday->halfday == -1)) {
						$duration_hour += ($conf->global->HEURE_JOUR / 2) * 3600;
					}
					elseif(!in_array($holiday->fk_type, $droit_rtt) && ($holiday->halfday == 1 || $holiday->halfday == -1)) {
						$duration_hour += $conf->global->HEURE_DEMIJOUR_NORTT * 3600;
					}
					$durationHoliday = $duration_hour;
				}

				if($idw + $numberDay > $nb_jour) {
					$numberDay = $nb_jour - $idw;
				}

				print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow] ? $css_holiday[$dayinloopfromfirstdaytoshow] : '').' statut'.$holiday->array_options['options_statutfdt'].'" colspan="'.($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month && ($dayinloopfromfirstdaytoshow_array[$idw + $numberDay] > $first_day_month || empty($dayinloopfromfirstdaytoshow_array[$idw + $numberDay]))? $numberDay + 1 : $numberDay).'">';

				if($displayVerification) {
					print '<input type="checkbox"'.($holiday->array_options['options_statutfdt'] == 3 || !$modifier ? ' disabled' : '').' name="holiday_valide['.$idw.']" id="holiday_valide['.$idw.']"'.($holiday->array_options['options_statutfdt'] != 1 ? ' checked' : '0').'> ';
				}
				print $holiday->getNomUrlBlank(2)." ".convertSecondToTime($durationHoliday, 'allhourmin');
				print ' '.$form->selectarray('holiday_type['.$idw.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$idw.']"'.(!$modifier  ? 'disabled' : ''), 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
				if($modifier && $action != 'ediths00' && $action != 'ediths25' && $action != 'ediths50') {
					print '<input type="hidden" name="holiday_id['.$idw.']"  id="holiday_id['.$idw.']" value="'.$holiday->id.'">';
				}
				$idw += $numberDay - 1;
			}
			else {
				print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow] ? ' '.$css_holiday[$dayinloopfromfirstdaytoshow] : '').'">';
			}

			print '</th>';
		}
		print '<th class="liste_total center fixed total_holiday"></th>';
	print '</tr></thead>';


	// By default, we can edit only tasks we are assigned to
	$restrictviewformytask = ((!isset($conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED)) ? 2 : $conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED);
	if (count($tasksarray) > 0) {
		$j = 0;
		$level = 0;

		// Récupération des temps précédent et suivant qui ne sont pas affichés
		$temps_prec = $object->getTempsSemainePrecedente($firstdaytoshow, $usertoprocess);
		$temps_suiv = $object->getTempsSemaineSuivante($lastdaytoshow, $usertoprocess);
		$temps_prec_hs25 = $object->getHS25SemainePrecedente($firstdaytoshow, $usertoprocess);
		$temps_suiv_hs25 = $object->getHS25SemaineSuivante($lastdaytoshow, $usertoprocess);
		$temps_prec_hs50 = $object->getHS50SemainePrecedente($firstdaytoshow, $usertoprocess);
		$temps_suiv_hs50 = $object->getHS50SemaineSuivante($lastdaytoshow, $usertoprocess);

		// Récupération des notes
		$notes = $task->fetchAllNotes($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);

		// Récupération des autres temps (compagnonnage/heure de nuit/heure de route/epi respiratoire)
		$projet_task_time_other = New Projet_task_time_other($db);
		$otherTime = $projet_task_time_other->getOtherTime($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);

		// Calculate total for all tasks
		$listofdistinctprojectid = array(); // List of all distinct projects
		if (is_array($tasksarraywithoutfilter) && count($tasksarraywithoutfilter)) {
			foreach ($tasksarraywithoutfilter as $tmptask) {
				$listofdistinctprojectid[$tmptask->fk_project] = $tmptask->fk_project;
			}
		}
		$totalforeachday = array();
		$timeSpentMonth = array();
		foreach ($listofdistinctprojectid as $tmpprojectid) {
			$projectstatic->id = $tmpprojectid;
			$projectstatic->loadTimeSpent_month($firstdaytoshow, 0, $usertoprocess->id); // Load time spent from table projet_task_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
			$timeSpentMonth[$projectstatic->id]['weekWorkLoad'] = $projectstatic->weekWorkLoad;
			$timeSpentMonth[$projectstatic->id]['weekWorkLoadPerTask'] = $projectstatic->weekWorkLoadPerTask;
			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];
				$totalforeachday[$tmpday] += $projectstatic->weekWorkLoad[$tmpday];
			}
		}

		// Affichage de l'interieur du tableau
		$totalforvisibletasks = FeuilleDeTempsLinesPerWeek($j, $firstdaytoshow, $lastdaytoshow, $usertoprocess, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restrictviewformytask, $isavailable, 0, $arrayfields, $extrafields, 
															$nb_jour, $modifier, $css, $ecart_jour, $typeDeplacement, $dayinloopfromfirstdaytoshow_array, $modifier_jour_conges, 
															$temps_prec, $temps_suiv, $temps_prec_hs25, $temps_suiv_hs25, $temps_prec_hs50, $temps_suiv_hs50, $notes, $otherTime, $timeSpentMonth, $timeSpentWeek, $month_now, $timeHoliday, $heure_semaine, $heure_semaine_hs, $usertoprocess);

		// Is there a diff between selected/filtered tasks and all tasks ?
		$isdiff = 0;
		if (count($totalforeachday)) {
			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];
				$timeonothertasks = ($totalforeachday[$tmpday] - $totalforvisibletasks[$tmpday]);
				if ($timeonothertasks) {
					$isdiff = 1;
					break;
				}
			}
		}

		// There is a diff between total shown on screen and total spent by user, so we add a line with all other cumulated time of user
		if ($isdiff) {
			print '<tr class="oddeven othertaskwithtime">';
			print '<td class="nowrap fixed" colspan="'.(2 + $addcolspan).'">'.$langs->trans("OtherFilteredTasks").'</td>';

			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

				if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
					print '<td></td>';
				}

				print '<td class="center hide'.$idw.' '.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
				$timeonothertasks = ($totalforeachday[$dayinloopfromfirstdaytoshow] - $totalforvisibletasks[$dayinloopfromfirstdaytoshow]);
				if ($timeonothertasks) {
					print '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center smallpadd time_'.$idw.'" size="2" disabled id="timespent[-1]['.$idw.']" name="task[-1]['.$idw.']" value="';
					print convertSecondToTime($timeonothertasks, 'allhourmin');
					print '"></span>';
				}
				print '</td>';
			}

			print ' <td class="liste_total fixed"></td>';
			print '</tr>';
		}

		// Affichage du total
		if ($conf->use_javascript_ajax) {
			print '<tr class="trforbreak">';
			print '<td class="fixed" colspan="'.(2 + $addcolspan).'">';
			print $langs->trans("Total");
			print '<span class="opacitymediumbycolor">  - '.$langs->trans("ExpectedWorkedHours").': <strong>'.price($usertoprocess->weeklyhours, 1, $langs, 0, 0).'</strong></span>';
			print '</td>';


			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

				if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
					print '<td style="border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;"></td>';
				}

				$total = (convertSecondToTime($totalforeachday[$dayinloopfromfirstdaytoshow], 'allhourmin') != '0' ? convertSecondToTime($totalforeachday[$dayinloopfromfirstdaytoshow], 'allhourmin') : '00:00');
				print '<td class="liste_total hide'.$idw.($total != '00:00' ? ' bold' : '').'" align="center"><div class="totalDay'.$idw.'" '.(!empty($style) ? $style : '').'>'.$total.'</div></td>';
			}
			print '<td class="liste_total center fixed"><div class="totalDayAll">&nbsp;</div></td>';
			print '</tr>';
		}

	} else {
		print '<tr><td colspan="'.(4 + $addcolspan + $nb_jour).'"><span class="opacitymedium">'.$langs->trans("NoAssignedTasks").'</span></td></tr>';
	}

	if($displayVerification) {
		FeuilleDeTempsVerification($firstdaytoshow, $lastdaytoshow, $nb_jour, $usertoprocess, $css, $css_holiday, $ecart_jour, !$modifier, $userInDeplacement, $userInGrandDeplacement, $dayinloopfromfirstdaytoshow_array);
	}
	else {
		FeuilleDeTempsDeplacement($firstdaytoshow, $lastdaytoshow, $nb_jour, $usertoprocess, $css, $ecart_jour, !$modifier, $addcolspan, $dayinloopfromfirstdaytoshow_array, $month_now);
	}
	
	print "</table>";
	print '</div>';

	// Tableau Full Screen
	print '<div id="fullscreenContainer" tabindex="-1" role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" style="height: calc(100vh - 62px); width: calc(100vw - 9px); top: 53px; display: none;">';
	print '<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">';
	print '<span id="ui-id-1" class="ui-dialog-title">Feuille de temps</span>';
	print '<button type="button" id="closeFullScreen" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close">';
	print '<span class="ui-button-icon ui-icon ui-icon-closethick"></span>';
	print '<span class="ui-button-icon-space"></span>Close</button></div>';

	print '<div id="dialog-confirm" style="width: auto; min-height: 0px; max-height: none; padding: unset; height: calc(-95px + 100vh); width: calc(-9px + 100vw);" class="ui-dialog-content ui-widget-content">';
	print '<div id="tableau"></div>';
	print '</div></div>';

	if($modifier && $action != 'ediths00' && $action != 'ediths25' && $action != 'ediths50') {
		print '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.count($tasksarray).'"/>'."\n";
		print '</form>'."\n\n";
	}
	elseif($permissionToVerification && ($action == 'ediths00' || $action == 'ediths25' || $action == 'ediths50')) {
		print '</form>'."\n\n";
	}
	elseif($action == 'savevalidator1') {
		print '</form>'."\n\n";
	}


	$modeinput = 'hours';

	if ($conf->use_javascript_ajax) {
		$lastday = dol_time_plus_duree($firstdaytoshow, $nb_jour-1, 'd');
		$temps_prec = $object->getTempsSemainePrecedente($firstdaytoshow, $usertoprocess);
		$temps_suiv = $object->getTempsSemaineSuivante($lastdaytoshow, $usertoprocess);

		print "\n<!-- JS CODE TO ENABLE Tooltips on all object with class classfortooltip -->\n";
		print '<script type="text/javascript">'."\n";
		print "jQuery(document).ready(function () {\n";
		// print '		jQuery(".timesheetalreadyrecorded").tooltip({
		// 				show: { collision: "flipfit", effect:\'toggle\', delay:50 },
		// 				hide: { effect:\'toggle\', delay: 50 },
		// 				tooltipClass: "mytooltip",
		// 				content: function () {
		// 					return \''.dol_escape_js($langs->trans("TimeAlreadyRecorded", $usertoprocess->getFullName($langs))).'\';
		// 				}
		// 			});'."\n";

		for($idw = 0; $idw < $nb_jour; $idw++) {
			$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];
			//print ' updateTotalLoad_TS('.$idw.',\''.$modeinput.'\','.$nb_jour.');';

			if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-06-03' && $tmp_heure_semaine == $conf->global->HEURE_SEMAINE) {
				$tmp_heure_semaine = 35;
			}
			else {
				$tmp_heure_semaine = $heure_semaine;
			}

			if(dol_print_date($tmpday, '%a') == 'Dim' || dol_print_date($tmpday, '%d/%m/%Y') == dol_print_date($lastdaytoshow, '%d/%m/%Y')) {
				$weekNumber = date("W", $tmpday);
				if($weekNumber == date("W", $firstdaytoshow)) {
					print ' updateTotalWeek('.($temps_prec ? $temps_prec : 0).', 0, \''.$weekNumber.'\', '.($timeHoliday[$weekNumber] ? $timeHoliday[$weekNumber] : 0).', '.$tmp_heure_semaine.');';
				}
				elseif($weekNumber == date("W", $lastdaytoshow)) {
					print ' updateTotalWeek(0, '.($temps_suiv ? $temps_suiv : 0).', \''.$weekNumber.'\', '.($timeHoliday[$weekNumber] ? $timeHoliday[$weekNumber] : 0).', '.$tmp_heure_semaine.');';
				}
				else {
					print ' updateTotalWeek(0, 0, \''.$weekNumber.'\', '.($timeHoliday[$weekNumber] ? $timeHoliday[$weekNumber] : 0).', '.$tmp_heure_semaine.');';
				}
			}
		}
		print ' updateAllTotalLoad_TS(\''.$modeinput.'\','.$nb_jour.', '.$ecart_jour.');';
		print "\n});\n";
		//print " redimenssion();";
		print '</script>';
	}
	
}

// End of page
llxFooter();
$db->close();