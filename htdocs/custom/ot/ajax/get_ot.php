<?php
/* Copyright (C) 2022 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/ot/ajax/myobject.php
 *       \brief      File to return Ajax response on product list request
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

$mode = GETPOST('mode', 'aZ09');

// Security check
// if (!isModEnabled("ot")) {
// 	accessforbidden();
// }
// if (!$user->rights->ot->read) {
// 	accessforbidden();
// }

// restrictedArea($user, 'ot', 0, 'ot');

/*
 * View
 */

dol_syslog("Call ajax ot/ajax/get_ot.php");

header('Content-Type: application/json');

require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/ot.class.php';

$id = GETPOST('id', 'int');

$arrayresult = array();

if ($id > 0) {
    $sql = "SELECT u.firstname, u.lastname, ctc.libelle, sp.fk_c_type_contact 
            FROM ".MAIN_DB_PREFIX."element_contact as sp 
            JOIN ".MAIN_DB_PREFIX."user as u ON sp.fk_socpeople = u.rowid 
            JOIN ".MAIN_DB_PREFIX."c_type_contact as ctc ON sp.fk_c_type_contact = ctc.rowid 
            WHERE sp.element_id = $id 
            AND sp.statut = 4";

    $resql = $db->query($sql);

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $arrayresult[] = $obj; 
        }
    } else {
        $arrayresult = array('error' => 'SQL query error: '.$db->lasterror());
    }
} else {
    $arrayresult = array('error' => 'Invalid or missing ID parameter');
}

$db->close();

print json_encode($arrayresult);


