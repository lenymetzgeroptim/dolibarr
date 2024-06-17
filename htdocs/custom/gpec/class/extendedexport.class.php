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
 *  \file       htdocs/custom/gpec/class/extendedexport.class.php
 *	\brief      File of class to manage Extended Export
 *  \ingroup	core
 */

require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/gpec.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/competencedomaine_level_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/competencetransverse_level_user.class.php';

/**
 *	Class to manage Dolibarr users
 */
class ExtendedExport extends Export
{

	public function build_file_bis($user, $model, $datatoexport, $array_selected, $array_filterValue, $sqlquery = '', $array_export_fields, $array_export_TypeFields, $array_export_special)
	{
		// phpcs:enable
		global $conf, $langs, $mysoc;

		$indice = 0;
		asort($array_selected);

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

		if (!empty($sqlquery)) {
			$sql = $sqlquery;
		} else {
			$sql = $this->build_sql_bis($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport);
		}


		// Run the sql
		$this->sqlusedforexport = $sql;
		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!empty($conf->global->EXPORT_PREFIX_SPEC)) {
				$filename = $conf->global->EXPORT_PREFIX_SPEC."_".$datatoexport;
			} else {
				$filename = "export_".$datatoexport;
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

				if($datatoexport == 'gpec_competence_domaine') {
					$competence_elementaire = new CompetenceDomaine_Level_User($this->db);
					$liste_competenceElementaires = $competence_elementaire->getAllCompetencesElementaires("d.nom, c.rowid");
					$array_export_fields[$indice] = array_merge($array_export_fields[$indice], $liste_competenceElementaires);
					$array_selected = array_merge($array_selected, $liste_competenceElementaires);
				}
				elseif($datatoexport == 'gpec_competence_transverse') {
					$competence_transverse = new CompetenceTransverse_Level_User($this->db);
					$liste_competenceTransverses = $competence_transverse->getAllCompetencesTransverses("c.competence");
					$array_export_fields[$indice] = array_merge($array_export_fields[$indice], $liste_competenceTransverses);
					$array_selected = array_merge($array_selected, $liste_competenceTransverses);
				}
				elseif($datatoexport == 'gpec_matrice_competence') {
					$competence_elementaire = new CompetenceDomaine_Level_User($this->db);
					$liste_moyennesCompetences = $competence_elementaire->getMoyennesCompetences(0);
					$array_export_fields[$indice] = array_merge($array_export_fields[$indice], $liste_moyennesCompetences);
					$array_selected = array_merge($array_selected, $liste_moyennesCompetences);
				}

				foreach($array_selected as $key => $val) {
					if(!isset($array_export_TypeFields[$indice][$key])) {
						$array_export_TypeFields[$indice][$key] = "Text";
					}
				}

				// Genere ligne de titre
				$objmodel->write_title($array_export_fields[$indice], $array_selected, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);

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

					$userid = $obj->rowid;
					if($datatoexport == 'gpec_competence_domaine') {
						$liste_competenceElementairesWithLevel = $competence_elementaire->getAllCompetencesElementairesWithLevel($userid, "d.nom, c.rowid", 1);
						$i = 1;
						foreach($liste_competenceElementairesWithLevel as $key => $val) {
							$num_question = "question".$i;
							if(!empty($val)) {
								$obj->$num_question = $val - 1;

							}
							elseif($val == '0') {
								$obj->$num_question = "SO";
							}
							else {
								$obj->$num_question = "/";
							}
							$i++;
						}
					}
					elseif($datatoexport == 'gpec_competence_transverse') {
						$liste_competenceTransversesWithLevel = $competence_transverse->getAllCompetencesTransversesWithLevel($userid, "c.competence", 1);
						$i = 1;
						foreach($liste_competenceTransversesWithLevel as $key => $val) {
							$num_question = "question".$i;
							if(!empty($val)) {
								$obj->$num_question = $val - 1;

							}
							elseif($val == 0) {
								$obj->$num_question = "SO";
							}
							else {
								$obj->$num_question = "/";
							}
							$i++;
						}
					}
					elseif($datatoexport == 'gpec_matrice_competence') {
						foreach($obj as $key => $value) {
							if(in_array($key, array('gmc_is_td', 'gmc_is_t', 'gmc_is_te', 'gmc_is_tc', 'gmc_is_id', 'gmc_is_i', 'gmc_is_ic', 'gmc_is_ie'))
								|| in_array($key, array('gmc_chef_projet', 'gmc_pilote_affaire', 'gmc_ingenieur_confirme', 'gmc_preparateur_charge_affaire', 'gmc_preparateur_methodes', 
								'gmc_charge_affaires_elec_auto', 'gmc_electricien', 'gmc_charge_affaires_mecanique', 'gmc_mecanicien', 'gmc_robinettier', 'gmc_pcr_operationnel', 'gmc_technicien_rp', 'gmc_charge_affaires_multi_specialites'))) {
								if($value == '1') {
									$obj->$key = 'X';
								}
								else {
									$obj->$key = '';
								}
							}
							elseif(in_array($key, array('gmc_mec_machine_tournante', 'gmc_robinetterie', 'gmc_chaudronnerie', 'gmc_tuyauterie_soudage', 'gmc_automatisme', 'gmc_electricite', 'gmc_ventilation', 'gmc_logistique', 'gmc_securite', 'gmc_soudage'))) {
								$obj->$key -= 1;
							}
						}


						$liste_moyennesCompetences = $competence_elementaire->getMoyennesCompetences($userid);
						foreach($liste_moyennesCompetences as $key => $val) {
							$obj->$key = $val;
						}
					}
				

					$objmodel->write_record($array_selected, $obj, $outputlangs, isset($array_export_TypeFields[$indice]) ? $array_export_TypeFields[$indice] : null);
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
		} else {
		$this->error = $this->db->error()." - sql=".$sql;
		return -1;
		}
	}


	public function build_sql_bis($indice, $array_selected, $array_filterValue, $array_export_fields, $array_export_TypeFields, $datatoexport)
	{
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

			if($newfield == 'eu.employeur as eu_employeur') {
				$newfield = 'CASE WHEN eu.employeur = 1 THEN "OPTIM Industries"';
				$newfield .= ' WHEN eu.employeur = 2 THEN "Sigedi"';
				$newfield .= ' WHEN eu.employeur = 3 THEN "ALORIS"';
				$newfield .= ' WHEN eu.employeur = 4 THEN "HP Formation"';
				$newfield .= ' WHEN eu.employeur = 5 THEN "ETT : AVS Le Teil"';
				$newfield .= ' WHEN eu.employeur = 6 THEN "ETT : AVS Lyon"';
				$newfield .= ' WHEN eu.employeur = 7 THEN "STT : CAP AIN"';
				$newfield .= ' WHEN eu.employeur = 8 THEN "ETT : Manpower"';
				$newfield .= ' WHEN eu.employeur = 9 THEN "ETT : Elitt"';
				$newfield .= ' END AS eu_employeur';
			}
			elseif($newfield == 'g.status as g_status') {
				$newfield = 'CASE WHEN g.status = 0 THEN "Non renseigné"';
				$newfield .= ' WHEN g.status = 1 THEN "Renseigné"';
				$newfield .= ' WHEN g.status = 2 THEN "Validé"';
				$newfield .= ' ELSE "Non renseigné"';
				$newfield .= ' END AS g_status';
			}
			elseif($newfield == 'anciennete_optim as anciennete_optim') {
				//$newfield = 'YEAR(now()) - YEAR(u.dateemployment) as anciennete_optim';
				$newfield = 'FLOOR(DATEDIFF(now(), u.dateemployment)/365.25) + 1 as anciennete_optim';
			}
			elseif($newfield == 'gmc.anciennete_metier as gmc_anciennete_metier') {
				$newfield = 'YEAR(now()) - gmc.anciennete_metier + 1 as gmc_anciennete_metier';
			}
			elseif($newfield == 'ug.nom as ug_nom') {
				$newfield = "GROUP_CONCAT(ug.nom SEPARATOR ', ') as ug_nom";
			}
	
			$sql .= $newfield;
		}
		$sql .= " FROM llx_user AS u";
		$sql .= " LEFT JOIN llx_user_extrafields AS eu ON eu.fk_object = u.rowid";
		$sql .= " LEFT JOIN llx_usergroup_user AS ugu ON ugu.fk_user = u.rowid";
		$sql .= " LEFT JOIN llx_usergroup AS ug ON ug.rowid = ugu.fk_usergroup";
		if($datatoexport == 'gpec_matrice_competence'){
			$sql .= " LEFT JOIN llx_gpec_matricecompetence AS gmc ON gmc.fk_user = u.rowid";
		}
		$sql .= " LEFT JOIN llx_gpec_gpec AS g ON g.fk_user = u.rowid";
		$sql .= " WHERE 1 = 1 AND u.statut = 1 ";

	
		// Add the WHERE part. Filtering into sql if a filtering array is provided
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			$sqlWhere = '';
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
				if (preg_match('/GROUP_CONCAT/i', $key)) {
					continue;
				}
				if ($value != '') {
					$sqlWhere .= " AND ".$this->build_filterQuery($array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
				}
				if($key == 'g.status' && $sqlWhere == ' AND  g.status = 0') {
					$sqlWhere = ' AND  (g.status = 0 OR g.status IS NULL)';
				}
			}
			$sql .= $sqlWhere;
		}
	
		// Add the order
		$sql .= " GROUP BY u.rowid";
		$sql .= " ORDER BY u.lastname";
	
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
}
