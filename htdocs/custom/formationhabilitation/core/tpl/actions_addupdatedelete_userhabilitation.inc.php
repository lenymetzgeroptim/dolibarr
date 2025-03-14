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
	$db->begin();

	$userHabilitation = new UserHabilitation($db);
	$userFormation = new UserFormation($db);

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

	// Prérequis
	$elementPrerequis = new ElementPrerequis($db);
	if(!$error && (empty(GETPOST('forcecreation')) || !$permissiontoforceline) && $elementPrerequis->gestionPrerequis(GETPOST('fk_user'), $habilitation_static, 0) < 0) {
		$error++;
	}

	if (!$error) {
		$objectline->ref = $user_static->login."-".$habilitation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
		$objectline->fk_habilitation = GETPOST('fk_habilitation');
		$objectline->date_habilitation = $date;
		$objectline->date_fin_habilitation = $elementPrerequis->getDatePrerequis(GETPOST('fk_user'), $habilitation_static, 0);
		$objectline->fk_user = GETPOST('fk_user');
		$objectline->domaineapplication = GETPOST('domaineapplication', 'int');
		$objectline->status = GETPOST('status');

		$resultcreate = $objectline->create($user, false, false, !empty(GETPOST('forcecreation')));
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
		$objectline->oldcopy = clone $objectline;

		if (empty(GETPOST("date_habilitationmonth", 'int')) || empty(GETPOST("date_habilitationday", 'int')) || empty(GETPOST("date_habilitationyear", 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateHabilitation")), null, 'errors');
			$error++;
		}
		$date = dol_mktime(-1, -1, -1, GETPOST("date_habilitationmonth", 'int'), GETPOST("date_habilitationday", 'int'), GETPOST("date_habilitationyear", 'int'));

		$date_fin = dol_mktime(-1, -1, -1, GETPOST("date_fin_habilitationmonth", 'int'), GETPOST("date_fin_habilitationday", 'int'), GETPOST("date_fin_habilitationyear", 'int'));

		// if(GETPOST('status') == -1 || empty(GETPOST('status'))){
		// 	setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
		// 	$error++;
		// }

		if (!$error) {
			$objectline->ref = $user_static->login."-".$habilitation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
			$objectline->date_habilitation = $date;
			$objectline->date_fin_habilitation = (!empty($date_fin) && $date_fin > $date ? $date_fin : '');
			$objectline->domaineapplication = GETPOST('domaineapplication');
			//$objectline->status = GETPOST('status');

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

if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontodeleteline) {
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

if ($action == 'confirm_generation_auto' && $confirm == 'yes' && $permissiontoaddline) {
	$db->begin();

	if(!($object->id > 0)){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
		$error++;
	}

	if(empty(GETPOST('object_generate'))){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv($objectparentline->element.'togenerate')), null, 'errors');
		$error++;
	}

	if(!$error) {
		$txtListHabilitation = '';
		$resultcreatelinehabilitations = $objectparentline->generateHabilitationsForUser($object->id, null, $txtListHabilitation, 0, explode(",", GETPOST('object_generate')));

		if($resultcreatelinehabilitations < 0) {
			setEventMessages('Erreur lors de la création des habilitations', null, 'errors');
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
		exit;
    } else {
        $db->rollback();
       // setEventMessages($object->error, $object->errors, 'errors');
    }
    $action = '';
}