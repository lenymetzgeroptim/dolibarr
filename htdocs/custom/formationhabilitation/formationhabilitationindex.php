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
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/extendedUser3.class.php';

// Load translation files required by the page
$langs->loadLangs(array("formationhabilitation@formationhabilitation"));

$action = GETPOST('action', 'aZ09');
$search_user = (!GETPOST('search_user', 'intcomma') && !GETPOST('search_user_multiselect', 'int') ? array($user->id) : explode(",", GETPOST('search_user', 'intcomma')));
$search_parent = (GETPOST('search_parent', 'intcomma') ? explode(",", GETPOST('search_parent', 'intcomma')) : array());
$onglet = (GETPOST('onglet', 'aZ') ? GETPOST('onglet', 'aZ') : 'formation');

// Security check
if (!$user->rights->formationhabilitation->userformation->read && !$user->rights->formationhabilitation->userformation->readall && $onglet == 'formation') {
	accessforbidden();
}
if (!$user->rights->formationhabilitation->userhabilitation_autorisation->read && !$user->rights->formationhabilitation->userhabilitation_autorisation->readall && $onglet == 'habilitation') {
	accessforbidden();
}
if (!$user->rights->formationhabilitation->userhabilitation_autorisation->read && !$user->rights->formationhabilitation->userhabilitation_autorisation->readall && $onglet == 'autorisation') {
	accessforbidden();
}
if (!$user->rights->formationhabilitation->uservolet->read && !$user->rights->formationhabilitation->uservolet->readall && $onglet == 'volet') {
	accessforbidden();
}
if (!$user->rights->formationhabilitation->visitemedical->read && !$user->rights->formationhabilitation->visitemedical->readall && $onglet == 'visitemedical') {
	accessforbidden();
}

$disableduserfilter = 0;
if (!$user->rights->formationhabilitation->userformation->readall && $onglet == 'formation') {
	$search_user = array($user->id);
	$disableduserfilter = 1;
}
if (!$user->rights->formationhabilitation->userhabilitation_autorisation->readall && $onglet == 'habilitation') {
	$search_user = array($user->id);
	$disableduserfilter = 1;
}
if (!$user->rights->formationhabilitation->userhabilitation_autorisation->readall && $onglet == 'autorisation') {
	$search_user = array($user->id);
	$disableduserfilter = 1;
}
if (!$user->rights->formationhabilitation->uservolet->readall && $onglet == 'volet') {
	$search_user = array($user->id);
	$disableduserfilter = 1;
}
if (!$user->rights->formationhabilitation->visitemedical->readall && $onglet == 'visitemedical') {
	$search_user = array($user->id);
	$disableduserfilter = 1;
}

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
$userFormation = new UserFormation($db);
$formation = new Formation($db);
$userHabilitation = new UserHabilitation($db);
$habilitation = new Habilitation($db);
$userAutorisation = new UserAutorisation($db);
$autorisation = new Autorisation($db);
$userVolet = new UserVolet($db);
$volet = new Volet($db);
$visitemedical = new VisiteMedical($db);
$user_static = new ExtendedUser3($db);

$arrayofcss = array('/includes/jsgantt/jsgantt.css');

if (!empty($conf->use_javascript_ajax)) {
	$arrayofjs = array(
	'/custom/formationhabilitation/js/jsgantt.js',
	'/projet/jsgantt_language.js.php?lang='.$langs->defaultlang
	);
}

llxHeader("", $langs->trans("FormationHabilitationArea"), '', '', 0, 0, $arrayofjs, $arrayofcss, '', 'classforhorizontalscrolloftabs formationhabilitation');

print load_fiche_titre($langs->trans("FormationHabilitationArea"), '', 'object_module_32@formationhabilitation');

print '<div class="fichecenter">';

$head = formationhabilitationIndexPrepareHead($object);

if($onglet == 'formation') {
	print dol_get_fiche_head($head, 'formation', 'Formation', -1, 'fontawesome_fa-user-graduate_fas_#1f3d89');
}
elseif($onglet == 'habilitation') {
	print dol_get_fiche_head($head, 'habilitation', 'Habilitation', -1, 'fontawesome_fa-user-gear_fas_#c46c0e');
}
elseif($onglet == 'autorisation') {
	print dol_get_fiche_head($head, 'autorisation', 'Autorisation', -1, 'fontawesome_fa-user-check_fas_green');
}
elseif($onglet == 'volet') {
	print dol_get_fiche_head($head, 'volet', 'Volet', -1, 'fontawesome_fa-book_fas_#004a95');
}
elseif($onglet == 'visitemedical') {
	print dol_get_fiche_head($head, 'visitemedical', 'Visite Medicale', -1, 'fontawesome_fa-hospital-alt_fas_#b4161b');
}

// Filres 
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="onglet" value="'.$onglet.'">';

	print '<table class="table_filter noborder centpercent">';
		print '<tr class="liste_titre">';
			print '<td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td>';
		print '</tr>';

		// Ligne 1 : Salariés, Emploi, Compétence
		print '<tr>';
			print '<td class="">';
				print $langs->trans("User") . ' &nbsp;';
				print img_picto('', 'user', 'class="pictofixedwidth"');
				$user_array = $user_static->getAllUserName();
				print $form->multiselectarray('search_user', $user_array, $search_user, 0, 0, '', 0, 0, ($disableduserfilter ? ' disabled' : ''), '', 'Tout le monde');
			print '</td>';

			print '<td class="">';
				if($onglet == 'formation') {
					print $langs->trans("Formations") . ' &nbsp;';
					print img_picto('', 'fa-graduation-cap_fas_#1f3d89', 'class="pictofixedwidth"');
					$formation_array = $formation->getAllFormationLabel();
					print $form->multiselectarray('search_parent', $formation_array, $search_parent, 0, 0, '', 0, 0, '', '', 'Toutes les formations');
				}
				elseif($onglet == 'habilitation') {
					print $langs->trans("Habilitation") . ' &nbsp;';
					print img_picto('', 'fa-cog_fa_#c46c0e', 'class="pictofixedwidth"');
					$habilitation_array = $habilitation->getAllHabilitationLabel();
					print $form->multiselectarray('search_parent', $habilitation_array, $search_parent, 0, 0, '', 0, 0, '', '', 'Toutes les habilitation');
				}
				elseif($onglet == 'autorisation') {
					print $langs->trans("Autorisations") . ' &nbsp;';
					print img_picto('', 'fa-check_fas_green', 'class="pictofixedwidth"');
					$autorisation_array = $autorisation->getAllAutorisationLabel();
					print $form->multiselectarray('search_parent', $autorisation_array, $search_parent, 0, 0, '', 0, 0, '', '', 'Toutes les autorisations');
				}
				elseif($onglet == 'volet') {
					print $langs->trans("Volets") . ' &nbsp;';
					print img_picto('', 'fa-book_fas_#004a95', 'class="pictofixedwidth"');
					$volet_array = $volet->getAllVoletLabel();
					print $form->multiselectarray('search_parent', $volet_array, $search_parent, 0, 0, '', 0, 0, '', '', 'Tous les volets');
				}
				elseif($onglet == 'visitemedical') {
					// print $langs->trans("VisiteMedical") . ' &nbsp;';
					// print img_picto('', 'fa-hospital-alt_fas_#b4161b', 'class="pictofixedwidth"');
					// $visitemedical_array = $visitemedical->getAllVisiteMedicalLabel();
					// print $form->multiselectarray('search_visitemedical', $visitemedical_array, $search_visitemedical, 0, 0, '', 0, 0, '', '', 'Toutes les visites médicale');
				}
			print '</td>';
		print '</tr>';
	print '</table>';

	print '<div class="center">';
	print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Search").'">';
	print '</div>';
print '</form><br>';


// Diagramme de Gantt
if($onglet == 'formation') {
	// Get list of formation
	$linearray = $userFormation->fetchAllWithUser('ASC', 'u.lastname', 0, 0, array("u.statut" => 1, "t.fk_user" => implode(",", $search_user), "t.fk_formation" => implode(",", $search_parent)));
}
elseif($onglet == 'habilitation') {
	// Get list of habilitations
	$linearray = $userHabilitation->fetchAllWithUser('ASC', 'u.lastname', 0, 0, array("u.statut" => 1, "t.fk_user" => implode(",", $search_user), "t.fk_habilitation" => implode(",", $search_parent)));
}
elseif($onglet == 'autorisation') {
	// Get list of autorisation
	$linearray = $userAutorisation->fetchAllWithUser('ASC', 'u.lastname', 0, 0, array("u.statut" => 1, "t.fk_user" => implode(",", $search_user), "t.fk_autorisation" => implode(",", $search_parent)));
}
elseif($onglet == 'volet') {
	// Get list of volet
	$linearray = $userVolet->fetchAllWithUser('ASC', 'u.lastname', 0, 0, array("u.statut" => 1, "t.fk_user" => implode(",", $search_user), "t.fk_volet" => implode(",", $search_parent)));
}
elseif($onglet == 'visitemedical') {
	// Get list of visitemedical
	$linearray = $visitemedical->fetchAllWithUser('ASC', 'u.lastname', 0, 0, array("u.statut" => 1, "t.fk_user" => implode(",", $search_user)));
}
$user_static->fetchAll('', '', 0, 0, array("statut" => 1));

//$habilitationarray = $habilitation->fetchAll();

// How the date for data are formated (format used bu jsgantt)
$dateformatinput = 'yyyy-mm-dd';
// How the date for data are formated (format used by dol_print_date)
$dateformatinput2 = 'standard';

$date_start = dol_get_first_day(dol_print_date($now, 'Y'), dol_print_date($now, 'm'));
$date_end = dol_get_last_day(dol_print_date($now, 'Y') + 3, dol_print_date($now, 'm'));

if (count($linearray) > 0) { // Show Gant diagram from $userhabilitationarray using JSGantt
	$dateformat = $langs->trans("FormatDateShortJQuery"); // Used by include ganttchart.inc.php later
	$datehourformat = $langs->trans("FormatDateShortJQuery").' '.$langs->trans("FormatHourShortJQuery"); // Used by include ganttchart.inc.php later
	
	$array_contacts = array();
	$lines = array();
	$task_dependencies = array();
	$cursor = 0;

	foreach ($linearray as $key => $val) {
		if($onglet == 'formation') {
			$date_debut = $val->date_fin_formation;
			$date_fin = $val->date_finvalidite_formation;
		}
		elseif($onglet == 'habilitation') {
			$date_debut = $val->date_habilitation;
			$date_fin = $val->date_fin_habilitation;
		}
		elseif($onglet == 'autorisation') {
			$date_debut = $val->date_autorisation;
			$date_fin = $val->date_fin_autorisation;
		}
		elseif($onglet == 'volet') {
			$date_debut = $val->datedebutvolet;
			$date_fin = $val->datefinvolet;
		}
		elseif($onglet == 'visitemedical') {
			$date_debut = $val->datevisite;
			$date_fin = $val->datefinvalidite;
		}

		//$userHabilitation = $val;

		$idparent = '-'.$val->fk_user; // If start with -, id is a project id

		$lines[$cursor]['line_id'] = ($cursor + 1);
		$lines[$cursor]['line_alternate_id'] = ($cursor + 1); // An id that has same order than position (required by ganttchart)
		// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
		$lines[$cursor]['line_user_id'] = $val->fk_user;
		$lines[$cursor]['line_parent'] = $idparent;
		$lines[$cursor]['line_is_group'] = 2;
		$lines[$cursor]['line_css'] = '';
		$lines[$cursor]['line_milestone'] = '0';
		//$lines[$cursor]['line_percent_complete'] = $val->progress;
		$lines[$cursor]['line_name'] = $val->ref;
		$lines[$cursor]['line_start_date'] = '';
		$lines[$cursor]['line_end_date'] = '';
		$lines[$cursor]['line_resources'] = '';
		$lines[$cursor]['note'] = 'test_note';
		$lines[$cursor]['line_dataObject'] = array('date_debut' => dol_print_date($date_debut, '%d/%m/%Y'), 'date_fin' => dol_print_date($date_fin, '%d/%m/%Y'));

		$cursor++;

		if(($date_fin <= $date_start && !empty($date_fin)) || $date_debut >= $date_end) { // Uniquement une barre rouge car déja échue ou pas encoré obtenue
			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = $cursor;
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskred';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_start;
			$lines[$cursor]['line_end_date'] = $date_end;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;
		}
		elseif(($date_debut <= $date_start && ($date_fin >= $date_end || empty($date_fin)))) { // Uniquement une barre verte 
			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = $cursor;
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskgreen';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_start;
			$lines[$cursor]['line_end_date'] = $date_end;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;
		}
		elseif($date_debut <= $date_start && $date_fin < $date_end) { // Barre verte puis rouge
			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = $cursor;
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskgreen';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_start;
			$lines[$cursor]['line_end_date'] = $date_fin;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;

			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = ($cursor - 1);
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskred';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_fin;
			$lines[$cursor]['line_end_date'] = $date_end;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;
		}
		elseif($date_debut > $date_start && ($date_fin >= $date_end || empty($date_fin))) { // Barre rouge puis verte
			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = $cursor;
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskred';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_start;
			$lines[$cursor]['line_end_date'] = $date_debut;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;

			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = ($cursor - 1);
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskgreen';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_debut;
			$lines[$cursor]['line_end_date'] = $date_end;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;
		}
		elseif($date_debut > $date_start && $date_fin < $date_end) { // Barre rouge puis verte puis rouge : impossible normalement
			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = $cursor;
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskred';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_start;
			$lines[$cursor]['line_end_date'] = $date_debut;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;

			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = ($cursor - 1);
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskgreen';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_debut;
			$lines[$cursor]['line_end_date'] = $date_fin;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;

			$lines[$cursor]['line_id'] = ($cursor + 1);
			$lines[$cursor]['line_alternate_id'] = ($cursor + 1);
			// $lines[$cursor]['line_habilitation_id'] = $val->fk_habilitation;
			$lines[$cursor]['line_user_id'] = $val->fk_user;
			$lines[$cursor]['line_parent'] = ($cursor - 2);
			$lines[$cursor]['line_is_group'] = 0;
			$lines[$cursor]['line_css'] = 'gtaskred';
			$lines[$cursor]['line_milestone'] = '0';
			//$lines[$cursor]['line_percent_complete'] = $val->progress;
			$lines[$cursor]['line_name'] = $val->ref;
			$lines[$cursor]['line_start_date'] = $date_fin;
			$lines[$cursor]['line_end_date'] = $date_end;
			$lines[$cursor]['note'] = 'test_note';

			$cursor++;
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
		print '<div class="div-table-responsive">';
		print '<div class="liste_titre liste_titre_bydiv centpercent">'.'</div>';

		print '<div id="tabs" class="gantt"">'."\n";
		include_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/habilitations_ganttchart.inc.php';
		print '</div>'."\n";

		print '</div>';
	} else {
		$langs->load("admin");
		print $langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
	}
} else {
	if($onglet == 'formation') {
		print '<div class="info">'.$langs->trans("NoFormation").'</div>';
	}
	elseif($onglet == 'habilitation') {
		print '<div class="info">'.$langs->trans("NoHabilitation").'</div>';
	}
	elseif($onglet == 'autorisation') {
		print '<div class="info">'.$langs->trans("NoAutorisation").'</div>';
	}
	elseif($onglet == 'volet') {
		print '<div class="info">'.$langs->trans("NoVolet").'</div>';
	}
	elseif($onglet == 'visitemedical') {
		print '<div class="info">'.$langs->trans("NoVisiteMedicale").'</div>';
	}
}


print '</div>';

// End of page
llxFooter();
$db->close();
