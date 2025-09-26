<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       causerie_card.php
 *		\ingroup    sse
 *		\brief      Page to create/edit/view causerie
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token).
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
//if (! defined('NOSESSION'))     		     define('NOSESSION', '1');				    // Disable session

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/core/class/html.form.causerie.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieattendance.class.php';

dol_include_once('/sse/class/causerie.class.php');
dol_include_once('/sse/lib/sse_causerie.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("sse@sse", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$save = GETPOST('save', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'causeriecard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOST('lineid', 'int');
$userid = GETPOST('fk_user', 'int');

// Initialize technical objects
$object = new Causerie($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->sse->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('causeriecard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
// if ($enablepermissioncheck) {
// 	$permissiontoread = $user->rights->sse->causerie->read_organisation;
// 	$permissiontoadd = $user->rights->sse->causerie->write_organisation; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
// 	$permissiontodelete = $user->rights->sse->causerie->delete_organisation || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
// 	$permissionnote = $user->rights->sse->causerie->write_organisation; // Used by the include of actions_setnotes.inc.php
// 	$permissiondellink = $user->rights->sse->causerie->write_organisation; // Used by the include of actions_dellink.inc.php
// } else {
// 	$permissiontoread = 1;
// 	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
// 	$permissiontodelete = 1;
// 	$permissionnote = 1;
// 	$permissiondellink = 1;
// }

if ($enablepermissioncheck) {
	$permissiontoread = $user->rights->sse->causerie->read_all;
	$permissiontoreadrestrict = $user->rights->sse->causerie->read;
	$permissiontoadd = $user->rights->sse->causerie->write_causerie; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->rights->sse->causerie->delete_modify_causerie || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionadmin = $user->rights->sse->causerie->admin_causerie || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->rights->sse->causerie->write_causerie; // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->rights->sse->causerie->write_causerie; // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoreadrestrict = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionadmin = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->sse->multidir_output[isset($object->entity) ? $object->entity : 1].'/causerie';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->sse->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();
$userid = GETPOST('user', 'int');
$lastname = GETPOST('lastname');
$firstname = GETPOST('firstname');
$email = GETPOST('email');
$externid = GETPOST('extern', 'int');

//permissions
$writeperms = ($permissionadmin || $user->id == $object->fk_user_creat || $user->id == $object->array_options['options_animateur']);


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/sse/causerie_all_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/sse/causerie_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'SSE_CAUSERIE_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'SSE_CAUSERIE_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_CAUSERIE_TO';
	$trackid = 'causerie'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	if ($action == 'confirm_setanimation' && $confirm == 'yes' && $permissiontoadd) {
		//notifier les animateur (to do)
		//draft card in animation
		$result = $object->setAnimation($user);
		
	if ($result >= 0) {
		// Nothing else done
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
		
	}

	$emargement = new CauserieAttendance($db);
	// $object->updateNbCauserieByUserAndYear();

	// Action validate causerie to be programmed
	if ($action == 'confirm_schedule_talk' && $confirm == 'yes' && $permissiontoadd && $writeperms) {
		$result = $object->schedule_talk($user);
		$result = $emargement->createNewEmargement($object->id);
		
		$object->actionmsg = $langs->transnoentitiesnoconv('CAUSERIE_SCHEDULE_TALKInDolibarr', $object->ref);
		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	// Action validate causerie to be programmed
	if ($action == 'confirm_in_animation' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->inanimation_talk($user);
	
		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}
	
	// Action validate causerie to be realized
	if($action == 'confirm_realized' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->realized_talk($user);
		// $result = $object->updateGoalForCauserie($user);
		$object->actionmsg = $langs->transnoentitiesnoconv('CAUSERIE_REALIZEDInDolibarr', $object->ref);

		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	// Action validate causerie to be closed
	if($action == 'confirm_closed' && $confirm == 'yes' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->closed_talk($user);
		$object->actionmsg = $langs->transnoentitiesnoconv('CAUSERIE_CLOSEDInDolibarr', $object->ref);
	
		if ($result >= 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				// if (method_exists($object, 'generateDocument')) {
				// 	$outputlangs = $langs;
				// 	$newlang = '';
				// 	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				// 		$newlang = GETPOST('lang_id', 'aZ09');
				// 	}
				// 	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
				// 		$newlang = $object->thirdparty->default_lang;
				// 	}
				// 	if (!empty($newlang)) {
				// 		$outputlangs = new Translate("", $conf);
				// 		$outputlangs->setDefaultLang($newlang);
				// 	}
	
				// 	$ret = $object->fetch($id); // Reload to get new records
	
				// 	$model = $object->model_pdf;
	
				// 	$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				// 	if ($retgen < 0) {
				// 		setEventMessages($object->error, $object->errors, 'warnings');
				// 	}
				// }
			}
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	//confirm signature for a user
	if($action == "confirmpresence") {
		if($userid) {
			$result = $emargement->confirm_signature($object->id, $userid, $entity = 0);
		}
		
		if ($result >= 0) {
	
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}


	//confirm signature if user is absent
	if($action == "confirmabsence") {
		if($userid) {
			$result = $emargement->confirm_absence($object->id, $userid, $entity = 0);
		}
		
		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	//confirm signature for an extern user
	if($action == "confirmpresenceextern") {
		if($externid) {
			// $result = $emargement->confirm_signature($object->id, $externid, $entity = 2);
		}

		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}
	

	if ($action == 'adduser' || $action == 'removeuser') {
		if ($userid > 0) {
			// Chargement de l'objet
			$object->fetch($id);
			$object->oldcopy = clone $object;
	
			// Chargement de l'utilisateur
			require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieuser.class.php';
			$edituser = new SSEUser($db);
			$edituser->fetch($userid);
	
			if ($action == 'adduser') {
				// Ajout de l'utilisateur à la causerie
				$result = $object->SetInCauserieGroup($object->id, $userid, $object->entity);
				$object->actionmsg = $langs->transnoentitiesnoconv('USER_CAUSERIE_MODIFYInDolibarr', $userid);
				if($result > 0 && GETPOST('confirm_presence') !== '1') {
					setEventMessages($langs->trans("L'utilisateur a été ajouté avec succès, pensez à confirmer sa présence."), null, 'mesgs');
				}
			}
			if ($action == 'removeuser') {
				// L'utilisateur de la causerie est retiré
				if($result > 0 && $userid !== $object->array_options['options_animateur']) {
					$result = $object->RemoveFromCauserie($object->id, $userid, $object->entity);
				}

				if($result > 0 && $userid == $object->array_options['options_animateur']) {
					setEventMessages($langs->trans("L'animateur ne peut pas être supprimé ici. Pour effectuer cette modification, veuillez le mettre à jour directement dans la carte en haut de la page."), null, 'warnings');
				}elseif($result > 0 && $userid !== $object->array_options['options_animateur']) {
					setEventMessages($langs->trans("L'utilisateur a été supprimer avec succès."), null, 'mesgs');
				}

			}
			
			// Après l'ajout, on vérifie si la case a été cochée pour confirmer la présence
			if ($result > 0 && GETPOSTISSET('confirm_presence') && GETPOST('confirm_presence') == '1') {
				// Appel de la fonction de confirmation de présence
				$result = $emargement->confirm_signature($object->id, $userid, $object->entity);
				// if($object->status >= $object::STATUS_REALIZED) {
				// 	$object->updateNbCauserie();
				// }
	
				if ($result >= 0) {
					// Redirection après confirmation de présence
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
					setEventMessages($langs->trans("L'utilisateur a été ajouté et sa présence a été confirmée avec succès."), null, 'mesgs');
				} else {
					// En cas d'erreur lors de la confirmation de présence
					setEventMessages($emargement->error, $emargement->errors, 'errors');
				}
			}
	
			// Rediriction après l'ajout/suppression de l'utilisateur
			if ($result > 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				// En cas d'erreur
				setEventMessages($edituser->error, $edituser->errors, 'errors');
			}
		}
	}
	
	// Mise à jour des indicateurs dans l'objet.
	$object->updateCounts();
	$object->updateCountPourcentage();

	if ($action == 'addextern' || $action == 'removextern') {
		require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieuser.class.php';
		$editcuser = new CauserieUser($db);
		$editcuser->fetch($externid);
				
		if ($externid > 0) {
		
			if ($action == 'addextern') {
				$result = $editcuser->SetExternInCauserieGroup($object->id, 2);
			}
			if ($action == 'removextern') {
				$result = $editcuser->RemoveExternFromCauserie($object->id, 2);
				if($result > 0) {
					setEventMessages($langs->trans("L'invité a été supprimer avec succès."), null, 'mesgs');
				}
			}
			
			if ($result > 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				setEventMessages($editcuser->error, $editcuser->errors, 'errors');
			}
		}

	}

	if ($action == 'addnewextern') {
		require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieuser.class.php';
		$editcuser = new CauserieUser($db);
		
		if ($action == 'addnewextern') {
		
			$result = $editcuser->SetNewExtern($lastname, $firstname, $email, $object->id);

		}
		if ($result > 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			setEventMessages($editcuser->error, $editcuser->errors, 'errors');
		}
	}

	
}

	print '<script type="text/javascript">setTimeout(function(){
		location.reload();
	}, 60 * 3000);</script>';

require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerieuser.class.php';
global $db;
$editcuser = new CauserieUser($db);


/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formextern = new FormCauserie($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Causerie");
$help_url = '';
llxHeader('', $title, $help_url);

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';

// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden($langs->trans('NotEnoughPermissions'), 0, 1);
		exit;
	}
	
	print load_fiche_titre($langs->trans("Nouvelle Causerie", $langs->transnoentitiesnoconv("Causerie")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');
	
	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';
	print '<table class="border centpercent tableforfieldcreate">'."\n";
	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';
	
	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}


// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Causerie"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';
	

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = causeriePrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Causerie"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteCauserie'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx') {
		$formquestion = array();
		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/sse/causerie_all_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";
	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
	
	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';
	
	print '<table class="border centpercent tableforfield">';
	
	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
	// print '<div>';
	print '</table>';
	
	//print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"><br></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				if (empty($reshook))
					$object->formAddObjectLine(1, $mysoc, $soc);
			}
		}
	
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';
		

		print "</form>\n";
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Exécute le hook pour ajouter des actions personnalisées
	
		// Vérification si un problème est survenu avec le hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}
	
		// Si le hook n'a pas ajouté d'actions supplémentaires, on continue
		if (empty($reshook)) {
			if (empty($user->socid)) {
				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle');
			}
	
			// Si l'objet est en statut supérieur à "réalisé" et que l'utilisateur a les permissions appropriées
			if ($object->status > $object::STATUS_REALIZED && $writeperms && $permissionadmin) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
			} elseif ($object->status <= $object::STATUS_REALIZED && $writeperms) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
			}
	
			// Si l'objet est en statut brouillon et que l'utilisateur peut ajouter et modifier
			if ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $writeperms) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction($langs->trans('Valider la proposition'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_schedule_talk&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}
	
			// Si l'objet est en statut "programmé" et que l'utilisateur a les droits nécessaires
			if ($object->status == $object::STATUS_PROGRAMMED && $permissiontoadd && $writeperms) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction($langs->trans('Confirmer la réalisation'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_realized&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}
	
			// Si l'objet est en statut "réalisé" et que l'utilisateur a les permissions nécessaires
			if ($object->status == $object::STATUS_REALIZED && $permissiontoadd && $writeperms) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction($langs->trans('Close'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_closed&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}
	
			// Si l'objet est en statut "fermé" et que l'utilisateur a les permissions nécessaires
			if ($object->status == $object::STATUS_CLOSED && $permissiontoadd && $writeperms) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					//
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}
	
			// Clone
			print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid)?'&socid='.$object->socid:'').'&action=clone&token='.newToken(), '', $permissiontoadd);
	
			// Supprimer (nécessite la permission de suppression, ou si en brouillon, juste la permission de création/modification)
			if ($object->status > $object::STATUS_INANIMATION && $writeperms && $permissionadmin) {
				print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissionadmin || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
			} elseif ($object->status <= $object::STATUS_INANIMATION && $writeperms) {
				print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
			}
		}
		print '</div>'."\n";
	}
	
	// Information à l'utilisateur 
	print '
	<div class="info-message" style="margin-top: 20px; padding: 15px; border-left: 4px solid #007bff; background-color: #e9f7fe; border-radius: 5px;">
			<p style="font-size: 14px; line-height: 1.6; color: #333;">
				L\'ajout des participants est possible via le formulaire ci-dessous. Une fois que la causerie passe au statut <strong>Réalisé</strong>, les participations seront comptabilisées en fonction de <strong>la date de la causerie</strong> sur un <strong>objectif annuel</strong> prédéfini.<br><br>
				En bas de cette page, vous pouvez gérer individuellement chaque participation (présence ou absence) une fois les participants ajoutés. <br><br>
				<strong>Important :</strong> La programmation d\'une causerie active une demande d\'émargement dans l\'espace utilisateur pour recueillir la signature du participant. Pour activer cette fonctionnalité, décochez la case lors de l\'ajout de l\'utilisateur. À défaut, l\'animateur pourra confirmer la présence sans signature directement depuis son espace.
			</p>
		</div>
	';

	print '<br>';


	$user_unsigned = array();
	$user_signed = array();
	$user_signature_confirmed = array();
	$arr_emargement = $object->getEmargementList();
	
	foreach($arr_emargement as $in_emargement) {
		$emargement->fetch($in_emargement);
	
		if($emargement->status == $emargement::STATUS_UNSIGNED) {
			//Modified $user user_unsigned
			$user_unsigned[] = $emargement->fk_user;
			$emargement_unsigned = $emargement->getLibStatut(5);
			// $emargement_signed = $emargement->getLibStatut(5);
		}else if($emargement->status == $emargement::STATUS_SIGNED) {
			$user_signed[] = $emargement->fk_user;
			$emargement_signed = $emargement->getLibStatut(5);
		}else if($emargement->status == $emargement::STATUS_CONFIRM) {
			$user_signature_confirmed[] = $emargement->fk_user;
			$emargement_confirmed = $emargement->getLibStatut(5);
		}else if($emargement->status == $emargement::STATUS_ABSENT) {
			$user_signature_absent[] = $emargement->fk_user;
			$user_type_abs[$emargement->fk_user] = $emargement->type_abs;
			$emargement_absent = $emargement->getLibStatut(5);
		}
	}

	$user_signed = array_unique($user_signed);
	$user_unsigned = array_unique($user_unsigned);
	

	// LISTE DES PARTICIAPNTS AVEC POSSIBILITé D'AJOUT ET DE SUPRESSION
	foreach ($object->members as $participant) {
		if (!empty($participant)) {
			$exclude[] = $participant->id;
		}
	}

	foreach ($object->extern_members as $participant) {
		if (!empty($participant)) {
			$exclude_extern[] = $participant->id;
		}
	}
	
	print load_fiche_titre($langs->trans("Liste des participants dans cette causerie"), '', 'user');
	
	
	// Other form for add user to group
	$parameters = array('caneditperms' => $caneditperms, 'exclude' => $exclude);
	$reshook = $hookmanager->executeHooks('formAddUserToGroup', $parameters, $object->member, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (empty($reshook)) {
		if ($writeperms) {
			print '<div class="fichecenter">';
			  print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'#attending" method="POST" style="margin-top: 20px;">'."\n";
			  print '<input type="hidden" name="token" value="'.newToken().'">';
			  print '<input type="hidden" name="action" value="adduser">';
			  print '<div class="form-container" style="padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;display: contents;">';
			  print '<h3 style="margin-bottom: 15px; font-size: 1.2em; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 5px;">'.$langs->trans("Affecter un utilisateur").'</h3>';
			  print '<table class="noborder centpercent" style="width: 100%;">'."\n";
			  print '<tr class="liste_titre" style="background-color: #f0f0f0; font-weight: bold; color: #333;">';
			  print '<td class="liste_titre" style="padding: 8px 15px;">';
			  $filter = "u.statut = 1"; 
			  print $form->select_dolusers('', 'user', 1, $exclude, 0, '', $filter, $groups->entity, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
			  print ' &nbsp; ';
			
			$statusValue = ($object->status == $object::STATUS_DRAFT) ? 0 : 1;

			// Le texte en fonction du statut
			$labelText = ($object->status == $object::STATUS_DRAFT) 
				? $langs->trans("Définir comme brouillon, pas de confirmation de présence") 
				: $langs->trans("Considérer comme présent par défaut");

			// Les attributs à appliquer à la case à cocher
			$checkboxAttributes = ($object->status == $object::STATUS_DRAFT) 
				? 'hidden' 
				: 'checked';

			print '<label style="margin-left: 10px;">
				<input type="checkbox" name="confirm_presence" id="confirm_presence" value="' . $statusValue . '" ' . $checkboxAttributes . '> 
				' . $labelText . '
			</label>';

			
	 		 print ' &nbsp; ';
			  print '<input type="submit" class="button button-add" value="'.$langs->trans("Add").'" style="padding: 8px 15px; background-color: #0056b3; color: #fff; border: none; border-radius: 4px; cursor: pointer;">';
			  print '</td>';
			  print '</tr>';
			  if ($object->status < $object::STATUS_REALIZED) {

				print '<div id="infoMessage" style="margin-top: 15px; padding: 10px; border: 1px solid #f0ad4e; border-radius: 5px; background-color: #fcf8e3; color: #8a6d3b; position: relative;">';
				print '<i class="fas fa-info-circle"></i> ';
				print $langs->trans("Les absences peuvent être gérées après la confirmation de la réalisation ou de la clôture.");
				print '<button id="closeInfoMessage" style="position: absolute; top: 5px; right: 10px; background: none; border: none; font-size: 16px; color: #8a6d3b; cursor: pointer;" title="Fermer">&times;</button>';
				print '</div>';

				print '<script>
					document.addEventListener("DOMContentLoaded", function() {
						var infoMessage = document.getElementById("infoMessage");
						var closeButton = document.getElementById("closeInfoMessage");

						// Si le message a été fermé, il n\'est pas afdfiché
						if (sessionStorage.getItem("hideInfoMessage") === "true") {
							infoMessage.style.display = "none";
						}

						// Événement de fermeture du message
						closeButton.addEventListener("click", function() {
							infoMessage.style.display = "none"; // Cache le message
							sessionStorage.setItem("hideInfoMessage", "true"); // Sauvegarde l\'état pour cette session
							sessionStorage.setItem("firstRefresh", "true"); // Marque qu\'une actualisation est requise
						});

						// Si une actualisation a déjà eu lieu après la fermeture du message, on le réaffiche
						if (sessionStorage.getItem("firstRefresh") === "true") {
							sessionStorage.removeItem("hideInfoMessage"); // Réinitialise pour le faire réapparaître après une nouvelle actualisation
							sessionStorage.removeItem("firstRefresh");
						}
					});
				</script>';


				print ' &nbsp; ';
			}
			  print '</table>'."\n";
			  
			  print '<div style="margin-top: 10px; font-size: 0.9em; color: #555;">
					'.$langs->trans("Sélectionnez un utilisateur dans la liste et cliquez sur 'Ajouter' pour l\'affecter à cette causerie.").'
				  </div>';
				  
			  print '</div>';
			  print '</form>'."\n";  		
		}	

		
		/*
		* Group members
		*/
		print '<div class="fichecenter">'; 
		
		print ' &nbsp; ';
		print '<br>';
		print '<table class="tagtable nobottomiftotal liste listwithfilterbefore" id="attending">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">' . $langs->trans("Login") . '</td>';
		print '<td class="liste_titre">' . $langs->trans("Lastname") . '</td>';
		print '<td class="liste_titre">' . $langs->trans("Firstname") . '</td>';
		print '<td class="liste_titre">' . $langs->trans("Email") . '</td>';
		print '<td class="liste_titre">' . $langs->trans("Suivi") . '</td>';
		print '<td class="liste_titre">' . $langs->trans("Approve") . '</td>';
		if (!empty(array_filter($user_type_abs))) {
			print '<td class="liste_titre">' . $langs->trans("Type d'absence") . '</td>';
		}
	
		if (!$writeperms) {
			print '<td class="liste_titre">' . $langs->trans("Emargement") . '</td>';
		}

		print '<td class="liste_titre center" width="5">' . $langs->trans("Status") . '</td>';
		print '<td class="liste_titre right" width="5">&nbsp;</td>';
		print "</tr>\n";
		
		// Séparation des participants confirmés et non confirmés
		$confirmedParticipants = [];
		$unconfirmedParticipants = [];

		foreach ($object->members as $participant) {
			if (!empty($participant)) {
				// Vérification du statut de confirmation et le tri en conséquence
				if (in_array($participant->id, $user_signature_confirmed) || in_array($participant->id, $user_signature_absent)) {
					$confirmedParticipants[] = $participant;
				} else {
					$unconfirmedParticipants[] = $participant;
				}
			}
		}
	
	// Pour fusionner les participants confirmés et non confirmés, en affichant les non confirmés à la fin
	$allParticipants = array_merge($confirmedParticipants, $unconfirmedParticipants);
		
	// Affichage des participants triés
	foreach ($allParticipants as $participant) {
		print '<tr class="oddeven">';
		print '<td class="tdoverflowmax150">';
		// if(in_array($participant->id, $object->getResByAntenne(428))) {
		if(in_array($participant->id, $object->getResByAntenne($user->idate))) {
			print '<span style="
				font-size: 0.85em;
				color: #155724;
				font-weight: bold;
				background-color: #d4edda;
				border: 1px solid #c3e6cb;
				padding: 2px 6px;
				text-align: center;
				border-radius: 4px;
				display: inline-block;
			">'. $participant->getNomUrl(-1, '', 0, 0, 24, 0, 'login') .'</span>';
		}else {
			print $participant->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
		}
		
	
		if ($participant->id == $object->array_options['options_animateur']) {
			print img_picto($langs->trans("Animateur"), 'star');
			print '<span style="font-size: 0.85em; color: #28a745; font-weight: bold; background-color: #e8f5e9; border: 1px solid #c3e6cb; text-align: center; border-radius: 4px;">Animateur</span>';
		}
		
		print '</td>';
		
		print '<td>' . $participant->lastname . '</td>';
		print '<td>' . $participant->firstname . '</td>';
		print '<td>' . $participant->email . '</td>';

		if ($object->status == $object::STATUS_DRAFT && !in_array($participant->id, $user_signature_confirmed) && $participant->id == $object->array_options['options_animateur']) {
			print '<td style="font-size: 0.85em; color: #28a745; font-weight: bold; background-color: #e8f5e9; border: 1px solid #c3e6cb; text-align: center; border-radius: 4px;">Animateur</td>';
			if ($writeperms) {
				print '<td class="text-center">';
			
				print '</td>';
			}
		}

		
		
		if ($object->status == $object::STATUS_DRAFT && !in_array($participant->id, $user_signature_confirmed) && $participant->id !== $object->array_options['options_animateur']) {
			
			print '<td style="font-size: 0.85em; color: #856404; font-weight: bold; background-color: #fff3cd; border: 1px solid #ffeeba; text-align: center; border-radius: 4px; padding: 4px;">Non programmé</td>';

			if ($writeperms) {
				print '<td class="text-center">';
			
				print '</td>';
			}
		}	
		
		if (in_array($participant->id, $user_unsigned) && $participant->entity != 2) {
			print '<td style="font-size: 0.85em;">' . $emargement_unsigned . '</td>';
			// if ($writeperms) {
			//     print '<td><span class="fas fa-sharp fa-solid fa-hourglass-start" title="En attente de signature"></span></td>';
			// }
			if ($writeperms) {
				print '<td class="text-center">';
				print '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($object->id) . '&action=confirmpresence&user=' . urlencode($participant->id) . '&token='.newToken().'#attending" class="btn btn-success btn-sm" title="Confirmer la présence">';
				print '<i class="fas fa-check-circle"></i> Présent';
				print '</a>';
				print ' &nbsp; ';
				print '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($object->id) . '&action=confirmabsence&user=' . urlencode($participant->id) . '&token='.newToken().'#attending" class="btn btn-danger btn-sm" title="Confirmer l\'absence">';
				print '<i class="fas fa-times-circle"></i> Absent';
				print '</a>';
				print '</td>';
			}
			
		} elseif (in_array($participant->id, $user_signed) && $participant->entity != 2) {
			print '<td style="font-size: 0.85em;">' . $emargement_signed . '</td>';
			if ($writeperms) {
				print '<td class="text-center">';
				print '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($object->id) . '&action=confirmpresence&user=' . urlencode($participant->id) . '&token='.newToken().'#attending" class="btn btn-success btn-sm" title="Confirmer la présence">';
				print '<i class="fas fa-check-circle"></i> Présent';
				print '</a>';
				print ' &nbsp; ';
				print '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($object->id) . '&action=confirmabsence&user=' . urlencode($participant->id) . '&token='.newToken().'#attending" class="btn btn-danger btn-sm" title="Confirmer l\'absence">';
				print '<i class="fas fa-times-circle"></i> Absent';
				print '</a>';
				print '</td>';
			}
			
		} elseif (in_array($participant->id, $user_signature_confirmed)) {
			print '<td style="font-size: 0.85em;">' . $emargement_confirmed . '</td>';
			if ($writeperms) {
				print '<td>';
				print '<span class="fas fa-check-circle text-success" title="Présence confirmée"></span> Présence confirmée';
				print ' &nbsp; ';
				print '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($object->id) . '&action=confirmabsence&user=' . urlencode($participant->id) . '&token='.newToken().'#attending" class="btn btn-warning btn-sm" title="Déclarer une absence">';
				print '<span class="fas fa-times-circle"></span> Passer absent';
				print '</a>';
				print '</td>';
			}
		} elseif (in_array($participant->id, $user_signature_absent)) {
			print '<td style="font-size: 0.85em;">' . $emargement_absent . '</td>';
			if ($writeperms) {
				print '<td>';
				print '<span class="fas fa-times-circle text-danger" title="Absence confirmée"></span> Absence confirmée';
				print ' &nbsp; ';
				print '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($object->id) . '&action=confirmpresence&user=' . urlencode($participant->id) . '&token='.newToken().'#attending" class="btn btn-success btn-sm" title="Déclarer une présence">';
				print '<span class="fas fa-check-circle"></span> Passer présent';
				print '</a>';
				print '</td>';
				
				print '<td id="td-select-typeabs-'.$participant->id.'" data-selected-id="'.$user_type_abs[$participant->id].'" ></td>';
			}
		}

		print '<td class="center">' . $participant->getLibStatut(3) . '</td>';
		
		if ($object->status != $object::STATUS_INANIMATION) {
			print '<td class="right">';
			print '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($object->id) . '&action=removeuser&user=' . urlencode($participant->id) . '&token='.newToken().'#attending">';
			print img_picto($langs->trans("Supprimer de la causerie"), 'unlink');
			print '</a>';
		} else {
			print "";
		}
		print "</td></tr>\n";
	}
	?>
		<script>
						
			document.addEventListener("DOMContentLoaded", function() {
			// Sélectionne tous les td
			document.querySelectorAll("td[id^='td-select-typeabs-']").forEach(td => {
				let objectId = td.id.replace('td-select-typeabs-', '');

				// le select
				let select = document.createElement("select");
				select.dataset.objectId = objectId; 

				// badge pour afficher la valeur sélectionnée
				let badge = document.createElement("span");
				badge.className = "tag";                
				badge.style.marginLeft = "8px";
				badge.style.padding = "2px 6px";
				badge.style.fontSize = "0.85em";
				badge.style.fontWeight = "bold";
				// badge.style.borderRadius = "4px";
				badge.style.display = "inline-block";
				badge.style.minWidth = "25px";
				badge.style.textAlign = "center";

				// Fond discret et texte lisible
				badge.style.backgroundColor = "#e7f1fa"; 
				badge.style.color = "#0366a6";          
				badge.textContent = "-"; // valeur par défaut


				// Ajoute select et badge dans le td
				td.appendChild(select);
				td.appendChild(badge);

				// les types d'absences
				fetch("/custom/sse/ajax/get_type_abs_data.php")
					.then(response => response.json())
					.then(data => {
						data.forEach(item => {
							let opt = document.createElement("option");
							opt.value = item.id;
							opt.textContent = item.label;
							select.appendChild(opt);
						});

						// Préselection si valeur existante
						if(td.dataset.selectedId) {
							select.value = td.dataset.selectedId;
							let opt = select.options[select.selectedIndex];
							if(opt) badge.textContent = opt.text;
						}
					});

				// Sauvegarde sur changement
				select.addEventListener("change", function() {
					let selectedId = this.value;
					badge.textContent = this.options[this.selectedIndex].text;

					let id = <?php echo isset($object->id) ? (int)$object->id : 0; ?>;
					let iduser = this.dataset.objectId;

					fetch("/custom/sse/ajax/save_type_abs_data.php", {
						method: "POST",
						headers: { "Content-Type": "application/json" },
						body: JSON.stringify({idabs: selectedId, id: id, iduser:iduser})
					})
					.then(r => r.json())
					.then(res => {
						if(res.success) {
							console.log("Sauvegardé pour causerie "+id+", selected "+selectedId);
						} else {
							console.error("Erreur sauvegarde ligne "+id+":", res.error);
						}
					});
				});
			});
		});

		</script>



	<?php
	// Affichage des participants extern triés
	foreach($object->extern_members as $participant) {
		if (!empty($participant)) {
			print '<tr class="oddeven">';
			print '<td class="tdoverflowmax150">';
			// print 'Invité';
			print '<span style="display: inline-flex; align-items: center; font-size: 1em; color: #333;">';
			print img_picto($langs->trans("Visitor"), 'user') . '&nbsp;'; // Afficher l'icône
			print '<strong style="margin-left: 5px; color: #0056b3;">' . $langs->trans("Invité") . '</strong>'; // Texte avec un style
			print '</span>';
			
			print '</td>';
			print '<td>'.$participant->lastname.'</td>';
			print '<td>'.$participant->firstname.'</td>';
			print '<td>'.$participant->email.'</td>';
			// if(in_array($participant->id, $user_unsigned) && $participant->entity != 2 && $writeperms) {
			// 	print '<td>'.$emargement_unsigned.'</td>';
			// 	print '<td>';
			// 	print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=confirmpresenceextern&amp;extern='.$participant->id.'">';
			// 	print '<span class="fas fa-duotone fa-check" title="Confirmer"></span>';
			// 	print '</a>';
			// 	print '</td>';
			// }else 
			if($writeperms) {
				print '<td style="font-size: 0.85em; color: #004085; font-weight: bold; background-color: #cce5ff; border: 1px solid #b8daff; text-align: center; border-radius: 4px; padding: 4px;">Invité</td>';

				print '<td>';
				// print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=confirmpresenceextern&amp;extern='.$participant->id.'">';
				print '<span class="fas fa-duotone fa-check" title="Confirmer"></span>';
				// print '</a>';
				print '</td>';
			}
			
			print '<td class="center">'.$participant->getLibStatut(3).'</td>';
			print '<td class="right">';
			if ($object->status != $object::STATUS_INANIMATION) {
			
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removextern&amp;token='.newToken().'&amp;extern='.$participant->id.'#attending">';
				print img_picto($langs->trans("Supprimer de la causerie"), 'unlink');
				print '</a>';
			} else {
				print "";
			}
			print "</td></tr>\n";
		} else {
			print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
	}

	print "</table>";
	print '</div>';
		if ($writeperms) {
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'#attending" method="POST" style="margin-top: 20px;">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="addnewextern">';

			print '<div class="form-container" style="padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">';
			print '<h3 style="margin-bottom: 15px; font-size: 1.2em; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 5px;">'.$langs->trans("Ajouter un invité externe").'</h3>';
			
			print '<table class="noborder centpercent" style="width: 100%;">'."\n";
			print '<tr class="liste_titre" style="background-color: #f0f0f0; font-weight: bold; color: #333;">';
			print '<td class="titlefield liste_titre" style="padding: 8px 15px;">';
			print '<input type="text" required name="lastname" placeholder="'.$langs->trans("Nom").'" style="width: 200px; padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; margin-right: 30px !important;">';
			print '<input type="text" required name="firstname" placeholder="'.$langs->trans("Prénom").'" style="width: 200px; padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; margin-right: 30px !important;">';
			print '<input type="email" name="email" placeholder="'.$langs->trans("Email (optionnel)").'" style="width: 300px; padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; margin-right: 30px !important;">';
			print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
			print '<input type="submit" class="button button-add" value="'.$langs->trans("Add").'" style="padding: 8px 15px; background-color: #0056b3; color: #fff; border: none; border-radius: 4px; cursor: pointer;">';
			print '</td>';
			print '</tr>';
			print '</table>'."\n";
			
			print '<div style="margin-top: 10px; font-size: 0.9em; color: #555;">
					'.$langs->trans("Veuillez remplir au minimum les champs 'Nom' et 'Prénom'. L'email est optionnel.").'
				</div>';
			print '</div>';
			print '</form>'."\n";
		}
	}
	print "<br>";

	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->sse->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('sse:Causerie', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		// $linktoelem = $form->showLinkToObjectBlock($object, null, array('causerie'));
		// $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		// $MAXEVENT = 10;

		// $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-list-alt imgforviewmode', dol_buildpath('/sse/causerie_agenda.php', 1).'?id='.$object->id);

		// // List of actions on element
		// include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		// $formactions = new FormActions($db);
		// $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'causerie';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->sse->dir_output;
	$trackid = 'causerie'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	
}

// End of page
llxFooter();
$db->close();
