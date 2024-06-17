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
 * \file        class/user_formation.class.php
 * \ingroup     formationhabilitation
 * \brief       This file is a CRUD class file for User_formation (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for User_formation
 */
class User_formation extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'formationhabilitation';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'user_formation';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'formationhabilitation_user_formation';

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
	 * @var string String with name of icon for user_formation. Must be the part after the 'object_' into object_user_formation.png
	 */
	public $picto = 'fa-user-graduate_fas_#1f3d89';


	const STATUS_VALIDE = 1;
	const STATUS_A_PROGRAMMER = 2;
	const STATUS_PROGRAMMEE = 3;
	const STATUS_EXPIREE = 4;
	const STATUS_CLOTUREE = 9;


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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'index'=>1, 'searchall'=>1, 'validate'=>'1', 'comment'=>"Reference of object"),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>0, 'cssview'=>'wordbreak', 'validate'=>'1',),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
		'fk_formation' => array('type'=>'integer:Formation:custom/formationhabilitation/class/formation.class.php:0:(status:=:1)', 'label'=>'Formation', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1,),
		'fk_user' => array('type'=>'integer:User:user\class\user.class.php:0:(statut:=:1)', 'label'=>'User', 'enabled'=>'1', 'position'=>31, 'notnull'=>1, 'visible'=>1,),
		'date_formation' => array('type'=>'date', 'label'=>'DateFormation', 'enabled'=>'1', 'position'=>32, 'notnull'=>1, 'visible'=>1,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('1'=>'Valide', '2'=>'A programmer', '3'=>'Programmée', '4'=>'Expirée', '9'=>'Cloturée'), 'validate'=>'1',),
		'cout_pedagogique' => array('type'=>'price', 'label'=>'CoutPedagogique', 'enabled'=>'1', 'position'=>35, 'notnull'=>1, 'visible'=>4,),
		'cout_mobilisation' => array('type'=>'price', 'label'=>'CoutMobilisation', 'enabled'=>'1', 'position'=>36, 'notnull'=>1, 'visible'=>4,),
		'cout_total' => array('type'=>'price', 'label'=>'CoutTotal', 'enabled'=>'1', 'position'=>37, 'notnull'=>1, 'visible'=>4,),
		'date_fin_formation' => array('type'=>'date', 'label'=>'DateFinFormation', 'enabled'=>'1', 'position'=>33, 'notnull'=>0, 'visible'=>1,),
	);
	public $rowid;
	public $ref;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $fk_formation;
	public $fk_user;
	public $date_formation;
	public $status;
	public $cout_pedagogique;
	public $cout_mobilisation;
	public $cout_total;
	public $date_fin_formation;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'formationhabilitation_user_formationline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_user_formation';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'User_formationline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('formationhabilitation_user_formationdet');

	// /**
	//  * @var User_formationLine[]     Array of subtable lines
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
		/*if ($user->rights->formationhabilitation->user_formation->read) {
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

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("User_formation").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/formationhabilitation/user_formation_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowUser_formation");
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
		$hookmanager->initHooks(array('user_formationdao'));
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
			//$langs->load("formationhabilitation@formationhabilitation");
			$this->labelStatus[self::STATUS_VALIDE] = $langs->transnoentitiesnoconv('Valide');
			$this->labelStatus[self::STATUS_A_PROGRAMMER] = $langs->transnoentitiesnoconv("A programmer");
			$this->labelStatus[self::STATUS_PROGRAMMEE] = $langs->transnoentitiesnoconv('Programmée');
			$this->labelStatus[self::STATUS_EXPIREE] = $langs->transnoentitiesnoconv('Expirée');
			$this->labelStatus[self::STATUS_CLOTUREE] = $langs->transnoentitiesnoconv('Cloturée');
			$this->labelStatusShort[self::STATUS_VALIDE] = $langs->transnoentitiesnoconv('Valide');
			$this->labelStatusShort[self::STATUS_A_PROGRAMMER] = $langs->transnoentitiesnoconv("A programmer");
			$this->labelStatusShort[self::STATUS_PROGRAMMEE] = $langs->transnoentitiesnoconv('Programmée');
			$this->labelStatusShort[self::STATUS_EXPIREE] = $langs->transnoentitiesnoconv('Expirée');
			$this->labelStatusShort[self::STATUS_CLOTUREE] = $langs->transnoentitiesnoconv('Cloturée');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_VALIDE) $statusType = 'status4';
		if ($status == self::STATUS_A_PROGRAMMER) $statusType = 'status1';
		if ($status == self::STATUS_PROGRAMMEE) $statusType = 'status2';
		if ($status == self::STATUS_EXPIREE) $statusType = 'status8';
		if ($status == self::STATUS_CLOTUREE) $statusType = 'status6';

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

		$objectline = new User_formationLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_user_formation = '.((int) $this->id)));

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

		if (empty($conf->global->FORMATIONHABILITATION_USER_FORMATION_ADDON)) {
			$conf->global->FORMATIONHABILITATION_USER_FORMATION_ADDON = 'mod_user_formation_standard';
		}

		if (!empty($conf->global->FORMATIONHABILITATION_USER_FORMATION_ADDON)) {
			$mybool = false;

			$file = $conf->global->FORMATIONHABILITATION_USER_FORMATION_ADDON.".php";
			$classname = $conf->global->FORMATIONHABILITATION_USER_FORMATION_ADDON;

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
		$includedocgeneration = 0;

		$langs->load("formationhabilitation@formationhabilitation");

		if (!dol_strlen($modele)) {
			$modele = 'standard_user_formation';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->USER_FORMATION_ADDON_PDF)) {
				$modele = $conf->global->USER_FORMATION_ADDON_PDF;
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
	 * 	Return l'id de user_formation à partir de l'id de la formation et de l'utilisateur
	 *
	 * 	@param	int		$id_formation	Id de la formation
	 * 	@param	int		$id_user		Id de l'utilisateur
	 * 	@return	int						Id de user_formation 
	 */
	public function getId($id_formation, $id_user)
	{
		global $conf, $user;

		$sql = "SELECT uf.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_user_formation as uf";
		$sql .= " WHERE uf.fk_user =". $id_user;
		$sql .= " AND uf.fk_formation =". $id_formation;


		dol_syslog(get_class($this)."::getId", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$num = $this->db->num_rows($resql);
			$this->db->free($resql);

			if($num > 0){
				return $obj->rowid;
			}
			else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}



	/**
	 * 	Utilisé par une tâche Cron : Permet de passer les formations des utilisateurs qui sont \'active\' en \'fin d\'échéance\' lorsque la période de recyclage est atteinte
	 *
	 * 	@return	int						0 si réussi, -1 sinon
	 */
	public function MajStatuts()
	{
		global $conf, $user;

		$error = 0;
		$now = dol_now();
		//$tab_id = array();

		$this->db->begin();

		// Gestion des formations avec periode de recyclage mais pas de periode de souplesse dont DateFormation + PeriodeRecyclage > DateJour => Expirée
		$sql = "SELECT uf.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_user_formation as uf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_formation f ON f.rowid = uf.fk_formation";
		$sql .= " WHERE f.periode_recyclage IS NOT NULL";
		//$sql .= " AND f.periode_souplesse IS NULL";
		$sql .= " AND uf.status = ".self::STATUS_VALIDE;
		$sql .= " AND DATE_ADD(uf.date_formation, INTERVAL f.periode_recyclage MONTH) <= '".$this->db->idate($now)."'";

		// Gestion des formations avec periode de recyclage mais pas de periode de souplesse dont DateFormation + PeriodeRecyclage > DateJour + 6 mois => A programmer
		$sql2 = "SELECT uf.rowid";
		$sql2 .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_user_formation as uf";
		$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_formation f ON f.rowid = uf.fk_formation";
		$sql2 .= " WHERE f.periode_recyclage IS NOT NULL";
		//$sql2 .= " AND f.periode_souplesse IS NULL";
		$sql2 .= " AND uf.status = ".self::STATUS_VALIDE;
		$sql2 .= " AND DATE_ADD(DATE_ADD(uf.date_formation, INTERVAL f.periode_recyclage MONTH), INTERVAL -6 MONTH) <= '".$this->db->idate($now)."'";

		dol_syslog(get_class($this)."::MajStatuts", LOG_DEBUG);

		$resql = $this->db->query($sql);
		$resql2 = $this->db->query($sql2);

		if ($resql && $resql2) {
			// Gestion des formations avec periode de recyclage mais pas de periode de souplesse dont DateFormation + PeriodeRecyclage > DateJour => Expirée
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tab_id[] = $obj->rowid;
				$i++;
			}	

			// Gestion des formations avec periode de recyclage mais pas de periode de souplesse dont DateFormation + PeriodeRecyclage > DateJour + 6 mois => A programmer
			$num = $this->db->num_rows($resql2);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql2);
				if(!in_array($obj->rowid, $tab_id)) {
					$tab_id2[] = $obj->rowid;
				}
				$i++;
			}	

			// Gestion des formations avec periode de recyclage mais pas de periode de souplesse dont DateFormation + PeriodeRecyclage > DateJour => Expirée
			if(!empty($tab_id)){
				$ID = implode(",", $tab_id);

				$sql = "UPDATE ".MAIN_DB_PREFIX."formationhabilitation_user_formation uf";
				$sql .= " SET uf.status = ".self::STATUS_EXPIREE;
				$sql .= " WHERE uf.rowid IN (".$this->db->sanitize($ID).")";

				$resql = $this->db->query($sql);
				if ($resql) {
					$this->db->commit();
				}
				else{
					$this->error = $this->db->lasterror();
					$error++;
				}
			}

			// Gestion des formations avec periode de recyclage mais pas de periode de souplesse dont DateFormation + PeriodeRecyclage > DateJour + 6 mois => A programmer
			if(!empty($tab_id2)){
				$ID = implode(",", $tab_id2);

				$sql = "UPDATE ".MAIN_DB_PREFIX."formationhabilitation_user_formation uf";
				$sql .= " SET uf.status = ".self::STATUS_A_PROGRAMMER;
				$sql .= " WHERE uf.rowid IN (".$this->db->sanitize($ID).")";

				$resql = $this->db->query($sql);
				if ($resql) {
					$this->db->commit();
				}
				else{
					$this->error = $this->db->lasterror();
					$error++;
				}
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		// Gestion des formations avec periode de recyclage et periode de souplesse (non restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse > DateJour => Expirée
		/*$sql = "SELECT uf.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_user_formation as uf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_formation f ON f.rowid = uf.fk_formation";
		$sql .= " WHERE f.periode_recyclage IS NOT NULL";
		$sql .= " AND f.periode_souplesse IS NOT NULL";
		$sql .= " AND (f.bloquant = 2 OR f.bloquant = 0)";
		$sql .= " AND uf.status = ".self::STATUS_VALIDE;
		$sql .= " AND DATE_ADD(DATE_ADD(uf.date_formation, INTERVAL f.periode_recyclage MONTH), INTERVAL f.periode_souplesse MONTH) <= '".$this->db->idate($now)."'";

		// Gestion des formations avec periode de recyclage et periode de souplesse (non restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse < DateJour => A programmer
		$sql2 = "SELECT uf.rowid";
		$sql2 .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_user_formation as uf";
		$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_formation f ON f.rowid = uf.fk_formation";
		$sql2 .= " WHERE f.periode_recyclage IS NOT NULL";
		$sql2 .= " AND f.periode_souplesse IS NOT NULL";
		$sql2 .= " AND (f.bloquant = 2 OR f.bloquant = 0)";
		$sql2 .= " AND uf.status = ".self::STATUS_VALIDE;
		$sql2 .= " AND DATE_ADD(DATE_ADD(uf.date_formation, INTERVAL f.periode_recyclage MONTH), INTERVAL f.periode_souplesse MONTH) > '".$this->db->idate($now)."'";
		$sql2 .= " AND DATE_ADD(uf.date_formation, INTERVAL f.periode_recyclage MONTH) <= '".$this->db->idate($now)."'";

		$resql = $this->db->query($sql);
		$resql2 = $this->db->query($sql2);

		if ($resql && $resql2) {
			// Gestion des formations avec periode de recyclage et periode de souplesse (non restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse > DateJour => Expirée
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tab_id[] = $obj->rowid;
				$i++;
			}	

			// Gestion des formations avec periode de recyclage et periode de souplesse (non restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse < DateJour => A programmer
			$num = $this->db->num_rows($resql2);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql2);
				if(!in_array($obj->rowid, $tab_id)) {
					$tab_id2[] = $obj->rowid;
				}
				$i++;
			}	

			// Gestion des formations avec periode de recyclage et periode de souplesse (non restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse > DateJour => Expirée
			if(!empty($tab_id)){
				$ID = implode(",", $tab_id);

				$sql = "UPDATE ".MAIN_DB_PREFIX."formationhabilitation_user_formation uf";
				$sql .= " SET uf.status = ".self::STATUS_EXPIREE;
				$sql .= " WHERE uf.rowid IN (".$this->db->sanitize($ID).")";

				$resql = $this->db->query($sql);
				if ($resql) {
					$this->db->commit();
				}
				else{
					$this->error = $this->db->lasterror();
					$error++;
				}
			}

			// Gestion des formations avec periode de recyclage et periode de souplesse (non restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse < DateJour => A programmer
			if(!empty($tab_id2)){
				$ID = implode(",", $tab_id2);

				$sql = "UPDATE ".MAIN_DB_PREFIX."formationhabilitation_user_formation uf";
				$sql .= " SET uf.status = ".self::STATUS_A_PROGRAMMER;
				$sql .= " WHERE uf.rowid IN (".$this->db->sanitize($ID).")";

				$resql = $this->db->query($sql);
				if ($resql) {
					$this->db->commit();
				}
				else{
					$this->error = $this->db->lasterror();
					$error++;
				}
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}


		// Gestion des formations avec periode de recyclage et periode de souplesse (restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse < DateJour => Expirée
		$sql = "SELECT uf.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."formationhabilitation_user_formation as uf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_formation f ON f.rowid = uf.fk_formation";
		$sql .= " WHERE f.periode_recyclage IS NOT NULL";
		$sql .= " AND f.periode_souplesse IS NOT NULL";
		$sql .= " AND f.bloquant = 1";
		$sql .= " AND uf.status = ".self::STATUS_VALIDE;
		$sql .= " AND DATE_ADD(DATE_ADD(uf.date_formation, INTERVAL f.periode_recyclage MONTH), INTERVAL f.periode_souplesse MONTH) > '".$this->db->idate($now)."'";
		$sql .= " AND DATE_ADD(uf.date_formation, INTERVAL f.periode_recyclage MONTH) <= '".$this->db->idate($now)."'";

		$resql = $this->db->query($sql);

		if ($resql) {
			// Gestion des formations avec periode de recyclage et periode de souplesse (restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse < DateJour => Expirée
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tab_id[] = $obj->rowid;
				$i++;
			}	

			// Gestion des formations avec periode de recyclage et periode de souplesse (restrictive) dont DateFormation + PeriodeRecyclage + PeriodeSouplesse < DateJour => Expirée
			if(!empty($tab_id)){
				$ID = implode(",", $tab_id);

				$sql = "UPDATE ".MAIN_DB_PREFIX."formationhabilitation_user_formation uf";
				$sql .= " SET uf.status = ".self::STATUS_EXPIREE;
				$sql .= " WHERE uf.rowid IN (".$this->db->sanitize($ID).")";

				$resql = $this->db->query($sql);
				if ($resql) {
					$this->db->commit();
				}
				else{
					$this->error = $this->db->lasterror();
					$error++;
				}
			}			
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}*/


		if($error == 0) {
			return 0;
		}
		else {
			return 1;
		}
	}

}

