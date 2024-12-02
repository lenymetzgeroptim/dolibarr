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

global $action, $resultcreate, $cancel, $resultupdate, $object, $objectline, $param;
global $permissiontoaddline, $permissiontoforceline;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

if (!$permissiontoaddline) {
	exit;
}

$objectline->fields = dol_sort_array($objectline->fields, 'position');

//if(count($this->lines) == 0){
	print '<tr class="liste_titre nodrag nodrop">';
	foreach($objectline->fields as $key => $val){
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
			continue;
		}
		if($key == 'formateur' || $key == 'non_renouvelee') {
			continue;
		}

		if($objectline->element == 'uservolet' && $key != 'fk_volet') {
			continue;
		}

		$cssforfield = (!empty($val['css']) ? $val['css'] : 'center');
		print '<td class="linecol'.$key.' '.$cssforfield.'">'.$langs->trans($val['label']).'</td>';
	}

	print '<td class=""></td>';
	print '</tr>';
//}

if(count($this->lines) == 0){
	print '<tr class="pair nodrag nodrop nohoverpair">';
}
else {
	print '<tr class="pair nodrag nodrop nohoverpair liste_titre_create">';
}

if($objectline->element == 'userformation') {
	unset($objectline->fields['status']['arrayofkeyval'][4]);
	unset($objectline->fields['status']['arrayofkeyval'][5]);
	unset($objectline->fields['status']['arrayofkeyval'][9]);
}
elseif($objectline->element == 'userhabilitation' || $objectline->element == 'userautorisation') {
	unset($objectline->fields['status']['arrayofkeyval'][2]);
	unset($objectline->fields['status']['arrayofkeyval'][3]);
	unset($objectline->fields['status']['arrayofkeyval'][9]);
}
elseif($objectline->element == 'uservolet') {
	unset($objectline->fields['status']['arrayofkeyval'][1]);
	unset($objectline->fields['status']['arrayofkeyval'][2]);
	unset($objectline->fields['status']['arrayofkeyval'][3]);
	unset($objectline->fields['status']['arrayofkeyval'][4]);
	unset($objectline->fields['status']['arrayofkeyval'][9]);
}

foreach($objectline->fields as $key => $val){
	if($objectline->element == 'uservolet' && $key != 'fk_volet') {
		continue;
	}
	elseif($objectline->element == 'userformation' && $key == 'non_renouvelee') {
		continue;
	}

	if (abs($val['visible']) != 1 && abs($val['visible']) != 3) {
		if(abs($val['visible']) == 4) {
			print '<td class="nobottom linecol'.$key.'">&nbsp;</td>';
		}
		continue;
	}

	if($key == 'date_finvalidite_formation' || $key == 'date_fin_habilitation' || $key == 'date_fin_autorisation') {
		print '<td class="nobottom linecol'.$key.'">&nbsp;</td>';
		continue;
	}

	if (in_array($val['type'], array('int', 'integer'))) {
		$value = GETPOST($key, 'int');
	} elseif ($val['type'] == 'double') {
		$value = price2num(GETPOST($key, 'alphanohtml'));
	} elseif (preg_match('/^text/', $val['type'])) {
		$tmparray = explode(':', $val['type']);
		if (!empty($tmparray[1])) {
			$check = $tmparray[1];
		} else {
			$check = 'nohtml';
		}
		$value = GETPOST($key, $check);
	} elseif (preg_match('/^html/', $val['type'])) {
		$tmparray = explode(':', $val['type']);
		if (!empty($tmparray[1])) {
			$check = $tmparray[1];
		} else {
			$check = 'restricthtml';
		}
		$value = GETPOST($key, $check);
	} elseif ($val['type'] == 'date') {
		$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
	} elseif ($val['type'] == 'datetime') {
		$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
	} elseif ($val['type'] == 'duration') {
		$value = convertTime2Seconds(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), 0);
	} elseif ($val['type'] == 'boolean') {
		$value = (GETPOST($key) == 'on' || GETPOST($key) == 1  ? 1 : 0);
	} elseif ($val['type'] == 'price') {
		$value = price2num(GETPOST($key));
	} elseif ($key == 'lang') {
		$value = GETPOST($key, 'aZ09');
	} else {
		$value = GETPOST($key, 'alphanohtml');
	}

	if(($action == 'addline' && $resultcreate) || ($action == 'updateline' && $resultupdate) || $cancel) {
		$value = '';
	}

	if(($key == 'fk_societe' && GETPOST('interne_externe') == 2) || ($key == 'formateur' && GETPOST('interne_externe') != 2)) {
		print '<td style="display: none;" class="center nobottom linecol'.$key.'">'.$objectline->showInputField($val, $key, $value, 'form="addline"').'</td>';
		continue;
	}

	$cssforfield = (!empty($val['css']) ? $val['css'] : 'center');
	print '<td class="nobottom linecol'.$key.' '.$cssforfield.'">'.$objectline->showInputField($val, $key, $value, 'form="addline"').'</td>';
}

print '<td class="nobottom linecoledit center valignmiddle" colspan="3">';
if($objectline->element != 'uservolet' && $permissiontoforceline) {
	print '<input type="checkbox" class="margin5" name="forcecreation" id="forcecreation" form="addline"/><label for="forcecreation">'.$langs->trans('ForceCreation').'</label>';
}
print '<input type="submit" class="button reposition" value="'.$langs->trans('Add').'" name="addline" id="addline" form="addline"></td>';
if(($objectline->element == 'userhabilitation' || $objectline->element == 'userautorisation') && $object->element == 'user') {
	print '<div style="text-align: center;">'.dolGetButtonAction($langs->trans('GenerateAuto'), '', 'default', $_SERVER["PHP_SELF"].'?'.$param.'&action=generation_auto&token='.newToken(), '', $permissiontoaddline).'</div><br>';
}
print '</tr>';