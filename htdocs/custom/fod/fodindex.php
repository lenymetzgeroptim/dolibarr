<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       fod/fodindex.php
 *	\ingroup    fod
 *	\brief      Home page of fod top menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/lib/fod.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("fod@fod"));

$action = GETPOST('action', 'aZ09');


// Security check
if (empty($conf->fod->enabled)) {
	accessforbidden('Module non activé');
}
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();

// Vérifie si l'utilisateur actif est un PCR
$user_group = New UserGroup($db);
$user_group->fetch(11);
$liste_PCR = $user_group->listUsersForGroup();
$userPCR = 0;
foreach($liste_PCR as $pcr){
	if ($pcr->id == $user->id){
		$userPCR = 1;
		break;
	}
}


// Vérifie si l'utilisateur actif est un RA
$user_group->fetch(8);
$liste_RA = $user_group->listUsersForGroup();
$userRA = 0;
foreach($liste_RA as $ra){
	if ($ra->id == $user->id){
		$userRA = 1;
		break;
	}
}

/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$fod = new Fod($db);

$FodListId = '';
$FodListId = $fod->getFodAuthorizedForUser($user, 1);

llxHeader("", $langs->trans("FODArea"));

print load_fiche_titre($langs->trans("FODArea"), '', 'object_fod_32@fod');

print '<div class="fichecenter"><div class="fichethirdleft">';

$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

// Graphe Statut FOD
$tmp = getFodPieChart($FodListId);
if ($tmp) {
	print $tmp;
	print '<br>';
}

// Doses des FOD actives
if($userPCR || $userRA || $user->admin){
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre" style="text-align: center !important;">';
	print '<th colspan="">';
	print 'Doses des FOD actives';
	print '</th>';
	print '<th>Contrainte</th>';
	print '<th>Dose actuelle max</th>';
	print '<th>Ratio</th>';
	print '</tr>';

	$filter = array();
	//$filter['status'] = '4';
	if($userRA && !$userPCR && !$user->admin){
		$project = new Project($db);
		$projectListResp = $project->getProjectsAuthorizedForUser($user, 0, 1, 0, " AND ec.fk_c_type_contact = 160");
		$filter['fk_project'] = $projectListResp;
		$filter['fk_user_raf'] = $user->id;
	}

	$fod_user = New Fod_user($db);
	$fod_active = $fod->fetchAll('', '', 0, 0, $filter, 'OR', 1);
	$donnees = array();
	$u = 0;
	foreach($fod_active as $une_fod){
		$cdd_cdi_max = 0;
		$cdd_autre_tab = array();
		$cdd_autre_max_tab = array();
		$i = 0;
		foreach($une_fod->listIntervenantsForFod() as $intervenant){
			$fod_user_id = $fod_user->getIdWithUserAndFod($intervenant->id, $une_fod->id);
			$fod_user->fetch($fod_user_id);
			$dose_interv = $intervenant->getDoseFod($une_fod);
			if($fod_user->contrat == 1 && $dose_interv > $cdd_cdi_max){
				$cdd_cdi_max = $dose_interv;
			}
			elseif($fod_user->contrat != 1){
				$cdd_autre_tab[$i] = $intervenant->getDoseMaxFod($une_fod);
				$cdd_autre_max_tab[$i] = $intervenant->getDoseFod($une_fod);
				$i++;
			}
		}

		$ratio_cdd_autre_max = 0;
		for($a=0; $a<$i; $a++){
			$ratio_cdd_autre = ($cdd_autre_max_tab[$a]/$cdd_autre_tab[$a])*100;
			if($ratio_cdd_autre >= $ratio_cdd_autre_max){
				$ratio_cdd_autre_max = $ratio_cdd_autre;
				$indice = $a;
			}
		}

		$dc = $une_fod->GetDoseCollectivePrevisionnelleOptimise().' H.mSv';
		$cdd_cdi = $une_fod->GetDoseIndividuelleMaxOptimise().' mSv';
		$dc_actuelle = $une_fod->GetDoseCollectiveReel().' H.mSv';
		$cdd_cdi = $une_fod->GetDoseIndividuelleMaxOptimise().' mSv';

		$ratio_dc = ($dc_actuelle/$dc)*100;
		$donnees[$u][0] = number_format($ratio_dc, 0); 

		$ratio_cdd_cdi = ($cdd_cdi_max/$cdd_cdi)*100;
		$donnees[$u][1] = number_format($ratio_cdd_cdi, 0); 

		$donnees[$u][2] = number_format($ratio_cdd_autre_max, 0); 

		$donnees[$u][3] = $une_fod; 

		$donnees[$u][4] = number_format($cdd_autre_tab[$indice], 3); 

		$donnees[$u][5] = number_format($cdd_autre_max_tab[$indice], 3);

		$donnees[$u][6] = number_format($cdd_cdi_max, 3);


		$u++;
	}

	$array_dc = array_column($donnees, '0');
	$array_cdd_cdi = array_column($donnees, '1');
	$array_cdd_autre = array_column($donnees, '2');

	array_multisort($array_cdd_cdi, SORT_DESC, $array_cdd_autre, SORT_DESC, $array_dc, SORT_DESC, $donnees);

	for($u = 0; $u < count($donnees); $u++){
		print '<tr class="oddeven center">';

		print '<td class="nowrap" colspan="">'.$donnees[$u][3]->getNomUrl(1);
		print '<p style="margin-top: 3px; margin-bottom: 0px;"><strong>DC</strong></p>';
		print '<p style="margin-top: 3px; margin-bottom: 0px;"><strong>CdD (CDI)</strong></p>';
		if(!empty($donnees[$u][2])){
			print '<p style="margin-top: 3px; margin-bottom: 0px;"><strong>CdD (CDD/Interim)</strong></p>';
		}
		print '</td>';

		print '<td class="nowrap" style="vertical-align: top"><br>';
		$dc = $donnees[$u][3]->GetDoseCollectivePrevisionnelleOptimise();
		print '<p style="margin-top: 3px; margin-bottom: 0px;">'.number_format($dc, 3).' H.mSv</p>';
		$cdd_cdi = $donnees[$u][3]->GetDoseIndividuelleMaxOptimise();
		print '<p style="margin-top: 3px; margin-bottom: 0px;">'.number_format($cdd_cdi, 3).' mSv</p>';
		if(!empty($donnees[$u][2])){
			print '<p style="margin-top: 3px; margin-bottom: 0px;">'.$donnees[$u][4].' mSv</p>';
		}
		print '</td>';

		print '<td class="nowrap" style="vertical-align: top"><br>';
		$dc_actuelle = $donnees[$u][3]->GetDoseCollectiveReel();
		print '<p style="margin-top: 3px; margin-bottom: 0px;">'.number_format($dc_actuelle, 3).' H.mSv</p>';
		print '<p style="margin-top: 3px; margin-bottom: 0px;">'.$donnees[$u][6].' mSv</p>';
		if(!empty($donnees[$u][2])){
			print '<p style="margin-top: 3px; margin-bottom: 0px;">'.$donnees[$u][5].' mSv</p>';
		}
		print '</td>';

		print '<td class="nowrap" style="vertical-align: top"><br>';
		$ratio_dc = $donnees[$u][0];
		if($ratio_dc < 80){
			print '<p style="margin-top: 0px; margin-bottom: 0px;"><span class="badge badge-status4 badge-status">'.$ratio_dc.'%</span></p>';
		}
		elseif($ratio_dc >= 80 && $ratio_dc <= 100){
			print '<p style="margin-top: 0px; margin-bottom: 0px;"><span class="badge badge-status1 badge-status">'.$ratio_dc.'%</span></p>';
		}
		elseif($ratio_dc > 100){
			print '<p style="margin-top: 0px; margin-bottom: 0px;"><span class="badge badge-status8 badge-status">'.$ratio_dc.'%</span></p>';
		}

		$ratio_cdd_cdi = $donnees[$u][1];
		if($ratio_cdd_cdi < 80){
			print '<p style="margin-top: 3px; margin-bottom: 0px;"><span class="badge badge-status4 badge-status">'.$ratio_cdd_cdi.'%</span></p>';
		}
		elseif($ratio_cdd_cdi >= 80 && $ratio_cdd_cdi <= 100){
			print '<p style="margin-top: 3px; margin-bottom: 0px;"><span class="badge badge-status1 badge-status">'.$ratio_cdd_cdi.'%</span></p>';
		}
		elseif($ratio_cdd_cdi > 100){
			print '<p style="margin-top: 3px; margin-bottom: 0px;"><span class="badge badge-status8 badge-status">'.$ratio_cdd_cdi.'%</span></p>';
		}

		if(!empty($donnees[$u][2])){
			$ratio_cdd_autre_max = $donnees[$u][2];
			if($ratio_cdd_autre_max < 80){
				print '<p style="margin-top: 3px; margin-bottom: 0px;"><span class="badge badge-status4 badge-status">'.$ratio_cdd_autre_max.'%</span></p>';
			}
			elseif($ratio_cdd_autre_max >= 80 && $ratio_cdd_autre_max <= 100){
				print '<p style="margin-top: 3px; margin-bottom: 0px;"><span class="badge badge-status1 badge-status">'.$ratio_cdd_autre_max.'%</span></p>';
			}
			elseif($ratio_cdd_autre_max > 100){
				print '<p style="margin-top: 3px; margin-bottom: 0px;"><span class="badge badge-status8 badge-status">'.$ratio_cdd_autre_max.'%</span></p>';
			}
		}
		print '</td>';

		print '</tr>';
	}

	print '</table>';
}
print '</div>';



print '<div class="fichetwothirdright"><div class="ficheaddleft">';
// Last modified myobject
if (!empty($conf->fod->enabled))
{
	$sql = "SELECT s.rowid, s.ref, s.date_creation, s.tms, s.status, s.client_site, s.indice, s.fk_project, s.date_cloture";
	$sql.= " FROM ".MAIN_DB_PREFIX."fod_fod as s";
	$sql .= " WHERE s.status > 7 AND s.status <> 13";
	if (!$userPCR && !$user->admin) {
        $sql .= " AND s.rowid IN (".$db->sanitize($FodListId).")"; // If we have this test true, it also means projectset is not 2
    }
	$sql .= " ORDER BY s.tms DESC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">';
		print "FOD en Bilan ou Clotûrée";
		print '</th>';
		//print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th class="right"><a class="commonlink" href="'.dol_buildpath('/custom/fod/fod_list.php?search_status=14', 1).'">Liste complète FOD clotûrée</a></th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{

				$objp = $db->fetch_object($resql);

				if(!empty($objp->date_cloture)){
					$date_cloture = dol_stringtotime($objp->date_cloture.'T000000Z');
					$date_cloture_plus_1mois = dol_time_plus_duree($date_cloture, 1, 'm');
					$now = dol_now();
				}

				if($objp->status != 14 || (!empty($objp->date_cloture && $date_cloture_plus_1mois >= $now))){
					$myobjectstatic = new Fod($db);
					$myobjectstatic->id=$objp->rowid;
					$myobjectstatic->ref=$objp->ref;
					$myobjectstatic->status=$objp->status;
					$myobjectstatic->client_site=$objp->client_site;
					$myobjectstatic->indice=$objp->indice;
					$myobjectstatic->projet=$objp->fk_project;
					//$myobjectstatic->label=$objp->label;
					//$myobjectstatic->status = $objp->status;

					$projet = New Project($db);
					$projet->fetch($myobjectstatic->projet);
					print '<tr class="oddeven">';
					print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
					
					// Label Projet
					print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($projet->title).'">';
					print $projet->title;
					print '</td>';

					print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";

					print '<td class="right nowrap">'.$myobjectstatic->getLibStatut(2).'</td>';
					print '</tr>';
				}
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}

	$sql = "SELECT s.rowid, s.ref, s.date_creation, s.tms, s.status, s.client_site, s.indice, s.fk_project";
	$sql.= " FROM ".MAIN_DB_PREFIX."fod_fod as s";
	if (!$userPCR && !$user->admin) {
        $sql .= " WHERE s.rowid IN (".$db->sanitize($FodListId).")"; // If we have this test true, it also means projectset is not 2
    }
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
		//print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th class="right"><a class="commonlink" href="'.dol_buildpath('/custom/fod/fod_list.php', 1).'">Liste complète</a></th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic = new Fod($db);
				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->status=$objp->status;
				$myobjectstatic->client_site=$objp->client_site;
				$myobjectstatic->indice=$objp->indice;
				$myobjectstatic->projet=$objp->fk_project;
				//$myobjectstatic->label=$objp->label;
				//$myobjectstatic->status = $objp->status;

				$projet = New Project($db);
				$projet->fetch($myobjectstatic->projet);
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				
				// Label Projet
				print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($projet->title).'">';
				print $projet->title;
				print '</td>';

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
