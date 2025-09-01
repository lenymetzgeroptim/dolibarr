<?php
/* Copyright (C) 2025 FAURE Louis <l.faure@optim-industries.fr>
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

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    constat/js/constat.js.php
 * \ingroup constat
 * \brief   JavaScript file for module Constat.
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
	$i--;
	$j--;
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

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

/* Javascript library of module Constat */

/* Code JS pour afficher une popup qui disparaît en haut de l'écran */

// Fonction pour afficher la popup
// function showPopup(message) {
//     var popup = document.createElement('div');
//     popup.className = 'popup-message';
//     popup.innerHTML = message;
    
//     // Ajouter la popup au corps de la page
//     document.body.appendChild(popup);
    
//     // Animer la popup pour la faire apparaître en haut
//     setTimeout(function() {
//         popup.style.top = '0px';
//     }, 100);

//     // Après 3 secondes, faire disparaître la popup
//     setTimeout(function() {
//         popup.style.top = '-50px'; // Décalage pour faire disparaître la popup
//         setTimeout(function() {
//             popup.remove(); // Retirer la popup du DOM après l'animation
//         }, 500); // Délai pour que l'animation de disparition soit terminée
//     }, 3000); // La popup disparaît après 3 secondes
// }

// // Exemple d'utilisation de la popup
// window.onload = function() {
//     showPopup('Ceci est un message temporaire !');
// };

// // CSS pour la popup (assurez-vous que ce style soit inclus dans ton fichier HTML ou dans une feuille de style)
// var style = document.createElement('style');
// style.innerHTML = `
//     .popup-message {
//         position: fixed;
//         top: -50px; /* Position initiale en dehors de l'écran */
//         left: 50%;
//         transform: translateX(-50%);
//         background-color: #4CAF50;
//         color: white;
//         padding: 10px;
//         border-radius: 5px;
//         font-size: 16px;
//         box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
//         transition: top 0.5s ease-in-out;
//         z-index: 9999;
//     }
// `;
// document.head.appendChild(style);

$(document).ready(function () {
    const mapping = {
        'actionsimmediates': [/*'field_actionsimmediates_commentaire', */'field_actionsimmediates_date', 'field_actionsimmediates_par'],
        'infoclient': [/*'field_infoclient_commentaire', */'field_infoclient_date', 'field_infoclient_par'],
        'accordclient': [/*'field_accordclient_commentaire', */'field_accordclient_date', 'field_accordclient_par'],
        'controleclient': [/*'field_controleclient_commentaire',*/ 'field_controleclient_date', 'field_controleclient_par'],
    };

    // Initialisation + écoute des événements
    if (!$('body').hasClass('view')) {
        $.each(mapping, function(checkboxId, targetClasses) {
            toggleRows(checkboxId, targetClasses);
            $('#' + checkboxId).on('change', function () {
                toggleRows(checkboxId, targetClasses);
            });
        });
    }
});

function toggleRows(selectId, targetClasses) {
    const select = $('#' + selectId);
    const selectedValue = select.val();

    // On affiche uniquement si la valeur est "1"
    const shouldShow = selectedValue === '1';

    targetClasses.forEach(function(className) {
        const row = $('.' + className);
        if (shouldShow) {
            row.show();
        } else {
            row.hide();
        }
    });
}