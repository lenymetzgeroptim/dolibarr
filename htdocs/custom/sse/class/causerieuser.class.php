<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/causerieuser.class.php
 * \ingroup     sse
 * \brief       This file is a CRUD class file for CauserieUser (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for CauserieUser
 */
class CauserieUser extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'sse';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'causerieuser';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'sse_causerieuser';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for causerieuser. Must be the part after the 'object_' into object_causerieuser.png
	 */
	public $picto = 'causerieuser@sse';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_INVITE = 2;
	const STATUS_CANCELED = 9;


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validé', '9'=>'Annulé'), 'validate'=>'1',),
		'lastname' => array('type'=>'varchar(255)', 'label'=>'Name', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'css'=>'maxwidth500', 'cssview'=>'wordbreak',),
		'firstname' => array('type'=>'varchar(255)', 'label'=>'Name', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>1, 'css'=>'maxwidth500', 'cssview'=>'wordbreak',),
		'email' => array('type'=>'varchar(255)', 'label'=>'Email', 'enabled'=>'1', 'position'=>100, 'notnull'=>1, 'visible'=>1, 'css'=>'maxwidth500',),
	);
	public $id = 0;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $status;
	public $lastname;
	public $firstname;
	public $email;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'sse_causerieuserline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_causerieuser';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'CauserieUserline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('sse_causerieuserdet');

	// /**
	//  * @var CauserieUserLine[]     Array of subtable lines
	//  */
	// public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db; 

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->sse->causerieuser->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$resultcreate = $this->createCommon($user, $notrigger);

		//$resultvalidate = $this->validate($user, $notrigger);

		return $resultcreate;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (!empty($object->socid) && property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	// /**
	//  * Load object in memory from the database
	//  *
	//  * @param int    $id   Id object
	//  * @param string $ref  Ref
	//  * @return int         <0 if KO, 0 if not found, >0 if OK
	//  */
	// public function fetch($id, $ref = null)
	// {
	// 	$result = $this->fetchCommon($id, $ref);
	// 	if ($result > 0 && !empty($this->table_element_line)) {
	// 		$this->fetchLines();
	// 	}
	// 	return $result;
	// }

	/**
	 *	Load a user from database with its id or ref (login).
	 *  This function does not load permissions, only user properties. Use getrights() for this just after the fetch.
	 *
	 *	@param	int		$id		       		If defined, id to used for search
	 * 	@param  string	$login       		If defined, login to used for search
	 *	@param  string	$sid				If defined, sid to used for search
	 * 	@param	int		$loadpersonalconf	1=also load personal conf of user (in $user->conf->xxx), 0=do not load personal conf.
	 *  @param  int     $entity             If a value is >= 0, we force the search on a specific entity. If -1, means search depens on default setup.
	 *  @param	int		$email       		If defined, email to used for search
	 * 	@return	int							<0 if KO, 0 not found, >0 if OK
	 */
	public function fetch($id = '', $login = '', $email = '')
	{
		global $conf, $user;

		// Clean parameters
		$login = trim($login);
		// Get user
		$sql = "SELECT u.rowid, u.lastname, u.firstname, u.email,";
		//$sql .= " u.login,";
		$sql .= " u.status,";
		//$sql .= " u.datec as datec,";
		$sql .= " u.tms as datem";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerieuser as u";
		// if ($entity < 0) {
		// 	if ((empty($conf->multicompany->enabled) || empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) && (!empty($user->entity))) {
		// 		$sql .= " WHERE u.entity IN (0, ".((int) $conf->entity).")";
		// 	} else {
		// 		$sql .= " WHERE u.entity IS NOT NULL"; // multicompany is on in transverse mode or user making fetch is on entity 0, so user is allowed to fetch anywhere into database
		// 	}
		// } else {
		// 	// The fetch was forced on an entity
		// 	if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		// 		$sql .= " WHERE u.entity IS NOT NULL"; // multicompany is on in transverse mode or user making fetch is on entity 0, so user is allowed to fetch anywhere into database
		// 	} else {
		// 		$sql .= " WHERE u.entity IN (0, ".((int) (($entity != '' && $entity >= 0) ? $entity : $conf->entity)).")"; // search in entity provided in parameter
		// 	}
		// }

		// if ($sid) {
		// 	// permet une recherche du user par son SID ActiveDirectory ou Samba
		// 	$sql .= " AND (u.rowid = '".$this->db->escape($sid)."' OR u.login = '".$this->db->escape($login)."')";
		// } elseif ($login) {
		// 	$sql .= " AND u.login = '".$this->db->escape($login)."'";
		// } elseif ($email) {
		// 	$sql .= " AND u.email = '".$this->db->escape($email)."'";
		// } else {
		// 	$sql .= " AND u.rowid = ".((int) $id);
		// }
		$sql .= " WHERE u.rowid = ".((int) $id);
		//$sql .= " ORDER BY u.entity ASC"; // Avoid random result when there is 2 login in 2 different entities


		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
			
				$this->lastname = $obj->lastname;
				$this->firstname = $obj->firstname;

			//var_dump($this->id);

				//$this->login = $obj->login;
			
				$this->email = $obj->email;
				$this->status		= $obj->status;
				//$this->entity		= $obj->entity;
				$this->thm			= $obj->thm;
				//$this->datec				= $this->db->jdate($obj->datec);
				$this->datem				= $this->db->jdate($obj->datem);
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($result);
			} else {
				$this->error = "USERNOTFOUND";
				dol_syslog(get_class($this)."::fetch user not found", LOG_DEBUG);

				$this->db->free($result);
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
		return 1;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->table_element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key." = ".((int) $value);
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key." IN (".$this->db->sanitize($this->db->escape($value)).")";
				} else {
					$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *	Validate object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerieuser->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerieuser->causerieuser_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ref = '".$this->db->escape($num)."',";
			$sql .= " status = ".self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CAUSERIEUSER_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'causerieuser/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'causerieuser/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->sse->dir_output.'/causerieuser/'.$oldref;
				$dirdest = $conf->sse->dir_output.'/causerieuser/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->sse->dir_output.'/causerieuser/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->sse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CAUSERIEUSER_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->sse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'CAUSERIEUSER_CANCEL');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->sse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'CAUSERIEUSER_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		//print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removeuser&amp;user='.$participant->id.'">';
						
		//print img_picto($langs->trans("RemoveFromGroup"), 'unlink');
		$label = img_picto('', $this->picto).' <u>'.$langs->trans("CauserieUser").'</u>';
	
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/sse/causerieuser_list.php', 1).'?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowCauserieUser");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('causerieuserdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("sse@sse");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_INVITE] = $langs->transnoentitiesnoconv('Invité');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_INVITE] = $langs->transnoentitiesnoconv('Invité');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT rowid, date_creation as datec, tms as datem,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if (!empty($obj->fk_user_author)) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if (!empty($obj->fk_user_valid)) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if (!empty($obj->fk_user_cloture)) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		$this->initAsSpecimenCommon();
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new CauserieUserLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_causerieuser = '.((int) $this->id)));

		if (is_numeric($result)) {
			$this->error = $objectline->error;
			$this->errors = $objectline->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("sse@sse");

		if (empty($conf->global->SSE_CAUSERIEUSER_ADDON)) {
			$conf->global->SSE_CAUSERIEUSER_ADDON = 'mod_causerieuser_standard';
		}

		if (!empty($conf->global->SSE_CAUSERIEUSER_ADDON)) {
			$mybool = false;

			$file = $conf->global->SSE_CAUSERIEUSER_ADDON.".php";
			$classname = $conf->global->SSE_CAUSERIEUSER_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/sse/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$result = 0;
		$includedocgeneration = 0;

		$langs->load("sse@sse");

		if (!dol_strlen($modele)) {
			$modele = 'standard_causerieuser';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->CAUSERIEUSER_ADDON_PDF)) {
				$modele = $conf->global->CAUSERIEUSER_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/sse/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add user into a group
	 *
	 * 	@param	string		$lastname     Nom
	 * 	@param	string		$Firstname     Prénom
	 * 	@param	string		$email     Email
	 *  @param	int		$causerie      Id of causerie
	 *  @param  int		$entity     Entity
	 *  @param  int		$causerieid  $causerieid Id of Causerie
	 * 
	 *  @return int  				<0 if KO, >0 if OK
	 */
	public function SetNewExtern($lastname, $firstname, $email, $causerieid)
	{
		global $conf, $langs, $user;

		// Charger les informations de la causerie
		$causerie = new Causerie($this->db);
		if (!$causerie->fetch($causerieid)) {
			$this->error = "Causerie ID $causerieid not found.";
			return -1;
		}

		$error = 0;
		$entity = 2; // Utiliser l'entité configurée
		$now = dol_now();

		$this->db->begin();

		try {
			// Supprimer l'entrée précédente si elle existe
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "sse_causerieuser";
			$sql .= " WHERE firstname = '" . $this->db->escape($firstname) . "'";
			$sql .= " AND lastname = '" . $this->db->escape($lastname) . "'";
			$sql .= " AND email = '" . $this->db->escape($email) . "'";
			if (!$this->db->query($sql)) {
				throw new Exception($this->db->lasterror());
			}

			// Ajouter un nouvel utilisateur externe à la causerie
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "sse_causerieuser (lastname, firstname, email, fk_user_creat, fk_causerie, status)";
			$sql .= " VALUES ('" . $this->db->escape($lastname) . "',";
			$sql .= "'" . $this->db->escape($firstname) . "',";
			$sql .= "'" . $this->db->escape($email) . "',";
			$sql .= "'" . (int) $user->id . "',";
			$sql .= (int)$causerieid . ", 2)";
			if (!$this->db->query($sql)) {
				throw new Exception($this->db->lasterror());
			}

			$newUserId = $this->db->last_insert_id(MAIN_DB_PREFIX . "sse_causerieuser");

			// Ajouter une ligne dans la table d'assiduité
			$status = ($causerie->status == 4) ? 2 : 0; // Status 2 : actif, 0 : en attente
			$date_creation = ($causerie->status == 4) ? $this->db->idate($now) : 0;

			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "sse_causerieattendance (fk_user, fk_causerie, entity, status, date_creation)";
			$sql .= " VALUES ($newUserId, $causerieid, $entity, $status, '$date_creation')";
			if (!$this->db->query($sql)) {
				throw new Exception($this->db->lasterror());
			}

			// Appeler le trigger USER_EXTERN_MODIFY
			$this->context = array('audit' => $langs->trans("SetNewExtern"), 'newcauseriegroupid' => $causerieid);
			if (!$error) {
				$result = $this->call_trigger('USER_EXTERN_MODIFY', $user);
				if ($result < 0) {
					throw new Exception($langs->trans("TriggerError"));
				}
			}
			$causerie->updateCounts();
			$causerie->updateCountPourcentage();
			$this->db->commit();
			return 1;
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::SetNewExtern " . $this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add user into a group
	 *
	 *  @param	int		$causerieid      Id of causerie
	 *  @param  int		$entity     Entity
	 *  @param  int		$notrigger  Disable triggers
	 *  @return int  				<0 if KO, >0 if OK
	 */
	public function SetExternInCauserieGroup($causerieid, $entity, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;
		$causerie = new Causerie($this->db);
		$causerie->fetch($causerieid);

		$error = 0;
		$now = dol_now();
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_causerieattendance";
		$sql .= " WHERE fk_user  = ".((int) $this->id);
		$sql .= " AND fk_causerie = ".((int) $causerieid);
		$sql .= " AND entity = ".((int) $entity);

		$result = $this->db->query($sql);
		if($causerie->status < $causerie::STATUS_PROGRAMMED) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."sse_causerieattendance (entity, fk_user, fk_causerie)";
			$sql .= " VALUES (".((int) $entity).",".((int) $this->id).",".((int) $causerieid).")";
			$result = $this->db->query($sql);
		}

		if($causerie->status >= $causerie::STATUS_PROGRAMMED) {
			$sql = " UPDATE ".MAIN_DB_PREFIX."sse_causerieattendance";
			$sql .= " SET";
			$sql .= " date_creation ='".$this->db->idate($now)."', ";
			$sql .= " status = 2";
			$sql .= " WHERE fk_user= '".((int) $this->id)."'";
			$sql .= " AND fk_causerie = '".((int) $causerieid)."'";
			$result = $this->db->query($sql);
		}

		
		if ($result) {
			if (!$error && !$notrigger) {
				$this->context = array('audit'=>$langs->trans("SetExternInCauserieGroup"), 'newcauseriegroupid'=>$causerieid);

				// Call trigger
				$result = $this->call_trigger('USER_EXTERN_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::SetExternInCauserieGroup ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Remove a user from a group
	 *
	 *  @param	int   	$group       Id of group
	 *  @param  int		$entity      Entity
	 *  @param  int		$notrigger   Disable triggers
	 *  @return int  			     <0 if KO, >0 if OK
	 */
	public function RemoveExternFromCauserie($causerie, $entity, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_causerieattendance";
		$sql .= " WHERE fk_user = ".((int) $this->id);
		$sql .= " AND fk_causerie = ".((int) $causerie);
		$sql .= " AND entity = ".((int) $entity);

		$result = $this->db->query($sql);
		if ($result) {
			if (!$error && !$notrigger) {
				//$this->oldgroupid = $causerie; // deprecated. Remove this.
				$this->context = array('audit'=>$langs->trans("UserRemovedFromGroup"), 'oldgroupid'=>$causerie);

				// Call trigger
				$result = $this->call_trigger('USER_EXTERN_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::RemoveCFromGroup ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	
}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class CauserieUserLine. You can also remove this and generate a CRUD class for lines objects.
 */
class CauserieUserLine extends CommonObjectLine
{
	// To complete with content of an object CauserieUserLine
	// We should have a field rowid, fk_causerieuser and position

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}

require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/goal.class.php';

class SSEUser extends User
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add user into a group
	 *
	 *  @param	int		$causerieid      Id of causerie
	 *  @param  int		$entity     Entity
	 *  @param  int		$notrigger  Disable triggers
	 *  @return int  				<0 if KO, >0 if OK
	 */
	// public function SetInCauserieGroup($causerieid, $entity, $notrigger = 0)
	// {
	// 	// phpcs:enable
	// 	global $conf, $langs, $user;

	// 	$error = 0;
	// 	$causerie = new Causerie($this->db);
	// 	$causerie->fetch($causerieid);

	// 	$now = dol_now();
	// 	$this->db->begin();

	// 	$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_causerieattendance";
	// 	$sql .= " WHERE fk_user  = ".((int) $this->id);
	// 	$sql .= " AND fk_causerie = ".((int) $causerieid);
	// 	$sql .= " AND entity = ".((int) $entity);

	// 	$result = $this->db->query($sql);
	// 	//if($causerie->status < $causerie::STATUS_PROGRAMMED) {
	// 		$sql = "INSERT INTO ".MAIN_DB_PREFIX."sse_causerieattendance (entity, fk_user, fk_causerie)";
	// 		$sql .= " VALUES (".((int) $entity).",".((int) $this->id).",".((int) $causerieid).")";
	// 		$result = $this->db->query($sql);
	// 	//}

	// 	if($causerie->status >= $causerie::STATUS_PROGRAMMED) {
	// 		$sql = " UPDATE ".MAIN_DB_PREFIX."sse_causerieattendance";
	// 		$sql .= " SET";
	// 		$sql .= " date_creation ='".$this->db->idate($now)."', ";
	// 		$sql .= " status = 2";
	// 		$sql .= " WHERE fk_user= '".((int) $this->id)."'";
	// 		$sql .= " AND fk_causerie = '".((int) $causerieid)."'";
	// 		$result = $this->db->query($sql);
	// 	}

	// 	//$result = $this->db->query($sql);
	// 	if ($result) {
	// 		if (!$error && !$notrigger) {
	// 			//$this->newgroupid = $causerie; // deprecated. Remove this.
	// 			// $this->context = array('audit'=>$langs->trans("UserSetInCauserieGroup"), 'newcauseriegroupid'=>$causerieid);

	// 			// Call trigger
	// 			$result = $this->call_trigger('USER_MODIFY', $user);
	// 			if ($result < 0) {
	// 				$error++;
	// 			}
	// 			// End call triggers
	// 		}

	// 		if (!$error) {
	// 			$this->db->commit();
	// 			return 1;
	// 		} else {
	// 			dol_syslog(get_class($this)."::SetInCauserieGroup ".$this->error, LOG_ERR);
	// 			$this->db->rollback();
	// 			return -2;
	// 		}
	// 	} else {
	// 		$this->error = $this->db->lasterror();
	// 		$this->db->rollback();
	// 		return -1;
	// 	}
	// }

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add user into a group
	 *
	 *  @param	int		$goalid      Id of causerie
	 *  @param  int		$entity     Entity
	 *  @param  int		$notrigger  Disable triggers
	 *  @return int  				<0 if KO, >0 if OK
	 */
	public function SetInGoalGroup($goalid, $entity, $date_debut, $date_fin, $nb_causerie, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$error = 0;
		$goal = new Goal($this->db);
		$goal->fetch($goalid);
		$status = 2;

		$now = dol_now();
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_goalelement";
		$sql .= " WHERE fk_user  = ".((int) $this->id);
		$sql .= " AND fk_goal = ".((int) $goalid);
		$sql .= " AND entity = ".((int) $entity);

		$result = $this->db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."sse_goalelement (entity, fk_user, fk_goal, date_debut, date_fin, nbcauserie, status)";
		$sql .= " VALUES (".((int) $entity).",".((int) $this->id).",".((int) $goalid)."";
		$sql .= ", '".$this->db->idate($date_debut)."'";
		$sql .= ", '".$this->db->idate($date_fin)."',";
		$sql .= ((int) $nb_causerie).",";
		$sql .= ((int) $status).")";

		$result = $this->db->query($sql);

		if ($result) {
			if (!$error && !$notrigger) {
				//$this->newgroupid = $goal; // deprecated. Remove this.
				// $this->context = array('audit'=>$langs->trans("UserSetInGoalGroup"), 'newgoalgroupid'=>$goalid);

				// Call trigger
				$result = $this->call_trigger('SSEUSER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::SetInGoalGroup ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add user into a group
	 *
	 *  @param	int		$goalid      Id of causerie
	 *  @param  int		$entity     Entity
	 *  @param  int		$notrigger  Disable triggers
	 *  @return int  				<0 if KO, >0 if OK
	 */
	public function UpdateInGoalGroup($goalid, $entity, $date_debut, $date_fin, $nb_causerie, $notrigger = 0)
	{
	// phpcs:enable
	global $conf, $langs, $user;
	
	$this->db->begin();

	$sql = " UPDATE ".MAIN_DB_PREFIX."sse_goalelement";
	$sql .= " SET";
	$sql .= " entity ='".((int) $entity)."', ";
	$sql .= " date_debut ='".$this->db->idate($date_debut)."', ";
	$sql .= " date_fin ='".$this->db->idate($date_fin)."', ";
	$sql .= " status = 2";
	$sql .= " WHERE fk_user= '".((int) $this->id)."'";
	$sql .= " AND fk_goal = '".((int) $goalid)."'";
	$result = $this->db->query($sql);

	if ($result) {
		if (!$error && !$notrigger) {
			// $this->context = array('audit'=>$langs->trans("SetExternInCauserieGroup"), 'newcauseriegroupid'=>$causerieid);

			// Call trigger
			// $result = $this->call_trigger('GOALELEMENT_MODIFY', $user);
			// if ($result < 0) {
			// 	$error++;
			// }
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			dol_syslog(get_class($this)."::SetExternInCauserieGroup ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} else {
		$this->error = $this->db->lasterror();
		$this->db->rollback();
		return -1;
	}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Remove a user from a causerie
	 *
	 *  @param	int   	$causerie       Id of causerie
	 *  @param  int		$entity      Entity
	 *  @param  int		$notrigger   Disable triggers
	 *  @return int  			     <0 if KO, >0 if OK
	 */
	// public function RemoveFromCauserie($causerie, $entity, $notrigger = 0)
	// {
	// 	// phpcs:enable
	// 	global $conf, $langs, $user;

	// 	$error = 0;

	// 	$this->db->begin();

	// 	$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_causerieattendance";
	// 	$sql .= " WHERE fk_user  = ".((int) $this->id);
	// 	$sql .= " AND fk_causerie = ".((int) $causerie);
	// 	$sql .= " AND entity = ".((int) $entity);

	// 	$result = $this->db->query($sql);
		
	// 	if ($result) {
	// 		if (!$error && !$notrigger) {
	// 			//$this->oldgroupid = $causerie; // deprecated. Remove this.
	// 			// $this->context = array('audit'=>$langs->trans("UserRemovedFromGroup"), 'oldgroupid'=>$causerie);

	// 			// // Call trigger
	// 			$result = $this->call_trigger('USER_MODIFY', $user);
	// 			if ($result < 0) {
	// 				$error++;
	// 			}
	// 			// End call triggers
	// 		}

	// 		if (!$error) {
	// 			$this->db->commit();
	// 			return 1;
	// 		} else {
	// 			dol_syslog(get_class($this)."::RemoveFromCauserie ".$this->error, LOG_ERR);
	// 			$this->db->rollback();
	// 			return -2;
	// 		}
	// 	} else {
	// 		$this->error = $this->db->lasterror();
	// 		$this->db->rollback();
	// 		return -1;
	// 	}
	// }

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Remove a user from a causerie
	 *
	 *  @param	int   	$goal       Id of causerie
	 *  @param  int		$entity      Entity
	 *  @param  int		$notrigger   Disable triggers
	 *  @return int  			     <0 if KO, >0 if OK
	 */
	public function RemoveFromGoal($goal, $entity, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_goalelement";
		$sql .= " WHERE fk_user  = ".((int) $this->id);
		$sql .= " AND fk_goal = ".((int) $goal);
		$sql .= " AND entity = ".((int) $entity);

		$result = $this->db->query($sql);
		
		if ($result) {
			if (!$error && !$notrigger) {
				//$this->oldgroupid = $goal; // deprecated. Remove this.
				// $this->context = array('audit'=>$langs->trans("UserRemoveFromGoal"), 'oldgroupid'=>$goal);

				// // Call trigger
				$result = $this->call_trigger('SSEUSER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::RemoveFromGoal ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	
}
