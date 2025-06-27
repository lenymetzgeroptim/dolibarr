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
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('workload', 'box_graph_plan_charge', 'afficher');
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1;
	$permissiontodelete = 1;
}

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

require_once DOL_DOCUMENT_ROOT.'/custom/workload/class/charge.class.php';

/*
 * View
 */

dol_syslog("Call ajax workload/ajax/filter_data.php");

top_httphead('application/json');
global $db;
$rcs = new Charge($db);
$arrayUsers = array();
$listUsers = $rcs->getUsers();
$listJobs = $rcs->getSkillsAndUsers();
$listGroups = $rcs->getGroups();
$listRespProjects = $rcs->getRespOfProjects();
$listProjects = $rcs->getProjects();
$listOrders = $rcs->getOrders();
$listSkills = $rcs->getSkills();
$listAgency = $rcs->getAgencies();
$listPropal = $rcs->getPropals();
$listTrainingType = $rcs->getUsersInCurrentTraining();
$listAbsType = $rcs->getAbsentUsers();


 $arrayUsers = array_merge($listUsers);
 $arrayJobs = array_merge($listJobs);
 $arrayGroups = array_merge($listGroups);
 $arrayRespProj = array_merge($listRespProjects);
 $arrayProjects = array_merge($listProjects);
 $arrayOrders = array_merge($listOrders);
 $arraySkills = array_merge($listSkills);
 $arrayAgencies = array_merge($listAgency);
 $arrayPropals = array_merge($listPropal);
 $arrayAbsType = array_merge($listAbsType, $listTrainingType);

 
 
 $arrayData = [
     'dataUsers' => $arrayUsers,
	 'dataJobs' => $arrayJobs,
	 'dataGroups' => $arrayGroups,
	 'dataRespProj' => $arrayRespProj,
	 'dataProjects' => $arrayProjects,
	 'dataOrders' => $arrayOrders,
	 'dataSkills' => $arraySkills,
	 'dataAgencies' => $arrayAgencies,
	 'dataPropals' => $arrayPropals,
	 'dataAbsType' =>  $arrayAbsType
 ];
 
 // header('Content-Type: application/json');
 // print json_encode($arrayData);

print json_encode($arrayData);
$db->close();