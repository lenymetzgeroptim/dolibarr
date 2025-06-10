<?php
/* Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/projet/messaging.php
 *      \ingroup    project
 *		\brief      Page with events on project
 */

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

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/lib/formationhabilitation.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("formationhabilitation@formationhabilitation", "other"));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$socid  = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", "aZ09comma");
$sortorder = GETPOST("sortorder", 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
if (!$sortfield) {
	$sortfield = "a.datep,a.id";
}
if (!$sortorder) {
	$sortorder = "DESC";
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ?GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}
$search_rowid = GETPOST('search_rowid');
$search_agenda_label = GETPOST('search_agenda_label');


// Initialize technical objects
$object = new User($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->formationhabilitation->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('userformationhabilitationagenda')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->formationhabilitation->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
}

// Security check
$permissiontoread = $user->rights->formationhabilitation->habilitation_autorisation->read;
$permissiontoadd = $user->rights->formationhabilitation->habilitation_autorisation->write;


// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("formationhabilitation")) {
	accessforbidden();
}
if (!$permissiontoread) accessforbidden();



/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
	$actioncode = '';
	$search_agenda_label = '';
}



/*
 * View
 */

$form = new Form($db);

if ($object->id > 0) {
	$help_url = 'EN:Module_Agenda_En|DE:Modul_Terminplanung';
	$page_name = "Formation - Habilitation";

	llxHeader('', $page_name, $help_url, '', 0, 0, '', '', '', 'formationhabilitation classforhorizontalscrolloftabs');

	// Check if user has rights
	if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$object->getrights();
		if (empty($object->nb_rights) && $object->statut != 0 && empty($object->admin)) {
			setEventMessages($langs->trans('UserHasNoPermissions'), null, 'warnings');
		}
	}

	$head = user_prepare_head($object);

	print dol_get_fiche_head($head, 'userformation', $title, -1, 'user');

	// Object card
	// ------------------------------------------------------------
	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="fichecenter"><div class="underbanner clearboth"></div><br>';

	$head = formationhabilitationUserPrepareHead($object);
    print dol_get_fiche_head($head, 'agenda', $title, -1, 'user');

	// Actions buttons
	$objthirdparty = $object;
	$objcon = new stdClass();

	$out = '&origin='.urlencode($object->element.(property_exists($object, 'module') ? '@'.$object->module : '')).'&originid='.urlencode($object->id);
	$urlbacktopage = $_SERVER['PHP_SELF'].'?id2='.$object->id;
	$out .= '&backtopage='.urlencode($urlbacktopage);
	$permok = $user->hasRight('agenda', 'myactions', 'create');
	if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
		//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objthirdparty) == 'Societe') {
			$out .= '&socid='.urlencode($objthirdparty->id);
		}
		$out .= (!empty($objcon->id) ? '&contactid='.urlencode($objcon->id) : '');
		//$out.=$langs->trans("AddAnAction").' ';
		//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
		//$out.="</a>";
	}

	//print '<div class="tabsAction">';
	$morehtmlcenter = '';

	// Show link to change view in message
	$messagingUrl = DOL_URL_ROOT.'/custom/formationhabilitation/user_messaging.php?id='.$object->id;
	$morehtmlright .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 2);
	$messagingUrl = DOL_URL_ROOT.'/custom/formationhabilitation/user_agenda.php?id='.$object->id;
	$morehtmlright .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $messagingUrl, '', 1);


	// // Show link to send an email (if read and not closed)
	// $btnstatus = $object->status < Ticket::STATUS_CLOSED && $action != "presend" && $action != "presend_addmessage";
	// $url = 'card.php?track_id='.$object->track_id.'&action=presend_addmessage&mode=init&private_message=0&send_email=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?track_id='.$object->track_id).'#formmailbeforetitle';
	// $morehtmlcenter .= dolGetButtonTitle($langs->trans('SendMail'), '', 'fa fa-paper-plane', $url, 'email-title-button', $btnstatus);

	// // Show link to add a private message (if read and not closed)
	// $btnstatus = $object->status < Ticket::STATUS_CLOSED && $action != "presend" && $action != "presend_addmessage";
	// $url = 'card.php?track_id='.$object->track_id.'&action=presend_addmessage&mode=init&backtopage='.urlencode($_SERVER["PHP_SELF"].'?track_id='.$object->track_id).'#formmailbeforetitle';
	// $morehtmlcenter .= dolGetButtonTitle($langs->trans('TicketAddMessage'), '', 'fa fa-comment-dots', $url, 'add-new-ticket-title-button', $btnstatus);

	// Show link to add event
	if (isModEnabled('agenda') && $permissiontoadd) {
		if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
			$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
		} else {
			$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out, '', 0);
		}
	}


	$param = 'id='.$object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.$contextpage;
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.$limit;
	}

	print_barre_liste($langs->trans("Actions"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlright, '', 0, 1, 0);

	// List of all actions
	$filters = array();
	$filters['search_agenda_label'] = $search_agenda_label;
	$filters['search_rowid'] = $search_rowid;
	//$filters['search_extraparams'] = 'cost';

	// List of actions on element
	show_actions_messaging_formationhabilitation($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);

	print '</div>';
}

// End of page
llxFooter();
$db->close();
