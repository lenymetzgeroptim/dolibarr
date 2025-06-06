<?php
/* Copyright (C) 2017  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023  Frédéric France          <frederic.france@netlogic.fr>
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
 * \file        class/action.class.php
 * \ingroup     actions
 * \brief       This file is a CRUD class file for Action (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/actions/lib/actions.lib.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Action
 */
class Action extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'actions';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'action';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'actions_action';

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
	 * @var string String with name of icon for action. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'action@actions' if picto is file 'img/object_action.png'.
	 */
	public $picto = 'fa-file';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_EN_COURS = 2;
	const STATUS_SOLDEE = 3;
	const STATUS_ATT_SOLDEE = 4;
	const STATUS_CLOTURE = 8;
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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>2, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'validate'=>'1', 'comment'=>"Reference of object"),
		'numeroo' => array('type'=>'integer', 'label'=>'Numéro', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'status' => array('type'=>'integer', 'label'=>'Statut', 'enabled'=>'1', 'position'=>32, 'notnull'=>1, 'visible'=>5, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validé', '2'=>'En cours', '3'=>'Soldée', '9'=>'Classé'), 'validate'=>'1',),
		'intervenant' => array('type'=>'integer:User:user/class/user.class.php:1', 'label'=>'Pilote', 'enabled'=>'1', 'position'=>42, 'notnull'=>0, 'visible'=>1, 'index'=>1,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'picto'=>'user', 'enabled'=>'1', 'position'=>25, 'notnull'=>1, 'visible'=>5, 'foreignkey'=>'user.rowid', 'csslist'=>'tdoverflowmax150',),
		'priority' => array('type'=>'integer', 'label'=>'Priorité', 'enabled'=>'1', 'position'=>31, 'notnull'=>1, 'visible'=>1,'arrayofkeyval'=>array('1'=>'1', '2'=>'2', '3'=>'3'), 'help'=>"Urgent et important : Priorité 1 (AC suivi mensuellement),Non urgent et important : Priorité 2 (Suivi 6mois),Urgent et pas important : Priorité 2 (Suivi 6mois),	Non urgent et pas important : Priorité 3 (Suivi 1an)",),																																
		'alert' => array('type'=>'method:alerte', 'label'=>'Alerte', 'enabled'=>'1', 'position'=>33, 'notnull'=>0, 'visible'=>5, 'index'=>1, 'help'=>"Alerte visuelle pour le retard de l'action"),
		'solde' => array('type'=>'method:solde_delais', 'label'=>'Solde délai', 'enabled'=>'1', 'position'=>35, 'notnull'=>0, 'visible'=>5, 'help'=>"Solde dans les délais (en nombre de jour).",),
		'origins' => array('type'=>'integer', 'label'=>'Origine', 'enabled'=>'1', 'position'=>33, 'notnull'=>1, 'visible'=>1,'arrayofkeyval'=>array('1'=>'AUDIT EXTERNE', '2'=>'AUDIT CROISE','3'=>'AUDIT INTERNE', '4'=>'FC', '5'=>'VEILLE REGLEMENTAIRE', '6'=>'REMONTE TERRAIN','7'=>'DECISION INTERNE','8'=>'DOCUMENT UNIQUE','10'=>'POINT Q3SE&RP','11'=>'REVUE DIRECTION','12'=>'VISITE TERRAIN'), 'help'=>"Origine de l'action",),
		'reference' => array('type'=>'varchar(255)', 'label'=>'Libellé', 'enabled'=>'1', 'position'=>36, 'notnull'=>0, 'visible'=>1, 'help'=>"Nom de la référence et du libellé de l'action",),
		'action_sse' => array('type'=>'integer', 'label'=>'Action SSE', 'enabled'=>'1', 'position'=>37, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('0'=>'Non', '1'=>'Oui'),),
		'action_rp' => array('type'=>'integer', 'label'=>'Action RP', 'enabled'=>'1', 'position'=>38, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('0'=>'Non', '1'=>'Oui'),),
		'action_surete' => array('type'=>'integer', 'label'=>'Action SURETE', 'enabled'=>'1', 'position'=>39, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('0'=>'Non', '1'=>'Oui'),),
		'CP' => array('type'=>'integer', 'label'=>'Préventive/Corrective', 'enabled'=>'1', 'position'=>42, 'notnull'=>0, 'visible'=>1, 'arrayofkeyval'=>array('1'=>'Préventive','2'=>'Corrective', '3'=>'Préventive/Corrective'),),
		'date_creation' => array('type'=>'date', 'label'=>'Date création action', 'enabled'=>'1', 'position'=>45, 'notnull'=>1, 'visible'=>1,),
		'action_txt' => array('type'=>'html', 'label'=>'Description action', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'help'=>"Déscription détaillé de l'action",),
		'date_eche' => array('type'=>'date', 'label'=>'Date échéance', 'enabled'=>'1', 'position'=>55, 'notnull'=>1, 'visible'=>1,),
		'avancement' => array('type'=>'integer', 'label'=>'Avancement', 'enabled'=>'1', 'position'=>4, 'notnull'=>1, 'visible'=>1, 'help'=>"Avancement en %",'arrayofkeyval'=>array('1'=>'0%','2'=>'25%','3'=>'50%','4'=>'75%','5'=>'100%',)),
		'date_sol' => array('type'=>'date', 'label'=>'Date solde', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>1),
		'diffusion' => array('type'=>'integer', 'label'=>'Diffusion document', 'enabled'=>'1', 'position'=>65, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('0'=>'Non', '1'=>'Oui'),),
		'com' => array('type'=>'html', 'label'=>'Commentaire', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1, 'help'=>"Commmentaire sur l'action ",),
		'eff_act' => array('type'=>'integer', 'label'=>'Efficacité action', 'enabled'=>'1', 'position'=>75, 'notnull'=>1, 'visible'=>1, 'help'=>"Différent niveau d'éfficacité de l'action : A = le plus éfficace C = le moins", 'arrayofkeyval'=>array('0'=>'','1'=>'A', '2'=>'B', '9'=>'C'),),
		'eff_act_description' => array('type'=>'html', 'label'=>'Détail efficacité', 'enabled'=>'1', 'position'=>77, 'notnull'=>0, 'visible'=>1,),
		'date_asse' => array('type'=>'date', 'label'=>'Date évalutation', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>1,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>270, 'notnull'=>0, 'visible'=>0,),
	); 
	public $rowid;
	public $ref;
	public $numeroo;
	public $intervenant;
	public $priority;
	public $fk_user_creat;
	public $alert;
	public $solde;
	public $origins;
	public $reference;
	public $action_sse;
	public $action_rp;
	public $action_surete;
	public $CP;
	public $date_creation;
	public $action_txt;
	public $date_eche;
	public $avancement;
	public $status;
	public $date_sol;
	public $diffusion;
	public $com;
	public $eff_act;
	public $eff_act_description;
	public $date_asse;
	public $last_main_doc;
	public $actionmsg;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'actions_actionline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_action';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Actionline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array('mychildtable' => array('name'=>'Action', 'fk_element'=>'fk_action'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('actions_actiondet');

	// /**
	//  * @var ActionLine[]     Array of subtable lines
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

		require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('actions', 'action', 'read')) {
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
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	// public function create($user, $notrigger = 0)
	// {
	// 	global $conf, $langs, $mysoc;
	// 	$error = 0;

	// 	// Clean parameters
	// 	$this->brouillon = 1; // set command as draft

	// 	dol_syslog(get_class($this)."::create user=".$user->id);

	// 	// Check parameters
	// 	if (!empty($this->ref)) {	// We check that ref is not already used
	// 		$result = self::isExistingObject($this->element, 0, $this->ref); // Check ref is not yet used
	// 		if ($result > 0) {
	// 			$this->error = 'ErrorRefAlreadyExists';
	// 			dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
	// 			$this->db->rollback();
	// 			return -1;
	// 		}
	// 	}

	// 	$now = dol_now();

	// 	$this->db->begin();

	// 	$sql = "INSERT INTO ".MAIN_DB_PREFIX."actions_action (";
	// 	// $sql .= " ref,";
	// 	// $sql .= " numeroo,";
	// 	$sql .= " priority,";
	// 	//$sql .= " rowid,";
	// 	$sql .= " alert,";
	// 	// $sql .= " solde,";
	// 	$sql .= " origins,";
	// 	// $sql .= " reference,";
	// 	$sql .= " action_sse,";
	// 	$sql .= " action_rp,";
	// 	$sql .= " date_creation,";
	// 	$sql .= " action_txt,";
	// 	$sql .= " date_eche,";
	// 	$sql .= " avancement,";
	// 	$sql .= " date_sol,";
	// 	$sql .= " diffusion";
	// 	// $sql .= " com";
	// 	$sql .= ")";
	// 	//$sql .= " VALUES ('(PROV)')";
	// 	// $sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : "null");
	// 	// $sql .= ", '".$this->db->idate($date)."'";
	// 	// $sql .= ", ".($this->source >= 0 && $this->source != '' ? $this->db->escape($this->source) : 'null');
	// 	// $sql .= ", '".$this->numeroo."'";
	// 	$sql .= " VALUES (".(int) $this->priority."";
	// 	//$sql .= ", ".(int) $this->id."";
	// 	$sql .= ", ".($this->alert > 0 ? ((int) $this->alert) : "null");
	// 	// $sql .= ", '".$this->db->escape($this->solde)."'";
	// 	$sql .= ", '".$this->db->escape($this->origins)."'";
	// 	// $sql .= ", '".$this->db->escape($this->reference)."'";
	// 	$sql .= ", '".$this->action_sse."'";
	// 	$sql .= ", '".$this->action_rp."'";
	// 	$sql .= ", '".$this->db->idate($this->date_creation)."'";
	// 	$sql .= ", '".$this->db->escape($this->action_txt)."'";
	// 	$sql .= ", '".$this->db->idate($this->date_eche)."'";
	// 	$sql .= ", '".$this->db->escape($this->avancement)."'";
	// 	$sql .= ", '".$this->db->idate($this->date_sol)."'";
	// 	$sql .= ", '".$this->db->escape($this->diffusion)."')";
	// 	// $sql .= ", '".$this->db->escape($this->com)."'";


	// 	dol_syslog(get_class($this)."::create", LOG_DEBUG);
	// 	$resql = $this->db->query($sql);
	// 	if ($resql) {
	// 		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'actions_action');

	// 		if ($this->id) {
				
	// 			// update ref
	// 			$initialref = '(PROV'.$this->id.')';
	// 			if (!empty($this->ref)) {
	// 				$initialref = $this->ref;
	// 			}

	// 			$sql = 'UPDATE '.MAIN_DB_PREFIX."actions_action SET ref='".$this->db->escape($initialref)."' WHERE rowid=".((int) $this->id);
	// 			if ($this->db->query($sql)) {
	// 				$this->ref = $initialref;

	// 				if (!empty($this->linkedObjectsIds) && empty($this->linked_objects)) {	// To use new linkedObjectsIds instead of old linked_objects
	// 					$this->linked_objects = $this->linkedObjectsIds; // TODO Replace linked_objects with linkedObjectsIds
	// 				}
				
	// 				// Add object linked
	// 				if (!$error && $this->id && !empty($this->linked_objects) && is_array($this->linked_objects)) {
	// 					foreach ($this->linked_objects as $origin => $tmp_origin_id) {
	// 						if (is_array($tmp_origin_id)) {       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
	// 							foreach ($tmp_origin_id as $origin_id) {
	// 								$ret = $this->add_object_linked($origin, $origin_id);
	// 								if (!$ret) {
	// 									$this->error = $this->db->lasterror();
	// 									$error++;
	// 								}
	// 							}
	// 						} else // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
	// 						{
	// 							$origin_id = $tmp_origin_id;
	// 							$ret = $this->add_object_linked($origin, $origin_id);
	// 							if (!$ret) {
	// 								$this->error = $this->db->lasterror();
	// 								$error++;
	// 							}
	// 						}
	// 					}
	// 				}
	// 				// var_dump($this->origin);
	// 				if (!$error && $this->id && !empty($conf->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN) && !empty($this->origin) && !empty($this->origin_id)) {   // Get contact from origin object
	// 					$originforcontact = $this->origin;
	// 					$originidforcontact = $this->origin_id;
	// 					if ($originforcontact == 'constat') {     // shipment and order share the same contacts. If creating from shipment we take data of order
	// 						require_once DOL_DOCUMENT_ROOT.'/custom/constat/class/constat.class.php';
	// 						$exp = new Constat($this->db);
	// 						$exp->fetch($this->origin_id);
	// 						$exp->fetchObjectLinked();
	// 						if (count($exp->linkedObjectsIds['constat']) > 0) {
	// 							foreach ($exp->linkedObjectsIds['constat'] as $key => $value) {
	// 								$originforcontact = 'constat';
	// 								if (is_object($value)) {
	// 									$originidforcontact = $value->id;
	// 								} else {
	// 									$originidforcontact = $value;
	// 								}
	// 								break; // We take first one
	// 							}
	// 						}
	// 					}
	// 				}


	// 				if (!$error) {
	// 					$result = $this->insertExtraFields();
	// 					if ($result < 0) {
	// 						$error++;
	// 					}
	// 				}

	// 				if (!$error && !$notrigger) {
	// 					// Call trigger
	// 					$result = $this->call_trigger('ACTIONS_ACTION', $user);
	// 					if ($result < 0) {
	// 						$error++;
	// 					}
	// 					// End call triggers
	// 				}

	// 				if (!$error) {
	// 					$this->db->commit();
	// 					return $this->id;
	// 				} else {
	// 					$this->db->rollback();
	// 					return -1 * $error;
	// 				}
	// 			}
	// 		} else {
	// 			$this->error = $this->db->lasterror();
	// 			$this->db->rollback();
	// 			return -1;
	// 		}

	// 		return 0;
	// 	} else {
	// 		dol_print_error($this->db);
	// 		$this->db->rollback();
	// 		return -1;
	// 	}
	// }

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
		// Check parameters
		if (empty($id) && empty($ref)) {
			return -1;
		}

		$sql = "SELECT";
		$sql .= " ac.rowid,";
		$sql .= " ac.ref,";
		$sql .= " ac.numeroo,";
		$sql .= " ac.intervenant,";
		$sql .= " ac.priority,";
		$sql .= " ac.fk_user_creat,";
		$sql .= " ac.alert,";
		$sql .= " ac.solde,";
		$sql .= " ac.origins,";
		$sql .= " ac.reference,";
		$sql .= " ac.action_sse,";
		$sql .= " ac.action_rp,";
		$sql .= " ac.action_surete,";
		$sql .= " ac.CP,";
		$sql .= " ac.date_creation,";
		$sql .= " ac.action_txt,";
		$sql .= " ac.date_eche,";
		// $sql .= " ac.user_pilote,";
		$sql .= " ac.avancement,";
		$sql .= " ac.status,";
		$sql .= " ac.date_sol,";
		$sql .= " ac.diffusion,";
		$sql .= " ac.com,";
		$sql .= " ac.rowid_constat,";
	
		$sql .= " ac.eff_act,";
		$sql .= " ac.eff_act_description,";
		$sql .= " ac.date_asse";
		
		
		$sql .= " FROM ".MAIN_DB_PREFIX."actions_action as ac";
		if ($id) {
			$sql .= " WHERE ac.rowid = ".((int) $id);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->ref;
				$this->numeroo   = $obj->numeroo;
				$this->intervenant   = $obj->intervenant;
				$this->priority   = $obj->priority;
				$this->fk_user_creat   = $obj->fk_user_creat;
				$this->alert   = $this->alerte($obj->status,$obj->date_eche);
			//	$this->alert = $this->alerte($obj->status, date('Y-m-d', $this->db->idate($obj->date_eche)));
				$this->solde   = $this->solde_delai($obj->status,$obj->date_eche,$obj->date_sol);
				$this->origins   = $obj->origins;
				$this->reference   = $obj->reference;
				$this->action_sse   = $obj->action_sse;
				$this->action_rp   = $obj->action_rp;
				$this->action_surete   = $obj->action_surete;
				$this->CP   = $obj->CP;
				$this->date_creation = $this->db->idate($obj->date_creation);
				$this->action_txt   = $obj->action_txt;
				$this->date_eche = $this->db->idate($obj->date_eche);
				$this->avancement	= $obj->avancement;
				$this->status	= $obj->status;
				$this->date_sol = $this->db->idate($obj->date_sol);
				$this->diffusion	= $obj->diffusion;
				$this->com	= $obj->com;
				$this->eff_act	= $obj->eff_act;
				$this->eff_act_description	= $obj->eff_act_description;
				$this->date_asse = $this->db->idate($obj->date_asse);

				$this->fetch_optionals();
				//$this->user_pilote = $this->listUserPilote();
				//var_dump($rowid_constat);
				//$this->inanimation_talk(412);
				
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
		$this->actionmsg = msgAgendaUpdateForAction($this, 1);
		return $this->updateCommonCustom($user, ($this->actionmsg ? 0 : $notrigger));

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
		$error = 0;

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('ACTION_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
				$error++;
			}
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('actions','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && !empty($user->rights->actions->action->action_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();
		$this->ref = $this->generateActionsReference($fk_project, $projectRef, $userId, $dateCreation);
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
				$result = $this->call_trigger('ACTIONS_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'action/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'action/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'action/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'action/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->actions->dir_output.'/action/'.$oldref;
				$dirdest = $conf->actions->dir_output.'/action/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->actions->dir_output.'/action/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			include_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
			include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
			include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
			global $db;
		
			$subject = '[OPTIM Industries] Notification automatique - Nouvelle action assignée';

			$from = 'erp@optim-industries.fr';
			$emails = [];

			// Récupération de l'email du pilote
			$pilote = new User($db);
			$pilote->fetch($this->intervenant);
			if (!empty($pilote->email) && filter_var($pilote->email, FILTER_VALIDATE_EMAIL)) {
				$emails[] = $pilote->email;
			}

			// Suppression des doublons
			$emails = array_unique($emails);
			$to = implode(", ", $emails);

			global $dolibarr_main_url_root;
			$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
			$link = '<a href="' . $urlwithroot . '/custom/actions/action_card.php?id=' . $this->id . '">' . $this->ref . '</a>';

			$msg = $langs->transnoentitiesnoconv("Bonjour, vous avez été ajouté à une nouvelle action : " . $link);

			$cmail = new CMailFile($subject, $to, $from, $msg, '', '', '', $cc, '', 0, 1, '', '', 'track' . '_' . $this->id);

			// Envoi du mail
			$res = $cmail->sendfile();
			if ($res) {
				setEventMessages($langs->trans("EmailSend"), null, 'mesgs');
				print '<script>window.location.replace("' . $_SERVER["PHP_SELF"] . "?id=" . $this->id . '");</script>';
			} else {
				setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('actions','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('actions','actions_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'ACTIONS_MYOBJECT_UNVALIDATE');
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('actions','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('actions','actions_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'ACTIONS_MYOBJECT_CANCEL');
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

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('actions','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('actions','actions_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'ACTIONS_MYOBJECT_REOPEN');
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
			return ['optimize' => $langs->trans("ShowAction")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Action").'</u>';
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

		$url = dol_buildpath('/actions/action_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowAction");
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

		// Récupérer la date actuelle sous forme de timestamp
		$current_timestamp = time();  // time() retourne le timestamp actuel (secondes depuis 1970)



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

		// La due date est déjà un timestamp, donc on peut directement la comparer
		if (in_array($this->status, [1, 2, 4])) {
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
			//$langs->load("actions@actions");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validé');
			$this->labelStatus[self::STATUS_EN_COURS] = $langs->transnoentitiesnoconv('En cours');
			$this->labelStatus[self::STATUS_SOLDEE] = $langs->transnoentitiesnoconv('Soldée');
			$this->labelStatus[self::STATUS_ATT_SOLDEE ] = $langs->transnoentitiesnoconv('Attente validation Soldée');
			$this->labelStatus[self::STATUS_CLOTURE] = $langs->transnoentitiesnoconv('Cloturée');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Classé sans suite');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validé');
			$this->labelStatusShort[self::STATUS_EN_COURS] = $langs->transnoentitiesnoconv('En cours');
			$this->labelStatusShort[self::STATUS_SOLDEE] = $langs->transnoentitiesnoconv('Soldée');
			$this->labelStatusShort[self::STATUS_ATT_SOLDEE] = $langs->transnoentitiesnoconv('Attente validation Soldée');
			$this->labelStatus[self::STATUS_CLOTURE] = $langs->transnoentitiesnoconv('Cloturée');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Classé sans suite');
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

		$objectline = new ActionLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_action = '.((int) $this->id)));

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
		$langs->load("actions@actions");

		if (!getDolGlobalString('ACTIONS_MYOBJECT_ADDON')) {
			$conf->global->ACTIONS_MYOBJECT_ADDON = 'mod_action_standard';
		}

		if (getDolGlobalString('ACTIONS_MYOBJECT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('ACTIONS_MYOBJECT_ADDON').".php";
			$classname = getDolGlobalString('ACTIONS_MYOBJECT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/actions/");

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

		$langs->load("actions@actions");

		if (!dol_strlen($modele)) {
			$modele = 'standard_action';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('MYOBJECT_ADDON_PDF')) {
				$modele = getDolGlobalString('MYOBJECT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/actions/doc/";

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
				$sql .= "fk_target = ".((int) $targetid)." AND targettype = '".$this->db->escape('actions_action')."'";
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
				 		$this->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
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

	/**
	 * 
	 * TOUTE LES FONCTION POUR LES CHANGEMENT DE ROLE AVEC LES BOUTONS 
	 * 
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


	public function setEncours($user, $notrigger = 0)
		{
			// Protection
			if ($this->status <= self::STATUS_EN_COURS) {
				return 0;
			}
			return $this->setStatusCommon($user, self::STATUS_EN_COURS, $notrigger, 'CONSTAT_UNVALIDATE');
		}
	
	public function setSolde($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_SOLDEE) {
			return 0;	
		}
		
		return $this->setStatusCommon($user, self::STATUS_SOLDEE, $notrigger, 'CONSTAT_UNVALIDATE');
	}
	public function setAttSolde($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_ATT_SOLDEE) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_ATT_SOLDEE , $notrigger, 'CONSTAT_UNVALIDATE');
	}
	public function setClasse($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_CANCELED) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'CONSTAT_UNVALIDATE');
	}

	public function setCloture($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_CLOTURE) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_CLOTURE, $notrigger, 'CONSTAT_UNVALIDATE');
	}
	

	public function updateEnCours()
	{
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."actions_action";
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

		public function updateSolde()
	{
		$error = 0;


		$today = date('Y-m-d');

		$sql = "UPDATE " . MAIN_DB_PREFIX . "actions_action";
		$sql .= " SET status = " . self::STATUS_SOLDEE . ", date_sol = '" . $this->db->escape($today) . "'";
		$sql .= " WHERE rowid = " . ((int)$this->id);

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
	public function updateAttSolde()
	{
		$error = 0;


		$today = date('Y-m-d');

		$sql = "UPDATE " . MAIN_DB_PREFIX . "actions_action";
		$sql .= " SET status = " . self::STATUS_ATT_SOLDEE . ", date_sol = '" . $this->db->escape($today) . "'";
		$sql .= " WHERE rowid = " . ((int)$this->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}
		if (!$error) {
			$this->status = self::STATUS_ATT_SOLDEE;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	public function updateClasse()
	{
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."actions_action";
		$sql .= " SET status = ".self::STATUS_CANCELED;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
		if (!$error) {
			$this->status = self::STATUS_CANCELED;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	
	public function updateCloture()
	{
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."actions_action";
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



	public function SetUserInGroup($group)
   {
     // phpcs:enable
     global $conf, $langs, $user;
  
     $error = 0;
  
     $this->db->begin();
  
     $sql = "DELETE FROM ".$this->db->prefix()."usergroup_user";
     $sql .= " WHERE fk_user  = ".((int) $this->intervanant);
     $sql .= " AND fk_usergroup = ".((int) $group);
    
  
     $result = $this->db->query($sql);
  
     $sql = "INSERT INTO ".$this->db->prefix()."usergroup_user ( fk_user, rowid)";
     $sql .= " VALUES (".((int) $this->intervenant).",".((int) $group).")";
  
     $result = $this->db->query($sql);
     if ($result) {
       if (!$error && !$notrigger) {
         $this->newgroupid = $group; // deprecated. Remove this.
         $this->context = array('audit'=>$langs->trans("UserSetInGroup"), 'newgroupid'=>$group);
  
         // Call trigger
         $result = $this->call_trigger('USER_MODIFY', $user);
         if ($result < 0) {
           $error++;
         }
         // End call triggers
       }
  
       if (!$error) {
         $this->db->commit();
         return 1;
       } else {
         dol_syslog(get_class($this)."::SetInGroup ".$this->error, LOG_ERR);
         $this->db->rollback();
         return -2;
       }
     } else {
       $this->error = $this->db->lasterror();
       $this->db->rollback();
       return -1;
     }
   }



   function alerte($status, $timestamp) {

	date_default_timezone_set('Europe/Paris');
    if (empty($timestamp)) {
        return "";
    }

    try {
        $date_eche = new DateTime($timestamp);
    } catch (Exception $e) {
        return "";
    }

    $today = new DateTime();
    if (empty($today)) {
        throw new Exception("Failed to create DateTime object for today");
    }

    $interval = $today->diff($date_eche);
    if (!$interval) {
        throw new Exception("Failed to calculate date difference");
    }

    $days = (int)$interval->format('%r%a'); 

    if ($status == "soldee" || $status == "classee") {
        return "";
    } elseif ($days > 20) {
        return "vert";
    } elseif ($days < 0 || $days < 10) { 
        return "rouge";
    } else {
        return "orange";
    }
}

function solde_delai($status, $date_eche, $date_sol) {
    if ($status != "en cours") {
        if ($date_sol === null) {
            // Retourner une valeur par défaut ou une erreur
            return "";
        }
        $date1 = new DateTime($date_eche);
        $date2 = new DateTime($date_sol);
        $interval = $date1->diff($date2);
        return $interval->days;
    } else {
        return "";
    }
}

public function updateCommonCustom(User $user, $notrigger = 0)
  {
    dol_syslog(get_class($this)."::updateCommon update", LOG_DEBUG);
 
    $error = 0;
 
    $now = dol_now();
 
    // $this->oldcopy should have been set by the caller of update
    //if (empty($this->oldcopy)) {
    //  dol_syslog("this->oldcopy should have been set by the caller of update (here properties were already modified)", LOG_WARNING);
    //  $this->oldcopy = dol_clone($this, 2);
    //}
 
    $fieldvalues = $this->setSaveQuery();
 
    // Note: Here, $fieldvalues contains same keys (or less) that are inside ->fields
 
    if (array_key_exists('date_modification', $fieldvalues) && empty($fieldvalues['date_modification'])) {
      $fieldvalues['date_modification'] = $this->db->idate($now);
    }
    if (array_key_exists('fk_user_modif', $fieldvalues) && !($fieldvalues['fk_user_modif'] > 0)) {
      $fieldvalues['fk_user_modif'] = $user->id;
    }
    if (array_key_exists('user_modification_id', $fieldvalues) && !($fieldvalues['user_modification_id'] > 0)) {
      $fieldvalues['user_modification_id'] = $user->id;
    }
    if (array_key_exists('ref', $fieldvalues)) {
      $fieldvalues['ref'] = dol_string_nospecial($fieldvalues['ref']); // If field is a ref, we sanitize data
    }
 
    unset($fieldvalues['rowid']); // The field 'rowid' is reserved field name for autoincrement field so we don't need it into update.
 
    // Add quotes and escape on fields with type string
    $keys = array();
    $values = array();
    $tmp = array();
    foreach ($fieldvalues as $k => $v) {
      $keys[$k] = $k;
      $value = $this->fields[$k];
      // @phan-suppress-next-line PhanPluginSuspiciousParamPosition
      $values[$k] = $this->quote($v, $value);
      if (($value["type"] == "text") && !empty($value['arrayofkeyval']) && is_array($value['arrayofkeyval'])) {
        // Clean values for text with selectbox
        $v = preg_replace('/\s/', ',', $v);
        $v = preg_replace('/,+/', ',', $v);
      }
      // @phan-suppress-next-line PhanPluginSuspiciousParamPosition
      $tmp[] = $k.'='.$this->quote($v, $this->fields[$k]);
    }
 
    // Clean and check mandatory fields
    foreach ($keys as $key) {
      if (preg_match('/^integer:/i', $this->fields[$key]['type']) && $values[$key] == '-1') {
        $values[$key] = ''; // This is an implicit foreign key field
      }
      if (!empty($this->fields[$key]['foreignkey']) && $values[$key] == '-1') {
        $values[$key] = ''; // This is an explicit foreign key field
      }
 
      //var_dump($key.'-'.$values[$key].'-'.($this->fields[$key]['notnull'] == 1));
      /*
       if ($this->fields[$key]['notnull'] == 1 && empty($values[$key]))
       {
       $error++;
       $this->errors[]=$langs->trans("ErrorFieldRequired", $this->fields[$key]['label']);
       }*/
    }
 
    $sql = 'UPDATE '.$this->db->prefix().$this->table_element.' SET '.implode(', ', $tmp).' WHERE rowid='.((int) $this->id);
 
    $this->db->begin();
 
    if (!$error) {
      $res = $this->db->query($sql);
      if (!$res) {
        $error++;
        $this->errors[] = $this->db->lasterror();
      }
    }
 
    // Update extrafield
    if (!$error) {
      $result = $this->insertExtraFields(); // This delete and reinsert extrafields
      if ($result < 0) {
        $error++;
      }
    }
 
    // Triggers
    if (!$error && !$notrigger) {
      // Call triggers
      $result = $this->call_trigger(strtoupper(get_class($this)).'S_MODIFY', $user);
      if ($result < 0) {
        $error++;
      } //Do also here what you must do to rollback action if trigger fail
      // End call triggers
    }
 
    // Commit or rollback
    if ($error) {
      $this->db->rollback();
      return -1;
    } else {
      $this->db->commit();
      return $this->id;
    }
  }

/**
 * 
 */
public function insertIntoElementElement($arrayofselected, $idaction)
	{
		// phpcs:enable
		global $conf, $langs, $user;
		$this->db->begin();

		foreach($arrayofselected as $selected){
			$sql = "INSERT INTO ".$this->db->prefix()."element_element(fk_source, sourcetype, fk_target, targettype)";
			$sql .= " VALUES (".((int) $selected).", 'constat', ".((int) $idaction).", 'actions_action')";
		$result = $this->db->query($sql);
		}
		
		if ($result) {
			if (!$notrigger) {
				$this->context = array('audit'=>$langs->trans("ElementInserted"), 'newElementId'=>$id);

				// Call trigger
				$result = $this->call_trigger('ELEMENT_INSERT', $user);
				if ($result < 0) {
					dol_syslog(get_class($this)."::insertIntoElementElement ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -2;
				}
			}
			
			

			$this->db->commit();
			return 1;
		} 
		else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
		
}

public function generateActionsReference($fk_project, $userId, $dateCreation)
{
    global $db, $user;

    // Récupérer l'ID de l'action actuelle (si disponible)
    $currentActionId = isset($this->id) ? $this->id : null;

    // Vérifier si nous avons un ID valide
    if ($currentActionId === null) {
        // Gérer le cas où l'ID n'est pas disponible
        return 'Erreur: ID d\'action non disponible';
    }

    // 1. Utiliser l'ID de l'action actuelle pour générer la référence
    $newIndice = $currentActionId;

    // 2. Générer la référence sous le format AC-<newIndice>
    $constatRef = 'AC' . $newIndice;

    return $constatRef; // Retourner la référence générée
}




	public function getElementElement($idaction){

		global $conf, $langs, $user, $db;		

		$sql = "SELECT rowid, fk_source, sourcetype, fk_target, targettype";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_element";
		$sql .= " WHERE fk_target = ".((int) $idaction);
		$sql .= " AND sourcetype = 'constat'";
		$sql .= " AND targettype = 'actions_action'";


		$result = $this->db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num) {

					$obj = $db->fetch_object($result);
					$exclude[] = $obj->fk_source;
					
					$i++;
				}
				return $exclude;
			} else {
				dol_print_error($db);
			}
		}


		public function formatDateString(&$arrayrecord, $listfields, $record_key)
{
	$value = dol_print_date(dol_stringtotime($arrayrecord[$record_key]['val']), '%Y-%m-%d');
	return $value;
}

public function formatDateStringHours(&$arrayrecord, $listfields, $record_key)
{
	$value = dol_print_date(dol_stringtotime($arrayrecord[$record_key]['val']), '%Y-%m-%d %H:%M:%S');
	return $value;
}

}






/**
 * Class ActionLine. You can also remove this and generate a CRUD class for lines objects.
 */
class ActionLine extends CommonObjectLine
{
	// To complete with content of an object ActionLine
	// We should have a field rowid, fk_action and position

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
