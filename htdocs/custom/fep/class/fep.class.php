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
 * \file        class/fep.class.php
 * \ingroup     fep
 * \brief       This file is a CRUD class file for FEP (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

/**
 * Class for FEP
 */
class FEP extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'fep';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'fep';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'fep_fep';

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
	 * @var string String with name of icon for fep. Must be the part after the 'object_' into object_fep.png
	 */
	public $picto = 'fep_32@fep';


	const STATUS_DRAFT = 0;
	const STATUS_COMMENTAIRE = 1;
	const STATUS_REPONSE = 2;
	const STATUS_PUBLIER = 4;
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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>11, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object"),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>201, 'notnull'=>0, 'visible'=>0,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>202, 'notnull'=>0, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'date_debut' => array('type'=>'date', 'label'=>'DateDebutPrestation', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1,),
		'date_fin' => array('type'=>'date', 'label'=>'DateFinPrestation', 'enabled'=>'1', 'position'=>31, 'notnull'=>1, 'visible'=>1,),
		'question1_1' => array('type'=>'integer', 'label'=>'Question1_1', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question1_2' => array('type'=>'integer', 'label'=>'Question1_2', 'enabled'=>'1', 'position'=>101, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question1_3' => array('type'=>'integer', 'label'=>'Question1_3', 'enabled'=>'1', 'position'=>102, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_theme1' => array('type'=>'integer', 'label'=>'NoteTheme1', 'enabled'=>'1', 'position'=>103, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question2_1' => array('type'=>'integer', 'label'=>'Question2_1', 'enabled'=>'1', 'position'=>104, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question2_2' => array('type'=>'integer', 'label'=>'Question2_2', 'enabled'=>'1', 'position'=>105, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question2_3' => array('type'=>'integer', 'label'=>'Question2_3', 'enabled'=>'1', 'position'=>106, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question2_4' => array('type'=>'integer', 'label'=>'Question2_4', 'enabled'=>'1', 'position'=>107, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question2_5' => array('type'=>'integer', 'label'=>'Question2_5', 'enabled'=>'1', 'position'=>108, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_theme2' => array('type'=>'integer', 'label'=>'NoteTheme2', 'enabled'=>'1', 'position'=>109, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_1' => array('type'=>'integer', 'label'=>'Question3_1', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_2' => array('type'=>'integer', 'label'=>'Question3_2', 'enabled'=>'1', 'position'=>111, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_3' => array('type'=>'integer', 'label'=>'Question3_3', 'enabled'=>'1', 'position'=>112, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_4' => array('type'=>'integer', 'label'=>'Question3_4', 'enabled'=>'1', 'position'=>113, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_5' => array('type'=>'integer', 'label'=>'Question3_5', 'enabled'=>'1', 'position'=>114, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_6' => array('type'=>'integer', 'label'=>'Question3_6', 'enabled'=>'1', 'position'=>115, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_7' => array('type'=>'integer', 'label'=>'Question3_7', 'enabled'=>'1', 'position'=>116, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question3_8' => array('type'=>'integer', 'label'=>'Question3_8', 'enabled'=>'1', 'position'=>117, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_theme3' => array('type'=>'integer', 'label'=>'NoteTheme3', 'enabled'=>'1', 'position'=>118, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question4_1' => array('type'=>'integer', 'label'=>'Question4_1', 'enabled'=>'1', 'position'=>119, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question4_2' => array('type'=>'integer', 'label'=>'Question4_2', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question4_3' => array('type'=>'integer', 'label'=>'Question4_3', 'enabled'=>'1', 'position'=>121, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question4_4' => array('type'=>'integer', 'label'=>'Question4_4', 'enabled'=>'1', 'position'=>122, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_theme4' => array('type'=>'integer', 'label'=>'NoteTheme4', 'enabled'=>'1', 'position'=>123, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question5_1' => array('type'=>'integer', 'label'=>'Question5_1', 'enabled'=>'1', 'position'=>124, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question5_2' => array('type'=>'integer', 'label'=>'Question5_2', 'enabled'=>'1', 'position'=>125, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_theme5' => array('type'=>'integer', 'label'=>'NoteTheme5', 'enabled'=>'1', 'position'=>126, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question6_1' => array('type'=>'integer', 'label'=>'Question6_1', 'enabled'=>'1', 'position'=>127, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question6_2' => array('type'=>'integer', 'label'=>'Question6_2', 'enabled'=>'1', 'position'=>128, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question6_3' => array('type'=>'integer', 'label'=>'Question6_3', 'enabled'=>'1', 'position'=>129, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question6_4' => array('type'=>'integer', 'label'=>'Question6_4', 'enabled'=>'1', 'position'=>130, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_theme6' => array('type'=>'integer', 'label'=>'NoteTheme6', 'enabled'=>'1', 'position'=>132, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question7_1' => array('type'=>'integer', 'label'=>'Question7_1', 'enabled'=>'1', 'position'=>133, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'question7_2' => array('type'=>'integer', 'label'=>'Question7_2', 'enabled'=>'1', 'position'=>134, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_theme7' => array('type'=>'integer', 'label'=>'NoteTheme7', 'enabled'=>'1', 'position'=>135, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'note_globale' => array('type'=>'integer', 'label'=>'NoteGlobale', 'enabled'=>'1', 'position'=>136, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('3'=>'A', '2'=>'B', '1'=>'C', '0'=>'D'),),
		'type' => array('type'=>'integer', 'label'=>'Type', 'enabled'=>'1', 'position'=>21, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('1'=>'FEP', '2'=>'QS'),),
		'contrat' => array('type'=>'integer:Contrat:contrat/class/contrat.class.php', 'label'=>'Contrat', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>1,),
		'date_transmission' => array('type'=>'date', 'label'=>'DateTransmission', 'enabled'=>'1', 'position'=>32, 'notnull'=>1, 'visible'=>1,),
		'date_reponse' => array('type'=>'date', 'label'=>'DateReponse', 'enabled'=>'1', 'position'=>33, 'notnull'=>0, 'visible'=>1,),
		'constat1' => array('type'=>'text', 'label'=>'Constat1', 'enabled'=>'1', 'position'=>150, 'notnull'=>0, 'visible'=>3,),
		'prop_amelioration1' => array('type'=>'text', 'label'=>'PropAmelioration1', 'enabled'=>'1', 'position'=>151, 'notnull'=>0, 'visible'=>3,),
		'constat2' => array('type'=>'text', 'label'=>'Constat2', 'enabled'=>'1', 'position'=>152, 'notnull'=>0, 'visible'=>3,),
		'prop_amelioration2' => array('type'=>'text', 'label'=>'PropAmelioration2', 'enabled'=>'1', 'position'=>153, 'notnull'=>0, 'visible'=>3,),
		'constat3' => array('type'=>'text', 'label'=>'Constat3', 'enabled'=>'1', 'position'=>154, 'notnull'=>0, 'visible'=>3,),
		'prop_amelioration3' => array('type'=>'text', 'label'=>'PropAmelioration3', 'enabled'=>'1', 'position'=>155, 'notnull'=>0, 'visible'=>3,),
		'constat4' => array('type'=>'text', 'label'=>'Constat4', 'enabled'=>'1', 'position'=>156, 'notnull'=>0, 'visible'=>3,),
		'prop_amelioration4' => array('type'=>'text', 'label'=>'PropAmelioration4', 'enabled'=>'1', 'position'=>157, 'notnull'=>0, 'visible'=>3,),
		'date_publication' => array('type'=>'date', 'label'=>'DatePublication', 'enabled'=>'1', 'position'=>34, 'notnull'=>0, 'visible'=>1,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>600, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'En attente de commentaire', '2'=>'Réponse à envoyer', '4'=>'Publier'),),
		'domaine_activite' => array('type'=>'varchar(128)', 'label'=>'DomaineActivite', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'irregularite_cfsi' => array('type'=>'boolean', 'label'=>'IrregulariteCFSI', 'enabled'=>'1', 'position'=>51, 'notnull'=>0, 'visible'=>1, 'help'=>"Contrefaçon, fraude sur produit et/ou sur document, produit suspect",),
	);
	public $rowid;
	public $ref;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $date_debut;
	public $date_fin;
	public $question1_1;
	public $question1_2;
	public $question1_3;
	public $note_theme1;
	public $question2_1;
	public $question2_2;
	public $question2_3;
	public $question2_4;
	public $question2_5;
	public $note_theme2;
	public $question3_1;
	public $question3_2;
	public $question3_3;
	public $question3_4;
	public $question3_5;
	public $question3_6;
	public $question3_7;
	public $question3_8;
	public $note_theme3;
	public $question4_1;
	public $question4_2;
	public $question4_3;
	public $question4_4;
	public $note_theme4;
	public $question5_1;
	public $question5_2;
	public $note_theme5;
	public $question6_1;
	public $question6_2;
	public $question6_3;
	public $question6_4;
	public $note_theme6;
	public $question7_1;
	public $question7_2;
	public $note_theme7;
	public $note_globale;
	public $type;
	public $contrat;
	public $date_transmission;
	public $date_reponse;
	public $constat1;
	public $prop_amelioration1;
	public $constat2;
	public $prop_amelioration2;
	public $constat3;
	public $prop_amelioration3;
	public $constat4;
	public $prop_amelioration4;
	public $date_publication;
	public $status;
	public $domaine_activite;
	public $irregularite_cfsi;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var string    Name of subtable line
	 */
	//public $table_element_line = 'fep_commande';

	/**
	 * @var string    Field with ID of parent key if this object has a parent
	 */
	public $fk_element = 'fk_fep';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'FEPline';

     /**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	//protected $childtables = array('fep_commande');

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	protected $childtablesoncascade = array('fep_commande');

	// /**
	//  * @var FEPLine[]     Array of subtable lines
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
		/*if ($user->rights->fep->fep->read) {
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
		if(!$this->verifRef(GETPOST('ref'))){
			setEventMessages('Cette réf existe déja', null, 'errors');
			return -1;
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
		if(!$this->verifRef(GETPOST('ref'))){
			setEventMessages('Cette réf existe déja', null, 'errors');
			return -1;
		}

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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->fep->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->fep->fep_advance->validate))))
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
				$result = $this->call_trigger('FEP_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'fep/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'fep/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->fep->dir_output.'/fep/'.$oldref;
				$dirdest = $conf->fep->dir_output.'/fep/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->fep->dir_output.'/fep/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
	 *	Transmettre au RA
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function transmettreRA($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_COMMENTAIRE) {
			dol_syslog(get_class($this)."::Le passage au RA a été abandonné, la FEP (/QS) est déja dans ce statut", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Transmettre au RA
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_COMMENTAIRE;
		$sql .= " WHERE rowid = ".((int) $this->id);
		
		dol_syslog(get_class($this)."::transmettreRA()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FEP_COMMENTAIRE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new status
		if (!$error) {
			$this->status = self::STATUS_COMMENTAIRE;
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
	 *	Transmettre au RA
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function commentaireFait($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_REPONSE) {
			dol_syslog(get_class($this)."::Le passage au statut 'Réponse à envoyer' a été abandonné, la FEP (/QS) est déja dans ce statut", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Passage en statut 'réponse à envoyer'
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_REPONSE;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::commentaireFait()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FEP_REPONSE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new status
		if (!$error) {
			$this->status = self::STATUS_REPONSE;
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
	 *	Transmettre au RA
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *  @param		int		$nodate			1=Does not change date, 0= change date
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function publier($user, $notrigger = 0, $nodate = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_PUBLIER) {
			dol_syslog(get_class($this)."::Le passage au statut 'Publié' a été abandonné, la FEP (/QS) est déja dans ce statut", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Passage en statut 'réponse à envoyer'
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_PUBLIER;
		if(!$nodate){
			$sql .= ", date_publication = '".substr($this->db->idate($now), 0, 10)."'";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::publier()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FEP_PUBLIER', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new status
		if (!$error) {
			$this->status = self::STATUS_PUBLIER;
			if(!$nodate){
				$this->date_publication = substr($this->db->idate($now), 0, 10);
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->fep_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'FEP_UNVALIDATE');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->fep_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'FEP_CANCEL');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fep->fep_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'FEP_REOPEN');
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

		$label = img_picto('', 'object_fep_16@fep').' <u>'.$langs->trans("FEP").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/fep/fep_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowFEP");
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
				$result .= img_object(($notooltip ? '' : $label), 'fep_16@fep', ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
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
		$hookmanager->initHooks(array('fepdao'));
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
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("fep@fep");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_COMMENTAIRE] = $langs->trans('En attente de commentaire');
			$this->labelStatus[self::STATUS_REPONSE] = $langs->trans('Réponse à envoyer');
			$this->labelStatus[self::STATUS_PUBLIER] = $langs->trans('Publié');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');

			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_COMMENTAIRE] = $langs->trans('En attente de commentaire');
			$this->labelStatusShort[self::STATUS_REPONSE] = $langs->trans('Réponse à envoyer');
			$this->labelStatusShort[self::STATUS_PUBLIER] = $langs->trans('Publié');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}
		if ($status == self::STATUS_COMMENTAIRE) {
			$statusType = 'status3';
		}
		if ($status == self::STATUS_REPONSE) {
			$statusType = 'status1';
		}
		if ($status == self::STATUS_PUBLIER) {
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
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new FEPLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_fep = '.$this->id));

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
		$langs->load("fep@fep");

		if (empty($conf->global->FEP_FEP_ADDON)) {
			$conf->global->FEP_FEP_ADDON = 'mod_fep_standard';
		}

		if (!empty($conf->global->FEP_FEP_ADDON)) {
			$mybool = false;

			$file = $conf->global->FEP_FEP_ADDON.".php";
			$classname = $conf->global->FEP_FEP_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/fep/");

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

		$langs->load("fep@fep");

		if (!dol_strlen($modele)) {
			$modele = 'standard_fep';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->FEP_ADDON_PDF)) {
				$modele = $conf->global->FEP_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/fep/doc/";

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
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param  array   		$val	       Array of properties for field to show (used only if ->fields not defined)
	 * @param  string  		$key           Key of attribute
	 * @param  string|array	$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param  string  		$moreparam     To add more parameters on html input tag
	 * @param  string  		$keysuffix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$morecss       Value for css to define style/length of field. May also be a numeric.
	 * @param  int			$nonewbutton   Force to not show the new button on field that are links to object
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		if (!empty($this->fields)) {
			$val = $this->fields[$key];
		}

		$out = '';
		$type = '';
		$isDependList=0;
		$param = array();
		$param['options'] = array();
		$reg = array();
		$size = !empty($this->fields[$key]['size']) ? $this->fields[$key]['size'] : 0;
		// Because we work on extrafields
		if (preg_match('/^(integer|link):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4].':'.$reg[5] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4].':'.$reg[5] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/varchar\((\d+)\)/', $val['type'], $reg)) {
			$param['options'] = array();
			$type = 'varchar';
			$size = $reg[1];
		} elseif (preg_match('/varchar/', $val['type'])) {
			$param['options'] = array();
			$type = 'varchar';
		} else {
			$param['options'] = array();
			$type = $this->fields[$key]['type'];
		}

		// Special case that force options and type ($type can be integer, varchar, ...)
		if (!empty($this->fields[$key]['arrayofkeyval']) && is_array($this->fields[$key]['arrayofkeyval'])) {
			$param['options'] = $this->fields[$key]['arrayofkeyval'];
			$type = 'select';
		}

		$label = $this->fields[$key]['label'];
		//$elementtype=$this->fields[$key]['elementtype'];	// Seems not used
		$default = (!empty($this->fields[$key]['default']) ? $this->fields[$key]['default'] : '');
		$computed = (!empty($this->fields[$key]['computed']) ? $this->fields[$key]['computed'] : '');
		$unique = (!empty($this->fields[$key]['unique']) ? $this->fields[$key]['unique'] : 0);
		$required = (!empty($this->fields[$key]['required']) ? $this->fields[$key]['required'] : 0);
		$autofocusoncreate = (!empty($this->fields[$key]['autofocusoncreate']) ? $this->fields[$key]['autofocusoncreate'] : 0);

		$langfile = (!empty($this->fields[$key]['langfile']) ? $this->fields[$key]['langfile'] : '');
		$list = (!empty($this->fields[$key]['list']) ? $this->fields[$key]['list'] : 0);
		$hidden = (in_array(abs($this->fields[$key]['visible']), array(0, 2)) ? 1 : 0);

		$objectid = $this->id;

		if ($computed) {
			if (!preg_match('/^search_/', $keyprefix)) {
				return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
			} else {
				return '';
			}
		}

		// Set value of $morecss. For this, we use in priority showsize from parameters, then $val['css'] then autodefine
		if (empty($morecss) && !empty($val['css'])) {
			$morecss = $val['css'];
		} elseif (empty($morecss)) {
			if ($type == 'date') {
				$morecss = 'minwidth100imp';
			} elseif ($type == 'datetime' || $type == 'link') {	// link means an foreign key to another primary id
				$morecss = 'minwidth200imp';
			} elseif (in_array($type, array('int', 'integer', 'price')) || preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $type)) {
				$morecss = 'maxwidth75';
			} elseif ($type == 'url') {
				$morecss = 'minwidth400';
			} elseif ($type == 'boolean') {
				$morecss = '';
			} else {
				if (round($size) < 12) {
					$morecss = 'minwidth100';
				} elseif (round($size) <= 48) {
					$morecss = 'minwidth200';
				} else {
					$morecss = 'minwidth400';
				}
			}
		}

		if (in_array($type, array('date'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 0;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') {
				$value = '-1';
			}

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1);
		} elseif (in_array($type, array('datetime'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 1;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') $value = '-1';

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1, '', '', '', 1, '', '', 'tzuserrel');
		} elseif (in_array($type, array('duration'))) {
			$out = $form->select_duration($keyprefix.$key.$keysuffix, $value, 0, 'text', 0, 1);
		} elseif (in_array($type, array('int', 'integer'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"'.($newsize > 0 ? ' maxlength="'.$newsize.'"' : '').' value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (in_array($type, array('real'))) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (preg_match('/varchar/', $type)) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"'.($size > 0 ? ' maxlength="'.$size.'"' : '').' value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (in_array($type, array('mail', 'phone', 'url'))) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (preg_match('/^text/', $type)) {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
				$out = $doleditor->Create(1);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif (preg_match('/^html/', $type)) {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled) && $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_5, '90%');
				$out = $doleditor->Create(1);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked value="1" ';
			} else {
				$checked = ' value="1" ';
			}
			$out = '<input type="checkbox" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam ? $moreparam : '').'>';
		} elseif ($type == 'price') {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> '.$langs->getCurrencySymbol($conf->currency);
		} elseif (preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $type)) {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> ';
		} elseif ($type == 'select') {
			$out = '';
			if (!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if ((!isset($this->fields[$key]['default'])) || ($this->fields[$key]['notnull'] != 1)) {
				$out .= '<option value="">&nbsp;</option>';
			}
			foreach ($param['options'] as $key => $val) {
				if ((string) $key == '') {
					continue;
				}
				list($val, $parent) = explode('|', $val);
				$out .= '<option value="'.$key.'"';
				$out .= (((string) $value == (string) $key) ? ' selected' : '');
				$out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
				$out .= '>'.$val.'</option>';
			}
			$out .= '</select>';
		} elseif ($type == 'sellist') {
			$out = '';
			if (!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}

				$fields_label = explode('|', $InfoFieldList[1]);
				if (is_array($fields_label)) {
					$keyList .= ', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere = '';
				$sql = 'SELECT '.$keyList;
				$sql .= ' FROM '.MAIN_DB_PREFIX.$InfoFieldList[0];
				if (!empty($InfoFieldList[4])) {
					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$') !== false) {
						$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
						$InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
					} else {
						$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
					}

					//We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra') !== false) {
						$sql .= ' as main, '.MAIN_DB_PREFIX.$InfoFieldList[0].'_extrafields as extra';
						$sqlwhere .= ' WHERE extra.fk_object=main.'.$InfoFieldList[2].' AND '.$InfoFieldList[4];
					} else {
						$sqlwhere .= ' WHERE '.$InfoFieldList[4];
					}
				} else {
					$sqlwhere .= ' WHERE 1=1';
				}
				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0], array('tablewithentity'))) {
					$sqlwhere .= ' AND entity = '.$conf->entity;
				}
				$sql .= $sqlwhere;
				//print $sql;

				$sql .= ' ORDER BY '.implode(', ', $fields_label);

				dol_syslog(get_class($this).'::showInputField type=sellist', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$out .= '<option value="0">&nbsp;</option>';
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$labeltoshow = '';
						$obj = $this->db->fetch_object($resql);

						// Several field into label (eq table:code|libelle:rowid)
						$notrans = false;
						$fields_label = explode('|', $InfoFieldList[1]);
						if (count($fields_label) > 1) {
							$notrans = true;
							foreach ($fields_label as $field_toshow) {
								$labeltoshow .= $obj->$field_toshow.' ';
							}
						} else {
							$labeltoshow = $obj->{$InfoFieldList[1]};
						}
						$labeltoshow = dol_trunc($labeltoshow, 45);

						if ($value == $obj->rowid) {
							foreach ($fields_label as $field_toshow) {
								$translabel = $langs->trans($obj->$field_toshow);
								if ($translabel != $obj->$field_toshow) {
									$labeltoshow = dol_trunc($translabel).' ';
								} else {
									$labeltoshow = dol_trunc($obj->$field_toshow).' ';
								}
							}
							$out .= '<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
						} else {
							if (!$notrans) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$labeltoshow = dol_trunc($translabel, 18);
								} else {
									$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]});
								}
							}
							if (empty($labeltoshow)) {
								$labeltoshow = '(not defined)';
							}
							if ($value == $obj->rowid) {
								$out .= '<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
							}

							if (!empty($InfoFieldList[3]) && $parentField) {
								$parent = $parentName.':'.$obj->{$parentField};
								$isDependList=1;
							}

							$out .= '<option value="'.$obj->rowid.'"';
							$out .= ($value == $obj->rowid ? ' selected' : '');
							$out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
							$out .= '>'.$labeltoshow.'</option>';
						}

						$i++;
					}
					$this->db->free($resql);
				} else {
					print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
				}
			}
			$out .= '</select>';
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', $value);
			$out = $form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options']) ?null:$param['options']), $value_arr, '', 0, '', 0, '100%');
		} elseif ($type == 'radio') {
			$out = '';
			foreach ($param['options'] as $keyopt => $val) {
				$out .= '<input class="flat '.$morecss.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '');
				$out .= ' value="'.$keyopt.'"';
				$out .= ' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
				$out .= ($value == $keyopt ? 'checked' : '');
				$out .= '/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$val.'</label><br>';
			}
		} elseif ($type == 'chkbxlst') {
			if (is_array($value)) {
				$value_arr = $value;
			} else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list ($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}

				$fields_label = explode('|', $InfoFieldList[1]);
				if (is_array($fields_label)) {
					$keyList .= ', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere = '';
				$sql = 'SELECT '.$keyList;
				$sql .= ' FROM '.MAIN_DB_PREFIX.$InfoFieldList[0];
				if (!empty($InfoFieldList[4])) {
					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$') !== false) {
						$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
						$InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
					} else {
						$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
					}

					// We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra') !== false) {
						$sql .= ' as main, '.MAIN_DB_PREFIX.$InfoFieldList[0].'_extrafields as extra';
						$sqlwhere .= ' WHERE extra.fk_object=main.'.$InfoFieldList[2].' AND '.$InfoFieldList[4];
					} else {
						$sqlwhere .= ' WHERE '.$InfoFieldList[4];
					}
				} else {
					$sqlwhere .= ' WHERE 1=1';
				}
				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0], array('tablewithentity'))) {
					$sqlwhere .= ' AND entity = '.$conf->entity;
				}
				// $sql.=preg_replace('/^ AND /','',$sqlwhere);
				// print $sql;

				$sql .= $sqlwhere;
				dol_syslog(get_class($this).'::showInputField type=chkbxlst', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;

					$data = array();

					while ($i < $num) {
						$labeltoshow = '';
						$obj = $this->db->fetch_object($resql);

						$notrans = false;
						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);
						if (count($fields_label) > 1) {
							$notrans = true;
							foreach ($fields_label as $field_toshow) {
								$labeltoshow .= $obj->$field_toshow.' ';
							}
						} else {
							$labeltoshow = $obj->{$InfoFieldList[1]};
						}
						$labeltoshow = dol_trunc($labeltoshow, 45);

						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							foreach ($fields_label as $field_toshow) {
								$translabel = $langs->trans($obj->$field_toshow);
								if ($translabel != $obj->$field_toshow) {
									$labeltoshow = dol_trunc($translabel, 18).' ';
								} else {
									$labeltoshow = dol_trunc($obj->$field_toshow, 18).' ';
								}
							}

							$data[$obj->rowid] = $labeltoshow;
						} else {
							if (!$notrans) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$labeltoshow = dol_trunc($translabel, 18);
								} else {
									$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
								}
							}
							if (empty($labeltoshow)) {
								$labeltoshow = '(not defined)';
							}

							if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
								$data[$obj->rowid] = $labeltoshow;
							}

							if (!empty($InfoFieldList[3]) && $parentField) {
								$parent = $parentName.':'.$obj->{$parentField};
								$isDependList=1;
							}

							$data[$obj->rowid] = $labeltoshow;
						}

						$i++;
					}
					$this->db->free($resql);

					$out = $form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');
				} else {
					print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
				}
			}
		} elseif ($type == 'link') {
			$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath[:AddCreateButtonOrNot[:Filter]]'
			$param_list_array = explode(':', $param_list[0]);
			$showempty = (($required && $default != '') ? 0 : 1);

			if (!preg_match('/search_/', $keyprefix)) {
				if (!empty($param_list_array[2])) {		// If the entry into $fields is set to add a create button
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusxx';
					} else {
						$morecss .= ' widthcentpercentminusx';
					}
				} else {
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusx';
					}
				}
			}

			$out = $form->selectForForms($param_list[0], $keyprefix.$key.$keysuffix, $value, $showempty, '', '', $morecss, $moreparam, 0, empty($val['disabled']) ? 0 : 1);

			if (!empty($param_list_array[2])) {		// If the entry into $fields is set to add a create button
				if (!GETPOSTISSET('backtopage') && empty($val['disabled']) && empty($nonewbutton)) {	// To avoid to open several times the 'Create Object' button and to avoid to have button if field is protected by a "disabled".
					list($class, $classfile) = explode(':', $param_list[0]);
					if (file_exists(dol_buildpath(dirname(dirname($classfile)).'/card.php'))) {
						$url_path = dol_buildpath(dirname(dirname($classfile)).'/card.php', 1);
					} else {
						$url_path = dol_buildpath(dirname(dirname($classfile)).'/'.strtolower($class).'_card.php', 1);
					}
					$paramforthenewlink = '';
					$paramforthenewlink .= (GETPOSTISSET('action') ? '&action='.GETPOST('action', 'aZ09') : '');
					$paramforthenewlink .= (GETPOSTISSET('id') ? '&id='.GETPOST('id', 'int') : '');
					$paramforthenewlink .= '&fk_'.strtolower($class).'=--IDFORBACKTOPAGE--';
					// TODO Add Javascript code to add input fields already filled into $paramforthenewlink so we won't loose them when going back to main page
					$out .= '<a class="butActionNew" title="'.$langs->trans("New").'" href="'.$url_path.'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF'].($paramforthenewlink ? '?'.$paramforthenewlink : '')).'"><span class="fa fa-plus-circle valignmiddle"></span></a>';
				}
			}
		} elseif ($type == 'password') {
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			$out = '<input type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
		} elseif ($type == 'array') {
			$newval = $val;
			$newval['type'] = 'varchar(256)';

			$out = '';
			if (!empty($value)) {
				foreach ($value as $option) {
					$out .= '<span><a class="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
					$out .= $this->showInputField($newval, $keyprefix.$key.$keysuffix.'[]', $option, $moreparam, '', '', $morecss).'<br></span>';
				}
			}
			$out .= '<a id="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_add" href="javascript:;"><span class="fa fa-plus-circle valignmiddle"></span></a>';

			$newInput = '<span><a class="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
			$newInput .= $this->showInputField($newval, $keyprefix.$key.$keysuffix.'[]', '', $moreparam, '', '', $morecss).'<br></span>';

			if (!empty($conf->use_javascript_ajax)) {
				$out .= '
					<script>
					$(document).ready(function() {
						$("a#'.dol_escape_js($keyprefix.$key.$keysuffix).'_add").click(function() {
							$("'.dol_escape_js($newInput).'").insertBefore(this);
						});

						$(document).on("click", "a.'.dol_escape_js($keyprefix.$key.$keysuffix).'_del", function() {
							$(this).parent().remove();
						});
					});
					</script>';
			}
		}
		if (!empty($hidden)) {
			$out = '<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
		}

		if ($isDependList==1) {
			$out .= $this->getJSListDependancies('_common');
		}
		/* Add comments
		 if ($type == 'date') $out.=' (YYYY-MM-DD)';
		 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
		 */
		return $out;
	}

	/**
	 * 	Return la liste des commandes liées à la FEP
	 *
	 * 	@param	string	$excludefilter		Filter to exclude. Do not use here a string coming from user input.
	 *  @param	int		$mode				0=Return array of Commande instance, 1=Return array of Commande id only
	 * 	@return	mixed						Array of Commande or -1 on error
	 */
	public function getCommande($excludefilter = '', $mode = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT f.fk_commande";
		
		$sql .= " FROM ".MAIN_DB_PREFIX."fep_commande as f";
		$sql .= " WHERE 1 = 1";
		if (!empty($this->id)) {
			$sql .= " AND f.fk_fep = ".$this->id;
		}
		if (!empty($excludefilter)) {
			$sql .= ' AND ('.$excludefilter.')';
		}

		dol_syslog(get_class($this)."::getCommande", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					if ($mode != 1) {
						$commande = new Commande($this->db);
						$commande->fetch($obj->fk_commande);
						$ret[] = $commande;
					} else {
						$ret[] = $obj->fk_commande;
					}
				}
			}
			$this->db->free($resql);
			return $ret;
		} 
		else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Ajoute une commande liée à la FEP
	 *
	 *  @param		int 	$idcommande 	id de la commande
	 * 	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function addCommande($idcommande)
	{
		global $conf, $user;

		$error = 0;

		if ($idcommande > 0 && $this->id > 0){

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."fep_commande";
			$sql .= " VALUES (".$this->id.', '.$idcommande.')';

			dol_syslog(get_class($this)."::addCommande", LOG_DEBUG);
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
	 * 	Supprimer une commande liée à la FEP
	 *
	 *  @param		int 	$idcommande 	id de la commande
	 * 	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function removeCommande($idcommande)
	{
		global $conf, $user;

		$error = 0;

		if ($idcommande > 0 && $this->id > 0){

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fep_commande";
			$sql .= " WHERE fk_fep = ".$this->id;
			$sql .= " AND fk_commande = ".$idcommande;

			dol_syslog(get_class($this)."::addCommande", LOG_DEBUG);
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
	 * 	Return les id des FEP d'une commande
	 *
	 * 	@param	int		$idcommande			Id du commande à laquelle les FEP sont rattachées
	 * 	@return	array(int)					tableau d'Id des fod 
	 */
	public function getListIdByCommande($idcommande = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT f.fk_fep";
		$sql .= " FROM ".MAIN_DB_PREFIX."fep_commande as f";
		$sql .= " WHERE f.fk_commande =".$idcommande;

		dol_syslog(get_class($this)."::getListIdByCommande", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i=0;
			while($i < $num){ 
				$obj = $this->db->fetch_object($resql);
				$ret[] = $obj->fk_fep;
				$i++;
			}
			$this->db->free($resql);
			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Envoi un mail pour les FEP dont la date de transmission était il y a $jour jours et qui n'a toujours pas été publié
	 *
	 * 	@param 		int 	nombre de jour
	 *  @return		int     resultat
	 */
	public function MailFEPdepassement_Publication($jour)
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$sql = "SELECT f.rowid, f.date_transmission, f.date_publication";
		$sql .= " FROM ".MAIN_DB_PREFIX."fep_fep as f";
		$sql .= " WHERE f.status != ".Self::STATUS_PUBLIER;
		$sql .= " AND f.date_publication IS NULL";

		dol_syslog(get_class($this)."::MailFEPdepassement_Publication", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$date = $obj->date_transmission;
				$date_actuelle = dol_mktime(-1, -1, -1, substr($this->db->idate(dol_now()), 5, 6), substr($this->db->idate(dol_now()), 8, 9), substr($this->db->idate(dol_now()), 0, 4));
				$date_10J = dol_time_plus_duree($this->db->jdate($date), $jour, 'd');
				
				if ($date_10J == $date_actuelle) {
					$subject = '[OPTIM Industries] Notification automatique FEP';
					$from = 'erp@optim-industries.fr';
					
					$user_group = New UserGroup($this->db);
					$user_group->fetch('', 'Q3SE');
					
					$liste_qualite = $user_group->listUsersForGroup();
					foreach($liste_qualite as $qualite){
						if(!empty($qualite->email && $qualite->status > 0)){
							$to .= $qualite->email;
							$to .= ", ";
						}
					}
					$to = rtrim($to, ", ");

					$fep = New FEP($this->db);
					$fep->fetch($obj->rowid);
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$fep->id.'">'.$fep->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFEPdepassement_Publication", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $mail->sendfile();
					}
				}
			}
			$this->db->free($result);
			if($res){
				return 0;
			}
			else return -1;
		} 
		else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Envoi un mail au RAF pour les FEP dont la date de transmission était il y a $jour jours et qui n'a toujours pas été commenté
	 *
	 *  @param 		int 	nombre de jour
	 *  @return		int     resultat
	 */
	public function MailFEPdepassement_Commentaire($jour)
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$sql = "SELECT f.rowid, f.date_transmission, f.date_reponse";
		$sql .= " FROM ".MAIN_DB_PREFIX."fep_fep as f";
		$sql .= " WHERE f.status != ".Self::STATUS_PUBLIER;
		$sql .= " AND f.date_reponse IS NULL";

		dol_syslog(get_class($this)."::MailFEPdepassement_10J", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$date = $obj->date_transmission;
				$date_actuelle = dol_mktime(-1, -1, -1, substr($this->db->idate(dol_now()), 5, 6), substr($this->db->idate(dol_now()), 8, 9), substr($this->db->idate(dol_now()), 0, 4));
				$date_10J = dol_time_plus_duree($this->db->jdate($date), $jour, 'd');
				if ($date_10J == $date_actuelle) {
					$subject = '[OPTIM Industries] Notification automatique FEP';
					$from = 'erp@optim-industries.fr';

					$fep = New FEP($this->db);
					$fep->fetch($obj->rowid);
					$liste_commande = $fep->getCommande();
					if(!empty($liste_commande)){
						$projectstatic = New Project($this->db);
						$projectstatic->fetch($liste_commande[0]->fk_project);
						$liste_chef_projet = $projectstatic->liste_contact(-1, 'internal', 1, 'PROJECTLEADER');
					}
					
					$to = '';
					foreach($liste_chef_projet as $chefprojet){
						$user_static = new User($this->db);
						$user_static->fetch($chefprojet);
						if(!empty($user_static->email)){
							$to .= $user_static->email;
							$to .= ', ';
						}
					}
					$to = rtrim($to, ", ");

					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fep/fep_card.php?id='.$fep->id.'">'.$fep->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFEPdepassement_Commentaire", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					if(!empty($to)){
						$res = $mail->sendfile();
					}
				}
			}
			$this->db->free($result);
			if($res){
				return 0;
			}
			else return -1;
		} 
		else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Vérifie si la réf n'est pas deja utilisée
	 *
	 *  @param		string		$ref 			Référence de la FEP
	 *  @return		int     					0 si déja utilisé, 1 sinon
	 */
	public function verifRef($ref)
	{
		global $conf, $user;

		$res = 1;

		$sql = "SELECT f.rowid, f.ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."fep_fep as f";
		$sql .= " WHERE f.ref = '".$ref."'";
		if(!empty($this->id)){
			$sql .= " AND f.rowid <> ".$this->id;
		}
	
		dol_syslog(get_class($this)."::verifRef", LOG_DEBUG);
		$result = $this->db->query($sql);
		
		if ($result) {
			if ($this->db->num_rows($resql) > 0){
				$res = 0;
			}
			$this->db->free($result);
			return $res;
		} 
		else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Return les nombre de FEP sur une commande
	 *
	 * 	@param	int		$idcommande			Id de la commande à laquelle les FEP sont rattachées
	 * 	@return	int							Nombre de FEP
	 */
	public function getNbFEP($idcommande)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT COUNT(f.fk_fep) as nb_fep";
		$sql .= " FROM ".MAIN_DB_PREFIX."fep_commande as f";
		$sql .= " WHERE f.fk_commande =".$idcommande;

		dol_syslog(get_class($this)."::getNbFEP", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if($num > 0){ 
				$obj = $this->db->fetch_object($resql);
				$this->db->free($resql);
				return $obj->nb_fep;
			}
			$this->db->free($resql);
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class FEPLine. You can also remove this and generate a CRUD class for lines objects.
 */
class FEPLine extends CommonObjectLine
{
	// To complete with content of an object FEPLine
	// We should have a field rowid, fk_fep and position

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
