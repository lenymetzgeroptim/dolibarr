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
 *	\file       sse/sseindex.php
 *	\ingroup    sse
 *	\brief      Home page of sse top menu
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/goalelement.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieattendance.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/lib/sse_causerie.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/lib/sse_causerieattendance.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerie.class.php';


// Load translation files required by the page
$langs->loadLangs(array("sse@sse"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->sse->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}



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





// None


/*
 * View
 */

$form = new Form($db);
$form1 = new Form($db);
$formfile = new FormFile($db);
$goal_element = new GoalElement($db);
$causerie_element = new CauserieAttendance($db);

// Class non trouvée erreur à corriger 
if (isModEnabled('ficheinter')) {
	//$fichinterstatic = new Fichinter($db);
}
llxHeader("", $langs->trans("Tableau de Bord"));

print load_fiche_titre($langs->trans("Tableau de Bord des Causeries"), '', 'fa-comments');

print '<div class="fichecenter"><div class="fichethirdleft">';
$tmp = $causerie_element->getCauserieByYearChart();

if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = $causerie_element->getCauserieByPresenceChart();

if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = $causerie_element->getAllCauserieByThemePieChart();

if ($tmp) {
	print $tmp;
	print '<br>';
}

if (empty($date_start)) {
	$date_start = dol_get_first_day(dol_print_date($now, 'Y'));
}

if (empty($date_end)) {
	$date_end = $now;
}

if (empty($date_start1)) {
	$date_start1 = dol_get_first_day(dol_print_date($now, 'Y'));
}

if (empty($date_end1)) {
	$date_end1 = $now;
}



$tmp = $causerie_element->getCauserieByThemePieChart($date_start, $date_end);

if ($tmp) {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder nohover centpercent">';

	print  '<tr class="liste_titre">';
	print  '<td colspan="5">'.$langs->trans("Statistics").'  '.$langs->trans("Répartition des Thèmes par Période").'</td>';
	//print  '<td class="right"> &nbsp;</td>';
	print  '</tr>';
	
	// print '<div class="div-table-responsive-no-min">';
	// print '<table class="noborder nohover centpercent">';
	print '<tr>';
	print '<td >Période de recherche</td>';
	print '<td colspan="3">'.$form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0).' &nbsp; '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0).'</td>';
	print '<td class="enter"><div class="center"><input type="submit" class="smallpaddingimp button" name="submit" value="Rafraichir"></div></td>';
	print '</tr>';
	print '<tr></tr>';
	print $tmp;
	print '<br>';
}

print '</div>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

//print '</div><div class="fichetwothirdright">';
$projectstatic = new Causerie($db);

// Latest modified projects
$sql = "SELECT";
$sql .= " c.rowid,";
$sql .= " c.tms,";
$sql .= " c.ref,";
$sql .= " t.label as causerie_theme,";
$sql .= " c.status,";
$sql .= " c.local,";
$sql .= " c.subtheme,";
$sql .= " c.description,";
$sql .= " c.note_public,";
$sql .= " c.note_private,";
$sql .= " c.date_creation,";
$sql .= " c.date_debut,";
$sql .= " c.date_fin,";
$sql .= " c.tms,";
$sql .= " c.fk_user_creat,";
$sql .= " c.fk_user_modif,";
$sql .= " c.last_main_doc,";
$sql .= " c.import_key,";
$sql .= " c.model_pdf";
$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as ce on ce.fk_object = c.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON t.rowid = ce.thme";

$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="5">'.$langs->trans("Les $max Dernières Causeries Modifiées").'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap">';

			$projectstatic->id = $obj->rowid;
			$projectstatic->ref = $obj->ref;
			$projectstatic->title = $obj->tms;
			$projectstatic->status = $obj->status;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowraponall">';
			print $projectstatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($projectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			// Label
			// print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->title).'">';
			// print $projectstatic->title;
			print '</td>';

			// Thirdparty
			print '<td class="nowrap">';
			if ($companystatic->id > 0) {
				print $companystatic->getNomUrl(1, 'company', 16);
			}
			print '</td>';

			// Date
			$datem = $db->jdate($obj->tms);
			print '<td class="center" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
			print 'Modifié le '.dol_print_date($datem, 'day', 'tzuserrel');
			print '</td>';

			// Status
			print '<td class="right">'.$projectstatic->LibStatut($obj->status, 3).'</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table></div>";
} else {
	dol_print_error($db);
}

print '<br>';

// Causeries en cours
$sql = "SELECT";
$sql .= " c.rowid,";
$sql .= " c.tms,";
$sql .= " c.ref,";
$sql .= " t.label as causerie_theme,";
$sql .= " c.status,";
$sql .= " c.local,";
$sql .= " c.subtheme,";
$sql .= " c.description,";
$sql .= " c.note_public,";
$sql .= " c.note_private,";
$sql .= " c.date_creation,";
$sql .= " c.date_debut,";
$sql .= " c.date_fin,";
$sql .= " c.tms,";
$sql .= " c.fk_user_creat,";
$sql .= " c.fk_user_modif,";
$sql .= " c.last_main_doc,";
$sql .= " c.import_key,";
$sql .= " c.model_pdf";
$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as ce ON c.rowid = ce.fk_object";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON t.rowid = ce.thme";
$sql .= " WHERE c.status > 0";
$sql .= " AND c.status < 6";

$sql .= " ORDER BY c.tms DESC";
//$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="5">'.$langs->trans("Causeries en Cours").'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap">';

			$projectstatic->id = $obj->rowid;
			$projectstatic->ref = $obj->ref;
			$projectstatic->title = $obj->date_debut;
			$projectstatic->status = $obj->status;

		
			

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowraponall">';
			print $projectstatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($projectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			// Label
			// print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->title).'">';
			// print $projectstatic->title;
			print '</td>';

			// Thirdparty
			print '<td class="nowrap">';
			if ($companystatic->id > 0) {
				print $companystatic->getNomUrl(1, 'company', 16);
			}
			print '</td>';

			// Date
			$datem = $db->jdate($obj->date_debut);
			print '<td class="center" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
			print 'Date de début le '.dol_print_date($datem, 'day', 'tzuserrel');
			print '</td>';

			// Status
			print '<td class="right">'.$projectstatic->LibStatut($obj->status, 3).'</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table></div>";
} else {
	dol_print_error($db);
}

print '<br>';

// Latest closed causeries
$sql = "SELECT";
$sql .= " c.rowid,";
$sql .= " c.tms,";
$sql .= " c.ref,";
$sql .= " t.label as causerie_theme,";
$sql .= " c.status,";
$sql .= " c.local,";
$sql .= " c.subtheme,";
$sql .= " c.description,";
$sql .= " c.note_public,";
$sql .= " c.note_private,";
$sql .= " c.date_creation,";
$sql .= " c.date_debut,";
$sql .= " c.date_fin,";
$sql .= " c.tms,";
$sql .= " c.fk_user_creat,";
$sql .= " c.fk_user_modif,";
$sql .= " c.last_main_doc,";
$sql .= " c.import_key,";
$sql .= " c.model_pdf";
$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as ce ON ce.fk_object = c.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON t.rowid = ce.thme";
$sql .= " WHERE c.status > 0";
$sql .= " AND c.status = 6";

$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="5">'.$langs->trans("Les $max Dernières Causeries Clôturées").'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap">';

			$projectstatic->id = $obj->rowid;
			$projectstatic->ref = $obj->ref;
			$projectstatic->title = $obj->date_fin;
			$projectstatic->status = $obj->status;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowraponall">';
			print $projectstatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($projectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			// Label
			// print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->title).'">';
			// print $projectstatic->title;
			print '</td>';

			// Thirdparty
			print '<td class="nowrap">';
			if ($companystatic->id > 0) {
				print $companystatic->getNomUrl(1, 'company', 16);
			}
			print '</td>';

			// Date
			$datem = $db->jdate($obj->date_fin);
			print '<td class="center" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
			print 'Date de fin le '.dol_print_date($datem, 'day', 'tzuserrel');
			print '</td>';

			// Status
			print '<td class="right">'.$projectstatic->LibStatut($obj->status, 3).'</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table></div>";
} else {
	dol_print_error($db);
}

print '</div>';



print '</div><div class="fichecenter"><div class="fichethirdleft">';

print '</div">';


print '</div><div class="fichetwothirdright">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
//if (! empty($conf->sse->enabled) && $user->rights->sse->read)
//{
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
	$sql.= " FROM ".MAIN_DB_PREFIX."sse_causerie as s";
	//if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.entity IN (".getEntity($myobjectstatic->element).")";
	//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	//if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->label=$objp->label;
				$myobjectstatic->status = $objp->status;
var_dump($objp->rowid);
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
//}
*/

print '</div></div>';

// End of page
llxFooter();
$db->close();
