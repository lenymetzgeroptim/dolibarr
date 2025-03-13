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
 *      \file       htdocs/projet/class/task.class.php
 *      \ingroup    project
 *      \brief      This file is a CRUD class file for Task (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


/**
 * 	Class to manage tasks
 */
class extendedTask extends Task
{

    /**
    *  Load properties of timespent of a task whithout ID.
    *
    *  @param	int		$fk_task 	Id of task
    *  @param	int		$date 	    Date of timespent
    *  @param	int		$fk_user 	Id of User
    *  @return  int		            <0 if KO, >0 if OK, 0 si aucun timespent n'a été trouvé
    */
    public function fetchTimeSpentWithoutId($fk_task, $date, $fk_user)
    {
        global $langs;

        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.fk_element,";
        $sql .= " t.element_date,";
        $sql .= " t.element_datehour,";
        $sql .= " t.element_date_withhour,";
        $sql .= " t.element_duration,";
        $sql .= " t.fk_user,";
        $sql .= " t.thm,";
        $sql .= " t.note";
        $sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
        $sql .= " WHERE t.fk_element = ".((int) $fk_task);
        $sql .= " AND t.elementtype = 'task'";
        $sql .= " AND t.element_date = '".($this->db->idate($date))."'";
        $sql .= " AND t.fk_user = ".((int) $fk_user);

        dol_syslog(get_class($this)."::fetchTimeSpentWithoutId", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->timespent_id = $obj->rowid;
                $this->id = $fk_task;
                $this->timespent_date = $this->db->jdate($obj->element_date);
                $this->timespent_datehour   = $this->db->jdate($obj->element_datehour);
                $this->timespent_withhour   = $obj->element_date_withhour;
                $this->timespent_duration = $obj->element_duration;
                $this->timespent_fk_user	= $obj->fk_user;
                $this->timespent_thm    	= $obj->thm; // hourly rate
                $this->timespent_note = $obj->note;

                $this->db->free($resql);
                return 1;
            }
            else {
                $this->timespent_id = null;
                $this->id = $fk_task;
                $this->timespent_date = null;
                $this->timespent_datehour   = null;
                $this->timespent_withhour   = null;
                $this->timespent_duration = null;
                $this->timespent_fk_user	= null;
                $this->timespent_thm    	= null; // hourly rate
                $this->timespent_note = null;

                $this->db->free($resql);
                return 0;
            }
        } else {
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }
    }

    /**
    *  Récupérer la note sans l'id du timespent
    *
    *  @param	int		$fk_task 	Id of task
    *  @param	int		$date 	    Date of timespent
    *  @param	int		$fk_user 	Id of User
    *  @return  int		            <0 if KO, >0 if OK, 0 si aucun timespent n'a été trouvé
    */
    public function getNoteWithoutId($fk_task, $date, $fk_user)
    {
        global $langs;

        $sql = "SELECT";
        $sql .= " t.note";
        $sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
        $sql .= " WHERE t.fk_element = ".((int) $fk_task);
        $sql .= " AND t.elementtype = 'task'";
        $sql .= " AND t.element_date = '".($this->db->idate($date))."'";
        $sql .= " AND t.fk_user = ".((int) $fk_user);

        dol_syslog(get_class($this)."::getNoteWithoutId", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->db->free($resql);
                return $obj->note;
            }
            else {
                $this->db->free($resql);
                return '';
            }
        } else {
            $this->error = "Error ".$this->db->lasterror();
            return '';
        }
    }

    /**
    *  Récupérer l'ensemble des notes d'un utilisateur entre 2 dates
    *
    *  @param	date		$date_debut 	    
    *  @param	int		    $date_fin 	
    *  @param	int		    $fk_user 	Id of User
    *  @return  array		Tableau avec l'ensemble des notes 
    */
    public function fetchAllNotes($date_debut, $date_fin, $fk_user, $columnmode = 0)
    {
        global $langs;
        $notes = array();

        $sql = "SELECT";
        $sql .= " t.note, t.fk_element, t.element_date";
        $sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
        $sql .= " WHERE t.element_date >= '".($this->db->idate($date_debut))."'";
        $sql .= " AND t.elementtype = 'task'";
        $sql .= " AND t.element_date <= '".($this->db->idate($date_fin))."'";
        $sql .= " AND t.fk_user = ".((int) $fk_user);

        dol_syslog(get_class($this)."::fetchAllNotes", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            for($i=0; $i<$num; $i++){
                $obj = $this->db->fetch_object($resql);
                if($columnmode) {
                    $notes[$this->db->jdate($obj->element_date)][] = $obj->note;
                }
                else {
                    $notes[$obj->fk_element][$this->db->jdate($obj->element_date)] = $obj->note;
                }
            }
            $this->db->free($resql);
            return $notes;
        } else {
            $this->error = "Error ".$this->db->lasterror();
            return '';
        }
    }

    /**
    *  Récupérer les heures sup d'un projet
    *
    *  @param	int		$projetid 	Id of Project
    *  @return  array		            
    */
    public function getSumOfAmount_HS($projetid)
    {
        global $langs;

        /*if (empty($id)) {
            $id = $this->id;
        }*/

        $result = array();

        $sql = "SELECT";
        $sql .= " SUM(hs.heure_sup_25_duration) as nbseconds_hs25,";
        $sql .= " SUM(hs.heure_sup_50_duration) as nbseconds_hs50,";
        $sql .= " SUM(hs.heure_sup_25_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm * 0.25").") as amount_hs25,";
        $sql .= " SUM(hs.heure_sup_50_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm * 0.5").") as amount_hs50";
        $sql .= " FROM ".MAIN_DB_PREFIX."feuilledetemps_projet_task_time_heure_sup as hs";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as t ON t.rowid = hs.fk_projet_task_time";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as p ON p.rowid = t.fk_element";
        $sql .= " WHERE p.fk_projet = ".((int) $projetid);
        $sql .= " AND t.elementtype = 'task'";
        /*if (is_object($fuser) && $fuser->id > 0) {
            $sql .= " AND fk_user = ".((int) $fuser->id);
        }
        if ($dates > 0) {
            $datefieldname = "element_datehour";
            $sql .= " AND (".$datefieldname." >= '".$this->db->idate($dates)."' OR ".$datefieldname." IS NULL)";
        }
        if ($datee > 0) {
            $datefieldname = "element_datehour";
            $sql .= " AND (".$datefieldname." <= '".$this->db->idate($datee)."' OR ".$datefieldname." IS NULL)";
        }
        //print $sql;*/

        dol_syslog(get_class($this)."::getSumOfAmount_HS", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);

            $result['amount_hs25'] = $obj->amount_hs25;
            $result['amount_hs50'] = $obj->amount_hs50;
            $result['nbseconds_hs25'] = $obj->nbseconds_hs25;
            $result['nbseconds_hs50'] = $obj->nbseconds_hs50;

            $this->db->free($resql);
            return $result;
        } else {
            dol_print_error($this->db);
            return $result;
        }
    }

    /**
    *  Récupérer les temps consommés d'un projet
    *
    *  @param	int		$projetid 	Id of Project
    *  @return  array		            
    */
    public function getDuration($projetid){
        global $langs;

        $result = array();

        $sql = "SELECT";
        $sql .= " t.element_duration, t.element_date, t.thm";
        $sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as p ON p.rowid = t.fk_element";
        $sql .= " WHERE p.fk_projet = ".((int) $projetid);

        dol_syslog(get_class($this)."::getDuration", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $result[$i]['duration'] = $obj->element_duration;
                $result[$i]['date'] = $this->db->jdate($obj->element_date);
                $result[$i]['thm'] = $obj->thm;
                $i++;
            }
            $this->db->free($resql);
            return $result;
        } else {
            dol_print_error($this->db);
            return $result;
        }
    }

    /**
    *  Récupérer les taches sur lequel un utilisateur a des temps consommés sur une periode donnée
    *
    *  @param	date		$firstdate 	1ere date
    *  @param	date		$lastdate 	2nd date
    *  @param	int		    $userid 	Id of User
    *  @return  array		            
    */
    public function getTask($firstdate, $lastdate, $userid){
        global $langs;

        $result = array();

        $sql = "SELECT";
        $sql .= " DISTINCT(t.fk_element)";
        $sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
        $sql .= " WHERE t.fk_user = ".((int) $userid);
        $sql .= " AND t.elementtype = 'task'";
        $sql .= " AND t.element_date >= '".($this->db->idate($firstdate))."'";
        $sql .= " AND t.element_date <= '".($this->db->idate($lastdate))."'";


        dol_syslog(get_class($this)."::getTask", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $result[] = $obj->fk_element;
                $i++;
            }
            $this->db->free($resql);
            return $result;
        } else {
            dol_print_error($this->db);
            return $result;
        }
    }

    public function fetchAllTimeSpent(User $userobj, $morewherefilter = '')
    {
      $arrayres = array();
   
      $sql = "SELECT";
      $sql .= " s.rowid as socid,";
      $sql .= " s.nom as thirdparty_name,";
      $sql .= " s.email as thirdparty_email,";
      $sql .= " ptt.rowid,";
      $sql .= " ptt.fk_element as fk_task,";
      $sql .= " ptt.element_date as task_date,";
      $sql .= " ptt.element_datehour as task_datehour,";
      $sql .= " ptt.element_date_withhour as task_date_withhour,";
      $sql .= " ptt.element_duration as task_duration,";
      $sql .= " ptt.fk_user,";
      $sql .= " ptt.note,";
      $sql .= " ptt.thm,";
      $sql .= " pt.rowid as task_id,";
      $sql .= " pt.ref as task_ref,";
      $sql .= " pt.label as task_label,";
      $sql .= " p.rowid as project_id,";
      $sql .= " p.ref as project_ref,";
      $sql .= " p.title as project_label,";
      $sql .= " p.public as public";
      $sql .= " FROM ".MAIN_DB_PREFIX."element_time as ptt, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
      $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
      $sql .= " WHERE ptt.fk_element = pt.rowid AND pt.fk_projet = p.rowid";
      $sql .= " AND ptt.elementtype = 'task'";
      $sql .= " AND ptt.fk_user = ".((int) $userobj->id);
      $sql .= " AND pt.entity IN (".getEntity('project').")";
      if ($morewherefilter) {
        $sql .= $morewherefilter;
      }
   
      dol_syslog(get_class($this)."::fetchAllTimeSpent", LOG_DEBUG);
      $resql = $this->db->query($sql);
      if ($resql) {
        $num = $this->db->num_rows($resql);
   
        $i = 0;
        while ($i < $num) {
          $obj = $this->db->fetch_object($resql);
   
          $newobj = new stdClass();
   
          $newobj->socid              = $obj->socid;
          $newobj->thirdparty_name    = $obj->thirdparty_name;
          $newobj->thirdparty_email   = $obj->thirdparty_email;
   
          $newobj->fk_project     = $obj->project_id;
          $newobj->project_ref    = $obj->project_ref;
          $newobj->project_label = $obj->project_label;
          $newobj->public       = $obj->project_public;
   
          $newobj->fk_task      = $obj->task_id;
          $newobj->task_ref = $obj->task_ref;
          $newobj->task_label = $obj->task_label;
   
          $newobj->timespent_id = $obj->rowid;
          $newobj->timespent_date = $this->db->jdate($obj->task_date);
          $newobj->timespent_datehour = $this->db->jdate($obj->task_datehour);
          $newobj->timespent_withhour = $obj->task_date_withhour;
          $newobj->timespent_duration = $obj->task_duration;
          $newobj->timespent_fk_user = $obj->fk_user;
          $newobj->timespent_thm = $obj->thm; // hourly rate
          $newobj->timespent_note = $obj->note;
   
          $arrayres[$this->db->jdate($obj->task_date)][] = $newobj;
   
          $i++;
        }
   
        $this->db->free($resql);
      } else {
        dol_print_error($this->db);
        $this->error = "Error ".$this->db->lasterror();
        return -1;
      }
   
      return $arrayres;
    }

    public function fetchAllTimeSpentId(User $userobj, $morewherefilter = '')
    {
      $arrayres = array();
   
      $sql = "SELECT";
      $sql .= " ptt.rowid";
      $sql .= " FROM ".MAIN_DB_PREFIX."element_time as ptt, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
      $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
      $sql .= " WHERE ptt.fk_element = pt.rowid AND pt.fk_projet = p.rowid";
      $sql .= " AND ptt.elementtype = 'task'";
      $sql .= " AND ptt.fk_user = ".((int) $userobj->id);
      $sql .= " AND pt.entity IN (".getEntity('project').")";
      if ($morewherefilter) {
        $sql .= $morewherefilter;
      }
   
      dol_syslog(get_class($this)."::fetchAllTimeSpentId", LOG_DEBUG);
      $resql = $this->db->query($sql);
      if ($resql) {
        $num = $this->db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
          $obj = $this->db->fetch_object($resql);
   
          $arrayres[] = $obj->rowid;
   
          $i++;
        }
   
        $this->db->free($resql);
      } else {
        dol_print_error($this->db);
        $this->error = "Error ".$this->db->lasterror();
        return -1;
      }
   
      return $arrayres;
    }
}