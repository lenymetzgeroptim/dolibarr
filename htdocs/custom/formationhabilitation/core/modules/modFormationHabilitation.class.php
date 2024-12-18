<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 METZGER Leny <l.metzger@optim-industries.fr>
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
 * 	\defgroup   formationhabilitation     Module FormationHabilitation
 *  \brief      FormationHabilitation module descriptor.
 *
 *  \file       htdocs/formationhabilitation/core/modules/modFormationHabilitation.class.php
 *  \ingroup    formationhabilitation
 *  \brief      Description and activation file for module FormationHabilitation
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module FormationHabilitation
 */
class modFormationHabilitation extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 500007; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'formationhabilitation';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleFormationHabilitationName' not found (FormationHabilitation is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleFormationHabilitationDesc' not found (FormationHabilitation is name of module).
		$this->description = "FormationHabilitationDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "FormationHabilitationDescription";

		// Author
		$this->editor_name = 'Lény METZGER';
		$this->editor_url = '';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where FORMATIONHABILITATION is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fa-id-card_fas_#1f3d89';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 0,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				'/formationhabilitation/css/formationhabilitation.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				'/formationhabilitation/js/formationhabilitation.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				   'data' => array(
				       'formationagenda',
				   ),
				   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/formationhabilitation/temp","/formationhabilitation/subdir");
		$this->dirs = array("/formationhabilitation/temp");

		// Config pages. Put here list of php page, stored into formationhabilitation/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@formationhabilitation");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("formationhabilitation@formationhabilitation");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'FormationHabilitationWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('FORMATIONHABILITATION_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('FORMATIONHABILITATION_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
				1 => array('MAIN_AGENDA_ACTIONAUTO_FORMATION_CREATE', 'chaine', '1', '', 0), // Lors de la création
				2 => array('MAIN_AGENDA_ACTIONAUTO_FORMATION_MODIFY', 'chaine', '1', '', 0), // Lors de la modification
				3 => array('MAIN_AGENDA_ACTIONAUTO_FORMATION_DELETE', 'chaine', '1', '', 0), // Lors de la supression
				4 => array('MAIN_AGENDA_ACTIONAUTO_FORMATION_VALIDATE', 'chaine', '1', '', 0), // Lors de la validation
				5 => array('MAIN_AGENDA_ACTIONAUTO_FORMATION_CLOTURE', 'chaine', '1', '', 0), // Lors de la clôture
				6 => array('MAIN_AGENDA_ACTIONAUTO_FORMATION_REOPEN', 'chaine', '1', '', 0), // Lors de la reouverture

				7 => array('MAIN_AGENDA_ACTIONAUTO_HABILITATION_CREATE', 'chaine', '1', '', 0), // Lors de la création
				8 => array('MAIN_AGENDA_ACTIONAUTO_HABILITATION_MODIFY', 'chaine', '1', '', 0), // Lors de la modification
				9 => array('MAIN_AGENDA_ACTIONAUTO_HABILITATION_DELETE', 'chaine', '1', '', 0), // Lors de la supression
				10 => array('MAIN_AGENDA_ACTIONAUTO_HABILITATION_VALIDATE', 'chaine', '1', '', 0), // Lors de la validation
				11 => array('MAIN_AGENDA_ACTIONAUTO_HABILITATION_CLOTURE', 'chaine', '1', '', 0), // Lors de la clôture
				12 => array('MAIN_AGENDA_ACTIONAUTO_HABILITATION_REOPEN', 'chaine', '1', '', 0), // Lors de la reouverture

				13 => array('MAIN_AGENDA_ACTIONAUTO_AUTORISATION_CREATE', 'chaine', '1', '', 0), // Lors de la création
				14 => array('MAIN_AGENDA_ACTIONAUTO_AUTORISATION_MODIFY', 'chaine', '1', '', 0), // Lors de la modification
				15 => array('MAIN_AGENDA_ACTIONAUTO_AUTORISATION_DELETE', 'chaine', '1', '', 0), // Lors de la supression
				16 => array('MAIN_AGENDA_ACTIONAUTO_AUTORISATION_VALIDATE', 'chaine', '1', '', 0), // Lors de la validation
				17 => array('MAIN_AGENDA_ACTIONAUTO_AUTORISATION_CLOTURE', 'chaine', '1', '', 0), // Lors de la clôture
				18 => array('MAIN_AGENDA_ACTIONAUTO_AUTORISATION_REOPEN', 'chaine', '1', '', 0), // Lors de la reouverture

				19 => array('MAIN_AGENDA_ACTIONAUTO_VISITEMEDICAL_CREATE', 'chaine', '1', '', 0), // Lors de la création
				20 => array('MAIN_AGENDA_ACTIONAUTO_VISITEMEDICAL_MODIFY', 'chaine', '1', '', 0), // Lors de la modification
				21 => array('MAIN_AGENDA_ACTIONAUTO_VISITEMEDICAL_DELETE', 'chaine', '1', '', 0), // Lors de la supression
				22 => array('MAIN_AGENDA_ACTIONAUTO_VISITEMEDICAL_CLOSE', 'chaine', '1', '', 0), // Lors de la clôture
				23 => array('MAIN_AGENDA_ACTIONAUTO_VISITEMEDICAL_EXPIRE', 'chaine', '1', '', 0), // Lors de l'expiration

				24 => array('MAIN_AGENDA_ACTIONAUTO_CONVOCATION_CREATE', 'chaine', '1', '', 0), // Lors de la création
				25 => array('MAIN_AGENDA_ACTIONAUTO_CONVOCATION_MODIFY', 'chaine', '1', '', 0), // Lors de la modification
				26 => array('MAIN_AGENDA_ACTIONAUTO_CONVOCATION_DELETE', 'chaine', '1', '', 0), // Lors de la supression
				27 => array('MAIN_AGENDA_ACTIONAUTO_CONVOCATION_VALIDATE', 'chaine', '1', '', 0), // Lors de la validation
				//28 => array('MAIN_AGENDA_ACTIONAUTO_CONVOCATION_CLOSE', 'chaine', '1', '', 0), // Lors de la validation
				//29 => array('MAIN_AGENDA_ACTIONAUTO_CONVOCATION_CANCEL', 'chaine', '1', '', 0), // Lors de la validation

				30 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_CREATE', 'chaine', '1', '', 0), // Lors de la création
				31 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_MODIFY', 'chaine', '1', '', 0), // Lors de la modification
				32 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_DELETE', 'chaine', '1', '', 0), // Lors de la supression
				33 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_VALIDATE1', 'chaine', '1', '', 0), // Lors de la validation
				34 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_VALIDATE2', 'chaine', '1', '', 0), // Lors de la validation
				35 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_VALIDATE3', 'chaine', '1', '', 0), // Lors de la validation
				36 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_VALIDATE_WITHOUT_USER', 'chaine', '1', '', 0), // Lors de la validation
				37 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_VALIDATE', 'chaine', '1', '', 0), // Lors de la validation
				38 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_EXPIRE', 'chaine', '1', '', 0), // Lors de l'expiration
				39 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_SUSPEND', 'chaine', '1', '', 0), // Lors de la suspension
				40 => array('MAIN_AGENDA_ACTIONAUTO_USERVOLET_UNSUSPEND', 'chaine', '1', '', 0), // Lors de la non suspensio,
				41 => array('MAIN_AGENDA_ACTIONAUTO_OBJECT_LINK_INSERT', 'chaine', '1', '', 0), // Lors de l'ajout d'une ligne
				42 => array('MAIN_AGENDA_ACTIONAUTO_OBJECT_LINK_DELETE', 'chaine', '1', '', 0), // Lors de la suppression d'une ligne

			);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->formationhabilitation) || !isset($conf->formationhabilitation->enabled)) {
			$conf->formationhabilitation = new stdClass();
			$conf->formationhabilitation->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array(
			'data'=>'user:+userformation:Formation - Habilitation:formationhabilitation@formationhabilitation:$user->rights->formationhabilitation->formation->readline||$user->rights->formationhabilitation->habilitation_autorisation->readline||$user->rights->formationhabilitation->volet->readline||$user->id==$object->id:/formationhabilitation/userformation.php?id=__ID__'
		);
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@formationhabilitation:$user->rights->formationhabilitation->read:/formationhabilitation/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@formationhabilitation:$user->rights->othermodule->read:/formationhabilitation/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view

		// Dictionaries
		$this->dictionaries=array(
			'langs'=>'formationhabilitation@formationhabilitation',
			'picto'=>'user',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array("c_famille_formation", "c_sousdomaine_formation", "c_examen_medical", "c_nature_visite", "c_domaine_application", "c_motif_visite", "c_qualification_profesionnelle"),
			// Label of tables
			'tablib'=>array("FamilleFormation", "SousDomaineFormation", "ExamensMedicaux", "NatureVisite", "DomainesApplication", "MotifVisite", "QualificationProfesionnelle"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_famille_formation as f', 'SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_sousdomaine_formation as f', 'SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_examen_medical as f', 'SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_nature_visite as f','SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_domaine_application as f', 'SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_motif_visite as f', 'SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_qualification_profesionnelle as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC", "label ASC", "label ASC", "label ASC", "label ASC", "label ASC", "label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("label", "label", "label", "label", "label", "label", "label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("label", "label", "label", "label", "label", "label", "label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("label", "label", "label", "label", "label", "label", "label"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid", "rowid", "rowid", "rowid", "rowid", "rowid", "rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->formationhabilitation->enabled, $conf->formationhabilitation->enabled, $conf->formationhabilitation->enabled, $conf->formationhabilitation->enabled, $conf->formationhabilitation->enabled, $conf->formationhabilitation->enabled, $conf->formationhabilitation->enabled),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp' => array('', '', '', '', '', '', ''),
		);
		

		// Boxes/Widgets
		// Add here list of php file(s) stored in formationhabilitation/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'formationhabilitationwidget1.php@formationhabilitation',
			//      'note' => 'Widget provided by FormationHabilitation',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			0 => array(
				'label' => 'Changement de statut des formations',
				'jobtype' => 'method',
				'class' => '/formationhabilitation/class/userformation.class.php',
				'objectname' => 'UserFormation',
				'method' => 'MajStatuts',
				'parameters' => '',
				'comment' => 'Permet de changer le statut des formations en fonction de la date de fin de validité',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 0,
				'test' => '$conf->formationhabilitation->enabled',
				'priority' => 50,
			),
			1 => array(
				'label' => 'Changement de statut des visites médicales',
				'jobtype' => 'method',
				'class' => '/formationhabilitation/class/visitemedical.class.php',
				'objectname' => 'VisiteMedical',
				'method' => 'MajStatuts',
				'parameters' => '',
				'comment' => 'Permet de changer le statut des visites médicales en fonction de la date de fin de validité',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 0,
				'test' => '$conf->formationhabilitation->enabled',
				'priority' => 50,
			),
			2 => array(
				'label' => 'Changement de statut des habilitations',
				'jobtype' => 'method',
				'class' => '/formationhabilitation/class/userhabilitation.class.php',
				'objectname' => 'UserHabilitation',
				'method' => 'MajStatuts',
				'parameters' => '',
				'comment' => 'Permet de changer le statut des habilitations en fonction de la date de fin de validité',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 0,
				'test' => '$conf->formationhabilitation->enabled',
				'priority' => 50,
			),
			3 => array(
				'label' => 'Changement de statut des autorisations',
				'jobtype' => 'method',
				'class' => '/formationhabilitation/class/userautorisation.class.php',
				'objectname' => 'UserAutorisation',
				'method' => 'MajStatuts',
				'parameters' => '',
				'comment' => 'Permet de changer le statut des autorisations en fonction de la date de fin de validité',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 0,
				'test' => '$conf->formationhabilitation->enabled',
				'priority' => 50,
			),
			4 => array(
				'label' => 'Changement de statut des volets',
				'jobtype' => 'method',
				'class' => '/formationhabilitation/class/uservolet.class.php',
				'objectname' => 'UserVolet',
				'method' => 'MajStatuts',
				'parameters' => '',
				'comment' => 'Permet de changer le statut des volets en fonction de la date de fin de validité',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 0,
				'test' => '$conf->formationhabilitation->enabled',
				'priority' => 50,
			),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->formationhabilitation->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->formationhabilitation->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 0 + 1);
		$this->rights[$r][1] = 'Lire les Formations (catalogue)';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 1 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Formations (catalogue)';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 2 + 1);
		$this->rights[$r][1] = 'Supprimer les Formations (catalogue)';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 4 + 1);
		$this->rights[$r][1] = 'Lire les Formations (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'readline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 6 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Formations (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'writeline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 5 + 1);
		$this->rights[$r][1] = 'Forcer la création des formations (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'forceline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 7 + 1);
		$this->rights[$r][1] = 'Supprimer les Formations (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'deleteline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 3 + 1);
		$this->rights[$r][1] = 'Lire les coûts relatifs aux formations';
		$this->rights[$r][4] = 'formation';
		$this->rights[$r][5] = 'readcout';
		$r++;


		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 0 + 1);
		$this->rights[$r][1] = 'Lire les Habilitations et Autorisations (catalogue)';
		$this->rights[$r][4] = 'habilitation_autorisation';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 1 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Habilitations et Autorisations (catalogue)';
		$this->rights[$r][4] = 'habilitation_autorisation';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 2 + 1);
		$this->rights[$r][1] = 'Supprimer les Habilitations et Autorisations (catalogue)';
		$this->rights[$r][4] = 'habilitation_autorisation';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 3 + 1);
		$this->rights[$r][1] = 'Lire les Habilitations et Autorisations (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'habilitation_autorisation';
		$this->rights[$r][5] = 'readline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 5 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Habilitations et Autorisations (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'habilitation_autorisation';
		$this->rights[$r][5] = 'writeline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 4 + 1);
		$this->rights[$r][1] = 'Forcer la création des habilitations et des autorisations liées à des utilisateurs';
		$this->rights[$r][4] = 'habilitation_autorisation';
		$this->rights[$r][5] = 'forceline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 6 + 1);
		$this->rights[$r][1] = 'Supprimer les Habilitations et Autorisations (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'habilitation_autorisation';
		$this->rights[$r][5] = 'deleteline';
		$r++;


		$this->rights[$r][0] = $this->numero . sprintf('%02d', (2 * 10) + 0 + 1);
		$this->rights[$r][1] = 'Lire les Volets (catalogue)';
		$this->rights[$r][4] = 'volet';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (2 * 10) + 1 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Volets (catalogue)';
		$this->rights[$r][4] = 'volet';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (2 * 10) + 2 + 1);
		$this->rights[$r][1] = 'Supprimer les Volets (catalogue)';
		$this->rights[$r][4] = 'volet';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (2 * 10) + 3 + 1);
		$this->rights[$r][1] = 'Lire les Volets (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'volet';
		$this->rights[$r][5] = 'readline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (2 * 10) + 4 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Volets (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'volet';
		$this->rights[$r][5] = 'writeline';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (2 * 10) + 5 + 1);
		$this->rights[$r][1] = 'Supprimer les Volets (assignées aux collaborateurs)';
		$this->rights[$r][4] = 'volet';
		$this->rights[$r][5] = 'deleteline';
		$r++;


		$this->rights[$r][0] = $this->numero . sprintf('%02d', (3 * 10) + 0 + 1);
		$this->rights[$r][1] = 'Lire les Visites médicales';
		$this->rights[$r][4] = 'visitemedical';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (3 * 10) + 1 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Visites médicales';
		$this->rights[$r][4] = 'visitemedical';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (3 * 10) + 2 + 1);
		$this->rights[$r][1] = 'Supprimer les Visites médicales';
		$this->rights[$r][4] = 'visitemedical';
		$this->rights[$r][5] = 'delete';
		$r++;


		$this->rights[$r][0] = $this->numero . sprintf('%02d', (4 * 10) + 0 + 1);
		$this->rights[$r][1] = 'Lire les Convocations';
		$this->rights[$r][4] = 'convocation';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (4 * 10) + 1 + 1);
		$this->rights[$r][1] = 'Créer/Modifier les Convocations';
		$this->rights[$r][4] = 'convocation';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (4 * 10) + 2 + 1);
		$this->rights[$r][1] = 'Supprimer les Convocations';
		$this->rights[$r][4] = 'convocation';
		$this->rights[$r][5] = 'delete';
		$r++;		
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'ModuleFormationHabilitationName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'formationhabilitation',
			'leftmenu'=>'',
			'url'=>'/formationhabilitation/formationhabilitationindex.php',
			'langs'=>'formationhabilitation@formationhabilitation', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->formationhabilitation->enabled', // Define condition to show or hide menu entry. Use '$conf->formationhabilitation->enabled' if entry must be visible if module is enabled.
			'perms'=>'', // Use 'perms'=>'$user->rights->formationhabilitation->formation->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		/* LEFTMENU FORMATIONS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation',
			 'type' => 'left',
			 'titre' => 'Formations',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_formation',
			 'url' => '/formationhabilitation/formation_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->formation->read',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU FORMATIONS */
		/* LEFTMENU NOUVELLE FORMATION */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=formationhabilitation_formation',
			 'type' => 'left',
			 'titre' => 'Nouvelle Formation',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_formationnew',
			 'url' => '/formationhabilitation/formation_card.php?action=create',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->formation->write',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU NOUVELLE FORMATION */
		/* LEFTMENU FORMATIONS DES COLLABORATEURS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=formationhabilitation_formation',
			 'type' => 'left',
			 'titre' => 'Formations des collaborateurs',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_userformationlist',
			 'url' => '/formationhabilitation/userformation_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->formation->readline',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU FORMATIONS DES COLLABORATEURS */
		/* LEFTMENU HABILITATIONS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation',
			 'type' => 'left',
			 'titre' => 'Habilitations',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_habilitation',
			 'url' => '/formationhabilitation/habilitation_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->habilitation_autorisation->read',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU HABILITATIONS */
		/* LEFTMENU NOUVELLE HABILITATION */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=formationhabilitation_habilitation',
			 'type' => 'left',
			 'titre' => 'Nouvelle Habilitation',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_habilitationnew',
			 'url' => '/formationhabilitation/habilitation_card.php?action=create',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->habilitation_autorisation->write',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU NOUVELLE HABILITATION */
		/* LEFTMENU HABILITATIONS DES COLLABORATEURS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=formationhabilitation_habilitation',
			 'type' => 'left',
			 'titre' => 'Habilitations des collaborateurs',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_userhabilitationlist',
			 'url' => '/formationhabilitation/userhabilitation_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->habilitation_autorisation->readline',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU HABILITATIONS DES COLLABORATEURS */
		/* LEFTMENU AUTORISATIONS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation',
			 'type' => 'left',
			 'titre' => 'Autorisations',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_autorisation',
			 'url' => '/formationhabilitation/autorisation_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->habilitation_autorisation->read',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU AUTORISATIONS */
		/* LEFTMENU NOUVELLE AUTORISATION */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=formationhabilitation_autorisation',
			 'type' => 'left',
			 'titre' => 'Nouvelle Autorisation',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_autorisationnew',
			 'url' => '/formationhabilitation/autorisation_card.php?action=create',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->habilitation_autorisation->write',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU NOUVELLE AUTORISATION */
		/* LEFTMENU AUTORISATIONS DES COLLABORATEURS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=formationhabilitation_autorisation',
			 'type' => 'left',
			 'titre' => 'Autorisations des collaborateurs',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_userautorisationlist',
			 'url' => '/formationhabilitation/userautorisation_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => '$conf->formationhabilitation->enabled',
			 'perms' => '$user->rights->formationhabilitation->habilitation_autorisation->readline',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU AUTORISATIONS DES COLLABORATEURS */
		/* LEFTMENU VOLETS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation',
			 'type' => 'left',
			 'titre' => 'Volets',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'volet',
			 'url' => '/formationhabilitation/volet_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => 'isModEnabled(\'formationhabilitation\')',
			 'perms' => '$user->hasRight(\'formationhabilitation\', \'volet\', \'read\')',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU VOLETS */
		/* LEFTMENU NOUVEAU VOLET D'UN COLLABORATEUR */
		// $this->menu[$r++]=array(
		// 	 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=volet',
		// 	 'type' => 'left',
		// 	 'titre' => 'Nouveau volet d\'un collaborateur',
		// 	 'mainmenu' => 'formationhabilitation',
		// 	 'leftmenu' => 'formationhabilitation_uservolet_new',
		// 	 'url' => '/formationhabilitation/uservolet_card.php?action=create',
		// 	 'langs' => 'formationhabilitation@formationhabilitation',
		// 	 'position' => 1000,
		// 	 'enabled' => 'isModEnabled(\'formationhabilitation\')',
		// 	 'perms' => '$user->hasRight(\'formationhabilitation\', \'volet\', \'writeline\')',
		// 	 'target' => '',
		// 	 'user' => 2,
		// );
		/* END LEFTMENU NOUVEAU VOLET D'UN COLLABORATEUR */
		/* LEFTMENU VOLETS DES COLLABORATEURS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=volet',
			 'type' => 'left',
			 'titre' => 'Volets des collaborateurs',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_uservolet_list',
			 'url' => '/formationhabilitation/uservolet_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => 'isModEnabled(\'formationhabilitation\')',
			 'perms' => '$user->hasRight(\'formationhabilitation\', \'volet\', \'readline\')',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU VOLETS DES COLLABORATEURS */
		/* LEFTMENU VISITES MéDICALES */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation',
			 'type' => 'left',
			 'titre' => 'Visites Médicales',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'visitemedical',
			 'url' => '/formationhabilitation/visitemedical_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => 'isModEnabled(\'formationhabilitation\')',
			 'perms' => '$user->hasRight(\'formationhabilitation\', \'visitemedical\', \'read\')',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU VISITES MéDICALES */
		/* LEFTMENU NOUVELLE VISITE MEDICALE */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=visitemedical',
			 'type' => 'left',
			 'titre' => 'Nouvelle Visite Medicale',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_visitemedical_new',
			 'url' => '/formationhabilitation/visitemedical_card.php?action=create',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => 'isModEnabled(\'formationhabilitation\')',
			 'perms' => '$user->hasRight(\'formationhabilitation\', \'visitemedical\', \'write\')',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU NOUVELLE VISITE MEDICALE */
		/* LEFTMENU CONVOCATIONS */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation',
			 'type' => 'left',
			 'titre' => 'Convocations',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'convocation',
			 'url' => '/formationhabilitation/convocation_list.php',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => 'isModEnabled(\'formationhabilitation\')',
			 'perms' => '$user->hasRight(\'formationhabilitation\', \'convocation\', \'read\')',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU CONVOCATIONS */
		/* LEFTMENU NOUVELLE CONVOCATION */
		$this->menu[$r++]=array(
			 'fk_menu' => 'fk_mainmenu=formationhabilitation,fk_leftmenu=convocation',
			 'type' => 'left',
			 'titre' => 'Nouvelle Convocation',
			 'mainmenu' => 'formationhabilitation',
			 'leftmenu' => 'formationhabilitation_convocation_new',
			 'url' => '/formationhabilitation/convocation_card.php?action=create',
			 'langs' => 'formationhabilitation@formationhabilitation',
			 'position' => 1000,
			 'enabled' => 'isModEnabled(\'formationhabilitation\')',
			 'perms' => '$user->hasRight(\'formationhabilitation\', \'convocation\', \'write\')',
			 'target' => '',
			 'user' => 2,
		);
		/* END LEFTMENU NOUVELLE CONVOCATION */


		/* END MODULEBUILDER LEFTMENU MYOBJECT */






		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT FORMATION */
		$langs->load("formationhabilitation@formationhabilitation");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='UserFormationLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='fa-user-graduate_fas_#1f3d89';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'UserFormation'; $keyforclassfile='/formationhabilitation/class/userformation.class.php'; $keyforelement='fa-user-graduate_fas_#1f3d89';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$this->export_fields_array[$r]['f.label']='Label'; $this->export_TypeFields_array[$r]['f.label']='Text'; $this->export_entities_array[$r]['f.label']='fa-graduation-cap_fas_#1f3d89';
		$this->export_fields_array[$r]['u.firstname']='Firstname'; $this->export_TypeFields_array[$r]['u.firstname']='Text'; $this->export_entities_array[$r]['u.firstname']='user';
		$this->export_fields_array[$r]['u.lastname']='Lastname'; $this->export_TypeFields_array[$r]['u.lastname']='Text'; $this->export_entities_array[$r]['u.lastname']='user';
		$this->export_fields_array[$r]['t.fk_societe']='Organisme2';	$this->export_fields_array[$r]['t.formateur']='Formateur2';
		unset($this->export_fields_array[$r]['t.fk_formation']);
		//$keyforclass = 'FormationLine'; $keyforclassfile='/formationhabilitation/class/formation.class.php'; $keyforelement='formationline@formationhabilitation'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='userformation'; $keyforaliasextra='extra'; $keyforelement='fa-user-graduate_fas_#1f3d89';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='formationline'; $keyforaliasextra='extraline'; $keyforelement='formationline@formationhabilitation';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('formationline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		$this->export_special_array[$r] = array(
			't.nombre_heure' => array('rule' => 'compute', 'classfile' => '/custom/formationhabilitation/class/userformation.class.php', 'class' => 'UserFormation', 'method' => 'formatDuration', 'element' => 'userformation', 'method_params' => array('t_nombre_heure')),
		);
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'formationhabilitation_userformation as t';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'formationhabilitation_formation as f ON t.fk_formation = f.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON t.fk_user = u.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		//$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('formation').')';
		$r++;
		/* END MODULEBUILDER EXPORT FORMATION */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT FORMATION */
		$langs->load("formationhabilitation@formationhabilitation");

		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='Formation';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='fa-graduation-cap_fas_#1f3d89';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'formationhabilitation_formation');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_creat'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'Formation'; $keyforclassfile='/formationhabilitation/class/formation.class.php'; $keyforelement='formation@formationhabilitation';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='formation'; $keyforaliasextra='extra'; $keyforelement='formation@formationhabilitation';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'formationhabilitation_formation', /*'t.delaisprogrammation' => 'const-6'*/);
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.rowid' => 'ID');
		$this->import_convertvalue_array[$r] = array(
			// 't.ref' => array(
			// 	'rule'=>'getrefifauto',
			// 	'class'=>(!getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON') ? 'mod_elementprerequis_standard' : getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON')),
			// 	'path'=>"/core/modules/commande/".(!getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON') ? 'mod_elementprerequis_standard' : getDolGlobalString('FORMATIONHABILITATION_MYOBJECT_ADDON')).'.php'
			// 	'classobject'=>'ElementPrerequis',
			// 	'pathobject'=>'/formationhabilitation/class/elementprerequis.class.php',
			// ),
			//'t.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++;

		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='UserFormation';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='fa-user-graduate_fas_#1f3d89';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'formationhabilitation_userformation');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_creat'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'UserFormation'; $keyforclassfile='/formationhabilitation/class/userformation.class.php'; $keyforelement='userformation@formationhabilitation';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='userformation'; $keyforaliasextra='extra'; $keyforelement='userformation@formationhabilitation';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'formationhabilitation_userformation');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++;



		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='Habilitation';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='fa-cog_fa_#c46c0e';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'formationhabilitation_habilitation');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_creat'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'Habilitation'; $keyforclassfile='/formationhabilitation/class/habilitation.class.php'; $keyforelement='habilitation@formationhabilitation';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='habilitation'; $keyforaliasextra='extra'; $keyforelement='habilitation@formationhabilitation';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'formationhabilitation_habilitation');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.rowid' => 'ID');
		$this->import_convertvalue_array[$r] = array(
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++;

		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='UserHabilitation';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='fa-user-gear_fas_#c46c0e';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'formationhabilitation_userhabilitation');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_creat'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'UserHabilitation'; $keyforclassfile='/formationhabilitation/class/userhabilitation.class.php'; $keyforelement='userhabilitation@formationhabilitation';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='userhabilitation'; $keyforaliasextra='extra'; $keyforelement='userhabilitation@formationhabilitation';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'formationhabilitation_userhabilitation');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++;


		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='Autorisation';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='fa-check_fas_green';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'formationhabilitation_autorisation');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_creat'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'Autorisation'; $keyforclassfile='/formationhabilitation/class/autorisation.class.php'; $keyforelement='autorisation@formationhabilitation';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='autorisation'; $keyforaliasextra='extra'; $keyforelement='autorisation@formationhabilitation';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'formationhabilitation_autorisation');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.rowid' => 'ID');
		$this->import_convertvalue_array[$r] = array(
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++;

		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='UserAutorisation';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='fa-user-check_fas_green';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'formationhabilitation_userautorisation');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_creat'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'UserAutorisation'; $keyforclassfile='/formationhabilitation/class/userautorisation.class.php'; $keyforelement='userautorisation@formationhabilitation';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='userautorisation'; $keyforaliasextra='extra'; $keyforelement='userautorisation@formationhabilitation';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'formationhabilitation_userautorisation');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++;
		/* END MODULEBUILDER IMPORT FORMATION */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		//$result = $this->_load_tables('/install/mysql/tables/', 'formationhabilitation');
		$result = $this->_load_tables('/formationhabilitation/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('fournisseur', "Fournisseur", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'formationhabilitation@formationhabilitation', '$conf->formationhabilitation->enabled');
		//$result2=$extrafields->addExtraField('formationhabilitation_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'formationhabilitation@formationhabilitation', '$conf->formationhabilitation->enabled');
		//$result3=$extrafields->addExtraField('formationhabilitation_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'formationhabilitation@formationhabilitation', '$conf->formationhabilitation->enabled');
		//$result4=$extrafields->addExtraField('formationhabilitation_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'formationhabilitation@formationhabilitation', '$conf->formationhabilitation->enabled');
		//$result5=$extrafields->addExtraField('formationhabilitation_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'formationhabilitation@formationhabilitation', '$conf->formationhabilitation->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('formationhabilitation');
		$myTmpObjects = array();
		$myTmpObjects['Formation'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'Formation') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_formations.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_formations.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, 0, 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
