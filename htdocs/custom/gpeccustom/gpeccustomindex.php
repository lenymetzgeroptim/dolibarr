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
 *	\file       gpeccustom/gpeccustomindex.php
 *	\ingroup    gpeccustom
 *	\brief      Home page of gpeccustom top menu
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
$langs->loadLangs(array("gpeccustom@gpeccustom"));

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
//if (!isModEnabled('gpeccustom')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('gpeccustom', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'gpeccustom', 0, 'gpeccustom_myobject', 'myobject', '', 'rowid');
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

llxHeader("", $langs->trans("Indicateurs et évolution dans le temps"));

print load_fiche_titre($langs->trans("Indicateurs et évolution dans le temps"), '', 'fa-stream');

// print '<div class="fichecenter"><div class="fichethirdleft">';

 require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/cvtec.class.php';

 
 $arr_skill_jobs = GETPOST('arr_skill_jobs');
 $arr_users = GETPOST('arr_users'); 
 $filter_fk_user = GETPOST('filter_fk_user');
 $arr_jobs = GETPOST('arr_jobs');
 $job_fk_user = GETPOST('job_fk_user');
 $arr_skill = GETPOST('arr_skill');
 $skill_fk_user = GETPOST('skill_fk_user');
 $nbusers_fk_user = GETPOST('nbusers_fk_user');
 $skill_val_user = GETPOST('skill_val_user');
 $arr_val_skill = GETPOST('arr_val_skill');

 $cv = new CVTec($db);
 
// Statistics
include DOL_DOCUMENT_ROOT.'/custom/gpeccustom/graph_cvtec.inc.php';
// print ' </div>';
include DOL_DOCUMENT_ROOT.'/custom/gpeccustom/graph_skill.inc.php';
$cvstatic = new CVTec($db);
$arrjobeval = $cvstatic->getJobEvaluated();
$usersjobs = $cvstatic->getUserJobs();
print '</div>';
print '<div class="fichecenter">';
print '<div class="fichethirdleft">';

$sql = "SELECT t.rowid,t.fk_user,t.fk_job,t.description, DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_debut, t.date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,";
$sql .= " u.lastname,u.firstname,MAX(t.date_start) as max_date,";
$sql .= " j.rowid as job_id, j.label as ref";
$sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on t.fk_user = u.rowid";
$sql .= ", ".MAIN_DB_PREFIX."hrm_job as j WHERE 1 = 1 AND t.fk_job = j.rowid";
$sql .= " AND t.date_end is null";
$sql .= ' AND (t.fk_user not in ('.implode(',', array_keys($arrjobeval)).')) ';
$sql .= ' OR (';
$i = 1;

foreach(array_filter($arrjobeval) as $key => $values) {
	$sql .= ' t.fk_user = '.$key.' AND';
	$sql .= ' t.fk_job NOT IN ('.implode(',', array_keys($values)).')';
	if($i < sizeof(array_filter($arrjobeval))) {
		$sql .= ' AND';
	}
	$i++;
}
$sql .= ')';
$sql .= " GROUP BY t.fk_user, t.fk_job";
$sql .= " ORDER BY max_date";


$resql = $db->query($sql);

if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="2" width="30">'.$langs->trans("Salariés en poste non encore évalués - en cours").'</th>';
	// print '<th></th>';
	print '<th  width="30" class="center">'.$langs->trans("Date d'affectation").'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);

	if ($num) {
		$i = 0;
		while ($i < $num) {
		
			$obj = $db->fetch_object($resql);
			$emp[] = $obj;
			$i++;
		}
	}
} else {
	dol_print_error($db);
}

foreach($emp as $key => $val) {
	if(!empty($emp)) { 
		// if($i < sizeof($emp)) { 
			print '<tr class="oddeven">';
			// print '<td class="nowrap">';
	
			$cvstatic->id = $val->fk_user;
			$cvstatic->label = $val->ref;
			$cvstatic->title = $val->date_debut;
			$cvstatic->status = $val->status;
			$CVuser = new User($db);
			if(isset($val->fk_user)) {
				$CVuser->fetch($val->fk_user);
			}
		
			

			// print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// // print '<td width="30" class="nobordernopadding nowraponall">';
			// // print $CVuser->getNomUrl(1);
			// // print '</td>';
			// // print '<td width="16" class="nobordernopadding nowrap">';
			// // print '&nbsp;';
			// // print '</td>';
			
			// print '</tr></table>';
			// print '</td>';
			print '<td width="30" class="nobordernopadding nowraponall">';
			print $CVuser->getNomUrl(1);
			print '</td>';
			// print '<td width="16" class="nobordernopadding nowrap">';
			// print '&nbsp;';
			// print '</td>';
			print '<td class="nobordernopadding tdoverflowmax150 maxwidth400onsmartphone">';
			print $cvstatic->label;
			print '</td>';
			// Date
			$datem = $db->jdate($val->date_debut);
			print '<td width="30" class="center" title="'.dol_escape_htmltag($langs->trans("Date d'affectation au poste").': '.dol_print_date($datem, 'day', 'tzuserrel')).'">';
			print '<span>'.dol_print_date($datem, 'day', 'tzuserrel').'</span>';
			print '</td>';

			// Status
			// print '<td class="right">'.$cvstatic->title.'</td>';
			print '</tr>';

		// 	$i++;
		// }
		// 
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	
}

print '<tr class="liste_total"><td colspan="2">Total des postes non évalués</td><td class="right">'.sizeof($emp).'</td></tr>';			
print "</table></div>";

print '<br>';
print '</div><div class="fichetwothirdright">';

$sql = "SELECT u.rowid,uf.employeur,";
$sql .= " u.lastname,u.firstname,DATE_FORMAT(u.dateemployment ,'%Y-%m-%d') as date_debut";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as uf on uf.fk_object = u.rowid";
$sql .= ' WHERE u.statut = 1';
$sql .= ' AND (u.rowid not in ('.implode(',', array_keys($usersjobs)).')) ';
$sql .= ' AND uf.employeur > 0';

// $sql .= " AND u.employueur = 1";
$sql .= " GROUP BY u.rowid";

$resql = $db->query($sql);

if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th width="30">'.$langs->trans("Salariés non affecté à un emploi - en cours").'</th>';
	print '<th class="center">'.$langs->trans("Société").'</th>';
	print '<th class="center">'.$langs->trans("Date d'embauche").'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			print '<tr class="oddeven">';
			// print '<td class="nowrap">';

			$cvstatic->id = $obj->fk_user;
			$cvstatic->label = $obj->ref;
			$cvstatic->title = $obj->date_debut;
			$cvstatic->status = $obj->status;
			$CVuser = new User($db);
			if(isset($obj->rowid)) {
				$CVuser->fetch($obj->rowid);
			}
		
			
			if(isset($obj->employeur)) {
				$societes = array(1 => 'OPTIM Industries',
					2 => 'SIGEDI',
					3 => 'ALORIS',
					4 => 'HP Formation',
					5 => 'ETT : AVS Le Teil',
					6 => 'ETT : AVS Lyon',
					7 => 'STT : CAP AIN',
					8 => 'ETT : Manpower',
					9 => 'ETT : Elitt',
					10 => 'SERVHOR',
					11 => 'Reactiv2M'
				);
			}
			// print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// // print '<td width="30" class="nobordernopadding nowraponall">';
			// // print $CVuser->getNomUrl(1);
			// // print '</td>';
			// // print '<td width="16" class="nobordernopadding nowrap">';
			// // print '&nbsp;';
			// // print '</td>';
			
			// print '</tr></table>';
			// print '</td>';
			print '<td width="30" class="nobordernopadding nowraponall">';
			print $CVuser->getNomUrl(1);
			print '</td>';
			// print '<td width="16" class="nobordernopadding nowrap">';
			// print '&nbsp;';
			// print '</td>';
			// print '<td class="nobordernopadding tdoverflowmax150 maxwidth400onsmartphone">';
			// print $cvstatic->label;
			// print '</td>';
			
			print '<td class="center" title="'.dol_escape_htmltag($langs->trans("Salarié OPTIM")).'">';

			print '<span>'.$societes[$obj->employeur].'</span>';
		
			print '</td>';
			// Date
			$datem = $db->jdate($obj->date_debut);
			print '<td width="30" class="center" title="'.dol_escape_htmltag($langs->trans("Date d'embauche").': '.dol_print_date($datem, 'day', 'tzuserrel')).'">';
			print '<span>'.dol_print_date($datem, 'day', 'tzuserrel').'</span>';
			print '</td>';
		

			// Status
			// print '<td class="right">'.$cvstatic->title.'</td>';
			print '</tr>';

			$i++;
		}
		print '<tr class="liste_total"><td colspan="2">Total des salariés non affectés à un emploi</td><td class="right">'.$num.'</td></tr>';
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table></div>";
} else {
	dol_print_error($db);
}

/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('gpeccustom') && $user->rights->gpeccustom->read)
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


// print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('gpeccustom') && $user->rights->gpeccustom->read)
{
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
	$sql.= " FROM ".MAIN_DB_PREFIX."gpeccustom_myobject as s";
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
