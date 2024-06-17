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
//include 'core/lib/includeMain.lib.php';
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/lib/timesheet.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/TimesheetReport.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/core/modules/pdf/pdf_rat.modules.php';
//require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
$htmlother = new FormOther($db);
//$objmodelexport=new ModeleExports($db);
$id                 = GETPOST('id', 'int');
$action                 = GETPOST('action', 'alpha');
//$dateStart         = GETPOST('dateStart', 'alpha');
$exportfriendly = GETPOST('exportfriendly', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$short = GETPOST('short', 'int');
$mode = GETPOST('mode', 'alpha');
$model = GETPOST('model', 'alpha');
$arrproj = GETPOST('arrproj', 'alpha');

if(empty($mode)){
    $mode = 'UTD';
}

if (!$user->rights->feuilledetemps->timespent->readall) {
	accessforbidden();
}

$projectSelectedId = GETPOST('projectSelected');
$year = GETPOST('year', 'int');
$month = GETPOST('month', 'alpha');//strtotime(str_replace('/', '-', $_POST['Date']))
// Load traductions files requiredby by page
//$langs->load("companies");
//$firstDay = ($month)?strtotime('01-'.$month.'-'. $year):strtotime('first day of previous month');
//$lastDay = ($month)?strtotime('last day of this month', $firstDay):strtotime('last day of previous month');
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
//find the right week
//find the right week
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

// if(empty($dateStart) || empty($dateEnd) || empty($projectSelectedId)) {
if(empty($dateStart) || empty($dateEnd) || empty($arrproj)) {
    $step = 0;
    $dateStart = strtotime("first day of previous month", time());
    $dateEnd = strtotime("last day of previous month", time());
}

$userid = is_object($user) ? $user->id : $user;
//querry to get the project where the user have priviledge;either project responsible or admin
$sql = 'SELECT pjt.rowid, pjt.ref, pjt.title, pjt.dateo, pjt.datee FROM '.MAIN_DB_PREFIX.'projet as pjt';
if(!$user->admin) {
    $sql .= ' JOIN '.MAIN_DB_PREFIX.'element_contact AS ec ON pjt.rowid = element_id ';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact';
    $sql .= ' WHERE ((ctc.element in (\'project_task\') AND ctc.code LIKE \'%EXECUTIVE%\')OR (ctc.element in (\'project\') AND ctc.code LIKE \'%LEADER%\')) AND ctc.active = \'1\'  ';
    $sql .= ' AND fk_socpeople = \''.$userid.'\' and fk_statut = \'1\'';
    $sql.= " AND pjt.entity IN (".getEntity('projet').")";
} else{
    $sql .= ' WHERE fk_statut = \'1\' ';
    $sql.= " AND pjt.entity IN (".getEntity('projet').")";
}
dol_syslog('timesheet::report::projectList ', LOG_DEBUG);
//launch the sql querry
$resql = $db->query($sql);
$numProject = 0;
$projectList = array();
if($resql) {
        $numProject = $db->num_rows($resql);
        $i = 0;
        // Loop on each record found, so each couple (project id, task id)
        while($i < $numProject)
        {
                $error = 0;
                $obj = $db->fetch_object($resql);
                $projectList[$obj->rowid]=array('value' => $obj->rowid, "label" =>  $obj->ref.' - '.$obj->title);
                //$projectList[$obj->rowid] = new TimesheetReport($db);
                //$projectList[$obj->rowid]->initBasic($obj->rowid, '', $obj->ref.' - '.$obj->title, $dateStart, $dateEnd, $mode, $invoicabletaskOnly);
                $arrproject[$obj->rowid]=  $obj->ref.' - '.$obj->title;
                
                
                // var_dump($obj->rowid == $projectSelectedId);
                $i++;
        }
        $db->free($resql);
} else {
        dol_print_error($db);
}


if($arrproj == '' && isset($projectSelectedId)) {
    $arrproj = array($projectSelectedId);
}

foreach($arrproject as $key => $value) {
    if($projectSelectedId == $key) {
        $projdefault[$key] = $value;
    }

    // if($projectSelectedId > $key || $projectSelectedId < $key){
    //     $projdefault[array_shift(array_keys($arrproject))] = array_shift(array_values($arrproject));
    // }

    if($projectSelectedId > $key || $projectSelectedId < $key){
        $label = "Projets";
    }
    // if($projectSelectedId > $key || $projectSelectedId < $key && isset($projectSelectedId)){
    //     $label = $langs->trans('NotAvailable');
    // }
}
   
    

$projectIdlist=array();
$reportName = $langs->trans('ReportProject');
if($projectSelectedId<>-999){
    $projectIdlist[]=$projectSelectedId;
    $reportName=$projectList[$projectSelectedId]['label'];
} else {
    $projectIdlist= array_keys($projectList);
}


if(!empty($arrproj) && $arrproj != '' && sizeof($arrproj) > 1) {
    foreach($projectList as $key => $val) {
        in_array($key, $arrproj) ?  $projectlist[$key] = $val['label'] : null;
    }
}

$projectIdlist = $arrproj;

$reportStatic = new TimesheetReport($db);

$reportStatic->initBasic($projectIdlist, '', '', $dateStart, $dateEnd, $mode, $invoicabletaskOnly);


if($action == 'getpdf') {
    $pdf = new pdf_rat($db);
    //$outputlangs = $langs;
    if($pdf->writeFile($reportStatic, $langs)>0) {
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=feuilledetemps&file=reports/".$reportStatic->name.".pdf");
        return;
    }
    ob_end_flush();
    exit();
}elseif($action == 'getExport'){
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
//$_SESSION["dateStart"] = $dateStart ;
llxHeader('', $langs->trans('projectReport'), '');

$title = $langs->trans('projectReport');
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'project');

$querryRes = '';

if(!empty($arrproj)  &&!empty($dateStart)) {
    $pasderes = 1;
    if($exportfriendly){
        $querryRes .= $reportStatic->getHTMLreportExport();
    }else {
        $querryRes .= $reportStatic->getHTMLreport($short,
            dol_print_date($dateStart, 'day').'-'.dol_print_date($dateEnd, 'day'));
    }
}

$projdefault == null ? $projdefault = array($label) : $projdefault = $projdefault;
// $resArrays = $reportStatic->getReportArray();
// $resArrays= array_merge($reportStatic->getReportArray(), $reportStatic->getReportArray2());

// $resArray = call_user_func_array('array_merge', $resArrays);

// var_dump($resArrays);

/*$Form .= "<div id='quicklinks'>";
//This week quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("monday this week"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("sunday this week"), 'dayxcard')."'>".$langs->trans('thisWeek')."</a>";
//This month quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("first day of this month"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of this month"), 'dayxcard')."'>".$langs->trans('thisMonth')."</a>";
//last week quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("monday last week"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("sunday last week"), 'dayxcard')."'>".$langs->trans('lastWeek')."</a>";
//Last month quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("first day of previous month"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of previous month"), 'dayxcard')."'>".$langs->trans('lastMonth')."</a>";
//today
$today = dol_print_date(mktime(), 'dayxcard');
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".$today;
$Form .= "&dateEnd=".$today."'>".$langs->trans('today')."</a> ";
$Form .= "</div>";*/
// <td class="wrapcolumntitle center liste_titre">'.$langs->trans('Mode').'</td>
$Form .= '<form action="?action=reportproject'.(($optioncss != '')?'&amp;optioncss='.$optioncss:'').'" method = "POST">
            <table class="tagtable nobottomiftotal liste">
            <tr class="liste_titre">
            <td width="35%" class="wrapcolumntitle center liste_titre">'.$langs->trans('Project').'</td>
            <td width="15%" class="wrapcolumntitle center liste_titre">'.$langs->trans('DateStart').'</td>
            <td width="15%" class="wrapcolumntitle center liste_titre">'.$langs->trans('DateEnd').'</td>
            <td width="15%" class="wrapcolumntitle center liste_titre">'.$langs->trans('short').'</td>
            <td width="10%" class="wrapcolumntitle center liste_titre">'.$langs->trans('InvoicableOnly').'</td>
            <td width="10%" class="wrapcolumntitle center liste_titre">'.$langs->trans('exportfriendly').'</td>
      
            </tr></table>';
        $Form .= '<table class="tagtable nobottomiftotal liste" style="display: flex;justify-content: center;border-top-style: hidden;border-top-color: #FEFEFE;">';
        $Form .= '<tr class="center">';
        // Select mode
        $Form.= '<td style="border: none;  border-top-color: #FEFEFE;" class="center"><input type = "radio" name = "mode" value = "UTD" '.($mode == 'UTD'?'checked':'');
        $Form .= '><span style="margin: 0px 50px 50px 0px;"> '.$langs->trans('User').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'</span>';
        $Form.= '<input type = "radio" name = "mode" value = "UDT" '.($mode == 'UDT'?'checked':'');
        $Form .= '><span style="margin: 0px 50px 50px 0px;"> '.$langs->trans('User').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'</span>';
        $Form.= '<input type = "radio" name = "mode" value = "DUT" '.($mode == 'DUT'?'checked':'');
        $Form .= '><span style="margin: 0px 50px 50px 0px;"> '.$langs->trans('Date').' / '.$langs->trans('User').' / '.$langs->trans('Task').'</span>';
        $Form.= '<input type = "radio" name = "mode" value = "TUD" '.($mode == 'TUD'?'checked':'');
        $Form .= '><span style="margin: 0px 50px 50px 0px;"> '.$langs->trans('Task').' / '.$langs->trans('User').' / '.$langs->trans('Date').'</span>';
        $Form.= '<input type = "radio" name = "mode" value = "TDU" '.($mode == 'TDU'?'checked':'');
        $Form .= '><span style="margin: 0px 50px 50px 0px;"> '.$langs->trans('Task').' / '.$langs->trans('Date').' / '.$langs->trans('User').'</span>';
        $Form .= '</td></tr></table>';
        
//         <td><select name="projectSelected" style="max-width: 500px;">';
// // // select project

// foreach($projectList as $pjt) {
//     $Form .= '<option value = "'.$pjt["value"].'" '.(($projectSelectedId == $pjt["value"])?"selected":'').' >'.$pjt["label"].'</option>'."\n";
// }

// $Form .= '<option value = "-999" '.(($projectSelectedId == "-999")?"selected":'').' >'.$langs->trans('All').'</option>'."\n";

// $Form .= '</select></td>';
$Form .= '<table class="tagtable nobottomiftotal liste">';
$Form .= '<tr class="oddeven center">';
$Form .= '<td class="center">';
$Form .=  $form->multiselectarray('arrproj',  $arrproject,  $arrproj, '', '', '', '', '480px tdoverflowmax100', '','', implode($projdefault));
$Form .= '</td>';  
  
//}
// select start date
$Form.=   '<td width="15%" class="center">'.$form->select_date($dateStart, 'dateStart', 0, 0, 0, "", 1, 1, 1)."</td>";
// select end date
$Form.=   '<td width="15%" class="center">'.$form->select_date($dateEnd, 'dateEnd', 0, 0, 0, "", 1, 1, 1)."</td>";
//$Form .= '<td> '.$htmlother->select_month($month, 'month').' - '.$htmlother->selectyear($year, 'year', 0, 10, 3)
// Select short
$Form .= ' <td width="10%"  class="center"><input type = "checkbox" name = "short" value = "1" ';
$Form .= (($short == 1)?'checked>':'>').'</td>' ;
// Select invoiceable only
$Form .= '<td width="10%" class="center" style="min-width: 180px;"><input type = "checkbox" name = "invoicabletaskOnly" value = "1" ';
$Form .= (($invoicabletaskOnly == 1)?'checked>':'>').'</td>';
// Select Export friendly
$Form .= '<td width="10%" class="center" style="min-width: 110px;"><input type = "checkbox" name = "exportfriendly" value = "1" ';
$Form .= (($exportfriendly == 1)?'checked>':'>').'</td>';
$Form .= '</tr></table>';

$model=$conf->global->TIMESHEET_EXPORT_FORMAT;
//if project are selected more than one.
if(!empty($arrproj) && $arrproj !== '' && sizeof($arrproj) > 1) {
    $Form .= '<table class="tagtable nobottomiftotal">';
    $Form .= '<tbody>';
    $Form .= '<tr class="liste_titre">';
    $Form .= ' <th class="wrapcolumntitle liste_titre" title="'.$langs->trans('Invoice').'.">';
    $Form .= '<span class="reposition">'.$langs->trans('Invoice').'.</span>';
    $Form .= '</th>';
    $Form .= '<th class="wrapcolumntitle liste_titre" title="'.$langs->trans('TimesheetPDF').'.">';
    $Form .= '<span class="reposition">'.$langs->trans('TimesheetPDF').'.</span>';
    $Form .= '</th>';
    $Form .= ' <th class="wrapcolumntitle liste_titre" title="'.$langs->trans('Export').'.">';
    $Form .= '<span class="reposition">'.$langs->trans('Export').'.</span>';
    $Form .= '</th>';
    $Form .= '</tr>';

    foreach($projectlist as $projid => $projref) {
        $projlabel = explode('-', $projref);
        $report = $projlabel[0].'_'.dol_print_date($dateStart, '%d-%m-%Y').'_'.dol_print_date($dateEnd, '%d-%m-%Y');
        $Form .= '<tr class="oddeven">';
        $Form .= '<td width="50%" class="nowrap tdoverflowmax150">';
        if(!empty($querryRes) && ($user->rights->facture->creer || version_compare(DOL_VERSION, "3.7")<=0))$Form .= '<a class = "documentdownload paddingright" href = "TimesheetProjectInvoice.php?step=0&dateStart='.dol_print_date($dateStart, 'dayxcard').'&invoicabletaskOnly='.$invoicabletaskOnly.'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectid='.$projid.'" ><span class="fas fa-project-diagram  em088 infobox-project pictofixedwidth em088" style=""></span>'.$projref.'</a>';
        $Form .= '</td>';
        $Form .= '<td width="25%" class="nowraponall">';
        if(!empty($querryRes))$Form .= '<a class = "documentdownload paddingright" href="?action=getpdf&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projid.'&mode='.$mode.'&invoicabletaskOnly='.$invoicabletaskOnly.'" ><i class="fa fa-file-pdf-o paddingright" title="Fichier: '.$projref.'"></i>'.$projlabel[0].'<i class="fas fa-download" style="color: gray;"></i></a>';
        //  if(!empty($querryRes))$Form .= '<a class="pictopreview documentpreview" href="/erp/document.php?modulepart=feuilledetemps&amp;attachment=0&amp;file=reports/'.$report.'.pdf&amp;entity=1" mime="application/pdf" target="_blank"><span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span></a>';
        $Form .= '</td>';
        $Form .= '<td width="25%">';
        // $Form .= '<a class="documentdownload paddingright" href="/erp/document.php?modulepart=gpeccustom&amp;file=cvtec%2FCVTEC_232%2FCVTEC_232.pdf&amp;entity=1" title="CVTEC_232.pdf"><i class="fa fa-file-pdf-o paddingright" title="Fichier: CVTEC_232.pdf"></i>CVTEC_232.pdf</a>';<i class="fas fa-file-csv"></i><i class="fa fa-file-pdf-o paddingright" title="Fichier: '.$projref.'">
        if(!empty($querryRes) && $conf->global->MAIN_MODULE_EXPORT)$Form .= '<a class = "documentdownload paddingright" href="?action=getExport&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projid.'&mode='.$mode.'&model='.$model.'&invoicabletaskOnly='.$invoicabletaskOnly.'" ><i style="color: #055;" class="fas fa-file-csv paddingright" title="Fichier: '.$projref.'"></i>'.$projlabel[0].'<i class="fas fa-download" style="color: gray;"></i></a>';
        // $Form .= '<a class="pictopreview documentpreview" href="?action=getExport&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projid.'&mode='.$mode.'&model='.$model.'&invoicabletaskOnly='.$invoicabletaskOnly.'" mime="application/pdf" target="_blank"><span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span></a>';
        $Form .= '</td>';
        $Form .= '</tr>';
        }
        $Form .= '</tbody></table>';
    }

//submit
$Form .= "<br>";
$Form .= '<div class="center">';

$Form .= '<input class = "butAction" type = "submit" value = "'.$langs->trans('getReport').'">';

if(empty($arrproj) || $arrproj == '' || sizeof($arrproj) == 1) {
    $projectSelectedId == '' ? $projectSelectedId = implode($arrproj) : $projectSelectedId = $projectSelectedId;
    if(!empty($querryRes) && ($user->rights->facture->creer || version_compare(DOL_VERSION, "3.7")<=0))$Form .= '<a class = "butAction" href = "TimesheetProjectInvoice.php?step=0&dateStart='.dol_print_date($dateStart, 'dayxcard').'&invoicabletaskOnly='.$invoicabletaskOnly.'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectid='.$projectSelectedId.'" >'.$langs->trans('Invoice').'</a>';
    if(!empty($querryRes))$Form .= '<a class = "butAction" href="?action=getpdf&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode='.$mode.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('TimesheetPDF').'</a>';
    if(!empty($querryRes) && $conf->global->MAIN_MODULE_EXPORT)$Form .= '<a class = "butAction" href="?action=getExport&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode='.$mode.'&model='.$model.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('Export').'</a>';
}
if(!empty($querryRes))$Form .= '<a class = "butAction" href="?action=reportproject&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode='.$mode.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('Refresh').'</a>';
$Form .= '</div><br>';
$Form .= '</form>';


if(!($optioncss != '' && !empty($_POST['userSelected']))) echo $Form;

if(!empty($querryRes)){
    echo $querryRes;
}
elseif ($pasderes){
    print '<div class="center" style="color: red">Aucun Résultat</div>';
}

/*
// List of available export formats
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryUsed").'</td>';
print '<td align="right">'.$langs->trans("LibraryVersion").'</td>';
print '</tr>'."\n";

$liste=$objmodelexport->liste_modeles($db);
$listeall=$liste;
foreach($listeall as $key => $val)
{
    if (preg_match('/__\(Disabled\)__/',$listeall[$key]))
    {
        $listeall[$key]=preg_replace('/__\(Disabled\)__/','('.$langs->transnoentitiesnoconv("Disabled").')',$listeall[$key]);
        unset($liste[$key]);
    }

    print '<tr class="oddeven">';
    print '<td width="16">'.img_picto_common($key,$objmodelexport->getPictoForKey($key)).' ';
    $text=$objmodelexport->getDriverDescForKey($key);
    $label=$listeall[$key];
    print $form->textwithpicto($label,$text).'</td>';
    print '<td>'.$objmodelexport->getLibLabelForKey($key).'</td>';
    print '<td align="right">'.$objmodelexport->getLibVersionForKey($key).'</td>';
    print '</tr>'."\n";
}
print '</table>';*/
llxFooter();
$db->close();
