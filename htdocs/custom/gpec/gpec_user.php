<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
 *
 * Licence information
 */

 /**
 *	\file		htdocs/custom/fod/add_data_intervenant.php
 *	\ingroup	fod
 *	\brief		Page to add fod data for intervenant
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/competencedomaine_level_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/competencetransverse_level_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/gpec.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/matricecompetence.class.php';

// Redirige l'utilisateur vers sa propre fiche
$url = $_SERVER["REQUEST_URI"];
if(strpos($url, '_ID_')){
	$urltogo = str_replace('_ID_', $user->id, $url);
	header("Location: ".$urltogo);
	exit;
}

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'dataintervenantlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page

$userid = GETPOST('id', 'int');
if(!($userid > 0)){
	$userid = GETPOST('userid', 'int');
}
$onglet = (GETPOST('onglet', 'int') ? GETPOST('onglet', 'int') : 1);

// Déclaration des objets
$form = new Form($db);
$formother = new FormOther($db);
$object = new User($db);
$competenceDomaine_level_user = new CompetenceDomaine_Level_User($db);
$competenceTransverse_level_user = new CompetenceTransverse_Level_User($db);
$gpec = new Gpec($db);
$matrice_competence = new MatriceCompetence($db);
$extrafields = new ExtraFields($db);

$res = $object->fetch($userid, '', '', 1);
if ($res < 0) {
	dol_print_error($db, $user->error);
	exit;
}
	
// Security check
if (empty($conf->gpec->enabled)) {
	accessforbidden('Module non activé');
}

// Chargement de l'objet GPEC correspondant à l'onglet
$morewhere = " AND fk_user = ".$userid." AND onglet = ".$onglet;
$gpec->fetchByUserAndOnglet($morewhere);
if(empty($gpec->id)){
	$gpec->status = 0;
}

// Chargement de l'objet MatriceCompetence correspondant à l'utilisateur
$morewhere = " AND fk_user = ".$userid;
$matrice_competence->fetchByUser($morewhere);

// Recherche du responsable de l'utilisateur
$user_resp = new User($db);
if(!empty($object->fk_user)){
	$user_resp->fetch($object->fk_user);
}
if(!empty($user_resp->fk_user) && $user_resp->fk_user != 16){
	$user_resp->fetch($user_resp->fk_user);
}

$user_hierarchie = false;
$allChildsId = $user->getAllChildIds();
if(in_array($object->id, $allChildsId)){
	$user_hierarchie = true;
}

$permissiontoread = $userid == $user->id || $user->rights->gpec->gpec->read_all || ($user->rights->gpec->gpec->read_hierarchie && $user_hierarchie) || $user_resp->id == $user->id;	
$permissiontoupdate = $userid == $user->id || $user->admin || $user->rights->gpec->gpec->update || ($user->rights->gpec->gpec->update && $user_hierarchie) || $user_resp->id == $user->id;
$permissiontoupdate_resp = $user->admin || $user->rights->gpec->gpec->update || ($user->rights->gpec->gpec->update && $user_hierarchie) || $user_resp->id == $user->id;
$permissiontovalidate = $user_resp->id == $user->id;

// Security check
if (empty($permissiontoread)) {
	accessforbidden('Vous n\'êtes pas autorisé à accéder à cette page');
}

// Security check
if (empty($conf->gpec->enabled)) {
	accessforbidden('Module non activé');
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = '';
}

if($onglet == 1 && $action == "update" && $permissiontoupdate){
	$niveaux = GETPOST("niveau", "array");
	$result = 1;
	$historique = "";
	$list_competences = $competenceDomaine_level_user->getAllCompetencesElementaires();

	$i = 0;
	foreach($niveaux as $id_competence => $niveau){
		$id_levelUser = $competenceDomaine_level_user->getIdWithUserAndCompetence($userid, $id_competence);
		if($id_levelUser > 0) {
			$competenceDomaine_level_user->fetch($id_levelUser);

			if($competenceDomaine_level_user->niveau != $niveau) {
				if(empty($historique)){
					$historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>";
				}
				$historique .= "<li>".$list_competences[$i]." : ".$competenceDomaine_level_user->fields['niveau']["arrayofkeyval"][$competenceDomaine_level_user->niveau]." -> ".$competenceDomaine_level_user->fields['niveau']["arrayofkeyval"][$niveau]."</li>";
				$competenceDomaine_level_user->niveau = $niveau;
				$result = $result && $competenceDomaine_level_user->update($user);
			}
		}
		else {
			if(empty($historique)){
				$historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>";
			}
			$historique .= "<li>".$list_competences[$i]." : ".$competenceDomaine_level_user->fields['niveau']["arrayofkeyval"][$competenceDomaine_level_user->niveau]." -> ".$competenceDomaine_level_user->fields['niveau']["arrayofkeyval"][$niveau]."</li>";
		
			$competenceDomaine_level_user->fk_user = $userid;
			$competenceDomaine_level_user->fk_competence = $id_competence;
			$competenceDomaine_level_user->niveau = $niveau;

			$result = $result && $competenceDomaine_level_user->create($user);
		}
		$i++;
	}

	if(!empty($historique)){
		$historique .= '</ul>';
	}

	if($gpec->id > 0 && !empty($historique)) {
		$gpec->status = 1;
		$gpec->historique .= $historique;
		$result = $result && $gpec->update($user);

		global $dolibarr_main_url_root;
		$subject = '[OPTIM Industries] Notification automatique GPEC';
		$from = 'erp@optim-industries.fr';
		$to = '';

		if(!empty($user_resp->email) && $user_resp->id != 16){
			$to .= $user_resp->email;
		}

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/gpec/gpec_user.php?id='.$object->id.'&onglet=1">'.$object->firstname." ".$object->lastname.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextModifGPEC", '"Compétences par Domaines"', $link);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $mail->sendfile();
		}
	}
	elseif (!empty($historique)) {
		$gpec->fk_user = $userid;
		$gpec->onglet = 1;
		$gpec->historique .= $historique;
		$gpec->status = 1;
		$result = $result && $gpec->create($user);

		global $dolibarr_main_url_root;
		$subject = '[OPTIM Industries] Notification automatique GPEC';
		$from = 'erp@optim-industries.fr';
		$to = '';

		if(!empty($user_resp->email) && $user_resp->id != 16){
			$to .= $user_resp->email;
		}

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/gpec/gpec_user.php?id='.$object->id.'&onglet=1">'.$object->firstname." ".$object->lastname.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextRenseignementGPEC", '"Compétences par Domaines"', $link);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $mail->sendfile();
		}
	}
	
	if ($result >= 0) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	} 
	else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
	}
}

if($onglet == 2 && $action == "update" && $permissiontoupdate){
	$niveaux = GETPOST("niveau", "array");
	$result = 1;
	$historique = "";
	$list_competences = $competenceTransverse_level_user->getAllCompetencesTransverses();

	$i = 0;
	foreach($niveaux as $id_competence => $niveau){
		$id_levelUser = $competenceTransverse_level_user->getIdWithUserAndCompetence($userid, $id_competence);
		if($id_levelUser > 0) {
			$competenceTransverse_level_user->fetch($id_levelUser);

			if($competenceTransverse_level_user->niveau != $niveau) {
				if(empty($historique)){
					$historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>";
				}
				$historique .= "<li>".$list_competences[$i]." : ".$competenceTransverse_level_user->fields['niveau']["arrayofkeyval"][$competenceTransverse_level_user->niveau]." -> ".$competenceTransverse_level_user->fields['niveau']["arrayofkeyval"][$niveau]."</li>";
				$competenceTransverse_level_user->niveau = $niveau;
				$result = $result && $competenceTransverse_level_user->update($user);
			}
		}
		else {
			if(empty($historique)){
				$historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>";
			}
			$historique .= "<li>".$list_competences[$i]." : ".$competenceTransverse_level_user->fields['niveau']["arrayofkeyval"][$competenceTransverse_level_user->niveau]." -> ".$competenceTransverse_level_user->fields['niveau']["arrayofkeyval"][$niveau]."</li>";

			$competenceTransverse_level_user->fk_user = $userid;
			$competenceTransverse_level_user->fk_competence = $id_competence;
			$competenceTransverse_level_user->niveau = $niveau;

			$result = $result && $competenceTransverse_level_user->create($user);
		}
		$i++;
	}

	if(!empty($historique)){
		$historique .= '</ul>';
	}

	if($gpec->id > 0 && !empty($historique)) {
		$gpec->status = 1;
		$gpec->historique .= $historique;
		$result = $result && $gpec->update($user);

		global $dolibarr_main_url_root;
		$subject = '[OPTIM Industries] Notification automatique GPEC';
		$from = 'erp@optim-industries.fr';
		$to = '';

		if(!empty($user_resp->email) && $user_resp->id != 16){
			$to .= $user_resp->email;
		}

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/gpec/gpec_user.php?id='.$object->id.'&onglet=2">'.$object->firstname." ".$object->lastname.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextModifGPEC", '"Compétences Transverses"', $link);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $mail->sendfile();
		}
	}
	elseif (!empty($historique)) {
		$gpec->fk_user = $userid;
		$gpec->onglet = 2;
		$gpec->historique .= $historique;
		$gpec->status = 1;
		$result = $result && $gpec->create($user);

		global $dolibarr_main_url_root;
		$subject = '[OPTIM Industries] Notification automatique GPEC';
		$from = 'erp@optim-industries.fr';
		$to = '';

		if(!empty($user_resp->email) && $user_resp->id != 16){
			$to .= $user_resp->email;
		}

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/gpec/gpec_user.php?id='.$object->id.'&onglet=2">'.$object->firstname." ".$object->lastname.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextRenseignementGPEC", '"Compétences Transverses"', $link);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $mail->sendfile();
		}
	}
	
	if ($result >= 0) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	} 
	else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
	}
}

if($onglet == 3 && $action == "update" && $permissiontoupdate){
	$result = 1;
	$historique = "";

	if($matrice_competence->diplome != GETPOST('diplome', 'alpha')) {
		$historique .= "<li>Diplôme : ".(!empty($matrice_competence->diplome) ? $matrice_competence->diplome : "/")." -> ".(!empty(GETPOST('diplome', 'alpha')) ? GETPOST('diplome', 'alpha') : "/")."</li>";
		$matrice_competence->diplome = GETPOST('diplome', 'alpha');
	}

	if($matrice_competence->anciennete_metier != GETPOST('anciennete_metier', 'int') || $matrice_competence->classification_pro != GETPOST('classification_pro', 'int')){
		$new_anciennete_metier = date("Y")- GETPOST('anciennete_metier', 'int') + 1;
		$old_anciennete_metier = date("Y")- $matrice_competence->anciennete_metier + 1;

		if($matrice_competence->anciennete_metier != GETPOST('anciennete_metier', 'alpha')) {
			$historique .= "<li>Ancienneté dans le métier : ".(!empty($matrice_competence->anciennete_metier) ? $matrice_competence->anciennete_metier : "/")." -> ".(!empty(GETPOST('anciennete_metier', 'alpha')) ? GETPOST('anciennete_metier', 'alpha') : "/")."</li>";
			$matrice_competence->anciennete_metier = GETPOST('anciennete_metier', 'int');
		}
		if($matrice_competence->classification_pro != GETPOST('classification_pro', 'alpha')) {
			$value = $matrice_competence->fields['classification_pro']["arrayofkeyval"][$matrice_competence->classification_pro];
			$new_value = $matrice_competence->fields['classification_pro']["arrayofkeyval"][GETPOST('classification_pro', 'alpha')];
			$historique .= "<li>Classification professionnelle : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
			$matrice_competence->classification_pro = GETPOST('classification_pro', 'int');
		}

		$is_td = 0;
		$is_t = 0;
		$is_te = 0;
		$is_tc = 0;
		$is_id = 0;
		$is_i = 0;
		$is_ic = 0;
		$is_ie = 0;
		if(!empty($new_anciennete_metier ) && !empty($matrice_competence->classification_pro)){
			if(($matrice_competence->classification_pro == 3 || $matrice_competence->classification_pro == 4) && $new_anciennete_metier  <= 2){
				$is_td = 1;
			}
			elseif((($matrice_competence->classification_pro == 5 || $matrice_competence->classification_pro == 6) && $new_anciennete_metier  <= 2) ||
			(($matrice_competence->classification_pro == 3 || $matrice_competence->classification_pro == 4) && $new_anciennete_metier  > 2 && $new_anciennete_metier <= 5)){
				$is_t = 1;
			}
			elseif((($matrice_competence->classification_pro == 5 || $matrice_competence->classification_pro == 6) && $new_anciennete_metier > 2 && $new_anciennete_metier <= 5) ||
			(($matrice_competence->classification_pro == 3 || $matrice_competence->classification_pro == 4) && $new_anciennete_metier > 5 && $new_anciennete_metier <= 8)){
				$is_te = 1;
			}
			elseif($new_anciennete_metier > 8 || (($matrice_competence->classification_pro == 5 || $matrice_competence->classification_pro == 6) && $new_anciennete_metier > 5)) {
				$is_tc = 1;
			}

			if(($matrice_competence->classification_pro == 7 || $matrice_competence->classification_pro == 8) && $new_anciennete_metier <= 2){
				$is_id = 1;
			}
			elseif(($matrice_competence->classification_pro == 7 || $matrice_competence->classification_pro == 8) && $new_anciennete_metier > 2 && $new_anciennete_metier <= 6){
				$is_i = 1;
			}
			elseif(($matrice_competence->classification_pro == 7 || $matrice_competence->classification_pro == 8) && $new_anciennete_metier > 6 && $new_anciennete_metier <= 10){
				$is_ic = 1;
			}
			elseif(($matrice_competence->classification_pro == 7 || $matrice_competence->classification_pro == 8) && $new_anciennete_metier > 10) {
				$is_ie = 1;
			}
		}

		$matrice_competence->is_td = $is_td;
		$matrice_competence->is_t = $is_t;
		$matrice_competence->is_te = $is_te;
		$matrice_competence->is_tc = $is_tc;

		$matrice_competence->is_id = $is_id;
		$matrice_competence->is_i = $is_i;
		$matrice_competence->is_ic = $is_ic;
		$matrice_competence->is_ie = $is_ie;
	}

	if($matrice_competence->chef_projet != GETPOST('chef_projet', 'alpha')) {
		$value = $matrice_competence->fields['chef_projet']["arrayofkeyval"][$matrice_competence->chef_projet];
		$new_value = $matrice_competence->fields['chef_projet']["arrayofkeyval"][GETPOST('chef_projet', 'alpha')];
		$historique .= "<li>Chef de projet : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->chef_projet = GETPOST('chef_projet', 'int');
	}
	if($matrice_competence->pilote_affaire != GETPOST('pilote_affaire', 'alpha')) {
		$value = $matrice_competence->fields['pilote_affaire']["arrayofkeyval"][$matrice_competence->pilote_affaire];
		$new_value = $matrice_competence->fields['pilote_affaire']["arrayofkeyval"][GETPOST('pilote_affaire', 'alpha')];
		$historique .= "<li>Pilote d'affaire : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->pilote_affaire = GETPOST('pilote_affaire', 'int');
	}
	if($matrice_competence->ingenieur_confirme != GETPOST('ingenieur_confirme', 'alpha')) {
		$value = $matrice_competence->fields['ingenieur_confirme']["arrayofkeyval"][$matrice_competence->ingenieur_confirme];
		$new_value = $matrice_competence->fields['ingenieur_confirme']["arrayofkeyval"][GETPOST('ingenieur_confirme', 'alpha')];
		$historique .= "<li>Ingénieur Confirmé : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->ingenieur_confirme = GETPOST('ingenieur_confirme', 'int');
	}
	if($matrice_competence->preparateur_charge_affaire != GETPOST('preparateur_charge_affaire', 'alpha')) {
		$value = $matrice_competence->fields['preparateur_charge_affaire']["arrayofkeyval"][$matrice_competence->preparateur_charge_affaire];
		$new_value = $matrice_competence->fields['preparateur_charge_affaire']["arrayofkeyval"][GETPOST('preparateur_charge_affaire', 'alpha')];
		$historique .= "<li>Préparateur chargé d'affaire ancrage/supportage : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->preparateur_charge_affaire = GETPOST('preparateur_charge_affaire', 'int');
	}
	if($matrice_competence->preparateur_methodes != GETPOST('preparateur_methodes', 'alpha')) {
		$value = $matrice_competence->fields['preparateur_methodes']["arrayofkeyval"][$matrice_competence->preparateur_methodes];
		$new_value = $matrice_competence->fields['preparateur_methodes']["arrayofkeyval"][GETPOST('preparateur_methodes', 'alpha')];
		$historique .= "<li>Préparateur méthodes : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->preparateur_methodes = GETPOST('preparateur_methodes', 'int');
	}
	if($matrice_competence->charge_affaires_elec_auto != GETPOST('charge_affaires_elec_auto', 'alpha')) {
		$value = $matrice_competence->fields['charge_affaires_elec_auto']["arrayofkeyval"][$matrice_competence->charge_affaires_elec_auto];
		$new_value = $matrice_competence->fields['charge_affaires_elec_auto']["arrayofkeyval"][GETPOST('charge_affaires_elec_auto', 'alpha')];
		$historique .= "<li>Chargé d'affaires PIAT électricité/automatisme : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->charge_affaires_elec_auto = GETPOST('charge_affaires_elec_auto', 'int');
	}
	if($matrice_competence->electricien != GETPOST('electricien', 'alpha')) {
		$value = $matrice_competence->fields['electricien']["arrayofkeyval"][$matrice_competence->electricien];
		$new_value = $matrice_competence->fields['electricien']["arrayofkeyval"][GETPOST('electricien', 'alpha')];
		$historique .= "<li>Electricien : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->electricien = GETPOST('electricien', 'int');
	}
	if($matrice_competence->charge_affaires_mecanique != GETPOST('charge_affaires_mecanique', 'alpha')) {
		$value = $matrice_competence->fields['charge_affaires_mecanique']["arrayofkeyval"][$matrice_competence->charge_affaires_mecanique];
		$new_value = $matrice_competence->fields['charge_affaires_mecanique']["arrayofkeyval"][GETPOST('charge_affaires_mecanique', 'alpha')];
		$historique .= "<li>Chargé d'affaires PIAT mécanique : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->charge_affaires_mecanique = GETPOST('charge_affaires_mecanique', 'int');
	}
	if($matrice_competence->mecanicien != GETPOST('mecanicien', 'alpha')) {
		$value = $matrice_competence->fields['mecanicien']["arrayofkeyval"][$matrice_competence->mecanicien];
		$new_value = $matrice_competence->fields['mecanicien']["arrayofkeyval"][GETPOST('mecanicien', 'alpha')];
		$historique .= "<li>Mécanicien : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->mecanicien = GETPOST('mecanicien', 'int');
	}
	if($matrice_competence->robinettier != GETPOST('robinettier', 'alpha')) {
		$value = $matrice_competence->fields['robinettier']["arrayofkeyval"][$matrice_competence->robinettier];
		$new_value = $matrice_competence->fields['robinettier']["arrayofkeyval"][GETPOST('robinettier', 'alpha')];
		$historique .= "<li>Robinettier : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->robinettier = GETPOST('robinettier', 'int');
	}
	if($matrice_competence->pcr_operationnel != GETPOST('pcr_operationnel', 'alpha')) {
		$value = $matrice_competence->fields['pcr_operationnel']["arrayofkeyval"][$matrice_competence->pcr_operationnel];
		$new_value = $matrice_competence->fields['pcr_operationnel']["arrayofkeyval"][GETPOST('pcr_operationnel', 'alpha')];
		$historique .= "<li>PCR opérationnel : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->pcr_operationnel = GETPOST('pcr_operationnel', 'int');
	}
	if($matrice_competence->technicien_rp != GETPOST('technicien_rp', 'alpha')) {
		$value = $matrice_competence->fields['technicien_rp']["arrayofkeyval"][$matrice_competence->technicien_rp];
		$new_value = $matrice_competence->fields['technicien_rp']["arrayofkeyval"][GETPOST('technicien_rp', 'alpha')];
		$historique .= "<li>Technicien RO : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->technicien_rp = GETPOST('technicien_rp', 'int');
	}
	if($matrice_competence->charge_affaires_multi_specialites != GETPOST('charge_affaires_multi_specialites', 'alpha')) {
		$value = $matrice_competence->fields['charge_affaires_multi_specialites']["arrayofkeyval"][$matrice_competence->charge_affaires_multi_specialites];
		$new_value = $matrice_competence->fields['charge_affaires_multi_specialites']["arrayofkeyval"][GETPOST('charge_affaires_multi_specialites', 'alpha')];
		$historique .= "<li>Chargé d'affaires multi-spécialités : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->charge_affaires_multi_specialites = GETPOST('charge_affaires_multi_specialites', 'int');
	}


	if($matrice_competence->mec_machine_tournante != GETPOST('mec_machine_tournante', 'alpha')) {
		$value = $matrice_competence->fields['mec_machine_tournante']["arrayofkeyval"][$matrice_competence->mec_machine_tournante];
		$new_value = $matrice_competence->fields['mec_machine_tournante']["arrayofkeyval"][GETPOST('mec_machine_tournante', 'alpha')];
		$historique .= "<li>Mécanique Machine Tournante : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->mec_machine_tournante = GETPOST('mec_machine_tournante', 'int');
	}
	if($matrice_competence->robinetterie != GETPOST('robinetterie', 'alpha')) {
		$value = $matrice_competence->fields['robinetterie']["arrayofkeyval"][$matrice_competence->robinetterie];
		$new_value = $matrice_competence->fields['robinetterie']["arrayofkeyval"][GETPOST('robinetterie', 'alpha')];
		$historique .= "<li>Robinetterie : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->robinetterie = GETPOST('robinetterie', 'int');
	}
	if($matrice_competence->chaudronnerie != GETPOST('chaudronnerie', 'alpha')) {
		$value = $matrice_competence->fields['chaudronnerie']["arrayofkeyval"][$matrice_competence->chaudronnerie];
		$new_value = $matrice_competence->fields['chaudronnerie']["arrayofkeyval"][GETPOST('chaudronnerie', 'alpha')];
		$historique .= "<li>Chaudonnerie : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->chaudronnerie = GETPOST('chaudronnerie', 'int');
	}
	if($matrice_competence->tuyauterie_soudage != GETPOST('tuyauterie_soudage', 'alpha')) {
		$value = $matrice_competence->fields['tuyauterie_soudage']["arrayofkeyval"][$matrice_competence->tuyauterie_soudage];
		$new_value = $matrice_competence->fields['tuyauterie_soudage']["arrayofkeyval"][GETPOST('tuyauterie_soudage', 'alpha')];
		$historique .= "<li>Tuyauterie/Soudage : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->tuyauterie_soudage = GETPOST('tuyauterie_soudage', 'int');
	}
	if($matrice_competence->automatisme != GETPOST('automatisme', 'alpha')) {
		$value = $matrice_competence->fields['automatisme']["arrayofkeyval"][$matrice_competence->automatisme];
		$new_value = $matrice_competence->fields['automatisme']["arrayofkeyval"][GETPOST('automatisme', 'alpha')];
		$historique .= "<li>Automatisme : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->automatisme = GETPOST('automatisme', 'int');
	}
	if($matrice_competence->electricite != GETPOST('electricite', 'alpha')) {
		$value = $matrice_competence->fields['electricite']["arrayofkeyval"][$matrice_competence->electricite];
		$new_value = $matrice_competence->fields['electricite']["arrayofkeyval"][GETPOST('electricite', 'alpha')];
		$historique .= "<li>Electricité : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->electricite = GETPOST('electricite', 'int');
	}
	if($matrice_competence->ventilation != GETPOST('ventilation', 'alpha')) {
		$value = $matrice_competence->fields['ventilation']["arrayofkeyval"][$matrice_competence->ventilation];
		$new_value = $matrice_competence->fields['ventilation']["arrayofkeyval"][GETPOST('ventilation', 'alpha')];
		$historique .= "<li>Ventilation : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->ventilation = GETPOST('ventilation', 'int');
	}
	if($matrice_competence->logistique != GETPOST('logistique', 'alpha')) {
		$value = $matrice_competence->fields['logistique']["arrayofkeyval"][$matrice_competence->logistique];
		$new_value = $matrice_competence->fields['logistique']["arrayofkeyval"][GETPOST('logistique', 'alpha')];
		$historique .= "<li>Logistique : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->logistique = GETPOST('logistique', 'int');
	}
	if($matrice_competence->securite != GETPOST('securite', 'alpha')) {
		$value = $matrice_competence->fields['securite']["arrayofkeyval"][$matrice_competence->securite];
		$new_value = $matrice_competence->fields['securite']["arrayofkeyval"][GETPOST('securite', 'alpha')];
		$historique .= "<li>Sécurité : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->securite = GETPOST('securite', 'int');
	}
	if($matrice_competence->soudage != GETPOST('soudage', 'alpha')) {
		$value = $matrice_competence->fields['soudage']["arrayofkeyval"][$matrice_competence->soudage];
		$new_value = $matrice_competence->fields['soudage']["arrayofkeyval"][GETPOST('soudage', 'alpha')];
		$historique .= "<li>Soudage : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->soudage = GETPOST('soudage', 'int');
	}

	if($matrice_competence->id > 0) {
		$matrice_competence->update($user);
	}
	else {
		$matrice_competence->fk_user = $userid;
		$matrice_competence->create($user);
	}


	if($gpec->id > 0 && !empty($historique)) {
		$historique = '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>".$historique.'</ul>';
		$gpec->status = 1;
		$gpec->historique .= $historique;
		$result = $result && $gpec->update($user);

		global $dolibarr_main_url_root;
		$subject = '[OPTIM Industries] Notification automatique GPEC';
		$from = 'erp@optim-industries.fr';
		$to = '';

		if(!empty($user_resp->email) && $user_resp->id != 16){
			$to .= $user_resp->email;
		}

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/gpec/gpec_user.php?id='.$object->id.'&onglet=3">'.$object->firstname." ".$object->lastname.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextModifGPEC", '"Matrice Compétence"', $link);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $mail->sendfile();
		}
	}
	elseif (!empty($historique)) {
		$historique = '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>".$historique.'</ul>';
		$gpec->fk_user = $userid;
		$gpec->onglet = 3;
		$gpec->historique .= $historique;
		$gpec->status = 1;
		$result = $result && $gpec->create($user);

		global $dolibarr_main_url_root;
		$subject = '[OPTIM Industries] Notification automatique GPEC';
		$from = 'erp@optim-industries.fr';
		$to = '';

		if(!empty($user_resp->email) && $user_resp->id != 16){
			$to .= $user_resp->email;
		}

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$link = '<a href="'.$urlwithroot.'/custom/gpec/gpec_user.php?id='.$object->id.'&onglet=3">'.$object->firstname." ".$object->lastname.'</a>';
		$msg = $langs->transnoentitiesnoconv("EMailTextRenseignementGPEC", '"Matrice Compétence"', $link);
		$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
		if(!empty($to)){
			$res = $mail->sendfile();
		}
	}
	
	if ($result >= 0) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	} 
	else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
	}
}
elseif ($onglet == 3 && $action == "update_classificationPro" && $permissiontoupdate_resp){
	$result = 1;
	$historique = "";

	if(GETPOST('is_td', 'alpha') != $matrice_competence->is_td) {
		$value = $matrice_competence->fields['is_td']["arrayofkeyval"][$matrice_competence->is_td];
		$new_value = $matrice_competence->fields['is_td']["arrayofkeyval"][GETPOST('is_td', 'alpha')];
		$historique .= "<li>TD : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_td = GETPOST('is_td', 'alpha');
	}
	if(GETPOST('is_t', 'alpha') != $matrice_competence->is_t) {
		$value = $matrice_competence->fields['is_t']["arrayofkeyval"][$matrice_competence->is_t];
		$new_value = $matrice_competence->fields['is_t']["arrayofkeyval"][GETPOST('is_t', 'alpha')];
		$historique .= "<li>T : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_t = GETPOST('is_t', 'alpha');
	}
	if(GETPOST('is_te', 'alpha') != $matrice_competence->is_te) {
		$value = $matrice_competence->fields['is_te']["arrayofkeyval"][$matrice_competence->is_te];
		$new_value = $matrice_competence->fields['is_te']["arrayofkeyval"][GETPOST('is_te', 'alpha')];
		$historique .= "<li>TE : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_te = GETPOST('is_te', 'alpha');
	}
	if(GETPOST('is_tc', 'alpha') != $matrice_competence->is_tc) {
		$value = $matrice_competence->fields['is_tc']["arrayofkeyval"][$matrice_competence->is_tc];
		$new_value = $matrice_competence->fields['is_tc']["arrayofkeyval"][GETPOST('is_tc', 'alpha')];
		$historique .= "<li>TC : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_tc = GETPOST('is_tc', 'alpha');
	}
	if(GETPOST('is_id', 'alpha') != $matrice_competence->is_id) {
		$value = $matrice_competence->fields['is_id']["arrayofkeyval"][$matrice_competence->is_id];
		$new_value = $matrice_competence->fields['is_id']["arrayofkeyval"][GETPOST('is_id', 'alpha')];
		$historique .= "<li>ID : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_id = GETPOST('is_id', 'alpha');
	}
	if(GETPOST('is_i', 'alpha') != $matrice_competence->is_i) {
		$value = $matrice_competence->fields['is_i']["arrayofkeyval"][$matrice_competence->is_i];
		$new_value = $matrice_competence->fields['is_i']["arrayofkeyval"][GETPOST('is_i', 'alpha')];
		$historique .= "<li>TD : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_i = GETPOST('is_i', 'alpha');
	}
	if(GETPOST('is_ic', 'alpha') != $matrice_competence->is_ic) {
		$value = $matrice_competence->fields['is_ic']["arrayofkeyval"][$matrice_competence->is_ic];
		$new_value = $matrice_competence->fields['is_ic']["arrayofkeyval"][GETPOST('is_ic', 'alpha')];
		$historique .= "<li>TD : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_ic = GETPOST('is_ic', 'alpha');
	}
	if(GETPOST('is_ie', 'alpha') != $matrice_competence->is_ie) {
		$value = $matrice_competence->fields['is_ie']["arrayofkeyval"][$matrice_competence->is_ie];
		$new_value = $matrice_competence->fields['is_ie']["arrayofkeyval"][GETPOST('is_ie', 'alpha')];
		$historique .= "<li>TD : ".(!empty($value) ? $value : "/")." -> ".$new_value."</li>";
		$matrice_competence->is_ie = GETPOST('is_ie', 'alpha');
	}

	if($matrice_competence->id > 0) {
		$matrice_competence->update($user);
	}
	else {
		$matrice_competence->fk_user = $userid;
		$matrice_competence->create($user);
	}

	if($gpec->id > 0 && !empty($historique)) {
		$historique = '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>".$historique.'</ul>';
		$gpec->historique .= $historique;
		$result = $result && $gpec->update($user);
	}
	elseif (!empty($historique)) {
		$historique = '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Renseignement par ".$user->firstname." ".$user->lastname." : </span><br><ul>".$historique.'</ul>';
		$gpec->fk_user = $userid;
		$gpec->onglet = 3;
		$gpec->historique .= $historique;
		$result = $result && $gpec->create($user);
	}

	if ($result >= 0) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	} 
	else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
	}
}

if($action == "validate" && $gpec->id > 0 && $gpec->status == 1 && $permissiontovalidate){
	$gpec->status = 2;
	$gpec->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Validation par le responsable</span><br>";
	$result = $gpec->update($user);

	if ($result >= 0) {
		setEventMessages($langs->trans("Validate"), null, 'mesgs');
	} 
	else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
	}
}

/*
 * View
 */

llxHeader("", $langs->trans("GPEC"));
$formother = new FormOther($db);

if($userid > 0){
	$res = $object->fetch_optionals();
	// Check if user has rights
	if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$object->getrights();
		if (empty($object->nb_rights) && $object->statut != 0 && empty($object->admin)) {
			setEventMessages($langs->trans('UserHasNoPermissions'), null, 'warnings');
		}
	}

	$head = user_prepare_head($object);
	print dol_get_fiche_head($head, 'GPEC', $langs->trans("User"), -1, 'user');
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	
	if($user->rights->user->user->lire || $user->admin){
		dol_banner_tab($object, 'userid', $linkback, 1);
	}
	else {
		dol_banner_tab($object, 'userid', '', 0);
	}

	
	print '</div>';
	print '<div class="tabs" data-role="controlgroup" data-type="horizontal">';
	print '<a class="tabTitle">';
	print '<span class="fas fa-gpec imgTabTitle em120 infobox-gpec" style="" title="'.$langs->trans("GPEC").'"></span>' ;
	print '<span class="tabTitleText">Test</span>';
	print '</a>';
		
	$link = $_SERVER['PHP_SELF'].'?id='.$userid.'&onglet=';
			
	// Onglet Compétences par Domaine
	if($onglet == 1){
		print '<div class="inline-block tabsElem tabsElemActive">';
		print '<div class="tab tabactive" style="margin: 0 !important">';
	}
	else {
		print '<div class="inline-block tabsElem">';
		print '<div class="tab tabunactive" style="margin: 0 !important">';
	}
	print '<a id="onglet" class="tab inline-block" href="'.$link.'1">'."Compétences par Domaine";
	print '</a>';
	print '</div>';
	print '</div>';


	// Onglet Compétences Transverses
	if($onglet == 2){
		print '<div class="inline-block tabsElem tabsElemActive">';
		print '<div class="tab tabactive" style="margin: 0 !important">';
	}
	else {
		print '<div class="inline-block tabsElem">';
		print '<div class="tab tabunactive" style="margin: 0 !important">';
	}
	print '<a id="onglet" class="tab inline-block" href="'.$link.'2">'."Compétences Transverses";
	print '</a>';
	print '</div>';
	print '</div>';


	// Onglet Matrice Compétence 
	if($onglet == 3){
		print '<div class="inline-block tabsElem tabsElemActive">';
		print '<div class="tab tabactive" style="margin: 0 !important">';
	}
	else {
		print '<div class="inline-block tabsElem">';
		print '<div class="tab tabunactive" style="margin: 0 !important">';
	}
	print '<a id="onglet" class="tab inline-block" href="'.$link.'3">'."Matrice Compétence";
	print '</a>';
	print '</div>';
	print '</div>';


	// Onglet Historique
	if($onglet == 4){
		print '<div class="inline-block tabsElem tabsElemActive">';
		print '<div class="tab tabactive" style="margin: 0 !important">';
	}
	else {
		print '<div class="inline-block tabsElem">';
		print '<div class="tab tabunactive" style="margin: 0 !important">';
	}
	print '<a id="onglet" class="tab inline-block" href="'.$link.'4">'."Historique";
	print '</a>';
	print '</div>';
	print '</div>';

	print '</div>';

	if($onglet == 1){
		print '<div class="tabBar">';

		if($action == "edit") {
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="onglet" value="1">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
		}

		print '<div class="statusref">';
		print $gpec->getLibStatut(5);
		print '</div><br><br><br>';

		print '<table id="competences_elementaires" style="width: 90%; margin: auto; border-collapse: collapse;">';

		print '<thead><tr>';
		print '<td id="titre"></td>';
		print '<td id="titre">Domaines</td>';
		print '<td id="titre">Activités</td>';
		print '<td id="titre">Exigences CCTP</td>';
		print '<td id="titre">Compétences Elémentaires</td>';
		print '<td id="titre">';
		$help = "Niveau 0 : <ul><li>Connaissances de base</li><li>A déjà pratiqué accompagné</li><li>Pas ou peu d’autonomie</li></ul><br>";
		$help .= "Niveau 1 : <ul><li>L’activité est connue ; les basiques sont maitrisés</li><li>Autonomie dans les situations les plus classiques</li></ul><br>";
		$help .= "Niveau 2 : <ul><li>L’activité est connue et maitrisée même dans des situations complexes</li><li>Au moins une expérience significative ; la compétence est possédée</li></ul><br>";
		$help .= "Niveau 3 : <ul><li>Expert sur l’activité</li><li>L’activité est connue et maitrisée même dans des situations complexes</li><li>Plusieurs expériences significatives</li><li>Possibilité d’assurer un rôle d’encadrement, de formation ou de tuteur</li></ul>";
		print '<span id="helplinkniveau" class="spanforparamtooltip">'.$form->textwithpicto("Niveau", $help, 1, 'help', '', 0, 3, 'tootips'.$constname).'</span>';
		print '</td>';
		print '</tr></thead><tbody>';

		$list_competences_elementaires = $competenceDomaine_level_user->getAllCompetencesElementairesWithLevel($userid);

		$nb_row_theme = array();
		$u = 0;
		for($i = 0; $i < count($list_competences_elementaires); $i++){
			if($i > 0 && $list_competences_elementaires[$i][6] != $list_competences_elementaires[$i - 1][6]) {
				$u++;
			}
			$nb_row_theme[$u] += count($list_competences_elementaires[$i][3]);
		}

		$u = 0;
		for($i = 0; $i < count($list_competences_elementaires); $i++){
			print '<tr>';
			if($i == 0 || $list_competences_elementaires[$i][6] != $list_competences_elementaires[$i - 1][6]) {
				print '<td id="theme" class="rotate" rowspan="'.$nb_row_theme[$u].'">'.$list_competences_elementaires[$i][6].'</td>';
				$u++;
			}
			print '<td id="domaine" style="width: 7.5%" rowspan="'.count($list_competences_elementaires[$i][3]).'">'.$list_competences_elementaires[$i][0].'</td>';
			print '<td  id="activite" style="width: 15%"  rowspan="'.count($list_competences_elementaires[$i][3]).'">'.$list_competences_elementaires[$i][1].'</td>';
			print '<td  id="exigence_ccpt" style="width: 32.5%"  rowspan="'.count($list_competences_elementaires[$i][3]).'">'.dol_htmlentitiesbr($list_competences_elementaires[$i][2]).'</td>';
			for($j = 0; $j < count($list_competences_elementaires[$i][3]); $j++){
				if($j != 0){
					print '<tr>';
				}
				print '<td id="competence_elementaire" style="width: 32.5%" >'.$list_competences_elementaires[$i][3][$j].'</td>';
				print '<td id="level" style="width: 7.5%">';

				$value = $list_competences_elementaires[$i][5][$j];
				if($action == "edit"){
					$key = 'niveau';
					$val = $competenceDomaine_level_user->fields[$key];
					$keysuffix = "[".$list_competences_elementaires[$i][4][$j]."]";
					print $competenceDomaine_level_user->showInputField($val, $key, $value, '', $keysuffix, '', 0);	
				}
				else {
					print $competenceDomaine_level_user->fields["niveau"]["arrayofkeyval"][$value];
				}

				print '</td>';
				if($j != 0){
					print '</tr>';
				}
			}
			print '</tr>'; 
		}


		print '</tbody></table>';


		if($action == "edit" && $permissiontoupdate){
			print '<br/><div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
			print '</div>';
			print "</form>";
		}
		else {
			print '<br/><div class="center">';
			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoupdate);
			if($gpec->id > 0 && $gpec->status == 1){
				print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validate&token='.newToken(), '', $permissiontovalidate);
			}
			print '</div>';
		}
		print '</div>';
	}
	elseif($onglet == 2){
		print '<div class="tabBar">';

		if($action == "edit") {
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="onglet" value="2">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
		}

		print '<div class="statusref">';
		print $gpec->getLibStatut(5);
		print '</div><br><br><br>';

		print '<table id="competences_transverses" style="width: 90%; margin: auto; border-collapse: collapse;">';

		print '<thead><tr>';
		print '<td id="titre"></td>';
		print '<td id="titre">Compétence Transverse</td>';
		print '<td id="titre">Niveau 0 (Apprenant)</td>';
		print '<td id="titre">Niveau 1 (Confirmé)</td>';
		print '<td id="titre">Niveau 2 (Maitrise)</td>';
		print '<td id="titre">Niveau 3 (Expert)</td>';
		print '<td id="titre">Niveau</td>';
		print '</tr></thead><tbody>';

		$list_competences_transverses = $competenceTransverse_level_user->getAllCompetencesTransversesWithLevel($userid);
		$nb_row_theme = array();
		$u = 0;
		for($i = 0; $i < count($list_competences_transverses); $i++){
			if($i > 0 && $list_competences_transverses[$i][7] != $list_competences_transverses[$i - 1][7]) {
				$u++;
			}
			$nb_row_theme[$u] += 1;
		}

		$u = 0;
		for($i = 0; $i < count($list_competences_transverses); $i++){
			print '<tr>';
			if($i == 0 || $list_competences_transverses[$i][7] != $list_competences_transverses[$i - 1][7]) {
				print '<td id="theme" class="rotate" rowspan="'.$nb_row_theme[$u].'">'.$list_competences_transverses[$i][7].'</td>';
				$u++;
			}
			print '<td id="competenceTransverse" style="width: 10%">'.$list_competences_transverses[$i][0].'</td>';
			print '<td  id="niveau0" style="width: 17.5%">'.$list_competences_transverses[$i][1].'</td>';
			print '<td  id="niveau1" style="width: 17.5%">'.$list_competences_transverses[$i][2].'</td>';
			print '<td  id="niveau2" style="width: 17.5%">'.$list_competences_transverses[$i][3].'</td>';
			print '<td  id="niveau3" style="width: 17.5%">'.$list_competences_transverses[$i][4].'</td>';

			print '<td id="level" style="width: 10%">';
			$value = $list_competences_transverses[$i][5];
			if($action == "edit"){
				$key = 'niveau';
				$val = $competenceTransverse_level_user->fields[$key];
				$keysuffix = "[".$list_competences_transverses[$i][6]."]";
				print $competenceTransverse_level_user->showInputField($val, $key, $value, '', $keysuffix, '', 0);	
			}
			else {
				print $competenceTransverse_level_user->fields["niveau"]["arrayofkeyval"][$value];
			}
			print '</td>';

			print '</tr>'; 
		}

		print '</tbody></table>';


		if($action == "edit" && $permissiontoupdate){
			print '<br/><div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
			print '</div>';
			print "</form>";
		}
		else {
			print '<br/><div class="center">';
			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=2&action=edit&token='.newToken(), '', $permissiontoupdate);
			if($gpec->id > 0 && $gpec->status == 1){
				print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=2&action=validate&token='.newToken(), '', $permissiontovalidate);
			}
			print '</div>';
		}
		print '</div>';
	}
	elseif($onglet == 3){
		print '<div class="tabBar">';

		if($action == "edit") {
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="onglet" value="3">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
		}

		if($action == "edit_classificationPro") {
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update_classificationPro">';
			print '<input type="hidden" name="onglet" value="3">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
		}

		print '<div class="statusref">';
		print $gpec->getLibStatut(5);
		print '</div><br><br><br>';


		// TABLE SALARIES
		print '<table id="salaries" style="width: 90%; margin: auto; border-collapse: collapse;">';
		print '<thead>';
		print '<tr id="titre"><td colspan="9" class="titre_tab">'."SALARIÉS".'</td></tr>';
		print '<tr>';
		print '<td id="titre">Société OPTIM Industries</td>';
		print '<td id="titre">Matricule</td>';
		print '<td id="titre">Nom</td>';
		print '<td id="titre">Prénom</td>';
		print '<td id="titre">Date d\'entrée dans la société</td>';
		print '<td id="titre">Ancienneté chez OPTIM Industries</td>';
		print '<td id="titre">Ancienneté dans le métier</td>';
		print '<td id="titre">Diplôme</td>';
		print '<td id="titre">Classification professionnelle</td>';
		print '</tr></thead><tbody>';

		if(!empty($object->dateemployment)){
			$anciennette_optim = floor(num_between_day($object->dateemployment, dol_now(), false)/365.25) + 1;
		}
		else {
			$anciennette_optim = "";
		}
		print '<tr>';
		if($action == "edit"){
			$key = 'niveau';
			print '<td id="employeur">'.$object->array_options['options_employeur'].'</td>';
			print '<td id="matricule">'.$object->array_options['options_matricule'].'</td>';
			print '<td id="nom">'.$object->lastname.'</td>';
			print '<td id="prenom">'.$object->firstname.'</td>';
			print '<td id="date_entree">'.dol_print_date($object->dateemployment, "%d/%m/%Y").'</td>';
			print '<td id="anciennette_optim">'.$anciennette_optim.'</td>';
			$key = 'anciennete_metier';
			print '<td id="ancienneteMetier">';
			print $formother->select_year($matrice_competence->$key, 'anciennete_metier', 0, 40, 0);
			print '</td>';
			$key = 'diplome';
			print '<td id="diplome">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 1).'</td>';
			$key = 'classification_pro';
			print '<td id="classification_prof">';
			print $matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0);
			print '</td>';
		}
		else {
			print '<td id="employeur">'.$extrafields->showOutputField('employeur', $object->array_options['options_employeur'], '', 'user').'</td>';
			print '<td id="matricule">'.$object->array_options['options_matricule'].'</td>';
			print '<td id="nom">'.$object->lastname.'</td>';
			print '<td id="prenom">'.$object->firstname.'</td>';
			print '<td id="date_entree">'.dol_print_date($object->dateemployment, "%d/%m/%Y").'</td>';
			print '<td id="anciennette_optim">'.$anciennette_optim.'</td>';
			$anciennete_metier = (!empty($matrice_competence->anciennete_metier) ? date("Y")-$matrice_competence->anciennete_metier+1 : "/");
			print '<td id="anciennete_metier">'.$anciennete_metier.'</td>';
			print '<td id="diplome">'.$matrice_competence->diplome.'</td>';
			print '<td id="classification_pro">'.$matrice_competence->fields["classification_pro"]["arrayofkeyval"][$matrice_competence->classification_pro].'</td>';
		}
		print '</tr>';
		print '</tbody></table><br><br>';




		//  Classification professionnelle par Profil
		print '<table id="classification_pro" style="width: 90%; margin: auto; border-collapse: collapse;">';
		print '<thead>';
		print '<tr id="titre"><td colspan="8" class="titre_tab">'."CLASSIFICATION PROFESSIONNELLE PAR PROFIL".'</td></tr>';
		print '<tr>';
		print '<td id="titre">TD</td>';
		print '<td id="titre">T</td>';
		print '<td id="titre">TE</td>';
		print '<td id="titre">TC</td>';
		print '<td id="titre">ID</td>';
		print '<td id="titre">I</td>';
		print '<td id="titre">IC</td>';
		print '<td id="titre">IE</td>';
		print '</tr></thead><tbody>';

		print '<tr>';
		if($action == "edit_classificationPro" && $permissiontoupdate_resp){
			$key = 'is_td';
			print '<td id="td">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'is_t';
			print '<td id="t">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'is_te';
			print '<td id="te">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'is_tc';
			print '<td id="tc">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'is_id';
			print '<td id="id">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'is_i';
			print '<td id="i">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'is_ic';
			print '<td id="ic">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'is_ie';
			print '<td id="ie">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
		}
		else {
			print '<td id="is_td">'.($matrice_competence->is_td ? "X" : "&nbsp;").'</td>';
			print '<td id="is_t">'.($matrice_competence->is_t ? "X" : "").'</td>';
			print '<td id="is_te">'.($matrice_competence->is_te ? "X" : "").'</td>';
			print '<td id="is_tc">'.($matrice_competence->is_tc ? "X" : "").'</td>';
			print '<td id="is_id">'.($matrice_competence->is_id ? "X" : "").'</td>';
			print '<td id="is_i">'.($matrice_competence->is_i ? "X" : "").'</td>';
			print '<td id="is_ic">'.($matrice_competence->is_ic ? "X" : "").'</td>';
			print '<td id="is_ie">'.($matrice_competence->is_ie ? "X" : "").'</td>';
		}
		print '</tr>';
		print '</tbody></table><br><br>';




		// Fonctions/Profils
		print '<table id="fonctions_profils" style="width: 90%; margin: auto; border-collapse: collapse;">';
		print '<thead>';
		print '<tr id="titre"><td colspan="13" class="titre_tab">'."FONCTIONS - PROFILS".'</td></tr>';
		print '<tr>';
		print '<td id="titre">Chef de projet</td>';
		print '<td id="titre">Pilote d\'affaire</td>';
		print '<td id="titre">Ingénieur Confirmé</td>';
		print '<td id="titre">Préparateur chargé d\'affaire ancrage/supportage</td>';
		print '<td id="titre">Préparateur méthodes</td>';
		print '<td id="titre">Chargé d\'affaires PIAT électricité/automatisme</td>';
		print '<td id="titre">Electricien</td>';
		print '<td id="titre">Chargé d\'affaires PIAT mécanique</td>';
		print '<td id="titre">Mécanicien</td>';
		print '<td id="titre">Robinettier</td>';
		print '<td id="titre">PCR opérationnel</td>';
		print '<td id="titre">Technicien RO</td>';
		print '<td id="titre">Chargé d\'affaires multi-spécialités</td>';
		print '</tr></thead><tbody>';

		print '<tr>';
		if($action == "edit"){
			$key = 'chef_projet';
			print '<td id="chefProjet">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'pilote_affaire';
			print '<td id="piloteAffaire">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'ingenieur_confirme';
			print '<td id="ingenieurConfirme">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'preparateur_charge_affaire';
			print '<td id="preparateurChargeAffaire">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'preparateur_methodes';
			print '<td id="preparateurMethodes">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'charge_affaires_elec_auto';
			print '<td id="chargeAffairesElecAuto">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'electricien';
			print '<td id="electricien_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'charge_affaires_mecanique';
			print '<td id="chargeAffairesMecanique">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'mecanicien';
			print '<td id="mecanicien_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'robinettier';
			print '<td id="robinettier_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'pcr_operationnel';
			print '<td id="pcrOperationnel">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'technicien_rp';
			print '<td id="technicienRp">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'charge_affaires_multi_specialites';
			print '<td id="chargeAffairesMultiSpecialites">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
		}
		else {
			print '<td id="chef_projet">'.($matrice_competence->chef_projet ? "X" : "&nbsp;").'</td>';
			print '<td id="pilote_affaire">'.($matrice_competence->pilote_affaire ? "X" : "").'</td>';
			print '<td id="ingenieur_confirme">'.($matrice_competence->ingenieur_confirme ? "X" : "").'</td>';
			print '<td id="preparateur_charge_affaire">'.($matrice_competence->preparateur_charge_affaire ? "X" : "").'</td>';
			print '<td id="preparateur_methodes">'.($matrice_competence->preparateur_methodes ? "X" : "").'</td>';
			print '<td id="charge_affaires_elec_auto">'.($matrice_competence->charge_affaires_elec_auto ? "X" : "").'</td>';
			print '<td id="electricien">'.($matrice_competence->electricien ? "X" : "").'</td>';
			print '<td id="charge_affaires_mecanique">'.($matrice_competence->charge_affaires_mecanique ? "X" : "").'</td>';
			print '<td id="mecanicien">'.($matrice_competence->mecanicien ? "X" : "").'</td>';
			print '<td id="robinettier">'.($matrice_competence->robinettier ? "X" : "").'</td>';
			print '<td id="pcr_operationnel">'.($matrice_competence->pcr_operationnel ? "X" : "").'</td>';
			print '<td id="technicien_rp">'.($matrice_competence->technicien_rp ? "X" : "").'</td>';
			print '<td id="charge_affaires_multi_specialites">'.($matrice_competence->charge_affaires_multi_specialites ? "X" : "").'</td>';
		}
		print '</tr>';
		print '</tbody></table><br><br>';




		// Reference Metier et domaine AMOA
		$list_moyenne = $competenceDomaine_level_user->getMoyennesCompetences($userid);
		print '<table id="moyennes" style="width: 90%; margin: auto; border-collapse: collapse;">';
		print '<thead>';
		print '<tr id="titre"><td colspan="'.count($list_moyenne).'" class="titre_tab">'."REFERENCE METIER ET DOMAINE AMOA".'</td></tr>';
		
		print '<tr>';
		foreach($list_moyenne as $domaine => $moyenne){
			print '<td id="titre">'.$domaine.'</td>';
		}
		print '</tr></thead><tbody>';

		print '<tr>';
		foreach($list_moyenne as $domaine => $moyenne){
			print '<td style="text-align: center">'.round($moyenne, 1).'</td>';
		}
		print '</tr>';
		print '</tbody></table><br><br>';




		// Référence Métier et Domaine Méthode
		print '<table id="ref_metier" style="width: 90%; margin: auto; border-collapse: collapse;">';
		print '<thead>';
		print '<tr id="titre"><td colspan="10" class="titre_tab">'."REFERENCE METIER ET DOMAINE METHODE".'</td></tr>';
		print '<tr>';
		print '<td id="titre">Mécanique Machine Tournante</td>';
		print '<td id="titre">Robinetterie</td>';
		print '<td id="titre">Chaudonnerie</td>';
		print '<td id="titre">Tuyauterie/Soudage</td>';
		print '<td id="titre">Automatisme</td>';
		print '<td id="titre">Electricité</td>';
		print '<td id="titre">Ventilation</td>';
		print '<td id="titre">Logistique</td>';
		print '<td id="titre">Sécurité</td>';
		print '<td id="titre">Soudage</td>';
		print '</tr></thead><tbody>';

		print '<tr>';
		if($action == "edit"){
			$key = 'mec_machine_tournante';
			print '<td id="mecMachineTournante">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'robinetterie';
			print '<td id="robinetterie_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'chaudronnerie';
			print '<td id="chaudronnerie_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'tuyauterie_soudage';
			print '<td id="tuyauterieSoudage">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'automatisme';
			print '<td id="automatisme_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'electricite';
			print '<td id="electricite_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'ventilation';
			print '<td id="ventilation_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'logistique';
			print '<td id="logistique_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'securite';
			print '<td id="securite_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';
			$key = 'soudage';
			print '<td id="soudage_">'.$matrice_competence->showInputField($matrice_competence->fields[$key], $key, $matrice_competence->$key, '', '', '', 0).'</td>';	
		}
		else {
			print '<td id="mec_machine_tournante">'.(!empty($matrice_competence->mec_machine_tournante) ? $matrice_competence->fields["mec_machine_tournante"]["arrayofkeyval"][$matrice_competence->mec_machine_tournante] : "&nbsp;").'</td>';
			print '<td id="robinetterie">'.$matrice_competence->fields["robinetterie"]["arrayofkeyval"][$matrice_competence->robinetterie].'</td>';
			print '<td id="chaudronnerie">'.$matrice_competence->fields["chaudronnerie"]["arrayofkeyval"][$matrice_competence->chaudronnerie].'</td>';
			print '<td id="tuyauterie_soudage">'.$matrice_competence->fields["tuyauterie_soudage"]["arrayofkeyval"][$matrice_competence->tuyauterie_soudage].'</td>';
			print '<td id="automatisme">'.$matrice_competence->fields["automatisme"]["arrayofkeyval"][$matrice_competence->automatisme].'</td>';
			print '<td id="electricite">'.$matrice_competence->fields["electricite"]["arrayofkeyval"][$matrice_competence->electricite].'</td>';
			print '<td id="ventilation">'.$matrice_competence->fields["ventilation"]["arrayofkeyval"][$matrice_competence->ventilation].'</td>';
			print '<td id="logistique">'.$matrice_competence->fields["logistique"]["arrayofkeyval"][$matrice_competence->logistique].'</td>';
			print '<td id="securite">'.$matrice_competence->fields["securite"]["arrayofkeyval"][$matrice_competence->securite].'</td>';
			print '<td id="soudage">'.$matrice_competence->fields["soudage"]["arrayofkeyval"][$matrice_competence->soudage].'</td>';	
		}
		print '</tr>';
		print '</tbody></table><br><br>';




		if($action == "edit" && $permissiontoupdate){
			print '<br/><div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
			print '</div>';
			print "</form>";
		}
		else if($action == "edit_classificationPro" && $permissiontoupdate_resp){
			print '<br/><div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
			print '</div>';
			print "</form>";
		}
		else {
			print '<br/><div class="center">';
			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=3&action=edit&token='.newToken(), '', $permissiontoupdate);
			print dolGetButtonAction($langs->trans('Modify')." la classification pro", '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=3&action=edit_classificationPro&token='.newToken(), '', $permissiontoupdate_resp);
			if($gpec->id > 0 && $gpec->status == 1){
				print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=3&action=validate&token='.newToken(), '', $permissiontovalidate);
			}
			print '</div>';
		}
		print '</div>';
	}
	elseif($onglet == 4){
		$gpec = new Gpec($db);
		$morewhere = " AND fk_user = ".$userid." AND onglet = 1";
		$gpec->fetchByUserAndOnglet($morewhere);
		print '<div class="tabBar">';
		print '<div class="titre" style="text-align: center"><strong>Compétences par Domaine';
		print '</strong></div><br>';
		print $gpec->historique;
		print '</div>';

		$gpec = new Gpec($db);
		$morewhere = " AND fk_user = ".$userid." AND onglet = 2";
		$gpec->fetchByUserAndOnglet($morewhere);
		print '<div class="tabBar">';
		print '<div class="titre" style="text-align: center"><strong>Compétences Transverses';
		print '</strong></div><br>';
		print $gpec->historique;
		print '</div>';

		$gpec = new Gpec($db);
		$morewhere = " AND fk_user = ".$userid." AND onglet = 3";
		$gpec->fetchByUserAndOnglet($morewhere);
		print '<div class="tabBar">';
		print '<div class="titre" style="text-align: center"><strong>Matrice Compétence';
		print '</strong></div><br>';
		print $gpec->historique;
		print '</div>';

	}


	
}

// End of page
llxFooter();
$db->close();

