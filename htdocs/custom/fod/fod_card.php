<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *   	\file       fod_card.php
 *		\ingroup    fod
 *		\brief      Page to create/edit/view fod
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
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/html.extendedform.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/extendeduser.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/data_intervenant.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/core/lib/functions.lib.php';
dol_include_once('/fod/class/fod.class.php');
dol_include_once('/fod/lib/fod_fod.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("fod@fod", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'fodcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOST('lineid', 'int');
$userid = GETPOST('user', 'int');

// Initialize technical objects
$object = new Fod($db);
$extrafields = new ExtraFields($db);
$fod_user = new Fod_user($db);
$diroutputmassaction = $conf->fod->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('fodcard', 'globalcard')); // Note that conf->hooks_modules contains array
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

if($object->id > 0) {
	// Vérifie si l'utilisateur actif est un intervenant 
	$user_interv = 0;
	$liste_intervenant = $object->listIntervenantsForFod();
	if (!empty($liste_intervenant)){
		foreach($liste_intervenant as $intervenant){
			if ($intervenant->id == $user->id){
				$user_interv = 1;
				break;
			}
		}
	}
}

$user_group = New UserGroup($db);
$user_group->fetch(17);
$liste_rd = $user_group->listUsersForGroup();
// Vérifie si l'utilisateur actif est un RD 
$userIsRd = 0;
foreach($liste_rd as $rd){
	if ($rd->id == $user->id){
		$userIsRd = 1;
		break;
	}
}

// Vérifie si l'utilisateur actif est un RA du projet
$userIsRaf = 0;
$projet = New Project($db);
if($action == "add"){
	$projet->fetch(GETPOST('fk_project','int'));
}
elseif (!empty($object->fk_project)){
	$projet->fetch($object->fk_project);
} 
else $projet = 0;

if ($projet->id > 0){
	$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
	if(in_array($user->id, $liste_chef_projet)){
		$userIsRaf = 1;
	}
}


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


// Vérifie si l'utilisateur actif est un RA
$user_group = New UserGroup($db);
$user_group->fetch(8);
$liste_RA = $user_group->listUsersForGroup();
$userRA = 0;
foreach($liste_RA as $ra){
	if ($ra->id == $user->id) {
		$userRA = 1;
		break;
	}
}


$permissiontoread = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user_interv || $userPCR || $userIsRaf || $user->admin || $user->rights->fod->fod->readAll;	
$permissiontoadd = $user->rights->fod->fod->write || $userIsRaf || $user->admin; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->fod->fod->delete || $user->admin;
$permissiontocancel = $userIsRd || $userPCR || $user->admin;
$permissionToProlongerFOD = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf  || $userPCR || $userIsRaf || $user->admin;
$permissionToModifierAOA = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $userPCR || $userIsRaf || $user->admin;
$permissionToBackDraft = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $userPCR || $userIsRaf || $user->admin;
$permissionToModifierFOD = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $userPCR || $userIsRaf || $user->admin;
$permissionToRefuserFOD = $user->id == $object->fk_user_pcr || $userPCR || $user->admin;
$permissionToBilan = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $userPCR || $userIsRaf || $user->admin;
$permissionToClone = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $userPCR || $userIsRaf || $user->admin;
if ($object->status == Fod::STATUS_VALIDATED){
	$permissionToAddIntervenant = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->admin || $userPCR || $userIsRaf;
}
else {
	$permissionToAddIntervenant = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->admin || $userPCR || $userIsRaf;
}
$permissionToEditandDeleteIntervenant = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->admin  || $userPCR || $userIsRaf;
$permissiontoGenerateDocFod = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id  || $userPCR || $userIsRaf || $user->admin;	
$permissionToEditDocClient = $object->fk_user_raf == $user->id || $object->fk_user_rsr == $user->id || $object->fk_user_pcr == $user->id  || $userPCR || $user->admin;
$permissionToEditChamps = $userPCR || $userRA || $user->admin;

// Security check
if (empty($conf->fod->enabled)) {
	accessforbidden('Module non activé');
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->fod->enabled)) accessforbidden();
if (!$permissiontoread && !$permissiontoadd) accessforbidden();

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

	$backurlforlist = dol_buildpath('/fod/fod_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/fod/fod_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'FOD_FOD_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/custom/fod/core/actions_addupdatedelete.inc.php';
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

}

// Add/Remove intervenant into FOD
if ($action == 'addintervenant' || ($action == 'confirmremoveintervenant' && $confirm == 'yes')) {

	if ($permissionToAddIntervenant || $permissionToEditandDeleteIntervenant) {
		if ($userid > 0) {
			$object->fetch($id);
			$edituser = new ExtendedUser($db);
			$edituser->fetch($userid);
			
			if ($action == 'addintervenant' && $permissionToAddIntervenant) {
				if (empty(GETPOST("timelinemonth", 'int')) || empty(GETPOST("timelineday", 'int')) || empty(GETPOST("timelineyear", 'int'))) {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Date")), null, 'errors');
					$error++;
				}
				$date = dol_mktime(-1, -1, -1, GETPOST("timelinemonth", 'int'), GETPOST("timelineday", 'int'), GETPOST("timelineyear", 'int'));

				$date_fin = (!empty($object->date_fin_prolong) ?  $object->date_fin_prolong : $object->date_fin);
				if($date < $object->date_debut || $date > $date_fin) {
					setEventMessages("La date d'entrée doit être comprise entre la date de début et de fin de la FOD", null, 'errors');
					$error++;
				}

				if(GETPOST('contrat') == -1 || empty(GETPOST('contrat'))){
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Contrat")), null, 'errors');
					$error++;
				}
				else if((GETPOST('duree_contrat') == -1 || empty(GETPOST('duree_contrat'))) && $fod_user->fields['contrat']['arrayofkeyval'][GETPOST('contrat')] != 'CDI'){
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DureeContrat")), null, 'errors');
					$error++;
				}

				if (!empty(GETPOST('duree_contrat')) && GETPOST('duree_contrat')<= 0){
					setEventMessages($langs->trans('ErrorFormat', $langs->transnoentitiesnoconv("DureeContrat")), null, 'errors');
					$error++;
				}

				if($object->status == $object::STATUS_VALIDATED){
					$notrigger = 0;
				}
				else $notrigger = 1;

				if (!$error) {
					$fod_user->fk_user = $userid;
					$fod_user->fk_fod = $id;
					$fod_user->contrat = GETPOST('contrat');
					$fod_user->duree_contrat = (GETPOST('duree_contrat') != -1 ? GETPOST('duree_contrat') : null);
					$fod_user->date_entree = $date;
					$fod_user->statut = Fod_user::STATUS_AUTORISE;
					$fod_user->visa = 2;
					$fod_user->prise_en_compte_fin = 2;
					$result = $fod_user->create($user, $notrigger);
				}
			}
			if ($action == 'confirmremoveintervenant' && $confirm == 'yes' && $permissionToEditandDeleteIntervenant) {
				$fod_user->fetch($lineid);
				$result = $fod_user->delete($user);
				//$result = $edituser->RemoveFromFod($object->id);
			}

			if ($result > 0) {
				if($action == 'addintervenant'){
					if ($edituser->getDose12mois() + $edituser->getDoseMaxFod($object) >= $edituser->getCdd()){
						$fod_user->statut = Fod_user::STATUS_NA_AJOUT_INTERV;
					}
					$fod_user->update($user);

					if (empty($object->effectif_optimise)){
						$effectif = $object->effectif;
					}
					else {
						$effectif = $object->effectif_optimise;
					}
					
					if(count($object->listIntervenantsForFod()) > $effectif){
						if (empty($object->effectif_optimise)){
							$object->effectif++;
						}
						else {
							$object->effectif_optimise++;
						}

						$object->update($user);
						$fod_user = New Fod_user($db);

						foreach($object->listIntervenantsForFod() as $intervenant){
							$edituser->fetch($intervenant->id);
							if ($edituser->getDoseFod($object) > $edituser->getDoseMaxFod($object)){
								$fod_user_id = $fod_user->getIdWithUserAndFod($edituser->id, $object->id);
								$fod_user->fetch($fod_user_id);
								$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddFOD);
								$fod_user->statut = $new_statut;
								$fod_user->update($user, true);
							}
							else if ($edituser->getDose12mois() + $edituser->getDoseMaxFod($object) >= $edituser->getCdd()){
								$fod_user_id = $fod_user->getIdWithUserAndFod($edituser->id, $object->id);
								$fod_user->fetch($fod_user_id);
								$new_statut = Fod_user::STATUS_NA_AJOUT_INTERV;
								$fod_user->statut = $new_statut;
								$fod_user->update($user, true);
							}
						}
					}
				}

				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				setEventMessages($langs->trans($object->error), null, 'errors');
				$error++;
			}
		}
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}

// Modifier un intervenant 
if (($action == 'updateintervenant') && !$cancel && $permissionToEditandDeleteIntervenant) {
	$error = 0;

	if (!GETPOST("date_entreemonth") && !GETPOST("date_entreeday") && !GETPOST("date_entreeyear")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}
	$date_entree = dol_mktime(-1, -1, -1, GETPOST("date_entreemonth", 'int'), GETPOST("date_entreeday", 'int'), GETPOST("date_entreeyear", 'int'));

	if (!GETPOST("contrat")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Contrat")), null, 'errors');
		$error++;
	}

	if (!empty(GETPOST('duree_contrat')) && GETPOST('duree_contrat')<= 0){
		setEventMessages($langs->trans('ErrorFormat', $langs->transnoentitiesnoconv("DureeContrat")), null, 'errors');
		$error++;
	}
	
	$date_fin = (!empty($object->date_fin_prolong) ?  $object->date_fin_prolong : $object->date_fin);
	if($date_entree < $object->date_debut || $date_entree > $date_fin) {
		setEventMessages("La date d'entrée doit être comprise entre la date de début et de fin de la FOD", null, 'errors');
		$error++;
	}

	if (!empty(GETPOST("date_sortiemonth")) && !empty(GETPOST("date_sortieday")) && !empty(GETPOST("date_sortieyear"))) {
		$date_sortie = dol_mktime(-1, -1, -1, GETPOST("date_sortiemonth", 'int'), GETPOST("date_sortieday", 'int'), GETPOST("date_sortieyear", 'int'));
		$date_fin = (!empty($object->date_fin_prolong) ?  $object->date_fin_prolong : $object->date_fin);
		if($date_sortie < $object->date_debut || $date_sortie > $date_fin) {
			setEventMessages('La date de sortie doit être comprise entre la date de début et de fin de la FOD', null, 'errors');
			$error++;
		}
		if($date_sortie < $date_entree) {
			setEventMessages("La date de sortie ne peut pas être avant la date d'entrée", null, 'errors');
			$error++;
		}
	}
	

	$modification = '';
	if (!$error) {
		if(!empty($lineid) && $lineid > 0){
			$fod_user->fetch($lineid);
			if(!empty(GETPOST("contrat")) && $fod_user->contrat != GETPOST("contrat")){
				$prefix = $fod_user->fields['contrat']['arrayofkeyval'];
				$modification .= '<li>'.$langs->trans('Contrat').' : '.$prefix[$fod_user->contrat].' <strong>-></strong> '.$prefix[GETPOST("contrat")].'</li>';
			}
			$fod_user->contrat = GETPOST("contrat");

			if(!empty(GETPOST("date_entreemonth", 'int')) && !empty(GETPOST("date_entreeday", 'int')) && !empty(GETPOST("date_entreeyear", 'int')) && $fod_user->date_entree != $date_entree){
				$modification .= '<li>'.$langs->trans("Date d'entrée").' : '.dol_print_date($fod_user->date_entree, '%d/%m/%Y').' <strong>-></strong> '.dol_print_date($date_entree, '%d/%m/%Y').'</li>';
			}
			$fod_user->date_entree = $date_entree;

			if (!empty(GETPOST("duree_contrat")) && $fod_user->duree_contrat != GETPOST("duree_contrat")){
				$modification .= '<li>'.$langs->trans('Durée de contrat').' : '.$fod_user->duree_contrat.' <strong>-></strong> '.GETPOST("duree_contrat").'</li>';
				$fod_user->duree_contrat = GETPOST("duree_contrat");
			}

			if (!empty(GETPOST("date_sortiemonth")) && !empty(GETPOST("date_sortieday")) && !empty(GETPOST("date_sortieyear"))) {
				if($fod_user->date_sortie != $date_sortie){
					$modification .= '<li>'.$langs->trans('Date de sortie').' : '.dol_print_date($fod_user->date_sortie, '%d/%m/%Y').' <strong>-></strong> '.dol_print_date($date_sortie, '%d/%m/%Y').'</li>';
				}
				$fod_user->date_sortie = $date_sortie;
			}
			else $fod_user->date_sortie = NULL;
			
			$edituser = new ExtendedUser($db);
			$edituser->fetch($fod_user->fk_user);
			if ($edituser->getDoseFod($object) > $edituser->getDoseMaxFod($object)){
				$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddFOD);
				$fod_user->statut = $new_statut;
			}
			else if ($edituser->getDoseFod($object) <= $edituser->getDoseMaxFod($object)){
				$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_cddFOD);
				$fod_user->statut = $new_statut;
			}

			if ($edituser->getDose12mois() + $edituser->getDoseMaxFod($object) >= $edituser->getCdd() && ($object->status == Fod::STATUS_DRAFT || $object->status == Fod::STATUS_AOA || $object->status == Fod::STATUS_VALIDATEDRA || $object->status == Fod::STATUS_VALIDATEDRARSR || $object->status == Fod::STATUS_VALIDATEDRSR)){
				$new_statut = Fod_user::STATUS_NA_AJOUT_INTERV;
				$fod_user->statut = $new_statut;
			}
			else if ($edituser->getDose12mois() + $edituser->getDoseMaxFod($object) < $edituser->getCdd() && ($object->status == Fod::STATUS_DRAFT || $object->status == Fod::STATUS_AOA || $object->status == Fod::STATUS_VALIDATEDRA || $object->status == Fod::STATUS_VALIDATEDRARSR || $object->status == Fod::STATUS_VALIDATEDRSR)){
				$new_statut = Fod_user::STATUS_AUTORISE;
				$fod_user->statut = $new_statut;
			}

			$result = $fod_user->update($user);

			if ($result >= 0) {
				$user_static = New User($db);
				$user_static->fetch($fod_user->fk_user);
				if (!empty($modification)){
					$object->historique .= '<span style="color: blue;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Modification de l'intervenant ".$user_static->firstname.' '.$user_static->lastname." par ".$user->firstname.' '.$user->lastname.' :<br><ul>'.$modification.'</span></ul>';
				}
				$result = $object->update($user);

				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans($fod_user->error), null, 'errors');
				$error++;
			}
		}
		else {
			setEventMessages($langs->trans($fod_user->error), null, 'errors');
		}
	} 
	else {
		$action = '';
	}
}

// Prise en compte intervenant
if (($action == 'priseencompte') && $user_interv) {
	$error = 0;
	if($userid > 0 && $lineid > 0){
		$fod_user->fetch($lineid);
		if($fod_user->statut == $fod_user::STATUS_AUTORISE && $fod_user->visa != 1){
			$fod_user->visa = 1;
			$fod_user->date_visa = dol_now();
			
			$result = $fod_user->update($user);

			// Call trigger
			$res = $fod_user->call_trigger('FOD_PRISEENCOMPTE', $user);
			if ($res < 0) {
				setEventMessages('Erreur mail automatique lors de la prise en compte', null, 'errors');
			}
			// End call triggers

			if ($result >= 0) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans($fod_user->error), null, 'errors');
				$error++;
			}
		}
	}
	else {
		setEventMessages($langs->trans($fod_user->error), null, 'errors');
	}
}

if (($action == 'nopriseencompte') && $user_interv) {
	setEventMessages($langs->trans('PriseEnCompteImpossible'), null, 'errors');
}

/*
 * View
 *
 */
$form = new ExtendedForm($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Fod");
$help_url = '';
llxHeader('', $title, $help_url, '', '', '', array('/core/js/fod.js'));

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewFod"), '', 'object_'.$object->picto);

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
	include DOL_DOCUMENT_ROOT.'/custom/fod/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Fod"), '', 'object_'.$object->picto);

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
	/*unset($object->fields['client_site']);
	unset($object->fields['installation']);
	unset($object->fields['etat_installation']);
	unset($object->fields['commentaire_etat_installation']);
	unset($object->fields['activite']);
	unset($object->fields['ref']);
	unset($object->fields['indice']);
	unset($object->fields['fk_project']);
	unset($object->fields['date_debut']);
	unset($object->fields['date_fin']);
	unset($object->fields['effectif']);
	unset($object->fields['debit_dose_estime']);
	unset($object->fields['debit_dose_max']);
	unset($object->fields['duree_intervention']);
	unset($object->fields['coef_exposition']);
	unset($object->fields['prop_radiologique']);
	unset($object->fields['risques']);
	unset($object->fields['commentaire_risque']);
	unset($object->fields['rex']);
	unset($object->fields['ref_rex']);
	unset($object->fields['ri']);
	unset($object->fields['commentaire_ri']);
	unset($object->fields['objectif_proprete']);
	unset($object->fields['com_objectif_proprete']);
	unset($object->fields['ref_doc_client']);
	unset($object->fields['commentaire_fod']);*/
	include DOL_DOCUMENT_ROOT.'/custom/fod/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}
// Part to edit AOA
if (($id || $ref) && $action == 'editaoa') {
	print load_fiche_titre($langs->trans("Fod"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="updateaoa">';
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
	include DOL_DOCUMENT_ROOT.'/custom/fod/core/tpl/commonfields_editAOA.tpl.php';

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
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create' && $action != 'editaoa'))) {
	$res = $object->fetch_optionals();

	if ($action == 'editdocclient' || $action == 'editpcr' || $action == 'editraf' || $action == 'editrsr') {
		print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'" />'."\n";
		print '<input type="hidden" name="action" value="update"/>'."\n";
		print '<input type="hidden" name="id" value="'.$object->id.'" />'."\n";
	}

	$head = fodPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Fod"), -1, $object->picto);

	$formconfirm = '';
	// Confirmation to delete
	if ($action == 'delete' && $permissiontodelete) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteFod'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// Confirmation to cancel
	if ($action == 'cancel' && $permissiontocancel) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Cancel'), $langs->trans('ConfirmCancelFOD'), 'confirm_close', '', 0, 1);
	}

	// Confirmation to refus
	if ($action == 'refus' && ($permissionToRefuserFOD || $user->id == $object->fk_user_raf)) {
		$value = '<textarea class="flat" rows="3" style="width: 440px" id="raison_refus" name="raison_refus"></textarea>';
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Raison du refus'), '', 'confirm_refus', array(array('type'=>'other', 'value'=>$value)), 0, 2);
	}

	// Confirmation pour supprimer un intervenant
	if ($action == 'removeintervenant' && $permissionToEditandDeleteIntervenant) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&user='.$userid.'&lineid='.$lineid, $langs->trans('Supprimer intervenant'), "Voulez vous supprimer l'intervenant ?", 'confirmremoveintervenant', '', 0, 1);
	}

	// Confirmation bilan
	if ($action == 'bilan' && $permissionToBilan) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('BilanFod'), $langs->trans('ConfirmBilan'), 'confirm_bilan', '', 0, 1);
	}

	// Confirmation Validation RA
	if ($action == 'validatera') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validation'), $langs->trans('ConfirmValidate'), 'confirm_validatera', '', 0, 1);
	}

	// Confirmation Validation RSR
	if ($action == 'validatersr') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validation'), $langs->trans('ConfirmValidate'), 'confirm_validatersr', '', 0, 1);
	}

	// Confirmation Validation RA et RSR
	if ($action == 'validaterarsr') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validation'), $langs->trans('ConfirmValidate'), 'confirm_validaterarsr', '', 0, 1);
	}

	// Confirmation Validation PCR
	if ($action == 'validate') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validation'), $langs->trans('ConfirmValidate'), 'confirm_validate', '', 0, 1);
	}


	// Confirmation Prolongation
	if ($action == 'prolonger' && $permissionToProlongerFOD) {
		$input = array();
		$tmp['label'] = "Date prolongation :";
		$tmp['type'] = 'date';
		$tmp['name'] = 'date_prolong';
		$input[] = $tmp;
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ProlongerFOD'), '', 'confirm_prolonger', $input, 0, 2);
	}

	// Confirmation to delete line
	/*if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}*/

	// Clone confirmation
	if ($action == 'clone' && $permissionToClone) {
		// Create an array for form
		//$formquestion = array(array('label'=>'Numéro chrono de la nouvelle FOD', 'type'=>'text', 'name'=>'num_chrono'));
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', '', 'yes', 2);
	}

	// Confirmation of action xxxx
	/*if ($action == 'xxx') {
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
		/*$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}*/

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
	$linkback = '<a href="'.dol_buildpath('/fod/fod_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	$morehtmlref = '';
	dol_banner_tab_fod($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 0, '', 0);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak='effectif';	// We change column just before this field
	unset($object->fields['client_site']);
	unset($object->fields['installation']);
	unset($object->fields['etat_installation']);
	unset($object->fields['commentaire_etat_installation']);
	unset($object->fields['activite']);
	include DOL_DOCUMENT_ROOT.'/custom/fod/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */
	/*if (!empty($object->table_element_line)) {
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
	}*/

	if (($action == 'editdocclient')) {
		print '</form>';
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
			// Modifier AOA
			if($object->status == $object::STATUS_AOA || ($object->status == $object::STATUS_DRAFT && $object->aoa == 2) && $permissionToModifierAOA){
				print dolGetButtonAction($langs->trans('ModifyAOA'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editaoa&token='.newToken(), '', $permissionToModifierAOA);
			}

			// Prolonger FOD
			if($object->status == $object::STATUS_VALIDATED && empty($object->date_fin_prolong) && $permissionToProlongerFOD){
				print dolGetButtonAction($langs->trans('Prolonger'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=prolonger&token='.newToken(), '', $permissionToProlongerFOD);
			}

			// Back to draft
			/*if (($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_VALIDATEDRA || $object->status == $object::STATUS_VALIDATEDRARSR || $object->status == $object::STATUS_AOA) && $permissionToBackDraft) {
				print dolGetButtonAction($langs->trans('SetToDraft'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissionToBackDraft);
			}*/

			// Modifier FOD
			if((($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_AOA) && $permissionToModifierFOD) || ($object->status == $object::STATUS_VALIDATEDRSR && ($user->id == $object->fk_user_raf || $user->admin)) || ($object->status == $object::STATUS_VALIDATEDRARSR && ($user->id == $object->fk_user_pcr || $user->admin))){
				print dolGetButtonAction($langs->trans('ModifyFOD'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissionToModifierFOD);
			}

			// Premiere Validation : RA 
			if ($object->status == $object::STATUS_DRAFT && ($user->id == $object->fk_user_raf || $userIsRaf || $user->admin)) {
				print dolGetButtonAction("Validation responsable d'affaire", $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validatera&token='.newToken(), '');
			}
			// Premiere Validation : RA lors d'une AOA
			else if ($object->status == $object::STATUS_AOA && ($user->id == $object->fk_user_raf || $userIsRaf || $user->admin)) {
				print dolGetButtonAction("Validation responsable d'affaire", $langs->trans('ValidateAOA'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validatera&token='.newToken(), '');
			}

			// Premiere Validation : RSR
			if ($object->status == $object::STATUS_DRAFT && ($user->id == $object->fk_user_rsr && ($user->id != $object->fk_user_raf && !$userIsRaf))) {
				print dolGetButtonAction("Validation RSR", $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validatersr&token='.newToken(), '');
			}
			// Premiere Validation : RSR lors d'une AOA
			else if ($object->status == $object::STATUS_AOA && ($user->id == $object->fk_user_rsr && ($user->id != $object->fk_user_raf && !$userIsRaf))) {
				print dolGetButtonAction("Validation RSR", $langs->trans('ValidateAOA'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validatersr&token='.newToken(), '');
			}

			// Deuxieme Validation : RA apres RSR
			if ($object->status == $object::STATUS_VALIDATEDRSR && ($user->id == $object->fk_user_raf || $userIsRaf || $user->admin)) {
				print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validaterarsr&token='.newToken(), '');
			}

			// Validation PCR => Validé 
			if (($object->status == $object::STATUS_VALIDATEDRARSR) && ($user->id == $object->fk_user_pcr || $userPCR || $user->admin)){
					print dolGetButtonAction("Validation PCR", $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken(), '');
			} 
			/*else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
			}*/

			// Refus PCR => Draft 
			if ((($object->status == $object::STATUS_VALIDATEDRARSR) && $permissionToRefuserFOD) || (($object->status == $object::STATUS_VALIDATEDRSR) && $user->id == $object->fk_user_raf)){
				print dolGetButtonAction("Refuser", $langs->trans('Refuser'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=refus&confirm=yes&token='.newToken(), '', $permissionToRefuserFOD || $user->id == $object->fk_user_raf);
			}

			// Bilan
			if ($object->status == $object::STATUS_VALIDATED && $permissionToBilan) {
				print dolGetButtonAction($langs->trans('Bilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=bilan&token='.newToken(), '', $permissionToBilan);
			}

			// Clone
			print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&token='.newToken(), '', $permissionToClone);

			// Generer Document FOD
			print dolGetButtonAction($langs->trans('GenererDoc'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_genererDocFod&confirm=yes&token='.newToken(), '', $permissiontoGenerateDocFod);
			
			// Delete (need delete permission, or if draft, just need create/modify permission)
			//print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete);

			// Annulé la FOD
			if($object->status == $object::STATUS_DRAFT){
				print dolGetButtonAction($langs->trans('Cancel'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=cancel&token='.newToken(), '', $permissiontocancel);
			}
		}
		print '</div>'."\n";
	}


	// LISTE DES INTERVENANTS AVEC POSSIBILITé D'AJOUT ET DE SUPRESSION
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	// List users in group
	print load_fiche_titre($langs->trans("ListOfIntervenantsInFOD"), '', 'user');

	// On selectionne les users qui ne sont pas deja dans le groupe
	$exclude = array();

	$liste_intervenant = $object->listIntervenantsForFod();
	if (!empty($liste_intervenant)) {
		foreach ($liste_intervenant as $userinfod) {
			$exclude[] = $userinfod->id;
		}
	}
	// et uniquement dans les contacts du projet
	$project = new Project($db);
	$project->fetch($object->fk_project);
	$contactsofproject = $project->getListContactId('internal');

	// Other form for add intervenant to fod
	$parameters = array('caneditperms' => $caneditperms, 'exclude' => $exclude);
	$reshook = $hookmanager->executeHooks('formAddUserToGroup', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	
	if (empty($reshook)) {
		print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.($action != 'editintervenant' ? '&action=addintervenant' : '').'" method="POST">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">';

		if ($permissionToAddIntervenant && ($object->status != Fod::STATUS_BILAN && $object->status != Fod::STATUS_BILANinter && $object->status != Fod::STATUS_BILANRSR && 
		$object->status != Fod::STATUS_BILANRSRRA && $object->status != Fod::STATUS_BILANRSRRAPCR && $object->status != Fod::STATUS_CLOTURE)) {
			if ($action == 'newuser'){
				print '<input type="hidden" name="action" value="addintervenantnew">';
			}
			/*elseif($action == 'view' || empty($action)) {
				print '<input type="hidden" name="action" value="addintervenant">';
			}*/
			print '<table class="noborder centpercent">'."\n";

			print '<tr class="liste_titre">';
			print '<td class="titlefield liste_titre">'.$langs->trans("NonAffectedUsersFod").'</td>'."\n".'</td>';
			print '<td class="titlefield liste_titre">Contrat</td>';
			print '<td class="titlefield liste_titre">Durée contrat (jours)</td>';
			print "<td class='titlefield liste_titre'>Date d'entrée</td>";
			print '<td class="titlefield liste_titre"></td>';
			print '</tr>'."\n";

			print '<tr class="liste_titre">';
			// USER
			print '<td class="liste_titre">';
			/*if ($action == 'newuser'){
				print '<td class="liste_titre">';
				print $new_user->showInputField($new_user->fields['prenom'], 'prenom', 1, '', '', '', 0);
				print $new_user->showInputField($new_user->fields['nom'], 'nom', 1, '', '', '', 0);
				print '</td>';
			}
			else {*/
				print $form->select_dolusers('', 'user', 1, $exclude, 0, $contactsofproject, '', $object->entity, 0, 0, '', 0, '', 'maxwidth300');
				// Bouton ajouter utilisateur
				/*$lien = '/erp/custom/fod/fod_card.php?id='.$id.'&action=newuser';
				print '<a href="'.$lien.'" title="AddIntervenant" style="margin-left: 10px;">';
				print '<span class="fa fa-plus-circle valignmiddle btnTitle-icon">';
				print '</span></a>';
				print '</td>';*/
			//}
			print '</td>';

			// Contrat
			print '<td class="liste_titre">';
			print $fod_user->showInputField($fod_user->fields['contrat'], 'contrat', 1, '', '', '', 0);
			print '</td>';

			// Durée contrat
			print '<td class="liste_titre">';
			print $fod_user->showInputField($fod_user->fields['duree_contrat'], 'duree_contrat', '', '', '', '', 0);
			print '</td>';

			// Date d'entrée
			print "<td class='maxwidthonsmartphone'>";
			$newdate = '';
			print $form->selectDate($object->date_debut, 'timeline', 0, 0, 2, '', 1, 0);
			print '</td>';

			print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
			print '<td class="liste_titre" align="right">';
			print '<input type="submit" class="button buttongen right" value="'.$langs->trans("Add").'">';
			print '</td></tr>'."\n";
			print '</table>';
			print '<br>';
		}

		/*
		* Group intervenants
		*/

		$data_intervenant = New Data_intervenant($db);
		
		print '<div class="div-table-responsive" style="min-height: 0px;">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th class="wrapcolumntitle">'.$langs->trans("Login").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Lastname").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Firstname").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("CdD").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Contrat").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Durée contrat").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Date d'entrée").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Prise en compte").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Date de prise en compte").'</th>';
		print '<th class="wrapcolumntitle">'.$langs->trans("Date de sortie").'</th>';
		print '<th class="wrapcolumntitle center" width="5">'.$langs->trans("Stat").'</th>';
		if ($permissionToEditandDeleteIntervenant) {
			print '<th class="wrapcolumntitle right" width="5">&nbsp;</th>';
		}
		print "</tr>\n";

		$liste_intervenant = $object->listIntervenantsForFod();
		if (!empty($liste_intervenant)) {
			foreach ($liste_intervenant as $userinfod) {
				$fod_user->fetch($fod_user->getIdWithUserAndFod($userinfod->id,$object->id));
				print '<tr class="oddeven">';
				print '<td class="tdoverflowmax150">';

				// Login 
				print $userinfod->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
				if ($userinfod->admin && !$userinfod->entity) {
					print img_picto($langs->trans("SuperAdministrator"), 'redstar');
				} elseif ($userinfod->admin) {
					print img_picto($langs->trans("Administrator"), 'star');
				}
				print '</td>';

				// Lastname
				print '<td>'.$userinfod->lastname.'</td>';

				// Firstname
				print '<td>'.$userinfod->firstname.'</td>';

				// CdD
				print '<td>';
				print $userinfod->getDoseMaxFod($object).' mSv';
				print '</td>';
				
				// Contrat 
				print '<td>';
				if ($action == 'editintervenant' && $_GET['user'] == $userinfod->id) {
					print $fod_user->showInputField($fod_user->fields['contrat'], 'contrat', $fod_user->contrat, '', '', '', 0);
				}
				else {
					print $fod_user->fields['contrat']['arrayofkeyval'][$fod_user->contrat];
				}
				print '</td>';

				// Durée contrat 
				print '<td>';
				if ($action == 'editintervenant' && $_GET['user'] == $userinfod->id) {
					print $fod_user->showInputField($fod_user->fields['duree_contrat'], 'duree_contrat', $fod_user->duree_contrat, '', '', '', 0).' jours';
				}
				else {
					if(isset($fod_user->duree_contrat)){
						print $fod_user->duree_contrat.' jours';
					}
					else { 
						print '/';
					}
				}
				print '</td>';

				// Date d'entrée
				print '<td class="maxwidthonsmartphone">';
				if ($action == 'editintervenant' && $_GET['user'] == $userinfod->id) {
					print $form->selectDate($fod_user->date_entree, 'date_entree', 0, 0, 2, '', 1, 0);
					//print $fod_user->showInputField($fod_user->fields['date_entree'], 'date_entree', $fod_user->date_entree, '', '', '', 0);
				}
				else {
					print dol_print_date($fod_user->date_entree, 'day');
				}
				print '</td>';

				// Visa
				if ($user->id == $userinfod->id && $fod_user->fields['visa']['arrayofkeyval'][$fod_user->visa] == 'Non' && $object->status == $object::STATUS_VALIDATED) {
					print '<td style="width: 200px;">';
					if($fod_user->statut == $fod_user::STATUS_AUTORISE){
						$link = $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=priseencompte&user='.$userinfod->id.'&lineid='.$fod_user->id.'&token='.newToken();
						print '<a class="butAction" href="'.$link.'" title="'.$langs->trans('PriseEnCompte').'" style="margin: 0px;">Prise en compte</a>';
					}
					else {
						//setEventMessages($langs->trans('PriseEnCompteImpossible'), null, 'errors');
						$link = $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=nopriseencompte&token='.newToken();
						print '<a class="butAction" href="'.$link.'" title="'.$langs->trans('PriseEnCompte').'" style="margin: 0px;">Prise en compte</a>';
						//print '<input class="butAction" type="submit" onclick="priseEnCompteImpossible()" value="'.$langs->trans('PriseEnCompte').'">';
					}
				}
				else {
					if($fod_user->fields['visa']['arrayofkeyval'][$fod_user->visa] == 'Oui'){
						print '<td style="width: 200px; color: green;"><strong>';
						print $fod_user->fields['visa']['arrayofkeyval'][$fod_user->visa].'</strong>';
					}
					elseif ($fod_user->fields['visa']['arrayofkeyval'][$fod_user->visa] == 'Non'){
						print '<td style="width: 200px; color: red;"><strong>';
						print $fod_user->fields['visa']['arrayofkeyval'][$fod_user->visa].'</strong>';
					}
				}
				print '</td>';

				// Date Visa
				print '<td>';
				print dol_print_date($fod_user->date_visa, 'day');
				if(empty($fod_user->date_visa)) print '/';
				print '</td>';

				// Date sortie
				print '<td>';
				if ($action == 'editintervenant' && $_GET['user'] == $userinfod->id) {
					print $form->selectDate(($fod_user->date_sortie ? $fod_user->date_sortie : -1), 'date_sortie', 0, 0, 2, '', 1, 0);
					//print $fod_user->showInputField($fod_user->fields['date_sortie'], 'date_sortie', $fod_user->date_sortie, '', '', '', 0);
				}
				else {
					print dol_print_date($fod_user->date_sortie, 'day');
					if(empty($fod_user->date_sortie)) print '/';
				}
				print '</td>';

				// Statut
				print '<td class="center">'.$fod_user->getLibStatut(5).'</td>';

				// Bouton action
				if ($permissionToEditandDeleteIntervenant) {
					if ($action == 'editintervenant' && $_GET['user'] == $userinfod->id) {
						print '<td class="center nowraponall">';
						print '<input type="hidden" name="lineid" value="'.$fod_user->id.'">';
						print '<input type="hidden" name="action" value="updateintervenant">';
						print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-save" name="save" value="'.$langs->trans("Save").'">';
						print '<br>';
						print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
					}
					else {
						print '<td class="center">';
						if ($object->status != Fod::STATUS_CLOTURE){
							if($userinfod->getEntreeFod($object) == 0){
								print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removeintervenant&amp;user='.$userinfod->id.'&lineid='.$fod_user->id.'">';
								print img_picto($langs->trans("RemoveFromFod"), 'unlink');
								print '</a>';
							}
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=editintervenant&amp;user='.$userinfod->id.'">';
							print img_picto($langs->trans("UpdateFromFod"), 'edit', '', '', '', '', '', 'margin-left: 6px;');
							print '</a>';
						}
					}
				}
				print "</td></tr>\n";
			}
		} else {
			print '<tr><td colspan="11" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table>";
		print '</form>';
		print '</div>';
	}

	print "<br><br><br><br><br><br><br>";


	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////


	/*if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->fod->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = All; // If you can read, you can build the PDF to read content
			$delallowed = $user->rights->fod->fod->write; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('fod:Fod', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('fod'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="'.dol_buildpath('/fod/fod_agenda.php', 1).'?id='.$object->id.'">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}*/

	//Select mail models is same action as presend
	/*if (GETPOST('modelselected')) {
		$action = 'presend';
	}*/

	// Presend form
	/*$modelmail = 'fod';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->fod->dir_output;
	$trackid = 'fod'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';*/
}

// End of page
llxFooter();
$db->close();
