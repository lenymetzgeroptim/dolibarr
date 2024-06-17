<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *  \file       htdocs/ecm/class/ecmdirectory.class.php
 *  \ingroup    ecm
 *  \brief      This file is an example for a class file
 */

require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

/**
 *  Class to manage ECM directories
 */
class EcmCustomDirectory extends EcmDirectory
{


	/**
	 *  Create record into database
	 *
	 *  @param      User	$user       User that create
	 *  @return     int      			<0 if KO, >0 if OK
	 */
	public function create_custom($user)
	{
		global $conf, $langs;
		$error = 0;
		$now = dol_now();

		// Clean parameters
		$this->label = dol_sanitizeFileName(trim($this->label));
		$this->fk_parent = trim($this->fk_parent);
		$this->description = trim($this->description);
		$this->date_c = $now;
		$this->fk_user_c = $user->id;
		if ($this->fk_parent <= 0) {
			$this->fk_parent = 0;
		}


		// Check if same directory does not exists with this name
		$relativepath = $this->label;
		if ($this->fk_parent) {
			$parent = new EcmDirectory($this->db);
			$parent->fetch($this->fk_parent);
			$relativepath = $parent->getRelativePath().$relativepath;
		}
		$relativepath = preg_replace('/([\/])+/i', '/', $relativepath); // Avoid duplicate / or \
		//print $relativepath.'<br>';

		$cat = new EcmDirectory($this->db);
		$cate_arbo = $cat->get_full_arbo(1);
		$pathfound = 0;
		foreach ($cate_arbo as $key => $categ) {
			$path = str_replace($this->forbiddencharsdir, '_', $categ['fullrelativename']);
			//print $relativepath.' - '.$path.'<br>';
			if ($path == $relativepath) {
				$pathfound = 1;
				break;
			}
		}

		if ($pathfound) {
			$this->error = "ErrorDirAlreadyExists";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
			return -1;
		} else {
			$this->db->begin();

			// Insert request
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_directories(";
			$sql .= "label,";
			$sql .= "entity,";
			$sql .= "fk_parent,";
			$sql .= "description,";
			$sql .= "cachenbofdoc,";
			$sql .= "date_c,";
			$sql .= "fk_user_c";
			$sql .= ") VALUES (";
			$sql .= " '".$this->db->escape($this->label)."',";
			$sql .= " '".$this->db->escape($conf->entity)."',";
			$sql .= " '".$this->db->escape($this->fk_parent)."',";
			$sql .= " '".$this->db->escape($this->description)."',";
			$sql .= " ".((int) $this->cachenbofdoc).",";
			$sql .= " '".$this->db->idate($this->date_c)."',";
			$sql .= " '".$this->db->escape($this->fk_user_c)."'";
			$sql .= ")";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ecm_directories");

				$dir = $conf->ecmcustom->dir_output.'/'.$this->getRelativePath();
				$result = dol_mkdir($dir);
				if ($result < 0) {
					$error++; $this->error = "ErrorFailedToCreateDir";
				}

				// Create extrafields
				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}

				// Call trigger
				$result = $this->call_trigger('MYECMDIR_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers

				if (!$error) {
					$this->db->commit();
					return $this->id;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = "Error ".$this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 * 	Delete object on database and/or on disk
	 *
	 *	@param	User	$user					User that delete
	 *  @param	string	$mode					'all'=delete all, 'databaseonly'=only database entry, 'fileonly' (not implemented)
	 *  @param	int		$deletedirrecursive		1=Agree to delete content recursiveley (otherwise an error will be returned when trying to delete)
	 *	@return	int								<0 if KO, >0 if OK
	 */
	public function delete_custom($user, $mode = 'all', $deletedirrecursive = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		
		if ($mode != 'databaseonly') {
			$relativepath = $this->getRelativePath(1); // Ex: dir1/dir2/dir3
		}

		dol_syslog(get_class($this)."::delete remove directory id=".$this->id." mode=".$mode.(($mode == 'databaseonly') ? '' : ' relativepath='.$relativepath));

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->db->rollback();
			$this->error = "Error ".$this->db->lasterror();
			return -2;
		} else {
			// Call trigger
			$result = $this->call_trigger('MYECMDIR_DELETE', $user);
			if ($result < 0) {
				$this->db->rollback();
				return -2;
			}
			// End call triggers
		}

		if ($mode != 'databaseonly') {
			$file = $conf->ecmcustom->dir_output."/".$relativepath;
			if ($deletedirrecursive) {
				$result = @dol_delete_dir_recursive($file, 0, 0);
			} else {
				$result = @dol_delete_dir($file, 0);
			}
		}

		// Remove extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
			}
		}

		if ($result || !@is_dir(dol_osencode($file))) {
			$this->db->commit();
		} else {
			$this->error = 'ErrorFailToDeleteDir';
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			$error++;
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

}
