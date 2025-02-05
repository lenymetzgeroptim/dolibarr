<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Faure Louis
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
 * \file        class/constat.class.php
 * \ingroup     constat
 * \brief       This file is a CRUD class file for Constat (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/constat/lib/constat.lib.php';

/**
 * Class for Constat
 */
class Constat extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'constat';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'constat';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'constat_constat';

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
	 * @var string String with name of icon for constat. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'constat@constat' if picto is file 'img/object_constat.png'.
	 */
	public $picto = 'fa-file';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_PRISE = 3;
	const STATUS_EN_COURS = 4;
	const STATUS_SOLDEE = 5;
	const STATUS_CLOTURE = 7;
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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>4, 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
		'label' => array('type'=>'varchar(255)', 'label'=>'Titre', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'default'=>'', 'searchall'=>1, 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'help'=>"Le numéro du constat", 'showoncombobox'=>'2', 'validate'=>'1',),
		'status' => array('type'=>'integer', 'label'=>'Statut', 'enabled'=>'1', 'position'=>43, 'notnull'=>1, 'visible'=>5, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validé', '3'=>'Vérifié', '4'=>'En cours', '5'=>'Soldée', '7'=>'Clôturé', '9'=>'Annulé'), 'validate'=>'1',),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'picto'=>'user', 'enabled'=>'1', 'position'=>502, 'notnull'=>1, 'visible'=>5, 'foreignkey'=>'user.rowid', 'csslist'=>'tdoverflowmax150',),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Projet', 'enabled'=>'1', 'position'=>505, 'notnull'=>0, 'visible'=>1,),
		'num_commande' => array('type'=>'integer:Commande:commande/class/commande.class.php:', 'label'=>'Numéro de commande', 'enabled'=>'1', 'position'=>504, 'notnull'=>0, 'visible'=>1,),
		'site' => array('type'=>'integer:Societe:societe/class/societe.class.php::(client:=:1)OR(client:=:3:)', 'label'=>'Site intervention', 'enabled'=>'1', 'position'=>508, 'notnull'=>0, 'visible'=>-1, 'index'=>1,),
		'dateEmeteur' => array('type'=>' timestamp', 'label'=>'Date création du constat', 'enabled'=>'1', 'position'=>506, 'notnull'=>0, 'visible'=>5,),
		'sujet' => array('type'=>'sellist:constat_sujet:label:rowid::(active:=:1)', 'label'=>'Typologie', 'enabled'=>'1', 'position'=>510, 'notnull'=>0, 'visible'=>1, 'csslist'=>'150',),
		'typeConstat' => array('type'=>'sellist:constat_type:label:rowid::(active:=:1)', 'label'=>'Type de constat', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>1,),
		'actionimmediate' => array('type'=>'boolean', 'label'=>'Action immédiate', 'enabled'=>'1', 'position'=>562, 'notnull'=>0, 'visible'=>1, 'help'=>"Coché si le constat est une action immédiate",),
		'actionimmediatecom' => array('type'=>'html', 'label'=>'Action immédiate commentaire', 'enabled'=>'1', 'position'=>563, 'notnull'=>0, 'visible'=>3, 'cssview'=>'wordbreak', 'help'=>"Détail action immédiate (Quoi, Qui, Où, Quand, Comment, Combien, Pourquoi)",),
		'analyseCauseRacine' => array('type'=>'html', 'label'=>'Analyse des causes racines', 'enabled'=>'1', 'position'=>576, 'notnull'=>0, 'visible'=>3, 'cssview'=>'wordbreak', 'help'=>"(Arbre des causes, description détaillée, etc).",),
		'recurent' => array('type'=>'boolean', 'label'=>'Recurent', 'enabled'=>'1', 'position'=>577, 'notnull'=>0, 'visible'=>1, 'help'=>"Coché si le constat est récurent",),
		'coutTotal' => array('type'=>'int', 'label'=>'Coût Total', 'enabled'=>'1', 'position'=>550, 'notnull'=>0, 'visible'=>1, 'help'=>"Coût total regroupant : Coût traitement horraire ,coût traitement financier et coût pénalité",),
		'descriptionConstat' => array('type'=>'html', 'label'=>'Description du constat', 'enabled'=>'1', 'position'=>515, 'notnull'=>0, 'visible'=>3, 'cssview'=>'wordbreak', 'help'=>"Description détaillée du constat (Quoi, Qui, Où, Quand, Comment, Combien, Pourquoi)",),
		'impactcomm' => array('type'=>'html', 'label'=>'Description impact', 'enabled'=>'1', 'position'=>525, 'notnull'=>0, 'visible'=>3, 'cssview'=>'wordbreak', 'help'=>"Description impact (réels et potentiels)",),
		'description' => array('type'=>'html', 'label'=>'Commentaire émetteur', 'enabled'=>'1', 'position'=>611, 'notnull'=>0, 'visible'=>3,),
		'infoClient' => array('type'=>'boolean', 'label'=>'Information Client Requise', 'enabled'=>'1', 'position'=>578, 'notnull'=>0, 'visible'=>1, 'help'=>"client informé requis ou non",),
		'commInfoClient' => array('type'=>'html', 'label'=>'Commentaire info client', 'enabled'=>'1', 'position'=>579, 'notnull'=>0, 'visible'=>3,'help'=>"précisez méthode d'information client ",),
		'accordClient' => array('type'=>'boolean', 'label'=>'Accord du Client', 'enabled'=>'1', 'position'=>595, 'notnull'=>0, 'visible'=>1, 'help'=>"Accord client requis ou non",),
		'commAccordClient' => array('type'=>'html', 'label'=>'Commentaire Accord client', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>3,'help'=>"précisez méthode d'information client ",),
		'controleClient' => array('type'=>'boolean', 'label'=>'Controle du Client', 'enabled'=>'1', 'position'=>606, 'notnull'=>0, 'visible'=>1, 'help'=>"Contrôle client requis ou non",),
		'commControleClient' => array('type'=>'html', 'label'=>'Commentaire Controle client', 'enabled'=>'1', 'position'=>610, 'notnull'=>0, 'visible'=>3,'help'=>"précisez méthode d'information client ",),
		'commRespAff' => array('type'=>'html', 'label'=>'Commentaire Responsable Affaire', 'enabled'=>'1', 'position'=>612, 'notnull'=>0, 'visible'=>3,),
		'commRespQ3' => array('type'=>'html', 'label'=>'Commentaire Responsable Q3SE', 'enabled'=>'1', 'position'=>613, 'notnull'=>0, 'visible'=>3,),
		'commServQ3' => array('type'=>'html', 'label'=>'Commentaire Service Q3SE', 'enabled'=>'1', 'position'=>614, 'notnull'=>0, 'visible'=>3,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>650, 'notnull'=>0, 'visible'=>0,),
		'date_eche' => array('type'=>'date', 'label'=>'Date échéance', 'enabled'=>'1', 'position'=>500, 'notnull'=>0, 'visible'=>1,),
		'dateCloture' => array('type'=>'date', 'label'=>'Date cloture', 'enabled'=>'1', 'position'=>561, 'notnull'=>0, 'visible'=>1, 'help'=>"Date à remplir quand le constat est soldé",),
	);
	
	public $rowid;
	public $ref;
	public $label;
	public $status;
	public $fk_user_creat;
	public $fk_project;
	public $num_commande;
	public $site;
	public $dateEmeteur;
	public $sujet;
	public $typeConstat;
	public $actionimmediate;
	public $actionimmediatecom;
	public $analyseCauseRacine;
	public $recurent;
	public $coutTotal;
	public $descriptionConstat;
	public $impactcomm;
	public $description;
	public $infoClient;
	public $commInfoClient;
	public $accordClient;
	public $commAccordClient;
	public $controleClient;
	public $commControleClient;
	public $commRespAff;
	public $commRespQ3;
	public $commServQ3;
	public $last_main_doc;
	public $date_eche;
	public $dateCloture;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'constat_constatline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_constat';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Constatline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('constat_constatdet');

	// /**
	//  * @var ConstatLine[]     Array of subtable lines
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
		
		// Vérifiez si la liste des projets est non vide
		
		if (
			(!$user->rights->constat->constat->ServiceQ3SE && 
			 !$user->rights->constat->constat->ResponsableQ3SE)
		) {
			
			if (!empty($projects)) {
				
				$rowids = implode(',', array_keys($projects));
		
				
				$this->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php::(t.rowid:IN:'.$rowids.')';
			}
		}

		/*global $user, $db;
		$commande = new Commande($db);
		
		// Vérifiez si la liste des projets est non vide
		
		if (!empty($commande)) {
				
			$rowids = implode(',', array_keys($commande));
		
				
			$this->fields['num_commande']['type'] = 'integer:Commande:commande/class/commande.class.php::(t.rowid:IN:'.$rowids.')';	
		}*/

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
		if (!empty($this->linkedObjectsIds) && empty($this->linked_objects)) {	// To use new linkedObjectsIds instead of old linked_objects
			$this->linked_objects = $this->linkedObjectsIds; // TODO Replace linked_objects with linkedObjectsIds
		}
		
		
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
	
		//Check parameters
		if (empty($id) && empty($ref)) {
			return -1;
		}
		
		$sql = "SELECT";
		$sql .= " co.rowid,";
		$sql .= " co.ref,";
		$sql .= " co.label,";
		$sql .= " co.status,";
		$sql .= " co.date_eche,";
		$sql .= " co.dateCloture,";
		$sql .= " co.fk_user_creat,";
		$sql .= " co.fk_project,";
		$sql .= " co.num_commande,";
		$sql .= " co.site,";
		$sql .= " co.dateEmeteur,";
		$sql .= " co.sujet,";
		$sql .= " co.descriptionConstat,";
		$sql .= " co.impactcomm,";
		$sql .= " co.description,";
		$sql .= " co.typeConstat,";
		$sql .= " co.rubrique,";
		$sql .= " co.actionimmediate,";
		$sql .= " co.actionimmediatecom,";
		$sql .= " co.analyseCauseRacine,";
		$sql .= " co.recurent,";
		$sql .= " co.coutTotal,";
		$sql .= " co.infoClient,";
		$sql .= " co.commInfoClient,";
		$sql .= " co.accordClient,";
		$sql .= " co.commAccordClient,";
		$sql .= " co.controleClient,";
		$sql .= " co.commControleClient,";
		$sql .= " co.commRespAff,";
		$sql .= " co.commRespQ3,";
		$sql .= " co.commServQ3";
		
		
		
		
	
		$sql .= " FROM ".MAIN_DB_PREFIX."constat_constat as co";
		if ($id) {
			$sql .= " WHERE co.rowid = ".((int) $id);
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
		$this->actionmsg = msgAgendaUpdateForConstat($this, 1);

		return $this->updateCommon($user, ($this->actionmsg ? 0 : $notrigger));
		

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

		

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->constat->constat->write))
		 || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->constat->constat->constat_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();
		$fk_project = $this->fk_project;
		$this->ref = $this->generateConstatReference($fk_project, $projectRef, $userId, $dateCreation);
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
				$result = $this->call_trigger('CONSTAT_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'constat/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'constat/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->constat->dir_output.'/constat/'.$oldref;
				$dirdest = $conf->constat->dir_output.'/constat/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->constat->dir_output.'/constat/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
		if (!$error) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			include_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
			include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
			include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
			global $db;
				$subject = '[OPTIM Industries] Notification automatique constat au status valider ';
			
				$from = 'erp@optim-industries.fr';
				
				$projet = new Project($db);
				$projet->fetch($this->fk_project);
				$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
		
				// Sélectionne les emails des utilisateurs dont les IDs sont dans $liste_chef_projet
				$sql = "SELECT email FROM " . MAIN_DB_PREFIX . "user WHERE rowid IN (" . implode(",", $liste_chef_projet) . ")";
				$result = $db->query($sql);
		
				// Si la requête a réussi
				if ($result) {
					$to = ''; // Initialisation de la chaîne d'emails
					while ($obj = $db->fetch_object($result)) {
						$email = $obj->email;
						// Ajoute l'email à la liste
						if (!empty($email)) {
							$to .= $email . ", ";
						}
					}
					$to = rtrim($to, ", ");
				}
					// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
				$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
				$resql_creator = $db->query($sql_creator);
				$creator_name = "";
				if ($resql_creator) {
					if ($db->num_rows($resql_creator) > 0) {
						$creator = $db->fetch_object($resql_creator);
						$creator_name = $creator->firstname . ' ' . $creator->lastname;
					}
				}
				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$this->id.'">'.$this->ref.'</a>';

				$to = rtrim($to, ", ");
				$msg = $langs->transnoentitiesnoconv("Bonjour, le constat ".$link." créé par ".$creator_name." a été validé. Veuillez compléter votre partie. Cordialement, votre système de notification.");
				$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$object->id);
				
				$res = $cmail->sendfile();
					
					

				$ret = $this->fetch($this->id); // Reload to get new records

				$model = $this->model_pdf;

				//$retgen = $this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				/*if ($retgen < 0) {
					setEventMessages($this->error, $this->errors, 'warnings');
				}*/
				//header("Location: ".$_SERVER["PHP_SELF"]."?id=".$this->id);
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->sendMailValidate();
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
	
	/**
	 * 
	 * Send mail to Q3SE
	 */
	public function sendMailValidate() {
		include_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
		include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

		global $db;
		$subject = '[OPTIM Industries] Notification automatique nouveau constat ';

		$from = 'erp@optim-industries.fr';
	
		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Resp. Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
					
			}
		}
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
        $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
        $link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$this->id.'">'.$this->ref.'</a>';


		$to = rtrim($to, ", ");
		$msg =  "test envoie de mail";
		$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$object->id);
		
		//$isSend = $cmail->sendfile();	
		if ($mail->error) {
			$res = 0;
		}
		// Send mail
		if ($res) {
			$res = $cmail->sendfile();
		}
	}

	/**
	 *	Set Status prise en compte
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	// public function setPrise($user, $notrigger = 0)
	// 	{
	// 		if ($this->status <= self::STATUS_PRISE) {
	// 			return 0;
	// 		}
	// 		return $this->setStatusCommon($user, self::STATUS_PRISE, $notrigger, 'CONSTAT_UNVALIDATE');
	// 	}
	
	/**
	 *	Set Status en cours
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setEnCours($user, $notrigger = 0)
		{
			
			if ($this->status <= self::STATUS_EN_COURS) {
				return 0;
			}
			return $this->setStatusCommon($user, self::STATUS_EN_COURS, $notrigger, 'CONSTAT_UNVALIDATE');
		}

	/**
	 *	Set Status Soldé
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setSolde($user, $notrigger = 0)
		{
			// Protection
			if ($this->status <= self::STATUS_SOLDEE) {
				return 0;
			}
			return $this->setStatusCommon($user, self::STATUS_SOLDEE, $notrigger, 'CONSTAT_UNVALIDATE');
		}
	
	/**
	 *	Set Status Clôturé
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setCloture($user, $notrigger = 0)
		{
			// Protection
			if ($this->status <= self::STATUS_CLOTURE) {
				return 0;
			}
			return $this->setStatusCommon($user, self::STATUS_CLOTURE, $notrigger, 'CONSTAT_UNVALIDATE');
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
		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CONSTAT_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function setCancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'CONSTAT_CANCEL');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->constat->write))
		 || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->constat->constat_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'CONSTAT_REOPEN');
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

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Constat").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/constat/constat_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowConstat");
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


		// La due date est déjà un timestamp, donc on peut directement la comparer
		if (in_array($this->status, [1, 3, 4])) {
			// Assurer que la date d'échéance existe et est valide
			if (!empty($this->date_eche)) {
				// Convertir la date d'échéance (qui est en timestamp) en entier
				$due_timestamp = (int) $this->date_eche;
						
								
				// Comparer les timestamps
				if ($due_timestamp < $current_timestamp) {
					// Si la date d'échéance est dans le passé, afficher l'icône
					$result .= img_warning($langs->trans("Late"));
				} 
			} 
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
			//$langs->load("constat@constat");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validé');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatus[self::STATUS_PRISE] = $langs->transnoentitiesnoconv('Vérifiée');
			$this->labelStatus[self::STATUS_EN_COURS] = $langs->transnoentitiesnoconv('En cours');
			$this->labelStatus[self::STATUS_SOLDEE] = $langs->transnoentitiesnoconv('Soldé');
			$this->labelStatus[self::STATUS_CLOTURE] = $langs->transnoentitiesnoconv('Classé');

			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validé');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_PRISE] = $langs->transnoentitiesnoconv('Vérifiée');
			$this->labelStatusShort[self::STATUS_EN_COURS] = $langs->transnoentitiesnoconv('En cours');
			$this->labelStatusShort[self::STATUS_SOLDEE] = $langs->transnoentitiesnoconv('Soldé');
			$this->labelStatusShort[self::STATUS_CLOTURE] = $langs->transnoentitiesnoconv('Classé');
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

		$objectline = new ConstatLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_constat = '.((int) $this->id)));

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
		$langs->load("constat@constat");

		if (empty($conf->global->CONSTAT_CONSTAT_ADDON)) {
			$conf->global->CONSTAT_CONSTAT_ADDON = 'mod_constat_standard';
		}

		if (!empty($conf->global->CONSTAT_CONSTAT_ADDON)) {
			$mybool = false;

			$file = $conf->global->CONSTAT_CONSTAT_ADDON.".php";
			$classname = $conf->global->CONSTAT_CONSTAT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/constat/");

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
    global $conf, $langs, $user; // Ajout de la variable $user

    $result = 0;
    $includedocgeneration = 1;

    // Charger les traductions
    $langs->load("constat@constat");

    // Si aucun modèle n'est spécifié, on utilise le modèle par défaut
    if (!dol_strlen($modele)) {
        $modele = 'standard_constat';

        if (!empty($this->model_pdf)) {
            $modele = $this->model_pdf;
        } elseif (!empty($conf->global->CONSTAT_ADDON_PDF)) {
            $modele = $conf->global->CONSTAT_ADDON_PDF;
        }
    }

    // Définir le chemin du modèle
    $modelpath = "core/modules/constat/doc/";

    // Correction : vérifier que $user est bien un objet de type User avant de générer le document
    if (!($user instanceof User)) {
        // Si l'utilisateur n'est pas de type User, on peut le recréer ou lever une exception
        dol_syslog("Erreur : L'objet \$user n'est pas de type User. Création d'un nouvel objet User.", LOG_ERR);

        // Par exemple, recréer un nouvel objet utilisateur avec l'ID de l'utilisateur courant
        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        $newuser = new User($this->db);
        $newuser->fetch($user->id); // On récupère les informations de l'utilisateur courant
        $user = $newuser; // Remplacer l'objet $user par le bon objet
    }

    // Générer le document si le modèle est spécifié
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
	
	
	public function updatePrise($notrigger = 0)
	{
		global $user; 
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."constat_constat";
		$sql .= " SET status = ".self::STATUS_PRISE;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}



		if (!$error) {
			$this->status = self::STATUS_PRISE;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}

	}

	public function updateEnCours($notrigger = 0)
	{
		global $user; 
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."constat_constat";
		$sql .= " SET status = ".self::STATUS_EN_COURS;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}



		if (!$error) {
			$this->status = self::STATUS_EN_COURS;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}

		
	}

	public function updateSolde($notrigger = 0)
	{
		global $user; 
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."constat_constat";
		$sql .= " SET status = ".self::STATUS_SOLDEE;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}


		if (!$error) {
			$this->status = self::STATUS_SOLDEE;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}

	}

	public function updateCloture($notrigger = 0)
	{
		global $user; 

		$error = 0;
		$sql = "UPDATE ".MAIN_DB_PREFIX."constat_constat";
		$sql .= " SET status = ".self::STATUS_CLOTURE;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

		
		if (!$error) {
			$this->status = self::STATUS_CLOTURE;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}

		
	}


	function updateCancel($notrigger = 0)
	{
		global $user; 
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."constat_constat";
		$sql .= " SET status = ".$this::STATUS_CANCELED;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}


		if (!$error && !$notrigger) {
			
			$this->actionmsg2 = $langs->transnoentitiesnoconv("CONSTAT_CANCELEDInDolibarr", $this->ref);
			// Call trigger
			$result = $this->call_trigger('CONSTAT_CANCELED', $user);
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


public function getManager()
{
	global $db;
	$sql  = 'SELECT u.rowid, u.lastname, u.firstname,';
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
     * Get agences.
     *
     */
    public function getAgencesBySoc()
    {
        global $conf, $db, $langs, $user;
        $name = 'OPTIM Industries';
 
        $sql = "SELECT DISTINCT u.rowid as userid, u.lastname, u.firstname, u.email, u.statut as status, u.entity, s.rowid as agenceid, s.nom as name, s.name_alias";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql .= ", ".MAIN_DB_PREFIX."user as u";
        $sql .= " , ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE u.entity in (0, 1) AND u.rowid = sc.fk_user";
        // $sql .= " AND s.rowid = sc.fk_soc";
        $sql .= " AND s.nom = '".$db->escape($name)."'";
        // $sql .= " AND u.rowid =".((int) $user->id);
   
 
        dol_syslog(get_class($this)."::getAgencesBySoc", LOG_DEBUG);
        $resql = $db->query($sql);
 
        if ($resql) {
            $num = $db->num_rows($resql);
           
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
   
                $agences[$obj->userid] = $obj->agenceid;
				
           
                $i++;
            }
 
            return $agences;
        } else {
            $this->error = $db->error();
            return -1;
        }
    }

	public function showSeparator($key, $object, $colspan = 2, $display_type = 'card', $mode = '')
	{
		global $conf, $langs;

		$tagtype='tr';
		$tagtype_dyn='td';

		if ($display_type=='line') {
			$tagtype='div';
			$tagtype_dyn='span';
			$colspan=0;
		}

		$extrafield_param = $table_element['param'][$key];
		$extrafield_param_list = array();
		if (!empty($extrafield_param) && is_array($extrafield_param)) {
			$extrafield_param_list = array_keys($extrafield_param['options']);
		}

		// Set $extrafield_collapse_display_value (do we have to collapse/expand the group after the separator)
		$extrafield_collapse_display_value = -1;
		$expand_display = false;
		if (is_array($extrafield_param_list) && count($extrafield_param_list) > 0) {
			$extrafield_collapse_display_value = intval($extrafield_param_list[0]);
			$expand_display = ((isset($_COOKIE['DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key]) || GETPOST('ignorecollapsesetup', 'int')) ? (empty($_COOKIE['DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key]) ? false : true) : ($extrafield_collapse_display_value == 2 ? false : true));
		}
		$disabledcookiewrite = 0;
		if ($mode == 'create') {
			// On create mode, force separator group to not be collapsable
			$extrafield_collapse_display_value = 1;
			$expand_display = true;	// We force group to be shown expanded
			$disabledcookiewrite = 1; // We keep status of group unchanged into the cookie
		}

		$out = '<'.$tagtype.' id="trextrafieldseparator'.$key.(!empty($object->id)?'_'.$object->id:'').'" class="trextrafieldseparator trextrafieldseparator'.$key.(!empty($object->id)?'_'.$object->id:'').'">';
		$out .= '<'.$tagtype_dyn.' '.(!empty($colspan)?'colspan="' . $colspan . '"':'').'>';
		// Some js code will be injected here to manage the collapsing of extrafields
		// Output the picto
		$out .= '<span class="'.($extrafield_collapse_display_value ? 'cursorpointer ' : '').($extrafield_collapse_display_value == 0 ? 'fas fa-square opacitymedium' : 'far fa-'.(($expand_display ? 'minus' : 'plus').'-square')).'"></span>';
		$out .= '&nbsp;';
		$out .= '<strong>';
		$out .= $langs->trans($this->attributes[$object->table_element]['label'][$key]);
		$out .= '</strong>';
		$out .= '</'.$tagtype_dyn.'>';
		$out .= '</'.$tagtype.'>';

		$collapse_group = $key.(!empty($object->id) ? '_'.$object->id : '');
		//$extrafields_collapse_num = $this->attributes[$object->table_element]['pos'][$key].(!empty($object->id)?'_'.$object->id:'');

		if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
			// Set the collapse_display status to cookie in priority or if ignorecollapsesetup is 1, if cookie and ignorecollapsesetup not defined, use the setup.
			$this->expand_display[$collapse_group] = $expand_display;

			if (!empty($conf->use_javascript_ajax)) {
				$out .= '<!-- Add js script to manage the collapse/uncollapse of extrafields separators '.$key.' -->'."\n";
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
				$out .= 'jQuery(document).ready(function(){'."\n";
				if (empty($disabledcookiewrite)) {
					if ($expand_display === false) {
						$out .= '   console.log("Inject js for the collapsing of extrafield '.$key.' - hide");'."\n";
						$out .= '   jQuery(".trextrafields_collapse'.$collapse_group.'").hide();'."\n";
					} else {
						$out .= '   console.log("Inject js for collapsing of extrafield '.$key.' - keep visible and set cookie");'."\n";
						$out .= '   document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
					}
				}
				$out .= '   jQuery("#trextrafieldseparator'.$key.(!empty($object->id)?'_'.$object->id:'').'").click(function(){'."\n";
				$out .= '       console.log("We click on collapse/uncollapse to hide/show .trextrafields_collapse'.$collapse_group.'");'."\n";
				$out .= '       jQuery(".trextrafields_collapse'.$collapse_group.'").toggle(100, function(){'."\n";
				$out .= '           if (jQuery(".trextrafields_collapse'.$collapse_group.'").is(":hidden")) {'."\n";
				$out .= '               jQuery("#trextrafieldseparator'.$key.(!empty($object->id)?'_'.$object->id:'').' '.$tagtype_dyn.' span").addClass("fa-plus-square").removeClass("fa-minus-square");'."\n";
				$out .= '               document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=0; path='.$_SERVER["PHP_SELF"].'"'."\n";
				$out .= '           } else {'."\n";
				$out .= '               jQuery("#trextrafieldseparator'.$key.(!empty($object->id)?'_'.$object->id:'').' '.$tagtype_dyn.' span").addClass("fa-minus-square").removeClass("fa-plus-square");'."\n";
				$out .= '               document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
				$out .= '           }'."\n";
				$out .= '       });'."\n";
				$out .= '   });'."\n";
				$out .= '});'."\n";
				$out .= '</script>'."\n";
			}
		} else {
			$this->expand_display[$collapse_group] = 1;
		}

		return $out;
	}

	function verifyStateOfElements() {
		
		$sql = "SELECT ac.status ";
		$sql .= "FROM ".MAIN_DB_PREFIX."actions_action as ac ";
		$sql .= "JOIN ".MAIN_DB_PREFIX."element_element as e ON ac.rowid = e.fk_source AND e.sourcetype = 'actions_action' ";
		$sql .= "JOIN ".MAIN_DB_PREFIX."constat_constat as co ON e.fk_target = co.rowid AND e.targettype = 'constat' ";
		$sql .= "WHERE (e.fk_source = ac.rowid AND e.sourcetype = 'actions_action') AND (e.fk_target = co.rowid AND e.targettype = 'constat') AND ac.status = 3 ";
		$sql .= "ORDER BY e.sourcetype";

		// Execute the query
		$result = $db->query($sql);

		// Check if all actions have status = 3
		$allStatusThree = true;
		while ($row = $db->fetch_object($result)) {
			if ($row->status != 3) {
				$allStatusThree = false;
				break;
			}
		}

	}


	public function drafttolong(){

		if ($this->status == 0) {
	
			$now = new DateTime(); 
			$date_creation = new DateTime($this->date_creation); 
			$interval = $date_creation->diff($now); 
				if ($interval->days > 3) {

				$subject = '[OPTIM Industries] Notification automatique alerte constat en brouillon ';

				$from = 'erp@optim-industries.fr';
				

					$user_group = New UserGroup($db);
				$user_group->fetch('', 'Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$to .= ", ";
							
					}
				}
				$user_group = New UserGroup($db);
				$user_group->fetch('', 'Resp. Q3SE');
				$liste_utilisateur = $user_group->listUsersForGroup();
				foreach($liste_utilisateur as $qualite){
					if(!empty($qualite->email)){
						$to .= $qualite->email;
						$torespQ3 .= ", ";
					
					}
				}
				$emeteur = New User($db);
				$emeteur->fetch($this->object->fk_user_creat);
				
				if(!empty($emeteur->email)){
				$toemeteur = $emeteur->email;
					}
					
					
				// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
				$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
				$resql_creator = $db->query($sql_creator);
				$creator_name = "";
				if ($resql_creator) {
					if ($db->num_rows($resql_creator) > 0) {
						$creator = $db->fetch_object($resql_creator);
						$creator_name = $creator->firstname . ' ' . $creator->lastname;
					}
				}	

				global $dolibarr_main_url_root;
				$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
				$link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$this->id.'">'.$this->ref.'</a>';

				
				$to .= $toemeteur;
				$to .= $torespQ3;
				$to = rtrim($to, ", ");
				$msg = $langs->transnoentitiesnoconv("Bonjour, le constat ".$link." créé par ".$creator_name. " est toujours à l'état de brouillon. Veuillez le passer à l'état validé. Cordialement, votre système de notification.");
				$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$this->object->id);
				
				// Send mail
				$res = $cmail->sendfile();
				if($res) {
					setEventMessages($langs->trans("EmailSend"), null, 'mesgs');	
				} else {
					setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
					print '<script>
					window.location.replace("'.$_SERVER["PHP_SELF"]."?id=".$this->object->id.'");
					</script>';
				}
			}
		}	
    }



	public function getActionsByConstat()
	{
		global $db;

		$sql = "SELECT e.fk_target, e.fk_source, a.status";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_element as e";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actions_action as a ON e.fk_target = a.rowid";
		$sql .= " WHERE e.fk_source = $this->id AND e.sourcetype = 'constat' ";

		$result = $db->query($sql);
		
		if ($result) {
			$nume = $db->num_rows($result);
			$i = 0;
			while ($i < $nume) {
				$obj = $db->fetch_object($result);
				$selectedelement[$obj->fk_source][$obj->fk_target] = $obj; 
				$status[] = $obj->status; 
				
				$i++;
			}
		} else {
			dol_print_error($db);
		}
		foreach($selectedelement as $elements) {
			foreach($elements as $val) {
				$is_exist = true;
				if($val->status !== '3'){
					$is_exist = false;
				}
			}
		}
		if($is_exist === true) {

			$subject = '[OPTIM Industries] Notification automatique toute les actions sont soldés ';

		$from = 'erp@optim-industries.fr';
		
		$projet = new Project($db);
		$projet->fetch($this->object->fk_project);
		$liste_chef_projet = $projet->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');

		// Sélectionne les emails des utilisateurs dont les IDs sont dans $liste_chef_projet
		$sql = "SELECT email FROM " . MAIN_DB_PREFIX . "user WHERE rowid IN (" . implode(",", $liste_chef_projet) . ")";
		$result = $db->query($sql);

		// Si la requête a réussi
		if ($result) {
			$to = ''; // Initialisation de la chaîne d'emails
			while ($obj = $db->fetch_object($result)) {
				$email = $obj->email;
				// Ajoute l'email à la liste
				if (!empty($email)) {
					$tochef .= $email . ", ";
				}
			}
		}

			$user_group = New UserGroup($db);
		$user_group->fetch('', 'Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$to .= ", ";
					
			}
		}
		$user_group = New UserGroup($db);
		$user_group->fetch('', 'Resp. Q3SE');
		$liste_utilisateur = $user_group->listUsersForGroup();
		foreach($liste_utilisateur as $qualite){
			if(!empty($qualite->email)){
				$to .= $qualite->email;
				$torespQ3 .= ", ";
				
			}
		}
		$emeteur = New User($db);
		$emeteur->fetch($this->object->fk_user_creat);
		
		if(!empty($emeteur->email)){
		$toemeteur = $emeteur->email;
			}	


			// Récupérer le nom et prénom de l'utilisateur qui a créé le constat
			$sql_creator = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $object->fk_user_creat;
			$resql_creator = $db->query($sql_creator);
			$creator_name = "";
			if ($resql_creator) {
				if ($db->num_rows($resql_creator) > 0) {
					$creator = $db->fetch_object($resql_creator);
					$creator_name = $creator->firstname . ' ' . $creator->lastname;
				}
			}
			
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
        $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
        $link = '<a href="'.$urlwithroot.'/custom/constat/constat_card.php?id='.$this->id.'">'.$this->ref.'</a>';

		$to .= $tochef;
		$to .= $toemeteur;
		$to .= $torespQ3;
		$to = rtrim($to, ", ");
		$msg = $langs->transnoentitiesnoconv(" Bonjour, toutes les actions du constat " .$link." créé par ".$creator_name. " sont soldées. Cordialement, votre système de notification.");
		$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track'.'_'.$this->object->id);
		
		// Send mail
		$res = $cmail->sendfile();
		if($res) {
			setEventMessages($langs->trans("EmailSend"), null, 'mesgs');	
		} else {
			setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
			print '<script>
			window.location.replace("'.$_SERVER["PHP_SELF"]."?id=".$this->object->id.'");
			</script>';
		}	

		}
		
		return $is_exist;
	}

	public function generateConstatReference($fk_project, $userId, $dateCreation)
{
    global $db;

    // 1. Rechercher la référence du projet via le fk_project
    $sqlProjectRef = "SELECT ref FROM ".MAIN_DB_PREFIX."projet WHERE rowid = ".intval($fk_project);
    
    // Ajout d'un log pour vérifier la requête SQL générée
  

    $resqlProjectRef = $db->query($sqlProjectRef);
    $projectRef = null; // Initialiser la variable

    if ($resqlProjectRef) {
        if ($db->num_rows($resqlProjectRef) > 0) {
            $projectRef = $db->fetch_object($resqlProjectRef)->ref;
        }
    } else {
        // Si la requête échoue, retour d'une erreur SQL
        return 'Erreur SQL : ' . $db->lasterror();
    }

    // 2. Récupérer le dernier rowid
    $sqlRowid = "SELECT MAX(rowid) as max_rowid FROM ".MAIN_DB_PREFIX."constat_constat";
    $resqlRowid = $db->query($sqlRowid);
    $newIndice = 138;

    if ($resqlRowid) {
        $objRowid = $db->fetch_object($resqlRowid);
        if ($objRowid && $objRowid->max_rowid !== null) {
            $newIndice = $objRowid->max_rowid; // Pas de +1 ici
        }
    }

    // 3. Construire la référence
    if ($projectRef) {
        $reference = 'FC_' . $projectRef . '_' . $newIndice;
    } else {
        // Si pas de projet, créer la référence avec "FC" et le rowid
        $reference = 'FC_' . $newIndice;
    }

    return $reference; // Retourne la référence construite
}

/**
	 *	Fetch array of objects linked to current object (object of enabled modules only). Links are loaded into
	 *		this->linkedObjectsIds array +
	 *		this->linkedObjects array if $loadalsoobjects = 1 or $loadalsoobjects = type
	 *  Possible usage for parameters:
	 *  - all parameters empty -> we look all link to current object (current object can be source or target)
	 *  - source id+type -> will get list of targets linked to source
	 *  - target id+type -> will get list of sources linked to target
	 *  - source id+type + target type -> will get list of targets of the type linked to source
	 *  - target id+type + source type -> will get list of sources of the type linked to target
	 *
	 *	@param	int			$sourceid			Object source id (if not defined, id of object)
	 *	@param  string		$sourcetype			Object source type (if not defined, element name of object)
	 *	@param  int			$targetid			Object target id (if not defined, id of object)
	 *	@param  string		$targettype			Object target type (if not defined, element name of object)
	 *	@param  string		$clause				'OR' or 'AND' clause used when both source id and target id are provided
	 *  @param  int			$alsosametype		0=Return only links to object that differs from source type. 1=Include also link to objects of same type.
	 *  @param  string		$orderby			SQL 'ORDER BY' clause
	 *  @param	int|string	$loadalsoobjects	Load also array this->linkedObjects. Use 0 to increase performances, Use 1 to load all, Use value of type ('facture', 'facturerec', ...) to load only a type of object.
	 *	@return int								<0 if KO, >0 if OK
	 *  @see	add_object_linked(), updateObjectLinked(), deleteObjectLinked()
	 */
	public function fetchObjectLinked2($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $clause = 'OR', $alsosametype = 1, $orderby = 'sourcetype', $loadalsoobjects = 1)
	{
		global $conf, $hookmanager, $action;

		$this->linkedObjectsIds = array();
		$this->linkedObjects = array();

		$justsource = false;
		$justtarget = false;
		$withtargettype = false;
		$withsourcetype = false;

		$parameters = array('sourcetype'=>$sourcetype, 'sourceid'=>$sourceid, 'targettype'=>$targettype, 'targetid'=>$targetid);
		// Hook for explicitly set the targettype if it must be differtent than $this->element
		$reshook = $hookmanager->executeHooks('setLinkedObjectSourceTargetType', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			if (!empty($hookmanager->resArray['sourcetype'])) $sourcetype = $hookmanager->resArray['sourcetype'];
			if (!empty($hookmanager->resArray['sourceid'])) $sourceid = $hookmanager->resArray['sourceid'];
			if (!empty($hookmanager->resArray['targettype'])) $targettype = $hookmanager->resArray['targettype'];
			if (!empty($hookmanager->resArray['targetid'])) $targetid = $hookmanager->resArray['targetid'];
		}

		if (!empty($sourceid) && !empty($sourcetype) && empty($targetid)) {
			$justsource = true; // the source (id and type) is a search criteria
			if (!empty($targettype)) {
				$withtargettype = true;
			}
		}
		if (!empty($targetid) && !empty($targettype) && empty($sourceid)) {
			$justtarget = true; // the target (id and type) is a search criteria
			if (!empty($sourcetype)) {
				$withsourcetype = true;
			}
		}

		$sourceid = (!empty($sourceid) ? $sourceid : $this->id);
		$targetid = (!empty($targetid) ? $targetid : $this->id);
		$sourcetype = (!empty($sourcetype) ? $sourcetype : $this->element);
		$targettype = (!empty($targettype) ? $targettype : $this->element);

		/*if (empty($sourceid) && empty($targetid))
		 {
		 dol_syslog('Bad usage of function. No source nor target id defined (nor as parameter nor as object id)', LOG_ERR);
		 return -1;
		 }*/

		// Links between objects are stored in table element_element
		$sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql .= " WHERE ";
		// if ($justsource || $justtarget) {
			// if ($justsource) {
			// 	$sql .= "fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourcetype)."'";
			// 	if ($withtargettype) {
			// 		$sql .= " AND targettype = '".$this->db->escape($targettype)."'";
			// 	}
			// } elseif ($justtarget) {
				$sql .= "fk_source = ".((int) $targetid)." AND sourcetype = '".$this->db->escape('constat')."'";
				// if ($withsourcetype) {
				// 	$sql .= " AND sourcetype = '".$this->db->escape($sourcetype)."'";
				// }
		// 	}
		// } else {
		// 	$sql .= "(fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourcetype)."')";
		// 	$sql .= " ".$clause." (fk_target = ".((int) $targetid)." AND targettype = '".$this->db->escape($targettype)."')";
		// }
		$sql .= ' ORDER BY '.$orderby;

		dol_syslog(get_class($this)."::fetchObjectLink", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				// if ($justsource || $justtarget) {
				// 	if ($justsource) {
						// $this->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
					// } elseif ($justtarget) {
					// 	$this->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
					// }
				// } else {
				// 	if ($obj->fk_source == $sourceid && $obj->sourcetype == $sourcetype) {
			 		// $this->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
				// 	}
				// var_dump($obj->targettype);
				// 	if ($obj->fk_target == $targetid && $obj->targettype == $targettype) {
				 		$this->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
				// 	}
				// }
		
				$i++;
			}

			if (!empty($this->linkedObjectsIds)) {
				$tmparray = $this->linkedObjectsIds;
				foreach ($tmparray as $objecttype => $objectids) {       // $objecttype is a module name ('facture', 'mymodule', ...) or a module name with a suffix ('project_task', 'mymodule_myobj', ...)
					// Parse element/subelement (ex: project_task, cabinetmed_consultation, ...)
					$module = $element = $subelement = $objecttype;
					$regs = array();
					if ($objecttype != 'supplier_proposal' && $objecttype != 'order_supplier' && $objecttype != 'invoice_supplier'
						&& preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
						$module = $element = $regs[1];
						$subelement = $regs[2];
					}

					$classpath = $element.'/class';
					// To work with non standard classpath or module name
					if ($objecttype == 'facture') {
						$classpath = 'compta/facture/class';
					} elseif ($objecttype == 'facturerec') {
						$classpath = 'compta/facture/class';
						$module = 'facture';
					} elseif ($objecttype == 'propal') {
						$classpath = 'comm/propal/class';
					} elseif ($objecttype == 'supplier_proposal') {
						$classpath = 'supplier_proposal/class';
					} elseif ($objecttype == 'shipping') {
						$classpath = 'expedition/class';
						$subelement = 'expedition';
						$module = 'expedition_bon';
					} elseif ($objecttype == 'delivery') {
						$classpath = 'delivery/class';
						$subelement = 'delivery';
						$module = 'delivery_note';
					} elseif ($objecttype == 'invoice_supplier' || $objecttype == 'order_supplier') {
						$classpath = 'fourn/class';
						$module = 'fournisseur';
					} elseif ($objecttype == 'fichinter') {
						$classpath = 'fichinter/class';
						$subelement = 'fichinter';
						$module = 'ficheinter';
					} elseif ($objecttype == 'subscription') {
						$classpath = 'adherents/class';
						$module = 'adherent';
					} elseif ($objecttype == 'contact') {
						 $module = 'societe';
					}elseif ($objecttype == 'constat') {
						$module = 'custom/constat';
				   }elseif ($objecttype == 'constat_constat') {
					$module = 'custom/constat';
			   	   }elseif ($objecttype == 'actions') {
						$module = 'custom/actions';
				   }elseif ($objecttype == 'actions') {
					$module = 'custom/actions';
			   	   }
					// Set classfile
					$classfile = strtolower($subelement);
					$classname = ucfirst($subelement);

					if ($objecttype == 'order') {
						$classfile = 'commande';
						$classname = 'Commande';
					} elseif ($objecttype == 'invoice_supplier') {
						$classfile = 'fournisseur.facture';
						$classname = 'FactureFournisseur';
					} elseif ($objecttype == 'order_supplier') {
						$classfile = 'fournisseur.commande';
						$classname = 'CommandeFournisseur';
					} elseif ($objecttype == 'supplier_proposal') {
						$classfile = 'supplier_proposal';
						$classname = 'SupplierProposal';
					} elseif ($objecttype == 'facturerec') {
						$classfile = 'facture-rec';
						$classname = 'FactureRec';
					} elseif ($objecttype == 'subscription') {
						$classfile = 'subscription';
						$classname = 'Subscription';
					} elseif ($objecttype == 'project' || $objecttype == 'projet') {
						$classpath = 'projet/class';
						$classfile = 'project';
						$classname = 'Project';
					} elseif ($objecttype == 'conferenceorboothattendee') {
						$classpath = 'eventorganization/class';
						$classfile = 'conferenceorboothattendee';
						$classname = 'ConferenceOrBoothAttendee';
						$module = 'eventorganization';
					} elseif ($objecttype == 'conferenceorbooth') {
						$classpath = 'eventorganization/class';
						$classfile = 'conferenceorbooth';
						$classname = 'ConferenceOrBooth';
						$module = 'eventorganization';
					} elseif ($objecttype == 'mo') {
						$classpath = 'mrp/class';
						$classfile = 'mo';
						$classname = 'Mo';
						$module = 'mrp';
					}elseif ($objecttype == 'constat') {
						$classpath = 'constat/class';
						$classfile = 'constat';
						$classname = 'Constat';
						$module = 'constat';
					}elseif ($objecttype == 'constat_constat') {
						$classpath = 'constat/class';
						$classfile = 'constat';
						$classname = 'Constat';
						$module = 'constat';
					}elseif ($objecttype == 'actions') {
						$classpath = 'actions/class';
						$classfile = 'action';
						$classname = 'Action';
						$module = 'actions';
					}elseif ($objecttype == 'actions_action') {
						$classpath = 'actions/class';
						$classfile = 'action';
						$classname = 'Action';
						$module = 'actions';
					}

					// Here $module, $classfile and $classname are set, we can use them.
					if ($conf->$module->enabled && (($element != $this->element) || $alsosametype)) {
						if ($loadalsoobjects && (is_numeric($loadalsoobjects) || ($loadalsoobjects === $objecttype))) {
							dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
							//print '/'.$classpath.'/'.$classfile.'.class.php '.class_exists($classname);
							if (class_exists($classname)) {
								foreach ($objectids as $i => $objectid) {	// $i is rowid into llx_element_element
									$object = new $classname($this->db);
									$ret = $object->fetch($objectid);
									if ($ret >= 0) {
										$this->linkedObjects[$objecttype][$i] = $object;
									}
								}
							}
						}
					} else {
						unset($this->linkedObjectsIds[$objecttype]);
					}
				}
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	public function load_board_constats($user)
{
    global $conf, $langs, $db;

    if ($user->socid) {
        return -1; // Protection pour éviter appel par utilisateur externe
    }

    // Préparation de la requête pour compter les constats
    $sql = 'SELECT COUNT(t.rowid) as nb_constats';
    $sql .= " FROM " . MAIN_DB_PREFIX . "constat_constat as t";

    $sql .= " WHERE";
    // Ajout du filtre sur les statuts
    $sql .= " t.status IN (1, 3, 4) AND"; // Filtrage sur les statuts

    // Condition pour les utilisateurs hors groupe 9
    $sql .= " (
        NOT EXISTS (
            SELECT 1 
            FROM " . MAIN_DB_PREFIX . "usergroup_user AS ug 
            WHERE ug.fk_user = " . $user->id . " 
            AND ug.fk_usergroup = 9
        ) 
        AND (
            t.fk_project IS NULL 
            OR t.fk_project IN (
                SELECT ec.element_id 
                FROM " . MAIN_DB_PREFIX . "element_contact AS ec 
                WHERE ec.fk_socpeople = " . $user->id . " 
                AND ec.fk_c_type_contact IN (160, 161, 170, 171)
            )
            OR t.fk_user_creat = " . $user->id . "
            OR EXISTS (
                SELECT 1
                FROM " . MAIN_DB_PREFIX . "user AS u
                JOIN " . MAIN_DB_PREFIX . "usergroup_user AS g ON u.rowid = g.fk_user
                JOIN " . MAIN_DB_PREFIX . "usergroup_rights AS r ON g.fk_usergroup = r.fk_usergroup
                WHERE r.fk_id IN (50002907, 50002906, 50002905)
                AND u.statut = 1
                AND u.rowid = " . $user->id . "
            )
            OR EXISTS (
                SELECT 1
                FROM " . MAIN_DB_PREFIX . "user_rights AS ur
                WHERE ur.fk_user = " . $user->id . "
                AND ur.fk_id IN (50002907, 50002906, 50002905)
            )
        )
    )";

    // Condition pour les utilisateurs dans le groupe 9
    $sql .= " OR (
        EXISTS (
            SELECT 1
            FROM " . MAIN_DB_PREFIX . "usergroup_user AS ug
            WHERE ug.fk_user = " . $user->id . "
            AND ug.fk_usergroup = 9
        )
        AND t.fk_project IS NOT NULL 
        AND EXISTS (
            SELECT 1
            FROM " . MAIN_DB_PREFIX . "projet_extrafields AS pe
            WHERE pe.fk_object = t.fk_project
            AND pe.agenceconcerne IN (
                SELECT sc.fk_soc
                FROM " . MAIN_DB_PREFIX . "societe_commerciaux AS sc
                WHERE sc.fk_user = " . $user->id . "
            )
        )
    )";

    // Exécution de la requête
    $resql = $this->db->query($sql);

    // Vérification si la requête s'est bien exécutée
    if ($resql) {
        // Récupérer les résultats
        $obj = $this->db->fetch_object($resql);

        // Créer la réponse
        $response = new WorkboardResponse();
        $response->label = $langs->trans("Vos constats ouverts");
        $response->labelShort = $langs->trans("Constats ouverts ");
        $response->url = DOL_URL_ROOT . '/custom/constat/constat_list.php';
        $response->nbtodo = $obj->nb_constats; // On récupère directement le nombre de constats

        return $response;
    } else {
        // Affichage des erreurs SQL
        dol_print_error($this->db);
        $this->error = $this->db->error();  // Ajoute le message d'erreur
        return -1;
    }
}

	

public function load_board_actions($user)
{
    global $conf, $langs, $db;

    if ($user->socid) {
        return -1; // Protection pour éviter appel par utilisateur externe
    }
	$now = dol_now();
    $sql = 'SELECT t.rowid';
    $sql .= " FROM " . MAIN_DB_PREFIX . "actions_action as t";

    // Requête principale pour sélectionner les actions
    $sql .= " WHERE t.status IN (0, 1, 2, 3, 4) AND ("; // Ajout du filtre sur les statuts

    $sql .= " (
        EXISTS (
            SELECT 1
            FROM " . MAIN_DB_PREFIX . "user AS u
            JOIN " . MAIN_DB_PREFIX . "usergroup_user AS g ON u.rowid = g.fk_user
            JOIN " . MAIN_DB_PREFIX . "usergroup_rights AS r ON g.fk_usergroup = r.fk_usergroup
            WHERE (r.fk_id IN (50003404))
            AND u.statut = 1
            AND u.rowid = " . $user->id . "
        ) OR EXISTS (
            SELECT 1
            FROM " . MAIN_DB_PREFIX . "user_rights AS ur
            WHERE ur.fk_user = " . $user->id . "
            AND ur.fk_id IN (50003404)
        ) OR (t.ref IS NOT NULL AND t.intervenant = " . $user->id . " AND t.status IN (0, 1, 2, 3, 4))
    ) OR EXISTS (
        SELECT 1 
        FROM " . MAIN_DB_PREFIX . "element_element AS ee 
        JOIN " . MAIN_DB_PREFIX . "constat_constat AS c ON (
            (ee.fk_source = t.rowid AND ee.sourcetype = 'action' AND ee.targettype = 'constat_constat' AND ee.fk_target = c.rowid)
            OR 
            (ee.fk_target = t.rowid AND ee.targettype = 'actions_action' AND ee.sourcetype = 'constat_constat' AND ee.fk_source = c.rowid)
        )
        WHERE c.fk_project IS NOT NULL
        AND c.fk_project IN (
            SELECT pe.fk_object 
            FROM " . MAIN_DB_PREFIX . "projet_extrafields AS pe 
            WHERE pe.agenceconcerne IN (
                SELECT sc.fk_soc 
                FROM " . MAIN_DB_PREFIX . "societe_commerciaux AS sc 
                WHERE sc.fk_soc = pe.agenceconcerne 
                AND EXISTS (
                    SELECT 1 
                    FROM " . MAIN_DB_PREFIX . "usergroup_user AS ug 
                    WHERE ug.fk_user = " . $user->id . " 
                    AND ug.fk_usergroup = 9
                )
            )
        )
    ) AND EXISTS (
        SELECT 1 
        FROM " . MAIN_DB_PREFIX . "usergroup_user AS ug 
        WHERE ug.fk_user = " . $user->id . " 
        AND ug.fk_usergroup = 9
    ))";

    // Exécution de la requête
    $resql = $this->db->query($sql);  
    if ($resql) {
        // Récupérer les résultats
        $obj = $this->db->fetch_object($resql);

        // Créer la réponse
        $response = new WorkboardResponse();
        $response->label = $langs->trans("Vos actions ouvertes");
        $response->labelShort = $langs->trans("Actions ouvertes");
        $response->url = DOL_URL_ROOT . '/custom/actions/action_list.php';

		// Initialiser les compteurs
        $response->nbtodo = 0;
        $response->nbtodolate = 0;

        while ($obj = $this->db->fetch_object($resql)) {
			$response->nbtodo++;
			
		}

		 // Deuxième requête pour récupérer les actions en retard
		 $sql_late = 'SELECT t.rowid, t.date_eche FROM ' . MAIN_DB_PREFIX . 'actions_action as t';
		 $sql_late .= " WHERE t.status IN ( 1, 2, 4) AND t.date_eche < '" . $this->db->idate($now) . "' AND (";
		 $sql_late .= " (
			 EXISTS (
				 SELECT 1
				 FROM " . MAIN_DB_PREFIX . "user AS u
				 JOIN " . MAIN_DB_PREFIX . "usergroup_user AS g ON u.rowid = g.fk_user
				 JOIN " . MAIN_DB_PREFIX . "usergroup_rights AS r ON g.fk_usergroup = r.fk_usergroup
				 WHERE (r.fk_id IN (50003404))
				 AND u.statut = 1
				 AND u.rowid = " . $user->id . "
			 ) OR EXISTS (
				 SELECT 1
				 FROM " . MAIN_DB_PREFIX . "user_rights AS ur
				 WHERE ur.fk_user = " . $user->id . "
				 AND ur.fk_id IN (50003404)
			 ) OR (t.ref IS NOT NULL AND t.intervenant = " . $user->id . " AND t.status IN (0, 1, 2, 3, 4))
		 ) OR EXISTS (
			 SELECT 1 
			 FROM " . MAIN_DB_PREFIX . "element_element AS ee 
			 JOIN " . MAIN_DB_PREFIX . "constat_constat AS c ON (
				 (ee.fk_source = t.rowid AND ee.sourcetype = 'action' AND ee.targettype = 'constat_constat' AND ee.fk_target = c.rowid)
				 OR 
				 (ee.fk_target = t.rowid AND ee.targettype = 'actions_action' AND ee.sourcetype = 'constat_constat' AND ee.fk_source = c.rowid)
			 )
			 WHERE c.fk_project IS NOT NULL
			 AND c.fk_project IN (
				 SELECT pe.fk_object 
				 FROM " . MAIN_DB_PREFIX . "projet_extrafields AS pe 
				 WHERE pe.agenceconcerne IN (
					 SELECT sc.fk_soc 
					 FROM " . MAIN_DB_PREFIX . "societe_commerciaux AS sc 
					 WHERE sc.fk_soc = pe.agenceconcerne 
					 AND EXISTS (
						 SELECT 1 
						 FROM " . MAIN_DB_PREFIX . "usergroup_user AS ug 
						 WHERE ug.fk_user = " . $user->id . " 
						 AND ug.fk_usergroup = 9
					 )
				 )
			 )
		 ) AND EXISTS (
			 SELECT 1 
			 FROM " . MAIN_DB_PREFIX . "usergroup_user AS ug 
			 WHERE ug.fk_user = " . $user->id . " 
			 AND ug.fk_usergroup = 9
		 ))";

		
 
		 // Exécution de la deuxième requête pour récupérer les actions en retard
		 $resql_late = $this->db->query($sql_late);  
		 if ($resql_late) {
			 // Parcours des actions en retard
			 while ($obj_late = $this->db->fetch_object($resql_late)) {
				 // Incrémente le compteur pour les actions en retard
				 $response->nbtodolate++;
			 }
		 }


        return $response;
    } else {
        // Affichage des erreurs SQL
        dol_print_error($this->db);
        $this->error = $this->db->error();  // Ajoute le message d'erreur
        return -1;
    }
}


}
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class ConstatLine. You can also remove this and generate a CRUD class for lines objects.
 */
class ConstatLine extends CommonObjectLine
{
	// To complete with content of an object ConstatLine
	// We should have a field rowid, fk_constat and position

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






