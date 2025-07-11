<?php
/* Copyright (C) 2017-2019  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 * $form
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}
if (!is_object($form)) {
	$form = new Form($db);
}

?>
<!-- BEGIN PHP TEMPLATE commonfields_edit.tpl.php -->
<?php
$now = dol_now();
$year =  dol_print_date($now, '%Y');

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val) {
	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
		continue;	
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
		continue; // We don't want this field
	}

	/*print '<tr class="field_'.$key.'"><td';
	print ' class="titlefieldcreate';
	if (isset($val['notnull']) && $val['notnull'] > 0) {
		print ' fieldrequired';
	}
	if (preg_match('/^(text|html)/', $val['type'])) {
		print ' tdtop';
	}
	print '">';*/


/*affichage des noms des champs de la liste lié au role Emeteur */

	if ($user->rights->actions->action->intervenant && $user->id != 75 && !($object->id <= 643 && $object->id >= 1140) || $user->rights->actions->action->ServiceQ3SE) {
		if ($object->status != $object::STATUS_SOLDEE)  {
			$keys = array( 'avancement', 'com');
			if(in_array($key, $keys)) {
				print '<tr class="field_'.$key.'"><td';
				print ' class="titlefieldcreate';
				if (isset($val['notnull']) && $val['notnull'] > 0) {
					print ' fieldrequired';
				}
				if (preg_match('/^(text|html)/', $val['type'])) {
					print ' tdtop';
				}
				print '">'; 
				unset($tab[array_search($valueraenlever, $tab)]);
				if (!empty($val['help'])) {
					print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
				} else {
					print $langs->trans($val['label']);
				}

				print '</td>';
		
				print '<td class="valuefieldcreate">';
				if (!empty($val['picto'])) {
					//print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
				}
				
				if (in_array($val['type'], array('int', 'integer'))) {
					$value = GETPOSTISSET($key) ?GETPOST($key, 'int') : $object->$key;
				} elseif ($val['type'] == 'double') {
					$value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $object->$key;
				} elseif (preg_match('/^(text|html)/', $val['type'])) {
					$tmparray = explode(':', $val['type']);
					if (!empty($tmparray[1])) {
						$check = $tmparray[1];
					} else {
						$check = 'restricthtml';
					}
					$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
				} elseif ($val['type'] == 'price') {
					$value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($object->$key);
				} elseif ($key == 'lang') {
					$value = GETPOSTISSET($key) ? GETPOST($key, 'aZ09') : $object->lang;
				}	else {
					$value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
				}

			}
				
		}
	}

/*affichage des zone a remplir des champs de la liste lié au role Emeteur */

	if ($user->rights->actions->action->intervenant && $user->id != 75 && !($object->id <= 643 && $object->id >= 1140) || $user->rights->actions->action->ServiceQ3SE) {
		if ($object->status != $object::STATUS_SOLDEE)  {
			if ($key == 'avancement') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif ($key == 'ref') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'com') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}
		}
	}

/*affichage des noms des champs de la liste lié au role Service Q3SE */
	if ($user->rights->actions->action->ServiceQ3SE && $user->id != 75 && !($object->id <= 643 && $object->id >= 1140)) {
		if ($object->status != $object::STATUS_SOLDEE)  {
			$keys = array('ref', 'intervenant','priority','z','solde','origins','label','action_sse','action_rp','action_surete','CP','date_creation','date_creation','action_txt','date_eche','rowid_constat');
				if(in_array($key, $keys)) {
					print '<tr class="field_'.$key.'"><td';
					print ' class="titlefieldcreate';
					if (isset($val['notnull']) && $val['notnull'] > 0) {
						print ' fieldrequired';
					}
					if (preg_match('/^(text|html)/', $val['type'])) {
						print ' tdtop';
					}
					print '">'; 
					unset($tab[array_search($valueraenlever, $tab)]);
					if (!empty($val['help'])) {
						print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
					} else {
						print $langs->trans($val['label']);
					}
				}
				print '</td>';
				if(in_array($key, $keys)) { 
					print '<td class="valuefieldcreate">';
					if (!empty($val['picto'])) {
						//print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
					}
					
					if (in_array($val['type'], array('int', 'integer'))) {
						$value = GETPOSTISSET($key) ?GETPOST($key, 'int') : $object->$key;
					} elseif ($val['type'] == 'double') {
						$value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $object->$key;
					} elseif (preg_match('/^(text|html)/', $val['type'])) {
						$tmparray = explode(':', $val['type']);
						if (!empty($tmparray[1])) {
							$check = $tmparray[1];
						} else {
							$check = 'restricthtml';
						}
						$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
					} elseif ($val['type'] == 'price') {
						$value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($object->$key);
					} elseif ($key == 'lang') {
						$value = GETPOSTISSET($key) ? GETPOST($key, 'aZ09') : $object->lang;
					}	else {
						$value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
					}
				}
		}
	}	
/*affichage des zone a remplir des champs de la liste lié au role Service Q3SE */
	
	if ($user->rights->actions->action->ServiceQ3SE && $user->id != 75 && !($object->id <= 643 && $object->id >= 1140)) {
		if ($object->status != $object::STATUS_SOLDEE)  {
			if ($key == 'ref') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'intervenant') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'priority') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'alert') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'solde') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'origins') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'label') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'action_sse') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'action_rp') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'action_surete') {  
					if (!empty($val['noteditable'])) {
						print $object->showOutputField($val, $key, $value, '', '', '', 0);
					} else {
						if ($key == 'lang') {
							print img_picto('', 'language', 'class="pictofixedwidth"');
							print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
						} else {
							print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
						}
					}	
			}elseif($key == 'CP') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}	
		}elseif($key == 'date_creation') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'date_creation') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}
			elseif($key == 'action_txt') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'date_eche') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}/*elseif($key == 'eff_act') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'date_asse') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}elseif($key == 'assessment') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}*/elseif($key == 'rowid_constat') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}
			}
		}
	}

	if ($user->rights->actions->action->ServiceQ3SE) {
		if ($object->status == $object::STATUS_SOLDEE) {
			if (!($user->id == 75 && $object->id >= 643 && $object->id <= 1140)) {		
				$keys = array('date_sol', 'eff_act','eff_act_description', 'date_asse', 'assessment', 'diffusion','ref');
				if (in_array($key, $keys)) {
					print '<tr class="field_' . $key . '"><td';
					print ' class="titlefieldcreate';
					if (isset($val['notnull']) && $val['notnull'] > 0) {
						print ' fieldrequired';
					}
					if (preg_match('/^(text|html)/', $val['type'])) {
						print ' tdtop';
					}
					print '">';
					
					if (!empty($val['help'])) {
						print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
					} else {
						print $langs->trans($val['label']);
					}
					print '</td>';
					if (in_array($key, $keys)) {
						print '<td class="valuefieldcreate">';
						if (!empty($val['picto'])) {
							//print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
						}
	
						if (in_array($val['type'], array('int', 'integer'))) {
							$value = GETPOSTISSET($key) ? GETPOST($key, 'int') : $object->$key;
						} elseif ($val['type'] == 'double') {
							$value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $object->$key;
						} elseif (preg_match('/^(text|html)/', $val['type'])) {
							$tmparray = explode(':', $val['type']);
							if (!empty($tmparray[1])) {
								$check = $tmparray[1];
							} else {
								$check = 'restricthtml';
							}
							$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
						} elseif ($val['type'] == 'price') {
							$value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($object->$key);
						} elseif ($key == 'lang') {
							$value = GETPOSTISSET($key) ? GETPOST($key, 'aZ09') : $object->lang;
						} else {
							$value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
						}
					}
				}
			}
		}
	}
	
	
	if ($user->rights->actions->action->ServiceQ3SE) {
			if ($object->status == $object::STATUS_SOLDEE)  {
				if (!($user->id == 75 && $object->id >= 643 && $object->id <= 1140)) {	
					if ($key == 'date_sol') {  
						if (!empty($val['noteditable'])) {
							print $object->showOutputField($val, $key, $value, '', '', '', 0);
						} else {
							if ($key == 'lang') {
								print img_picto('', 'language', 'class="pictofixedwidth"');
								print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
							} else {
								print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
							}
						}
					}
					elseif ($key == 'ref') {  
						if (!empty($val['noteditable'])) {
							print $object->showOutputField($val, $key, $value, '', '', '', 0);
						} else {
							if ($key == 'lang') {
								print img_picto('', 'language', 'class="pictofixedwidth"');
								print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
							} else {
								print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
							}
						}
					}elseif($key == 'eff_act') {  
						if (!empty($val['noteditable'])) {
							print $object->showOutputField($val, $key, $value, '', '', '', 0);
						} else {
							if ($key == 'lang') {
								print img_picto('', 'language', 'class="pictofixedwidth"');
								print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
							} else {
								print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
							}
						}
					}elseif($key == 'eff_act_description') {  
						if (!empty($val['noteditable'])) {
							print $object->showOutputField($val, $key, $value, '', '', '', 0);
						} else {
							if ($key == 'lang') {
								print img_picto('', 'language', 'class="pictofixedwidth"');
								print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
							} else {
								print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
							}
						}
					}
					elseif($key == 'date_asse') {  
						if (!empty($val['noteditable'])) {
							print $object->showOutputField($val, $key, $value, '', '', '', 0);
						} else {
							if ($key == 'lang') {
								print img_picto('', 'language', 'class="pictofixedwidth"');
								print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
							} else {
								print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
							}
						}
					}elseif($key == 'assessment') {  
						if (!empty($val['noteditable'])) {
							print $object->showOutputField($val, $key, $value, '', '', '', 0);
						} else {
							if ($key == 'lang') {
								print img_picto('', 'language', 'class="pictofixedwidth"');
								print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
							} else {
								print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
							}
						}
					}elseif($key == 'diffusion') {  
						if (!empty($val['noteditable'])) {
							print $object->showOutputField($val, $key, $value, '', '', '', 0);
						} else {
							if ($key == 'lang') {
								print img_picto('', 'language', 'class="pictofixedwidth"');
								print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
							} else {
								print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
							}
						}
					}
				}
			}
	}
	

	if ($user->id == 75 && $object->id >= 643 && $object->id <= 1140) {
		$keys = array('ref', 'intervenant','priority','alert','solde','origins','label','action_sse','action_rp','action_surete','CP','date_creation','action_txt','date_eche','rowid_constat','avancement','date_sol','diffusion','com','eff_act','eff_act_description','date_asse','assessment');
		if(in_array($key, $keys)) {
			print '<tr class="field_'.$key.'"><td';
			print ' class="titlefieldcreate';
			if (isset($val['notnull']) && $val['notnull'] > 0) {
				print ' fieldrequired';
			}
			if (preg_match('/^(text|html)/', $val['type'])) {
				print ' tdtop';
			}
			print '">'; 
			unset($tab[array_search($valueraenlever, $tab)]);
			if (!empty($val['help'])) {
				print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
			} else {
				print $langs->trans($val['label']);
			}
		}
		print '</td>';
		if(in_array($key, $keys)) { 
			print '<td class="valuefieldcreate">';
			if (!empty($val['picto'])) {
				//print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
			}
			
			if (in_array($val['type'], array('int', 'integer'))) {
				$value = GETPOSTISSET($key) ?GETPOST($key, 'int') : $object->$key;
			} elseif ($val['type'] == 'double') {
				$value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $object->$key;
			} elseif (preg_match('/^(text|html)/', $val['type'])) {
				$tmparray = explode(':', $val['type']);
				if (!empty($tmparray[1])) {
					$check = $tmparray[1];
				} else {
					$check = 'restricthtml';
				}
				$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
			} elseif ($val['type'] == 'price') {
				$value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($object->$key);
			} elseif ($key == 'lang') {
				$value = GETPOSTISSET($key) ? GETPOST($key, 'aZ09') : $object->lang;
			}	else {
				$value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
			}
		}
	}


	if ($user->id == 75 && $object->id >= 643 && $object->id <= 1140) {
		if ($key == 'ref') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'intervenant') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'priority') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'alert') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'solde') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'origins') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'label') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'action_sse') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'action_rp') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'action_surete') {  
				if (!empty($val['noteditable'])) {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					if ($key == 'lang') {
						print img_picto('', 'language', 'class="pictofixedwidth"');
						print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
					} else {
						print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
					}
				}	
			
		}elseif($key == 'CP') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}	
		
		}elseif($key == 'date_creation') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'action_txt') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'date_eche') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'eff_act') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'eff_act_description') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}
		elseif($key == 'date_asse') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'assessment') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'rowid_constat') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'avancement') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'date_sol') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'diffusion') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}elseif($key == 'com') {  
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
				}
			}
		}
	}


/*	if (!empty($val['noteditable'])) {
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	} else {
		if ($key == 'lang') {
			print img_picto('', 'language', 'class="pictofixedwidth"');
			print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
		} else {
			print $object->showInputField($val, $key == 'p_year' ? '' : $key, $value, '', '', '', 0);
		}
	}*/
	
	print '</td>';
	print '</tr>';
	
}

?>
<!-- END PHP TEMPLATE commonfields_edit.tpl.php -->
