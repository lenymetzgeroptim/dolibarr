<?php
/* Copyright (C) 2007-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Regis Houssin		<regis.houssin@inodbox.com>
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
 *		File that defines the balance of paid holiday of users.
 *
 *   	\file       htdocs/holiday/define_holiday.php
 *		\ingroup    holiday
 *		\brief      File that defines the balance of paid holiday of users.
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadlangs(array('users', 'other', 'holiday', 'hrm', "holidaycustom@holidaycustom"));

$action = GETPOST('action', 'aZ09');

// If the user does not have perm to read the page
if (empty($user->rights->holidaycustom->import)) {
	accessforbidden();
}

$holiday = new Holiday($db);

if (empty($conf->holidaycustom->enabled)) {
	llxHeader('', $langs->trans('CPTitreMenu'));
	print '<div class="tabBar">';
	print '<span style="color: #FF0000;">'.$langs->trans('NotActiveModCP').'</span>';
	print '</div>';
	llxFooter();
	exit();
}



/*
 * Actions
 */
if(GETPOST('sendit', 'alpha') && !empty($user->rights->holidaycustom->import) && !empty(($_FILES['userfile']['tmp_name'][0])) && 
(str_contains($_FILES['userfile']['name'], '.xlsx') || str_contains($_FILES['userfile']['name'], '.XLSX'))) {
	$res = $holiday->import_conges($_FILES['userfile']['tmp_name']);

	if($res) {
		setEventMessages("Erreur lors de l'import", $holiday->errors, 'errors');
	}
	else {
		setEventMessages("Import réalisé avec succés", null);
	}
}
elseif(!str_contains($_FILES['userfile']['name'], '.xlsx') && !str_contains($_FILES['userfile']['name'], '.XLSX')) {
	setEventMessages("Le fichier doit être au format xlsx", null, 'errors');
}


/*
 * View
 */

$formfile = new FormFile($db);

$title = $langs->trans('CPImport');

llxHeader('', $title);
print load_fiche_titre($title, '', 'title_hrm.png');

$langs_tmp = $langs;
$langs->tab_translate['Upload'] = 'Importer';
print '<div style="text-align: center">';
// Show upload form (document and links)
$formfile->form_attach_new_file(
	$_SERVER["PHP_SELF"],
	'none',
	0,
	0,
	$user->rights->holidaycustom->import,
	$conf->browser->layout == 'phone' ? 40 : 60,
	$holiday,
	'',
	1,
	'', 
	0, 
	'', 
	'.xlsx', 
	'', 
	0, 
	0, 
	1
);
$langs = $langs_tmp;
print '</div>';

print '<div id="msg_error">';
print '<h2>Messages / Erreurs</h2>';
print '<div id="content">';
if(!empty($holiday->output)) {
	print $holiday->output;
}
else {
	print "Rien n'a été exécuté";
}
print '</div>';
print '</div>';

// End of page
llxFooter();
$db->close();
