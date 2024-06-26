<?php
/* 
 * Copyright (C) 2017 Lény METZGER  <leny-07@hotmail.fr>
 */

/**
 *   	\file       user_formation.php
 *		\ingroup    formationhabilitation
 *		\brief      Page qui permet d'ajouter des formations sur la fiche d'un utilisateur
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
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT.'/custom/configurationaccidentaccueil/lib/configurationaccidentaccueil.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
dol_include_once('/formationhabilitation/class/formation.class.php');
dol_include_once('/formationhabilitation/class/habilitation.class.php');
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/user_formation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/user_habilitation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/extendedUser3.class.php';


// Translations
$langs->loadLangs(array("user", "formationhabilitation@formationhabilitation"));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');

$id = GETPOST('id', 'integer');
$lineid   = GETPOST('lineid', 'int');
$onglet = GETPOST('onglet', 'aZ09');

$permissiontoaddline = $user->rights->formationhabilitation->formation->addline;
$permissiontoreadCout = $user->rights->formationhabilitation->formation->readCout;

if (empty($conf->formationhabilitation->enabled)) accessforbidden();

if($id > 0){
    $object = New ExtendedUser3($db);
    $object->fetch($id);
}

$formation = new Formation($db);
$habilitation = new Habilitation($db);

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	reset($object->fields);					// Reset is required to avoid key() to return null.
	$sortfield = 'ref'; // Set here default search field. By default 1st field in definition.
}
if (!$sortorder) {
	$sortorder = "ASC";
}

/*
 * Actions
 */

 
// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
//include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

if($onglet == 'formation' || empty($onglet)){
    if($action == 'updateline' && !$cancel && $permissiontoaddline){
        if($lineid > 0 && $id > 0){
            $objectline = new User_formation($db);
            $objectline->fetch($lineid);


            if (empty(GETPOST("date_formationmonth", 'int')) || empty(GETPOST("date_formationday", 'int')) || empty(GETPOST("date_formationyear", 'int'))) {
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFormation")), null, 'errors');
                $error++;
            }
            $date = dol_mktime(-1, -1, -1, GETPOST("date_formationmonth", 'int'), GETPOST("date_formationday", 'int'), GETPOST("date_formationyear", 'int'));

            if(GETPOST('status') == -1 || empty(GETPOST('status'))){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
                $error++;
            }

            if (!$error) {
                $formation_static = new Formation($db);
                $formation_static->fetch($objectline->fk_formation);

				$objectline->ref = $object->login."-".$formation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
                $objectline->date_formation = $date;
                $objectline->date_fin_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date, $formation_static->periode_recyclage, 'd') : '');

                //$date_limite = dol_time_plus_duree($date, $object->periode_recyclage, 'm');
                //$date_limite = dol_print_date($date_limite, '%d/%m/%Y');
                //$now = dol_print_date(dol_now(), '%d/%m/%Y');
                /*if($date_limite > $now && GETPOST('status') == $objectline::STATUS_FINECHEANCE && $objectline->status == $objectline::STATUS_FINECHEANCE){
                    $objectline->status = $objectline::STATUS_PLANIFIEE;
                }
                else {*/
                   $objectline->status = GETPOST('status');
                //}

                $result = $objectline->update($user);
            }

            if(!$error && $result){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$result){
                setEventMessages($langs->trans($object->error), null, 'errors');
            }
        }
        else {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
        }
    }

    if($action == 'addline' && $permissiontoaddline){
        if($id > 0){
            $objectline = new User_formation($db);

            if(!(GETPOST('fk_formation') > 0)){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Formation")), null, 'errors');
                $error++;
            }

            if (empty(GETPOST("date_formationmonth", 'int')) || empty(GETPOST("date_formationday", 'int')) || empty(GETPOST("date_formationyear", 'int'))) {
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateFormation")), null, 'errors');
                $error++;
            }
            $date = dol_mktime(-1, -1, -1, GETPOST("date_formationmonth", 'int'), GETPOST("date_formationday", 'int'), GETPOST("date_formationyear", 'int'));

            if(GETPOST('status') == -1 || empty(GETPOST('status'))){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
                $error++;
            }

            if($objectline->getID(GETPOST('fk_formation'), $object->id) > 0){
                setEventMessages("Impossible d'ajouter cette formation car l'utilisateur est déja affecté à celle-ci", null, 'errors');
                $error++;
            }

            if (!$error) {
                $formation_static = new Formation($db);
                $formation_static->fetch(GETPOST('fk_formation'));

				$objectline->ref = $object->login."-".$formation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
                $objectline->fk_formation = GETPOST('fk_formation');
                $objectline->fk_user = $id;
                $objectline->date_formation = $date;
                $objectline->date_fin_formation = ($formation_static->periode_recyclage > 0 ? dol_time_plus_duree($date, $formation_static->periode_recyclage, 'd') : '');
                $objectline->status = GETPOST('status');
                $objectline->cout_pedagogique = $formation_static->cout;
                $objectline->cout_mobilisation = $object->thm * ($formation_static->nombre_heure / 3600);
                $objectline->cout_total = $objectline->cout_pedagogique + $objectline->cout_mobilisation;

                $result = $objectline->create($user);
            }

            if(!$error && $result){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$result){
                setEventMessages($langs->trans($object->error), null, 'errors');
            }
        }
        else {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
        }
    }

    if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoaddline) {
        $result = $object->deleteLine($user, $lineid);
        if ($result > 0) {
            setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
            exit;
        } else {
            $error++;
            setEventMessages($object->error, $object->errors, 'errors');
        }
        $action = '';
    }
}

if($onglet == 'habilitation'){
    if($action == 'updateline' && !$cancel && $permissiontoaddline){
        if($lineid > 0 && $id > 0){
            $objectline = new User_habilitation($db);
            $objectline->fetch($lineid);

            if (empty(GETPOST("date_habilitationmonth", 'int')) || empty(GETPOST("date_habilitationday", 'int')) || empty(GETPOST("date_habilitationyear", 'int'))) {
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateHabilitation")), null, 'errors');
                $error++;
            }
            $date = dol_mktime(-1, -1, -1, GETPOST("date_habilitationmonth", 'int'), GETPOST("date_habilitationday", 'int'), GETPOST("date_habilitationyear", 'int'));


            if(GETPOST('status') == -1 || empty(GETPOST('status'))){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
                $error++;
            }

            if (!$error) {
                $habilitation_static = new Habilitation($db);
                $habilitation_static->fetch($objectline->fk_habilitation);

				$objectline->ref = $object->login."-".$habilitation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
                $objectline->date_habilitation = $date;
                $objectline->status = GETPOST('status');

                $result = $objectline->update($user);
            }

            if(!$error && $result){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$result){
                setEventMessages($langs->trans($object->error), null, 'errors');
            }
        }
        else {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
        }
    }

    if($action == 'addline' && $permissiontoaddline){
        if($id > 0){
            $objectline = new User_habilitation($db);

            if (empty(GETPOST("date_habilitationmonth", 'int')) || empty(GETPOST("date_habilitationday", 'int')) || empty(GETPOST("date_habilitationyear", 'int'))) {
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateHabilitation")), null, 'errors');
                $error++;
            }
            $date = dol_mktime(-1, -1, -1, GETPOST("date_habilitationmonth", 'int'), GETPOST("date_habilitationday", 'int'), GETPOST("date_habilitationyear", 'int'));

            if(!(GETPOST('fk_habilitation') > 0)){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Habilitation")), null, 'errors');
                $error++;
            }

            if(GETPOST('status') == -1 || empty(GETPOST('status'))){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
                $error++;
            }

            if($objectline->getID(GETPOST('fk_habilitation'), $object->id) > 0){
                setEventMessages("Impossible d'ajouter cette habilitation car l'utilisateur est déja affecté à celle-ci", null, 'errors');
                $error++;
            }

            if (!$error) {
                $habilitation_static = new Habilitation($db);
                $habilitation_static->fetch(GETPOST('fk_habilitation'));

                $objectline->ref = $object->login."-".$habilitation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
                $objectline->fk_habilitation = GETPOST('fk_habilitation');
                $objectline->date_habilitation = $date;
                $objectline->fk_user = $id;
                $objectline->status = GETPOST('status');

                $result = $objectline->create($user);
            }

            if(!$error && $result){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$result){
                setEventMessages($langs->trans($object->error), null, 'errors');
            }
        }
        else {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
        }
    }

    if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoaddline) {
        $result = $object->deleteLine($user, $lineid);
        if ($result > 0) {
            setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet='.$onglet);
            exit;
        } else {
            $error++;
            setEventMessages($object->error, $object->errors, 'errors');
        }
        $action = '';
    }
}

// Action pour générer un document
if ($action == 'confirm_genererPdf' && $confirm == 'yes' && $permissiontoaddline) {
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

			$model = 'user_formationhabilitation';

			$retgen = $object->generateDocument_formationhabilitation($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($retgen < 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
		}
	}
}

/*
 * View
 */

if ($id == $user->id){

	$form = new Form($db);

	$help_url = '';
	$page_name = "Formation - Habilitation";

	llxHeader('', $page_name, $help_url);

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

	$head = user_prepare_head($object);

	print dol_get_fiche_head($head, 'user_formation', $title, -1, 'user');



    $formconfirm = '';
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid.'&onglet='.$onglet, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Print form confirm
	print $formconfirm;


	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="fichecenter"><div class="underbanner clearboth"></div><br>';

	$h = 0;
    $head2 = array();

	$head2[$h][0] = dol_buildpath("/formationhabilitation/user_formation.php", 1).'?id='.$object->id.'&onglet=formation';
	$head2[$h][1] = $langs->trans("Formations");
	$head2[$h][2] = 'formation';
	$h++;

    $head2[$h][0] = dol_buildpath("/formationhabilitation/user_formation.php", 1).'?id='.$object->id.'&onglet=habilitation';
	$head2[$h][1] = $langs->trans("Habilitations");
	$head2[$h][2] = 'habilitation';
	$h++;

    if(empty($onglet) || $onglet == 'formation'){
        print dol_get_fiche_head($head2, 'formation', $title, -1, 'user');

        // Show Formation lines
        $result = $object->getLinesArrayFormationHabilitation('formation');
        $object->table_element_line = 'formationhabilitation_user_formation';

        print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
        <input type="hidden" name="token" value="' . newToken().'">
        <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
        <input type="hidden" name="mode" value="">
        <input type="hidden" name="page_y" value="">
        <input type="hidden" name="id" value="' . $object->id.'">
        <input type="hidden" name="onglet" value="' .$onglet.'">
        ';


        print '<div class="div-table-responsive-no-min">';
        if (!empty($object->lines) || ($permissiontoaddline && $action != 'selectlines' && $action != 'editline')) {
            print '<table id="tablelines" class="noborder noshadow" width="100%">';
        }

        if (!empty($object->lines)) {
            $object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/custom/formationhabilitation/core/tpl');
        }

        // Form to add new line
        if ($permissiontoaddline && $action != 'selectlines') {
            if ($action != 'editline') {
                // Add products/services form
                $parameters = array();
                $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                if (empty($reshook)){
                    $object->formAddObjectLine(1, $mysoc, $soc, '/custom/formationhabilitation/core/tpl').'<br>';
                }
            }
        }

        if (!empty($object->lines) || ($permissiontoaddline && $action != 'selectlines' && $action != 'editline')) {
            print '</table>';
        }
        print '</div>';

        print "</form>\n";
    }
    elseif($onglet == 'habilitation'){
        print dol_get_fiche_head($head2, 'habilitation', $title, -1, 'user');

        // Show Formation lines
        $result = $object->getLinesArrayFormationHabilitation('habilitation');
        $object->table_element_line = 'formationhabilitation_user_habilitation';

        print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
        <input type="hidden" name="token" value="' . newToken().'">
        <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
        <input type="hidden" name="mode" value="">
        <input type="hidden" name="page_y" value="">
        <input type="hidden" name="id" value="' . $object->id.'">
        <input type="hidden" name="onglet" value="' .$onglet.'">
        ';


        print '<div class="div-table-responsive-no-min">';
        if (!empty($object->lines) || ($permissiontoaddline && $action != 'selectlines' && $action != 'editline')) {
            print '<table id="tablelines" class="noborder noshadow" width="100%">';
        }

        if (!empty($object->lines)) {
            $object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/custom/formationhabilitation/core/tpl');
        }

        // Form to add new line
        if ($permissiontoaddline && $action != 'selectlines') {
            if ($action != 'editline') {
                // Add products/services form
                $parameters = array();
                $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                if (empty($reshook)){
                    $object->formAddObjectLine(1, $mysoc, $soc, '/custom/formationhabilitation/core/tpl').'<br>';
                }
            }
        }

        if (!empty($object->lines) || ($permissiontoaddline && $action != 'selectlines' && $action != 'editline')) {
            print '</table>';
        }
        print '</div>';

        print "</form>\n";
    }

	print '</div>';

	// Page end
	print dol_get_fiche_end();

    print '<div class="tabsAction">'."\n";
    // Generer PDF
    print dolGetButtonAction($langs->trans('GenererDoc'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_genererPdf&confirm=yes&token='.newToken(), '', $permissiontoaddline);
    print '</div>'."\n";



	llxFooter();
	$db->close();
}
else {
	$urltogo = dol_buildpath('/user/card.php?id='.$id, 1);
	header("Location: ".$urltogo);
	exit;
}