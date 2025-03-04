<?php

/* Copyright (C) 2023  Lény METZGER  <leny-07@hotmail.fr>
 *
 * Need to have following variables defined:
 * $object
 * $action
 * $conf
 * $langs
 */

if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

// Dans le cas ou on clique sur "ENREGISTRER"
if ($conf->global->FDT_DISPLAY_COLUMN && $action == 'addtime' && GETPOST('formfilteraction') != 'listafterchangingselectedfields' && $massaction != 'validate1' && $massaction != 'validate2' && $massaction != 'verification' && $massaction != 'refus') {
	// Création de la feuille de temps au 1er enregistrement
	if($object->id == 0) {
		$object->ref = "FDT_".str_pad($usertoprocess->array_options['options_matricule'], 5, '0', STR_PAD_LEFT).'_'.dol_print_date($last_day_month, '%m%Y');
		$object->date_debut = $first_day_month;
		$object->date_fin = $last_day_month;
		$object->fk_user = $usertoprocess->id;
		$object->status = 0;

		$result = $object->create($user, 0);

		if($result <= 0) {
			setEventMessages("Impossible de créer la feuille de temps", null, 'errors');
			$error++;
		}
	}

	$timetoadd = (GETPOST('task') ? GETPOST('task')  :  array());
	$heure_nuit = (GETPOST('heure_nuit') ? GETPOST('heure_nuit')  :  array());
	$note = (GETPOST('note')  ? GETPOST('note')  : array());
	$fk_task = (GETPOST('fk_task')  ? GETPOST('fk_task')  : array());
	$site = (GETPOST('site')  ? GETPOST('site')  : array());
	
	// Temps de chaque semaine pour vérifier s'il y a moins de 35h enregistré
	//$timeDoneByWeekBefore = $timeSpentWeek; 
	$task = new extendedTask($db);
	$filter = ' AND ptt.element_date >= "'.substr($db->idate($firstdaytoshow), 0, 10).'" AND ptt.element_date <= "'.substr($db->idate($lastdaytoshow), 0, 10).'"';
	$timespent_month = $task->fetchAllTimeSpentByDate($usertoprocess, $filter);
	
	//foreach ($timetoadd as $day => $value) {     // Loop on each day
	foreach($dayinloopfromfirstdaytoshow_array as $day => $tmpday) {
		$is_day_anticipe = ($tmpday < $first_day_month ? 1 : 0);

		//foreach ($value as $cpt => $val) {         
		for($cpt = 0; $cpt < $conf->global->FDT_COLUMN_MAX_TASK_DAY; $cpt++) {
			$task = new ExtendedTask($db);
			$new_task = new ExtendedTask($db);
			$timespent = new TimeSpent($db);
			$heure_other = new Projet_task_time_other($db);

			$timespent_tmp = $timespent_month[$tmpday][$cpt];
			if($timespent_tmp->timespent_id > 0) {
				$resheure_other = $heure_other->fetchWithoutId($timespent_tmp->timespent_id); // $res contient l'id du Projet_task_time_other correspondant, si celui-ci existe
			}
			else {
				$resheure_other = 0;
			}
			$task_changed = 0;

			if($timespent_tmp->fk_task > 0) {
				$task->fetch($timespent_tmp->fk_task);
			}

			if($fk_task[$day][$cpt] > 0) {
				$new_task->fetch($fk_task[$day][$cpt]);
			}

			// if($note[$day][$cpt] !== null) {
			// 	var_dump('note : '.$day.' '.$cpt);
			// }
			// if($heure_nuit[$day][$cpt] !== null) {
			// 	var_dump('heure_nuit : '.$day.' '.$cpt);
			// }
			// if($timetoadd[$day][$cpt] !== null) {
			// 	var_dump('timetoadd : '.$day.' '.$cpt);
			// }
			// if($site[$day][$cpt] !== null) {
			// 	var_dump('site : '.$day.' '.$cpt);
			// }

			// Gestion des notes    
			if($note[$day][$cpt] !== null) {
				if ($note[$day][$cpt] != $timespent_tmp->timespent_note) {
					// Si le temps consommé existe déja et que tous les champs sont = null
					if($timespent_tmp->timespent_id > 0 && $timespent_tmp->timespent_duration == 0 && empty($note[$day][$cpt])){
						$timespent->fetch($timespent_tmp->timespent_id);
						$result = $timespent->delete($user);
					}
					// Si le temps consommé existe déja et qu'il y a au moins une modification
					elseif($timespent_tmp->timespent_id > 0 && $timespent_tmp->timespent_note != $note[$day][$cpt]){
						$timespent->fetch($timespent_tmp->timespent_id);
						$timespent->note = $note[$day][$cpt];
						$result = $timespent->update($user);
					}
					// Si le temps consommé n'existe pas et qu'il y a au moins un champ != null
					elseif (empty($timespent_tmp->timespent_id) && !empty($note[$day][$cpt]) && $fk_task[$day][$cpt] > 0) {
						$timespent = new TimeSpent($db);
						$timespent->fk_element = $fk_task[$day][$cpt];
						$timespent->elementtype = 'task';
						$timespent->element_date = $tmpday;
						$timespent->element_datehour = $tmpday;
						$timespent->fk_user = $usertoprocess->id;
						$timespent->note = $note[$day][$cpt];
						$timespent->datec = $db->idate($now);
						$timespent->thm = $usertoprocess->thm;

						$result = $timespent->create($user);

						$timespent_month[$tmpday][$cpt]->timespent_id = $result;
						$timespent_tmp = $timespent_month[$tmpday][$cpt];
					}

					if ($result < 0) {
						setEventMessages($task->error, $task->errors, 'errors');
						$error++;
						break;
					}
				}
			}



			// Gestion des temps    
			if($timetoadd[$day][$cpt] !== null) {
				//$updateoftaskdone = 0;
				$amountoadd = $timetoadd[$day][$cpt];
				$newduration = 0;

				// Formatage des heures
				if (!empty($amountoadd)) {
					if($conf->global->FDT_DECIMAL_HOUR_FORMAT) {
						$newduration += ($amountoadd * 3600);
					}
					else {
						$tmpduration = explode(':', $amountoadd);
						if (!empty($tmpduration[0])) {
							$newduration += ($tmpduration[0] * 3600);
						}
						if (!empty($tmpduration[1])) {
							$newduration += ($tmpduration[1] * 60);
						}
						if (!empty($tmpduration[2])) {
							$newduration += ($tmpduration[2]);
						}
					}
				}

				if($timespent_tmp->timespent_id > 0) $timespent->fetch($timespent_tmp->timespent_id);

				// Gestion des temps consommés
				if ((int)$timespent_tmp->timespent_duration != (int)$newduration || (int)$timespent->fk_element != (int)$fk_task[$day][$cpt]) {
					// Si le temps consommé existe déja et que tous les champs sont = null
					if($timespent_tmp->timespent_id > 0 && $newduration == 0 && empty($timespent_tmp->timespent_note)){
						// Agenda
						if($timespent_tmp->timespent_duration != $newduration) {
							$new_value = formatValueForAgenda('duration', $newduration);
							$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);

							if($is_day_anticipe){
								$modification .= ($old_value != $new_value ? '<li class="txt_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
							}
							elseif($object->id > 0){
								$modification .= ($old_value != $new_value ? '<li><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
							}
						}
						
						if($is_day_anticipe && $timespent_tmp->timespent_duration > 0) {
							$new_value = formatValueForAgenda('duration', 0);
							$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);
							$timespent->note = ' / Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.' sur '.$task->label.") : $old_value ➔ $new_value";
							$timespent->element_duration = 0;

							$result = $timespent->update($user);
						}
						elseif($resheure_other != 0 || (!empty($heure_nuit[$day][$cpt]) && (int)$fk_task[$day][$cpt] > 0)){
							$timespent->element_duration = 0;		
							$result = $timespent->update($user);
						}
						elseif(!$is_day_anticipe){		
							$result = $timespent->delete($user);
						}
					}
					// Si le temps consommé existe déja et qu'il y a au moins une modification
					else if($timespent_tmp->timespent_id > 0 && ($timespent_tmp->timespent_duration != $newduration || ($timespent->fk_element != $fk_task[$day][$cpt] && $fk_task[$day][$cpt] > 0))){
						// Agenda
						if($timespent->fk_element != $fk_task[$day][$cpt] && $fk_task[$day][$cpt] > 0) {
							$task_changed = 1;
							if($is_day_anticipe){
								if($timespent_tmp->timespent_duration > 0) {
									$new_value = formatValueForAgenda('duration', 0);
									$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);
									$modification .= '<li class="txt_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";
								}

								$new_value = formatValueForAgenda('duration', $newduration);
								$old_value = formatValueForAgenda('duration', 0);
								$modification .= '<li class="txt_before"><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";
							}
							elseif($object->id > 0){
								if($timespent_tmp->timespent_duration > 0) {
									$new_value = formatValueForAgenda('duration', 0);
									$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);
									$modification .= '<li><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";
								}

								$new_value = formatValueForAgenda('duration', $newduration);
								$old_value = formatValueForAgenda('duration', 0);
								$modification .= '<li><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";					
							}
						}
						elseif($timespent_tmp->timespent_duration != $newduration) {
							$new_value = formatValueForAgenda('duration', $newduration);
							$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);

							if($is_day_anticipe){
								$modification .= ($old_value != $new_value ? '<li class="txt_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
							}
							elseif($object->id > 0){
								$modification .= ($old_value != $new_value ? '<li><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
							}
						}
					
						if($timespent->fk_element != $fk_task[$day][$cpt] && $fk_task[$day][$cpt] > 0) {
							$tmpnote = $timespent->note;

							$res_del = $timespent->delete($user);

							$timespent = new TimeSpent($db);
							$timespent->fk_element = $fk_task[$day][$cpt];
							$timespent->elementtype = 'task';
							$timespent->element_date = $tmpday;
							$timespent->element_datehour = $tmpday;
							//$timespent->element_date_withhour = $this->timespent_withhour;
							$timespent->element_duration = $newduration;
							$timespent->fk_user = $usertoprocess->id;
							//$timespent->fk_product = $this->timespent_fk_product;
							$timespent->note = $tmpnote;
							if($is_day_anticipe) {
								$new_value = formatValueForAgenda('duration', 0);
								$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);
								$timespent->note .= ' / Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.' sur '.$task->label.") : $old_value ➔ $new_value";
								
								$new_value = formatValueForAgenda('duration', $newduration);
								$old_value = formatValueForAgenda('duration', 0);
								$timespent->note .= ' et Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.' sur '.$new_task->label.") : $old_value ➔ $new_value";
							}
							$timespent->datec = $db->idate($now);
							$timespent->thm = $usertoprocess->thm;

							$result = $timespent->create($user);

							if ($resheure_other > 0){
								$heure_other->fk_projet_task_time = $result;
								$result = $heure_other->update($user);
							}

							$timespent_month[$tmpday][$cpt]->timespent_id = $result;
							$timespent_tmp = $timespent_month[$tmpday][$cpt];
						}
						elseif($timespent_tmp->timespent_duration != $newduration) {
							if($is_day_anticipe) {
								$new_value = formatValueForAgenda('duration', $newduration);
								$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);
								$timespent->note .= ' / Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.' sur '.$new_task->label.") : $old_value ➔ $new_value";
							}
							$timespent->element_duration = $newduration;

							$result = $timespent->update($user);
						}
						
					}
					// Si le temps consommé n'existe pas et qu'il y a au moins un champ != null
					elseif (empty($timespent_tmp->timespent_id) && $newduration != 0 && $fk_task[$day][$cpt] > 0){
						if($timespent_tmp->timespent_duration != $newduration) {
							$new_value = formatValueForAgenda('duration', $newduration);
							$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);

							if($is_day_anticipe){
								$modification .= ($old_value != $new_value ? '<li class="txt_before"><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
							}
							elseif($object->id > 0){
								$modification .= ($old_value != $new_value ? '<li><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
							}
						}

						$timespent = new TimeSpent($db);
						$timespent->fk_element = $fk_task[$day][$cpt];
						$timespent->elementtype = 'task';
						$timespent->element_date = $tmpday;
						$timespent->element_datehour = $tmpday;
						//$timespent->element_date_withhour = $this->timespent_withhour;
						$timespent->element_duration = $newduration;
						$timespent->fk_user = $usertoprocess->id;
						//$timespent->fk_product = $this->timespent_fk_product;
						if($is_day_anticipe && $newduration > 0) {
							$new_value = formatValueForAgenda('duration', $newduration);
							$old_value = formatValueForAgenda('duration', 0);
							$timespent->note .= ' / Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.' sur '.$new_task->label.") : $old_value ➔ $new_value";
						}
						$timespent->datec = $db->idate($now);
						$timespent->thm = $usertoprocess->thm;

						$result = $timespent->create($user);

						$timespent_month[$tmpday][$cpt]->timespent_id = $result;
						$timespent_tmp = $timespent_month[$tmpday][$cpt];
					}

					if ($result < 0) {
						setEventMessages($timespent->error, $timespent->errors, 'errors');
						$error++;
						break;
					}
				}
			}




			// Gestion des heures de nuit
			if($heure_nuit[$day][$cpt] !== null) {	
				$timetoadd_heure_nuit = $heure_nuit[$day][$cpt];
				$newduration_heure_nuit = 0;

				if (!empty($timetoadd_heure_nuit)) {
					$newduration_heure_nuit = $timetoadd_heure_nuit * 3600;
				}
		
				if(empty($timespent_tmp->timespent_id) && $fk_task[$day][$cpt] > 0) {
					$timespent->fk_element = $fk_task[$day][$cpt];
					$timespent->elementtype = 'task';
					$timespent->element_date = $tmpday;
					$timespent->element_datehour = $tmpday;
					$timespent->fk_user = $usertoprocess->id;
					$timespent->datec = $db->idate($now);
					$timespent->thm = $usertoprocess->thm;

					$result = $timespent->create($user);

					$timespent_month[$tmpday][$cpt]->timespent_id = $result;
					$timespent_tmp = $timespent_month[$tmpday][$cpt];
				}

				$timespent->fetch($timespent_tmp->timespent_id);

				// S'il existe une ligne de Projet_task_time_other et que tous les champs sont = null
				if($resheure_other > 0 && ($newduration_heure_nuit == 0)){
					// Agenda Heure nuit
					if($heure_other->heure_nuit != $newduration_heure_nuit) {
						$new_value = formatValueForAgenda('duration', $newduration_heure_nuit);
						$old_value = formatValueForAgenda('duration', $heure_other->heure_nuit);

						if($is_day_anticipe){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
						elseif($object->id > 0){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
					}

					if($is_day_anticipe && $heure_other->heure_nuit > 0) {
						$timespent->note = (!empty($timespent->note) ? $timespent->note.' / ' : '');
						$timespent->note .= 'Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_other->heure_nuit > 0 ? convertSecondToTime($heure_other->heure_nuit) : '00:00').' ➔ 00:00';

						$result = $timespent->update($user);
					}

					$result = $heure_other->delete($user);
				}
				// S'il existe une ligne de Projet_task_time_other et qu'au moins un champ a été modifié
				elseif ($resheure_other > 0 && ($heure_other->heure_nuit != $newduration_heure_nuit || $task_changed || ($timespent->fk_element != $fk_task[$day][$cpt] && $fk_task[$day][$cpt] > 0))){
					// Agenda
					if($task_changed || ($timespent->fk_element != $fk_task[$day][$cpt] && $fk_task[$day][$cpt] > 0)) {
						if($is_day_anticipe){
							if($heure_other->heure_nuit > 0) {
								$new_value = formatValueForAgenda('duration', 0);
								$old_value = formatValueForAgenda('duration', $heure_other->heure_nuit);
								$modification .= '<li class="txt_heure_nuit_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";
							}
							$new_value = formatValueForAgenda('duration', $newduration_heure_nuit);
							$old_value = formatValueForAgenda('duration', 0);
							$modification .= '<li class="txt_heure_nuit_before"><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";
						}
						elseif($object->id > 0){
							if($heure_other->heure_nuit > 0) {
								$new_value = formatValueForAgenda('duration', 0);
								$old_value = formatValueForAgenda('duration', $heure_other->heure_nuit);
								$modification .= '<li class="txt_heure_nuit"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";
							}

							$new_value = formatValueForAgenda('duration', $newduration_heure_nuit);
							$old_value = formatValueForAgenda('duration', 0);
							$modification .= '<li class="txt_heure_nuit"><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>";					
						}
					}
					elseif($heure_other->heure_nuit != $newduration_heure_nuit) {
						$new_value = formatValueForAgenda('duration', $newduration_heure_nuit);
						$old_value = formatValueForAgenda('duration', $heure_other->heure_nuit);

						if($is_day_anticipe){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
						elseif($object->id > 0){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
					}

					if(!$task_changed && $timespent->fk_element != $fk_task[$day][$cpt] && $fk_task[$day][$cpt] > 0) {
						$tmpnote = $timespent->note;
						$tmpduration = $timespent->element_duration;

						$res_del = $timespent->delete($user);

						$timespent = new TimeSpent($db);
						$timespent->fk_element = $fk_task[$day][$cpt];
						$timespent->elementtype = 'task';
						$timespent->element_date = $tmpday;
						$timespent->element_datehour = $tmpday;
						$timespent->element_duration = $tmpduration;
						$timespent->fk_user = $usertoprocess->id;
						$timespent->note = $tmpnote;
						$timespent->datec = $db->idate($now);
						$timespent->thm = $usertoprocess->thm;

						if($is_day_anticipe && $heure_other->heure_nuit != $newduration_heure_nuit) {
							$timespent->note = (!empty($timespent->note) ? $timespent->note.' / ' : '');
							$new_value = formatValueForAgenda('duration', 0);
							$old_value = formatValueForAgenda('duration', $timespent_tmp->timespent_duration);
							$timespent->note .= ' / Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.' sur '.$task->label.") : $old_value ➔ $new_value";

							$new_value = formatValueForAgenda('duration', $newduration);
							$old_value = formatValueForAgenda('duration', 0);
							$timespent->note .= ' et Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.' sur '.$new_task->label.") : $old_value ➔ $new_value";
						}

						$result = $timespent->create($user);

						$heure_other->fk_projet_task_time = $result;
						$heure_other->heure_nuit = $newduration_heure_nuit;
						$result = $heure_other->update($user);

						$timespent_month[$tmpday][$cpt]->timespent_id = $result;
						$timespent_tmp = $timespent_month[$tmpday][$cpt];
					}
					elseif($heure_other->heure_nuit != $newduration_heure_nuit) {
						if($is_day_anticipe) {
							$timespent->note = (!empty($timespent->note) ? $timespent->note.' / ' : '');
							$timespent->note .= 'Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_other->heure_nuit > 0 ? convertSecondToTime($heure_other->heure_nuit) : '00:00').' ➔ '.($newduration_heure_nuit > 0 ? convertSecondToTime($newduration_heure_nuit) : '00:00');
								
							$result = $timespent->update($user);
						}

						if($newduration_heure_nuit >= 0){
							$heure_other->heure_nuit = $newduration_heure_nuit;
						}

						$result = $heure_other->update($user);
					}
				}
				// S'il n'existe pas de ligne de Projet_task_time_other et qu'au moins un champ est != null
				else if($resheure_other == 0 && $newduration_heure_nuit != 0 && $fk_task[$day][$cpt] > 0){
					if($heure_other->heure_nuit != $newduration_heure_nuit) {
						$new_value = formatValueForAgenda('duration', $newduration_heure_nuit);
						$old_value = formatValueForAgenda('duration', $heure_other->heure_nuit);

						if($is_day_anticipe){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit_before"><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
						elseif($object->id > 0){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit"><strong>'.$new_task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
					}

					if($is_day_anticipe) {
						$timespent->note = (!empty($timespent->note) ? $timespent->note.' / ' : '');
						$timespent->note .= 'Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.'00:00'.' ➔ '.($newduration_heure_nuit > 0 ? convertSecondToTime($newduration_heure_nuit) : '00:00');
						
						$result = $timespent->update($user);
					}

					$heure_other->fk_projet_task_time = $timespent->id ;

					if($newduration_heure_nuit > 0){
						$heure_other->heure_nuit = $newduration_heure_nuit;
					}

					$result = $heure_other->create($user);
				}

				if ($result < 0) {
					setEventMessages($heure_other->error, $heure_other->errors, 'errors');
					$error++;
					break;
				}
				
			}



			// Gestion des sites
			if($site[$day][$cpt] !== null) {
				$new_site = $site[$day][$cpt];
		
				if(empty($timespent_tmp->timespent_id) && $fk_task[$day][$cpt] > 0) {
					$timespent->fk_element = $fk_task[$day][$cpt];
					$timespent->elementtype = 'task';
					$timespent->element_date = $tmpday;
					$timespent->element_datehour = $tmpday;
					$timespent->fk_user = $usertoprocess->id;
					$timespent->datec = $db->idate($now);
					$timespent->thm = $usertoprocess->thm;

					$result = $timespent->create($user);
					
					$timespent_month[$tmpday][$cpt]->timespent_id = $result;
					$timespent_tmp = $timespent_month[$tmpday][$cpt];
				}

				$res = $heure_other->fetchWithoutId($timespent_tmp->timespent_id); // $res contient l'id du Projet_task_time_other correspondant, si celui-ci existe

				$new_value = formatValueForAgenda('duration', $new_site);
				$old_value = formatValueForAgenda('duration', $heure_other->site);

				$modification .= ($old_value != $new_value ? '<li><strong>Sites</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');

				$timespent->fetch($timespent_tmp->timespent_id);

				// S'il existe une ligne de Projet_task_time_other et que tous les champs sont = null
				if($res > 0 && (empty($new_site) && $heure_other->heure_nuit == 0)){
					$result = $heure_other->delete($user);
				}
				// S'il existe une ligne de Projet_task_time_other et qu'au moins un champ a été modifié
				elseif ($res > 0 && $heure_other->site != $new_site){
					$heure_other->site = $new_site;
					$result = $heure_other->update($user);
				}
				// S'il n'existe pas de ligne de Projet_task_time_other et qu'au moins un champ est != null
				else if($res == 0 && !empty($new_site)){
					$heure_other->fk_projet_task_time = $timespent->id;
					$heure_other->site = $new_site;
					$result = $heure_other->create($user);
				}

				if ($result < 0) {
					setEventMessages($heure_other->error, $heure_other->errors, 'errors');
					$error++;
					break;
				}
			}

		}

		// Autres
		$silae = new Silae($db);
		$extrafields->fetch_name_optionals_label($silae->table_element);
		$res = $silae->fetchSilaeWithoutId($tmpday, $usertoprocess->id);
		$has_modif = 0;
		$all_field_null = 1;
		
		foreach ($extrafields->attributes[$silae->table_element]['label'] as $key => $label) {
			if($key_post[$day] !== null || !empty($silae->key)) {
				$all_field_null = 0;
			}

			if (dol_eval($extrafields->attributes[$silae->table_element]['list'][$key], 1, 1, '2') != 1) {
				continue;
			}

			$key_post = (GETPOST('options_'.$key)  ? GETPOST('options_'.$key)  : array());
			$type = $extrafields->attributes[$silae->table_element]['type'][$key];

			if(($type != 'boolean' && $key_post[$day] !== null) || ($type == 'boolean' && ((isset($key_post[$day]) && $silae->array_options['options_'.$key] == 0) || (!isset($key_post[$day]) && $silae->array_options['options_'.$key] == 1)))) {
				$has_modif = 1;
				$new_value = $key_post[$day];
				$new_value = (isset($new_value) ? 1 : $new_value);
				
				// Agenda
				if($new_value != $silae->array_options['options_'.$key]) {
					$new_value = formatValueForAgenda($type, $new_value);
					$old_value = formatValueForAgenda($type, $silae->array_options['options_'.$key]);

					$modification .= ($old_value != $new_value ? '<li><strong>'.$label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
				}
				
				$silae->array_options['options_'.$key] = $new_value;
			}
		}

		// S'il existe une ligne et que tous les champs sont = null
		if($res > 0 && $all_field_null && empty($silae->heure_sup00) && empty($silae->heure_sup25) && empty($silae->heure_sup50) && empty($silae->heure_sup50ht)) {
				$result = $silae->delete($user);
		}
		// S'il existe une ligne et qu'au moins un champ a été modifié
		elseif($res > 0 && $has_modif) {
			$result = $silae->update($user);
		}
		// S'il n'existe pas de ligne et qu'au moins un champ est différent de null
		elseif($res == 0 && $has_modif) {
			$silae->fk_user = $usertoprocess->id;
			$silae->date = $tmpday;

			$result = $silae->create($user);
		}

		if ($result < 0) {
			setEventMessages($silae->error, $silae->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		// Si le feuille de temps existe et que des modifications ont été réalisé
		if($object->id > 0 && !empty($modification)){
			$modification = '<ul>'.$modification.'</ul>';
			
			if($user->hasRight('feuilledetemps','feuilledetemps','modify_verification') && !in_array($object->status, array(0, 2, 3))) {
				$object->actiontypecode = 'AC_FDT_VERIF';
				$object->actionmsg2 = ($object->status == 4 ? "Mise à jour des données lors de la vérification de la feuille de temps $object->ref" : "Mise à jour des données après la vérification de la feuille de temps $object->ref");
			}
			else {
				$object->actiontypecode = 'AC_OTH_AUTO';
				$object->actionmsg2 = "Mise à jour des données de la feuille de temps $object->ref";
			}
			$object->actionmsg = $modification;
			$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
		}

		if(strpos($_SERVER["PHP_SELF"], 'feuilledetemps_card') === false) {
			$param = '';
			$param .= ($mode ? '&mode='.urlencode($mode) : '');
			$param .= ($projectid ? 'id='.urlencode($projectid) : '');
			$param .= ($search_usertoprocessid ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
			$param .= ($day ? '&day='.urlencode($day) : '').($month ? '&month='.urlencode($month) : '').($year ? '&year='.urlencode($year) : '');
			$param .= ($search_project_ref ? '&search_project_ref='.urlencode($search_project_ref) : '');
			$param .= ($search_usertoprocessid > 0 ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
			$param .= ($search_thirdparty ? '&search_thirdparty='.urlencode($search_thirdparty) : '');
			$param .= ($search_declared_progress ? '&search_declared_progress='.urlencode($search_declared_progress) : '');
			$param .= ($search_task_ref ? '&search_task_ref='.urlencode($search_task_ref) : '');
			$param .= ($search_task_label ? '&search_task_label='.urlencode($search_task_label) : '');
			$param .= ($showFav ? '&showFav=1' : '');

			$search_array_options=$search_array_options_project;
			$search_options_pattern='search_options_';
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

			$search_array_options = $search_array_options_task;
			$search_options_pattern = 'search_task_options_';
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
		}

		if($massaction != "transmettre") {
			if(strpos($_SERVER["PHP_SELF"], 'feuilledetemps_card') === false) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				// Redirect to avoid submit twice on back
				header('Location: '.$_SERVER["PHP_SELF"].'?'.$param);
				exit;
			}
			else {
				if($permissionToVerification && $object->status == $object::STATUS_VERIFICATION) {
					$action = 'addtimeVerification';
				}
				else {
					setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
					// Redirect to avoid submit twice on back
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			}
		}
	}
	
}
elseif (!$conf->global->FDT_DISPLAY_COLUMN && $action == 'addtime' && GETPOST('formfilteraction') != 'listafterchangingselectedfields' && $massaction != 'validate1' && $massaction != 'validate2' && $massaction != 'verification' && $massaction != 'refus') {
	// Création de la feuille de temps au 1er enregistrement
	if($object->id == 0) {
		$object->ref = "FDT_".str_pad($usertoprocess->array_options['options_matricule'], 5, '0', STR_PAD_LEFT).'_'.dol_print_date($lastdaytoshow, '%m%Y');
		$object->date_debut = $first_day_month;
		$object->date_fin = $lastdaytoshow;
		$object->fk_user = $usertoprocess->id;
		$object->status = 0;

		$result = $object->create($user, 0);

		if($result <= 0) {
			setEventMessages("Impossible de créer la feuille de temps", null, 'errors');
			$error++;
		}
	}

	$timetoadd = ($_POST['task'] ? $_POST['task'] :  array());
	$timetoadd_hs_25 = $_POST['hs25_task'];
	$timetoadd_hs_50 = $_POST['hs50_task'];
	$heure_nuit = $_POST['heure_nuit'];
	$epi = $_POST['epi'];

	$deplacement_ponctuel = $_POST['deplacement_ponctuel'];
	$moyen_transport = $_POST['moyen_transport'];
	$type_deplacement = $_POST['type_deplacement'];
	$note = ($_POST['note'] ? $_POST['note'] : array());
	$notes_deplacement = $_POST['note_deplacement'];
	$observationFDT = GETPOST('observationFDT');

	// Temps de chaque semaine pour vérifier s'il y a moins de 35h enregistré
	$timeDoneByWeekBefore = $timeSpentWeek; 

	// Gestion des notes
	foreach ($note as $taskid => $value) {  	// Loop on each task
		$task = new ExtendedTask($db);
		$task->fetch($taskid);

		foreach ($value as $key => $val) {          // Loop on each day $key => $idw / $val => $value[$idw]
		//for ($idw = 0; $idw < $nb_jour; $idw++) {	
			$tmpday = $dayinloopfromfirstdaytoshow_array[$key];

			$res = $task->fetchTimeSpentWithoutId($taskid, $tmpday, $usertoprocess->id); // $res contient l'id de temps consommé correspondant, si celui-ci existe

			if ($note[$taskid][$key] != $task->timespent_note) {
				// Si le temps consommé existe déja et que tous les champs sont = null
				if($res == 1 && $task->timespent_duration == 0 && empty($note[$taskid][$key])){
					$result = $task->delTimeSpent($user);
				}
				// Si le temps consommé existe déja et qu'il y a au moins une modification
				elseif($res == 1 && $task->timespent_note != $note[$taskid][$key]){
					$task->timespent_note = $note[$taskid][$key];
					$result = $task->updateTimeSpent($user);
				}
				// Si le temps consommé n'existe pas et qu'il y a au moins un champ != null
				elseif ($res == 0 && !empty($note[$taskid][$key])) {
						$task->timespent_fk_user = $usertoprocess->id;
						$task->timespent_date = $tmpday;
						$task->timespent_datehour = $task->timespent_date;
						$task->timespent_note = $note[$taskid][$key];

						$result = $task->addTimeSpent($user);
				}

				if ($result < 0) {
					setEventMessages($task->error, $task->errors, 'errors');
					$error++;
					break;
				}
			}
		}
	}

	// Gestion des temps
	foreach ($timetoadd as $taskid => $value) {     // Loop on each task
		//$updateoftaskdone = 0;
		$task = new ExtendedTask($db);
		$task->fetch($taskid);
		
		if (GETPOSTISSET($taskid.'progress')) {
			$task->progress = GETPOST($taskid.'progress', 'int');
		} else {
			unset($task->progress);
		}

		foreach ($value as $key => $val) {          // Loop on each day $key => $idw / $val => $value[$idw]
		//for ($idw = 0; $idw < $nb_jour; $idw++) {	
			$tmpday = $dayinloopfromfirstdaytoshow_array[$key];

			$is_day_anticipe = 0;
			if($tmpday < $first_day_month) {
				$is_day_anticipe = 1;
			}

			$amountoadd = $timetoadd[$taskid][$key];
			$newduration = 0;


			// Formatage des heures
			if (!empty($amountoadd)) {
				$tmpduration = explode(':', $amountoadd);
				if (!empty($tmpduration[0])) {
					$newduration += ($tmpduration[0] * 3600);
				}
				if (!empty($tmpduration[1])) {
					$newduration += ($tmpduration[1] * 60);
				}
				if (!empty($tmpduration[2])) {
					$newduration += ($tmpduration[2]);
				}
			}

			$res = $task->fetchTimeSpentWithoutId($taskid, $tmpday, $usertoprocess->id); // $res contient l'id de temps consommé correspondant, si celui-ci existe

			// Gestion des temps consommés
			if ($task->timespent_duration != $newduration) {
				// Agenda
				if($task->timespent_duration != $newduration) {
					$new_value = formatValueForAgenda('duration', $newduration);
					$old_value = formatValueForAgenda('duration', $task->timespent_duration);

					if($is_day_anticipe){
						$modification .= ($old_value != $new_value ? '<li class="txt_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
					elseif($object->id > 0){
						$modification .= ($old_value != $new_value ? '<li><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
				}

				// Si le temps consommé existe déja et que tous les champs sont = null
				if($res == 1 && $newduration == 0 && empty($task->timespent_note)){
					if($is_day_anticipe && $task->timespent_duration > 0) {
						$task->timespent_note = ' / Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($task->timespent_duration > 0 ? convertSecondToTime($task->timespent_duration) : '00:00').' ➔ 00:00';
						$task->timespent_duration = 0;

						$result = $task->updateTimeSpent($user);
					}
					elseif(!$is_day_anticipe){
						$result = $task->delTimeSpent($user);
					}
				}
				// Si le temps consommé existe déja et qu'il y a au moins une modification
				else if($res == 1 && $task->timespent_duration != $newduration){
					if($is_day_anticipe && $task->timespent_duration != $newduration) {
						$task->timespent_note .= ' / Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($task->timespent_duration > 0 ? convertSecondToTime($task->timespent_duration) : '00:00').' ➔ '.($newduration > 0 ? convertSecondToTime($newduration) : '00:00');
						$task->timespent_duration = $newduration;

						$result = $task->updateTimeSpent($user);
					}
					elseif(!$is_day_anticipe){
						$task->timespent_duration = $newduration;

						$result = $task->updateTimeSpent($user);
					}
				}
				// Si le temps consommé n'existe pas et qu'il y a au moins un champ != null
				elseif ($res == 0 && $newduration != 0){
					if($is_day_anticipe && $newduration > 0) {
						$task->timespent_note .= ' / Modification semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : 00:00'.' ➔ '.($newduration > 0 ? convertSecondToTime($newduration) : '00:00');
						$task->timespent_duration = $newduration;
						$task->timespent_fk_user = $usertoprocess->id;
						$task->timespent_date = $tmpday;
						$task->timespent_datehour = $task->timespent_date;

						$result = $task->addTimeSpent($user);
					}
					elseif(!$is_day_anticipe){
						$task->timespent_duration = $newduration;
						$task->timespent_fk_user = $usertoprocess->id;
						$task->timespent_date = $tmpday;
						$task->timespent_datehour = $task->timespent_date;

						$result = $task->addTimeSpent($user);
					}
				}

				if ($result < 0) {
					setEventMessages($task->error, $task->errors, 'errors');
					$error++;
					break;
				}

				//$updateoftaskdone++;
			}
		}
	}

	// Gestion des HS25
	foreach ($timetoadd_hs_25 as $taskid => $value) {     // Loop on each task
		$task = new ExtendedTask($db);
		$task->fetch($taskid);
		
		foreach ($value as $key => $val) {          // Loop on each day $key => $idw / $val => $value[$idw]
		//for ($idw = 0; $idw < $nb_jour; $idw++) {	
			$tmpday = $dayinloopfromfirstdaytoshow_array[$key];

			$is_day_anticipe = 0;
			if($tmpday < $first_day_month) {
				$is_day_anticipe = 1;
			}

			$amountoadd_hs_25 = $timetoadd_hs_25[$taskid][$key];
			$newduration_hs_25 = 0;

			if (!empty($amountoadd_hs_25)) {
				$tmpduration_hs_25 = explode(':', $amountoadd_hs_25);
				if (!empty($tmpduration_hs_25[0])) {
					$newduration_hs_25 += ($tmpduration_hs_25[0] * 3600);
				}
				if (!empty($tmpduration_hs_25[1])) {
					$newduration_hs_25 += ($tmpduration_hs_25[1] * 60);
				}
				if (!empty($tmpduration_hs_25[2])) {
					$newduration_hs_25 += ($tmpduration_hs_25[2]);
				}
			}

			$res = $task->fetchTimeSpentWithoutId($taskid, $tmpday, $usertoprocess->id); // $res contient l'id de temps consommé correspondant, si celui-ci existe

			if ($task->timespent_id > 0) {
				$heure_sup = new Projet_task_time_heure_sup($db);
				$res = $heure_sup->fetchWithoutId($task->timespent_id); // $res contient l'id du Projet_task_time_heure_sup correspondant, si celui-ci existe

				if($heure_sup->heure_sup_25_duration != $newduration_hs_25) {
					// Agenda HS 25
					if($heure_sup->heure_sup_25_duration != $newduration_hs_25) {
						$new_value = formatValueForAgenda('duration', $newduration_hs_25);
						$old_value = formatValueForAgenda('duration', $heure_sup->heure_sup_25_duration);

						if($is_day_anticipe){
							$modification .= ($old_value != $new_value ? '<li class="txt_hs25_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
						elseif($object->id > 0){
							$modification .= ($old_value != $new_value ? '<li class="txt_hs25"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
					}

					// S'il existe une ligne d'heure sup et que tous les champs sont = null
					if($res > 0 && $newduration_hs_25 == 0 && $heure_sup->heure_sup_25_duration == 0){
						if($is_day_anticipe) {
							if($heure_sup->heure_sup_25_duration > 0) {
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification HS 25% semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_sup->heure_sup_25_duration > 0 ? convertSecondToTime($heure_sup->heure_sup_25_duration) : '00:00').' ➔ 00:00';
							}
							$result = $task->updateTimeSpent($user);
						}
						
						$result = $heure_sup->delete($user);
					}
					// S'il n'existe pas de ligne et qu'au moins un champ est != null
					else if($res == 0 && $newduration_hs_25 != 0){
						if($is_day_anticipe) {
							if($newduration_hs_25 > 0) {
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification HS 25% semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.'00:00'.' ➔ '.($newduration_hs_25 > 0 ? convertSecondToTime($newduration_hs_25) : '00:00');
							}
							$result = $task->updateTimeSpent($user);
						}

						$heure_sup->fk_projet_task_time = $task->timespent_id;

						if($newduration_hs_25 > 0){
							$heure_sup->heure_sup_25_duration = $newduration_hs_25;
						}

						$result = $heure_sup->create($user);
					}
					// Si il existe une ligne et qu'au moins un champ a été modifié
					elseif ($res > 0 && $heure_sup->heure_sup_25_duration != $newduration_hs_25){
						if($is_day_anticipe) {
							if($heure_sup->heure_sup_25_duration != $newduration_hs_25) {
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification HS 25% semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_sup->heure_sup_25_duration > 0 ? convertSecondToTime($heure_sup->heure_sup_25_duration) : '00:00').' ➔ '.($newduration_hs_25 > 0 ? convertSecondToTime($newduration_hs_25) : '00:00');
							}
							$result = $task->updateTimeSpent($user);
						}

						if($newduration_hs_25 >= 0){
							$heure_sup->heure_sup_25_duration = $newduration_hs_25;
						}

						$result = $heure_sup->update($user);
					}

					if ($result < 0) {
						setEventMessages($heure_sup->error, $heure_sup->errors, 'errors');
						$error++;
						break;
					}
				}
			}
		}
	}

	// Gestion des HS50
	foreach ($timetoadd_hs_50 as $taskid => $value) {     // Loop on each task
		$task = new ExtendedTask($db);
		$task->fetch($taskid);
		
		foreach ($value as $key => $val) {          // Loop on each day $key => $idw / $val => $value[$idw]
		//for ($idw = 0; $idw < $nb_jour; $idw++) {	
			$tmpday = $dayinloopfromfirstdaytoshow_array[$key];

			$is_day_anticipe = 0;
			if($tmpday < $first_day_month) {
				$is_day_anticipe = 1;
			}

			$amountoadd_hs_50 = $timetoadd_hs_50[$taskid][$key];
			$newduration_hs_50 = 0;

			if (!empty($amountoadd_hs_50)) {
				$tmpduration_hs_50 = explode(':', $amountoadd_hs_50);
				if (!empty($tmpduration_hs_50[0])) {
					$newduration_hs_50 += ($tmpduration_hs_50[0] * 3600);
				}
				if (!empty($tmpduration_hs_50[1])) {
					$newduration_hs_50 += ($tmpduration_hs_50[1] * 60);
				}
				if (!empty($tmpduration_hs_50[2])) {
					$newduration_hs_50 += ($tmpduration_hs_50[2]);
				}
			}

			$res = $task->fetchTimeSpentWithoutId($taskid, $tmpday, $usertoprocess->id); // $res contient l'id de temps consommé correspondant, si celui-ci existe

			if ($task->timespent_id > 0) {
				$heure_sup = new Projet_task_time_heure_sup($db);
				$res = $heure_sup->fetchWithoutId($task->timespent_id); // $res contient l'id du Projet_task_time_heure_sup correspondant, si celui-ci existe

				if($heure_sup->heure_sup_50_duration != $newduration_hs_50) {
					// Agenda HS 50
					if($heure_sup->heure_sup_50_duration != $newduration_hs_50) {
						$new_value = formatValueForAgenda('duration', $newduration_hs_50);
						$old_value = formatValueForAgenda('duration', $heure_sup->heure_sup_50_duration);

						if($is_day_anticipe){
							$modification .= ($old_value != $new_value ? '<li class="txt_hs50_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
						elseif($object->id > 0){
							$modification .= ($old_value != $new_value ? '<li class="txt_hs50"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
					}

					// S'il existe une ligne d'heure sup et que tous les champs sont = null
					if($res > 0 && $heure_sup->heure_sup_25_duration == 0 && $newduration_hs_50 == 0){
						if($is_day_anticipe) {
							if($heure_sup->heure_sup_50_duration > 0) {
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification HS 50% semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_sup->heure_sup_50_duration > 0 ? convertSecondToTime($heure_sup->heure_sup_50_duration) : '00:00').' ➔ 00:00';
							}
							$result = $task->updateTimeSpent($user);
						}
						
						$result = $heure_sup->delete($user);
					}
					// S'il n'existe pas de ligne et qu'au moins un champ est != null
					else if($res == 0 && $newduration_hs_50 != 0){
						if($is_day_anticipe) {
							if($newduration_hs_50 > 0) {
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification HS 50% semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.'00:00'.' ➔ '.($newduration_hs_50 > 0 ? convertSecondToTime($newduration_hs_50) : '00:00');
							}
							$result = $task->updateTimeSpent($user);
						}

						$heure_sup->fk_projet_task_time = $task->timespent_id;

						if($newduration_hs_50 > 0){
							$heure_sup->heure_sup_50_duration = $newduration_hs_50;
						}

						$result = $heure_sup->create($user);
					}
					// Si il existe une ligne et qu'au moins un champ a été modifié
					elseif ($res > 0 && ($heure_sup->heure_sup_50_duration != $newduration_hs_50)){
						if($is_day_anticipe) {
							if($heure_sup->heure_sup_50_duration != $newduration_hs_50) {
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification HS 50% semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_sup->heure_sup_50_duration > 0 ? convertSecondToTime($heure_sup->heure_sup_50_duration) : '00:00').' ➔ '.($newduration_hs_50 > 0 ? convertSecondToTime($newduration_hs_50) : '00:00');
							}
							$result = $task->updateTimeSpent($user);
						}

						if($newduration_hs_50 >= 0){
							$heure_sup->heure_sup_50_duration = $newduration_hs_50;
						}

						$result = $heure_sup->update($user);
					}

					if ($result < 0) {
						setEventMessages($heure_sup->error, $heure_sup->errors, 'errors');
						$error++;
						break;
					}
				}
			}
		}
	}

	// Gestion des Heures de nuit
	foreach ($heure_nuit as $taskid => $value) {     // Loop on each task
		$task = new ExtendedTask($db);
		$task->fetch($taskid);
		
		foreach ($value as $key => $val) {          // Loop on each day $key => $idw / $val => $value[$idw]
		//for ($idw = 0; $idw < $nb_jour; $idw++) {	
			$tmpday = $dayinloopfromfirstdaytoshow_array[$key];

			$is_day_anticipe = 0;
			if($tmpday < $first_day_month) {
				$is_day_anticipe = 1;
			}

			$timetoadd_heure_nuit = $heure_nuit[$taskid][$key];
			$newduration_heure_nuit = 0;

			if (!empty($timetoadd_heure_nuit)) {
				$newduration_heure_nuit = $timetoadd_heure_nuit * 3600;
			}

			$res = $task->fetchTimeSpentWithoutId($taskid, $tmpday, $usertoprocess->id); // $res contient l'id de temps consommé correspondant, si celui-ci existe

			// Gestion des autres heures (heure nuit/port epi)
			if ($task->timespent_id > 0) {	
				$heure_other = new Projet_task_time_other($db);
				$res = $heure_other->fetchWithoutId($task->timespent_id); // $res contient l'id du Projet_task_time_other correspondant, si celui-ci existe
		
				if($heure_other->heure_nuit != $newduration_heure_nuit) {
					// Agenda Heure nuit
					if($heure_other->heure_nuit != $newduration_heure_nuit) {
						$new_value = formatValueForAgenda('duration', $newduration_heure_nuit);
						$old_value = formatValueForAgenda('duration', $heure_other->heure_nuit);

						if($is_day_anticipe){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
						elseif($object->id > 0){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_nuit"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
					}

					// S'il existe une ligne de Projet_task_time_other et que tous les champs sont = null
					if($res > 0 && ($newduration_heure_nuit == 0 && $heure_other->port_epi == 0)){
						if($is_day_anticipe) {
							if($heure_other->heure_nuit > 0){
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_other->heure_nuit > 0 ? convertSecondToTime($heure_other->heure_nuit) : '00:00').' ➔ 00:00';
							}

							$result = $task->updateTimeSpent($user);
						}

						$result = $heure_other->delete($user);
					}
					// S'il n'existe pas de ligne de Projet_task_time_other et qu'au moins un champ est != null
					else if($res == 0 && $newduration_heure_nuit != 0){
						if($is_day_anticipe) {
							if($newduration_heure_nuit > 0){
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.'00:00'.' ➔ '.($newduration_heure_nuit > 0 ? convertSecondToTime($newduration_heure_nuit) : '00:00');
							}
							$result = $task->updateTimeSpent($user);
						}

						$heure_other->fk_projet_task_time = $task->timespent_id ;

						if($newduration_heure_nuit > 0){
							$heure_other->heure_nuit = $newduration_heure_nuit;
						}

						$result = $heure_other->create($user);
					}
					// S'il existe une ligne de Projet_task_time_other et qu'au moins un champ a été modifié
					elseif ($res > 0 && $heure_other->heure_nuit != $newduration_heure_nuit){
						if($is_day_anticipe) {
							if($heure_other->heure_nuit != $newduration_heure_nuit){
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification Heures de nuit semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_other->heure_nuit > 0 ? convertSecondToTime($heure_other->heure_nuit) : '00:00').' ➔ '.($newduration_heure_nuit > 0 ? convertSecondToTime($newduration_heure_nuit) : '00:00');
							}
								
							$result = $task->updateTimeSpent($user);
						}

						if($newduration_heure_nuit >= 0){
							$heure_other->heure_nuit = $newduration_heure_nuit;
						}

						$result = $heure_other->update($user);
					}

					if ($result < 0) {
						setEventMessages($heure_other->error, $heure_other->errors, 'errors');
						$error++;
						break;
					}
				}
			}
		}
	}

	// Gestion des Heures EPI
	foreach ($epi as $taskid => $value) {     // Loop on each task
		$task = new ExtendedTask($db);
		$task->fetch($taskid);

		foreach ($value as $key => $val) {          // Loop on each day $key => $idw / $val => $value[$idw]
		//for ($idw = 0; $idw < $nb_jour; $idw++) {	
			$tmpday = $dayinloopfromfirstdaytoshow_array[$key];

			$is_day_anticipe = 0;
			if($tmpday < $first_day_month) {
				$is_day_anticipe = 1;
			}

			$timetoadd_epi = $epi[$taskid][$key];
			$newduration_epi = 0;

			if (!empty($timetoadd_epi)) {
				$newduration_epi = $timetoadd_epi * 3600;
			}

			$res = $task->fetchTimeSpentWithoutId($taskid, $tmpday, $usertoprocess->id); // $res contient l'id de temps consommé correspondant, si celui-ci existe

			// Gestion des autres heures (heure nuit/port epi)
			if ($task->timespent_id > 0) {	
				$heure_other = new Projet_task_time_other($db);
				$res = $heure_other->fetchWithoutId($task->timespent_id); // $res contient l'id du Projet_task_time_other correspondant, si celui-ci existe
		
				if($heure_other->port_epi != $newduration_epi) {
					// Agenda Heure EPI
					if($heure_other->port_epi != $newduration_epi) {
						$new_value = formatValueForAgenda('duration', $newduration_epi);
						$old_value = formatValueForAgenda('duration', $heure_other->port_epi);

						if($is_day_anticipe){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_epi_before"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
						elseif($object->id > 0){
							$modification .= ($old_value != $new_value ? '<li class="txt_heure_epi"><strong>'.$task->label.'</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
						}
					}

					// S'il existe une ligne de Projet_task_time_other et que tous les champs sont = null
					if($res > 0 && ($heure_other->heure_nuit == 0 && $newduration_epi == 0)){
						if($is_day_anticipe) {
							if($heure_other->port_epi > 0){
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification Heure port EPI semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_other->port_epi > 0 ? convertSecondToTime($heure_other->port_epi) : '00:00').' ➔ 00:00';
							
							}

							$result = $task->updateTimeSpent($user);
						}

						$result = $heure_other->delete($user);
					}
					// S'il n'existe pas de ligne de Projet_task_time_other et qu'au moins un champ est != null
					else if($res == 0 && $newduration_epi != 0){
						if($is_day_anticipe) {
							if($newduration_epi > 0){
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification Heures port EPI semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.'00:00'.' ➔ '.($newduration_epi > 0 ? convertSecondToTime($newduration_epi) : '00:00');
							}
							$result = $task->updateTimeSpent($user);
						}

						$heure_other->fk_projet_task_time = $task->timespent_id ;

						if($newduration_epi > 0){
							$heure_other->port_epi = $newduration_epi;
						}

						$result = $heure_other->create($user);
					}
					// S'il existe une ligne de Projet_task_time_other et qu'au moins un champ a été modifié
					elseif ($res > 0 && $heure_other->port_epi != $newduration_epi){
						if($is_day_anticipe) {
							if($heure_other->port_epi != $newduration_epi){
								$task->timespent_note = (!empty($task->timespent_note) ? $task->timespent_note.' / ' : '');
								$task->timespent_note .= 'Modification Heures de port EPI semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.($heure_other->port_epi > 0 ? convertSecondToTime($heure_other->port_epi) : '00:00').' ➔ '.($newduration_epi > 0 ? convertSecondToTime($newduration_epi) : '00:00');
							}
								
							$result = $task->updateTimeSpent($user);
						}

						if($newduration_epi >= 0){
							$heure_other->port_epi = $newduration_epi;
						}

						$result = $heure_other->update($user);
					}

					if ($result < 0) {
						setEventMessages($heure_other->error, $heure_other->errors, 'errors');
						$error++;
						break;
					}
				}
			}
		}
	}

	// Gestion des notes de déplacement
	foreach($notes_deplacement as $key => $val) {
		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$key];

		$deplacement = new Deplacement($db);
		$res = $deplacement->fetchDeplacementWithoutId($dayinloopfromfirstdaytoshow, $usertoprocess->id);

		// S'il existe une ligne et que tous les champs sont = null
		if($res > 0 && empty($deplacement_ponctuel[$key]) && $moyen_transport[$key] == 0 && $type_deplacement[$key] == 0 && empty($notes_deplacement[$key])) {
			$result = $deplacement->delete($user);
		}
		// S'il existe une ligne et qu'au moins un champ a été modifié
		elseif($res > 0 && $deplacement->note != $notes_deplacement[$key]) {
			$deplacement->note = $notes_deplacement[$key];

			$result = $deplacement->update($user);
		}
		// S'il n'existe pas de ligne et qu'au moins un champ est différent de null
		elseif($res == 0 && !empty($notes_deplacement[$key])) {
			$deplacement->note = $notes_deplacement[$key];
			$deplacement->fk_user = $usertoprocess->id;
			$deplacement->date = $dayinloopfromfirstdaytoshow;

			$result = $deplacement->create($user);
		}
	}

	// Gestion des déplacements
	if($object->status != $object::STATUS_VALIDATED && $object->status != $object::STATUS_EXPORTED) {
		for ($idw = 0; $idw < $nb_jour; $idw++) {
			$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];

			$is_day_anticipe = 0;
			if($tmpday < $first_day_month) {
				$is_day_anticipe = 1;
			}

			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw];

			$deplacement = new Deplacement($db);
			$res = $deplacement->fetchDeplacementWithoutId($dayinloopfromfirstdaytoshow, $usertoprocess->id);

			if($deplacement_ponctuel[$idw] != $deplacement->deplacement_ponctuel || $type_deplacement[$idw] != $deplacement->type_deplacement || $moyen_transport[$idw] != $deplacement->moyen_transport || empty($deplacement->note)) {
				// Agenda Deplacement Ponctuel
				if($deplacement_ponctuel[$idw] != $deplacement->deplacement_ponctuel) {
					$new_value = formatValueForAgenda('boolean', $deplacement_ponctuel[$idw]);
					$old_value = formatValueForAgenda('boolean', $deplacement->deplacement_ponctuel);

					if($is_day_anticipe){
						$modification .= ($old_value != $new_value ? '<li class="txt_before"><strong>Déplacement Ponctuel</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
					elseif($object->id > 0){
						$modification .= ($old_value != $new_value ? '<li><strong>Déplacement Ponctuel</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
				}

				// Agenda Type Déplacement
				if($type_deplacement[$idw] != $deplacement->type_deplacement) {
					$new_value = formatValueForAgenda('int', $type_deplacement[$idw], $deplacement, 'type_deplacement');
					$old_value = formatValueForAgenda('int', $deplacement->type_deplacement, $deplacement, 'type_deplacement');

					if($is_day_anticipe){
						$modification .= ($old_value != $new_value ? '<li class="txt_before"><strong>Type Déplacement</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
					elseif($object->id > 0){
						$modification .= ($old_value != $new_value ? '<li><strong>Type Déplacement</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
				}

				// Agenda Moyen Transport
				if($moyen_transport[$idw] != $deplacement->moyen_transport) {
					$new_value = formatValueForAgenda('int', $moyen_transport[$idw], $deplacement, 'moyen_transport');
					$old_value = formatValueForAgenda('int', $deplacement->moyen_transport, $deplacement, 'moyen_transport');

					if($is_day_anticipe){
						$modification .= ($old_value != $new_value ? '<li class="txt_before"><strong>Moyen de transport</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
					elseif($object->id > 0){
						$modification .= ($old_value != $new_value ? '<li><strong>Moyen de transport</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
					}
				}

				// S'il existe une ligne et que tous les champs sont = null
				if($res > 0 && empty($deplacement_ponctuel[$idw]) && $moyen_transport[$idw] == 0 && $type_deplacement[$idw] == 0 && empty($notes_deplacement[$idw])) {
					if($is_day_anticipe) {
						if(empty($deplacement_ponctuel[$idw]) && !empty($deplacement->deplacement_ponctuel)) {
							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Deplacement ponctuel semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : Oui ➔ Non';
							$deplacement->deplacement_ponctuel = 0;
						}
						if($moyen_transport[$idw] == 0 && $deplacement->moyen_transport != 0) {
							$moyen_transport_before = $deplacement->fields['moyen_transport']['arrayofkeyval'][$deplacement->moyen_transport];

							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Moyen de transport semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.$moyen_transport_before.' ➔ /';
							$deplacement->moyen_transport = 0;
						}
						if($type_deplacement[$idw] == 0 && $deplacement->type_deplacement != 0) {
							$type_deplacement_before = $deplacement->fields['type_deplacement']['arrayofkeyval'][$deplacement->type_deplacement];

							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Type Déplacement semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.$type_deplacement_before.' ➔ /';
							$deplacement->type_deplacement = 0;
						}	

						$result = $deplacement->update($user);
					}
					elseif(!$is_day_anticipe) {
						$result = $deplacement->delete($user);
					}
				}
				// S'il existe une ligne et qu'au moins un champ a été modifié
				elseif($res > 0 && ($deplacement_ponctuel[$idw] != $deplacement->deplacement_ponctuel || $moyen_transport[$idw] != $deplacement->moyen_transport || 
				$type_deplacement[$idw] != $deplacement->type_deplacement)) {
					if($is_day_anticipe) {
						if($deplacement_ponctuel[$idw] != $deplacement->deplacement_ponctuel) {
							$deplacement_ponctuel_before = ($deplacement->deplacement_ponctuel ? 'Oui' : 'Non');
							$deplacement_ponctuel_after = ($deplacement_ponctuel[$idw] ? 'Oui' : 'Non');

							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Deplacement ponctuel semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.$deplacement_ponctuel_before.' ➔ '.$deplacement_ponctuel_after;
						}
						if($moyen_transport[$idw] != $deplacement->moyen_transport) {
							$moyen_transport_before = ($deplacement->moyen_transport > 0 ? $deplacement->fields['moyen_transport']['arrayofkeyval'][$deplacement->moyen_transport] : '/');
							$moyen_transport_after = ($moyen_transport[$idw] > 0 ? $deplacement->fields['moyen_transport']['arrayofkeyval'][$moyen_transport[$idw]] : '/');
							
							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Moyen de transport semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.$moyen_transport_before.' ➔ '.$moyen_transport_after;
						}
						if($type_deplacement[$idw] != $deplacement->type_deplacement) {
							$type_deplacement_before = ($deplacement->type_deplacement > 0 ? $deplacement->fields['type_deplacement']['arrayofkeyval'][$deplacement->type_deplacement] : '/');
							$type_deplacement_after = ($type_deplacement[$idw] > 0 ? $deplacement->fields['type_deplacement']['arrayofkeyval'][$type_deplacement[$idw]] : '/');

							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Type Déplacement semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : '.$type_deplacement_before.' ➔ '.$type_deplacement_after;
						}
					}

					$deplacement->deplacement_ponctuel = $deplacement_ponctuel[$idw];
					$deplacement->moyen_transport = $moyen_transport[$idw];
					$deplacement->type_deplacement = $type_deplacement[$idw];

					$result = $deplacement->update($user);
				}
				// S'il n'existe pas de ligne et qu'au moins un champ est différent de null
				elseif($res == 0 && (!empty($deplacement_ponctuel[$idw]) || $moyen_transport[$idw] != 0 || $type_deplacement[$idw] != 0)) {
					if($is_day_anticipe) {
						if(!empty($deplacement_ponctuel[$idw])) {
							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Deplacement ponctuel semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : Non ➔ Oui';
						}
						if($moyen_transport[$idw] != 0) {
							$moyen_transport_after = $deplacement->fields['moyen_transport']['arrayofkeyval'][$moyen_transport[$idw]];

							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Moyen de transport semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : / ➔ '.$moyen_transport_after;
						}
						if($type_deplacement[$idw] != 0) {
							$type_deplacement_after = $deplacement->fields['type_deplacement']['arrayofkeyval'][$type_deplacement[$idw]];

							$deplacement->note = (!empty($deplacement->note) ? $deplacement->note.' / ' : '');
							$deplacement->note .= 'Modification Type Trajet semaine anticipée (le '.dol_print_date(dol_now(), '%d/%m/%Y').' par '.$user->login.') : / ➔ '.$type_deplacement_after;
						}
					}

					$deplacement->domicile = $domicile[$idw];
					$deplacement->deplacement_local = $deplacement_local[$idw];
					$deplacement->deplacement_ponctuel = $deplacement_ponctuel[$idw];
					$deplacement->deplacement_calendaire = $deplacement_calendaire[$idw];
					$deplacement->deplacement_jour_retour = $deplacement_jour_retour[$idw];
					$deplacement->moyen_transport = $moyen_transport[$idw];
					$deplacement->type_deplacement = $type_deplacement[$idw];
					$deplacement->trajet_detente = $trajet_detente[$idw];
					$deplacement->fk_user = $usertoprocess->id;
					$deplacement->date = $dayinloopfromfirstdaytoshow;

					$result = $deplacement->create($user);
				}

				if ($result < 0) {
					setEventMessages($deplacement->error, $deplacement->errors, 'errors');
					$error++;
					break;
				}
			}
		}
	}

	if($observationFDT != $object->observation && $permissionToVerification) {
		$object->oldcopy = dol_clone($object);
		$object->actiontypecode = 'AC_FDT_VERIF';

		$object->observation = $observationFDT;
		$result = $object->update($user);
	}

	if (!$error) {
		$mail_hs = 0;

		$timeDoneByWeekAfter = $object->timeDoneByWeek(($object->fk_user ? $object->fk_user : $usertoprocess->id));

		foreach($timeDoneByWeekAfter as $semaine => $temps){
			if($temps > $heure_semaine_hs && $timeDoneByWeekBefore[$semaine] <= $heure_semaine_hs){ // Si il y a une semaine avec des hs et que ce n'était pas le cas avant les modifications
				$mail_hs = 1;
			}
		}

		// Envoi du mail si enregistrement de + de 35h ou - de 35h
		if($mail_hs && $object->status == FeuilleDeTemps::STATUS_DRAFT){
			$user_static = new User($db);

			// Le mail est envoyé aux responsables de tâche sur lequel l'utilisateur a pointé
			$to = "";
			foreach($timetoadd as $taskid => $val){
				$task->fetch($taskid);
				$liste_responsables_taches = $task->liste_contact(-1, 'internal', 1, 'TASKEXECUTIVE');

				foreach($liste_responsables_taches as $responsable_tache){
					$user_static->fetch($responsable_tache);
					if($user_static->statut == 1 && !empty($user_static->email)){
						$to .= $user_static->email.', ';
					}
				}
			}
			$to = rtrim($to, ", ");

			global $dolibarr_main_url_root;
			$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
			$from = 'erp@optim-industries.fr';
			$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
			$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">ici</a>';
			// if($mail_hs && $mail_hm) {
			// 	$msg = $langs->transnoentitiesnoconv("EMailTextHSAndHM", $usertoprocess->firstname, $usertoprocess->lastname, dol_print_date($object->date_fin, '%B'), $link);
			// }
			$msg = $langs->transnoentitiesnoconv("EMailTextHS", $usertoprocess->firstname, $usertoprocess->lastname, dol_print_date($object->date_fin, '%B'), $link);
			// elseif ($mail_hm) {
			// 	$msg = $langs->transnoentitiesnoconv("EMailTextHM", $usertoprocess->firstname, $usertoprocess->lastname, dol_print_date($object->date_fin, '%B'), $link);
			// }
			
			$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
			if (!empty($to)){
				$res = $mail->sendfile();
			}			
		}

		// Si le feuille de temps existe et que des modifications ont été réalisé
		if($object->id > 0 && !empty($modification)){
			$modification = '<ul>'.$modification.'</ul>';

			$object->actiontypecode = 'AC_OTH_AUTO';
			$object->actionmsg2 = "Mise à jour des données de la feuille de temps $object->ref";
			$object->actionmsg = $modification;
			$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);

			// Mail lors de modification des temps après une 1er validation
			// if($object->id) {
			// 	$list_resp_task = $object->listApprover1;
			// 	if(in_array(1, $list_resp_task[1])){
			// 		$resp_task_valide = 1;
			// 	}
			// 	else {
			// 		$resp_task_valide = 0;
			// 	}

			// 	$to = '';
			// 	if(($object->status == FeuilleDeTemps::STATUS_APPROBATION1 && $resp_task_valide) || $object->status == FeuilleDeTemps::STATUS_APPROBATION2 || $object->status == FeuilleDeTemps::STATUS_VALIDATED){
			// 		$user_static = new User($db);

			// 		$user_static->fetch($object->fk_user);
			// 		if(!empty($user_static->email)){
			// 			$to .= $user_static->email.', ';
			// 		}

			// 		$list_validation = $object->listApprover1;
			// 		foreach($list_validation[2] as $id => $user_static){
			// 			if(!empty($user_static->email) && $list_validation[1][$id] == 1){
			// 				$to .= $user_static->email.', ';
			// 			}
			// 		}

			// 		if($object->status == FeuilleDeTemps::STATUS_VALIDATED || $object->status == FeuilleDeTemps::STATUS_APPROBATION2){
			// 			$list_validation = $object->listApprover2;
			// 			foreach($list_validation[2] as $id => $user_static){
			// 				if(!empty($user_static->email) && $list_validation[1][$id] == 1){
			// 					$to .= $user_static->email.', ';
			// 				}
			// 			}
			// 		}
			// 	}
			// 	$to = rtrim($to, ", ");

			// 	global $dolibarr_main_url_root;
			// 	$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
			// 	$from = 'erp@optim-industries.fr';
			// 	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
			// 	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
			// 	$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">'.$object->ref.'</a>';

			// 	if($modification != '<ul>' && $modification_deplacement != '<ul>') {
			// 		$msg = $langs->transnoentitiesnoconv("EMailTextModifHeure", $link, $modification.$modification_deplacement);
			// 	}
			// 	else if($modification_deplacement != '<ul>') {
			// 		$msg = $langs->transnoentitiesnoconv("EMailTextModifHeure", $link, $modification_deplacement);
			// 	}
			// 	else if($modification != '<ul>') {
			// 		$msg = $langs->transnoentitiesnoconv("EMailTextModifHeure", $link, $modification);
			// 	}

			// 	$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
			// 	if (!empty($to) && !empty($msg)){
			// 		$res = $mail->sendfile();
			// 	}
			// }
		}

		if(strpos($_SERVER["PHP_SELF"], 'feuilledetemps_card') === false) {
			$param = '';
			$param .= ($mode ? '&mode='.urlencode($mode) : '');
			$param .= ($projectid ? 'id='.urlencode($projectid) : '');
			$param .= ($search_usertoprocessid ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
			$param .= ($day ? '&day='.urlencode($day) : '').($month ? '&month='.urlencode($month) : '').($year ? '&year='.urlencode($year) : '');
			$param .= ($search_project_ref ? '&search_project_ref='.urlencode($search_project_ref) : '');
			$param .= ($search_usertoprocessid > 0 ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
			$param .= ($search_thirdparty ? '&search_thirdparty='.urlencode($search_thirdparty) : '');
			$param .= ($search_declared_progress ? '&search_declared_progress='.urlencode($search_declared_progress) : '');
			$param .= ($search_task_ref ? '&search_task_ref='.urlencode($search_task_ref) : '');
			$param .= ($search_task_label ? '&search_task_label='.urlencode($search_task_label) : '');
			$param .= ($showFav ? '&showFav=1' : '');

			$search_array_options=$search_array_options_project;
			$search_options_pattern='search_options_';
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

			$search_array_options = $search_array_options_task;
			$search_options_pattern = 'search_task_options_';
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
		}

		if($massaction != "transmettre") {
			if(strpos($_SERVER["PHP_SELF"], 'feuilledetemps_card') === false) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				// Redirect to avoid submit twice on back
				header('Location: '.$_SERVER["PHP_SELF"].'?'.$param);
				exit;
			}
			else {
				if($permissionToVerification && $object->status == $object::STATUS_VERIFICATION) {
					$action = 'addtimeVerification';
				}
				else {
					setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
					// Redirect to avoid submit twice on back
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			}
		}
	}
	
}

// Modifier l'observation lorsque la feuille de temps est exportée
if ($action == 'updateObservation' && $permissionToVerification) {
	$observationFDT = GETPOST('observationFDT');

	if($observationFDT != $object->observation) {
		$object->oldcopy = dol_clone($object);
		$object->actiontypecode = 'AC_FDT_VERIF';

		$object->observation = $observationFDT;
		$result = $object->update($user);

		if($result < 0) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		// Redirect to avoid submit twice on back
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
	
}

// Enregistrement des vérifications
if ($conf->global->FDT_DISPLAY_COLUMN && $action == 'addtimeVerification' && GETPOST('formfilteraction') != 'listafterchangingselectedfields' && $massaction != 'validate1' && $massaction != 'validate2' && $massaction != 'verification' && $massaction != 'refus') {
	$holiday = new extendedHoliday($db);
	$modification = '';

	// $regul = new Regul($db);
	// $resregul = $regul->fetchWithoutId($first_day_month, $usertoprocess->id, 1);
	
	$holiday_type = $_POST['holiday_type'];
	$holiday_id = $_POST['holiday_id'];
	$holiday_valide = $_POST['holiday_valide'];

	// $regulD1 = ($_POST['regulD1'] > 0 || $_POST['regulD1'] < 0 ? $_POST['regulD1'] : null);
	// $regulD2 = ($_POST['regulD2'] > 0 || $_POST['regulD2'] < 0  ? $_POST['regulD2'] : null);
	// $regulD3 = ($_POST['regulD3'] > 0 || $_POST['regulD3'] < 0  ? $_POST['regulD3'] : null);
	// $regulD4 = ($_POST['regulD4'] > 0 || $_POST['regulD4'] < 0  ? $_POST['regulD4'] : null);
	// $regulGD1 = ($_POST['regulGD1'] > 0 || $_POST['regulGD1'] < 0 ? $_POST['regulGD1'] : null);
	// $regulGD2 = ($_POST['regulGD2'] > 0 || $_POST['regulGD2'] < 0  ? $_POST['regulGD2'] : null);
	// $regulHeureRoute = ($_POST['regulHeureRoute'] > 0 || $_POST['regulHeureRoute'] < 0  ? $_POST['regulHeureRoute'] : null);
	// $regulRepas1 = ($_POST['regulRepas1'] > 0 || $_POST['regulRepas1'] < 0  ? $_POST['regulRepas1'] : null);
	// $regulRepas2 = ($_POST['regulRepas2'] > 0 || $_POST['regulRepas2'] < 0  ? $_POST['regulRepas2'] : null);
	// $regulKilometres = ($_POST['regulKilometres'] > 0 || $_POST['regulKilometres'] < 0  ? price2num($_POST['regulKilometres']) : null);
	// $regulIndemniteTT = ($_POST['regulIndemniteTT'] > 0 || $_POST['regulIndemniteTT'] < 0  ? $_POST['regulIndemniteTT'] : null);
	// $regulHeureSup00 = ($regul->heure_sup00 != 0 ? (double)$regul->heure_sup00 : 0);
	// $regulHeureSup25 = ($regul->heure_sup25 != 0 ? (double)$regul->heure_sup25 : 0);
	// $regulHeureSup50 = ($regul->heure_sup50 != 0 ? (double)$regul->heure_sup50 : 0);
	// $regulHeureSup50HT = ($regul->heure_sup50ht != 0 ? (double)$regul->heure_sup50ht : 0);

	// Gestion des congés 
	foreach($holiday_type as $key => $type) {
		$holiday->fetch($holiday_id[$key]);
		
		if($holiday->fk_type != $type) {
			$needHour = $holiday->holidayTypeNeedHour($type);

			if($needHour && date("W", $holiday->date_debut) != date("W", $holiday->date_fin)) {
				setEventMessages($langs->trans("ErrorWeekHoliday"), null, 'errors');
				$error++;
				break;
			}

			if($needHour && $holiday->date_debut != $holiday->date_fin && $holiday->halfday != 0) {
				setEventMessages($langs->trans("ErrorHalfdayHoliday"), null, 'errors');
				$error++;
				break;
			}

			// If no hour and hour is required
			if (empty($holiday->array_options['options_hour']) && $needHour == 1) {				
				$holiday->array_options['options_hour'] = $holiday->getHourDuration($standard_week_hour, $holiday->date_debut);
			}
			elseif(!empty($holiday->array_options['options_hour']) && !$needHour) {
				$holiday->array_options['options_hour'] = null;
			}

			if(!$error) {
				$holiday->fk_type = $type;
				$result = $holiday->updateExtended($user);

				if ($result < 0) {
					setEventMessages($holiday->error, $holiday->errors, 'errors');
					$error++;
					break;
				}
			}
		}

		if($conf->global->FDT_STATUT_HOLIDAY && $holiday_valide[$key] && $holiday->array_options['options_statutfdt'] == 1 && !$error) {
			$exclude_type = explode(",", $conf->global->HOLIDAYTYPE_EXLUDED_EXPORT);
			if(in_array($holiday->fk_type, $exclude_type)) {
				$holiday->array_options['options_statutfdt'] = 3;
			}
			else {
				$holiday->array_options['options_statutfdt'] = 2;
			}
			$result = $holiday->updateExtended($user);
		}

		if($conf->global->FDT_STATUT_HOLIDAY && empty($holiday_valide[$key]) && $holiday->array_options['options_statutfdt'] == 2 && !$error) {
			$holiday->array_options['options_statutfdt'] = 1;
			$result = $holiday->updateExtended($user);
		}
	}

	$timeSpentWeek = $object->timeDoneByWeek($usertoprocess->id);
	$timeHoliday = $object->timeHolidayWeek($object->fk_user, $standard_week_hour);

	for ($idw = 0; $idw < $nb_jour; $idw++) {
		$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];

		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0*
		$silae = new Silae($db);
	
		// Calcul auto des heures sup
		if((!empty(GETPOST('task')) || !empty(GETPOST('heure_nuit')) || !empty($holiday_type)) && dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
			$res = $silae->fetchSilaeWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);
			$heure_sup00_before = $silae->heure_sup00;
			$heure_sup25_before = $silae->heure_sup25;
			$heure_sup50_before = $silae->heure_sup50;
			$heure_sup50ht_before = $silae->heure_sup50ht;

			$silae->date = $dayinloopfromfirstdaytoshow;
			$silae->fk_user = $object->fk_user;
			$silae->calculHS($heure_semaine, $heure_semaine_hs, $timeSpentWeek, $timeHoliday, $dayinloopfromfirstdaytoshow);

			if($heure_sup00_before != $silae->heure_sup00) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup00 / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup00_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 0%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Heure Sup 25%
			if($heure_sup25_before != $silae->heure_sup25) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup25 / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup25_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 25%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Heure Sup 50%
			if($heure_sup50_before != $silae->heure_sup50) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup50 / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup50_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 50%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Heure Sup 50% HT
			if($heure_sup50ht_before != $silae->heure_sup50ht) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup50ht / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup50ht_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 50% HT</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}
			
			if($dayinloopfromfirstdaytoshow < $first_day_month) {
				$regulHeureSup00 += ((double)$silae->heure_sup00 - (double)$heure_sup00_before);
				$regulHeureSup25 += ((double)$silae->heure_sup25 - (double)$heure_sup25_before);
				$regulHeureSup50 += ((double)$silae->heure_sup50 - (double)$heure_sup50_before);
				$regulHeureSup50HT += ((double)$silae->heure_sup50ht - (double)$heure_sup50ht_before);
			}

			if($res > 0) {
				$silae->update($user);
			}
			elseif($res == 0) {
				$silae->create($user);
			}
			else {
				$error++;
			}
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');

		// Si le feuille de temps existe et que des modifications ont été réalisé
		if($object->id > 0 && !empty($modification)){
			$modification = '<ul>'.$modification.'</ul>';

			$object->actiontypecode = 'AC_FDT_VERIF';
			$object->actionmsg2 = ($object->status == 4 ? "Mise à jour des données lors de la vérification de la feuille de temps $object->ref" : "Mise à jour des données après la vérification de la feuille de temps $object->ref");
			$object->actionmsg = $modification;
			$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
		}

		// Redirect to avoid submit twice on back
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
	
}
elseif (!$conf->global->FDT_DISPLAY_COLUMN && $action == 'addtimeVerification' && GETPOST('formfilteraction') != 'listafterchangingselectedfields' && $massaction != 'validate1' && $massaction != 'validate2' && $massaction != 'verification' && $massaction != 'refus') {
	$holiday = new extendedHoliday($db);
	$regul = new Regul($db);
	$resregul = $regul->fetchWithoutId($first_day_month, $usertoprocess->id, 1);
	$modification = '';

	// $heure_sup00 = $_POST['heure_sup00'];
	// $heure_sup25 = $_POST['heure_sup25'];
	// $heure_sup50 = $_POST['heure_sup50'];
	$heure_route = $_POST['heure_route'];
	$heure_nuit = $_POST['heure_nuit_verif'];
	$petit_deplacement = $_POST['petit_deplacement'];
	$grand_deplacement = $_POST['grand_deplacement'];
	$repas = $_POST['repas'];
	$kilometres = $_POST['kilometres'];
	$indemnite_tt = $_POST['indemnite_tt'];

	$holiday_type = $_POST['holiday_type'];
	$holiday_id = $_POST['holiday_id'];
	$holiday_valide = $_POST['holiday_valide'];

	$regulD1 = ($_POST['regulD1'] > 0 || $_POST['regulD1'] < 0 ? $_POST['regulD1'] : null);
	$regulD2 = ($_POST['regulD2'] > 0 || $_POST['regulD2'] < 0  ? $_POST['regulD2'] : null);
	$regulD3 = ($_POST['regulD3'] > 0 || $_POST['regulD3'] < 0  ? $_POST['regulD3'] : null);
	$regulD4 = ($_POST['regulD4'] > 0 || $_POST['regulD4'] < 0  ? $_POST['regulD4'] : null);
	$regulGD1 = ($_POST['regulGD1'] > 0 || $_POST['regulGD1'] < 0 ? $_POST['regulGD1'] : null);
	$regulGD2 = ($_POST['regulGD2'] > 0 || $_POST['regulGD2'] < 0  ? $_POST['regulGD2'] : null);
	$regulHeureRoute = ($_POST['regulHeureRoute'] > 0 || $_POST['regulHeureRoute'] < 0  ? $_POST['regulHeureRoute'] : null);
	$regulRepas1 = ($_POST['regulRepas1'] > 0 || $_POST['regulRepas1'] < 0  ? $_POST['regulRepas1'] : null);
	$regulRepas2 = ($_POST['regulRepas2'] > 0 || $_POST['regulRepas2'] < 0  ? $_POST['regulRepas2'] : null);
	$regulKilometres = ($_POST['regulKilometres'] > 0 || $_POST['regulKilometres'] < 0  ? price2num($_POST['regulKilometres']) : null);
	$regulIndemniteTT = ($_POST['regulIndemniteTT'] > 0 || $_POST['regulIndemniteTT'] < 0  ? $_POST['regulIndemniteTT'] : null);
	$HeureNuit50 = ($_POST['HeureNuit50'] > 0 || $_POST['HeureNuit50'] < 0  ? $_POST['HeureNuit50'] : null);
	$HeureNuit75 = ($_POST['HeureNuit75'] > 0 || $_POST['HeureNuit75'] < 0  ? $_POST['HeureNuit75'] : null);
	$HeureNuit100 = ($_POST['HeureNuit100'] > 0 || $_POST['HeureNuit100'] < 0  ? $_POST['HeureNuit100'] : null);
	// $regulHeureSup00 = ($_POST['regulHeureSup00'] > 0 || $_POST['regulHeureSup00'] < 0  ? $_POST['regulHeureSup00'] : null);
	// $regulHeureSup25 = ($_POST['regulHeureSup25'] > 0 || $_POST['regulHeureSup25'] < 0  ? $_POST['regulHeureSup25'] : null);
	// $regulHeureSup50 = ($_POST['regulHeureSup50'] > 0 || $_POST['regulHeureSup50'] < 0  ? $_POST['regulHeureSup50'] : null);
	
	$regulHeureSup00 = ($regul->heure_sup00 != 0 ? (double)$regul->heure_sup00 : 0);
	$regulHeureSup25 = ($regul->heure_sup25 != 0 ? (double)$regul->heure_sup25 : 0);
	$regulHeureSup50 = ($regul->heure_sup50 != 0 ? (double)$regul->heure_sup50 : 0);
	$regulHeureSup50HT = ($regul->heure_sup50ht != 0 ? (double)$regul->heure_sup50ht : 0);

	$prime_astreinte = ($_POST['prime_astreinte'] > 0 ? price2num($_POST['prime_astreinte'], 2) : null);
	$prime_exceptionnelle = ($_POST['prime_exceptionnelle'] > 0 ? price2num($_POST['prime_exceptionnelle'], 2) : null);
	$prime_objectif = ($_POST['prime_objectif'] > 0 ? price2num($_POST['prime_objectif'], 2) : null);
	$prime_variable = ($_POST['prime_variable'] > 0 ? price2num($_POST['prime_variable'], 2) : null);
	$prime_amplitude = ($_POST['prime_amplitude'] > 0 ? price2num($_POST['prime_amplitude'], 2) : null);

	if (!empty($HeureNuit50)) {
		$newdurationHeureNuit50 = price2num($HeureNuit50, 2) * 3600;
	}

	if (!empty($HeureNuit75)) {
		$newdurationHeureNuit75 = price2num($HeureNuit75, 2) * 3600;
	}

	if (!empty($HeureNuit100)) {
		$newdurationHeureNuit100 = price2num($HeureNuit100, 2) * 3600;
	}

	if (!empty($regulHeureRoute)) {
		$newdurationHeureRoute = price2num($regulHeureRoute, 2) * 3600;
	}

	// if (!empty($regulHeureSup00)) {
	// 	$regulHeureSup00 = price2num($regulHeureSup00, 2) * 3600;
	// }

	// if (!empty($regulHeureSup25)) {
	// 	$regulHeureSup25 = price2num($regulHeureSup25, 2) * 3600;
	// }

	// if (!empty($regulHeureSup50)) {
	// 	$regulHeureSup50 = price2num($regulHeureSup50, 2) * 3600;
	// }

	// Gestion des congés 
	foreach($holiday_type as $key => $type) {
		$holiday->fetch($holiday_id[$key]);
		
		if($holiday->fk_type != $type) {
			$needHour = $holiday->holidayTypeNeedHour($type);

			if($needHour && date("W", $holiday->date_debut) != date("W", $holiday->date_fin)) {
				setEventMessages($langs->trans("ErrorWeekHoliday"), null, 'errors');
				$error++;
				break;
			}

			if($needHour && $holiday->date_debut != $holiday->date_fin && $holiday->halfday != 0) {
				setEventMessages($langs->trans("ErrorHalfdayHoliday"), null, 'errors');
				$error++;
				break;
			}

			// If no hour and hour is required
			if (empty($holiday->array_options['options_hour']) && $needHour == 1) {
				$holiday->array_options['options_hour'] = $holiday->getHourDuration($standard_week_hour, $holiday->date_debut);
			}
			elseif(!empty($holiday->array_options['options_hour']) && !$needHour) {
				$holiday->array_options['options_hour'] = null;
			}

			if(!$error) {
				$holiday->fk_type = $type;
				$result = $holiday->updateExtended($user);

				if ($result < 0) {
					setEventMessages($holiday->error, $holiday->errors, 'errors');
					$error++;
					break;
				}
			}
		}

		if($conf->global->FDT_STATUT_HOLIDAY && $holiday_valide[$key] && $holiday->array_options['options_statutfdt'] == 1 && !$error) {
			$exclude_type = explode(",", $conf->global->HOLIDAYTYPE_EXLUDED_EXPORT);
			if(in_array($holiday->fk_type, $exclude_type)) {
				$holiday->array_options['options_statutfdt'] = 3;
			}
			else {
				$holiday->array_options['options_statutfdt'] = 2;
			}
			$result = $holiday->updateExtended($user);
		}

		if($conf->global->FDT_STATUT_HOLIDAY && empty($holiday_valide[$key]) && $holiday->array_options['options_statutfdt'] == 2 && !$error) {
			$holiday->array_options['options_statutfdt'] = 1;
			$result = $holiday->updateExtended($user);
		}
	}


	$timeSpentWeek = $object->timeDoneByWeek($usertoprocess->id);
	$timeHoliday = $object->timeHolidayWeek($object->fk_user, $standard_week_hour);

	for ($idw = 0; $idw < $nb_jour; $idw++) {

		$tmpday = $dayinloopfromfirstdaytoshow_array[$idw];

		$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw]; // $firstdaytoshow is a date with hours = 0*
		$silae = new Silae($db);
	
		// $newduration_heure_sup00 = 0;
		// if (!empty($heure_sup00[$idw])) {
		// 	$newduration_heure_sup00 = price2num($heure_sup00[$idw], 2) * 3600;
		// }

		// $newduration_heure_sup25 = 0;
		// if (!empty($heure_sup25[$idw])) {
		// 	$newduration_heure_sup25 = price2num($heure_sup25[$idw], 2) * 3600;
		// }

		// $newduration_heure_sup50 = 0;
		// if (!empty($heure_sup50[$idw])) {
		// 	$newduration_heure_sup50 = price2num($heure_sup50[$idw], 2) * 3600;
		// }

		$newduration_heure_nuit = 0;
		if (!empty($heure_nuit[$idw])) {
			$newduration_heure_nuit = price2num($heure_nuit[$idw], 2) * 3600;
		}

		$newduration_heure_route = 0;
		if (!empty($heure_route[$idw])) {
			$newduration_heure_route = price2num($heure_route[$idw], 2) * 3600;
		}


		// Calcul auto des heures sup
		if((!empty($_POST['task']) || !empty($holiday_type)) && dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
			$silae = new Silae($db);
			$res = $silae->fetchSilaeWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);
			$heure_sup00_before = $silae->heure_sup00;
			$heure_sup25_before = $silae->heure_sup25;
			$heure_sup50_before = $silae->heure_sup50;
			$heure_sup50ht_before = $silae->heure_sup50ht;

			$silae->date = $dayinloopfromfirstdaytoshow;
			$silae->fk_user = $object->fk_user;
			$silae->calculHS($heure_semaine, $heure_semaine_hs, $timeSpentWeek, $timeHoliday, $dayinloopfromfirstdaytoshow);
			
			if($heure_sup00_before != $silae->heure_sup00) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup00 / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup00_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 0%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Heure Sup 25%
			if($heure_sup25_before != $silae->heure_sup25) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup25 / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup25_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 25%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Heure Sup 50%
			if($heure_sup50_before != $silae->heure_sup50) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup50 / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup50_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 50%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Heure Sup 50% HT
			if($heure_sup50ht_before != $silae->heure_sup50ht) {
				$new_value = formatValueForAgenda('double', $silae->heure_sup50ht / 3600);
				$old_value = formatValueForAgenda('double', $heure_sup50ht_before / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 50% HT</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}
			
			if($dayinloopfromfirstdaytoshow < $first_day_month) {
				$regulHeureSup00 += ((double)$silae->heure_sup00 - (double)$heure_sup00_before);
				$regulHeureSup25 += ((double)$silae->heure_sup25 - (double)$heure_sup25_before);
				$regulHeureSup50 += ((double)$silae->heure_sup50 - (double)$heure_sup50_before);
				$regulHeureSup50HT += ((double)$silae->heure_sup50ht - (double)$heure_sup50ht_before);
			}

			if($res > 0) {
				$silae->update($user);
			}
			elseif($res == 0) {
				$silae->create($user);
			}
			else {
				$error++;
			}
		}

		$res = $silae->fetchSilaeWithoutId($dayinloopfromfirstdaytoshow, $usertoprocess->id);
		if(/*$newduration_heure_sup00 != $silae->heure_sup00 || $newduration_heure_sup25 != $silae->heure_sup25 || $newduration_heure_sup50 != $silae->heure_sup50 || */$newduration_heure_nuit != $silae->heure_nuit || $newduration_heure_route != $silae->heure_route || $repas[$idw] != $silae->repas || $kilometres[$idw] != $silae->kilometre || $indemnite_tt[$idw] != $silae->indemnite_tt) {
			// Agenda Heure Sup 0%
			// if($newduration_heure_sup00 != $silae->heure_sup00) {
			// 	$new_value = formatValueForAgenda('double', $newduration_heure_sup00);
			// 	$old_value = formatValueForAgenda('double', $silae->heure_sup00);

			// 	$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 0%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			// }

			// // Agenda Heure Sup 25%
			// if($newduration_heure_sup25 != $silae->heure_sup25) {
			// 	$new_value = formatValueForAgenda('double', $newduration_heure_sup25);
			// 	$old_value = formatValueForAgenda('double', $silae->heure_sup25);

			// 	$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 25%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			// }

			// // Agenda Heure Sup 50%
			// if($newduration_heure_sup50 != $silae->heure_sup50) {
			// 	$new_value = formatValueForAgenda('double', $newduration_heure_sup50);
			// 	$old_value = formatValueForAgenda('double', $silae->heure_sup50);

			// 	$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 50%</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			// }

			// Agenda Heure Nuit
			if($newduration_heure_nuit != $silae->heure_nuit) {
				$new_value = formatValueForAgenda('double', $newduration_heure_nuit / 3600);
				$old_value = formatValueForAgenda('double', $silae->heure_nuit / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Nuit</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Heure Route
			if($newduration_heure_route != $silae->heure_route) {
				$new_value = formatValueForAgenda('double', $newduration_heure_route / 3600);
				$old_value = formatValueForAgenda('double', $silae->heure_route / 3600);

				$modification .= ($old_value != $new_value ? '<li><strong>Heure Route</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Repas
			if($repas[$idw] != $silae->repas) {
				$new_value = formatValueForAgenda('int', $repas[$idw], $silae, 'repas');
				$old_value = formatValueForAgenda('int', $silae->repas, $silae, 'repas');

				$modification .= ($old_value != $new_value ? '<li><strong>Repas</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Kilomètres
			if(price2num($kilometres[$idw], 2) != price2num($silae->kilometres, 2)) {
				$new_value = formatValueForAgenda('double', $kilometres[$idw]);
				$old_value = formatValueForAgenda('double', $silae->kilometres);

				$modification .= ($old_value != $new_value ? '<li><strong>Kilomètres</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// Agenda Indemnite TT
			if($indemnite_tt[$idw] != $silae->indemnite_tt) {
				$new_value = formatValueForAgenda('boolean', $indemnite_tt[$idw]);
				$old_value = formatValueForAgenda('boolean', $silae->indemnite_tt);

				$modification .= ($old_value != $new_value ? '<li><strong>Indemnité TT</strong> ('.dol_print_date($tmpday, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
			}

			// S'il existe une ligne et que tous les champs sont = null
			if($res > 0 && /*(empty($newduration_heure_sup00) || $newduration_heure_sup00 == 0) && (empty($newduration_heure_sup25) || $newduration_heure_sup25 == 0) && (empty($newduration_heure_sup50) || $newduration_heure_sup50 == 0) && */
			(empty($newduration_heure_nuit) || $newduration_heure_nuit == 0) && (empty($newduration_heure_route) || $newduration_heure_route == 0) && (empty($repas[$idw]) || $repas[$idw] == 0) && 
			(empty($kilometres[$idw]) || $kilometres[$idw] == 0) && (empty($indemnite_tt[$idw]) || $indemnite_tt[$idw] == 0) && empty($silae->heure_sup00) && empty($silae->heure_sup25) && empty($silae->heure_sup50) && empty($silae->heure_sup50ht)) {
				$result = $silae->delete($user);
			}
			// S'il existe une ligne et qu'au moins un champ a été modifié
			elseif($res > 0 && (/*$newduration_heure_sup00 != $silae->heure_sup00 || $newduration_heure_sup25 != $silae->heure_sup25 || $newduration_heure_sup50 != $silae->heure_sup50 || */
			$newduration_heure_nuit != $silae->heure_nuit || $newduration_heure_route != $silae->heure_route || $repas[$idw] != $silae->repas || $kilometres[$idw] != $silae->kilometres || $indemnite_tt[$idw] != $silae->indemnite_tt)) {
				//$silae->heure_sup00 = $newduration_heure_sup00;
				//$silae->heure_sup25 = $newduration_heure_sup25;
				//$silae->heure_sup50 = $newduration_heure_sup50;
				$silae->heure_nuit = $newduration_heure_nuit;
				$silae->heure_route = $newduration_heure_route;
				$silae->repas = $repas[$idw];
				$silae->kilometres = ($kilometres[$idw] ? price2num($kilometres[$idw], 2) : '');
				$silae->indemnite_tt = $indemnite_tt[$idw];

				$result = $silae->update($user);
			}
			// S'il n'existe pas de ligne et qu'au moins un champ est différent de null
			elseif($res == 0 && (/*!empty($newduration_heure_sup00) || !empty($newduration_heure_sup25) || !empty($newduration_heure_sup50) || */
			!empty($newduration_heure_nuit) || !empty($newduration_heure_route) || !empty($repas[$idw]) || !empty($kilometres[$idw]) || !empty($indemnite_tt[$idw]))) {
				// $silae->heure_sup00 = $newduration_heure_sup00;
				// $silae->heure_sup25 = $newduration_heure_sup25;
				// $silae->heure_sup50 = $newduration_heure_sup50;
				$silae->heure_nuit = $newduration_heure_nuit;
				$silae->heure_route = $newduration_heure_route;
				$silae->repas = $repas[$idw];
				$silae->kilometres = ($kilometres[$idw] ? price2num($kilometres[$idw], 2) : '');
				$silae->indemnite_tt = $indemnite_tt[$idw];
				$silae->fk_user = $usertoprocess->id;
				$silae->date = $tmpday;

				$result = $silae->create($user);
			}

			if ($result < 0) {
				setEventMessages($silae->error, $silae->errors, 'errors');
				$error++;
				break;
			}
		}
	}

	if($regulD1 != $regul->d1 || $regulD2 != $regul->d2 || $regulD3 != $regul->d3 || $regulD4 != $regul->d4 || $regulGD1 != $regul->gd1 || $regulGD2 != $regul->gd2 
	|| $regulRepas1 != $regul->repas1 || $regulRepas2 != $regul->repas2 || $regulKilometres != $regul->kilometres || $regulIndemniteTT != $regul->indemnite_tt 
	|| $newdurationHeureNuit50 != $regul->heure_nuit_50 || $newdurationHeureNuit75 != $regul->heure_nuit_75 || $newdurationHeureNuit100 != $regul->heure_nuit_100 
	|| $regulHeureSup00 != $regul->heure_sup00 || $regulHeureSup25 != $regul->heure_sup25 || $regulHeureSup50 != $regul->heure_sup50 || $regulHeureSup50HT != $regul->heure_sup50ht
	|| $newdurationHeureRoute != $regul->heure_route) {
		// Agenda Regul D1
		if((int)$regulD1 != (int)$regul->d1) {
			$new_value = formatValueForAgenda('int', $regulD1);
			$old_value = formatValueForAgenda('int', $regul->d1);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul D1</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul D2
		if((int)$regulD2 != (int)$regul->d2) {
			$new_value = formatValueForAgenda('int', $regulD2);
			$old_value = formatValueForAgenda('int', $regul->d2);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul D2</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul D3
		if((int)$regulD3 != (int)$regul->d3) {
			$new_value = formatValueForAgenda('int', $regulD3);
			$old_value = formatValueForAgenda('int', $regul->d3);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul D3</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul D4
		if((int)$regulD4 != (int)$regul->d4) {
			$new_value = formatValueForAgenda('int', $regulD4);
			$old_value = formatValueForAgenda('int', $regul->d4);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul D4</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul GD1
		if((int)$regulGD1 != (int)$regul->gd1) {
			$new_value = formatValueForAgenda('int', $regulGD1);
			$old_value = formatValueForAgenda('int', $regul->gd1);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul GD1</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul GD2
		if((int)$regulGD2 != (int)$regul->gd2) {
			$new_value = formatValueForAgenda('int', $regulGD2);
			$old_value = formatValueForAgenda('int', $regul->gd2);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul GD2</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul Repas 1
		if((int)$regulRepas1 != (int)$regul->repas1) {
			$new_value = formatValueForAgenda('int', $regulRepas1);
			$old_value = formatValueForAgenda('int', $regul->repas1);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Repas 1</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul Repas 2
		if((int)$regulRepas2 != (int)$regul->repas2) {
			$new_value = formatValueForAgenda('int', $regulRepas2);
			$old_value = formatValueForAgenda('int', $regul->repas2);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Repas 2</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul Kilomètres
		if($regulKilometres != $regul->kilometres) {
			$new_value = formatValueForAgenda('double', $regulKilometres);
			$old_value = formatValueForAgenda('double', $regul->kilometres);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Kilomètres</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Regul Indemnite TT
		if((int)$regulIndemniteTT != (int)$regul->indemnite_tt) {
			$new_value = formatValueForAgenda('int', $regulIndemniteTT);
			$old_value = formatValueForAgenda('int', $regul->indemnite_tt);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Indemnite de TT</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Nuit 50%
		if($newdurationHeureNuit50 != $regul->heure_nuit_50) {
			$new_value = formatValueForAgenda('double', $newdurationHeureNuit50 / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_nuit_50 / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Nuit 50%</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Nuit 75%
		if($newdurationHeureNuit75 != $regul->heure_nuit_75) {
			$new_value = formatValueForAgenda('double', $newdurationHeureNuit75 / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_nuit_75 / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Nuit 75%</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Nuit 100%
		if($newdurationHeureNuit100 != $regul->heure_nuit_100) {
			$new_value = formatValueForAgenda('double', $newdurationHeureNuit100 / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_nuit_100 / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Nuit 100%</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Route
		if($newdurationHeureRoute != $regul->heure_route) {
			$new_value = formatValueForAgenda('double', $newdurationHeureRoute / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_route / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Route</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Sup 0%
		if($regulHeureSup00 != $regul->heure_sup00) {
			$new_value = formatValueForAgenda('double', $regulHeureSup00 / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_sup00 / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 0%</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Sup 25%
		if($regulHeureSup25 != $regul->heure_sup25) {
			$new_value = formatValueForAgenda('double', $regulHeureSup25 / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_sup25 / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 25%</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Sup 50%
		if($regulHeureSup50 != $regul->heure_sup50) {
			$new_value = formatValueForAgenda('double', $regulHeureSup50 / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_sup50 / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 50%</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// Agenda Heure Sup 50% HT
		if($regulHeureSup50HT != $regul->heure_sup50ht) {
			$new_value = formatValueForAgenda('double', $regulHeureSup50HT / 3600);
			$old_value = formatValueForAgenda('double', $regul->heure_sup50ht / 3600);

			$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 50% HT</strong> : $old_value ➔ $new_value</li>" : '');
		}

		// S'il existe une ligne et que tous les champs sont = null
		if($resregul > 0 && (empty($regulD1) || $regulD1 == 0) && (empty($regulD2) || $regulD2 == 0) && (empty($regulD3) || $regulD3 == 0) && (empty($regulD4) || $regulD4 == 0) && (empty($regulGD1) || $regulGD1 == 0) && (empty($regulGD2) || $regulGD2 == 0) && (empty($newdurationHeureRoute) || $newdurationHeureRoute == 0) && (empty($regulRepas1) || $regulRepas1 == 0)
		&& (empty($regulRepas2) || $regulRepas2 == 0) && (empty($regulKilometres) || $regulKilometres == 0) && (empty($regulIndemniteTT) || $regulIndemniteTT == 0)
		&& (empty($regulHeureSup00) || $regulHeureSup00 == 0) && (empty($regulHeureSup25) || $regulHeureSup25 == 0) && (empty($regulHeureSup50) || $regulHeureSup50 == 0)
		&& (empty($newdurationHeureNuit50) || $newdurationHeureNuit50 == 0) && (empty($newdurationHeureNuit75) || $newdurationHeureNuit75 == 0) && (empty($newdurationHeureNuit100) || $newdurationHeureNuit100 == 0)) {
			$result = $regul->delete($user);
		}
		// S'il existe une ligne et qu'au moins un champ a été modifié
		elseif($resregul > 0 && ($regulD1 != $regul->d1 || $regulD2 != $regul->d2 || $regulD3 != $regul->d3 || $regulD4 != $regul->d4 || $regulGD1 != $regul->gd1 || $regulGD2 != $regul->gd2 ||
		$newdurationHeureRoute != $regul->heure_route || $regulRepas1 != $regul->repas1 || $regulRepas2 != $regul->repas2 || $regulIndemniteTT != $regul->indemnite_tt || $regulKilometres != $regul->kilometres
		|| $newdurationHeureNuit50 != $regul->heure_nuit_50	 || $newdurationHeureNuit75 != $regul->heure_nuit_75 || $newdurationHeureNuit100 != $regul->heure_nuit_100
		|| $regulHeureSup00 != $regul->heure_sup00 || $regulHeureSup25 != $regul->heure_sup25 || $regulHeureSup50 != $regul->heure_sup50 || $regulHeureSup50HT != $regul->heure_sup50ht)) {
			$regul->d1 = $regulD1;
			$regul->d2 = $regulD2;
			$regul->d3 = $regulD3;
			$regul->d4 = $regulD4;
			$regul->gd1 = $regulGD1;
			$regul->gd2 = $regulGD2;
			$regul->heure_route = $newdurationHeureRoute;
			$regul->repas1 = $regulRepas1;
			$regul->repas2 = $regulRepas2;
			$regul->indemnite_tt = $regulIndemniteTT;
			$regul->kilometres = $regulKilometres;
			$regul->heure_nuit_50 = $newdurationHeureNuit50;
			$regul->heure_nuit_75 = $newdurationHeureNuit75;
			$regul->heure_nuit_100 = $newdurationHeureNuit100;
			$regul->heure_sup00 = $regulHeureSup00;
			$regul->heure_sup25 = $regulHeureSup25;
			$regul->heure_sup50 = $regulHeureSup50;
			$regul->heure_sup50ht = $regulHeureSup50HT;

			$result = $regul->update($user);
		}
		// S'il n'existe pas de ligne et qu'au moins un champ est différent de null
		elseif($resregul == 0 && (!empty($regulD1) || !empty($regulD2) || !empty($regulD3) || !empty($regulD4) || !empty($regulGD1) || !empty($regulGD2) || !empty($newdurationHeureRoute) || !empty($regulRepas1)
		|| !empty($regulRepas2) || !empty($regulKilometres) || !empty($regulIndemniteTT) || !empty($newdurationHeureNuit50) || !empty($newdurationHeureNuit75) || !empty($newdurationHeureNuit100)
		|| !empty($regulHeureSup00) || !empty($regulHeureSup25)|| !empty($regulHeureSup50))) {
			$regul->date = $first_day_month;
			$regul->fk_user = $usertoprocess->id;
			$regul->d1 = $regulD1;
			$regul->d2 = $regulD2;
			$regul->d3 = $regulD3;
			$regul->d4 = $regulD4;
			$regul->gd1 = $regulGD1;
			$regul->gd2 = $regulGD2;
			$regul->heure_route = $newdurationHeureRoute;
			$regul->repas1 = $regulRepas1;
			$regul->repas2 = $regulRepas2;
			$regul->indemnite_tt = $regulIndemniteTT;
			$regul->kilometres = $regulKilometres;
			$regul->heure_nuit_50 = $newdurationHeureNuit50;
			$regul->heure_nuit_75 = $newdurationHeureNuit75;
			$regul->heure_nuit_100 = $newdurationHeureNuit100;
			$regul->heure_sup00 = $regulHeureSup00;
			$regul->heure_sup25 = $regulHeureSup25;
			$regul->heure_sup50 = $regulHeureSup50;
			$regul->heure_sup50ht = $regulHeureSup50HT;

			$result = $regul->create($user);
		}
	}

	if($object->prime_astreinte != $prime_astreinte || $object->prime_exceptionnelle != $prime_exceptionnelle || $object->prime_objectif != $prime_objectif || $object->prime_amplitude != $prime_amplitude
	|| $object->prime_variable != $prime_variable) {
		$object->oldcopy = dol_clone($object);
		
		if($object->prime_astreinte != $prime_astreinte || $object->prime_exceptionnelle != $prime_exceptionnelle || $object->prime_objectif != $prime_objectif || $object->prime_amplitude != $prime_amplitude
		|| $object->prime_variable != $prime_variable) {
			$object->prime_astreinte = price2num($prime_astreinte, 2);
			$object->prime_exceptionnelle = price2num($prime_exceptionnelle, 2);
			$object->prime_objectif = price2num($prime_objectif, 2);
			$object->prime_amplitude = price2num($prime_amplitude, 2);
			$object->prime_variable = price2num($prime_variable, 2);
		}

		$result = $object->update($user);
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');

		// Si le feuille de temps existe et que des modifications ont été réalisé
		if($object->id > 0 && !empty($modification)){
			$modification = '<ul>'.$modification.'</ul>';

			$object->actiontypecode = 'AC_FDT_VERIF';
			$object->actionmsg2 = "Mise à jour des données de vérification de la feuille de temps $object->ref";
			$object->actionmsg = $modification;
			$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
		}

		// Redirect to avoid submit twice on back
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
	
}

// Dans le cas ou on clique sur "TRANSMETTRE"
if ($action == 'confirm_transmettre' && $confirm == 'yes' && $object->id > 0){
	// Vérifie si l'utilisateur est un RAF
	$user_group = New UserGroup($db);
	$user_group->fetch('', 'Responsable d\'antenne');
	$liste_raf = $user_group->listUsersForGroup();
	$userIsRA = 0;
	foreach($liste_raf as $raf){
		if ($raf->id == $usertoprocess->id){
			$userIsRA = 1;
			break;
		}
	}

	// Gestion des 1er approbateurs de la FDT
	if(!$userIsRA && !$conf->global->FDT_USER_APPROVER){
		$object->deleteAllTaskValidation();

		// 1er Approbateurs
		$list_validation1 = array();
		$list_validation2 = array();
		$listtask = $task->getTask($object->date_debut, $object->date_fin, $usertoprocess->id);
		foreach($listtask as $taskid){
			$task->fetch($taskid);
			
			// 2nd Approbateur
			$projet->fetch($task->fk_project);
			$liste_resp_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER', 1);
			foreach($liste_resp_projet as $userid) {
				if (!in_array($userid, $list_validation2)) {
					$object->createTaskValidation($userid, 1, 2);
					$list_validation2[] = $userid;
				}
			}
			
			$liste_resp_tache = $task->liste_contact(-1, 'internal', 1, 'TASKEXECUTIVE', 1);
			foreach($liste_resp_tache as $userid) {
				if (!in_array($userid, $list_validation1) && !in_array($userid, $list_validation2)) {
					$object->createTaskValidation($userid, 1, 1);
					$list_validation1[] = $userid;
				}
			}
		}
	}

	// Si l'utilisateur est un RAF, la FDT est directement validé
	if (($userIsRA  && !$conf->global->FDT_USER_APPROVER) || ($conf->global->FDT_USER_APPROVER && (empty($usertoprocess->array_options['options_approbateurfdt']) || in_array($user->id, explode(',', $usertoprocess->array_options['options_approbateurfdt']))))){
		$regulHeureSup00 = ($regul->heure_sup00 != 0 ? (double)$regul->heure_sup00 : 0);
		$regulHeureSup25 = ($regul->heure_sup25 != 0 ? (double)$regul->heure_sup25 : 0);
		$regulHeureSup50 = ($regul->heure_sup50 != 0 ? (double)$regul->heure_sup50 : 0);
		$regulHeureSup50HT = ($regul->heure_sup50ht != 0 ? (double)$regul->heure_sup50ht : 0);

		if($userIsRA  && !$conf->global->FDT_USER_APPROVER) {
			$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATION1_RAInDolibarr", $object->ref);
			$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATION1_RAInDolibarr", $object->ref);
		}
		else {
			$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATIONInDolibarr", $object->ref);
			$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATIONInDolibarr", $object->ref);
		}
	

		$result = $object->setVerification($user);
		//$object->update($user, 1);

		$heure_sup = new Projet_task_time_heure_sup($db);
		$projet_task_time_other = New Projet_task_time_other($db);
		$otherTime = $projet_task_time_other->getOtherTimeDay($firstdaytoshow, $lastdaytoshow, $usertoprocess->id);
		for ($idw = 0; $idw < $nb_jour; $idw++) { 
			$silae = new Silae($db);
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw];
			$res = $silae->fetchSilaeWithoutId($dayinloopfromfirstdaytoshow, $usertoprocess->id);

			$silae->date = $dayinloopfromfirstdaytoshow;
			$silae->fk_user = $usertoprocess->id;
			
			if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
				$heure_sup00_before = $silae->heure_sup00;
				$heure_sup25_before = $silae->heure_sup25;
				$heure_sup50_before = $silae->heure_sup50;
				$heure_sup50ht_before = $silae->heure_sup50ht;
				$silae->calculHS($heure_semaine, $heure_semaine_hs, $timeSpentWeek, $timeHoliday, $dayinloopfromfirstdaytoshow);

				// Agenda Heure Sup 0%
				if($heure_sup00_before != $silae->heure_sup00) {
					$new_value = formatValueForAgenda('double', $silae->heure_sup00 / 3600);
					$old_value = formatValueForAgenda('double', $heure_sup00_before / 3600);
	
					$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 0%</strong> ('.dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
				}
	
				// Agenda Heure Sup 25%
				if($heure_sup25_before != $silae->heure_sup25) {
					$new_value = formatValueForAgenda('double', $silae->heure_sup25 / 3600);
					$old_value = formatValueForAgenda('double', $heure_sup25_before / 3600);
	
					$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 25%</strong> ('.dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
				}
	
				// Agenda Heure Sup 50%
				if($heure_sup50_before != $silae->heure_sup50) {
					$new_value = formatValueForAgenda('double', $silae->heure_sup50 / 3600);
					$old_value = formatValueForAgenda('double', $heure_sup50_before / 3600);
	
					$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 50%</strong> ('.dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
				}

				// Agenda Heure Sup 50% HT
				if($heure_sup50ht_before != $silae->heure_sup50ht) {
					$new_value = formatValueForAgenda('double', $silae->heure_sup50ht / 3600);
					$old_value = formatValueForAgenda('double', $heure_sup50ht_before / 3600);
	
					$modification .= ($old_value != $new_value ? '<li><strong>Heure Sup 50% HT</strong> ('.dol_print_date($dayinloopfromfirstdaytoshow, '%d/%m/%Y').") : $old_value ➔ $new_value</li>" : '');
				}

				if($dayinloopfromfirstdaytoshow < $first_day_month) {
					$regulHeureSup00 += ((double)$silae->heure_sup00 - (double)$heure_sup00_before);
					$regulHeureSup25 += ((double)$silae->heure_sup25 - (double)$heure_sup25_before);
					$regulHeureSup50 += ((double)$silae->heure_sup50 - (double)$heure_sup50_before);
					$regulHeureSup50HT += ((double)$silae->heure_sup50ht - (double)$heure_sup50ht_before);
				}
			}

			if(!$conf->global->FDT_DISPLAY_COLUMN) {
				$deplacement = new Deplacement($db);
				$deplacement->fetchDeplacementWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);

				$silae->heure_nuit = $otherTime['heure_nuit'][$dayinloopfromfirstdaytoshow];
				if($object->getHeureDay($dayinloopfromfirstdaytoshow, $object->fk_user) > 0) {
					if($userRepas == 1 && $deplacement->type_deplacement != 7) { 
						$silae->repas = 1;
					}
					elseif($userRepas == 2 && $deplacement->type_deplacement != 7) { 
						$silae->repas = 2;
					}
				}
			}

			if($res > 0) {
				$result = $silae->update($user);
			}
			elseif($res == 0) {
				$result = $silae->create($user);
			}
			else {
				$result = -1;
			}
		}

		if($result) {
			$regul = new Regul($db);
			$resregul = $regul->fetchWithoutId($first_day_month, $usertoprocess->id, 1);

			if($regulHeureSup00 != $regul->heure_sup00 || $regulHeureSup25 != $regul->heure_sup25 || $regulHeureSup50 != $regul->heure_sup50 || $regulHeureSup50ht != $regul->heure_sup50ht) {
				// Agenda Heure Sup 0%
				if($regulHeureSup00 != $regul->heure_sup00) {
					$new_value = formatValueForAgenda('double', $regulHeureSup00 / 3600);
					$old_value = formatValueForAgenda('double', $regul->heure_sup00 / 3600);

					$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 0%</strong> : $old_value ➔ $new_value</li>" : '');
				}

				// Agenda Heure Sup 25%
				if($regulHeureSup25 != $regul->heure_sup25) {
					$new_value = formatValueForAgenda('double', $regulHeureSup25 / 3600);
					$old_value = formatValueForAgenda('double', $regul->heure_sup25 / 3600);

					$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 25%</strong> : $old_value ➔ $new_value</li>" : '');
				}

				// Agenda Heure Sup 50%
				if($regulHeureSup50 != $regul->heure_sup50) {
					$new_value = formatValueForAgenda('double', $regulHeureSup50 / 3600);
					$old_value = formatValueForAgenda('double', $regul->heure_sup50 / 3600);

					$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 50%</strong> : $old_value ➔ $new_value</li>" : '');
				}

				// Agenda Heure Sup 50% HT
				if($regulHeureSup50HT != $regul->heure_sup50ht) {
					$new_value = formatValueForAgenda('double', $regulHeureSup50HT / 3600);
					$old_value = formatValueForAgenda('double', $regul->heure_sup50ht / 3600);

					$modification .= ($old_value != $new_value ? "<li><strong>Regul Heure Sup 50% HT</strong> : $old_value ➔ $new_value</li>" : '');
				}

				if($resregul > 0) {
					$regul->heure_sup00 = $regulHeureSup00;
					$regul->heure_sup25 = $regulHeureSup25;
					$regul->heure_sup50 = $regulHeureSup50;
					$regul->heure_sup50ht = $regulHeureSup50HT;

					$result = $regul->update($user);
				}
				elseif($resregul == 0) {
					$regul->date = $first_day_month;
					$regul->fk_user = $usertoprocess->id;
					$regul->heure_sup00 = $regulHeureSup00;
					$regul->heure_sup25 = $regulHeureSup25;
					$regul->heure_sup50 = $regulHeureSup50;
					$regul->heure_sup50ht = $regulHeureSup50HT;

					$result = $regul->create($user);
				}
			}
		}
	}
	elseif(empty($list_validation1) && !$conf->global->FDT_USER_APPROVER) {
		$result = $object->setApprobation2($user);
	}
	else {	// Sinon, elle doit être approuvée 
		$result = $object->setApprobation1($user);
	}

	if ($result > 0) {
		if(!empty($modification)){
			$modification = '<ul>'.$modification.'</ul>';

			$object->actiontypecode = 'AC_FDT_VERIF';
			$object->actionmsg2 = ($object->status == 4 ? "Mise à jour des données lors de la vérification de la feuille de temps $object->ref" : "Mise à jour des données après la vérification de la feuille de temps $object->ref");
			$object->actionmsg = $modification;
			$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
		}

		setEventMessages($langs->trans("Feuille de temps transmise"), null, 'mesgs');
		header("Location: ".$_SERVER['PHP_SELF'].'?'.$param);
		exit;
	} 
	else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
	}

}

?>
