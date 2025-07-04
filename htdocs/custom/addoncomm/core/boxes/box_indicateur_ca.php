
<?php
/* Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
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
 *	\file       htdocs/core/boxes/box_indcateur_ca.php
 *	\ingroup    comm
 *	\brief      Box to show graph of commercial data by year by integrating D3.js
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
// include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/indicateur.class.php';
//
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/linedolgraph.class.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Class to manage the box to show last orders
 */
class box_indicateur_ca extends ModeleBoxes
{
    public $boxcode = "acdomagences";
    public $boximg = "fa-chart-line";
    public $boxlabel = "CA par agences";

    public $db;
    public $info_box_head = array();
    public $info_box_contents = array();
    public $widgettype = 'graph';

    public function __construct($db, $param)
    {
        global $user;
        $this->db = $db;
    }

    public function loadBox($max = 5)
    {
        global $conf, $langs, $user, $db;

        $this->max = $max;
        $refreshaction = 'reset_'.$this->boxcode;

        // $text = "Analyse du Chiffre d'Affaires des Agences <br><span style='color:red;'> </span>";
        $text = "Analyse du Chiffre d'Affaires des Agences<br>
         <span style='color:#0056b3;'>Suivi des Métriques Clés par Domaine</span>";
        $this->info_box_head = [
            'text' => $text,
            'limit' => dol_strlen($text),
            'graph' => 1,
            'sublink' => '',
            // 'subtext' => $langs->trans("Filter"),
            // 'subpicto' => 'filter.png',
            'subclass' => 'linkobject boxfilter',
            'target' => 'none'
        ];
        // Get data for graph
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/linedolgraph.class.php';
$dolclass = new LineDolGraph($db);
// /data for feltring by date
$now = dol_now();
$date_ac_startmonth = GETPOST('start_ac_datemonth', 'int');
$date_ac_startday = GETPOST('start_ac_dateday', 'int');
$date_ac_startyear = GETPOST('start_ac_dateyear', 'int');
$date_ac_endmonth = GETPOST('end_ac_datemonth', 'int');
$date_ac_endday = GETPOST('end_ac_dateday', 'int');
$date_ac_endyear = GETPOST('end_ac_dateyear', 'int');

$date_ac_start = dol_mktime(-1, -1, -1, $date_ac_startmonth, $date_ac_startday, $date_ac_startyear);
$date_ac_end = dol_mktime(-1, -1, -1, $date_ac_endmonth, $date_ac_endday, $date_ac_endyear);

if (empty($date_ac_start)) {
	$date_ac_start = dol_get_first_day($db->idate($now, 'y'));
}

if (empty($date_ac_end)) {
	$date_ac_end = $now;
}
$agenceDataDomain = $dolclass->getAgencesByDomainesValues('Year', $date_ac_start, $date_ac_end);
$agenciesCount = $dolclass->getSupplierInvoicesByAgencyCount('Year', $date_ac_start, $date_ac_end);
$agenciesProjCount = $dolclass->getProjectByAgencyCount('Year', $date_ac_start, $date_ac_end);
// Get colors for CA
$agenciesColors = $dolclass->getAgenceByDomainColors();

// Fonction pour compléter les domaines manquants
function completeDomains($agenciesColors) {
    // Obtenir la liste complète des domaines de référence (première agence)
    $referenceDomains = array_keys(reset($agenciesColors));
    $referenceColors = reset($agenciesColors);

    // Parcourir chaque agence pour compléter les domaines
    foreach ($agenciesColors as $agency => &$domains) {
        foreach ($referenceDomains as $domain) {
            // Si le domaine manque, ajouter avec la couleur de l'agence précédente
            if (!isset($domains[$domain])) {
                $domains[$domain] = $referenceColors[$domain] ?? '#000000'; // Couleur par défaut si non trouvée
            }
        }
    }

    return $agenciesColors;
}

// Compléter les données
$datacolors = completeDomains($agenciesColors);

// Calculate total amounts per agency in 'salaries'
$agenceDomTotals = [];
foreach ($agenceDataDomain as $name => $values) {
	foreach ($values as $expense) {
		if ($name === 'salaries') {
			$agency = $expense['agence'];
			$amount = (float) $expense['amount'];

			if (!isset($agenceDomTotals[$agency])) {
				$agenceDomTotals[$agency] = 0;
			}
			$agenceDomTotals[$agency] += $amount;
		}
	}
}

// Data processing for displaying revenue and expenses.
$valsForExepense = [];
foreach($agenceDataDomain as $name => $values) {
	foreach($values as $expense) {
	   
		switch ($name) {
			case 'salaries':
				// Vérification : si l'agence est définie
				if ($expense['agence'] == null) {
					//  $valsForExepense['salaries']['Non défini'][$expense['domaine'] ?? 'Non défini'] = $expense['amount'];
				} else {
					// Vérification : si l'agence est déjà initialisée
					if (!isset($valsForExepense['salaries'][$expense['agence']])) {
						$valsForExepense['salaries'][$expense['agence']] = []; // Initialise si l'agence n'existe pas
					}
			
					//Vérification : si le domaine est défini, sinon utiliser 'Non défini'
					if ($expense['domaine'] == null) {
						$valsForExepense['salaries'][$expense['agence']]['Non défini'] = $expense['amount'];
					} else {
						$valsForExepense['salaries'][$expense['agence']][$expense['domaine']] = $expense['amount'];
					}
				}
				break;
			case 'invoices':
				if ($expense['agence'] == null) {
					// Si l'agence est non définie, on utilise 'Non défini' pour le domaine
					//  $valsForExepense['invoices']['Non défini'][$expense['domaine'] ?? 'Non défini'] = $expense['amount'];
				} else {
					// pour s'assurer que l'agence est définie
					if (!isset($valsForExepense['invoices'][$expense['agence']])) {
						$valsForExepense['invoices'][$expense['agence']] = []; // Initialise si l'agence n'existe pas
					}
			
					if ($expense['domaine'] == null) {
						// Si le domaine est non défini, on le nomme 'Non défini'
						$valsForExepense['invoices'][$expense['agence']]['Non défini'] = $expense['amount'];
					} else {
						// Ajoute le montant à l'agence et au domaine spécifiés
						$valsForExepense['invoices'][$expense['agence']][$expense['domaine']] = $expense['amount'];
					}
				}
			break;
			case 'facture_fourn':
				// si l'agence est définie
				if ($expense['agence'] == null) {
					//  $valsForExepense['facture_fourn']['Non défini'][$expense['domaine'] ?? 'Non défini'] = $expense['amount'];
				} else {
					// si l'agence est déjà initialisée
					if (!isset($valsForExepense['facture_fourn'][$expense['agence']])) {
						$valsForExepense['facture_fourn'][$expense['agence']] = []; // Initialise si l'agence n'existe pas
					}
			
					// si le domaine est défini, sinon utiliser 'Non défini'
					if ($expense['domaine'] == null) {
						$valsForExepense['facture_fourn'][$expense['agence']]['Non défini'] = $expense['amount'];
					} else {
						$valsForExepense['facture_fourn'][$expense['agence']][$expense['domaine']] = $expense['amount'];
					}
				}
			break;
			// case 'soc':
			// 	strcasecmp($expense['date'], $key) == 0 ? $vals['soc'] = $expense['amount'] : 0; 
			// break;
			// case 'note':
			// 	strcasecmp($expense['date'], $key) == 0 ? $vals['note'] = $expense['amount'] : 0; 
			// break;
			// case 'donation':
			// 	strcasecmp($expense['date'], $key) == 0 ? $vals['donation'] = $expense['amount'] : 0; 
			// break;
			// case 'various':
			// 	strcasecmp($expense['date'], $key) == 0 ? $vals['various'] = $expense['amount'] : 0; 
			// break;
			// case 'loan':
			// 	strcasecmp($expense['date'], $key) == 0 ? $vals['loan'] = $expense['amount'] : 0; 
			// break;
		}
	}
	
}


foreach($valsForExepense as $label => $values) {
	// if($agence != null) {
		foreach($values as $agence => $value) {
			foreach($value as $domaine => $val) {
				$salaires = isset($valsForExepense['salaries'][$agence][$domaine])  ? $valsForExepense['salaries'][$agence][$domaine] : 0;
				$facture_fourn = isset($valsForExepense['facture_fourn'][$agence][$domaine]) ? $valsForExepense['facture_fourn'][$agence][$domaine] : 0;
				
				$financialsData[] = array('agence' => $agence, 'domaine' => $domaine, $valsForExepense['invoices'][$agence][$domaine], $salaires + $facture_fourn); 
			}
		}
	// }
}


// Parcours des données des agences pour extraire les informations
$infoForExpense = [];
foreach ($financialsData as $key => $value) {
	// Construction du tableau avec les informations, toujours avec la clé 'color'
	$infoForExpense[$value['agence'].'_'.$value['domaine']] = [
		'agence' => $value['agence'],
		'domaine' => $value['domaine'],
		'montant1' => (float) $value[0],
		'montant2' => (float) $value[1],
		'color' => $datacolors[$value['agence']][$value['domaine']] 
	];
}


foreach ($infoForExpense as $value) {
	// if (!is_null($value['agence']) && !is_null($value['domaine'])) {
		$financialdataseries[$value['agence'].'_'.$value['domaine']] = [
			'agence' => $value['agence'],
			'domaine' => $value['domaine'],
			'montant1' => round($value['montant1'], 2),
			'montant2' => round($value['montant2'], 2),
			'color' => $value['color']  
		];
	// }
}


$totauxAgencesFinancial = array();
// depense et reccette 
foreach ($infoForExpense as $item) {
	$montant1 = (float)$item['montant1'];
	$montant2 = (float)$item['montant2'];
	
	// Accumuler les totaux globaux
	$totalGeneralRevenue += $montant1;
	$totalGeneralExpense += $montant2;

	// Initialiser les totaux de l'agence si elle n'existe pas encore
	// if (!isset($totauxAgencesFinancial[$item['agence']])) {
	//     $totauxAgencesFinancial[$item['agence']] = ['montant1' => 0, 'montant2' => 0];
	// }

	// Ajouter les montants pour l'agence
	$totauxAgencesFinancial[$item['agence']]['montant1'] += $montant1;
	$totauxAgencesFinancial[$item['agence']]['montant2'] += $montant2;
}



// Trier les agences par montant1
uasort($totauxAgencesFinancial, function ($a, $b) {
	return $b['montant1'] <=> $a['montant1'];
});

$soc = new Societe($db);
// foreach($agenciesCount as $agenceCount) {
// 	$idArray = explode(',', $agenceCount['agencecount']);
    
// 	foreach ($idArray as $idagence) {
// 		$idagence =(int)$idagence;
// 		$soc->fetch($idagence);
// 		$valsForExepense['facture_fourn'][$soc->array_options['options_code']][$agenceCount['domaine']] += ($totauxAgencesFinancial[$soc->array_options['options_code']]['montant1'] / $totalGeneralRevenue) * $agenceCount['amount'];
			
// 	}
// }

foreach ($agenciesCount as $agenceCount) {
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


// foreach($agenciesProjCount as $agenceProjCount) {
// 	$idArray1 = explode(',', $agenceProjCount['agencecount']);
//     // var_dump($idArray1);
//     $montant += $agenceProjCount['amount'];     
    
//     // var_dump($montant);
//     // if(isset($idArray1)) {
//         foreach ($idArray1 as $idProjagence) {
//                 $idProjagence =(int)$idProjagence;
//                 $soc->fetch($idProjagence);
//                 // var_dump(($totauxAgencesFinancial[$soc->array_options['options_code']]['montant1'] / $totalGeneralRevenue) * $montant);
//                 $valsForExepense['salaries'][$soc->array_options['options_code']][$agenceProjCount['domaine']] = ($totauxAgencesFinancial[$soc->array_options['options_code']]['montant1'] / $totalGeneralRevenue) * $montant;
//         }
//     // }
	
// }
foreach ($agenciesProjCount as $agenceProjCount) {
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

if($user->id == 412) {
    // var_dump($valsForExepense);
}

foreach($valsForExepense as $label => $values) {
	// if($agence != null) {
		foreach($values as $agence => $value) {
			foreach($value as $domaine => $val) {
				$salaires = isset($valsForExepense['salaries'][$agence][$domaine])  ? $valsForExepense['salaries'][$agence][$domaine] : 0;
				$facture_fourn = isset($valsForExepense['facture_fourn'][$agence][$domaine]) ? $valsForExepense['facture_fourn'][$agence][$domaine] : 0;
				
				$financialsData[] = array('agence' => $agence, 'domaine' => $domaine, $valsForExepense['invoices'][$agence][$domaine], $salaires + $facture_fourn); 
			}
		}
	// }
}

// Parcours des données des agences pour extraire les informations
$infoForExpense = [];
foreach ($financialsData as $key => $value) {
	// Construction du tableau avec les informations, toujours avec la clé 'color'
	$infoForExpense[$value['agence'].'_'.$value['domaine']] = [
		'agence' => $value['agence'],
		'domaine' => $value['domaine'],
		'montant1' => (float) $value[0],
		'montant2' => (float) $value[1],
		'color' => $datacolors[$value['agence']][$value['domaine']] 
	];
}


foreach ($infoForExpense as $value) {
    
	// if (!is_null($value['agence']) && !is_null($value['domaine'])) {
		$financialdataseries[$value['agence'].'_'.$value['domaine']] = [
			'agence' => $value['agence'],
			'domaine' => $value['domaine'],
			'montant1' => round($value['montant1'], 2),
			'montant2' => round($value['montant2'], 2),
			'color' => $value['color']  
		];
	// }
}


$totauxAgencesFinancial = array();
// depense et reccette 
foreach ($infoForExpense as $item) {
	$montant1 = (float)$item['montant1'];
	$montant2 = (float)$item['montant2'];
	
	// Accumuler les totaux globaux
	$totalGeneralRevenue += $montant1;
	$totalGeneralExpense += $montant2;

	// Initialiser les totaux de l'agence si elle n'existe pas encore
	// if (!isset($totauxAgencesFinancial[$item['agence']])) {
	//     $totauxAgencesFinancial[$item['agence']] = ['montant1' => 0, 'montant2' => 0];
	// }

	// Ajouter les montants pour l'agence
	$totauxAgencesFinancial[$item['agence']]['montant1'] += $montant1;
	$totauxAgencesFinancial[$item['agence']]['montant2'] += $montant2;
}



// Trier les agences par montant1
uasort($totauxAgencesFinancial, function ($a, $b) {
	return $b['montant1'] <=> $a['montant1'];
});



// function addFinancialData(&$financialsData, $valsForExepense, $agence, $domaine) {
//     $salaires = isset($valsForExepense['salaries'][$agence][$domaine])  ? $valsForExepense['salaries'][$agence][$domaine] : 0;
//     $facture_fourn = isset($valsForExepense['facture_fourn'][$agence][$domaine]) ? $valsForExepense['facture_fourn'][$agence][$domaine] : 0;
    
//     $financialsData[] = array(
//         'agence' => $agence, 
//         'domaine' => $domaine, 
//         $valsForExepense['invoices'][$agence][$domaine], 
//         $salaires + $facture_fourn
//     );
// }

// // Fonction pour construire les informations des dépenses
// function buildExpenseInfo($financialsData, $datacolors) {
//     $infoForExpense = [];
//     foreach ($financialsData as $value) {
//         $infoForExpense[$value['agence'].'_'.$value['domaine']] = [
//             'agence' => $value['agence'],
//             'domaine' => $value['domaine'],
//             'montant1' => (float) $value[0],
//             'montant2' => (float) $value[1],
//             'color' => $datacolors[$value['agence']][$value['domaine']] 
//         ];
//     }
//     return $infoForExpense;
// }


// Fonction pour accumuler les totaux
// function accumulateTotals(&$totauxAgencesFinancial, $infoForExpense) {
//     foreach ($infoForExpense as $item) {
//         $montant1 = (float)$item['montant1'];
//         $montant2 = (float)$item['montant2'];
        
//         // Accumuler les totaux globaux
//         $GLOBALS['totalGeneralRevenue'] += $montant1;
//         $GLOBALS['totalGeneralExpense'] += $montant2;

//         // Ajouter les montants pour l'agence
//         $totauxAgencesFinancial[$item['agence']]['montant1'] += $montant1;
//         $totauxAgencesFinancial[$item['agence']]['montant2'] += $montant2;
//     }
// }

// // Fonction pour trier les totaux des agences
// function sortAgencyTotals(&$totauxAgencesFinancial) {
//     uasort($totauxAgencesFinancial, function ($a, $b) {
//         return $b['montant1'] <=> $a['montant1'];
//     });
// }

// // Utilisation des fonctions
// $financialsData = [];
// foreach($valsForExepense as $label => $values) {
//     foreach($values as $agence => $value) {
//         foreach($value as $domaine => $val) {
//             addFinancialData($financialsData, $valsForExepense, $agence, $domaine);
//         }
//     }
// }

// // Calcul des informations des dépenses
// $infoForExpense = buildExpenseInfo($financialsData, $datacolors);

// // Accumulation des totaux
// $totauxAgencesFinancial = [];
// accumulateTotals($totauxAgencesFinancial, $infoForExpense);

// // Tri des totaux par agence
// sortAgencyTotals($totauxAgencesFinancial);

// // Fonction pour calculer les répartitions (facture_fourn ou salaries)
// function calculateExpenseDistribution($soc, $agencyData, &$valsForExepense, $totauxAgencesFinancial, $totalGeneralRevenue, $key) {
//     foreach ($agencyData as $agencyCount) {
//         $idArray = explode(',', $agencyCount['agencecount']);
//         foreach ($idArray as $idAgence) {
//             $idAgence = (int)$idAgence;
//             $soc->fetch($idAgence);
//             $optionCode = $soc->array_options['options_code'];
//             $valsForExepense[$key][$optionCode][$agencyCount['domaine']] = 
//                 ($totauxAgencesFinancial[$optionCode]['montant1'] / $totalGeneralRevenue) * $agencyCount['amount'];
//         }
//     }
// }

// // Initialisation de l'objet Société
// $soc = new Societe($db);

// // Calcul des répartitions pour 'facture_fourn'
// calculateExpenseDistribution(
//     $soc, 
//     $agenciesCount, 
//     $valsForExepense, 
//     $totauxAgencesFinancial, 
//     $totalGeneralRevenue, 
//     'facture_fourn'
// );

// // Calcul des répartitions pour 'salaries'
// calculateExpenseDistribution(
//     $soc, 
//     $agenciesProjCount, 
//     $valsForExepense, 
//     $totauxAgencesFinancial, 
//     $totalGeneralRevenue, 
//     'salaries'
// );


// // Ajout des informations à $financialsData à nouveau
// foreach($valsForExepense as $label => $values) {
//     foreach($values as $agence => $value) {
//         foreach($value as $domaine => $val) {
//             addFinancialData($financialsData, $valsForExepense, $agence, $domaine);
//         }
//     }
// }

// // Recalcul des informations des dépenses
// $infoForExpense = buildExpenseInfo($financialsData, $datacolors);
// $financialdataseries = buildExpenseInfo($financialsData, $datacolors);
// // Finalisation des totaux
// $totauxAgencesFinancial = [];
// accumulateTotals($totauxAgencesFinancial, $infoForExpense);

// // Tri final
// sortAgencyTotals($totauxAgencesFinancial);

include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/js/d3js_graph.js.php';
// $config_js = '/custom/addoncomm/js/d3js_graph/configd3js.js';
// $graph_js = '/custom/addoncomm/js/d3js_graph/d3js_graph.js';

$main_js = '/custom/addoncomm/js/main.js';
// Charger les fichiers JS en tant que modules
// print '<script type="module" src="'.$config_js.'"></script>';
// print '<script type="module" src="'.$graph_js.'"></script>';
// print '<script type="module" src="'.$main_js.'"></script>';

// include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/js/main.js.php';
        
         // Pour plus de sécurité des échanges de données - entre client et serveur
         $token = newToken();

        // Une API interne (utilisant AJAX) est utilisée pour importer les données actualisées depuis le serveur
        // Chargement de D3.js et génération du graphique 
       
        // $output .= '<script src="https://d3js.org/d3.v7.min.js"></script>';
        $output = '';
        $output .= '<script src="https://d3js.org/d3.v7.min.js"></script>';
        $output .= '<script src="/custom/addoncomm/js/main.js"></script>'; 
        $output .= '<script src="https://unpkg.com/d3-geo@2.0.0"></script>';
        $output .= '<div id="chart-container" style="display: flex; flex-direction: column; align-items: center; width: 100%; max-width: 1200px; margin: 0 auto;">';
        $output .= '<div id="legend-container" style="display: grid; grid-template-columns: repeat(4, 1fr); margin-left: 10px;width: 100%;max-height: 600px;"></div>'; // Div pour la légende
        $output .= '<div id="chart" style="width: 100%; height: auto;"></div>'; // Div pour le graphique
        // $output .= '<svg height="0" style="width: 100%;"></svg>';
        $output .= '</div>';
        
        $output .= <<<EOT
            <script>
                // Afficher par défaut le graphique radial
                // createChart(filteredData);
                // displayYearSelection();
                // État pour suivre le type de graphique (radial ou bar inversé)
                // let isRadial = true;
                const dolibarrToken = "' . $token . '";
                
                let previousData = null;  // Stocke les données précédentes

                $(document).ready(function () {
                    // Initialisation : charger le graphique par défaut
                    // fetchData(createChart);
                  
                    // createChartType([], 'pie');
                    // displayYearSelection('pie');
                    // Actualisation automatique toutes les 30 secondes
                    // setInterval(() => {
                    //     fetchData(updateChart);
                    //     // fetchDataEvol(updateChartEvol);
                    // }, 30000);
                });

                // Fonction pour récupérer les données et vérifier les modifications
                function fetchData(callback) {
                    $.ajax({
                        url: '/custom/addoncomm/ajax/financial_year_data.php',
                        type: 'GET',
                        dataType: 'json',
                        cache: false,
                        success: function (data) {
                            console.log("Call AJAX for checking Analyse du Chiffre d\'Affaires des Agences ");

                            // Si la propriété 'dataFianance' existe et n'est pas vide
                            if (!data || !data.dataFianance || data.dataFianance.length === 0) {
                                console.warn("Aucune donnée 'dataFianance' reçue.");
                                return;
                            }

                            // Récupérer la propriété 'dataFianance' et la traiter
                            const newData = data.dataFianance;
                            // Forcer l'appel du callback pour vérifier son fonctionnement
                            callback(newData); 
                            console.log('Data reçu', data);
                            // Comparer les données reçues avec les données précédentes
                            if (!isDataEqual(previousData, newData)) {
                                console.log("Les données ont changé, mise à jour du graphique : Analyse du Chiffre d\'Affaires des Agences.");
                                previousData = newData; // Sauvegarder les données mises à jour
                            } else {
                                console.log("Les données n'ont pas changé, aucune mise à jour nécessaire : Analyse du Chiffre d\'Affaires des Agences.");
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("Erreur AJAX :", error);
                            console.log("Réponse brute :", xhr.responseText);
                        }
                    });
                }

                // Fonction pour comparer deux ensembles de données
                function isDataEqual(data1, data2) {
                    // Si l'une des deux données est nulle, elles ne sont pas égales
                    if (!data1 || !data2) {
                        return false;
                    }

                    // Comparaison des données sous forme de chaînes JSON
                    return JSON.stringify(data1) === JSON.stringify(data2);
                }
                

                // Fonction pour mettre à jour le graphique avec les données reçues
                // function updateChart(data) {
                //     clearChart(); // Nettoie l'affichage avant de redessiner
                //     // Appelle createChartType 
                //     createChartType(data, isRadial ? 'radial' : 'bar');
                // }

               
                // Fonction pour gérer le clic sur le bouton de basculement
                // document.getElementById('toggleButton').addEventListener('click', function () {
                //     isRadial = !isRadial; // Bascule l'état
                
                //     // Change l'icône du bouton en fonction de l'état
                //     const icon = this.querySelector('span');
                //     icon.classList.toggle('fa-toggle-on', !isRadial);
                //     icon.classList.toggle('fa-toggle-off', isRadial);
                
                //     // Actualisatin de graphique avec les données actuelles
                //     fetchData(updateChart); // updateChart comme callback
                // });
                
                // Fonction pour vider les conteneurs de graphique et de légende
                function clearChart() {
                    $('#chart').empty();
                    $('#legend-container').empty();
                }

                let previousDataEvol = null;
                // Fonction pour récupérer les données evolution et vérifier les modifications
                function fetchDataEvol(callback) {
                    $.ajax({
                        url: '/custom/addoncomm/ajax/financial_year_data.php',
                        type: 'GET',
                        dataType: 'json',
                        cache: false,
                        success: function (data) {
                            console.log("Call AJAX for checking Analyse du Chiffre d\'Affaires des Agences ");
                
                            // Si la propriété 'dataFiananceEvol' existe et n'est pas vide
                            if (!data || !data.dataFiananceEvol || data.dataFiananceEvol.length === 0) {
                                console.warn("Aucune donnée 'dataFiananceEvol' reçue.");
                                return;
                            }
                
                            // Récupérer la propriété 'dataFiananceEvol' et la traiter
                            const newDataEvol = data.dataFiananceEvol;
                            // Forcer l'appel du callback pour vérifier son fonctionnement
                            console.log('Data reçu en évolution', data);
                            callback(newDataEvol); 
                        
                            // Comparer les données reçues avec les données précédentes
                            if (!isDataEqual(previousDataEvol, newDataEvol)) {
                                console.log("Les données ont changé, mise à jour du graphique : Analyse de l\'évolution du Chiffre d\'Affaires des Agences.");
                                previousDataEvol = newDataEvol; // Sauvegarder les données mises à jour
                            } else {
                                //console.log("Les données n'ont pas changé, aucune mise à jour nécessaire : Analyse de l\'évolution de Chiffre d\'Affaires des Agences.");
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("Erreur AJAX :", error);
                            console.log("Réponse brute :", xhr.responseText);
                        }
                    });
                }
                
                // Fonction pour comparer deux ensembles de données
                function isDataEqual(data1, data2) {
                    // Si l'une des deux données est nulle, elles ne sont pas égales
                    if (!data1 || !data2) {
                        return false;
                    }
                
                    // Comparaison des données sous forme de chaînes JSON
                    return JSON.stringify(data1) === JSON.stringify(data2);
                }
                
                // function updateChartEvol(data) {
                //     clearChart(); // Nettoie l'affichage avant de redessiner
                //     // Appelle createChartType 
                //     createChartType(data, isRadial ? 'line' : 'pie');
                // }

                let isRadialEvol = true;
                // Fonction pour gérer le clic sur le bouton de basculement
                // document.getElementById('toggleButtonEvol').addEventListener('click', function () {
                //     isRadialEvol = !isRadialEvol;
                //     const icon = this.querySelector('span');
                //     icon.classList.toggle('fa-chart-line', !isRadialEvol);
                //     icon.classList.toggle('fa-spinner', isRadialEvol);

                //     // Actualise le graphique avec les données actuelles
                //     fetchDataEvol(updateChartEvol); // Passe directement updateChart comme callback
                // });
                
              


                let previousData1 = null; // Stocke les données précédentes

                $(document).ready(function () {
                    // fetchData1(); // Chargement des données au chargement de la page
                    
                    // Rechargement des données toutes les 30 secondes
                    setInterval(() => {
                        // fetchData1();
                    }, 30000);
                });

                // Fonction pour récupérer et traiter les données
                // function fetchData1() {
                //     $.ajax({
                //         url: '/custom/addoncomm/ajax/financial_year_data.php?' + new Date().getTime(), // Ajout du cache buster
                //         type: 'GET',
                //         dataType: 'json',
                //         cache: false,
                //         success: function (data) {
                //             console.log("Call AJAX pour les montants : Analyse du Chiffre d\'Affaires des Agences");

                //             // Vérification des données reçues
                //             if (!data || !data.totauxAgencesFinancial) {
                //                 console.warn("Données invalides ou manquantes.");
                //                 return;
                //             }

                //             const newData = data.totauxAgencesFinancial;

                //             // Comparaisn des données actuelles avec les précédentes
                //             if (!isDataEqual1(previousData1, newData)) {
                //                 console.log("Les données ont changé, mise à jour nécessaire des montants pour Analyse du Chiffre d\'Affaires des Agences.");
                                
                //                 // Mettre à jour l'affichage avec les nouvelles données
                //                // updateDisplay(newData);
                              
                //                 // Sauvegarde des nouvelles données pour la prochaine comparaison
                //                 previousData1 = newData;
                //             } else {
                //                 console.log("Les données n'ont pas changé, aucune mise à jour requise des montants pour Analyse du Chiffre d\'Affaires des Agences.");
                //             }
                //         },
                //         error: function (xhr, status, error) {
                //             console.error("Erreur AJAX :", error);
                //             console.log("Réponse brute :", xhr.responseText);
                //         }
                //     });
                // }

                // Fonction pour comparer deux ensembles de données
                // function isDataEqual1(data1, data2) {
                //     if (!data1 || !data2) return false; // Si l'une des deux données est nulle
                //     return JSON.stringify(data1) === JSON.stringify(data2); // Comparaison via JSON
                // }
               
                // Fonction pour mettre à jour l'affichage
                // function updateDisplay(data) {
                //     let totalMontant1Global = 0;
                //     let totalMontant2Global = 0;
                
                //     // Initialiser la sortie HTML
                //     let output = '';

                //     // Calcul totla
                //     Object.keys(data).forEach(function (agence) {
                //         const montant1 = data[agence].montant1 || 0;
                //         const montant2 = data[agence].montant2 || 0;
                    
                //         // Mise à jour des totaux globaux
                //         totalMontant1Global += montant1;
                //         totalMontant2Global += montant2;
                //     });
                
                //     // Parcourir les données pour chaque agence
                //     Object.keys(data).forEach(function (agence) {
                //         const montant1 = data[agence].montant1 || 0;
                //         const montant2 = data[agence].montant2 || 0;
                    
                //         // Calculs pour chaque agence
                //         const agDifference = montant1 - montant2;
                //         const percentageAgDifference = montant1 > 0 ? (agDifference / montant1) * 100 : 0;
                //         // Calcul de la proportion du CA par agence
                //         const proportionCA = totalMontant1Global > 0 ? (montant1 / totalMontant1Global) * 100 : 0;
                //         const proportionDep = totalMontant2Global > 0 ? (montant2 / totalMontant2Global) * 100 : 0;
                    
                //         // Générer la ligne pour chaque agence
                //         output += '<tr class="liste_total">';
                //         output += '<td>';
                //         output += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; padding: 10px;">';

                //         output += '<span style="flex: 1; display: flex; align-items: center; justify-content: flex-start; font-weight: bold;">';
                //         output += agence;
                //         output += '</span>';
                    
                //         output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
                //         output += 'CA : <span style="margin-left: 5px; color: #27ae60;">' + montant1.toLocaleString('fr-FR') + ' € <span style="margin-left: 5px; color: #2c3e50;border-right: 2px solid #d1d1d1;border-right: 2px solid #d1d1d1;border-bottom: 2px solid #a6a6a6;box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;border-radius: 4px;border-top: none;"> ' + proportionCA.toFixed(2) + '%</span></span>';
                //         output += '</span>';
                    
                //         output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
                //         output += 'Dépenses : <span style="margin-left: 5px; color: #e74c3c;">' + montant2.toLocaleString('fr-FR') + ' € <span style="margin-left: 5px; color: #2c3e50;border-right: 2px solid #d1d1d1;border-right: 2px solid #d1d1d1;border-bottom: 2px solid #a6a6a6;box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;border-radius: 4px;border-top: none;"> ' + proportionDep.toFixed(2) + '%</span></span>';
                //         output += '</span>';
                    
                //         output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
                //         output += 'Résultat : <span style="margin-left: 5px; color: ' + (percentageAgDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + percentageAgDifference.toFixed(2) + '%</span>';
                //         output += '</span>';
                    
                //         output += '</div>';
                //         output += '</td>';
                //         output += '</tr>';
                //     });
                
                //     // Calcul des totaux globaux
                //     const totalDifference = totalMontant1Global - totalMontant2Global;
                //     const percentageDifference = totalMontant1Global > 0 ? (totalDifference / totalMontant1Global) * 100 : 0;
                
                //     // Ajouter la ligne des totaux généraux
                //     output += '<tr class="liste_total">';
                //     output += '<td>Total des montants';
                //     output += '<div style="display: flex; justify-content: flex-end; gap: 15px;">';
                
                //     output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
                //     output += 'CA : <span style="color: #27ae60;">' + totalMontant1Global.toLocaleString('fr-FR') + ' €</span>';
                //     output += '</span>';
                
                //     output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
                //     output += 'Dépenses : <span style="color: #e74c3c;">' + totalMontant2Global.toLocaleString('fr-FR') + ' €</span>';
                //     output += '</span>';
                
                //     output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
                //     output += 'Résultat : <span style="color: ' + (totalDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + percentageDifference.toFixed(2) + '%</span>';
                //     output += '</span>';
                
                //     output += '</div></td></tr>';
                
                //     // Ajouter la ligne pour afficher la différence totale
                //     output += '<tr class="liste_total">';
                //     output += '<td>Résultat totale';
                //     output += '<div style="float: right; text-align: right;">';
                //     output += '<span style="display: inline-block; font-weight: bold;text-align: justify; margin-right:5px; width: 200px; color: ' + (totalDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">';
                //     output += totalDifference.toLocaleString('fr-FR') + ' € </span>';
                //     output += '</div></td></tr>';
                
                //     // Injecter les résultats dans le DOM
                //     $('#total-data').html(output); // Supposons que votre conteneur a l'id 'total-data'
                // }

                // Ajouter un écouteur d'événement sur chaque bouton
                    // buttons.forEach(button => {
                    //     button.addEventListener('click', () => {
                    //         // Retirer la classe active de tous les boutons
                    //         buttons.forEach(btn => btn.classList.remove('active'));
                    //         // Ajouter la classe active au bouton cliqué
                    //         button.classList.add('active');
                    //     });
                    // }); 

            </script>
        EOT;

        $output .= '<style>
            #chart {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
                margin: 20px 0;
            }
            .agency-legend {
                margin-bottom: 15px;
            }
            .legend-item {
                display: flex;
                align-items: center;
                margin: 5px 0;
            }
            .domain-legend {
                display: flex;
                flex-direction: column;
            }
            .agency-title {
                font-weight: bold;
                font-size: 14px;
            }
            .box {
                overflow-x: unset;
            }
            .text-success {
                color: green;
            }
            
            .text-danger {
                color: red;
            }



            .chart-buttons-container {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                align-items: center;
                // margin: 10px 0;
                margin-right:20px;
            }

            .chart-button button {
                background-color: #f4f4f4;
                border: 1px solid #ccc;
                border-radius: 5px;
                padding: 5px 8px;
                display: flex;
                align-items: center;
                gap: 5px;
                color: #333;
                font-family: Arial, sans-serif;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.1);
            }

            .chart-button button:hover {
                background-color: #e2e2e2;
                border-color: #999;
                color: #0056b3;
            }

            .chart-button button.active {
                background-color: #0056b3;
                color: white;
                border-color: #004080;
                box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
                font-weight: bold;
            }

            .chart-button button i {
                font-size: 18px;
                transition: transform 0.2s ease;
            }

            .chart-button button:active i {
                transform: scale(0.9);
            }

            .chart-button button .label {
                font-size: 12px;
                color: inherit;
                text-transform: capitalize;
            }

            .small-icon {
                font-size: 14px!important;
              }

        </style>';

       
        // Totaux généraux des agences
        $output .= '<tr class="liste_total" id="total-data" style="display: contents;">';
        $output .= '</tr>';
        

        // Ajout du script et du contenu dans la box Dolibarr
        $this->info_box_contents[0][0] = [
            'tr'=>'class="oddeven nohover"',
            'td' => 'class="nohover center"',
            'textnoformat' => $output,
        ];
    }

    // public function generateGradientColors($agency, $domainIndex, $totalDomains) {
    //     // On utilise une plage de teintes (hue) basée sur l'indice du domaine
    //     $hue = (360 / $totalDomains) * $domainIndex; // Équilibrer les teintes sur 360 degrés
    //     return sprintf('hsl(%d, 70%%, 60%%)', $hue); // Créer une couleur HSL avec teinte variable
    // }

    public function generateGradientColors($agency, $domainIndex, $totalDomains) {
        // Utiliser une plage de teintes (hue) basée sur l'indice du domaine
        $hue = (360 / $totalDomains) * $domainIndex; // Équilibrer les teintes sur 360 degrés
        // Créer une couleur HSL avec une saturation et luminosité fixes
        return sprintf('hsl(%d, 80%%, 50%%)', $hue); // Saturation à 80%, Luminosité à 50%
    }

    public function generateGradientColors1($agence, $domainIndex, $totalDomains) {
        $hue = (int) ((($domainIndex / $totalDomains) * 360) % 360);  // Varie la teinte en fonction de l'indice
        $saturation = 60;
        $lightness = 30;
    
        return "hsl($hue, {$saturation}%, {$lightness}%)";
    }

    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        global $langs, $user;

        if($user->hasRight('addoncomm', 'box_indicateur_ca', 'read')) { 
         return $this->showBoxCustom($this->info_box_head, $this->info_box_contents, $nooutput);
        }
    }



    
    /**
	 * Standard method to show a box (usage by boxes not mandatory, a box can still use its own showBox function)
	 *
	 * @param   array   $head       Array with properties of box title
	 * @param   array   $contents   Array with properties of box lines
	 * @param	int		$nooutput	No print, only return string
	 * @return  string
	 */
	public function showBoxCustom($head = null, $contents = null, $nooutput = 0)
	{
		global $langs, $user, $conf;

		if (!empty($this->hidden)) {
			return '\n<!-- Box ".get_class($this)." hidden -->\n'; // Nothing done if hidden (for example when user has no permission)
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$MAXLENGTHBOX = 60; // Mettre 0 pour pas de limite

		$cachetime = 900; // 900 : 15mn
		$cachedir = DOL_DATA_ROOT.'/boxes/temp';
		$fileid = get_class($this).'id-'.$this->box_id.'-e'.$conf->entity.'-u'.$user->id.'-s'.$user->socid.'.cache';
		$filename = '/box-'.$fileid;
		$refresh = dol_cache_refresh($cachedir, $filename, $cachetime);
		$out = '';
		$mode       = GETPOST('mode', 'aZ');
		$modedate  = GETPOST('modedate', 'aZ');
		$modeag       = GETPOST('modeag', 'aZ');
	

		if ($refresh) {
			dol_syslog(get_class($this).'::showBox');

			// Define nbcol and nblines of the box to show
			$nbcol = 0;
			if (isset($contents[0])) {
				$nbcol = count($contents[0]);
			}
			$nblines = count($contents);

			$out .= "\n<!-- Box ".get_class($this)." start -->\n";

			$out .= '<div class="box boxdraggable" id="boxto_'.$this->box_id.'">'."\n";

			if (!empty($head['text']) || !empty($head['sublink']) || !empty($head['subpicto']) || $nblines) {
				$out .= '<table summary="boxtable'.$this->box_id.'" width="100%" class="noborder boxtable">'."\n";
			}

			// Show box title
			if (!empty($head['text']) || !empty($head['sublink']) || !empty($head['subpicto'])) {
				$out .= '<tr class="liste_titre box_titre">';
				$out .= '<td';
				if ($nbcol > 0) {
					$out .= ' colspan="'.$nbcol.'"';
				}
				$out .= '>';
				if (!empty($conf->use_javascript_ajax)) {
					//$out.= '<table summary="" class="nobordernopadding" width="100%"><tr><td class="tdoverflowmax150 maxwidth150onsmartphone">';
					$out .= '<div class="tdoverflowmax400 maxwidth250onsmartphone float">';
				}
				if (!empty($head['text'])) {
					$s = dol_trunc($head['text'], isset($head['limit']) ? $head['limit'] : $MAXLENGTHBOX);
					$out .= $s;
				}
				if (!empty($conf->use_javascript_ajax)) {
					$out .= '</div>';
				}
				//$out.= '</td>';

				if (!empty($conf->use_javascript_ajax)) {
					$sublink = '';
					if (!empty($head['sublink'])) {
						$sublink .= '<a href="'.$head['sublink'].'"'.(empty($head['target']) ? '' : ' target="'.$head['target'].'"').'>';
					}
					if (!empty($head['subpicto'])) {
						$sublink .= img_picto($head['subtext'], $head['subpicto'], 'class="opacitymedium marginleftonly '.(empty($head['subclass']) ? '' : $head['subclass']).'" id="idsubimg'.$this->boxcode.'"');
					}
					if (!empty($head['sublink'])) {
						$sublink .= '</a>';
					}

					//$out.= '<td class="nocellnopadd boxclose right nowraponall">';
					$out .= '<div class="nocellnopadd boxclose floatright nowraponall" style="position: relative;top: 6px;right: 10px;">';
					$out .= $sublink;
					// The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
					$out .= img_picto($langs->trans("MoveBox", $this->box_id), 'grip_title', 'class="opacitymedium boxhandle hideonsmartphone cursormove marginleftonly"');
					$out .= img_picto($langs->trans("CloseBox", $this->box_id), 'close_title', 'class="opacitymedium boxclose cursorpointer marginleftonly" rel="x:y" id="imgclose'.$this->box_id.'"');
					$label = $head['text'];
					//if (! empty($head['graph'])) $label.=' ('.$langs->trans("Graph").')';
					if (!empty($head['graph'])) {
						$label .= ' <span class="opacitymedium fa fa-bar-chart"></span>';
					}
					$out .= '<input type="hidden" id="boxlabelentry'.$this->box_id.'" value="'.dol_escape_htmltag($label).'">';
					//$out.= '</td></tr></table>';
					$out .= '</div>';
				}
				// $out .= '<div class="nocellnopadd boxclose floatright nowraponall">';
                // $out .= '<button id="toggleButton" style="font-size: 0.9em;opacity: 0.4;border: rgb(240,240,240);background-color:rgb(240,240,240);" title="Cliquer pour afficher les recettes et dépenses par agence">';
                // $out .= '<span class="fas fa-toggle-off"></span>';
                // $out .= '</button>';
                // $out .= '<button id="fullscreenButton" style="font-size: 0.9em; opacity: 0.4; border: rgb(240,240,240); background-color: rgb(240,240,240); margin-left: 10px;" title="Agrandir">';
                // $out .= '<span id="fullscreenIcon" class="fas fa-expand"></span>';
                // $out .= '</button>';
                // $out .= '<div id="chartMode" style="display: none;"></div>';
                // $out .= '<div id="chartContainer" style="width: 80%; margin: auto;"></div>';  

                $out .= '<div class="chart-buttons-container">';
                $out .= '<div class="chart-button">';
                $out .= '<button id="toggleButton" title="Cliquer pour afficher les recettes par agence">';
                $out .= '<i class="fas fa-chart-pie small-icon"></i>';
                $out .= '</button>';
                $out .= '</div>';
                $out .= '<div class="chart-button">';
                $out .= '<button id="toggleButtonBar" title="Cliquer pour afficher les recettes et dépenses par agence">';
                $out .= '<i class="fas fa-chart-bar small-icon" style="transform: rotate(90deg);"></i>';
                $out .= '</button>';
                $out .= '</div>';
                $out .= '<div class="chart-button">';
                $out .= '<button id="toggleButtonLine" title="Cliquer pour afficher les évolutions des recettes et dépenses par agence">';
                $out .= '<i class="fas fa-chart-area small-icon"></i>';
                $out .= '</button>';
                $out .= '</div>';
              
                // $out .= '<input id="start_evolution_date" name="start_evolution_date" type="text" class="maxwidthdate hasDatepicker" maxlength="11" value="07/01/2025" size="10">';
                // $out .= '<img class="ui-datepicker-trigger" src="/erp/theme/eldy/img/object_calendarday.png" alt="empty" title="Date de début">';
                // $out .= '<input id="end_evolution_date" name="end_evolution_date" type="text" class="maxwidthdate hasDatepicker" maxlength="11" value="07/01/2025" size="10">';
                // $out .= '<img class="ui-datepicker-trigger" src="/erp/theme/eldy/img/object_calendarday.png" alt="empty" title="Date de Fin">';

                $out .= '<div class="date-picker">';
                $out .= '<input type="text" id="date-debut" class="maxwidthdate hasDatepicker" maxlength="11" value="' . date('d/m/Y', strtotime('first day of January')) . '" size="10">';
                $out .= '<div class="calendar" id="calendar-debut">';
                $out .= '<div class="calendar-header">';
                $out .= '<button id="prev-month-debut">&lt;</button>';
                $out .= '<div id="current-month-year-debut"></div>';
                $out .= '<button id="next-month-debut">&gt;</button>';
                $out .= '</div>';
                $out .= '<div class="days" id="days-debut"></div>';
                $out .= '</div>';
                $out .= '<input type="hidden" id="date-debut" class="maxwidthdate hasDatepicker" maxlength="11" value="' . date('d/m/Y', strtotime('first day of January')) . '" size="10">';
                $out .= '<img class="ui-datepicker-trigger" src="/erp/theme/eldy/img/object_calendarday.png" alt="empty" title="Date de début">';

                $out .= '</div>';

                $out .= '<div class="date-picker">';
                $out .= '<input type="text" id="date-fin" class="maxwidthdate hasDatepicker" maxlength="11" value="' . date('d/m/Y') . '" size="10">';
                $out .= '<div class="calendar" id="calendar-fin">';
                $out .= '<div class="calendar-header">';
                $out .= '<button id="prev-month-fin">&lt;</button>';
                $out .= '<div id="current-month-year-fin"></div>';
                $out .= '<button id="next-month-fin">&gt;</button>';
                $out .= '</div>';
                $out .= '<div class="days" id="days-fin"></div>';
                $out .= '</div>';
                $out .= '<img class="ui-datepicker-trigger" src="/erp/theme/eldy/img/object_calendarday.png" alt="empty" title="Date de fin">';
                $out .= '</div>';
               
                
                $out .= '<div id="yearSelectionContainer"></div>';
                $out .= '<div id="resultContainer"></div>';
                $out .= '</div>';
				

                // $out .= '<div class="date-picker">';
                // // Champ visible pour la date de début
                // $out .= '<input type="text" id="date-debut-display" class="maxwidthdate hasDatepicker" maxlength="11" value="' . date('d/m/Y', strtotime('first day of January')) . '" size="10">';

                // // Champ caché pour la date de début
                // $out .= '<input type="hidden" id="date-debut" class="maxwidthdate hasDatepicker" value="' . date('d/m/Y', strtotime('first day of January')) . '" size="10">';

                // // Calendrier caché pour la date de début
                // $out .= '<div class="calendar" id="calendar-debut">';
                // $out .= '<div class="calendar-header">';
                // $out .= '<button id="prev-month-debut">&lt;</button>';
                // $out .= '<div id="current-month-year-debut"></div>';
                // $out .= '<button id="next-month-debut">&gt;</button>';
                // $out .= '</div>';
                // $out .= '<div class="days" id="days-debut"></div>';
                // $out .= '</div>';

                // // Icône de calendrier pour la date de début
                // $out .= '<img class="ui-datepicker-trigger" src="/erp/theme/eldy/img/object_calendarday.png" alt="empty" title="Date de début">';

                // $out .= '</div>';

                // // Champ visible pour la date de fin
                // $out .= '<div class="date-picker">';
                // $out .= '<input type="text" id="date-fin-display" class="maxwidthdate hasDatepicker" maxlength="11" value="' . date('d/m/Y') . '" size="10">';

                // // Champ caché pour la date de fin
                // $out .= '<input type="hidden" id="date-fin" class="maxwidthdate hasDatepicker" value="' . date('d/m/Y') . '" size="10">';

                // // Calendrier caché pour la date de fin
                // $out .= '<div class="calendar" id="calendar-fin">';
                // $out .= '<div class="calendar-header">';
                // $out .= '<button id="prev-month-fin">&lt;</button>';
                // $out .= '<div id="current-month-year-fin"></div>';
                // $out .= '<button id="next-month-fin">&gt;</button>';
                // $out .= '</div>';
                // $out .= '<div class="days" id="days-fin"></div>';
                // $out .= '</div>';

                // // Icône de calendrier pour la date de fin
                // $out .= '<img class="ui-datepicker-trigger" src="/erp/theme/eldy/img/object_calendarday.png" alt="empty" title="Date de fin">';

                // $out .= '</div>';
				$out .= "</td>";
				$out .= "</tr>\n";
			}
            
			// Show box lines
			if ($nblines) {
				// Loop on each record
				for ($i = 0, $n = $nblines; $i < $n; $i++) {
					if (isset($contents[$i])) {
						// TR
						if (isset($contents[$i][0]['tr'])) {
							$out .= '<tr '.$contents[$i][0]['tr'].'>';
						} else {
							$out .= '<tr class="oddeven">';
						}

						// Loop on each TD
						$nbcolthisline = count($contents[$i]);
						for ($j = 0; $j < $nbcolthisline; $j++) {
							// Define tdparam
							$tdparam = '';
							if (!empty($contents[$i][$j]['td'])) {
								$tdparam .= ' '.$contents[$i][$j]['td'];
							}

							$text = isset($contents[$i][$j]['text']) ? $contents[$i][$j]['text'] : '';
							$textwithnotags = preg_replace('/<([^>]+)>/i', '', $text);
							$text2 = isset($contents[$i][$j]['text2']) ? $contents[$i][$j]['text2'] : '';
							$text2withnotags = preg_replace('/<([^>]+)>/i', '', $text2);

							$textnoformat = isset($contents[$i][$j]['textnoformat']) ? $contents[$i][$j]['textnoformat'] : '';
							//$out.= "xxx $textwithnotags y";
							if (empty($contents[$i][$j]['tooltip'])) {
								$contents[$i][$j]['tooltip'] = "";
							}
							$tooltip = isset($contents[$i][$j]['tooltip']) ? $contents[$i][$j]['tooltip'] : '';

							$out .= '<td'.$tdparam.'>'."\n";

							// Url
							if (!empty($contents[$i][$j]['url']) && empty($contents[$i][$j]['logo'])) {
								$out .= '<a href="'.$contents[$i][$j]['url'].'"';
								if (!empty($tooltip)) {
									$out .= ' title="'.dol_escape_htmltag($langs->trans("Show").' '.$tooltip, 1).'" class="classfortooltip"';
								}
								//$out.= ' alt="'.$textwithnotags.'"';      // Pas de alt sur un "<a href>"
								$out .= isset($contents[$i][$j]['target']) ? ' target="'.$contents[$i][$j]['target'].'"' : '';
								$out .= '>';
							}

							// Logo
							if (!empty($contents[$i][$j]['logo'])) {
								$logo = preg_replace("/^object_/i", "", $contents[$i][$j]['logo']);
								$out .= '<a href="'.$contents[$i][$j]['url'].'">';
								$out .= img_object($langs->trans("Show").' '.$tooltip, $logo, 'class="classfortooltip"');
							}

							$maxlength = $MAXLENGTHBOX;
							if (!empty($contents[$i][$j]['maxlength'])) {
								$maxlength = $contents[$i][$j]['maxlength'];
							}

							if ($maxlength) {
								$textwithnotags = dol_trunc($textwithnotags, $maxlength);
							}
							if (preg_match('/^<(img|div|span)/i', $text) || !empty($contents[$i][$j]['asis'])) {
								$out .= $text; // show text with no html cleaning
							} else {
								$out .= $textwithnotags; // show text with html cleaning
							}

							// End Url
							if (!empty($contents[$i][$j]['url'])) {
								$out .= '</a>';
							}

							if (preg_match('/^<(img|div|span)/i', $text2) || !empty($contents[$i][$j]['asis2'])) {
								$out .= $text2; // show text with no html cleaning
							} else {
								$out .= $text2withnotags; // show text with html cleaning
							}

							if (!empty($textnoformat)) {
								$out .= "\n".$textnoformat."\n";
							}

							$out .= "</td>\n";
						}

						$out .= "</tr>\n";
					}
				}
			}

			if (!empty($head['text']) || !empty($head['sublink']) || !empty($head['subpicto']) || $nblines) {
				$out .= "</table>\n";
			}

			// If invisible box with no contents
			if (empty($head['text']) && empty($head['sublink']) && empty($head['subpicto']) && !$nblines) {
				$out .= "<br>\n";
			}

			$out .= "</div>\n";

			$out .= "<!-- Box ".get_class($this)." end -->\n\n";
			if (!empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
				dol_filecache($cachedir, $filename, $out);
			}
		} else {
			dol_syslog(get_class($this).'::showBoxCached');
			$out = "<!-- Box ".get_class($this)." from cache -->";
			$out .= dol_readcachefile($cachedir, $filename);
		}

		if ($nooutput) {
			return $out;
		} else {
			print $out;
		}

		return '';
	}
}