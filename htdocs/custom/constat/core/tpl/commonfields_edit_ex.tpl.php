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
if ($object->status == $object::STATUS_PRISE ||$object->status == $object::STATUS_VALIDATED) {
	$object->fields['type_constat'] ['notnull'] = 1;
}
if ($object->status == $object::STATUS_EN_COURS) {
	$object->fields['analyse_cause_racine'] ['notnull'] = 1;
}
if ($object->status == $object::STATUS_DRAFT) {
	$object->fields['label'] ['notnull'] = 1;
	$object->fields['site'] ['notnull'] = 1;
	$object->fields['sujet'] ['notnull'] = 1;
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

if ($user->rights->constat->constat->Emetteur && $object->fk_user_creat == $user->id || $user->rights->constat->constat->ServiceQ3SE || $user->rights->constat->constat->ResponsableQ3SE) {
		$keys = array( 'emetteur_date','date_eche', 'fk_project','site', 'sujet','description_constat', 'commentaire_emetteur', 'impactNonConfo', 'ref', 'label');
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

if ($user->rights->constat->constat->Emetteur && $object->fk_user_creat == $user->id || $user->rights->constat->constat->ServiceQ3SE || $user->rights->constat->constat->ResponsableQ3SE) {
        if ($key == 'emetteur_date') {  
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
		}elseif($key == 'description_constat') {  
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
		elseif($key == 'commentaire_emetteur') {  
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
		}elseif($key == 'impactNonConfo') {  
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

	if (in_array($object->status, [1,3, 4, 5, 7, 9]) && ( ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1) || $user->rights->constat->constat->ServiceQ3SE || $user->rights->constat->constat->ResponsableQ3SE)) {
		$keys = array('num_commande','description_impact','processusconcerne', 'radioprotectionInfo', 'radioprotection', 'surete', 'actionsimmediates','actionsimmediates_commentaire', 'rubrique', 'type_constat', 'impactNonFactu', 'impactTemps', 'impactAnalyse', 'impactContractuel', 'impactFinnancier','infoclient','infoclient_commentaire','commentaire_resp_aff');
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

if (in_array($object->status, [1,3, 4, 5, 7, 9]) && ( ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1) || $user->rights->constat->constat->ServiceQ3SE || $user->rights->constat->constat->ResponsableQ3SE)) {
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
		elseif($key == 'description_impact') {  
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
		elseif($key == 'type_constat') {  
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
		}elseif($key == 'type_constat') {  
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
		}elseif($key == 'infoclient') {  
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
				var infoclient = document.getElementById("infoclient");
				
				var infoClientCom = document.getElementsByClassName("field_infoclient_commentaire")[0];
				
				if (infoClientCom) {
					if (infoclient && infoclient.checked) {
						infoClientCom.style.display = "table-row";
					} else {
						infoClientCom.style.display = "none";
					}
				}
				if (infoclient) {
					infoclient.onclick = function() {
						console.log("infoclient onclick");
						if (this.checked) {
							console.log("infoclient checked");
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
		}elseif($key == 'infoclient_commentaire') {  
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
		elseif($key == 'commentaire_resp_aff') {  
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
		elseif ($key == 'actionsimmediates') {  
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
					var actionsimmediates = document.getElementById("actionsimmediates");
					var actionsimmediates_commentaire = document.getElementsByClassName("field_actionsimmediates_commentaire")[0];
					if (actionsimmediates_commentaire) {
						if (actionsimmediates && actionsimmediates.checked) {
							actionsimmediates_commentaire.style.display = "table-row";
						} else {
							actionsimmediates_commentaire.style.display = "none";
						}
					}
					if (actionsimmediates) {
						actionsimmediates.onclick = function() {
							if (this.checked) {
								if (actionsimmediates_commentaire) {
									actionsimmediates_commentaire.style.display = "table-row";
								}
							} else {
								if (actionsimmediates_commentaire) {
									actionsimmediates_commentaire.style.display = "none";
								}
							}
						}
					}
				});
			</script>';
		} elseif($key == 'actionsimmediates_commentaire') {  
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
/*affichage des noms des champs de la liste lié au role Service Q3SE */

	if (in_array($object->status, [4, 5, 7, 9]) && ($user->rights->constat->constat->ServiceQ3SE  || $user->rights->constat->constat->ResponsableQ3SE)) {
		$keys = array('commentaire_serv_q3se');
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
/*affichage des zone a remplir des champs de la liste lié au role Service Q3SE */
	
if (in_array($object->status, [4, 5, 7, 9]) && ($user->rights->constat->constat->ServiceQ3SE  || $user->rights->constat->constat->ResponsableQ3SE)) {
        if($key == 'commentaire_serv_q3se') {  
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
		$keys = array('analyse_cause_racine','recurent','cloture_date','cout_total');
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
        if ($key == 'analyse_cause_racine') {  
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
		}elseif($key == 'cloture_date') {  
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
		}elseif($key == 'cout_total') {  
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
		$keys = array( 'accordclient','accordclient_commentaire','controleclient','controleclient_commentaire','actionSold','commentaire_resp_q3se');
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
		if($key == 'accordclient') {  
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
		
		elseif($key == 'accordclient_commentaire') {  
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
				var accordclient = document.getElementById("accordclient");
				
				var accordClientCom = document.getElementsByClassName("field_accordclient_commentaire")[0];
				console.log(accordClientCom);
				if (accordClientCom) {
					if (accordclient && accordclient.checked) {
						accordClientCom.style.display = "table-row";
					} else {
						accordClientCom.style.display = "none";
					}
				}
				if (accordclient) {
					accordclient.onclick = function() {
						console.log("accordclient onclick");
						if (this.checked) {
							console.log("accordclient checked");
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
		}elseif($key == 'controleclient') {  
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
				var controleclient = document.getElementById("controleclient");
				
				var controleClientCom = document.getElementsByClassName("field_controleclient_commentaire")[0];
				console.log(controleClientCom);
				if (controleClientCom) {
					if (controleclient && controleclient.checked) {
						controleClientCom.style.display = "table-row";
					} else {
						controleClientCom.style.display = "none";
					}
				}
				if (controleclient) {
					controleclient.onclick = function() {
						console.log("controleclient onclick");
						if (this.checked) {
							console.log("controleclient checked");
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
		}elseif($key == 'controleclient_commentaire') {  
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
		}elseif($key == 'commentaire_resp_q3se') {  
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
	$object->fields['description_impact'] ['notnull'] = 1;
	//var_dump($object->fields['description_impact']);
}
//var_dump($object->fields['description_impact']);

	
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
