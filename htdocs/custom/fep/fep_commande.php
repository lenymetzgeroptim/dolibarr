<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *   	\file       fep_card.php
 *		\ingroup    fep
 *		\brief      Page to create/edit/view fep
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
dol_include_once('/fep/class/fep.class.php');
dol_include_once('/fep/lib/fep_fep.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("fep@fep", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$idfep = GETPOST('idfep', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'fepcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$commandeid   = GETPOST('commandeid', 'int');

// Initialize technical objects
$fep = new FEP($db);
$object = new Commande($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->fep->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('fepcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($fep->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($fep->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($fep->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$permissiontoread = $user->rights->fep->fep->read;
$permissiontoadd = $user->rights->fep->fep->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->fep->fep->delete || ($permissiontoadd && isset($fep->status) && $fep->status == $fep::STATUS_DRAFT);
$permissionnote = $user->rights->fep->fep->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->fep->fep->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->fep->multidir_output[isset($fep->entity) ? $fep->entity : 1].'/fep';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($fep->status == $fep::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $fep->element, $fep->id, $fep->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->fep->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $fep, $action); // Note that $action and $fep may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("FEP");
$help_url = '';
llxHeader('', $title, $help_url);

$head = commande_prepare_head($object);
print dol_get_fiche_head($head, 'fep_commande', $langs->trans("CustomerOrder"), -1, 'order');

// Order card
$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Ref customer
$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
// Project
if (!empty($conf->projet->enabled)) {
	$langs->load("projects");
	$morehtmlref .= '<br>'.$langs->trans('Project').' ';
	if ($user->rights->commande->creer) {
		if ($action != 'classify') {
			//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			$morehtmlref .= ' : ';
		}
		if ($action == 'classify') {
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
			$morehtmlref .= $formproject->select_projects($object->thirdparty->id, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->thirdparty->id, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
			$morehtmlref .= $proj->ref;
			$morehtmlref .= '</a>';
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref .= '</div>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);



$i = 0;
$cpt = 0;
$listFep = $fep->getListIdByCommande($object->id); // Liste des fep rattachées à la commande
foreach($listFep as $fep_id){
	$fep->fetch($fep_id);

	if($cpt == 0){
		print '<div class="tabs" data-role="controlgroup" data-type="horizontal">';
		print '<a class="tabTitle">';
		print '<span class="fas fa-fep imgTabTitle em120 infobox-fep" style="" title="'.$langs->trans("Fep").'"></span>' ;
		print '<span class="tabTitleText">FEP</span>';
		print '</a>';
	}
	$link = $_SERVER['PHP_SELF'].'?id='.$id.'&idfep='.$fep->id;
	if($fep->id == GETPOST('idfep')){
		print '<div class="inline-block tabsElem tabsElemActive">';
		print '<div class="tab tabactive" style="margin: 0 !important">';
	}
	else {
		print '<div class="inline-block tabsElem">';
		print '<div class="tab tabunactive" style="margin: 0 !important">';
	}
	print '<a id="fep" class="tab inline-block" href="'.$link.'">'.$fep->ref.'</a>';
	print '</div>';
	print '</div>';
	$cpt++;
}
print '</div>';

print '<div class="tabBar">';
// Part to show record
if (!empty($idfep)) {
	$fep->fetch($idfep);
	$res = $fep->fetch_optionals();

	// Object card
	// ------------------------------------------------------------
	$morehtmlref = '';
	//$morehtmlstatus = '<div class="statusref">';
	$morehtmlstatus .= '<a class="butAction" href="/erp/custom/fep/fep_card.php?id='.$fep->id.'" style="margin-top: 5px;">Voir FEP</a>';
	//$morehtmlstatus .= '</div>';

	dol_banner_tab($fep, '', '', 0, '', '', $morehtmlref, '', '', '', $morehtmlstatus);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak='note_theme1';	// We change column just before this field
	unset($fep->fields['question1_1']);
	unset($fep->fields['question1_2']);
	unset($fep->fields['question1_3']);
	unset($fep->fields['question2_1']);
	unset($fep->fields['question2_2']);
	unset($fep->fields['question2_3']);
	unset($fep->fields['question2_4']);
	unset($fep->fields['question2_5']);
	unset($fep->fields['question3_1']);
	unset($fep->fields['question3_2']);
	unset($fep->fields['question3_3']);
	unset($fep->fields['question3_4']);
	unset($fep->fields['question3_5']);
	unset($fep->fields['question3_6']);
	unset($fep->fields['question3_7']);
	unset($fep->fields['question3_8']);
	unset($fep->fields['question4_1']);
	unset($fep->fields['question4_2']);
	unset($fep->fields['question4_3']);
	unset($fep->fields['question4_4']);
	unset($fep->fields['question5_1']);
	unset($fep->fields['question5_2']);
	unset($fep->fields['question6_1']);
	unset($fep->fields['question6_2']);
	unset($fep->fields['question6_3']);
	unset($fep->fields['question6_4']);
	unset($fep->fields['question6_5']);
	unset($fep->fields['question7_1']);
	unset($fep->fields['question7_2']);
	unset($fep->fields['constat1']);
	unset($fep->fields['prop_amelioration1']);
	unset($fep->fields['constat2']);
	unset($fep->fields['prop_amelioration2']);
	unset($fep->fields['constat3']);
	unset($fep->fields['prop_amelioration3']);
	unset($fep->fields['constat4']);
	unset($fep->fields['prop_amelioration4']);
	$object = $fep;
	include DOL_DOCUMENT_ROOT.'/custom/fep/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

}

// End of page
llxFooter();
$db->close();
