<?php
/* Copyright (C) 2023 METZGER Leny <l.metzger@optim-industries.fr>
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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    communication/js/communication.js.php
 * \ingroup communication
 * \brief   JavaScript file for module Communication.
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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
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

global $user, $db;

require_once DOL_DOCUMENT_ROOT.'/custom/communication/class/notification.class.php';

// Define js type
header('Content-Type: application/javascript');
header('Cache-Control: no-cache');

?>

$(document).ready(function() {
	var notification = <?php 
		$notif = new Notification($db);

		//Les deux variables suivantes retournent false si la valeur en base est 0, true sinon
		$notifPost = $notif->checkActivatedPost($user->id);
		$notifInfo = $notif->checkActivatedInfo($user->id);

		print ($notifPost || $notifInfo ? 1 : 0);
	?>

	var element = document.querySelector('.mainmenu.communication.topmenuimage')
	if(element !== null && notification == 1) {
		element.classList.remove('communication');
		element.classList.add('communication_notification');
	}

	var element = document.querySelector('.mainmenu.communication_notification.topmenuimage')
	if(element !== null && notification == 0) {
		element.classList.remove('communication_notification');
		element.classList.add('communication');
	}
})


//Générer une chaîne de requête unique basée sur le hachage du fichier
function generateUniqueQueryString() {
	var timestamp = new Date().getTime(); // Obtient l'horodatage actuel
	var hash = timestamp.toString(); // Utilisez un algorithme de hachage pour générer une valeur unique (par exemple, MD5, SHA-1, etc.)
	return 'v=' + hash;
}

// Mettre à jour l'URL du script avec une chaîne de requête unique
function updateScriptURL() {
	var scriptElement = document.querySelector('script[src="/erp/custom/communication/js/communication.js.php?lang=fr_FR"]');
	var queryString = generateUniqueQueryString();
	var newScriptURL = '/erp/custom/communication/js/communication.js.php?lang=fr_FR&' + queryString;
	scriptElement.setAttribute('src', newScriptURL);
}

// Appeler la fonction pour mettre à jour l'URL du script
updateScriptURL();


