<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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

?>
<!-- BEGIN PHP TEMPLATE commonfields_add.tpl.php -->
<?php

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val) {
	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
		continue; // We don't want this field
	}

	print '<tr class="field_'.$key.'">';
	print '<td';
	print ' class="titlefieldcreate';
	if (isset($val['notnull']) && $val['notnull'] > 0) {
		print ' fieldrequired';
	}
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		print ' tdtop';
	}
	print '"';
	print '>';
	if (!empty($val['help'])) {
		print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		if ($key != 'ref'){
			print $langs->trans($val['label']);	
		}
		else{
			print $langs->trans('NumeroChrono');
		}
	}
	print '</td>';
	print '<td class="valuefieldcreate">';

	if(($key == 'fk_user_pcr')||($key == 'fk_user_rsr')||($key == 'fk_user_raf')){
		if($key == 'fk_user_pcr'){
			print $form->select_dolusersInGroup(array(11), GETPOST('fk_user_pcr', 'int'), 'fk_user_pcr', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'minwidth200imp');
		}
		else if($key == 'fk_user_rsr'){
			print $form->select_dolusersInGroup(array(16, 11), GETPOST('fk_user_rsr', 'int'), 'fk_user_rsr', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'minwidth200imp');
		}
		else if($key == 'fk_user_raf'){
			print $form->select_dolusersInGroup(array(8), GETPOST('fk_user_raf', 'int'), 'fk_user_raf', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'minwidth200imp');
		}
	}
	elseif ($key == 'fk_project'){
		print $object->showInputField($val, $key, GETPOST('fk_project', 'int'), '', '', '', 0);
	}
	/*elseif ($key == 'ref'){
		print $object->showInputField($val, $key, (!empty(GETPOST('ref')) ? GETPOST('ref') : '000?'), '', '', '', 0);
	}*/
	elseif ($key == 'indice'){
		print $object->showInputField($val, $key, 'A', '', '', '', 0);
	}
	else {
		if (!empty($val['picto'])) {
			print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
		}
		if (in_array($val['type'], array('int', 'integer'))) {
			$value = GETPOST($key, 'int');
		} elseif (preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $val['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml'));
		} elseif ($val['type'] == 'text' || $val['type'] == 'html') {
			$value = GETPOST($key, 'restricthtml');
		} elseif ($val['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
		} elseif ($val['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
		} elseif ($val['type'] == 'boolean') {
			$value = (GETPOST($key) == 'on' ? 1 : 0);
		} elseif ($val['type'] == 'price') {
			$value = price2num(GETPOST($key));
		} else {
			$value = GETPOST($key, 'alphanohtml');
		}
		if (!empty($val['noteditable'])) {
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
		} else {
			print $object->showInputField($val, $key, $value, '', '', '', 0);
		}
	}

	print '</td>';
	print '</tr>';

}

?>
<!-- END PHP TEMPLATE commonfields_add.tpl.php -->
