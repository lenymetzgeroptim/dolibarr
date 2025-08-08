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
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/indicCommGraph.class.php';

// Vue d’ensemble des indicateurs commerciaux - récupération de la data

/*
 * View
 */

dol_syslog("Call ajax feuilledetemps/ajax/get_comm_data.php");

top_httphead('application/json');

$array = array();
$search = $db->escape($search);

$now = dol_now();
$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_start)) {
    $date_start = dol_time_plus_duree($now, -3, 'y');
}

if (empty($date_end)) {
    $date_end = dol_time_plus_duree($now, 3, 'y');
}

$id = GETPOST('id', 'int');

$vals = array();
foreach($values as $key => $arr) {
	foreach($arr as $val) {

		isset($val['total']) ? $val['total'] = $val['total'] :  $val['total'] = 0;
		switch ($key) {
			case 'commande':
				$vals[$val['date']]['commande'] = $val['total']; 
			break;
			case 'facture_fournisseur':
				$vals[$val['date']]['supplier'] = $val['total']; 
			break;
			case 'propal_open':
				$vals[$val['date']]['open'] = $val['total']; 
				// var_dump($val['total']);
			break;
			case 'propal_signed':
				$vals[$val['date']]['signed'] = $val['total']; 
			break;
			case 'facture':
				$vals[$val['date']]['facture'] = $val['total']; 
			break;
			case 'facture_pv':
				$vals[$val['date']]['facture_pv'] = $val['total']; 
			break;
			case 'facture_draft':
				$vals[$val['date']]['facture_d'] = $val['total']; 
			break;
		}
	}
}
        
$chartData = [];
foreach ($vals as $month => $fields) {
    $row = ['date' => $month . '-01']; 

    // Ajoute toutes les colonnes dynamiquement
    foreach ($fields as $key => $value) {
        $row[$key] = (float) $value;
    }

    // $chartData[] = $row;
}

$commGraph = new IndicCommGraph($db);
$chartData = $commGraph->fetchAllChartDataByMonth($id);

$seriesColors = $commGraph->getColorsChartData();

header('Content-Type: application/json');
echo json_encode([
    'data' => $chartData,
    'colors' => $seriesColors,
]);

$db->close();