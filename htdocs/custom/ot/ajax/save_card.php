<?php
/* Copyright (C) 2022 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *       \file       htdocs/ot/ajax/myobject.php
 *       \brief      File to return Ajax response on product list request
 */

 if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
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

$mode = GETPOST('mode', 'aZ09');

$data = json_decode(file_get_contents('php://input'), true);

// Vérifier le statut de l'OT
$sql = "SELECT status FROM " . MAIN_DB_PREFIX . "ot_ot WHERE rowid = " . intval($data['otid']);
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    $obj = $db->fetch_object($resql);
    if ($obj->status == 1 || $obj->status == 2) {
        // Si le statut est actif (1) ou terminé (2), on bloque la sauvegarde
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => 'Les modifications ne sont pas autorisées car l\'OT est active ou terminée.'));
        exit;
    }
}

// Commencer une transaction
$db->begin();

try {
    $insertedCellIds = [];
    $receivedCellIds = [];
    $receivedSubcontractors = []; // Stocker les sous-traitants reçus

    $otId = isset($data['otid']) ? intval($data['otid']) : 0;

    // Récupérer toutes les cellules existantes pour cet OT
    $existingCellIds = [];
    $sql = "SELECT id_cellule, rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE ot_id = $otId";
    $resql = $db->query($sql);
    while ($row = $db->fetch_object($resql)) {
        $existingCellIds[$row->id_cellule] = $row->rowid;
    }

    // Traiter chaque élément de `cardsData`
    if (isset($data['cardsData']) && is_array($data['cardsData'])) {
        foreach ($data['cardsData'] as $item) {
            $title = isset($item['title']) ? $db->escape($item['title']) : '';
            $type = isset($item['type']) ? $db->escape($item['type']) : 'card';
            $x = isset($item['x']) ? intval($item['x']) : 0;
            $y = isset($item['y']) ? intval($item['y']) : 0;
            $cellId = isset($item['id']) ? $db->escape($item['id']) : '';
            $role = isset($item['role']) ? $db->escape($item['role']) : '';
            $userId = isset($item['userId']) ? intval($item['userId']) : null;

            $receivedCellIds[] = $cellId; // Stocker les ID reçus

            if ($type !== 'listesoustraitant') {
                if (isset($existingCellIds[$cellId])) {
                    $rowid = $existingCellIds[$cellId];

                    // Mise à jour de la cellule existante
                    $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_cellule 
                            SET x = $x, y = $y, type = '$type', title = '$title' 
                            WHERE rowid = $rowid AND ot_id = $otId";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de la mise à jour de la cellule : " . $db->lasterror());
                    }
                } else {
                    // Insérer une nouvelle cellule
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule (ot_id, x, y, type, title, id_cellule)
                            VALUES ($otId, $x, $y, '$type', '$title', '$cellId')";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule : " . $db->lasterror());
                    }

                    $rowid = $db->last_insert_id(MAIN_DB_PREFIX . 'ot_ot_cellule');
                    $insertedCellIds[] = $rowid;
                    $existingCellIds[$cellId] = $rowid;
                }
            }

            // Gestion des rôles principaux (RA, Q3, PCR)
            if ($type === 'cardprincipale' && in_array($role, ['RA', 'Q3', 'PCR'])) {
                // Vérifier si une cellule existe déjà pour ce rôle
                $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE ot_id = $otId AND type = '$type' AND title = '$role'";
                $resql = $db->query($sql);
                $row = $resql ? $db->fetch_object($resql) : null;
            
                if (!$row) {
                    // Insérer une nouvelle cellule
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule (ot_id, type, title) 
                            VALUES ($otId, '$type', '$role')";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule : " . $db->lasterror());
                    }
                    $otCelluleId = $db->last_insert_id(MAIN_DB_PREFIX . 'ot_ot_cellule');
            
                    // Insérer dans ot_ot_cellule_donne même si l'utilisateur est null
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user, role) 
                            VALUES ($otCelluleId, " . ($userId !== null ? $userId : "NULL") . ", '$role')";
                    if (!$db->query($sql)) {
                        throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule_donne : " . $db->lasterror());
                    }
                } else {
                    // Si la cellule existe déjà, mettre à jour l'utilisateur associé
                    $otCelluleId = $row->rowid;
            
                    // Vérifier si une entrée existe déjà dans ot_ot_cellule_donne
                    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId AND role = '$role'";
                    $resql = $db->query($sql);
                    $donneRow = $resql ? $db->fetch_object($resql) : null;
            
                    if ($donneRow) {
                        // Mettre à jour l'utilisateur si une entrée existe déjà
                        $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                                SET fk_user = " . ($userId !== null ? $userId : "NULL") . " 
                                WHERE rowid = " . $donneRow->rowid;
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la mise à jour de l'utilisateur dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    } else {
                        // Insérer une nouvelle entrée dans ot_ot_cellule_donne si elle n'existe pas
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user, role) 
                                VALUES ($otCelluleId, " . ($userId !== null ? $userId : "NULL") . ", '$role')";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de l'insertion dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    }
                }
            }

            // Gestion des userId pour le type 'card' (ajout ou mise à jour)
            if ($type === 'card' && isset($item['userId'])) {
                $userid = intval($item['userId']);
                if (isset($existingCellIds[$cellId])) {
                    $otCelluleId = $existingCellIds[$cellId];

                    // Vérifier si un userid est déjà enregistré
                    $sql = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId";
                    $resql = $db->query($sql);
                    $existingUser = $resql ? $db->fetch_object($resql) : null;

                    if ($existingUser) {
                        // Mettre à jour si le userId est différent
                        if ($existingUser->fk_user != $userid) {
                            $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_cellule_donne SET fk_user = $userid WHERE ot_cellule_id = $otCelluleId";
                            if (!$db->query($sql)) {
                                throw new Exception("Erreur lors de la mise à jour du userId dans ot_ot_cellule_donne : " . $db->lasterror());
                            }
                        }
                    } else {
                        // Insérer un nouveau userId
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user) VALUES ($otCelluleId, $userid)";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de l'insertion du userId dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    }
                }
            }

            // GESTION DES userIds POUR LES LISTES ET LISTEUNIQUE
            if (($type === 'list' || $type === 'listeunique') && isset($item['userIds']) && is_array($item['userIds'])) {
                $userIds = $item['userIds']; // Récupère les userIds depuis le JSON
                if (isset($existingCellIds[$cellId])) {
                    $otCelluleId = $existingCellIds[$cellId];

                    // Récupérer les userIds existants dans la base de données
                    $sql = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId";
                    $resql = $db->query($sql);
                    $existingUserIds = [];
                    while ($row = $db->fetch_object($resql)) {
                        $existingUserIds[] = $row->fk_user;
                    }

                    // Trouver les userIds à supprimer (ceux qui ne sont plus dans le JSON)
                    $userIdsToDelete = array_diff($existingUserIds, $userIds);
                    if (!empty($userIdsToDelete)) {
                        $userIdsToDeleteString = implode(',', $userIdsToDelete);
                        // Supprimer les userIds non présents dans le JSON
                        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = $otCelluleId AND fk_user IN ($userIdsToDeleteString)";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la suppression des userIds obsolètes dans ot_ot_cellule_donne : " . $db->lasterror());
                        }
                    }

                    // Ajouter les nouveaux userIds qui ne sont pas encore enregistrés
                    foreach ($userIds as $userid) {
                        if (!in_array($userid, $existingUserIds)) {
                            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne (ot_cellule_id, fk_user) VALUES ($otCelluleId, $userid)";
                            if (!$db->query($sql)) {
                                throw new Exception("Erreur lors de l'insertion du userId dans ot_ot_cellule_donne : " . $db->lasterror());
                            }
                        }
                    }
                }
            }

            // Gestion des sous-traitants pour les listes de type 'listesoustraitant'
            if ($type === 'listesoustraitant' && isset($item['soustraitants']) && is_array($item['soustraitants'])) {
                foreach ($item['soustraitants'] as $soustraitant) {
                    $fk_socpeople = intval($soustraitant['soc_people']);
                    $fk_societe = $db->escape($soustraitant['supplier_id']);
                    $fonction = $db->escape($soustraitant['fonction']);
                    $contrat = $db->escape($soustraitant['contrat']);
                    $habilitation = $db->escape($soustraitant['habilitation']);

                    $receivedSubcontractors[] = $fk_socpeople; // Stocker les sous-traitants reçus

                    // Vérifier si le sous-traitant est déjà enregistré
                    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                            WHERE ot_id = $otId AND fk_socpeople = $fk_socpeople";
                    $resql = $db->query($sql);

                    if ($resql && $db->num_rows($resql) > 0) {
                        $row = $db->fetch_object($resql);
                        $rowid = $row->rowid;

                        // Mise à jour des informations du sous-traitant
                        $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                                SET fonction = '$fonction', contrat = '$contrat', habilitation = '$habilitation', fk_societe = '$fk_societe'
                                WHERE rowid = $rowid AND ot_id = $otId";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la mise à jour du sous-traitant : " . $db->lasterror());
                        }
                    } else {
                        // Insérer un nouveau sous-traitant
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_sous_traitants (ot_id, fk_socpeople, fonction, contrat, habilitation, fk_societe) 
                                VALUES ($otId, $fk_socpeople, '$fonction', '$contrat', '$habilitation', '$fk_societe')";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de l'insertion du sous-traitant : " . $db->lasterror());
                        }
                    }
                }

                // Supprimer les sous-traitants non reçus, mais uniquement ceux qui ne proviennent pas du projet
                    $sql = "SELECT fk_socpeople FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants WHERE ot_id = $otId";
                    $resql = $db->query($sql);
                    $existingSubcontractors = [];

                    while ($row = $db->fetch_object($resql)) {
                        $existingSubcontractors[] = $row->fk_socpeople;
                    }

                    // Ne supprimer que les sous-traitants qui ne sont pas dans les données reçues ET qui ne proviennent pas du projet
                    $subcontractorsToDelete = array_diff($existingSubcontractors, $receivedSubcontractors);
                    if (!empty($subcontractorsToDelete)) {
                        $subcontractorsToDeleteString = implode(',', $subcontractorsToDelete);

                        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants WHERE ot_id = $otId AND fk_socpeople IN ($subcontractorsToDeleteString)";
                        if (!$db->query($sql)) {
                            throw new Exception("Erreur lors de la suppression des sous-traitants obsolètes : " . $db->lasterror());
                        }
                    }
            }


            
            
        }
    }

    // Supprimer les cellules non reçues
    $cellsToDelete = array_diff(array_keys($existingCellIds), $receivedCellIds);
    if (!empty($cellsToDelete)) {
        $cellsToDeleteString = implode("','", $cellsToDelete);

        // Récupérer les ID des cellules à supprimer
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE ot_id = $otId AND id_cellule IN ('$cellsToDeleteString')";
        $resql = $db->query($sql);
        $cellIdsToDelete = [];

        while ($row = $db->fetch_object($resql)) {
            $cellIdsToDelete[] = $row->rowid;
        }

        if (!empty($cellIdsToDelete)) {
            $cellIdsToDeleteString = implode(',', $cellIdsToDelete);
        
            // Supprimer les entrées dans ot_ot_cellule_donne
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id IN ($cellIdsToDeleteString)";
            if (!$db->query($sql)) {
                throw new Exception("Erreur lors de la suppression des userId liés aux cellules supprimées : " . $db->lasterror());
            }
        
            // Supprimer les cellules
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ot_ot_cellule WHERE rowid IN ($cellIdsToDeleteString)";
            if (!$db->query($sql)) {
                throw new Exception("Erreur lors de la suppression des cellules : " . $db->lasterror());
            }
        }
    }

    // Valider la transaction
    $db->commit();

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    $db->rollback();
    error_log($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

?>
