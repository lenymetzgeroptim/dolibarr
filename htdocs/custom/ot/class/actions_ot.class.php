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
 * \file    htdocs/modulebuilder/template/class/actions_mymodule.class.php
 * \ingroup mymodule
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsMyModule
 */
class ActionsOT
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	public function formContactTpl($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user;

        // Gérer l'action de création d'OT via l'ajout de contact
        if ($action == 'create_ot_from_button') {
            $this->createOTForProject($object);
        }

        // Gérer la confirmation de suppression d'OT
        if ($action == 'delete_ot_from_button') {
            $this->createOTForProject($object);
            $this->notifyContactChange($object, 'remove');
        }

        // Afficher le JavaScript pour intercepter les clics de suppression
        if ($object->element == 'project') {
            $this->addDeleteContactConfirmationScript($object);
        }
    }

    /**
     * Hook to handle contact deletion from project
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user;

        // Gérer la suppression avec confirmation
        if ($action == 'deletecontact' && is_object($object) && $object->element == 'project') {
            if (GETPOST('confirm_delete_ot', 'alpha') == 'yes') {
                // L'utilisateur a confirmé, créer l'OT
                dol_syslog("ActionsOT: Contact deleted from project, creating new OT", LOG_DEBUG);
                $this->createOTForProject($object);
                $this->notifyContactChange($object, 'remove');
            }
        }

        // Check if this is an OT object
        if (get_class($object) === 'Ot' && $action == 'add') {
            // Nouvel OT créé, archiver l'ancien OT du même projet s'il existe
            $this->archivePreviousOT($object);
        }

        return 0;
    }

    /**
     * Ajouter le script JavaScript pour intercepter les suppressions de contact
     */
    private function addDeleteContactConfirmationScript($project)
    {
        global $langs;
        
        echo '<script type="text/javascript">
        $(document).ready(function() {
            // Intercepter tous les liens de suppression de contact
            $("a[href*=\"action=deletecontact\"]").click(function(e) {
                var href = $(this).attr("href");
                
                // Vérifier si ce n\'est pas déjà une confirmation
                if (href.indexOf("confirm_delete_ot") === -1) {
                    e.preventDefault();
                    
                    // Créer la popup stylisée
                    var popup = document.createElement("div");
                    popup.style.position = "fixed";
                    popup.style.top = "50%";
                    popup.style.left = "50%";
                    popup.style.transform = "translate(-50%, -50%)";
                    popup.style.backgroundColor = "#fff";
                    popup.style.border = "1px solid #ccc";
                    popup.style.padding = "30px";
                    popup.style.zIndex = "1000";
                    popup.style.boxShadow = "0 4px 8px rgba(0,0,0,0.1)";
                    popup.style.borderRadius = "5px";
                    popup.style.textAlign = "center";
                    popup.style.minWidth = "400px";
                    popup.innerHTML = `
                        <h3 style="margin-bottom: 20px; color: #333;">'.$langs->trans('Créer un OT').'</h3>
                        <p style="margin-bottom: 30px; color: #666; line-height: 1.5;">'.$langs->trans('Voulez-vous créer un OT lors de la suppression de ce contact du projet').' ' . $project->ref . ' ?</p>
                        <div style="display: flex; justify-content: center; gap: 15px;">
                            <button type="button" id="confirmDeleteOT" style="
                                background-color: rgb(40, 80, 139);
                                color: white;
                                border: 1px solid rgb(40, 80, 139);
                                padding: 8px 16px;
                                border-radius: 3px;
                                cursor: pointer;
                                font-size: 13px;
                                font-weight: bold;
                                text-transform: uppercase;
                                min-width: 80px;
                            ">'.$langs->trans('Confirmer').'</button>
                            <button type="button" id="cancelDeleteOT" style="
                                background-color: rgb(40, 80, 139);
                                color: white;
                                border: 1px solid rgb(40, 80, 139);
                                padding: 8px 16px;
                                border-radius: 3px;
                                cursor: pointer;
                                font-size: 13px;
                                font-weight: bold;
                                text-transform: uppercase;
                                min-width: 80px;
                            ">'.$langs->trans('Annuler').'</button>
                        </div>
                    `;
                    
                    // Ajouter un overlay pour assombrir le fond
                    var overlay = document.createElement("div");
                    overlay.style.position = "fixed";
                    overlay.style.top = "0";
                    overlay.style.left = "0";
                    overlay.style.width = "100%";
                    overlay.style.height = "100%";
                    overlay.style.backgroundColor = "rgba(0,0,0,0.5)";
                    overlay.style.zIndex = "999";
                    
                    document.body.appendChild(overlay);
                    document.body.appendChild(popup);

                    document.getElementById("confirmDeleteOT").addEventListener("click", function() {
                        // Supprimer la popup et rediriger avec confirmation
                        document.body.removeChild(popup);
                        document.body.removeChild(overlay);
                        window.location.href = href + "&confirm_delete_ot=yes";
                    });

                    document.getElementById("cancelDeleteOT").addEventListener("click", function() {
                        // Supprimer la popup et rediriger sans créer d\'OT
                        document.body.removeChild(popup);
                        document.body.removeChild(overlay);
                        window.location.href = href + "&confirm_delete_ot=no";
                    });
                }
            });
        });
        </script>';
    }

    private function notifyContactChange($project, $actionType)
    {
        global $langs, $conf, $user;

        // Charger les informations du projet et du contact
        $projectRef = $project->ref;
        $contactId = GETPOST('contactid', 'int'); // Récupère l'ID du contact dans la requête

        // Définir le message de notification en fonction de l'action
        if ($actionType == 'add') {
            $subject = "Contact ajouté au projet " . $projectRef;
            setEventMessages($langs->trans("bravoo"), null, 'mesgs');
        } elseif ($actionType == 'remove') {
            $subject = "Contact supprimé du projet " . $projectRef;
           
        }

        // Exemple : Envoyer un mail ou afficher dans le log
        dol_syslog($subject, LOG_INFO);
        
    }

    private function createOTForProject($project)
    {
        global $db, $user;

        $projectId = $project->id;
        $userId = $user->id;
        $dateCreation = date('Y-m-d H:i:s'); 

        // Récupérer la référence du projet
        $sql = "SELECT ref FROM ".MAIN_DB_PREFIX."projet WHERE rowid = ".intval($projectId);
        $resql = $db->query($sql);
        $projectRef = '';
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $projectRef = $obj->ref;
            }
        }

        // Formater la référence de l'OT avec le même système
        $lastFiveChars = substr($projectRef, -5); 
        $sql = "SELECT rowid, MAX(indice) as max_indice FROM ".MAIN_DB_PREFIX."ot_ot WHERE fk_project = ".intval($projectId)." GROUP BY fk_project";
        $resql = $db->query($sql);
        $maxIndice = 0;
        $previousOTId = null;
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->max_indice !== null) {
                $maxIndice = $obj->max_indice;
                // Récupérer l'ID de l'OT précédent
                $sqlPrevious = "SELECT rowid FROM ".MAIN_DB_PREFIX."ot_ot WHERE fk_project = ".intval($projectId)." AND indice = ".intval($maxIndice);
                $resqlPrevious = $db->query($sqlPrevious);
                if ($resqlPrevious) {
                    $objPrevious = $db->fetch_object($resqlPrevious);
                    if ($objPrevious) {
                        $previousOTId = $objPrevious->rowid;
                    }
                }
            }
        }
        $newIndice = $maxIndice + 1; 
        $otRef = $lastFiveChars . ' OT ' . $newIndice;

        // Mettre à jour le statut de l'OT précédent
        if ($maxIndice > 0) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."ot_ot SET status = 2 WHERE fk_project = ".intval($projectId)." AND indice = ".intval($maxIndice);
            $resql = $db->query($sql);
            if (!$resql) {
                setEventMessage("Erreur lors de la mise à jour du statut de l'OT précédent : " . $db->lasterror(), 'errors');
                return;
            }
        }

        // Insérer le nouvel enregistrement dans la table ot_ot
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."ot_ot 
        (fk_project, fk_user_creat, date_creation, indice, ref, status, date_applica_ot, fk_user_modif, last_main_doc, import_key, model_pdf, tms) 
        VALUES (
            ".intval($projectId).", 
            ".intval($userId).", 
            '".$db->escape($dateCreation)."', 
            ".intval($newIndice).", 
            '".$db->escape($otRef)."', 
            0, 
            NULL,      /* date_applica_ot */
            NULL,      /* fk_user_modif */
            NULL,      /* last_main_doc */
            NULL,      /* import_key */
            NULL,      /* model_pdf */
            NOW()      /* tms */
        )";

        $resql = $db->query($sql);
        if (!$resql) {
            setEventMessage("Erreur lors de la création de l'OT : " . $db->lasterror(), 'errors');
            return;
        }

        // Récupérer l'ID du nouvel OT créé
        $newOTId = $db->last_insert_id(MAIN_DB_PREFIX . "ot_ot");

        // Cloner l'architecture de l'OT précédent si il existe
        if ($previousOTId && $newOTId) {
            $this->cloneOTArchitecture($previousOTId, $newOTId);
        }

        setEventMessage("OT créé avec succès. Référence OT : " . $otRef, 'mesgs');
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
                setEventMessage("Erreur lors du clonage des cellules: " . $db->lasterror(), 'errors');
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
                    setEventMessage("Erreur lors du clonage des données de cellule " . $oldCellId . ": " . $db->lasterror(), 'errors');
                }
            }

            // 4. Gestion des sous-traitants avec comparaison projet/OT précédent
            $this->cloneAndUpdateSubcontractors($previousOTId, $newOTId);

            setEventMessage("Architecture de l'OT précédent clonée avec succès", 'mesgs');
            return true;

        } catch (Exception $e) {
            setEventMessage("Erreur lors du clonage de l'architecture: " . $e->getMessage(), 'errors');
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

        // 1. D'abord, cloner tous les sous-traitants de l'OT précédent
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
            setEventMessage("Erreur lors du clonage des sous-traitants: " . $db->lasterror(), 'errors');
        }

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
                    WHERE ot_id = " . intval($previousOTId) . "
                    AND fk_socpeople IS NOT NULL
                )";

        $resql = $db->query($sql);
        if (!$resql) {
            setEventMessage("Erreur lors de l'ajout des nouveaux sous-traitants: " . $db->lasterror(), 'errors');
        }
    }

    /**
     * Archive previous OT when a new one is created
     *
     * @param object $newOT The new OT object
     * @return int Return integer <0 if error, >0 if success
     */
    private function archivePreviousOT($newOT)
    {
        global $user;

        try {
            // Rechercher l'OT précédent du même projet
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ot_ot";
            $sql .= " WHERE fk_project = " . ((int) $newOT->fk_project);
            $sql .= " AND rowid != " . ((int) $newOT->id);
            $sql .= " AND status != 2"; // Pas déjà archivé (STATUS_ARCHIVED = 2)
            $sql .= " ORDER BY date_creation DESC";
            $sql .= " LIMIT 1";

            $resql = $this->db->query($sql);
            if ($resql) {
                if ($this->db->num_rows($resql)) {
                    $obj = $this->db->fetch_object($resql);
                    
                    // Charger l'ancien OT
                    require_once DOL_DOCUMENT_ROOT . '/custom/ot/class/ot.class.php';
                    $oldOT = new Ot($this->db);
                    $result = $oldOT->fetch($obj->rowid);
                    
                    if ($result > 0) {
                        // Archiver l'ancien OT
                        $result = $oldOT->setStatusArchived($user);
                        if ($result > 0) {
                            dol_syslog("Ancien OT " . $oldOT->ref . " archivé automatiquement", LOG_INFO);
                        } else {
                            dol_syslog("Erreur lors de l'archivage automatique de l'OT " . $oldOT->ref, LOG_ERR);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            dol_syslog("Erreur lors de l'archivage automatique: " . $e->getMessage(), LOG_ERR);
        }

        return 1;
    }

}
