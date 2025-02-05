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
			// ID du projet lié
			
			if ($action == 'PROJECT_ADD_CONTACT') {
				setEventMessages($langs->trans("Veuillez Créer un nouvelle OT (via le bouton créer l'OT en bas de la page).", $projectRef), null, 'mesgs');
			}

			if ($action == 'PROJECT_DELETE_CONTACT') {
				setEventMessages($langs->trans("Veuillez Créer un nouvelle OT (via le bouton créer l'OT en bas de la page).", $projectRef), null, 'mesgs');
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
