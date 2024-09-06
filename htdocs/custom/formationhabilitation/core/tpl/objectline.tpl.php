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

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
    $action = 'list';
    $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
    $massaction = '';
}

// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
//include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
    foreach ($objectline->fields as $key => $val) {
        if($key == 'status') {
            if($objectline->element == 'userformation'){
                $search[$key] =  implode(',', array($objectline::STATUS_VALIDE, $objectline::STATUS_A_PROGRAMMER, $objectline::STATUS_REPROGRAMMEE, $objectline::STATUS_PROGRAMMEE, $objectline::STATUS_EXPIREE));
            }
            elseif($objectline->element == 'userhabilitation'){
                $search[$key] =  implode(',', array($objectline::STATUS_NONHABILITE, $objectline::STATUS_HABILITABLE, $objectline::STATUS_HABILITE));
            }
            elseif($objectline->element == 'userautorisation'){
                $search[$key] =  implode(',', array($objectline::STATUS_AUTORISABLE, $objectline::STATUS_AUTORISE, $objectline::STATUS_NONAUTORISE));
            }
            continue;
        }
        elseif($object->element == 'user' && $key == 'fk_user'){
            continue;
        }
        elseif($object->element == 'formation' && $key == 'fk_formation'){
            continue;
        }
        elseif($object->element == 'habilitation' && $key == 'fk_habilitation'){
            continue;
        }
        elseif($object->element == 'autorisation' && $key == 'fk_autorisation'){
            continue;
        }

        $search[$key] = '';
        if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
            $search[$key.'_dtstart'] = '';
            $search[$key.'_dtend'] = '';
        }
    }
    $toselect = array();
    $search_array_options = array();
}
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
    || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
    $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
}

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Mass actions
if($objectline->element == 'userformation'){
    $objectclass = 'UserFormation';
    $objectlabel = 'UserFormation';
}
elseif($objectline->element == 'userhabilitation'){
    $objectclass = 'UserHabilitation';
    $objectlabel = 'UserHabilitation';
}
elseif($objectline->element == 'userautorisation'){
    $objectclass = 'UserAutorisation';
    $objectlabel = 'UserAutorisation';
}
$uploaddir = $conf->formationhabilitation->dir_output;
include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/actions_massactions.inc.php';


/*
 * View
 */

// List of mass actions available
$arrayofmassactions = array(
    //'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
    //'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
    //'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
    //'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if ($permissiontoaddline) {
    $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
    $arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$arrayofselected = is_array($toselect) ? $toselect : array();

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords FROM '.MAIN_DB_PREFIX.$objectline->table_element;

    // Manage filter
    $sqlforcount .= " WHERE 1 = 1";

    $sqlwhere = array();
    if (count($search) > 0) {
        foreach ($search as $key => $value) {
            if($value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key." = ".((int) $value);
                } elseif (in_array($objectline->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
                    $sqlwhere[] = $key." = '".$db->idate($value)."'";
                } elseif (preg_match('/(_dtstart|_dtend)$/', $key)) {
                    $columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
                    if (preg_match('/^(date|timestamp|datetime)/', $objectline->fields[$columnName]['type'])) {
                        if (preg_match('/_dtstart$/', $key)) {
                            $sqlwhere[] = $db->escape($columnName)." >= '".$db->idate($value)."'";
                        }
                        if (preg_match('/_dtend$/', $key)) {
                            $sqlwhere[] = $db->escape($columnName)." <= '".$db->idate($value)."'";
                        }
                    }
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } elseif (strpos($value, '%') === false) {
                    $sqlwhere[] = $key." IN (".$db->sanitize($db->escape($value)).")";
                } else {
                    $sqlwhere[] = $key." LIKE '%".$db->escape($value)."%'";
                }
            }
        }
    }
    if (count($sqlwhere) > 0) {
        $sqlforcount .= " AND (".implode(" AND ", $sqlwhere).")";
    }

    $resql = $db->query($sqlforcount);
    $objforcount = $db->fetch_object($resql);
    $nbtotalofrecords = $objforcount->nbtotalofrecords;
    if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
        $page = 0;
        $offset = 0;
    }
    $db->free($resql);
}


// Show lines
$result = $objectparentline->getLinesArray();

// Formulaire pour créer une ligne. Il est avant le contenu car impossible de mettre un form dans un autre form => Permet de gérer la recherche et la création sur la même page
print '<form name="addline" id="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
<input type="hidden" name="token" value="' . newToken().'">
<input type="hidden" name="mode" value="">
<input type="hidden" name="page_y" value="">
<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">
<input type="hidden" name="id" value="' . $object->id.'">';
if(!empty($onglet)) {
    print '<input type="hidden" name="onglet" value="' .$onglet.'">';
}
if($action == 'editline') {
    print '<input type="hidden" name="action" value="updateline">';
}
elseif($action == 'edit_datefinvalidite') {
    print '<input type="hidden" name="action" value="updatedatefinvalidite">';
}
elseif($action == 'edit_coutpedagogique') {
    print '<input type="hidden" name="action" value="updatecoutpedagogique">';
}
elseif($action == 'edit_coutmobilisation') {
    print '<input type="hidden" name="action" value="updatecoutmobilisation">';
}
else {
    print '<input type="hidden" name="action" value="addline">';
}

// Confirmation
if ($action == 'addline' && $objectparentline->element == 'formation') {
    $param = '';
    $param .= (GETPOST('fk_formation') ? '&fk_formation='.urlencode(GETPOST('fk_formation')) : '');
    $param .= (GETPOST('fk_user') ? '&fk_user='.urlencode(GETPOST('fk_user')) : '');
    $param .= (GETPOST('date_debut_formationmonth') ? '&date_debut_formationmonth='.urlencode(GETPOST('date_debut_formationmonth')) : '');
    $param .= (GETPOST('date_debut_formationday') ? '&date_debut_formationday='.urlencode(GETPOST('date_debut_formationday')) : '');
    $param .= (GETPOST('date_debut_formationyear') ? '&date_debut_formationyear='.urlencode(GETPOST('date_debut_formationyear')) : '');
    $param .= (GETPOST('date_fin_formationmonth') ? '&date_fin_formationmonth='.urlencode(GETPOST('date_fin_formationmonth')) : '');
    $param .= (GETPOST('date_fin_formationday') ? '&date_fin_formationday='.urlencode(GETPOST('date_fin_formationday')) : '');
    $param .= (GETPOST('date_fin_formationyear') ? '&date_fin_formationyear='.urlencode(GETPOST('date_fin_formationyear')) : '');
    $param .= (GETPOST('nombre_heurehour') ? '&nombre_heurehour='.urlencode(GETPOST('nombre_heurehour')) : '');
    $param .= (GETPOST('nombre_heuremin') ? '&nombre_heuremin='.urlencode(GETPOST('nombre_heuremin')) : '');
    $param .= (GETPOST('fk_societe') ? '&fk_societe='.urlencode(GETPOST('fk_societe')) : '');
    $param .= (GETPOST('status') ? '&status='.urlencode(GETPOST('status')) : '');
    $param .= (GETPOST('interne_externe') ? '&interne_externe='.urlencode(GETPOST('interne_externe')) : '');
    $param .= (GETPOST('formateur') ? '&formateur='.urlencode(GETPOST('formateur')) : '');
    $param .= (GETPOST('numero_certificat') ? '&numero_certificat='.urlencode(GETPOST('numero_certificat')) : '');
    $param .= (GETPOST('resultat') ? '&resultat='.urlencode(GETPOST('resultat')) : '');

    if(GETPOST('fk_formation') > 0 && GETPOST('fk_user') > 0 && GETPOST('status') == $objectline::STATUS_VALIDE) { // Formation inferieur
        $formationToClose = $objectparentline->getFormationToClose(GETPOST('fk_user'), GETPOST('fk_formation'));
        $txt_formationToClose = '';
		foreach($formationToClose as $idformation => $refformation) {
            $txt_formationToClose .= $refformation.', ';
        }
        $txt_formationToClose = rtrim($txt_formationToClose, ', ');
    }

    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet='.$onglet.$param, $langs->trans('AddLine'), (!empty($txt_formationToClose) ? $langs->trans('ConfirmAddLine2', $txt_formationToClose) : $langs->trans('ConfirmAddLine')), 'confirm_addline', '', 0, 1);
    print $formconfirm;
}

print "</form>\n";

// Formulaire pour la recherche
print '	<form name="searchline" id="searchline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
<input type="hidden" name="token" value="' . newToken().'">
<input type="hidden" name="mode" value="">
<input type="hidden" name="page_y" value="">
<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">
<input type="hidden" name="id" value="' . $object->id.'">';
if(!empty($onglet)) {
    print '<input type="hidden" name="onglet" value="' .$onglet.'">';
}

// Add code for pre mass action (confirmation or email presend form)
if($objectline->element == 'userformation'){
    $topicmail = "SendUserFormationRef";
    $modelmail = "UserFormation";
    $objecttmp = new UserFormation($db);
}
elseif($objectline->element == 'userhabilitation'){
    $topicmail = "SendUserHabilitationRef";
    $modelmail = "UserHabilitation";
    $objecttmp = new UserHabilitation($db);
}
elseif($objectline->element == 'userautorisation'){
    $topicmail = "SendUserAutorisationRef";
    $modelmail = "UserAutorisation";
    $objecttmp = new UserAutorisation($db);
}
$trackid = 'xxxx'.$object->id;
include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/massactions_pre.tpl.php';

$title = $langs->trans('ListOfs', $langs->transnoentitiesnoconv("UserFormation"));
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, sizeof($objectparentline->lines), $nbtotalofrecords, $objectline->picto, 0, '', '', $limit, 0, 0, 1);

print '<div class="div-table-responsive-no-min" style="'.($css_div ? $css_div : '').'">';

print '<table id="tablelinesaddline" class="noborder noshadow" width="100%">';
// Form to add new line
if ($permissiontoaddline && $action != 'selectlines' && $object->status == 1) {
    if ($action != 'editline') {
        // Add products/services form
        $parameters = array();
        $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $objectparentline, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        if (empty($reshook)){
            $objectparentline->formAddObjectLine(1, $mysoc, $soc, '/custom/formationhabilitation/core/tpl').'<br>';
        }
    }
}
print '</table>';

// if (!empty($objectparentline->lines)) {
    print '<table id="tablelines" class="noborder noshadow" width="100%">';

    print "<thead>";
        include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline_filter.tpl.php';
    print "</thead>\n";

    if (!empty($conf->use_javascript_ajax)) {
        include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
    }
    
    if (!empty($objectparentline->lines)) {
        $nbline = 0;
        $objectparentline->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/custom/formationhabilitation/core/tpl');
    }

    // If no record found
    if (sizeof($objectparentline->lines) == 0) {
        $colspan = 1;
        foreach ($arrayfields as $key => $val) {
            if (!empty($val['checked'])) {
                $colspan++;
            }
        }
        print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
    }

    print '</table>';
// }

print '</div>';
print "</form>\n";