<?php
/* Copyright (C) 2022 METZGER Leny <l.metzger@optim-industries.fr>
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


class ActionsHolidayCustom 
{ 
	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function dashboardHRM($parameters, &$object, &$action, $hookmanager)
	{
        global $conf, $user, $langs, $db; 
        $langs->loadLangs(array("holidaycustom@holidaycustom"));

		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

		require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';

        if(!empty($conf->holidaycustom->enabled) && $user->rights->holidaycustom->read){
			print '<div class="fichethirdleft">';
            if (empty($conf->global->HOLIDAY_HIDE_BALANCE)) {
                $holidaystatic = new Holiday($db);
                $user_id = $user->id;

                print '<div class="div-table-responsive-no-min">';
                print '<table class="noborder nohover centpercent">';
                print '<tr class="liste_titre"><th colspan="3">'.$langs->trans("Holidays").'</th></tr>';
                print '<tr class="oddeven">';
                print '<td>';
        
                print $holidaystatic->getArrayHoliday($user_id, 1, 0);

                print '</td>';
                print '</tr>';
                print '</table></div>';
            } 
            elseif (!is_numeric($conf->global->HOLIDAY_HIDE_BALANCE)) {
                print $langs->trans($conf->global->HOLIDAY_HIDE_BALANCE).'<br>';
            }

			$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.email, u.photo, u.statut as user_status,";
			$sql .= " x.rowid, x.ref, x.fk_type, x.date_debut as date_start, x.date_fin as date_end, x.halfday, x.tms as dm, x.statut as status";
			$sql .= " FROM ".MAIN_DB_PREFIX."holiday as x, ".MAIN_DB_PREFIX."user as u";
			$sql .= " WHERE u.rowid = x.fk_user";
			$sql .= " AND x.entity = ".$conf->entity;
			if (!$user->hasRight('holidaycustom', 'readall')) {
				//$sql .= ' AND x.fk_user IN ('.$db->sanitize(join(',', $childids)).')';
				$sql .= ' AND x.fk_user IN ('.$user->id.')';
			}
			//if (empty($user->rights->societe->client->voir) && !$user->socid) $sql.= " AND x.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			//if (!empty($socid)) $sql.= " AND x.fk_soc = ".((int) $socid);
			$sql .= $db->order("x.tms", "DESC");
			$sql .= $db->plimit($max, 0);
		
			$result = $db->query($sql);
			if ($result) {
				$var = false;
				$num = $db->num_rows($result);
		
				$holidaystatic = new Holiday($db);
				$userstatic = new User($db);
		
				$listhalfday = array('morning'=>$langs->trans("Morning"), "afternoon"=>$langs->trans("Afternoon"));
				$typeleaves = $holidaystatic->getTypes(1, -1);
		
				$i = 0;
		
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<th colspan="3">'.$langs->trans("BoxTitleLastLeaveRequests", min($max, $num)).'</th>';
				print '<th>'.$langs->trans("from").'</th>';
				print '<th>'.$langs->trans("to").'</th>';
				print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/holiday/list.php?sortfield=cp.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
				print '</tr>';
				if ($num) {
					while ($i < $num && $i < $max) {
						$obj = $db->fetch_object($result);
		
						$holidaystatic->id = $obj->rowid;
						$holidaystatic->ref = $obj->ref;
						$holidaystatic->statut = $obj->status;
						$holidaystatic->date_debut = $db->jdate($obj->date_start);
		
						$userstatic->id = $obj->uid;
						$userstatic->lastname = $obj->lastname;
						$userstatic->firstname = $obj->firstname;
						$userstatic->login = $obj->login;
						$userstatic->photo = $obj->photo;
						$userstatic->email = $obj->email;
						$userstatic->statut = $obj->user_status;
						$userstatic->status = $obj->user_status;
		
						print '<tr class="oddeven">';
						print '<td class="nowraponall">'.$holidaystatic->getNomUrl(1).'</td>';
						print '<td class="tdoverflowmax100">'.$userstatic->getNomUrl(-1, 'leave').'</td>';
		
						$leavecode = empty($typeleaves[$obj->fk_type]) ? 'Undefined' : $typeleaves[$obj->fk_type]['code'];
						print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($langs->trans($leavecode)).'">'.dol_escape_htmltag($langs->trans($leavecode)).'</td>';
		
						$starthalfday = ($obj->halfday == -1 || $obj->halfday == 2) ? 'afternoon' : 'morning';
						$endhalfday = ($obj->halfday == 1 || $obj->halfday == 2) ? 'morning' : 'afternoon';
		
						print '<td class="tdoverflowmax125">'.dol_print_date($db->jdate($obj->date_start), 'day').' <span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
						print '<td class="tdoverflowmax125">'.dol_print_date($db->jdate($obj->date_end), 'day').' <span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
						print '<td class="right">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>';
						print '<td class="right nowrap" width="16">'.$holidaystatic->LibStatut($obj->status, 3, $holidaystatic->date_debut).'</td>';
						print '</tr>';
		
						$i++;
					}
				} else {
					print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
				}
				print '</table>';
				print '</div>';
				print '<br>';
			} else {
				dol_print_error($db);
			}
			print '</div><br>';
        }

		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	function addSearchEntry($parameters, $object, $action) { 
        global $conf,$langs;

		if (!empty($conf->holidaycustom->enabled)) {
			$this->results = array('searchintoholiday'=>array('position'=>50, 'img'=>'holiday', 'label'=>$langs->trans("SearchIntoHoliday"), 'text'=>img_picto('', 'holiday').' '.$langs->trans("Holiday"), 'url'=>DOL_URL_ROOT.'/custom/holidaycustom/list.php?'.($parameters->search_boxvalue ? '&search_id='.urlencode($parameters->search_boxvalue) : '')));
		}
       
		return 0;
    }

}