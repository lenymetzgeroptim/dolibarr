<?php
/**
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */
?>
<style>
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

    <div id="ganttTabs" class="tabsContainer">
        <!-- <div id="fixedHeader"></div> -->
        <div class="" style="width:10px;">
            <div class="" style="margin: 0 !important">
                <!-- <a class="tab inline-block valignmiddle"> -->
                    <span class="fas fa-users imgTabTitle infobox-adherent"></span> 
                <!-- </a> -->
            </div>
        </div>
        <div class="inline-block tabsElem">
            <a id="gant5" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : absence">
                <div class="tab1 tabunactive" style="margin: 0 !important">
                        <span class="fas fa-chart-gantt"></span> Collaborateurs : abs - Projets
                </div>
            </a>
        </div>
        <?php if ($user->rights->propal->lire && $user->rights->commande->lire) { ?>
            <div class="inline-block tabsElem">
            <a id="gant6" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Projet : Collaborateurs - absence">
                <div class="tab1 tabunactive" style="margin: 0 !important">
                        <span class="fas fa-chart-gantt"></span> Projet : Collaborateurs - absence 
                </div>
            </a>
        </div>
        
        <div class="inline-block tabsElem">
            <a id="gant1" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : devis & cde">
                <div class="tab1 tabunactive" style="margin: 0 !important">
                        <span class="fas fa-chart-gantt"></span> Collaborateurs : devis & cde
                </div>
            </a>
        </div>
        <div class="inline-block tabsElem">
            <a id="gant2" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : projets/devis">
                <div class="tab1 tabunactive" style="margin: 0 !important">
                        <span class="fas fa-chart-gantt"></span> Collaborateurs : projet/devis
                </div>
            </a>
        </div>
        <div class="inline-block tabsElem">
            <a id="gant3" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Collaborateurs : projets/devis">
                <div class="tab1 tabunactive" style="margin: 0 !important">
                        <span class="fas fa-chart-gantt"></span> Projet : Cde/devis - collaborateurs
                </div>
            </a>
        </div>
        <div class="inline-block tabsElem">
            <a id="gant4" class="tab inline-block valignmiddle" href="javascript:void(0);" title="Cde : collaborateurs - Projet">
                <div class="tab1 tabunactive" style="margin: 0 !important">
                        <span class="fas fa-chart-gantt"></span> Cde : collaborateurs - Projet
                </div>
            </a>
        </div>
      
        <?php } ?>
       
        


       
        <!-- Ligne contenant le titre et le toggle -->
        <div class="float-right">
            <span id="toggleAvailability" 
                style="margin-left: 25px; font-weight: bold; cursor: pointer; padding: 8px 14px; 
                        background: #e9ecef; border-radius: 20px; display: flex; align-items: center;
                        transition: background 0.3s ease-in-out;">
                
                <span id="availabilityIcon" class="fas fa-toggle-off" 
                    style="display: inline-flex; flex-direction: row-reverse; align-items: center; font-size: 28px; color: #ccc; transition: color 0.3s ease-in-out;margin-right: 5px;">
                    <span style="color: #495057; font-size: 16px; margin-left: 5px;">Disponibilité - Totalement libres&nbsp;</span>
                </span>


            </span>
        </div>
        <div class="float-right">
            <span id="toggleAvailabilityPartial" 
                style="margin-left: 5px; font-weight: bold; cursor: pointer; padding: 8px 14px; 
                        background: #e9ecef; border-radius: 20px; display: flex; align-items: center;
                        transition: background 0.3s ease-in-out;">
                
                <span id="availabilityIconPartial" class="fas fa-toggle-off" 
                style="display: inline-flex; flex-direction: row-reverse; align-items: center; font-size: 28px; color: #ccc; transition: color 0.3s ease-in-out;margin-right: 5px;">
                    <span style="color: #495057; font-size: 16px;">Uniquement sur projets ou Libres&nbsp;</span>
                </span>
            </span>
        </div>

            <!-- Filtres Date -->
            <div class="filter-container fondStyle" id="dateFilterContainer" style="display: none; margin-bottom: 10px;margin-left: auto;">
            <table class="nobordernopadding" style="margin: 0;">
                <tr class="valignmiddle">
                    <td class="nowraponall">
                        <label for="startDate"><strong>Période d'absence :</strong></label>
                    </td>
                    <td class="nowraponall" style="padding-left: 10px;">
                        <input type="date" id="startDate" class="flat input-date" title="Cliquez pour choisir une date de début">
                    </td>
                    <td class="nowraponall" style="padding: 0 5px;">➜</td>
                    <td class="nowraponall">
                        <input type="date" id="endDate" class="flat input-date" title="Cliquez pour choisir une date de fin">
                    </td>
                    <td class="nowraponall" style="padding-left: 10px;">
                        <i class="fas fa-undo-alt" id="resetDates" style="cursor: pointer;color:#0a64ad;" title="Réinitialiser les dates couvrant toutes les périodes de congés en cours ou à venir (hors congés terminés avant aujourd'hui)"></i>
                    </td>
                </tr>
            </table>
        </div>

        
        <div class="filter-container fondStyle" id="centerTodayContainer" style="margin-bottom: 10px; margin-left: auto;cursor: pointer;" title="Centrer le graphique sur aujourd'hui">
            <table class="nobordernopadding" style="margin: 0;">
                <tr class="valignmiddle">
                    <!-- <td class="nowraponall">
                        <label><strong>Centrer :</strong></label>
                    </td> -->
                    <td class="nowraponall" style="padding-left: 10px;">
                        <i class="fas fa-crosshairs" id="centerTodayBtn"
                        title="Centrer le graphique sur aujourd'hui"
                        style="cursor: pointer; color: #0a64ad;"></i>
                    </td>
                </tr>
            </table>
        </div>

            <!-- <div class="filter-container" id="periodeFilterContainer" style="display: none; margin-bottom: 10px;">
                <table class="nobordernopadding" style="margin: 0;">
                    <tr class="valignmiddle">
                        <td class="nowraponall">
                            <label for="startDate"><strong>Période d'absence :</strong></label>
                        </td>
                        <td class="nowraponall" style="padding-left: 10px;">
                            <input type="date" id="startDatePeriode" class="flat input-date">
                        </td>
                        <td class="nowraponall" style="padding: 0 5px;">➜</td>
                        <td class="nowraponall">
                            <input type="date" id="endDatePeriode" class="flat input-date">
                        </td>
                        <td class="nowraponall" style="padding-left: 10px;">
                            <i class="fas fa-undo-alt" id="resetPeriodes" style="cursor: pointer;" title="Réinitialiser les dates couvrant toutes les périodes des projets comprenant les congés"></i>
                        </td>
                    </tr>
                </table>
            </div> -->
            
            <!-- <button id="zoomIn" class="zoom-btn"><i class="fa fa-search-plus"></i></button>
            <button id="zoomOut" class="zoom-btn"><i class="fa fa-search-minus"></i></button> -->
            <!-- Conteneur Gantt -->
            <!-- <div id="ganttMode" style="display: none;"></div>
            <div id="ganttContainer" class="dolGanttContainer" style="margin-top: 10px;"></div>
        </div> -->
            
    </div>
    <hr>
      <div id="ganttMode" style="display: none;"></div>
    <div id="ganttContainer" class="dolGanttContainer" style="margin-top: 10px;"></div>
    <div id="tabs5" class="gantt" style="width: 80vw;">
        <div id="principal_content5" style="margin-left: 0;">
            <div  class="gantt" id="GanttChartDIV5"></div>
            <div id="loader" class="loader-overlay" style="display: none;float: right;">
                    <div class="spinner"></div>
                    <p>Chargement en cours...</p>
            </div>
        </div>
    </div>
    <div id="tabs" class="gantt" style="width: 80vw;">
        <div id="principal_content" style="margin-left: 0;">
            <div class="gantt" id="GanttChartDIV"></div>
            <div id="loader" class="loader-overlay" style="display: none;float: right;">
                    <div class="spinner"></div>
                    <p>Chargement en cours...</p>
            </div>
        </div>
    </div>
    <div id="tabs2" class="gantt" style="width: 80vw;">
        <div id="principal_content2" style="margin-left: 0;">
            <div class="gantt" id="GanttChartDIV2"></div>
            <div id="loader" class="loader-overlay" style="display: none;float: right;">
                    <div class="spinner"></div>
                    <p>Chargement en cours...</p>
            </div>
        </div>
    </div>
    <div id="tabs3" class="gantt" style="width: 80vw;">
        <div id="principal_content3" style="margin-left: 0;">
            <div  class="gantt" id="GanttChartDIV3"></div>
            <div id="loader" class="loader-overlay" style="display: none;float: right;">
                    <div class="spinner"></div>
                    <p>Chargement en cours...</p>
            </div>
        </div>
    </div>
    <div id="tabs4" class="gantt" style="width: 80vw;">
        <div id="principal_content4" style="margin-left: 0;">
            <div  class="gantt" id="GanttChartDIV4"></div>
            <div id="loader" class="loader-overlay" style="display: none;float: right;">
                    <div class="spinner"></div>
                    <p>Chargement en cours...</p>
            </div>
        </div>
    </div>
  
    <div id="tabs6" class="gantt" style="width: 80vw;">
        <div id="principal_content6" style="margin-left: 0;">
            <div  class="gantt" id="GanttChartDIV6"></div>
            <div id="loader" class="loader-overlay" style="display: none;float: right;">
                    <div class="spinner"></div>
                    <p>Chargement en cours...</p>
            </div>
        </div>
        
    </div>
    
<?php
