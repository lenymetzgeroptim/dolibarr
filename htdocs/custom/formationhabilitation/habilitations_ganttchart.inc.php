<?php
/* Copyright (C) 2010-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Lény METZGER  <l.metzger@optim-industries.fr>
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
 *	\file       custom/formationhabilitation/habilitation_ganttchart.inc.php
 *	\ingroup    formationhabilitation
 *	\brief      Gantt diagram for Habilitations
 */

?>

<div id="principal_content" style="margin-left: 0;">
	<div style="margin-left: 0; position: relative;" class="gantt" id="GanttChartDIV"></div>

	<script type="text/javascript">

function DisplayHideRessources(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowRessources = 1;
	}
	else {
		booShowRessources = 0;
	}
	reloadGraph();
}

function DisplayHideDurations(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowDurations = 1;
	}
	else {
		booShowDurations = 0;
	}
	reloadGraph();
}

function DisplayHideComplete(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowComplete = 1;
	}
	else {
		booShowComplete = 0;
	}
	reloadGraph();
}

function selectBarText(value) {
	graphFormat = g.getFormat();
	id=value.options[value.selectedIndex].value;
	barText = id;
	reloadGraph();
}

function reloadGraph() {
	g.setShowRes(booShowRessources);
	g.setShowComp(booShowComplete);
	g.setShowDur(booShowDurations);
	g.setCaptionType(barText);
	g.setFormat(graphFormat);
	g.Draw(jQuery("#tabs").width()-40);
}


//var g = new JSGantt.GanttChart('g', document.getElementById('GanttChartDIV'), 'day');
var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV'), 'month');

if (g.getDivId() != null)
//if (g)
{
	var booShowRessources = 1;
	var booShowDurations = 1;
	var booShowComplete = 1;
	var barText = "Resource";
	var graphFormat = "month";

	g.setDateInputFormat('<?php echo $dateformatinput; ?>');  // Set format of input dates ('mm/dd/yyyy', 'dd/mm/yyyy', does not work with 'yyyy-mm-dd')
	g.setDateTaskTableDisplayFormat('<?php echo $dateformat; ?>');	// Format of date used into line
	g.setDateTaskDisplayFormat('<?php echo $datehourformat; ?>');		// Format of date used into popup, not into line
	g.setDayMajorDateDisplayFormat('dd mon');
	g.setShowRes(0); 		// Show/Hide Responsible (0/1)
	g.setShowDur(0); 		// Show/Hide Duration (0/1)
	g.setShowComp(0); 		// Show/Hide % Complete(0/1)
	g.setShowStartDate(0); 	// Show/Hide % Complete(0/1)
	g.setShowEndDate(0); 	// Show/Hide % Complete(0/1)
	g.setShowTaskInfoLink(0);
	g.setFormatArr("month") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
	g.setCaptionType('Caption');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
	g.setUseFade(0);
	g.setDayColWidth(20);
	g.setMinDate('<?php print dol_print_date($date_start, $dateformatinput2) ?>');
	g.setMaxDate('<?php print dol_print_date($date_end, $dateformatinput2) ?>');
	g.setUseSort(0);
	g.setOptions({
		vAdditionalHeaders: { // Add data columns to your table
			date_debut_habilitation: {
				title: 'Début habilitation'
			},
			date_fin_habilitation: {
				title: 'Fin habilitation'
			}
  		},
	});
	g.setTooltipTemplate("<div><strong>{{pName}}</strong></div> <div>{{Lang:pStart}}: {{pStart}}</div>");

	/* g.setShowTaskInfoLink(1) */
	g.addLang('<?php print $langs->getDefaultLang(1); ?>', vLangs['<?php print $langs->getDefaultLang(1); ?>']);
	g.setLang('<?php print $langs->getDefaultLang(1); ?>');

	<?php

	echo "\n";
	echo "/* g.AddTaskItem(new JSGantt.TaskItem(line_id, 'label', 'start_date', 'end_date', 'css', 'link', milestone, 'Resources', Compl%, Group, Parent, Open, 'Dependency', 'label','note', g)); */\n";

	$level = 0;
	$tnums = count($lines);
	$old_user_id = 0;
	for ($tcursor = 0; $tcursor < $tnums; $tcursor++) {
		$t = $lines[$tcursor];

		if (empty($old_user_id) || $old_user_id != $t['line_user_id']) {
			// Break on user, create a fictive line for user id $t['line_user_id']
			$usertmp = $user_static->users[$t['line_user_id']];
			$tmpt = array(
				'line_id'=> '-'.$t['line_user_id'],
				'line_alternate_id'=> '-'.$t['line_user_id'],
				'line_name'=>$usertmp->firstname.' '.$usertmp->lastname,
				'line_resources'=>'',
				'line_start_date'=>'',
				'line_end_date'=>'',
				'line_is_group'=>1, 'line_position'=>0, 'line_css'=>'', 'line_milestone'=> 0, 'line_parent'=>0, 'line_parent_alternate_id'=>0,
				'line_notes'=>'',
				'line_planned_workload'=>0
			);
			constructGanttLine($lines, $tmpt, array(), 0, $t['line_user_id']);
			$old_project_id = $t['line_user_id'];
		}

		if ($t["line_parent"] <= 0) {
			constructGanttLine($lines, $t, $line_dependencies, $level, $t['line_user_id']);
			findChildGanttLine($lines, $t["line_id"], $line_dependencies, $level + 1);
		}
	}

	echo "\n";
	?>

	g.Draw(jQuery("#tabs").width()-40);
	setTimeout('g.DrawDependencies()',100);
}
else
{
	alert("<?php echo $langs->trans("FailedToDefinGraph"); ?>");
}
</script>
</div>



<?php
/**
 * Add a gant chart line
 *
 * @param 	array	$tarr					Array of all lines
 * @param	array	$line					Array with properties of one line
 * @param 	array	$line_dependencies		Line dependencies (array(array(0=>idline,1=>idlinetofinishfisrt))
 * @param 	int		$level					Level
 * @param 	int		$project_id				Id of project
 * @return	void
 */
function constructGanttLine($tarr, $line, $line_dependencies, $level = 0, $project_id = null)
{
	global $langs;
	global $dateformatinput2;

	$start_date = $line["line_start_date"];
	$end_date = $line["line_end_date"];
	if (!$end_date) {
		$end_date = $start_date;
	}
	$start_date = dol_print_date($start_date, $dateformatinput2);
	$end_date = dol_print_date($end_date, $dateformatinput2);
	// Resources
	$resources = $line["line_resources"];

	// Define depend (ex: "", "4,13", ...)
	$depend = '';
	$count = 0;
	foreach ($line_dependencies as $value) {
		// Not yet used project_dependencies = array(array(0=>idline,1=>idlinetofinishfisrt))
		if ($value[0] == $line['line_id']) {
			$depend .= ($count > 0 ? "," : "").$value[1];
			$count++;
		}
	}
	// $depend .= "\"";
	// Define parent
	if ($project_id && $level < 0) {
		$parent = '-'.$project_id;
	} else {
		$parent = $line["line_parent_alternate_id"];
		//$parent = $line["line_parent"];
	}
	// Define percent
	$percent = $line['line_percent_complete'] ? $line['line_percent_complete'] : 0;
	$percent = 0;
	// Link (more information)
	if ($line["line_id"] < 0) {
		//$link=DOL_URL_ROOT.'/projet/lines.php?withproject=1&id='.abs($line["line_id"]);
		$link = '';
	} else {
		$link = DOL_URL_ROOT.'/custom/formationhabilitation/habilitation.php?id='.$line["line_fk_habilitation_id"];
	}

	// Name
	//$name='<a href="'.DOL_URL_ROOT.'/projet/line/lines.php?id='.$line['line_id'].'">'.$line['line_name'].'</a>';
	$name = $line['line_name'];

	/*for($i=0; $i < $level; $i++) {
		$name=' - '.$name;
	}*/
	// Add line to gantt
	/*
	g.AddTaskItem(new JSGantt.TaskItem(1, 'Define Chart API','',          '',          'ggroupblack','', 0, 'Brian', 0,  1,0,1,'','','Some Notes text',g));
	g.AddTaskItem(new JSGantt.TaskItem(11,'Chart Object',    '2014-02-20','2014-02-20','gmilestone', '', 1, 'Shlomy',100,0,1,1,'','','',g));
	</pre>
	<p>Method definition:
	<strong>TaskItem(<em>pID, pName, pStart, pEnd, pColor, pLink, pMile, pRes, pComp, pGroup, pParent, pOpen, pDepend, pCaption, pNotes, pGantt</em>)</strong></p>
	<dl>
	<dt>pID</dt><dd>(required) a unique numeric ID used to identify each row</dd>
	<dt>pName</dt><dd>(required) the line Label</dd>
	<dt>pStart</dt><dd>(required) the line start date, can enter empty date ('') for groups. You can also enter specific time (2014-02-20 12:00) for additional precision.</dd>
	<dt>pEnd</dt><dd>(required) the line end date, can enter empty date ('') for groups</dd>
	<dt>pClass</dt><dd>(required) the css class for this line</dd>
	<dt>pLink</dt><dd>(optional) any http link to be displayed in tool tip as the "More information" link.</dd>
	<dt>pMile</dt><dd>(optional) indicates whether this is a milestone line - Numeric; 1 = milestone, 0 = not milestone</dd>
	<dt>pRes</dt><dd>(optional) resource name</dd>
	<dt>pComp</dt><dd>(required) completion percent, numeric</dd>
	<dt>pGroup</dt><dd>(optional) indicates whether this is a group line (parent) - Numeric; 0 = normal line, 1 = standard group line, 2 = combined group line<a href='#combinedlines' class="footnote">*</a></dd>
	<dt>pParent</dt><dd>(required) identifies a parent pID, this causes this line to be a child of identified line. Numeric, top level lines should have pParent set to 0</dd>
	<dt>pOpen</dt><dd>(required) indicates whether a standard group line is open when chart is first drawn. Value must be set for all items but is only used by standard group lines.  Numeric, 1 = open, 0 = closed</dd>
	<dt>pDepend</dt><dd>(optional) comma separated list of id&#39;s this line is dependent on. A line will be drawn from each listed line to this item<br>Each id can optionally be followed by a dependency type suffix. Valid values are:<blockquote>'FS' - Finish to Start (default if suffix is omitted)<br>'SF' - Start to Finish<br>'SS' - Start to Start<br>'FF' - Finish to Finish</blockquote>If present the suffix must be added directly to the id e.g. '123SS'</dd>
	<dt>pCaption</dt><dd>(optional) caption that will be added after line bar if CaptionType set to "Caption"</dd>
	<dt>pNotes</dt><dd>(optional) Detailed line information that will be displayed in tool tip for this line</dd>
	<dt>pGantt</dt><dd>(required) javascript JSGantt.GanttChart object from which to take settings.  Defaults to &quot;g&quot; for backwards compatibility</dd>
	pCost = null, pPlanStart = null, pPlanEnd = null, pDuration = null, pBarText = null, pDataObject = null, pPlanClass = null)
	*/

	//$note="";

	$s = "\n// Add line level = ".$level." id=".$line["line_id"]." parent_id=".$line["line_parent"]." aternate_id=".$line["line_alternate_id"]." parent_aternate_id=".$line["line_parent_alternate_id"]."\n";

	//$line["line_is_group"]=1;		// When line_is_group is 1, content will be autocalculated from sum of all low lines

	// For JSGanttImproved
	$css = $line['line_css'];
	$line_is_auto_group = $line["line_is_group"];
	//$line_is_auto_group=0;
	//if ($line_is_auto_group) $css = 'ggroupblack';
	//$dependency = ($depend?$depend:$parent."SS");
	$dependency = '';
	//$name = str_repeat("..", $level).$name;

	$lineid = $line["line_alternate_id"];
	//$lineid = $line['line_id'];

	$note = $line['note'];

	$note = dol_concatdesc($note, $langs->trans("Workload").' : '.($line['line_planned_workload'] ? convertSecondToTime($line['line_planned_workload'], 'allhourmin') : ''));

	$dataObject = json_encode($line['line_dataObject']);

	$s .= "taskItem = new JSGantt.TaskItem('".$lineid."', '".dol_escape_js(trim($name))."', '".$start_date."', '".$end_date."', '".$css."', '".$link."', ".$line['line_milestone'].", '".dol_escape_js($resources)."', ".($percent >= 0 ? $percent : 0).", ".$line_is_auto_group.", '".$parent."', 1, '".$dependency."', '".(empty($line["line_is_group"]) ? (($percent >= 0 && $percent != '') ? $percent.'%' : '') : '')."', '".dol_escape_js($note)."', g);\n";
	$s .= "taskItem.setDataObject(".$dataObject.");\n";
	$s .= "g.AddTaskItem(taskItem);";

	echo $s;
}

/**
 * Find child Gantt line
 *
 * @param 	array	$tarr					tarr
 * @param	int		$parent					Parent
 * @param 	array	$line_dependencies		Line dependencies
 * @param 	int		$level					Level
 * @return	void
 */
function findChildGanttLine($tarr, $parent, $line_dependencies, $level)
{
	$n = count($tarr);

	$old_parent_id = 0;
	for ($x = 0; $x < $n; $x++) {
		if ($tarr[$x]["line_parent"] == $parent && $tarr[$x]["line_parent"] != $tarr[$x]["line_id"]) {
			// Create a grouping parent line for the new level
			/*if (empty($old_parent_id) || $old_parent_id != $tarr[$x]['line_project_id'])
			{
				$tmpt = array(
				'line_id'=> -98, 'line_name'=>'Level '.$level, 'line_resources'=>'', 'line_start_date'=>'', 'line_end_date'=>'',
				'line_is_group'=>1, 'line_css'=>'ggroupblack', 'line_milestone'=> 0, 'line_parent'=>$tarr[$x]["line_parent"], 'line_notes'=>'');
				constructGanttLine($lines, $tmpt, array(), 0, $tarr[$x]['line_project_id']);
				$old_parent_id = $tarr[$x]['line_project_id'];
			}*/

			constructGanttLine($tarr, $tarr[$x], $line_dependencies, $level, null);
			findChildGanttLine($tarr, $tarr[$x]["line_id"], $line_dependencies, $level + 1);
		}
	}
}
