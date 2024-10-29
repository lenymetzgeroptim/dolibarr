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

global $permissiontoaddline, $arrayfields, $massactionbutton, $massaction, $arrayofselected, $object, $lineid, $param, $objectline;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

if(!$user->rights->formationhabilitation->formation->addline){
	$disableedit = 1;
	$disableremove = 1;
}

$objectline->fields = dol_sort_array($objectline->fields, 'position');
$url = dol_buildpath('/formationhabilitation/uservolet_card.php', 1).'?id='.$line->id;

print '<div class="col">';
	print '<div class="card">';
		print '<a class="customhover" href="'.$url.'">';
			//print '<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSEdw67K7dC_tAz6cwn64PyZRirAHi8Vdgd-A&s" class="card-img-top" alt="Image UserVolet">';
			print '<div class="card-body">';
				print $line->getNomUrl(0, 'nolink', 1, 'uservoletcardtitle');
				print '<div class="statusref" style="margin-top: 0px;">'.$line->getLibStatut(5).'</div><br><br>';

				foreach($objectline->fields as $key => $val){
					if($key == 'ref' || $key == 'status' || $key == 'fk_user'){
						continue;
					}
					if (abs($val['visible']) != 1 && abs($val['visible']) != 2 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
						continue;
					}
				
					if (!empty($arrayfields['t.'.$key]['checked'])) {
						$cssforfield = (!empty($val['css']) ? $val['css'] : 'center');
						print '<span style="font-weight: bold;" class="mb-0 '.$cssforfield.'">'.$langs->trans($val['label']).' : '.'</span>';
						print '<span class="mb-0 '.$cssforfield.'">'.$line->showOutputField($val, $key, $line->$key).'</span><br>';
					}
				}
			print '</div>';
		print '</a>';
	print '</div>';
print '</div>';

