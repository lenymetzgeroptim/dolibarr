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
 * 	\defgroup   holidaycustom     Module Holidaycustom
 *  \brief      Holidaycustom module descriptor.
 *
 *  \file       htdocs/holidaycustom/core/modules/modHolidaycustom.class.php
 *  \ingroup    holidaycustom
 *  \brief      Description and activation file for module Holidaycustom
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Holidaycustom
 */
class modHolidaycustom extends DolibarrModules
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
		$this->numero = 500005; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'holidaycustom';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "hr";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '01';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleHolidaycustomName' not found (Holidaycustom is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleHolidaycustomDesc' not found (Holidaycustom is name of module).
		$this->description = "Leave requests";
		// Used only if file README.md and README-LL.md not found.
		//$this->descriptionlong = "HolidaycustomDescription";

		// Author
		$this->editor_name = 'Lény METZGER';
		//$this->editor_url = 'https://www.example.com';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where HOLIDAYCUSTOM is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'holiday';

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
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				'/holidaycustom/css/holidaycustom.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/holidaycustom/js/holidaycustom.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				   'data' => array(
				       'hrmindex',
					   'searchform'
				   ),
				   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/holidaycustom/temp","/holidaycustom/subdir");
		$this->dirs = array("/holiday/temp");

		// Config pages. Put here list of php page, stored into holidaycustom/admin directory, to use to setup module.
		$this->config_page_url = array("holiday.php@holidaycustom");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 0); // Minimum version of Dolibarr required by module
		$this->langfiles = array("holiday", "holidaycustom@holidaycustom");

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'HolidaycustomWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('HOLIDAYCUSTOM_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('HOLIDAYCUSTOM_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(); // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r = 0;

		$this->const[$r][0] = "HOLIDAY_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_holiday_madonna";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des congés';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "HOLIDAY_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "celebrate";
		$this->const[$r][3] = 'Name of PDF model of holiday';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "HOLIDAY_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/holiday";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->holidaycustom) || !isset($conf->holidaycustom->enabled)) {
			$conf->holidaycustom = new stdClass();
			$conf->holidaycustom->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@holidaycustom:$user->rights->holidaycustom->read:/holidaycustom/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@holidaycustom:$user->rights->othermodule->read:/holidaycustom/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		$this->dictionaries = array(
			'langs'=>array('holiday','holidaycustom@holidaycustom'),
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."c_holiday_types"),
			// Label of tables
			'tablib'=>array("DictionaryHolidayTypes"),
			// Request to select fields
			'tabsql'=>array("SELECT h.rowid as rowid, h.code, h.code_silae, h.label, h.affect, h.delay, h.newbymonth, h.fk_country as country_id, h.in_hour, h.droit_rtt, c.code as country_code, c.label as 
			country, h.active FROM ".MAIN_DB_PREFIX."c_holiday_types as h LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON h.fk_country=c.rowid WHERE 
			(h.code <> 'CP_ANC_ACQUIS' AND h.code <> 'CP_ANC_PRIS' AND h.code <> 'CP_FRAC_ACQUIS' AND h.code <> 'CP_FRAC_PRIS' AND h.code <> 'CP_N_ACQUIS' 
			AND h.code <> 'CP_N_PRIS' AND h.code <> 'CP_N-1_ACQUIS' AND h.code <> 'CP_N-1_PRIS' AND h.code <> 'RTT_ACQUIS' AND h.code <> 'RTT_PRIS')"),
			// Sort order
			'tabsqlsort'=>array("country ASC, code ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,code_silae,label,affect,delay,newbymonth,country_id,in_hour,droit_rtt,country"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,code_silae,label,affect,delay,newbymonth,in_hour,droit_rtt,country"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,code_silae,label,affect,delay,newbymonth,in_hour,droit_rtt,fk_country"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array(""),
			// Condition to show each dictionary
			'tabcond'=>array($conf->holidaycustom->enabled)
		);
		/* Example:
		$this->dictionaries=array(
			'langs'=>'holidaycustom@holidaycustom',
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
			'tabcond'=>array($conf->holidaycustom->enabled, $conf->holidaycustom->enabled, $conf->holidaycustom->enabled)
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in holidaycustom/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'holidaycustomwidget1.php@holidaycustom',
			//      'note' => 'Widget provided by Holidaycustom',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$arraydate = dol_getdate(dol_now());
		$datestart = dol_mktime(4, 0, 0, $arraydate['mon'], $arraydate['mday'], $arraydate['year']);
		$this->cronjobs = array(
			0 => array(
				'label' => 'Mise à jour mensuelle du solde des congés (Custom)',
				'jobtype' => 'method',
				'class' => '/custom/holidaycustom/class/holiday.class.php',
				'objectname' => 'Holiday',
				'method' => 'updateBalance',
				'parameters' => '',
				'comment' => 'Met à jour le nombre de congés tous les mois',
				'frequency' => 1,
				'unitfrequency' => 3600 * 24,
				'priority' => 50,
				'status' => 1,
				'test' => '$conf->holidaycustom->enabled',
				'datestart' => $datestart
			),
			// 1 => array(
			// 	'label' => 'Import des congés',
			// 	'jobtype' => 'method',
			// 	'class' => '/custom/holidaycustom/class/holiday.class.php',
			// 	'objectname' => 'Holiday',
			// 	'method' => 'import_conges',
			// 	'parameters' => '',
			// 	'comment' => "Permet d'importer le nombre de congés à partir d'un fichier excel",
			// 	'frequency' => 1,
			// 	'unitfrequency' => 3600 * 24 * 31,
			// 	'priority' => 50,
			// 	'status' => 1,
			// 	'test' => '$conf->holidaycustom->enabled',
			// 	'datestop' => $datestart
			// ),
			2 => array(
				'label' => 'Vérification congés >= 2 semaines',
				'jobtype' => 'method',
				'class' => '/custom/holidaycustom/class/holiday.class.php',
				'objectname' => 'Holiday',
				'method' => 'MailConges_2semaines',
				'parameters' => '',
				'comment' => "",
				'frequency' => 1,
				'unitfrequency' => 3600 * 24,
				'priority' => 50,
				'status' => 1,
				'test' => '$conf->holidaycustom->enabled',
			)
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->holidaycustom->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->holidaycustom->enabled', 'priority'=>50)
		// );

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = 20001; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read leave requests (yours and your subordinates)'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20002; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/modify leave requests'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20003; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete leave requests'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20007;
		$this->rights[$r][1] = 'Approve leave requests';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'approve';
		$r++;

		$this->rights[$r][0] = 20004; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read leave requests for everybody'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'readall'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20005; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/modify leave requests for everybody'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'writeall'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20006; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Setup leave requests of users (setup and update balance)'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'define_holiday'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		// $this->rights[$r][0] = 20008; // Permission id (must not be already used)
		// $this->rights[$r][1] = 'Lire toutes les demandes de congé en vue simplifiée (même celles des utilisateurs non subordonnés)'; // Permission label
		// $this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'readall_simple'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		$this->rights[$r][0] = 20009; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Modifier les approbateurs des congés'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'changeappro'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20010; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Réaliser l\'import des congés'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'import'; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;

		if(!$conf->global->HOLIDAY_MENU_IN_FDT) {
			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=hrm',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>$langs->trans("CPTitreMenu"),
				'prefix' => img_picto('', 'holiday', 'class="pictofixedwidth"'),
				'mainmenu'=>'hrm',
				'leftmenu'=>'holiday',
				'url'=>'/holidaycustom/list.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->read',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=> $langs->trans("New"),
				'mainmenu'=>'hrm',
				'leftmenu'=>'holiday_create',
				'url'=>'/holidaycustom/card.php?action=create',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->write',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=> "Liste des congés",
				'mainmenu'=>'hrm',
				'leftmenu'=>'holiday_list',
				'url'=>'/holidaycustom/list.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->read',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>  $langs->trans("MenuConfCP"),
				'mainmenu'=>'hrm',
				'leftmenu'=>'holiday_define',
				'url'=>'/holidaycustom/define_holiday.php?action=request',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->read',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=> $langs->trans("MenuReportMonth"),
				'mainmenu'=>'hrm',
				'leftmenu'=>'holiday_month_report',
				'url'=>'/holidaycustom/month_report.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->readall',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>  $langs->trans("MenuLogCP"),
				'mainmenu'=>'hrm',
				'leftmenu'=>'holiday_view_log',
				'url'=>'/holidaycustom/view_log.php?action=request',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->define_holiday',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>  "Import des congés",
				'mainmenu'=>'hrm',
				'leftmenu'=>'holiday_import',
				'url'=>'/holidaycustom/import.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->import',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);
		}
		else {
			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=feuilledetemps',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>$langs->trans("CPTitreMenu"),
				'prefix' => img_picto('', 'holiday', 'class="pictofixedwidth"'),
				'mainmenu'=>'feuilledetemps',
				'leftmenu'=>'holiday',
				'url'=>'/holidaycustom/list.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->read',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=feuilledetemps,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=> $langs->trans("New"),
				'mainmenu'=>'feuilledetemps',
				'leftmenu'=>'holiday_create',
				'url'=>'/holidaycustom/card.php?action=create',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->write',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=feuilledetemps,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=> "Liste des congés",
				'mainmenu'=>'feuilledetemps',
				'leftmenu'=>'holiday_list',
				'url'=>'/holidaycustom/list.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->read',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=feuilledetemps,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>  $langs->trans("MenuConfCP"),
				'mainmenu'=>'feuilledetemps',
				'leftmenu'=>'holiday_define',
				'url'=>'/holidaycustom/define_holiday.php?action=request',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->read',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=feuilledetemps,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=> $langs->trans("MenuReportMonth"),
				'mainmenu'=>'feuilledetemps',
				'leftmenu'=>'holiday_month_report',
				'url'=>'/holidaycustom/month_report.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->readall',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=feuilledetemps,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>  $langs->trans("MenuLogCP"),
				'mainmenu'=>'feuilledetemps',
				'leftmenu'=>'holiday_view_log',
				'url'=>'/holidaycustom/view_log.php?action=request',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->define_holiday',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);

			$this->menu[$r++]=array(
				'fk_menu'=>'fk_mainmenu=feuilledetemps,fk_leftmenu=holiday',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
				'type'=>'left',                          // This is a Top menu entry
				'titre'=>  "Import des congés",
				'mainmenu'=>'feuilledetemps',
				'leftmenu'=>'holiday_import',
				'url'=>'/holidaycustom/import.php',
				'langs'=>'holiday',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>$r,
				'enabled'=> '$conf->holidaycustom->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
				'perms'=> '$user->rights->holidaycustom->import',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			);
		}


		// Exports
		$r = 0;

		$r++;
		$this->export_code[$r] = 'leaverequest_'.$r;
		$this->export_label[$r] = 'ListeCP';
		$this->export_icon[$r] = 'holiday';
		$this->export_permission[$r] = array(array("holidaycustom", "readall"));
		$this->export_fields_array[$r] = array(
			'd.rowid'=>"LeaveId", 'd.fk_type'=>'TypeOfLeaveId', 't.code'=>'TypeOfLeaveCode', 't.label'=>'TypeOfLeaveLabel', 'd.fk_user'=>'UserID',
			'd.date_debut'=>'DateStart', 'd.date_fin'=>'DateEnd', 'd.halfday'=>'HalfDay', 'none.num_open_days'=>'NbUseDaysCP',
			'd.date_valid'=>'DateApprove', 'd.fk_validator'=>"UserForApprovalID",
			'u.lastname'=>'Lastname', 'u.firstname'=>'Firstname', 'u.login'=>"Login",
			'ua.lastname'=>"UserForApprovalLastname", 'ua.firstname'=>"UserForApprovalFirstname",
			'ua.login'=>"UserForApprovalLogin", 'd.description'=>'Description', 'd.statut'=>'Status'
		);
		$this->export_TypeFields_array[$r] = array(
			'd.rowid'=>"Numeric", 't.code'=>'Text', 't.label'=>'Text', 'd.fk_user'=>'Numeric',
			'd.date_debut'=>'Date', 'd.date_fin'=>'Date', 'none.num_open_days'=>'NumericCompute',
			'd.date_valid'=>'Date', 'd.fk_validator'=>"Numeric",
			'u.lastname'=>'Text', 'u.firstname'=>'Text', 'u.login'=>"Text",
			'ua.lastname'=>"Text", 'ua.firstname'=>"Text",
			'ua.login'=>"Text", 'd.description'=>'Text', 'd.statut'=>'Numeric'
		);
		$this->export_entities_array[$r] = array(
			'u.lastname'=>'user', 'u.firstname'=>'user', 'u.login'=>'user', 'ua.lastname'=>'user', 'ua.firstname'=>'user', 'ua.login'=>'user'
		);
		$this->export_alias_array[$r] = array('d.rowid'=>"idholiday");
		$this->export_special_array[$r] = array('none.num_open_days'=>'getNumOpenDays');
		$this->export_dependencies_array[$r] = array(); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$keyforselect = 'holiday';
		$keyforelement = 'holiday';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'user'; $keyforelement = 'user'; $keyforaliasextra = 'extrau';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'holiday as d';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'holiday_extrafields as extra on d.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_holiday_types as t ON t.rowid = d.fk_type';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as ua ON ua.rowid = d.fk_validator,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as extrau ON u.rowid = extrau.fk_object';
		$this->export_sql_end[$r] .= ' WHERE d.fk_user = u.rowid';
		$this->export_sql_end[$r] .= ' AND d.entity IN ('.getEntity('holiday').')';

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		 $langs->load("holidaycustom@holidaycustom");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='HolidayHourLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='holidayhour@holidaycustom';
		 $keyforclass = 'HolidayHour'; $keyforclassfile='/holidaycustom/class/holidayhour.class.php'; $keyforelement='holidayhour@holidaycustom';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='holidayhour'; $keyforaliasextra='extra'; $keyforelement='holidayhour@holidaycustom';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'holidayhour as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('holidayhour').')';
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

		//$result = $this->_load_tables('/install/mysql/tables/', 'holidaycustom');
		$result = $this->_load_tables('/holidaycustom/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('holidaycustom_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'holidaycustom@holidaycustom', '$conf->holidaycustom->enabled');
		//$result2=$extrafields->addExtraField('holidaycustom_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'holidaycustom@holidaycustom', '$conf->holidaycustom->enabled');
		//$result3=$extrafields->addExtraField('holidaycustom_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'holidaycustom@holidaycustom', '$conf->holidaycustom->enabled');
		//$result4=$extrafields->addExtraField('holidaycustom_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'holidaycustom@holidaycustom', '$conf->holidaycustom->enabled');
		//$result5=$extrafields->addExtraField('holidaycustom_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'holidaycustom@holidaycustom', '$conf->holidaycustom->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('holidaycustom');
		$myTmpObjects = array();
		$myTmpObjects['HolidayHour'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'HolidayHour') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_holidayhours.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_holidayhours.odt';

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
