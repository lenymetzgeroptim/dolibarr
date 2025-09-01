<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modMyModule_MyModuleTriggers.class.php
 * \ingroup mymodule
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modMyModule_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

/**
 *  Class of triggers for MyModule module
 */
class InterfaceOTTriggers extends DolibarrTriggers
{
    public function __construct($db)
    {
        global $conf;
        $this->db = $db;
        $this->name = 'InterfaceModulename';
        $this->family = "modulename";
        $this->description = "Triggers pour le module custom.";
        $this->version = 'dolibarr';
        $this->picto = 'modulename@modulename';
    }

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        global $db;

        dol_syslog("Trigger action: ".$action, LOG_DEBUG);

        if ($action == 'USERHABILITATION_VALIDATE') {
            if (isset($object->fk_user)) {
                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid 
                        WHERE ec.fk_socpeople = " . intval($object->fk_user) . " 
                          AND p.fk_statut = 1 
                          AND ec.fk_c_type_contact != 155";

                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }

                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'USERHABILITATION_SUSPEND') {
            if (isset($object->fk_user)) {
                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid 
                        WHERE ec.fk_socpeople = " . intval($object->fk_user) . " 
                          AND p.fk_statut = 1 
                          AND ec.fk_c_type_contact != 155";

                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }

                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'USERHABILITATION_UNSUSPEND') {
            if (isset($object->fk_user)) {
                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid 
                        WHERE ec.fk_socpeople = " . intval($object->fk_user) . " 
                          AND p.fk_statut = 1 
                          AND ec.fk_c_type_contact != 155";

                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }

                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }
            
        if ($action == 'USERHABILITATION_DELETE') {
            if (isset($object->fk_user)) {
                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid 
                        WHERE ec.fk_socpeople = " . intval($object->fk_user) . " 
                          AND p.fk_statut = 1 
                          AND ec.fk_c_type_contact != 155";

                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }

                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'USERHABILITATION_CLOSE') {
            if (isset($object->fk_user)) {
                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid 
                        WHERE ec.fk_socpeople = " . intval($object->fk_user) . " 
                          AND p.fk_statut = 1 
                          AND ec.fk_c_type_contact != 155";

                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }

                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'LINEHABILITATION_DELETE') {
            if (isset($object->fk_user)) {
                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid 
                        WHERE ec.fk_socpeople = " . intval($object->fk_user) . " 
                          AND p.fk_statut = 1 
                          AND ec.fk_c_type_contact != 155";

                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }

                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'LINE_DELETE') {
            if (isset($object->id)) {

                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid 
                        WHERE ec.fk_socpeople = " . intval($object->id) . " 
                          AND p.fk_statut = 1 
                          AND ec.fk_c_type_contact != 155 ";
                          
                       

                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }
                   

                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->id);
                        
                    }
                }
                
             
            }
        }
        return 1;
    }

    private function createOTForProject($projectId, $userId)
    {
        global $db, $user;

        $project = new Project($db);
        if (!$project->fetch($projectId)) {
            return;
        }

        $dateCreation = date('Y-m-d H:i:s');

        // Fetch project reference
        $sql = "SELECT ref FROM " . MAIN_DB_PREFIX . "projet WHERE rowid = " . intval($projectId);
        $resql = $db->query($sql);
        $projectRef = '';
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $projectRef = $obj->ref;
            }
        }

        // Generate unique OT reference
        $lastFiveChars = substr($projectRef, -5);
        $sql = "SELECT rowid, MAX(indice) as max_indice FROM " . MAIN_DB_PREFIX . "ot_ot WHERE fk_project = " . intval($projectId) . " GROUP BY fk_project";
        $resql = $db->query($sql);
        $maxIndice = 0;
        $previousOTId = null;
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->max_indice !== null) {
                $maxIndice = $obj->max_indice;
                // Récupérer l'ID de l'OT précédent
                $sqlPrevious = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot WHERE fk_project = " . intval($projectId) . " AND indice = " . intval($maxIndice);
                $resqlPrevious = $db->query($sqlPrevious);
                if ($resqlPrevious) {
                    $objPrevious = $db->fetch_object($resqlPrevious);
                    if ($objPrevious) {
                        $previousOTId = $objPrevious->rowid;
                    }
                }
            }
        }

        // Archive the last OT
        if ($maxIndice > 0 && $previousOTId) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot 
                    SET status = 2  -- Archived status
                    WHERE fk_project = " . intval($projectId) . " AND indice = " . intval($maxIndice);
            $resql = $db->query($sql);
            if (!$resql) {
                return;
            }
        }

        // Create the new OT
        $newIndice = $maxIndice + 1;
        $otRef = $lastFiveChars . ' OT ' . $newIndice;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot 
                (fk_project, fk_user_creat, date_creation, indice, ref, status, tms) 
                VALUES (
                    " . intval($projectId) . ", 
                    " . intval($userId) . ", 
                    '" . $db->escape($dateCreation) . "', 
                    " . intval($newIndice) . ", 
                    '" . $db->escape($otRef) . "', 
                    0, 
                    NOW()
                )";

        $resql = $db->query($sql);
        if (!$resql) {
            return;
        }

        // Récupérer l'ID du nouvel OT créé
        $newOTId = $db->last_insert_id(MAIN_DB_PREFIX . "ot_ot");

        // Cloner l'architecture de l'OT précédent si il existe
        if ($previousOTId && $newOTId) {
            $this->cloneOTArchitecture($previousOTId, $newOTId);
        }
    }

    /**
     * Clone l'architecture d'un OT précédent vers un nouvel OT
     * 
     * @param int $previousOTId ID de l'OT précédent à cloner
     * @param int $newOTId ID du nouvel OT
     */
    private function cloneOTArchitecture($previousOTId, $newOTId)
    {
        global $db;

        try {
            // 1. Cloner les cellules (ot_ot_cellule) - exclure les listes uniques
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule 
                    (ot_id, title, type, x, y)
                    SELECT 
                        " . intval($newOTId) . ",
                        title,
                        type,
                        x,
                        y
                    FROM " . MAIN_DB_PREFIX . "ot_ot_cellule 
                    WHERE ot_id = " . intval($previousOTId) . "
                    AND type != 'listeunique'";

            $resql = $db->query($sql);
            if (!$resql) {
                dol_syslog("Erreur lors du clonage des cellules: " . $db->lasterror(), LOG_ERR);
                return false;
            }

            // 2. Récupérer les mappings anciens/nouveaux IDs des cellules (sans les listes uniques)
            $cellMapping = array();
            $sql = "SELECT rowid, title, type, x, y FROM " . MAIN_DB_PREFIX . "ot_ot_cellule 
                    WHERE ot_id = " . intval($previousOTId) . " AND type != 'listeunique'";
            $resql = $db->query($sql);
            if ($resql) {
                $oldCells = array();
                while ($obj = $db->fetch_object($resql)) {
                    $oldCells[] = $obj;
                }

                // Récupérer les nouvelles cellules créées (sans les listes uniques)
                $sql = "SELECT rowid, title, type, x, y FROM " . MAIN_DB_PREFIX . "ot_ot_cellule 
                        WHERE ot_id = " . intval($newOTId) . " AND type != 'listeunique' ORDER BY rowid";
                $resql = $db->query($sql);
                if ($resql) {
                    $newCells = array();
                    while ($obj = $db->fetch_object($resql)) {
                        $newCells[] = $obj;
                    }

                    // Créer le mapping basé sur la position et le type
                    for ($i = 0; $i < count($oldCells) && $i < count($newCells); $i++) {
                        if ($oldCells[$i]->type == $newCells[$i]->type && 
                            $oldCells[$i]->x == $newCells[$i]->x && 
                            $oldCells[$i]->y == $newCells[$i]->y) {
                            $cellMapping[$oldCells[$i]->rowid] = $newCells[$i]->rowid;
                        }
                    }
                }
            }

            // 3. Cloner les données des cellules (ot_ot_cellule_donne) - sans habilitations et contrat
            foreach ($cellMapping as $oldCellId => $newCellId) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                        (ot_cellule_id, fk_user)
                        SELECT 
                            " . intval($newCellId) . ",
                            fk_user
                        FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                        WHERE ot_cellule_id = " . intval($oldCellId);

                $resql = $db->query($sql);
                if (!$resql) {
                    dol_syslog("Erreur lors du clonage des données de cellule " . $oldCellId . ": " . $db->lasterror(), LOG_ERR);
                }
            }

            // 4. Gestion des sous-traitants avec comparaison projet/OT précédent
            $this->cloneAndUpdateSubcontractors($previousOTId, $newOTId);

            dol_syslog("Architecture clonée avec succès de l'OT " . $previousOTId . " vers l'OT " . $newOTId, LOG_INFO);
            return true;

        } catch (Exception $e) {
            dol_syslog("Erreur lors du clonage de l'architecture: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Clone et met à jour les sous-traitants
     * 
     * @param int $previousOTId ID de l'OT précédent
     * @param int $newOTId ID du nouvel OT
     */
    private function cloneAndUpdateSubcontractors($previousOTId, $newOTId)
    {
        global $db;

        // Vérifier s'il y a déjà des données dans l'OT
        $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants WHERE ot_id = " . intval($newOTId);
        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj->count > 0) {
                // Il y a déjà des données, on ne fait rien
                dol_syslog("Sous-traitants déjà présents pour l'OT " . $newOTId . ", pas de clonage", LOG_INFO);
                return;
            }
        }

        // Récupérer le projet associé au nouvel OT
        $sql = "SELECT fk_project FROM " . MAIN_DB_PREFIX . "ot_ot WHERE rowid = " . intval($newOTId);
        $resql = $db->query($sql);
        if (!$resql) return;
        
        $obj = $db->fetch_object($resql);
        if (!$obj) return;
        
        $projectId = $obj->fk_project;

        // 1. D'abord, cloner tous les sous-traitants de l'OT précédent AVEC leurs données remplies
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                (ot_id, fk_socpeople, fk_societe, fonction, contrat, habilitation)
                SELECT 
                    " . intval($newOTId) . ",
                    fk_socpeople,
                    fk_societe,
                    fonction,
                    contrat,
                    habilitation
                FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                WHERE ot_id = " . intval($previousOTId);

        $resql = $db->query($sql);
        if (!$resql) {
            dol_syslog("Erreur lors du clonage des sous-traitants: " . $db->lasterror(), LOG_ERR);
            return;
        }

        // Compter combien de sous-traitants ont été clonés
        $clonedCount = $db->affected_rows();
        dol_syslog("Clonés " . $clonedCount . " sous-traitants depuis l'OT " . $previousOTId . " vers l'OT " . $newOTId, LOG_INFO);

        // 2. Ensuite, ajouter SEULEMENT les nouveaux sous-traitants du projet qui ne sont PAS dans l'OT précédent
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                (ot_id, fk_socpeople, fk_societe, fonction, contrat, habilitation)
                SELECT DISTINCT
                    " . intval($newOTId) . ",
                    ec.fk_socpeople,
                    sp.fk_soc,
                    NULL,
                    NULL,
                    NULL
                FROM " . MAIN_DB_PREFIX . "element_contact AS ec 
                JOIN " . MAIN_DB_PREFIX . "socpeople AS sp ON ec.fk_socpeople = sp.rowid 
                JOIN " . MAIN_DB_PREFIX . "c_type_contact AS ctc ON ec.fk_c_type_contact = ctc.rowid 
                WHERE ec.element_id = " . intval($projectId) . "
                AND ec.statut = 4
                AND ctc.element = 'project'
                AND ctc.source = 'external'
                AND ec.fk_socpeople NOT IN (
                    SELECT fk_socpeople 
                    FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                    WHERE ot_id = " . intval($newOTId) . "
                    AND fk_socpeople IS NOT NULL
                )";

        $resql = $db->query($sql);
        if (!$resql) {
            dol_syslog("Erreur lors de l'ajout des nouveaux sous-traitants: " . $db->lasterror(), LOG_ERR);
            return;
        }

        $newCount = $db->affected_rows();
        dol_syslog("Ajoutés " . $newCount . " nouveaux sous-traitants pour l'OT " . $newOTId, LOG_INFO);
    }
}
