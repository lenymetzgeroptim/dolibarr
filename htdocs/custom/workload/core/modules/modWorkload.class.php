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
 * 	\defgroup   workload     Module Workload
 *  \brief      Workload module descriptor.
 *
 *  \file       htdocs/workload/core/modules/modWorkload.class.php
 *  \ingroup    workload
 *  \brief      Description and activation file for module Workload
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Workload
 */
class modWorkload extends DolibarrModules
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
		$this->numero = 500036; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'workload';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleWorkloadName' not found (Workload is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleWorkloadDesc' not found (Workload is name of module).
		$this->description = "Permet de suivre le plan de charge";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Permet de suivre le plan de charge et gérer les ressources";

		// Author
		$this->editor_name = 'Soufiane FADEL';
		$this->editor_url = 'optim-industries.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where WORKLOAD is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fa-stream';
	
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
				    '/workload/css/workload.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/workload/js/workload.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				  'data' => array(
				      'admin',
				  ),
				//   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/workload/temp","/workload/subdir");
		$this->dirs = array("/workload/temp");

		// Config pages. Put here list of php page, stored into workload/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@workload");

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
		$this->langfiles = array("workload@workload");

		// Prerequisites
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'WorkloadWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('WORKLOAD_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('WORKLOAD_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->workload) || !isset($conf->workload->enabled)) {
			$conf->workload = new stdClass();
			$conf->workload->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@workload:$user->rights->workload->read:/workload/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@workload:$user->rights->othermodule->read:/workload/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
			'langs'=>'workload@workload',
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
			'tabcond'=>array(isModEnabled('workload'), isModEnabled('workload'), isModEnabled('workload')),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in workload/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'box_graph_plan_charge.php@workload',
			//      'note' => 'Widget provided by AddOnComm',
			//      'enabledbydefaulton' => 'Home',
			//  )
			//  ...
			// 0=>array('file'=>'box_graph_plan_charge.php@workload', 'enabledbydefaulton'=>'Home')
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/workload/class/charge.class.php',
			//      'objectname' => 'Charge',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("workload")',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'isModEnabled("workload")', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'isModEnabled("workload")', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 0 + 1);
		$this->rights[$r][1] = 'Visualisation du plan de charge et du graphique ETP.';
		$this->rights[$r][4] = 'box_graph_plan_charge';
		$this->rights[$r][5] = 'afficher';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 1 + 1);
		$this->rights[$r][1] = 'Accès complet au plan de charge avec limitation à seulement les projets dont l\'utilisateur est responsable (option désactivable en décochant cette case).';
		$this->rights[$r][4] = 'box_graph_plan_charge';
		$this->rights[$r][5] = 'restreint';
		$r++;
		// $this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 1 + 1);
		// $this->rights[$r][1] = 'Voir le plan de charge';
		// $this->rights[$r][4] = 'charge';
		// $this->rights[$r][5] = 'voir';
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 2 + 1);
		// $this->rights[$r][1] = 'Voir le graphique';
		// $this->rights[$r][4] = 'graph';
		// $this->rights[$r][5] = 'voir';
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 0 + 1);
		// $this->rights[$r][1] = 'Read Charge object of Workload';
		// $this->rights[$r][4] = 'charge';
		// $this->rights[$r][5] = 'read';
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 1 + 1);
		// $this->rights[$r][1] = 'Create/Update Charge object of Workload';
		// $this->rights[$r][4] = 'charge';
		// $this->rights[$r][5] = 'write';
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 2 + 1);
		// $this->rights[$r][1] = 'Delete Charge object of Workload';
		// $this->rights[$r][4] = 'charge';
		// $this->rights[$r][5] = 'delete';
		// $r++;
		
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		// $this->menu[$r++] = array(
		// 	'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'top', // This is a Top menu entry
		// 	'titre'=>'Plan de charge',
		// 	'prefix' => img_picto('', $this->picto, 'class="fas fa-stream paddingright pictofixedwidth valignmiddle"'),
		// 	'mainmenu'=>'workload',
		// 	'leftmenu'=>'',
		// 	'url'=>'workload/ganttview.php',
		// 	'langs'=>'workload@workload', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000 + $r,
		// 	'enabled'=>'isModEnabled("workload")', // Define condition to show or hide menu entry. Use 'isModEnabled("workload")' if entry must be visible if module is enabled.
		// 	'perms'=>'1', // Use 'perms'=>'$user->hasRight("workload", "charge", "read")' if you want your menu with a permission rules
		// 	'target'=>'',
		// 	'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		// );
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=hrm',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Plan de charge',
			'prefix' => img_picto('', $this->picto, 'class="fas fa-stream  paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'workload',
			'leftmenu'=>'charge',
			'url'=>'/workload/workloadindex.php',
			'langs'=>'workload@workload',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>10+$r,
			'enabled'=>'isModEnabled("workload")', // Define condition to show or hide menu entry. Use 'isModEnabled("workload")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("workload", "box_graph_plan_charge", "afficher")',
			// 'perms'=>'1',
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=workload',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'Cumul ETP',
			'prefix' => img_picto('', 'fa-chart-area', 'class="fas fa-chart-area  paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'workload',
			'leftmenu'=>'graph',
			'url'=>'/workload/workloadindex.php',
			'langs'=>'workload@workload',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("workload")', // Define condition to show or hide menu entry. Use 'isModEnabled("workload")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("workload", "box_graph_plan_charge", "afficher")',
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
	
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=workload',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'Gantt Plan Charge',
			'prefix' => img_picto('', $this->picto, 'class="fas fa-stream  paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'workload',
			'leftmenu'=>'charge',
			'url'=>'/workload/plancharge_modes_view.php',
			'langs'=>'workload@workload',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("workload")', // Define condition to show or hide menu entry. Use 'isModEnabled("workload")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("workload", "box_graph_plan_charge", "afficher")',
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		/*END LEFTMENU CHARGE*/
		/* END MODULEBUILDER LEFTMENU MYOBJECT */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("workload@workload");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ChargeLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='charge@workload';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'Charge'; $keyforclassfile='/workload/class/charge.class.php'; $keyforelement='charge@workload';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'ChargeLine'; $keyforclassfile='/workload/class/charge.class.php'; $keyforelement='chargeline@workload'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='charge'; $keyforaliasextra='extra'; $keyforelement='charge@workload';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='chargeline'; $keyforaliasextra='extraline'; $keyforelement='chargeline@workload';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('chargeline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'charge as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'charge_line as tl ON tl.fk_charge = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('charge').')';
		$r++; */

		$langs->load("Projets");
		//Marchés : Devis, Commandes et Factures
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Marchés : Devis, Commandes et Factures';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]= 'contract';
		$this->export_fields_array[$r] = array(
			't.fk_source' => 'ID. Contrat', 
			'ct.ref' => 'Rèf. Contrat', 
			'p.ref' => 'Rèf. Devis', 'p.total_ht' => 'Dev. Total HT', 'p.total_ttc' => 'Dev. Total TTC', 'pe.datedmarrage' => ' Dev. Date de démarage', 'p.date_livraison' => ' Dev. Date de livraison',
			'c.ref' => 'Rèf. Commande', 'c.total_ht' => 'Code. Total HT', 'c.total_ttc' => 'Code. Total TTC', 'ce.date_start' => ' Code. Date de démarage', 'c.date_livraison' => ' Code. Date de livraison',
			'f.ref' => 'Rèf. Facture',  'f.total_ht' => 'Fac. Total HT', 'f.total_ttc' => 'Fac. Total TTC', 'f.datef' => 'Date de facturation',
		);
		$this->export_TypeFields_array[$r] = array(
			't.fk_source' => 'Numeric', 
			'p.ref' => 'Text', 'p.total_ht' => 'Numeric','p.total_ttc' => 'Numeric', 'pe.datedmarrage' => 'Date', 'p.date_livraison' => 'Date',
			'c.ref' => 'Text', 'c.total_ht' => 'Numeric',  'c.total_ttc' => 'Numeric', 'ce.date_start' => 'Date', 'c.date_livraison' => 'Date',
			'f.ref' => 'Text', 'f.total_ht' => 'Numeric', 'f.total_ttc' => 'Numeric', 'f.datef' => 'Date',
		);
		
		$this->export_entities_array[$r] = array(
			't.fk_source' => 'contract', 
			'p.ref' => 'propal', 'p.total_ht' => 'propal', 'p.total_ttc' => 'propal', 'propal', 'pe.datedmarrage' => 'propal', 'p.date_livraison' => 'propal',
			'c.ref' => 'order', 'c.total_ht' => 'order', 'c.total_ttc' => 'order', 'ce.date_start' => 'order', 'c.date_livraison' => 'order',
			'f.ref' => 'invoice', 'f.total_ht' => 'invoice', 'f.total_ttc' => 'invoice', 'f.datef' => 'invoice',
		);
		
		$this->export_sql_start[$r] ='SELECT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'element_element as t';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'contrat as ct ON ct.rowid = t.fk_source';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande as c ON c.rowid = t.fk_target AND t.targettype = "commande"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields as ce ON c.rowid = ce.fk_object';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture as f ON f.rowid = t.fk_target AND t.targettype = "facture"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal as p ON p.rowid = t.fk_target AND t.targettype = "propal"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields as pe ON p.rowid = pe.fk_object';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.sourcetype = "contrat"';
		$this->export_sql_end[$r] .='  AND (t.targettype = "propal"';
		$this->export_sql_end[$r] .=' OR  t.targettype = "commande"';
		$this->export_sql_end[$r] .=' OR t.targettype = "facture")';
		$r++; 

		//Facture : Devis, Marché et Commandes
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Facture : Devis, Marché et Commandes';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]= 'invoice';
		$this->export_fields_array[$r] = array(
			't.fk_target' => 'ID. Facture', 
			'f.ref' => 'Rèf. Facture', 'f.total_ht' => 'Fac. Total HT', 'f.total_ttc' => 'Fac. Total TTC', 'f.datef' => 'Fact. Date de facturation',
			'p.ref' => 'Rèf. Devis', 'p.total_ht' => 'Dev. Total HT', 'p.total_ttc' => 'Dev. Total TTC', 'pe.datedmarrage' => ' Dev. Date de démarage', 'p.date_livraison' => ' Dev. Date de livraison',
			'ct.ref' => 'Rèf. Contrat',  
			'c.ref' => 'Rèf. Commande', 'c.total_ht' => 'Code. Total HT', 'c.total_ttc' => 'Code. Total TTC', 'ce.date_start' => ' Code. Date de démarage', 'c.date_livraison' => 'Code. Date de livraison',
		);
		$this->export_TypeFields_array[$r] = array(
			't.fk_target' => 'Numeric', 
			'f.ref' => 'Text', 'f.total_ht' => 'Numeric','f.total_ttc' => 'Numeric', 'f.datef' => 'Date',
			'p.ref' => 'Text', 'p.total_ht' => 'Numeric','p.total_ttc' => 'Numeric', 'pe.datedmarrage' => 'Date', 'p.date_livraison' => 'Date',
			'ct.ref' => 'Text', 
			'c.ref' => 'Text', 'c.total_ht' => 'Numeric',  'c.total_ttc' => 'Numeric', 'ce.date_start' => 'Date', 'c.date_livraison' => 'Date',
		);
		
		$this->export_entities_array[$r] = array(
			't.fk_target' => 'invoice', 
			'f.ref' => 'invoice', 'f.total_ht' => 'invoice', 'f.total_ttc' => 'invoice', 'f.datef' => 'invoice',
			'p.ref' => 'propal', 'p.total_ht' => 'propal', 'p.total_ttc' => 'propal', 'pe.datedmarrage' => 'propal', 'p.date_livraison' => 'propal',
			'ct.ref' => 'contract', 
			'c.ref' => 'order', 'c.total_ht' => 'order', 'c.total_ttc' => 'order', 'ce.date_start' => 'order', 'c.date_livraison' => 'order',
		);
		
		$this->export_sql_start[$r] ='SELECT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'element_element as t';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture as f ON f.rowid = t.fk_target';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande as c ON c.rowid = t.fk_source AND t.sourcetype = "commande"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields as ce ON c.rowid = ce.fk_object';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'contrat as ct ON ct.rowid = t.fk_source AND t.sourcetype = "contrat"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal as p ON p.rowid = t.fk_source AND t.sourcetype = "propal"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields as pe ON p.rowid = pe.fk_object';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.targettype = "facture"';
		$this->export_sql_end[$r] .='  AND (t.sourcetype = "propal"';
		$this->export_sql_end[$r] .=' OR  t.sourcetype = "commande"';
		$this->export_sql_end[$r] .=' OR t.sourcetype = "contrat")';
		$r++; 

		// Commande : Marché, Devis et Factures
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Commande : Marché, Devis et Factures';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='order';
		$this->export_fields_array[$r] = array(
			't.fk_target' => 'ID. Commande', 
			'c.ref' => 'Rèf. Commande', 'c.total_ht' => 'Code. Total HT', 'c.total_ttc' => 'Code. Total TTC', 'ce.date_start' => 'Code. Date de démarage', 'c.date_livraison' => 'Code. Date de livraison',
			'ct.ref' => 'Rèf. Contrat', 
			'p.ref' => 'Rèf. Devis', 'p.total_ht' => 'Dev. Total HT', 'p.total_ttc' => 'Dev. Total TTC', 'pe.datedmarrage' => ' Dev. Date de démarage', 'p.date_livraison' => ' Dev. Date de livraison',
			'f.ref' => 'Rèf. Facture', 'f.total_ht' => 'Fac. Total HT', 'f.total_ttc' => 'Fac. Total TTC', 'f.datef' => 'Fact. Date de facturation',
		);
		$this->export_TypeFields_array[$r] = array(
			't.fk_target' => 'Numeric', 
			'c.ref' => 'Text', 'c.total_ht' => 'Numeric', 'c.total_ttc' => 'Numeric', 'ce.date_start' => 'Date', 'c.date_livraison' => 'Date',
			'ct.ref' => 'Text', 
			'p.ref' => 'Text', 'p.total_ht' => 'Numeric','p.total_ttc' => 'Numeric', 'pe.datedmarrage' => 'Date', 'p.date_livraison' => 'Date',
			'f.ref' => 'Text', 'f.total_ht' => 'Numeric',  'f.total_ttc' => 'Numeric', 'f.datef' => 'Date',
		);
		
		$this->export_entities_array[$r] = array(
			't.fk_target' => 'order', 
			'c.ref' => 'order', 'c.total_ht' => 'order', 'c.total_ttc' => 'order', 'ce.date_start' => 'order', 'c.date_livraison' => 'order',
			'ct.ref' => 'contract', 
			'p.ref' => 'propal', 'p.total_ht' => 'propal', 'p.total_ttc' => 'propal', 'pe.datedmarrage' => 'propal', 'p.date_livraison' => 'propal',
			'f.ref' => 'invoice', 'f.total_ht' => 'invoice', 'f.total_ttc' => 'invoice', 'f.datef' => 'invoice',
		);
		
		$this->export_sql_start[$r] ='SELECT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'element_element as t';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande as c ON c.rowid = t.fk_target';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields as ce ON c.rowid = ce.fk_object';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture as f ON f.rowid = t.fk_source AND t.sourcetype = "facture"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'contrat as ct ON ct.rowid = t.fk_source AND t.sourcetype = "contrat"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal as p ON p.rowid = t.fk_source AND t.sourcetype = "propal"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields as pe ON p.rowid = pe.fk_object';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.targettype = "commande"';
		$this->export_sql_end[$r] .='  AND (t.sourcetype = "propal"';
		$this->export_sql_end[$r] .=' OR  t.sourcetype = "facture"';
		$this->export_sql_end[$r] .=' OR t.sourcetype = "contrat")';
		$r++; 

		// Devis : Commande, Marché et Factures
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Devis : Commande, Marché et Factures';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]= 'propal';
		$this->export_fields_array[$r] = array(
			't.fk_target' => 'ID. Devis', 
			'p.ref' => 'Rèf. Devis', 'p.total_ht' => 'Dev. Total HT', 'p.total_ttc' => 'Dev. Total TTC', 'pe.datedmarrage' => ' Dev. Date de démarage', 'p.date_livraison' => ' Dev. Date de livraison',
			'c.ref' => 'Rèf. Commande', 'c.total_ht' => 'Code. Total HT', 'c.total_ttc' => 'Code. Total TTC', 'ce.date_start' => 'Code. Date de démarage', 'c.date_livraison' => 'Code. Date de livraison',
			'ct.ref' => 'Rèf. Contrat',  
			'f.ref' => 'Rèf. Facture', 'f.total_ht' => 'Fac. Total HT', 'f.total_ttc' => 'Fac. Total TTC', 'f.datef' => 'Fact. Date de facturation',
		);
		$this->export_TypeFields_array[$r] = array(
			't.fk_target' => 'Numeric', 
			'p.ref' => 'Text', 'p.total_ht' => 'Numeric','p.total_ttc' => 'Numeric', 'pe.datedmarrage' => 'Date', 'p.date_livraison' => 'Date',
			'c.ref' => 'Text', 'c.total_ht' => 'Numeric','c.total_ttc' => 'Numeric', 'ce.date_start' => 'Date', 'c.date_livraison' => 'Date',
			'ct.ref' => 'Text', 
			'f.ref' => 'Text', 'f.total_ht' => 'Numeric',  'f.total_ttc' => 'Numeric', 'f.datef' => 'Date',
		);
		
		$this->export_entities_array[$r] = array(
			't.fk_target' => 'propal', 
			'p.ref' => 'propal', 'p.total_ht' => 'propal', 'p.total_ttc' => 'propal', 'pe.datedmarrage' => 'propal', 'p.date_livraison' => 'propal',
			'c.ref' => 'order', 'c.total_ht' => 'order', 'c.total_ttc' => 'order', 'ce.date_start' => 'order', 'c.date_livraison' => 'order',
			'ct.ref' => 'contract', 
			'f.ref' => 'invoice', 'f.total_ht' => 'invoice', 'f.total_ttc' => 'invoice', 'f.datef' => 'invoice',
		);
		
		$this->export_sql_start[$r] ='SELECT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'element_element as t';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal as p ON p.rowid = t.fk_target';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields as pe ON p.rowid = pe.fk_object';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande as c ON c.rowid = t.fk_target AND t.sourcetype = "commande"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields as ce ON c.rowid = ce.fk_object';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture as f ON f.rowid = t.fk_target AND t.sourcetype = "facture"';
		$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'contrat as ct ON ct.rowid = t.fk_target AND t.sourcetype = "contrat"';
		
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.targettype = "propal"';
		$this->export_sql_end[$r] .='  AND (t.sourcetype = "commande"';
		$this->export_sql_end[$r] .=' OR  t.sourcetype = "facture"';
		$this->export_sql_end[$r] .=' OR t.sourcetype = "contrat")';
		$r++; 
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		$langs->load("workload@workload");
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='ChargeLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='charge@workload';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'workload_charge', 'extra' => MAIN_DB_PREFIX.'workload_charge_extrafields');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'Charge'; $keyforclassfile='/workload/class/charge.class.php'; $keyforelement='charge@workload';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='charge'; $keyforaliasextra='extra'; $keyforelement='charge@workload';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'workload_charge');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.ref' => array(
				'rule'=>'getrefifauto',
				'class'=>(!getDolGlobalString('WORKLOAD_MYOBJECT_ADDON') ? 'mod_charge_standard' : getDolGlobalString('WORKLOAD_MYOBJECT_ADDON')),
				'path'=>"/core/modules/commande/".(!getDolGlobalString('WORKLOAD_MYOBJECT_ADDON') ? 'mod_charge_standard' : getDolGlobalString('WORKLOAD_MYOBJECT_ADDON')).'.php'
				'classobject'=>'Charge',
				'pathobject'=>'/workload/class/charge.class.php',
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
	 * Shows filter for id to select
	 */
	public function showExportForm()
	{
		global $langs, $db;
		
		echo '<form method="post" action="export.php">';
		echo '<h3>'.$langs->trans('ChooseFieldsToExport').'</h3>';
		
		foreach ($this->export_fields_array as $key => $val) {
			echo '<input type="checkbox" name="fields[]" value="'.$key.'"> '.$val[1].'<br>';
		}
		
		echo '<h3>'.$langs->trans('OrChooseExportProfile').'</h3>';
		echo '<select name="profile">';
		foreach ($this->export_sql_start as $profile_id => $sql) {
			echo '<option value="'.$profile_id.'">'.$langs->trans('Profile').' '.$profile_id.'</option>';
		}
		echo '</select>';
		
		echo '<br><input type="submit" value="'.$langs->trans('Export').'">';
		echo '</form>';
	}

	// function processExport($selectedFields, $selectedProfile)
	// {
	// 	global $db;

	// 	if ($selectedProfile) {
	// 		$sql = $this->export_sql_start[$selectedProfile];
	// 	} else {
	// 		$fields = implode(', ', $selectedFields);
	// 		$sql = 'SELECT '.$fields.' FROM '.MAIN_DB_PREFIX.'contrat';
	// 	}

	// 	$resql = $db->query($sql);

	// 	if ($resql) {
	// 		// Export the data (e.g., to CSV or Excel)
	// 	} else {
	// 		// Handle the error
	// 	}
	// }

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

		//$result = $this->_load_tables('/install/mysql/', 'workload');
		$result = $this->_load_tables('/workload/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('workload_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'workload@workload', 'isModEnabled("workload")');
		//$result2=$extrafields->addExtraField('workload_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'workload@workload', 'isModEnabled("workload")');
		//$result3=$extrafields->addExtraField('workload_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'workload@workload', 'isModEnabled("workload")');
		//$result4=$extrafields->addExtraField('workload_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'workload@workload', 'isModEnabled("workload")');
		//$result5=$extrafields->addExtraField('workload_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'workload@workload', 'isModEnabled("workload")');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('workload');
		$myTmpObjects = array();
		$myTmpObjects['Charge'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'Charge') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_charges.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_charges.odt';

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
