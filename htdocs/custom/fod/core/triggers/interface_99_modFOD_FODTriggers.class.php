<?php
/* Copyright (C) 2021 METZGER Leny <leny-07@hotmail.fr>
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
 * \file    core/triggers/interface_99_modFOD_FODTriggers.class.php
 * \ingroup fod
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modFOD_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/extendeduser.class.php';


/**
 *  Class of triggers for FOD module
 */
class InterfaceFODTriggers extends DolibarrTriggers
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
		$this->description = "FOD triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'fod_16@fod';
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
		if (empty($conf->fod) || empty($conf->fod->enabled)) {
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
			// Envoi d'un message au PCR lors de l'ajout d'un intervenant et que la FOD est active
			case 'FOD_USER_CREATE':
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$fod = new Fod($this->db);
				$fod->fetch($object->fk_fod);
				$user_ = new User($this->db);
				$user_->fetch($fod->fk_user_pcr);
				$to = '';
				if($user_->statut==1 && !empty($user_->email)){
					$to = $user_->email;
				}
				$user_->fetch($object->fk_user);
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODAjoutIntervenant", $link, $user_->firstname, $user_->lastname);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if (!empty($to) && $fod->status == Fod::STATUS_VALIDATED){
					$res = $res && $mail->sendfile();
				}
				if($res){
					return 1;
				}
				else{
					return -1;
				}
			// Envoi d'un message au RSR lors de la validation du RA (1ere validation) (pas utilisé car envoi direct au PCR)
			case 'FOD_VALIDATERA':
				if($object->ancien_status == $object::STATUS_AOA){
					$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Validation de la FOD et de l'AOA par le RA (".$user->firstname.' '.$user->lastname.")</span><br>";
				}
				else {
					$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Validation de la FOD par le RA ('.$user->firstname.' '.$user->lastname.')</span><br>';
				}
				$res = $object->update($user, false, true);

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_rsr);
				if($user_->statut==1 && !empty($user_->email)){
					$to = $user_->email;
				}
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODValidatedRA", $link);
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


			// Envoi d'un message au RA lors de la validation du RSR (1ere validation)
			case 'FOD_VALIDATERSR':
				if($object->ancien_status == $object::STATUS_AOA){
					$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Validation de la FOD et de l'AOA par le RSR (".$user->firstname.' '.$user->lastname.")</span><br>";
				}
				else {
					$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Validation de la FOD par le RSR ('.$user->firstname.' '.$user->lastname.')</span><br>';
				}
				$res = $object->update($user, false, true);

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_raf);
				if($user_->statut==1 && !empty($user_->email)){
					$to = $user_->email;
				}
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODValidatedRSR", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)){
					$res =  $res && $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}


			// Envoi d'un message au PCR lors de la validation du RA + envoi d'un message au RSR pour qu'il puisse voir la FOD
			case 'FOD_VALIDATERARSR':
				if($object->ancien_status == $object::STATUS_AOA){
					$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Validation de la FOD et de l'AOA par le RA (".$user->firstname.' '.$user->lastname.")</span><br>";
				}
				else {
					$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Validation de la FOD par le RA ('.$user->firstname.' '.$user->lastname.')</span><br>';
				}
				$res = $object->update($user, false, true);

				if($object->ancien_status != $object::STATUS_VALIDATEDRSR){
					global $dolibarr_main_url_root;
					dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
					$subject = '[OPTIM Industries] Notification automatique FOD';
					$from = 'erp@optim-industries.fr';
					$user_ = new User($this->db);
					$user_->fetch($object->fk_user_rsr);
					if($user_->statut==1 && !empty($user_->email)){
						$to = $user_->email;
					}
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFODValidatedRARSR2", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_pcr);
				if(!empty($user_->email)){
					$to = $user_->email;
				}
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODValidatedRARSR", $link);
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


			// Envoi d'un message aux intervenants lors de la validation du PCR
			case 'FOD_VALIDATE':
				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				foreach($object->listIntervenantsForFod() as $intervenant){
					if($intervenant->statut==1 && !empty($intervenant->email)){	
						$to .= $intervenant->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");
				//$to .= ">";
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODValidated", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)){
					$res = $res && $mail->sendfile();
				}

				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_raf);
				$to = '';
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email.", ";
				}
				$user_->fetch($object->fk_user_rsr);
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email;
				}
				$to = rtrim($to, ", ");
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODValidated2", $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if(!empty($to)){
					$res =  $res && $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}


			// Envoi d'un message à tous les intervenants lors du passage au bilan
			case 'FOD_BILAN':
				$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Passage au bilan de la FOD par ".$user->firstname.' '.$user->lastname."</span><br>";
				$res = $object->update($user, false, true);

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				//$to = '<';
				foreach($object->listIntervenantsForFod() as $intervenant){
					if($intervenant->statut==1 && !empty($intervenant->email)){
						$to .= $intervenant->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");
				//$to .= ">";
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODBilan", $link);
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

			// Envoi d'un message au RA et PCR de la FOD lorsque le RD refuse le bilan
			case 'FOD_REFUSBILAN':
				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_raf);
				//$to = '<';
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email.', ';
				}
				$user_->fetch($object->fk_user_pcr);
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email;
				}
				$to = rtrim($to, ', ');
				//$to .= '>';
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODRefusBilan", $link, GETPOST('raison_refus_bilan'));
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


			// Envoi d'un message à tous les intervenants lors de la clôture de la FOD
			case 'FOD_CLOTURE':
				/*$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : ".$user->firstname." ".$user->lastname." a clotûré la FOD</span><br>";
				$res = $object->update($user, false, true);*/

				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				//$to = '<';
				foreach($object->listIntervenantsForFod() as $intervenant){
					if($intervenant->statut==1 && !empty($intervenant->email)){
						$to .= $intervenant->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");
				//$to .= '>';
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link12mois = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php'.'">'.'12 mois'.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODCloture", $link, $link12mois);
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


			// Envoi d'un message au RSR lorsque tous les intervenants ont validé le bilan
			case 'FOD_VALIDATEBILANINTER':
				$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : Tous les intervenants ont validé le bilan de la FOD</span><br>";
				$res = $object->update($user, false, true);

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_rsr);
				if($user_->statut==1 && !empty($user_->email)){
					$to = $user_->email;
				}
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODBilanInter", $link);
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


			// Envoi d'un message au RA lorsque le RSR valide le bilan
			case 'FOD_VALIDATEBILANRSR':
				$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : ".$user->firstname." ".$user->lastname." a validé le bilan du RSR</span><br>";
				$res = $object->update($user, false, true);

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_raf);
				if($user_->statut==1 && !empty($user_->email)){
					$to = $user_->email;
				}
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODBilanRSR", $link);
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


			// Envoi d'un message au PCR lorsque la RA valide le bilan
			case 'FOD_VALIDATEBILANRSRRA':
				$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : ".$user->firstname." ".$user->lastname." a validé le bilan du RA</span><br>";
				$res = $object->update($user, false, true);

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_pcr);
				if($user_->statut==1 && !empty($user_->email)){
					$to = $user_->email;
				}
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODBilanRSRRA", $link);
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
			

			// Envoi d'un message au RD lorsque le PCR valide le bilan
			case 'FOD_VALIDATEBILANRSRRAPCR':
				$object->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y')."</strong> : ".$user->firstname." ".$user->lastname." a validé le bilan du PCR</span><br>";
				$res = $object->update($user, false, true);

				$user_group = New UserGroup($this->db);
				$user_group->fetch(17);
				$liste_rd = $user_group->listUsersForGroup('u.statut=1');
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				//$to = '<';
				foreach($liste_rd as $rd){
					if($rd->statut==1 && !empty($rd->email)){
						$to .= $rd->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");
				//$to .= ">";
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODBilanRSRRAPCR", $link);
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


			// Envoi d'un message au créateur de la FOD lorsque le PCR la refuse
			case 'FOD_REFUS':
				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_creat);
				if($user_->statut==1 && !empty($user_->email)){
					$to = $user_->email;
				}
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODRefusPCR", $link, GETPOST('raison_refus'));
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


			// Envoi d'un message au RA dans le cas ou le PCR crée la FOD
			case 'FOD_CREATE':
				if($user->id == $object->fk_user_pcr && $user->id != $object->fk_user_rsr && $user->id != $object->fk_user_raf){
					$res = 1;
					global $dolibarr_main_url_root;
					dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
					$subject = '[OPTIM Industries] Notification automatique FOD';
					$from = 'erp@optim-industries.fr';
					$user_ = new User($this->db);
					$user_->fetch($object->fk_user_raf);
					if($user_->statut==1 && !empty($user_->email)){
						$to = $user_->email;
					}
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFODCreatePCR", $link, GETPOST('raison_refus'));
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
				}
				else return 1;


			// Envoi d'un message au RA et RSR à chaque prise en compte d'un intervenant
			case 'FOD_PRISEENCOMPTE':
				$fod = New Fod($this->db);
				$fod->fetch($object->fk_fod, null, true);
				$fod->historique .= '<span style="color: blue;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : La FOD a été prise en compte par '.$user->firstname." ".$user->lastname.'</span><br>';
				$res = $fod->update($user, false, true);

				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($fod->fk_user_rsr);
				//$to = '<';
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email.", ";
				}
				$user_->fetch($fod->fk_user_raf);
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email;
				}
				$to = rtrim($to, ', ');
				//$to .= ">";
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				
				$msgPriseEnCompte = "<br/><br/> Les intervenants suivants ont pris en compte la FOD : <br/>";
				$msgNonPriseEnCompte = "<br/><br/> Les intervenants suivants n'ont pas pris en compte la FOD : <br/>";
				foreach($fod->intervenants as $intervenant){
					$idintervenant = $intervenant->id;
					$foduser = New Fod_user($this->db);
					$id_fod_user = $foduser->getIdWithUserAndFod($idintervenant, $fod->id);
					$foduser->fetch($id_fod_user);
					$user_->fetch($foduser->fk_user);
					if($foduser->visa == "1"){
						$msgPriseEnCompte .= "	- ".$user_->firstname." ".$user_->lastname." : ".dol_print_date($foduser->date_visa)."<br/>";
					}
					elseif($foduser->visa == "2"){
						$msgNonPriseEnCompte .= "	- ".$user_->firstname." ".$user_->lastname."<br/>";
					}
				}
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODModify", $link);
				$msg .= $msgPriseEnCompte;
				$msg .= $msgNonPriseEnCompte;
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


			// Envoi message lors de la prolongation de la FOD
			case 'FOD_PROLONGER':
				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
				$user_ = new User($this->db);
				$user_->fetch($object->fk_user_rsr);
				//$to = '<';
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email.', ';
				}
				$user_->fetch($object->fk_user_raf);
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email.', ';
				}
				$user_->fetch($object->fk_user_pcr);
				if($user_->statut==1 && !empty($user_->email)){
					$to .= $user_->email.', ';
				}
				foreach($object->listIntervenantsForFod() as $intervenant){
					if($intervenant->statut==1 && !empty($intervenant->email)){
						$to .= $intervenant->email;
						$to .= ", ";
					}
				}
				$to = rtrim($to, ", ");
				//$to .= '>';
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFODProlongation", $link, substr($this->db->idate($object->date_fin_prolong), 0, 10));
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


			// Envoi des messages lors des dépassement de DC, de CdD et de CdD annuelle
			case 'DATA_INTERVENANT_CREATE':
				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
	
				$fod = New Fod($this->db);
				$fod->fetch($object->fk_fod, null, true);
				$user_ = new ExtendedUser($this->db);
				$user_->fetch($object->fk_user);

				// Dépassement d'un intervenant de la CdD d'une FOD 
				if(($user_->getDoseFod($fod) > $user_->getDoseMaxFod($fod)) && (round($user_->getDoseFod($fod)-$object->dose, 3) <= $user_->getDoseMaxFod($fod))){
					$fod->historique .= '<span style="color: red;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Dépassement de la CdD de la FOD par "'.$user_->firstname." ".$user_->lastname.'"</span><br>';
					$res = $fod->update($user, false, true);

					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$username = $user_->firstname.' '.$user_->lastname;
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseIndiv", $link, $username, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}

					$fod_user = New Fod_user($this->db);
					$fod_user_id = $fod_user->getIdWithUserAndFod($object->fk_user, $object->fk_fod);
					$fod_user->fetch($fod_user_id);
					$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddFOD);
					$fod_user->statut = $new_statut;
					$res = $res && $fod_user->update($user, true);
				}
				elseif(($user_->getDoseFod($fod) >= round($user_->getDoseMaxFod($fod)*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && (round($user_->getDoseFod($fod)-$object->dose, 3) < round($user_->getDoseMaxFod($fod)*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3))){
					$fod->historique .= '<span style="color: #ff731f;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : 90% de la CdD de la FOD a été atteinte par "'.$user->firstname." ".$user->lastname.'"</span><br>';
					$res = $fod->update($user, false, true);

					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email;
					}
					$to = rtrim($to, ', ');
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$username = $user_->firstname.' '.$user_->lastname;
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseIndiv", $link, $username, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement d'un intervenant de la CdD mensuelle
				if(($user_->getDoseMoisActuelle() > $user_->getCddmensuelle()) && (round($user_->getDoseMoisActuelle()-$object->dose, 3) <= $user_->getCddmensuelle())){
					$to = "";
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseIndivMensuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}

					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddMensuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
				}
				elseif(($user_->getDoseMoisActuelle() >= round($user_->getCddmensuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && (round($user_->getDoseMoisActuelle()-$object->dose, 3) < round($user_->getCddmensuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3))){
					$to = '';
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseIndivMensuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement d'un intervenant de la CdD annuelle
				if(($user_->getDose12mois() > $user_->getCdd()) && (round($user_->getDose12mois()-$object->dose, 3) <= $user_->getCdd())){
					$to = "";
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= ">";
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseIndivAnnuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}

					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddAnnuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
				}
				elseif(($user_->getDose12mois() >= round($user_->getCdd()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && (round($user_->getDose12mois()-$object->dose, 3) < round($user_->getCdd()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3))){
					$to = '';
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseIndivAnnuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement de la DC 
				if(($fod->GetDoseCollectiveReel() > $fod->GetDoseCollectivePrevisionnelleOptimise()) && (round($fod->GetDoseCollectiveReel()-$object->dose, 3) <= $fod->GetDoseCollectivePrevisionnelleOptimise())){
					
					$fod->historique .= '<span style="color: red;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Dépassement de la DC</span><br>';
					$res = $res && $fod->update($user, false, true);


					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}

					foreach($fod->intervenants as $intervenant){
						if($intervenant->statut==1 && !empty($intervenant->email)){
							$to .= $intervenant->email;
							$to .= ", ";
						}
						$fod_user = New Fod_user($this->db);
						$fod_user_id = $fod_user->getIdWithUserAndFod($intervenant->id, $fod->id);
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_dcFOD);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= ">";
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseCol", $link, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}
				else if(($fod->GetDoseCollectiveReel() >= round($fod->GetDoseCollectivePrevisionnelleOptimise()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && (round($fod->GetDoseCollectiveReel()-$object->dose, 3) < round($fod->GetDoseCollectivePrevisionnelleOptimise()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3))){
					$fod->historique .= '<span style="color: #ff731f;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : 90% de la DC atteinte</span><br>';
					$res = $res && $fod->update($user, false, true);
					
					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					foreach($fod->intervenants as $intervenant){
						if($intervenant->statut==1 && !empty($intervenant->email)){
							$to .= $intervenant->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= ">";
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseCol", $link, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement de la CdD Annuelle OPTIM 
				if(($fod->getDose12moisTotale() > $fod->getCdDAnnuelle()) && (round($fod->getDose12moisTotale()-$object->dose, 3) <= $fod->getCdDAnnuelle())){
					$to = '';
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseAnnuelleOPTIM", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}
				else if(($fod->getDose12moisTotale() >= round($fod->getCdDAnnuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement_total12mois / 100), 3)) && (round($fod->getDose12moisTotale()-$object->dose, 3) < round($fod->getCdDAnnuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement_total12mois / 100), 3))){
					$to = '';
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseAnnuelleOPTIM", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}


				// Déclenchement portique
				if($object->portique > 0){
					$portique = $object->fields['portique']['arrayofkeyval'][$object->portique];
					$fod->historique .= '<span style="color: #ff731f;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Déclenchement portique '.$portique.' par '.$user_->firstname.' '.$user_->lastname.'</span><br>';
					$res = $res && $fod->update($user, false, true);
					
					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email;
					}
					$to = rtrim($to, ', ');
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$username = $user_->firstname.' '.$user_->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailTextPortique", $portique, $link, $username);
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
		

			// Envoi des messages lors des dépassement de DC, de CdD et de CdD annuelle
			case 'DATA_INTERVENANT_MODIFY':
				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique FOD';
				$from = 'erp@optim-industries.fr';
	
				$fod = New Fod($this->db);
				$fod->fetch($object->fk_fod, null, true);
				$user_ = new ExtendedUser($this->db);
				$user_->fetch($object->fk_user);

				// Dépassement d'un intervenant de la CdD d'une FOD 
				if(($user_->getDoseFod($fod) > $user_->getDoseMaxFod($fod)) && (round($user_->getDoseFod($fod)-$object->dose+$object->ancienne_dose, 3) <= $user_->getDoseMaxFod($fod))){
					$fod->historique .= '<span style="color: red;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Dépassement de la CdD de la FOD par "'.$user_->firstname." ".$user_->lastname.'"</span><br>';
					$res = $res && $fod->update($user, false, true);

					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$username = $user_->firstname.' '.$user_->lastname;
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseIndiv", $link, $username, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}

					$fod_user = New Fod_user($this->db);
					$fod_user_id = $fod_user->getIdWithUserAndFod($object->fk_user, $object->fk_fod);
					$fod_user->fetch($fod_user_id);
					$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddFOD);
					$fod_user->statut = $new_statut;
					$res = $res && $fod_user->update($user, true);
				}
				elseif(($user_->getDoseFod($fod) >= round($user_->getDoseMaxFod($fod)*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && (round($user_->getDoseFod($fod)-$object->dose+$object->ancienne_dose, 3) < round($user_->getDoseMaxFod($fod)*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3))){
					$fod->historique .= '<span style="color: #ff731f;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : 90% de la CdD de la FOD a été atteinte par "'.$user->firstname." ".$user->lastname.'"</span><br>';
					$res = $res && $fod->update($user, false, true);

					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email;
					}
					$to = rtrim($to, ', ');
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$username = $user_->firstname.' '.$user_->lastname;
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseIndiv", $link, $username, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement d'un intervenant de la CdD mensuelle
				if(($user_->getDoseMoisActuelle() > $user_->getCddmensuelle()) && (round($user_->getDoseMoisActuelle()-$object->dose+$object->ancienne_dose, 3) <= $user_->getCddmensuelle())){
					$to = '';
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseIndivMensuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}

					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddMensuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
				}
				elseif(($user_->getDoseMoisActuelle() >= $user_->getCddmensuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100)) && (round($user_->getDoseMoisActuelle()-$object->dose+$object->ancienne_dose, 3) < $user_->getCddmensuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100))){
					$to = '';
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseIndivMensuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement d'un intervenant de la CdD annuelle
				if(($user_->getDose12mois() > $user_->getCdd()) && (round($user_->getDose12mois()-$object->dose+$object->ancienne_dose, 3) <= $user_->getCdd())){
					$to = '';
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseIndivAnnuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}

					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_cddAnnuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
				}
				elseif(($user_->getDose12mois() >= $user_->getCdd()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100)) && (round($user_->getDose12mois()-$object->dose+$object->ancienne_dose, 3) < $user_->getCdd()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100))){
					$to = '';
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email.', ';
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/data_intervenant_list.php?search_fk_user='.$user_->id.'">'.$user_->firstname.' '.$user_->lastname.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseIndivAnnuelle", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement de la DC 
				if(($fod->GetDoseCollectiveReel() > $fod->GetDoseCollectivePrevisionnelleOptimise()) && (round($fod->GetDoseCollectiveReel()-$object->dose+$object->ancienne_dose, 3) <= $fod->GetDoseCollectivePrevisionnelleOptimise())){
					$fod->historique .= '<span style="color: red;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Dépassement de la DC</span><br>';
					$res = $res && $fod->update($user, false, true);

					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					foreach($fod->intervenants as $intervenant){
						if($intervenant->statut==1 && !empty($intervenant->email)){
							$to .= $intervenant->email;
							$to .= ", ";
						}
						$fod_user = New Fod_user($this->db);
						$fod_user_id = $fod_user->getIdWithUserAndFod($intervenant->id, $fod->id);
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '+', Fod_user::STATUS_NA_dcFOD);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= ">";
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseCol", $link, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}
				else if(($fod->GetDoseCollectiveReel() >= round($fod->GetDoseCollectivePrevisionnelleOptimise()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3)) && (round($fod->GetDoseCollectiveReel()-$object->dose+$object->ancienne_dose, 3) < round($fod->GetDoseCollectivePrevisionnelleOptimise()*($conf->global->FOD_Pourcentage_RisqueDepassement / 100), 3))){
					$fod->historique .= '<span style="color: #ff731f;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : 90% de la DC atteinte</span><br>';
					$res = $res && $fod->update($user, false, true);
					
					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					foreach($fod->intervenants as $intervenant){
						if($intervenant->statut==1 && !empty($intervenant->email)){
							$to .= $intervenant->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");
					//$to .= ">";
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$pcr = new User($this->db);
					$pcr->fetch($fod->fk_user_pcr);
					$pcrname = $pcr->firstname." ".$pcr->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseCol", $link, $pcrname);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Dépassement de la CdD Annuelle OPTIM 
				if(($fod->getDose12moisTotale() > $fod->getCdDAnnuelle()) && (round($fod->getDose12moisTotale()-$object->dose+$object->ancienne_dose, 3) <= $fod->getCdDAnnuelle())){
					$to = '';
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText100DoseAnnuelleOPTIM", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}
				else if(($fod->getDose12moisTotale() >= round($fod->getCdDAnnuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement_total12mois / 100), 3)) && (round($fod->getDose12moisTotale()-$object->dose+$object->ancienne_dose, 3) < round($fod->getCdDAnnuelle()*($conf->global->FOD_Pourcentage_RisqueDepassement_total12mois / 100), 3))){
					$to = '';
					$user_group = New UserGroup($this->db);
					$user_group->fetch(17);
					$liste_rd = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_rd as $rd){
						if(!empty($rd->email)){
							$to .= $rd->email;
							$to .= ", ";
						}
					}
					$user_group->fetch(11);
					$liste_pcr = $user_group->listUsersForGroup('u.statut=1');
					foreach($liste_pcr as $pcr){
						if(!empty($pcr->email)){
							$to .= $pcr->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailText90DoseAnnuelleOPTIM", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}

				// Déclenchement portique
				if($object->portique > 0){
					$portique = $object->fields['portique']['arrayofkeyval'][$object->portique];
					$fod->historique .= '<span style="color: #ff731f;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Déclenchement portique '.$portique.' par '.$user_->firstname.' '.$user_->lastname.'</span><br>';
					$res = $res && $fod->update($user, false, true);
					
					$user_mail = new User($this->db);
					$user_mail->fetch($fod->fk_user_pcr);
					$to = '';
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_rsr);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					$user_mail->fetch($fod->fk_user_raf);
					if($user_mail->statut==1 && !empty($user_mail->email)){
						$to .= $user_mail->email.', ';
					}
					if($user_->statut==1 && !empty($user_->email)){
						$to .= $user_->email;
					}
					$to = rtrim($to, ', ');
					//$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$username = $user_->firstname.' '.$user_->lastname;
					$msg = $langs->transnoentitiesnoconv("EMailTextPortique", $portique, $link, $username);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $res && $mail->sendfile();
					}
				}
	
				if(($user_->getDoseFod($fod) <= $user_->getDoseMaxFod($fod))){
					$fod_user = New Fod_user($this->db);
					$fod_user_id = $fod_user->getIdWithUserAndFod($object->fk_user, $object->fk_fod);
					$fod_user->fetch($fod_user_id);
					$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_cddFOD);
					if($fod_user->statut != $new_statut){
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
				}

				if(($user_->getDose12mois() <= $user_->getCdd())){
					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_cddAnnuelle);
						if($fod_user->statut != $new_statut){
							$fod_user->statut = $new_statut;
							$res = $res && $fod_user->update($user, true);
						}
					}
				}

				if(($user_->getDoseMoisActuelle() <= $user_->getCddmensuelle())){
					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_cddMensuelle);
						if($fod_user->statut != $new_statut){
							$fod_user->statut = $new_statut;
							$res = $res && $fod_user->update($user, true);
						}
					}
				}

				if(($fod->GetDoseCollectiveReel() <= $fod->GetDoseCollectivePrevisionnelleOptimise())){
					foreach($fod->intervenants as $intervenant){
						$fod_user = New Fod_user($this->db);
						$fod_user_id = $fod_user->getIdWithUserAndFod($intervenant->id, $fod->id);
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_dcFOD);
						if($fod_user->statut != $new_statut){
							$fod_user->statut = $new_statut;
							$res = $res && $fod_user->update($user, true);
						}
					}
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}


			case 'DATA_INTERVENANT_DELETE':
				$res = 1;
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				//$subject = '[OPTIM Industries] Notification automatique FOD';
				//$from = 'erp@optim-industries.fr';
	
				$fod = New Fod($this->db);
				$fod->fetch($object->fk_fod);
				$user_ = new ExtendedUser($this->db, null, true);
				$user_->fetch($object->fk_user);

				// Non dépassement d'un intervenant de la CdD d'une FOD 
				if(($user_->getDoseFod($fod) > $user_->getDoseMaxFod($fod)) && (round($user_->getDoseFod($fod)-$object->dose, 3) <= $user_->getDoseMaxFod($fod))){
					$fod_user = New Fod_user($this->db);
					$fod_user_id = $fod_user->getIdWithUserAndFod($object->fk_user, $object->fk_fod);
					$fod_user->fetch($fod_user_id);
					$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_cddFOD);
					$fod_user->statut = $new_statut;
					$res = $res && $fod_user->update($user, true);
				}

				// Non dépassement d'un intervenant de la CdD mensuelle
				if(($user_->getDoseMoisActuelle() > $user_->getCddmensuelle()) && (round($user_->getDoseMoisActuelle()-$object->dose, 3) <= $user_->getCddmensuelle())){
					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_cddMensuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
				}

				// Non dépassement d'un intervenant de la CdD annuelle
				if(($user_->getDose12mois() > $user_->getCdd()) && (round($user_->getDose12mois()-$object->dose, 3) <= $user_->getCdd())){
					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithUser($user_->id);
					foreach($fod_user_listid as $fod_user_id){
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_cddAnnuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
					}
				}

				// Non dépassement de la DC 
				if(($fod->GetDoseCollectiveReel() > $fod->GetDoseCollectivePrevisionnelleOptimise()) && (round($fod->GetDoseCollectiveReel()-$object->dose, 3) <= $fod->GetDoseCollectivePrevisionnelleOptimise())){
					$fod_user = New Fod_user($this->db);
					$fod_user_listid = $fod_user->getListWithFod($fod->id);
					foreach($fod->intervenants as $intervenant){
						$fod_user_id = $fod_user->getIdWithUserAndFod($intervenant->id, $fod->id);
						$fod_user->fetch($fod_user_id);
						$new_statut = $fod_user->getNewStatut($fod_user->statut, '-', Fod_user::STATUS_NA_dcFOD);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
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
