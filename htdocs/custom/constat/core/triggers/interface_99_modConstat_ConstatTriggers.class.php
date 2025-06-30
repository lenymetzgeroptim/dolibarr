<?php
/* Copyright (C) 2025 FAURE Louis <l.faure@optim-industries.fr>
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
 * \file    core/triggers/interface_99_modConstat_ConstatTriggers.class.php
 * \ingroup constat
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modConstat_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Constat module
 */
class InterfaceConstatTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "Constat triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'constat@constat';
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


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('constat')) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
		// For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
		$methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
		$callback = array($this, $methodName);
		if (is_callable($callback)) {
			dol_syslog(
				"Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id
			);

			return call_user_func($callback, $action, $object, $user, $langs, $conf);
		}

		// Or you can execute some code here
		switch ($action) {
			// Envoi d'un mail au(x) responsable(s) d'affaires lors de la validation par l'émetteur
			case 'CONSTAT_VALIDATE':
				$subject = '[OPTIM Industries] Notification automatique constat';
				$from = 'erp@optim-industries.fr';
				
				$projet = new Project($this->db);
				$user_static = new Project($this->db);
				$projet->fetch($object->fk_project);
				$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
		
				$to = ''; 
				foreach($liste_chef_projet as $chef_projet){
					$user_static->fetch($chef_projet);
					if($user_static->statut == 1 && !empty($user_static->email)){
						$to .= $user_static->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");

				// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
				$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
				$resql_creator = $db->query($sql_creator);
				$creator_name = "";
				if ($resql_creator) {
					if ($db->num_rows($resql_creator) > 0) {
						$creator = $db->fetch_object($resql_creator);
						$creator_name = $creator->firstname . ' ' . $creator->lastname;
					}
				}
				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$this->id.'">'.$this->ref.'</a>';

				$to = rtrim($to, ", ");
				$msg = $langs->transnoentitiesnoconv("Bonjour, le constat ".$link." créé par ".$creator_name." a été validé. Veuillez compléter votre partie. Cordialement, votre système de notification.");
				$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$object->id);
				
				$res = $cmail->sendfile();
		
			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
