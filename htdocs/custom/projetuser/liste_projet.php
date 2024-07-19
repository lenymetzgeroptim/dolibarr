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
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("projetuser@projetuser"));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$id = GETPOST('id', 'integer');

if (empty($conf->projetuser->enabled)) accessforbidden();



/*
 * View
 */

if ($user->rights->user->user->lire || $id == $user->id){
	$form = new Form($db);
	$object = New User($db);
    $companystatic = new Societe($db);
    $projectstatic = new Project($db);
    $formfile = new FormFile($db);

	$help_url = '';
	$page_name = "Liste Projet";

	llxHeader('', $page_name, $help_url);

	$res = $object->fetch($id, '', '', 1);
	if ($res < 0) {
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

	print dol_get_fiche_head($head, 'projets', $title, -1, 'user');

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="fichecenter"><div class="underbanner clearboth"></div><br>';

    $liste_projet = $projectstatic->getProjectsAuthorizedForUser($object, 1); 
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th colspan="6">'.$langs->trans("Liste des projets de l'utilisateur").'</th>';
    print '</tr>';

    if (sizeof($liste_projet) > 0) {
        foreach($liste_projet as $projet) {
            $projectstatic->fetch('', $projet);
            $companystatic->fetch($projectstatic->socid);

            print '<tr class="nocellnopadd">';
            print '<td class="nobordernopadding nowrap">';
            print $projectstatic->getNomUrl(1);
            print '</td>';

            print '<td class="nowrap">';
            print $projectstatic->title;
            print '</td>';

            print '<td class="nowrap">';
            print dol_print_date($projectstatic->date_start, '%d/%m/%Y');
            print '</td>';

            print '<td class="nowrap">';
            print dol_print_date($projectstatic->date_end, '%d/%m/%Y');
            print '</td>';

            print '<td class="nowrap">';
            if ($companystatic->id > 0) {
                print $companystatic->getNomUrl(1, 'company', 16);
            }
            print '</td>';

            //print '<td>'.dol_print_date($db->jdate($obj->datem), 'day').'</td>';

            print '<td class="right">'.$projectstatic->LibStatut($projectstatic->status, 3).'</td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
    }
    print "</table></div>";

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