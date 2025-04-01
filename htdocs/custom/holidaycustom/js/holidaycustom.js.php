<?php
/* Copyright (C) 2025 METZGER Leny <l.metzger@optim-industries.fr>
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
// if (!defined('NOREQUIREDB')) {
// 	define('NOREQUIREDB', '1');
// }
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
 * \file    holidaycustom/js/holidaycustom.js.php
 * \ingroup holidaycustom
 * \brief   JavaScript file for module Holidaycustom.
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

require_once DOL_DOCUMENT_ROOT.'/custom/holidaycustom/class/holiday.class.php';

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}


$holiday = new Holiday($db);
$holidayTypeNeedHour = $holiday->holidayTypesInHour();

?>

function afficherNomClient(nom) {
    if ($('#options_client_informe[value="1"]').is(':checked')) {
        if ($('tr[id*=nom_client]').length === 0) {
            let arg = "<tr id=\"nom_client\" class=\"valuefieldcreate holidaycustom_extras_nom_client trextrafields_collapse\" data-element=\"extrafield\" data-targetelement=\"holidaycustom\" data-targetid=\"\">";
            arg = arg + "<td class=\"wordbreak fieldrequired\">Nom du client</td>";
            arg = arg + "<td class=\"holidaycustom_extras_nom_client\">";
            arg = arg + "<input type=\"text\" class=\"flat minwidth100 maxwidthonsmartphone\" name=\"options_nom_client\" id=\"options_nom_client\" value=\"";
            if (nom !== null && nom !== '' && nom !== undefined) {
                arg = arg + nom;
            }
            arg = arg + "\"></td></tr>";
            $('tr.holidaycustom_extras_client_informe ').after(arg)
        }
    }
    else {
        $('tr[id*=nom_client]').remove();
    }
}

function checkHourField() {
	if (<?php echo '["'.implode('","', $holidayTypeNeedHour).'"]' ?>.includes($('select#type').val()) && $('input#date_debut_day').val() && $('input#date_debut_month').val() && $('input#date_debut_year').val()
    && parseInt($('input#date_debut_day').val(), 10) == parseInt($('input#date_fin_day').val(), 10) && 
    parseInt($('input#date_debut_month').val(), 10) == parseInt($('input#date_fin_month').val(), 10) && 
    parseInt($('input#date_debut_year').val(), 10) == parseInt($('input#date_fin_year').val(), 10)) {
        $('tr.holidaycustom_extras_hour').show();
        $('select#select_hourhour').prop("disabled", false);
        $('select#select_hourmin').prop("disabled", false);
    } else {
        $('tr.holidaycustom_extras_hour').hide();
        $('select#select_hourhour').prop("disabled", true);
        $('select#select_hourmin').prop("disabled", true);
    }
}

function FillDateFin() {
    let dateDebut = $("#date_debut_").val(); // Récupère la date sous format "dd/MM/yyyy"

        if (dateDebut && $("#date_fin_").val().trim() === "") {
            $("#date_fin_").val(dateDebut); // Remplit le champ principal

            let parts = dateDebut.split("/"); // Sépare en [jour, mois, année]
            if (parts.length === 3) {
                $("#date_fin_day").val(parts[0]);   // Jour
                $("#date_fin_month").val(parts[1]); // Mois
                $("#date_fin_year").val(parts[2]);  // Année
            }
        }
}

$(document).ready(function () {
    checkHourField();

    $('select#type').change(checkHourField);
    // const inputs_date = document.querySelectorAll("#date_debut_day, #date_fin_day, #date_debut_month, #date_fin_month, #date_debut_year, #date_fin_year");
    const inputs_date = document.querySelectorAll("#date_debut_day, #date_fin_day");
    inputs_date.forEach((input) => {
        const observer = new MutationObserver(() => {
            checkHourField();
            if(input.id == 'date_debut_day') {
                FillDateFin();
            }
        });
        observer.observe(input, { attributes: true, attributeFilter: ["value"] });
    });
});



