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
 * 	\defgroup   fod     Module FOD
 *  \brief      FOD module descriptor.
 *
 *  \file       htdocs/fod/core/modules/modFOD.class.php
 *  \ingroup    fod
 *  \brief      Description and activation file for module FOD
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module FOD
 */
class modFOD extends DolibarrModules
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
		$this->numero = 500000; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'fod';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleFODName' not found (FOD is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleFODDesc' not found (FOD is name of module).
		$this->description = "Gestion des fiches d'objectifs dosimétriques";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Gestion des fiches d'objectifs dosimétriques";

		// Author
		$this->editor_name = 'Lény METZGER';
		$this->editor_url = '';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where FOD is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fod_32@fod';

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
			'css' => array('/fod/css/fod.css.php'),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				   '/fod/core/js/fod.js',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				   'data' => array(
				       'searchform',
				       'cddcard',
				   ),
				   //'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/fod/temp","/fod/subdir");
		$this->dirs = array("/fod/temp");

		// Config pages. Put here list of php page, stored into fod/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@fod");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("fod@fod");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'FODWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('FOD_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('FOD_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->fod) || !isset($conf->fod->enabled)) {
			$conf->fod = new stdClass();
			$conf->fod->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array(
		    'data'=>'project:+FOD1:FOD:fod@fod:$object->array_options["options_fod"]:/custom/fod/fod_projet.php?projectid=__ID__',
		            'user:+FOD2:FOD:fod@fod:1:/custom/fod/fod_user.php?userid=__ID__',
		            'user:+liste_fod:FOD (Liste):fod@fod:$object->array_options["options_cat_med"]&&$object->array_options["options_cat_med"]!=3:/custom/fod/liste_fod_user.php?id=__ID__'
				);
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@fod:$user->rights->fod->read:/fod/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@fod:$user->rights->othermodule->read:/fod/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
			'langs'=>'fod@fod',
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
			'tabcond'=>array($conf->fod->enabled, $conf->fod->enabled, $conf->fod->enabled)
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in fod/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'fodwidget1.php@fod',
			//      'note' => 'Widget provided by FOD',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
		        0=>array(
		            'label'=>'Mail FOD J-15 Fin de validité', 
		            'jobtype'=>'method', 
		            'class'=>'/fod/class/fod.class.php',
		            'objectname'=>'Fod', 
		            'method'=>'MailFOD_15J', 
		            'parameters'=>'', 
		            'comment'=>"Permet d'envoyer des mails lorsque les FOD sont en fin de validitées dans 15 jours", 
		            'frequency'=>1, 
		            'unitfrequency'=>86400, 
		            'status'=>1, 
		            'test'=>'$conf->fod->enabled', 
		            'priority'=>50
		        ),
		        1=>array(
		            'label'=>'Mail FOD J-5 Fin de validité', 
		            'jobtype'=>'method', 
		            'class'=>'/fod/class/fod.class.php',
		            'objectname'=>'Fod', 
		            'method'=>'MailFOD_5J', 
		            'parameters'=>'', 
		            'comment'=>"Permet d'envoyer des mails lorsque les FOD sont en fin de validitées dans 5 jours", 
		            'frequency'=>1, 
		            'unitfrequency'=>86400, 
		            'status'=>1, 
		            'test'=>'$conf->fod->enabled', 
		            'priority'=>50
		        ),
		        2=>array(
		            'label'=>'Mail FOD J+1 Fin de validité + passage auto en bilan', 
		            'jobtype'=>'method', 
		            'class'=>'/fod/class/fod.class.php',
		            'objectname'=>'Fod', 
		            'method'=>'MailFODdepassement_1J', 
		            'parameters'=>'', 
		            'comment'=>"Permet d'envoyer des mails lorsque les FOD ont passé la date de fin de validité (Cela passe également automatiquement ces FOD en bilan", 
		            'frequency'=>1, 
		            'unitfrequency'=>86400, 
		            'status'=>1, 
		            'test'=>'$conf->fod->enabled', 
		            'priority'=>50
		        ),
		        3=>array(
		            'label'=>'Vérification sortie intervenant', 
		            'jobtype'=>'method', 
		            'class'=>'/fod/class/fod.class.php',
		            'objectname'=>'Fod', 
		            'method'=>'Verification_Sortie_Intervenant', 
		            'parameters'=>'', 
		            'comment'=>"Permet de mettre à jour le statut des intervenants lorsque la date de sortie est dépassée", 
		            'frequency'=>1, 
		            'unitfrequency'=>86400, 
		            'status'=>1, 
		            'test'=>'$conf->fod->enabled', 
		            'priority'=>50
		        ),
				4=>array(
		            'label'=>'Vérification dépassement CdD annuelle', 
		            'jobtype'=>'method', 
		            'class'=>'/fod/class/fod.class.php',
		            'objectname'=>'Fod', 
		            'method'=>'Verification_CdD_Annuelle', 
		            'parameters'=>'', 
		            'comment'=>"Permet de mettre à jour le statut des intervenants lorsqu'ils ont dépassé la CdD annuelle", 
		            'frequency'=>1, 
		            'unitfrequency'=>86400, 
		            'status'=>1, 
		            'test'=>'$conf->fod->enabled', 
		            'priority'=>50
		        ),
				5=>array(
		            'label'=>'Vérification dépassement CdD mensuelle', 
		            'jobtype'=>'method', 
		            'class'=>'/fod/class/fod.class.php',
		            'objectname'=>'Fod', 
		            'method'=>'Verification_CdD_Mensuelle', 
		            'parameters'=>'', 
		            'comment'=>"Permet de mettre à jour le statut des intervenants lorsqu'ils ont dépassé la CdD mensuelle", 
		            'frequency'=>1, 
		            'unitfrequency'=>86400, 
		            'status'=>1, 
		            'test'=>'$conf->fod->enabled', 
		            'priority'=>50
		        ),
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/fod/class/fod.class.php',
			//      'objectname' => 'Fod',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->fod->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->fod->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->fod->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		// Droits sur les FOD
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = "Permet l'acces et le téléchargement des fichier lier au FOD"; // Permission label
		$this->rights[$r][4] = 'lire'; // In php code, permission will be checked by test if ($user->rights->fod->lire)
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire toutes les FOD'; // Permission label
		$this->rights[$r][4] = 'fod';
		$this->rights[$r][5] = 'readAll'; // In php code, permission will be checked by test if ($user->rights->fod->fod->readAll)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer une FOD (en dehors du module projet)'; // Permission label
		$this->rights[$r][4] = 'fod';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->fod->fod->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer toutes les FOD'; // Permission label
		$this->rights[$r][4] = 'fod';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->fod->fod->delete)
		$r++;
		
		// Droits sur les CdC
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire les contraintes de dose annuelle'; // Permission label
		$this->rights[$r][4] = 'cdd';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->fod->cdd->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer/Modifier les contraintes de dose annuelle'; // Permission label
		$this->rights[$r][4] = 'cdd';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->fod->cdd->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer une contrainte de dose annuelle'; // Permission label
		$this->rights[$r][4] = 'cdd';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->fod->cdd->delete)
		$r++;
		
		// Droits sur les données des intervenants 
		/*$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = "Créer des données d'intervenant sur n'importe qu'elle FOD"; // Permission label
		$this->rights[$r][4] = 'data_intervenant';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->fod->data_intervenant->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = "Supprimer des données d'intervenant sur n'importe qu'elle FOD"; // Permission label
		$this->rights[$r][4] = 'data_intervenant';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->fod->data_intervenant->delete)
		$r++;*/
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = "Lire toutes les données des intervenants (PCR)"; // Permission label
		$this->rights[$r][4] = 'data_intervenant';
		$this->rights[$r][5] = 'readAll'; // In php code, permission will be checked by test if ($user->rights->fod->data_intervenant->readAll)
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
			'titre'=>'ModuleFODName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'fod',
			'leftmenu'=>'',
			'url'=>'/fod/fodindex.php',
			'langs'=>'fod@fod', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->fod->enabled', // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
			'perms'=>'', 
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER TOPMENU */
		
		/* BEGIN MODULEBUILDER LEFTMENU FOD */
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Top menu entry
			'titre'=>'Saisie dose op, ...',
			//'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'fod',
			'leftmenu'=>'intervenant',
			'url'=>'/fod/fod_user.php?userid=_ID_',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
			'perms'=>'',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		
		
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Top menu entry
			'titre'=>'Fod',
			//'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'fod',
			'leftmenu'=>'fod',
			'url'=>'/fod/fodindex.php',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
			'perms'=>'',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod,fk_leftmenu=fod',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'List_Fod',
			'mainmenu'=>'fod',
			'leftmenu'=>'fod_fod_list',
			'url'=>'/fod/fod_list.php',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod,fk_leftmenu=fod',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New_Fod',
			'mainmenu'=>'fod',
			'leftmenu'=>'fod_fod_new',
			'url'=>'/fod/fod_card.php?action=create',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->fod->fod->write',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		
    	$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Top menu entry
			'titre'=>'CdD',
			//'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'fod',
			'leftmenu'=>'CdD',
			'url'=>'/fod/cdd_list.php',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->fod->cdd->read',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod,fk_leftmenu=CdD',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New_CdD',
			'mainmenu'=>'fod',
			'leftmenu'=>'cdd_new',
			'url'=>'/fod/cdd_card.php?action=create',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->fod->cdd->write',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
    
        $this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Top menu entry
			'titre'=>'List_Data',
			//'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'fod',
			'leftmenu'=>'Data',
			'url'=>'/fod/data_intervenant_list.php',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled.
			'perms'=>'',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=fod,fk_leftmenu=Data',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'Suivi entrées intervenants',
			'mainmenu'=>'fod',
			'leftmenu'=>'bilan_fod_user',
			'url'=>'/fod/bilan_fod_user.php',
			'langs'=>'fod@fod',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->fod->enabled',  // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->fod->data_intervenant->readAll',			                // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);

        /*$this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=fod',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'List Fod',
            'mainmenu'=>'fod',
            'leftmenu'=>'fod_fod',
            'url'=>'/fod/fod_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'fod@fod',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->fod->enabled',
            // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=fod,fk_leftmenu=fod_fod',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'New Fod',
            'mainmenu'=>'fod',
            'leftmenu'=>'fod_fod',
            'url'=>'/fod/fod_card.php?action=create',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'fod@fod',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->fod->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->fod->enabled',
            // Use 'perms'=>'$user->rights->fod->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );*/

		/* END MODULEBUILDER LEFTMENU FOD */


		// Exports profiles provided by this module
		$r = 1;
		$langs->load("fod@fod");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Toutes les données";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='fod_16@fod';
		$this->export_fields_array[$r] = array(
			't.rowid' => 'Id', 't.date' => 'Date', 'f.ref' => 'Réference FOD', 'u.firstname' => 'Prénom utilisateur', 'u.lastname' => 'Nom utilisateur', 
			't.dose' => 'Dose', 't.description' => 'Commentaire', 
			"CASE t.niv_contamination WHEN 1 THEN 'NC0' WHEN 2 THEN 'NC1' WHEN 3 THEN 'NC2' WHEN 4 THEN 'NC3' ELSE '/' END as t_niv_contamination" => 'Niveau de contamination', 
			"CASE t.portique WHEN 1 THEN 0 WHEN 2 THEN 1 WHEN 3 THEN 2 WHEN 4 THEN 3 ELSE '/' END as t_portique" => 'Déclenchement portique'
		);
		$this->export_TypeFields_array[$r] = array(
			't.rowid' => 'Numeric', 't.date' => 'Date', 'f.ref' => 'Text', 'u.firstname' => 'Text', 'u.lastname' => 'Text', 
			't.dose' => 'Numeric', 't.description' => 'Text', 
			"t_niv_contamination" => 'Text', 
			"t_portique" => 'Text'
		);
		$this->export_entities_array[$r] = array(
			't.rowid' => 'fod_16@fod', 't.date' => 'fod_16@fod', 'f.ref' => 'fod_16@fod', 'u.firstname' => 'user', 'u.lastname' => 'user', 
			't.dose' => 'fod_16@fod', 't.description' => 'fod_16@fod', 
			"t_niv_contamination" => 'fod_16@fod', 
			"t_portique" => 'fod_16@fod'
		);
		//$this->export_dependencies_array[$r] = array('fodline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'test');
		//$this->export_examplevalues_array[$r] = array('t.date'=>'Example');
		//$this->export_help_array[$r] = array('t.date'=>'FieldDescHelp');
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] .= ' FROM '.MAIN_DB_PREFIX.'fod_data_intervenant as t';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'fod_fod as f ON f.rowid = t.fk_fod';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = t.fk_user';
		$this->export_sql_end[$r] .= ' WHERE 1 = 1';
		$r++; 






		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Toutes les données par Utilisateur";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='fod_16@fod';
		$this->export_fields_array[$r] = array(
			't.date' => 'Date (Filtre)', 'u.firstname' => 'Prénom utilisateur', 'u.lastname' => 'Nom utilisateur', 'sum(t.dose) as dose' => 'Dose totale'
		);
		$this->export_TypeFields_array[$r] = array(
			't.date' => 'Date', 'u.firstname' => 'Text', 'u.lastname' => 'Text', 'dose' => 'Numeric'
		);
		$this->export_entities_array[$r] = array(
			't.date' => 'fod_16@fod', 'u.firstname' => 'user', 'u.lastname' => 'user', 'dose' => 'fod_16@fod'
		);
		//$this->export_dependencies_array[$r] = array('fodline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.date'=>'Example');
		//$this->export_help_array[$r] = array('t.date'=>'FieldDescHelp');
		$this->export_sql_start[$r] ='SELECT DISTINCT ';
		$this->export_sql_end[$r] .= ' FROM '.MAIN_DB_PREFIX.'fod_data_intervenant as t';
		//$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'fod_fod as f ON f.rowid = t.fk_fod';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = t.fk_user';
		$this->export_sql_end[$r] .= ' WHERE 1 = 1';
		$this->export_sql_order[$r] .= ' GROUP BY t.fk_user ORDER BY t.fk_user ASC';
		$r++;






		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Nombre de FOD par Utilisateur";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='fod_16@fod';
		$this->export_fields_array[$r] = array(
			'u.firstname' => 'Prénom utilisateur', 'u.lastname' => 'Nom utilisateur', 'fu.date_entree' => "Date (Filtre)", 'COUNT(t.rowid) as nb_fod' => 'Nombre de FOD', "GROUP_CONCAT(t.ref SEPARATOR ', ') as ref_fod" => "Référence des FOD"
			, "GROUP_CONCAT(fu.date_entree SEPARATOR ', ') as dates" => "Dates d'entrées de l'intervenant"
		);
		$this->export_TypeFields_array[$r] = array(
			'u.firstname' => 'Text', 'u.lastname' => 'Text', 'fu.date_entree' => 'Date', 'nb_fod' => 'Numeric', "ref_fod" => "Text"
			, "dates" => "Date"
		);
		$this->export_entities_array[$r] = array(
			'u.firstname' => 'user', 'u.lastname' => 'user', 'fu.date_entree' => 'fod_16@fod', 'nb_fod' => 'fod_16@fod', "ref_fod" => "fod_16@fod"
			, "dates" => "fod_16@fod"
		);
		//$this->export_dependencies_array[$r] = array('fodline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.date'=>'Example');
		//$this->export_help_array[$r] = array('t.date'=>'FieldDescHelp');
		$this->export_sql_start[$r] ='SELECT DISTINCT ';
		$this->export_sql_end[$r] .= ", GROUP_CONCAT(fu.date_entree SEPARATOR ', ') as date_entree";
		$this->export_sql_end[$r] .= ' FROM '.MAIN_DB_PREFIX.'fod_fod as t';
		$this->export_sql_end[$r] .= ' RIGHT JOIN '.MAIN_DB_PREFIX.'fod_user as fu ON fu.fk_fod = t.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = fu.fk_user';
		$this->export_sql_end[$r] .= ' WHERE 1 = 1';
		$this->export_sql_order[$r] .= ' GROUP BY u.firstname, u.lastname';
		$r++;




		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Bilan des FOD";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='fod_16@fod';
		$this->export_fields_array[$r] = array(
			't.rowid' => 'Id', 't.ref' => 'Référence', 't.indice' => 'Indice', 'p.title' => 'Projet', 
			't.installation' => 'Installation', 't.etat_installation' => 'Etat installation', 't.activite' => 'Activité',
			't.date_debut' => 'Date Début', 't.date_fin' => 'Date Fin', 
			"CASE t.status WHEN 14 THEN 'CLOTURE' ELSE 'BILAN' END as statut" => "Statut",
			"t.com_rex_rsr" => 'REX RSR', 
			"t.com_rex_ra" => 'REX RAF', 
			"t.com_rex_pcr" => 'REX PCR', 
			"t.com_rex_rd" => 'REX RD', 
			"GROUP_CONCAT(fu.com_rex SEPARATOR ' / ') as rex_intervenants" => "REX Intervenants",
			"CASE t.q1_doses_individuelles WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non : à analyser' ELSE '/' END as q1_doses_individuelles" => 'Q1 Statut des doses individuelles', 
			"CASE t.q2_doses_individuelles WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non (NC)' ELSE '/' END as q1_doses_individuelles" => 'Q2 Statut des doses individuelles', 
			"CASE t.q3_doses_individuelles WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non (NC)' WHEN 3 THEN 'SO' ELSE '/' END as q1_doses_individuelles" => 'Q3 Statut des doses individuelles',
			"CASE t.q1_dose_collective WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non (NC)' ELSE '/' END as q1_dose_collective" => 'Q1 Statut de la dose collective', 
			"CASE t.q2_dose_collective WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non (NC)' WHEN 3 THEN 'SO' ELSE '/' END as q2_dose_collective" => 'Q2 Statut de la dose collective', 
			"CASE t.q1_contamination WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non (NC)' ELSE '/' END as q1_contamination" => 'Q1 Contamination', 
			"CASE t.q2_contamination WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non (NC)' WHEN 3 THEN 'SO' ELSE '/' END as q2_contamination" => 'Q2 Contamination', 
			"CASE t.q1_siseri WHEN 1 THEN 'Oui (C)' WHEN 2 THEN 'Non (NC)' ELSE '/' END as q1_siseri" => 'Q1 Siseri', 
			"CASE t.q1_radiopotection WHEN 1 THEN 'Oui' WHEN 2 THEN 'Non' ELSE '/' END as q1_radioprotection" => 'Q1 Radioprotection'
		);
		$this->export_TypeFields_array[$r] = array(
			't.rowid' => 'Numeric', 't.ref' => 'Text', 't.indice' => 'Indice', 'p.title' => 'Text',  
			't.installation' => 'Text', 't.etat_installation' => 'Text', 't.activite' => 'Text',
			't.date_debut' => 'Date', 't.date_fin' => 'Date',
			'statut' => 'Text',
			't.com_rex_rsr' => 'Text',
			't.com_rex_ra' => 'Text',
			't.com_rex_pcr' => 'Text',
			't.com_rex_rd' => 'Text',
			'rex_intervenants' => 'Text',
			"q1_doses_individuelles" => 'Text', 
			'q2_doses_individuelles' => 'Text', 
			'q3_doses_individuelles' => 'Text',
			'q1_dose_collective' => 'Text',
			'q2_dose_collective' => 'Text',
			'q1_contamination' => 'Text',
			'q2_contamination' => 'Text',
			'q1_siseri' => 'Text',
			'q1_radioprotection' => 'Text'
		);
		$this->export_entities_array[$r] = array(
			't.rowid' => 'fod_16@fod', 't.ref' => 'fod_16@fod', 't.indice' => 'fod_16@fod', 'p.title' => 'project', 
			't.installation' => 'fod_16@fod', 't.etat_installation' => 'fod_16@fod', 't.activite' => 'fod_16@fod',
			't.date_debut' => 'fod_16@fod', 't.date_fin' => 'fod_16@fod',
			'statut' => 'fod_16@fod',
			't.com_rex_rsr' => 'fod_16@fod',
			't.com_rex_ra' => 'fod_16@fod',
			't.com_rex_pcr' => 'fod_16@fod',
			't.com_rex_rd' => 'fod_16@fod',
			'rex_intervenants' => 'fod_16@fod',
			"q1_doses_individuelles" => 'fod_16@fod', 
			'q2_doses_individuelles' => 'fod_16@fod', 
			'q3_doses_individuelles' => 'fod_16@fod',
			'q1_dose_collective' => 'fod_16@fod',
			'q2_dose_collective' => 'fod_16@fod',
			'q1_contamination' => 'fod_16@fod',
			'q2_contamination' => 'fod_16@fod',
			'q1_siseri' => 'fod_16@fod',
			'q1_radioprotection' => 'fod_16@fod'
		);
		//$this->export_dependencies_array[$r] = array('fodline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.rowid'=>'test');
		//$this->export_examplevalues_array[$r] = array('t.q1_doses_individuelles'=>'Example');
		//$this->export_help_array[$r] = array('t.date'=>'FieldDescHelp');
		$this->export_sql_start[$r] ='SELECT DISTINCT ';
		$this->export_sql_end[$r] .= ' FROM '.MAIN_DB_PREFIX.'fod_fod as t';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'projet as p ON p.rowid = t.fk_project';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'fod_user as fu ON fu.fk_fod = t.rowid';
		$this->export_sql_end[$r] .= ' WHERE 1 = 1 AND t.status > 7 AND t.status <> 13';
		$this->export_sql_order[$r] .= ' GROUP BY t.rowid ORDER BY t.rowid ASC';
		$r++;




		// Imports
		/*$r = 0;

		// Import list of users attributes
		$r++;
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'Congés';
		$this->import_icon[$r] = 'holiday';
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('h'=>MAIN_DB_PREFIX.'holiday_users'); // List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r] = array(
			'h.fk_user'=>"Matricule*", 'h.nb_holiday'=>"Jour de congés*"
		);

		$this->import_fieldshidden_array[$r] = array('h.fk_type'=>'const-1'); // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)

		$this->import_convertvalue_array[$r] = array(
			'h.fk_user'=>array('rule'=>'fetchidfromcodeunits', 'classfile'=>'/custom/fod/class/extendeduser.class.php', 'class'=>'Extendeduser', 'method'=>'fetch'),
			//'h.fk_user'=>array('rule'=>'numeric'),
			'h.nb_holiday'=>array('rule'=>'numeric')
		);

		$this->import_regex_array[$r] = array(
			//'h.fk_user'=>'^[0|9][0|9][0|9]',
		);

		$this->import_examplevalues_array[$r] = array(
			'h.fk_user'=>"001", 'h.nb_holiday'=>"17"
		);
		$this->import_updatekeys_array[$r] = array('h.fk_user'=>'Matricule');*/
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

		$result = $this->_load_tables('/fod/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('fod_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'fod@fod', '$conf->fod->enabled');
		//$result2=$extrafields->addExtraField('fod_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'fod@fod', '$conf->fod->enabled');
		//$result3=$extrafields->addExtraField('fod_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'fod@fod', '$conf->fod->enabled');
		//$result4=$extrafields->addExtraField('fod_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'fod@fod', '$conf->fod->enabled');
		//$result5=$extrafields->addExtraField('fod_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'fod@fod', '$conf->fod->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = 'fod';
		$myTmpObjects = array();
		$myTmpObjects['Fod'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'Fod') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/fod/template_fods.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/fod';
				$dest = $dirodt.'/template_fods.odt';

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
		
        //$sql = array("INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('historique_fod', 'fod', $conf->entity)");
        
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
