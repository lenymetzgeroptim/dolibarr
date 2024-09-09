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
        if(!empty(GETPOST('search_'.$key, 'alpha')) && gettype(GETPOST('search_'.$key, 'alpha')) == "array") {
            $search[$key] = implode(',', GETPOST('search_'.$key, 'alpha'));
        }
        elseif(!empty(GETPOST('search_'.$key, 'alpha'))) {
            $search[$key] = GETPOST('search_'.$key, 'alpha');
        }
        else {
            if($objectline->element == 'userformation'){
                $search[$key] =  implode(',', array($objectline::STATUS_VALIDE, $objectline::STATUS_A_PROGRAMMER, $objectline::STATUS_REPROGRAMMEE, $objectline::STATUS_PROGRAMMEE, $objectline::STATUS_EXPIREE));
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
	elseif (GETPOST('search_'.$key.'_dtstart', 'alpha') !== '' && empty(GETPOST('search_'.$key.'_dtstartday', 'alpha'))) {
		$search[$key.'_dtstart'] = GETPOST('search_'.$key.'_dtstart', 'alpha');
	}
	elseif (GETPOST('search_'.$key.'_dtend', 'alpha') !== '' && empty(GETPOST('search_'.$key.'_dtendday', 'alpha'))) {
		$search[$key.'_dtend'] = GETPOST('search_'.$key.'_dtend', 'alpha');
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
if (!empty($onglet)) {
	$param .= '&onglet='.urlencode($onglet);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if(!empty(GETPOST('sortfield', 'aZ09comma'))) {
	$param .= '&sortfield='.urlencode($sortfield);
}
if(!empty(GETPOST('sortorder', 'aZ09comma'))) {
	$param .= '&sortorder='.urlencode($sortorder);
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
