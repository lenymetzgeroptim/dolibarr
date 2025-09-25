<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
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
 * 	\defgroup   sse     Module SSE
 *  \brief      SSE module descriptor.
 *
 *  \file       htdocs/sse/core/modules/modSSE.class.php
 *  \ingroup    sse
 *  \brief      Description and activation file for module SSE
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module SSE
 */
class modSSE extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf, $user;
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 500016; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'sse';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleSSEName' not found (SSE is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleSSEDesc' not found (SSE is name of module).
		$this->description = "SSEDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "SSEDescription";

		// Author
		$this->editor_name = 'SFA';
		$this->editor_url = 'https://www.example.com';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where SSE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fa-comments';

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
				//    '/sse/css/sse.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/sse/js/sse.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				//   'data' => array(
				//       'hookcontext1',
				//       'hookcontext2',
				//   ),
				//   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/sse/temp","/sse/subdir");
		$this->dirs = array("/sse/temp");

		// Config pages. Put here list of php page, stored into sse/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@sse");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("sse@sse");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'SSEWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('SSE_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('SSE_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		// $this->const = array();
		$this->const = array(
			1 => array('MAIN_AGENDA_ACTIONAUTO_CAUSERIE_CREATE', 'chaine', 'causerie@sse', '', 0), 
			2 => array('MAIN_AGENDA_ACTIONAUTO_CAUSERIE_MODIFY', 'chaine', 'causerie@sse', '', 0), 
			// 3 => array('MAIN_AGENDA_ACTIONAUTO_CAUSERIE_DELETE', 'chaine', 'causerie@sse', '', 0), 
			3 => array('MAIN_AGENDA_ACTIONAUTO_CAUSERIE_VALIDATE', 'chaine', 'causerie@sse', '', 0),
			4 => array('MAIN_AGENDA_ACTIONAUTO_CAUSERIE_SCHEDULE_TALK', 'chaine', 'causerie@sse', '', 0),
			5 => array('MAIN_AGENDA_ACTIONAUTO_CAUSERIE_REALIZED', 'chaine', 'causerie@sse', '', 0),
			6 => array('MAIN_AGENDA_ACTIONAUTO_CAUSERIE_CLOSED', 'chaine', 'causerie@sse', '', 0),
			6 => array('MAIN_AGENDA_ACTIONAUTO_USER_CAUSERIE_MODIFY', 'chaine', 'causerie@sse', '', 0),
		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->sse) || !isset($conf->sse->enabled)) {
			$conf->sse = new stdClass();
			$conf->sse->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@sse:$user->rights->sse->read:/sse/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@sse:$user->rights->othermodule->read:/sse/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
			'langs'=>'sse@sse',
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
			'tabcond'=>array($conf->sse->enabled, $conf->sse->enabled, $conf->sse->enabled)
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in sse/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'ssewidget1.php@sse',
			//      'note' => 'Widget provided by SSE',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/sse/class/causerie.class.php',
			//      'objectname' => 'Causerie',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->sse->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->sse->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->sse->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Lire une causerie en organisation'; // Permission label
		// $this->rights[$r][4] = 'causerie';
		// $this->rights[$r][5] = 'read_organisation'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->read)
		// $r++;
		
		
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Lire une causerie en animation'; // Permission label
		// $this->rights[$r][4] = 'causerie';
		// $this->rights[$r][5] = 'read_animation'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->read)
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Confirmer et annimer une causerie'; // Permission label
		// $this->rights[$r][4] = 'causerie';
		// $this->rights[$r][5] = 'write_animation'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write)
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Supprimer une causerie en animation'; // Permission label
		// $this->rights[$r][4] = 'causerie';
		// $this->rights[$r][5] = 'delete_animation'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->delete)
		// $r++;
		
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Confirmer et annimer une causerie'; // Permission label
		// $this->rights[$r][4] = 'causerie';
		// $this->rights[$r][5] = 'write_causerie'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write)
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Supprimer une causerie en animation'; // Permission label
		// $this->rights[$r][4] = 'causerie';
		// $this->rights[$r][5] = 'delete_causerie'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->delete)
		// $r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer ou modifier les causeries et leur animation (Les droits de modification et de suppression de chaque causerie sont, par défaut, attribués à l\'utilisateur qui a créé la causerie ou désigné comme animateur).'; // Permission label
		$this->rights[$r][4] = 'causerie';
		$this->rights[$r][5] = 'write_causerie'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write_emargement)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire les documents joints.'; // Permission label
		$this->rights[$r][4] = 'causerie';
		$this->rights[$r][5] = 'read'; 
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire toutes les causeries.'; // Permission label
		$this->rights[$r][4] = 'causerie';
		$this->rights[$r][5] = 'read_all'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write_emargement)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = "Lire ses causeries : celles qu'il a créées, celles où il est désigné comme animateur ou celles où il participe."; // Permission label
		$this->rights[$r][4] = 'causerie';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write_emargement)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer ou modifier les causeries avant réalisation.'; // Permission label
		$this->rights[$r][4] = 'causerie';
		$this->rights[$r][5] = 'delete_modify_causerie'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write_emargement)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Gérer les causeries après réalisation et clôture. (Super administrateur avec accès à toutes les causeries et au tableau de bord.).'; // Permission label
		$this->rights[$r][4] = 'causerie';
		$this->rights[$r][5] = 'admin_causerie'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write_emargement)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer, modifier et consulter les objectifs liés au nombre de causeries associées à une période, avant leur validation.'; // Permission label
		$this->rights[$r][4] = 'goal';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire et suivre la progression des causeries.'; // Permission label
		$this->rights[$r][4] = 'goal';
		$this->rights[$r][5] = 'follow'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Modifie et supprimer les objectifs liés au nombre de causeries associées aprés validation.'; // Permission label
		$this->rights[$r][4] = 'goal';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->sse->goal->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Accès de l\'utilisateur à son espace d\'émargement et de suivi de ses causeries.'; // Permission label
		$this->rights[$r][4] = 'causerie';
		$this->rights[$r][5] = 'write_emargement'; // In php code, permission will be checked by test if ($user->rights->sse->causerie->write_emargement)
		$r++;
		
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 10000;
		
		/* BEGIN MODULEBUILDER TOPMENU */
		// $this->menu[$r++] = array(
		// 	'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'top', // This is a Top menu entry
		// 	'titre'=>'ModuleSSEName',
		// 	'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
		// 	'mainmenu'=>'sse',
		// 	'leftmenu'=>'',
		// 	'url'=>'/sse/sseindex.php',
		// 	'langs'=>'sse@sse', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000 + $r,
		// 	'enabled'=>'$conf->sse->enabled', // Define condition to show or hide menu entry. Use '$conf->sse->enabled' if entry must be visible if module is enabled.
		// 	'perms'=>'1', // Use 'perms'=>'$user->rights->sse->causerie->read' if you want your menu with a permission rules
		// 	'target'=>'',
		// 	'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		// );
		global $user;
		
		/* END MODULEBUILDER TOPMENU */
		//BEGIN MODULEBUILDER LEFTMENU CAUSERIE
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=q3serp',      
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Causeries',
			'prefix' => img_picto('', $this->picto, 'class="fas fa-comments infobox-propal paddingright pictofixedwidth"'),
			'mainmenu'=>'causerie',
			'leftmenu'=>'causeries',
			'url'=>'/sse/sseindex.php',
			'langs'=>'sse@sse',	        
			'position'=>1+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->admin_causerie',			                
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie',      
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Causeries',
			'prefix' => img_picto('', $this->picto, 'class="fas fa-comments infobox-propal paddingright pictofixedwidth"'),
			'mainmenu'=>'causerie',
			'leftmenu'=>'causeries',
			'url'=>'/sse/sseindex.php',
			'langs'=>'sse@sse',	        
			'position'=>1+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->admin_causerie',			                
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=causeries',	    
			'type'=>'left',			                
			'titre'=>'Nouvelle causerie',
			'mainmenu'=>'causerie',
			'leftmenu'=>'sse_causerie_new',
			'url'=>'/sse/causerie_card.php?action=create',
			'langs'=>'sse@sse',	       
			'position'=>1+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->write_causerie',			                
			'target'=>'',
			'user'=>2,				                
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=sse_causerie_new',	    
			'titre'=>'Liste des causeries',
			'mainmenu'=>'causerie',
			'leftmenu'=>'sse_causerie_list',
			'url'=>'/sse/causerie_all_list.php',
			'langs'=>'sse@sse',	        
			'position'=>1+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->read_all',			                
			'target'=>'',
			'user'=>2,				                
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=sse_causerie_new',	    
			'titre'=>'Vos causeries',
			'mainmenu'=>'causerie',
			'leftmenu'=>'sse_causerie_list',
			'url'=>'/sse/causerie_restrict_list.php',
			'langs'=>'sse@sse',	        
			'position'=>1+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->read',			                
			'target'=>'',
			'user'=>2,				                
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie',      
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Causeries Participant',
			'prefix' => img_picto('', $this->picto, 'class="fas fa-user-circle infobox-propal paddingright pictofixedwidth"'),
			'mainmenu'=>'causerie',
			'leftmenu'=>'emargement',
			// 'url'=>'/sse/sseindex.php',
			'langs'=>'sse@sse',	        
			'position'=>1+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->admin_causerie',			                
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=emargement',	    
			'type'=>'left',			                
			'titre'=>'Espace Émargement',
			'mainmenu'=>'causerie',
			'leftmenu'=>'sse_emargement_new',
			'url'=>'/sse/causerie_emargement_espace.php',
			'langs'=>'sse@sse',	        
			'position'=>12001+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->write_emargement',			               
			'target'=>'',
			'user'=>2,				                
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=sse_emargement_new',	    
			'type'=>'left',			                
			'titre'=>'Liste d\'émargements',
			'mainmenu'=>'causerie',
			'leftmenu'=>'sse_emargement_new',
			'url'=>'/sse/causerieattendance_list.php',
			'langs'=>'sse@sse',	        
			'position'=>12001+$r,
			'enabled'=>'$conf->sse->enabled', 
			'perms'=>'$user->rights->sse->causerie->write_emargement',			              
			'target'=>'',
			'user'=>2,				                
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=causerie',      
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Causeries Objectif',
			'prefix' => img_picto('', $this->picto, 'class="fas fa-trophy infobox-propal paddingright pictofixedwidth"'),
			'mainmenu'=>'causerie',
			'leftmenu'=>'objectifs',
			// 'url'=>'/sse/sseindex.php',
			'langs'=>'sse@sse',	        
			'position'=>1+$r,
			'enabled'=>'$conf->sse->enabled',  
			'perms'=>'$user->rights->sse->causerie->admin_causerie',			                
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=objectifs',
            'type'=>'left',
            'titre'=>'Planifier un objectif',
            'mainmenu'=>'causerie',
            'leftmenu'=>'sse_goals',
            'url'=>'/sse/goal_card.php?action=create',
            'langs'=>'sse@sse',
            'position'=>12001+$r,
            'enabled'=>'$conf->sse->enabled',
            'perms'=>'$user->rights->sse->goal->write',
            'target'=>'',
            'user'=>2
        );
		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=sse_goals',
            'type'=>'left',
            'titre'=>'Liste des objectifs',
            'mainmenu'=>'causerie',
            'leftmenu'=>'sse_causerie',
            'url'=>'/sse/goal_list.php',
            'langs'=>'sse@sse',
            'position'=>12001+$r,
            'enabled'=>'$conf->sse->enabled',
            'perms'=>'$user->rights->sse->goal->write',
            'target'=>'',
            'user'=>2
        );
		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=objectifs',
            'type'=>'left',
            'titre' => '<strong style="font-weight: bold; font-size: 1.1em; color: #0073AA;">Progression atteinte</strong>',
            'mainmenu'=>'causerie',
            'leftmenu'=>'sse_causerie',
            'url'=>'/sse/objectif_suivi_presences.php',
            'langs'=>'sse@sse',
            'position'=>12001+$r,
            'enabled'=>'$conf->sse->enabled',
            'perms'=>'$user->rights->sse->goal->follow',
            'target'=>'',
            'user'=>2
        );

		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=causerie',
            'type'=>'left',
            'titre'=>'Administration',
            'mainmenu'=>'causerie',
            'leftmenu'=>'administration',
            'langs'=>'sse@sse',
            'position'=>12001+$r,
            'enabled'=>'$conf->sse->enabled',
            'perms'=>'$user->rights->sse->causerie->admin_causerie',
            'target'=>'',
            'user'=>2,
        );
		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=administration',
            'type'=>'left',
            'titre'=>'Créer un thème',
            'mainmenu'=>'causerie',
            'leftmenu'=>'sse_theme_new',
            'url'=>'/sse/theme_card.php?action=create',
            'langs'=>'sse@sse',
            'position'=>12001+$r,
            'enabled'=>'$conf->sse->enabled',
            'perms'=>'$user->rights->sse->causerie->admin_causerie',
            'target'=>'',
            'user'=>2
        );
		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=causerie,fk_leftmenu=sse_theme_new',
            'type'=>'left',
            'titre'=>'Liste des thèmes',
            'mainmenu'=>'causerie',
            'leftmenu'=>'sse_theme_new',
            'url'=>'/sse/theme_list.php',
            'langs'=>'sse@sse',
            'position'=>12001+$r,
            'enabled'=>'$conf->sse->enabled',
            'perms'=>'$user->rights->sse->causerie->admin_causerie',
            'target'=>'',
            'user'=>2
        );
		
		$r = 1;
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

		//$result = $this->_load_tables('/install/mysql/tables/', 'sse');
		$result = $this->_load_tables('/sse/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('sse_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'sse@sse', '$conf->sse->enabled');
		//$result2=$extrafields->addExtraField('sse_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'sse@sse', '$conf->sse->enabled');
		//$result3=$extrafields->addExtraField('sse_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'sse@sse', '$conf->sse->enabled');
		//$result4=$extrafields->addExtraField('sse_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'sse@sse', '$conf->sse->enabled');
		//$result5=$extrafields->addExtraField('sse_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'sse@sse', '$conf->sse->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('sse');
		$myTmpObjects = array();
		$myTmpObjects['Causerie'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'Causerie') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_causeries.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_causeries.odt';

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
