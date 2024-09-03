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

if($action == 'confirm_addline' && $confirm == 'yes' && $permissiontoaddline) {
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

	if(!$error) { // Gestion des prérequis 
		$formation->fetch(GETPOST('fk_formation'));
		$prerequis = explode(',', $formation->prerequis);
		foreach($prerequis as $formationid) {
			if(!$userFormation->userAsFormation(GETPOST('fk_user'), $formationid)) {
				$formation->fetch($formationid);
				setEventMessages($langs->trans('ErrorPrerequis', $formation->label), null, 'errors');
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

	if (!$error) {
		$objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date_fin, "%Y%m%d");
		$objectline->fk_user = GETPOST('fk_user');
		$objectline->fk_formation = GETPOST('fk_formation');
		$objectline->interne_externe = GETPOST('interne_externe');
		$objectline->date_debut_formation = $date_debut;
		$objectline->date_fin_formation = $date_fin;
		$objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm') : '');
		$objectline->nombre_heure = $nombre_heure;
		$objectline->cout_pedagogique = $formation_static->cout;
		$objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
		$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
		$objectline->fk_societe = GETPOST('fk_societe');
		$objectline->formateur = GETPOST('formateur');
		$objectline->numero_certificat = GETPOST('numero_certificat');
		$objectline->resultat = GETPOST('resultat');
		$objectline->status = GETPOST('status');

		$resultcreate = $objectline->create($user);
	}

	if(!$error && $resultcreate){
		$db->commit();
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
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
			$objectline->ref = $user_static->login."-".$formation_static->ref.'-'.dol_print_date($date_fin, "%Y%m%d");
			$objectline->interne_externe = GETPOST('interne_externe');
			$objectline->date_debut_formation = $date_debut;
			$objectline->date_fin_formation = $date_fin;
			$objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm') : '');
			$objectline->nombre_heure = $nombre_heure;
			$objectline->cout_pedagogique = $formation_static->cout;
			$objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
			$objectline->fk_societe = (GETPOST('interne_externe') != 2 ? GETPOST('fk_societe') : '');
			$objectline->formateur = (GETPOST('interne_externe') == 2 ? GETPOST('formateur') : '');
			$objectline->numero_certificat = GETPOST('numero_certificat');
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

		if(!$error && $resultupdate){
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
			exit;
		}
		elseif(!$error && !$resultupdate){
			setEventMessages($langs->trans($objectline->error), null, 'errors');
		}
		elseif($error) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&lineid='.$lineid.'#line_'.GETPOST('lineid', 'int').(!empty($onglet) ? "&onglet=$onglet" : ''));
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
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
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
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
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
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
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
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
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
			// $objectline->interne_externe = GETPOST('interne_externe');
			$objectline->date_debut_formation = $date_debut;
			$objectline->date_fin_formation = $date_fin;
			$objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm') : '');
			$objectline->nombre_heure = $formation_static->nombre_heure;
			$objectline->cout_pedagogique = $formation_static->cout;
			$objectline->cout_mobilisation = $user_static->thm * ($objectline->nombre_heure / 3600);
			$objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;
			// $objectline->fk_societe = GETPOST('fk_societe');
			// $objectline->formateur = GETPOST('formateur');
			$objectline->status = UserFormation::STATUS_PROGRAMMEE;

			$result = $objectline->create($user);
		}

		if(!$error && $result){
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		}
		elseif(!$result){
			setEventMessages($langs->trans($object->error), null, 'errors');
		}
	}
	else {
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
			// $objectline->date_finvalidite_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date_fin, $formation_static->periode_recyclage, 'm') : '');
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
		if($result && $formation_static->type == 1) {
			$habilitationStatic = new Habilitation($db);
			$userHabilitation = new UserHabilitation($db);
			
			$listHabilitation = $habilitationStatic->getHabilitationsByFormation($formationStatic->id); 
			$userHabilitation->fk_user = $userid;
			$userHabilitation->status = UserHabilitation::STATUS_HABILITABLE;
			$userHabilitation->date_habilitation = $objectline->date_fin_formation;

			foreach($listHabilitation as $habilitation) {
                $userHabilitation->ref = $user_static->login."-".$habilitation->ref.'-'.dol_print_date($objectline->date_fin_formation, "%Y%m%d");
                $userHabilitation->fk_habilitation = $habilitation->id;
                $userHabilitation->date_fin_habilitation = dol_time_plus_duree($objectline->date_fin_formation, $habilitation_static->validite_employeur, 'd');

				$resultcreateline = $userHabilitation->create($user);
				if(!$resultcreateline) {
					$error++;
				}
			}
		}

		if(!$error && $result){
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		}
		elseif(!$result){
			$db->rollback();
			setEventMessages($langs->trans($object->error), null, 'errors');
		}
	}
	else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
	}
}