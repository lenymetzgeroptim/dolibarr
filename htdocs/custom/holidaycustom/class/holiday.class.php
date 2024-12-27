<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2016       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *    \file       holiday.class.php
 *    \ingroup    holiday
 *    \brief      Class file of the module paid holiday.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/extendedhtml.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';

/**
 *	Class of the module paid holiday. Developed by Teclib ( http://www.teclib.com/ )
 */
class Holiday extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'holidaycustom';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'holiday';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_holiday';

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	 *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	 */
	protected $childtablesoncascade = array('holiday_approbation');

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'holiday';

	/**
	 * @deprecated
	 * @see $id
	 */
	public $rowid;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	public $date_create = '';

	/**
	 * @var string description
	 */
	public $description;

	public $date_debut = ''; // Date start in PHP server TZ
	public $date_fin = ''; // Date end in PHP server TZ
	public $date_debut_gmt = ''; // Date start in GMT
	public $date_fin_gmt = ''; // Date end in GMT
	public $halfday = ''; // 0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning
	public $statut = ''; // 1=draft, 2=validated, 3=approved

	/**
	 * @var int 	ID of user that must approve. TODO: there is no date for validation (date_valid is used for approval), add one.
	 */
	public $fk_validator;

	/**
	 * @var int 	Date of approval. TODO: Add a field for approval date and use date_valid instead for validation.
	 */
	public $date_valid = '';

	/**
	 * @var int 	ID of user that has approved (empty if not approved)
	 */
	public $fk_user_valid;


	/**
	 * @var int 	Date for refuse
	 */
	public $date_refuse = '';

	/**
	 * @var int 	ID for refuse
	 */
	public $fk_user_refuse;

	/**
	 * @var int 	Date for cancelation
	 */
	public $date_cancel = '';

	/**
	 * @var int 	ID for cancelation
	 */
	public $fk_user_cancel;


	public $detail_refuse = '';

	/**
	 * @var int ID
	 */
	public $fk_type;

	public $holiday = array();
	public $events = array();
	public $logs = array();

	public $optName = '';
	public $optValue = '';
	public $optRowid = '';

	public $import = '';

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 1;
	/**
	 * Validated status = En attente de 1er approbation
	 */
	const STATUS_VALIDATED = 2;
	/**
	 * Approved 2 => 2eme approbation réalisé = validé
	 */
	const STATUS_APPROVED2 = 3;
	/**
	 * Approved 1 => 1ere approbation réalisé, en attente de 2e approbation
	 */
	const STATUS_APPROVED1 = 6;
	/**
	 * Canceled
	 */
	const STATUS_CANCELED = 4;
	/**
	 * Refused
	 */
	const STATUS_REFUSED = 5;



	/**
	 *   Constructor
	 *
	 *   @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Returns the reference to the following non used Order depending on the active numbering module
	 *  defined into HOLIDAY_ADDON
	 *
	 *	@param	Societe		$objsoc     third party object
	 *  @return string      			Holiday free reference
	 */
	public function getNextNumRef($objsoc)
	{
		global $langs, $conf;
		$langs->load("order");

		if (empty($conf->global->HOLIDAY_ADDON)) {
			$conf->global->HOLIDAY_ADDON = 'mod_holiday_madonna';
		}

		if (!empty($conf->global->HOLIDAY_ADDON)) {
			$mybool = false;

			$file = $conf->global->HOLIDAY_ADDON.".php";
			$classname = $conf->global->HOLIDAY_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/holiday/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($objsoc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_HOLIDAY_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * Update balance of vacations and check table of users for holidays is complete. If not complete.
	 *
	 * @return	int			<0 if KO, >0 if OK
	 */
	public function updateBalance()
	{
		$this->db->begin();

		// Update sold of vocations
		$result = $this->updateSoldeCP();

		// Check nb of users into table llx_holiday_users and update with empty lines
		//if ($result > 0) $result = $this->verifNbUsers($this->countActiveUsersWithoutCP(), $this->getConfCP('nbUser'));

		if ($result >= 0) {
			$this->db->commit();
			return 0; // for cronjob use (0 is OK, any other value is an error code)
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *   Créer un congés payés dans la base de données
	 *
	 *   @param		User	$user        	User that create
	 *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *   @return    int			         	<0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		$now = dol_now();

		// Check parameters
		if (empty($this->fk_user) || !is_numeric($this->fk_user) || $this->fk_user < 0) {
			$this->error = "ErrorBadParameterFkUser"; return -1;
		}
		/*if (empty($this->fk_validator) || !is_numeric($this->fk_validator) || $this->fk_validator < 0) {
			$this->error = "ErrorBadParameterFkValidator"; return -1;
		}*/
		if (empty($this->fk_type) || !is_numeric($this->fk_type) || $this->fk_type < 0) {
			$this->error = "ErrorBadParameterFkType"; return -1;
		}

		// $user_static = new User($this->db);
		// $user_static->fetch($this->fk_user);
		// $user_validation_1 = new User($this->db);
		// if(!empty($user_static->fk_user)){
		// 	$user_validation_1->fetch($user_static->fk_user);
		// }
		// else {
		// 	$user_validation_1->fetch(16);
		// }

		// $user_validation_2 = new User($this->db);
		// if(!empty($user_validation_1->fk_user) && $user_validation_1->fk_user != 16){
		// 	$user_validation_2->fetch($user_validation_1->fk_user);
		// }

		// if(!empty($user_validation_2->id)) {
		// 	$this->array_options["options_fk_validator2"] = $user_validation_2->id;
		// }

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday(";
		$sql .= "ref,";
		$sql .= "fk_user,";
		$sql .= "date_create,";
		$sql .= "description,";
		$sql .= "date_debut,";
		$sql .= "date_fin,";
		$sql .= "halfday,";
		$sql .= "statut,";
		$sql .= "fk_validator,";
		$sql .= "fk_type,";
		$sql .= "fk_user_create,";
		$sql .= "entity";
		$sql .= ") VALUES (";
		$sql .= "'(PROV)',";
		$sql .= " ".((int) $this->fk_user).",";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " '".$this->db->escape($this->description)."',";
		$sql .= " '".$this->db->idate($this->date_debut)."',";
		$sql .= " '".$this->db->idate($this->date_fin)."',";
		$sql .= " ".((int) $this->halfday).",";
		$sql .= " '1',";
		//$sql .= " ".((int) $user_validation_1->id).",";
		$sql .= " '0',";
		$sql .= " ".((int) $this->fk_type).",";
		$sql .= " ".((int) $user->id).",";
		$sql .= " ".((int) $conf->entity);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday");

			if ($this->id) {
				// update ref
				$initialref = '(PROV'.$this->id.')';
				if (!empty($this->ref)) {
					$initialref = $this->ref;
				}

				$sql = 'UPDATE '.MAIN_DB_PREFIX."holiday SET ref='".$this->db->escape($initialref)."' WHERE rowid=".((int) $this->id);
				if ($this->db->query($sql)) {
					$this->ref = $initialref;

					if (!$error) {
						$result = $this->insertExtraFields();
						if ($result < 0) {
							$error++;
						}
					}

					if (!$error && !$notrigger) {
						// Call trigger
						$result = $this->call_trigger('HOLIDAYCUSTOM_CREATE', $user);
						if ($result < 0) {
							$error++;
						}
						// End call triggers
					}
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *	Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @param	string	$ref        Ref object
	 *  @return int         		<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		global $langs;

		$sql = "SELECT";
		$sql .= " cp.rowid,";
		$sql .= " cp.ref,";
		$sql .= " cp.fk_user,";
		$sql .= " cp.date_create,";
		$sql .= " cp.description,";
		$sql .= " cp.date_debut,";
		$sql .= " cp.date_fin,";
		$sql .= " cp.halfday,";
		$sql .= " cp.statut,";
		$sql .= " cp.fk_validator,";
		$sql .= " cp.date_valid,";
		$sql .= " cp.fk_user_valid,";
		$sql .= " cp.date_refuse,";
		$sql .= " cp.fk_user_refuse,";
		$sql .= " cp.date_cancel,";
		$sql .= " cp.fk_user_cancel,";
		$sql .= " cp.detail_refuse,";
		$sql .= " cp.note_private,";
		$sql .= " cp.note_public,";
		$sql .= " cp.fk_user_create,";
		$sql .= " cp.fk_type,";
		$sql .= " cp.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp";
		if ($id > 0) {
			$sql .= " WHERE cp.rowid = ".((int) $id);
		} else {
			$sql .= " WHERE cp.ref = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = ($obj->ref ? $obj->ref : $obj->rowid);
				$this->fk_user = $obj->fk_user;
				$this->date_create = $this->db->jdate($obj->date_create);
				$this->description = $obj->description;
				$this->date_debut = $this->db->jdate($obj->date_debut);
				$this->date_fin = $this->db->jdate($obj->date_fin);
				$this->date_debut_gmt = $this->db->jdate($obj->date_debut, 1);
				$this->date_fin_gmt = $this->db->jdate($obj->date_fin, 1);
				$this->halfday = $obj->halfday;
				$this->statut = $obj->statut;
				$this->fk_validator = $obj->fk_validator;
				$this->date_valid = $this->db->jdate($obj->date_valid);
				$this->fk_user_valid = $obj->fk_user_valid;
				$this->date_refuse = $this->db->jdate($obj->date_refuse);
				$this->fk_user_refuse = $obj->fk_user_refuse;
				$this->date_cancel = $this->db->jdate($obj->date_cancel);
				$this->fk_user_cancel = $obj->fk_user_cancel;
				$this->detail_refuse = $obj->detail_refuse;
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->fk_user_create = $obj->fk_user_create;
				$this->fk_type = $obj->fk_type;
				$this->entity = $obj->entity;

				$this->fetch_optionals();

				$result = 1;
			} else {
				$result = 0;
			}
			$this->db->free($resql);

			if ($result) {
				$this->listApprover1 = $this->listApprover('', 1);
				$this->listApprover2 = $this->listApprover('', 2);
			}

			return $result;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	List holidays for a particular user or list of users
	 *
	 *  @param		int|string		$user_id    ID of user to list, or comma separated list of IDs of users to list
	 *  @param      string			$order      Sort order
	 *  @param      string			$filter     SQL Filter
	 *  @return     int      					-1 if KO, 1 if OK, 2 if no result
	 */
	public function fetchByUser($user_id, $order = '', $filter = '')
	{
		global $langs, $conf;

		$sql = "SELECT";
		$sql .= " cp.rowid,";
		$sql .= " cp.ref,";

		$sql .= " cp.fk_user,";
		$sql .= " cp.fk_type,";
		$sql .= " cp.date_create,";
		$sql .= " cp.description,";
		$sql .= " cp.date_debut,";
		$sql .= " cp.date_fin,";
		$sql .= " cp.halfday,";
		$sql .= " cp.statut,";
		$sql .= " cp.fk_validator,";
		$sql .= " cp.date_valid,";
		$sql .= " cp.fk_user_valid,";
		$sql .= " cp.date_refuse,";
		$sql .= " cp.fk_user_refuse,";
		$sql .= " cp.date_cancel,";
		$sql .= " cp.fk_user_cancel,";
		$sql .= " cp.detail_refuse,";

		$sql .= " uu.lastname as user_lastname,";
		$sql .= " uu.firstname as user_firstname,";
		$sql .= " uu.login as user_login,";
		$sql .= " uu.statut as user_statut,";
		$sql .= " uu.photo as user_photo";

		// $sql .= " ua.lastname as validator_lastname,";
		// $sql .= " ua.firstname as validator_firstname,";
		// $sql .= " ua.login as validator_login,";
		// $sql .= " ua.statut as validator_statut,";
		// $sql .= " ua.photo as validator_photo";

		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp, ".MAIN_DB_PREFIX."user as uu"/*.MAIN_DB_PREFIX."user as ua"*/;
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = uu.rowid /*AND cp.fk_validator = ua.rowid*/"; // Hack pour la recherche sur le tableau
		$sql .= " AND cp.fk_user IN (".$this->db->sanitize($user_id).")";

		// Selection filter
		if (!empty($filter)) {
			$sql .= $filter;
		}

		// Order of display of the result
		if (!empty($order)) {
			$sql .= $order;
		}

		dol_syslog(get_class($this)."::fetchByUser", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// If no SQL error
		if ($resql) {
			$i = 0;
			$tab_result = $this->holiday;
			$num = $this->db->num_rows($resql);

			// If no registration
			if (!$num) {
				return 2;
			}

			// List the records and add them to the table
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['ref'] = ($obj->ref ? $obj->ref : $obj->rowid);

				$tab_result[$i]['fk_user'] = $obj->fk_user;
				$tab_result[$i]['fk_type'] = $obj->fk_type;
				$tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
				$tab_result[$i]['description'] = $obj->description;
				$tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
				$tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
				$tab_result[$i]['date_debut_gmt'] = $this->db->jdate($obj->date_debut, 1);
				$tab_result[$i]['date_fin_gmt'] = $this->db->jdate($obj->date_fin, 1);
				$tab_result[$i]['halfday'] = $obj->halfday;
				$tab_result[$i]['statut'] = $obj->statut;
				$tab_result[$i]['fk_validator'] = $obj->fk_validator;
				$tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
				$tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
				$tab_result[$i]['date_refuse'] = $this->db->jdate($obj->date_refuse);
				$tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
				$tab_result[$i]['date_cancel'] = $this->db->jdate($obj->date_cancel);
				$tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
				$tab_result[$i]['detail_refuse'] = $obj->detail_refuse;

				$tab_result[$i]['user_firstname'] = $obj->user_firstname;
				$tab_result[$i]['user_lastname'] = $obj->user_lastname;
				$tab_result[$i]['user_login'] = $obj->user_login;
				$tab_result[$i]['user_statut'] = $obj->user_statut;
				$tab_result[$i]['user_photo'] = $obj->user_photo;

				// $tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
				// $tab_result[$i]['validator_lastname'] = $obj->validator_lastname;
				// $tab_result[$i]['validator_login'] = $obj->validator_login;
				// $tab_result[$i]['validator_statut'] = $obj->validator_statut;
				// $tab_result[$i]['validator_photo'] = $obj->validator_photo;

				$i++;
			}

			// Returns 1 with the filled array
			$this->holiday = $tab_result;
			return 1;
		} else {
			// SQL Error
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	List all holidays of all users
	 *
	 *  @param      string	$order      Sort order
	 *  @param      string	$filter     SQL Filter
	 *  @return     int      			-1 if KO, 1 if OK, 2 if no result
	 */
	public function fetchAll($order, $filter)
	{
		global $langs;

		$sql = "SELECT";
		$sql .= " cp.rowid,";
		$sql .= " cp.ref,";

		$sql .= " cp.fk_user,";
		$sql .= " cp.fk_type,";
		$sql .= " cp.date_create,";
		$sql .= " cp.tms as date_update,";
		$sql .= " cp.description,";
		$sql .= " cp.date_debut,";
		$sql .= " cp.date_fin,";
		$sql .= " cp.halfday,";
		$sql .= " cp.statut,";
		$sql .= " cp.fk_validator,";
		$sql .= " cp.date_valid,";
		$sql .= " cp.fk_user_valid,";
		$sql .= " cp.date_refuse,";
		$sql .= " cp.fk_user_refuse,";
		$sql .= " cp.date_cancel,";
		$sql .= " cp.fk_user_cancel,";
		$sql .= " cp.detail_refuse,";

		$sql .= " uu.lastname as user_lastname,";
		$sql .= " uu.firstname as user_firstname,";
		$sql .= " uu.login as user_login,";
		$sql .= " uu.statut as user_statut,";
		$sql .= " uu.photo as user_photo";

		// $sql .= " ua.lastname as validator_lastname,";
		// $sql .= " ua.firstname as validator_firstname,";
		// $sql .= " ua.login as validator_login,";
		// $sql .= " ua.statut as validator_statut,";
		// $sql .= " ua.photo as validator_photo";

		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp, ".MAIN_DB_PREFIX."user as uu"/*.MAIN_DB_PREFIX."user as ua"*/;
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = uu.rowid /*AND cp.fk_validator = ua.rowid*/ "; // Hack pour la recherche sur le tableau

		// Selection filtering
		if (!empty($filter)) {
			$sql .= $filter;
		}

		// order of display
		if (!empty($order)) {
			$sql .= $order;
		}

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// If no SQL error
		if ($resql) {
			$i = 0;
			$tab_result = $this->holiday;
			$num = $this->db->num_rows($resql);

			// If no registration
			if (!$num) {
				return 2;
			}

			// List the records and add them to the table
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['ref'] = ($obj->ref ? $obj->ref : $obj->rowid);
				$tab_result[$i]['fk_user'] = $obj->fk_user;
				$tab_result[$i]['fk_type'] = $obj->fk_type;
				$tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
				$tab_result[$i]['date_update'] = $this->db->jdate($obj->date_update);
				$tab_result[$i]['description'] = $obj->description;
				$tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
				$tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
				$tab_result[$i]['date_debut_gmt'] = $this->db->jdate($obj->date_debut, 1);
				$tab_result[$i]['date_fin_gmt'] = $this->db->jdate($obj->date_fin, 1);
				$tab_result[$i]['halfday'] = $obj->halfday;
				$tab_result[$i]['statut'] = $obj->statut;
				$tab_result[$i]['fk_validator'] = $obj->fk_validator;
				$tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
				$tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
				$tab_result[$i]['date_refuse'] = $obj->date_refuse;
				$tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
				$tab_result[$i]['date_cancel'] = $obj->date_cancel;
				$tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
				$tab_result[$i]['detail_refuse'] = $obj->detail_refuse;

				$tab_result[$i]['user_firstname'] = $obj->user_firstname;
				$tab_result[$i]['user_lastname'] = $obj->user_lastname;
				$tab_result[$i]['user_login'] = $obj->user_login;
				$tab_result[$i]['user_statut'] = $obj->user_statut;
				$tab_result[$i]['user_photo'] = $obj->user_photo;

				// $tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
				// $tab_result[$i]['validator_lastname'] = $obj->validator_lastname;
				// $tab_result[$i]['validator_login'] = $obj->validator_login;
				// $tab_result[$i]['validator_statut'] = $obj->validator_statut;
				// $tab_result[$i]['validator_photo'] = $obj->validator_photo;

				$i++;
			}
			// Returns 1 and adds the array to the variable
			$this->holiday = $tab_result;
			return 1;
		} else {
			// SQL Error
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *	Validate leave request
	 *
	 *  @param	User	$user        	User that validate
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	public function validate($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$error = 0;

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref) || $this->ref == $this->id)) {
			$num = $this->getNextNumRef(null);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		// Update status
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";
		if (!empty($this->statut) && is_numeric($this->statut)) {
			$sql .= " statut = ".((int) $this->statut).",";
		} else {
			$error++;
		}
		$sql .= " ref = '".$this->db->escape($num)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::validate", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAYCUSTOM_VALIDATE', $user);
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
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'holiday/" . $this->db->escape($this->newref) . "'";
				$sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'holiday/" . $this->db->escape($this->ref) . "' and entity = " . ((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->holiday->multidir_output[$this->entity] . '/' . $oldref;
				$dirdest = $conf->holiday->multidir_output[$this->entity] . '/' . $newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this) . "::validate rename dir " . $dirsource . " into " . $dirdest);
					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($dirdest, 'files', 1, '^' . preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
							$dirsource = $fileentry['path'] . '/' . $dirsource;
							$dirdest = $fileentry['path'] . '/' . $dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}


		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::validate ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *	Approve leave request
	 *
	 *  @param	User	$user        	User that approve
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	public function approve($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";

		$sql .= " description= '".$this->db->escape($this->description)."',";

		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		$sql .= " halfday = ".((int) $this->halfday).",";
		if (!empty($this->statut) && is_numeric($this->statut)) {
			$sql .= " statut = ".((int) $this->statut).",";
		} else {
			$error++;
		}
		if ($this->fk_validator >= 0) {
			$sql .= " fk_validator = '".$this->db->escape($this->fk_validator)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = '".$this->db->escape($this->fk_user_valid)."',";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = '".$this->db->escape($this->fk_user_refuse)."',";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = '".$this->db->escape($this->fk_user_cancel)."',";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::approve", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAYCUSTOM_APPROVE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::approve ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Approve1 leave request
	 *
	 *  @param	User	$user        	User that approve
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	public function approve1($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";

		$sql .= " description= '".$this->db->escape($this->description)."',";

		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		$sql .= " halfday = ".((int) $this->halfday).",";
		if (!empty($this->statut) && is_numeric($this->statut)) {
			$sql .= " statut = ".self::STATUS_APPROVED1.",";
		} else {
			$error++;
		}
		if ($this->fk_validator >= 0) {
			$sql .= " fk_validator = '".$this->db->escape($this->fk_validator)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = '".$this->db->escape($this->fk_user_valid)."',";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = '".$this->db->escape($this->fk_user_refuse)."',";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = '".$this->db->escape($this->fk_user_cancel)."',";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::approve1", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAYCUSTOM_APPROVE1', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::approve ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Approve2 leave request
	 *
	 *  @param	User	$user        	User that approve
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	public function approve2($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";

		$sql .= " description= '".$this->db->escape($this->description)."',";

		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		$sql .= " halfday = ".((int) $this->halfday).",";
		if (!empty($this->statut) && is_numeric($this->statut)) {
			$sql .= " statut = ".self::STATUS_APPROVED2.",";
		} else {
			$error++;
		}
		if ($this->fk_validator >= 0) {
			$sql .= " fk_validator = '".$this->db->escape($this->fk_validator)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = '".$this->db->escape($this->fk_user_valid)."',";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = '".$this->db->escape($this->fk_user_refuse)."',";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = '".$this->db->escape($this->fk_user_cancel)."',";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::approve", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAYCUSTOM_APPROVE2', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::approve2 ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";

		$sql .= " description= '".$this->db->escape($this->description)."',";

		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		$sql .= " halfday = ".$this->halfday.",";
		if (!empty($this->statut) && is_numeric($this->statut)) {
			$sql .= " statut = ".$this->statut.",";
		} else {
			$error++;
		}
		if ($this->fk_validator >= 0) {
			$sql .= " fk_validator = '".$this->db->escape($this->fk_validator)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = '".$this->db->escape($this->fk_user_valid)."',";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = '".$this->db->escape($this->fk_user_refuse)."',";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = '".$this->db->escape($this->fk_user_cancel)."',";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}

		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAYCUSTOM_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *   Delete object in database
	 *
	 *	 @param		User	$user        	User that delete
	 *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	 @return	int						<0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."holiday";
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if(!$error) {
			// Delete cascade first
			if (is_array($this->childtablesoncascade) && !empty($this->childtablesoncascade)) {
				foreach ($this->childtablesoncascade as $table) {
					$deleteFromObject = explode(':', $table);
					if (count($deleteFromObject) >= 2) {
						$className = str_replace('@', '', $deleteFromObject[0]);
						$filePath = $deleteFromObject[1];
						$columnName = $deleteFromObject[2];
						$TMoreSQL = array();
						$more_sql = $deleteFromObject[3];
						if (!empty($more_sql)) {
							$TMoreSQL['customsql'] = $more_sql;
						}
						if (dol_include_once($filePath)) {
							$childObject = new $className($this->db);
							if (method_exists($childObject, 'deleteByParentField')) {
								$result = $childObject->deleteByParentField($this->id, $columnName, $TMoreSQL);
								if ($result < 0) {
									$error++;
									$this->errors[] = $childObject->error;
									break;
								}
							} else {
								$error++;
								$this->errors[] = "You defined a cascade delete on an object $childObject but there is no method deleteByParentField for it";
								break;
							}
						} else {
							$error++;
							$this->errors[] = 'Cannot include child class file '.$filePath;
							break;
						}
					} else {
						// Delete record in child table
						$sql = "DELETE FROM ".$this->db->prefix().$table." WHERE ".$this->fk_element." = ".((int) $this->id);

						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							$this->error = $this->db->lasterror();
							$this->errors[] = $this->error;
							break;
						}
					}
				}
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAYCUSTOM_DELETE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
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
	}

	/**
	 *	Check if a user is on holiday (partially or completely) into a period.
	 *  This function can be used to avoid to have 2 leave requests on same period for example.
	 *  Warning: It consumes a lot of memory because it load in ->holiday all holiday of a dedicated user at each call.
	 *
	 *  @param 	int		$fk_user		Id user
	 *  @param 	integer	$dateStart		Start date of period to check
	 *  @param 	integer	$dateEnd		End date of period to check
	 *  @param  int     $halfday        Tag to define how start and end the period to check:
	 *                                  0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning
	 * 	@return boolean					False = New range overlap an existing holiday, True = no overlapping (is never on holiday during checked period).
	 *  @see verifDateHolidayForTimestamp()
	 */
	public function verifDateHolidayCP($fk_user, $dateStart, $dateEnd, $halfday = 0)
	{
		$this->fetchByUser($fk_user, '', '');

		foreach ($this->holiday as $infos_CP) {
			if ($infos_CP['statut'] == 4) {
				continue; // ignore not validated holidays
			}
			if ($infos_CP['statut'] == 5) {
				continue; // ignore not validated holidays
			}
			/*
			 var_dump("--");
			 var_dump("old: ".dol_print_date($infos_CP['date_debut'],'dayhour').' '.dol_print_date($infos_CP['date_fin'],'dayhour').' '.$infos_CP['halfday']);
			 var_dump("new: ".dol_print_date($dateStart,'dayhour').' '.dol_print_date($dateEnd,'dayhour').' '.$halfday);
			 */

			if ($halfday == 0) {
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					return false;
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					return false;
				}
				if ($dateStart <= $infos_CP['date_debut'] && $dateEnd >= $infos_CP['date_fin']) {
					return false;
				}
			} elseif ($halfday == -1) {
				// new start afternoon, new end afternoon
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateStart < $dateEnd) {
						return false;
					}
					if ($dateEnd < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				} 
			} elseif ($halfday == 1) {
				// new start morning, new end morning
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $dateEnd) {
						return false;
					}
					if ($dateStart > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateEnd > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
			} elseif ($halfday == 2) {
				// new start afternoon, new end morning
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateEnd > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
			} else {
				dol_print_error('', 'Bad value of parameter halfday when calling function verifDateHolidayCP');
			}
		}

		return true;
	}


	/**
	 *	Check that a user is not on holiday for a particular timestamp. Can check approved leave requests and not into public holidays of company.
	 *
	 * 	@param 	int			$fk_user				Id user
	 *  @param	integer	    $timestamp				Time stamp date for a day (YYYY-MM-DD) without hours  (= 12:00AM in english and not 12:00PM that is 12:00)
	 *  @param	string		$status					Filter on holiday status. '-1' = no filter.
	 * 	@return array								array('morning'=> ,'afternoon'=> ), Boolean is true if user is available for day timestamp.
	 *  @see verifDateHolidayCP()
	 */
	public function verifDateHolidayForTimestamp($fk_user, $timestamp, $status = '-1')
	{
		global $langs, $conf;

		$isavailablemorning = true;
		$isavailableafternoon = true;

		// Check into leave requests
		$sql = "SELECT cp.rowid, cp.date_debut as date_start, cp.date_fin as date_end, cp.halfday, cp.statut";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = ".(int) $fk_user;
		$sql .= " AND cp.date_debut <= '".$this->db->idate($timestamp)."' AND cp.date_fin >= '".$this->db->idate($timestamp)."'";
		if ($status != '-1') {
			$sql .= " AND cp.statut IN (".$this->db->sanitize($status).")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql); // Note, we can have 2 records if on is morning and the other one is afternoon
			if ($num_rows > 0) {
				$arrayofrecord = array();
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);

					// Note: $obj->halfday is  0:Full days, 2:Sart afternoon end morning, -1:Start afternoon, 1:End morning
					$arrayofrecord[$obj->rowid] = array('date_start'=>$this->db->jdate($obj->date_start), 'date_end'=>$this->db->jdate($obj->date_end), 'halfday'=>$obj->halfday);
					$i++;
				}

				// We found a record, user is on holiday by default, so is not available is true.
				$isavailablemorning = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_start'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_start'] && $record['halfday'] == -1) {
						continue;
					}
					$isavailablemorning = false;
					break;
				}
				$isavailableafternoon = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_end'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_end'] && $record['halfday'] == 1) {
						continue;
					}
					$isavailableafternoon = false;
					break;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		$result = array('morning'=>$isavailablemorning, 'afternoon'=>$isavailableafternoon);
		if (!$isavailablemorning) {
			$result['morning_reason'] = 'leave_request';
		}
		if (!$isavailableafternoon) {
			$result['afternoon_reason'] = 'leave_request';
		}
		return $result;
	}


	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param	int			$withpicto					0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
	 *  @param  int     	$save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param  int         $notooltip					1=Disable tooltip
	 *	@return	string									String with URL
	 */
	public function getNomUrl($withpicto = 0, $save_lastsearch_value = -1, $notooltip = 0)
	{
		global $langs;

		$result = '';

		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Holiday").'</u>';
		if (isset($this->statut)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = DOL_URL_ROOT.'/custom/holidaycustom/card.php?id='.$this->id;

		//if ($option != 'nolink')
		//{
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}
		//}

		$linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}


	/**
	 *	Returns the label status
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode, $this->date_debut);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Returns the label of a status
	 *
	 *	@param      int		$status     Id status
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @param		integer	$startdate	Date holiday should start
	 *	@return     string      		Label
	 */
	public function LibStatut($status, $mode = 0, $startdate = '')
	{
		// phpcs:enable
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('DraftCP');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ToReviewCP_custom');
			$this->labelStatus[self::STATUS_APPROVED1] = $langs->transnoentitiesnoconv('Approved1CP');
			$this->labelStatus[self::STATUS_APPROVED2] = $langs->transnoentitiesnoconv('ApprovedCP');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('CancelCP');
			$this->labelStatus[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('RefuseCP');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('DraftCP');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ToReviewCP_custom');
			$this->labelStatusShort[self::STATUS_APPROVED1] = $langs->transnoentitiesnoconv('Approved1CP');
			$this->labelStatusShort[self::STATUS_APPROVED2] = $langs->transnoentitiesnoconv('ApprovedCP');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('CancelCP');
			$this->labelStatusShort[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('RefuseCP');
		}

		$params = array();
		$statusType = 'status6';
		if (!empty($startdate) && $startdate >= dol_now()) {		// If not yet passed, we use a green "in live" color
			$statusType = 'status4';
			$params = array('tooltip'=>$this->labelStatus[$status].' - '.$langs->trans("Forthcoming"));
		}
		if ($status == self::STATUS_DRAFT) {
			$statusType = 'status0';
		}
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status1';
		}
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status5';
		}
		if ($status == self::STATUS_REFUSED) {
			$statusType = 'status5';
		}
		if ($status == self::STATUS_APPROVED1) {
			$statusType = 'status7';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode, '', $params);
	}

	public function getArrayStatut() {
		global $langs; 

		$labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('DraftCP');
		$labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ToReviewCP_custom');
		$labelStatus[self::STATUS_APPROVED1] = $langs->transnoentitiesnoconv('Approved1CP');
		$labelStatus[self::STATUS_APPROVED2] = $langs->transnoentitiesnoconv('ApprovedCP');
		$labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('CancelCP');
		$labelStatus[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('RefuseCP');

		return $labelStatus;
	}


	/**
	 *   Affiche un select HTML des statuts de congés payés
	 *
	 *   @param 	int		$selected   	Id of preselected status
	 *   @param		string	$htmlname		Name of HTML select field
	 *   @param		string	$morecss		More CSS on select component
	 *   @return    string					Show select of status
	 */
	public function selectStatutCP($selected = '', $htmlname = 'select_statut', $morecss = 'minwidth125')
	{
		global $langs;

		// Liste des statuts
		$name = array('DraftCP', 'ToReviewCP_custom', 'ApprovedCP', 'CancelCP', 'RefuseCP', 'Approved1CP');
		$nb = count($name) + 1;

		// Select HTML
		$out = '<select name="'.$htmlname.'" id="'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'">'."\n";
		$out .= '<option value="-1">&nbsp;</option>'."\n";

		// Boucle des statuts
		for ($i = 1; $i < $nb; $i++) {
			if ($i == $selected) {
				$out .= '<option value="'.$i.'" selected>'.$langs->trans($name[$i - 1]).'</option>'."\n";
			} else {
				$out .= '<option value="'.$i.'">'.$langs->trans($name[$i - 1]).'</option>'."\n";
			}
		}

		$out .= '</select>'."\n";
		$out .= ajax_combobox($htmlname);

		print $out;
	}

	/**
	 *  Met à jour une option du module Holiday Payés
	 *
	 *  @param	string	$name       name du paramètre de configuration
	 *  @param	string	$value      vrai si mise à jour OK sinon faux
	 *  @return boolean				ok or ko
	 */
	public function updateConfCP($name, $value)
	{

		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
		$sql .= " value = '".$this->db->escape($value)."'";
		$sql .= " WHERE name = '".$this->db->escape($name)."'";

		dol_syslog(get_class($this).'::updateConfCP name='.$name.'', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return true;
		}

		return false;
	}

	/**
	 *  Return value of a conf parameterfor leave module
	 *  TODO Move this into llx_const table
	 *
	 *  @param	string	$name                 Name of parameter
	 *  @param  string  $createifnotfound     'stringvalue'=Create entry with string value if not found. For example 'YYYYMMDDHHMMSS'.
	 *  @return string      		          Value of parameter. Example: 'YYYYMMDDHHMMSS' or < 0 if error
	 */
	public function getConfCP($name, $createifnotfound = '')
	{
		$sql = "SELECT value";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_config";
		$sql .= " WHERE name = '".$this->db->escape($name)."'";

		dol_syslog(get_class($this).'::getConfCP name='.$name.' createifnotfound='.$createifnotfound, LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			$obj = $this->db->fetch_object($result);
			// Return value
			if (empty($obj)) {
				if ($createifnotfound) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_config(name, value)";
					$sql .= " VALUES('".$this->db->escape($name)."', '".$this->db->escape($createifnotfound)."')";
					$result = $this->db->query($sql);
					if ($result) {
						return $createifnotfound;
					} else {
						$this->error = $this->db->lasterror();
						return -2;
					}
				} else {
					return '';
				}
			} else {
				return $obj->value;
			}
		} else {
			// Erreur SQL
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Met à jour le timestamp de la dernière mise à jour du solde des CP
	 *
	 *	@param		int		$userID		Id of user
	 *	@param		float	$nbHoliday	Nb of days
	 *  @param		int		$fk_type	Type of vacation
	 *  @return     int					0=Nothing done, 1=OK, -1=KO
	 */
	public function updateSoldeCP($userID = '', $nbHoliday = '', $fk_type = '')
	{
		global $user, $langs;

		$error = 0;

		if (empty($userID) && empty($nbHoliday) && empty($fk_type)) {
			$langs->load("holiday");

			// Si mise à jour pour tout le monde en début de mois
			$now = dol_now();

			$month = date('m', $now);
			$newdateforlastupdate = dol_print_date($now, '%Y%m%d%H%M%S');

			// Get month of last update
			$lastUpdate = $this->getConfCP('lastUpdate', $newdateforlastupdate);
			$monthLastUpdate = $lastUpdate[4].$lastUpdate[5];
			//print 'month: '.$month.' lastUpdate:'.$lastUpdate.' monthLastUpdate:'.$monthLastUpdate;exit;

			// If month date is not same than the one of last update (the one we saved in database), then we update the timestamp and balance of each open user.
			if ($month != $monthLastUpdate) {
				$this->db->begin();

				$users = $this->fetchUsers(false, false, ' AND u.statut > 0');
				$nbUser = count($users);

				$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
				$sql .= " value = '".$this->db->escape($newdateforlastupdate)."'";
				$sql .= " WHERE name = 'lastUpdate'";
				$result = $this->db->query($sql);

				$typeleaves = $this->getTypes(-1, 1);

				// Update each user counter
				foreach ($users as $userCounter) {
					$nbDaysToAdd = (isset($typeleaves[$userCounter['type']]['newbymonth']) ? $typeleaves[$userCounter['type']]['newbymonth'] : 0);
					if (empty($nbDaysToAdd)) {
						continue;
					}

					dol_syslog("We update leave type id ".$userCounter['type']." for user id ".$userCounter['rowid'], LOG_DEBUG);

					$nowHoliday = $userCounter['nb_holiday'];
					$newSolde = $nowHoliday + $nbDaysToAdd;

					// We add a log for each user
					$this->addLogCP($user->id, $userCounter['rowid'], $langs->trans('HolidaysMonthlyUpdate'), $newSolde, $userCounter['type']);

					$result = $this->updateSoldeCP($userCounter['rowid'], $newSolde, $userCounter['type'], $langs->trans('HolidaysMonthlyUpdate'));

					if ($result < 0) {
						$error++;
						break;
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

			return 0;
		} else {
			// Mise à jour pour un utilisateur
			$nbHoliday = price2num($nbHoliday, 5);

			$sql = "SELECT nb_holiday FROM ".MAIN_DB_PREFIX."holiday_users";
			$sql .= " WHERE fk_user = ".(int) $userID." AND fk_type = ".(int) $fk_type;
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				if ($num > 0) {
					// Update for user
					$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_users SET";
					$sql .= " nb_holiday = ".((float) $nbHoliday);
					$sql .= " WHERE fk_user = ".(int) $userID." AND fk_type = ".(int) $fk_type;
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				} else {
					// Insert for user
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users(nb_holiday, fk_user, fk_type) VALUES (";
					$sql .= ((float) $nbHoliday);
					$sql .= ", ".(int) $userID.", ".(int) $fk_type.")";
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				}
			} else {
				$this->errors[] = $this->db->lasterror();
				$error++;
			}

			if (!$error) {
				return 1;
			} else {
				return -1;
			}
		}
	}

	/**
	 *	Retourne un checked si vrai
	 *
	 *  @param	string	$name       name du paramètre de configuration
	 *  @return string      		retourne checked si > 0
	 */
	public function getCheckOption($name)
	{

		$sql = "SELECT value";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_config";
		$sql .= " WHERE name = '".$this->db->escape($name)."'";

		$result = $this->db->query($sql);

		if ($result) {
			$obj = $this->db->fetch_object($result);

			// Si la valeur est 1 on retourne checked
			if ($obj->value) {
				return 'checked';
			}
		}
	}


	/**
	 *  Créer les entrées pour chaque utilisateur au moment de la configuration
	 *
	 *  @param	boolean		$single		Single
	 *  @param	int			$userid		Id user
	 *  @return void
	 */
	public function createCPusers($single = false, $userid = '')
	{
		// do we have to add balance for all users ?
		if (!$single) {
			dol_syslog(get_class($this).'::createCPusers');
			$arrayofusers = $this->fetchUsers(false, true);

			foreach ($arrayofusers as $users) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users";
				$sql .= " (fk_user, nb_holiday)";
				$sql .= " VALUES (".((int) $users['rowid'])."', '0')";

				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_print_error($this->db);
				}
			}
		} else {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users";
			$sql .= " (fk_user, nb_holiday)";
			$sql .= " VALUES (".((int) $userid)."', '0')";

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
			}
		}
	}

	/**
	 *  Supprime un utilisateur du module Congés Payés
	 *
	 *  @param	int		$user_id        ID de l'utilisateur à supprimer
	 *  @return boolean      			Vrai si pas d'erreur, faut si Erreur
	 */
	public function deleteCPuser($user_id)
	{

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."holiday_users";
		$sql .= " WHERE fk_user = ".((int) $user_id);

		$this->db->query($sql);
	}


	/**
	 *  Return balance of holiday for one user
	 *
	 *  @param	int		$user_id    ID de l'utilisateur
	 *  @param	int		$fk_type	Filter on type
	 *  @return float        		Retourne le solde de congés payés de l'utilisateur
	 */
	public function getCPforUser($user_id, $fk_type = 0)
	{
		$sql = "SELECT nb_holiday";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users";
		$sql .= " WHERE fk_user = ".(int) $user_id;
		if ($fk_type > 0) {
			$sql .= " AND fk_type = ".(int) $fk_type;
		}

		dol_syslog(get_class($this).'::getCPforUser user_id='.$user_id.' type_id='.$fk_type, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			//return number_format($obj->nb_holiday,2);
			if ($obj) {
				return $obj->nb_holiday;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 *    Get list of Users or list of vacation balance.
	 *
	 *    @param      boolean			$stringlist	    If true return a string list of id. If false, return an array with detail.
	 *    @param      boolean   		$type			If true, read Dolibarr user list, if false, return vacation balance list.
	 *    @param      string            $filters        Filters. Warning: This must not contains data from user input.
	 *    @return     array|string|int      			Return an array
	 */
	public function fetchUsers($stringlist = true, $type = true, $filters = '')
	{
		global $conf;

		dol_syslog(get_class($this)."::fetchUsers", LOG_DEBUG);

		if ($stringlist) {
			if ($type) {
				// If user of Dolibarr
				$sql = "SELECT";
				if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$sql .= " DISTINCT";
				}
				$sql .= " u.rowid";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

				if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
					$sql .= " WHERE ((ug.fk_user = u.rowid";
					$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
					$sql .= " OR u.entity = 0)"; // Show always superadmin
				} else {
					$sql .= " WHERE u.entity IN (".getEntity('user').")";
				}
				$sql .= " AND u.statut > 0";
				$sql .= " AND u.employee = 1"; // We only want employee users for holidays
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$num = $this->db->num_rows($resql);
					$stringlist = '';

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if ($i == 0) {
							$stringlist .= $obj->rowid;
						} else {
							$stringlist .= ', '.$obj->rowid;
						}

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $stringlist;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			} else {
				// We want only list of vacation balance for user ids
				$sql = "SELECT DISTINCT cpu.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users as cpu, ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE cpu.fk_user = u.rowid";
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$num = $this->db->num_rows($resql);
					$stringlist = '';

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if ($i == 0) {
							$stringlist .= $obj->fk_user;
						} else {
							$stringlist .= ', '.$obj->fk_user;
						}

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $stringlist;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			}
		} else {
			// Si faux donc return array
			// List for Dolibarr users
			if ($type) {
				// If we need users of Dolibarr
				$sql = "SELECT";
				if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$sql .= " DISTINCT";
				}
				$sql .= " u.rowid, u.lastname, u.firstname, u.gender, u.photo, u.employee, u.statut, u.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

				if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
					$sql .= " WHERE ((ug.fk_user = u.rowid";
					$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
					$sql .= " OR u.entity = 0)"; // Show always superadmin
				} else {
					$sql .= " WHERE u.entity IN (".getEntity('user').")";
				}

				$sql .= " AND u.statut > 0";
				$sql .= " AND u.employee = 1"; // We only want employee users for holidays
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$tab_result = $this->holiday;
					$num = $this->db->num_rows($resql);

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						$tab_result[$i]['rowid'] = $obj->rowid; // rowid of user
						$tab_result[$i]['name'] = $obj->lastname; // deprecated
						$tab_result[$i]['lastname'] = $obj->lastname;
						$tab_result[$i]['firstname'] = $obj->firstname;
						$tab_result[$i]['gender'] = $obj->gender;
						$tab_result[$i]['status'] = $obj->statut;
						$tab_result[$i]['employee'] = $obj->employee;
						$tab_result[$i]['photo'] = $obj->photo;
						$tab_result[$i]['fk_user'] = $obj->fk_user; // rowid of manager
						//$tab_result[$i]['type'] = $obj->type;
						//$tab_result[$i]['nb_holiday'] = $obj->nb_holiday;

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $tab_result;
				} else {
					// Erreur SQL
					$this->errors[] = "Error ".$this->db->lasterror();
					return -1;
				}
			} else {
				// List of vacation balance users
				$sql = "SELECT cpu.fk_type, cpu.nb_holiday, u.rowid, u.lastname, u.firstname, u.gender, u.photo, u.employee, u.statut, u.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users as cpu, ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE cpu.fk_user = u.rowid";
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$tab_result = $this->holiday;
					$num = $this->db->num_rows($resql);

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						$tab_result[$i]['rowid'] = $obj->rowid; // rowid of user
						$tab_result[$i]['name'] = $obj->lastname; // deprecated
						$tab_result[$i]['lastname'] = $obj->lastname;
						$tab_result[$i]['firstname'] = $obj->firstname;
						$tab_result[$i]['gender'] = $obj->gender;
						$tab_result[$i]['status'] = $obj->statut;
						$tab_result[$i]['employee'] = $obj->employee;
						$tab_result[$i]['photo'] = $obj->photo;
						$tab_result[$i]['fk_user'] = $obj->fk_user; // rowid of manager

						$tab_result[$i]['type'] = $obj->fk_type;
						$tab_result[$i]['nb_holiday'] = $obj->nb_holiday;

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $tab_result;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			}
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of people with permission to validate leave requests.
	 * Search for permission "approve leave requests"
	 *
	 * @return  array       Array of user ids
	 */
	public function fetch_users_approver_holiday()
	{
		// phpcs:enable
		$users_validator = array();

		$sql = "SELECT DISTINCT ur.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ur.fk_id = rd.id and rd.module = 'holidaycustom' AND rd.perms = 'approve'"; // Permission 'Approve';
		$sql .= "UNION";
		$sql .= " SELECT DISTINCT ugu.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_user as ugu, ".MAIN_DB_PREFIX."usergroup_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ugu.fk_usergroup = ur.fk_usergroup AND ur.fk_id = rd.id and rd.module = 'holidaycustom' AND rd.perms = 'approve'"; // Permission 'Approve';
		//print $sql;

		dol_syslog(get_class($this)."::fetch_users_approver_holiday sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$num_rows = $this->db->num_rows($result); $i = 0;
			while ($i < $num_rows) {
				$objp = $this->db->fetch_object($result);
				array_push($users_validator, $objp->fk_user);
				$i++;
			}
			return $users_validator;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_users_approver_holiday  Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Compte le nombre d'utilisateur actifs dans Dolibarr
	 *
	 *  @return     int      retourne le nombre d'utilisateur
	 */
	public function countActiveUsers()
	{
		$sql = "SELECT count(u.rowid) as compteur";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.statut > 0";

		$result = $this->db->query($sql);
		$objet = $this->db->fetch_object($result);

		return $objet->compteur;
	}
	/**
	 *	Compte le nombre d'utilisateur actifs dans Dolibarr sans CP
	 *
	 *  @return     int      retourne le nombre d'utilisateur
	 */
	public function countActiveUsersWithoutCP()
	{

		$sql = "SELECT count(u.rowid) as compteur";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u LEFT OUTER JOIN ".MAIN_DB_PREFIX."holiday_users hu ON (hu.fk_user=u.rowid)";
		$sql .= " WHERE u.statut > 0 AND hu.fk_user IS NULL";

		$result = $this->db->query($sql);
		$objet = $this->db->fetch_object($result);

		return $objet->compteur;
	}

	/**
	 *  Compare le nombre d'utilisateur actif de Dolibarr à celui des utilisateurs des congés payés
	 *
	 *  @param    int	$userDolibarrWithoutCP	Number of active users in Dolibarr without holidays
	 *  @param    int	$userCP    				Number of active users into table of holidays
	 *  @return   int							<0 if KO, >0 if OK
	 */
	public function verifNbUsers($userDolibarrWithoutCP, $userCP)
	{
		if (empty($userCP)) {
			$userCP = 0;
		}
		dol_syslog(get_class($this).'::verifNbUsers userDolibarr='.$userDolibarrWithoutCP.' userCP='.$userCP);
		return 1;
	}


	/**
	 * addLogCP
	 *
	 * @param 	int		$fk_user_action		Id user creation
	 * @param 	int		$fk_user_update		Id user update
	 * @param 	string	$label				Label (Example: 'Leave', 'Manual update', 'Leave request cancelation'...)
	 * @param 	int		$new_solde			New value
	 * @param	int		$fk_type			Type of vacation
	 * @return 	int							Id of record added, 0 if nothing done, < 0 if KO
	 */
	public function addLogCP($fk_user_action, $fk_user_update, $label, $new_solde, $fk_type)
	{
		global $conf, $langs;

		$error = 0;

		$prev_solde = price2num($this->getCPforUser($fk_user_update, $fk_type), 5);
		$new_solde = price2num($new_solde, 5);
		//print "$prev_solde == $new_solde";

		if ($prev_solde == $new_solde) {
			return 0;
		}

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_logs (";
		$sql .= "date_action,";
		$sql .= "fk_user_action,";
		$sql .= "fk_user_update,";
		$sql .= "type_action,";
		$sql .= "prev_solde,";
		$sql .= "new_solde,";
		$sql .= "fk_type";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->idate(dol_now())."',";
		$sql .= " ".((int) $fk_user_action).",";
		$sql .= " ".((int) $fk_user_update).",";
		$sql .= " '".$this->db->escape($label)."',";
		$sql .= " ".((float) $prev_solde).",";
		$sql .= " ".((float) $new_solde).",";
		$sql .= " ".((int) $fk_type);
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->optRowid = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday_logs");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::addLogCP ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->optRowid;
		}
	}

	/**
	 *  Liste le log des congés payés
	 *
	 *  @param	string	$order      Filtrage par ordre
	 *  @param  string	$filter     Filtre de séléction
	 *  @return int         		-1 si erreur, 1 si OK et 2 si pas de résultat
	 */
	public function fetchLog($order, $filter)
	{
		$sql = "SELECT";
		$sql .= " cpl.rowid,";
		$sql .= " cpl.date_action,";
		$sql .= " cpl.fk_user_action,";
		$sql .= " cpl.fk_user_update,";
		$sql .= " cpl.type_action,";
		$sql .= " cpl.prev_solde,";
		$sql .= " cpl.new_solde,";
		$sql .= " cpl.fk_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_logs as cpl";
		$sql .= " WHERE cpl.rowid > 0"; // To avoid error with other search and criteria

		// Filtrage de séléction
		if (!empty($filter)) {
			$sql .= " ".$filter;
		}

		// Ordre d'affichage
		if (!empty($order)) {
			$sql .= " ".$order;
		}

		dol_syslog(get_class($this)."::fetchLog", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// Si pas d'erreur SQL
		if ($resql) {
			$i = 0;
			$tab_result = $this->logs;
			$num = $this->db->num_rows($resql);

			// Si pas d'enregistrement
			if (!$num) {
				return 2;
			}

			// On liste les résultats et on les ajoutent dans le tableau
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['date_action'] = $obj->date_action;
				$tab_result[$i]['fk_user_action'] = $obj->fk_user_action;
				$tab_result[$i]['fk_user_update'] = $obj->fk_user_update;
				$tab_result[$i]['type_action'] = $obj->type_action;
				$tab_result[$i]['prev_solde'] = $obj->prev_solde;
				$tab_result[$i]['new_solde'] = $obj->new_solde;
				$tab_result[$i]['fk_type'] = $obj->fk_type;

				$i++;
			}
			// Retourne 1 et ajoute le tableau à la variable
			$this->logs = $tab_result;
			return 1;
		} else {
			// Erreur SQL
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Return array with list of types
	 *
	 *  @param		int		$active		Status of type. -1 = Both
	 *  @param		int		$affect		Filter on affect (a request will change sold or not). -1 = Both
	 *  @return     array	    		Return array with list of types
	 */
	public function getTypes($active = -1, $affect = -1)
	{
		global $mysoc;

		$sql = "SELECT rowid, code, label, affect, delay, newbymonth";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
		$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
		if ($active >= 0) {
			$sql .= " AND active = ".((int) $active);
		}
		if ($affect >= 0) {
			$sql .= " AND affect = ".((int) $affect);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				while ($obj = $this->db->fetch_object($result)) {
					$types[$obj->rowid] = array('rowid'=> $obj->rowid, 'code'=> $obj->code, 'label'=>$obj->label, 'affect'=>$obj->affect, 'delay'=>$obj->delay, 'newbymonth'=>$obj->newbymonth);
				}

				return $types;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *  Return array with list of types (sans les types de CP ajoutés)
	 *
	 *  @param		int		$active		Status of type. -1 = Both
	 *  @param		int		$affect		Filter on affect (a request will change sold or not). -1 = Both
	 *  @return     array	    		Return array with list of types
	 */
	public function getTypesNoCP($active = -1, $affect = -1)
	{
		global $mysoc;

		$sql = "SELECT rowid, code, label, affect, delay, newbymonth";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
		$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
		if ($active >= 0) {
			$sql .= " AND active = ".((int) $active);
		}
		if ($affect >= 0) {
			$sql .= " AND affect = ".((int) $affect);
		}
		$sql .= " AND code NOT IN ('CP_N-1_ACQUIS', 'CP_N-1_PRIS', 'CP_N_ACQUIS', 'CP_N_PRIS', 'CP_FRAC_ACQUIS', 'CP_FRAC_PRIS', 'CP_ANC_ACQUIS', 'CP_ANC_PRIS', 'RTT_ACQUIS', 'RTT_PRIS')";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				while ($obj = $this->db->fetch_object($result)) {
					$types[$obj->rowid] = array('rowid'=> $obj->rowid, 'code'=> $obj->code, 'label'=>$obj->label, 'affect'=>$obj->affect, 'delay'=>$obj->delay, 'newbymonth'=>$obj->newbymonth);
				}

				return $types;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *  Return type
	 *
	 *  @param		int		$active		Status of type. -1 = Both
	 *  @param		int		$code		Filter Code
	 *  @return     array	    		Return type
	 */
	public function getTypesCP($active = -1, $code = '')
	{
		global $mysoc;

		$sql = "SELECT rowid, code, label, affect, delay, newbymonth";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
		$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
		if ($active >= 0) {
			$sql .= " AND active = ".((int) $active);
		}
		if (!empty($code)) {
			$sql .= " AND code = '".$code."'";
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				while ($obj = $this->db->fetch_object($result)) {
					$type = array('rowid'=> $obj->rowid, 'code'=> $obj->code, 'label'=>$obj->label, 'affect'=>$obj->affect, 'delay'=>$obj->delay, 'newbymonth'=>$obj->newbymonth);
				}

				return $type;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *  Return array with list of types (uniquement les types de CP)
	 *
	 *  @return     array	    		Return array with list of types
	 */
	public function getTypesALLCP()
	{
		global $mysoc;

		$sql = "SELECT rowid, code, label, affect, delay, newbymonth";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
		$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
		$sql .= " AND code IN ('ACP', 'CP_N-1_ACQUIS', 'CP_N-1_PRIS', 'CP_N_ACQUIS', 'CP_N_PRIS', 'CP_FRAC_ACQUIS', 'CP_FRAC_PRIS', 'CP_ANC_ACQUIS', 'CP_ANC_PRIS', 'RTT_ACQUIS', 'RTT_PRIS')";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				while ($obj = $this->db->fetch_object($result)) {
					$types[$obj->rowid] = array('rowid'=> $obj->rowid, 'code'=> $obj->code, 'label'=>$obj->label, 'affect'=>$obj->affect, 'delay'=>$obj->delay, 'newbymonth'=>$obj->newbymonth);
				}

				return $types;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *  Return type
	 *
	 *  @param		int		$id			Filter ID
	 *  @return     array	    		Return type
	 */
	public function getTypeWithID($id)
	{
		global $mysoc;

		if($id > 0){
			$sql = "SELECT rowid, code, label, affect, delay, newbymonth";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
			$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
			if ($id >= 0) {
				$sql .= " AND rowid = ".((int) $id);
			}
		}
		else {
			dol_print_error($this->db);
		};

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				while ($obj = $this->db->fetch_object($result)) {
					$type = array('rowid'=> $obj->rowid, 'code'=> $obj->code, 'label'=>$obj->label, 'affect'=>$obj->affect, 'delay'=>$obj->delay, 'newbymonth'=>$obj->newbymonth);
				}

				return $type;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *  Load information on object
	 *
	 *  @param  int     $id      Id of object
	 *  @return void
	 */
	public function info($id)
	{
		global $conf;

		$sql = "SELECT f.rowid, f.statut as status,";
		$sql .= " f.date_create as datec,";
		$sql .= " f.tms as date_modification,";
		$sql .= " f.date_valid as datev,";
		//$sql .= " f.date_approve as datea,";
		$sql .= " f.date_refuse as dater,";
		$sql .= " f.fk_user_create as fk_user_creation,";
		$sql .= " f.fk_user_modif as fk_user_modification,";
		$sql .= " f.fk_user_valid as fk_user_approve_done,";
		$sql .= " f.fk_validator as fk_user_approve_expected,";
		$sql .= " f.fk_user_refuse as fk_user_refuse";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as f";
		$sql .= " WHERE f.rowid = ".((int) $id);
		$sql .= " AND f.entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->date_validation = $this->db->jdate($obj->datev);
				$this->date_approbation = $this->db->jdate($obj->datea);

				$cuser = new User($this->db);
				$cuser->fetch($obj->fk_user_author);
				$this->user_creation = $cuser;

				if ($obj->fk_user_creation) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creation);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}
				if ($obj->fk_user_modification) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modification);
					$this->user_modification = $muser;
				}

				if ($obj->status == Holiday::STATUS_APPROVED2 || $obj->status == Holiday::STATUS_CANCELED) {
					if ($obj->fk_user_approve_done) {
						$auser = new User($this->db);
						$auser->fetch($obj->fk_user_approve_done);
						$this->user_approve = $auser;
					}
				} else {
					if ($obj->fk_user_approve_expected) {
						$auser = new User($this->db);
						$auser->fetch($obj->fk_user_approve_expected);
						$this->user_approve = $auser;
					}
				}
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	public function initAsSpecimen()
	{
		global $user, $langs;

		// Initialise parameters
		$this->id = 0;
		$this->specimen = 1;

		$this->fk_user = $user->id;
		$this->description = 'SPECIMEN description';
		$this->date_debut = dol_now();
		$this->date_fin = dol_now() + (24 * 3600);
		$this->date_valid = dol_now();
		$this->fk_validator = $user->id;
		$this->halfday = 0;
		$this->fk_type = 1;
		$this->statut = Holiday::STATUS_VALIDATED;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load this->nb for dashboard
	 *
	 *      @return     int         <0 if KO, >0 if OK
	 */
	public function load_state_board()
	{
		// phpcs:enable
		global $user;

		$this->nb = array();

		$sql = "SELECT count(h.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.statut > 1";
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		if (empty($user->rights->expensereport->readall)) {
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (h.fk_user IN (".$this->db->sanitize(join(',', $userchildids)).")";
			$sql .= " OR h.fk_validator IN (".$this->db->sanitize(join(',', $userchildids))."))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["holidays"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Objet user
	 *      @return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user)
	{
		// phpcs:enable
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		$now = dol_now();

		$sql = "SELECT h.rowid, h.date_debut";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.statut = 2";
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		if (empty($user->rights->expensereport->read_all)) {
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (h.fk_user IN (".$this->db->sanitize(join(',', $userchildids)).")";
			$sql .= " OR h.fk_validator IN (".$this->db->sanitize(join(',', $userchildids))."))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("members");

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->holiday->approve->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("HolidaysToApprove");
			$response->labelShort = $langs->trans("ToApprove");
			$response->url = DOL_URL_ROOT.'custom/holidaycustom/list.php?search_status=2&amp;mainmenu=hrm&amp;leftmenu=holiday';
			$response->img = img_object('', "holiday");

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;

				if ($this->db->jdate($obj->date_debut) < ($now - $conf->holiday->approve->warning_delay)) {
					$response->nbtodolate++;
				}
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	public function showOptionals_custom($extrafields, $mode = 'view', $params = null, $keysuffix = '', $keyprefix = '', $onetrtd = 0, $display_type = 'card', $fk_validator2 = 0)
	{
		global $db, $conf, $langs, $action, $hookmanager, $user;

		$form = new ExtendedFormHoliday($db);

		$out = '';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('showOptionals', $parameters, $this, $action); // Note that $action and $this may have been modified by hook
		if (empty($reshook)) {
			if (key_exists('label', $extrafields->attributes[$this->table_element]) && is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label']) > 0) {
				$out .= "\n";
				$out .= '<!-- commonobject:showOptionals --> ';
				$out .= "\n";

				$extrafields_collapse_num = '';
				$e = 0;
				foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $label) {
					// Show only the key field in params
					if (is_array($params) && array_key_exists('onlykey', $params) && $key != $params['onlykey']) {
						continue;
					}

					if($key == 'fk_validator2' && !$fk_validator2){
						continue;
					}
					else if($fk_validator2 && $key != 'fk_validator2'){
						continue;
					}

					// Test on 'enabled' ('enabled' is different than 'list' = 'visibility')
					$enabled = 1;
					if ($enabled && isset($extrafields->attributes[$this->table_element]['enabled'][$key])) {
						$enabled = dol_eval($extrafields->attributes[$this->table_element]['enabled'][$key], 1, 1, '1');
					}
					if (empty($enabled)) {
						continue;
					}

					$visibility = 1;
					if ($visibility && isset($extrafields->attributes[$this->table_element]['list'][$key])) {
						$visibility = dol_eval($extrafields->attributes[$this->table_element]['list'][$key], 1, 1, '1');
					}

					$perms = 1;
					if ($perms && isset($extrafields->attributes[$this->table_element]['perms'][$key])) {
						$perms = dol_eval($extrafields->attributes[$this->table_element]['perms'][$key], 1, 1, '1');
					}

					if (($mode == 'create') && abs($visibility) != 1 && abs($visibility) != 3) {
						continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list
					} elseif (($mode == 'edit') && abs($visibility) != 1 && abs($visibility) != 3 && abs($visibility) != 4) {
						continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list and <> 4 = not visible at the creation
					} elseif ($mode == 'view' && empty($visibility)) {
						continue;
					}
					if (empty($perms)) {
						continue;
					}
					// Load language if required
					if (!empty($extrafields->attributes[$this->table_element]['langfile'][$key])) {
						$langs->load($extrafields->attributes[$this->table_element]['langfile'][$key]);
					}

					$colspan = 0;
					if (is_array($params) && count($params) > 0 && $display_type=='card') {
						if (array_key_exists('cols', $params)) {
							$colspan = $params['cols'];
						} elseif (array_key_exists('colspan', $params)) {   // For backward compatibility. Use cols instead now.
							$reg = array();
							if (preg_match('/colspan="(\d+)"/', $params['colspan'], $reg)) {
								$colspan = $reg[1];
							} else {
								$colspan = $params['colspan'];
							}
						}
					}
					$colspan = intval($colspan);

					switch ($mode) {
						case "view":
							$value = $this->array_options["options_".$key.$keysuffix]; // Value may be clean or formated later
							break;
						case "create":
						case "edit":
							// We get the value of property found with GETPOST so it takes into account:
							// default values overwrite, restore back to list link, ... (but not 'default value in database' of field)
							$check = 'alphanohtml';
							if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('html', 'text'))) {
								$check = 'restricthtml';
							}
							$getposttemp = GETPOST($keyprefix.'options_'.$key.$keysuffix, $check, 3); // GETPOST can get value from GET, POST or setup of default values overwrite.
							// GETPOST("options_" . $key) can be 'abc' or array(0=>'abc')
							if (is_array($getposttemp) || $getposttemp != '' || GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix)) {
								if (is_array($getposttemp)) {
									// $getposttemp is an array but following code expects a comma separated string
									$value = implode(",", $getposttemp);
								} else {
									$value = $getposttemp;
								}
							} else {
								$value = (!empty($this->array_options["options_".$key]) ? $this->array_options["options_".$key] : ''); // No GET, no POST, no default value, so we take value of object.
							}
							//var_dump($keyprefix.' - '.$key.' - '.$keysuffix.' - '.$keyprefix.'options_'.$key.$keysuffix.' - '.$this->array_options["options_".$key.$keysuffix].' - '.$getposttemp.' - '.$value);
							break;
					}

					// Output value of the current field
					if ($extrafields->attributes[$this->table_element]['type'][$key] == 'separate') {
						$extrafields_collapse_num = '';
						$extrafield_param = $extrafields->attributes[$this->table_element]['param'][$key];
						if (!empty($extrafield_param) && is_array($extrafield_param)) {
							$extrafield_param_list = array_keys($extrafield_param['options']);

							if (count($extrafield_param_list) > 0) {
								$extrafield_collapse_display_value = intval($extrafield_param_list[0]);

								if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
									$extrafields_collapse_num = $extrafields->attributes[$this->table_element]['pos'][$key];
								}
							}
						}

						// if colspan=0 or 1, the second column is not extended, so the separator must be on 2 columns
						$out .= $extrafields->showSeparator($key, $this, ($colspan ? $colspan + 1 : 2), $display_type);
					} else {
						$class = (!empty($extrafields->attributes[$this->table_element]['hidden'][$key]) ? 'hideobject ' : '');
						$csstyle = '';
						if (is_array($params) && count($params) > 0) {
							if (array_key_exists('class', $params)) {
								$class .= $params['class'].' ';
							}
							if (array_key_exists('style', $params)) {
								$csstyle = $params['style'];
							}
						}

						// add html5 elements
						$domData  = ' data-element="extrafield"';
						$domData .= ' data-targetelement="'.$this->element.'"';
						$domData .= ' data-targetid="'.$this->id.'"';

						$html_id = (empty($this->id) ? '' : 'extrarow-'.$this->element.'_'.$key.'_'.$this->id);
						if ($display_type=='card') {
							if (!empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && ($e % 2) == 0) {
								$colspan = 0;
							}

							if ($action == 'selectlines') {
								$colspan++;
							}
						}

						// Convert date into timestamp format (value in memory must be a timestamp)
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('date'))) {
							$datenotinstring = $this->array_options['options_'.$key];
							if (!is_numeric($this->array_options['options_'.$key])) {   // For backward compatibility
								$datenotinstring = $this->db->jdate($datenotinstring);
							}
							$datekey = $keyprefix.'options_'.$key.$keysuffix;
							$value = (GETPOSTISSET($datekey)) ? dol_mktime(12, 0, 0, GETPOST($datekey.'month', 'int', 3), GETPOST($datekey.'day', 'int', 3), GETPOST($datekey.'year', 'int', 3)) : $datenotinstring;
						}
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('datetime'))) {
							$datenotinstring = $this->array_options['options_'.$key];
							if (!is_numeric($this->array_options['options_'.$key])) {   // For backward compatibility
								$datenotinstring = $this->db->jdate($datenotinstring);
							}
							$timekey = $keyprefix.'options_'.$key.$keysuffix;
							$value = (GETPOSTISSET($timekey)) ? dol_mktime(GETPOST($timekey.'hour', 'int', 3), GETPOST($timekey.'min', 'int', 3), GETPOST($timekey.'sec', 'int', 3), GETPOST($timekey.'month', 'int', 3), GETPOST($timekey.'day', 'int', 3), GETPOST($timekey.'year', 'int', 3), 'tzuserrel') : $datenotinstring;
						}
						// Convert float submited string into real php numeric (value in memory must be a php numeric)
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('price', 'double'))) {
							$value = (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) ? price2num($value) : $this->array_options['options_'.$key];
						}

						// HTML, text, select, integer and varchar: take into account default value in database if in create mode
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('html', 'text', 'varchar', 'select', 'int', 'boolean'))) {
							if ($action == 'create') {
								$value = (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) ? $value : $extrafields->attributes[$this->table_element]['default'][$key];
							}
						}

						$labeltoshow = $langs->trans($label);
						$helptoshow = $langs->trans($extrafields->attributes[$this->table_element]['help'][$key]);

						if ($display_type == 'card') {
							$out .= '<tr '.($html_id ? 'id="'.$html_id.'" ' : '').$csstyle.' class="valuefieldcreate '.$class.$this->element.'_extras_'.$key.' trextrafields_collapse'.$extrafields_collapse_num.(!empty($this->id)?'_'.$this->id:'').'" '.$domData.' >';
							if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER) && ($action == 'view' || $action == 'valid' || $action == 'editline')) {
								$out .= '<td></td>';
							}
							$out .= '<td class="wordbreak';
						} elseif ($display_type == 'line') {
							$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').$csstyle.' class="valuefieldlinecreate '.$class.$this->element.'_extras_'.$key.' trextrafields_collapse'.$extrafields_collapse_num.(!empty($this->id)?'_'.$this->id:'').'" '.$domData.' >';
							$out .= '<div style="display: inline-block; padding-right:4px" class="wordbreak';
						}
						//$out .= "titlefield";
						//if (GETPOST('action', 'restricthtml') == 'create') $out.='create';
						// BUG #11554 : For public page, use red dot for required fields, instead of bold label
						$tpl_context = isset($params["tpl_context"]) ? $params["tpl_context"] : "none";
						if ($tpl_context == "public") { // Public page : red dot instead of fieldrequired characters
							$out .= '">';
							if (!empty($extrafields->attributes[$this->table_element]['help'][$key])) {
								$out .= $form->textwithpicto($labeltoshow, $helptoshow);
							} else {
								$out .= $labeltoshow;
							}
							if ($mode != 'view' && !empty($extrafields->attributes[$this->table_element]['required'][$key])) {
								$out .= '&nbsp;<span style="color: red">*</span>';
							}
						} else {
							if ($mode != 'view' && !empty($extrafields->attributes[$this->table_element]['required'][$key])) {
								$out .= ' fieldrequired';
							}
							$out .= '">';
							if (!empty($extrafields->attributes[$this->table_element]['help'][$key])) {
								$out .= $form->textwithpicto($labeltoshow, $helptoshow);
							} else {
								$out .= $labeltoshow;
							}
						}

						$out .= ($display_type == 'card' ? '</td>' : '</div>');

						$html_id = !empty($this->id) ? $this->element.'_extras_'.$key.'_'.$this->id : '';
						if ($display_type == 'card') {
							// a first td column was already output (and may be another on before if MAIN_VIEW_LINE_NUMBER set), so this td is the next one
							$out .= '<td '.($html_id ? 'id="'.$html_id.'" ' : '').' class="'.$this->element.'_extras_'.$key.'" '.($colspan ? ' colspan="'.$colspan.'"' : '').'>';
						} elseif ($display_type == 'line') {
							$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').' style="display: inline-block" class="'.$this->element.'_extras_'.$key.' extra_inline_'.$extrafields->attributes[$this->table_element]['type'][$key].'">';
						}

						switch ($mode) {
							case "view":
								$out .= $extrafields->showOutputField($key, $value, '', $this->table_element);
								break;
							case "create":
								if ($key == 'hour'){
									$duration_hour = (GETPOSTINT('hourhour') ? GETPOSTINT('hourhour') : 0) * 60 * 60;
									$duration_hour += (GETPOSTINT('hourmin') ? GETPOSTINT('hourmin') : 0) * 60;	
									$out .= $form->select_duration('hour', $duration_hour, 0, 'select', 0, 1);
								}
								elseif($key != 'client_informe'){
									$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $this->table_element);
								}
								else {
									$out .= $extrafields->showInputField($key, $value, 'onchange="afficherNomClient()"', $keysuffix, '', 0, $this->id, $this->table_element);
								}
								break;
							case "edit":
								if($key == 'fk_validator2'){
									//print '<td>';
									$include_users = $this->fetch_users_approver_holiday();
									if (!in_array($this->fk_validator, $include_users)) {  // Add the current validator to the list to not lose it when editing.
										$include_users[] = $this->fk_validator;
									}
									if (empty($include_users)) {
										$out .= img_warning().' '.$langs->trans("NobodyHasPermissionToValidateHolidays");
									} else {
										$arrayofvalidatorstoexclude = (($user->admin || ($user->id != $userRequest->id)) ? '' : array($user->id)); // Nobody if we are admin or if we are not the user of the leave.
										$s = $form->select_dolusers($this->array_options['options_fk_validator2'], "options_fk_validator2", (($action == 'editvalidator') ? 0 : 1), $arrayofvalidatorstoexclude, 0, $include_users);
										$out .= $form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate2"));
									}
									//print '</td>';
								}
								elseif ($key == 'client_informe') {
									$nom = "'".$this->array_options['options_nom_client']."'";
									$out .= $extrafields->showInputField($key, $value, 'onchange="afficherNomClient('.$nom.')"', $keysuffix, '', 0, $this->id, $this->table_element);
								}
								elseif ($key == 'hour'){
									$duration_hour = $this->array_options['options_'.$key];
									$out .= $form->select_duration('hour', $duration_hour, 0, 'select', 0, 1);
								}
								else {
									$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $this->table_element);
								}
								break;
						}

						$out .= ($display_type=='card' ? '</td>' : '</div>');

						if (!empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && (($e % 2) == 1)) {
							$out .= ($display_type=='card' ? '</tr>' : '</div>');
						} else {
							$out .= ($display_type=='card' ? '</tr>' : '</div>');
						}
						$e++;
					}
				}
				$out .= "\n";
				// Add code to manage list depending on others
				if (!empty($conf->use_javascript_ajax)) {
					$out .= $this->getJSListDependancies();
				}

				$out .= '<!-- /showOptionals --> '."\n";
			}
		}

		$out .= $hookmanager->resPrint;
		return $out;
	}


	/**
	 *  Import du nombre de congés à partir d'un fichier Excel
	 *
	 *  @return		int     resultat
	 */
	public function import_conges($file){
        global $conf, $langs, $user;

        $error = 0;

        $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $now = dol_now();
		$this->db->begin();

        $typeleaves_CP_N1_ACQUIS = $this->getTypesCP(1, 'CP_N-1_ACQUIS');
        $typeleaves_CP_N1_PRIS = $this->getTypesCP(1, 'CP_N-1_PRIS');
        $typeleaves_CP_N_ACQUIS = $this->getTypesCP(1, 'CP_N_ACQUIS');
        $typeleaves_CP_N_PRIS = $this->getTypesCP(1, 'CP_N_PRIS');
        $typeleaves_CP_FRAC_ACQUIS = $this->getTypesCP(1, 'CP_FRAC_ACQUIS');
        $typeleaves_CP_FRAC_PRIS = $this->getTypesCP(1, 'CP_FRAC_PRIS');
        $typeleaves_CP_ANC_ACQUIS = $this->getTypesCP(1, 'CP_ANC_ACQUIS');
        $typeleaves_CP_ANC_PRIS = $this->getTypesCP(1, 'CP_ANC_PRIS');
		$typeleaves_RTT_ACQUIS = $this->getTypesCP(1, 'RTT_ACQUIS');
		$typeleaves_RTT_PRIS = $this->getTypesCP(1, 'RTT_PRIS');
        $typeleaves_ACP = $this->getTypesCP(1, 'ACP');

        $i = 1;
        foreach ($worksheet->getRowIterator() as $row) { // On parcours toutes les lignes du fichier excel
            if($i > 3){ // On ignore la 1ere ligne (titres)
                $matricule = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1, $i)->getValue();
                if (!empty($matricule)){
                    $acquis_N1 = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(4, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4, $i)->getValue() : 0);
                    $pris_N1 = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(5, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5, $i)->getValue() : 0);
                    $solde_N1 = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(6, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6, $i)->getValue() : $acquis_N1 - $pris_N1);
                    $acquis_N = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(7, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7, $i)->getValue() : 0);
                    $pris_N = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(8, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8, $i)->getValue() : 0);
                    $solde_N = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(9, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9, $i)->getValue() : $acquis_N - $pris_N);
                    $acquis_Frac = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(13, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13, $i)->getValue() : 0);
                    $pris_Frac = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(14, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14, $i)->getValue() : 0);
                    $solde_Frac = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(15, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15, $i)->getValue() : $acquis_Frac - $pris_Frac);
                    $acquis_Anc = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(16, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16, $i)->getValue() : 0);
                    $pris_Anc = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(17, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17, $i)->getValue() : 0);
                    $solde_Anc = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(18, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18, $i)->getValue() : $acquis_Anc - $pris_Anc);
                    $acquis_RTT = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(10, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10, $i)->getValue() : 0);
                    $pris_RTT = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(11, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11, $i)->getValue() : 0);
                    $solde_RTT = (!empty($spreadsheet->getActiveSheet()->getCellByColumnAndRow(12, $i)->getValue()) ? $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12, $i)->getValue() : $acquis_RTT - $pris_RTT);
					$solde_total = $solde_N1 + $solde_N + $solde_Frac + $solde_Anc + $solde_RTT; // Récupération du nombre de congés

					$sql = 'SELECT u.fk_object, uu.firstname, uu.lastname FROM llx_user_extrafields as u LEFT JOIN llx_user as uu ON uu.rowid = u.fk_object WHERE u.matricule = '.$matricule;
					$resql = $this->db->query($sql);
					$obj_user = $this->db->fetch_object($resql);

					if(empty($obj_user->fk_object)) {
						$this->output .= '<span style="color: red;">L\'utilisateur avec le matricule '.$matricule.' n\'a pas été trouvé</span><br>';
					}
					else {
						// Gestion de nombre total de CP
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_ACP['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $solde_total){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$solde_total;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_ACP['rowid'];
							}
						}
						elseif($solde_total >= 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_ACP['rowid'].",";
							$sql .= ' '.$solde_total.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}
						else {
							$this->output .= '<span style="color: green;">Import réalisé avec succés pour '.$obj_user->firstname." ".$obj_user->lastname." (".$matricule.')</sapn><br>';
						}






						// Gestion des CP Acquis N-1
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_N1_ACQUIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $acquis_N1){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$acquis_N1;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_N1_ACQUIS['rowid'];
							}
						}
						elseif($acquis_N1 != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_N1_ACQUIS['rowid'].",";
							$sql .= ' '.$acquis_N1.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}

						// Gestion des CP Pris N-1
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_N1_PRIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $pris_N1){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$pris_N1;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_N1_PRIS['rowid'];
							}
						}
						elseif($pris_N1 != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_N1_PRIS['rowid'].",";
							$sql .= ' '.$pris_N1.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}





						// Gestion des CP Acquis N
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_N_ACQUIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $acquis_N){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$acquis_N;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_N_ACQUIS['rowid'];
							}
						}
						elseif($acquis_N != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_N_ACQUIS['rowid'].",";
							$sql .= ' '.$acquis_N.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}

						// Gestion des CP Pris N-1
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_N_PRIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $pris_N){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$pris_N;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_N_PRIS['rowid'];
							}
						}
						elseif($pris_N != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_N_PRIS['rowid'].",";
							$sql .= ' '.$pris_N.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}





						// Gestion des CP Frac Acquis 
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_FRAC_ACQUIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $acquis_Frac){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$acquis_Frac;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_FRAC_ACQUIS['rowid'];
							}
						}
						elseif($acquis_Frac != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_FRAC_ACQUIS['rowid'].",";
							$sql .= ' '.$acquis_Frac.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}

						// Gestion des CP Frac Pris
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_FRAC_PRIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $pris_Frac){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$pris_Frac;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_FRAC_PRIS['rowid'];
							}
						}
						elseif($pris_Frac != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_FRAC_PRIS['rowid'].",";
							$sql .= ' '.$pris_Frac.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}





						// Gestion des CP Anc Acquis 
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_ANC_ACQUIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $acquis_Anc){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$acquis_Anc;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_ANC_ACQUIS['rowid'];
							}
						}
						elseif($acquis_Anc != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_ANC_ACQUIS['rowid'].",";
							$sql .= ' '.$acquis_Anc.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}

						// Gestion des CP Frac Pris
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_CP_ANC_PRIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $pris_Anc){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$pris_Anc;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_CP_ANC_PRIS['rowid'];
							}
						}
						elseif($pris_Anc != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_CP_ANC_PRIS['rowid'].",";
							$sql .= ' '.$pris_Anc.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}




						// Gestion des RTT Acquis 
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_RTT_ACQUIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $acquis_RTT){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$acquis_RTT;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_RTT_ACQUIS['rowid'];
							}
						}
						elseif($acquis_RTT != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_RTT_ACQUIS['rowid'].",";
							$sql .= ' '.$acquis_RTT.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}

						// Gestion des RTT Pris
						$sql = 'SELECT h.nb_holiday FROM '.MAIN_DB_PREFIX.'holiday_users h';
						$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
						$sql .= ' ON u.fk_object = h.fk_user';
						$sql .= ' WHERE u.matricule = '.$matricule;
						$sql .= ' AND h.fk_type = '.$typeleaves_RTT_PRIS['rowid'];
						$resql = $this->db->query($sql);

						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							if($obj->nb_holiday != $pris_RTT){
								$sql = 'UPDATE '.MAIN_DB_PREFIX.'holiday_users h';
								$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as u';
								$sql .= ' ON u.fk_object = h.fk_user';
								$sql .= ' SET h.nb_holiday = '.$pris_RTT;
								$sql .= ' WHERE u.matricule = '.$matricule;
								$sql .= ' AND h.fk_type = '.$typeleaves_RTT_PRIS['rowid'];
							}
						}
						elseif($pris_RTT != 0) {
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'holiday_users(fk_user, fk_type, nb_holiday) VALUES(';
							$sql .= $obj_user->fk_object.",";
							$sql .= " ".$typeleaves_RTT_PRIS['rowid'].",";
							$sql .= ' '.$pris_RTT.')';
						}

						$resql = $this->db->query($sql);
						if (!$resql) {
							dol_print_error($this->db);
							$this->error = $this->db->lasterror();
							$error++;
						}




						$user_id = 0;
						$sql = 'SELECT u.fk_object FROM llx_user_extrafields as u WHERE u.matricule = '.$matricule;
						$resql = $this->db->query($sql);
						if($this->db->num_rows($resql) > 0){
							$obj = $this->db->fetch_object($resql);
							$user_id = $obj_user->fk_object;
						}

						// Vérification des congés futur
						/*if($user_id > 0) {
							$label = "Prise en compte des congés futur lors de l'import";
							$this->holiday = array();
							$this->fetchByUser($user_id, '', " AND cp.date_debut > '".dol_print_date(dol_now(), "%Y-%m-%d")."' AND cp.statut = 3 && cp.fk_type = 1");
							$nbopenedday = 0;
							foreach($this->holiday as $key => $conges){
								$nbopenedday += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
							}

							$nbopenedday_restant = $nbopenedday;


							if($nbopenedday_restant){
								$newSolde = $solde_total - $nbopenedday_restant;

								// The modification is added to the LOG
								$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_ACP['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $this->errors, 'errors');
								}

								// Update balance
								$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_ACP['rowid']);
								if ($result < 0) {
									$error++;
									setEventMessages(null, $this->errors, 'errors');
								}
							}


							$nb_FRAC_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_FRAC_ACQUIS['rowid']);
							$nb_FRAC_ACQUIS = ($nb_FRAC_ACQUIS ? price2num($nb_FRAC_ACQUIS) : 0);
							$nb_FRAC_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_FRAC_PRIS['rowid']);
							$nb_FRAC_PRIS = ($nb_FRAC_PRIS ? price2num($nb_FRAC_PRIS) : 0);
							$nb_FRAC_SOLDE = $nb_FRAC_ACQUIS-$nb_FRAC_PRIS;
							if($nb_FRAC_SOLDE > 0 && $nbopenedday_restant > 0){
								if($nb_FRAC_SOLDE >= $nbopenedday_restant){
									$newSolde = $nb_FRAC_PRIS + $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}

									// Update balance
									$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}
								}
								else {
									$newSolde = $nb_FRAC_ACQUIS;
									$nbopenedday_restant -= $nb_FRAC_SOLDE;

									// The modification is added to the LOG
									$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}

									// Update balance
									$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_FRAC_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}
								}
							}

							if($nbopenedday_restant > 0){
								$nb_ANC_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_ANC_ACQUIS['rowid']);
								$nb_ANC_ACQUIS = ($nb_ANC_ACQUIS ? price2num($nb_ANC_ACQUIS) : 0);
								$nb_ANC_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_ANC_PRIS['rowid']);
								$nb_ANC_PRIS = ($nb_ANC_PRIS ? price2num($nb_ANC_PRIS) : 0);
								$nb_ANC_SOLDE = $nb_ANC_ACQUIS-$nb_ANC_PRIS;
								if($nb_ANC_SOLDE > 0){
									if($nb_ANC_SOLDE >= $nbopenedday_restant){
										$newSolde = $nb_ANC_PRIS + $nbopenedday_restant;
										$nbopenedday_restant = 0;

										// The modification is added to the LOG
										$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}

										// Update balance
										$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}
									}
									else {
										$newSolde = $nb_ANC_ACQUIS;
										$nbopenedday_restant -= $nb_ANC_SOLDE;

										// The modification is added to the LOG
										$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}

										// Update balance
										$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_ANC_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}
									}
								}
							}

							if($nbopenedday_restant > 0){
								$nb_N1_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_N1_ACQUIS['rowid']);
								$nb_N1_ACQUIS = ($nb_N1_ACQUIS ? price2num($nb_N1_ACQUIS) : 0);
								$nb_N1_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_N1_PRIS['rowid']);
								$nb_N1_PRIS = ($nb_N1_PRIS ? price2num($nb_N1_PRIS) : 0);
								$nb_N1_SOLDE = $nb_N1_ACQUIS-$nb_N1_PRIS;
								if($nb_N1_SOLDE > 0){
									if($nb_N1_SOLDE >= $nbopenedday_restant){
										$newSolde = $nb_N1_PRIS + $nbopenedday_restant;
										$nbopenedday_restant = 0;

										// The modification is added to the LOG
										$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}

										// Update balance
										$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}
									}
									else {
										$newSolde = $nb_N1_ACQUIS;
										$nbopenedday_restant -= $nb_N1_SOLDE;

										// The modification is added to the LOG
										$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}

										// Update balance
										$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_N1_PRIS['rowid']);
										if ($result < 0) {
											$error++;
											setEventMessages(null, $this->errors, 'errors');
										}
									}
								}
							}

							if($nbopenedday_restant > 0){
								$nb_N_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_N_ACQUIS['rowid']);
								$nb_N_ACQUIS = ($nb_N_ACQUIS ? price2num($nb_N_ACQUIS) : 0);
								$nb_N_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_N_PRIS['rowid']);
								$nb_N_PRIS = ($nb_N_PRIS ? price2num($nb_N_PRIS) : 0);
								$nb_N_SOLDE = $nb_N_ACQUIS-$nb_N_PRIS;
								if($nb_N_SOLDE >= $nbopenedday_restant){
									$newSolde = $nb_N_PRIS + $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}

									// Update balance
									$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}
								}
								else {
									$newSolde = $nb_N_PRIS + $nbopenedday_restant;
									$nbopenedday_restant = 0;

									// The modification is added to the LOG
									$result = $this->addLogCP($user->id, $user_id, $label, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}

									// Update balance
									$result = $this->updateSoldeCP($user_id, $newSolde, $typeleaves_CP_N_PRIS['rowid']);
									if ($result < 0) {
										$error++;
										setEventMessages(null, $this->errors, 'errors');
									}
								}
							}
						}*/
					}
				}
            }
            $i++;
        }



        if(!$error){
            $this->db->commit();
            return 0;
        }
        else {
            $this->db->rollback();
            return 1;
        }
    }

	/**
	 *  Envoi d'un mail pour les demandes de congés >= 14j qui sont dans 2 mois ou moins 
	 *
	 *  @return		int     resultat
	 */
	public function MailConges_2semaines()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$now = dol_now();
		$now_plus_2mois = dol_time_plus_duree($now, 2, 'm');

		$sql = "SELECT h.rowid, h.date_debut, h.date_fin, h.fk_user, h.statut,  h.fk_validator, he.fk_validator2,";
		$sql .= " GROUP_CONCAT(IF((ha.validation = 0 AND ha.validation_number = 1), ha.fk_user, ''), ',') as approbateur1,";
		$sql .= " GROUP_CONCAT(IF((ha.validation = 0 AND ha.validation_number = 2), ha.fk_user, ''), ',') as approbateur2";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."holiday_extrafields as he ON he.fk_object = h.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."holiday_approbation as ha ON ha.fk_holiday = h.rowid";
		$sql .= " WHERE (h.statut = ".self::STATUS_VALIDATED;
		$sql .= " OR h.statut = ".self::STATUS_APPROVED1.")";
		$sql .= " AND DATEDIFF(h.date_fin, h.date_debut) >= 14";
		$sql .= " AND '".substr($this->db->idate($now_plus_2mois), 0, 10)."' >= h.date_debut";
		$sql .= " AND '".substr($this->db->idate($now), 0, 10)."' < h.date_debut";
		$sql .= " GROUP BY h.rowid";

		dol_syslog(get_class($this)."::MailConges_2semaines", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$this->fetch($obj->rowid);

				// To
				$emailTo = '';
				$destinataire = new User($this->db);
				if($obj->fk_validator > 0) {
					if($obj->statut == self::STATUS_VALIDATED && $obj->fk_validator > 0){
						$destinataire->fetch($obj->fk_validator);
					}
					elseif($obj->statut == self::STATUS_APPROVED1 && $obj->fk_validator2 > 0){
						$destinataire->fetch($obj->fk_validator2);
					}
					if(!empty($destinataire->email)) {
						$emailTo = $destinataire->email;
					}
				}
				else {
					if($obj->statut == self::STATUS_VALIDATED){
						$approbateur1 = explode(',', $obj->approbateur1);

						foreach($approbateur1 as $userid) {
							if($userid > 0) {
								$destinataire->fetch($userid);
								if(!empty($destinataire->email)) {
									$emailTo .= $destinataire->email.', ';
								}
							}
						}
					}
					elseif($obj->statut == self::STATUS_APPROVED1){
						$approbateur2 = explode(',', $obj->approbateur2);

						foreach($approbateur2 as $userid) {
							if($userid > 0) {
								$destinataire->fetch($userid);
								if(!empty($destinataire->email)) {
									$emailTo .= $destinataire->email.', ';
								}
							}
						}
					}
					rtrim($emailTo, ', ');
				}

				if (!$emailTo) {
					continue;
				}

				// From
				$expediteur = new User($this->db);
				$expediteur->fetch($obj->fk_user);
				$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

				// Subject
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

				// Content
				$message = '<p style="color: red;"><strong>'.$langs->transnoentities("AttentionHoliday2semainesAutomatique").'</strong><p>';
				$message .= "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";
				$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


				// option to warn the validator in case of too short delay
				if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY)) {
					$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
					if ($delayForRequest) {
						$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

						if ($this->date_debut < $nowplusdelay) {
							$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
						}
					}
				}

				// option to notify the validator if the balance is less than the request
				if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE)) {
					$nbopenedday = num_open_day($this->date_debut_gmt, $this->date_fin_gmt, 0, 1, $this->halfday);

					$ACP = $this->getTypesCP(1, 'ACP');
					if ($this->fk_type == $ACP['rowid'] && $nbopenedday > $this->getCPforUser($this->fk_user, $this->fk_type)) {
						$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
					}
				}

				$type = $this->getTypeWithID($this->fk_type);
				$link = dol_buildpath("/custom/holidaycustom/card.php", 3) . '?id='.$this->id;

				$message .= "<ul>";
				$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($this->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($this->date_fin, 'day')."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Type")." : ".$type['label']."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
				$message .= "</ul>\n";

				$trackid = 'leav'.$this->id;

				$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

				// Sending the email
				$res = $mail->sendfile();
								
				if(!$res){
					return -1;
				}
			}
		} 
		else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$this->db->free($result);
		return 0;
	}

	/**
	 *      Does the type of holiday have to be indicated in hours?
	 *
	 *      @return     int         <0 if KO, 0 if no, 1 if yes
	 */
	public function holidayTypeNeedHour($type)
	{
		global $user;

		if(empty($type)) {
			return -1;
		}

		$sql = "SELECT h.in_hour";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types as h";
		if(is_numeric($type)) {
			$sql .= " WHERE h.rowid = ".$type;
		}
		else {
			$sql .= " WHERE h.code = '".$type."'";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			return $obj->in_hour;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *     
	 *
	 *      @return     array()         Ids des types de congés qui ouvrent droit à RTT
	 */
	public function holidayTypeDroitRTT()
	{
		$res = array(); 

		$sql = "SELECT h.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types as h";
		$sql .= " WHERE h.droit_rtt = 1";

		$resql = $this->db->query($sql);
		if ($resql) {
			while($obj = $this->db->fetch_object($resql)) {
				$res[] = $obj->rowid;
			}

			$this->db->free($resql);
			return $res;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
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
	public function createApprobation($userid, $notrigger = 1, $validation_number)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id) && $userid > 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX.'holiday_approbation';
			$sql .= " VALUES(".$this->id;
			$sql .= ", ".((int) $userid).", 0, ".((int) $validation_number).")";

			dol_syslog(get_class($this)."::createApprobation()", LOG_DEBUG);
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
	public function updateApprobation($userid, $notrigger = 0, $validation, $validation_number)
	{
		global $conf, $langs, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id) && $userid > 0) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.'holiday_approbation';
			$sql .= " SET validation = ".$validation;
			$sql .= " WHERE fk_holiday = ".(int)$this->id;
			$sql .= " AND fk_user = ".(int)$userid;
			$sql .= " AND validation_number = ".(int)$validation_number;

			dol_syslog(get_class($this)."::updateApprobation()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			// if (!$error && !$notrigger) {
			// 	// Call trigger
			// 	$result = $this->call_trigger('HOLIDAY_APPROBATION', $user);
			// 	if ($result < 0) {
			// 		$error++;
			// 	}
			// 	// End call triggers
			// }
		}

		if (!$error) {
			if ($validation_number == 1) {
				$this->listApprover1[1][(int)$userid] = $validation;
			}
			elseif ($validation_number == 2) {
				$this->listApprover2[1][(int)$userid] = $validation;
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
	public function deleteAllApprobation()
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.'holiday_approbation';
			$sql .= " WHERE fk_holiday = ".(int)$this->id;

			dol_syslog(get_class($this)."::deleteAllApprobation()", LOG_DEBUG);
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
	 *  @param		int						$validation_number	Validation Number
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function deleteApprobation($userid, $validation_number)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		if (!empty($this->id)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.'holiday_approbation';
			$sql .= " WHERE fk_holiday = ".(int)$this->id;
			$sql .= " and fk_user = ".(int)$userid;
			$sql .= " and validation_number = ".(int)$validation_number;

			dol_syslog(get_class($this)."::deleteApprobation()", LOG_DEBUG);
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
	 * 	Return la liste des approbateurs d'un congés
	 *
	 * 	@param	string	$excludefilter		Filter to exclude. Do not use here a string coming from user input.
	 *  @param	int		$validation_number	Validation Number
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listApprover($excludefilter = '', $validation_number)
	{
		global $conf, $user;
		$ret = array();

		if(!empty($this->fk_validator) && $validation_number == 1) {
			$newuser = new User($this->db);
			$newuser->fetch($this->fk_validator);
			$ret[0][$this->fk_validator] = $this->fk_validator;
			$ret[1][$this->fk_validator] = ($this->statut == self::STATUS_DRAFT || $this->statut == self::STATUS_VALIDATED ? 0 : 1);
			$ret[2][$this->fk_validator] = $newuser;

			return $ret;
		}

		if(!empty($this->array_options['options_fk_validator2']) && $validation_number == 2) {
			$newuser = new User($this->db);
			$newuser->fetch($this->array_options['options_fk_validator2']);
			$ret[0][$this->array_options['options_fk_validator2']] = $this->array_options['options_fk_validator2'];
			$ret[1][$this->array_options['options_fk_validator2']] = ($this->statut == self::STATUS_DRAFT || $this->statut == self::STATUS_VALIDATED || $this->statut == self::STATUS_APPROVED1 ? 0 : 1);
			$ret[2][$this->array_options['options_fk_validator2']] = $newuser;

			return $ret;
		}

		$sql = "SELECT u.rowid, t.validation";
		
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		if (!empty($this->id)) {
			$sql .= ", ".MAIN_DB_PREFIX."holiday_approbation as t";
		}
		$sql .= " WHERE 1 = 1";
		$sql .= " AND t.fk_user = u.rowid";
		$sql .= " AND t.validation_number = ".(int)$validation_number;
		if (!empty($this->id)) {
			$sql .= " AND t.fk_holiday = ".((int) $this->id);
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

	public function getArrayHoliday($user_id, $detail = 0, $only_solde = 0) {
		global $langs; 
		$ret = '';

		$typeleaves_CP_N1_ACQUIS = $this->getTypesCP(1, 'CP_N-1_ACQUIS');
		$typeleaves_CP_N1_PRIS = $this->getTypesCP(1, 'CP_N-1_PRIS');
		$typeleaves_CP_N_ACQUIS = $this->getTypesCP(1, 'CP_N_ACQUIS');
		$typeleaves_CP_N_PRIS = $this->getTypesCP(1, 'CP_N_PRIS');
		$typeleaves_CP_FRAC_ACQUIS = $this->getTypesCP(1, 'CP_FRAC_ACQUIS');
		$typeleaves_CP_FRAC_PRIS = $this->getTypesCP(1, 'CP_FRAC_PRIS');
		$typeleaves_CP_ANC_ACQUIS = $this->getTypesCP(1, 'CP_ANC_ACQUIS');
		$typeleaves_CP_ANC_PRIS = $this->getTypesCP(1, 'CP_ANC_PRIS');
		$typeleaves_RTT_ACQUIS = $this->getTypesCP(1, 'RTT_ACQUIS');
		$typeleaves_RTT_PRIS = $this->getTypesCP(1, 'RTT_PRIS');
		$typeleaves_ACP = $this->getTypesCP(1, 'ACP');

		$nb_N1_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_N1_ACQUIS['rowid']);
		$nb_N1_ACQUIS = ($nb_N1_ACQUIS ? price2num($nb_N1_ACQUIS) : 0);
		$nb_N1_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_N1_PRIS['rowid']);
		$nb_N1_PRIS = ($nb_N1_PRIS ? price2num($nb_N1_PRIS) : 0);
		$nb_N1_SOLDE = $nb_N1_ACQUIS-$nb_N1_PRIS;

		$nb_N_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_N_ACQUIS['rowid']);
		$nb_N_ACQUIS = ($nb_N_ACQUIS ? price2num($nb_N_ACQUIS) : 0);
		$nb_N_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_N_PRIS['rowid']);
		$nb_N_PRIS = ($nb_N_PRIS ? price2num($nb_N_PRIS) : 0);
		$nb_N_SOLDE = $nb_N_ACQUIS-$nb_N_PRIS;

		$nb_FRAC_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_FRAC_ACQUIS['rowid']);
		$nb_FRAC_ACQUIS = ($nb_FRAC_ACQUIS ? price2num($nb_FRAC_ACQUIS) : 0);
		$nb_FRAC_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_FRAC_PRIS['rowid']);
		$nb_FRAC_PRIS = ($nb_FRAC_PRIS ? price2num($nb_FRAC_PRIS) : 0);
		$nb_FRAC_SOLDE = $nb_FRAC_ACQUIS-$nb_FRAC_PRIS;

		$nb_ANC_ACQUIS = $this->getCPforUser($user_id, $typeleaves_CP_ANC_ACQUIS['rowid']);
		$nb_ANC_ACQUIS = ($nb_ANC_ACQUIS ? price2num($nb_ANC_ACQUIS) : 0);
		$nb_ANC_PRIS = $this->getCPforUser($user_id, $typeleaves_CP_ANC_PRIS['rowid']);
		$nb_ANC_PRIS = ($nb_ANC_PRIS ? price2num($nb_ANC_PRIS) : 0);
		$nb_ANC_SOLDE = $nb_ANC_ACQUIS-$nb_ANC_PRIS;

		$nb_RTT_ACQUIS = $this->getCPforUser($user_id, $typeleaves_RTT_ACQUIS['rowid']);
		$nb_RTT_ACQUIS = ($nb_RTT_ACQUIS ? price2num($nb_RTT_ACQUIS) : 0);
		$nb_RTT_PRIS = $this->getCPforUser($user_id, $typeleaves_RTT_PRIS['rowid']);
		$nb_RTT_PRIS = ($nb_RTT_PRIS ? price2num($nb_RTT_PRIS) : 0);
		$nb_RTT_SOLDE = $nb_RTT_ACQUIS-$nb_RTT_PRIS;

		$nb_ACP = $this->getCPforUser($user_id, $typeleaves_ACP['rowid']);
		$nb_ACP_SOLDE = ($nb_ACP ? price2num($nb_ACP) : 0);
		$balancetoshow = $langs->trans('SoldeCPUser', '{s1}');

		if($detail) {
			$this->holiday = array();
			$this->fetchByUser($user_id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 1");
			$nb_conges_brouillon = 0;
			foreach($this->holiday as $key => $conges){
				$nb_conges_brouillon += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
			}
			$this->holiday = array();
			$this->fetchByUser($user_id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 2");
			$nb_conges_attente_approbation = 0;
			foreach($this->holiday as $key => $conges){
				$nb_conges_attente_approbation += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
			}
			$this->holiday = array();
			$this->fetchByUser($user_id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 6");
			$nb_conges_cours_approbation = 0;
			foreach($this->holiday as $key => $conges){
				$nb_conges_cours_approbation += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
			}
			$this->holiday = array();
			$this->fetchByUser($user_id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 3 AND cp.date_debut >= '".$this->db->idate(dol_now())."'");
			$nb_conges_approuve = 0;
			foreach($this->holiday as $key => $conges){
				$nb_conges_approuve += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
			}
		}

		if(!$only_solde) {
			$ret .= '<div class="valignmiddle div-balanceofleave center">'.str_replace('{s1}', img_picto('', 'holiday', 'class="paddingleft pictofixedwidth"').'<span class="balanceofleave valignmiddle'.($nb_ACP_SOLDE > 0 ? ' amountpaymentcomplete' : ($nb_ACP_SOLDE < 0 ? ' amountremaintopay' : ' amountpaymentneutral')).'">'.round($nb_ACP_SOLDE, 5).'</span>', $balancetoshow).'</div>';
			if($detail) {
				$ret .= '<div class="valignmiddle div-balanceofleave center">';
				$ret .= 'Dont <span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_brouillon.'</span> en brouillon, ';
				$ret .= '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_attente_approbation.'</span> en attente d\'approbation, ';
				$ret .= '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_cours_approbation.'</span> en cours d\'approbation, et ';
				$ret .= '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_approuve.'</span> approuvé(s) à venir';
				$ret .= '</div>';
			}
			$ret .= '<br><table class="noborder nohover" style="text-align: center; width: 96%; margin: auto; max-width: 1000px;">';
			$ret .= '<tr>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">Acquis<br>CP N</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">Pris<br>CP N</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;"><strong>Solde<br>CP N</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">Acquis<br>CP N-1</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">Pris<br>CP N-1</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;"><strong>Solde<br>CP N-1</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">Acquis<br>CP Anc</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">Pris<br>CP Anc</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;"><strong>Solde<br>CP Anc</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">Acquis<br>CP Frac</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">Pris<br>CP Frac</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;"><strong>Solde<br>CP Frac</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">Acquis<br>RTT</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">Pris<br>RTT</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;"><strong>Solde<br>RTT</strong></td>';
			$ret .= '</tr>';
			$ret .= '<tr>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">'.$nb_N_ACQUIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">'.$nb_N_PRIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;"><strong>'.$nb_N_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">'.$nb_N1_ACQUIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">'.$nb_N1_PRIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;"><strong>'.$nb_N1_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">'.$nb_ANC_ACQUIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">'.$nb_ANC_PRIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;"><strong>'.$nb_ANC_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">'.$nb_FRAC_ACQUIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">'.$nb_FRAC_PRIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;"><strong>'.$nb_FRAC_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">'.$nb_RTT_ACQUIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">'.$nb_RTT_PRIS.'</td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;"><strong>'.$nb_RTT_SOLDE.'</strong></td>';
			$ret .= '</tr>';
			$ret .= '</table><br>';
		}
		else {
			$ret .= '<div class="valignmiddle div-balanceofleave center">'.str_replace('{s1}', img_picto('', 'holiday', 'class="paddingleft pictofixedwidth"').'<span class="balanceofleave valignmiddle'.($nb_ACP > 0 ? ' amountpaymentcomplete' : ($nb_ACP < 0 ? ' amountremaintopay' : ' amountpaymentneutral')).'">'.round($nb_ACP, 5).'</span>', $balancetoshow).'</div>';
			$ret .= '<div class="valignmiddle div-balanceofleave center">';
			$ret .= 'Dont <span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_brouillon.'</span> en brouillon, ';
			$ret .= '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_attente_approbation.'</span> en attente d\'approbation, ';
			$ret .= '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_cours_approbation.'</span> en cours d\'approbation, et ';
			$ret .= '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_approuve.'</span> approuvé(s) à venir';
			$ret .= '</div>';
		
			$ret .= '<br><table class="noborder nohover" style="text-align: center; width: 96%; margin: auto;">';
			$ret .= '<tr>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;"><strong>Solde<br>CP N</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;"><strong>Solde<br>CP N-1</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;"><strong>Solde<br>CP Anc</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;"><strong>Solde<br>CP Frac</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;"><strong>Solde<br>RTT</strong></td>';
			$ret .= '</tr>';
			$ret .= '<tr>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;"><strong>'.$nb_N_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%"><strong>'.$nb_N1_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;"><strong>'.$nb_ANC_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;"><strong>'.$nb_FRAC_SOLDE.'</strong></td>';
			$ret .= '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;"><strong>'.$nb_RTT_SOLDE.'</strong></td>';
			$ret .= '</tr>';
			$ret .= '</table>';
		}

		return $ret;
	}
}
