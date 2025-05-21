<?php
/* Copyright (C) 2022 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/gpeccustom/ajax/getcvtec_data.php
 *       \brief      File to return Ajax response on product list request
 */

//  require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/cvtec.class.php';

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

// Load Dolibarr environment
// require '../../main.inc.php';

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

$mode = GETPOST('mode', 'aZ09');

// Security check
// restrictedArea($user, 'workload', 0, 'charge');
$permissiontoread = $user->hasRight('workload', 'box_graph_plan_charge', 'afficher');

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) accessforbidden();
//$socid = 0; if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, 0, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("workload")) {
	accessforbidden('Module workload not enabled');
}
if (!$permissiontoread) accessforbidden();


/*
 * View
 */

dol_syslog("Call ajax workload/ajax/workload_data.php");

top_httphead('application/json');

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/workload/class/charge.class.php';


$rsc = new Charge($db);
$employee = new User($db);
$proj = new Project($db);

$rscIds = $rsc->getUsers();
$absArray = $rsc->getAbsentUsers();

// Récupération des données
$fetchData = function($type, $str = null) use ($rsc, $arr_user, $date_start, $date_end) {
    return $rsc->getContactsAndOrders($arr_user, $date_start, $date_end, $type, $str);
};


$comArray = $fetchData('noproject');
$comArrayProj = $fetchData('project');
$comArrayProjAbs = $rsc->getContactsAndProjAbs();    
$comArrayProj1 = $fetchData('project');
$commArray = $fetchData('order');
$propArray = $rsc->getContactsAndPropals($arr_user, $date_start, $date_end);

$genericData = array_merge($comArray, $propArray);
$genericDataProj = array_merge($comArrayProj1, $propArray);

$genericDataComm = $commArray;
$genericDataAbs = array_merge($absArray);
$genericDataProjAbs = $comArrayProjAbs;

//Gestion des droits : par défaut, le chef de projet ne peut visualiser que 
// ses propres projets et ses collaborateurs. 
// Sinon, un droit générique est appliqué sur l\'interface
$chefdeprojet = $rsc->getChefDeProjet();
$currentUserId = $user->id;

$isGenericRight = $user->rights->workload->box_graph_plan_charge->afficher;
$isSpecificRight = $user->rights->workload->box_graph_plan_charge->restreint;
$rightsData = $isGenericRight && !$isSpecificRight;
if ($rightsData) {
    // Si l'utilisateur a le droit générique
    $data = $genericData;
    $dataProj = $genericDataProj;
    $dataComm = $genericDataComm;
    $dataProjAbs = $genericDataProjAbs;
    $dataAbs = $genericDataAbs;

} else {
    // Initialisation des tableaux vides
    $data = [];
    $dataProj = [];
    $dataComm = [];
    $dataProjAbs = [];
    $dataAbs = [];

    $projetsAvecId = array_filter($chefdeprojet, fn($p) => $p->id == $currentUserId);
    foreach ($projetsAvecId as $projet) {
        $projet_id = $projet->projet_id;

        // On fusionne les résultats 
        $data = array_merge($data, array_values(array_filter($genericData, fn($item) => $item->fk_projet == $projet_id)));
        $dataProj = array_merge($dataProj, array_values(array_filter($genericDataProj, fn($item) => $item->fk_projet == $projet_id)));
        $dataComm = array_merge($dataComm, array_values(array_filter($genericDataComm, fn($item) => $item->fk_projet == $projet_id)));
        $dataProjAbs = array_merge($dataProjAbs, array_values(array_filter($genericDataProjAbs, fn($item) => $item->fk_projet == $projet_id)));
        $dataAbs = array_merge($dataAbs, array_values(array_filter($genericDataAbs, fn($item) => $item->fk_projet == $projet_id)));
    }
}

// Fusion des absences avec les projets
foreach ($comArrayProjAbs as &$commItem) {
    // Initialisation des nouveaux champs sous forme de tableaux
    $commItem->periodes = [];
    $commItem->arrayreference = [];

    foreach ($absArray as $absItem) {
        if ($commItem->id === $absItem->id) {
            // Stockage des périodes d'absence dans datesabs
            $commItem->periodes[] = [
                "date_start" => $absItem->date_start,
                "date_end" => $absItem->date_end,
                "status" => $absItem->status,
                "nb_open_day_calculated" => $absItem->nb_open_day_calculated,
                'conge_label' => $absItem->conge_label,
                'fk_type' => $absItem->fk_type
            ];

            // Stockage des références des absences dans arrayreference
            // $commItem->arrayreference[] = $absItem->holidayref;

            // idref valeur unique 
            $commItem->idref = $absItem->idref;
            $commItem->str = $commItem->str;
            // $commItem->domaine = $commItem->domaine;

            // Mise à jour des autres champs
            foreach (["status"] as $field) {
                $commItem->$field = $absItem->$field;
            }
        }
    }
}
unset($commItem); // Libération de la référence

// Tri (to do in new way)
$sortResources = function (&$resources) {
    usort($resources, function($a, $b) {
        // id_element
        if ($a->id_element != $b->id_element) {
            return $a->id_element - $b->id_element;
        }

        // id_element_abs
        if ($a->id_element_abs != $b->id_element_abs) {
            return $a->id_element_abs - $b->id_element_abs;
        }

        //  id
        return $a->id - $b->id;
    });
};


// Ajout des salariés non touvé dans la data concernée (contact projet, abs, ...)
function mergeResourcesWithoutDuplicates(&$targetData, $rscIds) {
    $existingKeys = array_map(function($item) {
        return is_object($item) ? $item->id : (is_array($item) ? $item['id'] : null);
    }, $targetData);

    foreach ($rscIds as $key => $val) {
        if (!in_array($key, $existingKeys)) {
            $targetData[] = $val;
        }
    }
}

// Utilisation pour chaque tableau
mergeResourcesWithoutDuplicates($data, $rscIds);
mergeResourcesWithoutDuplicates($dataProj, $rscIds);
mergeResourcesWithoutDuplicates($dataComm, $rscIds);
mergeResourcesWithoutDuplicates($dataAbs, $rscIds);
// mergeResourcesWithoutDuplicates($dataProjAbs, $rscIds);

// Affectations finales
$ressources       = $data;
$ressourcesProj   = $dataProj;
$ressourcesComm   = $dataComm;
$ressourcesAbs    = $dataAbs;
$ressourcesProjAbs = $dataProjAbs;


foreach ([$ressources, $ressourcesComm, $ressourcesProj, $ressourcesAbs, $ressourcesProjAbs] as &$resSet) {
    $sortResources($resSet);
}
unset($resSet);

// Traitement des ressources
function processRessources(&$ressources, $employee) {
    foreach ($ressources as $res) {
        if (!isset($res->id)) {
            $res->name_html = "Aucune ressource";
            continue;
        }
        if ($employee->fetch($res->id)) {
            $firstLetter = strtoupper(substr($employee->firstname, 0, 1));
            $gender = $employee->gender ?: 'man';
            $logotouse = '<img class="photouserphoto userphoto" alt="" src="/erp/public/theme/common/user_' . $gender . '.png">';
            $tooltipTitle = htmlspecialchars($firstLetter . '. ' . $employee->lastname, ENT_QUOTES, 'UTF-8');
            $tooltipTitleLogin = htmlspecialchars($employee->login, ENT_QUOTES, 'UTF-8');
            $tooltipTitleName = htmlspecialchars($employee->firstname . ' ' . $employee->lastname, ENT_QUOTES, 'UTF-8');

            $res->name_abrvhtml = generateTooltip($tooltipTitleName, $logotouse, $tooltipTitle);
            $res->name_html = generateTooltip($tooltipTitleName, $logotouse, $tooltipTitleName);
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

foreach ([$ressourcesProj, $ressourcesProjAbs] as &$resSet) {
    etpInProjects($resSet);
}
unset($resSet);

// Génération de la réponse JSON
$arrayData = [
    'res' => $ressources,
    'resProj' => $ressourcesProj,
    'resComm' => $ressourcesComm,
    'resProjAbs' => $ressourcesProjAbs,
    'resAbs' => $ressourcesAbs,
];

echo json_encode($arrayData);
$db->close();

