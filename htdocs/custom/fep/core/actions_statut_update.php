<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
 *
 * Licence information
 */

// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined
// $triggermodname may be defined

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

// Action pour transmettre la FEP au RA pour commentaire
if ($action == 'confirm_transmettreRA' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->transmettreRA($user);
	if ($result >= 0) {
		setEventMessages('La FEP (/QS) a été transmise au RA pour commentaires', null, 'mesgs');
	} else {
		setEventMessages('Impossible de transmettre la FEP (/QS) au RA', null, 'errors');
	}
}

// Action pour valider les commentaires en envoyer une notif au groupe qualité
if ($action == 'confirm_commentaireFait' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->commentaireFait($user);
	if ($result >= 0) {
		setEventMessages('Les commentaires ont été validé', null, 'mesgs');
	} else {
		setEventMessages('Impossible de valider les commentaires', null, 'errors');
	}
}

// Action confirmer la publication de la FEP
if ($action == 'confirm_publier' && $confirm == 'yes' && $permissiontoadd) {
	if(empty($object->date_publication)){
		$result = $object->publier($user);
	}
	else{
		$result = $object->publier($user, 0, 1);
	}
	if ($result >= 0) {
		setEventMessages('La FEP (/QS) a été publié', null, 'mesgs');
	} else {
		setEventMessages('Impossible de publier la FEP (/QS)', null, 'errors');
	}
}

// Action to update record
if ($action == 'update' && !empty($permissiontoadd)) {
	$note_update = '';
	$note_globale = '';
	$envoi_mail = 0;
	$envoi_mail_note_globale = 0;
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

		if(preg_match('/note_theme/i', $key)){
			if ($value != '' && $object->$key != $value && $value <= 1 && $object->$key > 1){
				$note_update .= $langs->transnoentitiesnoconv($val['label']).' : '.$val['arrayofkeyval'][$value].'<br>';
				$envoi_mail = 1;
			}
		}

		if ($key == 'note_globale' && $value != '' && $object->$key != $value && $value <= 1 && $object->$key > 1){
			$note_globale .= $val['arrayofkeyval'][$value];
			$envoi_mail_note_globale = 1;
		}

		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}
	}

	global $dolibarr_main_url_root;
	$subject = '[OPTIM Industries] Notification automatique FEP';
	$from = 'erp@optim-industries.fr';
	if($envoi_mail){
		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Responsable Affaires');
		$liste_RA = $user_group->listUsersForGroup('u.statut=1');
		$to = '';
		foreach($liste_RA as $RA){
			if(!empty($RA->email)){
				$to .= $RA->email;
				$to .= ", ";
			}
		}
		$user_group->fetch('', 'Q3SE');
		$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
		foreach($liste_qualite as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
			}
		}
		$to = rtrim($to, ", ");

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$object->id.'">'.$object->ref.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextFEPModifyNoteThemeC", $link, $note_update);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $res && $mail->sendfile();
		}
	}
	if($envoi_mail_note_globale){
		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Responsable Affaires');
		$liste_RA = $user_group->listUsersForGroup('u.statut=1');
		$to = '';
		foreach($liste_RA as $RA){
			if(!empty($RA->email)){
				$to .= $RA->email;
				$to .= ", ";
			}
		}
		$user_group->fetch('', 'Q3SE');
		$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
		foreach($liste_qualite as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
			}
		}
		$user_group->fetch('', "Responsable d'antenne");
		$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
		foreach($liste_qualite as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
			}
		}
		$user_group->fetch('', 'Direction');
		$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
		foreach($liste_qualite as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
			}
		}
		$to = rtrim($to, ", ");

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$object->id.'">'.$object->ref.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextFEPModifyNoteGlobale", $link, $note_globale);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $res && $mail->sendfile();
		}
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
		$action = 'edit';
	}
}