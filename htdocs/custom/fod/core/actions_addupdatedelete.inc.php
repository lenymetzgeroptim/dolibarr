<?php
/* Copyright (C) 2017-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *	\file			htdocs/custom/fod/core/actions_addupdatedelete.inc.php
 *  \brief			Code for common actions for fod
 */


// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined
// $triggermodname may be defined
$validate_bilan_inter = 0;

if (!empty($permissionedit) && empty($permissiontoadd)) {
	$permissiontoadd = $permissionedit; // For backward compatibility
}

if ($cancel) {
	/*var_dump($cancel);
	var_dump($backtopage);exit;*/
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

// Action pour prolonger la FOD
if ($action == 'confirm_prolonger' && $confirm == 'yes' && $permissionToProlongerFOD) {
	$result = $object->prolonger($user);
	if ($result >= 0) {
		$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Prolongation de la FOD jusqu'au ".substr($db->idate($object->date_fin_prolong), 0, 10).' par '.$user->firstname.' '.$user->lastname.'</span><br>';
		$res = $object->update($user);
		
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('La FOD a été prolongée', null, 'mesgs');
	} else {
		setEventMessages('Impossible de prolonger la FOD', null, 'errors');
	}
}

// Action pour refuser la FOD
if ($action == 'confirm_refus' && $confirm == 'yes' && ($permissionToRefuserFOD || $user->id == $object->fk_user_raf || $user->admin)) {
	$result = $object->setDraft($user);

	$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : '.$user->firstname." ".$user->lastname.' a refusé la FOD "'.GETPOST('raison_refus').'"</span><br>';
	$object->update($user);
	
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('La FOD a été refusée', null, 'mesgs');
	} else {
		setEventMessages('Impossible de refuser la FOD', null, 'errors');
	}
}

// Action pour refuser le bilan de la FOD
if ($action == 'confirm_refus_bilan' && $confirm == 'yes' && $userIsRd) {
	$result = $object->refusbilan($user);

	$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Le RD ('.$user->firstname." ".$user->lastname.') a refusé le bilan de la FOD "'.GETPOST('raison_refus_bilan').'"</span><br>';
	$object->update($user);
	
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('Le bilan de la FOD a été refusée', null, 'mesgs');
	} else {
		setEventMessages('Impossible de refuser le bilan de la FOD', null, 'errors');
	}
}

// Action pour passer la FOD en AOA
/*if ($action == 'confirm_aoa' && $confirm == 'yes') {
	$result = $object->aoa($user);
	if ($result >= 0) {
		setEventMessages('Passage de la FOD en AOA', null, 'mesgs');
	} else {
		setEventMessages('Impossible de passer la FOD en AOA', null, 'errors');
	}
}*/


// Action pour générer un document
if ($action == 'confirm_genererDocFod' && $confirm == 'yes' && $permissiontoGenerateDocFod) {
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
		if (method_exists($object, 'generateDocument')) {
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

			$model = $object->model_pdf;

			$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($retgen < 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
		}
	}
}

// Action pour générer un document de l'historique
if ($action == 'confirm_genererHistoriqueFod' && $confirm == 'yes' && $permissiontoGenerateHistoriqueFod) {
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
		if (method_exists($object, 'generateDocument')) {
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

			$model = $object->model_pdf;

			$retgen = $object->generateDocument('historique_fod', $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($retgen < 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
		}
	}
}

// Action pour valider la FOD par le PCR
if ($action == 'confirm_validate' && $confirm == 'yes' && ($user->id == $object->fk_user_pcr || $userPCR || $user->admin)) {
	$result = $object->validate($user);
	if ($result >= 0) {
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			if (method_exists($object, 'generateDocument')) {
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

				$model = $object->model_pdf;

				$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($retgen < 0) {
					setEventMessages($object->error, $object->errors, 'warnings');
				}
			}
		}

		$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Validation de la FOD par la PCR ('.$user->firstname.' '.$user->lastname.')</span><br>';
		$object->update($user);

		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('Validation de la FOD par la PCR', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider la FOD', null, 'errors');
	}
	$action = '';
}

// Action pour valider (1ere validation) la FOD par le RA
if ($action == 'confirm_validatera' && $confirm == 'yes' && ($user->id == $object->fk_user_raf || $userIsRaf || $user->admin)) {
	if ($object->status == $object::STATUS_AOA){
		$object->aoa = 3;
		$object->update($user);
	}
	$result = $object->validaterarsr($user);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('Validation de la FOD par le RA', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider la FOD', null, 'errors');
	}
}

// Action pour valider (1ere validation) la FOD par le RSR
if ($action == 'confirm_validatersr' && $confirm == 'yes' && ($user->id == $object->fk_user_rsr || $user->admin)) {
	if ($object->status == $object::STATUS_AOA){
		$object->aoa = 3;
		$object->update($user);
	}

	$result = $object->validatersr($user);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('Validation de la FOD par le RSR', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider la FOD', null, 'errors');
	}
}

// Action pour valider la fod (2e validation) par le RA ou RSR
if ($action == 'confirm_validaterarsr' && $confirm == 'yes' && ($user->id == $object->fk_user_raf || $userIsRaf || $user->admin)) {
	$result = $object->validaterarsr($user);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('La FOD a bien été validée', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider la FOD', null, 'errors');
	}
}

// Action pour passer la fod au bilan 
if ($action == 'confirm_bilan' && $confirm == 'yes' && $permissionToBilan) {
	$result = $object->bilan($user);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('Passage de la FOD en bilan', null, 'mesgs');
	} else {
		setEventMessages('Vous ne pouvez pas valider le bilan de la FOD', null, 'errors');
	}
}

// Action pour cloture la fod
if ($action == 'confirm_cloture' && $confirm == 'yes' && $permissionToCloture) {
	$result = $object->cloture($user);
	if ($result >= 0) {
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			if (method_exists($object, 'generateDocument')) {
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

				//$ret = $object->fetch($id); // Reload to get new records

				$model = $object->model_pdf;

				$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($retgen < 0) {
					setEventMessages($object->error, $object->errors, 'warnings');
				}
			}
		}

		$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Clôture de la FOD par ".$user->firstname.' '.$user->lastname."</span><br>";
		$object->update($user);
		
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
		//setEventMessages('La FOD est clôturée', null, 'mesgs');
	} else {
		setEventMessages('Impossible de clôturer la FOD', null, 'errors');
	}
}

if ($action == 'update_userfod') {
	$user_fod = New Fod_user($db);
	if($intervid > 0){
		$user_static = new User($db);
		$user_static->fetch($intervid);
		$user_fod_id = $user_fod->getIdWithUserAndFod($intervid, $object->id);
		$user_fod->fetch($user_fod_id);
		$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : ".$user->firstname.' '.$user->lastname." a validé le bilan de ".$user_static->firstname.' '.$user_static->lastname."</span><br>";
	}
	else{
		$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : ".$user->firstname.' '.$user->lastname." a validé son bilan</span><br>";
		$user_fod_id = $user_fod->getIdWithUserAndFod($user->id, $object->id);
		$user_fod->fetch($user_fod_id);
	}
	foreach ($user_fod->fields as $key => $val) {
		// Check if field was submited to be edited
		if ($user_fod->fields[$key]['type'] == 'duration') {
			if (!GETPOSTISSET($key.'hour') || !GETPOSTISSET($key.'min')) {
				continue; // The field was not submited to be edited
			}
		} elseif ($user_fod->fields[$key]['type'] == 'boolean') {
			if (!GETPOSTISSET($key)) {
				$user_fod->$key = 0; // use 0 instead null if the field is defined as not null
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
		if (preg_match('/^(text|html)/', $user_fod->fields[$key]['type'])) {
			$tmparray = explode(':', $user_fod->fields[$key]['type']);
			if (!empty($tmparray[1])) {
				$value = GETPOST($key, $tmparray[1]);
			} else {
				$value = GETPOST($key, 'restricthtml');
			}
		} elseif ($user_fod->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));	// for date without hour, we use gmt
		} elseif ($user_fod->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
		} elseif ($user_fod->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour', 'int') != '' || GETPOST($key.'min', 'int') != '') {
				$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
			} else {
				$value = '';
			}
		} elseif (preg_match('/^(integer|price|real|double)/', $user_fod->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
		} elseif ($user_fod->fields[$key]['type'] == 'boolean') {
			$value = ((GETPOST($key, 'aZ09') == 'on' || GETPOST($key, 'aZ09') == '1') ? 1 : 0);
		} elseif ($user_fod->fields[$key]['type'] == 'reference') {
			$value = array_keys($user_fod->param_list)[GETPOST($key)].','.GETPOST($key.'2');
		} else {
			$value = GETPOST($key, 'alpha');
		}
		if (preg_match('/^integer:/i', $user_fod->fields[$key]['type']) && $value == '-1') {
			$value = ''; // This is an implicit foreign key field
		}
		if (!empty($user_fod->fields[$key]['foreignkey']) && $value == '-1') {
			$value = ''; // This is an explicit foreign key field
		}

		$user_fod->$key = $value;
		if ($val['notnull'] > 0 && $user_fod->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILAN && $key == 'rex_intervenant' && empty($user_fod->$key)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	if (!$error) {
		$result = $user_fod->update($user);
		$result = $result  && $object->update($user);
		if ($result > 0) {
		} else {
			// Creation KO
			setEventMessages($user_fod->error, $user_fod->errors, 'errors');
		}
	}
	else {
		$action = 'editintervenant';
		$massaction = '';;
	}
}

// Verifie si tous les intervenants ont validé leur bilan
if ($action == 'confirm_verif_bilan_inter' && $confirm == 'yes' && ($user_interv || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)) {
	setEventMessages('Validation du bilan par '.$user->firstname.' '.$user->lastname, null, 'mesgs');
	$all = 1;
	foreach($object->listIntervenantsForFod() as $intervenant){
		$user_fod = New Fod_user($db);
		$user_fod_id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
		$user_fod->fetch($user_fod_id);
		if ($user_fod->prise_en_compte_fin != 1){
			$all = 0;
			break;
		}
	}
	if ($all){
		$action = 'validate_bilan_inter';
		$validate_bilan_inter = 1;
	}
}

// Action pour valider le bilan de la fod par tous les intervenants (validation automatique apres toutes les validation des intervenants)
if ($action == 'validate_bilan_inter' && $validate_bilan_inter) {
	$result = $object->validatebilanInter($user);
	if ($result >= 0) {
		setEventMessages('Tous les intervenants ont validé le bilan de la FOD', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider le bilan de la FOD', null, 'errors');
	}
}

// Action pour valider le bilan de la fod (1e validation par rsr)
if ($action == 'confirm_validate_bilan_rsr' && $confirm == 'yes' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)) {
	$result = $object->validatebilanrsr($user);
	if ($result >= 0) {
		setEventMessages('Validation du bilan par le RSR', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider le bilan de la FOD', null, 'errors');
	}
}

// Action pour valider le bilan de la fod (2e validation par ra)
if ($action == 'confirm_validate_bilan_rsrra' && $confirm == 'yes' && ($user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)) {
	$result = $object->validatebilanrsrra($user);
	if ($result >= 0) {
		setEventMessages('Validation du bilan par le RA', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider le bilan de la FOD', null, 'errors');
	}
}

// Action pour valider le bilan de la fod (3e validation par pcr)
if ($action == 'confirm_validate_bilan_rsrrapcr' && $confirm == 'yes' && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)) {
	$result = $object->validatebilanrsrrapcr($user);
	if ($result >= 0) {
		setEventMessages('Validation du bilan par la PCR', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider le bilan de la FOD', null, 'errors');
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && $permissionToClone) {
	if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers')) {
		setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
	} else {
		$objectutil = dol_clone($object, 1); // To avoid to denaturate loaded object when setting some properties for clone or if createFromClone modifies the object. We use native clone to keep this->db valid.
		//$objectutil->date = dol_mktime(12, 0, 0, GETPOST('newdatemonth', 'int'), GETPOST('newdateday', 'int'), GETPOST('newdateyear', 'int'));
		// ...
		$result = $objectutil->createFromClone($user, (($object->id > 0) ? $object->id : $id));
		if (is_object($result) || $result > 0) {
			$newid = 0;
			if (is_object($result)) {
				$newid = $result->id;
			} else {
				$newid = $result;
			}

			if (empty($noback)) {
				header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $newid); // Open record of new object
				exit;
			}
		} else {
			$error++;
			setEventMessages($objectutil->error, $objectutil->errors, 'errors');
			$action = '';
		}
	}
}

// Action to add record
if ($action == 'add' && !empty($permissiontoadd)) {
	foreach ($object->fields as $key => $val) {
		if ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour') == '' && GETPOST($key.'min') == '') {
				continue; // The field was not submited to be edited
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
			if (!in_array(abs($val['visible']), array(1, 3))) {
				continue; // Only 1 and 3 that are case to create
			}
		}

		// Set value to insert
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key, 'restricthtml');
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));	// for date without hour, we use gmt
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
		} elseif ($object->fields[$key]['type'] == 'duration') {
			$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			$value = ((GETPOST($key) == '1' || GETPOST($key) == 'on') ? 1 : 0);
		} elseif ($object->fields[$key]['type'] == 'reference') {
			$tmparraykey = array_keys($object->param_list);
			$value = $tmparraykey[GETPOST($key)].','.GETPOST($key.'2');
		} else {
			$value = GETPOST($key, 'alphanohtml');
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
			$value = ''; // This is an implicit foreign key field
		}
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
			$value = ''; // This is an explicit foreign key field
		}

		//var_dump($key.' '.$value.' '.$object->fields[$key]['type']);
		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && !is_null($val['default']) && $val['default'] == '(PROV)') {
			$object->$key = '(PROV)';
		}
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if (($key == 'prop_radiologique' || $key == 'risques' || $key == 'rex' || $key == 'ri' || $key == 'objectif_proprete') && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'effectif' && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'debit_dose_estime' && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'debit_dose_max' && !empty($object->$key) && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'duree_intervention' && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		/*if ($key == 'ref' && ($object->$key < 1 ||  $object->$key > 9999 || !is_numeric($object->$key) || strlen($object->$key) != 4)) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv('NumeroChrono')), null, 'errors');
		}*/
		if ($key == 'indice' && (!ctype_alpha($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv('Indice')), null, 'errors');
		}
		/*if ($key == 'ref' && GETPOST('fk_project')>0){
			if(!$object->VerifNumChrono($value, GETPOST('fk_project'))){
				$error++;
				setEventMessages('Ce numéro chrono est deja utilisé sur cette affaire', null, 'errors');
			}
		}*/
	}

 	if ($object->date_debut > $object->date_fin) {
		$error++;
		setEventMessages('La date de début de la FOD ne peut pas être après la date de fin ', null, 'errors');
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
		}
	}

	$projet = New Project($db);
	$projet->fetch($object->fk_project);
	$refp = explode('-', $projet->ref);
	$nb_fod = $object->getNbFOD($projet->id) + 1;
	$object->ref = 'FOD '.str_replace(' ', '', $refp[0]).' '.str_pad($nb_fod, 4, "0", STR_PAD_LEFT);

	if (!$error) {
		$result = $object->create($user);
		if ($result > 0) {
			// Creation OK
			$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		} else {
			// Creation KO
			if (!empty($object->errors)) {
				setEventMessages(null, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, null, 'errors');
			}
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}
elseif ($action == 'add' && empty($permissiontoadd)){
	$urltogo = dol_buildpath('/fod/fod_card.php?action=create', 1);
	header("Location: ".$urltogo);
	setEventMessages($langs->trans("Vous n'êtes pas autorisés à créer une FOD sur cette affaire"), null, 'errors');
	exit;
}

// Action to update record
if ($action == 'update' && (!empty($permissionToModifierFOD) || $object->status == Fod::STATUS_BILANinter || $object->status == Fod::STATUS_BILANRSR || $object->status == Fod::STATUS_BILANRSRRA 
|| $object->status == Fod::STATUS_BILANRSRRAPCR || $object->status == Fod::STATUS_BILAN_REFUS)) {
	$modification = '';
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

		if ($object->$key != $value){
			/*if ($key == 'ref'){
				$refp = explode('_', $object->$key);
				if($refp[2] != $value){
					$modification .= '<li>'.$langs->trans($val['label']).' : '.$refp[2].' <strong>-></strong> '.$value.'</li>';
				}
			}*/
			if ($key == 'date_debut' || $key == 'date_fin' || $key == 'date_fin_prolong'){
				if (dol_print_date($object->$key, '%d/%m/%Y') != dol_print_date($value, '%d/%m/%Y')){
					$modification .= '<li>'.$langs->trans($val['label']).' : '.dol_print_date($object->$key, '%d/%m/%Y').' <strong>-></strong> '.dol_print_date($value, '%d/%m/%Y').'</li>';
				}
			}
			else if ($key == "etat_installation" || $key == "coef_exposition" || $key == "prop_radiologique" || $key == "risques" || $key == "rex" || $key == "ri" || $key == "objectif_proprete"){
				$prefix = $object->fields[$key]['arrayofkeyval'];
				$modification .= '<li>'.$langs->trans($val['label']).' : '.$prefix[$object->$key].' <strong>-></strong> '.$prefix[$value].'</li>';
			}
			else if($key == "fk_user_pcr" || $key == "fk_user_raf" || $key == "fk_user_rsr"){
				$user_static = New User($db);
				$user_static->fetch($object->$key);
				$prenom_avant = $user_static->firstname;
				$nom_avant = $user_static->lastname;
				$user_static->fetch($value);
				$prenom_apres = $user_static->firstname;
				$nom_apres = $user_static->lastname;
				$modification .= '<li>'.$langs->trans($val['label']).' : '.$prenom_avant.' '.$nom_avant.' <strong>-></strong> '.$prenom_apres.' '.$nom_apres.'</li>';
			}
			else if($key == "fk_project"){
				$projet_static = New Project($db);
				$projet_static->fetch($object->$key);
				$ref_avant = $projet_static->ref;
				$projet_static->fetch($value);
				$ref_apres = $projet_static->ref;
				$modification .= '<li>'.$langs->trans($val['label']).' : '.$ref_avant.' <strong>-></strong> '.$ref_apres.'</li>';

				$refp = explode('-', $projet_static->ref);
				$nb_fod = $object->getNbFOD($projet_static->id) + 1;
				$ancienne_ref = $object->ref;
				$object->ref = dol_string_nospecial('FOD '.str_replace(' ', '', $refp[0]).' '.str_pad($nb_fod, 4, "0", STR_PAD_LEFT));
				$modification .= '<li>Ref : '.$ancienne_ref.' <strong>-></strong> '.$object->ref.'</li>';
			}
			else {
				$modification .= '<li>'.$langs->trans($val['label']).' : '.$object->$key.' <strong>-></strong> '.$value.'</li>';
			}
		}

		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if (($key == 'prop_radiologique' || $key == 'risques' || $key == 'rex' || $key == 'ri' || $key == 'objectif_proprete') && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'effectif' && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'debit_dose_estime' && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'debit_dose_max' && !empty($object->$key) && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($key == 'duree_intervention' && ($object->$key < 0 || !is_numeric($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		/*if ($key == 'ref' && ($object->$key < 1 ||  $object->$key > 9999 || !is_numeric($object->$key) || strlen($object->$key) != 4)) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv('NumeroChrono')), null, 'errors');
		}*/
		if ($key == 'indice' && (!ctype_alpha($object->$key))) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv('Indice')), null, 'errors');
		}
		/*if ($key == 'ref' && GETPOST('fk_project')>0){
			if(!$object->VerifNumChrono($value, GETPOST('fk_project'))){
				$error++;
				setEventMessages('Ce numéro chrono est deja utilisé sur cette affaire', null, 'errors');
			}
		}*/
		if ($object->status == Fod::STATUS_BILANinter && $key == 'rex_rsr' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANinter && $key == 'q1_doses_individuelles' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANinter && $key == 'q2_doses_individuelles' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANinter && $key == 'q3_doses_individuelles' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANinter && $key == 'q1_dose_collective' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANinter && $key == 'q2_dose_collective' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANinter && $key == 'q1_contamination' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANinter && $key == 'q2_contamination' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANRSR && $key == 'rex_ra' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANRSRRA && $key == 'q1_siseri' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANRSRRA && $key == 'rex_pcr' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANRSRRA && $key == 'q1_radiopotection' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if ($object->status == Fod::STATUS_BILANRSRRAPCR && $key == 'rex_rd' && $object->$key == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	if ($object->date_debut > $object->date_fin) {
		$error++;
		setEventMessages('La date de début de la FOD ne peut pas être après la date de fin ', null, 'errors');
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}
	}

	if (!empty($modification)){
		$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Modification de la FOD par '.$user->firstname.' '.$user->lastname.' :<br><ul>'.$modification.'</ul></span>';
	}

	if (!$error) {
		$result = $object->update($user);
		if ($result > 0) {
			$action = 'view';
		} else {
			// Creation KO
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'edit';
		}
	} else {
		if($object->status == Fod::STATUS_BILANinter){
			$action = 'editrsr';
			$massaction = '';
		}
		elseif($object->status == Fod::STATUS_BILANRSR){
			$action = 'editra';
			$massaction = '';
		}
		elseif($object->status == Fod::STATUS_BILANRSRRA){
			$action = 'editpcr';
			$massaction = '';
		}
		elseif($object->status == Fod::STATUS_BILANRSRRAPCR){
			$action = 'editrd';
			$massaction = '';
		}
		else {
			$action = 'edit';
		}
	}


}

// Action to update AOA
if ($action == 'updateaoa' && !empty($permissionToModifierAOA)) {
	$modification_aoa = "";
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
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type']) && !(($key == 'debit_dose_max_optimise' || $key == 'debit_dose_estime_optimise' || $key == 'effectif_optimise' || $key == 'duree_intervention_optimise') && GETPOST($key, 'alphanohtml') == 'NA')) {
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

		if ($object->$key != $value){
			if (($key == "debit_dose_max_optimise" || $key == "debit_dose_estime_optimise" || $key == "effectif_optimise" || $key == "duree_intervention_optimise" 
			|| $key == "prop_radiologique_optimise" || $key == "epi_specifique" || $key == "consignes_rp" || $key == "commentaire_aoa") && ($value != 'NA' || !empty($object->$key))){
				if($key == "prop_radiologique_optimise" || $key == "epi_specifique" || $key == "consignes_rp"){
					if($value > 0 || $object->$key > 0){
						$prefix = $object->fields[$key]['arrayofkeyval'];
						$modification_aoa .= '<li>'.$langs->trans($val['label']).' : '.$prefix[$object->$key].' <strong>-></strong> '.$prefix[$value].'</li>';
					}
				}
				else{
					$modification_aoa .= '<li>'.$langs->trans($val['label']).' : '.$object->$key.' <strong>-></strong> '.$value.'</li>';
				}
			}
		}

		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if (($key == 'debit_dose_max_optimise' || $key == 'debit_dose_estime_optimise' || $key == 'effectif_optimise' || $key == 'duree_intervention_optimise') && !empty($object->$key) && ($object->$key < 0 || !is_numeric($object->$key)) && $object->$key != 'NA') {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if (($key == 'debit_dose_max_optimise' || $key == 'debit_dose_estime_optimise' || $key == 'effectif_optimise' || $key == 'duree_intervention_optimise') && empty($object->$key)) {
			$error++;
			setEventMessages($langs->trans("ChampVideAOA", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if (($key == 'prop_radiologique_optimise' || $key == 'epi_specifique' || $key == 'consignes_rp') && empty($object->$key)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
		if (($key == 'debit_dose_max_optimise' || $key == 'debit_dose_estime_optimise' || $key == 'effectif_optimise' || $key == 'duree_intervention_optimise' || $key == 'prop_radiologique_optimise') && $object->$key == 'NA') {
			$object->$key = '';
		}
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}
	}

	if (!empty($modification_aoa)){
		$object->historique .= '<span style="color: #d5a000;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Modification de l'AOA de la FOD par ".$user->firstname.' '.$user->lastname.' :<br><ul>'.$modification_aoa.'</ul></span>';
	}

	if (!$error) {
		$result = $object->update($user);
		if ($result > 0) {
			$action = 'view';
		} else {
			// Creation KO
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editaoa';
		}
	} else {
		$action = 'editaoa';
	}


}

// Action to add Cdd
if ($action == 'addcdd' && !empty($permissiontoadd)) {
	foreach ($object->fields as $key => $val) {
		if ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour') == '' && GETPOST($key.'min') == '') {
				continue; // The field was not submited to be edited
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
			if (!in_array(abs($val['visible']), array(1, 3))) {
				continue; // Only 1 and 3 that are case to create
			}
		}

		// Set value to insert
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key, 'restricthtml');
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));	// for date without hour, we use gmt
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
		} elseif ($object->fields[$key]['type'] == 'duration') {
			$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			$value = ((GETPOST($key) == '1' || GETPOST($key) == 'on') ? 1 : 0);
		} elseif ($object->fields[$key]['type'] == 'reference') {
			$tmparraykey = array_keys($object->param_list);
			$value = $tmparraykey[GETPOST($key)].','.GETPOST($key.'2');
		} else {
			$value = GETPOST($key, 'alphanohtml');
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
			$value = ''; // This is an implicit foreign key field
		}
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
			$value = ''; // This is an explicit foreign key field
		}

		//var_dump($key.' '.$value.' '.$object->fields[$key]['type']);
		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && !is_null($val['default']) && $val['default'] == '(PROV)') {
			$object->$key = '(PROV)';
		}
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	if(in_array($object->options_cat_med, $object->getCatMedicalCdDExistante(null, $object->mensuelle))){
		$error++;
		setEventMessages('Une CdD avec cette catégorie médical existe deja', null, 'errors');
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
		}
	}

	if (!$error) {
		$result = $object->create($user);
		if ($result > 0) {
			// Creation OK
			$urltogo = $backurlforlist;
			header("Location: ".$urltogo);
			exit;
		} else {
			// Creation KO
			if (!empty($object->errors)) {
				setEventMessages(null, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, null, 'errors');
			}
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}