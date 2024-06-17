<?php
/* Copyright (C) 2022 METZGER Leny <l.metzger@optim-industries.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    gpec/lib/gpec.lib.php
 * \ingroup gpec
 * \brief   Library files with common functions for GPEC
 */

require_once DOL_DOCUMENT_ROOT.'/custom/gpec/class/gpec.class.php';

/**
 * Prepare admin pages header
 *
 * @return array
 */
function gpecAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("gpec@gpec");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/gpec/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/gpec/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/gpec/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@gpec:/gpec/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@gpec:/gpec/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'gpec@gpec');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'gpec@gpec', 'remove');

	return $head;
}

function getGPECPieChart($onglet) {
    global $conf, $db, $langs, $user;
  
    $result= '';
  
    if (empty($conf->gpec->enabled)) {
    	return '';
    }
  
    $listofstatus = array(Gpec::STATUS_NON_RENSEIGNE, Gpec::STATUS_RENSEIGNE, Gpec::STATUS_VALIDATED);
  
    $gpec_static = new Gpec($db);
  
    $sql = "SELECT count(g.rowid) as nb_gpec, g.status as status";
    $sql .= " FROM ".MAIN_DB_PREFIX."gpec_gpec as g";
    $sql .= " WHERE g.onglet = ".$onglet;
    $sql .= " GROUP BY g.status";

	$sql2 = "SELECT count(u.rowid) as nb_user, u.statut as status";
    $sql2 .= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql2 .= " WHERE u.statut = 1";

    $resql = $db->query($sql);
	$resql2 = $db->query($sql2);

    if ($resql && $resql2) {
        $num = $db->num_rows($resql);
        $i = 0;
        $total = 0;
        $dataseries = array();
        $colorseries = array();
        $vals = array();

        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                switch($obj->status){
                    /*case Gpec::STATUS_NON_RENSEIGNE :
						$vals[Gpec::STATUS_NON_RENSEIGNE] = $obj->nb_gpec;
						$total += $obj->nb_gpec;
						break;*/
                    case Gpec::STATUS_RENSEIGNE :
						$vals[Gpec::STATUS_RENSEIGNE] = $obj->nb_gpec;
						//$total += $obj->nb_gpec;
						break;
                    case Gpec::STATUS_VALIDATED :
						$vals[Gpec::STATUS_VALIDATED] += $obj->nb_gpec;
						//$total += $obj->nb_gpec;
						break;
            	}
			}
            $i++;
        }
		$obj = $db->fetch_object($resql2);
		$total += $obj->nb_user;
		$vals[Gpec::STATUS_NON_RENSEIGNE] = $total - $vals[Gpec::STATUS_RENSEIGNE] - $vals[Gpec::STATUS_VALIDATED];

        $db->free($resql);

		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

        $result = '<div class="div-table-responsive-no-min">';
        $result .= '<table class="noborder nohover centpercent">';

        $result .=  '<tr class="liste_titre">';
		if($onglet == 1) {
			$result .=  '<td colspan="2">'.$langs->trans("Statistics").' - Compétences par Domaine'.'</td>';
		}
		if($onglet == 2) {
			$result .=  '<td colspan="2">'.$langs->trans("Statistics").' - Compétences Transverses'.'</td>';
		}
		if($onglet == 3) {
			$result .=  '<td colspan="2">'.$langs->trans("Statistics").' - Matrice Compétence'.'</td>';
		}
		$result .=  '</tr>';

        foreach ($listofstatus as $status) {
            $dataseries[] = array($gpec_static->LibStatut($status, 1), (isset($vals[$status]) ? (int) $vals[$status] : 0));
            if ($status == Gpec::STATUS_NON_RENSEIGNE) {
                $colorseries[$status] = '#ababab';
            }
            if ($status == Gpec::STATUS_RENSEIGNE) {
                $colorseries[$status] = '#d6d600';
            }
            if ($status == Gpec::STATUS_VALIDATED) {
                $colorseries[$status] = '#009c2f';
            }
        

            if (empty($conf->use_javascript_ajax)) {
                $result .=  '<tr class="oddeven">';
                $result .=  '<td>'.$propalstatic->LibStatut($status, 0).'</td>';
                $result .=  '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
                $result .=  "</tr>\n";
            }
        }

        if ($conf->use_javascript_ajax) {
            $result .=  '<tr>';
            $result .=  '<td align="center" colspan="2">';

            include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
            $dolgraph = new DolGraph();
            $dolgraph->SetData($dataseries);
            $dolgraph->SetDataColor(array_values($colorseries));
            $dolgraph->setShowLegend(2);
            $dolgraph->setShowPercent(1);
            $dolgraph->SetType(array('pie'));
            $dolgraph->setHeight('150');
            $dolgraph->setWidth('300');
            $dolgraph->draw('idgraphgpec_'.$onglet);
            $result .=  $dolgraph->show($total ? 0 : 1);

            $result .=  '</td>';
            $result .=  '</tr>';
        }

        $result .=  '<tr class="liste_total">';
        $result .=  '<td><strong>'.$langs->trans("Total").'</strong></td>';
        $result .=  '<td class="right"><strong>'.$total.'</strong></td>';
        $result .=  '</tr>';

        $result .=  '</table>';
        $result .=  '</div>';
        $result .=  '<br>';
    } else {
        dol_print_error($db);
    }

    return $result;
}
