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
	$permissiontoread = $user->hasRight('addoncomm', 'box_indicateur_ca', 'read');
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
if (!isModEnabled("addoncomm")) {
	accessforbidden('Module addoncomm not enabled');
}
if (!$permissiontoread) accessforbidden();


/**
 * actions 
 */

/*
 * View
 */

dol_syslog("Call ajax addoncomm/ajax/linedolgraph_data.php");
// print '<!DOCTYPE html>';
top_httphead('application/json');

global $db;
// Get data for graph
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/linedolgraph.class.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
$dolclass = new LineDolGraph($db);
//data for feltring by date
$now = dol_now();

$date_evolution_startmonth = GETPOST('start_evolution_datemonth', 'int');
$date_evolution_startday = GETPOST('start_evolution_dateday', 'int');
$date_evolution_startyear = GETPOST('start_evolution_dateyear', 'int');
$date_evolution_endmonth = GETPOST('end_evolution_datemonth', 'int');
$date_evolution_endday = GETPOST('end_evolution_dateday', 'int');
$date_evolution_endyear = GETPOST('end_evolution_dateyear', 'int');

$date_evolution_start = dol_mktime(-1, -1, -1, $date_evolution_startmonth, $date_evolution_startday, $date_evolution_startyear);
$date_evolution_end = dol_mktime(-1, -1, -1, $date_evolution_endmonth, $date_evolution_endday, $date_evolution_endyear);

header('Content-Type: application/json');

// Récupérer les dates -les années - envyées par requete Ajax suite à la selection dans la liste déroulante
// $date_evolution_start = GETPOST('startDate');
// $date_evolution_end = GETPOST('endDate');
$startDate = GETPOST('startDate', 'alpha');
$endDate = GETPOST('endDate', 'alpha');

if ($startDate && $endDate) {
    // Convertir les dates en timestamp UNIX
    $startDateTimestamp = strtotime($startDate);
    $endDateTimestamp = strtotime($endDate);

    // Extraire le jour, le mois et l'année de la date de début
    $date_evolution_startday = (int)date('d', $startDateTimestamp);
    $date_evolution_startmonth = (int)date('m', $startDateTimestamp);
    $date_evolution_startyear = (int)date('Y', $startDateTimestamp);

    // Extraire le jour, le mois et l'année de la date de fin
    $date_evolution_endday = (int)date('d', $endDateTimestamp);
    $date_evolution_endmonth = (int)date('m', $endDateTimestamp);
    $date_evolution_endyear = (int)date('Y', $endDateTimestamp);

    // Générer les timestamps avec dol_mktime
    $date_evolution_start = dol_mktime(-1, -1, -1, $date_evolution_startmonth, $date_evolution_startday, $date_evolution_startyear);
    $date_evolution_end = dol_mktime(-1, -1, -1, $date_evolution_endmonth, $date_evolution_endday, $date_evolution_endyear);
}


if (empty($date_evolution_start)) {
	$date_evolution_start = dol_get_first_day($db->idate($now, 'y'));
}

if (empty($date_evolution_end)) {
	$date_evolution_end = $now;
}

	/**
	 * Génération dynamique d'un tableau structuré pour une clé donnée, avec les valeurs associées à chaque agence.
	 * 
	 * @param string $key La clé pour laquelle les données doivent être extraites (par ex. 'CA', 'Dépenses').
	 * @param array $arr Tableau principal contenant les données organisées par date et agence.
	 * @param array $agences Liste des agences.
	 * @param string $date La date pour laquelle les données doivent être extraites.
	 * 
	 * @return array Tableau structuré contenant les valeurs pour chaque agence avec la clé donnée.
	 */
	function generateStructuredArray($key, $arr, $agences, $date)
	{
		$structuredData = []; // Tableau de résultat structuré
	
		foreach ($agences as $agence) {
			if (!empty($agence)) { // Vérifie que l'agence n'est pas nulle ou vide
				// Génère la clé sous le format "clé : agence" et attribue une valeur (ou 0 par défaut)
				$structuredData[$key . ' : ' . $agence] = isset($arr[$date][$agence][$key]) ? $arr[$date][$agence][$key] : 0;
			}
		}

	
		uksort($structuredData, function ($a, $b) {
			// Pour effectuer une comparaison alphabétique pure
			$a = preg_replace('@^(CA|Dépenses) : @', '', $a);
			$b = preg_replace('@^(CA|Dépenses) : @', '', $b);

			return strcasecmp($a, $b); // Comparaison insensible à la casse
		});
		

		return $structuredData;
	}


	function processAgencyData($agenciesData, $soc, $salarySums, &$vals, $typeKey)
	{
		foreach ($agenciesData as $agenceProjCount) {
			$agencecount = $agenceProjCount['agencecount'] ?? '';
			$amount = $agenceProjCount['amount'] ?? 0;
			$date = $agenceProjCount['date'] ?? '';
			$domaine = $agenceProjCount['domaine'] ?? '';
			$agence = $agenceProjCount['agence'] ?? '';

			// Validation des données
			if (empty($agencecount) || $amount <= 0 || empty($date) || empty($domaine)) {
				continue; 
			}

			// Initialisation du total ajusté
			$adjustedRevenue = 0;

			// Les IDs des agences à partir de 'agencecount'
			$idArray = explode(',', $agencecount);

			foreach ($idArray as $idagence) {
				$idagence = (int)$idagence;

				// Récupération des données de l'agence
				if (!$soc->fetch($idagence)) {
					continue; 
				}
				$code = $soc->array_options['options_code'];

				// Ajout au total ajusté si disponible
				if (isset($salarySums[$code]['CA'])) {
					$adjustedRevenue += $salarySums[$code]['CA'];
				}
			}

			// Protection contre la division par zéro
			if ($adjustedRevenue <= 0) {
				$adjustedRevenue = 1;
			}

			// Répartition des montants entre les agences
			foreach ($idArray as $idProjagence) {
				$idProjagence = (int)$idProjagence;

				// Récupération des données de l'agence
				if (!$soc->fetch($idProjagence)) {
					continue; 
				}
				$code = $soc->array_options['options_code'];

				// Ajout au tableau final
				$vals[$date][$code][$typeKey] += ($salarySums[$code]['CA'] / $adjustedRevenue) * $amount;
			}
		}
	}


	/**
	 * Récupèration et organisation des données des agences par date et type (CA, Dépenses, etc.)
	 */
	$agencesData = $dolclass->getAgencesByDomainesValues("Month", $date_evolution_start, $date_evolution_end);

	$agenciesFacFournCount = $dolclass->getSupplierInvoicesByAgencyCount('Month', $date_evolution_start, $date_evolution_end);
	$agenciesCostCount = $dolclass->getProjectByAgencyCount('Month', $date_evolution_start, $date_evolution_end);
	$agenciesFactureCount = $dolclass->getFactureByAgencyCount('Month', $date_evolution_start, $date_evolution_end);
	
	// Initialisation des variables
	$vals = [];
	$arr = [];
	$arrColors = [];
	$agencesList = [];
	$evolDates = [];
	
	foreach ($agencesData as $type => $entries) {
		foreach ($entries as $entry) {
			$agence = $entry['agence'] ?? '';
			$date = $entry['date'] ?? '';
			$domaine= $entry['domaine'] ?? '';
			$amount = $entry['amount'] ?? 0;
			$color = $entry['color_exp'] ?? '#000'; // Couleur par défaut
	
			if ($agence !== '' && $date !== '') {
				switch ($type) {
					case 'salaries':
						$vals[$date][$agence]['salaries'] += $amount;
						$arr[$date][$agence]['salaries'] += $amount;
						// $domaines[$date][$agence][$domaine]['Dépenses'] += $amount;
						$valsForExepense['salaries'][$agence][$domaine] += $amount;
						$arrColors[$agence]['Dépenses'] = $color;
						$evolDates[] = $date;
						break;
	
					case 'invoices':
						$vals[$date][$agence]['CA'] += $amount;
						$arr[$date][$agence]['CA'] += $amount;
						// $domaines[$date][$agence][$domaine]['CA'] += $amount;
						$valsForExepense['invoices'][$agence][$domaine] += $amount;
						$evolDates[] = $date;
						$arrColors[$agence]['CA'] = $entry['color_ca'] ?? '#000';
						$salarySums[$agence]['CA'] += $amount;
						break;
	
					case 'facture_fourn':
						$vals[$date][$agence]['fac_fourn'] += $amount;
						$arr[$date][$agence]['fac_fourn'] += $amount;
						// $domaines[$date][$agence][$domaine]['Dépenses'] += $amount;
						$valsForExepense['fac_fourn'][$agence][$domaine] += $amount;
						$evolDates[] = $date;
						break;
	
					default:
						break;
				}

				
	
				// Ajoute de l'agence à la liste si elle n'y est pas déjà
				if (!in_array($agence, $agencesList)) {
					$agencesList[] = $agence;
				}
			}
		}
		
	}
	

	// Répartition des montants pour les agences multiples en fonction de la proportion du montant total par agence
	$soc = new Societe($db);
	processAgencyData($agenciesCostCount, $soc, $salarySums, $vals, 'salaries');
	processAgencyData($agenciesFacFournCount, $soc, $salarySums, $vals, 'fac_fourn');
	processAgencyData($agenciesFactureCount, $soc, $salarySums, $vals, 'CA');

	
	
	// Calcul des profits, dépenses totales et CA totaux par date et agence
	$totalCA = 0;
	$totalDep = 0;
	$dataprofit = [];
	$dataexp = [];
	
	foreach ($vals as $date => $agences) {
		foreach ($agences as $agence => $values) {
			$salaries = $values['salaries'] ?? 0;
			$facFourn = $values['fac_fourn'] ?? 0;
			$ca = $values['CA'] ?? 0;
	
			$dataprofit[$date][$agence]['Rentabilité'] = $ca - ($salaries + $facFourn);
			$dataexp[$date][$agence]['Dépenses'] = $salaries + $facFourn;
	
			$totalDep += $salaries + $facFourn;
			$totalCA += $ca;
		}
	}

	
	
	// Génère les données fusionnées avec les clés "CA" et "Dépenses"
	$mergedData = [];
	
	foreach (array_unique($evolDates) as $date) {
		$mergedData[$date] = array_merge_recursive(
			['date' => $date],
			generateStructuredArray('CA', $arr, $agencesList, $date),
			generateStructuredArray('Dépenses', $dataexp, $agencesList, $date),
			// generateStructuredArray('Rentabilité', $dataprofit, $agencesList, $date)
		);
	}
	
	// Trie les données fusionnées par date
	ksort($mergedData);
	
	// Préparation des séries de données financières pour l'affichage
	$financialEvolDataSeries = [];
	$labels = [];
	
	foreach ($mergedData as $key => $values) {
		if ($key !== '') {
			$financialEvolDataSeries[] = $values;
			$labels = array_keys($values);
		}
	}


	// For total

	// Fonction pour ajouter les informations financières
	function addFinancialData(&$financialsData, $valsForExepense, $agence, $domaine) {
		$salaires = isset($valsForExepense['salaries'][$agence][$domaine])  ? $valsForExepense['salaries'][$agence][$domaine] : 0;
		$facture_fourn = isset($valsForExepense['facture_fourn'][$agence][$domaine]) ? $valsForExepense['facture_fourn'][$agence][$domaine] : 0;
		$invoices = isset($valsForExepense['invoices'][$agence][$domaine]) ? $valsForExepense['invoices'][$agence][$domaine] : 0;
		$financialsData[] = array(
			'agence' => $agence, 
			'domaine' => $domaine, 
			$invoices, 
			$salaires + $facture_fourn
		);
	}

	// Fonction pour construire les informations des dépenses
	function buildExpenseInfo($financialsData, $datacolors) {
		$infoForExpense = [];
		foreach ($financialsData as $value) {
			$infoForExpense[$value['agence'].'_'.$value['domaine']] = [
				'agence' => $value['agence'],
				'domaine' => $value['domaine'],
				'montant1' => (float) round($value[0], 2),
				'montant2' => (float) round($value[1], 2),
				'color' => $datacolors[$value['agence']][$value['domaine']] 
			];
		}
		return $infoForExpense;
	}
	// Finalisation des totaux
	$totauxAgencesFinancial = [];
	// Fonction pour accumuler les totaux
	function accumulateTotals(&$totauxAgencesFinancial, $infoForExpense) {
		foreach ($infoForExpense as $item) {
			$montant1 = (float) round($item['montant1'], 2);
			$montant2 = (float) round($item['montant2'], 2);
			
			// Accumuler les totaux globaux
			$GLOBALS['totalGeneralRevenue'] += $montant1;
			$GLOBALS['totalGeneralExpense'] += $montant2;

			// Ajouter les montants pour l'agence
			$totauxAgencesFinancial[$item['agence']]['montant1'] += $montant1;
			$totauxAgencesFinancial[$item['agence']]['montant2'] += $montant2;
		}
	}

	// Fonction pour trier les totaux des agences
	function sortAgencyTotals(&$totauxAgencesFinancial) {
		uasort($totauxAgencesFinancial, function ($a, $b) {
			return $b['montant1'] <=> $a['montant1'];
		});
	}

	// Utilisation des fonctions (Récursivité)
	$financialsData = [];
	foreach($valsForExepense as $label => $values) {
		foreach($values as $agence => $value) {
			foreach($value as $domaine => $val) {
				addFinancialData($financialsData, $valsForExepense, $agence, $domaine);
			}
		}
	}

	// Calcul des informations des dépenses
	$infoForExpense = buildExpenseInfo($financialsData, $datacolors);

	// Accumulation des totaux
	$totauxAgencesFinancial = [];
	accumulateTotals($totauxAgencesFinancial, $infoForExpense);

	// Tri des totaux par agence
	sortAgencyTotals($totauxAgencesFinancial);

	$soc = new Societe($db);
	foreach ($agenciesFacFournCount as $agenceCount) {
		$idArray = explode(',', $agenceCount['agencecount']);
		$montant = $agenceCount['amount'];
	
		// Calcul du revenu total ajusté pour les agences du domaine courant
		$adjustedRevenue = 0;
		foreach ($idArray as $idagence) {
			$idagence = (int)$idagence;
			$soc->fetch($idagence);
			$code = $soc->array_options['options_code'];
	
			if (isset($totauxAgencesFinancial[$code]['montant1'])) {
				$adjustedRevenue += $totauxAgencesFinancial[$code]['montant1'];
			}
		}
	
		// Protection contre les valeurs nulles ou négatives
		if ($adjustedRevenue <= 0) {
			$adjustedRevenue = 1; // Valeur par défaut pour éviter la division par zéro
		}
	
		// Répartition des montants par agence
		foreach ($idArray as $idagence) {
			$idagence = (int)$idagence;
			$soc->fetch($idagence);
			$code = $soc->array_options['options_code'];
	
			if (isset($totauxAgencesFinancial[$code]['montant1'])) {
				$montant1 = $totauxAgencesFinancial[$code]['montant1'];
				$valsForExepense['facture_fourn'][$code][$agenceCount['domaine']] +=
					($montant1 / $adjustedRevenue) * $montant;
			}
		}
	}
	
	
	foreach ($agenciesCostCount as $agenceProjCount) {
		$idArray1 = explode(',', $agenceProjCount['agencecount']);
		$montant = $agenceProjCount['amount'];
	
		// Calcul du revenu total ajusté pour les agences du domaine courant
		$adjustedRevenue = 0;
		foreach ($idArray1 as $idProjagence) {
			$idProjagence = (int)$idProjagence;
			$soc->fetch($idProjagence);
			$code = $soc->array_options['options_code'];
	
			if (isset($totauxAgencesFinancial[$code]['montant1'])) {
				$adjustedRevenue += $totauxAgencesFinancial[$code]['montant1'];
			}
		}
	
		// Protection contre les valeurs nulles ou négatives
		if ($adjustedRevenue <= 0) {
			$adjustedRevenue = 1; // Valeur par défaut pour éviter la division par zéro
		}
	
		// Répartition des montants par agence
		foreach ($idArray1 as $idProjagence) {
			$idProjagence = (int)$idProjagence;
			$soc->fetch($idProjagence);
			$code = $soc->array_options['options_code'];
	
			if (isset($totauxAgencesFinancial[$code]['montant1'])) {
				$montant1 = $totauxAgencesFinancial[$code]['montant1'];
				$valsForExepense['salaries'][$code][$agenceProjCount['domaine']] +=
					($montant1 / $adjustedRevenue) * $montant;
			}
		}
	}
	
	
	foreach ($agenciesFactureCount as $agenceFacCount) {
		$idArray2 = explode(',', $agenceFacCount['agencecount']);
		$montant = $agenceFacCount['amount'];
	
		// Calcul du revenu total ajusté pour les agences du domaine courant
		$adjustedRevenue = 0;
		foreach ($idArray2 as $idFacagence) {
			$idFacagence = (int)$idFacagence;
			$soc->fetch($idFacagence);
			$code = $soc->array_options['options_code'];
	
			if (isset($totauxAgencesFinancial[$code]['montant1'])) {
				$adjustedRevenue += $totauxAgencesFinancial[$code]['montant1'];
			}
		}
	
		// Protection contre les valeurs nulles ou négatives
		if ($adjustedRevenue <= 0) {
			$adjustedRevenue = 1; // Valeur par défaut pour éviter la division par zéro
		}
	
		// Répartition des montants par agence
		foreach ($idArray2 as $idFacagence) {
			$idFacagence = (int)$idFacagence;
			$soc->fetch($idFacagence);
			$code = $soc->array_options['options_code'];
	
			if (isset($totauxAgencesFinancial[$code]['montant1'])) {
				$montant1 = $totauxAgencesFinancial[$code]['montant1'];
				$valsForExepense['invoices'][$code][$agenceFacCount['domaine']] +=
					($montant1 / $adjustedRevenue) * $montant;
			}
		}
	}


	// Utilisation des fonctions (Récursivité)
	$financialsData = [];
	foreach($valsForExepense as $label => $values) {
		foreach($values as $agence => $value) {
			foreach($value as $domaine => $val) {
				addFinancialData($financialsData, $valsForExepense, $agence, $domaine);
			}
		}
	}

	// Recalcul des informations des dépenses
	$infoForExpense = buildExpenseInfo($financialsData, $datacolors);
	$financialdataseries = buildExpenseInfo($financialsData, $datacolors);

	accumulateTotals($totauxAgencesFinancial, $infoForExpense);

	// Tri final
	sortAgencyTotals($totauxAgencesFinancial);

	$financialDomainIndex = 0; // Initialisation de l'indice du domaine
	$totalFinancials = count($infoForExpense); // Nombre total de domaines
	// Tri du tableau par agence
	usort($financialdataseries, function($a, $b) {
		// Tri d'abord par 'montant1' en ordre décroissant
		$financialComparison = $b['montant1'] <=> $a['montant1'];
		if ($financialComparison === 0) {
			// Si montant1 est identique, on trie par 'montant2' en ordre décroissant
			$financialComparison = $b['montant2'] <=> $a['montant2'];
			if ($financialComparison === 0) {
				// Si montant1 et montant2 sont identiques, on trie par 'agence' en ordre alphabétique
				$financialComparison = strcmp($a['agence'], $b['agence']);
				if ($financialComparison === 0) {
					// Si les agences sont identiques, on trie par 'domaine' en ordre alphabétique
					return strcmp($a['domaine'], $b['domaine']);
				}
				return $financialComparison;
			}
			return $financialComparison;
		}
		return $financialComparison;
	});

$db->close();

if(empty($financialEvolDataSeries)) {
	$financialEvolDataSeries[] = array("0 : Aucune donnée" => "Aucune donnée");
}
// $json_data_financial = json_encode($financialdataseries);
$json_data_financial_evol = [
	'dataFiananceEvol' => $financialEvolDataSeries,
	'infoForExpense' => $financialdataseries,
	'colors' => $arrColors,
    'message' => 'sent',
    'date' => date('Y-m-d H:i:s')
];
header('Content-Type: application/json');
print json_encode($json_data_financial_evol);
