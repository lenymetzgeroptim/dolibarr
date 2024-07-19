<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       communication/communicationindex.php
 *	\ingroup    communication
 *	\brief      Home page of communication top menu
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
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("communication@communication"));

$action = GETPOST('action', 'aZ09');

$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('communication')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('communication', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'communication', 0, 'communication_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}


/*
 * Actions
 */

if(!$user->rights->communication->COMMUNICATION_MESSAGE_OF_THE_WEEK->write) {
	$message = dolibarr_get_const($db, 'COMMUNICATION_MESSAGE_OF_THE_WEEK');

	if(!empty($message)) {
		$viewMessage = dolibarr_get_const($db, 'COMMUNICATION_MESSAGE_OF_THE_WEEK_VIEW');
		dolibarr_set_const($db, 'COMMUNICATION_MESSAGE_OF_THE_WEEK_VIEW', (!empty((int)$viewMessage) ? (int)$viewMessage + 1 : 1), 'int', 1, 'View on message of the days/week/month');
	}
}



/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("CommunicationArea"));

//print load_fiche_titre($langs->trans("CommunicationArea"), '', 'communication.png@communication');
print load_fiche_titre(("Accueil Module Communication"), '', 'communication_noir.png@communication');

//print 'Module communication<br/>';
//print 'TODO : télécharger l\'image du logo mais en noir et en 32 px pour la mettre sur cette page (image en blanc actuellement)<br/>';

print '</br>';


require_once DOL_DOCUMENT_ROOT.'/custom/communication/core/modules/modCommunication.class.php';

//CSS for the logos :
print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/communication/css/logo.css">';
print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/communication/css/message_of_the_week.css">';
print '<div class = "logoReseaux">';
print '<a href = "https://www.youtube.com/@OPTIMIndustries" target = "_blank">'.img_picto('','youtube.png@communication', 'class = "logo"').'</a>';
print '<a href = "http://www.instagram.com/optimindustries/" target = "_blank">'.img_picto('','instagram.png@communication', 'class = "logo"').'</a>';
print '<a href = "http://www.facebook.com/optimindustries" target = "_blank">'.img_picto('','facebook.png@communication', 'class = "logo"').'</a>';
print '<a href = "http://www.linkedin.com/company/optim-industries/" target = "_blank">'.img_picto('','linkedin.png@communication', 'class = "logo"').'</a>';
print '</div>';

$messageToPrint = dolibarr_get_const($db, 'COMMUNICATION_MESSAGE_OF_THE_WEEK');
$viewMessage = dolibarr_get_const($db, 'COMMUNICATION_MESSAGE_OF_THE_WEEK_VIEW');
echo '<div class = "messageOfTheWeek">';
echo '<h3>Message de la semaine !';
if($user->rights->communication->COMMUNICATION_MESSAGE_OF_THE_WEEK->write) {
	print '<span style="color: black; font-size: 15px;">  (Vues : '.(empty($viewMessage) ? 0 : $viewMessage).')</span>';
}
print '</h3>';
echo $messageToPrint;
echo '</div>';

//Ajout liste 10 dernières infos :
require_once DOL_DOCUMENT_ROOT.'/custom/communication/class/information.class.php';

$info = new Information($db);
$info->get_latest_information();
$info->print_latest_information();

//Ajout liste 10 derniers posts :
//print '</div><div class="fichehalfright">';
require_once DOL_DOCUMENT_ROOT.'/custom/communication/class/post.class.php';

$post = new Post($db);
$post->get_latest_post();
$post->print_latest_post();


// End of page
llxFooter();
$db->close();
