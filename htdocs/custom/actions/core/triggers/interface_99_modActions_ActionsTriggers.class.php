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
 * \file    core/triggers/interface_99_modActions_ActionsTriggers.class.php
 * \ingroup actions
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modActions_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Actions module
 */
class InterfaceActionsTriggers extends DolibarrTriggers
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
		$this->description = "Actions triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'actions@actions';
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
		if (!isModEnabled('actions')) {
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
			// Envoi d'un mail au pilote lors de la validation par l'émetteur
			case 'ACTIONQ3SE_VALIDATE':
				$subject = '[OPTIM Industries] Notification automatique action';
				$from = 'erp@optim-industries.fr';
				
				$user_static = new User($this->db);
				$user_static->fetch($object->intervenant);
		
				$to = ''; 
				if(!empty($user_static->email)){
					$to .= $user_static->email;
				}	

				$user_static->fetch($object->fk_user_creat);
				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/actions/action_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextActionValidate", $link, $user_static->lastname." ".$user_static->firstname);
				$mail = new CMailFile($subject, $to, $from, $message, array(), array(), array(), '', '', 0, 1, '', '', 'action'.'_'.$object->id);

				if(!empty($to)) {
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				elseif(!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')){
					setEventMessages("Impossible d'envoyer le mail", null, 'warnings');
					return 0;
				}

			// Envoi d'un mail au service Q3SE, responsable Q3SE lorsque l'action est soldée
			case 'ACTIONQ3SE_SOLDE':
				$subject = '[OPTIM Industries] Notification automatique action';
				$from = 'erp@optim-industries.fr';
				
				$user_static = new User($this->db);
				$user_static->fetch($object->intervenant);
		
				$to = ''; 
				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
							
					}
				}
				$user_group->fetch('', 'Resp. Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
							
					}
				}
				$to = rtrim($to, ", ");

				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/actions/action_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextActionSolde", $link, $user_static->lastname." ".$user_static->firstname);
				$mail = new CMailFile($subject, $to, $from, $message, array(), array(), array(), '', '', 0, 1, '', '', 'action'.'_'.$object->id);

				if(!empty($to)) {
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				elseif(!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')){
					setEventMessages("Impossible d'envoyer le mail", null, 'warnings');
					return 0;
				}

			// Envoi d'un mail au service Q3SE et à l'émétteur lors de la clôture par le Q3SE
			case 'ACTIONQ3SE_CLOTURE':
				$subject = '[OPTIM Industries] Notification automatique action';
				$from = 'erp@optim-industries.fr';
				
				$user_static = new User($this->db);
				$user_static->fetch($object->intervenant);
		
				$to = ''; 
				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
					}
				}
				$user_group->fetch('', 'Resp. Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
							
					}
				}

				if(!empty($user_static->email)){
					$to .= $user_static->email;
				}	
				$to = rtrim($to, ", ");

				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/actions/action_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextActionClose", $link, $user_static->lastname." ".$user_static->firstname, $user->lastname." ".$user->firstname);
				$mail = new CMailFile($subject, $to, $from, $message, array(), array(), array(), '', '', 0, 1, '', '', 'action'.'_'.$object->id);

				if(!empty($to)) {
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				elseif(!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')){
					setEventMessages("Impossible d'envoyer le mail", null, 'warnings');
					return 0;
				}
				
			// Envoi d'un mail au(x) responsable(s) d'affaires, au service Q3SE et à l'émétteur lors de l'annulation d'un constat
			case 'ACTIONQ3SE_CANCEL':
				$subject = '[OPTIM Industries] Notification automatique action';
				$from = 'erp@optim-industries.fr';
				
				$user_static = new User($this->db);
				$user_static->fetch($object->intervenant);
		
				$to = ''; 
				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
							
					}
				}
				$user_group->fetch('', 'Resp. Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
							
					}
				}

				if(!empty($user_static->email)){
					$to .= $user_static->email;
				}	
				$to = rtrim($to, ", ");

				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/actions/action_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextActionCancel", $link, $user_static->lastname." ".$user_static->firstname, $user->lastname." ".$user->firstname);
				$mail = new CMailFile($subject, $to, $from, $message, array(), array(), array(), '', '', 0, 1, '', '', 'constat'.'_'.$object->id);

				if(!empty($to)) {
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				elseif(!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')){
					setEventMessages("Impossible d'envoyer le mail", null, 'warnings');
					return 0;
				}

			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
