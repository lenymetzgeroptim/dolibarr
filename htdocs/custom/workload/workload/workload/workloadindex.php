<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       workload/workloadindex.php
 *	\ingroup    workload
 *	\brief      Home page of workload top menu
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("workload@workload"));

$action = GETPOST('action', 'aZ09');

$max = 5;
$now = dol_now();

$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);

$arr_user = GETPOST('arr_user');
$un_order = GETPOST('un_order');
$un_project = GETPOST('un_project');
$arr_manager = GETPOST('arr_manager');
$arr_group = GETPOST('arr_group');
$arr_skill = GETPOST('arr_skill');
$occupied =  GETPOST('case');

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('workload')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('workload', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'workload', 0, 'workload_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}


/*
 * Actions
 */

// None
if (empty($date_start)) {
	$date_start = dol_time_plus_duree($now, -1, 'y');
	//$date_start = dol_get_first_day(dol_print_date($now, 'Y'));
}

if (empty($date_end)) {
	$date_end = dol_time_plus_duree($now, 1, 'y');
	// $date_end = dol_get_last_day(dol_print_date($now, 'Y'));
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("Cumul ETP"));

// print load_fiche_titre($langs->trans("Plan de charge"), '', 'workload.png@workload');


//$linktotasks = dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars paddingleft imgforviewmode', DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id, '', 1, array('morecss'=>'reposition'));
// $linktotasks .= dolGetButtonTitle($langs->trans('Projet : Collaborateus - Absence'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_plan_holiday.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
// $linktotasks .= dolGetButtonTitle($langs->trans('Cde : Collaborateur - Projet'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_by_commande.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
// $linktotasks .= dolGetButtonTitle($langs->trans('Projet : Cde/devis - collaborateur'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview_by_project.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
// $linktotasks .= dolGetButtonTitle($langs->trans('Collaborateurs : projets/devis/cde'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttviewglobal.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
// $linktotasks .= dolGetButtonTitle($langs->trans('Collaborateurs : devis & cde'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Cumul ETP'), '', 'fa fa-chart-line paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/workloadindex.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Plan de charge'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/plancharge_modes_view.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));

//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'generic', 0, '', '', 0, 1);
print load_fiche_titre($langs->trans("Cumul ETP"), $linktotasks.' &nbsp; '.$linktocreatetask, 'projecttask');

require_once DOL_DOCUMENT_ROOT.'/custom/workload/class/charge.class.php';
$charge = new Charge($db);
$tmp = $charge->getEtpPieChart($date_start, $date_end);

print '<div class="fichecenter">';
// print '<div class="fichethirdleft">';

// if ($commarr) {
	if($occupied == 'on') {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?case=on&token='.newToken().'&amp;action=filter">';
	}else{
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	}
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder nohover centpercent" style="margin-bottom: 10px; border-bottom-style: none;">';
	print '<tr class="liste_titre">';
	print '<td  width="80%">'.$langs->trans("Statistics").' - '.$langs->trans("ETP").'</td>';
	
	print '<td width="10%" class="right"><button title="Réinitialiser" style="background:transparent;" role="button" class=" btn-message-a tosend btn-tosend msg2" data-uia="nmhp-card-cta+hero_fuji" type="submit" name="remove" value="refresh"><div aria-hidden="true" class="default-ltr-cache-17uj5h e1ax5wel0"><span class="fas fa-sync-alt" style="font-size:1.2em;color:#8a8a8a;"></span></div></div></div><div class="default-ltr-cache-vgp0nn e9eyrqp3"></div></div></td>';

	print  '</tr>';
	print '</table>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder nohover centpercent">';
	print '<tr class="liste_titre" style="background:#fbfbfb;">';

	print '<td class="center" colspan="2" class="center"style="background:#fbfbfb;">'.$form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0).'  '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0).'';
	print '<input type="submit" class="smallpaddingimp button" name="submit" value="Appliquer"></td>';
	print '</tr>';
	print $tmp;
	

	print '</table></div></form>';
	
	print '<br>';
// }



print '</div></div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');


print '</div></div>';


// End of page
llxFooter();
$db->close();
