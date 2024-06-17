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
	require_once DOL_DOCUMENT_ROOT.'/custom/rgpd/class/adminrgpd.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';




	/**
	 *  Class of triggers for MyModule module
	 */
	class InterfaceRgpdTriggers extends DolibarrTriggers
{

		/**
		 * Constructor
		 *
		 * @param DoliDB $db Database handler
		 */
		public function __construct($db){
			$this->db = $db;

			$this->name = preg_replace('/^Interface/i', '', get_class($this));
			$this->family = "demo";
			$this->description = "edit user triggers.";
			// 'development', 'experimental', 'dolibarr' or version
			$this->version = 'development';
			$this->picto = 'mymodule@mymodule';
		}

		/**
		 * Trigger name
		 *
		 * @return string Name of trigger file
		 */
		public function getName(){
			return $this->name;
		}

		/**
		 * Trigger description
		 *
		 * @return string Description of trigger file
		 */
		public function getDesc(){
			return $this->description;
		}
		
		public function edit_user($id, $sqlmail){
			$this->db->begin();
			// $sql = "INSERT INTO ".MAIN_DB_PREFIX."user";
			// $sql .= " ("$sqlmail")";
			// $sql .= " VALUES";
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
		public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf){
			if (empty($conf->rgpd) || empty($conf->rgpd->enabled)) {
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
		
				case 'USER_MODIFY':
					global $db, $user;
					$error = 0;

					if($object->array_options['options_acceptationdroitimage'] == 0) {
						$sql =  " UPDATE ".MAIN_DB_PREFIX."user";
						$sql .= " SET photo = NULL" ;
						$result = $this->db->query($sql);
						if (!$result) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}
					}

					if(!$error && $object->oldcopy->array_options['options_acceptationdroitimage'] != $object->array_options['options_acceptationdroitimage']) {						
						$subject = '[OPTIM Industries] Notification automatique Droit à l\'image';
						$from = 'erp@optim-industries.fr';
						$to =  'dpo@optim-industries.fr';
						$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
						$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
			
						$logo = $urlwithroot.'/viewimage.php?modulepart=mycompany&file=logos%2Fthumbs%2FLogoOptim_FdBlancCroppe_25%25_small.jpg';
			
						if($object->array_options['options_acceptationdroitimage'] == 1){
							//$msg = $langs->transnoentitiesnoconv("EMailTextRGPDAccept" ,$user->firstname, $user->firstname." ".$user->lastname);
							$msg= 'bonjour, <br> <br>' .$user->firstname.' '.$user->lastname.' a accepté les droits à l\'image.';
							$msg.='<br> <br> Cordialement. <br> <br> <br> <br> <div><img src="'.$logo.'"></div>';
							$logo = $urlwithroot.'/viewimage.php?modulepart=mycompany&file=logos%2Fthumbs%2FLogoOptim_FdBlancCroppe_25%25_small.jpg';
			
						}
						elseif ($object->array_options['options_acceptationdroitimage'] == 0){
							//$msg = $langs->transnoentitiesnoconv("EMailTextRGPDRefus", $user->firstname." ".$user->lastname);
							$msg= 'bonjour, <br> <br>'.$user->firstname.' '.$user->lastname.' a refusé les droits à l\'image.';
							$msg.='<br> <br> Cordialement. <br> <br> <br> <br> <div><img src="'.$logo.'"></div>';
							$logo = $urlwithroot.'/viewimage.php?modulepart=mycompany&file=logos%2Fthumbs%2FLogoOptim_FdBlancCroppe_25%25_small.jpg';
						}
			
						$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
			
						if (!empty($to)){
							$res = $mail->sendfile();
						}
					}

					if (!$error) {
						$this->db->commit();
						return 1;
					} else {
						$this->db->rollback();
						return -1;
					}

				default:
					dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
					break;
			}

			return 0;
		
		}
		
}
