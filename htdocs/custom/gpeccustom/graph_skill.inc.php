<?php
/* Copyright (C) 2024 Soufiane FADEL  <s.fadel@optim-industries.fr>
 *
 */


$mode = GETPOST('mode', 'aZ09');
$now = dol_now();

$date_val_startmonth = GETPOST('date_val_startmonth', 'int');
$date_val_startday = GETPOST('date_val_startday', 'int');
$date_val_startyear = GETPOST('date_val_startyear', 'int');
$date_val_endmonth = GETPOST('date_val_endmonth', 'int');
$date_val_endday = GETPOST('date_val_endday', 'int');
$date_val_endyear = GETPOST('date_val_endyear', 'int');

$date_val_start = dol_mktime(-1, -1, -1, $date_val_startmonth, $date_val_startday, $date_val_startyear);
$date_val_end = dol_mktime(-1, -1, -1, $date_val_endmonth, $date_val_endday, $date_val_endyear);

$date_skill_startmonth = GETPOST('date_skill_startmonth', 'int');
$date_skill_startday = GETPOST('date_skill_startday', 'int');
$date_skill_startyear = GETPOST('date_skill_startyear', 'int');
$date_skill_endmonth = GETPOST('date_skill_endmonth', 'int');
$date_skill_endday = GETPOST('date_skill_endday', 'int');
$date_skill_endyear = GETPOST('date_skill_endyear', 'int');

$date_skill_start = dol_mktime(-1, -1, -1, $date_skill_startmonth, $date_skill_startday, $date_skill_startyear);
$date_skill_end = dol_mktime(-1, -1, -1, $date_skill_endmonth, $date_skill_endday, $date_skill_endyear);

$date_nbusers_startmonth = GETPOST('date_nbusers_startmonth', 'int');
$date_nbusers_startday = GETPOST('date_nbusers_startday', 'int');
$date_nbusers_startyear = GETPOST('date_nbusers_startyear', 'int');
$date_nbusers_endmonth = GETPOST('date_nbusers_endmonth', 'int');
$date_nbusers_endday = GETPOST('date_nbusers_endday', 'int');
$date_nbusers_endyear = GETPOST('date_nbusers_endyear', 'int');

$date_nbusers_start = dol_mktime(-1, -1, -1, $date_nbusers_startmonth, $date_nbusers_startday, $date_nbusers_startyear);
$date_nbusers_end = dol_mktime(-1, -1, -1, $date_nbusers_endmonth, $date_nbusers_endday, $date_nbusers_endyear);


$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);


if (empty($date_start)) {
	$date_start = dol_time_plus_duree($now, -7, 'y');
}

if (empty($date_end)) {
	$date_end = dol_time_plus_duree($now, 1, 'y');
}


if (empty($date_skill_start)) {
	$date_skill_start = dol_time_plus_duree($now, -7, 'y');
}

if (empty($date_skill_end)) {
	$date_skill_end = dol_time_plus_duree($now, 1, 'y');
}

if (empty($date_nbusers_start)) {
	$date_nbusers_start = dol_time_plus_duree($now, -7, 'y');
}

if (empty($date_nbusers_end)) {
	$date_nbusers_end = dol_time_plus_duree($now, 1, 'y');
}

if (empty($date_val_start)) {
	$date_val_end = null;
} else {
	$date_val_end = $now;
}

/**
 * action
 */
$skillJobsdata = $cv->getskillJobAvgGraph($arr_skill_jobs, $filter_fk_user, $date_start, $date_end);


if (sizeof($skillJobsdata) > 0) {
	foreach ($skillJobsdata as $val) {

		$avrgnote[$val->fk_job][$val->date_eval] = $val;
		$alltotaljs[$val->fk_job] = $val->fk_job;
		$arrSkillJobs[] = $val->fk_job;

		$dataUsers[$val->fk_job][dol_print_date($val->date_eval, '%Y')] = array('label' => $val->job_label, 'year' => dol_print_date($val->date_eval, '%Y'), 'avg' => $val->avrgnote);
	}

	$skillJobs = empty($arr_skill_jobs) ? $arrSkillJobs : $arr_skill_jobs;

	//default colors for dataseries
	$datacolors = array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF');

	foreach ($dataUsers as $key => $values) {
		foreach ($values as $val) {

			//call to dynamic colors code generation function if not default colors code
			array_push($datacolors, random_color());
			if (!empty($arr_skill_jobs) || $filter_fk_user > 0) {
				//datseries dynamic reconstruncting 
				foreach ($skillJobs as $jobs) {
					$avgjobs[$val['year']][] = $key == $jobs ? array('year' => $val['year'], 'avg_' . $jobs => $val['avg']) : array('avg-' . $jobs => 0.0);


					$label = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
					$words = explode(" ", $label);
					$acronym = "";

					foreach ($words as $w) {
						$acronym .= mb_substr($w, 0, 5);
						$acronym .= " ";
						$key == $jobs ? $labeljs[$key] =  ucfirst($acronym) : null;
					}
				}
			} elseif (empty($arr_skill_jobs) && ($filter_fk_user == -1 || $filter_fk_user == '')) {
				$label = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
				$words = explode(" ", $label);
				$acronym = "";

				foreach ($words as $w) {
					$acronym .= mb_substr($w, 0, 5);
					$acronym .= ". ";

					$dataseriesforavfjob[$key] = array(ucfirst($acronym), $val['avg']);
				}
			}
		}
	}


	//list of user's jobs and skills
	$dataskillJobsall = $cv->getAvrSkillJobs($filter_fk_user);
	foreach ($dataskillJobsall as $val) {
		$arrskilljobs[$val->fk_job] = $val->job_label;
	}



	$totaljs = sizeof($alltotaljs);

	ksort($avgjobs);


	foreach ($avgjobs as $key => $val) {
		$flattenarr[] = call_user_func_array('array_merge', $val);
	}


	foreach ($flattenarr as $p) {
		$sizearrs[] = sizeof($p);
		//make position 0 (first value) in subarray
		array_unshift($p, $p['year']);
		unset($p['year']);
		ksort(array_keys($p));

		$data[] = $p;
	}

	//control position order of values in array for graph data and filter null duplicates values
	foreach ($data as $k => $values) {
		$empty = empty(array_filter($values, function ($a) {
			return $a !== null;
		}));

		foreach ($values as $key => $value) {
			if (!$empty) {
				$arr[$k][0] = $values[0];
				if ("avg-" == substr($key, 0, 4) || "avg_" == substr($key, 0, 4)) {
					$str = substr($key, strrpos($key, '_'));
					$str2 = substr($key, strrpos($key, '-'));

					$num = preg_replace("/[^0-9]/", '', $str);
					$num2 = preg_replace("/[^0-9]/", '', $str2);
					$nums[$num] = $num;

					if ($value > 0) {
						$arr2[$k][$num] = $value;
					}

					foreach ($nums as $n) {
						$arr[$k][$n] = $arr2[$k][$n] == null ? 0 : $arr2[$k][$n];
					}
				}
			}
		}
	}


	foreach ($arr as $vals) {
		$nbval[] = sizeof($vals);
		$nbvalues = max($nbval);

		$dataseriesforavfjob[] = array_values($vals);
	}
}



// var_dump(sizeof($dataseriesforavfjob));
// include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			


print '<div class="fichethirdleft">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';

print  '<tr class="liste_titre">';
print  '<td>' . $langs->trans("Cartographie") . ' - ' . $langs->trans("Moyenne des compétences par emploi") . '</td>';

print '<td class="right">';
if (!empty(array_filter($arr_skill_jobs)) && sizeof(array_filter($arr_skill_jobs)) > 0) {
	print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill_jobs)) . ' emploi(s) séléctionné(s)">' . sizeof($arr_skill_jobs) . '</span>';
}
if ($filter_fk_user != -1 && $filter_fk_user != '') {
	print '&nbsp;&nbsp;';
	print '<span class="classfortooltip badge badge-info right" title="1 emplyée(s) séléctionné(s)">1</span>';
}
print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgjs"></span></td>';
print  '</tr>';

if ($conf->use_javascript_ajax) {
	print  '<script type="text/javascript">
			 jQuery(document).ready(function() {
				 jQuery("#idsubimgjs").click(function() {
					 jQuery("#idfilterjs").toggle();
				 });
			 });
			 </script>';
	print '<tr>';
	print '<td colspan="2">';
	print  '<div class="center hideobject" id="idfilterjs">'; // hideobject is to start hidden
	print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print  '<input type="hidden" name="token" value="' . newToken() . '">';
	print  '<input type="hidden" name="action" value="refresh_js">';
	print  '<input type="hidden" name="page_y" value="">';

	print  $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);

	// print '<div class="right">';
	// print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	// print '<span class="fas fa-chart-bars" name="bars" title="Affichage en barres"></span>';
	// print '&nbsp;&nbsp;';
	// print '<span class="fas fa-chart-line" name="lines" title="Affichage en courbes"></span>';
	// print '</div>';

	print '<br/>';
	print '<div>';
	print img_picto('', 'skill', 'class="pictofixedwidth"');
	print $form->multiselectarray('arr_skill_jobs',  $arrskilljobs,  $arr_skill_jobs, '', '', '', '', '65%', '', '', 'Emploi');
	// print  ' &nbsp; ';
	print '</div>';
	print '<div>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers($filter_fk_user, 'filter_fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
	print '</div>';
	print '<br/>';
	print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
	print  '</form>';
	print  '</div>';

	// 	print  '</div>';
	print '</td>';
	print '</tr>';

	print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

	include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

	$WIDTH = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '800' : '800';
	$HEIGHT = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '360' : '360';
	$NBLEG = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? 2 : 1;

	$dolgraph = new DolGraph();
	$mesg = $dolgraph->isGraphKo();
	if (!$mesg && $action = 'refresh_js') {
		$dolgraph->SetData(array_values($dataseriesforavfjob));
		$dolgraph->SetDataColor(array_values($datacolors));
		$dolgraph->setLegend(array_values(array_unique(array_filter($labeljs))));

		if (!empty($arr_skill_jobs) || ($filter_fk_user != -1 && $filter_fk_user != '')) {
			$dolgraph->setShowLegend($NBLEG);
			// $dolgraph->setShowPercent(1);
			$dolgraph->SetHeight($HEIGHT);
			$dolgraph->SetWidth($WIDTH);
			// $dolgraph->SetType(array('lines'));
			$dolgraph->SetType(array('bars'));
		} else {
			// $dolgraph->setShowLegend(2);
			$dolgraph->SetHeight('800');
			$dolgraph->SetWidth($WIDTH);
			$dolgraph->SetType(array('polar'));
		}


		$dolgraph->draw('idgraphavgjobskill');
		print $dolgraph->show($totaljs ? 0 : 1);

		print '</td></tr>';
	}
	print  '<tr class="liste_total">';
	print  '<td>' . $langs->trans("Total des emplois évalués") . '</td>';
	print  '<td class="right">' . $totaljs . '</td>';
	print  '</tr>';

	print  '</table>';
	print  '</div>';
	// print  '<br>';
}
if (empty($conf->use_javascript_ajax)) {
	$langs->load("errors");
	print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
}

// print '</div>';
print "<br>";

/**
 * actions 
 */
$skillData = $cv->getskillAvgGraph($arr_skill, $skill_fk_user, $date_skill_start, $date_skill_end);

if (sizeof($skillData) > 0) {
	foreach ($skillData as $val) {

		$avgskillnote[$val->fk_skill][$val->date_eval] = $val;
		$alltotalskill[$val->fk_skill] = $val->fk_skill;
		$arrSkill[] = $val->fk_skill;
		$arrskill[$val->fk_skill] = $val->skill_label;

		$dataSkill[$val->fk_skill][dol_print_date($val->date_eval, '%Y')] = array('label' => $val->skill_label, 'year' => dol_print_date($val->date_eval, '%Y'), 'avg' => $val->avgskillnote);
	}

	$skillids = empty($arr_skill) ? $arrSkill : $arr_skill;

	//default colors for dataseries
	$datacolors = array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF');

	foreach ($dataSkill as $key => $values) {
		foreach ($values as $val) {

			//call to dynamic colors code generation function if not default colors code
			array_push($datacolors, random_color());
			if (!empty($arr_skill) || $skill_fk_user > 0) {

				foreach ($skillids as $skillid) {
					$avgskill[$val['year']][] = $key == $skillid ? array('year' => $val['year'], 'avg_' . $skillid => $val['avg']) : array('avg-' . $skillid => 0.0);


					$labels = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
					$words = explode(" ", $labels);
					$acronym = "";

					foreach ($words as $w) {
						$acronym .= mb_substr($w, 0, 5);
						$acronym .= " ";
						$key == $skillid ? $labelskill[$key] =  ucfirst($acronym) : null;
					}
				}
			} elseif (empty($arr_skill) && ($skill_fk_user == -1 || $skill_fk_user == '')) {
				$labelskill = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
				$words = explode(" ", $labelskill);
				$acronym = "";

				foreach ($words as $w) {
					$acronym .= mb_substr($w, 0, 5);
					$acronym .= ". ";

					$dataseriesforavgskill[$key] = array(ucfirst($acronym), $val['avg']);
				}
			}
		}
	}


	// list of user's jobs and skills
	$dataskillall = $cv->getAvrSkill($skill_fk_user);
	foreach ($dataskillall as $val) {
		$arrskillall[$val->fk_skill] = $val->skill_label;
	}


	$totalskill = sizeof($alltotalskill);

	ksort($avgskill);


	foreach ($avgskill as $key => $val) {
		$flattenarrskill[] = call_user_func_array('array_merge', $val);
	}


	foreach ($flattenarrskill as $p) {
		$sizearrs[] = sizeof($p);
		//make position 0 (first value) in subarray
		array_unshift($p, $p['year']);
		unset($p['year']);
		ksort(array_keys($p));

		$skdata[] = $p;
	}

	//control position order of values in array for graph data and delete duplicates if they are null
	foreach ($skdata as $k => $values) {
		$empty = empty(array_filter($values, function ($a) {
			return $a !== null;
		}));

		foreach ($values as $key => $value) {
			if (!$empty) {
				$arrsk[$k][0] = $values[0];
				if ("avg-" == substr($key, 0, 4) || "avg_" == substr($key, 0, 4)) {
					$str = substr($key, strrpos($key, '_'));
					$str2 = substr($key, strrpos($key, '-'));

					$num = preg_replace("/[^0-9]/", '', $str);
					$num2 = preg_replace("/[^0-9]/", '', $str2);
					$nums[$num] = $num;

					if ($value > 0) {
						$arrsk2[$k][$num] = $value;
					}

					foreach ($nums as $n) {
						$arrsk[$k][$n] = $arrsk2[$k][$n] == null ? 0 : $arrsk2[$k][$n];
					}
				}
			}
		}
	}


	foreach ($arrsk as $vals) {
		$nbval[] = sizeof($vals);
		$nbvalues = max($nbval);

		$dataseriesforavgskill[] = array_values($vals);
	}
}

/**
 * view 
 */
print '</div><div class="fichetwothirdright">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';

print  '<tr class="liste_titre">';
print  '<td>' . $langs->trans("Cartographie") . ' - ' . $langs->trans("Moyenne des compétences") . '</td>';

print '<td class="right">';
if (!empty(array_filter($arr_skill)) && sizeof(array_filter($arr_skill)) > 0) {
	print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill)) . ' compétence(s) séléctionné(s)">' . sizeof($arr_skill) . '</span>';
}
if ($skill_fk_user != -1 && $skill_fk_user != '') {
	print '&nbsp;&nbsp;';
	print '<span class="classfortooltip badge badge-info right" title="1 emplyée(s) séléctionné(s)">1</span>';
}
print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgskill"></span></td>';
print  '</tr>';

if ($conf->use_javascript_ajax) {
	print  '<script type="text/javascript">
			 jQuery(document).ready(function() {
				 jQuery("#idsubimgskill").click(function() {
					 jQuery("#idfilterskill").toggle();
				 });
			 });
			 </script>';
	print '<tr>';
	print '<td colspan="2">';
	print  '<div class="center hideobject" id="idfilterskill">'; // hideobject is to start hidden
	print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print  '<input type="hidden" name="token" value="' . newToken() . '">';
	print  '<input type="hidden" name="action" value="refresh_skill">';
	print  '<input type="hidden" name="page_y" value="">';

	print  $form->selectDate($date_skill_start, 'date_skill_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_skill_end, 'date_skill_end', 0, 0, 0, '', 1, 0);

	// print '<div class="right">';
	// print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	// print '<span class="fas fa-chart-bars" name="bars" title="Affichage en barres"></span>';
	// print '&nbsp;&nbsp;';
	// print '<span class="fas fa-chart-line" name="lines" title="Affichage en courbes"></span>';
	// print '</div>';

	print '<br/>';
	print '<div>';
	print img_picto('', 'skill', 'class="pictofixedwidth"');
	print $form->multiselectarray('arr_skill',  $arrskillall,  $arr_skill, '', '', '', '', '65%', '', '', 'Compétence');
	// print  ' &nbsp; ';
	print '</div>';
	print '<div>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers($skill_fk_user, 'skill_fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
	print '</div>';
	print '<br/>';
	print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
	print  '</form>';
	print  '</div>';

	// 	print  '</div>';
	print '</td>';
	print '</tr>';

	print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

	include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

	$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '800' : '800';
	$HEIGHT = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '800' : '800';
	$NBLEG = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? 2 : 1;

	$dolgraph = new DolGraph();
	$mesg = $dolgraph->isGraphKo();
	if (!$mesg && $action = 'refresh_js') {
		$dolgraph->SetData(array_values($dataseriesforavgskill));
		$dolgraph->SetDataColor(array_values($datacolors));
		$dolgraph->setLegend(array_values(array_unique(array_filter($labelskill))));

		if (!empty($arr_skill) || ($skill_fk_user != -1 && $skill_fk_user != '')) {
			$dolgraph->setShowLegend($NBLEG);
			// $dolgraph->setShowPercent(1);
			$dolgraph->SetHeight($HEIGHT);
			$dolgraph->SetWidth($WIDTH);
			// $dolgraph->SetType(array('lines'));
			$dolgraph->SetType(array('bars'));
		} else {
			// $dolgraph->setShowLegend(2);
			$dolgraph->SetHeight('800');
			$dolgraph->SetWidth($WIDTH);
			$dolgraph->SetType(array('polar'));
		}


		$dolgraph->draw('idgraphavgskill');
		print $dolgraph->show($totalskill ? 0 : 1);

		print '</td></tr>';
	}
	print  '<tr class="liste_total">';
	print  '<td>' . $langs->trans("Total des compétences évalués") . '</td>';
	print  '<td class="right">' . $totalskill . '</td>';
	print  '</tr>';

	print  '</table>';
	print  '</div>';
	// print  '<br>';
}
if (empty($conf->use_javascript_ajax)) {
	$langs->load("errors");
	print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
}
// print  '</div>';
// print  '</div>';


/**
 * actions
 */

$nbEvalUsers = $cv->getNbEvaluation('nb_eval_users', $nbusers_fk_user, $arr_agence, $date_nbusers_start, $date_nbusers_end);
$nbUsers = $cv->getNbEvaluation('nb_users', $nbusers_fk_user, $arr_agence, $date_nbusers_start, $date_nbusers_end);
ksort($nbEvalUsers);
ksort($nbUsers);

foreach ($nbUsers as $year => $val1) {
	foreach ($nbEvalUsers as $key => $val2) {
		if ($year == $key) {

			$listofusers[] =  array($year, $val1, $val2);
			$totalnbeval += $val2;
		}
		// elseif($year != $key && isset($val2)){
		// 	$listusers[] =  array($key, $val1, $val2);
		// }

		// $listofusers[1][] = $year == $key ? $val1 : 0;
		// $listofusers[2][] = $year == $key ? $val2 : 0;
	}
}


/**
 * views
 */
print '</div></div>';
print '<div class="fichecenter">';
print '<div class="fichethirdleft">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';

print  '<tr class="liste_titre">';
print  '<td>' . $langs->trans("Statistics") . ' - ' . $langs->trans("Evaluation - en cours") . '</td>';

print '<td class="right">';
if (!empty(array_filter($arr_agences)) && sizeof(array_filter($arr_agence)) > 0) {
	print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_agence)) . ' agence(s) séléctionné(s)">' . sizeof($arr_skill_jobs) . '</span>';
}
if ($nbusers_fk_user != -1 && $nbusers_fk_user != '') {
	print '&nbsp;&nbsp;';
	print '<span class="classfortooltip badge badge-info right" title="1 emplyée(s) séléctionné(s)">1</span>';
}
print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgagence"></span></td>';
print  '</tr>';

if ($conf->use_javascript_ajax) {
	print  '<script type="text/javascript">
			  jQuery(document).ready(function() {
				  jQuery("#idsubimgagence").click(function() {
					  jQuery("#idfilteragence").toggle();
				  });
			  });
			  </script>';
	print '<tr>';
	print '<td colspan="2">';
	print  '<div class="center hideobject" id="idfilteragence">'; // hideobject is to start hidden
	print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print  '<input type="hidden" name="token" value="' . newToken() . '">';
	print  '<input type="hidden" name="action" value="refresh_eval">';
	print  '<input type="hidden" name="page_y" value="">';

	print  $form->selectDate($date_nbusers_start, 'date_nbusers_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_nbusers_end, 'date_nbusers_end', 0, 0, 0, '', 1, 0);

	// print '<div class="right">';
	// print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	// print '<span class="fas fa-chart-bars" name="bars" title="Affichage en barres"></span>';
	// print '&nbsp;&nbsp;';
	// print '<span class="fas fa-chart-line" name="lines" title="Affichage en courbes"></span>';
	// print '</div>';

	print '<br/>';
	print '<div>';
	print img_picto('', 'group', 'class="pictofixedwidth"');
	print $form->multiselectarray('arr_agences',  $arragences,  $arr_agences, '', '', '', '', '65%', '', '', 'Agence');
	// print  ' &nbsp; ';
	print '</div>';
	print '<div>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers($nbusers_fk_user, 'nbusers_fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
	print '</div>';
	print '<br/>';
	print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
	print  '</form>';
	print  '</div>';

	// 	print  '</div>';
	print '</td>';
	print '</tr>';

	print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

	include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

	$WIDTH = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '800' : '800';
	$HEIGHT = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '360' : '360';
	$NBLEG = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? 2 : 1;

	$dolgraph = new DolGraph();
	$mesg = $dolgraph->isGraphKo();
	if (!$mesg) {
		$dolgraph->SetData(array_values($listofusers));
		$dolgraph->SetDataColor(array_values($datacolors));
		$dolgraph->setLegend(array('Employés·es de la Société', 'Evaluations efffectuées'));

		if (!empty($arr_agence) || ($nbusers_fk_user != -1 && $nbusers_fk_user != '')) {
			$dolgraph->setShowLegend($NBLEG);
			// $dolgraph->setShowPercent(1);
			$dolgraph->SetHeight($HEIGHT);
			$dolgraph->SetWidth($WIDTH);
			// $dolgraph->SetType(array('lines'));
			$dolgraph->SetType(array('bars'));
		} else {
			// $dolgraph->setShowLegend(2);
			$dolgraph->SetHeight('500');
			$dolgraph->SetWidth($WIDTH);
			$dolgraph->SetType(array('bar'));
		}

		$dolgraph->draw('idgraphnbevaluated');
		print $dolgraph->show($totalnbeval ? 0 : 1);

		print '</td></tr>';
	}
	print  '<tr class="liste_total">';
	print  '<td>' . $langs->trans("Total des évaluations") . '</td>';
	print  '<td class="right">' . $totalnbeval . '</td>';
	print  '</tr>';

	print  '</table>';
	print  '</div>';
	// print  '<br>';
}
if (empty($conf->use_javascript_ajax)) {
	$langs->load("errors");
	print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
}
/**
 * actions
 */
$nbonskills = $cv->getSkillEvaluated($skill_val_user, $arr_val_skill, $date_val_start, $date_val_end, 'all_users', 'on_evaluation');
$nbvalonskills = $cv->getSkillEvaluated($skill_val_user, $arr_val_skill, $date_val_start, $date_val_end,  'validate_users', 'on_evaluation');
$nboffskills = $cv->getSkillEvaluated($skill_val_user, $arr_val_skill, $date_val_start, $date_val_end, 'all_users', 'off_evaluation');

//   var_dump($validateForUsers);
//   var_dump($validateValUsers);
// var_dump($nbskills);
//   $nbvalonskills = $cv->getSkillEvaluated($skill_user, $arr_skill, $date_start, $date_end, 'validate_users', 'on_evaluation');
//   $nbskills = array_merge($nbonskills, $nbvalonskills, $nboffskills);
//   var_dump($nboffskills);
foreach ($nbvalonskills as $key => $val) {
	$nbValSkills[$val->label][$val->fk_user] += count($val->fk_user);
}

foreach ($nbonskills as $key => $val) {
	$nbOnSkills[$val->label][$val->fk_user] += count($val->fk_user);
}

foreach ($nboffskills as $key => $val) {
	$nbOffSkills[$val->label][$val->fk_user] += count($val->fk_user);
}
//   var_dump($nbOnSkills);
if (!empty($nboffskills)) {
	foreach ($nboffskills as $key => $val) {
		$label = str_replace([" de ", " d'", " des ", " en ", " et ", " le ", " la ", " les ", " du "], '- ', $val->label);
		$words = explode(" ", $label);
		$acronym = "";

		foreach ($words as $w) {
			$acronym .= mb_substr($w, 0, 5);
			$acronym .= ". ";
			$labelvaljs[$val->label] =  ucfirst($acronym);
		}
		$labels = array("Nb comp. de l'emploi/collaborateur affécté", "Nb comp. de l'emploi/collaborateur évalué sur l'emploi", "Nb comp. par collaborateur < seuil requis dans l'emploi");
		$datacolors = array("rgb(60, 147, 183, 0.9)", "rgb(137, 86, 161, 0.9)", "rgb(250, 190, 80, 0.9)");
		// var_dump($val->label.'--'.array_sum($nbOffSkills[$val->label]));
		// var_dump($val->label.'_'.sizeof($nbOffSkills[$val->label]));
		// print '<a class="butAction" href="card.php?rowid='.$id.'&action=edit&token='.newToken().'".link("https://developer.mozilla.org/")>'.$langs->trans("Modify").'</a>'."\n";
		$nbSkills[$val->label] = array($labelvaljs[$val->label], array_sum($nbOffSkills[$val->label]), array_sum($nbOnSkills[$val->label]), array_sum($nbValSkills[$val->label]));
	}
} else {
	foreach ($nbonskills as $key => $val) {
		$label = str_replace([" de ", " d'", " des ", " en ", " et ", " le ", " la ", " les ", " du "], '- ', $val->label);
		$words = explode(" ", $label);
		$acronym = "";

		foreach ($words as $w) {
			$acronym .= mb_substr($w, 0, 5);
			$acronym .= ". ";
			$labelvaljs[$val->label] =  ucfirst($acronym);
		}
		$labels = array("Nb compétences par collaborateur sur un poste évalué", "Nb compétences par collaborateur < seuil requis");
		$datacolors = array("rgb(137, 86, 161, 0.9)", "rgb(250, 190, 80, 0.9)");
		// var_dump($val->label.'--'.array_sum($nbOffSkills[$val->label]));
		// var_dump($val->label.'_'.sizeof($nbOffSkills[$val->label]));
		// print '<a class="butAction" href="card.php?rowid='.$id.'&action=edit&token='.newToken().'".link("https://developer.mozilla.org/")>'.$langs->trans("Modify").'</a>'."\n";
		$nbSkills[$val->label] = array($labelvaljs[$val->label], array_sum($nbOnSkills[$val->label]), array_sum($nbValSkills[$val->label]));
	}
}


/**
 * view 
 */
//  print '</div><div class="fichetwothirdright">';
print '</div></div>';
print '<div class="fichecenter">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';

print  '<tr class="liste_titre">';
print  '<td>' . $langs->trans("Statistics") . ' - ' . $langs->trans("Nombre de Compétence/Emploi/Evaluation") . '</td>';

print '<td class="right">';
if (!empty(array_filter($arr_val_skill)) && sizeof(array_filter($arr_val_skill)) > 0) {
	print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_val_skill)) . ' compétence(s) séléctionnée(s)">' . sizeof($arr_val_skill) . '</span>';
}
if ($skill_val_user != -1 && $skill_val_user != '') {
	print '&nbsp;&nbsp;';
	print '<span class="classfortooltip badge badge-info right" title="1 employé(e·s) sélectionné(e·s)">1</span>';
}
print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgvalskill"></span>';

print ' &nbsp; ';

if ($mode == 'off' || $mode == '') {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=on">';
	print '<span class="fas fa-ellipsis-v" title="Cliquer pour afficher toutes les compétences - avec possibilité de filtrer sur la période des emplois exercés"></span>';
	print '</a>';
}
if ($mode == 'on') {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=off">';
	print '<span class="fas fa-solid fa-banas fa-ellipsis-h" title="Cliquer pour afficher les dernières compétences relatives à des emplois en cours"></span>';
	print '</a>';
}
print ' &nbsp; ';
print '</td>';
print  '</tr>';

if ($conf->use_javascript_ajax) {
	print  '<script type="text/javascript">
			 jQuery(document).ready(function() {
				 jQuery("#idsubimgvalskill").click(function() {
					 jQuery("#idfiltervalskill").toggle();
				 });
			 });
			 </script>';
	print '<tr>';
	print '<td colspan="2">';
	print  '<div class="center hideobject" id="idfiltervalskill">'; // hideobject is to start hidden
	print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print  '<input type="hidden" name="token" value="' . newToken() . '">';
	print  '<input type="hidden" name="action" value="refresh_val_skill">';
	print  '<input type="hidden" name="page_y" value="">';

	print  $form->selectDate($date_val_start, 'date_val_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_val_end, 'date_val_end', 0, 0, 0, '', 1, 0);


	print '<br/>';
	print '<div>';
	print img_picto('', 'skill', 'class="pictofixedwidth"');
	print $form->multiselectarray('arr_val_skill',  $arrskill,  $arr_val_skill, '', '', '', '', '65%', '', '', 'Compétence');
	// print  ' &nbsp; ';
	print '</div>';
	print '<div>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers($skill_val_user, 'skill_val_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
	print '</div>';
	print '<br/>';
	print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
	print  '</form>';
	print  '</div>';

	// 	print  '</div>';
	print '</td>';
	print '</tr>';

	print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

	include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

	$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '1820' : '1820';
	$HEIGHT = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '500' : '500';
	$NBLEG = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? 2 : 1;

	$dolgraph = new DolGraph();
	$mesg = $dolgraph->isGraphKo();
	if (!$mesg) {
		$dolgraph->SetData(array_values($nbSkills));
		//  #FF0202
		$dolgraph->SetDataColor($datacolors);
		$dolgraph->setLegend($labels);


		if (!empty($arr_val_skill) || ($skill_val_user != -1 && $skill_val_user != '')) {
			$dolgraph->setShowLegend($NBLEG);
			// $dolgraph->setShowPercent(1);
			$dolgraph->SetHeight($HEIGHT);
			$dolgraph->SetWidth($WIDTH);
			// $dolgraph->SetType(array('lines'));
			$dolgraph->SetType(array('bars'));
		} else {
			// $dolgraph->setShowLegend(2);
			$dolgraph->SetHeight('600');
			$dolgraph->SetWidth($WIDTH);
			$dolgraph->SetType(array('bars'));
		}

		$dolgraph->draw('idgraphvalskill');
		print $dolgraph->show($totalskill ? 0 : 1);

		print '</td></tr>';
	}
	print  '<tr class="liste_total">';
	print  '<td>' . $langs->trans("Total des compétences évaluées") . '</td>';
	print  '<td class="right">' . $totalskill . '</td>';
	print  '</tr>';

	print  '</table>';
	print  '</div>';
	// print  '<br>';
}
if (empty($conf->use_javascript_ajax)) {
	$langs->load("errors");
	print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
}

//   print '</div>';
print "<br>";


/**
 * random generation colors code
 */
function random_color_part()
{
	return str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
}

function random_color()
{
	return '#' . random_color_part() . random_color_part() . random_color_part();
}

/**
 * 
 */
function abreviationWords($str, $key, $isInAarray)
{
	//abbreviation of the words in the label
	$label = str_replace([" de ", " d'", " des ", " en "], '', $str);
	$words = explode(" ", $label);
	$acronym = "";

	foreach ($words as $w) {
		$acronym .= mb_substr($w, 0, 5);
		$acronym .= " ";
		$isInAarray ? $labeljs[$key] =  ucfirst($acronym) : null;
	}
	return $labeljs;
}
