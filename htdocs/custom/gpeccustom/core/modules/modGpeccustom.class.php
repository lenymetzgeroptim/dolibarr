<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
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
 * 	\defgroup   gpeccustom     Module Gpeccustom
 *  \brief      Gpeccustom module descriptor.
 *
 *  \file       htdocs/gpeccustom/core/modules/modGpeccustom.class.php
 *  \ingroup    gpeccustom
 *  \brief      Description and activation file for module Gpeccustom
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Gpeccustom
 */
class modGpeccustom extends DolibarrModules
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
		$this->numero = 500040; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'gpeccustom';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleGpeccustomName' not found (Gpeccustom is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleGpeccustomDesc' not found (Gpeccustom is name of module).
		$this->description = "Migration du module GRH pour la partie GPEC et customisation + liens vers d'autres modules (formation/habilitation, tests annuels";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Customisation du module GRH-Gpec pour développer la génération des CV, le motuer de recherche pour les CV, les indicateurs GPEC et sa gestion, ...";

		// Author
		$this->editor_name = 'Soufiane FADEL';
		$this->editor_url = 'optim-industries.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where GPECCUSTOM is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fa-user-tag';
		// <i class="fas fa-user-tag"></i>
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
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/gpeccustom/css/gpeccustom.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				  '/gpeccustom/js/gpeccustom.js.php',
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
		// Example: this->dirs = array("/gpeccustom/temp","/gpeccustom/subdir");
		$this->dirs = array("/gpeccustom/temp");

		// Config pages. Put here list of php page, stored into gpeccustom/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@gpeccustom");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = array();
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = array();
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("gpeccustom@gpeccustom");

		// Prerequisites
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'GpeccustomWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('GPECCUSTOM_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('GPECCUSTOM_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->gpeccustom) || !isset($conf->gpeccustom->enabled)) {
			$conf->gpeccustom = new stdClass();
			$conf->gpeccustom->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		$this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@gpeccustom:$user->rights->gpeccustom->read:/gpeccustom/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		//  $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@gpeccustom:$user->rights->gpeccustom->read:/gpeccustom/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@gpeccustom:$user->rights->othermodule->read:/gpeccustom/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		// 'order'            to add a tab in sale order view
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
			'langs'=>'gpeccustom@gpeccustom',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array("table1", "table2", "table3"),
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
			'tabcond'=>array(isModEnabled('gpeccustom'), isModEnabled('gpeccustom'), isModEnabled('gpeccustom')),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in gpeccustom/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'gpeccustomwidget1.php@gpeccustom',
			//      'note' => 'Widget provided by Gpeccustom',
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
			//      'class' => '/gpeccustom/class/myobject.class.php',
			//      'objectname' => 'MyObject',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("gpeccustom")',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'isModEnabled("gpeccustom")', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'isModEnabled("gpeccustom")', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire compétence/emploi/poste'; // Permission label
		$this->rights[$r][4] = 'gpeccustom';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer/modifier une compétence/un emploi/un poste'; // Permission label
		$this->rights[$r][4] = 'gpeccustom';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer compétence/emploi/poste'; // Permission label
		$this->rights[$r][4] = 'gpeccustom';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire les évaluations (les vôtres et celle de vos subordonnés)'; // Permission label
		$this->rights[$r][4] = 'evaluation';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer/modifier des évaluations'; // Permission label
		$this->rights[$r][4] = 'evaluation';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Valider l\'évaluation'; // Permission label
		$this->rights[$r][4] = 'evaluation_advance';
		$this->rights[$r][5] = 'validate'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer l\'évaluation'; // Permission label
		$this->rights[$r][4] = 'evaluation';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Voir menu de comparaison'; // Permission label
		$this->rights[$r][4] = 'compare_advance';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire toutes les évaluations'; // Permission label
		$this->rights[$r][4] = 'evaluation';
		$this->rights[$r][5] = 'readall'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;


		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Représentation graphique des indicateurs'; // Permission label
		$this->rights[$r][4] = 'indicators';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire liste des CV'; // Permission label
		$this->rights[$r][4] = 'cvtec';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire les informations personnelles'; // Permission label
		$this->rights[$r][4] = 'read_personal_information';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Ecrire les informations personnelles'; // Permission label
		$this->rights[$r][4] = 'write_personal_information';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		$r++;
		

		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Ajouter des domaines d\'activité'; // Permission label
		// $this->rights[$r][4] = 'domain';
		// $this->rights[$r][5] = 'create'; // In php code, permission will be checked by test if ($user->rights->gpeccustom->read)
		// $r++;
		/* END MODULEBUILDER PERMISSIONS */
	
		// Main menu entries to add
		$this->menu = array();

		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'GPEC',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpeccustom',
			'leftmenu'=>'',
			'url'=>'/gpeccustom/gpeccustomindex.php',
			'langs'=>'gpeccustom@gpeccustom', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'isModEnabled("gpeccustom")', // Define condition to show or hide menu entry. Use 'isModEnabled("gpeccustom")' if entry must be visible if module is enabled.
			//'perms'=>'1', // Use 'perms'=>'$user->hasRight("gpeccustom", "gpeccustom", "read")' if you want your menu with a permission rules
			'perms'=>'$user->hasRight("gpeccustom", "gpeccustom", "read")',
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu' =>'fk_mainmenu=gpeccustom',
			'type' =>'left',
			'titre' =>'Gestion des compétences',
			'prefix' => img_picto('', 'fa-shapes', 'class="infobox-adherent paddingright pictofixedwidth"'),
			'mainmenu' =>'gpeccustom',
			'leftmenu' =>'hrm_sm_list',
			'url' =>'/custom/gpeccustom/gpeccustomindex.php',
			'langs' =>'gpeccustom@gpeccustom',
			'position' =>1000 + $r,
			'enabled'=>'isModEnabled("gpeccustom")',
			// 'perms' =>'$user->hasRight("gpeccustom","indicators","read")',
			'perms' =>'$user->hasRight("gpeccustom","gpeccustom","read")',
			'target' =>'',
			'user' =>2,
	   );
	   /* END LEFTMENU GESTION DES COMPéTENCES */
	   /* LEFTMENU COMPéTENCE */
	   $this->menu[$r++]=array(
			'fk_menu' =>'fk_mainmenu=gpeccustom,fk_leftmenu=hrm_sm_list',
			'type' =>'left',
			'titre' =>'Compétence',
			'mainmenu' =>'gpeccustom',
			'leftmenu' =>'hrm_skill_list',
			'url' =>'/custom/gpeccustom/skill_list.php',
			'langs' =>'gpeccustom@gpeccustom',
			'position' =>1000 + $r,
			'enabled' =>'isModEnabled("gpeccustom")',
			'perms' =>'$user->rights->gpeccustom->gpeccustom->read',
			'target' =>'',
			'user' =>2,
	   );

	   $this->menu[$r++]=array(
			'fk_menu' =>'fk_mainmenu=gpeccustom,fk_leftmenu=hrm_sm_list',
			'type' =>'left',
			'titre' =>'Emploi',
			'mainmenu' =>'gpeccustom',
			'leftmenu' =>'hrm_job_list',
			'url' =>'/custom/gpeccustom/job_list.php',
			'langs' =>'gpeccustom@gpeccustom',
			'position' =>1000 + $r,
			'enabled' =>'isModEnabled("gpeccustom")',
			'perms' =>'$user->rights->gpeccustom->gpeccustom->read',
			'target' =>'',
			'user' =>2,
	   );

		$this->menu[$r++]=array(
				'fk_menu' =>'fk_mainmenu=gpeccustom,fk_leftmenu=hrm_sm_list',
				'type' =>'left',
				'titre' =>'Emploi exercé',
				'mainmenu' =>'gpeccustom',
				'leftmenu' =>'hrm_job_list',
				'url' =>'/gpeccustom/position_list.php',
				'langs' =>'gpeccustom@gpeccustom',
				'position' =>1000 + $r,
				'enabled' =>'isModEnabled("gpeccustom")',
				'perms' =>'$user->rights->gpeccustom->gpeccustom->read',
				'target' =>'',
				'user' =>2,
		);
	
		$this->menu[$r++]=array(
				'fk_menu' =>'fk_mainmenu=gpeccustom,fk_leftmenu=hrm_sm_list',
				'type' =>'left',
				'titre' =>'Évaluations',
				'mainmenu' =>'gpeccustom',
				'leftmenu' =>'hrm_evaluation_list',
				'url' =>'/custom/gpeccustom/evaluation_list.php',
				'langs' =>'gpeccustom@gpeccustom',
				'position' =>1000 + $r,
				'enabled' =>'isModEnabled("gpeccustom")',
				'perms' =>'$user->rights->gpeccustom->evaluation->read',
				'target' =>'',
				'user' =>2,
		);

		$this->menu[$r++]=array(
			'fk_menu' =>'fk_mainmenu=gpeccustom,fk_leftmenu=hrm_sm_list',
			'type' =>'left',
			'titre' =>'Comparaison des compétence',
			'mainmenu' =>'gpeccustom',
			'leftmenu' =>'hrm_comp_list',
			'url' =>'/custom/gpeccustom/compare.php',
			'langs' =>'gpeccustom@gpeccustom',
			'position' =>1000 + $r,
			'enabled' =>'isModEnabled("gpeccustom")',
			'perms' =>'$user->rights->gpeccustom->compare_advance->read',
			'target' =>'',
			'user' =>2,
		);
	   $this->menu[$r++]=array(
		   'fk_menu' =>'fk_mainmenu=gpeccustom,fk_leftmenu=hrm_sm_list',
		   'type' =>'left',
		   'titre' =>'CVTec',
		   'mainmenu' =>'gpeccustom',
		   'leftmenu' =>'hrm_cvtec_list',
		   'url' =>'/gpeccustom/cvtec_list.php',
		   'langs' =>'gpeccustom@gpeccustom',
		   'position' =>1000 + $r,
		   'enabled' =>'isModEnabled("gpeccustom")',
		   'perms' =>'$user->rights->gpeccustom->cvtec->read',
		   'target' =>'',
		   'user' =>2,
	 	 );

		//quiz 
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpeccustom',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Gestion des tests',
			'prefix' => img_picto('', 'fa-bars', 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpeccustom',
			'leftmenu'=>'bord',
			'url'=>'/quiz/quizindex.php',
			//'url'=>'/quiz/session_user_list.php',
			'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("quiz", "gestion", "setup")',
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		
		
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',                          // This is a Left menu entry
		// 	'titre'=>' &nbsp;Gestion campagnes',
		// 	'prefix' => img_picto('', 'fa-arrow-circle-right', 'class="paddingleft 30% valignmiddle"'),
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'campaign',
		// 	//'url'=>'/quiz/quizindex.php',
		// 	// 'url'=>'/quiz/campaign_list2.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "campaign")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=campaign',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',			                // This is a Left menu entry
		// 	'titre'=>'Nouvelle campagne',
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'quiz_campaign_card',
		// 	'url'=>'/quiz/campaign_card.php?action=create',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "campaign")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=campaign',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',			                // This is a Left menu entry
		// 	'titre'=>'Liste des campagnes',
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'quiz_campaign_list',
		// 	'url'=>'/quiz/campaign_list.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "campaign")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',                          // This is a Left menu entry
		// 	'titre'=>' &nbsp;Gestion session',
		// 	'prefix' => img_picto('', 'fa-arrow-circle-right', 'class="paddingleft 30% valignmiddle"'),
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'session',
		// 	//'url'=>'/quiz/quizindex.php',
		// 	'url'=>'/quiz/session_list.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "session")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );

		// // Get managers ids and give rights for managers
		// require_once DOL_DOCUMENT_ROOT.'/custom/quiz/class/session.class.php';
		// global $db, $user;
		// $session = new Session($this->db);
		// $managers = $session->getManager();
		//  foreach($managers as $key => $manager) {
		// 	$managerIds[] = $key;
		//  }
		// if(in_array($user->id, $managerIds)) {
		// 	$this->menu[$r++]=array(
		// 		'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=session',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 		'type'=>'left',			                // This is a Left menu entry
		// 		'titre'=>'Sessions de votre antenne',
		// 		'mainmenu'=>'quiz',
		// 		'leftmenu'=>'quiz_session_list',
		// 		'url'=>'/quiz/session_responsible_list.php',
		// 		'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 		'position'=>1000+$r,
		// 		'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 		'perms'=>'$user->hasRight("quiz", "espace", "user")',
		// 		'target'=>'',
		// 		'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// 	);
		// }
		
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=session',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',			                // This is a Left menu entry
		// 	'titre'=>'Liste des sessions',
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'quiz_session_list',
		// 	'url'=>'/quiz/session_list.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "session")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',                          // This is a Left menu entry
		// 	'titre'=>'&nbsp;Gestion questions',
		// 	'prefix' => img_picto('', 'fa-arrow-circle-right', 'class="paddingleft 30% valignmiddle"'),
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'question',
		// 	//'url'=>'/quiz/quizindex.php',
		// 	'url'=>'/quiz/question_list.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "question")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=question',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',			                // This is a Left menu entry
		// 	'titre'=>'Nouveau questionnaire',
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'quiz_question_new',
		// 	'url'=>'/quiz/question_card.php?action=create',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "question")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=question',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',			                // This is a Left menu entry
		// 	'titre'=>'Liste des questionnaires',
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'quiz_question_list',
		// 	'url'=>'/quiz/question_list.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "question")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=bord',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',			                // This is a Left menu entry
		// 	'titre'=>'Suivi et statistiques',
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'setup',
		// 	'url'=>'/quiz/quizindex.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "setup")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
		
		// $this->menu[$r++]=array(
		// 	'fk_menu'=>'fk_mainmenu=quiz,fk_leftmenu=bord',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'left',			                // This is a Left menu entry
		// 	'titre'=>'Paramètrage',
		// 	'mainmenu'=>'quiz',
		// 	'leftmenu'=>'quiz_setup_new',
		// 	//'url'=>'/formationhabilitation/theme_card.php?action=create',
		// 	'url'=>'/quiz/quiz_gestion_import.php',
		// 	'langs'=>'quiz@quiz',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000+$r,
		// 	'enabled'=>'isModEnabled("quiz")', // Define condition to show or hide menu entry. Use 'isModEnabled("quiz")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		// 	'perms'=>'$user->hasRight("quiz", "gestion", "setup")',
		// 	'target'=>'',
		// 	'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		// );
	
		/* END LEFTMENU COMPARAISON DES COMPéTENCE */


		/* END MODULEBUILDER LEFTMENU MYOBJECT */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		
		$langs->load("CV");
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='Toutes les donnés CV';	// Translation key (used only if key ExportDataset_xxx_z not found)
		// $this->export_icon[$r]=$this->picto;
		// Defiine $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		// $keyforclass = 'CVTec'; $keyforclassfile='/gpeccustom/class/cvtec.class.php'; $keyforelement='cvtec@gpeccustom';
		// include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		// //$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'MyObjectLine'; $keyforclassfile='/gpeccustom/class/myobject.class.php'; $keyforelement='myobjectline@gpeccustom'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		// $keyforselect='cvtec'; $keyforaliasextra='extra'; $keyforelement='cvtec@gpeccustom';
		// include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='myobjectline'; $keyforaliasextra='extraline'; $keyforelement='myobjectline@gpeccustom';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('myobjectline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		$this->export_special_array[$r] = array('t.fk_user'=>'test');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		// $this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		//case 1 users with jobs => name, job user, date of job posting
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Collaborateurs et affectations';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]=$this->picto;
		$this->export_fields_array[$r] = array(
			't.rowid' => 'ID. CV', 't.ref' => 'Rèf. CV', 'u.firstname' => 'Prénom utilisateur', 'u.lastname' => 'Nom utilisateur', 
			'j.label' => 'Emplois exercés', 'js.date_creation' => 'Date d\'affectation', 
		);
		$this->export_TypeFields_array[$r] = array(
			't.rowid' => 'Numeric', 't.ref' => 'Text', 'u.firstname' => 'Text', 'u.lastname' => 'Text', 
			'j.label' => 'Text', 'js.date_creation' => 'Date', 
		);
		
		$this->export_entities_array[$r] = array(
			't.rowid' => 'gpec_16@gpec', 't.ref' => 'gpec_16@gpec', 'u.firstname' => 'user', 'u.lastname' => 'user', 
			'j.label' => 'hrm', 'js.date_creation' => 'hrm',
		);
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'gpeccustom_cvtec as t';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_job_user as js ON js.fk_user = t.fk_user';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = js.fk_user';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_job as j ON j.rowid = js.fk_job';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.fk_job > 0';
		$r++; 
		//case 2 users with evalaution jobs => name, job, skils rankorder, ...
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Collaborateurs et évaluations';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]=$this->picto;
		$this->export_fields_array[$r] = array(
			't.rowid' => 'ID. CV', 't.ref' => 'Rèf. CV', 'u.firstname' => 'Prénom utilisateur', 'u.lastname' => 'Nom utilisateur', 
			'j.label' => 'Emplois exercés', 'sd.date_eval' => 'Date d\'évaluation', 's.label' => 'Compétences acquises', 'sdt.rankorder' => 'Niveau obtenu', 'sdt.required_rank' => 'Niveau requis', 
		);
		$this->export_TypeFields_array[$r] = array(
			't.rowid' => 'Numeric', 't.ref' => 'Text', 'u.firstname' => 'Text', 'u.lastname' => 'Text', 
			'j.label' => 'Text', 'sd.date_eval' => 'Date', 's.label' => 'Text', 'sdt.rankorder' => 'Numeric', 'sdt.required_rank' => 'Numeric',
		);
		$this->export_entities_array[$r] = array(
			't.rowid' => 'gpec_16@gpec', 't.ref' => 'gpec_16@gpec', 'u.firstname' => 'user', 'u.lastname' => 'user', 
			'j.label' => 'hrm', 'sd.date_eval' => 'hrm', 's.label' => 'hrm',  'sdt.rankorder' => 'hrm', 'sdt.required_rank' => 'hrm',
		);

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'hrm_evaluationdet as sdt';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_evaluation as sd ON sd.rowid = sdt.fk_evaluation';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'gpeccustom_cvtec as t  ON sd.fk_user = t.fk_user';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_skill as s ON s.rowid = sdt.fk_skill';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = sd.fk_user';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_job as j ON j.rowid = sd.fk_job';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.fk_job > 0';
		$this->export_sql_end[$r] .=' ORDER BY t.rowid';
		$r++; 

		//case 3 users with evalaution < seuil jobs => name, job, skils rankorder, ...
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Collaborateurs < seuil requis dans l\'emploi';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]=$this->picto;
		$this->export_fields_array[$r] = array(
			't.rowid' => 'ID. CV', 't.ref' => 'Rèf. CV', 'u.firstname' => 'Prénom utilisateur', 'u.lastname' => 'Nom utilisateur', 
			'j.label' => 'Emplois exercés', 'sd.date_eval' => 'Date d\'évaluation', 's.label' => 'Compétences acquises', 'sdt.rankorder' => 'Niveau obtenu', 'sdt.required_rank' => 'Niveau requis', 
		);
		$this->export_TypeFields_array[$r] = array(
			't.rowid' => 'Numeric', 't.ref' => 'Text', 'u.firstname' => 'Text', 'u.lastname' => 'Text', 
			'j.label' => 'Text', 'sd.date_eval' => 'Date', 's.label' => 'Text', 'sdt.rankorder' => 'Numeric', 'sdt.required_rank' => 'Numeric',
		);
		$this->export_entities_array[$r] = array(
			't.rowid' => 'gpec_16@gpec', 't.ref' => 'gpec_16@gpec', 'u.firstname' => 'user', 'u.lastname' => 'user', 
			'j.label' => 'hrm', 'sd.date_eval' => 'hrm', 's.label' => 'hrm',  'sdt.rankorder' => 'hrm', 'sdt.required_rank' => 'hrm',
		);

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'hrm_evaluationdet as sdt';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_evaluation as sd ON sd.rowid = sdt.fk_evaluation';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'gpeccustom_cvtec as t  ON sd.fk_user = t.fk_user';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_skill as s ON s.rowid = sdt.fk_skill';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = sd.fk_user';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_job as j ON j.rowid = sd.fk_job';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.fk_job > 0';
		$this->export_sql_end[$r] .=' AND sdt.rankorder < sdt.required_rank';
		// $this->export_sql_end[$r] .=' GROUP BY sd.date_eval';
		$this->export_sql_end[$r] .=' ORDER BY t.rowid, sd.date_eval';
		$r++; 
		
		//case 3 users with no posting neither evaluation users => name
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Les salariés sans affectation';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]=$this->picto;
		$this->export_fields_array[$r] = array(
			't.rowid' => 'Id', 't.ref' => 'Réf. CV', 'u.firstname' => 'Prénom utilisateur', 'u.lastname' => 'Nom utilisateur', 
		);
		$this->export_TypeFields_array[$r] = array(
			't.rowid' => 'Numeric', 't.ref' => 'Text', 'u.firstname' => 'Text', 'u.lastname' => 'Text', 
		);
		$this->export_entities_array[$r] = array(
			't.rowid' => 'gpec_16@gpec', 't.ref' => 'gpec_16@gpec', 'u.firstname' => 'user', 'u.lastname' => 'user', 
		);

		
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'gpeccustom_cvtec as t';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = t.fk_user';
		// $this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_job_user as js ON js.fk_user = t.fk_user';
		// $this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_job as j ON j.rowid = js.fk_job';
		// $this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_evaluation as sd ON sd.fk_user = t.fk_user';
		// $this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_evaluationdet as sdt ON sdt.fk_evaluation = sd.rowid';
		// $this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_skill as s ON s.rowid = sdt.fk_skill';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.fk_job = 0 OR t.fk_job is nulls';
		$this->export_sql_end[$r] .=' ORDER BY t.rowid';
		$r++; 
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		// $r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		$langs->load("gpeccustom@gpeccustom");
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='myobject@gpeccustom';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'gpeccustom_myobject', 'extra' => MAIN_DB_PREFIX.'gpeccustom_myobject_extrafields');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'MyObject'; $keyforclassfile='/gpeccustom/class/myobject.class.php'; $keyforelement='myobject@gpeccustom';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@gpeccustom';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'gpeccustom_myobject');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.ref' => array(
				'rule'=>'getrefifauto',
				'class'=>(!getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON') ? 'mod_myobject_standard' : getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON')),
				'path'=>"/core/modules/commande/".(!getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON') ? 'mod_myobject_standard' : getDolGlobalString('GPECCUSTOM_MYOBJECT_ADDON')).'.php'
				'classobject'=>'MyObject',
				'pathobject'=>'/gpeccustom/class/myobject.class.php',
			),
			't.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
			't.fk_mode_reglement' => array('rule' => 'fetchidfromcodeorlabel', 'file' => '/compta/paiement/class/cpaiement.class.php', 'class' => 'Cpaiement', 'method' => 'fetch', 'element' => 'cpayment'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++; */
		/* END MODULEBUILDER IMPORT MYOBJECT */
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

		//$result = $this->_load_tables('/install/mysql/', 'gpeccustom');
		$result = $this->_load_tables('/gpeccustom/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('gpeccustom_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'gpeccustom@gpeccustom', 'isModEnabled("gpeccustom")');
		//$result2=$extrafields->addExtraField('gpeccustom_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'gpeccustom@gpeccustom', 'isModEnabled("gpeccustom")');
		//$result3=$extrafields->addExtraField('gpeccustom_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'gpeccustom@gpeccustom', 'isModEnabled("gpeccustom")');
		//$result4=$extrafields->addExtraField('gpeccustom_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'gpeccustom@gpeccustom', 'isModEnabled("gpeccustom")');
		//$result5=$extrafields->addExtraField('gpeccustom_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'gpeccustom@gpeccustom', 'isModEnabled("gpeccustom")');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('gpeccustom');
		$myTmpObjects = array();
		$myTmpObjects['MyObject'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'MyObject') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_myobjects.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_myobjects.odt';

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
