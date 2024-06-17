<?php
/* Copyright (C) 2023 METZGER Leny <l.metzger@optim-industries.fr>
 * Addon 2023 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 * \file    core/triggers/interface_99_modDonneesRH_DonneesRHTriggers.class.php
 * \ingroup donneesrh
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modDonneesRH_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/responsible.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';


/**
 *  Class of triggers for DonneesRH module
 */
class InterfaceDonneesRHTriggers extends DolibarrTriggers
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
		$this->description = "DonneesRH triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'donneesrh@donneesrh';
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
	 * Update responsable d'antenne  
	 */
	public function upDateManager()
	{
		// update responsable d'antenne if user is modified
		$responsible = new Responsible($this->db);
		$managers = $responsible->getManager();
		$error = 0;

		foreach($managers as $key => $employees) {
			foreach($employees as $employee) {
				if($employee != null) {
						$sql = "UPDATE ".MAIN_DB_PREFIX."user_extrafields";
						$sql .= " SET resantenne = ".$key;
						$sql .= " WHERE fk_object=" . (int) $employee;
						$result = $this->db->query($sql);
						if (!$result) {
							$error++;
							dol_print_error($this->db);
						}

						if ($error) {
							$this->db->rollback();
						} else {
							$this->db->commit();
						}
				}
			}
		}
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
		if (empty($conf->donneesrh) || empty($conf->donneesrh->enabled)) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		global $conf, $user, $langs, $db;
		$error = 0; // Error counter

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
			// case 'USER_CREATE':
			// 	$extrafields = new ExtraFields($db);

			// 	$userField = new UserField($db);
			// 	$userField->id = $object->id;
			// 	$userField->fetch_optionals();

			// 	$db->begin();

			// 	$table_element = 'donneesrh_userfield';
			// 	$extrafields->fetch_name_optionals_label($table_element);

			// 	// Fill array 'array_options' with data from add form
			// 	$ret = $extrafields->setOptionalsFromPost(null, $userField);
			// 	if ($ret < 0) {
			// 		$error++;
			// 	}

			// 	// Actions on extra fields
			// 	if (!$error) {
			// 		$result = $userField->insertExtraFields();
			// 		if ($result < 0) {
			// 			$error++;
			// 		}
			// 	}

			// 	if (!$error) {
			// 		$db->commit();
			// 		return 1;
			// 	} else {
			// 		$db->rollback();
			// 		return -1;
			// 	}
				
			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
