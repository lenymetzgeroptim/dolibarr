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

        

        if ($action == 'USERHABILITATION_VALIDATE') {
            

            // Validate object properties
            if (isset($object->fk_user)) {
                
                // Fetch all projects where the user is listed as a contact and fk_statut = 1 (open), excluding fk_c_type_contact = 155
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

                   

                    // Generate a new OT for each project
                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                } else {
                   
                }
            } 
        }

        if ($action == 'USERHABILITATION_SUSPEND') {
            // Validate object properties
            if (isset($object->fk_user)) {
                // Fetch all projects where the user is listed as a contact and fk_statut = 1 (open), excluding fk_c_type_contact = 155
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

                    // Generate a new OT for each project
                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'USERHABILITATION_UNSUSPEND') {
            // Validate object properties
            if (isset($object->fk_user)) {
                // Fetch all projects where the user is listed as a contact and fk_statut = 1 (open), excluding fk_c_type_contact = 155
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

                    // Generate a new OT for each project
                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'USERHABILITATION_DELETE') {
            // Validate object properties
            if (isset($object->fk_user)) {
                // Fetch all projects where the user is listed as a contact and fk_statut = 1 (open), excluding fk_c_type_contact = 155
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

                    // Generate a new OT for each project
                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
                    }
                }
            }
        }

        if ($action == 'USERHABILITATION_CLOSE') {
            // Validate object properties
            if (isset($object->fk_user)) {
                // Fetch all projects where the user is listed as a contact and fk_statut = 1 (open), excluding fk_c_type_contact = 155
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

                    // Generate a new OT for each project
                    foreach ($projects as $projectId) {
                        $this->createOTForProject($projectId, $object->fk_user);
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
        $sql = "SELECT MAX(indice) as max_indice FROM " . MAIN_DB_PREFIX . "ot_ot WHERE fk_project = " . intval($projectId);
  
        $resql = $db->query($sql);
        $maxIndice = 0;
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->max_indice !== null) {
                $maxIndice = $obj->max_indice;
            }
        }

        // Archive the last OT
        if ($maxIndice > 0) {
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

        
    }
}
