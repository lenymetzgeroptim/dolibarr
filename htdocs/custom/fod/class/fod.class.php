<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 * \file        class/fod.class.php
 * \ingroup     fod
 * \brief       This file is a CRUD class file for Fod (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/extendeduser.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Fod
 */
class Fod extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'fod';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'fod';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'fod_fod';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = -1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for fod. Must be the part after the 'object_' into object_fod.png
	 */
	public $picto = 'fod_32@fod';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 4;
	const STATUS_VALIDATEDRA = 5;
	const STATUS_VALIDATEDRARSR = 6;
	const STATUS_VALIDATEDRSR = 7;
	const STATUS_AOA = 1;
	const STATUS_BILAN = 8;
	const STATUS_BILANinter = 9;
	const STATUS_BILANRSR = 10;
	const STATUS_BILANRSRRA = 11;
	const STATUS_BILANRSRRAPCR = 12;
	const STATUS_BILAN_REFUS = 15;
	const STATUS_CANCELED = 13;
	const STATUS_CLOTURE = 14;


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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>5, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object"),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>64, 'notnull'=>0, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>5, 'default'=>'0', 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'AOA', '4'=>'Valid&eacute;', '5'=>'Valid&eacute; par le RA', '6'=>'Valid&eacute; par le RA et le RSR', '7'=>'Valid&eacute; par le RSR', '8'=>'Bilan', '9'=>'Bilan réalisé par les intervenants', '10'=>'Bilan réalisé par le RSR', '11'=>'Bilan réalisé par le RSR et le RA', '12'=>'Bilan réalisé par le RSR, le RA, et le PCR', '14'=>'Cloturé', '15'=>'Bilan refusé'),),
		'fk_user_pcr' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'PCR', 'enabled'=>'1', 'position'=>53, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"Conseillé en Radioprotection",),
		'fk_user_rsr' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'RSR', 'enabled'=>'1', 'position'=>54, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"Responsable de Suivi Radiologique",),
		'fk_user_raf' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'RAF', 'enabled'=>'1', 'position'=>55, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"Responsable d'Affaire",),
		'debit_dose_estime' => array('type'=>'double(5,3)', 'label'=>'DebitDoseEstime', 'enabled'=>'1', 'position'=>66, 'notnull'=>1, 'visible'=>3, 'isameasure'=>'1', 'help'=>"mSv/h (min = 0.001 mSv/h)",),
		'duree_intervention' => array('type'=>'double(10,1)', 'label'=>'DureeIntervention', 'enabled'=>'1', 'position'=>68, 'notnull'=>1, 'visible'=>3, 'isameasure'=>'1', 'help'=>"h (min = 0.1h)",),
		'effectif' => array('type'=>'integer', 'label'=>'EffectifPrevisionnel', 'enabled'=>'1', 'position'=>65, 'notnull'=>1, 'visible'=>3,),
		'date_debut' => array('type'=>'date', 'label'=>'DateDebut', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"Date du début de l'intervention",),
		'date_fin' => array('type'=>'date', 'label'=>'DateFin', 'enabled'=>'1', 'position'=>63, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"Date de fin de l'intervention",),
		'coef_exposition' => array('type'=>'double(24,8)', 'label'=>'CoefficientExposition', 'enabled'=>'1', 'position'=>69, 'notnull'=>1, 'visible'=>3, 'help'=>"Le coefficient d'exposition permet de pondérer la durée collective de présence en ZC et préciser ainsi la durée d'exposition réelle au DED", 'arrayofkeyval'=>array('0'=>'0.1', '1'=>'0.2', '2'=>'0.3', '3'=>'0.4', '4'=>'0.5', '5'=>'0.6', '6'=>'0.7', '7'=>'0.8', '8'=>'0.9', '9'=>'1'),),
		'debit_dose_max' => array('type'=>'double(5,3)', 'label'=>'DebitDoseMax', 'enabled'=>'1', 'position'=>67, 'notnull'=>0, 'visible'=>3, 'isameasure'=>'1', 'help'=>"mSv/h",),
		'prop_radiologique' => array('type'=>'integer', 'label'=>'PropreteRadiologique', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>3, 'arrayofkeyval'=>array('1'=>'NC0', '2'=>'NC1', '3'=>'NC2', '4'=>'NC3'),),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1:(t.fk_statut:=:1)', 'label'=>'Project', 'enabled'=>'1', 'position'=>52, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'indice' => array('type'=>'varchar(1)', 'label'=>'Indice', 'enabled'=>'1', 'position'=>21, 'notnull'=>1, 'visible'=>1,),
		'risques' => array('type'=>'integer', 'label'=>'Risques', 'enabled'=>'1', 'position'=>71, 'notnull'=>1, 'visible'=>3, 'arrayofkeyval'=>array('1'=>'voir PdP et ADR Sécu', '2'=>'risques particuliers voir ci contre'),),
		'ri' => array('type'=>'integer', 'label'=>'RI', 'enabled'=>'1', 'position'=>75, 'notnull'=>1, 'visible'=>3, 'help'=>"RI = Rayonnements Ionisants", 'arrayofkeyval'=>array('1'=>'Gamma', '2'=>'Gamma Béta', '3'=>'Gamma Béta Neutron', '4'=>'Gamma Neutron', '5'=>'Gamma Béta Neutron Alpha'),),
		'rex' => array('type'=>'integer', 'label'=>'Rex', 'enabled'=>'1', 'position'=>73, 'notnull'=>1, 'visible'=>3, 'arrayofkeyval'=>array('1'=>'Nouvelle activité (pas de REX)', '2'=>'Type activité connue (REX pris en compte)', '3'=>'NA'),),
		'aoa' => array('type'=>'smallint', 'label'=>'AOA', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>5, 'default'=>'1', 'arrayofkeyval'=>array('1'=>'Optimisation faite', '2'=>'Optimisation approfondie à compléter', '3'=>'Optimisation approfondie faite'),),
		'ded_optimise' => array('type'=>'smallint', 'label'=>'DEDOptimise', 'enabled'=>'1', 'position'=>31, 'notnull'=>0, 'visible'=>6, 'default'=>'2', 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non'),),
		'ded_max_optimise' => array('type'=>'smallint', 'label'=>'DEDMaxOptimise', 'enabled'=>'1', 'position'=>31, 'notnull'=>0, 'visible'=>6, 'default'=>'2', 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non'),),
		'dc_optimise' => array('type'=>'smallint', 'label'=>'DCOptimise', 'enabled'=>'1', 'position'=>34, 'notnull'=>0, 'visible'=>6, 'default'=>'2', 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non'),),
		'cdd_optimise' => array('type'=>'smallint', 'label'=>'CDDOptimise', 'enabled'=>'1', 'position'=>35, 'notnull'=>0, 'visible'=>6, 'default'=>'2', 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non'),),
		'prop_rad_optimise' => array('type'=>'smallint', 'label'=>'PropRadOptimise', 'enabled'=>'1', 'position'=>38, 'notnull'=>0, 'visible'=>6, 'default'=>'2', 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non'),),
		'debit_dose_estime_optimise' => array('type'=>'double(5,3)', 'label'=>'DebitDoseEstimeOptimise', 'enabled'=>'1', 'position'=>33, 'notnull'=>0, 'visible'=>6, 'isameasure'=>'1', 'help'=>"mSv/h (min = 0.001 mSv/h)",),
		'prop_radiologique_optimise' => array('type'=>'integer', 'label'=>'PropreteRadiologiqueOptimise', 'enabled'=>'1', 'position'=>39, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'NC0', '2'=>'NC1', '3'=>'NC2', '4'=>'NC3', '5'=>'NA'),),
		'q1_doses_individuelles' => array('type'=>'integer', 'label'=>'Q1DosesIndividuelles', 'enabled'=>'1', 'position'=>200, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non : "à analyser"'),),
		'q2_doses_individuelles' => array('type'=>'integer', 'label'=>'Q2DosesIndividuelles', 'enabled'=>'1', 'position'=>201, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non "NC"', '3'=>'NA'),),
		'q3_doses_individuelles' => array('type'=>'integer', 'label'=>'Q3DosesIndividuelles', 'enabled'=>'1', 'position'=>202, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non "NC"', '3'=>'SO'),),
		'q1_dose_collective' => array('type'=>'integer', 'label'=>'Q1DoseCollective', 'enabled'=>'1', 'position'=>203, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non "NC"'),),
		'q2_dose_collective' => array('type'=>'integer', 'label'=>'Q2DoseCollective', 'enabled'=>'1', 'position'=>204, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non "NC"', '3'=>'SO'),),
		'q1_contamination' => array('type'=>'integer', 'label'=>'Q1Contamination', 'enabled'=>'1', 'position'=>205, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non "NC"'),),
		'q2_contamination' => array('type'=>'integer', 'label'=>'Q2Contamination', 'enabled'=>'1', 'position'=>206, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non "NC"', '3'=>'SO'),),
		'q1_siseri' => array('type'=>'integer', 'label'=>'Q1Siseri', 'enabled'=>'1', 'position'=>207, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui "C"', '2'=>'Non "NC"'),),
		'rex_rsr' => array('type'=>'integer', 'label'=>'RexRSR', 'enabled'=>'1', 'position'=>209, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Sans commentaires', '2'=>'Commentaires', '3'=>'Amélioration(s)', '4'=>'Action(s) corrective(s)', '5'=>'BVR'),),
		'rex_ra' => array('type'=>'integer', 'label'=>'RexRA', 'enabled'=>'1', 'position'=>211, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Sans commentaires', '2'=>'Commentaires', '3'=>'Amélioration(s)', '4'=>'Action(s) corrective(s)', '5'=>'BVR'),),
		'rex_pcr' => array('type'=>'integer', 'label'=>'RexPCR', 'enabled'=>'1', 'position'=>213, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Sans commentaires', '2'=>'Commentaires', '3'=>'Amélioration(s)', '4'=>'Action(s) corrective(s)', '5'=>'BVR'),),
		'rex_rd' => array('type'=>'integer', 'label'=>'RexRD', 'enabled'=>'1', 'position'=>214, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Sans commentaires', '2'=>'Commentaires', '3'=>'Amélioration(s)', '4'=>'Action(s) corrective(s)', '5'=>'BVR'),),
		'q1_radiopotection' => array('type'=>'integer', 'label'=>'Q1Radioprotection', 'enabled'=>'1', 'position'=>217, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non'),),
		'date_fin_prolong' => array('type'=>'date', 'label'=>'DateFinProlong', 'enabled'=>'1', 'position'=>95, 'notnull'=>0, 'visible'=>5,),
		'ref_rex' => array('type'=>'varchar(128)', 'label'=>'RefRex', 'enabled'=>'1', 'position'=>74, 'notnull'=>0, 'visible'=>3,),
		'objectif_proprete' => array('type'=>'integer', 'label'=>'ObjectifProprete', 'enabled'=>'1', 'position'=>77, 'notnull'=>1, 'visible'=>3, 'arrayofkeyval'=>array('1'=>'Standard et pas de dissémination de contamination labile', '2'=>'Autres à préciser en commentaires'),),
		'com_objectif_proprete' => array('type'=>'varchar(128)', 'label'=>'ComObjectifProprete', 'enabled'=>'1', 'position'=>78, 'notnull'=>0, 'visible'=>3,),
		'epi_specifique' => array('type'=>'integer', 'label'=>'EPIspecifique', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non', '3'=>'NA'),),
		'consignes_rp' => array('type'=>'integer', 'label'=>'ConsignesRP', 'enabled'=>'1', 'position'=>41, 'notnull'=>0, 'visible'=>6, 'arrayofkeyval'=>array('1'=>'Oui', '2'=>'Non', '3'=>'NA'),),
		'commentaire_aoa' => array('type'=>'text', 'label'=>'CommentaireAOA', 'enabled'=>'1', 'position'=>42, 'notnull'=>0, 'visible'=>6,),
		'effectif_optimise' => array('type'=>'integer', 'label'=>'EffectifOptimise', 'enabled'=>'1', 'position'=>36, 'notnull'=>0, 'visible'=>6,),
		'duree_intervention_optimise' => array('type'=>'double(10,1)', 'label'=>'DureeOptimise', 'enabled'=>'1', 'position'=>37, 'notnull'=>0, 'visible'=>6,),
		'historique' => array('type'=>'text', 'label'=>'Historique', 'enabled'=>'1', 'position'=>400, 'notnull'=>0, 'visible'=>0,),
		'commentaire_risque' => array('type'=>'varchar(256)', 'label'=>'CommentaireRisque', 'enabled'=>'1', 'position'=>72, 'notnull'=>0, 'visible'=>3,),
		'commentaire_ri' => array('type'=>'varchar(256)', 'label'=>'CommentaireRI', 'enabled'=>'1', 'position'=>76, 'notnull'=>0, 'visible'=>3, 'help'=>"RI = Rayonnements Ionisants",),
		'commentaire_fod' => array('type'=>'text', 'label'=>'CommentaireFOD', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>3,),
		'client_site' => array('type'=>'integer:Societe:societe/class/societe.class.php:1', 'label'=>'ClientSite', 'enabled'=>'1', 'position'=>10, 'notnull'=>0, 'visible'=>3,),
		'installation' => array('type'=>'varchar(128)', 'label'=>'Installation', 'enabled'=>'1', 'position'=>11, 'notnull'=>0, 'visible'=>3,),
		'etat_installation' => array('type'=>'integer', 'label'=>'EtatInstallation', 'enabled'=>'1', 'position'=>12, 'notnull'=>0, 'visible'=>3, 'arrayofkeyval'=>array('1'=>'TEM', '2'=>'AT', '3'=>'TEM et AT', '4'=>'Autre', '5'=>'NA'),),
		'commentaire_etat_installation' => array('type'=>'varchar(128)', 'label'=>'CommentaireEtatInstallation', 'enabled'=>'1', 'position'=>13, 'notnull'=>0, 'visible'=>3,),
		'activite' => array('type'=>'varchar(128)', 'label'=>'Activite', 'enabled'=>'1', 'position'=>14, 'notnull'=>0, 'visible'=>3,),
		'ref_doc_client' => array('type'=>'varchar(128)', 'label'=>'RefDocClient', 'enabled'=>'1', 'position'=>79, 'notnull'=>0, 'visible'=>3,),
		'debit_dose_max_optimise' => array('type'=>'double(5,3)', 'label'=>'DebitDoseMaxOptimise', 'enabled'=>'1', 'position'=>32, 'notnull'=>0, 'visible'=>6, 'help'=>"mSv/h",),
		'com_rex_rsr' => array('type'=>'varchar(255)', 'label'=>'ComREXRSR', 'enabled'=>'1', 'position'=>210, 'notnull'=>0, 'visible'=>6,),
		'com_rex_ra' => array('type'=>'varchar(255)', 'label'=>'ComREXRA', 'enabled'=>'1', 'position'=>212, 'notnull'=>0, 'visible'=>6,),
		'com_rex_pcr' => array('type'=>'varchar(255)', 'label'=>'ComREXPCR', 'enabled'=>'1', 'position'=>214, 'notnull'=>0, 'visible'=>6,),
		'com_rex_rd' => array('type'=>'varchar(255)', 'label'=>'ComREXRD', 'enabled'=>'1', 'position'=>216, 'notnull'=>0, 'visible'=>6,),
		'date_cloture' => array('type'=>'date', 'label'=>'DateCloture', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
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
	public $status;
	public $fk_user_pcr;
	public $fk_user_rsr;
	public $fk_user_raf;
	public $debit_dose_estime;
	public $duree_intervention;
	public $effectif;
	public $date_debut;
	public $date_fin;
	public $coef_exposition;
	public $debit_dose_max;
	public $prop_radiologique;
	public $fk_project;
	public $indice;
	public $risques;
	public $ri;
	public $rex;
	public $aoa;
	public $ded_optimise;
	public $ded_max_optimise;
	public $dc_optimise;
	public $cdd_optimise;
	public $prop_rad_optimise;
	public $debit_dose_estime_optimise;
	public $prop_radiologique_optimise;
	public $q1_doses_individuelles;
	public $q2_doses_individuelles;
	public $q3_doses_individuelles;
	public $q1_dose_collective;
	public $q2_dose_collective;
	public $q1_contamination;
	public $q2_contamination;
	public $q1_siseri;
	public $rex_rsr;
	public $rex_ra;
	public $rex_pcr;
	public $rex_rd;
	public $q1_radiopotection;
	public $date_fin_prolong;
	public $ref_rex;
	public $objectif_proprete;
	public $com_objectif_proprete;
	public $epi_specifique;
	public $consignes_rp;
	public $commentaire_aoa;
	public $effectif_optimise;
	public $duree_intervention_optimise;
	public $historique;
	public $commentaire_risque;
	public $commentaire_ri;
	public $commentaire_fod;
	public $client_site;
	public $installation;
	public $etat_installation;
	public $commentaire_etat_installation;
	public $activite;
	public $ref_doc_client;
	public $debit_dose_max_optimise;
	public $com_rex_rsr;
	public $com_rex_ra;
	public $com_rex_pcr;
	public $com_rex_rd;
	public $date_cloture;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'fod_fodline';

	/**
	 * @var string    Field with ID of parent key if this object has a parent
	 */
	public $fk_element = 'fk_fod';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Fodline';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array('fod_user', 'fod_dataintervenant');

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	 *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	 */
	protected $childtablesoncascade = array('fod_user');

	// /**
	//  * @var FodLine[]     Array of subtable lines
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
		/*if ($user->rights->fod->fod->read) {
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
		//$this->historique = '';
		$this->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Création de la FOD par '.$user->firstname.' '.$user->lastname.'</span><br>';
		
		$resultcreate = $this->createCommon($user, $notrigger);

		if($this->GetEnjeuRadiologique() == 2 || $this->GetEnjeuRadiologique() == 3){
			$resultaoa = $this->aoa($user);
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

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();
		/*if(!$this->VerifNumChrono(GETPOST('num_chrono'), $object->fk_project)){
			$error++;
			setEventMessages('Ce numéro chrono est deja utilisé sur cette affaire', null, 'errors');
		}

		if (GETPOST('num_chrono') < 1 ||  GETPOST('num_chrono') > 9999 || !is_numeric(GETPOST('num_chrono')) || strlen(GETPOST('num_chrono')) != 4) {
			$error++;
			setEventMessages($langs->trans("ErrorFormat", $langs->transnoentitiesnoconv('NumeroChrono')), null, 'errors');
		}*/

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);
		unset($object->date_fin_prolong);
		unset($object->q1_doses_individuelles);
		unset($object->q2_doses_individuelles);
		unset($object->q3_doses_individuelles);
		unset($object->q1_dose_collective);
		unset($object->q2_dose_collective);
		unset($object->q1_contamination);
		unset($object->q2_contamination);
		unset($object->q1_siseri);
		unset($object->rex_rsr);
		unset($object->com_rex_rsr);
		unset($object->rex_ra);
		unset($object->com_rex_ra);
		unset($object->rex_pcr);
		unset($object->com_rex_pcr);
		unset($object->rex_rd);
		unset($object->com_rex_rd);
		unset($object->q1_radiopotection);
		unset($object->note_public);
		unset($object->note_private);
		if($object->aoa == 2 || $object->aoa == 3){
			//$object->ded_max_optimise = 2;
			//$object->ded_optimise = 2;
			//$object->dc_optimise = 2;
			//$object->cdd_optimise = 2;
			//$object->prop_rad_optimise = 2;
			unset($object->ded_max_optimise);
			unset($object->ded_optimise);
			unset($object->dc_optimise);
			unset($object->cdd_optimise);
			unset($object->prop_rad_optimise);
			unset($object->debit_dose_max_optimise);
			unset($object->debit_dose_estime_optimise);
			unset($object->effectif_optimise);
			unset($object->duree_intervention_optimise);
			unset($object->prop_radiologique_optimise);
			unset($object->epi_specifique);
			unset($object->consignes_rp);
			unset($object->commentaire_aoa);
		}

		$object->historique = '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Création de la FOD</span><br>';

		$projet = New Project($this->db);
		$projet->fetch($object->fk_project);
		$refp = explode('-', $projet->ref);
		$nb_fod = $object->getNbFOD($projet->id) + 1;
		$object->ref = dol_string_nospecial('FOD '.str_replace(' ', '', $refp[0]).' '.str_pad($nb_fod, 4, "0", STR_PAD_LEFT));

		// Clear fields
		/*if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		}*/
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
			if($object->GetEnjeuRadiologique() == 2 || $object->GetEnjeuRadiologique() == 3){
				$object->status = self::STATUS_AOA;
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

		// Clone les intervenants
		$fod_user = New Fod_User($this->db);
		$liste_user = $fod_user->getListWithFod($fromid);
		$result = 1;
		foreach($liste_user as $user_fod){
			$result = $result && $fod_user->createFromClone_Fod($user, $user_fod, $object->id);
		}
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
	 * @param 	int    	$id   				Id object
	 * @param 	string 	$ref  				Ref
	 * @param 	boolean $load_intervenants 	Load all intervenants of the Fod
	 * @return 	int         				<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $load_intervenants = false)
	{

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}

		if ($result) {
			if ($load_intervenants) {
				$this->intervenants = $this->listIntervenantsForFod();
			}
			$this->enjeu_radiologique = $this->GetEnjeuRadiologique();

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
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
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $active = 0)
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
		if($active){
			$sql .= ' AND status = 4';
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
	public function update(User $user, $notrigger = false, $modif_historique = false)
	{
		$draft = 0;

		if(!$modif_historique){
			if ($this->aoa == 2){
				if(!empty($this->debit_dose_estime_optimise) && ($this->debit_dose_estime_optimise < $this->debit_dose_estime)){
					$this->ded_optimise = 1;
				}
				else {
					$this->ded_optimise = 2;
				}

				if(!empty($this->debit_dose_max_optimise) && ($this->debit_dose_max_optimise < $this->debit_dose_max)){
					$this->ded_max_optimise = 1;
				}
				else {
					$this->ded_max_optimise = 2;
				}

				if((!empty($this->debit_dose_estime_optimise) || !empty($this->duree_intervention_optimise)) && ($this->GetDoseCollectivePrevisionnelleOptimise() < $this->GetDoseCollectivePrevisionnelle())){
					$this->dc_optimise = 1;
				}
				else {
					$this->dc_optimise = 2;
				}

				if((!empty($this->debit_dose_estime_optimise) || !empty($this->duree_intervention_optimise) || !empty($this->effectif_optimise)) && ($this->GetDoseIndividuelleMaxOptimise() < $this->GetDoseIndividuelleMax())){
					$this->cdd_optimise = 1;
				}
				else {
					$this->cdd_optimise = 2;
				}

				if(isset($this->prop_radiologique_optimise) && $this->prop_radiologique_optimise != -1 && $this->prop_radiologique_optimise != 0 && ($this->prop_radiologique_optimise < $this->prop_radiologique)){
					$this->prop_rad_optimise = 1;
				}
				else {
					$this->prop_rad_optimise = 2;
				}
			}

			if(($this->GetEnjeuRadiologique() == 1 || $this->GetEnjeuRadiologique() == 0) && ($this->aoa == 2) && ($this->status == self::STATUS_AOA)){
				$this->ded_optimise = "";
				$this->ded_max_optimise = "";
				$this->debit_dose_max_optimise = "";
				$this->debit_dose_estime_optimise = "";
				$this->dc_optimise = "";
				$this->cdd_optimise = "";
				$this->effectif_optimise = "";
				$this->duree_intervention_optimise = "";
				$this->prop_rad_optimise = "";
				$this->prop_radiologique_optimise = "";
				$this->epi_specifique = "";
				$this->consignes_rp = "";
				$this->commentaire_aoa = "";
				$this->aoa = 1;
				$draft = 1;
			}
		}

		$resultupdate = $this->updateCommon($user, $notrigger);

		if(!$modif_historique){
			if(($this->GetEnjeuRadiologique() == 2 || $this->GetEnjeuRadiologique() == 3) && ($this->aoa == 1) && ($this->status == self::STATUS_DRAFT  || $this->status == self::STATUS_VALIDATEDRA || $this->status == self::STATUS_VALIDATEDRSR || $this->status == self::STATUS_VALIDATEDRARSR)){
				$resultaoa = $this->aoa($user);
				$resultupdate = $resultupdate && $resultaoa;
			}

			if($draft){
				$resultbtd = $this->setDraft($user, 1);
				$resultupdate = $resultupdate && $resultbtd;
			}

			$edituser = new ExtendedUser($this->db);
			$fod_user = New Fod_user($this->db);
			$liste_intervenant = $this->listIntervenantsForFod();
			foreach($liste_intervenant as $intervenant){
				$edituser->fetch($intervenant->id);
				if ($edituser->getDose12mois() + $edituser->getDoseMaxFod($this) >= $edituser->getCdd()){
					$fod_user_id = $fod_user->getIdWithUserAndFod($edituser->id, $this->id);
					$fod_user->fetch($fod_user_id);
					if($fod_user->statut == Fod_user::STATUS_AUTORISE){
						$fod_user->statut = Fod_user::STATUS_NA_AJOUT_INTERV;
						$fod_user->update($user, true);
					}
				}
				elseif ($edituser->getDose12mois() + $edituser->getDoseMaxFod($this) < $edituser->getCdd()){
					$fod_user_id = $fod_user->getIdWithUserAndFod($edituser->id, $this->id);
					$fod_user->fetch($fod_user_id);
					if($fod_user->statut == Fod_user::STATUS_NA_AJOUT_INTERV){
						$fod_user->statut = Fod_user::STATUS_AUTORISE;
						$fod_user->update($user, true);
					}
				}
			}
		}
		
		return $resultupdate;
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
		//return $this->deleteCommon($user, $notrigger);
		return $this->deleteCommon($user, $notrigger, 1);
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
	 *  Vérifie si le numéro chrono pour une fod n'est pas deja utilisé sur une même affaire
	 *
	 *  @param		int		$userid 			id de l'utilisateur
	 *  @param		boolean	$load_interenants	Charger les intervenants de chaque fod
	 *  @return		array     					Tableau de FOD (objet)
	 */
	public function VerifNumChrono($numchrono, $project)
	{
		global $conf, $user;

		$res = 1;

		$sql = "SELECT f.rowid, f.ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
		$sql .= " WHERE f.fk_project = ".(int) $project;
		if (!empty($this->id)) {
			$sql .= " AND f.rowid <> ".$this->id;
		}
	
		dol_syslog(get_class($this)."::VerifNumChrono", LOG_DEBUG);
		$result = $this->db->query($sql);
		
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$array = explode('_', $obj->ref);
				if($array[2] == $numchrono){
					$res = 0;
					break;
				}
			}

			$this->db->free($result);

			return $res;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//									GESTION DE LA LISTE DES INTERVENANTS											  /
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 *  Return liste des FOD pour un utilisateur
	 *
	 *  @param		int		$userid 			id de l'utilisateur
	 *  @param		boolean	$load_interenants	Charger les intervenants de chaque fod
	 *  @return		array     					Tableau de FOD (objet)
	 */
	public function listFodForUser($userid, $load_intervenants = true)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT f.rowid, uf.entity as userfod_entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f,";
		$sql .= " ".MAIN_DB_PREFIX."fod_user as uf";
		$sql .= " WHERE uf.fk_fod = f.rowid";
		$sql .= " AND uf.fk_user = ".((int) $userid);
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= " AND f.entity IS NOT NULL";
		} else {
			$sql .= " AND f.entity IN (0,".$conf->entity.")";
		}
		$sql .= " ORDER BY f.nom";

		dol_syslog(get_class($this)."::listFodForUser", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					$newfod = new Fod($this->db);
					$newfod->fetch($obj->rowid, '', $load_intervenants);
					$ret[$obj->rowid] = $newfod;
				}

				$ret[$obj->rowid]->userfod_entity[] = $obj->userfod_entity;
			}

			$this->db->free($result);

			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Return la liste des intervenant d'une FOD
	 *
	 * 	@param	string	$excludefilter		Filter to exclude. Do not use here a string coming from user input.
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listIntervenantsForFod($excludefilter = '', $mode = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT u.rowid";
		
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		if (!empty($this->id)) {
			$sql .= ", ".MAIN_DB_PREFIX."fod_user as uf";
		}
		$sql .= " WHERE 1 = 1";
		$sql .= " AND uf.fk_user = u.rowid";
		if (!empty($this->id)) {
			$sql .= " AND uf.fk_fod = ".((int) $this->id);
		}
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= " AND u.entity IS NOT NULL";
		} else {
			$sql .= " AND u.entity IN (0,".$conf->entity.")";
		}
		if (!empty($excludefilter)) {
			$sql .= ' AND ('.$excludefilter.')';
		}

		dol_syslog(get_class($this)."::listIntervenantsForFod", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					if ($mode != 1) {
						$newuser = new ExtendedUser($this->db);
						$newuser->fetch($obj->rowid);
						$newuser->user_fod_status = $obj->user_fod_status;
						$ret[$obj->rowid] = $newuser;
					} else {
						$ret[$obj->rowid] = $obj->rowid;
					}
				}
				if ($mode != 1 && !empty($obj->userfod_entity)) {
					$ret[$obj->rowid]->userfod_entity[] = $obj->userfod_entity;
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
	 * 	Return la liste des id des intervenants
	 *
	 * 	@return		array		Liste des intervenants (id)
	 */
	public function getListIntervenantId()
	{
		global $conf, $user;

		$intervenant = $this->listIntervenantsForFod();
		$intervenantid = array();

		dol_syslog(get_class($this)."::getListIntervenantId", LOG_DEBUG);

		if(!empty($intervenant)){
			foreach($intervenant as $int){
				$intervenantid[] = $int->id;
			}
		}

		return $intervenantid;
	}




	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//												CALCULS POUR UNE FOD												  /
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 *  Return l'enjeu radiologique d'une Fod
	 *
	 *  @return		int     Enjeu radiologique
	 */
	public function GetEnjeuRadiologique()
	{
		global $conf;

		if(($this->debit_dose_estime < $conf->global->FODEnjeuRadiologique0_DED) && ($this->debit_dose_max < $conf->global->FODEnjeuRadiologique0_DEDmax) && ($this->GetDoseCollectivePrevisionnelle() < $conf->global->FODEnjeuRadiologique0_DC)
		&& ($this->prop_radiologique <= $conf->global->FODEnjeuRadiologique0_prop_radiologique)){
			$enjeu_radiologique = 0;
		}
		else if(($this->debit_dose_estime < $conf->global->FODEnjeuRadiologique1_DED) && ($this->debit_dose_max < $conf->global->FODEnjeuRadiologique1_DEDmax) && ($this->GetDoseCollectivePrevisionnelle() <= $conf->global->FODEnjeuRadiologique1_DC) 
		&& ($this->prop_radiologique <= $conf->global->FODEnjeuRadiologique1_prop_radiologique)){
			$enjeu_radiologique = 1;
		}
		else if(($this->debit_dose_estime < $conf->global->FODEnjeuRadiologique2_DED) && ($this->debit_dose_max < $conf->global->FODEnjeuRadiologique2_DEDmax) && ($this->GetDoseCollectivePrevisionnelle() <= $conf->global->FODEnjeuRadiologique2_DC) 
		&& ($this->prop_radiologique <= $conf->global->FODEnjeuRadiologique2_prop_radiologique)){
			$enjeu_radiologique = 2;
		}
		else{
			$enjeu_radiologique = 3;
		}
		return $enjeu_radiologique;
	}

	/**
	 *  Return l'enjeu radiologique optimisé d'une Fod
	 *
	 *  @return		int     Enjeu radiologique optimisé
	 */
	public function GetEnjeuRadiologiqueOptimise()
	{
		global $conf;

		if($this->ded_optimise == 1){
			$ded = $this->debit_dose_estime_optimise;
		}
		else {
			$ded = $this->debit_dose_estime;
		}

		if($this->ded_max_optimise == 1){
			$ded_max = $this->debit_dose_max_optimise;
		}
		else {
			$ded_max = $this->debit_dose_max;
		}

		if($this->dc_optimise == 1){
			$dc = $this->GetDoseCollectivePrevisionnelleOptimise();
		}
		else {
			$dc = $this->GetDoseCollectivePrevisionnelle();
		}

		if($this->prop_rad_optimise == 1){
			$pr = $this->prop_radiologique_optimise;
		}
		else {
			$pr = $this->prop_radiologique;
		}

		if(($ded < $conf->global->FODEnjeuRadiologique0_DED) && ($ded_max < $conf->global->FODEnjeuRadiologique0_DEDmax) && ($dc < $conf->global->FODEnjeuRadiologique0_DC) && ($pr <= $conf->global->FODEnjeuRadiologique0_prop_radiologique)){
			$enjeu_radiologique = 0;
		}
		else if(($ded < $conf->global->FODEnjeuRadiologique1_DED) && ($ded_max < $conf->global->FODEnjeuRadiologique1_DEDmax) && ($dc <= $conf->global->FODEnjeuRadiologique1_DC) && ($pr <= $conf->global->FODEnjeuRadiologique1_prop_radiologique)){
			$enjeu_radiologique = 1;
		}
		else if(($ded < $conf->global->FODEnjeuRadiologique2_DED) && ($ded_max < $conf->global->FODEnjeuRadiologique2_DEDmax) && ($dc <= $conf->global->FODEnjeuRadiologique2_DC) && ($pr <= $conf->global->FODEnjeuRadiologique2_prop_radiologique)){
			$enjeu_radiologique = 2;
		}
		else{
			$enjeu_radiologique = 3;
		}
		return $enjeu_radiologique;
	}

	/**
	 *  Return la dose collective previsionnelle d'une Fod
	 *
	 *  @return		double     	Dose collective previsionnelle
	 */
	public function GetDoseCollectivePrevisionnelle()
	{
		return round($this->debit_dose_estime * $this->duree_intervention * $this->fields['coef_exposition']['arrayofkeyval'][$this->coef_exposition], 3);
	}

	/**
	 *  Return la dose collective optimisée d'une Fod
	 *
	 *  @return		double     	Dose collective optimisée
	 */
	public function GetDoseCollectivePrevisionnelleOptimise(){
		if($this->ded_optimise == 1){
			$ded_opti = $this->debit_dose_estime_optimise;
		}
		else $ded_opti = $this->debit_dose_estime;

		if(!empty($this->duree_intervention_optimise)){
			$duree_opti = $this->duree_intervention_optimise;
		}
		else $duree_opti = $this->duree_intervention;

		return round($ded_opti * $duree_opti * $this->fields['coef_exposition']['arrayofkeyval'][$this->coef_exposition], 3);
	}

	/**
	 *  Return la dose collective actuelle d'une FOD 
	 *
	 *  @return double  			    Contrainte de dose
	 */
	public function GetDoseCollectiveReel()
	{
		global $conf, $langs, $user;

		$error = 0;
		$dose = 0.00;
		//$this->db->begin();

		if(!empty($this->id)){
			$sql = "SELECT SUM(dose) as dose_totale FROM ".MAIN_DB_PREFIX."fod_dataintervenant";
			$sql .= " WHERE fk_fod=".$this->id;

			dol_syslog(get_class($this).'::GetDoseCollectiveReel', LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result){
				if ($this->db->num_rows($result)){
					$obj = $this->db->fetch_object($result);
					$dose = $obj->dose_totale;
				}
				else $dose = 0.00;
				//$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}
		else {
			$dose = 0.00;
		}
		return round($dose, 3);
	}

	/**
	 *  Return la dose individuelle moyenne d'une Fod
	 *
	 *  @return		double     	Dose Individuelle Moyenne
	 */
	public function GetDoseIndividuelleMoyenne()
	{
		return round($this->GetDoseCollectivePrevisionnelle() / $this->effectif, 3);
	}

	/**
	 *  Return la dose individuelle moyenne optimisée d'une Fod
	 *
	 *  @return		double     	Dose Individuelle Moyenne optimisée
	 */
	public function GetDoseIndividuelleMoyenneOptimise()
	{
		if (!empty($this->duree_intervention_optimise) || !empty($this->debit_dose_estime_optimise)){
			$dc = $this->GetDoseCollectivePrevisionnelleOptimise();
		}
		else {
			$dc = $this->GetDoseCollectivePrevisionnelle();
		}
		
		if (!empty($this->effectif_optimise)){
			$effectif = $this->effectif_optimise;
		}
		else $effectif = $this->effectif;

		return round($dc/$effectif, 3);
	}

	/**
	 *  Return la dose individuelle maximum d'une Fod
	 *
	 *  @return		double     	Dose Individuelle max
	 */
	public function GetDoseIndividuelleMax()
	{
		if($this->effectif==1){
			$dose_individuelle_max = 1 * $this->GetDoseCollectivePrevisionnelle();
		}
		else if($this->effectif==2){
			$dose_individuelle_max = 0.8 * $this->GetDoseCollectivePrevisionnelle();
		}
		else if($this->effectif>=3){
			$dose_individuelle_max = 0.7 * $this->GetDoseCollectivePrevisionnelle();
		}
		return round($dose_individuelle_max, 3);
	}

	/**
	 *  Return la dose individuelle maximum optimisée d'une Fod
	 *
	 *  @return		double     	Dose Individuelle max optimisée
	 */
	public function GetDoseIndividuelleMaxOptimise(){
		if (!empty($this->effectif_optimise)) {
			$effectif = $this->effectif_optimise;
		}
		else $effectif = $this->effectif;

		if (!empty($this->duree_intervention_optimise) || !empty($this->debit_dose_estime_optimise)){
			$dc = $this->GetDoseCollectivePrevisionnelleOptimise();
		}
		else {
			$dc = $this->GetDoseCollectivePrevisionnelle();
		}

		if($effectif==1){
			$dose_individuelle_max = 1 * $dc;
		}
		else if($effectif==2){
			$dose_individuelle_max = 0.8 * $dc;
		}
		else if($effectif>=3){
			$dose_individuelle_max = 0.7 * $dc;
		}
		return round($dose_individuelle_max, 3);
	}


	/**
	 * 	Return les id des Fod actives du projet
	 *
	 * 	@param	int		$idproject			Id du projet à laquelle les fod sont rattachées
	 * 	@return	array(int)					tableau d'Id des fod 
	 */
	public function getListIdByProject($idproject = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT f.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
		$sql .= " WHERE f.fk_project =".(int) $idproject;
		$sql .= " AND (f.status=".self::STATUS_VALIDATED.' or f.status='.self::STATUS_BILAN.' or f.status='.self::STATUS_BILANinter.' or f.status='.self::STATUS_BILANRSR.' or f.status='.self::STATUS_BILANRSRRA.' or f.status='.self::STATUS_BILANRSRRAPCR;
		$sql .= " or f.status = ".self::STATUS_BILAN_REFUS." or f.status = ".self::STATUS_CLOTURE.")";

		dol_syslog(get_class($this)."::getListIdByProject", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i=0;
			while($i < $num){ 
				$obj = $this->db->fetch_object($resql);
				$ret[] = $obj->rowid;
				$i++;
			}
			$this->db->free($resql);
			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//									FONCTION POUR LES TACHES PLANIFIEES 											  /
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 *  Envoi un mail pour les FOD dont la date de fin de validité est dans 15 jours
	 *
	 *  @return		int     resultat
	 */
	public function MailFOD_15J()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$sql = "SELECT f.rowid, f.date_fin, f.date_fin_prolong, f.fk_user_rsr, f.fk_user_raf";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
		$sql .= " WHERE f.status = 4";

		dol_syslog(get_class($this)."::MailFOD_15J", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				if (!empty($obj->date_fin_prolong)){
					$date = $obj->date_fin_prolong;
				}
				else $date = $obj->date_fin;

				$date_actuelle = dol_mktime(-1, -1, -1, substr($this->db->idate(dol_now()), 5, 6), substr($this->db->idate(dol_now()), 8, 9), substr($this->db->idate(dol_now()), 0, 4));
				$date_actuelle_15J = dol_time_plus_duree($date_actuelle, 15, 'd');
				
				if ($this->db->jdate($date) == $date_actuelle_15J) {
					$subject = '[OPTIM Industries] Notification automatique FOD';
					$from = 'erp@optim-industries.fr';
					$user_ = new User($this->db);
					$user_->fetch($obj->fk_user_rsr);
					$to = '<';
					$to .= $user_->email.', ';
					$user_->fetch($obj->fk_user_raf);
					$to .= $user_->email.'>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$fod = new Fod($this->db);
					$fod->fetch($obj->rowid);
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFOD_15J", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					$res = $res && $mail->sendfile();
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
	 *  Envoi un mail pour les FOD dont la date de fin de validité est dans 5 jours
	 *
	 *  @return		int     resultat
	 */
	public function MailFOD_5J()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$sql = "SELECT f.rowid, f.date_fin, f.date_fin_prolong, f.fk_user_rsr, f.fk_user_raf";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
		$sql .= " WHERE f.status = 4";

		dol_syslog(get_class($this)."::MailFOD_5J", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				if (!empty($obj->date_fin_prolong)){
					$date = $obj->date_fin_prolong;
				}
				else $date = $obj->date_fin;

				$date_actuelle = dol_mktime(-1, -1, -1, substr($this->db->idate(dol_now()), 5, 6), substr($this->db->idate(dol_now()), 8, 9), substr($this->db->idate(dol_now()), 0, 4));
				$date_actuelle_5J = dol_time_plus_duree($date_actuelle, 5, 'd');
				
				if ($this->db->jdate($date) == $date_actuelle_5J) {
					$subject = '[OPTIM Industries] Notification automatique FOD';
					$from = 'erp@optim-industries.fr';
					$user_ = new User($this->db);
					$user_->fetch($obj->fk_user_rsr);
					$to = '<';
					$to .= $user_->email.', ';
					$user_->fetch($obj->fk_user_raf);
					$to .= $user_->email.', ';
					$fod = new Fod($this->db);
					$fod->fetch($obj->rowid, null, true);
					foreach($fod->intervenants as $intervenant){
						$to .= $intervenant->email;
						$to .= ", ";
					}
					$to = rtrim($to, ", ");
					$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFOD_5J", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					$res = $res && $mail->sendfile();
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
	 *  Envoi un mail pour les FOD dont la date de fin de validité a été dépassé + passage au bilan
	 *
	 *  @return		int     resultat
	 */
	public function MailFODdepassement_1J()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$sql = "SELECT f.rowid, f.date_fin, f.date_fin_prolong, f.fk_user_rsr, f.fk_user_raf";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
		$sql .= " WHERE f.status = 4";

		dol_syslog(get_class($this)."::MailFODdepassement_1J", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				if (!empty($obj->date_fin_prolong)){
					$date = $obj->date_fin_prolong;
				}
				else $date = $obj->date_fin;

				$date_actuelle = dol_mktime(-1, -1, -1, substr($this->db->idate(dol_now()), 5, 6), substr($this->db->idate(dol_now()), 8, 9), substr($this->db->idate(dol_now()), 0, 4));
				$date_1J = dol_time_plus_duree($this->db->jdate($date), 1, 'd');
				
				if ($date_1J == $date_actuelle) {
					$subject = '[OPTIM Industries] Notification automatique FOD';
					$from = 'erp@optim-industries.fr';
					$user_ = new User($this->db);
					$user_->fetch($obj->fk_user_rsr);
					$to = '<';
					$to .= $user_->email.', ';
					$user_->fetch($obj->fk_user_raf);
					$to .= $user_->email.', ';
					$fod = new Fod($this->db);
					$fod->fetch($obj->rowid, null, true);
					foreach($fod->intervenants as $intervenant){
						$to .= $intervenant->email;
						$to .= ", ";
					}
					$to = rtrim($to, ", ");
					$to .= '>';
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$fod->id.'">'.$fod->ref.'</a>';
					$msg = $langs->transnoentitiesnoconv("EMailTextFODdepassement_1J", $link);
					$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
					$res = $res && $mail->sendfile();

					$fod->status = self::STATUS_BILAN;
					$res = $res && $fod->update($user);
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
	 *  Vérification de l'etat des intervenants qui ont dépassé la CdD mensuelle
	 *
	 *  @return		int 	resultat
	 */
	public function Verification_CdD_Mensuelle()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$sql = "SELECT fu.rowid, fu.fk_user, fu.statut";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_user as fu";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fod_fod as f ON f.rowid = fu.fk_fod";
		$sql .= " WHERE f.status <>".self::STATUS_CLOTURE;
		$sql .= " AND f.status <>".self::STATUS_CANCELED;
		$sql .= " AND (fu.statut =".Fod_user::STATUS_NA_cddMensuelle." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddMensuelle." OR fu.statut =".Fod_user::STATUS_NA_cddMensuelle_cddAnnuelle;
		$sql .= " OR fu.statut =".Fod_user::STATUS_NA_cddMensuelle_dcFOD." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddMensuelle_cddAnnuelle." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddMensuelle_dcFOD;
		$sql .= " OR fu.statut =".Fod_user::STATUS_NA_cddMensuelle_cddAnnuelle_dcFOD." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddMensuelle_cddAnnuelle_dcFOD.")";


		dol_syslog(get_class($this)."::Verification_CdD_Mensuelle", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$user_ = new ExtendedUser($this->db);
				$user_->fetch($obj->fk_user);
				if ($user_->getDoseMoisActuelle() <= $user_->getCddmensuelle()){
						$fod_user = New Fod_user($this->db);
						$fod_user->fetch($obj->rowid);
						$new_statut = $fod_user->getNewStatut($obj->statut, '-', Fod_user::STATUS_NA_cddMensuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
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
	 *  Vérification de l'etat des intervenants qui ont dépassé la CdD annuelle
	 *
	 *  @return		int 	resultat
	 */
	public function Verification_CdD_Annuelle()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;

		$sql = "SELECT fu.rowid, fu.fk_user, fu.statut";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_user as fu";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fod_fod as f ON f.rowid = fu.fk_fod";
		$sql .= " WHERE f.status <>".self::STATUS_CLOTURE;
		$sql .= " AND f.status <>".self::STATUS_CANCELED;
		$sql .= " AND (fu.statut =".Fod_user::STATUS_NA_cddAnnuelle." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddAnnuelle." OR fu.statut =".Fod_user::STATUS_NA_cddMensuelle_cddAnnuelle;
		$sql .= " OR fu.statut =".Fod_user::STATUS_NA_cddAnnuelle_dcFOD." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddMensuelle_cddAnnuelle." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddAnnuelle_dcFOD;
		$sql .= " OR fu.statut =".Fod_user::STATUS_NA_cddMensuelle_cddAnnuelle_dcFOD." OR fu.statut =".Fod_user::STATUS_NA_cddFOD_cddMensuelle_cddAnnuelle_dcFOD.")";


		dol_syslog(get_class($this)."::Verification_CdD_Annuelle", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$user_ = new ExtendedUser($this->db);
				$user_->fetch($obj->fk_user);
				if ($user_->getDose12mois() <= $user_->getCdd()){
						$fod_user = New Fod_user($this->db);
						$fod_user->fetch($obj->rowid);
						$new_statut = $fod_user->getNewStatut($obj->statut, '-', Fod_user::STATUS_NA_cddAnnuelle);
						$fod_user->statut = $new_statut;
						$res = $res && $fod_user->update($user, true);
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
	 *  Passage des intervenants dont la date de sortie est dépassé à l'état 'sortie'
	 *
	 *  @return		int     					resultat
	 */
	public function Verification_Sortie_Intervenant()
	{
		global $conf, $user, $dolibarr_main_url_root, $langs;
		$res = 1;
		$fod_user = New Fod_user($this->db);

		$sql = "SELECT fu.rowid, fu.statut, fu.date_sortie";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_user as fu";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fod_fod as f ON f.rowid = fu.fk_fod";
		$sql .= " WHERE f.status=".self::STATUS_VALIDATED;
		$sql .= " AND fu.statut!=".Fod_user::STATUS_SORTIE;
		$sql .= " AND fu.date_sortie IS NOT NULL";

		dol_syslog(get_class($this)."::Verification_Sortie_Intervenant", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$date_actuelle = dol_mktime(-1, -1, -1, substr($this->db->idate(dol_now()), 5, 6), substr($this->db->idate(dol_now()), 8, 9), substr($this->db->idate(dol_now()), 0, 4));
				//$date_1J = dol_time_plus_duree($this->db->jdate($date), 1, 'd');

				print $date_actuelle.' - '.$date_1J.' - '.$obj->date_sortie;
				
				if ($date_actuelle > $obj->date_sortie) {
					$fod_user->fetch($obj->rowid);
					$fod_user->statut = Fod_user::STATUS_SORTIE;
					$res = $res && $fod_user->update($user);
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
	 *	permet de prolonger une FOD 
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function prolonger($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		$result = 0;

		// Protection
		if (!empty($this->date_fin_prolong)) {
			dol_syslog(get_class($this)."::prolonger action abandonned: LA FOD a déja été prolongé", LOG_WARNING);
			return 0;
		}

		if (empty(GETPOST('date_prolongday', 'int')) || empty(GETPOST('date_prolongmonth', 'int')) || empty(GETPOST('date_prolongyear', 'int'))) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("DateProlongation")), null, 'errors');
			$error++;
		}

		$jour = GETPOST('date_prolongday', 'int');
		$mois = GETPOST('date_prolongmonth', 'int');
		$annee = GETPOST('date_prolongyear', 'int');

		$date = dol_mktime(-1, -1, -1, $mois, $jour, $annee);

		if (!$error && empty($this->date_fin_prolong)) {
			$this->date_fin_prolong = $date;
			$result = $this->update($user);
		}
	
		if ($result && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_PROLONGER', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if ($result && !$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Validate object (Validation du PCR)
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
		if ($this->status != self::STATUS_VALIDATEDRARSR) {
			dol_syslog(get_class($this)."::validate action abandonned", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->fod_advance->validate))))
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

			// Set new ref and current status
			if (!$error) {
				$this->ref = $num;
				$this->ancien_status = $this->status;
				$this->status = self::STATUS_VALIDATED;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('FOD_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		/*if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'fod/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'fod/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->fod->dir_output.'/fod/'.$oldref;
				$dirdest = $conf->fod->dir_output.'/fod/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->fod->dir_output.'/fod/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
		}*/


		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Validate object (Premiere validation par le RA)
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validatera($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATEDRA) {
			dol_syslog(get_class($this)."::validate by RA action abandonned: already validated by RA", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->fod_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_VALIDATEDRA;
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", date_validation = '".$this->db->idate($now)."'";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", fk_user_valid = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validatera()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_VALIDATEDRA;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_VALIDATERA', $user);
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

	/**
	 *	Validate object (Premiere validation par le RSR)
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validatersr($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_DRAFT && $this->status != self::STATUS_AOA) {
			dol_syslog(get_class($this)."::validate by RSR action abandonned: already validated by RSR", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->fod_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_VALIDATEDRSR;
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", date_validation = '".$this->db->idate($now)."'";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", fk_user_valid = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validatersr()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_VALIDATEDRSR;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_VALIDATERSR', $user);
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

	/**
	 *	Validate object (Deuxieme validation par le RA ou RSR)
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validaterarsr($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_DRAFT && $this->status != self::STATUS_AOA && $this->status != self::STATUS_VALIDATEDRSR && $this->status != self::STATUS_VALIDATEDRA) {
			dol_syslog(get_class($this)."::validate by RSR and RSR action abandonned: already validated by RA and RSR", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->fod_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_VALIDATEDRARSR;
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", date_validation = '".$this->db->idate($now)."'";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", fk_user_valid = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validaterarsr()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if(empty($this->listIntervenantsForFod())){
			$error++;
			setEventMessages("Il n'y a aucun intervenant", null, 'errors');
		}

		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_VALIDATEDRARSR;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_VALIDATERARSR', $user);
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
	

	/**
	 *	Passsage au bilan d'une FOD 
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function bilan($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::Bilan action abandonned: already Bilan", LOG_WARNING);
			return 0;
		}

		if(empty($this->listIntervenantsForFod())){
			$statut = 9;
		}
		else $statut = 8;

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".$statut;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::bilan()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}


		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = $statut;
		}

		
		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_BILAN', $user);
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

	/**
	 *	Validate Bilan FOD (Premiere validation par les intervenants)
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validatebilanInter($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_BILAN) {
			dol_syslog(get_class($this)."::validate Bilan by intervenants action abandonned: already validated by intervenants", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_BILANinter;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validatebilanInter()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}


		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_BILANinter;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_VALIDATEBILANINTER', $user);
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

	/**
	 *	Validate Bilan FOD (2e validation par le RSR)
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validatebilanrsr($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_BILAN && $this->status != self::STATUS_BILANinter) {
			dol_syslog(get_class($this)."::validate Bilan by RSR action abandonned: already validated by RSR", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_BILANRSR;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validatebilanrsr()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}


		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_BILANRSR;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_VALIDATEBILANRSR', $user);
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

	/**
	 *	Validate Bilan FOD (3e validation par le RA)
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validatebilanrsrra($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_BILANRSR && $this->status != self::STATUS_BILAN_REFUS) {
			dol_syslog(get_class($this)."::validate Bilan by RA action abandonned: already validated by RA", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_BILANRSRRA;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validatebilanrsrra()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}


		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_BILANRSRRA;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_VALIDATEBILANRSRRA', $user);
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

	/**
	 *	Validate Bilan FOD (4e validation par le PCR)
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validatebilanrsrrapcr($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_BILANRSRRA && $this->status != self::STATUS_BILAN_REFUS) {
			dol_syslog(get_class($this)."::validate Bilan by PCR action abandonned: already validated by PCR", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_BILANRSRRAPCR;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validatebilanrsrrapcr()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}


		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_BILANRSRRAPCR;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_VALIDATEBILANRSRRAPCR', $user);
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

	/**
	 *	Refus du Bilan de la FOD
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function refusbilan($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_BILANRSRRAPCR) {
			dol_syslog(get_class($this)."::Refus Bilan by RD action abandonned: already refus by RD", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_BILAN_REFUS;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::refusbilan()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}


		// Set new ref and current status
		if (!$error) {
			$this->ancien_status = $this->status;
			$this->status = self::STATUS_BILAN_REFUS;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_REFUSBILAN', $user);
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

	/**
	 *	Passer le status de la FOD à AOA
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function aoa($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_AOA) {
			dol_syslog(get_class($this)."::aoa action abandonned: already aoa", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->fod_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_AOA;
		$sql .= " ,aoa = 2";
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", date_validation = '".$this->db->idate($now)."'";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", fk_user_valid = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::aoa()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_AOA', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_AOA;
			$this->aoa = 2;
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
	 *	Passer le status de la FOD à cloturé
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function cloture($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status != self::STATUS_BILANRSRRAPCR) {
			dol_syslog(get_class($this)."::cloture action abandonned: already cloture", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod->fod_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_CLOTURE;
		if (!empty($this->fields['date_cloture'])) {
			$sql .= ", date_cloture = '".$this->db->idate($now)."'";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::cloture()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FOD_CLOTURE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_CLOTURE;
			$this->date_cloture = $now;
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
		if ($this->status == self::STATUS_DRAFT) {
			return 0;
		}

		if($this->aoa == 3){
			$this->aoa = 2;
			$this->update($user);
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'FOD_REFUS');
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
		if ($this->status == self::STATUS_CANCELED) {
			return 0;
		}

		$this->historique .= '<span><strong>'.dol_print_date(dol_now(), '%d/%m/%Y').'</strong> : Annulation de la FOD par '.$user->firstname.' '.$user->lastname.'</span><br>';
		$this->update($user, true);

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'FOD_CANCEL');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fod->fod_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'FOD_REOPEN');
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

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Fod").'</u>';

		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref.'<br>';
		$label .= '<b>'.$langs->trans('Indice').':</b> '.$this->indice.'<br>';
		$client = new Societe($this->db);
		$client->fetch($this->client_site);
		$label .= '<b>'.$langs->trans('ClientSite').':</b> '.$client->nom;

		$url = dol_buildpath('/fod/fod_card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowFod");
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
				$result .= img_object(($notooltip ? '' : $label), 'fod_16@fod', ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
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
		$hookmanager->initHooks(array('foddao'));
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
			//$langs->load("fod@fod");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_AOA] = $langs->trans('AOA');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatus[self::STATUS_VALIDATEDRA] = $langs->trans('ValidationEnAttente');
			$this->labelStatus[self::STATUS_VALIDATEDRARSR] = $langs->trans('ValidationEnAttente');
			$this->labelStatus[self::STATUS_VALIDATEDRSR] = $langs->trans('ValidationEnAttente');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatus[self::STATUS_CLOTURE] = $langs->trans('Cloturé');
			$this->labelStatus[self::STATUS_BILAN] = $langs->trans('Bilan');
			$this->labelStatus[self::STATUS_BILANinter] = $langs->trans('BilanInter');
			$this->labelStatus[self::STATUS_BILANRSR] = $langs->trans('BilanRSR');
			$this->labelStatus[self::STATUS_BILANRSRRA] = $langs->trans('BilanRSRRA');
			$this->labelStatus[self::STATUS_BILANRSRRAPCR] = $langs->trans('BilanRSRRAPCR');
			$this->labelStatus[self::STATUS_BILAN_REFUS] = $langs->trans('BilanRefus');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_AOA] = $langs->trans('AOA');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatusShort[self::STATUS_VALIDATEDRA] = $langs->trans('ValidationEnAttente');
			$this->labelStatusShort[self::STATUS_VALIDATEDRARSR] = $langs->trans('ValidationEnAttente');
			$this->labelStatusShort[self::STATUS_VALIDATEDRSR] = $langs->trans('ValidationEnAttente');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_CLOTURE] = $langs->trans('Cloturé');
			$this->labelStatusShort[self::STATUS_BILAN] = $langs->trans('Bilan');
			$this->labelStatusShort[self::STATUS_BILANinter] = $langs->trans('BilanInter');
			$this->labelStatusShort[self::STATUS_BILANRSR] = $langs->trans('BilanRSR');
			$this->labelStatusShort[self::STATUS_BILANRSRRA] = $langs->trans('BilanRSRRA');
			$this->labelStatusShort[self::STATUS_BILANRSRRAPCR] = $langs->trans('BilanRSRRAPCR');
			$this->labelStatusShort[self::STATUS_BILAN_REFUS] = $langs->trans('BilanRefus');

		}

		$statusType = 'status'.$status;

		if ($status == self::STATUS_CLOTURE) {
			$statusType = 'status6';
		}
		if ($status == self::STATUS_VALIDATEDRA) {
			$statusType = 'status7';
			return dolGetStatus('Le RA a validé la FOD (en attente de la validation du RSR)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
		if ($status == self::STATUS_VALIDATEDRSR) {
			$statusType = 'status7';
			return dolGetStatus('Le RSR a validé la FOD (en attente de la validation du RA)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
        if ($status == self::STATUS_VALIDATEDRARSR) {
			$statusType = 'status7';
			return dolGetStatus('Le RA (et le RSR) ont validé la FOD (en attente de la validation de la PCR)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
		if ($status == self::STATUS_BILAN) {
			$statusType = 'status8';
			return dolGetStatus('Bilan de la FOD (en attente de la validation des intervenants)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
		if ($status == self::STATUS_BILANinter) {
			$statusType = 'status8';
			return dolGetStatus('Le bilan de la FOD a été validé par les intervenants (en attente de la validation du RSR)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
		if ($status == self::STATUS_BILANRSR) {
			$statusType = 'status8';
			return dolGetStatus('Le bilan de la FOD a été validé par le RSR (en attente de la validation du RA)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
		if ($status == self::STATUS_BILANRSRRA) {
			$statusType = 'status8';
			return dolGetStatus('Le bilan de la FOD a été validé par le RSR et le RA (en attente de la validation de la PCR)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
		if ($status == self::STATUS_BILANRSRRAPCR) {
			$statusType = 'status8';
			return dolGetStatus('Le bilan de la FOD a été validé par le RSR, le RA et le PCR (en attente de la clotûre du RD)', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}
		if ($status == self::STATUS_BILAN_REFUS) {
			$statusType = 'status8';
			return dolGetStatus('Le bilan de la FOD a été refusé par le RD (en attente de la validation de la PCR et du RA (non obligatoire))', $this->labelStatusShort[$status], $this->labelStatus[$status], $statusType, $mode);
		}

		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status9';
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

		$objectline = new FodLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_fod = '.$this->id));

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
		$langs->load("fod@fod");

		if (empty($conf->global->FOD_FOD_ADDON)) {
			$conf->global->FOD_FOD_ADDON = 'mod_fod_standard';
		}

		if (!empty($conf->global->FOD_FOD_ADDON)) {
			$mybool = false;

			$file = $conf->global->FOD_FOD_ADDON.".php";
			$classname = $conf->global->FOD_FOD_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/fod/");

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

		$langs->load("fod@fod");

		if (!dol_strlen($modele)) {
			$modele = 'standard_fod';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->FOD_ADDON_PDF)) {
				$modele = $conf->global->FOD_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/fod/doc/";

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
	 * Return tableau de FOD que l'utilisateur est autorisé à voir
	 *
	 * @param 	User	$user			User object
	 * @param 	int		$list			0=Return array, 1=Return string list
	 * @param	string	$filter			additionnal filter on project (statut, ref, ...)
	 * @return 	array or string			Array of projects id, or string with projects id separated with "," if list is 1
	 */
	public function getFODAuthorizedForUser($user, $list = 0, $filter = '')
	{
		$fod = array();
		$temp = array();

		$sql = "SELECT DISTINCT f.rowid, f.ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fod_user as fu ON fu.fk_fod = f.rowid";
		$sql .= " WHERE (f.fk_user_pcr=".$user->id." or f.fk_user_rsr=".$user->id." or f.fk_user_raf=".$user->id." or fu.fk_user=".$user->id.")";

		$sql .= $filter;
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$fod[$row[0]] = $row[1];
				$temp[] = $row[0];
				$i++;
			}

			$this->db->free($resql);

			if ($list) {
				if (empty($temp)) {
					return '0';
				}
				$result = implode(',', $temp);
				return $result;
			}
		} else {
			dol_print_error($this->db);
		}

		return $fod;
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
				$value = price($value, 0, '', 0);
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
				$out .= '<option value="0">&nbsp;</option>';
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
	 *  Get la contrainte de dose annuelle OPTIM Industries
	 *
	 *  @return double  			    Contrainte de dose annuelle OPTIM
	 */
	public function getCdDAnnuelle()
	{
		global $conf, $langs, $user;

		$error = 0;

		//$this->db->begin();

		$sql = "SELECT cdd FROM ".MAIN_DB_PREFIX."fod_cdd";
		$sql .= " WHERE options_cat_med = 100";

		dol_syslog(get_class($this).'::getCdDAnnuelle', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$cdd = $obj->cdd;
			}
			else $cdd = 0.00;
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}

		return round($cdd, 3);
	}

	/**
	 *  Get la dose sur les 12 derniers mois de tous les intervenants
	 *
	 *  @return double  			    Dose
	 */
	public function getDose12moisTotale()
	{
		global $conf, $langs, $user;

		$error = 0;

		//$this->db->begin();

		$year_end = strftime("%Y", dol_now());
		$month_end = strftime("%m", dol_now());
		$day_end = strftime("%d", dol_now());
		$year_start = $year_end-1;
		$month_start = $month_end;
		$day_start = $day_end;

		$date_start = dol_mktime(0, 0, 0, $month_end, $day_end, $year_start);
		$date_end = dol_mktime(23, 59, 59, $month_end, $day_end, $year_end);

		$sql = "SELECT SUM(dose) as dose_totale FROM ".MAIN_DB_PREFIX."fod_dataintervenant";
		$sql .= " WHERE date <= '".$this->db->idate($date_end)."'";
		$sql .= " AND date > '".$this->db->idate($date_start)."'";

		dol_syslog(get_class($this).'::getDose12moisTotale', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$dose = $obj->dose_totale;
			}
			else $dose = 0.00;
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}

		return round($dose, 3);
	}

	/**
	 * 	Return les nombre de FOD sur un projet
	 *
	 * 	@param	int		$idprojet			Id du projet auquel es FOD sont rattachées
	 * 	@return	int							Nombre de FOD
	 */
	public function getNbFOD($idprojet)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT COUNT(f.rowid) as nb_fod";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
		$sql .= " WHERE f.fk_project = ".$idprojet;

		dol_syslog(get_class($this)."::getNbFOD", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if($num > 0){ 
				$obj = $this->db->fetch_object($resql);
				$this->db->free($resql);
				return $obj->nb_fod;
			}
			$this->db->free($resql);
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Return le nombre d'entrée sur une FOD
	 *
	 * 	@return	int						Nombre d'entrée
	 */
	public function getNbEntree()
	{
		global $conf, $user;
		$ret = 0;

		$sql = "SELECT COUNT(f.rowid) as nb_entree";
		$sql .= " FROM ".MAIN_DB_PREFIX."fod_dataintervenant as f";
		$sql .= " WHERE f.fk_fod =". $this->id;

		dol_syslog(get_class($this)."::getNbEntree", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($result);
			$ret = $obj->nb_entree;
			$this->db->free($resql);
			return $ret;
		}
		else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class FodLine. You can also remove this and generate a CRUD class for lines objects.
 */
class FodLine extends CommonObjectLine
{
	// To complete with content of an object FodLine
	// We should have a field rowid, fk_fod and position

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
