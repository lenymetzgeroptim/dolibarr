<?php
/* Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    constat/class/actions_constat.class.php
 * \ingroup constat
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

 require_once DOL_DOCUMENT_ROOT.'/custom/constat/class/constat.class.php';
/**
 * Class ActionsConstat
 */
class ActionsConstat
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("ConstatMassAction").'</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}



	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0; $deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0; $deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}



	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$langs->load("constat@constat");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'constat') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("Constat");
			$this->results['picto'] = 'constat@constat';
		}

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
	}



	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	<0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'constat') {
			if ($user->hasRight('constat', 'constat', 'read')) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// used to make some tabs removed
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('constat@constat');
			// used when we want to add some tabs
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['context1', 'context2'])) {
				$datacount = 0;

				$parameters['head'][$counter][0] = dol_buildpath('/constat/constat_tab.php', 1) . '?id=' . $id . '&amp;module='.$element;
				$parameters['head'][$counter][1] = $langs->trans('ConstatTab');
				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'constatemails';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// en V14 et + $parameters['head'] est modifiable par référence
				return 0;
			}
		} else {
			// Bad value for $parameters['mode']
			return -1;
		}
	}


	/**
     * Overloading the addOpenElementsDashboardLine function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	public function addOpenElementsDashboardLine($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs, $db;
 
        $error = 0; // Error counter
        $disabled = 1;
		
 
        if ($conf->constat->enabled) {       // do something only for the context 'somecontext1' or 'somecontext2'
            $board = new Constat($db);
            $result = $board->load_board_constats($user);
            if($result->nbtodo > 0) {
                $this->results['constat'] = $result;
				
            }

			$result = $board->load_board_actions($user);
            if($result->nbtodo > 0) {
                $this->results['actions'] = $result;
				

            }

        }
		
        if (!$error) {
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }

		
    }

	public function addOpenElementsDashboardGroup($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Compteur d'erreurs


		if ($conf->constat->enabled) {
			$this->results['constat'] = array(
				'groupName' => $langs->trans('Constat et Actions'),
				'globalStatsKey' => 'constat',
				'stats' => array('constat', 'actions'),
				'icon' => 'fa fa-dol-constat', // Ajout de l'icône ici
			);
		}

		if (!$error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the showLinkedObjectBlock function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function showLinkedObjectBlock($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		}

		print '<!-- showLinkedObjectBlock -->';
		print load_fiche_titre($langs->trans('RelatedObjects'), $parameters['morehtmlright'], '', 0, 0, 'showlinkedobjectblock');


		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder allwidth" data-block="showLinkedObject" data-element="' . $object->element . '"  data-elementid="' . $object->id . '"   >';

		print '<tr class="liste_titre">';
		print '<td>' . $langs->trans("Type") . '</td>';
		print '<td>' . $langs->trans("Ref") . '</td>';
		print '<td class="center"></td>';
		print '<td class="center">' . $langs->trans("Date") . '</td>';
		print '<td class="right">' . $langs->trans("Label") . '</td>';
		print '<td class="right">' . $langs->trans("Status") . '</td>';
		print '<td></td>';
		print '</tr>';

		$nboftypesoutput = 0;

		foreach ($object->linkedObjects as $objecttype => $objects) {
			$tplpath = $element = $subelement = $objecttype;

			// to display inport button on tpl
			$showImportButton = false;
			if (!empty($parameters['compatibleImportElementsList']) && in_array($element, $parameters['compatibleImportElementsList'])) {
				$showImportButton = true;
			}

			$regs = array();
			if ($objecttype != 'supplier_proposal' && preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
				$element = $regs[1];
				$subelement = $regs[2];
				$tplpath = $element . '/' . $subelement;
			}
			$tplname = 'linkedobjectblock';

			// To work with non standard path
			if ($objecttype == 'facture') {
				$tplpath = 'compta/' . $element;
				if (!isModEnabled('facture')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'facturerec') {
				$tplpath = 'compta/facture';
				$tplname = 'linkedobjectblockForRec';
				if (!isModEnabled('facture')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'propal') {
				$tplpath = 'comm/' . $element;
				if (!isModEnabled('propal')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'supplier_proposal') {
				if (!isModEnabled('supplier_proposal')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'shipping' || $objecttype == 'shipment' || $objecttype == 'expedition') {
				$tplpath = 'expedition';
				if (!isModEnabled('expedition')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'reception') {
				$tplpath = 'reception';
				if (!isModEnabled('reception')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'delivery') {
				$tplpath = 'delivery';
				if (!isModEnabled('expedition')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'ficheinter') {
				$tplpath = 'fichinter';
				if (!isModEnabled('ficheinter')) {
					continue; // Do not show if module disabled
				}
			} elseif ($objecttype == 'invoice_supplier') {
				$tplpath = 'fourn/facture';
			} elseif ($objecttype == 'order_supplier') {
				$tplpath = 'fourn/commande';
			} elseif ($objecttype == 'expensereport') {
				$tplpath = 'expensereport';
			} elseif ($objecttype == 'subscription') {
				$tplpath = 'adherents';
			} elseif ($objecttype == 'conferenceorbooth') {
				$tplpath = 'eventorganization';
			} elseif ($objecttype == 'conferenceorboothattendee') {
				$tplpath = 'eventorganization';
			} elseif ($objecttype == 'mo') {
				$tplpath = 'mrp';
				if (!isModEnabled('mrp')) {
					continue; // Do not show if module disabled
				}
			}

			global $linkedObjectBlock;
			$linkedObjectBlock = $objects;

			// Output template part (modules that overwrite templates must declare this into descriptor)
			$dirtpls = array_merge($conf->modules_parts['tpl'], array('/' . $tplpath . '/tpl'));
			foreach ($dirtpls as $reldir) {
				if ($nboftypesoutput == ($nbofdifferenttypes - 1)) {    // No more type to show after
					global $noMoreLinkedObjectBlockAfter;
					$noMoreLinkedObjectBlockAfter = 1;
				}

				$res = @include dol_buildpath($reldir . '/' . $tplname . '.tpl.php');
				if ($res) {
					$nboftypesoutput++;
					break;
				}
			}
		}

		if (!$nboftypesoutput) {
			print '<tr><td class="impair" colspan="7"><span class="opacitymedium">' . $langs->trans("None") . '</span></td></tr>';
		}

		print '</table>';

		if (!empty($parameters['compatibleImportElementsList'])) {
			$res = @include dol_buildpath('core/tpl/objectlinked_lineimport.tpl.php');
		}

		print '</div>';

		return 1;
	}


	/**
	 * Overloading the setLinkedObjectSourceTargetType function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	// public function setLinkedObjectSourceTargetType($parameters, &$object, &$action, $hookmanager)
	// {
	// 	$this->results = array('sourcetype' => 'constat_constat', 'targettype' => 'constat_constat');
	// 	return 1; // or return 1 to replace standard code
	// }


}
