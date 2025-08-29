<?php
/* Copyright (C) 2017  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023  Frédéric France          <frederic.france@netlogic.fr>
 * Copyright (C) 2024 Faure Louis <l.faure@optim-industries.fr>
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
 * \file        class/ot.class.php
 * \ingroup     ot
 * \brief       This file is a CRUD class file for Ot (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';


/**
 * Class for Ot
 */
class Ot extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'ot';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'ot';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'ot_ot';

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
	 * @var string String with name of icon for ot. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'ot@ot' if picto is file 'img/object_ot.png'.
	 */
	public $picto = 'fa-file';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_ARCHIVED = 2;
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
		"rowid" => array("type"=>"integer", "label"=>"TechnicalID", "enabled"=>"1", 'position'=>1, 'notnull'=>1, "visible"=>"0", "noteditable"=>"1", "index"=>"1", "css"=>"left", "comment"=>"Id"),
		"ref" => array("type"=>"varchar(128)", "label"=>"Ref", "enabled"=>"1", 'position'=>20, 'notnull'=>0, "visible"=>"5", "index"=>"1", "searchall"=>"1", "showoncombobox"=>"1", "validate"=>"1", "comment"=>"Reference of object"),
		"indice" => array("type"=>"integer", "label"=>"Indice", "enabled"=>"1", 'position'=>43, 'notnull'=>0, "visible"=>"5", "default"=>"0", "validate"=>"1",),
		"fk_project" => array("type"=>"integer:Project:projet/class/project.class.php:rowid(IN395,197,190,445)", "label"=>"Project", "picto"=>"project", "enabled"=>"isModEnabled('project')", 'position'=>52, 'notnull'=>0, "visible"=>"5", "index"=>"1", "css"=>"maxwidth500 widthcentpercentminusxx", "csslist"=>"tdoverflowmax150", "validate"=>"1",),
		"date_applica_ot" => array("type"=>"date", "label"=>"date applicabilité OT", "enabled"=>"1", 'position'=>540, 'notnull'=>0, "visible"=>"1",),
		"date_creation" => array("type"=>"datetime", "label"=>"DateCreation", "enabled"=>"1", 'position'=>500, 'notnull'=>0, "visible"=>"5",),
		"tms" => array("type"=>"timestamp", "label"=>"DateModification", "enabled"=>"1", 'position'=>550, 'notnull'=>0, "visible"=>"-2",),
		"fk_user_creat" => array("type"=>"integer:User:user/class/user.class.php", "label"=>"UserAuthor", "picto"=>"user", "enabled"=>"1", 'position'=>560, 'notnull'=>0, "visible"=>"5", "foreignkey"=>"0", "csslist"=>"tdoverflowmax150",),
		"fk_user_modif" => array("type"=>"integer:User:user/class/user.class.php", "label"=>"UserModif", "picto"=>"user", "enabled"=>"1", 'position'=>571, 'notnull'=>0, "visible"=>"-2", "csslist"=>"tdoverflowmax150",),
		"last_main_doc" => array("type"=>"varchar(255)", "label"=>"LastMainDoc", "enabled"=>"1", 'position'=>600, 'notnull'=>0, "visible"=>"0",),
		"import_key" => array("type"=>"varchar(14)", "label"=>"ImportId", "enabled"=>"1", 'position'=>1000, 'notnull'=>0, "visible"=>"-2",),
		"model_pdf" => array("type"=>"varchar(255)", "label"=>"Model pdf", "enabled"=>"1", 'position'=>1010, 'notnull'=>0, "visible"=>"0",),
		"status" => array("type"=>"integer", "label"=>"Status", "enabled"=>"1", 'position'=>2000, 'notnull'=>0, "visible"=>"5", "index"=>"1", "arrayofkeyval"=>array("0" => "Brouillon", "1" => "Validé", "9" => "Annulé"), "validate"=>"1",),
	);
	public $rowid;
	public $ref;
	public $indice;
	public $fk_project;
	public $date_applica_ot;
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
	// public $table_element_line = 'ot_otline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_ot';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Otline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array('mychildtable' => array('name'=>'Ot', 'fk_element'=>'fk_ot'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('ot_otdet');

	// /**
	//  * @var OtLine[]     Array of subtable lines
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
		/*if ($user->hasRight('ot', 'ot', 'read')) {
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
		global $user, $db;
		$projects = new Project($db);
		$projects = $projects->getProjectsAuthorizedForUser($user, 1);
		$this->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php:rowid(IN'.implode(',', array_keys($projects)).')';
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ot','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && !empty($user->rights->ot->ot->ot_advance->validate))))
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'ot/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'ot/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'ot/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'ot/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->ot->dir_output.'/ot/'.$oldref;
				$dirdest = $conf->ot->dir_output.'/ot/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->ot->dir_output.'/ot/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ot','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ot','ot_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'OT_MYOBJECT_UNVALIDATE');
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ot','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ot','ot_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'OT_MYOBJECT_CANCEL');
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

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ot','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ot','ot_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'OT_MYOBJECT_REOPEN');
	}

	/**
	 *	Set archive status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function setArchive($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_ARCHIVED, $notrigger, 'OT_MYOBJECT_ARCHIVE');
	}

	/**
	 * Set status to archived
	 *
	 * @param User $user      User that set status
	 * @param int  $notrigger 1=Does not execute triggers, 0= execute triggers
	 * @return int Return integer <0 if error, 0 if nothing done, >0 if success
	 */
	public function setStatusArchived($user, $notrigger = 0)
	{
		return $this->setStatusCommon($user, self::STATUS_ARCHIVED, $notrigger, 'OT_ARCHIVED');
	}

	/**
	 * Set status
	 *
	 * @param User $user       Object user that modify
	 * @param int  $status     Status
	 * @param int  $notrigger  1=Does not execute triggers, 0=Execute triggers
	 * @param string $triggercode Trigger code to use
	 * @return int Return integer <0 if error, 0 if nothing done, >0 if success
	 */
	public function setStatusCommon($user, $status, $notrigger = 0, $triggercode = '')
	{
		global $conf, $langs;

		$error = 0;

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot";
		$sql .= " SET status = " . ((int) $status);
		$sql .= " WHERE rowid = " . ((int) $this->id);

		if ($this->db->query($sql)) {
			$this->status = $status;

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger($triggercode, $user);
				if ($result < 0) {
					$error++;
				}
			}

			// Si le statut est "archivé" (2), générer automatiquement le PDF avec filigrane
			if (!$error && $status == self::STATUS_ARCHIVED) {
				$result = $this->generatePDFWithWatermark($user);
				if ($result < 0) {
					dol_syslog("Erreur lors de la génération du PDF avec filigrane", LOG_ERR);
					// Ne pas considérer cela comme une erreur bloquante
				}
			}
		} else {
			$error++;
			$this->errors[] = $this->db->lasterror();
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
	 * Generate PDF with watermark when OT is archived
	 *
	 * @param User $user User object
	 * @return int Return integer <0 if error, >0 if success
	 */
	private function generatePDFWithWatermark($user)
	{
		global $conf, $langs;

		try {
			// Charger les langues nécessaires
			$langs->loadLangs(array("main", "bills", "ot@ot"));

			// Définir le modèle PDF à utiliser
			$modelpdf = !empty($this->model_pdf) ? $this->model_pdf : 'standard';

			// Créer l'instance du générateur PDF
			$classname = 'pdf_' . $modelpdf . '_ot';
			$module_file = '/custom/ot/core/modules/ot/doc/' . $classname . '.modules.php';
			
			if (file_exists(DOL_DOCUMENT_ROOT . $module_file)) {
				require_once DOL_DOCUMENT_ROOT . $module_file;
				
				if (class_exists($classname)) {
					$obj = new $classname($this->db);
					
					 // S'assurer que l'objet est complet
					$this->fetch_thirdparty();
					
					// Générer le PDF
					$result = $obj->write_file($this, $langs);
					
					if ($result > 0) {
						// Mettre à jour le champ last_main_doc
						$filename = dol_sanitizeFileName($this->ref) . '.pdf';
						$sql = "UPDATE " . MAIN_DB_PREFIX . "ot_ot";
						$sql .= " SET last_main_doc = '" . $this->db->escape($filename) . "'";
						$sql .= " WHERE rowid = " . ((int) $this->id);
						$this->db->query($sql);
						
						dol_syslog("PDF avec filigrane généré avec succès pour l'OT " . $this->ref, LOG_INFO);
						
						// Afficher un message de succès
						global $langs;
						$langs->loadLangs(array("ot@ot"));
						setEventMessages($langs->trans("PDFWithWatermarkGenerated"), null, 'mesgs');
						
						return 1;
					} else {
						dol_syslog("Erreur lors de la génération du PDF avec filigrane pour l'OT " . $this->ref . ": " . $obj->error, LOG_ERR);
						return -1;
					}
				} else {
					dol_syslog("Classe $classname non trouvée", LOG_ERR);
					return -1;
				}
			} else {
				dol_syslog("Fichier module PDF non trouvé: " . $module_file, LOG_ERR);
				return -1;
			}
		} catch (Exception $e) {
			dol_syslog("Exception lors de la génération du PDF: " . $e->getMessage(), LOG_ERR);
			return -1;
		}
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
			return ['optimize' => $langs->trans("ShowOt")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Ot").'</u>';
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

		$url = dol_buildpath('/ot/ot_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowOt");
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
			//$langs->load("ot@ot");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validé');
			$this->labelStatus[self::STATUS_ARCHIVED] = $langs->transnoentitiesnoconv('Archivé');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Annulé');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validé');
			$this->labelStatusShort[self::STATUS_ARCHIVED] = $langs->transnoentitiesnoconv('Archivé');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Annulé');
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

		$objectline = new OtLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_ot = '.((int) $this->id)));

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
		$langs->load("ot@ot");

		if (!getDolGlobalString('OT_MYOBJECT_ADDON')) {
			$conf->global->OT_MYOBJECT_ADDON = 'mod_ot_standard';
		}

		if (getDolGlobalString('OT_MYOBJECT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('OT_MYOBJECT_ADDON').".php";
			$classname = getDolGlobalString('OT_MYOBJECT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/ot/");

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

		$langs->load("ot@ot");

		if (!dol_strlen($modele)) {
			$modele = 'standard_ot';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('MYOBJECT_ADDON_PDF')) {
				$modele = getDolGlobalString('MYOBJECT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/ot/doc/";

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

	public function showCard($fk_project)
{
    global $db, $langs, $user;

    // Inclure les bibliothèques nécessaires
    require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

    // Tableau pour stocker les contacts et leurs rôles
    $contacts_with_roles = array();

    // Requête SQL pour obtenir les contacts, leurs rôles, et leurs noms/prénoms
    $sql = "SELECT ec.fk_socpeople AS contact_id, u.lastname AS contact_lastname, u.firstname AS contact_firstname, ";
    $sql .= "ctc.code AS role_code, ctc.libelle AS role_label ";
    $sql .= "FROM " . MAIN_DB_PREFIX . "element_contact AS ec ";
    $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "c_type_contact AS ctc ON ec.fk_c_type_contact = ctc.rowid ";
    $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "user AS u ON ec.fk_socpeople = u.rowid "; // Jointure avec la table user
    $sql .= "WHERE ec.element_id = " . intval($fk_project);

    // Exécuter la requête SQL
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            // Stocker les détails de chaque contact dans le tableau
            $contacts_with_roles[] = array(
                'contact_id' => $obj->contact_id,
                'contact_lastname' => $obj->contact_lastname,
                'contact_firstname' => $obj->contact_firstname,
                'role_label' => $obj->role_label
            );
        }
		} else {
			// Gérer l'erreur SQL
			dol_print_error($db);
		}
		
        // Affichage ou traitement ultérieur des données
       $this->displayContacts($contacts_with_roles);

        // Autre logique d'affichage pour la carte...
    }

    private function displayContacts($contacts)
    {
		global $langs, $conf;
		$langs->load("ot@ot");

        // Exemple de logique d'affichage pour les contacts
        print '<table class="border" width="100%">';
        print '<tr class="liste_titre">';
		print '<th>' . $langs->trans("Nom") . '</th>';
		print '<th>' . $langs->trans("Prénom") . '</th>';
        print '<th>' . $langs->trans("Role") . '</th>';
        print '</tr>';

        foreach ($contacts as $contact) {
            print '<tr>';
			print '<td>' . $contact['contact_firstname'] . '</td>';
			print '<td>' . $contact['contact_lastname'] . '</td>';
            print '<td>' . $contact['role_label'] . '</td>';
            print '</tr>';
        }

        print '</table>';
    }





	private function formObjectOptions($fk_project)
	{
		global $db , $langs, $form;
	
			// Affichez le formulaire de confirmation avec les dropdowns
			print $form->formconfirm(
				$_SERVER["PHP_SELF"].'?id='.$fk_project,
				$langs->trans('Créé un OT'),
				$langs->trans('Voulez-vous créer un OT ?'),
				'confirm_ot',
				'',
				0,
				1
			);
	}

	/**
	 * Vérifie si l'utilisateur est dans les contacts du projet de l'OT.
	 *
	 * @param int $userId ID de l'utilisateur
	 * @return bool True si l'utilisateur est dans les contacts, False sinon
	 */
	public function isUserInProjectContacts($userId)
	{
		global $db;

		$sql = "SELECT COUNT(*) as count 
				FROM " . MAIN_DB_PREFIX . "element_contact AS ec 
				WHERE ec.element_id = " . intval($this->fk_project) . " 
				AND ec.fk_socpeople = " . intval($userId);

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			return $obj->count > 0;
		} else {
			dol_print_error($db);
			return false;
		}
	}

	/**
	 * Vérifie si l'utilisateur est dans les contacts du projet de l'OT et qu'il est un chef de projet.
	 *
	 * @param int $userId ID de l'utilisateur
	 * @return bool True si l'utilisateur est un chef de projet, False sinon
	 */
	public function isUserProjectManager($userId)
	{
		global $db;

		$sql = "SELECT COUNT(*) as count 
				FROM " . MAIN_DB_PREFIX . "element_contact AS ec 
				WHERE ec.element_id = " . intval($this->fk_project) . " 
				AND ec.fk_socpeople = " . intval($userId) . " 
				AND ec.fk_c_type_contact = 160"; // 160 correspond au rôle de chef de projet

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			return $obj->count > 0;
		} else {
			dol_print_error($db);
			return false;
		}
	}

	/**
	 * Get all users with their qualifications and contract information
	 * 
	 * @return array Array of users with qualifications
	 */
	public function getAllUsersWithQualifications()
	{
		$sql = "SELECT u.lastname, u.firstname, u.rowid, 
				GROUP_CONCAT(DISTINCT fh.ref) as habilitations,
				cct.type AS contrat
				FROM ".MAIN_DB_PREFIX."user as u
				LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_userhabilitation as fuh 
					ON u.rowid = fuh.fk_user
				LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_habilitation as fh 
					ON fuh.fk_habilitation = fh.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."ot_ot_cellule_donne AS ocd 
					ON ocd.fk_user = u.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."donneesrh_positionetcoefficient_extrafields AS drh 
					ON drh.fk_object = u.rowid
				LEFT JOIN ".MAIN_DB_PREFIX."c_contrattravail AS cct 
					ON drh.contratdetravail = cct.rowid
				WHERE u.statut = 1
				GROUP BY u.rowid, u.lastname, u.firstname, cct.type";

		$resql = $this->db->query($sql);
		$arrayresult = [];

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$arrayresult[] = $obj;
			}
		} else {
			return array('error' => 'SQL query error: '.$this->db->lasterror());
		}

		return $arrayresult;
	}

	/**
	 * Get project contacts with internal and external users
	 * 
	 * @return array Array of contacts
	 */
	public function getProjectContacts()
	{
		$sql = "
			(SELECT  
				u.firstname,
				u.lastname,
				u.office_phone AS phone,
				ctc.libelle, 
				sp.fk_c_type_contact, 
				sp.fk_socpeople,
				cct.type AS contrat,
				ctc.source AS source,
				NULL AS fonction,      
				NULL AS habilitation,
				NULL AS fk_societe,
				NULL AS societe_nom  
			FROM ".MAIN_DB_PREFIX."element_contact AS sp 
			JOIN ".MAIN_DB_PREFIX."user AS u 
				ON sp.fk_socpeople = u.rowid 
			JOIN ".MAIN_DB_PREFIX."c_type_contact AS ctc 
				ON sp.fk_c_type_contact = ctc.rowid 
			LEFT JOIN ".MAIN_DB_PREFIX."donneesrh_positionetcoefficient_extrafields AS drh 
				ON drh.fk_object = u.rowid  
			LEFT JOIN ".MAIN_DB_PREFIX."c_contrattravail AS cct 
				ON drh.contratdetravail = cct.rowid  
			WHERE sp.element_id = ".(int)$this->fk_project."
			AND sp.statut = 4
			AND ctc.element = 'project'
			AND ctc.source = 'internal')

			UNION

			(SELECT  
				spc.firstname,
				spc.lastname,
				spc.phone_mobile AS phone,
				ctc.libelle, 
				sp.fk_c_type_contact, 
				sp.fk_socpeople,
				ots.contrat AS contrat,
				ctc.source AS source,
				ots.fonction,  
				ots.habilitation,
				spc.fk_soc as fk_societe,
				s.nom AS societe_nom
			FROM ".MAIN_DB_PREFIX."element_contact AS sp 
			JOIN ".MAIN_DB_PREFIX."socpeople AS spc 
				ON sp.fk_socpeople = spc.rowid 
			JOIN ".MAIN_DB_PREFIX."c_type_contact AS ctc 
				ON sp.fk_c_type_contact = ctc.rowid 
			LEFT JOIN ".MAIN_DB_PREFIX."ot_ot_sous_traitants AS ots 
				ON sp.fk_socpeople = ots.fk_socpeople 
				AND ots.ot_id = ".(int)$this->id."
			LEFT JOIN ".MAIN_DB_PREFIX."societe AS s 
				ON spc.fk_soc = s.rowid
			WHERE sp.element_id = ".(int)$this->fk_project."
			AND sp.statut = 4
			AND ctc.element = 'project'
			AND ctc.source = 'external')";

		$resql = $this->db->query($sql);
		$arrayresult = [];

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				// Pour les utilisateurs internes, récupérer les fonctions et habilitations
				if ($obj->source == 'internal') {
					$obj->fonction = $this->getFonctions($obj->fk_socpeople);
					$obj->habilitation = $this->getHabilitations($obj->fk_socpeople);
				}
				$arrayresult[] = $obj; 
			}
		} else {
			return array('error' => 'SQL query error: '.$this->db->lasterror());
		}

		return $arrayresult;
	}

/**
	 * Get user functions for a specific user and project
	 * 
	 * @param int $userId User ID
	 * @return string User functions
	 */
	public function getFonctions($userId)
	{
			// Vérification de base
		if (empty($userId) || !is_numeric($userId)) {
			return "Utilisateur invalide";
		}
		
		if (empty($this->fk_project) || !is_numeric($this->fk_project)) {
			return "Projet non défini";
		}

		try {
			$fonctions = array();
		
			// Requête SQL simplifiée pour le test
			$sql = "SELECT cf.label 
					FROM ".MAIN_DB_PREFIX."element_contact_fonction as ecf 
					INNER JOIN ".MAIN_DB_PREFIX."contact_fonction as cf ON cf.rowid = ecf.function_id 
					WHERE ecf.element_id = ".intval($this->fk_project)." 
					AND ecf.contact_id = ".intval($userId);
			
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				if ($num > 0) {
					while ($obj = $this->db->fetch_object($resql)) {
						$fonctions[] = $obj->label;
					}
				}
				$this->db->free($resql);
			} else {
				return "Erreur SQL: " . $this->db->lasterror();
			}
			
			return !empty($fonctions) ? implode('-', $fonctions) : "Aucune fonction";
			
		} catch (Exception $e) {
			return "Erreur: " . $e->getMessage();
		}
	}

	/**
	 * Get user qualifications for a specific user
	 * 
	 * @param int $userId User ID
	 * @return string User qualifications
	 */
	public function getHabilitations($userId)
	{
			// Vérification de base
		if (empty($userId) || !is_numeric($userId)) {
			return "Utilisateur invalide";
		}

		try {
			$habilitationRefs = [];

			$sql = "SELECT fh.ref 
					FROM ".MAIN_DB_PREFIX."formationhabilitation_userhabilitation as fuh 
					JOIN ".MAIN_DB_PREFIX."formationhabilitation_habilitation as fh 
						ON fuh.fk_habilitation = fh.rowid 
					WHERE fuh.fk_user = ".intval($userId);
		
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				if ($num > 0) {
					while ($obj = $this->db->fetch_object($resql)) {
						$habilitationRefs[] = $obj->ref;
					}
				}
				$this->db->free($resql);
			} else {
				return "Erreur SQL: " . $this->db->lasterror();
			}
		
			return !empty($habilitationRefs) ? implode("-", $habilitationRefs) : "Aucune habilitation";
			
		} catch (Exception $e) {
			return "Erreur: " . $e->getMessage();
		}
	}

	/**
	 * Get all cells data for this OT
	 * 
	 * @return array Array of cells with their data
	 */
	public function getCellsData()
	{
		$sql = "SELECT oc.rowid, oc.x, oc.y, oc.type, oc.title, oc.tms
				FROM " . MAIN_DB_PREFIX . "ot_ot_cellule as oc
				WHERE oc.ot_id = " . (int)$this->id . "
				ORDER BY oc.x ASC, oc.y ASC, oc.tms DESC";

		$resql = $this->db->query($sql);
		$cellData = [];

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$cellData[] = $obj;
			}
		}

		// Ajouter les informations utilisateur aux cellules
		foreach ($cellData as $cell) {
			if (!is_object($cell) || !isset($cell->rowid)) {
				continue;
			}

			// Pour les cartes principales
			if ($cell->type === 'cardprincipale') {
				$userInfo = $this->getCellUserInfo($cell->rowid);
				if ($userInfo) {
					$cell->userId = $userInfo->userId;
					$cell->firstname = $userInfo->firstname;
					$cell->lastname = $userInfo->lastname;
					$cell->phone = $userInfo->phone ?? '';
				} else {
					$cell->userId = null;
					$cell->firstname = '';
					$cell->lastname = '';
					$cell->phone = '';
				}
			}

			// Pour les listes uniques
			if ($cell->type === 'listeunique') {
				$cell->userDetails = $this->getCellUserDetails($cell->rowid);
			}

			// Pour toutes les cellules, récupérer les IDs utilisateurs
			$userIds = $this->getCellUserIds($cell->rowid);
			if ($cell->type == 'card') {
				$cell->userId = count($userIds) > 0 ? $userIds[0] : null;
			} else {
				$cell->userIds = $userIds;
			}
		}

		// Ajouter les sous-traitants avec la structure correcte
		$subcontractors = $this->getSubcontractors();
		$cellData[] = (object)[
			'type' => 'soustraitantlist',
			'subcontractors' => $subcontractors
		];

		return $cellData;
	}

	/**
	 * Get user information for a specific cell
	 * 
	 * @param int $cellId Cell ID
	 * @return object|null User information
	 */
	private function getCellUserInfo($cellId)
	{
		$sql = "SELECT 
					u.rowid AS userId,
					u.firstname,
					u.lastname,
					u.office_phone AS phone
				FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne AS ocd
				JOIN " . MAIN_DB_PREFIX . "user AS u ON ocd.fk_user = u.rowid
				WHERE ocd.ot_cellule_id = " . (int)$cellId;

		$resql = $this->db->query($sql);
		
		if ($resql) {
			return $this->db->fetch_object($resql);
		}
		
		return null;
	}

	/**
	 * Get detailed user information for a specific cell (for unique lists)
	 * 
	 * @param int $cellId Cell ID
	 * @return array Array of user details
	 */
	private function getCellUserDetails($cellId)
	{
		$sql = "SELECT  
			u.rowid AS userId,
			u.firstname,
			u.lastname,
			u.office_phone AS phone,        
			cct.type AS contrat
		FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne AS ocd
		JOIN " . MAIN_DB_PREFIX . "user AS u 
			ON ocd.fk_user = u.rowid
		LEFT JOIN " . MAIN_DB_PREFIX . "donneesrh_positionetcoefficient_extrafields AS drh 
			ON drh.fk_object = u.rowid
		LEFT JOIN " . MAIN_DB_PREFIX . "c_contrattravail AS cct 
			ON drh.contratdetravail = cct.rowid
		WHERE ocd.ot_cellule_id = " . (int)$cellId;

		$resql = $this->db->query($sql);
		$userDetails = [];
		$seenUserIds = [];

		if ($resql) {
			while ($user = $this->db->fetch_object($resql)) {
				if (in_array($user->userId, $seenUserIds)) {
					continue;
				}
				$seenUserIds[] = $user->userId;

				// Vérifier si l'utilisateur n'est pas Q3SE ou PCR
				if (!$this->isUserQ3SEOrPCR($user->userId)) {
					$userDetails[] = [
						'userId' => $user->userId,
						'firstname' => $user->firstname,
						'lastname' => $user->lastname,
						'phone' => $user->phone,
						'contrat' => $user->contrat ?? 'Non défini',
						'fonction' => $this->getFonctions($user->userId) ?? 'Non définie',
						'habilitation' => $this->getHabilitations($user->userId) ?? 'Aucune habilitation'
					];
				}
			}
		}

		return $userDetails;
	}

	/**
	 * Check if user is Q3SE or PCR
	 * 
	 * @param int $userId User ID
	 * @return bool True if user is Q3SE or PCR
	 */
	private function isUserQ3SEOrPCR($userId)
	{
		$sql = "SELECT ctc.libelle 
				 FROM ".MAIN_DB_PREFIX."element_contact AS sp 
				 JOIN ".MAIN_DB_PREFIX."c_type_contact AS ctc 
					ON sp.fk_c_type_contact = ctc.rowid 
				 WHERE sp.fk_socpeople = ".(int)$userId." 
				 AND sp.element_id = ".(int)$this->fk_project." 
				 AND sp.statut = 4 
				 AND ctc.element = 'project'";

		$resql = $this->db->query($sql);
		
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (in_array($obj->libelle, array('ResponsableQ3SE', 'PCRRéférent'))) {
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Get user IDs for a specific cell
	 * 
	 * @param int $cellId Cell ID
	 * @return array Array of user IDs
	 */
	private function getCellUserIds($cellId)
	{
		$sql = "SELECT fk_user
				FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne
				WHERE ot_cellule_id = " . (int)$cellId;

		$resql = $this->db->query($sql);
		$users = [];

		if ($resql) {
			while ($user = $this->db->fetch_object($resql)) {
				$users[] = $user->fk_user;
			}
		}

		return $users;
	}

	/**
	 * Get subcontractors for this OT
	 * 
	 * @return array Array of subcontractors
	 */
	public function getSubcontractors()
	{
		$sql = "SELECT ots.rowid, ots.fk_socpeople, ots.fk_societe, ots.fonction, ots.contrat, ots.habilitation,
					sp.firstname, sp.lastname, s.nom AS societe_nom
				FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants AS ots
				LEFT JOIN " . MAIN_DB_PREFIX . "socpeople AS sp ON ots.fk_socpeople = sp.rowid
				LEFT JOIN " . MAIN_DB_PREFIX . "societe AS s ON ots.fk_societe = s.rowid
				WHERE ots.ot_id = " . (int)$this->id;

		$resql = $this->db->query($sql);
		$subcontractors = [];

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$subcontractors[] = [
					'rowid' => $obj->rowid,
					'fk_socpeople' => $obj->fk_socpeople,
					'fk_societe' => $obj->fk_societe,
					'fonction' => $obj->fonction,
					'contrat' => $obj->contrat,
					'habilitation' => $obj->habilitation,
					'firstname' => $obj->firstname,
					'lastname' => $obj->lastname,
					'societe_nom' => $obj->societe_nom
				];
			}
		}

		return $subcontractors;
	}

    /**
     * Clone l'architecture d'un autre OT vers cet OT
     * 
     * @param int $sourceOTId ID de l'OT source à cloner
     * @return int <0 if error, >0 if success
     */
    public function cloneArchitectureFrom($sourceOTId)
    {
        global $user;

        if (empty($this->id) || empty($sourceOTId)) {
            $this->error = "IDs des OTs manquants pour le clonage";
            return -1;
        }

        try {
            $this->db->begin();

            // 1. Cloner les cellules (ot_ot_cellule) - exclure les listes uniques
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule 
                    (ot_id, title, type, x, y)
                    SELECT 
                        " . intval($this->id) . ",
                        title,
                        type,
                        x,
                        y
                    FROM " . MAIN_DB_PREFIX . "ot_ot_cellule 
                    WHERE ot_id = " . intval($sourceOTId) . "
                    AND type != 'listeunique'";

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->error = "Erreur lors du clonage des cellules: " . $this->db->lasterror();
                $this->db->rollback();
                return -1;
            }

            // 2. Récupérer les mappings anciens/nouveaux IDs des cellules (sans les listes uniques)
            $cellMapping = array();
            $sql = "SELECT rowid, title, type, x, y FROM " . MAIN_DB_PREFIX . "ot_ot_cellule 
                    WHERE ot_id = " . intval($sourceOTId) . " AND type != 'listeunique'";
            $resql = $this->db->query($sql);
            if ($resql) {
                $oldCells = array();
                while ($obj = $this->db->fetch_object($resql)) {
                    $oldCells[] = $obj;
                }

                // Récupérer les nouvelles cellules créées (sans les listes uniques)
                $sql = "SELECT rowid, title, type, x, y FROM " . MAIN_DB_PREFIX . "ot_ot_cellule 
                        WHERE ot_id = " . intval($this->id) . " AND type != 'listeunique' ORDER BY rowid";
                $resql = $this->db->query($sql);
                if ($resql) {
                    $newCells = array();
                    while ($obj = $this->db->fetch_object($resql)) {
                        $newCells[] = $obj;
                    }

                    // Créer le mapping basé sur la position et le type
                    for ($i = 0; $i < count($oldCells) && $i < count($newCells); $i++) {
                        if ($oldCells[$i]->type == $newCells[$i]->type && 
                            $oldCells[$i]->x == $newCells[$i]->x && 
                            $oldCells[$i]->y == $newCells[$i]->y) {
                            $cellMapping[$oldCells[$i]->rowid] = $newCells[$i]->rowid;
                        }
                    }
                }
            }

            // 3. Cloner les données des cellules (ot_ot_cellule_donne) - sans habilitations et contrat
            foreach ($cellMapping as $oldCellId => $newCellId) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                        (ot_cellule_id, fk_user)
                        SELECT 
                            " . intval($newCellId) . ",
                            fk_user
                        FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne 
                        WHERE ot_cellule_id = " . intval($oldCellId);

                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->error = "Erreur lors du clonage des données de cellule " . $oldCellId . ": " . $this->db->lasterror();
                    $this->db->rollback();
                    return -1;
                }
            }

            // 4. Gestion des sous-traitants avec comparaison projet/OT précédent
            $this->cloneAndUpdateSubcontractors($sourceOTId);

            $this->db->commit();
            return 1;

        } catch (Exception $e) {
            $this->error = "Erreur lors du clonage de l'architecture: " . $e->getMessage();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Clone et met à jour les sous-traitants
     * 
     * @param int $sourceOTId ID de l'OT source
     */
    private function cloneAndUpdateSubcontractors($sourceOTId)
    {
        // Vérifier s'il y a déjà des données dans l'OT
        $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants WHERE ot_id = " . intval($this->id);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->count > 0) {
                // Il y a déjà des données, on ne fait rien
                return;
            }
        }

        // 1. D'abord, cloner tous les sous-traitants de l'OT source
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                (ot_id, fk_socpeople, fk_societe, fonction, contrat, habilitation)
                SELECT 
                    " . intval($this->id) . ",
                    fk_socpeople,
                    fk_societe,
                    fonction,
                    contrat,
                    habilitation
                FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                WHERE ot_id = " . intval($sourceOTId);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = "Erreur lors du clonage des sous-traitants: " . $this->db->lasterror();
            return -1;
        }

        // 2. Ensuite, ajouter SEULEMENT les nouveaux sous-traitants du projet qui ne sont PAS dans l'OT source
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                (ot_id, fk_socpeople, fk_societe, fonction, contrat, habilitation)
                SELECT DISTINCT
                    " . intval($this->id) . ",
                    ec.fk_socpeople,
                    sp.fk_soc,
                    NULL,
                    NULL,
                    NULL
                FROM " . MAIN_DB_PREFIX . "element_contact AS ec 
                JOIN " . MAIN_DB_PREFIX . "socpeople AS sp ON ec.fk_socpeople = sp.rowid 
                JOIN " . MAIN_DB_PREFIX . "c_type_contact AS ctc ON ec.fk_c_type_contact = ctc.rowid 
                WHERE ec.element_id = " . intval($this->fk_project) . "
                AND ec.statut = 4
                AND ctc.element = 'project'
                AND ctc.source = 'external'
                AND ec.fk_socpeople NOT IN (
                    SELECT fk_socpeople 
                    FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants 
                    WHERE ot_id = " . intval($sourceOTId) . "
                    AND fk_socpeople IS NOT NULL
                )";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = "Erreur lors de l'ajout des nouveaux sous-traitants: " . $this->db->lasterror();
            return -1;
        }
    }

	/**
	 * Vérifie si l'utilisateur a les droits d'écriture sur les OT.
	 *
	 * @param int $userId ID de l'utilisateur
	 * @return bool True si l'utilisateur a les droits, False sinon
	 */
	public function hasOTWriteRights($userId)
	{
		global $db;
		
		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user = new User($db);
		if ($user->fetch($userId) > 0) {
			return $user->hasRight('ot', 'ot', 'write');
		}
		
		return false;
	}
}





require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class OtLine. You can also remove this and generate a CRUD class for lines objects.
 */
class OtLine extends CommonObjectLine
{
	// To complete with content of an object OtLine
	// We should have a field rowid, fk_ot and position

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


