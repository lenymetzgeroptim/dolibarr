<?php

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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
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

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/rgpd/class/adminrgpd.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$object = new AdminRgpd($db);
$extrafields = new ExtraFields($db);
$action = GETPOST('action');
$acceptedImg = GETPOST('acceptedImg');
$notAcceptedImg = GETPOST('notAcceptedImg');
$acceptedRgpd = GETPOST('acceptatedrgpd');


//CSS file :
print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/rgpd/css/rgpdStyle.css">';
$required = 'required';

/*
 * Actions met à jour la table avec les données collecter de la modal
 * Fait appel à la function rgpdUpdate de la class
 */
if ($action == 'submitRGPD') {
		if($acceptedRgpd !== '' && $acceptedImg !== '') {
			$required = '';
			$triggersRgpd = $object->rgpdUpdate($acceptedImg, $acceptedRgpd , $user->id);
		}
		
			if($triggersRgpd > 0) {
				header("Location: ".DOL_URL_ROOT."/index.php");
				exit;
		// 	}else {
		// 		setEventMessages("Erreur lors de l'enregistrement", null, 'errors');
		// 	}
		// }else {
		// 	setEventMessages("Erreur lors de l'enregistrement", null, 'errors');
		}	
	}


/*
 * View de la modal et des boutons
 */
// $contenuRGPD = $object->getTextRGPD();
$contenuRGPD = dolibarr_get_const($db, 'RGPD_PARAM');
$contenuDROIT = dolibarr_get_const($db, 'DROIT_PARAM');
$instructions = 'Avant d\'accéder à Dolibarr OPTIM Industries, veuillez consultez les réglementations RGPD et acceptez ou non l\'autorisation du droit à l\'image en cochant la case correspondate à votre choix :';

//Vue :
print '<h1>Réglementations RGPD</h1>';
print '<div class = "instructions">'. $instructions . '</div>';
print '<div class = "contenu">'. $contenuRGPD . '</div>';
print '<div class = "contenu">'. $contenuDROIT . '</div>';


// foreach($contenuRGPD as  $value) {
// 			print '<div class = "contenu">'. "$value" . '</div>';
// 		};

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="submitRGPD">';


print '<div class="checkbox">';
//Checkbox RGPD
	print '</br><label for="acceptatedrgpd"> Je déclare avoir pris connaissance du réglement RGPD.</label>';
	print '<input type="radio" id="acceptatedrgpd" name="acceptatedrgpd" required ></input>';

//Checkbox droit à l'image
	print '</br><label for="acceptedImg"> Je n\'autorise pas la société Optim industrie d\'exploiter mon image.</label>';
	print '<input type="hidden" id="notAcceptedImg" name="checked"></input>';
	print '<input type="radio" id="notAcceptedImg" name="acceptedImg" value=0 '.$required.'></input>';

	print '</br><label for="acceptedImg"> J\'autorise la société Optim industrie d\'exploiter mon image.</label>';
	print '<input type="hidden" id="acceptedImg" name="unchecked"></input>';
	print '<input type="radio" id="acceptedImg" name="acceptedImg" '.$required.'></input>';
print '</div>';

print '<div style="text-align: center;" class="center">';
print '<input type="submit" class="button" name="submit" value="Suivant"/>';
print '</div>';
print '</form>';



