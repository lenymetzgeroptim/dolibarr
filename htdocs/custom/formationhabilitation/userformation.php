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
$voletid   = GETPOST('voletid', 'int');

$permissiontoaddline = $user->rights->formationhabilitation->formation->addline;
$permissiontoreadCout = $user->rights->formationhabilitation->formation->readCout;

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

include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline_init.tpl.php';

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
    $objectline = new UserHabilitation($db);
	
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
    $objectline = new UserAutorisation($db);
	
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
    $formquestion = array(
                        array('label'=>$langs->trans('Formation') ,'type'=>'hidden', 'name'=>'fk_formation_programmer', 'value'=>$objectline->fk_formation),
                        array('label'=>$langs->trans('DateDebutFormation') ,'type'=>'datetime', 'name'=>'date_debut_formation_programmer', 'value'=>$objectline->date_debut_formation),
                        array('label'=>$langs->trans('DateFinFormation') ,'type'=>'datetime', 'name'=>'date_fin_formation_programmer', 'value'=>$objectline->date_fin_formation),
                        array('label'=>$langs->trans('InterneExterne') ,'type'=>'select', 'name'=>'interne_externe_programmer', 'values'=>$objectline->fields['interne_externe']['arrayofkeyval'], 'select_show_empty'=>0, 'default'=>1),
                        array('label'=>$langs->trans('Organisme') ,'type'=>'link', 'code'=>'fk_societe', 'name'=>'fk_societe_programmer', 'options'=>$objectline->fields['fk_societe']['type'], 'showempty'=>1, 'element'=>$objectline->element, 'module'=>$objectline->module),
                        array('label'=>$langs->trans('Formateur') ,'type'=>'link', 'code'=>'formateur', 'name'=>'formateur_programmer', 'options'=>$objectline->fields['formateur']['type'], 'showempty'=>1, 'element'=>$objectline->element, 'module'=>$objectline->module, 'hidden'=>1)
                    );
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

    $contextpage = 'userformation';
    $css_div = 'min-height: 520px;';
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
    print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
}
elseif($onglet == 'habilitation'){
    print dol_get_fiche_head($head2, 'habilitation', $title, -1, 'user');

    $contextpage = 'userhabilitation';
    //$css_div = 'min-height: 520px;';
    include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
    print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
}
elseif($onglet == 'autorisation'){
    print dol_get_fiche_head($head2, 'autorisation', $title, -1, 'user');

    $contextpage = 'userautorisation';
   //$css_div = 'min-height: 520px;';
   include DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/core/tpl/objectline.tpl.php';
   print '<input type="hidden" form="addline" id="fk_user" name="fk_user" value="' . $object->id.'">';
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

