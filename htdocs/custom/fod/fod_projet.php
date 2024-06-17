<?php
/* Copyright (C) 2021 		Lény Metzger  		<leny-07@hotmail.fr>
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
 */

/**
 *	\file		htdocs/custom/fod/fod_projet.php
 *	\ingroup	fod
 *	\brief		Page to view fod data in project 
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/data_intervenant.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/html.extendedform.class.php';


$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'dataintervenantlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page

$dataid			= GETPOST('lineid', 'int'); 
$projectid	= GETPOST('projectid', 'int');
$project_ref = GETPOST('project_ref', 'alpha');
$fodid = GETPOST('fodid');

$search_day_start = GETPOST('search_start_day', 'int');
$search_month_start = GETPOST('search_start_month', 'int');
$search_year_start = GETPOST('search_start_year', 'int');
$search_day_end = GETPOST('search_end_day', 'int');
$search_month_end = GETPOST('search_end_month', 'int');
$search_year_end = GETPOST('search_end_year', 'int');
$search_description = GETPOST('search_description', 'alpha');
$search_user = GETPOST('search_user', 'int');
$search_dose = GETPOST('search_dose', 'double');
$search_niv_contamination = GETPOST('search_niv_contamination', 'int');
$search_portique = GETPOST('search_portique', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page == -1) { // If $page is not defined, or '' or -1
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'd.rowid';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC,DESC';
}

// Déclaration des objets 
$object = new Fod($db);
$projectstatic = new Project($db);
$data_intervenant = new Data_intervenant($db);

// Récupération de l'id du projet 
$projectidforalldata = 0;
if ($projectid > 0) { // Recupération du projet à partir de son id 
	$projectidforalldata = $projectid; // On récupère l'id du projet

	$result = $projectstatic->fetch($projectidforalldata); // On met l'objet projet dans la variable $result  
	if (!empty($projectstatic->socid)) {
		$projectstatic->fetch_thirdparty();
	}
	$res = $projectstatic->fetch_optionals();
} elseif ($project_ref) { // Recupération du projet à partir de sa ref
	$projectstatic->fetch(0, $project_ref);
	$projectidforalldata = $projectstatic->id;
} 

// Security check
$userRead = $projectstatic->restrictedProjectArea($user, 'read');
if (!$user->rights->projet->lire || $userRead == -1) {
	accessforbidden();
}

if (empty($conf->fod->enabled)) {
	accessforbidden('Module non activé');
}

$liste_chef_projet = $projectstatic->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
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

// Vérifie si l'utilisateur actif est un Resp d'antenne
$user_group->fetch(9);
$liste_RespAntenne = $user_group->listUsersForGroup();
$userRespAntenne = 0;
foreach($liste_RespAntenne as $ra){
	if ($ra->id == $user->id) {
		$userRespAntenne = 1;
		break;
	}
}


if($fodid>0){
	$object->fetch($fodid, null, true);
}

$permissionToAddDataIntervenant = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->admin || $userRA || $userPCR || $userRespAntenne;
$permissionToModifyDataIntervenant = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->admin || $userRA || $userPCR || $userRespAntenne;
$permissionToDeleteDataIntervenant = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->admin || $userRA || $userPCR || $userRespAntenne;
$permissionToReadAllData = $user->id == $object->fk_user_pcr || $user->id == $object->fk_user_rsr || $user->id == $object->fk_user_raf || $user->admin || $userRA || $userPCR || $userRespAntenne;

if(!$permissionToReadAllData){
	$search_user = $user->id;
}

if(!empty($search_month_start) && !empty($search_day_start) && !empty($search_year_start)){
	$search_date_start = dol_mktime(-1, -1, -1, $search_month_start, $search_day_start, $search_year_start);
}

if(!empty($search_month_end) && !empty($search_day_end) && !empty($search_year_end)){
	$search_date_end = dol_mktime(-1, -1, -1, $search_month_end, $search_day_end, $search_year_end);
}

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = '';
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_day = '';
	$search_month = '';
	$search_year = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_description = '';
	$search_user = 0;
	$search_dose = '';
	$search_niv_contamination = '';
	$search_portique = '';
	$toselect = '';
	$search_array_options = array();
	$action = '';
}

if ($action == 'adddataintervenant' && $permissionToAddDataIntervenant) { // Ajouter ligne du tableau 
	$error = 0;

	if (empty(GETPOST("timelinemonth", 'int')) || empty(GETPOST("timelineday", 'int')) || empty(GETPOST("timelineyear", 'int'))) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}
	$date = dol_mktime(-1, -1, -1, GETPOST("timelinemonth", 'int'), GETPOST("timelineday", 'int'), GETPOST("timelineyear", 'int'));

	if (!GETPOST("userid", 'int') || GETPOST("userid", 'int') == -1) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
		$error++;
	}

	if (!GETPOST("dose", 'double')) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}

	if (0>GETPOST("dose", "double") || 9.999<GETPOST("dose", "double")) {
		setEventMessages($langs->trans('ErrorFormat', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}
	
	$object->fetch($fodid, null, true);
	if(!empty($object->date_fin_prolong)){
		$datefin = $object->date_fin_prolong;
	}
	else {
		$datefin = $object->date_fin;
	}

	if($object->date_debut > $date || $datefin < $date){
		setEventMessages($langs->trans('ErrorDateFod'), null, 'errors');
		$error++;
	}

	if (!$error) {
		$projectstatic->fetch($projectidforalldata);
		if (empty($projectstatic->statut)) {
			setEventMessages($langs->trans("ProjectMustBeValidatedFirst"), null, 'errors');
			$action = 'createdata';
			$error++;
		} 
		else {
			$data_intervenant->date = $date;
			$data_intervenant->fk_user = GETPOST("userid", 'int');
			$data_intervenant->dose = GETPOST("dose", 'double');
			$data_intervenant->fk_fod = $fodid;
			$data_intervenant->fk_user_creat = $user->id;
			$data_intervenant->fk_user_modif = $user->id;

			if (!empty(GETPOST('niv_contamination', 'int'))) {
				$data_intervenant->niv_contamination = GETPOST('niv_contamination', 'int');
			}
			if (!empty(GETPOST('portique', 'int'))) {
				$data_intervenant->portique = GETPOST('portique', 'int');
			}
			if (!empty(GETPOST('description', 'text'))) {
				$data_intervenant->description = GETPOST('description', 'text');
			}

			$result = $data_intervenant->create($user);

			if ($result >= 0) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			} 
			else {
				setEventMessages($langs->trans($object->error), null, 'errors');
				$error++;
			}
		}
	}
}

if (($action == 'updateline') && !$cancel && $permissionToModifyDataIntervenant) { // Modifier lugne du tableau
	$error = 0;

	if (!GETPOST("timelinemonth") && !GETPOST("timelineday") && !GETPOST("timelineyear")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}

	if (!GETPOST("dose","double")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}

	if (0>GETPOST("dose", "double") || 9.999<GETPOST("dose", "double")) {
		setEventMessages($langs->trans('ErrorFormat', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}

	if (!$error) {
		if($dataid > 0){
			$now = dol_now();

			$data_intervenant->fetch($dataid);
			$data_intervenant->dose = GETPOST("dose", 'double');
			$data_intervenant->niv_contamination = GETPOST("niv_contamination", 'int');
			$data_intervenant->portique = GETPOST("portique", 'int');
			$data_intervenant->description = GETPOST("description", "text");
			$data_intervenant->date = dol_mktime(12, 0, 0, GETPOST("timelinemonth", 'int'), GETPOST("timelineday", 'int'), GETPOST("timelineyear", 'int'));
			$data_intervenant->fk_user = GETPOST("userid", 'int');
			$data_intervenant->tms = $db->idate($now);
			$data_intervenant->fk_user_modif = $user->id;

			$result = $data_intervenant->update($user);

			if ($result >= 0) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans($data_intervenant->error), null, 'errors');
				$error++;
			}
		}
		else {
			setEventMessages($langs->trans($data_intervenant->error), null, 'errors');
		}
	} 
	else {
		$action = '';
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $permissionToDeleteDataIntervenant) { // Supprimer ligne du tableau
	$data_intervenant->fetch($dataid);
	$result = $data_intervenant->delete($user);

	if ($result < 0) {
		$langs->load("errors");
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
		$action = '';
	} else {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	}
}


/*
 * View
 */

$arrayofselected = is_array($toselect) ? $toselect : array();

llxHeader("", $langs->trans("Fod"));

$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);
$userstatic = new ExtendedUser($db);

$projet_fod = $projectstatic->array_options['options_fod'];

if ($projectidforalldata > 0) {

	// Fiche Projet
	$result = $projectstatic->fetch($projectidforalldata); 
	if (!empty($projectstatic->socid)) {
		$projectstatic->fetch_thirdparty();
	}
	$res = $projectstatic->fetch_optionals();

	$linktocreatedata = '';

	if ($projectstatic->id > 0) {
		// Onglets
		$objecttmp = $object;
		$object = $projectstatic;
		$head = project_prepare_head($projectstatic);
		$object = $objecttmp;
		print dol_get_fiche_head($head, 'FOD1', $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));
		$param = ($mode == 'mine' ? '&mode=mine' : '');

		// Project card
		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= $projectstatic->title;
		// Thirdparty
		if ($projectstatic->thirdparty->id > 0) {
			$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$projectstatic->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (!$user->rights->projet->all->lire) {
			$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
			$projectstatic->next_prev_filter = " rowid IN (".$db->sanitize(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
		}

		dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}

	if($projet_fod == 1){
		// Si l'utilisateur est chef de projet, il peut créer une FOD
		if(in_array($user->id, $liste_chef_projet)){
			print '<div style="text-align: center;padding-top: 30px;">';
				print '<a class="btn-link" href="/erp/custom/fod/fod_card.php?action=create&fk_project='.$projectstatic->id.'&refp='.$projectstatic->ref.'" title="Créer une FOD" style="width: 200px;display: inline-block;height: 20px;">';
					print '<span class="fa fa-fod" valignmiddle="" btntitle-icon="" style="padding-right: 5px;"></span>';
					print 'Créer une FOD';
				print '</a>';
			print '</div>';
		}

		$listFod = $object->getListIdByProject($projectidforalldata); // Liste des fod rattachées au projet
		if(!empty($listFod)){
			// Affichage des différentes fod sous forme d'onglet 
			$i = 0;
			$cpt = 0;

			foreach($listFod as $idfod){
				// Vérifie si l'utilisateur actif est un intervenant 
				$user_interv = 0;
				$object->fetch($idfod, null, true);
				foreach($object->intervenants as $intervenant){
					if ($intervenant->id == $user->id){
						$user_interv = 1;
						break;
					}
				}
				$permissionToRead = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user_interv || $userRA || $userPCR || $user->admin || $userRespAntenne;	
				if($permissionToRead){
					if($cpt == 0){
						print '</div>';
						print '<div class="tabs" data-role="controlgroup" data-type="horizontal">';
						print '<a class="tabTitle">';
						print '<span class="fas fa-fod imgTabTitle em120 infobox-fod" style="" title="'.$langs->trans("Fod").'"></span>' ;
						print '<span class="tabTitleText">FOD</span>';
						print '</a>';
					}
					$link = $_SERVER['PHP_SELF'].'?projectid='.$projectidforalldata.'&fodid='.$idfod;
						if($idfod == GETPOST('fodid')){
							print '<div class="inline-block tabsElem tabsElemActive">';
							print '<div class="tab tabactive" style="margin: 0 !important">';
						}
						else {
							print '<div class="inline-block tabsElem">';
							print '<div class="tab tabunactive" style="margin: 0 !important">';
						}
							print '<a id="fod" class="tab inline-block" href="'.$link.'">'.$object->ref;
							$text = '<strong>Statut : </strong>'.$object->LibStatut($object->status, 2).'<br>';
							$text .= '<strong>Installation : </strong>'.$object->installation.'<br>';
							$text .= '<strong>Etat installation : </strong>'.$object->fields['etat_installation']['arrayofkeyval'][$object->etat_installation].'<br>';
							$text .= '<strong>Activité : </strong>'.$object->activite;
							print " ".$form->textwithpicto('', $text);
							print '</a>';
						print '</div>';
					print '</div>';
					$cpt++;
				}
			}
			print '</div>';
			
			// Si on a selectionné un onglet, on affiche la fod en conséquence
			if(!empty(GETPOST('fodid'))){
				// Vérifie si l'utilisateur actif est un intervenant 
				$user_interv = 0;
				$object->fetch(GETPOST('fodid'), null, true);
				foreach($object->intervenants as $intervenant){
					if ($intervenant->id == $user->id){
						$user_interv = 1;
						break;
					}
				}
				$permissionToRead = $object->fk_user_rsr == $user->id || $object->fk_user_raf == $user->id || $object->fk_user_pcr == $user->id || $user_interv || $userRA || $userPCR || $user->admin || $userRespAntenne;	
				if($permissionToRead){
					// Affichage banière + données de la FOD 
					$fodid = GETPOST('fodid');
					$object->fetch($fodid, null, true);
					print '<div class="tabBar">';
					dol_banner_tab_fod($object, 'ref', '', 0, 'ref', 'ref', '');
					print '<div class="fichecenter">';
					print '<div class="fichehalfleft">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border centpercent tableforfield">'."\n";
					$keyforbreak='effectif';	// We change column just before this field
					unset($object->fields['client_site']);
					unset($object->fields['installation']);
					unset($object->fields['etat_installation']);
					unset($object->fields['commentaire_etat_installation']);
					unset($object->fields['activite']);
					include DOL_DOCUMENT_ROOT.'/custom/fod/core/tpl/commonfields_view.tpl.php';
					print '</table>';
					print '</div>';
					print '</div>';
					print '<div class="clearboth"></div>';
					print '</div>';
					echo '<br/>'."\r\n";


					// Bouton + pour ajouter une ligne de donnée
					$linktocreatedataBtnStatus = 0;
					$linktocreatedataUrl = '';
					$linktocreatedataHelpText = '';
					if ($permissionToAddDataIntervenant) {
						$linktocreatedataBtnStatus = 1;
						$backtourl = $_SERVER['PHP_SELF'].'?projectid='.$projectstatic->id;
						$linktocreatedataUrl = $_SERVER['PHP_SELF'].'?projectid='.$projectstatic->id.'&fodid='.$fodid.'&action=createdata'.$param.'&backtopage='.urlencode($backtourl);
					} 
					else {
						$linktocreatedataBtnStatus = 0;
					}
					$linktocreatedata = dolGetButtonTitle($langs->trans('Ajouter'), $linktocreatedataHelpText, 'fa fa-plus-circle', $linktocreatedataUrl, '', $linktocreatedataBtnStatus);


					if ($projectstatic->id > 0) {
						// Affiche une fenêtre de confirmation lorsqu'on supprime une ligne
						if ($action == 'deleteline' && $permissionToDeleteDataIntervenant) {
							print $form->formconfirm($_SERVER["PHP_SELF"]."?".'projectid='.$projectstatic->id.'&fodid='.$fodid.'&lineid='.$dataid, $langs->trans("DeleteAdataintervenant"), $langs->trans("ConfirmDeleteAdataintervenant"), "confirm_delete", '', '', 1);
						}

						// Definition of fields for list
						$arrayfields = array();
						$arrayfields['d.date'] = array('label'=>$langs->trans("Date"), 'checked'=>1);
						$arrayfields['d.fk_user'] = array('label'=>$langs->trans("By"), 'checked'=>1);
						$arrayfields['d.dose'] = array('label'=>$langs->trans("Dose"), 'checked'=>1);
						$arrayfields['d.niv_contamination'] = array('label'=>$langs->trans("NiveauContamination"), 'checked'=>1);
						$arrayfields['d.portique'] = array('label'=>$langs->trans("Portique"), 'checked'=>1);
						$arrayfields['d.description'] = array('label'=>$langs->trans("Note"), 'checked'=>1);

						//$arrayfields = dol_sort_array($arrayfields, 'position');

						// param pour la recherche croissante et decroissante
						$param = '';
						if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
							$param .= '&contextpage='.urlencode($contextpage);
						}
						if ($limit > 0 && $limit != $conf->liste_limit) {
							$param .= '&limit='.urlencode($limit);
						}
						/*if ($search_month > 0) {
							$param .= '&search_month='.urlencode($search_month);
						}
						if ($search_year > 0) {
							$param .= '&search_year='.urlencode($search_year);
						}*/
						if ($search_user > 0) {
							$param .= '&search_user='.urlencode($search_user);
						}
						if ($search_dose != '') {
							$param .= '&search_dose='.urlencode($search_dose);
						}
						if ($search_niv_contamination != '') {
							$param .= '&search_niv_contamination='.urlencode($search_niv_contamination);
						}
						if ($search_portique != '') {
							$param .= '&search_portique='.urlencode($search_portique);
						}
						if ($search_description != '') {
							$param .= '&search_description='.urlencode($search_description);
						}
						if (!empty($projectid)) {
							$param .= '&projectid='.urlencode($projectid);
						}
						if (!empty($fodid)) {
							$param .= '&fodid='.urlencode($fodid);
						}

						// Formulaire
						print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
						if ($action == 'editline' && $permissionToModifyDataIntervenant) {
							print '<input type="hidden" name="action" value="updateline">';
						} 
						elseif ($action == 'createdata' && $permissionToAddDataIntervenant) {
							print '<input type="hidden" name="action" value="adddataintervenant">';
						} 
						else {
							print '<input type="hidden" name="action" value="list">';
						}
						print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
						print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
						print '<input type="hidden" name="lineid" value="'.$dataid.'">';
						print '<input type="hidden" name="projectid" value="'.$projectidforalldata.'">';
						print '<input type="hidden" name="fodid" value="'.$fodid.'">';

					
						// Liste des données intervenants
						$data = array();

						$sql = "SELECT d.rowid, d.date, d.fk_fod, d.fk_user, d.dose, d.niv_contamination, d.portique, d.description,";
						$sql .= " u.lastname, u.firstname, u.login, u.photo, u.statut as user_status";
						$sql .= " FROM ".MAIN_DB_PREFIX."fod_data_intervenant as d,";
						$sql .= " ".MAIN_DB_PREFIX."user as u";
						$sql .= " WHERE d.fk_user = u.rowid AND d.fk_fod = ".$object->id;
						if ($search_description) {
							$sql .= natural_search('d.description', $search_description);
						}
						if ($search_dose) {
							$sql .= natural_search('d.dose', $search_dose);
						}
						if ($search_niv_contamination) {
							$sql .= natural_search('d.niv_contamination', $search_niv_contamination);
						}
						if ($search_portique) {
							$sql .= natural_search('d.portique', $search_portique);
						}
						if ($search_user > 0) {
							$sql .= natural_search('d.fk_user', $search_user);
						}
						if (!empty($search_date_start)) {
							$sql .= " AND d.date >= '".$db->idate($search_date_start)."'";
						}
						if (!empty($search_date_end)) {
							$sql .= " AND d.date <= '" . $db->idate($search_date_end) . "'";
						}
						//$sql .= dolSqlDateFilter('d.date', $search_day, $search_month, $search_year);
						$sql .= $db->order($sortfield, $sortorder);

						// Count total nb of records
						$nbtotalofrecords = '';
						if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
							$resql = $db->query($sql);
							$nbtotalofrecords = $db->num_rows($resql);
							if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
								$page = 0;
								$offset = 0;
							}
						}
						// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
						if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords) {
							$num = $nbtotalofrecords;
						} else {
							$sql .= $db->plimit($limit + 1, $offset);

							$resql = $db->query($sql);
							if (!$resql) {
								dol_print_error($db);
								exit;
							}

							$num = $db->num_rows($resql);
						}

						if ($num >= 0) {
							// Tableau récap
							if ($permissionToReadAllData){
								print '<br><div style="text-align: center;">';
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
									print '<td style="border: 0.5px solid  #d2d2d2; text-align: center; width:'.$width.'%"><strong>Prénom/Nom</strong></td>';
									foreach($liste_intervenants as $intervenant){
										print '<td style="border: 0.5px solid  #d2d2d2; text-align: center; width:'.$width.'%">'.$intervenant->firstname." ".$intervenant->lastname.'</td>';
									}
									if ($tab > 0 && count($liste_intervenants) < 5) {
										$td = 0;
										while ($td < 5 - count($liste_intervenants)){
											print '<td style="border: 0.5px solid  #d2d2d2; text-align: center; width:'.$width.'%"></td>';
											$td++;
										}
									}
									print '</tr>';
									// Dose
									print '<tr>';
									print '<td style="border: 0.5px solid  #d2d2d2; text-align: center; width:'.$width.'%"><strong>Dose (mSv)</strong></td>';
									foreach($liste_intervenants as $intervenant){
										$userstatic->fetch($intervenant->id);
										print '<td style="border: 0.5px solid  #d2d2d2; text-align: center; width:'.$width.'%">';
										$pourcentage = ($userstatic->getDoseFod($object)/$userstatic->getDoseMaxFod($object))*100;
										if (($userstatic->getDoseFod($object) >= round($userstatic->getDoseMaxFod($object) * ($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && ($userstatic->getDoseFod($object) <= $userstatic->getDoseMaxFod($object))){
											$value = 'ATTENTION : Risque de dépassement de la CdD, prendre les dispositions pour la respecter ('.round($pourcentage, 1).'%)';
											print '<span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.number_format($userstatic->getDoseFod($object),3).' mSv</span>';
										}
										else if ($userstatic->getDoseFod($object) > $userstatic->getDoseMaxFod($object)){
											$value = 'ATTENTION : Dépassement de la CdD, autorisation accès en ZC suspendue ('.round($pourcentage, 1).'%)';
											print '<span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.number_format($userstatic->getDoseFod($object),3).' mSv</span>';
										}
										else {
											$value = round($pourcentage, 1).'% de la CdD';
											print '<span class="badge  badge-status4 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.number_format($userstatic->getDoseFod($object),3).' mSv</span>';
										}
										print '</td>';
									}
									if ($tab > 0 && count($liste_intervenants) < 5) {
										$td = 0;
										while ($td < 5 - count($liste_intervenants)){
											print '<td style="border: 0.5px solid  #d2d2d2; text-align: center; width:'.$width.'%"></td>';
											$td++;
										}
									}
									print '</tr>';
									print '<tbody></table>';
									$tab++;
								}
								print '</div>';
							}
							
							// Affichage de la dose avant le tableau 
							print '<br><div style="text-align: center;padding-top: 30px;">';
							if($object->dc_optimise == 1) {
								$dc = $object->GetDoseCollectivePrevisionnelleOptimise();
							}
							else {
								$dc = $object->GetDoseCollectivePrevisionnelle();
							}
							$pourcentage = ($object->GetDoseCollectiveReel()/$dc)*100;
							if (($object->GetDoseCollectiveReel() >= round($dc * ($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && ($object->GetDoseCollectiveReel() <= $dc)){
								$value = 'ATTENTION : Risque de dépassement de la DC, prendre les dispositions pour la respecter ('.round($pourcentage, 1).'%)';
								print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose collective : '.number_format($object->GetDoseCollectiveReel(),3)." H.mSv</span><strong> / ".$dc. " H.mSv (DC prévisionnelle)</strong></td>";
							}
							else if ($object->GetDoseCollectiveReel() > $dc){
								$value = 'ATTENTION : Dépassement de la DC ('.round($pourcentage, 1).'%)';
								print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose collective : '.number_format($object->GetDoseCollectiveReel(),3)." H.mSv</span><strong> / ".$dc. " H.mSv (DC prévisionnelle)</strong></td>";
							}
							else {
								$value = round($pourcentage, 1).'% de la DC';
								print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status4 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose collective : '.number_format($object->GetDoseCollectiveReel(),3)." H.mSv</span><strong> / ".$dc. " H.mSv (DC prévisionnelle)</strong></td>";
							}
							print '</div><br>';

							print '<!-- List of data intervenant for FOD -->'."\n";
							$title = $langs->trans("ListDataIntervenantForFod");
							print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'radioactif@fod', 0, $linktocreatedata, '', $limit, 0, 0, 1);

							$i = 0;
							while ($i < $num) {
								$row = $db->fetch_object($resql);
								$data[$i] = $row;
								$i++;
							}
							$db->free($resql);
						} else {
							dol_print_error($db);
						}

						/*
						* Form to add a new line of data
						*/
						if ($action == 'createdata' && $permissionToAddDataIntervenant) { 
							print '<!-- table to add time spent -->'."\n";
							if (!empty($dataid)) {
								print '<input type="hidden" name="lineid" value="'.$dataid.'">';
							}

							print '<div class="div-table-responsive-no-min">'; 
							print '<table class="noborder nohover centpercent">';
							print '<tr class="liste_titre">';
							print '<td>'.$langs->trans("Date").'</td>';
							print '<td>'.$langs->trans("By").'</td>';
							print '<td>'.$langs->trans("Dose").'</td>';
							print '<td>'.$langs->trans("NivContamination").'</td>';
							print '<td>'.$langs->trans("Portique").'</td>';
							print '<td>'.$langs->trans("Description").'</td>';
							print '<td></td>';
							print "</tr>\n";
							print '<tr class="oddeven nohover">';

							// Date
							print '<td class="maxwidthonsmartphone">';
							$newdate = '';
							print $form->selectDate($date, 'timeline', 0, 0, 2, "data_date", 1, 0);
							print '</td>';

							// User
							print '<td class="maxwidthonsmartphone nowraponall">';
							$intervenant = $object->getListIntervenantId();
							if (count($intervenant) > 0) {
								print img_object('', 'user', 'class="hideonsmartphone"');
								if($permissionToReadAllData){
									print $form->select_dolusers(-1, 'userid', 1, '', 0, $intervenant, '', 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToProject"), 'maxwidth250');
								}
								else print '<div></div>';
							} 
							else {
								print img_error($langs->trans('FirstAddRessourceToAllocateData')).' '.$langs->trans('FirstAddRessourceToAllocateData');
							}
							print '</td>';

							// Dose
							print '<td class="nowrap">';
							print $data_intervenant->showInputField($data_intervenant->fields['dose'], 'dose', '', '', '', '', 0);
							print ' mSv';
							print '</td>';
							
							// Niveau contamination 
							print '<td class="nowrap">';
							print $data_intervenant->showInputField($data_intervenant->fields['niv_contamination'], 'niv_contamination', '', '', '', '', 0);
							print '</td>';

							// Portique
							print '<td class="nowrap">';
							print $data_intervenant->showInputField($data_intervenant->fields['portique'], 'portique', '', '', '', '', 0);
							print '</td>';

							// Description
							print '<td>';
							print '<textarea name="description" class="maxwidth100onsmartphone" rows="'.ROWS_2.'">'.($_POST['description'] ? $_POST['description'] : '').'</textarea>';
							print '</td>';

							print '<td class="center">';
							print '<input type="submit" name="save" class="button buttongen marginleftonly margintoponlyshort marginbottomonlyshort" value="'.$langs->trans("Add").'">';
							print '<input type="submit" name="cancel" class="button buttongen marginleftonly margintoponlyshort marginbottomonlyshort button-cancel" value="'.$langs->trans("Cancel").'">';
							print '</td></tr>';
							print '</table>';
							print '</div>';
							print '<br>';
						}

						$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
						$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

						print '<div class="div-table-responsive">';
						print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

						// Barre de recherche dans la liste
						print '<tr class="liste_titre_filter">';
						// Date
						if (!empty($arrayfields['d.date']['checked'])) {
							print '<td class="liste_titre">';
							/*if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
								print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
							}
							print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
							print $formother->selectyear($search_year, 'search_year', 1, 20, 5);*/
							
							print '<div class="nowrap">';
							print $form->selectDate($search_date_start ? $search_date_start : '', "search_start_", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
							print '</div>';
							print '<div class="nowrap">';
							print $form->selectDate($search_date_end ? $search_date_end : '', "search_end_", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
							print '</div>';
							print '</td>';
						}

						// Author
						if (!empty($arrayfields['d.fk_user']['checked'])) {
							$intervenant = $object->getListIntervenantId();
							print '<td class="liste_titre">'.$form->select_dolusers(($search_user > 0 ? $search_user : -1), 'search_user', 1, null, 0, $intervenant, '', 0, 0, 0, '', 0, '', 'maxwidth250').'</td>';
						}
						// Dose
						if (!empty($arrayfields['d.dose']['checked'])) {
							print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_dose" value="'.dol_escape_htmltag($search_dose).'"></td>';
						}
						// Niveau contamination
						if (!empty($arrayfields['d.niv_contamination']['checked'])) {
							$para = 'value="'.dol_escape_htmltag($search_niv_contamination).'"';
							print '<td class="liste_titre">'.$data_intervenant->showInputField($data_intervenant->fields['niv_contamination'], 'niv_contamination', $search_niv_contamination, $para, '', 'search_', 0).'</td>';
						}
						// Portique
						if (!empty($arrayfields['d.portique']['checked'])) {
							$para = 'value="'.dol_escape_htmltag($search_portique).'"';
							print '<td class="liste_titre">'.$data_intervenant->showInputField($data_intervenant->fields['portique'], 'portique', $search_portique, $para, '', 'search_', 0).'</td>';
						}
						// Description
						if (!empty($arrayfields['d.description']['checked'])) {
							print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_description" value="'.dol_escape_htmltag($search_description).'"></td>';
						}
						// Action column
						print '<td class="liste_titre center">';
						$searchpicto = $form->showFilterButtons();
						print $searchpicto;
						print '</td>';
						print '</tr>'."\n";

						print '<tr class="liste_titre">';
						if (!empty($arrayfields['d.date']['checked'])) {
							print getTitleFieldOfList($arrayfields['d.date']['label'], 0, $_SERVER['PHP_SELF'], 'd.date, d.rowid', '', $param, '', $sortfield, $sortorder);
						}
						if (!empty($arrayfields['d.fk_user']['checked'])) {
							print getTitleFieldOfList($arrayfields['d.fk_user']['label'], 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
						}
						if (!empty($arrayfields['d.dose']['checked'])) {
							print getTitleFieldOfList($arrayfields['d.dose']['label'], 0, $_SERVER['PHP_SELF'], 'd.dose', '', $param, '', $sortfield, $sortorder);
						}
						if (!empty($arrayfields['d.niv_contamination']['checked'])) {
							print getTitleFieldOfList($arrayfields['d.niv_contamination']['label'], 0, $_SERVER['PHP_SELF'], 'd.niv_contamination', '', $param, '', $sortfield, $sortorder);
						}
						if (!empty($arrayfields['d.portique']['checked'])) {
							print getTitleFieldOfList($arrayfields['d.portique']['label'], 0, $_SERVER['PHP_SELF'], 'd.portique', '', $param, '', $sortfield, $sortorder);
						}
						if (!empty($arrayfields['d.description']['checked'])) {
							print getTitleFieldOfList($arrayfields['d.description']['label'], 0, $_SERVER['PHP_SELF'], 'd.description', '', $param, '', $sortfield, $sortorder);
						}

						print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], "", '', '', 'width="80"', $sortfield, $sortorder, 'center maxwidthsearch ');
						print "</tr>\n";

						$i = 0;
						$totalarray = array();

						// Affichage de toutes les lignes 
						foreach ($data as $data_line) {
							if ($i >= $limit) {
								break;
							}
							$res = $data_intervenant->fetch($data_line->rowid);
							print '<tr class="oddeven">';

							// Date
							$date = $db->jdate($data_line->date);
							if (!empty($arrayfields['d.date']['checked'])) {
								print '<td class="nowrap">';
								if ($action == 'editline' && $_GET['lineid'] == $data_line->rowid) {
									print $form->selectDate($date, 'timeline', 0, 0, 2, "data_date", 1, 0);
								} else {
									print dol_print_date($date, 'day');
								}
								print '</td>';
								if (!$i) {
									$totalarray['nbfield']++;
								}
							}

							// By User
							if (!empty($arrayfields['d.fk_user']['checked'])) {
								print '<td class="tdoverflowmax100">';
								if ($action == 'editline' && $_GET['lineid'] == $data_line->rowid) {
									$intervenant = $object->getListIntervenantId();
									if (count($intervenant) > 0) {
										print img_object('', 'user', 'class="hideonsmartphone"');
										print $form->select_dolusers($data_line->fk_user, 'userid', 1, '', 0, $intervenant, '', 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToProject"), 'maxwidth250');
									} 
									else {
										print img_error($langs->trans('FirstAddRessourceToAllocateData')).' '.$langs->trans('FirstAddRessourceToAllocateData');
									}
								} 
								else {
									$userstatic->id = $data_line->fk_user;
									$userstatic->lastname = $data_line->lastname;
									$userstatic->firstname = $data_line->firstname;
									$userstatic->photo = $data_line->photo;
									$userstatic->statut = $data_line->user_status;
									print $userstatic->getNomUrl(-1);
								}
								print '</td>';
								if (!$i) {
									$totalarray['nbfield']++;
								}
							}

							// Dose
							if (!empty($arrayfields['d.dose']['checked'])) {
								print '<td class="tdoverflowmax100">';
								if ($action == 'editline' && $_GET['lineid'] == $data_line->rowid) {
									print '<input type="text" class="flat maxwidth75 maxwidthonsmartphone" name="dose" id="dose" value="'.$data_line->dose.'">';
									print ' mSv';
								} else {
									print $data_line->dose;
									print ' mSv';
								}
								print '</td>';
								if (!$i) {
									$totalarray['nbfield']++;
								}
								if (!$i) {
									$totalarray['totaldosefield'] = $totalarray['nbfield'];
								}
								$totalarray['totaldose'] += $data_line->dose;
							}

							// Niveau de contamination
							if (!empty($arrayfields['d.niv_contamination']['checked'])) {
								print '<td class="tdoverflowmax100">';
								if ($action == 'editline' && $_GET['lineid'] == $data_line->rowid) {
									print $data_intervenant->showInputField($data_intervenant->fields['niv_contamination'], 'niv_contamination', $data_line->niv_contamination, '', '', '', 0);
								} else {
									print $data_intervenant->fields['niv_contamination']['arrayofkeyval'][$data_line->niv_contamination];
								}
								print '</td>';
								if (!$i) {
									$totalarray['nbfield']++;
								}
							}

							// Portique
							if (!empty($arrayfields['d.portique']['checked'])) {
								print '<td class="tdoverflowmax100">';
								if ($action == 'editline' && $_GET['lineid'] == $data_line->rowid) {
									print $data_intervenant->showInputField($data_intervenant->fields['portique'], 'portique', $data_line->portique, '', '', '', 0);
								} else {
									print $data_intervenant->fields['portique']['arrayofkeyval'][$data_line->portique];
								}
								print '</td>';
								if (!$i) {
									$totalarray['nbfield']++;
								}
							}

							// Note
							if (!empty($arrayfields['d.description']['checked'])) {
								print '<td class="small">';
								if ($action == 'editline' && $_GET['lineid'] == $data_line->rowid) {
									print '<textarea name="description" width="95%" rows="'.ROWS_2.'">'.$data_line->description.'</textarea>';
								} else {
									print $data_line->description;
								}
								print '</td>';
								if (!$i) {
									$totalarray['nbfield']++;
								}
							} elseif ($action == 'editline' && $_GET['lineid'] == $data_line->description) {
								print '<input type="hidden" name="description" value="'.$data_line->description.'">';
							}

							// Action column
							print '<td class="center nowraponall">';
							if (($action == 'editline') && $_GET['lineid'] == $data_line->rowid) {
								print '<input type="hidden" name="lineid" value="'.$_GET['lineid'].'">';
								print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-save" name="save" value="'.$langs->trans("Save").'">';
								print '<br>';
								print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
							} 
							elseif ($permissionToModifyDataIntervenant || $permissionToDeleteDataIntervenant) {	 
								if ($permissionToModifyDataIntervenant) {
									print '&nbsp;';
									print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editline&amp;lineid='.$data_line->rowid.$param.'">';
									print img_edit();
									print '</a>';
								}
								if ($permissionToDeleteDataIntervenant){
									print '&nbsp;';
									print '<a class="reposition paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=deleteline&amp;token='.newToken().'&amp;lineid='.$data_line->rowid.$param.'">';
									print img_delete('default', 'class="pictodelete paddingleft"');
									print '</a>';
								}
							}
							print '</td>';
							if (!$i) {
								$totalarray['nbfield']++;
							}
							print "</tr>\n";
							$i++;
						}

						// Show total line
						if (isset($totalarray['totaldose'])) {
							print '<tr class="liste_total">';
							$i = 0;
							while ($i < $totalarray['nbfield']) {
								$i++;
								if ($i == 1) {
									if ($num < $limit && empty($offset)) {
										print '<td class="left">'.$langs->trans("Total").'</td>';
									} else {
										print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
									}
								} 
								elseif ($totalarray['totaldosefield'] == $i) {
										print '<td class="left">'.$totalarray['totaldose'].' mSv'.'</td>';
								} else {
									print '<td></td>';
								}
							}
							print '</tr>';
						}

						if (!count($data)) {
							$totalnboffields = 1;
							foreach ($arrayfields as $value) {
								if ($value['checked']) {
									$totalnboffields++;
								}
							}
							print '<tr class="oddeven"><td colspan="'.$totalnboffields.'">';
							print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
							print '</td></tr>';
						}


						print "</table>";
						print '</div>';
						print "</form>";
	
					}	
				}
				else {
					accessforbidden("Vous n'avez pas accès à cette FOD", 0, 0, 1);
				}
			}
			else {
				print '<div class="tabBar"></div>';
			}
		}
		else {
			accessforbidden("Il n'y a aucune FOD active sur cette affaire", 0, 0, 1);
		}
	}
	else{
		accessforbidden("Cette affaire n'est pas soumise à rayonnement", 0, 0, 1);
	}
}


// End of page
llxFooter();
$db->close();
