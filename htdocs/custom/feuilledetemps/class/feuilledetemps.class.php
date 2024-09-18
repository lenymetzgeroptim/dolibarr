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
 * \file        class/feuilledetemps.class.php
 * \ingroup     feuilledetemps
 * \brief       This file is a CRUD class file for FeuilleDeTemps (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/regul.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/deplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/silae.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/extendedUser.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/donneesrh/class/userfield.class.php';

/**
 * Class for FeuilleDeTemps
 */
class FeuilleDeTemps extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'feuilledetemps';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'feuilledetemps';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'feuilledetemps_feuilledetemps';

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
	 * @var string String with name of icon for feuilledetemps. Must be the part after the 'object_' into object_feuilledetemps.png
	 */
	public $picto = 'timesheet_32@feuilledetemps';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_APPROBATION1 = 2;
	const STATUS_APPROBATION2 = 3;
	const STATUS_VERIFICATION = 4;
	const STATUS_EXPORTED = 5;
	const STATUS_CANCELED = 9;


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
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
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object"),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php:0', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php:0', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Valid&eacute;', '2'=>'1&egrave;re approbation en attente', '3'=>'2&egrave;me approbation en attente', '4'=>'En V&eacuterification', '5'=>'Export&eacute', '9'=>'Annul&eacute;'),),
		'date_debut' => array('type'=>'date', 'label'=>'DateDebut', 'enabled'=>'1', 'position'=>100, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'date_fin' => array('type'=>'date', 'label'=>'DateFin', 'enabled'=>'1', 'position'=>101, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'fk_user' => array('type'=>'integer:User:user/class/user.class.php:0:(t.statut:=:1)', 'label'=>'Utilisateur', 'enabled'=>'1', 'position'=>110, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'observation' => array('type'=>'text', 'label'=>'Observation', 'enabled'=>'1', 'position'=>210, 'notnull'=>0, 'visible'=>4,),
		'prime_astreinte' => array('type'=>'price', 'label'=>'PrimeAstreinte', 'enabled'=>'1', 'position'=>150, 'notnull'=>0, 'visible'=>1,),
		'prime_exceptionnelle' => array('type'=>'price', 'label'=>'PrimeExceptionnelle', 'enabled'=>'1', 'position'=>151, 'notnull'=>0, 'visible'=>1,),
		'prime_objectif' => array('type'=>'price', 'label'=>'PrimeObjectif', 'enabled'=>'1', 'position'=>152, 'notnull'=>0, 'visible'=>1,),
		'prime_variable' => array('type'=>'price', 'label'=>'PrimeVariable', 'enabled'=>'1', 'position'=>153, 'notnull'=>0, 'visible'=>1,),
		'prime_amplitude' => array('type'=>'price', 'label'=>'PrimeAmplitude', 'enabled'=>'1', 'position'=>154, 'notnull'=>0, 'visible'=>1,),
	);
	public $rowid;
	public $ref;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $status;
	public $date_debut;
	public $date_fin;
	public $fk_user;
	public $observation;
	public $prime_astreinte;
	public $prime_exceptionnelle;
	public $prime_objectif;
	public $prime_variable;
	public $prime_amplitude;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	public $table_element_line = 'feuilledetemps_observationcompta';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	public $fk_element = 'fk_feuilledetemps';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	public $class_element_line = 'ObservationCompta';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	protected $childtablesoncascade = array('feuilledetemps_task_validation');

	// /**
	//  * @var FeuilleDeTempsLine[]     Array of subtable lines
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
		/*if ($user->rights->feuilledetemps->feuilledetemps->read) {
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
		$lastFeuilleDeTemps = new FeuilleDeTemps($this->db);
		$lastFeuilleDeTemps->fetchLast($this->fk_user, $this->date_debut);
		
		if($lastFeuilleDeTemps->id > 0 && !empty($lastFeuilleDeTemps->observation)) {
			$this->observation = $lastFeuilleDeTemps->observation;
		}
		else {
			$this->observation = '';
		}

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
			if (property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
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
		
		if ($result) {
			$this->listApprover1 = $this->listApprover('', 1);
			$this->listApprover2 = $this->listApprover('', 2);
		}

		return $result;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $userid   Id of User
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLast($userid, $datedebut)
	{
		$result = $this->fetchCommon(0, '', ' AND t.fk_user = '.$userid.' AND t.date_debut = "'.$this->db->idate(dol_time_plus_duree($datedebut, -1, 'm')).'"');

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

		$sql = 'SELECT ';
		$sql .= $this->getFieldList('t');
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key.' IN ('.$this->db->sanitize($this->db->escape($value)).')';
				} else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$record->listApprover1 = $record->listApprover('', 1);
				$record->listApprover2 = $record->listApprover('', 2);

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
	public function update(User $user, $notrigger = true)
	{
		global $langs;

		$this->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_MODIFYInDolibarr", $this->ref);
		$this->actionmsg = '';
		if(!empty($this->oldcopy)) {
			foreach ($this->fields as $key => $val) {
				// Ignore special fields
				if (in_array($key, array('rowid', 'entity', 'import_key'))) {
					continue;
				}
				if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
					if (!in_array(abs($val['visible']), array(1, 3, 4))) {
						continue; // Only 1 and 3 and 4 that are case to update
					}
				}
		
				if (in_array($key, array('statut', 'status'))) {
					$value = formatValueForAgenda('statut', $this->$key, $this);
					$old_value = formatValueForAgenda('statut', $this->oldcopy->$key, $this);
				}
				else {
					$value = formatValueForAgenda($this->fields[$key]['type'], $this->$key);
					$old_value = formatValueForAgenda($this->fields[$key]['type'], $this->oldcopy->$key);
				}

				// Ajout des modification dans l'agenda
				if($value == $old_value) {
					continue;
				}

				$this->actionmsg .= "<strong>".$langs->transnoentities($val['label']).'</strong>: '.$old_value.' ➔ '.$value.'<br/>';
			}

			// 1ere étape : Supprimer les 1er et 2nd validateur nécéssaire
			$modification_1e_validation = '';
			$modification_2e_validation = '';
			foreach($this->oldcopy->listApprover1[2] as $id => $user_static){
				if(!in_array($id, $this->listApprover1[0])){	
					$prenom = $user_static->firstname;
					$nom = $user_static->lastname;
					$modification_1e_validation .= '<li>Suppression de '.$prenom.' '.$nom.'</li>';
				}
			}
			foreach($this->oldcopy->listApprover2[2] as $id => $user_static){
				if(!in_array($id, $this->listApprover2[0])){	
					$prenom = $user_static->firstname;
					$nom = $user_static->lastname;
					$modification_2e_validation .= '<li>Suppression de '.$prenom.' '.$nom.'</li>';
				}
			}

			// 2e étape : On ajoute les 1er et 2nd validateur nécéssaire
			foreach($this->listApprover1[2] as $id => $user_static){
				if(!in_array($id, $this->oldcopy->listApprover1[0])){
					$prenom = $user_static->firstname;
					$nom = $user_static->lastname;
					$modification_1e_validation .= '<li>Ajout de '.$prenom.' '.$nom.'</li>';
				}
			}
			foreach($this->listApprover2[2] as $id => $user_static){
				if(!in_array($id, $this->oldcopy->listApprover2[0])){
					$prenom = $user_static->firstname;
					$nom = $user_static->lastname;
					$modification_2e_validation .= '<li>Ajout de '.$prenom.' '.$nom.'</li>';
				}
			}

			if($modification_1e_validation) {
				$this->actionmsg .= '<strong>1ère validation</strong>:<ul>'.$modification_1e_validation."</ul><br/>";
			}
			if($modification_2e_validation) {
				$this->actionmsg .= '<strong>2ème validation</strong>:<ul>'.$modification_2e_validation."</ul><br/>";
			}
		}



		return $this->updateCommon($user, ($this->actionmsg ? 0 : $notrigger));
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
		// Suppression des actions liées
		$actionComm = new ActionComm($this->db);
		$listActions = $actionComm->getActions(0, $this->id, 'feuilledetemps@feuilledetemps');
		foreach($listActions as $action) {
			$action->delete($user);
		}

		// Supression des déplacements liés
		$deplacement = new Deplacement($this->db);
		$listDeplacements = $deplacement->fetchAll('', '', 0, 0, array('customsql' => "fk_user = $this->fk_user AND DATE_FORMAT(date, '%m') = ".date('m', $this->date_debut)));
		foreach($listDeplacements as $deplacement) {
			$deplacement->delete($user);
		}

		// Supression des regul liés
		$regul = new Regul($this->db);
		$listReguls = $regul->fetchAll('', '', 0, 0, array('customsql' => "fk_user = $this->fk_user AND DATE_FORMAT(date, '%m') = ".date('m', $this->date_debut)));
		foreach($listReguls as $regul) {
			$regul->delete($user);
		}

		// Supression des élements de verif liés
		$silae = new Silae($this->db);
		$listSilae = $silae->fetchAll('', '', 0, 0, array('customsql' => "fk_user = $this->fk_user AND DATE_FORMAT(date, '%m') = ".date('m', $this->date_debut)));
		foreach($listSilae as $silae) {
			$silae->delete($user);
		}

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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->feuilledetemps->feuilledetemps->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->feuilledetemps->feuilledetemps->feuilledetemps_advance->validate))))
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
				$result = $this->call_trigger('FEUILLEDETEMPS_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'feuilledetemps/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'feuilledetemps/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->feuilledetemps->dir_output.'/feuilledetemps/'.$oldref;
				$dirdest = $conf->feuilledetemps->dir_output.'/feuilledetemps/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->feuilledetemps->dir_output.'/feuilledetemps/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
	 *	Mettre l'objet en statut Approbation1
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function setApprobation1($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_APPROBATION1) {
			dol_syslog(get_class($this)."::setApprobation1 action abandonned: already in STATUS_APPROBATION1", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_APPROBATION1;
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setApprobation1()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				$this->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATION1InDolibarr", $this->ref);
				$this->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_APPROBATION1InDolibarr", $this->ref);

				// Call trigger
				$result = $this->call_trigger('FEUILLEDETEMPS_APPROBATION1', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_APPROBATION1;
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
	 *	Mettre l'objet en statut Approbation2
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function setApprobation2($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_APPROBATION2) {
			dol_syslog(get_class($this)."::setApprobation2 action abandonned: already in STATUS_APPROBATION2", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_APPROBATION2;
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setApprobation2()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				if($this->status == self::STATUS_APPROBATION1) {
					$this->actionmsg2 = $langs->transnoentities("FEUILLEDETEMPS_APPROBATION2InDolibarr", $this->ref);
					$this->actionmsg = $langs->transnoentities("FEUILLEDETEMPS_APPROBATION2InDolibarr", $this->ref);
				}
				else {
					$this->actionmsg2 = $langs->transnoentities("FEUILLEDETEMPS_APPROBATION12InDolibarr", $this->ref);
					$this->actionmsg = $langs->transnoentities("FEUILLEDETEMPS_APPROBATION12InDolibarr", $this->ref);
				}

				// Call trigger
				$result = $this->call_trigger('FEUILLEDETEMPS_APPROBATION2', $user);
				
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_APPROBATION2;
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
	 *	Mettre l'objet en statut Verification
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function setVerification($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VERIFICATION) {
			dol_syslog(get_class($this)."::STATUS_VERIFICATION action abandonned: already in STATUS_VERIFICATION", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_VERIFICATION;
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setVerification()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('FEUILLEDETEMPS_VERIFICATION', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_VERIFICATION;
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
	 *	Mettre l'objet en statut Exportée
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function setExported($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_EXPORTED) {
			dol_syslog(get_class($this)."::STATUS_EXPORTED action abandonned: already in STATUS_EXPORTED", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_EXPORTED;
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setExported()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				$this->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_EXPORTEDInDolibarr", $this->ref);
				$this->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_EXPORTEDInDolibarr", $this->ref);

				// Call trigger
				$result = $this->call_trigger('FEUILLEDETEMPS_EXPORTED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_EXPORTED;
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
		global $langs; 

		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		$this->actionmsg2 = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_REFUSInDolibarr", $this->ref);
		$this->actionmsg = $langs->transnoentitiesnoconv("FEUILLEDETEMPS_REFUSDetailInDolibarr", GETPOST('raison_refus'));

		$list_validation1 = $this->listApprover1;
		foreach($list_validation1[2] as $id => $user_static){
			$result = $this->updateTaskValidation($id, 1, 0, 1);
		}

		$list_validation2 = $this->listApprover2;
		foreach($list_validation2[2] as $id => $user_static){
			$result = $this->updateTaskValidation($id, 1, 0, 2);
		}

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'FEUILLEDETEMPS_REFUS');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->feuilledetemps->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->feuilledetemps->feuilledetemps_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'FEUILLEDETEMPS_CANCEL');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->feuilledetemps->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->feuilledetemps->feuilledetemps_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'FEUILLEDETEMPS_REOPEN');
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

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("FeuilleDeTemps").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
		$label .= '<br><b>'.$langs->trans('DateDebut').':</b> '.dol_print_date($this->date_debut, '%d-%m-%Y');
		$label .= '<br><b>'.$langs->trans('DateFin').':</b> '.dol_print_date($this->date_fin, '%d-%m-%Y');
		if(empty($notooltip)){
			$user_static = New User($this->db);
			$user_static->fetch($this->fk_user);
			$label .= '<br><b>'.$langs->trans('Utilisateur').':</b> '.$user_static->firstname.' '.$user_static->lastname;
		

			$list_validation1 = $this->listApprover1;
			$i = 0;
			foreach($list_validation1[2] as $id => $user_static){
				if($i == 0){
					$label .= '<br><b>'.$langs->trans('UserValidation1').':</b> ';
					$label .= $user_static->firstname.' '.$user_static->lastname;
				}
				else {
					$label .= ', ';
					$label .= $user_static->firstname.' '.$user_static->lastname;
				}
				$i++;
			}

			$list_validation2 = $this->listApprover2;
			$i = 0;
			foreach($list_validation2[2] as $id => $user_static){
				if($i == 0){
					$label .= '<br><b>'.$langs->trans('UserValidation2').':</b> ';
					$label .= $user_static->firstname.' '.$user_static->lastname;
				}
				else {
					$label .= ', ';
					$label .= $user_static->firstname.' '.$user_static->lastname;
				}
				$i++;
			}
		}

		$url = dol_buildpath('/feuilledetemps/feuilledetemps_card.php', 1).'?id='.$this->id;

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

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowFeuilleDeTemps");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink') {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), 'timesheet_16@feuilledetemps', ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
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
		$hookmanager->initHooks(array('feuilledetempsdao'));
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
		$mode = 5;

		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$user_fdt = new User($this->db);
			$user_fdt->fetch($this->fk_user);
			
			//$langs->load("feuilledetemps@feuilledetemps");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			if($user_fdt->array_options['options_employeur'] == 1) {
				$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Vérifiée');
			}
			else {
				$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Validée');
			}
			$this->labelStatus[self::STATUS_APPROBATION1] = $langs->trans('En attente de la 1ère approbation par la responsable hiérarchique');
			$this->labelStatus[self::STATUS_APPROBATION2] = $langs->trans('En attente de la 2nd approbation par la responsable de projet');
			$this->labelStatus[self::STATUS_VERIFICATION] = $langs->trans('En vérification');
			$this->labelStatus[self::STATUS_EXPORTED] = $langs->trans('Exportée');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			if($user_fdt->array_options['options_employeur'] == 1) {
				$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Vérifiée');
			}
			else {
				$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Validée');
			}
			$this->labelStatusShort[self::STATUS_APPROBATION1] = $langs->trans('1ère Approbation');
			$this->labelStatusShort[self::STATUS_APPROBATION2] = $langs->trans('2nd Approbation');
			$this->labelStatusShort[self::STATUS_VERIFICATION] = $langs->trans('En vérification');
			$this->labelStatusShort[self::STATUS_EXPORTED] = $langs->trans('Exportée');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}
		else if ($status == self::STATUS_APPROBATION1) {
			$statusType = 'status1';
		}
		else if ($status == self::STATUS_APPROBATION2) {
			$statusType = 'status1';
		}
		else if ($status == self::STATUS_VERIFICATION) {
			$statusType = 'status7';
		}
		else if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}
		else if ($status == self::STATUS_EXPORTED) {
			$statusType = 'status4';
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
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.((int) $id);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture) {
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
	public function getLinesArray($byUser = 0)
	{
		$this->lines = array();

		$objectline = new ObservationCompta($this->db);
		$dateMonthBefore = dol_time_plus_duree($this->date_debut, -1, 'm');
		$firstDayMonthAfter = dol_time_plus_duree($this->date_fin, 1, 'd');
		$dateMonthAfter = dol_get_last_day(dol_print_date($firstDayMonthAfter, '%Y'), dol_print_date($firstDayMonthAfter, '%m'));
		
		if(!$byUser) {
			$result = $objectline->fetchAll('DESC', 'date_end', 0, 0, array('customsql'=>"t.fk_user = ".$this->fk_user." AND t.date_end >= '".substr($this->db->idate($this->date_debut), 0, 10)."'"));
		}
		else {
			$result = $objectline->fetchAll('DESC', 'date_end', 0, 0, array('customsql'=>"t.fk_user = ".$this->fk_user));
		}

		if (is_numeric($result)) {
			$this->error = $this->error;
			$this->errors = $this->errors;
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
		$langs->load("feuilledetemps@feuilledetemps");

		if (empty($conf->global->FEUILLEDETEMPS_FEUILLEDETEMPS_ADDON)) {
			$conf->global->FEUILLEDETEMPS_FEUILLEDETEMPS_ADDON = 'mod_feuilledetemps_standard';
		}

		if (!empty($conf->global->FEUILLEDETEMPS_FEUILLEDETEMPS_ADDON)) {
			$mybool = false;

			$file = $conf->global->FEUILLEDETEMPS_FEUILLEDETEMPS_ADDON.".php";
			$classname = $conf->global->FEUILLEDETEMPS_FEUILLEDETEMPS_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/feuilledetemps/");

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

		$langs->load("feuilledetemps@feuilledetemps");

		if (!dol_strlen($modele)) {
			$modele = 'standard_feuilledetemps';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->FEUILLEDETEMPS_ADDON_PDF)) {
				$modele = $conf->global->FEUILLEDETEMPS_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/feuilledetemps/doc/";

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
	public function createAllFeuilleDeTemps()
	{
		global $conf, $langs, $user;

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		if((int)(dol_print_date(dol_now(), '%d') == 1)) {
			$this->db->begin();

			$user_static = new extendedUser3($this->db);
			$fdt = new FeuilleDeTemps($this->db);
			$form = new Form($this->db);
			$users_array = $user_static->get_full_treeIds('u.statut = 1');
			$month = dol_print_date(dol_now(), '%m');
			$fdt->date_debut = dol_get_first_day(dol_print_date(dol_now(), '%Y'), dol_print_date(dol_now(), '%m'));
			$fdt->date_fin = dol_get_last_day(dol_print_date(dol_now(), '%Y'), dol_print_date(dol_now(), '%m'));

			foreach($users_array as $key => $user_id) {
				$fdt_id = $this->ExisteDeja($month, $user_id);
				$user_static->fetch($user_id);

				if(empty($fdt_id) && !in_array(array_search('Exclusion FDT', $form->select_all_categories(Categorie::TYPE_USER, null, null, null, null, 1)), $user_static->getCategoriesCommon(Categorie::TYPE_USER))) {

					$fdt->ref = "FDT_".str_pad($user_static->array_options['options_matricule'], 5, '0', STR_PAD_LEFT).'_'.dol_print_date(dol_now(), '%m%Y');
					$fdt->fk_user = $user_id;
					$fdt->status = 0;

					$result = $fdt->create($user, 0);

					if($result <= 0) {
						$this->error .= "Impossible de créer la feuille de temps de $user_static->firstname $user_static->lastname";
						$error++;
					}
				}
			}			

			$this->db->commit();
		}
		return $error;
	}

	/**
	 *	Vérifie si une FDT a déja été créé sur un mois et un utilisateur donnée
	 *
	 *	@param		date	$mois    		Mois concerné par la feuille de temps
	 * 	@param 		User	$utilisateur	Utilisateur concerné par la feuille de temps
	 *	@return  	int						<=0 if OK, 0=La FDT n'existe pas, l'id de la FDT si elle existe
	 */
	public function ExisteDeja($mois, $utilisateur)
	{
		global $conf, $langs;

		$error = 0;
		$now = dol_now();
		//$this->db->begin();

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_feuilledetemps";
		$sql .= " WHERE DATE_FORMAT(date_debut, '%m') = ".$mois;
		$sql .= " AND DATE_FORMAT(date_fin, '%m') = ".$mois;
		if(gettype($utilisateur) == 'string') {
			$sql .= " AND fk_user = ".$utilisateur;
		}
		else {
			$sql .= " AND fk_user = ".$utilisateur->id;
		}

		dol_syslog(get_class($this)."::ExisteDeja()", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			if($num > 0){
				$obj = $this->db->fetch_object($resql);
				$res = $obj->rowid;
				$this->db->free($resql);
				return $res;
			}
			else {
				$this->db->free($resql);
				return 0;
			}		
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			return -1;
		}
	}

	/**
	 * Renvoi le temps consommé du début de la dernière semaine du mois précédent
	 * @param date	$date_debut		Date de début du mois
	 * @param int 	$user_static	Utilisateur
	 * @return int					Temps consommé de la dernière semaine du mois précédent
	 */
	function getTempsSemainePrecedente($date_debut, $user_static){
		global $conf, $langs;

			$error = 0;
			$now = dol_now();
			$res = 0;
			$tabday = dol_get_first_day_week(dol_print_date($date_debut, '%d'), dol_print_date($date_debut, '%m'), dol_print_date($date_debut, '%Y'));
			$tmpday =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']); 
			//$this->db->begin();

			$sql = "SELECT SUM(element_duration) as temps";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
			$sql .= " WHERE element_date < '".substr($this->db->idate($date_debut), 0, 10)."'";
			$sql .= " AND element_date >= '".substr($this->db->idate($tmpday), 0, 10)."'";
			$sql .= " AND fk_user = ".$user_static->id;
			$sql .= " AND elementtype = 'task'";


			dol_syslog(get_class($this)."::getTempsSemainePrecedente()", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				if($num > 0){
					$obj = $this->db->fetch_object($resql);
					$res = $obj->temps / 60;
					$this->db->free($resql);
					return $res;
				}
				else {
					$this->db->free($resql);
					return 0;
				}		
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				return -1;
			}
	}

	/**
	 * Renvoi le temps consommé du début de la première semaine du mois suivant
	 * @param date	$date_fin		Date de fin du mois	
	 * @param int 	$user_static	Utilisateur
	 * @return int					Temps consommé de la première semaine du mois suivant
	 */
	function getTempsSemaineSuivante($date_fin, $user_static){
		global $conf, $langs;

			$error = 0;
			$now = dol_now();
			$res = 0;
			$tabday = dol_get_next_week(dol_print_date($date_fin, '%d'), '', dol_print_date($date_fin, '%m'), dol_print_date($date_fin, '%Y'));
			$tabday = dol_get_first_day_week($tabday['day'], $tabday['month'], $tabday['year']);
			$tmpday =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']); 
			//$this->db->begin();

			$sql = "SELECT SUM(element_duration) as temps";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
			$sql .= " WHERE element_date > '".substr($this->db->idate($date_fin), 0, 10)."'";
			$sql .= " AND element_date < '".substr($this->db->idate($tmpday), 0, 10)."'";
			$sql .= " AND fk_user = ".$user_static->id;
			$sql .= " AND elementtype = 'task'";

			dol_syslog(get_class($this)."::getTempsSemaineSuivante()", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				if($num > 0){
					$obj = $this->db->fetch_object($resql);
					$res = $obj->temps / 60;
					$this->db->free($resql);
					return $res;
				}
				else {
					$this->db->free($resql);
					return 0;
				}		
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				return -1;
			}
	}

	/**
	 * Renvoi les heures sup à 25% du début de la dernière semaine du mois précédent
	 * @param date	$date_debut		Date de début du mois
	 * @param int 	$user_static	Utilisateur
	 * @return int					Heure Sup à 25% de la dernière semaine du mois précédent
	 */
	function getHS25SemainePrecedente($date_debut, $user_static){
		global $conf, $langs;

			$error = 0;
			$now = dol_now();
			$res = 0;
			$tabday = dol_get_first_day_week(dol_print_date($date_debut, '%d'), dol_print_date($date_debut, '%m'), dol_print_date($date_debut, '%Y'));
			$tmpday =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']); 
			$this->db->begin();

			$sql = "SELECT SUM(f.heure_sup_25_duration) as temps";
			$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as f";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as t ON t.rowid=f.fk_projet_task_time";
			$sql .= " WHERE t.element_date < '".substr($this->db->idate($date_debut), 0, 10)."'";
			$sql .= " AND t.element_date >= '".substr($this->db->idate($tmpday), 0, 10)."'";
			$sql .= " AND t.fk_user = ".$user_static->id;
			$sql .= " AND t.elementtype = 'task'";


			dol_syslog(get_class($this)."::getHS25SemainePrecedente()", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				if($num > 0){
					$obj = $this->db->fetch_object($resql);
					$res = $obj->temps / 60;
					$this->db->free($resql);
					return $res;
				}
				else {
					$this->db->free($resql);
					return 0;
				}		
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				return -1;
			}
	}

	/**
	 * Renvoi les heure sup à 25% du début de la première semaine du mois suivant
	 * @param date	$date_fin		Date de fin du mois	
	 * @param int 	$user_static	Utilisateur
	 * @return int					Heure Sup à 25% de la première semaine du mois suivant
	 */
	function getHS25SemaineSuivante($date_fin, $user_static){
		global $conf, $langs;

			$error = 0;
			$now = dol_now();
			$res = 0;
			$tabday = dol_get_next_week(dol_print_date($date_fin, '%d'), '', dol_print_date($date_fin, '%m'), dol_print_date($date_fin, '%Y'));
			$tabday = dol_get_first_day_week($tabday['day'], $tabday['month'], $tabday['year']);
			$tmpday =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']); 
			$this->db->begin();

			$sql = "SELECT SUM(f.heure_sup_25_duration) as temps";
			$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as f";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as t ON t.rowid=f.fk_projet_task_time";
			$sql .= " WHERE element_date > '".substr($this->db->idate($date_fin), 0, 10)."'";
			$sql .= " AND element_date < '".substr($this->db->idate($tmpday), 0, 10)."'";
			$sql .= " AND fk_user = ".$user_static->id;
			$sql .= " AND t.elementtype = 'task'";

			dol_syslog(get_class($this)."::getHS25SemaineSuivante()", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				if($num > 0){
					$obj = $this->db->fetch_object($resql);
					$res = $obj->temps / 60;
					$this->db->free($resql);
					return $res;
				}
				else {
					$this->db->free($resql);
					return 0;
				}		
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				return -1;
			}
	}

	/**
	 * Renvoi les heures sup à 50% du début de la dernière semaine du mois précédent
	 * @param date	$date_debut		Date de début du mois
	 * @param int 	$user_static	Utilisateur
	 * @return int					Heure Sup à 50% de la dernière semaine du mois précédent
	 */
	function getHS50SemainePrecedente($date_debut, $user_static){
		global $conf, $langs;

			$error = 0;
			$now = dol_now();
			$res = 0;
			$tabday = dol_get_first_day_week(dol_print_date($date_debut, '%d'), dol_print_date($date_debut, '%m'), dol_print_date($date_debut, '%Y'));
			$tmpday =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']); 
			$this->db->begin();

			$sql = "SELECT SUM(f.heure_sup_50_duration) as temps";
			$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as f";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as t ON t.rowid=f.fk_projet_task_time";
			$sql .= " WHERE t.element_date < '".substr($this->db->idate($date_debut), 0, 10)."'";
			$sql .= " AND t.element_date >= '".substr($this->db->idate($tmpday), 0, 10)."'";
			$sql .= " AND t.fk_user = ".$user_static->id;
			$sql .= " AND t.elementtype = 'task'";

			dol_syslog(get_class($this)."::getHS50SemainePrecedente()", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				if($num > 0){
					$obj = $this->db->fetch_object($resql);
					$res = $obj->temps / 60;
					$this->db->free($resql);
					return $res;
				}
				else {
					$this->db->free($resql);
					return 0;
				}		
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				return -1;
			}
	}

	/**
	 * Renvoi les heure sup à 50% du début de la première semaine du mois suivant
	 * @param date	$date_fin		Date de fin du mois	
	 * @param int 	$user_static	Utilisateur
	 * @return int					Heure Sup à 50% de la première semaine du mois suivant
	 */
	function getHS50SemaineSuivante($date_fin, $user_static){
		global $conf, $langs;

			$error = 0;
			$now = dol_now();
			$res = 0;
			$tabday = dol_get_next_week(dol_print_date($date_fin, '%d'), '', dol_print_date($date_fin, '%m'), dol_print_date($date_fin, '%Y'));
			$tabday = dol_get_first_day_week($tabday['day'], $tabday['month'], $tabday['year']);
			$tmpday =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']); 
			$this->db->begin();

			$sql = "SELECT SUM(f.heure_sup_50_duration) as temps";
			$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as f";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as t ON t.rowid=f.fk_projet_task_time";
			$sql .= " WHERE element_date > '".substr($this->db->idate($date_fin), 0, 10)."'";
			$sql .= " AND element_date < '".substr($this->db->idate($tmpday), 0, 10)."'";
			$sql .= " AND fk_user = ".$user_static->id;
			$sql .= " AND elementtype = 'task'";

			dol_syslog(get_class($this)."::getHS50SemaineSuivante()", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				if($num > 0){
					$obj = $this->db->fetch_object($resql);
					$res = $obj->temps / 60;
					$this->db->free($resql);
					return $res;
				}
				else {
					$this->db->free($resql);
					return 0;
				}		
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				return -1;
			}
	}

	/**
	 * Renvoi le temps consommé de la semaine du jour spécifié
	 * @param date	$date			Date 
	 * @param User 	$user_static	Utilisateur
	 * @return int					Temps consommé de la semaine 
	 */
	function getTimeSpentSemaine($date, $user_static){
		global $conf, $langs;

			$error = 0;
			$now = dol_now();
			$res = 0;
			/*$tabday = dol_get_next_week(dol_print_date($date, '%d'), '', dol_print_date($date, '%m'), dol_print_date($date, '%Y'));
			$tabday = dol_get_first_day_week($tabday['day'], $tabday['month'], $tabday['year']);*/
			//$tmpday_fin =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']);
			$tabday = dol_get_first_day_week(dol_print_date($date, '%d'), dol_print_date($date, '%m'), dol_print_date($date, '%Y'));
			$tmpday_debut =  dol_mktime(-1, -1, -1, $tabday['first_month'], $tabday['first_day'], $tabday['first_year']);  
			$tmpday_fin = dol_time_plus_duree($tmpday_debut, 6, 'd');

			$sql = "SELECT SUM(element_duration) as temps";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
			$sql .= " WHERE element_date >= '".substr($this->db->idate($tmpday_debut), 0, 10)."'";
			$sql .= " AND element_date < '".substr($this->db->idate($tmpday_fin), 0, 10)."'";
			if(is_int($user_static)) {
				$sql .= " AND fk_user = ".$user_static;
			}
			else {
				$sql .= " AND fk_user = ".$user_static->id;
			}
			$sql .= " AND elementtype = 'task'";

			dol_syslog(get_class($this)."::getTimeSpentSemaine()", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				if($num > 0){
					$obj = $this->db->fetch_object($resql);
					$res = $obj->temps / 3600;
					$this->db->free($resql);
					return $res;
				}
				else {
					$this->db->free($resql);
					return 0;
				}		
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				return -1;
			}
	}

	/**
	 * Est-ce que la feuille de temps comporte des heures manquantes
	 * @return int					1 si présence de semaine avec des heures en moins, 0 sinon
	 */
	// function semaineHeuresManquantes(){
	// 	global $conf, $langs;

	// 	$holiday = new Holiday($this->db);
	// 	$error = 0;
	// 	$now = dol_now();
	// 	$res = 0;
		
	// 	$month = dol_print_date($this->date_debut, '%m');
	// 	$year = dol_print_date($this->date_debut, '%Y');
	// 	$first_day  = 21;
	// 	if($month != '01')
	// 		$first_month = $month-1;
	// 	else 
	// 		$first_month = 12;
	// 	if($month != '01')
	// 		$first_year = $year;
	// 	else 
	// 		$first_year = $year-1;

	// 	$firstdate = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year);
	// 	$firstdate = dol_get_first_day_week(dol_print_date($firstdate, '%d'), dol_print_date($firstdate, '%m'), dol_print_date($firstdate, '%Y'));
	// 	$firstdate =  dol_mktime(-1, -1, -1, $firstdate['first_month'], $firstdate['first_day'], $firstdate['first_year']);  
	// 	$lastdate = $this->date_fin;
	// 	if(dol_print_date($lastdate, '%a') == 'Dim'){
	// 		$lastdate = $lastdate;
	// 	}
	// 	else {
	// 		$lastdate = dol_get_first_day_week(dol_print_date($lastdate, '%d'), dol_print_date($lastdate, '%m'), dol_print_date($lastdate, '%Y'));
	// 		$lastdate =  dol_mktime(-1, -1, -1, $lastdate['first_month'], $lastdate['first_day'], $lastdate['first_year']);  
	// 	}

	// 	//$this->db->begin();

	// 	$ecart_jour = num_between_day($firstdate, $lastdate, 1);
	// 	$tmpday = $firstdate;
	// 	$semaine = 0;
	// 	$heure_faite = array();
	// 	$heure_a_faire = array();
	// 	dol_syslog(get_class($this)."::semaineHeuresManquantes()", LOG_DEBUG);
	// 	for($i=1; $i<=$ecart_jour; $i++){
	// 		$sql = "SELECT SUM(element_duration) as time";
	// 		$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
	// 		$sql .= " WHERE element_date = '".$this->db->idate($tmpday)."'";
	// 		$sql .= " AND fk_user = ".(int)$this->fk_user;
	// 		$sql .= " AND elementtype = 'task'";

	// 		$resql = $this->db->query($sql);

	// 		if ($resql) {
	// 			if($obj = $this->db->fetch_object($resql)){
	// 				$heure_faite[$semaine] += $obj->time / 3600;
	// 			}
	// 			//$this->db->free($resql);
	// 		} else {
	// 			$this->errors[] = 'Error '.$this->db->lasterror();
	// 			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
	// 			return -1;
	// 		}

	// 		if(dol_print_date($tmpday, '%a') != 'Sam' && dol_print_date($tmpday, '%a') != 'Dim'){
	// 			$statusofholidaytocheck = Holiday::STATUS_APPROVED2;
	// 			$isavailablefordayanduser = $holiday->verifDateHolidayForTimestamp($this->fk_user, $tmpday, $statusofholidaytocheck);
	// 			$test = num_public_holiday($tmpday, $tmpday + 86400, $mysoc->country_code, 0, 0, 0, 0);
	// 			if(($isavailablefordayanduser['morning'] == true && $isavailablefordayanduser['afternoon'] == true) && !$test){
	// 				$heure_a_faire[$semaine] += $conf->global->HEURE_JOUR;
	// 			}
	// 		}

	// 		$tmpday = dol_time_plus_duree($firstdate, $i, 'd');
	// 		if(dol_print_date($tmpday, '%a') == 'Lun'){
	// 			$semaine ++;
	// 		}
	// 	}

	// 	for($u=0; $u<=$semaine; $u++){
	// 		if($heure_a_faire[$u] > $heure_faite[$u]){
	// 			return 1;
	// 		}
	// 	}
	// 	return 0;
	// }

	/**
	 * Heures faites et à faire durant chaque semaine de la feuille de temps
	 * @return array()
	 */
	// function timeDoneAndToDo(){
	// 	global $conf, $langs;

	// 	$holiday = new Holiday($this->db);
	// 	$error = 0;
	// 	$now = dol_now();
	// 	$result = array("ToDo" => array(), "Done" => array());
		
	// 	$month = dol_print_date($this->date_debut, '%m');
	// 	$year = dol_print_date($this->date_debut, '%Y');
	// 	$first_day  = 21;
	// 	if($month != '01')
	// 		$first_month = $month-1;
	// 	else 
	// 		$first_month = 12;
	// 	if($month != '01')
	// 		$first_year = $year;
	// 	else 
	// 		$first_year = $year-1;

	// 	$firstdate = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year);
	// 	$firstdate = dol_get_first_day_week(dol_print_date($firstdate, '%d'), dol_print_date($firstdate, '%m'), dol_print_date($firstdate, '%Y'));
	// 	$firstdate =  dol_mktime(-1, -1, -1, $firstdate['first_month'], $firstdate['first_day'], $firstdate['first_year']);  
	// 	$lastdate = $this->date_fin;
	// 	if(dol_print_date($lastdate, '%a') == 'Dim'){
	// 		$lastdate = $lastdate;
	// 	}
	// 	else {
	// 		$lastdate = dol_get_first_day_week(dol_print_date($lastdate, '%d'), dol_print_date($lastdate, '%m'), dol_print_date($lastdate, '%Y'));
	// 		$lastdate =  dol_mktime(-1, -1, -1, $lastdate['first_month'], $lastdate['first_day'], $lastdate['first_year']);  
	// 	}

	// 	//$this->db->begin();

	// 	$ecart_jour = num_between_day($firstdate, $lastdate, 1);
	// 	$tmpday = $firstdate;
	// 	dol_syslog(get_class($this)."::timeDoneAndToDo()", LOG_DEBUG);

	// 	for($i = 1; $i <= $ecart_jour; $i++){
	// 		$sql = "SELECT SUM(element_duration) as time, DATE_FORMAT('".$this->db->idate($tmpday)."', '%u') as weeknumber";
	// 		$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
	// 		$sql .= " WHERE element_date = '".$this->db->idate($tmpday)."'";
	// 		$sql .= " AND fk_user = ".(int)$this->fk_user;
	// 		$sql .= " AND elementtype = 'task'";

	// 		$resql = $this->db->query($sql);

	// 		if ($resql) {
	// 			if($obj = $this->db->fetch_object($resql)){
	// 				$result["Done"][(int)$obj->weeknumber] += $obj->time / 3600;
	// 			}
	// 			//$this->db->free($resql);
	// 		} else {
	// 			$this->errors[] = 'Error '.$this->db->lasterror();
	// 			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
	// 			return -1;
	// 		}

	// 		if(dol_print_date($tmpday, '%a') != 'Sam' && dol_print_date($tmpday, '%a') != 'Dim'){
	// 			$statusofholidaytocheck = Holiday::STATUS_APPROVED2;
	// 			$isavailablefordayanduser = $holiday->verifDateHolidayForTimestamp($this->fk_user, $tmpday, $statusofholidaytocheck);
	// 			$test = num_public_holiday($tmpday, $tmpday + 86400, $mysoc->country_code, 0, 0, 0, 0);
	// 			if(($isavailablefordayanduser['morning'] == true && $isavailablefordayanduser['afternoon'] == true) && !$test){
	// 				$result["ToDo"][(int)$obj->weeknumber] += $conf->global->HEURE_JOUR;
	// 			}
	// 		}

	// 		$tmpday = dol_time_plus_duree($firstdate, $i, 'd');
	// 	}

	// 	return $result;
	// }

	function timeHolidayWeek($user_id, $firstdate = '', $lastdate = '') {
		global $conf, $langs, $mysoc;

		$result = array();
		
		$holiday = new extendedHoliday($this->db);

		if(empty($firstdate)) {
			$firstdate = dol_time_plus_duree($this->date_debut, -$conf->global->JOUR_ANTICIPES, 'd');
			$firstdate = dol_get_first_day_week(dol_print_date($firstdate, '%d'), dol_print_date($firstdate, '%m'), dol_print_date($firstdate, '%Y'));
			$firstdate =  dol_mktime(-1, -1, -1, $firstdate['first_month'], $firstdate['first_day'], $firstdate['first_year']);
		}
		if(empty($lastdate)) {
			$lastdate = $this->date_fin;

			if(dol_print_date($lastdate, '%a') != 'Dim'){
				$lastdate = dol_time_plus_duree($lastdate, 1, 'w');
				$lastdate = dol_get_first_day_week(dol_print_date($lastdate, '%d'), dol_print_date($lastdate, '%m'), dol_print_date($lastdate, '%Y'));
				$lastdate =  dol_mktime(-1, -1, -1, $lastdate['first_month'], $lastdate['first_day'], $lastdate['first_year']);  
			}
		}

		$nb_jour = num_between_day($firstdate, $lastdate+3600); 

		$firstdaygmt = dol_mktime(0, 0, 0, dol_print_date($firstdate, '%m'), dol_print_date($firstdate, '%d'), dol_print_date($firstdate, '%Y'), 'gmt');

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
		$userField = new UserField($this->db);
		$userField->id = $user_id;
		$userField->table_element = 'donneesrh_Positionetcoefficient';
		$userField->fetch_optionals();

		for ($idw = 0; $idw < $nb_jour; $idw++) { 
			$tmpday = dol_time_plus_duree($firstdate, $idw, 'd');
			$tmpdaygmt = dol_time_plus_duree($firstdaygmt, 24*$idw, 'h'); // $firstdaytoshow is a date with hours = 0

			if (dol_print_date($tmpday, '%a') != 'Sam' && dol_print_date($tmpday, '%a') != 'Dim') {
				$statusofholidaytocheck =  array(Holiday::STATUS_VALIDATED, Holiday::STATUS_APPROVED2,  Holiday::STATUS_APPROVED1);
				$isavailablefordayanduser = $holiday->verifDateHolidayForTimestamp($user_id, $tmpday, $statusofholidaytocheck, array(4));
				$test = num_public_holiday($tmpdaygmt, $tmpdaygmt + 86400, $mysoc->country_code, 0, 0, 0, 0);

				if($test) { // Jour feriés
					if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt'])) {
						$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (1 * 7) : (1 * 7));
					} 
					else {
						$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (1 * $conf->global->HEURE_JOUR) : (1 * $conf->global->HEURE_JOUR));
					}
				}
				elseif(sizeof($isavailablefordayanduser['rowid']) > 1) {
					for($i = 0; $i < sizeof($isavailablefordayanduser['rowid']); $i++) {
						if($isavailablefordayanduser['hour'][$i] > 0){ // Congés en heure
							if($isavailablefordayanduser['nb_jour'][$i] > 1) {
								$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + ($isavailablefordayanduser['hour'][$i] / $isavailablefordayanduser['nb_jour'][$i] / 3600) : ($isavailablefordayanduser['hour'][$i] / $isavailablefordayanduser['nb_jour'][$i] / 3600));
							}
							else {
								$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + ($isavailablefordayanduser['hour'][$i] / 3600) : ($isavailablefordayanduser['hour'][$i] / 3600));
							}
						}
						elseif(dol_print_date($tmpday, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt'])) {
							$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (0.5 * 7) : (0.5 * 7));
						}
						else {
							if($isavailablefordayanduser['droit_rtt'][$i]) {
								$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (0.5 * $conf->global->HEURE_JOUR) : (0.5 * $conf->global->HEURE_JOUR));
							}
							else {
								$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + ($conf->global->HEURE_DEMIJOUR_NORTT) : ($conf->global->HEURE_DEMIJOUR_NORTT));
							}
						}
					}
				}
				elseif(($isavailablefordayanduser['morning'] == false || $isavailablefordayanduser['afternoon'] == false) && $isavailablefordayanduser['hour'][0] > 0){ // Congés en heure
					if($isavailablefordayanduser['nb_jour'][0] > 1) {
						$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + ($isavailablefordayanduser['hour'][0] / $isavailablefordayanduser['nb_jour'][0] / 3600) : ($isavailablefordayanduser['hour'][0] / $isavailablefordayanduser['nb_jour'][0] / 3600));
					}
					else {
						$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + ($isavailablefordayanduser['hour'][0] / 3600) : ($isavailablefordayanduser['hour'][0] / 3600));
					}
				}
				elseif($isavailablefordayanduser['morning'] == false && $isavailablefordayanduser['afternoon'] == false) { // Congés journées entières
					if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt'])) {
						$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (1 * 7) : (1 * 7));
					}
					else {
						$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (1 * $conf->global->HEURE_JOUR) : (1 * $conf->global->HEURE_JOUR));
					}
				}
				elseif($isavailablefordayanduser['morning'] == false || $isavailablefordayanduser['afternoon'] == false) { // Congés demi journées
					if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt'])) {
						$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (0.5 * 7) : (0.5 * 7));
					}
					else {
						if($isavailablefordayanduser['droit_rtt'][0]) {
							$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + (0.5 * $conf->global->HEURE_JOUR) : (0.5 * $conf->global->HEURE_JOUR));
						}
						else {
							$result[(int)date("W", $tmpday)] = ($result[(int)date("W", $tmpday)] > 0 ? $result[(int)date("W", $tmpday)] + ($conf->global->HEURE_DEMIJOUR_NORTT) : ($conf->global->HEURE_DEMIJOUR_NORTT));
						}
					}
				}
			}
		}

		foreach($result as $week => $time) {
			if($userField->array_options['options_horairehebdomadaire'] > 0 && $time > $userField->array_options['options_horairehebdomadaire']) {
				$result[$week] = $userField->array_options['options_horairehebdomadaire'];
			}
		}

		return $result;
	}

	function timeHolidayForExport($firstdate, $lastdate) {
		global $conf, $langs, $mysoc;

		$result = array();
		
		$holiday = new extendedHoliday($this->db);

		$nb_jour = num_between_day($firstdate, $lastdate+3600); 
		$firstdaygmt = dol_mktime(0, 0, 0, dol_print_date($firstdate, '%m'), dol_print_date($firstdate, '%d'), dol_print_date($firstdate, '%Y'), 'gmt');

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
		$userField = new UserField($this->db);
		$userField->table_element = 'donneesrh_Positionetcoefficient';
		$userField->fetch_optionals();

		$userfield_load = array();
		$userfield_pasdroitrtt = array();
		$userfield_datedepart = array();

		for ($idw = 0; $idw < $nb_jour; $idw++) { 
			$tmpday = dol_time_plus_duree($firstdate, $idw, 'd');
			$tmpdaygmt = dol_time_plus_duree($firstdaygmt, 24*$idw, 'h'); // $firstdaytoshow is a date with hours = 0
			$ferie = num_public_holiday($tmpdaygmt, $tmpdaygmt + 86400, $mysoc->country_code, 0, 0, 0, 0);

			if (dol_print_date($tmpday, '%a') != 'Sam' && dol_print_date($tmpday, '%a') != 'Dim' && !$ferie) {
				$statusofholidaytocheck =  array(Holiday::STATUS_VALIDATED, Holiday::STATUS_APPROVED2,  Holiday::STATUS_APPROVED1);
				$isavailablefordayanduser = $holiday->verifDateHolidayForTimestampForAllUser($tmpday, $statusofholidaytocheck, array(4));

				foreach($isavailablefordayanduser['user_id'] as $user_id) {
					if(!$userfield_load[$user_id]) {
						$userField->id = $user_id;
						$userField->fetch_optionals();

						$userfield_load[$user_id] = 1;
						$userfield_pasdroitrtt[$user_id] = $userField->array_options['options_pasdroitrtt'];
						$userfield_datedepart[$user_id] = $userField->array_options['options_datedepart'];
					}

					if(!empty($userfield_datedepart[$user_id]) && $userfield_datedepart[$user_id] < $tmpday) {
						continue;
					}

					if(sizeof($isavailablefordayanduser['rowid'][$user_id]) > 1) {
						for($i = 0; $i < sizeof($isavailablefordayanduser['rowid'][$user_id]); $i++) {
							if($isavailablefordayanduser['in_hour'][$user_id][$i] == 1){ // Congés en heure
								if($isavailablefordayanduser['hour'][$user_id][$i] > 0){ 
									if($isavailablefordayanduser['nb_jour'][$user_id][$isavailablefordayanduser['rowid'][$user_id][$i]] > 1) {
										$result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] + ($isavailablefordayanduser['hour'][$user_id][$i] / $isavailablefordayanduser['nb_jour'][$user_id][$isavailablefordayanduser['rowid'][$user_id][$i]] / 3600) : ($isavailablefordayanduser['hour'][$user_id][$i] / $isavailablefordayanduser['nb_jour'][$user_id][$isavailablefordayanduser['rowid'][$user_id][$i]] / 3600));
									}
									else {
										$result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] + ($isavailablefordayanduser['hour'][$user_id][$i] / 3600) : ($isavailablefordayanduser['hour'][$user_id][$i] / 3600));
									}
								}
								else {
									if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt'])) {
										$result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] + (0.5 * 7) : (0.5 * 7));
									}
									else {
										if($isavailablefordayanduser['droit_rtt'][0]) {
											$result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] + (0.5 * $conf->global->HEURE_JOUR) : (0.5 * $conf->global->HEURE_JOUR));
										}
										else {
											$result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] + ($conf->global->HEURE_DEMIJOUR_NORTT) : ($conf->global->HEURE_DEMIJOUR_NORTT));
										}
									}
								}
							}
							else {
								$result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][$i]] + 0.5 : 0.5);
							}
						}
					}
					elseif(($isavailablefordayanduser['morning'][$user_id] == false || $isavailablefordayanduser['afternoon'][$user_id] == false) && $isavailablefordayanduser['in_hour'][$user_id][0] == 1){ // Congés en heure
						if($isavailablefordayanduser['hour'][$user_id][0] > 0){ 
							if($isavailablefordayanduser['nb_jour'][$user_id][$isavailablefordayanduser['rowid'][$user_id][0]] > 1) {
								$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + ($isavailablefordayanduser['hour'][$user_id][0] / $isavailablefordayanduser['nb_jour'][$user_id][$isavailablefordayanduser['rowid'][$user_id][0]] / 3600) : ($isavailablefordayanduser['hour'][$user_id][0] / $isavailablefordayanduser['nb_jour'][$user_id][$isavailablefordayanduser['rowid'][$user_id][0]] / 3600));
							}
							else {
								$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + ($isavailablefordayanduser['hour'][$user_id][0] / 3600) : ($isavailablefordayanduser['hour'][$user_id][0] / 3600));
							}
						}
						else {
							if($isavailablefordayanduser['morning'][$user_id] == false && $isavailablefordayanduser['afternoon'][$user_id] == false) {
								if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt'])) {
									$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + (1 * 7) : (1 * 7));
								}
								else {
									$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + (1 * $conf->global->HEURE_JOUR) : (1 * $conf->global->HEURE_JOUR));
								}
							}
							else {
								if(dol_print_date($tmpday, '%Y-%m-%d') < '2024-07-01' || !empty($userField->array_options['options_pasdroitrtt'])) {
									$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + 3.5 : 3.5);
								}
								else {
									if($isavailablefordayanduser['droit_rtt'][0]) {
										$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + (0.5 * $conf->global->HEURE_JOUR) : (0.5 * $conf->global->HEURE_JOUR));
									}
									else {
										$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + ($conf->global->HEURE_DEMIJOUR_NORTT) : ($conf->global->HEURE_DEMIJOUR_NORTT));
									}
								}
							}
						}
					}
					elseif($isavailablefordayanduser['morning'][$user_id] == false && $isavailablefordayanduser['afternoon'][$user_id] == false) { // Congés journées entières
						$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + 1 : 1);
					}
					elseif($isavailablefordayanduser['morning'][$user_id] == false || $isavailablefordayanduser['afternoon'][$user_id] == false) { // Congés demi journées
						$result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] = ($result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] > 0 ? $result[$user_id][$isavailablefordayanduser['code'][$user_id][0]] + 0.5 : 0.5);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Heures faites et à faire durant chaque semaine de la feuille de temps
	 * @return array()
	 */
	function timeDoneByWeek($user_id, $firstdate = '', $lastdate = ''){
		global $conf; 

		$result = array();
		
		if(empty($firstdate)) {
			$firstdate = dol_time_plus_duree($this->date_debut, -$conf->global->JOUR_ANTICIPES, 'd');
			$firstdate = dol_get_first_day_week(dol_print_date($firstdate, '%d'), dol_print_date($firstdate, '%m'), dol_print_date($firstdate, '%Y'));
			$firstdate =  dol_mktime(-1, -1, -1, $firstdate['first_month'], $firstdate['first_day'], $firstdate['first_year']);  
		}
		if(empty($lastdate)) {
			$lastdate = $this->date_fin;
			if(dol_print_date($lastdate, '%a') != 'Dim'){
				$lastdate = dol_time_plus_duree($lastdate, 1, 'w');
				$lastdate = dol_get_first_day_week(dol_print_date($lastdate, '%d'), dol_print_date($lastdate, '%m'), dol_print_date($lastdate, '%Y'));
				$lastdate = dol_mktime(-1, -1, -1, $lastdate['first_month'], $lastdate['first_day'], $lastdate['first_year']);  
			}
		}
		
		$sql = "SELECT DATE_FORMAT(element_date, '%u') as week_number, SUM(element_duration)/3600 as temps";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
		$sql .= " WHERE element_date >= '".$this->db->idate($firstdate)."'";
		$sql .= " AND element_date < '".$this->db->idate($lastdate)."'";
		$sql .= " AND fk_user = ".$user_id;
		$sql .= " AND elementtype = 'task'";
		$sql .= " GROUP BY DATE_FORMAT(element_date, '%u')";

		dol_syslog(get_class($this)."::timeDoneByWeek()", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			while($obj = $this->db->fetch_object($resql)) {
				$result[$obj->week_number] = $obj->temps;
			}
			$this->db->free($resql);
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			return -1;
		}

		return $result;
	}

	function timeDoneByDay($user_id, $firstdate = '', $lastdate = ''){
		global $conf; 

		$result = array();
		
		if(empty($firstdate)) {
			$firstdate = dol_time_plus_duree($this->date_debut, -$conf->global->JOUR_ANTICIPES, 'd');
			$firstdate = dol_get_first_day_week(dol_print_date($firstdate, '%d'), dol_print_date($firstdate, '%m'), dol_print_date($firstdate, '%Y'));
			$firstdate =  dol_mktime(-1, -1, -1, $firstdate['first_month'], $firstdate['first_day'], $firstdate['first_year']);  
		}
		if(empty($lastdate)) {
			$lastdate = $this->date_fin;
			if(dol_print_date($lastdate, '%a') != 'Dim'){
				$lastdate = dol_time_plus_duree($lastdate, 1, 'w');
				$lastdate = dol_get_first_day_week(dol_print_date($lastdate, '%d'), dol_print_date($lastdate, '%m'), dol_print_date($lastdate, '%Y'));
				$lastdate = dol_mktime(-1, -1, -1, $lastdate['first_month'], $lastdate['first_day'], $lastdate['first_year']);  
			}
		}
		
		$sql = "SELECT DATE_FORMAT(element_date, '%d/%m/%Y') as date, SUM(element_duration)/3600 as temps";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
		$sql .= " WHERE element_date >= '".$this->db->idate($firstdate)."'";
		$sql .= " AND element_date < '".$this->db->idate($lastdate)."'";
		$sql .= " AND fk_user = ".$user_id;
		$sql .= " AND elementtype = 'task'";
		$sql .= " GROUP BY DATE_FORMAT(element_date, '%d/%m/%Y')";

		dol_syslog(get_class($this)."::timeDoneByDay()", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			while($obj = $this->db->fetch_object($resql)) {
				$result[$obj->date] = $obj->temps;
			}
			$this->db->free($resql);
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			return -1;
		}

		return $result;
	}


	/**
	 * 	Return les nombre de Temps consommés sur un projet
	 *
	 * 	@param	int		$idprojet			Id du projet
	 * 	@return	int							Nombre de Temps consommés
	 */
	public function getNbTC($idprojet)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT COUNT(t.rowid) as nb_tc";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t,";
		$sql .= " ".MAIN_DB_PREFIX."projet_task as pt";
		$sql .= " WHERE t.fk_element = pt.rowid AND pt.fk_projet = ".$idprojet;
		$sql .= " AND t.elementtype = 'task'";

		dol_syslog(get_class($this)."::getNbTC", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if($num > 0){ 
				$obj = $this->db->fetch_object($resql);
				$this->db->free($resql);
				return $obj->nb_tc;
			}
			$this->db->free($resql);
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Return les nombre de Temps consommés sur une Task
	 *
	 * 	@param	int		$idtask			Id de la Task
	 * 	@return	int							Nombre de Temps consommés
	 */
	public function getNbTCTask($idtask)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT COUNT(t.rowid) as nb_tc";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " WHERE t.fk_element = ".$idtask;
		$sql .= " AND t.elementtype = 'task'";

		dol_syslog(get_class($this)."::getNbTCTask", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if($num > 0){ 
				$obj = $this->db->fetch_object($resql);
				$this->db->free($resql);
				return $obj->nb_tc;
			}
			$this->db->free($resql);
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *
	 *	@param		int 	$userid     		Id de l'utilisateur
	 *  @param		int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *  @param 		int 	$validation_number 	Validation number
	 *	@return  	int							<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function createTaskValidation($userid, $notrigger = 1, $validation_number)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id) && $userid > 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX.'feuilledetemps_task_validation';
			$sql .= " VALUES(".$this->id;
			$sql .= ", ".((int) $userid).", 0, ".((int) $validation_number).")";

			dol_syslog(get_class($this)."::createTaskValidation()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			if ($validation_number == 1) {
				$this->listApprover1 = $this->listApprover('', 1);
			}
			elseif ($validation_number == 2) {
				$this->listApprover2 = $this->listApprover('', 2);
			}

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *	@param		int 	$userid     		Id de l'utilisateur
	 *  @param		int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *  @param		int		$validation			Validation ou non validation
	 * 	@param		int		$validation_number	Validation Number
	 *	@return  	int							<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function updateTaskValidation($userid, $notrigger = 0, $validation, $validation_number)
	{
		global $conf, $langs, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id) && $userid > 0) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.'feuilledetemps_task_validation';
			$sql .= " SET validation = ".$validation;
			$sql .= " WHERE fk_feuilledetemps = ".(int)$this->id;
			$sql .= " AND fk_user_validation = ".(int)$userid;
			$sql .= " AND validation_number = ".(int)$validation_number;

			dol_syslog(get_class($this)."::updateTaskValidation()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				$this->actionmsg2 = $langs->transnoentities("FEUILLEDETEMPS_TASKVALIDATIONInDolibarr", $this->ref);
				$this->actionmsg = $langs->transnoentities("FEUILLEDETEMPS_TASKVALIDATIONInDolibarr", $this->ref);

				// Call trigger
				$result = $this->call_trigger('FEUILLEDETEMPS_TASKVALIDATION', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			if ($validation_number == 1) {
				$this->listApprover1 = $this->listApprover('', 1);
			}
			elseif ($validation_number == 2) {
				$this->listApprover2 = $this->listApprover('', 2);
			}

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function deleteAllTaskValidation()
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.'feuilledetemps_task_validation';
			$sql .= " WHERE fk_feuilledetemps = ".(int)$this->id;

			dol_syslog(get_class($this)."::deleteAllTaskValidation()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			$this->listApprover1 = $this->listApprover('', 1);
			$this->listApprover2 = $this->listApprover('', 2);

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *  @param		int						$id de l'utilisateur qu'il faut supprimer de la table
	 *  @param		int		$validation_number	Validation Number
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function deleteTaskValidation($userid, $validation_number)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.'feuilledetemps_task_validation';
			$sql .= " WHERE fk_feuilledetemps = ".(int)$this->id;
			$sql .= " and fk_user_validation = ".(int)$userid;
			$sql .= " and validation_number = ".(int)$validation_number;

			dol_syslog(get_class($this)."::deleteTaskValidation()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			if ($validation_number == 1) {
				$this->listApprover1 = $this->listApprover('', 1);
			}
			elseif ($validation_number == 2) {
				$this->listApprover2 = $this->listApprover('', 2);
			}
			
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Return la liste des approbateurs d'une FDT
	 *
	 * 	@param	string	$excludefilter		Filter to exclude. Do not use here a string coming from user input.
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 *  @param	int		$validation_number	Validation Number
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listApprover($excludefilter = '', $validation_number)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT u.rowid, t.validation";
		
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		if (!empty($this->id)) {
			$sql .= ", ".MAIN_DB_PREFIX."feuilledetemps_task_validation as t";
		}
		$sql .= " WHERE 1 = 1";
		$sql .= " AND t.fk_user_validation = u.rowid";
		$sql .= " AND t.validation_number = ".(int)$validation_number;
		if (!empty($this->id)) {
			$sql .= " AND t.fk_feuilledetemps = ".((int) $this->id);
		}
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= " AND u.entity IS NOT NULL";
		} else {
			$sql .= " AND u.entity IN (0,".$conf->entity.")";
		}
		if (!empty($excludefilter)) {
			$sql .= ' AND ('.$excludefilter.')';
		}

		dol_syslog(get_class($this)."::listApprover", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$newuser = new User($this->db);
				$newuser->fetch($obj->rowid);
				$ret[0][$obj->rowid] = $obj->rowid;
				$ret[1][$obj->rowid] = $obj->validation;
				$ret[2][$obj->rowid] = $newuser;
			}

			$this->db->free($resql);
			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Modifie le trajet à la date donnée de l'utilisateur
	 *	@param		User 	$userstatic     Utilisateur
	 *  @param		date	$date			Date
	 *  @param 		int		$trajet			1 si il y a un trajet, 0 sinon
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	/*public function modifyTrajet($user_static, $date, $trajet)
	{
		global $conf, $langs;
		$modification_deplacement = '';

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($date) && $user_static->id > 0) {
			$sql = "SELECT date_trajet, trajet, fk_user";
			$sql .= " FROM ".MAIN_DB_PREFIX.'feuilledetemps_trajet';
			$sql .= ' WHERE date_trajet = "'.$this->db->idate($date).'"';
			$sql .= " AND fk_user = ".(int)$user_static->id;

			dol_syslog(get_class($this)."::modifyTrajet()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
			else {
				$num = $this->db->num_rows($resql);
				$this->db->free($resql);
				if($num == 0 && $trajet == 1){
					$sql = "INSERT INTO ".MAIN_DB_PREFIX.'feuilledetemps_trajet(date_trajet, trajet, fk_user)';
					$sql .= ' VALUES("'.$this->db->idate($date).'"';
					$sql .= ", 1";
					$sql .= ", ".(int)$user_static->id.")";

					$resql = $this->db->query($sql);
					if (!$resql) {
						dol_print_error($this->db);
						$this->error = $this->db->lasterror();
						$error++;
					}
					else {
						$modification_deplacement .= "<li>".substr($this->db->idate($date), 0, 11)." : Non -> Oui</li>";
					}
				}
				else if($num > 0){
					if($trajet == 0){
						$sql = "DELETE FROM ".MAIN_DB_PREFIX.'feuilledetemps_trajet';
						$sql .= ' WHERE date_trajet = "'.$this->db->idate($date).'"';
						$sql .= " AND fk_user = ".(int)$user_static->id;
						$sql .= " AND trajet = 1";


						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}
						else {
							$modification_deplacement .= "<li>".substr($this->db->idate($date), 0, 11)." : Oui -> Non</li>";
						}
					}
				}
			}
		}

		if (!$error) {
			$this->db->commit();
			return $modification_deplacement;
		} else {
			$this->db->rollback();
			return -1;
		}
	}*/

	/**
	 *	Est-ce que l'utilisateur a un trajet ce jour la
	 *	@param		User 	$userstatic     Utilisateur
	 *  @param		date	$date			Date
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	/*public function existeTrajet($user_static, $date)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($date) && $user_static->id > 0) {
			$sql = "SELECT date_trajet, trajet, fk_user";
			$sql .= " FROM ".MAIN_DB_PREFIX.'feuilledetemps_trajet';
			$sql .= ' WHERE date_trajet = "'.$this->db->idate($date).'"';
			$sql .= " AND fk_user = ".(int)$user_static->id;
			$sql .= " AND trajet = 1";

			dol_syslog(get_class($this)."::existeTrajet()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
			else {
				$num = $this->db->num_rows($resql);
				$this->db->free($resql);
				if($num == 0){
					return 0;
				}
				else{
					return 1;
				}
			}
		}
	}*/

	/**
	 *	Récupérer toutes les dates ou il y a eu un déplacement pour l'utilisateur (avant la date indiquée)
	 *	@param		User 	$userstatic     Utilisateur
	 *  @param		date	$date			Date
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	/*public function getNbJourDepuisDerniereDetente($user_static, $date)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		$ret = null;

		$now = dol_now();

		$this->db->begin();

		if (!empty($date) && $user_static->id > 0) {
			$sql = "SELECT DATEDIFF('".$this->db->idate($date)."', date) as nbJour";
			$sql .= " FROM ".MAIN_DB_PREFIX.'feuilledetemps_deplacement';
			$sql .= ' WHERE date < "'.$this->db->idate($date).'"';
			$sql .= " AND fk_user = ".(int)$user_static->id;
			$sql .= " AND type_trajet = 1";
			$sql .= " ORDER BY date DESC";
			$sql .= " LIMIT 1";


			dol_syslog(get_class($this)."::getNbJourDepuisDerniereDetente()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
			else {
				$obj = $this->db->fetch_object($resql);
				if(!empty($obj)) {
					$ret = $obj->nbJour;
				}
				$this->db->free($resql);
				return $ret;
			}
		}
	}*/


	/**
	 *  Envoie un mail pour les FDT qui n'ont pas été enregistrées durant la semaine.
	 *
	 * 	@param 		int 	nombre de jour
	 *  @return		int     resultat
	 */
	public function MailFDT_WeeklySave()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$this->output = 'Impossible d\'envoyer le mail à : ';

		$res = 1;
		$now = dol_now();
		$array_now = dol_getdate($now);
		$first_day_week = dol_get_first_day_week($array_now['mday'], $array_now['mon'], $array_now['year']);
		$day = (int)$first_day_week['first_day'];
		$monday = dol_mktime(-1, -1, -1, $first_day_week['first_month'], $day, $first_day_week['first_year']);
		$tuesday = dol_time_plus_duree($monday, 1, 'd');
		$wednesday = dol_time_plus_duree($monday, 2, 'd');
		$thursday = dol_time_plus_duree($monday, 3, 'd');
		$friday = dol_time_plus_duree($monday, 4, 'd');
		$sunday = dol_time_plus_duree($monday, 6, 'd');

		$no_working_day = num_public_holiday($monday, $sunday, '', 1);
		$working_day = 7 - $no_working_day;

		dol_syslog(get_class($this)."::MailFDT_WeeklySave", LOG_DEBUG);

		if(dol_print_date($now, '%a') == 'Sam' || dol_print_date($now, '%a') == 'Dim') {
			$sql = "SELECT u.rowid, u.email, IFNULL(table1.nb_jour, 0) as nb_jour_absence, IFNULL(table2.nb_jour, 0) as nb_jour_pointage";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

			// Table qui permet de récupérer le nombre de jour de congés pour chaque utilisateur durant la semaine
			$sql .= " LEFT JOIN (SELECT h.fk_user, h.rowid,";
			$sql .= " SUM(CASE 
						WHEN (h.date_debut < '".substr($this->db->idate($monday), 0, 10)."' AND  h.date_fin > '".substr($this->db->idate($friday), 0, 10)."') THEN DATEDIFF(h.date_fin, h.date_debut) + 1 - DATEDIFF('".substr($this->db->idate($monday), 0, 10)."', h.date_debut) - DATEDIFF(h.date_fin, '".substr($this->db->idate($friday), 0, 10)."')
						WHEN h.date_debut < '".substr($this->db->idate($monday), 0, 10)."' THEN DATEDIFF(h.date_fin, h.date_debut) + 1 - DATEDIFF('".substr($this->db->idate($monday), 0, 10)."', h.date_debut)
						WHEN h.date_fin > '".substr($this->db->idate($friday), 0, 10)."' THEN DATEDIFF(h.date_fin, h.date_debut) + 1 - DATEDIFF(h.date_fin, '".substr($this->db->idate($friday), 0, 10)."')
						ELSE DATEDIFF(h.date_fin, h.date_debut) + 1
	  				END) as nb_jour";
			$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
			$sql .= " WHERE (('".substr($this->db->idate($monday), 0, 10)."' BETWEEN h.date_debut AND h.date_fin) OR ('".substr($this->db->idate($tuesday), 0, 10)."' BETWEEN h.date_debut AND h.date_fin) OR ('".substr($this->db->idate($wednesday), 0, 10)."' BETWEEN h.date_debut AND h.date_fin) OR ('".substr($this->db->idate($thursday), 0, 10)."' BETWEEN h.date_debut AND h.date_fin) OR ('".substr($this->db->idate($friday), 0, 10)."' BETWEEN h.date_debut AND h.date_fin)) AND (h.statut = 2 OR h.statut = 3)";
			$sql .= " GROUP BY h.fk_user) AS table1";
			$sql .= " ON table1.fk_user = u.rowid";

			// Table qui permet de récupérer le nombre de jour pointé durant la semaine pour chaque utilisateur
			$sql .= " LEFT JOIN (SELECT t.fk_user,  COUNT(DISTINCT t.element_date) as nb_jour";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
			$sql .= " WHERE t.elementtype = 'task' AND t.element_date >= '".substr($this->db->idate($monday), 0, 10)."' AND t.element_date <= '".substr($this->db->idate($friday), 0, 10)."' GROUP BY t.fk_user) AS table2";
			$sql .= " ON table2.fk_user = u.rowid";

			$sql .= " WHERE u.statut = 1";
			
			// On enlève les utilisateurs qui ont enregistrés un temps cette semaine
			/*$sql .= " AND u.rowid NOT IN (SELECT DISTINCT u.rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as t ON u.rowid = t.fk_user";
			$sql .= ' WHERE t.element_date >= "'.substr($this->db->idate($monday), 0, 10).'"';
			$sql .= ' AND t.element_date <= "'.substr($this->db->idate($sunday), 0, 10).'")';

			// On enlève les utilisateurs qui ont un congés durant toute la semaine
			$sql .= " AND u.rowid NOT IN (SELECT DISTINCT u.rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."holiday as h ON u.rowid = h.fk_user";
			$sql .= ' WHERE h.date_fin >= "'.substr($this->db->idate($friday), 0, 10).'"';
			$sql .= ' AND h.date_debut <= "'.substr($this->db->idate($monday), 0, 10).'"';
			$sql .= " AND h.statut = 3)";*/

			$result = $this->db->query($sql);
			if ($result) {

				$user_static = new User($this->db);
				$form = new Form($this->db);
				$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
				$from = 'erp@optim-industries.fr';

				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/timesheet.php">'.'ici'.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFDTWeeklySave", dol_print_date($monday, '%d/%m/%Y'), dol_print_date($sunday, '%d/%m/%Y'), $link);
				
				while ($obj = $this->db->fetch_object($result)) {	
					$user_static->fetch($obj->rowid);
					if(!in_array(array_search('Exclusion FDT', $form->select_all_categories(Categorie::TYPE_USER, null, null, null, null, 1)), $user_static->getCategoriesCommon(Categorie::TYPE_USER))) {
						$to = '';	
						$nb_jour_ok = $obj->nb_jour_absence + $obj->nb_jour_pointage;

						if($nb_jour_ok < $working_day && !empty($obj->email)){
							$to = $obj->email;
						}

						$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);

						if(!empty($to)){
							$res = $mail->sendfile();
						}

						if(!empty($to) && !$res) {
							$this->output .= $to.", ";
						}
					}
				}

				$this->db->free($result);
				
				if($res) return 0;
				else return -1;
			} 
			else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		}

		return 0;
	}


	/**
	 *  Envoie un mail pour les FDT qui n'ont pas été transférer après le $jour de chaque mois
	 *
	 * 	@param 		int 	jour de l'envoi du mail
	 *  @return		int     resultat
	 */
	public function MailFDT_MonthlyTransfer($jour)
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;
		$now = dol_now();
		$array_now = dol_getdate($now);
		$day_now = $array_now["mday"];
		$month_now = $array_now["mon"];
		$user_static = new User($this->db);
		$form = new Form($this->db);

		dol_syslog(get_class($this)."::MailFDT_MonthlyTransfer", LOG_DEBUG);

		if($day_now >= $jour) {
			$sql = "SELECT DISTINCT u.rowid, u.email, f.rowid as fdt_id";
			$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_feuilledetemps as f";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u";
			$sql .= " ON u.rowid = f.fk_user";
			$sql .= " WHERE '".substr($this->db->idate($now), 0, 10)."' BETWEEN f.date_debut AND f.date_fin";
			$sql .= " AND (f.status = 0 OR f.status IS NULL)";
			$sql .= " AND u.statut = 1";

			$result = $this->db->query($sql);
			if ($result) {
				$subject = '[OPTIM Industries] Notification automatique Feuille de temps';
				$from = 'erp@optim-industries.fr';
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/feuilledetemps/timesheet.php">'.'ici'.'</a>';
				$msg = $langs->transnoentitiesnoconv("EMailTextFDTMonthlyTransfer", $link);
				
				while ($obj = $this->db->fetch_object($result)) {	
					$user_static->fetch($obj->rowid);

					if(!in_array(array_search('Exclusion FDT', $form->select_all_categories(Categorie::TYPE_USER, null, null, null, null, 1)), $user_static->getCategoriesCommon(Categorie::TYPE_USER))) {
						$to = '';
		
						if(!empty($obj->email)) {
							$to = $obj->email;
						}
					
						$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);

						if(!empty($to)) {
							$res = $mail->sendfile();
						}
					}
				}

				if($res) return 0;
				else return -1;
			} 
			else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		}

		return 0;
	}


	/**
	 *  Obtenir le total des heures à partir d'un utilisateur et d'une date
	 *
	 *  @param	    Date		$date			
	 *  @param      Int			id de l'user   
	 *  @return     array         				Total des heures
	 */
	public function getHeureDay($date, $user_static)
    {
        if (empty($date) || empty($user_static)) {
            return -1;
        }
  
        $sql = 'SELECT SUM(t.element_duration) as element_duration';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'element_time as t';
    	$sql .= " WHERE t.element_date = '".$this->db->idate($date)."'";
        $sql .= " AND t.fk_user = ".((int) $user_static);
		$sql .= " AND t.elementtype = 'task'";
  
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                if(!empty($obj->element_duration)){
					$heures = $obj->element_duration;
				}

				$this->db->free($resql);
                return $heures;
            } 
			else {
				return 0;
            }
        } 
		else {
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
            return -1;
        }
    }

	/**
	 *  Obtenir les taches favorites pour un utilisateur
	 *
	 *  @param      Int			id de l'user   
	 *  @return     array       Taches favorites
	 */
	public function getFavoris($user_id)
    {
		$res = array(); 

        if (empty($user_id)) {
            return -1;
        }
  
        $sql = 'SELECT fk_task';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'feuilledetemps_favoris';
        $sql .= " WHERE fk_user = ".((int) $user_id);
  
        $resql = $this->db->query($sql);
		if ($resql) {
			while($obj = $this->db->fetch_object($resql)) {
				$res[] = $obj->fk_task;
			}
			$this->db->free($resql);
			return $res;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			return -1;
		}
    }

	/**
	 *
	 *	@param		int 	$userid     		Id de l'utilisateur
	 *  @param		int		$task_id			Id de la tâche
	 *	@return  	int							<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function createFavoris($user_id, $task_id)
	{
		$error = 0;

		$this->db->begin();

		if ($user_id > 0 && $task_id > 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX.'feuilledetemps_favoris(fk_user, fk_task)';
			$sql .= " VALUES(".(int)$user_id.", ".(int)$task_id.")";

			dol_syslog(get_class($this)."::createFavoris()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
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
	 *
	 *  @param		int 	$userid     		Id de l'utilisateur
	 *  @param		int		$task_id			Id de la tâche
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function deleteFavoris($user_id, $task_id)
	{
		$error = 0;

		$this->db->begin();

		if ($user_id > 0 && $task_id > 0) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.'feuilledetemps_favoris';
			$sql .= " WHERE fk_user = ".(int)$user_id;
			$sql .= " AND fk_task = ".(int)$task_id;

			dol_syslog(get_class($this)."::deleteFavoris()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
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
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Objet user
	 *      @return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	public function load_board_approve1($user)
	{
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		$now = dol_now();

		$sql = "SELECT v.fk_feuilledetemps";
		$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_task_validation as v";
		$sql .= " WHERE v.fk_user_validation = ".$user->id;
		$sql .= " AND v.validation = 0 AND v.validation_number = 1";

		$resql = $this->db->query($sql);
		if ($resql) {
			$response = new WorkboardResponse();
			//$response->warning_delay = $conf->holiday->approve->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("FeuilleDeTempsToApprove1");
			$response->labelShort = $langs->trans("ToApprove1");
			$response->url = DOL_URL_ROOT.'/custom/feuilledetemps/feuilledetemps_list.php?mainmenu=feuilledetemps&search_fk_user_validation_1='.$user->id;

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Objet user
	 *      @return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	public function load_board_approve2($user)
	{
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		$now = dol_now();

		$sql = "SELECT v.fk_feuilledetemps";
		$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_task_validation as v";
		$sql .= " WHERE v.fk_user_validation = ".$user->id;
		$sql .= " AND v.validation = 0 AND v.validation_number = 2";

		$resql = $this->db->query($sql);
		if ($resql) {
			$response = new WorkboardResponse();
			//$response->warning_delay = $conf->holiday->approve->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("FeuilleDeTempsToApprove2");
			$response->labelShort = $langs->trans("ToApprove2");
			$response->url = DOL_URL_ROOT.'/custom/feuilledetemps/feuilledetemps_list.php?mainmenu=feuilledetemps&search_fk_user_validation_2='.$user->id;

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Objet user
	 *      @return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	public function load_board_verification($user)
	{
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		$now = dol_now();

		$sql = "SELECT f.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_feuilledetemps as f";
		$sql .= " WHERE f.status = ".self::STATUS_VERIFICATION;

		$resql = $this->db->query($sql);
		if ($resql) {
			$response = new WorkboardResponse();
			//$response->warning_delay = $conf->holiday->approve->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("FeuilleDeTempsToVerification");
			$response->labelShort = $langs->trans("ToVerification");
			$response->url = DOL_URL_ROOT.'/custom/feuilledetemps/feuilledetemps_list.php?mainmenu=feuilledetemps&search_status='.self::STATUS_VERIFICATION;

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	public function load_previous_next_ref_custom($filter, $filter2, $fieldid, $nodbprefix = 0)
	{
		// phpcs:enable
		global $conf, $user;
	
		if (!$this->table_element) {
		dol_print_error('', get_class($this)."::load_previous_next_ref was called on objet with property table_element not defined");
		return -1;
		}
		if ($fieldid == 'none') {
		return 1;
		}
	
		// For backward compatibility
		if ($this->table_element == 'facture_rec' && $fieldid == 'title') {
		$fieldid = 'titre';
		}
	
		// Security on socid
		$socid = 0;
		if ($user->socid > 0) {
		$socid = $user->socid;
		}
	
		// this->ismultientitymanaged contains
		// 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
		$aliastablesociete = 's';
		if ($this->element == 'societe') {
		$aliastablesociete = 'te'; // te as table_element
		}
		$restrictiononfksoc = empty($this->restrictiononfksoc) ? 0 : $this->restrictiononfksoc;
		$sql = "SELECT MAX(te.".$fieldid.")";
		$sql .= " FROM ".(empty($nodbprefix) ?$this->db->prefix():'').$this->table_element." as te";
		if ($this->element == 'user' && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$sql .= ",".$this->db->prefix()."usergroup_user as ug";
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
		$tmparray = explode('@', $this->ismultientitymanaged);
		$sql .= ", ".$this->db->prefix().$tmparray[1]." as ".($tmparray[1] == 'societe' ? 's' : 'parenttable'); // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ", ".$this->db->prefix()."societe as s"; // If we need to link to societe to limit select to socid
		} elseif ($restrictiononfksoc == 2 && $this->element != 'societe' && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON te.fk_soc = s.rowid"; // If we need to link to societe to limit select to socid
		}
		if ($restrictiononfksoc && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe_commerciaux as sc ON ".$aliastablesociete.".rowid = sc.fk_soc";
		}
		if ($fieldid == 'rowid') {
		$sql .= " WHERE te.".$fieldid." < ".((int) $this->id);
		} else {
		//$sql .= " WHERE te.".$fieldid." < '".$this->db->escape($this->ref)."'"; // ->ref must always be defined (set to id if field does not exists)
		}
		if ($restrictiononfksoc == 1 && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND sc.fk_user = ".((int) $user->id);
		}
		if ($restrictiononfksoc == 2 && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND (sc.fk_user = ".((int) $user->id).' OR te.fk_soc IS NULL)';
		}
		if (!empty($filter)) {
		if (!preg_match('/^\s*AND/i', $filter)) {
			//$sql .= " AND "; // For backward compatibility
		}
		$sql .= $filter;
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
		$tmparray = explode('@', $this->ismultientitymanaged);
		$sql .= " AND te.".$tmparray[0]." = ".($tmparray[1] == "societe" ? "s" : "parenttable").".rowid"; // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ' AND te.fk_soc = s.rowid'; // If we need to link to societe to limit select to socid
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
		if ($this->element == 'user' && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
			$sql .= " AND te.entity IS NOT NULL"; // Show all users
			} else {
			$sql .= " AND ug.fk_user = te.rowid";
			$sql .= " AND ug.entity IN (".getEntity('usergroup').")";
			}
		} else {
			$sql .= ' AND te.entity IN ('.getEntity($this->element).')';
		}
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged) && $this->element != 'societe') {
		$tmparray = explode('@', $this->ismultientitymanaged);
		$sql .= ' AND parenttable.entity IN ('.getEntity($tmparray[1]).')';
		}
		if ($restrictiononfksoc == 1 && $socid && $this->element != 'societe') {
		$sql .= ' AND te.fk_soc = '.((int) $socid);
		}
		if ($restrictiononfksoc == 2 && $socid && $this->element != 'societe') {
		$sql .= ' AND (te.fk_soc = '.((int) $socid).' OR te.fk_soc IS NULL)';
		}
		if ($restrictiononfksoc && $socid && $this->element == 'societe') {
		$sql .= ' AND te.rowid = '.((int) $socid);
		}
		//print 'socid='.$socid.' restrictiononfksoc='.$restrictiononfksoc.' ismultientitymanaged = '.$this->ismultientitymanaged.' filter = '.$filter.' -> '.$sql."<br>";
	
		$result = $this->db->query($sql);
		if (!$result) {
		$this->error = $this->db->lasterror();
		return -1;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_previous = $row[0];
	
		$sql = "SELECT MIN(te.".$fieldid.")";
		$sql .= " FROM ".(empty($nodbprefix) ?$this->db->prefix():'').$this->table_element." as te";
		if ($this->element == 'user' && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$sql .= ",".$this->db->prefix()."usergroup_user as ug";
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
		$tmparray = explode('@', $this->ismultientitymanaged);
		$sql .= ", ".$this->db->prefix().$tmparray[1]." as ".($tmparray[1] == 'societe' ? 's' : 'parenttable'); // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ", ".$this->db->prefix()."societe as s"; // If we need to link to societe to limit select to socid
		} elseif ($restrictiononfksoc == 2 && $this->element != 'societe' && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON te.fk_soc = s.rowid"; // If we need to link to societe to limit select to socid
		}
		if ($restrictiononfksoc && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe_commerciaux as sc ON ".$aliastablesociete.".rowid = sc.fk_soc";
		}
		if ($fieldid == 'rowid') {
		$sql .= " WHERE te.".$fieldid." > ".((int) $this->id);
		} else {
		//$sql .= " WHERE te.".$fieldid." > '".$this->db->escape($this->ref)."'"; // ->ref must always be defined (set to id if field does not exists)
		}
		if ($restrictiononfksoc == 1 && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND sc.fk_user = ".((int) $user->id);
		}
		if ($restrictiononfksoc == 2 && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND (sc.fk_user = ".((int) $user->id).' OR te.fk_soc IS NULL)';
		}
		if (!empty($filter2)) {
		if (!preg_match('/^\s*AND/i', $filter2)) {
			//$sql .= " AND "; // For backward compatibility
		}
		$sql .= $filter2;
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
		$tmparray = explode('@', $this->ismultientitymanaged);
		$sql .= " AND te.".$tmparray[0]." = ".($tmparray[1] == "societe" ? "s" : "parenttable").".rowid"; // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ' AND te.fk_soc = s.rowid'; // If we need to link to societe to limit select to socid
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
		if ($this->element == 'user' && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
			$sql .= " AND te.entity IS NOT NULL"; // Show all users
			} else {
			$sql .= " AND ug.fk_user = te.rowid";
			$sql .= " AND ug.entity IN (".getEntity('usergroup').")";
			}
		} else {
			$sql .= ' AND te.entity IN ('.getEntity($this->element).')';
		}
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged) && $this->element != 'societe') {
		$tmparray = explode('@', $this->ismultientitymanaged);
		$sql .= ' AND parenttable.entity IN ('.getEntity($tmparray[1]).')';
		}
		if ($restrictiononfksoc == 1 && $socid && $this->element != 'societe') {
		$sql .= ' AND te.fk_soc = '.((int) $socid);
		}
		if ($restrictiononfksoc == 2 && $socid && $this->element != 'societe') {
		$sql .= ' AND (te.fk_soc = '.((int) $socid).' OR te.fk_soc IS NULL)';
		}
		if ($restrictiononfksoc && $socid && $this->element == 'societe') {
		$sql .= ' AND te.rowid = '.((int) $socid);
		}
		//print 'socid='.$socid.' restrictiononfksoc='.$restrictiononfksoc.' ismultientitymanaged = '.$this->ismultientitymanaged.' filter = '.$filter.' -> '.$sql."<br>";
		// Rem: Bug in some mysql version: SELECT MIN(rowid) FROM llx_socpeople WHERE rowid > 1 when one row in database with rowid=1, returns 1 instead of null
	
		$result = $this->db->query($sql);
		if (!$result) {
		$this->error = $this->db->lasterror();
		return -2;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_next = $row[0];
	
		return 1;
	}

	/**
	 *     Show a confirmation HTML form or AJAX popup.
	 *     Easiest way to use this is with useajax=1.
	 *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
	 *     just after calling this method. For example:
	 *       print '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
	 *       print 'jQuery(document).ready(function() {'."\n";
	 *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
	 *       print '});'."\n";
	 *       print '</script>'."\n";
	 *
	 * @param string 		$page 				Url of page to call if confirmation is OK. Can contains parameters (param 'action' and 'confirm' will be reformated)
	 * @param string 		$title 				Title
	 * @param string 		$question 			Question
	 * @param string 		$action 			Action
	 * @param array|string 	$formquestion 		An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , 'size'=>, 'morecss'=>, 'moreattr'=>'autofocus' or 'style=...'))
	 *                                   		'type' can be 'text', 'password', 'checkbox', 'radio', 'date', 'datetime', 'select', 'multiselect', 'morecss',
	 *                                   		'other', 'onecolumn' or 'hidden'...
	 * @param int|string 	$selectedchoice 	'' or 'no', or 'yes' or '1', 1, '0' or 0
	 * @param int|string 	$useajax 			0=No, 1=Yes use Ajax to show the popup, 2=Yes and also submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
	 * @param int|string 	$height 			Force height of box (0 = auto)
	 * @param int 			$width 				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
	 * @param int 			$disableformtag 	1=Disable form tag. Can be used if we are already inside a <form> section.
	 * @param string 		$labelbuttonyes 	Label for Yes
	 * @param string 		$labelbuttonno 		Label for No
	 * @return string                        	HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
	 */
	public function formconfirm($page, $title, $question, $action, $formquestion = '', $selectedchoice = '', $useajax = 0, $height = 0, $width = 500, $disableformtag = 0, $labelbuttonyes = 'Yes', $labelbuttonno = 'No')
	{
		global $langs, $conf;
		$form = new Form($this->db);

		$more = '<!-- formconfirm - before call, page=' . dol_escape_htmltag($page) . ' -->';
		$formconfirm = '';
		$inputok = array();
		$inputko = array();

		// Clean parameters
		$newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
		if ($conf->browser->layout == 'phone') {
			$width = '95%';
		}

		// Set height automatically if not defined
		if (empty($height)) {
			$height = 220;
			if (is_array($formquestion) && count($formquestion) > 2) {
				$height += ((count($formquestion) - 2) * 24);
			}
		}

		if (is_array($formquestion) && !empty($formquestion)) {
			// First add hidden fields and value
			foreach ($formquestion as $key => $input) {
				if (is_array($input) && !empty($input)) {
					if ($input['type'] == 'hidden') {
						$moreattr = (!empty($input['moreattr']) ? ' ' . $input['moreattr'] : '');
						$morecss = (!empty($input['morecss']) ? ' ' . $input['morecss'] : '');

						$more .= '<input type="hidden" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '" value="' . dol_escape_htmltag($input['value']) . '" class="' . $morecss . '"' . $moreattr . '>' . "\n";
					}
				}
			}

			// Now add questions
			$moreonecolumn = '';
			$more .= '<div class="tagtable paddingtopbottomonly centpercent noborderspacing">' . "\n";
			foreach ($formquestion as $key => $input) {
				if (is_array($input) && !empty($input)) {
					$size = (!empty($input['size']) ? ' size="' . $input['size'] . '"' : '');    // deprecated. Use morecss instead.
					$moreattr = (!empty($input['moreattr']) ? ' ' . $input['moreattr'] : '');
					$morecss = (!empty($input['morecss']) ? ' ' . $input['morecss'] : '');

					if ($input['type'] == 'text') {
						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">' . $input['label'] . '</div><div class="tagtd"><input type="text" class="flat' . $morecss . '" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '"' . $size . ' value="' . (empty($input['value']) ? '' : $input['value']) . '"' . $moreattr . ' /></div></div>' . "\n";
					} elseif ($input['type'] == 'password') {
						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">' . $input['label'] . '</div><div class="tagtd"><input type="password" class="flat' . $morecss . '" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '"' . $size . ' value="' . (empty($input['value']) ? '' : $input['value']) . '"' . $moreattr . ' /></div></div>' . "\n";
					} elseif ($input['type'] == 'textarea') {
						/*$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd">';
						$more .= '<textarea name="'.$input['name'].'" class="'.$morecss.'"'.$moreattr.'>';
						$more .= $input['value'];
						$more .= '</textarea>';
						$more .= '</div></div>'."\n";*/
						$moreonecolumn .= '<div class="margintoponly">';
						$moreonecolumn .= $input['label'] . '<br>';
						$moreonecolumn .= '<textarea name="' . dol_escape_htmltag($input['name']) . '" id="' . dol_escape_htmltag($input['name']) . '" class="' . $morecss . '"' . $moreattr . '>';
						$moreonecolumn .= $input['value'];
						$moreonecolumn .= '</textarea>';
						$moreonecolumn .= '</div>';
					} elseif (in_array($input['type'], ['select', 'multiselect'])) {
						if (empty($morecss)) {
							$morecss = 'minwidth100';
						}

						$show_empty = isset($input['select_show_empty']) ? $input['select_show_empty'] : 1;
						$key_in_label = isset($input['select_key_in_label']) ? $input['select_key_in_label'] : 0;
						$value_as_key = isset($input['select_value_as_key']) ? $input['select_value_as_key'] : 0;
						$translate = isset($input['select_translate']) ? $input['select_translate'] : 0;
						$maxlen = isset($input['select_maxlen']) ? $input['select_maxlen'] : 0;
						$disabled = isset($input['select_disabled']) ? $input['select_disabled'] : 0;
						$sort = isset($input['select_sort']) ? $input['select_sort'] : '';

						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">';
						if (!empty($input['label'])) {
							$more .= $input['label'].'';
						}
						if ($input['type'] == 'select') {
							$more .= $form->selectarray($input['name'], $input['values'], isset($input['default']) ? $input['default'] : '-1', $show_empty, $key_in_label, $value_as_key, $moreattr, $translate, $maxlen, $disabled, $sort, $morecss);
						} else {
							$more .= $form->multiselectarray($input['name'], $input['values'], is_array($input['default']) ? $input['default'] : [$input['default']], $key_in_label, $value_as_key, $morecss, $translate, $maxlen, $moreattr);
						}
						$more .= '</div></div>' . "\n";
					} elseif ($input['type'] == 'checkbox') {
						$more .= '<div class="tagtr">';
						$more .= '<div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '"><label for="' . dol_escape_htmltag($input['name']) . '">' . $input['label'] . '</label></div><div class="tagtd">';
						$more .= '<input type="checkbox" class="flat' . ($morecss ? ' ' . $morecss : '') . '" id="' . dol_escape_htmltag($input['name']) . '" name="' . dol_escape_htmltag($input['name']) . '"' . $moreattr;
						if (!is_bool($input['value']) && $input['value'] != 'false' && $input['value'] != '0' && $input['value'] != '') {
							$more .= ' checked';
						}
						if (is_bool($input['value']) && $input['value']) {
							$more .= ' checked';
						}
						if (isset($input['disabled'])) {
							$more .= ' disabled';
						}
						$more .= ' /></div>';
						$more .= '</div>' . "\n";
					} elseif ($input['type'] == 'radio') {
						$i = 0;
						foreach ($input['values'] as $selkey => $selval) {
							$more .= '<div class="tagtr">';
							if (isset($input['label'])) {
								if ($i == 0) {
									$more .= '<div class="tagtd' . (empty($input['tdclass']) ? ' tdtop' : (' tdtop ' . $input['tdclass'])) . '">' . $input['label'] . '</div>';
								} else {
									$more .= '<div clas="tagtd' . (empty($input['tdclass']) ? '' : (' "' . $input['tdclass'])) . '">&nbsp;</div>';
								}
							}
							$more .= '<div class="tagtd' . ($i == 0 ? ' tdtop' : '') . '"><input type="radio" class="flat' . $morecss . '" id="' . dol_escape_htmltag($input['name'] . $selkey) . '" name="' . dol_escape_htmltag($input['name']) . '" value="' . $selkey . '"' . $moreattr;
							if (!empty($input['disabled'])) {
								$more .= ' disabled';
							}
							if (isset($input['default']) && $input['default'] === $selkey) {
								$more .= ' checked="checked"';
							}
							$more .= ' /> ';
							$more .= '<label for="' . dol_escape_htmltag($input['name'] . $selkey) . '" class="valignmiddle">' . $selval . '</label>';
							$more .= '</div></div>' . "\n";
							$i++;
						}
					} elseif ($input['type'] == 'date' || $input['type'] == 'datetime') {
						$more .= '<div class="tagtr"><div class="tagtd' . (empty($input['tdclass']) ? '' : (' ' . $input['tdclass'])) . '">' . $input['label'] . '</div>';
						$more .= '<div class="tagtd">';
						$addnowlink = (empty($input['datenow']) ? 0 : 1);
						$h = $m = 0;
						if ($input['type'] == 'datetime') {
							$h = isset($input['hours']) ? $input['hours'] : 1;
							$m = isset($input['minutes']) ? $input['minutes'] : 1;
						}
						$more .= $form->selectDate($input['value'], $input['name'], $h, $m, 0, '', 1, $addnowlink);
						$more .= '</div></div>'."\n";
						$formquestion[] = array('name'=>$input['name'].'day');
						$formquestion[] = array('name'=>$input['name'].'month');
						$formquestion[] = array('name'=>$input['name'].'year');
						$formquestion[] = array('name'=>$input['name'].'hour');
						$formquestion[] = array('name'=>$input['name'].'min');
					} elseif ($input['type'] == 'other') { // can be 1 column or 2 depending if label is set or not
						$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
						if (!empty($input['label'])) {
							$more .= $input['label'] . '</div><div class="tagtd">';
						}
						$more .= $input['value'];
						$more .= '</div></div>' . "\n";
					} elseif ($input['type'] == 'onecolumn') {
						$moreonecolumn .= '<div class="margintoponly">';
						$moreonecolumn .= $input['value'];
						$moreonecolumn .= '</div>' . "\n";
					} elseif ($input['type'] == 'hidden') {
						// Do nothing more, already added by a previous loop
					} elseif ($input['type'] == 'separator') {
						$more .= '<br>';
					} elseif (preg_match('/^html/', $input['type'])) {
							require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
							$doleditor = new DolEditor($input['name'], $value, '', 200, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor') && $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_5, '90%');
							$more .= '<br>'.$doleditor->Create(1, '', true, '', '', $moreparam, $morecss);
					}
					else {
						$more .= 'Error type ' . $input['type'] . ' for the confirm box is not a supported type';
					}
				}
			}
			$more .= '</div>' . "\n";
			$more .= $moreonecolumn;
		}

		// JQUERY method dialog is broken with smartphone, we use standard HTML.
		// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
		// See page product/card.php for example
		if (!empty($conf->dol_use_jmobile)) {
			$useajax = 0;
		}
		if (empty($conf->use_javascript_ajax)) {
			$useajax = 0;
		}

		if ($useajax) {
			$autoOpen = true;
			$dialogconfirm = 'dialog-confirm';
			$button = '';
			if (!is_numeric($useajax)) {
				$button = $useajax;
				$useajax = 1;
				$autoOpen = false;
				$dialogconfirm .= '-' . $button;
			}
			$pageyes = $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . urlencode($action) . '&confirm=yes';
			$pageno = ($useajax == 2 ? $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . urlencode($action) . '&confirm=no' : '');

			// Add input fields into list of fields to read during submit (inputok and inputko)
			if (is_array($formquestion)) {
				foreach ($formquestion as $key => $input) {
					//print "xx ".$key." rr ".is_array($input)."<br>\n";
					// Add name of fields to propagate with the GET when submitting the form with button OK.
					if (is_array($input) && isset($input['name'])) {
						if (strpos($input['name'], ',') > 0) {
							$inputok = array_merge($inputok, explode(',', $input['name']));
						} else {
							array_push($inputok, $input['name']);
						}
					}
					// Add name of fields to propagate with the GET when submitting the form with button KO.
					if (isset($input['inputko']) && $input['inputko'] == 1) {
						array_push($inputko, $input['name']);
					}
				}
			}

			// Show JQuery confirm box.
			$formconfirm .= '<div id="' . $dialogconfirm . '" title="' . dol_escape_htmltag($title) . '" style="display: none;">';
			if (is_array($formquestion) && !empty($formquestion['text'])) {
				$formconfirm .= '<div class="confirmtext">' . $formquestion['text'] . '</div>' . "\n";
			}
			if (!empty($more)) {
				$formconfirm .= '<div class="confirmquestions">' . $more . '</div>' . "\n";
			}
			$formconfirm .= ($question ? '<div class="confirmmessage">' . img_help('', '') . ' ' . $question . '</div>' : '');
			$formconfirm .= '</div>' . "\n";

			$formconfirm .= "\n<!-- begin code of popup for formconfirm page=" . $page . " -->\n";
			$formconfirm .= '<script nonce="' . getNonce() . '" type="text/javascript">' . "\n";
			$formconfirm .= "/* Code for the jQuery('#dialogforpopup').dialog() */\n";
			$formconfirm .= 'jQuery(document).ready(function() {
            $(function() {
            	$( "#' . $dialogconfirm . '" ).dialog(
            	{
                    autoOpen: ' . ($autoOpen ? "true" : "false") . ',';
			if ($newselectedchoice == 'no') {
				$formconfirm .= '
						open: function() {
            				$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
			}

			$jsforcursor = '';
			if ($useajax == 1) {
				$jsforcursor = '// The call to urljump can be slow, so we set the wait cursor' . "\n";
				$jsforcursor .= 'jQuery("html,body,#id-container").addClass("cursorwait");' . "\n";
			}

			$postconfirmas = 'GET';

			$formconfirm .= '
                    resizable: false,
                    height: "' . $height . '",
                    width: "' . $width . '",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "' . dol_escape_js($langs->transnoentities($labelbuttonyes)) . '": function() {
							var options = "token=' . urlencode(newToken()) . '";
                        	var inputok = ' . json_encode($inputok) . ';	/* List of fields into form */
							var page = "' . dol_escape_js(!empty($page) ? $page : '') . '";
                         	var pageyes = "' . dol_escape_js(!empty($pageyes) ? $pageyes : '') . '";

                         	if (inputok.length > 0) {
                         		$.each(inputok, function(i, inputname) {
                         			var more = "";
									var inputvalue;
                         			if ($("input[name=\'" + inputname + "\']").attr("type") == "radio") {
										inputvalue = $("input[name=\'" + inputname + "\']:checked").val();
									} else {
                         		    	if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         				inputvalue = $("#" + inputname + more).val();
									}
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
									console.log("formconfirm check inputname="+inputname+" inputvalue="+inputvalue);
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "&") + options;
            				if (pageyes.length > 0) {';
			if ($postconfirmas == 'GET') {
				$formconfirm .= 'location.href = urljump;';
			} else {
				$formconfirm .= $jsforcursor;
				$formconfirm .= 'var post = $.post(
									pageyes,
									options,
									function(data) { $("body").html(data); jQuery("html,body,#id-container").removeClass("cursorwait"); }
								);';
			}
			$formconfirm .= '
								console.log("after post ok");
							}
	                        $(this).dialog("close");
                        },
                        "' . dol_escape_js($langs->transnoentities($labelbuttonno)) . '": function() {
                        	var options = "token=' . urlencode(newToken()) . '";
                         	var inputko = ' . json_encode($inputko) . ';	/* List of fields into form */
							var page = "' . dol_escape_js(!empty($page) ? $page : '') . '";
                         	var pageno="' . dol_escape_js(!empty($pageno) ? $pageno : '') . '";
                         	if (inputko.length > 0) {
                         		$.each(inputko, function(i, inputname) {
                         			var more = "";
                         			if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         			var inputvalue = $("#" + inputname + more).val();
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "&") + options;
                         	//alert(urljump);
            				if (pageno.length > 0) {';
			if ($postconfirmas == 'GET') {
				$formconfirm .= 'location.href = urljump;';
			} else {
				$formconfirm .= $jsforcursor;
				$formconfirm .= 'var post = $.post(
									pageno,
									options,
									function(data) { $("body").html(data); jQuery("html,body,#id-container").removeClass("cursorwait"); }
								);';
			}
			$formconfirm .= '
								console.log("after post ko");
							}
                            $(this).dialog("close");
                        }
                    }
                }
                );

            	var button = "' . $button . '";
            	if (button.length > 0) {
                	$( "#" + button ).click(function() {
                		$("#' . $dialogconfirm . '").dialog("open");
        			});
                }
            });
            });
            </script>';
			$formconfirm .= "<!-- end ajax formconfirm -->\n";
		} else {
			$formconfirm .= "\n<!-- begin formconfirm page=" . dol_escape_htmltag($page) . " -->\n";

			if (empty($disableformtag)) {
				$formconfirm .= '<form method="POST" action="' . $page . '" class="notoptoleftroright">' . "\n";
			}

			$formconfirm .= '<input type="hidden" name="action" value="' . $action . '">' . "\n";
			$formconfirm .= '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";

			$formconfirm .= '<table class="valid centpercent">' . "\n";

			// Line title
			$formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="2">';
			$formconfirm .= img_picto('', 'pictoconfirm') . ' ' . $title;
			$formconfirm .= '</td></tr>' . "\n";

			// Line text
			if (is_array($formquestion) && !empty($formquestion['text'])) {
				$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">' . $formquestion['text'] . '</td></tr>' . "\n";
			}

			// Line form fields
			if ($more) {
				$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">' . "\n";
				$formconfirm .= $more;
				$formconfirm .= '</td></tr>' . "\n";
			}

			// Line with question
			$formconfirm .= '<tr class="valid">';
			$formconfirm .= '<td class="valid">' . $question . '</td>';
			$formconfirm .= '<td class="valid center">';
			$formconfirm .= $form->selectyesno("confirm", $newselectedchoice, 0, false, 0, 0, 'marginleftonly marginrightonly', $labelbuttonyes, $labelbuttonno);
			$formconfirm .= '<input class="button valignmiddle confirmvalidatebutton small" type="submit" value="' . $langs->trans("Validate") . '">';
			$formconfirm .= '</td>';
			$formconfirm .= '</tr>' . "\n";

			$formconfirm .= '</table>' . "\n";

			if (empty($disableformtag)) {
				$formconfirm .= "</form>\n";
			}
			$formconfirm .= '<br>';

			if (!empty($conf->use_javascript_ajax)) {
				$formconfirm .= '<!-- code to disable button to avoid double clic -->';
				$formconfirm .= '<script nonce="' . getNonce() . '" type="text/javascript">' . "\n";
				$formconfirm .= '
				$(document).ready(function () {
					$(".confirmvalidatebutton").on("click", function() {
						console.log("We click on button");
						$(this).attr("disabled", "disabled");
						setTimeout(\'$(".confirmvalidatebutton").removeAttr("disabled")\', 3000);
						//console.log($(this).closest("form"));
						$(this).closest("form").submit();
					});
				});
				';
				$formconfirm .= '</script>' . "\n";
			}

			$formconfirm .= "<!-- end formconfirm -->\n";
		}

		return $formconfirm;
	}

	public function getTaskWithTimespent($firstdaytoshow, $lastdaytoshow, $userid)
  	{
		$arrayres = array();
	
		$sql = "SELECT DISTINCT";
		$sql .= " pt.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as ptt";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = ptt.fk_element";
		$sql .= " WHERE ptt.elementtype = 'task'";
		$sql .= " AND ptt.fk_user = ".((int) $userid);
		$sql .= " AND ptt.element_date BETWEEN '".$this->db->idate($firstdaytoshow)."' AND '".$this->db->idate($lastdaytoshow)."'";
		// if ($morewherefilter) {
		// 	$sql .= $morewherefilter;
		// }
	
		dol_syslog(get_class($this)."::getTaskWithTimespent", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$task = new Task($this->db);
				$task->fetch($obj->rowid);

				$arrayres[] = $task;

				$i++;
			}
		
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	
		return $arrayres;
	}

}



require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class FeuilleDeTempsLine. You can also remove this and generate a CRUD class for lines objects.
 */
class FeuilleDeTempsLine extends CommonObjectLine
{
	// To complete with content of an object FeuilleDeTempsLine
	// We should have a field rowid, fk_feuilledetemps and position

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
