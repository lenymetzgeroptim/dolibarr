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

        dol_syslog("Trigger activé pour l'action : " . $action);

        if ($action == 'FORMATIONHABILITATION_USERHABILITATION_MODIFY') {
            if ($object->status == 2) { // Check if status changed to 2
                dol_syslog("Trigger activated for habilitation status change to 2 for user ID: " . $object->fk_user);

                // Fetch all projects where the user is listed as a contact
                $sql = "SELECT DISTINCT p.rowid 
                        FROM " . MAIN_DB_PREFIX . "projet AS p
                        INNER JOIN " . MAIN_DB_PREFIX . "element_contact AS ec 
                            ON ec.element_id = p.rowid
                        WHERE ec.fk_socpeople = " . intval($object->fk_user);
                $resql = $db->query($sql);

                if ($resql) {
                    $projects = [];
                    while ($obj = $db->fetch_object($resql)) {
                        $projects[] = $obj->rowid;
                    }

                    // Generate a new OT for each project
                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                } else {
                    dol_syslog("Error fetching projects for user ID: " . $object->fk_user . " - " . $db->lasterror(), LOG_ERR);
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
            dol_syslog("Erreur lors de la récupération du projet ID: " . $projectId, LOG_ERR);
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
        $sql = "SELECT MAX(indice) as max_indice FROM " . MAIN_DB_PREFIX . "ot_ot WHERE fk_project = " . intval($projectId);
        $resql = $db->query($sql);
        $maxIndice = 0;
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->max_indice !== null) {
                $maxIndice = $obj->max_indice;
            }
        }
        $newIndice = $maxIndice + 1;
        $otRef = $lastFiveChars . ' OT ' . $newIndice;

        // Insert new OT record
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
            dol_syslog("Erreur lors de la création de l'OT : " . $db->lasterror(), LOG_ERR);
            return;
        }

        dol_syslog("OT créé avec succès. Référence OT : " . $otRef);
    }
}
