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
if ($object->status == $object::STATUS_VALIDATED) {
	$object->fields['typeConstat'] ['notnull'] = 1;
}
if ($object->status == $object::STATUS_EN_COURS) {
	$object->fields['analyseCauseRacine'] ['notnull'] = 1;
}
if ($object->status == $object::STATUS_DRAFT) {
	$object->fields['label'] ['notnull'] = 1;
	$object->fields['site'] ['notnull'] = 1;
	$object->fields['sujet'] ['notnull'] = 1;
}

if ($object->status == $object::STATUS_SOLDEE) {
	$object->fields['dateCloture'] ['notnull'] = 1;
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

	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	//condition si le respAFF n'est pas le chef de projet
	$projet = new Project($db);
	$projet->fetch($object->fk_project);
	$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
	
	$pasresponsableaffaire = 0; 

	if (!in_array($user->id, $liste_chef_projet)) {
		$pasresponsableaffaire = 1; 
	}
	

/*affichage des noms des champs de la liste lié au role Emeteur */

if ($user->rights->constat->constat->Emetteur && $object->fk_user_creat == $user->id  || $user->rights->constat->constat->ResponsableQ3SE) {
		$keys = array( 'dateEmeteur','fk_project','site', 'sujet','descriptionConstat', 'impactNonConfo', 'ref', 'label');
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

/*affichage des zone a remplir des champs de la liste lié au role Emeteur */

if ($user->rights->constat->constat->Emetteur && $object->fk_user_creat == $user->id || $user->rights->constat->constat->ResponsableQ3SE) {
        if ($key == 'dateEmeteur') {  
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
		elseif($key == 'fk_project') {  
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
		}elseif($key == 'sujet') {  
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
		}elseif($key == 'descriptionConstat') {  
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
		elseif($key == 'impactNonConfo') {  
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
		}elseif($key == 'ref') {  
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
		}elseif($key == 'site') {  
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


/*affichage des noms des champs de la liste lié au role Responsable Affaire */

	if (in_array($object->status, [1,3, 4, 5, 7, 9]) && ( ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1) || $user->rights->constat->constat->ResponsableQ3SE)) {
		$keys = array('num_commande','date_eche','impactcomm','processusconcerne', 'radioprotectionInfo', 'radioprotection', 'surete', 'actionimmediate','actionimmediatecom', 'rubrique', 'typeConstat', 'impactNonFactu', 'impactTemps', 'impactAnalyse', 'impactContractuel', 'impactFinnancier','infoClient','commInfoClient','commRespAff');
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

/*affichage des zone a remplir des champs de la liste lié au role Responsable Affaire */
if (in_array($object->status, [1,3, 4, 5, 7, 9]) && ( ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1) ||  $user->rights->constat->constat->ResponsableQ3SE)) {
        if ($key == 'impactFinnancier') {  
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
		elseif($key == 'date_eche') {  
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
		}elseif($key == 'num_commande') {  
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
		elseif($key == 'impactcomm') {  
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
		elseif($key == 'impactContractuel') {  
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
		}elseif($key == 'impactAnalyse') {  
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
		}elseif($key == 'impactTemps') {  
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
		elseif($key == 'impactNonFactu') {  
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
		elseif($key == 'typeConstat') {  
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
		}elseif($key == 'rubrique') {  
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
		}elseif($key == 'typeConstat') {  
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
		}elseif($key == 'surete') {  
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
			
		}elseif($key == 'radioprotection') {  
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
			print '<script>
				document.addEventListener("DOMContentLoaded", function() {
					console.log("DOMContentLoaded event fired");
					var radioprotection = document.getElementById("radioprotection");
					
					var radioprotectionInfo = document.getElementsByClassName("field_radioprotectionInfo")[0];
					
					if (radioprotectionInfo) {
						if (radioprotection && radioprotection.checked) {
							radioprotectionInfo.style.display = "table-row";
						} else {
							radioprotectionInfo.style.display = "none";
						}
					}
					if (radioprotection) {
						radioprotection.onclick = function() {
							console.log("radioprotection onclick");
							if (this.checked) {
								console.log("radioprotection checked");
								if (radioprotectionInfo) {
									radioprotectionInfo.style.display = "table-row";
								}
							} else {
								if (radioprotectionInfo) {
									radioprotectionInfo.style.display = "none";
								}
							}
						}
					}
				});
			</script>';
			
		}elseif($key == 'radioprotectionInfo') {  
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
		}elseif($key == 'processusconcerne') {  
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
		}elseif($key == 'infoClient') {  
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
			print '<script>
			document.addEventListener("DOMContentLoaded", function() {
				console.log("DOMContentLoaded event fired");
				var infoClient = document.getElementById("infoClient");
				
				var infoClientCom = document.getElementsByClassName("field_commInfoClient")[0];
				
				if (infoClientCom) {
					if (infoClient && infoClient.checked) {
						infoClientCom.style.display = "table-row";
					} else {
						infoClientCom.style.display = "none";
					}
				}
				if (infoClient) {
					infoClient.onclick = function() {
						console.log("infoClient onclick");
						if (this.checked) {
							console.log("infoClient checked");
							if (infoClientCom) {
								infoClientCom.style.display = "table-row";
							}
						} else {
							if (infoClientCom) {
								infoClientCom.style.display = "none";
							}
						}
					}
				}
			});
			</script>';
		}elseif($key == 'commInfoClient') {  
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
		elseif($key == 'commRespAff') {  
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
		elseif ($key == 'actionimmediate') {  
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
			print '<script>
				document.addEventListener("DOMContentLoaded", function() {
					var actionImmediate = document.getElementById("actionimmediate");
					var actionImmediateCom = document.getElementsByClassName("field_actionimmediatecom")[0];
					if (actionImmediateCom) {
						if (actionImmediate && actionImmediate.checked) {
							actionImmediateCom.style.display = "table-row";
						} else {
							actionImmediateCom.style.display = "none";
						}
					}
					if (actionImmediate) {
						actionImmediate.onclick = function() {
							if (this.checked) {
								if (actionImmediateCom) {
									actionImmediateCom.style.display = "table-row";
								}
							} else {
								if (actionImmediateCom) {
									actionImmediateCom.style.display = "none";
								}
							}
						}
					}
				});
			</script>';
		} elseif($key == 'actionimmediatecom') {  
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


/*affichage des noms des champs de la liste lié au role Service Q3SE et ResponsableQ3SE*/

if (in_array($object->status, [4, 5, 7, 9]) && ($user->rights->constat->constat->ServiceQ3SE  || $user->rights->constat->constat->ResponsableQ3SE)) {
		$keys = array('analyseCauseRacine','recurent','dateCloture','coutTotal');
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

	/*affichage des zone a remplir des champs de la liste lié au role Service Q3SE et le role responsableQ3SE */

	if (in_array($object->status, [4, 5, 7, 9]) && ($user->rights->constat->constat->ServiceQ3SE  || $user->rights->constat->constat->ResponsableQ3SE)) {
        if ($key == 'analyseCauseRacine') {  
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
		elseif($key == 'recurent') {  
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
		}elseif($key == 'dateCloture') {  
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
		}elseif($key == 'coutTotal') {  
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

/*affichage des noms des champs de la liste lié au role Responsable Q3SE */

if (in_array($object->status, [4, 5, 7, 9]) && ($user->rights->constat->constat->ServiceQ3SE  || $user->rights->constat->constat->ResponsableQ3SE)) {
		$keys = array( 'accordClient','commAccordClient','controleClient','commControleClient','actionSold','commRespQ3');
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

/*affichage des zone a remplir des champs de la liste lié au role Responsable Q3SE */

if (in_array($object->status, [4, 5, 7, 9]) && ($user->rights->constat->constat->ServiceQ3SE  || $user->rights->constat->constat->ResponsableQ3SE)) {
		if($key == 'accordClient') {  
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
		
		elseif($key == 'commAccordClient') {  
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
			print '<script>
			document.addEventListener("DOMContentLoaded", function() {
				console.log("DOMContentLoaded event fired");
				var accordClient = document.getElementById("accordClient");
				
				var accordClientCom = document.getElementsByClassName("field_commAccordClient")[0];
				console.log(accordClientCom);
				if (accordClientCom) {
					if (accordClient && accordClient.checked) {
						accordClientCom.style.display = "table-row";
					} else {
						accordClientCom.style.display = "none";
					}
				}
				if (accordClient) {
					accordClient.onclick = function() {
						console.log("accordClient onclick");
						if (this.checked) {
							console.log("accordClient checked");
							if (accordClientCom) {
								accordClientCom.style.display = "table-row";
							}
						} else {
							if (accordClientCom) {
								accordClientCom.style.display = "none";
							}
						}
					}
				}
			});
			</script>';
		}elseif($key == 'controleClient') {  
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
			print '<script>
			document.addEventListener("DOMContentLoaded", function() {
				console.log("DOMContentLoaded event fired");
				var controleClient = document.getElementById("controleClient");
				
				var controleClientCom = document.getElementsByClassName("field_commControleClient")[0];
				console.log(controleClientCom);
				if (controleClientCom) {
					if (controleClient && controleClient.checked) {
						controleClientCom.style.display = "table-row";
					} else {
						controleClientCom.style.display = "none";
					}
				}
				if (controleClient) {
					controleClient.onclick = function() {
						console.log("controleClient onclick");
						if (this.checked) {
							console.log("controleClient checked");
							if (controleClientCom) {
								controleClientCom.style.display = "table-row";
							}
						} else {
							if (controleClientCom) {
								controleClientCom.style.display = "none";
							}
						}
					}
				}
			});
			</script>';
		}elseif($key == 'commControleClient') {  
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
		}elseif($key == 'actionSold') {  
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
		}elseif($key == 'commRespQ3') {  
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


if ($object->status == $object::STATUS_VALIDATED) {
	$object->fields['impactcomm'] ['notnull'] = 1;
	//var_dump($object->fields['impactcomm']);
}
//var_dump($object->fields['impactcomm']);

	
/*
	if (!empty($val['noteditable'])) {
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
	


?>
<!-- END PHP TEMPLATE commonfields_edit.tpl.php -->
