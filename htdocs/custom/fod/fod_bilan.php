<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
 *
 * Licence information
 */

/**
 *   	\file       fod_bilan.php
 *		\ingroup    fod
 *		\brief      Page to view bilan of fod
 */


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/extendeduser.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/html.extendedform.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
dol_include_once('/fod/class/fod.class.php');
dol_include_once('/fod/class/fod_user.class.php');
dol_include_once('/fod/lib/fod_fod.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("fod@fod", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$intervid = GETPOST('intervid', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'aZ09');
$cancel	= GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$massaction = GETPOST('massaction', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object = new Fod($db);
if ($id){
	$object->fetch($id, null, true);
}
$fod_user = new Fod_user($db);
$extendeduser = new ExtendedUser($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Vérifie si l'utilisateur actif est un intervenant 
$user_interv = 0;
foreach($object->intervenants as $intervenant){
	if ($intervenant->id == $user->id){
		$user_interv = 1;
		break;
	}
}

// Vérifie si l'utilisateur actif est un RD 
$userIsRd = 0;
$user_group = New UserGroup($db);
$user_group->fetch(17);
if(!empty($user_group)){
	$liste_rd = $user_group->listUsersForGroup();
	foreach($liste_rd as $rd){
		if ($rd->id == $user->id){
			$userIsRd = 1;
			break;
		}
	}
}

// Vérifie si l'utilisateur actif est kdans le groupe direction
$Direction = 0;
$user_group = New UserGroup($db);
$user_group->fetch(10);
if(!empty($user_group)){
	$liste_dir = $user_group->listUsersForGroup();
	foreach($liste_dir as $dir){
		if ($dir->id == $user->id){
			$Direction = 1;
			break;
		}
	}
}

// Vérifie si l'utilisateur actif est un RA du projet
$projet = New Project($db);
$projet->fetch($object->fk_project);
$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
if(in_array($user->id, $liste_chef_projet)){
	$userRA = 1;
}
else $userRA = 0;

// Vérifie si l'utilisateur actif est un PCR
$user_group = New UserGroup($db);
$user_group->fetch(11);
$liste_PCR = $user_group->listUsersForGroup();
$userPCR = 0;
foreach($liste_PCR as $pcr){
	if ($pcr->id == $user->id){
		$userPCR = 1;
		break;
	}
}

$permissiontoread = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user_interv || $userIsRd || $user->admin || $userRA || $userPCR;
$permissiontoadd = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user_interv || $userIsRd || $user->admin || $userRA || $userPCR; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
//$permissionToEditandDeleteIntervenant = $user->rights->fod->fod->readAll || $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user_interv;
$permissionToCloture = $userIsRd || $userPCR || $Direction || $user->admin;

// Security check
if (empty($conf->fod->enabled)) {
	accessforbidden('Module non activé');
}

if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
include DOL_DOCUMENT_ROOT.'/custom/fod/core/actions_addupdatedelete.inc.php';
include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

/*
 * View
 *
 * Put here all code to build page
 */

$form = new ExtendedForm($db);

$title = $langs->trans("Fod");
$help_url = '';
llxHeader('', $title, $help_url);

// Part to show record
if ($object->id > 0) {
	$res = $object->fetch_optionals();

	$head = fodPrepareHead($object);
	print dol_get_fiche_head($head, 'bilan', $langs->trans("Fod"), -1, $object->picto);

	$formconfirm = '';
	// Confirmation pour refus de la fod

	if ($action == 'refus_bilan' && ($userIsRd || $userPCR || $Direction)) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('RefusBilanFod'), '', 'confirm_refus_bilan', array(array('label'=>'Raison du refus', 'type'=>'text', 'name'=>'raison_refus_bilan')), 0, 2);
	}
	else if ($massaction == 'verif_bilan_inter' && ($user_interv || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd) && !$cancel) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidationBilanFOD'), 'Voulez vous valider le bilan (vous ne pourrez plus le modifier) ? ATTENTION : Veuillez saisir vos dernières entrées en ZC', 'confirm_verif_bilan_inter', '', 0, 1);
	}
	else if($massaction == 'validate_bilan_rsr' && !$cancel){
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidationBilanFOD'), 'Voulez vous valider le bilan (vous ne pourrez plus le modifier) ?', 'confirm_validate_bilan_rsr', '', 0, 1);
	}
	else if($massaction == 'validate_bilan_rsrra' && !$cancel){
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidationBilanFOD'), 'Voulez vous valider le bilan (vous ne pourrez plus le modifier) ?', 'confirm_validate_bilan_rsrra', '', 0, 1);
	}
	else if($massaction == 'validate_bilan_rsrrapcr' && !$cancel){
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidationBilanFOD'), 'Voulez vous valider le bilan (vous ne pourrez plus le modifier) ?', 'confirm_validate_bilan_rsrrapcr', '', 0, 1);
	}

	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/fod/fod_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab_fod($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print "<h1 style='text-align: center;'> Bilan radiologique de l'intervention</h1><br/>";

	if ($action == 'editrsr' || $action == 'editra' || $action == 'editpcr' || $action == 'editintervenant' || $action == 'editrd'){
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		if ($action == 'editintervenant'){
			print '<input type="hidden" name="action" value="update_userfod">';
			print '<input type="hidden" name="prise_en_compte_fin" value="1">';
			if($intervid > 0){
				print '<input type="hidden" name="intervid" value="'.$intervid.'">';
			}
		}
		else {
			print '<input type="hidden" name="action" value="update">';
		}
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		if ($backtopage) {
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		}
		if ($backtopageforcancel) {
			print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
		}
	}

	// Récapitulatif des doses individuelles relevées (automatique)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'> Récapitulatif des doses individuelles relevées :</h3><hr style='margin-top: 0px;'>";
	$i = 0;
	$tab = 0;
	while ($i < count($object->intervenants)){
		$liste_intervenants = array_slice($object->intervenants, $tab*5, 5);
		$i += count($liste_intervenants);
		if ($tab > 0 && count($liste_intervenants) < 5){
			$width = 1/6*100;
		}
		else {
			$width = 1/(count($liste_intervenants)+1)*100;
		}
		
		print '<table style="border-collapse: collapse; width: 100%;"><tbody>';
		// Nom et prénom
		print '<tr>';
		print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"><strong>Prénom/Nom</strong></td>';
		foreach($liste_intervenants as $intervenant){
			print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%">'.$intervenant->firstname." ".$intervenant->lastname.'</td>';
		}
		if ($tab > 0 && count($liste_intervenants) < 5) {
			$td = 0;
			while ($td < 5 - count($liste_intervenants)){
				print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"></td>';
				$td++;
			}
		}
		print '</tr>';
		// Contrat
		print '<tr>';
		print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"><strong>Contrat</strong></td>';
		foreach($liste_intervenants as $intervenant){
			$fod_user_id = $fod_user->getIdWithUserAndFod($intervenant->id, $object->id);
			$fod_user->fetch($fod_user_id);
			print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%">'.$fod_user->showOutputField($fod_user->fields['contrat'], 'contrat', $fod_user->contrat, '', '', '', 0).'</td>';
		}
		if ($tab > 0 && count($liste_intervenants) < 5) {
			$td = 0;
			while ($td < 5 - count($liste_intervenants)){
				print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"></td>';
				$td++;
			}
		}
		print '</tr>';
		// Nombre de dose
		print '<tr>';
		print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%">'."<strong>Nombre d'entrée</strong></td>";
		foreach($liste_intervenants as $intervenant){
			print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%">'.$fod_user->getNbEntree($intervenant->id, $object->id).'</td>';
		}
		if ($tab > 0 && count($liste_intervenants) < 5) {
			$td = 0;
			while ($td < 5 - count($liste_intervenants)){
				print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"></td>';
				$td++;
			}
		}
		print '</tr>';
		// Dose Moyenne
		print '<tr>';
		print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"><strong>Dose moyenne (mSv)</strong></td>';
		$dose_moyenne = $object->GetDoseIndividuelleMoyenneOptimise();
		foreach($liste_intervenants as $intervenant){
			$extendeduser->fetch($intervenant->id);
			print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%">';
			$dose_interv = $extendeduser->getDoseFod($object);
			$pourcentage = number_format(($dose_interv/$dose_moyenne)*100, 0);
			if ($pourcentage < $conf->global->FOD_Pourcentage_RisqueDepassement){
				print '<span class="badge  badge-status4 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dose_interv, 3).' mSv / '.number_format($dose_moyenne, 3).' mSv';
			}
			else if ($pourcentage >= $conf->global->FOD_Pourcentage_RisqueDepassement && $pourcentage <= 100){
				print '<span class="badge  badge-status1 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dose_interv, 3).' mSv / '.number_format($dose_moyenne, 3).' mSv';
			}
			else {
				print '<span class="badge  badge-status8 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dose_interv, 3).' mSv / '.number_format($dose_moyenne, 3).' mSv';
			}
			print '</td>';
		}
		if ($tab > 0 && count($liste_intervenants) < 5) {
			$td = 0;
			while ($td < 5 - count($liste_intervenants)){
				print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"></td>';
				$td++;
			}
		}
		print '</tr>';
		// Dose Max
		print '<tr>';
		print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"><strong>Dose max (mSv)</strong></td>';
		foreach($liste_intervenants as $intervenant){
			$extendeduser->fetch($intervenant->id);
			print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%">';
			$dose_max = $extendeduser->getDoseMaxFod($object);
			$dose_interv = $extendeduser->getDoseFod($object);
			$pourcentage = number_format(($dose_interv/$dose_max)*100, 0);
			if ($pourcentage < $conf->global->FOD_Pourcentage_RisqueDepassement){
				print '<span class="badge  badge-status4 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dose_interv, 3).' mSv / '.number_format($dose_max, 3).' mSv';
			}
			else if ($pourcentage >= $conf->global->FOD_Pourcentage_RisqueDepassement && $pourcentage <= 100){
				print '<span class="badge  badge-status1 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dose_interv, 3).' mSv / '.number_format($dose_max, 3).' mSv';
			}
			else {
				print '<span class="badge  badge-status8 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dose_interv, 3).' mSv / '.number_format($dose_max, 3).' mSv';
			}
			print '</td>';
		}
		if ($tab > 0 && count($liste_intervenants) < 5) {
			$td = 0;
			while ($td < 5 - count($liste_intervenants)){
				print '<td style="border: 0.5px solid  #001b40; text-align: center; width:'.$width.'%"></td>';
				$td++;
			}
		}
		print '</tr>';
		print '<tbody></table><br/>';
		$tab++;
	}
	print '<br/><br/>';

	// Statut des doses individuelles (RSR)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'>Statut des doses individuelles (RSR)</h3><hr style='margin-top: 0px;'>";
	print '<table style="width: 60%; margin: auto; border-collapse: collapse;">';
	// Question 1 
	$key = 'q1_doses_individuelles';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	// Question 2 
	$key = 'q2_doses_individuelles';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	// Question 3 
	$key = 'q3_doses_individuelles';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	print '</table><br/><br/>';

	// Dose collective (automatique)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'>Récapitulatif de la dose collective relevée</h3><hr style='margin-top: 0px;'>";
	print '<table style="width: 60%; margin: auto; border-collapse: collapse;">';
	print '<tr>'; 
	print '<td style="border: 0.5px solid  #001b40; text-align: center; width: 50%;">'."<strong>Nombre total d'entrée</strong></td>";
	print '<td style="border: 0.5px solid  #001b40; text-align: center; width: 50%;">'.$object->getNbEntree().'</td>';
	print '</tr>';
	print '<tr>'; 
	print '<td style="border: 0.5px solid  #001b40; text-align: center; width: 50%;">';
	print '<h3 style="text-align: center;">Dose collective</h3>';
	print '</td>';
	print '<td style="border: 0.5px solid  #001b40; text-align: center;">';
	if($object->dc_optimise == 1) {
		$dc = $object->GetDoseCollectivePrevisionnelleOptimise();
	}
	else {
		$dc = $object->GetDoseCollectivePrevisionnelle();
	}
	$dc_reel = $object->GetDoseCollectiveReel();
	$pourcentage = number_format(($dc_reel/$dc)*100, 0);
	if ($pourcentage < $conf->global->FOD_Pourcentage_RisqueDepassement){
		print '<span class="badge  badge-status4 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dc_reel, 3).' mSv / '.number_format($dc, 3).' mSv';
	}
	else if ($pourcentage >= $conf->global->FOD_Pourcentage_RisqueDepassement && $pourcentage <= 100){
		print '<span class="badge  badge-status1 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dc_reel, 3).' mSv / '.number_format($dc, 3).' mSv';
	}
	else {
		print '<span class="badge  badge-status8 badge-status" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.$pourcentage.' %</span> => '.number_format($dc_reel, 3).' mSv / '.number_format($dc, 3).' mSv';
	}
	print '</td></tr></table><br><br/>';

	// Statut de la dose collective (RSR)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'>Statut de la dose collective (RSR)</h3><hr style='margin-top: 0px;'>";
	print '<table style="width: 60%; margin: auto; border-collapse: collapse;">';
	// Question 1 
	$key = 'q1_dose_collective';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' &&  ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	// Question 2 
	$key = 'q2_dose_collective';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	print '</table><br/><br/>';

	// Statut de la contamination et  propreté radiologique (RSR)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'>Statut de la contamination et propreté radiologique (RSR)</h3><hr style='margin-top: 0px;'>";
	print '<table style="width: 60%; margin: auto; border-collapse: collapse;">';
	// Question 1 
	$key = 'q1_contamination';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	// Question 2 
	$key = 'q2_contamination';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	print '</table><br/><br/>';

	// Rapprochement SISERI (PCR)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'>Rapprochement SISERI (PCR)</h3><hr style='margin-top: 0px;'>";
	print '<table style="width: 60%; margin: auto; border-collapse: collapse;">';
	// Question 1 
	$key = 'q1_siseri';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 80%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 20%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editpcr' && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	print '</table><br/><br/>';

	// RETOUR D'EXPERIENCE (Intervenants / RSR / RA / PCR)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'>RETOUR D'EXPERIENCE (Intervenants / RSR / RA / PCR)</h3><hr style='margin-top: 0px;'>";
	print '<table style="width: 60%; margin: auto; border-collapse: collapse;">';
	// REX intervenants
	$user_fod = New Fod_user($db);
	foreach($object->intervenants as $intervenant){
		$key = 'rex_intervenant';
		$user_fod_id = $user_fod->getIdWithUserAndFod($intervenant->id, $object->id);
		$user_fod->fetch($user_fod_id);
		$val = $user_fod->fields[$key];
		$value = $user_fod->$key;
		print '<tr class="field_'.$key.'"><td';
		print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
		print $intervenant->firstname.' '.$intervenant->lastname.'</td>';
		print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
		if ($action == 'editintervenant' && ($intervid > 0 ? $intervenant->id == $intervid : $intervenant->id == $user->id)){
			print $user_fod->showInputField($val, $key, $value, '', '', '', 0);
		}
		else{ 
			print $user_fod->showOutputField($val, $key, $value, '', '', '', 0);
		}
		print '</strong></td>';

		$key = 'com_rex';
		$val = $user_fod->fields[$key];
		$value = $user_fod->$key;
		print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
		if ($action == 'editintervenant' && ($intervid > 0 ? ($intervenant->id == $intervid && ($user->id == $object->fk_user_pcr || $user->id == $object->fk_user_raf || $userIsRd)) : $intervenant->id == $user->id)){
			print $user_fod->showInputField($val, $key, $value, '', '', '', 0);
		}
		else{ 
			print $user_fod->showOutputField($val, $key, $value, '', '', '', 0);
		}
		print '</strong></td>';

		print '</tr>';
	}

	// Question 2 
	$key = 'rex_rsr';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';

	$key = 'com_rex_rsr';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrsr' && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';

	// Question 3 
	$key = 'rex_ra';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;"">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editra' && ($user->id == $object->fk_user_raf || $userIsRd || $user->id == $object->fk_user_pcr)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';

	$key = 'com_rex_ra';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editra' && ($user->id == $object->fk_user_raf || $userIsRd || $user->id == $object->fk_user_pcr)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';

	// Question 4 
	$key = 'rex_pcr';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editpcr' && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';

	$key = 'com_rex_pcr';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editpcr' && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';

	// Question 5
	$key = 'rex_rd';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 40%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrd' && ($userIsRd || $userPCR || $Direction)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';

	$key = 'com_rex_rd';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 30%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editrd' && ($userIsRd || $userPCR || $Direction)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	print '</table><br/><br/>';

	// Bilan et Vérification en radioprotection (PCR)
	print "<h3 style='color: #006eb0; text-align: center; margin-bottom: 0px;'>Bilan et Vérification en radioprotection (PCR)</h3><hr style='margin-top: 0px;'>";
	print '<table style="width: 60%; margin: auto; border-collapse: collapse;">';
	// Question 1 
	$key = 'q1_radiopotection';
	$val = $object->fields[$key];
	$value = $object->$key;
	print '<tr class="field_'.$key.'"><td';
	print ' class="fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 50%;">';
	print $langs->trans($val['label']).'</td>';
	print '<td class="valuefield fieldname_'.$key.'" style="border: 0.5px solid  #001b40; text-align: center; width: 50%; background-color: #0068a6b3;"><strong>';
	if ($action == 'editpcr' && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)){
		print $object->showInputField($val, $key, $value, '', '', '', 0);
	}
	else{ 
		print $object->showOutputField($val, $key, $value, '', '', '', 0);
	}
	print '</strong></td>';
	print '</tr>';
	print '</table><br/><br/>';

	if ($action != 'editrsr' && $action != 'editra' && $action != 'editpcr' && $action != 'editrd' && $action != 'editintervenant') {

		print '<div class="tabsAction">'."\n";
		$permissiontomodifyBilan = 0;
		foreach($object->intervenants as $intervenant){
			if($user->id == $intervenant->id){
				$permissiontomodifyBilan = 1;
			}
		}
		$bouton_interv = '';
		if(($object->status == $object::STATUS_BILAN) && $permissiontomodifyBilan){
			$bouton_interv .= '<div class="center">';
			$bouton_interv .= dolGetButtonAction($langs->trans('ModifyBilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editintervenant&token='.newToken(), '');
		}
		if($object->status == $object::STATUS_BILAN && ($user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
			foreach($object->intervenants as $intervenant){
				if($intervenant->id != $user->id){
					if($bouton_interv == ''){
						$bouton_interv .= '<div class="center">';
						$bouton_interv .= dolGetButtonAction($langs->trans('ModifyBilanOf', $intervenant->firstname, $intervenant->lastname), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editintervenant&intervid='.$intervenant->id.'&token='.newToken(), '');
					}
					else{
						$bouton_interv .= dolGetButtonAction($langs->trans('ModifyBilanOf', $intervenant->firstname, $intervenant->lastname), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editintervenant&intervid='.$intervenant->id.'&token='.newToken(), '');
					}
				}
			}
		}
		if($bouton_interv != ''){
			$bouton_interv .= '</div>';
			print $bouton_interv;
		}
		if(($object->status == $object::STATUS_BILANinter) && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
			print '<div class="center">';
			print dolGetButtonAction($langs->trans('ModifyBilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editrsr&token='.newToken(), '');
			print '</div>';
		}
		if(($object->status == $object::STATUS_BILANRSR) && ($user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
			print '<div class="center">';
			print dolGetButtonAction($langs->trans('ModifyBilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editra&token='.newToken(), '');
			print '</div>';
		}
		if(($object->status == $object::STATUS_BILANRSRRA) && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)){
			print '<div class="center">';
			print dolGetButtonAction($langs->trans('ModifyBilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editpcr&token='.newToken(), '');
			print '</div>';
		}
		if(($object->status == $object::STATUS_BILANRSRRAPCR) && ($userIsRd || $userPCR || $Direction)){
			print '<div class="center">';
			print dolGetButtonAction($langs->trans('ModifyBilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editrd&token='.newToken(), '');
			print dolGetButtonAction($langs->trans('Refus Bilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refus_bilan&confirm=yes&token='.newToken(), '');
			// Clôture
			if ($permissionToCloture) {
				print dolGetButtonAction($langs->trans('Clotûre'), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_cloture&confirm=yes&token='.newToken(), '', $permissionToCloture);
			}
		}
		if(($object->status == $object::STATUS_BILAN_REFUS) && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)){
			print '<div class="center">';
			print dolGetButtonAction($langs->trans('ModifyBilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editpcr&token='.newToken(), '');
			print '</div>';
		}
		else if(($object->status == $object::STATUS_BILAN_REFUS) && ($user->id == $object->fk_user_raf)){
			print '<div class="center">';
			print dolGetButtonAction($langs->trans('ModifyBilan'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editra&token='.newToken(), '');
			print '</div>';
		}
		print '<div>';
	}

	if ($action == 'editrsr' || $action == 'editra' || $action == 'editpcr' || $action == 'editrd' || $action == 'editintervenant') {
		if($object->status == $object::STATUS_BILAN){
			print '<input type="hidden" name="massaction" value="verif_bilan_inter">';
		}
		if(($object->status == $object::STATUS_BILANinter) && ($user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)){
			print '<input type="hidden" name="massaction" value="validate_bilan_rsr">';
		}
		if(($object->status == $object::STATUS_BILANRSR && ($user->id == $object->fk_user_raf || $user->id == $object->fk_user_pcr || $userIsRd)) || ($object->status == $object::STATUS_BILAN_REFUS && ($user->id == $object->fk_user_raf && $user->id != $object->fk_user_pcr && !$userPCR && !$userIsRd))){
			print '<input type="hidden" name="massaction" value="validate_bilan_rsrra">';
		}
		if(($object->status == $object::STATUS_BILANRSRRA || $object->status == $object::STATUS_BILAN_REFUS) && ($user->id == $object->fk_user_pcr || $userPCR || $userIsRd)){
			print '<input type="hidden" name="massaction" value="validate_bilan_rsrrapcr">';
		}
		print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("ValiderBilan").'">';
		print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print '</form>';
	}

	print '</div>';
}

// End of page
llxFooter();
$db->close();
