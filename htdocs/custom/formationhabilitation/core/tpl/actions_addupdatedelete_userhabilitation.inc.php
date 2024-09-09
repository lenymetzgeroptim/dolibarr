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

	if(!$error) { // Gestion des prérequis : TODOL -> aptitude medicale
		$prerequis = explode(',', $habilitation_static->formation);
		foreach($prerequis as $formationid) {
			if(!$userFormation->userAsFormation(GETPOST('fk_user'), $formationid)) {
				$formation->fetch($formationid);
				setEventMessages($langs->trans('ErrorPrerequis', $formation->label), null, 'errors');
				$error++;
			}
		}
	}

	if (!$error) {
		$objectline->ref = $user_static->login."-".$habilitation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
		$objectline->fk_habilitation = GETPOST('fk_habilitation');
		$objectline->date_habilitation = $date;
		$objectline->date_fin_habilitation = dol_time_plus_duree($date, $habilitation_static->validite_employeur, 'd');
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
			$objectline->date_fin_habilitation = dol_time_plus_duree($date, $habilitation_static->validite_employeur, 'd');
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

// if ($action == 'updatedatefinvalidite' && !$cancel && $permissiontoaddline) {
// 	$db->begin();

// 	if($lineid > 0){
// 		$objectline->fetch($lineid);
// 		if (empty(GETPOST("date_finvalidite_formationmonth", 'int')) || empty(GETPOST("date_finvalidite_formationday", 'int')) || empty(GETPOST("date_finvalidite_formationyear", 'int'))) {
// 			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFinValiditeFormation")), null, 'errors');
// 			$error++;
// 		}
// 		$date_finvalidite = dol_mktime(-1, -1, -1, GETPOST("date_finvalidite_formationmonth", 'int'), GETPOST("date_finvalidite_formationday", 'int'), GETPOST("date_finvalidite_formationyear", 'int'));



// 		if(!$error) {
// 			$objectline->date_finvalidite_formation = $date_finvalidite; 
// 			$objectline->update($user);
// 		}
		
// 		if (!$error) {
// 			$db->commit();
// 			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
// 			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
// 			exit;
// 		} else {
// 			$db->rollback();
// 			setEventMessages($objectline->error, $objectline->errors, 'warnings');
// 			$action = 'edit_datefinvalidite';
// 		}
// 	}
// 	else {
// 		$langs->load("errors");
// 		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
// 	}
// }

// if ($action == 'updatecoutpedagogique' && !$cancel && $permissiontoaddline) {
// 	$db->begin();

// 	if($lineid > 0){
// 		$objectline->fetch($lineid);

// 		if(!$error) {
// 			$objectline->cout_pedagogique = GETPOST('cout_pedagogique');
// 			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
// 			$objectline->update($user);
// 		}
		
// 		if (!$error) {
// 			$db->commit();
// 			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
// 			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
// 			exit;
// 		} else {
// 			$db->rollback();
// 			setEventMessages($objectline->error, $objectline->errors, 'warnings');
// 			$action = 'edit_coutpedagogique';
// 		}
// 	}
// 	else {
// 		$langs->load("errors");
// 		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
// 	}
// }

// if ($action == 'updatecoutmobilisation' && !$cancel && $permissiontoaddline) {
// 	$db->begin();

// 	if($lineid > 0){
// 		$objectline->fetch($lineid);

// 		if(!$error) {
// 			$objectline->cout_mobilisation = GETPOST('cout_mobilisation');
// 			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
// 			$objectline->update($user);
// 		}
		
// 		if (!$error) {
// 			$db->commit();
// 			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
// 			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
// 			exit;
// 		} else {
// 			$db->rollback();
// 			setEventMessages($objectline->error, $objectline->errors, 'warnings');
// 			$action = 'edit_coutmobilisation';
// 		}
// 	}
// 	else {
// 		$langs->load("errors");
// 		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
// 	}
// }

// if($action == 'confirm_programmer_formation' && $confirm == 'yes' && $permissiontoaddline){
// 	if($lineid > 0){
// 		if (empty(GETPOST("date_debut_formation_programmermonth", 'int')) || empty(GETPOST("date_debut_formation_programmerday", 'int')) || empty(GETPOST("date_debut_formation_programmeryear", 'int'))) {
// 			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateDebutFormation")), null, 'errors');
// 			$error++;
// 		}
// 		$date_debut = dol_mktime(-1, -1, -1, GETPOST("date_debut_formation_programmermonth", 'int'), GETPOST("date_debut_formation_programmerday", 'int'), GETPOST("date_debut_formation_programmeryear", 'int'));

// 		if (empty(GETPOST("date_fin_formation_programmermonth", 'int')) || empty(GETPOST("date_fin_formation_programmerday", 'int')) || empty(GETPOST("date_fin_formation_programmeryear", 'int'))) {
// 			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFinFormation")), null, 'errors');
// 			$error++;
// 		}
// 		$date_fin = dol_mktime(-1, -1, -1, GETPOST("date_fin_formation_programmermonth", 'int'), GETPOST("date_fin_formation_programmerday", 'int'), GETPOST("date_fin_formation_programmeryear", 'int'));

// 		if (!$error) {
// 			// Changement status de l'ancienne ligne
// 			$objectline->fetch($lineid);
// 			$objectline->status = UserFormation::STATUS_REPROGRAMMEE;
// 			$result == $objectline->update($user);
// 			$userid = $objectline->fk_user; 
// 			$formationid = $objectline->fk_formation;

// 			$user_static = new User($db);
// 			$user_static->fetch($userid);
// 			$formation_static = new Formation($db);
// 			$formation_static->fetch($formationid);

// 			$objectline = New UserFormation($db);
// 			$objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date_fin, "%Y%m%d");
// 			$objectline->fk_formation = $formationid;
// 			$objectline->fk_user = $userid;
// 			// $objectline->interne_externe = GETPOST('interne_externe');
// 			$objectline->date_debut_formation = $date_debut;
// 			$objectline->date_fin_formation = $date_fin;
// 			$objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm') : '');
// 			$objectline->nombre_heure = $formation_static->nombre_heure;
// 			$objectline->cout_pedagogique = $formation_static->cout;
// 			$objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
// 			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
// 			// $objectline->fk_societe = GETPOST('fk_societe');
// 			// $objectline->formateur = GETPOST('formateur');
// 			$objectline->status = UserFormation::STATUS_PROGRAMMEE;

// 			$result = $objectline->create($user);
// 		}

// 		if(!$error && $result){
// 			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
// 			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
// 			exit;
// 		}
// 		elseif(!$result){
// 			setEventMessages($langs->trans($object->error), null, 'errors');
// 		}
// 	}
// 	else {
// 		$langs->load("errors");
// 		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
// 	}
// }

// if($action == 'confirm_valider_formation' && $confirm == 'yes' && $permissiontoaddline){
// 	$formation = new Formation($db);
// 	$userFormation = new UserFormation($db);
// 	$db->begin();

// 	if($lineid > 0){
// 		if(empty(GETPOST('numero_certificat_valider'))){
// 			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("NumeroCertificat")), null, 'errors');
// 			$error++;
// 		}
		
// 		if(!(GETPOST('resultat_valider') > 0)){
// 			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Resultat")), null, 'errors');
// 			$error++;
// 		}

// 		if (!$error) {
// 			$objectline->fetch($lineid);
// 			$userid = $objectline->fk_user; 
// 			$formationid = $objectline->fk_formation;

// 			$user_static = new User($db);
// 			$user_static->fetch($userid);
// 			$formation_static = new Formation($db);
// 			$formation_static->fetch($formationid);

// 			// Changement status des anciennes lignes
// 			$formationToClose = $formation->getFormationToClose($userid, $formationid, $lineid);
// 			foreach($formationToClose as $userformation_id => $userformation_ref) {
// 				$userFormation->fetch($userformation_id);
// 				$res = $userFormation->cloture($user);
	
// 				if(!$res) {
// 					$error++;
// 				}
// 			}

// 			// $objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
// 			// $objectline->fk_formation = $formationid;
// 			// $objectline->fk_user = $userid;
// 			// $objectline->interne_externe = GETPOST('interne_externe');
// 			// $objectline->date_debut_formation = $date_debut;
// 			// $objectline->date_fin_formation = $date_fin;
// 			// $objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm') : '');
// 			// $objectline->nombre_heure = $formation_static->nombre_heure;
// 			// $objectline->cout_pedagogique = $formation_static->cout;
// 			// $objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
// 			// $objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
// 			// $objectline->fk_societe = GETPOST('fk_societe');
// 			// $objectline->formateur = GETPOST('formateur');
// 			$objectline->numero_certificat = GETPOST('numero_certificat_valider');
// 			$objectline->resultat = GETPOST('resultat_valider');
// 			$objectline->status = UserFormation::STATUS_VALIDE;

// 			$result = $objectline->update($user);
// 		}

// 		// Création des habilitations 
// 		$resultcreateline = 1;
// 		if($result && $formation_static->type == 1) {
// 			$habilitationStatic = new Habilitation($db);
// 			$userHabilitation = new UserHabilitation($db);
			
// 			$listHabilitation = $habilitationStatic->getHabilitationsByFormation($formationid); 
// 			$userHabilitation->fk_user = $userid;
// 			$userHabilitation->status = UserHabilitation::STATUS_HABILITABLE;
// 			$userHabilitation->date_habilitation = $objectline->date_fin_formation;

// 			foreach($listHabilitation as $habilitation) {
//                 $userHabilitation->ref = $user_static->login."-".$habilitation->ref.'-'.dol_print_date($objectline->date_fin_formation, "%Y%m%d");
//                 $userHabilitation->fk_habilitation = $habilitation->id;
//                 $userHabilitation->date_fin_habilitation = dol_time_plus_duree($objectline->date_fin_formation, $habilitationStatic->validite_employeur, 'd');

// 				$resultcreateline = $userHabilitation->create($user);
// 				if($resultcreateline <= 0) {
// 					break;
// 				}
// 			}
// 		}

// 		if(!$error && $result > 0 && $resultcreateline > 0){
// 			$db->commit();
// 			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
// 			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
// 			exit;
// 		}
// 		elseif($result <= 0){
// 			$db->rollback();
// 			setEventMessages('Erreur lors de la validation de la formation', null, 'errors');
// 		}
// 		elseif($resultcreateline <= 0){
// 			$db->rollback();
// 			setEventMessages('Erreur lors de la création des habilitations', null, 'errors');
// 		}
// 	}
// 	else {
// 		$langs->load("errors");
// 		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
// 	}
// }