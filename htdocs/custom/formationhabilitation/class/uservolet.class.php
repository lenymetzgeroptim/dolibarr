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
 * \file        class/uservolet.class.php
 * \ingroup     formationhabilitation
 * \brief       This file is a CRUD class file for UserVolet (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
dol_include_once('/formationhabilitation/class/userhabilitation.class.php');
dol_include_once('/formationhabilitation/class/userformation.class.php');
dol_include_once('/formationhabilitation/class/userautorisation.class.php');
dol_include_once('/formationhabilitation/class/volet.class.php');

/**
 * Class for UserVolet
 */
class UserVolet extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'formationhabilitation';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'uservolet';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'formationhabilitation_uservolet';

	/**
	 * @var int  	Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for uservolet. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'uservolet@formationhabilitation' if picto is file 'img/object_uservolet.png'.
	 */
	public $picto = 'fa-book_fas_#004a95';


	const STATUS_VALIDATION0 = 0;
	const STATUS_VALIDATION1 = 1;
	const STATUS_VALIDATION2 = 2;
	const STATUS_VALIDATION3 = 3;
	const STATUS_VALIDATION_WITHOUT_USER = 4;
	const STATUS_VALIDATED = 5;
	const STATUS_EXPIRE = 7;
	const STATUS_SUSPEND = 8;
	const STATUS_CLOSE = 9;

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
		"ref" => array("type"=>"varchar(128)", "label"=>"Ref", "enabled"=>"1", 'position'=>20, 'notnull'=>1, "visible"=>"4", "index"=>"1", "searchall"=>"1", "validate"=>"1", "comment"=>"Reference of object"),
		"date_creation" => array("type"=>"datetime", "label"=>"DateCreation", "enabled"=>"1", 'position'=>500, 'notnull'=>1, "visible"=>"-2",),
		"tms" => array("type"=>"timestamp", "label"=>"DateModification", "enabled"=>"1", 'position'=>501, 'notnull'=>0, "visible"=>"-2",),
		"fk_user_creat" => array("type"=>"integer:User:user/class/user.class.php", "label"=>"UserAuthor", "picto"=>"user", "enabled"=>"1", 'position'=>510, 'notnull'=>1, "visible"=>"-2", "csslist"=>"tdoverflowmax150",),
		"fk_user_modif" => array("type"=>"integer:User:user/class/user.class.php", "label"=>"UserModif", "picto"=>"user", "enabled"=>"1", 'position'=>511, 'notnull'=>-1, "visible"=>"-2", "csslist"=>"tdoverflowmax150",),
		"last_main_doc" => array("type"=>"varchar(255)", "label"=>"LastMainDoc", "enabled"=>"1", 'position'=>600, 'notnull'=>0, "visible"=>"0",),
		"import_key" => array("type"=>"varchar(14)", "label"=>"ImportId", "enabled"=>"1", 'position'=>1000, 'notnull'=>-1, "visible"=>"-2",),
		"model_pdf" => array("type"=>"varchar(255)", "label"=>"Model pdf", "enabled"=>"1", 'position'=>1010, 'notnull'=>-1, "visible"=>"0",),
		"status" => array("type"=>"integer", "label"=>"Status", "enabled"=>"1", 'position'=>2000, 'notnull'=>1, "visible"=>"1", "index"=>"1", "arrayofkeyval"=>array("0" => "Brouillon", "1" => "Valid&eacute;", "2" => "En cours d'approbation", "3" => "En cours d'approbation", "4" => "En cours d'approbation", "8" => "Suspendu", "9" => "Clôtur&eacute;"), "validate"=>"1",),
		"fk_user" => array("type"=>"integer:user:user/class/user.class.php:0", "label"=>"Utilisateur", "enabled"=>"1", 'position'=>30, 'notnull'=>1, "visible"=>"1",),
		"fk_volet" => array("type"=>"integer:volet:custom/formationhabilitation/class/volet.class.php:0:(status:=:1)", "label"=>"Volet", "enabled"=>"1", 'position'=>35, 'notnull'=>1, "visible"=>"1",),
		"datedebutvolet" => array("type"=>"date", "label"=>"DateDebutVolet", "enabled"=>"1", 'position'=>50, 'notnull'=>0, "visible"=>"1",),
		"datefinvolet" => array("type"=>"date", "label"=>"DateFinVolet", "enabled"=>"1", 'position'=>51, 'notnull'=>0, "visible"=>"1",),
		"commentaire" => array("type"=>"text", "label"=>"Commentaire", "enabled"=>"1", 'position'=>60, 'notnull'=>0, "visible"=>"1",),
		"qualif_pro" => array("type"=>"sellist:c_qualification_profesionnelle:label:rowid::(active:=:1)", "label"=>"QualifPro", "enabled"=>"1", 'position'=>40, 'notnull'=>0, "visible"=>"1",),
		"cloture" => array("type"=>"boolean", "label"=>"ClotureOtherVolet", "enabled"=>"1", 'position'=>39, 'notnull'=>0, "visible"=>"1", "default"=>"1",),
		"date_valid_employeur" => array("type"=>"datetime", "label"=>"DateValidationEmployeur", "enabled"=>"1", 'position'=>550, 'notnull'=>-1, "visible"=>"-2",),
		"date_valid_intervenant" => array("type"=>"datetime", "label"=>"DateValidationIntervenant", "enabled"=>"1", 'position'=>560, 'notnull'=>-1, "visible"=>"-2",),
		"fk_user_valid_employeur" => array("type"=>"integer:user:user/class/user.class.php", "label"=>"UserValidationEmployeur", "enabled"=>"1", 'position'=>551, 'notnull'=>-1, "visible"=>"-2",),
		"fk_user_valid_intervenant" => array("type"=>"integer:user:user/class/user.class.php", "label"=>"UserValidationIntervenant", "enabled"=>"1", 'position'=>561, 'notnull'=>-1, "visible"=>"-2",),
		"fk_action_valid_employeur" => array("type"=>"integer:commaction:comm/action/class/commaction.class.php", "label"=>"ActionValidationEmployeur", "enabled"=>"1", 'position'=>552, 'notnull'=>-1, "visible"=>"-2",),
		"fk_action_valid_intervenant" => array("type"=>"integer:commaction:comm/action/class/commaction.class.php", "label"=>"ActionValidationIntervenant", "enabled"=>"1", 'position'=>562, 'notnull'=>-1, "visible"=>"-2",),
	);
	public $rowid;
	public $ref;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $status;
	public $fk_user;
	public $fk_volet;
	public $datedebutvolet;
	public $datefinvolet;
	public $commentaire;
	public $qualif_pro;
	public $cloture;
	public $date_valid_employeur;
	public $date_valid_intervenant;
	public $fk_user_valid_employeur;
	public $fk_user_valid_intervenant;
	public $fk_action_valid_employeur;
	public $fk_action_valid_intervenant;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	public $table_element_line = '';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_uservolet';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'UserVoletline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array('mychildtable' => array('name'=>'UserVolet', 'fk_element'=>'fk_uservolet'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('formationhabilitation_uservoletdet');

	// /**
	//  * @var UserVoletLine[]     Array of subtable lines
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
		/*if ($user->hasRight('formationhabilitation', 'uservolet', 'read')) {
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
	public function create(User $user, $notrigger = false, $generation_other_volet = true)
	{
		global $conf, $langs; 

		$volet = new Volet($this->db);
		$volet->fetch($this->fk_volet);

		// Crétion des volets ID, SST, Entreprise
		if($generation_other_volet) {
			$user_static = new User($this->db);
			$user_static->fetch($this->fk_user);
			$listVoletAutre = $volet->getAllVoletByType(4);
			$listUserVolet = $this->getActiveUserVolet(1, 1, 1);
			foreach($listVoletAutre as $volet_id) {
				if(!array_key_exists($volet_id, $listUserVolet)) {
					$uservolet = new UserVolet($this->db);
					$volet->fetch($volet_id);

					$uservolet->ref = $this->getUniqueRef($user_static->login."_VOLET".$volet->nommage.'_'.dol_print_date(dol_now(), '%d%m%Y'));
					$uservolet->fk_user = $this->fk_user;
					$uservolet->fk_volet = $volet_id;
			
					$resultcreate = $uservolet->create($user, $notrigger, false);

					if($resultcreate < 0) {
						return -1;
					}
				}
			}
		}

		$this->cloture = 1;

		$variableName = 'FORMTIONHABILITATION_APPROBATIONVOLET'.$this->fk_volet;
		$approbationRequire = $conf->global->$variableName;
		if(strpos($approbationRequire, '1') !== false) { // Il y a l'approbation 1
			$this->status = self::STATUS_VALIDATION0;
		}
		elseif(strpos($approbationRequire, '2') !== false) { // Il y a l'approbation 2
			$this->status = self::STATUS_VALIDATION1;
		}
		elseif(strpos($approbationRequire, '3') !== false) { // Il y a l'approbation 3
			$this->status = self::STATUS_VALIDATION2;
		}
		elseif(strpos($approbationRequire, '4') !== false) { // Il y a l'approbation 4
			$this->status = self::STATUS_VALIDATION3;
		}
		elseif(strpos($approbationRequire, '5') !== false) { // Il y a l'approbation du collaborateur
			$this->status = self::STATUS_VALIDATION_WITHOUT_USER;
		}
		else {
			$this->status = self::STATUS_VALIDATED;
		}

		$resultcreate = $this->createCommon($user, $notrigger);

		if($resultcreate > 0 && $this->status == self::STATUS_VALIDATED) {
			$this->validate($user, 0, 1, 1);
		}
		elseif($resultcreate > 0) {
			// Génération du PDF
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($this, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = !empty($this->thirdparty->default_lang) ? $this->thirdparty->default_lang : "";
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$model = '';

					$retgen = $this->generateDocument($model, $outputlangs, 0, 0, 0);
					if ($retgen < 0) {
						setEventMessages($this->error, $this->errors, 'warnings');
					}
				}
			}
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
		global $langs, $extrafields, $conf;
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
			$variableName = 'FORMTIONHABILITATION_APPROBATIONVOLET'.$this->fk_volet;
			$approbationRequire = $conf->global->$variableName;

			if(strpos($approbationRequire, '1') !== false) { // Il y a l'approbation 1
				$object->status = self::STATUS_VALIDATION0;
			}
			elseif(strpos($approbationRequire, '2') !== false) { // Il y a l'approbation 2
				$object->status = self::STATUS_VALIDATION1;
			}
			elseif(strpos($approbationRequire, '3') !== false) { // Il y a l'approbation 3
				$object->status = self::STATUS_VALIDATION2;
			}
			elseif(strpos($approbationRequire, '4') !== false) { // Il y a l'approbation 4
				$object->status = self::STATUS_VALIDATION3;
			}
			elseif(strpos($approbationRequire, '5') !== false) { // Il y a l'approbation du collaborateur
				$object->status = self::STATUS_VALIDATION_WITHOUT_USER;
			}
			else {
				$object->status = self::STATUS_VALIDATED;
			}
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

		if($result > 0) {
			$volet = new Volet($this->db);
			$volet->fetch($this->fk_volet); 

			if($volet->typevolet == 1) {
				$this->table_element_line = 'formationhabilitation_userformation';
			}
			elseif($volet->typevolet == 2) {
				$this->table_element_line = 'formationhabilitation_userhabilitation';
			}
			elseif($volet->typevolet == 3) {
				$this->table_element_line = 'formationhabilitation_userautorisation';
			}
			else {
				$this->table_element_line = '';
			}
		}

		if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
			$this->getLinkedLinesArray();
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
				if($value) {
					if ($key == 't.rowid') {
						$sqlwhere[] = $key." = ".((int) $value);
					} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
						$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
					} elseif (preg_match('/(_dtstart|_dtend)$/', $key)) {
						$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
						if (preg_match('/^(date|timestamp|datetime)/', $this->fields[$columnName]['type'])) {
							if (preg_match('/_dtstart$/', $key)) {
								$sqlwhere[] = $this->db->escape($columnName)." >= '".$this->db->idate($value)."'";
							}
							if (preg_match('/_dtend$/', $key)) {
								$sqlwhere[] = $this->db->escape($columnName)." <= '".$this->db->idate($value)."'";
							}
						}
					} elseif ($key == 'customsql') {
						$sqlwhere[] = $value;
					} elseif (strpos($value, '%') === false) {
						$sqlwhere[] = $key." IN (".$this->db->sanitize($this->db->escape($value)).")";
					} else {
						$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
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
	 *	First Validation of object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate1($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$user_group = New UserGroup($this->db);
		$user_group->fetch(0, "Direction");
		$arrayUserDirection = $user_group->listUsersForGroup('', 1);

		// Protection
		if ($this->status == self::STATUS_VALIDATION1) {
			dol_syslog(get_class($this)."::validate1 action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Evenement Agenda
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = 'AC_OTH'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_VOLET_VALIDATE';
		$actioncomm->label       =  $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Label of event
		$actioncomm->note_private = $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Description
		$actioncomm->fk_project  = '';
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->percentage  = -1; // Not applicable
		$actioncomm->socid       = '';
		$actioncomm->contact_id  = ''; // deprecated, now managed by setting $actioncomm->socpeopleassigned later
		$actioncomm->authorid    = $user->id; // User saving action
		$actioncomm->userownerid = $user->id; // Owner of action
		$actioncomm->fk_element  = $this->id;
		$actioncomm->elementtype = $this->element.($this->module ? '@'.$this->module : '');

		$ret = $actioncomm->create($user); // User creating action
		
		if($ret < 0) {
			$error++;
		}

		if(!$error) {
			// Validate
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_VALIDATION1;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			// if($this->fk_user == $user->id) {
			// 	$sql .= ", date_valid_intervenant = '".$this->db->idate($now)."'";
			// 	$sql .= ", fk_user_valid_intervenant = ".((int) $user->id);
			// 	$sql .= ", fk_action_valid_intervenant = ".((int) $ret);
			// }
			if(in_array($user->id, $arrayUserDirection) && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET1 == $user_group->id) {
				$sql .= ", date_valid_employeur = '".$this->db->idate($now)."'";
				$sql .= ", fk_user_valid_employeur = ".((int) $user->id);
				$sql .= ", fk_action_valid_employeur = ".((int) $ret);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate1()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('USERVOLET_VALIDATE1', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if(!$error) {
			// Génération du PDF
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($this, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = !empty($this->thirdparty->default_lang) ? $this->thirdparty->default_lang : "";
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$model = '';

					$retgen = $this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($retgen < 0) {
						setEventMessages($this->error, $this->errors, 'warnings');
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_VALIDATION1;
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
	 *	2nd Validation of object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate2($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$user_group = New UserGroup($this->db);
		$user_group->fetch(0, "Direction");
		$arrayUserDirection = $user_group->listUsersForGroup('', 1);

		// Protection
		if ($this->status == self::STATUS_VALIDATION2) {
			dol_syslog(get_class($this)."::validate2 action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Evenement Agenda
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = 'AC_OTH'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_VOLET_VALIDATE';
		$actioncomm->label       =  $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Label of event
		$actioncomm->note_private = $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Description
		$actioncomm->fk_project  = '';
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->percentage  = -1; // Not applicable
		$actioncomm->socid       = '';
		$actioncomm->contact_id  = ''; // deprecated, now managed by setting $actioncomm->socpeopleassigned later
		$actioncomm->authorid    = $user->id; // User saving action
		$actioncomm->userownerid = $user->id; // Owner of action
		$actioncomm->fk_element  = $this->id;
		$actioncomm->elementtype = $this->element.($this->module ? '@'.$this->module : '');

		$ret = $actioncomm->create($user); // User creating action
		
		if($ret < 0) {
			$error++;
		}

		if(!$error) {
			// Validate
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_VALIDATION2;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			if(in_array($user->id, $arrayUserDirection) && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET2 == $user_group->id) {
				$sql .= ", date_valid_employeur = '".$this->db->idate($now)."'";
				$sql .= ", fk_user_valid_employeur = ".((int) $user->id);
				$sql .= ", fk_action_valid_employeur = ".((int) $ret);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate2()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('USERVOLET_VALIDATE2', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if(!$error) {
			// Génération du PDF
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($this, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = !empty($this->thirdparty->default_lang) ? $this->thirdparty->default_lang : "";
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$model = '';

					$retgen = $this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($retgen < 0) {
						setEventMessages($this->error, $this->errors, 'warnings');
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_VALIDATION2;
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
	 *	3rd Validation of object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate3($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$user_group = New UserGroup($this->db);
		$user_group->fetch(0, "Direction");
		$arrayUserDirection = $user_group->listUsersForGroup('', 1);

		// Protection
		if ($this->status == self::STATUS_VALIDATION3) {
			dol_syslog(get_class($this)."::validate3 action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Evenement Agenda
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = 'AC_OTH'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_VOLET_VALIDATE';
		$actioncomm->label       =  $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Label of event
		$actioncomm->note_private = $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Description
		$actioncomm->fk_project  = '';
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->percentage  = -1; // Not applicable
		$actioncomm->socid       = '';
		$actioncomm->contact_id  = ''; // deprecated, now managed by setting $actioncomm->socpeopleassigned later
		$actioncomm->authorid    = $user->id; // User saving action
		$actioncomm->userownerid = $user->id; // Owner of action
		$actioncomm->fk_element  = $this->id;
		$actioncomm->elementtype = $this->element.($this->module ? '@'.$this->module : '');

		$ret = $actioncomm->create($user); // User creating action
		
		if($ret < 0) {
			$error++;
		}

		if(!$error) {
			// Validate
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_VALIDATION3;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			// if($this->fk_user == $user->id) {
			// 	$sql .= ", date_valid_intervenant = '".$this->db->idate($now)."'";
			// 	$sql .= ", fk_user_valid_intervenant = ".((int) $user->id);
			// 	$sql .= ", fk_action_valid_intervenant = ".((int) $ret);
			// }
			if(in_array($user->id, $arrayUserDirection) && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET3 == $user_group->id) {
				$sql .= ", date_valid_employeur = '".$this->db->idate($now)."'";
				$sql .= ", fk_user_valid_employeur = ".((int) $user->id);
				$sql .= ", fk_action_valid_employeur = ".((int) $ret);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate3()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('USERVOLET_VALIDATE3', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if(!$error) {
			// Génération du PDF
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($this, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = !empty($this->thirdparty->default_lang) ? $this->thirdparty->default_lang : "";
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$model = '';

					$retgen = $this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($retgen < 0) {
						setEventMessages($this->error, $this->errors, 'warnings');
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_VALIDATION3;
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
	 *	Validation without user of object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate_without_user($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$user_group = New UserGroup($this->db);
		$user_group->fetch(0, "Direction");
		$arrayUserDirection = $user_group->listUsersForGroup('', 1);

		// Protection
		if ($this->status == self::STATUS_VALIDATION_WITHOUT_USER) {
			dol_syslog(get_class($this)."::validate_without_user action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Evenement Agenda
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = 'AC_OTH'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_VOLET_VALIDATE';
		$actioncomm->label       =  $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Label of event
		$actioncomm->note_private = $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Description
		$actioncomm->fk_project  = '';
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->percentage  = -1; // Not applicable
		$actioncomm->socid       = '';
		$actioncomm->contact_id  = ''; // deprecated, now managed by setting $actioncomm->socpeopleassigned later
		$actioncomm->authorid    = $user->id; // User saving action
		$actioncomm->userownerid = $user->id; // Owner of action
		$actioncomm->fk_element  = $this->id;
		$actioncomm->elementtype = $this->element.($this->module ? '@'.$this->module : '');

		$ret = $actioncomm->create($user); // User creating action
		
		if($ret < 0) {
			$error++;
		}

		// Validate
		if(!$error) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET status = ".self::STATUS_VALIDATION_WITHOUT_USER;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			// if($this->fk_user == $user->id) {
			// 	$sql .= ", date_valid_intervenant = '".$this->db->idate($now)."'";
			// 	$sql .= ", fk_user_valid_intervenant = ".((int) $user->id);
			// 	$sql .= ", fk_action_valid_intervenant = ".((int) $ret);
			// }
			if(in_array($user->id, $arrayUserDirection) && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET4 == $user_group->id) {
				$sql .= ", date_valid_employeur = '".$this->db->idate($now)."'";
				$sql .= ", fk_user_valid_employeur = ".((int) $user->id);
				$sql .= ", fk_action_valid_employeur = ".((int) $ret);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate_without_user()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
		

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('USERVOLET_VALIDATE_WITHOUT_USER', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if(!$error) {
			// Génération du PDF
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($this, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = !empty($this->thirdparty->default_lang) ? $this->thirdparty->default_lang : "";
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$model = '';

					$retgen = $this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($retgen < 0) {
						setEventMessages($this->error, $this->errors, 'warnings');
					}
				}
			}
		}
		
		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_VALIDATION_WITHOUT_USER;
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
	 *	Last Validation of object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0, $forcecreation = 0, $nosigning = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$user_group = New UserGroup($this->db);
		$user_group->fetch(0, "Direction");
		$arrayUserDirection = $user_group->listUsersForGroup('', 1);
		$variableName = 'FORMTIONHABILITATION_APPROBATIONVOLET'.$this->fk_volet;
		$approbationRequire = $conf->global->$variableName;
		$approbationRequireArray = explode(',', $conf->global->$variableName);

		// Protection
		if ($this->status == self::STATUS_VALIDATED && !$forcecreation) {
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		if($this->cloture) {
			$result = $this->closeActiveUserVolet();

			if($result < 0) {
				$error++;
			}
		}

		$now = dol_now();

		$this->db->begin();

		// Evenement Agenda
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = 'AC_OTH'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_VOLET_VALIDATE';
		$actioncomm->label       =  $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Label of event
		$actioncomm->note_private = $langs->transnoentities("FORMATIONHABILITATION_VALIDATE_WITHOUT_USERInDolibarr", $this->ref);		// Description
		$actioncomm->fk_project  = '';
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->percentage  = -1; // Not applicable
		$actioncomm->socid       = '';
		$actioncomm->contact_id  = ''; // deprecated, now managed by setting $actioncomm->socpeopleassigned later
		$actioncomm->authorid    = $user->id; // User saving action
		$actioncomm->userownerid = $user->id; // Owner of action
		$actioncomm->fk_element  = $this->id;
		$actioncomm->elementtype = $this->element.($this->module ? '@'.$this->module : '');

		$ret = $actioncomm->create($user); // User creating action
		
		if($ret < 0) {
			$error++;
		}

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if (!empty($num) && !$error) {
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
			if(!$nosigning) {
				if($this->fk_user == $user->id && strpos($approbationRequire, '5') !== false) {
					$sql .= ", date_valid_intervenant = '".$this->db->idate($now)."'";
					$sql .= ", fk_user_valid_intervenant = ".((int) $user->id);
					$sql .= ", fk_action_valid_intervenant = ".((int) $ret);
				}
				elseif(in_array($user->id, $arrayUserDirection)) {
					$sql .= ", date_valid_employeur = '".$this->db->idate($now)."'";
					$sql .= ", fk_user_valid_employeur = ".((int) $user->id);
					$sql .= ", fk_action_valid_employeur = ".((int) $ret);
				}
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
				$result = $this->call_trigger('USERVOLET_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'uservolet/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'uservolet/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'uservolet/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'uservolet/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->formationhabilitation->dir_output.'/uservolet/'.$oldref;
				$dirdest = $conf->formationhabilitation->dir_output.'/uservolet/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->formationhabilitation->dir_output.'/uservolet/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		if(!$error) {
			// Génération du PDF
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($this, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = !empty($this->thirdparty->default_lang) ? $this->thirdparty->default_lang : "";
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$model = '';

					$retgen = $this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($retgen < 0) {
						setEventMessages($this->error, $this->errors, 'warnings');
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error && empty($this->datedebutvolet)) {
			$this->datedebutvolet = dol_now();
			$resultupdate = $this->update($user);

			if($resultupdate < 0) {
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
	 *	Expire object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function expire($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_EXPIRE) {
			dol_syslog(get_class($this)."::expire action abandonned: already expired", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_EXPIRE;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::expire()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('USERVOLET_EXPIRE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_EXPIRE;
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
	 *	Suspend object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function suspend($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_SUSPEND) {
			dol_syslog(get_class($this)."::suspend action abandonned: already suspend", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_SUSPEND;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::suspend()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('USERVOLET_SUSPEND', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_SUSPEND;
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
	 *	Unsuspend object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function unsuspend($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_SUSPEND) {
			dol_syslog(get_class($this)."::unsuspend action abandonned: not suspended", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_VALIDATION_WITHOUT_USER;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::unsuspend()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('USERVOLET_UNSUSPEND', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_VALIDATION_WITHOUT_USER;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	// /**
	//  *	Set draft status
	//  *
	//  *	@param	User	$user			Object user that modify
	//  *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	//  *	@return	int						Return integer <0 if KO, >0 if OK
	//  */
	// public function setDraft($user, $notrigger = 0)
	// {
	// 	// Protection
	// 	if ($this->status <= self::STATUS_VALIDATION0) {
	// 		return 0;
	// 	}

	// 	/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','write'))
	// 	 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','formationhabilitation_advance','validate'))))
	// 	 {
	// 	 $this->error='Permission denied';
	// 	 return -1;
	// 	 }*/

	// 	return $this->setStatusCommon($user, self::STATUS_VALIDATION0, $notrigger, 'FORMATIONHABILITATION_USERVOLET_UNVALIDATE');
	// }

	/**
	 *	Set close status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function close($user, $notrigger = 0)
	{
		// Protection
		// if ($this->status != self::STATUS_VALIDATED) {
		// 	return 0;
		// }

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('formationhabilitation','formationhabilitation_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CLOSE, $notrigger, 'FORMATIONHABILITATION_USERVOLET_CANCEL');
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

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'FORMATIONHABILITATION_USERVOLET_REOPEN');
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
			return ['optimize' => $langs->trans("ShowUserVolet")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("UserVolet").'</u>';
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

		$url = dol_buildpath('/formationhabilitation/uservolet_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowUserVolet");
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
		global $conf; 

		// phpcs:enable
		if (is_null($status)) {
			return '';
		}

		if($mode == 6) {
			$mode = 5;
		}

		$usergroup = new UserGroup($this->db);
		if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET1 > 0 && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET1 != 9999) {
			$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET1);
			$nameGroup1 = $usergroup->name;
		}
		if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET2 > 0 && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET2 != 9999) {
			$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET2);
			$nameGroup2 = $usergroup->name;
		}
		if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET3 > 0 && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET3 != 9999) {
			$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET3);
			$nameGroup3 = $usergroup->name;
		}
		if($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET4 > 0 && $conf->global->FORMTIONHABILITATION_APPROBATEURVOLET4 != 9999) {
			$usergroup->fetch($conf->global->FORMTIONHABILITATION_APPROBATEURVOLET4);
			$nameGroup4 = $usergroup->name;
		}

		$variableName = 'FORMTIONHABILITATION_APPROBATIONVOLET'.$this->fk_volet;
		$approbationRequire = explode(',', $conf->global->$variableName);
		asort($approbationRequire);

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("formationhabilitation@formationhabilitation");

			// $variableName = 'nameGroup'.($approbationRequire[0]+1);
			// if(!empty($$variableName)) {
			// 	$this->labelStatus[self::STATUS_VALIDATION0] = "Approbation du groupe ".$$variableName." en attente";
			// }
			// else {
			// 	$this->labelStatus[self::STATUS_VALIDATION0] = $langs->transnoentitiesnoconv('ApprobationVoletCollaborateur');
			// }

			// if(!empty($nameGroup2)) {
			// 	$this->labelStatus[self::STATUS_VALIDATION1] = "Approbation du groupe $nameGroup2 en attente";
			// }
			// else {
			// 	$this->labelStatus[self::STATUS_VALIDATION1] = $langs->transnoentitiesnoconv('ApprobationVoletCollaborateur');
			// }
		
			// if(!empty($nameGroup3)) {
			// 	$this->labelStatus[self::STATUS_VALIDATION2] = "Approbation du groupe $nameGroup3 en attente";
			// }
			// else {
			// 	$this->labelStatus[self::STATUS_VALIDATION2] = $langs->transnoentitiesnoconv('ApprobationVoletCollaborateur');
			// }

			// if(!empty($nameGroup4)) {
			// 	$this->labelStatus[self::STATUS_VALIDATION3] = "Approbation du groupe $nameGroup4 en attente";
			// }
			// else {
			// 	$this->labelStatus[self::STATUS_VALIDATION3] = $langs->transnoentitiesnoconv('ApprobationVoletCollaborateur');
			// }

			$this->labelStatus[self::STATUS_VALIDATION0] = "Approbation du groupe $nameGroup1 en attente";
			$this->labelStatus[self::STATUS_VALIDATION1] = "Approbation du groupe $nameGroup2 en attente";
			$this->labelStatus[self::STATUS_VALIDATION2] = "Approbation du groupe $nameGroup3 en attente";
			$this->labelStatus[self::STATUS_VALIDATION3] = "Approbation du groupe $nameGroup4 en attente";
			$this->labelStatus[self::STATUS_VALIDATION_WITHOUT_USER] = "Approbation du collaborateur en attente";
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_CLOSE] = $langs->transnoentitiesnoconv('Close');
			$this->labelStatus[self::STATUS_SUSPEND] = $langs->transnoentitiesnoconv('SuspendLong');
			$this->labelStatus[self::STATUS_EXPIRE] = $langs->transnoentitiesnoconv('Expiré');

			$this->labelStatusShort[self::STATUS_VALIDATION0] = $langs->transnoentitiesnoconv('ApprobationVolet');
			$this->labelStatusShort[self::STATUS_VALIDATION1] = $langs->transnoentitiesnoconv('ApprobationVolet');
			$this->labelStatusShort[self::STATUS_VALIDATION2] = $langs->transnoentitiesnoconv('ApprobationVolet');
			$this->labelStatusShort[self::STATUS_VALIDATION3] = $langs->transnoentitiesnoconv('ApprobationVolet');
			$this->labelStatusShort[self::STATUS_VALIDATION_WITHOUT_USER] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_CLOSE] = $langs->transnoentitiesnoconv('Close');
			$this->labelStatusShort[self::STATUS_SUSPEND] = $langs->transnoentitiesnoconv('Suspend');
			$this->labelStatusShort[self::STATUS_EXPIRE] = $langs->transnoentitiesnoconv('Expiré');
		}

		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}
		elseif ($status == self::STATUS_VALIDATION_WITHOUT_USER) {
			$statusType = 'status2';
		}
		elseif ($status == self::STATUS_CLOSE) {
			$statusType = 'status6';
		}
		elseif ($status == self::STATUS_SUSPEND) {
			$statusType = 'status10';
		}
		elseif ($status == self::STATUS_EXPIRE) {
			$statusType = 'status8';
		}
		else {
			$statusType = 'status1';
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
	public function getLinkedLinesArray()
	{
		$this->lines = array();

		$volet = new Volet($this->db);
		$volet->fetch($this->fk_volet); 

		if($volet->typevolet == 1) {
			$objectline = new UserFormation($this->db);
		}
		elseif($volet->typevolet == 2) {
			$objectline = new UserHabilitation($this->db);
		}
		elseif($volet->typevolet == 3) {
			$objectline = new UserAutorisation($this->db);
		}

		$result = $objectline->fetchAllLinked('ASC', 'h.label', 0, 0, array('customsql'=>'fk_user = '.((int) $this->fk_user).' AND e.fk_target IS NOT NULL'), 'AND', $this->id, $this->fk_volet);

		if (is_numeric($result)) {
			$this->setErrorsFromObject($objectline);
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getNoLinkedLinesArray()
	{
		$this->lines = array();

		$volet = new Volet($this->db);
		$volet->fetch($this->fk_volet); 

		if($volet->typevolet == 1) {
			$objectline = new UserFormation($this->db);
			$status = UserFormation::STATUS_VALIDE.', '.UserFormation::STATUS_REPROGRAMMEE.', '.UserFormation::STATUS_A_PROGRAMMER;
		}
		elseif($volet->typevolet == 2) {
			$objectline = new UserHabilitation($this->db);
			$status = UserHabilitation::STATUS_HABILITE.', '.UserHabilitation::STATUS_HABILITABLE;
		}
		elseif($volet->typevolet == 3) {
			$objectline = new UserAutorisation($this->db);
			$status = UserAutorisation::STATUS_AUTORISE.', '.UserAutorisation::STATUS_AUTORISABLE;
		}

		if($volet->typevolet == 1) {
			$result = $objectline->fetchAllLinked('ASC', 'h.label', 0, 0, array('customsql'=>'fk_user = '.((int) $this->fk_user).' AND e.fk_target IS NULL AND t.resultat != 3 AND t.status IN ('.$this->db->sanitize($this->db->escape($status)).')'), 'AND', $this->id, $this->fk_volet);
		}
		else {
			$result = $objectline->fetchAllLinked('ASC', 'h.label', 0, 0, array('customsql'=>'fk_user = '.((int) $this->fk_user).' AND e.fk_target IS NULL AND t.status IN ('.$this->db->sanitize($this->db->escape($status)).')'), 'AND', $this->id, $this->fk_volet);
		}

		if (is_numeric($result)) {
			$this->setErrorsFromObject($objectline);
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new UserVoletLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_uservolet = '.((int) $this->id)));

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
		$langs->load("formationhabilitation@formationhabilitation");

		if (!getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON')) {
			$conf->global->FORMATIONHABILITATION_MYOBJECT_ADDON = 'mod_uservolet_standard';
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
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$result = 0;
		$includedocgeneration = 1;

		$langs->load("formationhabilitation@formationhabilitation");

		if (!dol_strlen($modele)) {
			$modele = 'standard_uservolet';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('MYOBJECT_ADDON_PDF')) {
				$modele = getDolGlobalString('MYOBJECT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/formationhabilitation/doc/";

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

	public function getArrayStatut() {
		global $langs; 

		//$labelStatus[self::STATUS_VALIDATION0] = $langs->transnoentitiesnoconv('Draft');
		$labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
		// $labelStatus[self::STATUS_VALIDATION1] = $langs->transnoentitiesnoconv('Validation1');
		// $labelStatus[self::STATUS_VALIDATION2] = $langs->transnoentitiesnoconv('Validation2');
		// $labelStatus[self::STATUS_VALIDATION3] = $langs->transnoentitiesnoconv('Validation3');
		// $labelStatus[self::STATUS_VALIDATION4] = $langs->transnoentitiesnoconv('Validation4');
		$labelStatus[50] = $langs->transnoentitiesnoconv('ApprobationVolet');
		$labelStatus[self::STATUS_CLOSE] = $langs->transnoentitiesnoconv('Close');
		$labelStatus[self::STATUS_SUSPEND] = $langs->transnoentitiesnoconv('Suspend');
		$labelStatus[self::STATUS_EXPIRE] = $langs->transnoentitiesnoconv('Expiré');

		return $labelStatus;
	}

	/**
	 *	Return HTML table for object lines
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *	If lines are into a template, title must also be into a template
	 *	But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
	 *
	 *	@param	string		$action				Action code
	 *	@param  Societe		$seller            	Object of seller third party
	 *	@param  Societe  	$buyer             	Object of buyer third party
	 *	@param	int			$selected		   	ID line selected
	 *	@param  int	    	$dateSelector      	1=Show also date range input fields
	 *  @param	string		$defaulttpldir		Directory where to find the template
	 *	@return	void
	 */
	public function printObjectLinkedLines($action, $seller, $buyer, $selected = 0, $dateSelector = 0, $defaulttpldir = '/core/tpl')
	{
		global $conf, $hookmanager, $langs, $user, $form, $extrafields, $object;
		// TODO We should not use global var for this
		global $inputalsopricewithtax, $usemargins, $disableedit, $disablemove, $disableremove, $outputalsopricetotalwithtax;

		// Define usemargins
		$usemargins = 0;
		if (isModEnabled('margin') && !empty($this->element) && in_array($this->element, array('facture', 'facturerec', 'propal', 'commande'))) {
			$usemargins = 1;
		}

		$num = count($this->lines);

		// Line extrafield
		if (!is_object($extrafields)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
		}
		$extrafields->fetch_name_optionals_label($this->table_element_line);

		$parameters = array('num'=>$num, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer, 'selected'=>$selected, 'table_element_line'=>$this->table_element_line);
		$reshook = $hookmanager->executeHooks('printObjectLineTitle', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if (empty($reshook)) {
			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				$res = 0;
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir.'/objectline_title.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_title.tpl.php';
				}
				if (file_exists($tpl)) {
					if (empty($conf->file->strict_mode)) {
						$res = @include $tpl;
					} else {
						$res = include $tpl; // for debug
					}
				}
				if ($res) {
					break;
				}
			}
		}

		$i = 0;

		print "<!-- begin printObjectLines() --><tbody>\n";
		foreach ($this->lines as $line) {
			//Line extrafield
			$line->fetch_optionals();

			//if (is_object($hookmanager) && (($line->product_type == 9 && !empty($line->special_code)) || !empty($line->fk_parent_line)))
			if (is_object($hookmanager)) {   // Old code is commented on preceding line.
				if (empty($line->fk_parent_line)) {
					$parameters = array('line'=>$line, 'num'=>$num, 'i'=>$i, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer, 'selected'=>$selected, 'table_element_line'=>$line->table_element, 'defaulttpldir'=>$defaulttpldir);
					$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				} else {
					$parameters = array('line'=>$line, 'num'=>$num, 'i'=>$i, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer, 'selected'=>$selected, 'table_element_line'=>$line->table_element, 'fk_parent_line'=>$line->fk_parent_line, 'defaulttpldir'=>$defaulttpldir);
					$reshook = $hookmanager->executeHooks('printObjectSubLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				}
			}
			if (empty($reshook)) {
				$this->printObjectLinkedLine($action, $line, '', $num, $i, $dateSelector, $seller, $buyer, $selected, $extrafields, $defaulttpldir);
			}

			$i++;
		}
		print "</tbody><!-- end printObjectLines() -->\n";
	}

	/**
	 *	Return HTML content of a detail line
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *
	 *	@param	string      		$action				GET/POST action
	 *	@param  CommonObjectLine 	$line			    Selected object line to output
	 *	@param  string	    		$var               	Not used
	 *	@param  int		    		$num               	Number of line (0)
	 *	@param  int		    		$i					I
	 *	@param  int		    		$dateSelector      	1=Show also date range input fields
	 *	@param  Societe	    		$seller            	Object of seller third party
	 *	@param  Societe	    		$buyer             	Object of buyer third party
	 *	@param	int					$selected		   	ID line selected
	 *  @param  Extrafields			$extrafields		Object of extrafields
	 *  @param	string				$defaulttpldir		Directory where to find the template (deprecated)
	 *	@return	void
	 */
	public function printObjectLinkedLine($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected = 0, $extrafields = null, $defaulttpldir = '/core/tpl')
	{
		global $conf, $langs, $user, $object, $hookmanager;
		global $form;
		global $object_rights, $disableedit, $disablemove, $disableremove; // TODO We should not use global var for this !

		$object_rights = $this->getRights();

		// var used into tpl
		$text = '';
		$description = '';

		// Line in view mode
		if ($action != 'editline' || $selected != $line->id) {
			// Product
			if (!empty($line->fk_product) && $line->fk_product > 0) {
				$product_static = new Product($this->db);
				$product_static->fetch($line->fk_product);

				$product_static->ref = $line->ref; //can change ref in hook
				$product_static->label = !empty($line->label) ? $line->label : ""; //can change label in hook

				$text = $product_static->getNomUrl(1);

				// Define output language and label
				if (getDolGlobalInt('MAIN_MULTILANGS')) {
					if (property_exists($this, 'socid') && !is_object($this->thirdparty)) {
						dol_print_error('', 'Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
						return;
					}

					$prod = new Product($this->db);
					$prod->fetch($line->fk_product);

					$outputlangs = $langs;
					$newlang = '';
					if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE') && empty($newlang) && is_object($this->thirdparty)) {
						$newlang = $this->thirdparty->default_lang; // To use language of customer
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $line->product_label;
				} else {
					$label = $line->product_label;
				}

				$text .= ' - '.(!empty($line->label) ? $line->label : $label);
				$description .= (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : (!empty($line->description) ? dol_htmlentitiesbr($line->description) : '')); // Description is what to show on popup. We shown nothing if already into desc.
			}

			$line->pu_ttc = price2num((!empty($line->subprice) ? $line->subprice : 0) * (1 + ((!empty($line->tva_tx) ? $line->tva_tx : 0) / 100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				$res = 0;
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir.'/objectline_view.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_view.tpl.php';
				}
				//var_dump($tpl);
				if (file_exists($tpl)) {
					if (empty($conf->file->strict_mode)) {
						$res = @include $tpl;
					} else {
						$res = include $tpl; // for debug
					}
				}
				if ($res) {
					break;
				}
			}
		}

		// Line in update mode
		if ($this->statut == 0 && $action == 'editline' && $selected == $line->id) {
			$label = (!empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));

			$line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir.'/objectline_edit.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_edit.tpl.php';
				}

				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res) {
					break;
				}
			}
		}
	}

	/**
	 *	Delete all links between an object $this
	 *
	 *	@param	int		$sourceid		Object source id
	 *	@param  string	$sourcetype		Object source type
	 *	@param  int		$targetid		Object target id
	 *	@param  string	$targettype		Object target type
	 *  @param	int		$rowid			Row id of line to delete. If defined, other parameters are not used.
	 * 	@param	User	$f_user			User that create
	 * 	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return     					int	>0 if OK, <0 if KO
	 *	@see	add_object_linked(), updateObjectLinked(), fetchObjectLinked()
	 */
	public function deleteObjectLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $rowid = 0, $f_user = null, $notrigger = 0)
	{
		global $user;
		$deletesource = false;
		$deletetarget = false;
		$deletetargetandsource = false;
		$f_user = isset($f_user) ? $f_user : $user;

		if (!empty($sourceid) && !empty($sourcetype) && empty($targetid) && empty($targettype)) {
			$deletesource = true;
		} elseif (empty($sourceid) && empty($sourcetype) && !empty($targetid) && !empty($targettype)) {
			$deletetarget = true;
		} elseif (!empty($sourceid) && !empty($sourcetype) && !empty($targetid) && !empty($targettype)) {
			$deletetargetandsource = true;
		}

		$sourceid = (!empty($sourceid) ? $sourceid : $this->id);
		$sourcetype = (!empty($sourcetype) ? $sourcetype : $this->element);
		$targetid = (!empty($targetid) ? $targetid : $this->id);
		$targettype = (!empty($targettype) ? $targettype : $this->element);
		$this->db->begin();
		$error = 0;

		if (!$notrigger) {
			// Call trigger
			$this->context['link_id'] = $rowid;
			$this->context['link_source_id'] = $sourceid;
			$this->context['link_source_type'] = $sourcetype;
			$this->context['link_target_id'] = $targetid;
			$this->context['link_target_type'] = $targettype;
			$result = $this->call_trigger('OBJECT_LINK_DELETE', $f_user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = "DELETE FROM " . $this->db->prefix() . "element_element";
			$sql .= " WHERE";
			if ($rowid > 0) {
				$sql .= " rowid = " . ((int) $rowid);
			} else {
				if ($deletesource) {
					$sql .= " fk_source = " . ((int) $sourceid) . " AND sourcetype = '" . $this->db->escape($sourcetype) . "'";
					$sql .= " AND fk_target = " . ((int) $this->id) . " AND targettype = '" . $this->db->escape($this->element) . "'";
				} elseif ($deletetarget) {
					$sql .= " fk_target = " . ((int) $targetid) . " AND targettype = '" . $this->db->escape($targettype) . "'";
					$sql .= " AND fk_source = " . ((int) $this->id) . " AND sourcetype = '" . $this->db->escape($this->element) . "'";
				} elseif ($deletetargetandsource) {
					$sql .= " (fk_source = " . ((int) $sourceid) . " AND sourcetype = '" . $this->db->escape($sourcetype) . "')";
					$sql .= " AND";
					$sql .= " (fk_target = " . ((int) $targetid) . " AND targettype = '" . $this->db->escape($targettype) . "')";
				} else {
					$sql .= " (fk_source = " . ((int) $this->id) . " AND sourcetype = '" . $this->db->escape($this->element) . "')";
					$sql .= " OR";
					$sql .= " (fk_target = " . ((int) $this->id) . " AND targettype = '" . $this->db->escape($this->element) . "')";
				}
			}

			dol_syslog(get_class($this) . "::deleteObjectLinked", LOG_DEBUG);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$error++;
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return 0;
		}
	}

	
	/**
	 * 	Génère les volets d'habilitation ou d'autorisation avec les lignes sélectionnées
	 *  @param	string	$objectclass			Object class name
	 * 	@param	string	$objectparentclass		Object parent class name
	 * 	@param	array	$validateObjects		List of object validated with massaction
	 *  @param  int		$userid					Id of user
	 * 	@return	int								return 1 if OK, -1 if KO
	 */
	public function generateNewVoletHabilitationAutorisation($objectclass, $objectparentclass, $validateObjects, $userid)
	{
		global $conf, $user;
		$error = 0;

		$objecttmp = new $objectclass($this->db);
		$objectparenttmp = new $objectparentclass($this->db);

		$uservolet = new self($this->db);
		$user_static = new User($this->db);
		$user_static->fetch($userid);

		$voletsCreate = array(); 

		dol_syslog(get_class($this)."::generateNewVoletHabilitationAutorisation", LOG_DEBUG);
		foreach($validateObjects as $validateObject) { // On boucle sur toutes les lignes ajouté
			if($objecttmp->element == 'userhabilitation') {
				$objectparenttmp->fetch($validateObject->fk_habilitation);
			}
			elseif($objecttmp->element == 'userautorisation') {
				$objectparenttmp->fetch($validateObject->fk_autorisation);
			}

			$voletsForObject = explode(',', $objectparenttmp->fk_volet);
			foreach($voletsForObject as $voletid) {
				if($voletid > 0) {
					$volet = new Volet($this->db);
					$volet->fetch($voletid); 

					// Création du nouveau volet
					if(!array_key_exists($voletid, $voletsCreate)) {
						$uservolet->ref = $this->getUniqueRef($user_static->login."_VOLET".$volet->nommage.'_'.dol_print_date(dol_now(), '%d%m%Y'));
						$uservolet->fk_user = $userid;
						$uservolet->fk_volet = $voletid;
						$uservolet->commentaire = GETPOST('commentaire', 'alpha');
						if($voletid == 6) {
							$uservolet->qualif_pro = GETPOST('qualif_pro', 'int');
						}
						else {
							$uservolet->qualif_pro = '';
						}

						$resultcreate = $uservolet->create($user);

						if($resultcreate < 0) {
							$error++;
							$this->error = "Impossible de créer le volet ".$volet->label;
							break;
						}

						$voletsCreate[$voletid] = clone $uservolet;
					}
				
					if($objecttmp->element == 'userhabilitation') {
						$addlink = 'habilitation';
					}
					elseif($objecttmp->element == 'userautorisation') {
						$addlink = 'autorisation';
					}

					$uservolet = $voletsCreate[$voletid];
					$resultLink = $uservolet->add_object_linked($addlink, $validateObject->id);

					if($resultLink < 0) {
						$error++;
						$this->error = "Impossible de lier la ligne ".$validateObject->ref." sur le volet ".$volet->label;
						break;
					}
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * 	Génère les volets de formation avec l'ensemble des formations actives
	 *  @param  int					$userid					Id of user
	 *  @param  int					$fk_volet				Id of Volet
	 *  @param  array				$voletsCreate			If of UserVolet also create
	 * 	@param  UserFormation		$userFormation			Object of userFormation validate or close
	 * 	@return	int								return 1 if OK, -1 if KO				
	 */
	public function generateNewVoletFormation($userid, $fk_volet, &$voletsCreate = array())
	{
		global $conf, $user;
		$error = 0;

		$objecttmp = new UserFormation($this->db);

		$uservolet = new self($this->db);
		$uservolet2 = new self($this->db);
		$user_static = new User($this->db);
		$user_static->fetch($userid);

		dol_syslog(get_class($this)."::generateNewVoletFormation", LOG_DEBUG);
		$voletsForObject = explode(',', $fk_volet);
		foreach($voletsForObject as $voletid) {
			if($voletid > 0) {
				$volet = new Volet($this->db);
				$volet->fetch($voletid); 

				$listUserFormations = $objecttmp->getAllFormationsForUserOnVolet($userid, $voletid);

				// Création du nouveau volet
				if(!in_array($voletid, $voletsCreate) && sizeof($listUserFormations) > 0) {
					foreach($listUserFormations as $userformation_id => $userformation_res) {
						if(!empty($userformation_res['date_fin_formation']) && (empty($uservolet->datedebutvolet) || $uservolet->datedebutvolet < $userformation_res['date_fin_formation'])) {
							$uservolet->datedebutvolet = $userformation_res['date_fin_formation'];
						}
						if(!empty($userformation_res['date_finvalidite_formation']) && (empty($uservolet->datefinvolet) || $uservolet->datefinvolet > $userformation_res['date_finvalidite_formation'])) {
							$uservolet->datefinvolet = $userformation_res['date_finvalidite_formation'];
						}
					}

					$uservolet->ref = $this->getUniqueRef($user_static->login."_VOLET".$volet->nommage.'_'.dol_print_date(dol_now(), '%d%m%Y'));
					$uservolet->fk_user = $userid;
					$uservolet->fk_volet = $voletid;

					$resultcreate = $uservolet->create($user);

					if($resultcreate < 0) {
						$error++;
						$this->error = "Impossible de créer le volet ".$volet->label;
						break;
					}

					$voletsCreate[] = $voletid;
					
					
					foreach($listUserFormations as $userformation_id => $userformation_res) {
						$resultLink = $uservolet->add_object_linked('formation', $userformation_id);
						if($resultLink < 0) {
							$error++;
							$this->error = "Impossible de lier la ligne ".$userformation_res['ref']." sur le volet ".$volet->label;
							break;
						}
					}
				}				
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * 	Génère une ref unique pour le volet
	 *  @param  int			$ref					Ref actuelle
	 *  @param  User		$user_static			Object of User
	 * 	@param  Volet		$volet					Object of Volet
	 * 	@return	int							return new Ref			
	 */
	public function getUniqueRef($ref) {
		$userVolet = new self($this->db);
		$result = $ref;
		$i = 1;

		while($userVolet->fetch(0, $result) > 0) {
			$result = $ref.'_'.$i;
			$i++;
		}

		return $result;
	}

	/**
	 * 	Ajoute un domaine d'application spécifique pour l'habilitation liée au uservolet
	 *
	 * 	@param  int		$lineid       				Id of Habilitation
	 *  @param  int		$domaineapplication     	Dommaine d'application
	 *  @return	int		> 0 if OK, < 0 if KO
	 */
	public function updateDomaineApplication($lineid, $domaineapplication, $sourcetype)
	{
		global $conf, $user;

		$sql = "SELECT f.fk_element_element";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_element_fields as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as e ON e.rowid = f.fk_element_element AND e.sourcetype = '$sourcetype' AND e.fk_source = $lineid";
		$sql .= " AND e.targettype = 'formationhabilitation_uservolet' AND e.fk_target = $this->id";
		$sql .= " WHERE e.rowid > 0";

		dol_syslog(get_class($this)."::updateDomaineApplication", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_object($resql);

				$sql = "UPDATE ".MAIN_DB_PREFIX."formationhabilitation_element_fields";
				$sql .= " SET domaineapplication = $domaineapplication";
				$sql .= " WHERE fk_element_element = $obj->fk_element_element";

				$resql = $this->db->query($sql);

				if ($resql) {
					return 1;
				}
				else {
					$this->error = $this->db->lasterror();
					return -1;
				}
			}
			else {
				$sql = "SELECT e.rowid";
				$sql .= " FROM ".MAIN_DB_PREFIX."element_element as e ";
				$sql .= " WHERE e.sourcetype = '$sourcetype' AND e.fk_source = $lineid";
				$sql .= " AND e.targettype = 'formationhabilitation_uservolet' AND e.fk_target = $this->id";

				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."formationhabilitation_element_fields(fk_element_element, domaineapplication) VALUES";
					$sql .= " ($obj->rowid, $domaineapplication)";

					$resql = $this->db->query($sql);

					if ($resql) {
						return 1;
					}
					else {
						$this->error = $this->db->lasterror();
						return -1;
					}
				}
				else {
					$this->error = $this->db->lasterror();
					return -1;
				}
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Supprime un domaine d'application spécifique pour l'habilitation liée au uservolet
	 *
	 * 	@param  int		$lineid       	Id of Habilitation
	 *  @return	int		> 0 if OK, < 0 if KO
	 */
	public function deleteDomaineApplication($lineid)
	{
		global $conf, $user;

		$sql = "SELECT e.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_element as e";
		$sql .= " WHERE e.sourcetype = 'habilitation' AND e.fk_source = $lineid";
		$sql .= " AND e.targettype = 'formationhabilitation_uservolet' AND e.fk_target = $this->id";

		dol_syslog(get_class($this)."::deleteDomaineApplication", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_object($resql);

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."formationhabilitation_element_fields";
				$sql .= " WHERE fk_element_element = $obj->rowid";

				$resql = $this->db->query($sql);
			}

			if ($resql) {
				return 1;
			}
			else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Récupère les uservolet actifs
	 *
	 *  @param  int		$mode			0 uniquement l'id, 1 les objets uservolet
	 *  @param  int		$all			0 uniquement le volet actuel, 1 tous les volets
	 *  @param  int		$get_fk_volet	Récupère fk_volet plutot que le rowid
	 * 	@param  string	$fk_volet		filtre pour le numéro des volets à récupérer
	 *  @return	array|int		array with uservolet if OK, < 0 if KO
	 */
	public function getActiveUserVolet($mode = 0, $all = 0, $get_fk_volet = 0, $fk_volet = '')
	{
		global $conf, $user;
		$ret = array(); 

		$sql = "SELECT v.rowid, v.fk_volet";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_uservolet as v";
		$sql .= " WHERE v.fk_user = $this->fk_user";
		if(!$all && empty($fk_volet)) {
			$sql .= " AND v.fk_volet = $this->fk_volet";
		}
		elseif(!$all && !empty($fk_volet)) {
			$sql .= " AND v.fk_volet IN (".$this->db->sanitize($this->db->escape($fk_volet)).")";
		}
		$sql .= " AND (v.status = ".self::STATUS_VALIDATED." OR v.status = ".self::STATUS_VALIDATION_WITHOUT_USER.")";

		dol_syslog(get_class($this)."::getActiveUserVolet", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while($obj = $this->db->fetch_object($resql)) {
				if($mode = 1) {
					$uservolet = new self($this->db);
					$uservolet->fetch($obj->rowid);
					if($get_fk_volet) {
						$ret[$obj->fk_volet] = clone $uservolet;
					}
					else {
						$ret[$obj->rowid] = clone $uservolet;
					}
				}
				else {
					if($get_fk_volet) {
						$ret[] = $obj->fk_volet;
					}
					else {
						$ret[] = $obj->rowid;
					}
				}
			}
			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Clôture les uservolet actifs lors de la validation d'un nouveau uservolet
	 *
	 *  @param  string	$fk_volet		filtre pour le numéro des volets à récupérer
	 *  @return	int		> 0 if OK, < 0 if KO
	 */
	public function closeActiveUserVolet($fk_volet = '')
	{
		global $conf, $user;

		$listUserVolet = $this->getActiveUserVolet(1, 0, 0, $fk_volet);
		
		if (is_array($listUserVolet)) {
			foreach($listUserVolet as $uservolet) {
				$result = $uservolet->close($user);

				if(!$result) {
					$this->error = $uservolet->db->lasterror();
					return -1;
				}
			}
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Récupère les volets dont l'objet (UserAutorisation / UserFormation / UserHabilitation) est lié
	 *
	 * 	@param  int		$fk_source       	Id of source object
	 *  @param  int		$fk_source       	type of source object
	 *  @return	array(UserVolet)|int		> 0 if OK, < 0 if KO
	 */
	public function getVoletWithLinkedObject($fk_source, $sourcetype, $fk_user)
	{
		global $conf, $user;

		$userVolet = new UserVolet($this->db);
		$res = array();

		$sql = "SELECT uv.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_uservolet as uv";
		$sql .= " RIGHT JOIN ".MAIN_DB_PREFIX."element_element as e ON e.fk_target = uv.rowid AND e.targettype = '".$this->module."_".$this->element."' AND e.fk_source = $fk_source AND e.sourcetype = '$sourcetype'";
		$sql .= " WHERE uv.fk_user = $fk_user";
		$sql .= " AND (uv.status = ".self::STATUS_VALIDATED." OR uv.status = ".self::STATUS_VALIDATION_WITHOUT_USER.")";

		dol_syslog(get_class($this)."::getVoletWithLinkedObject", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while($obj = $this->db->fetch_object($resql)) {
				$userVolet->fetch($obj->rowid);
				$res[$obj->rowid] = clone $userVolet;
			}

			$this->db->free($resql);
			return $res;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Récupère les volets suspendus dont l'objet (UserAutorisation / UserFormation / UserHabilitation) est lié
	 *
	 * 	@param  int		$fk_source       	Id of source object
	 *  @param  int		$fk_source       	type of source object
	 *  @return	array(UserVolet)|int		> 0 if OK, < 0 if KO
	 */
	public function getVoletSuspendWithLinkedObject($fk_source, $sourcetype, $fk_user)
	{
		global $conf, $user;

		$userVolet = new UserVolet($this->db);
		$res = array();

		$sql = "SELECT uv.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_uservolet as uv";
		$sql .= " RIGHT JOIN ".MAIN_DB_PREFIX."element_element as e ON e.fk_target = uv.rowid AND e.targettype = '".$this->module."_".$this->element."' AND e.fk_source = $fk_source AND e.sourcetype = '$sourcetype'";
		$sql .= " WHERE uv.fk_user = $fk_user";
		$sql .= " AND uv.status = ".self::STATUS_SUSPEND;

		dol_syslog(get_class($this)."::getVoletWithLinkedObject", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while($obj = $this->db->fetch_object($resql)) {
				$userVolet->fetch($obj->rowid);
				$res[$obj->rowid] = clone $userVolet;
			}

			$this->db->free($resql);
			return $res;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	public function getDateFinVolet($volet){
		global $conf; 

		$date_fin_volet = '';

		if($volet->typevolet == 1) {
			$name_date = 'date_finvalidite_formation';
		}
		elseif($volet->typevolet == 2) {
			$name_date = 'date_fin_habilitation';
		}
		elseif($volet->typevolet == 3) {
			$name_date = 'date_fin_autorisation';
		}

		$lines = $this->getLinkedLinesArray();
		foreach($lines as $line) {
			if($date_fin_volet == '' || $line->$name_date < $date_fin_volet) {
				$date_fin_volet = $line->$name_date;
			}
		}
		
		if($date_fin_volet > dol_time_plus_duree($this->datedebutvolet, $conf->global->FORMTIONHABILITATION_VOLETDURATIONMAX, 'm')) {
			$date_fin_volet = dol_time_plus_duree($this->datedebutvolet, $conf->global->FORMTIONHABILITATION_VOLETDURATIONMAX, 'm');
		}

		return $date_fin_volet;
	}

}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class UserVoletLine. You can also remove this and generate a CRUD class for lines objects.
 */
class UserVoletLine extends CommonObjectLine
{
	// To complete with content of an object UserVoletLine
	// We should have a field rowid, fk_uservolet and position

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
