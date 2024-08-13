<?php
/* 
 * Copyright (C) 2017 Lény METZGER  <leny-07@hotmail.fr>
 */

/**
 *   	\file       userformation.php
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
dol_include_once('/formationhabilitation/class/autorisation.class.php');
dol_include_once('/formationhabilitation/class/habilitation.class.php');
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/userformation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/userhabilitation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/userautorisation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/extendedUser3.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


// Translations
$langs->loadLangs(array("user", "formationhabilitation@formationhabilitation"));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');

$userid = GETPOST('id', 'integer');
$lineid   = GETPOST('lineid', 'int');
$onglet = GETPOST('onglet', 'aZ09');
$voletid   = GETPOST('voletid', 'int');

$permissiontoaddline = $user->rights->formationhabilitation->formation->addline;
$permissiontoreadCout = $user->rights->formationhabilitation->formation->readCout;

if (empty($conf->formationhabilitation->enabled)) accessforbidden();

$object = New ExtendedUser3($db);
if($userid > 0){
    $object->fetch($userid);
}
$form = new Form($db);

if($onglet == 'formation' || empty($onglet)){
    $objectline = new UserFormation($db);
    $objectparentline = new Formation($db);
}
elseif($onglet == 'habilitation'){
    $objectline = new UserHabilitation($db);
    $objectparentline = new Habilitation($db);
}
elseif($onglet == 'autorisation'){
    $objectline = new UserAutorisation($db);
    $objectparentline = new Autorisation($db);
}
elseif($onglet == 'volet'){
    $objectparentline = new Formation($db);
}

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	reset($object->fields);					// Reset is required to avoid key() to return null.
	$sortfield = 'ref'; // Set here default search field. By default 1st field in definition.
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$search = array();
$search['fk_user'] = $object->id;

/*
 * Actions
 */


if($onglet == 'formation' || empty($onglet)){
    $objectline = new UserFormation($db);
	
    if(GETPOST('fk_formation') > 0) {
        $formation_static = new Formation($db);
        $formation_static->fetch(GETPOST('fk_formation'));
    }

    if(GETPOST('fk_user') > 0) {
        $user_static = new User($db);
        $user_static->fetch(GETPOST('fk_user'));
    }

    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userformation.inc.php';
}

if($onglet == 'habilitation'){
    if($action == 'updateline' && !$cancel && $permissiontoaddline){
        if($lineid > 0 && $id > 0){
            $objectline = new UserHabilitation($db);
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
                $objectline->date_fin_habilitation = dol_time_plus_duree($date, $habilitation_static->validite_employeur, 'd');
                $objectline->status = GETPOST('status');

                $resultupdate = $objectline->update($user);
            }

            if(!$error && $resultupdate){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$error && !$resultupdate){
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
            $objectline = new UserHabilitation($db);

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
                $objectline->date_fin_habilitation = dol_time_plus_duree($date, $habilitation_static->validite_employeur, 'd');
                $objectline->fk_user = $id;
                $objectline->status = GETPOST('status');

                $resultcreate = $objectline->create($user);
            }

            if(!$error && $resultcreate){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$error && !$resultcreate){
                setEventMessages($langs->trans($object->error), null, 'errors');
            }
        }
        else {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
        }
    }

    if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoaddline) {
        $resultdelete = $object->deleteLine($user, $lineid);
        if ($resultdelete > 0) {
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

if($onglet == 'autorisation'){
    if($action == 'updateline' && !$cancel && $permissiontoaddline){
        if($lineid > 0 && $id > 0){
            $objectline = new UserAutorisation($db);
            $objectline->fetch($lineid);

            if (empty(GETPOST("date_autorisationmonth", 'int')) || empty(GETPOST("date_autorisationday", 'int')) || empty(GETPOST("date_autorisationyear", 'int'))) {
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateAutorisation")), null, 'errors');
                $error++;
            }
            $date = dol_mktime(-1, -1, -1, GETPOST("date_autorisationmonth", 'int'), GETPOST("date_autorisationday", 'int'), GETPOST("date_autorisationyear", 'int'));


            if(GETPOST('status') == -1 || empty(GETPOST('status'))){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
                $error++;
            }

            if (!$error) {
                $autorisation_static = new Autorisation($db);
                $autorisation_static->fetch($objectline->fk_autorisation);

                $objectline->ref = $object->login."-".$autorisation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
                $objectline->date_autorisation = $date;
                $objectline->date_fin_autorisation = dol_time_plus_duree($date, $autorisation_static->validite_employeur, 'd');
                $objectline->status = GETPOST('status');

                $resultupdate = $objectline->update($user);
            }

            if(!$error && $resultupdate){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$error && !$resultupdate){
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
            $objectline = new UserAutorisation($db);

            if (empty(GETPOST("date_autorisationmonth", 'int')) || empty(GETPOST("date_autorisationday", 'int')) || empty(GETPOST("date_autorisationyear", 'int'))) {
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateAutorisation")), null, 'errors');
                $error++;
            }
            $date = dol_mktime(-1, -1, -1, GETPOST("date_autorisationmonth", 'int'), GETPOST("date_autorisationday", 'int'), GETPOST("date_autorisationyear", 'int'));

            if(!(GETPOST('fk_autorisation') > 0)){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Autorisation")), null, 'errors');
                $error++;
            }

            if(GETPOST('status') == -1 || empty(GETPOST('status'))){
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Status")), null, 'errors');
                $error++;
            }

            if($objectline->getID(GETPOST('fk_autorisation'), $object->id) > 0){
                setEventMessages("Impossible d'ajouter cette autorisation car l'utilisateur est déja affecté à celle-ci", null, 'errors');
                $error++;
            }

            if (!$error) {
                $autorisation_static = new Autorisation($db);
                $autorisation_static->fetch(GETPOST('fk_autorisation'));

                $objectline->ref = $object->login."-".$autorisation_static->ref.'-'.dol_print_date($date, "%Y%m%d");
                $objectline->fk_autorisation = GETPOST('fk_autorisation');
                $objectline->date_autorisation = $date;
                $objectline->date_fin_autorisation = dol_time_plus_duree($date, $autorisation_static->validite_employeur, 'd');
                $objectline->fk_user = $id;
                $objectline->status = GETPOST('status');

                $resultcreate = $objectline->create($user);
            }

            if(!$error && $resultcreate){
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            elseif(!$error && !$resultcreate){
                setEventMessages($langs->trans($object->error), null, 'errors');
            }
        }
        else {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
        }
    }

    if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoaddline) {
        $resultdelete = $object->deleteLine($user, $lineid);
        if ($resultdelete > 0) {
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
if ($onglet == 'volet') {
    if($action == 'confirm_genererPdf' && $confirm == 'yes' && $permissiontoaddline) {
        if ($voletid < 1) {
            setEventMessages("Vous devez sélectionner un volet", null, 'errors');
            $error++;
        }

        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            if (method_exists($objectparentline, 'generateDocument') && !$error) {
                $outputlangs = $langs;
                $newlang = '';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
                    $newlang = GETPOST('lang_id', 'aZ09');
                }
                if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                    $newlang = $objectparentline->thirdparty->default_lang;
                }
                if (!empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }

                //$ret = $object->fetch($id); // Reload to get new records

                $model = 'userformationhabilitation';

                $retgen = $objectparentline->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                if ($retgen < 0) {
                    setEventMessages($objectparentline->error, $objectparentline->errors, 'warnings');
                }
            }
        }
    }

    // Delete file
    if ($action == 'confirm_deletefile' && $confirm == 'yes') {
        $file = $conf->formationhabilitation->dir_output.'/'.$object->id."/".GETPOST('file'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

        $ret = dol_delete_file($file);
        if ($ret) {
            setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
        } else {
            setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
        }
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=volet');
        exit;
    }
}



/*
 * View
 */

$help_url = '';
$page_name = "Formation - Habilitation";

llxHeader('', $page_name, $help_url);

$res = $object->fetch($userid, '', '', 1);
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

print dol_get_fiche_head($head, 'userformation', $title, -1, 'user');



$formconfirm = '';
// Confirmation to delete line
if ($action == 'deleteline') {
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid.'&onglet='.$onglet, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
}
if ($action == 'remove_file') {
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&file='.urlencode(GETPOST("file")).'&onglet='.$onglet, $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
}
if ($action == 'programmer_formation') {
    $objectline->fetch($lineid);
    $formquestion = array(array('label'=>'Date début formation' ,'type'=>'date', 'name'=>'date_debut_formation_programmer', 'value'=>$objectline->date_debut_formation),
                          array('label'=>'Date fin formation' ,'type'=>'date', 'name'=>'date_fin_formation_programmer', 'value'=>$objectline->date_fin_formation));
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('ProgrammerFormation'), $langs->trans('ConfirmProgrammerFormation'), 'confirm_programmer_formation', $formquestion, 0, 2);
}
if ($action == 'valider_formation') {
    $objectline->fetch($lineid);

    if($objectline->fk_formation > 0 && $objectline->fk_user > 0) { // Formation inferieur
        $formationToClose = $objectparentline->getFormationToClose($objectline->fk_user, $objectline->fk_formation, $lineid);
        $txt_formationToClose = '';
		foreach($formationToClose as $idformation => $refformation) {
            $txt_formationToClose .= $refformation.', ';
        }
        $txt_formationToClose = rtrim($txt_formationToClose, ', ');
    }

    $formquestion = array(array('label'=>'Résultat' ,'type'=>'select', 'name'=>'resultat_valider', 'value'=>$objectline->resultat, 'values' => $objectline->fields['resultat']['arrayofkeyval']),
                          array('label'=>'Numéro Certificat' ,'type'=>'text', 'name'=>'numero_certificat_valider', 'value'=>$objectline->numero_certificat));
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('ValiderFormation'), (!empty($txt_formationToClose) ? $langs->trans('ConfirmValiderFormation2', $txt_formationToClose) : $langs->trans('ConfirmValiderFormation')), 'confirm_valider_formation', $formquestion, 0, 2);
}
// Print form confirm
print $formconfirm;


dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

print '<div class="fichecenter"><div class="underbanner clearboth"></div><br>';

$h = 0;
$head2 = array();

$head2[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=formation';
$head2[$h][1] = $langs->trans("Formations");
$head2[$h][2] = 'formation';
$h++;

$head2[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=habilitation';
$head2[$h][1] = $langs->trans("Habilitations");
$head2[$h][2] = 'habilitation';
$h++;

$head2[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=autorisation';
$head2[$h][1] = $langs->trans("Autorisations");
$head2[$h][2] = 'autorisation';
$h++;

$head2[$h][0] = dol_buildpath("/formationhabilitation/userformation.php", 1).'?id='.$object->id.'&onglet=volet';
$head2[$h][1] = $langs->trans("Volets");
$head2[$h][2] = 'volet';
$h++;

if(empty($onglet) || $onglet == 'formation'){
    print dol_get_fiche_head($head2, 'formation', $title, -1, 'user');

    $css_div = 'min-height: 520px;';
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
    print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
}
elseif($onglet == 'habilitation'){
    print dol_get_fiche_head($head2, 'habilitation', $title, -1, 'user');

    // Show Formation lines
    $result = $object->getLinesArrayFormationHabilitation('habilitation');
    $object->table_element_line = 'formationhabilitation_userhabilitation';

    print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
    <input type="hidden" name="token" value="' . newToken().'">
    <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
    <input type="hidden" name="mode" value="">
    <input type="hidden" name="page_y" value="">
    <input type="hidden" name="id" value="' . $object->id.'">
    <input type="hidden" name="fk_user" value="' . $object->id.'">
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
elseif($onglet == 'autorisation'){
    print dol_get_fiche_head($head2, 'autorisation', $title, -1, 'user');

    // Show Formation lines
    $result = $object->getLinesArrayFormationHabilitation('autorisation');
    $object->table_element_line = 'formationhabilitation_userautorisation';

    print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
    <input type="hidden" name="token" value="' . newToken().'">
    <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
    <input type="hidden" name="mode" value="">
    <input type="hidden" name="page_y" value="">
    <input type="hidden" name="id" value="' . $object->id.'">
    <input type="hidden" name="fk_user" value="' . $object->id.'">
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
elseif($onglet == 'volet') {
    $formfile = new FormFile($db);
    $upload_dir = $conf->export->dir_temp.'/'.$user->id;

    print dol_get_fiche_head($head2, 'volet', $title, -1, 'user');
    
    print '<div class="fichecenter">';
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action=confirm_genererPdf">';
            print '<input type="hidden" name="confirm" value="yes">';
            print '<input type="hidden" name="onglet" value="volet">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';
            print '<input type="hidden" name="fk_user" value="'.$object->id.'">';


            $urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id.'&onglet=volet';
        
            $filedir = $conf->formationhabilitation->dir_output.'/'.$object->id;
            $genallowed = 1; // LENYTODO
            $delallowed = 1; // LENYTODO
        
            include_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/modules/formationhabilitation/modules_formationhabilitation_user.php';
            print $formfile->showdocuments('formationhabilitation_user', '', $filedir, $urlsource, $genallowed, $delallowed, '', 1, 0, 0, 0, 1, '', 'Volets');

            print '<div class="tabsAction">'."\n";
            // Generer PDF
            $voletarray = $objectparentline->getallVolet();
            print $form->selectarray('voletid', $voletarray, $voletid, 1);
            if($permissiontoaddline) {
                print '<input type="submit" value="'.$langs->trans("GenererDoc").'" class="button"/>';
            }
            print '</div>'."\n";
        print '</form>';
    print '</div>';
}

print '</div>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();

