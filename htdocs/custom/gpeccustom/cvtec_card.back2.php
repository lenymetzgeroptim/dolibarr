<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 *   	\file       cvtec_card.php
 *		\ingroup    gpeccustom
 *		\brief      Page to create/edit/view cvtec
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("MAIN_SECURITY_FORCECSP"))   define('MAIN_SECURITY_FORCECSP', 'none');	// Disable all Content Security Policies
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
dol_include_once('/gpeccustom/class/cvtec.class.php');
dol_include_once('/gpeccustom/lib/gpeccustom_cvtec.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("gpeccustom@gpeccustom", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOST('lineid', 'int');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

// Initialize technical objects
$object = new CVTec($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->gpeccustom->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('cvteccard', 'globalcard')); // Note that conf->hooks_modules contains array

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

$outputlangs = new Translate('', $conf);
$testcvdoc = $object->generateDocument('standard_cvtec', $outputlangs);
// var_dump($testcvdoc);
// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('gpeccustom', 'cvtec', 'read');
	$permissiontoadd = $user->hasRight('gpeccustom', 'cvtec', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('gpeccustom', 'cvtec', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('gpeccustom', 'cvtec', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('gpeccustom', 'cvtec', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->gpeccustom->multidir_output[isset($object->entity) ? $object->entity : 1].'/cvtec';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("gpeccustom")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}


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

	$backurlforlist = dol_buildpath('/gpeccustom/cvtec_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/gpeccustom/cvtec_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'GPECCUSTOM_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

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
	$triggersendname = 'GPECCUSTOM_MYOBJECT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid = 'cvtec'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

?>
<div id="blockvmenusearch" class="blockvmenusearch">
<select type="text" title="Raccourci clavier ALT + s" id="searchselectcombo" class="searchselectcombo vmenusearchselectcombo select2-hidden-accessible" accesskey="s" name="searchselectcombo" data-select2-id="searchselectcombo" tabindex="-1" aria-hidden="true"><option data-select2-id="11"></option><option value="searchintomember" data-select2-id="12">&lt;span class="fas fa-user-alt  em092 infobox-adherent pictofixedwidth" style=""&gt;&lt;/span&gt; Adh&amp;eacute;rents</option><option value="searchintothirdparty" data-select2-id="13">&lt;span class="fas fa-building pictofixedwidth" style=" color: #6c6aa8;"&gt;&lt;/span&gt; Tiers</option><option value="searchintocontact" data-select2-id="14">&lt;span class="fas fa-address-book pictofixedwidth" style=" color: #6c6aa8;"&gt;&lt;/span&gt; Contacts</option><option value="searchintoproduct" data-select2-id="15">&lt;span class="fas fa-cube pictofixedwidth" style=" color: #a69944;"&gt;&lt;/span&gt; Produits ou services</option><option value="searchintobatch" data-select2-id="16">&lt;span class="fas fa-barcode pictofixedwidth" style=" color: #a69944;"&gt;&lt;/span&gt; Lots / S&amp;eacute;ries</option><option value="searchintomo" data-select2-id="17">&lt;span class="fas fa-cubes pictofixedwidth" style=" color: #a69944;"&gt;&lt;/span&gt; Ordres Fabrication</option><option value="searchintoprojects" data-select2-id="18">&lt;span class="fas fa-project-diagram  em088 infobox-project pictofixedwidth" style=""&gt;&lt;/span&gt; Projets</option><option value="searchintotasks" data-select2-id="19">&lt;span class="fas fa-tasks infobox-project pictofixedwidth" style=""&gt;&lt;/span&gt; T&amp;acirc;ches</option><option value="searchintopropal" data-select2-id="20">&lt;span class="fas fa-file-signature infobox-propal pictofixedwidth" style=""&gt;&lt;/span&gt; Propositions/devis</option><option value="searchintoorder" data-select2-id="21">&lt;span class="fas fa-file-invoice infobox-commande pictofixedwidth" style=""&gt;&lt;/span&gt; Commandes clients</option><option value="searchintoinvoice" data-select2-id="22">&lt;span class="fas fa-file-invoice-dollar infobox-commande pictofixedwidth" style=""&gt;&lt;/span&gt; Factures clients</option><option value="searchintosupplierpropal" data-select2-id="23">&lt;span class="fas fa-file-signature infobox-supplier_proposal pictofixedwidth" style=""&gt;&lt;/span&gt; Propositions commerciales fournisseurs</option><option value="searchintosupplierorder" data-select2-id="24">&lt;span class="fas fa-dol-order_supplier infobox-order_supplier pictofixedwidth" style=""&gt;&lt;/span&gt; Commandes fournisseurs</option><option value="searchintosupplierinvoice" data-select2-id="25">&lt;span class="fas fa-file-invoice-dollar infobox-order_supplier pictofixedwidth" style=""&gt;&lt;/span&gt; Factures fournisseur</option><option value="searchintocontract" data-select2-id="26">&lt;span class="fas fa-suitcase  em092 infobox-contrat pictofixedwidth" style=""&gt;&lt;/span&gt; Contrats</option><option value="searchintointervention" data-select2-id="27">&lt;span class="fas fa-ambulance  em080 infobox-contrat pictofixedwidth" style=""&gt;&lt;/span&gt; Interventions</option><option value="searchintoknowledgemanagement" data-select2-id="28">&lt;span class="fas fa-ticket-alt infobox-contrat rotate90 pictofixedwidth" style=""&gt;&lt;/span&gt; Base de connaissance</option><option value="searchintotickets" data-select2-id="29">&lt;span class="fas fa-ticket-alt infobox-contrat pictofixedwidth" style=""&gt;&lt;/span&gt; Tickets</option><option value="searchintocustomerpayments" data-select2-id="30">&lt;span class="fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth" style=""&gt;&lt;/span&gt; R&amp;egrave;glements clients</option><option value="searchintovendorpayments" data-select2-id="31">&lt;span class="fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth" style=""&gt;&lt;/span&gt; R&amp;egrave;glements fournisseurs</option><option value="searchintomiscpayments" data-select2-id="32">&lt;span class="fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth" style=""&gt;&lt;/span&gt; Paiements divers</option><option value="searchintouser" data-select2-id="33">&lt;span class="fas fa-user infobox-adherent pictofixedwidth" style=""&gt;&lt;/span&gt; Utilisateurs</option><option value="searchintoexpensereport" data-select2-id="34">&lt;span class="fas fa-wallet infobox-expensereport pictofixedwidth" style=""&gt;&lt;/span&gt; Notes de frais</option><option value="searchintofod" data-select2-id="35">&lt;img src="/erp/custom/fod/img/object_fod_16.png" alt="" class="inline-block"&gt; Fiche d'objectifs dosim&amp;eacute;triques</option><option value="searchintofep" data-select2-id="36">&lt;img src="/erp/custom/fep/img/object_fep_16.png" alt="" class="inline-block"&gt; FEP/QS</option></select><span class="select2 select2-container select2-container--default select2-container--focus" dir="ltr" data-select2-id="10" style="width: 188px;"><span class="selection"><span class="select2-selection select2-selection--single searchselectcombo vmenusearchselectcombo" role="combobox" aria-haspopup="true" aria-expanded="false" title="Raccourci clavier ALT + s" tabindex="0" aria-disabled="false" aria-labelledby="select2-searchselectcombo-container"><span class="select2-selection__rendered" id="select2-searchselectcombo-container" role="textbox" aria-readonly="true"><span class="select2-selection__placeholder">Rechercher</span></span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span><script>
				jQuery(document).keydown(function(e){
					if( e.which === 70 && e.ctrlKey && e.shiftKey ){
						console.log('control + shift + f : trigger open global-search dropdown');
		                openGlobalSearchDropDown();
		            }
		            if( (e.which === 83 || e.which === 115) && e.altKey ){
		                console.log('alt + s : trigger open global-search dropdown');
		                openGlobalSearchDropDown();
		            }
		        });

		        var openGlobalSearchDropDown = function() {
		            jQuery("#searchselectcombo").select2('open');
		        }
			</script></div>
<?php
/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("CVTec");
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
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("CVTec")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($backtopagejsfields) {
		print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
	}
	if ($dol_openinpopup) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
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
	print load_fiche_titre($langs->trans("CVTec"), '', 'object_'.$object->picto);

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
	$head = cvtecPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("CVTec"), -1, $object->picto, 0, '', '', 0, '', 1);
	
	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteCVTec'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$text = $langs->trans('ConfirmActionCVTec', $object->ref);
		/*if (isModEnabled('notification'))
		{
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('MYOBJECT_CLOSE', $object->socid, $object);
		}*/

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
	$linkback = '<a href="'.dol_buildpath('/gpeccustom/cvtec_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') ? ':'.getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($permissiontoadd) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
	*/
	$morehtmlref .= '</div>';


	// dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	if($user->hasRight("user", "user", "read") || $user->admin) {
		$employee = new User($db);
		$employee->fetch($object->fk_user);
	
		$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$employee->id.'&output=file&file='.urlencode(dol_sanitizeFileName($employee->getFullName($langs).'.vcf')).'" class="refid" rel="noopener" rel="noopener">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard").' ('.$langs->trans("AddToContacts").')', 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';
	
		$urltovirtualcard = '/user/virtualcard.php?id='.((int) $employee->id);
		$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->trans("PublicVirtualCardUrl").' - '.$employee->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');
		// print $morehtmlref;
		print '<div class="inline-block floatleft valignmiddle maxwidth750 marginbottomonly refid refidpadding">CVTEC_236<div class="refidno"></div></div>';
		dol_banner_tab($employee, 'id', '', $user->hasRight("user", "user", "read") || $user->admin, 'rowid', 'ref', '');
	}else{
		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}
	

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

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
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid)?'&socid='.$object->socid:'').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/

			// Delete
			$params = array();
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete, $params);
		}
		print '</div>'."\n";
	}


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
			$filedir = $conf->gpeccustom->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('gpeccustom:CVTec', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		// $linktoelem = $form->showLinkToObjectBlock($object, null, array('cvtec'));
		// $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

		print '<br><br>';
		print '</div><div class="fichehalfright">';

		// $MAXEVENT = 10;

		// $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/gpeccustom/cvtec_agenda.php', 1).'?id='.$object->id);

		// // List of actions on element
		// include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		// $formactions = new FormActions($db);
		// $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
		
		global $user;
		$now = dol_now();
		$date_startmonth = GETPOST('date_startmonth', 'int');
		$date_startday = GETPOST('date_startday', 'int');
		$date_startyear = GETPOST('date_startyear', 'int');
		$date_endmonth = GETPOST('date_endmonth', 'int');
		$date_endday = GETPOST('date_endday', 'int');
		$date_endyear = GETPOST('date_endyear', 'int');

		$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
		$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);


		if (empty($date_start)) {
			$date_start = dol_time_plus_duree($now, -7, 'y');
		}

		if (empty($date_end)) {
			$date_end = dol_time_plus_duree($now, 1, 'y');
		}
		print '<div class="fichecenter"><div class="fichehalfleft">';
		/**
		 * action
		 */
		$skillJobsdata = $object->getskillJobAvgGraph($arr_skill_jobs, $object->fk_user, $date_start, $date_end);

		if (sizeof($skillJobsdata) > 0) {
			foreach ($skillJobsdata as $val) {

				$avrgnote[$val->fk_job][$val->date_eval] = $val;
				$alltotaljs[$val->fk_job] = $val->fk_job;
				$arrSkillJobs[] = $val->fk_job;

				$dataUsers[$val->fk_job][dol_print_date($val->date_eval, '%Y')] = array('label' => $val->job_label, 'year' => dol_print_date($val->date_eval, '%Y'), 'avg' => $val->avrgnote);
			}

			$skillJobs = empty($arr_skill_jobs) ? $arrSkillJobs : $arr_skill_jobs;

			//default colors for dataseries
			$datacolors = array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF');

			foreach ($dataUsers as $key => $values) {
				foreach ($values as $val) {

					//call to dynamic colors code generation function if not default colors code
					// array_push($datacolors, random_color());
					if (!empty($arr_skill_jobs) || $filter_fk_user > 0) {
						//datseries dynamic reconstruncting 
						foreach ($skillJobs as $jobs) {
							$avgjobs[$val['year']][] = $key == $jobs ? array('year' => $val['year'], 'avg_' . $jobs => $val['avg']) : array('avg-' . $jobs => 0.0);


							$label = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
							$words = explode(" ", $label);
							$acronym = "";

							foreach ($words as $w) {
								$acronym .= mb_substr($w, 0, 5);
								$acronym .= " ";
								$key == $jobs ? $labeljs[$key] =  ucfirst($acronym) : null;
							}
						}
					} elseif (empty($arr_skill_jobs) && ($filter_fk_user == -1 || $filter_fk_user == '')) {
						$label = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
						$words = explode(" ", $label);
						$acronym = "";

						foreach ($words as $w) {
							$acronym .= mb_substr($w, 0, 5);
							$acronym .= ". ";

							$dataseriesforavfjob[$key] = array(ucfirst($acronym), $val['avg']);
						}
					}
				}
			}


			//list of user's jobs and skills
			$dataskillJobsall = $object->getAvrSkillJobs($filter_fk_user);
			foreach ($dataskillJobsall as $val) {
				$arrskilljobs[$val->fk_job] = $val->job_label;
			}



			$totaljs = sizeof($alltotaljs);

			ksort($avgjobs);


			foreach ($avgjobs as $key => $val) {
				$flattenarr[] = call_user_func_array('array_merge', $val);
			}


			foreach ($flattenarr as $p) {
				$sizearrs[] = sizeof($p);
				//make position 0 (first value) in subarray
				array_unshift($p, $p['year']);
				unset($p['year']);
				ksort(array_keys($p));

				$data[] = $p;
			}

			//control position order of values in array for graph data and filter null duplicates values
			foreach ($data as $k => $values) {
				$empty = empty(array_filter($values, function ($a) {
					return $a !== null;
				}));

				foreach ($values as $key => $value) {
					if (!$empty) {
						$arr[$k][0] = $values[0];
						if ("avg-" == substr($key, 0, 4) || "avg_" == substr($key, 0, 4)) {
							$str = substr($key, strrpos($key, '_'));
							$str2 = substr($key, strrpos($key, '-'));

							$num = preg_replace("/[^0-9]/", '', $str);
							$num2 = preg_replace("/[^0-9]/", '', $str2);
							$nums[$num] = $num;

							if ($value > 0) {
								$arr2[$k][$num] = $value;
							}

							foreach ($nums as $n) {
								$arr[$k][$n] = $arr2[$k][$n] == null ? 0 : $arr2[$k][$n];
							}
						}
					}
				}
			}


			foreach ($arr as $vals) {
				$nbval[] = sizeof($vals);
				$nbvalues = max($nbval);

				$dataseriesforavfjob[] = array_values($vals);
			}
		}



		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';	


		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';

		print  '<tr class="liste_titre">';
		print  '<td>' . $langs->trans("Cartographie") . ' - ' . $langs->trans("Moyenne des compétences par emploi") . '</td>';

		print '<td class="right">';
		if (!empty(array_filter($arr_skill_jobs)) && sizeof(array_filter($arr_skill_jobs)) > 0) {
			print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill_jobs)) . ' emploi(s) séléctionné(s)">' . sizeof($arr_skill_jobs) . '</span>';
		}
		
		print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgjs1"></span></td>';
		print  '</tr>';

		if ($conf->use_javascript_ajax) {
			print  '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimgjs1").click(function() {
							jQuery("#idfilterjs1").toggle();
						});
					});
					</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject" id="idfilterjs1">'; // hideobject is to start hidden
			print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id='.$object->id.'&save_lastsearch_values=1">';
			print  '<input type="hidden" name="token" value="' . newToken() . '">';
			print  '<input type="hidden" name="action" value="refresh_js">';
			print  '<input type="hidden" name="page_y" value="">';

			print  $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);


			print '<br/>';
			print '<div>';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print $form->multiselectarray('arr_skill_jobs',  $arrskilljobs,  $arr_skill_jobs, '', '', '', '', '65%', '', '', 'Emploi');
			// print  ' &nbsp; ';
			print '</div>';
			
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
			print  '</form>';
			print  '</div>';

			// 	print  '</div>';
			print '</td>';
			print '</tr>';

			print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

			include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

			$WIDTH = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '360' : '360';
			$HEIGHT = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? '300' : '300';
			$NBLEG = sizeof($dataseriesforavfjob) * $nbvalues < 5 ? 2 : 1;

			$dolgraph = new DolGraph();
			$mesg = $dolgraph->isGraphKo();
			if (!$mesg && $action = 'refresh_js') {
				$dolgraph->SetData(array_values($dataseriesforavfjob));
				$dolgraph->SetDataColor(array_values($datacolors));
				$dolgraph->setLegend(array_values(array_unique(array_filter($labeljs))));

				if (!empty($arr_skill_jobs) || ($filter_fk_user != -1 && $filter_fk_user != '')) {
					$dolgraph->setShowLegend($NBLEG);
					// $dolgraph->setShowPercent(1);
					$dolgraph->SetHeight($HEIGHT);
					$dolgraph->SetWidth($WIDTH);
					// $dolgraph->SetType(array('lines'));
					$dolgraph->SetType(array('bars'));
				} else {
					// $dolgraph->setShowLegend(2);
					// $dolgraph->setShowPercent(2);
					$dolgraph->SetHeight('300');
					$dolgraph->SetWidth($WIDTH);
					$dolgraph->SetType(array('polar'));
				}


				$dolgraph->draw('idgraphavgjobskill');
				print $dolgraph->show($totaljs ? 0 : 1);

				print '</td></tr>';
			}
			print  '<tr class="liste_total">';
			print  '<td>' . $langs->trans("Total des emplois évalués") . '</td>';
			print  '<td class="right">' . $totaljs . '</td>';
			print  '</tr>';

			print  '</table>';
			print  '</div>';
			// print  '<br>';
		}
		if (empty($conf->use_javascript_ajax)) {
			$langs->load("errors");
			print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}

		print '</div><div class="fichehalfright">';

		/**
		 * actions 
		 */
		$skillData = $object->getskillAvgGraph($arr_skill, $object->fk_user, $date_skill_start, $date_skill_end);

		if (sizeof($skillData) > 0) {
		foreach ($skillData as $val) {

			$avgskillnote[$val->fk_skill][$val->date_eval] = $val;
			$alltotalskill[$val->fk_skill] = $val->fk_skill;
			$arrSkill[] = $val->fk_skill;
			$arrskill[$val->fk_skill] = $val->skill_label;

			$dataSkill[$val->fk_skill][dol_print_date($val->date_eval, '%Y')] = array('label' => $val->skill_label, 'year' => dol_print_date($val->date_eval, '%Y'), 'avg' => $val->avgskillnote);
		}

		$skillids = empty($arr_skill) ? $arrSkill : $arr_skill;

		//default colors for dataseries
		$datacolors = array('#177F00', '#D0D404', '#29D404', '#36FF09', '#FF0202', '#9E2B40', '#FD7F7F', '#FCCACA', '#04D0D4', '#0005FF');

		foreach ($dataSkill as $key => $values) {
			foreach ($values as $val) {

				//call to dynamic colors code generation function if not default colors code
				// array_push($datacolors, random_color());
				if (!empty($arr_skill) || $skill_fk_user > 0) {

					foreach ($skillids as $skillid) {
						$avgskill[$val['year']][] = $key == $skillid ? array('year' => $val['year'], 'avg_' . $skillid => $val['avg']) : array('avg-' . $skillid => 0.0);


						$labels = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
						$words = explode(" ", $labels);
						$acronym = "";

						foreach ($words as $w) {
							$acronym .= mb_substr($w, 0, 5);
							$acronym .= " ";
							$key == $skillid ? $labelskill[$key] =  ucfirst($acronym) : null;
						}
					}
				} elseif (empty($arr_skill) && ($skill_fk_user == -1 || $skill_fk_user == '')) {
					$labelskill = str_replace([" de ", " d'", " des ", " en ", " et "], '. ', $val['label']);
					$words = explode(" ", $labelskill);
					$acronym = "";

					foreach ($words as $w) {
						$acronym .= mb_substr($w, 0, 5);
						$acronym .= ". ";

						$dataseriesforavgskill[$key] = array(ucfirst($acronym), $val['avg']);
					}
				}
			}
		}


		// list of user's jobs and skills
		$dataskillall = $object->getAvrSkill($object->fk_user);
		foreach ($dataskillall as $val) {
			$arrskillall[$val->fk_skill] = $val->skill_label;
		}


		$totalskill = sizeof($alltotalskill);

		ksort($avgskill);


		foreach ($avgskill as $key => $val) {
			$flattenarrskill[] = call_user_func_array('array_merge', $val);
		}


		foreach ($flattenarrskill as $p) {
			$sizearrs[] = sizeof($p);
			//make position 0 (first value) in subarray
			array_unshift($p, $p['year']);
			unset($p['year']);
			ksort(array_keys($p));

			$skdata[] = $p;
		}

		//control position order of values in array for graph data and delete duplicates if they are null
		foreach ($skdata as $k => $values) {
			$empty = empty(array_filter($values, function ($a) {
				return $a !== null;
			}));

			foreach ($values as $key => $value) {
				if (!$empty) {
					$arrsk[$k][0] = $values[0];
					if ("avg-" == substr($key, 0, 4) || "avg_" == substr($key, 0, 4)) {
						$str = substr($key, strrpos($key, '_'));
						$str2 = substr($key, strrpos($key, '-'));

						$num = preg_replace("/[^0-9]/", '', $str);
						$num2 = preg_replace("/[^0-9]/", '', $str2);
						$nums[$num] = $num;

						if ($value > 0) {
							$arrsk2[$k][$num] = $value;
						}

						foreach ($nums as $n) {
							$arrsk[$k][$n] = $arrsk2[$k][$n] == null ? 0 : $arrsk2[$k][$n];
						}
					}
				}
			}
		}


		foreach ($arrsk as $vals) {
			$nbval[] = sizeof($vals);
			$nbvalues = max($nbval);

			$dataseriesforavgskill[] = array_values($vals);
		}
		}

		/**
		* view 
		*/
		print '</div><div class="fichetwothirdright">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';

		print  '<tr class="liste_titre">';
		print  '<td>' . $langs->trans("Cartographie") . ' - ' . $langs->trans("Moyenne des compétences") . '</td>';

		print '<td class="right">';
		if (!empty(array_filter($arr_skill)) && sizeof(array_filter($arr_skill)) > 0) {
			print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_skill)) . ' compétence(s) séléctionné(s)">' . sizeof($arr_skill) . '</span>';
		}
		
		print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgskill"></span></td>';
		print  '</tr>';

		if ($conf->use_javascript_ajax) {
			print  '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimgskill").click(function() {
							jQuery("#idfilterskill").toggle();
						});
					});
					</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject" id="idfilterskill">'; // hideobject is to start hidden
			print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
			print  '<input type="hidden" name="token" value="' . newToken() . '">';
			print  '<input type="hidden" name="action" value="refresh_skill">';
			print  '<input type="hidden" name="page_y" value="">';

			print  $form->selectDate($date_skill_start, 'date_skill_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_skill_end, 'date_skill_end', 0, 0, 0, '', 1, 0);

			// print '<div class="right">';
			// print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			// print '<span class="fas fa-chart-bars" name="bars" title="Affichage en barres"></span>';
			// print '&nbsp;&nbsp;';
			// print '<span class="fas fa-chart-line" name="lines" title="Affichage en courbes"></span>';
			// print '</div>';

			print '<br/>';
			print '<div>';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print $form->multiselectarray('arr_skill',  $arrskillall,  $arr_skill, '', '', '', '', '65%', '', '', 'Compétence');
			// print  ' &nbsp; ';
			print '</div>';
			
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
			print  '</form>';
			print  '</div>';

			// 	print  '</div>';
			print '</td>';
			print '</tr>';

			print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

			include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

			$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '360' : '360';
			$HEIGHT = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '300' : '300';
			$NBLEG = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? 2 : 1;

			$dolgraph = new DolGraph();
			$mesg = $dolgraph->isGraphKo();
			if (!$mesg && $action = 'refresh_js') {
				$dolgraph->SetData(array_values($dataseriesforavgskill));
				$dolgraph->SetDataColor(array_values($datacolors));
				$dolgraph->setLegend(array_values(array_unique(array_filter($labelskill))));

				if (!empty($arr_skill) || ($skill_fk_user != -1 && $skill_fk_user != '')) {
					$dolgraph->setShowLegend($NBLEG);
					// $dolgraph->setShowPercent(1);
					$dolgraph->SetHeight($HEIGHT);
					$dolgraph->SetWidth($WIDTH);
					// $dolgraph->SetType(array('lines'));
					$dolgraph->SetType(array('bars'));
				} else {
					$dolgraph->setShowLegend(2);
							$dolgraph->setShowPercent(2);
					$dolgraph->SetHeight('300');
					$dolgraph->SetWidth($WIDTH);
					$dolgraph->SetType(array('pie'));
				}


				$dolgraph->draw('idgraphavgskill');
				print $dolgraph->show($totalskill ? 0 : 1);

				print '</td></tr>';
			}
			print  '<tr class="liste_total">';
			print  '<td>' . $langs->trans("Total des compétences évalués") . '</td>';
			print  '<td class="right">' . $totalskill . '</td>';
			print  '</tr>';

			print  '</table>';
			print  '</div>';
			// print  '<br>';
		}
		if (empty($conf->use_javascript_ajax)) {
			$langs->load("errors");
			print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}

		
		
		/**
		 * actions
		 */
		$nbonskills = $object->getSkillEvaluated($object->fk_user, $arr_val_skill, $date_val_start, $date_val_end, 'all_users', 'on_evaluation');
		$nbvalonskills = $object->getSkillEvaluated($object->fk_user, $arr_val_skill, $date_val_start, $date_val_end,  'validate_users', 'on_evaluation');
		$nboffskills = $object->getSkillEvaluated($object->fk_user, $arr_val_skill, $date_val_start, $date_val_end, 'all_users', 'off_evaluation');


		foreach ($nbvalonskills as $key => $val) {
			$nbValSkills[$val->label][$val->fk_user] += count($val->fk_user);
		}

		foreach ($nbonskills as $key => $val) {
			$nbOnSkills[$val->label][$val->fk_user] += count($val->fk_user);
		}

		foreach ($nboffskills as $key => $val) {
			$nbOffSkills[$val->label][$val->fk_user] += count($val->fk_user);
		}
		//   var_dump($nbOnSkills);
		if (!empty($nboffskills)) {
			foreach ($nboffskills as $key => $val) {
				$label = str_replace([" de ", " d'", " des ", " en ", " et ", " le ", " la ", " les ", " du "], '- ', $val->label);
				$words = explode(" ", $label);
				$acronym = "";

				foreach ($words as $w) {
					$acronym .= mb_substr($w, 0, 5);
					$acronym .= ". ";
					$labelvaljs[$val->label] =  ucfirst($acronym);
				}
				$labels = array("Nb comp. de l'emploi/collaborateur affécté", "Nb comp. de l'emploi/collaborateur évalué sur l'emploi", "Nb comp. par collaborateur < seuil requis dans l'emploi");
				$datacolors = array("rgb(60, 147, 183, 0.9)", "rgb(137, 86, 161, 0.9)", "rgb(250, 190, 80, 0.9)");
				// var_dump($val->label.'--'.array_sum($nbOffSkills[$val->label]));
				// var_dump($val->label.'_'.sizeof($nbOffSkills[$val->label]));
				// print '<a class="butAction" href="card.php?rowid='.$id.'&action=edit&token='.newToken().'".link("https://developer.mozilla.org/")>'.$langs->trans("Modify").'</a>'."\n";
				$nbSkills[$val->label] = array($labelvaljs[$val->label], array_sum($nbOffSkills[$val->label]), array_sum($nbOnSkills[$val->label]), array_sum($nbValSkills[$val->label]));
			}
		} else {
			foreach ($nbonskills as $key => $val) {
				$label = str_replace([" de ", " d'", " des ", " en ", " et ", " le ", " la ", " les ", " du "], '- ', $val->label);
				$words = explode(" ", $label);
				$acronym = "";

				foreach ($words as $w) {
					$acronym .= mb_substr($w, 0, 5);
					$acronym .= ". ";
					$labelvaljs[$val->label] =  ucfirst($acronym);
				}
				$labels = array("Nb compétences par collaborateur sur un poste évalué", "Nb compétences par collaborateur < seuil requis");
				$datacolors = array("rgb(137, 86, 161, 0.9)", "rgb(250, 190, 80, 0.9)");
				// var_dump($val->label.'--'.array_sum($nbOffSkills[$val->label]));
				// var_dump($val->label.'_'.sizeof($nbOffSkills[$val->label]));
				// print '<a class="butAction" href="card.php?rowid='.$id.'&action=edit&token='.newToken().'".link("https://developer.mozilla.org/")>'.$langs->trans("Modify").'</a>'."\n";
				$nbSkills[$val->label] = array($labelvaljs[$val->label], array_sum($nbOnSkills[$val->label]), array_sum($nbValSkills[$val->label]));
			}
		}


		/**
		* view 
		*/
		//  print '</div><div class="fichetwothirdright">';
		// print '</div></div>';
		// print '<div class="fichecenter">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';

		print  '<tr class="liste_titre">';
		print  '<td>' . $langs->trans("Statistics") . ' - ' . $langs->trans("Nombre de Compétence/Emploi/Evaluation") . '</td>';

		print '<td class="right">';
		if (!empty(array_filter($arr_val_skill)) && sizeof(array_filter($arr_val_skill)) > 0) {
			print '<span class="classfortooltip badge badge-info right" title="' . sizeof(array_filter($arr_val_skill)) . ' compétence(s) séléctionnée(s)">' . sizeof($arr_val_skill) . '</span>';
		}

		print '<span class="fas fa-filter opacitymedium marginleftonly linkobject boxfilter" style="" title="Filtre" id="idsubimgvalskill"></span>';

		print ' &nbsp; ';

		if ($mode == 'off' || $mode == '') {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=on">';
			print '<span class="fas fa-ellipsis-v" title="Cliquer pour afficher toutes les compétences - avec possibilité de filtrer sur la période des emplois exercés"></span>';
			print '</a>';
		}
		if ($mode == 'on') {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=off">';
			print '<span class="fas fa-solid fa-banas fa-ellipsis-h" title="Cliquer pour afficher les dernières compétences relatives à des emplois en cours"></span>';
			print '</a>';
		}
		print ' &nbsp; ';
		print '</td>';
		print  '</tr>';

		if ($conf->use_javascript_ajax) {
			print  '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimgvalskill").click(function() {
							jQuery("#idfiltervalskill").toggle();
						});
					});
					</script>';
			print '<tr>';
			print '<td colspan="2">';
			print  '<div class="center hideobject" id="idfiltervalskill">'; // hideobject is to start hidden
			print  '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
			print  '<input type="hidden" name="token" value="' . newToken() . '">';
			print  '<input type="hidden" name="action" value="refresh_val_skill">';
			print  '<input type="hidden" name="page_y" value="">';

			print  $form->selectDate($date_val_start, 'date_val_start', 0, 0, 1, '', 1, 0) . ' &nbsp; ' . $form->selectDate($date_val_end, 'date_val_end', 0, 0, 0, '', 1, 0);


			print '<br/>';
			print '<div>';
			print img_picto('', 'skill', 'class="pictofixedwidth"');
			print $form->multiselectarray('arr_val_skill',  $arrskill,  $arr_val_skill, '', '', '', '', '65%', '', '', 'Compétence');
			// print  ' &nbsp; ';
			print '</div>';
			
			print '<br/>';
			print  '<input type="image" class="reposition inline-block valigntextbottom" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
			print  '</form>';
			print  '</div>';

			// 	print  '</div>';
			print '</td>';
			print '</tr>';

			print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

			include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

			$WIDTH = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '1220' : '1220';
			$HEIGHT = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? '500' : '500';
			$NBLEG = sizeof($dataseriesforavgskill) * $nbvalues < 5 ? 2 : 1;

			$dolgraph = new DolGraph();
			$mesg = $dolgraph->isGraphKo();
			if (!$mesg) {
				$dolgraph->SetData(array_values($nbSkills));
				//  #FF0202
				$dolgraph->SetDataColor($datacolors);
				$dolgraph->setLegend($labels);


				//if (!empty($arr_val_skill) || ($object->fk_user != -1 && $object->fk_user != '')) {
					// $dolgraph->setShowLegend($NBLEG);
					// $dolgraph->setShowPercent(1);
				// 	$dolgraph->SetHeight($HEIGHT);
				// 	$dolgraph->SetWidth($WIDTH);
				// 	// $dolgraph->SetType(array('lines'));
				// 	$dolgraph->SetType(array('bars'));
				// } else {
					// $dolgraph->setShowLegend(2);
					$dolgraph->SetHeight('500');
					$dolgraph->SetWidth($WIDTH);
					$dolgraph->SetType(array('bars'));
				// }

				$dolgraph->draw('idgraphvalskill');
				print $dolgraph->show($totalskill ? 0 : 1);

				print '</td></tr>';
			}
			print  '<tr class="liste_total">';
			print  '<td>' . $langs->trans("Total des compétences évaluées") . '</td>';
			print  '<td class="right">' . $totalskill . '</td>';
			print  '</tr>';

			print  '</table>';
			print  '</div>';
			// print  '<br>';
		}
		if (empty($conf->use_javascript_ajax)) {
			$langs->load("errors");
			print $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}

		print '</div>';

		
	}


	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'cvtec';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->gpeccustom->dir_output;
	$trackid = 'cvtec'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

}


	// $html = '<img src="" id="gen-image" alt="" style="border: 0.1em solid black; no-repeat;>';
	// print $html;
print '</div></div>';
		print '<div class="fichecenter">';
		print '<script>
		  let off_canvas = document.getElementById("canvas_idgraphavgskill");
		  var off_ctx = off_canvas.getContext("2d");

			// off_ctx.beginPath();
			// off_ctx.rect(10, 10, 0, 0);
			// off_ctx.fillStyle = "red";
			// off_ctx.fill();

			var brick = new Image();
			brick.setAttribute("crossorigin", "anonymous");
			brick.src = "https://picsum.photos/200/300";
			// brick.src =  "img/object_gpeccustom.png";

			brick.onload = function(){
			// var pattern = off_ctx.createPattern(brick, "no-repeat");
			// off_ctx.fillStyle = pattern;
			// off_ctx.fillRect(500, 0, 1000, 1000);
			off_ctx.beginPath();
			//off_ctx.fillRect(50, 50, 0, 0);
			off_ctx.fillStyle = "white";
			off_ctx.fill();
			
			// needs delay to fully render to canvas
			var timer = save_canvas(off_canvas);
		};

		function save_canvas(c) {
			var url = c.toDataURL("image/png");
			var v = 0
			for(var i = 0; i < 100; i++ ){
		
				v += 0.01;
				x = parseFloat((v).toFixed(2))
				var test = c.toDataURL("image/png", x);
	
				if(test ==	url){
					var b64Image = c.toDataURL("image/png");
					console.log("The default value is: " + x);
				}
			}
			
			fetch("https://erp.optim-industries.fr/erp/custom/gpeccustom/cvtec_card.php?id='.$id.'", {
				method: "POST",
				mode: "no-cors",
				// contentType: "application/json",
				// dataType: "json",
				// encoding: "text/plain",
				headers: {"Content-Type": "application/x-www-form-urlencoded"},
				body: b64Image
			})  .then(response => b64Image)
				.then(success => console.log(b64Image))
				// .then(success => document.cookie = b64Image)
				.catch(error => console.log(error));
		}
	 </script>';
		
	$img = file_get_contents("php://input"); // $_POST didn't work		

    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace('', '+', $img);

    $data = base64_decode($img);


	if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/img/".$id)) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . "/img/".$id, 0777, true);			
    }
 
    // if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/img")) {
    //     mkdir($_SERVER['DOCUMENT_ROOT'] . "/img", 0777, true);
    // }

	$file = $id != null ? $_SERVER['DOCUMENT_ROOT'] ."/img/".$id."/".$id.'.png' : null;
	// var_dump($file);
	
	$im = file_get_contents($file);
	// var_dump($im);

	// $success = file_put_contents($file, $data);
	// var_dump($success.' ____ '.$data);

// End of page
llxFooter();
$db->close();
