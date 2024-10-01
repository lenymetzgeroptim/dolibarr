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
 *	\file			htdocs/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userautorisation.inc.php
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
	$db->begin();

	if(!(GETPOST('fk_user') > 0)){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
		$error++;
	}

	if (!empty(GETPOST("datedebutvoletmonth", 'int')) && !empty(GETPOST("datedebutvoletmonthday", 'int')) && !empty(GETPOST("datedebutvoletmonthyear", 'int'))) {
		$date_debut = dol_mktime(-1, -1, -1, GETPOST("datedebutvoletmonth", 'int'), GETPOST("datedebutvoletmonthday", 'int'), GETPOST("datedebutvoletmonthyear", 'int'));
	}

	if (!empty(GETPOST("datefinvoletmonth", 'int')) && !empty(GETPOST("datefinvoletmonthday", 'int')) && !empty(GETPOST("datefinvoletmonthyear", 'int'))) {
		$date_fin = dol_mktime(-1, -1, -1, GETPOST("datefinvoletmonth", 'int'), GETPOST("datefinvoletmonthday", 'int'), GETPOST("datefinvoletmonthyear", 'int'));
	}

	if(GETPOST('status') == -1){
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$objectline->ref = $user_static->login."-Volet".GETPOST('numvolet');
		$objectline->fk_user = GETPOST('fk_user');
		$objectline->numvolet = GETPOST('numvolet');
		if($date_debut) {
			$objectline->datedebutvolet = $date_debut;
		}
		if($date_fin) {
			$objectline->datefinvolet = $date_fin;
		}
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

// if($action == 'updateline' && !$cancel && $permissiontoaddline){
// 	if($lineid > 0){
// 		$objectline->fetch($lineid);

// 		if (empty(GETPOST("date_autorisationmonth", 'int')) || empty(GETPOST("date_autorisationday", 'int')) || empty(GETPOST("date_autorisationyear", 'int'))) {
// 			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateAutorisation")), null, 'errors');
// 			$error++;
// 		}
// 		$date = dol_mktime(-1, -1, -1, GETPOST("date_autorisationmonth", 'int'), GETPOST("date_autorisationday", 'int'), GETPOST("date_autorisationyear", 'int'));


// 		if(GETPOST('status') == -1 || empty(GETPOST('status'))){
// 			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
// 			$error++;
// 		}

// 		if (!$error) {
// 			$objectline->ref = $user_static->login."-".$autorisation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
// 			$objectline->date_autorisation = $date;
// 			$objectline->date_fin_autorisation = dol_time_plus_duree($date, $autorisation_static->validite_employeur, 'd');
// 			$objectline->status = GETPOST('status');

// 			$resultupdate = $objectline->update($user);
// 		}

// 		if(!$error && $resultupdate){
// 			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
// 			// header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
// 			header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
// 			exit;
// 		}
// 		elseif(!$error && !$resultupdate){
// 			setEventMessages($langs->trans($objectline->error), null, 'errors');
// 		}
// 		elseif($error) {
// 			header('Location: '.$_SERVER["PHP_SELF"].'?'.($param ? $param : '').'&action=editline&lineid='.$lineid.'#line_'.GETPOST('lineid', 'int'));
// 			exit;
// 		}
// 	}
// 	else {
// 		$langs->load("errors");
// 		setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
// 	}
// }

// if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoaddline) {
//     $resultdelete = $object->deleteLine($user, $lineid);
//     if ($resultdelete > 0) {
//         setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
//         // header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($onglet) ? "&onglet=$onglet" : ''));
// 		header('Location: '.$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
// 		exit;
//     } else {
//         $error++;
//         setEventMessages($object->error, $object->errors, 'errors');
//     }
//     $action = '';
// }

// Link object to another object
if ($action == 'addlink' && !empty($permissiondellink) && $id > 0 && $addlinkid > 0) {
	$result = $object->add_object_linked($addlink, $addlinkid);

	if($result > 0) {
		$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
		if ($urltogo && empty($noback)) {
			header("Location: " . $urltogo);
			exit;
		}
	}
}

// Delete link in table llx_element_element
if ($action == 'dellink' && !empty($permissiondellink) && !$cancellink && $dellinkid > 0) {
	$result = $object->deleteObjectLinked($dellinkid, $addlink, $object->id, $object->table_element);
	
	if($result > 0) {
		$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
		if ($urltogo && empty($noback)) {
			header("Location: " . $urltogo);
			exit;
		}
	}
	elseif ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Action validate1 object
if ($action == 'confirm_validate1' && $confirm == 'yes' && $permissiontovalidate1) {
	$result = $object->validate1($user);

	if ($result >= 0) {
		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action validate2 object
if ($action == 'confirm_validate2' && $confirm == 'yes' && $permissiontovalidate2) {
	$result = $object->validate2($user);

	if ($result >= 0) {
		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action validate3 object
if ($action == 'confirm_validate3' && $confirm == 'yes' && $permissiontovalidate2) {
	$result = $object->validate3($user);

	if ($result >= 0) {
		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action validate4 object
if ($action == 'confirm_validate4' && $confirm == 'yes' && $permissiontovalidate4) {
	$result = $object->validate4($user);

	if ($result >= 0) {
		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
		// Define output language
		// if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
		// 	if (method_exists($object, 'generateDocument')) {
		// 		$outputlangs = $langs;
		// 		$newlang = '';
		// 		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
		// 			$newlang = GETPOST('lang_id', 'aZ09');
		// 		}
		// 		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
		// 			$newlang = !empty($object->thirdparty->default_lang) ? $object->thirdparty->default_lang : "";
		// 		}
		// 		if (!empty($newlang)) {
		// 			$outputlangs = new Translate("", $conf);
		// 			$outputlangs->setDefaultLang($newlang);
		// 		}

		// 		$ret = $object->fetch($id); // Reload to get new records

		// 		$model = $object->model_pdf;

		// 		$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
		// 		if ($retgen < 0) {
		// 			setEventMessages($object->error, $object->errors, 'warnings');
		// 		}
		// 	}
		// }
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}