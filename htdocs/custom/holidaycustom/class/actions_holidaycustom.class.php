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

require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';

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
        
                /*$holidaystatic->fetchByUser($user->id);
                $out = '';
                $nb_holiday = 0;
                $typeleaves = $holidaystatic->getTypes(1, 1);
                foreach ($typeleaves as $key => $val) {
                    if($val['code'] != 'ACP'){
                        $nb_type = $holidaystatic->getCPforUser($user->id, $val['rowid']);
                        $nb_type = ($nb_type < 0 ? $nb_type*(-1) : $nb_type);
                        $nb_holiday += $nb_type;
                        $out .= '<li>'.($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']).': <strong>'.($nb_type ? price2num($nb_type) : 0).'</strong></li>';
                    }
                }*/
                $balancetoshow = $langs->trans('SoldeCPUser2', '{s1}');

                $typeleaves_CP_N1_ACQUIS = $holidaystatic->getTypesCP(1, 'CP_N-1_ACQUIS');
                $typeleaves_CP_N1_PRIS = $holidaystatic->getTypesCP(1, 'CP_N-1_PRIS');
                $typeleaves_CP_N_ACQUIS = $holidaystatic->getTypesCP(1, 'CP_N_ACQUIS');
                $typeleaves_CP_N_PRIS = $holidaystatic->getTypesCP(1, 'CP_N_PRIS');
                $typeleaves_CP_FRAC_ACQUIS = $holidaystatic->getTypesCP(1, 'CP_FRAC_ACQUIS');
                $typeleaves_CP_FRAC_PRIS = $holidaystatic->getTypesCP(1, 'CP_FRAC_PRIS');
                $typeleaves_CP_ANC_ACQUIS = $holidaystatic->getTypesCP(1, 'CP_ANC_ACQUIS');
                $typeleaves_CP_ANC_PRIS = $holidaystatic->getTypesCP(1, 'CP_ANC_PRIS');
                $typeleaves_RTT_ACQUIS = $holidaystatic->getTypesCP(1, 'RTT_ACQUIS');
                $typeleaves_RTT_PRIS = $holidaystatic->getTypesCP(1, 'RTT_PRIS');
                $typeleaves_ACP = $holidaystatic->getTypesCP(1, 'ACP');

                $nb_N1_ACQUIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_N1_ACQUIS['rowid']);
                $nb_N1_ACQUIS = ($nb_N1_ACQUIS ? price2num($nb_N1_ACQUIS) : 0);
                $nb_N1_PRIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_N1_PRIS['rowid']);
                $nb_N1_PRIS = ($nb_N1_PRIS ? price2num($nb_N1_PRIS) : 0);
                $nb_N1_SOLDE = $nb_N1_ACQUIS-$nb_N1_PRIS;

                $nb_N_ACQUIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_N_ACQUIS['rowid']);
                $nb_N_ACQUIS = ($nb_N_ACQUIS ? price2num($nb_N_ACQUIS) : 0);
                $nb_N_PRIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_N_PRIS['rowid']);
                $nb_N_PRIS = ($nb_N_PRIS ? price2num($nb_N_PRIS) : 0);
                $nb_N_SOLDE = $nb_N_ACQUIS-$nb_N_PRIS;

                $nb_FRAC_ACQUIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_FRAC_ACQUIS['rowid']);
                $nb_FRAC_ACQUIS = ($nb_FRAC_ACQUIS ? price2num($nb_FRAC_ACQUIS) : 0);
                $nb_FRAC_PRIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_FRAC_PRIS['rowid']);
                $nb_FRAC_PRIS = ($nb_FRAC_PRIS ? price2num($nb_FRAC_PRIS) : 0);
                $nb_FRAC_SOLDE = $nb_FRAC_ACQUIS-$nb_FRAC_PRIS;

                $nb_ANC_ACQUIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_ANC_ACQUIS['rowid']);
                $nb_ANC_ACQUIS = ($nb_ANC_ACQUIS ? price2num($nb_ANC_ACQUIS) : 0);
                $nb_ANC_PRIS = $holidaystatic->getCPforUser($user->id, $typeleaves_CP_ANC_PRIS['rowid']);
                $nb_ANC_PRIS = ($nb_ANC_PRIS ? price2num($nb_ANC_PRIS) : 0);
                $nb_ANC_SOLDE = $nb_ANC_ACQUIS-$nb_ANC_PRIS;

                $nb_RTT_ACQUIS = $holidaystatic->getCPforUser($user->id, $typeleaves_RTT_ACQUIS['rowid']);
                $nb_RTT_ACQUIS = ($nb_RTT_ACQUIS ? price2num($nb_RTT_ACQUIS) : 0);
                $nb_RTT_PRIS = $holidaystatic->getCPforUser($user->id, $typeleaves_RTT_PRIS['rowid']);
                $nb_RTT_PRIS = ($nb_RTT_PRIS ? price2num($nb_RTT_PRIS) : 0);
                $nb_RTT_SOLDE = $nb_RTT_ACQUIS-$nb_RTT_PRIS;
                $nb_ACP = $holidaystatic->getCPforUser($user->id, $typeleaves_ACP['rowid']);
                $nb_ACP_SOLDE = ($nb_ACP ? price2num($nb_ACP) : 0);

                //$holidaystatic->fetchByUser($user->id, '', " AND cp.date_debut > '".dol_print_date(dol_now(), "%Y-%m-%d")."' AND cp.fk_type = 1 AND cp.statut = 1");
                $holidaystatic->fetchByUser($user->id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 1");
                $nb_conges_brouillon = 0;
                foreach($holidaystatic->holiday as $key => $conges){
                    $nb_conges_brouillon += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
                }
                $holidaystatic->holiday = array();
                $holidaystatic->fetchByUser($user->id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 2");
                $nb_conges_attente_approbation = 0;
                foreach($holidaystatic->holiday as $key => $conges){
                    $nb_conges_attente_approbation += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
                }
                $holidaystatic->holiday = array();
                $holidaystatic->fetchByUser($user->id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 6");
                $nb_conges_cours_approbation = 0;
                foreach($holidaystatic->holiday as $key => $conges){
                    $nb_conges_cours_approbation += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
                }
                $holidaystatic->holiday = array();
                $holidaystatic->fetchByUser($user->id, '', " AND cp.fk_type IN (1, 101, 102, 103) AND cp.statut = 3 AND cp.date_debut >= '".$db->idate(dol_now())."'");
                $nb_conges_approuve = 0;
                foreach($holidaystatic->holiday as $key => $conges){
                    $nb_conges_approuve += num_open_day($conges['date_debut_gmt'], $conges['date_fin_gmt'], 0, 1, $conges['halfday']);
                }


                print '<div class="valignmiddle div-balanceofleave center">'.str_replace('{s1}', img_picto('', 'holiday', 'class="paddingleft pictofixedwidth"').'<span class="balanceofleave valignmiddle'.($nb_ACP > 0 ? ' amountpaymentcomplete' : ($nb_ACP < 0 ? ' amountremaintopay' : ' amountpaymentneutral')).'">'.round($nb_ACP, 5).'</span>', $balancetoshow).'</div>';
                print '<div class="valignmiddle div-balanceofleave center">';
                print 'Dont <span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_brouillon.'</span> en brouillon, ';
                print '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_attente_approbation.'</span> en attente d\'approbation, ';
                print '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_cours_approbation.'</span> en cours d\'approbation, et ';
                print '<span class="balanceofleave valignmiddle amountpaymentcomplete">'.$nb_conges_approuve.'</span> approuvé(s) à venir';
                print '</div>';

                print '<br><table class="noborder nohover" style="text-align: center; width: 96%; margin: auto;">';
                print '<tr>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">Acquis<br>CP N</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">Pris<br>CP N</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;"><strong>Solde<br>CP N</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">Acquis<br>CP N-1</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">Pris<br>CP N-1</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;"><strong>Solde<br>CP N-1</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">Acquis<br>CP Anc</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">Pris<br>CP Anc</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;"><strong>Solde<br>CP Anc</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">Acquis<br>CP Frac</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">Pris<br>CP Frac</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;"><strong>Solde<br>CP Frac</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">Acquis<br>RTT</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">Pris<br>RTT</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;"><strong>Solde<br>RTT</strong></td>';
                print '</tr>';
                print '<tr>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">'.$nb_N_ACQUIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;">'.$nb_N_PRIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #0070ff2e; width: 6.4%;"><strong>'.$nb_N_SOLDE.'</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">'.$nb_N1_ACQUIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;">'.$nb_N1_PRIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #29ff695c; width: 6.4%;"><strong>'.$nb_N1_SOLDE.'</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">'.$nb_ANC_ACQUIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;">'.$nb_ANC_PRIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ff480030; width: 6.4%;"><strong>'.$nb_ANC_SOLDE.'</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">'.$nb_FRAC_ACQUIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;">'.$nb_FRAC_PRIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #ffb30038; width: 6.4%;"><strong>'.$nb_FRAC_SOLDE.'</strong></td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">'.$nb_RTT_ACQUIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;">'.$nb_RTT_PRIS.'</td>';
                print '<td style="border :#b9b9b9 0.5px solid; background-color: #c8c8c8; width: 6.4%;"><strong>'.$nb_RTT_SOLDE.'</strong></td>';
                print '</tr>';
                print '</table><br>';
               // print '<span class="opacitymedium"><strong>Types de congés pris :</strong><br><ul style="margin-top: 0;">'.$out.'<ul></span>';
        
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
}