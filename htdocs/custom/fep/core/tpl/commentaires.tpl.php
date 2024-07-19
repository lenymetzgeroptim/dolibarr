<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 * $form
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

?>
<!-- BEGIN PHP TEMPLATE question.tpl.php -->
<?php

print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>COMMENTAIRES FOURNISSEURS</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 90%; margin: auto; border-collapse: collapse;">';
print '<tr class="field">';
print '<td class="valuefield" style="border: 0.5px solid  #ccc; padding: 5px; width: 30%; text-align: center;"><strong>';
print 'Thématique';
print '</strong></td>';
print '<td class="valuefield" style="border: 0.5px solid  #ccc; padding: 5px; width: 30%; text-align: center;"><strong>';
print 'Constats';
print '</strong></td>';
print '<td class="valuefield" style="border: 0.5px solid  #ccc; padding: 5px; width: 30%; text-align: center;"><strong>';
print "Propositions d'amélioration";
print '</strong></td></tr>';

// LIGNE 1
print '<tr class="field">';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; width: 30%;">';
print '<strong>Moyens mis à disposition pour réaliser la prestation :</strong><br>- Documentation : procédures, analyse de risques, DSI, homogénéisation des pratiques et méthodes, 
		qualité de la documentation, etc.<br>- Logistique : accès au site, outillage, informatique et télécoms, accueil, pièces de rechange (qualité et disponibilité), 
		colisage/entreposage/stockage, moyens de levage et manutention, échafaudages, gestion des déchets, etc';
print '</td>';
$key = 'constat1';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
$key = 'prop_amelioration1';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
print '</tr>';

// LIGNE 2
print '<tr class="field">';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; width: 30%;">';
print '<strong>Dispositions vis-à-vis du personnel :</strong><br>- Sécurité : règles vitales, situations à risques, régimes, autorisations, permis de feu, consignation (qualité et délai), 
		qualité des moyens mis à disposition, etc.<br>- Radioprotection : propreté radiologique, matériel RP, gestion des tirs radio, balisage, exposition (dont probio), cartographie/affichage, sas, confinement, appui RP, etc.';
print '</td>';
$key = 'constat2';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
$key = 'prop_amelioration2';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
print '</tr>';

// LIGNE 3
print '<tr class="field">';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; width: 30%;">';
print "<strong>Contexte de l'intervention :</strong><br>- Préparation / déroulement de l'intervention : coordination et planification de l'intervention, ponctualité, implication, 
		facilitation de l'intervention, durée de la prestation, traitement des écarts, surveillance, dispositions mises en place par EDF pour améliorer la maîtrise du risque FME
		, des NQME, etc.<br>- Conditions d'intervention : accès au matériel, environnement de travail, conception, co-activités, aléas, etc.<br>- Etat des installations : maintien 
		en état exemplaire des installations, etc.<br>- Prise en compte du REX : partage des bonns pratiques, récurrence d'écarts observées, etc.";
print '</td>';
$key = 'constat3';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
$key = 'prop_amelioration3';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
print '</tr>';

// LIGNE 4
print '<tr class="field">';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; width: 30%;">';
print "<strong>Commentaires généraux :</strong><br>- Accord avec l'évaluation<br>- Communication / relationnel : transparence, partage, réactivité, collaboration, ambiance, etc.<br>- Autres ";
print '</td>';
$key = 'constat4';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
$key = 'prop_amelioration4';
$val = $object->fields[$key];
$value = $object->$key;
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 5px; background-color: #b7b7b763; vertical-align: baseline; width: 30%;">';
if ($action == 'editcommentaires') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</td>';
print '</tr>';

print '</table><br/><br/>';

?>
<!-- END PHP TEMPLATE question.tpl.php -->