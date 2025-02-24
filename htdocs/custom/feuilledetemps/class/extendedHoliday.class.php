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
 *      \file       htdocs/holiday/class/holiday.class.php
 *      \ingroup    holiday
 *      \brief      This file is a CRUD class file for Holiday (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';

/**
 * 	Class to manage Holiday
 */
class extendedHoliday extends Holiday
{

	/**
	 *	Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	public function updateExtended($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";

		$sql .= " description= '".$this->db->escape($this->description)."',";

		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		if (!empty($this->fk_type) && is_numeric($this->fk_type)) {
			$sql .= " fk_type = ".$this->fk_type.",";
		} else {
			$error++;
		}
		$sql .= " halfday = ".$this->halfday.",";
		if (!empty($this->statut) && is_numeric($this->statut)) {
			$sql .= " statut = ".$this->statut.",";
		} else {
			$error++;
		}
		if ($this->fk_validator >= 0) {
			$sql .= " fk_validator = '".$this->db->escape($this->fk_validator)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = '".$this->db->escape($this->fk_user_valid)."',";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = '".$this->db->escape($this->fk_user_refuse)."',";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = '".$this->db->escape($this->fk_user_cancel)."',";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}

		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAYCUSTOM_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

    /**
	 *	Check that a user is not on holiday for a particular timestamp. Can check approved leave requests and not into public holidays of company.
	 *
	 * 	@param 	int			$fk_user				Id user
	 *  @param	integer	    $timestamp				Time stamp date for a day (YYYY-MM-DD) without hours  (= 12:00AM in english and not 12:00PM that is 12:00)
	 *  @param	string		$status					Filter on holiday status. '-1' = no filter.
	 *  @param	array		$excluded_types			Array of excluded types of holiday
	 * 	@return array								array('morning'=> ,'afternoon'=> ), Boolean is true if user is available for day timestamp.
	 *  @see verifDateHolidayCP()
	 */
	public function verifDateHolidayForTimestamp($fk_user, $timestamp, $status = '-1', $excluded_types = array())
	{
		global $langs, $conf;

		$isavailablemorning = true;
		$isavailableafternoon = true;
        $statut = '';
		$rowid_array = array();
		$statut_array = array();
		$code_array = array();
		$hour_array = array();
		$statutfdt_array = array();
		$droitrtt_array = array();
		$in_hour_array = array();
		$nb_jour_array = array();

		// Check into leave requests
		$sql = "SELECT cp.rowid, cp.date_debut as date_start, cp.date_fin as date_end, cp.halfday, cp.statut, ht.code, ht.droit_rtt, he.hour, he.statutfdt, ht.in_hour";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_holiday_types as ht ON ht.rowid = cp.fk_type";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."holiday_extrafields as he ON he.fk_object = cp.rowid";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = ".(int) $fk_user;
		$sql .= " AND cp.date_debut <= '".$this->db->idate($timestamp)."' AND cp.date_fin >= '".$this->db->idate($timestamp)."'";
		if ($status != '-1' && gettype($status) != "array") {
			$sql .= " AND cp.statut IN (".$this->db->sanitize($status).")";
		}
		elseif ($status != '-1') {
			$sql .= " AND cp.statut IN (".$this->db->sanitize(implode(',', $status)).")";
		}
		if(!empty($excluded_types)) {
			$sql .= " AND cp.fk_type NOT IN (".$this->db->sanitize(implode(',', $excluded_types)).")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql); // Note, we can have 2 records if on is morning and the other one is afternoon
			if ($num_rows > 0) {
				$arrayofrecord = array();
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);

					$date_debut_gmt = $this->db->jdate($obj->date_start, 1);
					$date_fin_gmt = $this->db->jdate($obj->date_end, 1);

					// Note: $obj->halfday is  0:Full days, 2:Sart afternoon end morning, -1:Start afternoon, 1:End morning
					$arrayofrecord[$obj->rowid] = array('date_start'=>$this->db->jdate($obj->date_start), 'date_end'=>$this->db->jdate($obj->date_end), 'halfday'=>$obj->halfday);
					$rowid_array[] = $obj->rowid;
					$statut_array[] = $obj->statut;
					$code_array[] = $obj->code;
					$hour_array[] = $obj->hour;
					$statutfdt_array[] = $obj->statutfdt;
					$droitrtt_array[] = $obj->droit_rtt;
					$in_hour_array[] = $obj->in_hour;
					$nb_jour_array[] = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $obj->halfday);

					$i++;
				}

				// We found a record, user is on holiday by default, so is not available is true.
				$isavailablemorning = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_start'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_start'] && $record['halfday'] == -1) {
						continue;
					}
					$isavailablemorning = false;
					break;
				}
				$isavailableafternoon = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_end'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_end'] && $record['halfday'] == 1) {
						continue;
					}
					$isavailableafternoon = false;
					break;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		$result = array('morning'=>$isavailablemorning, 'afternoon'=>$isavailableafternoon, 'statut'=>$statut_array, 'code'=>$code_array, 'rowid'=>$rowid_array, 'hour'=>$hour_array, 'statutfdt'=>$statutfdt_array, 'droit_rtt'=>$droitrtt_array, 'in_hour'=>$in_hour_array, 'nb_jour'=>$nb_jour_array);
		if (!$isavailablemorning) {
			$result['morning_reason'] = 'leave_request';
		}
		if (!$isavailableafternoon) {
			$result['afternoon_reason'] = 'leave_request';
		}
		return $result;
	}

	/**
	 *	Check that a user is not on holiday for a particular timestamp. Can check approved leave requests and not into public holidays of company.
	 *
	 * 	@param 	int			$fk_user				Id user
	 *  @param	integer	    $timestamp				Time stamp date for a day (YYYY-MM-DD) without hours  (= 12:00AM in english and not 12:00PM that is 12:00)
	 *  @param	string		$status					Filter on holiday status. '-1' = no filter.
	 *  @param	array		$excluded_types			Array of excluded types of holiday
	 * 	@return array								array('morning'=> ,'afternoon'=> ), Boolean is true if user is available for day timestamp.
	 *  @see verifDateHolidayCP()
	 */
	public function verifDateHolidayForTimestampForAllUser($timestamp, $status = '-1', $excluded_types = array())
	{
		global $langs, $conf;

		$isavailablemorning = array();
		$isavailableafternoon = array();
        $statut = '';
		$rowid_array = array();
		$statut_array = array();
		$code_array = array();
		$hour_array = array();
		$statutfdt_array = array();
		$droitrtt_array = array();
		$in_hour_array = array();
		$user_id_array = array();
		$nb_jour_array = array();

		// Check into leave requests
		$sql = "SELECT cp.rowid, cp.date_debut as date_start, cp.date_fin as date_end, cp.halfday, cp.statut, ht.code, ht.droit_rtt, he.hour, he.statutfdt, ht.in_hour, cp.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_holiday_types as ht ON ht.rowid = cp.fk_type";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."holiday_extrafields as he ON he.fk_object = cp.rowid";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.date_debut <= '".$this->db->idate($timestamp)."' AND cp.date_fin >= '".$this->db->idate($timestamp)."'";
		if ($status != '-1' && gettype($status) != "array") {
			$sql .= " AND cp.statut IN (".$this->db->sanitize($status).")";
		}
		elseif ($status != '-1') {
			$sql .= " AND cp.statut IN (".$this->db->sanitize(implode(',', $status)).")";
		}
		if(!empty($excluded_types)) {
			$sql .= " AND cp.fk_type NOT IN (".$this->db->sanitize(implode(',', $excluded_types)).")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql); // Note, we can have 2 records if on is morning and the other one is afternoon
			if ($num_rows > 0) {
				$arrayofrecord = array();
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);

					$date_debut_gmt = $this->db->jdate($obj->date_start, 1);
					$date_fin_gmt = $this->db->jdate($obj->date_end, 1);

					// Note: $obj->halfday is  0:Full days, 2:Sart afternoon end morning, -1:Start afternoon, 1:End morning
					$arrayofrecord[$obj->fk_user][$obj->rowid] = array('date_start'=>$this->db->jdate($obj->date_start), 'date_end'=>$this->db->jdate($obj->date_end), 'halfday'=>$obj->halfday, 'fk_user'=>$obj->fk_user);
					$rowid_array[$obj->fk_user][] = $obj->rowid;
					$statut_array[$obj->fk_user][] = $obj->statut;
					$code_array[$obj->fk_user][] = $obj->code;
					$hour_array[$obj->fk_user][] = $obj->hour;
					$statutfdt_array[$obj->fk_user][] = $obj->statutfdt;
					$droitrtt_array[$obj->fk_user][] = $obj->droit_rtt;
					$in_hour_array[$obj->fk_user][] = $obj->in_hour;
					if(!in_array($obj->fk_user, $user_id_array)) {
						$user_id_array[] = $obj->fk_user;
					}
					if($obj->hour > 0) {
						$nb_jour_array[$obj->fk_user][$obj->rowid] = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $obj->halfday);
					}

					$i++;
				}

				// We found a record, user is on holiday by default, so is not available is true.
				foreach($user_id_array as $user_id) {
					$isavailablemorning[$user_id] = true;
					foreach ($arrayofrecord[$user_id] as $record) {
						if ($timestamp == $record['date_end'] && $record['halfday'] == 2) {
							continue;
						}
						if ($timestamp == $record['date_end'] && $record['halfday'] == -1) {
							continue;
						}
						$isavailablemorning[$user_id] = false;
						break;
					}
				}

				foreach($user_id_array as $user_id) {
					$isavailableafternoon[$user_id] = true;
					foreach ($arrayofrecord[$user_id] as $record) {
						if ($timestamp == $record['date_end'] && $record['halfday'] == 2) {
							continue;
						}
						if ($timestamp == $record['date_end'] && $record['halfday'] == 1) {
							continue;
						}
						$isavailableafternoon[$user_id] = false;
						break;
					}
				}
			}
		} else {
			dol_print_error($this->db);
		}

		$result = array('morning'=>$isavailablemorning, 'afternoon'=>$isavailableafternoon, 'statut'=>$statut_array, 'code'=>$code_array, 'rowid'=>$rowid_array, 'hour'=>$hour_array, 'statutfdt'=>$statutfdt_array, 'droit_rtt'=>$droitrtt_array, 'in_hour'=>$in_hour_array, 'user_id'=>$user_id_array, 'nb_jour'=>$nb_jour_array);

		return $result;
	}

	/**
	 *  Return array with list of types (sans les types de CP ajoutés)
	 *
	 *  @param		int		$active		Status of type. -1 = Both
	 *  @param		int		$affect		Filter on affect (a request will change sold or not). -1 = Both
	 *  @return     array	    		Return array with list of types
	 */
	public function getTypesNoCP($active = -1, $affect = -1)
	{
		global $mysoc;

		$sql = "SELECT rowid, code, label, affect, delay, newbymonth, in_hour";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
		$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
		if ($active >= 0) {
			$sql .= " AND active = ".((int) $active);
		}
		if ($affect >= 0) {
			$sql .= " AND affect = ".((int) $affect);
		}
		$sql .= " AND code NOT IN ('CP_N-1_ACQUIS', 'CP_N-1_PRIS', 'CP_N_ACQUIS', 'CP_N_PRIS', 'CP_FRAC_ACQUIS', 'CP_FRAC_PRIS', 'CP_ANC_ACQUIS', 'CP_ANC_PRIS', 'RTT_ACQUIS', 'RTT_PRIS')";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				while ($obj = $this->db->fetch_object($result)) {
					$types[$obj->rowid] = array('rowid'=> $obj->rowid, 'code'=> $obj->code, 'label'=>$obj->label, 'affect'=>$obj->affect, 'delay'=>$obj->delay, 'newbymonth'=>$obj->newbymonth, 'in_hour'=>$obj->in_hour);
				}

				return $types;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param	int			$withpicto					0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
	 *  @param  int     	$save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param  int         $notooltip					1=Disable tooltip
	 *	@return	string									String with URL
	 */
	public function getNomUrlBlank($withpicto = 0, $save_lastsearch_value = -1, $notooltip = 0)
	{
		global $langs;

		$result = '';

		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Holiday").'</u>';
		if (isset($this->statut)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = DOL_URL_ROOT.'/custom/holidaycustom/card.php?id='.$this->id;

		//if ($option != 'nolink')
		//{
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}
		//}

		$linkstart = '<a target="_blank" href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}

	/**
	 *
	 *  @param		array		$ids		
	 *  @return     int	    		<0 if KO, >0 if OK
	 */
	public function setStatutExported($ids)
	{
		$error = 0;

		if(!empty($ids)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_extrafields";
			$sql .= " SET statutfdt = 3";
			$sql .= " WHERE fk_object IN (".$this->db->sanitize(implode(',', $ids)).')';

			$this->db->begin();

			dol_syslog(get_class($this)."::update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++; $this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}	
	}
}