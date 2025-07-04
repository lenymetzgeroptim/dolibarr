<style>
.select2-container--default .select2-search--inline .select2-search__field {
	width:100% !important;
}
</style>
<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * addon 2023 Soufiane Fadel <s.fadel@optim-industries.fr>
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
 *	\file       htdocs/core/boxes/box_graph_orders_permonth.php
 *	\ingroup    comm
 *	\brief      Box to show graph of commercial indicators by year
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
//
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/indicateur.class.php';
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/filtringindicateur.class.php';

/**
 * Class to manage the box to show last orders
 */
class box_graph_indic_rentabilite extends ModeleBoxes
{
	public $boxcode = "indic";
	public $boximg = "fa-chart-line";
	public $boxlabel = "Indicateurs";

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	
	public $info_box_head = array();
	public $info_box_contents = array();

	public $widgettype = 'graph';


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		//$this->hidden = !($user->rights->commande->lire);
	}

	/**
	 * get nb of emplyees 
	 * 
	 * @return string sql
	 * 
	 */
	// public function stringSQL($option, $arr_agences, $arr_resp, $arr_proj, $date_start, $date_end)
	// {
	// 	if($option == 'all' && (empty($arr_agences) || empty($arr_resp) || empty($arr_proj))) {
	// 		$sql = "SELECT sum(g.total_employee), DATE_FORMAT(g.year, '%Y-%m') as dm";
	// 		$sql .= " FROM ".MAIN_DB_PREFIX."comm_element_graph AS g";

	// 		$sql .= " WHERE g.year >= '".$this->db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
	// 		$sql .= " AND g.year <= '".$this->db->idate($date_end)."'";
	// 		$sql .= " GROUP BY dm";
	// 	 }

	// 	if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
	// 		$i = 1;
	
	// 		$sql = "SELECT count(*), DATE_FORMAT(t.element_date,'%Y-%m') as dm";
	// 		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
	// 		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
	// 		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
	// 		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";

	// 		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
	// 		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on ec.fk_socpeople = u.rowid";
	// 		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";

	// 		$sql .= " WHERE 1 = 1";

	// 		if(!empty($arr_agences) && $option == 'all') {
	// 			$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
	// 		}

	// 		if(!empty($arr_proj) && $option == 'all') {
	// 			$sql .= " AND pe.fk_object IN (".implode(', ', $arr_proj).")";
	// 		}

	// 		if(!empty($arr_resp) && $option ='all') {
	// 			$sql .= " AND tc.element = 'project'";
	// 			$sql .= " AND tc.source = 'internal'";
	// 			$sql .= " AND tc.code = 'PROJECTLEADER'";
	// 			$sql .= " AND ec.fk_socpeople IN (".implode(', ', $arr_resp).")";
	// 		}

			
	
	// 		if($option == 'resp') {
	// 			$sql .= " AND";
	// 			if(!empty(array_filter($this->getAgencesBySoc()))) {
	// 				$agences = array_unique($this->getAgencesBySoc());
	// 				array_filter($agences);

	// 				foreach($agences as $manager => $agence) {
	// 					$sql .= " pe.agenceconcerne =".((int) $agence);
	// 					if($i < sizeof($agences)) {
	// 						$sql .= " AND";
	// 					}

	// 					$i++;
	// 				}
	// 			}
	// 		}

	// 		$sql .= " AND t.element_date >= '".$this->db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
	// 		$sql .= " AND t.element_date <= '".$this->db->idate($date_end)."'";
	// 		$sql .= " AND t.elementtype = 'task'";
	// 		$sql .= " GROUP BY dm, ec.fk_socpeople";
	// 	}

	
	// 	$sql .= " ORDER BY dm";

	// 	return $sql;
	// }

	public function stringSQL($option, $arr_agences, $arr_resp, $arr_proj, $date_start, $date_end)
	{
		// if($option == 'all' && (empty($arr_agences) && empty($arr_resp) && empty($arr_proj))) {
		// 	$sql = "SELECT sum(g.total_employee), DATE_FORMAT(g.year, '%Y-%m') as dm";
		// 	$sql .= " FROM ".MAIN_DB_PREFIX."comm_element_graph AS g";

		// 	$sql .= " WHERE g.year >= '".$this->db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
		// 	$sql .= " AND g.year <= '".$this->db->idate($date_end)."'";
			
		//  }

		 if($option == 'all' && (empty($arr_agences) && empty($arr_resp) && empty($arr_proj))) {
			$sql = "SELECT sum(t.element_duration / 3600) / 151.67 as total_employee, DATE_FORMAT(t.element_date, '%Y-%m') as dm";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_time AS t";
			$sql .= " WHERE t.element_date >= '".$this->db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
			$sql .= " AND t.element_date <= '".$this->db->idate($date_end)."'";
			$sql .= " AND t.elementtype = 'task'";
			// $sql .= " GROUP BY dm";
		}

		if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
			$i = 1;
	
			$sql = "SELECT count(DISTINCT t.fk_user), DATE_FORMAT(t.element_date,'%Y-%m') as dm";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
			// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = t.fk_user";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";

			
			if(empty($arr_resp) && $option ='all') {
				$sql .= " WHERE 1 = 1";
			}

			if(!empty($arr_resp) && $option ='all') {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on ec.fk_socpeople = u.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
				$sql .= " WHERE 1 = 1";
				$sql .= " AND tc.element = 'project'";
				$sql .= " AND tc.source = 'internal'";
				$sql .= " AND tc.code = 'PROJECTLEADER'";
				$sql .= " AND ec.fk_socpeople IN (".implode(', ', $arr_resp).")";
			}

			if(!empty($arr_agences) && $option == 'all') {
				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
			}

			if(!empty($arr_proj) && $option == 'all') {
				$sql .= " AND pe.fk_object IN (".implode(', ', $arr_proj).")";
			}
	
			if($option == 'resp') {
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);

					foreach($agences as $manager => $agence) {
						$sql .= " pe.agenceconcerne =".((int) $agence);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}

						$i++;
					}
				}
			}

			$sql .= " AND t.element_date >= '".$this->db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
			$sql .= " AND t.element_date <= '".$this->db->idate($date_end)."'";
			$sql .= " AND t.elementtype = 'task'";
		}
		
		// if(!empty($arr_resp) && $option ='all') {
		// 	$sql .= " GROUP BY dm, u.rowid";
		// }else{
		// 	$sql .= " GROUP BY dm";
		// }
		
		$sql .= " GROUP BY dm";
		$sql .= " ORDER BY dm";

		return $sql;
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
 	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;
		
		$this->max = $max;
		$refreshaction = 'reset_'.$this->boxcode;

		$arr_agences = GETPOST('arr_agences');
		$arr_resp = GETPOST('arr_resp');
		$arr_proj = GETPOST('arr_proj');

		$indicateur = new Indicateur($db);
		$agences = $indicateur->getAgences();
		$respids = $indicateur->getResp();
		$postresp = $indicateur->getResp();
		$projects = $indicateur->select_multi_projects();
		$agencesids = $indicateur->getAgencesBySoc();

		// $modeFiltring = empty($arr_agences) || empty($arr_resp) || empty($arr_proj);
		// $modeNotFiltring = empty($arr_agences) && empty($arr_resp) && empty($arr_proj);
		$respright = !$user->hasRight('addoncomm', 'incateurs_box_graph', 'readall') && (array_key_exists($user->id, $agencesids) || array_key_exists($user->id, $respids));

		//$text = $langs->trans("BoxCustomersCommPerYear", $max);
		$text = "Indicateurs commerciaux";
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1,
				'sublink'=>'',
				'subtext'=>$langs->trans("Filter"),
				'subpicto'=>'filter.png',
				'subclass'=>'linkobject boxfilter',
				'target'=>'none'	// Set '' to get target="_blank"
		);
	
		$prefix = '';
		$socid = 0;
		if ($user->socid) {
			$socid = $user->socid;
		}
		if (empty($user->rights->societe->client->voir) || $socid) {
			$prefix .= 'private-'.$user->id.'-'; // If user has no permission to see all, output dir is specific to user
		}
	
		if($user->hasRight('addoncomm', 'incateurs_box_graph', 'read')) { 
			$langs->load("orders");

			$param_year = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
			$param_shownb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_shownb';
			
			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			
			$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
			if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode, $autosetarray)) {
				$endyear = GETPOST($param_year, 'int');
				$shownb = GETPOST($param_shownb, 'alpha');
			} else {
				$tmparray = (!empty($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode]) ? json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode], true) : array());
				$endyear = (!empty($tmparray['year']) ? $tmparray['year'] : '');
				$shownb = (!empty($tmparray['shownb']) ? $tmparray['shownb'] : '');
			}
			if (empty($shownb)) {
				$shownb = 1;
			}
			$nowarray = dol_getdate(dol_now(), true);
			if (empty($endyear)) {
				$endyear = $nowarray['year'];
			}
			$startyear = $endyear - (empty($conf->global->MAIN_NB_OF_YEAR_IN_WIDGET_GRAPH) ? 2 : ($conf->global->MAIN_NB_OF_YEAR_IN_WIDGET_GRAPH - 1));

			$mode = 'customer';
			$WIDTH = (($shownb) || !empty($conf->dol_optimize_smallscreen)) ? '880' : '220';
			$HEIGHT = '500';

			// Get all employee's salaries by month for x year
			require_once DOL_DOCUMENT_ROOT.'/salaries/class/salariesstats.class.php';
			$nowyear = strftime("%Y", dol_now());
			$year = GETPOST('year') > 0 ? GETPOST('year') : $nowyear;
			$startyear = $year - (empty($conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS) ? 2 : max(1, min(10, $conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS)));
			$endyear = $year;

			$userid = GETPOST('userid', 'int');
			$useridtofilter = $userid; // Filter from parameters
			if ($userid < 0) {
				$userid = 0;
			}
			if (empty($user->rights->salaries->readall) && empty($useridtofilter)) {
				$useridtofilter = $user->getAllChildIds(1);
			}
			
			$expense_year = array();
			$expense_avg = array();
		
			$stats = new SalariesStats($db, $socid, $useridtofilter);
			$data = $stats->getAllByYear($endyear, $startyear);
			sort($data);

			//option to display data by rights
			if($respright) {
				$option = 'resp';
			}else{
				$option = 'all';
			}

			//data whether to be filtered or not
			$indicators = $indicateur->getValues($option, $arr_agences, $arr_resp, $arr_proj);

			//data for feltring by date
			$now = dol_now();
			$date_startmonth = GETPOST('start_datemonth', 'int');
			$date_startday = GETPOST('start_dateday', 'int');
			$date_startyear = GETPOST('start_dateyear', 'int');
			$date_endmonth = GETPOST('end_datemonth', 'int');
			$date_endday = GETPOST('end_dateday', 'int');
			$date_endyear = GETPOST('end_dateyear', 'int');
		
			$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
			$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);
		
			if (empty($date_start)) {
				$date_start = dol_time_plus_duree($now, -3, 'y');
			}
			
			if (empty($date_end)) {
				$date_end = dol_time_plus_duree($now, 0, 'y');
			}
			
			
			$form = new Form($db);
			$formproject = new FormProjets($db);
			
			//displayon result whith last -1 month
			// if(!empty($factures)) {
			// 	unset($factures[count($factures)-1]);
			// }
	
			//query to draw a diagram using new reconstructed data  
			$i = 0;
			if ($shownb) {
				 
				$sql = $this->stringSQL($option, $arr_agences, $arr_resp, $arr_proj, $date_start, $date_end);
			
				$resql = $db->query($sql);
			

				if ($resql) {
					$num = $db->num_rows($resql);
					$i = 0;
					
					$dataseries = array();
					$vals = array();
		
					while ($i < $num) {
						$row = $db->fetch_row($resql);
						// $obj = $db->fetch_object($resql);
						
	
						if ($row) {
							$key = $row[1];
							$years = substr($row[1], 0, 4);

							$vals['facture'] = null;
							$vals['expense'] = null;
							$vals['facture_fourn'] = null;
							$vals['soc'] = null;
							$vals['note'] = null;
							$vals['donation'] = null;
							$vals['vartious'] = null;
							$vals['loan'] = null;
							
							foreach($indicators as $name => $values) {
								foreach($values as $expense) {
									switch ($name) {
										case 'salaries':
											strcasecmp($expense['date'], $key) == 0 ? $vals['expense'] = $expense['amount'] : 0; 
										break;
										case 'facture':
											strcasecmp($expense['date'], $key) == 0 ? $vals['facture'] = $expense['amount'] : 0; 
										break;
										case 'facture_fourn':
											strcasecmp($expense['date'], $key) == 0 ? $vals['facture_fourn'] = $expense['amount'] : 0; 
										break;
										case 'soc':
											strcasecmp($expense['date'], $key) == 0 ? $vals['soc'] = $expense['amount'] : 0; 
										break;
										case 'note':
											strcasecmp($expense['date'], $key) == 0 ? $vals['note'] = $expense['amount'] : 0; 
										break;
										case 'donation':
											strcasecmp($expense['date'], $key) == 0 ? $vals['donation'] = $expense['amount'] : 0; 
										break;
										case 'various':
											strcasecmp($expense['date'], $key) == 0 ? $vals['various'] = $expense['amount'] : 0; 
										break;
										case 'loan':
											strcasecmp($expense['date'], $key) == 0 ? $vals['loan'] = $expense['amount'] : 0; 
										break;
									}
								}
							}

							$vals['recettes'] = round($vals['facture'], 2);
							$vals['depense'] = round($vals['facture_fourn'] + $vals['expense'] + $vals['soc'] + $vals['note'] + $vals['donation'] + $vals['various'] + $vals['loan'], 2);
							$vals['nb_employee'] = $row[0];
							$listofcomm[$key] = $vals;

							//mm - 1 compared to the value
							if($i < $num -1) {
								//new array to calculate average of values
								$m_1 = strtotime($key);
								if(strtotime($key) == strtotime($key)) {
									$listofcommavg[$years]['recettes'][] = round($vals['facture'], 2);
									$listofcommavg[$years]['depenses'][] = round($vals['facture_fourn'] + $vals['expense'] + $vals['soc'] + $vals['note'] + $vals['donation'] + $vals['various'] + $vals['loan'], 2);
									$listofcommavg[$years]['expenses'][] = $vals['expense'];
									$listofcommavg[$years]['nb_employees'][] = $vals['nb_employee'];
								}
							}
							
							//mm - 1 compared to the today date
							//new array to calculate average of values
							// if(strtotime($key) < strtotime(dol_print_date($now, 'Y-m'))) {
							// 	$listofcommavg[$years]['recettes'][] = round($vals['facture'], 2);
							// 	$listofcommavg[$years]['depenses'][] = round($vals['facture_fourn'] + $vals['expense'] + $vals['soc'] + $vals['note'] + $vals['donation'] + $vals['various'] + $vals['loan'], 2);
							// 	$listofcommavg[$years]['expenses'][] = $vals['expense'];
							// 	$listofcommavg[$years]['nb_employees'][] = $vals['nb_employee'];
							// }
						}
						$i++;
					}

					//average calculating
					foreach($listofcommavg as $key => $values) {
				
						$avgrecettes =  sizeof($values['recettes']) > 0 ? array_sum(array_filter($values['recettes'])) / sizeof($values['recettes']) : null;
						$avgdepenses =  sizeof($values['depenses']) > 0 ? array_sum(array_filter($values['depenses'])) / sizeof($values['depenses']) : null;
						$avgexpenses =  sizeof($values['expenses']) > 0 ? array_sum(array_filter($values['expenses'])) / sizeof($values['expenses']) : null;
						$nbemployees =  sizeof($values['nb_employees']) ? array_sum(array_filter($values['nb_employees'])) / sizeof($values['nb_employees']) : null;
		
						$avg[$key.'_'.'rent'] = $avgrecettes > 0 || $avgrecettes != null ? (($avgrecettes - $avgdepenses) / $avgrecettes) : null;
						$avg[$key.'_'.'cams'] = $avgrecettes > 0 || $avgrecettes != null ? (($avgrecettes - $avgexpenses) / $avgrecettes) : null;
						$avg[$key.'_'.'etp'] = $nbemployees > 0 || $nbemployees != null ? $avgrecettes / $nbemployees : null;
					}
					// var_dump($listofcomm);
					foreach($listofcomm as $key => $value) {
						$years = substr($key, 0, 4);

						// strtotime(dol_print_date($now, 'Y-m'))
						foreach($avg as $year => $avgvalue) {
							if($year == substr($key, 0, 4).'_'.'rent') {
								$avrgrent = $value['facture'] > 0 && strtotime($key) <= $m_1 ? $avgvalue : null;
								
							}

							if($year == substr($key, 0, 4).'_'.'cams') {
								$avrgcams = $value['facture'] > 0  && strtotime($key) <= $m_1 ? $avgvalue : null;
							}

							if($year == substr($key, 0, 4).'_'.'etp') {
								$avrgetp = $value['nb_employee'] > 0 && $value['facture'] >= 0 && strtotime($key) <= $m_1 ? $avgvalue : null;
							}
						}
						
						$depense = $value['depense'];
						$recette = $value['facture'];
						
						$rent = $recette > 0  ? (($recette - $depense) / $recette) : null;
						$tauxetp = $value['nb_employee'] > 0 && $recette >= 0 ? $recette / $value['nb_employee'] : null;
						$cams = $recette > 0 ? (($recette - $value['expense']) / $recette) : null;

						$dataseries[] = array($key, $rent, $avrgrent, $tauxetp, $avrgetp, $cams, $avrgcams);
						
					}


					$db->free($resql);
					include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				
					if ($conf->use_javascript_ajax) {
						$labels = array('Ind. rentabilité', 'Moy. rentabilité', 'Ind. TAUX / ETP', 'Moy. TAUX / ETP', 'Ind. CA / MS', 'Moy. CA / MS');
						
						if ($shownb) {
						$px1 = new DolGraph();
						$mesg = $px1->isGraphKo();
							
							if (!$mesg) {
								if ($conf->use_javascript_ajax) {
									$px1 = new DolGraph();
									$px1->SetData(array_values($dataseries));
									$px1->SetDataColor(array('#177F00', '#29D404', '#0005FF', '#04D0D4', '#FF0202', '#FD7F7F'));
									//$px1->setShowLegend(1);
									$px1->setShowPercent(1);
									$px1->SetType(array('lines', 'linesnopoint', 'lines', 'linesnopoint', 'lines', 'linesnopoint'));
									$px1->setHeight($HEIGHT);
									$px1->SetWidth($WIDTH);
									$px1->SetLegend(array_values($labels));
									//$px1->setTooltipsLabels();
									$px1->mode = 'depth';
									$px1->draw('infographindicateurs');
								}
							}
						}		
					}		
				}else {
					dol_print_error($db);
				}		
			}

			if (empty($conf->use_javascript_ajax)) {
				$langs->load("errors");
				$mesg = $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
			}

			if (!$mesg) {
				// filtring form by date
				$stringtoshow = '';
				$stringtoshow .= '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimg'.$this->boxcode.'").click(function() {
							jQuery("#idfilt'.$this->boxcode.'").toggle();
						});
					});
					</script>';
				
				$stringtoshow .= '<div class="center hideobject" id="idfilt'.$this->boxcode.'">'; // hideobject is to start hidden
				$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'#boxhalfleft">';
				$stringtoshow .= '<input type="hidden" name="token" value="'.newToken().'">';
				$stringtoshow .= '<input type="hidden" name="action" value="'.$refreshaction.'">';
				$stringtoshow .= '<input type="hidden" name="page_y" value="">';
				$stringtoshow .= $form->selectDate($date_start, 'start_date', 0, 0, 1, '', 1, 0).' &nbsp; '.$form->selectDate($date_end, 'end_date', 0, 0, 0, '', 1, 0);
				$stringtoshow .= ' &nbsp; ';
				$stringtoshow .= '<br><br>';
				// $stringtoshow .= img_picto('', 'group', 'class="pictofixedwidth"');
				// $stringtoshow .= $form->multiselectarray('arr_agences',  $agences,  $arr_agences, '', '', '', '', '25%', '','', 'Agences');
				// $stringtoshow .= ' &nbsp; ';
				// $stringtoshow .= img_picto('', 'group', 'class="pictofixedwidth"');
				// $stringtoshow .= $form->multiselectarray('arr_resp',  array_unique($postresp),  $arr_resp, '', '', '', '', '25%', '','', 'Res.suivi');
				// $stringtoshow .= ' &nbsp; ';
				// $stringtoshow .= img_picto('', 'project', 'class="pictofixedwidth"');
				// $stringtoshow .= $form->multiselectarray('arr_proj',  $projects,  $arr_proj, '', '', '', '', '25%', '','', 'Affaires');
				// $stringtoshow .= ' &nbsp; ';
				// // $stringtoshow .= img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $arr_proj, 'arr_proj', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, '25%');
				// $stringtoshow .= '<br><br>';
				$stringtoshow .= '<input type="submit" class="smallpaddingimp button" name="submit" value="Rafraichir">';
				$stringtoshow .= '<br>';
				$stringtoshow .= '</form>';
				$stringtoshow .= '</div>';

				if ($shownb) {
					$stringtoshow .= '<div class="fichecenter">';
					$stringtoshow .= '<div class="fichehacenter">';
				}
				if ($shownb) {
					$stringtoshow .= $px1->show();
				}
				if(empty($dataseries)) {
					$stringtoshow .= '<div>'.$langs->trans("Enregistrement non trouvé").'</div>';
				}
			
				if ($shownb) {
					$stringtoshow .= '</div>';
					$stringtoshow .= '<div">';
				}
				
				if ($shownb) {
					$stringtoshow .= '</div>';
					$stringtoshow .= '</div>';
				}
				$this->info_box_contents[0][0] = array(
					'tr'=>'class="oddeven nohover"',
					'td' => 'class="nowraponall center"',
					'textnoformat'=>$stringtoshow,
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'tr'=>'class="oddeven nohover"',
					'td' => 'class="nohover left"',
					'maxlength'=>500,
					'text' => $mesg,
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover opacitymedium left"',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}


	/**
	 *  Method to show box
	 *
	 *  @param	array	$head       Array with properties of box title
	 *  @param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *  @return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		global $user;

		if($user->hasRight('addoncomm', 'incateurs_box_graph', 'read')) { 
			return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
		}
	}
}


