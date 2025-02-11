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
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */

	 public function __construct($db)
    {
        global $conf;
        $this->db = $db;

        // Nom de la classe Trigger (doit correspondre au nom du fichier)
        $this->name = 'InterfaceModulename';
        $this->family = "modulename";
        $this->description = "Triggers pour le module custom.";
        $this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' ou 'mycompany'
        $this->picto = 'modulename@modulename'; // Icône du module
    }

    /**
     * Fonction principale pour exécuter les triggers
     *
     * @param string $action Type d'événement (ex: CONTACT_CREATE, CONTACT_DELETE)
     * @param object $object Objet concerné (ex: un contact, projet)
     * @param User $user Objet utilisateur qui exécute l'action
     * @param Translate $langs Objet de traduction pour les messages
     * @param Conf $conf Objet de configuration
     * @return int <0 si erreur, 0 si pas d'action effectuée, 1 si OK
     */
	public function runTrigger($action, $object, $user, $langs, $conf)
	{
		global $db;
	
		// Charger les traductions pour les notifications
		$langs->load("modulename@modulename");
	
		// Afficher dans les logs pour vérifier l'action déclenchée
		dol_syslog("Trigger activé pour l'action : " . $action);
	
		// Vérifier que l'objet est un contact dans un projet
		
		if ($object->element == 'project') {
			
			if ($action == 'PROJECT_ADD_CONTACT') {
				setEventMessages($langs->trans("Veuillez Créer un nouvelle OT (via le bouton créer l'OT en bas de la page).", $projectRef), null, 'mesgs');
			}

			if ($action == 'PROJECT_DELETE_CONTACT') {
				setEventMessages($langs->trans("Veuillez Créer un nouvelle OT (via le bouton créer l'OT en bas de la page).", $projectRef), null, 'mesgs');
			}
			
		}

		if ($action == 'USERVOLET_VALIDATE') {
			if (!empty($object->fk_volet) && $object->fk_volet == 7) { 
				setEventMessages("Trigger USERVOLET_VALIDATE activé pour un volet Habilitation (ID=7)", 'mesgs');
				dol_syslog("Trigger USERVOLET_VALIDATE activé pour un volet Habilitation (ID=7)", LOG_DEBUG);
				
				// Débogage : ID du volet_user concerné
				dol_syslog("ID volet_user concerné : ".$object->rowid, LOG_DEBUG);
		
				// Appel de la fonction pour créer les OT
				$this->createOTForUserVolet($object->id);
			}
		}
		
		
		

		return 1;
	}
	
    /**
     * Fonction pour notifier les changements de contact
     *
     * @param Project $project Objet projet
     * @param string $actionType Type d'action ('add' ou 'remove')
     * @param string $subject Sujet de la notification
     */
    private function notifyContactChange($project, $actionType, $subject)
    {
        global $langs;

        // Message spécifique en fonction de l'action
        if ($actionType == 'add') {
            setEventMessages($langs->trans("ContactAddedToProject", $project->ref), null, 'mesgs');
        } elseif ($actionType == 'remove') {
            setEventMessages($langs->trans("ContactRemovedFromProject", $project->ref), null, 'mesgs');
        }

        // Enregistrer dans les logs Dolibarr
        dol_syslog($subject);
    }

	private function createOTForUserVolet($voletUserId)
	{
		global $db, $user;
	
		// Récupérer l'utilisateur associé à ce volet
		$sql = "SELECT fk_user FROM ".MAIN_DB_PREFIX."formationhabilitation_uservolet WHERE rowid = ".intval($voletUserId);
		$resql = $db->query($sql);
		if (!$resql || $db->num_rows($resql) == 0) {
			dol_syslog("Aucun utilisateur trouvé pour volet_user ID: ".$voletUserId, LOG_ERR);
			return;
		}
		$obj = $db->fetch_object($resql);
		$userVoletId = $obj->fk_user;
	
		// Récupérer les projets où l'utilisateur est un contact
		$sql = "SELECT DISTINCT p.rowid FROM ".MAIN_DB_PREFIX."projet AS p
				INNER JOIN ".MAIN_DB_PREFIX."element_contact AS ec ON ec.element_id = p.rowid
				WHERE ec.fk_socpeople = ".intval($userVoletId);
		$resql = $db->query($sql);
	
		if (!$resql) {
			dol_syslog("Erreur SQL lors de la récupération des projets : ".$db->lasterror(), LOG_ERR);
			return;
		}
	
		$projects = [];
		while ($obj = $db->fetch_object($resql)) {
			$projects[] = $obj->rowid;
		}
	
		if (empty($projects)) {
			dol_syslog("Aucun projet trouvé pour l'utilisateur du volet_user ID: ".$userVoletId, LOG_INFO);
			return;
		}
	
		// Parcours des projets et création d'OT pour chaque projet, sans doublon
		$processedProjects = []; // Tableau pour éviter les doublons
		foreach ($projects as $projectId) {
			// Vérifier si ce projet a déjà été traité
			if (!in_array($projectId, $processedProjects)) {
				// Créer l'OT pour ce projet
				$project = new Project($db);
				if ($project->fetch($projectId)) {
					// Appeler la fonction qui crée l'OT pour ce projet
					$this->createOTForProject($project);
					// Ajouter le projet traité au tableau
					$processedProjects[] = $projectId;
				} else {
					dol_syslog("Erreur lors de la récupération du projet ID: ".$projectId, LOG_ERR);
				}
			}
		}
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
	

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

}
