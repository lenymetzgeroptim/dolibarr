<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *  \file       autorisation_prerequis.php
 *  \ingroup    formationhabilitation
 *  \brief      Tab for prerequis on Autorisation
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

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

dol_include_once('/formationhabilitation/class/autorisation.class.php');
dol_include_once('/formationhabilitation/lib/formationhabilitation_autorisation.lib.php');
dol_include_once('/formationhabilitation/class/elementprerequis.class.php');

// Load translation files required by the page
$langs->loadLangs(array("formationhabilitation@formationhabilitation", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$lineid_formation = GETPOST('lineid_formation', 'int');
$lineid_nature_visite = GETPOST('lineid_nature_visite', 'int');
$lineid_autre = GETPOST('lineid_autre', 'int');
$condition_group = GETPOST('condition_group', 'int');
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation

// Initialize technical objects
$object = new Autorisation($db);
$extrafields = new ExtraFields($db);
$elementPrerequis = new ElementPrerequis($db);
$diroutputmassaction = $conf->formationhabilitation->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('autorisationprerequis', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->formationhabilitation->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
}


// There is several ways to check permission.
$permissiontoread = $user->rights->formationhabilitation->habilitation_autorisation->read;
$permissiontoadd = $user->rights->formationhabilitation->habilitation_autorisation->write;


// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->formationhabilitation->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	if($action == 'addline' && $object->id > 0 && $permissiontoadd) {
		$db->begin();

		if(!(GETPOST('prerequisobjects_formation')) && !(GETPOST('prerequisobjects_nature_visite')) && !(GETPOST('prerequisobjects_autre'))){
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("PrerequisObjects")), null, 'errors');
			$error++;
		}
	
		$elementPrerequis->sourcetype = $object->element;
		$elementPrerequis->fk_source = $object->id;
		$elementPrerequis->condition_group = $elementPrerequis->getNextConditionGroup($object->id, $object->element);

		if (!$error && !$errorcreate) { // Prérequis de formation
			if(sizeof(GETPOST('prerequisobjects_formation', 'array')) > 0) {
				$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_formation', 'array'));
				$elementPrerequis->prerequistype = 'formation';

				$resultcreate = $elementPrerequis->create($user);

				if($resultcreate < 0 || $elementPrerequis->condition_group < 1) {
					setEventMessages("Erreur lors de la création du prérequis de formation", null, 'errors');
					$errorcreate++;
				} 
			}
		}

		if (!$error && !$errorcreate) { // Prérequis de nature de visite
			if(sizeof(GETPOST('prerequisobjects_nature_visite', 'array')) > 0) {
				$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_nature_visite', 'array'));
				$elementPrerequis->prerequistype = 'nature_visite';

				$resultcreate = $elementPrerequis->create($user);

				if($resultcreate < 0 || $elementPrerequis->condition_group < 1) {
					setEventMessages("Erreur lors de la création du prérequis de nature de visite", null, 'errors');
					$errorcreate++;
				} 
			}
		}

		if (!$error && !$errorcreate) { // Prérequis de nature de visite
			if(sizeof(GETPOST('prerequisobjects_autre', 'array')) > 0) {
				$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_autre', 'array'));
				$elementPrerequis->prerequistype = 'autre';

				$resultcreate = $elementPrerequis->create($user);

				if($resultcreate < 0 || $elementPrerequis->condition_group < 1) {
					setEventMessages("Erreur lors de la création du prérequis autre", null, 'errors');
					$errorcreate++;
				} 
			}
		}
	
		if(!$error && $resultcreate > 0){
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		elseif(!$error && $resultcreate < 0){
			$db->rollback();
			setEventMessages($langs->trans($objectline->error), null, 'errors');
		}
		elseif($errorcreate){
			$db->rollback();
		}
	}

	if($action == 'updateline' && !$cancel && $permissiontoadd){
		if($lineid_formation > 0 || $lineid_nature_visite > 0 || $lineid_autre > 0){
			if(!(GETPOST('prerequisobjects_formation')) && !(GETPOST('prerequisobjects_nature_visite')) && !(GETPOST('prerequisobjects_autre'))){
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("PrerequisObjects")), null, 'errors');
				$error++;
			}

			// Prérequis de formation
			if(!$error && $lineid_formation > 0 && !GETPOST('prerequisobjects_formation', 'array')) {
				$elementPrerequis->fetch($lineid_formation);
				$resultdelete = $elementPrerequis->delete($user);

				if($resultdelete < 0) {
					setEventMessages("Erreur lors de la suppression des prérequis de formation", null, 'errors');
					$error++;
				}
				
			}
			elseif(!$error && $lineid_formation > 0) {
				$elementPrerequis->fetch($lineid_formation);

				if ($elementPrerequis->prerequisobjects != implode(',', GETPOST('prerequisobjects_formation', 'array'))) {
					$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_formation', 'array'));
					$resultupdate = $elementPrerequis->update($user);

					if($resultupdate < 0) {
						setEventMessages("Erreur lors de la modification des prérequis de formation", null, 'errors');
						$error++;
					}
				}
			}
			elseif(!$error && GETPOST('prerequisobjects_formation', 'array')) {
				$elementPrerequis->sourcetype = $object->element;
				$elementPrerequis->fk_source = $object->id;
				$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_formation', 'array'));
				$elementPrerequis->prerequistype = 'formation';
				$elementPrerequis->condition_group = $condition_group;

				$resultcreate = $elementPrerequis->create($user);

				if($resultcreate < 0) {
					setEventMessages("Erreur lors de la création des prérequis de formation", null, 'errors');
					$error++;
				}
			}

			// Prérequis de nature de visite
			if(!$error && $lineid_nature_visite > 0 && !GETPOST('prerequisobjects_nature_visite', 'array')) {
				$elementPrerequis->fetch($lineid_nature_visite);
				$resultdelete = $elementPrerequis->delete($user);

				if($resultdelete < 0) {
					setEventMessages("Erreur lors de la suppression des prérequis de nature de visite", null, 'errors');
					$error++;
				}
				
			}
			elseif(!$error && $lineid_nature_visite > 0) {
				$elementPrerequis->fetch($lineid_nature_visite);

				if ($elementPrerequis->prerequisobjects != implode(',', GETPOST('prerequisobjects_nature_visite', 'array'))) {
					$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_nature_visite', 'array'));
					$resultupdate = $elementPrerequis->update($user);

					if($resultupdate < 0) {
						setEventMessages("Erreur lors de la modification des prérequis de nature de visite", null, 'errors');
						$error++;
					}
				}
			}
			elseif(!$error && GETPOST('prerequisobjects_nature_visite', 'array')) {
				$elementPrerequis->sourcetype = $object->element;
				$elementPrerequis->fk_source = $object->id;
				$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_nature_visite', 'array'));
				$elementPrerequis->prerequistype = 'nature_visite';
				$elementPrerequis->condition_group = $condition_group;

				$resultcreate = $elementPrerequis->create($user);

				if($resultcreate < 0) {
					setEventMessages("Erreur lors de la création des prérequis de nature de visite", null, 'errors');
					$error++;
				}
			}

			// Prérequis autres
			if(!$error && $lineid_autre > 0 && !GETPOST('prerequisobjects_autre', 'array')) {
				$elementPrerequis->fetch($lineid_autre);
				$resultdelete = $elementPrerequis->delete($user);

				if($resultdelete < 0) {
					setEventMessages("Erreur lors de la suppression des prérequis autres", null, 'errors');
					$error++;
				}
				
			}
			elseif(!$error && $lineid_autre > 0) {
				$elementPrerequis->fetch($lineid_autre);

				if ($elementPrerequis->prerequisobjects != implode(',', GETPOST('prerequisobjects_autre', 'array'))) {
					$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_autre', 'array'));
					$resultupdate = $elementPrerequis->update($user);

					if($resultupdate < 0) {
						setEventMessages("Erreur lors de la modification des prérequis autres", null, 'errors');
						$error++;
					}
				}
			}
			elseif(!$error && GETPOST('prerequisobjects_autre', 'array')) {
				$elementPrerequis->sourcetype = $object->element;
				$elementPrerequis->fk_source = $object->id;
				$elementPrerequis->prerequisobjects = implode(',', GETPOST('prerequisobjects_autre', 'array'));
				$elementPrerequis->prerequistype = 'autre';
				$elementPrerequis->condition_group = $condition_group;

				$resultcreate = $elementPrerequis->create($user);

				if($resultcreate < 0) {
					setEventMessages("Erreur lors de la création des prérequis autre", null, 'errors');
					$error++;
				}
			}
	
			if(!$error){
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id, true, 303);
				exit;
			}
			elseif($error) {
				header('Location: '.$_SERVER["PHP_SELF"].'?'.$object->id.'&action=editline&lineid_formation='.$lineid_formation.'&lineid_nature_visite='.$lineid_nature_visite.'&lineid_autre='.$lineid_autre);
				exit;
			}
		}
		else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}

	if ($action == 'confirm_deleteline' && ($lineid_formation > 0 || $lineid_nature_visite > 0 || $lineid_autre > 0) && $confirm == 'yes' && $permissiontoadd) {
		if($lineid_formation > 0) {
			$elementPrerequis->fetch($lineid_formation);
			$resultdelete = $elementPrerequis->delete($user);
			
			if($resultdelete < 0) {
				$error++;
			}
		}

		if($lineid_nature_visite > 0) {
			$elementPrerequis->fetch($lineid_nature_visite);
			$resultdelete = $elementPrerequis->delete($user);

			if($resultdelete < 0) {
				$error++;
			}
		}

		if($lineid_autre > 0) {
			$elementPrerequis->fetch($lineid_autre);
			$resultdelete = $elementPrerequis->delete($user);

			if($resultdelete < 0) {
				$error++;
			}
		}
		
		if($error) {
			setEventMessages($elementPrerequis->error, $elementPrerequis->errors, 'errors');
		}
		elseif ($resultdelete > 0) {
			setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} 
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('Autorisation'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = autorisationPrepareHead($object);

	print dol_get_fiche_head($head, 'prerequis', $langs->trans("Autorisation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid_formation='.$lineid_formation.'&lineid_nature_visite='.$lineid_nature_visite.'&lineid_autre='.$lineid_autre, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/formationhabilitation/autorisation_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->projet->enabled))
	 {
	 $langs->load("projects");
	 $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd)
	 {
	 if ($action != 'classify')
	 //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	 $morehtmlref.=' : ';
	 if ($action == 'classify') {
	 //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref.='<input type="hidden" name="action" value="classin">';
	 $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref.='</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	 $morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div><br>';


	//$cssclass = "titlefield";
	include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/elementprerequis_list.php';

	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
