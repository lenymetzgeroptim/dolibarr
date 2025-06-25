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
 * 	\defgroup   ecmcustom     Module ECMCustom
 *  \brief      ECMCustom module descriptor.
 *
 *  \file       htdocs/ecmcustom/core/modules/modECMCustom.class.php
 *  \ingroup    ecmcustom
 *  \brief      Description and activation file for module ECMCustom
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module ECMCustom
 */
class modECMCustom extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id.
		$this->numero = 500010;

		// Family can be 'crm','financial','hr','projects','product','ecm','technic','other'
		// It is used to sort modules in module setup page
		$this->family = "ecm";
		$this->module_position = '10';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Gestion de documents (GED). Stockage automatic des documents générés ou stockés. Fonction de partage.";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (XXX is id value)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of png file (without png) used for this module
		$this->picto = 'folder-open';

		// Data directories to create when module is enabled
		$this->dirs = array("/ecmcustom/temp");

		// Config pages. Put here list of php page names stored in admmin directory used to setup module
		$this->config_page_url = array('ecm.php');

		// The language file dedicated to your module
		$this->langfiles = array("ecmcustom@ecmcustom");

		// Dependencies
		$this->depends = array(); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled

		// Constants
		$this->const = array(); // List of parameters
		$r = 0;

		$this->const[$r][0] = "ECM_AUTO_TREE_ENABLED";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = 'Auto tree is enabled by default';
		$this->const[$r][4] = 0;

		// Boxes
		$this->boxes = array(); // List of boxes
		$r = 0;

		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		// Example:
		//$this->boxes[$r][1] = "myboxa.php";
		//$r++;
		//$this->boxes[$r][1] = "myboxb.php";
		//$r++;

		// Permissions
		$this->rights_class = 'ecmcustom'; // Permission key
		$this->rights = array(); // Permission array used by this module

		$r++;
		$this->rights[$r][0] = 2501;
		$this->rights[$r][1] = 'Read or download documents';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 2504;
		$this->rights[$r][1] = 'Soumettre des documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'upload';

		$r++;
		$this->rights[$r][0] = 2505;
		$this->rights[$r][1] = 'Supprimer des documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';

		$r++;
		$this->rights[$r][0] = 2535;
		$this->rights[$r][1] = 'Lire les rapports des emails envoyés';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read_report_email';

		$r++;
		$this->rights[$r][0] = 2515;
		$this->rights[$r][1] = 'Administer directories of documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'setup';


		// Menus
		//------
		$this->menus = array(); // List of menus to add
		$r = 0;

		// Top menu
		$this->menu[$r] = array(
			'fk_menu'=>0,
			'type'=>'top',
			'titre'=>'MenuECM',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'mainmenu'=>'ecm',
			'url'=>'/custom/ecmcustom/index.php',
			'langs'=>'ecm',
			'position'=>82,
			'perms'=>'$user->rights->ecmcustom->read || $user->rights->ecmcustom->upload || $user->rights->ecmcustom->setup',
			'enabled'=>'$conf->ecmcustom->enabled',
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$r++;

		// Left menu linked to top menu
		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=ecm',
			'type'=>'left',
			'titre'=>'ECMArea',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'mainmenu'=>'ecm',
			'leftmenu'=>'ecm',
			'url'=>'/custom/ecmcustom/index.php?mainmenu=ecm&leftmenu=ecm',
			'langs'=>'ecm',
			'position'=>101,
			'perms'=>'$user->rights->ecmcustom->read || $user->rights->ecmcustom->upload',
			'enabled'=>'$user->rights->ecmcustom->read || $user->rights->ecmcustom->upload',
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$r++;

		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=ecm,fk_leftmenu=ecm',
			'type'=>'left',
			'titre'=>'ECMSectionsManual',
			'mainmenu'=>'ecm',
			'leftmenu'=>'ecm_manual',
			'url'=>'/custom/ecmcustom/index.php?action=file_manager&mainmenu=ecm&leftmenu=ecm',
			'langs'=>'ecm',
			'position'=>102,
			'perms'=>'$user->rights->ecmcustom->read || $user->rights->ecmcustom->upload',
			'enabled'=>'$user->rights->ecmcustom->read || $user->rights->ecmcustom->upload',
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$r++;

		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=ecm,fk_leftmenu=ecm',
			'type'=>'left',
			'titre'=>'ECMSectionsAuto',
			'mainmenu'=>'ecm',
			'url'=>'/custom/ecmcustom/index_auto.php?action=file_manager&mainmenu=ecm&leftmenu=ecm',
			'langs'=>'ecm',
			'position'=>103,
			'perms'=>'$user->rights->ecmcustom->read || $user->rights->ecmcustom->upload',
			'enabled'=>'($user->rights->ecmcustom->read || $user->rights->ecmcustom->upload) && ! empty($conf->global->ECM_AUTO_TREE_ENABLED)',
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$r++;
	}
}
