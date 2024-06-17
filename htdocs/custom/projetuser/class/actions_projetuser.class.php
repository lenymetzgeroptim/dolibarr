<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    htdocs/modulebuilder/template/class/actions_mymodule.class.php
 * \ingroup mymodule
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsProjetUser
 */
class ActionsProjetUser
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
	 * Overloading the formattachOptions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formattachOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $result, $permissiontoadd, $object, $destfile;
		$langs->loadLangs(array("projetuser@projetuser"));

		$error = 0; // Error counter

		if (GETPOST('sendit', 'alpha') && !empty($conf->global->MAIN_UPLOAD_DOC) && !empty($permissiontoadd) && $result) {
			global $dolibarr_main_url_root;
			$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
			$subject = '[OPTIM Industries] Notification automatique Projet';

			if($object->table_element == 'projet') {
				$type = "le projet";
				$link = '<a href="'.$urlwithroot.'/projet/card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/projet/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
			} 
			elseif($object->table_element == 'commande') {
				$type = "la commande";
				$link = '<a href="'.$urlwithroot.'/commande/card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/commande/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
				// to exclude collaborateurs pressentis from list email
				$excludecontatcs = $object->liste_contact(-1, 'internal', 0, 'PdC', 1);
				foreach($excludecontatcs as $exclude) {
					unset($contactlist[array_search($exclude, $contactlist)]);
				}
			} 
			elseif($object->table_element == 'projet_task') {
				$type = "la tache";
				$link = '<a href="'.$urlwithroot.'/projet/tasks/task.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/projet/tasks/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
			} 
			elseif($object->table_element == 'facture') {
				$type = "la facture";
				$link = '<a href="'.$urlwithroot.'/compta/facture/card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/compta/facture/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
			} 
			elseif($object->table_element == 'fod_fod') {
				$type = "la fod";
				$link = '<a href="'.$urlwithroot.'/custom/fod/fod_card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/custom/fod/fod_document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->listIntervenantsForFod('', 0);
			} 
			elseif($object->table_element == 'propal') {
				$type = "la proposition commerciale";
				$link = '<a href="'.$urlwithroot.'/comm/propal/card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/comm/propal/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
				// to exclude collaborateurs pressentis from list email
				$excludecontatcs = $object->liste_contact(-1, 'internal', 0, 'PdC', 1);
				foreach($excludecontatcs as $exclude) {
					unset($contactlist[array_search($exclude, $contactlist)]);
				}
			} 
			elseif($object->table_element == 'supplier_proposal') {
				$type = "la proposition commerciale fournisseur";
				$link = '<a href="'.$urlwithroot.'/supplier_proposal/card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/supplier_proposal/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
			}
			elseif($object->table_element == 'commande_fournisseur') {
				$type = "la commande fournisseur";
				$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/fourn/commande/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
			}
			elseif($object->table_element == 'facture_fourn') {
				$type = "la facture fournisseur";
				$link = '<a href="'.$urlwithroot.'/fourn/facture/card.php?id='.$object->id.'">'.$object->ref.'</a>';
				$link_doc = '<a href="'.$urlwithroot.'/fourn/facture/document.php?id='.$object->id.'">'.'ici'.'</a>';
				$contactlist = $object->liste_contact(-1, 'internal', 0, '', 1);
			}
			else{
				return 0;
			}

			$user_text = $user->firstname." ".$user->lastname;
			$msg = $langs->transnoentitiesnoconv("EmailTextDocLinked", $user_text, $type, $link, $_FILES['userfile']['name'][0], $link_doc);

			$from = 'erp@optim-industries.fr';
			$to = '';
			if($object->table_element == 'fod_fod') {
				foreach($contactlist as $contact) {
					if($contact->id != $user->id && !empty($contact->email)){
						$to .= $contact->email.', ';
					}
				}
			}
			else {
				foreach($contactlist as $contact) {
					if($contact['statuscontact'] == '1' && $contact['id'] != $user->id && !empty($contact['email'])){
						$to .= $contact['email'].', ';
					}
				}
			}
			$to = rtrim($to, ', ');
			
			$mail = new CMailFile($subject, $to, $from, $msg, '', '', '', '', '', 0, 1);
			if (!empty($to)){
				$res = $mail->sendfile();
			}
			if(!$res){
				$error++;
			}
		}

		if (!$error) {
			//$this->results = array('myreturn' => 999);
			//$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			//$this->errors[] = 'Error message';
			return -1;
		}
	}

}
