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
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/convocation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/extendedhtml.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/uservolet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/volet.class.php';


// Translations
$langs->loadLangs(array("user", "formationhabilitation@formationhabilitation"));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');

$userid = GETPOST('id', 'integer');
$lineid   = GETPOST('lineid', 'int');
$onglet = GETPOST('onglet', 'aZ09');


//Permissions
$user_group = New UserGroup($db);
$societe = New Societe($db);

$user_group->fetch(0, "Responsable d'antenne");
$arrayUserRespAntenneGroup = $user_group->listUsersForGroup('', 1);
$userInRespAntenneGroup = in_array($user->id, $arrayUserRespAntenneGroup); // Utilisateur dans le groupe Responsable d'antenne

$societe->fetch($user->array_options['options_antenne']);
$arrayUserRespAntenne = $societe->getSalesRepresentatives($user, 1);
$userIsRespAntenne = in_array($user->id, $arrayUserRespAntenne); // Utilisateur commercial de l'antenne de ratachement
$arrayRespAntenneForMail = array_intersect($arrayUserRespAntenneGroup, $arrayUserRespAntenne);

$permissiontoaddline = $user->rights->formationhabilitation->formation->addline;
$permissiontoreadCout = $user->rights->formationhabilitation->formation->readcout;
$permissiontovalidatelines = $userInRespAntenneGroup && $userIsRespAntenne;



if (empty($conf->formationhabilitation->enabled)) accessforbidden();

$object = New ExtendedUser3($db);
if($userid > 0){
    $object->fetch($userid);
}
$form = new ExtendedForm($db);

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
    $objectline = new UserVolet($db);
    $objectparentline = new Volet($db);
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

include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline_init.tpl.php';

/*
 * Actions
 */


if($onglet == 'formation' || empty($onglet)){	
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
    if(GETPOST('fk_habilitation') > 0) {
        $habilitation_static = new Habilitation($db);
        $habilitation_static->fetch(GETPOST('fk_habilitation'));
    }

    if(GETPOST('fk_user') > 0) {
        $user_static = new User($db);
        $user_static->fetch(GETPOST('fk_user'));
    }
        
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userhabilitation.inc.php';
}

if($onglet == 'autorisation'){	
    if(GETPOST('fk_autorisation') > 0) {
        $autorisation_static = new Autorisation($db);
        $autorisation_static->fetch(GETPOST('fk_autorisation'));
    }

    if(GETPOST('fk_user') > 0) {
        $user_static = new User($db);
        $user_static->fetch(GETPOST('fk_user'));
    }

    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_userautorisation.inc.php';
}

// Action pour générer un document
if ($onglet == 'volet') {
    if(GETPOST('fk_user') > 0) {
        $user_static = new User($db);
        $user_static->fetch(GETPOST('fk_user'));
    }

    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/actions_addupdatedelete_uservolet.inc.php';

    // // Delete file
    // if ($action == 'confirm_deletefile' && $confirm == 'yes') {
    //     $file = $conf->formationhabilitation->dir_output.'/'.$object->id."/".GETPOST('file'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

    //     $ret = dol_delete_file($file);
    //     if ($ret) {
    //         setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
    //     } else {
    //         setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
    //     }
    //     header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&onglet=volet');
    //     exit;
    // }
}

/*
 * View
 */

if ($onglet == 'volet') {
    print '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>'; 
    print '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
}

$help_url = '';
$page_name = "Formation - Habilitation";

llxHeader('', $page_name, $help_url, '', 0, 0, '', '', '', 'formationhabilitation classforhorizontalscrolloftabs');

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
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].($param ? '?'.$param : '').'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
}
if ($action == 'remove_file') {
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].($param ? '?'.$param : '').'&file='.urlencode(GETPOST("file")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
}
if ($action == 'programmer_formation') {
    $objectline->fetch($lineid);
    $formquestion = array(
                        array('label'=>$langs->trans('Formation') ,'type'=>'hidden', 'name'=>'fk_formation_programmer', 'value'=>$objectline->fk_formation),
                        array('label'=>$langs->trans('DateDebutFormation') ,'type'=>'datetime', 'name'=>'date_debut_formation_programmer', 'value'=>$objectline->date_debut_formation),
                        array('label'=>$langs->trans('DateFinFormation') ,'type'=>'datetime', 'name'=>'date_fin_formation_programmer', 'value'=>$objectline->date_fin_formation),
                        array('label'=>$langs->trans('InterneExterne') ,'type'=>'select', 'name'=>'interne_externe_programmer', 'values'=>$objectline->fields['interne_externe']['arrayofkeyval'], 'select_show_empty'=>0, 'default'=>1),
                        array('label'=>$langs->trans('Organisme') ,'type'=>'link', 'code'=>'fk_societe', 'name'=>'fk_societe_programmer', 'options'=>$objectline->fields['fk_societe']['type'], 'showempty'=>1, 'element'=>$objectline->element, 'module'=>$objectline->module),
                        array('label'=>$langs->trans('Formateur') ,'type'=>'link', 'code'=>'formateur', 'name'=>'formateur_programmer', 'options'=>$objectline->fields['formateur']['type'], 'showempty'=>1, 'element'=>$objectline->element, 'module'=>$objectline->module, 'hidden'=>1)
                    );
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].($param ? '?'.$param : '').'&lineid='.$lineid, $langs->trans('ProgrammerFormation'), $langs->trans('ConfirmProgrammerFormation'), 'confirm_programmer_formation', $formquestion, 0, 2);
}
if ($action == 'generation_auto') {
    if($onglet == 'habilitation'){
        $object_to_generate = $objectparentline->generateHabilitationsForUser($object->id, null, $txtListHabilitation, 1);
    }
    elseif($onglet == 'autorisation'){
        $object_to_generate = $objectparentline->generateAutorisationsForUser($object->id, null, $txtListAutorisation, 1);
    }
    $formquestion = array(
                        array('label'=>$langs->trans($objectparentline->element.'togenerate') ,'type'=>'multiselect', 'name'=>'object_generate', 'values'=>$object_to_generate, 'default'=>array_keys($object_to_generate)),
                    );
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].($param ? '?'.$param : ''), $langs->trans('GenerateAuto'), $langs->trans('ConfirmGenerateAuto'.$objectparentline->element), 'confirm_generation_auto', $formquestion, 0, 2, 400, 700);
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

    $habilitation = new Habilitation($db);
    $habilitation_to_generate = $habilitation->generateHabilitationsForUser($objectline->fk_user, $objectline, $txtListHabilitation, 1);
    $autorisation = new Autorisation($db);
    $autorisation_to_generate = $autorisation->generateAutorisationsForUser($objectline->fk_user, $objectline, $txtListAutorisation, 1);

    $formquestion = array(array('label'=>'Résultat' ,'type'=>'select', 'name'=>'resultat_valider', 'default'=>$objectline->resultat, 'values' => $objectline->fields['resultat']['arrayofkeyval']),
                          array('label'=>'Numéro Certificat' ,'type'=>'text', 'name'=>'numero_certificat_valider', 'value'=>$objectline->numero_certificat),
                          array('label'=>$langs->trans('NotificationRespAntenne') ,'type'=>'checkbox', 'name'=>'notification_resp_anetenne', 'value'=>1),
                          array('label'=>$langs->trans('habilitationtogenerate') ,'type'=>'multiselect', 'name'=>'habilitation_generate', 'values'=>$habilitation_to_generate, 'default'=>array_keys($habilitation_to_generate)),
                          array('label'=>$langs->trans('autorisationtogenerate') ,'type'=>'multiselect', 'name'=>'autorisation_generate', 'values'=>$autorisation_to_generate, 'default'=>array_keys($autorisation_to_generate)),
                        );
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].($param ? '?'.$param : '').'&lineid='.$lineid, $langs->trans('ValiderFormation'), (!empty($txt_formationToClose) ? $langs->trans('ConfirmValiderFormation2', $txt_formationToClose) : $langs->trans('ConfirmValiderFormation')), 'confirm_valider_formation', $formquestion, 0, 2, 400, 700);
}
// Print form confirm
print $formconfirm;

unset($arrayfields['t.formateur']);
unset($objectline->fields['fk_user']);
unset($arrayfields['t.fk_user']);
if(!$permissiontoreadCout) {
    unset($objectline->fields['cout_pedagogique']);
    unset($objectline->fields['cout_mobilisation']);
    unset($objectline->fields['cout_annexe']);
    unset($objectline->fields['cout_total']);
    unset($arrayfields['t.cout_pedagogique']);
    unset($arrayfields['t.cout_mobilisation']);
    unset($arrayfields['t.cout_annexe']);
    unset($arrayfields['t.cout_total']);
}

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

    $contextpage = 'userformation';
    $css_table = 'min-height: 450px;';
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
    print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
}
elseif($onglet == 'habilitation'){
    print dol_get_fiche_head($head2, 'habilitation', $title, -1, 'user');

    $contextpage = 'userhabilitation';
    $css_table = 'min-height: 450px;';
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
    print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
    print '<input type="hidden" form="searchline" id="fk_user" name="fk_user" value="' . $object->id.'">';
}
elseif($onglet == 'autorisation'){
    print dol_get_fiche_head($head2, 'autorisation', $title, -1, 'user');

    $contextpage = 'userautorisation';
    $css_table = 'min-height: 450px;';
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
    print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
    print '<input type="hidden" form="searchline" id="fk_user" name="fk_user" value="' . $object->id.'">';
}
elseif($onglet == 'volet') {
    print dol_get_fiche_head($head2, 'volet', $title, -1, 'user');

    $contextpage = 'uservolet';
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
    print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
}

print '</div>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();

