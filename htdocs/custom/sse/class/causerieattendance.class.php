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
 * \file        class/causerieattendance.class.php
 * \ingroup     sse
 * \brief       This file is a CRUD class file for CauserieAttendance (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/causerie.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for CauserieAttendance
 */
class CauserieAttendance extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'sse';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'causerieattendance';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'sse_causerieattendance';

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
	 * @var string String with name of icon for causerieattendance. Must be the part after the 'object_' into object_causerieattendance.png
	 */
	public $picto = 'fa-comments';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_UNSIGNED = 2;
	const STATUS_SIGNED = 4;
	const STATUS_CONFIRM = 6;
	const STATUS_ABSENT = 7;
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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validé', '2'=>'Non signée', '4'=>'Signée','6'=>'Présence confrimée', '7'=>'Absence', '9'=>'Annulé'), 'validate'=>'1',),
		'fk_causerie' => array('type'=>'integer:Causerie:custom/sse/class/causerie.class.php', 'label'=>'Causerie', 'enabled'=>'1', 'position'=>30, 'notnull'=>-1, 'visible'=>0,),
		'presence' => array('type'=>'varchar(255)', 'label'=>'Présence', 'enabled'=>'1', 'position'=>40, 'notnull'=>-1, 'visible'=>-2, 'css'=>'maxwidth500',),
		'reason' => array('type'=>'text', 'label'=>'Justification', 'enabled'=>'1', 'position'=>50, 'notnull'=>-1, 'visible'=>-2, 'index'=>1, 'css'=>'maxwidth500', 'cssview'=>'wordbreak',),
		'visas' => array('type'=>'varchar(255)', 'label'=>'Visas', 'enabled'=>'1', 'position'=>55, 'notnull'=>-1, 'visible'=>1, 'css'=>'maxwidth500', 'cssview'=>'wordbreak',),
		'date_signature' => array('type'=>'datetime', 'label'=>'DateSignature', 'enabled'=>'1', 'position'=>60, 'notnull'=>-1, 'visible'=>0,),
		'fk_user' => array('type'=>'integer', 'label'=>'Participant', 'enabled'=>'1', 'position'=>35, 'notnull'=>-1, 'visible'=>0,),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>65, 'notnull'=>-1, 'visible'=>0,),
		'type_abs' => array('type' => 'integer','label' => 'Type abs','enabled' => '1','position' => 170,'notnull' => 0,'visible' => -1,'index' => 1,'foreignkey' => 'societe.rowid','cssview' => 'wordbreak',),
	);

	public $id;
	public $ref;
	public $date_creation;
	public $tms;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $status;
	public $fk_causerie;
	public $presence;
	public $reason;
	public $visas;
	public $date_signature;
	public $fk_user;
	public $entity;
	public $description;
	public $date_debut;
	public $date_fin;
	public $causerie_status;
	public $causerie_ref;
	public $type_abs;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'sse_causerieattendanceline';

	/**
	 * @var string    Field with ID of parent key if this object has a parent
	 */
	public $fk_element = 'fk_causerieattendance';

	/**
	 * @var string    Name of subtable class that manage subtable lines
	 */
	public $class_element_line = 'CauserieAttendanceline';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	 *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	 */
	protected $childtablesoncascade = array('sse_causerieattendancedet');

	/**
	 * @var CauserieAttendanceLine[]     Array of subtable lines
	 */
	public $lines = array();



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
		/*if ($user->rights->sse->causerieattendance->read) {
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

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		global $conf, $user;

		// Clean parameters
		$login = trim($login);
		// Get user
		$sql = "SELECT ce.rowid, ce.status, ce.fk_causerie, ce.fk_user, c.description, c.date_debut, c.date_fin, c.status as causerie_status, c.ref as causerie_ref, c.local as causerie_local, t.label as causerie_theme,";
		$sql .= " ce.presence, ce.reason, ce.visas, ce.entity,";
		$sql .= " ce.date_creation as datec, ce.date_signature,ce.type_abs,";
		$sql .= " ce.tms as datem";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as te on te.fk_object = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerieattendance as ce ON c.rowid = ce.fk_causerie";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON t.rowid = te.thme";
		
		if ($id) {
			$sql .= " WHERE ce.rowid = ".((int) $id);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->datem);
				
				$this->status = $obj->status;
				$this->fk_causerie = $obj->fk_causerie;
				$this->fk_user = $obj->fk_user;
				$this->fk_emargement = $obj->fk_emargement;
				$this->presence = $obj->presence;
				$this->reason = $obj->reason;
				$this->visas = $obj->visas;
				$this->date_signature = $this->db->jdate($obj->date_signature);
				$this->entity = $obj->entity;
				$this->description = $obj->description;
				$this->date_debut = $obj->date_debut;
				$this->date_fin = $obj->date_fin;
				$this->causerie_status = $obj->causerie_status;
				$this->causerie_ref = $obj->causerie_ref;
				$this->causerie_local = $obj->causerie_local;
				$this->causerie_theme = $obj->causerie_theme;
				$this->type_abs = $obj->type_abs;

				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($result);
			} else {
				$this->error = "USERNOTFOUND";
				dol_syslog(get_class($this)."::fetch sse_causerieattendance not found", LOG_DEBUG);

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
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	// public function fetch($id, $ref = null)
	// {
	// 	$result = $this->fetchCommon($id, $ref);
	// 	if ($result > 0 && !empty($this->table_element_line)) {
	// 		$this->fetchLines();
	// 	}
	// 	return $result;
	// }


	/**
	 *  Return the label of the status of an causerieEmargement object
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatutCauserie($mode = 0)
	{
		$causerie = new Causerie($this->db);
		return $causerie->LibStatut($this->causerie_status, $mode);
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
		$sql .=" ORDER BY t.status ASC";
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerieattendance->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerieattendance->causerieattendance_advance->validate))))
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
				$result = $this->call_trigger('CAUSERIEATTENDANCE_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'causerieattendance/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'causerieattendance/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->sse->dir_output.'/causerieattendance/'.$oldref;
				$dirdest = $conf->sse->dir_output.'/causerieattendance/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->sse->dir_output.'/causerieattendance/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CAUSERIEATTENDANCE_UNVALIDATE');
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

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'CAUSERIEATTENDANCE_CANCEL');
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

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'CAUSERIEATTENDANCE_REOPEN');
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

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("CauserieAttendance").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/sse/causerieattendance_list.php', 1).'?card='.$this->fk_user;

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
				$label = $langs->trans("ShowCauserieAttendance");
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
		$hookmanager->initHooks(array('causerieattendancedao'));
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
	 *  Return a HTML link to the user card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpictoimg				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 *	@param	string	$option						On what the link point to ('leave', 'accountancy', 'nolink', )
	 *  @param  integer $infologin      			0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
	 *  @param	integer	$notooltip					1=Disable tooltip on picto and name
	 *  @param	int		$maxlen						Max length of visible user name
	 *  @param	int		$hidethirdpartylogo			Hide logo of thirdparty if user is external user
	 *  @param  string  $mode               		''=Show firstname and lastname, 'firstname'=Show only firstname, 'firstelselast'=Show firstname or lastname if not defined, 'login'=Show login
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getUserNomUrl($withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs, $conf, $db, $hookmanager, $user;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;
		$dol_user = new User($db);
		$dol_user->fetch($this->fk_user);

		if (!$user->hasRight('user', 'user', 'read') && $user->id != $this->id) {
			$option = 'nolink';
		}

		if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) {
			$withpictoimg = 0;
		}

		$result = ''; $label = ''; $companylink = '';

		if (!empty($this->photo)) {
			$label .= '<div class="photointooltip floatright">';
			$label .= Form::showphoto('userphoto', $this, 0, 60, 0, 'photoref photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
			$label .= '</div>';
			//$label .= '<div style="clear: both;"></div>';
		}
		// Info Login
		$label .= '<div class="centpercent">';
		$label .= img_picto('', $dol_user->picto).' <u class="paddingrightonly">'.$langs->trans("User").'</u>';
		$label .= ' '.$dol_user->getLibStatut(4);
		$label .= '<br><b>'.$langs->trans('Name').':</b> '.dol_string_nohtmltag($dol_user->getFullName($langs, ''));
		if (!empty($dol_user->login)) {
			$label .= '<br><b>'.$langs->trans('Login').':</b> '.dol_string_nohtmltag($this->login);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$dol_user->ref;

	
		//$url = dol_buildpath('/sse/causerieattendance_list.php', 1).'?card='.$this->fk_user;

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
		$company = '';
		if (!empty($this->socid)) {	// Add thirdparty for external users
			$thirdpartystatic = new Societe($db);
			$thirdpartystatic->fetch($this->socid);
			if (empty($hidethirdpartylogo)) {
				$companylink = ' '.$thirdpartystatic->getNomUrl(2, (($option == 'nolink') ? 'nolink' : '')); // picto only of company
			}
			$company = ' ('.$langs->trans("Company").': '.img_picto('', 'company').' '.dol_string_nohtmltag($thirdpartystatic->name).')';
		}
		$type = ($this->socid ? $langs->trans("ExternalUser").$company : $langs->trans("InternalUser"));
		$label .= '<br><b>'.$langs->trans("Type").':</b> '.$type;
		$label .= '</div>';
		if ($infologin > 0) {
			$label .= '<br>';
			$label .= '<br><u>'.$langs->trans("Session").'</u>';
			$label .= '<br><b>'.$langs->trans("IPAddress").'</b>: '.dol_string_nohtmltag(getUserRemoteIP());
			if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
				$label .= '<br><b>'.$langs->trans("ConnectedOnMultiCompany").':</b> '.$conf->entity.' (User entity '.$this->entity.')';
			}
			$label .= '<br><b>'.$langs->trans("AuthenticationMode").':</b> '.dol_string_nohtmltag($_SESSION["dol_authmode"].(empty($dolibarr_main_demo) ? '' : ' (demo)'));
			$label .= '<br><b>'.$langs->trans("ConnectedSince").':</b> '.dol_print_date($this->datelastlogin, "dayhour", 'tzuser');
			$label .= '<br><b>'.$langs->trans("PreviousConnexion").':</b> '.dol_print_date($this->datepreviouslogin, "dayhour", 'tzuser');
			$label .= '<br><b>'.$langs->trans("CurrentTheme").':</b> '.dol_string_nohtmltag($conf->theme);
			$label .= '<br><b>'.$langs->trans("CurrentMenuManager").':</b> '.dol_string_nohtmltag($menumanager->name);
			$s = picto_from_langcode($langs->getDefaultLang());
			$label .= '<br><b>'.$langs->trans("CurrentUserLanguage").':</b> '.dol_string_nohtmltag(($s ? $s.' ' : '').$langs->getDefaultLang());
			$label .= '<br><b>'.$langs->trans("Browser").':</b> '.dol_string_nohtmltag($conf->browser->name.($conf->browser->version ? ' '.$conf->browser->version : '').' ('.$_SERVER['HTTP_USER_AGENT'].')');
			$label .= '<br><b>'.$langs->trans("Layout").':</b> '.dol_string_nohtmltag($conf->browser->layout);
			$label .= '<br><b>'.$langs->trans("Screen").':</b> '.dol_string_nohtmltag($_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight']);
			if ($conf->browser->layout == 'phone') {
				$label .= '<br><b>'.$langs->trans("Phone").':</b> '.$langs->trans("Yes");
			}
			if (!empty($_SESSION["disablemodules"])) {
				$label .= '<br><b>'.$langs->trans("DisabledModules").':</b> <br>'.dol_string_nohtmltag(join(', ', explode(',', $_SESSION["disablemodules"])));
			}
		}
		if ($infologin < 0) {
			$label = '';
		}

		// $url = DOL_URL_ROOT.'/user/card.php?id='.$this->id;
		// if ($option == 'leave') {
		// 	$url = DOL_URL_ROOT.'/holiday/list.php?id='.$this->id;
		// }

		$url = dol_buildpath('/sse/user_causerie_info.php', 1).'?card='.$this->fk_user;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkstart = '<a href="'.$url.'"';
		$linkclose = "";
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$langs->load("users");
				$label = $langs->trans("ShowUser");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		}

		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		//if ($withpictoimg == -1) $result.='<div class="nowrap">';
		$result .= (($option == 'nolink') ? '' : $linkstart);
		if ($withpictoimg) {
			$paddafterimage = '';
			if (abs((int) $withpictoimg) == 1) {
				$paddafterimage = 'style="margin-'.($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right').': 3px;"';
			}
			// Only picto
			if ($withpictoimg > 0) {
				$picto = '<!-- picto user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip ? '' : 'class="paddingright classfortooltip"'), 0, 0, $notooltip ? 0 : 1).'</span>';
			} else {
				// Picto must be a photo
				//$picto = '<!-- picto photo user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'"'.($paddafterimage ? ' '.$paddafterimage : '').'>'.Form::showphoto('userphoto', $this, 0, 0, 0, 'userphoto'.($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1).'</span>';
				if($dol_user->gender == 'man') {
					$picto .= '<span class="nopadding userimg" style="margin-right: 3px;"><img class="photouserphoto userphoto" alt="" src="/erp/public/theme/common/user_man.png"></span>';
				}else if($dol_user->gender == 'woman'){
					$picto .= '<span class="nopadding userimg" style="margin-right: 3px;"><img class="photouserphoto userphoto" alt="" src="/erp/public/theme/common/user_woman.png"></span>';
				}else{
					$picto = '<!-- picto photo user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'"'.($paddafterimage ? ' '.$paddafterimage : '').'>'.Form::showphoto('userphoto', $this, 0, 0, 0, 'userphoto'.($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1).'</span>';
				}
			}
			$result .= $picto;
		}
		if ($withpictoimg > -2 && $withpictoimg != 2) {
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$result .= '<span class="nopadding usertext'.((!isset($this->statut) || $this->statut) ? '' : ' strikefordisabled').($morecss ? ' '.$morecss : '').'">';
			}
			if ($mode == 'login') {
				$result .= dol_string_nohtmltag(dol_trunc($this->login, $maxlen));
			} else {
				$result .= dol_string_nohtmltag($this->getFullName($langs, '', ($mode == 'firstelselast' ? 3 : ($mode == 'firstname' ? 2 : -1)), $maxlen));
			}
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$result .= '</span>';
			}
		}
		$result .= (($option == 'nolink') ? '' : $linkend);
		//if ($withpictoimg == -1) $result.='</div>';

		$result .= $companylink;

		global $action;
		$hookmanager->initHooks(array('userdao'));
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
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrlCauserie($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;
		
		//$this->fetch($this->id);

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Causerie").'</u>';
		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Causerie").'</u>';
		if (isset($this->causerie_status)) {
			$label .= ' '.$this->getLibStatutCauserie(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->causerie_ref;
		$label .= '<br><b>'.$langs->trans('Description').':</b> '.$this->description;
		$label .= '<br><b>'.$langs->trans('Animateur').':</b> '.$this->animateur;

		$url = dol_buildpath('/sse/causerie_card.php', 1).'?id='.$this->fk_causerie;
		
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
				$label = $langs->trans("ShowCauserie");
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
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->causerie_ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->causerie_ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						//$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						//$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}
				
					$result .= '</div>';
					
				} else {
					//$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->causerie_ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $causerie->label) ? $sep . dol_trunc($causerie->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('causeriedao'));
		$parameters = array('id'=>$this->fk_causerie, 'getNomUrlCauserie'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrlCauserie', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 * Function to load data from a SQL pointer into properties of current object $this
	 *
	 * @param   stdClass    $obj    Contain data of object from database
	 * @return void
	 */
	public function setJoinVarsFromFetchObj(&$obj, $object_data)
	{
		global $db;

		foreach ($object_data as $field => $info) {
			if ($this->isDate($info)) {
				if (is_null($obj->{$field}) || $obj->{$field} === '' || $obj->{$field} === '0000-00-00 00:00:00' || $obj->{$field} === '1000-01-01 00:00:00') {
					$this->{$field} = '';
				} else {
					$this->{$field} = $db->jdate($obj->{$field});
				}
			} elseif ($this->isInt($info)) {
				if ($field == 'fk_causerie') {
					$this->fk_causerie = (int) $obj->{$field};
				} else {
					if ($this->isForcedToNullIfZero($info)) {
						if (empty($obj->{$field})) {
							$this->{$field} = null;
						} else {
							$this->{$field} = (double) $obj->{$field};
						}
					} else {
						if (!is_null($obj->{$field}) || (isset($info['notnull']) && $info['notnull'] == 1)) {
							$this->{$field} = (int) $obj->{$field};
						} else {
							$this->{$field} = null;
						}
					}
				}
			} elseif ($this->isFloat($info)) {
				if ($this->isForcedToNullIfZero($info)) {
					if (empty($obj->{$field})) {
						$this->{$field} = null;
					} else {
						$this->{$field} = (double) $obj->{$field};
					}
				} else {
					if (!is_null($obj->{$field}) || (isset($info['notnull']) && $info['notnull'] == 1)) {
						$this->{$field} = (double) $obj->{$field};
					} else {
						$this->{$field} = null;
					}
				}
			} else {
				$this->{$field} = $obj->{$field};
			}
		}

		//  we force property ->causerie_status and ->causerie_ref for a better compatibility with common functions.
		$this->causerie_status = $obj->causerie_status;
		$this->causerie_ref = $obj->causerie_ref;
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
			$this->labelStatus[self::STATUS_UNSIGNED] = $langs->transnoentitiesnoconv('Non signée');
			$this->labelStatus[self::STATUS_SIGNED] = $langs->transnoentitiesnoconv('Signée');
			$this->labelStatus[self::STATUS_CONFIRM] = $langs->transnoentitiesnoconv('Présence confirmée');
			$this->labelStatus[self::STATUS_ABSENT] = $langs->transnoentitiesnoconv('Absence');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_UNSIGNED] = $langs->transnoentitiesnoconv('Non signée');
			$this->labelStatusShort[self::STATUS_SIGNED] = $langs->transnoentitiesnoconv('Signée');
			$this->labelStatusShort[self::STATUS_CONFIRM] = $langs->transnoentitiesnoconv('Présence confirmée');
			$this->labelStatusShort[self::STATUS_ABSENT] = $langs->transnoentitiesnoconv('Absence');
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

		$objectline = new CauserieAttendanceLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_causerieattendance = '.((int) $this->id)));

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

		if (empty($conf->global->SSE_CAUSERIEATTENDANCE_ADDON)) {
			$conf->global->SSE_CAUSERIEATTENDANCE_ADDON = 'mod_causerieattendance_standard';
		}

		if (!empty($conf->global->SSE_CAUSERIEATTENDANCE_ADDON)) {
			$mybool = false;

			$file = $conf->global->SSE_CAUSERIEATTENDANCE_ADDON.".php";
			$classname = $conf->global->SSE_CAUSERIEATTENDANCE_ADDON;

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
			$modele = 'standard_causerieattendance';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->CAUSERIEATTENDANCE_ADDON_PDF)) {
				$modele = $conf->global->CAUSERIEATTENDANCE_ADDON_PDF;
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
     * Met à jour la signature de présence.
     *
     * @param   string  $presence   Statut de présence (ex. : 'present', 'absent')
     * @param   string  $reason     Raison associée à la mise à jour (ex. : 'maladie', 'retard')
     * @param   int     $notrigger  1 = Ne pas exécuter les triggers, 0 = Exécuter les triggers
	 * 
     * @return  int                 < 0 en cas d'erreur, 0 si aucune action effectuée, > 0 en cas de succès
     */
    public function update_signature($presence, $reason, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// //Protection
		if ($this->status == self::STATUS_SIGNED) {
			dol_syslog(get_class($this)."::validate action abandonned: already signed", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET";
		//$sql .= " SET ref = '".$this->db->escape($num)."',";
		$sql .= " status = '".self::STATUS_SIGNED."',";
		$sql .= " presence = '$presence',";
		$sql .= " reason = '$reason'";
		
		$sql .= ", date_signature = '".$this->db->idate($now)."'";
		
		$sql .= " WHERE rowid = '".((int) $this->id)."'";

		dol_syslog(get_class($this)."::update_signature()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CAUSERIEATTENDANCE_VALIDATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			//$this->presence = $;
			$this->status = self::STATUS_SIGNED;
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
     * Confirme la signature d'un utilisateur pour une causerie donnée.
     *
     * Cette méthode met à jour le statut de signature pour un utilisateur spécifié
     * dans la table des présences, en le marquant comme ayant signé pour la causerie
     * indiquée. Elle prend en compte l'ID de la causerie, l'ID de l'utilisateur et 
     * l'entité concernée.
     *
     * @param   int  $causerieid   Identifiant de la causerie
     * @param   int  $userid       Identifiant de l'utilisateur dont la signature est confirmée
     * @param   int  $entity       Identifiant de l'entité concernée
     * @return  int                1 en cas de succès, -1 si une erreur survient
     */
    public function confirm_signature($causerieid, $userid, $entity)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		
		// //Protection
		if ($this->status == self::STATUS_CONFIRM) {
			dol_syslog(get_class($this)."::presence confirmation action abandonned: already confirmed", LOG_WARNING);
			return 0;
		}

		

		$now = dol_now();

		$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET";
			$sql .= " status =".self::STATUS_CONFIRM;
			$sql .= " ,presence = 1";
			$sql .= ", date_signature = '".$this->db->idate($now)."'";
			$sql .= " WHERE fk_causerie = '".((int) $causerieid)."'";
			$sql .= " AND fk_user ='".((int) $userid)."'";
			$sql .= " AND entity ='".((int) $entity)."'";

			dol_syslog(get_class($this)."::confirm_signature", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('SIGNATURE_CONFIRM', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

		
		// Set new ref and current status
		if (!$error) {
			//$this->presence = $;
			$this->status = self::STATUS_CONFIRM;
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
     * Confirme l'absence d'un utilisateur pour une causerie donnée.
     *
     * Cette méthode met à jour le statut d'émargement pour l'utilisateur spécifié
     * en le marquant comme "absent confirmé" dans la table des présences,
     * en fonction de l'ID de la causerie, de l'utilisateur et de l'entité.
     *
     * @param   int  $causerieid   Identifiant de la causerie
     * @param   int  $userid       Identifiant de l'utilisateur à marquer comme absent
     * @param   int  $entity       Identifiant de l'entité concernée
	 * 
     * @return  int                1 en cas de succès, -1 si une erreur survient
     */
    public function confirm_absence($causerieid, $userid, $entity)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		
		// //Protection
		if ($this->status == self::STATUS_ABSENT) {
			dol_syslog(get_class($this)."::absence confirmation action abandonned: already confirmed", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET";
			$sql .= " status =".self::STATUS_ABSENT;
			$sql .= " , presence =0";
			$sql .= " WHERE fk_causerie = '".((int) $causerieid)."'";
			$sql .= " AND fk_user ='".((int) $userid)."'";
			$sql .= " AND entity ='".((int) $entity)."'";

			dol_syslog(get_class($this)."::confirm_absence", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CAUSERIEATTENDANCE_ABSENCE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

		
		// Set current status
		if (!$error) {
			$this->status = self::STATUS_ABSENT;
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
     * Crée un nouvel enregistrement d’émargement pour une causerie donnée.
     *
     * Cette fonction initialise la date de création et le statut de l’émargement à "non signé" 
     * pour tous les participants liés à la causerie spécifiée. Elle déclenche également le trigger 
     * EMARGEMENT_CREATE si nécessaire.
     *
     * @param   int  $causerie  ID de la causerie concernée
     * @return  int             1 si succès, -1 si erreur SQL, -2 si erreur lors du déclenchement des triggers
     */
	public function createNewEmargement($causerie)
	{
		global $conf, $langs, $user;

		$error = 0;
		
		$now = dol_now();

		$this->db->begin();
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."sse_causerieattendance";
		$sql .= " SET date_creation = '".$this->db->idate($now)."',";
		$sql .= " status = '".self::STATUS_UNSIGNED."'";
		$sql .= " WHERE fk_causerie ='".((int) $causerie)."'";
		
		$result = $this->db->query($sql);
		
		if ($result) {
			if (!$error && !$notrigger) {
				$this->context = array('audit'=>$langs->trans("createNewEmargement"), 'newemargementid'=>$causerie);

			// Call trigger
			$result = $this->call_trigger('EMARGEMENT_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
			}
			if(!$error) {
				// Set new ref and current status
				$this->status = self::STATUS_UNSIGNED;
				
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::createNewEmargement ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}



 /**
     * Retourne une table HTML contenant un graphique à barres des présences aux causeries.
     * 
     * Cette méthode génère un tableau HTML avec un graphique à barres représentant
     * les présences aux causeries, avec la possibilité de filtrer les résultats
     * par une plage d'années spécifiée.
     *
     * @param   int     $year_start  L'année de début pour le filtre des causeries
     * @param   int     $year_end    L'année de fin pour le filtre des causeries
	 * 
     * @return  string                Un tableau HTML contenant un graphique à barres des présences aux causeries
     */
    public function getCauserieByThemePieChart($year_start, $year_end)
	{
		global $conf, $db, $langs, $user;

		if ($startyear > $endyear) {
			return -1;
		}

		$result= '';

		// if (empty($conf->causerie->enabled) || empty($user->rights->causerie->read_admin)) {
		// 	return '';
		// }

		$sql = "SELECT count(c.rowid) as nb_accomplished, t.label as fk_theme";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as ce ON ce.fk_object = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON ce.thme = t.rowid";
		$sql .= " WHERE c.date_debut >='".dol_print_date($year_start, '%Y-%m-%d')."'";
		$sql .= " AND c.date_debut <='".dol_print_date($year_end, '%Y-%m-%d')."'";
		$sql .= " AND c.date_fin >='".dol_print_date($year_start, '%Y-%m-%d')."'";
		$sql .= " AND c.date_fin <='".dol_print_date($year_end, '%Y-%m-%d')."'";
		$sql .= " AND c.status = 6";
		
		$sql .= " GROUP BY ce.thme";
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
						$list[$obj->fk_theme] = $obj->nb_accomplished;
						$total += $obj->nb_accomplished;
					}
					
					$i++;
	
				}

				// $list = array_count_values($list);
				// ksort($list);

				foreach($list as $label_theme => $nb_theme) {
					$dataseries[] = array($label_theme, $nb_theme);
				}

				$db->free($resql);
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
		

				if ($conf->use_javascript_ajax) {
					$dataarr = array('test');

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
						$dolgraph->setLegend(array_values($dataarr));
						$dolgraph->draw('infographcauserietheme');
						$result .=  $dolgraph->show(($total ? 0 : 1));
						$result .=  '</td>';
						$result .=  '</tr>';
					}
				}	
				$result .=  '<tr class="liste_total">';
				$result .=  '<td colspan="4">'.$langs->trans("Total").'</td>';
				$result .=  '<td class="right">'.$total.'</td>';
				$result .=  '</tr>';

				$result .=  '</table></div></form>';
			
				
		}else {
			dol_print_error($db);
		}	

		return $result; 
	}

	/**
	 * Return a HTML table that contains a bar chart of causerie attendances pieChart by Theme
	 * 
	 * @return	string				A HTML table that contains a bar chart of causerie attendances
	 */
	public function getAllCauserieByThemePieChart()
	{
		global $conf, $db, $langs, $user;

		if ($startyear > $endyear) {
			return -1;
		}

		$result= '';

		// if (empty($conf->causerie->enabled) || empty($user->rights->causerie->read_admin)) {
		// 	return '';
		// }

		$sql = "SELECT count(c.rowid) as nb_accomplished, t.label as fk_theme";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as ce ON ce.fk_object = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON ce.thme = t.rowid";
		$sql .= " WHERE c.status = 6";
		$sql .= " GROUP BY ce.thme";

		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
				$i = 0;
				$dataseries = array();
				$colorseries = array();
				$listofobject = array();
				$list = array();
				$labels = array();
	
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
				
					if ($obj) {
						$listofobject[$obj->fk_theme] = $obj->nb_accomplished;
					}
					$i++;
	
				}

				//data not entered in the database
				$list["Qualité"] = "4";
				$list["Santé"] = "29";
				$list["Sécurité"] = "193";
				$list["Environnement"] = "11";
				$list["Sureté"] = "25";
				$list["RP"] = "26";
			
				$merge = array();
				$merge[] = array($list, $listofobject);
				foreach($merge as $v1) {
					foreach($v1 as $key => $values){
						foreach($values as $key => $val) {
							$mergelist[$key] += $val;
						}
						
						
					}
				}
				
				foreach($mergelist as $label_theme => $nb_theme) {
					$dataseries[] = array($label_theme, $nb_theme);
					$total += $nb_theme;
				}

				$rand=rand(50,200);
				$color="rgb($rand,$rand,$rand)";
				$db->free($resql);
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				$result = '<div class="div-table-responsive-no-min">';
				$result .= '<table class="noborder nohover centpercent">';
				$result .=  '<tr class="liste_titre">';
				$result .=  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Répartition des Causeries par Thème").'</td>';
				$result .=  '</tr>';

				if ($conf->use_javascript_ajax) {
					$dataarr = array('test');

					//$result .=  '<tr>';
					$result .=  '<td align="center" colspan="6">';
					include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$mesg = $dolgraph->isGraphKo();
					
					if (!$mesg) {
						
						$dolgraph->SetData(array_values($dataseries));
						$dolgraph->SetDataColor(array('#6495ED', '#FB8682', '#bb1511', $badgeStatus4, '#BEBE64', '#FFBDB7'));
						$dolgraph->setShowLegend(2);
						$dolgraph->SetType(array('pie'));
						$dolgraph->setHeight('150');
						$dolgraph->setWidth('300');
						$dolgraph->setLegend(array_values($dataarr));
						$dolgraph->draw('infographallcauserietheme');
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
	 * Retourne un graphique représentant le nombre de causeries par année.
	 *
	 * Cette fonction exécute une requête SQL pour récupérer le nombre de causeries validées par année et génère un graphique à lignes 
	 * représentant ces données. Les données de l'année 2017 à 2022 sont également ajoutées (directement dans le code) pour compléter le graphique.
	 * 
	 * @return string Le code HTML contenant le tableau avec le graphique et les statistiques.
	 */
	public function getCauserieByYearChart()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT count(c.rowid) as nb_causerie, YEAR(c.date_debut) as year";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		$sql .= " WHERE c.status = 6";
		$sql .= " GROUP BY YEAR(c.date_debut)";
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
	
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
				
					if ($obj) {
						$list[$obj->year] = $obj->nb_causerie;
						$total += $obj->nb_causerie;
					}
					$i++;
				}

				// Données non saisies dans la base de données
				$list["2017"] = "07";
				$list["2018"] = "28";
				$list["2019"] = "45";
				$list["2020"] = "56";
				$list["2021"] = "87";
				$list["2022"] = "65";

				ksort($list);

				foreach($list as $year => $nb_causerie) {
					$dataseries[] = array($year, $nb_causerie);
					$total += $nb_causerie;
				}

				$db->free($resql);
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				$result = '<div class="div-table-responsive-no-min">';
				$result .= '<table class="noborder nohover centpercent">';
		  
				$result .=  '<tr class="liste_titre">';
				$result .=  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Nombre de Causeries").'</td>';
				$result .=  '</tr>';

				if ($conf->use_javascript_ajax) {
					$dataarr = array('Nombre des causeries');

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
						$dolgraph->draw('infographcauseriebYyYear');
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
	 * Retourne un graphique représentant le nombre de causeries par année.
	 *
	 * Cette fonction exécute une requête SQL pour récupérer le nombre de causeries validées par année et génère un graphique à lignes 
	 * représentant ces données. Les données de l'année 2017 à 2022 sont également ajoutées (directement dans le code) pour compléter le graphique.
	 * 
	 * @return string Le code HTML contenant le tableau avec le graphique et les statistiques.
	 */
	public function getCauserieByPresenceChart()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT count(c.rowid) as nb_accomplished, YEAR(c.date_debut) as year, COUNT(CASE WHEN c.status >= 3 THEN 1 END) as nb_users, COUNT(CASE WHEN ca.status = 6 THEN 1 END) as nb_confirmed";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerieattendance as ca on c.rowid = ca.fk_causerie";
		//$sql .= " WHERE c.status >= 3";
		$sql .= " WHERE ca.entity = 0";
		$sql .= " GROUP BY YEAR(c.date_debut)";
		$sql .= " ORDER BY YEAR(c.date_debut)";
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
				$i = 0;
				$dataseries = array();
				$colorseries = array();
				$confirmed = array();
				$year = array();
				$arr_values = array();
				$users = array();
	
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					if ($obj) { 
						$list[$obj->year] = array($obj->nb_users, $obj->nb_confirmed);
						
					}
					$i++;
				}
		  
				//data has not entered in the database yet
				$list["2017"] = array("65", "65");
				$list["2018"] = array("155", "155");
				$list["2019"] = array("335", "331");
				$list["2020"] = array("535", "474");
				$list["2021"] = array("1050", "937");
				$list["2022"] = array("997", "856");
				
				ksort($list);
				foreach($list as $key => $value) {
					$dataseries[] = array($key, $value[0], $value[1]);
				}

				$db->free($resql);
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				$result = '<div class="div-table-responsive-no-min">';
				$result .= '<table class="noborder nohover centpercent">';
				$result .=  '<tr class="liste_titre">';
				$result .=  '<td colspan="4">'.$langs->trans("Statistics").' - '.$langs->trans("Nombre de Participants").'</td>';
				$result .=  '</tr>';

				if ($conf->use_javascript_ajax) {
					$dataarr = array('Nombre des participants prévus', 'Nombre des participants réels');

					$result .=  '<tr>';
					$result .=  '<td align="center" colspan="4">';
					include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$mesg = $dolgraph->isGraphKo();
					
					if (!$mesg) {
						
						$dolgraph->SetData(array_values($dataseries));
						$dolgraph->SetDataColor(array('#ff8c66', $badgeStatus4));
						$dolgraph->setShowLegend(1);
						$dolgraph->setShowPercent(2);
						$dolgraph->SetType(array('linesnopoint', 'linesnopoint'));
						$dolgraph->setHeight('180');
						$dolgraph->setWidth('300');
						$dolgraph->setLegend(array_values($dataarr));
						$dolgraph->draw('infographcauseriebYyPrensence');
						$result .=  $dolgraph->show();
						$result .=  '</td>';
						$result .=  '</tr>';
					}
				}	
					
				$result .=  '<tr class="liste_total">';
				$result .= '<td></td>';
				$result .=  '</tr>';
			
				$result .=  '</table>';
				$result .=  '</div>';
				
		}else {
			dol_print_error($db);
		}	

		return $result; 
	}

	/**
	 * Retourne un graphique représentant les causeries par thème pour un utilisateur spécifique.
	 *
	 *
	 * @param int $userid L'ID de l'utilisateur pour lequel les causeries sont récupérées.
	 * @return string Code HTML contenant le graphique et les données correspondantes.
	 */
	public function getCauserieByThemeForUserChart($userid)
	{
		global $conf, $db, $langs, $user;

		$result= '';

		// if (empty($conf->causerie->enabled) || empty($user->rights->causerie->read_admin)) {
		// 	return '';
		// }
		
		$sql = "SELECT count(ca.fk_causerie) as nb_accomplished, t.label as fk_theme, ca.fk_causerie";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerieattendance as ca";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie as c on c.rowid = ca.fk_causerie";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields as ce ON ce.fk_object = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sse_theme as t ON ce.thme = t.rowid";
	
		$sql .= " WHERE ca.fk_user =".$userid; 
		$sql .= " AND c.status = 6";
		
		$sql .= " GROUP BY ce.thme";
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
						$list[$obj->fk_theme] = $obj->nb_accomplished;
						$total += $obj->nb_accomplished;
					}
					$i++;
				}
				
				foreach($list as $label_theme => $nb_theme) {
					$dataseries[] = array($label_theme, $nb_theme);
				}
			
				$db->free($resql);
				include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';			
				$result = '<div class="div-table-responsive-no-min">';
				$result .= '<table class="noborder nohover centpercent">';
				$result .=  '<tr class="liste_titre">';
				$result .=  '<td colspan="2">'.$langs->trans("Participation par thème").'</td>';
				$result .=  '</tr>';

				if ($conf->use_javascript_ajax) {
					$dataarr = array('Nombre des participants par thèmes');

					$result .=  '<tr>';
					$result .=  '<td align="center" colspan="6">';
					include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$mesg = $dolgraph->isGraphKo();
					
					if (!$mesg) {
						$dolgraph->SetData(array_values($dataseries));
						$dolgraph->SetDataColor(array($badgeStatus9, $badgeStatus4));
						$dolgraph->setShowLegend(1);
						$dolgraph->setShowPercent(2);
						$dolgraph->SetType(array('pie'));
						$dolgraph->setHeight('150');
						$dolgraph->setWidth('300');
						$dolgraph->setLegend(array_values($dataarr));
						$dolgraph->draw('infographcauserieElementtheme');
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
}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class CauserieAttendanceLine. You can also remove this and generate a CRUD class for lines objects.
 */
class CauserieAttendanceLine extends CommonObjectLine
{
	// To complete with content of an object CauserieAttendanceLine
	// We should have a field rowid, fk_causerieattendance and position

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
