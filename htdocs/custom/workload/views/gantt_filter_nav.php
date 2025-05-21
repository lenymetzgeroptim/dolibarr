<?php

?>
<style>
/* Conteneur des filtres */
.container.filter_menu_wrapper {
    /* margin: 20px 0;
    padding: 10px; */
    background: #f8f8f8;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

/* Table des filtres */
.table_filter {
    width: 100%;
    table-layout: fixed; /* Fixe la largeur des colonnes */
    border-collapse: collapse;
}

/* Colonnes des filtres */
.td_filter {
    width: 33%;
    padding: 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}

/* Contenu des cellules */
.td_filter div {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Icônes */
.pictofixedwidth {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Sélecteurs */
select {
    flex: 1;
    min-width: 150px;
    max-width: 100%;
    height: 34px;
    padding: 5px 10px;
    font-size: 14px;
    color: #333;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    appearance: none;
    transition: border 0.2s ease-in-out;
}

select:hover {
    border-color: #0073e6;
}

select:focus {
    border-color: #0056b3;
    box-shadow: 0 0 4px rgba(0, 115, 230, 0.5);
    outline: none;
}

/* Responsive pour tablettes */
@media (max-width: 992px) {
    .td_filter {
        width: 50%; /* 2 colonnes */
    }

    .table_filter tr {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .td_filter {
        flex: 1 1 calc(50% - 10px);
        text-align: left;
    }
}

/* Responsive pour mobiles */
@media (max-width: 768px) {
    .td_filter {
        width: 100%; /* 1 colonne */
        flex: 1 1 100%;
    }

    .table_filter tr {
        flex-direction: column;
    }

    .td_filter div {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    select {
        width: 100%;
    }
}


/* Partie navigation  */
 /* Conteneur des onglets */
 .tabsContainer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    /* justify-content: center; */
    gap: 16px;
    padding: 10px 15px;
    background: #f8f8f8;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    max-width: 100%;
    overflow: hidden;
}

/* Chaque onglet */
.tabsElem {
    display: flex;
    align-items: center;
    min-width: 160px;
    flex-grow: 1;
    max-width: fit-content;
}

/* Style de l’onglet */
.tab1 {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 8px 10px;
    border-radius: 6px;
    background: #fff;
    transition: background 0.2s, box-shadow 0.2s;
    box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
}

.tab1:hover {
    background: #e0e0e0;
    box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.15);
    padding: 8px 10px;
}

/* Liens à l’intérieur des onglets */
.tab1 a {
    text-decoration: none;
    color: #333;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
    font-weight: bold;
}

/* Ombre sur les icônes */
.tab1 span.fas {
    font-size: 16px;
    color: #0a64ad;;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
}

/* Ajustement du dernier élément */
.tabsElem:last-child {
    margin-left: auto;
}

.inline-block {
    padding:1px!important;
}

/* Loader */
.loader-overlay {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 5px;
    background: rgba(255, 255, 255, 0.8);
    padding: 10px;
    border-radius: 6px;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid #007bff;
    border-top: 3px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}


@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}


.fondStyle {
    background: linear-gradient(to right, #f8f8f8, #f1f1f1);
    border: 1px solid #d0d0d0;
    border-left: 4px solid #0a64ad; 
    border-radius: 6px;
    padding: 4px 7px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
    display: inline-block;
}

</style>


<?php


print '<div id="fichecenter" class="fichecenter">';
print '<div class="container filter_menu_wrapper">';

print '<table class="table_filter noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3" style="text-align: left;">';
print '<span class="fas fa-search-plus" style="margin-right: 8px;opacity:0.3;"></span>';
print $langs->trans("Recherche avancée");
print '<span class="fas fa-sync-alt" id="resetFiltersBtn" title="Réinitialiser les filtres"';
print ' style="position:relative; float:right; top: 40%; transform: translateY(-50%); cursor: pointer; color: #0a64ad;"></span>';
print '</td>';
print '</tr>';
// Ligne 4 : Responsables projet, Responsable antenne
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Responsables de projet") . ' &nbsp;';
print img_picto('', 'user', 'class="pictofixedwidth"');
print '<select id="respProjFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Rattachement agence") . ' &nbsp;';
print img_picto('', 'user', 'class="pictofixedwidth"');
print '<select id="resAntFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Agence associée affaire") . ' &nbsp;';
print img_picto('', 'building', 'class="pictofixedwidth"');
print '<select id="agenceFilter" multiple></select>';
print '</div>';
print '</td>';
print '<tr>';

// Ligne 1 : Salariés, Emploi, Compétence
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Salariés") . ' &nbsp;';
print img_picto('', 'user', 'class="pictofixedwidth"');
print '<select id="userFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Emplois") . ' &nbsp;';
print img_picto('', 'skill', 'class="pictofixedwidth"');
print '<select id="jobFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Compétences") . ' &nbsp;';
print img_picto('', 'skill', 'class="pictofixedwidth"');
print '<select id="skillFilter" multiple></select>';
print '</div>';
print '</td>';
print '</tr>';


// Ligne 3 : Groupes, Agences, Domaines
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Groupes") . ' &nbsp;';
print img_picto('', 'group', 'class="pictofixedwidth"');
print '<select id="groupFilter" multiple></select>';
print '</div>';
print '</td>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Domaines") . ' &nbsp;';
print img_picto('', 'building', 'class="pictofixedwidth"');
print '<select id="domFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Type de Congé") . ' &nbsp;';
print img_picto('', 'list', 'class="pictofixedwidth"');
print '<select id="absFilter" multiple></select>';
print '</div>';
print '</tr>';

// Ligne 2 : Projets, Commandes, Devis
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Projets") . ' &nbsp;';
print img_picto('', 'project', 'class="pictofixedwidth"');
print '<select id="projectFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Commandes") . ' &nbsp;';
print img_picto('', 'order', 'class="pictofixedwidth"');
print '<select id="orderFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Devis") . ' &nbsp;';
print img_picto('', 'propal', 'class="pictofixedwidth"');
print '<select id="propalFilter" multiple></select>';
print '</div>';
print '</td>';
print '</tr>';


print '</table>';
print '</div>'; // Fin de container
// print '</div>'; // Fin de fichecenter

print '<div id="ganttTabs" class="tabsContainer">';
print '<div class="" style="width:10px;">';
print '<div class="" style="margin: 0 !important">';
print '<span class="fas fa-users imgTabTitle infobox-adherent"></span>';
print '</div>';
print '</div>';

print '<div class="inline-block tabsElem">';
print '<a id="gant5" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : absence">';
print '<div class="tab1 tabunactive" style="margin: 0 !important">';
print '<span class="fas fa-chart-gantt"></span> Collaborateurs : abs - Projets';
print '</div>';
print '</a>';
print '</div>';

if ($user->rights->propal->lire && $user->rights->commande->lire) {
    print '<div class="inline-block tabsElem">';
    print '<a id="gant6" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Projet : Collaborateurs - absence">';
    print '<div class="tab1 tabunactive" style="margin: 0 !important">';
    print '<span class="fas fa-chart-gantt"></span> Projet : Collaborateurs - absence';
    print '</div>';
    print '</a>';
    print '</div>';

    print '<div class="inline-block tabsElem">';
    print '<a id="gant1" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : devis & cde">';
    print '<div class="tab1 tabunactive" style="margin: 0 !important">';
    print '<span class="fas fa-chart-gantt"></span> Collaborateurs : devis & cde';
    print '</div>';
    print '</a>';
    print '</div>';

    print '<div class="inline-block tabsElem">';
    print '<a id="gant2" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : projets/devis">';
    print '<div class="tab1 tabunactive" style="margin: 0 !important">';
    print '<span class="fas fa-chart-gantt"></span> Collaborateurs : projet/devis';
    print '</div>';
    print '</a>';
    print '</div>';

    print '<div class="inline-block tabsElem">';
    print '<a id="gant3" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : projets/devis">';
    print '<div class="tab1 tabunactive" style="margin: 0 !important">';
    print '<span class="fas fa-chart-gantt"></span> Projet : Cde/devis - collaborateurs';
    print '</div>';
    print '</a>';
    print '</div>';

    print '<div class="inline-block tabsElem">';
    print '<a id="gant4" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Cde : collaborateurs - Projet">';
    print '<div class="tab1 tabunactive" style="margin: 0 !important">';
    print '<span class="fas fa-chart-gantt"></span> Cde : collaborateurs - Projet';
    print '</div>';
    print '</a>';
    print '</div>';
}

print '<div class="float-right">';
print '<span id="toggleAvailability" style="margin-left: 25px; font-weight: bold; cursor: pointer; padding: 8px 14px; background: #e9ecef; border-radius: 20px; display: flex; align-items: center; transition: background 0.3s ease-in-out;">';
print '<span id="availabilityIcon" class="fas fa-toggle-off" style="display: inline-flex; flex-direction: row-reverse; align-items: center; font-size: 28px; color: #ccc; transition: color 0.3s ease-in-out;margin-right: 5px;">';
print '<span style="color: #495057; font-size: 16px; margin-left: 5px;">Disponibilité - Totalement libres&nbsp;</span>';
print '</span>';
print '</span>';
print '</div>';

print '<div class="float-right">';
print '<span id="toggleAvailabilityPartial" style="margin-left: 5px; font-weight: bold; cursor: pointer; padding: 8px 14px; background: #e9ecef; border-radius: 20px; display: flex; align-items: center; transition: background 0.3s ease-in-out;">';
print '<span id="availabilityIconPartial" class="fas fa-toggle-off" style="display: inline-flex; flex-direction: row-reverse; align-items: center; font-size: 28px; color: #ccc; transition: color 0.3s ease-in-out;margin-right: 5px;">';
print '<span style="color: #495057; font-size: 16px;">Uniquement sur projets ou Libres&nbsp;</span>';
print '</span>';
print '</span>';
print '</div>';

print '<div class="filter-container fondStyle" id="dateFilterContainer" style="display: none; margin-bottom: 10px;margin-left: auto;">';
print '<table class="nobordernopadding" style="margin: 0;">';
print '<tr class="valignmiddle">';
print '<td class="nowraponall"><label for="startDate"><strong>Période d\'absence :</strong></label></td>';
print '<td class="nowraponall" style="padding-left: 10px;"><input type="date" id="startDate" class="flat input-date" title="Cliquez pour choisir une date de début"></td>';
print '<td class="nowraponall" style="padding: 0 5px;">➜</td>';
print '<td class="nowraponall"><input type="date" id="endDate" class="flat input-date" title="Cliquez pour choisir une date de fin"></td>';
print '<td class="nowraponall" style="padding-left: 10px;"><i class="fas fa-undo-alt" id="resetDates" style="cursor: pointer;color:#0a64ad;" title="Réinitialiser les dates couvrant toutes les périodes de congés en cours ou à venir (hors congés terminés avant aujourd\'hui)"></i></td>';
print '</tr>';
print '</table>';
print '</div>';

print '<div class="filter-container fondStyle" style="display: none; margin-bottom: 10px;margin-left: auto;"></div>';

print '<div class="filter-container fondStyle" id="centerTodayContainer" title="Centrer le graphique sur aujourd\'hui" style="position: fixed;top: 83%; right: 5px; transform: translateY(-50%); cursor: pointer; background: white; padding: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.3); border-radius: 5px; z-index: 9999;">';
print '<table class="nobordernopadding" style="margin: 0;"><tbody><tr class="valignmiddle">';
print '<td class="nowraponall" style="padding-left: 5px;"><i class="fas fa-crosshairs" id="centerTodayBtn" title="Centrer le graphique sur aujourd\'hui" style="cursor: pointer; color: #0a64ad;"></i></td>';
print '</tr></tbody></table>';
print '</div>';
print '</div>';


print '<style>
.gantt { display: none; }
#tabs5 { display: block !important; }
</style>';

print '<hr>';
print '<div id="ganttMode" style="display: none;"></div>';
print '<div id="ganttContainer" class="dolGanttContainer" style="margin-top: 10px;"></div>';

$blocks = [
  ['tabs5', 'principal_content5', 'GanttChartDIV5', 'loader', 'block'], 
  ['tabs', 'principal_content', 'GanttChartDIV', 'loader', 'none'],
  ['tabs2', 'principal_content2', 'GanttChartDIV2', 'loader', 'none'],
  ['tabs3', 'principal_content3', 'GanttChartDIV3', 'loader', 'none'],
  ['tabs4', 'principal_content4', 'GanttChartDIV4', 'loader', 'none'],
  ['tabs6', 'principal_content6', 'GanttChartDIV6', 'loader', 'none']
];

foreach ($blocks as $block) {
    [$tabId, $contentId, $chartId, $loaderId, $display] = $block;
    print '<div id="'.$tabId.'" class="gantt" style="width: 80vw; display: '.$display.';">';
    print '<div id="'.$contentId.'" style="margin-left: 0;">';
    print '<div class="gantt" id="'.$chartId.'"></div>';
    print '<div id="'.$loaderId.'" class="loader-overlay" style="display: none; float: right;">';
    print '<div class="spinner"></div><p>Chargement en cours...</p>';
    print '</div></div></div>';
}



// print '<hr>';
// print '<div id="ganttMode" style="display: none;"></div>';
// print '<div id="ganttContainer" class="dolGanttContainer" style="margin-top: 10px;"></div>';

// print '<div id="tabs5" class="gantt" style="width: 80vw;">';
// print '<div id="principal_content5" style="margin-left: 0;">';
// print '<div class="gantt" id="GanttChartDIV5"></div>';
// print '<div id="loader" class="loader-overlay" style="display: none;float: right;">';
// print '<div class="spinner"></div>';
// print '<p>Chargement en cours...</p>';
// print '</div>';
// print '</div>';
// print '</div>';

// print '<div id="tabs" class="gantt" style="width: 80vw;">';
// print '<div id="principal_content" style="margin-left: 0;">';
// print '<div class="gantt" id="GanttChartDIV"></div>';
// print '<div id="loader" class="loader-overlay" style="display: none;float: right;">';
// print '<div class="spinner"></div>';
// print '<p>Chargement en cours...</p>';
// print '</div>';
// print '</div>';
// print '</div>';

// print '<div id="tabs2" class="gantt" style="width: 80vw;">';
// print '<div id="principal_content2" style="margin-left: 0;">';
// print '<div class="gantt" id="GanttChartDIV2"></div>';
// print '<div id="loader" class="loader-overlay" style="display: none;float: right;">';
// print '<div class="spinner"></div>';
// print '<p>Chargement en cours...</p>';
// print '</div>';
// print '</div>';
// print '</div>';

// print '<div id="tabs3" class="gantt" style="width: 80vw;">';
// print '<div id="principal_content3" style="margin-left: 0;">';
// print '<div class="gantt" id="GanttChartDIV3"></div>';
// print '<div id="loader" class="loader-overlay" style="display: none;float: right;">';
// print '<div class="spinner"></div>';
// print '<p>Chargement en cours...</p>';
// print '</div>';
// print '</div>';
// print '</div>';

// print '<div id="tabs4" class="gantt" style="width: 80vw;">';
// print '<div id="principal_content4" style="margin-left: 0;">';
// print '<div class="gantt" id="GanttChartDIV4"></div>';
// print '<div id="loader" class="loader-overlay" style="display: none;float: right;">';
// print '<div class="spinner"></div>';
// print '<p>Chargement en cours...</p>';
// print '</div>';
// print '</div>';
// print '</div>';

// print '<div id="tabs6" class="gantt" style="width: 80vw;">';
// print '<div id="principal_content6" style="margin-left: 0;">';
// print '<div class="gantt" id="GanttChartDIV6"></div>';
// print '<div id="loader" class="loader-overlay" style="display: none;float: right;">';
// print '<div class="spinner"></div>';
// print '<p>Chargement en cours...</p>';
// print '</div>';
// print '</div>';
// print '</div>';