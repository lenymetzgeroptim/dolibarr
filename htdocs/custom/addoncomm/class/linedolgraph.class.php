<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/commande.class.php
 * \ingroup     addoncomm
 * \brief       This file is a CRUD class file for Commande (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/commonorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

/**
 *  Class to show commercial data for line graph
 */
class LineDolGraph extends CommonObject
{	
	// filds to join (filter) data in sql string
	public $sumfields = array(
		'commande' => array(
		'total' => 'SUM(g.total_commande)',
		'id' => 'g.fk_commande',
		'date_livraison' => 'g.c_date_livraison',
		'fk_projet' => 'g.fk_c_projet',
		),
		'commande_fournisseur' => array(
			'total' => 'SUM(g.total_supplier_c)',
			'id' => 'g.fk_supplier_c',
			'fk_projet' => 'g.fk_sc_projet',
		),
		'propal_open' => array(
			'total' => 'SUM(g.total_propal_open)',
			'id' => 'g.fk_propal_open',
			'fk_projet' => 'g.fk_po_projet',
		),
		'propal_signed' => array(
			'total' => 'SUM(g.total_propal_signed)',
			'id' => 'g.fk_propal_signed',
			'fk_projet' => 'g.fk_ps_projet',
		),
		'facture' => array(
			'total' => 'SUM(g.total_facture)',
			'id' => 'g.fk_facture',
			'fk_projet' => 'g.fk_f_projet',
		),
		'facture_pv' => array(
			'total' => 'SUM(g.total_facture_pv)',
			'id' => 'g.fk_facture_pv',
			'fk_projet' => 'g.fk_fp_projet',
		),
		'facture_draft' => array(
			'total' => 'SUM(g.total_facture_draft)',
			'id' => 'g.fk_facture_draft',
			'fk_projet' => 'g.fk_fd_projet',
		),
		'facture_fournisseur' => array(
			'total' => 'SUM(g.total_facturefourn)',
			'id' => 'g.fk_facturefourn',
			'fk_projet' => 'g.fk_ff_projet',
		),
	);

	public function comm_diagram()
	{
		$this->deleteElement();
		// $this->fetchCommande();
		$this->fetchCommandeWithFilter();
		$this->fetchCFournisseur();
		$this->fetchPropalSigned();
		$this->fetchPropalOpen();
		$this->fetchFacture();
		$this->fetchFacturePv();
		$this->fetchFactureDraft();
		$this->fetchFactureFourn();
		$this->fetchUsers();
		$this->updateOrdersContacts();
		$this->updatePropalContacts();
		$this->updatingEtpCommande();
		$this->updatingEtpPropal();
		//$this->fetch_expense();
		return 0;
	}

	/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int			$id       		Id of object to load
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetchCommandeWithFilter()
	{
		global $conf, $db, $langs, $user;

		$now = dol_now();

		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "commande as c";
		

		dol_syslog(get_class($this)."::fetchCommande", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$commande = new Commande($db);
				$commande->fetch($obj->rowid);
				$date_demarage = intval($commande->array_options['options_date_start']);

				if($date_demarage > 0) {
					$date_demarage = new DateTimeImmutable(dol_print_date($date_demarage, '%Y-%m-%d'));
				}
		
				$delivery_date = $obj->date_livraison;
				if($delivery_date > 0) {
					$delivery_date = new DateTimeImmutable(dol_print_date($obj->date_livraison, '%Y-%m-%d'));
				}
				
				if($date_demarage != null && $delivery_date != null) {
					$this->insertGroupedCommande($obj->rowid, $date_demarage, $delivery_date, $obj->total_ht, $obj->fk_projet, $obj->date_livraison);
					
				}
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 *	Get object from database. Get also lines. (depricated)
	 *
	 *	@param      int			$id       		Id of object to load
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetchCommande()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
		//$sql .= " WHERE c.fk_statut = 1";

		dol_syslog(get_class($this)."::fetchCommande", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$commande = new Commande($db);
				$commande->fetch($obj->rowid);
				$date_demarage = intval($commande->array_options['options_date_start']);

				if($date_demarage > 0) {
					$date_demarage = new DateTimeImmutable(dol_print_date($date_demarage, '%Y-%m-%d'));
				}
		
				$delivery_date = $obj->date_livraison;
				if($delivery_date > 0) {
					$delivery_date = new DateTimeImmutable(dol_print_date($obj->date_livraison, '%Y-%m-%d'));
				}
		
				if($date_demarage != null && $delivery_date != null) {
					$this->insertGroupedCommande($obj->rowid, $date_demarage, $delivery_date, $obj->total_ht, $obj->fk_projet);
				}
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int			$id       		Id of object to load
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetchUsers()
	{
		global $conf, $db, $langs, $user;
		$now = dol_now();

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

		dol_syslog(get_class($this)."::fetchUsers", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$employee = new User($db);
				$employee->fetch($obj->rowid);

				$date_entree = $obj->dateemployment;
				if($date_entree > 0) {
					$date_entree = new DateTimeImmutable(dol_print_date($obj->dateemployment, '%Y-%m-%d'));
				}

				$date_sortie = intval($employee->array_options['options_d_sortie']);

				if($date_sortie > 0) {
					$date_sortie = new DateTimeImmutable(dol_print_date($date_sortie, '%Y-%m-%d'));
				}else{
					$date_sortie = new DateTimeImmutable(dol_print_date($now, '%Y-%m-%d'));
				}
		
				if($date_entree != null && $date_sortie != null) {
					$this->insertGroupedUser($obj->rowid, $date_entree, $date_sortie, 1);
				}
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}
	
	/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int			$id       		Id of object to load
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetchCFournisseur()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT cf.rowid, cf.total_ht, cf.date_approve, cf.date_livraison as delivery_date";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
		//$sql .= " WHERE cf.fk_statut = 2";

		dol_syslog(get_class($this)."::fetchCFournisseur", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$commande = new CommandeFournisseur($db);
				$commande->fetch($obj->rowid);
				
				// $date_demarage = $obj->date_approve;
				// if($date_demarage > 0) {
				// 	$date_demarage = new DateTimeImmutable(dol_print_date($approved_date, '%Y-%m-%d'));
				// }
				$date_demarage = intval($commande->array_options['options_date_start']);
				if($date_demarage > 0) {
				$date_demarage = new DateTimeImmutable(dol_print_date($date_demarage, '%Y-%m-%d'));
				}
				
				$delivery_date = $obj->delivery_date;
				if($delivery_date > 0) {
					$delivery_date = new DateTimeImmutable(dol_print_date($delivery_date, '%Y-%m-%d'));
				}
				
				if($date_demarage != null && $delivery_date != null) {
					$this->insertGroupedCFournisseur($obj->rowid, $date_demarage, $delivery_date, $obj->total_ht, $obj->fk_projet);
				}
				
				
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int			$id       		Id of object to load
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetchPropalSigned()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT p.rowid, p.entity, p.fk_statut, p.total_ht, p.datep as date_propal, p.date_livraison";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafildes as pe on p.rowid = pe.fk_object";
		
		$sql .= " WHERE p.fk_statut = 2";
		

		dol_syslog(get_class($this)."::fetch_opropal", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$srcObject = new Propal($db);
				$srcObject->fetch($obj->rowid);
				$srcObject->fetchObjectLinked();
				if(empty($srcObject->linkedObjectsIds['commande'])) {
					//$date_propal = $obj->date_propal;
					
					$date_propal = intval($srcObject->array_options['options_datedmarrage']);
					//var_dump($date_propal);
					if($date_propal > 0) {
						$date_propal = new DateTimeImmutable(dol_print_date($date_propal, '%Y-%m-%d'));
					}
				
			
					$date_livraison = $obj->date_livraison;
					if($date_livraison > 0) {
						$date_livraison = new DateTimeImmutable(dol_print_date($date_livraison, '%Y-%m-%d'));
					}
					
					if($date_propal != null && $date_livraison != null) {
						$this->insertGroupedPropalSigned($obj->rowid, $date_propal, $date_livraison, $obj->total_ht, $obj->fk_projet);
					}
				}	
				
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int			$id       		Id of object to load
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetchPropalOpen()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT p.rowid, p.entity, p.fk_statut, p.total_ht, p.datep as date_propal, p.date_livraison";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafildes as pe on p.rowid = pe.fk_object";
		$sql .= " WHERE p.fk_statut = 1";
		

		dol_syslog(get_class($this)."::fetch_opropal", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				
				$srcObject = new Propal($db);
				$srcObject->fetch($obj->rowid);
				$srcObject->fetchObjectLinked();
				if(empty($srcObject->linkedObjectsIds['commande'])) {
					$date_propal = intval($srcObject->array_options['options_datedmarrage']);
					//$date_propal = $obj->date_propal;
					if($date_propal > 0) {
						$date_propal = new DateTimeImmutable(dol_print_date($date_propal, '%Y-%m-%d'));
					}
				

				
					$date_livraison = $obj->date_livraison;
					if($date_livraison > 0) {
						$date_livraison = new DateTimeImmutable(dol_print_date($date_livraison, '%Y-%m-%d'));
					}

					
					
					if($date_propal != null && $date_livraison != null) {
						$this->insertGroupedPropalOpen($obj->rowid, $date_propal, $date_livraison, $obj->total_ht, $obj->fk_projet);
					}
				}

				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * Get facture from database. Get also lines.
	 * 
	 */
	public function fetchFacture()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT f.rowid, f.datef, SUM(f.total_ht) as amount_ht, f.fk_projet";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.fk_soc = s.rowid";
		//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = ef.fk_object";
		// $sql .= " AND f.fk_statut IN (1,2)";
		// if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
		// 	$sql .= " AND f.type IN (0,1,2,5)";
		// } else {
		// 	$sql .= " AND f.type IN (0,1,2,3,5)";
		// }

		$sql.= " GROUP BY DATE_FORMAT(f.datef,'%Y-%m')";
		

		dol_syslog(get_class($this)."::fetchFacture", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				var_dump($obj);
				$obj->datef > 0 ? $facturation_date = $obj->datef : $facturation_date = null;
				// $obj->sumtotal_ht > 0 ? $obj->sumtotal_ht = $obj->sumtotal_ht : $obj->sumtotal_ht = null;
				
				$this->insertGroupedFacture($obj->rowid, $facturation_date, $obj->amount_ht, $obj->fk_projet);
				
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * Get facture from database. Get also lines.
	 * 
	 */
	public function fetchFacturePv()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT f.rowid, f.datef, SUM(f.total_ht) as total_ht, f.fk_projet";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = fe.fk_object";

		$sql .= " WHERE fe.pv_reception = 1";
		$sql .= " AND f.fk_statut = 0";
		$sql.= " GROUP BY DATE_FORMAT(f.datef, '%b %Y')";
		

		dol_syslog(get_class($this)."::fetchFacture", LOG_DEBUG);
		$resql = $db->query($sql);
	
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$obj->datef > 0 ? $facturation_date = $obj->datef : $facturation_date = null;
				$obj->total_ht > 0 ? $obj->total_ht = $obj->total_ht : $obj->total_ht = null;
				
				$this->insertGroupedFacturePv($obj->rowid, $facturation_date, $obj->total_ht, $obj->fk_projet);
				
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * Get facture from database. 
	 * 
	 */
	public function fetchFactureDraft()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT f.rowid, f.datef, SUM(f.total_ht) as total_ht, f.fk_projet";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = fe.fk_object";
		$sql .= " WHERE f.fk_statut = 0";
		$sql .= " AND (fe.pv_reception = '0' OR fe.pv_reception IS NULL)";

		$sql.= " GROUP BY DATE_FORMAT(f.datef, '%b %Y')";
		

		dol_syslog(get_class($this)."::fetchFactureDraft", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$obj->datef > 0 ? $facturation_date = $obj->datef : $facturation_date = null;
				// $obj->total_ht = $obj->total_ht;
				
				$this->insertGroupedFactureDraft($obj->rowid, $facturation_date, $obj->total_ht, $obj->fk_projet);
				
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * Get facture fournisseur
	 */
	public function fetchFactureFourn()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT faf.rowid, faf.datef, SUM(faf.total_ht) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as faf";
		$sql .= " WHERE faf.fk_statut IN (1,2)";
		if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
			$sql .= " AND faf.type IN (0,1,2,5)";
		} else {
			$sql .= " AND faf.type IN (0,1,2,3,5)";
		}
		 $sql .= " AND faf.entity IN (".getEntity('invoice').")";
		$sql.= " GROUP BY date_format(faf.datef,'%Y-%m')";
	

		dol_syslog(get_class($this)."::fetch_factureFourn", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
			// var_dump($obj);
				$obj->datef > 0 ? $facturation_date = $obj->datef : $facturation_date = null;
				// $obj->total_ht > 0 ? $obj->total_ht = $obj->total_ht : $obj->total_ht = null;
				
				$faturation_date = new DateTimeImmutable(dol_print_date($faturation_date, '%Y-%m-00 00:00:00'));
				$this->insertGroupedFactureFourn($obj->rowid, $facturation_date, $obj->amount, $obj->fk_projet);
				
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * Get contacts of orders
	 */
	public function updateOrdersContacts()
	{
		global $db;
		$error = 0;
	
		$comms = array();
		$sql = "SELECT ec.rowid, count(ec.fk_socpeople) as nbcontact, ec.element_id, c.ref, DATE_FORMAT(c.date_valid,'%Y-%m-%d') as date_start, DATE_FORMAT(c.date_livraison ,'%Y-%m-%d') as date_end,";
		$sql .= " t.lastname, t.firstname, c.total_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c on ec.element_id = c.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'commande'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PdC'";
		$sql .= " AND t.statut = '1'";
		
		$sql .= " GROUP BY ec.element_id";
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$this->updateNbContactCommande($obj->element_id, $obj->nbcontact);
					
				}
				$i++;
			}
			$db->free($resql);
			return 1;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * @param array of users
	 * @param date start
	 * @param date end
	 * 
	 * @return array of object propals
	 * 
	 */
	public function updatePropalContacts()
	{
		global $db;
		$error = 0;
		$propals = array();
		$sql = "SELECT ec.rowid, count(ec.fk_socpeople) as nbcontact, p.fk_projet, ec.element_id, p.ref, p.datep as date_start, DATE_FORMAT(p.date_livraison ,'%Y-%m-%d') as date_end,";
		$sql .= " t.lastname, t.firstname";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p on ec.element_id = p.rowid";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields as pe on pe.fk_object = p.rowid";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = 'propal'";
		$sql .= " AND tc.source = 'internal'";
		$sql .= " AND tc.active = 1";
		$sql .= " AND tc.code = 'PdC'";
		$sql .= " AND t.statut = '1'";
		// $sql .= " AND p.fk_statut = 2";
		$sql .= " GROUP BY ec.element_id";
		$sql .= " ORDER BY t.lastname ASC";
		
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
			
				if ($obj) {
					$this->updateNbContactPropal($obj->element_id, $obj->nbcontact);
				}
				$i++;
			}
			$db->free($resql);
			return $propals;
		} else {
			dol_print_error($db);
		}
	}

	/**
	 *	Get etp options from commande.
	 *
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function updatingEtpCommande()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";

		dol_syslog(get_class($this)."::updateEtpCommande", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$commande = new Commande($db);
				$commande->fetch($obj->rowid);
			
				$this->updateEtpCommande($commande->id, $commande->array_options['options_pdc_etp_cde']);
				
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 *	Get etp options from commande.
	 *
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function updatingEtpPropal()
	{
		global $conf, $db, $langs, $user;

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		// $sql .= " WHERE p.fk_statut = 2";

		dol_syslog(get_class($this)."::updatingEtpPropal", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$propal = new Propal($db);
				$propal->fetch($obj->rowid);
				
				$this->updateEtpPropal($propal->id, $propal->array_options['options_pdc_etp_devis']);
				
			
				$i++;
			} 
			return 1;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * Update nb etp of order in reconstructing data for diagram
	 *
	 * @param int $id_commande
	 * @param int $nb contact
	 * 
	 * @return int if KO, Id of line if OK
	 */
	public function updateEtpCommande($id_commande, $etp) 
	{
		global $conf, $db, $langs, $user;
		$sql = "UPDATE ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " SET";
		$sql .= " expect_etp_commande =".((int) $etp);
		$sql .= " WHERE fk_commande = ".((int) $id_commande);

		dol_syslog(get_class($this)."::insert_grouped_commande", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$db->commit();
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * Update nb etp of order in reconstructing data for diagram
	 *
	 * @param int $id_commande
	 * @param int $nb contact
	 * 
	 * @return int if KO, Id of line if OK
	 */
	public function updateEtpPropal($id_propal, $etp) 
	{
		global $conf, $db, $langs, $user;
		$sql = "UPDATE ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " SET";
		$sql .= " expect_etp_propal =".((int) $etp);
		$sql .= " WHERE fk_propal_signed = ".((int) $id_propal);
		$sql .= " OR fk_propal_open = ".((int) $id_propal);

		dol_syslog(get_class($this)."::insert_grouped_commande", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$db->commit();
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * Update nb contacts of order in reconstructing data for diagram
	 *
	 * @param int $id_commande
	 * @param int $nb contact
	 * 
	 * @return int if KO, Id of line if OK
	 */
	public function updateNbContactCommande($id_commande, $nbcontact) 
	{
		global $conf, $db, $langs, $user;
		$sql = "UPDATE ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " SET";
		$sql .= " etp_commande =".((int) $nbcontact);
		$sql .= " WHERE fk_commande = ".((int) $id_commande);

		dol_syslog(get_class($this)."::insert_grouped_commande", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$db->commit();
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * Update nb contacts of order in reconstructing data for diagram
	 *
	 * @param int $id_propal
	 * @param int $nb contact
	 * 
	 * @return int if KO, Id of line if OK
	 */
	public function updateNbContactPropal($id_propal, $nbcontact) 
	{
		global $conf, $db, $langs, $user;
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " SET";
		$sql .= " etp_propal =".((int) $nbcontact);
		$sql .= " WHERE fk_propal_signed = ".((int) $id_propal);
		$sql .= " OR fk_propal_open = ".((int) $id_propal);

		dol_syslog(get_class($this)."::insert_grouped_propal", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$db->commit();
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * Insert facture data reconstituted according to the total by years into database (just for a first time to update data)
	 *
	 */
	public function insertGroupedFacture($id_facture, $date_facture, $total, $fk_projet) 
	{
		global $conf, $db, $langs, $user;
		
		var_dump($fk_facture);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " (year, total_facture, fk_facture, fk_f_projet)";
		$sql .= " VALUES('$date_facture', '$total', '$id_facture', '$fk_projet')";

		dol_syslog(get_class($this)."::insertGroupedFacture", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

			if ($id > 0) {
				$rowid = $id;
				$result = $rowid;
			} else {
				$result = - 2;
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = - 1;
			dol_syslog($this->error, LOG_ERR);
		}
		return $result;
	}

	/**
	 * Insert facture data reconstituted according to the total by years into database (just for a first time to update data)
	 *
	 */
	public function insertGroupedFacturePv($id_facture, $date_facture, $total, $fk_projet) 
	{
		global $conf, $db, $langs, $user;
		

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " (year, total_facture_pv, fk_facture_pv, fk_fp_projet)";
		$sql .= " VALUES('$date_facture', '$total', '$id_facture', '$fk_projet')";

		dol_syslog(get_class($this)."::insertGroupedFacture", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

			if ($id > 0) {
				$rowid = $id;
				$result = $rowid;
			} else {
				$result = - 2;
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = - 1;
			dol_syslog($this->error, LOG_ERR);
		}
		return $result;
	}

	/**
	 * Insert facture data reconstituted according to the total by years into database (just for a first time to update data)
	 *
	 */
	public function insertGroupedFactureDraft($id_facture, $date_facture, $total, $fk_projet) 
	{
		global $conf, $db, $langs, $user;
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " (year, total_facture_draft, fk_facture_draft, fk_fd_projet)";
		$sql .= " VALUES('$date_facture', '$total', '$id_facture', '$fk_projet')";

		dol_syslog(get_class($this)."::insertGroupedFacture", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

			if ($id > 0) {
				$rowid = $id;
				$result = $rowid;
			} else {
				$result = - 2;
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = - 1;
			dol_syslog($this->error, LOG_ERR);
		}
		return $result;
	}

	/**
	 * 
	 */
	public function insertGroupedFactureFourn($id_facturefourn, $date_facturefourn, $total, $fk_projet) 
	{
		global $conf, $db, $langs, $user;
		

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
		$sql .= " (year, total_facturefourn, fk_facturefourn, fk_ff_projet)";
		$sql .= " VALUES('$date_facturefourn', '$total', '$id_facturefourn', '$fk_projet')";

		dol_syslog(get_class($this)."::insert_grouped_facturefourn", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

			if ($id > 0) {
				$rowid = $id;
				$result = $rowid;
			} else {
				$result = - 2;
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = - 1;
			dol_syslog($this->error, LOG_ERR);
		}
		return $result;
	}

	/**
	 * Insert commercial data reconstituted according to the total by years into database (just for a first time to update data)
	 *
	 * @param User $user making insert
	 * @return int if KO, Id of line if OK
	 */
	public function insertGroupedCommande($id_commande, $date_debut, $date_fin, $total, $fk_projet, $date_livraison) 
	{
		global $conf, $db, $langs, $user;
		//var_dump($date_fin);
		$arr = array();
		
		$interval = $date_debut->diff($date_fin);
		$diff_month = $date_fin->format('m') - $date_debut->format('m');
		$month = $interval->format('%m');
		$year1 = $date_debut->format('Y');
		$year2 = $date_fin->format('Y');
		$month1 = $date_debut->format('m');
		$month2 = $date_fin->format('m') + 1;
		$nb_year = $date_fin->format('Y') - $date_debut->format('Y');
		//$nb_month = ($year * 12) + $month;
		$nb_month = (($year2 - $year1) * 12) + ($month2 - $month1);
		$total_by_month = floatval($total) / intval($nb_month); 
		$diff_month = $date_fin->format('m') - $date_debut->format('m');

		if($nb_year == 0 && $diff_month == 0) { 
			$dates = mktime(0, 0, 0, intval( $date_debut->format('m')), 1,  $date_debut->format('Y'));
			$arr[][] = array($db->idate($dates) => floatval($total));
		}
	
		if($nb_year == 0 && $diff_month > 0) {
			$arr[] = $this->one_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month, $diff_month);
		}else if($nb_year == 1){
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}else if($nb_year > 1) {
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->between_year_dates($date_fin->format('m'), $date_debut->format('Y'), $total_by_month, $nb_year);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}
		
		
		$arr = array_filter($arr);
	
		if(!empty($arr)) {

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
			$sql .= " (year, total_commande, fk_commande, c_date_livraison, fk_c_projet)";
			$sql .= " VALUES(";

			$i = 0;
			foreach ($arr as $attributes) {
				foreach ($attributes as $values) {
					foreach ($values as $year => $total) {
						if ($i) {
							$sql .= "),(";
						}
						$sql .= ' '.(!isset($year) ? 'NULL' : "'".$db->escape($year)."'").',';
						$sql .= ' '.(!isset($total) ? 'NULL' : "'".$db->escape((string) $total)."'").',';
						$sql .= ' '.(!isset($id_commande) ? 'NULL' : "'".$db->escape((int) $id_commande)."'").',';
						$sql .= ' '.(!isset($id_commande) ? 'NULL' : "'".$db->escape($date_livraison)."'").',';
						$sql .= ' '.(!isset($id_commande) ? 'NULL' : "'".$db->escape((int) $fk_projet)."'").'';
				
						
						$i++;
					}
				}
			}
			$sql .= ")";

			dol_syslog(get_class($this)."::insertGroupedCommande", LOG_DEBUG);
			$resql = $db->query($sql);
			
			if ($resql) {
				$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

				if ($id > 0) {
					$rowid = $id;
					$result = $rowid;
				} else {
					$result = - 2;
					dol_syslog($this->error, LOG_ERR);
				}
			} else {
				$result = - 1;
				dol_syslog($this->error, LOG_ERR);
			}
		}
		return $result;
	}


	/**
	 * Insert commercial data reconstituted according to the total by years into database (just for a first time to update data)
	 *
	 * @param User $user making insert
	 * @return int if KO, Id of line if OK
	 */
	public function insertGroupedUser($id_user, $date_debut, $date_fin, $total) 
	{
		global $conf, $db, $langs, $user;
		//var_dump($date_fin);
		$arr = array();
		
		$interval = $date_debut->diff($date_fin);
		$diff_month = $date_fin->format('m') - $date_debut->format('m');
		$month = $interval->format('%m');
		$year1 = $date_debut->format('Y');
		$year2 = $date_fin->format('Y');
		$month1 = $date_debut->format('m');
		$month2 = $date_fin->format('m') + 1;
		$nb_year = $date_fin->format('Y') - $date_debut->format('Y');
		//$nb_month = ($year * 12) + $month;
		$nb_month = (($year2 - $year1) * 12) + ($month2 - $month1);
		// $total_by_month = floatval($total) / intval($nb_month); 
		$diff_month = $date_fin->format('m') - $date_debut->format('m');

		if($nb_year == 0 && $diff_month == 0) { 
			$dates = mktime(0, 0, 0, intval( $date_debut->format('m')), 1,  $date_debut->format('Y'));
			$arr[][] = array($db->idate($dates) => floatval($total));
		}
	
		if($nb_year == 0 && $diff_month > 0) {
			$arr[] = $this->one_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total, $diff_month);
		}else if($nb_year == 1){
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total);
		}else if($nb_year > 1) {
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total);
			$arr[] = $this->between_year_dates($date_fin->format('m'), $date_debut->format('Y'), $total, $nb_year);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total);
		}
		
		
		$arr = array_filter($arr);
	
		if(!empty($arr)) {

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
			$sql .= " (year, total_employee, fk_employee)";
			$sql .= " VALUES(";

			$i = 0;
			foreach ($arr as $attributes) {
				foreach ($attributes as $values) {
					foreach ($values as $year => $nb) {
						if ($i) {
							$sql .= "),(";
						}
						$sql .= ' '.(!isset($year) ? 'NULL' : "'".$db->escape($year)."'").',';
						$sql .= ' '.(!isset($nb) ? 'NULL' : "'".$db->escape((string) $nb)."'").',';
						$sql .= ' '.(!isset($id_user) ? 'NULL' : "'".$db->escape((int) $id_user)."'").'';
				
						
						$i++;
					}
				}
			}
			$sql .= ")";

			dol_syslog(get_class($this)."::insertGroupedUser", LOG_DEBUG);
			$resql = $db->query($sql);
			
			if ($resql) {
				$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

				if ($id > 0) {
					$rowid = $id;
					$result = $rowid;
				} else {
					$result = - 2;
					dol_syslog($this->error, LOG_ERR);
				}
			} else {
				$result = - 1;
				dol_syslog($this->error, LOG_ERR);
			}
		}
		return $result;
	}

	/**
	 * Insert commercial data reconstituted according to the total by years into database (just for a first time to update data)
	 *
	 * @param User $user making insert
	 * @return int if KO, Id of line if OK
	 */
	public function insertGroupedCFournisseur($id_c_fournisseur, $date_debut, $date_fin, $total, $fk_projet) 
	{
		global $conf, $db, $langs, $user;
		//var_dump($date_fin);
		$arr = array();
		$interval = $date_debut->diff($date_fin);
		$diff_month = $date_fin->format('m') - $date_debut->format('m');
		$month = $interval->format('%m');
		$year1 = $date_debut->format('Y');
		$year2 = $date_fin->format('Y');
		$month1 = $date_debut->format('m');
		$month2 = $date_fin->format('m') + 1;
		$nb_year = $date_fin->format('Y') - $date_debut->format('Y');
		//$nb_month = ($year * 12) + $month;
		$nb_month = (($year2 - $year1) * 12) + ($month2 - $month1);
		$total_by_month = floatval($total) / intval($nb_month); 
		$diff_month = $date_fin->format('m') - $date_debut->format('m');

		if($nb_year == 0 && $diff_month == 0) { 
			$dates = mktime(0, 0, 0, intval( $date_debut->format('m')), 1,  $date_debut->format('Y'));
			$arr[][] = array($db->idate($dates) => floatval($total));
		}

		if($nb_year == 0 && $diff_month > 0) {
			$arr[] = $this->one_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month, $diff_month);
		}else if($nb_year == 1){
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}else if($nb_year > 1) {
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->between_year_dates($date_fin->format('m'), $date_debut->format('Y'), $total_by_month, $nb_year);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}
		
		$arr = array_filter($arr);
		
		if(!empty($arr)) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
			$sql .= " (year, total_supplier_c, fk_supplier_c, fk_sc_projet)";
			$sql .= " VALUES(";

			$i = 0;
			foreach ($arr as $attributes) {
				foreach ($attributes as $values) {
					foreach ($values as $year => $total) {
						if ($i) {
							$sql .= "),(";
						}
						$sql .= "'".$db->escape($year)."',";
						$sql .= "'".$db->escape((string) $total)."',";
						$sql .= "'".$db->escape((int) $id_c_fournisseur)."',";
						$sql .= "'".$db->escape((int) $fk_projet)."'";
						
						$i++;
					}
				}
			}
			$sql .= ")";
			
			dol_syslog(get_class($this)."::insert_grouped_element", LOG_DEBUG);
			$resql = $db->query($sql);
			
			if ($resql) {
				$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

				if ($id > 0) {
					$rowid = $id;
					$result = $rowid;
				} else {
					$result = - 2;
					dol_syslog($this->error, LOG_ERR);
				}
			} else {
				$result = - 1;
				dol_syslog($this->error, LOG_ERR);
			}
		}

		return $result;
	}


	/**
	 * 
	 */
	public function insertGroupedPropalOpen($id_propal, $date_debut, $date_fin, $total, $fk_projet) 
	{
		global $conf, $db, $langs, $user;
		//var_dump($date_fin);
		$arr = array();
		$interval = $date_debut->diff($date_fin);
		$diff_month = $date_fin->format('m') - $date_debut->format('m');
		$month = $interval->format('%m');
		$year1 = $date_debut->format('Y');
		$year2 = $date_fin->format('Y');
		$month1 = $date_debut->format('m');
		$month2 = $date_fin->format('m') + 1;
		$nb_year = $date_fin->format('Y') - $date_debut->format('Y');
		//$nb_month = ($year * 12) + $month;
		$nb_month = (($year2 - $year1) * 12) + ($month2 - $month1);
		$total_by_month = floatval($total) / intval($nb_month); 
		$diff_month = $date_fin->format('m') - $date_debut->format('m');

		if($nb_year == 0 && $diff_month == 0) { 
			$dates = mktime(0, 0, 0, intval( $date_debut->format('m')), 1,  $date_debut->format('Y'));
			$arr[][] = array($db->idate($dates) => floatval($total));
		}

		if($nb_year == 0 && $diff_month > 0) {
			$arr[] = $this->one_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month, $diff_month);
		}else if($nb_year == 1){
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}else if($nb_year > 1) {
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->between_year_dates($date_fin->format('m'), $date_debut->format('Y'), $total_by_month, $nb_year);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}
		
		$arr = array_filter($arr);

		if(!empty($arr)) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
			$sql .= " (year, total_propal_open, fk_propal_open, fk_po_projet)";
			$sql .= " VALUES(";

			$i = 0;
			foreach ($arr as $attributes) {
				foreach ($attributes as $values) {
					foreach ($values as $year => $total) {
						if ($i) {
							$sql .= "),(";
						}
						$sql .= "'".$db->escape($year)."',";
						$sql .= "'".$db->escape((string) $total)."',";
						$sql .= "'".$db->escape((int) $id_propal)."',";
						$sql .= "'".$db->escape((int) $fk_projet)."'";
					
						$i++;
					}
				}
			}
			$sql .= ")";
			
			dol_syslog(get_class($this)."::insertGroupedPropalOpen", LOG_DEBUG);
			$resql = $db->query($sql);
			
			if ($resql) {
				$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

				if ($id > 0) {
					$rowid = $id;
					$result = $rowid;
				} else {
					$result = - 2;
					dol_syslog($this->error, LOG_ERR);
				}
			} else {
				$result = - 1;
				dol_syslog($this->error, LOG_ERR);
			}
		}

		return $result;
	}

	/**
	 * 
	 */
	public function insertGroupedPropalSigned($id_propal, $date_debut, $date_fin, $total, $fk_projet) 
	{
		global $conf, $db, $langs, $user;
		//var_dump($date_fin);
		$arr = array();
		$interval = $date_debut->diff($date_fin);
		$diff_month = $date_fin->format('m') - $date_debut->format('m');
		$month = $interval->format('%m');
		$year1 = $date_debut->format('Y');
		$year2 = $date_fin->format('Y');
		$month1 = $date_debut->format('m');
		$month2 = $date_fin->format('m') + 1;
		$nb_year = $date_fin->format('Y') - $date_debut->format('Y');
		//$nb_month = ($year * 12) + $month;
		$nb_month = (($year2 - $year1) * 12) + ($month2 - $month1);
		$total_by_month = floatval($total) / intval($nb_month); 
		$diff_month = $date_fin->format('m') - $date_debut->format('m');

		if($nb_year == 0 && $diff_month == 0) { 
			$dates = mktime(0, 0, 0, intval( $date_debut->format('m')), 1,  $date_debut->format('Y'));
			$arr[][] = array($db->idate($dates) => floatval($total));
		}

		if($nb_year == 0 && $diff_month > 0) {
			$arr[] = $this->one_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month, $diff_month);
		}else if($nb_year == 1){
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}else if($nb_year > 1) {
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->between_year_dates($date_fin->format('m'), $date_debut->format('Y'), $total_by_month, $nb_year);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}
		
		$arr = array_filter($arr);
		
		if(!empty($arr)) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
			$sql .= " (year, total_propal_signed, fk_propal_signed, fk_ps_projet)";
			$sql .= " VALUES(";

			$i = 0;
			foreach ($arr as $attributes) {
				foreach ($attributes as $values) {
					foreach ($values as $year => $total) {
						if ($i) {
							$sql .= "),(";
						}
						$sql .= "'".$db->escape($year)."',";
						$sql .= "'".$db->escape((string) $total)."',";
						$sql .= "'".$db->escape((int) $id_propal)."',";
						$sql .= "'".$db->escape((int) $fk_projet)."'";
					
						$i++;
					}
				}
			}
			$sql .= ")";
			
			dol_syslog(get_class($this)."::insertGroupedPropalSigned", LOG_DEBUG);
			$resql = $db->query($sql);
			
			if ($resql) {
				$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

				if ($id > 0) {
					$rowid = $id;
					$result = $rowid;
				} else {
					$result = - 2;
					dol_syslog($this->error, LOG_ERR);
				}
			} else {
				$result = - 1;
				dol_syslog($this->error, LOG_ERR);
			}
		}
		return $result;
	}

	/**
	 * Insert commercial data reconstituted according to the total by years into database (just for a first time to update data)
	 *
	 * @param User $user making insert
	 * @return int if KO, Id of line if OK
	 */
	public function insert_grouped_expense($id_expense,  $date_debut, $date_fin, $total) 
	{
		global $conf, $db, $langs, $user;
		//var_dump($date_fin);
		$arr = array();
		$interval = $date_debut->diff($date_fin);
		$diff_month = $date_fin->format('m') - $date_debut->format('m');
		$month = $interval->format('%m');
		$year1 = $date_debut->format('Y');
		$year2 = $date_fin->format('Y');
		$month1 = $date_debut->format('m');
		$month2 = $date_fin->format('m') + 1;
		$nb_year = $date_fin->format('Y') - $date_debut->format('Y');
		$month_13 = 0.92308;
		$nb_month = ($year * 12) + $month;
		
		$nb_month = (($year2 - $year1) * 12) + ($month2 - $month1);
		//$total_by_months += floatval($total); 
		$total_by_month = floatval($total) / (intval($nb_month) * $month_13); 
		$diff_month = $date_fin->format('m') - $date_debut->format('m');
		
		if($nb_year == 0 && $diff_month == 0) { 
			$dates = mktime(0, 0, 0, intval( $date_debut->format('m')), 1,  $date_debut->format('Y'));
			//if($db->idate($dates) == )
			
			$total_by_months += floatval($total);
			$arr[][] = array($db->idate($dates) => floatval($total) * $month_13);
			$date = new DateTimeImmutable(dol_print_date($db->idate($dates), '%Y-%m-%d'));
			if($date->format('Y') == $date_fin->format('Y')) {
				$arr[][] = array($db->idate($dates) => $total_expense += floatval($total));
			}
		}

		if($nb_year == 0 && $diff_month > 0) {
			$arr[] = $this->one_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month, $diff_month);
		}else if($nb_year == 1){
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}else if($nb_year > 1) {
			$arr[] = $this->first_year_dates($date_debut->format('m'), $date_debut->format('Y'), $total_by_month);
			$arr[] = $this->between_year_dates($date_fin->format('m'), $date_debut->format('Y'), $total_by_month, $nb_year);
			$arr[] = $this->last_year_dates($date_fin->format('m'), $date_fin->format('Y'), $total_by_month);
		}
		
		$arr = array_filter($arr);
		// var_dump($arr);
		// var_dump( $date_debut);
		// var_dump( $date_fin);
		// var_dump($total);
		// var_dump($id_expense);
		
		// var_dump($total_by_month);
		// var_dump($total_by_month1);
		if(!empty($arr)) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."comm_element_graph";
			$sql .= " (year, total_expense, fk_expense)";
			$sql .= " VALUES(";

			$i = 0;
			foreach ($arr as $attributes) {
				foreach ($attributes as $values) {
					foreach ($values as $year => $total) {
						if ($i) {
							$sql .= "),(";
						}
						$sql .= "'".$db->escape($year)."',";
						$sql .= "'".$db->escape((string) $total)."',";
						$sql .= "'".$db->escape((int) $id_expense)."'";
						$i++;
					}
				}
			}
			$sql .= ")";
			
			dol_syslog(get_class($this)."::insert_grouped_expense", LOG_DEBUG);
			$resql = $db->query($sql);
			
			if ($resql) {
				$id = $db->last_insert_id(MAIN_DB_PREFIX."comm_element_graph");

				if ($id > 0) {
					$rowid = $id;
					$result = $rowid;
				} else {
					$result = - 2;
					dol_syslog($this->error, LOG_ERR);
				}
			} else {
				$result = - 1;
				dol_syslog($this->error, LOG_ERR);
			}

			return $result;
		}
	}

	/**
	 * 
	 * 
	 * @return array wtih date as keys and float total as values
	 */
	public function one_year_dates($month, $year, $total_by_month, $diff_month) {
		global $db;
		$arr = array();

		$last_year = intval($month) + intval($diff_month);
	
		for($i = intval($month); $i <= $last_year; $i++) {
			if($diff_month > 0) { 
				$dates = mktime(0, 0, 0, intval($month++), 1, $year);
				$arr[] = array($db->idate($dates) => $total_by_month);
			}
		}

		return $arr;
	}

	/**
	 * 
	 * 
	 * @return array wtih date as keys and float total as values
	 */
	public function first_year_dates($month, $year, $total_by_month) {
		global $db;
		$arr = array();

		
		
		for($i = intval($month); $i <= 12; $i++) {
			//if($nb_month > 0) { 
				$dates = mktime(0, 0, 0, intval($month++), 1, $year);
				$arr[] = array($db->idate($dates) => $total_by_month);
			//}
		}

		return $arr;
	}

	/**
	 * 
	 * 
	 * @return array wtih date as keys and float total as values
	 */
	public function last_year_dates($month, $year, $total_by_month) {
		global $db;
		$arr = array();
		
		for($i = 1; $i <= intval($month); $i++) {
			//if($total_by_month > 0) { 
				$dates = mktime(0, 0, 0, $i, 1, $year);
				$arr[] = array($db->idate($dates) => $total_by_month);
			//}
		}
		return $arr;
	}

	/**
	 * 
	 * 
	 * @return array wtih date as keys and float total as values
	 */
	public function between_year_dates($month, $year, $total_by_month, $nb_year) {
		global $db;
		$arr = array();
		
		for($j = 1; $j < intval($nb_year); $j++) {
			for($i = 1; $i <= 12; $i++) {
				//if($total_by_month > 0) { 
					$dates = mktime(0, 0, 0, $i, 1, $year + $j);
					$arr[] = array($db->idate($dates) => $total_by_month);
				//}
			}
		}
		return $arr;
	}

	/**
	 * 
	 */
	public function deleteElement() 
	{
		$error = 0;
		global $db;
			$db->begin();

			// remove categorie association
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."comm_element_graph";
				//$sql .= " WHERE ".$sqlfk."=".((int) $id);
				
				$res = $db->query($sql);
				if (!$res) {
					$this->error = $db->lasterror();
					$error++;
				}

			if (!$error) {
				$db->commit();
			} else {
				$this->db->rollback();
				$this->error = $db->lasterror();
			}
		return $res;
	}
	
	/**
	 * 
	 */
	// public function getValues($arragences, $arrresp, $arrproj, $date_start, $date_end, $option)
	// {
	// 	global $db;

	// 	foreach($this->sumfields as $key => $values) {
	// 		$sql = "SELECT ".$values['total'].", DATE_FORMAT(g.year, '%Y-%m') as dm";
	// 		$sql .= " FROM ".MAIN_DB_PREFIX."comm_element_graph AS g";
			
	// 		if($option == 'resp' || !empty($arragences) || !empty($arrresp) || !empty($arrproj)) {
	// 			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = ".$values['id']."";
	// 			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";

	// 			$i = 1;

	// 			if(empty($arrresp) && (!empty($arragences) || !empty($arrproj))) {
	// 				$sql .= " WHERE 1 = 1";
	// 			}
	
	// 			if(!empty($arrresp) && $option ='all') {
	// 				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = ".$values['fk_projet']."";
	// 				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
	// 				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

	// 				$sql .= " WHERE 1 = 1";
	// 				$sql .= " AND tc.element = 'project'";
	// 				$sql .= " AND tc.source = 'internal'";
	// 				$sql .= " AND tc.code = 'PROJECTLEADER'";
			
	// 				$sql .= " AND t.rowid IN (".implode(', ', $arrresp).")";
	// 				}
					 
	// 			if(!empty($arragences) && $option == 'all') {
	// 				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arragences).")";
	// 			}
	
	// 			if(!empty($arrproj) && $option == 'all') {
	// 				$sql .= " AND p.rowid IN (".implode(', ', $arrproj).")";
	// 			}
		
	// 			if($option == 'resp') {
	// 				$sql .= " AND";
	// 				if(!empty(array_filter($this->getAgencesBySoc()))) {
	// 					$agences = array_unique($this->getAgencesBySoc());
	// 					array_filter($agences);
	
	// 					foreach($agences as $manager => $agence) {
	// 						$sql .= " pe.agenceconcerne =".((int) $agence);
	// 						if($i < sizeof($agences)) {
	// 							$sql .= " AND";
	// 						}
	
	// 						$i++;
	// 					}
	// 				}
	// 			}
	// 		}
	// 		else{
	// 			$sql .= " WHERE 1 = 1";
	// 		}

	// 		// $sql .= " AND g.year > NOW() - INTERVAL 3 YEAR";
	// 		$sql .= " AND g.year >= '".$db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
	// 		// $sql .= " AND g.year < NOW() + INTERVAL 3 YEAR";
	// 		$sql .= " AND g.year <= '".$db->idate($date_end, '%Y-%m-%d')."'";
	// 		$sql .= " GROUP BY DATE_FORMAT(g.year, '%Y-%m')";
	// 		$sql .= " ORDER BY DATE_FORMAT(g.year, '%Y-%m')";

	// 		$resql = $db->query($sql);
				
	// 		if ($resql) {
	// 			$i = 0;
	// 			$num = $db->num_rows($resql);
				
				
	// 			$obj = $db->fetch_object($resql);
				
	// 			$vals = array();
		
	// 			while ($i < $num) {
	// 			$row = $db->fetch_row($resql);
	// 				if ($row) {
	// 					// var_dump($row);
	// 					$arr[$key][] = array('total' => $row[0], 'date' => $row[1]);
	// 				}
	
	// 				$i++;
	// 			}
	// 			$vals = $arr;
	// 		}
	// 	}
	// 	return $vals;
	// }

	/**
	 * 
	 */
	public function getValues($arragences, $arrresp, $arrproj, $date_start, $date_end, $option, $total, $fk_projet, $date_livraison = '')
	{
		global $db, $user;
		$now = dol_now();
		
		// foreach($this->sumfields as $key => $values) {
			$sql = "SELECT ".$total.", DATE_FORMAT(g.year, '%Y-%m') as dm";
			if(isset($date_livraison) && $date_livraison !== '') {
				$sql .= " ,".$date_livraison.", count(g.fk_commande), g.fk_commande";
			}
			$sql .= " FROM ".MAIN_DB_PREFIX."comm_element_graph AS g";
			
			if($option == 'resp' || !empty($arragences) || !empty($arrresp) || !empty($arrproj)) {
			
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = ".$fk_projet."";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";

				$i = 1;

				if(empty($arrresp) && (!empty($arragences) || !empty($arrproj))) {
					$sql .= " WHERE 1 = 1";
				}
	
				if(!empty($arrresp) && $option ='all') {
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

					$sql .= " WHERE 1 = 1";
					$sql .= " AND tc.element = 'project'";
					$sql .= " AND tc.source = 'internal'";
					$sql .= " AND tc.code = 'PROJECTLEADER'";
			
					$sql .= " AND t.rowid IN (".implode(', ', $arrresp).")";
					}
					 
				if(!empty($arragences) && $option == 'all') {
					$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arragences).")";
				}
	
				if(!empty($arrproj) && $option == 'all') {
					$sql .= " AND p.rowid IN (".implode(', ', $arrproj).")";
				}
		
				if($option == 'resp') {
					$sql .= " AND";
					if(!empty(array_filter($this->getAgencesBySoc()))) {
						$agences = array_unique($this->getAgencesBySoc());
						array_filter($agences);
	
						foreach($agences as $manager => $agence) {
							$sql .= " pe.agenceconcerne =".((int) $agence);
							if($i < sizeof($agences)) {
								$sql .= " AND";
							}
	
							$i++;
						}
					}
				}
			}
			else{
				$sql .= " WHERE 1 = 1";
			}

		
			// $sql .= " AND g.year > NOW() - INTERVAL 3 YEAR";
			$sql .= " AND g.year >= '".$db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
			// $sql .= " AND g.year < NOW() + INTERVAL 3 YEAR";
			$sql .= " AND g.year <= '".$db->idate($date_end, '%Y-%m-%d')."'";
			
			if(isset($date_livraison) && $date_livraison !== '') {
				$sql .= " GROUP BY DATE_FORMAT(g.year, '%Y-%m')";
			}else{
				$sql .= " GROUP BY DATE_FORMAT(g.year, '%Y-%m')";
			}
			$sql .= " ORDER BY DATE_FORMAT(g.year, '%Y-%m')";

			$resql = $db->query($sql);
				
			if ($resql) {
				$i = 0;
				$num = $db->num_rows($resql);
				
				
				// $obj = $db->fetch_object($resql);
				
				$vals = array();
				
				$delivery_status = '';
				$arr = [];

				while ($i < $num) {
					$row = $db->fetch_row($resql);

					if ($row) {
						// Extraction des valeurs nécessaires avec vérifications
						$dateLivraison = isset($row[2]) ? $db->jdate($row[2]) : null;
						$delivery_status = ($dateLivraison && $dateLivraison >= $now) ? $row[2] : $delivery_status;

						$nb_month_commande = isset($row[3]) && is_numeric($row[2]) && $dateLivraison >= $now 
						? $row[2] - $now 
						: null;

						// Ajout des données formatées dans le tableau
						$arr[] = [
							'total' => $row[0],
							'date' => $row[1],
							'date_livraison' => $delivery_status
						];
					}

					$i++;
				}
				
				$vals = $arr;
			}
		// }
		return $vals;
	}

	/**
	 * Get employees' salaries by referencing their respective projects
	 * 
	 * @param string Option to filter by month or show by year.
	 */
	public function employeesCostByProjects($option, $date_start, $date_end)
	{
		global $db, $user;
		$heureSup = $this->employeeHeureSup($option, $date_start, $date_end);

		$sql = "SELECT sum(t.thm * (t.element_duration / 3600)) as cost, t.element_duration, DATE_FORMAT(t.element_date,'%Y') as dy, DATE_FORMAT(t.element_date,'%Y-%m') as dm, pe.agenceconcerne as agence";
		$sql .= ", se.code as name_alias, t.element_date, se.couleur_a as color_ca, se.couleur_b as color_exp, p.rowid as id";

		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
		
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = pe.agenceconcerne";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
		$sql .= " WHERE pe.agenceconcerne is not null";
		$sql .= " AND t.element_date BETWEEN '".$db->idate($date_start)."' AND '".$db->idate($date_end)."'";
		$sql .= " AND t.elementtype = 'task'";
		//for project with status 'gagné'
		// $sql .= " AND p.fk_opp_status = 6 AND p.entity IN (1)";
		$sql .= " AND p.entity IN (1)";
		
		if($option == "Year") {
			$sql .= " GROUP BY agence, dy";
			$sql .= " ORDER BY dy";
		}
		if($option == "Month") {
			$sql .= " GROUP BY agence, dm";
			$sql .= " ORDER BY dm";
		}
		
		dol_syslog(get_class($this)."::employeesCostByProjects", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if($option == "Year") {
					$filterdate = $obj->dy;
				}
				if($option == "Month") {
					$filterdate = $obj->dm;
				}
		
				$hs25 =  $heureSup[$obj->agence][$filterdate]['hs25'] == null ? 0 :  $heureSup[$obj->agence][$filterdate]['hs25'];
				$hs50 =  $heureSup[$obj->agence][$filterdate]['hs25'] == null ? 0 :  $heureSup[$obj->agence][$filterdate]['hs50'];
				$cost = $obj->cost + $hs25 + $hs50;
				// if($user->id == 412) {
				// 	// var_dump($cost.'_'.$obj->cost.'_'.$filterdate.'_'.$obj->name_alias.' ------- '.$hs25);
				// 	var_dump($obj->name_alias.'_'.$cost);
				// }
				
				$salaries[] = array('date' => $filterdate, 'amount' => $cost, 'agence' => $obj->name_alias, 'color_exp' => $obj->color_exp);
				
				$i++;
			} 

			return $salaries;
		} else {
			$this->error = $db->error();
			return -1;
		}
	
	}

	public function employeeHeureSup($option, $date_start, $date_end)
	{
		global $db, $user;

		$sql = "SELECT DATE_FORMAT(t.element_date,'%Y') as dy, DATE_FORMAT(t.element_date,'%Y-%m') as dm, pe.agenceconcerne as agence";
		$sql .= ", s.name_alias";
		$sql .= " , SUM((hs.heure_sup_25_duration / 3600) * ".$db->ifsql("t.thm IS NULL", 0, "(t.thm * 0.25)").") as amount_hs25,";
        $sql .= " SUM((hs.heure_sup_50_duration / 3600) * ".$db->ifsql("t.thm IS NULL", 0, "(t.thm * 0.5)").") as amount_hs50";

		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " RIGHT JOIN ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs ON hs.fk_projet_task_time = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
	
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = pe.agenceconcerne";
		$sql .= " WHERE pe.agenceconcerne is not null";
		$sql .= " AND t.element_date BETWEEN '".$db->idate($date_start)."' AND '".$db->idate($date_end)."'";
		$sql .= " AND t.elementtype = 'task'";
	
		
		if($option == "Year") {
			$sql .= " GROUP BY agence, dy";
			$sql .= " ORDER BY dy";
		}
		if($option == "Month") {
			$sql .= " GROUP BY agence, dm";
			$sql .= " ORDER BY dm";
		}
		
		
		dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if($option == "Year") {
					$filterdate = $obj->dy;
				}
				if($option == "Month") {
					$filterdate = $obj->dm;
				}

				$hs25 =  $obj->amount_hs25 == null ? 0 : $obj->amount_hs25;
				$hs50 = $obj->amount_hs50 == null ? 0 : $obj->amount_hs50;
				// $cost = $obj->cost + $hs25 + $hs50;
			
			
				$heursup[$obj->agence][$filterdate] = array('hs25' => $hs25, 'hs50' => $hs50, 'agence' => $obj->name_alias);
				
				$i++;
			} 

			return $heursup;
		} else {
			$this->error = $db->error();
			return -1;
		}
	
	}

	public function employeeHeureSupByDomains($option, $date_start, $date_end)
	{
		global $db, $user;

		$sql = "SELECT DATE_FORMAT(t.element_date,'%Y') as dy, DATE_FORMAT(t.element_date,'%Y-%m') as dm, pe.agenceconcerne as agence";
		$sql .= ", s.name_alias";
		$sql .= " , SUM((hs.heure_sup_25_duration / 3600) * ".$db->ifsql("t.thm IS NULL", 0, "(t.thm * 0.25)").") as amount_hs25,";
        $sql .= " SUM((hs.heure_sup_50_duration / 3600) * ".$db->ifsql("t.thm IS NULL", 0, "(t.thm * 0.5)").") as amount_hs50, pe.domaine";

		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " RIGHT JOIN ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs ON hs.fk_projet_task_time = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
	
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = pe.agenceconcerne";
		$sql .= " WHERE pe.agenceconcerne is not null";
		$sql .= " AND t.element_date BETWEEN '".$db->idate($date_start)."' AND '".$db->idate($date_end)."'";
		$sql .= " AND t.elementtype = 'task'";
		// $sql .= " AND pe.agenceconcerne NOT LIKE '%,%' AND pe.agenceconcerne REGEXP '^[0-9]+$'";
	
		
		if($option == "Year") {
			$sql .= " GROUP BY agence, pe.domaine, dy";
			$sql .= " ORDER BY dy";
		}
		if($option == "Month") {
			$sql .= " GROUP BY agence, pe.domaine, dm";
			$sql .= " ORDER BY dm";
		}
		
		
		dol_syslog(get_class($this)."::employeeHeureSupByDomains", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if($option == "Year") {
					$filterdate = $obj->dy;
				}
				if($option == "Month") {
					$filterdate = $obj->dm;
				}

				$hs25 =  $obj->amount_hs25 == null ? 0 : $obj->amount_hs25;
				$hs50 = $obj->amount_hs50 == null ? 0 : $obj->amount_hs50;
				// $cost = $obj->cost + $hs25 + $hs50;
			
				$heursup[$obj->agence][$obj->domaine][$filterdate] = array('hs25' => $hs25, 'hs50' => $hs50, 'agence' => $obj->name_alias, 'domaine' => $obj->domaine);
				
				$i++;
			} 

			return $heursup;
		} else {
			$this->error = $db->error();
			return -1;
		}
	
	}

	/**
	 * get salaries of employees by reference to the projects
	 * 
	 * @param string option to filter by option either resp or all py default
	 */
	public function employeeCostByProject($option, $arr_agences, $arr_resp,  $arr_proj, $date_start, $date_end)
	{
		global $db, $user;

		$sql = "SELECT sum((t.thm) * (t.element_duration / 3600)) as cost, DATE_FORMAT(t.element_date,'%Y-%m') as dm";
		$sql .= ", SUM(hs.heure_sup_25_duration / 3600 * ".$db->ifsql("t.thm IS NULL", 0, "t.thm * 0.25").") as amount_hs25,";
        $sql .= " SUM(hs.heure_sup_50_duration / 3600 * ".$db->ifsql("t.thm IS NULL", 0, "t.thm * 0.5").") as amount_hs50, u.rowid as userid";

		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs ON hs.fk_projet_task_time = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on ec.fk_socpeople = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
		$sql .= " WHERE 1 = 1";

			if(!empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
				
				if(!empty($arr_resp) && $option ='all') {
					$sql .= " AND tc.element = 'project'";
					$sql .= " AND tc.source = 'internal'";
					$sql .= " AND tc.code = 'PROJECTLEADER'";
					$sql .= " AND ec.element_id = p.rowid";
					// $sql .= " AND u.rowid  IN (".implode(', ', $arr_resp).")";
					$sql .= " AND ec.fk_socpeople IN (".implode(', ', $arr_resp).")";
				}

				if(!empty($arr_proj) && $option ='all') {
					$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
				}
				
				if(!empty($arr_agences) && $option ='all') {
					$sql .= " AND pe.agenceconcerne  IN (".implode(', ', $arr_agences).")";
				}
			}

			if($option == 'resp') {
				$i = 1;
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);
					foreach($agences as $manager => $agenceid) {
						$sql .= " pe.agenceconcerne =".((int) $agenceid);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}
						$i++;
					}
				}
			}
			
		

		$sql .= " AND t.elementtype = 'task'";
		$sql .= " AND t.element_date > NOW() - INTERVAL 5 YEAR";
		// $sql .= " AND t.element_date >= '".$db->idate(dol_time_plus_duree($date_start, -1, 'm'))."'";
		$sql .= " AND t.element_date < NOW() + INTERVAL 3 YEAR";
		// $sql .= " AND t.element_date <= '".$db->idate($date_end, '%Y-%m-%d')."'";
		$sql .= " GROUP BY dm, u.rowid";
		$sql .= " ORDER BY dm";
	
		dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$hs25 =  $obj->amount_hs25 == null ? 0 : $obj->amount_hs25;
				$hs50 = $obj->amount_hs50 == null ? 0 : $obj->amount_hs50;
				$cost = $obj->cost + $hs25 + $hs50;
				$salaries[] = array('date' => $obj->dm, 'amount' => $cost);
	
				$i++;
			} 

			return $salaries;
		} else {
			$this->error = $db->error();
			return -1;
		}
	
	}

	 /**
	 * Get agences by user.
	 * 
	 */
	public function getAgencesBySoc()
	{
		global $conf, $db, $langs, $user;
		$name = 'OPTIM Industries';

		// $sql = "SELECT DISTINCT u.rowid as userid, u.lastname, u.firstname, u.email, u.statut as status, u.entity, s.rowid as agenceid, s.nom as name, s.name_alias";
		// $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		// $sql .= ", ".MAIN_DB_PREFIX."user as u";
		// $sql .= " , ".MAIN_DB_PREFIX."societe as s";
		// $sql .= " WHERE u.entity in (0, 1) AND u.rowid = sc.fk_user";
		// $sql .= " AND s.rowid = sc.fk_soc";
		// $sql .= " AND s.nom = '".$db->escape($name)."'";
		
		// Optim Industries and get filial dynamically
		$sql_get_id = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE nom = '" . $db->escape($name) . "' OR rowid = 57";
		$res_get_id = $db->query($sql_get_id);

		if ($res_get_id && $db->num_rows($res_get_id) > 0) {
			$obj = $db->fetch_object($res_get_id);
			$socid = $obj->rowid; // ID de la société
		} else {
			die("Société non trouvée.");
		}

		$sql = "SELECT DISTINCT u.rowid as userid, u.lastname, u.firstname, u.email, u.statut as status, u.entity, s.rowid as agenceid, s.nom as name, s.name_alias";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= ", ".MAIN_DB_PREFIX."user as u";
		$sql .= " , ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE u.entity in (0, 1) AND u.rowid = sc.fk_user";
		$sql .= " AND s.rowid = sc.fk_soc";
		$sql .= " AND (s.nom = '" . $db->escape($name) . "' OR s.parent = " . (int) $socid . " OR s.rowid = 57)";
	

		$sql .= " ORDER BY u.lastname ASC, u.firstname ASC";
	

		dol_syslog(get_class($this)."::getAgencesBySoc", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$agences[$obj->userid] = $obj->agenceid;
				
			
				$i++;
			} 

			return array_unique($agences);
		} else {
			$this->error = $db->error();
			return -1;
		}
	}


	 /**
	 * Get agences.
	 * 
	 */
	public function getAgences()
	{
		global $conf, $db, $langs, $user;
		$name = 'OPTIM Industries';
		// $filial = '157';

		// $sql = "SELECT DISTINCT u.rowid AS userid, s.rowid AS socid, u.lastname, u.firstname, u.email, u.statut AS status, u.entity, s.rowid AS agenceid, s.nom AS name, s.name_alias";
		// $sql .= " FROM " . MAIN_DB_PREFIX . "societe_commerciaux AS sc";
		// $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "user AS u ON u.rowid = sc.fk_user";
		// $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe AS s ON s.rowid = sc.fk_soc";
		// $sql .= " WHERE u.entity IN (0, 1)";
		// $sql .= " AND s.nom = '" . $db->escape($name) . "'";
		// $sql .= " OR s.parent = " . (int) $filial;

		// $name = 'OPTIM Industries';

		// Récupération de l'ID de la société dont le nom est $name
		$sql_get_id = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE nom = '" . $db->escape($name) . "' OR rowid = 157";
		$res_get_id = $db->query($sql_get_id);

		if ($res_get_id && $db->num_rows($res_get_id) > 0) {
			$obj = $db->fetch_object($res_get_id);
			$socid = $obj->rowid; // ID de la société
		} else {
			die("Société non trouvée.");
		}

		$sql = "SELECT DISTINCT u.rowid AS userid, s.rowid AS socid, u.lastname, u.firstname, u.email, u.statut AS status, u.entity, s.rowid AS agenceid, s.nom AS name, s.name_alias";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe_commerciaux AS sc";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "user AS u ON u.rowid = sc.fk_user";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe AS s ON s.rowid = sc.fk_soc";
		$sql .= " WHERE u.entity IN (0, 1)";
		$sql .= " AND (s.nom = '" . $db->escape($name) . "' OR s.parent = " . (int) $socid . " OR s.rowid = 157)";
	

		$sql .= " ORDER BY u.lastname ASC, u.firstname ASC";
	

		dol_syslog(get_class($this)."::getAgences", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$agences[$obj->socid] = $obj->name_alias;
				
			
				$i++;
			} 

			return array_unique($agences);
		} else {
			$this->error = $db->error();
			return -1;
		}
	}


		/**
	 * Retrieve data values from queries for chart generation to use domain info data.
	 *
	 * @param string $option Specifies filtering options.
	 * @param string $date_start Start date for data range.
	 * @param string $date_end End date for data range.
	 * 
	 * @return array Associative array of data (e.g., date and amount) for the chart.
	 */
	public function getAgencesByDomainesValues($option, $date_start, $date_end)
	{
		global $user;
		$vals['invoices'] = $this->getAgencesDomaineFacture($option, $date_start, $date_end);
		$vals['salaries'] = $this->employeeCostByProjectDomaine($option, $date_start, $date_end);
		$vals['facture_fourn'] = $this->getFactureFournDomaine($option, $date_start, $date_end);
		// $vals['colors'] = $this->getAgenceByDomainColors();
		
		// $vals['note'] = $this->getExpenseReport($option, $arr_agences, $arr_resp, $arr_proj);
		// $vals['soc'] = $this->getSocialContribution($option, $arr_agences, $arr_resp, $arr_proj);
		return $vals;
	}

	public function getAgenceByDomainColors()
	{
		global $conf, $db, $langs, $user;
		$i = 1;
 
		$sql = "SELECT pe.agenceconcerne as agence_id, se.code as name_alias, pe.domaine, pe.color_domain";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_extrafields as pe";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = pe.agenceconcerne";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
		$sql .= " WHERE pe.agenceconcerne is not null";
		$sql .= " GROUP BY pe.agenceconcerne, pe.domaine";
 
 
		dol_syslog(get_class($this)."::getAgenceByDomainColors", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		    // var_dump($obj);
				$domainColor[$obj->name_alias][$obj->domaine] = $obj->color_domain;
				$i++;
			} 
			return array_filter($domainColor);
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

		/**
	 * Retrieve data values from queries for chart generation.
	 *
	 * @param string $option Specifies filtering options.
	 * @param string $date_start Start date for data range.
	 * @param string $date_end End date for data range.
	 * 
	 * @return array Associative array of data (e.g., date and amount) for the chart.
	 */
	public function getAgencesValues($option, $date_start, $date_end)
	{
		// Retrieve invoice amounts by domain and agency.
		$vals['invoices'] = $this->getAgencesFacture($option, $date_start, $date_end);
		
		// Retrieve employee salary costs by project and domain.
		$vals['salaries'] = $this->employeesCostByProjects($option, $date_start, $date_end);
		
		// Retrieve supplier invoice costs by domain.
		$vals['facture_fourn'] = $this->getFactureFourn($option, $date_start, $date_end);

		return $vals;
	}


		/**
	 * get salaries of employees by reference to the projects
	 * 
	 * @param string option to filter by option either resp or all py default
	 */
	// public function employeeCostByProject($option,$arr_agences, $arr_resp,  $arr_proj)
	// {
		
	// 	global $db, $user;

	// 	$sql = "SELECT sum((t.thm) * (t.element_duration / 3600)) as cost, DATE_FORMAT(t.element_date,'%Y-%m') as dm";
	// 	$sql .= ", SUM(hs.heure_sup_25_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm * 0.25").") as amount_hs25,";
    //     $sql .= " SUM(hs.heure_sup_50_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm * 0.5").") as amount_hs50, u.rowid as userid";

	// 	$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe ON p.rowid = pe.fk_object";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs ON hs.fk_projet_task_time = t.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = p.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on ec.fk_socpeople = u.rowid";
	// 	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
	// 	$sql .= " WHERE 1 = 1";

	// 		if(!empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
				
	// 			if(!empty($arr_resp) && $option ='all') {
	// 				$sql .= " AND tc.element = 'project'";
	// 				$sql .= " AND tc.source = 'internal'";
	// 				$sql .= " AND tc.code = 'PROJECTLEADER'";
	// 				$sql .= " AND ec.element_id = p.rowid";
	// 				// $sql .= " AND u.rowid  IN (".implode(', ', $arr_resp).")";
	// 				$sql .= " AND ec.fk_socpeople IN (".implode(', ', $arr_resp).")";
	// 			}

	// 			if(!empty($arr_proj) && $option ='all') {
	// 				$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
	// 			}
				
	// 			if(!empty($arr_agences) && $option ='all') {
	// 				$sql .= " AND pe.agenceconcerne  IN (".implode(', ', $arr_agences).")";
	// 			}
	// 		}

	// 		if($option == 'resp') {
	// 			$i = 1;
	// 			$sql .= " AND";
	// 			if(!empty(array_filter($this->getAgencesBySoc()))) {
	// 				$agences = array_unique($this->getAgencesBySoc());
	// 				array_filter($agences);
	// 				foreach($agences as $manager => $agenceid) {
	// 					$sql .= " pe.agenceconcerne =".((int) $agenceid);
	// 					if($i < sizeof($agences)) {
	// 						$sql .= " AND";
	// 					}
	// 					$i++;
	// 				}
	// 			}
	// 		}
			
		

	// 	$sql .= " AND t.elementtype = 'task'";
	// 	$sql .= " GROUP BY dm, u.rowid";
	// 	$sql .= " ORDER BY dm";
	
	// 	dol_syslog(get_class($this)."::employeeCostByProject", LOG_DEBUG);
	// 	$resql = $db->query($sql);
		
	// 	if ($resql) {
	// 		$num = $db->num_rows($resql);
			
	// 		$i = 0;
	// 		while ($i < $num) {
	// 			$obj = $db->fetch_object($resql);
		
	// 			$hs25 =  $obj->amount_hs25 == null ? 0 : $obj->amount_hs25;
	// 			$hs50 = $obj->amount_hs50 == null ? 0 : $obj->amount_hs50;
	// 			$cost = $obj->cost + $hs25 + $hs50;
	// 			$salaries[] = array('date' => $obj->dm, 'amount' => $cost);
	
	// 			$i++;
	// 		} 

	// 		return $salaries;
	// 	} else {
	// 		$this->error = $db->error();
	// 		return -1;
	// 	}
	
	// }

	/**
	 * get salaries for each month
	 * 
	 * @return array of date payment and employees salaries amount by month
	 */
	public function getSalariesByMonth()
	{
		global $db, $user;
		$childids = $user->getAllChildIds(1);

		$sql = "SELECT u.rowid as uid,";
		$sql .= " DATE_FORMAT(s.datesp,'%Y-%m') as datesp, s.dateep, ";
		$sql .= " SUM(ps.amount) as alreadypayed";
		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_salary as ps ON (ps.fk_salary = s.rowid) ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON (s.fk_typepayment = pst.id) ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account ba ON (ba.rowid = s.fk_account), ";
		//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_salary as ps ON ps.fk_salary = s.rowid, ";
		$sql .= " ".MAIN_DB_PREFIX."user as u";

		$sql .= " WHERE u.rowid = s.fk_user";
		$sql .= " AND s.entity IN (".getEntity('payment_salaries').")";
		if (empty($user->rights->salaries->readall)) {
			$sql .= " AND s.fk_user IN (".$db->sanitize(join(',', $childids)).")";
		}

		$sql .= " GROUP BY DATE_FORMAT(s.datesp,'%Y-%m')";

		dol_syslog(get_class($this)."::getSalariesByMonth", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$salaries[] = array('date' => $obj->datesp, 'amount' => $obj->alreadypayed);
				
				$i++;
			} 
			return $salaries;
		} else {
			$this->error = $db->error();
			return -1;
		}

	}

	/**
	 * get donation and dunning payment 
	 * 
	 * @return array date, donanation and dunnig payment by month
	 */
	public function getDonation()
	{
		global $db;

		$sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."don as p";
		$sql .= " WHERE p.entity IN (".getEntity('donation').")";
		$sql .= " AND fk_statut in (1,2)";

		$sql .= " GROUP BY p.societe, p.firstname, p.lastname, dm";

		dol_syslog(get_class($this)."::getDonation", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$donation[] = array('date' => $obj->dm, 'amount' => $obj->amount);
				
				$i++;
			} 
			return $donation;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get various payment 
	 * 
	 * @return array date and various payment amount by month
	 */
	public function getVariousPayment()
	{
		global $db;

		$sql = "SELECT date_format(p.datep, '%Y-%m') AS dm, SUM(p.amount) AS amount FROM ".MAIN_DB_PREFIX."payment_various as p";
		$sql .= " WHERE p.entity IN (".getEntity('variouspayment').")";
		$sql .= ' AND p.sens = 0';
		$sql .= ' GROUP BY dm';

		dol_syslog(get_class($this)."::getVariousPayment", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$variouspay[] = array('date' => $obj->dm, 'amount' => $obj->amount);
				
				$i++;
			} 
			return $variouspay;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	
	/**
	 * get payment loan
	 * 
	 * @return array date and payment loan by month
	 */
	public function getPaymentLoan()
	{
	   global $db;

	   $sql = "SELECT date_format(p.datep, '%Y-%m') AS dm, SUM(p.amount_capital + p.amount_insurance + p.amount_interest) AS amount";
	   $sql .= " FROM ".MAIN_DB_PREFIX."payment_loan AS p, ".MAIN_DB_PREFIX."loan as l";
	   $sql .= " WHERE l.entity IN (".getEntity('variouspayment').")";
	   $sql .= " AND p.fk_loan = l.rowid";
	   $sql .= ' GROUP BY dm';

	   dol_syslog(get_class($this)."::getPaymentLoan", LOG_DEBUG);
	   $resql = $db->query($sql);
	   
	   if ($resql) {
		   $num = $db->num_rows($resql);
		   
		   $i = 0;
		   while ($i < $num) {
			   $obj = $db->fetch_object($resql);
	   
			   $loan[] = array('date' => $obj->dm, 'amount' => $obj->amount);
			   
			   $i++;
		   } 
		   return $loan;
	   } else {
		   $this->error = $db->error();
		   return -1;
	   }
	}

	/**
	* Get facture from database. without agencies tagged more than one item.
	* 
	*/
	public function getAgencesDomaineFacture($option, $date_start, $date_end)
	{
		global $conf, $db, $langs, $user;
		$i = 1;
 
		$sql = "SELECT f.rowid, date_format(f.datef, '%Y-%m') AS dm, date_format(f.datef, '%Y') AS dy, SUM(f.total_ht) as amount_ht, p.rowid as projid, f.ref";
		$sql .= " ,fe.agence, se.code as name_alias, se.couleur_a as color_ca, fe.domaine, f.ref"; 
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = fe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = f.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = fe.agence";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
		$sql .= " WHERE fe.agence is not null AND f.datef is not null";
		// $sql .= " WHERE 1 = 1";
		$sql .= " AND fe.pv_reception = 1";
 
		$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <='".$db->idate($date_end)."'";
		// $sql .= " AND fe.agence IS NOT NULL AND fe.domaine IS NOT NULL";
		$sql .= " AND fe.agence NOT LIKE '%,%' AND fe.agence REGEXP '^[0-9]+$'";
		
		$agencesAutorisees = array_keys($this->getAgences());

	if (!empty($agencesAutorisees)) {
		$conditions = [];
		foreach ($agencesAutorisees as $idAgence) {
			// Vérifie si l'utilisateur a le droit pour une agence spécifique
			if ($user->hasRight('addoncomm', 'box_indicateur_ca', 'read_by_' . $idAgence)) {
				$conditions[] = 's.rowid = ' . (int)$idAgence;
			}
		}

		// Ajoute les conditions à la requête SQL si des agences sont autorisées
		if (!empty($conditions)) {
			$sql .= ' AND (' . implode(' OR ', $conditions) . ')';
		}
	}

		if($option == "Year") {
			$sql .= " GROUP BY fe.agence, fe.domaine";
			$sql .= " ORDER BY amount_ht DESC";
		}
		if($option == "Month") {
			$sql .= " GROUP BY fe.agence, fe.domaine, dm";
			$sql .= " ORDER BY amount_ht DESC";
		}


	   dol_syslog(get_class($this)."::getAgencesDomaineFacture", LOG_DEBUG);
	   $resql = $db->query($sql);
	   
	   if ($resql) {
		   $num = $db->num_rows($resql);
		   
		   $i = 0;
		   while ($i < $num) {
			$obj = $db->fetch_object($resql);
				if($option == "Year") {
					$filterdate = $obj->dy;
					
				}
				elseif($option == "Month") {
					$filterdate = $obj->dm;
				}
			 
			   $factures[] = array('date' => $filterdate, 'amount' => $obj->amount_ht, 'agence' => $obj->name_alias, 'color_ca' => $obj->color_ca, 'domaine' => $obj->domaine);
			   $i++;
		   } 
		   return array_filter($factures);
	   } else {
		   $this->error = $db->error();
		   return -1;
	   }
	}

	/**
	* Get facture from database. with agencies tagged more than one item.
	* 
	*/
	public function getFactureByAgencyCount($option, $date_start, $date_end)
	{
		global $conf, $db, $langs, $user;
		$i = 1;
 
		$sql = "SELECT f.rowid, date_format(f.datef, '%Y-%m') AS dm, date_format(f.datef, '%Y') AS dy, SUM(f.total_ht) as amount_ht, p.rowid as projid, f.ref";
		$sql .= " ,fe.agence, se.code as name_alias, se.couleur_a as color_ca, fe.domaine, f.ref"; 
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = fe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = f.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = fe.agence";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
		$sql .= " WHERE fe.agence is not null AND f.datef is not null";
		// $sql .= " WHERE 1 = 1";
		$sql .= " AND fe.pv_reception = 1";
 
		$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <='".$db->idate($date_end)."'";
		// $sql .= " AND fe.agence IS NOT NULL AND fe.domaine IS NOT NULL";
		$sql .= " AND fe.agence LIKE '%,%' AND fe.agence REGEXP '^[0-9]+(,[0-9]+)+$'";

		if($option == "Year") {
			$sql .= " GROUP BY fe.agence";
			$sql .= " ORDER BY amount_ht DESC";
		}
		if($option == "Month") {
			$sql .= " GROUP BY fe.agence";
			$sql .= " ORDER BY amount_ht DESC";
		}


	   dol_syslog(get_class($this)."::getFactureByAgencyCount", LOG_DEBUG);
	   $resql = $db->query($sql);
	   
	   if ($resql) {
		   $num = $db->num_rows($resql);
		   
		   $i = 0;
		   while ($i < $num) {
			$obj = $db->fetch_object($resql);
				if($option == "Year") {
					$filterdate = $obj->dy;
					
				}
				elseif($option == "Month") {
					$filterdate = $obj->dm;
				}
			 
			   $factures[] = array('date' => $filterdate, 'amount' => $obj->amount_ht, 'agence' => $obj->name_alias, 'agencecount' => $obj->agence, 'color_ca' => $obj->color_ca, 'domaine' => $obj->domaine);
			   $i++;
		   } 
		   return array_filter($factures);
	   } else {
		   $this->error = $db->error();
		   return -1;
	   }
	}

	/**
	 * Get salaries of employees by reference to the projects without projet agencies tagged more than one agency
	 * 
	 * @param string $option Filter by "Year" or "Month". Default is "all".
	 * @param string $date_start Start date in YYYY-MM-DD format.
	 * @param string $date_end End date in YYYY-MM-DD format.
	 * @return array Array of salaries or -1 in case of error.
	 */
	public function employeeCostByProjectDomaine($option, $date_start, $date_end)
	{
		global $db, $user;
		$agencyName = $this->getAgences();
		// Heures supplémentaires pour le domaine et l'agence
		$heureSup = $this->employeeHeureSupByDomains($option, $date_start, $date_end);

		$sql = "SELECT 
					SUM(t.thm * (t.element_duration / 3600)) AS cost, 
					DATE_FORMAT(t.element_date, '%Y') AS dy, 
					DATE_FORMAT(t.element_date, '%Y-%m') AS dm, 
					pe.agenceconcerne AS agence,
					se.code AS name_alias, s.rowid as socid,
					pe.domaine, 
					se.couleur_a AS color_ca, 
					se.couleur_b AS color_exp 
				FROM ".MAIN_DB_PREFIX."element_time AS t
				LEFT JOIN ".MAIN_DB_PREFIX."projet_task AS pt ON pt.rowid = t.fk_element
				LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON p.rowid = pt.fk_projet
				LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS pe ON p.rowid = pe.fk_object
				LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = pe.agenceconcerne
				LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields AS se ON s.rowid = se.fk_object
				WHERE pe.agenceconcerne IS NOT NULL 
				AND pe.domaine IS NOT NULL
				AND t.element_date BETWEEN '".$db->idate($date_start)."' AND '".$db->idate($date_end)."'
				AND t.elementtype = 'task'
				AND p.entity IN (1)"; 
			$sql .= " AND pe.agenceconcerne NOT LIKE '%,%' AND pe.agenceconcerne REGEXP '^[0-9]+$'";
				
				$agencesAutorisees = array_keys($this->getAgences());

				if (!empty($agencesAutorisees)) {
					$conditions = [];
					foreach ($agencesAutorisees as $idAgence) {
						// Vérifie si l'utilisateur a le droit pour une agence spécifique
						if ($user->hasRight('addoncomm', 'box_indicateur_ca', 'read_by_' . $idAgence)) {
							$conditions[] = 's.rowid = ' . (int)$idAgence;
						}
					}
			
					// Ajoute les conditions à la requête SQL si des agences sont autorisées
					if (!empty($conditions)) {
						$sql .= ' AND (' . implode(' OR ', $conditions) . ')';
					}
				}

		if ($option == "Year") {
			$sql .= " GROUP BY agence, pe.domaine, dy ORDER BY cost";
		} elseif ($option == "Month") {
			$sql .= " GROUP BY agence, pe.domaine, dm ORDER BY dm";
		}

		dol_syslog(get_class($this)."::employeeCostByProjectDomaine", LOG_DEBUG);

		$resql = $db->query($sql);
		if (!$resql) {
			$this->error = $db->error();
			return -1;
		}

		$salaries = array();
		$costByAgencyDomain = array();
		while ($obj = $db->fetch_object($resql)) {
			$filterdate = ($option == "Year") ? $obj->dy : $obj->dm;

			// Heures supplémentaires
			$hs25 = $heureSup[$obj->agence][$obj->domaine][$filterdate]['hs25'] ?? 0;
			$hs50 = $heureSup[$obj->agence][$obj->domaine][$filterdate]['hs50'] ?? 0;

			$cost = ($obj->cost ?? 0) + $hs25 + $hs50;

			// Accumulation des coûts par agence et domaine
			$costByAgencyDomain[$obj->name_alias][$obj->domaine] = ($costByAgencyDomain[$obj->name_alias][$obj->domaine] ?? 0) + $cost;
		
	
			// Ajout au tableau des salaires
			$salaries[] = array(
				'date' => $filterdate,
				'amount' => $cost,
				'agence' => $obj->name_alias,
				'color_exp' => $obj->color_exp,
				'domaine' => $obj->domaine,
			);
		}

		return $salaries;
	}

	/**
	 * Get salaries of employees by reference to the projects without projet agencies tagged more than one agency
	 * 
	 * @param string $option Filter by "Year" or "Month". Default is "all".
	 * @param string $date_start Start date in YYYY-MM-DD format.
	 * @param string $date_end End date in YYYY-MM-DD format.
	 * @return array Array of salaries or -1 in case of error.
	 */
	public function getProjectByAgencyCount($option, $date_start, $date_end)
	{
		global $db;

		// Heures supplémentaires pour le domaine et l'agence
		$heureSup = $this->employeeHeureSupByDomains($option, $date_start, $date_end);

		$sql = "SELECT 
					SUM(t.thm * (t.element_duration / 3600)) AS cost, 
					DATE_FORMAT(t.element_date, '%Y') AS dy, 
					DATE_FORMAT(t.element_date, '%Y-%m') AS dm, 
					pe.agenceconcerne AS agence,
					se.code AS name_alias, 
					pe.domaine, 
					se.couleur_a AS color_ca, 
					se.couleur_b AS color_exp 
				FROM ".MAIN_DB_PREFIX."element_time AS t
				LEFT JOIN ".MAIN_DB_PREFIX."projet_task AS pt ON pt.rowid = t.fk_element
				LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON p.rowid = pt.fk_projet
				LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS pe ON p.rowid = pe.fk_object
				LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = pe.agenceconcerne
				LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields AS se ON s.rowid = se.fk_object
				WHERE pe.agenceconcerne IS NOT NULL 
				AND pe.domaine IS NOT NULL
				AND t.element_date BETWEEN '".$db->idate($date_start)."' AND '".$db->idate($date_end)."'
				AND t.elementtype = 'task'
				AND p.entity IN (1)"; 
			$sql .= " AND pe.agenceconcerne LIKE '%,%' AND pe.agenceconcerne REGEXP '^[0-9]+(,[0-9]+)+$'";
				//   AND p.fk_statut IN (0,1,2)";

		if ($option == "Year") {
			$sql .= " GROUP BY agence, pe.domaine, dy ORDER BY cost";
		} elseif ($option == "Month") {
			$sql .= " GROUP BY agence, pe.domaine, dm ORDER BY dm";
		}

		dol_syslog(get_class($this)."::getProjectByAgencyCount", LOG_DEBUG);

		$resql = $db->query($sql);
		if (!$resql) {
			$this->error = $db->error();
			return -1;
		}

		$salaries = array();
		$costByAgencyDomain = array();
		while ($obj = $db->fetch_object($resql)) {
			$filterdate = ($option == "Year") ? $obj->dy : $obj->dm;

			// Heures supplémentaires
			$hs25 = $heureSup[$obj->agence][$obj->domaine][$filterdate]['hs25'] ?? 0;
			$hs50 = $heureSup[$obj->agence][$obj->domaine][$filterdate]['hs50'] ?? 0;

			$cost = ($obj->cost ?? 0) + $hs25 + $hs50;

			// Accumulation des coûts par agence et domaine
			$costByAgencyDomain[$obj->name_alias][$obj->domaine] = ($costByAgencyDomain[$obj->name_alias][$obj->domaine] ?? 0) + $cost;

			// Ajout au tableau des salaires
			$salaries[] = array(
				'date' => $filterdate,
				'amount' => $cost,
				'agence' => $obj->name_alias,
				'agencecount' => $obj->agence,
				'color_exp' => $obj->color_exp,
				'domaine' => $obj->domaine,
			);
		}

		return $salaries;
	}

	
   /**
	* Get facture fournisseur witouth facture_fourn agencies tagged more than 1 item
	*/
	public function getFactureFournDomaine($option, $date_start, $date_end)
	{
		global $conf, $db, $langs, $user;
 
		$sql = "SELECT date_format(faf.datef,'%Y-%m') as dm, date_format(faf.datef,'%Y') as dy, SUM(faf.total_ht) as amount_ht";
		$sql .= " ,se.code as name_alias, pe.domaine";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as faf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn_extrafields as fafe on faf.rowid = fafe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = faf.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
 
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = fafe.agence";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
		$sql .= " WHERE fafe.agence is not null AND faf.datef is not null";
	  
		$sql .= " AND faf.fk_statut IN (1,2)";
		if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
			$sql .= " AND faf.type IN (0,1,2,5)";
		} else {
			$sql .= " AND faf.type IN (0,1,2,3,5)";
		}
		$sql .= " AND faf.entity IN (".getEntity('invoice').")";
 
		$sql .= " AND faf.datef >= '".$db->idate($date_start)."' AND faf.datef <='".$db->idate($date_end)."'";
		$sql .= " AND fafe.agence NOT LIKE '%,%' AND fafe.agence REGEXP '^[0-9]+$'";

		$agencesAutorisees = array_keys($this->getAgences());

	if (!empty($agencesAutorisees)) {
		$conditions = [];
		foreach ($agencesAutorisees as $idAgence) {
			// Vérifie si l'utilisateur a le droit pour une agence spécifique
			if ($user->hasRight('addoncomm', 'box_indicateur_ca', 'read_by_' . $idAgence)) {
				$conditions[] = 's.rowid = ' . (int)$idAgence;
			}
		}

		// Ajoute les conditions à la requête SQL si des agences sont autorisées
		if (!empty($conditions)) {
			$sql .= ' AND (' . implode(' OR ', $conditions) . ')';
		}
	}
 
		if($option == "Year") {
		 $sql .= " GROUP BY pe.agenceconcerne, pe.domaine, dy";
		 $sql .= " ORDER BY amount_ht DESC";
		 }
 
		 if($option == "Month") {
			 $sql .= " GROUP BY pe.agenceconcerne, pe.domaine, dm";
			 $sql .= " ORDER BY dm";
		 }
	
 
		dol_syslog(get_class($this)."::getFactureFournDomaine", LOG_DEBUG);
		$resql = $db->query($sql);
	
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				
				if($option == "Year") {
					 $filterdate = $obj->dy;
				 }elseif($option == "Month") {
					 $filterdate = $obj->dm;
				 }
				 
				
				$facturesfourn[] = array('date' => $filterdate, 'amount' => $obj->amount_ht, 'agence' => $obj->name_alias, 'domaine' => $obj->domaine);
				
			
				$i++;
			} 
			// var_dump($facturesfourn);
			return $facturesfourn;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	* Get facture fournisseur witouth facture_fourn agencies tagged more than 1 item
	*/
	public function getSupplierInvoicesByAgencyCount($option, $date_start, $date_end)
	{
		global $conf, $db, $langs, $user;
 
		$sql = "SELECT date_format(faf.datef,'%Y-%m') as dm, date_format(faf.datef,'%Y') as dy, SUM(faf.total_ht) as amount_ht";
		$sql .= " ,se.code as name_alias, pe.domaine, fafe.agence as agence";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as faf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn_extrafields as fafe on faf.rowid = fafe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = faf.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
 
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = fafe.agence";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
		$sql .= " WHERE fafe.agence is not null AND faf.datef is not null";
	  
		$sql .= " AND faf.fk_statut IN (1,2)";
		if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
			$sql .= " AND faf.type IN (0,1,2,5)";
		} else {
			$sql .= " AND faf.type IN (0,1,2,3,5)";
		}
		$sql .= " AND faf.entity IN (".getEntity('invoice').")";
 
		$sql .= " AND faf.datef >= '".$db->idate($date_start)."' AND faf.datef <='".$db->idate($date_end)."'";
		$sql .= " AND fafe.agence LIKE '%,%' AND fafe.agence REGEXP '^[0-9]+(,[0-9]+)+$'";	
 
		if($option == "Year") {
		 $sql .= " GROUP BY agence, dy";
		 $sql .= " ORDER BY amount_ht DESC";
		 }
 
		 if($option == "Month") {
			 $sql .= " GROUP BY agence, dm";
			 $sql .= " ORDER BY dm";
		 }
	
 
		dol_syslog(get_class($this)."::getSupplierInvoicesByAgencyCount", LOG_DEBUG);
		$resql = $db->query($sql);
	
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				
				if($option == "Year") {
					 $filterdate = $obj->dy;
				 }elseif($option == "Month") {
					 $filterdate = $obj->dm;
				 }
				 
				$facturesfourn[] = array('date' => $filterdate, 'amount' => $obj->amount_ht, 'agencecount' => $obj->agence, 'domaine' => $obj->domaine);
				
			
				$i++;
			} 
		
			return $facturesfourn;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	* Get facture from database. Get also lines.
	* 
	*/
   public function getAgencesFacture($option, $date_start, $date_end)
   {
	   global $conf, $db, $langs, $user;
	   $i = 1;

	   $sql = "SELECT f.rowid, date_format(f.datef, '%Y-%m') AS dm, date_format(f.datef, '%Y') AS dy, SUM(f.total_ht) as amount_ht, p.rowid as projid, f.ref";
	   $sql .= " ,fe.agence, se.code as name_alias, se.couleur_a as color_ca, fe.domaine"; 
	   $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = fe.fk_object";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = f.fk_projet";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = fe.agence";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
	   $sql .= " WHERE fe.agence is not null AND f.datef is not null";

	   $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <='".$db->idate($date_end)."'";
		

	   $sql .= " AND fe.pv_reception = 1";	
		//    $sql.= " GROUP BY dm";
		if($option == "Year") {
			$sql .= " GROUP BY fe.agence";
			$sql .= " ORDER BY amount_ht DESC";
		}
		if($option == "Month") {
			$sql .= " GROUP BY fe.agence, dm";
			$sql .= " ORDER BY dm";
		}


	   dol_syslog(get_class($this)."::getFacture", LOG_DEBUG);
	   $resql = $db->query($sql);
	   
	   if ($resql) {
		   $num = $db->num_rows($resql);
		   
		   $i = 0;
		   while ($i < $num) {
			$obj = $db->fetch_object($resql);
				if($option == "Year") {
					$filterdate = $obj->dy;
					
				}
				elseif($option == "Month") {
					$filterdate = $obj->dm;
				}

				// var_dump($obj->color_ca);
			 
			   $factures[] = array('date' => $filterdate, 'amount' => $obj->amount_ht, 'agence' => $obj->name_alias, 'color_ca' => $obj->color_ca);
			   $i++;
		   } 
		   return array_filter($factures);
	   } else {
		   $this->error = $db->error();
		   return -1;
	   }
   }

   /**
	* Get facture from database. Get also lines.
	* 
	*/
	public function getAgencesFacture2($date_start, $date_end)
	{
		global $conf, $db, $langs, $user;
		$i = 1;
 
		$sql = "SELECT f.rowid, date_format(f.datef, '%Y') AS dm, SUM(f.total_ht) as amount_ht, p.rowid as projid, f.ref";
		$sql .= " ,pe.agenceconcerne as agence, s.name_alias"; 
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		// $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe on f.rowid = fe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = f.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = pe.agenceconcerne";
		$sql .= " WHERE pe.agenceconcerne is not null";
 
		$sql .= " AND f.datef BETWEEN '".$db->idate($date_start)."' AND '".$db->idate($date_end)."'";
		 
	
	    // $sql .= " AND fe.pv_reception = 1";	
		// $sql .= " AND f.fk_statut = 2";
	 //    $sql.= " GROUP BY dm";
		$sql .= " GROUP BY agence, dm";
		$sql .= " ORDER BY dm";
 
 
		dol_syslog(get_class($this)."::getFacture", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		 //    var_dump($obj);
				$factures[] = array('date' => $obj->dm, 'amount' => $obj->amount_ht, 'agence' => $obj->name_alias);
				$i++;
			} 
			return array_filter($factures);
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

   /**
	* Get facture fournisseur
	*/
   public function getFactureFourn($option, $date_start, $date_end)
   {
	   global $conf, $db, $langs, $user;

	   $sql = "SELECT date_format(faf.datef,'%Y-%m') as dm, date_format(faf.datef,'%Y') as dy, SUM(faf.total_ht) as amount_ht";
	   $sql .= " ,se.code as name_alias";
	   $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as faf";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn_extrafields as fafe on faf.rowid = fafe.fk_object";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on p.rowid = faf.fk_projet";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on p.rowid = pe.fk_object";

	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = fafe.agence";
	   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object";
	   $sql .= " WHERE fafe.agence is not null AND faf.datef is not null";
	 
	   $sql .= " AND faf.fk_statut IN (1,2)";
	   if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
		   $sql .= " AND faf.type IN (0,1,2,5)";
	   } else {
		   $sql .= " AND faf.type IN (0,1,2,3,5)";
	   }
	   $sql .= " AND faf.entity IN (".getEntity('invoice').")";

	   $sql .= " AND faf.datef >= '".$db->idate($date_start)."' AND faf.datef <='".$db->idate($date_end)."'";

	   if($option == "Year") {
		$sql .= " GROUP BY fafe.agence, dy";
		$sql .= " ORDER BY amount_ht DESC";
		}

		if($option == "Month") {
			$sql .= " GROUP BY fafe.agence, dm";
			$sql .= " ORDER BY dm";
		}
   

	   dol_syslog(get_class($this)."::getFactureFourn", LOG_DEBUG);
	   $resql = $db->query($sql);
   
	   if ($resql) {
		   $num = $db->num_rows($resql);
		   
		   $i = 0;
		   while ($i < $num) {
			   $obj = $db->fetch_object($resql);
			   
			   	if($option == "Year") {
					$filterdate = $obj->dy;
				}elseif($option == "Month") {
					$filterdate = $obj->dm;
				}

			   $facturesfourn[] = array('date' => $filterdate, 'amount' => $obj->amount_ht, 'agence' => $obj->name_alias);
			   
		   
			   $i++;
		   } 
		   // var_dump($facturesfourn);
		   return $facturesfourn;
	   } else {
		   $this->error = $db->error();
		   return -1;
	   }
   }

   /**
	 * get expense report
	 * 
	 * @return array date and exepnense report amount 
	 */
	public function getExpenseReport($option, $arr_agences, $arr_resp, $arr_proj)
	{
		global $db;

		$sql = "SELECT date_format(r.date_debut,'%Y-%m') as dm, sum(r.total_ht) as amount_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as r";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user_author";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_det as de ON r.rowid = de.fk_expensereport";
	
		if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on de.fk_projet = p.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on pe.fk_object = p.rowid";

			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = pe.fk_object";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

			$sql .= " WHERE 1 = 1";
			$i = 1;

			if(!empty($arr_resp) && $option ='all') {
				$sql .= " AND tc.element = 'project'";
				$sql .= " AND tc.source = 'internal'";
				$sql .= " AND tc.code = 'PROJECTLEADER'";
		
				$sql .= " AND ec.fk_socpeople  IN (".implode(', ', $arr_resp).")";
				}
	
			if(!empty($arr_agences) && $option == 'all') {
				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
			}

			if(!empty($arr_proj) && $option == 'all') {
				$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
			}
	
			if($option == 'resp') {
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);

					foreach($agences as $manager => $agence) {
						$sql .= " pe.agenceconcerne =".((int) $agence);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}

						$i++;
					}
				}
			}
		}else{
			$sql .= " WHERE 1 = 1";
		}
		
		
		$sql .= " AND r.entity IN (".getEntity('expensereport').")";
		$sql .= " AND r.fk_statut = 6";
		$sql .= " GROUP BY dm";

		dol_syslog(get_class($this)."::getExpenseReport", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
		
				$exepnesereport[] = array('date' => $obj->dm, 'amount' => $obj->amount_ht);
				
				$i++;
			} 
			return $exepnesereport;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	/**
	 * get social contributions
	 * 
	 * @return array of date and social contribution amount by month
	 */
	public function getSocialContribution($option, $arr_agences, $arr_resp, $arr_proj)
	{
		global $db, $user;

		$sql = "SELECT c.libelle as nom, date_format(cs.date_ech,'%Y-%m') as dm, sum(cs.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";

		if($option == 'resp' || !empty($arr_agences) || !empty($arr_resp) || !empty($arr_proj)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on cs.fk_projet = p.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as pe on pe.fk_object = p.rowid";

			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on ec.element_id = de.fk_projet";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as t on ec.fk_socpeople = t.rowid";

			$sql .= " WHERE 1 = 1";
			$i = 1;

			if(!empty($arr_resp) && $option ='all') {
				$sql .= " AND tc.element = 'project'";
				$sql .= " AND tc.source = 'internal'";
				$sql .= " AND tc.code = 'PROJECTLEADER'";
		
				$sql .= " AND ec.fk_socpeople  IN (".implode(', ', $arr_resp).")";
				}
	
			if(!empty($arr_agences) && $option == 'all') {
				$sql .= " AND pe.agenceconcerne IN (".implode(', ', $arr_agences).")";
			}

			if(!empty($arr_proj) && $option == 'all') {
				$sql .= " AND p.rowid IN (".implode(', ', $arr_proj).")";
			}
	
			if($option == 'resp') {
				$sql .= " AND";
				if(!empty(array_filter($this->getAgencesBySoc()))) {
					$agences = array_unique($this->getAgencesBySoc());
					array_filter($agences);

					foreach($agences as $manager => $agence) {
						$sql .= " pe.agenceconcerne =".((int) $agence);
						if($i < sizeof($agences)) {
							$sql .= " AND";
						}

						$i++;
					}
				}
			}
		}else{
			$sql .= " WHERE 1 = 1";
		}

		$sql .= " AND cs.fk_type = c.id";
		$sql .= " GROUP BY c.libelle, dm";

		dol_syslog(get_class($this)."::getSocialContribution", LOG_DEBUG);
		$resql = $db->query($sql);
		
		if ($resql) {
			$num = $db->num_rows($resql);
			
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$soccontribute[] = array('date' => $obj->dm, 'amount' => $obj->amount);
				
				$i++;
			} 
			return $soccontribute;
		} else {
			$this->error = $db->error();
			return -1;
		}
	}

	// public function getAmountByMonthNoDetails($year, $format = 0)
	// {
	// 	global $db;
	//   $sql = "SELECT date_format(s.dateep,'%m') as dm, sum(s.amount)";
	//   $sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
	//   $sql .= " WHERE date_format(s.dateep,'%Y') = '".$db->escape($year)."'";
	  
	//   $sql .= " GROUP BY dm";
	//   $sql .= $db->order('dm', 'DESC');
   
	//   $res = $this->_getAmountByMonthNoDetails($year, $sql, $format);
   
	//   return $res;
	// }

	// // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// private function _getAmountByMonthNoDetails($year, $sql, $format = 0)
	// {
	//   // phpcs:enable
	//   global $langs, $db;
   
	//   $result = array();
	//   $res = array();
   
	//   dol_syslog(get_class($this).'::'.__FUNCTION__, LOG_DEBUG);
   
	//   $resql = $db->query($sql);
	//   if ($resql) {
	// 	$num = $db->num_rows($resql);
	// 	$i = 0;
	// 	while ($i < $num) {
	// 	  $row = $db->fetch_row($resql);
	// 	  $j = $row[0] * 1;
	// 	  $result[$j] = $row[1];
	// 	  $i++;
	// 	}
	// 	$db->free($resql);
	//   } else {
	// 	dol_print_error($db);
	//   }
   
	//   for ($i = 1; $i < 13; $i++) {
	// 	$res[$i] = (int) round((isset($result[$i]) ? $result[$i] : 0));
	//   }
   
	//   $data = array();
   
	//   for ($i = 1; $i < 13; $i++) {
	// 	$month = 'unknown';
	// 	if ($format == 0) {
	// 	  $month = $langs->transnoentitiesnoconv('MonthShort'.sprintf("%02d", $i));
	// 	} elseif ($format == 1) {
	// 	  $month = $i;
	// 	} elseif ($format == 2) {
	// 	  $month = $langs->transnoentitiesnoconv('MonthVeryShort'.sprintf("%02d", $i));
	// 	}
	// 	//$month=dol_print_date(dol_mktime(12,0,0,$i,1,$year),($format?"%m":"%b"));
	// 	//$month=dol_substr($month,0,3);
	// 	$data[$i - 1] = array($month, $res[$i]);
	//   }
   
	//   return $data;
	// }
   
	/**
	 * Récupère les montants mensuels des salaires pour une année donnée.
	 *
	 * @param int $year   Année au format YYYY (ex. 2024)
	 * @param int $format Format de sortie des mois :
	 *                    0 = nom court (Jan, Feb...),
	 *                    1 = numéro (1, 2...),
	 *                    2 = nom très court (J, F...)
	 *
	 * @return array Tableau contenant les mois et les montants, ex :
	 * 
	 */
	public function getAmountByMonthNoDetails($year, $format = 0)
	{
		global $db;

		$start = $db->escape($year) . "-01-01";
		$end = ($db->escape($year) + 1) . "-01-01";

		$sql = "SELECT MONTH(s.dateep) AS dm, SUM(s.amount) AS total";
		$sql .= " FROM " . MAIN_DB_PREFIX . "salary AS s";
		$sql .= " WHERE s.dateep >= '" . $start . "' AND s.dateep < '" . $end . "'";
		$sql .= " GROUP BY dm";
		$sql .= $db->order('dm', 'ASC');

		return $this->_getAmountByMonthNoDetails($year, $sql, $format);
	}

	/**
	 * Exécute la requête SQL et formate les résultats mois/montant.
	 *
	 * @param int    $year   Année cible
	 * @param string $sql    Requête SQL préparée
	 * @param int    $format Format des mois (0 = court, 1 = numéro, 2 = très court)
	 *
	 * @return array Résultats formatés mois/montant
	 */
	private function _getAmountByMonthNoDetails($year, $sql, $format = 0)
	{
		// phpcs:enable
		global $langs, $db;

		dol_syslog(get_class($this) . '::' . __FUNCTION__, LOG_DEBUG);

		$result = array_fill(1, 12, 0); // Initialise avec 0 pour chaque mois

		$resql = $db->query($sql);
		if ($resql) {
			while ($row = $db->fetch_array($resql)) {
				$month = (int)$row['dm'];
				$result[$month] = (int)round($row['total']);
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		$data = array();

		for ($i = 1; $i <= 12; $i++) {
			switch ($format) {
				case 1:
					$monthLabel = $i;
					break;
				case 2:
					$monthLabel = $langs->transnoentitiesnoconv('MonthVeryShort' . sprintf("%02d", $i));
					break;
				default:
					$monthLabel = $langs->transnoentitiesnoconv('MonthShort' . sprintf("%02d", $i));
					break;
			}
			$data[] = array($monthLabel, $result[$i]);
		}

		return $data;
	}

   

}
