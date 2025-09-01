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
 * 	\defgroup   actions     Module Actions
 *  \brief      Actions module descriptor.
 *
 *  \file       htdocs/actions/core/modules/modActions.class.php
 *  \ingroup    actions
 *  \brief      Description and activation file for module Actions
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Actions
 */
class modActions extends DolibarrModules
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
		$this->numero = 500034; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'actions';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleActionsName' not found (Actions is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleActionsDesc' not found (Actions is name of module).
		$this->description = "ActionsDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "ActionsDescription";

		// Author
		$this->editor_name = 'OPTIM Industries';
		$this->editor_url = 'optim-industries.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where ACTIONS is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		
		$this->picto = 'fa-file-alt_fas_green';
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
				//    '/actions/css/actions.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/actions/js/actions.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				  'data' => array(
				      'constatLinked',
				      'hookcontext2',
					  'actioncard',
				  ),
				  'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/actions/temp","/actions/subdir");
		$this->dirs = array("/actions/temp");

		// Config pages. Put here list of php page, stored into actions/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@actions");

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
		$this->langfiles = array("actions@actions");

		// Prerequisites
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'ActionsWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('ACTIONS_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('ACTIONS_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
			//1 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONS_SENDMAIL', 'chaine', '1', '', 0), 
			2 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_CREATE', 'chaine', '1', '', 0), 
			3 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_MODIFY', 'chaine', '1', '', 0), 
			// 4 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_DELETE', 'chaine', '1', '', 0), 
			5 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_VALIDATE', 'chaine', '1', '', 0),
			6 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_ATT_SOLDE', 'chaine', '1', '', 0), 
			// 7 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_EN_COURS', 'chaine', '1', '', 0),
			8 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_SOLDE', 'chaine', '1', '', 0), 
			9 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_CLOTURE', 'chaine', '1', '', 0), 
			10 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONQ3SE_CANCEL', 'chaine', '1', '', 0),
		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->actions) || !isset($conf->actions->enabled)) {
			$conf->actions = new stdClass();
			$conf->actions->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@actions:$user->rights->actions->read:/actions/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@actions:$user->rights->othermodule->read:/actions/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		$this->dictionaries=array(
			'langs'=>'actions@actions',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array("c_actions_type"),
			// Label of tables
			'tablib'=>array("ActionsType"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_actions_type as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("label"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid"),
			// Condition to show each dictionary
			'tabcond'=>array(isModEnabled('actions')),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp'=>array(''),
		);
		

		// Boxes/Widgets
		// Add here list of php file(s) stored in actions/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'actionswidget1.php@actions',
			//      'note' => 'Widget provided by Actions',
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
			//      'class' => '/actions/class/actionq3se.class.php',
			//      'objectname' => 'ActionQ3SE',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("actions")',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'isModEnabled("actions")', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'isModEnabled("actions")', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire ses propres actions'; // Permission label
		$this->rights[$r][4] = 'action';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->hasRight('actions', 'action', 'read'))
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 2); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire toutes les actions'; // Permission label
		$this->rights[$r][4] = 'action';
		$this->rights[$r][5] = 'readall'; // In php code, permission will be checked by test if ($user->hasRight('actions', 'action', 'read'))
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 3); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer/Modifier les actions'; // Permission label
		$this->rights[$r][4] = 'action';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->hasRight('actions', 'action', 'write'))
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 4); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Clôturer les actions'; // Permission label
		$this->rights[$r][4] = 'action';
		$this->rights[$r][5] = 'close'; // In php code, permission will be checked by test if ($user->rights->actions->action->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 5); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Annuler les actions'; // Permission label
		$this->rights[$r][4] = 'action';
		$this->rights[$r][5] = 'cancel'; // In php code, permission will be checked by test if ($user->rights->actions->action->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 6); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer les actions'; // Permission label
		$this->rights[$r][4] = 'action';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->actions->action->delete)
		$r++;
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Accès partie service Q3SE'; // Permission label
		// $this->rights[$r][4] = 'action';
		// $this->rights[$r][5] = 'ServiceQ3SE'; // In php code, permission will be checked by test if ($user->rights->actions->action->ServiceQ3SE)
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Accès partie intervenant'; // Permission label
		// $this->rights[$r][4] = 'action';
		// $this->rights[$r][5] = 'intervenant'; // In php code, permission will be checked by test if ($user->rights->actions->action->intervenant)
		// $r++;
		// $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Droit des statistique'; // Permission label
		// $this->rights[$r][4] = 'action';
		// $this->rights[$r][5] = 'Statistique'; // In php code, permission will be checked by test if ($user->rights->actions->action->Statistique)
		// $r++;



		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
/*		$this->menu = array();
		$r = 0;*/
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
/*		$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'ModuleActionsName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'actions',
			'leftmenu'=>'',
			'url'=>'/actions/actionsindex.php',
			'langs'=>'actions@actions', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'isModEnabled("actions")', // Define condition to show or hide menu entry. Use 'isModEnabled("actions")' if entry must be visible if module is enabled.
			'perms'=>'1', // Use 'perms'=>'$user->hasRight("actions", "action", "read")' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);*/
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=q3serp',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'Action',
			'prefix' => img_picto('', $this->picto, ' class="pictofixedwidth"'),
			'mainmenu'=>'q3serp',
			'leftmenu'=>'actions_action_list',
			'url'=>'/actions/actionsindex.php',
			'langs'=>'q3serp@q3serp',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1107,
			'enabled'=>'isModEnabled("actions")', // Define condition to show or hide menu entry. Use 'isModEnabled("actions")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("actions", "action", "read")||$user->hasRight("actions", "action", "readall")',
			'target'=>'',
			'user'=>2,			                // 0=Menu for internal users, 1=external users, 2=both
		);
	
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=q3serp,fk_leftmenu=actions_action_list',     // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'Liste des actions',
			'mainmenu'=>'q3serp',
			'leftmenu'=>'actions_action_list_',
			'url'=>'/actions/action_list.php',
			'langs'=>'q3serp@q3serp',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1108,
			'enabled'=>'isModEnabled("actions")', // Define condition to show or hide menu entry. Use 'isModEnabled("actions")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("actions", "action", "read")||$user->hasRight("actions", "action", "readall")',
			'target'=>'',
			'user'=>2,			                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=q3serp,fk_leftmenu=actions_action_list',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'Nouvelle actions',
			'mainmenu'=>'q3serp',
			'leftmenu'=>'actions_action_new',
			'url'=>'/actions/action_card.php?action=create',
			'langs'=>'q3serp@q3serp',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1109,
			'enabled'=>'isModEnabled("actions")', // Define condition to show or hide menu entry. Use 'isModEnabled("actions")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->hasRight("actions", "action", "write")',
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		

		/*LEFTMENU ACTION*/
		/* $this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=actions',
			'type'=>'left',
			'titre'=>'Action',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'actions',
			'leftmenu'=>'action',
			'url'=>'/actions/action_list.php',
			'langs'=>'actions@actions',
			'position'=>1000+$r,
			'enabled'=>'$conf->testmodule->enabled',
			'perms'=>'1',
			'target'=>'',
			'user'=>2,
		);
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=actions,fk_leftmenu=action',
            'type'=>'left',
            'titre'=>'List Action',
            'mainmenu'=>'actions',
            'leftmenu'=>'actions_action_list',
            'url'=>'/actions/action_list.php',
            'langs'=>'actions@actions',
            'position'=>1000+$r,
            'enabled'=>'$conf->actions->enabled',
            'perms'=>'1',
            'target'=>'',
            'user'=>2,
        );
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=actions,fk_leftmenu=action',
            'type'=>'left',
            'titre'=>'New Action',
            'mainmenu'=>'actions',
            'leftmenu'=>'actions_action_new',
            'url'=>'/actions/action_card.php?action=create',
            'langs'=>'actions@actions',
            'position'=>1000+$r,
            'enabled'=>'$conf->actions->enabled',
            'perms'=>'1',
            'target'=>'',
            'user'=>2
        ); */

		/*END LEFTMENU ACTION*/
		/* END MODULEBUILDER LEFTMENU MYOBJECT */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("actions@actions");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ActionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='action@actions';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'ActionQ3SE'; $keyforclassfile='/actions/class/actionq3se.class.php'; $keyforelement='action@actions';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'ActionLine'; $keyforclassfile='/actions/class/actionq3se.class.php'; $keyforelement='actionline@actions'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='action'; $keyforaliasextra='extra'; $keyforelement='action@actions';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='actionline'; $keyforaliasextra='extraline'; $keyforelement='actionline@actions';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('actionline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'action as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'action_line as tl ON tl.fk_action = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('action').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		
		
		// error_log("Début de l'importation des actions");

		$langs->load("actions@actions");
		$this->import_code[$r] = $this->rights_class . '_' . $r;
		$this->import_label[$r] = 'ActionLines'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r] = 'action@actions';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX . 'actions_actionq3se', 'extra' => MAIN_DB_PREFIX . 'actions_actionq3se_extrafields');
		// $this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id

		$import_sample = array();
		$keyforclass = 'ActionQ3SE';
		$keyforclassfile = '/actions/class/actionq3se.class.php';
		$keyforelement = 'action@actions';

		// error_log("Inclusion de commonfieldsinimport.inc.php");
		include DOL_DOCUMENT_ROOT . '/core/commonfieldsinimport.inc.php';

		$import_extrafield_sample = array();
		$keyforselect = 'action';
		$keyforaliasextra = 'extra';
		$keyforelement = 'action@actions';

		// error_log("Inclusion de extrafieldsinimport.inc.php");
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinimport.inc.php';

		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-' . MAIN_DB_PREFIX . 'actions_actionq3se');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.rowid' => 'ID');
		$this->import_convertvalue_array[$r] = array(
		't.rowid' => array(
			'rule' => 'getrowidifauto',
			'class' => (!getDolGlobalString('ACTIONS_MYOBJECT_ADDON') ? 'mod_action_standard' : getDolGlobalString('ACTIONS_MYOBJECT_ADDON')),
		),
		't.date_eche' => array('rule' => 'compute', 'file' => '/custom/actions/class/actionq3se.class.php', 'class' => 'ActionQ3SE', 'method' => 'formatDateString'),
		't.date_sol' => array('rule' => 'compute', 'file' => '/custom/actions/class/actionq3se.class.php', 'class' => 'ActionQ3SE', 'method' => 'formatDateString'),
		't.date_asse' => array('rule' => 'compute', 'file' => '/custom/actions/class/actionq3se.class.php', 'class' => 'ActionQ3SE', 'method' => 'formatDateString'),
		't.date_creation' => array('rule' => 'compute', 'file' => '/custom/actions/class/actionq3se.class.php', 'class' => 'ActionQ3SE', 'method' => 'formatDateStringHours'),
		);
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

		//$result = $this->_load_tables('/install/mysql/', 'actions');
		$result = $this->_load_tables('/actions/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('actions_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'actions@actions', 'isModEnabled("actions")');
		//$result2=$extrafields->addExtraField('actions_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'actions@actions', 'isModEnabled("actions")');
		//$result3=$extrafields->addExtraField('actions_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'actions@actions', 'isModEnabled("actions")');
		//$result4=$extrafields->addExtraField('actions_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'actions@actions', 'isModEnabled("actions")');
		//$result5=$extrafields->addExtraField('actions_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'actions@actions', 'isModEnabled("actions")');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('actions');
		$myTmpObjects = array();
		$myTmpObjects['ActionQ3SE'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'ActionQ3SE') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_actions.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_actions.odt';

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
