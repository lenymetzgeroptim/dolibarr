<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/feuilledetemps.class.php
 * \ingroup     feuilledetemps
 * \brief       This file is a CRUD class file for FeuilleDeTemps (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/regul.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/deplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/silae.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedUser.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if($conf->donneesrh->enabled) require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedexport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

/**
 * Class for IndicCommGraph - Vue d’ensemble des indicateurs commerciaux 
 */
class IndicCommGraph extends CommonObject
{

    /**
     * 
     */
    public function fetchAllChartDataByMonth($fk_projet)
    {
        global $db;
        $commandeData = $this->fetchCommande($fk_projet);
        $values = [
            'commande'             => $this->toStructuredArray($commandeData['commande'] ?? []),
            'reste_a_facturer'     => $this->toStructuredArray($commandeData['reste_a_facturer'] ?? []),
            'facture'              => $this->toStructuredArray($this->fetchFacture($fk_projet)),
            'facture_draft'        => $this->toStructuredArray($this->fetchFactureDraft($fk_projet)),
            'facture_pv'           => $this->toStructuredArray($this->fetchFacturePv($fk_projet)),
            'facture_fournisseur'  => $this->toStructuredArray($this->fetchFactureFourn($fk_projet)),
            'propal_open'          => $this->toStructuredArray($this->fetchPropalOpen($fk_projet)),
            'propal_signed'        => $this->toStructuredArray($this->fetchPropalSigned($fk_projet)),
            'cost_temps'           => $this->toStructuredArray($this->fetchCostTemps($fk_projet)),
            'nb_heure'             => $this->fetchMonthlyHoursByProject($fk_projet),
            'note_frais'          =>  $this->fetchExpenseReport($fk_projet),
        ];

        $vals = [];
        $factureParAnnee = [];

        $moisExistants = [];
       

        foreach ($values as $key => $arr) {
            foreach ($arr as $val) {
                $val['total'] = $val['total'] ?? 0;
                $date = $val['date']; // format YYYY-MM
                $year = substr($date, 0, 4);
                $month = substr($date, 5, 2);

                $moisExistants[$year][$month] = true;

                switch ($key) {
                    case 'commande':
                        $vals[$date]['commande'] = $val['total'];
                        break;
                    case 'facture_fournisseur':
                        $vals[$date]['supplier'] = $val['total'];
                        break;
                    case 'propal_open':
                        $vals[$date]['open'] = $val['total'];
                        break;
                    case 'propal_signed':
                        $vals[$date]['signed'] = $val['total'];
                        break;
                    case 'facture':
                        $vals[$date]['facture'] = $val['total'];
                        if (preg_match('/^\d{4}$/', $year) && $year >= 1900 && $year <= 2100) {
                            $factureParAnnee[$year][$month] = $val['total'];
                        }
                        break;
                    case 'facture_pv':
                        $vals[$date]['facture_pv'] = $val['total'];
                        break;
                    case 'facture_draft':
                        $vals[$date]['facture_d'] = $val['total'];
                        break;
                    case 'cost_temps':
                        $vals[$date]['temps'] = $val['total'];
                        break;
                    case 'reste_a_facturer':
                        $vals[$date]['reste'] = $val['total'];
                        break;
                    case 'nb_heure':
                        $vals[$date]['nb_heure'] = $val['total'];
                        break;
                    case 'note_frais':
                        $vals[$date]['note_frais'] = $val['total'];
                        break;
                }
            }
        }

 
        $project = new Project($db);
        $project->fetch($fk_projet);

        // Identifier les années min et max
       $invoiceyears = array_keys($factureParAnnee);
        if (empty($invoiceyears)) {
            // Aucune facture : on continue quand même sans les moyennes
            $firstYear = $lastYear = null;
            $courbeMoyenneParMois = [];
        } else {
            sort($invoiceyears);
            $firstYear = (int) $invoiceyears[0];
            $lastYear = (int) end($invoiceyears);

            foreach (range($firstYear, $lastYear) as $year) {
                $moisFin = ($year == $lastYear) ? dol_print_date($project->date_end, "%m") : 12;
                $moisDebut = ($year == $firstYear) ? dol_print_date($project->date_start, "%m") : 1;

                for ($m = $moisDebut; $m <= $moisFin; $m++) {
                    $month = str_pad($m, 2, '0', STR_PAD_LEFT);
                    $monthInt = (int)$month;
                    if ($monthInt < 1 || $monthInt > 12) continue;

                    $dateKey = "$year-$month";
                    if (!isset($vals[$dateKey])) {
                        $vals[$dateKey] = []; // mois vide mais affiché
                    }
                    if (!isset($factureParAnnee[$year][$month])) {
                        $factureParAnnee[$year][$month] = 0; // ajouter mois avec 0
                    }
                }
            }

            // Compléter les mois manquants pour la dernière année
            if (!empty($factureParAnnee[$lastYear])) {
                $moisDejaPresents = array_keys($factureParAnnee[$lastYear]);
                $moisDejaPresents = array_map('intval', $moisDejaPresents);
                $moisMin = !empty($moisDejaPresents) ? min($moisDejaPresents) : 12;

                for ($m = 1; $m < $moisMin; $m++) {
                    $month = str_pad($m, 2, '0', STR_PAD_LEFT);
                    if (!isset($factureParAnnee[$lastYear][$month])) {
                        $factureParAnnee[$lastYear][$month] = 0;
                    }
                }
                ksort($factureParAnnee[$lastYear]); // TRI
            }

            // Calcul des moyennes annuelles
            $moyenneParAnnee = [];
            foreach ($factureParAnnee as $year => $moisData) {
                ksort($moisData);
                $somme = array_sum($moisData);

                if ((int)$year === $lastYear) {
                    $moisFactures = array_filter($moisData, fn($val) => $val > 0);
                    $nbMois = !empty($moisFactures) ? (int) max(array_keys($moisFactures)) : count($moisData);
                } else {
                    $nbMois = count($moisData);
                }

                $moyenneParAnnee[$year] = ($nbMois > 0) ? round($somme / $nbMois, 2) : 0;
            }

            // Récupération du dernier mois réel de facturation pour l'année de fin
            $dernierMoisFacturation = '12';
            if (!empty($factureParAnnee[$lastYear])) {
                $moisFactures = array_filter($factureParAnnee[$lastYear], fn($val) => $val > 0);
                if (!empty($moisFactures)) {
                    $dernierMoisFacturation = max(array_keys($moisFactures));
                }
            }

            // Courbe des moyennes mensuelles
            $courbeMoyenneParMois = [];
            foreach ($factureParAnnee as $year => $moisData) {
                foreach ($moisData as $month => $_) {
                    if ((int)$year === $lastYear && (int)$month > (int)$dernierMoisFacturation) {
                        continue;
                    }
                    $dateKey = "$year-$month";
                    $courbeMoyenneParMois[$dateKey] = $moyenneParAnnee[$year];
                }
            }
        }

    
        ksort($vals);

        // Génération du format graphique
        $chartData = [];

        foreach ($vals as $month => $value) {
            $commande       = $value['commande'] ?? null;
            $open           = $value['open'] ?? null;
            $signed         = $value['signed'] ?? null;
            $facture        = $value['facture'] ?? null;
            $facture_pv     = $value['facture_pv'] ?? null;
            $facture_draft  = $value['facture_d'] ?? null;
            $facture_fourn  = $value['supplier'] ?? null;
            $temps          = $value['temps'] ?? null;
            $reste          = $value['reste'] ?? null;
            $nbHeure        = $value['nb_heure'] ?? null;
            $noteFrais      = $vals['note_frais'] ?? null;
            $facture_moy    = $courbeMoyenneParMois[$month] ?? null;

            $row = ['date' => $month . '-01'];
            
            $row['Cde Client']         = $commande;
            $row['Cde+Dev.']           = $commande + $open;
            $row['Dev. signé']         = $signed;
            $row['Dev. ouvert']        = $open;
            $row['Fact. Client Tot.']  = $facture;
            $row['fact. moyenne']      = $facture_moy;
            $row['Fact. attente compta'] = $facture_pv;
            $row['Fact. à venir']      = $facture_draft;
            $row['Fact. fournisseur']  = $facture_fourn;
            $row['Charges']     = $temps + $noteFrais;
            $row['Reste à facturer']   = $reste;
            $row['Nb. Heure']   = $nbHeure;
            

            $chartData[] = $row;
        }
  
        return $chartData;
    }


    protected function getStartMonthOfProject($fk_projet)
    {
        global $db;
        $sql = "SELECT dateo FROM ".MAIN_DB_PREFIX."projet WHERE rowid = ".((int) $fk_projet);
        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            return dol_print_date($db->jdate($obj->dateo), '%Y-%m');
        }
        return date('Y-m'); // fallback
    }


    private function toStructuredArray(array $raw): array
    {
        $result = [];
        foreach ($raw as $month => $amount) {
            $result[] = ['date' => $month, 'total' => $amount];
        }
        return $result;
    }


    private function spreadAmountOverMonths(DateTimeImmutable $start, DateTimeImmutable $end, float $amount): array
    {
        $results = [];

        $interval = $start->diff($end);
        $months = ($interval->y * 12) + $interval->m + 1;

        if ($months <= 0) return [];

        $monthlyAmount = $amount / $months;

        $current = $start;
        for ($i = 0; $i < $months; $i++) {
            $key = $current->format('Y-m');
            $results[$key] = ($results[$key] ?? 0) + $monthlyAmount;
            $current = $current->modify('+1 month');
        }

        return $results;
    }


    public function fetchCostTemps($fk_projet): array
    {
        $data = [];

        $rows = $this->employeeCostByProject($fk_projet);
        if ($rows === -1) return [];

        foreach ($rows as $entry) {
            $month = $entry['date'];
            $data[$month] = ($data[$month] ?? 0) + (float) $entry['amount'];
        }

        return $data;
    }

    public function getColorsChartData()
    {
        // Couleurs associées 
        $datacolors = [
            'Cde Client'             => '#177F00',
            'Cde+Dev.'               => '#D0D404',
            'Dev. signé'             => '#29D404',
            'Dev. ouvert'            => '#36FF09',
            'Fact. Client Tot.'      => '#FF0202',
            'fact. moyenne'          => '#9E2B40',
            'Fact. attente compta'   => '#FD7F7F',
            'Fact. à venir'          => '#FCCACA',
            'Fact. fournisseur'      => '#04D0D4',
            'Charges'         => '#0005FF',
            'Reste à facturer'       => '#FF7F00',
        ];

        return $datacolors;
    }

    /**
	 * get salaries of employees by reference to the projects
	 * 
	 * @param string option to filter by option either resp or all py default
	 */
	// public function employeeCostByProject($fk_projet)
	// {
	// 	global $db, $user;

	// 	$sql = "SELECT sum((t.thm) * (t.element_duration / 3600)) as cost, DATE_FORMAT(t.element_date,'%Y-%m') as dm";
	// 	$sql .= ", SUM(hs.heure_sup_25_duration / 3600 * ".$db->ifsql("t.thm IS NULL", 0, "t.thm * 0.25").") as amount_hs25,";
    //     $sql .= " SUM(hs.heure_sup_50_duration / 3600 * ".$db->ifsql("t.thm IS NULL", 0, "t.thm * 0.5").") as amount_hs50, u.rowid as userid";

	// 	$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs ON hs.fk_projet_task_time = t.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on ec.fk_socpeople = u.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
	// 	$sql .= " WHERE 1 = 1";
    //     $sql .= " AND tc.element = 'project'";
    //     $sql .= " AND tc.source = 'internal'";
    //     $sql .= " AND tc.code = 'PROJECTLEADER'";
    //     $sql .= " AND ec.element_id = '".$fk_projet."'";

	// 	$sql .= " AND t.elementtype = 'task'";
    //     // $sql .= " t.element_date BETWEEN p.dateo AND p.datee";
	// 	$sql .= " GROUP BY dm, u.rowid";
	// 	$sql .= " ORDER BY dm";
	
	// 	dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
	// 	$resql = $db->query($sql);
		
	// 	if ($resql) {
	// 		$num = $db->num_rows($resql);
			
	// 		$i = 0;
	// 		while ($i < $num) {
	// 			$obj = $db->fetch_object($resql);
		
	// 			$hs25 =  $obj->amount_hs25 == null ? 0 : $obj->amount_hs25;
	// 			$hs50 = $obj->amount_hs50 == null ? 0 : $obj->amount_hs50;
	// 			$cost = $obj->cost + $hs25 + $hs50;
	// 			$salaries[] = array('date' => $obj->dm, 'amount' => $cost);
	
	// 			$i++;
	// 		} 

	// 		return $salaries;
	// 	} else {
	// 		$this->error = $db->error();
	// 		return -1;
	// 	}
	
	// }

    public function employeeCostByProject($fk_projet)
    {
        global $db, $user, $mysoc;

        $sql = "SELECT t.rowid, t.element_date, t.element_duration, t.thm, DATE_FORMAT(t.element_date,'%Y-%m') as dm";
        $sql .= ", hs.heure_sup_25_duration, hs.heure_sup_50_duration";
        $sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
        // $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
        // $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs ON hs.fk_projet_task_time = t.rowid";
        // $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
        // $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on ec.fk_socpeople = u.rowid";
        // $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
        // $sql .= " WHERE tc.element = 'project'";
        // $sql .= " AND tc.source = 'internal'";
        // $sql .= " AND tc.code = 'PROJECTLEADER'";
        $sql .= " WHERE pt.fk_projet = '".$fk_projet."'";
        // $sql .= " AND t.elementtype = 'task'";
        $sql .= " ORDER BY t.element_date";

        dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
        $resql = $db->query($sql);

        if ($resql) {
            $salaries = [];

            while ($obj = $db->fetch_object($resql)) {
                $date = $obj->element_date;
                $thm = $obj->thm ?? 0;
                $duration = $obj->element_duration ?? 0;
                $dm = $obj->dm;

                $hs25 = $obj->heure_sup_25_duration ? ($obj->heure_sup_25_duration / 3600 * $thm * 0.25) : 0;
                $hs50 = $obj->heure_sup_50_duration ? ($obj->heure_sup_50_duration / 3600 * $thm * 0.5) : 0;

                $standard_cost = $thm * ($duration / 3600);

                // Vérifier si dimanche ou jour férié
                $isSunday = dol_print_date(strtotime($date), '%a') === 'Dim';

                $isPublicHoliday = num_public_holiday(strtotime($date) + 86400, strtotime($date) + 172800, $mysoc->country_code, 0, 0, 0);
                // var_dump(dol_print_date(strtotime($date), '%a'));
                $extra_cost = 0;
       
                if ($isSunday || $isPublicHoliday) {
                    $extra_cost = $standard_cost;
                }

                $total = $standard_cost + $hs25 + $hs50 + $extra_cost;

                if (!isset($salaries[$dm])) $salaries[$dm] = 0;
                $salaries[$dm] += $total;
            }

            // Remise en tableau avec clé => valeur
            $data = [];
            foreach ($salaries as $dm => $amount) {
                $data[] = ['date' => $dm, 'amount' => $amount];
            }

            return $data;
        } else {
            $this->error = $db->error();
            return -1;
        }
    }


    /**
	 * get expense report
	 * 
	 * @return array date and exepnense report amount 
	 */
	public function fetchExpenseReport($fk_projet)
	{
		global $db;

		$sql = "SELECT date_format(r.date_debut,'%Y-%m') as dm, sum(de.total_ht) as amount_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as r";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_det as de ON r.rowid = de.fk_expensereport";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on de.fk_projet = p.rowid";
	
		$sql .= " WHERE p.rowid = '".$fk_projet."'";
		
		$sql .= " AND r.entity IN (".getEntity('expensereport').")";
		$sql .= " AND r.fk_statut = 6";
        $sql .= " AND r.date_debut BETWEEN p.dateo AND p.datee";
		$sql .= " GROUP BY dm";
  


		dol_syslog(get_class($this)."::fetchExpenseReport", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$exepnesereport[] = array('date' => $obj->dm, 'amount' => $obj->amount_ht);
				
				$i++;
			} 
			return $exepnesereport;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}


    // Par nb Heure
        /**
     * Récupère les heures totales travaillées par mois pour un projet donné.
     *
     * @param DoliDB $db         Objet base de données Dolibarr
     * @param int    $projectId  ID du projet (fk_projet)
     * @return array             Tableau associatif ['YYYY-MM' => heures]
     */
    public function fetchMonthlyHoursByProject($fk_projet) {
        global $db;
        $data = [];

        $sql = "
            SELECT 
                DATE_FORMAT(t.element_date, '%Y-%m') AS mois,
                SUM(t.element_duration) / 3600 AS heures_total
            FROM llx_element_time AS t
            LEFT JOIN llx_projet_task AS p ON p.rowid = t.fk_element
            WHERE p.fk_projet = ".((int) $fk_projet)."
            GROUP BY mois
            ORDER BY mois
        ";
    
        dol_syslog("fetchMonthlyHoursByProject sql=".$sql, LOG_DEBUG);

        $resql = $db->query($sql);
        if (!$resql) {
            dol_print_error($db);
            return $data;
        }

        while ($obj = $db->fetch_object($resql)) {
            // $data['nb_heure'][$obj->mois] = round($obj->heures_total, 2);
            $data[] = ['date' => $obj->mois, 'total' => round($obj->heures_total, 2)];
        }

        return $data;
    }

    
    //par commande
    public function fetchCommande($fk_projet)
    {
        global $db;

        $data = [];

        // Récupération de la date de la fin du projet
        $sql = "SELECT datee as date_fin FROM " . MAIN_DB_PREFIX . "projet WHERE rowid = " . ((int)$fk_projet);
        $resql = $db->query($sql);

        if (!$resql || $db->num_rows($resql) === 0) {
            return []; 
        }

        $obj = $db->fetch_object($resql);
        $dateFinProjet = !empty($obj->date_fin) ? new DateTimeImmutable(dol_print_date($obj->date_fin, '%Y-%m-%d')) : null;

        if (!$dateFinProjet || $dateFinProjet < new DateTimeImmutable()) {
            return []; // Date absente
        }

        $sql = "SELECT c.rowid as commande_id, c.total_ht, c.date_livraison, ce.date_start, c.date_creation, c.fk_statut as statut";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commande as c";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_extrafields as ce ON c.rowid = ce.fk_object";
        $sql .= " WHERE c.fk_projet = " . ((int)$fk_projet);
        // $sql .= " AND p.dateo <= ce.date_start AND p.datee >= c.date_livraison";
        
        $resql = $db->query($sql);
        if (!$resql) return [];

        $totalCommande = 0;
        $totalFacture = 0;

        while ($obj = $db->fetch_object($resql)) {
            $start = !empty($obj->date_start) ? new DateTimeImmutable(dol_print_date($obj->date_start, '%Y-%m-%d')) : null;
            $end   = !empty($obj->date_livraison) ? new DateTimeImmutable(dol_print_date($obj->date_livraison, '%Y-%m-%d')) : null;
        
            // Toute les commandes except à l'état brouillon.
            if($obj->statut > 0) {
                $commandeTotal = (float)$obj->total_ht;
            }
            

            if ($start && $end && $commandeTotal > 0) {
                // Répartition commande sur sa période
                $spreadCommande = $this->spreadAmountOverMonths($start, $end, $commandeTotal);
                foreach ($spreadCommande as $month => $val) {
                    $data['commande'][$month] = ($data['commande'][$month] ?? 0) + $val;
                }

                // Factures liées
                $factures = $this->getCommandeFactures($obj->commande_id);
                $totalFacture = 0;
                foreach ($factures as $fac) {
                    $totalFacture += (float)$fac['total_ht'];
                }

                if ($totalFacture > 0) {
                    $spreadFacture = $this->spreadAmountOverMonths($start, $end, $totalFacture);
                    foreach ($spreadFacture as $month => $val) {
                        $data['facture'][$month] = ($data['facture'][$month] ?? 0) + $val;
                    }
                }

                 if($obj->statut > 0) {
                    $sumTotalCommande += $commandeTotal;
                }
                
                $sumTotalFacture  += $totalFacture;
            }
        }

        // Lissage du reste à facturer sur la période : aujourd'hui -> date fin de projet
        $dateDebut = new DateTimeImmutable();
        $dateFin   = $dateFinProjet;

        $nbMoisTotal = 0;
        $tmp = clone $dateDebut;

        while ($tmp->format('Y-m') <= $dateFin->format('Y-m')) {
            $nbMoisTotal++;
            $tmp = $tmp->modify('+1 month');
        }

        $resteGlobal = $sumTotalCommande - $sumTotalFacture;
        $montantMensuel = $nbMoisTotal > 0 ? $resteGlobal / $nbMoisTotal : 0;

        $tmp = clone $dateDebut;
        for ($i = 0; $i < $nbMoisTotal; $i++) {
            $month = $tmp->format('Y-m');

            // Valeurs existantes
            $commande = $data['commande'][$month] ?? 0;
            $facture  = $data['facture'][$month] ?? 0;

            // Reste lissé 
            $data['reste_a_facturer'][$month] = $montantMensuel;

            $tmp = $tmp->modify('+1 month');
        }

        return $data;
    }


    public function getCommandeFactures($commande_id)
    {
        global $db;
        $sql = "SELECT f.total_ht, f.datef, lf.fk_facture 
            FROM " . MAIN_DB_PREFIX . "facturedet as lf
            LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee ON lf.fk_facture = ee.fk_target
            LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON lf.fk_facture = f.rowid
            LEFT JOIN " . MAIN_DB_PREFIX . "facture_extrafields as ef ON ef.fk_object = f.rowid
            WHERE ee.fk_source = " . intval($commande_id) . "
            AND ee.sourcetype = 'commande' 
            AND ef.pv_reception = '1'
            AND ee.targettype = 'facture'";

        $resql = $db->query($sql);
        $facturedItems = [];

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $facturedItems[$obj->fk_facture] = [
                    'total_ht' => $obj->total_ht,
                    'date' => dol_print_date($obj->datef, '%Y-%m-%d'),
                ];
            }
        }
        return $facturedItems;
    }

    /**
     * 
     */
    public function fetchCFournisseur($fk_projet)
    {
        global $db;
        $data = [];

        $sql = "SELECT cf.total_ht, cf.date_livraison, cfe.options_date_start";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur as cf";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseur_extrafields as cfe ON cf.rowid = cfe.fk_object";
        $sql .= " WHERE cf.fk_sc_projet = " . ((int)$fk_projet);

        $resql = $db->query($sql);
        while ($obj = $db->fetch_object($resql)) {
            $start = !empty($obj->options_date_start) ? new DateTimeImmutable(dol_print_date($obj->options_date_start, '%Y-%m-%d')) : null;
            $end = !empty($obj->date_livraison) ? new DateTimeImmutable(dol_print_date($obj->date_livraison, '%Y-%m-%d')) : null;

            if ($start && $end && $obj->total_ht > 0) {
                $spread = $this->spreadAmountOverMonths($start, $end, $obj->total_ht);
                foreach ($spread as $month => $val) $data[$month] = ($data[$month] ?? 0) + $val;
            }
        }

        return $data;
    }

    public function fetchPropalOpen($fk_projet)
    {
        global $db;
        $data = [];

        $sql = "SELECT p.total_ht, p.date_livraison, pfe.datedmarrage";
        $sql .= " FROM " . MAIN_DB_PREFIX . "propal as p";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "propal_extrafields as pfe ON p.rowid = pfe.fk_object";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as pr ON pr.rowid = p.fk_projet";
        $sql .= " WHERE p.fk_statut = 1 AND p.fk_projet = " . ((int)$fk_projet);
        // $sql .= " AND pr.dateo <= pfe.datedmarrage AND pr.datee >=  p.date_livraison";

        $resql = $db->query($sql);
        while ($obj = $db->fetch_object($resql)) {
            $start = !empty($obj->datedmarrage) ? new DateTimeImmutable(dol_print_date($obj->datedmarrage, '%Y-%m-%d')) : null;
            $end = !empty($obj->date_livraison) ? new DateTimeImmutable(dol_print_date($obj->date_livraison, '%Y-%m-%d')) : null;

            if ($start && $end && $obj->total_ht > 0) {
                $spread = $this->spreadAmountOverMonths($start, $end, $obj->total_ht);
                foreach ($spread as $month => $val) $data[$month] = ($data[$month] ?? 0) + $val;
            }
        }

        return $data;
    }

    public function fetchPropalSigned($fk_projet)
    {
        global $db;
        $data = [];

        $sql = "SELECT p.total_ht, p.date_livraison, pfe.datedmarrage";
        $sql .= " FROM " . MAIN_DB_PREFIX . "propal as p";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "propal_extrafields as pfe ON p.rowid = pfe.fk_object";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as pr ON pr.rowid = p.fk_projet";
        $sql .= " WHERE p.fk_statut = 2 AND p.fk_projet = " . ((int)$fk_projet);
        // $sql .= " AND pr.dateo <= pfe.datedmarrage AND pr.datee >=  p.date_livraison";

        $resql = $db->query($sql);
        while ($obj = $db->fetch_object($resql)) {
            $start = !empty($obj->datedmarrage) ? new DateTimeImmutable(dol_print_date($obj->datedmarrage, '%Y-%m-%d')) : null;
            $end = !empty($obj->date_livraison) ? new DateTimeImmutable(dol_print_date($obj->date_livraison, '%Y-%m-%d')) : null;

            if ($start && $end && $obj->total_ht > 0) {
                $spread = $this->spreadAmountOverMonths($start, $end, $obj->total_ht);
                foreach ($spread as $month => $val) $data[$month] = ($data[$month] ?? 0) + $val;
            }
        }

        return $data;
    }

    public function fetchFacture($fk_projet)
    {
        global $db;
        $data = [];

     $sql = "SELECT f.rowid, DATE_FORMAT(f.datef,'%Y-%m') as dm, SUM(f.total_ht) as amount_ht, f.fk_projet";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON f.fk_projet = p.rowid";
        $sql .= " WHERE f.fk_projet = " . ((int)$fk_projet);
		$sql .= " AND f.fk_soc = s.rowid"; 
		
		$sql.= " GROUP BY DATE_FORMAT(f.datef,'%Y-%m')";

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $data[$obj->dm] = $obj->amount_ht;
            }
        } else {
            dol_print_error($db, 'Erreur SQL : '.$sql);
        }

        return $data;
    }

    // public function fetchFacture($fk_projet)
    // {
    //     global $db;
    //     $data = [];

    //     $sql = "SELECT f.total_ht, f.datef";
    //     $sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
    //     $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON f.fk_projet = p.rowid";
    //     $sql .= " WHERE f.fk_projet = " . ((int)$fk_projet);
    //     // $sql .= " AND f.datef BETWEEN p.dateo AND p.datee";

    //     $resql = $db->query($sql);
    //     while ($obj = $db->fetch_object($resql)) {
    //         $start = !empty($obj->datef) ? new DateTimeImmutable(dol_print_date($obj->datef, '%Y-%m-%d')) : null;
    //         if ($start && $obj->total_ht > 0) {
    //             $spread = $this->spreadAmountOverMonths($start, $start, $obj->total_ht);
    //             foreach ($spread as $month => $val) $data[$month] = ($data[$month] ?? 0) + $val;
    //         }
    //     }

    //     return $data;
    // }

    public function fetchFactureDraft($fk_projet)
    {
        global $db;
        $data = [];

        $sql = "SELECT f.total_ht, f.datef";
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON f.fk_projet = p.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_extrafields as fe ON f.rowid = fe.fk_object";
        $sql .= " WHERE f.fk_statut = 0 AND (fe.pv_reception = '0' OR fe.pv_reception IS NULL)";
        $sql .= " AND f.fk_projet = " . ((int)$fk_projet);
        // $sql .= " AND f.datef BETWEEN p.dateo AND p.datee";

        $resql = $db->query($sql);
        while ($obj = $db->fetch_object($resql)) {
            $start = !empty($obj->datef) ? new DateTimeImmutable(dol_print_date($obj->datef, '%Y-%m-%d')) : null;

            if ($start && $obj->total_ht > 0) {
                $spread = $this->spreadAmountOverMonths($start, $start, $obj->total_ht);
                foreach ($spread as $month => $val) $data[$month] = ($data[$month] ?? 0) + $val;
            }
        }

        return $data;
    }


    public function fetchFacturePv($fk_projet)
    {
        global $db;
        $data = [];

        $sql = "SELECT f.total_ht, f.datef";
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_extrafields as fe ON f.rowid = fe.fk_object";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON p.rowid = f.fk_projet";
        $sql .= " WHERE fe.pv_reception = 1 AND f.fk_statut = 0";
        $sql .= " AND f.fk_projet = " . ((int)$fk_projet);
        // $sql .= " AND f.datef BETWEEN p.dateo AND p.datee";

        $resql = $db->query($sql);
        while ($obj = $db->fetch_object($resql)) {
            $start = !empty($obj->datef) ? new DateTimeImmutable(dol_print_date($obj->datef, '%Y-%m-%d')) : null;

            if ($start && $obj->total_ht > 0) {
                $spread = $this->spreadAmountOverMonths($start, $start, $obj->total_ht);
                foreach ($spread as $month => $val) $data[$month] = ($data[$month] ?? 0) + $val;
            }
        }

        return $data;
    }

    public function fetchFactureFourn($fk_projet)
    {
        global $db, $conf;
        $data = [];

        $sql = "SELECT faf.total_ht, faf.datef";
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as faf";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON p.rowid = faf.fk_projet";
        $sql .= " WHERE faf.fk_projet = " . ((int)$fk_projet);
        $sql .= " AND faf.fk_statut IN (1, 2)";
        if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
            $sql .= " AND faf.type IN (0, 1, 2, 5)";
        } else {
            $sql .= " AND faf.type IN (0, 1, 2, 3, 5)";
        }
      
        // $sql .= " AND faf.datef BETWEEN p.dateo AND p.datee";

        $resql = $db->query($sql);
        while ($obj = $db->fetch_object($resql)) {
            $start = !empty($obj->datef) ? new DateTimeImmutable(dol_print_date($obj->datef, '%Y-%m-%d')) : null;

            if ($start && $obj->total_ht > 0) {
                $spread = $this->spreadAmountOverMonths($start, $start, $obj->total_ht);
                foreach ($spread as $month => $val) $data[$month] = ($data[$month] ?? 0) + $val;
            }
        }

        return $data;
    }


}