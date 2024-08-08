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

global $langs, $user;

$formationId = $_POST['formationId'];
$organismeId = $_POST['organismeId'];

$societe = new Societe($db);
$fk_societe = '';
$hour = '';
$min = '';

if ($formationId > 0) {
    // Récupérer les sous-catégories
    $sql = "SELECT f.fournisseur, f.nombre_heure";
    $sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_formation as f";
    $sql .= " WHERE f.rowid = $formationId";

    $resql = $db->query($sql);

    if ($resql) {
        $obj = $db->fetch_object($resql);
        $fournisseurs = explode(",", $obj->fournisseur);
        $time = explode(':', convertSecondToTime($obj->nombre_heure, 'allhourmin'));
        $hour = $time[0];
        $min = $time[1];

        foreach($fournisseurs as $fournisseur_id) {
            $societe->fetch($fournisseur_id);
            $fk_societe .= '<option'.($fournisseur_id == $organismeId ? ' selected' : '').' value="' . $societe->id . '">' . $societe->name . '</option>';
        }
    } else {
        $fk_societe .= '<option value="">Aucun organisme de formation</option>';
    }
} else {
    $fk_societe .= '<option value="">Sélectionnez une formation</option>';
}

// Préparer la réponse
$response = [
    'fk_societe' => $fk_societe,
    'hour' => $hour,
    'min' => $min
];

echo json_encode($response);

?>
