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

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $id; 

$objectline = new ObservationCompta($this->db);


$domData = ' data-id="'.$line->id.'"';

print '<tr class="drag drop oddeven"'.$domData.'>';
foreach($objectline->fields as $key => $val){
	if($key != 'date_start' && $key != 'date_end' && $key != 'type' && $key != 'observation'){
		continue;
	}

	print '<td class="center linecol'.$key.' nowrap">';
	if($key == 'date_start' || $key == 'date_end') {
		print dol_print_date($line->$key, '%B %Y');
	}
	else {
		print $line->showOutputField($val, $key, $line->$key);
	}
	print '</td>';
}


if($this->id > 0) {
	print '<td class="linecoldonneesrh center" style="width: 10px">';
	print '</td>';
}
else {
	print '<td class="linecolmove center" style="width: 10px">';
	print '</td>';

	print '<td class="linecoledit center" style="width: 10px">';
	if (empty($disableedit)) {
		$url = $_SERVER["PHP_SELF"].'?id='.$id.'&action=editline&onglet=9999&token='.newToken().'&lineid='.$line->id.'#line_'.$line->id;
		print '<a class="editfielda reposition" href="'.$url.'">';
		print img_edit().'</a>';
	}
	print '</td>';

	print '<td class="linecoldelete center" style="width: 10px">';
	if (empty($disableremove)) { 
		$url = $_SERVER["PHP_SELF"].'?id='.$id.'&action=deleteline&onglet=9999&token='.newToken().'&lineid='.$line->id;
		print '<a class="reposition" href="'.$url.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';
}

print '</tr>';

