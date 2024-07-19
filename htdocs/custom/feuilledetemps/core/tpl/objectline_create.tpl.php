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

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

$objectline = new ObservationCompta($this->db);
$formother = new FormOther($db);

if(count($this->lines) == 0){
	print '<tr class="liste_titre nodrag nodrop">';
	foreach($objectline->fields as $key => $val){
		if($key != 'date_start' && $key != 'date_end' && $key != 'type' && $key != 'observation'){
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
	if($key != 'date_start' && $key != 'date_end' && $key != 'type' && $key != 'observation'){
		continue;
	}

	if($key == 'date_start' || $key == 'date_end') {
		print '<td class="center nobottom linecol'.$key.'">';
		print $formother->select_month((GETPOST($key.'month') ? GETPOST($key.'month') : dol_print_date(dol_now(), '%m')), $key.'month', 1, 1, 'minwidth50 valignmiddle', true);
		print ' ';
		print $formother->selectyear(GETPOST($key.'year'), $key.'year', 0, 1, 5, 0, 0, '', 'minwidth75 maxwidth75imp valignmiddle', true);
		print '</td>';
	}
	else if($key != 'ref'){
		print '<td class="center nobottom linecol'.$key.'">'.$objectline->showInputField($val, $key, '').'</td>';
	}
	else {
		print '<td class="nobottom linecol'.$key.'">&nbsp;</td>';
	}
}

print '<td class="nobottom linecoledit center valignmiddle" colspan="'.(count($this->lines) == 0 ? "3" : "6").'">';
print '<input type="submit" class="button reposition" value="'.$langs->trans('Add').'" name="addline" id="addline"></td>';
print '</tr>';