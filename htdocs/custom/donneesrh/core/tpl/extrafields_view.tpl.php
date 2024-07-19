<?php
/* Copyright (C) 2014	Maxime Kohlhaas		<support@atm-consulting.fr>
 * Copyright (C) 2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2021	Frédéric France		<frederic.france@netlogic.fr>
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
 * Show extrafields. It also show fields from hook formObjectOptions. Need to have following variables defined:
 * $userField (invoice, order, ...)
 * $action
 * $conf
 * $langs
 *
 * $parameters
 * $cols
 */

// Protection to avoid direct call of template
if (empty($userField) || !is_object($userField)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (!is_object($form)) {
	$form = new Form($db);
}


?>
<!-- BEGIN PHP TEMPLATE extrafields_view.tpl.php -->
<?php
if (!isset($parameters) || !is_array($parameters)) {
	$parameters = array();
}
if (!empty($cols)) {
	$parameters['colspan'] = ' colspan="'.$cols.'"';
}
if (!empty($cols)) {
	$parameters['cols'] = $cols;
}
if (!empty($userField->fk_soc)) {
	$parameters['socid'] = $userField->fk_soc;
}
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $userField, $action);
print $hookmanager->resPrint;
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


//var_dump($extrafields->attributes[$table_element]);
if (empty($reshook) && isset($extrafields->attributes[$table_element]['label']) && is_array($extrafields->attributes[$table_element]['label'])) {
	$lastseparatorkeyfound = '';
	$extrafields_collapse_num = '';
	$extrafields_collapse_num_old = '';
	$i = 0;

	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	foreach ($extrafields->attributes[$table_element]['label'] as $tmpkeyextra => $tmplabelextra) {
		if (!empty($keyforbreak) && $tmpkeyextra == $keyforbreak) {
			break; // key used for break on second column
		}
		
		$i++;

		// Discard if extrafield is a hidden field on form
		$enabled = 1;
		if ($enabled && isset($extrafields->attributes[$table_element]['enabled'][$tmpkeyextra])) {
			$enabled = dol_eval($extrafields->attributes[$table_element]['enabled'][$tmpkeyextra], 1, 1, '2');
		}
		if ($enabled && isset($extrafields->attributes[$table_element]['list'][$tmpkeyextra])) {
			$enabled = dol_eval($extrafields->attributes[$table_element]['list'][$tmpkeyextra], 1, 1, '2');
		}

		$perms = 1;
		if ($perms && isset($extrafields->attributes[$table_element]['perms'][$tmpkeyextra])) {
			$perms = dol_eval($extrafields->attributes[$table_element]['perms'][$tmpkeyextra], 1, 1, '2');
		}
		//print $tmpkeyextra.'-'.$enabled.'-'.$perms.'<br>'."\n";

		if (empty($enabled)) {
			continue; // 0 = Never visible field
		}
		if (abs($enabled) != 1 && abs($enabled) != 3 && abs($enabled) != 5 && abs($enabled) != 4) {
			continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list <> 4 = not visible at the creation
		}
		if (empty($perms)) {
			continue; // 0 = Not visible
		}

		// Load language if required
		if (!empty($extrafields->attributes[$table_element]['langfile'][$tmpkeyextra])) {
			$langs->load($extrafields->attributes[$table_element]['langfile'][$tmpkeyextra]);
		}
		if ($action == 'edit_extras') {
			$value = (GETPOSTISSET("options_".$tmpkeyextra) ? GETPOST("options_".$tmpkeyextra) : (isset($userField->array_options["options_".$tmpkeyextra]) ? $userField->array_options["options_".$tmpkeyextra] : ''));
		} else {
			$value = (isset($userField->array_options["options_".$tmpkeyextra]) ? $userField->array_options["options_".$tmpkeyextra] : '');
			//var_dump($tmpkeyextra.' - '.$value);
		}

		// Print line tr of extra field
		if ($extrafields->attributes[$table_element]['type'][$tmpkeyextra] == 'separate') {
			$extrafields_collapse_num = $tmpkeyextra;

			print $extrafields->showSeparator($tmpkeyextra, $userField);

			$lastseparatorkeyfound = $tmpkeyextra;
		} else {
			$collapse_group = $extrafields_collapse_num.(!empty($userField->id) ? '_'.$userField->id : '');
			print '<tr class="trextrafields_collapse'.$collapse_group;
			/*if ($extrafields_collapse_num && $extrafields_collapse_num_old && $extrafields_collapse_num != $extrafields_collapse_num_old) {
				print ' trextrafields_collapse_new';
			}*/
			if ($extrafields_collapse_num && $i == count($extrafields->attributes[$table_element]['label'])) {
				print ' trextrafields_collapse_last';
			}
			print '"';
			if (isset($extrafields->expand_display) && empty($extrafields->expand_display[$collapse_group])) {
				print ' style="display: none;"';
			}
			print '>';
			$extrafields_collapse_num_old = $extrafields_collapse_num;
			print '<td class="titlefield">';
			print '<table class="nobordernopadding centpercent">';
			print '<tr>';

			print '<td class="';
			if ((!empty($action) && ($action == 'create' || $action == 'edit')) && !empty($extrafields->attributes[$table_element]['required'][$tmpkeyextra])) {
				print ' fieldrequired';
			}
			print '">';
			if (!empty($extrafields->attributes[$table_element]['help'][$tmpkeyextra])) {
				// You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
				$tmptooltip = explode(':', $extrafields->attributes[$table_element]['help'][$tmpkeyextra]);
				print $form->textwithpicto($langs->trans($tmplabelextra), $langs->trans($tmptooltip[0]), 1, 'help', '', 0, 3, (empty($tmptooltip[1]) ? '' : 'extra_'.$tmpkeyextra.'_'.$tmptooltip[1]));
			} else {
				print $langs->trans($tmplabelextra);
			}
			print '</td>';

			//TODO Improve element and rights detection
			//var_dump($user->rights);
			$permok = false;
			$keyforperm = $userField->element;

			if ($userField->element == 'fichinter') {
				$keyforperm = 'ficheinter';
			}
			if (isset($user->rights->$keyforperm)) {
				$permok = !empty($user->rights->$keyforperm->creer) || !empty($user->rights->$keyforperm->create) || !empty($user->rights->$keyforperm->write);
			}
			if ($userField->element == 'order_supplier') {
				if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
					$permok = $user->rights->fournisseur->commande->creer;
				} else {
					$permok = $user->rights->supplier_order->creer;
				}
			}
			if ($userField->element == 'invoice_supplier') {
				if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
					$permok = $user->rights->fournisseur->facture->creer;
				} else {
					$permok = $user->rights->supplier_invoice->creer;
				}
			}
			if ($userField->element == 'shipping') {
				$permok = $user->rights->expedition->creer;
			}
			if ($userField->element == 'delivery') {
				$permok = $user->rights->expedition->delivery->creer;
			}
			if ($userField->element == 'productlot') {
				$permok = $user->rights->stock->creer;
			}
			if ($userField->element == 'facturerec') {
				$permok = $user->rights->facture->creer;
			}
			if ($userField->element == 'mo') {
				$permok = $user->rights->mrp->write;
			}
			if ($userField->element == 'contact') {
				$permok = $user->rights->societe->contact->creer;
			}
			if ($userField->element == 'salary') {
				$permok = $user->rights->salaries->read;
			}

			$isdraft = ((isset($userField->statut) && $userField->statut == 0) || (isset($userField->status) && $userField->status == 0));
			if (($isdraft || !empty($extrafields->attributes[$table_element]['alwayseditable'][$tmpkeyextra]))
				&& $permok && $enabled != 5 && ($action != 'edit_extras' || GETPOST('attribute') != $tmpkeyextra)
				&& empty($extrafields->attributes[$table_element]['computed'][$tmpkeyextra])) {
				$fieldid = empty($forcefieldid) ? 'id' : $forcefieldid;
				$valueid = empty($forceobjectid) ? $userField->id : $forceobjectid;
				if ($table_element == 'societe') {
					$fieldid = 'socid';
				}

				print '<td class="right"><a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?'.$fieldid.'='.$valueid.'&action=edit_extras&token='.newToken().'&attribute='.$tmpkeyextra.'&ignorecollapsesetup=1">'.img_edit().'</a></td>';
			}
			print '</tr></table>';
			print '</td>';

			$html_id = !empty($userField->id) ? $userField->element.'_extras_'.$tmpkeyextra.'_'.$userField->id : '';

			print '<td id="'.$html_id.'" class="valuefield '.$userField->element.'_extras_'.$tmpkeyextra.' wordbreak '.$extrafields->attributes[$table_element]['cssview'][$tmplabelextra].'"'.(!empty($cols) ? ' colspan="'.$cols.'"' : '').'>';

			// Convert date into timestamp format
			if (in_array($extrafields->attributes[$table_element]['type'][$tmpkeyextra], array('date'))) {
				$datenotinstring = empty($userField->array_options['options_'.$tmpkeyextra]) ? '' : $userField->array_options['options_'.$tmpkeyextra];
				// print 'X'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!empty($userField->array_options['options_'.$tmpkeyextra]) && !is_numeric($userField->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(12, 0, 0, GETPOST("options_".$tmpkeyextra."month", 'int'), GETPOST("options_".$tmpkeyextra."day", 'int'), GETPOST("options_".$tmpkeyextra."year", 'int')) : $datenotinstring;
			}
			if (in_array($extrafields->attributes[$table_element]['type'][$tmpkeyextra], array('datetime'))) {
				$datenotinstring = empty($userField->array_options['options_'.$tmpkeyextra]) ? '' : $userField->array_options['options_'.$tmpkeyextra];
				// print 'X'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!empty($userField->array_options['options_'.$tmpkeyextra]) && !is_numeric($userField->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(GETPOST("options_".$tmpkeyextra."hour", 'int'), GETPOST("options_".$tmpkeyextra."min", 'int'), GETPOST("options_".$tmpkeyextra."sec", 'int'), GETPOST("options_".$tmpkeyextra."month", 'int'), GETPOST("options_".$tmpkeyextra."day", 'int'), GETPOST("options_".$tmpkeyextra."year", 'int'), 'tzuserrel') : $datenotinstring;
			}

			//TODO Improve element and rights detection
			if ($action == 'edit_extras' && $permok && GETPOST('attribute', 'restricthtml') == $tmpkeyextra) {
				$fieldid = 'id';
				if ($table_element == 'societe') {
					$fieldid = 'socid';
				}
				print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"] . '?' . $fieldid . '=' . $userField->id . '" method="post" name="formextra">';
				print '<input type="hidden" name="action" value="update_extras">';
				print '<input type="hidden" name="attribute" value="'.$tmpkeyextra.'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="'.$fieldid.'" value="'.$userField->id.'">';
				print $extrafields->showInputField($tmpkeyextra, $value, '', '', '', 0, $userField->id, $table_element);

				print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';

				print '</form>';
			} else {
				//var_dump($tmpkeyextra.'-'.$value.'-'.$table_element);
				print $extrafields->showOutputField($tmpkeyextra, $value, '', $table_element);
			}

			print '</td>';
			print '</tr>'."\n";
		}
	}

	print '</table>';
	print '</div>';


	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	$alreadyoutput = 1;
	foreach ($extrafields->attributes[$table_element]['label'] as $tmpkeyextra => $tmplabelextra) {
		if ($alreadyoutput) {
			if (!empty($keyforbreak) && $tmpkeyextra == $keyforbreak) {
				$alreadyoutput = 0; // key used for break on second column
			} else {
				continue;
			}
		}

		$i++;

		// Discard if extrafield is a hidden field on form
		$enabled = 1;
		if ($enabled && isset($extrafields->attributes[$table_element]['enabled'][$tmpkeyextra])) {
			$enabled = dol_eval($extrafields->attributes[$table_element]['enabled'][$tmpkeyextra], 1, 1, '2');
		}
		if ($enabled && isset($extrafields->attributes[$table_element]['list'][$tmpkeyextra])) {
			$enabled = dol_eval($extrafields->attributes[$table_element]['list'][$tmpkeyextra], 1, 1, '2');
		}

		$perms = 1;
		if ($perms && isset($extrafields->attributes[$table_element]['perms'][$tmpkeyextra])) {
			$perms = dol_eval($extrafields->attributes[$table_element]['perms'][$tmpkeyextra], 1, 1, '2');
		}
		//print $tmpkeyextra.'-'.$enabled.'-'.$perms.'<br>'."\n";

		if (empty($enabled)) {
			continue; // 0 = Never visible field
		}
		if (abs($enabled) != 1 && abs($enabled) != 3 && abs($enabled) != 5 && abs($enabled) != 4) {
			continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list <> 4 = not visible at the creation
		}
		if (empty($perms)) {
			continue; // 0 = Not visible
		}

		// Load language if required
		if (!empty($extrafields->attributes[$table_element]['langfile'][$tmpkeyextra])) {
			$langs->load($extrafields->attributes[$table_element]['langfile'][$tmpkeyextra]);
		}
		if ($action == 'edit_extras') {
			$value = (GETPOSTISSET("options_".$tmpkeyextra) ? GETPOST("options_".$tmpkeyextra) : (isset($userField->array_options["options_".$tmpkeyextra]) ? $userField->array_options["options_".$tmpkeyextra] : ''));
		} else {
			$value = (isset($userField->array_options["options_".$tmpkeyextra]) ? $userField->array_options["options_".$tmpkeyextra] : '');
			//var_dump($tmpkeyextra.' - '.$value);
		}

		// Print line tr of extra field
		if ($extrafields->attributes[$table_element]['type'][$tmpkeyextra] == 'separate') {
			$extrafields_collapse_num = $tmpkeyextra;

			print $extrafields->showSeparator($tmpkeyextra, $userField);

			$lastseparatorkeyfound = $tmpkeyextra;
		} else {
			$collapse_group = $extrafields_collapse_num.(!empty($userField->id) ? '_'.$userField->id : '');
			print '<tr class="trextrafields_collapse'.$collapse_group;
			/*if ($extrafields_collapse_num && $extrafields_collapse_num_old && $extrafields_collapse_num != $extrafields_collapse_num_old) {
				print ' trextrafields_collapse_new';
			}*/
			if ($extrafields_collapse_num && $i == count($extrafields->attributes[$table_element]['label'])) {
				print ' trextrafields_collapse_last';
			}
			print '"';
			if (isset($extrafields->expand_display) && empty($extrafields->expand_display[$collapse_group])) {
				print ' style="display: none;"';
			}
			print '>';
			$extrafields_collapse_num_old = $extrafields_collapse_num;
			print '<td class="titlefield">';
			print '<table class="nobordernopadding centpercent">';
			print '<tr>';

			print '<td class="';
			if ((!empty($action) && ($action == 'create' || $action == 'edit')) && !empty($extrafields->attributes[$table_element]['required'][$tmpkeyextra])) {
				print ' fieldrequired';
			}
			print '">';
			if (!empty($extrafields->attributes[$table_element]['help'][$tmpkeyextra])) {
				// You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
				$tmptooltip = explode(':', $extrafields->attributes[$table_element]['help'][$tmpkeyextra]);
				print $form->textwithpicto($langs->trans($tmplabelextra), $langs->trans($tmptooltip[0]), 1, 'help', '', 0, 3, (empty($tmptooltip[1]) ? '' : 'extra_'.$tmpkeyextra.'_'.$tmptooltip[1]));
			} else {
				print $langs->trans($tmplabelextra);
			}
			print '</td>';

			//TODO Improve element and rights detection
			//var_dump($user->rights);
			$permok = false;
			$keyforperm = $userField->element;

			if ($userField->element == 'fichinter') {
				$keyforperm = 'ficheinter';
			}
			if (isset($user->rights->$keyforperm)) {
				$permok = !empty($user->rights->$keyforperm->creer) || !empty($user->rights->$keyforperm->create) || !empty($user->rights->$keyforperm->write);
			}
			if ($userField->element == 'order_supplier') {
				if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
					$permok = $user->rights->fournisseur->commande->creer;
				} else {
					$permok = $user->rights->supplier_order->creer;
				}
			}
			if ($userField->element == 'invoice_supplier') {
				if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
					$permok = $user->rights->fournisseur->facture->creer;
				} else {
					$permok = $user->rights->supplier_invoice->creer;
				}
			}
			if ($userField->element == 'shipping') {
				$permok = $user->rights->expedition->creer;
			}
			if ($userField->element == 'delivery') {
				$permok = $user->rights->expedition->delivery->creer;
			}
			if ($userField->element == 'productlot') {
				$permok = $user->rights->stock->creer;
			}
			if ($userField->element == 'facturerec') {
				$permok = $user->rights->facture->creer;
			}
			if ($userField->element == 'mo') {
				$permok = $user->rights->mrp->write;
			}
			if ($userField->element == 'contact') {
				$permok = $user->rights->societe->contact->creer;
			}
			if ($userField->element == 'salary') {
				$permok = $user->rights->salaries->read;
			}

			$isdraft = ((isset($userField->statut) && $userField->statut == 0) || (isset($userField->status) && $userField->status == 0));
			if (($isdraft || !empty($extrafields->attributes[$table_element]['alwayseditable'][$tmpkeyextra]))
				&& $permok && $enabled != 5 && ($action != 'edit_extras' || GETPOST('attribute') != $tmpkeyextra)
				&& empty($extrafields->attributes[$table_element]['computed'][$tmpkeyextra])) {
				$fieldid = empty($forcefieldid) ? 'id' : $forcefieldid;
				$valueid = empty($forceobjectid) ? $userField->id : $forceobjectid;
				if ($table_element == 'societe') {
					$fieldid = 'socid';
				}

				print '<td class="right"><a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?'.$fieldid.'='.$valueid.'&action=edit_extras&token='.newToken().'&attribute='.$tmpkeyextra.'&ignorecollapsesetup=1">'.img_edit().'</a></td>';
			}
			print '</tr></table>';
			print '</td>';

			$html_id = !empty($userField->id) ? $userField->element.'_extras_'.$tmpkeyextra.'_'.$userField->id : '';

			print '<td id="'.$html_id.'" class="valuefield '.$userField->element.'_extras_'.$tmpkeyextra.' wordbreak"'.(!empty($cols) ? ' colspan="'.$cols.'"' : '').'>';

			// Convert date into timestamp format
			if (in_array($extrafields->attributes[$table_element]['type'][$tmpkeyextra], array('date'))) {
				$datenotinstring = empty($userField->array_options['options_'.$tmpkeyextra]) ? '' : $userField->array_options['options_'.$tmpkeyextra];
				// print 'X'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!empty($userField->array_options['options_'.$tmpkeyextra]) && !is_numeric($userField->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(12, 0, 0, GETPOST("options_".$tmpkeyextra."month", 'int'), GETPOST("options_".$tmpkeyextra."day", 'int'), GETPOST("options_".$tmpkeyextra."year", 'int')) : $datenotinstring;
			}
			if (in_array($extrafields->attributes[$table_element]['type'][$tmpkeyextra], array('datetime'))) {
				$datenotinstring = empty($userField->array_options['options_'.$tmpkeyextra]) ? '' : $userField->array_options['options_'.$tmpkeyextra];
				// print 'X'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!empty($userField->array_options['options_'.$tmpkeyextra]) && !is_numeric($userField->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$userField->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(GETPOST("options_".$tmpkeyextra."hour", 'int'), GETPOST("options_".$tmpkeyextra."min", 'int'), GETPOST("options_".$tmpkeyextra."sec", 'int'), GETPOST("options_".$tmpkeyextra."month", 'int'), GETPOST("options_".$tmpkeyextra."day", 'int'), GETPOST("options_".$tmpkeyextra."year", 'int'), 'tzuserrel') : $datenotinstring;
			}

			//TODO Improve element and rights detection
			if ($action == 'edit_extras' && $permok && GETPOST('attribute', 'restricthtml') == $tmpkeyextra) {
				$fieldid = 'id';
				if ($table_element == 'societe') {
					$fieldid = 'socid';
				}
				print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"] . '?' . $fieldid . '=' . $userField->id . '" method="post" name="formextra">';
				print '<input type="hidden" name="action" value="update_extras">';
				print '<input type="hidden" name="attribute" value="'.$tmpkeyextra.'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="'.$fieldid.'" value="'.$userField->id.'">';
				print $extrafields->showInputField($tmpkeyextra, $value, '', '', '', 0, $userField->id, $table_element);

				print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';

				print '</form>';
			} else {
				
				//var_dump($tmpkeyextra.'-'.$value.'-'.$table_element);
				print $extrafields->showOutputField($tmpkeyextra, $value, '', $table_element);
			}

			print '</td>';
			print '</tr>'."\n";
		}
	}

	print '</table>';
	print '</div>';

	// Add code to manage list depending on others
	// TODO Test/enhance this with a more generic solution
	if (!empty($conf->use_javascript_ajax)) {
		print "\n";
		print '
				<script>
				    jQuery(document).ready(function() {
				    	function showOptions(child_list, parent_list)
				    	{
				    		var val = $("select[name="+parent_list+"]").val();
				    		var parentVal = parent_list + ":" + val;
							if(val > 0) {
					    		$("select[name=\""+child_list+"\"] option[parent]").hide();
					    		$("select[name=\""+child_list+"\"] option[parent=\""+parentVal+"\"]").show();
							} else {
								$("select[name=\""+child_list+"\"] option").show();
							}
				    	}
						function setListDependencies() {
					    	jQuery("select option[parent]").parent().each(function() {
					    		var child_list = $(this).attr("name");
								var parent = $(this).find("option[parent]:first").attr("parent");
								var infos = parent.split(":");
								var parent_list = infos[0];
								showOptions(child_list, parent_list);

								/* Activate the handler to call showOptions on each future change */
								$("select[name=\""+parent_list+"\"]").change(function() {
									showOptions(child_list, parent_list);
								});
					    	});
						}
						setListDependencies();
				    });
				</script>'."\n";
	}
}
else {
	print info_admin($langs->trans("WarningNoProperty"));
	$noproperty = 1;
}

?>
<!-- END PHP TEMPLATE extrafields_view.tpl.php -->
