<?php
/* Copyright (C) 2024 Soufiane FADEL  <s.fadel@optim-industries.fr>
 *
 */

/**
 * display evolution of job creation 
 * 
 * @param object cvtec
 * 
 * 
 * @return string canvas of graph
 */
function getOpenJobsLineChart($cv)
{
	global $db, $langs, $conf;
	$form = new Form($db);
	$now = dol_now();


		
	$date_startmonth = GETPOST('date_startmonth', 'int');
	$date_startday = GETPOST('date_startday', 'int');
	$date_startyear = GETPOST('date_startyear', 'int');
	$date_endmonth = GETPOST('date_endmonth', 'int');
	$date_endday = GETPOST('date_endday', 'int');
	$date_endyear = GETPOST('date_endyear', 'int');

	$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
	$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);
	$arr_jobs_nb = GETPOST('arr_jobs_nb');

	if (empty($date_start)) {
		$date_start = dol_get_first_day(date('Y'), -5, false);
	}
	
	if (empty($date_end)) {
		$date_end = dol_get_last_day(date('Y'), 12, false);
	}

	//list of user's jobs and skills
	$dataskillJobsall = $cv->getAvrSkillJobs($filter_fk_user);
	foreach ($dataskillJobsall as $val) {
		$jobsnb[$val->fk_job] = $val->job_label;
	}

	//list of jobs 
	$jbs = $cv->getJob2($date_start, $date_end, $arr_jobs_nb);

	foreach($jbs as $jb) {
		$alltotal[$jb->fk_job] = $jb->fk_job;
		$nb[dol_print_date($jb->date_creation, '%m-%Y')][$jb->fk_job] = $jb->fk_job;
		//array to use in drop down list
		$arrjobs[$jb->fk_job] = $jb->job_label;
	
		//data to display in (diagram - number of jobs by year)
		$listofjs[dol_print_date($jb->date_creation, '%Y-%m')] = array('year' => dol_print_date($jb->date_creation, '%m-%Y'), 'nb' => sizeof($nb[dol_print_date($jb->date_creation, '%m-%Y')]));
	}

	$total = sizeof($alltotal);
	ksort($listofjs);
	foreach($listofjs as $key => $value) {
		$dataseries[] = array($value['year'],$value['nb']);
	}

	// Tri des données par année (ordre croissant)
	usort($dataseries, function($a, $b) {
		return $a[0] - $b[0];  
	});

	/**
	 * views
	 */
	$result = '<div class="div-table-responsive-no-min" id="jobnb">';
		$result .= '<table class="noborder">';
		$result .=  '<tr class="liste_titre">';
		$result .=  '<td colspan="2">'.$langs->trans("Statistics").' : '.$langs->trans("Évolution des Emplois Créés");
		$result .= '<div class="nocellnopadd boxclose floatright nowraponall">';
		$result .= '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgjobs"></span>';
		$result .= '</div>';
		$result .= '</td>';
		$result .= '</tr>';
		
		if ($conf->use_javascript_ajax) {
	
				$result .=  '<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery("#idsubimgjobs").click(function() {
								jQuery("#idfilterjobs").toggle();
							});
						});
						</script>';
				$result .= '<tr>';
				$result .= '<td colspan="2">';
				$result .=  '<div class="center hideobject" id="idfilterjobs">'; // hideobject is to start hidden
				$result .=  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '#jobnb">';
				$result .=  '<input type="hidden" name="token" value="' . newToken() . '">';
				$result .=  '<input type="hidden" name="action" value="refresh_js_job">';
				$result .=  '<input type="hidden" name="page_y" value="">';
		
				$result .=  $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
		
				// $result .= '<br/>';
				// $result .= '<div>';
				// $result .= img_picto('', 'skill', 'class="pictofixedwidth"');
				$result .= '<span class="fas fa-briefcase" style="margin-right: 5px;"></span>';
				$result .= $form->multiselectarray('arr_jobs_nb',  $jobsnb,  $arr_jobs_nb, '', '', '', '', '65%', '', '', 'Compétence');
				// $result .=  ' &nbsp; ';
				// $result .= '</div>';
			
				$result .=  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
				$result .=  '</form>';
				$result .=  '</div>';
		
				// 	$result .=  '</div>';
				$result .= '</td>';
				$result .= '</tr>';
			
			$result .= '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="4">';
			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dgraphjob = new DolGraph();
			$mesg = $dgraphjob->isGraphKo();
			if (!$mesg && $action='refresh_js_job') {
				$dgraphjob->SetData(array_values($dataseries));
				$dgraphjob->SetDataColor(array('#177F00'));
				$dgraphjob->setLegend(array('Nombre des Emplois types ouverts'));
				
				// if(!empty($arr_jobs)) {
				// 	$dolgraph->setShowLegend(2);
				// 	$dolgraph->setShowPercent(1);
					// $dolgraph->SetHeight('300');
					$dgraphjob->SetWidth('700');
					$dgraphjob->SetType(array('lines'));
				// }else{
					$dgraphjob->SetHeight('220');
					// $dolgraph->SetType(array('polar'));
				// }
				
				
				$dgraphjob->draw('idgraphcvjob');
				$result .= $dgraphjob->show($total ? 0 : 1);

				$result .= '</td></tr>';
			}
		$result .=  '<tr class="liste_total">';
		$result .=  '<td class="center nopaddingleftimp nopaddingrightimp">'.$langs->trans("Total des emplois").'</td>';
		$result .=  '<td class="center nopaddingleftimp nopaddingrightimp">'.$total.'</td>';
		$result .=  '</tr>';
		$result .=  '</table>';
		$result .=  '</div>';
		// $result .=  '<br>';
		// $result .=  '<br>';
	}
	if (empty($conf->use_javascript_ajax)) {
		$langs->load("errors");
		$result .= $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
	}

	return $result;
}

/**
 * display evolution of skills creation 
 * 
 * @param object cvtec
 * 
 * 
 * @return string canvas of graph
 */
function getOpenSkillsLineChart($cv)
{
	global $db, $langs, $conf;
	$form = new Form($db);
	$now = dol_now();

	$arr_jobskill_nb = GETPOST('arr_jobskill_nb');
		
	$date_startmonth = GETPOST('date_startsmonth', 'int');
	$date_startday = GETPOST('date_startsday', 'int');
	$date_startyear = GETPOST('date_startsyear', 'int');
	$date_endmonth = GETPOST('date_endsmonth', 'int');
	$date_endday = GETPOST('date_endsday', 'int');
	$date_endyear = GETPOST('date_endsyear', 'int');

	$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
	$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);
	

	if (empty($date_start)) {
		$date_start = dol_get_first_day(date('Y'), -5, false);
	}
	
	if (empty($date_end)) {
		$date_end = dol_get_last_day(date('Y'), 12, false);
	}

	//list of user's jobs and skills
	$dataskillJobsall = $cv->getAvrSkillJobs($filter_fk_user);
	foreach ($dataskillJobsall as $val) {
		$jobsskillsnb[$val->fk_job] = $val->job_label;
	}

	//list of skills
	$listskills = $cv->getSkills($date_start, $date_end, $arr_jobskill_nb);
	
	foreach($listskills as $sk) {
		$total += $sk->nb;
		$nb = $sk->nb;
		//array to use in drop down list
		$arrskill[$sk->skillid] = $sk->skill_label;
		//data to display in (diagram - number of jobs by year)
		$listofskill[dol_print_date($sk->date_creation, '%Y-%m')] = array('year' => dol_print_date($sk->date_creation, '%m-%Y'), 'nb' => $nb);
	}
	ksort($listofskill);
	foreach($listofskill as $key => $value) {
		$dataskillseries[] = array($value['year'], $value['nb']);
	}

	/**
	 * views
	 */
	$result = '<div class="div-table-responsive-no-min" id="skillnb">';
		$result .= '<table class="noborder">';
		$result .=  '<tr class="liste_titre">';
		$result .=  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Évolution des Compétences Créés").'s';
		$result .= '<div class="nocellnopadd boxclose floatright nowraponall">';
		$result .= '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgskills"></span>';
		$result .= '</div>';
		$result .= '</td>';
		$result .= '</tr>';
		if ($conf->use_javascript_ajax) {
			

			$result .=  '<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery("#idsubimgskills").click(function() {
								jQuery("#idfilterskill").toggle();
							});
						});
						</script>';
				$result .= '<tr>';
				$result .= '<td colspan="2">';
				$result .=  '<div class="center hideobject" id="idfilterskill">'; // hideobject is to start hidden
				$result .=  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '#skillnb">';
				$result .=  '<input type="hidden" name="token" value="' . newToken() . '">';
				$result .=  '<input type="hidden" name="action" value="refresh_js_skill">';
				$result .=  '<input type="hidden" name="page_y" value="">';
		
				$result .=  $form->selectDate($date_start, 'date_starts', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_end, 'date_ends', 0, 0, 0, '', 1, 0);
			
				$result .= '<div>';
				// $result .= img_picto('', 'skill', 'class="pictofixedwidth"');
				$result .= '<span class="fas fa-briefcase" style="margin-right: 5px;"></span>';
				$result .= $form->multiselectarray('arr_jobskill_nb',  $jobsskillsnb,  $arr_jobskill_nb, '', '', '', '', '65%', '', '', 'Compétence');
				// $result .=  ' &nbsp; ';
				$result .= '</div>';
			
				$result .=  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
				$result .=  '</form>';
				$result .=  '</div>';
		
				// 	$result .=  '</div>';
				$result .= '</td>';
				$result .= '</tr>';
				
			$result .= '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dgraphjob = new DolGraph();
			$mesg = $dgraphjob->isGraphKo();
			if (!$mesg && $action='refresh_js_skill') {
				$dgraphjob->SetData(array_values($dataskillseries));
				$dgraphjob->SetDataColor(array('#FF0202'));
				$dgraphjob->setLegend(array('Nombre des compétences'));
				$dgraphjob->SetWidth('700');
				$dgraphjob->SetType(array('lines'));
				$dgraphjob->SetHeight('220');
				$dgraphjob->draw('idgraphcvskill');
				$result .= $dgraphjob->show($total ? 0 : 1);
				$result .= '</td></tr>';
			}
		$result .=  '<tr class="liste_total">';
		$result .=  '<td class="center nopaddingleftimp nopaddingrightimp">'.$langs->trans("Total des compètences").'</td>';
		$result .=  '<td class="center nopaddingleftimp nopaddingrightimp">'.$total.'</td>';
		$result .=  '</tr>';

		$result .=  '</table>';
		$result .=  '</div>';
	
	}
	if (empty($conf->use_javascript_ajax)) {
		$langs->load("errors");
		$result .= $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
	}

	return $result;
}

/**
 * 
 */
function getAVGSkillsByJobsPolarChart($cv)
{
	global $db, $langs, $conf;
	$form = new Form($db);
	$formfile = new FormFile($db);
	$mode = GETPOST('mode', 'aZ09');
	$now = dol_now();
	$arr_skill_jobs = GETPOST('arr_skill_jobs');
	$filter_fk_user = GETPOST('filter_fk_user');

		
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

		
	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder">';

	$result .=  '<tr class="liste_titre">';
	$result .=  '<td>' . $langs->trans("Cartographie") . ' : ' . $langs->trans("Niveau Moyen des Compétences par Poste") . '</td>';

	$result .= '<td class="right">';
	if (!empty(array_filter($arr_skill_jobs)) && sizeof(array_filter($arr_skill_jobs)) > 0) {
		$result .= '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill_jobs)) . ' poste(s) séléctionné(s)">' . sizeof($arr_skill_jobs) . '</span>';
	}
	if ($filter_fk_user != -1 && $filter_fk_user != '') {
		$result .= '&nbsp;&nbsp;';
		$result .= '<span class="classfortooltip badge badge-info right" title="1 emplyée(s) séléctionné(s)">1</span>';
	}
	$result .= '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgjs"></span></td>';
	$result .=  '</tr>';

	if ($conf->use_javascript_ajax) {
		$result .=  '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#idsubimgjs").click(function() {
						jQuery("#idfilterjs").toggle();
					});
				});
				</script>';
		$result .= '<tr>';
		$result .= '<td colspan="2">';
		$result .=  '<div class="center hideobject" id="idfilterjs">'; // hideobject is to start hidden
		$result .=  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '#avgskilljob">';
		$result .=  '<input type="hidden" name="token" value="' . newToken() . '">';
		$result .=  '<input type="hidden" name="action" value="refresh_js">';
		$result .=  '<input type="hidden" name="page_y" value="">';

		$result .=  $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);

		$result .= '<br/>';
		$result .= '<div>';
		// $result .= img_picto('', 'skill', 'class="pictofixedwidth"');
		$result .= '<span class="fas fa-briefcase" style="margin-right: 5px;"></span>';
		$result .= $form->multiselectarray('arr_skill_jobs',  $arrskilljobs,  $arr_skill_jobs, '', '', '', '', '65%', '', '', 'Emploi');
		// $result .=  ' &nbsp; ';
		$result .= '</div>';
		$result .= '<div>';
		$result .= img_picto('', 'user', 'class="pictofixedwidth"');
		$result .= $form->select_dolusers($filter_fk_user, 'filter_fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
		$result .= '</div>';
		$result .= '<br/>';
		$result .=  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
		$result .=  '</form>';
		$result .=  '</div>';

		// 	$result .=  '</div>';
		$result .= '</td>';
		$result .= '</tr>';

		$result .= '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2" id="avgskilljob">';

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
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth('600');
				// $dolgraph->SetType(array('lines'));
				$dolgraph->SetType(array('bars'));
			} else {
				// $dolgraph->setShowLegend(2);
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth('600');
				$dolgraph->SetType(array('polar'));
			}


			$dolgraph->draw('idgraphavgjobskill');
			$result .= $dolgraph->show($totaljs ? 0 : 1);

			$result .= '</td></tr>';
		}
		$result .=  '<tr class="liste_total">';
		$result .=  '<td>' . $langs->trans("Total des emplois évalués") . '</td>';
		$result .=  '<td class="right">' . $totaljs . '</td>';
		$result .=  '</tr>';

		$result .=  '</table>';
		$result .=  '</div>';
		// $result .=  '<br>';
	}
	if (empty($conf->use_javascript_ajax)) {
		$langs->load("errors");
		$result .= $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
	}
	return $result;
}

/**
 * 
 */
function getAVGSkillsPolarChart($cv)
{
	global $db, $langs, $conf;
	$form = new Form($db);
	$formfile = new FormFile($db);
	$mode = GETPOST('mode', 'aZ09');
	$now = dol_now();
	$arr_skill = GETPOST('arr_skill');
	$skill_fk_user = GETPOST('skill_fk_user');

		
	$date_skill_startmonth = GETPOST('date_skill_startmonth', 'int');
	$date_skill_startday = GETPOST('date_skill_startday', 'int');
	$date_skill_startyear = GETPOST('date_skill_startyear', 'int');
	$date_skill_endmonth = GETPOST('date_skill_endmonth', 'int');
	$date_skill_endday = GETPOST('date_skill_endday', 'int');
	$date_skill_endyear = GETPOST('date_skill_endyear', 'int');

	$date_skill_start = dol_mktime(-1, -1, -1, $date_skill_startmonth, $date_skill_startday, $date_skill_startyear);
	$date_skill_end = dol_mktime(-1, -1, -1, $date_skill_endmonth, $date_skill_endday, $date_skill_endyear);
	
	if (empty($date_skill_start)) {
		$date_skill_start = dol_time_plus_duree($now, -7, 'y');
	}

	if (empty($date_skill_end)) {
		$date_skill_end = dol_time_plus_duree($now, 1, 'y');
	}

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

	$result = '<div class="div-table-responsive-no-min" style="width: inherit!important;" id="avgskill">';
	$result .= '<table class="noborder">';

	$result .=  '<tr class="liste_titre">';
	$result .=  '<td>' . $langs->trans("Cartographie") . ' : ' . $langs->trans("Niveau Moyen des Compétences") . '</td>';

	$result .= '<td class="right">';
	if (!empty(array_filter($arr_skill)) && sizeof(array_filter($arr_skill)) > 0) {
		$result .= '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill)) . ' compétence(s) séléctionné(s)">' . sizeof($arr_skill) . '</span>';
	}
	if ($skill_fk_user != -1 && $skill_fk_user != '') {
		$result .= '&nbsp;&nbsp;';
		$result .= '<span class="classfortooltip badge badge-info right" title="1 emplyée(s) séléctionné(s)">1</span>';
	}
	// $result .= '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgskill"></span></td>';
	$result .=  '</tr>';

	if ($conf->use_javascript_ajax) {
		$result .=  '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#idsubimgskill").click(function() {
						jQuery("#idfilterskill").toggle();
					});
				});
				</script>';
		$result .= '<tr>';
		$result .= '<td colspan="2">';
		$result .=  '<div class="center hideobject" id="idfilterskill">'; // hideobject is to start hidden
		$result .=  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '#avgskill">';
		$result .=  '<input type="hidden" name="token" value="' . newToken() . '">';
		$result .=  '<input type="hidden" name="action" value="refresh_skill">';
		$result .=  '<input type="hidden" name="page_y" value="">';

		$result .=  $form->selectDate($date_skill_start, 'date_skill_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_skill_end, 'date_skill_end', 0, 0, 0, '', 1, 0);

		$result .= '<br/>';
		$result .= '<div>';
		$result .= img_picto('', 'skill', 'class="pictofixedwidth"');
		$result .= $form->multiselectarray('arr_skill',  $arrskillall,  $arr_skill, '', '', '', '', '65%', '', '', 'Compétence');
		// $result .=  ' &nbsp; ';
		$result .= '</div>';
		$result .= '<div>';
		$result .= img_picto('', 'user', 'class="pictofixedwidth"');
		$result .= $form->select_dolusers($skill_fk_user, 'skill_fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
		$result .= '</div>';
		$result .= '<br>';
		$result .=  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
		$result .=  '</form>';
		$result .=  '</div>';

		// 	$result .=  '</div>';
		$result .= '</td>';
		$result .= '</tr>';

		$result .= '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

		include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

		$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '600' : '600';
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
				$dolgraph->setShowPercent(1);
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth('700');
				// $dolgraph->SetType(array('lines'));
				$dolgraph->SetType(array('bars'));
			} else {
				$dolgraph->setShowLegend(2);
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth('1000');
				$dolgraph->SetType(array('polar'));
			}


			$dolgraph->draw('idgraphavgskill');
			$result .= $dolgraph->show($totalskill ? 0 : 1);

			$result .= '</td></tr>';
		}
		$result .=  '<tr class="liste_total">';
		$result .=  '<td>' . $langs->trans("Total des compétences évalués") . '</td>';
		$result .=  '<td class="right">' . $totalskill . '</td>';
		$result .=  '</tr>';

		$result .=  '</table>';
		$result .=  '</div>';
		// $result .=  '<br>';
	}
	if (empty($conf->use_javascript_ajax)) {
		$langs->load("errors");
		$result .= $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
	}

	return $result;
}

function getNBEvalSkillsBarChart($cv)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	global $db, $langs, $conf;
	$form = new Form($db);
	$formfile = new FormFile($db);
	$mode = GETPOST('mode', 'aZ09');
	$now = dol_now();
	$datacolors = array("rgb(60, 147, 183, 0.9)", "rgb(137, 86, 161, 0.9)", "rgb(250, 190, 80, 0.9)");
	$nbusers_fk_user = GETPOST('nbusers_fk_user');
	$arr_jobeval = GETPOST('arr_jobeval');

	$date_nbusers_startmonth = GETPOST('date_nbusers_startmonth', 'int');
	$date_nbusers_startday = GETPOST('date_nbusers_startday', 'int');
	$date_nbusers_startyear = GETPOST('date_nbusers_startyear', 'int');
	$date_nbusers_endmonth = GETPOST('date_nbusers_endmonth', 'int');
	$date_nbusers_endday = GETPOST('date_nbusers_endday', 'int');
	$date_nbusers_endyear = GETPOST('date_nbusers_endyear', 'int');

	$date_nbusers_start = dol_mktime(-1, -1, -1, $date_nbusers_startmonth, $date_nbusers_startday, $date_nbusers_startyear);
	$date_nbusers_end = dol_mktime(-1, -1, -1, $date_nbusers_endmonth, $date_nbusers_endday, $date_nbusers_endyear);

	if (empty($date_nbusers_start)) {
		$date_nbusers_start = dol_time_plus_duree($now, -7, 'y');
	}
	
	if (empty($date_nbusers_end)) {
		$date_nbusers_end = dol_time_plus_duree($now, 1, 'y');
	}

	$nbEvalUsers = $cv->getNbEvaluation('nb_eval_users', $nbusers_fk_user, $arr_jobeval, $date_nbusers_start, $date_nbusers_end);
	$nbUsers = $cv->getNbEvaluation('nb_users', $nbusers_fk_user, $arr_jobeval, $date_nbusers_start, $date_nbusers_end);
	ksort($nbEvalUsers);
	ksort($nbUsers);
	//list of user's jobs and skills
	$dataskillJobsall = $cv->getAvrSkillJobs($filter_fk_user);
	foreach ($dataskillJobsall as $val) {
		$jobeval[$val->fk_job] = $val->job_label;
	}
	// $jobeval = 
	foreach ($nbUsers as $year => $val1) {
		foreach ($nbEvalUsers as $key => $val2) {
			if ($year == $key) {
				$listofusers[] =  array($year, $val1, $val2);
				$totalnbeval += $val2;
			}
		}
	}

	
	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder">';

	$result .=  '<tr class="liste_titre">';
	$result .=  '<td>' . $langs->trans("Statistics") . ' ' . $langs->trans("des Évaluations") . '</td>';

	$result .= '<td class="right">';
	if (!empty(array_filter($arr_jobeval)) && sizeof(array_filter($arr_jobeval)) > 0) {
		$result .= '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_jobeval)) . ' emploi(s) séléctionné(s)">' . sizeof($arr_jobeval) . '</span>';
	}
	if ($nbusers_fk_user != -1 && $nbusers_fk_user != '') {
		$result .= '&nbsp;&nbsp;';
		$result .= '<span class="classfortooltip badge badge-info right" title="1 emplyée(s) séléctionné(s)">1</span>';
	}
	$result .= '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgagence"></span></td>';
	$result .=  '</tr>';

	if ($conf->use_javascript_ajax) {
		$result .=  '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#idsubimgagence").click(function() {
						jQuery("#idfilteragence").toggle();
					});
				});
				</script>';
		$result .= '<tr>';
		$result .= '<td colspan="2">';
		$result .=  '<div class="center hideobject" id="idfilteragence">'; // hideobject is to start hidden
		$result .=  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '#eval">';
		$result .=  '<input type="hidden" name="token" value="' . newToken() . '">';
		$result .=  '<input type="hidden" name="action" value="refresh_eval">';
		$result .=  '<input type="hidden" name="page_y" value="">';

		$result .=  $form->selectDate($date_nbusers_start, 'date_nbusers_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_nbusers_end, 'date_nbusers_end', 0, 0, 0, '', 1, 0);

		$result .= '<br/>';
		$result .= '<div>';
		// $result .= img_picto('', 'skill', 'class="pictofixedwidth"');
		$result .= '<span class="fas fa-briefcase" style="margin-right: 5px;"></span>';
		$result .= $form->multiselectarray('arr_jobeval',  $jobeval,  $arr_jobeval, '', '', '', '', '65%', '', '', 'Agence');
		// $result .=  ' &nbsp; ';
		$result .= '</div>';
		$result .= '<div>';
		$result .= img_picto('', 'user', 'class="pictofixedwidth"');
		$result .= $form->select_dolusers($nbusers_fk_user, 'nbusers_fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
		$result .= '</div>';
		$result .= '<br/>';
		$result .=  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
		$result .=  '</form>';
		$result .=  '</div>';
		$result .= '</td>';
		$result .= '</tr>';

		$result .= '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2"  id="eval">';

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
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth($WIDTH);
				// $dolgraph->SetType(array('lines'));
				$dolgraph->SetType(array('bars'));
			} else {
				// $dolgraph->setShowLegend(2);
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth($WIDTH);
				$dolgraph->SetType(array('bar'));
			}

			$dolgraph->draw('idgraphnbevaluated');
			$result .= $dolgraph->show($totalnbeval ? 0 : 1);

			$result .= '</td></tr>';
		}
		$result .=  '<tr class="liste_total">';
		$result .=  '<td>' . $langs->trans("Total des évaluations") . '</td>';
		$result .=  '<td class="right">' . $totalnbeval . '</td>';
		$result .=  '</tr>';

		$result .=  '</table>';
		$result .=  '</div>';
		// $result .=  '<br>';
	}
	if (empty($conf->use_javascript_ajax)) {
		$langs->load("errors");
		$result .= $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
	}

	return $result;
}

function getSkillsAlertBarChart($cv)
{
	global $db, $langs, $conf;
	$form = new Form($db);
	$formfile = new FormFile($db);
	$mode = GETPOST('mode', 'aZ09');
	$now = dol_now();

	$skill_val_user = GETPOST('skill_val_user');
	$arr_val_skill = GETPOST('arr_val_skill');
	$arr_val_skilljobs = GETPOST('arr_val_skilljobs');

	$date_val_startmonth = GETPOST('date_val_startmonth', 'int');
	$date_val_startday = GETPOST('date_val_startday', 'int');
	$date_val_startyear = GETPOST('date_val_startyear', 'int');
	$date_val_endmonth = GETPOST('date_val_endmonth', 'int');
	$date_val_endday = GETPOST('date_val_endday', 'int');
	$date_val_endyear = GETPOST('date_val_endyear', 'int');

	$date_val_start = dol_mktime(-1, -1, -1, $date_val_startmonth, $date_val_startday, $date_val_startyear);
	$date_val_end = dol_mktime(-1, -1, -1, $date_val_endmonth, $date_val_endday, $date_val_endyear);

	if (empty($date_val_start)) {
		$date_val_end = null;
	} else {
		$date_val_end = $now;
	}
	// if (empty($date_val_start)) {
	// 	$date_val_start = dol_get_first_day(date('Y'), 1, false);
	// }
	
	// if (empty($date_val_end)) {
	// 	$date_val_end = dol_get_last_day(date('Y'), 12, false);
	// }
	//list of user's jobs and skills
	$arrskills = $cv->getarrSkills();
	// var_dump($dataskillJobsall);
	//list of user's jobs and skills
	$dataskillJobsall = $cv->getAvrSkillJobs($filter_fk_user);
	foreach ($dataskillJobsall as $val) {
		$jobskills[$val->fk_job] = $val->job_label;
	}

	$nbonskills = $cv->getSkillEvaluated($skill_val_user, $arr_val_skill, $date_val_start, $date_val_end, $arr_val_skilljobs, 'all_users', 'on_evaluation');
	$nbvalonskills = $cv->getSkillEvaluated($skill_val_user, $arr_val_skill, $date_val_start, $date_val_end,  $arr_val_skilljobs, 'validate_users', 'on_evaluation');
	$nboffskills = $cv->getSkillEvaluated($skill_val_user, $arr_val_skill, $date_val_start, $date_val_end, $arr_val_skilljobs, 'all_users', 'off_evaluation');

	foreach ($nbvalonskills as $key => $val) {
		$nbValSkills[$val->label][$val->fk_user] += count($val->fk_user);
	}
	
	foreach ($nbonskills as $key => $val) {
		$nbOnSkills[$val->label][$val->fk_user] += count($val->fk_user);
	}
	
	foreach ($nboffskills as $key => $val) {
		$nbOffSkills[$val->label][$val->fk_user] += count($val->fk_user);
	}

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

	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder">';

	$result .=  '<tr class="liste_titre">';
	$result .=  '<td>' . $langs->trans("Statistics") . ' : ' . $langs->trans("Répartition des Compétences, Emplois et Évaluations") . '</td>';

	$result .= '<td class="right">';
	if (!empty(array_filter($arr_val_skilljobs)) && sizeof(array_filter($arr_val_skilljobs)) > 0) {
		$result .= '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_val_skilljobs)) . ' poste(s) séléctionnée(s)">' . sizeof($arr_val_skill) . '</span>';
	}
	if (!empty(array_filter($arr_val_skill)) && sizeof(array_filter($arr_val_skill)) > 0) {
		$result .= '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_val_skill)) . ' compétence(s) séléctionnée(s)">' . sizeof($arr_val_skill) . '</span>';
	}
	if ($skill_val_user != -1 && $skill_val_user != '') {
		$result .= '&nbsp;&nbsp;';
		$result .= '<span class="classfortooltip badge badge-info right" title="1 employé(e·s) sélectionné(e·s)">1</span>';
	}
	$result .= '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgvalskill"></span>';

	$result .= ' &nbsp; ';

	if ($mode == 'off' || $mode == '') {
		$result .= '<a href="' . $_SERVER['PHP_SELF'] . '?mode=on">';
		$result .= '<span class="fas fa-ellipsis-v" title="Cliquer pour afficher toutes les compétences - avec possibilité de filtrer sur la période des emplois exercés"></span>';
		$result .= '</a>';
	}
	if ($mode == 'on') {
		$result .= '<a href="' . $_SERVER['PHP_SELF'] . '?mode=off">';
		$result .= '<span class="fas fa-solid fa-banas fa-ellipsis-h" title="Cliquer pour afficher les dernières compétences relatives à des emplois en cours"></span>';
		$result .= '</a>';
	}
	$result .= ' &nbsp; ';
	$result .= '</td>';
	$result .=  '</tr>';

	if ($conf->use_javascript_ajax) {
		$result .=  '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#idsubimgvalskill").click(function() {
						jQuery("#idfiltervalskill").toggle();
					});
				});
				</script>';
		$result .= '<tr>';
		$result .= '<td colspan="2">';
		$result .=  '<div class="center hideobject" id="idfiltervalskill">'; // hideobject is to start hidden
		$result .=  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '#nb">';
		$result .=  '<input type="hidden" name="token" value="' . newToken() . '">';
		$result .=  '<input type="hidden" name="action" value="refresh_val_skill">';
		$result .=  '<input type="hidden" name="page_y" value="">';

		$result .=  $form->selectDate($date_val_start, 'date_val_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_val_end, 'date_val_end', 0, 0, 0, '', 1, 0);


		$result .= '<br/>';
		$result .= '<div>';
		$result .= '<span class="fas fa-briefcase" style="margin-right: 5px;"></span>';
		$result .= $form->multiselectarray('arr_val_skilljobs',  $jobskills,  $arr_val_skilljobs, '', '', '', '', '65%', '', '', 'Compétence');
		// $result .=  ' &nbsp; ';
		$result .= '</div>';
		$result .= '<div>';
		$result .= img_picto('', 'skill', 'class="pictofixedwidth"');
		$result .= $form->multiselectarray('arr_val_skill',  $arrskills,  $arr_val_skill, '', '', '', '', '65%', '', '', 'Compétence');
		// $result .=  ' &nbsp; ';
		$result .= '</div>';
		
		$result .= '<div>';
		$result .= img_picto('', 'user', 'class="pictofixedwidth"');
		$result .= $form->select_dolusers($skill_val_user, 'skill_val_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
		$result .= '</div>';
		$result .= '<br/>';
		$result .=  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
		$result .=  '</form>';
		$result .=  '</div>';

		// 	$result .=  '</div>';
		$result .= '</td>';
		$result .= '</tr>';

		$result .= '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2"  id="nb">';

		include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

		$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '1220' : '1220';
		$HEIGHT = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '500' : '500';
		$NBLEG = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? 2 : 1;

		$dolgraph = new DolGraph();
		$mesg = $dolgraph->isGraphKo();
	
		if (!$mesg) {
			// Si des données existent
			$dolgraph->SetData(array_values($nbSkills));
			$dolgraph->SetDataColor($datacolors);
			$dolgraph->setLegend($labels);
		
			if (!empty($arr_val_skill) || ($skill_val_user != -1 && $skill_val_user != '')) {
				$dolgraph->setShowLegend($NBLEG);
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth($WIDTH);
				$dolgraph->SetType(array('bars'));
			} else {
				$dolgraph->SetHeight('600');
				$dolgraph->SetWidth($WIDTH);
				$dolgraph->SetType(array('bars'));
			}
		
			// Dessiner le graphique avec des données
			$dolgraph->draw('idgraphalertskill');
			$result .= $dolgraph->show();
			$result .= '</td></tr>';
		} 
		// $result .=  '<br>';
	}
	if (empty($conf->use_javascript_ajax)) {
		$langs->load("errors");
		$result .= $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
	}

	return $result; 
}

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


