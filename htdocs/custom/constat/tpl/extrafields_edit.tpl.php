<?php
/* Copyright (C) 2014	Maxime Kohlhaas		<support@atm-consulting.fr>
 * Copyright (C) 2014	Juanjo Menent		<jmenent@2byte.es>
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
 * $parameters
 * $cols
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}


?>
<!-- BEGIN PHP TEMPLATE extrafields_edit.tpl.php -->
<?php
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
    print "Error, template page can't be called as URL";
    exit;
}



require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
//condition si le respAFF n'est pas le chef de projet
$projet = new Project($db);
$projet->fetch($object->fk_project);
$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');

$pasresponsableaffaire = 0; 

if (!in_array($user->id, $liste_chef_projet)) {
    $pasresponsableaffaire = 1; 
}

// Script pour masquer certains champs si nécessaire
if (( ($user->rights->constat->constat->ResponsableAffaire && $pasresponsableaffaire != 1) || $user->rights->constat->constat->ServiceQ3SE || $user->rights->constat->constat->ResponsableQ3SE)) {
    print '<script>
        var elements = ["#extrarow-constat_impact_140", "#extrarow-constat_rubrique_140", "#extrarow-constat_processusconcern_140"];

        elements.forEach(function(element) {
            var elems = document.querySelectorAll(element);
            elems.forEach(function(el) {
                el.style.display = "none";
            });
        });
    </script>';
}

$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); 
print $hookmanager->resPrint;

if (empty($reshook)) {
    $params = array();
    if (isset($tpl_context)) {
        $params['tpl_context'] = $tpl_context;
    }
    $params['cols'] = key_exists('colspanvalue', $parameters) ? $parameters['colspanvalue'] : null;


    // Réorganiser les extrafields pour mettre "impact" en dernier
    if (!empty($extrafields->attributes[$object->table_element]['label'])) {

        $labels = $extrafields->attributes[$object->table_element]['label'];

        // Crée un nouvel ordre des champs
        $new_order = [];
        foreach ($labels as $key => $label) {
            if (strtolower($key) !== 'impact') {
                $new_order[$key] = $label;
            }
        }
        if (array_key_exists('impact', $labels)) {
            $new_order['impact'] = $labels['impact']; // Ajouter "impact" à la fin
        }

        if ($object->status == $object::STATUS_VALIDATED) {
            $requiredFields = &$extrafields->attributes[$object->table_element]['required'];
            foreach ($requiredFields as &$field) {
                $field = '1';
            }
            $required = $requiredFields;
        }

        // Remplace les labels par le nouvel ordre
        $extrafields->attributes[$object->table_element]['label'] = $new_order;
        $extrafields->attributes[$object->table_element]['required'] = $requiredFields;
    }
//var_dump($new_order);
//var_dump( $extrafields->attributes[$object->table_element]);
    // Affichage des options supplémentaires
    print $object->showOptionals($extrafields, 'edit', $params);
}
?>

<!-- END PHP TEMPLATE extrafields_edit.tpl.php -->
