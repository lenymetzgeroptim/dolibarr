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

$permissiontoreadCout = $user->rights->formationhabilitation->formation->readCout;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

$objectline = new ObservationCompta($this->db);
$formother = new FormOther($db);


print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

print '<tr class="oddeven tredited">';
print '<input type="hidden" name="lineid" value="'.$line->id.'">';
foreach($objectline->fields as $key => $val){
	if($key != 'date_start' && $key != 'date_end' && $key != 'type' && $key != 'observation'){
		continue;
	}
	
	print '<td class="center linecol'.$key.' nowrap">';
	if($key == 'date_start' || $key == 'date_end') {
		print $formother->select_month(dol_print_date($line->$key, '%m'), $key.'month', 1, 1, 'minwidth50 valignmiddle', true);
		print ' ';
		print $formother->select_year(dol_print_date($line->$key, '%Y'), $key.'year', 0, 1, 5, 0, 0, '', 'minwidth50 maxwidth75imp valignmiddle', true);
	}
	else { 
		print $line->showInputField($val, $key, $line->$key);
	}
	print '</td>';
}

print '<td class="center valignmiddle" colspan="3">';
print '<input type="submit" class="button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'"><br>';
print '<input type="submit" class="button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</td>';

print '</tr>';

print '<!-- END PHP TEMPLATE objectline_edit.tpl.php -->';
