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

//---------------------------------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/ot.class.php';

// Récupérer l'ID de la carte à partir du contexte de Dolibarr
$id = GETPOST('id', 'int');

if ($id > 0) {
    if ($id > 0) {
        $langs->loadLangs(array("ot@ot", "other"));
    
        // Requête SQL
        $sql = "SELECT u.firstname, u.lastname, ctc.libelle, sp.fk_c_type_contact 
                FROM ".MAIN_DB_PREFIX."element_contact as sp 
                JOIN ".MAIN_DB_PREFIX."user as u ON sp.fk_socpeople = u.rowid 
                JOIN ".MAIN_DB_PREFIX."c_type_contact as ctc ON sp.fk_c_type_contact = ctc.rowid 
                WHERE sp.element_id = $id 
                AND sp.statut = 4";
    
        $resql = $db->query($sql);
    
        if ($resql) {
            $contactprojet = array();
            while ($obj = $db->fetch_object($resql)) {
                $contactprojet[] = $obj;
            }
    
            // Si le tableau est vide, renvoyer un message JSON approprié
            if (empty($contactprojet)) {
                header('Content-Type: application/json');
                echo json_encode(array('message' => 'Aucun contact trouvé.'));
            } else {
                header('Content-Type: application/json');
                echo json_encode($contactprojet);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(array('error' => 'Erreur dans la requête SQL: ' . $db->lasterror()));
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'ID non valide'));
    }
}



?>