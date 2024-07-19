<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
 *
 * Licence information
 */

 /**
 *	\file		htdocs/custom/fod/add_dataintervenant.php
 *	\ingroup	fod
 *	\brief		Page to add fod data for intervenant
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/dataintervenant.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/extendeduser.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/html.extendedform.class.php';

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

$lineid	= GETPOST('lineid', 'int'); // id de la fod
$userid = GETPOST('userid', 'int');
$fodid = GETPOST("fodid", 'int');

$search_day = GETPOST('search_day', 'int');
$search_month = GETPOST('search_month', 'int');
$search_year = GETPOST('search_year', 'int');
$search_description = GETPOST('search_description', 'alpha');
$search_user = GETPOST('search_user', 'int');
$search_dose = GETPOST('search_dose', 'double');
$search_niv_contamination = GETPOST('search_niv_contamination', 'int');
$search_portique = GETPOST('search_portique', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page == -1) {
	$page = 0;
}		// If $page is not defined, or '' or -1
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
$form = new Form($db);
$formother = new FormOther($db);
$user_fiche = new ExtendedUser($db);
$dataintervenant = new DataIntervenant($db);

$res = $user_fiche->fetch($userid, '', '', 1);
if ($res < 0) {
	dol_print_error($db, $user->error);
	exit;
}
	
// Security check
if (empty($conf->fod->enabled)) {
	accessforbidden('Module non activé');
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
	$search_date = '';
	$search_description = '';
	$search_user = 0;
	$search_dose = '';
	$search_niv_contamination = '';
	$search_portique = '';
	$toselect = '';
	$search_array_options = array();
	$action = '';
}

if ($action == 'adddataintervenant' && $user->id == $user_fiche->id) { // Add data intervenant 
	$error = 0;

	if (empty(GETPOST("timelinemonth", 'int')) || empty(GETPOST("timelineday", 'int')) || empty(GETPOST("timelineyear", 'int'))) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}
	$date = dol_mktime(-1, -1, -1, GETPOST("timelinemonth", 'int'), GETPOST("timelineday", 'int'), GETPOST("timelineyear", 'int'));

	if (!GETPOST("dose", 'double')) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}

	if (0>GETPOST("dose", "double") || 9.999<GETPOST("dose", "double")) {
		setEventMessages($langs->trans('ErrorFormat', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}

	$object->fetch($fodid);
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
		$dataintervenant->date = $date;
		$dataintervenant->fk_user = $user->id;
		$dataintervenant->dose = GETPOST("dose", 'double');
		$dataintervenant->fk_fod = $fodid;
		$dataintervenant->fk_user_creat = $user->id;
		$dataintervenant->fk_user_modif = $user->id;

		if (!empty(GETPOST('niv_contamination', 'int'))) {
			$dataintervenant->niv_contamination = GETPOST('niv_contamination', 'int');
		}
		if (!empty(GETPOST('portique', 'int'))) {
			$dataintervenant->portique = GETPOST('portique', 'int');
		}
		if (!empty(GETPOST('description', 'text'))) {
			$dataintervenant->description = GETPOST('description', 'text');
		}
		$result = $dataintervenant->create($user);
	
		if ($result >= 0) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		} 
		else {
			setEventMessages($langs->trans($object->error), null, 'errors');
			$error++;
		}
	}
}

if (($action == 'updateline') && !$cancel && $user->id == $user_fiche->id) { // Update ligne du tableau
	$error = 0;

	if (!GETPOST("timelinemonth") && !GETPOST("timelineday") && !GETPOST("timelineyear")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}
	$date = dol_mktime(-1, -1, -1, GETPOST("timelinemonth", 'int'), GETPOST("timelineday", 'int'), GETPOST("timelineyear", 'int'));

	if (!GETPOST("dose")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}

	if (0>GETPOST("dose", "double") || 9.999<GETPOST("dose", "double")) {
		setEventMessages($langs->trans('ErrorFormat', $langs->transnoentitiesnoconv("Dose")), null, 'errors');
		$error++;
	}

	$object->fetch($fodid);
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
		if($lineid > 0){
			$now = dol_now();

			$dataintervenant->fetch($lineid);
			$dataintervenant->dose = GETPOST("dose", 'double');
			$dataintervenant->niv_contamination = GETPOST("niv_contamination", 'int');
			$dataintervenant->portique = GETPOST("portique", 'int');
			$dataintervenant->description = GETPOST("description", "text");
			$dataintervenant->date = $date;
			$dataintervenant->fk_user = $userid;
			$dataintervenant->tms = $db->idate($now);
			$dataintervenant->fk_user_modif = $user->id;
			
			$result = $dataintervenant->update($user);

			if ($result >= 0) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans($dataintervenant->error), null, 'errors');
				$error++;
			}
		}
		else {
			setEventMessages($langs->trans($dataintervenant->error), null, 'errors');
		}
	} 
	else {
		$action = '';
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->id == $user_fiche->id) { // Supprimer ligne du tableau
	$dataintervenant->fetch($lineid);
	$result = $dataintervenant->delete($user);

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

if($userid > 0){
	$cat_med = $user_fiche->array_options['options_cat_med'];

	$res = $user_fiche->fetch_optionals();
	// Check if user has rights
	if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$user_fiche->getrights();
		if (empty($user_fiche->nb_rights) && $user_fiche->statut != 0 && empty($user_fiche->admin)) {
			setEventMessages($langs->trans('UserHasNoPermissions'), null, 'warnings');
		}
	}

	$objecttmp = $object;
	$object = $user_fiche;
	$head = user_prepare_head($user_fiche);
	$object = $objecttmp;
	print dol_get_fiche_head($head, 'FOD2', $langs->trans("User"), -1, 'user');
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	
	if($user->rights->user->user->lire || $user->admin){
		dol_banner_tab($user_fiche, 'userid', $linkback, $user->rights->user->user->lire || $user->admin);
	}
	else {
		dol_banner_tab($user_fiche, 'userid', '', 0);
	}

	if($user->id != $userid && $user->admin == 0 && !$user->rights->fod->dataintervenant->readAll){
		accessforbidden("Vous n'êtes pas autorisé à voir les données de cette utilisateur", 0, 0, 1);
	}
	else {
		if($cat_med > 0 && $cat_med != 3){
			/*print '<div style="text-align: center;padding-top: 30px;">';
				print '<div class="btn-link" style="width: 280px;display: inline-block;height: 20px;">';
					print 'Contraite de dose annuelle : '.$user_fiche->getCdD(). ' mSv';
				print '</div>';
			print '</div>';*/

			print '<div style="text-align: center;padding-top: 30px;">';
			$pourcentage = ($user_fiche->getDose12mois()/$user_fiche->getCdD())*100;
			if (($user_fiche->getDose12mois() >= round($user_fiche->getCdD() * ($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && ($user_fiche->getDose12mois() <= $user_fiche->getCdD())){
				$value = 'ATTENTION : Risque de dépassement de la CdD annuelle  ('.$user_fiche->getCdD().' mSv), prendre les dispositions pour la respecter ('.round($pourcentage, 1).'%)';
				print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose 12 derniers mois : '.number_format($user_fiche->getDose12mois(),3).' mSv</span></td>';
			}
			else if ($user_fiche->getDose12mois() > $user_fiche->getCdD()){
				$value = 'ATTENTION : Dépassement de la CdD annuelle ('.$user_fiche->getCdD().' mSv), autorisation accès en ZC suspendue ('.round($pourcentage, 1).'%)';
				print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose 12 derniers mois : '.number_format($user_fiche->getDose12mois(),3).' mSv</span></td>';
			}
			else {
				$value = round($pourcentage, 1).'% de la CdD annuelle ('.$user_fiche->getCdD().' mSv)';
				print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status4 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose 12 derniers mois : '.number_format($user_fiche->getDose12mois(),3).' mSv</span></td>';
			}
			print '</div>';
			
			// Liste des FOD active de l'utilisateur 
			$listFod = $user_fiche->getListFod($user_fiche);
			if(!empty($listFod)){
				$i = 0;
				print '</div>';
				print '<div class="tabs" data-role="controlgroup" data-type="horizontal">';
					print '<a class="tabTitle">';
						print '<span class="fas fa-fod imgTabTitle em120 infobox-fod" style="" title="'.$langs->trans("Fod").'"></span>' ;
						print '<span class="tabTitleText">FOD</span>';
					print '</a>';
					foreach($listFod as $idfod){
						$object->fetch($idfod);

						if($object->debit_dose_estime >= $conf->global->FODOrange_PARAM1 || $object->debit_dose_max >= $conf->global->FODOrange_PARAM2) {
							$fod_orange =  'OUI';
						}
						else {
							$fod_orange = 'NON';
						}
						
						$link = $_SERVER['PHP_SELF'].'?userid='.$userid.'&fodid='.$idfod;
							if($idfod == GETPOST('fodid')){
								print '<div class="inline-block tabsElem tabsElemActive">';
								print '<div class="tab tabactive" style="margin: 0 !important">';
							}
							else {
								print '<div class="inline-block tabsElem">';
								print '<div class="tab tabunactive" style="margin: 0 !important">';
							}
							print '<a id="fod" class="tab inline-block" href="'.$link.'">'.$object->ref;
							$text = '<strong>Etat installation : </strong>'.$object->fields['etat_installation']['arrayofkeyval'][$object->etat_installation].'<br><strong>Activité : </strong>'.$object->activite.'<br><strong>FOD Orange : </strong>'.$fod_orange;
							print " ".$form->textwithpicto('', $text);
							print '</a>';
							print '</div>';
						print '</div>';
					}
				print '</div>';


				// Si on a selectionné une fod, afficher les infos + le tableau de donnée
				if(!empty(GETPOST('fodid'))){
					$idfod = GETPOST('fodid');
					$object->fetch($idfod);

					/// Affichage banière + données de la FOD 
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
					echo '<br/>'."\r\n";

					// Bouton + pour ajouter une ligne de donnée
					$linktocreatedataBtnStatus = 0;
					$linktocreatedataUrl = '';
					$linktocreatedataHelpText = '';
					if ($user->id == $user_fiche->id) {	
							$linktocreatedataBtnStatus = 1;
							$backtourl = $_SERVER['PHP_SELF'].'?userid='.$userid;
							$linktocreatedataUrl = $_SERVER['PHP_SELF'].'?userid='.$userid.'&fodid='.$object->id.'&action=createdata'.$param.'&backtopage='.urlencode($backtourl);
					}
					$linktocreatedata = dolGetButtonTitle($langs->trans('Ajouter'), $linktocreatedataHelpText, 'fa fa-plus-circle', $linktocreatedataUrl, '', $linktocreatedataBtnStatus);

					// Affiche une fenêtre quand on supprime une ligne
					if ($action == 'deleteline') {
						print $form->formconfirm($_SERVER["PHP_SELF"]."?"."userid=".$userid.'&fodid='.$fodid.'&lineid='.$lineid, $langs->trans("DeleteAdataintervenant"), $langs->trans("ConfirmDeleteAdataintervenant"), "confirm_delete", '', '', 1);
					}

					// Definition of fields for list
					$arrayfields = array();
					$arrayfields['d.date'] = array('label'=>$langs->trans("Date"), 'checked'=>1);
					$arrayfields['d.fk_user'] = array('label'=>$langs->trans("By"), 'checked'=>1);
					$arrayfields['d.dose'] = array('label'=>$langs->trans("Dose"), 'checked'=>1);
					$arrayfields['d.niv_contamination'] = array('label'=>$langs->trans("NiveauContamination"), 'checked'=>1);
					$arrayfields['d.portique'] = array('label'=>$langs->trans("Portique"), 'checked'=>1);
					$arrayfields['d.description'] = array('label'=>$langs->trans("Note"), 'checked'=>1);

					// param pour la recherche croissante et decroissante
					$param = '';
					if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
						$param .= '&contextpage='.urlencode($contextpage);
					}
					if ($limit > 0 && $limit != $conf->liste_limit) {
						$param .= '&limit='.urlencode($limit);
					}
					if ($search_month > 0) {
						$param .= '&search_month='.urlencode($search_month);
					}
					if ($search_year > 0) {
						$param .= '&search_year='.urlencode($search_year);
					}
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
					if ($userid) {
						$param .= '&userid='.urlencode($userid);
					}
					if (!empty($fodid)) {
						$param .= '&fodid='.urlencode($fodid);
					}

					print '</div>';
					print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="padding-bottom: 75px;">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
					if ($action == 'editline' && $user->id == $user_fiche->id) {
						print '<input type="hidden" name="action" value="updateline">';
					} 
					elseif ($action == 'createdata' && !empty($fodid) && $user->id == $user_fiche->id) {
						print '<input type="hidden" name="action" value="adddataintervenant">';
					}
					else {
						print '<input type="hidden" name="action" value="list">';
					}
					print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
					print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
					print '<input type="hidden" name="userid" value="'.$userid.'">';
					print '<input type="hidden" name="fodid" value="'.$fodid.'">';


					// Liste des données de l'intervenant
					$data = array();

					$sql = "SELECT d.rowid, d.date, d.fk_fod, d.fk_user, d.dose, d.niv_contamination, d.portique, d.description,";
					$sql .= " u.lastname, u.firstname, u.login, u.photo, u.statut as user_status";
					$sql .= " FROM ".MAIN_DB_PREFIX."fod_dataintervenant as d,";
					$sql .= " ".MAIN_DB_PREFIX."user as u";
					$sql .= " WHERE d.fk_user = u.rowid AND d.fk_fod = ".$object->id;
					$sql .= " AND d.fk_user = ".$userid;
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
						$sql .= dolSqlDateFilter('d.date', $search_day, $search_month, $search_year);
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



					// Affichage DC avant tableau 
					if($object->dc_optimise == 1) {
						$dc = $object->GetDoseCollectivePrevisionnelleOptimise();
					}
					else {
						$dc = $object->GetDoseCollectivePrevisionnelle();
					}
					$pourcentage = ($object->GetDoseCollectiveReel()/$dc)*100;
					print '<div style="text-align: center;padding-top: 30px;">';
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
					print '<br><br>';

					// Affichage de la DI avant le tableau 
					$pourcentage = ($user_fiche->getDoseFod($object)/$user_fiche->getDoseMaxFod($object))*100;
					if (($user_fiche->getDoseFod($object) >= round($user_fiche->getDoseMaxFod($object) * ($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && ($user_fiche->getDoseFod($object) <= $user_fiche->getDoseMaxFod($object))){
						$value = 'ATTENTION : Risque de dépassement de la CdD, prendre les dispositions pour la respecter ('.round($pourcentage, 1).'%)';
						print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose enregistrée pour la FOD : '.number_format($user_fiche->getDoseFod($object), 3)." mSv</span><strong> / ".$user_fiche->getDoseMaxFod($object). " mSv (CdD de l'intéréssé)</strong></td>";
					}
					else if ($user_fiche->getDoseFod($object) > $user_fiche->getDoseMaxFod($object)){
						$value = 'ATTENTION : Dépassement de la CdD, autorisation accès en ZC suspendue ('.round($pourcentage, 1).'%)';
						print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status8 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose enregistrée pour la FOD : '.number_format($user_fiche->getDoseFod($object), 3)." mSv</span><strong> / ".$user_fiche->getDoseMaxFod($object). " mSv (CdD de l'intéréssé)</strong></td>";
					}
					else {
						$value = round($pourcentage, 1).'% de la CdD';
						print '<td class="left" style="padding-left: 0px;"><span class="badge  badge-status4 badge-status" title="'.$value.'" style="padding-top: 0.40em;padding-bottom: 0.40em;">'.'Dose enregistrée pour la FOD : '.number_format($user_fiche->getDoseFod($object), 3)." mSv</span><strong> / ".$user_fiche->getDoseMaxFod($object). " mSv (CdD de l'intéréssé)</strong></td>";
					}
					print '</div>';




					if ($num >= 0) {
						print '<!-- List of data for FOD -->'."\n";
						$title = $langs->trans("ListDataIntervenantForFod");
						print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, './img/radioactif.png', 1, $linktocreatedata, '', $limit, 0, 0, 1);

						$i = 0;
						while ($i < $num) {
							$row = $db->fetch_object($resql);
							$data[$i] = $row;					
							$i++;
						}
						$db->free($resql);
					} 
					else {
						prinprint($db);
					}

					/*
					* Form to add a new data
					*/
					if ($action == 'createdata' && !empty($fodid) && $user_fiche->id == $user->id) { // Ajouter une ligne
						if ($object->id == $fodid){
							print '<!-- table to add data -->'."\n";
							print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
							print '<table class="noborder nohover centpercent">';			

							print '<tr class="liste_titre">';
							print '<td>'.$langs->trans("Date").'</td>';
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

							// Dose
							print '<td class="nowrap">';
							print $dataintervenant->showInputField($dataintervenant->fields['dose'], 'dose', '', '', '', '', 0);
							print ' mSv';
							print '</td>';
								
							// Niveau contamination 
							print '<td class="nowrap">';
							print $dataintervenant->showInputField($dataintervenant->fields['niv_contamination'], 'niv_contamination', '', '', '', '', 0);
							print '</td>';

							// Portique
							print '<td class="nowrap">';
							print $dataintervenant->showInputField($dataintervenant->fields['portique'], 'portique', '', '', '', '', 0);
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
						if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
							print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
						}
						print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
						print $formother->selectyear($search_year, 'search_year', 1, 20, 5);
						print '</td>';
					}
					// Dose
					if (!empty($arrayfields['d.dose']['checked'])) {
						print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_dose" value="'.dol_escape_htmltag($search_dose).'"></td>';
					}
					// Niveau contamination
					if (!empty($arrayfields['d.niv_contamination']['checked'])) {
						$para = 'value="'.dol_escape_htmltag($search_niv_contamination).'"';
						print '<td class="liste_titre">'.$dataintervenant->showInputField($dataintervenant->fields['niv_contamination'], 'niv_contamination', $search_niv_contamination, $para, '', 'search_', 0).'</td>';
					}
					// Portique
					if (!empty($arrayfields['d.portique']['checked'])) {
						$para = 'value="'.dol_escape_htmltag($search_portique).'"';
						print '<td class="liste_titre">'.$dataintervenant->showInputField($dataintervenant->fields['portique'], 'portique', $search_portique, $para, '', 'search_', 0).'</td>';
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
					
					foreach ($data as $data_line) {
						if ($i >= $limit) {
							break;
						}

						$res = $dataintervenant->fetch($data_line->rowid);
						print '<tr class="oddeven">';

						// Date
						$date = $db->jdate($data_line->date);
						if (!empty($arrayfields['d.date']['checked'])) {
							print '<td class="nowrap">';
							if ($action == 'editline' && $_GET['lineid'] == $data_line->rowid) {
								print $form->selectDate($date, 'timeline', 0, 0, 2, "data_date", 1, 0);
							} 
							else {
								print dol_print_date($date, 'day');
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
								print $dataintervenant->showInputField($dataintervenant->fields['niv_contamination'], 'niv_contamination', $data_line->niv_contamination, '', '', '', 0);
							} 
							else {
								print $dataintervenant->fields['niv_contamination']['arrayofkeyval'][$data_line->niv_contamination];
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
								print $dataintervenant->showInputField($dataintervenant->fields['portique'], 'portique', $data_line->portique, '', '', '', 0);
							} 
							else {
								print $dataintervenant->fields['portique']['arrayofkeyval'][$data_line->portique];
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
							}
							else {
								print $data_line->description;
							}
							print '</td>';
							if (!$i) {
								$totalarray['nbfield']++;
							}
						}
						elseif ($action == 'editline' && $_GET['lineid'] == $data_line->description) {
							print '<input type="hidden" name="description" value="'.$data_line->description.'">';
						}

						// Action column
						print '<td class="center nowraponall">';
						if (($action == 'editline') && $_GET['lineid'] == $data_line->rowid && $user->id == $user_fiche->id) {
							print '<input type="hidden" name="lineid" value="'.$_GET['lineid'].'">';
							print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-save" name="save" value="'.$langs->trans("Save").'">';
							print '<br>';
							print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
						} 
						elseif ($user->id == $user_fiche->id) {	 // Read project and enter time consumed on assigned tasks
							if ($data_line->fk_user == $user->id) {
								print '&nbsp;';
								print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editline&amp;lineid='.$data_line->rowid.$param.'">';
								print img_edit();
								print '</a>';

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
								} 
								else {
									print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
								}
							} 
							elseif ($totalarray['totaldosefield'] == $i) {
								print '<td class="left">'.round($totalarray['totaldose'], 3).' mSv'.'</td>';
							}
							else {
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
				else {
					print '<div class="tabBar"></div>';
				}

				print "</div>";
			}
			else {
				accessforbidden("Aucune FOD active que vous avez prise en compte", 0, 0, 1);
			}
		}
		else {
			accessforbidden("Vous n'êtes pas catégorisés", 0, 0, 1);
		}
	}
	
}

// End of page
llxFooter();
$db->close();

