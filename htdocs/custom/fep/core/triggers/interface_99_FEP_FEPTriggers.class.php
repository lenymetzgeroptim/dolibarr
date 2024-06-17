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
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for MyModule module
 */
class InterfaceFEPTriggers extends DolibarrTriggers
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
		$this->description = "FEP triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'fep_16@fep';
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
		if (empty($conf->fep) || empty($conf->fep->enabled)) {
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

		$res = 1;

		// Or you can execute some code here
		switch ($action) {
			case 'FEP_COMMENTAIRE':				
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FEP';
				$from = 'erp@optim-industries.fr';

				$liste_commande = $object->getCommande();
				if(!empty($liste_commande)){
					$projectstatic = New Project($this->db);
					$projectstatic->fetch($liste_commande[0]->fk_project);
					$liste_chef_projet = $projectstatic->liste_contact(1, 'internal', 1, 'PROJECTLEADER');
				}
				
				$to = '';
				foreach($liste_chef_projet as $chefprojet){
					$user_static = new User($this->db);
					$user_static->fetch($chefprojet);
					if(!empty($user_static->email)){
						$to .= $user_static->email;
						$to .= ', ';
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFEPCommentaireRA", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if (!empty($to)){
					$res = $res && $mail->sendfile();
				}
				if($res){
					return 1;
				}
				else{
					return -1;
				}

			case 'FEP_REPONSE':
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FEP';
				$from = 'erp@optim-industries.fr';

				$user_group = New UserGroup($this->db);
				$user_group->fetch('', 'Q3SE');
				$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
				$to = '';
				foreach($liste_qualite as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFEPReponseAEnvoyer", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)){
					$res = $res && $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}

			case 'FEP_CREATE':
				// Mail note theme
				$envoi_mail = 0;
				$note = '';
				foreach($object->fields as $key => $val){
					if(preg_match('/note_theme/i', $key)){
						if ($object->$key != '' && $object->$key <= 1){
							$note .= $langs->transnoentitiesnoconv($val['label']).' : '.$val['arrayofkeyval'][$object->$key].'<br>';
							$envoi_mail = 1;
						}
					}
				}

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FEP';
				$from = 'erp@optim-industries.fr';

				if($envoi_mail){
					$user_group = New UserGroup($this->db);
					$user_group->fetch('', 'Responsable Affaires');
					$liste_RA = $user_group->listUsersForGroup('u.statut=1');
					$to = '';
					foreach($liste_RA as $RA){
						if(!empty($RA->email)){
							$to .= $RA->email;
							$to .= ", ";
						}
					}
					$user_group->fetch('', 'Q3SE');
					$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFEPNoteThemeC", $link, $note);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Mail note globale 
				$envoi_mail = 0;
				$note = '';
				$key = 'note_globale';
				if ($object->$key != '' && $object->$key <= 1){
					$note .= $val['arrayofkeyval'][$object->$key];
					$envoi_mail = 1;
				}

				if($envoi_mail){
					$user_group = New UserGroup($this->db);
					$user_group->fetch('', 'Responsable Affaires');
					$liste_RA = $user_group->listUsersForGroup('u.statut=1');
					$to = '';
					foreach($liste_RA as $RA){
						if(!empty($RA->email)){
							$to .= $RA->email;
							$to .= ", ";
						}
					}
					$user_group->fetch('', 'Q3SE');
					$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$user_group->fetch('', "Responsable d'antenne");
					$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$user_group->fetch('', 'Direction');
					$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFEPNoteGlobale", $link, $note);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Irrégularité CFSI
				if($object->irregularite_cfsi == 1){
					$user_group = New UserGroup($this->db);
					$user_group->fetch('', 'Responsable Affaires');
					$liste_RA = $user_group->listUsersForGroup('u.statut=1');
					$to = '';
					foreach($liste_RA as $RA){
						if(!empty($RA->email)){
							$to .= $RA->email;
							$to .= ", ";
						}
					}
					$user_group->fetch('', 'Q3SE');
					$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$user_group->fetch('', "Responsable d'antenne");
					$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$user_group->fetch('', 'Direction');
					$liste_qualite = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFEPIrregulariteCFSI", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
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
