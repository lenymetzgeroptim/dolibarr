<?php
/* Copyright (C) 2023 METZGER Leny <l.metzger@optim-industries.fr>
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
 * \file    core/triggers/interface_99_modAddOnUserGroup_AddOnUserGroupTriggers.class.php
 * \ingroup addonusergroup
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modAddOnUserGroup_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


/**
 *  Class of triggers for AddOnUserGroup module
 */
class InterfaceAddOnUserGroupTriggers extends DolibarrTriggers
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
		$this->description = "AddOnUserGroup triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'addonusergroup@addonusergroup';
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
		if (empty($conf->addonusergroup) || empty($conf->addonusergroup->enabled)) {
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

		$langs->loadLangs(array("addonusergroup@addonusergroup"));

		// Or you can execute some code here
		switch ($action) {
			case 'USERGROUP_CREATE':
				$object->array_options['options_history'] .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Création du groupe par '.$user->firstname.' '.$user->lastname.'</span><br>';
				$res = $object->updateExtraField('history');
				if($res) {
					return 1;
				}
				else {
					return -1;
				}

			case 'USER_MODIFY':
				$res = 1;

				if($object->context['audit'] == 'UserSetInGroup') {
					$usergroup = new UserGroup($this->db);
					$usergroup->fetch($object->context['newgroupid']);

					$usergroup->array_options['options_history'] .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Ajout de '.$object->firstname.' '.$object->lastname.' dans le groupe par '.$user->firstname.' '.$user->lastname.'</span><br>';
					$res = $usergroup->updateExtraField('history');

					$subject = '[OPTIM Industries] Notification automatique Groupes';
					$from = 'erp@optim-industries.fr';

					// Mail envoyé à l'interéssé
					$to = $object->email;

					if($object->context['newgroupid'] == 16){
						$msg = str_replace('__USER_FULLNAME__', $object->firstname." ".$object->lastname, dolibarr_get_const($this->db, 'EMailTextAddUserGroupRSR'));
					}
					else {
						$msg = $langs->transnoentitiesnoconv("EMailTextAddUserGroupToUser", $object->firstname." ".$object->lastname, $usergroup->nom);
					}

					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);

					if (!empty($to)){
						$res = $res && $mail->sendfile();
					}

					// Mail envoyé au RAN et RD (dans le cas du groupe RSR)
					$msg2 = $langs->transnoentitiesnoconv("EMailTextAddUserGroupToResp", $object->firstname." ".$object->lastname, $usergroup->nom);

					$to = '';
					if($object->context['newgroupid'] == 16){
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

						$user_validation_1 = new User($this->db);
						if(!empty($object->fk_user)){
							$user_validation_1->fetch($object->fk_user);
						}

						$user_validation_2 = new User($this->db);
						if(!empty($user_validation_1->fk_user) && $user_validation_1->fk_user != 16){
							$user_validation_2->fetch($user_validation_1->fk_user);
						}

						if(!empty($user_validation_2->email)) {
							$to .= ', '.$user_validation_2->email;
						}
						elseif(!empty($user_validation_1->email)) {
							$to .= ', '.$user_validation_1->email;
						}
					}
					else {
						$user_validation_1 = new User($this->db);
						if(!empty($object->fk_user)){
							$user_validation_1->fetch($object->fk_user);
						}

						$user_validation_2 = new User($this->db);
						if(!empty($user_validation_1->fk_user) && $user_validation_1->fk_user != 16){
							$user_validation_2->fetch($user_validation_1->fk_user);
						}

						if(!empty($user_validation_2->email)) {
							$to .= $user_validation_2->email;
						}
						elseif(!empty($user_validation_1->email)) {
							$to .= $user_validation_1->email;
						}
					}

					$mail2 = new CMailFile($subject, $to, $from, $msg2, '', '', '', '', '', 0, 1);

					if (!empty($to)){
						$res = $res && $mail2->sendfile();
					}
				}
				elseif($object->context['audit'] == 'UserRemovedFromGroup') {
					$usergroup = new UserGroup($this->db);
					$usergroup->fetch($object->context['oldgroupid']);

					$usergroup->array_options['options_history'] .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Suppression de '.$object->firstname.' '.$object->lastname.' du groupe par '.$user->firstname.' '.$user->lastname.'</span><br>';
					$res = $usergroup->updateExtraField('history');

					$subject = '[OPTIM Industries] Notification automatique Groupes';
					$from = 'erp@optim-industries.fr';

					// Mail envoyé à l'interéssé
					$to = $object->email;
					$msg = $langs->transnoentitiesnoconv("EMailTextDeleteUserGroupToUser", $object->firstname." ".$object->lastname, $usergroup->nom);

					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);

					if (!empty($to)){
						$res = $res && $mail->sendfile();
					}

					// Mail envoyé au RAN et RD (dans le cas du groupe RSR)
					$msg2 = $langs->transnoentitiesnoconv("EMailTextDeleteUserGroupToResp", $object->firstname." ".$object->lastname, $usergroup->nom);

					$to = '';
					if($object->context['oldgroupid'] == 16){
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

						$user_validation_1 = new User($this->db);
						if(!empty($object->fk_user)){
							$user_validation_1->fetch($object->fk_user);
						}

						$user_validation_2 = new User($this->db);
						if(!empty($user_validation_1->fk_user) && $user_validation_1->fk_user != 16){
							$user_validation_2->fetch($user_validation_1->fk_user);
						}

						if(!empty($user_validation_2->email)) {
							$to .= ', '.$user_validation_2->email;
						}
						elseif(!empty($user_validation_1->email)) {
							$to .= ', '.$user_validation_1->email;
						}
					}
					else {
						$user_validation_1 = new User($this->db);
						if(!empty($object->fk_user)){
							$user_validation_1->fetch($object->fk_user);
						}

						$user_validation_2 = new User($this->db);
						if(!empty($user_validation_1->fk_user) && $user_validation_1->fk_user != 16){
							$user_validation_2->fetch($user_validation_1->fk_user);
						}

						if(!empty($user_validation_2->email)) {
							$to .= $user_validation_2->email;
						}
						elseif(!empty($user_validation_1->email)) {
							$to .= $user_validation_1->email;
						}
					}

					$mail2 = new CMailFile($subject, $to, $from, $msg2, '', '', '', '', '', 0, 1);

					if (!empty($to)){
						$res = $res && $mail2->sendfile();
					}
				}

				if($res) {
					return 1;
				}
				else {
					return -1;
				}

			case 'USERGROUP_MODIFY':
				$res = 1;
				global $module;

				if(strpos($object->context['audit'], $langs->trans("PermissionsAdd")) !== false) {
					if($module == '') {
						$right_id = substr($object->context['audit'], strpos($object->context['audit'], 'id=') + 3, -1);
						
						$sql = "SELECT r.id, r.libelle as label";
						$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
						$sql .= " WHERE r.id = ".(int)$right_id;

						$result = $this->db->query($sql);

						if($result) {
							$obj = $this->db->fetch_object($result);

							$object->array_options['options_history'] .= '<span style="color: green;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Ajout du droit "'.$obj->label.'" par '.$user->firstname.' '.$user->lastname.'</span><br>';
							$res = $object->updateExtraField('history');
						}
					}
					elseif($module == 'allmodules') {
						$object->array_options['options_history'] .= '<span style="color: green;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Ajout de l\'ensemble des droits par '.$user->firstname.' '.$user->lastname.'</span><br>';
						$res = $object->updateExtraField('history');
					}
					else {
						$object->array_options['options_history'] .= '<span style="color: green;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Ajout de l\'ensemble des droits du module "'.$langs->trans($module).'" par '.$user->firstname.' '.$user->lastname.'</span><br>';
						$res = $object->updateExtraField('history');
					}
				}
				elseif(strpos($object->context['audit'], $langs->trans("PermissionsDelete")) !== false) {
					if($module == '') {
						$right_id = substr($object->context['audit'], strpos($object->context['audit'], 'id=') + 3, -1);
						
						$sql = "SELECT r.id, r.libelle as label";
						$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
						$sql .= " WHERE r.id = ".(int)$right_id;

						$result = $this->db->query($sql);

						if($result) {
							$obj = $this->db->fetch_object($result);

							$object->array_options['options_history'] .= '<span style="color: red;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Suppression du droit "'.$obj->label.'" par '.$user->firstname.' '.$user->lastname.'</span><br>';
							$res = $object->updateExtraField('history');
						}
					}
					elseif($module == 'allmodules') {
						$object->array_options['options_history'] .= '<span style="color: red;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Suppression de l\'ensemble des droits par '.$user->firstname.' '.$user->lastname.'</span><br>';
						$res = $object->updateExtraField('history');
					}
					else {
						$object->array_options['options_history'] .= '<span style="color: red;"><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Suppression de l\'ensemble des droits du module "'.$langs->trans($module).'" par '.$user->firstname.' '.$user->lastname.'</span><br>';
						$res = $object->updateExtraField('history');
					}
				}
				
				if($res) {
					return 1;
				}
				else {
					return -1;
				}

			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
