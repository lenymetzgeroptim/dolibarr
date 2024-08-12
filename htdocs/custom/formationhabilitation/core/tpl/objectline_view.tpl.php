<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 * $conf
 * $langs
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $text, $description, $line
 */

global $permissiontoreadCout, $permissiontoaddline, $arrayfields, $massactionbutton, $massaction, $arrayofselected, $object, $lineid;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

if ($this->table_element_line == 'formationhabilitation_userformation') {
	$objectline = new UserFormation($this->db);
}
if ($this->table_element_line == 'formationhabilitation_userhabilitation') {
	$objectline = new UserHabilitation($this->db);
} 
if ($this->table_element_line == 'formationhabilitation_userautorisation') {
	$objectline = new UserAutorisation($this->db);
} 

if(!$user->rights->formationhabilitation->formation->addline){
	$disableedit = 1;
	$disableremove = 1;
}

$objectline->fields = dol_sort_array($objectline->fields, 'position');

$domData = ' data-id="'.$line->id.'"';

print '<tr class="drag drop oddeven"'.$domData.'>';
foreach($objectline->fields as $key => $val){
	if($object->element == 'formation' && $key == 'fk_formation'){
		continue;
	}
	if($object->element == 'habilitation' && $key == 'fk_habilitation'){
		continue;
	}
	if($object->element == 'autorisation' && $key == 'fk_autorisation'){
		continue;
	}
	if($object->element == 'user' && $key == 'fk_user'){
		continue;
	}
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
		continue;
	}
	if(($key == 'cout_pedagogique' || $key == 'cout_mobilisation' || $key == 'cout_total') && !$permissiontoreadCout) {
		continue;
	}
	if($key == 'formateur') {
		continue;
	}

	if (!empty($arrayfields['t.'.$key]['checked']) || $action == 'editline') {
		print '<td class="center linecol'.$key.' nowrap">';
		if($key == 'fk_societe' && $line->interne_externe == 2){
			$key = 'formateur';
			$val = $objectline->fields[$key];
			print $line->showOutputField($val, $key, $line->$key);
		}
		elseif($key == 'status'){
			print $line->getLibStatut(2);
			// if($line->status == UserFormation::STATUS_A_PROGRAMMER) {
			// 	print dolGetButtonAction($langs->trans('Programmer'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=programmer_formation&token='.newToken().'&lineid='.$line->id, '', $permissiontoaddline);
			// }
		}
		// elseif($key == 'ref'){
		// 	print $line->getNomUrl(0, 'nolink', 1);
		// }
		elseif($key == 'date_finvalidite_formation' && $permissiontoaddline && $action == 'edit_datefinvalidite' && $line->id == $lineid) {
			print $line->showInputField($val, $key, $line->$key, 'form="addline"');
			print '<input type="hidden" form="addline" name="lineid" value="'.$line->id.'">';
		}
		else {
			print $line->showOutputField($val, $key, $line->$key);
		}

		if($key == 'date_finvalidite_formation') {
			if($permissiontoaddline && $action != 'edit_datefinvalidite') {
				print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_datefinvalidite&token='.newToken().(!empty($onglet) ? "&onglet=$onglet" : '').'&lineid='.$line->id.'#line_'.$line->id.'">'.img_edit($langs->trans("Edit")).'</a>';
			}
		}

		print '</td>';
	}
}

if(($object->element == 'user' && (empty(GETPOST('onglet', 'aZ09')) || GETPOST('onglet', 'aZ09') == 'formation')) || $object->element == 'formation'){
	/*print '<td class="center linecolcout_total nowrap">';
	$value3 = $value + $value2;
	print '<span class="amount" title="'.$langs->trans("CoutTot").' : '.price($value3, 1, $langs, 1, -1, -1, $conf->currency).'">';
	print price($value3, 1, $langs, 1, -1, -1, $conf->currency);
	print '</span>';
	print '</td>';*/
}


print '</td>';

if ($action == 'edit_datefinvalidite') {
	print '<td class="center valignmiddle" colspan="3">';
	print '<input type="submit" form="addline" class="button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save_datefinvalidite" value="'.$langs->trans("Save").'">';
	print '<input type="submit" class="button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td>';
}
else {
	print '<td class="linecoledit center">';
	if (empty($disableedit)) {
		if($object->element == 'user'){
			$url = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&onglet='.GETPOST('onglet', 'aZ09').'&token='.newToken().'&lineid='.$line->id.'#line_'.$line->id;
		}
		else {
			$url = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&token='.newToken().'&lineid='.$line->id.'#line_'.$line->id;
		}
		print '<a class="editfielda reposition" href="'.$url.'">';
		print img_edit().'</a>';
	}
	print '</td>';

	print '<td class="linecoldelete center">';
	if (empty($disableremove)) { 
		if($object->element == 'user'){
			$url = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteline&onglet='.GETPOST('onglet', 'aZ09').'&token='.newToken().'&lineid='.$line->id;
		}
		else {
			$url = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteline&token='.newToken().'&lineid='.$line->id;
		}
		print '<a class="reposition" href="'.$url.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected = 0;
		if (in_array($line->id, $arrayofselected)) {
			$selected = 1;
		}
		print '<input id="cb'.$line->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$line->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}
	print '</td>';
}

print '</tr>';

