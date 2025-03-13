<?php
/* Copyright (C) 2005-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2012		Charles-Fr BENKE	<charles.fr@benke.fr>
 * Copyright (C) 2015       Juanjo Menent       <jmenent@2byte.es>
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
 *       \file       htdocs/exports/export.php
 *       \ingroup    export
 *       \brief      Pages of export Wizard
 */

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedexport.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/feuilledetemps.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedHoliday.class.php';


// Load translation files required by the page
$langs->loadlangs(array('admin', 'exports', 'other', 'users', 'companies', 'projects', 'suppliers', 'products', 'bank', 'bills', 'user'));

// Map icons, array duplicated in import.php, was not synchronized, TODO put it somewhere only once
$entitytoicon = array(
	'invoice'      => 'bill',
	'invoice_line' => 'bill',
	'order'        => 'order',
	'order_line'   => 'order',
	'propal'       => 'propal',
	'propal_line'  => 'propal',
	'intervention' => 'intervention',
	'inter_line'   => 'intervention',
	'member'       => 'user',
	'member_type'  => 'group',
	'subscription' => 'payment',
	'payment'      => 'payment',
	'tax'          => 'generic',
	'tax_type'     => 'generic',
	'other'        => 'generic',
	'account'      => 'account',
	'product'      => 'product',
	'virtualproduct'=>'product',
	'subproduct'   => 'product',
	'product_supplier_ref'      => 'product',
	'stock'        => 'stock',
	'warehouse'    => 'stock',
	'batch'        => 'stock',
	'stockbatch'   => 'stock',
	'category'     => 'category',
	'shipment'     => 'sending',
	'shipment_line'=> 'sending',
	'reception'=> 'sending',
	'reception_line'=> 'sending',
	'expensereport'=> 'trip',
	'expensereport_line'=> 'trip',
	'holiday'      => 'holiday',
	'contract_line' => 'contract',
	'translation'  => 'generic',
	'bomm'         => 'bom',
	'bomline'      => 'bom'
);

// Translation code, array duplicated in import.php, was not synchronized, TODO put it somewhere only once
$entitytolang = array(
	'user'         => 'User',
	'company'      => 'Company',
	'contact'      => 'Contact',
	'invoice'      => 'Bill',
	'invoice_line' => 'InvoiceLine',
	'order'        => 'Order',
	'order_line'   => 'OrderLine',
	'propal'       => 'Proposal',
	'propal_line'  => 'ProposalLine',
	'intervention' => 'Intervention',
	'inter_line'   => 'InterLine',
	'member'       => 'Member',
	'member_type'  => 'MemberType',
	'subscription' => 'Subscription',
	'tax'          => 'SocialContribution',
	'tax_type'     => 'DictionarySocialContributions',
	'account'      => 'BankTransactions',
	'payment'      => 'Payment',
	'product'      => 'Product',
	'virtualproduct'  => 'AssociatedProducts',
	'subproduct'      => 'SubProduct',
	'product_supplier_ref'      => 'SupplierPrices',
	'service'      => 'Service',
	'stock'        => 'Stock',
	'movement'	   => 'StockMovement',
	'batch'        => 'Batch',
	'stockbatch'   => 'StockDetailPerBatch',
	'warehouse'    => 'Warehouse',
	'category'     => 'Category',
	'other'        => 'Other',
	'trip'         => 'TripsAndExpenses',
	'shipment'     => 'Shipments',
	'shipment_line'=> 'ShipmentLine',
	'project'      => 'Projects',
	'projecttask'  => 'Tasks',
	'task_time'    => 'TaskTimeSpent',
	'action'       => 'Event',
	'expensereport'=> 'ExpenseReport',
	'expensereport_line'=> 'ExpenseReportLine',
	'holiday'      => 'TitreRequestCP',
	'contract'     => 'Contract',
	'contract_line'=> 'ContractLine',
	'translation'  => 'Translation',
	'bom'          => 'BOM',
	'bomline'      => 'BOMLine'
);

$array_selected = isset($_SESSION["export_selected_fields"]) ? $_SESSION["export_selected_fields"] : array();
$array_filtervalue = isset($_SESSION["export_filtered_fields"]) ? $_SESSION["export_filtered_fields"] : array();
$datatoexport = GETPOST("datatoexport", "aZ09");
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$step = GETPOST("step", "int") ? GETPOST("step", "int") : 1;
$export_name = GETPOST("export_name", "alphanohtml");
$hexa = GETPOST("hexa", "alpha");
$exportmodelid = GETPOST("exportmodelid", "int");
$export_code = GETPOST("export_code", "int");
$field = GETPOST("field", "alpa");

$objexport = new ExtendedExportFDT($db);

$objmodelexport = new ModeleExports($db);
$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new ExtendedFormFile($db);
$holiday = new extendedHoliday($db);
$sqlusedforexport = '';
$typesHoliday = $holiday->getTypesNoCP();
$silae = new Silae($db);
$extrafields = new Extrafields($db);
if (empty($extrafields->attributes[$silae->table_element]['loaded'])) {
	$extrafields->fetch_name_optionals_label($silae->table_element);
}

$head = array();
$upload_dir = $conf->export->dir_temp.'/'.$user->id;

$usefilters = 1;

if(empty($datatoexport) && $export_code >= 0) {
	if($export_code == '0') {
		$datatoexport = 'analytique_pourcentage';
	}
	elseif($export_code == '1') {
		$datatoexport = 'donnees_variables';
	}
	elseif($export_code == '2') {
		$datatoexport = 'absences';
	}
	elseif($export_code == '3') {
		$datatoexport = 'heure_sup';
	}
	elseif($export_code == '4') {
		$datatoexport = 'total_hour_week';
	}
	elseif($export_code == '5') {
		$datatoexport = 'total_hour';
	}
	elseif($export_code == '6') {
		$datatoexport = 'total_holiday';
	}
	else {
		$datatoexport = array(
			0 => "analytique_pourcentage", 
			1 => "donnees_variables", 
			2 => "absences",
			3 => "heure_sup",
			4 => "total_hour_week",
			5 => "total_hour",
			6 => "total_holiday"
		);
	}
}

// Security check
if (!$user->rights->feuilledetemps->feuilledetemps->export) {
	accessforbidden();
}

if($datatoexport == 'analytique_pourcentage') {
	$array_export_fields[0] = array(
		"eu.matricule" => "MATRICULE",
		"u.firstname" => "PRENOM",
		"u.lastname" => "NOM",
		//"tt.element_duration" => "Heure",
		"tt.element_date" => "JOUR",
		"axe" => "AXE",
		"section" => "SECTION",
		"pourcentage" => "POURCENTAGE",
		"fdt.date_debut" => "PERIODE",
		"fdt.status" => "STATUT FEUILLE DE TEMPS",
	);
}
elseif($datatoexport == 'donnees_variables') {
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_export_fields[0] = array(
			"eu.matricule" => "Matricule",
			"u.firstname" => "Prénom",
			"u.lastname" => "Nom",
			//"fdt.date_debut" => "Date Début",
			//"fdt.date_fin" => "Date Fin",
			"s.date" => "Date des éléments de vérification",
			"petit_deplacement1" => "Ind.P.Depl1",
			"petit_deplacement2" => "Ind.P.Depl2",
			"petit_deplacement3" => "Ind.P.Depl3",
			"petit_deplacement4" => "Ind.P.Depl4",
			"repas1" => "Ind.Repas",
			"repas2" => "Ind.Repas.2",
			"heure_route" => "HRoute",
			"kilometres" => "IK",
			"kilometres_rappel" => "IK.Rappel",
			"grand_deplacement1" => "Ind.G.Depl1",
			"grand_deplacement2" => "Ind.G.Depl2",
			"grand_deplacement3" => "Ind.G.Depl3",
			"indemnite_tt" => "Indem.Teletravail",
			"fdt.prime_astreinte" => "P.Astreinte",
			"fdt.prime_exceptionnelle" => "P.Exceptionnelle",
			"fdt.prime_objectif" => "P.Objectifs",
			"fdt.prime_variable" => "P.Variable",
			"fdt.prime_amplitude" => "P.Amplitude",
			"heure_nuit50" => "H_Dim_Nuit_50%",
			"heure_nuit75" => "H_Dim_Nuit_75%",
			"heure_nuit100" => "H_Dim_Nuit_100%",
			"fdt.status" => "Statut feuille de temps",
		);
	}
	else {
		$array_export_fields[0] = array(
			"eu.matricule" => "Matricule",
			"u.firstname" => "Prénom",
			"u.lastname" => "Nom",
			"s.date" => "Date des éléments de vérification",
			"fdt.status" => "Statut feuille de temps",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_export_fields[0]['silae_extrafields.'.$key] = $extrafields->attributes[$silae->table_element]['label'][$key];
		}
	}
}
elseif($datatoexport == 'absences') {
	$array_export_fields[0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"ht.code_silae" => "Code",
		"valeur" => "Valeur",
		"h.date_debut" => "Date début",
		"h.date_fin" => "Date fin",
		"type" => "Type (H ou J)",
		"fdt.status" => "Statut feuille de temps",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_export_fields[0]["hef.statutfdt"] = "Statut Feuille de temps des congés";
	}
}
elseif($datatoexport == 'heure_sup') {
	$array_export_fields[0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"code" => "Code",
		"valeur" => "Valeur",
		"s.date" => "Date début",
		"s.date2" => "Date fin",
		"type" => "Type (H ou J)",
		"fdt.status" => "Statut feuille de temps",
	);
}
elseif($datatoexport == 'total_hour_week') {
	$array_export_fields[0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"eu.antenne" => "Antenne",
		"week" => "Semaine",
		"total_work" => "Heures travaillées",
		"total_holiday" => "Heures en congés",
		"total_hour" => "Total",
	);
}
elseif($datatoexport == 'total_hour') {
	$array_export_fields[0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"eu.antenne" => "Antenne",
		"element_date" => "Date",
		"SUM(element_duration)/3600 as total_hour" => "Total Heure",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "Total HS 0%",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "Total HS 25%",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "Total HS 50%",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "Total HS 50% HT",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "Total Heure Nuit 50%",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "Total Heure Nuit 75%",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "Total Heure Nuit 100%",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "Total Heure Route",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"Total D1 (km)",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"Total D2 (km)",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"Total D3 (km)",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"Total D4 (km)",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"Total GD1 (km)",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"Total GD2 (km)",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"Total GD3 (km)",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"Total GD4 (km)",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"Total Déplacement (km)",
	);
}
elseif($datatoexport == 'total_holiday') {
	$array_export_fields[0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"eu.antenne" => "Antenne",
		"date_debut" => "Date début",
		"date_fin" => "Date fin",
	);
	foreach($typesHoliday as $type) {
		$array_export_fields[0][$type['code']] = $type['label'];
		$array_export_fields[0][$type['code']] .= ($type['in_hour'] ? ' (H)' : ' (J)');
	}
}
else {
	$array_export_fields[0][0] = array(
		"eu.matricule" => "MATRICULE",
		"u.firstname" => "PRENOM",
		"u.lastname" => "NOM",
		//"tt.element_duration" => "Heure",
		"tt.element_date" => "JOUR",
		"axe" => "AXE",
		"section" => "SECTION",
		"pourcentage" => "POURCENTAGE",
		"fdt.date_debut" => "PERIODE",
		"fdt.status" => "STATUT FEUILLE DE TEMPS",
	);
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_export_fields[1][0] = array(
			"eu.matricule" => "Matricule",
			"u.firstname" => "Prénom",
			"u.lastname" => "Nom",
			//"fdt.date_debut" => "Date Début",
			//"fdt.date_fin" => "Date Fin",
			"s.date" => "Date des éléments de vérification",
			"petit_deplacement1" => "Ind.P.Depl1",
			"petit_deplacement2" => "Ind.P.Depl2",
			"petit_deplacement3" => "Ind.P.Depl3",
			"petit_deplacement4" => "Ind.P.Depl4",
			"repas1" => "Ind.Repas",
			"repas2" => "Ind.Repas.2",
			"heure_route" => "HRoute",
			"kilometres" => "IK",
			"kilometres_rappel" => "IK.Rappel",
			"grand_deplacement1" => "Ind.G.Depl1",
			"grand_deplacement2" => "Ind.G.Depl2",
			"grand_deplacement3" => "Ind.G.Depl3",
			"indemnite_tt" => "Indem.Teletravail",
			"fdt.prime_astreinte" => "P.Astreinte",
			"fdt.prime_exceptionnelle" => "P.Exceptionnelle",
			"fdt.prime_objectif" => "P.Objectifs",
			"fdt.prime_variable" => "P.Variable",
			"fdt.prime_amplitude" => "P.Amplitude",
			"heure_nuit50" => "H_Dim_Nuit_50%",
			"heure_nuit75" => "H_Dim_Nuit_75%",
			"heure_nuit100" => "H_Dim_Nuit_100%",
			"fdt.status" => "Statut feuille de temps",
		);
	}
	else {
		$array_export_fields[1][0] = array(
			"eu.matricule" => "Matricule",
			"u.firstname" => "Prénom",
			"u.lastname" => "Nom",
			"s.date" => "Date des éléments de vérification",
			"fdt.status" => "Statut feuille de temps",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_export_fields[1][0]['silae_extrafields.'.$key] = $extrafields->attributes[$silae->table_element]['label'][$key];
		}
	}
	$array_export_fields[2][0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"ht.code_silae" => "Code",
		"valeur" => "Valeur",
		"h.date_debut" => "Date début",
		"h.date_fin" => "Date fin",
		"type" => "Type (H ou J)",
		"fdt.status" => "Statut feuille de temps",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_export_fields[2][0]["hef.statutfdt"] = "Statut Feuille de temps des congés";
	}
	$array_export_fields[3][0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"code" => "Code",
		"valeur" => "Valeur",
		"s.date" => "Date début",
		"s.date2" => "Date fin",
		"type" => "Type (H ou J)",
		"fdt.status" => "Statut feuille de temps",
	);
	$array_export_fields[4][0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"eu.antenne" => "Antenne",
		"week" => "Semaine",
		"total_work" => "Heures travaillées",
		"total_holiday" => "Heures en congés",
		"total_hour" => "Total",
	);
	$array_export_fields[5][0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"eu.antenne" => "Antenne",
		"element_date" => "Date",
		"SUM(element_duration)/3600 as total_hour" => "Total Heure",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "Total HS 0%",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "Total HS 25%",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "Total HS 50%",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "Total HS 50% HT",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "Total Heure Nuit 50%",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "Total Heure Nuit 75%",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "Total Heure Nuit 100%",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "Total Heure Route",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"Total D1 (km)",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"Total D2 (km)",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"Total D3 (km)",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"Total D4 (km)",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"Total GD1 (km)",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"Total GD2 (km)",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"Total GD3 (km)",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"Total GD4 (km)",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"Total Déplacement (km)",
	);
	$array_export_fields[6][0] = array(
		"eu.matricule" => "Matricule",
		"u.firstname" => "Prénom",
		"u.lastname" => "Nom",
		"eu.antenne" => "Antenne",
		"date_debut" => "Date début",
		"date_fin" => "Date fin",
	);
	foreach($typesHoliday as $type) {
		$array_export_fields[6][0][$type['code']] = $type['label'];
		$array_export_fields[0][$type['code']] .= ($type['in_hour'] ? ' (H)' : ' (J)');
	}
}



if($datatoexport == 'analytique_pourcentage') {
	$array_export_entities[0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		//"tt.element_duration" => "task_time",
		"tt.element_date" => "task_time",
		"axe" => "task_time",
		"section" => "task_time",
		"pourcentage" => "task_time",
		"fdt.date_debut" => "timesheet_16@feuilledetemps",
		"fdt.status" => "timesheet_16@feuilledetemps",
	);
}
elseif($datatoexport == 'donnees_variables') { 
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_export_entities[0] = array(
			"eu.matricule" => "user",
			"u.firstname" => "user",
			"u.lastname" => "user",
			//"fdt.date_debut" => "timesheet_16@feuilledetemps",
			//"fdt.date_fin" => "timesheet_16@feuilledetemps",
			"s.date" => "timesheet_16@feuilledetemps",
			"petit_deplacement1" => "timesheet_16@feuilledetemps",
			"petit_deplacement2" => "timesheet_16@feuilledetemps",
			"petit_deplacement3" => "timesheet_16@feuilledetemps",
			"petit_deplacement4" => "timesheet_16@feuilledetemps",
			"repas1" => "timesheet_16@feuilledetemps",
			"repas2" => "timesheet_16@feuilledetemps",
			"heure_route" => "timesheet_16@feuilledetemps",
			"kilometres" => "timesheet_16@feuilledetemps",
			"kilometres_rappel" => "timesheet_16@feuilledetemps",
			"grand_deplacement1" => "timesheet_16@feuilledetemps",
			"grand_deplacement2" => "timesheet_16@feuilledetemps",
			"grand_deplacement3" => "timesheet_16@feuilledetemps",
			"teletravail" => "timesheet_16@feuilledetemps",
			"fdt.prime_astreinte" => "timesheet_16@feuilledetemps",
			"fdt.prime_exceptionnelle" => "timesheet_16@feuilledetemps",
			"fdt.prime_objectif" => "timesheet_16@feuilledetemps",
			"fdt.prime_variable" => "timesheet_16@feuilledetemps",
			"fdt.prime_amplitude" => "timesheet_16@feuilledetemps",
			"heure_nuit50" => "timesheet_16@feuilledetemps",
			"heure_nuit75" => "timesheet_16@feuilledetemps",
			"heure_nuit100" => "timesheet_16@feuilledetemps",
			"fdt.status" => "timesheet_16@feuilledetemps",
		);
	}
	else {
		$array_export_entities[0] = array(
			"eu.matricule" => "user",
			"u.firstname" => "user",
			"u.lastname" => "user",
			"s.date" => "timesheet_16@feuilledetemps",
			"fdt.status" => "timesheet_16@feuilledetemps",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_export_entities[0]['silae_extrafields.'.$key] = 'timesheet_16@feuilledetemps';
		}
	}
}
elseif($datatoexport == 'absences') {
	$array_export_entities[0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"ht.code_silae" => "holiday",
		"valeur" => "holiday",
		"h.date_debut" => "holiday",
		"h.date_fin" => "holiday",
		"type" => "holiday",
		"fdt.status" => "timesheet_16@feuilledetemps",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_export_entities[0]["hef.statutfdt"] = "holiday";
	}
}
elseif($datatoexport == 'heure_sup') {
	$array_export_entities[0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"code" => "timesheet_16@feuilledetemps",
		"valeur" => "holiday",
		"s.date" => "timesheet_16@feuilledetemps",
		"s.date2" => "timesheet_16@feuilledetemps",
		"type" => "holiday",
		"fdt.status" => "timesheet_16@feuilledetemps",
	);
}
elseif($datatoexport == 'total_hour_week') {
	$array_export_entities[0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"eu.antenne" => "user",
		"week" => "timesheet_16@feuilledetemps",
		"total_work" => "timesheet_16@feuilledetemps",
		"total_holiday" => "timesheet_16@feuilledetemps",
		"total_hour" => "timesheet_16@feuilledetemps",
	);
}
elseif($datatoexport == 'total_hour') {
	$array_export_entities[0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"eu.antenne" => "user",
		"element_date" => "timesheet_16@feuilledetemps",
		"SUM(element_duration)/3600 as total_hour" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "timesheet_16@feuilledetemps",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "timesheet_16@feuilledetemps",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "timesheet_16@feuilledetemps",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"timesheet_16@feuilledetemps",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"timesheet_16@feuilledetemps",
	);
}
elseif($datatoexport == 'total_holiday') {
	$array_export_entities[0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"eu.antenne" => "user",
		"date_debut" => "holiday",
		"date_fin" => "holiday",
	);
	foreach($typesHoliday as $type) {
		$array_export_entities[0][$type['code']] = 'holiday';
	}
}
else {
	$array_export_entities[0][0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		//"tt.element_duration" => "task_time",
		"tt.element_date" => "task_time",
		"axe" => "task_time",
		"section" => "task_time",
		"pourcentage" => "task_time",
		"fdt.date_debut" => "timesheet_16@feuilledetemps",
		"fdt.status" => "timesheet_16@feuilledetemps",
	);
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_export_entities[1][0] = array(
			"eu.matricule" => "user",
			"u.firstname" => "user",
			"u.lastname" => "user",
			//"fdt.date_debut" => "timesheet_16@feuilledetemps",
			//"fdt.date_fin" => "timesheet_16@feuilledetemps",
			"s.date" => "timesheet_16@feuilledetemps",
			"petit_deplacement1" => "timesheet_16@feuilledetemps",
			"petit_deplacement2" => "timesheet_16@feuilledetemps",
			"petit_deplacement3" => "timesheet_16@feuilledetemps",
			"petit_deplacement4" => "timesheet_16@feuilledetemps",
			"repas1" => "timesheet_16@feuilledetemps",
			"repas2" => "timesheet_16@feuilledetemps",
			"heure_route" => "timesheet_16@feuilledetemps",
			"kilometres" => "timesheet_16@feuilledetemps",
			"kilometres_rappel" => "timesheet_16@feuilledetemps",
			"grand_deplacement1" => "timesheet_16@feuilledetemps",
			"grand_deplacement2" => "timesheet_16@feuilledetemps",
			"grand_deplacement3" => "timesheet_16@feuilledetemps",
			"teletravail" => "timesheet_16@feuilledetemps",
			"fdt.prime_astreinte" => "timesheet_16@feuilledetemps",
			"fdt.prime_exceptionnelle" => "timesheet_16@feuilledetemps",
			"fdt.prime_objectif" => "timesheet_16@feuilledetemps",
			"fdt.prime_variable" => "timesheet_16@feuilledetemps",
			"fdt.prime_amplitude" => "timesheet_16@feuilledetemps",
			"heure_nuit50" => "timesheet_16@feuilledetemps",
			"heure_nuit75" => "timesheet_16@feuilledetemps",
			"heure_nuit100" => "timesheet_16@feuilledetemps",
			"fdt.status" => "timesheet_16@feuilledetemps",
		);
	}
	else {
		$array_export_entities[1][0] = array(
			"eu.matricule" => "user",
			"u.firstname" => "user",
			"u.lastname" => "user",
			"s.date" => "timesheet_16@feuilledetemps",
			"fdt.status" => "timesheet_16@feuilledetemps",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_export_entities[1][0]['silae_extrafields.'.$key] = 'timesheet_16@feuilledetemps';
		}
	}
	$array_export_entities[2][0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"ht.code_silae" => "holiday",
		"valeur" => "holiday",
		"h.date_debut" => "holiday",
		"h.date_fin" => "holiday",
		"type" => "holiday",
		"fdt.status" => "timesheet_16@feuilledetemps",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_export_entities[2][0]["hef.statutfdt"] = "holiday";
	}
	$array_export_entities[3][0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"code" => "timesheet_16@feuilledetemps",
		"valeur" => "holiday",
		"s.date" => "timesheet_16@feuilledetemps",
		"s.date2" => "timesheet_16@feuilledetemps",
		"type" => "holiday",
		"fdt.status" => "timesheet_16@feuilledetemps",
	);
	$array_export_entities[4][0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"week" => "timesheet_16@feuilledetemps",
		"total_work" => "timesheet_16@feuilledetemps",
		"total_holiday" => "timesheet_16@feuilledetemps",
		"total_hour" => "timesheet_16@feuilledetemps",
	);
	$array_export_entities[5][0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"eu.antenne" => "user",
		"element_date" => "timesheet_16@feuilledetemps",
		"SUM(element_duration)/3600 as total_hour" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "timesheet_16@feuilledetemps",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "timesheet_16@feuilledetemps",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "timesheet_16@feuilledetemps",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "timesheet_16@feuilledetemps",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"timesheet_16@feuilledetemps",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"timesheet_16@feuilledetemps",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"timesheet_16@feuilledetemps",
	);
	$array_export_entities[6][0] = array(
		"eu.matricule" => "user",
		"u.firstname" => "user",
		"u.lastname" => "user",
		"eu.antenne" => "user",
		"date_debut" => "holiday",
		"date_fin" => "holiday",
	);
	foreach($typesHoliday as $type) {
		$array_export_entities[6][0][$type['code']] = 'holiday';
	}
}


if($datatoexport == 'analytique_pourcentage') {
	$array_export_TypeFields[0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		//"tt.element_duration" => "Numeric",
		"tt.element_date" => "Date",
		"axe" => "Text",
		"section" => "Numeric",
		"pourcentage" => "Numeric",
		"fdt.date_debut" => "Date",
		"fdt.status" => "Status",
	);
}
elseif($datatoexport == 'donnees_variables') {
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_export_TypeFields[0] = array(
			"eu.matricule" => "Numeric",
			"u.firstname" => "Text",
			"u.lastname" => "Text",
			"fdt.date_debut" => "Date",
			//"fdt.date_fin" => "Date",
			"s.date" => "Date",
			"petit_deplacement1" => "Numeric",
			"petit_deplacement2" => "Numeric",
			"petit_deplacement3" => "Numeric",
			"petit_deplacement4" => "Numeric",
			"repas1" => "Numeric",
			"repas2" => "Numeric",
			"heure_route" => "Numeric",
			"kilometres" => "Numeric",
			"kilometres_rappel" => "Numeric",
			"grand_deplacement1" => "Numeric",
			"grand_deplacement2" => "Numeric",
			"grand_deplacement3" => "Numeric",
			"teletravail" => "Numeric",
			"fdt.prime_astreinte" => "Numeric",
			"fdt.prime_exceptionnelle" => "Numeric",
			"fdt.prime_objectif" => "Numeric",
			"fdt.prime_variable" => "Numeric",
			"fdt.prime_amplitude" => "Numeric",
			"heure_nuit50" => "Numeric",
			"heure_nuit75" => "Numeric",
			"heure_nuit100" => "Numeric",
			"fdt.status" => "Status",
		);
	}
	else {
		$array_export_TypeFields[0] = array(
			"eu.matricule" => "Numeric",
			"u.firstname" => "Text",
			"u.lastname" => "Text",
			"fdt.date_debut" => "Date",
			"s.date" => "Date",
			"fdt.status" => "Status",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_export_TypeFields[0]['silae_extrafields.'.$key] = $extrafields->attributes[$silae->table_element]['type'][$key];
		}
	}
}
elseif($datatoexport == 'absences') {
	$array_export_TypeFields[0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"ht.code_silae" => "Text",
		"valeur" => "Numeric",
		"h.date_debut" => "Date",
		"h.date_fin" => "Date",
		"type" => "Text",
		"fdt.status" => "Status",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_export_TypeFields[0]["hef.statutfdt"] = "Numeric";
	}
}
elseif($datatoexport == 'heure_sup') {
	$array_export_TypeFields[0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"code" => "Text",
		"valeur" => "Numeric",
		"s.date" => "Date",
		"s.date2" => "Date",
		"type" => "Text",
		"fdt.status" => "Status",
	);
}
elseif($datatoexport == 'total_hour_week') {
	$array_export_TypeFields[0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"eu.antenne" => "Text",
		"week" => "Date",
		"total_work" => "Numeric",
		"total_holiday" => "Numeric",
		"total_hour" => "Numeric",
	);
}
elseif($datatoexport == 'total_hour') {
	$array_export_TypeFields[0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"eu.antenne" => "Text",
		"element_date" => "Date",
		"SUM(element_duration)/3600 as total_hour" => "Numeric",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "Numeric",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "Numeric",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "Numeric",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "Numeric",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "Numeric",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "Numeric",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "Numeric",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "Numeric",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"Numeric",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"Numeric",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"Numeric",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"Numeric",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"Numeric",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"Numeric",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"Numeric",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"Numeric",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"Numeric",
	);
}
elseif($datatoexport == 'total_holiday') {
	$array_export_TypeFields[0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"eu.antenne" => "Text",
		"date_debut" => "Date",
		"date_fin" => "Date",
	);
	foreach($typesHoliday as $type) {
		$array_export_TypeFields[0][$type['code']] = 'Numeric';
	}
}
else {
	$array_export_TypeFields[0][0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		//"fdt.date_debut" => "Date",
		//"fdt.date_fin" => "Date",
		"tt.element_date" => "Date",
		//"tt.element_duration" => "Numeric",
		"axe" => "Text",
		"section" => "Numeric",
		"pourcentage" => "Numeric",
		"fdt.date_debut" => "Date",
		"fdt.status" => "Status",
	);
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_export_TypeFields[1][0] = array(
			"eu.matricule" => "Numeric",
			"u.firstname" => "Text",
			"u.lastname" => "Text",
			"fdt.date_debut" => "Date",
			//"fdt.date_fin" => "Date",
			"s.date" => "Date",
			"petit_deplacement1" => "Numeric",
			"petit_deplacement2" => "Numeric",
			"petit_deplacement3" => "Numeric",
			"petit_deplacement4" => "Numeric",
			"repas1" => "Numeric",
			"repas2" => "Numeric",
			"heure_route" => "Numeric",
			"kilometres" => "Numeric",
			"kilometres_rappel" => "Numeric",
			"grand_deplacement1" => "Numeric",
			"grand_deplacement2" => "Numeric",
			"grand_deplacement3" => "Numeric",
			"teletravail" => "Numeric",
			"fdt.prime_astreinte" => "Numeric",
			"fdt.prime_exceptionnelle" => "Numeric",
			"fdt.prime_objectif" => "Numeric",
			"fdt.prime_variable" => "Numeric",
			"fdt.prime_amplitude" => "Numeric",
			"heure_nuit50" => "Numeric",
			"heure_nuit75" => "Numeric",
			"heure_nuit100" => "Numeric",
			"fdt.status" => "Status",
		);
	}
	else {
		$array_export_TypeFields[1][0] = array(
			"eu.matricule" => "Numeric",
			"u.firstname" => "Text",
			"u.lastname" => "Text",
			"fdt.date_debut" => "Date",
			"s.date" => "Date",
			"fdt.status" => "Status",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_export_TypeFields[1][0]['silae_extrafields.'.$key] = $extrafields->attributes[$silae->table_element]['type'][$key];
		}
	}
	$array_export_TypeFields[2][0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		//"fdt.date_debut" => "Date",
		//"fdt.date_fin" => "Date",
		"ht.code_silae" => "Text",
		"valeur" => "Numeric",
		"h.date_debut" => "Date",
		"h.date_fin" => "Date",
		"type" => "Text",
		"fdt.status" => "Status",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_export_TypeFields[2][0]["hef.statutfdt"] = "Numeric";
	}
	$array_export_TypeFields[3][0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		//"fdt.date_debut" => "Date",
		//"fdt.date_fin" => "Date",
		"code" => "Text",
		"valeur" => "Numeric",
		"s.date" => "Date",
		"s.date2" => "Date",
		"type" => "Text",
		"fdt.status" => "Status",
	);
	$array_export_TypeFields[4][0] = array(
		"eu.matricule" => "",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"week" => "Numeric",
		"total_work" => "",
		"total_holiday" => "",
		"total_hour" => "",
	);
	$array_export_TypeFields[5][0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"eu.antenne" => "Text",
		"element_date" => "Date",
		"SUM(element_duration)/3600 as total_hour" => "Numeric",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "Numeric",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "Numeric",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "Numeric",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "Numeric",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "Numeric",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "Numeric",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "Numeric",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "Numeric",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"Numeric",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"Numeric",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"Numeric",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"Numeric",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"Numeric",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"Numeric",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"Numeric",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"Numeric",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"Numeric",
	);
	$array_export_TypeFields[6][0] = array(
		"eu.matricule" => "Numeric",
		"u.firstname" => "Text",
		"u.lastname" => "Text",
		"eu.antenne" => "Text",
		"date_debut" => "Date",
		"date_fin" => "Date",
	);
	foreach($typesHoliday as $type) {
		$array_export_TypeFields[6][0][$type['code']] = 'Numeric';
	}
}


if($datatoexport == 'analytique_pourcentage') {
	$array_tablename[0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		//"tt.element_duration" => "llx_element_time",
		"tt.element_date" => "llx_element_time",
		"section" => "llx_projet",
		"pourcentage" => "llx_element_time",
		"fdt.date_debut" => "llx_feuilledetemps_feuilledetemps",
		"fdt.status" => "llx_feuilledetemps_feuilledetemps",
	);
}
elseif($datatoexport == 'donnees_variables')  {
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_tablename[0] = array(
			"eu.matricule" => "llx_user_extrafields",
			"u.firstname" => "llx_user",
			"u.lastname" => "llx_user",
			//"fdt.date_debut" => "llx_feuilledetemps_feuilledetemps",
			//"fdt.date_fin" => "llx_feuilledetemps_feuilledetemps",
			"s.date" => "llx_feuilledetemps_feuilledetemps",
			"petit_deplacement1" => "llx_feuilledetemps_deplacement",
			"petit_deplacement2" => "llx_feuilledetemps_deplacement",
			"petit_deplacement3" => "llx_feuilledetemps_deplacement",
			"petit_deplacement4" => "llx_feuilledetemps_deplacement",
			"repas1" => "llx_feuilledetemps_silae",
			"repas2" => "llx_feuilledetemps_silae",
			"heure_route" => "llx_feuilledetemps_silae",
			"kilometres" => "llx_feuilledetemps_silae",
			"kilometres_rappel" => "llx_feuilledetemps_silae",
			"grand_deplacement1" => "llx_feuilledetemps_deplacement",
			"grand_deplacement2" => "llx_feuilledetemps_deplacement",
			"grand_deplacement3" => "llx_feuilledetemps_deplacement",
			"teletravail" => "llx_feuilledetemps_silae",
			"fdt.prime_astreinte" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_exceptionnelle" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_objectif" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_variable" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_amplitude" => "llx_feuilledetemps_feuilledetemps",
			"heure_nuit50" => "llx_feuilledetemps_silae",
			"heure_nuit75" => "llx_feuilledetemps_silae",
			"heure_nuit100" => "llx_feuilledetemps_silae",
			"fdt.status" => "llx_feuilledetemps_feuilledetemps",
		);
	}
	else {
		$array_tablename[0] = array(
			"eu.matricule" => "llx_user_extrafields",
			"u.firstname" => "llx_user",
			"u.lastname" => "llx_user",
			"s.date" => "llx_feuilledetemps_feuilledetemps",
			"fdt.status" => "llx_feuilledetemps_feuilledetemps",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_tablename[0]['silae_extrafields.'.$key] = 'llx_feuilledetemps_silae_extrafields';
		}
	}
}
elseif($datatoexport == 'absences') {
	$array_tablename[0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"ht.code_silae" => "llx_holiday_types",
		"valeur" => "",
		"h.date_debut" => "llx_holiday",
		"h.date_fin" => "llx_holiday",
		"type" => "",
		"fdt.status" => "llx_feuilledetemps_feuilledetemps",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_tablename[0]["hef.statutfdt"] = "llx_holiday";
	}
}
elseif($datatoexport == 'heure_sup') {
	$array_tablename[0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"code" => "",
		"valeur" => "",
		"s.date" => "llx_feuilledetemps_feuilledetemps",
		"s.date2" => "llx_feuilledetemps_feuilledetemps",
		"type" => "",
		"fdt.status" => "llx_feuilledetemps_feuilledetemps",
	);
}
elseif($datatoexport == 'total_hour_week') {
	$array_tablename[0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"week" => "",
		"total_work" => "",
		"total_holiday" => "",
		"total_hour" => "",
	);
}
elseif($datatoexport == 'total_hour') {
	$array_tablename[0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"eu.antenne" => "llx_user_extrafields",
		"element_date" => "llx_element_time",
		"SUM(element_duration)/3600 as total_hour" => "llx_element_time",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "llx_feuilledetemps_silae",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "llx_feuilledetemps_silae",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "llx_feuilledetemps_silae",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "llx_feuilledetemps_silae",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "llx_feuilledetemps_regul",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "llx_feuilledetemps_regul",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "llx_feuilledetemps_regul",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "llx_feuilledetemps_silae",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"llx_feuilledetemps_deplacement",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"llx_feuilledetemps_deplacement",
	);
}
elseif($datatoexport == 'total_holiday') {
	$array_tablename[0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"eu.antenne" => "llx_user_extrafields",
		"date_debut" => "llx_holiday",
		"date_fin" => "llx_holiday",
	);
	foreach($typesHoliday as $type) {
		$array_tablename[0][$type['code']] = 'llx_holiday';
	}
}
else {
	$array_tablename[0][0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		//"tt.element_duration" => "llx_element_time",
		"tt.element_date" => "llx_element_time",
		"section" => "llx_projet",
		"pourcentage" => "llx_element_time",
		"fdt.date_debut" => "llx_feuilledetemps_feuilledetemps",
		"fdt.status" => "llx_feuilledetemps_feuilledetemps",
	);
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_tablename[1][0] = array(
			"eu.matricule" => "llx_user_extrafields",
			"u.firstname" => "llx_user",
			"u.lastname" => "llx_user",
			//"fdt.date_debut" => "llx_feuilledetemps_feuilledetemps",
			//"fdt.date_fin" => "llx_feuilledetemps_feuilledetemps",
			"s.date" => "llx_feuilledetemps_feuilledetemps",
			"petit_deplacement1" => "llx_feuilledetemps_deplacement",
			"petit_deplacement2" => "llx_feuilledetemps_deplacement",
			"petit_deplacement3" => "llx_feuilledetemps_deplacement",
			"petit_deplacement4" => "llx_feuilledetemps_deplacement",
			"repas1" => "llx_feuilledetemps_silae",
			"repas2" => "llx_feuilledetemps_silae",
			"heure_route" => "llx_feuilledetemps_silae",
			"kilometres" => "llx_feuilledetemps_silae",
			"kilometres_rappel" => "llx_feuilledetemps_silae",
			"grand_deplacement1" => "llx_feuilledetemps_deplacement",
			"grand_deplacement2" => "llx_feuilledetemps_deplacement",
			"grand_deplacement3" => "llx_feuilledetemps_deplacement",
			"teletravail" => "llx_feuilledetemps_silae",
			"fdt.prime_astreinte" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_exceptionnelle" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_objectif" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_variable" => "llx_feuilledetemps_feuilledetemps",
			"fdt.prime_amplitude" => "llx_feuilledetemps_feuilledetemps",
			"heure_nuit50" => "llx_feuilledetemps_silae",
			"heure_nuit75" => "llx_feuilledetemps_silae",
			"heure_nuit100" => "llx_feuilledetemps_silae",
			"fdt.status" => "llx_feuilledetemps_feuilledetemps",
		);
	}
	else {
		$array_tablename[1][0] = array(
			"eu.matricule" => "llx_user_extrafields",
			"u.firstname" => "llx_user",
			"u.lastname" => "llx_user",
			"s.date" => "llx_feuilledetemps_feuilledetemps",
			"fdt.status" => "llx_feuilledetemps_feuilledetemps",
		);
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_tablename[1][0]['silae_extrafields.'.$key] = 'llx_feuilledetemps_silae_extrafields';
		}
	}
	$array_tablename[2][0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"ht.code_silae" => "llx_holiday_types",
		"valeur" => "",
		"h.date_debut" => "llx_holiday",
		"h.date_fin" => "llx_holiday",
		"type" => "",
		"fdt.status" => "llx_feuilledetemps_feuilledetemps",
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_tablename[2][0]["hef.statutfdt"] = "llx_holiday";
	}
	$array_tablename[3][0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"code" => "",
		"valeur" => "",
		"s.date" => "llx_feuilledetemps_feuilledetemps",
		"s.date2" => "llx_feuilledetemps_feuilledetemps",
		"type" => "",
		"fdt.status" => "llx_feuilledetemps_feuilledetemps",
	);
	$array_tablename[4][0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"week" => "",
		"total_work" => "",
		"total_holiday" => "",
		"total_hour" => "",
	);
	$array_tablename[5][0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"eu.antenne" => "llx_user_extrafields",
		"element_date" => "llx_element_time",
		"SUM(element_duration)/3600 as total_hour" => "llx_element_time",
		"(SUM(COALESCE(s_heure_sup00/3600, 0) + COALESCE(r_heure_sup00/3600, 0))) as total_hs00" => "llx_feuilledetemps_silae",
		"(SUM(COALESCE(s_heure_sup25/3600, 0) + COALESCE(r_heure_sup25/3600, 0))) as total_hs25" => "llx_feuilledetemps_silae",
		"(SUM(COALESCE(s_heure_sup50/3600, 0) + COALESCE(r_heure_sup50/3600, 0))) as total_hs50" => "llx_feuilledetemps_silae",
		"(SUM(COALESCE(s_heure_sup50ht/3600, 0) + COALESCE(r_heure_sup50ht/3600, 0))) as total_hs50ht" => "llx_feuilledetemps_silae",
		"(SUM(r_heure_nuit_50)/3600) as total_heurenuit_50" => "llx_feuilledetemps_regul",
		"(SUM(r_heure_nuit_75)/3600) as total_heurenuit_75" => "llx_feuilledetemps_regul",
		"(SUM(r_heure_nuit_100)/3600) as total_heurenuit_100" => "llx_feuilledetemps_regul",
		"(SUM(COALESCE(s_heure_route/3600, 0) + COALESCE(r_heure_route/3600, 0))) as total_heureroute" => "llx_feuilledetemps_silae",
		// "SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1 as total_d1"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2 as total_d2"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3 as total_d3"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4 as total_d4"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 5 THEN 1 ELSE 0 END) * dd.distancegd1 as total_gd1"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 6 THEN 1 ELSE 0 END) * dd.distancegd2 as total_gd2"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 8 THEN 1 ELSE 0 END) * dd.distancegd3 as total_gd3"=>"llx_feuilledetemps_deplacement",
		// "SUM(CASE deplacement WHEN 9 THEN 1 ELSE 0 END) * dd.distancegd4 as total_gd4"=>"llx_feuilledetemps_deplacement",
		"COALESCE(SUM(CASE deplacement WHEN 1 THEN 1 ELSE 0 END) * dd.distanced1, 0) + COALESCE(SUM(CASE deplacement WHEN 2 THEN 1 ELSE 0 END) * dd.distanced2, 0) +
			COALESCE(SUM(CASE deplacement WHEN 3 THEN 1 ELSE 0 END) * dd.distanced3, 0) + COALESCE(SUM(CASE deplacement WHEN 4 THEN 1 ELSE 0 END) * dd.distanced4, 0) +
			SUM(COALESCE(s_kilometres, 0) + COALESCE(r_kilometres, 0)) as total_deplacement"=>"llx_feuilledetemps_deplacement",
	);
	$array_tablename[6][0] = array(
		"eu.matricule" => "llx_user_extrafields",
		"u.firstname" => "llx_user",
		"u.lastname" => "llx_user",
		"eu.antenne" => "llx_user_extrafields",
		"date_debut" => "llx_holiday",
		"date_fin" => "llx_holiday",
	);
	foreach($typesHoliday as $type) {
		$array_tablename[6][0][$type['code']] = 'llx_holiday';
	}
}


$array_export_module['name'] = $langs->transnoentitiesnoconv("ModuleFeuilleDeTempsName");

if($datatoexport == 'analytique_pourcentage') {
	$array_export_label[0] = "Analytique en pourcentage";
} 
elseif($datatoexport == 'donnees_variables')  {
	$array_export_label[0] = "Données variables";
}
elseif($datatoexport == 'absences')  {
	$array_export_label[0] = "Absences";
}
elseif($datatoexport == 'heure_sup')  {
	$array_export_label[0] = "Heures Sup";
}
elseif($datatoexport == 'total_hour_week')  {
	$array_export_label[0] = "Total des heures hebdomadaires par collaborateur";
}
elseif($datatoexport == 'total_hour')  {
	$array_export_label[0] = "Total des heures travaillées par collaborateur";
}
elseif($datatoexport == 'total_holiday')  {
	$array_export_label[0] = "Total des heures de congés par collaborateur";
}
else {
	$array_export_label[0] = "Activité journalière des utilisateurs";
	$array_export_label[1] = "Données variables";
	$array_export_label[2] = "Absences";
	$array_export_label[3] = "Heures Sup";
	$array_export_label[4] = "Total des heures hebdomadaires par collaborateur";
	$array_export_label[5] = "Total des heures travaillées par collaborateur";
	$array_export_label[6] = "Total des heures de congés par collaborateur";
}


$array_export_icon[0] = "timesheet_16@feuilledetemps";
$array_export_special[0] = "";
$array_export_examplevalues[0] = "";
$array_export_help[0] = "";
$array_export_dependencies = "";
$array_export_FilterValue[0] = null;

/*
 * Actions
 */
if ($action == 'selectfield') {     // Selection of field at step 2
	$fieldsarray = $array_export_fields[0];
	$fieldsentitiesarray = $array_export_entities[0];
	$fieldsdependenciesarray = $array_export_dependencies[0];

	if ($field == 'all') {
		foreach ($fieldsarray as $key => $val) {
			if (!empty($array_selected[$key])) {
				continue; // If already selected, check next
			}
			$array_selected[$key] = count($array_selected) + 1;
			//print_r($array_selected);
			$_SESSION["export_selected_fields"] = $array_selected;
		}
	} else {
		$warnings = array();

		$array_selected[$field] = count($array_selected) + 1; // We tag the key $field as "selected"
		// We check if there is a dependency to activate
		/*var_dump($field);
		var_dump($fieldsentitiesarray[$field]);
		var_dump($fieldsdependenciesarray);*/
		$listofdependencies = array();
		if (!empty($fieldsentitiesarray[$field]) && !empty($fieldsdependenciesarray[$fieldsentitiesarray[$field]])) {
			// We found a dependency on the type of field
			$tmp = $fieldsdependenciesarray[$fieldsentitiesarray[$field]]; // $fieldsdependenciesarray=array('element'=>'fd.rowid') or array('element'=>array('fd.rowid','ab.rowid'))
			if (is_array($tmp)) {
				$listofdependencies = $tmp;
			} else {
				$listofdependencies = array($tmp);
			}
		} elseif (!empty($field) && !empty($fieldsdependenciesarray[$field])) {
			// We found a dependency on a dedicated field
			$tmp = $fieldsdependenciesarray[$field]; // $fieldsdependenciesarray=array('fd.fieldx'=>'fd.rowid') or array('fd.fieldx'=>array('fd.rowid','ab.rowid'))
			if (is_array($tmp)) {
				$listofdependencies = $tmp;
			} else {
				$listofdependencies = array($tmp);
			}
		}

		if (count($listofdependencies)) {
			foreach ($listofdependencies as $fieldid) {
				if (empty($array_selected[$fieldid])) {
					$array_selected[$fieldid] = count($array_selected) + 1; // We tag the key $fieldid as "selected"
					$warnings[] = $langs->trans("ExportFieldAutomaticallyAdded", $langs->transnoentitiesnoconv($fieldsarray[$fieldid]));
				}
			}
		}
		//print_r($array_selected);
		$_SESSION["export_selected_fields"] = $array_selected;

		setEventMessages($warnings, null, 'warnings');
	}
}
if ($action == 'unselectfield') {
	if ($_GET["field"] == 'all') {
		$array_selected = array();
		$_SESSION["export_selected_fields"] = $array_selected;
	} else {
		unset($array_selected[$_GET["field"]]);
		// Renumber fields of array_selected (from 1 to nb_elements)
		asort($array_selected);
		$i = 0;
		$array_selected_save = $array_selected;
		foreach ($array_selected as $code => $value) {
			$i++;
			$array_selected[$code] = $i;
			//print "x $code x $i y<br>";
		}
		$_SESSION["export_selected_fields"] = $array_selected;
	}
}

if ($action == 'downfield' || $action == 'upfield') {
	$pos = $array_selected[$_GET["field"]];
	if ($action == 'downfield') {
		$newpos = $pos + 1;
	}
	if ($action == 'upfield') {
		$newpos = $pos - 1;
	}
	// Recherche code avec qui switcher
	$newcode = "";
	foreach ($array_selected as $code => $value) {
		if ($value == $newpos) {
			$newcode = $code;
			break;
		}
	}
	//print("Switch pos=$pos (code=".$_GET["field"].") and newpos=$newpos (code=$newcode)");
	if ($newcode) {   // Si newcode trouve (protection contre resoumission de page)
		$array_selected[$_GET["field"]] = $newpos;
		$array_selected[$newcode] = $pos;
		$_SESSION["export_selected_fields"] = $array_selected;
	}
}

if ($action == 'cleanselect' || $step == 1) {
	$_SESSION["export_selected_fields"] = array();
	$_SESSION["export_filtered_fields"] = array();
	$array_selected = array();
	$array_filtervalue = array();
}

if ($action == 'builddoc') {
	$max_execution_time_for_importexport = (empty($conf->global->EXPORT_MAX_EXECUTION_TIME) ? 300 : $conf->global->EXPORT_MAX_EXECUTION_TIME); // 5mn if not defined
	$max_time = @ini_get("max_execution_time");
	if ($max_time && $max_time < $max_execution_time_for_importexport) {
		dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
		@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
	}

	// Build export file
	$result = $objexport->build_file_bis($user, GETPOST('model', 'alpha'), $datatoexport, $array_selected, $array_filtervalue, '', $array_export_fields, $array_export_TypeFields, $array_export_special);
	if ($result < 0) {
		setEventMessages($objexport->error, $objexport->errors, 'errors');
		$sqlusedforexport = $objexport->sqlusedforexport;
	} else {
		setEventMessages($langs->trans("FileSuccessfullyBuilt"), null, 'mesgs');
		$sqlusedforexport = $objexport->sqlusedforexport;
	}
}

if ($action == 'buildalldoc') {
	$error = 0;
	$max_execution_time_for_importexport = (empty($conf->global->EXPORT_MAX_EXECUTION_TIME) ? 300 : $conf->global->EXPORT_MAX_EXECUTION_TIME); // 5mn if not defined
	$max_time = @ini_get("max_execution_time");
	if ($max_time && $max_time < $max_execution_time_for_importexport) {
		dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
		@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
	}

	$date_debut = 0;
	$date_fin = 0;
	if (GETPOST("exportdate_month", 'int') > 0 && GETPOST("exportdate_year", 'int') > 0) {
		$date_debut = dol_mktime(-1, -1, -1, GETPOST("exportdate_month", 'int'), 1, GETPOST("exportdate_year", 'int'));
		$date_fin = dol_get_last_day(GETPOST("exportdate_year", 'int'), GETPOST("exportdate_month", 'int'));
	}

	$objexport = new ExtendedExportFDT($db);


	$array_selected[0] = array(
		"eu.matricule" => 1,
		"axe" => 2,
		"section" => 3,
		"pourcentage" => 4,
		"fdt.date_debut" => 5,
	);
	if(!$conf->global->FDT_DISPLAY_COLUMN) {
		$array_selected[1] = array(
			"eu.matricule" => 1,
			"u.lastname" => 2,
			"u.firstname" => 3,
			"petit_deplacement1" => 4,
			"petit_deplacement2" => 5,
			"petit_deplacement3" => 6,
			"petit_deplacement4" => 7,
			"repas1" => 8,
			"repas2" => 9,
			"heure_route" => 10,
			"kilometres" => 11,
			"kilometres_rappel" => 12,
			"grand_deplacement1" => 13,
			"grand_deplacement2" => 14,
			"grand_deplacement3" => 15,
			"indemnite_tt" => 16,
			"fdt.prime_astreinte" => 17,
			"fdt.prime_exceptionnelle" => 18,
			"fdt.prime_objectif" => 19,
			"fdt.prime_variable" => 20,
			"fdt.prime_amplitude" => 21,
			"heure_nuit50" => 22,
			"heure_nuit75" => 23,
			"heure_nuit100" => 24
		);
	}
	else {
		$array_selected[1] = array(
			"eu.matricule" => 1,
			"u.lastname" => 2,
			"u.firstname" => 3,
		);
		$cpt = 4;
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			$array_selected[1]['silae_extrafields.'.$key] = $cpt;
			$cpt++;
		}
	}
	$array_selected[2] = array(
		"eu.matricule" => 1,
		"ht.code_silae" => 2,
		"valeur" => 3,
		"h.date_debut" => 4,
		"h.date_fin" => 5,
		"type" => 6
	);
	$array_selected[3] = array(
		"eu.matricule" => 1,
		"code" => 2,
		"valeur" => 3,
		"s.date" => 4,
		"s.date2" => 5,
		"type" => 6
	);

	$array_filtervalue[0] = array(
		//"fdt.date_debut" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		//"fdt.date_fin" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		"tt.element_date" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		"fdt.status" => FeuilleDeTemps::STATUS_VALIDATED,
	);
	$array_filtervalue[1] = array(
		//"fdt.date_debut" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		//"fdt.date_fin" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		"fdt.date_debut" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		"fdt.status" => FeuilleDeTemps::STATUS_VALIDATED,
	);
	$array_filtervalue[2] = array(
		//"fdt.date_debut" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		//"fdt.date_fin" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		"h.date_debut" => dol_mktime(-1, -1, -1, GETPOST("exportdate_month", 'int'), 1, GETPOST("exportdate_year", 'int')),
		"h.date_fin" => dol_get_last_day(GETPOST("exportdate_year", 'int'), GETPOST("exportdate_month", 'int')),
		"fdt.status" => FeuilleDeTemps::STATUS_VALIDATED,
	);
	if($conf->global->FDT_STATUT_HOLIDAY) {
		$array_filtervalue[2]["hef.statutfdt"] = 2;
	}
	$array_filtervalue[3] = array(
		//"fdt.date_debut" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		//"fdt.date_fin" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		"s.date" => GETPOST("exportdate_year", 'int').str_pad(GETPOST("exportdate_month", 'int'), 2, '0', STR_PAD_LEFT),
		"fdt.status" => FeuilleDeTemps::STATUS_VALIDATED,
	);

	$array_export_special[0] = '';
	$array_export_special[1] = '';
	$array_export_special[2] = '';
	$array_export_special[3] = '';

	// Build export file
	for($i = 0; $i < 4; $i++) {
		$result = $objexport->build_file_bis($user, GETPOST('model', 'alpha'), $datatoexport[$i], $array_selected[$i], $array_filtervalue[$i], '', $array_export_fields[$i], $array_export_TypeFields[$i], $array_export_special[$i]);
		if ($result < 0) {
			$error++;
			setEventMessages($objexport->error, $objexport->errors, 'errors');
			$sqlusedforexport = $objexport->sqlusedforexport;
		} else {
			setEventMessages("Fichier généré", null, 'mesgs');
			$sqlusedforexport = $objexport->sqlusedforexport;
		}
	}

	if(!$error) {
		// On passe au statut Exporté l'ensemble des feuilles de temps que l'on a exportée
		$feuilleDeTemps = new FeuilleDeTemps($db);
		$user_static = new User($db);
		$filter = array(
			'customsql' => 'date_debut = "'.substr($db->idate($date_debut), 0, 10).'" AND date_fin = "'.substr($db->idate($date_fin), 0, 10).'"',
			'status' => FeuilleDeTemps::STATUS_VALIDATED,
		);
		$allFdtValidated = $feuilleDeTemps->fetchAll('', '', 0, 0, $filter, 'AND');
		foreach($allFdtValidated as $id => $fdt) {
			$user_static->fetch($fdt->fk_user);
			if(!$conf->global->FDT_MANAGE_EMPLOYER || ($conf->global->FDT_MANAGE_EMPLOYER && $user_static->array_options['options_fk_employeur'] == 157)){
				$fdt->setExported($user);
			}
		}

		header("Location: ".$_SERVER["PHP_SELF"].'?step=1');
		return;
	}
}

if ($action == 'builddoccompta' && $conf->donneesrh->enabled) {
	$max_execution_time_for_importexport = (empty($conf->global->EXPORT_MAX_EXECUTION_TIME) ? 300 : $conf->global->EXPORT_MAX_EXECUTION_TIME); // 5mn if not defined
	$max_time = @ini_get("max_execution_time");
	if ($max_time && $max_time < $max_execution_time_for_importexport) {
		dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
		@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
	}

	$date_start = 0;
	if (GETPOST("exportdate_startmonth", 'int') > 0 && GETPOST("exportdate_startyear", 'int') > 0) {
		$date_start = dol_mktime(-1, -1, -1, GETPOST("exportdate_startmonth", 'int'), 1, GETPOST("exportdate_startyear", 'int'));
	}

	$date_end = 0;
	if (GETPOST("exportdate_endmonth", 'int') > 0 && GETPOST("exportdate_endyear", 'int') > 0) {
		$date_end = dol_get_last_day(GETPOST("exportdate_endyear", 'int'), GETPOST("exportdate_endmonth", 'int'));
	}

	$objexport = new ExtendedExportFDT($db);

	$array_selected = [
		'ue.matricule' => 1,
		'u.firstname' => 2,
		'u.lastname' => 3,
		't.type' => 4,
		't.date_start' => 5,
		't.date_end' => 6,
		't.observation' => 7
	];

	$array_filtervalue = [
		't.date_start' => $date_start,
		't.date_end' => $date_end,
	];

	$array_export_fields[0] = [
		'ue.matricule' => "Matricule",
		'u.firstname' => "Prénom",
		'u.lastname' => "Nom",
		't.type' => "Type",
		't.date_start' => "Date début",
		't.date_end' => "Date fin",
		't.observation' => "Observation"
	];

	$array_export_TypeFields[0] = [
		'ue.matricule' => "Numeric",
		'u.firstname' => "Text",
		'u.lastname' => "Text",
		't.type' => "Text",
		't.date_start' => "Date",
		't.date_end' => "Date",
		't.observation' => "Text"
	];

	$array_export_special[0] = '';

	// Build export file
	$result = $objexport->build_file_bis($user, GETPOST('model', 'alpha'), 'ObservationCompta', $array_selected, $array_filtervalue, '', $array_export_fields, $array_export_TypeFields, $array_export_special);
	if ($result < 0) {
		$error++;
		setEventMessages($objexport->error, $objexport->errors, 'errors');
		$sqlusedforexport = $objexport->sqlusedforexport;
	} else {
		setEventMessages("Fichier généré", null, 'mesgs');
		$sqlusedforexport = $objexport->sqlusedforexport;
		header("Location: ".$_SERVER["PHP_SELF"].'?step=1');
		return;
	}
}

// Delete file
if (($step == 5 || $step == 1) && $action == 'confirm_deletefile' && $confirm == 'yes') {
	$file = $upload_dir."/".GETPOST('file'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

	$ret = dol_delete_file($file);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
	}
	header('Location: '.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport);
	exit;
}

if ($action == 'deleteprof') {
	if (GETPOST("id", 'int')) {
		$objexport->fetch(GETPOST('id', 'int'));
		$result = $objexport->delete($user);
	}
}

// TODO The export for filter is not yet implemented (old code created conflicts with step 2). We must use same way of working and same combo list of predefined export than step 2.
if ($action == 'add_export_model') {
	if ($export_name) {
		asort($array_selected);

		// Set save string
		$hexa = '';
		foreach ($array_selected as $key => $val) {
			if ($hexa) {
				$hexa .= ',';
			}
			$hexa .= $key;
		}

		$hexafiltervalue = '';
		if (!empty($array_filtervalue) && is_array($array_filtervalue)) {
			foreach ($array_filtervalue as $key => $val) {
				if ($hexafiltervalue) {
					$hexafiltervalue .= ',';
				}
				$hexafiltervalue .= $key.'='.$val;
			}
		}

		$objexport->model_name = $export_name;
		$objexport->datatoexport = $datatoexport;
		$objexport->hexa = $hexa;
		$objexport->hexafiltervalue = $hexafiltervalue;
		$objexport->fk_user = (GETPOST('visibility', 'aZ09') == 'all' ? 0 : $user->id);

		$result = $objexport->create($user);
		if ($result >= 0) {
			setEventMessages($langs->trans("ExportModelSaved", $objexport->model_name), null, 'mesgs');
		} else {
			$langs->load("errors");
			if ($objexport->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->trans("ErrorExportDuplicateProfil"), null, 'errors');
			} else {
				setEventMessages($objexport->error, $objexport->errors, 'errors');
			}
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("ExportModelName")), null, 'errors');
	}
}

// Reload a predefined export model
if ($step == 2 && $action == 'select_model') {
	$_SESSION["export_selected_fields"] = array();
	$_SESSION["export_filtered_fields"] = array();

	$array_selected = array();
	$array_filtervalue = array();

	$result = $objexport->fetch($exportmodelid);
	if ($result > 0) {
		$fieldsarray = preg_split("/,(?! [^(]*\))/", $objexport->hexa);
		$i = 1;
		foreach ($fieldsarray as $val) {
			$array_selected[$val] = $i;
			$i++;
		}
		$_SESSION["export_selected_fields"] = $array_selected;

		$fieldsarrayvalue = explode(',', $objexport->hexafiltervalue);
		$i = 1;
		foreach ($fieldsarrayvalue as $val) {
			$tmp = explode('=', $val);
			$array_filtervalue[$tmp[0]] = $tmp[1];
			$i++;
		}
		$_SESSION["export_filtered_fields"] = $array_filtervalue;
	}
}

// Get form with filters
if ($step == 4 && $action == 'submitFormField') {
	// on boucle sur les champs selectionne pour recuperer la valeur
	if (is_array($array_export_TypeFields[0])) {
		$_SESSION["export_filtered_fields"] = array();
		foreach ($array_export_TypeFields[0] as $code => $type) {	// $code: s.fieldname $value: Text|Boolean|List:ccc
			$newcode = (string) preg_replace('/\./', '_', $code);
			//print 'xxx '.$code."=".$newcode."=".$type."=".$_POST[$newcode]."\n<br>";
			$check = 'alphanohtml';
			$filterqualified = 1;
			if (!GETPOSTISSET($newcode) || GETPOST($newcode, $check) == '') {
				$filterqualified = 0;
			} elseif (preg_match('/^List/', $type) && (is_numeric(GETPOST($newcode, $check)) && GETPOST($newcode, $check) <= 0)) {
				$filterqualified = 0;
			}
			if ($filterqualified) {
				//print 'Filter on '.$newcode.' type='.$type.' value='.$_POST[$newcode]."\n";
				$array_export_FilterValue[0][$code] = GETPOST($newcode, $check);
			}
		}
		$array_filtervalue = (!empty($array_export_FilterValue[0]) ? $array_export_FilterValue[0] : '');
		$_SESSION["export_filtered_fields"] = $array_filtervalue;
	}
}

if ($step == 4 && $datatoexport == "total_hour_week") {
	if(empty($array_filtervalue['week']) || strlen($array_filtervalue['week']) != 6) {	
		setEventMessages("Veuillez entrer un filtre pour 'Semaine' sous la forme YYYYMM", null, 'errors');
		header("Location: ".$_SERVER["PHP_SELF"].'?step=3&datatoexport='.$datatoexport);
		exit;
	}
}

if ($step == 4 && $datatoexport == "total_holiday") {
	if(empty($array_filtervalue['date_debut']) || strlen($array_filtervalue['date_debut']) != 8 || empty($array_filtervalue['date_fin']) || strlen($array_filtervalue['date_fin']) != 8) {	
		setEventMessages("Veuillez entrer un filtre pour 'Date début' et 'Date fin' sous la forme AAAAMMJJ", null, 'errors');
		header("Location: ".$_SERVER["PHP_SELF"].'?step=3&datatoexport='.$datatoexport);
		exit;
	}
}


/*
 * View
 */


if ($step == 1 || !$datatoexport) {
	llxHeader('', $langs->trans("Export Feuille de temps"));

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -1);

	/*
	 * Confirmation suppression fichier
	 */
	if ($action == 'remove_file') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?step=1&file='.urlencode(GETPOST("file")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
	}

	print '<div class="opacitymedium">'.$langs->trans("SelectExportDataSet").'</div><br>';

	// Affiche les modules d'exports
	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Module").'</td>';
	print '<td>'.$langs->trans("ExportableDatas").'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	if (count($array_export_module)) {
		foreach ($array_export_label as $key => $value) {
			print '<tr class="oddeven"><td nospan="nospan">';
			print $array_export_module['name'];
			print '</td><td>';
			$entity = preg_replace('/:.*$/', '', $array_export_icon[0]);
			$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
			$label = $value;
			//print $value.'-'.$icon.'-'.$label."<br>";
			print img_object($array_export_module['name'], $entityicon).' ';
			print $label;
			print '</td><td class="right">';
			if ($user->rights->feuilledetemps->feuilledetemps->export) {
				print '<a href="'.DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=2&export_code='.$key.'">'.img_picto($langs->trans("NewExport"), 'next', 'class="fa-15"').'</a>';
			} else {
				print '<span class="opacitymedium">'.$langs->trans("NotEnoughPermissions").'</span>';
			}
			print '</td></tr>';
		}
	} else {
		print '<tr><td class="oddeven" colspan="3">'.$langs->trans("NoExportableData").'</td></tr>';
	}
	print '</table></div><br><br>';


	// List of available export formats
	$htmltabloflibs = '<table class="noborder centpercent">';
	$htmltabloflibs .= '<tr class="liste_titre">';
	$htmltabloflibs .= '<td>'.$langs->trans("AvailableFormats").'</td>';
	$htmltabloflibs .= '<td>'.$langs->trans("LibraryUsed").'</td>';
	$htmltabloflibs .= '<td class="right">'.$langs->trans("LibraryVersion").'</td>';
	$htmltabloflibs .= '</tr>'."\n";

	$liste = $objmodelexport->listOfAvailableExportFormat($db);
	$liste2 = $liste;
	$listeall = $liste;
	foreach ($listeall as $key => $val) {
		if (preg_match('/__\(Disabled\)__/', $listeall[$key])) {
			$listeall[$key] = preg_replace('/__\(Disabled\)__/', '('.$langs->transnoentitiesnoconv("Disabled").')', $listeall[$key]);
			unset($liste[$key]);
			unset($liste2[$key]);
		}
		if($key != 'excel2007') {
			unset($liste[$key]);
		}

		$htmltabloflibs .= '<tr class="oddeven">';
		$htmltabloflibs .= '<td>'.img_picto_common($key, $objmodelexport->getPictoForKey($key)).' ';
		$text = $objmodelexport->getDriverDescForKey($key);
		$label = $listeall[$key];
		$htmltabloflibs .= $form->textwithpicto($label, $text).'</td>';
		$htmltabloflibs .= '<td>'.$objmodelexport->getLibLabelForKey($key).'</td>';
		$htmltabloflibs .= '<td class="right">'.$objmodelexport->getLibVersionForKey($key).'</td>';
		$htmltabloflibs .= '</tr>'."\n";
	}
	$htmltabloflibs .= '</table>';

	if (!is_dir($conf->export->dir_temp)) {
		dol_mkdir($conf->export->dir_temp);
	}

	print '<div class="fichecenter">';
		print '<div class="fichethirdleft">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("Export Feuille de temps");
		print '</th>';
		print '</tr>';
		print '<tr>';
		print '<td align="center" colspan="2">';
			print '<form name="exportFeuilleDeTemps" id="exportFeuilleDeTemps" action="'.$_SERVER["PHP_SELF"].'?step=1&action=buildalldoc&token='.newToken().'" method="POST">';
			print $htmlother->select_month((GETPOST('exportdate_month') ? GETPOST('exportdate_month') : date("m")), 'exportdate_month', 0, 1, 'minwidth50 valignmiddle', false);
			print ' ';
			print $htmlother->select_year(GETPOST('exportdate_year'), 'exportdate_year', 0, 1, 5, 0, 0, '', 'minwidth50 maxwidth75imp valignmiddle', true);
			// Show existing generated documents
			// NB: La fonction show_documents rescanne les modules qd genallowed=1, sinon prend $liste
			print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"].'?step=1', $liste, 1, (!empty($_POST['model']) ? $_POST['model'] : 'csv'), 1, 1, 0, 0, 0, '', '<input class="butAction" type="submit" value="'.$langs->trans('Export').'">', '', '', '', null, 0, 'remove_file', '', '^export_(analytique_pourcentage|donnees_variables|absences|heure_sup).*$');
			print '</form>';
		print '</td>';
		print '</tr>';
		print '</table></div></div>';

		if($conf->donneesrh->enabled) {
			print '<div class="fichetwothirdright">';
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th colspan="2">';
			print $langs->trans("Export Notes pour la compta");
			print '</th>';
			print '</tr>';
			print '<tr>';
			print '<td align="center" colspan="2">';
				print '<form name="exportObservationCompta" id="exportObservationCompta" action="'.$_SERVER["PHP_SELF"].'?step=1&action=builddoccompta&token='.newToken().'" method="POST">';
				print $htmlother->select_month((GETPOST('exportdate_startmonth') ? GETPOST('exportdate_startmonth') : date("m")), 'exportdate_startmonth', 0, 1, 'minwidth50 valignmiddle', true);
				print ' ';
				print $htmlother->select_year(GETPOST('exportdate_startyear'), 'exportdate_startyear', 0, 1, 5, 0, 0, '', 'minwidth50 maxwidth75imp valignmiddle', true);
				print ' - ';
				print $htmlother->select_month((GETPOST('exportdate_endmonth') ? GETPOST('exportdate_endmonth') : date("m")), 'exportdate_endmonth', 1, 1, 'minwidth50 valignmiddle', true);
				print ' ';
				print $htmlother->select_year((GETPOST('exportdate_endyear') ? GETPOST('exportdate_endyear') : date("Y")), 'exportdate_endyear', 1, 1, 5, 0, 0, '', 'minwidth50 maxwidth75imp valignmiddle', true);
				// Show existing generated documents
				// NB: La fonction show_documents rescanne les modules qd genallowed=1, sinon prend $liste
				print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"].'?step=1', $liste2, 1, (!empty($_POST['model']) ? $_POST['model'] : 'csv'), 1, 1, 0, 0, 0, '', '<input class="butAction" type="submit" value="'.$langs->trans('Export').'">', '', '', '', null, 0, 'remove_file', '', '^export_ObservationCompta\..*$');
				print '</form>';
			print '</td>';
			print '</tr>';
			print '</table></div></div>';
		}
	print '</div>';

	print '</form>';

	print '</div>';
}

if ($step == 2 && $datatoexport) {
	llxHeader('', $langs->trans("Export Feuille de temps"));

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table width="100%" class="border tableforfield">';

	// Module
	print '<tr><td class="titlefield">'.$langs->trans("Module").'</td>';
	print '<td>';
	print $langs->transnoentitiesnoconv("ModuleFeuilleDeTempsName");
	print '</td></tr>';

	// Lot de donnees a exporter
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	print img_object("FeuilleDeTemps", "timesheet_16@feuilledetemps").' ';
	print $array_export_label[0];
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	print '<br>';

	// Combo list of export models
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="select_model">';
	print '<input type="hidden" name="step" value="2">';
	print '<input type="hidden" name="datatoexport" value="'.$datatoexport.'">';
	print '<div class="valignmiddle marginbottomonly">';
	print '<span class="opacitymedium">'.$langs->trans("SelectExportFields").'</span> ';
	$htmlother->select_export_model($exportmodelid, 'exportmodelid', $datatoexport, 1, $user->id);
	print ' ';
	print '<input type="submit" class="button" value="'.$langs->trans("Select").'">';
	print '</div>';
	print '</form>';


	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	print '<td>'.$langs->trans("ExportableFields").'</td>';
	print '<td width="100" class="center">';
	print '<a class="liste_titre commonlink" title='.$langs->trans("All").' alt='.$langs->trans("All").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field=all">'.$langs->trans("All")."</a>";
	print ' / ';
	print '<a class="liste_titre commonlink" title='.$langs->trans("None").' alt='.$langs->trans("None").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field=all">'.$langs->trans("None")."</a>";
	print '</td>';
	print '<td width="44%">'.$langs->trans("ExportedFields").'</td>';
	print '</tr>';

	// Champs exportables
	$fieldsarray = $array_export_fields[0];
	// Select request if all fields are selected
	//$sqlmaxforexport = $objexport->build_sql(0, array(), array());

	//    $this->array_export_module[0]=$module;
	//    $this->array_export_code[0]=$module->export_code[$r];
	//    $this->array_export_label[0]=$module->export_label[$r];
	//    $this->array_export_sql[0]=$module->export_sql[$r];
	//    $this->array_export_fields[0]=$module->export_fields_array[$r];
	//    $this->array_export_entities[0]=$module->export_fields_entities[$r];
	//    $this->array_export_alias[0]=$module->export_fields_alias[$r];

	$i = 0;

	foreach ($fieldsarray as $code => $label) {
		print '<tr class="oddeven">';

		$i++;

		$entity = (!empty($array_export_entities[0][$code]) ? $array_export_entities[0][$code] : $array_export_icon[0]);
		$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
		$entitylang = (!empty($entitytolang[$entity]) ? $entitytolang[$entity] : $entity);

		print '<td class="nowrap">';
		// If value of entityicon=entitylang='icon:Label'
		//print $code.'-'.$label.'-'.$entity;

		$tmparray = explode(':', $entityicon);
		if (count($tmparray) >= 2) {
			$entityicon = $tmparray[0];
			$entitylang = $tmparray[1];
		}
		print img_object('', $entityicon).' '.$langs->trans($entitylang);
		print '</td>';

		$text = (empty($array_export_special[0][$code]) ? '' : '<i>').$langs->trans($label).(empty($array_export_special[0][$code]) ? '' : '</i>');

		$tablename = $array_tablename[0][$code];
		$htmltext = '<b>'.$langs->trans("Name").":</b> ".$text.'<br>';
		if (!empty($array_export_special[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("ComputedField")." -> ".$langs->trans("Method")." :</b> ".$array_export_special[0][$code]."<br>";
		} else {
			$htmltext .= '<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./', '', $code)."<br>";
		}
		if (!empty($array_export_examplevalues[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("SourceExample").':</b> '.$array_export_examplevalues[0][$code].'<br>';
		}
		if (!empty($array_export_TypeFields[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Type").':</b> '.$array_export_TypeFields[0][$code].'<br>';
		}
		if (!empty($array_export_help[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Help").':</b> '.$langs->trans($array_export_help[0][$code]).'<br>';
		}

		if (isset($array_selected[$code]) && $array_selected[$code]) {
			// Selected fields
			print '<td>&nbsp;</td>';
			print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field='.$code.'">'.img_left('default', 0, 'style="max-width: 20px"').'</a></td>';
			print '<td>';
			//print $text.'-'.$htmltext."<br>";
			print $form->textwithpicto($text, $htmltext);
			//print ' ('.$code.')';
			print '</td>';
		} else {
			// Fields not selected
			print '<td>';
			//print $text.'-'.$htmltext."<br>";
			print $form->textwithpicto($text, $htmltext);
			//print ' ('.$code.')';
			print '</td>';
			print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field='.$code.'">'.img_right('default', 0, 'style="max-width: 20px"').'</a></td>';
			print '<td>&nbsp;</td>';
		}

		print '</tr>';
	}

	print '</table>';
	print '</div>';

	/*
	* Action bar
	*/
	print '<div class="tabsAction tabsActionNoBottom">';

	if (count($array_selected)) {
		// If filters exist
		if ($usefilters && isset($array_export_TypeFields[0]) && is_array($array_export_TypeFields[0])) {
			print '<a class="butAction" href="export.php?step=3&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
		} else {
			print '<a class="butAction" href="export.php?step=4&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
		}
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("SelectAtLeastOneField")).'">'.$langs->trans("NextStep").'</a>';
	}

	print '</div>';
	
}

if ($step == 3 && $datatoexport) {
	if (count($array_selected) < 1) {      // This occurs when going back to page after sessecion expired
		// Switch to step 2
		header("Location: ".DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=2&datatoexport='.$datatoexport);
		exit;
	}

	llxHeader('', $langs->trans("Export Feuille de temps"));

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=3&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 3";
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table width="100%" class="border tableforfield">';

	// Module
	print '<tr><td class="titlefield">'.$langs->trans("Module").'</td>';
	print '<td>';
	print $langs->transnoentitiesnoconv("ModuleFeuilleDeTempsName");
	print '</td></tr>';

	// Lot de donnees a exporter
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	print img_object("FeuilleDeTemps", "timesheet_16@feuilledetemps").' ';
	print $array_export_label[0];
	print '</td></tr>';

	// Nbre champs exportes
	print '<tr><td>'.$langs->trans("ExportedFields").'</td>';
	$list = '';
	foreach ($array_selected as $code => $value) {
		$list .= (!empty($list) ? ', ' : '');
		$list .= (isset($array_export_fields[0][$code]) ? $langs->trans($array_export_fields[0][$code]) : '');
	}
	print '<td>'.$list.'</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';

	// Combo list of export models
	print '<span class="opacitymedium">'.$langs->trans("SelectFilterFields").'</span><br><br>';


	// un formulaire en plus pour recuperer les filtres
	print '<form action="'.$_SERVER["PHP_SELF"].'?step=4&action=submitFormField&datatoexport='.$datatoexport.'" name="FilterField" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	//print '<td>'.$langs->trans("ExportableFields").'</td>';
	//print '<td class="center"></td>';
	print '<td>'.$langs->trans("ExportableFields").'</td>';
	print '<td width="25%">'.$langs->trans("FilteredFieldsValues").'</td>';
	print '</tr>';

	// Champs exportables
	$fieldsarray = $array_export_fields[0];
	// Champs filtrable
	$Typefieldsarray = $array_export_TypeFields[0];
	// valeur des filtres
	$ValueFiltersarray = (!empty($array_export_FilterValue[0]) ? $array_export_FilterValue[0] : '');
	// Select request if all fields are selected
	//$sqlmaxforexport = $objexport->build_sql(0, array(), array());

	$i = 0;
	// on boucle sur les champs
	foreach ($fieldsarray as $code => $label) {
		print '<tr class="oddeven">';

		$i++;
		$entity = (!empty($array_export_entities[0][$code]) ? $array_export_entities[0][$code] : $array_export_icon[0]);
		$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
		$entitylang = (!empty($entitytolang[$entity]) ? $entitytolang[$entity] : $entity);

		print '<td class="nowrap">';
		// If value of entityicon=entitylang='icon:Label'
		$tmparray = explode(':', $entityicon);
		if (count($tmparray) >= 2) {
			$entityicon = $tmparray[0];
			$entitylang = $tmparray[1];
		}
		print img_object('', $entityicon).' '.$langs->trans($entitylang);
		print '</td>';

		// Field name
		$labelName = (!empty($fieldsarray[$code]) ? $fieldsarray[$code] : '');
		$ValueFilter = (!empty($array_filtervalue[$code]) ? $array_filtervalue[$code] : '');
		$text = (empty($array_export_special[0][$code]) ? '' : '<i>').$langs->trans($labelName).(empty($array_export_special[0][$code]) ? '' : '</i>');

		$tablename = $array_tablename[0][$code];
		$htmltext = '<b>'.$langs->trans("Name").':</b> '.$text.'<br>';
		if (!empty($array_export_special[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("ComputedField")." -> ".$langs->trans("Method")." :</b> ".$array_export_special[0][$code]."<br>";
		} else {
			$htmltext .= '<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./', '', $code)."<br>";
		}
		if (!empty($array_export_examplevalues[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("SourceExample").':</b> '.$array_export_examplevalues[0][$code].'<br>';
		}
		if (!empty($array_export_TypeFields[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Type").':</b> '.$array_export_TypeFields[0][$code].'<br>';
		}
		if (!empty($array_export_help[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Help").':</b> '.$langs->trans($array_export_help[0][$code]).'<br>';
		}

		print '<td>';
		print $form->textwithpicto($text, $htmltext);
		print '</td>';

		// Filter value
		print '<td>';
		if (!empty($Typefieldsarray[$code])) {	// Example: Text, List:c_country:label:rowid, Number, Boolean
			$szInfoFiltre = $objexport->genDocFilter($Typefieldsarray[$code]);
			if ($szInfoFiltre) {	// Is there an info help for this filter ?
				$tmp = $objexport->build_filterField($Typefieldsarray[$code], $code, $ValueFilter);
				if($code == "g.status") {
					$szInfoFiltre = "0 -> Non renseigné<br>1 -> Renseigné<br>2 -> Validé";
				}
				print $form->textwithpicto($tmp, $szInfoFiltre);
			} else {
				print $objexport->build_filterField($Typefieldsarray[$code], $code, $ValueFilter);
			}
		}
		print '</td>';

		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '</div>';

	/*
	 * Action bar
	 */
	print '<div class="tabsAction tabsActionNoBottom">';
	// il n'est pas obligatoire de filtrer les champs
	print '<a class="butAction" href="javascript:FilterField.submit();">'.$langs->trans("NextStep").'</a>';
	print '</div>';
}

if ($step == 4 && $datatoexport) {
	if (count($array_selected) < 1) {     // This occurs when going back to page after sessecion expired
		// Switch to step 3
		header("Location: ".DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=3&datatoexport='.$datatoexport);
		exit;
	}

	asort($array_selected);

	llxHeader('', $langs->trans("Export Feuille de temps"));

	$stepoffset = 0;
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	// If filters exist
	if ($usefilters && isset($array_export_TypeFields[0]) && is_array($array_export_TypeFields[0])) {
		$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=3&datatoexport='.$datatoexport;
		$head[$h][1] = $langs->trans("Step")." 3";
		$h++;
		$stepoffset++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=4&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." ".(3 + $stepoffset);
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table width="100%" class="border tableforfield">';

	// Module
	print '<tr><td class="titlefield tableforfield">'.$langs->trans("Module").'</td>';
	print '<td>';
	print $langs->transnoentitiesnoconv("ModuleFeuilleDeTempsName");
	print '</td></tr>';

	// Lot de donnees a exporter
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	print img_object("FeuilleDeTemps", "timesheet_16@feuilledetemps").' ';
	print $array_export_label[0];
	print '</td></tr>';

	// List of exported fields
	print '<tr><td>'.$langs->trans("ExportedFields").'</td>';
	$list = '';
	foreach ($array_selected as $code => $value) {
		$list .= (!empty($list) ? ', ' : '');
		$list .= $langs->trans($array_export_fields[0][$code]);
	}
	print '<td>'.$list.'</td>';
	print '</tr>';

	// List of filtered fiels
	if (isset($array_export_TypeFields[0]) && is_array($array_export_TypeFields[0])) {
		print '<tr><td>'.$langs->trans("FilteredFields").'</td>';
		$list = '';
		if (!empty($array_filtervalue)) {
			foreach ($array_filtervalue as $code => $value) {
				if (isset($array_export_fields[0][$code])) {
					$list .= ($list ? ', ' : '');
					if (isset($array_filtervalue[$code]) && preg_match('/^\s*[<>]/', $array_filtervalue[$code])) {
						$list .= $langs->trans($array_export_fields[0][$code]).(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '');
					} else {
						$list .= $langs->trans($array_export_fields[0][$code])."='".(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '')."'";
					}
				}
			}
		}
		print '<td>'.(!empty($list) ? $list : '<span class="opacitymedium">'.$langs->trans("None").'</span>').'</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '<br>';

	// Select request if all fields are selected
	//$sqlmaxforexport = $objexport->build_sql(0, array(), array());

	print '<div class="marginbottomonly"><span class="opacitymedium">'.$langs->trans("ChooseFieldsOrdersAndTitle").'</span></div>';

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	print '<td>'.$langs->trans("ExportedFields").'</td>';
	print '<td class="right" colspan="2">'.$langs->trans("Position").'</td>';
	//print '<td>&nbsp;</td>';
	//print '<td>'.$langs->trans("FieldsTitle").'</td>';
	print '</tr>';

	foreach ($array_selected as $code => $value) {
		print '<tr class="oddeven">';

		$entity = (!empty($array_export_entities[0][$code]) ? $array_export_entities[0][$code] : $array_export_icon[0]);
		$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
		$entitylang = (!empty($entitytolang[$entity]) ? $entitytolang[$entity] : $entity);

		print '<td class="nowrap">';
		// If value of entityicon=entitylang='icon:Label'
		$tmparray = explode(':', $entityicon);
		if (count($tmparray) >= 2) {
			$entityicon = $tmparray[0];
			$entitylang = $tmparray[1];
		}
		print img_object('', $entityicon).' '.$langs->trans($entitylang);
		print '</td>';

		$labelName = $array_export_fields[0][$code];

		$text = (empty($array_export_special[0][$code]) ? '' : '<i>').$langs->trans($labelName).(empty($array_export_special[0][$code]) ? '' : '</i>');

		$tablename = $array_tablename[0][$code];
		$htmltext = '<b>'.$langs->trans("Name").':</b> '.$text.'<br>';
		if (!empty($array_export_special[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("ComputedField")." -> ".$langs->trans("Method")." :</b> ".$array_export_special[0][$code]."<br>";
		} else {
			$htmltext .= '<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./', '', $code)."<br>";
		}
		if (!empty($array_export_examplevalues[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("SourceExample").':</b> '.$array_export_examplevalues[0][$code].'<br>';
		}
		if (!empty($array_export_TypeFields[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Type").':</b> '.$array_export_TypeFields[0][$code].'<br>';
		}
		if (!empty($array_export_help[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Help").':</b> '.$langs->trans($array_export_help[0][$code]).'<br>';
		}

		print '<td>';
		print $form->textwithpicto($text, $htmltext);
		//print ' ('.$code.')';
		print '</td>';

		print '<td class="right" width="100">';
		print $value.' ';
		print '</td><td class="center nowraponall" width="40">';
		if ($value < count($array_selected)) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=downfield&field='.$code.'">'.img_down().'</a>';
		}
		if ($value > 1) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=upfield&field='.$code.'">'.img_up().'</a>';
		}
		print '</td>';

		//print '<td>&nbsp;</td>';
		//print '<td>'.$langs->trans($array_export_fields[0][$code]).'</td>';

		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '</div>';

	/*
	 * Action bar
	 */
	print '<div class="tabsAction">';

	if (count($array_selected)) {
		print '<a class="butAction" href="export.php?step='.($step + 1).'&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
	}

	print '</div>';


	// Area for profils export
	if (count($array_selected)) {
		print '<br>';

		print '<div class="marginbottomonly">';
		print '<span class="opacitymedium">'.$langs->trans("SaveExportModel").'</span>';
		print '</div>';

		print '<form class="nocellnopadd" action="export.php" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add_export_model">';
		print '<input type="hidden" name="step" value="'.$step.'">';
		print '<input type="hidden" name="datatoexport" value="'.$datatoexport.'">';
		print '<input type="hidden" name="hexa" value="'.$hexa.'">';

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ExportModelName").'</td>';
		print '<td>'.$langs->trans("Visibility").'</td>';
		print '<td></td>';
		print '</tr>';

		print '<tr class="oddeven">';
		print '<td><input name="export_name" value=""></td>';
		print '<td>';
		$arrayvisibility = array('private'=>$langs->trans("Private"), 'all'=>$langs->trans("Everybody"));
		print $form->selectarray('visibility', $arrayvisibility, 'private');
		print '</td>';
		print '<td class="right">';
		print '<input type="submit" class="button reposition button-save" value="'.$langs->trans("Save").'">';
		print '</td></tr>';

		$tmpuser = new User($db);

		// List of existing export profils
		$sql = "SELECT rowid, label, fk_user, entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."export_model";
		$sql .= " WHERE type = '".$db->escape($datatoexport)."'";
		if (empty($conf->global->EXPORTS_SHARE_MODELS)) {	// EXPORTS_SHARE_MODELS means all templates are visible, whatever is owner.
			$sql .= " AND fk_user IN (0, ".((int) $user->id).")";
		}
		$sql .= " ORDER BY rowid";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven"><td>';
				print $obj->label;
				print '</td>';
				print '<td>';
				if (empty($obj->fk_user)) {
					print $langs->trans("Everybody");
				} else {
					$tmpuser->fetch($obj->fk_user);
					print $tmpuser->getNomUrl(1);
				}
				print '</td>';
				print '<td class="right">';
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=deleteprof&token='.newToken().'&id='.$obj->rowid.'">';
				print img_delete();
				print '</a>';
				print '</tr>';
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		print '</table>';
		print '</div>';

		print '</form>';
	}
}

if ($step == 5 && $datatoexport) {
	if (count($array_selected) < 1) {      // This occurs when going back to page after sessecion expired
		// Switch to step 4
		header("Location: ".DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=4&datatoexport='.$datatoexport);
		exit;
	}

	asort($array_selected);

	llxHeader('', $langs->trans("Export Feuille de temps"));

	$h = 0;
	$stepoffset = 0;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	// si le filtrage est parametre pour l'export ou pas
	if ($usefilters && isset($array_export_TypeFields[0]) && is_array($array_export_TypeFields[0])) {
		$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=3&datatoexport='.$datatoexport;
		$head[$h][1] = $langs->trans("Step")." 3";
		$h++;
		$stepoffset++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=4&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." ".(3 + $stepoffset);
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/feuilledetemps/export.php?step=5&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." ".(4 + $stepoffset);
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	/*
	 * Confirmation suppression fichier
	 */
	if ($action == 'remove_file') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?step=5&datatoexport='.$datatoexport.'&file='.urlencode(GETPOST("file")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
	}

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table width="100%" class="border tableforfield">';

	// Module
	print '<tr><td class="titlefield">'.$langs->trans("Module").'</td>';
	print '<td>';
	print $langs->transnoentitiesnoconv("ModuleFeuilleDeTempsName");
	print '</td></tr>';

	// Dataset to export
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	print img_object("FeuilleDeTemps", "timesheet_16@feuilledetemps").' ';
	print $array_export_label[0];
	print '</td></tr>';

	// List of exported fields
	print '<tr><td>'.$langs->trans("ExportedFields").'</td>';
	$list = '';
	foreach ($array_selected as $code => $label) {
		$list .= (!empty($list) ? ', ' : '');
		$list .= $langs->trans($array_export_fields[0][$code]);
	}
	print '<td>'.$list.'</td></tr>';

	// List of filtered fields
	if (isset($array_export_TypeFields[0]) && is_array($array_export_TypeFields[0])) {
		print '<tr><td>'.$langs->trans("FilteredFields").'</td>';
		$list = '';
		if (!empty($array_filtervalue)) {
			foreach ($array_filtervalue as $code => $value) {
				if (isset($array_export_fields[0][$code])) {
					$list .= ($list ? ', ' : '');
					if (isset($array_filtervalue[$code]) && preg_match('/^\s*[<>]/', $array_filtervalue[$code])) {
						$list .= $langs->trans($array_export_fields[0][$code]).(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '');
					} else {
						$list .= $langs->trans($array_export_fields[0][$code])."='".(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '')."'";
					}
				}
			}
		}
		print '<td>'.(!empty($list) ? $list : '<span class="opacitymedium">'.$langs->trans("None").'</span>').'</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '<br>';

	// List of available export formats
	$htmltabloflibs = '<table class="noborder centpercent">';
	$htmltabloflibs .= '<tr class="liste_titre">';
	$htmltabloflibs .= '<td>'.$langs->trans("AvailableFormats").'</td>';
	$htmltabloflibs .= '<td>'.$langs->trans("LibraryUsed").'</td>';
	$htmltabloflibs .= '<td class="right">'.$langs->trans("LibraryVersion").'</td>';
	$htmltabloflibs .= '</tr>'."\n";

	$liste = $objmodelexport->listOfAvailableExportFormat($db);
	$listeall = $liste;
	foreach ($listeall as $key => $val) {
		if (preg_match('/__\(Disabled\)__/', $listeall[$key])) {
			$listeall[$key] = preg_replace('/__\(Disabled\)__/', '('.$langs->transnoentitiesnoconv("Disabled").')', $listeall[$key]);
			unset($liste[$key]);
		}

		$htmltabloflibs .= '<tr class="oddeven">';
		$htmltabloflibs .= '<td>'.img_picto_common($key, $objmodelexport->getPictoForKey($key)).' ';
		$text = $objmodelexport->getDriverDescForKey($key);
		$label = $listeall[$key];
		$htmltabloflibs .= $form->textwithpicto($label, $text).'</td>';
		$htmltabloflibs .= '<td>'.$objmodelexport->getLibLabelForKey($key).'</td>';
		$htmltabloflibs .= '<td class="right">'.$objmodelexport->getLibVersionForKey($key).'</td>';
		$htmltabloflibs .= '</tr>'."\n";
	}
	$htmltabloflibs .= '</table>';

	print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("NowClickToGenerateToBuildExportFile"), $htmltabloflibs, 1, 'help', '', 0, 2, 'helphonformat').'</span>';
	//print $htmltabloflibs;
	print '<br>';

	print '</div>';


	if ($sqlusedforexport && $user->admin) {
		print info_admin($langs->trans("SQLUsedForExport").':<br> '.$sqlusedforexport, 0, 0, 1, '', 'TechnicalInformation');
	}


	if (!is_dir($conf->export->dir_temp)) {
		dol_mkdir($conf->export->dir_temp);
	}

	// Show existing generated documents
	// NB: La fonction show_documents rescanne les modules qd genallowed=1, sinon prend $liste
	print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"].'?step=5&datatoexport='.$datatoexport, $liste, 1, (!empty($_POST['model']) ? $_POST['model'] : 'csv'), 1, 1, 0, 0, 0, '', 'none', '', '', '');
}

llxFooter();

$db->close();

exit; // don't know why but apache hangs with php 5.3.10-1ubuntu3.12 and apache 2.2.2 if i remove this exit or replace with return


/**
 * 	Return table name of an alias. For this, we look for the "tablename as alias" in sql string.
 *
 * 	@param	string	$code				Alias.Fieldname
 * 	@param	string	$sqlmaxforexport	SQL request to parse
 * 	@return	string						Table name of field
 */
function getablenamefromfield($code, $sqlmaxforexport)
{
	$alias = preg_replace('/\.(.*)$/i', '', $code); // Keep only 'Alias' and remove '.Fieldname'
	$regexstring = '/([a-zA-Z_]+) as '.preg_quote($alias).'[, \)]/i';

	$newsql = $sqlmaxforexport;
	$newsql = preg_replace('/^(.*) FROM /i', '', $newsql); // Remove part before the FROM
	$newsql = preg_replace('/WHERE (.*)$/i', '', $newsql); // Remove part after the WHERE so we have now only list of table aliases in a string. We must keep the ' ' before WHERE

	if (preg_match($regexstring, $newsql, $reg)) {
		return $reg[1]; // The tablename
	} else {
		return '';
	}
}
