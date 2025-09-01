<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos Garc�a <marcosgdf@gmail.com>
 * Copyright (C) 2023       Soufiane Fadel <s.fadel@optim-industries.fr>
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
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

print "<!-- BEGIN PHP TEMPLATE custom/actions/core/tpl/linkedobjectblock.tpl.php -->\n";

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

// Load translation files required by the page
$langs->load("actions");

$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);

$total = 0;
$ilink = 0;

foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	}
	echo '<tr class="'.$trclass.'" >';
	echo '<td class="linkedcol-element" >'.$langs->trans(ucfirst($objectlink->element));
	if (!empty($showImportButton) && !empty($conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES)) {
		print '<a class="objectlinked_importbtn" href="'.$objectlink->getNomUrl(0, '', 0, 1).'&amp;action=selectlines" data-element="'.$objectlink->element.'" data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a';
	}
	
	echo '</td>';
	echo '<td class="linkedcol-name nowraponall" >'.$objectlink->getNomUrl(1).'</td>';
	echo '<td class="linkedcol-ref">'.$objectlink->ref_client.'</td>';

	if($objectlink->element == 'action' && $objectlink->date_creation > 0) {
		echo '<td class="linkedcol-date">'.dol_print_date($objectlink->date_creation, 'day').'</td>';
	}elseif($objectlink->element == 'constat' && $objectlink->emetteur_date > 0){
		echo '<td class="linkedcol-date">'.dol_print_date($objectlink->emetteur_date, 'day').'</td>';
	}else{
		echo '<td class="linkedcol-date"></td>';
	}

	echo '<td class="linkedcol-amount right">';
	//if ($user->rights->actions->lire) {
		$total = sizeof($linkedObjectBlock);
		echo $objectlink->label;
	// }
	echo '</td>';
	echo '<td class="linkedcol-statut right">'.$objectlink->getLibStatut(1).'</td>';
	echo '<td class="linkedcol-action right">';
	// For now, shipments must stay linked to order, so link is not deletable
	/*if ($object->element != 'shipping') {
		echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a>';
	}*/
	echo '</td>';
	echo "</tr>\n";
}
if (count($linkedObjectBlock) > 0) {
	echo '<tr class="liste_total '.(empty($noMoreLinkedObjectBlockAfter) ? 'liste_sub_total' : '').'">';
	echo '<td>'.$langs->trans("Total").'</td>';
	echo '<td></td>';
	echo '<td class="center"></td>';
	echo '<td class="center"></td>';
	echo '<td class="right">'.$total.'</td>';
	echo '<td class="right"></td>';
	echo '<td class="right"></td>';
	echo "</tr>\n";
}

echo "<!-- END PHP TEMPLATE -->\n";
