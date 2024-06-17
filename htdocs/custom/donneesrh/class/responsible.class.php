<?php
/* Copyright (C) 2023 FADEL Soufiane <s.fadel@optim-industries.fr>
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

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';



/**
 * Class for Responsible
 */
class Responsible extends CommonObject
{
    public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
	}

	public function getManager()
	{
		$sql = 'SELECT u.rowid, u.lastname, u.firstname,';
		$sql .= ' u.job, u.fk_user';
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.statut = 1";
		
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				if($obj->fk_user == null) {
					$data[$obj->rowid]= 'dg';
				}
				$res[$obj->rowid] = $obj->fk_user;
				$i++;
			}
		
			foreach($data as $key => $value) {
				array_key_exists($key, array_keys($res)) ? $pdg[$key] = $value : null; 
			}
			
			foreach ($res as $key => $val){
				$employees[$val][] = $key;
			}

			foreach($employees as $key => $value) {
				foreach($value as $employee) {
					// array_key_exists($employee, $employees) ? $submanagers[$employee] = 'submanager' : null;
					array_key_exists($key, $pdg) ? $managers[$employee] = null : null; 
				}
			}
			
			foreach($employees as $key => $val) {
				foreach($val as $value) {
					// array_key_exists($value, $employees) ? $inManager[$value] = $key : null;
					array_key_exists($key, $managers) ? $inManager[$key][] = $value : null; 
					$key !== '' && array_key_exists($key, $managers) == false && array_key_exists($key, $pdg) == false ? $inSubmanager[$key][] = $value : null; 
					
				}
				
			}

			$arr = $this->subManager($inManager, $inSubmanager);
			foreach($arr as $key => $flatten) {
				$ids[$key] = $this->array_flatten($flatten);
			}
		

			$this->db->free($result);

			return $ids;
		} else {
			dol_print_error($this->db);
		}
		
	}

	function array_flatten($array) { 
		if (!is_array($array)) { 
		  return FALSE; 
		} 
	
		$result = array(); 
		foreach ($array as $key => $value) { 
		  if (is_array($value)) { 
			
			$result = array_merge($result, $this->array_flatten($value)); 
			
		  } 
		  else { 
			$result[$key] = $value; 
		  } 
		} 
		return $result; 
	  } 


	function subManager($inManager, $inSubmanager)
	{
		
		foreach(array_filter($inManager) as $key1 => $val1) {
			foreach($val1 as $value) {
				foreach(array_filter($inSubmanager) as $key2 => $val2) {
					$inSubmanager2[$key2] = array_filter($val2);
					
					foreach(array_filter($inSubmanager2) as $key3 => $val3) {
						$inSubmanager3[$key2] = array_filter($val3);
						foreach(array_filter($inSubmanager3) as $key4 => $val4) {
						array_key_exists($value, $inSubmanager) ? $inManager[$key1][$key2] = in_array($key2, $val1) ? array_filter($val2) : null : null;
						array_key_exists($value, $inSubmanager2) ? $inSubmanager[$key2][$key3] = in_array($key3, $val2) ? array_filter($val3) : null : null;
						array_key_exists($value, $inSubmanager3) ? $inSubmanager2[$key3][$key4] = in_array($key4, $val3) ? array_filter($val4) : null : null;
						}
					}
				}
			}
		}

		
		return array_filter($inManager);
	}

}



