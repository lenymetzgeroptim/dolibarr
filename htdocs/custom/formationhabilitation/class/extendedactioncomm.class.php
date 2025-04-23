<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2017  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015	    Marcos García		    <marcosgdf@gmail.com>
 * Copyright (C) 2018	    Nicolas ZABOURI	        <info@inovea-conseil.com>
 * Copyright (C) 2018-2023  Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       htdocs/comm/action/class/actioncomm.class.php
 *       \ingroup    agenda
 *       \brief      File of class to manage agenda events (actions)
 */
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncommreminder.class.php';


/**
 *		Class to manage agenda events (actions)
 */
class ExtendedActionComm extends ActionComm
{
	/**
	 *  Load all objects with filters.
	 *  @todo WARNING: This make a fetch on all records instead of making one request with a join.
	 *
	 *  @param		int		$socid			Filter by thirdparty
	 *  @param		int		$fk_element		Id of element action is linked to
	 *  @param		string	$elementtype	Type of element action is linked to
	 *  @param		string	$filter			Other filter
	 *  @param		string	$sortfield		Sort on this field
	 *  @param		string	$sortorder		ASC or DESC
	 *  @param		int		$limit			Limit number of answers
	 *  @return		ActionComm[]|string		Error string if KO, array with actions if OK
	 */
	public function getActions($socid = 0, $fk_element = 0, $elementtype = '', $filter = '', $sortfield = 'a.datep', $sortorder = 'DESC', $limit = 0)
	{
		global $conf, $langs, $hookmanager;

		$resarray = array();

		dol_syslog(get_class()."::getActions", LOG_DEBUG);

		// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('agendadao'));

		$sql = "SELECT a.id";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		// Fields from hook
		$parameters = array('sql' => &$sql, 'socid' => $socid, 'fk_element' => $fk_element, 'elementtype' => $elementtype);
		$reshook = $hookmanager->executeHooks('getActionsListFrom', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."formationhabilitation_userformation as uf ON a.elementtype = 'userautorisation@formationhabilitation' AND a.fk_element = uf.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";

		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		if (!empty($socid)) {
			$sql .= " AND a.fk_soc = ".((int) $socid);
		}
		if (!empty($elementtype)) {
			if ($elementtype == 'project') {
				$sql .= ' AND a.fk_project = '.((int) $fk_element);
			} elseif ($elementtype == 'contact') {
				$sql .= ' AND a.id IN';
				$sql .= " (SELECT fk_actioncomm FROM ".MAIN_DB_PREFIX."actioncomm_resources WHERE";
				$sql .= " element_type = 'socpeople' AND fk_element = ".((int) $fk_element).')';
			} else {
				$sql .= " AND a.fk_element = ".((int) $fk_element)." AND a.elementtype = '".$this->db->escape($elementtype)."'";
			}
		}
		if (!empty($filter)) {
			$sql .= $filter;
		}
		// Fields where hook
		$parameters = array('sql' => &$sql, 'socid' => $socid, 'fk_element' => $fk_element, 'elementtype' => $elementtype);
		$reshook = $hookmanager->executeHooks('getActionsListWhere', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}
		if ($sortorder && $sortfield) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		$sql .= $this->db->plimit($limit, 0);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if ($num) {
				for ($i = 0; $i < $num; $i++) {
					$obj = $this->db->fetch_object($resql);
					$actioncommstatic = new ActionComm($this->db);
					$actioncommstatic->fetch($obj->id);
					$resarray[$i] = $actioncommstatic;
				}
			}
			$this->db->free($resql);
			return $resarray;
		} else {
			return $this->db->lasterror();
		}
	}
}
