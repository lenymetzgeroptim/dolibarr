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
	$db->begin();

	$formation = new Formation($db);
	$userFormation = new UserFormation($db);

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

	if(GETPOST('status') == $objectline::STATUS_VALIDE && GETPOST('resultat') == 3) {
		setEventMessages($langs->trans('ErrorFormationUnsatisfate'), null, 'errors');
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

	// Impossible d'ajouter une formation si une ligne avec la même formation existe déja (hors cloturée et expirée)
	if(!$error && (empty(GETPOST('forcecreation')) || !$permissiontoforceline) && GETPOST('status') != $objectline::STATUS_VALIDE && GETPOST('status') != $objectline::STATUS_EXPIREE && GETPOST('status') != $objectline::STATUS_CLOTUREE){ 
		$formationEnCours = $formation->getFormationEnCours(GETPOST('fk_user'), GETPOST('fk_formation'));

		if(sizeof($formationEnCours) >= 1){
			setEventMessages($langs->trans('ErrorFormationAlreadyExist'), null, 'errors');
			$error++;
		}
	}

	// Impossible d'ajouter une formation si l'utilisateur n'a pas les prérequis
	$elementPrerequis = new ElementPrerequis($db);
	if(!$error && (empty(GETPOST('forcecreation')) || !$permissiontoforceline) && $elementPrerequis->gestionPrerequis(GETPOST('fk_user'), $formation_static, 0) < 0) {
		$error++;
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

	if(!$error && $resultcreate > 0){
		$db->commit();
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
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

		// if(GETPOST('status') == -1 || empty(GETPOST('status'))){
		// 	setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
		// 	$error++;
		// }

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
			$objectline->non_renouvelee = GETPOST('non_renouvelee', 'int');
			$objectline->resultat = GETPOST('resultat');
			// $objectline->status = GETPOST('status');

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

if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontodeleteline) { // TODOLény -> Pouvoir supprimer des lignes que dans certains cas
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

			// Création de la nouvelle ligne
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
	$userVolet = new UserVolet($db);
	$db->begin();

	if($lineid > 0){
		if(empty(GETPOST('numero_certificat_valider')) && GETPOST('resultat_valider') != 3){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("NumeroCertificat")), null, 'errors');
			$error++;
		}
		
		if(!(GETPOST('resultat_valider') > 0)){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Resultat")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$objectline->fetch($lineid);
			// $userid = $objectline->fk_user; 
			// $formationid = $objectline->fk_formation;

			// $user_static = new User($db);
			// $user_static->fetch($userid);
			// $formation_static = new Formation($db);
			// $formation_static->fetch($formationid);

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

			$result = $objectline->update($user);

			if($result) {
				if(GETPOST('resultat_valider') == 3) {
					$result = $objectline->to_program($user);
				}
				else {
					$result = $objectline->validate($user);
				}
			}
		}

		if(!$error && $result > 0){
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