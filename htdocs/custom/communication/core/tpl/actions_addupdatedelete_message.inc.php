<?php
/* Copyright (C) 2017-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_addupdatedelete.inc.php
 *  \brief			Code for common actions cancel / add / update / update_extras / delete / deleteline / validate / cancel / reopen / clone
 */


// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined
// $noback may be defined
// $triggermodname may be defined

$hidedetails = isset($hidedetails) ? $hidedetails : '';
$hidedesc = isset($hidedesc) ? $hidedesc : '';
$hideref = isset($hideref) ? $hideref : '';


if (!empty($permissionedit) && empty($permissiontoadd)) {
	$permissiontoadd = $permissionedit; // For backward compatibility
}

if ($cancel) {
	/*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = '';
}


// Action to add record
if ($action == 'add' && !empty($permissiontoadd)) {

	global $db;

	$res = dolibarr_set_const($db, 'COMMUNICATION_MESSAGE_OF_THE_WEEK', $_POST['nouveau_message_de_la_semaine'], 'chaine', 1, 'Message of the day/week/month for the module communication');
	$res = $res && dolibarr_set_const($db, 'COMMUNICATION_MESSAGE_OF_THE_WEEK_VIEW', 0, 'int', 1, 'View on message of the days/week/month');
	

	if($res) {
		$action = '';
		header("Location: ".$backtopage);
		exit;
	}
	else {
		$action = '';
		setEventMessages('Impossible d\'enregistrer le message', null, 'errors');
	}

}
