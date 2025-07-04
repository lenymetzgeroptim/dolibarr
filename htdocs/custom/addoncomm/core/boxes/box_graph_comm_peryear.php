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
 *	\brief      Box to show graph of commercial data by year
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/indicateur.class.php';
include_once DOL_DOCUMENT_ROOT.'/custom/addoncomm/class/linedolgraph.class.php';

/**
 * Class to manage the box to show last orders
 */
class box_graph_comm_peryear extends ModeleBoxes
{
	public $boxcode = "commsperyear";
	public $boximg = "fa-chart-line";
	public $boxlabel = "Suivi commercial";

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

	private function convertToYearMonthArray($originalArray, $year)
	{
		$convertedArray = [];

		foreach ($originalArray as $entry) {
			$month = str_pad($entry[0], 2, '0', STR_PAD_LEFT); // Formate le mois en 01, 02,
			$amount = $entry[1];

			// Format de la clé : 'année-mois'
			$key = $year . '-' . $month;

			$convertedArray[$key] = $amount;
		}

		return $convertedArray;
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
		$refreshaction = 'refresh_'.$this->boxcode;

		$arragences = GETPOST('arragences');
		$arrresp = GETPOST('arrresp');
		$arrproj = GETPOST('arrproj');

		$indicateur = new Indicateur($db);
		$listagences = $indicateur->getAgences();
		$respids = $indicateur->getResp();
		$listresp = $indicateur->getResp();
		$listproj = $indicateur->select_multi_projects();
		// $agencesids = $indicateur->getAgencesBySoc();

		$linecomm = new LineDolGraph($db);
		
		
		// $respright = !$user->hasRight('widgetindicateur', 'incateurs_box_graph', 'readall') && (array_key_exists($user->id, $agencesids) || array_key_exists($user->id, $respids));

		//$text = $langs->trans("BoxCustomersCommPerYear", $max);
		$text = "Suivi commercial";
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

		

		//if ($user->rights->commande->lire) {
		if($user->rights->addoncomm->box_graph_comm_peryear->afficher) { 
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
			
			$modeFiltring = !empty($arragences) || !empty($arrresp) || !empty($arrproj);
			$modeNotFiltring = empty($arragences) && empty($arrresp) && empty($arrproj);
			// $respright = !$user->hasRight($user->rights->addoncomm->box_graph_comm_peryear->readall) && (array_key_exists($user->id, $agencesids) || array_key_exists($user->id, $respids));
			if($respright) {
				$option = 'resp';
				$costs = $linecomm->employeeCostByProject($option, $arragences, $arrresp,  $arrproj, $date_start, $date_end);
			}else{
				$option = 'all';
				if($modeNotFiltring) {
					$data = $stats->getAllByYear($endyear, $startyear);
					sort($data);
				}elseif($modeFiltring){
					$costs = $linecomm->employeeCostByProject($option, $arragences, $arrresp,  $arrproj, $date_start, $date_end);
				}
			}
		
			//data for feltring by date
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
			
			$form = new Form($db);

			
		// // Get grouped data for graph
		// $values = $linecomm->getValues($arragences, $arrresp, $arrproj, $date_start, $date_end, $option);
		// // var_dump($values);
		// foreach($values as $key => $scopes) { 			
		// 	foreach($scopes as  $val) {
				
		// 		isset($val['total']) ? $val['total'] = $val['total'] :  $val['total'] = 0;
		// 		switch ($key) {
		// 			case 'commande':
		// 				$vals[$val['date']]['commande'] = $val['total'] ; 
		// 			break;
		// 			case 'commande_fournisseur':
		// 				$vals[$val['date']]['supplier'] = $val['total']; 
		// 			break;
		// 			case 'propal_open':
		// 				$vals[$val['date']]['open'] = $val['total']; 
		// 			break;
		// 			case 'propal_signed':
		// 				$vals[$val['date']]['signed'] = $val['total']; 
		// 			break;
		// 			case 'facture':
		// 				$vals[$val['date']]['facture'] = $val['total']; 
		// 			break;
		// 			case 'facture_pv':
		// 				$vals[$val['date']]['facture_pv'] = $val['total']; 
		// 			break;
		// 			case 'facture_draft':
		// 				$vals[$val['date']]['facture_d'] = $val['total']; 
		// 			break;
		// 			case 'facture_fournisseur':
		// 				$vals[$val['date']]['facture_d'] = $val['total']; 
		// 			break;
		// 		}

		// 	}
		// }
		// var_dump($now);	
		foreach($linecomm->sumfields as  $key => $scopes) { 
			if($key == 'commande') {
				$values[$key] = $linecomm->getValues($arragences, $arrresp, $arrproj, $date_start, $date_end, $option, $scopes['total'], $scopes['fk_projet'], $scopes['date_livraison']);	
			}else{
				$values[$key] = $linecomm->getValues($arragences, $arrresp, $arrproj, $date_start, $date_end, $option, $scopes['total'], $scopes['fk_projet']);	
			}	
		}
		

		$vals = array();
		foreach($values as $key => $arr) {
			foreach($arr as $val) {
		
				isset($val['total']) ? $val['total'] = $val['total'] :  $val['total'] = 0;
				switch ($key) {
					case 'commande':
						$vals[$val['date']]['commande'] = $val['total']; 
						if($val['date_livraison'] != '') {
							$vals[$val['date']]['commmande_restante'] = $val['date_livraison'];
						}
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

		foreach($vals as $key => $value) {
			$years = substr($key, 0, -3);
			$yearsArr[$years] = $years;
		}
		//salaries by month
		array_unique($yearsArr);
		foreach($yearsArr as $year) {
			// $salbymonth[] = $this->convertToYearMonthArray($stats->getAmountByMonth($year, 01), $year);
			// Remplace la fonction native ($stats->getAmountByMonth($year, 01))protégée par le droit "Lire les salaires et leur paiement de tout le monde". Cette version fournit un accès limité, sans afficher les salaires nominaux. Elle est protégée par le droit d'accès au graphique
			$salbymonth[] = $this->convertToYearMonthArray($linecomm->getAmountByMonthNoDetails($year, 01), $year);	
		}
		

		foreach($salbymonth as $sals) {
			foreach($sals as $key => $sal) {
					$salaires[$key] = $sal;
			}
		}
		
	
		foreach($vals as $key => $salaire) {
			$vals[$key]['expense'] = $salaires[$key] > 0 ? $salaires[$key] : null;
		}
		
		
		//calculate the sum and average of invoices if date is less than now
		foreach($vals as $key => $value) {
			$k = explode('-', $key);
			$invoicesDate = new DateTime($key);
			$datenow = new DateTime(dol_print_date($now, 'Y-m'));
			$invoicesDate->format('Y-m') < $datenow->format('Y-m') ? $sumMoy[$k[0]][$k[1]] += $value['facture'] : null;
			
		}

		foreach($sumMoy as $key => $moy) {
			$moyenne[$key] =  array_sum($moy) / sizeof($moy);
		}

		$listofcomm = $vals; 

		
		if ($shownb) {
			$dataseries = array();
			$colorseries = array();
			$dateNow = new DateTime('@' . $now);
			$dateNow->setTimezone(new DateTimeZone(date_default_timezone_get())); // Assurer le fuseau horaire
		
			foreach($listofcomm as $key => $value) {
				$k = explode('-', $key);
					// Conversion de la date de livraison en objet DateTime
					$dateLivraison = DateTime::createFromFormat('Y-m-d', $value['commmande_restante']);

					// Calcul de l’intervalle entre les deux dates
					$interval = $dateNow->diff($dateLivraison);

					// Calcul du nombre de mois entre les deux dates
					$nb_month_commande = $interval->y * 12 + $interval->m;

					// Gérer le signe négatif si la date de livraison est dans le passé
					if ($interval->invert) {
						$nb_month_commande = -$nb_month_commande;
					}
				
					if ($dateLivraison >= $dateNow) {
						$restant = ($nb_month_commande > 0) ? 
							(($value['commande'] - $value['facture']) / $nb_month_commande) : 
							// ($value['commande'] - $value['facture']);
							null;
					}

					// if($user->id == 412) {
					// 	var_dump($value['commmande_restante']);
					// 	var_dump('Result :' .'_'.$restant);
					// 	var_dump('Commande : '.'_'.$value['commande']);
					// 	var_dump('Facture PV :'.'_'.$value['facture']);
					// 	var_dump('nb mois calculé : '.'_'.$nb_month_commande);
					// }
				//show values ​of avrage invoices ​up to today's date
				strtotime($key) <= $now ? $factureMoy = $moyenne[$k[0]] : $factureMoy = null;
				
				$massDevis =  round($value['supplier'] + $value['expense'], 2) > 0 ? round($value['supplier'] + $value['expense'], 2) : null; 
				$dataseries[] = array($key, $value['commande'], $value['commande'] + $value['open'] + $value['signed'], $value['signed'], $value['open'], $value['facture'], $factureMoy, $value['facture_pv'], $value['facture_d'], $value['expense'], $massDevis);
			}
			
		
			include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				
			if ($conf->use_javascript_ajax) {
				$labels = array('Cde Client', 'Cde+Dev.', 'Dev. signé', 'Dev. ouvert', 'Fact. Client Tot.', 'Fact. Client Moy.', 'Fact. attente compta', 'Fact. à venir', 'MS', 'Fact. Four.+MS');
					// var_dump($labels);
				if ($shownb) {
				$px1 = new DolGraph();
				$mesg = $px1->isGraphKo();
					
					if (!$mesg) {
						if ($conf->use_javascript_ajax) {
							$px1 = new DolGraph();
							$px1->SetData(array_values($dataseries));
							$px1->SetDataColor(array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF'));
							//$px1->setShowLegend(1);
							$px1->setShowPercent(1);
							$px1->SetType(array('lines', 'linesnopoint', 'linesnopoint', 'linesnopoint', 'lines', 'linesnopoint', 'linesnopoint', 'linesnopoint', 'linesnopoint', 'linesnopoint'));
							$px1->setHeight($HEIGHT);
							$px1->SetWidth($WIDTH);
							$px1->setLegend(array_values($labels));
							//$px1->setTooltipsLabels();
							$px1->mode = 'depth';
							$px1->draw('infographcomm');
						}
					}
				}		
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
							jQuery("#idfilter'.$this->boxcode.'").toggle();
						});
					});
					</script>';

					$stringtoshow .= '<script type="text/javascript">
					document.onreadystatechange = function () {
						var state = document.readyState
						if (state == "interactive") {
							
							 document.getElementById("contents").style.visibility="hidden";
						} else if (state == "complete") {
							setTimeout(function(){
							   document.getElementById("interactive");
							   document.getElementById("load").style.visibility="hidden";
							   document.getElementById("contents").style.visibility="visible";
							},1000);
						}
					  }
				  	</script>';
				
				$stringtoshow .= '<div class="center hideobject" id="idfilter'.$this->boxcode.'">'; // hideobject is to start hidden
				$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'#boxhalfleft">';
				$stringtoshow .= '<input type="hidden" name="token" value="'.newToken().'">';
				$stringtoshow .= '<input type="hidden" name="action" value="'.$refreshaction.'">';
				$stringtoshow .= '<input type="hidden" name="page_y" value="">';
				$stringtoshow .= $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0).' &nbsp; '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
				$stringtoshow .= ' &nbsp; ';
				$stringtoshow .= '<br><br>';
				// $stringtoshow .= img_picto('', 'group', 'class="pictofixedwidth"');
				// $stringtoshow .= $form->multiselectarray('arragences',  $listagences,  $arragences, '', '', '', '', '25%', '','', 'Agences');
				// $stringtoshow .= ' &nbsp; ';
				// $stringtoshow .= img_picto('', 'group', 'class="pictofixedwidth"');
				// $stringtoshow .= $form->multiselectarray('arrresp',  array_unique($listresp),  $arrresp, '', '', '', '', '25%', '','', 'Res.suivi');
				// $stringtoshow .= ' &nbsp; ';
				// $stringtoshow .= img_picto('', 'project', 'class="pictofixedwidth"');
				// $stringtoshow .= $form->multiselectarray('arrproj',  $listproj,  $arrproj, '', '', '', '', '25%', '','', 'Affaires');
				// $stringtoshow .= ' &nbsp; ';
				// $stringtoshow .= img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $arrproj, 'arr_proj', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, '25%');
				// $stringtoshow .= '<br><br>';
				$stringtoshow .= '<input id="contents" type="submit" class="smallpaddingimp button" name="submit" value="Rafraichir">';
				$stringtoshow .= '<div id="load"><i class="fa fa-refresh fa-spin"></i></div>';
				$stringtoshow .= '</form>';
				$stringtoshow .= '</div>';

				if ($shownb) {
					$stringtoshow .= '<div class="fichecenter">';
					$stringtoshow .= '<div class="fichehacenter">';
				}
				if ($shownb) {
					$stringtoshow .= $px1->show();
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

		if($user->rights->addoncomm->box_graph_comm_peryear->afficher) { 
			return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
		}
	}
}


