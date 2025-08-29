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
 * \file        class/cvtec.class.php
 * \ingroup     gpeccustom
 * \brief       This file is a CRUD class file for CVTec (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/gpeccustom/class/job.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/gpeccustom/class/skill.class.php';

/**
 * Class for CVTec
 */
class CVTec extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'gpeccustom';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'cvtec';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'gpeccustom_cvtec';

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
	 * @var string String with name of icon for cvtec. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'cvtec@gpeccustom' if picto is file 'img/object_cvtec.png'.
	 */
	public $picto = 'gpec_16@gpec';


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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>5, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
		'label' => array('type'=>'varchar(255)', 'label'=>'Fichier', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>2, 'searchall'=>1, 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'showoncombobox'=>'2', 'validate'=>'1',),
		'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>1, 'alwayseditable'=>'1', 'validate'=>'1',),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'picto'=>'user', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid', 'csslist'=>'tdoverflowmax150',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'picto'=>'user', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2, 'csslist'=>'tdoverflowmax150',),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>2000, 'notnull'=>1, 'visible'=>0, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Valid&eacute;', '9'=>'Annul&eacute;'), 'validate'=>'1',),
		'fk_user' => array('type'=>'integer:User:user/class/user.class.php:statut=1', 'label'=>'Employé', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1,),
		'fk_job' => array('type'=>'varchar(255)', 'label'=>'Emploi', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>0,),
	);
	public $id;
	public $ref;
	public $label;
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
	public $fk_user;
	public $fk_job;

	public $filterArray = array(
		'skilljobuser' => array(
			'GROUP BY' => '',
			'WHERE' => '',
		),
		'skilluser' => array(
			'GROUP BY' => '',
			'WHERE' => '',
		)
	);
	// public $fk_skill;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'gpeccustom_cvtecline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_cvtec';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'CVTecline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array('mychildtable' => array('name'=>'CVTec', 'fk_element'=>'fk_cvtec'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('gpeccustom_cvtecdet');

	/**
	 * @var CVTecLine[]     Array of subtable lines
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

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('gpeccustom', 'cvtec', 'read')) {
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
		global $user;
		// Check parameters
		if (empty($id) && empty($ref)) {
			return -1;
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.tms,";
		$sql .= " t.ref,";
		$sql .= " t.date_creation,";
		$sql .= " t.status,";
		$sql .= " t.fk_user,";
		$sql .= " t.fk_job,";
		$sql .= " t.fk_skill,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX."gpeccustom_cvtec as t";
		//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."sseorganisation_test as t ON t.rowid = q.test";
		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		}
		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id  = $obj->rowid;
				$this->ref  = $obj->ref;
				$this->tms = $this->db->jdate($obj->tms);
				$this->status = $obj->status;
				$this->fk_user = $obj->fk_user;
				$this->fk_job = $obj->fk_job;
				$this->fk_skill = $obj->fk_skill;
		
				// $this->k_user_creat = $user;
				// $this->k_user_modif = $user;
				// $this->date_creation = $this->db->idate(dol_now());
				
				
				$this->fetch_optionals();
			}

			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}

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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('gpeccustom','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && !empty($user->rights->gpeccustom->cvtec->cvtec_advance->validate))))
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'cvtec/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'cvtec/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'cvtec/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'cvtec/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->gpeccustom->dir_output.'/cvtec/'.$oldref;
				$dirdest = $conf->gpeccustom->dir_output.'/cvtec/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->gpeccustom->dir_output.'/cvtec/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('gpeccustom','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('gpeccustom','gpeccustom_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'GPECCUSTOM_MYOBJECT_UNVALIDATE');
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('gpeccustom','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('gpeccustom','gpeccustom_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'GPECCUSTOM_MYOBJECT_CANCEL');
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

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('gpeccustom','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('gpeccustom','gpeccustom_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'GPECCUSTOM_MYOBJECT_REOPEN');
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
			return ['optimize' => $langs->trans("ShowCVTec")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("CVTec").'</u>';
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

		$url = dol_buildpath('/gpeccustom/cvtec_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowCVTec");
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
			//$langs->load("gpeccustom@gpeccustom");
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

		$objectline = new CVTecLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_cvtec = '.((int) $this->id)));

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
		$langs->load("gpeccustom@gpeccustom");

		if (!getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON')) {
			$conf->global->GPECCUSTOM_MYOBJECT_ADDON = 'mod_cvtec_standard';
		}

		if (getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON').".php";
			$classname = getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/gpeccustom/");

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

		$langs->load("gpeccustom@gpeccustom");

		if (!dol_strlen($modele)) {
			$modele = 'standard_cvtec';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('CVTEC_ADDON_PDF')) {
				$modele = getDolGlobalString('CVTEC_ADDON_PDF');
			}
		}

		$modelpath = "custom/gpeccustom/core/modules/gpeccustom/doc/";

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

/**
	 * have the professional background of employees
	 * 
	 * @return array of users and their jobs and skills
	 */
	public function getEvaluation($option)
	{
		global $db;
		$error = 0;
		
		$sql = "SELECT ev.fk_user as userid, ev.fk_job, ev.date_eval";
		// $sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluationdet as t";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
		$sql .= " WHERE 1 = 1";
		$sql .= " ORDER BY ev.fk_user ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$arr[$obj->userid][$obj->fk_job][$obj->date_eval] = $obj;
				}
				$i++;
			}
			foreach($arr as $userids => $data) {
				$arrprofil[$userids] = implode(',', array_keys($data));
			}
			foreach($arr as $key => $values) {
				foreach($values as $job => $vals) {
						$nbofeval[$key][$job] = sizeof($vals);
				}
			}
			// var_dump($nbofeval);
			$db->free($resql);
			if($option == 'user_evaluated') {
				return $arrprofil;
			}
			if($option == 'nb_evaluated') {
				return $nbofeval;
			}
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * have the professional background of employees
	 * A refaire 
	 * 
	 * @return array of users and their jobs and skills
	 */
	public function getUserJobs()
	{
		global $db;
		$error = 0;
		
		// $sql = "SELECT js.fk_user as userid, js.fk_job";
		// $sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as js";
		// $sql .= " WHERE 1 = 1";

		// $sql .= " ORDER BY js.fk_user ASC";
		$sql = "SELECT t.rowid,t.fk_user as userid, t.fk_job as fk_job,t.date_start,t.date_end";
		// $sql .= " ,u.lastname,u.firstname";
		$sql .= " ,j.rowid as job_id, j.label as job_label";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on t.fk_job = j.rowid";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on t.fk_user = u.rowid";
		// $sql .= ", ".MAIN_DB_PREFIX."hrm_job as j";
		// $sql .= " WHERE 1 = 1 AND t.fk_job = j.rowid";
		// $sql .= " GROUP BY t.fk_user";
		$sql .= " ORDER BY t.fk_user ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$arr[$obj->userid][$obj->fk_job] = $obj->fk_job;
				
				}
				$i++;
			}
			foreach($arr as $userids => $data) {
				// foreach($data as $jobids => $skillids) { 
					$arrprofil[$userids] = implode(',', array_keys($data));
				// }
			}
			// var_dump($arrprofil);
			$db->free($resql);
			return $arrprofil;
		} else {
			dol_print_error($db);
		}
	}

	public function getLastUserJob($uid)
	{
		global $db;
		$error = 0;
		
		
		$sql = "SELECT t.rowid as jobuserid,t.fk_user as userid, t.fk_job as fk_job,t.date_start,t.date_end";	
		$sql .= " ,j.rowid as job_id, j.label as job_label, t.description, j.description as job_description";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on t.fk_job = j.rowid";
		$sql .= " WHERE t.fk_user =".$uid;
		// $sql .= " GROUP BY t.date_start";
		$sql .= " ORDER BY t.date_start DESC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$obj->description != null ?
					$arr[$obj->fk_job][$obj->date_start][$obj->date_end] = $obj->job_label.'_'.$obj->description.'_'.$obj->jobuserid
					:
					$arr[$obj->fk_job][$obj->date_start][$obj->date_end] = $obj->job_label.'_'.$obj->job_description.'_'.$obj->jobuserid;
				}
				$i++;
			}
			
			$db->free($resql);
			return $arr;
		} else {
			dol_print_error($db);
		}
	}

	public function getLastUserEvalJob($uid, $option)
	{
		global $db;
		$error = 0;
		
		
		$sql = "SELECT t.rowid as evalid,t.fk_user as userid, t.fk_job as fk_job,t.date_eval";	
		$sql .= " ,j.rowid as job_id, j.label as job_label, t.description, j.description as job_description";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluation as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on t.fk_job = j.rowid";
		$sql .= " WHERE t.fk_user =".$uid;
		// $sql .= " GROUP BY userid, t.date_eval";
		if($option == 'last') {
			$sql .= " HAVING max(t.date_eval)";
		}
		
		$sql .= " ORDER BY t.date_eval DESC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					// var_dump($obj);
					// $obj->description != null ?
					// $arr[$obj->fk_job][$obj->date_eval] = $obj->job_label.'_'.$obj->evalid
					// :
					if($option == 'last') {
						$arr[$obj->fk_job][$obj->date_eval] = $obj->job_label.'_'.$obj->evalid;
					}elseif($option == 'all') {
						$arr[$obj->date_eval][$obj->fk_job] = $obj->job_label.'_'.$obj->evalid;
					}
					
				}
				$i++;
			}
			
			$db->free($resql);
			return $arr;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function getUsers()
	{
		dol_syslog(__METHOD__, LOG_DEBUG);
		$userscvtec = $this->getUserJobs();

		$records = array();

		$sql = "SELECT t.rowid as id";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as t";
		
		$sql .= " WHERE t.statut = '1'";
		$sql .= ' AND t.rowid NOT IN ('.implode(',', array_filter(array_keys($userscvtec))).')';
		
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);
				$records[$obj->id] = 0;
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
	 * 
	 * @param      array   getpost array of job
	 * @param      array   getpost array of skills
	 * @param      array   getpost array of levels
	 * 
	 * 
	 * 
	 * get user's job or evaluated user's job data and filtred data
	 * 
	 * 5 case for filtring 
	 * 
	 * @return array of users and their jobs and skills
	 * 
	 */
	public function getFiltredCVData2($arr_job, $arr_skill, $arr_level)
	{
		global $db;
	
		//checking if values are in arr_level getpost array
		if(is_array($arr_level) && !empty($arr_level)) { 
			foreach($arr_level as $level) {
				if($level < 0 || $level == '') {
					$is_filtrer = 0; 
				}elseif($level >= 0){
					$is_filter = 1;
				}
			}
		}
		// in_array('-1', $arr_level) || in_array('', $arr_level) ? $is_filter = false : $is_filter = true;

		//2 selects options whether user job is evaluated or not
		if(empty(array_filter($arr_level)) || !$is_filter) {
			$sql = "SELECT js.fk_user as userid, js.fk_job, t.fk_skill, t.rankorder as eval, t.required_rank as required_eval, j.label as job_label, s.rowid as skill_id, s.label as skill_label";
		}else{
			$sql = "SELECT ev.fk_user as userid, ev.fk_job, t.fk_skill, t.rankorder as eval, t.required_rank as required_eval, j.label as job_label, s.rowid as skill_id, s.label as skill_label, max(ev.date_eval)";
		}
		$sql .= "  ,sd.description as level_desc, sd.rankorder";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluationdet as t ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluation as ev on ev.rowid = t.fk_evaluation";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on ev.fk_job = j.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on t.fk_skill = s.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skillrank as hr on s.rowid = hr.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skilldet as sd on s.rowid = sd.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job_user as js on js.fk_job = hr.fk_object";
		
		$sql .= ' WHERE 1 = 1';
		// $sql .= '  AND js.fk_job = hr.fk_object';
		$sql .= " AND hr.objecttype = 'job'";
		
		//1 case (arr_skill) or 2 case (arr_skil and arr_level)
		if(!empty($arr_skill)) {
			$sql .= $this->filter_skills2($arr_skill, $arr_level);
		}

		//checking if skill's levels is specified. In this case only user evaluated jobs are filtred in list. 
		//3 cse (arr_job) or 4 case (arr_job and arr_skill) or 5 case (arr_job, arr_skill and arr_level)
		if(!empty($arr_job)) {
			empty(array_filter($arr_skill)) && !$is_filter ? $sql .= ' AND (js.fk_job IN ('.implode(', ', $arr_job).'))' 
			: $sql .= ' AND (ev.fk_job IN ('.implode(', ', $arr_job).')) ';
		}

		if(empty(array_filter($arr_level)) || !$is_filter) {
			$sql .=" GROUP BY js.fk_user, js.fk_job, t.fk_skill, t.rankorder";
		}else{
			$sql .=" GROUP BY ev.fk_user, ev.fk_job, t.fk_skill, t.rankorder";
		}
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					
					if(isset($obj->userid)) {
						$arr[$obj->userid] = $obj->userid;
					}
					
				}
				$i++;
			}
	
			$db->free($resql);
			return $arr;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * @param      array   getpost array of skills
	 * @param      array   getpost array of levels
	 * 
	 * @return     string filtred sql skills and levels
	 */
	private function filter_skills2($arr_skill, $arr_level)
	{
		if(!empty($arr_level)) {
			foreach($arr_level as $key => $level) {
				$skilllevel[$key] = $key;
			}
		}
		$i = 0;
		$sql = "";
		if(!empty($arr_skill)) {
			$sql .= " AND (";
			foreach($arr_skill as $idSkill) {
				$i++;
		
				if(!empty($arr_level)) {
					$in_levels = $idSkill == $skilllevel[$idSkill] && $arr_level[$idSkill] !== '' && $arr_level[$idSkill] >= 0;
					$off_levels = $arr_level[$idSkill] === '' || $arr_level[$idSkill] == -1;
					
					if($in_levels) {
						$sql .= ' (t.fk_skill = '.$idSkill.' AND t.rankorder >= '.$arr_level[$idSkill].' AND t.rankorder > 0)';
						
					}elseif($off_levels) {
						$sql .= ' t.fk_skill = '.$idSkill.'';
					}
				}elseif(empty(array_filter($arr_level))) {
					$sql .= ' t.fk_skill = '.$idSkill.'';
				}

				if($i < sizeof($arr_level)) {
					$sql .= ' OR';
				}
			}
			$sql .= ')';
		}
		return $sql;
	}

	public function getFiltredCVData($arr_job, $arr_skill, $arr_level)
	{
		global $db;
		
		// Vérification de l'activation du filtre par niveau
		$is_filter = !empty(array_filter($arr_level, function($level) {
			return $level >= 0;
		}));

		// Construire la requête SQL en fonction des niveaux sélectionnés
		$select = $is_filter ? 
			"SELECT ev.fk_user as userid, ev.fk_job, t.fk_skill, t.rankorder as eval, t.required_rank as required_eval, j.label as job_label, s.rowid as skill_id, s.label as skill_label, MAX(ev.date_eval)" : 
			"SELECT js.fk_user as userid, js.fk_job, t.fk_skill, t.rankorder as eval, t.required_rank as required_eval, j.label as job_label, s.rowid as skill_id, s.label as skill_label";

		// Requête SQL commune
		$sql = $select . " ,sd.description as level_desc, sd.rankorder
			FROM " . MAIN_DB_PREFIX . "hrm_evaluationdet as t 
			LEFT JOIN " . MAIN_DB_PREFIX . "hrm_evaluation as ev ON ev.rowid = t.fk_evaluation
			LEFT JOIN " . MAIN_DB_PREFIX . "hrm_job as j ON ev.fk_job = j.rowid
			LEFT JOIN " . MAIN_DB_PREFIX . "hrm_skill as s ON t.fk_skill = s.rowid
			LEFT JOIN " . MAIN_DB_PREFIX . "hrm_skillrank as hr ON s.rowid = hr.fk_skill
			LEFT JOIN " . MAIN_DB_PREFIX . "hrm_skilldet as sd ON s.rowid = sd.fk_skill
			LEFT JOIN " . MAIN_DB_PREFIX . "hrm_job_user as js ON js.fk_job = hr.fk_object
			WHERE hr.objecttype = 'job'";

		// Ajouter les filtres sur les compétences et niveaux
		if (!empty($arr_skill)) {
			$sql .= $this->filter_skills($arr_skill, $arr_level);
		}

		// Ajouter les filtres sur les métiers
		if (!empty($arr_job)) {
			$job_filter = $is_filter ? 'ev.fk_job' : 'js.fk_job';
			$sql .= ' AND ' . $job_filter . ' IN (' . implode(', ', array_map('intval', $arr_job)) . ')';
		}

		// Grouper les résultats
		$group_by = $is_filter ? 'ev.fk_user, ev.fk_job, t.fk_skill, t.rankorder' : 'js.fk_user, js.fk_job, t.fk_skill, t.rankorder';
		$sql .= " GROUP BY " . $group_by;

		// Exécuter la requête et traiter les résultats
		$resql = $db->query($sql);
		if ($resql) {
			$arr = [];
			while ($obj = $db->fetch_object($resql)) {
				if (isset($obj->userid)) {
					$arr[$obj->userid] = $obj->userid;
				}
			}
			$db->free($resql);
			return $arr;
		} else {
			dol_print_error($db);
			return [];
		}
	}

	private function filter_skills($arr_skill, $arr_level)
	{
		$sql = '';
		
		if (!empty($arr_skill)) {
			$sql .= " AND (";
			$conditions = [];

			foreach ($arr_skill as $idskill) {
				$idskill = (int)$idskill; // Sécuriser la variable pour éviter les injections SQL
				$level = $arr_level[$idskill] ?? null;

				if ($level !== null && $level >= 0) {
					$conditions[] = "(t.rankorder IS NOT NULL AND t.rankorder > 0 AND t.fk_skill = $idskill AND t.rankorder >= $level)";
				} else {
					$conditions[] = "t.fk_skill = $idskill";
				}
			}

			$sql .= implode(' OR ', $conditions) . ")";
		}
		
		return $sql;
	}

	// public function getFiltredCVData($arr_job, $arr_skill, $arr_level)
	// {
	// 	global $db;

	// 	// Initialize the filter flag
	// 	$is_filter = false;

	// 	// Check if levels array is valid and contains meaningful values
	// 	if (is_array($arr_level) && !empty($arr_level)) {
	// 		foreach ($arr_level as $level) {
	// 			if ($level >= 0) {
	// 				$is_filter = true;
	// 				break;
	// 			}
	// 		}
	// 	}

	// 	// Select query initialization
	// 	$select_fields = "js.fk_user as userid, js.fk_job, t.fk_skill, t.rankorder as eval, t.required_rank as required_eval, 
	// 					j.label as job_label, s.rowid as skill_id, s.label as skill_label, sd.description as level_desc, sd.rankorder";

	// 	if ($is_filter) {
	// 		// Include date_eval only if levels are used in filtering
	// 		$select_fields .= ", MAX(ev.date_eval)";
	// 	}

	// 	$sql = "SELECT ";
	// 	$sql .= $select_fields; 
	// 	$sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluationdet as t ";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluation as ev on ev.rowid = t.fk_evaluation";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on ev.fk_job = j.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on t.fk_skill = s.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skillrank as hr on s.rowid = hr.fk_skill";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skilldet as sd on s.rowid = sd.fk_skill";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job_user as js on js.fk_job = hr.fk_object";
	// 	$sql .= " WHERE hr.objecttype = 'job'";

	// 	// Apply skill and level filters
	// 	if (!empty($arr_skill)) {
	// 		$sql .= $this->filter_skills($arr_skill, $arr_level);
	// 	}

	// 	// Apply job filter
	// 	if (!empty($arr_job)) {
	// 		$job_column = $is_filter ? 'ev.fk_job' : 'js.fk_job';
	// 		$sql .= " AND $job_column IN (" . implode(', ', array_map('intval', $arr_job)) . ")";
	// 	}

	// 	// Group by clause to aggregate data
	// 	$group_by_column = $is_filter ? 'ev.fk_user, ev.fk_job, t.fk_skill, t.rankorder' : 'js.fk_user, js.fk_job, t.fk_skill, t.rankorder';
	// 	$sql .= " GROUP BY $group_by_column";

	// 	// Execute the query and fetch results
	// 	$resql = $db->query($sql);
	// 	if ($resql) {
	// 		$arr = [];
	// 		while ($obj = $db->fetch_object($resql)) {
	// 			if (isset($obj->userid)) {
	// 				$arr[$obj->userid] = $obj->userid;
	// 			}
	// 		}

	// 		$db->free($resql);
	// 		return $arr;
	// 	} else {
	// 		dol_print_error($db);
	// 	}
	// }

/**
 * Builds the SQL filter for skills and their respective levels.
 *
 * @param array $arr_skill Array of selected skill IDs.
 * @param array $arr_level Array of skill levels corresponding to the skills.
 * 
 * @return string SQL WHERE clause to filter by skills and levels.
 */
// private function filter_skills($arr_skill, $arr_level)
// {
//     $sql = "";
//     if (!empty($arr_skill)) {
//         $sql .= " AND (";
//         $conditions = [];

//         foreach ($arr_skill as $idSkill) {
//             if (!empty($arr_level) && isset($arr_level[$idSkill])) {
//                 $level = $arr_level[$idSkill];
//                 if ($level !== '' && $level >= 0) {
//                     $conditions[] = "(t.fk_skill = $idSkill AND t.rankorder >= $level)";
//                 } else {
//                     $conditions[] = "t.fk_skill = $idSkill";
//                 }
//             } else {
//                 $conditions[] = "t.fk_skill = $idSkill";
//             }
//         }

//         $sql .= implode(' OR ', $conditions) . ")";
//     }
//     return $sql;
// }


	/**
	 * Inserts a cvtec's users data (id user has no data, only user's name is given)
	 * 
	 *
	 * @return int <0 if KO, rowid of the line if OK
	 */
	public function setUserBackground()
	{
		$error = 0;
		$now = dol_now();
		global $user;

		//Delete all rows from tables and reset ID to zero
		$sqldel = "TRUNCATE TABLE ".MAIN_DB_PREFIX."gpeccustom_cvtec";
		$sqldelef = "TRUNCATE TABLE ".MAIN_DB_PREFIX."gpeccustom_cvtec_extrafields";

		$this->db->begin();

		dol_syslog(get_class($this)."::setUserBackground", LOG_DEBUG);
		$resqldel = $this->db->query($sqldel);
		$resqldelef = $this->db->query($sqldelef);

		if (!$resqldel) {
			$error++; $this->errors[] = "Error delete table cvtec ".$this->db->lasterror();
		}

		if (!$resqldelef) {
			$error++; $this->errors[] = "Error delete table cvtec extrafields ".$this->db->lasterror();
		}

		// Reinsert all rows from the CV data table. show user data if no data.
		$userscvtec = $this->getUserJobs();
		$usersnocv = $this->getUsers();
		
		$arr = array($userscvtec, $usersnocv);
	
		foreach($arr as $values) {
			foreach($values as $key => $value) {
				if(!empty($key)) {
					$usersArray[$key] = $value;
				}
				
			}
		}
		
		
	// 	uasort($usersArray, function($a) {
	// 		return (is_null($a) OR $a == "") ? 1 : -1;
	//    });
	 
		$this->db->begin();
		
		foreach($usersArray as $userid => $jobs) {
			
			// if($userid !== null) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."gpeccustom_cvtec (";
				$sql .= " tms,";
				$sql .= " ref,";
				$sql .= " date_creation,";
				$sql .= " status,";
				$sql .= " fk_user,";
				$sql .= " fk_job,";
				$sql .= " fk_user_creat,";
				$sql .= " fk_user_modif";
				$sql .= ") VALUES (";
				$sql .= "'".$this->db->idate($now)."'";
				$sql .= ", '".$this->db->escape('CVTEC'.'_'.$userid)."'";
				$sql .= ", '".$this->db->idate($now)."'";
				$sql .= ", '".self::STATUS_VALIDATED."'";
				
				$sql .= ", '".$this->db->escape($userid)."'";
				$sql .= ", '".$this->db->escape($jobs)."'";
				
				$sql .= ", '".$this->db->escape($user->id)."'";
				$sql .= ", '".$this->db->escape($user->id)."'";
				$sql .= ")";

				dol_syslog(get_class($this)."::settUserBackground", LOG_DEBUG);
				$resql = $this->db->query($sql);
			
			// }
			
		}
		$sqlef = "INSERT INTO ".MAIN_DB_PREFIX."gpeccustom_cvtec_extrafields (";
		$sqlef .= " fk_object, emploi)";
		$sqlef .= " SELECT c.rowid, c.fk_job";
		$sqlef .= " FROM ".MAIN_DB_PREFIX."gpeccustom_cvtec as c";
		dol_syslog(get_class($this)."::settUserBackground", LOG_DEBUG);
		$resqlef = $this->db->query($sqlef);
		
			
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'gpeccustom_cvtec');
		} else {
			$error++;
			$this->error = $this->db->lasterror();
			dol_print_error($this->db);
		}

		if ($resqlef) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'gpeccustom_cvtec_extrafields');
		} else {
			$error++;
			$this->error = $this->db->lasterror();
			dol_print_error($this->db);
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CVTEC_SETUSERBACKGROUND', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}


		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 * 
	 * @return array list of skills
	 */
	public function getSkills($date_start, $date_end, $arr_jobs)
	{
		global $db;
		$date = array('start' => $date_start, 'end' => $date_end);
		$error = 0;
		$jobskills = array();
		$sql = "SELECT t.label,t.rowid as skillid, t.date_creation, count(t.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_skill as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skillrank as sr on sr.fk_skill = t.rowid";
		$sql .= " WHERE 1 = 1";
		if(!empty(array_filter($date))) {
			$sql .= ' AND t.date_creation BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
		}


		if(!empty($arr_jobs)) {
			$sql .= ' AND sr.fk_object IN (' . implode(',', $arr_jobs) . ')';
		}
		$sql .= " GROUP BY DATE_FORMAT(t.date_creation ,'%Y-%m')";
		$sql .= " ORDER BY t.date_creation DESC";
		
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
	 * 
	 * @return array list of skills
	 */
	public function getarrSkills()
	{
		global $db;

		$error = 0;
		$jobskills = array();
		$sql = "SELECT t.label,t.rowid as skillid, t.date_creation";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_skill as t";
		$sql .= " WHERE 1 = 1";
		
	
		$sql .= " ORDER BY t.date_creation DESC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$skills[$obj->skillid] = $obj->label;
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
	 * 
	 * @return array list of user's skills and skill's rank
	 * 
	 * @return array of data 
	 */
	public function getSkillsRank($arr_skill, $option)
	{
		global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT u.rowid as userid,t.label,t.rowid as skillid, sd.description as skill_level, sd.rankorder,s.date_creation,sd.fk_skill";
		// $sql .= "DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,";
		// $sql .= " ef.orientation as options_orientation, ef.catgorie as options_catgorie, ef.piatdpn as options_piatdpn";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_skill as t";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill_extrafields as ef on t.rowid = ef.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skillrank as hr on t.rowid = hr.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skilldet as sd on t.rowid = sd.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on s.rowid = sd.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job_user as js on js.fk_job = hr.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on js.fk_user = u.rowid";
		$sql .= " WHERE 1 = 1";
		$sql .= " AND hr.objecttype = 'job'";
		if($option == 'skill_users') {
			if(!empty($arr_skill)) {
				$sql .= ' AND t.rowid IN ('.implode(',', $arr_skill).')';
			}
		}
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
	 * 
	 * 
	 * @return array list of user's job's skills and levles
	 */
	public function getJobSkills($arr_job, $arr_skill, $option)
	{
		global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT t.rowid, t.fk_user, t.fk_job, j.label as job_label, t.description, DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,";
		// $sql .= " AVG(det.rankorder) as avrgnote,ev.date_eval,";
		$sql .= " det.fk_skill as skillid, sd.description as skill_level, sd.rankorder,s.label as skill_label,j.date_creation";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as t";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on t.fk_user = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluation as ev on t.fk_job = ev.fk_job and t.fk_user = ev.fk_user";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on j.rowid = t.fk_job";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as det on det.fk_evaluation = ev.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skilldet as sd on det.fk_skill = sd.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on s.rowid = sd.fk_skill";
		$sql .= " WHERE 1 = 1";

		if(!empty($arr_job) && $option == 'job_user_skills') {
			$sql .= ' AND t.fk_job IN ('.implode(',', $arr_job).')';
		}
		if(!empty($arr_skill) && !empty($arr_job) && $option == 'skills_after_filter') {
			$sql .= ' AND det.fk_skill IN ('.implode(',', $arr_skill).')';
		}
		
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					if(isset($obj->fk_user)) {
						// $arrjs[$obj->fk_job][$obj->skillid][$obj->rankorder][$obj->date_end] = $obj;
						$jobskills[] = $obj;
					}
				}
				$i++;
			}
			$db->free($resql);
			
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 *	Show a multiselect form from an array. WARNING: Use this only for short lists.
	 *
	 *	@return	string						HTML multiselect string
	 */
	public function customMultiselectarray($htmlname, $array, $morecss = '', $addjscombo = 1)
	{
		global $conf, $langs;

		$out = '';

		// Try also magic suggest
		$out .= '<select id="'.$htmlname.'" class="multiselect'.($morecss ? ' '.$morecss : '').'" multiple name="'.$htmlname.'[]"'.($moreattrib ? ' '.$moreattrib : '').($width ? ' style="width: '.(preg_match('/%/', $width) ? $width : $width.'px').'"' : '').'>'."\n";
		if (is_array($array) && !empty($array)) {
			if ($value_as_key) {
				$array = array_combine($array, $array);
			}

			if (!empty($array)) {
				foreach ($array as $key => $value) {
					$newval = ($translate ? $langs->trans($value) : $value);
					$newval = ($key_in_label ? $key.' - '.$newval : $newval);

					$out .= '<option value=""';
					if (is_array($selected) && !empty($selected) && in_array((string) $key, $selected) && ((string) $key != '')) {
						$out .= ' selected';
					}
					$out .= ' data-html="'.dol_escape_htmltag($newval).'"';
					$out .= '>';
					$out .= dol_htmlentitiesbr($newval);
					$out .= '</option>'."\n";
				}
			}
		}
		$out .= '</select>'."\n";

		// Add code for jquery to use multiselect
		if (!empty($conf->use_javascript_ajax) && !empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')) {
			$out .= "\n".'<!-- JS CODE TO ENABLE select for id '.$htmlname.', addjscombo='.$addjscombo.' -->';
			$out .= "\n".'<script>'."\n";
			if ($addjscombo == 1) {
				$tmpplugin = empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) ?constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
				$out .= 'function formatResult(record) {'."\n";
				if ($elemtype == 'category') {
					$out .= 'return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png"> \'+record.text+\'</span>\';';
				} else {
					$out .= 'return record.text;';
				}
				$out .= '};'."\n";
				$out .= 'function formatSelection(record) {'."\n";
				if ($elemtype == 'category') {
					$out .= 'return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png"> \'+record.text+\'</span>\';';
				} else {
					$out .= 'return record.text;';
				}
				$out .= '};'."\n";
				$out .= '$(document).ready(function () {
							$(\'#'.$htmlname.'\').'.$tmpplugin.'({
								dir: \'ltr\',
								// Specify format function for dropdown item
								formatResult: formatResult,
							 	templateResult: formatResult,		/* For 4.0 */
								// Specify format function for selected item
								formatSelection: formatSelection,
							 	templateSelection: formatSelection		/* For 4.0 */
							});

							/* Add also morecss to the css .select2 that is after the #htmlname, for component that are show dynamically after load, because select2 set
								 the size only if component is not hidden by default on load */
							$(\'#'.$htmlname.' + .select2\').addClass(\''.$morecss.'\');
						});'."\n";
			} elseif ($addjscombo == 2 && !defined('DISABLE_MULTISELECT')) {
				// Add other js lib
				// TODO external lib multiselect/jquery.multi-select.js must have been loaded to use this multiselect plugin
				// ...
				$out .= 'console.log(\'addjscombo=2 for htmlname='.$htmlname.'\');';
				$out .= '$(document).ready(function () {
							$(\'#'.$htmlname.'\').multiSelect({
								containerHTML: \'<div class="multi-select-container">\',
								menuHTML: \'<div class="multi-select-menu">\',
								buttonHTML: \'<span class="multi-select-button '.$morecss.'">\',
								menuItemHTML: \'<label class="multi-select-menuitem">\',
								activeClass: \'multi-select-container--open\',
								noneText: \''.$placeholder.'\'
							});
						})';
			}
			$out .= '</script>';
		}

		return $out;
	}

		/**
	 * 
	 * 
	 * @return array list of user's job's skills and levles
	 */
	public function getJob()
	{
		global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT t.rowid, t.fk_user, t.fk_job, j.label as job_label, t.description, DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,";
		// $sql .= " AVG(det.rankorder) as avrgnote,ev.date_eval,";
		$sql .= " det.fk_skill as skillid, sd.description as skill_level, sd.rankorder,s.label as skill_label,j.date_creation";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as t";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on t.fk_user = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluation as ev on t.fk_job = ev.fk_job and t.fk_user = ev.fk_user";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on j.rowid = t.fk_job";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as det on det.fk_evaluation = ev.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skilldet as sd on det.fk_skill = sd.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on s.rowid = sd.fk_skill";
		$sql .= " WHERE 1 = 1";
		
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					if(isset($obj->fk_user)) {
						// $arrjs[$obj->fk_job][$obj->skillid][$obj->rankorder][$obj->date_end] = $obj;
						$jobskills[] = $obj;
					}
				}
				$i++;
			}
			$db->free($resql);
			
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	// 	/**
	//  * 
	//  * 
	//  * @return array list of user's job's skills and levles
	//  */
	public function getJob2($date_start, $date_end, $arr_jobs)
	{
		global $db;
		$error = 0;
		$date = array('start' => $date_start, 'end' => $date_end);
	
		$jobskills = array();
		$sql = "SELECT t.rowid, t.fk_user, t.fk_job, j.label as job_label, t.description, DATE_FORMAT(t.date_start ,'%Y-%m-%d') as date_start, DATE_FORMAT(t.date_end ,'%Y-%m-%d') as date_end,j.date_creation";
		// $sql .= " AVG(det.rankorder) as avrgnote,ev.date_eval,";
		// $sql .= " det.fk_skill as skillid, sd.description as skill_level, sd.rankorder,s.label as skill_label,j.date_creation";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as t";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on t.fk_user = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluation as ev on t.fk_job = ev.fk_job and t.fk_user = ev.fk_user";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on j.rowid = t.fk_job";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as det on det.fk_evaluation = ev.rowid";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skilldet as sd on det.fk_skill = sd.fk_skill";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on s.rowid = sd.fk_skill";
		$sql .= " WHERE 1 = 1";
		if(!empty($date)) {
			$sql .= ' AND t.date_creation BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
		}
		if(!empty($arr_jobs)) {
			$sql .= ' AND t.fk_job IN (' . implode(',', $arr_jobs) . ')';
		}
		
		// $sql .= " ORDER BY date_start";
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					if(isset($obj->fk_user)) {
						// $arrjs[$obj->fk_job][$obj->skillid][$obj->rankorder][$obj->date_end] = $obj;
						$jobskills[] = $obj;
					}
				}
				$i++;
			}
			$db->free($resql);
			// var_dump($jobskills);
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}
// 	public function getJob2($date_start, $date_end)
// {
//     global $db;
//     $error = 0;
//     $date = array('start' => $date_start, 'end' => $date_end);
    
//     $jobskills = array();

//     // Modifié pour grouper par mois et compter le nombre d'emplois
//     $sql = "SELECT 
//                 DATE_FORMAT(t.date_start, '%Y-%m') AS month, 
//                 COUNT(t.rowid) AS job_count
//             FROM ".MAIN_DB_PREFIX."hrm_job_user AS t
//             LEFT JOIN ".MAIN_DB_PREFIX."hrm_job AS j ON j.rowid = t.fk_job
//             WHERE 1 = 1";

//     if(!empty($date)) {
//         $sql .= ' AND t.date_start BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
//     }

//     $sql .= " GROUP BY month ORDER BY month"; // Grouper par mois et trier par mois

//     $resql = $db->query($sql);

//     if ($resql) {
//         $num = $db->num_rows($resql);
//         $i = 0;
//         while ($i < $num) {
//             $obj = $db->fetch_object($resql);
//             if ($obj) {
//                 // Ajout des résultats dans le tableau jobskills
//                 $jobskills[] = array(
//                     'month' => $obj->month,
//                     'job_count' => $obj->job_count
//                 );
//             }
//             $i++;
//         }
//         $db->free($resql);
//         return $jobskills;
//     } else {
//         dol_print_error($db);
//     }
// }

	/**
	 * 
	 * @return array list of user jobs 
	 */
	public function getAvrSkillJobs($filter_fk_user)
	{
		//parameters date, job, users (where) 
		global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT ev.fk_user, ev.fk_job,j.label as job_label, s.label as skill_label, s.rowid as fk_skill,";
		$sql .= " AVG(t.rankorder) as avrgnote,ev.date_eval,count(t.fk_skill) as nbskill,t.rankorder";
		// $sql .= " t.fk_skill as skillid, sd.description as skill_level, sd.rankorder,s.label as skill_label";
		// $sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluationdet as t";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on t.fk_user = u.rowid";
		$sql .= "  FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as t on t.fk_evaluation = ev.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on j.rowid = ev.fk_job";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as det on det.fk_evaluation = ev.rowid";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skilldet as sd on t.fk_skill = sd.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on s.rowid = t.fk_skill";
		$sql .= " WHERE t.rankorder > 0";
		if($filter_fk_user != -1 && $filter_fk_user != '') {
			$sql .= " AND ev.fk_user = ".$filter_fk_user;
		}
		$sql .= " GROUP BY ev.fk_job";
		// if($option == 'allaverage') {
		// 	//if all jobs and no date only fk_job delete DATE_FORMAT(ev.date_eval, '%Y')
		// 	//if all jobs and date filter fk_job and date
		// 	$sql .= " GROUP BY ev.fk_job,DATE_FORMAT(ev.date_eval, '%Y')";
		
		// }
		// elseif($option == 'allavgnodate') {
		// 	//if user and no date and no job 
		// 	//if user and job and no date 
		// 	//if user and job and date 
		// 	$sql .= " GROUP BY ev.fk_job";

		// }
		// elseif($option == 'useraverage') {
		// 	//if user and no date and no job 
		// 	//if user and job and no date 
		// 	//if user and job and date 
		// 	$sql .= " GROUP BY ev.fk_user,ev.fk_job,DATE_FORMAT(ev.date_eval, '%Y')";

		// }
		
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					if(isset($obj->fk_user)) {
						// $arrjs[$obj->fk_job][$obj->skillid][$obj->rankorder][$obj->date_end] = $obj;
						$jobskills[] = $obj;
						// var_dump($obj);
					}
				}
				$i++;
			}
			$db->free($resql);
			
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	public function getskillJobAvgGraph($arr_skill_jobs, $filter_fk_user, $date_start, $date_end)
	{
		global $db;
		$sqlfilters = $this->filterArray;
		$date = array('start' => $date_start, 'end' => $date_end);
		$case1 =  !empty(array_filter($date)) && !empty($arr_skill_jobs) && $filter_fk_user > 0;
		$case2 =  !empty(array_filter($date)) && empty($arr_skill_jobs) && $filter_fk_user > 0;
		$case3 = !empty(array_filter($date)) && empty($arr_skill_jobs) && ($filter_fk_user == -1 || $filter_fk_user == '');
		$case4 = !empty(array_filter($date)) && !empty($arr_skill_jobs) && ($filter_fk_user == -1 || $filter_fk_user == '');
		$case5 = empty(array_filter($date)) && empty($arr_skill_jobs) && $filter_fk_user > 0;

		switch (true) {
			case $case1:
				$sqlfilters['skilljobuser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$filter_fk_user.' and ev.fk_job IN('.implode(',', $arr_skill_jobs).') AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
				$sqlfilters['skilljobuser']['GROUP BY'] = 'fk_user,fk_job,ev.date_eval';
				break;
			case $case2:
				$sqlfilters['skilljobuser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$filter_fk_user.' AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
				$sqlfilters['skilljobuser']['GROUP BY'] = 'fk_user,fk_job,ev.date_eval';
				break;
			case $case3:
				$sqlfilters['skilljobuser']['WHERE'] = 't.rankorder > 0  AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
				$sqlfilters['skilljobuser']['GROUP BY'] = 'fk_job';
				break;
			case $case4:
				$sqlfilters['skilljobuser']['WHERE'] = 't.rankorder > 0 AND ev.fk_job IN('.implode(',', $arr_skill_jobs).') AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
				$sqlfilters['skilljobuser']['GROUP BY'] = 'fk_job,ev.date_eval';
				break;
			case $case5:
				$sqlfilters['skilljobuser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$filter_fk_user.'';
				$sqlfilters['skilljobuser']['GROUP BY'] = 'fk_user,fk_job,ev.date_eval';
				break;
		}
		// $sqlfilters['skilljobuser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$filter_fk_user.' and ev.fk_job IN('.implode(',', $arr_skill_jobs).') AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
		// $sqlfilters['skilljobuser']['GROUP BY'] = 'ev.fk_job';
		// var_dump($sqlfilters);

		// var_dump($case1);
		$error = 0;
		$jobskills = array();
		$sql = "SELECT ev.fk_user, ev.fk_job,j.label as job_label,";
		$sql .= " AVG(t.rankorder) as avrgnote,ev.date_eval,count(t.fk_skill) as nbskill,t.rankorder";
		$sql .= "  FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as t on t.fk_evaluation = ev.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_job as j on j.rowid = ev.fk_job";
		if($sqlfilters['skilljobuser']['WHERE'] !== '') {
			$sql .= " WHERE ".$sqlfilters['skilljobuser']['WHERE'];
		}
		if($sqlfilters['skilljobuser']['GROUP BY'] !== '') {
			$sql .= " GROUP BY ".$sqlfilters['skilljobuser']['GROUP BY'];
		}else{
			$sql .= " GROUP BY ev.fk_job";
		}

		
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					if(isset($obj->fk_user)) {
						$jobskills[] = $obj;
						
					}
				}
				$i++;
			}
			$db->free($resql);
			
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * 
	 * @return array list of users's skill
	 */
	public function getAvrSkill($skill_fk_user)
	{
		global $db;
		$error = 0;
		$jobskills = array();
		$sql = "SELECT ev.fk_user, t.fk_skill,s.label as skill_label,";
		$sql .= " AVG(t.rankorder) as avgskillnote,ev.date_eval,count(t.fk_skill) as nbskill,t.rankorder, se.catgorie as domaine";
		$sql .= "  FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as t on t.fk_evaluation = ev.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on s.rowid = t.fk_skill";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill_extrafields as se on s.rowid = se.fk_object";
		
		$sql .= " WHERE t.rankorder > 0";
		if($skill_fk_user != -1 && $skill_fk_user != '') {
			$sql .= " AND ev.fk_user = ".$skill_fk_user;
		}
		$sql .= " GROUP BY t.fk_skill";
		// if($option == 'allaverage') {
		// 	//if all jobs and no date only fk_job delete DATE_FORMAT(ev.date_eval, '%Y')
		// 	//if all jobs and date filter fk_job and date
		// 	$sql .= " GROUP BY ev.fk_job,DATE_FORMAT(ev.date_eval, '%Y')";
		
		// }
		// elseif($option == 'allavgnodate') {
		// 	//if user and no date and no job 
		// 	//if user and job and no date 
		// 	//if user and job and date 
		// 	$sql .= " GROUP BY ev.fk_job";

		// }
		// elseif($option == 'useraverage') {
		// 	//if user and no date and no job 
		// 	//if user and job and no date 
		// 	//if user and job and date 
		// 	$sql .= " GROUP BY ev.fk_user,ev.fk_job,DATE_FORMAT(ev.date_eval, '%Y')";

		// }
		
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					if(isset($obj->fk_user)) {
						// $arrjs[$obj->fk_job][$obj->skillid][$obj->rankorder][$obj->date_end] = $obj;
						$jobskills[] = $obj;
						// var_dump($obj);
					}
				}
				$i++;
			}
			$db->free($resql);
			
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * 
	 * @param array arr_skill
	 * @param int skil fk_user
	 * @param date start date 
	 * @param date start end
	 * 
	 * 
	 * @return array of filtred user's skills jobs data
	 * 
	 */
	public function getskillAvgGraph($arr_skill, $skill_fk_user, $date_skill_start, $date_skill_end)
	{
		global $db;
		$sqlfilters = $this->filterArray;
		$date = array('start' => $date_skill_start, 'end' => $date_skill_end);
		$case1 =  !empty(array_filter($date)) && !empty($arr_skill) && $skill_fk_user > 0;
		$case2 =  !empty(array_filter($date)) && empty($arr_skill) && $skill_fk_user > 0;
		$case3 = !empty(array_filter($date)) && empty($arr_skill) && ($skill_fk_user == -1 && $skill_fk_user == '');
		// $case4 = !empty($date) && !empty($arr_skill) && ($skill_fk_user == -1 && $skill_fk_user == '');
		$case5 = empty(array_filter($date)) && empty($arr_skill) && $skill_fk_user > 0;
	
		switch (true) {
			case $case1:
				$sqlfilters['skilluser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$skill_fk_user.' and t.fk_skill IN('.implode(',', $arr_skill).') AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
				$sqlfilters['skilluser']['GROUP BY'] = 'ev.fk_user,t.fk_skill,ev.date_eval';
				break;
			case $case2:
				$sqlfilters['skilluser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$skill_fk_user.' AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
				$sqlfilters['skilluser']['GROUP BY'] = 'ev.fk_user,t.fk_skill,ev.date_eval';
				break;
			case $case3:
				$sqlfilters['skilluser']['WHERE'] = 't.rankorder > 0  AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
				$sqlfilters['skilluser']['GROUP BY'] = 't.fk_skill';
				break;
			// case $case4:
			// 	$sqlfilters['skilluser']['WHERE'] = 't.rankorder > 0 AND t.fk_skill IN('.implode(',', $arr_skill_jobs).') AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
			// 	$sqlfilters['skilluser']['GROUP BY'] = 't.fk_skill,ev.date_eval';
			// 	break;
			case $case5:
				$sqlfilters['skilluser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$skill_fk_user.'';
				$sqlfilters['skilluser']['GROUP BY'] = 'ev.fk_user,t.fk_skill';
				break;
		}
		// $sqlfilters['skilljobuser']['WHERE'] = 't.rankorder > 0 AND ev.fk_user = '.$filter_fk_user.' and ev.fk_job IN('.implode(',', $arr_skill_jobs).') AND ev.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
		// var_dump($sqlfilters['skilluser']);
		$error = 0;
		$jobskills = array();
		$sql = "SELECT ev.fk_user, t.fk_skill,s.label as skill_label,";
		$sql .= " AVG(t.rankorder) as avgskillnote,ev.date_eval,count(t.fk_skill) as nbskill,t.rankorder";
		$sql .= "  FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as t on t.fk_evaluation = ev.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on s.rowid = t.fk_skill";
		if($sqlfilters['skilluser']['WHERE'] !== '') {
			$sql .= " WHERE ".$sqlfilters['skilluser']['WHERE'];
		}
		if($sqlfilters['skilluser']['GROUP BY'] !== '') {
			$sql .= " GROUP BY ".$sqlfilters['skilluser']['GROUP BY'];
		}else{
			$sql .= " GROUP BY t.fk_skill";
		}

		
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					if(isset($obj->fk_user)) {
						$jobskills[] = $obj;
						
					}
				}
				$i++;
			}
			$db->free($resql);
			
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * 
	 * @param string           option for evaluated users and not evaluated
	 * @param string           getpost nbusers_fk_user
	 * @param array            $arr_agence
	 * @param date             $date_start
	 * @param date             $date_end 
	 * 
	 * 
	 * 
	 * @return array list of evaluation 
	 * 
	 */
	public function getNbEvaluation($option, $nbusers_fk_user, $arr_jobs, $date_start, $date_end)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		global $db, $conf, $langs;
		$error = 0;
		$now = dol_now();
		$userinlist = array();
		$date = array('start' => $date_start, 'end' => $date_end);

		$period = new DatePeriod(
			new DateTime('2018-01'),
			new DateInterval('P1M'),
			new DateTime(dol_print_date($now, 'Y-m'))
	   );

		if($option == 'nb_eval_users') {
			$sql = "SELECT t.fk_user, DATE_FORMAT(t.date_eval ,'%Y') as date_eval";
			$sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluation as t";
			if(!empty($date)) {
				$sql .= ' WHERE t.date_eval BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
			}
			if(!empty($arr_jobs)) {
				$sql .= ' AND t.fk_job IN (' . implode(',', $arr_jobs) . ')';
			}
			
			if($nbusers_fk_user != '' && $nbusers_fk_user != -1) {
				$sql .= " AND t.fk_user =".$nbusers_fk_user;
			}
			$sql .= " ORDER BY date_eval";
		}
		if($option == 'nb_users') {
			$sql = "SELECT u.rowid, DATE_FORMAT(u.dateemployment,'%Y-%m') as dm_start, DATE_FORMAT(u.dateemploymentend,'%Y-%m') as dm_end";
			
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			if(!empty($date)) {
				$sql .= ' WHERE u.dateemployment BETWEEN "'.$db->idate($date['start']).'" AND "'.$db->idate($date['end']).'"';
			}
		}
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				
				if ($obj) {
					if($option == 'nb_eval_users') {
						$nbevalusers[$obj->date_eval] += count($obj->fk_user);
					}
					if($option == 'nb_users') {
						if(!isset($obj->dm_end) && isset($obj->dm_start)) {
							//where dm_end is null   --- date now(), user belong to list from dm_start until now()
							//add all previous values when date start == date for each month until now() 
							foreach ($period as $key => $value) {
								$value->format('Y-m') == $obj->dm_start ? $arr[$value->format('Y')] += count($obj->rowid) : null;  
							}
						}elseif(isset($obj->dm_start)) {
							//dm_end is not null --- user belong to list from dm_start until dm_end
							foreach ($period as $key => $value) {
								$date_end = new DateTime($obj->dm_end);
								$value->format('Y-m') == $obj->dm_start && $date_end < $value ? $arr[$value->format('Y')] += count($obj->rowid) : null; 
							}
						}
					}
					
				}
				$i++;
			}

			$db->free($resql);
			if($option == 'nb_eval_users') {
				return $nbevalusers;
			}
			if($option == 'nb_users') {
				//sum of previous values 
				$keys = array_keys($arr);
				$arr = array_values($arr);

				$nbusers = array();

				foreach ($arr as $key=>$val) {
					$nbusers[] = array_sum(array_slice($arr, 0, $key+1));
				}
				$nbusers = array_combine($keys, $nbusers);
	
				return $nbusers;
			}
		} else {
			dol_print_error($db);
		}
	}

		/**
	 * 
	 */
	public function getJobEvaluated()
	{
		global $db;
		$sql = "SELECT ev.fk_user, ev.fk_job,";
		$sql .= " ev.date_eval";
		$sql .= "  FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
		$sql .= " GROUP BY ev.fk_user, ev.fk_job";

		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$jobskills[$obj->fk_user][$obj->fk_job] = $obj;
				 }
				
				$i++;
			}
			$db->free($resql);
			
			return $jobskills;
		} else {
			dol_print_error($db);
		}
	}

	

	/**
	 * @param int         getpost id user skill_user
	 * @param date       date_debut
	 * @param date       date_end
	 * @param array       arr_skill
	 * @param string      option 
	 * 
	 * @return array      according to option nb_skill, nb_validate, label
	 * 
	 */
	public function getSkillEvaluated($skill_user, $arr_skill, $date_start, $date_end, $arr_skilljobs, $option, $modeselect)
	{
		global $db;

		$date = array('start' => $date_start, 'end' => $date_end);

		$case1 = !empty($arr_skill) && !empty(array_filter($date)) && ($skill_user > 0);
		$case2 = !empty(array_filter($date)) &&  ($skill_user > 0) && empty($arr_skill);
		$case3 = !empty(array_filter($date)) && empty($arr_skill) && ($skill_user == -1 || $skill_user == '');
		$case4 = !empty($arr_skill) && empty(array_filter($date)) && ($skill_user == -1 || $skill_user == '');
		$case5 = !empty($arr_skill) && empty(array_filter($date)) && ($skill_user > 0);
		$case6 = empty($arr_skill) && empty(array_filter($date)) && ($skill_user > 0);
		

		switch (true) {
			case $case1:
				$wheresql = ' AND t.fk_skill IN ('.implode(',', $arr_skill).')';
				if($modeselect == 'on_evaluation') {
					$wheresql .= ' AND (ev.date_eval BETWEEN '.$date['start'].' AND '.$date['end'].' )';
				}
				if($modeselect == 'off_evaluation') {
					// $wheresql .= 'AND (js.date_start BETWEEN '.$date['start'].' AND '.$date['end'].' )';
					$wheresql .= ' AND (js.date_end <= '.$date['end'].' ';
					$wheresql .= ' OR (js.date_start BETWEEN '.$date['start'].' AND '.$date['end'].' ) AND js.date_end IS NULL)';
				}
				
				break;
			case $case2:
				
				if($modeselect == 'on_evaluation') {
					$wheresql = ' AND (ev.fk_user = '.$skill_user.')';
					$wheresql .= ' AND (ev.date_eval BETWEEN '.$date['start'].' AND '.$date['end'].' )';
				}
				if($modeselect == 'off_evaluation') {
					$wheresql .= ' AND (js.date_end <= '.$date['end'].' ';
					$wheresql .= ' OR js.date_start BETWEEN '.$date['start'].' AND '.$date['end'].' AND js.date_end IS NULL)';
				}
				
				break;
			case $case3:
				if($modeselect == 'on_evaluation') {
					$wheresql .= ' AND (ev.date_eval >= '.dol_print_date($date['start'], '%Y-%m-%d').' AND ev.date_eval <= '.dol_print_date($date['end'], '%Y-%m-%d').' )';
				}
				if($modeselect == 'off_evaluation') {
					$wheresql .= ' AND (js.date_end <= '.$date['end'].' )';
					$wheresql .= ' OR (js.date_start BETWEEN '.$date['start'].' AND '.$date['end'].' AND js.date_end IS NULL)';
				}
				
				break;
			case $case4:
				$wheresql = ' AND t.fk_skill IN ('.implode(',', $arr_skill).')';
				
				break;
			// case $case5:
			// 	$wheresql = ' AND t.fk_skill IN ('.implode(',', $arr_skill).')';
			// 	if($modeselect == 'on_evaluation') {
			// 		$wheresql .= ' AND (ev.date_eval BETWEEN '.$date['start'].' AND '.$date['end'].' )';
			// 	}
			// 	if($modeselect == 'off_evaluation') {
			// 		$wheresql .= ' AND (js.date_end <= '.$date['end'].' ';
			// 		$wheresql .= ' OR js.date_start BETWEEN '.$date['start'].' AND '.$date['end'].' AND js.date_end IS NULL)';
			// 	}
			// 	break;
			case $case5:
				if($modeselect == 'on_evaluation') {
				$wheresql = ' AND (ev.fk_user = '.$skill_user.')';
				$wheresql .= ' AND t.fk_skill IN ('.implode(',', $arr_skill).')';
			}
			if($modeselect == 'off_evaluation') {
				$wheresql = ' AND (js.fk_user = '.$skill_user.')';
				// $wheresql = ' AND 1 > 1';
			}
				break;
			case $case6:
				if($modeselect == 'on_evaluation') {
					$wheresql = ' AND (ev.fk_user = '.$skill_user.')';
				}
				if($modeselect == 'off_evaluation') {
					$wheresql = ' AND (js.fk_user = '.$skill_user.')';
					// $wheresql = ' AND 1 > 1';
				}
				break;
		}
		
		
		if($modeselect == 'off_evaluation') {
			$sql = "SELECT js.fk_user, js.fk_job,t.fk_skill, s.label";
		}elseif($modeselect == 'on_evaluation') {
			$sql = "SELECT ev.fk_user, ev.fk_job, ev.date_eval";
			$sql .= " ,ev.date_eval, t.fk_skill, t.rankorder, t.required_rank,s.label";
		}
		
		// if($option == 'all_users') {
		// 	$sql .= ", COUNT(ev.fk_user) as nb_skill_users";
		// }elseif($option == 'validate_users') {
		// 	$sql .= ", COUNT(ev.fk_user) as nb_confirmed_skilluser";
		// }

		if($modeselect == 'off_evaluation') {
			$sql .= "  FROM ".MAIN_DB_PREFIX."hrm_job_user as js";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skillrank as t on t.fk_object = js.fk_job";
			
		}elseif($modeselect == 'on_evaluation') {
			$sql .= "  FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as t on t.fk_evaluation = ev.rowid";
			
		}
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s on t.fk_skill = s.rowid";
		$sql .= " WHERE 1 = 1";
		
		$sql .= $wheresql;
		if($modeselect == 'off_evaluation') {
			if(!empty(array_filter($arr_skilljobs))) {
				$sql .= ' AND js.fk_job IN ('.implode(',', $arr_skilljobs).')';
			}
			}elseif($modeselect == 'on_evaluation') {
				if(!empty(array_filter($arr_skilljobs))) {
					$sql .= ' AND ev.fk_job IN ('.implode(',', $arr_skilljobs).')';
				}
			}
	
		if($option == 'validate_users') {
			$sql .= ' AND t.rankorder < t.required_rank AND t.rankorder > 0';
		}

		if($modeselect == 'on_evaluation') {
			$sql .= " GROUP BY ev.fk_user, ev.fk_job,t.fk_skill,t.rankorder";
		}

		if($modeselect == 'off_evaluation') {
			$sql .= " AND t.objecttype='job'";
			$sql .= " GROUP BY js.fk_user, js.fk_job,t.fk_skill";
		}

		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					
					if($option == 'all_users') {
						// var_dump($obj);
						// $nbskills[$obj->fk_user][$obj->fk_job][$obj->fk_skill] = $obj;
						$nbskills[] = $obj;
					}
			
					if($option == 'validate_users') {
						// var_dump($obj);
						// $nbvalidateskills[$obj->fk_user][$obj->fk_job][$obj->fk_skill][$obj->rankorder] = $obj;
						$nbvalidateskills[] = $obj;
					}
				 }
				
				$i++;
			}
			$db->free($resql);

			if($option == 'validate_users') {
				// var_dump($nbvalidateskills);
				return $nbvalidateskills;
			}

			if($option == 'all_users'){
				// var_dump($nbskills);
				return $nbskills;
			}
		} else {
			dol_print_error($db);
		}
	}

// 	public function getSkillEvaluated2($skill_user, $arr_skill, $date_start, $date_end, $arr_skilljobs, $option, $modeselect)
// 	{
// 		global $db;

// 		$date = array('start' => $date_start, 'end' => $date_end);

// 		// Définition des cas possibles (16 combinaisons)
// 		$case1 = !empty($arr_skill) && !empty(array_filter($date)) && ($skill_user > 0) && !empty($arr_skilljobs);
// 		$case2 = empty($arr_skill) && !empty(array_filter($date)) && ($skill_user > 0) && !empty($arr_skilljobs);
// 		$case3 = empty($arr_skill) && !empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && !empty($arr_skilljobs);
// 		$case4 = !empty($arr_skill) && empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && !empty($arr_skilljobs);
// 		$case5 = !empty($arr_skill) && empty(array_filter($date)) && ($skill_user > 0) && !empty($arr_skilljobs);
// 		$case6 = empty($arr_skill) && empty(array_filter($date)) && ($skill_user > 0) && !empty($arr_skilljobs);
// 		$case7 = empty($arr_skill) && empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && !empty($arr_skilljobs);
// 		$case8 = !empty($arr_skill) && !empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && !empty($arr_skilljobs);
// 		$case9 = !empty($arr_skill) && !empty(array_filter($date)) && ($skill_user > 0) && empty($arr_skilljobs);
// 		$case10 = empty($arr_skill) && !empty(array_filter($date)) && ($skill_user > 0) && empty($arr_skilljobs);
// 		$case11 = empty($arr_skill) && !empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && empty($arr_skilljobs);
// 		$case12 = !empty($arr_skill) && empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && empty($arr_skilljobs);
// 		$case13 = !empty($arr_skill) && empty(array_filter($date)) && ($skill_user > 0) && empty($arr_skilljobs);
// 		$case14 = empty($arr_skill) && empty(array_filter($date)) && ($skill_user > 0) && empty($arr_skilljobs);
// 		$case15 = empty($arr_skill) && empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && empty($arr_skilljobs);
// 		$case16 = !empty($arr_skill) && !empty(array_filter($date)) && ($skill_user <= 0 || $skill_user === '') && empty($arr_skilljobs);

// 		$wheresql = '';

// 		// Traitement de chaque cas
// 		switch (true) {
// 			case $case1:
// 				$wheresql .= ' AND t.fk_skill IN (' . implode(',', array_map('intval', $arr_skill)) . ')';
// 				$wheresql .= $this->buildDateCondition($modeselect, $date);
// 				$wheresql .= ' AND ev.fk_user = ' . intval($skill_user);
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case2:
// 				$wheresql .= $this->buildDateCondition($modeselect, $date);
// 				$wheresql .= ' AND ev.fk_user = ' . intval($skill_user);
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case3:
// 				$wheresql .= $this->buildDateCondition($modeselect, $date);
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case4:
// 				$wheresql .= ' AND t.fk_skill IN (' . implode(',', array_map('intval', $arr_skill)) . ')';
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case5:
// 				$wheresql .= ' AND t.fk_skill IN (' . implode(',', array_map('intval', $arr_skill)) . ')';
// 				$wheresql .= ' AND ev.fk_user = ' . intval($skill_user);
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case6:
// 				$wheresql .= ' AND ev.fk_user = ' . intval($skill_user);
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case7:
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case8:
// 				$wheresql .= ' AND t.fk_skill IN (' . implode(',', array_map('intval', $arr_skill)) . ')';
// 				$wheresql .= $this->buildDateCondition($modeselect, $date);
// 				$wheresql .= $this->buildSkillJobsCondition($arr_skilljobs, $modeselect);
// 				break;

// 			case $case9:
// 			case $case10:
// 			case $case11:
// 			case $case12:
// 			case $case13:
// 			case $case14:
// 			case $case15:
// 			case $case16:
// 				// Même logique, sans `$arr_skilljobs`
// 				$wheresql .= $this->handleCasesWithoutSkillJobs($case9, $case10, $case11, $case12, $case13, $case14, $case15, $case16, $arr_skill, $date, $skill_user, $modeselect);
// 				break;

// 			default:
// 				$wheresql .= ''; // Aucun filtre spécifique
// 				break;
// 		}

// 		// Construire la requête SQL
// 		$sql = $this->buildBaseQuery($modeselect);
// 		$sql .= " WHERE 1 = 1";
// 		$sql .= $wheresql;

// 		if ($option === 'validate_users') {
// 			$sql .= ' AND t.rankorder < t.required_rank';
// 		}

// 		if ($modeselect === 'on_evaluation') {
// 			$sql .= " GROUP BY ev.fk_user, ev.fk_job, t.fk_skill, t.rankorder";
// 		} elseif ($modeselect === 'off_evaluation') {
// 			$sql .= " AND t.objecttype = 'job'";
// 			$sql .= " GROUP BY js.fk_user, js.fk_job, t.fk_skill";
// 		}

// 		// Exécuter la requête
// 		$resql = $db->query($sql);

// 		if ($resql) {
// 			$results = [];
// 			while ($obj = $db->fetch_object($resql)) {
// 				$results[] = $obj;
// 			}
// 			$db->free($resql);
// 			return $results;
// 		} else {
// 			dol_print_error($db);
// 		}
// 	}

// private function buildSkillJobsCondition($arr_skilljobs, $modeselect)
// {
//     $condition = '';

//     if (!empty($arr_skilljobs) && is_array($arr_skilljobs)) {
//         $jobIds = implode(',', array_map('intval', $arr_skilljobs));
//         if ($modeselect === 'on_evaluation') {
//             // $condition .= ' AND js.fk_job IN (' . $jobIds . ')';
//         } elseif ($modeselect === 'off_evaluation') {
//             // $condition .= ' AND js.fk_job IN (' . $jobIds . ')';
//         }
//     }

//     return $condition;
// }

// private function buildDateCondition($modeselect, $date)
// {
//     $condition = '';
    
//     // Si les dates de début et de fin sont définies
//     if (!empty($date['start']) && !empty($date['end'])) {
//         if ($modeselect === 'on_evaluation') {
//             $condition .= ' AND ev.date_eval BETWEEN "' . dol_escape($date['start']) . '" AND "' . dol_escape($date['end']) . '"';
//         } elseif ($modeselect === 'off_evaluation') {
//             $condition .= ' AND js.date_end BETWEEN "' . dol_escape($date['start']) . '" AND "' . dol_escape($date['end']) . '"';
//         }
//     }
//     // Cas où seule la date de début est définie
//     elseif (!empty($date['start'])) {
//         if ($modeselect === 'on_evaluation') {
//             $condition .= ' AND ev.date_eval >= "' . dol_escape($date['start']) . '"';
//         } elseif ($modeselect === 'off_evaluation') {
//             $condition .= ' AND js.date_start >= "' . dol_escape($date['start']) . '"';
//         }
//     }
//     // Cas où seule la date de fin est définie
//     elseif (!empty($date['end'])) {
//         if ($modeselect === 'on_evaluation') {
//             $condition .= ' AND ev.date_eval <= "' . dol_escape($date['end']) . '"';
//         } elseif ($modeselect === 'off_evaluation') {
//             $condition .= ' AND js.date_end <= "' . dol_escape($date['end']) . '"';
//         }
//     }

//     return $condition;
// }

// private function handleCasesWithoutSkillJobs($skill_user, $arr_skill, $date, $modeselect)
// {
//     $condition = '';

//     // Cas où il y a des compétences spécifiques
//     if (!empty($arr_skill)) {
//         $condition .= ' AND t.fk_skill IN (' . implode(',', $arr_skill) . ')';
//     }

//     // Cas où une plage de dates est définie
//     if (!empty(array_filter($date))) {
//         if ($modeselect === 'on_evaluation') {
//             $condition .= ' AND (ev.date_eval BETWEEN ' . $date['start'] . ' AND ' . $date['end'] . ')';
//         } elseif ($modeselect === 'off_evaluation') {
//             $condition .= ' AND (js.date_end <= ' . $date['end'] . ' OR js.date_start BETWEEN ' . $date['start'] . ' AND ' . $date['end'] . ' AND js.date_end IS NULL)';
//         }
//     }

//     // Cas où un utilisateur spécifique est ciblé
//     if ($skill_user > 0) {
//         if ($modeselect === 'on_evaluation') {
//             $condition .= ' AND ev.fk_user = ' . intval($skill_user);
//         } elseif ($modeselect === 'off_evaluation') {
//             $condition .= ' AND js.fk_user = ' . intval($skill_user);
//         }
//     }

//     return $condition;
// }

// private function buildBaseQuery($modeselect)
// {
//     // Initialisation de la requête SQL de base
//     $sql = '';

//     // En fonction du mode, on sélectionne les bonnes tables et colonnes
//     if ($modeselect == 'on_evaluation') {
//         $sql = "SELECT ev.fk_user, ev.fk_job, ev.date_eval, ev.date_eval, t.fk_skill, t.rankorder, t.required_rank, s.label";
//         $sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluation as ev";
//         $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_evaluationdet as t ON t.fk_evaluation = ev.rowid";
//     } elseif ($modeselect == 'off_evaluation') {
//         $sql = "SELECT js.fk_user, js.fk_job, t.fk_skill, s.label";
//         $sql .= " FROM ".MAIN_DB_PREFIX."hrm_job_user as js";
//         $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skillrank as t ON t.fk_object = js.fk_job";
//     }

//     // On ajoute toujours la jointure avec la table des compétences
//     $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s ON t.fk_skill = s.rowid";
    
//     // On inclut une condition générale 'WHERE' qui sera augmentée par d'autres filtres
//     $sql .= " WHERE 1 = 1";

//     return $sql;
// }


}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class CVTecLine. You can also remove this and generate a CRUD class for lines objects.
 */
class CVTecLine extends CommonObjectLine
{
	// To complete with content of an object CVTecLine
	// We should have a field rowid, fk_cvtec and position

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
