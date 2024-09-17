<?php
/* Copyright (C) 2024 METZGER Leny <l.metzger@optim-industries.fr>
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
 * \file    formationhabilitation/js/formationhabilitation.js.php
 * \ingroup formationhabilitation
 * \brief   JavaScript file for module FormationHabilitation.
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

$(document).ready(function() {
	// Sélectionne tous les inputs dans la table avec l'ID 'tablelinesaddline'
	$("#tablelinesaddline input, #tablelines tr.tredited input").each(function() {
		// Vérifie si l'input n'a pas l'attribut 'form'
		if (!$(this).attr("form")) {
			// Ajoute l'attribut 'form' avec la valeur 'addline'
			$(this).attr("form", "addline");
		}
	});

	// Sauvegarde et exécution au chargement de la page
	if ($("#visitemedicaleform").length) {
		var initialNatureInput = $('#naturevisite').html();	
		var initialUserId = $('#visitemedicaleform #fk_user').val();	
		loadDoctorAndNature(initialUserId, initialNatureInput);
	}
	else if ($("#tablelinesaddline").length) {
		var initialFormationId = $('#tablelinesaddline #fk_formation').val();	
		var initialOrganismeId = $('#tablelinesaddline #fk_societe').val();	
		loadOrganismeAndDuration(initialFormationId, initialOrganismeId, 1);
	}
	else if ($("#convocationform").length) {
		var initialNatureConvoc = $('#convocationform #nature').val();	
		hideConvocationInput(initialNatureConvoc);
		var initialNatureInput = $('#naturevisite').html();	

		if(initialNatureConvoc == 2) {
			var initialUserId = $('#convocationform #fk_user').val();	
			loadDoctorAndNature(initialUserId, initialNatureInput);
		}
	}

	$('#tablelinesaddline #fk_formation').change(function() {
		var formationId = $(this).val();
		loadOrganismeAndDuration(formationId);
	});

	$('#visitemedicaleform #fk_user').change(function() {
		var userId = $(this).val();
		loadDoctorAndNature(userId, initialNatureInput);
	});

	$('#convocationform #fk_user').change(function() {
		var userId = $(this).val();
		loadDoctorAndNature(userId, initialNatureInput);
	});

	$('#convocationform #nature').change(function() {
		var NatureConvoc = $('#convocationform #nature').val();	
		hideConvocationInput(NatureConvoc);
	});

	$('#tablelinesaddline #interne_externe').change(function() {
		if($('#tablelinesaddline #interne_externe').val() == 2) {
			$('#tablelinesaddline #formateur').parent().css('display', '');
			$('#tablelinesaddline #fk_societe').parent().css('display', 'none');
		}
		else {
			$('#tablelinesaddline #fk_societe').parent().css('display', '');
			$('#tablelinesaddline #formateur').parent().css('display', 'none');
		}
	});
});

function loadOrganismeAndDuration(formationId, organismeId, firstLoad) {
	if (formationId > 0) {
		$.ajax({
			type: 'POST',
			url: '/custom/formationhabilitation/ajax/getSocieteByFormation.php',
			data: { formationId: formationId, organismeId: organismeId },
			success: function(response) {
				var data = JSON.parse(response);
                $('#fk_societe').html(data.fk_societe);
				if(!firstLoad) {
					$('input[name="nombre_heurehour"]').val(data.hour);
					$('input[name="nombre_heuremin"]').val(data.min);
				}
			}
		});
	} else {
		$('#fk_societe').html('<option value="">Sélectionnez une formation</option>');
		if(!firstLoad) {
			$('input[name="nombre_heurehour"]').val('');
			$('input[name="nombre_heuremin"]').val('');
		}
	}
}

function loadDoctorAndNature(userId, initialNatureInput) {
	if (userId > 0) {
		$.ajax({
			type: 'POST',
			url: '/custom/formationhabilitation/ajax/getDoctorByUser.php',
			data: { userId: userId },
			success: function(response) {
				var data = JSON.parse(response);

				if(data.fk_contact) {
                	$('#fk_contact').val(data.fk_contact).trigger('change');
				}
				else {
					$('#fk_contact').val('').trigger('change');
				}

				if(data.naturevisite) {
					listNatures = data.naturevisite.split(',')

					$('#naturevisite option').each(function() {
						var optionValue = $(this).val();
						
						if (!listNatures.includes(optionValue)) {
							$(this).remove();
						}
					});

					$('#naturevisite').trigger('change');

					if(listNatures.length == 1) {
						$('#naturevisite').val(listNatures[0]).trigger('change');
					}
				}
				else {
					$('#naturevisite').html(initialNatureInput);
					$('#naturevisite').val('').trigger('change');
				}
			}
		});
	} else {
		$('#fk_contact').val('').trigger('change');
		$('#naturevisite').html(initialNatureInput);
		$('#naturevisite').val('').trigger('change');
	}
}

function hideConvocationInput(NatureConvoc) {
	if(NatureConvoc == 1) {
		$('#convocationform .field_type').css('display', '');
		$('#convocationform .field_fk_societe').css('display', '');
		$('#convocationform .field_fk_formation').css('display', '');
		$('#convocationform .field_fk_contact').css('display', 'none');
		$('#convocationform .field_naturevisite').css('display', 'none');
		$('#convocationform .field_centremedecine').css('display', 'none');
		$('#convocationform .field_examenrealiser').css('display', 'none');

		$('#convocationform .field_type #type').prop('disabled', false);
		$('#convocationform .field_fk_societe #fk_societe').prop('disabled', false);
		$('#convocationform .field_fk_formation #fk_formation').prop('disabled', false);
		$('#convocationform .field_fk_contact #fk_contact').prop('disabled', true);
		$('#convocationform .field_naturevisite #naturevisite').prop('disabled', true);
		$('#convocationform .field_centremedecine #centremedecine').prop('disabled', true);
		$('#convocationform .field_examenrealiser #examenrealiser').prop('disabled', true);
	}
	else if(NatureConvoc == 2) {
		$('#convocationform .field_type').css('display', 'none');
		$('#convocationform .field_fk_societe').css('display', 'none');
		$('#convocationform .field_fk_formation').css('display', 'none');
		$('#convocationform .field_fk_contact').css('display', '');
		$('#convocationform .field_naturevisite').css('display', '');
		$('#convocationform .field_centremedecine').css('display', 'none');
		$('#convocationform .field_examenrealiser').css('display', 'none');

		$('#convocationform .field_type #type').prop('disabled', true);
		$('#convocationform .field_fk_societe #fk_societe').prop('disabled', true);
		$('#convocationform .field_fk_formation #fk_formation').prop('disabled', true);
		$('#convocationform .field_fk_contact #fk_contact').prop('disabled', false);
		$('#convocationform .field_naturevisite #naturevisite').prop('disabled', false);
		$('#convocationform .field_centremedecine #centremedecine').prop('disabled', true);
		$('#convocationform .field_examenrealiser #examenrealiser').prop('disabled', true);
	}
	else if(NatureConvoc == 3) {
		$('#convocationform .field_type').css('display', 'none');
		$('#convocationform .field_fk_societe').css('display', 'none');
		$('#convocationform .field_fk_formation').css('display', 'none');
		$('#convocationform .field_fk_contact').css('display', 'none');
		$('#convocationform .field_naturevisite').css('display', 'none');
		$('#convocationform .field_centremedecine').css('display', '');
		$('#convocationform .field_examenrealiser').css('display', '');

		$('#convocationform .field_type #type').prop('disabled', true);
		$('#convocationform .field_fk_societe #fk_societe').prop('disabled', true);
		$('#convocationform .field_fk_formation #fk_formation').prop('disabled', true);
		$('#convocationform .field_fk_contact #fk_contact').prop('disabled', true);
		$('#convocationform .field_naturevisite #naturevisite').prop('disabled', true);
		$('#convocationform .field_centremedecine #centremedecine').prop('disabled', false);
		$('#convocationform .field_examenrealiser #examenrealiser').prop('disabled', false);
	}
	else if(NatureConvoc == 4) {
		$('#convocationform .field_type').css('display', 'none');
		$('#convocationform .field_fk_societe').css('display', 'none');
		$('#convocationform .field_fk_formation').css('display', 'none');
		$('#convocationform .field_fk_contact').css('display', 'none');
		$('#convocationform .field_naturevisite').css('display', 'none');
		$('#convocationform .field_centremedecine').css('display', '');
		$('#convocationform .field_examenrealiser').css('display', '');

		$('#convocationform .field_type #type').prop('disabled', true);
		$('#convocationform .field_fk_societe #fk_societe').prop('disabled', true);
		$('#convocationform .field_fk_formation #fk_formation').prop('disabled', true);
		$('#convocationform .field_fk_contact #fk_contact').prop('disabled', true);
		$('#convocationform .field_naturevisite #naturevisite').prop('disabled', true);
		$('#convocationform .field_centremedecine #centremedecine').prop('disabled', false);
		$('#convocationform .field_examenrealiser #examenrealiser').prop('disabled', false);
	}
	else {
		$('#convocationform .field_type').css('display', 'none');
		$('#convocationform .field_fk_societe').css('display', 'none');
		$('#convocationform .field_fk_formation').css('display', 'none');
		$('#convocationform .field_fk_contact').css('display', 'none');
		$('#convocationform .field_naturevisite').css('display', 'none');
		$('#convocationform .field_centremedecine').css('display', 'none');
		$('#convocationform .field_examenrealiser').css('display', 'none');

		$('#convocationform .field_type #type').prop('disabled', true);
		$('#convocationform .field_fk_societe #fk_societe').prop('disabled', true);
		$('#convocationform .field_fk_formation #fk_formation').prop('disabled', true);
		$('#convocationform .field_fk_contact #fk_contact').prop('disabled', true);
		$('#convocationform .field_naturevisite #naturevisite').prop('disabled', true);
		$('#convocationform .field_centremedecine #centremedecine').prop('disabled', true);
		$('#convocationform .field_examenrealiser #examenrealiser').prop('disabled', true);
	}
}
