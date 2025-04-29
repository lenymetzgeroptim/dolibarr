<?php
/* Copyright (C) 2017  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023  Frédéric France          <frederic.france@netlogic.fr>
 * Copyright (C) 2024 METZGER Leny <l.metzger@optim-industries.fr>
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
 * \file        class/elementprerequis.class.php
 * \ingroup     formationhabilitation
 * \brief       This file is a CRUD class file for ElementPrerequis (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/formationhabilitation/class/visitemedical.class.php';

/**
 * Class for ElementPrerequis
 */
class ElementPrerequis extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'formationhabilitation';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'elementprerequis';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'formationhabilitation_elementprerequis';

	/**
	 * @var int  	Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for elementprerequis. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'elementprerequis@formationhabilitation' if picto is file 'img/object_elementprerequis.png'.
	 */
	public $picto = 'fa-tasks';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price', 'stock',
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
		"rowid" => array("type"=>"integer", "label"=>"TechnicalID", "enabled"=>"1", 'position'=>1, 'notnull'=>1, "visible"=>"0", "noteditable"=>"1", "index"=>"1", "css"=>"left", "comment"=>"Id"),
		"import_key" => array("type"=>"varchar(14)", "label"=>"ImportId", "enabled"=>"1", 'position'=>1000, 'notnull'=>-1, "visible"=>"-2",),
		"sourcetype" => array("type"=>"varchar(128)", "label"=>"SourceType", "enabled"=>"1", 'position'=>10, 'notnull'=>1, "visible"=>"0",),
		"fk_source" => array("type"=>"integer", "label"=>"SourceObject", "enabled"=>"1", 'position'=>11, 'notnull'=>1, "visible"=>"0",),
		"prerequistype" => array("type"=>"varchar(128)", "label"=>"PrerequisType", "enabled"=>"1", 'position'=>20, 'notnull'=>1, "visible"=>"0",),
		"prerequisobjects" => array("type"=>"varchar(255)", "label"=>"PrerequisObjects", "enabled"=>"1", 'position'=>21, 'notnull'=>1, "visible"=>"1",),
		"condition_group" => array("type"=>"integer", "label"=>"ConditionGroupe", "enabled"=>"1", 'position'=>25, 'notnull'=>1, "visible"=>"0"),
	);
	public $rowid;
	public $import_key;
	public $sourcetype;
	public $fk_source;
	public $prerequistype;
	public $prerequisobjects;
	public $condition_group;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'formationhabilitation_elementprerequisline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_elementprerequis';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'ElementPrerequisline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array('mychildtable' => array('name'=>'ElementPrerequis', 'fk_element'=>'fk_elementprerequis'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('formationhabilitation_elementprerequisdet');

	// /**
	//  * @var ElementPrerequisLine[]     Array of subtable lines
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
		/*if ($user->hasRight('formationhabilitation', 'elementprerequis', 'read')) {
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
	 * @return int             Return integer <0 if KO, Id of created object if OK
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
	 * @param 	int    	$id   			Id object
	 * @param 	string 	$ref  			Ref
	 * @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @param	int		$nolines		0=Default to load extrafields, 1=No extrafields
	 * @return 	int     				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->fetchCommon($id, $ref, '', $noextrafields);
		if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
			$this->fetchLines($noextrafields);
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @return 	int         			Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines($noextrafields = 0)
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon('', $noextrafields);
		return $result;
	}


	/**
	 * Load list of objects in memory from the database. Using a fetchAll is a bad practice, instead try to forge you optimized and limited SQL request.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('mystringfield'=>'value', 'myintfield'=>4, 'customsql'=>...)
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
		if (isset($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$columnName = preg_replace('/^t\./', '', $key);
				if ($key === 'customsql') {
					// Never use 'customsql' with a value from user input since it is injected as is. The value must be hard coded.
					$sqlwhere[] = $value;
					continue;
				} elseif (isset($this->fields[$columnName])) {
					$type = $this->fields[$columnName]['type'];
					if (preg_match('/^integer/', $type)) {
						if (is_int($value)) {
							// single value
							$sqlwhere[] = $key . " = " . intval($value);
						} elseif (is_array($value)) {
							if (empty($value)) {
								continue;
							}
							$sqlwhere[] = $key . ' IN (' . $this->db->sanitize(implode(',', array_map('intval', $value))) . ')';
						}
						continue;
					} elseif (in_array($type, array('date', 'datetime', 'timestamp'))) {
						$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
						continue;
					}
				}

				// when the $key doesn't fall into the previously handled categories, we do as if the column were a varchar/text
				if (is_array($value) && count($value)) {
					$value = implode(',', array_map(function ($v) {
						return "'" . $this->db->sanitize($this->db->escape($v)) . "'";
					}, $value));
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($value, true) . ')';
				} elseif (is_scalar($value)) {
					if (strpos($value, '%') === false) {
						$sqlwhere[] = $key . " = '" . $this->db->sanitize($this->db->escape($value)) . "'";
					} else {
						$sqlwhere[] = $key . " LIKE '%" . $this->db->escape($this->db->escapeforlike($value)) . "%'";
					}
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
	 * @return int             Return integer <0 if KO, >0 if OK
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
	 * @return int             Return integer <0 if KO, >0 if OK
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
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation', 'elementprerequis', 'write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation', 'elementprerequis_advance', 'validate')))
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'elementprerequis/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'elementprerequis/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'elementprerequis/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'elementprerequis/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->formationhabilitation->dir_output.'/elementprerequis/'.$oldref;
				$dirdest = $conf->formationhabilitation->dir_output.'/elementprerequis/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->formationhabilitation->dir_output.'/elementprerequis/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','formationhabilitation_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'FORMATIONHABILITATION_MYOBJECT_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','formationhabilitation_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'FORMATIONHABILITATION_MYOBJECT_CANCEL');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','formationhabilitation_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'FORMATIONHABILITATION_MYOBJECT_REOPEN');
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
		global $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowElementPrerequis")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("ElementPrerequis").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		if (property_exists($this, 'ref')) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (property_exists($this, 'label')) {
			$datas['ref'] = '<br>'.$langs->trans('Label').':</b> '.$this->label;
		}

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

		$url = dol_buildpath('/formationhabilitation/elementprerequis_card.php', 1).'?id='.$this->id;

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowElementPrerequis");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
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
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
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
		if (is_null($status)) {
			return '';
		}

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("formationhabilitation@formationhabilitation");
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
		$sql .= " date_creation as datec, tms as datem";
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", date_validation as datev";
		}
		if (!empty($this->fields['fk_user_creat'])) {
			$sql .= ", fk_user_creat";
		}
		if (!empty($this->fields['fk_user_modif'])) {
			$sql .= ", fk_user_modif";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", fk_user_valid";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if (!empty($this->fields['fk_user_creat'])) {
					$this->user_creation_id = $obj->fk_user_creat;
				}
				if (!empty($this->fields['fk_user_modif'])) {
					$this->user_modification_id = $obj->fk_user_modif;
				}
				if (!empty($this->fields['fk_user_valid'])) {
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

		$objectline = new ElementPrerequisLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_elementprerequis = '.((int) $this->id)));

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
		$langs->load("formationhabilitation@formationhabilitation");

		if (!getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON')) {
			$conf->global->FORMATIONHABILITATION_MYOBJECT_ADDON = 'mod_elementprerequis_standard';
		}

		if (getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON').".php";
			$classname = getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/formationhabilitation/");

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
	// public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	// {
	// 	global $conf, $langs;

	// 	$result = 0;
	// 	$includedocgeneration = 0;

	// 	$langs->load("formationhabilitation@formationhabilitation");

	// 	if (!dol_strlen($modele)) {
	// 		$modele = 'standard_elementprerequis';

	// 		if (!empty($this->model_pdf)) {
	// 			$modele = $this->model_pdf;
	// 		} elseif (getDolGlobalString('MYOBJECT_ADDON_PDF')) {
	// 			$modele = getDolGlobalString('MYOBJECT_ADDON_PDF');
	// 		}
	// 	}

	// 	$modelpath = "core/modules/formationhabilitation/doc/";

	// 	if ($includedocgeneration && !empty($modele)) {
	// 		$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	// 	}

	// 	return $result;
	// }

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

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlogfile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__." start", LOG_INFO);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		dol_syslog(__METHOD__." end", LOG_INFO);

		return $error;
	}

	/**
	 *  Met à jour les prérequis en supprimant une formation donnée
	 *
	 *  @param int $formation_id ID de la formation à supprimer
	 *  @return bool
	 */
	function removeFormationFromPrerequis($formation_id) {
		// Récupérer toutes les entrées de prérequis contenant la formation à supprimer
		$sql = "SELECT rowid, prerequisobjects";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_elementprerequis";
		$sql .= " WHERE FIND_IN_SET(".$formation_id.", prerequisobjects) > 0";


		dol_syslog(get_class($this)."::removeFormationFromPrerequis", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$prerequisList = explode(',', $obj->prerequisobjects);

				// Supprimer l'ID de formation de la liste des prérequis
				$updatedList = array_filter($prerequisList, function($id) use ($formation_id) {
					return $id != $formation_id;
				});

				// Recréer la liste sous forme de chaîne de caractères
				$newPrerequis = implode(',', $updatedList);

				// Vérifier si la liste est vide et supprimer la ligne si nécessaire
				if (empty($newPrerequis)) {
					$sqlDelete = "DELETE FROM ".MAIN_DB_PREFIX."formationhabilitation_elementprerequis WHERE rowid = ".$obj->rowid;
					$resqlDelete = $this->db->query($sqlDelete);

					if (!$resqlDelete) {
						dol_syslog(get_class($this)."::removeFormationFromPrerequis Erreur suppression: ".$this->db->lasterror(), LOG_ERR);
						return -1;
					}
				} else {
					// Mettre à jour avec la liste non vide
					$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."formationhabilitation_elementprerequis";
					$sqlUpdate .= " SET prerequisobjects = '".$this->db->escape($newPrerequis)."'";
					$sqlUpdate .= " WHERE rowid = ".$obj->rowid;
					$resqlUpdate = $this->db->query($sqlUpdate);

					if (!$resqlUpdate) {
						dol_syslog(get_class($this)."::removeFormationFromPrerequis Erreur mise à jour: ".$this->db->lasterror(), LOG_ERR);
						return -1;
					}
				}			
			}
			return 1;
		} else {
			dol_syslog(get_class($this)."::removeFormationFromPrerequis Erreur requête: ".$this->db->lasterror(), LOG_ERR);
			return 1;
		}
	}

	/**
	 * 	Return le nombre du prochain condition_group
	 *
	 * 	@param  int		$fk_source       			Id de l'objet
	 *  @param  string		$sourcetype    				type de l'objet
	 * 	@return	int-mask-of						
	 */
	public function getNextConditionGroup($fk_source, $sourcetype)
	{
		global $conf, $user;

		$sql = "SELECT MIN(ep.condition_group + 1) AS next_missing_condition_group";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_elementprerequis as ep";
		$sql .= " WHERE ep.fk_source = $fk_source AND ep.sourcetype = '$sourcetype'";
		$sql .= " AND (condition_group + 1) NOT IN (SELECT condition_group FROM llx_formationhabilitation_elementprerequis WHERE fk_source = $fk_source AND sourcetype = '$sourcetype')";

		dol_syslog(get_class($this)."::getNextConditionGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if($obj->next_missing_condition_group > 0) {
				return $obj->next_missing_condition_group;
			}
			else {
				return 1;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Gestion des prérequis
	 *
	 * 	@param  int		$fk_user       				Id de l'utilisateur
	 *  @param  Habilitation|Autorisation|Formation	$objectparenttmp    		Objet dont on gère les prérequis
	 * 	@param  int		$withprogram    			include STATUS_PROGRAMMEE
	 * 	@param  int		$withMessage    			Display error message
	 *  @param  int		$date_finvalidite    		Date fin de validité la plus restrictive
	 *  @param  int		$formation_added    		Formation qui est ajouté à l'utilisateur
	 *  @param  int		$return_text    			Si = 1 : Return un texte avec les prérequis manquant
	 * 	@return	int|string				1 if OK, -1 if KO		
	 */
	public function gestionPrerequis($fk_user, $objectparenttmp, $withprogram = 0, $withMessage = 1, &$date_finvalidite = null, $formation_added = 0, $return_text = 0)
	{
		global $conf, $user, $langs;

		$text_for_return = '';

		$userFormation = new UserFormation($this->db);
		$formations_user = $userFormation->getAllFormationsForUser($fk_user, $withprogram);
		$visiteMedicale = new VisiteMedical($this->db);
		$natures_visite_user = $visiteMedicale->getAllNatureVisiteForUser($fk_user);
		$formation = new Formation($this->db);
		$visite_medicale = new VisiteMedical($this->db);
		$natureVM = $visite_medicale->getAllNature();

		if($formation_added > 0) {
			$formations_user['id'][] = $formation_added;
		}

		// Récupérer toutes les conditions de prérequis 
		$prerequisConditions = $objectparenttmp->getPrerequis($objectparenttmp->id);
		
		// var_dump($prerequisConditions);
		// var_dump($formations_user);
		// var_dump($natures_visite_user);

		foreach ($prerequisConditions as $condition_group => $prerequistype) {
			$conditionMetForFormation = false;
			$conditionMetForVisiteMedicale = false;
			$VisiteMedicaleConditionnelle = false;

			// Vérifier si l'utilisateur possède au moins une des formations requises dans cette condition (condition OR)
			$date_finvalidite_prerequis = '';
			foreach ($prerequistype['formation'] as $formationid) {
				if ($formationid > 0 && in_array($formationid, $formations_user['id'])) {
					$conditionMetForFormation = true; 

					if(!empty($formations_user['date_finvalidite'][$formationid]) && ($date_finvalidite_prerequis == '' || $date_finvalidite_prerequis < $formations_user['date_finvalidite'][$formationid])) {
						$date_finvalidite_prerequis = $formations_user['date_finvalidite'][$formationid];
					}
				}
			}

			// Vérifier si l'utilisateur possède au moins une des nature de visite dans cette condition (condition OR)
			foreach ($prerequistype['nature_visite'] as $nature_visiteid) {
				if ($nature_visiteid > 0 && in_array($nature_visiteid, $natures_visite_user['Conditionnelle']['naturevisite'])) {
					$conditionMetForVisiteMedicale = true; 
					$VisiteMedicaleConditionnelle = true; 
				}
				elseif ($nature_visiteid > 0 && in_array($nature_visiteid, $natures_visite_user['Apte'])) {
					$conditionMetForVisiteMedicale = true; 
					$VisiteMedicaleConditionnelle = false; 
					break;
				}

			}
			

			// Si une condition OR n'est pas remplie, générer un message d'erreur
			if (!$conditionMetForFormation && (!$conditionMetForVisiteMedicale || $VisiteMedicaleConditionnelle)) {
				if($withMessage && !$conditionMetForFormation && !$conditionMetForVisiteMedicale) {
					if(sizeof($prerequistype['formation']) > 0 && sizeof($prerequistype['nature_visite']) > 0) {
						setEventMessages($langs->trans('ErrorPrerequisFormationAptitude'), null, 'errors');
					}
					elseif(sizeof($prerequistype['formation']) > 0) {
						setEventMessages($langs->trans('ErrorPrerequisFormation'), null, 'errors');
					}
					elseif(sizeof($prerequistype['nature_visite']) > 0) {
						setEventMessages($langs->trans('ErrorPrerequisAptitude'), null, 'errors');
					}
				}

				if($return_text) {
					$prerequis_array = array();
					if(!$conditionMetForFormation && sizeof($prerequistype['formation']) > 0) {
						foreach ($prerequistype['formation'] as $formationid) {
							if ($formationid > 0) {
								$formation->fetch($formationid);
								$prerequis_array[] = $formation->label;
							}
						}
					}
					if((!$conditionMetForVisiteMedicale || $VisiteMedicaleConditionnelle) && sizeof($prerequistype['nature_visite']) > 0) {
						foreach ($prerequistype['nature_visite'] as $nature_visiteid) {
							if ($nature_visiteid > 0) {
								if(in_array($nature_visiteid,  $natures_visite_user['Conditionnelle']['naturevisite'])) {
									$prerequis_array[] = $natureVM[$nature_visiteid]." (conditionnelle pour le motif suivant : ".$natures_visite_user['Conditionnelle']['commentaire'][$natures_visite_user['Conditionnelle']['rowid'][$nature_visiteid]].")";
								}
								else {
									$prerequis_array[] = $natureVM[$nature_visiteid];
								}
							}
						}
					}
	
					if(!empty($prerequis_array)) {
						$text_for_return .= '<li>'.implode(' OU ', $prerequis_array).'</li>';
					}
				}
				
				if(!$return_text && !$conditionMetForFormation && !$conditionMetForVisiteMedicale) {
					return -1;
				}
			}
			
			if(!empty($date_finvalidite_prerequis) && (empty($date_finvalidite) || $date_finvalidite > $date_finvalidite_prerequis)) {
				$date_finvalidite = $date_finvalidite_prerequis;
			}
		}

		if(!$return_text) {
			return 1;
		}
		elseif($return_text && sizeof($prerequisConditions) == 0) {
			return '1';
		}
		elseif($return_text && sizeof($prerequisConditions) != 0) {
			return $text_for_return;
		}
	}

	/**
	 * 	Retourne la date la + réstrictive des prérequis
	 *
	 * 	@param  int		$fk_user       				Id de l'utilisateur
	 *  @param  Habilitation|Autorisation|Formation	$objectparenttmp    		Objet dont on gère les prérequis
	 * 	@param  int		$withprogram    			include STATUS_PROGRAMMEE
	 *  @param  int		$formation_added    		Formation qui est ajouté à l'utilisateur
	 * 	@return	string								Date de fin de validité
	 */
	public function getDatePrerequis($fk_user, $objectparenttmp, $withprogram = 0, $formation_added = 0)
	{
		global $conf, $user, $langs;

		$date_finvalidite = '';

		$userFormation = new UserFormation($this->db);
		$formations_user = $userFormation->getAllFormationsForUser($fk_user, $withprogram);

		if($formation_added > 0) {
			$formations_user['id'][] = $formation_added;
		}

		// Récupérer toutes les conditions de prérequis 
		$prerequisConditions = $objectparenttmp->getPrerequis($objectparenttmp->id);

		foreach ($prerequisConditions as $condition_group => $prerequistype) {
			// Vérifier si l'utilisateur possède au moins une des formations requises dans cette condition (condition OR)
			$date_finvalidite_prerequis = '';
			foreach ($prerequistype['formation'] as $formationid) {
				if ($formationid > 0 && in_array($formationid, $formations_user['id'])) {
					if($date_finvalidite_prerequis == '' || $date_finvalidite_prerequis < $formations_user['date_finvalidite'][$formationid]) {
						$date_finvalidite_prerequis = $formations_user['date_finvalidite'][$formationid];
					}
				}
			}
			
			if(empty($date_finvalidite) || $date_finvalidite > $date_finvalidite_prerequis) {
				$date_finvalidite = $date_finvalidite_prerequis;
			}
		}

		return $date_finvalidite;
	}

	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 * Custom to not truncat string for checkboxlist
	 *
	 * @param  array|null	$val	       Array of properties for field to show (used only if ->fields not defined)
	 * @param  string  		$key           Key of attribute
	 * @param  string|array	$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param  string  		$moreparam     To add more parameters on html input tag
	 * @param  string  		$keysuffix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$morecss       Value for css to define style/length of field. May also be a numeric.
	 * @param  int			$nonewbutton   Force to not show the new button on field that are links to object
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		if (!empty($this->fields)) {
			$val = $this->fields[$key];
		}

		// Validation tests and output
		$fieldValidationErrorMsg = '';
		$validationClass = '';
		$fieldValidationErrorMsg = $this->getFieldError($key);
		if (!empty($fieldValidationErrorMsg)) {
			$validationClass = ' --error'; // the -- is use as class state in css :  .--error can't be be defined alone it must be define with another class like .my-class.--error or input.--error
		} else {
			$validationClass = ' --success'; // the -- is use as class state in css :  .--success can't be be defined alone it must be define with another class like .my-class.--success or input.--success
		}

		$out = '';
		$type = '';
		$isDependList = 0;
		$param = array();
		$param['options'] = array();
		$reg = array();
		$size = !empty($this->fields[$key]['size']) ? $this->fields[$key]['size'] : 0;
		// Because we work on extrafields
		if (preg_match('/^(integer|link):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4].':'.$reg[5] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4].':'.$reg[5] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^chkbxlst:(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] => 'N');
			$type = 'chkbxlst';
		} elseif (preg_match('/varchar\((\d+)\)/', $val['type'], $reg)) {
			$param['options'] = array();
			$type = 'varchar';
			$size = $reg[1];
		} elseif (preg_match('/varchar/', $val['type'])) {
			$param['options'] = array();
			$type = 'varchar';
		} else {
			$param['options'] = array();
			$type = $this->fields[$key]['type'];
		}
		//var_dump($type); var_dump($param['options']);

		// Special case that force options and type ($type can be integer, varchar, ...)
		if (!empty($this->fields[$key]['arrayofkeyval']) && is_array($this->fields[$key]['arrayofkeyval'])) {
			$param['options'] = $this->fields[$key]['arrayofkeyval'];
			$type = (($this->fields[$key]['type']=='checkbox') ? $this->fields[$key]['type'] : 'select');
		}

		$label = $this->fields[$key]['label'];
		//$elementtype=$this->fields[$key]['elementtype'];	// Seems not used
		$default = (!empty($this->fields[$key]['default']) ? $this->fields[$key]['default'] : '');
		$computed = (!empty($this->fields[$key]['computed']) ? $this->fields[$key]['computed'] : '');
		$unique = (!empty($this->fields[$key]['unique']) ? $this->fields[$key]['unique'] : 0);
		$required = (!empty($this->fields[$key]['required']) ? $this->fields[$key]['required'] : 0);
		$autofocusoncreate = (!empty($this->fields[$key]['autofocusoncreate']) ? $this->fields[$key]['autofocusoncreate'] : 0);

		$langfile = (!empty($this->fields[$key]['langfile']) ? $this->fields[$key]['langfile'] : '');
		$list = (!empty($this->fields[$key]['list']) ? $this->fields[$key]['list'] : 0);
		$hidden = (in_array(abs($this->fields[$key]['visible']), array(0, 2)) ? 1 : 0);

		$objectid = $this->id;

		if ($computed) {
			if (!preg_match('/^search_/', $keyprefix)) {
				return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
			} else {
				return '';
			}
		}

		// Set value of $morecss. For this, we use in priority showsize from parameters, then $val['css'] then autodefine
		if (empty($morecss) && !empty($val['css'])) {
			$morecss = $val['css'];
		} elseif (empty($morecss)) {
			if ($type == 'date') {
				$morecss = 'minwidth100imp';
			} elseif ($type == 'datetime' || $type == 'link') {	// link means an foreign key to another primary id
				$morecss = 'minwidth200imp';
			} elseif (in_array($type, array('int', 'integer', 'price')) || preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $type)) {
				$morecss = 'maxwidth75';
			} elseif ($type == 'url') {
				$morecss = 'minwidth400';
			} elseif ($type == 'boolean') {
				$morecss = '';
			} else {
				if (round($size) < 12) {
					$morecss = 'minwidth100';
				} elseif (round($size) <= 48) {
					$morecss = 'minwidth200';
				} else {
					$morecss = 'minwidth400';
				}
			}
		}

		// Add validation state class
		if (!empty($validationClass)) {
			$morecss.= $validationClass;
		}

		if (in_array($type, array('date'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 0;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') {
				$value = '-1';
			}

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1);
		} elseif (in_array($type, array('datetime'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 1;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') {
				$value = '-1';
			}

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1, '', '', '', 1, '', '', 'tzuserrel');
		} elseif (in_array($type, array('duration'))) {
			$out = $form->select_duration($keyprefix.$key.$keysuffix, $value, 0, 'text', 0, 1);
		} elseif (in_array($type, array('int', 'integer'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"'.($newsize > 0 ? ' maxlength="'.$newsize.'"' : '').' value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (in_array($type, array('real'))) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (preg_match('/varchar/', $type)) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"'.($size > 0 ? ' maxlength="'.$size.'"' : '').' value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (in_array($type, array('email', 'mail', 'phone', 'url', 'ip'))) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (preg_match('/^text/', $type)) {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
				$out = $doleditor->Create(1);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif (preg_match('/^html/', $type)) {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor') && $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_5, '90%');
				$out = $doleditor->Create(1, '', true, '', '', $moreparam, $morecss);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked value="1" ';
			} else {
				$checked = ' value="1" ';
			}
			$out = '<input type="checkbox" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam ? $moreparam : '').'>';
		} elseif ($type == 'price') {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> '.$langs->getCurrencySymbol($conf->currency);
		} elseif (preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $type)) {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> ';
		} elseif ($type == 'select') {	// combo list
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if ((!isset($this->fields[$key]['default'])) || ($this->fields[$key]['notnull'] != 1)) {
				$out .= '<option value="0">&nbsp;</option>';
			}
			foreach ($param['options'] as $keyb => $valb) {
				if ((string) $keyb == '') {
					continue;
				}
				if (strpos($valb, "|") !== false) {
					list($valb, $parent) = explode('|', $valb);
				}
				$out .= '<option value="'.$keyb.'"';
				$out .= (((string) $value == (string) $keyb) ? ' selected' : '');
				if (!empty($parent)) {
					$isDependList = 1;
				}
				$out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
				$out .= '>'.$langs->trans($valb).'</option>';
			}
			$out .= '</select>';
		} elseif ($type == 'sellist') {
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0], 5);
				if (! empty($InfoFieldList[4])) {
					$pos = 0;
					$parenthesisopen = 0;
					while (substr($InfoFieldList[4], $pos, 1) !== '' && ($parenthesisopen || $pos == 0 || substr($InfoFieldList[4], $pos, 1) != ':')) {
						if (substr($InfoFieldList[4], $pos, 1) == '(') {
							$parenthesisopen++;
						}
						if (substr($InfoFieldList[4], $pos, 1) == ')') {
							$parenthesisopen--;
						}
						$pos++;
					}
					$tmpbefore = substr($InfoFieldList[4], 0, $pos);
					$tmpafter = substr($InfoFieldList[4], $pos+1);
					//var_dump($InfoFieldList[4].' -> '.$pos); var_dump($tmpafter);
					$InfoFieldList[4] = $tmpbefore;
					if ($tmpafter !== '') {
						$InfoFieldList = array_merge($InfoFieldList, explode(':', $tmpafter));
					}
					//var_dump($InfoFieldList);
				}
				$parentName = '';
				$parentField = '';

				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				// 5 : id category type
				// 6 : ids categories list separated by comma for category root
				// 7 : sort field
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}

				$filter_categorie = false;
				if (count($InfoFieldList) > 5) {
					if ($InfoFieldList[0] == 'categorie') {
						$filter_categorie = true;
					}
				}

				if ($filter_categorie === false) {
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($fields_label)) {
						$keyList .= ', ';
						$keyList .= implode(', ', $fields_label);
					}

					$sqlwhere = '';
					$sql = "SELECT " . $keyList;
					$sql .= " FROM " . $this->db->prefix() . $InfoFieldList[0];
					if (!empty($InfoFieldList[4])) {
						// can use SELECT request
						if (strpos($InfoFieldList[4], '$SEL$') !== false) {
							$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
						}

						// current object id can be use into filter
						if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
							$InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
						} else {
							$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
						}

						// We have to join on extrafield table
						$errstr = '';
						if (strpos($InfoFieldList[4], 'extra') !== false) {
							$sql .= " as main, " . $this->db->prefix() . $InfoFieldList[0] . "_extrafields as extra";
							$sqlwhere .= " WHERE extra.fk_object=main." . $InfoFieldList[2];
							$sqlwhere .= " AND " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						} else {
							$sqlwhere .= " WHERE " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						}
					} else {
						$sqlwhere .= ' WHERE 1=1';
					}
					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($InfoFieldList[0], array('tablewithentity'))) {
						$sqlwhere .= " AND entity = " . ((int) $conf->entity);
					}
					$sql .= $sqlwhere;
					//print $sql;

					// Note: $InfoFieldList can be 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:Sortfield]]]]]]'
					if (isset($InfoFieldList[7]) && preg_match('/^[a-z0-9_\-,]+$/i', $InfoFieldList[7])) {
						$sql .= " ORDER BY ".$this->db->escape($InfoFieldList[7]);
					} else {
						$sql .= " ORDER BY ".$this->db->sanitize(implode(', ', $fields_label));
					}

					dol_syslog(get_class($this) . '::showInputField type=sellist', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$out .= '<option value="0">&nbsp;</option>';
						$num = $this->db->num_rows($resql);
						$i = 0;
						while ($i < $num) {
							$labeltoshow = '';
							$obj = $this->db->fetch_object($resql);

							// Several field into label (eq table:code|libelle:rowid)
							$notrans = false;
							$fields_label = explode('|', $InfoFieldList[1]);
							if (count($fields_label) > 1) {
								$notrans = true;
								foreach ($fields_label as $field_toshow) {
									$labeltoshow .= $obj->$field_toshow . ' ';
								}
							} else {
								$labeltoshow = $obj->{$InfoFieldList[1]};
							}
							$labeltoshow = dol_trunc($labeltoshow, 45);

							if ($value == $obj->rowid) {
								foreach ($fields_label as $field_toshow) {
									$translabel = $langs->trans($obj->$field_toshow);
									if ($translabel != $obj->$field_toshow) {
										$labeltoshow = dol_trunc($translabel) . ' ';
									} else {
										$labeltoshow = dol_trunc($obj->$field_toshow) . ' ';
									}
								}
								$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
							} else {
								if (!$notrans) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
									if ($translabel != $obj->{$InfoFieldList[1]}) {
										$labeltoshow = dol_trunc($translabel, 18);
									} else {
										$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]});
									}
								}
								if (empty($labeltoshow)) {
									$labeltoshow = '(not defined)';
								}
								if ($value == $obj->rowid) {
									$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
								}

								if (!empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName . ':' . $obj->{$parentField};
									$isDependList = 1;
								}

								$out .= '<option value="' . $obj->rowid . '"';
								$out .= ($value == $obj->rowid ? ' selected' : '');
								$out .= (!empty($parent) ? ' parent="' . $parent . '"' : '');
								$out .= '>' . $labeltoshow . '</option>';
							}

							$i++;
						}
						$this->db->free($resql);
					} else {
						print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
					}
				} else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
					$data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
					$out .= '<option value="0">&nbsp;</option>';
					foreach ($data as $data_key => $data_value) {
						$out .= '<option value="' . $data_key . '"';
						$out .= ($value == $data_key ? ' selected' : '');
						$out .= '>' . $data_value . '</option>';
					}
				}
			}
			$out .= '</select>';
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', $value);
			$out = $form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options']) ? null : $param['options']), $value_arr, '', 0, $morecss, 0, '100%');
		} elseif ($type == 'radio') {
			$out = '';
			foreach ($param['options'] as $keyopt => $valopt) {
				$out .= '<input class="flat '.$morecss.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '');
				$out .= ' value="'.$keyopt.'"';
				$out .= ' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
				$out .= ($value == $keyopt ? 'checked' : '');
				$out .= '/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$valopt.'</label><br>';
			}
		} elseif ($type == 'chkbxlst') {
			if (is_array($value)) {
				$value_arr = $value;
			} else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				// 5 : id category type
				// 6 : ids categories list separated by comma for category root
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}

				$filter_categorie = false;
				if (count($InfoFieldList) > 5) {
					if ($InfoFieldList[0] == 'categorie') {
						$filter_categorie = true;
					}
				}

				if ($filter_categorie === false) {
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($fields_label)) {
						$keyList .= ', ';
						$keyList .= implode(', ', $fields_label);
					}

					$sqlwhere = '';
					$sql = "SELECT " . $keyList;
					$sql .= ' FROM ' . $this->db->prefix() . $InfoFieldList[0];
					if (!empty($InfoFieldList[4])) {
						// can use SELECT request
						if (strpos($InfoFieldList[4], '$SEL$') !== false) {
							$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
						}

						// current object id can be use into filter
						if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
							$InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
						} else {
							$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
						}

						// We have to join on extrafield table
						if (strpos($InfoFieldList[4], 'extra') !== false) {
							$sql .= ' as main, ' . $this->db->prefix() . $InfoFieldList[0] . '_extrafields as extra';
							$sqlwhere .= " WHERE extra.fk_object=main." . $InfoFieldList[2] . " AND " . $InfoFieldList[4];
						} else {
							$sqlwhere .= " WHERE " . $InfoFieldList[4];
						}
					} else {
						$sqlwhere .= ' WHERE 1=1';
					}
					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($InfoFieldList[0], array('tablewithentity'))) {
						$sqlwhere .= " AND entity = " . ((int) $conf->entity);
					}
					// $sql.=preg_replace('/^ AND /','',$sqlwhere);
					// print $sql;

					$sql .= $sqlwhere;
					dol_syslog(get_class($this) . '::showInputField type=chkbxlst', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						$i = 0;

						$data = array();

						while ($i < $num) {
							$labeltoshow = '';
							$obj = $this->db->fetch_object($resql);

							$notrans = false;
							// Several field into label (eq table:code|libelle:rowid)
							$fields_label = explode('|', $InfoFieldList[1]);
							if (count($fields_label) > 1) {
								$notrans = true;
								foreach ($fields_label as $field_toshow) {
									$labeltoshow .= $obj->$field_toshow . ' ';
								}
							} else {
								$labeltoshow = $obj->{$InfoFieldList[1]};
							}
							$labeltoshow = dol_trunc($labeltoshow, 45);

							if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
								foreach ($fields_label as $field_toshow) {
									$translabel = $langs->trans($obj->$field_toshow);
									if ($translabel != $obj->$field_toshow) {
										$labeltoshow = dol_trunc($translabel, 0) . ' ';
									} else {
										$labeltoshow = dol_trunc($obj->$field_toshow, 0) . ' ';
									}
								}

								$data[$obj->rowid] = $labeltoshow;
							} else {
								if (!$notrans) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
									if ($translabel != $obj->{$InfoFieldList[1]}) {
										$labeltoshow = dol_trunc($translabel, 0);
									} else {
										$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 0);
									}
								}
								if (empty($labeltoshow)) {
									$labeltoshow = '(not defined)';
								}

								if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
									$data[$obj->rowid] = $labeltoshow;
								}

								if (!empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName . ':' . $obj->{$parentField};
									$isDependList = 1;
								}

								$data[$obj->rowid] = $labeltoshow;
							}

							$i++;
						}
						$this->db->free($resql);

						$out = $form->multiselectarray($keyprefix . $key . $keysuffix, $data, $value_arr, '', 0, $morecss, 0, '100%');
					} else {
						print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
					}
				} else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
					$data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
					$out = $form->multiselectarray($keyprefix . $key . $keysuffix, $data, $value_arr, '', 0, $morecss, 0, '100%');
				}
			}
		} elseif ($type == 'link') {
			// $param_list='ObjectName:classPath[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'
			// Filter can contains some ':' inside.
			$param_list = array_keys($param['options']);
			$param_list_array = explode(':', $param_list[0], 4);

			$showempty = (($required && $default != '') ? 0 : 1);

			if (!preg_match('/search_/', $keyprefix)) {
				if (!empty($param_list_array[2])) {		// If the entry into $fields is set to add a create button
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusxx';
					} else {
						$morecss .= ' widthcentpercentminusx';
					}
				} else {
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusx';
					}
				}
			}
			$objectfield = $this->element.($this->module ? '@'.$this->module : '').':'.$key.$keysuffix;
			$out = $form->selectForForms($param_list_array[0], $keyprefix.$key.$keysuffix, $value, $showempty, '', '', $morecss, $moreparam, 0, (empty($val['disabled']) ? 0 : 1), '', $objectfield);

			if (!empty($param_list_array[2])) {		// If the entry into $fields is set, we must add a create button
				if ((!GETPOSTISSET('backtopage') || strpos(GETPOST('backtopage'), $_SERVER['PHP_SELF']) === 0)	// // To avoid to open several times the 'Plus' button (we accept only one level)
					&& empty($val['disabled']) && empty($nonewbutton)) {	// and to avoid to show the button if the field is protected by a "disabled".
					list($class, $classfile) = explode(':', $param_list[0]);
					if (file_exists(dol_buildpath(dirname(dirname($classfile)).'/card.php'))) {
						$url_path = dol_buildpath(dirname(dirname($classfile)).'/card.php', 1);
					} else {
						$url_path = dol_buildpath(dirname(dirname($classfile)).'/'.strtolower($class).'_card.php', 1);
					}
					$paramforthenewlink = '';
					$paramforthenewlink .= (GETPOSTISSET('action') ? '&action='.GETPOST('action', 'aZ09') : '');
					$paramforthenewlink .= (GETPOSTISSET('id') ? '&id='.GETPOST('id', 'int') : '');
					$paramforthenewlink .= (GETPOSTISSET('origin') ? '&origin='.GETPOST('origin', 'aZ09') : '');
					$paramforthenewlink .= (GETPOSTISSET('originid') ? '&originid='.GETPOST('originid', 'int') : '');
					$paramforthenewlink .= '&fk_'.strtolower($class).'=--IDFORBACKTOPAGE--';
					// TODO Add Javascript code to add input fields already filled into $paramforthenewlink so we won't loose them when going back to main page
					$out .= '<a class="butActionNew" title="'.$langs->trans("New").'" href="'.$url_path.'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF'].($paramforthenewlink ? '?'.$paramforthenewlink : '')).'"><span class="fa fa-plus-circle valignmiddle"></span></a>';
				}
			}
		} elseif ($type == 'password') {
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			if ($keyprefix.$key.$keysuffix == 'pass_crypted') {
				$out = '<input type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="pass" id="pass" value="" '.($moreparam ? $moreparam : '').'>';
				$out .= '<input type="hidden" name="pass_crypted" id="pass_crypted" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
			} else {
				$out = '<input type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif ($type == 'array') {
			$newval = $val;
			$newval['type'] = 'varchar(256)';

			$out = '';
			if (!empty($value)) {
				foreach ($value as $option) {
					$out .= '<span><a class="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
					$out .= $this->showInputField($newval, $keyprefix.$key.$keysuffix.'[]', $option, $moreparam, '', '', $morecss).'<br></span>';
				}
			}
			$out .= '<a id="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_add" href="javascript:;"><span class="fa fa-plus-circle valignmiddle"></span></a>';

			$newInput = '<span><a class="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
			$newInput .= $this->showInputField($newval, $keyprefix.$key.$keysuffix.'[]', '', $moreparam, '', '', $morecss).'<br></span>';

			if (!empty($conf->use_javascript_ajax)) {
				$out .= '
					<script nonce="'.getNonce().'">
					$(document).ready(function() {
						$("a#'.dol_escape_js($keyprefix.$key.$keysuffix).'_add").click(function() {
							$("'.dol_escape_js($newInput).'").insertBefore(this);
						});

						$(document).on("click", "a.'.dol_escape_js($keyprefix.$key.$keysuffix).'_del", function() {
							$(this).parent().remove();
						});
					});
					</script>';
			}
		}
		if (!empty($hidden)) {
			$out = '<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
		}

		if ($isDependList == 1) {
			$out .= $this->getJSListDependancies('_common');
		}
		/* Add comments
		 if ($type == 'date') $out.=' (YYYY-MM-DD)';
		 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
		 */

		// Display error message for field
		if (!empty($fieldValidationErrorMsg) && function_exists('getFieldErrorIcon')) {
			$out .= ' '.getFieldErrorIcon($fieldValidationErrorMsg);
		}

		return $out;
	}

}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class ElementPrerequisLine. You can also remove this and generate a CRUD class for lines objects.
 */
class ElementPrerequisLine extends CommonObjectLine
{
	// To complete with content of an object ElementPrerequisLine
	// We should have a field rowid, fk_elementprerequis and position

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
