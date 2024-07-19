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
 *	\file       fep/fepindex.php
 *	\ingroup    fep
 *	\brief      Home page of fep top menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/fep/lib/fep.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fep/class/fep.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fep/class/Extendeddolgraph.class.php';

// Load translation files required by the page
$langs->loadLangs(array("fep@fep"));

$action = GETPOST('action', 'aZ09');

$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');
$yearid = GETPOST('yearid', 'int');

if (empty($yearid)){
	$yearid = (int)substr($db->idate(dol_now()), 0, 4);
}

/*if(empty($date_startmonth) || empty($date_startday) || empty($date_startyear)){
	$date_startmonth = 01;
	$date_startday = 01;
	$date_startyear = (int)substr($db->idate(dol_now()), 0, 4) - 1;
}

if(empty($date_endmonth) || empty($date_endday) || empty($date_endyear)){
	$date_endmonth = 01;
	$date_endday = 31;
	$date_endyear = (int)substr($db->idate(dol_now()), 0, 4);
}*/

$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);



// Security check
// if (! $user->rights->fep->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);

llxHeader("", "FEP/QS");

print load_fiche_titre('FEP/QS', '', 'object_fep_32.png@fep');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="tabBar tabBarWithBottom">';
print '<table class="border tableforfield centpercent"><tr>';
print '<td style="width: 250px;">'."Période d'analyse (moyenne)</td>";
print '<td>'.$form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0).'</td></tr>';
print '<tr><td style="width: 250px;">'."Année d'analyse (pourcentage de retour)</td>";
print '<td>';
$formother->select_year($yearid, 'yearid', 1);
print '</td></tr>';
print '</table></div>';
print '<div class="center"><input type="submit" class="button" name="submit" value="Rafraichir"></div></form><br>';


print '<div class="fichecenter"><div class="fichethirdleft">';

$tmp = getFepBarsChart($date_start, $date_end);
if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = getFepRetour($yearid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

print '</div>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$myobjectstatic = new Fep($db);

// Last modified myobject
if (!empty($conf->fep->enabled) && $user->rights->fep->fep->read)
{
	$sql = "SELECT s.rowid, s.ref, s.date_creation, s.tms";
	$sql.= " FROM ".MAIN_DB_PREFIX."fep_fep as s";
	//if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	//$sql.= " WHERE s.entity IN (".getEntity($myobjectstatic->element).")";
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
		print $langs->trans("Les dernières FEP (et QS) modifiées", $max);
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
				if(isset($objp->status)) {
					$myobjectstatic->status = $objp->status;
				}

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
}

print '</div></div></div>';

// End of page
llxFooter();
$db->close();
