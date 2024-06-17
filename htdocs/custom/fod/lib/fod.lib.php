<?php
/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
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
 * \file    fod/lib/fod.lib.php
 * \ingroup fod
 * \brief   Library files with common functions for FOD
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function fodAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("fod@fod");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/fod/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/fod/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/fod/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@fod:/fod/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@fod:/fod/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'fod');

	return $head;
}

function getFodPieChart($FodListId = '') {
    global $conf, $db, $langs, $user;
  
    $result= '';
  
    if (empty($conf->fod->enabled)) {
    	return '';
    }
  
    $listofstatus = array(Fod::STATUS_DRAFT, Fod::STATUS_VALIDATEDRA, Fod::STATUS_VALIDATED, Fod::STATUS_AOA, Fod::STATUS_BILAN);
  
    $fodstatic = new Fod($db);
  
     $sql = "SELECT count(f.rowid) as nb, f.status as status";
     $sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
     if (empty($user->rights->fod->fod->readAll) && !$user->admin) {
        $sql .= " WHERE f.rowid IN (".$db->sanitize($FodListId).")"; // If we have this test true, it also means projectset is not 2
     }
     $sql .= " GROUP BY f.status";

     $resql = $db->query($sql);
     if ($resql) {
         $num = $db->num_rows($resql);
         $i = 0;
         $total = 0;
         $dataseries = array();
         $colorseries = array();
         $vals = array();
		 $vals[Fod::STATUS_BILAN] = 0;
		 $vals[5] = 0;

         while ($i < $num) {
             $obj = $db->fetch_object($resql);
             if ($obj) {
				 switch($obj->status){
					 case Fod::STATUS_DRAFT :
						$vals[Fod::STATUS_DRAFT] = $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_VALIDATED :
						$vals[Fod::STATUS_VALIDATED] = $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_VALIDATEDRA :
						$vals[5] += $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_VALIDATEDRARSR :
						$vals[5] += $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_VALIDATEDRSR :
						$vals[5] += $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_AOA :
						$vals[Fod::STATUS_AOA] = $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_BILAN :
						$vals[Fod::STATUS_BILAN] += $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_BILANinter :
						$vals[Fod::STATUS_BILAN] += $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_BILANRSR :
						$vals[Fod::STATUS_BILAN] += $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_BILANRSRRA :
						$vals[Fod::STATUS_BILAN] += $obj->nb;
                        $total += $obj->nb;
						break;
					 case Fod::STATUS_BILANRSRRAPCR :
						$vals[Fod::STATUS_BILAN] += $obj->nb;
                        $total += $obj->nb;
						break;
                    case Fod::STATUS_BILAN_REFUS :
                        $vals[Fod::STATUS_BILAN] += $obj->nb;
                        $total += $obj->nb;
                        break;
					 case Fod::STATUS_CLOTURE :
						$vals[Fod::STATUS_CLOTURE] = $obj->nb;
						break;
                    case Fod::STATUS_CANCELED :
                        $vals[Fod::STATUS_CANCELED] = $obj->nb;
                        break;
				 }  
             }
             $i++;
         }
         $db->free($resql);
  
         include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
  
         $result = '<div class="div-table-responsive-no-min">';
         $result .= '<table class="noborder nohover centpercent">';
  
         $result .=  '<tr class="liste_titre">';
         $result .=  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Fod").'</td>';
         $result .=  '</tr>';
  
         foreach ($listofstatus as $status) {
             $dataseries[] = array($fodstatic->LibStatut($status, 1), (isset($vals[$status]) ? (int) $vals[$status] : 0));
             if ($status == Fod::STATUS_DRAFT) {
                 $colorseries[$status] = '-'.$badgeStatus0;
             }
             if ($status == Fod::STATUS_VALIDATED) {
                 $colorseries[$status] = $badgeStatus4;
             }
             if ($status == Fod::STATUS_AOA) {
                 $colorseries[$status] = $badgeStatus1;
             }
             if ($status == Fod::STATUS_BILAN) {
                 $colorseries[$status] = $badgeStatus8;
             }
             if ($status == Fod::STATUS_CLOTURE) {
                 $colorseries[$status] = $badgeStatus6;
             }
			 if ($status == 5) {
				$colorseries[$status] = '-'.$badgeStatus4;
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
             $dolgraph->draw('idgraphthirdparties');
             $result .=  $dolgraph->show($total ? 0 : 1);
  
             $result .=  '</td>';
             $result .=  '</tr>';
         }
  
         $result .=  '<tr class="liste_total">';
         $result .=  '<td><strong>'.$langs->trans("Total").'</strong></td>';
         $result .=  '<td class="right"><strong>'.$total.'</strong></td>';
         $result .=  '</tr>';

         $result .=  '<tr class="liste_total">';
         $result .=  '<td>'.'FOD Clotûré'.'</td>';
         $result .=  '<td class="right">'.$vals[Fod::STATUS_CLOTURE].'</td>';
         $result .=  '</tr>';
         
         $result .=  '<tr class="liste_total">';
         $result .=  '<td>'.'FOD Annulé'.'</td>';
         $result .=  '<td class="right">'.$vals[Fod::STATUS_CANCELED].'</td>';
         $result .=  '</tr>';

         $result .=  '</table>';
         $result .=  '</div>';
         $result .=  '<br>';
     } else {
         dol_print_error($db);
     }
  
     return $result;
 }