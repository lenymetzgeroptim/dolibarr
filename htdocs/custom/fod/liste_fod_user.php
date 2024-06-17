<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 METZGER Leny <l.metzger@optim-industries.fr>
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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/custom/configurationaccidentaccueil/lib/configurationaccidentaccueil.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/fod/class/fod.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("fod@fod"));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$id = GETPOST('id', 'integer');

if (empty($conf->fod->enabled)) accessforbidden();



/*
 * View
 */

if ($user->rights->user->user->lire || $id == $user->id){
	$form = new Form($db);
	$object = New User($db);
    $fod = new Fod($db);
    $projectstatic = new Project($db);
    $formfile = new FormFile($db);

	$help_url = '';
	$page_name = "Liste FOD";

	llxHeader('', $page_name, $help_url);

	$res = $object->fetch($id, '', '', 1);
	if ($res <= 0) {
		dol_print_error($db, $object->error);
		exit;
	}
	$res = $object->fetch_optionals();

	// Check if user has rights
	if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$object->getrights();
		if (empty($object->nb_rights) && $object->statut != 0 && empty($object->admin)) {
			setEventMessages($langs->trans('UserHasNoPermissions'), null, 'warnings');
		}
	}

	$head = user_prepare_head($object);

	print dol_get_fiche_head($head, 'liste_fod', $title, -1, 'user');

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="fichecenter"><div class="underbanner clearboth"></div><br>';

    // Toutes les Fod de l'utiliateur
    $sql = "SELECT DISTINCT f.rowid as fod_id, f.ref ref_fod, f.date_debut, f.date_fin, f.indice, f.client_site, f.status as status";
    $sql .= ", p.rowid, p.ref, p.title";
    $sql .= " FROM ".MAIN_DB_PREFIX."fod_fod as f";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p on f.fk_project = p.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fod_user as fu on f.rowid = fu.fk_fod";
    $sql .= " WHERE fu.fk_user = ".$object->id." OR f.fk_user_pcr = ".$object->id." OR f.fk_user_rsr = ".$object->id." OR f.fk_user_raf = ".$object->id;
    $sql .= " ORDER BY f.date_debut DESC";

    $resql = $db->query($sql);
    if ($resql) {
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th class="">'.$langs->trans("FOD").'</th>';
        print '<th class="">'.$langs->trans("Projet").'</th>';
        print '<th class="">'.$langs->trans("Date Début").'</th>';
        print '<th class="">'.$langs->trans("Date Fin").'</th>';
        print '<th class="right">'.$langs->trans("Statut").'</th>';
        print '</tr>';

        $num = $db->num_rows($resql);

        if ($num) {
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($resql);

                $projectstatic->id = $obj->rowid;
                $projectstatic->ref = $obj->ref;
                $projectstatic->title = $obj->title;

                $fod->id = $obj->fod_id;
                $fod->ref = $obj->ref_fod;
                $fod->date_debut = $obj->date_debut;
                $fod->date_fin = $obj->date_fin;
                $fod->indice = $obj->indice;
                $fod->client_site = $obj->client_site;
                $fod->status = $obj->status;

                print '<tr class="nocellnopadd">';
                print '<td class="nobordernopadding nowrap">';
                print $fod->getNomUrl(1);
                print '</td>';

                print '<td class="nobordernopadding nowrap">';
                print $projectstatic->getNomUrl(1);
                print '</td>';

                print '<td class="nowrap">';
                print dol_print_date($fod->date_debut, '%d/%m/%Y');
                print '</td>';

                print '<td class="nowrap">';
                print dol_print_date($fod->date_fin, '%d/%m/%Y');
                print '</td>';            

                print '<td class="right">'.$fod->LibStatut($fod->status, 2).'</td>';
                print '</tr>';
                $i++;
            }
        } else {
            print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
        }
        print "</table></div>";
    } else {
        dol_print_error($db);
    }

    print '</div>';

    // Page end
	print dol_get_fiche_end();

	llxFooter();
	$db->close();
}
else {
	$urltogo = dol_buildpath('/user/card.php?id='.$id, 1);
	header("Location: ".$urltogo);
	exit;
}