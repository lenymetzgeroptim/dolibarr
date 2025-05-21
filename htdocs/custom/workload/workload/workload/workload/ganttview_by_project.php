<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
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
 *	\file       htdocs/projet/ganttview.php
 *	\ingroup    projet
 *	\brief      Gantt diagramm of a project
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/workload/class/charge.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$id = GETPOST('id', 'intcomma');
$ref = GETPOST('ref', 'alpha');
// $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'contributor'; // The action 'create'/'add', 'edit'/'update', 'view', ...

$ur = GETPOST('ur', 'alpha');
$gr = GETPOST('gr', 'alpha');
$sk = GETPOST('sk', 'alpha');
$pr = GETPOST('pr', 'alpha');
$cd = GETPOST('cd', 'alpha');
$dv = GETPOST('dv', 'alpha');
$rp = GETPOST('rp', 'alpha');
$an = GETPOST('an', 'alpha');
$ag = GETPOST('ag', 'alpha');

$mode = GETPOST('mode', 'alpha');
$mine = ($mode == 'mine' ? 1 : 0);
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$now = dol_now();

$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);

// $arr_user = GETPOST('arr_user');
// $un_order = GETPOST('un_order');
// $un_project = GETPOST('un_project');
// $arr_manager = GETPOST('arr_manager');
// $arr_group = GETPOST('arr_group');
// $arr_skill = GETPOST('arr_skill');
// $arr_agence = GETPOST('arr_agence');
// $arr_resp = GETPOST('arr_resp');
// $arr_proj = GETPOST('arr_proj');
// $arr_propal = GETPOST('arr_propal');
// $arr_order = GETPOST('arr_order');

// $arr_responsible = GETPOST('arr_responsible');
$occupied =  GETPOST('case');

$object = new User($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once
if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) {
	$object->fetchComments();
}

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

// Load translation files required by the page
$langs->loadlangs(array('users', 'projects'));
$rsc = new Charge($db);
$charge = new User($db);

if(GETPOST('case') == "on") {
	$occupied = 'on';
}


/*
 * Actions
 */
//To restart
if(GETPOST('remove') == "refresh") {
	if(isset($arr_user)) {
		unset($arr_user);
	}
	if(isset($arr_group)) {
		unset($arr_group);
	}
	if(isset($arr_manager)) {
		unset($arr_manager);
	}
	if(isset($un_order)) {
		unset($un_order);
	}
	if(isset($un_project)) {
		unset($un_project);
	}
	if(isset($arr_skill)) {
		unset($arr_skill);
	}
	if(isset($arr_agence)) {
		unset($arr_agence);
	}
	if(isset($arr_resp)) {
		unset($arr_resp);
	}
	if(isset($arr_proj)) {
		unset($arr_proj);
	}
	if(isset($arr_propal)) {
		unset($arr_propal);
	}
	if(isset($arr_order)) {
		unset($arr_order);
	}
	
	$date_start = 0;
	$date_end = 0;
}

$rscIds = $rsc->getUsers();
$projectIds = $rsc->fetchProjects();

$commArray = $rsc->getContactsAndOrders($arr_user, $date_start, $date_end, 'project');
$propArray = $rsc->getContactsAndPropals($arr_user, $date_start, $date_end);
// $projArray = $rsc->getContactsAndProject($arr_user, $date_start, $date_end);
// $jobskills = $rsc->getSkillsAndUsers();

$data = array_merge($commArray, $propArray);
// $data = array_merge($comArray, $propArray, $projArray);
//get users merged with data
$ressources = array_merge($data, $rscIds);

// $projectIds = array_map(function($val) { foreach($val as $v) return $val->fk_projet;}, $projets);

// $ressources = array_merge($data, $projectIds);
$commarr = array_merge($commArray, $rscIds);
// $projectarr = array_merge($projArray, $rscIds);


//If no date filter
if (empty($date_start)) {
	// $date_start = dol_get_first_day(dol_print_date($now, 'Y'));
	$date_start = dol_time_plus_duree($now, -1, 'y');
}



if (empty($date_end)) {
	$date_end = strtotime($dateend);
}

$resps = $rsc->getRespOfProject();


$projs = $rsc->select_multi_projects();
$orders = $rsc->select_multi_orders();
$devs = $rsc->select_multi_propals();


// $groupslist = $usergroup->listUsersForGroup();

// if (!empty($groupslist)) {
// 	foreach ($groupslist as $groupforuser) {
// 		$groupIds[] = $groupforuser->id;
// 	}
// }

// foreach($rsc->getManager() as $key => $value) {
// 	if(in_array($key, $arr_manager)) {
// 		foreach($value as $val) {
// 			$antenneUser[] = $val;
// 		}
// 	}
// }

// if(count($jobskills) > 0) {
// 	foreach($jobskills as $key => $val) {
// 		$arrskill[$val->fk_job] = $val->job_label;
// 		$searchuser[] = $val->fk_user.'_'.$val->fk_job;
// 	}
// }

include DOL_DOCUMENT_ROOT.'/custom/workload/core/actions/actions_filter.php'; 


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$userstatic = new User($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);
$task = new Task($db);

$arrayofcss = array('/includes/jsgantt/jsgantt.css');

if (!empty($conf->use_javascript_ajax)) {
	$arrayofjs = array(
	'/includes/jsgantt/jsgantt.js',
	'/projet/jsgantt_language.js.php?lang='.$langs->defaultlang
	);
}

//$title=$langs->trans("Gantt").($object->ref?' - '.$object->ref.' '.$object->name:'');
$title = $langs->trans("Projet : Cde/devis - collaborateurs");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = ($object->ref ? $object->ref.' '.$object->name.' - ' : '').$langs->trans("Gantt");
}
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";

llxHeader("", $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

// Link to create task
$linktocreatetaskParam = array();
$linktocreatetaskUserRight = false;
if ($user->rights->workload->all->creer || $user->rights->workload->creer) {
	if ($object->public || $userWrite > 0) {
		$linktocreatetaskUserRight = true;
	} else {
		$linktocreatetaskParam['attr']['title'] = $langs->trans("NotOwnerOfProject");
	}
}

// $linktocreatetask = dolGetButtonTitle($langs->trans('AddTask'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id.'&action=create'.$param.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id), '', $linktocreatetaskUserRight, $linktocreatetaskParam);

//$linktotasks = dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars paddingleft imgforviewmode', DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id, '', 1, array('morecss'=>'reposition'));
$linktotasks .= dolGetButtonTitle($langs->trans('Projet : Collaborateus - Absence'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_plan_holiday.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Cde : Collaborateur - Projet'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_by_commande.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Projet : Cde/devis - collaborateur'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_by_project.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Collaborateurs : projets/devis/cde'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttviewglobal.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Collaborateurs : devis & cde'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Cumul ETP'), '', 'fa fa-chart-line paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/workloadindex.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));;

$linktotasks .= '<td class="liste_titre right" width="5%">';
$linktotasks .= '<button title="Réinitialiser le filtre" style="background:transparent;" role="button" class=" btn-message-a tosend btn-tosend msg2" data-uia="nmhp-card-cta+hero_fuji" type="submit" name="remove" value="refresh">';
$linktotasks .= '<div aria-hidden="true" class="default-ltr-cache-17uj5h e1ax5wel0">';
$linktotasks .= '<span class="fas fa-sync-alt" style="font-size:1em;color:#8a8a8a;"></span>';
$linktotasks .= '</div>';
$linktotasks .= '</td>';
//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'generic', 0, '', '', 0, 1);
print load_fiche_titre($title, $linktotasks.' &nbsp; '.$linktocreatetask, 'projecttask');

// if(count($jobskills) > 0) {
// 	foreach($jobskills as $key => $val) {
// 		$arrkill[$val->fk_user] = $val->job_label;
// 	}
// }
// $arrkill = array_unique($arrkill);

if (count($commarr ) > 0) {
	foreach ($commarr as $key => $val) {
		$existekey4[] = $val->id;
		$count = array_count_values($existekey4);
	}

	foreach($count as $i => $frequency4) {
		$charge->fetch($i);
		$ids[$i] = $charge->firstname.' '.$charge->lastname;
		// if($frequency4 == 1) {
		// 	$unorder[$i] = $charge->firstname.' '.$charge->lastname;
		// }
	}
}

// if (count($projectarr) > 0) {
// 	foreach ($projectarr as $key => $val) {
// 		$existekey3[] = $val->id;
// 		$count = array_count_values($existekey3);
// 	}

// 	foreach($count as $j => $frequency3) {
// 		$charge->fetch($j);
// 		$ids[$j] = $charge->firstname.' '.$charge->lastname;
	
// 		if($frequency3 <= 1) {
// 			$unproject[$j] = $charge->firstname.' '.$charge->lastname;
// 		}
// 	}
// }
$projet = new Project($db);
// if (count($projArray) > 0) {
// 	foreach ($projArray as $key => $val) { 
// 			$userinproject[$val->id] = $val->ref;
// 	 }
// }
// var_dump($userinproject[][41]);
// if (count($projArray) > 0) {
// 	foreach ($projArray as $key => $val) {
// 		$exist[] = $val->id;
// 		$count = array_count_values($exist);
// 	}

// 	foreach($count as $j => $freq1) {
// 		$charge->fetch($j);
// 		$projet->fetch($val->element_id);
// 		// $ids[$j] = $charge->firstname.' '.$charge->lastname;
// 		if($freq1 <= 1) {
// 			$charge->fetch($j);
// 			$projbyuser[$j] = $charge->getNomUrl(2).' '.$charge->login.'-'.$projet->ref;
// 			$projbyproj[$projet->id] = $charge->getNomUrl(2).' '.$charge->login.'-'.$projet->ref;
// 		}
// 	}
// }

$managers = $rsc->getManager();


if (count($managers) > 0) {
	foreach ($managers as $key => $value) {
		$charge->fetch($key);
		$manager[$key] = $charge->firstname.' '.$charge->lastname;
	}
}

$arrgroup = $rsc->listGroups();
asort($arrgroup);


foreach ($ressources as $resource) {
    if(isset($resource->date_start))
    $dateStartList[] = $resource->date_start;

    // Vous pouvez décommenter cette ligne pour inspecter les dates de fin si nécessaire
    // var_dump($resource->date_end);
}

// Trouve la date de début la plus ancienne
$earliestDateStart = min($dateStartList);

$earliestYearStart = dol_print_date($earliestDateStart, 'Y');


?>
<style>

div.fichetwothirdright {
  float: right;
  width: calc(33.33% - 14px) !important;
}

div.fichethirdleft {
  float: left;
  width: calc(33.33% - 14px) !important;
}

  /* .gmainright {
	display: flex;
	justify-content: center;
	align-items: center;
} */
div#GanttChartDIVglisthead, div#GanttChartDIVgcharthead {
	/* width: fit-content!important; */
}

div.divsearchfield {
  /* float: left; */
  /* display: block;
  margin-right: 12px; */
  /* margin-left: 2px;
  margin-top: 4px;
  margin-bottom: 4px;
  padding-left: 2px; */
}

div.liste_titre_bydiv .divsearchfield {
  padding: 2px 1px 2px 7px;
  display: flex;
  align-content: center;
  align-items: end;
  align-content: space-around;
}
</style>
<script>
	document.addEventListener("DOMContentLoaded", function () {
		// Sélection du conteneur de défilement
		const ganttContainer = document.getElementById("GanttChartDIVgcharthead");
		const table = document.getElementById("GanttChartDIVchartTableh");

		// Largeur d'une cellule correspondant à un mois
		const monthWidth = 28; 
		const currentDate = new Date();

		// Calculer l'année et le mois actuels
		const currentYear = currentDate.getFullYear();
		const currentMonth = currentDate.getMonth(); // 0 = Janvier, 11 = Décembre

		// Trouver l'index du mois actuel dans le tableau
		let offset = 0;
		const startYear = <?php echo json_encode($earliestYearStart); ?>; //valeur en fonction de diagramme
		if (currentYear >= startYear) {
			offset = (currentYear - startYear) * 12 + currentMonth;
		}

		// Calculer la position de défilement
		const scrollPosition = offset * monthWidth;

		// Appliquer le défilement horizontal
		ganttContainer.scrollLeft = scrollPosition;
	});
</script>
<?php

print '<div class="fichecenter">';
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre" width="40%"><td class="liste_titre">'.$langs->trans("Filter").'</td>';

print '<td class="liste_titre" width="30%">';
// print $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0).'  '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
print '</td>';

// print '<td class="liste_titre right" width="25%">';
// 		print '<div class="smile-rating-container">';
// 		print ' <div class="smile-rating-toggle-container">';
// 		print '<div class="submit-rating" style="display:flex; flex-direction: row-reverse;">';
	
// 		if($occupied == 'off' || $occupied == '') {
// 			print '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=on&token='.newToken().'&amp;action=filter">';
// 			print '<span class="fas fa-toggle-off" for="meh" style=""></span><label class="rating-label rating-label-meh" style="font-size:14px;color:black;padding-left: 15px;padding-right: 15px;">Dispoinibilité</label>'; 
// 			print '</a>';
// 		}

// 		if($occupied == 'on') {
// 			print '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=off&token='.newToken().'&amp;action=filter">';
// 			print '<span class="fas fa-toggle-on" for="fun" style="color: rgb(40,80,139);"></span><label class="rating-label rating-label-fun" style="color: rgb(40,80,139);font-size:14px;padding-left: 15px;padding-right: 15px;">Innocupées</label>';
// 			print '</a>';
// 		}
// 		print '</div>';
// 		print '</div>';
// 		print '</div>';
// 	print '</td>';
// 	print '<td class="liste_titre right" width="5%"><button title="Réinitialiser" style="background:transparent;" role="button" class=" btn-message-a tosend btn-tosend msg2" data-uia="nmhp-card-cta+hero_fuji" type="submit" name="remove" value="refresh"><div aria-hidden="true" class="default-ltr-cache-17uj5h e1ax5wel0"><span class="fas fa-sync-alt" style="font-size:1em;color:#8a8a8a;"></span></div></div></div><div class="default-ltr-cache-vgp0nn e9eyrqp3"></div></div></td>';
	// print '<td><button role="button" class=" btn-message-a tosend btn-tosend msg2" data-uia="nmhp-card-cta+hero_fuji" type="submit">Lancer<div aria-hidden="true" class="default-ltr-cache-17uj5h e1ax5wel0"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-mirrorinrtl="true" class="default-ltr-cache-4z3qvp e1svuwfo1" data-name="ChevronRight" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5859 12L8.29303 19.2928L9.70725 20.7071L17.7072 12.7071C17.8948 12.5195 18.0001 12.2652 18.0001 12C18.0001 11.7347 17.8948 11.4804 17.7072 11.2928L9.70724 3.29285L8.29303 4.70706L15.5859 12Z" fill="currentColor"></path></svg></div></button></div></form></div><div class="center-pixel default-ltr-cache-1qms9jn ekwtkbw0"></div></div></div><div class="default-ltr-cache-vgp0nn e9eyrqp3"></div></div></td>';
print '</tr>';
print '</table>';
print '<div class="fichethirdleft">';
print '<table>';

// user
print '<tr><td class="left">'.$langs->trans("Employees").'</td><td class="left">';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_user',  $ids,  $arr_user, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td></tr>';
// group
print '<tr><td>'.$langs->trans("Groups").'</td><td>';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_group', $arrgroup, $arr_group, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td></tr>';
// emplois
print '<tr><td>'.$langs->trans("Emplois").'</td><td class="nowrap tdoverflowmax250">';
print img_picto('', 'skill', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_job', $arrjobs, $arr_job, '', '', '', '', '200pxx', '','', 'Tous');
print '</td></tr>';
// Compétences
print '<tr><td>'.$langs->trans("skill").'s'.'</td><td class="nowrap tdoverflowmax250">';
print img_picto('', 'skill', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_skill', $arrskills, $arr_skill, '', '', '', '', '200pxx', '','', 'Tous');
print '</td></tr>';
print '</td></tr>';
print '</table>';

print '</div><div class="fichetwothirdright">';

print '<table>';

// user
print '<tr><td class="left">'.$langs->trans("Responsables").'</td><td class="left">';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_resp',  $resps,  $arr_resp, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td></tr>';
// user
print '<tr><td class="left">'.$langs->trans("Res. antenne").'</td><td class="left">';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_manager',  $manager,  $arr_manager, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td></tr>';
// Compétences
print '<tr><td>'.$langs->trans("Agence").'</td><td>';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_agence', $agences, $arr_agence, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td></tr>';
print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright">';
print '<table>';
// user
print '<tr><td class="left">'.$langs->trans("Projects").'</td><td class="left">';
print img_picto('', 'project', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_proj',  $projs,  $arr_proj, '', '', '', '', '200px maxwidth300', '','', 'Tous');
print '</td></tr>';
// group
print '<tr><td>'.$langs->trans("Orders").'</td><td>';
print img_picto('', 'order', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_order', $orders, $arr_order, '', '', '', '', '200px maxwidth300', '','', 'Tous');
print '</td></tr>';
// Compétences
print '<tr><td>'.$langs->trans("Devis").'</td><td>';
print img_picto('', 'propal', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_propal', $devs, $arr_propal, '', '', '', '', '200px maxwidth300', '','', 'Tous');
print '</td></tr>';
print '</table>';
print '</div>';
print '<div class="fichecenter">';
print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield center">';
print '<div style="padding-left: 40%;">';
print '<button style="background: var(--butactionbg);color: var(--textbutaction);border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="filter" value="start">'.$langs->trans("Refresh").'';
print '</div>';

print '<div class="smile-rating-container" style="padding-left: 35%;">';
		print ' <div class="smile-rating-toggle-container">';
		print '<div class="submit-rating" style="display:flex; flex-direction: row-reverse;">';
	
		if($occupied == 'off' || $occupied == '') {
			print '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=on&token='.newToken().'&amp;action=filter">';
			print '<span class="fas fa-toggle-off" for="meh" style=""></span><label class="rating-label rating-label-meh" style="font-size:14px;color:black;padding-left: 15px;padding-right: 15px;">Dispoinibilité</label>'; 
			print '</a>';
		}

		if($occupied == 'on') {
			print '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=off&token='.newToken().'&amp;action=filter">';
			print '<span class="fas fa-toggle-on" for="fun" style="color: rgb(40,80,139);"></span><label class="rating-label rating-label-fun" style="color: rgb(40,80,139);font-size:14px;padding-left: 15px;padding-right: 15px;">Innocupées</label>';
			print '</a>';
		}
		print '</div>';
		print '</div>';
		print '</div>';

		print '</div>';		
print '</div>';
// print '<div class="divsearchfield">';
// 		print '<div class="smile-rating-container">';
// 		print ' <div class="smile-rating-toggle-container">';
// 		print '<div class="submit-rating" style="display:flex; flex-direction: row-reverse;">';
	
// 		if($occupied == 'off' || $occupied == '') {
// 			print '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=on&token='.newToken().'&amp;action=filter">';
// 			print '<span class="fas fa-toggle-off" for="meh" style=""></span><label class="rating-label rating-label-meh" style="font-size:14px;color:black;padding-left: 15px;padding-right: 15px;">Dispoinibilité</label>'; 
// 			print '</a>';
// 		}

// 		if($occupied == 'on') {
// 			print '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=off&token='.newToken().'&amp;action=filter">';
// 			print '<span class="fas fa-toggle-on" for="fun" style="color: rgb(40,80,139);"></span><label class="rating-label rating-label-fun" style="color: rgb(40,80,139);font-size:14px;padding-left: 15px;padding-right: 15px;">Innocupées</label>';
// 			print '</a>';
// 		}
// 		print '</div>';
// 		print '</div>';
// 		print '</div>';
// 	print '</div>';
print '</div>';
print '</form>';

$member = new User($db);
// var_dump($ressources);
if (count($ressources) > 0) {
	//to calculte frequency in data 
	foreach ($ressources as $key => $val) {
		if(isset($val->missing)) {
			$etp_proj[$val->fk_projet]['etp_proj'] = intval($val->pdc_etp_proj);
		}
		$etp_code[$val->fk_projet]['etp_code'] = intval($val->pdc_etp_cde);
	}

	 //give etp condition to display color and message warnning if missing etp
	 foreach($etp_proj as $projkey => $projval) {
		foreach($etp_code as $codekey => $codeval) {
			if($projkey == $codekey) {
				$etp[$projkey] = $projval['etp_proj'] > $codeval['etp_code'];
			}
			$diff[$projkey] = $projval['etp_proj'] - $codeval['etp_code'];
		}
		
	}
	
		

	$dateformat = $langs->trans("FormatDateShortJQuery"); // Used by include ganttchart.inc.php later
	$datehourformat = $langs->trans("FormatDateShortJQuery").' '.$langs->trans("FormatHourShortJQuery"); // Used by include ganttchart.inc.php later
	$array_contacts = array();
	$members = array();
	$member_dependencies = array();
	$membercursor = 0;

	foreach ($ressources as $key => $val) {
		// condition to dispaly available projects either with users and no order or without user 
		$condition = $occupied == 'on' ? isset($val->avl) || ($val->idref !== "CO" && $val->fk_projet !== null) : $val->fk_projet !== null;
		if($condition) { 
			// Show Gant diagram from $ressources using JSGant
			// data array are sorted by "commande -> inter -> date related to project, project -> inter -> date not related, project -> no data"
			$idparent = ($val->s ? $val->fk_projet : '-'.$val->fk_projet);
			
			$members[$membercursor]['member_id'] = $val->element_id;
			$members[$membercursor]['member_idref'] = $val->idref;
			$member->fetch($val->id, '');
			

			
			$members[$membercursor]['member_alternate_id'] = ($membercursor + 1); // An id that has same order than position (required by ganttchart)
			$members[$membercursor]['member_member_id'] = $val->fk_projet;
			$members[$membercursor]['member_parent'] = $idparent;

			$members[$membercursor]['member_is_group'] = 0;
		
			if($val->idref === "CO") {
				// $members[$membercursor]['member_id'] = $val->element_id;
				$members[$membercursor]['member_css'] = 'gtaskblue';
			}elseif($val->idref == "PR"){
				// $members[$membercursor]['member_id'] = $val->element_id;
				$members[$membercursor]['member_css'] = 'gtaskgreen';
			}elseif($val->idref == "PROJ") {
				// $members[$membercursor]['member_id'] = $val->element_id;
				$members[$membercursor]['member_css'] = 'gtaskbluecomplete';
			}
			
			 $members[$membercursor]['member_milestone'] = '0';

			//right to access to project and users
			$user->rights->user->user->lire
			? $emmployee =  $member->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1)
			: $employee = $member->login;

			$user->rights->projet->lire
			? $refproj = '<a href="'.DOL_URL_ROOT.'/projet/contact.php?id='.$val->fk_projet.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$val->projet_ref.'</a>'
			: $refproj = $val->projet_ref;

			$user->rights->commande->lire
			? $refcomm = '<a href="'.DOL_URL_ROOT.'/commande/contact.php?id='.$val->element_id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$val->ref.'</a>'
			: $refcomm = $val->ref;
			
			$user->rights->propal->lire
			? $refpropal = '<a href="'.DOL_URL_ROOT.'/comm/propal/contact.php?id='.$val->element_id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$val->ref.'</a>'
			: $refpropal = $val->ref;

			
			if($val->idref == "CO") {
			$members[$membercursor]['member_name'] = $refcomm.'- '.$emmployee;
			}elseif($val->idref == "PR") {
				$members[$membercursor]['member_name'] = $refpropal.'- '.$emmployee;
			 }
			elseif(isset($val->id) && $val->idref == "PROJ") {
				$members[$membercursor]['member_name'] = $refproj.'- '.$emmployee;
			 }elseif(!isset($val->id) && $val->idref == "PROJ") {
				$members[$membercursor]['member_name'] = $refproj.'- Pas de ressource';
			 }
		
			$members[$membercursor]['member_start_date'] = dol_print_date($val->date_start, '%Y-%m-%d');
			$members[$membercursor]['member_end_date'] = dol_print_date($val->date_end, '%Y-%m-%d');
			$members[$membercursor]['member_color'] = 'b4d1ea';
			
			// $idofusers = $task->getListContactId('internal');
			// $idofcontacts = $task->getListContactId('external');
	
			/* For JSGanttImproved */
			$members[$membercursor]['member_resources'] = $s;
			if($val->idref === "CO") {
				$members[$membercursor]['member_resources'] = '<span title="Les contacts qui sont prévus sur la commande">'.$val->pdc_etp_cde.'</span>';
				$label = 'la commande';
			}
			if($val->idref === "PR"){
				$label = 'le devis';
				$members[$membercursor]['member_resources'] = '<span title="Les contacts qui sont prévus sur le devis">'.$val->pdc_etp_devis.'</span>';
			}
			
			$diff = $val->pdc_etp_cde < $val->pdc_etp_proj;
			if($val->idref == "PROJ") {
				$label = 'le projet';
				$val->missing !== null ? $missing = abs($val->missing) : $missing = 0;
				 if($etp[$val->fk_projet]) {
					$members[$membercursor]['member_resources'] = '<span class="classfortooltip badge badge-warning" title="'.$missing.' manquant(s) (contacts réels sur le projet par rapport aux contacts prévus sur les commandes)"><i class="fa fa-exclamation-triangle"></i>'.$val->pdc_etp_proj.'</span>';
				// }elseif(isset($val->pdc_etp_proj_no_code)) {
				// 	$members[$membercursor]['member_resources'] = '<span class="classfortooltip badge badge-info" title="'.$missing.' manquant(s) (aucune commande pour ce projet)">'.$val->pdc_etp_proj.'</span>';
				}else{
					$members[$membercursor]['member_resources'] = '<span class="classfortooltip badge badge-info" title="'.$missing.' manquant(s) (contacts réels sur le projet par rapport aux contacts prévus sur les commandes)">'.$val->pdc_etp_proj.'</span>';
				}
				if($val->pdc_etp_proj == 0) {
					$members[$membercursor]['member_resources'] = '<span class="classfortooltip badge badge-info" title="Aucune ressource ni commande ne sont affectées à ce projet">'.$val->pdc_etp_proj.'</span>';
				}
			}

			


			$members[$membercursor]['note'] = '* Plus d\'informations : redirection ver '.$label.' <br> ';
			$membercursor++;
		}
	}

	// Search parent to set element_parent_alternate_id (requird by ganttchart)
	foreach ($members as $tmpkey => $tmptask) {
		foreach ($members as $tmptask2) {
			 if ($tmptask2['member_id'] == $tmptask['member_parent']) {
				$members[$tmpkey]['member_parent_alternate_id'] = $tmptask2['member_alternate_id'];
				 break;
			 }
		}
		 if (empty($members[$tmpkey]['member_parent_alternate_id'])) {
			$members[$tmpkey]['member_parent_alternate_id'] = $members[$tmpkey]['member_parent'];
		 }
	}

	print "\n";

	if (!empty($conf->use_javascript_ajax)) {

		// How the date for data are formated (format used bu jsgantt)
		$dateformatinput = 'yyyy-mm-dd';
		// How the date for data are formated (format used by dol_print_date)
		$dateformatinput2 = 'standard';
		//var_dump($dateformatinput);
		//var_dump($dateformatinput2);

		$moreforfilter = '<div class="liste_titre liste_titre_bydiv centpercent">';

		$moreforfilter .= '<div class="divsearchfield">';
		//$moreforfilter .= $langs->trans("DataAssignedTo").': ';
		// $moreforfilter .= '<td class="liste_titre right" width="25%">';
		// $moreforfilter .= '<div class="smile-rating-container">';
		// $moreforfilter .= ' <div class="smile-rating-toggle-container">';
		// $moreforfilter .= '<div class="submit-rating" style="display:flex; flex-direction: row-reverse;">';
	
		// if($occupied == 'off' || $occupied == '') {
		// 	$moreforfilter .= '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=on&token='.newToken().'&amp;action=filter">';
		// 	$moreforfilter .= '<span class="fas fa-toggle-off" for="meh" style=""></span><label class="rating-label rating-label-meh" style="font-size:14px;color:black;padding-left: 15px;padding-right: 15px;">Dispoinibilité</label>'; 
		// 	$moreforfilter .= '</a>';
		// }

		// if($occupied == 'on') {
		// 	$moreforfilter .= '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=off&token='.newToken().'&amp;action=filter">';
		// 	$moreforfilter .= '<span class="fas fa-toggle-on" for="fun" style="color: rgb(40,80,139);"></span><label class="rating-label rating-label-fun" style="color: rgb(40,80,139);font-size:14px;padding-left: 15px;padding-right: 15px;">Innocupées</label>';
		// 	$moreforfilter .= '</a>';
		// }
		// $moreforfilter .= '</div>';
		// $moreforfilter .= '</div>';
		// $moreforfilter .= '</div>';
		// $moreforfilter .= '</td>';
		$moreforfilter .= '&nbsp;';
		$moreforfilter .= '</div>';

		$moreforfilter .= '</div>';

		// print $moreforfilter;

		print '<div class="div-table-responsive">';

		print '<div id="tabs" class="gantt" style="width: 80vw;">'."\n";
		include_once DOL_DOCUMENT_ROOT.'/custom/workload/ganttchart_by_project.inc.php';

		print '</div>'."\n";

		print '</div>';
	} else {
		$langs->load("admin");
		print $langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
	}
} else {
	print '<div class="opacitymedium">'.$langs->trans("NoRecordFound").'</div>';
}


// End of page
llxFooter();
$db->close();
