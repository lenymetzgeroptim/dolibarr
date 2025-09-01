<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/holiday.lib.php
 *		\brief      Ensemble de fonctions de base pour les adherents
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object         Holiday
 *  @return array           		head
 */
function holiday_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/custom/holidaycustom/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Leave");
	$head[$h][2] = 'card';
	$h++;

	// Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->holiday->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/custom/holidaycustom/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/holidaycustom/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'holiday');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'holiday', 'remove');

	return $head;
}


/**
 *  Return array head with list of tabs to view object informations
 *
 *  @return array           		head
 */
function holiday_admin_prepare_head()
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/custom/holidaycustom/admin/holiday.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'holiday';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/custom/holidaycustom/admin/holidaycustom.php';
	$head[$h][1] = $langs->trans("SetupCustom");
	$head[$h][2] = 'holidaycustom';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'holiday_admin');

	$head[$h][0] = DOL_URL_ROOT.'/custom/holidaycustom/admin/holiday_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'holiday_admin', 'remove');

	return $head;
}

function dolSqlBetweenDateFilter($datefield, $day_start, $month_start, $year_start, $day_end, $month_end, $year_end, $excludefirstand = 0, $gm = false)
{
	global $db;
	$sqldate = '';

	$day_start = intval($day_start);
	$month_start = intval($month_start);
	$year_start = intval($year_start);
	$day_end = intval($day_end);
	$month_end = intval($month_end);
	$year_end = intval($year_end);

	if ($day_start > 0 && $month_start > 0 && $year_start > 0) {
		if ($month_date > 12 || $day_start > 31) { // protection for bad value of month
			return " AND 1 = 2";
		}

		if ($day_end > 0 && $month_end > 0 && $year_end > 0) {
			if ($month_end > 12 || $day_end > 31) { // protection for bad value of month
				return " AND 1 = 2";
			}
			$sqldate .= " AND ".$datefield." BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_start, $day_start, $year_start, $gm));
			$sqldate .= "' AND '".$db->idate(dol_mktime(0, 0, 0, $month_end, $day_end, $year_end, $gm))."'";
		} 
		else {
			$sqldate .= " AND ".$datefield." >= '".$db->idate(dol_mktime(0, 0, 0, $month_start, $day_start, $year_start, $gm))."'";
		} 
	}
	elseif ($day_end > 0 && $month_end > 0 && $year_end > 0) {
		if ($month_end > 12 || $day_end > 31) { // protection for bad value of month
			return " AND 1 = 2";
		}

		$sqldate .= " AND ".$datefield." <= '".$db->idate(dol_mktime(23, 59, 59, $month_end, $day_end, $year_end, $gm))."'"; 
	}

	return $sqldate;
}

/**
 * 	Return tous les volets à partir du dictionnaire
 *
 * 	@return	array						
 */
function getLabelList($table, $fieldname)
{
	global $conf, $user, $db;
	$res = array();

	$sql = "SELECT t.rowid, t.$fieldname";
	$sql .= " FROM ".MAIN_DB_PREFIX."$table as t";
	$sql .= " ORDER BY t.$fieldname";

	dol_syslog("formationhabilitation.lib.php::getLabelList", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		while($obj = $db->fetch_object($resql)) {
			$res[$obj->rowid] = $obj->$fieldname;
		}

		$db->free($resql);
		return $res;
	} else {
		return -1;
	}
}