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

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val) {
	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
		continue; // We don't want this field
	}

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
		print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
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
	} else {
		$value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
	}
	//var_dump($val.' '.$key.' '.$value);
	if ($val['noteditable']) {
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	} else {
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	print '</td>';
	print '</tr>';

	if($key == 'fk_user' && !$conf->global->FDT_USER_APPROVER){
		// 1er Approbateur
		$value = array();
		print '<tr class="field_fk_user_validation1"><td';
		print ' class="titlefieldcreate">1ère validation';
		print '</td>';
		$list_validation1 = $object->listApprover1;
		foreach($list_validation1[2] as $id => $user_static){
			$value = array_merge($value, array($id));
		}
		print '<td class="valuefieldcreate">';
		$key = 'fk_user_validation1';
		$object->fields[$key] = array('type'=>'chkbxlst:user:firstname|lastname:rowid', 'label'=>'UserValidation1', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1);
		print $object->showInputField($object->fields[$key], $key, $value, '', '', '', 0);
		unset($object->fields[$key]);
		print '</td>';
		print '</tr>';

		// 2nd Approbateur
		$value = array();
		print '<tr class="field_fk_user_validation2"><td';
		print ' class="titlefieldcreate">2ème validation';
		print '</td>';
		$list_validation2 = $object->listApprover2;
		foreach($list_validation2[2] as $id => $user_static){
			$value = array_merge($value, array($id));
		}
		print '<td class="valuefieldcreate">';
		$key = 'fk_user_validation2';
		$object->fields[$key] = array('type'=>'chkbxlst:user:firstname|lastname:rowid', 'label'=>'UserValidation2', 'enabled'=>'1', 'position'=>51, 'notnull'=>1, 'visible'=>1);
		print $object->showInputField($object->fields[$key], $key, $value, '', '', '', 0);
		unset($object->fields[$key]);
		print '</td>';
		print '</tr>';
	}
}

?>
<!-- END PHP TEMPLATE commonfields_edit.tpl.php -->
