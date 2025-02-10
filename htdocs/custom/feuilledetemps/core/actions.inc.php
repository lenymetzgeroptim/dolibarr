<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 * or see https://www.gnu.org/
 */

// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined
// $triggermodname may be defined

require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';


if ($cancel) {
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = '';
	$massaction = '';
}

if ($action == 'confirm_validate1' && $confirm == 'yes' && $conf->global->FDT_USER_APPROVER && $userIsResp) {
	$modification = '<ul>';
	$regulHeureSup00 = ($regul->heure_sup00 != 0 ? (double)$regul->heure_sup00 : 0);
	$regulHeureSup25 = ($regul->heure_sup25 != 0 ? (double)$regul->heure_sup25 : 0);
	$regulHeureSup50 = ($regul->heure_sup50 != 0 ? (double)$regul->heure_sup50 : 0);
	$regulHeureSup50HT = ($regul->heure_sup50ht != 0 ? (double)$regul->heure_sup50ht : 0);

	if(!$conf->global->FDT_MANAGE_EMPLOYER || ($conf->global->FDT_MANAGE_EMPLOYER && $usertoprocess->array_options['options_fk_employeur'] == 157)){
		$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATIONInDolibarr", $object->ref);
		$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATIONInDolibarr", $object->ref);

		$result = $object->setVerification($user);
		
		$object->update($user, 1);

		$heure_sup = new Projet_task_time_heure_sup($db);
		$projet_task_time_other = New Projet_task_time_other($db);
		$otherTime = $projet_task_time_other->getOtherTimeDay($firstdaytoshow, $lastdaytoshow, $object->fk_user);
		for ($idw = 0; $idw < $nb_jour; $idw++) { 
			$silae = new Silae($db);
			$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw];
			$res = $silae->fetchSilaeWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);

			$silae->date = $dayinloopfromfirstdaytoshow;
			$silae->fk_user = $object->fk_user;
			
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
	//}
	// else {
	// 	$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATE12_NONOPTIMInDolibarr", $object->ref);
	// 	$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATE12_NONOPTIMInDolibarr", $object->ref);

	// 	$result = $object->validate($user);
	}
	

	if ($result < 0) {
		setEventMessages('Impossible de valider', null, 'errors');
	}
	else {
		if($modification != '<ul>'){
			$modification .= '</ul>';

			if($modification != '<ul></ul>') {
				$object->actiontypecode = 'AC_FDT_VERIF';
				$object->actionmsg2 = "Mise à jour des données de vérification de la feuille de temps $object->ref";
				$object->actionmsg = $modification;
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}
elseif ($action == 'confirm_validate1' && $confirm == 'yes' && !$conf->global->FDT_USER_APPROVER && (($userIsResp && $resp_pas_valide) || $userIsRA)) {
	$modification = '<ul>';
	$regulHeureSup00 = ($regul->heure_sup00 != 0 ? (double)$regul->heure_sup00 : 0);
	$regulHeureSup25 = ($regul->heure_sup25 != 0 ? (double)$regul->heure_sup25 : 0);
	$regulHeureSup50 = ($regul->heure_sup50 != 0 ? (double)$regul->heure_sup50 : 0);
	$regulHeureSup50HT = ($regul->heure_sup50ht != 0 ? (double)$regul->heure_sup50ht : 0);

	$result = $object->updateTaskValidation($user->id, 0, 1, 1);

	$list_valideur = $object->listApprover1;
	$list_valideur2 = $object->listApprover2;
	if(!in_array(0, $list_valideur[1]) && (sizeof($list_valideur2[0]) > 1 || (!empty($list_valideur2[0]) && !in_array($user->id, $list_valideur2[0])))){
		$result = $object->setApprobation2($user);
	}
	elseif(!in_array(0, $list_valideur[1]) && (empty($list_valideur2[0]) || (sizeof($list_valideur2[0]) == 1 && in_array($user->id, $list_valideur2[0])))){
		if(sizeof($list_valideur2[0]) == 1 && in_array($user->id, $list_valideur2[0])) {
			$object->updateTaskValidation($user->id, 1, 1, 2);
		}
		
		if(!$conf->global->FDT_MANAGE_EMPLOYER || ($conf->global->FDT_MANAGE_EMPLOYER && $usertoprocess->array_options['options_fk_employeur'] == 157)){
			$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATION1ET2InDolibarr", $object->ref);
			$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATION1ET2InDolibarr", $object->ref);

			$result = $object->setVerification($user);
			
			$object->update($user, 1);

			$heure_sup = new Projet_task_time_heure_sup($db);
			$projet_task_time_other = New Projet_task_time_other($db);
			$otherTime = $projet_task_time_other->getOtherTimeDay($firstdaytoshow, $lastdaytoshow, $object->fk_user);
			for ($idw = 0; $idw < $nb_jour; $idw++) { 
				$silae = new Silae($db);
				$deplacement = new Deplacement($db);
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw];
				$res = $silae->fetchSilaeWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);
				$deplacement->fetchDeplacementWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);

				$silae->date = $dayinloopfromfirstdaytoshow;
				$silae->fk_user = $object->fk_user;
				
				if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') {
					$heure_sup00_before = $silae->heure_sup00;
					$heure_sup25_before = $silae->heure_sup25;
					$heure_sup50_before = $silae->heure_sup50;
					$heure_sup50ht_before = $silae->heure_sup50ht;
					$silae->calculHS($heure_semaine, $heure_semaine_hs, $timeSpentWeek, $timeHoliday, $dayinloopfromfirstdaytoshow);
					
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

				$silae->heure_nuit = $otherTime['heure_nuit'][$dayinloopfromfirstdaytoshow];
				if($object->getHeureDay($dayinloopfromfirstdaytoshow, $object->fk_user) > 0) {
					if($userRepas == 1 && $deplacement->type_deplacement != 7) { 
						$silae->repas = 1;
					}
					elseif($userRepas == 2 && $deplacement->type_deplacement != 7) { 
						$silae->repas = 2;
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

				if($regulHeureSup00 != $regul->heure_sup00 || $regulHeureSup25 != $regul->heure_sup25 || $regulHeureSup50 != $regul->heure_sup50) {
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
		else {
			$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATE12_NONOPTIMInDolibarr", $object->ref);
			$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATE12_NONOPTIMInDolibarr", $object->ref);

			$result = $object->validate($user);
		}
	}

	if ($result < 0) {
		setEventMessages('Impossible de valider', null, 'errors');
	}
	else {
		if($modification != '<ul>'){
			$modification .= '</ul>';

			if($modification != '<ul></ul>') {
				$object->actiontypecode = 'AC_FDT_VERIF';
				$object->actionmsg2 = "Mise à jour des données de vérification de la feuille de temps $object->ref";
				$object->actionmsg = $modification;
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}

if ($action == 'confirm_validate2' && $confirm == 'yes' && $userIsRespProjet && !$conf->global->FDT_USER_APPROVER) {
	$modification = '<ul>';
	$regulHeureSup00 = ($regul->heure_sup00 != 0 ? (double)$regul->heure_sup00 : 0);
	$regulHeureSup25 = ($regul->heure_sup25 != 0 ? (double)$regul->heure_sup25 : 0);
	$regulHeureSup50 = ($regul->heure_sup50 != 0 ? (double)$regul->heure_sup50 : 0);
	$regulHeureSup50HT = ($regul->heure_sup50ht != 0 ? (double)$regul->heure_sup50ht : 0);

	$result = $object->updateTaskValidation($user->id, 0, 1, 2);

	$list_valideur = $object->listApprover2;
	if(!in_array(0, $list_valideur[1])) {
		if(!$conf->global->FDT_MANAGE_EMPLOYER || ($conf->global->FDT_MANAGE_EMPLOYER && $usertoprocess->array_options['options_fk_employeur'] == 157)){
			$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VERIFICATIONInDolibarr", $object->ref);
			$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VERIFICATIONInDolibarr", $object->ref);

			$result = $object->setVerification($user);
			
			$object->update($user, 1);

			$heure_sup = new Projet_task_time_heure_sup($db);
			$projet_task_time_other = New Projet_task_time_other($db);
			$otherTime = $projet_task_time_other->getOtherTimeDay($firstdaytoshow, $lastdaytoshow, $object->fk_user);
			for ($idw = 0; $idw < $nb_jour; $idw++) { 
				$silae = new Silae($db);
				$deplacement = new Deplacement($db);
				$dayinloopfromfirstdaytoshow = $dayinloopfromfirstdaytoshow_array[$idw];
				$res = $silae->fetchSilaeWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);
				$deplacement->fetchDeplacementWithoutId($dayinloopfromfirstdaytoshow, $object->fk_user);

				$silae->date = $dayinloopfromfirstdaytoshow;
				$silae->fk_user = $object->fk_user;

				if(dol_print_date($dayinloopfromfirstdaytoshow, '%a') == 'Dim') { 
					$heure_sup00_before = $silae->heure_sup00;
					$heure_sup25_before = $silae->heure_sup25;
					$heure_sup50_before = $silae->heure_sup50;
					$heure_sup50ht_before = $silae->heure_sup50ht;
					$silae->calculHS($heure_semaine, $heure_semaine_hs, $timeSpentWeek, $timeHoliday, $dayinloopfromfirstdaytoshow);

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

				$silae->heure_nuit = $otherTime['heure_nuit'][$dayinloopfromfirstdaytoshow];
				if($object->getHeureDay($dayinloopfromfirstdaytoshow, $object->fk_user) > 0) {
					if($userRepas == 1 && $deplacement->type_deplacement != 7) { 
						$silae->repas = 1;
					}
					elseif($userRepas == 2 && $deplacement->type_deplacement != 7) { 
						$silae->repas = 2;
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

				if($regulHeureSup00 != $regul->heure_sup00 || $regulHeureSup25 != $regul->heure_sup25 || $regulHeureSup50 != $regul->heure_sup50 || $regulHeureSup50HT != $regul->heure_sup50ht) {
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
		else {
			$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATE_NONOPTIMInDolibarr", $object->ref);
			$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATE_NONOPTIMInDolibarr", $object->ref);

			$result = $object->validate($user);
		}
	}

	if ($result < 0) {
		setEventMessages('Impossible de valider', null, 'errors');
	}
	else {
		if($modification != '<ul>'){
			$modification .= '</ul>';

			if($modification != '<ul></ul>') {
				$object->actiontypecode = 'AC_FDT_VERIF';
				$object->actionmsg2 = "Mise à jour des données de vérification de la feuille de temps $object->ref";
				$object->actionmsg = $modification;
				$object->call_trigger(strtoupper(get_class($object)).'_MODIFY', $user);
			}
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}

if ($action == 'confirm_verification' && $confirm == 'yes' && $permissionToVerification) {
	$object->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATEInDolibarr", $object->ref);
	$object->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_VALIDATEInDolibarr", $object->ref);

	$result = $object->validate($user);
    
	if ($result < 0) {
		setEventMessages('Impossible de valider la vérification', null, 'errors');
	}
	else {
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}

// Action to update record
if ($action == 'update' && !empty($permissiontoadd)) {
	$email = 0;
	$utilisateur_email = array();

    $object->oldcopy = dol_clone($object);
  
	foreach ($object->fields as $key => $val) {
		// Check if field was submited to be edited
		if ($object->fields[$key]['type'] == 'duration') {
			if (!GETPOSTISSET($key.'hour') || !GETPOSTISSET($key.'min')) {
				continue; // The field was not submited to be edited
			}
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			if (!GETPOSTISSET($key)) {
				$object->$key = 0; // use 0 instead null if the field is defined as not null
				continue;
			}
		} else {
			if (!GETPOSTISSET($key)) {
				continue; // The field was not submited to be edited
			}
		}
		// Ignore special fields
		if (in_array($key, array('rowid', 'entity', 'import_key'))) {
			continue;
		}
		if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
			if (!in_array(abs($val['visible']), array(1, 3, 4))) {
				continue; // Only 1 and 3 and 4 that are case to update
			}
		}

		// Set value to update
		if (preg_match('/^(text|html)/', $object->fields[$key]['type'])) {
			$tmparray = explode(':', $object->fields[$key]['type']);
			if (!empty($tmparray[1])) {
				$value = GETPOST($key, $tmparray[1]);
			} else {
				$value = GETPOST($key, 'restricthtml');
			}
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));	// for date without hour, we use gmt
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
		} elseif ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour', 'int') != '' || GETPOST($key.'min', 'int') != '') {
				$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
			} else {
				$value = '';
			}
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			$value = ((GETPOST($key, 'aZ09') == 'on' || GETPOST($key, 'aZ09') == '1') ? 1 : 0);
		} elseif ($object->fields[$key]['type'] == 'reference') {
			$value = array_keys($object->param_list)[GETPOST($key)].','.GETPOST($key.'2');
		} else {
			$value = GETPOST($key, 'alpha');
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
			$value = ''; // This is an implicit foreign key field
		}
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
			$value = ''; // This is an explicit foreign key field
		}

		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}
	}

	if (!$error) {
		$fk_user_validation1 = GETPOST('fk_user_validation1');
		$fk_user_validation2 = GETPOST('fk_user_validation2');

		// 1ere étape : Supprimer les 1er et 2nd validateur nécéssaire
		$list_validation1 = $object->listApprover1;
		foreach($list_validation1[2] as $id => $user_static){
			if(!in_array($id, $fk_user_validation1)){
				$object->deleteTaskValidation($id, 1);
			}
		}

		$list_validation2 = $object->listApprover2;
		foreach($list_validation2[2] as $id => $user_static){
			if(!in_array($id, $fk_user_validation2)){
				$object->deleteTaskValidation($id, 2);
			}
		}

		// 2e étape : On ajoute les 1er et 2nd validateur nécéssaire
		foreach($fk_user_validation1 as $id_user){
			if($id_user > 0 && !array_key_exists($id_user, $list_validation1[0])){
				$object->createTaskValidation($id_user, 1, 1); 

				$email = 1;
				$utilisateur_email[] = $id_user;
			}
		}

		foreach($fk_user_validation2 as $id_user){
			if($id_user > 0 && !array_key_exists($id_user, $list_validation2[0])){
				$object->createTaskValidation($id_user, 1, 2); 

				$email = 1;
				$utilisateur_email[] = $id_user;
			}
		}
	}

	if (!$error) {
		$result = $object->update($user);
		if ($result > 0) {
			if($email){
				global $dolibarr_main_url_root;
				$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
				$from = 'erp@optim-industries.fr';

				$to = '';
				$user_static = new User($db);
				foreach($utilisateur_email as $user_id){
					$user_static->fetch($user_id);
					if(!empty($user_static->email)){
						$to .= $user_static->email.', ';
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$heure_manquante = '';
				// if($object->semaineHeuresManquantes()){
				// 	$heure_manquante = '<p style="color: red">Celle-ci contient une ou plusieurs semaines à moins de 35h</p>';
				// }
				$msg = $langs->transnoentitiesnoconv("EMailTextFDTApprobation", $link, $heure_manquante);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if (!empty($to)){
					$res = $mail->sendfile();
				}
			}
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			// Creation KO
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'edit';
		}
	} else {
		$action = 'edit';
	}
}

if ($action == 'confirm_sendMail' && $confirm == 'yes' && $permissionToVerification) {
	$result = 0;
	$user_static = new User($db);

	$message = $_POST["sendMailContent"];
	$sendTo = GETPOST('sendMailTo');

	$to = '';
	foreach($sendTo as $key => $id) {
		$user_static->fetch($id);
		if(!empty($user_static->email)) {
			$to .= $user_static->email;
			$to .= ', ';
		}
	}
	$to = rtrim($to, ", ");

	global $dolibarr_main_url_root;
	$subject = 'Remarque sur pointage';
	$from = 'pointage@optim-industries.fr';
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">'.$object->ref.'</a>';
	$msg = $langs->transnoentitiesnoconv("EMailTextSendMail", $link, $message);
	
	$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
	if (!empty($to)){
		$result = $mail->sendfile();
	}
    
	if ($result > 0) {
		$object->actiontypecode = 'AC_EMAIL';
		$object->actionmsg2 = "Mail Feuille de temps envoyé à $user_static->lastname $user_static->firstname";
		$object->actionmsg = $langs->transnoentities("SENDMAILInDolibarr", $message);
		$object->call_trigger(strtoupper(get_class($object)).'_SENDMAIL', $user);
		setEventMessages('Mail envoyé', null, 'mesgs');
	}
	else {
		setEventMessages('Impossible d\'envoyer un mail', null, 'errors');
	}
}

// Action to delete Favoris
if ($action == 'removeFavoris' && $user->id == $usertoprocess->id) {
	$result = $object->deleteFavoris($usertoprocess->id, GETPOST('taskid', 'int'));

	if ($result > 0) {
		// Delete OK
		setEventMessages("Favoris supprimé", null, 'mesgs');

		header('Location: '.$_SERVER["PHP_SELF"].'?'.$param);
		exit;
	} else {
		$error++;
		if (!empty($object->errors)) {
			setEventMessages(null, $object->errors, 'errors');
		} else {
			setEventMessages($object->error, null, 'errors');
		}
	}

	$action = '';
}

// Action to add Favoris
if ($action == 'addFavoris' && $user->id == $usertoprocess->id) {
	$result = $object->createFavoris($usertoprocess->id, GETPOST('taskid', 'int'));

	if ($result > 0) {
		// Delete OK
		setEventMessages("Favoris ajouté", null, 'mesgs');

		header('Location: '.$_SERVER["PHP_SELF"].'?'.$param);
		exit;
	} else {
		$error++;
		if (!empty($object->errors)) {
			setEventMessages(null, $object->errors, 'errors');
		} else {
			setEventMessages($object->error, null, 'errors');
		}
	}

	$action = '';
}