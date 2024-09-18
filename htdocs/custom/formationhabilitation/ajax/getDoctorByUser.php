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

require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';

global $langs, $user;

$userId = $_POST['userId'];

$fk_contact = '';
$naturevisite = '';
$centremedecine = '';

if ($userId > 0) {
    $extrafields = new Extrafields($db);
    $extrafields->fetch_name_optionals_label('donneesrh_Medecinedutravail');
    $userField = new UserField($db);
    $userField->id = $userId;
    $userField->table_element = 'donneesrh_Medecinedutravail';
    $userField->fetch_optionals();

    if ($userField->array_options['options_docteur']) {
        $fk_contact = $userField->array_options['options_docteur'];
    }

    if ($userField->array_options['options_naturevisitemedicale']) {
        $naturevisite = $userField->array_options['options_naturevisitemedicale'];
    }

    if ($userField->array_options['options_medecinedutravail']) {
        $centremedecine = $userField->array_options['options_medecinedutravail'];
    }
}

// Préparer la réponse
$response = [
    'fk_contact' => $fk_contact,
    'naturevisite' => $naturevisite,
    'centremedecine' => $centremedecine
];

echo json_encode($response);

?>
