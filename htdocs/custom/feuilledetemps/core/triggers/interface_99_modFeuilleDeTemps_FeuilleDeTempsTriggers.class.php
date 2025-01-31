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
class InterfaceFeuilleDeTempsTriggers extends DolibarrTriggers
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
		$this->description = "FeuilleDeTemps triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'timesheet_16@feuilledetemps';
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
		if (empty($conf->FeuilleDeTemps) || empty($conf->FeuilleDeTemps->enabled)) {
			//return 0; // If module is not enabled, we do nothing
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
			// Envoi un mail aux approbateurs 1 lors de la transmission
			case 'FEUILLEDETEMPS_APPROBATION1':
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
				$from = 'erp@optim-industries.fr';

				$to = '';

				$list_validation = $object->listApprover1;
				foreach($list_validation[2] as $id => $user_static){
					if(!empty($user_static->email)){
						$to .= $user_static->email.', ';
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$heure_manquante = '';
				// if($object->semaineHeuresManquantes()){
				// 	$heure_manquante = '<p style="color: red">Celle-ci contient une ou plusieurs semaines à moins de 35h</p>';
				// }
				$msg = $langs->transnoentitiesnoconv("EMailTextFDTApprobation", $link, $heure_manquante);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if (!empty($to)){
					$res = $mail->sendfile();
				}

				if($res){
					return 1;
				}
				else{
					return -1;
				}
		
			
			// Envoi d'un mail au responsable hiérarchique lorsque la FDT est validé par tous les responsables de taches
			case 'FEUILLEDETEMPS_APPROBATION2':
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
				$from = 'erp@optim-industries.fr';

				$to = '';
				$list_validation = $object->listApprover2;
				foreach($list_validation[2] as $id => $user_static){
					if(!empty($user_static->email)){
						$to .= $user_static->email.', ';
					}
				}
				$to = rtrim($to, ", ");

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/feuilledetemps_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				if($object->status == FeuilleDeTemps::STATUS_APPROBATION1) {
					$msg = $langs->transnoentitiesnoconv("EMailTextFDTApprobation2", $link);
				}
				else {
					$msg = $langs->transnoentitiesnoconv("EMailTextFDTApprobation", $link);
				}

				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if (!empty($to)){
					$res = $mail->sendfile();
				}
				
				if($res){
					return 1;
				}
				else{
					return -1;
				}


			// Envoi d'un mail à l'utilisateur lors d'un refus d'une feuille de temps
			case 'FEUILLEDETEMPS_REFUS':
				global $dolibarr_main_url_root;
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
				$from = 'erp@optim-industries.fr';

				$user_static = new User($this->db);
				$user_static->fetch($object->fk_user);
				if(!empty($user_static->email)){
					$to = $user_static->email;
				}

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$mois = dol_print_date($object->date_fin, '%B');
				$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/timesheet.php?year='.dol_print_date($object->date_fin, '%Y').'&month='.dol_print_date($object->date_fin, '%m').'">ici</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFDTRefus", $mois, GETPOST('raison_refus'), $link);
				$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
				if (!empty($to)){
					$res = $mail->sendfile();
				}
				
				if($res){
					return 1;
				}
				else{
					return -1;
				}
				
			case 'USER_CREATE':
				$fdt = new FeuilleDeTemps($this->db);

				$this->db->begin();

				$fdt->date_debut = dol_get_first_day(dol_print_date(dol_now(), '%Y'), dol_print_date(dol_now(), '%m'));
				$fdt->date_fin = dol_get_last_day(dol_print_date(dol_now(), '%Y'), dol_print_date(dol_now(), '%m'));
				$fdt->ref = "FDT_".str_pad($object->array_options['options_matricule'], 5, '0', STR_PAD_LEFT).'_'.dol_print_date(dol_now(), '%m%Y');
				$fdt->fk_user = $object->id;
				$fdt->status = 0;

				$result = $fdt->create($user, 0);

				if($result){
					$this->db->commit();
					return 1;
				}
				else{
					return -1;
				}
				
			case 'USER_ENABLEDISABLE':
				if($object->status == 0) {
					$fdt = new FeuilleDeTemps($this->db);
					$object_id = $fdt->ExisteDeja(dol_print_date(dol_now(), '%m%Y'), $object->id);

					if($object_id == 0) {
						$this->db->begin();

						$fdt->date_debut = dol_get_first_day(dol_print_date(dol_now(), '%Y'), dol_print_date(dol_now(), '%m'));
						$fdt->date_fin = dol_get_last_day(dol_print_date(dol_now(), '%Y'), dol_print_date(dol_now(), '%m'));
						$fdt->ref = "FDT_".str_pad($object->array_options['options_matricule'], 5, '0', STR_PAD_LEFT).'_'.dol_print_date(dol_now(), '%m%Y');
						$fdt->fk_user = $object->id;
						$fdt->status = 0;

						$res = $fdt->create($user, 0);
					}
				}

				if($res){
					$this->db->commit();
					return 1;
				}
				else{
					return -1;
				}

			case 'TASK_TIMESPENT_CREATE':
				$error = 0;
				// select user if alternant and update alternant for each timespent line creation 
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_time_extrafields (fk_object, alternant)";
				$sql .= " SELECT '.$object->timespent_id.', ue.isalternant";
				$sql .= " FROM ".MAIN_DB_PREFIX."user_extrafields as ue";
				$sql .= ' WHERE  ue.fk_object = '.$object->timespent_fk_user.'';
				$res = $this->db->query($sql);
				if ($res) {
						if (!$error) {
							$this->db->commit();
							return 1;
						} else {
							$this->db->rollback();
							return -3;
						}
					
				} else {
					$error = $this->db->error();
					$this->db->rollback();
					return -1;
				}
				break;
				
			case 'TASK_TIMESPENT_DELETE' :
				$error = 0;
				//Delete timespent line fk_object from element_time_extrafields
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_time_extrafields WHERE fk_object = ".((int) $object->timespent_id);
				$res = $this->db->query($sql);
				if ($res) {
						if (!$error) {
							$this->db->commit();
							return 1;
						} else {
							$this->db->rollback();
							return -3;
						}
					
				} else {
					$error = $this->db->error();
					$this->db->rollback();
					return -1;
				}
				break;

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;


				//Lancer une seule fois
				// case 'USER_MODIFY' :
				// 	// select user if alternant and update alternant for each timespent line creation 
				// 	$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_time_extrafields (fk_object, alternant)";
				// 	$sql .= " SELECT e.rowid, ue.isalternant";
				// 	$sql .= " FROM ".MAIN_DB_PREFIX."user_extrafields as ue";
				// 	// $sql .= " LEFT JOIN".MAIN_DB_PREFIX."user as u on u.rowid = ue.fk_object";
				// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as e on ue.fk_object = e.fk_user";
				// 	$sql .= " WHERE  e.datec > '2024-01-01'";
				// 	$sql .= ' AND ue.fk_object = '.$object->id.'';
				// 	$res = $this->db->query($sql);
			
				// 	if ($res) {
				// 			if (!$error) {
				// 				$this->db->commit();
				// 				return 1;
				// 			} else {
				// 				$this->db->rollback();
				// 				return -3;
				// 			}
						
				// 	} else {
				// 		$error = $this->db->error();
				// 		$this->db->rollback();
				// 		return -1;
				// 	}
				// break; 
				// case 'USER_CREATE' :
				// 	// select user if alternant and update alternant for each timespent line creation 
				// 	$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_time_extrafields (fk_object, alternant)";
				// 	$sql .= " SELECT e.rowid, ue.isalternant";
				// 	$sql .= " FROM ".MAIN_DB_PREFIX."user_extrafields as ue";
				// 	// $sql .= " LEFT JOIN".MAIN_DB_PREFIX."user as u on u.rowid = ue.fk_object";
				// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as e on ue.fk_object = e.fk_user";
				// 	$sql .= " WHERE  e.datec > '2024-01-01'";
				// 	$sql .= ' AND ue.fk_object = '.$object->id.'';
				// 	$res = $this->db->query($sql);
	
				// 	if ($res) {
				// 			if (!$error) {
				// 				$this->db->commit();
				// 				return 1;
				// 			} else {
				// 				$this->db->rollback();
				// 				return -3;
				// 			}
						
				// 	} else {
				// 		$error = $this->db->error();
				// 		$this->db->rollback();
				// 		return -1;
				// 	}
				// break; 
		}

		return 0;
	}
}
