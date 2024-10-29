<?php
/* Copyright (C) 2017 Lény METZGER  <leny-07@hotmail.fr>
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

/**
 *	\file			htdocs/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userhabilitation.inc.php
 *  \brief			Code for common actions delete / add / update
 */

// if ($cancel) {
// 	/*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
// 	if (!empty($backtopageforcancel)) {
// 		header("Location: ".$backtopageforcancel);
// 		exit;
// 	} elseif (!empty($backtopage)) {
// 		header("Location: ".$backtopage);
// 		exit;
// 	}
// 	$action = '';
// }

require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/visitemedical.class.php';

if($action == 'addline' && $permissiontoaddline) {
	$userHabilitation = new UserHabilitation($db);
	$userFormation = new UserFormation($db);
	$db->begin();

	if(!(GETPOST('fk_habilitation') > 0)){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Habilitation")), null, 'errors');
		$error++;
	}

	if($objectline->getID(GETPOST('fk_habilitation'), $object->id) > 0){
		setEventMessages("Impossible d'ajouter cette habilitation car l'utilisateur est déja affecté à celle-ci", null, 'errors');
		$error++;
	}

	if(!(GETPOST('fk_user') > 0)){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
		$error++;
	}

	if (empty(GETPOST("date_habilitationmonth", 'int')) || empty(GETPOST("date_habilitationday", 'int')) || empty(GETPOST("date_habilitationyear", 'int'))) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateHabilitation")), null, 'errors');
		$error++;
	}
	$date = dol_mktime(-1, -1, -1, GETPOST("date_habilitationmonth", 'int'), GETPOST("date_habilitationday", 'int'), GETPOST("date_habilitationyear", 'int'));

	if(GETPOST('status') == -1 || empty(GETPOST('status'))){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
		$error++;
	}

	if(!$error && empty(GETPOST('forcecreation'))) {
		// Prérequis des formations
		$formations_user = $userFormation->getAllFormationsForUser(GETPOST('fk_user'));

		// Récupérer toutes les conditions de prérequis pour cette habilitation
		$prerequisConditions = $habilitation_static->getPrerequis($habilitation_static->id);

		foreach ($prerequisConditions as $conditionId => $formationIds) {
			$conditionMet = false;

			// Vérifier si l'utilisateur possède au moins une des formations requises dans cette condition (condition OR)
			foreach ($formationIds as $formationid) {
				if (in_array($formationid, $formations_user)) {
					$conditionMet = true; 
					break;
				}
			}

			// Si une condition OR n'est pas remplie, générer un message d'erreur
			if (!$conditionMet) {
				setEventMessages($langs->trans('ErrorPrerequisFormation'), null, 'errors');
				$error++;
				break;
			}
		}
		

		// Prérequis aptitude médicale
		if(!$error) {
			$visiteMedicale = new VisiteMedical($db);
			$extrafields = new Extrafields($db);
			$extrafields->fetch_name_optionals_label('donneesrh_Medecinedutravail');
			$userField = new UserField($db);
			$userField->id = GETPOST('fk_user', 'int');
			$userField->table_element = 'donneesrh_Medecinedutravail';
			$userField->fetch_optionals();
			$naturesVisite = explode(',', $userField->array_options['options_naturevisitemedicale']);
			foreach($naturesVisite as $natureid) {
				if(!$visiteMedicale->userAsAptitudeMedicale(GETPOST('fk_user', 'int'), $natureid)) {
					$nature = $visiteMedicale->getNatureInfo($natureid);
					setEventMessages($langs->trans('ErrorPrerequisAptitude', $nature['label']), null, 'errors');
					$error++;
				}
			}
		}	
	}

	if (!$error) {
		$objectline->ref = $user_static->login."-".$habilitation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
		$objectline->fk_habilitation = GETPOST('fk_habilitation');
		$objectline->date_habilitation = $date;
		$objectline->date_fin_habilitation = dol_time_plus_duree(dol_time_plus_duree($date, $habilitation_static->validite_employeur, 'd'), -1, 'd');
		$objectline->fk_user = GETPOST('fk_user');
		$objectline->status = GETPOST('status');

		$resultcreate = $objectline->create($user);
	}

	if(!$error && $resultcreate){
		$db->commit();
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		// header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
		header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
		exit;
	}
	elseif(!$error && !$resultcreate){
		$db->rollback();
		setEventMessages($langs->trans($objectline->error), null, 'errors');
	}

}

if($action == 'updateline' && !$cancel && $permissiontoaddline){
	if($lineid > 0){
		$objectline->fetch($lineid);

		if (empty(GETPOST("date_habilitationmonth", 'int')) || empty(GETPOST("date_habilitationday", 'int')) || empty(GETPOST("date_habilitationyear", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateHabilitation")), null, 'errors');
			$error++;
		}
		$date = dol_mktime(-1, -1, -1, GETPOST("date_habilitationmonth", 'int'), GETPOST("date_habilitationday", 'int'), GETPOST("date_habilitationyear", 'int'));


		if(GETPOST('status') == -1 || empty(GETPOST('status'))){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$objectline->ref = $user_static->login."-".$habilitation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
			$objectline->date_habilitation = $date;
			$objectline->date_fin_habilitation = dol_time_plus_duree(dol_time_plus_duree($date, $habilitation_static->validite_employeur, 'd'), -1, 'd');
			$objectline->domaineapplication = GETPOST('domaineapplication');
			$objectline->status = GETPOST('status');

			$resultupdate = $objectline->update($user);
		}

		if(!$error && $resultupdate){
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			// header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
			exit;
		}
		elseif(!$error && !$resultupdate){
			setEventMessages($langs->trans($objectline->error), null, 'errors');
		}
		elseif($error) {
			header('Location: '.$_SERVER["PHP_SELF"].'?'.($param ? $param : '').'&action=editline&lineid='.$lineid.'#line_'.GETPOST('lineid', 'int'));
			exit;
		}
	}
	else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}

if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoaddline) {
    $resultdelete = $object->deleteLine($user, $lineid);
    if ($resultdelete > 0) {
        setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
        // header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
		header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
		exit;
    } else {
        $error++;
        setEventMessages($object->error, $object->errors, 'errors');
    }
    $action = '';
}