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
        $sql = "SELECT MAX(indice) as max_indice FROM ".MAIN_DB_PREFIX."ot_ot WHERE fk_project = ".intval($projectId);
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

        $resql = $db->query($sql); // Exécuter l'INSERT
        if (!$resql) {
            setEventMessage("Erreur lors de la création de l'OT : " . $db->lasterror(), 'errors');
            return; // Stopper la fonction en cas d'échec
        }

        // Maintenant, on peut exécuter une autre requête sans écraser l'INSERT
        $sql = "SELECT ot_id,id_cellule,title,type,x,y FROM ".MAIN_DB_PREFIX."ot_ot_cellule";
        $resql = $db->query($sql);

        if ($resql) {
            setEventMessage("OT créé avec succès. Référence OT : " . $otRef, 'mesgs');
        } else {
            setEventMessage("Erreur lors de la création de l'OT : " . $db->lasterror(), 'errors');
        }
    }

}
