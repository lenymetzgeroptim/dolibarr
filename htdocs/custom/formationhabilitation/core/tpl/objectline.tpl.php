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

// Load variable for pagination
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

 // Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml');
foreach ($objectline->fields as $key => $val) {
	if($key == 'status') {
        if(!empty(GETPOST('search_'.$key, 'alpha'))) {
            $search[$key] = implode(',', GETPOST('search_'.$key, 'alpha'));
        }
        else {
            if($objectline->element == 'userformation'){
                $search[$key] =  implode(',', array($objectline::STATUS_VALIDE, $objectline::STATUS_A_PROGRAMMER, $objectline::STATUS_PROGRAMMEE, $objectline::STATUS_EXPIREE));
            }
            elseif($objectline->element == 'userhabilitation'){
                $search[$key] =  implode(',', array($objectline::STATUS_NONHABILITE, $objectline::STATUS_HABILITABLE, $objectline::STATUS_HABILITE));
            }
            elseif($objectline->element == 'userautorisation'){
                $search[$key] =  implode(',', array($objectline::STATUS_AUTORISABLE, $objectline::STATUS_AUTORISE, $objectline::STATUS_NONAUTORISE));
            }
        }
    }
    elseif (GETPOST('search_'.$key, 'alpha') !== '') {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
	elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
		$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, GETPOST('search_'.$key.'_dtstartmonth', 'int'), GETPOST('search_'.$key.'_dtstartday', 'int'), GETPOST('search_'.$key.'_dtstartyear', 'int'));
		$search[$key.'_dtend'] = dol_mktime(23, 59, 59, GETPOST('search_'.$key.'_dtendmonth', 'int'), GETPOST('search_'.$key.'_dtendday', 'int'), GETPOST('search_'.$key.'_dtendyear', 'int'));
	}
}

foreach($objectline as $key => $val) {
	if ($key == 'status' && $filters[$key] == -1) {
		$search[$key] = '';
	}
	if ((strpos($objectline->fields[$key]['type'], 'integer:') === 0) || (strpos($objectline->fields[$key]['type'], 'sellist:') === 0) || !empty($objectline->fields[$key]['arrayofkeyval'])) {
		if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($objectline->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $objectline->fields[$key]['arrayofkeyval'])))) {
			$search[$key] = '';
		}
	}
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($objectline->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['t.'.$key] = $val['label'];
	}
}

foreach($objectline as $key => $val) {
    if ($key == 'status' && $search[$key] == -1) {
        unset($search[$key]);
    }
    if ((strpos($objectline->fields[$key]['type'], 'integer:') === 0) || (strpos($objectline->fields[$key]['type'], 'sellist:') === 0) || !empty($objectline->fields[$key]['arrayofkeyval'])) {
        if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($objectline->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $objectline->fields[$key]['arrayofkeyval'])))) {
            unset($search[$key]);
        }
    }
}

// Definition of array of fields for columns
$arrayfields = array();
foreach ($objectline->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1);
		$arrayfields['t.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>($visible != 3 && dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=> isset($val['help']) ? $val['help'] : ''
		);
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

// Param
$param = '';
if (!empty($object->id)) {
	$param .= '&id='.urlencode($object->id);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) {
		foreach ($search[$key] as $skey) {
			if ($skey != '') {
				$param .= '&search_'.$key.'[]='.urlencode($skey);
			}
		}
	} elseif ($search[$key] != '') {
		$param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

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
    if($object->element == 'formation'){
        $sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords FROM '.MAIN_DB_PREFIX.'formationhabilitation_userformation WHERE fk_formation = '.$object->id;
    }
    elseif($object->element == 'habilitation'){
        $sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords FROM '.MAIN_DB_PREFIX.'formationhabilitation_userhabilitation WHERE fk_habilitation = '.$object->id;
    }
    elseif($object->element == 'autorisation'){
        $sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords FROM '.MAIN_DB_PREFIX.'formationhabilitation_userautorisation WHERE fk_autorisation = '.$object->id;
    }
    elseif($object->element == 'user'){
        if($objectline->element == 'userformation'){
            $sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords FROM '.MAIN_DB_PREFIX.'formationhabilitation_userformation WHERE fk_user = '.$object->id;
        }
        elseif($objectline->element == 'userhabilitation'){
            $sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords FROM '.MAIN_DB_PREFIX.'formationhabilitation_userhabilitation WHERE fk_user = '.$object->id;
        }
        elseif($objectline->element == 'userautorisation'){
            $sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords FROM '.MAIN_DB_PREFIX.'formationhabilitation_userautorisation WHERE fk_user = '.$object->id;
        }
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
<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">
<input type="hidden" name="id" value="' . $object->id.'">';
if(!empty($onglet)) {
    print '<input type="hidden" name="onglet" value="' .$onglet.'">';
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
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, sizeof($objectparentline->lines), $nbtotalofrecords, 'fa-graduation-cap_fas_#1f3d89', 0, '', '', $limit, 0, 0, 1);

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