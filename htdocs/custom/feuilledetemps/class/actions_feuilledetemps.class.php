<?php
/* Copyright (C) 2021 METZGER Leny <l.metzger@optim-industries.fr>
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
 * \file    fep/class/actions_fep.class.php
 * \ingroup fep
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

require_once DOL_DOCUMENT_ROOT.'/custom/feuilledetemps/class/feuilledetemps.class.php';

/**
 * Class ActionsFeuilledetemps
 */
class ActionsFeuilledetemps
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
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
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
		global $conf, $user, $langs, $db, $obj, $resql, $i;
		
		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('dictionaryadmin')) && $parameters['tabname'] == 'llx_c_holiday_types') {	    // do something only for the context 'somecontext1' or 'somecontext2'
	
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

		$langs->load("fep@fep");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'fep') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("FEP");
			$this->results['picto'] = 'fep@fep';
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

		if ($parameters['features'] == 'myobject') {
			if ($user->rights->fep->myobject->read) {
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
		global $langs, $conf, $user, $db;

		if ($parameters['object']->element != 'project' && $parameters['object']->element != 'project_task') {
			return 0;
		}

		if ($parameters['mode'] == 'remove') {
			// utilisé si on veut faire disparaitre des onglets.
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('feuilledetemps@feuilledetemps');
			// utilisé si on veut ajouter des onglets.
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			$head = $parameters['head'];
			foreach($head as $key => $onglet){
				if($parameters['object']->element == 'project'){
					if ($onglet[1] == 'Temps consommé (HS)'){
						$fdt = new Feuilledetemps($db);
						$nb_tc = $fdt->getNbTC($parameters['object']->id);
						if($nb_tc > 0)
							$head[$key][1] .= '<span class="badge marginleftonlyshort">' . $nb_tc . '</span>';
					}
					if ($onglet[1] == "Vue d'ensemble"){
						$project = $parameters['object'];
						$cachekey = 'count_elements_project_'.$project->id;
						$dataretrieved = dol_getcache($cachekey);
						if (!is_null($dataretrieved)) {
							$nbElements = $dataretrieved;
						} else {
							if (!empty($conf->propal->enabled)) {
								$nbElements += $project->getElementCount('propal', 'propal');
							}
							if (!empty($conf->commande->enabled)) {
								$nbElements += $project->getElementCount('order', 'commande');
							}
							if (!empty($conf->facture->enabled)) {
								$nbElements += $project->getElementCount('invoice', 'facture');
							}
							if (!empty($conf->facture->enabled)) {
								$nbElements += $project->getElementCount('invoice_predefined', 'facture_rec');
							}
							if (!empty($conf->supplier_proposal->enabled)) {
								$nbElements += $project->getElementCount('proposal_supplier', 'supplier_proposal');
							}
							if (!empty($conf->supplier_order->enabled)) {
								$nbElements += $project->getElementCount('order_supplier', 'commande_fournisseur');
							}
							if (!empty($conf->supplier_invoice->enabled)) {
								$nbElements += $project->getElementCount('invoice_supplier', 'facture_fourn');
							}
							if (!empty($conf->contrat->enabled)) {
								$nbElements += $project->getElementCount('contract', 'contrat');
							}
							if (!empty($conf->ficheinter->enabled)) {
								$nbElements += $project->getElementCount('intervention', 'fichinter');
							}
							if (!empty($conf->expedition->enabled)) {
								$nbElements += $project->getElementCount('shipping', 'expedition');
							}
							if (!empty($conf->mrp->enabled)) {
								$nbElements += $project->getElementCount('mrp', 'mrp_mo', 'fk_project');
							}
							if (!empty($conf->deplacement->enabled)) {
								$nbElements += $project->getElementCount('trip', 'deplacement');
							}
							if (!empty($conf->expensereport->enabled)) {
								$nbElements += $project->getElementCount('expensereport', 'expensereport');
							}
							if (!empty($conf->don->enabled)) {
								$nbElements += $project->getElementCount('donation', 'don');
							}
							if (!empty($conf->loan->enabled)) {
								$nbElements += $project->getElementCount('loan', 'loan');
							}
							if (!empty($conf->tax->enabled)) {
								$nbElements += $project->getElementCount('chargesociales', 'chargesociales');
							}
							if (!empty($conf->projet->enabled)) {
								$nbElements += $project->getElementCount('project_task', 'projet_task');
							}
							if (!empty($conf->stock->enabled)) {
								$nbElements += $project->getElementCount('stock_mouvement', 'stock');
							}
							if (!empty($conf->salaries->enabled)) {
								$nbElements += $project->getElementCount('salaries', 'payment_salary');
							}
							if (!empty($conf->banque->enabled)) {
								$nbElements += $project->getElementCount('variouspayment', 'payment_various');
							}
							dol_setcache($cachekey, $nbElements, 120);	// If setting cache fails, this is not a problem, so we do not test result.
						}
						if($nbElements > 0)
							$head[$key][1] .= '<span class="badge marginleftonlyshort">' . $nbElements . '</span>';
					}
				}
				elseif($parameters['object']->element == 'project_task'){
					if ($onglet[1] == 'Temps consommé (HS)'){
						$fdt = new Feuilledetemps($db);
						$nb_tc = $fdt->getNbTCTask($parameters['object']->id);
						if($nb_tc > 0)
							$head[$key][1] .= '<span class="badge marginleftonlyshort">' . $nb_tc . '</span>';
					}
				}
			}
			$this->results = $head;
			// return 1 to replace standard code
			return 1;
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

		if ($conf->feuilledetemps->enabled) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$board = new FeuilleDeTemps($db);
			$result = $board->load_board_approve1($user);
			if($result->nbtodo > 0) {
				$this->results['feuilledetemps_approve1'] = $result;
			}

			if($conf->global->FDT_RESP_TASKPROJECT_APPROVER) {
				$result = $board->load_board_approve2($user);
				if($result->nbtodo > 0) {
					$this->results['feuilledetemps_approve2'] = $result;
				}
			}

			if($user->rights->feuilledetemps->feuilledetemps->modify_verification) {
				$result = $board->load_board_verification($user);
				if($result->nbtodo > 0) {
					$this->results['feuilledetemps_verification'] = $result;
				}
			}
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
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
	public function addOpenElementsDashboardGroup($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;

		$error = 0; // Error counter
		$disabled = 1;

		if ($conf->feuilledetemps->enabled) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->results['feuilledetemps'] = array(
				'groupName' => 'FeuilleDeTemps',
				'globalStatsKey' => 'feuilledetemps',
				'stats' =>
					array('feuilledetemps_approve1', 'feuilledetemps_approve2', 'feuilledetemps_verification'),
			);
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


}
