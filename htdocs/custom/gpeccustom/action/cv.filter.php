<?php

//If a job has been selected, these skills are displayed in the drop-down list. 
//By default, all skills are displayed.
foreach($arr_job as $job) {
	$skillKeys[] = array_keys($arrjs[$job]);
}

foreach($skillKeys as $values) {
	foreach($values as $key) {
		$newarrskills[$key] = $arrskills[$key];
	}
}

//array used in drop-down list for skills and job's filtred skills. 
if(!empty($newarrskills)) {
	$arrskills = $newarrskills;
}

$arrskills = array_filter($arrskills);

if(empty($arr_job) && !empty($arr_skill)) {
	$arrjobs = $newuserjob;
}

print '<div class="fichecenter">';

print '<div class="filter_menu_wrapper">';

	// print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	// print '<input type="hidden" name="token" value="'.newToken().'">';
	// print '<input type="hidden" name="mode" value="'.$mode.'">';
	
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre" width="40%"><td class="liste_titre">'.$langs->trans("Recherche avancée").'</td>';
	
	print '<td class="liste_titre right" width="30%">';
	// print '<a href="#menu" class="menu-link"> <span class="far fa-caret-square-down right" aria-hidden="true"></span>';
	print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgcvsearch"></span>';
  	print '</a>';
	print '</td>';
	
	print '</tr>';

	//
	print  '<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#idsubimgcvsearch").click(function() {
					jQuery("#idsearchcv").toggle();
				});
			});
			</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject" id="idsearchcv">'; // hideobject is to start hidden
			// print  '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print  '<input type="hidden" name="token" value="'.newToken().'">';
			print  '<input type="hidden" name="action" value="refresh_cvsearch">';
			print  '<input type="hidden" name="page_y" value="">';

			// print  $form->selectDate($date_skill_start, 'date_skill_start', 0, 0, 1, '', 1, 0).' &nbsp; '.$form->selectDate($date_skill_end, 'date_skill_end', 0, 0, 0, '', 1, 0);
			// print '<br/>';
			print '<br/>';
			print '<div>';
			print $langs->trans("Emploi");
			print  ' &nbsp; ';
			// print img_picto('', 'skill', 'class="pictofixedwidth"');
			print '<i class="fas fa-user-md"></i>';
			print $form->multiselectarray('arr_job', $arrjobs, $arr_job, '', '', '', '', '700pxx', '','', 'Tous');
			print  ' &nbsp;  &nbsp;';
			print $langs->trans("skill");
			print  ' &nbsp; ';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print $form->multiselectarray('arr_skill', $arrskills, $arr_skill, '', '', '', '', '700pxx', '','', 'Tous');
			print '</div>';
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1).'">';
			// print  '</form>';
			print  '</div>';
			
			// 	print  '</div>';
		print '</td>';
		print '</tr>';
	//
	print '</table>';

if(!empty($arr_job) || !empty($arr_skill)) {
	print '<nav id="filter_menu">';	
	print '<div class="filter_menu">';
	
		print '<div class="fichecenter"><div class="fichethirdleft">';
		
		print '<table>';
		foreach($arr_job as $jobid) {
			print '<tr>';
			print '<td>';
			// print img_picto('', 'skill', 'class="pictofixedwidth"');
			print '<i class="fas fa-user-md"></i>';
			print  ' &nbsp; ';
			print '<span>'.$arrjobs[$jobid].'</span>';
			print '</td>';
			print '</tr>';
		}
		print '</table>';
	
		print '<br>';
		print '</div>';
	
		print '<div class="fichetwothirdright">';
		print '<table>';
	
		if(!empty($arr_skill)) {
			print '<div>';
			foreach($arr as $key => $val) {
				if(is_null($arrskills[$key])) {
					print '<tr>';
					print '<td class="tdoverflowmax260 maxwidth400onsmartphone">';
					// print '<span style ="color:red;font-size: 0.7em;">La compétence « '.$allskills[$key].' » ne correspond pas aux profils des métiers recherchés.</span>';
					print setEventMessages($langs->trans("La compétence « ".$allskills[$key]." » ne correspond pas aux profils des métiers recherchés."), null, 'errors');
					print '</td></tr>';
				}
				if(in_array($key, $arr_skill) && !is_null($arrskills[$key])) {
					// Niveaux des compétences
					print '<tr>';
					print '<td class="tdoverflowmax260 maxwidth300onsmartphone">';
					print img_picto('', 'skill', 'class="pictofixedwidth"');
					// print  ' &nbsp; ';
					print $arrskills[$key];
					print '</td>';
					print '<td>';
					print $form->selectarray('arr_level_'.$key.'', $val, $arr_level[$key], 1, 0, 0, '', 0, 32);
					print '</td></tr>';
				}
				
			}
		
			print '<tr><td></td><td class="center">';
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1).'">';
			print '</td></tr>';
		}
		
	
		print '</table>';
		print '<br>';
	
		print '</div>';
		
		print '</div>';
		
	print '</div>';
	
	print '</nav>';
}

	print '<div class="fichecenter">';
	print '</div>';
	// print '</form>';
    print '</div>';
	if(empty($arr_skill) && !empty($arr_job)) {
	print '<HR style="width: 50%;float:left;">';
	print '<br>';
	}
	if(!empty($arr_skill)) {
		print '<HR style="width: 50%;">';
		print '<br>';
	}
print '<br>';
print '</div>';