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

		require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';

        if(!empty($conf->holidaycustom->enabled) && $user->rights->holidaycustom->read){
            if (empty($conf->global->HOLIDAY_HIDE_BALANCE)) {
                $holidaystatic = new Holiday($db);
                $user_id = $user->id;

                print '<div class="fichethirdleft">';
                print '<div class="div-table-responsive-no-min">';
                print '<table class="noborder nohover centpercent">';
                print '<tr class="liste_titre"><th colspan="3">'.$langs->trans("Holidays").'</th></tr>';
                print '<tr class="oddeven">';
                print '<td>';
        
                print $holidaystatic->getArrayHoliday($user_id, 1, 0);

                print '</td>';
                print '</tr>';
                print '</table></div></div><br>';
            } 
            elseif (!is_numeric($conf->global->HOLIDAY_HIDE_BALANCE)) {
                print $langs->trans($conf->global->HOLIDAY_HIDE_BALANCE).'<br>';
            }
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