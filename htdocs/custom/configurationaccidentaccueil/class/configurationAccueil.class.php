<?php
/*  Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 * \file        class/configurationAccueil.class.php
 * \ingroup     ConfigurationAccidentAccueil
 */

// Put here all includes required by your class file
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for configurationAccueil
 */
class configurationAccueil extends CommonObject
{

    /**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->fod->fod->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	public function majConfigurationQSE(){
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."const";
		$sql .= " SET value = ".($conf->global->JOUR_SANS_ACC_TRAJET+1);
		$sql .= ' WHERE name="JOUR_SANS_ACC_TRAJET"'; 

		dol_syslog(get_class($this)."::incrementeCste()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."const";
		$sql .= " SET value = ".($conf->global->JOUR_SANS_ACC_SANS_ARRET+1);
		$sql .= ' WHERE name="JOUR_SANS_ACC_SANS_ARRET"'; 

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."const";
		$sql .= " SET value = ".($conf->global->JOUR_SANS_ACC_AVEC_ARRET+1);
		$sql .= ' WHERE name="JOUR_SANS_ACC_AVEC_ARRET"'; 

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		$jour = (int)dol_print_date(dol_now(), '%d');
		if($jour == 1 /*&& $conf->global->REINITIALISATION_REMONTEES_SSE*/){
			$sql = "UPDATE ".MAIN_DB_PREFIX."const";
			$sql .= " SET value = ''";
			$sql .= ' WHERE name="REMONTEES_SSE_VDRNORD"'; 

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."const";
			$sql .= " SET value = ''";
			$sql .= ' WHERE name="REMONTEES_SSE_GRANDOUEST"'; 

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."const";
			$sql .= " SET value = ''";
			$sql .= ' WHERE name="REMONTEES_SSE_SUDEST"'; 

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."const";
			$sql .= " SET value = ''";
			$sql .= ' WHERE name="REMONTEES_SSE_MARSEILLE"'; 

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."const";
			$sql .= " SET value = 0";
			$sql .= ' WHERE name="REINITIALISATION_REMONTEES_SSE"'; 

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}
		}
		// elseif (!$conf->global->REINITIALISATION_REMONTEES_SSE){
		// 	$sql = "UPDATE ".MAIN_DB_PREFIX."const";
		// 	$sql .= " SET value = 1";
		// 	$sql .= ' WHERE name="REINITIALISATION_REMONTEES_SSE"'; 

		// 	$resql = $this->db->query($sql);
		// 	if (!$resql) {
		// 		dol_print_error($this->db);
		// 		$this->error = $this->db->lasterror();
		// 		$error++;
		// 	}
		// }

		if (!$error) {
			$this->db->commit();
			return 0;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}
