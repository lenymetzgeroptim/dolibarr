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
 *	\file       gpec/gpecindex.php
 *	\ingroup    gpec
 *	\brief      Home page of gpec top menu
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

require_once DOL_DOCUMENT_ROOT.'/custom/gpec/lib/gpec.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/lib/fod.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("gpec@gpec"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->gpec->myobject->read) {
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

llxHeader("", $langs->trans("GPECArea"));

print load_fiche_titre($langs->trans("GPECArea"), '', 'object_gpec_32@gpec');

print '<div class="fichecenter"><div class="fichethirdleft">';

$tmp = getGPECPieChart(1);
if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = getGPECPieChart(2);
if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = getGPECPieChart(3);
if ($tmp) {
	print $tmp;
	print '<br>';
}

print '</div><div class="fichetwothirdright">';

$sql = "SELECT d.nom, AVG(l.niveau) - 1 as moyenne";
$sql.= " FROM ".MAIN_DB_PREFIX."gpec_competencedomaine_level_user as l";
$sql.= " RIGHT JOIN ".MAIN_DB_PREFIX."gpec_competencesElementaires  as c ON c.rowid = l.fk_competence";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."gpec_domaines as d ON d.rowid = c.domaine";
$sql.= " WHERE c.active = 1 AND d.active = 1 AND l.niveau <> 0";
$sql .= " GROUP BY d.nom";
$sql .= " ORDER BY d.nom";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="center">';
	print 'Compétence Domaine';
	print '</th>';
	print '<th class="center">'."Moyenne".'</th>';
	print '</tr>';
	if ($num)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap center">'.$objp->nom.'</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->moyenne, 2)."</td>";
			print '</tr>';
			$i++;
		}

		$db->free($resql);
	} else {
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print "</table><br>";
}

$sql = "SELECT c.competence, AVG(l.niveau) - 1 as moyenne";
$sql.= " FROM ".MAIN_DB_PREFIX."gpec_competencetransverse_level_user as l";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."gpec_competencesTransverses  as c ON c.rowid = l.fk_competence";
$sql.= " WHERE c.active = 1 AND l.niveau <> 0";
$sql .= " GROUP BY c.competence";
$sql .= " ORDER BY c.competence";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="center">';
	print 'Compétence Transverse';
	print '</th>';
	print '<th class="center">'."Moyenne".'</th>';
	print '</tr>';
	if ($num)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap center">'.$objp->competence.'</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->moyenne, 2)."</td>";
			print '</tr>';
			$i++;
		}

		$db->free($resql);
	} else {
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print "</table><br>";
}



$sql = "SELECT SUM(is_td) as td, SUM(is_t) as t, SUM(is_te) as te, SUM(is_tc) as tc, SUM(is_id) as id, SUM(is_i) as i, SUM(is_ic) as ic, SUM(is_ie) as ie";
$sql.= " FROM ".MAIN_DB_PREFIX."gpec_matricecompetence as m";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = m.fk_user";
$sql.= " WHERE u.statut = 1";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="center">';
	print 'Classification Professionnelle par profil';
	print '</th>';
	print '<th class="center">'."Nombre total".'</th>';
	print '</tr>';
	if ($num)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap center">TD</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->td."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">T</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->t."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">TE</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->te."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">TC</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->tc."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">ID</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->id."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">I</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->i."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">IC</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->ic."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">IE</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->ie."</td>";
			print '</tr>';
			$i++;
		}

		$db->free($resql);
	} else {
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print "</table><br>";
}




$sql = "SELECT SUM(chef_projet) as chef_projet, SUM(pilote_affaire) as pilote_affaire, SUM(ingenieur_confirme) as ingenieur_confirme, SUM(preparateur_charge_affaire) as preparateur_charge_affaire, SUM(preparateur_methodes) as preparateur_methodes,";
$sql .= " SUM(charge_affaires_elec_auto) as charge_affaires_elec_auto, SUM(electricien) as electricien, SUM(charge_affaires_mecanique) as charge_affaires_mecanique, SUM(mecanicien) as mecanicien, SUM(robinettier) as robinettier,";
$sql .= " SUM(pcr_operationnel) as pcr_operationnel, SUM(technicien_rp) as technicien_rp, SUM(charge_affaires_multi_specialites) as charge_affaires_multi_specialites";
$sql.= " FROM ".MAIN_DB_PREFIX."gpec_matricecompetence as m";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = m.fk_user";
$sql.= " WHERE u.statut = 1";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="center">';
	print 'FONCTIONS - PROFILS';
	print '</th>';
	print '<th class="center">'."Nombre total".'</th>';
	print '</tr>';
	if ($num)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Chef de projet</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->chef_projet."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Pilote d\'affaire</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->pilote_affaire."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Ingénieur Confirmé</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->ingenieur_confirme."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Préparateur chargé d\'affaire ancrage/supportage</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->preparateur_charge_affaire."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Préparateur méthodes</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->preparateur_methodes."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Chargé d\'affaires PIAT électricité/automatisme</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->charge_affaires_elec_auto."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Electricien</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->electricien."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Chargé d\'affaires PIAT mécanique</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->charge_affaires_mecanique."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Mécanicien</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->mecanicien."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Robinettier</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->robinettier."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">PCR opérationnel</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->pcr_operationnel."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Technicien RO</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->technicien_rp."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Chargé d\'affaires multi-spécialités</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.$objp->charge_affaires_multi_specialites."</td>";
			print '</tr>';

			$i++;
		}

		$db->free($resql);
	} else {
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print "</table><br>";
}



$sql = "SELECT AVG(NULLIF(mec_machine_tournante,0)) - 1 as mec_machine_tournante, AVG(NULLIF(robinetterie,0)) - 1 as robinetterie, AVG(NULLIF(chaudronnerie,0)) - 1 as chaudronnerie, AVG(NULLIF(tuyauterie_soudage,0)) - 1 as tuyauterie_soudage, AVG(NULLIF(automatisme,0)) - 1 as automatisme,";
$sql .= " AVG(NULLIF(electricite,0)) - 1 as electricite, AVG(NULLIF(ventilation,0)) - 1 as ventilation, AVG(NULLIF(logistique,0)) - 1 as logistique, AVG(NULLIF(securite,0)) - 1 as securite, AVG(NULLIF(soudage,0)) - 1 as soudage";
$sql.= " FROM ".MAIN_DB_PREFIX."gpec_matricecompetence as m";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = m.fk_user";
$sql.= " WHERE u.statut = 1";


$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="center">';
	print 'REFERENCE METIER ET DOMAINE METHODE';
	print '</th>';
	print '<th class="center">'."Moyenne".'</th>';
	print '</tr>';
	if ($num)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Mécanique Machine Tournante</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->mec_machine_tournante, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Robinetterie</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->robinetterie, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Chaudonnerie</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->chaudronnerie, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Tuyauterie/Soudage</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->tuyauterie_soudage, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Automatisme</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->automatisme, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Electricité</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->electricite, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Ventilation</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->ventilation, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Logistique</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->logistique, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Sécurité</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->securite, 2)."</td>";
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td class="nowrap center">Soudage</td>';
			print '<td style="width: 40%" class="center nowrap bold" style="font-size: 17px">'.round($objp->soudage, 2)."</td>";
			print '</tr>';
			$i++;
		}

		$db->free($resql);
	} else {
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print "</table><br>";
}


print '</div></div>';

// End of page
llxFooter();
$db->close();
