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

		$res = 1;

		// Or you can execute some code here
		switch ($action) {
			// Envoi d'un mail au(x) responsable(s) d'affaires lors de la validation par l'émetteur
			case 'CONSTAT_VALIDATE':
				$subject = '[OPTIM Industries] Notification automatique constat';
				$from = 'erp@optim-industries.fr';
				
				$projet = new Project($this->db);
				$user_static = new User($this->db);
				$projet->fetch($object->fk_project);
				$user_static->fetch($object->fk_user);
				$liste_chef_projet = $projet->liste_contact(-1, 'internal', 0, 'PROJECTLEADER');
		
				$to = ''; 
				foreach($liste_chef_projet as $id_user => $val){
					if($val['statuscontact'] == 1 && !empty($val['email'])){
						$to .= $val['email'];
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");

				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextConstatValidate", $link, $user_static->lastname." ".$user_static->firstname);
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

			// Envoi d'un mail au service Q3SE lors de la validation par le responsable d'affaire
			case 'CONSTAT_EN_COURS':
				$subject = '[OPTIM Industries] Notification automatique constat';
				$from = 'erp@optim-industries.fr';
				
				$user_static = new User($this->db);
				$user_static->fetch($object->fk_user);
		
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
				$to = rtrim($to, ", ");

				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextConstatEnCours", $link, $user_static->lastname." ".$user_static->firstname);
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

			// Envoi d'un mail au(x) responsable(s) d'affaires, au service Q3SE et à l'émétteur lors de la clôture par le Q3SE
			case 'CONSTAT_CLOSE':
				$subject = '[OPTIM Industries] Notification automatique constat';
				$from = 'erp@optim-industries.fr';
				
				$projet = new Project($this->db);
				$user_static = new User($this->db);
				$projet->fetch($object->fk_project);
				$user_static->fetch($object->fk_user);
				$liste_chef_projet = $projet->liste_contact(-1, 'internal', 0, 'PROJECTLEADER');
		
				$to = ''; 
				foreach($liste_chef_projet as $id_user => $val){
					if($val['statuscontact'] == 1 && !empty($val['email'])){
						$to .= $val['email'];
						$to .= ", ";
					}
				}

				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
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
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextConstatClose", $link, $user_static->lastname." ".$user_static->firstname);
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
				
			// Envoi d'un mail au(x) responsable(s) d'affaires, au service Q3SE et à l'émétteur lors de l'annulation d'un constat
			case 'CONSTAT_CANCEL':
				$subject = '[OPTIM Industries] Notification automatique constat';
				$from = 'erp@optim-industries.fr';
				
				$projet = new Project($this->db);
				$user_static = new User($this->db);
				$projet->fetch($object->fk_project);
				$user_static->fetch($object->fk_user);
				$liste_chef_projet = $projet->liste_contact(-1, 'internal', 0, 'PROJECTLEADER');
		
				$to = ''; 
				foreach($liste_chef_projet as $id_user => $val){
					if($val['statuscontact'] == 1 && !empty($val['email'])){
						$to .= $val['email'];
						$to .= ", ";
					}
				}

				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
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
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$message = $langs->transnoentitiesnoconv("EMailTextConstatCancel", $link, $user_static->lastname." ".$user_static->firstname, $user->lastname." ".$user->firstname);
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
			
			case 'CONSTAT_DECLINE':
				$subject = '[OPTIM Industries] Notification automatique constat';
				$from = 'erp@optim-industries.fr';
				
				$projet = new Project($this->db);
				$user_static = new User($this->db);
				$projet->fetch($object->fk_project);
				$user_static->fetch($object->fk_user);
				$liste_chef_projet = $projet->liste_contact(-1, 'internal', 0, 'PROJECTLEADER');
		
				$to = ''; 
				if ($object->status == $object::STATUS_VALIDATED) {
					if(!empty($user_static->email)){
						$to .= $user_static->email;
					}
				}
				elseif ($object->status == $object::STATUS_EN_COURS) {
					foreach($liste_chef_projet as $id_user => $val){
						if($val['statuscontact'] == 1 && !empty($val['email'])){
							$to .= $val['email'];
							$to .= ", ";
						}
					}
				}
				$to = rtrim($to, ", ");

				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				if ($object->status == $object::STATUS_VALIDATED) {
					$message = $langs->transnoentitiesnoconv("EMailTextConstatDeclineRespAff", $link, $user->lastname." ".$user->firstname, GETPOST('decline_reason', 'alphanohtml'));
				}
				elseif ($object->status == $object::STATUS_EN_COURS) {
					$message = $langs->transnoentitiesnoconv("EMailTextConstatDeclineQ3SE", $link, $user->lastname." ".$user->firstname, GETPOST('decline_reason', 'alphanohtml'));
				}

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

			// Envoi d'un mail au(x) responsable(s) d'affaires, au service Q3SE, au responsable Q3SE et au créateur lorsue toutes les actions sont soldées
			case 'ACTION_SOLDEE':
				$subject = '[OPTIM Industries] Notification automatique constat';
				$from = 'erp@optim-industries.fr';

				$object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 'constat');
				var_dump($object->linkedObjects);

				// $constat = new Constat($this->db);
				// $constat->fetch($object)
				
				// $projet = new Project($this->db);
				// $user_static = new User($this->db);
				// $projet->fetch($object->fk_project);
				// $user_static->fetch($object->fk_user);
				// $liste_chef_projet = $projet->liste_contact(-1, 'internal', 0, 'PROJECTLEADER');
		
				// $to = ''; 
				// foreach($liste_chef_projet as $id_user => $val){
				// 	if($val['statuscontact'] == 1 && !empty($val['email'])){
				// 		$to .= $val['email'];
				// 		$to .= ", ";
				// 	}
				// }

				// $user_group = New UserGroup($this->db);
				// $user_group->fetch('', 'Q3SE');
				// $liste_utilisateur = $user_group->listUsersForGroup();
				// foreach($liste_utilisateur as $qualite){
				// 	if(!empty($qualite->email)){
				// 		$to .= $qualite->email;
				// 		$to .= ", ";
							
				// 	}
				// }

				// if(!empty($user_static->email)){
				// 	$to .= $user_static->email;
				// }	
				// $to = rtrim($to, ", ");

				// global $dolibarr_main_url_root;
				// $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				// $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				// $link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				// $message = $langs->transnoentitiesnoconv("EMailTextConstatCancel", $link, $user_static->lastname." ".$user_static->firstname, $user->lastname." ".$user->firstname);
				// $mail = new CMailFile($subject, $to, $from, $message, array(), array(), array(), '', '', 0, 1, '', '', 'constat'.'_'.$object->id);

				// if(!empty($to)) {
				// 	$res = $mail->sendfile();
				// }

				// if($res){
				// 	return 1;
				// }
				// elseif(!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')){
				// 	setEventMessages("Impossible d'envoyer le mail", null, 'warnings');
				// 	return 0;
				// }
		
		
			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}