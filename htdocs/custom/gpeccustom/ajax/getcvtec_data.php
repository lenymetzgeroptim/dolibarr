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
// restrictedArea($user, 'gpeccustom', 'cvtec', 'read');
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('gpeccustom', 'cvtec', 'read');
	$permissiontoadd = $user->hasRight('gpeccustom', 'cvtec', 'write');
	$permissiontodelete = $user->hasRight('gpeccustom', 'cvtec', 'delete');
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
if (!isModEnabled("gpeccustom")) {
	accessforbidden('Module gpeccustom not enabled');
}
if (!$permissiontoread) accessforbidden();


// // Initialize technical objects
// $object = new CVTec($db);

/**
 * actions 
 */

/*
 * View
 */

dol_syslog("Call ajax gpeccustom/ajax/getcvtec_data.php");
// print '<!DOCTYPE html>';
top_httphead('application/json');

global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT t.rowid, t.fk_user, t.fk_job, j.label as job_label, t.description, DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,";
		// $sql .= " AVG(det.rankorder) as avrgnote,ev.date_eval,";
		$sql .= " det.fk_skill as skillid, sd.description as skill_level, sd.rankorder,s.label as skill_label,j.date_creation";
		$sql .= " FROM llx_hrm_job_user as t";
		// $sql .= " LEFT JOIN llx_user as u on t.fk_user = u.rowid";
		$sql .= " LEFT JOIN llx_hrm_evaluation as ev on t.fk_job = ev.fk_job and t.fk_user = ev.fk_user";
		$sql .= " LEFT JOIN llx_hrm_job as j on j.rowid = t.fk_job";
		$sql .= " LEFT JOIN llx_hrm_evaluationdet as det on det.fk_evaluation = ev.rowid";
		$sql .= " LEFT JOIN llx_hrm_skilldet as sd on det.fk_skill = sd.fk_skill";
		$sql .= " LEFT JOIN llx_hrm_skill as s on s.rowid = sd.fk_skill";
		$sql .= " WHERE 1 = 1";

		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					// if(isset($obj->fk_user)) {
						$jobskills[] = $obj;
					// }
				}
				$i++;
			}
			$db->free($resql);
	
		} else {
			// dol_print_error($db);
		}
// $arrayresult = $object->getJobSkills(array_filter(array('')), array_filter(array('')), 'job_user_skills');
// ....
// var_dump($jobskills);
$db->close();

print json_encode($jobskills);
