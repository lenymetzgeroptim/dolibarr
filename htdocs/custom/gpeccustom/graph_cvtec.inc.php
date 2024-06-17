<?php
/* Copyright (C) 2024 Soufiane FADEL  <s.fadel@optim-industries.fr>
 *
 */

//  require_once DOL_DOCUMENT_ROOT.'/custom/gpeccustom/class/cvtec.class.php';

//  $cv = new CVTec($db);


// //default colors for dataseries
// $datacolors = array('#FF0202', '#9E2B40', '#FD7F7F', '#177F00', '#D0D404', '#29D404', '#36FF09','#FCCACA', '#04D0D4', '#0005FF');

//   $WIDTH = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '300' : '380';
//   $HEIGHT = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '360' : '360';
//   $NBLEG = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? 2 : 1;
 

// var_dump(sizeof($dataseriesforavfjob));
// include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			

/**
 * actions 
 */
//list of jobs 
$jbs = $cv->getJob();

foreach($jbs as $jb) {
	$alltotal[$jb->fk_job] = $jb->fk_job;
	$nb[dol_print_date($jb->date_creation, '%m-%Y')][$jb->fk_job] = $jb->fk_job;
	//array to use in drop down list
	$arrjobs[$jb->fk_job] = $jb->job_label;
	// var_dump($nb);
	//data to display in (diagram - number of jobs by year)
	$listofjs[dol_print_date($jb->date_creation, '%m-%Y')] = array('year' => dol_print_date($jb->date_creation, '%m-%Y'), 'nb' => sizeof($nb[dol_print_date($jb->date_creation, '%m-%Y')]));
}
// var_dump($nb);
$total = sizeof($alltotal);
foreach($listofjs as $key => $value) {
	$dataseries[] = array($value['year'],$value['nb']);
}

/**
 * views
 */
print '<div class="fichecenter"><div class="fichethirdleft">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';

print  '<tr class="liste_titre">';
print  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Emplois ouverts dans l'entreprise").'</td>';

	
 	if ($conf->use_javascript_ajax) {
		print '</tr>';
	
		print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dgraphjob = new DolGraph();
		$mesg = $dgraphjob->isGraphKo();
		if (!$mesg && $action='refresh_job') {
			$dgraphjob->SetData(array_values($dataseries));
			$dgraphjob->SetDataColor(array('#177F00'));
			$dgraphjob->setLegend(array('Nombre des Emplois types ouverts'));
			
			// if(!empty($arr_jobs)) {
			// 	$dolgraph->setShowLegend(2);
			// 	$dolgraph->setShowPercent(1);
				// $dolgraph->SetHeight('300');
				$dgraphjob->SetWidth('800');
				$dgraphjob->SetType(array('lines'));
			// }else{
				$dgraphjob->SetHeight('220');
				// $dolgraph->SetType(array('polar'));
			// }
			
			
			$dgraphjob->draw('idgraphcvjob');
			print $dgraphjob->show($total ? 0 : 1);

			print '</td></tr>';
		}
	print  '<tr class="liste_total">';
	print  '<td>'.$langs->trans("Total des emplois").'</td>';
	print  '<td class="right">'.$total.'</td>';
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
//list of jobs 
$listskills = $cv->getSkills();
foreach($listskills as $sk) {
	$total += $sk->nb;
	$nb = $sk->nb;
	//array to use in drop down list
	$arrskill[$sk->skillid] = $sk->skill_label;
	//data to display in (diagram - number of jobs by year)
	$listofskill[dol_print_date($sk->date_creation, '%m-%Y')] = array('year' => dol_print_date($sk->date_creation, '%m-%Y'), 'nb' => $nb);
}


foreach($listofskill as $key => $value) {
	$dataskillseries[] = array($value['year'], $value['nb']);
}

/**
 * views
 */

print '</div><div class="fichetwothirdright">';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';

print  '<tr class="liste_titre">';
print  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("skill").'s</td>';

	
 	if ($conf->use_javascript_ajax) {
		print '</tr>';
	
		print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dgraphjob = new DolGraph();
		$mesg = $dgraphjob->isGraphKo();
		if (!$mesg && $action='refresh_job') {
			$dgraphjob->SetData(array_values($dataskillseries));
			$dgraphjob->SetDataColor(array('#FF0202'));
			$dgraphjob->setLegend(array('Nombre des compétences'));
			$dgraphjob->SetWidth('800');
			$dgraphjob->SetType(array('lines'));
		
			$dgraphjob->SetHeight('220');
			
			$dgraphjob->draw('idgraphcvskill');
			print $dgraphjob->show($total ? 0 : 1);

			print '</td></tr>';
		}
	print  '<tr class="liste_total">';
	print  '<td>'.$langs->trans("Total des compétences").'</td>';
	print  '<td class="right">'.$total.'</td>';
	print  '</tr>';

	print  '</table>';
	print  '</div>';
	// print  '<br>';
}
if (empty($conf->use_javascript_ajax)) {
	$langs->load("errors");
	print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
}

print '</div>';


