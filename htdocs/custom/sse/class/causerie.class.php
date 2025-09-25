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
 * \file        class/causerie.class.php
 * \ingroup     sse
 * \brief       This file is a CRUD class file for Causerie (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/sse/class/causerieuser.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sse/class/goalelement.class.php';
dol_include_once('/sse/lib/sse.lib.php');

/**
 * Class for Causerie
 */
class Causerie extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'sse';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'causerie';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'sse_causerie';

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
	 * @var string String with name of icon for causerie. Must be the part after the 'object_' into object_causerie.png
	 */
	public $picto = 'fa-comments';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_INORGANISATION = 2;
	const STATUS_PROGRAMMED = 3;
	const STATUS_INANIMATION = 4;
	const STATUS_REALIZED = 5;
	const STATUS_CLOSED = 6;
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
		'ref' => array('type'=>'varchar(255)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>1, 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'validate'=>'1', 'comment'=>"Reference of object"),
		'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>3, 'validate'=>'1',),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>0,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>2, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validé', '2'=>'En organisation', '3'=>'Programmée', '4'=>'En animation', '5'=>'Réalisée', '6'=>'Clôturée', '9'=>'Annulé'), 'validate'=>'1',),
		'subtheme' => array('type'=>'varchar(255)', 'label'=>'Sous-thème', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>3, 'index'=>1, 'cssview'=>'wordbreak',),
		'date_debut' => array('type'=>'datetime', 'label'=>'Date début', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>1,),
		'date_fin' => array('type'=>'datetime', 'label'=>'Date fin', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>1,),
		'local' => array('type'=>'varchar(255)', 'label'=>'Local', 'enabled'=>'1', 'position'=>65, 'notnull'=>0, 'visible'=>3, 'cssview'=>'wordbreak',),
		// 'theme' => array('type'=>'integer:Theme:sse/class/theme.class.php', 'label'=>'Thème', 'enabled'=>'1', 'position'=>75, 'notnull'=>0, 'visible'=>-2, 'index'=>1, 'validate'=>'1',),
		'nbpresent' => array('type'=>'integer', 'label'=>'Inscrits', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>5,),
		'nbinvited' => array('type'=>'integer', 'label'=>'Invités', 'enabled'=>'1', 'position'=>140, 'notnull'=>0, 'visible'=>5,),
		'percentparticip' => array('type'=>'integer', 'label'=>'% Participation', 'enabled'=>'1', 'position'=>160, 'notnull'=>0, 'visible'=>2,),
	);
	public $id;
	public $ref;
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
	public $theme;
	public $subtheme;
	public $date_debut;
	public $date_fin;
	public $local;
	public $causerie_theme;
	public $nbpresent;
	public $nbinvited;
	public $animateur;
	public $percentparticip;
	// END MODULEBUILDER PROPERTIES

	public $globalgroup; // Global group
	public $members = array(); // Array of users
	public $extern_members = array();
	public $contact_ids = array();

	public $nb_rights; // Number of rights granted to the user

	private $_tab_loaded = array(); // Array of cache of already loaded permissions

	public $oldcopy; // To contains a clone of this when we need to save old properties of object
	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'sse_causerieline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_causerie';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Causerieline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('sse_causeriedet');

	// /**
	//  * @var CauserieLine[]     Array of subtable lines
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
		/*if ($user->rights->sse->causerie->read) {
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
	
		$this->SetAnimateur();

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
		// Check parameters
		if (empty($id) && empty($ref)) {
			return -1;
		}

		$sql = "SELECT";
		$sql .= " c.rowid,";
		$sql .= " c.tms,";
		$sql .= " c.ref,";
		$sql .= " c.status,";
		$sql .= " c.local,";
		$sql .= " c.subtheme,";
		$sql .= " c.description,";
		$sql .= " c.note_public,";
		$sql .= " c.note_private,";
		$sql .= " c.date_creation,";
		$sql .= " c.date_debut,";
		$sql .= " c.date_fin,";
		$sql .= " c.tms,";
		$sql .= " c.fk_user_creat,";
		$sql .= " c.fk_user_modif,";
		$sql .= " c.last_main_doc,";
		$sql .= " c.import_key,";
		$sql .= " c.model_pdf,";
		$sql .= " c.nbpresent,";
		$sql .= " c.nbinvited";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerie as c";
		if ($id) {
			$sql .= " WHERE c.rowid = ".((int) $id);
		}
		// if ($ref) {
		// 	$sql .= " WHERE c.rowid = '".$this->db->escape($ref)."'";
		// }
		
		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->ref;
				$this->tms = $this->db->jdate($obj->tms);
				$this->status = $obj->status;
				//$this->fk_projet = $obj->fk_projet;
				$this->causerie_theme = $obj->causerie_theme;
				$this->subtheme = $obj->subtheme;
				$this->description = $obj->description;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->local = $obj->local;
				// $this->animateur = $obj->userid;
				$this->date_debut = $this->db->jdate($obj->date_debut);
				$this->date_fin = $this->db->jdate($obj->date_fin);
				$this->nbpresent = sizeof($this->listSSEUsersForGroup());
				$this->nbinvited = sizeof($this->listSSEExternsForGroup());
				$this->percentparticip = 0;

				$this->fetch_optionals();

				// Liste des partipants 
				$this->extern_members = $this->listSSEExternsForGroup();
				$this->members = $this->listSSEUsersForGroup();

				$this->oldcopy = clone $this;
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
		$this->actionmsg = msgAgendaUpdateForCauserie($this, 1);
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
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE c, ca,ce FROM ".MAIN_DB_PREFIX."sse_causerie AS c";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."sse_causerieattendance AS ca";
		$sql .= " ON ca.fk_causerie = c.rowid"; 
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."sse_causerie_extrafields AS ce";
		$sql .= " ON ce.fk_object = c.rowid"; 
		$sql .= " WHERE c.rowid = ".((int) $this->id);
		$sql .= " AND ca.fk_causerie = ".((int) $this->id);
		$sql .= " AND ce.fk_object = ".((int) $this->id);
		

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CAUSERIE_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			
			return 1;
		}
		// return $this->deleteCommon($user, $notrigger);
		// return $this->deleteCommon($user, $notrigger, 1);
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->causerie_advance->validate))))
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
		//$this->contact_ids = $this->SetListCauserieUser();

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
				$result = $this->call_trigger('CAUSERIE_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'causerie/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'causerie/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->sse->dir_output.'/causerie/'.$oldref;
				$dirdest = $conf->sse->dir_output.'/causerie/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->sse->dir_output.'/causerie/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CAUSERIE_UNVALIDATE');
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

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'CAUSERIE_REOPEN');
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
		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Causerie").'</u>';
		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Causerie").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/sse/causerie_card.php', 1).'?id='.$this->id;

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
		$hookmanager->initHooks(array('causeriedao'));
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
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('En cours d\'organistion');
			$this->labelStatus[self::STATUS_INORGANISATION] = $langs->transnoentitiesnoconv('En attente de confirmation');
			$this->labelStatus[self::STATUS_PROGRAMMED] = $langs->transnoentitiesnoconv('Programmée');
			$this->labelStatus[self::STATUS_INANIMATION] = $langs->transnoentitiesnoconv('En cours d\'animation');
			$this->labelStatus[self::STATUS_REALIZED] = $langs->transnoentitiesnoconv('Réalisée');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Closed');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_INORGANISATION] = $langs->transnoentitiesnoconv('En attente de confirmation');
			$this->labelStatusShort[self::STATUS_INANIMATION] = $langs->transnoentitiesnoconv('En cours d\'animation');
			$this->labelStatusShort[self::STATUS_PROGRAMMED] = $langs->transnoentitiesnoconv('Programmée');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Organisation');
			$this->labelStatusShort[self::STATUS_REALIZED] = $langs->transnoentitiesnoconv('Réalisée');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Closed');
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

		$objectline = new CauserieLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_causerie = '.((int) $this->id)));

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

		if (empty($conf->global->SSE_CAUSERIE_ADDON)) {
			$conf->global->SSE_CAUSERIE_ADDON = 'mod_causerie_standard';
		}

		if (!empty($conf->global->SSE_CAUSERIE_ADDON)) {
			$mybool = false;

			$file = $conf->global->SSE_CAUSERIE_ADDON.".php";
			$classname = $conf->global->SSE_CAUSERIE_ADDON;

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
		$includedocgeneration = 1;

		$langs->load("sse@sse");

		if (!dol_strlen($modele)) {
			$modele = 'standard_causerie';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->CAUSERIE_ADDON_PDF)) {
				$modele = $conf->global->CAUSERIE_ADDON_PDF;
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
	 *	Validate object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function schedule_talk($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_PROGRAMMED) {
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->causerie_advance->validate))))
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
			$sql .= " status = ".self::STATUS_PROGRAMMED;
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
				$result = $this->call_trigger('CAUSERIE_SCHEDULE_TALK', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'causerie/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'causerie/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->sse->dir_output.'/causerie/'.$oldref;
				$dirdest = $conf->sse->dir_output.'/causerie/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->sse->dir_output.'/causerie/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
			$this->status = self::STATUS_PROGRAMMED;
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
	 *	inanimation_talk object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function inanimation_talk($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// // Protection
		if ($this->status == self::STATUS_INANIMATION) {
			dol_syslog(get_class($this)."::inanimation action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		// if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->write))
		//  || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->causerie_advance->validate))))
		//  {
		//  $this->error='NotEnoughPermissions';
		//  dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		//  return -1;
		//  } 

		$now = dol_now();

		$this->db->begin();

		
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		//$sql .= " SET ref = '".$this->db->escape($num)."',";
		$sql .= " SET status = ".self::STATUS_INANIMATION;
	
		if (!empty($this->fields['fk_user_modif'])) {
			$sql .= ", fk_user_modif = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::inanimation_talk()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CAUSERIE_INANIMATION_TALK', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_INANIMATION;
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
	 *	realized_talk object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function realized_talk($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// // Protection
		if ($this->status == self::STATUS_REALIZED) {
			dol_syslog(get_class($this)."::validate action abandonned: already realized", LOG_WARNING);
			return 0;
		}

		// if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->write))
		//  || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->causerie_advance->validate))))
		//  {
		//  $this->error='NotEnoughPermissions';
		//  dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		//  return -1;
		//  } 

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
			$sql .= " status = ".self::STATUS_REALIZED;
		
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::realized_talk", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CAUSERIE_REALIZED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->oldref = $this->ref;
			}
		}

		// Set new ref and current status
		if (!$error) {
			if(dol_print_date(dol_now()) == dol_print_date($this->date_debut) && dol_print_date(dol_now(), 'hour') == dol_print_date($this->date_debut, 'hour')) {
				$this->ref = $num;
				$this->status = self::STATUS_REALIZED;
			}
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
	 *	closed_talk object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function closed_talk($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// // Protection
		if ($this->status == self::STATUS_CLOSED) {
			dol_syslog(get_class($this)."::validate action abandonned: already closed", LOG_WARNING);
			return 0;
		}

		// if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->write))
		//  || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->sse->causerie->causerie_advance->validate))))
		//  {
		//  $this->error='NotEnoughPermissions';
		//  dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		//  return -1;
		//  } 
		
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
			$sql .= " status = ".self::STATUS_CLOSED;
		
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
				$result = $this->call_trigger('CAUSERIE_CLOSED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->oldref = $this->ref;
			}
		}
		// Set new ref and current status
		if (!$error) {
			if(dol_print_date(dol_now()) == dol_print_date($this->date_debut) && dol_print_date(dol_now(), 'hour') == dol_print_date($this->date_debut, 'hour')) {
				$this->ref = $num;
				$this->status = self::STATUS_CLOSED;
			}
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
	 *	Set animation status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setAnimation($user, $notrigger = 0)
	{
		// // Protection
		if ($this->status >= self::STATUS_PROGRAMMED) {
			return $this->setStatusCommon($user, $this->status, $notrigger, 'CAUSERIE_ANIMATION');
		}

		return $this->setStatusCommon($user, self::STATUS_INORGANISATION, $notrigger, 'CAUSERIE_ANIMATION');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function realized($user, $notrigger = 0)
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

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'CAUSERIE_CANCEL');
	}


	/**
	 * 
	 * Compter les causeries associées à un utilisateur.
	 * 
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function updateCounts()
    {
		$this->db->begin();
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$updates = [];
		
		if (isset($this->nbpresent)) {
			$updates[] = "nbpresent = ".intval($this->nbpresent);
		}
		if (isset($this->nbinvited)) {
			$updates[] = "nbinvited = ".intval($this->nbinvited);
		}
		
		// Vérifier qu'on a bien des valeurs à mettre à jour
		if (!empty($updates)) {
			$sql .= " " . implode(", ", $updates);
			$sql .= " WHERE rowid = ".intval($this->id);
		
		
	

			dol_syslog(get_class($this)."::updateCounts", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('UPDATE_NB', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		}
    }

	/**
	 * Mise à jour du pourcentage de taux de participation. 
	 * 
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function updateCountPourcentage()
	{
		$this->db->begin();
		$error = 0;

		// Requête SELECT pour compter les présences
		if (!empty($this->id)) {
			$sql = "SELECT COUNT(ca.rowid) AS count
					FROM ".MAIN_DB_PREFIX."sse_causerieattendance as ca
					WHERE ca.fk_causerie = ".$this->id."
					AND ca.status = 6
					AND ca.presence = 1";

			// Exécution de la requête SELECT
			$resql = $this->db->query($sql);
		
			if ($resql) {
				$row = $this->db->fetch_object($resql);
				$presenceConfime = $row->count; 

				// Calcul du pourcentage
				if ($this->nbpresent > 0) {
					$pourcentage = (((int) $presenceConfime + $this->nbinvited) * 100) / ($this->nbpresent + $this->nbinvited);
					$pourcentage = $pourcentage;
				} else {
					$pourcentage = 0; 
				}
		
				$sql_update = "UPDATE ".MAIN_DB_PREFIX."sse_causerie"; 
				$sql_update .= " SET percentparticip = '".(float) $pourcentage."'";  // Ajout du pourcentage échappé
				$sql_update .= " WHERE rowid = ".(int) $this->id."";

				
				$resql_update = $this->db->query($sql_update);
				if (!$resql_update) {
					dol_print_error($this->db);
					$this->error = $this->db->lasterror();
					$error++;
				}
			} else {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			// Si aucune erreur, on effectue les triggers
			if (!$error && !$notrigger) {
				$result = $this->call_trigger('UPDATE_POURCENTAGE', $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				dol_syslog(get_class($this)."::updateCountPourcentage", LOG_DEBUG);
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 * Insertion d'un animateur à la causerie
	 * 
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function SetAnimateur() 
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."sse_causerieattendance (entity, fk_user, fk_causerie)";
		$sql .= " VALUES (";
		$sql .= ((int) $entity) . ", "; 
		$sql .= ((int) $this->array_options['options_animateur']) . ", ";
		$sql .= ((int) $this->id); 
		$sql .= ")";
		$result = $this->db->query($sql);

		if ($result) {
			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::SetAnimateur ".$this->error, LOG_ERR);
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
	 * Add a user to a causerie group.
	 *
	 * @param  int  $causerieid  ID of the causerie.
	 * @param  int  $userid      ID of the user to add.
	 * @param  int  $entity      Entity ID.
	 * @param  int  $notrigger   If set to 1, disables triggers.
	 * 
	 * @return int               >0 if successful, <0 if an error occurred.
	 */
	public function SetInCauserieGroup($causerieid, $userid, $entity, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$error = 0;
		$causerie = new Causerie($this->db);
		$causerie->fetch($causerieid);

		$now = dol_now();
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_causerieattendance";
		$sql .= " WHERE fk_user  = ".((int)  $userid);
		$sql .= " AND fk_causerie = ".((int) $causerieid);
		$sql .= " AND entity = ".((int) $entity);

		$result = $this->db->query($sql);
		//if($causerie->status < $causerie::STATUS_PROGRAMMED) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."sse_causerieattendance (entity, fk_user, fk_causerie)";
			$sql .= " VALUES (".((int) $entity).",".((int)  $userid).",".((int) $causerieid).")";
			$result = $this->db->query($sql);
		//}

		if($causerie->status >= $causerie::STATUS_PROGRAMMED) {
			$sql = " UPDATE ".MAIN_DB_PREFIX."sse_causerieattendance";
			$sql .= " SET";
			$sql .= " date_creation ='".$this->db->idate($now)."', ";
			$sql .= " status = 2";
			$sql .= " WHERE fk_user= '".((int)  $userid)."'";
			$sql .= " AND fk_causerie = '".((int) $causerieid)."'";
			$result = $this->db->query($sql);
		}

		if ($result) {
			if (!$error && !$notrigger) {

				// Call trigger
				$result = $this->call_trigger('USER_CAUSERIE_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->updateCounts();
				$this->updateCountPourcentage();
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::SetInCauserieGroup ".$this->error, LOG_ERR);
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
	 *  Remove a user from a causerie
	 * 
	 * @param  int  $causerieid  ID of the causerie.
	 * @param  int  $userid      ID of the user to add.
	 * @param  int  $entity      Entity ID.
	 * @param  int  $notrigger   If set to 1, disables triggers.
	 * 
	 * @return int               >0 if successful, <0 if an error occurred.
	 */
	public function RemoveFromCauserie($causerie,  $userid, $entity, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."sse_causerieattendance";
		$sql .= " WHERE fk_user  = ".((int) $userid);
		$sql .= " AND fk_causerie = ".((int) $causerie);
		$sql .= " AND entity = ".((int) $entity);

		$result = $this->db->query($sql);
		
		if ($result) {
			if (!$error && !$notrigger) {
				// // Call trigger
				$result = $this->call_trigger('USER_CAUSERIE_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->updateCounts();
				$this->updateCountPourcentage();
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::RemoveFromCauserie ".$this->error, LOG_ERR);
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
	 * 	Return array of User objects for group this->id (or all if this->id not defined)
	 *
	 * 	@param	string	$excludefilter		Filter to exclude. Do not use here a string coming from user input.
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listSSEUsersForGroup($excludefilter = '', $mode = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT u.rowid";
		if (!empty($this->id)) {
			$sql .= ", cg.entity as ssegroup_entity";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		if (!empty($this->id)) {
			$sql .= ", ".MAIN_DB_PREFIX."sse_causerieattendance as cg";
		}
		$sql .= " WHERE 1 = 1";
		if (!empty($this->id)) {
			$sql .= " AND cg.fk_user = u.rowid";
		}
		if (!empty($this->id)) {
			$sql .= " AND cg.fk_causerie = ".((int) $this->id);
		}
	
		if (!empty($this->id)) {
			$sql .= " AND cg.entity = 0";
		}
		if (!empty($excludefilter)) {
			$sql .= ' AND ('.$excludefilter.')';
		}
		
		$sql .= " ORDER BY u.lastname ASC";

		dol_syslog(get_class($this)."::listSSEUsersForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					if ($mode != 1) {
						$newuser = new User($this->db);
						$newuser->fetch($obj->rowid);
						$ret[$obj->rowid] = $newuser;
					} else {
						$ret[$obj->rowid] = $obj->rowid;
					}
				}
						
				if ($mode != 1 && !empty($obj->sseusergroup_entity)) {
					$ret[$obj->rowid]->sseusergroup_entity[] = $obj->sseusergroup_entity;
				}
			}

			$this->db->free($resql);

			if (is_object($this) && isset($this->array_options['options_animateur'])) {
				$newuser = new User($this->db);
				$newuser->fetch($this->array_options['options_animateur']);
					$ret[$this->array_options['options_animateur']] = $newuser;
			}

			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Return array of User extern objects for group this->id (or all if this->id not defined)
	 *
	 * 	@param	string	$excludefilter		Filter to exclude. Do not use here a string coming from user input.
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listSSEExternsForGroup($excludefilter = '', $mode = 0) 
	{
		global $conf, $user;
		$ret = array();

		$sql = "SELECT uc.rowid";
		if (!empty($this->id)) {
			$sql .= ", cg.entity as ssegroup_entity";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerieuser as uc";
		if (!empty($this->id)) {
			$sql .= ", ".MAIN_DB_PREFIX."sse_causerieattendance as cg";
		}
		$sql .= " WHERE 1 = 1";
		if (!empty($this->id)) {
			$sql .= " AND cg.fk_user = uc.rowid";
		}
		if (!empty($this->id)) {
			$sql .= " AND cg.fk_causerie = ".((int) $this->id);
		}
		if (!empty($this->id)) {
			$sql .= " AND cg.entity = 2";
		}
		
		if (!empty($excludefilter)) {
			$sql .= ' AND ('.$excludefilter.')';
		}
		$sql .= " ORDER BY uc.lastname ASC";

		dol_syslog(get_class($this)."::listSSEExternsForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					if ($mode != 1) {
						$newuser = new CauserieUser($this->db);
						$newuser->fetch($obj->rowid);
						$ret[$obj->rowid] = $newuser;
					} else {
						$ret[$obj->rowid] = $obj->rowid;
					}
				}
				if ($mode != 1 && !empty($obj->sseusergroup_entity)) {
					$ret[$obj->rowid]->sseusergroup_entity[] = $obj->sseusergroup_entity;
				}
			}

			$this->db->free($resql);
			
			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
		

	/**
	 * list of participants in a causerie card
	 * 
	 * @return int               >0 if successful, <0 if an error occurred.
	 */
	public function getEmargementList()
	{
		global $conf;

		$sql = "SELECT ce.status, ce.fk_user, ce.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."sse_causerieattendance as ce";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."sse_causerie as c";
		$sql .= " WHERE ce.fk_causerie = ".((int) $this->id);

		$emargements = array();

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			
			while ($obj = $this->db->fetch_object($resql)) {
				$emargements[$obj->rowid] = $obj->rowid;
			}

			$this->db->free($resql);
			return $emargements;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__." ".implode(','.$this->errors), LOG_ERR);

			return -1;
		}
	}


}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class CauserieLine. You can also remove this and generate a CRUD class for lines objects.
 */
class CauserieLine extends CommonObjectLine
{
	// To complete with content of an object CauserieLine
	// We should have a field rowid, fk_causerie and position

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
