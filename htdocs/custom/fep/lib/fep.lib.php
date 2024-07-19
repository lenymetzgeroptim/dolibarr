<?php
/* Copyright (C) 2021 METZGER Leny <l.metzger@optim-industries.fr>
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
 * \file    fep/lib/fep.lib.php
 * \ingroup fep
 * \brief   Library files with common functions for FEP
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function fepAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("fep@fep");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/fep/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/fep/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/fep/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@fep:/fep/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@fep:/fep/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'fep');

	return $head;
}

function getFepBarsChart($date_start, $date_end) {
    global $conf, $db, $langs, $user;
  
    $result= '';
  
    if (empty($conf->fod->enabled)) {
    	return '';
    }
  
    $listoftheme = array('Relations TC', 'Moyens mis en oeuvre', 'Sureté et organisation qualité', 'Sécurité et radioprotection', 'Environnement', 'Qualité technique du projet', 'Gestion des délais et planning', 'Note globale');
  
    $fep = new Fep($db);
  
    $sql = "SELECT SUM(f.note_theme1) as note1,";
	$sql .= " count(f.note_theme1) as nb_note1,";
	$sql .= " SUM(f.note_theme2) as note2,";
	$sql .= " count(f.note_theme2) as nb_note2,";
	$sql .= " SUM(f.note_theme3) as note3,";
	$sql .= " count(f.note_theme3) as nb_note3,";
	$sql .= " SUM(f.note_theme4) as note4,";
	$sql .= " count(f.note_theme4) as nb_note4,";
	$sql .= " SUM(f.note_theme5) as note5,";
	$sql .= " count(f.note_theme5) as nb_note5,";
	$sql .= " SUM(f.note_theme6) as note6,";
	$sql .= " count(f.note_theme6) as nb_note6,";
	$sql .= " SUM(f.note_theme7) as note7,";
	$sql .= " count(f.note_theme7) as nb_note7,";
	$sql .= " count(f.rowid) as nb_fep";
    $sql .= " FROM ".MAIN_DB_PREFIX."fep_fep as f";
	if(!empty($date_start)){
		$sql .= " WHERE f.date_publication >= '".$db->idate($date_start)."'";
	}
	if(!empty($date_end) && empty($date_start)){
		$sql .= " WHERE f.date_publication <= '".$db->idate($date_end)."'";
	}
	else if (!empty($date_end)){
		$sql .= " AND f.date_publication <= '".$db->idate($date_end)."'";
	}
	if(empty($date_end) && empty($date_start)){
		$sql .= ' WHERE f.date_publication IS NOT NULL';
	}

    $resql = $db->query($sql);
    
	if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;
        $total = 0;
    	$dataseries = array();
        $colorseries = array();
        $vals = array();

        $obj = $db->fetch_object($resql);
        if ($obj) {
			$vals['Relations TC'] = ($obj->nb_note1 ? $obj->note1 / $obj->nb_note1 : 0);
			$vals['Moyens mis en oeuvre'] = ($obj->nb_note2 ? $obj->note2 / $obj->nb_note2 : 0);
			$vals['Sureté et organisation qualité'] = ($obj->nb_note3 ? $obj->note3 / $obj->nb_note3 : 0);
			$vals['Sécurité et radioprotection'] = ($obj->nb_note4 ? $obj->note4 / $obj->nb_note4 : 0);
			$vals['Environnement'] = ($obj->nb_note5 ? $obj->note5 / $obj->nb_note5 : 0);
			$vals['Qualité technique du projet'] = ($obj->nb_note6 ? $obj->note6 / $obj->nb_note6 : 0);
			$vals['Gestion des délais et planning'] = ($obj->nb_note7 ? $obj->note7 / $obj->nb_note7 : 0);
			$total += $obj->nb_fep;
        }
        $db->free($resql);
  
        include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
  
        $result = '<div class="div-table-responsive-no-min">';
        $result .= '<table class="noborder nohover centpercent">';
  
        $result .=  '<tr class="liste_titre">';
        $result .=  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("FEP/QS").'</td>';
        $result .=  '</tr>';
  
        foreach ($listoftheme as $theme) {
			$dataseries[] = array($theme, (isset($vals[$theme]) ? (double) $vals[$theme] : 0));
			$colorseries[$theme] = 'rgb(47,80,139)';
        }
  
        if ($conf->use_javascript_ajax) {
            $result .=  '<tr>';
            $result .=  '<td align="center" colspan="2">';
            include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
            $dolgraph = new ExtendedDolGraph();
            $dolgraph->SetData($dataseries);
            $dolgraph->SetDataColor(array_values($colorseries));
			$dolgraph->setLegend(array('Moyenne'));
            $dolgraph->setShowLegend(1);
            $dolgraph->SetType(array('bars'));
            $dolgraph->setHeight('300');
            $dolgraph->setWidth('500');
            $dolgraph->draw('graphfep');
            $result .=  $dolgraph->show(($total ? 0 : 1));
  
            $result .=  '</td>';
            $result .=  '</tr>';
        }
  
		$result .=  '<tr class="liste_total">';
		$result .=  '<td>'.$langs->trans("Total").'</td>';
		$result .=  '<td class="right">'.$total.'</td>';
		$result .=  '</tr>';

		$result .=  '</table>';
		$result .=  '</div>';
		$result .=  '<br>';
	} 
	else {
		dol_print_error($db);
	}

	return $result;
}

function getFepRetour($year) {
    global $conf, $db, $langs, $user;
  
    $result= '';
	$error = 0;
	$nb_retour = 0;
	$commande_fep = array();
  
    if (empty($conf->fod->enabled)) {
    	return '';
    }
  
    //$listoftheme = array('Relations TC', 'Moyens mis en oeuvre', 'Sureté et organisation qualité', 'Sécurité et radioprotection', 'Environnement', 'Qualité technique du projet', 'Gestion des délais et planning', 'Note globale');
  
    $fep = new Fep($db);
  
    $sql = "SELECT c.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	if(!empty($year)){
		$sql .= " WHERE c.date_commande >= '".$year."-01-01'";
		$sql .= " AND c.date_commande <= '".$year."-12-31'";
	}


    $resql = $db->query($sql);

	$sql = "SELECT fc.fk_commande";
    $sql .= " FROM ".MAIN_DB_PREFIX."fep_commande as fc";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fep_fep as f ON f.rowid = fc.fk_fep";
	if(!empty($year)){
		$sql .= " WHERE f.date_publication >= '".$year."-01-01'";
		$sql .= " AND f.date_publication <= '".($year+1)."-01-31'";
	}
	if(empty($year)){
		$sql .= ' WHERE f.date_publication IS NOT NULL';
	}

    $resql2 = $db->query($sql);
    
	if ($resql && $resql2){
		$i = 0;
		while($i < $db->num_rows($resql2)){
			$obj = $db->fetch_object($resql2);
			$i++;
			$commande_fep[] = $obj->fk_commande;
		}

		$nb_commande = $db->num_rows($resql);
		$i = 0;
		while($i < $nb_commande){
			$obj = $db->fetch_object($resql);
			$i++;
			if (in_array($obj->rowid, $commande_fep)){
				$nb_retour++;
			}
		}

		$dataseries = array();
		$colorseries = array();
		$vals = array();

		$db->free($resql);
		$db->free($resql2);

		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

		$result = '<div class="div-table-responsive-no-min">';
		$result .= '<table class="noborder nohover centpercent">';

		$result .=  '<tr class="liste_titre">';
		$result .=  '<td colspan="2">'.$langs->trans("Pourcentage de retour").' - '.$langs->trans("FEP/QS").'</td>';
		$result .=  '</tr>';

		$dataseries[] = array('Retour', ($nb_retour / $nb_commande) * 100);
		$dataseries[] = array('Pas de retour', 100 - ($nb_retour / $nb_commande) * 100);
		$colorseries['Retour'] = '#2f508b';
		$colorseries['Pas de retour'] = '#7b1515';

		if ($conf->use_javascript_ajax) {
			$result .=  '<tr>';
			$result .=  '<td align="center" colspan="2">';
			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->SetDataColor(array_values($colorseries));
			//$dolgraph->setShowPercent(1);
			//$dolgraph->setLegend(array('Moyenne'));
			$dolgraph->setShowLegend(2);
			$dolgraph->SetType(array('pie'));
			$dolgraph->setHeight('200');
			$dolgraph->setWidth('400');
			$dolgraph->draw('retourfep');
			$result .=  $dolgraph->show(($nb_commande ? 0 : 1));

			$result .=  '</td>';
			$result .=  '</tr>';
		}

		$result .=  '<tr class="liste_total">';
		$result .=  '<td>'.$langs->trans("Commandes").'</td>';
		$result .=  '<td class="right">'.$nb_commande.'</td>';
		$result .=  '</tr>';
		$result .=  '<tr class="liste_total">';
		$result .=  '<td>'.$langs->trans("Retours").'</td>';
		$result .=  '<td class="right">'.$nb_retour.'</td>';
		$result .=  '</tr>';

		$result .=  '</table>';
		$result .=  '</div>';
		$result .=  '<br>';
	}
	else {
		dol_print_error($db);
	}

	return $result;
}