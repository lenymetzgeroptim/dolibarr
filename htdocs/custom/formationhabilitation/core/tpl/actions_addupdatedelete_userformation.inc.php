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
 *	\file			htdocs/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userformation.inc.php
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

if((($action == 'confirm_addline' && $confirm == 'yes' && (GETPOST('status') == $objectline::STATUS_VALIDE || GETPOST('status') == $objectline::STATUS_PROGRAMMEE)) || ($action == 'addline' && (GETPOST('status') != $objectline::STATUS_VALIDE && GETPOST('status') != $objectline::STATUS_PROGRAMMEE))) && $permissiontoaddline) {
	$formation = new Formation($db);
	$userFormation = new UserFormation($db);
	$db->begin();

	if(!(GETPOST('fk_formation') > 0)){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Formation")), null, 'errors');
		$error++;
	}

	if(!(GETPOST('fk_user') > 0)){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
		$error++;
	}

	if (empty(GETPOST("date_debut_formationmonth", 'int')) || empty(GETPOST("date_debut_formationday", 'int')) || empty(GETPOST("date_debut_formationyear", 'int'))) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateDebutFormation")), null, 'errors');
		$error++;
	}
	$date_debut = dol_mktime(-1, -1, -1, GETPOST("date_debut_formationmonth", 'int'), GETPOST("date_debut_formationday", 'int'), GETPOST("date_debut_formationyear", 'int'));

	if (empty(GETPOST("date_fin_formationmonth", 'int')) || empty(GETPOST("date_fin_formationday", 'int')) || empty(GETPOST("date_fin_formationyear", 'int'))) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFinFormation")), null, 'errors');
		$error++;
	}
	$date_fin = dol_mktime(-1, -1, -1, GETPOST("date_fin_formationmonth", 'int'), GETPOST("date_fin_formationday", 'int'), GETPOST("date_fin_formationyear", 'int'));

	if (empty(GETPOST("nombre_heurehour", 'int')) && empty(GETPOST("nombre_heuremin", 'int'))) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("NombreHeure")), null, 'errors');
		$error++;
	}
	$nombre_heure = 60 * 60 * GETPOSTINT('nombre_heurehour') + 60 * GETPOSTINT('nombre_heuremin');

	if((GETPOST('fk_societe') == -1 || empty(GETPOST('fk_societe'))) && (GETPOST('formateur') == -1 || empty(GETPOST('formateur')))){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Organisme")), null, 'errors');
		$error++;
	}

	if(GETPOST('status') == -1 || empty(GETPOST('status'))){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
		$error++;
	}

	if(GETPOST('status') == $objectline::STATUS_PROGRAMMEE) {
		if (empty(GETPOST("date_debut_formation_programmerhour", 'int')) || empty(GETPOST("date_debut_formation_programmermin", 'int')) || 
		(GETPOST("date_debut_formation_programmerhour", 'int') == '00' && GETPOST("date_debut_formation_programmermin", 'int') == '00')) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("HeureDebut")), null, 'errors');
			$error++;
		}
		$date_debut_convoc = dol_mktime(GETPOST("date_debut_formation_programmerhour", 'int'), GETPOST("date_debut_formation_programmermin", 'int'), -1, GETPOST("date_debut_formation_programmermonth", 'int'), GETPOST("date_debut_formation_programmerday", 'int'), GETPOST("date_debut_formation_programmeryear", 'int'));

		if (empty(GETPOST("date_fin_formation_programmerhour", 'int')) || empty(GETPOST("date_fin_formation_programmermin", 'int')) || 
		(GETPOST("date_fin_formation_programmerhour", 'int') == '00' && GETPOST("date_fin_formation_programmermin", 'int') == '00')) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("HeureFin")), null, 'errors');
			$error++;
		}
		$date_fin_convoc = dol_mktime(GETPOST("date_fin_formation_programmerhour", 'int'), GETPOST("date_fin_formation_programmermin", 'int'), -1, GETPOST("date_fin_formation_programmermonth", 'int'), GETPOST("date_fin_formation_programmerday", 'int'), GETPOST("date_fin_formation_programmeryear", 'int'));
	}

	if(!$error && empty(GETPOST('forcecreation')) && GETPOST('status') != $objectline::STATUS_VALIDE && GETPOST('status') != $objectline::STATUS_EXPIREE && GETPOST('status') != $objectline::STATUS_CLOTUREE){ // Impossimble d'ajouter une formation si une ligne avec la même formation existe déja (hors cloturée et expirée)
		$formationEnCours = $formation->getFormationEnCours(GETPOST('fk_user'), GETPOST('fk_formation'));

		if(sizeof($formationEnCours) >= 1){
			setEventMessages($langs->trans('ErrorFormationAlreadyExist'), null, 'errors');
			$error++;
		}
	}

	if(!$error && empty(GETPOST('forcecreation'))) { // Gestion des prérequis 
		$formation->fetch(GETPOST('fk_formation'));
		$prerequis = explode(',', $formation->prerequis);
		foreach($prerequis as $formationid) {
			if(!$userFormation->userAsFormation(GETPOST('fk_user'), $formationid)) {
				$formation->fetch($formationid);
				setEventMessages($langs->trans('ErrorPrerequisFormation', $formation->label), null, 'errors');
				$error++;
			}
		}
	}

	if(!$error && GETPOST('status') == $objectline::STATUS_VALIDE) { // Gestion de la cloture des formations de niveau inferieur
		$formationToClose = $formation->getFormationToClose(GETPOST('fk_user'), GETPOST('fk_formation'));
		foreach($formationToClose as $userformation_id => $userformation_ref) {
            $userFormation->fetch($userformation_id);
			$res = $userFormation->cloture($user);

			if(!$res) {
				$error++;
			}
        }
	}
	elseif(!$error && GETPOST('status') == $objectline::STATUS_PROGRAMMEE) { // Gestion de la reprogrammation des formations de niveau inferieur
		$formationToReprogrammer = $formation->getFormationToReprogrammer(GETPOST('fk_user'), GETPOST('fk_formation'));
		foreach($formationToReprogrammer as $userformation_id => $userformation_ref) {
            $userFormation->fetch($userformation_id);
			$userFormation->status = UserFormation::STATUS_REPROGRAMMEE;
			$res == $userFormation->update($user);

			if(!$res) {
				$error++;
			}
        }
	}

	if (!$error) {
		$objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date_fin, "%Y%m%d");
		$objectline->fk_user = GETPOST('fk_user');
		$objectline->fk_formation = GETPOST('fk_formation');
		$objectline->interne_externe = GETPOST('interne_externe');
		$objectline->date_debut_formation = $date_debut;
		$objectline->date_fin_formation = $date_fin;
		$objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree(dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm'), -1, 'd') : '');
		$objectline->nombre_heure = $nombre_heure;
		$objectline->cout_pedagogique = $formation_static->cout;
		$objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
		$objectline->cout_annexe = GETPOST('cout_annexe');
		$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation + $objectline->cout_annexe;
		$objectline->fk_societe = GETPOST('fk_societe');
		$objectline->formateur = GETPOST('formateur');
		$objectline->numero_certificat = GETPOST('numero_certificat');
		$objectline->prevupif = GETPOST('prevupif', 'int');
		$objectline->resultat = GETPOST('resultat');
		$objectline->status = GETPOST('status');

		$resultcreate = $objectline->create($user);
	}

	if($resultcreate > 0 && $objectline->status == $objectline::STATUS_PROGRAMMEE){
		$convocation = new Convocation($db);
		$result = $convocation->generationWithFormation($objectline, $user, $date_debut_convoc, $date_fin_convoc);
	}

	if(!$error && $resultcreate > 0){
		$db->commit();
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		//header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
		header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
		exit;
	}
	elseif(!$error && $resultcreate <= 0){
		$db->rollback();
		setEventMessages($langs->trans($objectline->error), null, 'errors');
	}

}

if($action == 'updateline' && !$cancel && $permissiontoaddline){
	if($lineid > 0){
		$objectline->fetch($lineid);

		if (empty(GETPOST("date_debut_formationmonth", 'int')) || empty(GETPOST("date_debut_formationday", 'int')) || empty(GETPOST("date_debut_formationyear", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateDebutFormation")), null, 'errors');
			$error++;
		}
		$date_debut = dol_mktime(-1, -1, -1, GETPOST("date_debut_formationmonth", 'int'), GETPOST("date_debut_formationday", 'int'), GETPOST("date_debut_formationyear", 'int'));
	
		if (empty(GETPOST("date_fin_formationmonth", 'int')) || empty(GETPOST("date_fin_formationday", 'int')) || empty(GETPOST("date_fin_formationyear", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFinFormation")), null, 'errors');
			$error++;
		}
		$date_fin = dol_mktime(-1, -1, -1, GETPOST("date_fin_formationmonth", 'int'), GETPOST("date_fin_formationday", 'int'), GETPOST("date_fin_formationyear", 'int'));

		if (empty(GETPOST("nombre_heurehour", 'int')) && empty(GETPOST("nombre_heuremin", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("NombreHeure")), null, 'errors');
			$error++;
		}
		$nombre_heure = 60 * 60 * GETPOSTINT('nombre_heurehour') + 60 * GETPOSTINT('nombre_heuremin');
	
		if((GETPOST('fk_societe') == -1 || empty(GETPOST('fk_societe'))) && (GETPOST('formateur') == -1 || empty(GETPOST('formateur')))){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Organisme")), null, 'errors');
			$error++;
		}

		if(GETPOST('status') == -1 || empty(GETPOST('status'))){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$nombre_heure_before = $objectline->nombre_heure;

			$objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date_fin, "%Y%m%d");
			$objectline->interne_externe = GETPOST('interne_externe');
			$objectline->date_debut_formation = $date_debut;
			$objectline->date_fin_formation = $date_fin;
			$objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree(dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm'), -1, 'd') : '');
			$objectline->nombre_heure = $nombre_heure;
			if(empty($objectline->cout_pedagogique)) {
				$objectline->cout_pedagogique = $formation_static->cout;
			}
			if(empty($objectline->cout_mobilisation) || $nombre_heure_before != $nombre_heure) {
				$objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
			}
			$objectline->cout_annexe = GETPOST('cout_annexe');
			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation + $objectline->cout_annexe;
			$objectline->fk_societe = (GETPOST('interne_externe') != 2 ? GETPOST('fk_societe') : '');
			$objectline->formateur = (GETPOST('interne_externe') == 2 ? GETPOST('formateur') : '');
			$objectline->numero_certificat = GETPOST('numero_certificat');
			$objectline->prevupif = GETPOST('prevupif', 'int');
			$objectline->resultat = GETPOST('resultat');
			$objectline->status = GETPOST('status');

			//$date_limite = dol_time_plus_duree($date, $object->periode_recyclage, 'm');
			//$date_limite = dol_print_date($date_limite, '%d/%m/%Y');
			//$now = dol_print_date(dol_now(), '%d/%m/%Y');
			/*if($date_limite > $now && GETPOST('status') == $objectline::STATUS_FINECHEANCE && $objectline->status == $objectline::STATUS_FINECHEANCE){
				$objectline->status = $objectline::STATUS_PLANIFIEE;
			}
			else {*/
				//$objectline->status = GETPOST('status');
			//}

			$resultupdate = $objectline->update($user);
		}

		if(!$error && $resultupdate > 0){
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			// header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
			exit;
		}
		elseif(!$error && $resultupdate <= 0){
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
		header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
        exit;
    } else {
        $error++;
        setEventMessages($object->error, $object->errors, 'errors');
    }
    $action = '';
}

if ($action == 'updatedatefinvalidite' && !$cancel && $permissiontoaddline) {
	$db->begin();

	if($lineid > 0){
		$objectline->fetch($lineid);
		if (empty(GETPOST("date_finvalidite_formationmonth", 'int')) || empty(GETPOST("date_finvalidite_formationday", 'int')) || empty(GETPOST("date_finvalidite_formationyear", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFinValiditeFormation")), null, 'errors');
			$error++;
		}
		$date_finvalidite = dol_mktime(-1, -1, -1, GETPOST("date_finvalidite_formationmonth", 'int'), GETPOST("date_finvalidite_formationday", 'int'), GETPOST("date_finvalidite_formationyear", 'int'));



		if(!$error) {
			$objectline->date_finvalidite_formation = $date_finvalidite; 
			$objectline->update($user);
		}
		
		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
			exit;
		} else {
			$db->rollback();
			setEventMessages($objectline->error, $objectline->errors, 'warnings');
			$action = 'edit_datefinvalidite';
		}
	}
	else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}

if ($action == 'updatecoutpedagogique' && !$cancel && $permissiontoaddline) {
	$db->begin();

	if($lineid > 0){
		$objectline->fetch($lineid);

		if(!$error) {
			$objectline->cout_pedagogique = GETPOST('cout_pedagogique');
			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
			$objectline->update($user);
		}
		
		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
			exit;
		} else {
			$db->rollback();
			setEventMessages($objectline->error, $objectline->errors, 'warnings');
			$action = 'edit_coutpedagogique';
		}
	}
	else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}

if ($action == 'updatecoutmobilisation' && !$cancel && $permissiontoaddline) {
	$db->begin();

	if($lineid > 0){
		$objectline->fetch($lineid);

		if(!$error) {
			$objectline->cout_mobilisation = GETPOST('cout_mobilisation');
			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
			$objectline->update($user);
		}
		
		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
			exit;
		} else {
			$db->rollback();
			setEventMessages($objectline->error, $objectline->errors, 'warnings');
			$action = 'edit_coutmobilisation';
		}
	}
	else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}

if($action == 'confirm_programmer_formation' && $confirm == 'yes' && $permissiontoaddline){
	$db->begin();
	$result = 0;

	if($lineid > 0){
		if (empty(GETPOST("date_debut_formation_programmermonth", 'int')) || empty(GETPOST("date_debut_formation_programmerday", 'int')) || empty(GETPOST("date_debut_formation_programmeryear", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateDebutFormation")), null, 'errors');
			$error++;
		}
		$date_debut = dol_mktime(-1, -1, -1, GETPOST("date_debut_formation_programmermonth", 'int'), GETPOST("date_debut_formation_programmerday", 'int'), GETPOST("date_debut_formation_programmeryear", 'int'));

		if (empty(GETPOST("date_fin_formation_programmermonth", 'int')) || empty(GETPOST("date_fin_formation_programmerday", 'int')) || empty(GETPOST("date_fin_formation_programmeryear", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFinFormation")), null, 'errors');
			$error++;
		}
		$date_fin = dol_mktime(-1, -1, -1, GETPOST("date_fin_formation_programmermonth", 'int'), GETPOST("date_fin_formation_programmerday", 'int'), GETPOST("date_fin_formation_programmeryear", 'int'));

		if (empty(GETPOST("date_debut_formation_programmerhour", 'int')) || empty(GETPOST("date_debut_formation_programmermin", 'int')) || 
		(GETPOST("date_debut_formation_programmerhour", 'int') == '00' && GETPOST("date_debut_formation_programmermin", 'int') == '00')) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("HeureDebut")), null, 'errors');
			$error++;
		}
		$date_debut_convoc = dol_mktime(GETPOST("date_debut_formation_programmerhour", 'int'), GETPOST("date_debut_formation_programmermin", 'int'), -1, GETPOST("date_debut_formation_programmermonth", 'int'), GETPOST("date_debut_formation_programmerday", 'int'), GETPOST("date_debut_formation_programmeryear", 'int'));

		if (empty(GETPOST("date_fin_formation_programmerhour", 'int')) || empty(GETPOST("date_fin_formation_programmermin", 'int')) || 
		(GETPOST("date_fin_formation_programmerhour", 'int') == '00' && GETPOST("date_fin_formation_programmermin", 'int') == '00')) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("HeureFin")), null, 'errors');
			$error++;
		}
		$date_fin_convoc = dol_mktime(GETPOST("date_fin_formation_programmerhour", 'int'), GETPOST("date_fin_formation_programmermin", 'int'), -1, GETPOST("date_fin_formation_programmermonth", 'int'), GETPOST("date_fin_formation_programmerday", 'int'), GETPOST("date_fin_formation_programmeryear", 'int'));


		if (!$error) {
			// Changement status de l'ancienne ligne
			$objectline->fetch($lineid);
			$objectline->status = UserFormation::STATUS_REPROGRAMMEE;
			$result == $objectline->update($user);
			$userid = $objectline->fk_user; 
			$formationid = $objectline->fk_formation;

			$user_static = new User($db);
			$user_static->fetch($userid);
			$formation_static = new Formation($db);
			$formation_static->fetch($formationid);

			$objectline = New UserFormation($db);
			$objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date_fin, "%Y%m%d");
			$objectline->fk_formation = $formationid;
			$objectline->fk_user = $userid;
			$objectline->interne_externe = GETPOST('interne_externe_programmer');
			$objectline->date_debut_formation = $date_debut;
			$objectline->date_fin_formation = $date_fin;
			$objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree(dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm'), -1, 'd') : '');
			$objectline->nombre_heure = $formation_static->nombre_heure;
			$objectline->cout_pedagogique = $formation_static->cout;
			$objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
			// $objectline->cout_annexe = GETPOST('cout_annexe');
			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation + $objectline->cout_annexe;
			$objectline->fk_societe = GETPOST('fk_societe_programmer');
			$objectline->formateur = GETPOST('formateur_programmer');
			$objectline->status = UserFormation::STATUS_PROGRAMMEE;
			$result = $objectline->create($user);
		}

		if($result > 0){
			$convocation = new Convocation($db);
			$result = $convocation->generationWithFormation($objectline, $user, $date_debut_convoc, $date_fin_convoc);
		}

		if(!$error && $result > 0){
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
			exit;
		}
		elseif($result < 0){
			$db->rollback();
			setEventMessages($langs->trans($object->error), null, 'errors');
		}
	}
	else {
		$db->rollback();
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}

if($action == 'confirm_valider_formation' && $confirm == 'yes' && $permissiontoaddline){
	$formation = new Formation($db);
	$userFormation = new UserFormation($db);
	$db->begin();

	if($lineid > 0){
		if(empty(GETPOST('numero_certificat_valider'))){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("NumeroCertificat")), null, 'errors');
			$error++;
		}
		
		if(!(GETPOST('resultat_valider') > 0)){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Resultat")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$objectline->fetch($lineid);
			$userid = $objectline->fk_user; 
			$formationid = $objectline->fk_formation;

			$user_static = new User($db);
			$user_static->fetch($userid);
			$formation_static = new Formation($db);
			$formation_static->fetch($formationid);

			// Changement status des anciennes lignes
			$formationToClose = $formation->getFormationToClose($userid, $formationid, $lineid);
			foreach($formationToClose as $userformation_id => $userformation_ref) {
				$userFormation->fetch($userformation_id);
				$res = $userFormation->cloture($user);
	
				if(!$res) {
					$error++;
				}
			}

			// $objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
			// $objectline->fk_formation = $formationid;
			// $objectline->fk_user = $userid;
			// $objectline->interne_externe = GETPOST('interne_externe');
			// $objectline->date_debut_formation = $date_debut;
			// $objectline->date_fin_formation = $date_fin;
			// $objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree(dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm'), -1, 'd') : '');
			// $objectline->nombre_heure = $formation_static->nombre_heure;
			// $objectline->cout_pedagogique = $formation_static->cout;
			// $objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
			// $objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
			// $objectline->fk_societe = GETPOST('fk_societe');
			// $objectline->formateur = GETPOST('formateur');
			$objectline->numero_certificat = GETPOST('numero_certificat_valider');
			$objectline->resultat = GETPOST('resultat_valider');
			$objectline->status = UserFormation::STATUS_VALIDE;

			$result = $objectline->update($user);
		}

		// Création des habilitations 
		$resultcreateline = 1;
		$txtListHabilitation = '';
		if($result && !$error) {
			$habilitationStatic = new Habilitation($db);
			$userHabilitation = new UserHabilitation($db);
			
			$listHabilitation = $habilitationStatic->getHabilitationsByFormation($formationid); 
			$userHabilitation->fk_user = $userid;
			$userHabilitation->status = UserHabilitation::STATUS_HABILITABLE;
			$userHabilitation->date_habilitation = $objectline->date_fin_formation;

			foreach($listHabilitation as $habilitation) {
                $userHabilitation->ref = $user_static->login."-".$habilitation->ref.'-'.dol_print_date($objectline->date_fin_formation, "%Y%m%d");
                $userHabilitation->fk_habilitation = $habilitation->id;
                //$userHabilitation->date_fin_habilitation = dol_time_plus_duree($objectline->date_fin_formation, $habilitationStatic->validite_employeur, 'd');
				$userHabilitation->date_fin_habilitation = $objectline->date_finvalidite_formation;
				$txtListHabilitation .= $habilitation->label.', ';

				$resultcreateline = $userHabilitation->create($user);
				if($resultcreateline <= 0) {
					break;
				}
			}

			// Envoi du mail
			if($resultcreateline > 0 && !empty($txtListHabilitation)) { 
				$user_static = new User($db);
				rtrim($txtListHabilitation, ', ');

				global $dolibarr_main_url_root;

				$subject = "[OPTIM Industries] Notification automatique ".$langs->transnoentitiesnoconv($object->module);
				$from = $conf->global->MAIN_MAIL_EMAIL_FROM;

				$to = '';
				if(sizeof($arrayRespAntenneForMail) > 0) {
					foreach($arrayRespAntenneForMail as $userid) {
						$user_static->fetch($user_id);

						if(!empty($user_static->email)) {
							$to .= $user_static->email.', ';
						}
					}
				}
				else {
					$to = 'administratif@optim-industries.fr';
				}
				rtrim($to, ', ');

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file

				$link = '<a href="'.$urlwithroot.'/custom/formationhabilitation/userformation.php?id='.$object->id.'$onglet=habilitation">'.$object->login.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextHabilitationCreation", $formation_static->label, $link, $txtListHabilitation);

				$trackid = 'formationhabilitation'.$formation_static->id;

				$mailfile = new CMailFile(
					$subject,
					$to,
					$from,
					$message,
					array(),
					array(),
					array(),
					'',
					'',
					0,
					1,
					'',
					'',
					$trackid,
					'',
					$sendcontext
				);

				if(!empty($to)) {
					$result = $mail->sendfile();

					if (!$result) {
						setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
					}
				}
			}
		}

		if(!$error && $result > 0 && $resultcreateline > 0){
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
			exit;
		}
		elseif($result <= 0){
			$db->rollback();
			setEventMessages('Erreur lors de la validation de la formation', null, 'errors');
		}
		elseif($resultcreateline <= 0){
			$db->rollback();
			setEventMessages('Erreur lors de la création des habilitations', null, 'errors');
		}
	}
	else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}