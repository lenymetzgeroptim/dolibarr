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

// THEME 1
print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>THEME 1 - RELATION TECHNICO-COMMERCIALES</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 50%; margin: auto; border-collapse: collapse;">';
// Question 1 
$key = 'question1_1';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}
print '</strong></td>';
print '</tr>';
// Question 2 
$key = 'question1_2';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 3 
$key = 'question1_3';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
print '</table><br/><br/>';

// THEME 2
print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>THEME 2 - MOYENS MIS EN OEUVRE</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 50%; margin: auto; border-collapse: collapse;">';
// Question 1 
$key = 'question2_1';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 2 
$key = 'question2_2';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 3 
$key = 'question2_3';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 4
$key = 'question2_4';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 5
$key = 'question2_5';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
print '</table><br/><br/>';

// THEME 3
print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>THEME 3 - SURETE ET ORGANISATION QUALITE</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 50%; margin: auto; border-collapse: collapse;">';
// Question 1 
$key = 'question3_1';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 2 
$key = 'question3_2';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 3 
$key = 'question3_3';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 4
$key = 'question3_4';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 5
$key = 'question3_5';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 6
$key = 'question3_6';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 7
$key = 'question3_7';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 8
$key = 'question3_8';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
print '</table><br/><br/>';

// THEME 4
print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>THEME 4 - SECURITE ET RADIOPROTECTION</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 50%; margin: auto; border-collapse: collapse;">';
// Question 1 
$key = 'question4_1';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 2 
$key = 'question4_2';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 3 
$key = 'question4_3';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 4
$key = 'question4_4';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
print '</table><br/><br/>';

// THEME 5
print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>THEME 5 - ENVIRONNEMENT</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 50%; margin: auto; border-collapse: collapse;">';
// Question 1 
$key = 'question5_1';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 2 
$key = 'question5_2';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
print '</table><br/><br/>';

// THEME 6
print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>THEME 6 - QUALITE TECHNIQUE DU PRODUIT</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 50%; margin: auto; border-collapse: collapse;">';
// Question 1 
$key = 'question6_1';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 2 
$key = 'question6_2';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 3 
$key = 'question6_3';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 4
$key = 'question6_4';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
print '</table><br/><br/>';

// THEME 7
print "<h3 style='color: rgb(47,80,139); text-align: center; margin-bottom: 0px;'>THEME 7 - GESTION DES DELAIS ET PLANNING</h3><hr style='margin-top: 0px;'>";
print '<table style="width: 50%; margin: auto; border-collapse: collapse;">';
// Question 1 
$key = 'question7_1';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
// Question 2
$key = 'question7_2';
$val = $object->fields[$key];
$value = $object->$key;
print '<tr class="field_'.$key.'"><td';
print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #ccc; padding: 3px; width: 80%;">';
print $langs->trans($val['label']).'</td>';
print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #ccc; text-align: center; background-color: #b7b7b763;"><strong>';
if ($action == 'editquestions') {
	print $object->showInputField($val, $key, $value, '', '', '', 0);
}
else {
	print $object->showOutputField($val, $key, $value, '', '', '', 0);
}print '</strong></td>';
print '</tr>';
print '</table><br/><br/>';

?>
<!-- END PHP TEMPLATE question.tpl.php -->