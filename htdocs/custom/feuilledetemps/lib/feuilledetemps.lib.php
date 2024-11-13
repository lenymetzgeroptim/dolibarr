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

	/*
	$head[$h][0] = dol_buildpath("/feuilledetemps/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

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
 * @param	string	   	$inc					Line output identificator (start to 0, then increased by recursive call)
 * @param	int			$firstdaytoshow			First day to show
 * @param	int			$lastdaytoshow			Last day to show
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   string		$parent					Id of parent task to show (0 to show all)
 * @param   Task[]		$lines					Array of lines (list of tasks but we will show only if we have a specific role on task)
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	string		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is assigned to me, 2=Enable add time only if tasks is assigned to me and hide others
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @param	array		$arrayfields		    Array of additional column
 * @param	Extrafields	$extrafields		    Object extrafields
 * @param	int			$nb_jour				Nombre de jour à afficher
 * @param 	int			$modify					1 si les cases sont modifiables, 0 sinon 
 * @param   string[]    $css					css pour la couleur des cases
 * @param   int 	 	$num_first_day			Numero du 1er jour du mois (0 si celui-ci n'est pas présent dans les dates affichées)
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and substasks
 */
function FeuilleDeTempsLinesPerWeek(&$inc, $firstdaytoshow, $lastdaytoshow, $fuser, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, &$isavailable, $oldprojectforbreak = 0, $arrayfields = array(), $extrafields = null, 
$nb_jour = 31, $modify = 1, $css = '', $num_first_day = 0, $typeDeplacement = 'none', $dayinloopfromfirstdaytoshow_array, $modifier_jour_conges,  
$temps_prec, $temps_suiv, $temps_prec_hs25, $temps_suiv_hs25, $temps_prec_hs50, $temps_suiv_hs50, $notes, $otherTime, $timeSpentMonth, $timeSpentWeek, $month_now, $timeHoliday, $heure_semaine, $heure_semaine_hs, $usertoprocess, $favoris = -1, $param = '')
{
	global $conf, $db, $user, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic, $object;
	
	$numlines = count($lines);
	$lastprojectid = 0;
	$workloadforid = array();
	$totalforeachday = array();
	$lineswithoutlevel0 = array();
	$u = 0;
	$total_hs25 = 0;
	$total_hs50 = 0;

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
					print '<input type="checkbox" '.($has_heure_nuit ? 'checked ' : '').'id="heure_nuit_chkb_'.$lines[$i]->id.'" name="heure_nuit_chkb"'.($disabledtask || !$modify ? ' disabled' : '').' onchange="CheckboxHeureChange(this, '.$lines[$i]->id.', '.$nb_jour.', '.$inc.', '.$num_first_day.')"><label for="heure_nuit_chkb_'.$lines[$i]->id.'"> dont Heures de nuit (21h/6h)</label></span>';
					print '<input type="checkbox" '.($has_port_epi ? 'checked ' : '').'id="port_epi_chkb_'.$lines[$i]->id.'" name="port_epi_chkb"'.($disabledtask || !$modify ? ' disabled' : '').' onchange="CheckboxHeureChange(this, '.$lines[$i]->id.', '.$nb_jour.', '.$inc.', '.$num_first_day.')"><label for="port_epi_chkb_'.$lines[$i]->id.'"> dont Port EPI respiratoire</label></span>';
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
					$modeinput = 'hours';
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
						$totalforeachday[$dayinloopfromfirstdaytoshow] += $dayWorkLoad;

						$alreadyspent = '';
						if ($dayWorkLoad > 0) {
							$alreadyspent = convertSecondToTime($dayWorkLoad, 'allhourmin');
						}
						if($idw >= $num_first_day) {
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
						if(!$modify || $disabledtask || ($user_conges && !$modifier_jour_conges && empty($alreadyspent))) {
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
						$tableCell .= ' onkeyup="updateTotal_TS(this, '.$idw.',\''.$modeinput.'\','.$inc.', '.$num_first_day.'); this.oldvalue = this.value; updateTotalWeek('.$hn_prec.', '.$hn_suiv.', \''.$weekNumber.'\', '.($timeHoliday[$weekNumber] ? $timeHoliday[$weekNumber] : 0).', '.$tmp_heure_semaine.');"';
						$tableCell .= ' onblur="regexEvent_TS(this,event,\''.$modeinput.'\'); validateTime(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.','.$temps.',\''.$typeDeplacement.'\', '.$tmp_heure_semaine_hs.', '.($usertoprocess->id == $user->id).'); updateTotal_TS(this, '.$idw.',\''.$modeinput.'\','.$inc.', '.$num_first_day.'); updateTotalWeek('.$hn_prec.', '.$hn_suiv.', \''.$weekNumber.'\', '.($timeHoliday[$weekNumber] ? $timeHoliday[$weekNumber] : 0).', '.$tmp_heure_semaine.'); validateTime_HS(this,'.$idw.','.$ecart_lundi.',\''.$modeinput.'\','.$nb_jour.','.$inc.','.$temps.','.$hs_25.','.$hs_50.', '.$tmp_heure_semaine_hs.');" />';

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

							if($idw >= $num_first_day) {
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

							if($idw >= $num_first_day) {
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
			if ($lines[$i]->id > 0) {				
				$ret = FeuilleDeTempsLinesPerWeek($inc, $firstdaytoshow, $lastdaytoshow, $fuser, $lines[$i]->id, ($parent == 0 ? $lineswithoutlevel0 : $lines), $level, $projectsrole, $tasksrole, $mine, $restricteditformytask, $isavailable, $oldprojectforbreak, $arrayfields, $extrafields, 
				$nb_jour, $modify, $css, $num_first_day, $typeDeplacement, $dayinloopfromfirstdaytoshow_array, $modifier_jour_conges, $temps_prec, $temps_suiv, 
				$temps_prec_hs25, $temps_suiv_hs25, $temps_prec_hs50, $temps_suiv_hs50, $notes, $otherTime, $timeSpentMonth, $timeSpentWeek, $month_now, $timeHoliday, $heure_semaine, $heure_semaine_hs, $usertoprocess, $favoris, $param);
				foreach ($ret as $key => $val) {
					$totalforeachday[$key] += $val;
				}
			}
			$level--;
		} 
	}

	return $totalforeachday;
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
 * @param	User|null	$usertoprocess			Restrict list to user if defined
 * @param   string[]    $css					css pour la couleur des cases
 * @param   int         $num_first_day			Numéro du 1er jour du mois (0 si celui-ci n'apparait pas sur les dates affichées)
 * @return  array								Tableau avec le nombre de chaque type de déplacement
 */
function FeuilleDeTempsDeplacement($firstdaytoshow, $lastdaytoshow, $nb_jour, $usertoprocess, $css, $num_first_day = null, $disabled, $addcolspan, $dayinloopfromfirstdaytoshow_array, $month_now) {
	global $conf, $db, $user, $langs, $form, $object;

	$deplacement = New Deplacement($db);
	$notes = $deplacement->fetchAllNotes($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);
	$regul = new Regul($db);
	$regul->fetchWithoutId(dol_time_plus_duree($firstdaytoshow, $num_first_day, 'd'), $usertoprocess->id);

	$array_deplacement = $deplacement->getAllDeplacements($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);
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

			if($idw >= $num_first_day && $array_deplacement['deplacement_ponctuel'][$dayinloopfromfirstdaytoshow]) {
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

			if($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_type_deplacement['d1']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_type_deplacement['d2']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 3) {
				$nb_type_deplacement['d3']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 4) {
				$nb_type_deplacement['d4']++;
			}
			elseif($idw >= $num_first_day && ($array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 5 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 8 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 9)) {
				$nb_type_deplacement['gd1']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 6) {
				$nb_type_deplacement['gd2']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 7) {
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

			if($idw >= $num_first_day && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_moyen_transport['VS']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_moyen_transport['A']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 3) {
				$nb_moyen_transport['T']++;
			}

			print '<td class="center hide'.$idw.($css[$dayinloopfromfirstdaytoshow] ? ' '.$css[$dayinloopfromfirstdaytoshow] : '').'">';
			print $deplacement->showInputField('', 'moyen_transport', $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow], ($disabled ? 'disabled ' : '').'onchange="updateTotal_MoyenTransport('.$nb_jour.', '.$num_first_day.'); deleteTypeDeplacementVS('.$idw.')"', $keysuffix, '', 1);
			print '</td>';
		}
		$totalMoyenTransport = $nb_moyen_transport['VS']." VS<br>".$nb_moyen_transport['A']." A<br>".$nb_moyen_transport['T']." T<br>";
		print '<td class="liste_total center fixed"><div class="'.($totalMoyenTransport != 0 ? 'noNull' : '').'" id="totalMoyenTransport">'.$totalMoyenTransport.'</div></td>';
	print '</tr>';
}


/**
 * Tableau de vérification (silae) d'une feuille de temps
 *
 * @param	int			$firstdaytoshow			First day to show
 * @param	int			$lastdaytoshow			Last day to show
 * @param	int			$nb_jour				Nombre de jour à afficher
 * @param	User|null	$usertoprocess			Restrict list to user if defined
 * @param   string[]    $css					css pour la couleur des cases
 * @param   int         $num_first_day			Numéro du 1er jour du mois (0 si celui-ci n'apparait pas sur les dates affichées)
 * @return  array								Tableau avec le nombre de chaque type de déplacement
 */
function FeuilleDeTempsVerification($firstdaytoshow, $lastdaytoshow, $nb_jour, $usertoprocess, $css, $css_holiday, $num_first_day, $disabled, $userInDeplacement = 0, $userInGrandDeplacement = 0, $dayinloopfromfirstdaytoshow_array) {
	global $conf, $db, $user, $langs, $form, $object;

	$silae = New Silae($db);
	$arraySilae = $silae->getAllSilae($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);
	$regul = new Regul($db);
	$regul->fetchWithoutId(dol_time_plus_duree($firstdaytoshow, $num_first_day, 'd'), $usertoprocess->id);
	$deplacement = New Deplacement($db);
	$notes = $deplacement->fetchAllNotes($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);

	$total_heure_sup00 = 0;
	$total_heure_sup25 = 0;
	$total_heure_sup50 = 0;
	$total_heure_nuit = 0;
	$total_heure_route = 0;
	$nb_repas = array('R1' => 0, 'R2' => 0);
	$total_kilometres = 0;
	$nb_indemnite_tt = 0;
	$nb_deplacement_ponctuel = 0;
	$nb_type_deplacement = array('d1' => 0, 'd2' => 0, 'd3' => 0, 'd4' => 0, 'gd1' => 0, 'gd2' => 0, 'dom' => 0);
	$nb_moyen_transport = array('VS' => 0, 'A' => 0, 'T' => 0);

	$array_deplacement = $deplacement->getAllDeplacements($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);

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

			if($idw >= $num_first_day && $array_deplacement['deplacement_ponctuel'][$dayinloopfromfirstdaytoshow]) {
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

			if($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_type_deplacement['d1']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_type_deplacement['d2']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 3) {
				$nb_type_deplacement['d3']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 4) {
				$nb_type_deplacement['d4']++;
			}
			elseif($idw >= $num_first_day && ($array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 5 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 8 || $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 9)) {
				$nb_type_deplacement['gd1']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 6) {
				$nb_type_deplacement['gd2']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['type_deplacement'][$dayinloopfromfirstdaytoshow] == 7) {
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

			if($idw >= $num_first_day && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 1) {
				$nb_moyen_transport['VS']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 2) {
				$nb_moyen_transport['A']++;
			}
			elseif($idw >= $num_first_day && $array_deplacement['moyen_transport'][$dayinloopfromfirstdaytoshow] == 3) {
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

			if($idw >= $num_first_day && $arraySilae['heure_sup00'][$dayinloopfromfirstdaytoshow] > 0) {
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

			if($idw >= $num_first_day && $arraySilae['heure_sup25'][$dayinloopfromfirstdaytoshow] > 0) {
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

			if($idw >= $num_first_day && $arraySilae['heure_sup50'][$dayinloopfromfirstdaytoshow] > 0) {
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

			if($idw >= $num_first_day && $arraySilae['heure_nuit'][$dayinloopfromfirstdaytoshow] > 0) {
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

			if($idw >= $num_first_day && $arraySilae['heure_route'][$dayinloopfromfirstdaytoshow] > 0) {
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

			if($idw >= $num_first_day && $arraySilae['kilometres'][$dayinloopfromfirstdaytoshow] > 0) {
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

		if($idw >= $num_first_day && $arraySilae['indemnite_tt'][$dayinloopfromfirstdaytoshow]) {
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

		if($idw >= $num_first_day && $arraySilae['repas'][$dayinloopfromfirstdaytoshow] == 1) {
			$nb_repas['R1']++;
		}
		elseif($idw >= $num_first_day && $arraySilae['repas'][$dayinloopfromfirstdaytoshow] == 2) {
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
	global $conf, $langs, $hookmanager, $extralanguages;

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
	$object->load_previous_next_ref_custom($filter, $filter2, $fieldid, $nodbprefix);
	$previous_refByUser = $object->ref_previous ? '<a accesskey="p" title="' . $stringforfirstkey . ' p" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_previous) . $moreparam . '"><i class="fa fa-chevron-left"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
	$next_refByUser = $object->ref_next ? '<a accesskey="n" title="' . $stringforfirstkey . ' n" class="classfortooltip" href="' . $navurl . '?' . $paramid . '=' . urlencode($object->ref_next) . $moreparam . '"><i class="fa fa-chevron-right"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';
	if ($previous_ref || $next_ref || $morehtml) {
		$ret .= '<div class="pagination paginationref"><ul class="right">';
		$ret .= '<li class="pagination">' . $previous_refByUser . '</li>';
		$ret .= img_object('', 'fontawesome_user_fas_#2f508b');
		$ret .= '<li class="pagination">' . $next_refByUser . '</li>';
		$ret .= '</ul></div>';
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
		$extralanguages = new ExtraLanguages($this->db);
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
		$ret .= $this->textwithpicto('', $htmltext, -1, 'language', 'opacitymedium paddingleft');
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
	if ($type == 'date' || $type == 'datetime') {
		$res = dol_print_date($value);
	} elseif ($type == 'duration') {
		$res = ($value > 0 ? convertSecondToTime($value) : '00:00');
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