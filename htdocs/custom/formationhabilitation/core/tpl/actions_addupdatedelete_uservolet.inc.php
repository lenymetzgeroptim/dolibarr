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

	// if (!empty(GETPOST("datedebutvoletmonth", 'int')) && !empty(GETPOST("datedebutvoletmonthday", 'int')) && !empty(GETPOST("datedebutvoletmonthyear", 'int'))) {
	// 	$date_debut = dol_mktime(-1, -1, -1, GETPOST("datedebutvoletmonth", 'int'), GETPOST("datedebutvoletmonthday", 'int'), GETPOST("datedebutvoletmonthyear", 'int'));
	// }

	// if (!empty(GETPOST("datefinvoletmonth", 'int')) && !empty(GETPOST("datefinvoletmonthday", 'int')) && !empty(GETPOST("datefinvoletmonthyear", 'int'))) {
	// 	$date_fin = dol_mktime(-1, -1, -1, GETPOST("datefinvoletmonth", 'int'), GETPOST("datefinvoletmonthday", 'int'), GETPOST("datefinvoletmonthyear", 'int'));
	// }

	// if(GETPOST('status') == -1){
	// 	setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
	// 	$error++;
	// }

	if (!$error) {
		$volet = new Volet($db);
		$volet->fetch(GETPOST('fk_volet'));
		
		if($volet->typevolet == 1) { // Gestion des volets de formation
			$resultcreate = $objectline->generateNewVoletFormation(GETPOST('fk_user'), GETPOST('fk_volet'));
		}
		else { // Gestion des autres volets
			$objectline->ref = $objectline->getUniqueRef($user_static->login."_VOLET".$volet->nommage.'_'.dol_print_date(dol_now(), '%d%m%Y'));
			$objectline->fk_user = GETPOST('fk_user');
			$objectline->fk_volet = GETPOST('fk_volet');
	
			$resultcreate = $objectline->create($user);
		}
	}

	if(!$error && $resultcreate){
		$db->commit();
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
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
	$db->begin();

	$result = $object->add_object_linked($addlink, $addlinkid);

	if($result > 0) {
		$objectline->fetch($addlinkid);
		if($objectline->element == 'userhabilitation') {
			$objectline->status = $objectline::STATUS_HABILITE;
		}
		elseif($objectline->element == 'userautorisation') {
			$objectline->status = $objectline::STATUS_AUTORISE;
		}
		$result = $objectline->update($user);
	}

	if($result > 0) {
		$db->commit();

		$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
		if ($urltogo && empty($noback)) {
			header("Location: " . $urltogo);
			exit;
		}
	}
	else {
		$db->rollback();
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Delete link in table llx_element_element
if ($action == 'dellink' && !empty($permissiondellink) && !$cancellink && $dellinkid > 0) {
	$db->begin();

	$result = $object->deleteDomaineApplication($dellinkid);

	if($result > 0) {
		$result = $object->deleteObjectLinked($dellinkid, $addlink, $object->id, $object->table_element);
	}
	
	if($result > 0) {
		$objectline->fetch($dellinkid);
		if($objectline->element == 'userhabilitation') {
			$objectline->status = $objectline::STATUS_HABILITABLE;
		}
		elseif($objectline->element == 'userautorisation') {
			$objectline->status = $objectline::STATUS_AUTORISABLE;
		}
		$result = $objectline->update($user);
	}

	if($result > 0) {
		$db->commit();

		$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
		if ($urltogo && empty($noback)) {
			header("Location: " . $urltogo);
			exit;
		}
	}
	elseif ($result < 0) {
		$db->rollback();
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Action validate1 object
if ($action == 'confirm_validate1' && $confirm == 'yes' && $permissiontovalidate1) {
	$db->begin();
	$volet = new Volet($db);
	$volet->fetch($object->fk_volet);

	if (empty(GETPOST("date_debut_voletmonth", 'int')) || empty(GETPOST("date_debut_voletday", 'int')) || empty(GETPOST("date_debut_voletyear", 'int'))) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateDebutVolet")), null, 'errors');
		$error++;
	}
	$date_debut_volet = dol_mktime(-1, -1, -1, GETPOST("date_debut_voletmonth", 'int'), GETPOST("date_debut_voletday", 'int'), GETPOST("date_debut_voletyear", 'int'));

	if(!$error) {
		// TODOLENY : Gérer une date de fin en fonction du volet
		if($volet->typevolet == 2 || $volet->typevolet == 3) {
			$object->datedebutvolet = $date_debut_volet;
			$object->datefinvolet = $object->getDateFinVolet($volet);
		}
		$object->cloture = (!empty(GETPOST("close_volet")) ? 1 : 0);

		$result = $object->update($user);

		if($result > 0) {
			if($next_status == $object::STATUS_VALIDATION1) {
				$result = $object->validate1($user);
			}
			elseif($next_status == $object::STATUS_VALIDATION2) {
				$result = $object->validate2($user);
			}
			elseif($next_status == $object::STATUS_VALIDATION3) {
				$result = $object->validate3($user);
			}
			elseif($next_status == $object::STATUS_VALIDATION_WITHOUT_USER) {
				$result = $object->validate_without_user($user);
			}
			elseif($next_status == $object::STATUS_VALIDATED) {
				$result = $object->validate($user);
			}
		}
	}

	if (!$error && $result >= 0) {
		$db->commit();
		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
	} else {
		$db->rollback();
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action validate object
if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontovalidate) {
	if($next_status == $object::STATUS_VALIDATION1) {
		$result = $object->validate1($user);
	}
	elseif($next_status == $object::STATUS_VALIDATION2) {
		$result = $object->validate2($user);
	}
	elseif($next_status == $object::STATUS_VALIDATION3) {
		$result = $object->validate3($user);
	}
	elseif($next_status == $object::STATUS_VALIDATION_WITHOUT_USER) {
		$result = $object->validate_without_user($user);
	}
	elseif($next_status == $object::STATUS_VALIDATED) {
		$result = $object->validate($user);
	}

	if ($result >= 0) {
		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// // Action validate3 object
// if ($action == 'confirm_validate3' && $confirm == 'yes' && $permissiontovalidate2) {
// 	$result = $object->validate3($user);

// 	if ($result >= 0) {
// 		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
// 	} else {
// 		$error++;
// 		setEventMessages($object->error, $object->errors, 'errors');
// 	}
// 	$action = '';
// }

// // Action validate4 object
// if ($action == 'confirm_validate4' && $confirm == 'yes' && $permissiontovalidate4) {
// 	$db->begin();

// 	// TODOLENY : Gérer une date de fin en fonction du volet
// 	if(empty($object->datedebutvolet)) {
// 		$object->datedebutvolet = dol_now();
// 	}
// 	$object->datefinvolet = dol_time_plus_duree($object->datedebutvolet, 1, 'y');
// 	$result = $object->update($user);

// 	if($result > 0) {
// 		$result = $object->validate4($user);
// 	}

// 	if ($result >= 0) {
// 		$db->commit();
// 		setEventMessages($langs->trans('RecordValidated'), null, 'mesgs');
// 	} else {
// 		$db->rollback();
// 		$error++;
// 		setEventMessages($object->error, $object->errors, 'errors');
// 	}
// 	$action = '';
// }

if($action == 'confirm_genererPdf' && $confirm == 'yes' && $permissiontoaddline) {
        if ($object->fk_volet < 1) {
            setEventMessages("Vous devez sélectionner un volet", null, 'errors');
            $error++;
        }

        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            if (method_exists($object, 'generateDocument') && !$error) {
                $outputlangs = $langs;
                $newlang = '';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
                    $newlang = GETPOST('lang_id', 'aZ09');
                }
                if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                    $newlang = $object->thirdparty->default_lang;
                }
                if (!empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }

                $ret = $object->fetch($id); // Reload to get new records

                $model = '';

                $retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                if ($retgen < 0) {
                    setEventMessages($object->error, $object->errors, 'warnings');
                }
            }
        }
    }

	if ($action == 'updatedomaineapplication' && !$cancel && $permissiontoaddline) {
		$db->begin();
	
		if($lineid > 0){
			$result = $object->updateDomaineApplication($lineid, GETPOST('domaineapplication', 'int'), ($objectline->element == 'userhabilitation' ? 'habilitation' : 'autorisation'));

			if ($result) {
				$db->commit();
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
				if ($urltogo && empty($noback)) {
					header("Location: " . $urltogo);
					exit;
				}
			} else {
				$db->rollback();
				setEventMessages($objectline->error, $objectline->errors, 'warnings');
				$action = 'edit_domaineapplication';
			}
		}
		else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}