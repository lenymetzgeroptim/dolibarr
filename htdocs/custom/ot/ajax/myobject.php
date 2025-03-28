<?php
/* Copyright (C) 2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * Ce fichier gère l'Ajax pour récupérer les fournisseurs et leurs contacts dans Dolibarr.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Désactive le renouvellement du token
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

// Load Dolibarr environment
require_once '../../../main.inc.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_clean(); // Supprime toute sortie parasite avant d'envoyer JSON
header('Content-Type: application/json'); // Force JSON

$mode = GETPOST('mode', 'aZ09');

// Vérification des droits d'accès
if (!$user->id) {
    accessforbidden("Vous devez être connecté pour accéder à cette page.");
}

// Charger la bibliothèque de base de données
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Configuration des entêtes pour JSON
top_httphead('application/json');

// Tableau de résultats
$arrayresult = array();

if ($mode == 'getSuppliersAndContacts') {
    // Requête pour récupérer les fournisseurs et leurs contacts
    $sql = "SELECT s.rowid AS supplier_id, s.nom AS supplier_name, sp.rowid AS contact_id, sp.firstname, sp.lastname
        FROM ".MAIN_DB_PREFIX."societe s
        LEFT JOIN ".MAIN_DB_PREFIX."societe_contacts sc ON sc.fk_soc = s.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."socpeople sp ON sp.rowid = sc.fk_socpeople
        WHERE s.fournisseur = 1";


    // Exécution de la requête
    $resql = $db->query($sql);
    if ($resql) {
        $suppliers = array();
        
        // Traitement des résultats
        while ($obj = $db->fetch_object($resql)) {
            // On organise les données des fournisseurs et contacts
            if (!isset($suppliers[$obj->supplier_id])) {
                $suppliers[$obj->supplier_id] = array(
                    'supplier_id' => $obj->supplier_id,
                    'supplier_name' => $obj->supplier_name,
                    'contacts' => array()
                );
            }
            
            // Ajouter le contact à la liste du fournisseur
            $suppliers[$obj->supplier_id]['contacts'][] = array(
                'contact_id' => $obj->contact_id,
                'firstname' => $obj->firstname,
                'lastname' => $obj->lastname
            );
        }
        
        // On retourne les données sous forme JSON
        $arrayresult['status'] = 'success';
        $arrayresult['data'] = array_values($suppliers);
    } else {
        // Erreur de la requête
        $arrayresult['status'] = 'error';
        $arrayresult['message'] = $db->lasterror();
    }
}

// Fermeture de la connexion à la base de données
$db->close();

// Retourne la réponse JSON
print json_encode($arrayresult);
?>
