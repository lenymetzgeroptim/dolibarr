<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 *  \file       htdocs/user/class/user.class.php
 *	\brief      File of class to manage users
 *  \ingroup	core
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod_user.class.php';

/**
 *	Class to manage Dolibarr users
 */
class ExtendedUser extends User
{

	/**
	 *  Get la contrainte de dose annuelle d'un intervenant
	 *
	 *  @return double  			    Contrainte de dose annuelle
	 */
	public function getCdd()
	{
		global $conf, $langs, $user;

		$error = 0;

		//$this->db->begin();

		if(!empty($this->array_options['options_cat_med'])){
			$sql = "SELECT cdd FROM ".MAIN_DB_PREFIX."fod_cdd";
			$sql .= " WHERE options_cat_med = ". $this->array_options['options_cat_med'];
			$sql .= " AND mensuelle = 0";

			dol_syslog(get_class($this).'::getCdd', LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result)
			{
				if ($this->db->num_rows($result))
				{
					$obj = $this->db->fetch_object($result);
					$cdd = $obj->cdd;
				}
				else $cdd = 0.00;
				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}
		else {
			$cdd = 0.00;
		}
		return number_format($cdd, 3);
	}

	/**
	 *  Get la contrainte de dose mensuelle d'un intervenant
	 *
	 *  @return double 		Contrainte de dose mensuelle
	 */
	public function getCddmensuelle()
	{
		global $conf, $langs, $user;

		$error = 0;

		//$this->db->begin();

		if(!empty($this->array_options['options_cat_med'])){
			$sql = "SELECT cdd FROM ".MAIN_DB_PREFIX."fod_cdd";
			$sql .= " WHERE options_cat_med = ". $this->array_options['options_cat_med'];
			$sql .= " AND mensuelle = 1";

			dol_syslog(get_class($this).'::getCddmensuelle', LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result)
			{
				if ($this->db->num_rows($result))
				{
					$obj = $this->db->fetch_object($result);
					$cdd = $obj->cdd;
				}
				else $cdd = 0.00;
				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}
		else {
			$cdd = 0.00;
		}
		return number_format($cdd, 3);
	}
	

	/**
	 *  Get la dose actuelle d'un intervenant sur UNE Fod 
	 *
	 *  @param	Fod   		$fod       	fod
	 *  @return double  			    Dose
	 */
	public function getDoseFod($fod)
	{
		global $conf, $langs, $user;

		$error = 0;
		$dose = 0.00;

		//$this->db->begin();

		if(!empty($fod->id)){
			$sql = "SELECT SUM(dose) as dose_totale FROM ".MAIN_DB_PREFIX."fod_dataintervenant";
			$sql .= " WHERE fk_fod=".$fod->id;
			$sql .= " AND fk_user=".$this->id;

			dol_syslog(get_class($this).'::getDoseFod', LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result){
				if ($this->db->num_rows($result)){
					$obj = $this->db->fetch_object($result);
					if(!empty($obj->dose_totale)){
						$dose = $obj->dose_totale;
					}
				}
				else $dose = 0.00;
				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}
		else {
			$dose = 0.00;
		}
		return number_format($dose, 3);
	}

	/**
	 *  Get le nombre d'entrée d'un intervenant sur UNE Fod 
	 *
	 *  @param	Fod   		$fod       	fod
	 *  @return double  			    Nombre d'entrée
	 */
	public function getEntreeFod($fod)
	{
		global $conf, $langs, $user;

		$error = 0;
		$nb_entree = 0;

		//$this->db->begin();

		if(!empty($fod->id)){
			$sql = "SELECT COUNT(rowid) as nb_entree FROM ".MAIN_DB_PREFIX."fod_dataintervenant";
			$sql .= " WHERE fk_fod=".$fod->id;
			$sql .= " AND fk_user=".$this->id;

			dol_syslog(get_class($this).'::getEntreeFod', LOG_DEBUG);
			$result = $this->db->query($sql);

			if($result){
				if($this->db->num_rows($result)){
					$obj = $this->db->fetch_object($result);
					if(!empty($obj->nb_entree)){
						$nb_entree = $obj->nb_entree;
					}
				}
				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}

		return $nb_entree;
	}

	/**
	 *  Get la dose d'un intervenant sur les 12 derniers mois
	 *
	 *  @return double  			    Dose
	 */
	public function getDose12mois()
	{
		global $conf, $langs, $user;

		$error = 0;

		//$this->db->begin();

		$year_end = strftime("%Y", dol_now());
		$month_end = strftime("%m", dol_now());
		$day_end = strftime("%d", dol_now());
		$year_start = $year_end-1;
		$month_start = $month_end;
		$day_start = $day_end;

		$date_start = dol_mktime(0, 0, 0, $month_end, $day_end, $year_start);
		$date_end = dol_mktime(23, 59, 59, $month_end, $day_end, $year_end);

		if(!empty($this->id)){
			$sql = "SELECT SUM(dose) as dose_totale FROM ".MAIN_DB_PREFIX."fod_dataintervenant";
			$sql .= " WHERE fk_user=".$this->id;
			$sql .= " AND date <= '".$this->db->idate($date_end)."'";
			$sql .= " AND date > '".$this->db->idate($date_start)."'";

			dol_syslog(get_class($this).'::getDose12mois', LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result)
			{
				if ($this->db->num_rows($result))
				{
					$obj = $this->db->fetch_object($result);
					$dose = $obj->dose_totale;
				}
				else $dose = 0.00;
				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}
		else {
			$dose = 0.00;
		}
		return number_format($dose, 3);
	}

	/**
	 *  Get la dose d'un intervenant sur le mois en cours
	 *
	 *  @return double  			    Dose
	 */
	public function getDoseMoisActuelle()
	{
		global $conf, $langs, $user;

		$error = 0;

		//$this->db->begin();

		$year_end = strftime("%Y", dol_now());
		$month_end = strftime("%m", dol_now()) + 1;
		$day_end = 1;
		$year_start = $year_end;
		$month_start = $month_end - 1;
		$day_start = 1;

		$date_start = dol_mktime(0, 0, 0, $month_start, $day_start, $year_start);
		$date_end = dol_mktime(23, 59, 59, $month_end, $day_end, $year_end);

		if(!empty($this->id)){
			$sql = "SELECT SUM(dose) as dose_totale FROM ".MAIN_DB_PREFIX."fod_dataintervenant";
			$sql .= " WHERE fk_user=".$this->id;
			$sql .= " AND date < '".$this->db->idate($date_end)."'";
			$sql .= " AND date >= '".$this->db->idate($date_start)."'";

			dol_syslog(get_class($this).'::getDoseMoisActuelle', LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result)
			{
				if ($this->db->num_rows($result))
				{
					$obj = $this->db->fetch_object($result);
					$dose = $obj->dose_totale;
				}
				else $dose = 0.00;
				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}
		else {
			$dose = 0.00;
		}
		return number_format($dose, 3);
	}


	/**
	 *  Get la CdD d'un intervenant sur UNE Fod
	 *
	 *  @param	Fod   		$fod        fod
	 *  @return double  			    Contrainte de dose
	 */
	public function getDoseMaxFod($fod)
	{
		global $conf, $langs, $user;

		$fod_user = new Fod_user($this->db);
		$fod_user_id = $fod_user->getIdWithUserAndFod($this->id, $fod->id);
		$fod_user->fetch($fod_user_id);
		if ($fod_user->fields['contrat']['arrayofkeyval'][$fod_user->contrat] == 'CDI'){
			if($fod->cdd_optimise == 1){
				$dosemax = $fod->GetDoseIndividuelleMaxOptimise();
			}
			else {
				$dosemax = $fod->GetDoseIndividuelleMax();
			}
		}
		else {
			if($fod->cdd_optimise == 1){
				$dosemax = min($fod->GetDoseIndividuelleMaxOptimise(), $fod_user->duree_contrat*$conf->global->FOD_DIMax_PRORATATEMPORIS);
			}
			else {
				$dosemax = min($fod->GetDoseIndividuelleMax(), $fod_user->duree_contrat*$conf->global->FOD_DIMax_PRORATATEMPORIS);
			}
			$dosemax = number_format($dosemax,3);
		}

		return number_format($dosemax, 3);
	}


	/**
	 *  Obtenir la liste des Fod active d'un utilisateur
	 *
	 *  @param		User   $user       	Utilisateur dont on veut récupérer les fod
	 *  @return 	array				Tableau d'id des fod 
	 */
	public function getListFod($user_f)
	{
		global $conf, $langs, $user;
		
		$listfod = array();
		$error = 0;

		$this->db->begin();
		if(!empty($user_f->id)){
			$sql = "SELECT f.rowid FROM ".MAIN_DB_PREFIX."fod_fod as f";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fod_user as u ON f.rowid = u.fk_fod";
			$sql .= " WHERE u.fk_user  = ". $user_f->id;
			$sql .= " AND u.visa=1";
			$sql .= " AND (f.status=".Fod::STATUS_VALIDATED.' or f.status='.Fod::STATUS_BILAN.')';
			$sql .= " AND u.prise_en_compte_fin=2";
			$sql .= " AND u.statut!=".Fod_user::STATUS_SORTIE;


			dol_syslog(get_class($this).'::getListFod', LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result)
			{
				if ($this->db->num_rows($result))
				{
					$i=0;
					while($i < $this->db->num_rows($result)){
						$obj = $this->db->fetch_object($result);
						$listfod[] = $obj->rowid;
						$i++; 
					}
				}
				$this->db->free($result);
			} 
		}
		return $listfod;
	}

}
