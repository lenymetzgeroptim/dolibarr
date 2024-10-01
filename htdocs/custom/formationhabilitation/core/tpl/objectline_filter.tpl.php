<?php
/* Copyright (C) 2017 Lény METZGER  <leny-07@hotmail.fr>
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

global $sortfield, $sortorder, $search, $langs;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

$objectline->fields = dol_sort_array($objectline->fields, 'position');

print "<!-- BEGIN PHP TEMPLATE objectline_filter.tpl.php -->\n";

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

	// if($object->element == 'formation' && $key == 'fk_formation'){
	// 	print '<input type="hidden" name="search_'.$key.'" value="'.dol_escape_htmltag(isset($object->id) ? $object->id : '').'">';
	// 	continue;
	// }
	// elseif($object->element == 'user' && $key == 'fk_user'){
	// 	print '<input type="hidden" name="search_'.$key.'" value="'.dol_escape_htmltag(isset($object->id) ? $object->id : '').'">';
	// 	continue;
	// }

	$cssforfield = (!empty($val['css']) ? $val['css'] : 'center');
	if (in_array($val['type'], array('timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	} 
	if (!empty($arrayfields['t.'.$key]['checked']) || $action == 'editline') {
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if($key == 'status') {
			$value = $objectline->getArrayStatut();
			print $form->multiselectarray('search_status', $value, explode(',', $search[$key]));
		} elseif (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			//print $form->selectarray('search_'.$key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
			print $form->selectarray('search_'.$key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), 1, 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);

		} elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
			print $objectline->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', 'maxwidth125', 1);
		} elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
			print '<div class="nowrap">';
			print $form->selectDate($search[$key.'_dtstart'] ? $search[$key.'_dtstart'] : '', "search_".$key."_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
			print '</div>';
			print '<div class="nowrap">';
			print $form->selectDate($search[$key.'_dtend'] ? $search[$key.'_dtend'] : '', "search_".$key."_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
			print '</div>';
		} elseif ($key == 'lang') {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
			$formadmin = new FormAdmin($db);
			print $formadmin->select_language($search[$key], 'search_lang', 0, null, 1, 0, 0, 'minwidth150 maxwidth200', 2);
		} else {
			print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '').'">';
		}
		print '</td>';
	}
}

print '<td colspan="3" class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
print '</td>';

print "</tr>\n";

print "<!-- END PHP TEMPLATE objectline_filter.tpl.php -->\n";
