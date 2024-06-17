<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023 METZGER Leny <l.metzger@optim-industries.fr>
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
 * 	\defgroup   donneesrh     Module DonneesRH
 *  \brief      DonneesRH module descriptor.
 *
 *  \file       htdocs/donneesrh/core/modules/modDonneesRH.class.php
 *  \ingroup    donneesrh
 *  \brief      Description and activation file for module DonneesRH
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module DonneesRH
 */
class modDonneesRH extends DolibarrModules
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
		$this->numero = 500024; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'donneesrh';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "hr";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleDonneesRHName' not found (DonneesRH is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleDonneesRHDesc' not found (DonneesRH is name of module).
		$this->description = "DonneesRHDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "DonneesRHDescription";

		// Author
		$this->editor_name = 'Lény METZGER';
		$this->editor_url = 'l.metzger@optim-industries.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where DONNEESRH is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fa-user-plus_fa_#79633f';

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
				'/donneesrh/css/donneesrh.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
                // '/donneesrh/js/donneesrh.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				   'data' => array(
				       'usercard',
				   ),
				   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/donneesrh/temp","/donneesrh/subdir");
		$this->dirs = array("/donneesrh/temp");

		// Config pages. Put here list of php page, stored into donneesrh/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@donneesrh");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("donneesrh@donneesrh");

		// Prerequisites
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'DonneesRHWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('DONNEESRH_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('DONNEESRH_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->donneesrh) || !isset($conf->donneesrh->enabled)) {
			$conf->donneesrh = new stdClass();
			$conf->donneesrh->enabled = 0;
		}

		// Array to add new pages in new tabs
		// Example:
		$this->tabs[] = array('data'=>'user:+donneesrh:DonneesRH:donneesrh@donneesrh:$user->rights->user->user->lire:/donneesrh/user_donneesrh.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@donneesrh:$user->rights->othermodule->read:/donneesrh/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
			'langs'=>'donneesrh@donneesrh',
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
			'tabcond'=>array($conf->donneesrh->enabled, $conf->donneesrh->enabled, $conf->donneesrh->enabled),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in donneesrh/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'donneesrhwidget1.php@donneesrh',
			//      'note' => 'Widget provided by DonneesRH',
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
			//      'class' => '/donneesrh/class/ongletdonneesrh.class.php',
			//      'objectname' => 'OngletDonneesRH',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->donneesrh->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->donneesrh->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->donneesrh->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire les onglets du module Données RH'; // Permission label
		$this->rights[$r][4] = 'ongletdonneesrh';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->donneesrh->ongletdonneesrh->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer/Modifier les onglets du module Données RH'; // Permission label
		$this->rights[$r][4] = 'ongletdonneesrh';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->donneesrh->ongletdonneesrh->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer les onglets du module Données RH'; // Permission label
		$this->rights[$r][4] = 'ongletdonneesrh';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->donneesrh->ongletdonneesrh->delete)
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer/Modifier des attributs'; // Permission label
		$this->rights[$r][4] = 'fielddonneesrh';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->donneesrh->ongletdonneesrh->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer des attributs'; // Permission label
		$this->rights[$r][4] = 'fielddonneesrh';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->donneesrh->ongletdonneesrh->delete)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER LEFTMENU ONGLETDONNEESRH */
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=hrm',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'Données RH',
			'prefix' => img_picto('', $this->picto, 'class="fas fa-user-plus infobox-donneesrh paddingright pictofixedwidth"'),
            'mainmenu'=>'hrm',
            'leftmenu'=>'donneesrh',
            'url'=>'/donneesrh/donneesrh.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'donneesrh@donneesrh',
            'position'=>100+$r,
            // Define condition to show or hide menu entry. Use '$conf->donneesrh->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->donneesrh->enabled',
            // Use 'perms'=>'$user->rights->donneesrh->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->donneesrh->ongletdonneesrh->read',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
		/* END MODULEBUILDER LEFTMENU ONGLETDONNEESRH */

		// Exports
		$r = 0;

		$r++;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'Utilisateurs et attributs (avec les attributs du module Données RH)';
		$this->export_permission[$r] = array(array("user", "user", "export"));
		$this->export_fields_array[$r] = array(
			'u.rowid'=>"Id", 'u.login'=>"Login", 'u.lastname'=>"Lastname", 'u.firstname'=>"Firstname", 'u.employee'=>"Employee", 'u.job'=>"PostOrFunction", 'u.gender'=>"Gender",
			'u.accountancy_code'=>"UserAccountancyCode",
			'u.address'=>"Address", 'u.zip'=>"Zip", 'u.town'=>"Town",
			'u.office_phone'=>'Phone', 'u.user_mobile'=>"Mobile", 'u.office_fax'=>'Fax',
			'u.email'=>"Email", 'u.note_public'=>"NotePublic", 'u.note_private'=>"NotePrivate", 'u.signature'=>'Signature',
			'u.fk_user'=>'HierarchicalResponsible', 'u.thm'=>'THM', 'u.tjm'=>'TJM', 'u.weeklyhours'=>'WeeklyHours',
			'u.dateemployment'=>'DateEmploymentStart', 'u.dateemploymentend'=>'DateEmploymentEnd', 'u.salary'=>'Salary', 'u.color'=>'Color', 'u.api_key'=>'ApiKey',
			'u.birth'=>'DateOfBirth',
			'u.datec'=>"DateCreation", 'u.tms'=>"DateLastModification",
			'u.admin'=>"Administrator", 'u.statut'=>'Status', 'u.datelastlogin'=>'LastConnexion', 'u.datepreviouslogin'=>'PreviousConnexion',
			'u.fk_socpeople'=>"IdContact", 'u.fk_soc'=>"IdCompany",
			'u.fk_member'=>"MemberId",
			"a.firstname"=>"MemberFirstname",
			"a.lastname"=>"MemberLastname",
			'g.nom'=>"Group"
		);
		$this->export_TypeFields_array[$r] = array(
			'u.rowid'=>'Numeric', 'u.login'=>"Text", 'u.lastname'=>"Text", 'u.firstname'=>"Text", 'u.employee'=>'Boolean', 'u.job'=>'Text',
			'u.accountancy_code'=>'Text',
			'u.address'=>"Text", 'u.zip'=>"Text", 'u.town'=>"Text",
			'u.office_phone'=>'Text', 'u.user_mobile'=>'Text', 'u.office_fax'=>'Text',
			'u.email'=>'Text', 'u.datec'=>"Date", 'u.tms'=>"Date", 'u.admin'=>"Boolean", 'u.statut'=>'Status', 'u.note_public'=>"Text", 'u.note_private'=>"Text", 'u.signature'=>"Text", 'u.datelastlogin'=>'Date',
			'u.fk_user'=>"FormSelect:select_dolusers",
			'u.birth'=>'Date',
			'u.datepreviouslogin'=>'Date',
			'u.fk_socpeople'=>'FormSelect:selectcontacts',
			'u.fk_soc'=>"FormSelect:select_company",
			'u.tjm'=>"Numeric", 'u.thm'=>"Numeric", 'u.fk_member'=>"Numeric",
			'u.weeklyhours'=>"Numeric",
			'u.dateemployment'=>"Date", 'u.dateemploymentend'=>"Date", 'u.salary'=>"Numeric",
			'u.color'=>'Text', 'u.api_key'=>'Text',
			'a.firstname'=>'Text',
			'a.lastname'=>'Text',
			'g.nom'=>"Text"
		);
		$this->export_entities_array[$r] = array(
			'u.rowid'=>"user", 'u.login'=>"user", 'u.lastname'=>"user", 'u.firstname'=>"user", 'u.employee'=>'user', 'u.job'=>'user', 'u.gender'=>'user',
			'u.accountancy_code'=>'user',
			'u.address'=>"user", 'u.zip'=>"user", 'u.town'=>"user",
			'u.office_phone'=>'user', 'u.user_mobile'=>'user', 'u.office_fax'=>'user',
			'u.email'=>'user', 'u.note_public'=>"user", 'u.note_private'=>"user", 'u.signature'=>'user',
			'u.fk_user'=>'user', 'u.thm'=>'user', 'u.tjm'=>'user', 'u.weeklyhours'=>'user',
			'u.dateemployment'=>'user', 'u.dateemploymentend'=>'user', 'u.salary'=>'user', 'u.color'=>'user', 'u.api_key'=>'user',
			'u.birth'=>'user',
			'u.datec'=>"user", 'u.tms'=>"user",
			'u.admin'=>"user", 'u.statut'=>'user', 'u.datelastlogin'=>'user', 'u.datepreviouslogin'=>'user',
			'u.fk_socpeople'=>"contact", 'u.fk_soc'=>"company", 'u.fk_member'=>"member",
			'a.firstname'=>"member", 'a.lastname'=>"member",
			'g.nom'=>"Group"
		);

		$keyforselect = 'user';
		$keyforelement = 'user';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		if (empty($conf->adherent->enabled)) {
			unset($this->export_fields_array[$r]['u.fk_member']);
			unset($this->export_entities_array[$r]['u.fk_member']);
		}
		
		$keyforselect = 'donneesrh_userfield';
		$keyforelement = 'fa-user-plus_fa_#79633f';
		$keyforaliasextra = 'extra2';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as extra ON u.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'donneesrh_userfield_extrafields as extra2 ON u.rowid = extra2.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'usergroup_user as ug ON u.rowid = ug.fk_user';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'usergroup as g ON ug.fk_usergroup = g.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'adherent as a ON u.fk_member = a.rowid';
		$this->export_sql_end[$r] .= ' WHERE u.entity IN ('.getEntity('user').')';


		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT ONGLETDONNEESRH */
		
		$langs->load("donneesrh@donneesrh");
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'Données RH Déplacement des utilisateurs';
		$this->import_icon[$r] = 'user';
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('u'=>MAIN_DB_PREFIX.'donneesrh_Deplacement_extrafields'); // List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r] = array(
			'u.fk_object'=>"Matricule", 'u.systeme'=>"Système", 'u.datedapplicationsysteme'=>"Date d'application Système",
			'u.montantd1'=>"Indemnité D1", 'u.d_1'=>"D1", 'u.datedapplicationd1'=>"Date d'application D1",
			'u.montantd2'=>"Indemnité D2", 'u.d_2'=>"D2", 'u.datedapplicationd2'=>"Date d'application D2",
			'u.montantd3'=>"Indemnité D3", 'u.d_3'=>"D3", 'u.datedapplicationd3'=>"Date d'application D3",
			'u.montantd4'=>"Indemnité D4", 'u.d_4'=>"D4", 'u.datedapplicationd4'=>"Date d'application D4",
			'u.panier1'=>"Panier 1", 'u.datedapplicationpanier1'=>"Date d'application panier 1",
			'u.panier2'=>"Panier 2", 'u.datedapplicationpanier2'=>"Date d'application panier 2",
			'u.typegd1'=>"Type GD1", 'u.gd1'=>"GD1", 'u.distancegd1'=>"Distance GD1", 'u.heurederoutegd1'=>"Heure de route GD1", 'u.datedapplicationgd1'=>"Date d'application GD1",
			'u.typegd3'=>"Type GD3", 'u.gd3'=>"GD3", 'u.distancegd3'=>"Distance GD3", 'u.heurederoutegd3'=>"Heure de route GD3", 'u.datedapplicationgd3'=>"Date d'application GD3",
			'u.typegd4'=>"Type GD4", 'u.gd4'=>"GD4", 'u.distancegd4'=>"Distance GD4", 'u.heurederoutegd4'=>"Heure de route GD4", 'u.datedapplicationgd4'=>"Date d'application GD4",
			'u.vehicule'=>"Véhicule", 'u.datedapplicationvehicule'=>"Date d'application véhicule",
		);
		// $this->import_fieldshidden_array[$r] = array('u.tms'=>'334'); // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_examplevalues_array[$r] = array(
			'u.fk_object'=>"389", 'u.systeme'=>"NK", 'u.datedapplicationsysteme'=>"18/04/2024",
			'u.montantd1'=>"10", 'u.d_1'=>"Bron", 'u.datedapplicationd1'=>"18/04/2024",
			'u.montantd2'=>"10", 'u.d_2'=>"Bron", 'u.datedapplicationd2'=>"18/04/2024",
			'u.montantd3'=>"10", 'u.d_3'=>"Bron", 'u.datedapplicationd3'=>"18/04/2024",
			'u.montantd4'=>"10", 'u.d_4'=>"Bron", 'u.datedapplicationd4'=>"18/04/2024",
			'u.panier1'=>"Panier 1", 'u.datedapplicationpanier1'=>"18/04/2024",
			'u.panier2'=>"Panier 2", 'u.datedapplicationpanier2'=>"18/04/2024",
			'u.typegd1'=>"GRAND D", 'u.gd1'=>"CNPE TRICASTIN", 'u.distancegd1'=>"50", 'u.heurederoutegd1'=>"Heure de route GD1", 'u.datedapplicationgd1'=>"18/04/2024",
			'u.typegd3'=>"GRAND D", 'u.gd3'=>"CNPE TRICASTIN", 'u.distancegd3'=>"50", 'u.heurederoutegd3'=>"Heure de route GD3", 'u.datedapplicationgd3'=>"18/04/2024",
			'u.typegd4'=>"GRAND D", 'u.gd4'=>"CNPE TRICASTIN", 'u.distancegd4'=>"50", 'u.heurederoutegd4'=>"Heure de route GD4", 'u.datedapplicationgd4'=>"18/04/2024",
			'u.vehicule'=>"VS", 'u.datedapplicationvehicule'=>"18/04/2024",
		);
		$this->import_updatekeys_array[$r] = array('u.fk_object'=>'Matricule');
		$this->import_regex_array[$r] = array(
			//'u.datedapplicationsysteme'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
		);
		$this->import_convertvalue_array[$r] = array(
			'u.fk_object' => array('rule' => 'fetchidfromcodeunits', 'file' => '/custom/feuilledetemps/class/extendedUser.class.php', 'class' => 'ExtendedUser3', 'method' => 'fetchIdWithMatricule', 'element' => 'user'),
			'u.systeme' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatSysteme'),
			'u.datedapplicationsysteme' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.datedapplicationd1' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.datedapplicationd2' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.datedapplicationd3' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.datedapplicationd4' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.panier1' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatPanier'),
			'u.datedapplicationpanier1' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.panier2' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatPanier'),
			'u.datedapplicationpanier2' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.typegd1' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatTypeGD'),
			'u.datedapplicationgd1' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.typegd3' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatTypeGD'),
			'u.datedapplicationgd3' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.typegd4' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatTypeGD'),
			'u.datedapplicationgd4' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
			'u.vehicule' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatVehicule'),
			'u.datedapplicationvehicule' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateString'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++; 

		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'Données RH Contrat des utilisateurs';
		$this->import_icon[$r] = 'user';
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('u'=>MAIN_DB_PREFIX.'donneesrh_Positionetcoefficient_extrafields'); // List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r] = array(
			'u.fk_object'=>"Matricule", 'u.contratdetravail'=>"Contrat de travail", 'u.horairehebdomadaire'=>"Horaire hebdomadaire",
			'u.college'=>"Collège", 'u.positionetam'=>"Position", 'u.coefficientetam'=>"Coefficient",
			'u.dateembauche'=>"Date d'embauche", 'u.datedepart'=>"Date de départ",
		);
		// $this->import_fieldshidden_array[$r] = array('u.tms'=>'334'); // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_examplevalues_array[$r] = array(
			'u.fk_object'=>"389", 'u.contratdetravail'=>"CDI", 'u.horairehebdomadaire'=>"35",
			'u.college'=>"X", 'u.positionetam'=>"1.2", 'u.coefficientetam'=>"100", 
			'u.dateembauche'=>"01/09/2023", 'u.datedepart'=>"01/09/2023",
		);
		$this->import_updatekeys_array[$r] = array('u.fk_object'=>'Matricule');
		$this->import_regex_array[$r] = array(
			//'u.datedapplicationsysteme'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
		);
		$this->import_convertvalue_array[$r] = array(
			'u.fk_object' => array('rule' => 'fetchidfromcodeunits', 'file' => '/custom/feuilledetemps/class/extendedUser.class.php', 'class' => 'ExtendedUser3', 'method' => 'fetchIdWithMatricule', 'element' => 'user'),
			'u.contratdetravail' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatContratTravail'),
			'u.horairehebdomadaire' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatHoraireHebdomadaire'),
			'u.college' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatCollege'),
			'u.positionetam' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatPositionEtam'),
			'u.coefficientetam' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatCoefficientEtam'),
			'u.dateembauche' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateExcel'),
			'u.datedepart' => array('rule' => 'compute', 'file' => '/custom/donneesrh/class/userfield.class.php', 'class' => 'UserField', 'method' => 'formatDateExcel'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++; 

		/* END MODULEBUILDER IMPORT ONGLETDONNEESRH */
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

		//$result = $this->_load_tables('/install/mysql/', 'donneesrh');
		$result = $this->_load_tables('/donneesrh/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('donneesrh_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'donneesrh@donneesrh', '$conf->donneesrh->enabled');
		//$result2=$extrafields->addExtraField('donneesrh_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'donneesrh@donneesrh', '$conf->donneesrh->enabled');
		//$result3=$extrafields->addExtraField('donneesrh_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'donneesrh@donneesrh', '$conf->donneesrh->enabled');
		//$result4=$extrafields->addExtraField('donneesrh_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'donneesrh@donneesrh', '$conf->donneesrh->enabled');
		//$result5=$extrafields->addExtraField('donneesrh_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'donneesrh@donneesrh', '$conf->donneesrh->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('donneesrh');
		$myTmpObjects = array();
		$myTmpObjects['OngletDonneesRH'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'OngletDonneesRH') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_ongletdonneesrhs.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_ongletdonneesrhs.odt';

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
