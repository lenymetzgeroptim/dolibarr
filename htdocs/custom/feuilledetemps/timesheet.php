<?php
/* Copyright (C) 2021-2022      Lény Metzger <leny-07@hotmail.fr>
 *
  This program is free software; you can redistribute it and/or modify
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
 *	\file       custom/feuilledetemps/timesheetByMonth.php
 *	\ingroup    feuilledetemps
 *	\brief      Permet aux utilisateurs de réaliser leurs pointages (tableau mensuel)
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedTask.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedHoliday.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/feuilledetemps.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/lib/feuilledetemps.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedProjet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/projet_task_time_heure_sup.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/projet_task_time_other.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedUser.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/deplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/silae.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/regul.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';

// Load translation files required by the page
$langs->loadLangs(array("feuilledetemps@feuilledetemps", 'projects', 'users', 'companies', 'holidaycustom@holidaycustom'));


$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'aZ09');
$mode = GETPOST("mode", 'alpha');
$id = GETPOST('id', 'int');
$taskid = GETPOST('taskid', 'int');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'perweekcard';
$projectid = GETPOSTISSET("id") ? GETPOST("id", "int", 1) : GETPOST("projectid", "int");
$showFav = GETPOST('showFav', 'int');

$mine = 0;
if ($mode == 'mine') {
	$mine = 1;
}

$socid = 0;
$now = dol_now();

$year = GETPOST('reyear', 'int') ?GETPOST('reyear', 'int') : (GETPOST("year", 'int') ?GETPOST("year", "int") : date("Y"));
$month = GETPOST('remonth', 'int') ?GETPOST('remonth', 'int') : (GETPOST("month", 'int') ?GETPOST("month", "int") : date("m"));
$day = GETPOST('reday', 'int') ?GETPOST('reday', 'int') : (GETPOST("day", 'int') ?GETPOST("day", "int") : date("d"));
$day = (int)$day;

$search_usertoprocessid = (!empty($_POST['search_usertoprocessid']) ? $_POST['search_usertoprocessid'] : $_GET['search_usertoprocessid']);
$search_task_ref = GETPOST('search_task_ref', 'alpha', 3);
$search_task_label = GETPOST('search_task_label', 'alpha', 3);
$search_project_ref = GETPOST('search_project_ref', 'alpha', 3);
$search_thirdparty = GETPOST('search_thirdparty', 'alpha', 3);
$search_declared_progress = GETPOST('search_declared_progress', 'alpha', 3);


// Déclaration des objets
if (empty($search_usertoprocessid) || $search_usertoprocessid == $user->id) {
	$usertoprocess = $user;
	$search_usertoprocessid = $usertoprocess->id;
} elseif ($search_usertoprocessid > 0) {
	$usertoprocess = new User($db);
	$usertoprocess->fetch($search_usertoprocessid);
	$search_usertoprocessid = $usertoprocess->id;
} else {
	$usertoprocess = new User($db);
}

$task = new extendedTask($db);
$object = new FeuilleDeTemps($db); // Représente la FDT qui correspond au mois du 1er jour de la semaine
$projet = New Project($db);
$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$formproject = new FormProjets($db);
$projectstatic = new ExtendedProjet($db);
$project = new Project($db);
$thirdpartystatic = new Societe($db);
$holiday = new extendedHoliday($db);
$taskstatic = new Task($db);

// Extra fields
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($task->table_element);


// Gestion des dates 
$prev_month = dol_get_prev_month($month, $year);

$prev_year  = $prev_month['year'];
$prev_month = $prev_month['month'];
$prev_day   = $prev['prev_day'];

$next = dol_get_next_month($month, $year);
$next_year  = $next['year'];
$next_month = $next['month'];

$first_day_month = dol_get_first_day($year, $month);
$firstdaytoshow = dol_time_plus_duree($first_day_month, -$conf->global->JOUR_ANTICIPES, 'd');
$firstdaytoshowgmt = dol_mktime(0, 0, 0, dol_print_date($firstdaytoshow, '%m'), dol_print_date($firstdaytoshow, '%d'), dol_print_date($firstdaytoshow, '%Y'), 'gmt');
$lastdaytoshow = dol_get_last_day($year, $month);

$ecart_jour = num_between_day($firstdaytoshow, $first_day_month + 3600); // Nombre de jour à anticiper
$month_fdt = date('mY', $lastdaytoshow);


// Chargement de la feuille de temps et Vérification de la possibilité de modifier la FDT
$object_id = $object->ExisteDeja($month_fdt, $usertoprocess);
$can_modify_fdt = 1;
if($object_id > 0) {
	$object->fetch($object_id);

	$list_resp_task = $object->listApprover1;
	if(in_array(1, $list_resp_task[1])){
		$resp_task_valide = 1;
	}
	else {
		$resp_task_valide = 0;
	}

	if($object->status != FeuilleDeTemps::STATUS_DRAFT){
		$can_modify_fdt = 0;
	}
}
else {
	$object->date_debut = $first_day_month;
	$object->date_fin = $lastdaytoshow;
}

$month_now = date('m');
$nb_jour = num_between_day($firstdaytoshow, $lastdaytoshow - 3600) + 1; // Nombre de jour total à affiché

$timeHoliday = $object->timeHolidayWeek($usertoprocess->id);
$timeSpentWeek = $object->timeDoneByWeek($usertoprocess->id);

for ($idw = 0; $idw < $nb_jour; $idw++) {
	$dayinloopfromfirstdaytoshow = dol_time_plus_duree($firstdaytoshow, $idw, 'd');
	$dayinloopfromfirstdaytoshow_array[$idw] = $dayinloopfromfirstdaytoshow; 
}


$arrayfields = array();
$arrayfields['t.planned_workload'] = array('label'=>'PlannedWorkload', 'checked'=>0, 'enabled'=>1, 'position'=>5);
$arrayfields['t.progress'] = array('label'=>'ProgressDeclared', 'checked'=>1, 'enabled'=>0, 'position'=>10);
$arrayfields['timeconsumed'] = array('label'=>'TimeConsumed', 'checked'=>1, 'enabled'=>0, 'position'=>15);

// Extra fields
if (is_array($extrafields->attributes['projet_task']['label']) && count($extrafields->attributes['projet_task']['label']) > 0) {
	foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
		if (!empty($extrafields->attributes['projet_task']['list'][$key])) {
		$arrayfields["efpt.".$key] = array('label'=>$extrafields->attributes['projet_task']['label'][$key], 'checked'=>(($extrafields->attributes['projet_task']['list'][$key] < 0) ? 0 : 0), 'position'=>$extrafields->attributes['projet_task']['pos'][$key], 'enabled'=>/*(abs((int) $extrafields->attributes['projet_task']['list'][$key]) != 3 && $extrafields->attributes['projet_task']['perms'][$key])*/0);
		}
	}
}
$arrayfields = dol_sort_array($arrayfields, 'position');

$search_array_options = array();
$search_array_options_project = $extrafields->getOptionalsFromPost('projet', '', 'search_');
$search_array_options_task = $extrafields->getOptionalsFromPost('projet_task', '', 'search_task_');

if(!empty($_POST["transmettre"])) {
	$massaction = "transmettre";
}

$extrafields->fetch_name_optionals_label('donneesrh_Deplacement');
$userField_deplacement = new UserField($db);
$userField_deplacement->id = $usertoprocess->id;
$userField_deplacement->table_element = 'donneesrh_Deplacement';
$userField_deplacement->fetch_optionals();

// Gestion des types de déplacement
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


// Nombre d'heures par semaine à faire et avant de pouvoir avoir des hs
$extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
$userField = new UserField($db);
$userField->id = $usertoprocess->id;
$userField->table_element = 'donneesrh_Positionetcoefficient';
$userField->fetch_optionals();

$heure_semaine = (!empty($userField->array_options['options_pasdroitrtt']) ?  $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
$heure_semaine = (!empty($userField->array_options['options_horairehebdomadaire']) ? $userField->array_options['options_horairehebdomadaire'] : $heure_semaine);
$heure_semaine_hs = (!empty($userField->array_options['options_pasdroitrtt']) ? $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);


/*
 * Actions
 */

// Purge criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$action = '';
	$search_usertoprocessid = $user->id;
	$search_task_ref = '';
	$search_task_label = '';
	$search_project_ref = '';
	$search_thirdparty = '';
	$search_declared_progress = '';

	$search_array_options_project = array();
	$search_array_options_task = array();

	// We redefine $usertoprocess
	$usertoprocess = $user;
}

$onlyopenedproject = -1;
$morewherefilter = ' AND (t.dateo <= "'.$db->idate($lastdaytoshow).'" OR t.dateo IS NULL) AND (t.datee >= "'.$db->idate($firstdaytoshow).'" OR t.datee IS NULL)';

if ($search_project_ref) {
	$morewherefilter .= natural_search(array("p.ref", "p.title"), $search_project_ref);
}
if ($search_task_ref) {
	$morewherefilter .= natural_search("t.ref", $search_task_ref);
}
if ($search_task_label) {
	$morewherefilter .= natural_search(array("t.ref", "t.label"), $search_task_label);
}
if ($search_thirdparty) {
	$morewherefilter .= natural_search("s.nom", $search_thirdparty);
}
if ($search_declared_progress) {
	$morewherefilter .= natural_search("t.progress", $search_declared_progress, 1);
}

$sql = &$morewherefilter;

// Paramètres URL
$param = '';
$param .= ($mode ? '?mode='.urlencode($mode) : '');
$param .= ($search_project_ref ? '&search_project_ref='.urlencode($search_project_ref) : '');
$param .= ($search_usertoprocessid > 0 ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
$param .= ($search_thirdparty ? '&search_thirdparty='.urlencode($search_thirdparty) : '');
$param .= ($search_task_ref ? '&search_task_ref='.urlencode($search_task_ref) : '');
$param .= ($search_task_label ? '&search_task_label='.urlencode($search_task_label) : '');
$paramwithoutdate = $param;
//if ($massaction == 'transmettre' || $action == 'confirm_transmettre') {
	$param .= ($day ? '&day='.urlencode($day) : '').($month ? '&month='.urlencode($month) : '').($year ? '&year='.urlencode($year) : '');
//}

$search_array_options = $search_array_options_project;
$search_options_pattern = 'search_options_';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$search_array_options = $search_array_options_task;
$search_options_pattern = 'search_task_options_';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

if (GETPOST("button_search_x", 'alpha') || GETPOST("button_search.x", 'alpha') || GETPOST("button_search", 'alpha')) {
	$action = '';
}

if (GETPOST('submitdateselect')) {
	if (GETPOST('remonth', 'int') && GETPOST('reday', 'int') && GETPOST('reyear', 'int')) {
		$daytoparse = dol_mktime(0, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
	}

	$action = '';
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

include DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/tpl/actions_timesheet.tpl.php';

include DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/actions.inc.php';

// Gestion des congés et des jours feriés
$timeSpentDay = $object->timeDoneByDay($usertoprocess->id);
$multiple_holiday = 0;
$uncompleted_fdt = 0;
for ($idw = 0; $idw < $nb_jour; $idw++) {
	$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0*
	$dayinloopfromfirstdaytoshowgmt = dol_time_plus_duree($firstdaytoshowgmt, 24*$idw, 'h'); // $firstdaytoshow is a date with hours = 0

	$isavailable[$dayinloopfromfirstdaytoshow] = $holiday->verifDateHolidayForTimestamp($usertoprocess->id, $dayinloopfromfirstdaytoshow, Holiday::STATUS_APPROVED2, array(4));
	$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow] = $holiday->verifDateHolidayForTimestamp($usertoprocess->id, $dayinloopfromfirstdaytoshow, array(Holiday::STATUS_DRAFT, Holiday::STATUS_VALIDATED, Holiday::STATUS_APPROVED2,  Holiday::STATUS_APPROVED1), array(4));	

	$holidayTypeNeedHour = 1;
	for($i = 0; $i < sizeof($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['code']); $i++) {
		if(!$holiday->holidayTypeNeedHour($isavailable[$dayinloopfromfirstdaytoshow]['code'][$i])) {
			$holidayTypeNeedHour = 0;
		}
	}

	if (!$isavailable[$dayinloopfromfirstdaytoshow]['morning'] && !$isavailable[$dayinloopfromfirstdaytoshow]['afternoon'] && !$holidayTypeNeedHour) {
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidayallday';
	} elseif(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim'){
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidayallday';
	} elseif (!$isavailable[$dayinloopfromfirstdaytoshow]['morning']) {
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidaymorning';
	} elseif (!$isavailable[$dayinloopfromfirstdaytoshow]['afternoon']) {
		$css[$dayinloopfromfirstdaytoshow] .= ' onholidayafternoon';
	} 

	if(!$multiple_holiday && sizeof($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['rowid']) > 1) {
		$multiple_holiday = 1;
	}

	if (!$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['morning'] && !$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['afternoon']) {
		for($i = 0; $i < sizeof($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut']); $i++) {
			$css_holiday[$dayinloopfromfirstdaytoshow][$i] .= ' conges'.$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut'][$i].'allday';
		}
	} elseif (!$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['morning']) {
		for($i = 0; $i < sizeof($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut']); $i++) {
			$css_holiday[$dayinloopfromfirstdaytoshow][$i] .= ' conges'.$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut'][$i].'morning';
		}
	} elseif (!$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['afternoon']) {
		for($i = 0; $i < sizeof($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut']); $i++) {
			$css_holiday[$dayinloopfromfirstdaytoshow][$i] .= ' conges'.$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['statut'][$i].'afternoon';
		}	
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

	if(!$uncompleted_fdt && empty($css[$dayinloopfromfirstdaytoshow]) && empty($css_holiday[$dayinloopfromfirstdaytoshow]) && empty($timeSpentDay[dol_print_date($dayinloopfromfirstdaytoshow, "%d/%m/%Y")])) {
		$uncompleted_fdt = 1;
	}
}

/*
 * View
 */

$title = $langs->trans("FeuilleDeTemps");

if ($morewherefilter) {	// Get all task without any filter, so we can show total of time spent for not visible tasks
	$tasksarraywithoutfilter = $taskstatic->getTasksArray(0, 0, 0, $socid, 0, '', $onlyopenedproject, '', 0); // We want to see all tasks of open project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
}
$projectsrole = $taskstatic->getUserRolesForProjectsOrTasks($usertoprocess, 0, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
$tasksrole = $taskstatic->getUserRolesForProjectsOrTasks(0, $usertoprocess, ($project->id ? $project->id : 0), 0, $onlyopenedproject);

$array_js = array('/core/js/timesheet.js', '/custom/feuilledetemps/core/js/timesheet.js', '/custom/feuilledetemps/core/js/parameters.php');
llxHeader("", $title, "", '', '', '', $array_js, '', '', 'classforhorizontalscrolloftabs feuilledetemps timesheet');
//print '<body onresize="redimenssion()">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'object_timesheet_32@feuilledetemps');

// Affichage des fenêtres de confirmation
$formconfirm = '';
if ($massaction == 'transmettre') {
	$question = 'Voulez vous transmettre votre feuille de temps pour validation ?';
	$question .= ($uncompleted_fdt ? '<br><span style="color: #be0000; font-size: initial;"><strong>⚠ Le pointage n\'a pas été renseigné en totalité</strong></span>' : '');
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?'.$param, $langs->trans('Transmission'), $question, 'confirm_transmettre', '', 0, 1);
}
print $formconfirm;

// Show description of content
print '<div class="toggled-off">';
print '<div class="toggle-title">';
print '<i class="fa fa-angle-down fa-fw close-intro"></i>';
print '<i class="fa fa-angle-up fa-fw open-intro" style="display: none;"></i> ';
print 'Fonctionnement';
print '</div>';
print '<div id="fonctionnement">';
print '<span class="hideonsmartphone opacitymedium">';
print 'Cette vue est restreinte aux projets ou tâches pour lesquels vous êtes un contact affecté.. Seuls les projets ouverts sont visibles (les projets à l\'état brouillon ou fermé ne sont pas visibles).<br>';
print 'Seules les tâches qui vous sont assignées sont visibles.<br>';
print '<strong>Temps de travail :</strong> Veuillez renseigner vos horaires pour chaque jour du mois (max : 10h par jour et 48h par semaine).<br>';
print '<strong>Heure sup :</strong> Si vous entrez + de '.$heure_semaine_hs.'h, 2 nouvelles cases apparaissent. Dans la case <span class="txt_hs25">bleue</span>, entrez les heures entre '.$heure_semaine_hs.'h et '.$conf->global->HEURE_SUP1.'h. Dans la case <span class="txt_hs50">orange</span>, entrez les heures entre '.$conf->global->HEURE_SUP1.'h et '.$conf->global->HEURE_MAX_SEMAINE.'h.<br>';
print '<strong>Autres :</strong> Vous pouvez également renseigner les autres types d\'heures en cochant la case correspondante sur la tache. (max : temps de travail du jour concerné).<br>';
print '<strong>Code couleur : ';
print '</span>';
print '<span class="txt_before">Jours anticipés</span> - <span class="txt_ferie">Jours feriés</span> - <span class="txt_conges_brouillon">Absence en brouillon</span> - <span class="txt_conges_valide">Absence en Approbation n°1</span> - <span class="txt_conges_approuve1">Absence en Approbation n°2</span> - <span class="txt_conges_approuve2">Absence approuvée</span></strong>';
print '<span class="hideonsmartphone opacitymedium info_fdt">';
if($userInDeplacement) {
	print '<br>D1 = '.$userField_deplacement->array_options['options_d_1'].', D2 = '.$userField_deplacement->array_options['options_d_2'].' D3 = '.$userField_deplacement->array_options['options_d_3'].' D4 = '.$userField_deplacement->array_options['options_d_4'];
}
if($userInGrandDeplacement) {
	print '<br>GD1 = '.$userField_deplacement->array_options['options_gd1'].', GD2 = '.$userField_deplacement->array_options['options_gd2'].', GD3 = '.$userField_deplacement->array_options['options_gd3'].', GD4 = '.$userField_deplacement->array_options['options_gd4'];
}	
print '<br>Les heures de route ne doivent pas être pointées';
print '</span><br><br>';
print '</div></div>';

$nav = '<a class="inline-block valignmiddle" href="?year='.$prev_year."&month=".$prev_month.$paramwithoutdate.'">'.img_previous($langs->trans("Previous"))."</a>\n";
$nav .= " <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), "%Y").", ".dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), "%B")." </span>\n";
$nav .= '<a class="inline-block valignmiddle" href="?year='.$next_year."&month=".$next_month.$paramwithoutdate.'">'.img_next($langs->trans("Next"))."</a>\n";
$nav .= ' '.$form->selectDate(-1, '', 0, 0, 2, "addtime", 1, 1).' ';
$nav .= ' <button type="submit" name="submitdateselect" value="x" class="bordertransp"><span class="fa fa-search"></span></button>';

print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'?'.$param.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="day" value="'.$day.'">';
print '<input type="hidden" name="month" value="'.$month.'">';
print '<input type="hidden" name="year" value="'.$year.'">';
print '<div class="floatleft right'.($conf->dol_optimize_smallscreen ? ' centpercent' : '').'">'.$nav.'</div>'; // We move this before the assign to components so, the default submit button is not the assign to.
print '<div class="clearboth" style="padding-bottom: 20px;"></div>';

$tmpvar = "MAIN_SELECTEDFIELDS_".$varpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$moreforfilter = '';
// Gestion des utilisateurs que l'on peut voir
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= '<div class="inline-block hideonsmartphone"></div>';
$projectListResp = $project->getProjectsAuthorizedForUser($user, 1, 1, 0, " AND ec.fk_c_type_contact = 160");
$userList = $projectstatic->getUserForProjectLeader($projectListResp);
if(!$user->rights->feuilledetemps->feuilledetemps->read) {
	$includeonly = array_merge($userList, $user->getAllChildIds(1));
	if (empty($user->rights->user->user->lire)) {
		$includeonly = array($user->id);
	}
}
$extendedUser = New ExtendedUser3($db);
$exlude = $extendedUser->get_full_treeIds("statut <> 1");
$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('User'), 'user', 'class="paddingright pictofixedwidth"').$form->select_dolusers($search_usertoprocessid ? $search_usertoprocessid : $usertoprocess->id, 'search_usertoprocessid', 0, $exlude, 0, $includeonly, null, 0, 0, 0, '', 0, '', 'maxwidth200');
$moreforfilter .= '</div>';


// Filtre
if (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)) {
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<div class="inline-block"></div>';
	$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('Project'), 'project', 'class="paddingright pictofixedwidth"').'<input type="text" name="search_project_ref" class="maxwidth100" value="'.dol_escape_htmltag($search_project_ref).'">';
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<div class="inline-block"></div>';
	$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('ThirdParty'), 'company', 'class="paddingright pictofixedwidth"').'<input type="text" name="search_thirdparty" class="maxwidth100" value="'.dol_escape_htmltag($search_thirdparty).'">';
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<div class="inline-block"></div>';
	$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('Task'), 'projecttask', 'class="paddingright pictofixedwidth"').'<input type="text" name="search_task_label" class="maxwidth100" value="'.dol_escape_htmltag($search_task_label).'">';
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield nowrap">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	$moreforfilter .= $searchpicto;
	$moreforfilter .= '</div>';
}

if (!empty($moreforfilter)) {
	print '<div id="filtre" class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '<div class="divsearchfield nowrap" style="float: right;">';
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print '<button type="button" title="Plein écran" id="fullScreen" name="fullScreen" class="nobordertransp button_search_x"><span class="fa fa-expand"></span></button>';
	print '<button type="button" title="Voir les favoris" id="seeFavoris" class="nobordertransp button_search_x" onclick="displayFav()" style="vertical-align: middle; margin-left: 4px;"><span class="far fa-star" style="font-size: large; color: var(--colorbackhmenu1);"></span></button>';;
	print '</div>';
	print '</div>';
}


// This must be after the $selectedfields
$addcolspan = 0;
if (!empty($arrayfields['t.planned_workload']['checked'])) {
	$addcolspan++;
}
if (!empty($arrayfields['t.progress']['checked'])) {
	$addcolspan++;
}
if (!empty($arrayfields['timeconsumed']['checked'])) {
	$addcolspan += 2;
}
foreach ($arrayfields as $key => $val) {
	if ($val['checked'] && substr($key, 0, 5) == 'efpt.') {
		$addcolspan++;
	}
}


print '<div class="div-table-responsive" style="min-height: 0px">';
print '<table class="tagtable liste '.($moreforfilter ? " listwithfilterbefore" : "").'" id="tablelines_fdt">'."\n";
print '<thead>';
print '<tr class="liste_titre favoris">';
print '<th class="fixed" colspan="'.(2 + $addcolspan).'" style="min-width: 500px;"></th>';


// Affichage des jours de la semaine
for ($idw = 0; $idw < $nb_jour; $idw++) {
	$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

	if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
		print '<th style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none; border-top: none !important; z-index:1;" width="9%"></th>';
	}

	print '<th width="9%" align="center" style="min-width: 90px; z-index: 1" class="bold hide'.$idw.' day">';
	print dol_print_date($dayinloopfromfirstdaytoshow, '%a');
	print '<br>'.dol_print_date($dayinloopfromfirstdaytoshow, 'dayreduceformat').'</th>';
}
print '<th class="fixed total_title" width="9%" style="min-width: 90px;"><strong>TOTAL</strong></th>';
print "</tr>";


// Affichage de la ligne avec le total de chaque semaine
print '<tr class="liste_titre fixed favoris">';
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

	print '<th class="liste_total_semaine_'.$semaine.'" align="center" colspan='.$taille.'><strong>Semaine '.$weekNumber.' : <span class="totalSemaine" name="totalSemaine'.$weekNumber.'" id="totalSemaine'.$semaine.'_'.$premier_jour.'_'.$dernier_jour.'">&nbsp</span></strong></td>';
	$semaine++;
	$idw += $taille - 1;
}
print '<th class="fixed total_week"></th>';
print '</tr>';


// Affichage de la ligne des congés
$holiday = new extendedHoliday($db);
$typeleaves = $holiday->getTypesNoCP(-1, -1);
$arraytypeleaves = array();
foreach ($typeleaves as $key => $val) {
	$labeltoshow = $val['code'];
	$arraytypeleaves[$val['rowid']] = $labeltoshow;
}	
	
$conges_texte = $holiday->getArrayHoliday($usertoprocess->id, 0, 1);
$cpt = 0; 

print '<tr class="nostrong liste_titre fixed conges">';
	print '<th colspan="'.(2 + $addcolspan).'" '.($multiple_holiday ? 'rowspan="2"' : '').' class="fixed"><strong>Congés</strong>';
	print $form->textwithpicto('', $conges_texte);
	print '</th>';
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
		$keysuffix = '['.$idw.']';

		if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
			print '<th style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></th>';
		}

		if(!empty($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['rowid'][0])) {
			$holiday->fetch((int)$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['rowid'][0]);
			$numberDay = (num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) ? num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) : 1);
			$droit_rtt = $holiday->holidayTypeDroitRTT();
				
			if(!empty($holiday->array_options['options_hour'])) {
				$durationHoliday = $holiday->array_options['options_hour'];
			}
			else {
				$nbDay = floor(num_open_day($holiday->date_debut_gmt, $holiday->date_fin_gmt, 0, 1, $holiday->halfday));
				$duration_hour = (dol_print_date($holiday->date_fin, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt']) ? $nbDay * 7 * 3600 : $nbDay * $conf->global->HEURE_JOUR * 3600);
				if((!empty($userField->array_options['options_pasdroitrtt']) || dol_print_date($holiday->date_fin, '%Y-%m-%d') < '2024-07-01') && ($holiday->halfday == 1 || $holiday->halfday == -1)) {
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
			
			print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? $css_holiday[$dayinloopfromfirstdaytoshow][0] : '').'" colspan="'.($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month && ($dayinloopfromfirstdaytoshow_array[$idw + $numberDay] > $first_day_month || empty($dayinloopfromfirstdaytoshow_array[$idw + $numberDay]))? $numberDay + 1 : $numberDay).'">';

			print $holiday->getNomUrlBlank(2)." ".convertSecondToTime($durationHoliday, 'allhourmin');
			print ' '.$form->selectarray('holiday_type['.$cpt.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cpt.']" disabled', 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);

			$idw += $numberDay - 1;
			$cpt++;
		}
		else {
			print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? ' '.$css_holiday[$dayinloopfromfirstdaytoshow][0] : '').'">';
		}

		print '</th>';
	}
	print '<th class="liste_total center fixed total_holiday"></th>';
print '</tr>';

if($multiple_holiday) {
	print '<tr class="nostrong liste_titre conges">';
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
		$keysuffix = '['.$idw.']';

		if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
			print '<th style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></th>';
		}

		if(!empty($holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['rowid'][1])) {
			$holiday->fetch((int)$holidayWithoutCanceled[$dayinloopfromfirstdaytoshow]['rowid'][1]);
			$numberDay = (num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) ? num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) : 1);
			$droit_rtt = $holiday->holidayTypeDroitRTT();
				
			if(!empty($holiday->array_options['options_hour'])) {
				$durationHoliday = $holiday->array_options['options_hour'];
			}
			else {
				$nbDay = floor(num_open_day($holiday->date_debut_gmt, $holiday->date_fin_gmt, 0, 1, $holiday->halfday));
				$duration_hour = (dol_print_date($holiday->date_fin, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt']) ? $nbDay * 7 * 3600 : $nbDay * $conf->global->HEURE_JOUR * 3600);
				if((!empty($userField->array_options['options_pasdroitrtt']) || dol_print_date($holiday->date_fin, '%Y-%m-%d') < '2024-07-01') && ($holiday->halfday == 1 || $holiday->halfday == -1)) {
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
			
			print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? $css_holiday[$dayinloopfromfirstdaytoshow][1] : '').'" colspan="'.($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month && ($dayinloopfromfirstdaytoshow_array[$idw + $numberDay] > $first_day_month || empty($dayinloopfromfirstdaytoshow_array[$idw + $numberDay]))? $numberDay + 1 : $numberDay).'">';

			print $holiday->getNomUrlBlank(2)." ".convertSecondToTime($durationHoliday, 'allhourmin');
			print ' '.$form->selectarray('holiday_type['.$cpt.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cpt.']" disabled', 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);

			$idw += $numberDay - 1;
			$cpt++;
		}
		else {
			print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? ' '.$css_holiday[$dayinloopfromfirstdaytoshow][1] : '').'">';
		}

		print '</th>';
	}
	print '<th class="liste_total center fixed total_holiday"></th>';
	print '</tr>';
}

print '</thead>';

$tasksarray = $taskstatic->getTasksArray(0, 0, 0, $socid, 0, $search_project_ref, $onlyopenedproject, $morewherefilter, 0, 0, $extrafields); // We want to see all tasks of open project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.

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

	// Récupération des favoris
	$favoris = $object->getFavoris($usertoprocess->id);

	// Récupération des notes
	$notes = $task->fetchAllNotes($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);

	// Récupération des autres temps (compagnonnage/heure de nuit/heure de route/epi respiratoire)
	$projet_task_time_other = New Projet_task_time_other($db);
	$otherTime = $projet_task_time_other->getOtherTime($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);

	// Affichage de l'interieur du tableau
	$totalforvisibletasks = FeuilleDeTempsLinesPerWeek($j, $firstdaytoshow, $lastdaytoshow, $usertoprocess, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restrictviewformytask, $isavailable, 0, $arrayfields, $extrafields, 
														$nb_jour, $can_modify_fdt, $css, $ecart_jour, $typeDeplacement, $dayinloopfromfirstdaytoshow_array, 0, 
														$temps_prec, $temps_suiv, $temps_prec_hs25, $temps_suiv_hs25, $temps_prec_hs50, $temps_suiv_hs50, $notes, $otherTime, $timeSpentMonth, $timeSpentWeek, $month_now, $timeHoliday, $heure_semaine, $heure_semaine_hs, $usertoprocess, $favoris, $param);
} else {
	print '<tr><td colspan="'.(4 + $addcolspan + $nb_jour).'"><span class="opacitymedium">'.$langs->trans("NoAssignedTasks").'</span></td></tr>';
}

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
	print '<tr class="oddeven othertaskwithtime favoris">';
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
	print '<tr class="trforbreak favoris">';
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

FeuilleDeTempsDeplacement($firstdaytoshow, $lastdaytoshow, $nb_jour, $usertoprocess, $css, $ecart_jour, !$can_modify_fdt, $addcolspan, $dayinloopfromfirstdaytoshow_array, $month_now);

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

print '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.count($tasksarray).'"/>'."\n";

print '<br><div class="center" style="margin-top: 14px;">';

// Affichage du bouton "ENREGISTRER"
if($can_modify_fdt){
	print '<input onclick="disableNullInput()" type="submit" class="butAction" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'" style="margin-right: 0px;height: 40px;accent-color: ;">';
}

// Affichage du bouton "TRANSMETRE"
if($object->id == 0 || ($object->id > 0 && $object->status == FeuilleDeTemps::STATUS_DRAFT)){
	print '<input onclick="disableNullInput()" type="submit" class="butActionDelete" name="transmettre" value="Transmettre" style="margin-right: 0px;height: 40px;accent-color: ;">';
}

print '</div>';
print '</form>'."\n\n";

// Appel des fonctions JS
$modeinput = 'hours';
if ($conf->use_javascript_ajax) {	
	print "\n<!-- JS CODE TO ENABLE Tooltips on all object with class classfortooltip -->\n";
	print '<script type="text/javascript">'."\n";
// 	print "jQuery(document).ready(function () {\n";
// 	print '		jQuery(".timesheetalreadyrecorded").tooltip({
// 					show: { collision: "flipfit", effect:\'toggle\', delay:50 },
// 					hide: { effect:\'toggle\', delay: 50 },
// 					tooltipClass: "mytooltip",
// 					content: function () {
// 						return \''.dol_escape_js($langs->trans("TimeAlreadyRecorded", $usertoprocess->getFullName($langs))).'\';
// 					}
// 				});'."\n";
// -	print "\n});\n";

	if($showFav) {
		print " displayFav();";
	}

	//print " redimenssion();";
	print " $('.close-intro').click(function() {
				$('#fonctionnement').slideUp();
				$('.open-intro').show();
				$('.close-intro').hide();
				redimenssion('close');
			});
			$('.open-intro').click(function() { 
				$('#fonctionnement').slideDown(function() {
					// Cette fonction de rappel sera exécutée après la fin de slideDown
					$('.close-intro').show();
					$('.open-intro').hide();
					redimenssion('open');
				});
			});";

	if (count($tasksarray) > 0) {
		for($idw = 0; $idw < $nb_jour; $idw++) {
			$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];

			if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-06-03' && $heure_semaine == $conf->global->HEURE_SEMAINE) {
				$tmp_heure_semaine = 35;
			}
			else {
				$tmp_heure_semaine = $heure_semaine;
			}

			//print ' updateTotalLoad_TS('.$idw.',\''.$modeinput.'\','.$nb_jour.');';
			if(dol_print_date($tmpday, '%a') == 'Dim' || dol_print_date($tmpday, '%d/%m/%Y') == dol_print_date($lastdaytoshow, '%d/%m/%Y')) {
				$weekNumber = date("W", $tmpday);
				if($weekNumber == date("W", $firstdaytoshow)) {
					print ' updateTotalWeek('.($temps_prec ? $temps_prec : 0).', 0, \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');';
				}
				elseif($weekNumber == date("W", $lastdaytoshow)) {
					print ' updateTotalWeek(0, '.($temps_suiv ? $temps_suiv : 0).', \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');';
				}
				else {
					print ' updateTotalWeek(0, 0, \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');';
				}
			}
		}
		print ' updateAllTotalLoad_TS(\''.$modeinput.'\','.$nb_jour.', '.$ecart_jour.');';
	}
	
	print '</script>';
}


// End of page
llxFooter();
$db->close();
