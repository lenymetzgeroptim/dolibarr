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
 *	\file       q3serp/q3serpindex.php
 *	\ingroup    q3serp
 *	\brief      Home page of q3serp top menu
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
$langs->loadLangs(array("q3serp@q3serp"));

$action = GETPOST('action', 'aZ09');

$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('q3serp')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('q3serp', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'q3serp', 0, 'q3serp_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("Q3SERPArea"));

print load_fiche_titre($langs->trans("Q3SERPArea"), '', 'q3serp.png@q3serp');


function afficherTableaux() {
	global $conf;

    echo '
	
    <span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><strong><u>Nombre de jour sans accident </u>:</strong></span></span>

    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; height:158px; margin-top:10px; width:50%">
        <tbody>
            <tr>
                <th scope="row" style="background-color:#2f508b"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff">Avec arrêt</span></span></span></th>
                <td style="background-color:#2f508b; text-align:center; width:25%"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff"><strong>'.$conf->global->JOUR_SANS_ACC_AVEC_ARRET.'</strong></span></span></span></td>
            </tr>
            <tr>
                <th colspan="2" scope="row" style="background-color:#ffffff; text-align:left"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#c0392b">'.$conf->global->JOUR_SANS_ACC_AVEC_ARRET_COM.'</span></span></span></th>
            </tr>
            <tr>
                <th scope="row" style="background-color:#cccccc"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff">Sans arrêt</span></span></span></th>
                <td style="background-color:#cccccc; text-align:center"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff"><strong>'.$conf->global->JOUR_SANS_ACC_SANS_ARRET.'</strong></span></span></span></td>
            </tr>
            <tr>
                <th colspan="2" scope="row" style="background-color:#ffffff; text-align:left"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#c0392b">'.$conf->global->JOUR_SANS_ACC_SANS_ARRET_COM.'</span></span></span></th>
            </tr>
            <tr>
                <th scope="row" style="background-color:#2f508b"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff">De trajet</span></span></span></th>
                <td style="background-color:#2f508b; text-align:center"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff"><strong>'.$conf->global->JOUR_SANS_ACC_TRAJET.'</strong></span></span></span></td>
            </tr>
            <tr>
                <th colspan="2" scope="row" style="text-align:left"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#c0392b">'.$conf->global->JOUR_SANS_ACC_TRAJET_COM.'</span></span></span></th>
            </tr>
        </tbody>
    </table>

    <div><br /><br />
    <span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><strong><u>Remontées du mois de '.$conf->global->__MONTH_TEXT__.' </u>: (<a href="http://erp.optim-industries.fr/custom/remonteessse/remontees_sse_list.php">Liste complète</a>)</strong></span></span>

    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; height:158px; margin-top:10px; width:50%">
        <tbody>
            <tr>
                <th scope="row" style="background-color:#2f508b; width:25%"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff"><strong>VDR-NORD </strong></span></span></span></th>
                <td style="background-color:#2f508b; width:75%"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><strong><span style="color:#ffffff">'.$conf->global->REMONTEESSSE_VDRNORD.'</span></strong></span></span></td>
            </tr>
            <tr>
                <th scope="row" style="background-color:#cccccc"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff"><strong>GRAND-OUEST</strong></span></span></span></th>
                <td style="background-color:#cccccc"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><strong><span style="color:#ffffff">'.$conf->global->REMONTEESSSE_GRANDOUEST.'</span></strong></span></span></td>
            </tr>
            <tr>
                <th scope="row" style="background-color:#2f508b"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><span style="color:#ffffff"><strong>SUD-EST</strong></span></span></span></th>
                <td style="background-color:#2f508b"><span style="font-family:Arial,Helvetica,sans-serif"><span style="font-size:14px"><strong><span style="color:#ffffff">'.$conf->global->REMONTEESSSE_SUDEST.'</span></strong></span></span></td>
            </tr>
        </tbody>
    </table>';
}


afficherTableaux();
/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('q3serp') && $user->rights->q3serp->read)
{
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftMyObjects").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$myobjectstatic->id=$obj->rowid;
				$myobjectstatic->ref=$obj->ref;
				$myobjectstatic->ref_client=$obj->ref_client;
				$myobjectstatic->total_ht = $obj->total_ht;
				$myobjectstatic->total_tva = $obj->total_tva;
				$myobjectstatic->total_ttc = $obj->total_ttc;

				print $myobjectstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */


print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('q3serp') && $user->rights->q3serp->read)
{
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
	$sql.= " FROM ".MAIN_DB_PREFIX."q3serp_myobject as s";
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
*/

print '</div></div>';

// End of page
llxFooter();
$db->close();
