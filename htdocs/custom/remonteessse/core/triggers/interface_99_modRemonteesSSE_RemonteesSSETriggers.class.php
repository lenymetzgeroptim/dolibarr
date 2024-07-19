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
class InterfaceRemonteesSSETriggers extends DolibarrTriggers
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
		$this->description = "Remontees SSE triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'remonteesse@remonteesse';
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
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{

		if (empty($conf->remonteessse) || empty($conf->remonteessse->enabled)) {
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
		};

		// Or you can execute some code here
		switch ($action) {

			case 'REMONTEESSSE_VALIDATE':
				$res = 1;
				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
				$liste_Q3E = $user_group->listUsersForGroup('u.statut=1');
				
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique Remontée Q3SE/RP';
				$from = 'erp@optim-industries.fr';

				$to = '';
				foreach($liste_Q3E as $q3e){
					if(!empty($q3e->email)){
						$to .= $q3e->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/remonteessse/remonteessse_card.php?id='.$object->id.'">'.$object->libelle.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextRemonteeSSEValidate", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, -1);
				if(!empty($to)){
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}

				
			case 'REMONTEESSSE_PRISEENCOMPTE':
				$res = 1;
			
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique Remontée Q3SE/RP';
				$from = 'erp@optim-industries.fr';

				$user_static = new User($this->db);
				$user_static->fetch($object->fk_user);
				$to = $user_static->email.', ';
				
				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
				$liste_Q3E = $user_group->listUsersForGroup('u.statut=1');
				foreach($liste_Q3E as $q3e){
					if(!empty($q3e->email)){
						$to .= $q3e->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/remonteessse/remonteessse_card.php?id='.$object->id.'">'.$object->libelle.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextRemonteeSSEPrisencompte", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)){
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}
			
			/*case 'REMONTEESSSE_ANNULATION':
				$res = 1;
			
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique Remontée SSE';
				$from = 'erp@optim-industries.fr';

				$user_static = new User($this->db);
				$user_static->fetch($object->fk_user);
				$to = $user_static->email.', ';
				
				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
				$liste_Q3E = $user_group->listUsersForGroup('u.statut=1');
				foreach($liste_Q3E as $q3e){
					if(!empty($q3e->email)){
						$to .= $q3e->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/remonteessse/remonteessse_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextRemonteeSSEAnnulation", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)){
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}*/

			case 'REMONTEESSSE_CLOTURE':
				$res = 1;
			
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique Remontée Q3SE/RP';
				$from = 'erp@optim-industries.fr';

				$user_static = new User($this->db);
				$user_static->fetch($object->fk_user);
				$to = $user_static->email;

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/remonteessse/remonteessse_card.php?id='.$object->id.'">'.$object->libelle.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextRemonteeSSECloture", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)){
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
