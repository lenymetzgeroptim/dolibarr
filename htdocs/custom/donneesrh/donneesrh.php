<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 METZGER Leny <l.metzger@optim-industries.fr>
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
 *   	\file       ongletdonneesrh_list.php
 *		\ingroup    donneesrh
 *		\brief      List page for ongletdonneesrh
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/responsible.class.php';


require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
// load donneesrh libraries
require_once __DIR__.'/class/ongletdonneesrh.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("donneesrh@donneesrh", "other", 'users', 'admin'));

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

$attrname = GETPOST('attrname', 'alpha');

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
$object = new OngletDonneesRH($db);
$object_static = new OngletDonneesRH($db);
$extrafields = new ExtraFields($db);
$form = new Form($db);
$diroutputmassaction = $conf->donneesrh->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('ongletdonneesrhlist')); // Note that conf->hooks_modules contains array

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
$search_all = GETPOST('search_all', 'alphanohtml') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml');
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

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) {
	$type2label[$key] = $langs->transnoentitiesnoconv($val);
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
$enablepermissioncheck = 1;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('donneesrh', 'ongletdonneesrh', 'read');
	$permissiontoadd = $user->hasRight('donneesrh', 'ongletdonneesrh', 'write');
	$permissiontodelete = $user->hasRight('donneesrh', 'ongletdonneesrh', 'delete');
	$permissiontoaddfield = $user->hasRight('donneesrh', 'fielddonneesrh', 'write');
	$permissiontodeletefield = $user->hasRight('donneesrh', 'fielddonneesrh', 'delete');
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1;
	$permissiontodelete = 1;
	$permissiontoaddfield = 1;
	$permissiontodeletefield = 1;
}

$textobject = $langs->transnoentitiesnoconv("Users");
$backurlforlist = $_SERVER["PHP_SELF"];

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) accessforbidden();
//$socid = 0; if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, 0, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("donneesrh")) {
	accessforbidden('Module donneesrh not enabled');
}
if (!$permissiontoread) accessforbidden();

if($id > 0) {
	$object->fetch($id);
	$elementtype = 'donneesrh_'.$object->ref; //Must be the $table_element of the class that manage extrafield
}

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
	if($action == 'confirm_delete' && $confirm == 'yes' && !empty($permissiontodelete)) {
		$result = $object->count_extrafield($elementtype);

		if($result >= 0) {
			if($result > 0) {
				setEventMessages("Impossible de supprimer l'onglet car celui-ci contient encore des attributs", null, 'errors');
				$action = '';
				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
				exit;
			}
		}
		else {
			setEventMessages("Erreur lors de la suppression de l'onglet", null, 'errors');
		}
	}

	require DOL_DOCUMENT_ROOT.'/custom/donneesrh/core/tpl/actions_extrafields.inc.php';

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/custom/donneesrh/core/tpl/actions_addupdatedelete.inc.php';
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

//$help_url = "EN:Module_OngletDonneesRH|FR:Module_OngletDonneesRH_FR|ES:Módulo_OngletDonneesRH";
$help_url = '';
$title = $langs->trans("OngletDonneesRHs");
$morejs = array();
$morecss = array();


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
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
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
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
	$sql .= " WHERE t.entity IN (".getEntity($object->element).")";
} else {
	$sql .= " WHERE 1 = 1";
}
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
			$sql .= natural_search("t.".$db->escape($key), $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
	} else {
		if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
			$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
			if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
				if (preg_match('/_dtstart$/', $key)) {
					$sql .= " AND t.".$db->escape($columnName)." >= '".$db->idate($search[$key])."'";
				}
				if (preg_match('/_dtend$/', $key)) {
					$sql .= " AND t.".$db->escape($columnName)." <= '".$db->idate($search[$key])."'";
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
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
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

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
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


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre('Nouvel '.$langs->transnoentitiesnoconv("OngletDonneesRH"), '', 'fontawesome_user-plus_fa_#79633f');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($langs->trans("OngletDonneesRH"), '', 'fontawesome_user-plus_fa_#79633f');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

if (($action != 'edit' && $action != 'create')) {
	$newcardbutton = '';
	//$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/donneesrh/donneesrh.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']).'%3Fid%3D--IDFORBACKTOPAGE--', '', $permissiontoadd);

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, -1, 'fontawesome_user-plus_fa_#79633f', 0, $newcardbutton, '', $limit, 1, 0, 1);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		  $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('DeleteOngletDonneesRH'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// Confirmation to delete
	if ($action == 'delete_extrafield') {
		 $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id.'&attrname='.$attrname, $langs->trans('DeleteOngletDonneesRH'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete_extrafield', '', 0, 1);
	}

	// Print form confirm
	print $formconfirm;

	if($num > 0) {
		print '<div class="tabs" data-role="controlgroup" data-type="horizontal">';

		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if (empty($obj)) {
				break; // Should not happen
			}

			// Store properties in $object
			$object_static->setVarsFromFetchObj($obj);

			$link = $_SERVER['PHP_SELF'].'?id='.$object_static->id;

			if($object_static->id == $id){
				print '<div class="inline-block tabsElem tabsElemActive">';
				print '<div class="tab tabactive" style="margin: 0 !important">';
			}
			else {
				print '<div class="inline-block tabsElem">';
				print '<div class="tab tabunactive" style="margin: 0 !important">';
			}

			print '<a id="donneesRH" class="tab inline-block" href="'.$link.'" style="margin-right: 0px;">'.$object_static->label;
			print '</a>';

			if($object_static->id == $id){
				if($permissiontoadd) {
					print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=edit&backtopage='.urlencode($_SERVER['PHP_SELF']).'%3Fid%3D'.$object->id.'&token='.newToken().'">'.img_edit().'</a>';
				}

				if($permissiontodelete) {
					 print '<a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=delete&token='.newToken().'">'.img_delete().'</a>&nbsp;';
				}
			}

			print '</div>';
			print '</div>';
			$i++;
		}
		print '</div>';

		print '<div class="tabBar">';
			if($object->id > 0) {
				$reftmp = $object->ref;
				$object->ref = $object->label;
				dol_banner_tab($object, 'ref', '', 1, 'ref', 'ref', $morehtmlref);
				$object->ref = $reftmp;

				print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
				print '<div class="underbanner clearboth"></div>';
				print '<table class="border centpercent tableforfield">'."\n";

				$keyforbreak='groupes_edit';	// We change column just before this field
				unset($object->fields['label']);
				include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

				print '</table>';
				print '</div>';
				print '</div>';
				print '<div class="clearboth"></div><br><br><br>';

				require DOL_DOCUMENT_ROOT.'/custom/donneesrh/core/tpl/admin_extrafields_view.tpl.php';

				print dol_get_fiche_end();

				// Creation of an optional field
				if ($action == 'create_extrafield' && $permissiontoaddfield) {
					print '<br><div id="newattrib"></div>';
					print load_fiche_titre($langs->trans('NewAttribute'));

					require DOL_DOCUMENT_ROOT.'/custom/donneesrh/core/tpl/admin_extrafields_add.tpl.php';
				}

				// Editing an optional field
				if ($action == 'edit_extrafield' && !empty($attrname) && $permissiontoaddfield) {
					print "<br>";
					print load_fiche_titre($langs->trans("FieldEdition", $attrname));

					require DOL_DOCUMENT_ROOT.'/custom/donneesrh/core/tpl/admin_extrafields_edit.tpl.php';
				}
			}
			else {
				print info_admin($langs->trans("WarningNoTabSelect"));
			}
			print '</div>';
	}
	else {
		print info_admin($langs->trans("WarningNoTabExist"));
	}
}

$db->free($resql);

// End of page
llxFooter();
$db->close();
