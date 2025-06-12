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
 * \file    feuilledetemps/js/feuilledetemps.js.php
 * \ingroup feuilledetemps
 * \brief   JavaScript file for module FeuilleDeTemps.
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


function autoFillSite(sitedefaut, day, cpt){
    heure = $('input[name="task[' + day + '][' + cpt + ']"').val();
    heure_nuit = $('input[name="heure_nuit[' + day + '][' + cpt + ']"').val();
    if(sitedefaut && ((heure && heure != '0.00' && heure != '00.00' && heure != '0:00' && heure != '00:00') || (heure_nuit && heure_nuit != '0.00' && heure_nuit != '00.00' && heure_nuit != '0:00' && heure_nuit != '00:00')) && $('input[name="site[' + day + '][' + cpt + ']"') && $('input[name="site[' + day + '][' + cpt + ']"').val() == "") {
        $('input[name="site[' + day + '][' + cpt + ']"').val(sitedefaut);
    }
    else if(sitedefaut && sitedefaut == $('input[name="site[' + day + '][' + cpt + ']"').val() && ((!heure || heure == '0.00' || heure == '00.00' || heure == '0:00' || heure == '00:00') && (!heure_nuit || heure_nuit == '0.00' || heure_nuit == '00.00' || heure_nuit == '0:00' || heure_nuit == '00:00'))) {
        $('input[name="site[' + day + '][' + cpt + ']"').val("");
    }
}

function forceUppercase(input) {
    input.value = input.value.toUpperCase().replace(/[^A-Z\s]/g, '');
}

function addTimespentLine(button, idw, cpt) {
    document.querySelectorAll(`.line_${idw}_${cpt + 1}`).forEach(el => {
        el.classList.remove('displaynone');

        button.classList.add('visibilityhidden');
        const span = el.querySelector(`span.fa-plus.visibilityhidden`);
        if (span && cpt + 2 < FDT_COLUMN_MAX_TASK_DAY) {
            span.classList.remove('visibilityhidden');
        }
    });  

    // const parent = button.parentNode;
    // const clonedParent = parent.cloneNode(true);

    // const oldIdImgNote = clonedParent.querySelector('[id^="img_note_"]');
    // if (oldIdImgNote) {
    //     const newId = `img_note_${cpt + 1}_${idw}`;
    //     oldIdImgNote.id = newId;

    //     const oldOnClick = oldIdImgNote.getAttribute('onClick');
    //     if (oldOnClick) {
    //         const newOnClick = `openNote('note_${cpt + 1}_${idw}')`;
    //         oldIdImgNote.setAttribute('onClick', newOnClick);
    //     }
    // }

    // const oldIdNote = clonedParent.querySelector('[id^="note_"]');
    // if (oldIdNote) {
    //     const newId = `note_${cpt + 1}_${idw}`;
    //     oldIdNote.id = newId;
    // }

    // const textareaElement = clonedParent.querySelector(`textarea[name="note[${idw}][${cpt}]"]`);
    // if (textareaElement) {
    //     // Mettre à jour le name et vider son contenu
    //     textareaElement.name = `note[${idw}][${cpt + 1}]`;
    //     textareaElement.value = '';
    // }

    // const oldIdTimespent = clonedParent.querySelector('[id^="timeadded["]');
    // if (oldIdTimespent) {
    //     oldIdTimespent.id = `timeadded[${idw}][${cpt + 1}]`;
    //     oldIdTimespent.value = '';
    //     oldIdTimespent.name = `task[${idw}][${cpt + 1}]`;
    // }

    // const spanElement = parent.querySelector('span.fas.fa-plus[onclick]');
    // const spanElementCloned = clonedParent.querySelector('span.fas.fa-plus[onclick]');
    // if (spanElement) {
    //     spanElement.style.visibility = 'hidden';
    // }
    // if(cpt + 2 >= FDT_COLUMN_MAX_TASK_DAY) {
    //     if (spanElementCloned) {
    //         spanElementCloned.style.visibility = 'hidden';
    //     }
    // }
    // else {
    //     const oldOnClickSpan = spanElementCloned.getAttribute('onClick');
    //     if (oldOnClickSpan) {
    //         const newOnClickSpan = `addTimespentLine(this, ${idw}, ${cpt + 1})`;
    //         spanElementCloned.setAttribute('onClick', newOnClickSpan);
    //     }
    // }

    // const heureNuit = document.querySelector(`input[id^="time_heure_nuit[${idw}][${cpt}]"]`);
    // const parentheureNuit = heureNuit.parentNode;
    // const clonedheureNuitParent = parentheureNuit.cloneNode(true);

    // const oldIdHeureNuit = clonedheureNuitParent.querySelector('[id^="time_heure_nuit["]');
    // if (oldIdHeureNuit) {
    //     oldIdHeureNuit.id = `time_heure_nuit[${idw}][${cpt + 1}]`;
    //     oldIdHeureNuit.value = '';
    //     oldIdHeureNuit.name = `heure_nuit[${idw}][${cpt + 1}]`;

    //     const newClass = `time_heure_nuit_${cpt + 1}_${idw}`;
    //     oldIdHeureNuit.className = oldIdHeureNuit.className.replace(
    //         new RegExp(`time_heure_nuit_${cpt}_${idw}`),
    //         newClass
    //     );
    // }

    // const site = document.querySelector(`input[id^="site[${idw}][${cpt}]"]`);
    // const parentsite = site.parentNode;
    // const clonedsiteParent = parentsite.cloneNode(true);

    // const oldIdSite = clonedsiteParent.querySelector('[id^="site["]');
    // if (oldIdSite) {
    //     oldIdSite.id = `site[${idw}][${cpt + 1}]`;
    //     oldIdSite.value = '';
    //     oldIdSite.name = `site[${idw}][${cpt + 1}]`;
    // }

    // const selectTask = document.querySelector(`select[id^="fk_task_${idw}_${cpt}"]`);
    // const parentselectTask = selectTask.parentNode;
    // const clonedselectTaskParent = parentselectTask.cloneNode(true);
    
    // const oldIdAffaire = clonedselectTaskParent.querySelector('select[name^="fk_task["]');
    // if (oldIdAffaire) {
    //     oldIdAffaire.id = `fk_task_${idw}_${cpt + 1}`;
    //     oldIdAffaire.value = '';
    //     oldIdAffaire.name = `fk_task[${idw}][${cpt + 1}]`;
    //     oldIdAffaire.setAttribute('data-select2-id', `fk_task_${idw}_${cpt + 1}`);
    // }

    // // Insère le parent cloné après l'original
    // parent.parentNode.insertBefore(clonedParent, parent.nextSibling);
    // parentselectTask.parentNode.insertBefore(clonedselectTaskParent, parentselectTask.nextSibling);
    // parentheureNuit.parentNode.insertBefore(clonedheureNuitParent, parentheureNuit.nextSibling);
    // parentsite.parentNode.insertBefore(clonedsiteParent, parentsite.nextSibling);
}

function displayFav() {
    if ($('#seeFavoris span').hasClass('fas')) {
        $('#seeFavoris span').removeClass('fas');
        $('#seeFavoris span').addClass('far');

        $('#tablelines_fdt tr.task:not(.favoris)').show();

        // Afficher les tr.projet si au moins une tr.task qui suit est visible
        $('tr.project').each(function () {
            var projetRow = $(this);
            var taskVisible = false;

            // Vérifie toutes les lignes suivantes jusqu'à la prochaine ligne projet
            projetRow.nextAll('tr').each(function () {
                if ($(this).hasClass('project')) {
                    return false; // Arrête la boucle si une autre ligne projet est rencontrée
                }
                if ($(this).hasClass('task') && $(this).is(':visible')) {
                    taskVisible = true; // Trouvé une tâche visible
                    return false; // Arrête la boucle
                }
            });

            if (taskVisible) {
                projetRow.show();
            }
        });

        $('form[name="addtime"]').attr('action', function (i, val) {
            return val.replace(/&?showFav=1/, '');
        });
    }
    else {
        $('#seeFavoris span').removeClass('far');
        $('#seeFavoris span').addClass('fas');

        $('#tablelines_fdt tr.task:not(.favoris)').hide();

        // Masquer les tr.projet si toutes les tr.task qui suivent sont masquées
        $('tr.project').each(function () {
            var projetRow = $(this);
            var allTasksHidden = true;

            // Vérifie toutes les lignes suivantes jusqu'à la prochaine ligne projet
            projetRow.nextAll('tr').each(function () {
                if ($(this).hasClass('project')) {
                    return false; // Arrête la boucle si une autre ligne projet est rencontrée
                }
                if ($(this).hasClass('task') && $(this).is(':visible')) {
                    allTasksHidden = false; // Trouvé une tâche visible
                    return false; // Arrête la boucle
                }
            });

            if (allTasksHidden) {
                projetRow.hide();
            }
        });

        $('form[name="addtime"]').attr('action', function (i, val) {
            return val + '&showFav=1';
        });
    }
}

function mouseOverFav(object) {
    if (!$(object).hasClass('clicked')) {
        $(object).find('span').addClass('fas');
        $(object).find('span').removeClass('far');
    }
}

function mouseOutFav(object) {
    if (!$(object).hasClass('clicked')) {
        $(object).find('span').addClass('far');
        $(object).find('span').removeClass('fas');
    }
}

function clickFav(object) {
    if ($(object).hasClass('clicked')) {
        $(object).removeClass('clicked');
    } else {
        $(object).addClass('clicked');
    }
}

function screenFDT(url, name) {
    const screenshotTarget = document.querySelector('#tablelines_fdt');

    html2canvas(screenshotTarget, {
        scrollX: 0,
        scrollY: 0,
        windowWidth: 6000,
        windowHeight: 2000
    }).then((canvas) => {
        const base64image = canvas.toDataURL("image/png");

        // Pour télécharger l'image
        const link = document.createElement('a');
        link.href = base64image;
        link.download = name + '.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        window.location.href = url;
    });
}

function disableNullInput(columnmode) {
    if(columnmode) {
        $('form[name="addtime"] input[type="text"][id^="timeadded"]:not(:disabled), form[name="addtime"] input[type="text"][id^="time_heure_nuit"]:not(:disabled), form[name="addtime"] input[type="text"][id^="site"]:not(:disabled), form[name="addtime"] input[type="text"]:not(:disabled):not( #search_project_ref):not(#search_thirdparty):not(#search_task_label):not(#re)').each(function (index, obj) {
            if (obj.defaultValue == obj.value /*&& !obj.parentNode.classList.contains('prefilling_time')*/) {
                $(obj).prop('disabled', true);
            }
        });

        $('form[name="addtime"] textarea[name^="note"]:not(:disabled), form[name="addtime"] textarea[name^="options_notedeplacement"]:not(:disabled)').each(function (index, obj) {
            if (obj.defaultValue == obj.value) {
                $(obj).prop('disabled', true);
            }
        });

        $('form[name="addtime"] select:not(:disabled):not(#search_usertoprocessid):not([name^="holiday_type"])').each(function () {
            let initialValue = $(this).find("option[selected]").val(); 
            let currentValue = $(this).val(); 

            if(initialValue == undefined || initialValue == null) initialValue = '0'
            if(currentValue == undefined || currentValue == null) currentValue = '0'
            
            if (initialValue === currentValue) {
                $(this).prop("disabled", true); 
            }
        });

        $('form[name="addtime"] input[type="text"][id^="timeadded"]:disabled').each(function (index, obj) {
            let id = $(obj).attr("id"); 
            let matches = id.match(/timeadded\[(\d+)\]\[(\d+)\]/);

            if (matches) {
                let day = matches[1];
                let cpt = matches[2];

                let fk_task_obj = $("#" + `fk_task_${day}_${cpt}`);
                
                let initialValue = fk_task_obj.find("option[selected]").val(); 
                let currentValue = fk_task_obj.val(); 

                if(initialValue == undefined || initialValue == null) initialValue = '0'
                if(currentValue == undefined || currentValue == null) currentValue = '0'

                if (initialValue !== currentValue) {
                    $(obj).prop('disabled', false);
                }
            }
        });

        $('form[name="addtime"] input[type="text"][id^="time_heure_nuit"]:disabled').each(function (index, obj) {
            let id = $(obj).attr("id"); 
            let matches = id.match(/time_heure_nuit\[(\d+)\]\[(\d+)\]/);

            if (matches) {
                let day = matches[1];
                let cpt = matches[2];

                let fk_task_obj = $("#" + `fk_task_${day}_${cpt}`);
                
                let initialValue = fk_task_obj.find("option[selected]").val(); 
                let currentValue = fk_task_obj.val(); 

                if(initialValue == undefined || initialValue == null) initialValue = '0'
                if(currentValue == undefined || currentValue == null) currentValue = '0'

                if (initialValue !== currentValue) {
                    $(obj).prop('disabled', false);
                }
            }
        });
    }
    else {
        $('form[name="addtime"] input[type="text"][id^="timeadded"], form[name="addtime"] input[type="text"][id^="time_heure_nuit"], form[name="addtime"] input[type="text"][id^="time_epi"]').each(function (index, obj) {
            if (obj.defaultValue == obj.value) {
                $(obj).prop('disabled', true);
            }
        });
    }

    $('form[name="addtime"] textarea[name^="note"]').each(function (index, obj) {
        if (obj.defaultValue == obj.value) {
            $(obj).prop('disabled', true);
        }
    });
}

function toggleCheckboxesHoliday(source) {
    const checkboxes = document.querySelectorAll('input[id^="holiday_valide"]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.disabled) {
            checkbox.checked = source.checked;
        }
    });
}

function deletePrefillingClass(objet, sitedefaut) {
    if($(objet).attr('id') && $(objet).attr('id').includes('timeadded') && objet.value != '' && objet.parentNode.classList.contains('prefilling_time')) {
        objet.parentNode.classList.remove('prefilling_time')
    }
    else if($(objet).attr('id') && $(objet).attr('id').includes('timeadded') && objet.value == '' && !objet.parentNode.classList.contains('prefilling_time')) {
        objet.parentNode.classList.add('prefilling_time')
    }
    else if(objet.value > 0 && $(objet).attr('id') && $(objet).attr('id').includes('fk_task')) {
        const lineClass = $(objet).parent().attr('class').split(' ').find(c => c.includes('line_'));
        var parentTr = $(objet).closest('tr');
        var element = parentTr.find('.'+ lineClass + '.prefilling_time');
        if (element.length) {
            element.removeClass('prefilling_time')
            var input = element.find('input[type="text"]');
            if (input.length) {
                var placeholderValue = input.attr('placeholder'); 
                if (placeholderValue) {
                    input.trigger('focus');  // Simule l'événement onfocus
                    input.val(placeholderValue); 
                    input.trigger('keyup');  // Simule l'événement onfocus
                }
            }

            // var id = $(objet).attr('id');
            // var regex = /^fk_task\_(\d+)\_(\d+)$/; 
            // var match = id.match(regex);
            // if (match) {
            //     var idw = match[1];
            //     var cpt = match[2];
            //     autoFillSite(sitedefaut, idw, cpt);
            // }
        }
    }
}

/* Parse en input data for time entry into timesheet */
function regexEvent_TS(objet, evt, type, negative = 0) {
    switch (type) {
        case 'days':
            var regex = /^[0-9]{1}([.,]{1}[0-9]{1})?$/;

            if (regex.test(objet.value)) {
                var tmp = objet.value.replace(',', '.');
                if (tmp <= 1.5) {
                    var tmpint = parseInt(tmp);
                    if (tmp - tmpint >= 0.5) {
                        objet.value = tmpint + 0.5;
                    } else {
                        objet.value = tmpint;
                    }
                } else {
                    objet.value = '1.5';
                }
            } else {
                objet.value = '0';
            }
            break;
        case 'hours':
            var regex = /^[0-9]{1,2}:[0-9]{2}$/;
            var regex2 = /^[0-9]{1,2}$/;
            var regex3 = /^[0-9]{1}([.,]{1}[0-9]{1,2})?$/;
            if (!regex.test(objet.value)) {
                if (regex2.test(objet.value))
                    objet.value = objet.value + ':00';
                else if (regex3.test(objet.value)) {
                    var tmp = parseFloat(objet.value.replace(',', '.'));
                    var rnd = Math.trunc(tmp);
                    if (60 * (tmp - rnd) >= 10) {
                        objet.value = rnd + ':' + Math.round(60 * (tmp - rnd));
                    }
                    else {
                        objet.value = rnd + ':0' + Math.round(60 * (tmp - rnd));
                    }
                }
                else
                    objet.value = '';
            }
            break;
        case 'hours_decimal':
            var regex = /^[0-9]{1,2}:[0-9]{2}$/;
            var regex2 = /^[0-9]{1,2}$/;
            var regex3 = /^[0-9]{1,2}[.,]{1}[0-9]{2}?$/;
            var regex4 = /^[0-9]{1}([.,]{1}[0-9]{1,3})?$/;
            if (!regex3.test(objet.value)) {
                if (regex2.test(objet.value))
                    objet.value = objet.value + '.00';
                else if (regex.test(objet.value)) {
                    var tmp = parseFloat(objet.value.replace(':', '.'));
                    var rnd = Math.trunc(tmp);

                    objet.value = parseFloat(rnd + (100 * (tmp - rnd) / 60)).toFixed(2);;
                }
                else if (regex4.test(objet.value)) {
                    objet.value = parseFloat(objet.value).toFixed(2);;
                }
                else
                    objet.value = '';
            }
            break;
        case 'timeChar':
            //var regex= /^[0-9:]{1}$/;
            //alert(event.charCode);
            var charCode = (evt.which) ? evt.which : event.keyCode;
            if (((charCode >= 48) && (charCode <= 57)) || //num
                (charCode === 46) || (charCode === 8) ||// comma & periode
                (charCode === 58) || (charCode == 44) || (negative && charCode == 45))// : & all charcode
            {
                // ((charCode>=96) && (charCode<=105)) || //numpad
                return true;

            } else {
                return false;
            }

            break;
        default:
            break;
    }
}

function parseTimeInt(timeStr) {
    const timeParts = timeStr.split(':');
    const hours = parseInt(timeParts[0], 10);
    const minutes = parseInt(timeParts[1], 10);
    return { hours, minutes };
}

function updateAllTotalLoad_TS(mode, nb_jour, num_first_day = 0) {
    /* Output total of all total */
    var total = new Date(0);
    total.setHours(0);
    total.setMinutes(0);
    var nbextradays = 0;

    for (var i = num_first_day; i < nb_jour; i++) {
        var taskTime = new Date(0);
        result = parseTime(jQuery('.totalDay' + i).text(), taskTime);
        if (result >= 0) {
            nbextradays = nbextradays + Math.floor((total.getHours() + taskTime.getHours() + result * 24) / 24);
            total.setHours(total.getHours() + taskTime.getHours());
            total.setMinutes(total.getMinutes() + taskTime.getMinutes());
        }
    }

    if (total.getHours() || total.getMinutes()) jQuery('.totalDayAll').addClass("bold");
    else jQuery('.totalDayAll').removeClass("bold");
    var texttoshow = pad(nbextradays * 24 + total.getHours()) + ':' + pad(total.getMinutes());
    jQuery('.totalDayAll').text(texttoshow);
}

function updateTotalSigedi(object, key, type) {
    total_col = $('#total_' + key);
    total = (parseFloat(total_col.text()) ? parseFloat(total_col.text()) : 0);

    if(type == 'boolean') {
        if(object.checked == true) {
            total += 1;
        }
        else {
            total -= 1;
        }
    }
    else {
        if(object.oldvalue && parseFloat(object.oldvalue) > 0.00 && !object.parentNode.classList.contains('prefilling_time')) total -= parseFloat(object.oldvalue)
        if(object.value && parseFloat(object.value) > 0.00) total += parseFloat(object.value)

        total = parseFloat(total).toFixed(2);

        if(type == 'price') {
            total += " €" 
        }
    }

    total_col.text(total);
}

/* Update total. days = column nb starting from 0 */
// function updateTotalLoad_TS(days, mode, nb_jour, num_first_day = 0) {
//     if (mode == "hours") {
//         var total = new Date(0);
//         total.setHours(0);
//         total.setMinutes(0);
//         var nbline = document.getElementById('numberOfLines').value;
//         var nbextradays = 0;

//         for (var i = -1; i < nbline; i++) {
//             /* get value into timeadded cell */
//             var id = 'timeadded[' + i + '][' + days + ']';
//             var taskTime = new Date(0);
//             var element = document.getElementById(id);
//             if (element) {
//                 /* alert(element.value);*/
//                 if (element.value) {
//                     result = parseTime(element.value, taskTime);
//                 }
//                 else {
//                     result = parseTime(element.innerHTML, taskTime);
//                 }
//                 if (result >= 0) {
//                     nbextradays = nbextradays + Math.floor((total.getHours() + taskTime.getHours() + result * 24) / 24);
//                     total.setHours(total.getHours() + taskTime.getHours());
//                     total.setMinutes(total.getMinutes() + taskTime.getMinutes());
//                 }
//             }
//         }

//         var stringdays = days;

//         /* Output total in top of column */
//         if (total.getHours() || total.getMinutes()) jQuery('.totalDay' + stringdays).addClass("bold");
//         else jQuery('.totalDay' + stringdays).removeClass("bold");
//         var texttoshow = pad(nbextradays * 24 + total.getHours()) + ':' + pad(total.getMinutes());
//         jQuery('.totalDay' + stringdays).text(texttoshow);
//     }
//     else {
//         var total = 0;
//         var nbline = document.getElementById('numberOfLines').value;
//         for (var i = -1; i < nbline; i++) {
//             var id = 'timeadded[' + i + '][' + days + ']';
//             var taskTime = new Date(0);
//             var element = document.getElementById(id);
//             if (element) {
//                 if (element.value) {
//                     total += parseInt(element.value);

//                 }
//                 else {
//                     total += parseInt(element.innerHTML);
//                 }
//             }
//         }

//         var stringdays = days;

//         if (total) jQuery('.totalDay' + stringdays).addClass("bold");
//         else jQuery('.totalDay' + stringdays).removeClass("bold");
//         jQuery('.totalDay' + stringdays).text(total);
//     }
// }

/* Update total. days = column nb starting from 0 */
// totalDay, totalDayAll, total_task
function updateTotal_TS(object, days, mode, num_task, num_first_day = 0, holiday = 0) {
    if (mode == "hours") {
        var total = new Date(0);
        var nbextradays = 0;

        if (object.value != object.oldvalue) {
            var taskTime = new Date(0);
            var oldtaskTime = new Date(0);
            var newtaskTime = new Date(0);

            total.setHours(0);
            total.setMinutes(0);

            result = parseTime(jQuery('.totalDay' + days).text(), taskTime);
            if (result >= 0) {
                total.setHours(total.getHours() + taskTime.getHours());
                total.setMinutes(total.getMinutes() + taskTime.getMinutes());
            }

            result = parseTime(object.oldvalue, oldtaskTime);
            if (result >= 0) {
                total.setHours(total.getHours() - oldtaskTime.getHours());
                total.setMinutes(total.getMinutes() - oldtaskTime.getMinutes());
            }

            result = parseTime(object.value, newtaskTime);
            if (result >= 0) {
                total.setHours(total.getHours() + newtaskTime.getHours());
                total.setMinutes(total.getMinutes() + newtaskTime.getMinutes());
            }

            /* Output total in top of column */
            if (total.getHours() || total.getMinutes()) jQuery('.totalDay' + days).addClass("bold");
            else jQuery('.totalDay' + days).removeClass("bold");
            var texttoshow = pad(total.getHours()) + ':' + pad(total.getMinutes());
            jQuery('.totalDay' + days).text(texttoshow);

            /* Output total of all total */
            if (days >= num_first_day) {
                total.setHours(0);
                total.setMinutes(0);

                result = parseTime(jQuery('.totalDayAll').text(), taskTime);
                if (result >= 0) {
                    nbextradays = nbextradays + Math.floor((total.getHours() + taskTime.getHours() + result * 24) / 24);
                    total.setHours(total.getHours() + taskTime.getHours());
                    total.setMinutes(total.getMinutes() + taskTime.getMinutes());
                }

                result = parseTime(object.oldvalue, oldtaskTime);
                if (result >= 0) {
                    if (total.getHours() - oldtaskTime.getHours() < 0) {
                        nbextradays--;
                    }
                    total.setHours(total.getHours() - oldtaskTime.getHours());
                    total.setMinutes(total.getMinutes() - oldtaskTime.getMinutes());
                }

                result = parseTime(object.value, newtaskTime);
                if (result >= 0) {
                    nbextradays = nbextradays + Math.floor((total.getHours() + newtaskTime.getHours() + result * 24) / 24);
                    total.setHours(total.getHours() + newtaskTime.getHours());
                    total.setMinutes(total.getMinutes() + newtaskTime.getMinutes());
                }

                if (total.getHours() || total.getMinutes()) jQuery('.totalDayAll').addClass("bold");
                else jQuery('.totalDayAll').removeClass("bold");
                var texttoshow = pad(nbextradays * 24 + total.getHours()) + ':' + pad(total.getMinutes());
                jQuery('.totalDayAll').text(texttoshow);
            }

            // Mise à jour du total d'heure de la tache
            if (num_task >= 0 && (days >= num_first_day)) {
                var nbextradays = 0;
                total.setHours(0);
                total.setMinutes(0);

                var total_tache = document.getElementById('total_task[' + num_task + ']').innerText;
                result = parseTime(total_tache, taskTime);
                if (result >= 0) {
                    nbextradays = nbextradays + Math.floor((total.getHours() + taskTime.getHours() + result * 24) / 24);
                    total.setHours(total.getHours() + taskTime.getHours());
                    total.setMinutes(total.getMinutes() + taskTime.getMinutes());
                }

                result = parseTime(object.oldvalue, oldtaskTime);
                if (result >= 0) {
                    if (total.getHours() - oldtaskTime.getHours() < 0) {
                        nbextradays--;
                    }
                    total.setHours(total.getHours() - oldtaskTime.getHours());
                    total.setMinutes(total.getMinutes() - oldtaskTime.getMinutes());
                }

                result = parseTime(object.value, newtaskTime);
                if (result >= 0) {
                    nbextradays = nbextradays + Math.floor((total.getHours() + newtaskTime.getHours() + result * 24) / 24);
                    total.setHours(total.getHours() + newtaskTime.getHours());
                    total.setMinutes(total.getMinutes() + newtaskTime.getMinutes());
                }

                var texttoshow = pad(nbextradays * 24 + total.getHours()) + ':' + pad(total.getMinutes());
                document.getElementById('total_task[' + num_task + ']').innerText = texttoshow;
            }
        }
    }
    else if (mode == "hours_decimal") {
        var total = 0;
        if (object.value != object.oldvalue || object.parentNode.classList.contains('prefilling_time')) {
            total += parseFloat(jQuery('.totalDay' + days).text());
            if(object.oldvalue && parseFloat(parseFloat(object.oldvalue).toFixed(2)) > 0.00 && !object.parentNode.classList.contains('prefilling_time')) total -= parseFloat(parseFloat(object.oldvalue).toFixed(2));
            if(object.value && parseFloat(parseFloat(object.value).toFixed(2)) > 0.00) total += parseFloat(parseFloat(object.value).toFixed(2));

            /* Output total in top of column */
            if (total > 0.00) jQuery('.totalDay' + days).addClass("bold");
            else jQuery('.totalDay' + days).removeClass("bold");
            var texttoshow = parseFloat(total).toFixed(2);
            jQuery('.totalDay' + days).text(texttoshow);

            if($('#diff_' + days)) {
                contrat = parseFloat(jQuery('#contrat_' + days).text());
                diff = total + holiday - contrat;
                var texttoshow = (diff > 0 ? '+' : '') + parseFloat(diff).toFixed(2);
                $('#diff_' + days).text(texttoshow);
                $('#diff_' + days).removeClass('diffpositive');
                $('#diff_' + days).removeClass('diffnegative');
                if(diff > 0) {
                    $('#diff_' + days).addClass('diffpositive');
                }
                else if(diff < 0) {
                    $('#diff_' + days).addClass('diffnegative');
                }
            }

            // /* Output total of all total */
            // if (days >= num_first_day) {
            //     total.setHours(0);
            //     total.setMinutes(0);

            //     result = parseTime(jQuery('.totalDayAll').text(), taskTime);
            //     if (result >= 0) {
            //         nbextradays = nbextradays + Math.floor((total.getHours() + taskTime.getHours() + result * 24) / 24);
            //         total.setHours(total.getHours() + taskTime.getHours());
            //         total.setMinutes(total.getMinutes() + taskTime.getMinutes());
            //     }

            //     result = parseTime(object.oldvalue, oldtaskTime);
            //     if (result >= 0) {
            //         if (total.getHours() - oldtaskTime.getHours() < 0) {
            //             nbextradays--;
            //         }
            //         total.setHours(total.getHours() - oldtaskTime.getHours());
            //         total.setMinutes(total.getMinutes() - oldtaskTime.getMinutes());
            //     }

            //     result = parseTime(object.value, newtaskTime);
            //     if (result >= 0) {
            //         nbextradays = nbextradays + Math.floor((total.getHours() + newtaskTime.getHours() + result * 24) / 24);
            //         total.setHours(total.getHours() + newtaskTime.getHours());
            //         total.setMinutes(total.getMinutes() + newtaskTime.getMinutes());
            //     }

            //     if (total.getHours() || total.getMinutes()) jQuery('.totalDayAll').addClass("bold");
            //     else jQuery('.totalDayAll').removeClass("bold");
            //     var texttoshow = pad(nbextradays * 24 + total.getHours()) + ':' + pad(total.getMinutes());
            //     jQuery('.totalDayAll').text(texttoshow);
            // }

            // // Mise à jour du total d'heure de la tache
            // if (num_task >= 0 && (days >= num_first_day)) {
            //     var nbextradays = 0;
            //     total.setHours(0);
            //     total.setMinutes(0);

            //     var total_tache = document.getElementById('total_task[' + num_task + ']').innerText;
            //     result = parseTime(total_tache, taskTime);
            //     if (result >= 0) {
            //         nbextradays = nbextradays + Math.floor((total.getHours() + taskTime.getHours() + result * 24) / 24);
            //         total.setHours(total.getHours() + taskTime.getHours());
            //         total.setMinutes(total.getMinutes() + taskTime.getMinutes());
            //     }

            //     result = parseTime(object.oldvalue, oldtaskTime);
            //     if (result >= 0) {
            //         if (total.getHours() - oldtaskTime.getHours() < 0) {
            //             nbextradays--;
            //         }
            //         total.setHours(total.getHours() - oldtaskTime.getHours());
            //         total.setMinutes(total.getMinutes() - oldtaskTime.getMinutes());
            //     }

            //     result = parseTime(object.value, newtaskTime);
            //     if (result >= 0) {
            //         nbextradays = nbextradays + Math.floor((total.getHours() + newtaskTime.getHours() + result * 24) / 24);
            //         total.setHours(total.getHours() + newtaskTime.getHours());
            //         total.setMinutes(total.getMinutes() + newtaskTime.getMinutes());
            //     }

            //     var texttoshow = pad(nbextradays * 24 + total.getHours()) + ':' + pad(total.getMinutes());
            //     document.getElementById('total_task[' + num_task + ']').innerText = texttoshow;
            // }
        }
    }
    else {
        var total = 0;
        var nbline = document.getElementById('numberOfLines').value;
        for (var i = -1; i < nbline; i++) {
            var id = 'timeadded[' + i + '][' + days + ']';
            var taskTime = new Date(0);
            var element = document.getElementById(id);
            if (element) {
                if (element.value) {
                    total += parseInt(element.value);

                }
                else {
                    total += parseInt(element.innerHTML);
                }
            }
        }

        var stringdays = days;

        if (total) jQuery('.totalDay' + stringdays).addClass("bold");
        else jQuery('.totalDay' + stringdays).removeClass("bold");
        jQuery('.totalDay' + stringdays).text(total);
    }
}

function updateTotalWeek($mode, temps_prec = 0, temps_suiv = 0, weekNumber, timeHoliday, heure_semaine) {
    const modal = document.getElementsByName("totalSemaine" + weekNumber)[0];
    const [_, premierJour, dernierJour] = modal.id.split('_');

    let totalMinutes = 0;
    let totalHours = 0;
    let totalHourWeek = 0;

    // Prise en compte des heures précédentes ou suivantes
    if (temps_prec) {
        totalMinutes = temps_prec;
        totalHourWeek = temps_prec / 60;
    } else if (temps_suiv) {
        totalMinutes = temps_suiv;
        totalHourWeek = temps_suiv / 60;
    }

    // Accumulation des temps pour chaque jour de la semaine
    for (let i = parseInt(premierJour); i <= parseInt(dernierJour); i++) {
        const value = jQuery('.totalDay' + i).text().trim();

        if ($mode === 'hours_decimal') {
            totalHourWeek += parseFloat(value || '0');
        } else {
            const taskTime = new Date(0);
            const result = parseTime(value, taskTime); // valeur entre 0 et 1 ?
            if (result >= 0) {
                totalHours += taskTime.getHours() + result * 24;
                totalMinutes += taskTime.getMinutes();
            }
        }
    }

    // Calcul de l'écart avec l'objectif de la semaine
    let diff;
    if ($mode === 'hours_decimal') {
        diff = totalHourWeek - parseFloat(heure_semaine - timeHoliday);
    } else {
        const extraHours = Math.floor(totalMinutes / 60);
        totalMinutes = totalMinutes % 60;
        diff = ((extraHours + totalHours) * 60 + totalMinutes) - parseInt((heure_semaine - timeHoliday) * 60);
    }

    // Choix de la couleur en fonction du dépassement
    let color = "";
    if (diff < 0) {
        color = "red";
    } else if (diff > 0) {
        color = "#3d85c6"; // bleu
    }

    // Affichage du total hebdomadaire + écart
    const container = jQuery('#' + modal.id);
    const parent = container.parent();

    if ($mode === 'hours_decimal') {
        const formattedTotal = totalHourWeek.toFixed(2);
        const formattedDiff = Math.abs(diff).toFixed(2);
        if (diff < 0) {
            container.text(`${pad(formattedTotal)} (-${formattedDiff}h)`);
        } else if (diff > 0) {
            container.text(`${pad(formattedTotal)} (+${formattedDiff}h)`);
        } else {
            container.text(pad(formattedTotal));
        }
    } else {
        const totalHourFinal = totalHours + Math.floor(totalMinutes / 60);
        const totalMinFinal = totalMinutes % 60;
        const timeText = `${pad(totalHourFinal)}:${pad(totalMinFinal)}`;
        const convertedDiff = time_convert(Math.abs(diff));

        if (diff < 0) {
            container.text(`${timeText} (${(diff / 60).toFixed(2)}h ➔ -${convertedDiff})`);
        } else if (diff > 0) {
            container.text(`${timeText} (+${(diff / 60).toFixed(2)}h ➔ +${convertedDiff})`);
        } else {
            container.text(timeText);
        }
    }

    parent.css('color', color);
}

//function to open note
function openNote(noteid) {
    var modal = document.getElementById(noteid);
    modal.style.display = "block";
}

//function to close note
function closeNotes(object) {
    var patt = /(\w+)\.png$/gi
    var modalbox = object.parentNode.parentNode;
    modalbox.style.display = "none";
    var icon = (modalbox.firstChild.lastChild.value.length > 0) ? "note_plein" : "note_vide";
    var imgnote = document.getElementById("img_" + modalbox.id);
    imgnote.src = imgnote.src.replace(patt, "$'" + icon + ".png");
}

function closeNotes2(object) {
    var patt = /(\w+)\.png$/gi
    var modalbox = object.parentNode.parentNode;
    modalbox.style.display = "none";
    var icon = (modalbox.firstChild.lastChild.value.length > 0) ? "fas" : "far";
    var imgnote = document.getElementById("img_" + modalbox.id);
    imgnote.src = imgnote.classList.remove('far');
    imgnote.src = imgnote.classList.remove('fas');
    imgnote.src = imgnote.classList.add(icon);

}

// Redimensionne le tableau et certains éléments pour adapter à la taille de l'écran
function redimenssion(explication) {
    // if (window.location.href.includes('card')) {
    //     if (window.innerHeight >= 750) {
    //         $(".div-table-responsive table")[0].style.maxHeight = "calc(100vh - 40px - 52px - " + $("#dragDropAreaTabBar")[0].offsetHeight + "px)";
    //     }
    //     else {
    //         $(".div-table-responsive table")[0].style.maxHeight = ""
    //     }
    // }
    // else {
    //     if (window.innerHeight >= 750) {
    //         if (explication == 'close') {
    //             $(".div-table-responsive table")[0].style.maxHeight = "calc(100vh - 20px - 30px - 19px - 10px - 40px - 12px - 8px - 53px - 52px - 15px - 20px - 20px - " + $("#filtre.liste_titre.liste_titre_bydiv.centpercent")[0].offsetHeight + "px - "
    //                 + $(".toggled-off")[0].offsetHeight + "px + " + $("#fonctionnement")[0].offsetHeight + "px)";
    //         }
    //         else {
    //             $(".div-table-responsive table")[0].style.maxHeight = "calc(100vh - 20px - 30px - 19px - 10px - 40px - 12px - 8px - 53px - 52px - 15px - 20px - 20px - " + $("#filtre.liste_titre.liste_titre_bydiv.centpercent")[0].offsetHeight + "px - "
    //                 + $(".toggled-off")[0].offsetHeight + "px)";
    //         }
    //     }
    //     else {
    //         $(".div-table-responsive table")[0].style.maxHeight = ""
    //     }
    // }

    if (explication == 'close') {
        heightbefore = $("div.tmenudiv")[0].offsetHeight + 150;
        $('form[name=addtime]')[0].style.height = 'calc(100vh - '+heightbefore+'px)';
    }
    else {
        heightbefore = $("div.tmenudiv")[0].offsetHeight + $("div.toggled-off")[0].offsetHeight + 100;
        $('form[name=addtime]')[0].style.height = 'calc(100vh - '+heightbefore+'px)';
    }

    // cac = document.querySelectorAll(".fixed_cac");
    // for (var i = 0; i < cac.length; i++) {
    //     cac[i].style.left = (columnWidth1 + 16) + "px";
    // }
}

function checkEmptyFormFields(even, Myform, msg) {
    var curform = document.forms[Myform];
    var fields = curform.getElementsByTagName("input");
    var error = 0;
    for (field in fields) {
        if (fields[field].value == '' && fields[field].name != '') error++;
    }
    var selects = curform.getElementsByTagName("select");
    for (select in selects) {
        if (selects[select].value == '-1' && fields[field].name != '') error++;
    }

    if (error) {
        $.jnotify(msg, 'error', true);
        return false
    }


}

// TODO : a améliorer pour set un type de déplacement spécifique
function setTypeDeplacement(idw, typeDeplacement) {
    select = $("[name='type_deplacement[" + idw + "]']")[0];
    if (typeDeplacement == 'petitDeplacement' && select[1].selected == false && select[2].selected == false && select[3].selected == false && select[4].selected == false && select[5].selected == false && select[6].selected == false && select[7].selected == false && select[8].selected == false && select[9].selected == false) {
        select[1].selected = true;
    }
    else if (typeDeplacement == 'grandDeplacement' && select[1].selected == false && select[2].selected == false && select[3].selected == false && select[4].selected == false && select[5].selected == false && select[6].selected == false && select[7].selected == false && select[8].selected == false && select[9].selected == false) {
        select[5].selected = true;
    }
}

function deleteTypeDeplacement(idw, typeDeplacement, nb_jour, num_first_day) {
    select = $("[name='type_deplacement[" + idw + "]']")[0];
    if (typeDeplacement == 'petitDeplacement' && select[1].selected == true) {
        select[1].selected = false;
    }
    else if (typeDeplacement == 'grandDeplacement' && select[5].selected == true) {
        select[5].selected = false;
    }
    else if (typeDeplacement == '') {
        select[0].selected = true;
    }
    updateTotal_TypeDeplacement(nb_jour, num_first_day);
}


//
// Gestion des heures pointées
//

/*
 *
 * @param {type} object where the data has to e validated
 * @param {type} ts     timesheet id
 * @param {type} day    day to update total
 * @param {type} silent will show message to user or not
 * @returns {undefined}
 */
function validateTime(object, idw, jour_ecart, mode_input, nb_jour, temps, typeDeplacement, heure_semaine_hs, modifyTypeDeplacement, heure_max_jour, heure_max_semaine) {
    updated = false;
    if (validateTotal(idw, mode_input, heure_max_jour) < 0) {
        object.value = "";
        object.style.backgroundColor = "#ff000078";
        return 0;
    }
    else if (object.style.backgroundColor == "#ff000078" || object.style.backgroundColor == "rgba(255, 0, 0, 0.47)") {
        object.style.backgroundColor = "white";
    }

    if (validateTotalSemaine(object, idw, jour_ecart, temps, nb_jour, heure_semaine_hs, mode_input, heure_max_semaine) < 0) {
        object.value = "";
        object.style.backgroundColor = "#ff000078";
        return 0;
    }

    // Notification jours feriés
    if (object.value && object.parentNode.className.includes('public_holiday')) {
        $.jnotify(WRN_PUBLIC_HOLIDAY, 'warning', false);
    }

    if (modifyTypeDeplacement) {
        if (object.value != '' && object.value != '0:00' && object.value != '0' && (object.oldvalue_focus == '' || object.oldvalue_focus == '00:00' || object.oldvalue_focus == '0:00')) {
            setTypeDeplacement(idw, typeDeplacement);
        }
        else if (document.getElementsByClassName('totalDay' + idw)[0].innerHTML == '00:00') {
            deleteTypeDeplacement(idw, typeDeplacement, nb_jour, jour_ecart);
        }
    }
}

// Valide le total des heures pointées
function validateTotal(idw, mode_input, heure_max_jour) {
    var total = 0;
    try {
        //var Total = document.getElementsByClassName('TotalColumn_'+idw);
        var col = document.getElementsByClassName('time_' + idw);
        var Total = document.getElementsByClassName('totalDay' + idw);
        if($mode_input = 'hours_decimal') {
            if (Total[0].innerHTML) {
                total += parseFloat(Total[0].innerHTML * 60);
            }
        }
        else {
            for (var i = 0; i < col.length; i++) {
                if (col[i].value) {
                    taskTime = parseTimeInt(col[i].value);
                    total += taskTime.minutes + 60 * taskTime.hours;
                }
            }
        }
        var hours = total / 60;
        if (hours > heure_max_jour) {
            $.jnotify(ERR_HEURE_MAX_JOUR_DEPASSEMENT, 'error', false);
            return -1;
        }
    }
    catch (err) {
        $.jnotify("validateTotal " + err, 'error', true);
    }
    return 1;
}

function validateTotalSemaine(object, idw, jour_ecart, temps, nb_jour, heure_semaine_hs, mode_input, heure_max_semaine) {
    var total = 0;
    var heureCase = 0;
    var total_hs = 0;
    try {
        var debut = idw - jour_ecart;
        if (debut < 0) {
            debut = 0;
            total += parseInt(temps, null);
        }
        var fin = idw - jour_ecart + 7;
        if (fin >= nb_jour) {
            fin = nb_jour;
            total += parseInt(temps, null);
        }
        for (var i = debut; i < fin; i++) {
            var Total = document.getElementsByClassName('totalDay' + i);
            if(mode_input == 'hours_decimal' && Total[0].innerHTML) {
                totalDay = 60 * parseFloat(Total[0].innerHTML);

                if (totalDay <= 1440) {
                    total += totalDay;
                    if (Total[0].parentNode.className.indexOf('onholidayallday') !== -1) {
                        total_hs += totalDay;
                    }
                }
            }
            else if (Total[0].innerHTML) {
                var taskTime = new Date(0);
                parseTime(Total[0].innerHTML, taskTime);
                totalDay = taskTime.getMinutes() + 60 * taskTime.getHours();

                if (totalDay <= 600) {
                    total += totalDay;
                    if (Total[0].parentNode.className.indexOf('onholidayallday') !== -1) {
                        total_hs += totalDay;
                    }
                }
            }
            var hours = total / 60;
            if (hours > heure_max_semaine) {
                $.jnotify(ERR_HEURE_MAX_SEMAINE_DEPASSEMENT, 'error', false);
                return -1;
            }
        }

        // On enlève le dimanche
        total -= total_hs;
        hours = total / 60;
        if (fin < nb_jour) {
            fin -= 1;
        }

        if(mode_input == 'hours_decimal' && object.value) {
            heureCase = 60 * parseFloat(object.value);
        }
        else if (object.value) {
            var tempsCase = new Date(0);
            parseTime(object.value, tempsCase);
            heureCase = tempsCase.getMinutes() / 60 + tempsCase.getHours();
        }

        if (WRN_35H_DEPASSEMENT && hours > heure_semaine_hs && (hours - heureCase <= heure_semaine_hs)) {
            $.jnotify(WRN_35H_DEPASSEMENT, 'warning', false);
        }

        if(USE_HS_CASE) {
            if (hours > heure_semaine_hs) {
                ajouterCaseHS(debut, fin, idw, jour_ecart, 'hours', nb_jour, temps, heure_semaine_hs);
            }

            if (hours > HEURE_SUP1 /*&& (hours - heureCase <= HEURE_SUP1)*/) {
                ActiverCaseHS_50(debut, fin);
            }

            var hs = document.getElementsByClassName('hs25');
            if (hs.length != 0) {
                if (hours <= heure_semaine_hs) {
                    supprimerCaseHS(debut, fin);
                }
                else if (hours <= HEURE_SUP1) {
                    DesactiverCaseHS_50(debut, fin);
                }
            }
        }
    }
    catch (err) {
        $.jnotify("validateTotalSemaine " + err, 'error', true);
    }
    return 1;
}



//
// Gestion des heures sup
//

function validateTime_HS(object, idw, jour_ecart, mode_input, nb_jour, tache, temps, temps_hs_25, temps_hs_50, heure_semaine_hs) {
    if (validateTotal_HS(idw, tache) < 0) {
        if (object.id.indexOf('timeadded[') !== -1) {
            var heure_sup = document.getElementsByClassName('time_hs_' + tache + '_' + idw);
            for (var i = 0; i < heure_sup.length; i++) {
                if (heure_sup[i].value != "") {
                    heure_sup[i].value = "";
                    heure_sup[i].style.backgroundColor = "#ff000078";
                }
            }
        }
        else {
            object.value = "";
            object.style.backgroundColor = "#ff000078";
        }
    }
    else if (validateTotalSemaine_HS(idw, jour_ecart, temps, temps_hs_25, temps_hs_50, nb_jour, heure_semaine_hs) < 0) {
        if (object.id.indexOf('timeadded[') !== -1) {
            var debut = idw - jour_ecart;
            if (debut < 0) {
                debut = 0;
            }
            var fin = idw - jour_ecart + 7;
            if (fin >= nb_jour) {
                fin = nb_jour;
            }
            for (var i = debut; i < fin; i++) {
                var heure_sup = document.getElementsByClassName('time_hs_' + i);
                for (var a = 0; a < heure_sup.length; a++) {
                    if (heure_sup[a].value != "") {
                        heure_sup[a].value = "";
                        heure_sup[a].style.backgroundColor = "#ff000078";
                    }
                }
            }
        }
        else {
            object.value = "";
            object.style.backgroundColor = "#ff000078";
        }
    }
    else if ((object.style.backgroundColor == "#ff000078" || object.style.backgroundColor == "rgba(255, 0, 0, 0.47)") && object.id.indexOf('timeadded[') === -1) {
        object.style.backgroundColor = "white";
    }
}

// Valide le total des heures sup
function validateTotal_HS(idw, tache) {
    var hours_non_sup = 0;
    try {
        var heure_non_sup = document.getElementById('timeadded[' + tache + '][' + idw + ']');
        if (heure_non_sup.value) {
            var taskTime = new Date(0);
            parseTime(heure_non_sup.value, taskTime);
            total = taskTime.getMinutes() + 60 * taskTime.getHours();
            hours_non_sup = total / 60;
        }
        var heure_sup = document.getElementsByClassName('time_hs_' + tache + '_' + idw);
        var total = 0;
        for (var i = 0; i < heure_sup.length; i++) {
            if (heure_sup[i].value) {
                taskTime = parseTimeInt(heure_sup[i].value);
                total += taskTime.minutes + 60 * taskTime.hours;
            }
        }
        var hours_sup = total / 60;
        if (hours_sup > hours_non_sup) {
            $.jnotify('Les heures sup ne peuvent pas dépasser le temps sur cette journée/tache', 'error', false);
            return -1;
        }
    }
    catch (err) {
        $.jnotify("validateTotal_HS " + err, 'error', true);
    }
    return 1;
}

function validateTotalSemaine_HS(idw, jour_ecart, temps, temps_hs_25, temps_hs_50, nb_jour, heure_semaine_hs) {
    var total_heure_sup_25 = 0;
    var total_heure_sup_50 = 0;
    var total_heure = 0;
    var total_hs = 0;
    try {
        var debut = idw - jour_ecart;
        if (debut < 0) {
            debut = 0;
            total_heure_sup_25 += parseInt(temps_hs_25, null);
            total_heure_sup_50 += parseInt(temps_hs_50, null);
            total_heure += parseInt(temps, null);
        }
        var fin = idw - jour_ecart + 6;
        if (fin >= nb_jour) {
            fin = nb_jour;
            total_heure_sup_25 += parseInt(temps_hs_25, null);
            total_heure_sup_50 += parseInt(temps_hs_50, null);
            total_heure += parseInt(temps, null);
        }

        // Calcul le nombre d'heure total de la semaine
        for (var i = debut; i < fin; i++) {
            var Total = document.getElementsByClassName('totalDay' + i);
            if (Total[0].innerHTML) {
                var taskTime = new Date(0);
                parseTime(Total[0].innerHTML, taskTime);
                total_heure += taskTime.getMinutes() + 60 * taskTime.getHours();
                if (Total[0].parentNode.className.indexOf('onholidayallday') !== -1) {
                    total_hs += taskTime.getMinutes() + 60 * taskTime.getHours();
                }
            }
        }
        total_heure -= total_hs;
        var hours = total_heure / 60;

        // Calcul le nombre d'heure sup à 25% de la semaine
        for (var i = debut; i < fin; i++) {
            var heure_sup = document.getElementsByClassName('time_hs_' + i);
            for (var a = 0; a < heure_sup.length; a++) {
                if (heure_sup[a].name.indexOf('hs25_task') !== -1 && heure_sup[a].value) {
                    var taskTime = new Date(0);
                    parseTime(heure_sup[a].value, taskTime);
                    total_heure_sup_25 += taskTime.getMinutes() + 60 * taskTime.getHours();
                }
            }
        }
        var hours_sup_25 = total_heure_sup_25 / 60;

        // Calcul le nombre d'heure sup à 50% de la semaine
        for (var i = debut; i < fin; i++) {
            var heure_sup = document.getElementsByClassName('time_hs_' + i);
            for (var a = 0; a < heure_sup.length; a++) {
                if (heure_sup[a].name.indexOf('hs50_task') !== -1 && heure_sup[a].value) {
                    var taskTime = new Date(0);
                    parseTime(heure_sup[a].value, taskTime);
                    total_heure_sup_50 += taskTime.getMinutes() + 60 * taskTime.getHours();
                }
            }
        }
        var hours_sup_50 = total_heure_sup_50 / 60;

        if (hours > heure_semaine_hs) {
            if (hours_sup_25 > 8) {
                $.jnotify("Vous ne pouvez pas faire + de 8h d'heure sup à 25%", 'error', false);
                return -1;
            }
            else if (hours_sup_25 > hours - heure_semaine_hs) {
                $.jnotify("Vous n'avez pas fait autant d'heure sup", 'error', false);
                return -1;
            }

            if (hours > HEURE_SUP1) {
                if (hours_sup_50 > 5) {
                    $.jnotify("Vous ne pouvez pas faire + de 5h d'heure sup à 50%", 'error', false);
                    return -1;
                }
                else if (hours_sup_50 > hours - HEURE_SUP1) {
                    $.jnotify("Vous n'avez pas fait autant d'heure sup", 'error', false);
                    return -1;
                }
            }
        }
        else return 1


    }
    catch (err) {
        $.jnotify("validateTotalSemaine_HS " + err, 'error', true);
    }
    return 1;
}

// Ajoute les 2 cases d'HS
function ajouterCaseHS(debut, fin, idw, ecart_lundi, mode_input, nb_jour, temps, heure_semaine_hs) {
    for (var a = debut; a < fin; a++) {
        var col = document.getElementsByClassName('time_' + a);
        for (var u = 0; u < col.length; u++) {
            var time_hs = document.getElementsByClassName('time_hs_' + u + '_' + a);
            if (col[u].disabled != true && col[u].value) {
                if (time_hs.length == 0 && (col[u].parentNode.className.indexOf('onholidayallday') === -1)) {
                    jour_ecart = ecart_lundi + a - idw;
                    col[u].insertAdjacentHTML('afterend', '<input type="text" disabled alt="Ajoutez ici les heures sup entre ' + HEURE_SUP1 + ' et 48h" title="Ajoutez ici les heures sup entre ' + HEURE_SUP1 + ' et 48h" name="hs50_' + col[u].name + '" class="center smallpadd hs50 time_hs_' + a + ' time_hs_' + u + '_' + a + '" size="2" id="timeaddedhs[' + u + '][' + a + ']" value="" cols="2"  maxlength="5" onkeypress="return regexEvent_TS(this,event,\'timeChar\')" onblur="regexEvent_TS(this,event,\'hours\'); validateTime_HS(this,' + a + ',' + jour_ecart + ', \'' + mode_input + '\',' + nb_jour + ',' + u + ',' + temps + ',' + '0' + ',' + '0' + ',' + heure_semaine_hs + ');" />');
                    col[u].insertAdjacentHTML('afterend', '<br><input type="text" alt="Ajoutez ici les heures sup entre ' + heure_semaine_hs + ' et ' + HEURE_SUP1 + 'h" title="Ajoutez ici les heures sup entre ' + heure_semaine_hs + ' et ' + HEURE_SUP1 + 'h" name="hs25_' + col[u].name + '" class="center smallpadd hs25 time_hs_' + a + ' time_hs_' + u + '_' + a + '" size="2" id="timeaddedhs[' + u + '][' + a + ']" value="" cols="2"  maxlength="5" onkeypress="return regexEvent_TS(this,event,\'timeChar\')" onblur="regexEvent_TS(this,event,\'hours\'); validateTime_HS(this,' + a + ',' + jour_ecart + ', \'' + mode_input + '\',' + nb_jour + ',' + u + ',' + temps + ',' + '0' + ',' + '0' + ',' + heure_semaine_hs + ');" />');
                }
            }
            else if (time_hs.length != 0 && !col[u].value) {
                time_hs[0].remove();
                time_hs[0].remove();
            }
        }
    }
}

// Supprime les 2 cases d'HS
function supprimerCaseHS(debut, fin) {
    for (var a = debut; a < fin; a++) {
        var col = document.getElementsByClassName('time_' + a);
        for (var u = 0; u < col.length; u++) {
            if (col[u].disabled != true) {
                var time_hs = document.getElementsByClassName('time_hs_' + u + '_' + a);
                if (time_hs.length != 0) {
                    time_hs[0].remove();
                    time_hs[0].remove();
                }
            }
        }
    }
}

// Active la case d'heure sup > 43
function ActiverCaseHS_50(debut, fin) {
    for (var a = debut; a < fin; a++) {
        var col = document.getElementsByClassName('time_' + a);
        for (var u = 0; u < col.length; u++) {
            if (col[u].disabled != true) {
                var time_hs = document.getElementsByClassName('time_hs_' + u + '_' + a);
                if (time_hs.length != 0) {
                    time_hs[1].disabled = false;
                }
            }
        }
    }
}

// Désactive la case d'heure sup > 43
function DesactiverCaseHS_50(debut, fin) {
    for (var a = debut; a < fin; a++) {
        var col = document.getElementsByClassName('time_' + a);
        for (var u = 0; u < col.length; u++) {
            if (col[u].disabled != true) {
                var time_hs = document.getElementsByClassName('time_hs_' + u + '_' + a);
                if (time_hs.length != 0) {
                    time_hs[1].disabled = true;
                    time_hs[1].value = "";
                    time_hs[1].style.backgroundColor = '';
                }
            }
        }
    }
}




//
// Gestion des autres types d'heures
//

// TODO : Améliorer la fonction
/* Update total. days = column nb starting from 0 */
function updateTotal_OtherHours(nb_jour, num_task, num_first_day, id_task) {
    // Mise à jour du total d'heure de nuit de la tache
    totalhour = 0;
    totalmin = 0;
    var total_heureNuit = document.getElementById('total_heureNuit[' + num_task + ']');
    if (total_heureNuit !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            var heure = document.getElementById('time_heure_nuit[' + id_task + '][' + i + ']');
            var taskTime = new Date(0);

            result = parseTime(heure.value, taskTime);
            if (result >= 0) {
                totalhour = totalhour + taskTime.getHours() + result * 24;
                totalmin = totalmin + taskTime.getMinutes();
            }
        }
        morehours = Math.floor(totalmin / 60);
        totalmin = totalmin % 60;

        var texttoshow = pad(morehours + totalhour) + ':' + pad(totalmin);
        total_heureNuit.textContent = texttoshow;
    }

    // Mise à jour du total d'heure de port d'EPI de la tache
    totalhour = 0;
    totalmin = 0;
    var total_heureEPI = document.getElementById('total_heureEPI[' + num_task + ']');

    if (total_heureEPI !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            var heure = document.getElementById('time_epi[' + id_task + '][' + i + ']');
            var taskTime = new Date(0);
            result = parseTime(heure.value, taskTime);
            if (result >= 0) {
                totalhour = totalhour + taskTime.getHours() + result * 24;
                totalmin = totalmin + taskTime.getMinutes();
            }
        }
        morehours = Math.floor(totalmin / 60);
        totalmin = totalmin % 60;

        var texttoshow = pad(morehours + totalhour) + ':' + pad(totalmin);
        total_heureEPI.textContent = texttoshow;
    }
}

// Permet d'ajouter / supprimes les cases correspondantes lorsqu'on coche une checkbox
function CheckboxHeureChange(checkbox, task_id, nb_jour_mois, ligne_id, num_first_day) {
    if (checkbox.checked == true) {
        if (checkbox.name == "heure_nuit_chkb") {
            AjouterCaseHeureNuit(0, nb_jour_mois, task_id, ligne_id, num_first_day);
        }
        else if (checkbox.name == "port_epi_chkb") {
            AjouterCaseEPI(0, nb_jour_mois, task_id, ligne_id, num_first_day);
        }
    } else {
        if (checkbox.name == "heure_nuit_chkb") {
            SupprimerCaseHeureNuit(0, nb_jour_mois, task_id, ligne_id);
        }
        else if (checkbox.name == "port_epi_chkb") {
            SupprimerCaseEPI(0, nb_jour_mois, task_id, ligne_id);
        }
    }
}



// Permet d'ajouter les cases d'heures de nuit
function AjouterCaseHeureNuit(debut, fin, task_id, ligne_id, num_first_day) {
    active_total = 0;
    var task_line = document.querySelector('tr.oddeven[data-taskid="' + task_id + '"]');

    for (var a = debut; a < fin; a++) {
        var case_temps = task_line.getElementsByClassName('time_' + a)[0];

        // On récupère l'input des heures de compagnonnage 
        var input_before = document.getElementById('time_heure_compagnonnage_' + task_id + "_" + a);
        // Si l'input des heures de compagnonnage n'existe pas, on récupère l'input des heures sup
        if (input_before === undefined || input_before === null) {
            input_before = task_line.getElementsByClassName('time_hs_' + ligne_id + "_" + a)[1];
        }

        // Si l'input des heures sup n'existe pas, on récupère l'input des temps
        if (input_before === undefined || input_before === null) {
            input_before = case_temps;
        }

        if (case_temps.disabled != true) {
            input_before.insertAdjacentHTML('afterend', '<div id="time_heure_nuit_' + task_id + '_' + a + '" style="display: inline;"><br><input type="text" alt="Ajoutez ici les heures de nuit" title="Ajoutez ici les heures de nuit" name="heure_nuit[' + task_id + "][" + a + ']" class="center smallpadd heure_nuit ' + 'time_heure_nuit' + task_id + '_' + a + '" size="2" id="time_heure_nuit[' + task_id + '][' + a + ']" value="" cols="2"  maxlength="5" onblur="validateTime_HeureNuit(this, ' + ligne_id + ', ' + a + '); updateTotal_OtherHours(' + fin + ', ' + ligne_id + ', ' + num_first_day + ', ' + task_id + ')"/></div>');
            active_total = 1;
        }
    }

    if (active_total) {
        var total_before = document.getElementById('total_heureCompagnonnage[' + ligne_id + ']');
        if (total_before === undefined || total_before === null) {
            var total_before = document.getElementById('total_task[' + ligne_id + ']');
        }
        total_before.insertAdjacentHTML('afterend', '<br><span class="total_heureNuit txt_heure_nuit" id="total_heureNuit[' + ligne_id + ']">' + '00:00' + '</span>');
    }
}

// Permet de supprimer les cases d'heures de nuit
function SupprimerCaseHeureNuit(debut, fin, task_id, ligne_id) {
    for (var a = debut; a < fin; a++) {
        var time_heure_nuit = document.getElementById('time_heure_nuit_' + task_id + '_' + a);
        if(time_heure_nuit) {
            time_heure_nuit.remove();
        }
    }

    var total_heureNuit = document.getElementById('total_heureNuit[' + ligne_id + ']');
    var baliseBR = total_heureNuit.previousSibling;
    total_heureNuit.remove();
    if (baliseBR.nodeName === "BR") {
        baliseBR.remove();
    }
}

// Permet de valider ou non un input dans les cases d'heures de nuit (l'input est non validé si le nombre d'heure entré > temps pointé)
function validateTime_HeureNuit(object, num_ligne, idw) {
    nb_heure = 0;
    nb_heure_nuit = 0;
    total = 0; 

    var heure = document.getElementById('timeadded[' + num_ligne + '][' + idw + ']');
    if (heure.value) {
        taskTime = parseTimeInt(heure.value);
        total += taskTime.minutes + 60 * taskTime.hours;
        nb_heure = total / 60;
    }

    nb_heure_nuit = formatDecimalTime(object.value);
    if (nb_heure_nuit !== undefined) {
        object.value = nb_heure_nuit;
    }

    if (nb_heure_nuit > nb_heure) {
        $.jnotify('Les heures de nuit ne peuvent pas dépasser le temps sur cette tache', 'error', false);
        object.value = "";
        object.style.backgroundColor = "#ff000078";
    }
    else if (object.style.backgroundColor == "#ff000078" || object.style.backgroundColor == "rgba(255, 0, 0, 0.47)") {
        object.style.backgroundColor = "white";
    }
}


// Permet d'ajouter les cases d'heures de port des EPI
function AjouterCaseEPI(debut, fin, task_id, ligne_id, num_first_day) {
    active_total = 0;
    var task_line = document.querySelector('tr.oddeven[data-taskid="' + task_id + '"]');

    for (var a = debut; a < fin; a++) {
        var case_temps = task_line.getElementsByClassName('time_' + a)[0];

        // On récupère l'input des heures de route 
        var input_before = document.getElementById('time_heure_route_' + task_id + "_" + a);

        // Si l'input des heures de route n'existe pas, on récupère l'input des heures de nuit
        if (input_before === undefined || input_before === null) {
            input_before = document.getElementById('time_heure_nuit_' + task_id + "_" + a);
        }

        // Si l'input des heures de nuit n'existe pas, on récupère l'input des heures de compagnonnages
        if (input_before === undefined || input_before === null) {
            input_before = document.getElementById('time_heure_compagnonnage_' + task_id + "_" + a);
        }

        // Si l'input des heures de compagnonnage n'existe pas, on récupère l'input des heures sup
        if (input_before === undefined || input_before === null) {
            input_before = task_line.getElementsByClassName('time_hs_' + ligne_id + "_" + a)[1];
        }

        // Si l'input des heures sup n'existe pas, on récupère l'input des temps
        if (input_before === undefined || input_before === null) {
            input_before = case_temps;
        }

        if (case_temps.disabled != true) {
            input_before.insertAdjacentHTML('afterend', '<div id="time_epi_' + task_id + '_' + a + '" style="display: inline;"><br><input type="text" alt="Ajoutez ici les EPI respiratoire" title="Ajoutez ici les EPI respiratoire" name="epi[' + task_id + "][" + a + ']" class="center smallpadd heure_epi ' + 'time_epi_' + task_id + '_' + a + '" size="2" id="time_epi[' + task_id + '][' + a + ']" value="" cols="2"  maxlength="5" onblur="validateTime_EPI(this, ' + ligne_id + ', ' + a + ');  updateTotal_OtherHours(' + fin + ', ' + ligne_id + ', ' + num_first_day + ', ' + task_id + ')"/></div>');
            active_total = 1;
        }
    }

    if (active_total) {
        var total_before = document.getElementById('total_heureRoute[' + ligne_id + ']');
        if (total_before === undefined || total_before === null) {
            var total_before = document.getElementById('total_heureNuit[' + ligne_id + ']');
        }
        if (total_before === undefined || total_before === null) {
            var total_before = document.getElementById('total_heureCompagnonnage[' + ligne_id + ']');
        }
        if (total_before === undefined || total_before === null) {
            var total_before = document.getElementById('total_task[' + ligne_id + ']');
        }
        total_before.insertAdjacentHTML('afterend', '<br><span class="total_heureEPI txt_heure_epi" id="total_heureEPI[' + ligne_id + ']">' + '00:00' + '</span>');
    }
}

// Permet de supprimer les cases d'heures de port des EPI
function SupprimerCaseEPI(debut, fin, task_id, ligne_id) {
    for (var a = debut; a < fin; a++) {
        var time_epi = document.getElementById('time_epi_' + task_id + '_' + a);
        if(time_epi) {
            time_epi.remove();
        }
    }

    var total_heureEPI = document.getElementById('total_heureEPI[' + ligne_id + ']');
    var baliseBR = total_heureEPI.previousSibling;
    total_heureEPI.remove();
    if (baliseBR.nodeName === "BR") {
        baliseBR.remove();
    }
}

// Permet de valider ou non un input dans les cases d'heures des EPI (l'input est non validé si le nombre d'heure entré > temps pointé)
function validateTime_EPI(object, num_ligne, idw) {
    nb_heure = 0;
    nb_heure_epi = 0;
    total = 0;

    var heure = document.getElementById('timeadded[' + num_ligne + '][' + idw + ']');
    if (heure.value) {
        taskTime = parseTimeInt(heure.value);
        total += taskTime.minutes + 60 * taskTime.hours;
        nb_heure = total / 60;
    }

    nb_heure_epi = formatDecimalTime(object.value);
    if (nb_heure_epi !== undefined) {
        object.value = nb_heure_epi;
    }

    if (nb_heure_epi > nb_heure) {
        $.jnotify('Les heures d\'EPI ne peuvent pas dépasser le temps sur cette tache', 'error', false);
        object.value = "";
        object.style.backgroundColor = "#ff000078";
    }
    else if (object.style.backgroundColor == "#ff000078" || object.style.backgroundColor == "rgba(255, 0, 0, 0.47)") {
        object.style.backgroundColor = "white";
    }
}

function ValidateTimeDecimal(time) {
    timeDecimal = formatDecimalTime(time.value);
    if (timeDecimal !== undefined) {
        time.value = timeDecimal;
    }
}

function formatDecimalTime(time) {
    if (time) {
        if (time.includes(':')) {
            // Diviser l'heure et les minutes
            var split = time.split(':');

            // Convertir les parties en nombres entiers
            var heures = parseInt(split[0], 10);
            var minutes = parseInt(split[1], 10);

            // Calculer la valeur décimale
            var timeDecimal = heures + minutes / 60;
        }
        else if (isNaN(parseFloat(time))) {
            timeDecimal = '';
        }
        else {
            timeDecimal = parseFloat(time.replace(',', '.'));
        }
    }

    return timeDecimal;
}




//
// Gestion des déplacements
//

// -TODO : Améliorer toutes les fonctions 

// Permet de mettre à jour le total de nombre de déplacement ponctuel
function updateTotal_DeplacementPonctuel(nb_jour, num_first_day) {
    total = 0;

    for (i = num_first_day; i < nb_jour; i++) {
        checkbox_deplacementPonctuel = document.getElementById('deplacement_ponctuel[' + i + ']');
        if (checkbox_deplacementPonctuel.checked === true) {
            total++;
        }
    }

    totalDeplacementPonctuel = document.getElementById('totalDeplacementPonctuel');
    totalDeplacementPonctuel.textContent = total + ' DP';

    if (total != 0) {
        totalDeplacementPonctuel.classList.add('noNull')
    }
    else {
        totalDeplacementPonctuel.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total de type de déplacement
function updateTotal_TypeDeplacement(nb_jour, num_first_day) {
    arrayTotalTypeDeplacement = { 'D1': 0, 'D2': 0, 'D3': 0, 'D4': 0, 'GD1': 0, 'GD2': 0, 'DOM': 0 };
    arrayTitle = { 1: 'D1', 2: 'D2', 3: 'D3', 4: 'D4', 5: 'GD1', 6: 'GD2', 7: 'DOM', 8: 'GD1', 9: 'GD1' }

    if (document.getElementById('regulD1') && document.getElementById('regulD1').value) {
        arrayTotalTypeDeplacement['D1'] = parseInt(document.getElementById('regulD1').value);
    }
    if (document.getElementById('regulD2') && document.getElementById('regulD2').value) {
        arrayTotalTypeDeplacement['D2'] = parseInt(document.getElementById('regulD2').value);
    }
    if (document.getElementById('regulD3') && document.getElementById('regulD3').value) {
        arrayTotalTypeDeplacement['D3'] = parseInt(document.getElementById('regulD3').value);
    }
    if (document.getElementById('regulD4') && document.getElementById('regulD4').value) {
        arrayTotalTypeDeplacement['D4'] = parseInt(document.getElementById('regulD4').value);
    }
    if (document.getElementById('regulGD1') && document.getElementById('regulGD1').value) {
        arrayTotalTypeDeplacement['GD1'] = parseInt(document.getElementById('regulGD1').value);
    }
    if (document.getElementById('regulGD2') && document.getElementById('regulGD2').value) {
        arrayTotalTypeDeplacement['GD2'] = parseInt(document.getElementById('regulGD2').value);
    }
    if (document.getElementById('regulDOM') && document.getElementById('regulDOM').value) {
        arrayTotalTypeDeplacement['DOM'] = parseInt(document.getElementById('regulDOM').value);
    }

    for (i = num_first_day; i < nb_jour; i++) {
        typeDeplacement = document.getElementById('type_deplacement[' + i + ']');
        arrayTotalTypeDeplacement[arrayTitle[typeDeplacement.value]]++;
    }

    totalTypeDeplacement = document.getElementById('totalTypeDeplacement');
    textTotal = '';
    textTotal += arrayTotalTypeDeplacement['D1'] != 0 ? arrayTotalTypeDeplacement['D1'] + " D1<br>" : '';
    textTotal += arrayTotalTypeDeplacement['D2'] != 0 ? arrayTotalTypeDeplacement['D2'] + " D2<br>" : '';
    textTotal += arrayTotalTypeDeplacement['D3'] != 0 ? arrayTotalTypeDeplacement['D3'] + " D3<br>" : '';
    textTotal += arrayTotalTypeDeplacement['D4'] != 0 ? arrayTotalTypeDeplacement['D4'] + " D4<br>" : '';
    textTotal += arrayTotalTypeDeplacement['GD1'] != 0 ? arrayTotalTypeDeplacement['GD1'] + " GD1<br>" : '';
    textTotal += arrayTotalTypeDeplacement['GD2'] != 0 ? arrayTotalTypeDeplacement['GD2'] + " GD2<br>" : '';
    textTotal += arrayTotalTypeDeplacement['DOM'] != 0 ? arrayTotalTypeDeplacement['DOM'] + " DOM<br>" : '';


    totalTypeDeplacement.innerHTML = textTotal;

    if (textTotal != '') {
        totalTypeDeplacement.classList.add('noNull')
    }
    else {
        totalTypeDeplacement.classList.remove('noNull')
    }
}

// Permet de mettre en rouge le type de déplacement lors d'un congés
function updateTypeDeplacement(object, deplacement_holiday) {
    if (deplacement_holiday && object.value != 0 && !object.classList.contains('deplacement_holiday')) {
        object.classList.add('deplacement_holiday')
    }
    else if (deplacement_holiday && object.value == 0 && object.classList.contains('deplacement_holiday')) {
        object.classList.remove('deplacement_holiday')
    }
}

// Permet de mettre à jour le total de Moyen de transport
function updateTotal_MoyenTransport(nb_jour, num_first_day, type_deplacement) {
    arrayTotalMoyenTransport = { 'VS': 0, 'A': 0, 'T': 0 };
    arrayTitle = { 1: 'VS', 2: 'A', 3: 'T' }

    for (i = num_first_day; i < nb_jour; i++) {
        moyenTransport = document.getElementById('moyen_transport[' + i + ']');
        arrayTotalMoyenTransport[arrayTitle[moyenTransport.value]]++;
    }

    totalMoyenTransport = document.getElementById('totalMoyenTransport');
    textTotal = '';
    textTotal += arrayTotalMoyenTransport['VS'] != 0 ? arrayTotalMoyenTransport['VS'] + " VS<br>" : '';
    textTotal += arrayTotalMoyenTransport['A'] != 0 ? arrayTotalMoyenTransport['A'] + " A<br>" : '';
    textTotal += arrayTotalMoyenTransport['T'] != 0 ? arrayTotalMoyenTransport['T'] + " T<br>" : '';
    totalMoyenTransport.innerHTML = textTotal;

    if (textTotal != '') {
        totalMoyenTransport.classList.add('noNull')
    }
    else {
        totalMoyenTransport.classList.remove('noNull')
    }
}

function deleteTypeDeplacementVS(idw, nb_jour, num_first_day) {
    select = $("[name='moyen_transport[" + idw + "]']")[0];
    if(select[1].selected == true) {
        deleteTypeDeplacement(idw, '', nb_jour, num_first_day);
    }
}

// Permet de mettre en rouge le moyen de transport lors d'un congés
function updateMoyenTransport(object, deplacement_holiday) {
    if (deplacement_holiday && object.value != 0 && !object.classList.contains('deplacement_holiday')) {
        object.classList.add('deplacement_holiday')
    }
    else if (deplacement_holiday && object.value == 0 && object.classList.contains('deplacement_holiday')) {
        object.classList.remove('deplacement_holiday')
    }
}

// Permet de mettre à jour le total des repas
function updateTotal_Repas(nb_jour, num_first_day, type_deplacement) {
    arrayTotalRepas = { 'R1': 0, 'R2': 0 };
    arrayTitle = { 1: 'R1', 2: 'R2' }

    if (document.getElementById('regulRepas1') && document.getElementById('regulRepas1').value) {
        arrayTotalRepas['R1'] = parseInt(document.getElementById('regulRepas1').value);
    }
    if (document.getElementById('regulRepas2') && document.getElementById('regulRepas2').value) {
        arrayTotalRepas['R2'] = parseInt(document.getElementById('regulRepas2').value);
    }

    for (i = num_first_day; i < nb_jour; i++) {
        repas = document.getElementById('repas[' + i + ']');
        arrayTotalRepas[arrayTitle[repas.value]]++;
    }

    totalRepas = document.getElementById('totalRepas');
    textTotal = arrayTotalRepas['R1'] + " R1<br>" + arrayTotalRepas['R2'] + " R2<br>"
    totalRepas.innerHTML = textTotal;

    if (arrayTotalRepas['R1'] != 0 || arrayTotalRepas['R2'] != 0) {
        totalRepas.classList.add('noNull')
    }
    else {
        totalRepas.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total des heures sup à 0%
function updateTotal_HeureSup00(nb_jour, num_first_day) {
    var totalHeureSup00 = document.getElementById('totalHeureSup00');
    var heure = 0;

    if (document.getElementById('regulHeureSup00') && document.getElementById('regulHeureSup00').value) {
        heure += parseFloat(document.getElementById('regulHeureSup00').value.replace(',', '.'));
    }

    if (totalHeureSup00 !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            if (document.getElementById('heure_sup00[' + i + ']') && parseFloat(document.getElementById('heure_sup00[' + i + ']').value) > 0)
                heure += parseFloat(document.getElementById('heure_sup00[' + i + ']').value.replace(',', '.'));
        }
    }

    totalHeureSup00.textContent = heure;

    if (heure != 0) {
        totalHeureSup00.classList.add('noNull')
    }
    else {
        totalHeureSup00.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total des heures sup à 25%
function updateTotal_HeureSup25(nb_jour, num_first_day) {
    var totalHeureSup25 = document.getElementById('totalHeureSup25');
    var heure = 0;

    if (document.getElementById('regulHeureSup25') && document.getElementById('regulHeureSup25').value) {
        heure += parseFloat(document.getElementById('regulHeureSup25').value.replace(',', '.'));
    }

    if (totalHeureSup25 !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            if (document.getElementById('heure_sup25[' + i + ']') && parseFloat(document.getElementById('heure_sup25[' + i + ']').value) > 0)
                heure += parseFloat(document.getElementById('heure_sup25[' + i + ']').value.replace(',', '.'));
        }
    }

    totalHeureSup25.textContent = heure;

    if (heure != 0) {
        totalHeureSup25.classList.add('noNull')
    }
    else {
        totalHeureSup25.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total des heures sup à 50%
function updateTotal_HeureSup50(nb_jour, num_first_day) {
    var totalHeureSup50 = document.getElementById('totalHeureSup50');
    var heure = 0;

    if (document.getElementById('regulHeureSup50') && document.getElementById('regulHeureSup50').value) {
        heure += parseFloat(document.getElementById('regulHeureSup50').value.replace(',', '.'));
    }

    if (totalHeureSup50 !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            if (document.getElementById('heure_sup50[' + i + ']') && parseFloat(document.getElementById('heure_sup50[' + i + ']').value) > 0)
                heure += parseFloat(document.getElementById('heure_sup50[' + i + ']').value.replace(',', '.'));
        }
    }

    totalHeureSup50.textContent = heure;

    if (heure != 0) {
        totalHeureSup50.classList.add('noNull')
    }
    else {
        totalHeureSup50.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total des heures sup à 50% HT
function updateTotal_HeureSup50HT(nb_jour, num_first_day) {
    var totalHeureSup50HT = document.getElementById('totalHeureSup50HT');
    var heure = 0;

    if (document.getElementById('regulHeureSup50HT') && document.getElementById('regulHeureSup50HT').value) {
        heure += parseFloat(document.getElementById('regulHeureSup50HT').value.replace(',', '.'));
    }

    if (totalHeureSup50HT !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            if (document.getElementById('heure_sup50ht[' + i + ']') && parseFloat(document.getElementById('heure_sup50ht[' + i + ']').value) > 0)
                heure += parseFloat(document.getElementById('heure_sup50ht[' + i + ']').value.replace(',', '.'));
        }
    }

    totalHeureSup50HT.textContent = heure;

    if (heure != 0) {
        totalHeureSup50HT.classList.add('noNull')
    }
    else {
        totalHeureSup50HT.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total des heures de nuit
function updateTotal_HeureNuit(nb_jour, num_first_day) {
    var totalHeureNuit = document.getElementById('totalHeureNuit');
    var heure = 0;

    if (totalHeureNuit !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            if (parseFloat(document.getElementById('heure_nuit_verif[' + i + ']').value) > 0)
                heure += parseFloat(document.getElementById('heure_nuit_verif[' + i + ']').value.replace(',', '.'));
        }
    }

    totalHeureNuit.textContent = heure + ' HN';

    if (heure != 0) {
        totalHeureNuit.classList.add('noNull')
    }
    else {
        totalHeureNuit.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total des heures de route
function updateTotal_HeureRoute(nb_jour, num_first_day) {
    var totalHeureRoute = document.getElementById('totalHeureRoute');
    var heure = 0;

    if (document.getElementById('regulHeureRoute') && document.getElementById('regulHeureRoute').value) {
        heure += parseFloat(document.getElementById('regulHeureRoute').value.replace(',', '.'));
    }

    if (totalHeureRoute !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            if (parseFloat(document.getElementById('heure_route[' + i + ']').value) > 0)
                heure += parseFloat(document.getElementById('heure_route[' + i + ']').value.replace(',', '.'));
        }
    }

    totalHeureRoute.textContent = heure + ' HR';

    if (heure != 0) {
        totalHeureRoute.classList.add('noNull')
    }
    else {
        totalHeureRoute.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total des kilometres
function updateTotal_Kilometres(nb_jour, num_first_day) {
    var totalKilometres = document.getElementById('totalKilometres');
    var heure = 0;

    if (document.getElementById('regulKilometres') && document.getElementById('regulKilometres').value) {
        heure += parseFloat(document.getElementById('regulKilometres').value.replace(',', '.'));
    }

    if (totalKilometres !== null) {
        for (i = num_first_day; i < nb_jour; i++) {
            if (parseFloat(document.getElementById('kilometres[' + i + ']').value) > 0)
                heure += parseFloat(document.getElementById('kilometres[' + i + ']').value.replace(',', '.'));
        }
    }

    totalKilometres.textContent = heure + ' IK';

    if (heure != 0) {
        totalKilometres.classList.add('noNull')
    }
    else {
        totalKilometres.classList.remove('noNull')
    }
}

// Permet de mettre à jour le total de nombre d'indemnite de tt
function updateTotal_IndemniteTT(nb_jour, num_first_day) {
    total = 0;

    if (document.getElementById('regulIndemniteTT') && document.getElementById('regulIndemniteTT').value) {
        total += parseInt(document.getElementById('regulIndemniteTT').value);
    }

    for (i = num_first_day; i < nb_jour; i++) {
        checkbox_indemniteTT = document.getElementById('indemnite_tt[' + i + ']');
        if (checkbox_indemniteTT.checked === true) {
            total++;
        }
    }

    totalIndemniteTT = document.getElementById('totalIndemniteTT');
    totalIndemniteTT.textContent = total + ' TT';

    if (total != 0) {
        totalIndemniteTT.classList.add('noNull')
    }
    else {
        totalIndemniteTT.classList.remove('noNull')
    }
}





//
// Gestion du Full Screen
//

document.addEventListener('DOMContentLoaded', function () {
    const fullscreenButton = document.getElementById('fullScreen');
    const fullscreenContainer = document.getElementById('fullscreenContainer');
    const closefullscreenButton = document.getElementById('closeFullScreen');
    const tableau = document.getElementById('tablelines_fdt');

    fullscreenButton.addEventListener('click', function () {
        fullscreenContainer.style.display = '';
        $("#tableau").append(tableau);
    });

    closefullscreenButton.addEventListener('click', function () {
        fullscreenContainer.style.display = 'none';
        $(".div-table-responsive").append(tableau);
    });

    // Gestion de la non suppression du contenu des notes des jours anticipés
    let textareas = document.querySelectorAll("textarea.no-delete[name*='note']");
    let initialTexts = new Map();

    // Stockage du texte initial
    textareas.forEach(textarea => {
        initialTexts.set(textarea, textarea.value);

        textarea.addEventListener("input", function () {
            let initialText = initialTexts.get(textarea);
            
            // Vérifie si le texte initial a été modifié
            if (!textarea.value.startsWith(initialText)) {
                textarea.value = initialText + textarea.value.slice(initialText.length);
            }
        });
    });

    // Sur écoute des changements des inputs timeadded et time_heure_nuit
    $(document).on('input', 'input[id^="timeadded["], input[id^="time_heure_nuit["]', function () {
        var id = $(this).attr('id');
        var match = id.match(/^timeadded\[(\d+)\]\[(\d+)\]$|^time_heure_nuit\[(\d+)\]\[(\d+)\]$/);

        if (match) {
            var idw = match[1] || match[3]; // Récupérer idw (jour)
            var index = parseInt(match[2] || match[4]); // Récupérer y (ligne)
            updateNextInput(idw, index);
        }
    });

    $(document).on('blur', 'input[id^="timeadded["], input[id^="time_heure_nuit["]', function () {
        var id = $(this).attr('id');
        var match = id.match(/^timeadded\[(\d+)\]\[(\d+)\]$|^time_heure_nuit\[(\d+)\]\[(\d+)\]$/);
    
        if (match) {
            var idw = match[1] || match[3];
            var index = parseInt(match[2] || match[4]);
            
            // Petite pause pour laisser le script de formatage s'exécuter
            setTimeout(() => updateNextInput(idw, index), 50);
        }
    });

    // Sur écoute des changements du select fk_task (format fk_task_x_y)
    $(document).on('change', 'select[id^="fk_task_"]', function () {
        var id = $(this).attr('id');
        var match = id.match(/^fk_task_(\d+)_(\d+)$/);

        if (match) {
            var idw = match[1]; // Jour (x)
            var index = parseInt(match[2]); // Ligne (y)
            updateNextInput(idw, index);
        }
    });
});

function updateNextInput(idw, index) {
    var timeInput = $(`[id='timeadded[${idw}][${index}]']`);
    var nightInput = $(`[id='time_heure_nuit[${idw}][${index}]']`);
    var fkTaskSelect = $(`#fk_task_${idw}_${index}`);
    var siteInput = $(`[id='site[${idw}][${index}]']`);

    var nextIndex = index + 1;
    var nextTimeInput = $(`[id='timeadded[${idw}][${nextIndex}]']`);
    var nextNightInput = $(`[id='time_heure_nuit[${idw}][${nextIndex}]']`);
    var nextFkTask = $(`#fk_task_${idw}_${nextIndex}`);
    var nextSsiteInput = $(`[id='site[${idw}][${nextIndex}]']`);

    // Vérifier si les éléments existent
    if (!fkTaskSelect.length || !timeInput.length || !nightInput.length) return;

    var fkTaskValue = fkTaskSelect.val()?.trim() || "";
    var timeValue = timeInput.val()?.trim() || "";
    var nightValue = nightInput.val()?.trim() || "";

    // La ligne suivante est activée SEULEMENT SI une tâche est sélectionnée ET qu'il y a des heures (jour ou nuit)
    var isCurrentFilled = fkTaskValue !== "" && fkTaskValue != 0 && (timeValue !== "" || nightValue !== "");

    if (isCurrentFilled) {
        nextTimeInput.prop('disabled', false);
        nextNightInput.prop('disabled', false);
        nextFkTask.prop('disabled', false);
        nextSsiteInput.prop('disabled', false);
    } else {
        nextTimeInput.prop('disabled', true).val("");
        nextNightInput.prop('disabled', true).val("");
        nextFkTask.prop('disabled', true).val("");
        nextSsiteInput.prop('disabled', true).val("");
    }
}

// Define a function named time_convert with parameter num
function time_convert(num) {
    // Calculate the number of hours by dividing num by 60 and rounding down
    var hours = Math.floor(num / 60);

    // Calculate the remaining minutes by taking the remainder when dividing num by 60
    var minutes = (String)(num % 60);
    minutes = minutes.padStart(2, '0');

    // Return the result as a string in the format "hours:minutes"
    return hours + ":" + minutes;
}

function isFilled(value) {
    return value !== null && value !== '' && value !== '0' && value !== '0.00' && value !== '0:00' && value !== '00:00';
}

function hasValidationErrors() {
    const errors = [];

    const timeaddedInputs = document.querySelectorAll('[id^="timeadded["]');

    timeaddedInputs.forEach(input => {
        const id = input.id;
        const match = id.match(/^timeadded\[(\d+)\]\[(\d+)\]$/);
        if (!match) return;

        const i = match[1];
        const j = match[2];

        const timeadded = document.getElementById(`timeadded[${i}][${j}]`)?.value || '';
        const timeNuit = document.getElementById(`time_heure_nuit[${i}][${j}]`)?.value || '';
        const fkTask = document.getElementById(`fk_task_${i}_${j}`)?.value || '';
        const siteInput = document.getElementById(`site[${i}][${j}]`);
        const site = siteInput?.value || '';

        if ((isFilled(timeadded) || isFilled(timeNuit)) && isFilled(fkTask) && !isFilled(site)) {        
            errors.push(`Ligne [${i}][${j}] : le champ "site" est requis si des heures sont saisies.`);
            if (siteInput) siteInput.classList.add('input-error');
        }
    });

    if (errors.length > 0) {
        //alert("Erreur de validation :\n" + errors.join('\n'));
        alert("Erreur de validation :\n" + "Si des heures sont pointées, il est obligatoire de renseigner la colonne 'Site'");
        return true;
    }

    return false;
}


$(document).ready(function () {
    const selectedTasks = {};
    const fuserid = $('#fuserid').val();

    $('.select-task').each(function () {
        const $select = $(this);
        const selectedId = $select.data('selected');
        const selectId = $select.attr('id'); // Utiliser l'id du select pour l'associer à selectedtask

        // Initialisation de Select2 avec Ajax
        $select.select2({
            ajax: {
                url: './ajax/get_tasks.php', 
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        fuserid: fuserid
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(task => ({
                            id: task.id,
                            text: task.ref,
                            disabled: task.disabled || false
                        }))
                    };
                },
                cache: true
            },
            placeholder: 'Sélectionner une tâche',
            minimumInputLength: 1,
            language: {
                inputTooShort: function () {
                    return 'Tapez au moins un caractère pour commencer la recherche';
                },
                noResults: function () {
                    return 'Aucun résultat trouvé';
                },
                searching: function () {
                    return 'Recherche en cours...';
                },
                loadingMore: function () {
                    return 'Chargement de plus de résultats...';
                }
            }
        });

        // Si une valeur est pré-sélectionnée, on la charge manuellement (car Select2 n’a pas encore les données en Ajax)
        if (selectedId) {
            $.ajax({
                type: 'GET',
                url: './ajax/get_tasks.php', 
                data: { 
                    task_id: selectedId,
                    fuserid: fuserid
                },
                dataType: 'json'
            }).then(function (data) {
                const task = data[0]; // on suppose que le résultat est un tableau [{id, ref}]
                const option = new Option(task.ref, task.id, true, true);
                $select.append(option).trigger('change'); // Ajoute l'option et informe Select2 du changement
            });
        }

        // Lorsqu'un changement se produit dans la sélection
        $select.on('change', function () {
            const selectedValue = $(this).val();
            selectedTasks[selectId] = selectedValue; // Enregistrer l'ID du task dans selectedTasks

            selectedtask = selectedValue;
        });

        // Lorsque le select2 est ouvert, on force la recherche sur l'élément pré-sélectionné
        $select.on('select2:open', function () {
            const selectedtask = selectedTasks[selectId]; // Récupérer la valeur spécifique à ce select
            if (selectedtask) {
                // Trouver l'élément pré-sélectionné dans la liste des options
                const selectedOption = $select.find(`option[value="${selectedtask}"]`);
                const selectedText = selectedOption.text(); // Le label de l'élément pré-sélectionné

                // Utiliser la méthode `trigger` pour envoyer le texte de la recherche
                if (selectedText) {
                    // Simuler la saisie du texte du label pour activer la recherche
                    $select.data('select2').dropdown.$search.val(selectedText).trigger('input');
                }
            }
        });
    });

    // Soumission globale du formulaire
    const form = document.forms['addtime'];
    if (form) {
        form.addEventListener('submit', function (e) {
            if (hasValidationErrors()) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Intercepter le click des boutons pour bloquer leur onclick si erreur
    const buttons = document.querySelectorAll('input[name="save"], input[name="transmettre"]');
    buttons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (hasValidationErrors()) {
                e.stopImmediatePropagation(); // bloque les autres onclick
                e.preventDefault(); // empêche la soumission
                return false;
            }
        }, true);
    });

    // Suppression de l'erreur sur l'input site lorsqu'on saisie 
    document.querySelectorAll('[id^="site["]').forEach(siteInput => {
        siteInput.addEventListener('input', function () {
            if (this.classList.contains('input-error') && this.value.trim() !== '') {
                this.classList.remove('input-error');
            }
        });
    });
});



