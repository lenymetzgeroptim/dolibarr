<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2023 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 * \file        class/goalelement.class.php
 * \ingroup     sse
 * \brief       This file is a CRUD class file for GoalElement (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
// require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 * Class for GoalElement
 */
class GoalElement extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'sse';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'goalelement';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'sse_goalelement';

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
	 * @var string String with name of icon for goalelement. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'goalelement@sse' if picto is file 'img/object_goalelement.png'.
	 */
	public $picto = 'fa-trophy';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_SPECIFIC = 2;
	const STATUS_GENERIC = 3;
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
	 *		Note: Filter must be a Dolibarr filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or 'isModEnabled("multicurrency")' ...)
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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>2, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>2000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Valid&eacute;', '2'=>'Spécifique', '3'=>'Générique', '9'=>'Annul&eacute;'), 'validate'=>'1',),
		'date_debut' => array('type'=>'date', 'label'=>'Date de début', 'enabled'=>'1', 'position'=>55, 'notnull'=>-1, 'visible'=>0, 'alwayseditable'=>'1',),
		'date_fin' => array('type'=>'date', 'label'=>'Date de fin', 'enabled'=>'1', 'position'=>60, 'notnull'=>-1, 'visible'=>0, 'alwayseditable'=>'1',),
		'nbcauserie' => array('type'=>'integer', 'label'=>'Causeries prévues', 'enabled'=>'1', 'position'=>63, 'notnull'=>-1,  'alwayseditable'=>'1', 'visible'=>1,),
		'fk_user' => array('type'=>'integer:User:user/class/user.classe.php', 'label'=>'Participant', 'enabled'=>'1', 'position'=>5, 'notnull'=>-1, 'visible'=>1,),
		'fk_goal' => array('type'=>'integer:Goal:custom/sse/class/goal.class.php', 'label'=>'Goal', 'enabled'=>'1', 'position'=>70, 'notnull'=>-1, 'visible'=>0,),
		'nb_accomplished' => array('type'=>'integer', 'label'=>'Causeries réalisées', 'enabled'=>'1', 'position'=>75, 'notnull'=>1, 'visible'=>1, 'default'=>'0',),
		'entity' => array('type'=>'integer', 'label'=>'entity', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>0,),
		'p_year' => array('type'=>'integer', 'label'=>'Période', 'enabled'=>'1', 'position'=>85, 'notnull'=>0, 'visible'=>1,),
	);
	public $rowid;
	public $date_creation;
	public $tms;
	public $status;
	public $date_debut;
	public $date_fin;
	public $nbcauserie;
	public $fk_user;
	public $fk_goal;
	public $nb_accomplished;
	public $entity;
	public $p_year;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'sse_goalelementline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_goalelement';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'GoalElementline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('sse_goalelementdet');

	// /**
	//  * @var GoalElementLine[]     Array of subtable lines
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

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->sse->goalelement->read) {
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
		global $conf;

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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->goalelement->write))
		 || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->goalelement->goalelement_advance->validate))))
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
				$result = $this->call_trigger('GOALELEMENT_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'goalelement/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'goalelement/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->sse->dir_output.'/goalelement/'.$oldref;
				$dirdest = $conf->sse->dir_output.'/goalelement/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->sse->dir_output.'/goalelement/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->write))
		 || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->sse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'GOALELEMENT_UNVALIDATE');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->write))
		 || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->sse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'GOALELEMENT_CANCEL');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->write))
		 || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->sse->sse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'GOALELEMENT_REOPEN');
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

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("GoalElement").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/sse/goalelement_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowGoalElement");
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
		$hookmanager->initHooks(array($this->element.'dao'));
		$parameters = array('id'=>$this->id, 'getnomurl' => &$result);
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
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	/*
	public function getKanbanView($option = '')
	{
		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if (property_exists($this, 'label')) {
			$return .= '<br><span class="info-box-label opacitymedium">'.$this->label.'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status margintoponly">'.$this->getLibStatut(5).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}
	*/

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
			$this->labelStatus[self::STATUS_SPECIFIC] = $langs->transnoentitiesnoconv('Spécifique');
			$this->labelStatus[self::STATUS_GENERIC] = $langs->transnoentitiesnoconv('Générique');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_SPECIFIC] = $langs->transnoentitiesnoconv('Spécifique');
			$this->labelStatusShort[self::STATUS_GENERIC] = $langs->transnoentitiesnoconv('Générique');
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

		$objectline = new GoalElementLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_goalelement = '.((int) $this->id)));

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

		if (empty($conf->global->SSE_GOALELEMENT_ADDON)) {
			$conf->global->SSE_GOALELEMENT_ADDON = 'mod_goalelement_standard';
		}

		if (!empty($conf->global->SSE_GOALELEMENT_ADDON)) {
			$mybool = false;

			$file = $conf->global->SSE_GOALELEMENT_ADDON.".php";
			$classname = $conf->global->SSE_GOALELEMENT_ADDON;

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
			$modele = 'standard_goalelement';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->GOALELEMENT_ADDON_PDF)) {
				$modele = $conf->global->GOALELEMENT_ADDON_PDF;
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

	
	/**
	 * Return an HTML table that contains a bar chart of causerie setting goals.
	 *
	 * @param int $year_start  Start year of the period to display.
	 * @param int $year_end    End year of the period to display.
	 *
	 * @return string          An HTML string containing the bar chart table.
	 */
	public function getCauserieGoalBarChart($year_start, $year_end)
	{
		global $conf, $db, $langs, $user;

		if ($startyear > $endyear) {
			return -1;
		}

		$result= '';

		// if (empty($conf->causerie->enabled) || empty($user->rights->causerie->read_admin)) {
		// 	return '';
		// }

		$sql = "SELECT ge.nb_accomplished, ge.nbcauserie as nb_expected, ge.p_year, count(ge.fk_user) as nb_users";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_goalelement as ge";
		$sql .= " GROUP BY ge.nb_accomplished";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
				$i = 0;
				
				$dataseries = array();
				$colorseries = array();
				$listofobject = array();
				$vals = array();
	
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$vals['year'] = $obj->p_year;
						$vals['nb_accomplished'] = $obj->nb_accomplished;
						$vals['nb_users'] = $obj->nb_users;
						$listofobject[] = $vals;
					}
					
					$i++;
	
				}
		
				foreach($listofobject as $value) {
					$dataseries[] = array($value['nb_accomplished'], $value['nb_users']);
				}

				$db->free($resql);
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				// $result = '<div class="div-table-responsive-no-min">';
				// $result .= '<table class="noborder nohover centpercent">';
				// $result .=  '<tr class="liste_titre">';
				// $result .=  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Causerie").'</td>';
				// $result .=  '</tr>';

				if ($conf->use_javascript_ajax) {
					$dataarr = array('Nombre des utilisateurs par causeries');

					$result .=  '<tr>';
					$result .=  '<td align="center" colspan="2">';
					include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$mesg = $dolgraph->isGraphKo();
					
					if (!$mesg) {
						
						$dolgraph->SetData(array_values($dataseries));
						//$dolgraph->SetDataColor(array($badgeStatus4, '#264653', '#e63946', $badgeStatus3, '#a3b18a'));
						$dolgraph->setShowLegend(1);
						$dolgraph->setShowPercent(2);
						$dolgraph->SetType(array('bars'));
						$dolgraph->setHeight('150');
						$dolgraph->setWidth('300');
						$dolgraph->setLegend(array_values($dataarr));
						$dolgraph->draw('infographcauserie');
						$result .=  $dolgraph->show();
						
						$result .=  '</td>';
						$result .=  '<td> &nbsp;</td>';
						$result .=  '</tr>';
					}
				}	
					
				$result .=  '<tr class="liste_total">';
				$result .=  '<td>'.$langs->trans("Total").'</td>';
				$result .=  '<td class="right">'.$total.'</td>';
				$result .=  '<td> &nbsp;</td>';
				$result .=  '</tr>';

				$result .=  '</table></div></form>';
				$result .=  '</div>';
				$result .=  '<br>';		
				
		}else {
			dol_print_error($db);
		}	

		return $result; 
	}


	/**
	 * Return an HTML table that contains a monthly bar chart of causeries for a specific user and year.
	 *
	 * @param int $p_year   Year for which to retrieve the causerie data.
	 * @param int $userid   ID of the user for whom the causeries are displayed.
	 *
	 * @return string       An HTML string containing the bar chart table by month.
	 */
	public function getCauserieByMonthChart($p_year, $userid)
	{
		global $conf, $db, $langs, $user;
		
		$sql = "SELECT count(c.rowid) as nb_causerie, c.date_debut as causerieyear";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		if(isset($userid) && $userid !== '-1') {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerieattendance as ca on ca.fk_causerie = c.rowid";
		}
		$sql .= " WHERE c.status > 3";

		if($p_year !== 'all') {
		$sql .= " AND YEAR(c.date_debut) ='".$p_year."'";
		}

		if(isset($userid) && $userid !== '-1') {
			$sql .= " AND ca.fk_user ='".$userid."'";
			$sql .= " AND ca.presence = 1";
		}
		
		$sql .= " GROUP BY YEAR(c.date_debut), MONTH(c.date_debut)";
		if($p_year !== 'all') {
			$sql .= " HAVING YEAR(c.date_debut) =".$p_year;
		}
		
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
				$i = 0;
				$dataseries = array();
				$colorseries = array();
				$nb_causerie = array();
				$year = array();
				$labels = array();
				$vals = array();
				$test = array();
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
				
					if ($obj) {
						$year[dol_print_date($obj->causerieyear, 'Y')] = dol_print_date($obj->causerieyear, 'Y');
						
						$list[dol_print_date($obj->causerieyear, 'Y, M')] = $obj->nb_causerie;
						$total += $obj->nb_causerie;
					}
					$i++;
				}
				ksort($year);
				
				foreach($list as $month => $nb_causerie) {
					$dataseries[] = array($month, intval($nb_causerie));
				}

				$db->free($resql);
				$participant = new User($db);
				$name = '';
				if (isset($userid) && $userid !== '-1') {
					$participant->fetch($userid);
					$name = $participant->firstname.' '.$participant->lastname; 
					$login = $participant->login; 
				}
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				$result = '<div class="div-table-responsive-no-min">';
				$result .= '<table class="noborder nohover centpercent">';
		  
				$result .=  '<tr class="liste_titre">';
				if(isset($userid) && $userid !== '-1') {
					$result .=  '<td colspan="2">'.$langs->trans("Statistiques").' - '.$langs->trans("Nombre des participations aux Causeries par Thème ").' <span class="badge" title="'.$name.'" style="font-size: 0.8em; background-color: #e9f7f; color: white; padding: 3px 8px; border-radius: 12px; font-weight: bold;">'.$login.'</span> '.$langs->trans(" Année").' '.implode(' - ', $year).'</td>';
					// $result .=  '<td colspan="2">'.$langs->trans("Statistiques").' - '.$langs->trans("Nombre des participation aux Causeries par Thème - ".$login." Année ").$p_year.'</td>';
				}else{
					$result .=  '<td colspan="2">'.$langs->trans("Statistiques").' - '.$langs->trans("Nombre de Séances de Causeries par Thème - Année ".$p_year).'</td>';
				}
				$result .=  '</tr>';
				

				if ($conf->use_javascript_ajax) {
					$dataarr = array('Nombre des causeries - '.$name.' - '.implode($year));

					$result .=  '<tr>';
					$result .=  '<td align="center" colspan="2">';
					include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$mesg = $dolgraph->isGraphKo();
					
					if (!$mesg) {
						
						$dolgraph->SetData(array_values($dataseries));
						$dolgraph->SetDataColor(array('#6495ED'));
						$dolgraph->setShowLegend(1);
						$dolgraph->setShowPercent(2);
						$dolgraph->SetType(array('lines'));
						$dolgraph->setHeight('150');
						$dolgraph->setWidth('300');
						$dolgraph->setLegend(array_values($dataarr));
						$dolgraph->draw('infographcauseriebYyMonth');
						$result .=  $dolgraph->show(($total ? 0 : 1));
						$result .=  '</td>';
						$result .=  '</tr>';
					}
				}	
					
				$result .=  '<tr class="liste_total">';
				$result .=  '<td>'.$langs->trans("Total").'</td>';
				$result .=  '<td class="right">'.$total.'</td>';
				$result .=  '</tr>';

				$result .=  '</table>';
				$result .=  '</div>';
				
		}else {
			dol_print_error($db);
		}	

		return $result; 
	}

	

	/**
	 * Return an HTML table that contains a pie chart of causerie attendances by theme and goal.
	 *
	 * @param int $p_year   Year for which to retrieve the causerie data.
	 * @param int $userid   ID of the user for whom the causeries are displayed.
	 *
	 * @return string       An HTML string containing a pie chart of causerie attendances by theme.
	 */
	public function getCauserieByThemeGoalPieChart($p_year, $userid)
	{
		global $conf, $db, $langs, $user;

		if ($startyear > $endyear) {
			return -1;
		}

		$result= '';

		// if (empty($conf->causerie->enabled) || empty($user->rights->causerie->read_admin)) {
		// 	return '';
		// }
			
		$sql = "SELECT count(c.rowid) as nb_accomplished, t.label as fk_theme, ce.thme, c.date_debut as causerieyear";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as ce ON ce.fk_object = c.rowid";

		if(isset($userid) && $userid !== '-1') {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerieattendance as ca on ca.fk_causerie = c.rowid";
		}

		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON ce.thme = t.rowid";

		$sql .= " WHERE c.status > 3";
	
		if($p_year !== 'all') {
			$sql .= " AND YEAR(c.date_debut) ='".$p_year."'";
		}

		if(isset($userid) && $userid !== '-1') {
			$sql .= " AND ca.fk_user ='".$userid."'";
			$sql .= " AND ca.presence = 1";
		}
		
		$sql .= " GROUP BY ce.thme, YEAR(c.date_debut)";
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
				$i = 0;
				$dataseries = array();
				$colorseries = array();
				$listofobject = array();
				$list = array();
				$labels = array();
				$vals = array();

				while ($i < $num) {
					$obj = $db->fetch_object($resql);
				
					if ($obj) {
						if(isset($obj->fk_theme)) {
							$list[dol_print_date($obj->causerieyear, 'Y').' - '.$obj->fk_theme] = $obj->nb_accomplished;
						}
						
						$total += $obj->nb_accomplished;
						$year[dol_print_date($obj->causerieyear, 'Y')] = dol_print_date($obj->causerieyear, 'Y');
					}
					
					$i++;
	
				}

				// $list = array_count_values($list);
				ksort($year);
				ksort($list);
				foreach($list as $theme => $nb_theme) {
					$label_theme = explode(' - ', $theme);
					$dataseries[] = array($theme, $nb_theme);
				}

				$db->free($resql);
				$participant = new User($db);
				$login = '';
				if (isset($userid) && $userid !== '-1') {
					$participant->fetch($userid);
					$login = $participant->login; 
					$name = $participant->firstname.' '.$participant->lastname;
				}
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				$result = '<div class="div-table-responsive-no-min">';
				$result .= '<table class="noborder nohover centpercent">';
				$result .=  '<tr class="liste_titre">';
				if(isset($userid) && $userid !== '-1') {
					$result .=  '<td colspan="2">'.$langs->trans("Statistiques").' - '.$langs->trans("Répartition des participation aux Causeries par Thème ").' <span class="badge" title="'.$name.'" style="font-size: 0.8em; background-color: #e9f7f; color: white; padding: 3px 8px; border-radius: 12px; font-weight: bold;">'.$login.'</span> '.$langs->trans(" Année").' '.implode(' - ', $year).'</td>';
					// $result .=  '<td colspan="2">'.$langs->trans("Statistiques").' - '.$langs->trans("Répartition des participation aux Causeries par Thème - ".$login." Année ").$p_year.'</td>';
				}else{
					$result .=  '<td colspan="2">'.$langs->trans("Statistiques").' - '.$langs->trans("Répartition des Séances des Causeries par Thème - Année ".$p_year).'</td>';
				}
				
				$result .=  '</tr>';

				if ($conf->use_javascript_ajax) {

					//$result .=  '<tr>';
					$result .=  '<td align="center" colspan="6">';
					include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$mesg = $dolgraph->isGraphKo();
					
					if (!$mesg) {
						
						$dolgraph->SetData(array_values($dataseries));
						//$dolgraph->SetDataColor(array($badgeStatus9, $badgeStatus4));
						$dolgraph->setShowLegend(2);
						$dolgraph->SetType(array('pie'));
						$dolgraph->setHeight('150');
						$dolgraph->setWidth('300');
						// $dolgraph->setLegend(array_values($dataarr));
						//$dolgraph->setTitle('Partition des causeries par thèmes');
						$dolgraph->draw('infographcauseriethemeForGoal');
						$result .=  $dolgraph->show(($total ? 0 : 1));
						$result .=  '</td>';
						$result .=  '</tr>';
					}
				}	
					
				$result .=  '<tr class="liste_total">';
				$result .=  '<td>'.$langs->trans("Total").'</td>';
				$result .=  '<td class="right">'.$total.'</td>';
				$result .=  '</tr>';

				$result .=  '</table>';
				$result .=  '</div>';
			
				
		}else {
			dol_print_error($db);
		}	

		return $result; 
	}

	// Méthode provisoire pour corriger un bug sur le champ nb_accomp dans la liste des éléments de l'objectif (goalelements).
	// Cette fonction met à jour le nombre de causeries par utilisateur et par année. (A faire pour plus optimiser le code et la mise a jour du nombre de causerie sur la liste du suivi des objectifs)
	public function updateNbCauserieByUserAndYear()
	{
		global $langs;

		$this->db->begin();

		// Réinitialiser les compteurs à 0
		$sqlReset = "UPDATE ".MAIN_DB_PREFIX."sse_goalelement SET nb_accomplished = 0";
		$resqlReset = $this->db->query($sqlReset);
		if (!$resqlReset) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		// Recalculer et mettre à jour pour ceux qui ont présence = 1
		$sqlUpdate = "
			UPDATE ".MAIN_DB_PREFIX."sse_goalelement AS ge
			JOIN (
				SELECT 
					ca.fk_user,
					YEAR(c.date_debut) AS causerieyear,
					COUNT(c.rowid) AS nb_accomplished
				FROM ".MAIN_DB_PREFIX."sse_causerie AS c
				INNER JOIN ".MAIN_DB_PREFIX."sse_causerieattendance AS ca ON ca.fk_causerie = c.rowid
				WHERE c.status > 3 AND ca.presence = 1
				GROUP BY ca.fk_user, YEAR(c.date_debut)
			) AS sub
			ON ge.p_year = sub.causerieyear AND ge.fk_user = sub.fk_user
			SET ge.nb_accomplished = sub.nb_accomplished
		";

		$resqlUpdate = $this->db->query($sqlUpdate);

		if ($resqlUpdate) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class GoalElementLine. You can also remove this and generate a CRUD class for lines objects.
 */
class GoalElementLine extends CommonObjectLine
{
	// To complete with content of an object GoalElementLine
	// We should have a field rowid, fk_goalelement and position

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
