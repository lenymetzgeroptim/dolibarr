<?php
/* Copyright (C) 2021 METZGER Leny <l.metzger@optim-industries.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    feuilledetemps/lib/feuilledetemps.lib.php
 * \ingroup feuilledetemps
 * \brief   Library files with common functions for FeuilleDeTemps
 */


/**
 * Prepare admin pages header
 *
 * @return array
 */
function feuilledetempsAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("feuilledetemps@feuilledetemps");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/feuilledetemps/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/feuilledetemps/admin/silae_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFieldsSilae");
	$head[$h][2] = 'silae_extrafields';
	$h++;

	$head[$h][0] = dol_buildpath("/feuilledetemps/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@feuilledetemps:/feuilledetemps/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@feuilledetemps:/feuilledetemps/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'feuilledetemps');

	return $head;
}


function timesheet_prepare_head($mode, $fuser = null)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$h = 0;

	$param = '';
	$param .= ($mode ? '&mode='.$mode : '');
	if (is_object($fuser) && $fuser->id > 0 && $fuser->id != $user->id) {
		$param .= '&search_usertoprocessid='.$fuser->id;
	}

	$head[$h][0] = DOL_URL_ROOT."/custom/feuilledetemps/timesheet.php?per=week".($param ? $param : '');
	$head[$h][1] = $langs->trans("InputPerWeek");
	$head[$h][2] = 'inputperweek';
	$h++;


	$head[$h][0] = DOL_URL_ROOT."/custom/feuilledetemps/timesheet.php?per=month".($param ? $param : '');
	$head[$h][1] = $langs->trans("InputPerMonth");
	$head[$h][2] = 'inputpermonth';
	$h++;

	return $head;
}



/**
 * Output a task line into a perday intput mode
 *
 * @param	string	   	$mode								Mode d'affichage
 * @param	string	   	$inc								Line output identificator (start to 0, then increased by recursive call)
 * @param	int			$firstdaytoshow						First day to show
 * @param	int			$lastdaytoshow						Last day to show
 * @param	User|null	$fuser								Restrict list to user if defined
 * @param   string		$parent								Id of parent task to show (0 to show all)
 * @param   Task[]		$lines								Array of lines (list of tasks but we will show only if we have a specific role on task)
 * @param   int			$level								Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole						Array of roles user has on project
 * @param   string		$tasksrole							Array of roles user has on task
 * @param	string		$mine								Show only task lines I am assigned to
 * @param   int			$restricteditformytask				0=No restriction, 1=Enable add time only if task is assigned to me, 2=Enable add time only if tasks is assigned to me and hide others
 * @param   array       $isavailable						Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak					Old project id of last project break
 * @param	array		$arrayfields		   				Array of additional column
 * @param	Extrafields	$extrafields		    			Object extrafields
 * @param 	int			$modify								1 si les cases sont modifiables, 0 sinon 
 * @param   string[]    $css								css pour la couleur des cases
 * @param   string[]    $css_holiday						css pour la couleur des cases
 * @param   int 	 	$num_first_day						Numero du 1er jour du mois (0 si celui-ci n'est pas présent dans les dates affichées)
 * @param   int 	 	$num_last_day						Numero du dernier jour du mois (0 si celui-ci n'est pas différent du dernier jour du mois)
 * @param   string 	 	$type_deplacement					Type de déplacement de l'utilisateur : 'none', 'petitDeplacement' ou 'grandDeplacement'
 * @param   int[] 	 	$dayinloopfromfirstdaytoshow_array	Tableau avec l'ensemble des jours a afficher (amélioration de la performance)
 * @param   int 	 	$modifier_jour_conges				Droit de modifier les jours de congés
 * @param   double 	 	$temps_prec							Temps précédent de la semaine coupée de début de pointage 
 * @param   double 	 	$temps_suiv							Temps suivant de la semaine coupée de fin de pointage 
 * @param   double 	 	$temps_prec_hs25					Temps des HS à 25% précédent de la semaine coupée de début de pointage 
 * @param   double 	 	$temps_suiv_hs25					Temps des HS à 25% suivant de la semaine coupée de fin de pointage 
 * @param   double 	 	$temps_prec_hs50					Temps des HS à 50% précédent de la semaine coupée de début de pointage 
 * @param   double 	 	$temps_suiv_hs50					Temps des HS à 50% suivant de la semaine coupée de fin de pointage 
 * @param   string[] 	$notes								Tableau des notes de la feuille de temps
 * @param   double[] 	$otherTime							Autres temps : port EPI / Heure de nuit
 * @param   double[] 	$timeSpentMonth						Temps consommés du mois
 * @param   double[] 	$timeSpentWeek						Temps consommés pour chaque semaine du mois
 * @param   double[] 	$timeHoliday						Temps des congés
 * @param   double 	 	$heure_semaine						Heures travaillées par l'utilisateur dans la semaine
 * @param   double 	 	$heure_semaine_hs					Heures travaillées à partir desquelles l'utilisateur à droit à des HS dans la semaine
 * @param   int[] 	 	$favoris							Tâches ajoutées en favoris
 * @param   string 	 	$param								Paramètres pour les liens
 * @param   double[] 	$totalforeachday					Total des heures par jour pour la différence avec les tâches affichées
 * @param   array	 	$holiday_without_canceled			Tableau avec les congés non annulés de l'utilisateur
 * @param   int	 		$multiple_holiday					Est-ce qu'il faut 2 lignes pour les congés ?
 * @param   int 	 	$appel_actif						Numéro d'appel récursif pour gérer le footer du tableau
 * @param   int 	 	$nb_appel							Nombre d'appel récursif pour gérer le header du tableau
 * @return  array				Array with time spent for $fuser for each day of week on tasks in $lines and substasks
 */
function FeuilleDeTempsLinesPerWeek($mode, &$inc, $firstdaytoshow, $lastdaytoshow, $fuser, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, &$isavailable, $oldprojectforbreak = 0, $arrayfields = array(), $extrafields = null, 
									$modify = 1, $css = '', $css_holiday, $num_first_day = 0, $num_last_day = 0, $type_deplacement = 'none', $dayinloopfromfirstdaytoshow_array, $modifier_jour_conges,  
									$temps_prec, $temps_suiv, $temps_prec_hs25, $temps_suiv_hs25, $temps_prec_hs50, $temps_suiv_hs50, 
									$notes, $otherTime, $timeSpentMonth, $timeSpentWeek, $timeHoliday, $heure_semaine, $heure_semaine_hs, 
									$favoris = -1, $param = '', $totalforeachday, $holiday_without_canceled, $multiple_holiday, $heure_max_jour, $heure_max_semaine, $arraytypeleaves, $appel_actif = 0, $nb_appel = 0){
	global $conf, $db, $user, $langs, $action;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic, $object, $displayVerification;
	global $last_day_month;

	$holiday = new extendedHoliday($db);

	$numlines = count($lines);
	$lastprojectid = 0;
	$workloadforid = array();
	$totalforvisibletasks = array();
	$lineswithoutlevel0 = array();
	$u = 0;
	$total_hs25 = 0;
	$total_hs50 = 0;
	$nb_jour = sizeof($dayinloopfromfirstdaytoshow_array);
	$appel_actif++;
	$first_day_month = dol_time_plus_duree($firstdaytoshow, $num_first_day, 'd');

	// Header
	if($nb_appel == 0) {
		$addcolspan = 0;
		if (!empty($arrayfields['t.planned_workload']['checked'])) {
			$addcolspan++;
		}
		if (!empty($arrayfields['t.progress']['checked'])) {
			$addcolspan++;
		}
		if (!empty($arrayfields['timeconsumed']['checked'])) {
			$addcolspan += 2;
		}
		foreach ($arrayfields as $key => $val) {
			if ($val['checked'] && substr($key, 0, 5) == 'efpt.') {
				$addcolspan++;
			}
		}

		print '<div class="div-table-responsive" style="min-height: 0px">';
		print '<table class="tagtable liste listwithfilterbefore" id="tablelines_fdt">'."\n";
		print '<thead>';
		print '<tr class="liste_titre favoris">';
		print '<th class="fixed" colspan="'.(2 + $addcolspan).'" style="min-width: 500px;">';
		if($mode == 'card') {
			print '<button type="button" title="Plein écran" id="fullScreen" name="fullScreen" class="nobordertransp button_search_x"><span class="fa fa-expand" style="font-size: 1.7em;"></span></button>';
			print $fuser->getNomUrl(1);
		}
		print '</th>';

		// Affichage des jours de la semaine
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

			if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
				print '<th style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none; border-top: none !important; z-index:1;" width="9%"></th>';
			}

			print '<th width="9%" align="center" style="min-width: 90px; z-index: 1" class="bold hide'.$idw.' day">';
			print dol_print_date($dayinloopfromfirstdaytoshow, '%a');
			print '<br>'.dol_print_date($dayinloopfromfirstdaytoshow, 'dayreduceformat').'</th>';
		}
		print '<th class="fixed total_title" width="9%" style="min-width: 90px;"><strong>TOTAL</strong></th>';
		print "</tr>";

		// Affichage de la ligne avec le total de chaque semaine
		print '<tr class="liste_titre fixed favoris">';
		print '<th class="fixed" colspan="'.(2 + $addcolspan).'"></th>';
		$semaine = 1;
		for ($idw = 0; $idw < $nb_jour; $idw++) {			
			$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];
			$ecart_lundi = ecart_lundi($tmpday);
			$weekNumber = date("W", $tmpday);

			if ($idw == 0) {
				$taille = 7-$ecart_lundi;
			}
			elseif (dol_print_date($tmpday, '%a') == 'Lun' && $nb_jour - $idw < 7 && $idw-$ecart_lundi > 23 && dol_print_date($lastdaytoshow, '%a') != 'Dim'){
				$taille = $nb_jour - $idw;
			}
			elseif (dol_print_date($tmpday, '%a') == 'Lun' && $idw != 0) {	
				$taille = 7;
				$date = dol_time_plus_duree($tmpday, 7, 'd');

				if($first_day_month == $tmpday){
					print '<th style="min-width: 90px; border-left: 1px solid var(--colortopbordertitle1); border-bottom: none; border-top: none !important; z-index:1;" width="9%"></th>';
				}
				elseif($first_day_month > $tmpday && $first_day_month < $date){
					$taille++;
					$idw--;
				}
			}

			$premier_jour = $idw;
			$dernier_jour = $idw+$taille-1;

			print '<th class="liste_total_semaine_'.$semaine.'" align="center" colspan='.$taille.'><strong>Semaine '.$weekNumber.' : <span class="totalSemaine" name="totalSemaine'.$weekNumber.'" id="totalSemaine'.$semaine.'_'.$premier_jour.'_'.$dernier_jour.'">&nbsp</span></strong></td>';
			$semaine++;
			$idw += $taille - 1;
		}
		print '<th class="fixed total_week"></th>';
		print '</tr>';

		// Affichage de la ligne des congés			
		if(empty($conf->global->HOLIDAY_HIDE_BALANCE)) $conges_texte = $holiday->getArrayHoliday($fuser->id, 0, 1);
		$cptholiday = 0; 

		print '<tr class="nostrong liste_titre fixed conges">';
			print '<th colspan="'.(2 + $addcolspan).'" '.($multiple_holiday ? 'rowspan="2"' : '').' class="fixed">';
			if($mode == 'card' && $displayVerification && $conf->global->FDT_STATUT_HOLIDAY && !$conf->global->FDT_STATUT_HOLIDAY_VALIDATE_VERIF) {
				print '<input type="checkbox"'.(!$modify ? 'disabled' : '').' id="selectAllHoliday" onclick="toggleCheckboxesHoliday(this)"> ';
			}
			print '<strong>Congés</strong>';
			print $form->textwithpicto('', $conges_texte);
			print '</th>';
			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
				$keysuffix = '['.$idw.']';

				if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
					print '<th style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></th>';
				}

				if(!empty($holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][0])) {
					$holiday->fetch((int)$holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][0]);
					$numberDay = (num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) ? num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) : 1);
					if($idw + $numberDay > $nb_jour) $numberDay = $nb_jour - $idw;

					$durationHoliday = $holiday->getHourDuration($standard_week_hour, $dayinloopfromfirstdaytoshow, $fuser);

					if($mode == 'card') {
						print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? $css_holiday[$dayinloopfromfirstdaytoshow][0] : '').' statut'.$holiday->array_options['options_statutfdt'].'" colspan="'.($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month && ($dayinloopfromfirstdaytoshow_array[$idw + $numberDay] > $first_day_month || empty($dayinloopfromfirstdaytoshow_array[$idw + $numberDay]))? $numberDay + 1 : $numberDay).'">';
					}
					else {
						print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? $css_holiday[$dayinloopfromfirstdaytoshow][0] : '').'" colspan="'.($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month && ($dayinloopfromfirstdaytoshow_array[$idw + $numberDay] > $first_day_month || empty($dayinloopfromfirstdaytoshow_array[$idw + $numberDay]))? $numberDay + 1 : $numberDay).'">';
					}

					if($mode == 'card' && $displayVerification && $conf->global->FDT_STATUT_HOLIDAY && !$conf->global->FDT_STATUT_HOLIDAY_VALIDATE_VERIF) {
						print '<input type="checkbox"'.($holiday->array_options['options_statutfdt'] == 3 || !$modify ? ' disabled' : '').' name="holiday_valide['.$cptholiday.']" id="holiday_valide['.$cptholiday.']"'.($holiday->array_options['options_statutfdt'] != 1 ? ' checked' : '0').'> ';
					}

					print $holiday->getNomUrlBlank(2)." ".convertSecondToTime($durationHoliday, 'allhourmin');
					
					if($mode == 'card') {
						print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']"'.(!$modify  ? 'disabled' : ''), 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
					
						if($modify && $action != 'ediths00' && $action != 'ediths25' && $action != 'ediths50') {
							print '<input type="hidden" name="holiday_id['.$cptholiday.']"  id="holiday_id['.$cptholiday.']" value="'.$holiday->id.'">';
						}
					}
					else {
						print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']" disabled', 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
					}

					$idw += $numberDay - 1;
					$cptholiday++;
				}
				else {
					print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? ' '.$css_holiday[$dayinloopfromfirstdaytoshow][0] : '').'">';
				}

				print '</th>';
			}
			print '<th class="liste_total center fixed total_holiday"></th>';
		print '</tr>';

		if($multiple_holiday) {
			print '<tr class="nostrong liste_titre conges">';
			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
				$keysuffix = '['.$idw.']';
		
				if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
					print '<th style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></th>';
				}
		
				if(!empty($holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][1])) {
					$holiday->fetch((int)$holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][1]);
					$numberDay = (num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) ? num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) : 1);
					if($idw + $numberDay > $nb_jour) $numberDay = $nb_jour - $idw;

					$durationHoliday = $holiday->getHourDuration($standard_week_hour, $dayinloopfromfirstdaytoshow, $fuser);

					
					if($mode == 'card' && $conf->global->FDT_STATUT_HOLIDAY) {
						print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? $css_holiday[$dayinloopfromfirstdaytoshow][1] : '').' statut'.$holiday->array_options['options_statutfdt'].'" colspan="'.($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month && ($dayinloopfromfirstdaytoshow_array[$idw + $numberDay] > $first_day_month || empty($dayinloopfromfirstdaytoshow_array[$idw + $numberDay]))? $numberDay + 1 : $numberDay).'">';
					}
					else {
						print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? $css_holiday[$dayinloopfromfirstdaytoshow][1] : '').'" colspan="'.($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month && ($dayinloopfromfirstdaytoshow_array[$idw + $numberDay] > $first_day_month || empty($dayinloopfromfirstdaytoshow_array[$idw + $numberDay]))? $numberDay + 1 : $numberDay).'">';
					}

					if($mode == 'card' && $displayVerification && $conf->global->FDT_STATUT_HOLIDAY && !$conf->global->FDT_STATUT_HOLIDAY_VALIDATE_VERIF) {
						print '<input type="checkbox"'.($holiday->array_options['options_statutfdt'] == 3 || !$modify ? ' disabled' : '').' name="holiday_valide['.$cptholiday.']" id="holiday_valide['.$cptholiday.']"'.($holiday->array_options['options_statutfdt'] != 1 ? ' checked' : '0').'> ';
					}
		
					print $holiday->getNomUrlBlank(2)." ".convertSecondToTime($durationHoliday, 'allhourmin');
					
					if($mode == 'card') {
						print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']"'.(!$modify  ? 'disabled' : ''), 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
						if($modify && $action != 'ediths00' && $action != 'ediths25' && $action != 'ediths50') {
							print '<input type="hidden" name="holiday_id['.$cptholiday.']"  id="holiday_id['.$cptholiday.']" value="'.$holiday->id.'">';
						}
					}
					else {
						print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']" disabled', 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
					}
		
					$idw += $numberDay - 1;
					$cptholiday++;
				}
				else {
					print '<th class="center hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? ' '.$css_holiday[$dayinloopfromfirstdaytoshow][1] : '').'">';
				}
		
				print '</th>';
			}
			print '<th class="liste_total center fixed total_holiday"></th>';
			print '</tr>';
		}
		
		print '</thead>';
	}

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) { // Always and only if at first level
		for ($i = 0; $i < $numlines; $i++) {
			if ($lines[$i]->fk_task_parent) {
				$lineswithoutlevel0[] = $lines[$i];
			}
		}
	}

	if (empty($oldprojectforbreak)) {
		$oldprojectforbreak = (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT) ? 0 : -1); // 0 = start break, -1 = never break
	}

	// Boucle sur les taches
	for ($i = 0; $i < $numlines; $i++) {
		if ($parent == 0) {
			$level = 0;
		}

		if ($lines[$i]->fk_task_parent == $parent) {
			$obj = &$lines[$i]; // To display extrafields

			// If we want all or we have a role on task, we show it
			if (empty($mine) || str_contains($tasksrole[$lines[$i]->id], 'TASKCONTRIBUTOR')) {
					// Break on a new project
					if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid) {
						$lastprojectid = $lines[$i]->fk_project;
						$projectstatic->id = $lines[$i]->fk_project;
					}

					if (empty($workloadforid[$projectstatic->id])) {
						$projectstatic->weekWorkLoad = $timeSpentMonth[$projectstatic->id]['weekWorkLoad'];
						$projectstatic->weekWorkLoadPerTask = $timeSpentMonth[$projectstatic->id]['weekWorkLoadPerTask'];
						$workloadforid[$projectstatic->id] = 1;
					}

					$noTimespentInTask = 1;
					for ($idw = 0; $idw < $nb_jour; $idw++) {
						$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw];
		
						if(!empty($projectstatic->weekWorkLoadPerTask[$dayinloopfromfirstdaytoshow][$lines[$i]->id])) {
							$noTimespentInTask = 0;
							break;
						}
					}

				if(!$noTimespentInTask || ($object->status != $object::STATUS_VALIDATED && $object->status!= $object::STATUS_VERIFICATION && $object->status != $object::STATUS_EXPORTED)) {
					if ($restricteditformytask == 2 && !str_contains($tasksrole[$lines[$i]->id], 'TASKCONTRIBUTOR') && $noTimespentInTask) {	// we have no role on task and we request to hide such cases
						continue;
					}

					$projectstatic->id = $lines[$i]->fk_project;
					$projectstatic->ref = $lines[$i]->projectref;
					$projectstatic->title = $lines[$i]->projectlabel;
					$projectstatic->public = $lines[$i]->public;
					$projectstatic->thirdparty_name = $lines[$i]->thirdparty_name;
					$projectstatic->status = $lines[$i]->projectstatus;
					
					$taskstatic->id = $lines[$i]->id;
					$taskstatic->ref = ($lines[$i]->ref ? $lines[$i]->ref : $lines[$i]->id);
					$taskstatic->label = $lines[$i]->label;
					$taskstatic->date_start = $lines[$i]->date_start;
					$taskstatic->date_end = $lines[$i]->date_end;

					$thirdpartystatic->id = $lines[$i]->thirdparty_id;
					$thirdpartystatic->name = $lines[$i]->thirdparty_name;
					$thirdpartystatic->email = $lines[$i]->thirdparty_email;

					if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id)) {
						$addcolspan = 0;
						if (!empty($arrayfields['t.planned_workload']['checked'])) {
							$addcolspan++;
						}
						if (!empty($arrayfields['t.progress']['checked'])) {
							$addcolspan++;
						}
						if (!empty($arrayfields['timeconsumed']['checked'])) {
							$addcolspan += 2;
						}
						foreach ($arrayfields as $key => $val) {
							if ($val['checked'] && substr($key, 0, 5) == 'efpt.') {
								$addcolspan++;
							}
						}

						// Affichage de la ligne avec le projet
						print '<tr class="oddeven trforbreak nostrong project">'."\n";
						print '<td colspan="'.(2 + $addcolspan + $nb_jour + 2).'">';

						print '<div style="position: sticky; width: fit-content; left: 8px;">';
						print $projectstatic->getNomUrl(1, '', 0, '<strong>'.$langs->transnoentitiesnoconv("YourRole").':</strong> '.$projectsrole[$lines[$i]->fk_project], ' - ', 0, -1, 'valignmiddle');
						$projectstatic->fetch($projectstatic->id);
						if ($projectstatic->array_options['options_tiers_secondaire'] > 0) {
							$thirdpartystatic->fetch($projectstatic->array_options['options_tiers_secondaire']);
							print ' - '.$thirdpartystatic->getNomUrl(1);
						}
						if ($projectstatic->title) {
							print ' - ';
							print '<span class="secondary valignmiddle">'.$projectstatic->title.'</span>';
						}
						print '</div>';
						print '</td>';
						//print '<td colspan="'.($nb_jour + 2).'"></td>';
						print '</tr>';
					}

					if ($oldprojectforbreak != -1) {
						$oldprojectforbreak = $projectstatic->id;
					}

					$disabledproject = 1;
					$disabledtask = 1;

					// If at least one role for project
					if ($lines[$i]->public || !empty($projectsrole[$lines[$i]->fk_project]) || $user->rights->projet->all->creer) {
						$disabledproject = 0;
						$disabledtask = 0;
					}
					// If $restricteditformytask is on and I have no role on task, i disable edit
					if ($restricteditformytask && !str_contains($tasksrole[$lines[$i]->id], 'TASKCONTRIBUTOR')) {
						$disabledtask = 1;
					}

					// Affichage de la ligne avec la tache 
					print '<tr class="oddeven task'.(in_array($taskstatic->id, $favoris) ? ' favoris' : '').'" data-taskid="'.$lines[$i]->id.'">'."\n";

					// Ref
					print '<td class="fixed" colspan="2">';
					print '<!-- Task id = '.$lines[$i]->id.' -->';
					for ($k = 0; $k < $level; $k++) {
						print '<div class="marginleftonly">';
					}
					print $taskstatic->getNomUrl(1, 'withproject', 'task');
					// Label task
					print '<span class="opacitymedium"> '.$taskstatic->label.'</span>';
					if($favoris != -1) {
						print '<a href="?action='.(in_array($taskstatic->id, $favoris) ? 'removeFavoris' : 'addFavoris').'&taskid='.$taskstatic->id.$param.'"><button type="button" title=" Ajouter / Supprimer favoris" onmouseover="mouseOverFav(this)" onmouseout="mouseOutFav(this)" onclick="clickFav(this)" name="Favoris" class="nobordertransp button_search_x'.(in_array($taskstatic->id, $favoris) ? ' clicked' : '').'"><span class="'.(in_array($taskstatic->id, $favoris) ? 'fas' : 'far').' fa-star" style="font-size: initial; color: var(--colorbackhmenu1);"></span></button></a>';;
					}

					$has_heure_nuit = (empty($otherTime['heure_nuit'][$lines[$i]->id]) ? 0 : 1);
					$has_port_epi = (empty($otherTime['port_epi'][$lines[$i]->id]) ? 0 : 1);

					// Colonne avec les cases à cocher
					print '<div id="div_otherhour">';
					print '<input type="checkbox" '.($has_heure_nuit ? 'checked ' : '').'id="heure_nuit_chkb_'.$lines[$i]->id.'" name="heure_nuit_chkb"'.($disabledtask || !$modify || ($conf->global->FDT_ANTICIPE_BLOCKED && ($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month || $dayinloopfromfirstdaytoshow_array[$idw] > $last_day_month)) ? ' disabled' : '').' onchange="CheckboxHeureChange(this, '.$lines[$i]->id.', '.$nb_jour.', '.$inc.', '.$num_first_day.')"><label for="heure_nuit_chkb_'.$lines[$i]->id.'"> dont Heures de nuit (21h/6h)</label></span>';
					print '<input type="checkbox" '.($has_port_epi ? 'checked ' : '').'id="port_epi_chkb_'.$lines[$i]->id.'" name="port_epi_chkb"'.($disabledtask || !$modify || ($conf->global->FDT_ANTICIPE_BLOCKED && ($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month || $dayinloopfromfirstdaytoshow_array[$idw] > $last_day_month)) ? ' disabled' : '').' onchange="CheckboxHeureChange(this, '.$lines[$i]->id.', '.$nb_jour.', '.$inc.', '.$num_first_day.')"><label for="port_epi_chkb_'.$lines[$i]->id.'"> dont Port EPI respiratoire</label></span>';
					print '</div></td>';

					for ($k = 0; $k < $level; $k++) {
						print "</div>";
					}

					// TASK extrafields
					$extrafieldsobjectkey = 'projet_task';
					$extrafieldsobjectprefix = 'efpt.';
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

					// Planned Workload
					if (!empty($arrayfields['t.planned_workload']['checked'])) {
						print '<td class="leftborder plannedworkload right">';
						if ($lines[$i]->planned_workload) {
							print convertSecondToTime($lines[$i]->planned_workload, 'allhourmin');
						} else {
							print '--:--';
						}
						print '</td>';
					}

					if (!empty($arrayfields['t.progress']['checked'])) {
						// Progress declared %
						print '<td class="right">';
						print $formother->select_percent($lines[$i]->progress, $lines[$i]->id.'progress');
						print '</td>';
					}

					if (!empty($arrayfields['timeconsumed']['checked'])) {
						// Time spent by everybody
						print '<td class="right">';
						// $lines[$i]->duration is a denormalised field = summ of time spent by everybody for task. What we need is time consummed by user
						if ($lines[$i]->duration) {
							print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.'">';
							print convertSecondToTime($lines[$i]->duration, 'allhourmin');
							print '</a>';
						} else {
							print '--:--';
						}
						print "</td>\n";

						// Time spent by user
						print '<td class="right">';
						$tmptimespent = $taskstatic->getSummaryOfTimeSpent($fuser->id);
						if ($tmptimespent['total_duration']) {
							print convertSecondToTime($tmptimespent['total_duration'], 'allhourmin');
						} else {
							print '--:--';
						}
						print "</td>\n";
					}

					// Fields to show current time
					$tableCell = '';
					$total_work_task = 0;
					$total_heureCompagnonnage = 0;
					$total_heureNuit = 0;
					$total_heureRoute = 0;
					$total_heureEPI = 0;
					$modeinput = ($conf->global->FDT_DECIMAL_HOUR_FORMAT ? 'hours_decimal' : 'hours');
					for ($idw = 0; $idw < $nb_jour; $idw++) { // Gestion des cases de chaque jour
						$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw];

						if($idw > 0 && $idw == $num_first_day){
							print '<td style="border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;"></td>';
						}

						if($disabledtask = 0 || $disabledtask = 2)
						if ((!empty($taskstatic->date_end) && $dayinloopfromfirstdaytoshow > $taskstatic->date_end) || (!empty($taskstatic->date_start) && $dayinloopfromfirstdaytoshow < $taskstatic->date_start)) {
							$disabledtask = 2;
						}
						else $disabledtask = 0;

						//var_dump($projectstatic->weekWorkLoadPerTask);
						$tmparray = dol_getdate($dayinloopfromfirstdaytoshow);
						$dayWorkLoad = $projectstatic->weekWorkLoadPerTask[$dayinloopfromfirstdaytoshow][$lines[$i]->id];
						$totalforvisibletasks[$dayinloopfromfirstdaytoshow] += $dayWorkLoad;

						$alreadyspent = '';
						if ($dayWorkLoad > 0) {
							$alreadyspent = convertSecondToTime($dayWorkLoad, 'allhourmin');
						}
						if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
							$total_work_task += (int)$dayWorkLoad;
						}
						$alttitle = $langs->trans("AddHereTimeSpentForDay", $tmparray['day'], $tmparray['mon']);
						
						// Est-ce que l'utilisateur est en congé sur le jour actuel => Utilisé pour bloquer l'input
						if($isavailable[$dayinloopfromfirstdaytoshow]['morning'] == false && $isavailable[$dayinloopfromfirstdaytoshow]['morning_reason'] == "leave_request" 
								&& $isavailable[$dayinloopfromfirstdaytoshow]['afternoon'] == false && $isavailable[$dayinloopfromfirstdaytoshow]['afternoon_reason'] == "leave_request" && str_contains($css[$dayinloopfromfirstdaytoshow], 'onholidayallday')){
							$user_conges = 1;
						}
						else{
							$user_conges = 0;
						}

						// Est-ce qu'on désactive l'input ou non ?
						$disabled = 0;
						if(!$modify || $disabledtask || ($user_conges && !$modifier_jour_conges && empty($alreadyspent)) || ($conf->global->FDT_ANTICIPE_BLOCKED && ($dayinloopfromfirstdaytoshow < $first_day_month || $dayinloopfromfirstdaytoshow > $last_day_month))) {
							$disabled = 1;
						}

						$tableCell = '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';

						// Note 
						$note = $notes[$lines[$i]->id][$dayinloopfromfirstdaytoshow];
						$tableCell .= img_picto('Note', (empty($note) ? 'note_vide@feuilledetemps' : 'note_plein@feuilledetemps'), ' id="img_note_'.$lines[$i]->id.'_'.$idw.'" style="display:inline-block; padding: 6px; vertical-align: middle;" onClick="openNote(\'note_'.$lines[$i]->id.'_'.$idw.'\')"');
						$tableCell .= '<div class="modal" id="note_'.$lines[$i]->id.'_'.$idw.'">';
						$tableCell .= '<div class = "modal-content">';
						$tableCell .= '<span class="close" onclick="closeNotes(this)">&times;</span>';
						$tableCell .= '<a>'.$langs->trans('Note').' ('.$taskstatic->label.' : '.dol_print_date($dayinloopfromfirstdaytoshow, '%a %d/%m/%y').")".'</a><br><br>';
						$tableCell .= '<textarea class = "flat"  rows = "3"'.($disabled ? ' disabled' : '').' style = "width:350px; top:10px; max-width: 350px; min-width: 350px;"';
						$tableCell .= ' name = "note['.$lines[$i]->id.']['.$idw.']"';
						$tableCell .= '>'.$note.'</textarea>';
						$tableCell .= '</div></div>';

						// Time
						// Gestion des heures et des heures sup pour la 1ère et la dernière semaine
						$ecart_lundi = ecart_lundi($dayinloopfromfirstdaytoshow);
						$weekNumber = date('W', $dayinloopfromfirstdaytoshow);
						if ($idw < 6 && $idw-$ecart_lundi < 0 && dol_print_date($firstdaytoshow, '%a') != 'Lun'){
							$temps = $temps_prec;
						}
						else if ($nb_jour - $idw < 7 && $idw-$ecart_lundi > 23 && dol_print_date($lastdaytoshow, '%a') != 'Dim'){
							$temps = $temps_suiv;
						}
						else $temps = 0;

						if ($idw < 6 && $idw-$ecart_lundi < 0 && dol_print_date($firstdaytoshow, '%a') != 'Lun'){
							$hs_25 = $temps_prec_hs25;
							$hs_50 = $temps_prec_hs50;
							$hn_prec = $temps_prec;
							$hn_suiv = 0;
						}
						else if ($nb_jour - $idw < 7 && $idw-$ecart_lundi > 23 && dol_print_date($lastdaytoshow, '%a') != 'Dim'){
							$hs_25 = $temps_suiv_hs25;
							$hs_50 = $temps_suiv_hs50;
							$hn_suiv = $temps_suiv;
							$hn_prec = 0;
						}
						else {
							$hs_25 = 0;
							$hs_50 = 0;
							$hn_prec = 0;
							$hn_suiv = 0;
						}

						if(dol_print_date($dayinloopfromfirstdaytoshow, '%Y-%m-%d') < '2024-06-03' && $heure_semaine == $conf->global->HEURE_SEMAINE) {
							$tmp_heure_semaine = 35;
						}
						else {
							$tmp_heure_semaine = $heure_semaine;
						}

						if(dol_print_date($dayinloopfromfirstdaytoshow, '%Y-%m-%d') < '2024-06-03' && $heure_semaine_hs == $conf->global->HEURE_SEMAINE) {
							$tmp_heure_semaine_hs = 35;
						}
						else {
							$tmp_heure_semaine_hs = $heure_semaine_hs;
						}

						$tableCell .= '<input type="text" style="border: 1px solid grey;" alt="'.($disabledtask ? '' : $alttitle).'" title="'.($disabledtask ? '' : $alttitle).'" '.($disabled ? 'disabled' : '').' class="center smallpadd time_'.$idw.'" size="2" id="timeadded['.$inc.']['.$idw.']" name="task['.$lines[$i]->id.']['.$idw.']" value="'.$alreadyspent.'" cols="2"  maxlength="5"';
						$tableCell .= ' onfocus="this.oldvalue = this.value; this.oldvalue_focus = this.value;"';
						$tableCell .= ' onkeypress="return regexEvent_TS(this,event,\'timeChar\')"';
						$tableCell .= ' onkeyup="updateTotal_TS(this, '.$idw.',\''.$modeinput.'\','.$inc.', '.$num_first_day.'); this.oldvalue = this.value; updateTotalWeek(\''.$modeinput.'\', '.$hn_prec.', '.$hn_suiv.', \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');"';
						$tableCell .= ' onblur="regexEvent_TS(this,event,\''.$modeinput.'\'); validateTime(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.','.$temps.',\''.$type_deplacement.'\', '.$tmp_heure_semaine_hs.', '.($fuser->id == $user->id).', '.$heure_max_jour.', '.$heure_max_semaine.'); updateTotal_TS(this, '.$idw.',\''.$modeinput.'\','.$inc.', '.$num_first_day.'); updateTotalWeek(\''.$modeinput.'\', '.$hn_prec.', '.$hn_suiv.', \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.'); validateTime_HS(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.','.$inc.','.$temps.','.$hs_25.','.$hs_50.', '.$tmp_heure_semaine_hs.');" />';

						// On récupère le total de la semaine pour savoir s'il faut afficher ou non les cases des heures sup
						if($dayinloopfromfirstdaytoshow == $firstdaytoshow || dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Lun'){
							$temps_cons_semaine = $timeSpentWeek[date('W', $dayinloopfromfirstdaytoshow)];
						}

						// S'il y a + de 35h sur la semaine, on affiche les cases des heures sup
						if($temps_cons_semaine > $tmp_heure_semaine_hs && dol_print_date($dayinloopfromfirstdaytoshow, '%a') != 'Dim' && !$disabledtask && $alreadyspent){
							$heure_sup = new Projet_task_time_heure_sup($db);

							$hs_25_alreadyspent = '';
							$hs_50_alreadyspent = '';
							$hs_Load = $heure_sup->getHeureSup($dayinloopfromfirstdaytoshow, $lines[$i]->id, $fuser);

							if($hs_Load['25'] > 0){
								$hs_25_alreadyspent = convertSecondToTime($hs_Load['25'], 'allhourmin');
								$total_hs25 += $hs_Load['25'];
							}
							if($hs_Load['50'] > 0){
								$hs_50_alreadyspent = convertSecondToTime($hs_Load['50'], 'allhourmin');
								$total_hs50 += $hs_Load['50'];
							}

							// Cases Heure sup 
							$tableCell .= '<br><input type="text"'.($disabled ? ' disabled' : '').' alt="Ajoutez ici les heures sup entre '.$tmp_heure_semaine_hs.' et '.$conf->global->HEURE_SUP1.'h" title="Ajoutez ici les heures sup entre '.$tmp_heure_semaine_hs.' et '.$conf->global->HEURE_SUP1.'h" 
											name="hs25_task['.$lines[$i]->id.']['.$idw.']" class="center smallpadd hs25 time_hs_'.$idw.' time_hs_'.$inc.'_'.$idw.'" size="2" 
											id="timeaddedhs['.$inc.']['.$idw.']" value="'.$hs_25_alreadyspent.'" cols="2"  maxlength="5" 
											onkeypress="return regexEvent_TS(this,event,\'timeChar\')"
											onblur="regexEvent_TS(this,event,\'hours\'); validateTime_HS(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.','.$inc.','.$temps.','.$hs_25.','.$hs_50.', '.$tmp_heure_semaine_hs.');" />';
							$tableCell .= '<input type="text"'.($temps_cons_semaine <= $conf->global->HEURE_SUP1 || $disabled ? ' disabled' : '').' alt="Ajoutez ici les heures sup entre '.$conf->global->HEURE_SUP1.' et 48h"
											title="Ajoutez ici les heures sup entre '.$conf->global->HEURE_SUP1.' et 48h" name="hs50_task['.$lines[$i]->id.']['.$idw.']" 
											class="center smallpadd hs50 time_hs_'.$idw.' time_hs_'.$inc.'_'.$idw.'" size="2" id="timeaddedhs['.$inc.']['.$idw.']" 
											value="'.$hs_50_alreadyspent.'" cols="2"  maxlength="5" onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
											onblur="regexEvent_TS(this,event,\'hours\'); validateTime_HS(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.','.$inc.','.$temps.','.$hs_25.','.$hs_50.', '.$tmp_heure_semaine_hs.');" />';

						}


						// Cases des heures de nuit
						if($has_heure_nuit) {
							$heure_nuit_nf = $otherTime['heure_nuit'][$lines[$i]->id][$dayinloopfromfirstdaytoshow];

							$heure_nuit = '';
							if ($heure_nuit_nf > 0) {
								$heure_nuit = $heure_nuit_nf / 3600;
							}

							if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
								$total_heureNuit += $heure_nuit_nf;
							}

							$tableCell .= '<div id="time_heure_nuit_'.$lines[$i]->id.'_'.$idw.'" style="display: inline;"><br><input type="text"'.($disabled ? ' disabled' : '').' 
										alt="Ajoutez ici les heures de nuit" title="Ajoutez ici les heures de nuit" name="heure_nuit['.$lines[$i]->id."][".$idw.']" 
										class="center smallpadd heure_nuit time_heure_nuit_'.$lines[$i]->id.'_'.$idw.'" size="2" id="time_heure_nuit['.$lines[$i]->id.']['.$idw.']" 
										value="'.$heure_nuit.'" cols="2"  maxlength="5" 
										onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
										onblur="validateTime_HeureNuit(this, '.$inc.', '.$idw.'); updateTotal_OtherHours('.$nb_jour.', '.$inc.', '.$num_first_day.', '.$lines[$i]->id.')"/></div>';
						}

						// Cases des ports d'epi
						if($has_port_epi) {
							$port_epi_nf = $otherTime['port_epi'][$lines[$i]->id][$dayinloopfromfirstdaytoshow];

							$port_epi = '';
							if ($port_epi_nf > 0) {
								$port_epi = $port_epi_nf / 3600;
							}

							if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
								$total_heureEPI += $port_epi_nf;
							}

							$tableCell .= '<div id="time_epi_'.$lines[$i]->id.'_'.$idw.'" style="display: inline;"><br><input type="text"'.($disabled ? ' disabled' : '').' 
							alt="Ajoutez ici les EPI respiratoire" title="Ajoutez ici les EPI respiratoire" name="epi['.$lines[$i]->id."][".$idw.']" 
							class="center smallpadd heure_epi time_epi_'.$lines[$i]->id.'_'.$idw.'" size="2" id="time_epi['.$lines[$i]->id.']['.$idw.']" value="'.$port_epi.'" cols="2"  maxlength="5" 
							onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
							onblur="validateTime_EPI(this, '.$inc.', '.$idw.'); updateTotal_OtherHours('.$nb_jour.', '.$inc.', '.$num_first_day.', '.$lines[$i]->id.')"/></div>';
						}

						$tableCell .= '</td>';
						print $tableCell;

						$u++;
					}

					print '<td class="liste_total_task fixed">';
					print '<span id="total_task['.$inc.']">'.(convertSecondToTime($total_work_task, 'allhourmin') != '0' ? convertSecondToTime($total_work_task, 'allhourmin') : '00:00').'</span>';
					if($has_heure_nuit) {
						print '<br><span class="total_heureNuit txt_heure_nuit" id="total_heureNuit['.$inc.']">'.(convertSecondToTime($total_heureNuit, 'allhourmin') != '0' ? convertSecondToTime($total_heureNuit, 'allhourmin') : '00:00').'</span>';
					}
					if($has_port_epi) {
						print '<br><span class="total_heureEPI txt_heure_epi" id="total_heureEPI['.$inc.']">'.(convertSecondToTime($total_heureEPI, 'allhourmin') != '0' ? convertSecondToTime($total_heureEPI, 'allhourmin') : '00:00').'</span>';
					}
					print '</td>';

					// Warning
					if ((!$lines[$i]->public) && $disabledproject) {
						print '<td class="right">';
						print $form->textwithpicto('', $langs->trans("UserIsNotContactOfProject"));
						print '</td>';
					} elseif ($disabledtask == 1) {
						$titleassigntask = $langs->trans("AssignTaskToMe");
						if ($fuser->id != $user->id) {
							$titleassigntask = $langs->trans("AssignTaskToUser", '...');
						}

						print '<td class="right">';
						print $form->textwithpicto('', $langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
						print '</td>';
					}

					print "</tr>\n";

					$inc++;
				}
			}

			// Call to show task with a lower level (task under the current task)
			//$inc++;
			$level++;
			$nb_appel++;
			if ($lines[$i]->id > 0) {				
				$ret = FeuilleDeTempsLinesPerWeek($mode, $inc, $firstdaytoshow, $lastdaytoshow, $fuser, $lines[$i]->id, ($parent == 0 ? $lineswithoutlevel0 : $lines), $level, $projectsrole, $tasksrole, $mine, $restricteditformytask, $isavailable, $oldprojectforbreak, $arrayfields, $extrafields, 
				$modify, $css, $css_holiday, $num_first_day, $num_last_day, $type_deplacement, $dayinloopfromfirstdaytoshow_array, 
				$modifier_jour_conges, $temps_prec, $temps_suiv, 
				$temps_prec_hs25, $temps_suiv_hs25, $temps_prec_hs50, $temps_suiv_hs50, $notes, $otherTime, $timeSpentMonth, $timeSpentWeek, 
				$timeHoliday, $heure_semaine, $heure_semaine_hs, $favoris, $param,
				$totalforeachday, $holiday_without_canceled, $multiple_holiday, $heure_max_jour, $heure_max_semaine, $arraytypeleaves, $appel_actif, $nb_appel);
				foreach ($ret as $key => $val) {
					$totalforvisibletasks[$key] += $val;
				}
			}
			$level--;
		} 
	}

	$appel_actif--;
	if ($appel_actif === 0) {
        // Is there a diff between selected/filtered tasks and all tasks ?
		$isdiff = 0;
		if (count($totalforeachday)) {
			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];
				$timeonothertasks = ($totalforeachday[$tmpday] - $totalforvisibletasks[$tmpday]);
				if ($timeonothertasks) {
					$isdiff = 1;
					break;
				}
			}
		}

		// There is a diff between total shown on screen and total spent by user, so we add a line with all other cumulated time of user
		if ($isdiff) {
			print '<tr class="oddeven othertaskwithtime favoris">';
			print '<td class="nowrap fixed" colspan="'.(2 + $addcolspan).'">'.$langs->trans("OtherFilteredTasks").'</td>';

			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

				if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
					print '<td></td>';
				}

				print '<td class="center hide'.$idw.' '.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
				$timeonothertasks = ($totalforeachday[$dayinloopfromfirstdaytoshow] - $totalforvisibletasks[$dayinloopfromfirstdaytoshow]);
				if ($timeonothertasks) {
					print '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center smallpadd time_'.$idw.'" size="2" disabled id="timespent[-1]['.$idw.']" name="task[-1]['.$idw.']" value="';
					print convertSecondToTime($timeonothertasks, 'allhourmin');
					print '"></span>';
				}
				print '</td>';
			}

			print ' <td class="liste_total fixed"></td>';
			print '</tr>';
		}

		// Affichage du total
		if ($conf->use_javascript_ajax) {
			print '<tr class="trforbreak favoris">';
			print '<td class="fixed" colspan="'.(2 + $addcolspan).'">';
			print $langs->trans("Total");
			print '<span class="opacitymediumbycolor">  - '.$langs->trans("ExpectedWorkedHours").': <strong>'.price($fuser->weeklyhours, 1, $langs, 0, 0).'</strong></span>';
			print '</td>';

			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
				
				if($idw > 0 && dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y') == dol_print_date($first_day_month, '%d/%m/%Y')){
					print '<td style="border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;"></td>';
				}

				$total = (convertSecondToTime($totalforeachday[$dayinloopfromfirstdaytoshow], 'allhourmin') != '0' ? convertSecondToTime($totalforeachday[$dayinloopfromfirstdaytoshow], 'allhourmin') : '00:00');
				print '<td class="liste_total hide'.$idw.($total != '00:00' ? ' bold' : '').'" align="center"><div class="totalDay'.$idw.'" '.(!empty($style) ? $style : '').'>'.$total.'</div></td>';
			}
			print '<td class="liste_total center fixed"><div class="totalDayAll">&nbsp;</div></td>';
			print '</tr>';
		}

		if($mode == 'card' && $displayVerification) {
			FeuilleDeTempsVerification($firstdaytoshow, $lastdaytoshow, $nb_jour, $fuser, $css, $css_holiday, $num_first_day, !$modify, $dayinloopfromfirstdaytoshow_array);
		}
		else {
			FeuilleDeTempsDeplacement($firstdaytoshow, $lastdaytoshow, $nb_jour, $fuser, $css, $num_first_day, $num_last_day, !$modify, $addcolspan, $dayinloopfromfirstdaytoshow_array);
		}

		print "</table>";
		print '</div>';
	}

	return $totalforvisibletasks;
}

/**
 * Output a task line into a perday intput mode
 *
 * @param	string	   	$mode								Mode d'affichage
 * @param	string	   	$inc								Line output identificator (start to 0, then increased by recursive call)
 * @param	int			$firstdaytoshow						First day to show
 * @param	int			$lastdaytoshow						Last day to show
 * @param	User|null	$fuser								Restrict list to user if defined
 * @param   string		$parent								Id of parent task to show (0 to show all)
 * @param   Task[]		$lines								Array of lines (list of tasks but we will show only if we have a specific role on task)
 * @param   int			$level								Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole						Array of roles user has on project
 * @param   string		$tasksrole							Array of roles user has on task
 * @param	string		$mine								Show only task lines I am assigned to
 * @param   int			$restricteditformytask				0=No restriction, 1=Enable add time only if task is assigned to me, 2=Enable add time only if tasks is assigned to me and hide others
 * @param   array       $isavailable						Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak					Old project id of last project break
 * @param	array		$arrayfields		   				Array of additional column
 * @param	Extrafields	$extrafields		    			Object extrafields
 * @param 	int			$modify								1 si les cases sont modifiables, 0 sinon 
 * @param   string[]    $css								css pour la couleur des cases
 * @param   string[]    $css_holiday						css pour la couleur des cases
 * @param   int 	 	$num_first_day						Numero du 1er jour du mois (0 si celui-ci n'est pas présent dans les dates affichées)
 * @param   int 	 	$num_last_day						Numero du dernier jour du mois (0 si celui-ci n'est pas différent du dernier jour affiché)
 * @param   string 	 	$type_deplacement					Type de déplacement de l'utilisateur : 'none', 'petitDeplacement' ou 'grandDeplacement'
 * @param   int[] 	 	$dayinloopfromfirstdaytoshow_array	Tableau avec l'ensemble des jours a afficher (amélioration de la performance)
 * @param   int 	 	$modifier_jour_conges				Droit de modifier les jours de congés
 * @param   double 	 	$temps_prec							Temps précédent de la semaine coupée de début de pointage 
 * @param   double 	 	$temps_suiv							Temps suivant de la semaine coupée de fin de pointage 
 * @param   double 	 	$temps_prec_hs25					Temps des HS à 25% précédent de la semaine coupée de début de pointage 
 * @param   double 	 	$temps_suiv_hs25					Temps des HS à 25% suivant de la semaine coupée de fin de pointage 
 * @param   double 	 	$temps_prec_hs50					Temps des HS à 50% précédent de la semaine coupée de début de pointage 
 * @param   double 	 	$temps_suiv_hs50					Temps des HS à 50% suivant de la semaine coupée de fin de pointage 
 * @param   string[] 	$notes								Tableau des notes de la feuille de temps
 * @param   double[] 	$otherTaskTime							Autres temps : port EPI / Heure de nuit
 * @param   double[] 	$timeSpentMonth						Temps consommés du mois
 * @param   double[] 	$timeSpentWeek						Temps consommés pour chaque semaine du mois
 * @param   double[] 	$timeHoliday						Temps des congés
 * @param   double 	 	$heure_semaine						Heures travaillées par l'utilisateur dans la semaine
 * @param   double 	 	$heure_semaine_hs					Heures travaillées à partir desquelles l'utilisateur à droit à des HS dans la semaine
 * @param   int[] 	 	$favoris							Tâches ajoutées en favoris
 * @param   string 	 	$param								Paramètres pour les liens
 * @param   double[] 	$totalforeachday					Total des heures par jour pour la différence avec les tâches affichées
 * @param   array	 	$holiday_without_canceled			Tableau avec les congés non annulés de l'utilisateur
 * @param   int	 		$multiple_holiday					Est-ce qu'il faut 2 lignes pour les congés ?
 * @return  array				Array with time spent for $fuser for each day of week on tasks in $lines and substasks
 */
function FeuilleDeTempsLinesPerWeek_Sigedi($mode, &$inc, $firstdaytoshow, $lastdaytoshow, $fuser, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, &$isavailable, $oldprojectforbreak = 0, $arrayfields = array(), $extrafields = null, 
									$modify = 1, $css = '', $css_holiday, $num_first_day = 0, $num_last_day = 0, $type_deplacement = 'none', $dayinloopfromfirstdaytoshow_array, $modifier_jour_conges,  
									$temps_prec, $temps_suiv, $temps_prec_hs25, $temps_suiv_hs25, $temps_prec_hs50, $temps_suiv_hs50, 
									$notes, $otherTaskTime, $timeSpentMonth, $timeSpentWeek, $timeHoliday, $heure_semaine, $heure_semaine_hs, 
									$favoris = -1, $param = '', $totalforeachday, $holiday_without_canceled, $multiple_holiday, $heure_max_jour, $heure_max_semaine, $standard_week_hour, $arraytypeleaves){
	global $conf, $db, $user, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic, $object, $displayVerification, $objectoffield;
	global $first_day_month, $last_day_month;
	
	$holiday = new extendedHoliday($db);
	$silae = new Silae($db);
	$task = new Task($db);

	if(empty($conf->global->HOLIDAY_HIDE_BALANCE)) $conges_texte = $holiday->getArrayHoliday($fuser->id, 0, 1);
	$total_array = array();
	$cptholiday = 0;

	$fields = array(
		'date' => array('text' => 'Date', 'visible' => 1, 'css' => 'fixed minwidth80'),
		'total_semaine' => array('text' => 'Semaine', 'type' => 'duration', 'visible' => 1, 'css' => 'fixedcolumn2 minwidth100'),
		'absence' => array('text' => 'Absence'.$form->textwithpicto('', $conges_texte), 'visible' => 1, 'css' => 'fixedcolumn3 minwidth100'),
		'contrat' => array('text' => 'Contrat', 'type' => 'duration', 'visible' => 1, 'css' => 'fixedcolumn4 minwidth55'),
		'heure_jour' => array('text' => 'Heures Jour', 'type' => 'duration', 'visible' => 1, 'css' => 'fixedcolumn5 minwidth100'),
		'heure_nuit' => array('text' => 'Heures Nuit', 'type' => 'duration', 'visible' => 1, 'css' => 'fixedcolumn6 minwidth60'),
		'heure_total' => array('text' => 'Total', 'type' => 'duration', 'visible' => 1, 'css' => 'fixedcolumn7 minwidth40'),
		'diff' => array('text' => 'Diff.', 'visible' => 1, 'css' => 'fixedcolumn8 minwidth40'),
		'site' => array('text' => 'SiteFDT', 'visible' => 1, 'css' => 'fixedcolumn9'),
		'affaire' => array('text' => 'Affaire', 'visible' => 1, 'css' => 'fixedcolumn10 affairecolumn'),
	);

	$total_array = array(
		'heure_jour' => 0,
		'heure_nuit' => 0,
		'heure_total' => 0,
	);

	if($mode == 'card') {
		$fields['date']['text'] = '<button type="button" title="Plein écran" id="fullScreen" name="fullScreen" class="nobordertransp button_search_x"><span class="fa fa-expand" style="font-size: 1.7em;"></span></button>'.$fields['date']['text'];
	}

	if($user->rights->holidaycustom->write || $user->rights->holidaycustom->writeall) {
		$fields['absence']['text'] .= ' <a target="_blank" href="'.DOL_URL_ROOT.'/custom/holidaycustom/card.php?action=create&fuserid='.$fuser->id.'"><span title="'.$langs->trans("NewCP").'" class="fa fa-plus-circle"></span></a>';
	}
	if($mode == 'card' && $displayVerification && $conf->global->FDT_STATUT_HOLIDAY && !$conf->global->FDT_STATUT_HOLIDAY_VALIDATE_VERIF) {
		$fields['absence']['text'] .= '<input type="checkbox"'.(!$modify ? 'disabled' : '').' id="selectAllHoliday" onclick="toggleCheckboxesHoliday(this)"> ';
	}
	if($multiple_holiday) {
		$fields['absence']['colspan'] = 2;
	}

	foreach($silae->fields as $key => $value) {
		if(in_array($key, array('heure_sup00', 'heure_sup25', 'heure_sup50', 'heure_sup50ht'))) {
			if(!$conf->global->HEURE_SUP_SUPERIOR_HEURE_MAX_SEMAINE && $key == 'heure_sup50ht') {
				continue;
			}
			$fields[$key] = array('text' => $value['label'],  'type' => 'duration', 'visible' => ($user->hasRight('feuilledetemps','feuilledetemps','modify_verification') && $object->status != 0 && $object->status != 2 && $object->status != 3));
			$total_array[$key] = 0;
		}
	}

	// Fetch optionals attributes and labels
	if (empty($extrafields->attributes[$silae->table_element]['loaded'])) {
		$extrafields->fetch_name_optionals_label($silae->table_element);
	}
	foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
		$fields[$key] = array(
						'text' => $label, 
						'type' => $extrafields->attributes[$silae->table_element]['type'][$key],
						'visible' => dol_eval($extrafields->attributes[$silae->table_element]['list'][$key], 1, 1, '2'),
						'css' => $extrafields->attributes[$silae->table_element]['cssview'][$key]
					);	

		if($extrafields->attributes[$silae->table_element]['type'][$key] != 'text') $total_array[$key] = 0;	
	}

	$totalforvisibletasks = array();
	$nb_jour = sizeof($dayinloopfromfirstdaytoshow_array);
	$task_load = array();

	print '<div class="div-table-responsive" style="min-height: 0px">';
	print '<table class="tagtable liste listwithfilterbefore column" id="tablelines_fdt">'."\n";			

	printHeaderLine_Sigedi($fields);

	$task = new extendedTask($db);
	$filter = ' AND ptt.element_date >= "'.substr($db->idate($firstdaytoshow), 0, 10).'" AND ptt.element_date <= "'.substr($db->idate($lastdaytoshow), 0, 10).'"';
	$timespent_month = $task->fetchAllTimeSpent($fuser, $filter);
	$timeHolidayByDay = array();
	$silae_array = $silae->fetchAllSilaeWithoutId($firstdaytoshow, $lastdaytoshow, $fuser->id);

	print '<tbody>';
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$modify_day = (!$modify || ($conf->global->FDT_ANTICIPE_BLOCKED && ($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month || $dayinloopfromfirstdaytoshow_array[$idw] > $last_day_month)) ? 0 : 1);
		$morecss = (dol_print_date($dayinloopfromfirstdaytoshow_array[$idw], '%a') == 'Dim' ? 'sunday' : '');

		// Si c'est un jour anticipé, on ne met pas à jour le total
		if($dayinloopfromfirstdaytoshow_array[$idw] < $first_day_month || $dayinloopfromfirstdaytoshow_array[$idw] > $last_day_month) {
			printLine_Sigedi($mode, $idw, $fuser, $dayinloopfromfirstdaytoshow_array, $nb_jour, $lastdaytoshow, $modify_day, $modifier_jour_conges,
			$holiday_without_canceled, $firstdaytoshow, $css, $morecss, $css_holiday, $multiple_holiday, $isavailable, $notes, $heure_semaine, $heure_semaine_hs, 
			$num_first_day, $num_last_day, $timeHoliday, $timeHolidayByDay, $timeSpentWeek, $type_deplacement, $otherTaskTime, $timespent_month, $totalforeachday, 
			$heure_max_jour, $heure_max_semaine, $standard_week_hour, $total_array, $cptholiday, $arraytypeleaves, $task_load, $silae_array);
		}
		else {
			$total_array = printLine_Sigedi($mode, $idw, $fuser, $dayinloopfromfirstdaytoshow_array, $nb_jour, $lastdaytoshow, $modify_day, $modifier_jour_conges,
			$holiday_without_canceled, $firstdaytoshow, $css, $morecss, $css_holiday, $multiple_holiday, $isavailable, $notes, $heure_semaine, $heure_semaine_hs, 
			$num_first_day, $num_last_day, $timeHoliday, $timeHolidayByDay, $timeSpentWeek, $type_deplacement, $otherTaskTime, $timespent_month, $totalforeachday, 
			$heure_max_jour, $heure_max_semaine, $standard_week_hour, $total_array, $cptholiday, $arraytypeleaves, $task_load, $silae_array);
		}
	}

	printTotalLine_Sigedi($fields, $total_array);

	print '</tbody>';
	print '</table>';
	print '</div>';
	// var_dump($total_array);

	return $totalforvisibletasks;
}

function printHeaderLine_Sigedi($fields) {
	global $langs;

	print '<thead>';
	print '<tr class="liste_titre">';
	foreach($fields as $key => $value) {
		if (abs($value['visible']) != 1) {
			continue;
		}

		print '<th class="bold columntitle'.($value['css'] ? ' '.$value['css'] : '').'" align="center" colspan="'.($value['colspan'] ? $value['colspan'] : 1).'">'.$langs->trans($value['text']).'</th>';
	}
	print '</tr>';
	print '</thead>';
}

function printTotalLine_Sigedi($fields, $total_array) {
	global $langs, $form;

	print '<tr class="liste_totalcolumn">';
	foreach($fields as $key => $value) {
		if (abs($value['visible']) != 1) {
			continue;
		}

		if($key == 'date') {
			$total = 'TOTAL'.$form->textwithpicto('', 'Le total prend en compte uniquement les jours du mois en cours');
		}
		elseif(!is_null($total_array[$key])) {
			$total = ($value['type'] != 'boolean' ? formatValueForAgenda($value['type'], $total_array[$key]) : $total_array[$key]);
		}
		else {
			$total = '';
		}

		print '<th class="bold columntitle'.($value['css'] ? ' '.$value['css'] : '').'" name="total['.$key.']" id="total_'.$key.'" align="center" colspan="'.($value['colspan'] ? $value['colspan'] : 1).'">'.$total.'</th>';
	}
	print '</tr>';
}

function printLine_Sigedi($mode, $idw, $fuser, $dayinloopfromfirstdaytoshow_array, $nb_jour, $lastdaytoshow, $modify, $modifier_jour_conges, 
						 $holiday_without_canceled, $firstdaytoshow, $css, $morecss, $css_holiday, $multiple_holiday, $isavailable, $notes, $heure_semaine, $heure_semaine_hs,
						 $num_first_day, $num_last_day, $timeHoliday, &$timeHolidayByDay, $timeSpentWeek, $type_deplacement, $otherTaskTime, $timespent_month, $totalforeachday, 
						 $heure_max_jour, $heure_max_semaine, $standard_week_hour, $total_array, &$cptholiday, $arraytypeleaves, &$task_load, $silae_array) {
	global $db, $form, $formother, $conf, $langs, $user, $extrafields, $object, $objectoffield, $action;
	global $displayVerification;

	$projet_task_time_other = new Projet_task_time_other($db);
	$holiday = new extendedHoliday($db);
	$silae = new Silae($db);

	$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
	$dayinloopfromfirstdaytoshowgmt = dol_mktime(0, 0, 0, dol_print_date($dayinloopfromfirstdaytoshow, '%m'), dol_print_date($dayinloopfromfirstdaytoshow, '%d'), dol_print_date($dayinloopfromfirstdaytoshow, '%Y'), 'gmt');
	$ecart_lundi = ecart_lundi($dayinloopfromfirstdaytoshow);
	$weekNumber = date("W", $dayinloopfromfirstdaytoshow);
	$modeinput = ($conf->global->FDT_DECIMAL_HOUR_FORMAT ? 'hours_decimal' : 'hours');
	$timespent_number = (sizeof($timespent_month[$dayinloopfromfirstdaytoshow]) > $conf->global->FDT_COLUMN_MAX_TASK_DAY ? $conf->global->FDT_COLUMN_MAX_TASK_DAY : sizeof($timespent_month[$dayinloopfromfirstdaytoshow]));
	$timespent_number = ($timespent_number == 0 ? 1 : $timespent_number);

	print '<tr>';
		// Date
		print '<th align="center" class="bold fixed hide'.$idw.' day daycolumn '.$morecss.'">';
		print dol_print_date($dayinloopfromfirstdaytoshow, '%a');
		print '<br>'.dol_print_date($dayinloopfromfirstdaytoshow, 'dayreduceformat').'</th>';



		// Total semaine
		if (dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Lun' || $idw == 0) {
			if ($idw == 0) {
				$taille = 7-$ecart_lundi;
			}
			elseif (dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Lun' && $nb_jour - $idw < 7 && $idw-$ecart_lundi > 23 && dol_print_date($lastdaytoshow, '%a') != 'Dim'){
				$taille = $nb_jour - $idw;
			}
			elseif (dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Lun' && $idw != 0) {	
				$taille = 7;
				// $date = dol_time_plus_duree($tmpday, 7, 'd');

				// if($first_day_month == $tmpday){
				// 	print '<th style="min-width: 90px; border-left: 1px solid var(--colortopbordertitle1); border-bottom: none; border-top: none !important; z-index:1;" width="9%"></th>';
				// }
				// elseif($first_day_month > $tmpday && $first_day_month < $date){
				// 	$taille++;
				// 	$idw--;
				// }
			}

			$premier_jour = $idw;
			$dernier_jour = $idw + $taille - 1;

			print '<td class="totalweekcolumn fixedcolumn2 liste_total_semaine_'.$weekNumber.(dol_print_date(dol_time_plus_duree($dayinloopfromfirstdaytoshow, $taille - 1, 'd'), '%a') == 'Dim'? ' withsunday' : '').'" align="center" rowspan='.$taille.'><strong>Semaine '.$weekNumber.' : <span class="totalSemaine" name="totalSemaine'.$weekNumber.'" id="totalSemaine'.$weekNumber.'_'.$premier_jour.'_'.$dernier_jour.'">&nbsp</span></strong></td>';
		}



		// Congés
		if(!empty($holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][0]) && $holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][0] != $holiday_without_canceled[dol_time_plus_duree($dayinloopfromfirstdaytoshow, -1, 'd')]['rowid'][0]) {
			$holiday->fetch((int)$holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][0]);
			$numberDay = (num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) ? num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) : 1);
			if($idw + $numberDay > $nb_jour) $numberDay = $nb_jour - $idw;

			$durationHoliday = $holiday->getHourDuration($standard_week_hour, $dayinloopfromfirstdaytoshow, $fuser, $numberDay, $timeHolidayByDay);

			if($mode == 'card' && $conf->global->FDT_STATUT_HOLIDAY) {
				print '<td class="center fixedcolumn3 '.$morecss.' '.($multiple_holiday ? 'holidaycolumnmultiple1' : 'holidaycolumn').' hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? $css_holiday[$dayinloopfromfirstdaytoshow][0] : '').' statut'.$holiday->array_options['options_statutfdt'].'" rowspan="'.$numberDay.'">';
			}
			else {
				print '<td class="center fixedcolumn3 '.$morecss.' '.($multiple_holiday ? 'holidaycolumnmultiple1' : 'holidaycolumn').' hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? $css_holiday[$dayinloopfromfirstdaytoshow][0] : '').'" rowspan="'.$numberDay.'">';
			}

			if($mode == 'card' && $displayVerification && $conf->global->FDT_STATUT_HOLIDAY && !$conf->global->FDT_STATUT_HOLIDAY_VALIDATE_VERIF) {
				print '<input type="checkbox"'.($holiday->array_options['options_statutfdt'] == 3 || !$modify ? ' disabled' : '').' name="holiday_valide['.$cptholiday.']" id="holiday_valide['.$cptholiday.']"'.($holiday->array_options['options_statutfdt'] != 1 ? ' checked' : '0').'> ';
			}

			print $holiday->getNomUrlBlank(2)." ".($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($durationHoliday / 3600, 2, '.', '') : convertSecondToTime($durationHoliday, 'allhourmin'));
			
			if($mode == 'card') {
				print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']"'.(!$modify  ? 'disabled' : ''), 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
			
				if($modify && $action != 'ediths00' && $action != 'ediths25' && $action != 'ediths50') {
					print '<input type="hidden" name="holiday_id['.$cptholiday.']"  id="holiday_id['.$cptholiday.']" value="'.$holiday->id.'">';
				}
			}
			else {
				print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']" disabled', 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
			}

			print '</td>';

			$cptholiday++;
		}
		elseif(empty($holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][0])) {
			print '<td class="center fixedcolumn3 '.$morecss.' '.($multiple_holiday ? 'holidaycolumnmultiple1' : 'holidaycolumn').' hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][0] ? ' '.$css_holiday[$dayinloopfromfirstdaytoshow][0] : '').'">';
			print '</td>';
		}
		
		if($multiple_holiday) {		
			if(!empty($holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][1])) {
				$holiday->fetch((int)$holiday_without_canceled[$dayinloopfromfirstdaytoshow]['rowid'][1]);
				$numberDay = (num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) ? num_between_day(($holiday->date_debut_gmt < $firstdaytoshow ? $firstdaytoshow : $holiday->date_debut_gmt), $holiday->date_fin_gmt, 1) : 1);
				if($idw + $numberDay > $nb_jour) $numberDay = $nb_jour - $idw;

				$durationHoliday = $holiday->getHourDuration($standard_week_hour, $dayinloopfromfirstdaytoshow, $fuser, $numberDay, $timeHolidayByDay);

				if($mode == 'card' && $conf->global->FDT_STATUT_HOLIDAY) {
					print '<td class="center fixedcolumn3 '.$morecss.' '.($multiple_holiday ? 'holidaycolumnmultiple2' : 'holidaycolumn').' hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? $css_holiday[$dayinloopfromfirstdaytoshow][1] : '').' statut'.$holiday->array_options['options_statutfdt'].'" rowspan="'.$numberDay.'">';
				}
				else {
					print '<td class="center fixedcolumn3 '.$morecss.' '.($multiple_holiday ? 'holidaycolumnmultiple2' : 'holidaycolumn').' hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? $css_holiday[$dayinloopfromfirstdaytoshow][1] : '').'" rowspan="'.$numberDay.'">';
				}

				if($mode == 'card' && $displayVerification && $conf->global->FDT_STATUT_HOLIDAY && !$conf->global->FDT_STATUT_HOLIDAY_VALIDATE_VERIF) {
					print '<input type="checkbox"'.($holiday->array_options['options_statutfdt'] == 3 || !$modify ? ' disabled' : '').' name="holiday_valide['.$cptholiday.']" id="holiday_valide['.$cptholiday.']"'.($holiday->array_options['options_statutfdt'] != 1 ? ' checked' : '0').'> ';
				}
	
				print $holiday->getNomUrlBlank(2)." ".($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($durationHoliday / 3600, 2, '.', '') : convertSecondToTime($durationHoliday, 'allhourmin'));
				
				if($mode == 'card') {
					print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']"'.(!$modify  ? 'disabled' : ''), 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
					if($modify && $action != 'ediths00' && $action != 'ediths25' && $action != 'ediths50') {
						print '<input type="hidden" name="holiday_id['.$cptholiday.']"  id="holiday_id['.$cptholiday.']" value="'.$holiday->id.'">';
					}
				}
				else {
					print ' '.$form->selectarray('holiday_type['.$cptholiday.']', $arraytypeleaves, $holiday->fk_type, 0, 0, 0, 'id="holiday_type['.$cptholiday.']" disabled', 0, 0, $holiday->array_options['options_statutfdt'] == 3, '', 'maxwidth80', true);
				}
	
				$cptholiday++;
			}
			else {
				print '<td class="center fixedcolumn3 '.$morecss.' '.($multiple_holiday ? 'holidaycolumnmultiple2' : 'holidaycolumn').' hide'.$idw.($css_holiday[$dayinloopfromfirstdaytoshow][1] ? ' '.$css_holiday[$dayinloopfromfirstdaytoshow][1] : '').'">';
			}
	
			print '</td>';
		}



		// Contrat
		$contrat = (float)$standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')];
		print '<td class="center fixedcolumn4 '.$morecss.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
		print '<span class="" id="contrat_'.$idw.'">'.($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($contrat / 3600, 2, '.', '') : convertSecondToTime($contrat, 'allhourmin')).'</span>';
		print '</td>';



		// Heures Jour
		$tableCell = '';
		$totalforday = 0;

		// Est-ce que l'utilisateur est en congé sur le jour actuel => Utilisé pour bloquer l'input
		$conges_hour = 0;
		foreach($isavailable[$dayinloopfromfirstdaytoshow]['in_hour'] as $key => $value) {
			if($value && $isavailable[$dayinloopfromfirstdaytoshow]['nb_jour'][$key] <= 1) {
				$conges_hour = 1; // Il y a un congés en heure sur la journée 
			}
		}
		
		$user_conges = 0;
		if((!$conges_hour || $timeHolidayByDay[$dayinloopfromfirstdaytoshow] >= $contrat) && $isavailable[$dayinloopfromfirstdaytoshow]['morning'] == false && $isavailable[$dayinloopfromfirstdaytoshow]['morning_reason'] == "leave_request" 
				&& $isavailable[$dayinloopfromfirstdaytoshow]['afternoon'] == false && $isavailable[$dayinloopfromfirstdaytoshow]['afternoon_reason'] == "leave_request" /*&& str_contains($css[$dayinloopfromfirstdaytoshow], 'onholidayallday')*/){
			$user_conges = 1;
		}

		if(dol_print_date($dayinloopfromfirstdaytoshow, '%Y-%m-%d') < '2024-06-03' && $heure_semaine == $conf->global->HEURE_SEMAINE) { 
			$tmp_heure_semaine = 35;
		}
		else {
			$tmp_heure_semaine = $heure_semaine;
		}

		if(dol_print_date($dayinloopfromfirstdaytoshow, '%Y-%m-%d') < '2024-06-03' && $heure_semaine_hs == $conf->global->HEURE_SEMAINE) {
			$tmp_heure_semaine_hs = 35;
		}
		else {
			$tmp_heure_semaine_hs = $heure_semaine_hs;
		}

		$tmparray = dol_getdate($dayinloopfromfirstdaytoshow);
		$alttitle = $langs->trans("AddHereTimeSpentForDay", $tmparray['day'], $tmparray['mon']);
		for($cpt = 0; $cpt < $conf->global->FDT_COLUMN_MAX_TASK_DAY; $cpt++) {
			$timespent = $timespent_month[$dayinloopfromfirstdaytoshow][$cpt];
			$alreadyspent = (!empty($timespent->timespent_duration) ? ($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($timespent->timespent_duration / 3600, 2, '.', '') : convertSecondToTime($timespent->timespent_duration, 'allhourmin')) : '');
			$prefilling_time = ''; 
			
			// Est-ce qu'on désactive l'input ou non ?
			$disabled = 0;
			if(!$modify || ($user_conges && !$modifier_jour_conges && empty($alreadyspent)) || ($cpt > 0 && (!$timespent_month[$dayinloopfromfirstdaytoshow][$cpt-1] || !$timespent_month[$dayinloopfromfirstdaytoshow][$cpt-1]->fk_task))) {
				$disabled = 1;
			}

			$class = '';
			$class_timespent = '';
			if($cpt >= $timespent_number) {
				$class .= ' displaynone';
			}

			$totalforday += (int)$timespent->timespent_duration;
			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
				$total_array['heure_jour'] += (int)$timespent->timespent_duration;
			}

			if($cpt == 0 && $mode == 'timesheet' && !$disabled && empty($timespent) && num_public_holiday($dayinloopfromfirstdaytoshowgmt, $dayinloopfromfirstdaytoshowgmt, '', 1) == 0 /*&& empty($timeHoliday[(int)$weekNumber]) */&& empty($timeSpentWeek[(int)$weekNumber]) && !empty($standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')]) && $standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')] != $timeHolidayByDay[$dayinloopfromfirstdaytoshow]) {
				if(!empty($timeHolidayByDay[$dayinloopfromfirstdaytoshow]) && $standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')] - $timeHolidayByDay[$dayinloopfromfirstdaytoshow]) {
					$prefilling_time = $standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')] - $timeHolidayByDay[$dayinloopfromfirstdaytoshow];
				}
				else {
					$prefilling_time = $standard_week_hour[dol_print_date($dayinloopfromfirstdaytoshow, '%A')];
				}
				$class_timespent .= ' prefilling_time';
				$prefilling_time = (!empty($prefilling_time) ? ($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($prefilling_time / 3600, 2, '.', '') : convertSecondToTime($prefilling_time, 'allhourmin')) : '');
			} 

			if($cpt == 0) $tableCellTimespent = '<td class="center fixedcolumn5 valignmiddle '.$morecss.' hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			if($cpt == 0) $tableCellHeureNuit = '<td class="center fixedcolumn6 valignmiddle '.$morecss.' hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			if($cpt == 0) $tableCellSite = '<td class="center fixedcolumn9 '.$morecss.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			if($cpt == 0) $tableCellAffaire = '<td class="center affairecolumn fixedcolumn10 '.$morecss.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			if($cpt == 0) $tableCellNoteModal = '<div class="center">';

			$tableCellTimespent .= '<div class="multipleLineColumn line_'.$idw.'_'.$cpt.$class.$class_timespent.'">';
			$tableCellHeureNuit .= '<div class="multipleLineColumn line_'.$idw.'_'.$cpt.$class.'">';
			$tableCellSite .= '<div class="multipleLineColumn line_'.$idw.'_'.$cpt.$class.'">';
			$tableCellAffaire .= '<div class="multipleLineColumn width150 minwidth150imp line_'.$idw.'_'.$cpt.$class.'">';

			if($cpt == $timespent_number - 1 && $timespent_number < $conf->global->FDT_COLUMN_MAX_TASK_DAY) {
				$tableCellTimespent .= '<span class="fas fa-plus" style="margin-left: 8px;" onclick="addTimespentLine(this, '.$idw.', '.$cpt.');"></span>';
			} 
			else {
				$tableCellTimespent .= '<span class="fas fa-plus visibilityhidden" style="margin-left: 8px;" onclick="addTimespentLine(this, '.$idw.', '.$cpt.');"></span>';
			}

			// Note 
			$note = $notes[$dayinloopfromfirstdaytoshow][$cpt]; 
			//$tableCellTimespent .= img_picto('Note', (empty($note) ? 'note_vide@feuilledetemps' : 'note_plein@feuilledetemps'), ' id="img_note_'.$cpt.'_'.$idw.'" style="display:inline-block; padding: 6px; vertical-align: middle;" onClick="openNote(\'note_'.$cpt.'_'.$idw.'\')"');
			$tableCellTimespent .= '<span class="'.(empty($note) ? 'far' : 'fas').' fa-sticky-note" id="img_note_'.$cpt.'_'.$idw.'" style="padding: 6px;" onClick="openNote(\'note_'.$cpt.'_'.$idw.'\')"></span>';
			$tableCellNoteModal .= '<div class="modal" id="note_'.$cpt.'_'.$idw.'">';
			$tableCellNoteModal .= '<div class = "modal-content">';
			$tableCellNoteModal .= '<span class="close" onclick="closeNotes2(this)">&times;</span>';
			$tableCellNoteModal .= '<a>'.$langs->trans('Note').' ('.dol_print_date($dayinloopfromfirstdaytoshow, '%a %d/%m/%y').")".'</a><br><br>';
			$tableCellNoteModal .= '<textarea class = "'.($idw < $num_first_day ? 'no-delete' : '').' flat"  rows = "3"'.($disabled ? ' disabled' : '').' style = "min-height:200px; width:370px; top:10px; max-width: 370px; min-width: 370px;"';
			$tableCellNoteModal .= ' name = "note['.$idw.']['.$cpt.']"';
			$tableCellNoteModal .= '>'.$note.'</textarea>';
			$tableCellNoteModal .= '</div></div>';

			// Time
			$tableCellTimespent .= '<input type="text" style="border: 1px solid grey;" alt="'.$alttitle.'" title="'.$alttitle.'" '.($disabled ? 'disabled' : '').' 
									 class="center smallpadd time_'.$idw.'" size="2" id="timeadded['.$idw.']['.$cpt.']" name="task['.$idw.']['.$cpt.']" placeholder="'.$prefilling_time.'" value="'.$alreadyspent.'" 
									 cols="2"  maxlength="5"';
			$tableCellTimespent .= ' onfocus="this.oldvalue = this.value; this.oldvalue_focus = this.value;"';
			$tableCellTimespent .= ' onkeypress="return regexEvent_TS(this,event,\'timeChar\');"';
			$tableCellTimespent .= ' onkeyup="updateTotal_TS(this, '.$idw.',\''.$modeinput.'\', 0, '.$num_first_day.', '.($timeHolidayByDay[$dayinloopfromfirstdaytoshow] / 3600).');';
			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) $tableCellTimespent .= ' updateTotalSigedi(this, \'heure_jour\', \'duration\'); updateTotalSigedi(this, \'heure_total\', \'duration\');';
			$tableCellTimespent .= ' this.oldvalue = this.value;
									 updateTotalWeek(\''.$modeinput.'\', 0, 0, \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');
									 deletePrefillingClass(this, \''.$fuser->array_options['options_sitedefaut'].'\');"';
			$tableCellTimespent .= ' onblur="regexEvent_TS(this,event,\''.$modeinput.'\'); validateTime(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.', 0,\''.$type_deplacement.'\', '.$tmp_heure_semaine_hs.', 0, '.$heure_max_jour.', '.$heure_max_semaine.');
									 updateTotal_TS(this, '.$idw.',\''.$modeinput.'\', 0, '.$num_first_day.', '.($timeHolidayByDay[$dayinloopfromfirstdaytoshow] / 3600).');'; 
			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) $tableCellTimespent .= ' updateTotalSigedi(this, \'heure_jour\', \'duration\'); updateTotalSigedi(this, \'heure_total\', \'duration\');';
			$tableCellTimespent .= ' updateTotalWeek(\''.$modeinput.'\', 0, 0, \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');
									 deletePrefillingClass(this, \''.$fuser->array_options['options_sitedefaut'].'\'); autoFillSite(\''.$fuser->array_options['options_sitedefaut'].'\', '.$idw.', '.$cpt.')"';
								  // validateTime_HS(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.', 0, 0, 0, 0, '.$tmp_heure_semaine_hs.');"';
			//$tableCellTimespent .= ' onchange="updateTotalSigedi(this, \''.$key.'\', \''.$type.'\');"';
			$tableCellTimespent .= '	/>';

			$tableCellTimespent .= '</div>';

			
			
			// Heures Nuit
			$heure_nuit_nf = $otherTaskTime['heure_nuit'][$dayinloopfromfirstdaytoshow][$timespent->timespent_id];
			$heure_nuit = ($heure_nuit_nf > 0 ? ($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($heure_nuit_nf / 3600, 2, '.', '') : convertSecondToTime($heure_nuit_nf, 'allhourmin')) : '');
			$totalforday += (int)$heure_nuit_nf;

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
				$total_array['heure_nuit'] += (int)$heure_nuit_nf;
			}

			$tableCellHeureNuit .= '<input type="text"'.($disabled ? ' disabled' : '').' 
									alt="Ajoutez ici les heures de nuit" title="Ajoutez ici les heures de nuit" name="heure_nuit['.$idw."][".$cpt.']" 
									class="center smallpadd mt0 ml0 heure_nuit time_heure_nuit_'.$cpt.'_'.$idw.'" size="2" id="time_heure_nuit['.$idw.']['.$cpt.']" 
									value="'.$heure_nuit.'" cols="2"  maxlength="5"';
			$tableCellHeureNuit .= ' onfocus="this.oldvalue = this.value; this.oldvalue_focus = this.value;"';
			$tableCellHeureNuit .= ' onkeypress="return regexEvent_TS(this,event,\'timeChar\')"';
			$tableCellHeureNuit .= ' onkeyup="updateTotal_TS(this, '.$idw.',\''.$modeinput.'\', 0, '.$num_first_day.', '.($timeHolidayByDay[$dayinloopfromfirstdaytoshow] / 3600).');'; 
			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) $tableCellHeureNuit .= ' updateTotalSigedi(this, \'heure_nuit\', \'duration\'); updateTotalSigedi(this, \'heure_total\', \'duration\');'; 
			$tableCellHeureNuit .= ' this.oldvalue = this.value;
									 updateTotalWeek(\''.$modeinput.'\', 0, 0, \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');"';
			$tableCellHeureNuit .= ' onblur="regexEvent_TS(this,event,\''.$modeinput.'\'); validateTime(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.', 0,\''.$type_deplacement.'\', '.$tmp_heure_semaine_hs.', 0, '.$heure_max_jour.', '.$heure_max_semaine.');
									 updateTotal_TS(this, '.$idw.',\''.$modeinput.'\', 0, '.$num_first_day.', '.($timeHolidayByDay[$dayinloopfromfirstdaytoshow] / 3600).');'; 
			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) $tableCellHeureNuit .= ' updateTotalSigedi(this, \'heure_nuit\', \'duration\'); updateTotalSigedi(this, \'heure_total\', \'duration\');';
			$tableCellHeureNuit .= ' updateTotalWeek(\''.$modeinput.'\', 0, 0, \''.$weekNumber.'\', '.($timeHoliday[(int)$weekNumber] ? $timeHoliday[(int)$weekNumber] : 0).', '.$tmp_heure_semaine.');
									 autoFillSite(\''.$fuser->array_options['options_sitedefaut'].'\', '.$idw.', '.$cpt.')"';
			$tableCellHeureNuit .= ' />';
			$tableCellHeureNuit .= '</div>';



			// Site
			// $tableCellSite .= '<input type="text" id="site['.$idw.']['.$cpt.']" name="site['.$idw.']['.$cpt.']"></input>';
			$tableCellSite .= $projet_task_time_other->showInputField($projet_task_time_other->fields['site'], 'site', $otherTaskTime['site'][$dayinloopfromfirstdaytoshow][$timespent->timespent_id], ($disabled ? ' disabled' : '').' oninput="forceUppercase(this)"', '['.$idw.']['.$cpt.']');
			$tableCellSite .= '</div>';



			// Affaires
			$tableCellAffaire .= '<select '.($disabled ? ' disabled' : '').' data-selected="'.$timespent->fk_task.'" class="select-task valignmiddle flat width150" id="fk_task_'.$idw.'_'.$cpt.'" name="fk_task['.$idw.']['.$cpt.']" onchange="deletePrefillingClass(this, \''.$fuser->array_options['options_sitedefaut'].'\');"></select>';
			//$tableCellAffaire .= $formproject->selectTasksCustom(-1, $timespent->fk_task, 'fk_task['.$idw.']['.$cpt.']', 0, 0, 1, 1, 0, $disabled, 'width150', $projectsListId, 'projectstatut', $fuser, 'fk_task_'.$idw.'_'.$cpt, ($idw == 0 && $cpt == 0 ? 1 : 0), $task_load, 'onchange="deletePrefillingClass(this, \''.$fuser->array_options['options_sitedefaut'].'\');"');
			$tableCellAffaire .= '</div>';



			if($cpt == $conf->global->FDT_COLUMN_MAX_TASK_DAY - 1) {
				// There is a diff between total shown on screen and total spent by user, so we add a line with all other cumulated time of user
				$timeonothertasks = ($totalforeachday[$dayinloopfromfirstdaytoshow] - $totalforday);
				if ($timeonothertasks > 0) {
					$tableCellTimespent .= '<div class="timesheetalreadyrecorded" title="Heures pointées sur d\'autres tâches"><input type="text" class="center smallpadd time_'.$idw.'" size="2" disabled id="timeadded['.$idw.']['.($cpt + 1).']" name="task['.$idw.']['.($cpt + 1).']" value="';
					$tableCellTimespent .=  ($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($timeonothertasks / 3600, 2, '.', '') : convertSecondToTime($timeonothertasks, 'allhourmin'));
					$tableCellTimespent .=  '"></div>';

					$totalforday += (int)$timeonothertasks;
					if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
						$total_array['heure_jour'] += (int)$timeonothertasks;
					}

					$tableCellHeureNuit .= '<div><input style="visibility: hidden"></div>';
					$tableCellSite .= '<div><input style="visibility: hidden"></div>';
					$tableCellAffaire .= '<div><input style="visibility: hidden"></div>';
				}

				$tableCellTimespent .= '</td>';
				$tableCellHeureNuit .= '</td>';
				$tableCellSite .= '</td>';
				$tableCellAffaire .= '</td>';
				$tableCellNoteModal .= '</div>';
			} 
		}

		print $tableCellTimespent;
		print $tableCellHeureNuit;
		print $tableCellNoteModal;



		// Total
		if($modeinput == 'hours_decimal') {
			$total = (!empty($totalforday) ? number_format($totalforday / 3600, 2, '.', '') : '0.00');
		}
		else {
			$total = (convertSecondToTime($totalforday, 'allhourmin') != '0' ? convertSecondToTime($totalforday, 'allhourmin') : '00:00');
		}
		print '<td class="liste_total fixedcolumn7 center '.$morecss.' hide'.$idw.($total != '00:00' ? ' bold' : '').($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'" align="center">';
		print '<div class="totalDay'.$idw.'" '.(!empty($style) ? $style : '').'>'.$total;
		print '</div></td>';
		if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
			$total_array['heure_total'] += $totalforday;
		}

		// Diff
		if(num_public_holiday($dayinloopfromfirstdaytoshowgmt, $dayinloopfromfirstdaytoshowgmt, '', 1) != 0 && empty($timeHolidayByDay[$dayinloopfromfirstdaytoshow])) {
			$diff = $totalforday;
		}
		else {
			$diff = $totalforday + $timeHolidayByDay[$dayinloopfromfirstdaytoshow] - $contrat;
		}
		$diff_class = '';
		if($diff > 0) {
			$diff_class .= "diffpositive";
		}
		elseif($diff < 0) {
			$diff_class .= "diffnegative";
		}
		print '<td class="center fixedcolumn8 '.$morecss.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
		print '<span class="'.$diff_class.'" id="diff_'.$idw.'">'.($diff > 0 ? '+' : '').($conf->global->FDT_DECIMAL_HOUR_FORMAT ? number_format($diff / 3600, 2, '.', '') : convertSecondToTime($diff, 'allhourmin')).'</span>';
		print '</td>';



		// Site
		print $tableCellSite;



		// Affaire
		print $tableCellAffaire;



		// Autres
		if($silae_array[$dayinloopfromfirstdaytoshow]->id > 0) $silae = $silae_array[$dayinloopfromfirstdaytoshow];
		foreach($silae->fields as $key => $value) {
			if(in_array($key, array('heure_sup00', 'heure_sup25', 'heure_sup50', 'heure_sup50ht')) && ($user->hasRight('feuilledetemps','feuilledetemps','modify_verification') && $object->status != 0 && $object->status != 2 && $object->status != 3)) {
				if(!$conf->global->HEURE_SUP_SUPERIOR_HEURE_MAX_SEMAINE && $key == 'heure_sup50ht') {
					continue;
				}

				$moreparam = 'onfocus="this.oldvalue = this.value;"';
				$moreparam .= ' onkeypress="return regexEvent_TS(this,event,\'timeChar\');"';
				$moreparam .= ' maxlength="5"';
				if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) $moreparam .= ' onchange="updateTotalSigedi(this, \''.$key.'\', \''.$type.'\');"';
				//$moreparam .= ($disabled ? ' disabled' : '');
				$moreparam .= ' disabled';

				print '<td class="center '.$morecss.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
					if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim' || !empty($silae->$key)) {
						print $silae->showInputField($value, $key, ($silae->$key / 3600), $moreparam, '['.$idw.']');
					}
				print '</td>';
				if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
					$total_array[$key] += $silae->$key;
				}
			}
		}

		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			if (dol_eval($extrafields->attributes[$silae->table_element]['list'][$key], 1, 1, '2') != 1) {
				continue;
			}
			
			$type = $extrafields->attributes[$silae->table_element]['type'][$key];

			print '<td class="center '.$morecss.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';

			$moreparam = '';
			if(!$modify || ($user_conges && !$modifier_jour_conges)) {
				$moreparam .= ' disabled';
			}

			if($type == 'boolean') {
				if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) $moreparam .= ' onchange="updateTotalSigedi(this, \''.$key.'\', \''.$type.'\');"';
				$checked = '';
				if (!empty($silae->array_options['options_'.$key])) $checked = ' checked';
				print '<input type="checkbox" class="flat valignmiddle'.($morecss ? ' '.$morecss : '').' maxwidthonsmartphone" name="options_'.$key.'['.$idw.']" id="options_'.$key.'['.$idw.']" '.$checked.$moreparam.'>';
			}
			elseif($type == 'text') {
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor('options_'.$key.'['.$idw.']', $silae->array_options['options_'.$key], '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
				print $doleditor->Create(1, '', true, '', '', $moreparam, $extrafields->attributes[$silae->table_element]['css'][$key]);
			}
			else {
				$moreparam .= ' onfocus="this.oldvalue = this.value;"';
				$moreparam .= ' onkeypress="return regexEvent_TS(this,event,\'timeChar\');"';
				$moreparam .= ' maxlength="7"';
				if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) $moreparam .= ' onchange="updateTotalSigedi(this, \''.$key.'\', \''.$type.'\');"';
				print $extrafields->showInputField($key, $silae->array_options['options_'.$key], $moreparam, '['.$idw.']', '', 0, $silae->id, $silae->table_element);
			}
			print '</td>';
			if(!is_null($total_array[$key]) && $idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day))) {
				$total_array[$key] += $silae->array_options['options_'.$key];
			}
		}

	print '</tr>';

	return $total_array;
}

/**
 * Renvoi le nombre de jour d'écart de la date avec le lundi de la meme semaine
 */
function ecart_lundi($date){
	$jour = dol_print_date($date, '%a');
	switch($jour){
		case 'Lun':
			return 0;
		case 'Mar':
			return 1;
		case 'Mer':
			return 2;
		case 'Jeu':
			return 3;
		case 'Ven':
			return 4;
		case 'Sam':
			return 5;
		case 'Dim':
			return 6;
		default:
			break;
	}
}


/**
 * Tableau des déplacements d'une feuille de temps
 *
 * @param	int			$firstdaytoshow			First day to show
 * @param	int			$lastdaytoshow			Last day to show
 * @param	int			$nb_jour				Nombre de jour à afficher
 * @param	User|null	$fuser			Restrict list to user if defined
 * @param   string[]    $css					css pour la couleur des cases
 * @param   int         $num_first_day			Numéro du 1er jour du mois (0 si celui-ci n'apparait pas sur les dates affichées)
 * @return  array								Tableau avec le nombre de chaque type de déplacement
 */
function FeuilleDeTempsDeplacement($firstdaytoshow, $lastdaytoshow, $nb_jour, $fuser, $css, $num_first_day = null, $num_last_day = null, $disabled, $addcolspan, $dayinloopfromfirstdaytoshow_array) {
	global $conf, $db, $user, $langs, $form, $object;

	$deplacement = New Deplacement($db);
	$notes = $deplacement->fetchAllNotes($firstdaytoshow, $lastdaytoshow, $fuser->id);
	$regul = new Regul($db);
	$regul->fetchWithoutId(dol_time_plus_duree($firstdaytoshow, $num_first_day, 'd'), $fuser->id);

	$array_deplacement = $deplacement->getAllDeplacements($firstdaytoshow, $lastdaytoshow, $fuser->id);
	$nb_deplacement_ponctuel = 0;
	$nb_type_deplacement = array('d1' => 0, 'd2' => 0, 'd3' => 0, 'd4' => 0, 'gd1' => 0, 'gd2' => 0, 'dom' => 0);
	$nb_moyen_transport = array('VS' => 0, 'A' => 0, 'T' => 0);
	
	// Note 
	print '<tr class="oddeven nostrong favoris">';
	print '<td colspan="'.(2 + $addcolspan).'" class="fixed"><strong>Note Déplacement</strong></td>';
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

		if($idw > 0 && $idw == $num_first_day){
			print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
		}

		$note = $notes[$dayinloopfromfirstdaytoshow];

		print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
		print img_picto('Note', (empty($note) ? 'note_vide@feuilledetemps' : 'note_plein@feuilledetemps'), ' id="img_note_deplacement_'.$idw.'" style="display:inline-block; padding: 6px; vertical-align: middle;" onClick="openNote(\'note_deplacement_'.$idw.'\')"');
		print '<div class="modal" id="note_deplacement_'.$idw.'">';
		print '<div class = "modal-content">';
		print '<span class="close" onclick="closeNotes(this)">&times;</span>';
		print '<a>'.$langs->trans('Note Déplacement').' ('.dol_print_date($dayinloopfromfirstdaytoshow, '%a %d/%m/%y').")".'</a><br><br>';
		print '<textarea '.($disabled ? 'disabled ' : '').'class = "flat"  rows = "3" style = "width:350px; top:10px; max-width: 350px; min-width: 350px;"';
		print ' name = "note_deplacement['.$idw.']"';
		print '>'.$note.'</textarea>';
		print '</div></div>';
		print '</td>';

	}
	print '<td class="liste_total center fixed"><div id="totalNote"></div></td>';
	print '</tr>';

	// Déplacement Ponctuel
	print '<tr class="oddeven nostrong favoris">';
		print '<td colspan="'.(2 + $addcolspan).'" class="fixed"><strong>Déplacement PONCTUEL</strong>';
		print $form->textwithpicto('', 'Déplacement ponctuel : déplacement inhabituel dans le cadre d\'une formation, d\'une visite médicale...');
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['deplacement_ponctuel'][$dayinloopfromfirstdaytoshow]) {
				$nb_deplacement_ponctuel++;
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $deplacement->showInputField('', 'deplacement_ponctuel', $array_deplacement['deplacement_ponctuel'][$dayinloopfromfirstdaytoshow], ($disabled ? 'disabled ' : '').'onchange="updateTotal_DeplacementPonctuel('.$nb_jour.', '.$num_first_day.')"', $keysuffix);
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($nb_deplacement_ponctuel != 0 ? 'noNull' : '').'" id="totalDeplacementPonctuel">'.$nb_deplacement_ponctuel.' DP</div></td>';
	print '</tr>';

	// Type de deplacement
	print '<tr class="oddeven nostrong favoris">';
		print '<td colspan="'.(2 + $addcolspan).'" class="fixed"><strong>Type de déplacement';
		print $form->textwithpicto('', 'Dx : détail dans la partie supérieure de votre FdT en déroulant "Fonctionnement"<br>GD1, GD3 et GD4 = grand déplacement ou déplacement régional<br>GD2 : jour de retour en détente');
		print '</strong>';
		if($object->status == $object::STATUS_VERIFICATION || $object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_EXPORTED) {
			print '<div class="regulTypeDeplacement">';
			if($regul->d1 != 0) {
				$nb_type_deplacement['d1'] += $regul->d1;
				print '<input type="text" alt="Ajoutez ici les régulations des D1" title="Ajoutez ici les régulations des D1" name="regulD1" id="regulD1" class="smallpad" placeholder="D1" value="'.($regul->d1 ? $regul->d1 : '').'" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
				onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')" disabled>';
			}
			if($regul->d2 != 0) {
				$nb_type_deplacement['d2'] += $regul->d2;
				print '<input type="text" alt="Ajoutez ici les régulations des D2" title="Ajoutez ici les régulations des D2" name="regulD2" id="regulD2" class="smallpad" placeholder="D2" value="'.($regul->d2 ? $regul->d2 : '').'" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
				onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')" disabled>';
			}
			if($regul->d3 != 0) {
				$nb_type_deplacement['d3'] += $regul->d3;
				print '<input type="text" alt="Ajoutez ici les régulations des D3" title="Ajoutez ici les régulations des D3" name="regulD3" id="regulD3" class="smallpad" placeholder="D3" value="'.($regul->d3 ? $regul->d3 : '').'" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
				onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')" disabled>';
			}
			if($regul->d4 != 0) {
				$nb_type_deplacement['d4'] += $regul->d4;
				print '<input type="text" alt="Ajoutez ici les régulations des D4" title="Ajoutez ici les régulations des D4" name="regulD4" id="regulD4" class="smallpad" placeholder="D4" value="'.($regul->d4 ? $regul->d4 : '').'" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
				onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')" disabled>';
			}

			if($regul->gd1 != 0) {
				$nb_type_deplacement['gd1'] += $regul->gd1;
				print '<input type="text" alt="Ajoutez ici les régulations des GD1" title="Ajoutez ici les régulations des GD1" name="regulGD1" id="regulGD1" class="smallpad" placeholder="GD1" value="'.($regul->gd1 ? $regul->gd1 : '').'" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
				onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')" disabled>';
			}
			if($regul->gd2 != 0) {
				$nb_type_deplacement['gd2'] += $regul->gd2;
				print '<input type="text" alt="Ajoutez ici les régulations des GD2" title="Ajoutez ici les régulations des GD2" name="regulGD2" id="regulGD2" class="smallpad" placeholder="GD2" value="'.($regul->gd2 ? $regul->gd2 : '').'" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
				onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')" disabled>';
			}
			if($regul->dom != 0) {
				$nb_type_deplacement['dom'] += $regul->dom;
				print '<input type="text" alt="Ajoutez ici les régulations des DOM" title="Ajoutez ici les régulations des DOM" name="regulDOM" id="regulDOM" class="smallpad" placeholder="DOM" value="'.($regul->dom ? $regul->dom : '').'" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
				onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')" disabled>';
			}
			print '</div>';
		}
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_type_deplacement['d1']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_type_deplacement['d2']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 3) {
				$nb_type_deplacement['d3']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 4) {
				$nb_type_deplacement['d4']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && ($array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 5 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 8 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 9)) {
				$nb_type_deplacement['gd1']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 6) {
				$nb_type_deplacement['gd2']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 7) {
				$nb_type_deplacement['dom']++;
			}

			$tmp = $conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2;
			$conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2 = 1;
			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $deplacement->showInputField('', 'type_deplacement', $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow], ($disabled ? 'disabled ' : '').'onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"', $keysuffix, '', 1);
			$conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2 = $tmp;
			print '</td>';
		}

		$totalTypeDeplacement = ($nb_type_deplacement['d1'] != 0 ? $nb_type_deplacement['d1']." D1<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['d2'] != 0 ? $nb_type_deplacement['d2']." D2<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['d3'] != 0 ? $nb_type_deplacement['d3']." D3<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['d4'] != 0 ? $nb_type_deplacement['d4']." D4<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['gd1'] != 0 ? $nb_type_deplacement['gd1']." GD1<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['gd2'] != 0 ? $nb_type_deplacement['gd2']." GD2<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['dom'] != 0 ? $nb_type_deplacement['dom']." DOM<br>" : "");

		print '<td class="liste_total center fixed"><div class="'.($totalTypeDeplacement != 0 ? 'noNull' : '').'" id="totalTypeDeplacement">'.$totalTypeDeplacement.'</div></td>';
	print '</tr>';

	// Moyen de transport
	print '<tr class="oddeven nostrong favoris">';
		print '<td colspan="'.(2 + $addcolspan).'" class="fixed"><strong>Moyen de transport</strong>';
		print $form->textwithpicto('', "VS = Véhicule de Service<br>A = Avion<br>T = Train");
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_moyen_transport['VS']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_moyen_transport['A']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 3) {
				$nb_moyen_transport['T']++;
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $deplacement->showInputField('', 'moyen_transport', $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow], ($disabled ? 'disabled ' : '').'onchange="updateTotal_MoyenTransport('.$nb_jour.', '.$num_first_day.'); deleteTypeDeplacementVS('.$idw.', '.$nb_jour.', '.$num_first_day.')"', $keysuffix, '', 1);
			print '</td>';
		}

		$totalMoyenTransport = ($nb_moyen_transport['VS'] != 0 ? $nb_moyen_transport['VS']." VS<br>" : "");
		$totalMoyenTransport .= ($nb_moyen_transport['A'] != 0 ? $nb_moyen_transport['A']." A<br>" : "");
		$totalMoyenTransport .= ($nb_moyen_transport['T'] != 0 ? $nb_moyen_transport['T']." T<br>" : "");

		print '<td class="liste_total center fixed"><div class="'.($totalMoyenTransport != 0 ? 'noNull' : '').'" id="totalMoyenTransport">'.$totalMoyenTransport.'</div></td>';
	print '</tr>';
}


/**
 * Tableau de vérification (silae) d'une feuille de temps
 *
 * @param	int			$firstdaytoshow			First day to show
 * @param	int			$lastdaytoshow			Last day to show
 * @param	int			$nb_jour				Nombre de jour à afficher
 * @param	User|null	$fuser			Restrict list to user if defined
 * @param   string[]    $css					css pour la couleur des cases
 * @param   int         $num_first_day			Numéro du 1er jour du mois (0 si celui-ci n'apparait pas sur les dates affichées)
 * @return  array								Tableau avec le nombre de chaque type de déplacement
 */
function FeuilleDeTempsVerification($firstdaytoshow, $lastdaytoshow, $nb_jour, $fuser, $css, $css_holiday, $num_first_day, $disabled, $dayinloopfromfirstdaytoshow_array) {
	global $conf, $db, $user, $langs, $form, $object;

	$silae = New Silae($db);
	$arraySilae = $silae->getAllSilae($firstdaytoshow, $lastdaytoshow, $fuser->id);
	$regul = new Regul($db);
	$regul->fetchWithoutId(dol_time_plus_duree($firstdaytoshow, $num_first_day, 'd'), $fuser->id);
	$deplacement = New Deplacement($db);
	$notes = $deplacement->fetchAllNotes($firstdaytoshow, $lastdaytoshow, $fuser->id);

	$total_heure_sup00 = 0;
	$total_heure_sup25 = 0;
	$total_heure_sup50 = 0;
	$total_heure_sup50ht = 0;
	$total_heure_nuit = 0;
	$total_heure_route = 0;
	$nb_repas = array('R1' => 0, 'R2' => 0);
	$total_kilometres = 0;
	$nb_indemnite_tt = 0;
	$nb_deplacement_ponctuel = 0;
	$nb_type_deplacement = array('d1' => 0, 'd2' => 0, 'd3' => 0, 'd4' => 0, 'gd1' => 0, 'gd2' => 0, 'dom' => 0);
	$nb_moyen_transport = array('VS' => 0, 'A' => 0, 'T' => 0);

	$array_deplacement = $deplacement->getAllDeplacements($firstdaytoshow, $lastdaytoshow, $fuser->id);

	// Gestion des types de déplacement de l'utilisateur
	$userInDeplacement = 0;
	$userInGrandDeplacement = 0;
	if($conf->donneesrh->enabled) {
		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label('donneesrh_Deplacement');
		$userField_deplacement = new UserField($db);
		$userField_deplacement->id = $object->fk_user;
		$userField_deplacement->table_element = 'donneesrh_Deplacement';
		$userField_deplacement->fetch_optionals();

		if(!empty($userField_deplacement->array_options['options_d_1']) || !empty($userField_deplacement->array_options['options_d_2']) || !empty($userField_deplacement->array_options['options_d_3']) || !empty($userField_deplacement->array_options['options_d_4'])) {
			$userInDeplacement = 1;
		}
		if(!empty($userField_deplacement->array_options['options_gd1']) || !empty($userField_deplacement->array_options['options_gd3']) || !empty($userField_deplacement->array_options['options_gd4'])) {
			$userInGrandDeplacement = 1;
		}
	}

	// Note 
	print '<tr class="oddeven nostrong">';
	print '<td colspan="2" class="fixed"><strong>Note Déplacement</strong></td>';
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0

		if($idw > 0 && $idw == $num_first_day){
			print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
		}

		$note = $notes[$dayinloopfromfirstdaytoshow];

		print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
		print img_picto('Note', (empty($note) ? 'note_vide@feuilledetemps' : 'note_plein@feuilledetemps'), ' id="img_note_deplacement_'.$idw.'" style="display:inline-block; padding: 6px; vertical-align: middle;" onClick="openNote(\'note_deplacement_'.$idw.'\')"');
		print '<div class="modal" id="note_deplacement_'.$idw.'">';
		print '<div class = "modal-content">';
		print '<span class="close" onclick="closeNotes(this)">&times;</span>';
		print '<a>'.$langs->trans('Note Déplacement').' ('.dol_print_date($dayinloopfromfirstdaytoshow, '%a %d/%m/%y').")".'</a><br><br>';
		print '<textarea class="flat"  rows="3" style="width:350px; top:10px; max-width: 350px; min-width: 350px;"'.($disabled ? ' disabled' : '');
		print ' name = "note_deplacement['.$idw.']"';
		print '>'.$note.'</textarea>';
		print '</div></div>';
		print '</td>';

	}
	print '<td class="liste_total center fixed"><div id="totalNote"></div></td>';
	print '</tr>';

	// Déplacement Ponctuel
	print '<tr class="oddeven nostrong">';
		print '<td colspan="2" class="fixed"><strong>Déplacement PONCTUEL</strong>';
		print $form->textwithpicto('', 'Déplacement ponctuel : déplacement inhabituel dans le cadre d\'une formation, d\'une visite médicale...');
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['deplacement_ponctuel'][$dayinloopfromfirstdaytoshow]) {
				$nb_deplacement_ponctuel++;
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $deplacement->showInputField($deplacement->fields['deplacement_ponctuel'], 'deplacement_ponctuel', $array_deplacement['deplacement_ponctuel'][$dayinloopfromfirstdaytoshow], 'onchange="updateTotal_DeplacementPonctuel('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : ''), $keysuffix, '', ($css_holiday[$dayinloopfromfirstdaytoshow] ? 'deplacement_holiday' : ''));
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($nb_deplacement_ponctuel != 0 ? 'noNull' : '').'" id="totalDeplacementPonctuel">'.$nb_deplacement_ponctuel.' DP</div></td>';
	print '</tr>';

	// Type de deplacement
	print '<tr class="oddeven nostrong">';
		print '<td colspan="2" class="fixed"><strong>Type de déplacement';
		print $form->textwithpicto('', 'Dx : détail dans la partie supérieure de votre FdT en déroulant "Fonctionnement"<br>GD1, GD3 et GD4 = grand déplacement ou déplacement régional<br>GD2 : jour de retour en détente');
		print '</strong>';
		print '<div class="regulTypeDeplacement">';
		if($userInDeplacement || $regul->d1 != 0) {
			$nb_type_deplacement['d1'] += $regul->d1;
			print '<input type="text" alt="Ajoutez ici les régulations des D1" title="Ajoutez ici les régulations des D1" name="regulD1" id="regulD1" class="smallpad" placeholder="D1" value="'.($regul->d1 ? $regul->d1 : '').'" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
			onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		}
		if($userInDeplacement || $regul->d2 != 0) {
			$nb_type_deplacement['d2'] += $regul->d2;
			print '<input type="text" alt="Ajoutez ici les régulations des D2" title="Ajoutez ici les régulations des D2" name="regulD2" id="regulD2" class="smallpad" placeholder="D2" value="'.($regul->d2 ? $regul->d2 : '').'" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
			onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		}
		if($userInDeplacement || $regul->d3 != 0) {
			$nb_type_deplacement['d3'] += $regul->d3;
			print '<input type="text" alt="Ajoutez ici les régulations des D3" title="Ajoutez ici les régulations des D3" name="regulD3" id="regulD3" class="smallpad" placeholder="D3" value="'.($regul->d3 ? $regul->d3 : '').'" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
			onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		}
		if($userInDeplacement || $regul->d4 != 0) {
			$nb_type_deplacement['d4'] += $regul->d4;
			print '<input type="text" alt="Ajoutez ici les régulations des D4" title="Ajoutez ici les régulations des D4" name="regulD4" id="regulD4" class="smallpad" placeholder="D4" value="'.($regul->d4 ? $regul->d4 : '').'" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
			onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		}

		if($userInGrandDeplacement || $regul->gd1 != 0) {
			$nb_type_deplacement['gd1'] += $regul->gd1;
			print '<input type="text" alt="Ajoutez ici les régulations des GD1" title="Ajoutez ici les régulations des GD1" name="regulGD1" id="regulGD1" class="smallpad" placeholder="GD1" value="'.($regul->gd1 ? $regul->gd1 : '').'" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
			onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		}
		if($userInGrandDeplacement || $regul->gd2 != 0) {
			$nb_type_deplacement['gd2'] += $regul->gd2;
			print '<input type="text" alt="Ajoutez ici les régulations des GD2" title="Ajoutez ici les régulations des GD2" name="regulGD2" id="regulGD2" class="smallpad" placeholder="GD2" value="'.($regul->gd2 ? $regul->gd2 : '').'" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
			onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		}
		if($regul->dom != 0) {
			$nb_type_deplacement['dom'] += $regul->dom;
			print '<input type="text" alt="Ajoutez ici les régulations des DOM" title="Ajoutez ici les régulations des DOM" name="regulDOM" id="regulDOM" class="smallpad" placeholder="DOM" value="'.($regul->dom ? $regul->dom : '').'" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
			onchange="updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		}
		print '</div>';
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_type_deplacement['d1']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_type_deplacement['d2']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 3) {
				$nb_type_deplacement['d3']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 4) {
				$nb_type_deplacement['d4']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && ($array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 5 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 8 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 9)) {
				$nb_type_deplacement['gd1']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 6) {
				$nb_type_deplacement['gd2']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 7) {
				$nb_type_deplacement['dom']++;
			}

			$tmp = $conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2;
			$conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2 = 1;
			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $deplacement->showInputField('', 'type_deplacement', $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow], 'onchange="updateTypeDeplacement(this, '.($css_holiday[$dayinloopfromfirstdaytoshow] ? '1' : '0').'); updateTotal_TypeDeplacement('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : ''), $keysuffix, '', ($css_holiday[$dayinloopfromfirstdaytoshow] && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] ? 'deplacement_holiday' : 1));
			$conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2 = $tmp;
			print '</td>';

		}

		$totalTypeDeplacement = ($nb_type_deplacement['d1'] != 0 ? $nb_type_deplacement['d1']." D1<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['d2'] != 0 ? $nb_type_deplacement['d2']." D2<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['d3'] != 0 ? $nb_type_deplacement['d3']." D3<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['d4'] != 0 ? $nb_type_deplacement['d4']." D4<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['gd1'] != 0 ? $nb_type_deplacement['gd1']." GD1<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['gd2'] != 0 ? $nb_type_deplacement['gd2']." GD2<br>" : "");
		$totalTypeDeplacement .= ($nb_type_deplacement['dom'] != 0 ? $nb_type_deplacement['dom']." DOM<br>" : "");

		print '<td class="liste_total center fixed"><div class="'.($totalTypeDeplacement != 0 ? 'noNull' : '').'" id="totalTypeDeplacement">'.$totalTypeDeplacement.'</div></td>';
	print '</tr>';

	// Moyen de transport
	print '<tr class="oddeven nostrong">';
		print '<td colspan="2" class="fixed"><strong>Moyen de transport</strong>';
		print $form->textwithpicto('', "VS = Véhicule de Service<br>A = Avion<br>T = Train");
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_moyen_transport['VS']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_moyen_transport['A']++;
			}
			elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 3) {
				$nb_moyen_transport['T']++;
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $deplacement->showInputField('', 'moyen_transport', $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow], 'onchange="updateMoyenTransport(this, '.($css_holiday[$dayinloopfromfirstdaytoshow] ? '1' : '0').'); updateTotal_MoyenTransport('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : ''), $keysuffix, '', ($css_holiday[$dayinloopfromfirstdaytoshow] && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] ? 'deplacement_holiday' : 1));
			print '</td>';
		}
		$totalMoyenTransport = ($nb_moyen_transport['VS'] != 0 ? $nb_moyen_transport['VS']." VS<br>" : "");
		$totalMoyenTransport .= ($nb_moyen_transport['A'] != 0 ? $nb_moyen_transport['A']." A<br>" : "");
		$totalMoyenTransport .= ($nb_moyen_transport['T'] != 0 ? $nb_moyen_transport['T']." T<br>" : "");
		print '<td class="liste_total center fixed"><div class="'.($totalMoyenTransport != 0 ? 'noNull' : '').'" id="totalMoyenTransport">'.$totalMoyenTransport.'</div></td>';
	print '</tr>';

	// Heure Sup 0%
	print '<tr class="nostrong">';
		print '<td colspan="2" class="fixed">';
			print '<div style="display: flex; align-items: center; justify-content: space-between;">';
				print '<strong>Heure Sup 0%</strong>';
				print '<span style="display: flex; align-items: center;">';
					$heure_sup00 = $regul->heure_sup00 / 3600;
					$total_heure_sup00 += $regul->heure_sup00;
					if(GETPOST('action', 'aZ09') != 'ediths00' && !$disabled) {
						print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=ediths00&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a>';
					}
					if (GETPOST('action', 'aZ09') == 'ediths00' && !$disabled) {
						print '<input type="text" alt="Ajoutez ici les régulations des heures sup non majorées" title="Ajoutez ici les régulations des heures sup non majorées" 
						name="regulHeureSup00" id="regulHeureSup00" class="smallpad" placeholder="Regul" value="'.($heure_sup00 ? $heure_sup00 : '').'" 
						onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
						onblur="ValidateTimeDecimal(this);" 
						onchange="updateTotal_HeureSup00('.$nb_jour.', '.$num_first_day.');">';

						print '<input type="submit" class="button button-save" name="saveediths00" value="'.$langs->trans("Save").'">';
						print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
					}
					else {
						print '<input disabled type="text" alt="Ajoutez ici les régulations des heures sup non majorées" title="Ajoutez ici les régulations des heures sup non majorées" 
						name="regulHeureSup00" id="regulHeureSup00" class="smallpad" placeholder="Regul" value="'.($heure_sup00 ? $heure_sup00 : '').'" 
						onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
						onblur="ValidateTimeDecimal(this);" 
						onchange="updateTotal_HeureSup00('.$nb_jour.', '.$num_first_day.');">';
					}
				print '</span>';
			print '</div>';
		print '</td>';

		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			$heure_sup00 = '';
			if ($arraySilae['heure_sup00'][$dayinloopfromfirstdaytoshow] > 0) {
				$heure_sup00 = $arraySilae['heure_sup00'][$dayinloopfromfirstdaytoshow] / 3600;
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['heure_sup00'][$dayinloopfromfirstdaytoshow] > 0) {
				$total_heure_sup00 += $arraySilae['heure_sup00'][$dayinloopfromfirstdaytoshow];
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
				print '<input disabled type="text" style="border: 1px solid grey;" class="center smallpadd heure_sup00_'.$idw.'" size="2" id="heure_sup00['.$idw.']" 
				name="heure_sup00['.$idw.']" value="'.$heure_sup00.'" cols="2"  maxlength="5" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
				onblur="ValidateTimeDecimal(this);" 
				onchange="updateTotal_HeureSup00('.$nb_jour.', '.$num_first_day.');"'.($disabled ? ' disabled' : '').'/>';
			}
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($total_heure_sup00 != 0 ? 'noNull' : '').'" id="totalHeureSup00">'.($total_heure_sup00 / 3600).' HS00</div></td>';
	print '</tr>';

	// Heure Sup 25%
	print '<tr class="nostrong">';
		print '<td colspan="2" class="fixed">';
			print '<div style="display: flex; align-items: center; justify-content: space-between;">';
				print '<strong>Heure Sup 25%</strong>';
				print '<span style="display: flex; align-items: center;">';
					$heure_sup25 = $regul->heure_sup25 / 3600;
					$total_heure_sup25 += $regul->heure_sup25;
					if(GETPOST('action', 'aZ09') != 'ediths25' && !$disabled) {
						print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=ediths25&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a>';
					}
					if (GETPOST('action', 'aZ09') == 'ediths25' && !$disabled) {
						print '<input type="text" alt="Ajoutez ici les régulations des heures sup de 25%" title="Ajoutez ici les régulations des heures sup de 25%" 
						name="regulHeureSup25" id="regulHeureSup25" class="smallpad" placeholder="Regul" value="'.($heure_sup25 ? $heure_sup25 : '').'" 
						onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
						onblur="ValidateTimeDecimal(this);" 
						onchange="updateTotal_HeureSup25('.$nb_jour.', '.$num_first_day.');">';

						print '<input type="submit" class="button button-save" name="saveediths25" value="'.$langs->trans("Save").'">';
						print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
					}
					else {
						print '<input disabled type="text" alt="Ajoutez ici les régulations des heures sup de 25%" title="Ajoutez ici les régulations des heures sup de 25%" 
						name="regulHeureSup25" id="regulHeureSup25" class="smallpad" placeholder="Regul" value="'.($heure_sup25 ? $heure_sup25 : '').'" 
						onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
						onblur="ValidateTimeDecimal(this);" 
						onchange="updateTotal_HeureSup25('.$nb_jour.', '.$num_first_day.');">';
					}
				print '</span>';
			print '</div>';
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			$heure_sup25 = '';
			if ($arraySilae['heure_sup25'][$dayinloopfromfirstdaytoshow] > 0) {
				$heure_sup25 = $arraySilae['heure_sup25'][$dayinloopfromfirstdaytoshow] / 3600;
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['heure_sup25'][$dayinloopfromfirstdaytoshow] > 0) {
				$total_heure_sup25 += $arraySilae['heure_sup25'][$dayinloopfromfirstdaytoshow];
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
				print '<input disabled type="text" style="border: 1px solid grey;" class="center smallpadd heure_sup25_'.$idw.'" size="2" id="heure_sup25['.$idw.']" 
				name="heure_sup25['.$idw.']" value="'.$heure_sup25.'" cols="2"  maxlength="5" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
				onblur="ValidateTimeDecimal(this);" 
				onchange="updateTotal_HeureSup25('.$nb_jour.', '.$num_first_day.');"'.($disabled ? ' disabled' : '').'/>';
			}
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($total_heure_sup25 != 0 ? 'noNull' : '').'" id="totalHeureSup25">'.($total_heure_sup25 / 3600).' HS25</div></td>';
	print '</tr>';

	// Heure Sup 50%
	print '<tr class="nostrong">';
		print '<td colspan="2" class="fixed">';
			print '<div style="display: flex; align-items: center; justify-content: space-between;">';
				print '<strong>Heure Sup 50%</strong>';
				print '<span style="display: flex; align-items: center;">';
					$heure_sup50 = $regul->heure_sup50 / 3600;
					$total_heure_sup50 += $regul->heure_sup50;
					if(GETPOST('action', 'aZ09') != 'ediths50' && !$disabled) {
						print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=ediths50&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a>';
					}
					if (GETPOST('action', 'aZ09') == 'ediths50' && !$disabled) {
						print '<input type="text" alt="Ajoutez ici les régulations des heures sup de 50%" title="Ajoutez ici les régulations des heures sup de 50%" 
						name="regulHeureSup50" id="regulHeureSup50" class="smallpad" placeholder="Regul" value="'.($heure_sup50 ? $heure_sup50 : '').'" 
						onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
						onblur="ValidateTimeDecimal(this);" 
						onchange="updateTotal_HeureSup50('.$nb_jour.', '.$num_first_day.');">';

						print '<input type="submit" class="button button-save" name="saveediths50" value="'.$langs->trans("Save").'">';
						print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
					}
					else {
						print '<input disabled type="text" alt="Ajoutez ici les régulations des heures sup de 50%" title="Ajoutez ici les régulations des heures sup de 50%" 
						name="regulHeureSup50" id="regulHeureSup50" class="smallpad" placeholder="Regul" value="'.($heure_sup50 ? $heure_sup50 : '').'" 
						onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
						onblur="ValidateTimeDecimal(this);" 
						onchange="updateTotal_HeureSup50('.$nb_jour.', '.$num_first_day.');">';
					}
				print '</span>';
			print '</div>';
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			$heure_sup50 = '';
			if ($arraySilae['heure_sup50'][$dayinloopfromfirstdaytoshow] > 0) {
				$heure_sup50 = $arraySilae['heure_sup50'][$dayinloopfromfirstdaytoshow] / 3600;
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['heure_sup50'][$dayinloopfromfirstdaytoshow] > 0) {
				$total_heure_sup50 += $arraySilae['heure_sup50'][$dayinloopfromfirstdaytoshow];
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';

			if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
				print '<input disabled type="text" style="border: 1px solid grey;" class="center smallpadd heure_sup50_'.$idw.'" size="2" id="heure_sup50['.$idw.']" 
				name="heure_sup50['.$idw.']" value="'.$heure_sup50.'" cols="2"  maxlength="5" 
				onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
				onblur="ValidateTimeDecimal(this);" 
				onchange="updateTotal_HeureSup50('.$nb_jour.', '.$num_first_day.');"'.($disabled ? ' disabled' : '').'/>';
			}
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($total_heure_sup50 != 0 ? 'noNull' : '').'" id="totalHeureSup50">'.($total_heure_sup50 / 3600).' HS50</div></td>';
	print '</tr>';

	// Heure Sup 50% HT
	if($conf->global->HEURE_SUP_SUPERIOR_HEURE_MAX_SEMAINE){
		print '<tr class="nostrong">';
			print '<td colspan="2" class="fixed">';
				print '<div style="display: flex; align-items: center; justify-content: space-between;">';
					print '<strong>Heure Sup 50% HT</strong>';
					print '<span style="display: flex; align-items: center;">';
						$heure_sup50ht = $regul->heure_sup50ht / 3600;
						$total_heure_sup50ht += $regul->heure_sup50ht;
						if(GETPOST('action', 'aZ09') != 'ediths50ht' && !$disabled) {
							print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=ediths50ht&token='.newToken().'">'.img_edit($langs->trans("Edit")).'</a>';
						}
						if (GETPOST('action', 'aZ09') == 'ediths50ht' && !$disabled) {
							print '<input type="text" alt="Ajoutez ici les régulations des heures sup de 50% HT" title="Ajoutez ici les régulations des heures sup de 50% HT" 
							name="regulHeureSup50HT" id="regulHeureSup50HT" class="smallpad" placeholder="Regul" value="'.($heure_sup50ht ? $heure_sup50ht : '').'" 
							onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
							onblur="ValidateTimeDecimal(this);" 
							onchange="updateTotal_HeureSup50HT('.$nb_jour.', '.$num_first_day.');">';

							print '<input type="submit" class="button button-save" name="saveediths50ht" value="'.$langs->trans("Save").'">';
							print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
						}
						else {
							print '<input disabled type="text" alt="Ajoutez ici les régulations des heures sup de 50% HT" title="Ajoutez ici les régulations des heures sup de 50% HT" 
							name="regulHeureSup50HT" id="regulHeureSup50HT" class="smallpad" placeholder="Regul" value="'.($heure_sup50ht ? $heure_sup50ht : '').'" 
							onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" 
							onblur="ValidateTimeDecimal(this);" 
							onchange="updateTotal_HeureSup50HT('.$nb_jour.', '.$num_first_day.');">';
						}
					print '</span>';
				print '</div>';
			print '</td>';
			for ($idw = 0; $idw < $nb_jour; $idw++) {
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
				$keysuffix = '['.$idw.']';

				if($idw > 0 && $idw == $num_first_day){
					print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
				}

				$heure_sup50ht = '';
				if ($arraySilae['heure_sup50ht'][$dayinloopfromfirstdaytoshow] > 0) {
					$heure_sup50ht = $arraySilae['heure_sup50ht'][$dayinloopfromfirstdaytoshow] / 3600;
				}

				if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['heure_sup50ht'][$dayinloopfromfirstdaytoshow] > 0) {
					$total_heure_sup50ht += $arraySilae['heure_sup50ht'][$dayinloopfromfirstdaytoshow];
				}

				print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';

				if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
					print '<input disabled type="text" style="border: 1px solid grey;" class="center smallpadd heure_sup50ht_'.$idw.'" size="2" id="heure_sup50ht['.$idw.']" 
					name="heure_sup50ht['.$idw.']" value="'.$heure_sup50ht.'" cols="2"  maxlength="5" 
					onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
					onblur="ValidateTimeDecimal(this);" 
					onchange="updateTotal_HeureSup50HT('.$nb_jour.', '.$num_first_day.');"'.($disabled ? ' disabled' : '').'/>';
				}
				print '</td>';
			}
			print '<td class="liste_total center fixed"><div class="'.($total_heure_sup50ht != 0 ? 'noNull' : '').'" id="totalHeureSup50HT">'.($total_heure_sup50ht / 3600).' HS50</div></td>';
		print '</tr>';
	}

	// Heure Nuit
	print '<tr class="nostrong">';
		print '<td colspan="2" class="fixed"><strong>Heure de nuit</strong>';
		$heure_nuit50 = $regul->heure_nuit_50 / 3600;
		$heure_nuit75 = $regul->heure_nuit_75 / 3600;
		$heure_nuit100 = $regul->heure_nuit_100 / 3600;
		print '<input type="text" alt="Ajoutez ici les heures de nuit à 100%" title="Ajoutez ici les heures de nuit à 100%" name="HeureNuit100" id="HeureNuit100" class="smallpad" placeholder="100%"value="'.($heure_nuit100 ? $heure_nuit100 : '').'" onkeypress="return regexEvent_TS(this,event,\'timeChar\')" onblur="ValidateTimeDecimal(this);"'.($disabled ? ' disabled' : '').'>';
		print '<input type="text" alt="Ajoutez ici les heures de nuit à 75%" title="Ajoutez ici les heures de nuit à 75%" name="HeureNuit75" id="HeureNuit75" class="smallpad" placeholder="75%"value="'.($heure_nuit75 ? $heure_nuit75 : '').'" onkeypress="return regexEvent_TS(this,event,\'timeChar\')" onblur="ValidateTimeDecimal(this);"'.($disabled ? ' disabled' : '').'>';
		print '<input type="text" alt="Ajoutez ici les heures de nuit à 50%" title="Ajoutez ici les heures de nuit à 50%" name="HeureNuit50" id="HeureNuit50" class="smallpad" placeholder="50%" value="'.($heure_nuit50 ? $heure_nuit50 : '').'" onkeypress="return regexEvent_TS(this,event,\'timeChar\')" onblur="ValidateTimeDecimal(this);"'.($disabled ? ' disabled' : '').'>';
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			$heure_nuit = '';
			if ($arraySilae['heure_nuit'][$dayinloopfromfirstdaytoshow] > 0) {
				$heure_nuit = $arraySilae['heure_nuit'][$dayinloopfromfirstdaytoshow] / 3600;
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['heure_nuit'][$dayinloopfromfirstdaytoshow] > 0) {
				$total_heure_nuit+= $arraySilae['heure_nuit'][$dayinloopfromfirstdaytoshow];
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print '<input type="text" style="border: 1px solid grey;" class="center smallpadd heure_nuit_verif_'.$idw.'" size="2" id="heure_nuit_verif['.$idw.']" 
			name="heure_nuit_verif['.$idw.']" value="'.$heure_nuit.'" cols="2"  maxlength="5" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
			onblur="ValidateTimeDecimal(this);" 
			onchange="updateTotal_HeureNuit('.$nb_jour.', '.$num_first_day.');"'.($disabled ? ' disabled' : '').'/>';
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($total_heure_nuit != 0 ? 'noNull' : '').'" id="totalHeureNuit">'.($total_heure_nuit/3600).' HN</div></td>';
	print '</tr>';

	// Heure Route
	print '<tr class="nostrong">';
		print '<td colspan="2" class="fixed"><strong>Heure de route</strong>';
		$heure_route = $regul->heure_route / 3600;
		$total_heure_route += $regul->heure_route;
		print '<input type="text" alt="Ajoutez ici les régulations des heures de route" title="Ajoutez ici les régulations des heures de route" name="regulHeureRoute" id="regulHeureRoute" class="smallpad" placeholder="Regul" value="'.($heure_route ? $heure_route : '').'" onblur="ValidateTimeDecimal(this);" onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" onchange="updateTotal_HeureRoute('.$nb_jour.', '.$num_first_day.');"'.($disabled ? ' disabled' : '').'>';
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			$heure_route = '';
			if ($arraySilae['heure_route'][$dayinloopfromfirstdaytoshow] > 0) {
				$heure_route = $arraySilae['heure_route'][$dayinloopfromfirstdaytoshow] / 3600;
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['heure_route'][$dayinloopfromfirstdaytoshow] > 0) {
				$total_heure_route+= $arraySilae['heure_route'][$dayinloopfromfirstdaytoshow];
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print '<input type="text" style="border: 1px solid grey;" class="center smallpadd heure_route_'.$idw.'" size="2" id="heure_route['.$idw.']" 
			name="heure_route['.$idw.']" value="'.$heure_route.'" cols="2"  maxlength="5" 
			onkeypress="return regexEvent_TS(this,event,\'timeChar\')" 
			onblur="ValidateTimeDecimal(this);" 
			onchange="updateTotal_HeureRoute('.$nb_jour.', '.$num_first_day.');"'.($disabled ? ' disabled' : '').'/>';
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($total_heure_route != 0 ? 'noNull' : '').'" id="totalHeureRoute">'.($total_heure_route/3600).' HR</div></td>';
	print '</tr>';

	// Kilomètres
	print '<tr class="nostrong">';
		print '<td colspan="2" class="fixed"><strong>IK</strong>';
		$total_kilometres += $regul->kilometres;
		print '<input type="text" alt="Ajoutez ici les régulations des IK" title="Ajoutez ici les régulations des IK" name="regulKilometres" id="regulKilometres" class="smallpad" placeholder="Regul" value="'.($regul->kilometres ? $regul->kilometres : '').'" onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" onchange="updateTotal_Kilometres('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
		print '</td>';
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
			$keysuffix = '['.$idw.']';

			if($idw > 0 && $idw == $num_first_day){
				print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
			}

			if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['kilometres'][$dayinloopfromfirstdaytoshow] > 0) {
				$total_kilometres+= $arraySilae['kilometres'][$dayinloopfromfirstdaytoshow];
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $silae->showInputField('', 'kilometres', $arraySilae['kilometres'][$dayinloopfromfirstdaytoshow], 'style="min-width: auto;" onkeypress="return regexEvent_TS(this,event,\'timeChar\')" onchange="updateTotal_Kilometres('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : ''), $keysuffix);
			print '</td>';
		}
		print '<td class="liste_total center fixed"><div class="'.($total_kilometres != 0 ? 'noNull' : '').'" id="totalKilometres">'.$total_kilometres.' IK</div></td>';
	print '</tr>';

	// Indemnité de TT
	print '<tr class="nostrong">';
	print '<td colspan="2" class="fixed"><strong>Indemnité de TT</strong>';
	$nb_indemnite_tt += $regul->indemnite_tt;
	print '<input type="text" alt="Ajoutez ici les régulations des indemnité de TT" title="Ajoutez ici les régulations des indemnité de TT" name="regulIndemniteTT" id="regulIndemniteTT" class="smallpad" placeholder="Regul" value="'.($regul->indemnite_tt ? $regul->indemnite_tt : '').'" onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" onchange="updateTotal_IndemniteTT('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
	print '</td>';
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
		$keysuffix = '['.$idw.']';

		if($idw > 0 && $idw == $num_first_day){
			print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
		}

		if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['indemnite_tt'][$dayinloopfromfirstdaytoshow]) {
			$nb_indemnite_tt++;
		}

		print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
		print $silae->showInputField('', 'indemnite_tt', $arraySilae['indemnite_tt'][$dayinloopfromfirstdaytoshow], 'onkeypress="return regexEvent_TS(this,event,\'timeChar\')" onchange="updateTotal_IndemniteTT('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : ''), $keysuffix);
		print '</td>';
	}
	print '<td class="liste_total center fixed"><div class="'.($nb_indemnite_tt != 0 ? 'noNull' : '').'" id="totalIndemniteTT">'.$nb_indemnite_tt.' TT</div></td>';
	print '</tr>';

	// Repas
	print '<tr class="nostrong">';
	print '<td colspan="2" class="fixed"><strong>Repas</strong>';
	$nb_repas['R1'] += $regul->repas1;
	$nb_repas['R2'] += $regul->repas2;
	print '<input type="text" alt="Ajoutez ici les régulations des repas 1" title="Ajoutez ici les régulations des repas 1" name="regulRepas1" id="regulRepas1" class="smallpad" placeholder="R1" value="'.($regul->repas1 ? $regul->repas1 : '').'" onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" onchange="updateTotal_Repas('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
	print '<input type="text" alt="Ajoutez ici les régulations des repas 2" title="Ajoutez ici les régulations des repas 2" name="regulRepas2" id="regulRepas2" class="smallpad" placeholder="R2" value="'.($regul->repas2 ? $regul->repas2 : '').'" onkeypress="return regexEvent_TS(this,event,\'timeChar\', 1)" onchange="updateTotal_Repas('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : '').'>';
	print '</td>';
	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0
		$keysuffix = '['.$idw.']';

		if($idw > 0 && $idw == $num_first_day){
			print '<td style="min-width: 90px; border-right: 1px solid var(--colortopbordertitle1); border-left: 1px solid var(--colortopbordertitle1); border-bottom: none;" width="9%"></td>';
		}

		if($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['repas'][$dayinloopfromfirstdaytoshow] == 1) {
			$nb_repas['R1']++;
		}
		elseif($idw >= $num_first_day && ($idw <= $num_last_day || empty($num_last_day)) && $arraySilae['repas'][$dayinloopfromfirstdaytoshow] == 2) {
			$nb_repas['R2']++;
		}

		print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
		print $silae->showInputField('', 'repas', $arraySilae['repas'][$dayinloopfromfirstdaytoshow], 'style="min-width: auto;" onchange="updateTotal_Repas('.$nb_jour.', '.$num_first_day.')"'.($disabled ? ' disabled' : ''), $keysuffix);
		print '</td>';
	}
	$totalRepas = $nb_repas['R1']." R1<br>".$nb_repas['R2']." R2<br>";
	print '<td class="liste_total center fixed"><div class="'.($totalRepas != 0 ? 'noNull' : '').'" id="totalRepas">'.$totalRepas.'</div></td>';
	print '</tr>';
	print '<tbody>';
}

function dol_banner_tab_custom($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $onlybanner = 0, $morehtmlright = '', $buttonAction = '') {
	global $conf, $form, $user, $langs, $hookmanager, $action;
	
	$error = 0;
	
	$maxvisiblephotos = 1;
	$showimage = 1;
	$entity = (empty($object->entity) ? $conf->entity : $object->entity);
	$showbarcode = empty($conf->barcode->enabled) ? 0 : (empty($object->barcode) ? 0 : 1);
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) {
		$showbarcode = 0;
	}
	$modulepart = 'unknown';
	
	if ($object->element == 'societe' || $object->element == 'contact' || $object->element == 'product' || $object->element == 'ticket') {
		$modulepart = $object->element;
	} elseif ($object->element == 'member') {
		$modulepart = 'memberphoto';
	} elseif ($object->element == 'user') {
		$modulepart = 'userphoto';
	}
	
	if (class_exists("Imagick")) {
		if ($object->element == 'expensereport' || $object->element == 'propal' || $object->element == 'commande' || $object->element == 'facture' || $object->element == 'supplier_proposal') {
		$modulepart = $object->element;
		} elseif ($object->element == 'fichinter') {
		$modulepart = 'ficheinter';
		} elseif ($object->element == 'contrat') {
		$modulepart = 'contract';
		} elseif ($object->element == 'order_supplier') {
		$modulepart = 'supplier_order';
		} elseif ($object->element == 'invoice_supplier') {
		$modulepart = 'supplier_invoice';
		}
	}
	
	if ($object->element == 'product') {
		$width = 80;
		$cssclass = 'photowithmargin photoref';
		$showimage = $object->is_photo_available($conf->product->multidir_output[$entity]);
		$maxvisiblephotos = (isset($conf->global->PRODUCT_MAX_VISIBLE_PHOTO) ? $conf->global->PRODUCT_MAX_VISIBLE_PHOTO : 5);
		if ($conf->browser->layout == 'phone') {
		$maxvisiblephotos = 1;
		}
		if ($showimage) {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->show_photos('product', $conf->product->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, 0, $width, 0, '').'</div>';
		} else {
		if (!empty($conf->global->PRODUCT_NODISPLAYIFNOPHOTO)) {
			$nophoto = '';
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
		} else {    // Show no photo link
			$nophoto = '/public/theme/common/nophoto.png';
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass ? ' '.$cssclass : '').'" title="'.dol_escape_htmltag($langs->trans("UploadAnImageToSeeAPhotoHere", $langs->transnoentitiesnoconv("Documents"))).'" alt="No photo"'.($width ? ' style="width: '.$width.'px"' : '').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
		}
		}
	} elseif ($object->element == 'ticket') {
		$width = 80;
		$cssclass = 'photoref';
		$showimage = $object->is_photo_available($conf->ticket->multidir_output[$entity].'/'.$object->ref);
		$maxvisiblephotos = (isset($conf->global->TICKET_MAX_VISIBLE_PHOTO) ? $conf->global->TICKET_MAX_VISIBLE_PHOTO : 2);
		if ($conf->browser->layout == 'phone') {
		$maxvisiblephotos = 1;
		}
	
		if ($showimage) {
		$showphoto = $object->show_photos('ticket', $conf->ticket->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, $width, 0);
		if ($object->nbphoto > 0) {
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$showphoto.'</div>';
		} else {
			$showimage = 0;
		}
		}
		if (!$showimage) {
		if (!empty($conf->global->TICKET_NODISPLAYIFNOPHOTO)) {
			$nophoto = '';
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
		} else {    // Show no photo link
			$nophoto = img_picto('No photo', 'object_ticket');
			$morehtmlleft .= '<!-- No photo to show -->';
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
			$morehtmlleft .= $nophoto;
			$morehtmlleft .= '</div></div>';
		}
		}
	} else {
		if ($showimage) {
		if ($modulepart != 'unknown') {
			$phototoshow = '';
			// Check if a preview file is available
			if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick")) {
			$objectref = dol_sanitizeFileName($object->ref);
			$dir_output = (empty($conf->$modulepart->multidir_output[$entity]) ? $conf->$modulepart->dir_output : $conf->$modulepart->multidir_output[$entity])."/";
			if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice'))) {
				$subdir = get_exdir($object->id, 2, 0, 1, $object, $modulepart);
				$subdir .= ((!empty($subdir) && !preg_match('/\/$/', $subdir)) ? '/' : '').$objectref; // the objectref dir is not included into get_exdir when used with level=2, so we add it at end
			} else {
				$subdir = get_exdir($object->id, 0, 0, 1, $object, $modulepart);
			}
			if (empty($subdir)) {
				$subdir = 'errorgettingsubdirofobject'; // Protection to avoid to return empty path
			}
	
			$filepath = $dir_output.$subdir."/";
	
			$filepdf = $filepath.$objectref.".pdf";
			$relativepath = $subdir.'/'.$objectref.'.pdf';
	
			// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
			$fileimage = $filepdf.'_preview.png';
			$relativepathimage = $relativepath.'_preview.png';
	
			$pdfexists = file_exists($filepdf);
	
			// If PDF file exists
			if ($pdfexists) {
				// Conversion du PDF en image png si fichier png non existant
				if (!file_exists($fileimage) || (filemtime($fileimage) < filemtime($filepdf))) {
				if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS)) {    // If you experience trouble with pdf thumb generation and imagick, you can disable here.
					include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					$ret = dol_convert_file($filepdf, 'png', $fileimage, '0'); // Convert first page of PDF into a file _preview.png
					if ($ret < 0) {
					$error++;
					}
				}
				}
			}
	
			if ($pdfexists && !$error) {
				$heightforphotref = 80;
				if (!empty($conf->dol_optimize_smallscreen)) {
				$heightforphotref = 60;
				}
				// If the preview file is found
				if (file_exists($fileimage)) {
				$phototoshow = '<div class="photoref">';
				$phototoshow .= '<img height="'.$heightforphotref.'" class="photo photowithborder" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
				$phototoshow .= '</div>';
				}
			}
			} elseif (!$phototoshow) { // example if modulepart = 'societe' or 'photo'
			$phototoshow .= $form->showphoto($modulepart, $object, 0, 0, 0, 'photowithmargin photoref', 'small', 1, 0, $maxvisiblephotos);
			}
	
			if ($phototoshow) {
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">';
			$morehtmlleft .= $phototoshow;
			$morehtmlleft .= '</div>';
			}
		}
	
		if (empty($phototoshow)) {      // Show No photo link (picto of object)
			if ($object->element == 'action') {
			$width = 80;
			$cssclass = 'photorefcenter';
			$nophoto = img_picto('No photo', 'title_agenda');
			} else {
			$width = 14;
			$cssclass = 'photorefcenter';
			$picto = $object->picto;
			$prefix = 'object_';
			if ($object->element == 'project' && !$object->public) {
				$picto = 'project'; // instead of projectpub
			}
			if (strpos($picto, 'fontawesome_') !== false) {
				$prefix = '';
			}
			$nophoto = img_picto('No photo', $prefix.$picto);
			}
			$morehtmlleft .= '<!-- No photo to show -->';
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
			$morehtmlleft .= $nophoto;
			$morehtmlleft .= '</div></div>';
		}
		}
	}

	// Show barcode
	if ($showbarcode) {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$form->showbarcode($object, 100, 'photoref valignmiddle').'</div>';
	}
	
	if ($object->element == 'societe') {
		if (!empty($conf->use_javascript_ajax) && $user->hasRight('societe', 'creer') && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
		$morehtmlstatus .= ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
		} else {
		$morehtmlstatus .= $object->getLibStatut(6);
		}
	} elseif ($object->element == 'product') {
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Sell").') ';
		if (!empty($conf->use_javascript_ajax) && $user->hasRight('produit', 'creer') && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
		$morehtmlstatus .= ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
		} else {
		$morehtmlstatus .= '<span class="statusrefsell">'.$object->getLibStatut(6, 0).'</span>';
		}
		$morehtmlstatus .= ' &nbsp; ';
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Buy").') ';
		if (!empty($conf->use_javascript_ajax) && $user->hasRight('produit', 'creer') && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
		$morehtmlstatus .= ajax_object_onoff($object, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
		} else {
		$morehtmlstatus .= '<span class="statusrefbuy">'.$object->getLibStatut(6, 1).'</span>';
		}
	} elseif (in_array($object->element, array('facture', 'invoice', 'invoice_supplier', 'chargesociales', 'loan', 'tva', 'salary'))) {
		$tmptxt = $object->getLibStatut(6, $object->totalpaid);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) {
		$tmptxt = $object->getLibStatut(5, $object->totalpaid);
		}
		$morehtmlstatus .= $tmptxt;
	} elseif ($object->element == 'contrat' || $object->element == 'contract') {
		if ($object->statut == 0) {
		$morehtmlstatus .= $object->getLibStatut(5);
		} else {
		$morehtmlstatus .= $object->getLibStatut(4);
		}
	} elseif ($object->element == 'facturerec') {
		if ($object->frequency == 0) {
		$morehtmlstatus .= $object->getLibStatut(2);
		} else {
		$morehtmlstatus .= $object->getLibStatut(5);
		}
	} elseif ($object->element == 'project_task') {
		$object->fk_statut = 1;
		if ($object->progress > 0) {
		$object->fk_statut = 2;
		}
		if ($object->progress >= 100) {
		$object->fk_statut = 3;
		}
		$tmptxt = $object->getLibStatut(5);
		$morehtmlstatus .= $tmptxt; // No status on task
	} elseif (method_exists($object, 'getLibStatut')) { // Generic case
		$tmptxt = $object->getLibStatut(6);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) {
		$tmptxt = $object->getLibStatut(5);
		}
		$morehtmlstatus .= $tmptxt;
	}
	
	// Add if object was dispatched "into accountancy"
	if (isModEnabled('accounting') && in_array($object->element, array('bank', 'paiementcharge', 'facture', 'invoice', 'invoice_supplier', 'expensereport', 'payment_various'))) {
		// Note: For 'chargesociales', 'salaries'... this is the payments that are dispatched (so element = 'bank')
		if (method_exists($object, 'getVentilExportCompta')) {
		$accounted = $object->getVentilExportCompta();
		$langs->load("accountancy");
		$morehtmlstatus .= '</div><div class="statusref statusrefbis"><span class="opacitymedium">'.($accounted > 0 ? $langs->trans("Accounted") : $langs->trans("NotYetAccounted")).'</span>';
		}
	}
	
	// Add alias for thirdparty
	if (!empty($object->name_alias)) {
		$morehtmlref .= '<div class="refidno opacitymedium">'.dol_escape_htmltag($object->name_alias).'</div>';
	}
	
	// Add label
	if (in_array($object->element, array('product', 'bank_account', 'project_task'))) {
		if (!empty($object->label)) {
		$morehtmlref .= '<div class="refidno opacitymedium">'.$object->label.'</div>';
		}
	}
	
	// Show address and email
	if (method_exists($object, 'getBannerAddress') && !in_array($object->element, array('product', 'bookmark', 'ecm_directories', 'ecm_files'))) {
		$moreaddress = $object->getBannerAddress('refaddress', $object);
		if ($moreaddress) {
		$morehtmlref .= '<div class="refidno refaddress">';
		$morehtmlref .= $moreaddress;
		$morehtmlref .= '</div>';
		}
	}
	if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && ($conf->global->MAIN_SHOW_TECHNICAL_ID == '1' || preg_match('/'.preg_quote($object->element, '/').'/i', $conf->global->MAIN_SHOW_TECHNICAL_ID)) && !empty($object->id)) {
		$morehtmlref .= '<div style="clear: both;"></div>';
		$morehtmlref .= '<div class="refidno opacitymedium">';
		$morehtmlref .= $langs->trans("TechnicalID").': '.((int) $object->id);
		$morehtmlref .= '</div>';
	}
	
	$parameters=array('morehtmlref'=>$morehtmlref);
	$reshook = $hookmanager->executeHooks('formDolBanner', $parameters, $object, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif (empty($reshook)) {
		$morehtmlref .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$morehtmlref = $hookmanager->resPrint;
	}
	
	
	print '<div class="'.($onlybanner ? 'arearefnobottom ' : 'arearef ').'heightref valignmiddle centpercent">';
	print showrefnav_custom($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright, $buttonAction);
	print '</div>';
	print '<div class="underrefbanner clearboth"></div>';
}

function showrefnav_custom($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $morehtmlright = '', $buttonAction = '') {
	global $conf, $langs, $hookmanager, $extralanguages, $db;

	$ret = '';
	if (empty($fieldid)) {
	$fieldid = 'rowid';
	}
	if (empty($fieldref)) {
	$fieldref = 'ref';
	}

	// Preparing gender's display if there is one
	$addgendertxt = '';
	if (property_exists($object, 'gender') && !empty($object->gender)) {
	$addgendertxt = ' ';
	switch ($object->gender) {
		case 'man':
		$addgendertxt .= '<i class="fas fa-mars"></i>';
		break;
		case 'woman':
		$addgendertxt .= '<i class="fas fa-venus"></i>';
		break;
		case 'other':
		$addgendertxt .= '<i class="fas fa-transgender"></i>';
		break;
	}
	}

	/*
	$addadmin = '';
	if (property_exists($object, 'admin')) {
	if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
		$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
	} elseif (!empty($object->admin)) {
		$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
	}
	}*/

	// Add where from hooks
	if (is_object($hookmanager)) {
	$parameters = array('showrefnav' => true);
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
	$object->next_prev_filter .= $hookmanager->resPrint;
	}
	$previous_ref = $next_ref = '';
	if ($shownav) {
	//print "paramid=$paramid,morehtml=$morehtml,shownav=$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
	//$object->load_previous_next_ref((isset($object->next_prev_filter) ? $object->next_prev_filter : ''), $fieldid, $nodbprefix);
	$filter = " WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(te.ref, '_', 2), '_', -1) = ".explode('_', $object->ref)[1]." AND te.date_debut < '".$object->db->idate($object->date_debut)."' AND te.date_debut > '".$object->db->idate(dol_time_plus_duree($object->date_debut, -2, 'm'))."' AND CHAR_LENGTH(te.ref) = 16";
	$filter2 = " WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(te.ref, '_', 2), '_', -1) = ".explode('_', $object->ref)[1]." AND te.date_debut > '".$object->db->idate($object->date_debut)."' AND te.date_debut < '".$object->db->idate(dol_time_plus_duree($object->date_debut, 2, 'm'))."' AND CHAR_LENGTH(te.ref) = 16";
	$object->load_previous_next_ref_custom($filter, $filter2, $fieldid, $nodbprefix);

	$navurl = $_SERVER["PHP_SELF"];
	// Special case for project/task page
	if ($paramid == 'project_ref') {
		if (preg_match('/\/tasks\/(task|contact|note|document)\.php/', $navurl)) {     // TODO Remove this when nav with project_ref on task pages are ok
		$navurl = preg_replace('/\/tasks\/(task|contact|time|note|document)\.php/', '/tasks.php', $navurl);
		$paramid = 'ref';
		}
	}

	// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
	// accesskey is for Mac:               CTRL + key for all browsers
	$stringforfirstkey = $langs->trans("KeyboardShortcut");
	if ($conf->browser->name == 'chrome') {
		$stringforfirstkey .= ' ALT +';
	} elseif ($conf->browser->name == 'firefox') {
		$stringforfirstkey .= ' ALT + SHIFT +';
	} else {
		$stringforfirstkey .= ' CTL +';
	}

	$previous_ref = $object->ref_previous ? '<a accesskey="p" title="' . $stringforfirstkey . ' p" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_previous) . $moreparam . '"><i class="fa fa-chevron-left"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
	$next_ref = $object->ref_next ? '<a accesskey="n" title="' . $stringforfirstkey . ' n" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_next) . $moreparam . '"><i class="fa fa-chevron-right"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';
	}

	//print "xx".$previous_ref."x".$next_ref;
	$ret .= '<!-- Start banner content --><div style="vertical-align: middle">';

	// Right part of banner
	if ($morehtmlright) {
	$ret .= '<div class="inline-block floatleft">' . $morehtmlright . '</div>';
	}

	//$filter = 'and SUBSTR(te.ref, 1, 9) < "'.substr($object->ref, 0, 9).'" AND CHAR_LENGTH(te.ref) = 16 AND te.date_debut = "'.$object->db->idate($object->date_debut).'"';
	$filter = " WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(te.ref, '_', 2), '_', -1) < ".explode('_', $object->ref)[1]." AND SUBSTRING_INDEX(te.ref, '_', -1) = '".explode('_', $object->ref)[2]."' AND CHAR_LENGTH(te.ref) = 16";
	//$filter2 = 'and SUBSTR(te.ref, 1, 9) > "'.substr($object->ref, 0, 9).'" AND CHAR_LENGTH(te.ref) = 16 AND te.date_debut = "'.$object->db->idate($object->date_debut).'"';
	$filter2 = " WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(te.ref, '_', 2), '_', -1) > ".explode('_', $object->ref)[1]." AND SUBSTRING_INDEX(te.ref, '_', -1) = '".explode('_', $object->ref)[2]."' AND CHAR_LENGTH(te.ref) = 16";
	if($conf->global->FDT_ORDER_MATRICULE) {
		$object->load_previous_next_ref_custom($filter, $filter2, $fieldid, $nodbprefix);
	}
	else {
		$object->load_previous_next_ref_byusername($object->fk_user, $object->date_debut);
	}
	$previous_refByUser = $object->ref_previous ? '<a accesskey="p" title="' . $stringforfirstkey . ' p" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_previous) . $moreparam . '"><i class="fa fa-chevron-left"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
	$next_refByUser = $object->ref_next ? '<a accesskey="n" title="' . $stringforfirstkey . ' n" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_next) . $moreparam . '"><i class="fa fa-chevron-right"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';
	if ($previous_ref || $next_ref || $morehtml) {
		$ret .= '<div class="pagination paginationref"><ul class="right">';
		$ret .= '<li class="pagination">' . $previous_refByUser . '</li>';
		$ret .= img_object('', 'fontawesome_user_fas_#2f508b');
		$ret .= '<li class="pagination">' . $next_refByUser . '</li>';
		$ret .= '</ul></div>';
	}

	$user_static = new User($db);
	$user_static->fetch($object->fk_user);
	$user_extrafields = new Extrafields($db);
	if (empty($user_extrafields->attributes[$user_static->table_element]['loaded'])) {
		$user_extrafields->fetch_name_optionals_label($user_static->table_element);
	}
	if(isset($user_extrafields->attributes['user']['type']['etablissement'])) {
		//$filter = 'and SUBSTR(te.ref, 1, 9) < "'.substr($object->ref, 0, 9).'" AND CHAR_LENGTH(te.ref) = 16 AND te.date_debut = "'.$object->db->idate($object->date_debut).'"';
		$filter = " WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(te.ref, '_', 2), '_', -1) < ".explode('_', $object->ref)[1]." AND SUBSTRING_INDEX(te.ref, '_', -1) = '".explode('_', $object->ref)[2]."' AND CHAR_LENGTH(te.ref) = 16";
		//$filter2 = 'and SUBSTR(te.ref, 1, 9) > "'.substr($object->ref, 0, 9).'" AND CHAR_LENGTH(te.ref) = 16 AND te.date_debut = "'.$object->db->idate($object->date_debut).'"';
		$filter2 = " WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(te.ref, '_', 2), '_', -1) > ".explode('_', $object->ref)[1]." AND SUBSTRING_INDEX(te.ref, '_', -1) = '".explode('_', $object->ref)[2]."' AND CHAR_LENGTH(te.ref) = 16";
		if($conf->global->FDT_ORDER_MATRICULE) {
			$object->load_previous_next_ref_bymatricule($object->fk_user, $object->date_debut, $user_static->array_options['options_etablissement']);
		}
		else {
			$object->load_previous_next_ref_byusername($object->fk_user, $object->date_debut, $user_static->array_options['options_etablissement']);
		}
		$previous_refByUser = $object->ref_previous ? '<a accesskey="p" title="' . $stringforfirstkey . ' p" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_previous) . $moreparam . '"><i class="fa fa-chevron-left"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
		$next_refByUser = $object->ref_next ? '<a accesskey="n" title="' . $stringforfirstkey . ' n" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_next) . $moreparam . '"><i class="fa fa-chevron-right"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';
		if ($previous_ref || $next_ref || $morehtml) {
			$ret .= '<div class="pagination paginationref"><ul class="right">';
			$ret .= '<li class="pagination">' . $previous_refByUser . '</li>';
			$ret .= img_object('', 'fontawesome_fa-house-user_fas_#2f508b');
			$ret .= '<li class="pagination">' . $next_refByUser . '</li>';
			$ret .= '</ul></div>';
		}
	}

	if ($previous_ref || $next_ref || $morehtml) {
	$ret .= '<div class="pagination paginationref"><ul class="right">';
	}
	if ($morehtml) {
	$ret .= '<li class="noborder litext' . (($shownav && $previous_ref && $next_ref) ? ' clearbothonsmartphone' : '') . '">' . $morehtml . '</li>';
	}
	if ($shownav && ($previous_ref || $next_ref)) {
	$ret .= '<li class="pagination">' . $previous_ref . '</li>';
	$ret .= img_object('', 'fontawesome_calendar_fas_#2f508b');
	$ret .= '<li class="pagination">' . $next_ref . '</li>';
	}
	if ($previous_ref || $next_ref || $morehtml) {
	$ret .= '</ul></div>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('moreHtmlStatus', $parameters, $object); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
	$morehtmlstatus .= $hookmanager->resPrint;
	} else {
	$morehtmlstatus = $hookmanager->resPrint;
	}
	if ($morehtmlstatus) {
	$ret .= '<div class="statusref">' . $morehtmlstatus . '</div>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('moreHtmlRef', $parameters, $object); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
	$morehtmlref .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
	$morehtmlref = $hookmanager->resPrint;
	}

	// Left part of banner
	if ($morehtmlleft) {
	if ($conf->browser->layout == 'phone') {
		$ret .= '<!-- morehtmlleft --><div class="floatleft">' . $morehtmlleft . '</div>';
	} else {
		$ret .= '<!-- morehtmlleft --><div class="inline-block floatleft">' . $morehtmlleft . '</div>';
	}
	}

	//if ($conf->browser->layout == 'phone') $ret.='<div class="clearboth"></div>';
	$ret .= '<div class="inline-block floatleft valignmiddle marginbottomonly refid' . (($shownav && ($previous_ref || $next_ref)) ? ' refidpadding' : '') . '">';

	// For thirdparty, contact, user, member, the ref is the id, so we show something else
	if ($object->element == 'societe') {
	$ret .= dol_htmlentities($object->name);

	// List of extra languages
	$arrayoflangcode = array();
	if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE)) {
		$arrayoflangcode[] = $conf->global->PDF_USE_ALSO_LANGUAGE_CODE;
	}

	if (is_array($arrayoflangcode) && count($arrayoflangcode)) {
		if (!is_object($extralanguages)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/extralanguages.class.php';
		$extralanguages = new ExtraLanguages($object->db);
		}
		$extralanguages->fetch_name_extralanguages('societe');

		if (!empty($extralanguages->attributes['societe']['name'])) {
		$object->fetchValuesForExtraLanguages();

		$htmltext = '';
		// If there is extra languages
		foreach ($arrayoflangcode as $extralangcode) {
			$htmltext .= picto_from_langcode($extralangcode, 'class="pictoforlang paddingright"');
			if ($object->array_languages['name'][$extralangcode]) {
			$htmltext .= $object->array_languages['name'][$extralangcode];
			} else {
			$htmltext .= '<span class="opacitymedium">' . $langs->trans("SwitchInEditModeToAddTranslation") . '</span>';
			}
		}
		$ret .= '<!-- Show translations of name -->' . "\n";
		$ret .= $object->textwithpicto('', $htmltext, -1, 'language', 'opacitymedium paddingleft');
		}
	}
	} elseif ($object->element == 'member') {
	$ret .= $object->ref . '<br>';
	$fullname = $object->getFullName($langs);
	if ($object->morphy == 'mor' && $object->societe) {
		$ret .= dol_htmlentities($object->societe) . ((!empty($fullname) && $object->societe != $fullname) ? ' (' . dol_htmlentities($fullname) . $addgendertxt . ')' : '');
	} else {
		$ret .= dol_htmlentities($fullname) . $addgendertxt . ((!empty($object->societe) && $object->societe != $fullname) ? ' (' . dol_htmlentities($object->societe) . ')' : '');
	}
	} elseif (in_array($object->element, array('contact', 'user'))) {
	$ret .= dol_htmlentities($object->getFullName($langs)) . $addgendertxt;
	} elseif ($object->element == 'usergroup') {
	$ret .= dol_htmlentities($object->name);
	} elseif (in_array($object->element, array('action', 'agenda'))) {
	$ret .= $object->ref . '<br>' . $object->label;
	} elseif (in_array($object->element, array('adherent_type'))) {
	$ret .= $object->label;
	} elseif ($object->element == 'ecm_directories') {
	$ret .= '';
	} elseif ($fieldref != 'none') {
	$ret .= dol_htmlentities(!empty($object->$fieldref) ? $object->$fieldref : "");
	}
	if ($morehtmlref) {
	// don't add a additional space, when "$morehtmlref" starts with a HTML div tag
	if (substr($morehtmlref, 0, 4) != '<div') {
		$ret .= ' ';
	}

	$ret .= $morehtmlref;
	}

	$ret .= '</div>';

	$ret .= $buttonAction;

	$ret .= '</div><!-- End banner content -->';

	return $ret;
}

/**
 *    	Show html area with actions in messaging format.
 *      Note: Global parameter $param must be defined.
 *
 * 		@param	Conf		       $conf		   Object conf
 * 		@param	Translate	       $langs		   Object langs
 * 		@param	DoliDB		       $db			   Object db
 * 		@param	mixed			   $filterobj	   Filter on object Adherent|Societe|Project|Product|CommandeFournisseur|Dolresource|Ticket|... to list events linked to an object
 * 		@param	Contact		       $objcon		   Filter on object contact to filter events on a contact
 *      @param  int			       $noprint        Return string but does not output it
 *      @param  string		       $actioncode     Filter on actioncode
 *      @param  string             $donetodo       Filter on event 'done' or 'todo' or ''=nofilter (all).
 *      @param  array              $filters        Filter on other fields
 *      @param  string             $sortfield      Sort field
 *      @param  string             $sortorder      Sort order
 *  	@param  string		 $exclude_actioncode   Exclude one actioncode
 *      @return	string|void				           Return html part or void if noprint is 1
 */
function show_actions_messaging_custom($conf, $langs, $db, $filterobj, $objcon = '', $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC', $exclude_actioncode = array())
{
	global $user, $conf;
	global $form;

	global $param, $massactionbutton;

	dol_include_once('/comm/action/class/actioncomm.class.php');

	// Check parameters
	if (!is_object($filterobj) && !is_object($objcon)) {
		dol_print_error('', 'BadParameter');
	}

	$histo = array();
	$numaction = 0;
	$now = dol_now();

	$sortfield_list = explode(',', $sortfield);
	$sortfield_label_list = array('a.id' => 'id', 'a.datep' => 'dp', 'a.percent' => 'percent');
	$sortfield_new_list = array();
	foreach ($sortfield_list as $sortfield_value) {
		$sortfield_new_list[] = $sortfield_label_list[trim($sortfield_value)];
	}
	$sortfield_new = implode(',', $sortfield_new_list);

	if (isModEnabled('agenda')) {
		// Search histo on actioncomm
		if (is_object($objcon) && $objcon->id > 0) {
			$sql = "SELECT DISTINCT a.id, a.label as label,";
		} else {
			$sql = "SELECT a.id, a.label as label,";
		}
		$sql .= " a.datep as dp,";
		$sql .= " a.note as message,";
		$sql .= " a.datep2 as dp2,";
		$sql .= " a.percent as percent, 'action' as type,";
		$sql .= " a.fk_element, a.elementtype,";
		$sql .= " a.fk_contact,";
		$sql .= " a.email_from as msg_from,";
		$sql .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
		$sql .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql .= ", sp.lastname, sp.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", m.lastname, m.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj)) {
			$sql .= ", o.ref";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_action";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";

		$force_filter_contact = false;
		if (is_object($objcon) && $objcon->id > 0) {
			$force_filter_contact = true;
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm_resources as r ON a.id = r.fk_actioncomm";
			$sql .= " AND r.element_type = '".$db->escape($objcon->table_element)."' AND r.fk_element = ".((int) $objcon->id);
		}

		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."element_resources as er";
			$sql .= " ON er.resource_type = 'dolresource'";
			$sql .= " AND er.element_id = a.id";
			$sql .= " AND er.resource_id = ".((int) $filterobj->id);
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", ".MAIN_DB_PREFIX."adherent as m";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", ".MAIN_DB_PREFIX."product as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", ".MAIN_DB_PREFIX."ticket as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", ".MAIN_DB_PREFIX."bom_bom as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", ".MAIN_DB_PREFIX."contrat as o";
		} elseif (is_object($filterobj) && !empty($filterobj->table_element)) {
			$sql .= ", ".MAIN_DB_PREFIX.$filterobj->table_element." as o";
		}

		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		if ($force_filter_contact === false) {
			if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur')) && $filterobj->id) {
				$sql .= " AND a.fk_soc = ".((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Project' && $filterobj->id) {
				$sql .= " AND a.fk_project = ".((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
				$sql .= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'order_supplier'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'product'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'ticket'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'bom'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'contract'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			}  elseif (is_object($filterobj) && !empty($filterobj->element)) {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = '".$filterobj->element.(property_exists($filterobj, 'module') ? '@'.$filterobj->module : '')."'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			}
		}

		// Condition on actioncode
		if (!empty($actioncode)) {
			if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
				if ($actioncode == 'AC_NON_AUTO') {
					$sql .= " AND c.type != 'systemauto'";
				} elseif ($actioncode == 'AC_ALL_AUTO') {
					$sql .= " AND c.type = 'systemauto'";
				} else {
					if ($actioncode == 'AC_OTH') {
						$sql .= " AND c.type != 'systemauto'";
					} elseif ($actioncode == 'AC_OTH_AUTO') {
						$sql .= " AND c.type = 'systemauto'";
					}
				}
			} else {
				if ($actioncode == 'AC_NON_AUTO') {
					$sql .= " AND c.type != 'systemauto'";
				} elseif ($actioncode == 'AC_ALL_AUTO') {
					$sql .= " AND c.type = 'systemauto'";
				} else {
					$sql .= " AND c.code = '".$db->escape($actioncode)."'";
				}
			}
		}
		if ($donetodo == 'todo') {
			$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
		} elseif ($donetodo == 'done') {
			$sql .= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
		}
		if (is_array($filters) && $filters['search_agenda_label']) {
			$sql .= natural_search('a.label', $filters['search_agenda_label']);
		}
		if (!empty($exclude_actioncode)) {
			$sql .= " AND c.code NOT IN (".implode(',', $exclude_actioncode).")";
		}
	}

	// Add also event from emailings. TODO This should be replaced by an automatic event ? May be it's too much for very large emailing.
	if (isModEnabled('mailing') && !empty($objcon->email)
		&& (empty($actioncode) || $actioncode == 'AC_OTH_AUTO' || $actioncode == 'AC_EMAILING')) {
		$langs->load("mails");

		$sql2 = "SELECT m.rowid as id, m.titre as label, mc.date_envoi as dp, mc.date_envoi as dp2, '100' as percent, 'mailing' as type";
		$sql2 .= ", null as fk_element, '' as elementtype, null as contact_id";
		$sql2 .= ", 'AC_EMAILING' as acode, '' as alabel, '' as apicto";
		$sql2 .= ", u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname"; // User that valid action
		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj)) {
			$sql2 .= ", '' as ref";
		}
		$sql2 .= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u";
		$sql2 .= " WHERE mc.email = '".$db->escape($objcon->email)."'"; // Search is done on email.
		$sql2 .= " AND mc.statut = 1";
		$sql2 .= " AND u.rowid = m.fk_user_valid";
		$sql2 .= " AND mc.fk_mailing=m.rowid";
	}

	if (!empty($sql) && !empty($sql2)) {
		$sql = $sql." UNION ".$sql2;
	} elseif (empty($sql) && !empty($sql2)) {
		$sql = $sql2;
	}

	// TODO Add limit in nb of results
	if ($sql) {	// May not be defined if module Agenda is not enabled and mailing module disabled too
		$sql .= $db->order($sortfield_new, $sortorder);

		dol_syslog("function.lib::show_actions_messaging", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($obj->type == 'action') {
					$contactaction = new ActionComm($db);
					$contactaction->id = $obj->id;
					$result = $contactaction->fetchResources();
					if ($result < 0) {
						dol_print_error($db);
						setEventMessage("actions.lib::show_actions_messaging Error fetch ressource", 'errors');
					}

					//if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
					//elseif ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
					$tododone = '';
					if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && $obj->dp > $now)) {
						$tododone = 'todo';
					}

					$histo[$numaction] = array(
						'type'=>$obj->type,
						'tododone'=>$tododone,
						'id'=>$obj->id,
						'datestart'=>$db->jdate($obj->dp),
						'dateend'=>$db->jdate($obj->dp2),
						'note'=>$obj->label,
						'message'=>$obj->message,
						'percent'=>$obj->percent,

						'userid'=>$obj->user_id,
						'login'=>$obj->user_login,
						'userfirstname'=>$obj->user_firstname,
						'userlastname'=>$obj->user_lastname,
						'userphoto'=>$obj->user_photo,
						'msg_from'=>$obj->msg_from,

						'contact_id'=>$obj->fk_contact,
						'socpeopleassigned' => $contactaction->socpeopleassigned,
						'lastname' => (empty($obj->lastname) ? '' : $obj->lastname),
						'firstname' => (empty($obj->firstname) ? '' : $obj->firstname),
						'fk_element'=>$obj->fk_element,
						'elementtype'=>$obj->elementtype,
						// Type of event
						'acode'=>$obj->acode,
						'alabel'=>$obj->alabel,
						'libelle'=>$obj->alabel, // deprecated
						'apicto'=>$obj->apicto
					);
				} else {
					$histo[$numaction] = array(
						'type'=>$obj->type,
						'tododone'=>'done',
						'id'=>$obj->id,
						'datestart'=>$db->jdate($obj->dp),
						'dateend'=>$db->jdate($obj->dp2),
						'note'=>$obj->label,
						'message'=>$obj->message,
						'percent'=>$obj->percent,
						'acode'=>$obj->acode,

						'userid'=>$obj->user_id,
						'login'=>$obj->user_login,
						'userfirstname'=>$obj->user_firstname,
						'userlastname'=>$obj->user_lastname,
						'userphoto'=>$obj->user_photo
					);
				}

				$numaction++;
				$i++;
			}
		} else {
			dol_print_error($db);
		}
	}

	// Set $out to show events
	$out = '';

	if (!isModEnabled('agenda')) {
		$langs->loadLangs(array("admin", "errors"));
		$out = info_admin($langs->trans("WarningModuleXDisabledSoYouMayMissEventHere", $langs->transnoentitiesnoconv("Module2400Name")), 0, 0, 'warning');
	}

	if (isModEnabled('agenda') || (isModEnabled('mailing') && !empty($objcon->email))) {
		$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		$formactions = new FormActions($db);

		$actionstatic = new ActionComm($db);
		$userstatic = new User($db);
		$contactstatic = new Contact($db);
		$userGetNomUrlCache = array();
		$contactGetNomUrlCache = array();

		$out .= '<div class="filters-container" >';
		$out .= '<form name="listactionsfilter" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$out .= '<input type="hidden" name="token" value="'.newToken().'">';

		if ($objcon && get_class($objcon) == 'Contact' &&
			(is_null($filterobj) || get_class($filterobj) == 'Societe')) {
			$out .= '<input type="hidden" name="id" value="'.$objcon->id.'" />';
		} else {
			$out .= '<input type="hidden" name="id" value="'.$filterobj->id.'" />';
		}
		if ($filterobj && get_class($filterobj) == 'Societe') {
			$out .= '<input type="hidden" name="socid" value="'.$filterobj->id.'" />';
		}

		$out .= "\n";

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder borderbottom centpercent">';

		$out .= '<tr class="liste_titre">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		$out .= getTitleFieldOfList('Date', 0, $_SERVER["PHP_SELF"], 'a.datep', '', $param, '', $sortfield, $sortorder, '')."\n";

		$out .= '<th class="liste_titre"><strong class="hideonsmartphone">'.$langs->trans("Search").' : </strong></th>';
		if ($donetodo) {
			$out .= '<th class="liste_titre"></th>';
		}
		$out .= '<th class="liste_titre">';
		$out .= '<span class="fas fa-square inline-block fawidth30" style=" color: #ddd;" title="'.$langs->trans("ActionType").'"></span>';
		//$out .= img_picto($langs->trans("Type"), 'type');
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : -1, 0, 0, 1, 'minwidth200imp');
		$out .= '</th>';
		$out .= '<th class="liste_titre maxwidth100onsmartphone">';
		$out .= '<input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'" placeholder="'.$langs->trans("Label").'">';
		$out .= '</th>';

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		$out .= '</tr>';


		$out .= '</table>';

		$out .= '</form>';
		$out .= '</div>';

		$out .= "\n";

		$out .= '<ul class="timeline">';

		if ($donetodo) {
			$tmp = '';
			if (get_class($filterobj) == 'Societe') {
				$tmp .= '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&socid='.$filterobj->id.'&status=done">';
			}
			$tmp .= ($donetodo != 'done' ? $langs->trans("ActionsToDoShort") : '');
			$tmp .= ($donetodo != 'done' && $donetodo != 'todo' ? ' / ' : '');
			$tmp .= ($donetodo != 'todo' ? $langs->trans("ActionsDoneShort") : '');
			//$out.=$langs->trans("ActionsToDoShort").' / '.$langs->trans("ActionsDoneShort");
			if (get_class($filterobj) == 'Societe') {
				$tmp .= '</a>';
			}
			$out .= getTitleFieldOfList($tmp);
		}


		//require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
		//$caction=new CActionComm($db);
		//$arraylist=$caction->liste_array(1, 'code', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE)?1:0), '', 1);

		$actualCycleDate = false;

		// Loop on each event to show it
		foreach ($histo as $key => $value) {
			$actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

			$actionstatic->type_picto = $histo[$key]['apicto'];
			$actionstatic->type_code = $histo[$key]['acode'];

			$url = DOL_URL_ROOT.'/comm/action/card.php?id='.$histo[$key]['id'];

			$tmpa = dol_getdate($histo[$key]['datestart'], false);
			if ($actualCycleDate !== $tmpa['year'].'-'.$tmpa['yday']) {
				$actualCycleDate = $tmpa['year'].'-'.$tmpa['yday'];
				$out .= '<!-- timeline time label -->';
				$out .= '<li class="time-label">';
				$out .= '<span class="timeline-badge-date">';
				$out .= dol_print_date($histo[$key]['datestart'], 'daytext', 'tzuserrel', $langs);
				$out .= '</span>';
				$out .= '</li>';
				$out .= '<!-- /.timeline-label -->';
			}


			$out .= '<!-- timeline item -->'."\n";
			$out .= '<li class="timeline-code-'.strtolower($actionstatic->code).'">';

			$out .= getTimelineIcon($actionstatic, $histo, $key);

			$out .= '<div class="timeline-item">'."\n";

			$out .= '<span class="timeline-header-action">';

			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a class="timeline-btn" href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$out .= $histo[$key]['id'];
				$out .= '</a> ';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1, 'valignmiddle').' ';
			}

			if ($user->hasRight('agenda', 'allactions', 'create') ||
				(($actionstatic->authorid == $user->id || $actionstatic->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))) {
				$out .= '<a class="timeline-btn" href="'.DOL_MAIN_URL_ROOT.'/comm/action/card.php?action=edit&token='.newToken().'&id='.$actionstatic->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'"><i class="fa fa-pencil" title="'.$langs->trans("Modify").'" ></i></a>';
			}

			$out .= '</span>';
			// Date
			$out .= '<span class="time"><i class="fa fa-clock-o valignmiddle"></i> <span class="valignmiddle">';
			$out .= dol_print_date($histo[$key]['datestart'], 'dayhour', 'tzuserrel');
			if ($histo[$key]['dateend'] && $histo[$key]['dateend'] != $histo[$key]['datestart']) {
				$tmpa = dol_getdate($histo[$key]['datestart'], true);
				$tmpb = dol_getdate($histo[$key]['dateend'], true);
				if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'hour', 'tzuserrel');
				} else {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'dayhour', 'tzuserrel');
				}
			}
			$late = 0;
			if ($histo[$key]['percent'] == 0 && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] == 0 && !$histo[$key]['datestart'] && $histo[$key]['dateend'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && $histo[$key]['dateend'] && $histo[$key]['dateend'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && !$histo[$key]['dateend'] && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($late) {
				$out .= img_warning($langs->trans("Late")).' ';
			}
			$out .= "</span></span>\n";

			// Ref
			$out .= '<h3 class="timeline-header">';

			// Author of event
			$out .= '<div class="messaging-author inline-block tdoverflowmax150 valignmiddle marginrightonly">';
			if ($histo[$key]['userid'] > 0) {
				if (!isset($userGetNomUrlCache[$histo[$key]['userid']])) { // is in cache ?
					$userstatic->fetch($histo[$key]['userid']);
					$userGetNomUrlCache[$histo[$key]['userid']] = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
				}
				$out .= $userGetNomUrlCache[$histo[$key]['userid']];
			} elseif (!empty($histo[$key]['msg_from']) && $actionstatic->code == 'TICKET_MSG') {
				if (!isset($contactGetNomUrlCache[$histo[$key]['msg_from']])) {
					if ($contactstatic->fetch(0, null, '', $histo[$key]['msg_from']) > 0) {
						$contactGetNomUrlCache[$histo[$key]['msg_from']] = $contactstatic->getNomUrl(-1, '', 16);
					} else {
						$contactGetNomUrlCache[$histo[$key]['msg_from']] = $histo[$key]['msg_from'];
					}
				}
				$out .= $contactGetNomUrlCache[$histo[$key]['msg_from']];
			}
			$out .= '</div>';

			// Title
			$libelle = '';
			$out .= ' <div class="messaging-title inline-block">';

			if (preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$out .= $langs->trans('TicketNewMessage');
			} elseif (preg_match('/^TICKET_MSG_PRIVATE/', $actionstatic->code)) {
				$out .= $langs->trans('TicketNewMessage').' <em>('.$langs->trans('Private').')</em>';
			} elseif (isset($histo[$key]['type'])) {
				if ($histo[$key]['type'] == 'action') {
					$transcode = $langs->transnoentitiesnoconv("Action".$histo[$key]['acode']);
					$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : $histo[$key]['alabel']);
					$libelle = $histo[$key]['note'];
					$actionstatic->id = $histo[$key]['id'];
					$out .= dol_escape_htmltag(dol_trunc($libelle, 120));
				} elseif ($histo[$key]['type'] == 'mailing') {
					$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
					$transcode = $langs->transnoentitiesnoconv("Action".$histo[$key]['acode']);
					$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : 'Send mass mailing');
					$out .= dol_escape_htmltag(dol_trunc($libelle, 120));
				} else {
					$libelle .= $histo[$key]['note'];
					$out .= dol_escape_htmltag(dol_trunc($libelle, 120));
				}
			}

			$out .= '</div>';

			$out .= '</h3>';

			if (!empty($histo[$key]['message'] && $histo[$key]['message'] != $libelle)
				&& $actionstatic->code != 'AC_TICKET_CREATE'
				&& $actionstatic->code != 'AC_TICKET_MODIFY'
			) {
				$out .= '<div class="timeline-body">';
				$out .= $histo[$key]['message'];
				$out .= '</div>';
			}

			// Timeline footer
			$footer = '';

			// Contact for this action
			if (isset($histo[$key]['socpeopleassigned']) && is_array($histo[$key]['socpeopleassigned']) && count($histo[$key]['socpeopleassigned']) > 0) {
				$contactList = '';
				foreach ($histo[$key]['socpeopleassigned'] as $cid => $Tab) {
					$contact = new Contact($db);
					$result = $contact->fetch($cid);

					if ($result < 0) {
						dol_print_error($db, $contact->error);
					}

					if ($result > 0) {
						$contactList .= !empty($contactList) ? ', ' : '';
						$contactList .= $contact->getNomUrl(1);
						if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
							if (!empty($contact->phone_pro)) {
								$contactList .= '('.dol_print_phone($contact->phone_pro).')';
							}
						}
					}
				}

				$footer .= $langs->trans('ActionOnContact').' : '.$contactList;
			} elseif (empty($objcon->id) && isset($histo[$key]['contact_id']) && $histo[$key]['contact_id'] > 0) {
				$contact = new Contact($db);
				$result = $contact->fetch($histo[$key]['contact_id']);

				if ($result < 0) {
					dol_print_error($db, $contact->error);
				}

				if ($result > 0) {
					$footer .= $contact->getNomUrl(1);
					if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
						if (!empty($contact->phone_pro)) {
							$footer .= '('.dol_print_phone($contact->phone_pro).')';
						}
					}
				}
			}

			$documents = getActionCommEcmList($actionstatic);
			if (!empty($documents)) {
				$footer .= '<div class="timeline-documents-container">';
				foreach ($documents as $doc) {
					$footer .= '<span id="document_'.$doc->id.'" class="timeline-documents" ';
					$footer .= ' data-id="'.$doc->id.'" ';
					$footer .= ' data-path="'.$doc->filepath.'"';
					$footer .= ' data-filename="'.dol_escape_htmltag($doc->filename).'" ';
					$footer .= '>';

					$filePath = DOL_DATA_ROOT.'/'.$doc->filepath.'/'.$doc->filename;
					$mime = dol_mimetype($filePath);
					$file = $actionstatic->id.'/'.$doc->filename;
					$thumb = $actionstatic->id.'/thumbs/'.substr($doc->filename, 0, strrpos($doc->filename, '.')).'_mini'.substr($doc->filename, strrpos($doc->filename, '.'));
					$doclink = dol_buildpath('document.php', 1).'?modulepart=actions&attachment=0&file='.urlencode($file).'&entity='.$conf->entity;
					$viewlink = dol_buildpath('viewimage.php', 1).'?modulepart=actions&file='.urlencode($thumb).'&entity='.$conf->entity;

					$mimeAttr = ' mime="'.$mime.'" ';
					$class = '';
					if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
						$class .= ' documentpreview';
					}

					$footer .= '<a href="'.$doclink.'" class="btn-link '.$class.'" target="_blank" rel="noopener noreferrer" '.$mimeAttr.' >';
					$footer .= img_mime($filePath).' '.$doc->filename;
					$footer .= '</a>';

					$footer .= '</span>';
				}
				$footer .= '</div>';
			}

			if (!empty($footer)) {
				$out .= '<div class="timeline-footer">'.$footer.'</div>';
			}

			$out .= '</div>'."\n"; // end timeline-item

			$out .= '</li>';
			$out .= '<!-- END timeline item -->';

			$i++;
		}

		$out .= "</ul>\n";

		if (empty($histo)) {
			$out .= '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
		}
	}

	if ($noprint) {
		return $out;
	} else {
		print $out;
	}
}

function formatValueForAgenda($type, $value, $object = null, $field_name = '') {
	global $conf;

	if ($type == 'date' || $type == 'datetime') {
		$res = dol_print_date($value);
	} elseif ($type == 'duration') {
		if($conf->global->FDT_DECIMAL_HOUR_FORMAT) {
			$res = ($value > 0 ? number_format($value / 3600, 2, '.', '') : '0.00');
		}
		else {
			$res = ($value > 0 ? convertSecondToTime($value, 'allhourmin') : '00:00');
		}
	} elseif ($type == 'price') {
		$res = price($value, 0, '', 1, 2, -1, 'auto'); 
	} elseif ($type == 'boolean') {
		$res = ($value == 1 ? 'Oui' : 'Non');
	} elseif (($type == 'int' || $type == 'integer') && $object && $field_name) {
		$res = ($object->fields[$field_name]['arrayofkeyval'][$value] ? $object->fields[$field_name]['arrayofkeyval'][$value] : '/');
	} elseif ($type == 'int' || $type == 'integer') {
		$res = ($value ? (int)$value : 0);
	} elseif ($type == 'double') {
		$res = (price2num($value, 2) ? price2num($value, 2) : 0);
	} elseif ($type == 'statut') {
		$res = $object->LibStatut($value, 2);
	} else {
		$res = ($value ? $value : '/');
	}

	return $res;
}