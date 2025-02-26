<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *  \file       htdocs/custom/feuilledetemps/class/extendedexport.class.php
 *	\brief      File of class to manage Extended Export
 *  \ingroup	core
 */

require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';

/**
 *	Class to manage Dolibarr users
 */
class ExtendedExportFDT extends Export
{

	public function build_file_bis($user, $model, $datatoexport, $array_selected, $array_filterValue, $sqlquery = '', $array_export_fields, $array_export_TypeFields, $array_export_special)
	{
		// phpcs:enable
		global $conf, $langs, $mysoc, $dirname, $filename, $extrafields;

		$indice = 0;
		asort($array_selected);
		$all_holiday = array();
		$heure_semaine = array();

		dol_syslog(__METHOD__." ".$model.", ".$datatoexport.", ".implode(",", $array_selected));

		// Check parameters or context properties
		if (empty($array_export_fields) || !is_array($array_export_fields) || empty($datatoexport)) {
			$this->error = "ErrorBadParameter";
			dol_syslog($this->error, LOG_ERR);
			return -1;
		}

		// Creation of class to export using model ExportXXX
		$dir = DOL_DOCUMENT_ROOT."/core/modules/export/";
		$file = "export_".$model.".modules.php";
		$classname = "Export".$model;
		require_once $dir.$file;
		$objmodel = new $classname($this->db);

		if($datatoexport == "total_hour_week") {
			if (!empty($conf->global->EXPORT_PREFIX_SPEC)) {
				$filename = $conf->global->EXPORT_PREFIX_SPEC."_".$datatoexport;
			} else {
				$filename = "export_".$datatoexport;
				if(GETPOST('action', 'aZ09') == 'buildalldoc') {
					$filename .= '_'.dol_print_date(dol_now(), '%Y%m%d_%H%M%S');
				}
			}
			if (!empty($conf->global->EXPORT_NAME_WITH_DT)) {
				$filename .= dol_print_date(dol_now(), '%Y%m%d%_%H%M');
			}

			$filename .= '.'.$objmodel->getDriverExtension();
			$dirname = $conf->export->dir_temp.'/'.$user->id;

			$outputlangs = clone $langs; // We clone to have an object we can modify (for example to change output charset by csv handler) without changing original value

			// Open file
			dol_mkdir($dirname);
			$result = $objmodel->open_file($dirname."/".$filename, $outputlangs);

			if ($result >= 0) {
				// Genere en-tete
				$objmodel->write_header($outputlangs);

				foreach($array_selected as $key => $val) {
					if(!isset($array_export_TypeFields[$indice][$key])) {
						$array_export_TypeFields[$indice][$key] = "Text";
					}
				}

				// Genere ligne de titre
				$objmodel->write_title($array_export_fields[$indice], $array_selected, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);

				$userstatic = new User($this->db);
				$object = new FeuilleDeTemps($this->db);
				
				if($array_filterValue["u.firstname"]) {
					$filter["t.firstname"] = $array_filterValue["u.firstname"];
				}
				if($array_filterValue["u.lastname"]) {
					$filter["t.lastname"] = $array_filterValue["u.lastname"];
				}
				$filter["t.statut"] = "1";

				if($datatoexport == "total_hour_week") {
					$date_debut = dol_get_first_day(substr($array_filterValue["week"], 0, 4), substr($array_filterValue["week"], 4, 2));
					$date_debut = dol_time_plus_duree($date_debut, -$conf->global->JOUR_ANTICIPES, 'd');
					$date_debut = dol_get_first_day_week(dol_print_date($date_debut, '%d'), dol_print_date($date_debut, '%m'), dol_print_date($date_debut, '%Y'));
					$date_debut = dol_mktime(-1, -1, -1, $date_debut['first_month'], $date_debut['first_day'], $date_debut['first_year']);  
				}
				// else {
				// 	$date_debut =  dol_get_first_day(substr($array_filterValue["month"], 0, 4), substr($array_filterValue["month"], 4, 2));
				// }
			
				if($datatoexport == "total_hour_week") {
					$date_fin =  dol_get_last_day(substr($array_filterValue["week"], 0, 4), substr($array_filterValue["week"], 4, 2));
					if(dol_print_date($date_fin, '%a') != 'Dim'){
						$date_fin = dol_time_plus_duree($date_fin, 1, 'w');
						$date_fin = dol_get_first_day_week(dol_print_date($date_fin, '%d'), dol_print_date($date_fin, '%m'), dol_print_date($date_fin, '%Y'));
						$date_fin = dol_mktime(-1, -1, -1, $date_fin['first_month'], $date_fin['first_day'], $date_fin['first_year']);  
					}
				}
				// else {
				// 	$date_fin =  dol_get_last_day(substr($array_filterValue["month"], 0, 4), substr($array_filterValue["month"], 4, 2));
				// }

				$userstatic->fetchAll('', 't.lastname', 0, 0, $filter);
				foreach($userstatic->users as $id => $user_obj) {
					if(!$conf->global->FDT_MANAGE_EMPLOYER || ($conf->global->FDT_MANAGE_EMPLOYER && $user_obj->array_options['options_fk_employeur'] == 157)){
						$timeHoliday = $object->timeHolidayWeek($id, $date_debut, $date_fin);
						$timeSpentWeek = $object->timeDoneByWeek($id, $date_debut, $date_fin);
						$societe = new Societe($this->db);
						if(!empty($user_obj->array_options['options_antenne'])) {
							$societe->fetch($user_obj->array_options['options_antenne']);
							$obj->eu_antenne = $societe->name_alias;
						}

						$obj->eu_matricule = $user_obj->array_options['options_matricule'];
						$obj->u_firstname = $user_obj->firstname;
						$obj->u_lastname = $user_obj->lastname;

						$dayinloop = $date_debut;
						if($datatoexport == "total_hour_week") {
							while ($dayinloop <= $date_fin) {							
								$obj->week = date("W", $dayinloop);
								$obj->total_work = ($timeSpentWeek[date("W", $dayinloop)] > 0 ? $timeSpentWeek[date("W", $dayinloop)] : 0);
								$obj->total_holiday = ($timeHoliday[date("W", $dayinloop)] > 0 ? $timeHoliday[date("W", $dayinloop)] : 0);
								$obj->total_hour = $obj->total_work + $obj->total_holiday;

								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
								$dayinloop = dol_time_plus_duree($dayinloop, 1, 'w');
							}	
						}
						// else {
						// 	$total_work = 0;
						// 	$total_holiday = 0;
						// 	$total_hour = 0;
						// 	while ($dayinloop <= $date_fin) {							
						// 		$total_work += $timeSpentWeek[date("W", $dayinloop)];
						// 		$total_holiday += $timeHoliday[date("W", $dayinloop)];
						// 		$total_hour += ($timeHoliday[date("W", $dayinloop)] + $timeSpentWeek[date("W", $dayinloop)]);

						// 		$dayinloop = dol_time_plus_duree($dayinloop, 1, 'w');
						// 	}	
						// 	$obj->month = date("Y-m", $date_fin);
						// 	$obj->total_work = $total_work;
						// 	$obj->total_holiday = $total_holiday;
						// 	$obj->total_hour = $total_hour;
						// 	$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
						// }
					}
				}

				// Genere en-tete
				$objmodel->write_footer($outputlangs);

				// Close file
				$objmodel->close_file();

				return 1;
			} else {
				$this->error = $objmodel->error;
				dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
				return -1;
			}
		}
		if($datatoexport == "total_holiday") {
			if (!empty($conf->global->EXPORT_PREFIX_SPEC)) {
				$filename = $conf->global->EXPORT_PREFIX_SPEC."_".$datatoexport;
			} else {
				$filename = "export_".$datatoexport;
				if(GETPOST('action', 'aZ09') == 'buildalldoc') {
					$filename .= '_'.dol_print_date(dol_now(), '%Y%m%d_%H%M%S');
				}
			}
			if (!empty($conf->global->EXPORT_NAME_WITH_DT)) {
				$filename .= dol_print_date(dol_now(), '%Y%m%d%_%H%M');
			}

			$filename .= '.'.$objmodel->getDriverExtension();
			$dirname = $conf->export->dir_temp.'/'.$user->id;

			$outputlangs = clone $langs; // We clone to have an object we can modify (for example to change output charset by csv handler) without changing original value

			// Open file
			dol_mkdir($dirname);
			$result = $objmodel->open_file($dirname."/".$filename, $outputlangs);

			if ($result >= 0) {
				// Genere en-tete
				$objmodel->write_header($outputlangs);

				foreach($array_selected as $key => $val) {
					if(!isset($array_export_TypeFields[$indice][$key])) {
						$array_export_TypeFields[$indice][$key] = "Text";
					}
				}

				// Genere ligne de titre
				$objmodel->write_title($array_export_fields[$indice], $array_selected, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);

				$userstatic = new User($this->db);
				$object = new FeuilleDeTemps($this->db);
				$holiday = new extendedHoliday($this->db);
				$typesHoliday = $holiday->getTypesNoCP();

				$extrafields = new ExtraFields($this->db);
				if($conf->donneesrh->enabled) {
					$extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
					$userField = new UserField($this->db);
					$userField->table_element = 'donneesrh_Positionetcoefficient';
				}

				if($array_filterValue["u.firstname"]) {
					$filter["t.firstname"] = $array_filterValue["u.firstname"];
				}
				if($array_filterValue["u.lastname"]) {
					$filter["t.lastname"] = $array_filterValue["u.lastname"];
				}

				//$filter["t.statut"] = "1";

				$date_debut = dol_mktime(-1, -1, -1, substr($array_filterValue["date_debut"], 4, 2), substr($array_filterValue["date_debut"], 6, 2), substr($array_filterValue["date_debut"], 0, 4));
				$date_fin = dol_mktime(-1, -1, -1, substr($array_filterValue["date_fin"], 4, 2), substr($array_filterValue["date_fin"], 6, 2), substr($array_filterValue["date_fin"], 0, 4));
				$timeHoliday = $object->timeHolidayForExport($date_debut, $date_fin);
				$total = array();

				$userstatic->fetchAll('', 't.lastname', 0, 0, $filter);
				foreach($userstatic->users as $id => $user_obj) {
					if($conf->donneesrh->enabled) {
						$userField->id = $id;
						$userField->fetch_optionals();
						$date_depart =  $userField->array_options['options_datedepart'];
					}
					else {
						$date_depart =  $user_obj->dateemploymentend;
					}

					if((!$conf->global->FDT_MANAGE_EMPLOYER || ($conf->global->FDT_MANAGE_EMPLOYER && $user_obj->array_options['options_fk_employeur'] == 157)) && (empty($date_depart) || $date_depart >= $date_debut)) {
						$societe = new Societe($this->db);

						$obj->eu_matricule = $user_obj->array_options['options_matricule'];
						$obj->u_firstname = $user_obj->firstname;
						$obj->u_lastname = $user_obj->lastname;
						$obj->eu_antenne = $societe->name_alias;
						$obj->date_debut = $array_filterValue["date_debut"];
						$obj->date_fin = $array_filterValue["date_fin"];

						foreach($typesHoliday as $type) {		
							$code = $type['code'];				
							$obj->$code = ($timeHoliday[$id][$type['code']] > 0 ? $timeHoliday[$id][$type['code']] : 0);
							$total[$type['code']] += $obj->$code;
						}	

						$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
					}
				}

				// Affiche un total en dernière ligne
				$obj->eu_matricule ='TOTAL';
				$obj->u_firstname = '';
				$obj->u_lastname = '';
				$obj->eu_antenne = '';
				$obj->date_debut = $array_filterValue["date_debut"];
				$obj->date_fin = $array_filterValue["date_fin"];
				foreach($typesHoliday as $type) {		
					$code = $type['code'];				
					$obj->$code = ($total[$type['code']] > 0 ? $total[$type['code']] : 0);
				}	
				$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);

				// Genere en-tete
				$objmodel->write_footer($outputlangs);

				// Close file
				$objmodel->close_file();

				return 1;
			} else {
				$this->error = $objmodel->error;
				dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
				return -1;
			}
		}
		else {
			if (!empty($sqlquery)) {
				$sql = $sqlquery;
			} else {
				if($datatoexport == 'ObservationCompta') {
					$sql = $this->build_sql_observationCompta($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport);
				}
				elseif($datatoexport == 'total_hour') {
					$sql = $this->build_sql_TotalHour($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport);
				}
				else {
					$sql = $this->build_sql_bis($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport);
				}
			}
	
	
			// Run the sql
			$this->sqlusedforexport = $sql;
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if($datatoexport == 'analytique_pourcentage') {
				$resql2 = $this->db->query($sql);
			}
			if ($resql) {
				if (!empty($conf->global->EXPORT_PREFIX_SPEC)) {
					$filename = $conf->global->EXPORT_PREFIX_SPEC."_".$datatoexport;
				} else {
					$filename = "export_".$datatoexport;
					if(GETPOST('action', 'aZ09') == 'buildalldoc') {
						$filename .= '_'.dol_print_date(dol_now(), '%Y%m%d_%H%M%S');
					}
				}
				if (!empty($conf->global->EXPORT_NAME_WITH_DT)) {
					$filename .= dol_print_date(dol_now(), '%Y%m%d%_%H%M');
				}
	
				$filename .= '.'.$objmodel->getDriverExtension();
				$dirname = $conf->export->dir_temp.'/'.$user->id;
	
				$outputlangs = clone $langs; // We clone to have an object we can modify (for example to change output charset by csv handler) without changing original value
	
				// Open file
				dol_mkdir($dirname);
				$result = $objmodel->open_file($dirname."/".$filename, $outputlangs);
	
				if ($result >= 0) {
					// Genere en-tete
					$objmodel->write_header($outputlangs);
	
					foreach($array_selected as $key => $val) {
						if(!isset($array_export_TypeFields[$indice][$key])) {
							$array_export_TypeFields[$indice][$key] = "Text";
						}
					}
	
					// Genere ligne de titre
					$objmodel->write_title($array_export_fields[$indice], $array_selected, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
	
					if($datatoexport == 'analytique_pourcentage') {
						$obj2 = $this->db->fetch_object($resql2);
						$sum_pourcentage = 0;
					}

					while ($obj = $this->db->fetch_object($resql)) {
						// Process special operations
						if (!empty($array_export_special[$indice])) {
							foreach ($array_export_special[$indice] as $key => $value) {
							if (!array_key_exists($key, $array_selected)) {
								continue; // Field not selected
							}
							// Operation NULLIFNEG
							if ($array_export_special[$indice][$key] == 'NULLIFNEG') {
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								if ($obj->$alias < 0) {
								$obj->$alias = '';
								}
							} elseif ($array_export_special[$indice][$key] == 'ZEROIFNEG') {
								// Operation ZEROIFNEG
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								if ($obj->$alias < 0) {
								$obj->$alias = '0';
								}
							} elseif ($array_export_special[$indice][$key] == 'getNumOpenDays') {
								// Operation GETNUMOPENDAYS (for Holiday module)
								include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								$obj->$alias = num_open_day(dol_stringtotime($obj->d_date_debut, 1), dol_stringtotime($obj->d_date_fin, 1), 0, 1, $obj->d_halfday, $mysoc->country_code);
							} elseif ($array_export_special[$indice][$key] == 'getRemainToPay') {
								// Operation INVOICEREMAINTOPAY
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								$remaintopay = '';
								if ($obj->f_rowid > 0) {
								global $tmpobjforcomputecall;
								if (!is_object($tmpobjforcomputecall)) {
									include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
									$tmpobjforcomputecall = new Facture($this->db);
								}
								$tmpobjforcomputecall->id = $obj->f_rowid;
								$tmpobjforcomputecall->total_ttc = $obj->f_total_ttc;
								$tmpobjforcomputecall->close_code = $obj->f_close_code;
								$remaintopay = $tmpobjforcomputecall->getRemainToPay();
								}
								$obj->$alias = $remaintopay;
							} else {
								// TODO FIXME
								// Export of compute field does not work. $obj contains $obj->alias_field and formula may contains $obj->field
								// Also the formula may contains objects of class that are not loaded.
								$computestring = $array_export_special[$indice][$key];
								//$tmp = dol_eval($computestring, 1, 0, '1');
								//$obj->$alias = $tmp;
	
								$this->error = "ERROPNOTSUPPORTED. Operation ".$computestring." not supported. Export of 'computed' extrafields is not yet supported, please remove field.";
								return -1;
							}
							}
						}
						// end of special operation processing
	
						if($datatoexport == 'analytique_pourcentage') {
							$obj2 = $this->db->fetch_object($resql2);
								if($obj2->eu_matricule != $obj->eu_matricule) {
								$obj->pourcentage = 100 - $sum_pourcentage;
								$sum_pourcentage = 0;
							}
							else {
								$obj->pourcentage = round(($obj->total_task / (int)$obj->total) * 100, 2);
								$sum_pourcentage += $obj->pourcentage;
							}
							$obj->axe = 'AXE 1';
							$obj->fdt_date_debut = dol_print_date($obj->fdt_date_debut, '%d/%m/%Y');
						}
						elseif($datatoexport == 'donnees_variables') {
							if(!$conf->global->FDT_DISPLAY_COLUMN) {
								$obj->heure_route = (int)$obj->heure_route / 3600;
								$obj->heure_nuit50 = (int)$obj->heure_nuit50 / 3600;
								$obj->heure_nuit75 = (int)$obj->heure_nuit75 / 3600;
								$obj->heure_nuit100 = (int)$obj->heure_nuit100 / 3600;
								$obj->kilometres_rappel = '';
								$obj->grand_deplacement3 = '';
							}
						}
						elseif($datatoexport == 'ObservationCompta') {
							$obj->t_date_start = substr($obj->t_date_start, 0, 7);
							$obj->t_date_end = substr($obj->t_date_end, 0, 7);
						}
						elseif($datatoexport == 'absences') {
							$all_holiday[] = $obj->id_holiday;
	
							$obj->ht_code_silae = 'AB-'.$obj->ht_code_silae;
							$obj->h_date_debut = dol_print_date($obj->h_date_debut, '%d/%m/%Y');
							$obj->h_date_fin = dol_print_date($obj->h_date_fin, '%d/%m/%Y');
	
							// $date_debut_res = dol_mktime(-1, -1, -1, substr($obj->h_date_debut, 3, 2), substr($obj->h_date_debut, 0, 2), substr($obj->h_date_debut, 6, 4));
							// $date_fin_res = dol_mktime(-1, -1, -1, substr($obj->h_date_fin, 3, 2), substr($obj->h_date_fin, 0, 2), substr($obj->h_date_fin, 6, 4));
							// $date_debut = $array_filterValue["h.date_debut"];
							// $date_debut_anticipe = dol_time_plus_duree($date_debut, -$conf->global->JOUR_ANTICIPES, 'd');
							// $date_fin = $array_filterValue["h.date_fin"];
	
							// if($obj->hef_reguler == 1) {
							// 	if($date_debut_res < $date_debut_anticipe) {
							// 		$obj->h_date_debut = dol_print_date($date_debut_anticipe, "%d/%m/%Y");
							// 	}
							// 	if($date_fin_res > $date_fin) {
							// 		$obj->h_date_fin = dol_print_date($date_fin, "%d/%m/%Y");
							// 	}
							// }
							// else {
							// 	if($date_debut_res < $date_debut) {
							// 		$obj->h_date_debut = dol_print_date($date_debut, "%d/%m/%Y");
							// 	}
							// 	if($date_fin_res > $date_fin) {
							// 		$obj->h_date_fin = dol_print_date($date_fin, "%d/%m/%Y");
							// 	}
							// }
	
							// if($obj->ht_in_hour == 1 && $obj->hef_hour%26280 != 0) {
							// 	$obj->type = 'H';
							// 	$obj->valeur = $obj->hef_hour / 3600;
							// }

							if($obj->ht_in_hour == 1) {
								$obj->type = 'H';
								$obj->valeur = $obj->hef_hour / 3600;
							}
							else {
								$obj->type = 'J';
								$obj->valeur = '';
							}
	
							//if(empty($obj->drh_pasdroitrtt) && empty($obj->ht_droit_rtt)) {
							if($obj->ht_in_hour == 1){ // Gestion des congés en heure qui sont sur plusieurs jours
								$date_debut = dol_mktime(-1, -1, -1, substr($obj->h_date_debut, 3, 2), substr($obj->h_date_debut, 0, 2), substr($obj->h_date_debut, 6, 4));
								$nb_jour = num_between_day($date_debut, dol_mktime(-1, -1, -1, substr($obj->h_date_fin, 3, 2), substr($obj->h_date_fin, 0, 2), substr($obj->h_date_fin, 6, 4)) + 3600, 1); 
								$heure = $obj->valeur;

								if($conf->feuilledetemps->enabled && $conf->global->FDT_STANDARD_WEEK_FOR_HOLIDAY) {
									if($conf->donneesrh->enabled && empty($heure_semaine[$obj->rowid])) {
										$userstatic = new User($db);
										$userstatic->fetch($obj->rowid);
										$extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
										$userField = new UserField($db);
										$userField->id = $obj->rowid;
										$userField->table_element = 'donneesrh_Positionetcoefficient';
										$userField->fetch_optionals();
								
										$heure_semaine[$obj->rowid] = (!empty($userField->array_options['options_pasdroitrtt']) ?  $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
										$heure_semaine[$obj->rowid] = (!empty($userField->array_options['options_horairehebdomadaire']) ? $userField->array_options['options_horairehebdomadaire'] : $heure_semaine[$obj->rowid]);
									}
									else {
										$heure_semaine[$obj->rowid] = (!empty($userstatic->array_options['options_pasdroitrtt']) ?  $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
										$heure_semaine[$obj->rowid] = (!empty($userstatic->array_options['options_horairehebdomadaire']) ? $userstatic->array_options['options_horairehebdomadaire'] : $heure_semaine[$obj->rowid]);
									}
								
									// Semaine type
									$standard_week_hour = array();
									if($heure_semaine[$obj->rowid] == $conf->global->HEURE_SEMAINE_NO_RTT) {
										$standard_week_hour['Lundi'] = $conf->global->FDT_STANDARD_WEEK_MONDAY_NO_RTT * 3600;
										$standard_week_hour['Mardi'] = $conf->global->FDT_STANDARD_WEEK_TUESDAY_NO_RTT * 3600;
										$standard_week_hour['Mercredi'] = $conf->global->FDT_STANDARD_WEEK_WEDNESDAY_NO_RTT * 3600;
										$standard_week_hour['Jeudi'] = $conf->global->FDT_STANDARD_WEEK_THURSDAY_NO_RTT * 3600;
										$standard_week_hour['Vendredi'] = $conf->global->FDT_STANDARD_WEEK_FRIDAY_NO_RTT * 3600;
										$standard_week_hour['Samedi'] = $conf->global->FDT_STANDARD_WEEK_SATURDAY_NO_RTT * 3600;
										$standard_week_hour['Dimanche'] = $conf->global->FDT_STANDARD_WEEK_SUNDAY_NO_RTT * 3600;
									}
									else {
										$standard_week_hour['Lundi'] = $conf->global->FDT_STANDARD_WEEK_MONDAY_WITH_RTT * 3600;
										$standard_week_hour['Mardi'] = $conf->global->FDT_STANDARD_WEEK_TUESDAY_WITH_RTT * 3600;
										$standard_week_hour['Mercredi'] = $conf->global->FDT_STANDARD_WEEK_WEDNESDAY_WITH_RTT * 3600;
										$standard_week_hour['Jeudi'] = $conf->global->FDT_STANDARD_WEEK_THURSDAY_WITH_RTT * 3600;
										$standard_week_hour['Vendredi'] = $conf->global->FDT_STANDARD_WEEK_FRIDAY_WITH_RTT * 3600;
										$standard_week_hour['Samedi'] = $conf->global->FDT_STANDARD_WEEK_SATURDAY_WITH_RTT * 3600;
										$standard_week_hour['Dimanche'] = $conf->global->FDT_STANDARD_WEEK_SUNDAY_WITH_RTT * 3600;
									}
								}						
	
								for ($idw = 0; $idw < $nb_jour; $idw++) {
									$dayinloopfromfirstdaytoshow = dol_time_plus_duree($date_debut, $idw, 'd');
	
									if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') != 'Sam' && dol_print_date($dayinloopfromfirstdaytoshow, '%a') != 'Dim' && num_public_holiday($dayinloopfromfirstdaytoshow, $dayinloopfromfirstdaytoshow, '', 1) == 0) {
										$obj->h_date_debut = dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y');
										$obj->h_date_fin = dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y');
	
										if($conf->feuilledetemps->enabled && $conf->global->FDT_STANDARD_WEEK_FOR_HOLIDAY) {
											if($heure > $standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')] / 3600) {
												$obj->valeur = $standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')] / 3600;
											}
											else {
												$obj->valeur = $heure;
											}
										}
										else {
											if($heure > 7) {
												$obj->valeur = 7;
											}
											else {
												$obj->valeur = $heure;
											}
										}
	
										$heure -= $obj->valeur;
	
										$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
									}
								}
								continue;
							}
						}
						elseif($datatoexport == 'heure_sup') {
							$obj->s_date = dol_print_date($obj->s_date, '%d/%m/%Y');
							$obj->s_date2 = dol_print_date($obj->s_date2, '%d/%m/%Y');
	
							if(!empty($obj->s_heure_sup00)) {
								$obj->valeur = $obj->s_heure_sup00 / 3600;
								$obj->type = '';
								$obj->code = 'HS-HS00';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
							if(!empty($obj->s_heure_sup25)) {
								$obj->valeur = $obj->s_heure_sup25 / 3600;
								$obj->type = '';
								$obj->code = 'HS-HS25';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
							if(!empty($obj->s_heure_sup50)) {
								$obj->valeur = $obj->s_heure_sup50 / 3600;
								$obj->type = '';
								$obj->code = 'HS-HS50';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
							if(!empty($obj->s_heure_sup50ht)) {
								$obj->valeur = $obj->s_heure_sup50ht / 3600;
								$obj->type = '';
								$obj->code = 'HS-HS50-HT';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
						}
	
						if($datatoexport == 'absences' && $obj->ht_in_hour != 1) {
							if($obj->h_halfday == '-1') {
								$h_date_debut = $obj->h_date_debut;
								if($obj->h_date_debut != $obj->h_date_fin) {
									$obj->h_date_debut = dol_print_date(dol_time_plus_duree(dol_mktime(-1, -1, -1, substr($h_date_debut, 3, 2), substr($h_date_debut, 0, 2), substr($h_date_debut, 6, 4)), 1, 'd'), '%d/%m/%Y');
									$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
								}
	
								$obj->h_date_debut = $h_date_debut;
								$obj->h_date_fin = $h_date_debut;
								$obj->type = 'J';
								$obj->valeur = '0.5';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
							elseif($obj->h_halfday == '1') {
								$h_date_fin = $obj->h_date_fin;
								if($obj->h_date_debut != $obj->h_date_fin) {
									$obj->h_date_fin = dol_print_date(dol_time_plus_duree(dol_mktime(-1, -1, -1, substr($h_date_fin, 3, 2), substr($h_date_fin, 0, 2), substr($h_date_fin, 6, 4)), -1, 'd'), '%d/%m/%Y');
									$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
								}
	
								$obj->h_date_debut = $h_date_fin;
								$obj->h_date_fin = $h_date_fin;
								$obj->type = 'J';
								$obj->valeur = '0.5';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
							elseif($obj->h_halfday == '2') {
								$h_date_debut = $obj->h_date_debut;
								$h_date_fin = $obj->h_date_fin;
	
								$obj->h_date_debut = $h_date_debut;
								$obj->h_date_fin = $h_date_debut;
								$obj->type = 'J';
								$obj->valeur = '0.5';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
	
								if(num_open_day(dol_mktime(-1, -1, -1, substr($h_date_debut, 3, 2), substr($h_date_debut, 0, 2), substr($h_date_debut, 6, 4)), dol_mktime(-1, -1, -1, substr($h_date_fin, 3, 2), substr($h_date_fin, 0, 2), substr($h_date_fin, 6, 4))) > 1) {
									$obj->h_date_debut = dol_print_date(dol_time_plus_duree(dol_mktime(-1, -1, -1, substr($h_date_debut, 3, 2), substr($h_date_debut, 0, 2), substr($h_date_debut, 6, 4)), 1, 'd'), '%d/%m/%Y');
									$obj->h_date_fin = dol_print_date(dol_time_plus_duree(dol_mktime(-1, -1, -1, substr($h_date_fin, 3, 2), substr($h_date_fin, 0, 2), substr($h_date_fin, 6, 4)), -1, 'd'), '%d/%m/%Y');
									$obj->valeur = '';
									$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
								}

								$obj->h_date_debut = $h_date_fin;
								$obj->h_date_fin = $h_date_fin;
								$obj->type = 'J';
								$obj->valeur = '0.5';
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
							else {
								$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
							}
						}
						elseif($datatoexport != 'heure_sup') {
							$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
						}
					}
						
					// Genere en-tete
					$objmodel->write_footer($outputlangs);
	
					// Close file
					$objmodel->close_file();
	
					if($datatoexport == 'absences') {
						require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedHoliday.class.php';
						$holiday = new extendedHoliday($this->db);
						$result = $holiday->setStatutExported($all_holiday);
					}
	
					return 1;
				} else {
					$this->error = $objmodel->error;
					dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
					return -1;
				}
			} else {
			$this->error = $this->db->error()." - sql=".$sql;
			return -1;
			}
		}

	}


	public function build_sql_bis($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport)
	{
		global $conf; 

		// phpcs:enable
		// Build the sql request
		$sql = "SELECT DISTINCT u.rowid, ";
		$i = 0;
	
		//print_r($array_selected);
		foreach ($array_export_fields[$indice] as $key => $value) {
			if (!array_key_exists($key, $array_selected)) {
				continue; // Field not selected
			}
			if (preg_match('/^none\./', $key)) {
				continue; // A field that must not appears into SQL
			}

			if($key == 'code' || $key == 'hdebut' || $key == 'hfin' || $key == 'kilometres_rappel' || $key == 'grand_deplacement3' || $key == 'valeur') {
				continue;
			}

			if ($i > 0) {
				$sql .= ', ';
			} else {
				$i++;
			}
	
			if (strpos($key, ' as ') === false) {
				$newfield = $key.' as '.str_replace(array('.', '-', '(', ')'), '_', $key);
			} else {
				$newfield = $key;
			}

			if(str_contains($key, 'silae_extrafields')) {
				if($array_export_TypeFields[$key] == 'checkbox') {
					$newfield = "COALESCE(SUM(CASE WHEN $key = 1 THEN 1 ELSE 0 END), 0) AS ".str_replace(array('.', '-', '(', ')'), '_', $key);
				}
				else {
					$newfield = "COALESCE(SUM($key), 0) AS ".str_replace(array('.', '-', '(', ')'), '_', $key); 
				}
			}

			if($key == 'petit_deplacement1') {
				$newfield = "COALESCE(d.d1, 0) + COALESCE(r.regul_d1, 0) AS petit_deplacement1";
			}
			elseif($key == 'petit_deplacement2') {
				$newfield = "COALESCE(d.d2, 0) + COALESCE(r.regul_d2, 0) AS petit_deplacement2";
			}
			elseif($key == 'petit_deplacement3') {
				$newfield = "COALESCE(d.d3, 0) + COALESCE(r.regul_d3, 0) AS petit_deplacement3";
			}
			elseif($key == 'petit_deplacement4') {
				$newfield = "COALESCE(d.d4, 0) + COALESCE(r.regul_d4, 0) AS petit_deplacement4";
			}
			elseif($key == 'grand_deplacement1') {
				$newfield = "COALESCE(d.gd1, 0) + COALESCE(r.regul_gd1, 0) AS grand_deplacement1";
			}
			elseif($key == 'grand_deplacement2') {
				$newfield = "COALESCE(d.gd2, 0) + COALESCE(r.regul_gd2, 0) AS grand_deplacement2";
			}
			elseif($key == 'repas1') {
				$newfield = "COALESCE(s.repas1, 0) + COALESCE(r.regul_repas1, 0) AS repas1";
			}
			elseif($key == 'repas2') {
				$newfield = "COALESCE(s.repas2, 0) + COALESCE(r.regul_repas2, 0) AS repas2";
			}
			elseif($key == 'heure_route') {
				$newfield = "COALESCE(s.heure_route, 0) + COALESCE(r.regul_heure_route, 0) AS heure_route";
			}
			elseif($key == 'kilometres') {
				$newfield = "COALESCE(s.kilometres , 0) + COALESCE(r.regul_kilometres, 0) AS kilometres";
			}
			elseif($key == 'indemnite_tt') {
				$newfield = "COALESCE(s.indemnite_tt, 0) + COALESCE(r.regul_indemnite_tt, 0) AS indemnite_tt";
			}
			elseif($key == 'heure_nuit50') {
				$newfield = "COALESCE(SUM(r.heure_nuit_50), 0) AS heure_nuit50";
			}
			elseif($key == 'heure_nuit75') {
				$newfield = "COALESCE(SUM(r.heure_nuit_75), 0) AS heure_nuit75";
			}
			elseif($key == 'heure_nuit100') {
				$newfield = "COALESCE(SUM(r.heure_nuit_100), 0) AS heure_nuit100";
			}
			elseif($datatoexport == 'heure_sup' && $key == 'type') {
				$newfield = "null as type";
			}
			elseif($datatoexport == 'heure_sup' && $key == 'code') {
				$newfield = "null as code";
			}
			elseif($datatoexport == 'heure_sup' && $key == 's.date') {
				$newfield = "s.date as s_date";
			}
			elseif($datatoexport == 'heure_sup' && $key == 's.date2') {
				$newfield = "s.date as s_date2";
			}
			elseif($key == 'type') {
				$newfield = "ht.in_hour as type";
			}
			elseif($key == 'section') {
				$newfield = "p.ref as section";
			}
			elseif($key == 'pourcentage') {
				if(!$conf->global->FDT_DISPLAY_COLUMN) {
					$newfield = "SUM(tt.element_duration)/3600 as total_task, totalMonth.total as total";
				}
				else {
					$newfield = "(SUM(tt.element_duration)/3600 + SUM(ptto.heure_nuit)/3600) as total_task, totalMonth.total as total";
				}
			}
			elseif($key == 'axe') {
				$newfield = "null as axe";
			}
	
			$sql .= $newfield;
		}

		if($datatoexport == 'absences') {
			$sql .= ", h.halfday as h_halfday";
			$sql .= ", hef.hour as hef_hour";
			$sql .= ", ht.in_hour as ht_in_hour";
			$sql .= ", h.rowid as id_holiday";
			$sql .= ", ht.code as ht_code";
			$sql .= ", drh.pasdroitrtt as drh_pasdroitrtt";
			$sql .= ", ht.droit_rtt as ht_droit_rtt";
		}
		elseif($datatoexport == 'heure_sup') {
			$sql .= ", SUM(s.heure_sup00) as s_heure_sup00";
			$sql .= ", SUM(s.heure_sup25) as s_heure_sup25";
			$sql .= ", SUM(s.heure_sup50) as s_heure_sup50";
			$sql .= ", SUM(s.heure_sup50ht) as s_heure_sup50ht";
		}


		$sql .= " FROM llx_user AS u";
		$sql .= " LEFT JOIN llx_user_extrafields AS eu ON eu.fk_object = u.rowid";

		if($datatoexport == 'analytique_pourcentage'){
			$sql .= " LEFT JOIN llx_element_time AS tt ON tt.fk_user = u.rowid";
			$sql .= " LEFT JOIN llx_feuilledetemps_projet_task_time_other AS ptto ON ptto.fk_projet_task_time = tt.rowid";
			$sql .= " LEFT JOIN llx_projet_task AS pt ON pt.rowid = tt.fk_element";
			$sql .= " LEFT JOIN llx_projet AS p ON p.rowid = pt.fk_projet";
			$sql .= " LEFT JOIN (SELECT u.rowid, (SUM(tt.element_duration)/3600 + SUM(ptto .heure_nuit)/3600) as total FROM llx_user AS u LEFT JOIN llx_element_time AS tt ON tt.fk_user = u.rowid LEFT JOIN llx_feuilledetemps_projet_task_time_other AS ptto ON ptto.fk_projet_task_time = tt.rowid LEFT JOIN llx_projet_task AS pt ON pt.rowid = tt.fk_element LEFT JOIN llx_projet AS p ON p.rowid = pt.fk_projet WHERE tt.elementtype = 'task'";
			if(!empty($array_filterValue['tt.element_date'])) {
				$sql .= " AND date_format(tt.element_date,'%Y%m') = '".$array_filterValue['tt.element_date']."'";
			}
			$sql .= " GROUP BY u.rowid) as totalMonth ON totalMonth.rowid = u.rowid";
		}
		elseif($datatoexport == 'donnees_variables'){
			$sql .= " LEFT JOIN llx_feuilledetemps_feuilledetemps AS fdt ON fdt.fk_user = u.rowid";
			if(!$conf->global->FDT_DISPLAY_COLUMN) {
				$sql .= " LEFT JOIN 
							(SELECT 
								fk_user, 
								COALESCE(SUM(heure_route), 0) AS heure_route, 
								COALESCE(SUM(kilometres), 0) AS kilometres ,
								COALESCE(SUM(CASE WHEN repas = 1 THEN 1 ELSE 0 END), 0) AS repas1, 
								COALESCE(SUM(CASE WHEN repas = 2 THEN 1 ELSE 0 END), 0) AS repas2, 
								COALESCE(SUM(indemnite_tt), 0) AS indemnite_tt
							FROM 
								llx_feuilledetemps_silae 
							WHERE 
								date_format(date,'%Y%m') = '".$array_filterValue["fdt.date_debut"]."' 
							GROUP BY 
								fk_user) AS s ON s.fk_user = u.rowid ";
				$sql .= " LEFT JOIN 
							(SELECT 
								fk_user, 
								COALESCE(SUM(heure_nuit_50), 0) AS heure_nuit_50, 
								COALESCE(SUM(heure_nuit_75), 0) AS heure_nuit_75, 
								COALESCE(SUM(heure_nuit_100), 0) AS heure_nuit_100, 
								COALESCE(SUM(d1), 0) AS regul_d1, 
								COALESCE(SUM(d2), 0) AS regul_d2, 
								COALESCE(SUM(d3), 0) AS regul_d3, 
								COALESCE(SUM(d4), 0) AS regul_d4, 
								COALESCE(SUM(gd1), 0) AS regul_gd1, 
								COALESCE(SUM(gd2), 0) AS regul_gd2, 
								COALESCE(SUM(repas1), 0) AS regul_repas1, 
								COALESCE(SUM(repas2), 0) AS regul_repas2, 
								COALESCE(SUM(indemnite_tt), 0) AS regul_indemnite_tt,
								COALESCE(SUM(heure_route), 0) AS regul_heure_route,
								COALESCE(SUM(kilometres), 0) AS regul_kilometres
							FROM 
								llx_feuilledetemps_regul 
							WHERE 
								date_format(date,'%Y%m') = '".$array_filterValue["fdt.date_debut"]."' 
							GROUP BY 
								fk_user) AS r ON r.fk_user = u.rowid";
				$sql .= " LEFT JOIN 
							(SELECT 
								fk_user, 
								COALESCE(SUM(CASE WHEN type_deplacement = 1 THEN 1 ELSE 0 END), 0) AS d1, 
								COALESCE(SUM(CASE WHEN type_deplacement = 2 THEN 1 ELSE 0 END), 0) AS d2, 
								COALESCE(SUM(CASE WHEN type_deplacement = 3 THEN 1 ELSE 0 END), 0) AS d3, 
								COALESCE(SUM(CASE WHEN type_deplacement = 4 THEN 1 ELSE 0 END), 0) AS d4, 
								COALESCE(SUM(CASE WHEN (type_deplacement = 5 OR type_deplacement = 8 OR type_deplacement = 9) THEN 1 ELSE 0 END), 0) AS gd1, 
								COALESCE(SUM(CASE WHEN type_deplacement = 6 THEN 1 ELSE 0 END), 0) AS gd2 
							FROM 
								llx_feuilledetemps_deplacement 
							WHERE 
								date_format(date,'%Y%m') = '".$array_filterValue["fdt.date_debut"]."' 
							GROUP BY 
								fk_user) AS d ON d.fk_user = u.rowid";
			}
			else {
				$sql .= " LEFT JOIN llx_feuilledetemps_silae AS silae ON silae.fk_user = u.rowid AND date_format(silae.date,'%Y%m') = '".$array_filterValue["fdt.date_debut"]."'";
				$sql .= " LEFT JOIN llx_feuilledetemps_silae_extrafields AS silae_extrafields ON silae_extrafields.fk_object = silae.rowid";
			}
		}
		elseif($datatoexport == 'absences') {
			$sql .= " LEFT JOIN llx_donneesrh_Positionetcoefficient_extrafields AS drh ON drh.fk_object = u.rowid";
			$sql .= " LEFT JOIN llx_holiday AS h ON h.fk_user = u.rowid";
			$sql .= " LEFT JOIN llx_c_holiday_types AS ht ON ht.rowid = h.fk_type";
			$sql .= " LEFT JOIN llx_holiday_extrafields AS hef ON hef.fk_object = h.rowid";
		}
		elseif($datatoexport == 'heure_sup') {
			$sql .= " LEFT JOIN llx_feuilledetemps_silae AS s ON s.fk_user = u.rowid";
		}

		if($datatoexport != 'donnees_variables') {
			$sql .= " LEFT JOIN llx_feuilledetemps_feuilledetemps AS fdt ON fdt.fk_user = u.rowid";
		}

		if($datatoexport == 'analytique_pourcentage'){
			$sql .= " AND date_format(tt.element_date,'%Y%m') = date_format(fdt.date_debut,'%Y%m') AND date_format(tt.element_date,'%Y%m') = date_format(fdt.date_fin,'%Y%m')";
		}
		elseif($datatoexport == 'heure_sup') {
			$sql .= " AND date_format(s.date,'%Y%m') = date_format(fdt.date_debut,'%Y%m') AND date_format(s.date,'%Y%m') = date_format(fdt.date_fin,'%Y%m')";
		}

		$sql .= " WHERE 1 = 1 AND u.statut = 1";
		if($conf->global->FDT_MANAGE_EMPLOYER) {
			$sql .= " AND eu.fk_employeur = 157";
		}
		if($datatoexport == 'analytique_pourcentage'){
			$sql .= " AND tt.elementtype = 'task'";
		}
	
		// Add the WHERE part. Filtering into sql if a filtering array is provided
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			$sqlWhere = '';
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
				if (preg_match('/GROUP_CONCAT/i', $key)) {
					continue;
				}
				if($datatoexport == 'absences' && ($key == 'h.date_debut' || $key == 'h.date_fin')) {
					continue;
				}
				elseif ($value != '') {
					if($datatoexport == 'heure_sup' && $key == 's.date2') { 
						$sqlWhere .= " AND ".$this->build_filterQuery($array_export_TypeFields[$indice][$key], 's.date', $array_filterValue[$key]);
					}
					else {
						$sqlWhere .= " AND ".$this->build_filterQuery($array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
					}
				}
			}
			if($datatoexport == 'absences') {
				if(!empty($array_filterValue["h.date_debut"]) && !empty($array_filterValue["h.date_fin"])) {
					$sqlWhere .= " AND ".$this->build_filterQuery("Date", "fdt.date_debut", substr(str_replace('-', '', $this->db->idate($array_filterValue["h.date_debut"])), 0, 6));
					$sqlWhere .= " AND ".$this->build_filterQuery("Date", "fdt.date_fin", substr(str_replace('-', '', $this->db->idate($array_filterValue["h.date_fin"])), 0, 6));
					$sqlWhere .= " AND (h.date_debut <= '".$this->db->idate($array_filterValue["h.date_fin"])."' AND h.date_fin >= '".$this->db->idate(dol_time_plus_duree($array_filterValue["h.date_debut"], -$conf->global->JOUR_ANTICIPES, 'd'))."')";
				}
				$sqlWhere .= " AND h.statut NOT IN (1,4,5)";
				if(!empty($conf->global->HOLIDAYTYPE_EXLUDED_EXPORT)) {
					$sqlWhere .= " AND ht.rowid NOT IN (".$conf->global->HOLIDAYTYPE_EXLUDED_EXPORT.")";
				}
			}
			elseif($datatoexport == 'heure_sup') {
				$sqlWhere .= " AND (s.heure_sup00 > 0 OR s.heure_sup00 < 0 OR s.heure_sup25 > 0 OR s.heure_sup25 < 0 OR s.heure_sup50 > 0 OR s.heure_sup50 < 0 OR s.heure_sup50ht > 0 OR s.heure_sup50ht < 0)";
			}

			$sql .= $sqlWhere;
		}

		// Add the order
		if($datatoexport == 'donnees_variables'){
			$sql .= " GROUP BY u.rowid";
		}
		elseif($datatoexport == 'analytique_pourcentage'){
			$sql .= " GROUP BY eu.matricule, p.ref";
		}
		elseif($datatoexport == 'heure_sup'){
			$sql .= " GROUP BY eu_matricule";
		}

		// Regul des heures sup
		if($datatoexport == 'heure_sup') {
			$sql_duplicate = $sql;
			$sql_duplicate = str_replace('s.date as s_date', 'DATE_ADD(s.date, INTERVAL -1 MONTH) as s_date', $sql_duplicate);
			$sql_duplicate = str_replace('s.date as s_date2', 'DATE_ADD(s.date, INTERVAL -1 MONTH) as s_date2', $sql_duplicate);
			$sql_duplicate = str_replace('llx_feuilledetemps_silae', 'llx_feuilledetemps_regul', $sql_duplicate);
			$sql .= ' UNION '.$sql_duplicate;
			$sql = "Select * FROM ($sql) AS t";
		}

		$sql .= " ORDER BY eu_matricule";
		if($datatoexport == 'analytique_pourcentage'){
			$sql .= ", total_task";
		}

		// Add the HAVING part.
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
			if (preg_match('/GROUP_CONCAT/i', $key) and $value != '') {
				$sql .= " HAVING ".$this->build_filterQuery($this->array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
			}
			}
		}
	
		return $sql;
	}

	public function build_sql_observationCompta($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport)
	{
		// phpcs:enable
		// Build the sql request
		$sql = "SELECT DISTINCT u.rowid, ";
		$i = 0;
	
		foreach ($array_export_fields[$indice] as $key => $value) {
			if (!array_key_exists($key, $array_selected)) {
				continue; // Field not selected
			}
			if (preg_match('/^none\./', $key)) {
				continue; // A field that must not appears into SQL
			}

			if($key == 'u.rowid') {
				continue;
			}

			if ($i > 0) {
				$sql .= ', ';
			} else {
				$i++;
			}
	
			if (strpos($key, ' as ') === false) {
				$newfield = $key.' as '.str_replace(array('.', '-', '(', ')'), '_', $key);
			} else {
				$newfield = $key;
			}

			if($key == 't.type') {
				$newfield = "(CASE 
								WHEN t.type = 1 THEN 'Pour prise en compte'
								WHEN t.type = 2 THEN 'Pour controle'
								WHEN t.type = 3 THEN 'Pour info'
								WHEN t.type = 4 THEN 'En attente'
								WHEN t.type = 5 THEN 'Suivi contrat'
								WHEN t.type = 6 THEN 'Demande pieces'
								WHEN t.type = 7 THEN 'ADMIN'
								WHEN t.type = 8 THEN 'Soldé ADMIN'
								ELSE '' END) AS t_type";
			}
	
			$sql .= $newfield;
		}
		$sql .= " FROM llx_feuilledetemps_observationcompta AS t";
		$sql .= " LEFT JOIN llx_user AS u ON t.fk_user = u.rowid";
		$sql .= " LEFT JOIN llx_user_extrafields AS ue ON u.rowid = ue.fk_object";
		$sql .= " WHERE 1 = 1 AND u.statut = 1";

		if(!empty($array_filterValue['t.date_start']) && !empty($array_filterValue['t.date_end'])) {
			$sql .= " AND ((t.date_start >= '".substr($this->db->idate($array_filterValue['t.date_start']), 0, 10)."' AND t.date_start <= '".substr($this->db->idate($array_filterValue['t.date_end']), 0, 10)."')";
			$sql .= " OR (t.date_end >= '".substr($this->db->idate($array_filterValue['t.date_start']), 0, 10)."' AND t.date_end <= '".substr($this->db->idate($array_filterValue['t.date_end']), 0, 10)."') ";
			$sql .= " OR (t.date_start < '".substr($this->db->idate($array_filterValue['t.date_start']), 0, 10)."' AND t.date_end > '".substr($this->db->idate($array_filterValue['t.date_end']), 0, 10)."')";
			$sql .= " OR (t.date_start >= '".substr($this->db->idate($array_filterValue['t.date_start']), 0, 10)."' AND t.date_end <= '".substr($this->db->idate($array_filterValue['t.date_end']), 0, 10)."'))";
		}
		elseif(!empty($array_filterValue['t.date_start'])) {
			$sql .= " AND t.date_end >= '".substr($this->db->idate($array_filterValue['t.date_start']), 0, 10)."'";
		}

		// Add the WHERE part. Filtering into sql if a filtering array is provided
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			$sqlWhere = '';
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
				if($key == 't.date_start' || $key == 't.date_end') {
					continue;
				}
				if (preg_match('/GROUP_CONCAT/i', $key)) {
					continue;
				}
				if ($value != '') {
					$sqlWhere .= " AND ".$this->build_filterQuery($array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
				}
			}
			$sql .= $sqlWhere;
		}
	
		// Add the order
		//$sql .= " ORDER BY eu.matricule";

		// Add the HAVING part.
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
			if (preg_match('/GROUP_CONCAT/i', $key) and $value != '') {
				$sql .= " HAVING ".$this->build_filterQuery($this->array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
			}
			}
		}
	
		return $sql;
	}

	public function build_sql_totalHour($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport)
	{
		// phpcs:enable
		// Build the sql request
		$sql = "SELECT DISTINCT ";
		$i = 0;
	
		foreach ($array_export_fields[$indice] as $key => $value) {
			if (!array_key_exists($key, $array_selected)) {
				continue; // Field not selected
			}
			if (preg_match('/^none\./', $key)) {
				continue; // A field that must not appears into SQL
			}

			if($key == 'u.rowid') {
				continue;
			}

			if ($i > 0) {
				$sql .= ', ';
			} else {
				$i++;
			}
	
			if (strpos($key, ' as ') === false) {
				$newfield = $key.' as '.str_replace(array('.', '-', '(', ')'), '_', $key);
			} else {
				$newfield = $key;
			}
	
			$sql .= $newfield;
		}
		$sql .= " FROM (
						SELECT et.fk_user as user_id, et.element_date as element_date, et.element_duration as element_duration, null as s_heure_sup00, null as s_heure_sup25, null as s_heure_sup50, null as s_heure_sup50ht, null as s_heure_route, null as r_heure_sup00, null as r_heure_sup25, null as r_heure_sup50, null as r_heure_sup50ht, null as r_heure_nuit_50, null as r_heure_nuit_75, null as r_heure_nuit_100, null as r_heure_route, null as deplacement, null as s_kilometres, null as r_kilometres FROM llx_element_time AS et
						UNION
						SELECT s.fk_user, s.date, null, s.heure_sup00, s.heure_sup25, s.heure_sup50, s.heure_sup50ht, s.heure_route, null, null, null, null, null, null, null, null, null, s.kilometres, null FROM llx_feuilledetemps_silae AS s
						UNION 
						SELECT r.fk_user, r.date, null, null, null, null, null, null, r.heure_sup00, r.heure_sup25, r.heure_sup50, r.heure_sup50ht, r.heure_nuit_50, r.heure_nuit_75, r.heure_nuit_100, r.heure_route, null, null, r.kilometres FROM llx_feuilledetemps_regul AS r
						UNION 
						SELECT d.fk_user, d.date, null, null, null, null, null, null, null, null, null, null, null, null, null, null, d.type_deplacement, null, null FROM llx_feuilledetemps_deplacement AS d
					) AS t1";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user AS u ON user_id = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields AS eu ON u.rowid = eu.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."donneesrh_Deplacement_extrafields AS dd ON u.rowid = dd.fk_object";
		$sql .= " WHERE 1 = 1";

		// Add the WHERE part. Filtering into sql if a filtering array is provided
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			$sqlWhere = '';
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
				if($key == 't.date_start' || $key == 't.date_end') {
					continue;
				}
				if (preg_match('/GROUP_CONCAT/i', $key)) {
					continue;
				}
				if ($value != '') {
					$sqlWhere .= " AND ".$this->build_filterQuery($array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
				}
			}
			$sql .= $sqlWhere;
		}
	
		$sql .= " GROUP BY u.rowid";

		// Add the HAVING part.
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
			if (preg_match('/GROUP_CONCAT/i', $key) and $value != '') {
				$sql .= " HAVING ".$this->build_filterQuery($this->array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
			}
			}
		}

		// Add the order
		$sql .= " ORDER BY eu.matricule";
	
		return $sql;
	}
}
