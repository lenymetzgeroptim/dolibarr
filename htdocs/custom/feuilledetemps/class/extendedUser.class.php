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
class ExtendedUser3 extends User
{

		/**
	 *	Load a user from database with its Matricule.
	 *  This function does not load permissions, only user properties. Use getrights() for this just after the fetch.
	 *
	 *	@param	int		$id		       		If defined, id to used for search
	 * 	@param  string	$matricule       	If defined, matricule to used for search
	 * 	@return	int							<0 if KO, 0 not found, >0 if OK
	 */
	public function fetchIdWithMatricule($id = '', $ref = '', $matricule = '')
	{
		global $conf, $user;

		if($matricule <= 0) {
			return -1;
		}

		// Get user
		$sql = "SELECT u.rowid";
		$sql .= " FROM ".$this->db->prefix()."user as u";
		$sql .= " LEFT JOIN ".$this->db->prefix()."user_extrafields as ue ON ue.fk_object = u.rowid";

		// The fetch was forced on an entity
		if (isModEnabled('multicompany') && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			$sql .= " WHERE u.entity IS NOT NULL"; // multicompany is on in transverse mode or user making fetch is on entity 0, so user is allowed to fetch anywhere into database
		} else {
			$sql .= " WHERE u.entity IN (0, ".((int) (($entity != '' && $entity >= 0) ? $entity : $conf->entity)).")"; // search in entity provided in parameter
		}
		if ($matricule) {
			$sql .= " AND ue.matricule = ".(int)$matricule;
		}
		//$sql .= " AND u.statut = 1";
		$sql .= " ORDER BY u.entity ASC"; // Avoid random result when there is 2 login in 2 different entities

		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->db->free($result);
			} else {
				$this->error = "USERNOTFOUND";
				dol_syslog(get_class($this)."::fetch user not found", LOG_DEBUG);

				$this->db->free($result);
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	public function get_full_treeIds($filter = '')
	{
		// phpcs:enable
		global $conf, $user;
		global $hookmanager;

		// Actions hooked (by external module)
		$hookmanager->initHooks(array('userdao'));

		$tree_ids = array();

		// Init $this->users array
		$sql = "SELECT DISTINCT u.rowid"; // Distinct reduce pb with old tables with duplicates
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printUserListWhere', $parameters); // Note that $action and $object may have been modified by hook
		if ($reshook > 0) {
			$sql .= $hookmanager->resPrint;
		} else {
			$sql .= " WHERE u.entity IN (".getEntity('user').")";
		}
		if ($filter) {
			$sql .= " AND ".$filter;
		}

		dol_syslog(get_class($this)."::get_full_treeIds get user list", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			while ($obj = $this->db->fetch_object($resql)) {
				$tree_ids[] = $obj->rowid;
				$i++;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}

		return $tree_ids;
	}

}
