<?php
/* Copyright (C) 2023 Faure Louis
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    constat/lib/constat.lib.php
 * \ingroup constat
 * \brief   Library files with common functions for Constat
 */
require_once DOL_DOCUMENT_ROOT.'/custom/constat/class/constat.class.php';
/**
 * Prepare admin pages header
 *
 * @return array
 */
function constatAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("constat@constat");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/constat/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/constat/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = is_countable($extrafields->attributes['myobject']['label']) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/constat/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/constat/admin/constat_extrafields.php", 1);
	$head[$h][1] = $langs->trans("Attribut supplémentaire constat");
	$head[$h][2] = 'Attribut supplémentaire constat';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@constat:/constat/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@constat:/constat/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'constat@constat');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'constat@constat', 'remove');

	return $head;

}


	
 /*function getStatusByYearChart()
{
    global $conf, $db, $langs, $user;

    $sql = "SELECT COUNT(co.rowid) as nb_constats, YEAR(co.dateEmeteur) as year, co.status, co.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."constat_constat as co";
    $sql .= " GROUP BY YEAR(co.dateEmeteur), co.status";
    $sql .= " ORDER BY YEAR(co.dateEmeteur), co.status";
    $resql = $db->query($sql);

    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;
        $dataseries = array();
        $status_series = array();

        $status_labels = array('0'=>'Brouillon', '1'=>'Validé', '3'=>'Vérifié', '4'=>'En cours', '5'=>'Soldée', '7'=>'Clôturé', '9'=>'Annulé');
        $list = array();

        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            if ($obj) { 
				var_dump($obj);
                // if (!isset($list[$obj->year])) {
                //     $list[$obj->year] = array_fill_keys(array_keys($status_labels), 0);
                // }
				$constat =  new Constat($db);
				$constat->fetch($obj->rowid);
		

				if(isset($obj->year)) {
					$list[$obj->year][$constat->getLibStatut(1)] = $obj->nb_constats;
					$dates[] = $obj->year;
					$status_series[] = $constat->getLibStatut(1);
				}
               
            }
            $i++;
        }

        // $years = range(date("Y") - 5, date("Y"));
        // foreach ($years as $year) {
        //     if (!isset($list[$year])) {
        //         $list[$year] = array_fill_keys(array_keys($status_labels), 0);
        //     }
        // }

        ksort($list);

		foreach($dates as $d) {
			
			
			$mergeddata[$d]= array_merge_recursive([$d], generateStructuredArray($list, $status_series, $d));
			
		}
        foreach($list as $key => $values) {
            $dataseries[$values['status']] = array($values['status'], $values['nb']);
			$labels[] = $values['status'];
        }

		

        $db->free($resql);

        include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';            
        $result = '<div class="div-table-responsive-no-min">';
        $result .= '<table class="noborder nohover centpercent">';
        $result .= '<tr class="liste_titre">';
        $result .= '<td colspan="'.(count($status_labels)+1).'">'.$langs->trans("Statistics").' - '.$langs->trans("Statut par année").'</td>';
        $result .= '</tr>';

        if ($conf->use_javascript_ajax) {
            $dataarr = array_values($status_labels);

            $result .= '<tr>';
            $result .= '<td align="center" colspan="'.(count($status_labels)+1).'">';
            include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
            $dolgraph = new DolGraph();
            $mesg = $dolgraph->isGraphKo();
            var_dump($labels);
            if (!$mesg) {
                $dolgraph->SetData(array_values($dataseries));
                $dolgraph->SetDataColor(array('#ff8c66', '#ffcc99', '#66b2ff', '#99ccff', '#ff9999', '#99ff99', '#cc99ff'));
                // $dolgraph->setShowLegend(1);
                // $dolgraph->setShowPercent(2);
                $dolgraph->SetType(array('lines', 'lines', 'lines', 'lines', 'lines', 'lines', 'lines'));
                $dolgraph->setHeight('400');
                $dolgraph->setWidth('800');
                $dolgraph->setLegend($labels);
                $dolgraph->draw('infographstatusbyyear2');
                $result .= $dolgraph->show();
                $result .= '</td>';
                $result .= '</tr>';
            }
        }

        $result .= '<tr class="liste_total">';
        $result .= '<td>'.$langs->trans("Year").'</td>';
        foreach ($status_labels as $label) {
            $result .= '<td>'.$label.'</td>';
        }
        $result .= '</tr>';
        
        foreach ($list as $year => $statuses) {
            $result .= '<tr>';
            $result .= '<td>'.$year.'</td>';
            foreach ($statuses as $status => $count) {
                $result .= '<td>'.$count.'</td>';
            }
            $result .= '</tr>';
        }

        $result .= '</table>';
        $result .= '</div>';
    } else {
        dol_print_error($db);
    }

    return $result; 
}*/




function getStatusByYearChart()
{
    global $conf, $db, $langs, $user;

    // Récupérer les dates du formulaire
    $date_start = GETPOST('date_start', 'alphanohtml');
    $date_end = GETPOST('date_end', 'alphanohtml');

    // Convertir les dates en format Y-m-d
    if (!empty($date_start)) {
        $date_start = date('Y-m-d', strtotime(str_replace('/', '-', $date_start)));
    } else {
        $date_start = date('Y-m-d', strtotime('-1 year'));
    }

    if (!empty($date_end)) {
        $date_end = date('Y-m-d', strtotime(str_replace('/', '-', $date_end)));
    } else {
        $date_end = date('Y-m-d');
    }

    // Afficher le formulaire pour la sélection de dates avec centrage
    echo '<div style="text-align: center; margin: 20px auto;">'; // Conteneur centré
    echo '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display: inline-block; text-align: left;">'; // Formulaire centré
    echo '<div class="nowraponall inline-block divfordateinput">';
    echo '<label for="date_start">Date de début:</label>';
    echo '<input id="date_start" name="date_start" type="text" class="maxwidthdate hasDatepicker" maxlength="11" value="'.date('d/m/Y', strtotime($date_start)).'" onchange="dpChangeDay(\'date_start\',\'dd/MM/yyyy\');" size="10">';
    echo '</div>';
    echo '<div class="nowraponall inline-block divfordateinput">';
    echo '<label for="date_end">Date de fin:</label>';
    echo '<input id="date_end" name="date_end" type="text" class="maxwidthdate hasDatepicker" maxlength="11" value="'.date('d/m/Y', strtotime($date_end)).'" onchange="dpChangeDay(\'date_end\',\'dd/MM/yyyy\');" size="10">';
    echo '</div>';
    echo '<input type="submit" value="Filtrer">';
    echo '</form>';
    echo '</div>';

    // Définir les labels de statut
    $status_labels = array('0'=>'Brouillon', '1'=>'Validé', '3'=>'Vérifié', '4'=>'En cours', '5'=>'Soldée', '7'=>'Clôturé', '9'=>'Annulé');

    // Modification de la requête SQL pour inclure un total des constats par année
    $sql = "SELECT COUNT(co.rowid) as nb_constats, YEAR(co.dateEmeteur) as year, co.status";
    $sql .= " FROM ".MAIN_DB_PREFIX."constat_constat as co";
    $sql .= " WHERE co.dateEmeteur BETWEEN '".$db->escape($date_start)."' AND '".$db->escape($date_end)."'";
    $sql .= " GROUP BY YEAR(co.dateEmeteur), co.status";
    $sql .= " ORDER BY YEAR(co.dateEmeteur), co.status";

    $resql = $db->query($sql);

    if ($resql) {
        $list = array();
        $total_list = array();
        
        // Regrouper les données par année et statut
        while ($obj = $db->fetch_object($resql)) {
            if (isset($obj->year) && isset($obj->status)) {
                $status_label = $status_labels[$obj->status];
                if (!isset($list[$obj->year])) {
                    $list[$obj->year] = array_fill_keys(array_values($status_labels), 0);
                    $total_list[$obj->year] = 0; // Initialiser le total pour chaque année
                }
                $list[$obj->year][$status_label] += $obj->nb_constats;
                $total_list[$obj->year] += $obj->nb_constats; // Ajouter au total général
            }
        }

        ksort($list);

        // Préparer les données pour DolGraph
        $graph_data = array();
        foreach ($list as $year => $statuses) {
            $data = array($year); // Année seule
            foreach ($status_labels as $status) {
                $data[] = isset($statuses[$status]) ? $statuses[$status] : 0;
            }
            // Ajouter la colonne de total
            $data[] = isset($total_list[$year]) ? $total_list[$year] : 0;
            $graph_data[] = $data;
        }

        $db->free($resql);

        if ($conf->use_javascript_ajax) {
            $result = '<div align="center">';
            include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
            $dolgraph = new DolGraph();
            $mesg = $dolgraph->isGraphKo();
            if (!$mesg) {
                $dolgraph->SetData($graph_data);
                $dolgraph->SetDataColor(array('#ff8c66', '#ffcc99', '#66b2ff', '#99ccff', '#ff9999', '#99ff99', '#cc99ff', '#ffccff'));
                $dolgraph->SetLegend(array_merge(array_values($status_labels), ['Total']));
                $dolgraph->SetMaxValue(max(array_map('max', $graph_data)));
                $dolgraph->SetType(array('bars')); // Utilisation de barres pour l'affichage par année
                $dolgraph->SetMinValue(0);
                $dolgraph->SetWidth(800);
                $dolgraph->SetHeight(400);
                $dolgraph->SetTitle($langs->trans("Statut par année avec total"));
                $dolgraph->draw('infographstatusbyyear');
                $result .= $dolgraph->show();
            }
            $result .= '</div>';
        }
    } else {
        dol_print_error($db);
    }

    return $result;
}


function msgAgendaUpdateForConstat($object, $onlydiff, $excluded_key = array(), $included_key = array()) {
	global $langs; 

	$actionmsg = '';

	if(!empty($object->oldcopy) || !$onlydiff) {
		$object->fields = dol_sort_array($object->fields, 'position');

		foreach ($object->fields as $key => $val) {
			if(!empty($included_key) && !in_array($key, $included_key)) {
				continue;
			}

			if(in_array($key, $excluded_key)) {
				continue;
			}

			if($val['visible'] <= 0) {
				continue;
			}

			// Ignore special fields
			if (in_array($key, array('rowid', 'entity', 'import_key'))) {
				continue;
			}
			if (in_array($key, array('date', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
				if (!in_array(abs($val['visible']), array(1, 3, 4))) {
					continue; // Only 1 and 3 and 4 that are case to update
				}
			}
	
			if (in_array($key, array('statut', 'status'))) {
				$value = $object->LibStatut($object->$key, 2);
				$old_value = $object->LibStatut($object->oldcopy->$key, 2);
			}
			elseif (in_array($key, array('ref'))) {
				$value = $object->$key;
				$old_value = $object->oldcopy->$key;
			}
			elseif (preg_match('/^integer:([^:]*):([^:]*)/i', $val['type'], $reg)) {
				$stringforoptions = $reg[1].':'.$reg[2];
				// Special case: Force addition of getnomurlparam1 to -1 for users
				if ($reg[1] == 'User') {
					$stringforoptions .= ':#getnomurlparam1=-1';
				}
				$param['options'] = array($stringforoptions => $stringforoptions);

				$param_list = array_keys($param['options']);
				// Example: $param_list='ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'
				// Example: $param_list='ObjectClass:PathToClass:#getnomurlparam1=-1#getnomurlparam2=customer'

				$InfoFieldList = explode(":", $param_list[0]);

				$classname = $InfoFieldList[0];
				$classpath = $InfoFieldList[1];

				if (!empty($classpath)) {
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname)) {
						$object_value = new $classname($object->db);
						$object_oldvalue = new $classname($object->db);
						
						if ($object_value->element === 'product') {	// Special case for product because default valut of fetch are wrong
							$result_value = $object_value->fetch($value, '', '', '', 0, 1, 1);
						} else {
							$result_value = $object_value->fetch($object->$key);
						}
						if ($object_oldvalue->element === 'product') {	// Special case for product because default valut of fetch are wrong
							$result_oldvalue = $object_oldvalue->fetch($value, '', '', '', 0, 1, 1);
						} else {
							$result_oldvalue = $object_oldvalue->fetch($object->oldcopy->$key);
						}

						if($object_value->element === 'societe')  {
							if ($result_value > 0) {
								$value = $object_value->name;
							} else {
								$value = '';
							}
	
							if ($result_oldvalue > 0) {
								$old_value = $object_oldvalue->name;
							} else {
								$old_value = '';
							}
						}
						elseif($object_value->element === 'user')  {
							if ($result_value > 0) {
								$value = '<span class="fas fa-user infobox-adherent"></span> '.$object_value->getNomUrl(0, 'nolink', -1, 1);
							} else {
								$value = '';
							}
	
							if ($result_oldvalue > 0) {
								$old_value = '<span class="fas fa-user infobox-adherent"></span> '.$object_oldvalue->getNomUrl(0, 'nolink', -1, 1);
							} else {
								$old_value = '';
							}
						}
						elseif($object_value->element === 'contact')  {
							if ($result_value > 0) {
								$value = '<span class="fas fa-address-book pictofixedwidth" style="color: #6c6aa8;"></span>'.$object_value->getNomUrl(0, 'nolink', 0, '', -1, 1);
							} else {
								$value = '';
							}
	
							if ($result_oldvalue > 0) {
								$old_value = '<span class="fas fa-address-book pictofixedwidth" style="color: #6c6aa8;"></span>'.$object_oldvalue->getNomUrl(0, 'nolink', 0, '', -1, 1);
							} else {
								$old_value = '';
							}
						}
						else {
							if ($result_value > 0) {
								$value = $object_value->getNomUrl(1, 'nolink', 1);
							} else {
								$value = '';
							}
	
							if ($result_oldvalue > 0) {
								$old_value = $object_oldvalue->getNomUrl(1, 'nolink', 1);
							} else {
								$old_value = '';
							}
						}
					}
				} else {
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			}
			elseif ($val['type'] == 'boolean') {
				$value = ($object->$key ? 'Oui' : 'Non');
				$old_value = ($object->oldcopy->$key ? 'Oui' : 'Non');
			}
			else {
				$value = $object->showOutputField($val, $key, $object->$key);
				$old_value = $object->showOutputField($val, $key, $object->oldcopy->$key);
			}

			// Ajout des modification dans l'agenda
			if($value == $old_value && $onlydiff) {
				continue;
			}

			if(str_contains($val['type'], 'chkbxlst')) {
				preg_match_all('/<li[^>]*>(.*?)<\/li>/', $value, $matches);
				$value = implode(', ', $matches[1]);

				preg_match_all('/<li[^>]*>(.*?)<\/li>/', $old_value, $matches);
				$old_value = implode(', ', $matches[1]);
			}

			if($onlydiff) {
				$actionmsg .= "<strong>".$langs->transnoentities($val['label']).'</strong> : '.(!empty($old_value) ? $old_value : '/').' ➔ '.(!empty($value) ? $value : '/').'<br/>';
			}
			else {
				$actionmsg .= "<strong>".$langs->transnoentities($val['label']).'</strong> : '.(!empty($value) ? $value : '/').'<br/>';
			}
		}
	}

	return $actionmsg;
}

function show_actions_messaging_custom($conf, $langs, $db, $filterobj, $objcon = '', $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC', $exclude_actioncode = array())
{
	global $user, $conf;
	global $form;

	global $param, $massactionbutton;

	dol_include_once('/comm/action/class/actioncomm.class.php');

	// Check parameters
	if (!is_object($filterobj) && !is_object($objcon)) {
		dol_print_error('', 'BadParameter');
	}
  

	$histo = array();
	$numaction = 0;
	$now = dol_now();

	$sortfield_list = explode(',', $sortfield);
	$sortfield_label_list = array('a.id' => 'id', 'a.datep' => 'dp', 'a.percent' => 'percent');
	$sortfield_new_list = array();
	foreach ($sortfield_list as $sortfield_value) {
		$sortfield_new_list[] = $sortfield_label_list[trim($sortfield_value)];
	}

   
	$sortfield_new = implode(',', $sortfield_new_list);

	if (isModEnabled('agenda')) {
		// Search histo on actioncomm
		if (is_object($objcon) && $objcon->id > 0) {
			$sql = "SELECT DISTINCT a.id, a.label as label,";
		} else {
			$sql = "SELECT a.id, a.label as label,";
		}
		$sql .= " a.datep as dp,";
		$sql .= " a.note as message,";
		$sql .= " a.datep2 as dp2,";
		$sql .= " a.percent as percent, 'action' as type,";
		$sql .= " a.fk_element, a.elementtype,";
		$sql .= " a.fk_contact,";
		$sql .= " a.email_from as msg_from,";
		$sql .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
		$sql .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql .= ", sp.lastname, sp.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", m.lastname, m.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj)) {
			$sql .= ", o.ref";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_action";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";

		$force_filter_contact = false;
		if (is_object($objcon) && $objcon->id > 0) {
			$force_filter_contact = true;
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm_resources as r ON a.id = r.fk_actioncomm";
			$sql .= " AND r.element_type = '".$db->escape($objcon->table_element)."' AND r.fk_element = ".((int) $objcon->id);
		}

		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."element_resources as er";
			$sql .= " ON er.resource_type = 'dolresource'";
			$sql .= " AND er.element_id = a.id";
			$sql .= " AND er.resource_id = ".((int) $filterobj->id);
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", ".MAIN_DB_PREFIX."adherent as m";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", ".MAIN_DB_PREFIX."product as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", ".MAIN_DB_PREFIX."ticket as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", ".MAIN_DB_PREFIX."bom_bom as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", ".MAIN_DB_PREFIX."contrat as o";
		} elseif (is_object($filterobj) && !empty($filterobj->table_element)) {
			$sql .= ", ".MAIN_DB_PREFIX.$filterobj->table_element." as o";
		}

		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		if ($force_filter_contact === false) {
			if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur')) && $filterobj->id) {
				$sql .= " AND a.fk_soc = ".((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Project' && $filterobj->id) {
				$sql .= " AND a.fk_project = ".((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
				$sql .= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'order_supplier'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'product'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'ticket'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'bom'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'contract'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			}  elseif (is_object($filterobj) && !empty($filterobj->element)) {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = '".$filterobj->element.(property_exists($filterobj, 'module') ? '@'.$filterobj->module : '')."'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			}
		}

		// Condition on actioncode
		if (!empty($actioncode)) {
			if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
				if ($actioncode == 'AC_NON_AUTO') {
					$sql .= " AND c.type != 'systemauto'";
				} elseif ($actioncode == 'AC_ALL_AUTO') {
					$sql .= " AND c.type = 'systemauto'";
				} else {
					if ($actioncode == 'AC_OTH') {
						$sql .= " AND c.type != 'systemauto'";
					} elseif ($actioncode == 'AC_OTH_AUTO') {
						$sql .= " AND c.type = 'systemauto'";
					}
				}
			} else {
				if ($actioncode == 'AC_NON_AUTO') {
					$sql .= " AND c.type != 'systemauto'";
				} elseif ($actioncode == 'AC_ALL_AUTO') {
					$sql .= " AND c.type = 'systemauto'";
				} else {
					$sql .= " AND c.code = '".$db->escape($actioncode)."'";
				}
			}
		}
		if ($donetodo == 'todo') {
			$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
		} elseif ($donetodo == 'done') {
			$sql .= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
		}
		if (is_array($filters) && $filters['search_agenda_label']) {
			$sql .= natural_search('a.label', $filters['search_agenda_label']);
		}
		if (!empty($exclude_actioncode)) {
			$sql .= " AND c.code NOT IN (".implode(',', $exclude_actioncode).")";
		}
	}

	// Add also event from emailings. TODO This should be replaced by an automatic event ? May be it's too much for very large emailing.
	if (isModEnabled('mailing') && !empty($objcon->email)
		&& (empty($actioncode) || $actioncode == 'AC_OTH_AUTO' || $actioncode == 'AC_EMAILING')) {
		$langs->load("mails");

		$sql2 = "SELECT m.rowid as id, m.titre as label, mc.date_envoi as dp, mc.date_envoi as dp2, '100' as percent, 'mailing' as type";
		$sql2 .= ", null as fk_element, '' as elementtype, null as contact_id";
		$sql2 .= ", 'AC_EMAILING' as acode, '' as alabel, '' as apicto";
		$sql2 .= ", u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname"; // User that valid action
		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj)) {
			$sql2 .= ", '' as ref";
		}
		$sql2 .= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u";
		$sql2 .= " WHERE mc.email = '".$db->escape($objcon->email)."'"; // Search is done on email.
		$sql2 .= " AND mc.statut = 1";
		$sql2 .= " AND u.rowid = m.fk_user_valid";
		$sql2 .= " AND mc.fk_mailing=m.rowid";
	}

	if (!empty($sql) && !empty($sql2)) {
		$sql = $sql." UNION ".$sql2;
	} elseif (empty($sql) && !empty($sql2)) {
		$sql = $sql2;
	}

	// TODO Add limit in nb of results
	if ($sql) {	// May not be defined if module Agenda is not enabled and mailing module disabled too
		$sql .= $db->order($sortfield_new, $sortorder);

		dol_syslog("function.lib::show_actions_messaging", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);
          
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($obj->type == 'action') {
					$contactaction = new ActionComm($db);
					$contactaction->id = $obj->id;
					$result = $contactaction->fetchResources();
					if ($result < 0) {
						dol_print_error($db);
						setEventMessage("actions.lib::show_actions_messaging Error fetch ressource", 'errors');
					}

					//if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
					//elseif ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
					$tododone = '';
					if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && $obj->dp > $now)) {
						$tododone = 'todo';
					}

					$histo[$numaction] = array(
						'type'=>$obj->type,
						'tododone'=>$tododone,
						'id'=>$obj->id,
						'datestart'=>$db->jdate($obj->dp),
						'dateend'=>$db->jdate($obj->dp2),
						'note'=>$obj->label,
						'message'=>$obj->message,
						'percent'=>$obj->percent,

						'userid'=>$obj->user_id,
						'login'=>$obj->user_login,
						'userfirstname'=>$obj->user_firstname,
						'userlastname'=>$obj->user_lastname,
						'userphoto'=>$obj->user_photo,
						'msg_from'=>$obj->msg_from,

						'contact_id'=>$obj->fk_contact,
						'socpeopleassigned' => $contactaction->socpeopleassigned,
						'lastname' => (empty($obj->lastname) ? '' : $obj->lastname),
						'firstname' => (empty($obj->firstname) ? '' : $obj->firstname),
						'fk_element'=>$obj->fk_element,
						'elementtype'=>$obj->elementtype,
						// Type of event
						'acode'=>$obj->acode,
						'alabel'=>$obj->alabel,
						'libelle'=>$obj->alabel, // deprecated
						'apicto'=>$obj->apicto
					);
				} else {
					$histo[$numaction] = array(
						'type'=>$obj->type,
						'tododone'=>'done',
						'id'=>$obj->id,
						'datestart'=>$db->jdate($obj->dp),
						'dateend'=>$db->jdate($obj->dp2),
						'note'=>$obj->label,
						'message'=>$obj->message,
						'percent'=>$obj->percent,
						'acode'=>$obj->acode,

						'userid'=>$obj->user_id,
						'login'=>$obj->user_login,
						'userfirstname'=>$obj->user_firstname,
						'userlastname'=>$obj->user_lastname,
						'userphoto'=>$obj->user_photo
					);
				}

				$numaction++;
				$i++;
			}
		} else {
			dol_print_error($db);
		}
	}

	// Set $out to show events
	$out = '';

	if (!isModEnabled('agenda')) {
		$langs->loadLangs(array("admin", "errors"));
		$out = info_admin($langs->trans("WarningModuleXDisabledSoYouMayMissEventHere", $langs->transnoentitiesnoconv("Module2400Name")), 0, 0, 'warning');
	}

	if (isModEnabled('agenda') || (isModEnabled('mailing') && !empty($objcon->email))) {
		$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		$formactions = new FormActions($db);

		$actionstatic = new ActionComm($db);
		$userstatic = new User($db);
		$contactstatic = new Contact($db);
		$userGetNomUrlCache = array();
		$contactGetNomUrlCache = array();

		$out .= '<div class="filters-container" >';
		$out .= '<form name="listactionsfilter" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$out .= '<input type="hidden" name="token" value="'.newToken().'">';

		if ($objcon && get_class($objcon) == 'Contact' &&
			(is_null($filterobj) || get_class($filterobj) == 'Societe')) {
			$out .= '<input type="hidden" name="id" value="'.$objcon->id.'" />';
		} else {
			$out .= '<input type="hidden" name="id" value="'.$filterobj->id.'" />';
		}
		if ($filterobj && get_class($filterobj) == 'Societe') {
			$out .= '<input type="hidden" name="socid" value="'.$filterobj->id.'" />';
		}

		$out .= "\n";

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder borderbottom centpercent">';

		$out .= '<tr class="liste_titre">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		$out .= getTitleFieldOfList('Date', 0, $_SERVER["PHP_SELF"], 'a.datep', '', $param, '', $sortfield, $sortorder, '')."\n";

		$out .= '<th class="liste_titre"><strong class="hideonsmartphone">'.$langs->trans("Search").' : </strong></th>';
		if ($donetodo) {
			$out .= '<th class="liste_titre"></th>';
		}
		$out .= '<th class="liste_titre">';
		$out .= '<span class="fas fa-square inline-block fawidth30" style=" color: #ddd;" title="'.$langs->trans("ActionType").'"></span>';
		//$out .= img_picto($langs->trans("Type"), 'type');
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : -1, 0, 0, 1, 'minwidth200imp');
		$out .= '</th>';
		$out .= '<th class="liste_titre maxwidth100onsmartphone">';
		$out .= '<input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'" placeholder="'.$langs->trans("Label").'">';
		$out .= '</th>';

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		$out .= '</tr>';


		$out .= '</table>';

		$out .= '</form>';
		$out .= '</div>';

		$out .= "\n";

		$out .= '<ul class="timeline">';

		if ($donetodo) {
			$tmp = '';
			if (get_class($filterobj) == 'Societe') {
				$tmp .= '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&socid='.$filterobj->id.'&status=done">';
			}
			$tmp .= ($donetodo != 'done' ? $langs->trans("ActionsToDoShort") : '');
			$tmp .= ($donetodo != 'done' && $donetodo != 'todo' ? ' / ' : '');
			$tmp .= ($donetodo != 'todo' ? $langs->trans("ActionsDoneShort") : '');
			//$out.=$langs->trans("ActionsToDoShort").' / '.$langs->trans("ActionsDoneShort");
			if (get_class($filterobj) == 'Societe') {
				$tmp .= '</a>';
			}
			$out .= getTitleFieldOfList($tmp);
		}


		//require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
		//$caction=new CActionComm($db);
		//$arraylist=$caction->liste_array(1, 'code', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE)?1:0), '', 1);

		$actualCycleDate = false;

		// Loop on each event to show it
		foreach ($histo as $key => $value) {
			$actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

			$actionstatic->type_picto = $histo[$key]['apicto'];
			$actionstatic->type_code = $histo[$key]['acode'];

			$url = DOL_URL_ROOT.'/comm/action/card.php?id='.$histo[$key]['id'];

			$tmpa = dol_getdate($histo[$key]['datestart'], false);
			if ($actualCycleDate !== $tmpa['year'].'-'.$tmpa['yday']) {
				$actualCycleDate = $tmpa['year'].'-'.$tmpa['yday'];
				$out .= '<!-- timeline time label -->';
				$out .= '<li class="time-label">';
				$out .= '<span class="timeline-badge-date">';
				$out .= dol_print_date($histo[$key]['datestart'], 'daytext', 'tzuserrel', $langs);
				$out .= '</span>';
				$out .= '</li>';
				$out .= '<!-- /.timeline-label -->';
			}


			$out .= '<!-- timeline item -->'."\n";
			$out .= '<li class="timeline-code-'.strtolower($actionstatic->code).'">';

			$out .= getTimelineIcon($actionstatic, $histo, $key);

			$out .= '<div class="timeline-item">'."\n";

			$out .= '<span class="timeline-header-action">';

			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a class="timeline-btn" href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$out .= $histo[$key]['id'];
				$out .= '</a> ';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1, 'valignmiddle').' ';
			}

			if ($user->hasRight('agenda', 'allactions', 'create') ||
				(($actionstatic->authorid == $user->id || $actionstatic->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))) {
				$out .= '<a class="timeline-btn" href="'.DOL_MAIN_URL_ROOT.'/comm/action/card.php?action=edit&token='.newToken().'&id='.$actionstatic->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'"><i class="fa fa-pencil" title="'.$langs->trans("Modify").'" ></i></a>';
			}

			$out .= '</span>';
			// Date
			$out .= '<span class="time"><i class="fa fa-clock-o valignmiddle"></i> <span class="valignmiddle">';
			$out .= dol_print_date($histo[$key]['datestart'], 'dayhour', 'tzuserrel');
			if ($histo[$key]['dateend'] && $histo[$key]['dateend'] != $histo[$key]['datestart']) {
				$tmpa = dol_getdate($histo[$key]['datestart'], true);
				$tmpb = dol_getdate($histo[$key]['dateend'], true);
				if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'hour', 'tzuserrel');
				} else {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'dayhour', 'tzuserrel');
				}
			}
			$late = 0;
			if ($histo[$key]['percent'] == 0 && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] == 0 && !$histo[$key]['datestart'] && $histo[$key]['dateend'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && $histo[$key]['dateend'] && $histo[$key]['dateend'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && !$histo[$key]['dateend'] && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($late) {
				$out .= img_warning($langs->trans("Late")).' ';
			}
			$out .= "</span></span>\n";

			// Ref
			$out .= '<h3 class="timeline-header">';

			// Author of event
			$out .= '<div class="messaging-author inline-block tdoverflowmax150 valignmiddle marginrightonly">';
			if ($histo[$key]['userid'] > 0) {
				if (!isset($userGetNomUrlCache[$histo[$key]['userid']])) { // is in cache ?
					$userstatic->fetch($histo[$key]['userid']);
					$userGetNomUrlCache[$histo[$key]['userid']] = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
				}
				$out .= $userGetNomUrlCache[$histo[$key]['userid']];
			} elseif (!empty($histo[$key]['msg_from']) && $actionstatic->code == 'TICKET_MSG') {
				if (!isset($contactGetNomUrlCache[$histo[$key]['msg_from']])) {
					if ($contactstatic->fetch(0, null, '', $histo[$key]['msg_from']) > 0) {
						$contactGetNomUrlCache[$histo[$key]['msg_from']] = $contactstatic->getNomUrl(-1, '', 16);
					} else {
						$contactGetNomUrlCache[$histo[$key]['msg_from']] = $histo[$key]['msg_from'];
					}
				}
				$out .= $contactGetNomUrlCache[$histo[$key]['msg_from']];
			}
			$out .= '</div>';

			// Title
			$libelle = '';
			$out .= ' <div class="messaging-title inline-block">';

			if (preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$out .= $langs->trans('TicketNewMessage');
			} elseif (preg_match('/^TICKET_MSG_PRIVATE/', $actionstatic->code)) {
				$out .= $langs->trans('TicketNewMessage').' <em>('.$langs->trans('Private').')</em>';
			} elseif (isset($histo[$key]['type'])) {
				if ($histo[$key]['type'] == 'action') {
					$transcode = $langs->transnoentitiesnoconv("Action".$histo[$key]['acode']);
					$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : $histo[$key]['alabel']);
					$libelle = $histo[$key]['note'];
					$actionstatic->id = $histo[$key]['id'];
					$out .= dol_escape_htmltag(dol_trunc($libelle, 120));
				} elseif ($histo[$key]['type'] == 'mailing') {
					$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
					$transcode = $langs->transnoentitiesnoconv("Action".$histo[$key]['acode']);
					$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : 'Send mass mailing');
					$out .= dol_escape_htmltag(dol_trunc($libelle, 120));
				} else {
					$libelle .= $histo[$key]['note'];
					$out .= dol_escape_htmltag(dol_trunc($libelle, 120));
				}
			}

			$out .= '</div>';

			$out .= '</h3>';

			if (!empty($histo[$key]['message'] && $histo[$key]['message'] != $libelle)
				&& $actionstatic->code != 'AC_TICKET_CREATE'
				&& $actionstatic->code != 'AC_TICKET_MODIFY'
			) {
				$out .= '<div class="timeline-body">';
				$out .= $histo[$key]['message'];
				$out .= '</div>';
			}

			// Timeline footer
			$footer = '';

			// Contact for this action
			if (isset($histo[$key]['socpeopleassigned']) && is_array($histo[$key]['socpeopleassigned']) && count($histo[$key]['socpeopleassigned']) > 0) {
				$contactList = '';
				foreach ($histo[$key]['socpeopleassigned'] as $cid => $Tab) {
					$contact = new Contact($db);
					$result = $contact->fetch($cid);

					if ($result < 0) {
						dol_print_error($db, $contact->error);
					}

					if ($result > 0) {
						$contactList .= !empty($contactList) ? ', ' : '';
						$contactList .= $contact->getNomUrl(1);
						if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
							if (!empty($contact->phone_pro)) {
								$contactList .= '('.dol_print_phone($contact->phone_pro).')';
							}
						}
					}
				}

				$footer .= $langs->trans('ActionOnContact').' : '.$contactList;
			} elseif (empty($objcon->id) && isset($histo[$key]['contact_id']) && $histo[$key]['contact_id'] > 0) {
				$contact = new Contact($db);
				$result = $contact->fetch($histo[$key]['contact_id']);

				if ($result < 0) {
					dol_print_error($db, $contact->error);
				}

				if ($result > 0) {
					$footer .= $contact->getNomUrl(1);
					if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
						if (!empty($contact->phone_pro)) {
							$footer .= '('.dol_print_phone($contact->phone_pro).')';
						}
					}
				}
			}

			$documents = getActionCommEcmList($actionstatic);
			if (!empty($documents)) {
				$footer .= '<div class="timeline-documents-container">';
				foreach ($documents as $doc) {
					$footer .= '<span id="document_'.$doc->id.'" class="timeline-documents" ';
					$footer .= ' data-id="'.$doc->id.'" ';
					$footer .= ' data-path="'.$doc->filepath.'"';
					$footer .= ' data-filename="'.dol_escape_htmltag($doc->filename).'" ';
					$footer .= '>';

					$filePath = DOL_DATA_ROOT.'/'.$doc->filepath.'/'.$doc->filename;
					$mime = dol_mimetype($filePath);
					$file = $actionstatic->id.'/'.$doc->filename;
					$thumb = $actionstatic->id.'/thumbs/'.substr($doc->filename, 0, strrpos($doc->filename, '.')).'_mini'.substr($doc->filename, strrpos($doc->filename, '.'));
					$doclink = dol_buildpath('document.php', 1).'?modulepart=actions&attachment=0&file='.urlencode($file).'&entity='.$conf->entity;
					$viewlink = dol_buildpath('viewimage.php', 1).'?modulepart=actions&file='.urlencode($thumb).'&entity='.$conf->entity;

					$mimeAttr = ' mime="'.$mime.'" ';
					$class = '';
					if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
						$class .= ' documentpreview';
					}

					$footer .= '<a href="'.$doclink.'" class="btn-link '.$class.'" target="_blank" rel="noopener noreferrer" '.$mimeAttr.' >';
					$footer .= img_mime($filePath).' '.$doc->filename;
					$footer .= '</a>';

					$footer .= '</span>';
				}
				$footer .= '</div>';
			}

			if (!empty($footer)) {
				$out .= '<div class="timeline-footer">'.$footer.'</div>';
			}

			$out .= '</div>'."\n"; // end timeline-item

			$out .= '</li>';
			$out .= '<!-- END timeline item -->';

			$i++;
		}

		$out .= "</ul>\n";

		if (empty($histo)) {
			$out .= '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
		}
	}

	if ($noprint) {
		return $out;
	} else {
		print $out;
	}
}

 