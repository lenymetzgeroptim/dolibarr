<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021  Lény Metzger         <leny-07@hotmail.fr>
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
 * $action
 * $conf
 * $langs
 *
 * $keyforbreak may be defined to key to switch on second column
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}
if (!is_object($form)) {
	$form = new Form($db);
}

if (empty($keyforbreak)){
	$keyforbreak = 'effectif';
}
?>
<!-- BEGIN PHP TEMPLATE commonfields_view.tpl.php -->
<?php

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val) {
	if ($key != 'ded_optimise' && $key != 'dc_optimise' && $key != 'cdd_optimise' && $key != 'prop_rad_optimise' && $key != 'debit_dose_estime_optimise' && $key != 'prop_radiologique_optimise') {
		if (!empty($keyforbreak) && $key == $keyforbreak) {
			break; // key used for break on second column
		}

		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
			continue;
		}

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
			continue; // We don't want this field
		}
		if (in_array($key, array('ref', 'status'))) {
			continue; // Ref and status are already in dol_banner
		}

		$value = $object->$key;

		print '<tr class="field_'.$key.'"><td';
		print ' class="titlefield fieldname_'.$key;
		//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
		if ($val['type'] == 'text' || $val['type'] == 'html') {
			print ' tdtop';
		}
		print '">';
		if (!empty($val['help'])) {
			print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		} else {
			print $langs->trans($val['label']);
		}
		print '</td>';
		print '<td class="valuefield fieldname_'.$key;
		if ($val['type'] == 'text') {
			print ' wordbreak';
		}
		if (!empty($val['cssview'])) {
			print ' '.$val['cssview'];
		}
		print '">';
		if (in_array($val['type'], array('text', 'html'))) {
			print '<div class="longmessagecut">';
		}

		if($key == "fk_user_pcr"){
			if($action == 'editpcr' && $permissionToEditChamps){
				print $form->select_dolusersInGroup(array(11), (!empty(GETPOST('fk_user_pcr', 'int')) ? GETPOST('fk_user_pcr', 'int') : $value), 'fk_user_pcr', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'minwidth200imp');
				//print $object->showInputField($val, $key, $value, '', '', '', 0);
				print '<td class="center">';
				print '<input type="submit" class="button button-save" name="savevalidator" value="'.$langs->trans("Save").'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
			}
			else {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
				if($permissionToEditChamps){
					print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editpcr">'.img_edit($langs->trans("Edit")).'</a>';
				}
			}
		}
		elseif($key == "fk_user_rsr"){
			if($action == 'editrsr' && $permissionToEditChamps){
				print $form->select_dolusersInGroup(array(16, 11), (!empty(GETPOST('fk_user_rsr', 'int')) ? GETPOST('fk_user_rsr', 'int') : $value), 'fk_user_rsr', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'minwidth200imp');
				//print $object->showInputField($val, $key, $value, '', '', '', 0);
				print '<td class="center">';
				print '<input type="submit" class="button button-save" name="savevalidator" value="'.$langs->trans("Save").'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
			}
			else {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
				if($permissionToEditChamps){
					print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editrsr">'.img_edit($langs->trans("Edit")).'</a>';
				}
			}
		}
		else if($key == "fk_user_raf"){
			if($action == 'editraf' && $permissionToEditChamps){
				print $form->select_dolusersInGroup(array(8), (!empty(GETPOST('fk_user_raf', 'int')) ? GETPOST('fk_user_raf', 'int') : $value), 'fk_user_raf', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'minwidth200imp');
				//print $object->showInputField($val, $key, $value, '', '', '', 0);
				print '<td class="center">';
				print '<input type="submit" class="button button-save" name="savevalidator" value="'.$langs->trans("Save").'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
			}
			else {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
				if($permissionToEditChamps){
					print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editraf">'.img_edit($langs->trans("Edit")).'</a>';
				}
			}
		}
		else {
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
		}

		//print dol_escape_htmltag($object->$key, 1, 1);
		if (in_array($val['type'], array('text', 'html'))) {
			print '</div>';
		}
		print '</td>';

		if ($key == 'date_fin'){
			$key = 'date_fin_prolong';
			$val = $object->fields[$key];
			$value = $object->$key;
			print '<td style="color: #0064ff;">';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
			print '</td>';
		}
		else {
			print '<td></td>';
		}

		print '</tr>';
	}
}

print '</table>';

// Afichage de la table de l'AOA
if($object->aoa == 2 || $object->aoa == 3){
	print '<br/>';
	print '<br/>';
	print '<br/>';
	print '<br/>';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// DED moyen Optimisé
	$key = 'ded_optimise';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';

	// DED max Optimisé
	$key = 'ded_max_optimise';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';

	// DC Optimisée
	$key = 'dc_optimise';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';

	// CDD Optimisée
	$key = 'cdd_optimise';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';

	// Prop Rad Optimisée
	$key = 'prop_rad_optimise';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';

	// Consignes RP
	$key = 'consignes_rp';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="color: #0064ff;">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';

	// EPI spécifique
	$key = 'epi_specifique';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="color: #0064ff;">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';

	// Commentaire AOA
	$key = 'commentaire_aoa';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="titlefield fieldname_'.$key.'">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'"style="color: #0064ff;">';
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
	print '</td>';
	print '</tr>';
	

	print '</table><br>';
}




// We close div and reopen for second column
print '</div>';
print '<div class="fichehalfright">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Ligne Valeur init / Valeur opti
print '<tr><td';
print ' class="titlefield fieldname_valeur">';
print '</td>';
print '<td class="valuefield fieldname_valeur_init">';
print '<strong>Valeur initiale</strong>';
print '</td>';
print '<td style="color: #0064ff;">';
print '<strong>Valeur optimisée</strong>';
print '</td>';
print '</tr>';

// Fod Orange
print '<tr class="field_fod_orange"><td';
print ' class="titlefield fieldname_fod_orange">';
print 'FOD orange</td>';
if($object->debit_dose_estime >= $conf->global->FODOrange_PARAM1 || $object->debit_dose_max >= $conf->global->FODOrange_PARAM2) {
	print '<td class="valuefield fieldname_fod_orange" style="color: #fb7600;"><strong>';
	print 'OUI';
}
else {
	print '<td class="valuefield fieldname_fod_orange"><strong>';
	print 'NON';
	$non = 1;
}
print '</strong></td>';
if(!$non){
	if (!empty($object->debit_dose_estime_optimise) || !empty($object->debit_dose_max_optimise)){
		if (!empty($object->debit_dose_estime_optimise)){
			$ded_estime = $object->debit_dose_estime_optimise;
		}
		else {
			$ded_estime = $object->debit_dose_estime;
		}

		if (!empty($object->debit_dose_max_optimise)){
			$ded_max = $object->debit_dose_max_optimise;
		}
		else {
			$ded_max = $object->debit_dose_max;
		}

		if($ded_estime < 1.6 && $ded_max < 1.6) {
			print '<td style="color: #0064ff;"><strong>';
			print 'NON';
			print '</strong></td>';
		}
	}
	else print '<td></td>';
}
else print '<td></td>';
print '</tr>';

$alreadyoutput = 1;
foreach ($object->fields as $key => $val) {
	if ($alreadyoutput) {
		if (!empty($keyforbreak) && $key == $keyforbreak) {
			$alreadyoutput = 0; // key used for break on second column
		} else {
			continue;
		}
	}

	if ($key != 'date_fin_prolong'){
		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
			continue;
		}


		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !$val['enabled']) {
			continue; // We don't want this field
		}
		if (in_array($key, array('ref', 'status'))) {
			continue; // Ref and status are already in dol_banner
		}

		$value = $object->$key;

		print '<tr><td';
		print ' class="titlefield fieldname_'.$key;
		//if ($val['notnull'] > 0) print ' fieldrequired';		// No fieldrequired inthe view output
		if ($val['type'] == 'text' || $val['type'] == 'html') {
			print ' tdtop';
		}
		print '">';
		if (!empty($val['help'])) {
			print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		} else {
			print $langs->trans($val['label']);
		}
		print '</td>';
		print '<td class="valuefield fieldname_'.$key;
		if ($val['type'] == 'text') {
			print ' wordbreak';
		}
		if ($val['cssview']) {
			print ' '.$val['cssview'];
		}
		print '">';
		if (in_array($val['type'], array('text', 'html'))) {
			print '<div class="longmessagecut">';
		}
		if($key == 'ref_doc_client'){
			if($action == 'editdocclient' && $permissionToEditDocClient){
				print $object->showInputField($val, $key, $value, '', '', '', 0);
				print '<td class="center">';
				print '<input type="submit" class="button button-save" name="savevalidator" value="'.$langs->trans("Save").'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
			}
			else {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
				if($permissionToEditDocClient){
					print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editdocclient">'.img_edit($langs->trans("Edit")).'</a>';
				}
			}
		}
		else {
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
		}

		

		//print dol_escape_htmltag($object->$key, 1, 1);
		if (in_array($val['type'], array('text', 'html'))) {
			print '</div>';
		}

		print '</td>';

		if ($key == 'debit_dose_estime' && $object->ded_optimise == 1){
			$key = 'debit_dose_estime_optimise';
			$val = $object->fields[$key];
			$value = $object->$key;
			print '<td style="color: #0064ff;">';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
			print '</td>';
		}
		elseif($key == 'debit_dose_estime') print '<td></td>';

		if ($key == 'debit_dose_max' && $object->ded_max_optimise == 1){
			$key = 'debit_dose_max_optimise';
			$val = $object->fields[$key];
			$value = $object->$key;
			print '<td style="color: #0064ff;">';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
			print '</td>';
		}
		elseif($key == 'debit_dose_estime') print '<td></td>';

		if ($key == 'duree_intervention' && !empty($object->duree_intervention_optimise) && $object->duree_intervention_optimise != $object->duree_intervention){
			$key = 'duree_intervention_optimise';
			$val = $object->fields[$key];
			$value = $object->$key;
			print '<td style="color: #0064ff;">';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
			print '</td>';
		}
		elseif($key == 'duree_intervention') print '<td></td>';

		if ($key == 'prop_radiologique' && $object->prop_rad_optimise == 1){
			$key = 'prop_radiologique_optimise';
			$val = $object->fields[$key];
			$value = $object->$key;
			print '<td style="color: #0064ff;">';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
			print '</td>';
		}
		elseif($key == 'prop_radiologique') print '<td></td>';

		if ($key == 'date_fin' && !empty($object->date_fin_prolong)){
			$key = 'date_fin_prolong';
			$val = $object->fields[$key];
			$value = $object->$key;
			print '<td style="color: #0064ff;">';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
			print '</td>';
		}
		elseif($key == 'date_fin') print '<td></td>';

		if ($key == 'effectif' && !empty($object->effectif_optimise) && $object->effectif_optimise != $object->effectif){
			$key = 'effectif_optimise';
			$val = $object->fields[$key];
			$value = $object->$key;
			print '<td style="color: #0064ff;">';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
			print '</td>';
		}
		elseif($key == 'effectif') print '<td></td>';

		if(!($key == 'debit_dose_estime' || $key == 'duree_intervention' || $key == 'prop_radiologique' || $key == 'date_fin')){
			print '<td></td>';
		}

		print '</tr>';
	}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Affichage de la dose collective previsionnel 
print '<tr class="field_dose_collective_previsionnelle"><td';
print ' class="titlefield fieldname_dose_collective_previsionnelle">';
print $form->textwithpicto('<strong>Dose collective previsionnelle</strong>', 'H.mSv');
print '</td>';
print '<td class="valuefield fieldname_dose_collective_previsionnelle"><strong>';
print $object->GetDoseCollectivePrevisionnelle();
print '</strong></td>';
if ($object->dc_optimise == 1){
	print '<td style="color: #0064ff;"><strong>';
	print $object->GetDoseCollectivePrevisionnelleOptimise();
	print '</strong></td>';
}
else print '<td></td>';
print '</tr>';

// Affichage de la dose individuelle moyenne
print '<tr class="field_dose_individuelle_moyenne"><td';
print ' class="titlefield fieldname_dose_individuelle_moyenne">';
print $form->textwithpicto('Dose individuelle moyenne', 'mSv');
print '</td>';
print '<td class="valuefield fieldname_dose_individuelle_moyenne">';
print $object->GetDoseIndividuelleMoyenne();
print '</td>';
if ((!empty($object->duree_intervention_optimise) || !empty($object->debit_dose_estime_optimise) || !empty($object->effectif_optimise)) && ($object->GetDoseIndividuelleMoyenneOptimise() < $object->GetDoseIndividuelleMoyenne())){
	print '<td style="color: #0064ff;">';
	print $object->GetDoseIndividuelleMoyenneOptimise();
	print '</td>';
}
else print '<td></td>';
print '</tr>';

// Affichage de la dose individuelle maximale
print '<tr class="field_dose_individuelle_max"><td';
print ' class="titlefield fieldname_dose_individuelle_max">';
print $form->textwithpicto('<strong>Dose individuelle maximale (CdD)</strong>', 'mSv');
print '</td>';
print '</strong><td class="valuefield fieldname_dose_individuelle_max"><strong>';
print $object->GetDoseIndividuelleMax();
print '</strong></td>';
if ($object->cdd_optimise == 1){
	print '<td style="color: #0064ff;"><strong>';
	print $object->GetDoseIndividuelleMaxOptimise();
	print '</strong></td>';
}
else print '<td></td>';
print '</tr>';

// Affichage de l'enjeu radiologique
print '<tr class="field_enjeu_radiologique"><td';
print ' class="titlefield fieldname_enjeu_radiologique">';
print $form->textwithpicto('<strong>Enjeu Radiologique</strong>', "Les critères de l'enjeu radiologique sont déclinés dans la SM 002 (§ 43412)<br>Si enjeu 2 ou 3 : une optimisation est à faire : Imprimé 601G ou comité ALARA");
print '</td>';
print '</strong><td class="valuefield fieldname_enjeu_radiologique"><strong>';
print $object->GetEnjeuRadiologique();
print '</strong></td>';
if($object->GetEnjeuRadiologiqueOptimise() < $object->GetEnjeuRadiologique()){
	print '<td style="color: #0064ff;"><strong>';
	print $object->GetEnjeuRadiologiqueOptimise();
	print '</strong></td>';
}
else print '<td></td>';
print '</tr>';

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


?>
<!-- END PHP TEMPLATE commonfields_view.tpl.php -->
