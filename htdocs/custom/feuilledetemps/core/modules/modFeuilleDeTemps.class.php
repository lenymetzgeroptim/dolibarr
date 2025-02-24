<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021       METZGER Leny            <leny-07@hotmail.fr>
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
 * 	\defgroup   feuilledetemps     Module FeuilleDeTemps
 *  \brief      FeuilleDeTemps module descriptor.
 *
 *  \file       htdocs/feuilledetemps/core/modules/modFeuilleDeTemps.class.php
 *  \ingroup    feuilledetemps
 *  \brief      Description and activation file for module FeuilleDeTemps
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module FeuilleDeTemps
 */
class modFeuilleDeTemps extends DolibarrModules
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
		$this->numero = 500004; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'feuilledetemps';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleFeuilleDeTempsName' not found (FeuilleDeTemps is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleFeuilleDeTempsDesc' not found (FeuilleDeTemps is name of module).
		$this->description = "Permet la gestion des feuilles de temps";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "";

		// Author
		$this->editor_name = 'Lény METZGER';
		$this->editor_url = '';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where FEUILLEDETEMPS is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'timesheet_16@feuilledetemps';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
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
                '/feuilledetemps/css/feuilledetemps.css.php',
                '/feuilledetemps/lib/FixedHeaderTable/css/defaultTheme.css'
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/feuilledetemps/js/feuilledetemps.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				   'data' => array(
				       'globalcard',
					   'dictionaryadmin',
					   'index',
				   ),
				//   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/feuilledetemps/temp","/feuilledetemps/subdir");
		$this->dirs = array("/feuilledetemps/temp");

		// Config pages. Put here list of php page, stored into feuilledetemps/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@feuilledetemps");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("feuilledetemps@feuilledetemps");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'FeuilleDeTempsWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('FEUILLEDETEMPS_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('FEUILLEDETEMPS_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
			1 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_SENDMAIL', 'chaine', '1', '', 0), // Lors de l'envoi de mail 
			2 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_CREATE', 'chaine', '1', '', 0), // Lors de la création
			3 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_MODIFY', 'chaine', '1', '', 0), // Lors de la modification
			4 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_DELETE', 'chaine', '1', '', 0), // Lors de la suppression
			5 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_APPROBATION1', 'chaine', '1', '', 0), // Lors du passage Brouillon -> Approbation 1
			6 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_APPROBATION2', 'chaine', '1', '', 0), // Lors du passage Approbation 1 -> Approbation 2
			7 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_VALIDATE', 'chaine', '1', '', 0), // Lors de la validation
			8 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_VERIFICATION', 'chaine', '1', '', 0), // Lors du passage Approbation 2 -> Vérification
			9 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_REFUS', 'chaine', '1', '', 0), // Lors du refus
			10 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_EXPORTED', 'chaine', '1', '', 0),
			11 => array('MAIN_AGENDA_ACTIONAUTO_FEUILLEDETEMPS_TASKVALIDATION', 'chaine', '1', '', 0), // Lorsqu'un utilisateur appouve la feuille de temps
		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->feuilledetemps) || !isset($conf->feuilledetemps->enabled)) {
			$conf->feuilledetemps = new stdClass();
			$conf->feuilledetemps->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array(
		        'data'=>'project:+timespent_heure_sup:Temps consommé (HS):feuilledetemps@feuilledetemps:1:/feuilledetemps/time_heure_sup.php?withproject=1&projectid=__ID__',
		                'project:-timespent::1',
		                'task:+task_time_heure_sup:Temps consommé (HS):feuilledetemps@feuilledetemps:1:/feuilledetemps/time_heure_sup.php?withproject=1&id=__ID__',
		                'task:-task_time::1',
		                'project:+vue_ensemble_HS:Vue d\'ensemble:feuilledetemps@feuilledetemps:$user->rights->feuilledetemps->feuilledetemps->vueensemble:/feuilledetemps/vue_ensemble_projet.php?id=__ID__',
		                'project:-element::1',
						'project:+report:projectReport:feuilledetemps@feuilledetemps:$user->rights->feuilledetemps->reports->read:/feuilledetemps/rapport_projet.php?projectSelected=__ID__',
						'project:+invoice:projectInvoice:feuilledetemps@feuilledetemps:$user->rights->feuilledetemps->feuilledetemps->facturertemps:/feuilledetemps/TimesheetProjectInvoice.php?projectid=__ID__'
		    );
		
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@feuilledetemps:$user->rights->feuilledetemps->read:/feuilledetemps/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@feuilledetemps:$user->rights->othermodule->read:/feuilledetemps/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		$this->dictionaries = array();
		/* Example:
		$this->dictionaries=array(
			'langs'=>'feuilledetemps@feuilledetemps',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."table1", MAIN_DB_PREFIX."table2", MAIN_DB_PREFIX."table3"),
			// Label of tables
			'tablib'=>array("Table1", "Table2", "Table3"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid", "rowid", "rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->feuilledetemps->enabled, $conf->feuilledetemps->enabled, $conf->feuilledetemps->enabled)
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in feuilledetemps/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'feuilledetempswidget1.php@feuilledetemps',
			//      'note' => 'Widget provided by FeuilleDeTemps',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			// 0 => array(
			// 	'label' => 'Vérification retard enregistrement FDT hebdomadaire',
			// 	'jobtype' => 'method',
			// 	'class' => '/feuilledetemps/class/feuilledetemps.class.php',
			// 	'objectname' => 'FeuilleDeTemps',
			// 	'method' => 'MailFDT_WeeklySave',
			// 	'parameters' => '',
			// 	'comment' => 'Envoie un mail pour les FDT qui n\'ont pas été enregistrées durant la semaine.',
			// 	'frequency' => 1,
			// 	'unitfrequency' => 604800,
			// 	'status' => 1,
			// 	'test' => '$conf->feuilledetemps->enabled',
			// 	'priority' => 50,
			// ),
			1 => array(
				'label' => 'Vérification retard transfert FDT mensuel',
				'jobtype' => 'method',
				'class' => '/feuilledetemps/class/feuilledetemps.class.php',
				'objectname' => 'FeuilleDeTemps',
				'method' => 'MailFDT_MonthlyTransfer',
				'parameters' => 'jour',
				'comment' => 'Envoie un mail pour les FDT qui n\'ont pas été transferées à partir du 20 de chaque mois.',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 1,
				'test' => '$conf->feuilledetemps->enabled',
				'priority' => 50,
			),
			2 => array(
				'label' => 'Création des FDT mensuel',
				'jobtype' => 'method',
				'class' => '/feuilledetemps/class/feuilledetemps.class.php',
				'objectname' => 'FeuilleDeTemps',
				'method' => 'createAllFeuilleDeTemps',
				'parameters' => '',
				'comment' => 'Crée toutes les feuilles de temps qui ne sont pas créées le 1er jour de chaque mois',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 1,
				'test' => '$conf->feuilledetemps->enabled',
				'priority' => 50,
			),
			3 => array(
				'label' => 'Mail export feuille de temps mensuel',
				'jobtype' => 'method',
				'class' => '/feuilledetemps/class/feuilledetemps.class.php',
				'objectname' => 'FeuilleDeTemps',
				'method' => 'MailFDT_MonthlyExport',
				'parameters' => 'pointage@optim-industries.fr, jm.pierre@optim-industries.fr',
				'comment' => 'Envoie un mail avec un export des heures travaillées et des déplacements par collaborateur le 1er de chaque mois',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 1,
				'test' => '$conf->feuilledetemps->enabled',
				'priority' => 50,
			),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->feuilledetemps->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->feuilledetemps->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Lire ses propres feuilles de temps';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Lire toutes les feuilles de temps';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'readall';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Lire les feuilles de temps de sa sous-hiérarchie';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'readHierarchy';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Permet d\'accéder à la liste des feuilles de temps à valider pour un responsable';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'read_listeResponsable';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Modifier les temps des feuilles de temps après vérification';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'modify';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Supprimer les feuilles de temps';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Accès à la page \'Vue d\'ensemble\' des projets';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'vueensemble';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Accès à la page \'Facturer le temps\' des projets';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'facturertemps';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Accès au Rapport Utilisateur';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'rapportUtilisateur';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Accès à l\'export des feuilles de temps';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'export';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Modifier les feuilles de temps lors de la vérification';
		$this->rights[$r][4] = 'feuilledetemps';
		$this->rights[$r][5] = 'modify_verification';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Accès à l\'ensemble des temps consommé';
		$this->rights[$r][4] = 'timespent';
		$this->rights[$r][5] = 'readall';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Lire les coûts sur la page "Temps consommé" des projets/tâches';
		$this->rights[$r][4] = 'timespent';
		$this->rights[$r][5] = 'read_value';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Créer des temps consommé sur les projets/tâches';
		$this->rights[$r][4] = 'timespent';
		$this->rights[$r][5] = 'add';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Accès au Rapport Projet';
		$this->rights[$r][4] = 'reports';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = 'Modifier les approbateurs des feuilles de temps'; // Permission label
		$this->rights[$r][4] = 'changeappro'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
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
			'titre'=>'Feuille de temps',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'feuilledetemps',
			'leftmenu'=>'',
			'url'=>'/feuilledetemps/timesheet.php',
			'langs'=>'feuilledetemps@feuilledetemps', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->feuilledetemps->enabled', // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled.
			'perms'=>'1', // Use 'perms'=>'$user->rights->feuilledetemps->feuilledetemps->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER TOPMENU */

		/* BEGIN MODULEBUILDER LEFTMENU FEUILLEDETEMPS */
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=feuilledetemps',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'Liste Feuille de temps',
            'mainmenu'=>'feuilledetemps',
            'leftmenu'=>'feuilledetemps_feuilledetemps',
            'url'=>'/feuilledetemps/feuilledetemps_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'feuilledetemps@feuilledetemps',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->feuilledetemps->enabled',
            // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );

		$this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=feuilledetemps',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'Liste à approuver',
            'mainmenu'=>'feuilledetemps',
            'leftmenu'=>'feuilledetemps_feuilledetemps',
            'url'=>'/feuilledetemps/feuilledetemps_list_responsable.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'feuilledetemps@feuilledetemps',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->feuilledetemps->enabled',
            // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->feuilledetemps->feuilledetemps->read_listeResponsable',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );

		$this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=feuilledetemps',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'Liste Compta',
            'mainmenu'=>'feuilledetemps',
            'leftmenu'=>'feuilledetemps_feuilledetemps',
            'url'=>'/feuilledetemps/observationcompta_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'feuilledetemps@feuilledetemps',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->feuilledetemps->enabled',
            // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->feuilledetemps->feuilledetemps->modify_verification',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );

		$this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=feuilledetemps',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'Rapport Utilisateur',
            'mainmenu'=>'feuilledetemps',
            'leftmenu'=>'feuilledetemps_feuilledetemps',
            'url'=>'/feuilledetemps/TimesheetReportUser.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'feuilledetemps@feuilledetemps',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->feuilledetemps->enabled',
            // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->feuilledetemps->feuilledetemps->rapportUtilisateur',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=project,fk_leftmenu=projects',                // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                                        						// This is a Left menu entry
			'titre'=>'projectReport',
			'mainmenu'=>'project',
			'leftmenu'=>'projectReport',
			'url'=>'/feuilledetemps/rapport_projet.php',
			'langs'=>'feuilledetemps@feuilledetemps',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->feuilledetemps->enabled', // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->feuilledetemps->reports->read',                                        // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,
		);


		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=feuilledetemps',
			'type'=>'left',                                        						// This is a Left menu entry
			'titre'=>'projectExport',
			'mainmenu'=>'feuilledetemps',
			'leftmenu'=>'feuilledetemps_projectExport',
			'url'=>'/feuilledetemps/export.php',
			'langs'=>'feuilledetemps@feuilledetemps',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->feuilledetemps->enabled', // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->feuilledetemps->feuilledetemps->export',                                        // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,
		);


		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=feuilledetemps',
			'type'=>'left',                                        						// This is a Left menu entry
			'titre'=> $langs->trans("NewHoliday"),
			'mainmenu'=>'feuilledetemps',
			'leftmenu'=>'holiday_create',
			'url'=>'/holidaycustom/card.php?action=create',
			'langs'=>'feuilledetemps@feuilledetemps',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->feuilledetemps->enabled&&$conf->holidaycustom->enabled', // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->holidaycustom->write&&$conf->global->FDT_SHORTCUT_HOLIDAY',                                        // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,
		);

        /*$this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=feuilledetemps,fk_leftmenu=feuilledetemps_feuilledetemps',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'New FeuilleDeTemps',
            'mainmenu'=>'feuilledetemps',
            'leftmenu'=>'feuilledetemps_feuilledetemps',
            'url'=>'/feuilledetemps/feuilledetemps_card.php?action=create',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'feuilledetemps@feuilledetemps',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->feuilledetemps->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->feuilledetemps->enabled',
            // Use 'perms'=>'$user->rights->feuilledetemps->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );*/

		/* END MODULEBUILDER LEFTMENU FEUILLEDETEMPS */

		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT FEUILLEDETEMPS */
		// $langs->load("feuilledetemps@feuilledetemps");
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='FeuilleDeTempsExportCompta';	// Translation key (used only if key ExportDataset_xxx_z not found)
		// $this->export_permission[$r] = array(array("feuilledetemps", "feuilledetemps", "modify_verification"));
		// $this->export_icon[$r]='timesheet_16@feuilledetemps';
		// // Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		// $keyforclass = 'ObservationCompta'; $keyforclassfile='/feuilledetemps/class/observationcompta.class.php'; $keyforelement='Observation Compta';
		// include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		// $keyforselect='ObservationCompta'; $keyforaliasextra='extra'; $keyforelement='Observation Compta';
		// include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// unset($this->export_fields_array[$r]['t.rowid']);
		// unset($this->export_fields_array[$r]['t.date_creation']);
		// unset($this->export_fields_array[$r]['t.tms']);
		// unset($this->export_fields_array[$r]['t.fk_user_creat']);
		// unset($this->export_fields_array[$r]['t.fk_user_modif']);

		// //$this->export_dependencies_array[$r] = array('feuilledetempsline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		// //$this->export_special_array[$r] = array('t.field'=>'...');
		// //$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		// //$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'feuilledetemps_observationcompta as t';
		// $this->export_sql_end[$r] .=' WHERE 1 = 1';
		// $r++; 

		// $this->export_code[$r] = $this->rights_class.'_'.$r;
		// $this->export_label[$r] = 'Heures mensuel des collaborateurs';
		// $this->export_permission[$r] = array(array("feuilledetemps", "feuilledetemps", "modify_verification"));
		// $this->export_fields_array[$r] = array(
		// 	'u.firstname'=>"Prénom", 'u.lastname'=>"Nom", 'ue.matricule'=>"Matricule", 'MONTH(et.element_date)'=>"Mois", 'et.element_date'=>"Date", 'SUM(et.element_duration) / 3600 as hour'=>"Nombre d'heure",
		// );
		// $this->export_TypeFields_array[$r] = array(
		// 	'u.firstname'=>"Text", 'u.lastname'=>"Text", 'ue.matricule'=>"Numeric", 'MONTH(et.element_date)'=>"Numeric", 'et.element_date'=>"Date", 'SUM(et.element_duration) / 3600 as hour'=>"Numeric",
		// );
		// $this->export_entities_array[$r] = array(
		// 	'u.firstname'=>"user", 'u.lastname'=>"user", 'ue.matricule'=>"user", 'MONTH(et.element_date)'=>"timesheet_16@feuilledetemps", 'et.element_date'=>"timesheet_16@feuilledetemps", 'SUM(et.element_duration) / 3600 as hour'=>"timesheet_16@feuilledetemps",
		// );

		// $this->export_sql_start[$r] = 'SELECT DISTINCT ';
		// $this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'user as u';
		// $this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as ue ON u.rowid = ue.fk_object';
		// $this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_time as et ON u.rowid = et.fk_user';
		// $this->export_sql_end[$r] .= ' WHERE u.entity IN ('.getEntity('user').')';
		// $this->export_sql_end[$r] .= ' AND et.elementtype = \'task\'';
		// $this->export_sql_order[$r] .= ' GROUP BY u.rowid, MONTH(et.element_date)';
		// $this->export_sql_order[$r] .= ' ORDER BY u.lastname, u.firstname, MONTH(et.element_date)';

		/* END MODULEBUILDER EXPORT FEUILLEDETEMPS */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT FEUILLEDETEMPS */
		$langs->load("feuilledetemps@feuilledetemps");
		// $this->import_code[$r] = $this->rights_class.'_'.$r;
		// $this->import_label[$r] = 'Données RH Déplacement des utilisateurs';
		// $this->import_icon[$r] = 'user';
		// $this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		// $this->import_tables_array[$r] = array('u'=>MAIN_DB_PREFIX.'donneesrh_Deplacement_extrafields'); // List of tables to insert into (insert done in same order)
		// $this->import_fields_array[$r] = array(
		// 	'u.fk_object'=>"Matricule", 'u.systeme'=>"Système", 'u.datedapplicationsysteme'=>"Date d'application Système",
		// 	'u.montantd1'=>"Indemnité D1", 'u.d_1'=>"D1", 'u.datedapplicationd1'=>"Date d'application D1",
		// 	'u.montantd2'=>"Indemnité D2", 'u.d_2'=>"D2", 'u.datedapplicationd2'=>"Date d'application D2",
		// 	'u.montantd3'=>"Indemnité D3", 'u.d_3'=>"D3", 'u.datedapplicationd3'=>"Date d'application D3",
		// 	'u.montantd4'=>"Indemnité D4", 'u.d_4'=>"D4", 'u.datedapplicationd4'=>"Date d'application D4",
		// 	'u.panier1'=>"Panier 1", 'u.datedapplicationpanier1'=>"Date d'application panier 1",
		// 	'u.panier2'=>"Panier 2", 'u.datedapplicationpanier2'=>"Date d'application panier 2",
		// 	'u.typegd1'=>"Type GD1", 'u.gd1'=>"GD1", 'u.distancegd1'=>"Distance GD1", 'u.heurederoutegd1'=>"Heure de route GD1", 'u.datedapplicationgd1'=>"Date d'application GD1",
		// 	'u.typegd3'=>"Type GD3", 'u.gd3'=>"GD3", 'u.distancegd3'=>"Distance GD3", 'u.heurederoutegd3'=>"Heure de route GD3", 'u.datedapplicationgd3'=>"Date d'application GD3",
		// 	'u.typegd4'=>"Type GD4", 'u.gd4'=>"GD4", 'u.distancegd4'=>"Distance GD4", 'u.heurederoutegd4'=>"Heure de route GD4", 'u.datedapplicationgd4'=>"Date d'application GD4",
		// 	'u.vehicule'=>"Véhicule", 'u.datedapplicationvehicule'=>"Date d'application véhicule",
		// );
		// // $this->import_fieldshidden_array[$r] = array('u.tms'=>'334'); // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		// $this->import_examplevalues_array[$r] = array(
		// 	'u.fk_object'=>"389", 'u.systeme'=>"NK", 'u.datedapplicationsysteme'=>"18/04/2024",
		// 	'u.montantd1'=>"10", 'u.d_1'=>"Bron", 'u.datedapplicationd1'=>"18/04/2024",
		// 	'u.montantd2'=>"10", 'u.d_2'=>"Bron", 'u.datedapplicationd2'=>"18/04/2024",
		// 	'u.montantd3'=>"10", 'u.d_3'=>"Bron", 'u.datedapplicationd3'=>"18/04/2024",
		// 	'u.montantd4'=>"10", 'u.d_4'=>"Bron", 'u.datedapplicationd4'=>"18/04/2024",
		// 	'u.panier1'=>"Panier 1", 'u.datedapplicationpanier1'=>"18/04/2024",
		// 	'u.panier2'=>"Panier 2", 'u.datedapplicationpanier2'=>"18/04/2024",
		// 	'u.typegd1'=>"GRAND D", 'u.gd1'=>"CNPE TRICASTIN", 'u.distancegd1'=>"50", 'u.heurederoutegd1'=>"Heure de route GD1", 'u.datedapplicationgd1'=>"18/04/2024",
		// 	'u.typegd3'=>"GRAND D", 'u.gd3'=>"CNPE TRICASTIN", 'u.distancegd3'=>"50", 'u.heurederoutegd3'=>"Heure de route GD3", 'u.datedapplicationgd3'=>"18/04/2024",
		// 	'u.typegd4'=>"GRAND D", 'u.gd4'=>"CNPE TRICASTIN", 'u.distancegd4'=>"50", 'u.heurederoutegd4'=>"Heure de route GD4", 'u.datedapplicationgd4'=>"18/04/2024",
		// 	'u.vehicule'=>"VS", 'u.datedapplicationvehicule'=>"18/04/2024",
		// );
		// $this->import_updatekeys_array[$r] = array('u.fk_object'=>'Matricule');
		// $this->import_regex_array[$r] = array(
		// 	//'u.datedapplicationsysteme'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
		// );
		// $this->import_convertvalue_array[$r] = array(
		// 	'u.fk_object' => array('rule' => 'fetchidfromcodeunits', 'file' => '/custom/feuilledetemps/class/extendedUser.class.php', 'class' => 'ExtendedUser3', 'method' => 'fetchIdWithMatricule', 'element' => 'user'),
		// 	'u.systeme' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatSysteme'),
		// 	'u.datedapplicationsysteme' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.datedapplicationd1' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.datedapplicationd2' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.datedapplicationd3' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.datedapplicationd4' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.panier1' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatPanier'),
		// 	'u.datedapplicationpanier1' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.panier2' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatPanier'),
		// 	'u.datedapplicationpanier2' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.typegd1' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatTypeGD'),
		// 	'u.datedapplicationgd1' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.typegd3' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatTypeGD'),
		// 	'u.datedapplicationgd3' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.typegd4' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatTypeGD'),
		// 	'u.datedapplicationgd4' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// 	'u.vehicule' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatVehicule'),
		// 	'u.datedapplicationvehicule' => array('rule' => 'compute', 'file' => '/custom/feuilledetemps/class/feuilledetemps.class.php', 'class' => 'Feuilledetemps', 'method' => 'formatDate'),
		// );
		// $this->import_run_sql_after_array[$r] = array();
		// $r++; 
		/* END MODULEBUILDER IMPORT FEUILLEDETEMPS */
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

		$result = $this->_load_tables('/feuilledetemps/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('feuilledetemps_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'feuilledetemps@feuilledetemps', '$conf->feuilledetemps->enabled');
		//$result2=$extrafields->addExtraField('feuilledetemps_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'feuilledetemps@feuilledetemps', '$conf->feuilledetemps->enabled');
		//$result3=$extrafields->addExtraField('feuilledetemps_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'feuilledetemps@feuilledetemps', '$conf->feuilledetemps->enabled');
		//$result4=$extrafields->addExtraField('feuilledetemps_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'feuilledetemps@feuilledetemps', '$conf->feuilledetemps->enabled');
		//$result5=$extrafields->addExtraField('feuilledetemps_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'feuilledetemps@feuilledetemps', '$conf->feuilledetemps->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = 'feuilledetemps';
		$myTmpObjects = array();
		$myTmpObjects['FeuilleDeTemps'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'FeuilleDeTemps') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/feuilledetemps/template_feuilledetempss.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/feuilledetemps';
				$dest = $dirodt.'/template_feuilledetempss.odt';

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
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".strtolower($myTmpObjectKey)."' AND entity = ".$conf->entity,
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."','".strtolower($myTmpObjectKey)."',".$conf->entity.")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".strtolower($myTmpObjectKey)."' AND entity = ".$conf->entity,
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".strtolower($myTmpObjectKey)."', ".$conf->entity.")"
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
