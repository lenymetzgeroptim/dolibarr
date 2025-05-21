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
require_once DOL_DOCUMENT_ROOT.'/custom/workload/class/charge.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

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

//test
$rsc = new Charge($db);
$employee = new User($db);
$proj = new Project($db);

$rscIds = $rsc->getUsers();
$absArray = $rsc->getAbsentUsers();

// Récupération des données
$fetchData = function($type) use ($rsc, $arr_user, $date_start, $date_end) {
    return $rsc->getContactsAndOrders($arr_user, $date_start, $date_end, $type);
};

$comArray = $fetchData('noproject');
$comArrayProj = $fetchData('project');

$commArray = $fetchData('order');
$propArray = $rsc->getContactsAndPropals($arr_user, $date_start, $date_end);

$data = array_merge($comArray, $propArray);
$dataProj = array_merge($comArrayProj, $propArray);
var_dump($comArrayProj);
$dataComm = $commArray;
$dataAbs = array_merge($absArray);
$dataProjAbs = $comArrayProj;

// Fusionner les absences avec les projets
foreach ($comArrayProj as &$commItem) {
    foreach ($absArray as $absItem) {
        if ($commItem->id === $absItem->id) {
            foreach (["date_start", "date_end", "holidayref", "idref", "status"] as $field) {
                $commItem->$field = $absItem->$field;
            }
        }
    }
}
unset($commItem);

// Vérification et ajout des ressources manquantes
$addMissingResources = function(&$dataToFilter) use ($rscIds) {
    $existingIds = array_column($dataToFilter, 'id');
    foreach ($rscIds as $rscId) {
        if (!in_array($rscId->id, $existingIds)) {
            $dataToFilter[] = $rscId;
        }
    }
};

foreach ([$data, $dataComm, $dataProj, $dataAbs, $dataProjAbs] as &$dataset) {
    $addMissingResources($dataset);
}
unset($dataset);


// Fonction de tri des ressources
$sortResources = function(&$resources) {
    usort($resources, function($a, $b) {
        return ($a->id_element == $b->id_element) ? ($a->id - $b->id) : ($a->id_element - $b->id_element);
    });
};

// foreach ($rscIds as $key => $value) {
//     if (!array_key_exists($key, $data)) {
// 		if($key == 412)
//         $data1[$key] = $value;
//     }
// }
$existingKeys = array_map(fn($item) => is_object($item) ? $item->id : (is_array($item) ? $item['id'] : null), $data);

$existingKeys = array_unique($existingKeys);
foreach ($rscIds as $key => $val) {
    if (!in_array($key, $existingKeys)) {
        $data[] = $val; 
    }
}


$ressources = $data;

$ressourcesComm = $dataComm;
$ressourcesProj = $dataProj;




$ressourcesAbs = $dataAbs;
$ressourcesProjAbs = $dataProjAbs;

foreach ([$ressources, $ressourcesComm, $ressourcesProj, $ressourcesAbs, $ressourcesProjAbs] as &$resSet) {
    $sortResources($resSet);
}
unset($resSet);

// Traitement des ressources
function processRessources(&$ressources, $employee) {
    foreach ($ressources as $res) {
        if (!isset($res->id)) {
            $res->name_html = "ID manquant";
            continue;
        }
        if ($employee->fetch($res->id)) {
 
            $gender = $employee->gender ?: 'man';
            $logotouse = '<img class="photouserphoto userphoto" alt="" src="/erp/public/theme/common/user_' . $gender . '.png">';
            $tooltipTitle = htmlspecialchars($employee->firstname . ' ' . $employee->lastname, ENT_QUOTES, 'UTF-8');
            $tooltipTitleLogin = htmlspecialchars($employee->login, ENT_QUOTES, 'UTF-8');

            $res->name_html = generateTooltip($tooltipTitle, $logotouse, $tooltipTitle);
            $res->name_login = generateTooltip($tooltipTitle, $logotouse, $tooltipTitleLogin);
            // $res->name_login = htmlspecialchars($employee->getNomUrl(1), ENT_QUOTES, 'UTF-8');

        } else {
            $res->name_html = "Nom non trouvé";
        }
    }
}

foreach ([$ressources, $ressourcesProj, $ressourcesComm, $ressourcesAbs, $ressourcesProjAbs] as &$resSet) {
    processRessources($resSet, $employee);
}
unset($resSet);

function generateTooltip($title, $img, $text) {
    return '<span class="user-info-tooltip" title="' . $title . '">
                <span class="nopadding userimg" style="margin-right: 3px;">' . $img . '</span>
                <span class="nopadding usertext">' . $text . '</span>
            </span>';
}

// Calcul des PDC pour les projets
function calculatePdcProject(&$ressources, $proj) {
    foreach ($ressources as $val) {
        $proj->fetch($val->fk_projet);
        $listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');
        $val->pdc_proj = count($listofproject);
        $val->missing1 = $val->pdc_proj - $val->pdc_etp_cde;
    }
}

foreach ([$ressourcesComm, $ressourcesProj] as &$resSet) {
    calculatePdcProject($resSet, $proj);
}
unset($resSet);

// Gestion des ETP dans les projets
function etpInProjects(&$ressources) {
    $etp_proj = $etp_code = [];
    foreach ($ressources as $val) {
        if (isset($val->missing)) {
            $etp_proj[$val->fk_projet] = intval($val->pdc_etp_proj);
        }
        $etp_code[$val->fk_projet] = intval($val->pdc_etp_cde);
    }
    foreach ($ressources as $val) {
        $val->etp = isset($etp_proj[$val->fk_projet]) && isset($etp_code[$val->fk_projet]) && $etp_proj[$val->fk_projet] > $etp_code[$val->fk_projet];
    }
}

foreach ([$ressourcesProj, $ressourcesAbs, $ressourcesProjAbs] as &$resSet) {
    etpInProjects($resSet);
}

$projetsArray = $rsc->getProjects();
unset($resSet);

// Génération de la réponse JSON
// $arrayData = [
//     'res' => $ressources,
//     'resProj' => $ressourcesProj,
//     'resComm' => $ressourcesComm,
//     'resAbs' => $ressourcesAbs,
//     'resProjAbs' => $ressourcesProjAbs
// ];

// echo json_encode($arrayData);

//end test

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

$comArray = $rsc->getContactsAndOrders($arr_user, $date_start, $date_end, 'noproject');
$propArray = $rsc->getContactsAndPropals($arr_user, $date_start, $date_end);


$data = array_merge($comArray, $propArray);


$ressources = array_merge($data, $rscIds);




$commarr = array_merge($comArray, $rscIds);


foreach($ressources as $ressource) {
	$dates[] = strtotime($ressource->date_end);	
}
$date_e = max($dates);


//If no date filter
if (empty($date_start)) {
	// $date_start = dol_get_first_day(dol_print_date($now, 'Y'));
	$date_start = dol_time_plus_duree($now, -1, 'y');
}



if (empty($date_end)) {
	$date_end = dol_get_last_day(dol_print_date($date_e, 'Y'));
}


$resps = $rsc->getRespOfProject();

// $agences = $rsc->getAgences();

$projs = $rsc->select_multi_projects();
$orders = $rsc->select_multi_orders();
$devs = $rsc->select_multi_propals();


// $usergroup = new UserGroup($db);
// foreach($arr_group as $val) {
// 	$usergroup->fetch($val);
// }

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
$title = $langs->trans("Collaborateurs : devis & cde");
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

$linktocreatetask = dolGetButtonTitle($langs->trans('AddTask'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id.'&action=create'.$param.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id), '', $linktocreatetaskUserRight, $linktocreatetaskParam);
// if($user->id == 412) {
	$linktotasks = dolGetButtonTitle($langs->trans('ViewList TEST'), '', 'fa fa-bars paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview.test.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition'));
// }

$linktotasks .= dolGetButtonTitle($langs->trans('Projet : Collaborateurs - Absence'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_plan_holiday.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Cde : Collaborateur - Projet'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_by_commande.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Projet : Cde/devis - collaborateur'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_by_project.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Collaborateurs : projets/devis/cde'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttviewglobal.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Collaborateurs : devis & cde'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Cumul ETP'), '', 'fa fa-chart-line paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/workloadindex.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));

//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'generic', 0, '', '', 0, 1);
print load_fiche_titre($title, $linktotasks.' &nbsp; '.$linktocreatetask, 'projecttask');



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

$managers = $rsc->getManager();


if (count($managers) > 0) {
	foreach ($managers as $key => $value) {
		$charge->fetch($key);
		$manager[$key] = $charge->firstname.' '.$charge->lastname;
	}
}

$arrgroup = $rsc->listGroups();
asort($arrgroup);

// if($action == 'refproj') {
//     $refproj = 'checked';
//     $date_startv = null; 
//     $date_endv = null;
//     $date_startnv = null;
//     $date_endnv = null;
// }elseif($action == 'responsible') {
//     $responsible = 'checked';
//     $date_startv = null; 
//     $date_endv = null;
//     $date_starth = null; 
//     $date_endh = null;
// }elseif(($action == 'contributor')) {
//     $contributor = 'checked';
//     $date_startnv = null;
//     $date_endnv = null;
//     $date_starth = null;
//     $date_endh = null;
// }
foreach ($ressources as $resource) {
    if(isset($resource->date_start)) {
		$dateStartList[] = $resource->date_start;
	}
	if(isset($resource->date_end)) {
		$dateEndList[] = $resource->date_end;
	}
}

// La date de début la plus ancienne
$earliestDateStart = min($dateStartList);
$latestDateStart = min($dateEndList);

$earliestYearStart = dol_print_date($earliestDateStart, 'Y');
$latestYearEnd = dol_print_date($latestDateStart, 'Y');
$selectedJobs = GETPOST('jobs');
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

/* 
.switch {
    position: absolute;
    /* top: 50%; */
    /* left: 50%;  */
    width: 45px;
    height: 35px;
    text-align: center;
    /* margin: -30px 0 0 -75px; */
    /* background: #00bc9c; */
    transition: all 0.2s ease;
    border-radius: 25px;
  }
  .switch span {
    position: absolute;
    width: 20px;
    height: 4px;
    top: 50%;
    left: 50%;
    margin: -2px 0px 0px -4px;
    /* background: #fff; */
    display: block;
    transform: rotate(-45deg);
    transition: all 0.2s ease;
  }
  .switch span:after {
    content: "";
    display: block;
    position: absolute;
    width: 4px;
    height: 12px;
    margin-top: -8px;
    /* background: #fff; */
    transition: all 0.2s ease;
  }
  input[type=radio] {
    display: none;
  }
  .switch label {
    cursor: pointer;
    color: rgba(0,0,0,0.2);
    /* width: 60px;
    line-height: 50px; */
    transition: all 0.2s ease;
  }
  /* label[for=and] {position: absolute;left: 0px;height: 20px;}
  label[for=or] {position: absolute;right: 0px;} */
  label[for=userand] {position: absolute;left: 0px;height: 20px;}
  label[for=useror] {position: absolute;right: 0px;}
  label[for=groupand] {position: absolute;left: 0px;height: 20px;}
  label[for=groupor] {position: absolute;right: 0px;}
  label[for=skilland] {position: absolute;left: 0px;height: 20px;}
  label[for=skillor] {position: absolute;right: 0px;}
  label[for=projand] {position: absolute;left: 0px;height: 20px;}
  label[for=projor] {position: absolute;right: 0px;}
  label[for=orderand] {position: absolute;left: 0px;height: 20px;}
  label[for=orderor] {position: absolute;right: 0px;}
  label[for=propaland] {position: absolute;left: 0px;height: 20px;}
  label[for=propalor] {position: absolute;right: 0px;}
  label[for=respand] {position: absolute;left: 0px;height: 20px;}
  label[for=respor] {position: absolute;right: 0px;}
  label[for=antenneand] {position: absolute;left: 0px;height: 20px;}
  label[for=antenneor] {position: absolute;right: 0px;}
  label[for=agenceand] {position: absolute;left: 0px;height: 20px;}
  label[for=agenceor] {position: absolute;right: 0px;}

  #or:checked ~ .switch span {margin-left: -8px;}
  #or:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #useror:checked ~ .switch span {margin-left: -8px;}
  #useror:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #groupor:checked ~ .switch span {margin-left: -8px;}
  #groupor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #skillor:checked ~ .switch span {margin-left: -8px;}
  #skillor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #projor:checked ~ .switch span {margin-left: -8px;}
  #projor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #orderor:checked ~ .switch span {margin-left: -8px;}
  #orderor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #propalor:checked ~ .switch span {margin-left: -8px;}
  #propalor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #respor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #respor:checked ~ .switch span {margin-left: -8px;}
  #antenneor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}
  #antenneor:checked ~ .switch span {margin-left: -8px;}
  #agenceor:checked ~ .switch span {margin-left: -8px;}
  #agenceor:checked ~ .switch span:after {height: 20px;margin-top: -8px;margin-left: 8px;}


  #and:checked ~ .switch label[for=and] {color: #25a580;}
  #or:checked ~ .switch label[for=or] {color: #25a580;}
  #userand:checked ~ .switch label[for=userand] {color: #25a580;}
  #useror:checked ~ .switch label[for=useror] {color: #25a580;}
  #groupand:checked ~ .switch label[for=groupand] {color: #25a580;}
  #groupor:checked ~ .switch label[for=groupor] {color: #25a580 !important;}
  #skilland:checked ~ .switch label[for=skilland] {color: #25a580 !important;}
  #skillor:checked ~ .switch label[for=skillor] {color: #25a580;}
  #projand:checked ~ .switch label[for=projand] {color: #25a580 !important;}
  #projor:checked ~ .switch label[for=projor] {color: #25a580;}
  #orderand:checked ~ .switch label[for=orderand] {color: #25a580 !important;}
  #orderor:checked ~ .switch label[for=orderor] {color: #25a580;}
  #propaland:checked ~ .switch label[for=propaland] {color: #25a580 !important;}
  #propalor:checked ~ .switch label[for=propalor] {color: #25a580;}
  #respand:checked ~ .switch label[for=respand] {color: #25a580 !important;}
  #respor:checked ~ .switch label[for=respor] {color: #25a580;}
  #antenneand:checked ~ .switch label[for=antenneand] {color: #25a580 !important;}
  #antenneor:checked ~ .switch label[for=antenneor] {color: #25a580;}
  #agenceand:checked ~ .switch label[for=agenceand] {color: #25a580;}
  #agenceor:checked ~ .switch label[for=agenceor] {color: #25a580;}

  .tog-r{
	padding-bottom:15px;
  } */

  /* .gmainright {
	display: flex;
	justify-content: center;
	align-items: center;
}
div#GanttChartDIVglisthead, div#GanttChartDIVgcharthead {
	width: fit-content!important;
} */
 
</style>

<script>
	document.addEventListener("DOMContentLoaded", function () {
		// Sélection du conteneur de défilement
		const ganttContainer = document.getElementById("GanttChartDIVgcharthead");
		const table = document.getElementById("GanttChartDIVchartTableh");

		// Largeur d'une cellule correspondant à un mois (ajustez selon votre table)
		const monthWidth = 24; // En pixels, adapté à votre structure HTML

		// Dates de début et de fin du projet (injectées depuis PHP)
		const startYear = <?php echo json_encode($earliestYearStart); ?>;
		const endYear = <?php echo json_encode($latestYearEnd); ?>;

		// Vérification des dates
		if (!startYear || !endYear || startYear >= endYear) {
			console.error("Les dates de début et de fin sont incorrectes :", startYear, endYear);
			return;
		}

		// Calcul de l'année et du mois actuels
		const currentDate = new Date();
		const currentYear = currentDate.getFullYear();
		const currentMonth = currentDate.getMonth(); // 0 = Janvier, 11 = Décembre

		// Calculer l'offset en mois en fonction des limites [startYear, endYear]
		let offset = 0;

		if (currentYear < startYear) {
			// Si la date actuelle est avant le début du projet
			offset = 0;
		} else if (currentYear > endYear) {
			// Si la date actuelle est après la fin du projet
			offset = (endYear - startYear + 1) * 12 - 1; // Position ultime
		} else {
			// Si la date actuelle est entre les deux
			offset = (currentYear - startYear) * 12 + currentMonth;
		}

		// Calculer la position de défilement en pixels
		const scrollPosition = offset * monthWidth;

		// Appliquer le défilement horizontal
		ganttContainer.scrollLeft = scrollPosition;

		console.log("Position défilement calculée :", scrollPosition);
	});
</script>

<?php
$filter = '';

print '<div class="fichecenter">';
// if($user->id == 412) {
	
// 	include DOL_DOCUMENT_ROOT.'/custom/workload/views/workload.filter.php';
	


// // Initialisation de la variable globale des données
// // let data = [];

// // Fonction pour récupérer les données via AJAX
// function fetchData() {
//     $.ajax({
//         url: '/custom/workload/ajax/workload_data.php',
//         type: 'GET',
//         dataType: 'json',
//         cache: false,
//         success: function (response) {
//             console.log("Données reçues :", response);
//             if (response && Array.isArray(response)) {
//                 data = response;
//                 initializeFilters();
//                 displayData(data);
//             } else {
//                 console.warn("Réponse non valide.");
//             }
//         },
//         error: function (xhr, status, error) {
//             console.error("Erreur lors de l'appel AJAX :", error);
//             console.log("Statut :", status);
//             console.log("Réponse :", xhr.responseText);
//         }
//     });
// }

// // Fonction pour initialiser les options des filtres
// function initializeFilters() {
//     populateFilterOptions("orderFilter", "element_id", "Element ID");
//     populateFilterOptions("userFilter", "id", "User ID");
// }

// // Fonction générique pour remplir les options d'un filtre
// function populateFilterOptions(filterId, key, labelPrefix) {
//     const filterSelect = document.getElementById(filterId);
//     if (!filterSelect) {
//         console.error(`L'élément #${filterId} est introuvable.`);
//         return;
//     }

//     const uniqueValues = [...new Set(data.map(item => item[key]))];
//     filterSelect.innerHTML = '<option value="">-- Sélectionner --</option>';

//     uniqueValues.forEach(value => {
//         const option = document.createElement("option");
//         option.value = value;
//         option.textContent = `${labelPrefix}: ${value}`;
//         filterSelect.appendChild(option);
//     });
// }

// // Fonction pour afficher les données dans la table
// function displayData(filteredData) {
//     const tbody = document.querySelector("#dataTable tbody");
//     tbody.innerHTML = ""; // Effacer les données précédentes

//     filteredData.forEach(item => {
//         const row = document.createElement("tr");
//         row.innerHTML = `
//             <td>${item.id}</td>
//             <td>${item.agence}</td>
//             <td>${item.ref}</td>
//             <td>${item.date_start}</td>
//             <td>${item.date_end}</td>
//             <td>${item.element_id}</td>
//         `;
//         tbody.appendChild(row);
//     });
// }

// // Fonction pour filtrer les données en fonction d'un filtre donné
// function filterData(filterId, key) {
//     const filterValue = document.getElementById(filterId).value;
//     const filteredData = data.filter(item => {
//         return filterValue === "" || item[key] === filterValue;
//     });
//     displayData(filteredData);
// }

// // Fonction pour gérer les mises à jour avancées via AJAX
// function updateResults() {
//     // Récupération des valeurs sélectionnées dans les filtres
//     const selectedUsers = Array.from(document.getElementById('userFilter').selectedOptions).map(option => option.value);
//     const selectedJobs = Array.from(document.getElementById('jobFilter').selectedOptions).map(option => option.value);
//     const selectedSkills = Array.from(document.getElementById('skillFilter').selectedOptions).map(option => option.value);

//     console.log("Filtrage avec :", { selectedUsers, selectedJobs, selectedSkills });

//     // Appel AJAX pour envoyer les données de filtre
//     $.ajax({
//         url: '/custom/workload/ganttview.php',
//         type: 'POST',
//         data: {
//             employees: selectedUsers,
//             jobs: selectedJobs,
//             skills: selectedSkills
//         },
//         success: function (response) {
//             // console.log('Réponse reçue :', response);

//             // Utilisation d'un conteneur temporaire pour manipuler la réponse HTML
//             const tempDiv = $('<div>').html(response);
//             const filteredContent = tempDiv.find('#id-right').html();
				
//             // Vérifier si le contenu filtré existe avant de le mettre à jour
//             if (filteredContent) {
//                 $('#id-right').html(filteredContent);
//                 console.log("Mise à jour de #id-right avec le contenu filtré.");
//             } else {
//                 console.warn("Aucun contenu filtré trouvé dans la réponse.");
//             }
//         },
//         error: function (xhr, status, error) {
//             console.error('Une erreur est survenue lors de l’appel AJAX :', error);
//             console.log("Statut :", status);
//             console.log("Réponse brute :", xhr.responseText);
//         }
//     });
// }

// // Ajout d'écouteurs sur les filtres
// function setupEventListeners() {
//     // document.getElementById("orderFilter").addEventListener("change", () => filterData("orderFilter", "element_id"));
//     // document.getElementById("userFilter").addEventListener("change", () => filterData("userFilter", "id"));
//     document.getElementById("userFilter").addEventListener("change", updateResults);
//     document.getElementById("jobFilter").addEventListener("change", updateResults);
//     document.getElementById("skillFilter").addEventListener("change", updateResults);
// }

// // Initialisation des fonctionnalités au chargement de la page
// document.addEventListener("DOMContentLoaded", function () {
//     fetchData(); // Récupérer les données via AJAX
//     setupEventListeners(); // Configurer les écouteurs
// });
// </script>
// <?php

print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre" width="40%"><td class="liste_titre">'.$langs->trans("Filter").'</td>';
// print '<td class="liste_titre" width="20%"></td>';
// // date
// <td class="left">'.$langs->trans("Date").'</td>
print '<td class="liste_titre left" width="30%">';
// print $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0).'  '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
print '</td>';

print '<td class="liste_titre right" width="25%">';
		print '<div class="smile-rating-container">';
		print ' <div class="smile-rating-toggle-container">';
		print '<div class="submit-rating" style="display:flex; flex-direction: row-reverse;">';
	
		if($occupied == 'off' || $occupied == '') {
			print '<a style="text-decoration: none;display: contents;" href="'.$_SERVER['PHP_SELF'].'?case=on&token='.newToken().'&amp;action=filter">';
			print '<span class="fas fa-toggle-off" for="meh" style="color:#d0cccd;"></span><label class="rating-label rating-label-meh" style="font-size:14px;color:black;padding-left: 15px;padding-right: 15px;">Dispoinibilité</label>'; 
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
	print '</td>';
	print '<td class="liste_titre right" width="5%"><button title="Réinitialiser" style="background:transparent;" role="button" class=" btn-message-a tosend btn-tosend msg2" data-uia="nmhp-card-cta+hero_fuji" type="submit" name="remove" value="refresh"><div aria-hidden="true" class="default-ltr-cache-17uj5h e1ax5wel0"><span class="fas fa-sync-alt" style="font-size:1em;color:#8a8a8a;"></span></div></div></div><div class="default-ltr-cache-vgp0nn e9eyrqp3"></div></div></td>';

print '</tr>';
// print '<tr class="liste_titre">';
// // print '<td class="left">'.$langs->trans("Test").'</td>';
// print '<td class="liste_titre" colspan="4">';
// print '<div class="divsearchfieldfilter">'.$langs->trans("Chaine d'opérations - order des parenthèses à respecter").join(', ', '( )').'</div>';
// print '<input  type="text" id="name" name="name"  size="100" placeholder=" = "/>';
// print '</td>';
// print '</tr>';
print '</table>';
print '<div class="fichethirdleft">';
print '<table>';

// user
print '<tr><td class="left">'.$langs->trans("Employees").'</td><td class="left">';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_user',  $ids,  $arr_user, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td>';
// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="userand" name = "ur" value = "userand" '.($ur == 'userand'?'checked':'').'>';
// print '<input type="radio" id="useror" name = "ur" value = "useror" '.($ur == 'useror'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="userand">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="useror">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';
// group
print '<tr><td>'.$langs->trans("Groups").'</td><td>';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_group', $arrgroup, $arr_group, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td>';
// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="groupand" name = "gr" value = "groupand" '.($gr == 'groupand'?'checked':'').'>';
// print '<input type="radio" id="groupor" name = "gr" value = "groupor" '.($gr == 'groupor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="groupand">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="groupor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';
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
// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="skilland" name = "sk" value = "skilland" '.($sk == 'skilland'?'checked':'').'>';
// print '<input type="radio" id="skillor" name = "sk" value = "skillor" '.($sk == 'skillor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="skilland">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="skillor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';

print '</table>';

print '</div><div class="fichetwothirdright">';

print '<table>';
// user
print '<tr><td class="left">'.$langs->trans("Res. projets").'</td><td class="left">';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_resp',  $resps,  $arr_resp, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td>';
// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="respand" name = "rp" value = "respand" '.($rp == 'respand'?'checked':'').'>';
// print '<input type="radio" id="respor" name = "rp" value = "respor" '.($rp == 'respor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="respand">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="respor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';
// user
print '<tr>';
print '<td>'.$langs->trans("Res. antenne").'</td><td>';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_manager', $manager, $arr_manager, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td>';

// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="antenneand" name = "an" value = "antenneand" '.($an == 'antenneand'?'checked':'').'>';
// print '<input type="radio" id="antenneor" name = "an" value = "antenneor" '.($an == 'antenneor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="antenneand">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="antenneor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';

// Compétences
print '<tr>';
print '<td>'.$langs->trans("Agence").'</td><td>';
print img_picto('', 'group', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_agence', $agences, $arr_agence, '', '', '', '', 'widthcentpercentminusx maxwidth300', '','', 'Tous');
print '</td>';

// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="agenceand" name = "ag" value = "agenceand" '.($ag == 'agenceand'?'checked':'').'>';
// print '<input type="radio" id="agenceor" name = "ag" value = "agenceor" '.($ag == 'agenceor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="agenceand">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="agenceor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';

print '</table>';


print '</div>';

print '</div><div class="fichetwothirdright">';
print '<table>';

// user
print '<tr><td class="left">'.$langs->trans("Projects").'</td><td class="left">';
print img_picto('', 'project', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_proj',  $projs,  $arr_proj, '', '', '', '', '200px maxwidth300', '','', 'Tous');
print '</td>';
// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="projand" name = "pr" value = "projand" '.($pr == 'projand'?'checked':'').'>';
// print '<input type="radio" id="projor" name = "pr" value = "projor" '.($pr == 'projor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="projand">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="projor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
// print '</tr>';
// group
print '<tr><td>'.$langs->trans("Orders").'</td><td>';
print img_picto('', 'order', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_order', $orders, $arr_order, '', '', '', '', '200px maxwidth300', '','', 'Tous');
print '</td>';
// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="orderand" name = "cd" value = "orderand" '.($cd == 'orderand'?'checked':'').'>';
// print '<input type="radio" id="orderor" name = "cd" value = "orderor" '.($cd == 'orderor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="orderand">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="orderor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';
// Compétences
print '<tr><td>'.$langs->trans("Devis").'</td><td>';
print img_picto('', 'propal', 'class="pictofixedwidth"');
print $form->multiselectarray('arr_propal', $devs, $arr_propal, '', '', '', '', '200px maxwidth300', '','', 'Tous');
print '</td>';
// print '<td>';
// print '<div class="tog-r">';
// print '<input type="radio" id="propaland" name = "dv" value = "propaland" '.($dv == 'propaland'?'checked':'').'>';
// print '<input type="radio" id="propalor" name = "dv" value = "propalor" '.($dv == 'propalor'?'checked':'').'>';
// print ' <div class="switch">';
// print ' <label for="propaland">'.$langs->trans('and').'</label>';
// print ' &nbsp; /  &nbsp; ';
// print '<label for="propalor">'.$langs->trans('or').'</label>';
// print ' <label for="del"><i class="fas fa-backspace"></i></label>';
// print ' <span></span>';
// print '</div>';
// print '</div>';
// print '</td>';
print '</tr>';

// print '<tr><td align="center" colspan="2"><input type="submit" class="button small" name="submit" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';


print '</div>';

print '<div class="fichecenter">';
print '<table class="noborder centpercent" style="margin-top: 130px;">';
// print '<tr><td align="center" colspan="4"><input type="submit" class="button small" name="submit" value="'.$langs->trans("Refresh").'"></td></tr>';
print '<td align="center" colspan="4"><button style="background: var(--butactionbg);color: var(--textbutaction);border-radius: 3px;border-collapse: collapse;border: none;" role="button" class="button small" type="submit" name="filter" value="start">'.$langs->trans("Refresh").'</div></td>';
print '</table>';
print '</form>';
// print '<br><br>';
// print '</div>';
print '</div>';

// var_dump($ressources);
$member = new User($db);
// function sortByValue($a, $b) {
// 	return strcmp($a['member_id'], $b['member_id']);
// }
if($user->id == 412){
	
	$ressources1 = $rsc->filterResourcesByIds($ressources, '499', '669');
	// var_dump($ressources);
	
	// var_dump($_POST);
}

if (count($ressources) > 0) {
	// Show Gant diagram from $ressources using JSGantt
	foreach ($ressources as $key => $val) {
		// var_dump($val->id);
		$existkey[] = $val->id;
		$count = array_count_values($existkey);
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

	///check submit and choices of parent data if not assigned to child data <frequent is equal or less than one if it is not assigned > 
	$nofilter = empty(array_filter($arr_filter)) && GETPOST('filter') == "start";
	if(GETPOST('filter') != "start" || $nofilter) {
		foreach($count as $id => $frequency) {
			if($frequency <= 1) {
				$employees[] = $id;
			}
		}
	}

	foreach($count as $id => $freq) {
		if($freq <= 1) {
			$availableusers[] = $id;
		}
	}
		
	$dateformat = $langs->trans("FormatDateShortJQuery"); // Used by include ganttchart.inc.php later
	$datehourformat = $langs->trans("FormatDateShortJQuery").' '.$langs->trans("FormatHourShortJQuery"); // Used by include ganttchart.inc.php later
	$array_contacts = array();
	$members = array();
	$member_dependencies = array();
	$membercursor = 0;
	
	foreach ($ressources as $key => $val) {
		//Pour afficher les perspnnes innocupées. 
		$occupied == 'on' ? $condition = in_array($val->id, $employees) : $condition = $val->element_id != null || in_array($val->id, $availableusers);
		// $occupied ==
		if($condition) { 
		// Users array are sorted by "user, prder, date"
			$member->fetch($val->id, '');
			$idparent = ($val->s ? $val->id : '-'.$val->id); // If start with -, id is a project id
			
			$members[$membercursor]['member_id'] = $val->element_id;
			$members[$membercursor]['member_idref'] = $val->idref;
		
			$members[$membercursor]['member_alternate_id'] = ($membercursor + 1); // An id that has same order than position (required by ganttchart)
			$members[$membercursor]['member_member_id'] = $val->id;
			$members[$membercursor]['member_parent'] = $idparent;

			$members[$membercursor]['member_is_group'] = 0;
		
			if($val->idref === "CO") {
				$members[$membercursor]['member_css'] = 'gtaskblue';
			}elseif($val->idref === "PR"){
				$members[$membercursor]['member_css'] = 'gtaskgreen';
			}
			
			$members[$membercursor]['member_milestone'] = '0';

			//right to access by link on ref
			$user->rights->commande->lire
			? $refcomm = '<a href="'.DOL_URL_ROOT.'/commande/contact.php?id='.$val->element_id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$val->ref.'</a>'
			: $refcomm = $val->ref;
			
			$user->rights->supplier_proposal->lire
			? $refpropal = '<a href="'.DOL_URL_ROOT.'/supplier_proposal/contact.php?id='.$val->element_id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$val->ref.'</a>'
			: $refpropal = $val->ref;
			if($val->idref === "CO") {
				$members[$membercursor]['member_name'] = $refcomm.' - '.'Aquis';
			}elseif($val->idref === "PR"){
				$members[$membercursor]['member_name'] = $refpropal.' - '.'Potentiel';
			}
			
			$members[$membercursor]['member_start_date'] = $val->date_start;
			$members[$membercursor]['member_end_date'] = $val->date_end;
			$members[$membercursor]['member_color'] = 'b4d1ea';
	

			/* For JSGanttImproved */
			$members[$membercursor]['member_resources'] = $s;

			if($val->idref == "CO") {
				$members[$membercursor]['member_resources'] = '<span title="Les contacts qui sont prévus sur la commande">'.$val->pdc_etp_cde.'</span>';
				$label = 'la commande';
			}
			if($val->idref === "PR"){
				$label = 'le devis';
				$members[$membercursor]['member_resources'] = '<span title="Les contacts qui sont prévus sur le devis">'.$val->pdc_etp_devis.'</span>';
			}

			$diff = $val->pdc_etp_cde < $val->pdc_etp_proj;

			
			//print "xxx".$val->id.$tasks[$taskcursor]['task_resources'];
			// $members[$membercursor]['note'] = $task->note_public;
			$membercursor++;
		}
	}
	// usort($members, "sortByValue");

	// Search parent to set task_parent_alternate_id (requird by ganttchart)
	foreach ($members as $tmpkey => $tmptask) {
		foreach ($members as $tmptask2) {
			 if ($tmptask2['member_id'] == $tmptask['member_parent']) {
				// $tasks[$tmpkey]['task_parent_alternate_id'] = $tmptask2['task_alternate_id'];
				$members[$tmpkey]['member_parent_alternate_id'] = $tmptask2['member_alternate_id'];
				 break;
			 }
		}
		 if (empty($members[$tmpkey]['member_parent_alternate_id'])) {
			$members[$tmpkey]['member_parent_alternate_id'] = $members[$tmpkey]['member_parent'];
		 }
		
	}

	print "\n";
	
	
	

	// usort($myArray, function($a, $b) {
	// 	return $a['order'] <=> $b['order'];
	// });

	if (!empty($conf->use_javascript_ajax)) {
		//var_dump($_SESSION);

		// How the date for data are formated (format used bu jsgantt)
		$dateformatinput = 'yyyy-mm-dd';
		// How the date for data are formated (format used by dol_print_date)
		$dateformatinput2 = 'standard';
		//var_dump($dateformatinput);
		//var_dump($dateformatinput2);

		$moreforfilter = '<div class="liste_titre liste_titre_bydiv centpercent">';

		$moreforfilter .= '<div class="divsearchfield">';
		// $moreforfilter .= $langs->trans("TasksAssignedTo").': ';
		// $moreforfilter .= $form->select_dolusers($val->id > 0 ? $val->id : '', 'search_user_id', 1);
		$moreforfilter .= '&nbsp;';
		$moreforfilter .= '</div>';

		$moreforfilter .= '</div>';

		print $moreforfilter;

		print '<div class="div-table-responsive">';

		print '<div id="tabs" class="gantt" style="width: 80vw;">'."\n";
		include_once DOL_DOCUMENT_ROOT.'/custom/workload/ganttchart.inc.php';
		// var_dump($membertmp);
		
		print '</div>'."\n";
// usort($membertmp, 'sortByValue');
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


