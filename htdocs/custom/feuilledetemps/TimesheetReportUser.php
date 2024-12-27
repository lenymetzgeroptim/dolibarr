<?php
/*
 * Copyright (C) 2015 delcroip <patrick@pmpd.eu>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/lib/timesheet.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/TimesheetReport.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/modules/pdf/pdf_rat.modules.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedProjet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedUser.class.php';


$htmlother = new FormOther($db);
$project = new Project($db);
$projectstatic = new ExtendedProjet($db);
$extendedUser = New ExtendedUser3($db);
$form = new Form($db);


$userid = is_object($user) ? $user->id : $user;
$userIdSelected = (GETPOST('search_usertoprocessid', 'int') > 0 || GETPOST('search_usertoprocessid', 'int') == -2 ? GETPOST('search_usertoprocessid', 'int') : $userid);
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$exportFriendly = GETPOST('exportFriendly', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('mode', 'alpha');
$model = GETPOST('model', 'alpha');
if(empty($mode)) $mode = 'PTD';
$short = GETPOST('short', 'int');;
$year = GETPOST('year', 'int');;

$dateStart = strtotime(GETPOST('dateStart', 'alpha'));
$dateStartday = GETPOST('dateStartday', 'int');// to not look for the date if action not goTodate
$dateStartmonth = GETPOST('dateStartmonth', 'int');
$dateStartyear = GETPOST('dateStartyear', 'int');
$dateStart = parseDate($dateStartday, $dateStartmonth, $dateStartyear, $dateStart);
$dateEnd = strtotime(GETPOST('dateEnd', 'alpha'));
$dateEndday = GETPOST('dateEndday', 'int');// to not look for the date if action not goTodate
$dateEndmonth = GETPOST('dateEndmonth', 'int');
$dateEndyear = GETPOST('dateEndyear', 'int');
$dateEnd = parseDate($dateEndday, $dateEndmonth, $dateEndyear, $dateEnd);
$invoicabletaskOnly = GETPOST('invoicabletaskOnly', 'int');
if(empty($dateStart) || empty($dateEnd) || empty($userIdSelected)) {
    $step = 0;
    $dateStart = strtotime("first day of previous month", time());
    $dateEnd = strtotime("last day of previous month", time());
}


// Load traductions files requiredby by page
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');


//querry to get the project where the user have priviledge;either project responsible or admin
/*$sql = 'SELECT DISTINCT usr.rowid as userid, usr.lastname, usr.firstname '
     .'FROM '.MAIN_DB_PREFIX.'user as usr ';
$sql .= 'JOIN '.MAIN_DB_PREFIX.'element_contact as ec '
     .' ON ec.fk_socpeople = usr.rowid '
     .' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact'
     .' WHERE ctc.element in (\'project_task\', \'project\') AND ctc.active = \'1\' ';
if(!$user->admin) {
    $list = getSubordinates($db, $userid, 3);
    $list[] = $userid;
    $sql .= ' AND (usr.rowid in ('.implode(', ', $list).'))';
}

//launch the sql querry
$resql = $db->query($sql);
$numUser = 0;
$userList = array();

if($resql) {
    $numUser = $db->num_rows($resql);
    $i = 0;
    // Loop on each record found, so each couple (project id, task id)
    while($i < $numUser)
    {
        $error = 0;
        $obj = $db->fetch_object($resql);
        $userList[$obj->userid] = array('value' => $obj->userid, "label" => $obj->firstname.' '.$obj->lastname);
        //$userList[$obj->userid] = new TimesheetReport($db);
        //$userList[$obj->userid]->initBasic('', $obj->userid, $obj->firstname.' '.$obj->lastname, $dateStart, $dateEnd, $mode);
        $i++;
    }
    $db->free($resql);
} else {
    dol_print_error($db);
}
var_dump($userIdSelected);
$userIdlist=array();
$reportName=$langs->trans('ReportProject');
if($userIdSelected<>-999){
    $userIdlist[]=$userIdSelected;
    $reportName=$userList[$userIdSelected]['value'];
} else {
    $userIdlist=array_keys($userList);
}*/


$reportStatic = new TimesheetReport($db);
$userid = array();
if($userIdSelected > 0) {
    $userid[] = $userIdSelected;
    $reportName = $userIdSelected;
}
else {
    $userid = $extendedUser->get_full_treeIds();
    $reportName=$langs->trans('ReportProject');
}
$reportStatic->initBasic('', $userid, $reportName, $dateStart, $dateEnd, $mode, $invoicabletaskOnly);


$querryRes = '';
if(!empty($userIdSelected) &&!empty($dateEnd) && !empty($dateStart) && $action == 'reportUser') {
    if($exportfriendly){
        $querryRes .= $reportStatic->getHTMLreportExport();
    }
    else {
        $querryRes .= $reportStatic->getHTMLreport($short, "User report ".dol_print_date($dateStart, 'day').'-'.dol_print_date($dateEnd, 'day'));
    }
}

if (empty($conf->feuilledetemps->enabled)) accessforbidden();
if (!$user->rights->feuilledetemps->feuilledetemps->rapportUtilisateur) accessforbidden();


/*
 * Actions
 */

if($action == 'getpdf') {
    $pdf = new pdf_rat($db);
    //$outputlangs = $langs;
    if($pdf->writeFile($reportStatic, $langs)>0) {
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=timesheet&file=reports/".$report->ref.".pdf");
        return;
    }
    ob_end_flush();
    exit();
} elseif($action == 'getExport'){
    $max_execution_time_for_export = (empty($conf->global->EXPORT_MAX_EXECUTION_TIME)?300:$conf->global->EXPORT_MAX_EXECUTION_TIME);    // 5mn if not defined
    $max_time = @ini_get("max_execution_time");
    if($max_time && $max_time < $max_execution_time_for_export)
    {
        @ini_set("max_execution_time", $max_execution_time_for_export); // This work only if safe mode is off. also web servers has timeout of 300
    }
    $name=$reportStatic->buildFile($model, false);
    if(!empty($name)){
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=export&file=".$name);
        return;
    }
    ob_end_flush();
    exit();
}



/*
 * View
 */

llxHeader('', $langs->trans('userReport'), '');

$title = $langs->trans('userReport');
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'user');

print '<form action="?action=reportUser'.(($optioncss != '')?'&amp;optioncss='.$optioncss:'').'" method = "POST">';
print '<table class="tagtable nobottomiftotal liste">';
print '<tr class="liste_titre">';
print '<td class="wrapcolumntitle center liste_titre">'.$langs->trans('User').'</td>';
print '<td class="wrapcolumntitle center liste_titre">'.$langs->trans('DateStart').'</td>';
print '<td class="wrapcolumntitle center liste_titre">'.$langs->trans('DateEnd').'</td>';
print '<td class="wrapcolumntitle center liste_titre">'.$langs->trans('short').'</td>';
print '<td class="wrapcolumntitle center liste_titre">'.$langs->trans('InvoicableOnly').'</td>';
print '<td class="wrapcolumntitle center liste_titre">'.$langs->trans('exportfriendly').'</td>';
print '<td class="wrapcolumntitle center liste_titre">'.$langs->trans('Mode').'</td>';
print '<td></td>';
print '</tr>';
print '<tr class="oddeven center">';
print '<td>';

// Gestion des utilisateurs que l'on peut voir
$moreforfilter = '';
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= '<div class="inline-block hideonsmartphone"></div>';
$projectListResp = $project->getProjectsAuthorizedForUser($user, 1, 1, 0, " AND ec.fk_c_type_contact = 160");
$userList = $projectstatic->getUserForProjectLeader($projectListResp);
// $includeonly = array_merge($userList, $user->getAllChildIds(1));
if(!$user->rights->feuilledetemps->feuilledetemps->modify_verification) {
    $includeonly = $user->getAllChildIds(1);
}
// if (empty($user->rights->user->user->lire)) {
// 	$includeonly = array($user->id);
// }
$exlude = $extendedUser->get_full_treeIds("statut <> 1");
$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('User'), 'user', 'class="paddingright pictofixedwidth"').$form->select_dolusers($userIdSelected, 'search_usertoprocessid', 0, $exlude, 0, $includeonly, null, 0, 0, 0, '', $user->rights->feuilledetemps->feuilledetemps->modify_verification, '', 'maxwidth200');
$moreforfilter .= '</div>';
print $moreforfilter;

// select start date
print '</td>';
print '<td class="center">'.$form->select_date($dateStart, 'dateStart', 0, 0, 0, "", 1, 1, 1)."</td>";

// select end date
print   '<td class="center">'.$form->select_date($dateEnd, 'dateEnd', 0, 0, 0, "", 1, 1, 1)."</td>";

// select short
print ' <td class="center"><input type = "checkbox" name = "short" value = "1" ';
print (($short == 1)?'checked>':'>').'</td>' ;

// Select invoiceable only
print '<td class="center"><input type = "checkbox" name = "invoicabletaskOnly" value = "1" ';
print (($invoicabletaskOnly == 1)?'checked>':'>').'</td>';

// Select Export friendly
print '<td class="center"><input type = "checkbox" name = "exportfriendly" value = "1" ';
print (($exportfriendly == 1)?'checked>':'>').'</td>';

// Select mode
print '<td class="center"><input type = "radio" name = "mode" value = "PTD" '.($mode == 'PTD'?'checked':'');
print '> '.$langs->trans('Project').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'<br>';
print '<input type = "radio" name = "mode" value = "PDT" '.($mode == 'PDT'?'checked':'');
print '> '.$langs->trans('Project').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'<br>';
print '<input type = "radio" name = "mode" value = "DPT" '.($mode == 'DPT'?'checked':'');
print '> '.$langs->trans('Date').' / '.$langs->trans('Project').' / '.$langs->trans('Task').'<br>';
 print '</td></tr></table>';


print "<br>";
// Get Report Button
print '<div class="center">';
print '<input class = "butAction" type = "submit" value = "'.$langs->trans('getReport').'">';

// Export Button
$model = $conf->global->TIMESHEET_EXPORT_FORMAT;
//if(!empty($querryRes))print '<a class = "butAction" href="?action=getpdf&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode=DTU&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('TimesheetPDF').'</a>';
if(!empty($querryRes)  && $conf->global->MAIN_MODULE_EXPORT)print '<a class = "butAction" href="?action=getExport&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&search_usertoprocessid='.$userIdSelected.'&mode=DTU&model='.$model.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('Export').'</a>';
print '</div><br>';
print '</form>';

// section to generate
if(!empty($querryRes)) {
    echo $querryRes;
}

llxFooter();
$db->close();
