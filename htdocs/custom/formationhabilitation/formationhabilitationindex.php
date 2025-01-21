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
 *	\file       formationhabilitation/formationhabilitationindex.php
 *	\ingroup    formationhabilitation
 *	\brief      Home page of formationhabilitation top menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/formation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/habilitation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/userhabilitation.class.php';

// Load translation files required by the page
$langs->loadLangs(array("formationhabilitation@formationhabilitation"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->formationhabilitation->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$userHabilitation = new UserHabilitation($db);
$Habilitation = new Habilitation($db);
$user_static = new User($db);

$arrayofcss = array('/includes/jsgantt/jsgantt.css');

if (!empty($conf->use_javascript_ajax)) {
	$arrayofjs = array(
	'/custom/formationhabilitation/js/jsgantt.js',
	'/projet/jsgantt_language.js.php?lang='.$langs->defaultlang
	);
}

llxHeader("", $langs->trans("FormationHabilitationArea"), '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans("FormationHabilitationArea"), '', 'object_module_32@formationhabilitation');

print '<div class="fichecenter">';

// print '<div class="fichethirdleft">';

// // Last modified myobject
// if (! empty($conf->formationhabilitation->enabled) && $user->rights->formationhabilitation->formation->read)
// {
// 	$myobjectstatic = new Formation($db);

// 	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
// 	$sql.= " FROM ".MAIN_DB_PREFIX."formationhabilitation_formation as s";
// 	$sql .= " ORDER BY s.tms DESC";
// 	$sql .= $db->plimit($max, 0);

// 	$resql = $db->query($sql);
// 	if ($resql)
// 	{
// 		$num = $db->num_rows($resql);
// 		$i = 0;

// 		print '<table class="noborder centpercent">';
// 		print '<tr class="liste_titre">';
// 		print '<th colspan="2">';
// 		print $langs->trans("BoxTitleLatestModifiedFormation", $max);
// 		print '</th>';
// 		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
// 		print '</tr>';
// 		if ($num)
// 		{
// 			while ($i < $num)
// 			{
// 				$objp = $db->fetch_object($resql);

// 				$myobjectstatic->id=$objp->rowid;
// 				$myobjectstatic->ref=$objp->ref;
// 				$myobjectstatic->label=$objp->label;
// 				$myobjectstatic->status = $objp->status;

// 				print '<tr class="oddeven">';
// 				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
// 				print '<td class="right nowrap">';
// 				print "</td>";
// 				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
// 				print '</tr>';
// 				$i++;
// 			}

// 			$db->free($resql);
// 		} else {
// 			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
// 		}
// 		print "</table><br>";
// 	}
// }

// print '</div><div class="fichetwothirdright">';

// // Last modified myobject
// if (! empty($conf->formationhabilitation->enabled) && $user->rights->formationhabilitation->habilitation->read)
// {
// 	$myobjectstatic = new Habilitation($db);

// 	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
// 	$sql.= " FROM ".MAIN_DB_PREFIX."formationhabilitation_habilitation as s";
// 	$sql .= " ORDER BY s.tms DESC";
// 	$sql .= $db->plimit($max, 0);

// 	$resql = $db->query($sql);
// 	if ($resql)
// 	{
// 		$num = $db->num_rows($resql);
// 		$i = 0;

// 		print '<table class="noborder centpercent">';
// 		print '<tr class="liste_titre">';
// 		print '<th colspan="2">';
// 		print $langs->trans("BoxTitleLatestModifiedHabilitation", $max);
// 		print '</th>';
// 		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
// 		print '</tr>';
// 		if ($num)
// 		{
// 			while ($i < $num)
// 			{
// 				$objp = $db->fetch_object($resql);

// 				$myobjectstatic->id=$objp->rowid;
// 				$myobjectstatic->ref=$objp->ref;
// 				$myobjectstatic->label=$objp->label;
// 				$myobjectstatic->status = $objp->status;

// 				print '<tr class="oddeven">';
// 				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
// 				print '<td class="right nowrap">';
// 				print "</td>";
// 				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
// 				print '</tr>';
// 				$i++;
// 			}

// 			$db->free($resql);
// 		} else {
// 			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
// 		}
// 		print "</table><br>";
// 	}
// }

// print '</div>';

// Diagramme de Gantt
if (! empty($conf->formationhabilitation->enabled) && $user->rights->formationhabilitation->formation->read) {
	// Get list of habilitations
	$userhabilitationarray = $userHabilitation->fetchAllWithUser('ASC', 'u.lastname', 0, 0, array("u.statut" => 1));
	$user_static->fetchAll('', '', 0, 0, array("statut" => 1));
	$linearray = $userhabilitationarray;

	$habilitationarray = $Habilitation->fetchAll();

	// How the date for data are formated (format used bu jsgantt)
	$dateformatinput = 'yyyy-mm-dd';
	// How the date for data are formated (format used by dol_print_date)
	$dateformatinput2 = 'standard';

	$date_start = dol_get_first_day(dol_print_date($now, 'Y'), dol_print_date($now, 'm'));
	$date_end = dol_get_last_day(dol_print_date($now, 'Y') + 2, dol_print_date($now, 'm'));

	if (count($linearray) > 0) { // Show Gant diagram from $userhabilitationarray using JSGantt
		$dateformat = $langs->trans("FormatDateShortJQuery"); // Used by include ganttchart.inc.php later
		$datehourformat = $langs->trans("FormatDateShortJQuery").' '.$langs->trans("FormatHourShortJQuery"); // Used by include ganttchart.inc.php later
		
		$array_contacts = array();
		$lines = array();
		$task_dependencies = array();
		$habilitationcursor = 0;

		foreach ($linearray as $key => $val) {
			$userHabilitation = $val;

			$idparent = '-'.$val->fk_user; // If start with -, id is a project id

			$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
			$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1); // An id that has same order than position (required by ganttchart)
			$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
			$lines[$habilitationcursor]['line_parent'] = $idparent;
			$lines[$habilitationcursor]['line_is_group'] = 2;
			$lines[$habilitationcursor]['line_css'] = '';
			$lines[$habilitationcursor]['line_milestone'] = '0';
			//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
			$lines[$habilitationcursor]['line_name'] = $val->ref;
			$lines[$habilitationcursor]['line_start_date'] = '';
			$lines[$habilitationcursor]['line_end_date'] = '';
			$lines[$habilitationcursor]['line_resources'] = '';
			$lines[$habilitationcursor]['note'] = 'test_note';
			$lines[$habilitationcursor]['line_dataObject'] = array('date_debut_habilitation' => dol_print_date($val->date_habilitation, '%d/%m/%Y'), 'date_fin_habilitation' => dol_print_date($val->date_fin_habilitation, '%d/%m/%Y'));

			$habilitationcursor++;

			if(($val->date_fin_habilitation <= $date_start && !empty($val->date_fin_habilitation)) || $val->date_habilitation >= $date_end) { // Uniquement une barre rouge car l'habilitation est déja échue ou pas encoré obtenue
				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = $habilitationcursor;
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskred';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $date_start;
				$lines[$habilitationcursor]['line_end_date'] = $date_end;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;
			}
			elseif(($val->date_habilitation <= $date_start && ($val->date_fin_habilitation >= $date_end || empty($val->date_fin_habilitation)))) { // Uniquement une barre verte 
				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = $habilitationcursor;
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskgreen';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $date_start;
				$lines[$habilitationcursor]['line_end_date'] = $date_end;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;
			}
			elseif($val->date_habilitation <= $date_start && $val->date_fin_habilitation < $date_end) { // Barre verte puis rouge
				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = $habilitationcursor;
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskgreen';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $date_start;
				$lines[$habilitationcursor]['line_end_date'] = $val->date_fin_habilitation;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;

				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = ($habilitationcursor - 1);
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskred';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $val->date_fin_habilitation;
				$lines[$habilitationcursor]['line_end_date'] = $date_end;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;
			}
			elseif($val->date_habilitation > $date_start && ($val->date_fin_habilitation >= $date_end || empty($val->date_fin_habilitation))) { // Barre rouge puis verte
				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = $habilitationcursor;
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskred';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $date_start;
				$lines[$habilitationcursor]['line_end_date'] = $val->date_habilitation;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;

				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = ($habilitationcursor - 1);
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskgreen';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $val->date_habilitation;
				$lines[$habilitationcursor]['line_end_date'] = $date_end;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;
			}
			elseif($val->date_habilitation > $date_start && $val->date_fin_habilitation < $date_end) { // Barre rouge puis verte puis rouge : impossible normalement
				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = $habilitationcursor;
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskred';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $date_start;
				$lines[$habilitationcursor]['line_end_date'] = $val->date_habilitation;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;

				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = ($habilitationcursor - 1);
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskgreen';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $val->date_habilitation;
				$lines[$habilitationcursor]['line_end_date'] = $val->date_fin_habilitation;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;

				$lines[$habilitationcursor]['line_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_alternate_id'] = ($habilitationcursor + 1);
				$lines[$habilitationcursor]['line_habilitation_id'] = $val->fk_habilitation;
				$lines[$habilitationcursor]['line_user_id'] = $val->fk_user;
				$lines[$habilitationcursor]['line_parent'] = ($habilitationcursor - 2);
				$lines[$habilitationcursor]['line_is_group'] = 0;
				$lines[$habilitationcursor]['line_css'] = 'gtaskred';
				$lines[$habilitationcursor]['line_milestone'] = '0';
				//$lines[$habilitationcursor]['line_percent_complete'] = $val->progress;
				$lines[$habilitationcursor]['line_name'] = $val->ref;
				$lines[$habilitationcursor]['line_start_date'] = $val->date_fin_habilitation;
				$lines[$habilitationcursor]['line_end_date'] = $date_end;
				$lines[$habilitationcursor]['note'] = 'test_note';

				$habilitationcursor++;
			}
		}

		// Search parent to set line_parent_alternate_id (requird by ganttchart)
		foreach ($lines as $tmpkey => $tmpline) {
			foreach ($lines as $tmpline2) {
				if ($tmpline2['line_id'] == $tmpline['line_parent']) {
					$lines[$tmpkey]['line_parent_alternate_id'] = $tmpline2['line_alternate_id'];
					break;
				}
			}
			if (empty($lines[$tmpkey]['line_parent_alternate_id'])) {
				$lines[$tmpkey]['line_parent_alternate_id'] = $lines[$tmpkey]['line_parent'];
			}
		}

		print "\n";

		if (!empty($conf->use_javascript_ajax)) {
			$moreforfilter = '<div class="liste_titre liste_titre_bydiv centpercent">';

			$moreforfilter .= '<div class="divsearchfield">';
			//$moreforfilter .= $langs->trans("TasksAssignedTo").': ';
			//$moreforfilter .= $form->select_dolusers($tmpuser->id > 0 ? $tmpuser->id : '', 'search_user_id', 1);
			$moreforfilter .= '&nbsp;';
			$moreforfilter .= '</div>';

			$moreforfilter .= '</div>';

			print $moreforfilter;

			print '<div class="div-table-responsive" style="max-height: 500px; overflow: auto;">';

			print '<div id="tabs" class="gantt" style="width: 80vw;">'."\n";
			include_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/habilitations_ganttchart.inc.php';
			print '</div>'."\n";

			print '</div>';
		} else {
			$langs->load("admin");
			print $langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
		}
	} else {
		print '<div class="opacitymedium">'.$langs->trans("NoLines").'</div>';
	}
}

print '</div>';

// End of page
llxFooter();
$db->close();
