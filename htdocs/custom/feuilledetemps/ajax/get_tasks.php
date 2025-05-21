<?php
/* Copyright (C) 2022       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) ---Replace with your own copyright and developer email---
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
 *       \file       htdocs/mymodule/ajax/get_tasks.php
 *       \brief      File to return Ajax response on get_tasks list request
 */

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
$res = 0;
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

$task_id = GETPOSTINT('task_id');
$search = GETPOST('search');
$fuserid = GETPOSTINT('fuserid'); 

// Déclaration des objets
if ($fuserid > 0) {
    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
    $fuser = new User($db);
    $fuser->fetch($fuserid);
} else {
    $fuser = $user; // fallback sur utilisateur courant si pas de cible
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// @phan-suppress-next-line PhanUndeclaredClass
// $object = new MyObject($db);

// Security check
if (!$user->hasRight('feuilledetemps', 'feuilledetemps', 'read')) {
	accessforbidden();
}

/*
 * View
 */

dol_syslog("Call ajax feuilledetemps/ajax/get_tasks.php");

top_httphead('application/json');

$search = $db->escape($search);

// $projectstatic = new Project($db);
//  $projectsListId = $projectstatic->getProjectsAuthorizedForUser($fuser, 0, 1);

$sql = "SELECT DISTINCT t.rowid as tid, t.ref as tref, t.label as tlabel, t.progress,";
$sql .= " p.rowid as pid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public, p.usage_task";
$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON t.fk_projet = p.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec ON ec.element_id = t.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON ec.fk_c_type_contact = tc.rowid";
$sql .= " WHERE p.entity IN (" . getEntity('project') . ")";
// if ($projectsListId) {
// 	$sql .= " AND p.rowid IN (" . $db->sanitize($projectsListId) . ")";
// }
$sql .= " AND tc.element = 'project_task'";
$sql .= " AND ( p.public = 1";
$sql .= " OR (tc.code IN ('TASKCONTRIBUTOR') AND ec.fk_socpeople = ".((int) $user->id).")";
$sql .= " )";
if (empty($task_id)) {
	$sql .= " AND p.fk_statut = 1";
}
if (!empty($task_id)) {
    $sql .= " AND t.rowid = ".(int)$task_id;
}
elseif (!empty($search)) {
    $sql .= " AND t.ref LIKE '%".$search."%'";
}
$sql .= " ORDER BY p.ref, t.ref ASC";

error_log("sql : " . print_r($sql, true));

$resql = $db->query($sql);
$tasks = [];

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $tasks[] = ['id' => $obj->tid, 'ref' => $obj->tref];
    }
}

echo json_encode($tasks);
$db->close();