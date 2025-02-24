<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $keyforbreak may be defined to key to switch on second column
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
<!-- BEGIN PHP TEMPLATE commonfields_view.tpl.php -->
<?php

$object->fields = dol_sort_array($object->fields, 'position');

if($action == 'editvalidator1' || $action == 'editvalidator2') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	if($action == 'editvalidator1') {
		print '<input type="hidden" name="action" value="savevalidator1">';
	}
	elseif($action == 'editvalidator2') {
		print '<input type="hidden" name="action" value="savevalidator2">';
	}
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
}

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';
if($displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
	print '<div class="fichehalfleft">';
}
print '<table class="border centpercent tableforfield">'."\n";

foreach ($object->fields as $key => $val) {
	if (!empty($keyforbreak1) && $key == $keyforbreak1 && $displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
		break; // key used for break on second column
	}

	if (!empty($keyforbreak) && $key == $keyforbreak) {
		break; // key used for break on second column
	}

	// Discard if extrafield is a hidden field on form
	$visible = dol_eval($val['visible'], 1, 1, '2');
	if ($visible != 1 && $visible != 3 && $visible != 4 && $visible != 5) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
		continue; // We don't want this field
	}
	if (in_array($key, array('ref', 'status'))) {
		continue; // Ref and status are already in dol_banner
	}

	$value = $object->$key;

	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key;
	//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		print ' tdtop';
	}
	print '">';
	if (!empty($val['help'])) {
		print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		print $langs->trans($val['label']);
	}
	print '</td>';
	print '<td class="valuefield fieldname_'.$key;
	if ($val['type'] == 'text') {
		print ' wordbreak';
	}
	if (!empty($val['cssview'])) {
		print ' '.$val['cssview'];
	}
	print '">';
	if (in_array($val['type'], array('text', 'html'))) {
		print '<div class="longmessagecut">';
	}
	if($object->status != $object::STATUS_VERIFICATION || ($key != 'prime_astreinte' && $key != 'prime_exceptionnelle' && $key != 'prime_objectif' && $key != 'prime_variable' && $key != 'prime_amplitude')) {
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	else {
		print $object->showInputField($val, $key, $value, 'form="feuilleDeTempsForm"', '', '', 0, 1);
	}
	//print dol_escape_htmltag($object->$key, 1, 1);
	if (in_array($val['type'], array('text', 'html'))) {
		print '</div>';
	}
	print '</td>';
	print '</tr>';
}

print '</table>';

// We close div and reopen for second column
if($displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
	print '</div>';
	print '<div class="fichehalfright">';

	print '<table class="border centpercent tableforfield">';

	if(!$conf->global->FDT_USER_APPROVER) {
		// 1er Approbateurs 
		print '<tr>';
		print '<td class="titlefield fieldname_user_validation">1ère Validation</td>';
		print '<td class="valuefield fieldname_fk_user_validation_1">';
		$list_validation1 = $object->listApprover1;
		foreach($list_validation1[2] as $id => $user_static){
			print $user_static->getNomUrl(1).($list_validation1[1][$id] == 1 ? ' <i class="fas fa-check" style="color: #00a300;"></i>' : ' <i class="fas fa-times" style="color: red"></i>').'<br>';
		}
		print '</td></td></tr>';

		// 2nd Approbateurs
		print '<tr>';
		print '<td class="titlefield fieldname_user_validation">2ème Validation</td>';
		print '<td class="valuefield fieldname_fk_user_validation_2">';
		$list_validation2 = $object->listApprover2;
		foreach($list_validation2[2] as $id => $user_static){
			print $user_static->getNomUrl(1).($list_validation2[1][$id] == 1 ? ' <i class="fas fa-check" style="color: #00a300;"></i>' : ' <i class="fas fa-times" style="color: red"></i>').'<br>';
		}
		print '</td></td></tr>';
	}
	else {
		$extrafieldsuser = new extrafields($db);
		$extrafieldsuser->fetch_name_optionals_label($usertoprocess->table_element);
		// 1er Approbateurs 
		print '<tr>';
		print '<td class="titlefield fieldname_user_validation">Approbateur</td>';
		print '<td class="valuefield fieldname_fk_user_validation_1">';
		print $extrafieldsuser->showOutputField('approbateurfdt', $usertoprocess->array_options['options_approbateurfdt'], '', $usertoprocess->table_element);
		print '</td></td></tr>';
	}

	if($user->rights->feuilledetemps->feuilledetemps->rapportUtilisateur) {
		print '<tr>';
		print '<td class="titlefield">Rapport Utilisateur</td>';
		print '<td class="valuefield"><a href="'.DOL_URL_ROOT.'/custom/feuilledetemps/TimesheetReportUser.php?search_usertoprocessid='.$object->fk_user.'">Voir le rapport</td></a>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';
}

if($permissionToVerification) {
	print '<textarea id="observationFDT" name="observationFDT" form="feuilleDeTempsForm" class="textarea_observation">';
	print $object->observation;
	print '</textarea>';
}

print '</div>';

print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';
if($displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
	print '<div class="fichehalfleft" style="margin-bottom: 2%;">';
}

print '<table class="border centpercent tableforfield">';

$alreadyoutput = 1;
foreach ($object->fields as $key => $val) {
	if ($alreadyoutput && $displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
		if (!empty($keyforbreak1) && $key == $keyforbreak1) {
			$alreadyoutput = 0; // key used for break on second column
		} else {
			continue;
		}
	}
	elseif ($alreadyoutput && (!$displayVerification || $conf->global->FDT_DISPLAY_COLUMN)) {
		if (!empty($keyforbreak) && $key == $keyforbreak) {
			$alreadyoutput = 0; // key used for break on second column
		} else {
			continue;
		}
	}

	if (!empty($keyforbreak2) && $key == $keyforbreak2 && $displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
		break; // key used for break on second column
	}

	// Discard if extrafield is a hidden field on form
	$visible = dol_eval($val['visible'], 1, 1, '2');
	if ($visible != 1 && $visible != 3 && $visible != 4 && $visible != 5) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !$val['enabled']) {
		continue; // We don't want this field
	}
	if (in_array($key, array('ref', 'status'))) {
		continue; // Ref and status are already in dol_banner
	}

	$value = $object->$key;

	print '<tr><td';
	print ' titlefieldfieldname_'.$key;
	//if ($val['notnull'] > 0) print ' fieldrequired';		// No fieldrequired inthe view output
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		print ' tdtop';
	}
	print '">';
	if (!empty($val['help'])) {
		print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		print $langs->trans($val['label']);
	}
	print '</td>';
	print '<td class="valuefield fieldname_'.$key;
	if ($val['type'] == 'text') {
		print ' wordbreak';
	}
	if ($val['cssview']) {
		print ' '.$val['cssview'];
	}
	print '">';
	if (in_array($val['type'], array('text', 'html'))) {
		print '<div class="longmessagecut">';
	}
	if($object->status != $object::STATUS_VERIFICATION || ($key != 'prime_astreinte' && $key != 'prime_exceptionnelle' && $key != 'prime_objectif' && $key != 'prime_variable' && $key != 'prime_amplitude')) {
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	else {
		print $object->showInputField($val, $key, $value, 'form="feuilleDeTempsForm"', '', '', 0, 1);
	}
	//print dol_escape_htmltag($object->$key, 1, 1);
	if (in_array($val['type'], array('text', 'html'))) {
		print '</div>';
	}
	print '</td>';
	print '</tr>';
}

if(!$displayVerification || $conf->global->FDT_DISPLAY_COLUMN) {
	if(!$conf->global->FDT_USER_APPROVER) {
		// 1er Approbateurs 
		print '<tr>';
		print '<td class="titlefield fieldname_user_validation">';
		print '1ère Validation';
		if(!empty($user->rights->feuilledetemps->changeappro) && $action != 'editvalidator1' && ($object->status == FeuilleDeTemps::STATUS_APPROBATION1)) {
			print '<span style="float: right;"><a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editvalidator1&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a></span>';
		}
		print '</td>';
		print '<td class="valuefield fieldname_fk_user_validation_1">';
		if ($action == 'editvalidator1' && !empty($user->rights->feuilledetemps->changeappro)) {
			$value = array();
			$list_validation1 = $object->listApprover1;
			foreach($list_validation1[2] as $id => $user_static){
				$value = array_merge($value, array($id));
			}
			$key = 'fk_user_approbation1';
			$object->fields[$key] = array('type'=>'chkbxlst:user:firstname|lastname:rowid', 'label'=>'UserApprobation1', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1);
			print $object->showInputField($object->fields[$key], $key, $value, '', '', '', 0);
			unset($object->fields[$key]);

			if ($action == 'editvalidator1') {
				print '<div style="text-align: center; margin: 5px;">';
				print '<input type="submit" class="button button-save" name="savevalidator1" value="'.$langs->trans("Save").'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';
			}
		}
		else {
			$list_validation1 = $object->listApprover1;
			foreach($list_validation1[2] as $id => $user_static){
				print $user_static->getNomUrl(1).($list_validation1[1][$id] == 1 ? ' <i class="fas fa-check" style="color: #00a300;"></i>' : ' <i class="fas fa-times" style="color: red"></i>').'<br>';
			}
		}
		print '</td></td></tr>';

		// 2nd Approbateurs
		print '<tr>';
		print '<td class="titlefield fieldname_user_validation">';
		print '2ème Validation';
		if(!empty($user->rights->feuilledetemps->changeappro) && $action != 'editvalidator2' && ($object->status == FeuilleDeTemps::STATUS_APPROBATION1 || $object->status == FeuilleDeTemps::STATUS_APPROBATION2)) {
			print '<span style="float: right;"><a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editvalidator2&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a></span>';
		}
		print '</td>';
		print '<td class="valuefield fieldname_fk_user_validation_2">';
		if ($action == 'editvalidator2' && !empty($user->rights->feuilledetemps->changeappro)) {
			$value = array();
			$list_validation2 = $object->listApprover2;
			foreach($list_validation2[2] as $id => $user_static){
				$value = array_merge($value, array($id));
			}
			$key = 'fk_user_approbation2';
			$object->fields[$key] = array('type'=>'chkbxlst:user:firstname|lastname:rowid', 'label'=>'UserApprobation2', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1);
			print $object->showInputField($object->fields[$key], $key, $value, '', '', '', 0);
			unset($object->fields[$key]);

			if ($action == 'editvalidator2') {
				print '<div style="text-align: center; margin: 5px;">';
				print '<input type="submit" class="button button-save" name="savevalidator1" value="'.$langs->trans("Save").'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';
			}
		}
		else {
			$list_validation2 = $object->listApprover2;
			foreach($list_validation2[2] as $id => $user_static){
				print $user_static->getNomUrl(1).($list_validation2[1][$id] == 1 ? ' <i class="fas fa-check" style="color: #00a300;"></i>' : ' <i class="fas fa-times" style="color: red"></i>').'<br>';
			}
		}
		print '</td></td></tr>';
	}
	else {
		$extrafieldsuser = new extrafields($db);
		$extrafieldsuser->fetch_name_optionals_label($usertoprocess->table_element);
		// 1er Approbateurs 
		print '<tr>';
		print '<td class="titlefield fieldname_user_validation">Approbateur</td>';
		print '<td class="valuefield fieldname_fk_user_validation_1">';
		print $extrafieldsuser->showOutputField('approbateurfdt', $usertoprocess->array_options['options_approbateurfdt'], '', $usertoprocess->table_element);
		print '</td></td></tr>';
	}

	if($user->rights->feuilledetemps->feuilledetemps->rapportUtilisateur) {
		print '<tr>';
		print '<td class="titlefield">Rapport Utilisateur</td>';
		print '<td class="valuefield"><a href="'.DOL_URL_ROOT.'/custom/feuilledetemps/TimesheetReportUser.php?search_usertoprocessid='.$object->fk_user.'">Voir le rapport</td></a>';
		print '</tr>';
	}
}

print '</table>';
if($displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
	print '</div>';
}

if($displayVerification && !$conf->global->FDT_DISPLAY_COLUMN) {
	print '<div class="fichehalfright">';
	print '<table class="border centpercent tableforfield">';

	$alreadyoutput = 1;
	foreach ($object->fields as $key => $val) {
		if ($alreadyoutput) {
			if (!empty($keyforbreak2) && $key == $keyforbreak2) {
				$alreadyoutput = 0; // key used for break on second column
			} else {
				continue;
			}
		}

		// Discard if extrafield is a hidden field on form
		$visible = dol_eval($val['visible'], 1, 1, '2');
		if ($visible != 1 && $visible != 3 && $visible != 4 && $visible != 5) {
			continue;
		}

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !$val['enabled']) {
			continue; // We don't want this field
		}
		if (in_array($key, array('ref', 'status'))) {
			continue; // Ref and status are already in dol_banner
		}

		$value = $object->$key;

		print '<tr><td';
		print ' titlefieldfieldname_'.$key;
		//if ($val['notnull'] > 0) print ' fieldrequired';		// No fieldrequired inthe view output
		if ($val['type'] == 'text' || $val['type'] == 'html') {
			print ' tdtop';
		}
		print '">';
		if (!empty($val['help'])) {
			print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		} else {
			print $langs->trans($val['label']);
		}
		print '</td>';
		print '<td class="valuefield fieldname_'.$key;
		if ($val['type'] == 'text') {
			print ' wordbreak';
		}
		if ($val['cssview']) {
			print ' '.$val['cssview'];
		}
		print '">';
		if (in_array($val['type'], array('text', 'html'))) {
			print '<div class="longmessagecut">';
		}
		if($object->status != $object::STATUS_VERIFICATION || ($key != 'prime_astreinte' && $key != 'prime_exceptionnelle' && $key != 'prime_objectif' && $key != 'prime_variable' && $key != 'prime_amplitude')) {
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
		}
		else {
			print $object->showInputField($val, $key, $value, 'form="feuilleDeTempsForm"', '', '', 0, 1);
		}
		//print dol_escape_htmltag($object->$key, 1, 1);
		if (in_array($val['type'], array('text', 'html'))) {
			print '</div>';
		}
		print '</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';
}

if (!empty($object->table_element_line) && $permissionToVerification && $conf->donneesrh->enabled) {
	// Show object lines
	$result = $object->getLinesArray();

	if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	print '<div style="margin: 2%;">';
	print '<div class="div-table-responsive-no-min compta">';
	print '<table id="tablelines" class="noborder noshadow" width="100%" style="border: unset;">';
	$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/custom/feuilledetemps/core/tpl');

	if(empty($object->lines)) {
		print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print '</table>';
	print '</div>';
	print '</div>';
	print "</form>\n";
}


?>
<!-- END PHP TEMPLATE commonfields_view.tpl.php -->
