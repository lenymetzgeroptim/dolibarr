<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2020	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $canchangeproduct (0 by default, 1 to allow to change the product if it is a predefined product)
 */

global $object, $objectline;
global $permissiontoaddline;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (!$permissiontoaddline) {
	exit;
}

$objectline->fields = dol_sort_array($objectline->fields, 'position');

if($objectline->element == 'userhabilitation' || $objectline->element == 'userautorisation') {
	unset($line->fields['status']['arrayofkeyval'][2]);
	unset($line->fields['status']['arrayofkeyval'][3]);
}

print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

print '<tr class="oddeven tredited">';
print '<input type="hidden" name="lineid" value="'.$line->id.'">';
foreach($objectline->fields as $key => $val){
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
		continue;
	}

	if(($key == 'fk_societe' && $line->interne_externe == 2) || ($key == 'formateur' && $line->interne_externe != 2)) {
		print '<td style="display: none;" class="center linecol'.$key.' nowrap">'.$objectline->showInputField($val, $key, $line->$key, 'form="addline"').'</td>';
		continue;
	}
	
	$cssforfield = (!empty($val['css']) ? $val['css'] : 'center');
	print '<td class="linecol'.$key.' nowrap '.$cssforfield.'">';
	if($key == 'ref'){
		print $line->getNomUrl(0, 'nolink', 1);
	}
	elseif($object->element == 'formation' && $key == 'fk_user'){
		print $line->showOutputField($val, $key, $line->$key);
		print '<input type="hidden" id="'.$key.'" name="'.$key.'" value="'.$line->$key.'"></input>';
	}
	elseif($object->element == 'user' && ($key == 'fk_formation' || $key == 'fk_habilitation' || $key == 'fk_autorisation')){
		print $line->showOutputField($val, $key, $line->$key);
		print '<input type="hidden" id="'.$key.'" name="'.$key.'" value="'.$line->$key.'"></input>';
	}
	elseif($key == 'cout_pedagogique' || $key == 'cout_mobilisation' || $key == 'cout_total' || $key == 'date_finvalidite_formation'){
		print $line->showOutputField($val, $key, $line->$key);
	}
	elseif($key == 'status') {
		print $line->getLibStatut(2);
	}
	else {
		print $line->showInputField($val, $key, $line->$key, 'form="addline"');
	}
	print '</td>';
}

print '<td class="center valignmiddle" colspan="3">';
print '<input type="submit" form="addline" class="button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'">';
print '<input type="submit" class="button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</td>';

print '</tr>';

print '<!-- END PHP TEMPLATE objectline_edit.tpl.php -->';
