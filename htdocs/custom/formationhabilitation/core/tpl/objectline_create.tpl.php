<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019		Nicolas ZABOURI		<info@inovea-conseil.com>
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
 * $senderissupplier (0 by default, 1 or 2 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

 global $permissiontoreadCout;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

if ($this->table_element_line == 'formationhabilitation_user_formation') {
	$objectline = new User_formation($this->db);
}
if ($this->table_element_line == 'formationhabilitation_user_habilitation') {
	$objectline = new User_habilitation($this->db);
} 

$objectline->fields = dol_sort_array($objectline->fields, 'position');

if(count($this->lines) == 0){
	print '<tr class="liste_titre nodrag nodrop">';
	foreach($objectline->fields as $key => $val){
		if($this->element == 'formation' && $key == 'fk_formation'){
			continue;
		}
		if($this->element == 'habilitation' && $key == 'fk_habilitation'){
			continue;
		}
		if($this->element == 'user' && $key == 'fk_user'){
			continue;
		}
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3) {
			continue;
		}
		if(($key == 'cout_pedagogique' || $key == 'cout_mobilisation' || $key == 'cout_total') && !$permissiontoreadCout) {
			continue;
		}

		print '<td class="center linecol'.$key.'">'.$langs->trans($val['label']).'</td>';
	}

	print '<td class=""></td>';
	print '</tr>';
}

if(count($this->lines) == 0){
	print '<tr class="pair nodrag nodrop nohoverpair">';
}
else {
	print '<tr class="pair nodrag nodrop nohoverpair liste_titre_create">';
}

foreach($objectline->fields as $key => $val){
	if($this->element == 'formation' && $key == 'fk_formation'){
		continue;
	}
	if($this->element == 'habilitation' && $key == 'fk_habilitation'){
		continue;
	}
	if($this->element == 'user' && $key == 'fk_user'){
		continue;
	}
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3) {
		if(abs($val['visible']) == 4) {
			print '<td class="nobottom linecol'.$key.'">&nbsp;</td>';
		}
		continue;
	}
	if(($key == 'cout_pedagogique' || $key == 'cout_mobilisation' || $key == 'cout_total') && !$permissiontoreadCout) {
		continue;
	}

	print '<td class="center nobottom linecol'.$key.'">'.$objectline->showInputField($val, $key, '').'</td>';
}

print '<td class="nobottom linecoledit center valignmiddle" colspan="3">';
print '<input type="submit" class="button reposition" value="'.$langs->trans('Add').'" name="addline" id="addline"></td>';
print '</tr>';