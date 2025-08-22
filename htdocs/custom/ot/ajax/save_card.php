<?php
/* Copyright (C) 2024 Faure Louis <l.faure@optim-industries.fr>
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

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

// Load Dolibarr environment
$res = 0;
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception("Données invalides");
    }

    $otId = intval($data['otid']);
    $cardsData = $data['cardsData'] ?? [];
    $selectedContacts = $data['selectedContacts'] ?? [];

    if (!$otId) {
        throw new Exception("ID OT manquant");
    }

    // Commencer une transaction
    $db->begin();

    // 1. Nettoyer les anciennes données
    // Supprimer les anciennes cellules
    $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE ot_id = " . intval($otId);
    if (!$db->query($sql)) {
        throw new Exception("Erreur lors de la suppression des anciennes cellules");
    }

    // Supprimer les anciens sous-traitants
    $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants WHERE ot_id = " . intval($otId);
    if (!$db->query($sql)) {
        throw new Exception("Erreur lors de la suppression des anciens sous-traitants");
    }

    // 2. Insérer les nouvelles données
    if (!empty($cardsData)) {
        foreach ($cardsData as $card) {
            if (isset($card['type']) && in_array($card['type'], ['card', 'list', 'listeunique', 'cardprincipale'])) {
                // Insérer la cellule
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule 
                        (ot_id, title, type, x, y) 
                        VALUES (" . intval($otId) . ", 
                                '" . $db->escape($card['title'] ?? '') . "', 
                                '" . $db->escape($card['type']) . "', 
                                " . intval($card['x'] ?? 0) . ", 
                                " . intval($card['y'] ?? 0) . ")";
                
                if (!$db->query($sql)) {
                    throw new Exception("Erreur lors de l'insertion de la cellule: " . $db->lasterror());
                }

                $celluleId = $db->last_insert_id(MAIN_DB_PREFIX . "ot_ot_cellule");

                // Insérer les données des utilisateurs pour les cellules
                if ($card['type'] == 'card' && !empty($card['userId'])) {
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                            (ot_cellule_id, fk_user) 
                            VALUES (" . intval($celluleId) . ", 
                                    " . intval($card['userId']) . ")";
                    
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion des données utilisateur");
                    }
                }

                // Insérer les utilisateurs pour les listes
                if (in_array($card['type'], ['list', 'listeunique']) && !empty($card['userIds'])) {
                    foreach ($card['userIds'] as $userId) {
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                                (ot_cellule_id, fk_user) 
                                VALUES (" . intval($celluleId) . ", " . intval($userId) . ")";
                        
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de l'insertion des utilisateurs de liste");
                        }
                    }
                }

                // Insérer les données pour les cartes principales
                if ($card['type'] == 'cardprincipale' && !empty($card['userId'])) {
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                            (ot_cellule_id, fk_user) 
                            VALUES (" . intval($celluleId) . ", " . intval($card['userId']) . ")";
                    
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion des données de carte principale");
                    }
                }
            }
        }
    }

    // 3. Insérer les sous-traitants
    if (!empty($selectedContacts)) {
        foreach ($selectedContacts as $contact) {
            // Vérifier que le contact n'existe pas déjà pour éviter les doublons
            $checkSql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                        WHERE ot_id = " . intval($otId) . " 
                        AND fk_socpeople = " . intval($contact['soc_people'] ?? 0);
            
            $checkResult = $db->query($checkSql);
            if ($checkResult) {
                $checkObj = $db->fetch_object($checkResult);
                if ($checkObj->count > 0) {
                    // Le contact existe déjà, mettre à jour au lieu d'insérer
                    $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                            SET fonction = '" . $db->escape($contact['fonction'] ?? '') . "', 
                                contrat = '" . $db->escape($contact['contrat'] ?? '') . "', 
                                habilitation = '" . $db->escape($contact['habilitation'] ?? '') . "'
                            WHERE ot_id = " . intval($otId) . " 
                            AND fk_socpeople = " . intval($contact['soc_people'] ?? 0);
                } else {
                    // Nouveau contact, insérer
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                            (ot_id, fk_socpeople, fk_societe, fonction, contrat, habilitation) 
                            VALUES (" . intval($otId) . ", 
                                    " . intval($contact['soc_people'] ?? 0) . ", 
                                    " . intval($contact['supplier_id'] ?? 0) . ", 
                                    '" . $db->escape($contact['fonction'] ?? '') . "', 
                                    '" . $db->escape($contact['contrat'] ?? '') . "', 
                                    '" . $db->escape($contact['habilitation'] ?? '') . "')";
                }
                
                if (!$db->query($sql)) {
                    throw new Exception("Erreur lors de la sauvegarde du sous-traitant: " . $db->lasterror());
                }
            }
        }
    }

    // Confirmer la transaction
    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Données sauvegardées avec succès'
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($db)) {
        $db->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
