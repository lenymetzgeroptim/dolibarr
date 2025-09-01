
<?php

//If a job has been selected, these skills are displayed in the drop-down list. 
//By default, all skills are displayed.
// foreach($arr_job as $job) {
// 	$skillKeys[] = array_keys($arrjs[$job]);
// }

// foreach($skillKeys as $values) {
// 	foreach($values as $key) {
// 		$newarrskills[$key] = $arrskills[$key];
// 	}
// }

// //array used in drop-down list for skills and job's filtred skills. 
// if(!empty($newarrskills)) {
// 	$arrskills = $newarrskills;
// }

// $arrskills = array_filter($arrskills);

// if(empty($arr_job) && !empty($arr_skill)) {
// 	$arrjobs = $newuserjob;
// }

?>
<style>
#icon_count {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.count-circle {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    font-weight: bold;
    font-size: 10px;
    color: white;
    position: static; 
}


.job-counter {
    background-color: violet;
	margin-right: 5px;
}

.skill-counter {
    background-color: red;
	margin-right: 5px;
}

.level-counter {
    background-color: green;
	margin-right: 5px;
}

.warning-option {
    font-weight: bold;
}
.bold-select-text {
    font-weight: bold;
}
</style>
<?php

print '<div id="fichecenter" class="fichecenter">';
			
print '<div class="filter_menu_wrapper">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre" ><td class="liste_titre">'.$langs->trans("Recherche avancée").'</td>';
	
	print '<td class="liste_titre right" id="icon_count">';
	print '<span id="jobCount" class="count-circle job-counter" title="Nombre des emplois séléctionnés"></span>';
	print '<span id="skillCount" class="count-circle skill-counter" title="Nombre des compétences séléctionnées"></span>';
	print '<span id="levelCount" class="count-circle level-counter" title="Nombre des niveaux séléctionnés"></span>';
	// print '<a href="#menu" class="menu-link"> <span class="far fa-caret-square-down right" aria-hidden="true"></span>';
	// print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Afficher/Masquer les filtres" id="idsubcvsearch"></span>';
	//   print '</a>';
	print '</td>';
	
	print '</tr>';

	//
	// print  '<script type="text/javascript">
	// 		jQuery(document).ready(function() {
	// 			jQuery("#idsubcvsearch").click(function() {
	// 				jQuery("#idsearch").toggle();
	// 			});
	// 		});
	// 		</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject.back" id="idsearch">'; // hideobject is to start hidden
			print  '<input type="hidden" name="token" value="'.newToken().'">';
			print  '<input type="hidden" name="action" value="refresh_cvsearch">';
			print  '<input type="hidden" name="page_y" value="">';
			print '<div>';
			print $langs->trans("Emploi");
			print  ' &nbsp; ';
			print '<i class="fas fa-user-md"></i>';
			print '<select id="jobFilter" multiple>
						// <option value="">Selectionner un poste</option>
						<!-- Populate dynamically using JavaScript -->
					</select>';
			print  ' &nbsp;  &nbsp;';
			print $langs->trans("skill");
			print  ' &nbsp; ';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print '<select id="skillFilter" multiple>
						<option value="">Selectionner une compétence</option>
						<!-- Populate dynamically using JavaScript -->
					</select>';
			print '</div>';
			print '<br/>';
			print  '</div>';
		print '</td>';
		print '</tr>';
	print '</table>';
	print '<nav id="filter_menu">';	
	print '<div class="filter_menu">';
		print '<div id="levelSection" style="display:none;padding: 0px 20px 0px 20px;">';
		print '</div>';
		print '<br>';
	print '</nav>';
print '</div>';
