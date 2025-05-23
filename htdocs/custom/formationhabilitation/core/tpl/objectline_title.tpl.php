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
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

global $sortfield, $sortorder, $selectedfields, $arrayfields, $object, $param, $objectline;
global $permissiontoreadline, $permissiontolinkandunlink;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (!$permissiontoreadline) {
	exit;
}

$objectline->fields = dol_sort_array($objectline->fields, 'position');

print "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php -->\n";

// Title line
print '<tr class="liste_titre nodrag nodrop">';

foreach($objectline->fields as $key => $val){
	if($action == 'editline') {
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
			continue;
		}
	}

	if($key == 'formateur') {
		continue;
	}
	
	//print '<td class="linecol'.$key.'">'.$langs->trans($val['label']).'</td>';
	if (!empty($arrayfields['t.'.$key]['checked']) || $action == 'editline') {
		$cssforfield = (!empty($val['css']) ? $val['css'] : 'center');
		print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], $key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}
}

if ($object->table_element == 'formationhabilitation_uservolet' && $object->id > 0 && $permissiontolinkandunlink) {
	print '<th class="liste_titre linecollink"></th>';
	// print '<th class="liste_titre linecoledit"></th>'; 
	// print '<th class="liste_titre linecoldelete"></th>';
}
elseif($object->table_element != 'formationhabilitation_uservolet') {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', $param, 'class="center" colspan="3"', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
}

print "</tr>\n";

print "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";
