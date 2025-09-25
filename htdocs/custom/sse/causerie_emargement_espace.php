<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       causerieattendance_list.php
 *		\ingroup    sse
 *		\brief      List page for causerieattendance
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
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
	$i--; $j--;
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

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieuser.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerie.class.php';
//require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieattendance.class.php';


// load sse libraries
require_once __DIR__.'/class/causerieattendance.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("sse@sse", "other"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'causerieattendancelist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Load variable for pagination
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

// Initialize technical objects
$object = new CauserieAttendance($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->sse->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('causerieattendancelist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

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
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['t.'.$key] = $val['label'];
	}
}

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
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

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->rights->sse->causerie->write_emargement;
	$permissiontoadd = $user->rights->sse->causerie->write_emargement;
	$permissiontodelete = $user->rights->sse->causerie->write_emargement;
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1;
	$permissiontodelete = 1;
}

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) accessforbidden();
//$socid = 0; if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->sse->enabled)) accessforbidden('Moule not enabled');
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
	$objectclass = 'CauserieAttendance';
	$objectlabel = 'CauserieAttendance';
	$uploaddir = $conf->sse->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$form = new Form($db);

$now = dol_now();

//$help_url="EN:Module_CauserieAttendance|FR:Module_CauserieAttendance_FR|ES:Módulo_CauserieAttendance";
$help_url = '';
//$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("CauserieAttendances"));
$title = $langs->trans('Liste d\'émargements');
$morejs = array();
$morecss = array();


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
//$sql .= $object->getFieldList('t');
$sql .= ($object->getFieldList('t').', c.rowid as causerie_id, c.description, c.date_debut, c.date_fin, c.status as causerie_status, c.ref as causerie_ref, c.local as causerie_local ');
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie as c ON c.rowid = t.fk_causerie";

if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
}
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
	$sql .= " WHERE t.entity IN (".getEntity($object->element).")";
} else {
	$sql .= " WHERE 1 = 1";
}
$sql .= " AND c.status > '2'";
$sql .= " AND t.fk_user = ".$user->id;
//$sql .= " AND t.fk_user = 1";

foreach ($search as $key => $val) {
	if (array_key_exists($key, $object->fields)) {
		if ($key == 'status' && $search[$key] == -1) {
			continue;
		}
		$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
		if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
			if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
				$search[$key] = '';
			}
			$mode_search = 2;
		}
		if ($search[$key] != '') {
			$sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
	} else {
		if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
			$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
			if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
				if (preg_match('/_dtstart$/', $key)) {
					$sql .= " AND t.".$columnName." >= '".$db->idate($search[$key])."'";
				}
				if (preg_match('/_dtend$/', $key)) {
					$sql .= " AND t." . $columnName . " <= '" . $db->idate($search[$key]) . "'";
				}
			}
		}
	}
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

/* If a group by is required
$sql .= " GROUP BY ";
foreach($object->fields as $key => $val) {
	$sql .= "t.".$key.", ";
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
	}
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);
*/

// Add HAVING from hooks
/*
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= empty($hookmanager->resPrint) ? "" : " HAVING 1=1 ".$hookmanager->resPrint;
*/

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	/* This old and fast method to get and count full list returns all record so use a high amount of memory.
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	*/
	/* The slow method does not consume memory on mysql (not tested on pgsql) */
	/*$resql = $db->query($sql, 0, 'auto', 1);
	while ($db->fetch_object($resql)) {
		$nbtotalofrecords++;
	}*/
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^SELECT[a-z0-9\._\s\(\),]+FROM/i', 'SELECT COUNT(*) as nbtotalofrecords FROM', $sql);
	$resql = $db->query($sqlforcount);
	$objforcount = $db->fetch_object($resql);
	
	$nbtotalofrecords = $objforcount->nbtotalofrecords;
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
//$sql .= $db->order($sortfield, $sortorder);
$sql .= " ORDER BY c.date_debut DESC, c.date_fin DESC, t.date_signature DESC";
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

//$num = $db->num_rows($resql);

// Direct jump if only one record found
// if ($num == 1 ) {
// 	$obj = $db->fetch_object($resql);
// 	$id = $obj->rowid;
// 	//var_dump($obj);
// 	//header("Location: ".dol_buildpath('/sse/causerieattendance_card.php', 1).'?id='.$id);
// 	//exit;
// }


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', '');

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
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
// if ($permissiontodelete) {
// 	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
// }
// if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
// 	$arrayofmassactions = array();
// }
// $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
// if ($optioncss != '') {
// 	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
// }
// print '<input type="hidden" name="token" value="'.newToken().'">';
// print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
// print '<input type="hidden" name="action" value="list">';
// print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
// print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
// print '<input type="hidden" name="page" value="'.$page.'">';
// print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

//$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/sse/causerieattendance_card.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

//print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// // Add code for pre mass action (confirmation or email presend form)
// $topicmail = "SendCauserieAttendanceRef";
// $modelmail = "causerieattendance";
// $objecttmp = new CauserieAttendance($db);
// $trackid = 'xxxx'.$object->id;
// include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

// if ($search_all) {
// 	$setupstring = '';
// 	foreach ($fieldstosearchall as $key => $val) {
// 		$fieldstosearchall[$key] = $langs->trans($val);
// 		$setupstring .= $key."=".$val.";";
// 	}
// 	print '<!-- Search done like if PRODUCT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
// 	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>'."\n";
// }

// $moreforfilter = '';
// /*$moreforfilter.='<div class="divsearchfield">';
// $moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
// $moreforfilter.= '</div>';*/

// $parameters = array();
// $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
// if (empty($reshook)) {
// 	$moreforfilter .= $hookmanager->resPrint;
// } else {
// 	$moreforfilter = $hookmanager->resPrint;
// }

// if (!empty($moreforfilter)) {
// 	print '<div class="liste_titre liste_titre_bydiv centpercent">';
// 	print $moreforfilter;
// 	print '</div>';
// }

// $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
// $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
// $selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

// print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
// print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


// //Fields title search
// //--------------------------------------------------------------------
// print '<tr class="liste_titre">';
// foreach ($object->fields as $key => $val) {
// 	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
// 	if ($key == 'status') {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'center';
// 	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'center';
// 	} elseif (in_array($val['type'], array('timestamp'))) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
// 	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'right';
// 	}
// 	if (!empty($arrayfields['t.'.$key]['checked'])) {
// 		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
// 		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
// 			print $form->selectarray('search_'.$key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
// 		} elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
// 			print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', 'maxwidth125', 1);
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

// // Fields from hook
// $parameters = array('arrayfields'=>$arrayfields);
// $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
// print $hookmanager->resPrint;
// // Action column
// print '<td class="liste_titre maxwidthsearch">';
// $searchpicto = $form->showFilterButtons();
// print $searchpicto;
// print '</td>';
// print '</tr>'."\n";

// print '</table><br><br>';

// Fields title label
// --------------------------------------------------------------------
// print '<tr class="liste_titre">';
// foreach ($object->fields as $key => $val) {
// 	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
// 	if ($key == 'status') {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'center';
// 	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'center';
// 	} elseif (in_array($val['type'], array('timestamp'))) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
// 	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
// 		$cssforfield .= ($cssforfield ? ' ' : '').'right';
// 	}
// 	if (!empty($arrayfields['t.'.$key]['checked'])) {
// 		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
// 	}
// }
// // Extra fields
// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// // Hook fields
// $parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
// $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
// print $hookmanager->resPrint;
// // Action column
// print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
// print '</tr>'."\n";


// // Detect if we need a fetch on each output line
// $needToFetchEachLine = 0;
// if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
// 	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
// 		if (preg_match('/\$object/', $val)) {
// 			$needToFetchEachLine++; // There is at least one compute field that use $object
// 		}
// 	}
// }

print '<br>';

$emargement = new CauserieAttendance($db);
$participant = new User($db);
$guest = new CauserieUser($db);
$causerie = new Causerie($db);

// Loop on record
// --------------------------------------------------------------------


$i = 0;
$totalarray = array();
$totalarray['nbfield'] = 0;
$data = array();
$num = $db->num_rows($resql);

while ($i < ($limit ? min($num, $limit) : $num)) {
	if ($num >= 1 ) {
		$obj = $db->fetch_object($resql);
		if (empty($obj)) {
			break; // Should not happen
		}

		// Store properties in $object
		$object->setVarsFromFetchObj($obj);
		
		if($object->id != null) {
			$emargement->fetch($object->id);
		}

		if(($emargement->entity == '0'  || $emargement->entity == '1') && $emargement->causerie_status >= 3) {
			$data[] = array('emargement' => $object->id); 
		}
		if($emargement->causerie_status == 3 && ($db->idate(dol_now()) < $emargement->date_debut)) {
			$programmed[] =  $emargement->id;
		}

		if($db->idate(dol_now()) >= $emargement->date_debut && $db->idate(dol_now()) <= $emargement->date_fin && $emargement->status == $emargement::STATUS_UNSIGNED && $emargement->causerie_status < 6) {
			$inanimation_unsigned[] =  $emargement->id;
		}

		if($emargement->status == $emargement::STATUS_SIGNED && $db->idate(dol_now()) >= $emargement->date_debut && $emargement->causerie_status < 6) {
			$causerie_signed[] =  $emargement->id;
		}
		if($emargement->status == $emargement::STATUS_UNSIGNED && $db->idate(dol_now()) >= $emargement->date_fin && $emargement->causerie_status < 6) {
			$causerie_unsigned[] =  $emargement->id;
		}
		if($emargement->causerie_status == 6) {
			$causerie_closed[] =  $emargement->id;
		}
		
		$i++;
	}
}


// Le code actuel fait provisoirement pour tester l'usage d'espace d'émargement et nécessite une refonte pour le rendre optimisé et plus maintenable (to do). Il comporte des parties à simplifier ou à adapter pour garantir un meilleur fonctionnement, une performance correcte et une meilleure lisibilité. Une évolution du besoin de l'espace émargement va définir son évolution.
$id_user = $object->fk_user;
if($id_user) {
	$participant->fetch($id_user);
	$login = $participant->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
	$admin = $participant->admin;
	$participant_entity = $participant->entity;
	$participant_id = $participant->id;
	$participant_lastname = $participant->lastname;
	$participant_firstname = $participant->firstname;
}
$nb_programmed =  $programmed== null ? $nb_programmed = 0 : count($programmed);
$nb_inanimation_unsigned =  $inanimation_unsigned == null ? $nb_inanimation_unsigned = 0 : count($inanimation_unsigned);
$nb_causerie_signed =  $causerie_signed == null ? $nb_causerie_signed = 0 : count($causerie_signed);
$nb_causerie_unsigned =  $causerie_unsigned == null ? $nb_causerie_unsigned = 0 : count($causerie_unsigned);
$nb_causerie_closed = $causerie_closed == null ? $nb_causerie_closed = 0 : count($causerie_closed);

print '<table class="centpercent notopnoleftnoright table-fiche-title">';
print '<tr class="titre">';
print '<td class="nobordernopadding widthpictotitle valignmiddle col-picto">';
print '<span class="fas fa-sharp fa-solid fa-user fa-flip" style="">';
print '</span>';
print '</td><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">Mon espace émargement causerie</div></td>';
print '</tr>';
print '</table>';
//print '<td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block"><span class="fas fa-sharp fa-solid fa-user fa-flip"></span> Mon espace émargement causerie</div></td>';
print '<div class="underbanner clearboth"></div>';
print ' &nbsp; ';
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="opened-dash-board-wrap">';
print '<div class="box-flex-container" style="width: calc(90% + 34px);">';
print '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
print '<div class="info-box" style="width: max-content;">';
print '<span class="info-box-icon bg-fa-comments">';
print '<i class="fas fa-comments">';
print '</span></i>';
print '<div class="info-box-content">';
print '<div class="info-box-title">Vos causeries</div>';
print '<div class="info-box-lines">';
print '<div class="info-box-line"><a href="#signature" class="info-box-text info-box-text-a"><span class="marginrightonly" title="A signer">Retard de signature (en cours) | (déjà réalisée)</span><span class="classfortooltip badge badge-warning" title="'.$nb_inanimation_unsigned.' causerie(s) en cours à signer">'.$nb_inanimation_unsigned.'</span></a> <a href="#signature" class="info-box-text info-box-text-a paddingleft"><span title="'.$nb_causerie_unsigned.' causerie(s) en retard, à signer impérativement" class="classfortooltip badge badge-danger"><i class="fa fa-exclamation-triangle"></i>'.$nb_causerie_unsigned.'</span></a>';
print '</div>';
print '<div class="info-box-line"><a href="#" class="info-box-text info-box-text-a"><span class="marginrightonly" title="Causeries qui sont déjà programmée pour vous">A venir prochainement</span><span class="classfortooltip badge badge-warning" title="Causeries programmées ('.$nb_programmed.')">'.$nb_programmed.'</span></a>';
print '</div>';
print '<div class="info-box-line"><a href="#signature" class="info-box-text info-box-text-a"><span class="marginrightonly" title="Causerie(s) réalisée(s)">Causeries signées | Causeries clôturées</span><span class="classfortooltip badge badge-info" title="'.$nb_causerie_signed.' causerie(s) signée(s) en attente de confirmation">'.$nb_causerie_signed.'</span></a> <a href="#signature" class="info-box-text info-box-text-a paddingleft"><span title="'.$nb_causerie_closed.' causerie(s) clôturée(s)" class="classfortooltip badge badge-info">'.$nb_causerie_closed.'</span></a>';
print '</div>';
print '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
print '	</div><!-- /.info-box -->';
print '</div><!-- /.box-flex-item-with-margin -->';
print '</div>';

print '</div>';
print '</div>';
print '</div>';

print '<div class="fichehalfright">';
if($nb_programmed > 0) { 
	print '<table class="border centpercent tableforfield" style = "margin: 0px 0px 0px 0px;">'."\n";
	print '<tr class="liste_titre box_titre"><th colspan="5"><div class="tdoverflowmax400 maxwidth250onsmartphone float"><span class="fa fa-comment-o"></span> Causerie à venir</div>';
	//print '<div class="liste_titre right">'.$participant_firstname.' '.$participant_lastname.'<div></th>';
	print '<div class="right">';
	print $login;
				if ($admin && !$participant_entity) {
					print img_picto($langs->trans("SuperAdministrator"), 'redstar');
				} elseif ($participant->admin) {
					print img_picto($langs->trans("Administrator"), 'star');
				}
	print '</div></th>';
	print '</tr>';

	foreach($data as $val) {
		$emargement->fetch($val['emargement']);

		if($emargement->causerie_status == 3 && $db->idate(dol_now()) < $emargement->date_debut) {
			if($nb_programmed > 0) { 
			print '<tr class="oddeven">';
			print '<td class="liste_titre">';
			print $emargement->getNomUrlCauserie(-1, '', 0, 0, 24, 0, 'login');
			print '</td>';
			//var_dump($emargement->id);
			/* if($participant_id != null) {
			print '<td>'.$participant_lastname.'</td>';
			} //else {
			// 	print '<td>'.$guest->lastname.'</td>';
			// }
			if($participant_id != null) {
			print '<td>'.$participant_firstname.'</td>';
			}  *///else {
			// 	print '<td>'.$guest->firstname.'</td>';
			// }
			//print '<td class="tdoverflowmax200 maxwidth50onsmartphone">'.$emargement->description.'</td>';
			print '<td class="liste_titre"><span class="fas fa-solid fa-bell" style="color:red;"> </span>'.strftime('%d-%m-%Y à %H:%M',strtotime($emargement->date_debut)).'</td>';
			//print '<td class="liste_titre">'.dol_print_date($emargement->date_fin).' à '.dol_print_date($emargement->date_fin, 'hour').'</td>';
			print '<td class="liste_titre" center" width="5">'.$emargement->getLibStatutCauserie(5).'</td>';
			//print '<td class="center">'.$object->getLibStatut(5).'</td>';
			//print '<td class="right">';
			//print "</td></tr>\n";
			} else {
				print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}
		}
	}
print "</tr></table>";
print ' &nbsp; ';
}
print '</div></div>';

//
print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print ' &nbsp; ';
//print '<div class="centpercent notopnoleftnoright table-fiche-title">';
//print ' &nbsp; ';

print '<table class="centpercent notopnoleftnoright table-fiche-title">';
print '<tr class="titre">';
print '<td class="nobordernopadding widthpictotitle valignmiddle col-picto">';
print '<span class="fas fa-sharp fa-solid fa-file-signature" style="">';
print '</span>';
print '</td><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block" id="signature">Apposer votre signature</div></td>';
print '</tr>';
print '</table>';

//print '<td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block"><span class="fas fa-sharp fa-solid fa-file-signature"></span> Apposer votre signature</div></td>';

foreach($data as $val) {
	$emargement->fetch($val['emargement']);
	if((($emargement->causerie_status == 4 || $emargement->causerie_status == 5 ) && $emargement->status == $emargement::STATUS_UNSIGNED) || ($emargement->causerie_status == 3 && $db->idate(dol_now()) >= $emargement->date_debut && $emargement->status == $emargement::STATUS_UNSIGNED)) {
		//table to edit the information of a eache causerie
		print '<table class="border centpercent tableforfield" style = "margin: 0px 0px 0px 0px;">'."\n";
		print '<tr class="liste_titre" style="background:#d3def1;">';
		print '<td class="liste_titre"><i class="fa fa-comment fa-1x" aria-hidden="true"></i></td>';
		print '<td class="liste_titre" style="font-weight:bold;">'.'Date de début le '.strftime('%d-%m-%Y à %H:%M',strtotime($emargement->date_debut)).'</td>';
		print '<td class="liste_titre" style="font-weight:bold;">'.'Date de fin le '.strftime('%d-%m-%Y à %H:%M',strtotime($emargement->date_fin)).'</td>';
		print '<td class="liste_titre center" style="font-weight:bold;">';
		print 'Animateur ';
		print $user->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
			if ($user->admin && !$user->entity) {
				print img_picto($langs->trans("SuperAdministrator"), 'redstar');
			} elseif ($user->admin) {
				print img_picto($langs->trans("Administrator"), 'star');
			}
		print '</td>';
		if($emargement->causerie_status == 3 && $db->idate(dol_now()) >= $emargement->date_debut) {
			print '<td class="liste_titre" center" width="5">'.$emargement->getLibStatutCauserie(3).'</td>';
		}else{
			print '<td class="liste_titre center" width="5">'.$emargement->getLibStatutCauserie(5).'</td>';
		}

		print '<td class="liste_titre right" width="5">&nbsp;</td>';
		print '<td class="liste_titre right" width="5">&nbsp;</td>';
		print "</tr>\n";
		print "</table>";

		//start form for each causerie
		print '<form action="'.dol_buildpath('/sse/causerieattendance_card.php', 1).'?id='.$emargement->id.'" method="POST">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update_emargement">';
	
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">'.$langs->trans("Réf. Causerie").'</td>';
		print '<td class="liste_titre">'.$langs->trans("Thème").'</td>';
		print '<td class="liste_titre">'.$langs->trans("Lastname").'</td>';
		// print '<td class="liste_titre">'.$langs->trans("Firstname").'</td>';
		// print '<td class="liste_titre">'.$langs->trans("Absence").'</td>';
		// print '<td class="liste_titre">'.$langs->trans("Raison d'absence").'</td>';
		print '<td class="liste_titre center" width="5">'.$langs->trans("Visas").'</td>';
		print '<td class="liste_titre right" width="5">&nbsp;</td>';
		print "</tr>\n";
		print '<tr class="oddeven">';
		if($emargement->id != null) { 
		print '<td class="tdoverflowmax80 maxwidth70onsmartphone float">';
		// print $login;
		// 	if ($admin && !$participant_entity) {
		// 		print img_picto($langs->trans("SuperAdministrator"), 'redstar');
		// 	} elseif ($participant->admin) {
		// 		print img_picto($langs->trans("Administrator"), 'star');
		// 	}
		print $emargement->getNomUrlCauserie(-1, '', 0, 0, 24, 0, 'login');
		print '</td>';
		print '<td class="tdoverflowmax80 maxwidth70onsmartphone float"><span title="'.$emargement->causerie_theme.'">'.$emargement->causerie_theme.'</span></td>';
		print '<td class="liste_titre">'.$participant_lastname.' '.$participant_firstname.'</td>';

		if ($emargement->status == $emargement::STATUS_UNSIGNED || ($emargement->causerie_status == 3 && $db->idate(dol_now()) >= $emargement->date_debut)) {
		print '<td class="tdoverflowmax80 maxwidth70onsmartphone float">';
		// print '<input type="checkbox" id="other" name="absence">';
		// print "<label for='absence'>Oui</label>\n";
		// print '<td class="tdoverflowmax80 maxwidth70onsmartphone float"><textarea class="textarea is-warning" id="otherValue" name="reason" style=" padding: 12px 20px;box-sizing: border-box;border: 2px solid #ccc;border-radius: 4px;background-color: #f8f8f8;"></textarea></td>';
			// print '<td></td>';
	} 
		print '<td class="tdoverflowmax80 maxwidth70onsmartphone float">';
		if ($emargement->status == $emargement::STATUS_UNSIGNED || ($emargement->causerie_status == 3 && $db->idate(dol_now()) >= $emargement->date_debut)) {
			//if (empty($emargement->table_element_line) || (is_array($emargement->lines) && count($emargement->lines) > 0)) {
				print '<div>';
				print '<input type="submit" class="button buttongen button-add" style="background:#edcbcd;font-weight: bold; color: #FFFFFF;padding: 0.6em 0.7em;font-size: 0.95em;border: 1px solid transparent;" value="'.$langs->trans("Signer").'">';
				print '</div>';
				// } else {
			// 	$langs->load("errors");
			// 	print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Signer"), 'default', '#', '', 0);
			// }
		} 
		print '</td>';
		// print '<td class="liste_titre right" width="5">&nbsp;</td>';
		//print '<td class="center">'.$object->getLibStatut(5).'</td>';
		//print '<td class="right">';
		//print "</td></tr>\n";
		} else {
			print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table></form>";

		print "<br><br>";
	}
}

//print '<div class="underbanner clearboth"></div>';
//print ' &nbsp; ';
//print '<div class="centpercent notopnoleftnoright table-fiche-title">';
//print ' &nbsp; ';

print '<table class="centpercent notopnoleftnoright table-fiche-title">';
print '<tr class="titre">';
print '<td class="nobordernopadding widthpictotitle valignmiddle col-picto">';
print '<span class="fas fa-sharp fa-solid fa-file-signature">';
print '</span>';
print '</td><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block" id="valid">Vos causerie(s) récente(s)</div></td>';
print '</tr>';
print '</table>';


foreach($data as $val) {
	$emargement->fetch($val['emargement']);
	if((($emargement->causerie_status == 4 || $emargement->causerie_status == 5 ) && $emargement->status == $emargement::STATUS_SIGNED) || ($emargement->causerie_status == 3 && $db->idate(dol_now()) >= $emargement->date_debut && $emargement->status == $emargement::STATUS_SIGNED)) {
		//table to edit the information of a eache causerie
		print '<table class="border centpercent tableforfield" style = "margin: 0px 0px 0px 0px;">'."\n";
			print '<tr class="liste_titre" style="background:#cfcecd;">';
		print '<td class="liste_titre"><i class="fa fa-comment fa-1x" aria-hidden="true"></i></td>';
		print '<td class="liste_titre" style="font-weight:bold;">'.'Date de début le '.strftime('%d-%m-%Y à %H:%M',strtotime($emargement->date_debut)).'</td>';
		print '<td class="liste_titre" style="font-weight:bold;">'.'Date de fin le '.strftime('%d-%m-%Y à %H:%M',strtotime($emargement->date_fin)).'</td>';
		print '<td class="liste_titre center" style="font-weight:bold;">';
		print 'Animateur ';
		print $user->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
			if ($user->admin && !$user->entity) {
				print img_picto($langs->trans("SuperAdministrator"), 'redstar');
			} elseif ($user->admin) {
				print img_picto($langs->trans("Administrator"), 'star');
			}
		print '</td>';
		if($emargement->causerie_status == 3 && $db->idate(dol_now()) >= $emargement->date_debut) {
			print '<td class="liste_titre" center" width="5">'.$emargement->getLibStatutCauserie(3).'</td>';
		}else{
			print '<td class="liste_titre center" width="5">'.$emargement->getLibStatutCauserie(5).'</td>';
		}

		print '<td class="liste_titre right" width="5">&nbsp;</td>';
		print "</tr>\n";
		print "</table>";

		//start form for each causerie
		print '<form action="'.dol_buildpath('/sse/causerieattendance_card.php', 1).'?id='.$emargement->id.'" method="POST">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update_emargement">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">'.$langs->trans("Réf. Causerie").'</td>';
		// print '<td class="liste_titre">'.$langs->trans("Thème").'</td>';
		print '<td class="liste_titre">'.$langs->trans("Lastname").'</td>';
		//print '<td class="liste_titre">'.$langs->trans("Firstname").'</td>';
		print '<td class="liste_titre">'.$langs->trans("Absence").'</td>';
		print '<td class="liste_titre ">'.$langs->trans("Raison d'absence").'</td>';
		print '<td class="liste_titre center" width="5">'.$langs->trans("Visas").'</td>';
		print '<td class="liste_titre right" width="5">&nbsp;</td>';
		print "</tr>\n";
		print '<tr class="oddeven">';
		if($emargement->id != null) { 
			print '<td class="tdoverflowmax80 maxwidth40onsmartphone float">';
		// print $login;
		// 	if ($admin && !$participant_entity) {
		// 		print img_picto($langs->trans("SuperAdministrator"), 'redstar');
		// 	} elseif ($participant->admin) {
		// 		print img_picto($langs->trans("Administrator"), 'star');
		// 	}
		print $emargement->getNomUrlCauserie(-1, '', 0, 0, 24, 0, 'login');
		print '</td>';
		// print '<td class="tdoverflowmax80 maxwidth70onsmartphone float"><span title="'.$emargement->causerie_theme.'">'.$emargement->causerie_theme.'</td>';
		print '<td class="liste_titre">'.$participant_lastname.'&nbsp; '.$participant_firstname.'</td>';
	
		print '<td class="tdoverflowmax80 maxwidth70onsmartphone float">'.$emargement->presence.'</td>';
		print '<td class="tdoverflowmax80 maxwidth70onsmartphone float">'.$emargement->reason.'</td>';
		
		print '<td>';
	
			print $emargement->getLibStatut(5);
		
		print '</td>';
		print '<td class="liste_titre right" width="5">&nbsp;</td>';
		//print '<td class="center">'.$object->getLibStatut(5).'</td>';
		//print '<td class="right">';
		//print "</td></tr>\n";
		} else {
			print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table></form>";
		print "<br><br>";
	}
}
print '</div>';
//print '</div></div>';
?>
<script>
	let otherCheckbox = document.querySelector('#other');
	let otherText = document.querySelector('#otherValue');
	otherText.style.visibility = 'hidden';
	otherCheckbox.addEventListener('change', () => {
		if (otherCheckbox.checked) {
			otherText.style.visibility = 'visible';
			otherText.value = '';
		} else {
			otherText.style.visibility = 'hidden';
		}
	});

</script>
<?php

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
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("Aucune causerie à signer pour l'instant").'</span></td></tr>';
}


$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
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

	print $formfile->showdocuments('massfilesarea_sse', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
