<?php
/* Copyright (C) 2017  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023  Frédéric France          <frederic.france@netlogic.fr>
 * Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 * \file        class/charge.class.php
 * \ingroup     workload
 * \brief       This file is a CRUD class file for Charge (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

/**
 * Class for Charge
 */
class Charge extends User
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'workload';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'charge';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'workload_charge';

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
	 * @var string String with name of icon for charge. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'charge@workload' if picto is file 'img/object_charge.png'.
	 */
	public $picto = 'fa-file';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;


	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>1, 'alwayseditable'=>'1', 'searchall'=>1, 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'help'=>"Help text", 'showoncombobox'=>'2', 'validate'=>'1',),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1:((status:=:1) AND (entity:IN:__SHARED_ENTITIES__))', 'label'=>'ThirdParty', 'picto'=>'company', 'enabled'=>'isModEnabled("societe")', 'position'=>50, 'notnull'=>-1, 'visible'=>1, 'index'=>1, 'css'=>'maxwidth500 widthcentpercentminusxx', 'csslist'=>'tdoverflowmax150', 'help'=>"OrganizationEventLinkToThirdParty", 'validate'=>'1',),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Project', 'picto'=>'project', 'enabled'=>'isModEnabled("project")', 'position'=>52, 'notnull'=>-1, 'visible'=>-1, 'index'=>1, 'css'=>'maxwidth500 widthcentpercentminusxx', 'csslist'=>'tdoverflowmax150', 'validate'=>'1',),
		'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>3, 'validate'=>'1',),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'picto'=>'user', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid', 'csslist'=>'tdoverflowmax150',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'picto'=>'user', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2, 'csslist'=>'tdoverflowmax150',),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>2000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Valid&eacute;', '9'=>'Annul&eacute;'), 'validate'=>'1',),
	);
	public $rowid;
	public $ref;
	public $label;
	public $fk_soc;
	public $fk_project;
	public $description;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $status;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'workload_chargeline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_charge';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Chargeline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array('mychildtable' => array('name'=>'Charge', 'fk_element'=>'fk_charge'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('workload_chargedet');

	// /**
	//  * @var ChargeLine[]     Array of subtable lines
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

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('workload', 'charge', 'read')) {
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
					//var_dump($key);
					//var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->setErrorsFromObject($object);
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

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		return $result;
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
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
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
					$sqlwhere[] = $key." LIKE '%".$this->db->escapeforlike($this->db->escape($value))."%'";
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
	 * @param bool $notrigger  false=launch triggers, true=disable triggers
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('workload','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && !empty($user->rights->workload->charge->charge_advance->validate))))
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
				$result = $this->call_trigger('MYOBJECT_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'charge/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'charge/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'charge/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'charge/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->workload->dir_output.'/charge/'.$oldref;
				$dirdest = $conf->workload->dir_output.'/charge/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->workload->dir_output.'/charge/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('workload','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('workload','workload_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'WORKLOAD_MYOBJECT_UNVALIDATE');
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('workload','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('workload','workload_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'WORKLOAD_MYOBJECT_CANCEL');
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
		if ($this->status == self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('workload','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('workload','workload_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'WORKLOAD_MYOBJECT_REOPEN');
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param 	array 	$params 	Params to construct tooltip data
	 * @since 	v18
	 * @return 	array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowCharge")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Charge").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		$datas['ref'] .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		return $datas;
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
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element.($this->module ? '@'.$this->module : ''),
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = dol_buildpath('/workload/charge_card.php', 1).'?id='.$this->id;

		if ($option !== 'nolink') {
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
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowCharge");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' :  ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
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
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
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
					if (!getDolGlobalString(strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS')) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array($this->element.'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *	Return a thumb for kanban views
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $conf, $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'label')) {
			$return .= ' <div class="inline-block opacitymedium valignmiddle tdoverflowmax100">'.$this->label.'</div>';
		}
		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<br><div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br>';
			$return .= '<span class="info-box-label amount">'.price($this->amount, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status margintoponly">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
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
	 *  Return the label of a given status
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
			//$langs->load("workload@workload");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
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
		$sql = "SELECT rowid,";
		$sql .= " date_creation as datec, tms as datem,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				if (!empty($obj->fk_user_valid)) {
					$this->user_validation_id = $obj->fk_user_valid;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
				if (!empty($obj->datev)) {
					$this->date_validation   = empty($obj->datev) ? '' : $this->db->jdate($obj->datev);
				}
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

		$objectline = new ChargeLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_charge = '.((int) $this->id)));

		if (is_numeric($result)) {
			$this->setErrorsFromObject($objectline);
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
		$langs->load("workload@workload");

		if (!getDolGlobalString('WORKLOAD_MYOBJECT_ADDON')) {
			$conf->global->WORKLOAD_MYOBJECT_ADDON = 'mod_charge_standard';
		}

		if (getDolGlobalString('WORKLOAD_MYOBJECT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('WORKLOAD_MYOBJECT_ADDON').".php";
			$classname = getDolGlobalString('WORKLOAD_MYOBJECT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/workload/");

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
		$includedocgeneration = 1;

		$langs->load("workload@workload");

		if (!dol_strlen($modele)) {
			$modele = 'standard_charge';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('MYOBJECT_ADDON_PDF')) {
				$modele = getDolGlobalString('MYOBJECT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/workload/doc/";

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
		//global $conf, $langs;

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

	// public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = array(), $filtermode = 'AND', $entityfilter = false)
	// {
	//   global $conf, $user;
   
	//   $sql = "SELECT t.rowid";
	//   $sql .= ' FROM '.$this->db->prefix().$this->table_element.' as t ';
   
	//   if ($entityfilter) {
	// 	if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
	// 	  if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
	// 		$sql .= " WHERE t.entity IS NOT NULL"; // Show all users
	// 	  } else {
	// 		$sql .= ",".$this->db->prefix()."usergroup_user as ug";
	// 		$sql .= " WHERE ((ug.fk_user = t.rowid";
	// 		$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
	// 		$sql .= " OR t.entity = 0)"; // Show always superadmin
	// 	  }
	// 	} else {
	// 	  $sql .= " WHERE t.entity IN (".getEntity('user').")";
	// 	}
	//   } else {
	// 	$sql .= " WHERE 1 = 1";
	//   }
   
	//   // Manage filter
	//   $sqlwhere = array();
	//   if (!empty($filter)) {
	// 	foreach ($filter as $key => $value) {
	// 	  if ($key == 't.rowid') {
	// 		$sqlwhere[] = $key." = ".((int) $value);
	// 	  } elseif (isset($this->fields[$key]['type']) && in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
	// 		$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
	// 	  } elseif ($key == 'customsql') {
	// 		$sqlwhere[] = $value;
	// 	  } else {
	// 		$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
	// 	  }
	// 	}
	//   }
	//   if (count($sqlwhere) > 0) {
	// 	$sql .= ' AND ('.implode(' '.$this->db->escape($filtermode).' ', $sqlwhere).')';
	//   }
	//   $sql .= $this->db->order($sortfield, $sortorder);
	//   if ($limit) {
	// 	$sql .= $this->db->plimit($limit + 1, $offset);
	//   }
   
	//   dol_syslog(__METHOD__, LOG_DEBUG);
   
	//   $resql = $this->db->query($sql);
	//   if ($resql) {
	// 	$this->users = array();
	// 	$num = $this->db->num_rows($resql);
	// 	if ($num) {
	// 	  while ($obj = $this->db->fetch_object($resql)) {
	// 		$line = new self($this->db);
	// 		$result = $line->fetch($obj->rowid);
	// 		if ($result > 0 && !empty($line->id)) {
	// 		  $this->users[$obj->rowid] = clone $line;
	// 		}
	// 	  }
	// 	  $this->db->free($resql);
	// 	}
	// 	return $num;
	//   } else {
	// 	$this->errors[] = $this->db->lasterror();
	// 	return -1;
	//   }
	// }

	
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
	public function getUsers()
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT t.rowid as id, t.firstname, t.lastname,  CONCAT(t.firstname, ' ', t.lastname) AS fullname";
		// $sql .= " ,DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00') as date_start,";	
		// $sql .= " DATE_FORMAT(NOW(), '%Y-%m-%d 23:59:59') as date_end";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as t";
		
		$sql .= " WHERE t.statut = '1'";
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records[$obj->id] = $obj;

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
	 * @param array of users
	 * @param date start
	 * @param date end
	 * 
	 * @return array of object commande
	 * 
	 */
	// public function getContactsAndOrders($arr_user, $date_start, $date_end, $option)
	// {
	// 	global $db;
	// 	$now = dol_now();
	// 	$error = 0;
	// 	$listContactProject = $this->listProjectsContact();
	// 	$listProject = $this->fetchProjects();
	// 	$listOrder = $this->fetchOders();

	// 	$comms = array();
	// 	$sql = "SELECT ec.fk_socpeople as id,  'CO' as idref, c.fk_projet, ec.element_id, c.ref, ce.date_start as date_start, DATE_FORMAT(c.date_livraison ,'%Y-%m-%d') as date_end, c.fk_statut,";
	// 	$sql .= " ec.fk_c_type_contact, t.statut as statuscontact,";
	// 	$sql .= " t.lastname, t.firstname,ce.pdc_etp_cde,ce.agence,";
	// 	$sql .= " tc.source, tc.element, tc.code, tc.libelle, p.ref as projet_ref";
	// 	$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c on ec.element_id = c.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ce on ce.fk_object = c.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = c.fk_projet";
	// 	$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
	// 	$sql .= " AND tc.element = 'commande'";
	// 	$sql .= " AND tc.source = 'internal'";
	// 	$sql .= " AND tc.active = 1";
	// 	$sql .= " AND tc.code = 'PdC'";
	// 	$sql .= " AND t.statut = '1'";
	// 	$sql .= " AND c.fk_statut <> '3'";
	// 	$sql .= " AND c.date_livraison >= '".$this->db->idate($now)."'";
		
	// 	if(!empty($arr_user)) {
	// 		$sql .= " AND t.rowid IN (".implode(', ', $arr_user).")";
	// 	}
		
	// 	$sql .= " ORDER BY t.rowid DESC";
		
	// 	$resql = $db->query($sql);

	// 	if ($resql) {
	// 		$num = $db->num_rows($resql);
	// 		$i = 0;
	// 		while ($i < $num) {
	// 			$obj = $db->fetch_object($resql);
	// 			if ($obj) {
	// 				$projects[] = $obj->fk_projet;
	// 				$orders[] = $obj->element_id;
	// 				$users[] = $obj->id;
					
	// 				$pdcEtpCde[$obj->fk_projet] = $obj->pdc_etp_cde;
	// 				//contacts with project but with user and order
	// 				$comms[] = $obj;	
	// 			}
	// 			$i++;
	// 		}
	
	// 		if($option == 'project') {
	// 			foreach($listProject as $key => $value) {
	// 				if(!in_array($value->fk_projet, $projects)) {
	// 					$proj = new Project($db);
	// 					$proj->fetch($value->fk_projet);
	// 					$listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');
	// 					// var_dump($value->fk_projet);
	// 					//contacts with project but without order
	// 					foreach($listofproject as $userid) {
	// 						$projarr[] = $value->fk_projet;
	// 						 if(in_array($value->id, $users)) {
	// 							var_dump($comms);
	// 							$comms[] = (object) array('id' => $userid, 'element_id' => $value->fk_projet, 'ref' => $value->ref, 'fk_projet' => $value->fk_projet, 'date_start' => $value->date_start, 'date_end' => $value->date_end, 'pdc_etp_proj' => sizeof($listofproject), 'agence' => $value->agence, 'avl' => 'yes', 'projet_ref' => $value->ref, 'idref' => $value->idref);
	// 						}
	// 					}

	// 					//project without order and user
	// 					if(!in_array($value->fk_projet, $projarr)) {
	// 						$comms[] = (object) array('element_id' => $value->fk_projet, 'ref' => $value->ref, 'fk_projet' => $value->fk_projet, 'date_start' => $value->date_start, 'date_end' => $value->date_end, 'pdc_etp_proj' => 0, 'agence' => $value->agence, 'avl' => 'yes', 'projet_ref' => $value->ref, 'idref' => $value->idref);
	// 					}
	// 				}
	// 			}
	
	// 			//contacts with project but whith order and without user
	// 			foreach($listContactProject as $key => $value) {
	// 				  if(in_array($value->element_id, $projects) && !in_array($value->id, $users)) {
	// 					$proj = new Project($db);
	// 					$proj->fetch($value->element_id);
	// 					$listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');
	// 					$missing = $pdcEtpCde[$value->element_id] - sizeof($listofproject);
	// 					$comms[] = (object) array('id' => $value->id, 'element_id' => $value->element_id, 'ref' => $value->ref, 'fk_projet' => $value->element_id, 'date_start' => $value->date_start, 'date_end' => $value->date_end, 'pdc_etp_proj' => sizeof($listofproject), 'agence' => $value->agence, 'missing' => $missing, 'projet_ref' => $value->ref, 'idref' => $value->idref);
	// 				}
	// 			}
		
	// 		}
	
	// 		if($option == 'order') {
	// 			foreach($listOrder as $key => $value) {
	// 				if(!in_array($value->fk_projet, $projects)) {
	// 					$proj = new Project($db);
	// 					$proj->fetch($value->fk_projet);
	// 					$listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');
	// 					//contacts with project but without order
	// 					//order without user
	// 					$comms[] = (object) array('id' => null, 'element_id' => $value->element_id, 'projet_ref' => $value->projet_ref, 'fk_projet' => $value->fk_projet, 'date_start' => $value->date_start, 'date_end' => $value->date_end, 'pdc_etp_cde' => 0, 'pdc_etp_proj' => !empty($listofproject) ? sizeof($listofproject) : 0, 'agence' => $value->agence, 'avl' => 'yes', 'idref' => $value->idref, 'ref' => $value->ref);
	// 				}
					
	// 			}
	// 		}
	
	// 		$db->free($resql);

	// 		return $comms;
	// 	} else {
	// 		dol_print_error($db);
	// 	}
	// }

	/***
	 * Get chef de projet and project id
	 * 
	 * @return 
	 */
	public function getChefDeProjet()
	{
		global $db;
		$now = dol_now();
		$res = array();
		$sql = "SELECT ec.fk_socpeople AS id, ec.element_id AS projet_id, p.ref AS projet_ref,
               t.lastname, t.firstname
        FROM ".MAIN_DB_PREFIX."element_contact AS ec
        INNER JOIN ".MAIN_DB_PREFIX."c_type_contact AS tc ON ec.fk_c_type_contact = tc.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."user AS t ON ec.fk_socpeople = t.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON ec.element_id = p.rowid
        WHERE tc.element = 'project'
        AND tc.source = 'internal'
        AND tc.active = 1
        AND tc.code = 'PROJECTLEADER'
        AND p.fk_statut < 2";

								
		// $sql .= " AND (c.date_livraison >= '".$db->idate($now)."' OR c.date_livraison IS NULL)";
		// $sql .= " AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)";


		if (!empty($arr_user)) {
			$sql .= " AND t.rowid IN (".implode(', ', $arr_user).")";
		}

		$sql .= " ORDER BY t.rowid DESC";

		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$res[] = $obj;
			}
		}
		return $res;
	}


	public function getContactsAndProjAbs()
	{
		global $db;
		$now = dol_now();
		$comms = [];
	
		$sql = "SELECT ec.fk_socpeople AS id, 'PROJ' AS idref, p.rowid as fk_projet, DATE_FORMAT(p.dateo, '%Y-%m-%d') as date_start, 
					DATE_FORMAT(p.datee, '%Y-%m-%d') AS date_end, 
					pe.agenceconcerne as agence,te.antenne, pe.domaine,
					p.ref AS projet_ref, pe.projetstructurel as str
				FROM ".MAIN_DB_PREFIX."element_contact AS ec
				INNER JOIN ".MAIN_DB_PREFIX."c_type_contact AS tc ON ec.fk_c_type_contact = tc.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."user AS t ON ec.fk_socpeople = t.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as te ON te.fk_object = t.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON ec.element_id = p.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS pe ON pe.fk_object = p.rowid
				WHERE tc.element = 'project'
				AND tc.source = 'internal'
				AND tc.active = 1
				AND t.statut = 1
				AND tc.code = 'PROJECTCONTRIBUTOR'
				AND p.fk_statut = 1";
		$sql .= " AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)";
		// $sql .= " UNION";
		// $sql = "SELECT ec.fk_socpeople AS id, 'CO' AS idref, c.fk_projet, ec.element_id, c.ref, ce.date_start, 
		// 			DATE_FORMAT(c.date_livraison ,'%Y-%m-%d') AS date_end, c.fk_statut, ec.fk_c_type_contact, 
		// 			t.statut AS statuscontact, t.lastname, t.firstname, ce.pdc_etp_cde, ce.agence, tc.source, 
		// 			tc.element, tc.code, tc.libelle, p.ref AS projet_ref, pe.projetstructurel
		// 		FROM ".MAIN_DB_PREFIX."element_contact AS ec
		// 		INNER JOIN ".MAIN_DB_PREFIX."c_type_contact AS tc ON ec.fk_c_type_contact = tc.rowid
		// 		LEFT JOIN ".MAIN_DB_PREFIX."user AS t ON ec.fk_socpeople = t.rowid
		// 		LEFT JOIN ".MAIN_DB_PREFIX."commande AS c ON ec.element_id = c.rowid
		// 		LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields AS ce ON ce.fk_object = c.rowid
		// 		LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON p.rowid = c.fk_projet
		// 		LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS pe ON p.rowid = pe.fk_object
		// 		WHERE tc.element = 'commande' 
		// 		AND tc.source = 'internal' 
		// 		AND tc.active = 1 
		// 		AND tc.code = 'PdC'
		// 		AND t.statut = 1
		// 		AND p.fk_statut = 1
		// 		AND c.fk_statut <> 3";
								
		// $sql .= " AND (c.date_livraison >= '".$db->idate($now)."' OR c.date_livraison IS NULL)";
		// $sql .= " AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)";

				// AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)";

		// $sql .= " ORDER BY p.rowid";

	// 	$sql = "
    // (
    //     SELECT 
    //         ec.fk_socpeople AS id, 
    //         'PROJ' AS idref,
	// 		CASE WHEN tc.code = 'PROJECTCONTRIBUTOR' THEN
    //         p.rowid ELSE NULL END AS fk_projet, 
    //         DATE_FORMAT(p.dateo, '%Y-%m-%d') as date_start, 
    //         DATE_FORMAT(p.datee, '%Y-%m-%d') AS date_end, 
    //         p.rowid as element_id,
    //         pe.agenceconcerne as agence, 
    //         p.ref AS projet_ref, 
    //         pe.projetstructurel as str
    //     FROM ".MAIN_DB_PREFIX."element_contact AS ec
    //     INNER JOIN ".MAIN_DB_PREFIX."c_type_contact AS tc ON ec.fk_c_type_contact = tc.rowid
    //     LEFT JOIN ".MAIN_DB_PREFIX."user AS t ON ec.fk_socpeople = t.rowid
    //     LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON ec.element_id = p.rowid
    //     LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS pe ON pe.fk_object = p.rowid
    //     WHERE tc.element = 'project'
    //     AND tc.source = 'internal'
    //     AND tc.active = 1
    //     AND t.statut = 1
    //     AND tc.code = 'PROJECTCONTRIBUTOR'
    //     AND p.fk_statut = 1
    //     AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)
    // )
    // UNION
    // (
    //     SELECT 
    //         ec.fk_socpeople AS id, 
    //         'CO' AS idref,
    //         c.fk_projet AS fk_projet, 
    //         ce.date_start as date_start, 
    //         DATE_FORMAT(c.date_livraison, '%Y-%m-%d') AS date_end, 
    //         ec.element_id as element_id,
    //         null as agence, 
    //         null AS projet_ref, 
    //         null as str
    //     FROM ".MAIN_DB_PREFIX."element_contact AS ec
    //     INNER JOIN ".MAIN_DB_PREFIX."c_type_contact AS tc ON ec.fk_c_type_contact = tc.rowid
    //     LEFT JOIN ".MAIN_DB_PREFIX."user AS t ON ec.fk_socpeople = t.rowid
    //     LEFT JOIN ".MAIN_DB_PREFIX."commande AS c ON ec.element_id = c.rowid
	// 	LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields AS ce ON ce.fk_object = c.rowid
	// 	LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON c.fk_projet = p.rowid
    //     LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS pe ON pe.fk_object = p.rowid
    //     WHERE tc.element = 'commande'
    //     AND tc.source = 'internal'
    //     AND tc.active = 1
    //     AND t.statut = 1
    //     AND tc.code = 'PdC'
	// 	AND c.fk_projet IS NOT NULL
	// 	AND p.fk_statut = 1
    // 	AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)
    // )";


		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				// $projects[$obj->fk_projet] = $obj->fk_projet;
				// $orders[$obj->id] = $obj->element_id;
				// $users[$obj->fk_projet.'_'.$obj->id] = $obj->id;
				
				// $pdcEtpCde[$obj->fk_projet][$obj->ref] = $obj->pdc_etp_cde;
				$comms[$obj->fk_projet.'_'.$obj->id] = $obj;
			}
		} else {
			dol_print_error($db);
			return [];
		}
		return array_values(array_unique($comms, SORT_REGULAR));
	}


	/**
	 * 
	 */
	public function getContactsAndOrders($arr_user, $date_start, $date_end, $option, $optionStr)
	{
		global $db;
		$now = dol_now();

		// Récupération des données
		$listContactProject = $this->listProjectsContact($optionStr);
		$listProject = $this->fetchProjects($optionStr);
		$listOrder = $this->fetchOders();

		$comms = array();
		$projects = [];
		$orders = [];
		$users = [];
		$projarr = [];
	
		// Récupération des commandes et contacts
		$sql = "SELECT ec.fk_socpeople AS id, 'CO' AS idref, c.fk_projet, ec.element_id, c.ref, ce.date_start, 
					DATE_FORMAT(c.date_livraison ,'%Y-%m-%d') AS date_end, c.fk_statut, ec.fk_c_type_contact, 
					t.statut AS statuscontact, t.lastname, t.firstname, ce.pdc_etp_cde, ce.agence, tc.source, ce.domaine,
					tc.element, tc.code, tc.libelle, p.ref AS projet_ref, pe.projetstructurel, te.antenne
				FROM ".MAIN_DB_PREFIX."element_contact AS ec
				INNER JOIN ".MAIN_DB_PREFIX."c_type_contact AS tc ON ec.fk_c_type_contact = tc.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."user AS t ON ec.fk_socpeople = t.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as te ON te.fk_object = t.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."commande AS c ON ec.element_id = c.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields AS ce ON ce.fk_object = c.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON p.rowid = c.fk_projet
				LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS pe ON p.rowid = pe.fk_object
				WHERE tc.element = 'commande' 
				AND tc.source = 'internal' 
				AND tc.active = 1 
				AND tc.code = 'PdC'
				AND t.statut = 1
				AND p.fk_statut = 1
				AND c.fk_statut <> 3";
								
		$sql .= " AND (c.date_livraison >= '".$db->idate($now)."' OR c.date_livraison IS NULL)";
		$sql .= " AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)";


		if (!empty($arr_user)) {
			$sql .= " AND t.rowid IN (".implode(', ', $arr_user).")";
		}

		$sql .= " ORDER BY t.rowid DESC";

		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$projects[$obj->fk_projet] = $obj->fk_projet;
				$orders[$obj->id] = $obj->element_id;
				$users[$obj->fk_projet.'_'.$obj->id] = $obj->id;
				
				$pdcEtpCde[$obj->fk_projet][$obj->ref] = $obj->pdc_etp_cde;
				$comms[] = $obj;
			}
		} else {
			dol_print_error($db);
			return [];
		}

		// Option Project
		if ($option == 'project') {
		
			// foreach ($listProject as $value) {
			// 	// Commande liées aux projets
			// 	if (!isset($projects[$value->fk_projet])) {
			// 		$proj = new Project($db);
			// 		$proj->fetch($value->fk_projet);
			// 		$listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');

			// 		foreach ($listofproject as $userid) {
			// 			$projarr[$value->fk_projet] = true;
			// 			// if (isset($users[$value->fk_projet.'_'.$value->id])) {
			// 			//     $comms[] = (object) [
			// 			//         'id' => $userid, 'element_id' => $value->fk_projet, 'ref' => $value->ref,
			// 			//         'fk_projet' => $value->fk_projet, 'date_start' => $value->date_start, 'date_end' => $value->date_end,
			// 			//         'pdc_etp_proj' => count($listofproject), 'agence' => $value->agence, 'avl' => 'yes',
			// 			//         'projet_ref' => $value->ref, 'idref' => $value->idref
			// 			//     ];
			// 			// }else{
			// 				$comms[] = (object) [
			// 					'id' => $userid, 'element_id' => $value->fk_projet, 'ref' => $value->ref,
			// 						'fk_projet' => $value->fk_projet, 'date_start' => $value->date_start, 'date_end' => $value->date_end,
			// 						'pdc_etp_proj' => count($listofproject), 'agence' => $value->agence, 'avl' => 'yes',
			// 						'projet_ref' => $value->ref, 'idref' => $value->idref, 'str' => $value->projetstructurel
			// 				];
						
			// 		}

			// 		// Sans utilisateurs
			// 		if (!isset($projarr[$value->fk_projet])) {
			// 			$comms[] = (object) [
			// 				'element_id' => $value->fk_projet, 'ref' => $value->ref, 'fk_projet' => $value->fk_projet,
			// 				'date_start' => $value->date_start, 'date_end' => $value->date_end, 'pdc_etp_proj' => 0,
			// 				'agence' => $value->agence, 'avl' => 'yes', 'projet_ref' => $value->ref, 'idref' => $value->idref, 'str' => $value->projetstructurel
			// 			];
			// 		}
			// 	}
				
			// }

			foreach ($listProject as $value) {
				// if (!isset($projects[$value->fk_projet])) {
				if (!isset($users[$value->fk_projet.'_'.$value->id]) && !isset($projects[$value->fk_projet])) {
					$proj = new Project($db);
					$proj->fetch($value->fk_projet);
					$listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');
			
					if (!empty($listofproject)) {
						foreach ($listofproject as $userid) {
							$projarr[$value->fk_projet] = true;
							$comms[] = (object) [
								'id' => $userid,
								'element_id' => $value->fk_projet,
								'ref' => $value->ref,
								'fk_projet' => $value->fk_projet,
								'date_start' => $value->date_start,
								'date_end' => $value->date_end,
								'pdc_etp_proj' => count($listofproject),
								'agence' => $value->agence,
								'avl' => 'yes',
								'projet_ref' => $value->ref,
								'idref' => $value->idref,
								'str' => $value->projetstructurel,
								'antenne' => $value->antenne,
								'domaine' => $value->domaine
							];
						}
					} 
					else {
						// Aucun utilisateur trouvé, donc projet sans affectation
						$comms[] = (object) [
							'element_id' => $value->fk_projet,
							'ref' => $value->ref,
							'fk_projet' => $value->fk_projet,
							'date_start' => $value->date_start,
							'date_end' => $value->date_end,
							'pdc_etp_proj' => 0,
							'agence' => $value->agence,
							'avl' => 'yes',
							'projet_ref' => $value->ref,
							'idref' => $value->idref,
							'str' => $value->projetstructurel,
							'antenne' => $value->antenne,
							'domaine' => $value->domaine
						];
					}
				 }
			}
			

			foreach ($listContactProject as $value) {
				// && !isset($users[$value->fk_projet.'_'.$value->id]
				 if (!isset($users[$value->element_id.'_'.$value->id])) {
					$proj = new Project($db);
					$proj->fetch($value->element_id);
					$listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');

					foreach ($pdcEtpCde[$value->element_id] as $key => $val) { 
						$missingarr[$value->element_id][$key] = count($listofproject) - $pdcEtpCde[$value->element_id][$key]; 
					}
					
					$missing = implode(', ', array_map(
						fn($commande, $contactsPrevus, $projetId, $manque) => 
							(int)$contactsPrevus . " contact(s) prévus sur la commande $commande liée à ce projet, " . 
							((int)$manque < 0 ? "surplus de " . abs((int)$manque) : "écart de " . (int)$manque) . 
							" par rapport aux contacts affectés au projet",
						array_keys($pdcEtpCde[$value->element_id]),
						array_map('intval', array_values($pdcEtpCde[$value->element_id])),
						array_keys($missingarr[$value->element_id]),
						array_map('intval', array_values($missingarr[$value->element_id]))
					));
					

					$comms[] = (object) [
						'id' => $value->id, 'element_id' => $value->element_id, 'ref' => $value->ref,
						'fk_projet' => $value->element_id, 'date_start' => $value->date_start, 'date_end' => $value->date_end,
						'pdc_etp_proj' => count($listofproject), 'agence' => $value->agence, 'missing' => $missing,
						'projet_ref' => $value->ref, 'idref' => $value->idref, 'str' => $value->projetstructurel, 'antenne' => $value->antenne, 'domaine' => $value->domaine
					];
				}
			}
			
		}

		// Option Order
		if ($option == 'order') {
			foreach ($listOrder as $value) {
				if (!isset($projects[$value->fk_projet])) {
					$proj = new Project($db);
					$proj->fetch($value->fk_projet);
					$listofproject = $proj->liste_contact(-1, 'internal', 1, 'PROJECTCONTRIBUTOR');

					$comms[] = (object) [
						'id' => null, 'element_id' => $value->element_id, 'projet_ref' => $value->projet_ref,
						'fk_projet' => $value->fk_projet, 'date_start' => $value->date_start, 'date_end' => $value->date_end,
						'pdc_etp_cde' => 0, 'pdc_etp_proj' => count($listofproject), 'agence' => $value->agence,
						'avl' => 'yes', 'idref' => $value->idref, 'ref' => $value->ref, 'antenne' => $value->antenne, 'domaine' => $value->domaine
					];
				}
			}
		}

		// return $comms;
		return array_values(array_unique($comms, SORT_REGULAR));
	}

		/**
	 * Récupère les utilisateurs en formation en cours
	 *
	 * @return array Liste des objets utilisateurs en formation
	 */
	public function getUsersInCurrentTraining()
	{
		global $langs, $db;
		$result = array();
		$now = dol_now();
		$sql = "SELECT t.rowid as element_id_abs, 
		tf.ref as holidayref, 
		t.fk_formation as fk_type,
		u.rowid as id, 
		GROUP_CONCAT(DISTINCT CASE 
			WHEN (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL) 
				AND p.fk_statut < 2 
			THEN p.ref 
			ELSE NULL 
		END ORDER BY p.ref SEPARATOR ', ') AS projets,

		GROUP_CONCAT(DISTINCT CASE 
			WHEN (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL) 
				AND p.fk_statut < 2 
			THEN p.rowid 
			ELSE NULL 
		END ORDER BY p.rowid SEPARATOR ', ') AS fk_projets,

		GROUP_CONCAT(DISTINCT CASE 
			WHEN (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL) 
				AND p.fk_statut < 2 
			THEN pe.domaine 
			ELSE NULL 
		END ORDER BY pe.domaine SEPARATOR ', ') AS domaines,

		GROUP_CONCAT(DISTINCT c.rowid ORDER BY c.rowid SEPARATOR ', ') AS fk_orders,
		GROUP_CONCAT(DISTINCT pr.rowid ORDER BY pr.rowid SEPARATOR ', ') AS fk_propals,
		GROUP_CONCAT(DISTINCT pe.agenceconcerne ORDER BY pe.agenceconcerne SEPARATOR ', ') AS agences,
		u.lastname as user_lastname, 
		u.firstname as user_firstname, 
		ue.antenne,
		tf.label as conge_label,
		p.rowid as fk_projet, 
		p.ref as projet_ref,
		t.fk_user, 
		'FH' as idref,
		CONCAT(DATE_FORMAT(t.date_debut_formation, '%Y-%m-%d'), ' 00:00') AS date_start,
		CONCAT(DATE_FORMAT(t.date_fin_formation, '%Y-%m-%d'), ' 23:59') AS date_end,
		t.fk_formation";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_userformation as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = t.fk_user";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_formation as tf on t.fk_formation = tf.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as ue ON ue.fk_object = u.rowid";		
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec ON ec.fk_socpeople = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON ec.fk_c_type_contact = tc.rowid"; 
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON ec.element_id = p.rowid"; 
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON pe.fk_object = p.rowid"; 
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON c.fk_projet = ec.element_id";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as pr ON pr.fk_projet = ec.element_id";
		$sql .= " WHERE u.statut = 1"; 
		$sql .= " AND ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'project'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PROJECTCONTRIBUTOR'";
		$sql .= " AND p.fk_statut = 1";
		// $sql .= " AND t.date_fin_formation IS NOT NULL AND t.date_fin_formation >= '".dol_print_date($now, '%Y-%m-%d')."'";
		$sql .= " AND t.date_fin_formation BETWEEN '".dol_print_date($now, '%Y-%m-%d')."' AND '2035-12-31'";
		$sql .= " GROUP BY t.rowid";
		$sql .= " ORDER BY u.rowid DESC";
		

		dol_syslog(get_class($this)."::getUsersInCurrentTraining", LOG_DEBUG);
		 $resql = $db->query($sql);

		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$result[] = $obj;
			}
			$db->free($resql);
		} else {
			dol_syslog(__METHOD__." SQL error: ".$db->lasterror(), LOG_ERR);
		}

		return $result;
	}



	/**
	 * get all projects still at work
	 */
	public function fetchProjects($option = null)
	{
		global $conf, $db, $langs, $user;
		$now = dol_now();

		$sql = "SELECT p.rowid as fk_projet,  'PROJ' as idref, DATE_FORMAT(p.dateo,'%Y-%m-%d') as date_start, DATE_FORMAT(p.datee ,'%Y-%m-%d') as date_end, pe.agenceconcerne as agence, p.ref, pe.projetstructurel";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
		$sql .= " WHERE p.datee >= '".$this->db->idate($now)."'";
		

		$sql .= " WHERE p.fk_statut = 1";
		// $sql .= " WHERE 1 = 1";
		if($option == 'ProjetStructure') {
			$sql .= " AND 1 = 1";
		}else{
			$sql .= " AND (pe.projetstructurel IS NULL)";
		}
		
		$sql .= " AND (p.datee >= '".$this->db->idate($now)."' OR p.datee IS NULL)";
		// $sql .= " OR p.datee IS NULL";

		dol_syslog(get_class($this)."::fetchProjects", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$projects[] = $obj;
				$i++;
			} 
		
			return $projects;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get all orders still at work
	 */
	public function fetchOders()
	{
		global $conf, $db, $langs, $user;
		$now = dol_now();
		$projects = [];

		$sql = "SELECT c.rowid as element_id, 'CO' as idref, c.fk_projet, p.ref as projet_ref, ce.date_start as date_start, DATE_FORMAT(c.date_livraison ,'%Y-%m-%d') as date_end, ce.agence as agence, c.ref,ce.domaine";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ce on c.rowid = ce.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = c.fk_projet";
		$sql .= " WHERE c.date_livraison >= '".$this->db->idate($now)."'";
		$sql .= " AND p.datee >= '".$this->db->idate($now)."'";
		// $sql .= " ORDER BY p.rowid ASC";

		dol_syslog(get_class($this)."::fetchOders", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$orders[] = $obj;
				$i++;
			} 
		
			return $orders;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * 
	 * @return array of ids responsable de suivi commande
	 * 
	 */
	public function getRespOrders()
	{
		global $db;
		$error = 0;
	
		$comms = array();
		$sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, c.fk_projet, ec.element_id, c.ref, ce.date_start as date_start, DATE_FORMAT(c.date_livraison ,'%Y-%m-%d') as date_end, c.fk_statut,";
		$sql .= " ec.fk_c_type_contact, t.statut as statuscontact,";
		$sql .= " t.lastname, t.firstname,ce.pdc_etp_cde,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c on ec.element_id = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ce on ce.fk_object = c.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'commande'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'SALESREPFOLL'";
		$sql .= " AND t.statut = '1'";
		$sql .= " AND c.fk_statut <> '3'";
		
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
				
					$respcomms[$obj->element_id.'_'.$obj->ref	] = $obj->id;
				}
				$i++;
			}
			$db->free($resql);
			return $respcomms;
		} else {
			dol_print_error($db);
		}
	}
	
	/**
	 * 
	 * @return array of ids responsable de suivi commande
	 * 
	 */
	public function getRespPropals()
	{
		global $db;
		$error = 0;
	
		$comms = array();
		$sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, c.fk_projet, ec.element_id, c.ref, c.fk_statut,";
		$sql .= " ec.fk_c_type_contact, t.statut as statuscontact,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as c on ec.element_id = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields as ce on ce.fk_object = c.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'propal'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'SALESREPFOLL'";
		$sql .= " AND t.statut = '1'";
		$sql .= " AND c.fk_statut <> '3'";
		
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
				
					$respdevis[$obj->element_id.'_'.$obj->ref] = $obj->id;
				}
				$i++;
			}
			$db->free($resql);
			return $respdevis;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * 
	 * @return array of ids responsable de suivi projets
	 * 
	 */
	public function getRespProjects()
	{
		global $db;
		$error = 0;
	
		$sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, ec.element_id, p.ref, DATE_FORMAT(p.dateo ,'%Y-%m-%d') as date_start, DATE_FORMAT(p.datee ,'%Y-%m-%d') as date_end,";
		$sql .= " t.lastname, t.firstname, t.rowid as userid,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on ec.element_id = p.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'project'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PROJECTLEADER'";
		$sql .= " AND t.statut = '1'";
		// $sql .= " AND p.fk_statut = 1";
		
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$respproj[$obj->element_id.'_'.$obj->ref] = $obj->id;
				}
				$i++;
			}

		  
			
			$db->free($resql);
	
			return $respproj;
		} else {
			dol_print_error($db);
		}
	}


	/**
	 * @param array of users
	 * @param date start
	 * @param date end
	 * 
	 * @return array of object propals
	 * 
	 */
	public function getContactsAndPropals($arr_user, $date_start, $date_end)
	{
		global $db;
		$error = 0;
		$now = dol_now();
		$propals = array();
		$sql = "SELECT ec.rowid, p.rowid as propalid, 'PR' as idref, ec.statut as statuslink, ec.fk_socpeople as id, p.fk_projet, ec.element_id, p.ref, pe.datedmarrage as date_start, DATE_FORMAT(p.date_livraison ,'%Y-%m-%d') as date_end,";
		$sql .= " ec.fk_c_type_contact, t.statut as statuscontact,proj.ref as projet_ref,";
		$sql .= " t.lastname, t.firstname,pe.pdc_etp_devis,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p on ec.element_id = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields as pe on pe.fk_object = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as proj on proj.rowid = p.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as projextr on proj.rowid = projextr.fk_object";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'propal'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PdC'";
		$sql .= " AND t.statut = '1'";
		$sql .= " AND p.fk_statut IN ('1','2')";
		$sql .= " AND (projextr.projetstructurel <> 1 OR projextr.projetstructurel IS NULL OR projextr.projetstructurel = 0)";
		if(!empty($arr_user)) {
			$sql .= " AND t.rowid IN (".implode(', ', $arr_user).")";
		}
		$sql .= " AND (p.date_livraison >= '".$db->idate($now)."' OR p.date_livraison IS NULL)";
		$sql .= " AND (proj.datee >= '".$db->idate($now)."' OR proj.datee IS NULL)";

		// $sql .= " AND DATE_FORMAT(pe.datedmarrage ,'%Y-%m-%d') >='".dol_print_date($date_start, '%Y-%m-%d')."'";
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$srcObject = new Propal($db);
					$srcObject->fetch($obj->propalid);
					$srcObject->fetchObjectLinked();
			
					if(empty($srcObject->linkedObjectsIds['commande'])) {
						$propals[] = $obj;
					}
				}
				$i++;
			}
			$db->free($resql);
			return $propals;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * @param array of users
	 * @param date start
	 * @param date end
	 * 
	 * @return array of object projects
	 * 
	 */
	public function getContactsAndProject($arr_user, $date_start, $date_end)
	{
		global $db;
		$now = dol_now();
		$error = 0;
		$projects = array();
		$sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, ec.element_id, p.ref, DATE_FORMAT(p.dateo ,'%Y-%m-%d') as date_start, DATE_FORMAT(p.datee ,'%Y-%m-%d') as date_end,";
		$sql .= " ec.fk_c_type_contact, t.statut as statuscontact,";
		$sql .= " t.lastname, t.firstname, c.rowid as commandeid, c.ref as commanderef,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on ec.element_id = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on pe.fk_object = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande  as c on c.fk_projet = p.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'project'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PROJECTCONTRIBUTOR'";
		$sql .= " AND t.statut = '1'";
		$sql .= " AND p.fk_statut > 2";
		$sql .= " AND (pe.projetstructurel <> 1 OR pe.projetstructurel IS NULL OR pe.projetstructurel = 0)";
		if(!empty($arr_user)) {
			$sql .= " AND t.rowid IN (".implode(', ', $arr_user).")";
		}
		$sql .= " AND (c.date_livraison >= '".$db->idate($now)."' OR c.date_livraison IS NULL)";
		$sql .= " AND (p.datee >= '".$db->idate($now)."' OR p.datee IS NULL)";
		// $sql .= " AND DATE_FORMAT(p.dateo ,'%Y-%m-%d') >='".dol_print_date($date_start, '%Y-%m-%d')."'";
		// $sql .= " AND DATE_FORMAT(p.datee ,'%Y-%m-%d') <='".dol_print_date($date_end, '%Y-%m-%d')."'";
		// $sql .= " AND '".dol_print_date($date_start, '%Y-%m-%d')."' <= DATE_FORMAT(p.dateo ,'%Y-%m-%d')";
		// $sql .= " AND '".dol_print_date($date_start, '%Y-%m-%d')."' <= DATE_FORMAT(p.datee ,'%Y-%m-%d')";
		// $sql .= " AND '".dol_print_date($date_end, '%Y-%m-%d')."' >= DATE_FORMAT(p.dateo ,'%Y-%m-%d')";
		// $sql .= " AND '".dol_print_date($date_end, '%Y-%m-%d')."' >= DATE_FORMAT(p.datee ,'%Y-%m-%d')";
		// $sql .= " AND DATE_FORMAT(p.datee ,'%Y-%m-%d') >='".dol_print_date($date_start, '%Y-%m-%d')."'";
		$sql .= " ORDER BY t.lastname ASC";
	
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$projects[] = $obj;
				}
				$i++;
			}
			$db->free($resql);
	
			return $projects;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * 
	 * @return array of object projects
	 * 
	 */
	public function listProjectsContact($option = null)
	{
		global $db;
		$error = 0;
		$now = dol_now();
		$projects = array();
		$sql = "SELECT ec.rowid, ec.statut as statuslink, 'PROJ' as idref, ec.fk_socpeople as id, ec.element_id, p.ref, DATE_FORMAT(p.dateo ,'%Y-%m-%d') as date_start, DATE_FORMAT(p.datee ,'%Y-%m-%d') as date_end,";
		$sql .= " ec.fk_c_type_contact, t.statut as statuscontact,p.ref,";
		$sql .= " t.lastname, t.firstname, c.rowid as commandeid, c.ref as commanderef, pe.agenceconcerne as agence, te.antenne, pe.domaine,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle, pe.projetstructurel";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as te ON te.fk_object = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on ec.element_id = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on pe.fk_object = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c on c.fk_projet = p.rowid";
		// $sql .= " WHERE ec.element_id = 422";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'project'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PROJECTCONTRIBUTOR'";
		// $sql .= " AND t.statut = '1'";
		$sql .= " AND p.fk_statut = 1";
		// $sql .= " AND pe.projetstructurel <> 1";
		if($option == 'ProjetStructure') {
			$sql .= " AND 1 = 1";
		}else{
			$sql .= " AND pe.projetstructurel IS NULL";
		}
		// $sql .= " AND p.datee >= '".$this->db->idate($now)."'";
		$sql .= " AND (p.datee >= '".$this->db->idate($now)."' OR p.datee IS NULL)";

		
		// if(!empty($arr_user)) {
		// 	$sql .= " AND t.rowid IN (".implode(', ', $arr_user).")";
		// }
		// $sql .= " AND DATE_FORMAT(p.dateo ,'%Y-%m-%d') >='".dol_print_date($date_start, '%Y-%m-%d')."'";
		// $sql .= " AND DATE_FORMAT(p.datee ,'%Y-%m-%d') <='".dol_print_date($date_end, '%Y-%m-%d')."'";
		// $sql .= " AND '".dol_print_date($date_start, '%Y-%m-%d')."' <= DATE_FORMAT(p.dateo ,'%Y-%m-%d')";
		// $sql .= " AND '".dol_print_date($date_start, '%Y-%m-%d')."' <= DATE_FORMAT(p.datee ,'%Y-%m-%d')";
		// $sql .= " AND '".dol_print_date($date_end, '%Y-%m-%d')."' >= DATE_FORMAT(p.dateo ,'%Y-%m-%d')";
		// $sql .= " AND '".dol_print_date($date_end, '%Y-%m-%d')."' >= DATE_FORMAT(p.datee ,'%Y-%m-%d')";
		// $sql .= " AND DATE_FORMAT(p.datee ,'%Y-%m-%d') >='".dol_print_date($date_start, '%Y-%m-%d')."'";
		$sql .= " ORDER BY t.lastname ASC";
	
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					// if($obj->fk_projet !== $obj->element_id) {
						// $projects[$obj->id.'_'.$obj->element_id] = $obj;
						$projects[] = $obj;
					// }
					
				}
				$i++;	
			}
			$db->free($resql);
	
			return $projects;
		} else {
			dol_print_error($db);
		}
	}


	// FROM llx_hrm_job_user as t LEFT JOIN llx_user as u on t.fk_user = u.rowid, llx_hrm_job as j WHERE 1 = 1 AND t.fk_job = j.rowid
	/**
	 * 
	 */
	public function getSkillsAndUsers()
	{
		global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT t.rowid,t.fk_contrat,t.fk_user,t.fk_job,t.description, DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,";
		$sql .= " u.lastname,u.firstname,";
		$sql .= " j.rowid as job_id, j.label as job_label";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on t.fk_user = u.rowid";
		$sql .= ", ".MAIN_DB_PREFIX."hrm_job as j WHERE 1 = 1 AND t.fk_job = j.rowid";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$jobskills[] = $obj;
				}
				$i++;
			}
			$db->free($resql);
	
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	public function getSkills()
	{
		global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT u.rowid as userid,t.label,t.rowid as skillid, js.fk_user as fk_user";
		// $sql .= "DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,";
		// $sql .= " ef.orientation as options_orientation, ef.catgorie as options_catgorie, ef.piatdpn as options_piatdpn";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_skill as t";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill_extrafields as ef on t.rowid = ef.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skillrank as hr on t.rowid = hr.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job_user as js on js.fk_job = hr.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on js.fk_user = u.rowid";
		$sql .= " WHERE 1 = 1";
		$sql .= " AND hr.objecttype = 'job'";
		$sql .= " ORDER BY t.rowid ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$skills[] = $obj;
				}
				$i++;
			}
			$db->free($resql);
	
			return $skills;
		} else {
			dol_print_error($db);
		}
	}

	


	/**
	 * get the antenna managers and their subordinates 
	 */
	public function getManager()
	{
		global $db;
		$sql = 'SELECT u.rowid, u.lastname, u.firstname,';
		$sql .= ' u.job, u.fk_user';
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.statut = 1";
		
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if($obj->fk_user == null) {
					$data[$obj->rowid]= 'dg';
				}
				$res[$obj->rowid] = $obj->fk_user;
				$i++;
			}
		
			foreach($data as $key => $value) {
				array_key_exists($key, array_keys($res)) ? $pdg[$key] = $value : null; 
			}
			
			foreach ($res as $key => $val){
				$employees[$val][] = $key;
			}

			foreach($employees as $key => $value) {
				foreach($value as $employee) {
					// array_key_exists($employee, $employees) ? $submanagers[$employee] = 'submanager' : null;
					array_key_exists($key, $pdg) ? $managers[$employee] = null : null; 
				}
			}
			
			foreach($employees as $key => $val) {
				foreach($val as $value) {
					array_key_exists($key, $pdg) ? $inPdg[$key][] = $value : null; 
					array_key_exists($key, $managers) ? $inManager[$key][] = $value : null; 
					$key !== '' && array_key_exists($key, $managers) == false && array_key_exists($key, $pdg) == false ? $inSubmanager[$key][] = $value : null; 
				}
				
			}

			$arr = $this->subManager($inManager, $inSubmanager);
			foreach($arr as $key => $flatten) {
				$ids[$key] = $this->array_flatten($flatten);
			}
		
			foreach($inPdg as $key => $value) {
				$ids[$key] = $value;
			}

			$db->free($result);

			return $ids;
		} else {
			dol_print_error($db);
		}
		
	}

	/**
	 * convert array to one dimension
	 */
	function array_flatten($array) { 
		if (!is_array($array)) { 
		  return FALSE; 
		} 
	
		$result = array(); 
		foreach ($array as $key => $value) { 
		  if (is_array($value)) { 
			
			$result = array_merge($result, $this->array_flatten($value)); 
			
		  } 
		  else { 
			$result[$key] = $value; 
		  } 
		} 
		return $result; 
	  } 

	/**
	 * get suboridnates 
	 */
	function subManager($inManager, $inSubmanager)
	{
		foreach(array_filter($inManager) as $key1 => $val1) {
			foreach($val1 as $value) {
				foreach(array_filter($inSubmanager) as $key2 => $val2) {
					$inSubmanager2[$key2] = array_filter($val2);
					
					foreach(array_filter($inSubmanager2) as $key3 => $val3) {
						$inSubmanager3[$key2] = array_filter($val3);
						foreach(array_filter($inSubmanager3) as $key4 => $val4) {
						array_key_exists($value, $inSubmanager) ? $inManager[$key1][$key2] = in_array($key2, $val1) ? array_filter($val2) : null : null;
						array_key_exists($value, $inSubmanager2) ? $inSubmanager[$key2][$key3] = in_array($key3, $val2) ? array_filter($val3) : null : null;
						array_key_exists($value, $inSubmanager3) ? $inSubmanager2[$key3][$key4] = in_array($key4, $val3) ? array_filter($val4) : null : null;
						}
					}
				}
			}
		}
		return array_filter($inManager);
	}


	/**
	 * Return a HTML table that contains a pie chart of etp (commande and propals)
	 * @param	date		$year_star		(Optional) Show only results from the corrected tests with this start date
	 * @param	date		$year_end		(Optional) Show only results from the corrected tests with this end date
	 * @return	string				A HTML table that contains a pie chart of etp (commande and propals)
	 */
	public function getEtpPieChart($date_start, $date_end)
	{
		global $conf, $db, $langs, $user;

		if ($date_start > $date_end) {
			return -1;
		}
		
		$result= '';

		$sql = "SELECT sum(g.etp_commande), sum(g.expect_etp_commande), sum(g.etp_propal), sum(g.expect_etp_propal), DATE_FORMAT(g.year, '%Y-%m'), g.fk_commande";
		$sql .= " FROM ".MAIN_DB_PREFIX."comm_element_graph AS g";
		$sql .= " WHERE g.year >= '".$this->db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
		$sql .= " AND g.year <= '".$this->db->idate($date_end)."'";
		$sql .= " GROUP BY DATE_FORMAT(g.year, '%Y-%m')";
		// $sql .= " GROUP BY g.rowid";
		$sql .= " ORDER BY DATE_FORMAT(g.year, '%Y-%m')";
	
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$obj = $db->fetch_object($resql);
			
			$dataseries = array();
			$colorseries = array();
			$listofcomm = array();
			$vals = array();
	
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				if ($row) {
					$key = $row[4];
					$vals['etp_commande'] = $row[0] > 0 ? $row[0] : null;
					$vals['expect_etp_commande'] = $row[1] > 0 ? $row[1] : null;
					$vals['etp_propal'] = $row[2] > 0 ? $row[2] : null;
					$vals['expect_etp_propal'] = $row[3] > 0 ? $row[3] : null;
		
					$listofcomm[$key] = $vals;
				}
				$i++;
			}
			// var_dump($listofcomm);
			foreach($listofcomm as $key => $value) {
				$dataseries[] = array($key, $value['etp_commande'], $value['expect_etp_commande'], $value['etp_propal'], $value['expect_etp_propal']);
			}

			$db->free($resql);
			include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';		
				
				// $result = '<div class="div-table-responsive-no-min">';
				// $result .= '<table class="noborder nohover centpercent">';
				$result .=  '<tr class="liste_titre">';
				// $result .=  '<td colspan="4">'.$langs->trans("Statistics").' - '.$langs->trans("Etp").'</td>';
				$result .=  '</tr>';

				if ($conf->use_javascript_ajax) {
					$labels = array('Cde. affectée', 'Cde. prévue', 'Dev. affecté', 'Dev. prévu');
					$colorseries = array('#25a580', '#bb1511','#0005FF', '#04D0D4');
					$result .=  '<tr>';
					$result .=  '<td align="center" colspan="2">';
					include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$mesg = $dolgraph->isGraphKo();
					
					if (!$mesg) {
						
						$dolgraph->SetData(array_values($dataseries));
						$dolgraph->SetDataColor($colorseries);
						$dolgraph->setShowLegend(1);
						$dolgraph->SetType(array('linesnopoint', 'linesnopoint','linesnopoint', 'linesnopoint'));
						$dolgraph->setHeight('400');
						$dolgraph->setWidth('1100');
						$dolgraph->setLegend(array_values($labels));
						// $dolgraph->setTitle('etp');
						$dolgraph->draw('infographetp');
						$result .=  $dolgraph->show();
						$result .=  '</td>';
						$result .=  '</tr>';
					}
				}	
					
				 $result .=  '<tr class="liste_total">';
				$result .=  '<td></td>';
				$result .=  '<td class="right"></td>';
				$result .=  '</tr>';

				// $result .=  '</table>';
			
		}else {
			dol_print_error($db);
		}	

		return $result;   
	}

	/**
	 * 
	 */
	public function listGroups()
   {
     global $conf, $user, $db;
  
     $ret = array();
  
     $sql = "SELECT g.rowid, g.nom";
     $sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
	$sql .= " WHERE 1 = 1";
	$sql .= " ORDER BY g.nom";

     dol_syslog(get_class($this)."::listGroups", LOG_DEBUG);
     $result = $db->query($sql);
     if ($result) {
       while ($obj = $db->fetch_object($result)) {
        $ret[$obj->rowid] = $obj->nom;
       }
  
       $db->free($result);
  
       return $ret;
     } else {
       return -1;
     }
   }

   public function getGroups()
   {
		global $conf, $user, $db;
	
		$ret = array();
	
		$sql = "SELECT g.rowid as group_id, g.nom, gu.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as gu ON gu.fk_usergroup = g.rowid";
		// $sql .= " WHERE 1 = 1";
		// $sql .= " ORDER BY g.nom";

		dol_syslog(get_class($this)."::getGroups", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
		while ($obj = $db->fetch_object($result)) {
			$ret[] = $obj;
		}
	
		$db->free($result);
	
		return $ret;
		} else {
		return -1;
		}
   }

   	public function getProjects()
	{
		global $conf, $db, $langs, $user;
		$now = dol_now();

		$sql = "SELECT p.rowid as fk_projet, CONCAT(p.ref, ', ',p.title) as nom, pe.domaine";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
		// $sql .= " WHERE p.datee >= '".$this->db->idate($now)."'";
		$sql .= " WHERE (p.datee >= '".$this->db->idate($now)."' OR p.datee IS NULL)";
		$sql .= " AND p.fk_statut < 2";
		$sql .= " ORDER BY p.rowid ASC";

		dol_syslog(get_class($this)."::getProjects", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$projects[] = $obj;
				$i++;
			} 
			return $projects;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get list of propal
	 */
	public function getPropals()
	{
		global $db, $user;
		$now = dol_now();

		$sql = "SELECT p.rowid as propal_id, p.ref, CONCAT(p.ref,', ', p.ref_client) as nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as proj ON proj.rowid = p.fk_projet";
		$sql .= " WHERE p.date_livraison >= '".$db->idate($now)."'";
		$sql .= " AND proj.datee >= '".$db->idate($now)."'";
	
		dol_syslog(get_class($this)."::getPropals", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$propals[] = $obj;
	
				$i++;
			} 

			return $propals;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	  /**
	 * Get agences.
	 * 
	 */
	public function getAgencies()
	{
		global $conf, $db, $langs, $user;
		
		$name = 'OPTIM Industries';
		$sql = "SELECT DISTINCT u.rowid as fk_user, s.rowid as socid, sc.fk_user,s.nom as name, s.name_alias,  CONCAT(u.firstname, ' ', u.lastname) AS fullname";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= ", ".MAIN_DB_PREFIX."user as u";
		$sql .= " , ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE u.entity in (0, 1) AND u.rowid = sc.fk_user";
		$sql .= " AND s.rowid = sc.fk_soc";
		// $sql .= " AND s.nom = '".$db->escape($name)."'";
		$sql .= " AND s.nom LIKE '%" . $db->escape($name) . "%'";
		

		dol_syslog(get_class($this)."::getAgencies", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
	
				$agences[$obj->socid] = $obj;
				
			
				$i++;
			} 

			return $agences;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get orders
	 */
	public function getOrders()
	{
		global $conf, $db, $langs, $user;
		$now = dol_now();

		$sql = "SELECT c.rowid as order_id, CONCAT(c.ref,', ',c.ref_client) as nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
		$sql .= " WHERE c.date_livraison >= '".$this->db->idate($now)."'";
		// $sql .= " ORDER BY p.rowid ASC";

		dol_syslog(get_class($this)."::getOrders", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$orders[] = $obj;
				$i++;
			} 
		
			return $orders;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}


   /**
	 * 
	 * @return array of ids responsable de suivi projets
	 * 
	 */
	public function getRespOfProjects()
	{
		global $db;
		$error = 0;
	
		$sql = "SELECT";
		$sql .= " t.lastname, t.firstname, CONCAT(t.firstname, ' ', t.lastname) as fullname, t.rowid as id, p.rowid as id_project";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on ec.element_id = p.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'project'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PROJECTLEADER'";
		$sql .= " AND t.statut = '1'";
		// $sql .= " AND p.fk_statut = 1";
		
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$respproj[] = $obj;
				}
				$i++;
			}

		  
			
			$db->free($resql);
	
			return $respproj;
		} else {
			dol_print_error($db);
		}
	}

   /**
	 * Get agences.
	 * 
	 */
	public function getAgences()
	{
		global $conf, $db, $langs, $user;
		$name = 'OPTIM Industries';
		$sql = "SELECT DISTINCT u.rowid as userid, s.rowid as socid, sc.fk_user, u.lastname, u.firstname, u.email, u.statut as status, u.entity, s.nom as name, s.name_alias";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= ", ".MAIN_DB_PREFIX."user as u";
		$sql .= " , ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE u.entity in (0, 1) AND u.rowid = sc.fk_user";
		$sql .= " AND s.rowid = sc.fk_soc";
		$sql .= " AND s.nom = '".$db->escape($name)."'";
		// $sql .= " AND u.rowid =".((int) $user->id);
	

		// $sql .= " ORDER BY u.lastname ASC, u.firstname ASC";
	

		dol_syslog(get_class($this)."::getAgences", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
	
				$agences[$obj->socid] = $obj->name_alias;
				
			
				$i++;
			} 

			return $agences;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get list of projects
	 */
	public function select_multi_projects()
	{
		global $db, $user;

		$sql = "SELECT p.rowid, p.ref, p.title as nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
	
		dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				
				$projects[$obj->rowid] = trim($obj->ref).', '.trim($obj->nom);
	
				$i++;
			} 

			return $projects;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get list of orders
	 */
	public function select_multi_orders()
	{
		global $db, $user;

		$sql = "SELECT c.rowid, c.ref, c.ref_client as nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	
		dol_syslog(get_class($this)."::select_multi_orders", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$commandes[$obj->rowid] = trim($obj->ref).', '.trim($obj->nom);
	
				$i++;
			} 

			return $commandes;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get list of propal
	 */
	public function select_multi_propals()
	{
		global $db, $user;

		$sql = "SELECT p.rowid, p.ref, p.ref_client as nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
	
		dol_syslog(get_class($this)."::select_multi_propals", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$propals[$obj->rowid] = trim($obj->ref).', '.trim($obj->nom);
	
				$i++;
			} 

			return $propals;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get list of resp project
	 * 
	 * @return array 
	 * 
	 */
	public function getRespOfProject()
	{
		global $db;
		$error = 0;
		$projects = array();
		$sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, ec.element_id, p.ref, DATE_FORMAT(p.dateo ,'%Y-%m-%d') as date_start, DATE_FORMAT(p.datee ,'%Y-%m-%d') as date_end,";
		$sql .= " t.lastname, t.firstname, t.rowid as userid,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on ec.element_id = p.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'project'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PROJECTLEADER'";
		$sql .= " AND t.statut = '1'";
		$sql .= " AND p.fk_statut = 1";
		
		$sql .= " ORDER BY t.lastname ASC";
	
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$respids[$obj->userid] = trim($obj->firstname).' '.trim($obj->lastname);
				}
				$i++;
			}
			$db->free($resql);
	
			return $respids;
		} else {
			dol_print_error($db);
		}
	}

	
	public function getAbsentUsers()
	{
		global $db;
		$now = dol_now();
		$listabs = []; 
		$today = dol_print_date(dol_now(), '%Y-%m-%d');

		$sql = "SELECT cp.rowid as element_id_abs, 
					cp.ref as holidayref, 
					'HL' as idref, 
					uu.rowid as id, 
					cp.fk_type,
					hu.nb_holiday as nb_open_day, 
					-- GROUP_CONCAT(DISTINCT p.ref ORDER BY p.ref SEPARATOR ', ') AS projets, 
					-- GROUP_CONCAT(DISTINCT p.rowid ORDER BY p.rowid SEPARATOR ', ') AS fk_projets,
				 	GROUP_CONCAT(DISTINCT CASE 
					WHEN (p.datee >= '".$this->db->idate($now)."' OR p.datee IS NULL) 
						AND p.fk_statut < 2 
					THEN p.ref 
					ELSE NULL 
				END ORDER BY p.ref SEPARATOR ', ') AS projets,

				GROUP_CONCAT(DISTINCT CASE 
					WHEN (p.datee >= '".$this->db->idate($now)."' OR p.datee IS NULL) 
						AND p.fk_statut < 2 
					THEN p.rowid 
					ELSE NULL 
				END ORDER BY p.rowid SEPARATOR ', ') AS fk_projets,

				GROUP_CONCAT(DISTINCT CASE 
					WHEN (p.datee >= '".$this->db->idate($now)."' OR p.datee IS NULL) 
						AND p.fk_statut < 2 
					THEN pe.domaine 
					ELSE NULL 
				END ORDER BY pe.domaine SEPARATOR ', ') AS domaines,

					GROUP_CONCAT(DISTINCT c.rowid ORDER BY c.rowid SEPARATOR ', ') AS fk_orders,
					GROUP_CONCAT(DISTINCT pr.rowid ORDER BY pr.rowid SEPARATOR ', ') AS fk_propals,
					GROUP_CONCAT(DISTINCT pe.agenceconcerne ORDER BY pe.agenceconcerne SEPARATOR ', ') AS agences,
					CASE 
					WHEN cp.halfday = 0 
						THEN CONCAT(DATE_FORMAT(cp.date_debut, '%Y-%m-%d'), ' 00:00') 
					ELSE 
						CONCAT(DATE_FORMAT(cp.date_debut, '%Y-%m-%d'), ' 12:30') 
					END as date_start, 

					CASE 
					WHEN cp.halfday = 0 
						THEN CONCAT(DATE_FORMAT(cp.date_fin, '%Y-%m-%d'), ' 23:59') 
					ELSE 
						CONCAT(DATE_FORMAT(cp.date_fin, '%Y-%m-%d'), ' 18:15') 
					END as date_end,
					cp.halfday, 
					cp.statut as status, 
					DATE_FORMAT(cp.date_valid, '%Y-%m-%d') as date_valid, 
					DATE_FORMAT(cp.date_refuse, '%Y-%m-%d') as date_refuse, 
					uu.lastname as user_lastname, 
					uu.firstname as user_firstname, uue.antenne,
					ef.remplacement as options_remplacement, ht.label as conge_label,
					ef.statutfdt as options_statutfdt, p.rowid as fk_projet, p.ref as projet_ref 
				FROM ".MAIN_DB_PREFIX."holiday as cp 
				LEFT JOIN ".MAIN_DB_PREFIX."holiday_extrafields as ef ON cp.rowid = ef.fk_object 
				LEFT JOIN ".MAIN_DB_PREFIX."c_holiday_types as ht ON cp.fk_type = ht.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."user as uu ON uu.rowid = cp.fk_user 
				LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as uue ON uue.fk_object = uu.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."holiday_users as hu ON hu.fk_user = cp.fk_user AND hu.fk_type = cp.fk_type
				LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec ON ec.fk_socpeople = uu.rowid 
				LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON ec.fk_c_type_contact = tc.rowid 
				LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON ec.element_id = p.rowid 
				LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON pe.fk_object = p.rowid 
				LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON c.fk_projet = ec.element_id
				LEFT JOIN ".MAIN_DB_PREFIX."propal as pr ON pr.fk_projet = ec.element_id
				WHERE uu.statut = 1 
				AND ec.fk_c_type_contact = tc.rowid
				AND cp.entity IN (1)
				AND tc.element = 'project'
				AND tc.source = 'internal'
				AND tc.active = 1
				AND tc.code = 'PROJECTCONTRIBUTOR'
				AND p.fk_statut = 1
				AND DATE(cp.date_fin) >= '".$db->escape($today)."'
				GROUP BY cp.rowid
				ORDER BY uu.rowid DESC";
		
			$resql = $db->query($sql);

		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				if ($obj->status == 2 || $obj->status == 3 || $obj->status == 6) {
					 // Les dates en timestamp GMT
					 $date_debut_gmt = dol_mktime(0, 0, 0, date("m", strtotime($obj->date_start)), date("d", strtotime($obj->date_start)), date("Y", strtotime($obj->date_start)), 1);
					 $date_fin_gmt = dol_mktime(0, 0, 0, date("m", strtotime($obj->date_end)), date("d", strtotime($obj->date_end)), date("Y", strtotime($obj->date_end)), 1);
	 
					 // Calcul des jours ouvrés
					 $obj->nb_open_day_calculated = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $obj->halfday);
					$listabs[] = $obj;
				}
			}
			$db->free($resql);
			return $listabs;
		} else {
			dol_print_error($db);
		}
	}


	/**
	 * 
	 */
	public function filterResourcesByIds($ressources, $id_filter, $element_id_filter) {
		// Filter the resources based on the $params
		$filtered_resources = array_filter($ressources, function($ressource) use ($id_filter, $element_id_filter) {
			// var_dump($id_filter);
			$matches_id = (empty($id_filter) || $ressource->id === $id_filter);
			$matches_element_id = (empty($element_id_filter) || $ressource->element_id === $element_id_filter);
	
			return $matches_id && $matches_element_id;
		});
		// Reindex the filtered array to maintain sequential keys
		return array_values($filtered_resources);
	}

}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class ChargeLine. You can also remove this and generate a CRUD class for lines objects.
 */
class ChargeLine extends CommonObjectLine
{
	// To complete with content of an object ChargeLine
	// We should have a field rowid, fk_charge and position

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
