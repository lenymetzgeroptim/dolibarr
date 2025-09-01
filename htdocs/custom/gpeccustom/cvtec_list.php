
<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
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

/**
 *   	\file       cvtec_list.php
 *		\ingroup    gpeccustom
 *		\brief      List page for cvtec
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("MAIN_SECURITY_FORCECSP"))   define('MAIN_SECURITY_FORCECSP', 'none');	// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification
//if (! defined('NOSESSION'))                define('NOSESSION', '1');					// On CLI mode, no need to use web sessions

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/position.class.php';
// require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/ajax/getcvtec_data.php';
// include DOL_DOCUMENT_ROOT.'/custom/gpeccustom/js/filter_cv_script.js';
// Inclure le fichier JavaScript
// print '<script type="text/javascript" src="/custom/gpeccustom/js/filter_cv_script.js"></script>';
// load module libraries
require_once __DIR__.'/class/cvtec.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("gpeccustom@gpeccustom", "other"));

$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'create'/'add', 'edit'/'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : 1000;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

$arr_skill = GETPOST('arr_skill');
if(!empty($arr_skill)) {
	foreach($arr_skill as $id_skill) {
		$arr_level[$id_skill] = GETPOST('arr_level_'.$id_skill.'');
	}
}


if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new CVTec($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->gpeccustom->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($contextpage)); 	// Note that conf->hooks_modules contains array of activated contexes

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

//used in extrafields_list_print_filds in custom core
$userevaluated = $object->getEvaluation('user_evaluated');
$userscvtec = $object->getUserJobs();


$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	reset($object->fields);					// Reset is required to avoid key() to return null.
	$sortfield = "t.".key($object->fields); // Set here default search field. By default 1st field in definition.
}
if (!$sortorder) {
	$sortorder = "ASC";
}

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha') !== '') {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
	if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
		$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, GETPOST('search_'.$key.'_dtstartmonth', 'int'), GETPOST('search_'.$key.'_dtstartday', 'int'), GETPOST('search_'.$key.'_dtstartyear', 'int'));
		$search[$key.'_dtend'] = dol_mktime(23, 59, 59, GETPOST('search_'.$key.'_dtendmonth', 'int'), GETPOST('search_'.$key.'_dtendday', 'int'), GETPOST('search_'.$key.'_dtendyear', 'int'));
	}
}

// List of fields to search into when doing a "search in all"
// $fieldstosearchall = array();
// foreach ($object->fields as $key => $val) {
// 	if (!empty($val['searchall'])) {
// 		$fieldstosearchall['t.'.$key] = $val['label'];
// 	}
// }
// $parameters = array('fieldstosearchall'=>$fieldstosearchall);
// $reshook = $hookmanager->executeHooks('completeFieldsToSearchAll', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
// if ($reshook > 0) {
// 	$fieldstosearchall = empty($hookmanager->resArray['fieldstosearchall']) ? array() : $hookmanager->resArray['fieldstosearchall'];
// } elseif ($reshook == 0) {
// 	$fieldstosearchall = array_merge($fieldstosearchall, empty($hookmanager->resArray['fieldstosearchall']) ? array() : $hookmanager->resArray['fieldstosearchall']);
// }

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1);
		$arrayfields['t.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>(abs($visible) != 3 && dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=> isset($val['help']) ? $val['help'] : ''
		);
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
//$arrayfields['anotherfield'] = array('type'=>'integer', 'label'=>'AnotherField', 'checked'=>1, 'enabled'=>1, 'position'=>90, 'csslist'=>'right');
$arrayfields = dol_sort_array($arrayfields, 'position');

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('gpeccustom', 'cvtec', 'read');
	$permissiontoadd = $user->hasRight('gpeccustom', 'cvtec', 'write');
	$permissiontodelete = $user->hasRight('gpeccustom', 'cvtec', 'delete');
} else {
	$permissiontoread = $user->hasRight('gpeccustom', 'cvtec', 'read');
	$permissiontoadd = 1;
	$permissiontodelete = 1;
}

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) accessforbidden();
//$socid = 0; if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, 0, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("gpeccustom")) {
	accessforbidden('Module gpeccustom not enabled');
}
if (!$permissiontoread) accessforbidden();


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

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($object->fields as $key => $val) {
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

	// Mass actions
	$objectclass = 'CVTec';
	$objectlabel = 'CVTec';
	$uploaddir = $conf->gpeccustom->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	// You can add more action here
	// if ($action == 'xxx' && $permissiontoxxx) ...
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

$title = $langs->trans("CVTec");
//$help_url = "EN:Module_CVTec|FR:Module_CVTec_FR|ES:Módulo_CVTec";
$help_url = '';
$morejs = array();
$morecss = array();

//get data to filter
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php'; 

//for filtring
// $arr_job = GETPOST('arr_job');
// $arr_skill = GETPOST('arr_skill');

// Supposons que vous receviez des sélections de jobs et de compétences via POST
$selectedJobs = GETPOST('jobs');
$selectedSkills2 = GETPOST('skills');
$selectedLevels = GETPOST('levels');

if(!empty($arr_skill)) {
	foreach($arr_skill as $id_skill) {
		$arr_level[$id_skill] = GETPOST('arr_level_'.$id_skill.'');
	}
}

// start code for filtring (custom)
// $cv = new CVTec($db);
// header('Content-Type: application/json');
//list of object used in drop-down list in filter bloc bofore filtring
// $listofskill = $object->getSkillsRank($arr_skill, 'all');
// $skillusers = $object->getSkillsRank($arr_skill, 'skill_users');
// $listofjobskills = $object->getJobSkills($arr_job, $arr_skill, 'all');
$jobuserskills = $object->getJobSkills(array_filter($arr_job), array_filter($arr_skill), 'job_user_skills');
// $filtredskills = $object->getJobSkills($arr_job, $arr_skill, 'skills_after_filter');


// test for integrate js ajax in dolibarr
// if($user->id == 412) {
// 	var_dump($jobuserskills);
// }
// Encode PHP array into JSON
// $json_jobskills = json_encode($jobuserskills);

// Pass the JSON-encoded PHP data to JavaScript
// Retrieve filter values from GET request
// $skillFilter = GETPOST('skillFilter');
// $levelFilter = GETPOST('levelFilter');
// $langs->addJsFile("/custom/gpeccustom/js/filter_cv_script.js");


// print '<script>
// 	let jobskills = ' . $json_jobskills . ';
// </script>';



//list of skill label in drop down list
// foreach($listofskill as $key => $val) {
// 	$arrskills[$val->skillid] = $val->label;
// 	$allskills[$val->skillid] = $val->label;
// }

//skills with levels to display after filter
// if(count($skillusers) > 0) {
// 	foreach($skillusers as $key => $val) {
// 		$arr[$val->skillid][$val->rankorder] = $val->rankorder.'-'.$val->skill_level;
// 	}
// }

//list of jobs label in drop down list
// if(count($listofjobskills) > 0) {
// 	foreach($listofjobskills as $key => $val) {
// 		$arrjobs[$val->fk_job] = $val->job_label;
// 	}
// }

//job's skill and levels to display after filter
// if(count($jobuserskills) > 0) {
// 	foreach($jobuserskills as $key => $val) {
// 		// $arrjobskills[] = $val->fk_job.'_'.$val->skillid.'_'.$val->rankorder.' - '.$val->skill_level;
// 		$arrjs[$val->fk_job][$val->skillid][$val->rankorder] = $val->rankorder.' - '.$val->skill_level;
// 	}
// }

//job's skills in drop down list after filtred (skils by job)
// if(count($filtredskills) > 0) {
// 	foreach($filtredskills as $key => $val) {
// 		//get job by skills (in filter)
// 		$newjobskills[$val->fk_job] = $val->job_label;
// 	}
// }

// $arrjs = array_filter($arrjs);

//data of users used to filter
// if(!empty(array_filter($arr_job)) || !empty(array_filter($arr_skill))|| !empty(array_filter($arr_level))) {
// 	$searchUserArray = $object->getFiltredCVData($arr_job, $arr_skill, $arr_level);
// 	$searchUser = array_filter(array_keys($searchUserArray));
// }

if(!empty(array_filter($selectedJobs)) || !empty(array_filter($selectedSkills2))|| !empty(array_filter($selectedLevels))) {
	$searchUserArray = $object->getFiltredCVData(array_filter($selectedJobs), array_filter($selectedSkills2), array_filter($selectedLevels));
	$searchUser = array_filter(array_keys($searchUserArray));
}



// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
$sql .= $object->getFieldList('t');
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);
//$sql .= ", COUNT(rc.rowid) as anotherfield";

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."anothertable as rc ON rc.parent = t.rowid";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
}
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
	$sql .= " WHERE t.entity IN (".getEntity($object->element, (GETPOST('search_current_entity', 'int') ? 0 : 1)).")";
} else {
	$sql .= " WHERE 1 = 1";
}

//data of users used to filter if cvtec user is in search array
// if((!empty($arr_skill) || !empty($arr_job)) && !is_null($searchUser)) {
// 	$sql .=" AND t.fk_user IN (".implode(',', $searchUser).")";
// }

if(!is_null($searchUser)) {
	$sql .=" AND t.fk_user IN (".implode(',', $searchUser).")";
}

// foreach ($search as $key => $val) {
// 	if (array_key_exists($key, $object->fields)) {
// 		if ($key == 'status' && $search[$key] == -1) {
// 			continue;
// 		}
// 		$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
// 		if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
// 			if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
// 				$search[$key] = '';
// 			}
// 			$mode_search = 2;
// 		}
// 		if ($search[$key] != '') {
// 			$sql .= natural_search("t.".$db->escape($key), $search[$key], (($key == 'status') ? 2 : $mode_search));
// 		}
// 	} else {
// 		if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
// 			$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
// 			if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
// 				if (preg_match('/_dtstart$/', $key)) {
// 					$sql .= " AND t.".$db->escape($columnName)." >= '".$db->idate($search[$key])."'";
// 				}
// 				if (preg_match('/_dtend$/', $key)) {
// 					$sql .= " AND t.".$db->escape($columnName)." <= '".$db->idate($search[$key])."'";
// 				}
// 			}
// 		}
// 	}
// }
// if ($search_all) {
// 	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
// }
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

/* If a group by is required
$sql .= " GROUP BY ";
foreach($object->fields as $key => $val) {
	$sql .= "t.".$db->escape($key).", ";
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
	}
}
// Add groupby from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);
*/

// Add HAVING from hooks
/*
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= empty($hookmanager->resPrint) ? "" : " HAVING 1=1 ".$hookmanager->resPrint;
*/

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);


// Direct jump if only one record found
if ($num == 1 && getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/gpeccustom/cvtec_card.php', 1).'?id='.((int) $id));
	exit;
}


// Output page
// --------------------------------------------------------------------
$morejs = array('');
llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll
include DOL_DOCUMENT_ROOT.'/custom/gpeccustom/view/cv.filter.php';
header('Content-Type: text/html'); 

?>
<script type="text/javascript">
// Get unique elements from an array based on a key
function getUniqueItems(array, key, label) {
    const unique = new Map();
    array.forEach(item => {
        if (!unique.has(item[key])) {
            unique.set(item[key], { [key]: item[key], [label]: item[label] });
        }
    });
    return Array.from(unique.values());
}

// Restore previous selections in a select element using Select2
function restoreSelection(selectElement, selectedValues) {
    $(selectElement).val(selectedValues).trigger('change.select2', { manual: true });
}

// Initialize Select2 for job and skill dropdowns
function initializeSelect2() {
    $("#jobFilter, #skillFilter").select2({
        width: '40%',
        placeholder: function() { return $(this).attr('placeholder'); },
        allowClear: true
    });
}

// Store selected levels for each skill in a global variable
let skillLevels = {};

// Populate the job dropdown with unique job options
function populateJobFilter() {
    const jobFilter = $('#jobFilter');
    const selectedJobs = jobFilter.val() || [];

    const fragment = document.createDocumentFragment();
    const uniqueJobs = getUniqueItems(window.jobskills, 'fk_job', 'job_label');

    // Add each job as an option in the dropdown
    uniqueJobs.forEach(job => {
        let option = document.createElement('option');
        option.value = job.fk_job;
        option.textContent = job.job_label;
        fragment.appendChild(option);
    });

    // Reset the job dropdown and restore the previous selection
    jobFilter.empty().append(fragment);
    restoreSelection('#jobFilter', selectedJobs);

    // Filter the skills based on selected jobs
    filterSkills();
}

// Filter skills based on selected jobs and update the dropdown
function filterSkills() {
    const selectedJobs = $('#jobFilter').val() || [];
    const skillFilter = $('#skillFilter');
    const selectedSkills = skillFilter.val() || [];

    const fragment = document.createDocumentFragment();
    const filteredSkills = selectedJobs.length === 0 
        ? window.jobskills 
        : window.jobskills.filter(item => item.fk_job && selectedJobs.includes(item.fk_job));

    // Get unique skills and add them as options in the skill dropdown
    const uniqueSkills = getUniqueItems(filteredSkills, 'skillid', 'skill_label');
    uniqueSkills.forEach(skill => {
        let option = document.createElement('option');
        option.value = skill.skillid;
        option.textContent = skill.skill_label;
        fragment.appendChild(option);
    });

    // Reset the skill dropdown and restore the previous selection
    skillFilter.empty().append(fragment);
    restoreSelection('#skillFilter', selectedSkills);

    // Update the level section based on selected skills
    toggleLevelSection();
}

// Show or hide the level section based on selected skills
function toggleLevelSection() {
    const selectedSkills = $('#skillFilter').val() || [];
	console.log(selectedSkills.length);
    if (selectedSkills.length > 0) {
        $('#levelSection').show();  // Show level section if skills are selected
        updateLevelDropdowns(selectedSkills);  // Update level dropdowns for selected skills
    } else {
        $('#levelSection').hide();  // Hide level section if no skills are selected
    }
}

// Dynamically create level dropdowns for each selected skill
function updateLevelDropdowns(selectedSkills) {
    const levelSection = $('#levelSection');
    levelSection.empty();  // Clear the level section before updating

    selectedSkills.forEach((skillId, index) => {
        const skill = window.jobskills.find(item => item.skillid === skillId);

        if (skill) {
            // Create a container for each skill and its level dropdown
            const skillContainer = $('<div>').addClass('skill-level-container').css({
                display: 'flex',
                alignItems: 'center',
                width: '42%',
                float: index % 2 === 0 ? 'left' : 'right',
                marginBottom: '10px'
            });

            // Add skill label
            skillContainer.append(`<span class="fas fa-shapes pictofixedwidth"></span> &nbsp;<label data-skill-id="${skill.skillid}">${skill.skill_label}</label>`);

            // Create level dropdown
            const selectLevel = $('<select>').addClass('levelFilter').css({
                width: '50%',
                marginLeft: '10px'
            });

            // Add default option for selecting a level
            const defaultOption = $('<option>')
                .val('-1')
                .text('Sélectionner un niveau')
                .addClass('warning-option'); // Apply class for default option

            selectLevel.append(defaultOption);

            // Add other options
            const uniqueLevels = getUniqueItems(window.jobskills.filter(item => item.skillid === skillId), 'rankorder', 'skill_level');
            uniqueLevels.forEach(level => {
                selectLevel.append(`<option value="${level.rankorder}">${level.rankorder} - ${level.skill_level}</option>`);
            });
			
		
            // Restore previously selected level if available
            if (skillLevels[skillId]) {
                selectLevel.val(skillLevels[skillId]);
            }

			

            skillContainer.append(selectLevel);
            levelSection.append(skillContainer);

            // Add event listener for level changes and store the selected level
            selectLevel.on('change', function () {
                skillLevels[skillId] = $(this).val();  // Update skillLevels when a level is changed
                updateResults();  // Update results when level is changed
            });
        }
    });

    // Clear float to avoid layout issues
    levelSection.append('<div style="clear: both;"></div>');
    levelSection.append('<hr>');
}


// Filter jobs based on selected skills and update the job dropdown
function filterJobs() {
    const selectedSkills = $('#skillFilter').val() || [];
    const jobFilter = $('#jobFilter');
    const selectedJobs = jobFilter.val() || [];

    const fragment = document.createDocumentFragment();

    // Filter jobs based on selected skills
    const filteredJobs = selectedSkills.length === 0 ? window.jobskills : window.jobskills.filter(item => selectedSkills.includes(item.skillid));

    // Get unique jobs and add them as options
    const uniqueJobs = getUniqueItems(filteredJobs, 'fk_job', 'job_label');
    uniqueJobs.forEach(job => {
        let option = document.createElement('option');
        option.value = job.fk_job;
        option.textContent = job.job_label;
        fragment.appendChild(option);
    });

    // Reset the job dropdown and restore previous selection
    jobFilter.empty().append(fragment);
    restoreSelection('#jobFilter', selectedJobs);

    // Update the level dropdowns
    updateLevelDropdowns(selectedSkills);
}
// Met à jour les compteurs de job, skill et level
function updateCounters() {
    const selectedJobs = $('#jobFilter').val() || [];
    const selectedSkills = $('#skillFilter').val() || [];
    
    // Compter les niveaux où le rankorder est supérieur à 0
    const selectedLevels = Object.values(skillLevels).filter(level => level > 0);

    // Mettre à jour les compteurs directement dans les span avec les icônes
    $('#jobCount').text(selectedJobs.length);
	// if (selectedJobs.length > 0) {
    //     $('#jobCount').text(selectedJobs.length);
    // } else {
    //     $('#jobCount').text(''); // Laisser vide ou vous pouvez cacher l'élément avec .hide()
    // }
    $('#skillCount').text(selectedSkills.length);
    $('#levelCount').text(selectedLevels.length);
}
// Fonction pour afficher l'indicateur de chargement
function showLoadingIndicator() {
    // Trouver le premier span ayant la classe 'opacitymedium' à l'intérieur de la div 'titre inline-block'
    const existingSpan = $('.titre.inline-block .opacitymedium');

    // Si l'indicateur de chargement n'existe pas encore, on l'ajoute après le premier span
    if (!$('#loadingIndicator').length) {
        const loadingSpan = $('<span id="loadingIndicator" class="loading-indicator">    <span class="fas fa-spinner fa-lg"></span></span>');
        existingSpan.after(loadingSpan); // Insérer après le premier span
    }
}

// Fonction pour masquer l'indicateur de chargement après le chargement
function hideLoadingIndicator() {
    $('#loadingIndicator').remove(); // Supprime le span d'indicateur de chargement
}

// Fonction pour mettre à jour l'objet skillLevelsArray basé sur les compétences sélectionnées
function updateSkillLevels(selectedSkills) {
    // Supprimer les niveaux de compétence pour les compétences qui ne sont plus sélectionnées
    Object.keys(skillLevels).forEach(skillId => {
        if (!selectedSkills.includes(skillId)) {
            delete skillLevels[skillId]; // Supprimer les compétences non sélectionnées
        }
    });

    // Convertir les niveaux de compétence en tableau pour l'envoi AJAX
    const skillLevelsArray = Object.keys(skillLevels).reduce((acc, skillId) => {
        acc[skillId] = skillLevels[skillId];
        return acc;
    }, {});

    console.log('Skill levels array:', skillLevelsArray); // Vérifiez les niveaux de compétence
    return skillLevelsArray;
}

// Mettre à jour les résultats en fonction des compétences et niveaux sélectionnés
function updateResults() {
    const selectedJobs = $('#jobFilter').val() || [];
    const selectedSkills = $('#skillFilter').val() || [];

    // Mettre à jour l'objet skillLevelsArray en fonction des compétences sélectionnées
    const skillLevelsArray = updateSkillLevels(selectedSkills);
	
	
    // Afficher l'indicateur de chargement
    showLoadingIndicator();
	
    $.ajax({
        url: '/custom/gpeccustom/cvtec_list.php?idmenu=89399&mainmenu=gpeccustom&leftmenu=',
        type: 'POST',
		// cache: false,
        data: {
            jobs: selectedJobs,
            skills: selectedSkills,
            levels: skillLevelsArray,
			// _: new Date().getTime()  
        },
        success: function(data) {
            console.log('Result response:', data);
            const tempDiv = $('<div>').html(data);
            const filteredContent = tempDiv.find('#searchFormList').html();
            $('#searchFormList').html(filteredContent);

            populateJobFilter();
            filterSkills();
            filterJobs();
            updateLevelDropdowns(selectedSkills);

            // Mettre à jour les compteurs après chaque changement de sélection
            updateCounters();
        },
        error: function(xhr, status, error) {
            console.error('An error occurred: ' + error);
        },
        complete: function() {
            // Cacher l'indicateur de chargement une fois la requête terminée
            hideLoadingIndicator();
        }
    });
}

// Initialize the page and add event listeners
function initializePage() {
    initializeSelect2();
    fetchJobSkills();  // Fetch job skills data via AJAX

    $('#jobFilter').on('change', function() {
        filterSkills();
        updateResults();
    });

    $('#skillFilter').on('change', function() {
        toggleLevelSection();
        filterJobs();
        updateResults();
    });
}

// Call initializePage when the document is ready
$(document).ready(initializePage);
</script>
<?php
// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// foreach ($search as $key => $val) {
// 	if (is_array($search[$key])) {
// 		foreach ($search[$key] as $skey) {
// 			if ($skey != '') {
// 				$param .= '&search_'.$key.'[]='.urlencode($skey);
// 			}
// 		}
// 	} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
// 		$param .= '&search_'.$key.'month='.((int) GETPOST('search_'.$key.'month', 'int'));
// 		$param .= '&search_'.$key.'day='.((int) GETPOST('search_'.$key.'day', 'int'));
// 		$param .= '&search_'.$key.'year='.((int) GETPOST('search_'.$key.'year', 'int'));
// 	} elseif ($search[$key] != '') {
// 		$param .= '&search_'.$key.'='.urlencode($search[$key]);
// 	}
// }
// // Add $param from extra fields
// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
if($user->id == '412' || $user->id == '1') {
	$arrayofmassactions = array(
		// 'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
		'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
		// 'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
		//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	);
}


if (!empty($permissiontodelete)) {
	if($user->id == '412' || $user->id == '1') {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
// $newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/gpeccustom/cvtec_card.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// insert filter (recherche avancée)
// include DOL_DOCUMENT_ROOT.'/custom/gpeccustom/view/cv.filter.php';

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendCVTecRef";
$modelmail = "cvtec";
$objecttmp = new CVTec($db);
$trackid = 'xxxx'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

// if ($search_all) {
// 	$setupstring = '';
// 	foreach ($fieldstosearchall as $key => $val) {
// 		$fieldstosearchall[$key] = $langs->trans($val);
// 		$setupstring .= $key."=".$val.";";
// 	}
// 	print '<!-- Search done like if MYOBJECT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
// 	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>'."\n";
// }

$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';*/

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')) : ''); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div id="list_cv" class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
// if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
// 	print '<td class="liste_titre center maxwidthsearch">';
// 	$searchpicto = $form->showFilterButtons('left');
// 	print $searchpicto;
// 	print '</td>';
// }

// foreach ($object->fields as $key => $val) {
// 	$searchkey = empty($search[$key]) ? '' : $search[$key];
// 	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
// 	if ($key == 'status') {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'center';
// 	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'center';
// 	} elseif (in_array($val['type'], array('timestamp'))) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
// 	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('id', 'rowid', 'ref', 'status')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'right';
// 	}
// 	if (!empty($arrayfields['t.'.$key]['checked'])) {
// 		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').($key == 'status' ? ' parentonrightofpage' : '').'">';
// 		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
// 			print $form->selectarray('search_'.$key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100'.($key == 'status' ? ' search_status width100 onrightofpage' : ''), 1);
// 		} elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
// 			print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', $cssforfield.' maxwidth250', 1);
// 		} elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
// 			print '<div class="nowrap">';
// 			print $form->selectDate($search[$key.'_dtstart'] ? $search[$key.'_dtstart'] : '', "search_".$key."_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
// 			print '</div>';
// 			print '<div class="nowrap">';
// 			print $form->selectDate($search[$key.'_dtend'] ? $search[$key.'_dtend'] : '', "search_".$key."_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
// 			print '</div>';
// 		} elseif ($key == 'lang') {
// 			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
// 			$formadmin = new FormAdmin($db);
// 			print $formadmin->select_language($search[$key], 'search_lang', 0, null, 1, 0, 0, 'minwidth150 maxwidth200', 2);
// 		} else {
// 			print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '').'">';
// 		}
// 		print '</td>';
// 	}
// }
// // Extra fields
// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// var_dump($object->fields);
// Fields from hook
// $parameters = array('arrayfields'=>$arrayfields);
// $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
// print $hookmanager->resPrint;
// /*if (!empty($arrayfields['anotherfield']['checked'])) {
// 	print '<td class="liste_titre"></td>';
// }*/
// // Action column
// if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
// 	print '<td class="liste_titre center maxwidthsearch">';
// 	$searchpicto = $form->showFilterButtons();
// 	print $searchpicto;
// 	print '</td>';
// }
// print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
foreach ($object->fields as $key => $val) {
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('id', 'rowid', 'ref', 'status')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
		$cssforfield .= ($cssforfield ? ' ' : '').'right';
	}
	$cssforfield = preg_replace('/small\s*/', '', $cssforfield);	// the 'small' css must not be used for the title label
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''), 0, (empty($val['helplist']) ? '' : $val['helplist']))."\n";
		$totalarray['nbfield']++;
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder, 'totalarray'=>&$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
/*if (!empty($arrayfields['anotherfield']['checked'])) {
	print '<th class="liste_titre right">'.$langs->trans("AnotherField").'</th>';
	$totalarray['nbfield']++;
}*/
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
		if (!is_null($val) && preg_match('/\$object/', $val)) {
			$needToFetchEachLine++; // There is at least one compute field that use $object
		}
	}
}

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;

// require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/modules/pdf/pdf_rat.modules.php';
// $pdf = new pdf_rat($db);
// //$outputlangs = $langs;
// $reportStatic = new TimesheetReport($db);
// require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/TimesheetReport.class.php';
// var_dump($pdf->writeFile($reportStatic, $langs));
// print '<a class="documentdownload paddingright" href="/erp/document.php?modulepart=gpeccustom&amp;file=cvtec%2FCVTEC_55%2FCVTEC_55.pdf&amp;entity=1" title="CVTEC_55.pdf"><i class="fa fa-file-pdf-o paddingright" title="Fichier: CVTEC_55.pdf"></i>CVTEC_55.pdf</a>';
// print '<a class="pictopreview documentpreview" href="/erp/document.php?modulepart=gpeccustom&amp;attachment=0&amp;file=cvtec%2FCVTEC_55%2FCVTEC_55.pdf&amp;entity=1" mime="application/pdf" target="_blank"><span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span></a>';
//  if($massaction == "generate_doc") {
// 	var_dump('ok');
// // 	foreach($toselect as $cv) {
//  	header("Location: ".DOL_URL_ROOT."/document.php?modulepart=gpeccustom&amp;file=cvtec%2FCVTEC_55%2FCVTEC_55.pdf&amp;entity=1");
// 	// return;
//  }
$imaxinloop = ($limit ? min($num, $limit) : $num);

	//get the rest of the jobs that are not rated for extrafields 
	// foreach($userscvtec as $key => $val) {
	// 	$notevaluate[$key] = $val;
	// }


while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	// $obj->options_emploi != null ? $in_users[$obj->fk_user] = $obj->options_emploi : '';
	// var_dump($obj->fk_user.'_'.$obj->options_emploi);

	//diff betwwen evaluated and not evaluated jobs for users for extrafields
	// foreach($userevaluated as $key => $val) {
	// 	if(in_array($key, array_keys($in_users))) {
	// 		$evaluate[$key] = $val;
	// 	}
	// }


	// Store properties in $object
	$object->setVarsFromFetchObj($obj);


	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		$selected = -1;
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		//print $object->getKanbanView('', array('thirdparty'=>$object->thirdparty, 'selected' => $selected));
		print $object->getKanbanView('', array('selected' => $selected));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		foreach ($object->fields as $key => $val) {
			
			$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
			if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
				$cssforfield .= ($cssforfield ? ' ' : '').'center';
			} elseif ($key == 'status') {
				$cssforfield .= ($cssforfield ? ' ' : '').'center';
			}

			if (in_array($val['type'], array('timestamp'))) {
				$cssforfield .= ($cssforfield ? ' ' : '').'nowraponall';
			} elseif ($key == 'ref') {
				$cssforfield .= ($cssforfield ? ' ' : '').'nowraponall';
			}

			if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('id', 'rowid', 'ref', 'status')) && empty($val['arrayofkeyval'])) {
				$cssforfield .= ($cssforfield ? ' ' : '').'right';
			}
			//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

			if (!empty($arrayfields['t.'.$key]['checked'])) {
				print '<td'.($cssforfield ? ' class="'.$cssforfield.(preg_match('/tdoverflow/', $cssforfield) ? ' classfortooltip' : '').'"' : '');
				if (preg_match('/tdoverflow/', $cssforfield) && !is_numeric($object->$key)) {
					print ' title="'.dol_escape_htmltag($object->$key).'"';
				}
				print '>';
				if($key == "label") {
					if($user->id == '412' || $user->id == '1') {
						print '<a class="documentdownload paddingright" href="/erp/document.php?modulepart=gpeccustom&amp;file=cvtec%2F'.$object->ref.'%2F'.$object->ref.'.pdf&amp;entity=1" title="'.$object->ref.'.pdf"><i class="fa fa-file-pdf-o paddingright" title="Fichier: '.$object->ref.'.pdf"></i>'.$object->ref.'.pdf</a>';
						print '<a class="pictopreview documentpreview" href="/erp/document.php?modulepart=gpeccustom&amp;attachment=0&amp;file=cvtec%2F'.$object->ref.'%2F'.$object->ref.'.pdf&amp;entity=1" mime="application/pdf" target="_blank"><span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span></a>';
					}
				}
				
				if ($key == 'status') {
					print $object->getLibStatut(3);
				} elseif ($key == 'rowid') {
					print $object->showOutputField($val, $key, $object->id, '');
				} elseif($key == 'ref') {
					if($user->id == '412' || $user->id =='1') {
						print $object->showOutputField($val, $key, $object->$key, '');
					}else{
						print $object->$key;
					}
					
				} else {
					print $object->showOutputField($val, $key, $object->$key, '');
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
					if (!$i) {
						$totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
					}
					if (!isset($totalarray['val'])) {
						$totalarray['val'] = array();
					}
					if (!isset($totalarray['val']['t.'.$key])) {
						$totalarray['val']['t.'.$key] = 0;
					}
					$totalarray['val']['t.'.$key] += $object->$key;
				}
			}
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// var_dump($object->array_options);
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		
		/*if (!empty($arrayfields['anotherfield']['checked'])) {
			print '<td class="right">'.$obj->anotherfield.'</td>';
		}*/

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
	}

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}


$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', $arrayofmassactions) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_'.$object->module, '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();

