<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2017 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2019      Juanjo Menent        <jmenent@2byte.es>
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
 * 		\file       htdocs/projet/class/project.class.php
 * 		\ingroup    projet
 * 		\brief      File of class to manage projects
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 *	Class to manage projects
 */
class ExtendedProjet extends Project{

/**
 * Load time spent into this->weekWorkLoad and this->weekWorkLoadPerTask for all day of a week of project.
 * Note: array weekWorkLoad and weekWorkLoadPerTask are reset and filled at each call.
 *
 * @param 	int		$datestart		First day of week (use dol_get_first_day to find this date)
 * @param 	int		$taskid			Filter on a task id
 * @param 	int		$userid			Time spent by a particular user
 * @return 	int						<0 if OK, >0 if KO
 */
public function loadTimeSpent_month($datestart, $taskid = 0, $userid = 0)
{
    $error = 0;

    $this->weekWorkLoad = array();
    $this->weekWorkLoadPerTask = array();
    $fin_mois = dol_get_last_day(dol_print_date(dol_time_plus_duree($datestart, 1, 'm'), '%Y'), dol_print_date(dol_time_plus_duree($datestart, 1, 'm'), '%m'));
    if (empty($datestart)) {
        dol_print_error('', 'Error datestart parameter is empty');
    }

    $sql = "SELECT ptt.rowid as taskid, ptt.element_duration, ptt.element_date, ptt.element_datehour, ptt.fk_element";
    $sql .= " FROM ".MAIN_DB_PREFIX."element_time AS ptt, ".MAIN_DB_PREFIX."projet_task as pt";
    $sql .= " WHERE ptt.fk_element = pt.rowid";
    $sql .= " AND ptt.elementtype = 'task'";
    $sql .= " AND pt.fk_projet = ".((int) $this->id);
    $sql .= " AND (ptt.element_date >= '".$this->db->idate($datestart)."' ";
    $sql .= " AND ptt.element_date <= '".$this->db->idate($fin_mois)."')";
    if ($taskid) {
        $sql .= " AND ptt.fk_element=".((int) $taskid);
    }
    if (is_numeric($userid)) {
        $sql .= " AND ptt.fk_user=".((int) $userid);
    }

    //print $sql;
    $resql = $this->db->query($sql);
    if ($resql) {
        $daylareadyfound = array();

        $num = $this->db->num_rows($resql);
        $i = 0;
        // Loop on each record found, so each couple (project id, task id)
        while ($i < $num) {
            $obj = $this->db->fetch_object($resql);
            $day = $this->db->jdate($obj->element_date); // task_date is date without hours
            if (empty($daylareadyfound[$day])) {
                $this->weekWorkLoad[$day] = $obj->element_duration;
                $this->weekWorkLoadPerTask[$day][$obj->fk_element] = $obj->element_duration;
            } else {
                $this->weekWorkLoad[$day] += $obj->element_duration;
                $this->weekWorkLoadPerTask[$day][$obj->fk_element] += $obj->element_duration;
            }
            $daylareadyfound[$day] = 1;
            $i++;
        }
        $this->db->free($resql);
        return 1;
    } else {
        $this->error = "Error ".$this->db->lasterror();
        dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
        return -1;
    }
}

/**
 * Get the total of timespent by day
 *
 * @param 	int		$datestart		First day 
 * @param 	int		$dateend		Last day
 * @param 	int		$userid			Time spent by a particular user
 * @return 	array|int				<0 if KO, Total for each day if OK
 */
public function getTotalForEachDay($datestart, $dateend, $userid = 0)
{
    $error = 0;
    $totalforeachday = array();

    $sql = "SELECT ptt.rowid as taskid, SUM(ptt.element_duration) as total_duration, ptt.element_date, ptt.fk_element";
    $sql .= " FROM ".MAIN_DB_PREFIX."element_time AS ptt, ".MAIN_DB_PREFIX."projet_task as pt";
    $sql .= " WHERE ptt.fk_element = pt.rowid";
    $sql .= " AND ptt.elementtype = 'task'";
    $sql .= " AND (ptt.element_date >= '".$this->db->idate($datestart)."' ";
    $sql .= " AND ptt.element_date <= '".$this->db->idate($dateend)."')";
    if (is_numeric($userid)) {
        $sql .= " AND ptt.fk_user=".((int) $userid);
    }
    $sql .= " GROUP BY ptt.element_date";

    //print $sql;
    $resql = $this->db->query($sql);
    if ($resql) {
        $num = $this->db->num_rows($resql);
        $i = 0;

        while ($i < $num) {
            $obj = $this->db->fetch_object($resql);
            $day = $this->db->jdate($obj->element_date); // task_date is date without hours
            $totalforeachday[$day] = (int)$obj->total_duration;
            $i++;
        }

        $this->db->free($resql);
        return $totalforeachday;
    } else {
        $this->error = "Error ".$this->db->lasterror();
        dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
        return -1;
    }
}

public function getUserForProjectLeader($listProject, $filter = '')
{
    $users = array();

    $sql = "SELECT DISTINCT u.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec ON ec.fk_socpeople = u.rowid";
    $sql .= " WHERE ec.fk_c_type_contact = 161";
    $sql .= " AND ec.element_id IN (".$listProject.")";

    $sql .= $filter;
    //print $sql;

    $resql = $this->db->query($sql);
    if ($resql) {
        $num = $this->db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
            $obj = $this->db->fetch_object($resql);
            $users[] = $obj->rowid;
            $i++;
        }

        $this->db->free($resql);
    } else {
        dol_print_error($this->db);
    }

    return $users;
}

}