<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2021 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012-2018 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2017 Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2015-2018 Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2016      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2021  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2018       David Beniamine     <David.Beniamine@Tetras-Libre.fr>
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
 *       \file       htdocs/user/card.php
 *       \brief      Tab of user card
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
	$i--;
	$j--;
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

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
if (!empty($conf->ldap->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
}
if (isModEnabled('adherent')) {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
}
if (isModEnabled('categorie')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}
if (isModEnabled('stock')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/ongletdonneesrh.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/feuilledetemps.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/observationcompta.class.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedexport.class.php';


// Load translation files required by page
$langs->loadLangs(array('users', 'companies', 'ldap', 'admin', 'hrm', 'stocks', 'other'));

$id = GETPOST('id', 'int');
$id_onglet = GETPOST('onglet', 'int');
$action		= GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$group = GETPOST("group", "int", 3);
$cancel		= GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'useracard'; // To manage different context of search
$childids = $user->getAllChildIds(1);	// For later, test on salary visibility
$lineid   = GETPOST('lineid', 'int');

$object = new User($db);
$userField = new UserField($db);
$extrafields = new ExtraFields($db);
$object_static = new OngletDonneesRH($db);
$usergroup = new UserGroup($db);

$socialnetworks = getArrayOfSocialNetworks();

$error = 0;

if ($id > 0) {
	$res = $object->fetch($id, '', '', 1);

	if($id_onglet > 0) {
		$object_static->fetch($id_onglet);
		$table_element = 'donneesrh_'.$object_static->ref;
		$element_type = $table_element;
		$extrafields->fetch_name_optionals_label($table_element, false);
		$userField->table_element = $table_element;
	}

	$userField->id = $id;
	$userField->fetch_optionals();
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = 'user';
$result = restrictedArea($user, 'user', $id, 'user', $feature2);

// Define value to know what current user can do on users
$userIsInViewGroups = 0;
$userIsInEditGroups = 0;

if($id_onglet > 0) {
	$list_groups_user = array_keys($usergroup->listGroupsForUser($user->id));
	
	$liste_groupes_auth_view = explode(',', $object_static->groupes_view);
	foreach($liste_groupes_auth_view as $key => $id_groupe) {
		if(in_array($id_groupe, $list_groups_user)) {
			$userIsInViewGroups = 1;
		}
	}

	$liste_groupes_auth_edit = explode(',', $object_static->groupes_edit);
	foreach($liste_groupes_auth_edit as $key => $id_groupe) {
		if(in_array($id_groupe, $list_groups_user)) {
			$userIsInEditGroups = 1;
		}
	}
}

$canreaduser = (!empty($user->admin) || $userIsInViewGroups);
$canedituser = (!empty($user->admin) || $userIsInEditGroups);	// edit other user

if (($user->id != $id && !$user->rights->user->user->lire)) {
	accessforbidden();
}

/**
 * Actions
 */

$backurlforlist = DOL_URL_ROOT.'/user/list.php';

if (empty($backtopage) || ($cancel && empty($id))) {
	if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
		if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
			$backtopage = $backurlforlist;
		} else {
			$backtopage = DOL_URL_ROOT.'/custom/donneesrh/user_donneesrh.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__').((!empty($id_onglet) && $id_onglet > 0) ? '&onglet='.$id_onglet : '');
		}
	}
}

if ($cancel) {
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = '';
}

if ($action == 'update' && $canedituser) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$object->fetch($id);

	$object->oldcopy = clone $object;

	$db->begin();

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $userField, '@GETPOSTISSET', 0);
	
	if ($ret < 0) {
		$error++;
	}

	if (!$error) {
		$ret = $userField->insertExtraFields();		// This may include call to setPassword if password has changed
		if ($ret < 0) {
			$error++;
			if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorLoginAlreadyExists", $object->login), null, 'errors');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'edit';
			}
		}
	}

	if (!$error && !count($object->errors)) {
		setEventMessages($langs->trans("UserModified"), null, 'mesgs');
		$db->commit();
	} else {
		$db->rollback();
	}
	
}

if($id_onglet == 9999) {
	if($action == 'updateline' && !$cancel && $user->rights->feuilledetemps->feuilledetemps->modify_verification){
		if($lineid > 0 && $id > 0){
			$objectline = new ObservationCompta($db);
			$objectline->fetch($lineid);


			if (empty(GETPOST("date_startmonth", 'int')) || empty(GETPOST("date_startyear", 'int'))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateStart")), null, 'errors');
				$error++;
			}
			$date_start = dol_mktime(-1, -1, -1, GETPOST("date_startmonth", 'int'), 1, GETPOST("date_startyear", 'int'));

			if (empty(GETPOST("date_endmonth", 'int')) || empty(GETPOST("date_endyear", 'int'))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateEnd")), null, 'errors');
				$error++;
			}
			$date_end = dol_get_last_day(GETPOST("date_endyear", 'int'), GETPOST("date_endmonth", 'int'));

			if($date_end < $date_start) {
				setEventMessages('La date de fin ne peut pas être inferieur à la date de début', null, 'errors');
				$error++;
			}

			if(!($id > 0)){
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
				$error++;
			}

			if(empty(GETPOST('observation'))){
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Observation")), null, 'errors');
				$error++;
			}

			if(!(GETPOST('type') > 0)){
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
			}

			if (!$error) {
				$objectline->date_start = $date_start;
				$objectline->date_end = $date_end;
				$objectline->fk_user = $id;
				$objectline->observation = GETPOST('observation');
				$objectline->type = GETPOST('type');

				$result = $objectline->update($user);
			}

			if(!$error && $result){
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=9999');
				exit;
			}
			elseif(!$result){
				setEventMessages($langs->trans($objectline->error), null, 'errors');
			}
		}
		else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}

	if($action == 'addline' && $user->rights->feuilledetemps->feuilledetemps->modify_verification){
		if($id > 0){
			if (empty(GETPOST("date_startmonth", 'int')) || empty(GETPOST("date_startyear", 'int'))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateStart")), null, 'errors');
				$error++;
			}
			$date_start = dol_mktime(-1, -1, -1, GETPOST("date_startmonth", 'int'), 1, GETPOST("date_startyear", 'int'));

			if (empty(GETPOST("date_endmonth", 'int')) || empty(GETPOST("date_endyear", 'int'))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateEnd")), null, 'errors');
				$error++;
			}
			$date_end = dol_get_last_day(GETPOST("date_endyear", 'int'), GETPOST("date_endmonth", 'int'));

			if($date_end < $date_start) {
				setEventMessages('La date de fin ne peut pas être inferieur à la date de début', null, 'errors');
				$error++;
			}

			if(!($id > 0)){
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
				$error++;
			}
			
			if(empty(GETPOST('observation'))){
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Observation")), null, 'errors');
				$error++;
			}

			if(!(GETPOST('type') > 0)){
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
			}

			if (!$error) {
				$objectline = new ObservationCompta($db);

				$objectline->date_start = $date_start;
				$objectline->date_end = $date_end;
				$objectline->fk_user = $id;
				$objectline->observation = GETPOST('observation');
				$objectline->type = GETPOST('type');

				$result = $objectline->create($user);
			}

			if(!$error && $result){
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=9999');
				exit;
			}
			elseif(!$result){
				setEventMessages($langs->trans($objectline->error), null, 'errors');
			}
		}
		else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}

	if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->feuilledetemps->feuilledetemps->modify_verification) {
		$feuilleDeTemps = new FeuilleDeTemps($db);
		$result = $feuilleDeTemps->deleteLine($user, $lineid);
		if ($result > 0) {
			setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=9999');
			exit;
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$formadmin = new FormAdmin($db);
$formfile = new FormFile($db);

$title = $langs->trans("DonneesRH");
$help_url = '';

llxHeader('', $title, $help_url);

// Confirmation to delete line
if ($action == 'deleteline') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=9999&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
}
print $formconfirm;

// View and edit mode
if ($id > 0) {
	$res = $object->fetch($id, '', '', 1);
	if ($res < 0) {
		dol_print_error($db, $object->error);
		exit;
	}
	$res = $object->fetch_optionals();

	// Check if user has rights
	if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$object->getrights();
		if (empty($object->nb_rights) && $object->statut != 0 && empty($object->admin)) {
			setEventMessages($langs->trans('UserHasNoPermissions'), null, 'warnings');
		}
	}

	// Show tabs
	$title = $langs->trans("User");
	$linkback = '';

	if ($user->hasRight("user", "user", "read") || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	$head = user_prepare_head($object);

	/*
	* Fiche en mode visu
	*/
	if ($action != 'edit') {
		print dol_get_fiche_head($head, 'donneesrh', $title, -1, 'user');

		$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'" class="refid">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';

		dol_banner_tab($object, 'id', $linkback, $user->hasRight("user", "user", "read") || $user->admin, 'rowid', 'ref', $morehtmlref);

		print '<div class="fichecenter">';

			print '<div class="tabs" data-role="controlgroup" data-type="horizontal">';
			$list_onglet = $object_static->fetchAll('', '', 0, 0);
			$list_groups_user = array_keys($usergroup->listGroupsForUser($user->id));
			if(count($list_onglet) > 0) {
				$i = 0;
				foreach($list_onglet as $key => $onglet) {
					$can_view_onglet = 0;

					$liste_groupes_auth = explode(',', $onglet->groupes_view);
					foreach($liste_groupes_auth as $key => $id_groupe) {
						if($user->admin || in_array($id_groupe, $list_groups_user)) {
							$can_view_onglet = 1;
						}
					}

					if($can_view_onglet) {
						$link = $_SERVER['PHP_SELF'].'?id='.$object->id.'&onglet='.$onglet->id;

						if($onglet->id == $id_onglet){
							print '<div class="inline-block tabsElem tabsElemActive">';
							print '<div class="tab tabactive" style="margin: 0 !important">';
						}
						else {
							print '<div class="inline-block tabsElem">';
							print '<div class="tab tabunactive" style="margin: 0 !important">';
						}

						print '<a id="donneesRH" class="tab inline-block" href="'.$link.'" style="margin-right: 0px;">'.$onglet->label;
						print '</a>';

						print '</div>';
						print '</div>';
					}
					$i++;
				}

				if($user->rights->feuilledetemps->feuilledetemps->modify_verification) {
					$link = $_SERVER['PHP_SELF'].'?id='.$object->id.'&onglet=9999';

					if($id_onglet == 9999){
						print '<div class="inline-block tabsElem tabsElemActive">';
						print '<div class="tab tabactive" style="margin: 0 !important">';
					}
					else {
						print '<div class="inline-block tabsElem">';
						print '<div class="tab tabunactive" style="margin: 0 !important">';
					}

					print '<a id="fdtCompta" class="tab inline-block" href="'.$link.'" style="margin-right: 0px;">Notes Compta FDT';
					print '</a>';

					print '</div>';
					print '</div>';
				}

				print '</div>';

				print '<div class="tabBar">';

				if($id_onglet == 9999) {
					$feuilleDeTemps = new FeuilleDeTemps($db);
					$feuilleDeTemps->fk_user = $id;
					$result = $feuilleDeTemps->getLinesArray(1);
					
					if (!empty($feuilleDeTemps->table_element_line) && $user->rights->feuilledetemps->feuilledetemps->modify_verification) {	
						print '<form name="addObservationCompta" id="addObservationCompta" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'&onglet=9999'.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">';
						print '<input type="hidden" name="token" value="' . newToken().'">';
						print '<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">';
						print '<input type="hidden" name="mode" value="">';
						print '<input type="hidden" name="page_y" value="">';
					
						if (!empty($conf->use_javascript_ajax)) {
							include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
						}
					
						print '<div class="div-table-responsive-no-min compta">';
						print '<table id="tablelines" class="noborder noshadow" width="100%">';
						if (!empty($feuilleDeTemps->lines)) {
							$feuilleDeTemps->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/custom/feuilledetemps/core/tpl');
						}
					
						// Form to add new line
						if ($action != 'editline' && $action != 'selectlines') {					
							$feuilleDeTemps->formAddObjectLine(1, $mysoc, $soc, '/custom/feuilledetemps/core/tpl');
						}
						
						print '</table>';
						
						print '</div>';
					
						print "</form>\n";
					}
				}
				elseif($id_onglet > 0 && $canreaduser) {
					$middle = round(count($extrafields->attributes[$table_element]['label'])/2);
					$keys = array_keys($extrafields->attributes[$table_element]['label']);
					$keyforbreak = $keys[$middle];
					// Other attributes
					include DOL_DOCUMENT_ROOT.'/custom/donneesrh/core/tpl/extrafields_view.tpl.php';

					print '<div style="clear:both"></div>';

					print dol_get_fiche_end();


					if(!$noproperty) {
						/*
						* Buttons actions
						*/
						print '<div class="tabsAction">';
						
						print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit&token='.newToken().'&onglet='.$id_onglet, '', $canedituser, $params);

						print '</div>';
					}
				}
				else {
					print info_admin($langs->trans("WarningNoTabSelectUser"));
				}
			}
			else {
				print info_admin($langs->trans("WarningNoTabExistUser"));
			}

			print '</div>';

		print "</div>\n";
	
	}

	/*
	* Card in edit mode
	*/
	if ($action == 'edit' && $canedituser) {
		print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="updateuser" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="entity" value="'.$object->entity.'">';
		print '<input type="hidden" name="onglet" value="'.$id_onglet.'">';

		print dol_get_fiche_head($head, 'donneesrh', $title, 0, 'user');

		print '<table class="border centpercent">';
		foreach($extrafields->attributes as $key => $value) {
			if($key == 'user' || $key == 'donneesrh_ongletdonneesrh') {
				continue;
			}
			$userField->table_element = $key;
			print $userField->showOptionals($extrafields, 'edit', null, '', '', 0, 'card');
		}
		print '</table>';

		print dol_get_fiche_end();

		print '<div class="center">';
		print '<input value="'.$langs->trans("Save").'" class="button button-save" type="submit" name="save">';
		print '&nbsp; &nbsp; &nbsp;';
		print '<input value="'.$langs->trans("Cancel").'" class="button button-cancel" type="submit" name="cancel">';
		print '</div>';

		print '</form>';
	}

}

// End of page
llxFooter();
$db->close();
