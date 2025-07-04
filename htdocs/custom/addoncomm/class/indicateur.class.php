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
 * \file        class/indicateur.class.php
 * \ingroup     addoncomm
 * \brief       This file is a CRUD class file for Indicateur (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Indicateur
 */
class Indicateur extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'addoncomm';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'indicateur';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'widgetindicateur_indicateur';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for indicateur. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'indicateur@addoncomm' if picto is file 'img/object_indicateur.png'.
	 */
	public $picto = 'fa-file';


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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>1, 'alwayseditable'=>'1', 'searchall'=>1, 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'help'=>"Help text", 'showoncombobox'=>'2', 'validate'=>'1',),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>2000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Valid&eacute;', '9'=>'Annul&eacute;'), 'validate'=>'1',),
	);
	public $rowid;
	public $ref;
	public $label;
	public $import_key;
	public $status;
	// END MODULEBUILDER PROPERTIES

	 /**
  * @var array
  */
  public $listoffields = array(
	'propal'=>array(
	'name'=>"Proposals",
	'title'=>"ListProposalsAssociatedProject",
	'class'=>'Propal',
	'table'=>'propal',
	'datefieldname'=>'datep',
	'lang'=>'propal',
	),
	'order'=>array(
		'name'=>"CustomersOrders",
		'title'=>"ListOrdersAssociatedProject",
		'class'=>'Commande',
		'table'=>'commande',
		'datefieldname'=>'date_commande',
		'lang'=>'orders',
		'buttonnew'=>'CreateOrder',
	),
	'invoice'=>array(
		'name'=>"CustomersInvoices",
		'title'=>"ListInvoicesAssociatedProject",
		'class'=>'Facture',
		'table'=>'facture',
		'datefieldname'=>'datef',
		'lang'=>'bills',
	),
	'invoice_predefined'=>array(
		'name'=>"PredefinedInvoices",
		'title'=>"ListPredefinedInvoicesAssociatedProject",
		'class'=>'FactureRec',
		'table'=>'facture_rec',
		'datefieldname'=>'datec',
		'lang'=>'bills',
	),
	'proposal_supplier'=>array(
		'name'=>"SupplierProposals",
		'title'=>"ListSupplierProposalsAssociatedProject",
		'class'=>'SupplierProposal',
		'table'=>'supplier_proposal',
		'datefieldname'=>'date_valid',
		'lang'=>'supplier_proposal',
	),
	'order_supplier'=>array(
		'name'=>"SuppliersOrders",
		'title'=>"ListSupplierOrdersAssociatedProject",
		'class'=>'CommandeFournisseur',
		'table'=>'commande_fournisseur',
		'datefieldname'=>'date_commande',
		'lang'=>'suppliers',
		'buttonnew'=>'AddSupplierOrder',
	),
	'invoice_supplier'=>array(
		'name'=>"BillsSuppliers",
		'title'=>"ListSupplierInvoicesAssociatedProject",
		'class'=>'FactureFournisseur',
		'table'=>'facture_fourn',
		'datefieldname'=>'datef',
		'lang'=>'suppliers',
	),
	'contract'=>array(
		'name'=>"Contracts",
		'title'=>"ListContractAssociatedProject",
		'class'=>'Contrat',
		'table'=>'contrat',
		'datefieldname'=>'date_contrat',
		'lang'=>'contracts',
	),
	'intervention'=>array(
		'name'=>"Interventions",
		'title'=>"ListFichinterAssociatedProject",
		'class'=>'Fichinter',
		'table'=>'fichinter',
		'datefieldname'=>'date_valid',
		'lang'=>'interventions',
	),
	'trip'=>array(
		'name'=>"TripsAndExpenses",
		'title'=>"ListExpenseReportsAssociatedProject",
		'class'=>'Deplacement',
		'table'=>'deplacement',
		'datefieldname'=>'dated',
		'lang'=>'trips',
	),
	'expensereport'=>array(
		'name'=>"ExpenseReports",
		'title'=>"ListExpenseReportsAssociatedProject",
		'class'=>'ExpenseReportLine',
		'table'=>'expensereport_det',
		'datefieldname'=>'date',
		'lang'=>'trips',
	),
	'donation'=>array(
		'name'=>"Donation",
		'title'=>"ListDonationsAssociatedProject",
		'class'=>'Don',
		'margin'=>'add',
		'table'=>'don',
		'datefieldname'=>'datedon',
		'lang'=>'donations',
		'buttonnew'=>'AddDonation',
	),
	'loan'=>array(
		'name'=>"Loan",
		'title'=>"ListLoanAssociatedProject",
		'class'=>'Loan',
		'margin'=>'add',
		'table'=>'loan',
		'datefieldname'=>'datestart',
		'lang'=>'loan',
	),
	'chargesociales'=>array(
		'name'=>"SocialContribution",
		'title'=>"ListSocialContributionAssociatedProject",
		'class'=>'ChargeSociales',
		'margin'=>'minus',
		'table'=>'chargesociales',
		'datefieldname'=>'date_ech',
		'lang'=>'compta',
		'buttonnew'=>'AddSocialContribution',
	),
	'project_task'=>array(
		'name'=>"TaskTimeSpent",
		'title'=>"ListTaskTimeUserProject",
		'class'=>'Task',
		'margin'=>'minus',
		'table'=>'projet_task',
		'datefieldname'=>'element_date',
		'buttonnew'=>'AddTimeSpent',
	),
	'stock_mouvement'=>array(
		'name'=>"MouvementStockAssociated",
		'title'=>"ListMouvementStockProject",
		'class'=>'MouvementStock',
		'table'=>'stock_mouvement',
		'datefieldname'=>'datem',
	),
	'salaries'=>array(
		'name'=>"Salaries",
		'title'=>"ListSalariesAssociatedProject",
		'class'=>'Salary',
		'table'=>'salary',
		'datefieldname'=>'datesp',
		'lang'=>'salaries',
	),
	'variouspayment'=>array(
		'name'=>"VariousPayments",
		'title'=>"ListVariousPaymentsAssociatedProject",
		'class'=>'PaymentVarious',
		'table'=>'payment_various',
		'datefieldname'=>'datev',
		'lang'=>'banks',
	),
);

	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'widgetindicateur_indicateurline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_indicateur';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Indicateurline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array('mychildtable' => array('name'=>'Indicateur', 'fk_element'=>'fk_indicateur'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('widgetindicateur_indicateurdet');

	// /**
	//  * @var IndicateurLine[]     Array of subtable lines
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
		/*if ($user->hasRight('addoncomm', 'indicateur', 'read')) {
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('addoncomm','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && !empty($user->rights->addoncomm->indicateur->indicateur_advance->validate))))
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'indicateur/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'indicateur/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'indicateur/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'indicateur/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->addoncomm->dir_output.'/indicateur/'.$oldref;
				$dirdest = $conf->addoncomm->dir_output.'/indicateur/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->addoncomm->dir_output.'/indicateur/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('addoncomm','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('addoncomm','widgetindicateur_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'WIDGETINDICATEUR_MYOBJECT_UNVALIDATE');
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

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('addoncomm','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('addoncomm','widgetindicateur_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'WIDGETINDICATEUR_MYOBJECT_CANCEL');
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

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('addoncomm','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('addoncomm','widgetindicateur_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'WIDGETINDICATEUR_MYOBJECT_REOPEN');
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
			return ['optimize' => $langs->trans("ShowIndicateur")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Indicateur").'</u>';
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

		$url = dol_buildpath('/addoncomm/indicateur_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowIndicateur");
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
			//$langs->load("addoncomm@addoncomm");
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

		$objectline = new IndicateurLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_indicateur = '.((int) $this->id)));

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
		$langs->load("addoncomm@addoncomm");

		if (!getDolGlobalString('WIDGETINDICATEUR_MYOBJECT_ADDON')) {
			$conf->global->WIDGETINDICATEUR_MYOBJECT_ADDON = 'mod_indicateur_standard';
		}

		if (getDolGlobalString('WIDGETINDICATEUR_MYOBJECT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('WIDGETINDICATEUR_MYOBJECT_ADDON').".php";
			$classname = getDolGlobalString('WIDGETINDICATEUR_MYOBJECT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/addoncomm/");

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

		$langs->load("addoncomm@addoncomm");

		if (!dol_strlen($modele)) {
			$modele = 'standard_indicateur';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('MYOBJECT_ADDON_PDF')) {
				$modele = getDolGlobalString('MYOBJECT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/addoncomm/doc/";

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
	 * get values ​​from queries for graph data
	 * 
	 * @param string option either filtring or not
	 * @param array agences to filter
	 * @param array responsable in contact to fitler
	 * @param array projets to filter
	 * 
	 * @return array of data (date and amount) for graph
	 */
	public function getValues($option,$arr_agences, $arr_resp,  $arr_proj)
	{
		$modeFiltring = !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj);
		$modeNotFiltring = empty($arr_agences) && empty($arr_resp) && empty($arr_proj);


		if($option == 'resp') {
			$vals['salaries'] = $this->employeeCostByProject($option, $arr_agences, $arr_resp,  $arr_proj);
		}elseif($option == 'all'){
			if($modeNotFiltring) {
				$vals['salaries'] = $this->getSalariesByMonth();
				$vals['donation'] = $this->getDonation();
				$vals['various'] = $this->getVariousPayment();
				$vals['loan'] = $this->getPaymentLoan();
			}elseif($modeFiltring){
				// $vals['salaries'] = $this->employeeCostByProject($option, $arr_agences, $arr_resp,  $arr_proj);
				$vals['salaries'] = $this->getSalariesByMonth();
			}
		}
		
		$vals['facture'] = $this->getFacture($option, $arr_agences, $arr_resp, $arr_proj);
		$vals['facture_fourn'] = $this->getFactureFourn($option, $arr_agences, $arr_resp, $arr_proj);
		$vals['note'] = $this->getExpenseReport($option, $arr_agences, $arr_resp, $arr_proj);
		$vals['soc'] = $this->getSocialContribution($option, $arr_agences, $arr_resp, $arr_proj);

		return $vals;
	}

	/**
	 * get salaries for each month
	 * 
	 * @return array of date payment and employees salaries amount by month
	 */
	public function getSalariesByMonth()
	{
		global $db, $user;
		$childids = $user->getAllChildIds(1);

		$sql = "SELECT u.rowid as uid,";
		$sql .= " DATE_FORMAT(s.datesp,'%Y-%m') as datesp, s.dateep, ";
		$sql .= " SUM(ps.amount) as alreadypayed";
		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_salary as ps ON (ps.fk_salary = s.rowid) ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON (s.fk_typepayment = pst.id) ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account ba ON (ba.rowid = s.fk_account), ";
		//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_salary as ps ON ps.fk_salary = s.rowid, ";
		$sql .= " ".MAIN_DB_PREFIX."user as u";

		$sql .= " WHERE u.rowid = s.fk_user";
		$sql .= " AND s.entity IN (".getEntity('payment_salaries').")";
		if (empty($user->rights->salaries->readall)) {
			$sql .= " AND s.fk_user IN (".$db->sanitize(join(',', $childids)).")";
		}

		$sql .= " GROUP BY DATE_FORMAT(s.datesp,'%Y-%m')";

		dol_syslog(get_class($this)."::getSalariesByMonth", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$salaries[] = array('date' => $obj->datesp, 'amount' => $obj->alreadypayed);
				
				$i++;
			} 
			return $salaries;
		} else {
			$this->error = $db->error();
			return -1;
		}

	}

	/**
	 * get salaries of employees by reference to the projects
	 * 
	 * @param string option to filter by option either resp or all py default
	 */
	public function employeeCostByProject($option,$arr_agences, $arr_resp,  $arr_proj)
	{
		
		global $db, $user;

		$sql = "SELECT sum((t.thm) * (t.element_duration / 3600)) as cost, DATE_FORMAT(t.element_date,'%Y-%m') as dm";
		$sql .= ", SUM(hs.heure_sup_25_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm * 0.25").") as amount_hs25,";
        $sql .= " SUM(hs.heure_sup_50_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm * 0.5").") as amount_hs50, u.rowid as userid";

		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs ON hs.fk_projet_task_time = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on ec.fk_socpeople = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
		$sql .= " WHERE 1 = 1";

			if(!empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
				
				if(!empty($arr_resp) && $option ='all') {
					$sql .= " AND tc.element = 'project'";
					$sql .= " AND tc.source = 'internal'";
					$sql .= " AND tc.code = 'PROJECTLEADER'";
					$sql .= " AND ec.element_id = p.rowid";
					// $sql .= " AND u.rowid  IN (".implode(', ', $arr_resp).")";
					$sql .= " AND ec.fk_socpeople IN (".implode(', ', $arr_resp).")";
				}

				if(!empty($arr_proj) && $option ='all') {
					$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
				}
				
				if(!empty($arr_agences) && $option ='all') {
					$sql .= " AND pe.agenceconcerne  IN (".implode(', ', $arr_agences).")";
				}
			}

			if($option == 'resp') {
				$i = 1;
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);
					foreach($agences as $manager => $agenceid) {
						$sql .= " pe.agenceconcerne =".((int) $agenceid);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}
						$i++;
					}
				}
			}
			
		

		$sql .= " AND t.elementtype = 'task'";
		$sql .= " GROUP BY dm, u.rowid";
		$sql .= " ORDER BY dm";
	
		dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$hs25 =  $obj->amount_hs25 == null ? 0 : $obj->amount_hs25;
				$hs50 = $obj->amount_hs50 == null ? 0 : $obj->amount_hs50;
				$cost = $obj->cost + $hs25 + $hs50;
				$salaries[] = array('date' => $obj->dm, 'amount' => $cost);
	
				$i++;
			} 

			return $salaries;
		} else {
			$this->error = $db->error();
			return -1;
		}
	
	}


	/**
	 * get social contributions
	 * 
	 * @return array of date and social contribution amount by month
	 */
	public function getSocialContribution($option, $arr_agences, $arr_resp, $arr_proj)
	{
		global $db, $user;

		$sql = "SELECT c.libelle as nom, date_format(cs.date_ech,'%Y-%m') as dm, sum(cs.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";

		if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on cs.fk_projet = p.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on pe.fk_object = p.rowid";

			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = de.fk_projet";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

			$sql .= " WHERE 1 = 1";
			$i = 1;

			if(!empty($arr_resp) && $option ='all') {
				$sql .= " AND tc.element = 'project'";
				$sql .= " AND tc.source = 'internal'";
				$sql .= " AND tc.code = 'PROJECTLEADER'";
		
				$sql .= " AND ec.fk_socpeople  IN (".implode(', ', $arr_resp).")";
				}
	
			if(!empty($arr_agences) && $option == 'all') {
				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
			}

			if(!empty($arr_proj) && $option == 'all') {
				$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
			}
	
			if($option == 'resp') {
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);

					foreach($agences as $manager => $agence) {
						$sql .= " pe.agenceconcerne =".((int) $agence);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}

						$i++;
					}
				}
			}
		}else{
			$sql .= " WHERE 1 = 1";
		}

		$sql .= " AND cs.fk_type = c.id";
		$sql .= " GROUP BY c.libelle, dm";

		dol_syslog(get_class($this)."::getSocialContribution", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$soccontribute[] = array('date' => $obj->dm, 'amount' => $obj->amount);
				
				$i++;
			} 
			return $soccontribute;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get expense report
	 * 
	 * @return array date and exepnense report amount 
	 */
	public function getExpenseReport($option, $arr_agences, $arr_resp, $arr_proj)
	{
		global $db;

		$sql = "SELECT date_format(r.date_debut,'%Y-%m') as dm, sum(r.total_ht) as amount_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as r";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user_author";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_det as de ON r.rowid = de.fk_expensereport";
	
		if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on de.fk_projet = p.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on pe.fk_object = p.rowid";

			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = pe.fk_object";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

			$sql .= " WHERE 1 = 1";
			$i = 1;

			if(!empty($arr_resp) && $option ='all') {
				$sql .= " AND tc.element = 'project'";
				$sql .= " AND tc.source = 'internal'";
				$sql .= " AND tc.code = 'PROJECTLEADER'";
		
				$sql .= " AND ec.fk_socpeople  IN (".implode(', ', $arr_resp).")";
				}
	
			if(!empty($arr_agences) && $option == 'all') {
				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
			}

			if(!empty($arr_proj) && $option == 'all') {
				$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
			}
	
			if($option == 'resp') {
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);

					foreach($agences as $manager => $agence) {
						$sql .= " pe.agenceconcerne =".((int) $agence);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}

						$i++;
					}
				}
			}
		}else{
			$sql .= " WHERE 1 = 1";
		}
		
		
		$sql .= " AND r.entity IN (".getEntity('expensereport').")";
		$sql .= " AND r.fk_statut = 6";
		$sql .= " GROUP BY dm";

		dol_syslog(get_class($this)."::getExpenseReport", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$exepnesereport[] = array('date' => $obj->dm, 'amount' => $obj->amount_ht);
				
				$i++;
			} 
			return $exepnesereport;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get donation and dunning payment 
	 * 
	 * @return array date, donanation and dunnig payment by month
	 */
	public function getDonation()
	{
		global $db;

		$sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."don as p";
		$sql .= " WHERE p.entity IN (".getEntity('donation').")";
		$sql .= " AND fk_statut in (1,2)";

		$sql .= " GROUP BY p.societe, p.firstname, p.lastname, dm";

		dol_syslog(get_class($this)."::getDonation", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$donation[] = array('date' => $obj->dm, 'amount' => $obj->amount);
				
				$i++;
			} 
			return $donation;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get various payment 
	 * 
	 * @return array date and various payment amount by month
	 */
	public function getVariousPayment()
	{
		global $db;

		$sql = "SELECT date_format(p.datep, '%Y-%m') AS dm, SUM(p.amount) AS amount FROM ".MAIN_DB_PREFIX."payment_various as p";
		$sql .= " WHERE p.entity IN (".getEntity('variouspayment').")";
		$sql .= ' AND p.sens = 0';
		$sql .= ' GROUP BY dm';

		dol_syslog(get_class($this)."::getVariousPayment", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$variouspay[] = array('date' => $obj->dm, 'amount' => $obj->amount);
				
				$i++;
			} 
			return $variouspay;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get payment loan
	 * 
	 * @return array date and payment loan by month
	 */
	 public function getPaymentLoan()
	 {
		global $db;

		$sql = "SELECT date_format(p.datep, '%Y-%m') AS dm, SUM(p.amount_capital + p.amount_insurance + p.amount_interest) AS amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_loan AS p, ".MAIN_DB_PREFIX."loan as l";
		$sql .= " WHERE l.entity IN (".getEntity('variouspayment').")";
		$sql .= " AND p.fk_loan = l.rowid";
		$sql .= ' GROUP BY dm';

		dol_syslog(get_class($this)."::getPaymentLoan", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$loan[] = array('date' => $obj->dm, 'amount' => $obj->amount);
				
				$i++;
			} 
			return $loan;
		} else {
			$this->error = $db->error();
			return -1;
		}
	 }

	 

	 /**
	 * Get facture from database. Get also lines.
	 * 
	 */
	public function getFacture($option, $arr_agences, $arr_resp, $arr_proj)
	{
		global $conf, $db, $langs, $user;
		$i = 1;

		$sql = "SELECT f.rowid, date_format(f.datef, '%Y-%m') AS dm, SUM(f.total_ht) as amount_ht, p.rowid as projid, f.ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = fe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = f.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
		
		if(empty($arr_resp) && !empty($arr_agences) && !empty($arr_proj) && $option ='all') {
			$sql .= " WHERE 1 = 1";
		}

		if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
			$i = 1;

			if(!empty($arr_resp) && $option ='all') {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = f.fk_projet";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

				$sql .= " WHERE 1 = 1";
				$sql .= " AND tc.element = 'project'";
				$sql .= " AND tc.source = 'internal'";
				$sql .= " AND tc.code = 'PROJECTLEADER'";
		
				$sql .= " AND t.rowid  IN (".implode(', ', $arr_resp).")";
			 }
	
			if(!empty($arr_agences) && $option == 'all') {
				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
			}

			if(!empty($arr_proj) && $option == 'all') {
				$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
			}
	
			if($option == 'resp') {
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);

					foreach($agences as $manager => $agence) {
						$sql .= " pe.agenceconcerne =".((int) $agence);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}

						$i++;
					}
				}
			}
		}
		else{
			$sql .= " WHERE 1 = 1";
		}
	
		$sql .= " AND fe.pv_reception = 1";
		$sql.= " GROUP BY dm";
		

		dol_syslog(get_class($this)."::getFacture", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
			
				$factures[] = array('date' => $obj->dm, 'amount' => $obj->amount_ht);
				$i++;
			} 
			return array_filter($factures);
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * Get facture fournisseur
	 */
	public function getFactureFourn($option, $arr_agences, $arr_resp, $arr_proj)
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT date_format(faf.datef,'%Y-%m') as dm, SUM(faf.total_ht) as amount_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as faf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn_extrafields as fafe on faf.rowid = fafe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = faf.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";

		if(empty($arr_resp) && !empty($arr_agences) && !empty($arr_proj) && $option ='all') {
			$sql .= " WHERE 1 = 1";
		}

		if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
			$i = 1;

			if(!empty($arr_resp) && $option ='all') {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

				$sql .= " WHERE 1 = 1";
				$sql .= " AND tc.element = 'project'";
				$sql .= " AND tc.source = 'internal'";
				$sql .= " AND tc.code = 'PROJECTLEADER'";
		
				$sql .= " AND ec.fk_socpeople IN (".implode(', ', $arr_resp).")";
			 }
	
			if(!empty($arr_agences) && $option == 'all') {
				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
			}

			if(!empty($arr_proj) && $option == 'all') {
				$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
			}
	
			if($option == 'resp') {
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);

					foreach($agences as $manager => $agence) {
						$sql .= " pe.agenceconcerne =".((int) $agence);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}

						$i++;
					}
				}
			}
		}
		else{
			$sql .= " WHERE 1 = 1";
		}


		$sql .= " AND faf.fk_statut IN (1,2)";
		if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
			$sql .= " AND faf.type IN (0,1,2,5)";
		} else {
			$sql .= " AND faf.type IN (0,1,2,3,5)";
		}
		$sql .= " AND faf.entity IN (".getEntity('invoice').")";

		$sql.= " GROUP BY dm";
	

		dol_syslog(get_class($this)."::getFactureFourn", LOG_DEBUG);
		$resql = $db->query($sql);
	
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				
				$facturesfourn[] = array('date' => $obj->dm, 'amount' => $obj->amount_ht);
				
			
				$i++;
			} 
			// var_dump($facturesfourn);
			return $facturesfourn;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}


	 /**
	 * Get agences by user.
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
		$sql .= " AND s.rowid = sc.fk_soc";
		$sql .= " AND s.nom = '".$db->escape($name)."'";
		 $sql .= " AND u.rowid =".((int) $user->id);
		// $sql .= " AND u.rowid = 1";

		$sql .= " ORDER BY u.lastname ASC, u.firstname ASC";
	

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

			return array_unique($agences);
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	 /**
	 * Get agences.
	 * 
	 */
	public function getAgences()
	{
		global $conf, $db, $langs, $user;
		$name = 'OPTIM Industries';

		$sql = "SELECT DISTINCT u.rowid as userid, s.rowid as socid, u.lastname, u.firstname, u.email, u.statut as status, u.entity, s.rowid as agenceid, s.nom as name, s.name_alias";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= ", ".MAIN_DB_PREFIX."user as u";
		$sql .= " , ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE u.entity in (0, 1) AND u.rowid = sc.fk_user";
		$sql .= " AND s.rowid = sc.fk_soc";
		$sql .= " AND s.nom = '".$db->escape($name)."'";
		// $sql .= " AND u.rowid =".((int) $user->id);
	

		$sql .= " ORDER BY u.lastname ASC, u.firstname ASC";
	

		dol_syslog(get_class($this)."::getAgences", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$agences[$obj->socid] = $obj->name_alias;
				
			
				$i++;
			} 

			return array_unique($agences);
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	 /**
	 * Get responsable de suivi.
	 * 
	 */
	public function getResp()
	{
		global $db;
		$error = 0;
		$projects = array();
		$sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, p.ref, ec.element_id, p.ref, DATE_FORMAT(p.dateo ,'%Y-%m-%d') as date_start, DATE_FORMAT(p.datee ,'%Y-%m-%d') as date_end,";
		$sql .= " ec.fk_c_type_contact, t.statut as statuscontact,";
		$sql .= " t.lastname, t.firstname, t.rowid as userid,";
		$sql .= " tc.source, tc.element, tc.code, tc.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on ec.element_id = p.rowid";
		// $sql .= " WHERE ec.element_id = 422";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'project'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PROJECTLEADER'";
		$sql .= " AND t.statut = '1'";
		$sql .= " AND p.fk_statut = 1";
		
		// if(!empty($arr_user)) {
		// 	$sql .= " AND t.rowid IN (".implode(', ', $arr_resp).")";
		// }
		
		$sql .= " ORDER BY t.lastname ASC";
	
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$projects[$obj->userid] = $obj->firstname.' '.$obj->lastname;
				}
				$i++;
			}
			$db->free($resql);
	
			return $projects;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * get list of projects
	 */
	public function select_multi_projects()
	{
		global $db, $user;

		$sql = "SELECT p.rowid, p.ref, p.title as nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
	
		dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				
				$projects[$obj->rowid] = $obj->ref.', '.$obj->nom;
	
				$i++;
			} 

			return $projects;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}
}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class IndicateurLine. You can also remove this and generate a CRUD class for lines objects.
 */
class IndicateurLine extends CommonObjectLine
{
	// To complete with content of an object IndicateurLine
	// We should have a field rowid, fk_indicateur and position

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
