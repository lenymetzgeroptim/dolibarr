<?php

// Ce code n'est pas utilisé. c'est un code pour créer un bouton pour généré des ot dans la page contact des projets.
// Actuellement le systeme de création d'ot est dans actions_ot.class.php et dans interface_99_modOT_OTTriggers.class.php
class ActionsOt
{ 
	/**
	 * formObjectOptions
	 *
	 * @param	array	$parameters		Parameters
	 * @param	Object	$object			Object
	 * @param	string	$action			Action
	 * @return bool
	 */
        public function formContactTpl($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user;

        // Ajouter un bouton pour créer un OT

            print dolGetButtonAction('', $langs->trans('Créer un OT'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=create_ot_from_button&token='.newToken(), '');

        

        // Gérer l'action de création d'OT via le bouton
        if ($action == 'create_ot_from_button') {
            // Appeler la fonction pour créer l'OT
            $this->createOTForProject($object);
        }

    }


    // private function notifyContactChange($project, $actionType)
    // {
    //     global $langs, $conf, $user;

    //     // Charger les informations du projet et du contact
    //     $projectRef = $project->ref;
    //     $contactId = GETPOST('contactid', 'int'); // Récupère l'ID du contact dans la requête

    //     // Définir le message de notification en fonction de l'action
    //     if ($actionType == 'add') {
    //         $subject = "Contact ajouté au projet " . $projectRef;
    //         setEventMessages($langs->trans("bravoo"), null, 'mesgs');
    //     } elseif ($actionType == 'remove') {
    //         $subject = "Contact supprimé du projet " . $projectRef;
    //         setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
    //     }

    //     // Exemple : Envoyer un mail ou afficher dans le log
    //     dol_syslog($subject . ': ' . $message);
        
    //     // Vous pouvez aussi utiliser une fonction pour envoyer un email ici si nécessaire.
    // }


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
        $maxIndice = 137;
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->max_indice !== null) {
                $maxIndice = $obj->max_indice;
            }
        }
        $newIndice = $maxIndice + 1; 
        $otRef = $lastFiveChars . ' OT ' . $newIndice;

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
        
        if ($resql) {
            setEventMessage("OT créé avec succès. Référence OT : " . $otRef, 'mesgs');
        } else {
            setEventMessage("Erreur lors de la création de l'OT : " . $db->lasterror(), 'errors');
        }
    }

}