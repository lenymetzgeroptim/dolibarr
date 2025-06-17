<?php
/* Copyright (C) 2022 METZGER Leny <l.metzger@optim-industries.fr>
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
 * \file    formationhabilitation/lib/formationhabilitation.lib.php
 * \ingroup formationhabilitation
 * \brief   Library files with common functions for FormationHabilitation
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function formationhabilitationAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("formationhabilitation@formationhabilitation");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/formationhabilitation/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/admin/formation_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields")." Formations";
	$head[$h][2] = 'formation_extrafields';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/admin/habilitation_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields")." Habilitations";
	$head[$h][2] = 'habilitation_extrafields';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/admin/test_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields")." Tests";
	$head[$h][2] = 'test_extrafields';
	$h++;
	
	$head[$h][0] = dol_buildpath("/formationhabilitation/admin/theme_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields")." Themes";
	$head[$h][2] = 'theme_extrafields';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@formationhabilitation:/formationhabilitation/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@formationhabilitation:/formationhabilitation/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'formationhabilitation@formationhabilitation');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'formationhabilitation@formationhabilitation', 'remove');

	return $head;
}

/**
 * 	Return tous les volets à partir du dictionnaire
 *
 * 	@return	array						
 */
function getLabelList($table, $fieldname)
{
	global $conf, $user, $db;
	$res = array();

	$sql = "SELECT t.rowid, t.$fieldname";
	$sql .= " FROM ".MAIN_DB_PREFIX."$table as t";
	$sql .= " ORDER BY t.$fieldname";

	dol_syslog("formationhabilitation.lib.php::getLabelList", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		while($obj = $db->fetch_object($resql)) {
			$res[$obj->rowid] = $obj->$fieldname;
		}

		$db->free($resql);
		return $res;
	} else {
		return -1;
	}
}

function msgAgendaUpdate($object, $onlydiff, $excluded_key = array(), $included_key = array()) {
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
			if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
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

/**
 * Prepare formation user pages header
 *
 * @return array
 */
function formationhabilitationUserPrepareHead($object)
{
	global $langs, $conf;

	$langs->load("formationhabilitation@formationhabilitation");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=formation';
	$head[$h][1] = $langs->trans("Formations");
	$head[$h][2] = 'formation';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=habilitation';
	$head[$h][1] = $langs->trans("Habilitations");
	$head[$h][2] = 'habilitation';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=autorisation';
	$head[$h][1] = $langs->trans("Autorisations");
	$head[$h][2] = 'autorisation';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=volet';
	$head[$h][1] = $langs->trans("Volets");
	$head[$h][2] = 'volet';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=convocation';
	$head[$h][1] = $langs->trans("Convocations");
	$head[$h][2] = 'convocation';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=visitemedical';
	$head[$h][1] = $langs->trans("VisiteMedicals");
	$head[$h][2] = 'visitemedical';
	$h++;

	$head[$h][0] = dol_buildpath("/formationhabilitation/user_messaging.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Agenda");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@formationhabilitation:/formationhabilitation/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@formationhabilitation:/formationhabilitation/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'formationhabilitationuser@formationhabilitation');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'formationhabilitationuser@formationhabilitation', 'remove');

	return $head;
}

/**
 * Prepare formation index pages header
 *
 * @return array
 */
function formationhabilitationIndexPrepareHead($object)
{
	global $langs, $conf, $user;

	$langs->load("formationhabilitation@formationhabilitation");

	$h = 0;
	$head = array();

	if ($user->rights->formationhabilitation->userformation->read || $user->rights->formationhabilitation->userformation->readall) {
		$head[$h][0] = dol_buildpath("/formationhabilitation/formationhabilitationindex.php", 1).'?onglet=formation';
		$head[$h][1] = $langs->trans("Formations");
		$head[$h][2] = 'formation';
		$h++;
	}

	if ($user->rights->formationhabilitation->userhabilitation_autorisation->read || $user->rights->formationhabilitation->userhabilitation_autorisation->readall) {
		$head[$h][0] = dol_buildpath("/formationhabilitation/formationhabilitationindex.php", 1).'?onglet=habilitation';
		$head[$h][1] = $langs->trans("Habilitations");
		$head[$h][2] = 'habilitation';
		$h++;
	}

	if ($user->rights->formationhabilitation->userhabilitation_autorisation->read || $user->rights->formationhabilitation->userhabilitation_autorisation->readall) {
		$head[$h][0] = dol_buildpath("/formationhabilitation/formationhabilitationindex.php", 1).'?onglet=autorisation';
		$head[$h][1] = $langs->trans("Autorisations");
		$head[$h][2] = 'autorisation';
		$h++;
	}

	if ($user->rights->formationhabilitation->uservolet->read || $user->rights->formationhabilitation->uservolet->readall) {
		$head[$h][0] = dol_buildpath("/formationhabilitation/formationhabilitationindex.php", 1).'?onglet=volet';
		$head[$h][1] = $langs->trans("Volets");
		$head[$h][2] = 'volet';
		$h++;
	}

	if ($user->rights->formationhabilitation->visitemedical->read || $user->rights->formationhabilitation->visitemedical->readall) {
		$head[$h][0] = dol_buildpath("/formationhabilitation/formationhabilitationindex.php", 1).'?onglet=visitemedical';
		$head[$h][1] = $langs->trans("Visites médicale");
		$head[$h][2] = 'visitemedical';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@formationhabilitation:/formationhabilitation/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@formationhabilitation:/formationhabilitation/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'formationhabilitationindex@formationhabilitation');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'formationhabilitationindex@formationhabilitation', 'remove');

	return $head;
}


/**
 *    	Show html area with actions in messaging format.
 *      Note: Global parameter $param must be defined.
 *
 * 		@param	Conf		       $conf		   Object conf
 * 		@param	Translate	       $langs		   Object langs
 * 		@param	DoliDB		       $db			   Object db
 * 		@param	mixed			   $filterobj	   Filter on object Adherent|Societe|Project|Product|CommandeFournisseur|Dolresource|Ticket|... to list events linked to an object
 * 		@param	Contact		       $objcon		   Filter on object contact to filter events on a contact
 *      @param  int			       $noprint        Return string but does not output it
 *      @param  string		       $actioncode     Filter on actioncode
 *      @param  string             $donetodo       Filter on event 'done' or 'todo' or ''=nofilter (all).
 *      @param  array              $filters        Filter on other fields
 *      @param  string             $sortfield      Sort field
 *      @param  string             $sortorder      Sort order
 *      @return	string|void				           Return html part or void if noprint is 1
 */
function show_actions_messaging_formationhabilitation($conf, $langs, $db, $filterobj, $objcon = '', $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC')
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
		} elseif (is_object($filterobj) && get_class($filterobj) == 'User') {
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
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_extrafields as ae on ae.fk_object = a.id";
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
		} elseif (is_object($filterobj) && get_class($filterobj) == 'User') {
			$sql .= ", ".MAIN_DB_PREFIX."user as m";
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
		}

		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		if ($force_filter_contact === false) {
			$sql .= " AND ae.fk_element2 = m.rowid AND ae.elementtype2 = 'user'";
			if ($filterobj->id) {
				$sql .= " AND ae.fk_element2 = ".((int) $filterobj->id);
			}
		}

		// Condition on actioncode
		if (!empty($actioncode)) {
			if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
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

		if(!$user->rights->formationhabilitation->formation->readcoutpedagogique && !$user->rights->formationhabilitation->formation->readcoutall) {
			$sql .= " AND a.extraparams IS NULL";
		}
		elseif(!$user->rights->formationhabilitation->formation->readcoutall) {
			$sql .= " AND (a.extraparams IS NULL OR a.extraparams = 'costpedagogique')";
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
		} elseif (is_object($filterobj) && get_class($filterobj) == 'User') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
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
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', !getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : -1, 0, 0, 1, 'minwidth200imp');
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

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
		$caction = new CActionComm($db);
		$arraylist = $caction->liste_array(1, 'code', '', (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : 0), '', 1);

		$actualCycleDate = false;

		// Loop on each event to show it
		foreach ($histo as $key => $value) {
			$actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

			$actionstatic->type_picto = $histo[$key]['apicto'];
			$actionstatic->type_code = $histo[$key]['acode'];

			$labeltype = $actionstatic->type_code;
			if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && empty($arraylist[$labeltype])) {
				$labeltype = 'AC_OTH';
			}
			if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$labeltype = $langs->trans("Message");
			} else {
				if (!empty($arraylist[$labeltype])) {
					$labeltype = $arraylist[$labeltype];
				}
				if ($actionstatic->type_code == 'AC_OTH_AUTO' && ($actionstatic->type_code != $actionstatic->code) && $labeltype && !empty($arraylist[$actionstatic->code])) {
					$labeltype .= ' - '.$arraylist[$actionstatic->code]; // Use code in priority on type_code
				}
			}

			$url = DOL_URL_ROOT.'/comm/action/card.php?id='.$histo[$key]['id'];

			$tmpa = dol_getdate($histo[$key]['datestart'], false);

			if (isset($tmpa['year']) && isset($tmpa['yday']) && $actualCycleDate !== $tmpa['year'].'-'.$tmpa['yday']) {
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

			//$timelineicon = getTimelineIcon($actionstatic, $histo, $key);
			$typeicon = $actionstatic->getTypePicto('pictofixedwidth timeline-icon-not-applicble', $labeltype);
			//$out .= $timelineicon;
			//var_dump($timelineicon);
			$out .= $typeicon;

			$out .= '<div class="timeline-item">'."\n";

			$out .= '<span class="time timeline-header-action2">';

			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a class="paddingleft paddingright timeline-btn2 editfielda" href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$out .= $histo[$key]['id'];
				$out .= '</a> ';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1, 'valignmiddle').' ';
			}

			if ($user->hasRight('agenda', 'allactions', 'create') ||
				(($actionstatic->authorid == $user->id || $actionstatic->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))) {
				$out .= '<a class="paddingleft paddingright timeline-btn2 editfielda" href="'.DOL_MAIN_URL_ROOT.'/comm/action/card.php?action=edit&token='.newToken().'&id='.$actionstatic->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'">';
				//$out .= '<i class="fa fa-pencil" title="'.$langs->trans("Modify").'" ></i>';
				$out .= img_picto($langs->trans("Modify"), 'edit', 'class="edita"');
				$out .= '</a>';
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
			$out .= ' <div class="messaging-title inline-block">';
			//$out .= $actionstatic->getTypePicto();
			// if (empty($conf->dol_optimize_smallscreen) && $actionstatic->type_code != 'AC_OTH_AUTO') {
			// 	$out .= $labeltype.' - ';
			// }

			$libelle = '';
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

			if (isset($histo[$key]['elementtype']) && !empty($histo[$key]['fk_element'])) {
				if (isset($conf->cache['elementlinkcache'][$histo[$key]['elementtype']]) && isset($conf->cache['elementlinkcache'][$histo[$key]['elementtype']][$histo[$key]['fk_element']])) {
					$link = $conf->cache['elementlinkcache'][$histo[$key]['elementtype']][$histo[$key]['fk_element']];
				} else {
					if (!isset($conf->cache['elementlinkcache'][$histo[$key]['elementtype']])) {
						$conf->cache['elementlinkcache'][$histo[$key]['elementtype']] = array();
					}
					$link = dolGetElementUrl($histo[$key]['fk_element'], $histo[$key]['elementtype'], 1);
					$conf->cache['elementlinkcache'][$histo[$key]['elementtype']][$histo[$key]['fk_element']] = $link;
				}
				if ($link) {
					$out .= ' - '.$link;
				}
			}

			$out .= '</div>';

			$out .= '</h3>';

			// Message
			if (!empty($histo[$key]['message'] && $histo[$key]['message'] != $libelle)
				&& $actionstatic->code != 'AC_TICKET_CREATE'
				&& $actionstatic->code != 'AC_TICKET_MODIFY'
			) {
				$out .= '<div class="timeline-body wordbreak">';
				$truncateLines = getDolGlobalInt('MAIN_TRUNCATE_TIMELINE_MESSAGE', 3);
				$truncatedText = dolGetFirstLineOfText($histo[$key]['message'], $truncateLines);
				if ($truncateLines > 0 && strlen($histo[$key]['message']) > strlen($truncatedText)) {
					$out .= '<div class="readmore-block --closed" >';
					$out .= '	<div class="readmore-block__excerpt" >';
					$out .= 	$truncatedText ;
					$out .= ' 	<a class="read-more-link" data-read-more-action="open" href="'.DOL_MAIN_URL_ROOT.'/comm/action/card.php?id='.$actionstatic->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'" >'.$langs->trans("ReadMore").' <span class="fa fa-chevron-right" aria-hidden="true"></span></a>';
					$out .= '	</div>';
					$out .= '	<div class="readmore-block__full-text" >';
					$out .= $histo[$key]['message'];
					$out .= ' 	<a class="read-less-link" data-read-more-action="close" href="#" ><span class="fa fa-chevron-up" aria-hidden="true"></span> '.$langs->trans("ReadLess").'</a>';
					$out .= '	</div>';
					$out .= '</div>';
				} else {
					$out .= $histo[$key]['message'];
				}

				$out .= '</div>';
			}

			// Timeline footer
			$footer = '';

			// Contact for this action
			if (isset($histo[$key]['socpeopleassigned']) && is_array($histo[$key]['socpeopleassigned']) && count($histo[$key]['socpeopleassigned']) > 0) {
				$contactList = '';
				foreach ($histo[$key]['socpeopleassigned'] as $cid => $Tab) {
					if (empty($conf->cache['contact'][$histo[$key]['contact_id']])) {
						$contact = new Contact($db);
						$contact->fetch($cid);
						$conf->cache['contact'][$histo[$key]['contact_id']] = $contact;
					} else {
						$contact = $conf->cache['contact'][$histo[$key]['contact_id']];
					}

					if ($contact) {
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
				if (empty($conf->cache['contact'][$histo[$key]['contact_id']])) {
					$contact = new Contact($db);
					$result = $contact->fetch($histo[$key]['contact_id']);
					$conf->cache['contact'][$histo[$key]['contact_id']] = $contact;
				} else {
					$contact = $conf->cache['contact'][$histo[$key]['contact_id']];
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

		$out .= '<script>
				jQuery(document).ready(function () {
				   $(document).on("click", "[data-read-more-action]", function(e){
					   let readMoreBloc = $(this).closest(".readmore-block");
					   if(readMoreBloc.length > 0){
							e.preventDefault();
							if($(this).attr("data-read-more-action") == "close"){
								readMoreBloc.addClass("--closed").removeClass("--open");
								 $("html, body").animate({
									scrollTop: readMoreBloc.offset().top - 200
								}, 100);
							}else{
								readMoreBloc.addClass("--open").removeClass("--closed");
							}
					   }
					});
				});
			</script>';


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

/**
 *    	Show html area with actions (done or not, ignore the name of function).
 *      Note: Global parameter $param must be defined.
 *
 * 		@param	Conf		       $conf		   Object conf
 * 		@param	Translate	       $langs		   Object langs
 * 		@param	DoliDB		       $db			   Object db
 * 		@param	mixed			   $filterobj	   Filter on object Adherent|Societe|Project|Product|CommandeFournisseur|Dolresource|Ticket... to list events linked to an object
 * 		@param	Contact		       $objcon		   Filter on object contact to filter events on a contact
 *      @param  int			       $noprint        Return string but does not output it
 *      @param  string|string[]    $actioncode     Filter on actioncode
 *      @param  string             $donetodo       Filter on event 'done' or 'todo' or ''=nofilter (all).
 *      @param  array              $filters        Filter on other fields
 *      @param  string             $sortfield      Sort field
 *      @param  string             $sortorder      Sort order
 *      @param	string			   $module		   You can add module name here if elementtype in table llx_actioncomm is objectkey@module
 *      @return	string|void				           Return html part or void if noprint is 1
 */
function show_actions_done_formationhabilitation($conf, $langs, $db, $filterobj, $objcon = '', $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC', $module = '')
{
	global $user, $conf, $hookmanager;
	global $form;
	global $param, $massactionbutton;

	$start_year = GETPOST('dateevent_startyear', 'int');
	$start_month = GETPOST('dateevent_startmonth', 'int');
	$start_day = GETPOST('dateevent_startday', 'int');
	$end_year = GETPOST('dateevent_endyear', 'int');
	$end_month = GETPOST('dateevent_endmonth', 'int');
	$end_day = GETPOST('dateevent_endday', 'int');
	$tms_start = '';
	$tms_end = '';

	if (!empty($start_year) && !empty($start_month) && !empty($start_day)) {
		$tms_start = dol_mktime(0, 0, 0, $start_month, $start_day, $start_year, 'tzuserrel');
	}
	if (!empty($end_year) && !empty($end_month) && !empty($end_day)) {
		$tms_end = dol_mktime(23, 59, 59, $end_month, $end_day, $end_year, 'tzuserrel');
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$tms_start = '';
		$tms_end = '';
	}
	dol_include_once('/comm/action/class/actioncomm.class.php');

	// Check parameters
	if (!is_object($filterobj) && !is_object($objcon)) {
		dol_print_error('', 'BadParameter');
	}

	$out = '';
	$histo = array();
	$numaction = 0;
	$now = dol_now('tzuser');

	// Open DSI -- Fix order by -- Begin
	$sortfield_list = explode(',', $sortfield);
	$sortfield_label_list = array('a.id' => 'id', 'a.datep' => 'dp', 'a.percent' => 'percent');
	$sortfield_new_list = array();
	foreach ($sortfield_list as $sortfield_value) {
		$sortfield_new_list[] = $sortfield_label_list[trim($sortfield_value)];
	}
	$sortfield_new = implode(',', $sortfield_new_list);

	$sql = '';

	if (isModEnabled('agenda')) {
		// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
		$hookmanager->initHooks(array('agendadao'));

		// Recherche histo sur actioncomm
		if (is_object($objcon) && $objcon->id > 0) {
			$sql = "SELECT DISTINCT a.id, a.label as label,";
		} else {
			$sql = "SELECT a.id, a.label as label,";
		}
		$sql .= " a.datep as dp,";
		$sql .= " a.datep2 as dp2,";
		$sql .= " a.percent as percent, 'action' as type,";
		$sql .= " a.fk_element, a.elementtype,";
		$sql .= " a.fk_contact,";
		$sql .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
		$sql .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
		if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur'))) {
			$sql .= ", sp.lastname, sp.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			/* Nothing */
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Project') {
			/* Nothing */
		} elseif (is_object($filterobj) && (get_class($filterobj) == 'Adherent' || get_class($filterobj) == 'User')) {
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
		} elseif (is_object($filterobj) && is_array($filterobj->fields) && is_array($filterobj->fields['rowid']) && $filterobj->table_element && $filterobj->element) {
			if (!empty($filterobj->fields['ref'])) {
				$sql .= ", o.ref";
			} elseif (!empty($filterobj->fields['label'])) {
				$sql .= ", o.label";
			}
		}

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon);
		$reshook = $hookmanager->executeHooks('showActionsDoneListSelect', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}

		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_extrafields as ae on ae.fk_object = a.id";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_action";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";

		$force_filter_contact = false;
		if (is_object($objcon) && $objcon->id > 0) {
			$force_filter_contact = true;
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm_resources as r ON a.id = r.fk_actioncomm";
			$sql .= " AND r.element_type = '".$db->escape($objcon->table_element)."' AND r.fk_element = ".((int) $objcon->id);
		}

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon);
		$reshook = $hookmanager->executeHooks('showActionsDoneListFrom', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}

		if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur'))) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."element_resources as er";
			$sql .= " ON er.resource_type = 'dolresource'";
			$sql .= " AND er.element_id = a.id";
			$sql .= " AND er.resource_id = ".((int) $filterobj->id);
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Project') {
			/* Nothing */
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", ".MAIN_DB_PREFIX."adherent as m";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'User') {
			$sql .= ", ".MAIN_DB_PREFIX."user as m";
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
		} elseif (is_object($filterobj) && is_array($filterobj->fields) && is_array($filterobj->fields['rowid'])
			&& ((!empty($filterobj->fields['ref']) && is_array($filterobj->fields['ref'])) || (!empty($filterobj->fields['label']) && is_array($filterobj->fields['label'])) || (!empty($filterobj->fields['titre']) && is_array($filterobj->fields['titre'])))
			&& $filterobj->table_element && $filterobj->element) {
			$sql .= ", ".MAIN_DB_PREFIX.$filterobj->table_element." as o";
		} elseif (is_object($filterobj)) {
			return 'Bad value for $filterobj';
		}

		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		if ($force_filter_contact === false) {
			$sql .= " AND ae.fk_element2 = m.rowid AND ae.elementtype2 = 'user'";
			if ($filterobj->id) {
				$sql .= " AND ae.fk_element2 = ".((int) $filterobj->id);
			}
		}

		if (!empty($tms_start) && !empty($tms_end)) {
			$sql .= " AND ((a.datep BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."') OR (a.datep2 BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."'))";
		} elseif (empty($tms_start) && !empty($tms_end)) {
			$sql .= " AND ((a.datep <= '".$db->idate($tms_end)."') OR (a.datep2 <= '".$db->idate($tms_end)."'))";
		} elseif (!empty($tms_start) && empty($tms_end)) {
			$sql .= " AND ((a.datep >= '".$db->idate($tms_start)."') OR (a.datep2 >= '".$db->idate($tms_start)."'))";
		}

		if (is_array($actioncode) && !empty($actioncode)) {
			$sql .= ' AND (';
			foreach ($actioncode as $key => $code) {
				if ($key != 0) {
					$sql .= " OR ";
				}
				if (!empty($code)) {
					addEventTypeSQL($sql, $code, "");
				}
			}
			$sql .= ')';
		} elseif (!empty($actioncode)) {
			addEventTypeSQL($sql, $actioncode);
		}

		if(!$user->rights->formationhabilitation->formation->readcoutpedagogique && !$user->rights->formationhabilitation->formation->readcoutall) {
			$sql .= " AND a.extraparams IS NULL";
		}
		elseif(!$user->rights->formationhabilitation->formation->readcoutall) {
			$sql .= " AND (a.extraparams IS NULL OR a.extraparams = 'costpedagogique')";
		}

		addOtherFilterSQL($sql, $donetodo, $now, $filters);

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon, 'module' => $module);
		$reshook = $hookmanager->executeHooks('showActionsDoneListWhere', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}

		if (is_array($actioncode)) {
			foreach ($actioncode as $code) {
				$sql2 = addMailingEventTypeSQL($code, $objcon, $filterobj);
				if (!empty($sql2)) {
					if (!empty($sql)) {
						$sql = $sql." UNION ".$sql2;
					} elseif (empty($sql)) {
						$sql = $sql2;
					}
					break;
				}
			}
		} else {
			$sql2 = addMailingEventTypeSQL($actioncode, $objcon, $filterobj);
			if (!empty($sql) && !empty($sql2)) {
				$sql = $sql." UNION ".$sql2;
			} elseif (empty($sql) && !empty($sql2)) {
				$sql = $sql2;
			}
		}
	}

	//TODO Add limit in nb of results
	if ($sql) {
		$sql .= $db->order($sortfield_new, $sortorder);

		dol_syslog("company.lib::show_actions_done", LOG_DEBUG);

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
						setEventMessage("company.lib::show_actions_done Error fetch ressource", 'errors');
					}

					//if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
					//elseif ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
					$tododone = '';
					if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && (!empty($obj->datep) && $obj->datep > $now))) {
						$tododone = 'todo';
					}

					$histo[$numaction] = array(
						'type'=>$obj->type,
						'tododone'=>$tododone,
						'id'=>$obj->id,
						'datestart'=>$db->jdate($obj->dp),
						'dateend'=>$db->jdate($obj->dp2),
						'note'=>$obj->label,
						'percent'=>$obj->percent,

						'userid'=>$obj->user_id,
						'login'=>$obj->user_login,
						'userfirstname'=>$obj->user_firstname,
						'userlastname'=>$obj->user_lastname,
						'userphoto'=>$obj->user_photo,

						'contact_id'=>$obj->fk_contact,
						'socpeopleassigned' => $contactaction->socpeopleassigned,
						'lastname' => empty($obj->lastname) ? '' : $obj->lastname,
						'firstname' => empty($obj->firstname) ? '' : $obj->firstname,
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

	if (isModEnabled('agenda')|| (isModEnabled('mailing') && !empty($objcon->email))) {
		$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		$formactions = new FormActions($db);

		$actionstatic = new ActionComm($db);
		$userstatic = new User($db);
		$userlinkcache = array();
		$contactstatic = new Contact($db);
		$elementlinkcache = array();

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
		$out .= '<table class="noborder centpercent">';

		$out .= '<tr class="liste_titre">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		if ($donetodo) {
			$out .= '<td class="liste_titre"></td>';
		}

		$out .= '<td class="liste_titre"><input type="text" class="width50" name="search_rowid" value="'.(isset($filters['search_rowid']) ? $filters['search_rowid'] : '').'"></td>';
		$out .= '<td class="liste_titre"></td>';
		$out .= '<td class="liste_titre">';
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', !getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : -1, 0, (!getDolGlobalString('AGENDA_USE_MULTISELECT_TYPE') ? 0 : 1), 1, 'minwidth100 maxwidth150');
		$out .= '</td>';
		$out .= '<td class="liste_titre maxwidth100onsmartphone"><input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'"></td>';
		$out .= '<td class="liste_titre center">';
		$out .= $form->selectDateToDate($tms_start, $tms_end, 'dateevent', 1);
		$out .= '</td>';
		$out .= '<td class="liste_titre"></td>';
		$out .= '<td class="liste_titre"></td>';
		$out .= '<td class="liste_titre"></td>';
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<td class="liste_titre" align="middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</td>';
		}
		$out .= '</tr>';

		$out .= '<tr class="liste_titre">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
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
		$out .= getTitleFieldOfList("Ref", 0, $_SERVER["PHP_SELF"], 'a.id', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("Owner");
		$out .= getTitleFieldOfList("Type");
		$out .= getTitleFieldOfList("Label", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("Date", 0, $_SERVER["PHP_SELF"], 'a.datep,a.id', '', $param, '', $sortfield, $sortorder, 'center ');
		$out .= getTitleFieldOfList("RelatedObjects", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("ActionOnContact", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'tdoverflowmax125 ', 0, '', 0);
		$out .= getTitleFieldOfList("Status", 0, $_SERVER["PHP_SELF"], 'a.percent', '', $param, '', $sortfield, $sortorder, 'center ');
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
		$out .= '</tr>';

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
		$caction = new CActionComm($db);
		$arraylist = $caction->liste_array(1, 'code', '', (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : 0), '', 1);

		foreach ($histo as $key => $value) {
			$actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

			$actionstatic->type_picto = $histo[$key]['apicto'];
			$actionstatic->type_code = $histo[$key]['acode'];

			$out .= '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				$out .= '<td></td>';
			}

			// Done or todo
			if ($donetodo) {
				$out .= '<td class="nowrap">';
				$out .= '</td>';
			}

			// Ref
			$out .= '<td class="nowraponall">';
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$out .= $histo[$key]['id'];
				$out .= '</a>';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1);
			}
			$out .= '</td>';

			// Author of event
			$out .= '<td class="tdoverflowmax125">';
			if ($histo[$key]['userid'] > 0) {
				if (isset($userlinkcache[$histo[$key]['userid']])) {
					$link = $userlinkcache[$histo[$key]['userid']];
				} else {
					$userstatic->fetch($histo[$key]['userid']);
					$link = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
					$userlinkcache[$histo[$key]['userid']] = $link;
				}
				$out .= $link;
			}
			$out .= '</td>';

			// Type
			$labeltype = $actionstatic->type_code;
			if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && empty($arraylist[$labeltype])) {
				$labeltype = 'AC_OTH';
			}
			if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$labeltype = $langs->trans("Message");
			} else {
				if (!empty($arraylist[$labeltype])) {
					$labeltype = $arraylist[$labeltype];
				}
				if ($actionstatic->type_code == 'AC_OTH_AUTO' && ($actionstatic->type_code != $actionstatic->code) && $labeltype && !empty($arraylist[$actionstatic->code])) {
					$labeltype .= ' - '.$arraylist[$actionstatic->code]; // Use code in priority on type_code
				}
			}
			$out .= '<td class="tdoverflowmax125" title="'.$labeltype.'">';
			$out .= $actionstatic->getTypePicto();
			//if (empty($conf->dol_optimize_smallscreen)) {
			$out .= $labeltype;
			//}
			$out .= '</td>';

			// Title/Label of event
			$out .= '<td class="tdoverflowmax300"';
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'action') {
				$transcode = $langs->trans("Action".$histo[$key]['acode']);
				//$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : $histo[$key]['alabel']);
				$libelle = $histo[$key]['note'];
				$actionstatic->id = $histo[$key]['id'];
				$out .= ' title="'.dol_escape_htmltag($libelle).'">';
				$out .= dol_trunc($libelle, 120);
			}
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$transcode = $langs->trans("Action".$histo[$key]['acode']);
				$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : 'Send mass mailing');
				$out .= ' title="'.dol_escape_htmltag($libelle).'">';
				$out .= dol_trunc($libelle, 120);
			}
			$out .= '</td>';

			// Date
			$out .= '<td class="center nowraponall">';
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
			$out .= "</td>\n";

			// Linked object
			$out .= '<td class="nowraponall">';
			if (isset($histo[$key]['elementtype']) && !empty($histo[$key]['fk_element'])) {
				if (isset($elementlinkcache[$histo[$key]['elementtype']]) && isset($elementlinkcache[$histo[$key]['elementtype']][$histo[$key]['fk_element']])) {
					$link = $elementlinkcache[$histo[$key]['elementtype']][$histo[$key]['fk_element']];
				} else {
					if (!isset($elementlinkcache[$histo[$key]['elementtype']])) {
						$elementlinkcache[$histo[$key]['elementtype']] = array();
					}
					$link = dolGetElementUrl($histo[$key]['fk_element'], $histo[$key]['elementtype'], 1);
					$elementlinkcache[$histo[$key]['elementtype']][$histo[$key]['fk_element']] = $link;
				}
				$out .= $link;
			}
			$out .= '</td>';

			// Contact(s) for action
			if (isset($histo[$key]['socpeopleassigned']) && is_array($histo[$key]['socpeopleassigned']) && count($histo[$key]['socpeopleassigned']) > 0) {
				$out .= '<td class="valignmiddle">';
				$contact = new Contact($db);
				foreach ($histo[$key]['socpeopleassigned'] as $cid => $cvalue) {
					$result = $contact->fetch($cid);

					if ($result < 0) {
						dol_print_error($db, $contact->error);
					}

					if ($result > 0) {
						$out .= $contact->getNomUrl(-3, '', 10, '', -1, 0, 'paddingright');
						if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
							if (!empty($contact->phone_pro)) {
								$out .= '('.dol_print_phone($contact->phone_pro).')';
							}
						}
						$out .= '<div class="paddingright"></div>';
					}
				}
				$out .= '</td>';
			} elseif (empty($objcon->id) && isset($histo[$key]['contact_id']) && $histo[$key]['contact_id'] > 0) {
				$contactstatic->lastname = $histo[$key]['lastname'];
				$contactstatic->firstname = $histo[$key]['firstname'];
				$contactstatic->id = $histo[$key]['contact_id'];
				$contactstatic->photo = $histo[$key]['contact_photo'];
				$out .= '<td width="120">'.$contactstatic->getNomUrl(-1, '', 10).'</td>';
			} else {
				$out .= '<td>&nbsp;</td>';
			}

			// Status
			$out .= '<td class="nowrap center">'.$actionstatic->LibStatut($histo[$key]['percent'], 2, 0, $histo[$key]['datestart']).'</td>';

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				$out .= '<td></td>';
			}

			$out .= "</tr>\n";
			$i++;
		}
		if (empty($histo)) {
			$colspan = 9;
			$out .= '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
		}

		$out .= "</table>\n";
		$out .= "</div>\n";

		$out .= '</form>';
	}

	if ($noprint) {
		return $out;
	} else {
		print $out;
	}
}