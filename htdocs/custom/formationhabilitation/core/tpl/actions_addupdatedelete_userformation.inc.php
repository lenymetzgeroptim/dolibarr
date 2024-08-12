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

if($action == 'addline' && $permissiontoaddline) {
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
		$objectline->status = GETPOST('status');

		$resultcreate = $objectline->create($user);
	}

	if(!$error && $resultcreate){
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
		exit;
	}
	elseif(!$error && !$resultcreate){
		setEventMessages($langs->trans($objectline->error), null, 'errors');
	}

}

if($action == 'updateline' && !GETPOSTISSET('save_datefinvalidite') && !$cancel && $permissiontoaddline){
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

if ($action == 'updateline' && GETPOSTISSET('save_datefinvalidite') && !$cancel && $permissiontoaddline) {
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