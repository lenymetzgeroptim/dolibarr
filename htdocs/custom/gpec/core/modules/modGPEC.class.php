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
 * 	\defgroup   gpec     Module GPEC
 *  \brief      GPEC module descriptor.
 *
 *  \file       htdocs/gpec/core/modules/modGPEC.class.php
 *  \ingroup    gpec
 *  \brief      Description and activation file for module GPEC
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module GPEC
 */
class modGPEC extends DolibarrModules
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
		$this->numero = 500011; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'gpec';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleGPECName' not found (GPEC is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleGPECDesc' not found (GPEC is name of module).
		$this->description = "GPECDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "GPECDescription";

		// Author
		$this->editor_name = 'Lény Metzger';
		$this->editor_url = '';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where GPEC is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'gpec_32@gpec';

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
				'/gpec/css/gpec.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/gpec/js/gpec.js.php',
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
		// Example: this->dirs = array("/gpec/temp","/gpec/subdir");
		$this->dirs = array("/gpec/temp");

		// Config pages. Put here list of php page, stored into gpec/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@gpec");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("gpec@gpec");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'GPECWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('GPEC_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('GPEC_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->gpec) || !isset($conf->gpec->enabled)) {
			$conf->gpec = new stdClass();
			$conf->gpec->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array(
            'data'=>'user:+GPEC:GPEC:gpec@gpec:$user->id == $object->id || $user->rights->gpec->gpec->read_all || ($user->rights->gpec->gpec->read_hierarchie && in_array($object->id, $user->getAllChildIds())):/gpec/gpec_user.php?id=__ID__'
        );
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@gpec:$user->rights->gpec->read:/gpec/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@gpec:$user->rights->othermodule->read:/gpec/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
			/*'langs'=>'gpec@gpec',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."gpec_domaines", MAIN_DB_PREFIX."gpec_competencesElementaires"),
			// Label of tables
			'tablib'=>array("Domaines d'activités", "Compétences élémentaires"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.nom, f.activite, f.exigence_cctp, f.active FROM '.MAIN_DB_PREFIX.'gpec_domaines as f', 'SELECT f.rowid as rowid, f.domaine, f.competence, f.active FROM '.MAIN_DB_PREFIX.'gpec_competencesElementaires as f'),
			// Sort order
			'tabsqlsort'=>array("nom ASC", "domaine ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("nom,activite,exigence_cctp", "domaine,competence"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("nom,activite,exigence_cctp", "domaine,competence"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("nom,activite,exigence_cctp", "domaine,competence"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid", "rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->gpec->enabled, $conf->gpec->enabled)*/
		);

		// Boxes/Widgets
		// Add here list of php file(s) stored in gpec/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'gpecwidget1.php@gpec',
			//      'note' => 'Widget provided by GPEC',
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
			//      'class' => '/gpec/class/competencedomaine_level_user.class.php',
			//      'objectname' => 'CompetenceDomaine_Level_User',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->gpec->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->gpec->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->gpec->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire l\'onglet GPEC de tous les utilisateurs'; // Permission label
		$this->rights[$r][4] = 'gpec';
		$this->rights[$r][5] = 'read_all'; // In php code, permission will be checked by test if ($user->rights->gpec->competencedomaine_level_user->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Modifier l\'onglet GPEC de tous les utilisateurs'; // Permission label
		$this->rights[$r][4] = 'gpec';
		$this->rights[$r][5] = 'update'; // In php code, permission will be checked by test if ($user->rights->gpec->competencedomaine_level_user->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Ajout/Modification/Suppression des compétences élémentaires et transversales'; // Permission label
		$this->rights[$r][4] = 'gpec';
		$this->rights[$r][5] = 'modify_competence'; // In php code, permission will be checked by test if ($user->rights->gpec->competencedomaine_level_user->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire l\'onglet GPEC de toute sa hiérarchie'; // Permission label
		$this->rights[$r][4] = 'gpec';
		$this->rights[$r][5] = 'read_hierarchie'; // In php code, permission will be checked by test if ($user->rights->gpec->competencedomaine_level_user->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Modifier l\'onglet GPEC de toute sa hiérarchie'; // Permission label
		$this->rights[$r][4] = 'gpec';
		$this->rights[$r][5] = 'update_hierarchie'; // In php code, permission will be checked by test if ($user->rights->gpec->competencedomaine_level_user->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Faire des exports des onlgets GPEC'; // Permission label
		$this->rights[$r][4] = 'gpec';
		$this->rights[$r][5] = 'export'; // In php code, permission will be checked by test if ($user->rights->gpec->competencedomaine_level_user->read)
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
			'titre'=>'ModuleGPECName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'',
			'url'=>'/gpec/gpecindex.php',
			'langs'=>'gpec@gpec', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->gpec->enabled', // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->read_all', // Use 'perms'=>'$user->rights->gpec->competencedomaine_level_user->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU COMPETENCEDOMAINE_LEVEL_USER */
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Compétences par Domaine',
			//'prefix' => img_picto('', '', 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_comp_domaine',
			'url'=>'',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'1',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec,fk_leftmenu=gpec_comp_domaine',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Thèmes',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_comp_domaine_theme',
			'url'=>'/gpec/theme_competences_domaines.php',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->modify_competence',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec,fk_leftmenu=gpec_comp_domaine',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Domaines d\'activités',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_comp_domaine_activite',
			'url'=>'/gpec/domaines_activites.php',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->modify_competence',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec,fk_leftmenu=gpec_comp_domaine',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Compétences',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_comp_domaine_comp_elem',
			'url'=>'/gpec/competences_elementaires.php',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->modify_competence',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);



		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Compétences Transverses',
			//'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_comp_transverses',
			'url'=>'',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->modify_competence',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec,fk_leftmenu=gpec_comp_transverses',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Thèmes',
			//'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_comp_transverses_theme',
			'url'=>'/gpec/theme_competences_transverses.php',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->modify_competence',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec,fk_leftmenu=gpec_comp_transverses',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Compétences',
			//'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_comp_transverses_competences',
			'url'=>'/gpec/competences_transverses.php',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->modify_competence',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);



		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=gpec',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Export',
			//'prefix' => img_picto('', '', 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'gpec',
			'leftmenu'=>'gpec_export',
			'url'=>'/gpec/export.php',
			'langs'=>'gpec@gpec',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->gpec->enabled',  // Define condition to show or hide menu entry. Use '$conf->gpec->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->gpec->gpec->export',			                // Use 'perms'=>'$user->rights->gpec->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER LEFTMENU COMPETENCEDOMAINE_LEVEL_USER */




		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT COMPETENCEDOMAINE_LEVEL_USER */
		/*
		$langs->load("gpec@gpec");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='CompetenceDomaine_Level_UserLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='competencedomaine_level_user@gpec';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'CompetenceDomaine_Level_User'; $keyforclassfile='/gpec/class/competencedomaine_level_user.class.php'; $keyforelement='competencedomaine_level_user@gpec';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'CompetenceDomaine_Level_UserLine'; $keyforclassfile='/gpec/class/competencedomaine_level_user.class.php'; $keyforelement='competencedomaine_level_userline@gpec'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='competencedomaine_level_user'; $keyforaliasextra='extra'; $keyforelement='competencedomaine_level_user@gpec';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='competencedomaine_level_userline'; $keyforaliasextra='extraline'; $keyforelement='competencedomaine_level_userline@gpec';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('competencedomaine_level_userline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'competencedomaine_level_user as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'competencedomaine_level_user_line as tl ON tl.fk_competencedomaine_level_user = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('competencedomaine_level_user').')';
		$r++; */
		/* END MODULEBUILDER EXPORT COMPETENCEDOMAINE_LEVEL_USER */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT COMPETENCEDOMAINE_LEVEL_USER */
		/*
		 $langs->load("gpec@gpec");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='CompetenceDomaine_Level_UserLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='competencedomaine_level_user@gpec';
		 $keyforclass = 'CompetenceDomaine_Level_User'; $keyforclassfile='/gpec/class/competencedomaine_level_user.class.php'; $keyforelement='competencedomaine_level_user@gpec';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='competencedomaine_level_user'; $keyforaliasextra='extra'; $keyforelement='competencedomaine_level_user@gpec';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'competencedomaine_level_user as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('competencedomaine_level_user').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT COMPETENCEDOMAINE_LEVEL_USER */
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

		//$result = $this->_load_tables('/install/mysql/tables/', 'gpec');
		$result = $this->_load_tables('/gpec/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('gpec_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'gpec@gpec', '$conf->gpec->enabled');
		//$result2=$extrafields->addExtraField('gpec_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'gpec@gpec', '$conf->gpec->enabled');
		//$result3=$extrafields->addExtraField('gpec_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'gpec@gpec', '$conf->gpec->enabled');
		//$result4=$extrafields->addExtraField('gpec_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'gpec@gpec', '$conf->gpec->enabled');
		//$result5=$extrafields->addExtraField('gpec_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'gpec@gpec', '$conf->gpec->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('gpec');
		$myTmpObjects = array();
		$myTmpObjects['CompetenceDomaine_Level_User'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'CompetenceDomaine_Level_User') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_competencedomaine_level_users.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_competencedomaine_level_users.odt';

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
