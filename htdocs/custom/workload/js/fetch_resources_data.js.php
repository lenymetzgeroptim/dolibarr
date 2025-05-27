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
    .dolibarr-name {
        font-size: 1.2em;
        line-height: 1.4;
        font-family: arial,tahoma,verdana,helvetica;
        font-weight: 600;
        direction: ltr;
    }

    .loader-overlay {
        width: 50%;
        float: right;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center; 
    
    }

    /* Animation du spinner */
    .spinner {
        border: 8px solid #f3f3f3;
        border-top: 8px solid #3498db;
        border-radius: 50%; 
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

    /* Texte sous le spinner */
    .loader-overlay p {
        color: #fff;
        margin-top: 10px;
        font-size: 16px;
    }

    /* Animation du spinner */
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .gtasktable {
        table-layout: fixed; 
        width: 100%; 
    }

    .gtasktable th:first-child, .gtasktable td:first-child {
        width: 0%;
    }

    .gtasktable th:nth-child(2), .gtasktable td:nth-child(2) {
        width: 80%; 
    }

    .gtasktable th:nth-child(3), .gtasktable td:nth-child(3) {
        /* width: 10%; */
    }
    .gtaskname div {
        min-width: 100% !important;
    }

    .JSGanttToolTip[style*="visibility: hidden"] {
        display: none !important;
    }

    .gcomp {
        display: none;
    }

    #GanttChartDIVgchartbody, #GanttChartDIV2gchartbody, #GanttChartDIV3gchartbody, #GanttChartDIV4gchartbody, #GanttChartDIV5gchartbody, #GanttChartDIV6gchartbody {
        overflow-x: hidden; 
        overflow-y: hidden;
        width: 100%; 
        max-width: 100%;
        /* white-space: nowrap; */
    }

    /* .gchartlbl {
        overflow-x: hidden  ;
    } */

    .gchartcontainer {
        bottom: 23px;
        position: relative;
    }
  
    .gminorheading.holiday {
        background-color: red !important;
        color: white;
    }

    .gantt-holiday-line {
        position: absolute;
        top: 0;
        height: 100%;
        background-color: rgba(255, 0, 0, 0.3);
        z-index: 15;
        pointer-events: none;
    }


    .gantt-holiday-cell {
        background-color: rgba(255, 0, 0, 0.3) !important;
        opacity: 0.4;
    }

    /* .gantt-holiday-line-week {
        position: absolute;
        top: 0;
        height: 100%;
        background-color: rgba(255, 0, 0, 0.3);
        z-index: 15;
        pointer-events: none;
    } */


    .gantt-holiday-cell-week {
        background-color: rgba(255, 0, 0, 0.3) !important;
        opacity: 0.5;
    }
    

    #gant6 .gcharttableh tr{
        visibility: hidden!important;
    }

     /* Application sur FireFox Navigateur */
     #fixedHeader_GanttChartDIV6,
    #fixedHeader_GanttChartDIV5,
    #fixedHeader_GanttChartDIV4,
    #fixedHeader_GanttChartDIV3,
    #fixedHeader_GanttChartDIV2,
    #fixedHeader_GanttChartDIV {
        scrollbar-width: auto;            
        /* scrollbar-color: #006f99 #e6ecf1; */
        scrollbar-color: #8a6e3d #f2f0e5;
    }

    #fixedHeader_GanttChartDIV6::-webkit-scrollbar,
    #fixedHeader_GanttChartDIV5::-webkit-scrollbar,
    #fixedHeader_GanttChartDIV4::-webkit-scrollbar,
    #fixedHeader_GanttChartDIV3::-webkit-scrollbar,
    #fixedHeader_GanttChartDIV2::-webkit-scrollbar,
    #fixedHeader_GanttChartDIV::-webkit-scrollbar {
        height: 14px;
    }

    #fixedHeader_GanttChartDIV6::-webkit-scrollbar-track,
    #fixedHeader_GanttChartDIV5::-webkit-scrollbar-track,
    #fixedHeader_GanttChartDIV4::-webkit-scrollbar-track,
    #fixedHeader_GanttChartDIV3::-webkit-scrollbar-track,
    #fixedHeader_GanttChartDIV2::-webkit-scrollbar-track,
    #fixedHeader_GanttChartDIV::-webkit-scrollbar-track {
        background: #f2f0e5;
        border-radius: 10px;
    }

    #fixedHeader_GanttChartDIV6::-webkit-scrollbar-thumb,
    #fixedHeader_GanttChartDIV5::-webkit-scrollbar-thumb,
    #fixedHeader_GanttChartDIV4::-webkit-scrollbar-thumb,
    #fixedHeader_GanttChartDIV3::-webkit-scrollbar-thumb,
    #fixedHeader_GanttChartDIV2::-webkit-scrollbar-thumb,
    #fixedHeader_GanttChartDIV::-webkit-scrollbar-thumb {
        background: #8a6e3d;
        border-radius: 10px;
    }


    .fondStyle.red-left-border {
        border-left: 4px solid red !important;
    }

    .fondStyle.blue-left-border {
        border-left: 4px solid #0a64ad !important;
    }

     /* Pour agrandire le Gantt */
   div.fiche {
    margin-left:5px!important;
    margin-right:5px!important;
   }
    /* Largeur normale (écran >1600px) */
    #tabs, #tabs2, #tabs3, #tabs4, #tabs5, #tabs6 {
        width: 85vw!important;
        max-width: 100%;
        /* margin: auto; */
        transition: width 0.3s ease;
    }

    body {
        overflow-x: hidden;
    }
   
    /* Écrans moyens */
    @media (max-width: 1600px) {
        #tabs, #tabs2, #tabs3, #tabs4, #tabs5, #tabs6 {
            width: 82vw!important;;
        }
    }

    @media (max-width: 1400px) {
        #tabs, #tabs2, #tabs3, #tabs4, #tabs5, #tabs6 {
            width: 80vw!important;;
        }
    }

    @media (max-width: 1180px) {
        #tabs, #tabs2, #tabs3, #tabs4, #tabs5, #tabs6 {
            width: 70vw!important;;
        }
        .gdur {
            display: none;
        } 
        
        .gstartdate, .genddate {
            width:80px;
            font-size: 12px !important;
        }
    }

    @media (max-width: 1020px) {
        #tabs, #tabs2, #tabs3, #tabs4, #tabs5, #tabs6 {
            width: 90vw!important;
        }
    }

    /* Tablettes */
    @media (max-width: 768px) {
        #tabs, #tabs2, #tabs3, #tabs4, #tabs5, #tabs6 {
            width: 80vw!important;
        }  
        .gdur {
            display: none;
        } 

        .gstartdate, .genddate {
            width:60px;
            font-size: 10px !important;
        }

        #centerTodayContainer {
            padding:1px;
        }
    }

    /* Smartphones */
    @media (max-width: 510px) {
        #tabs, #tabs2, #tabs3, #tabs4, #tabs5, #tabs6 {
            width: 48vw!important;
            font-size: 0.85em;
        }

        .gdur {
            display: none;
        }
        .gstartdate, .genddate {
            /* width:60px;
            font-size: 10px !important; */
            display:none!important;
        }

        .gmainleft{
            flex: 0 0 35%;
            font-size: 0.85em;
        }

        #centerTodayContainer {
            padding:1px;
        }
    }


</style>

<script>

// Déclaration globale initiale, vide


// Récupération des données principale
// function fetchData(lazy = false) {

//     return new Promise((resolve, reject) => {
//         const data = lazy ? { limit: 100 } : {};

//         $.ajax({
//             url: '/custom/workload/ajax/workload_data.php',
//             type: 'GET',
//             dataType: 'json',
//             data: data,
//             cache: false,
//             success: function(response) {
//                 // Assignation aux variables globales
//                 ressources = response.res;
//                 ressourcesProj = response.resProj;
//                 ressourcesComm = response.resComm;
//                 ressourcesAbs = response.resAbs;
//                 ressourcesProjAbs = response.resProjAbs;

//                 resolve({
//                     ressources,
//                     ressourcesProj,
//                     ressourcesComm,
//                     ressourcesAbs,
//                     ressourcesProjAbs
//                 });
//             },
//             error: function(xhr, status, error) {
//                 console.error("Erreur AJAX :", error);
//                 reject({ message: "Erreur lors de l'appel AJAX", status, error });
//             }
//         });
//     });
// }

// // Récupération des données filtres
// function fetchFilterData(lazy = false) {
//     return new Promise((resolve, reject) => {
//         const datatofilter = lazy ? { limit: 100 } : {};

//         $.ajax({
//             url: '/custom/workload/ajax/filter_data.php',
//             type: 'GET',
//             dataType: 'json',
//             data: datatofilter,
//             cache: false,
//             success: function(responsefilter) {
//                 if (
//                     responsefilter.dataUsers && Array.isArray(responsefilter.dataUsers) &&
//                     responsefilter.dataJobs && Array.isArray(responsefilter.dataJobs) &&
//                     responsefilter.dataGroups && Array.isArray(responsefilter.dataGroups) &&
//                     responsefilter.dataRespProj && Array.isArray(responsefilter.dataRespProj) &&
//                     responsefilter.dataProjects && Array.isArray(responsefilter.dataProjects) &&
//                     responsefilter.dataOrders && Array.isArray(responsefilter.dataOrders) &&
//                     responsefilter.dataSkills && Array.isArray(responsefilter.dataSkills) &&
//                     responsefilter.dataAgencies && Array.isArray(responsefilter.dataAgencies) &&
//                     responsefilter.dataPropals && Array.isArray(responsefilter.dataPropals) &&
//                     responsefilter.dataAbsType && Array.isArray(responsefilter.dataAbsType)
//                 ) {
//                     populateSelect("#jobFilter", responsefilter.dataJobs, "job_id", "job_label");
//                     populateSelect("#userFilter", responsefilter.dataUsers, "id", "fullname");
//                     populateSelect("#groupFilter", responsefilter.dataGroups, "group_id", "nom");
//                     populateSelect("#respProjFilter", responsefilter.dataRespProj, "id", "fullname");
//                     populateSelect("#projectFilter", responsefilter.dataProjects, "fk_projet", "nom");
//                     populateSelect("#orderFilter", responsefilter.dataOrders, "order_id", "nom");
//                     populateSelect("#skillFilter", responsefilter.dataSkills, "skillid", "label");
//                     populateSelect("#agenceFilter", responsefilter.dataAgencies, "socid", "name_alias");
//                     populateSelect("#propalFilter", responsefilter.dataPropals, "propal_id", "nom");
//                     populateSelect("#absFilter", responsefilter.dataAbsType, "fk_type", "conge_label");
//                     populateSelect("#resAntFilter", responsefilter.dataAgencies, "socid", "name_alias");
//                     populateSelect("#domFilter", responsefilter.dataProjects, "domaine", "domaine");

//                     resolve(responsefilter);
//                 } else {
//                     console.warn("Réponse non valide :", responsefilter);
//                     reject("Données des filtres invalides reçues.");
//                 }
//             },
//             error: function(xhr, status, error) {
//                 console.error("Erreur AJAX :", error);
//                 reject({ message: "Erreur lors de l'appel AJAX", status, error });
//             }
//         });
//     });
// }

// function centerScrollOnAllLines5(lineElement5, fixedHeader) {
//     const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV5');
     
//  if(fixedHeader !== fixedHeader5) return;
  
//     if (lineElement5 && fixedHeader) {
//         let linePosition = lineElement5.offsetLeft;
//         let scrollCenter = linePosition - 60;
//         fixedHeader.scrollLeft = scrollCenter;
//     }

 
// }

// function centerScrollOnAllLines4(lineElement5, fixedHeader) {
//     const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV4');
  
//     if(fixedHeader !== fixedHeader5) return;

//     if (lineElement5 && fixedHeader) {
//     let linePosition = lineElement5.offsetLeft;
//     let scrollCenter = linePosition - 60;
//     fixedHeader.scrollLeft = scrollCenter;
//     }

// }

// function centerScrollOnAllLines3(lineElement5, fixedHeader) {
//     const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV3');
     
//  if(fixedHeader !== fixedHeader5) return;
  
//     if (lineElement5 && fixedHeader) {
//         let linePosition = lineElement5.offsetLeft;
//         let scrollCenter = linePosition - 60;
//         fixedHeader.scrollLeft = scrollCenter;
//     }

 
// }

// function centerScrollOnAllLines2(lineElement5, fixedHeader) {
//     const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV2');
  
//     if(fixedHeader !== fixedHeader5) return;

//     if (lineElement5 && fixedHeader) {
//     let linePosition = lineElement5.offsetLeft;
//     let scrollCenter = linePosition - 60;
//     fixedHeader.scrollLeft = scrollCenter;
//     }

// }

// function centerScrollOnAllLines1(lineElement5, fixedHeader) {
//     const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV');
     
//  if(fixedHeader !== fixedHeader5) return;
  
//     if (lineElement5 && fixedHeader) {
//         let linePosition = lineElement5.offsetLeft;
//         let scrollCenter = linePosition - 60;
//         fixedHeader.scrollLeft = scrollCenter;
//     }

 
// }

// function centerScrollOnAllLines6(lineElement5, fixedHeader) {
//     const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV6');
  
//     if(fixedHeader !== fixedHeader5) return;

//     if (lineElement5 && fixedHeader) {
//     let linePosition = lineElement5.offsetLeft;
//     let scrollCenter = linePosition - 60;
//     fixedHeader.scrollLeft = scrollCenter;
//     }

// }

// function observeUntilReady(elementIds, callback) {
//     const observer = new MutationObserver(() => {
//         const elements = elementIds.map(id => document.getElementById(id));
//         const allFound = elements.every(el => el !== null);

//         if (allFound) {
//             callback(...elements);
//             observer.disconnect();
//         }
//     });

//     observer.observe(document.body, { childList: true, subtree: true });
// }


// document.addEventListener("DOMContentLoaded", function () {
    
//     function addFixedHeader() {
//         let ganttMapping = {
//             "gant1": "GanttChartDIV",
//             "gant2": "GanttChartDIV2",
//             "gant3": "GanttChartDIV3",
//             "gant4": "GanttChartDIV4",
//             "gant5": "GanttChartDIV5",
//             "gant6": "GanttChartDIV6"
//         };

//         let activeTab = document.querySelector('.tabsElem.tabactive a');
//         if (!activeTab) {
//             console.warn("Aucun onglet actif trouvé !");
//             return;
//         }

//         let activeTabId = activeTab.id;
//         let ganttId = ganttMapping[activeTabId];
//         if (!ganttId) {
//             console.warn("Aucun Gantt correspondant trouvé pour l'onglet :", activeTabId);
//             return;
//         }

//         let ganttContainer = document.getElementById(ganttId);
//         let ganttTable = document.getElementById(ganttId + "chartTable");
//         let ganttBody = document.getElementById(ganttId + "gchartbody");
//         let ganttHead = document.getElementById(ganttId + "gcharthead");

//         if (!ganttContainer || !ganttTable || !ganttBody || !ganttHead) {
//             console.warn("Élément(s) introuvable(s) !");
//             return;
//         }
//         if (document.getElementById("fixedHeader_" + ganttId)) return;

//         let fixedHeader = ganttHead.cloneNode(true);
//         fixedHeader.id = "fixedHeader_" + ganttId;
//         fixedHeader.style.position = "fixed";
//         fixedHeader.style.bottom = "25px";
//         fixedHeader.style.width = "41.99%";
//         fixedHeader.style.background = "white";
//         fixedHeader.style.zIndex = "1000";
//         fixedHeader.style.borderBottomRightRadius = "10px";
//         fixedHeader.style.borderTopRightRadius = "10px";
//         fixedHeader.style.overflowY = "hidden";
//         fixedHeader.style.display = "block";
//         fixedHeader.style.textAlign = "center";
//         fixedHeader.style.float = "right";
//         // fixedHeader.style.height = "10" - ganttHead.clientHeight + "px";
//         // fixedHeader.style.height = ganttHead.clientHeight + "px";
//         // fixedHeader.style.paddingTop = "10px";
//         fixedHeader.style.overflowX = "auto";
//         document.body.appendChild(fixedHeader);
        

//         ganttTable.insertBefore(fixedHeader, ganttTable.firstChild);

      
//         // Synchroniser le scrollLeft du ganttHead avec le fixedHeader et verse versa
//         function syncScroll() {
//             ganttHead.scrollLeft = fixedHeader.scrollLeft;
//             fixedHeader.scrollLeft = ganttHead.scrollLeft;
//         }

//         // Écoute du défilement du ganttHead
//         ganttHead.addEventListener("scroll", function () {
//             syncScroll();
//         });

//         // Écoute du défilement du fixedHeader
//         fixedHeader.addEventListener("scroll", function () {
//             syncScroll();
//         });
        
//         // Centrer le graphique par rapport à la date d'aujourd'hui
//         const lineElement5 = document.getElementById('GanttChartDIV5line1');
//         centerScrollOnAllLines5(lineElement5, fixedHeader);
       
//         const lineElement4 = document.getElementById('GanttChartDIV4line1');
//         centerScrollOnAllLines4(lineElement4, fixedHeader);
//         const lineElement3 = document.getElementById('GanttChartDIV3line1');
//         centerScrollOnAllLines3(lineElement3, fixedHeader);
//         const lineElement2 = document.getElementById('GanttChartDIV2line1');
//         centerScrollOnAllLines2(lineElement2, fixedHeader);
//         const lineElement1 = document.getElementById('GanttChartDIVline1');
//         centerScrollOnAllLines1(lineElement1, fixedHeader);
//         const lineElement6 = document.getElementById('GanttChartDIV6line1');
//         centerScrollOnAllLines6(lineElement6, fixedHeader);

//         document.getElementById('centerTodayContainer').addEventListener('click', function () {
//             // Centrer le graphique par rapport à la date d'aujourd'hui
//             const lineElement5 = document.getElementById('GanttChartDIV5line1');
//             centerScrollOnAllLines5(lineElement5, fixedHeader);
        
//             const lineElement4 = document.getElementById('GanttChartDIV4line1');
//             centerScrollOnAllLines4(lineElement4, fixedHeader);
//             const lineElement3 = document.getElementById('GanttChartDIV3line1');
//             centerScrollOnAllLines3(lineElement3, fixedHeader);
//             const lineElement2 = document.getElementById('GanttChartDIV2line1');
//             centerScrollOnAllLines2(lineElement2, fixedHeader);
//             const lineElement1 = document.getElementById('GanttChartDIVline1');
//             centerScrollOnAllLines1(lineElement1, fixedHeader);
//             const lineElement6 = document.getElementById('GanttChartDIV6line1');
//             centerScrollOnAllLines6(lineElement6, fixedHeader);
//         });

//         function observeHeaderChanges() {
//             let observer = new MutationObserver(() => {
//                 requestAnimationFrame(() => {
//                     fixedHeader.replaceWith(ganttHead.cloneNode(true)); 
//                 });
//             });
//             observer.observe(ganttHead, { childList: true, subtree: true, attributes: true });
//         }
        
//         ganttBody.addEventListener("scroll", syncScroll);
//         observeHeaderChanges();
        

//         // // Suppression d'anciennes lignes pour éviter la duplication
//         // document.querySelectorAll(".gantt-holiday-line").forEach(line => line.remove());
//         // document.querySelectorAll(".gantt-holiday-cell").forEach(cell => cell.classList.remove("gantt-holiday-cell"));

//         let firstRow = ganttHead.querySelectorAll("tr:first-child td");
//         let secondRow = ganttHead.querySelectorAll("tr:nth-child(2) td");
//         let rows = ganttBody.querySelectorAll("tr");

//         let weekRanges = [];
//         firstRow.forEach((cell, index) => {
//             let match = cell.innerText.match(/(\d{2}\/\d{2}\/\d{4}) - (\d{2}\/\d{2}\/\d{4})/);
//             if (match) {
//                 let startDate = new Date(match[1].split('/').reverse().join('-'));
//                 let endDate = new Date(match[2].split('/').reverse().join('-'));
//                 weekRanges.push({ index, startDate, endDate });
//             }
//         });

//         // let columnDates = [];
//         window.columnDates = [...document.querySelectorAll(".gantt-column")].map(column => {
//             let dateStr = column.getAttribute("data-date"); // colonne avec une date
//             return { column, date: new Date(dateStr) };
//         });
//         window.columnDatesWeek = [...document.querySelectorAll(".gantt-column")].map(column => {
//             let dateStrWeek = column.getAttribute("data-date"); // colonne avec une date
//             return { column, date: new Date(dateStr) };
//         });
//         secondRow.forEach((column, colIndex) => {
//             let dayNumber = parseInt(column.innerText.trim(), 10);

//             if (!isNaN(dayNumber)) {
//                 let weekIndex = Math.floor(colIndex / 7);
//                 let foundWeek = weekRanges[weekIndex];

//                 if (foundWeek) {
//                     let fullDate = new Date(foundWeek.startDate);
//                     fullDate.setDate(dayNumber);
//                     columnDates.push({ colIndex, date: fullDate, column });
//                 }
//             }
//         });
        
//         // let joursFeries = getJoursFeries(new Date().getFullYear());
//         let uniqueYears = new Set(columnDates.map(({ date }) => date.getFullYear()));
//         // les jours fériés pour toutes les années trouvées
//         let joursFeries = [...uniqueYears].flatMap(year => getJoursFeries(year));

//         columnDates.forEach(({ colIndex, date, column }) => {
//              dateStr = date.toISOString().split('T')[0];

//             if (joursFeries.includes(dateStr)) {
//                 rows.forEach(row => {
//                     let cell = row.children[colIndex + 1];
//                     if (cell) {
//                         cell.classList.add("gantt-holiday-cell");
//                     }
//                 });

//                 // Création de la ligne rouge pour le jour férié
//                 let holidayLine = document.createElement("div");
//                 holidayLine.classList.add("gantt-holiday-line");

//                 // Position et largeur basées sur la cellule de la deuxième ligne
//                 let columnRect = column.getBoundingClientRect();
//                 let ganttRect = ganttBody.getBoundingClientRect();

//                 holidayLine.style.left = `${column.offsetLeft}px`;
//                 holidayLine.style.width = `${column.offsetWidth}px`;
//                 holidayLine.style.height = `${ganttBody.scrollHeight}px`;
//                 let nextColumn = secondRow[colIndex + 1];
//                 if (nextColumn) {
//                     holidayLine.style.left = `${nextColumn.offsetLeft}px`; // position de la case suivante
//                     holidayLine.style.width = `${nextColumn.offsetWidth}px`; // largeur de la case
//                     holidayLine.style.height = `${ganttBody.scrollHeight}px`; // hauteur totale du Gantt
//                     ganttBody.appendChild(holidayLine);
//                 }
//             }


//             // Traitement des jours des week-end
//             if (column.classList.contains("gminorheadingwkend")) {
//                 // Création de la ligne verticale grise
//                 let weekendLine = document.createElement("div");
//                 weekendLine.classList.add("gantt-weekend-line");

//                 // Style de la ligne
//                 weekendLine.style.position = "absolute";
//                 weekendLine.style.left = `${column.offsetLeft}px`;
//                 weekendLine.style.width = `${column.offsetWidth}px`;
//                 weekendLine.style.height = `${ganttBody.scrollHeight}px`;
//                 weekendLine.style.backgroundColor = "rgba(136, 136, 136, 0.2)"; 
//                 weekendLine.style.opacity = "0.2"; 

//                 // Ajout de la ligne dans le Gantt
//                 ganttBody.appendChild(weekendLine);
//             }
//         });

//         // Suppression d'anciennes lignes pour éviter la duplication
//         document.querySelectorAll(".gantt-holiday-line-week").forEach(line => line.remove());
//         document.querySelectorAll(".gantt-holiday-cell-week").forEach(cell => cell.classList.remove("gantt-holiday-cell"));

//         let yearMap = [];
//         firstRow.forEach((cell, index) => {
//             let yearMatch = cell.innerText.match(/^(\d{4})$/); // si c'est une année seule (2024, 2025)
//             if (yearMatch) {
//                 let year = parseInt(yearMatch[1], 10);
//                 yearMap.push({ index, year });
//             }
//         });

//         // let columnDatesWeek = [];
//         secondRow.forEach((column, colIndex) => {
//             let dayMonthMatch = column.innerText.match(/^(\d{2})\/(\d{2})$/); // Ex: "24/02"
//             if (dayMonthMatch) {
//                 let day = parseInt(dayMonthMatch[1], 10);
//                 let month = parseInt(dayMonthMatch[2], 10) - 1; // 0 = Janvier

//                 // L'année associée epar rapport à la première ligne
//                 let foundYear = yearMap.find(y => y.index <= colIndex);
//                 let year = foundYear ? foundYear.year : new Date().getFullYear(); // Si aucune année trouvée, année actuelle

//                 let fullDate = new Date(year, month, day);
//                 columnDatesWeek.push({ colIndex, date: fullDate, column });
//             }
//         });

//         let uniqueYearsWeek = new Set(columnDatesWeek.map(({ date }) => date.getFullYear()));
//         let joursFeriesWeek = [...uniqueYearsWeek].flatMap(year => getJoursFeries(year));

//         columnDatesWeek.forEach(({ colIndex, date, column }) => {
//             let weekDays = [];

//             // Récupération des 7 jours de la semaine à partir du lundi
//             for (let i = 0; i < 7; i++) {
//                 let fullDate = new Date(date);
//                 fullDate.setDate(fullDate.getDate() + i);
//                 weekDays.push(fullDate);
//             }

//             // Ajout des jours fériés sur la semaine
//             weekDays.forEach((fullDate, i) => {
//                 let dateStr = fullDate.toISOString().split('T')[0];

//                 if (joursFeriesWeek.includes(dateStr)) {
//                     let holidayLineWeek = document.createElement("div");
//                     holidayLineWeek.classList.add("gantt-holiday-line-week");
//                     holidayLineWeek.style.position = "absolute";

//                     // Position exacte du jour férié dans la semaine
//                     let dayPosition = (i / 7) * column.offsetWidth;
//                     holidayLineWeek.style.left = `${column.offsetLeft + dayPosition}px`;

//                     holidayLineWeek.style.width = "1px";
//                     holidayLineWeek.style.height = `${ganttBody.scrollHeight}px`;
//                     holidayLineWeek.style.backgroundColor = "rgba(255, 0, 0, 0.3)";

//                     ganttBody.appendChild(holidayLineWeek);
//                 }
//             });

//         });

        

//         function attachEventListeners() {
//             // Sauvegarde de l'élément 'jour', 'mois' '...sélectionné
//             // const currentSelectedText = document.querySelector(".gformlabel.gselected")?.textContent.trim();
//             document.querySelectorAll(".gformlabel").forEach(element => {
//                 element.addEventListener("click", function () {
//                     // si l'élément est déjà sélectionné
//                     if (this.classList.contains("gselected")) {
//                         return; 
//                     }

//                     // Suppression de la classe 'gselected' de tous les éléments
//                     // document.querySelectorAll(".gformlabel").forEach(el => el.classList);
//                     // document.querySelectorAll(".gformlabel").forEach(el => el.classList.remove("gselected"));

//                     // Ajout de la classe 'gselected' à l'élément cliqué
//                     this.classList.add("gselected");

//                     // console.log(`Format changé : ${this.textContent.trim()}`);
//                     setTimeout(() => {
//                         addFixedHeader();
//                     }, 100);
//                 });
//             });
//             // console.log("Événements de sélection ajoutés !");
//             // if (currentSelectedText) {
//             //     const toSelect = Array.from(document.querySelectorAll(".gformlabel"))
//             //         .find(el => el.textContent.trim() === currentSelectedText);
//             //     if (toSelect) toSelect.classList.add("gselected");
//             // }
//         }

//         // Observation de l'ajout de nouveaux éléments dans le DOM
//         const observer = new MutationObserver((mutationsList, observer) => {
//             if (document.querySelector(".gformlabel")) {
//                 attachEventListeners();
//                 observer.disconnect(); // On arrête d'observer une fois que les éléments sont trouvés
//             }
//         });

//         observer.observe(document.body, { childList: true, subtree: true });

//         // Ajout des événements immédiatement si les éléments existent déjà
//         if (document.querySelector(".gformlabel")) {
//             attachEventListeners();
//         }

//         // $("#userFilter, #jobFilter, #skillFilter, #projectFilter, #orderFilter, #propalFilter, #groupFilter, #agenceFilter, #domFilter, #respProjFilter, #resAntFilter, #absFilter").on("select2:select select2:unselect", function () {
//         //     attachEventListeners();
//         // });

//     }

//     //    Observables pour la mise à jour de l'en-tête fixe des dates et du centre graphique en fonction de la date d'aujourd'hui.
//     observeUntilReady(['resetDates', 'startDate', 'endDate'], (resetBtn, startInput, endInput) => {
//         const triggerScrollAndHeader = () => {
//             setTimeout(() => {
//                 addFixedHeader();
//                 const lineElement5 = document.getElementById('GanttChartDIV5line1');
//                 centerScrollOnAllLines5(lineElement5, document.getElementById("fixedHeader_GanttChartDIV5"));
//             }, 100);
//         };

//         resetBtn.addEventListener('click', triggerScrollAndHeader);
//         startInput.addEventListener('change', triggerScrollAndHeader);
//         endInput.addEventListener('change', triggerScrollAndHeader);
//     });


//     observeUntilReady(['toggleAvailabilityPartial', 'availabilityIconPartial'], (toggleBtn, icon) => {
//         toggleBtn.addEventListener('click', () => {
//             const isActive = icon.classList.contains('fa-toggle-off');

//             setTimeout(() => {
//                 addFixedHeader();
//                 const lineElement2 = document.getElementById('GanttChartDIV2line1');
//                 centerScrollOnAllLines2(lineElement2, document.getElementById("fixedHeader_GanttChartDIV5"));
//             }, 100);
//         });
//     });

//     observeUntilReady(['toggleAvailability', 'availabilityIcon'], (toggleBtn, icon) => {
//         toggleBtn.addEventListener('click', () => {
//             const isActive = icon.classList.contains('fa-toggle-off');

//             setTimeout(() => {
//                 addFixedHeader();
//                 const lineElement2 = document.getElementById('GanttChartDIV2line1');
//                 centerScrollOnAllLines2(lineElement2, document.getElementById("fixedHeader_GanttChartDIV5"));
//             }, 100);
//         });
//     });    


//     function getJoursFeries(year) {
//         function getEasterDate(y) {
//             let f = Math.floor,
//                 a = y % 19,
//                 b = f(y / 100),
//                 c = y % 100,
//                 d = f(b / 4),
//                 e = b % 4,
//                 g = f((8 * b + 13) / 25),
//                 h = (19 * a + b - d - g + 15) % 30,
//                 i = f(c / 4),
//                 k = c % 4,
//                 l = (32 + 2 * e + 2 * i - h - k) % 7,
//                 m = f((a + 11 * h + 22 * l) / 451),
//                 month = f((h + l - 7 * m + 114) / 31),
//                 day = ((h + l - 7 * m + 114) % 31) + 1;
//             return new Date(y, month - 1, day);
//         }

//         let paques = getEasterDate(year);
//         let joursFeries = [
//             new Date(year, 0, 1), new Date(year, 4, 1), new Date(year, 4, 8),
//             new Date(year, 6, 14), new Date(year, 7, 15), new Date(year, 10, 1),
//             new Date(year, 10, 11), new Date(year, 11, 25),
//             new Date(paques.getTime() + 1 * 24 * 60 * 60 * 1000),
//             new Date(paques.getTime() + 39 * 24 * 60 * 60 * 1000),
//             new Date(paques.getTime() + 50 * 24 * 60 * 60 * 1000)
//         ];

//         return joursFeries.map(date => date.toISOString().split('T')[0]);
//     }
    
//     // Gestion du reset des filtres (excepté les dates d'absence)
//     const resetFiltersBtn = document.getElementById('resetFiltersBtn');
//     if (resetFiltersBtn) {
//         resetFiltersBtn.addEventListener('click', function() {
//             setTimeout(() => {
//                 addFixedHeader();
//             }, 100);
//         });
//     }


//     $(document).ready(function () {
//         // Sélection de tous les filtres et mise a jours des jours fériés
//         $("#userFilter, #jobFilter, #skillFilter, #projectFilter, #orderFilter, #propalFilter, #groupFilter, #agenceFilter, #domFilter, #respProjFilter, #resAntFilter, #absFilter, #domFilter").on("select2:select select2:unselect", function () {
//             setTimeout(() => {
//                 addFixedHeader();
//             }, 100);
//         });
//     });

//     let idsToCheck = [
//         "GanttChartDIVchartTableh",
//         "GanttChartDIV2chartTableh",
//         "GanttChartDIV3chartTableh",
//         "GanttChartDIV4chartTableh",
//         "GanttChartDIV5chartTableh",
//         "GanttChartDIV6chartTableh"
//     ];

//     idsToCheck.forEach(id => {
//         let interval = setInterval(() => {
//             let el = document.getElementById(id);
//             if (el) {
//                 addFixedHeader(); 
//                 clearInterval(interval); 
//             }
//         }, 100);
//     });

//     // let checkInterval = setInterval(() => {
//     //     let ganttHeader = document.getElementById("GanttChartDIVchartTableh");
//     //     if (ganttHeader) {
//     //         addFixedHeader();
//     //         clearInterval(checkInterval);
//     //     }
//     // }, 100);

//     document.querySelectorAll(".tabsElem a").forEach(tab => {
//         tab.addEventListener("click", function () {
//             setTimeout(() => {
//                 addFixedHeader();
//             }, 100);
//         });
//     });

    
// });


// // Affichage du loader
// function showLoader(visible) {
//     const loader = document.getElementById('loader'); 
//     if (loader) {
//         loader.style.display = visible ? 'block' : 'none';
//     }
// }


// function applyScrollWhenVisible1(ganttContainer, offset, monthWidth) {
//     const parentElement = document.getElementById('tabs');
//     const checkVisibility = () => {
    
//         if (window.getComputedStyle(parentElement).display !== 'none') {
//             setTimeout(() => {
//                 scrollPosition = offset * monthWidth;
    
//                 ganttContainer.scrollLeft = scrollPosition;
//                 observer.disconnect(); // On arrête d'observer
//                 console.log(ganttContainer.scrollLeft);
//             }, 100);
//         } else {
//             // console.log("En attente que le parent devienne visible...");
//         }
//     };

//     const observer = new MutationObserver(checkVisibility);
//     observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

//     checkVisibility();
// }

// function applyScrollWhenVisible3(ganttContainer, offset, monthWidth) {
//     const parentElement = document.getElementById('tabs3');
//     const checkVisibility = () => {
    
//         if (window.getComputedStyle(parentElement).display !== 'none') {
//             setTimeout(() => {
//                 scrollPosition = offset * monthWidth;
    
//                 ganttContainer.scrollLeft = scrollPosition;
//                 observer.disconnect(); // On arrête d'observer
//                 // console.log(ganttContainer.scrollLeft);
//             }, 100);
//         } else {
//             // console.log("En attente que le parent devienne visible...");
//         }
//     };

//     const observer = new MutationObserver(checkVisibility);
//     observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });
//     checkVisibility();
// }

// function applyScrollWhenVisible4(ganttContainer, offset, monthWidth) {
//     const parentElement = document.getElementById('tabs4');
//     const checkVisibility = () => {
    
//         if (window.getComputedStyle(parentElement).display !== 'none') {
//             setTimeout(() => {
//                 scrollPosition = offset * monthWidth;
    
//                 ganttContainer.scrollLeft = scrollPosition;
//                 observer.disconnect(); // On arrête d'observer
//                 // console.log(ganttContainer.scrollLeft);
//             }, 100);
//         } else {
//             console.log("En attente que le parent devienne visible...");
//         }
//     };

//     const observer = new MutationObserver(checkVisibility);
//     observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

//     checkVisibility();
// }

// function applyScrollWhenVisible5(ganttContainer, offset, monthWidth) {
//     const parentElement = document.getElementById('tabs5');
//     const checkVisibility = () => {
    
//         if (window.getComputedStyle(parentElement).display !== 'none') {
//             setTimeout(() => {
//                 scrollPosition = offset * monthWidth;
    
//                 ganttContainer.scrollLeft = scrollPosition;
//                 observer.disconnect(); // On arrête d'observer
//                 // console.log(ganttContainer.scrollLeft);
//             }, 100);
//         } else {
//             // console.log("En attente que le parent devienne visible...");
//         }
//     };

//     const observer = new MutationObserver(checkVisibility);
//     observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

//     checkVisibility();
// }

// function applyScrollWhenVisible6(ganttContainer, offset, monthWidth) {
//     const parentElement = document.getElementById('tabs6');
//     const checkVisibility = () => {
    
//         if (window.getComputedStyle(parentElement).display !== 'none') {
//             setTimeout(() => {
//                 scrollPosition = offset * monthWidth;
    
//                 ganttContainer.scrollLeft = scrollPosition;
//                 observer.disconnect(); // On arrête d'observer
//                 // console.log(ganttContainer.scrollLeft);
//             }, 100);
//         } else {
//             // console.log("En attente que le parent devienne visible...");
//         }
//     };

//     const observer = new MutationObserver(checkVisibility);
//     observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

//     checkVisibility();
// }

// function applyScrollWhenVisible(ganttContainer, offset, monthWidth) {
//     const parentElement = document.getElementById('tabs2');
//     const checkVisibility = () => {
    
//         if (window.getComputedStyle(parentElement).display !== 'none') {
//             setTimeout(() => {
//                 scrollPosition = offset * monthWidth;
    
//                 ganttContainer.scrollLeft = scrollPosition;
//                 observer.disconnect(); // On arrête d'observer
//             }, 100);
//         } else {
//             // console.log("En attente que le parent devienne visible...");
//         }
//     };

//     const observer = new MutationObserver(checkVisibility);
//     observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

//     checkVisibility();
// }


// document.addEventListener('DOMContentLoaded', function () {
//     const ganttHeader = document.querySelector('.gcharttableh'); 
//     const navbar = document.querySelector('#topmenu');

//     if (ganttHeader) {
//         // Si l'en-tête du Gantt est trouvé
//         let navbarHeight = 0;

//         if (navbar) {
//             // Si la barre de navigation est trouvée, on récupère sa hauteur
//             navbarHeight = navbar.offsetHeight;
//         } 

//         // Application du positionnement fixe à l'en-tête du Gantt
//         window.addEventListener('scroll', function () {
//             if (window.scrollY > navbarHeight) {
//                 // Si le défilement dépasse la hauteur de la barre de navigation
//                 ganttHeader.style.position = 'fixed';
//                 ganttHeader.style.top = `${navbarHeight}px`;  
//                 ganttHeader.style.zIndex = '1000';
//                 ganttHeader.style.backgroundColor = '#fff'; 
//             } else {
//                 // L'en-tête dans son état normal au début d la page
//                 ganttHeader.style.position = 'relative';
//                 ganttHeader.style.top = '0';
//             }
//         });
//     } 
// });



/***********************************************
 *                                             *
 *           FETCH FILTRED DATA SECTION        *
 *                                             *
 *  Cette section contient les fonctions       *
 *  permettant de récupérer les données des    *
 * filtrres via des appels AJAX et de          *
 * les traiter.         *
 *                                             *
 ***********************************************/

// function fetchFilterData(lazy = false) {
//     return new Promise((resolve, reject) => {
//         // Paramètres conditionnels (lazy loading)
//         const datatofilter = lazy ? { limit: 100 } : {};

//         // Indicateur de chargement
//         // showLoader(true);

//         $.ajax({
//             url: '/custom/workload/ajax/filter_data.php',
//             type: 'GET',
//             dataType: 'json',
//             data: datatofilter, 
//             cache: false,
//             success: function (responsefilter) {
//                 // showLoader(false); // Masquer le loader

//                 // Vérification des données
//                 if (responsefilter.dataUsers && Array.isArray(responsefilter.dataUsers) 
// 				    && responsefilter.dataJobs && Array.isArray(responsefilter.dataJobs)
// 					&& responsefilter.dataGroups && Array.isArray(responsefilter.dataGroups)
// 					&& responsefilter.dataRespProj && Array.isArray(responsefilter.dataRespProj)
// 					&& responsefilter.dataProjects && Array.isArray(responsefilter.dataProjects)
// 					&& responsefilter.dataOrders && Array.isArray(responsefilter.dataOrders)
// 					&& responsefilter.dataSkills && Array.isArray(responsefilter.dataSkills)
// 					&& responsefilter.dataAgencies && Array.isArray(responsefilter.dataAgencies)
// 					&& responsefilter.dataPropals && Array.isArray(responsefilter.dataPropals)
//                     && responsefilter.dataAbsType && Array.isArray(responsefilter.dataAbsType)) {
                  
// 					// console.log(responsefilter.dataPropals);
// 					 populateSelect("#jobFilter", responsefilter.dataJobs, "job_id", "job_label");
// 					 populateSelect("#userFilter", responsefilter.dataUsers, "id", "fullname");
// 					 populateSelect("#groupFilter", responsefilter.dataGroups, "group_id", "nom");
// 					 populateSelect("#respProjFilter", responsefilter.dataRespProj, "id", "fullname");
// 					 populateSelect("#projectFilter", responsefilter.dataProjects, "fk_projet", "nom");
// 					 populateSelect("#orderFilter", responsefilter.dataOrders, "order_id", "nom");
// 					 populateSelect("#skillFilter", responsefilter.dataSkills, "skillid", "label");
// 					 populateSelect("#agenceFilter", responsefilter.dataAgencies, "socid", "name_alias");
// 					 populateSelect("#propalFilter", responsefilter.dataPropals, "propal_id", "nom");
//                      populateSelect("#absFilter", responsefilter.dataAbsType, "fk_type", "conge_label");
//                      populateSelect("#resAntFilter", responsefilter.dataAgencies, "socid", "name_alias");
//                      populateSelect("#domFilter", responsefilter.dataProjects, "domaine", "domaine");
                     
                     
					 
                  
//                     resolve(responsefilter);
//                 } else {
//                     console.warn("Réponse non valide :", responsefilter);
//                     reject("Données des filtres invalides reçues.");
//                 }
//             },
//             error: function (xhr, status, error) {
//                 // showLoader(false); // Masquer le loader

//                 // Log détaillé des erreurs
//                 console.error("Erreur AJAX :", error);
//                 reject({
//                     message: "Erreur lors de l'appel AJAX",
//                     status: status,
//                     error: error,
//                 });
//             }
//         });
//     });
// }


// // Fonction pour remplir un select avec des options
// function populateSelect(selectId, dataArray, valueField, labelField) {
//     const select = $(selectId);
    
//     // On filtre des éléments pour qu'ils soient uniques en fonction de valueField
//     const uniqueDataArray = dataArray.filter((item, index, self) =>
//         index === self.findIndex(e => e[valueField] === item[valueField])
//     );
   
//     select.empty(); // Pour vider les options existantes
//     initializeSelect2();
    
//     // Ajout des options à la liste déroulante
//     uniqueDataArray.forEach(item => {
//         select.append(new Option(item[labelField], item[valueField]));
//     });
// }

// // Fonction pour obtenir les utilisateurs associés à un critère spécifique
// function getUsersFromDataArray(selectedId, criteriaField, dataArray) {
//     const users = new Set();

//     // Parcours des dataArray pour trouver les utilisateurs associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId && item.fk_user) {
//             users.add(item.fk_user);
//         }
//     });

//     return Array.from(users); // Le Set en Array
// }

// // Fonction pour obtenir les utilisateurs associés à un critère spécifique
// function getUsersDataArray(selectedId, criteriaField, dataArray) {
//     const users = new Set();

//     // Parcours des dataArray pour trouver les utilisateurs associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             users.add(item.id);
//         }
//     });

//     return Array.from(users); // Le Set en Array
// }

// // Fonction pour obtenir les projets associés à un critère spécifique
// function getProjectFromDataArray(selectedId, criteriaField, dataArray) {
//     const projects = new Set();
	
//     // Parcours des dataArray pour trouver les projets associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId && item.id_project) {
//             projects.add(item.id_project);
//         }
//     });

//     return Array.from(projects); // Le Set en Array
// }

// function getProjectDataArray(selectedId, criteriaField, dataArray) {
//     const projects = new Set();
	
//     // Parcours des dataArray pour trouver les projets associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             projects.add(item.fk_projet);
//         }
//     });

//     return Array.from(projects); // Le Set en Array
// }

// function getOrderDataArray(selectedId, criteriaField, dataArray) {
//     const orders = new Set();
	
//     // Parcours des dataArray pour trouver les commandes associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             orders.add(item.order_id);
//         }
//     });

//     return Array.from(orders); // Le Set en Array
// }

// function getAgencyDataArray(selectedId, criteriaField, dataArray) {
//     const agencies = new Set();
	
//     // Parcours des dataArray pour trouver les agences associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             agencies.add(item.socid);
//         }
//     });

//     return Array.from(agencies); // Le Set en Array
// }

// function getPropalDataArray(selectedId, criteriaField, dataArray) {
//     const propals = new Set();
	
//     // Parcours des dataArray pour trouver les agences associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             propals.add(item.propal_id);
//         }
//     });

//     return Array.from(propals); // Le Set en Array
// }

// function getDomDataArray(selectedId, criteriaField, dataArray) {
//     const doms = new Set();
	
//     // Parcours des dataArray pour trouver les agences associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             doms.add(item.domaine);
//         }
//     });

//     return Array.from(doms); // Le Set en Array
// }

// function getResAntDataArray(selectedId, criteriaField, dataArray) {
//     const resAnt = new Set();
	
//     // Parcours des dataArray pour trouver les agences associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             resAnt.add(item.socid);
//         }
//     });

//     return Array.from(resAnt); // Le Set en Array
// }

// function getAbsTypeDataArray(selectedId, criteriaField, dataArray) {
//     const absType = new Set();
	
//     // Parcours des dataArray pour trouver les agences associés
//     dataArray.forEach(item => {
//         if (item[criteriaField] == selectedId) {
//             absType.add(item.fk_type);
//         }
//     });

//     return Array.from(absType); // Le Set en Array
// }
// // Fonction pour filtrer les ressourcesAbs comprises entre deux dates qui concernent la période d'absence d'un salarié. 
// function filterResourcesByAbsPeriodes(startAbsPeriode, endAbsPeriode, resources) {
//     const start = new Date(startAbsPeriode);
//     const end = new Date(endAbsPeriode);
  
//     // if (resources.periodes && Array.isArray(resources.periodes)) {
//         const filtered = resources.filter(resource => {
//             return resource.periodes?.some(abs => {
//                 const absStart = new Date(abs.date_start);
//                 const absEnd = new Date(abs.date_end);
//                 return absEnd >= start && absStart <= end;
//             });
//         });
//         return filtered;
//     // }
// }

// // Fonction pour filtrer les ressourcesAbs comprises entre deux dates qui concernent la période d'absence d'un salarié. 
// function filterResourcesByAbsDates(startAbsDate, endAbsDate, resources) {
//     const start = startAbsDate ? new Date(startAbsDate) : null;
//     const end = endAbsDate ? new Date(endAbsDate) : null;
   
//     const absencesFiltered = resources.filter(abs => {
//         const absStart = new Date(abs.date_start);
//         const absEnd = new Date(abs.date_end);

//         const overlaps =
//             (!start || absEnd >= start) &&
//             (!end || absStart <= end);

//         return overlaps;
//     });
//     return absencesFiltered;
// }


// // Fonction pour filtrer les ressources en fonction des IDs d'utilisateurs
// function filterResourcesByUsers(userIds, resources) {
//     return resources.filter(resource => userIds.includes(resource.id));
// }

// // function filterResourcesByProjects(projectIds, resources) {
// //     return resources.filter(resource => projectIds.includes(resource.fk_projet));
// // }
// function filterResourcesByProjects(projectIds, resources) {
//     return resources.filter(resource => {
//         const projetsArray = resource.fk_projets ? resource.fk_projets.split(',').map(p => p.trim()) : [];
//         return projetsArray.some(projet => projectIds.includes(projet)) || projectIds.includes(resource.fk_projet);
//     });
// }


// function filterResourcesByOrders(orderIds, resources) {
//     return resources.filter(resource => resource.idref == 'CO' && orderIds.includes(resource.element_id));
// }

// // function filterResourcesByOrders(orderIds, resources) {
// //     return resources.filter(resource => {
// //         const ordersArray = resource.fk_orders ? resource.fk_orders.split(',').map(p => p.trim()) : [];
// //         return ordersArray.some(order => orderIds.includes(order)) || ((resource.idref == 'CO' || resource.idref == 'HL') && orderIds.includes(resource.element_id));
// //         // return ordersArray.some(order => orderIds.includes(order)) || (orderIds.includes(resource.element_id));
// //     });
// // }

// function filterResourcesByAgencies(agencyIds, resources) {
//     return resources.filter(resource => {
//         const agenciesArray = resource.agences ? resource.agences.split(',').map(p => p.trim()) : [];
//         return agenciesArray.some(agency => agencyIds.includes(agency)) || agencyIds.includes(resource.agence);
//     });
// }

// // function filterResourcesByAgencies(agencyIds, resources) {
// //     return resources.filter(resource => {
// //         let agenciesArray = [];
// //         if (resource.agences) {
// //             agenciesArray = agenciesArray.concat(
// //                 resource.agences.split(',').map(p => p.trim())
// //             );
// //         }
// //         return agenciesArray.some(agency => agencyIds.includes(agency));
// //     });
// // }


// // function filterResourcesByAgencies(agencyIds, resources) {
// //     return resources.filter(resource => agencyIds.includes(resource.agence));
// // }

// function filterResourcesByPropals(propalIds, resources) {
//     return resources.filter(resource => resource.idref == 'PR' && propalIds.includes(resource.propalid));
// }

// // function  filterResourcesByDoms(domIds, resources) {
// //     return resources.filter(resource => domIds.includes(resource.domaine));
// // }

// function  filterResourcesByDoms(domIds, resources) {
//     return resources.filter(resource => {
//         const domsArray = resource.domaines ? resource.domaines.split(',').map(d => d.trim()) : [];
//         return (resources.includes(resource.domaine)) || domsArray.some(domaine => domIds.includes(domaine));
//     });
// }


// // function filterResourcesByPropals(propalIds, resources) {
// //     return resources.filter(resource => {
// //         const propalsArray = resource.fk_propals ? resource.fk_propals.split(',').map(p => p.trim()) : [];
// //         return propalsArray.some(propal => propalIds.includes(propal)) || (resource.idref == 'PR' && propalIds.includes(resource.propalid));
// //     });
// // }


// function filterResourcesByResAnts(resAntIds, resources) {
//     return resources.filter(resource => resAntIds.includes(resource.antenne));
// }

// function filterResourcesByAbsType(absTypeIds, resources) {
//     return resources.filter(resource => absTypeIds.includes(resource.fk_type));
// }

// function filterResourcesByAbsPeriodeType(absTypeIds, resources) {
//     return resources.filter(resource => 
//         Array.isArray(resource.periodes) &&
//         resource.periodes.some(periode => absTypeIds.includes(periode.fk_type))
//     );
// }

// function setDefaultDateRangeFromResources(resources) {
//     // if (!resources || resources.length === 0) return;
//     let minDate = null;
//     let maxDate = null;

//     resources.forEach(res => {
//         const start = new Date(res.date_start);
//         const end = new Date(res.date_end);

//         if (!minDate || start < minDate) minDate = start;
//         if (!maxDate || end > maxDate) maxDate = end;
//     });
    

//     if (minDate) {
//         document.getElementById("startDate").value = minDate.toISOString().split('T')[0];
//     }
//     if (maxDate) {
//         document.getElementById("endDate").value = maxDate.toISOString().split('T')[0];
//     }
// }


// //Fonction pour initialiser Select2 pour les filtres
// function initializeSelect2() {
//     $("#userFilter, #jobFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #skillFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter").select2({
//         width: 'resolve', 
//         placeholder: function() { return $(this).attr('placeholder'); },
//         allowClear: false
//     });
// }


/***********************************************
 *                                             *
 *           FETCH DATA SECTION            *
 *                                             *
 *  Cette section contient les fonctions       *
 *  permettant de récupérer les données via    *
 *  des appels AJAX et de les traiter.         *
 *                                             *
 ***********************************************/
// Fetch data 
// let ressources = null;
// let ressourcesProj = null;
// let ressourcesComm = null;
// let ressourcesAbs = null;
// let ressourcesProjAbs = null;
// function fetchData(lazy = false) {
//     return new Promise((resolve, reject) => {
//         // Paramètres conditionnels (lazy loading)
//         const data = lazy ? { limit: 100 } : {};

//         $.ajax({
//             url: '/custom/workload/ajax/workload_data.php',
//             type: 'GET',
//             dataType: 'json',
//             data: data, 
//             cache: false,
//             success: function (response) {
//                 // Vérification des données
//                 // console.log("Données reçues :", response);
//                 // Si les données sont valides
//                 ressources = response.res; // Données globalement stocked
//                 ressourcesProj = response.resProj;
//                 ressourcesComm = response.resComm;
//                 ressourcesAbs = response.resAbs;
//                 ressourcesProjAbs = response.resProjAbs;
//                 resolve({
//                     ressources: response.res,
//                     ressourcesProj: response.resProj,
//                     ressourcesComm: response.resComm,
//                     ressourcesAbs: response.resAbs,
//                     ressourcesProjAbs: response.resProjAbs
//                 });
//             },
//             error: function (xhr, status, error) {
//                 // Log détaillé des erreurs
//                 console.error("Erreur AJAX :", error);
//                 reject({
//                     message: "Erreur lors de l'appel AJAX",
//                     status: status,
//                     error: error,
//                 });
//             }
//         });
//     });
// }



// function setupFilterListeners(resources, filterData, updateGanttCallback, type) {
//     function applyAllFilters(resourcesBase) {
//         const selectedJobIds = $("#jobFilter").val() || [];
//         const selectedSkillIds = $("#skillFilter").val() || [];
//         const selectedAgencyIds = $("#agenceFilter").val() || [];
//         const selectedEmployeeIds = $("#userFilter").val() || [];
//         const selectedGroupIds = $("#groupFilter").val() || [];
//         const selectedRespProjIds = $("#respProjFilter").val() || [];
//         const selectedAffaireIds = $("#projectFilter").val() || [];
//         const selectedOrderIds = $("#orderFilter").val() || [];
//         const selectedPropalIds = $("#propalFilter").val() || [];
//         const selectedAbsTypeIds = $("#absFilter").val() || [];
//         const selectedResAntFilter = $("#resAntFilter").val() || [];
//         const selectedDomFilter = $("#domFilter").val() || [];
//         const startAbsDate = ($("#startDate").val() || "").trim();
//         const endAbsDate = ($("#endDate").val() || "").trim();
      

//         let filtered = [];
//         let filteredAbs = [];

//         const filters = {
//             job: selectedJobIds,
//             skill: selectedSkillIds,
//             agency: selectedAgencyIds,
//             employee: selectedEmployeeIds,
//             group: selectedGroupIds,
//             resp: selectedRespProjIds,
//             affaire: selectedAffaireIds,
//             abs: selectedAbsTypeIds,
//             resAnt: selectedResAntFilter,
//             dom: selectedDomFilter,
//             order: selectedOrderIds,
//             propal: selectedPropalIds,
//         };

//         // Vérification des données filtrées
//         const hasFilter = keys => keys.some(k => filters[k]?.length > 0);

//         // Les séléctions
//         const filtersSelected = hasFilter(['job','skill','agency','employee','group','resp','affaire','abs','resAnt','dom']);
//         const filtersSelectedLess = hasFilter(['order', 'propal']);
//         const isFiltredAbs = filters.abs.length > 0 && !hasFilter(['job','skill','agency','employee','group','resp','affaire','resAnt','dom']);

//         // Cas sans filtre actif
//         if (!filtersSelected && !filtersSelectedLess) {
//             filtered = [...resourcesBase];
//         }


//         // Les résultats des filtres sont additionnés
//         if (selectedJobIds.length > 0) {
//             selectedJobIds.forEach(jobId => {
//                 const users = getUsersFromDataArray(jobId, "job_id", filterData.dataJobs || []);
//                 filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
//             });
//         }

//         if (selectedSkillIds.length > 0) {
//             selectedSkillIds.forEach(skillId => {
//                 const users = getUsersFromDataArray(skillId, "skillid", filterData.dataSkills || []);
//                 filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
//             });
//         }

//         if (selectedEmployeeIds.length > 0) {
//             selectedEmployeeIds.forEach(employeeId => {
//                 const users = getUsersDataArray(employeeId, "id", filterData.dataUsers || []);
//                 filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
//             });
//         }

//         if (selectedGroupIds.length > 0) {
//             selectedGroupIds.forEach(groupId => {
//                 const users = getUsersFromDataArray(groupId, "group_id", filterData.dataGroups || []);
//                 filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
//             });
//         }

//         if (selectedRespProjIds.length > 0) {
//             selectedRespProjIds.forEach(resProjId => {
//                 const projects = getProjectFromDataArray(resProjId, "id", filterData.dataRespProj || []);
//                 filtered = filtered.concat(filterResourcesByProjects(projects, resourcesBase));
//             });
//         }

//         if (selectedAffaireIds.length > 0) {
//             selectedAffaireIds.forEach(projId => {
//                 const projects = getProjectDataArray(projId, "fk_projet", filterData.dataProjects || []);
//                 filtered = filtered.concat(filterResourcesByProjects(projects, resourcesBase));
//             });
//         }

        
//         if (type != 'abs' && type != 'projabs') {
//             if (selectedOrderIds.length > 0) {
//                 selectedOrderIds.forEach(orderId => {
//                     const orders = getOrderDataArray(orderId, "order_id", filterData.dataOrders || []);
//                     filtered = filtered.concat(filterResourcesByOrders(orders, resourcesBase));
//                 });
//             }
//         }else if (!filtersSelected){
//             filtered = [...resourcesBase];
//         }

//         if (selectedAgencyIds.length > 0) {
//             selectedAgencyIds.forEach(agencyId => {
//                 const agencies = getAgencyDataArray(agencyId, "socid", filterData.dataAgencies || []);
//                 filtered = filtered.concat(filterResourcesByAgencies(agencies, resourcesBase));
//             });
//         }

//         if (selectedPropalIds.length > 0) {
//             selectedPropalIds.forEach(propalId => {
//                 const propals = getPropalDataArray(propalId, "propal_id", filterData.dataPropals || []);
//                 filtered = filtered.concat(filterResourcesByPropals(propals, resourcesBase));
//             });
//         }

//         if (selectedDomFilter.length > 0) {
//             selectedDomFilter.forEach(domId => {
//                 const doms = getDomDataArray(domId, "domaine", filterData.dataProjects || []);
//                 filtered = filtered.concat(filterResourcesByDoms(doms, resourcesBase));
//             });
//         }

//         if (selectedResAntFilter.length > 0 )  {
//             selectedResAntFilter.forEach(resAntId => {
//                 const resAnts = getResAntDataArray(resAntId, "socid", filterData.dataAgencies || []);
//                 filtered = filtered.concat(filterResourcesByResAnts(resAnts, resourcesBase));
//             });
//         }

        
//         if ((type === 'abs' || type === 'projabs') && selectedAbsTypeIds.length > 0) {
//             selectedAbsTypeIds.forEach(absTypeId => {
//                 const absType = getAbsTypeDataArray(absTypeId, "fk_type", filterData.dataAbsType || []);
                
//                 if (type === 'abs') {
//                     filtered = filtered.concat(filterResourcesByAbsType(absType, resourcesBase));
//                 } else if (type === 'projabs') {
//                     filtered = filtered.concat(filterResourcesByAbsPeriodeType(absType, resourcesBase));
//                 }
//             });
//         }
        
//         if (isFiltredAbs && (type !== 'abs' && type !== 'projabs')) {
//             filtered = [...resourcesBase];
//         }
//         // les dates d'absence en interaction avec les ressources filtrées
//         const filterContainer = document.getElementById("dateFilterContainer");
//         if (type == 'abs' || type == 'projabs') {
           
//             if (type == 'abs') {
//                 if (startAbsDate !== "" && endAbsDate !== "") {
//                     if (filtered.some(resource => resource.id && resource.id.trim() !== "")) {
//                         filtered = filterResourcesByAbsDates(startAbsDate, endAbsDate, filtered);
//                     }
//                 }

//             }
            
//             if (type == 'projabs') {
//                 if (startAbsDate !== "" && endAbsDate !== "") {
//                     // if (filtered.some(resource => resource.id && resource.id.trim() !== "")) {
//                         filtered = filterResourcesByAbsPeriodes(startAbsDate, endAbsDate, filtered);
//                     // }
//                 }
//             }
           
//         }
//         // Suppression des doublons après addition 
//         const seen = new Set();
//         filtered = filtered.filter(item => {
//             const mainElement = item.element_id || item.element_id_abs || 'null';
//             // Clé unique composite : id + idref + element_id|fk_projet
//             const key = `${item.id}|${item.idref}|${item.fk_projet}|${mainElement}`;
//             if (seen.has(key)) {
//                 return false;
//             }

//             seen.add(key);
//             return true;
//         });

                
//         if (filtered.length === 0) {
//             return [];
//         }

//         return filtered;
//     }


//     function updateFilteredResources() {
//         let filteredResources = applyAllFilters(resources);
//         // let filteredResources = applyAllFilters(Array.isArray(resources) ? resources : Object.values(resources));
       
//         // Gestion de la disponibilité via les icônes
//         const iconElementFree = document.getElementById("availabilityIcon");
//         const iconElementPartial = document.getElementById("availabilityIconPartial");

//         if (iconElementFree?.classList.contains("fa-toggle-on") || iconElementPartial?.classList.contains("fa-toggle-on")) {
//             filteredResources = filteredResources.filter(filterCondition);
//         }

//         updateGanttCallback(filteredResources);  
        
//     }

//     $("#jobFilter, #skillFilter, #userFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter, #startDate, #endDate")
//     .on("change", function () {
//         updateFilteredResources();
//     });

    
//     // Gestion du reset des filtres (excepté les dates d'absence)
//     const resetFiltersBtn = document.getElementById('resetFiltersBtn');
//     if (resetFiltersBtn) {
//         resetFiltersBtn.addEventListener('click', function() {
//             // Réinitialiser tous les filtres sauf les dates
//             $("#jobFilter, #skillFilter, #userFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter")
//             .val([]); 
//             $('#jobFilter, #skillFilter, #userFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter').select2();
//             updateFilteredResources();
//         });
//     }
     

//     // Gestion du reset des dates
//     const filterContainer = document.getElementById("dateFilterContainer");
//     // if ($('#gant5').closest('.tabsElem').hasClass('tabactive')) {
//     $(document).ready(function() {
//         const resetBtn = document.getElementById('resetDates');
//         if (resetBtn) {
//             resetBtn.addEventListener('click', function() {
//                 let ressourcesAbs = resources;
//                 if (ressourcesAbs.some(resource => resource.id && resource.id.trim() !== "")) {
//                     // setDefaultDateRangeFromResources(ressourcesAbs);
//                     updateFilteredResources();
//                 } 
                
//             });
//         }
//     });
        
    

//     const startDateInput = document.getElementById('startDate');
//     const endDateInput = document.getElementById('endDate');
//     const resetButton = document.getElementById('resetDates');

//     let dateChanged = false;

//     // Fonction pour vérifier et appliquer la couleur
//     function updateBorderColor() {
//         if (startDateInput.value || endDateInput.value) {
//             filterContainer.classList.add('red-left-border');
//             dateChanged = true;
//         } else {
//             filterContainer.classList.remove('red-left-border');
//             dateChanged = false;
//         }
//     }

//     // Appliquer la logique au changement de dates
//     [startDateInput, endDateInput].forEach(input => {
//         input.addEventListener('change', updateBorderColor);
//     });

//     // Réinitialiser les dates et la couleur
//     resetButton.addEventListener('click', () => {
//         startDateInput.value = '';
//         endDateInput.value = '';
//         filterContainer.classList.remove('red-left-border');
//         dateChanged = false;
//     });

//     // Observation pour les icônes de disponibilité
//     const iconElementFree = document.getElementById("availabilityIcon");
//     const iconElementPartial = document.getElementById("availabilityIconPartial");

//     observeIconState(iconElementFree, updateFilteredResources);
//     observeIconState(iconElementPartial, updateFilteredResources);
  
//     updateFilteredResources(); // Initialisation
// }


// const observers = new Map(); // Stock des observers existants

// function observeIconState(iconElement, callback) {
//     // Vérification s'il existe déjà un observer pour cet élément
//     if (observers.has(iconElement)) {
//         observers.get(iconElement).disconnect(); // Suppresion de l'ancien observer
//     }

//     const observer = new MutationObserver(() => {
//         const isActive = iconElement.classList.contains("fa-toggle-on");
//         callback(isActive);
//     });

//     observer.observe(iconElement, { attributes: true, attributeFilter: ["class"] });
//     // observers.set(iconElement, observer); // Stock le nouvel observer
// }


// document.addEventListener("DOMContentLoaded", async function () {
//     // window.availabilityFree = false; // Disponibilité totale
//     // window.availabilityPartial = false; // Affecté partiel

//     const iconElementFree = document.getElementById("availabilityIcon"); // Icône totalement libre
//     const iconElementPartial = document.getElementById("availabilityIconPartial"); // Icône partiellement affecté


//     try {
//         // Événement sur l'icône "Totalement libre"
//         iconElementFree.addEventListener("click", function () {
//             window.availabilityFree = !window.availabilityFree;
//             updateIconState(iconElementFree, window.availabilityFree);
//             filterResourcesByAvailability();
//         });

//         // Événement sur l'icône "Affecté partiel"
//         iconElementPartial.addEventListener("click", function () {
//             window.availabilityPartial = !window.availabilityPartial;
//             updateIconState(iconElementPartial, window.availabilityPartial);
//             filterResourcesByAvailability();
//         });

//     } catch (error) {
//         console.error("Erreur lors du chargement des données :", error);
//     }
// });



// // Fonction pour mettre à jour l'état des icônes
// function updateIconState(iconElement, isActive) {
//     if (isActive) {
//         iconElement.classList.replace("fa-toggle-off", "fa-toggle-on");
//         iconElement.style.color = iconElement.id === "availabilityIcon" || iconElement === "availabilityIcon" ? "#007bff" : "#FFA500"; 
//     } else {
//         iconElement.classList.replace("fa-toggle-on", "fa-toggle-off");
//         iconElement.style.color = "#ccc"; 
//     }
// }

// function filterResourcesByAvailability() {
//     // Récupération de toutes les ressources d'origine sans filtre appliqué
//     let updatedResources = [...ressources]; 
//     let updatedResourcesProj = [...ressourcesProj];
//     let updatedResourcesComm = [...ressourcesComm];
//     // let updatedResourcesAbs = [...ressourcesAbs];
//     // let updatedResourcesProjAbs = [...ressourcesProjAbs];

        
//     // Filtres de disponibilité
//     if (window.availabilityFree || window.availabilityPartial) {
//         updatedResources = ressources.filter(filterCondition);
//         updatedResourcesProj = ressourcesProj.filter(filterCondition);
//         updatedResourcesComm = ressourcesComm.filter(filterCondition);
//         // updatedResourcesAbs = ressourcesAbs.filter(filterCondition);
//         // updatedResourcesProjAbs = ressourcesProjAbs.filter(filterCondition);
//     }

//     // Mise à jour des graphiques 
//     updateGanttChartColDev(updatedResources);
//     updateGanttChartColProj(updatedResourcesProj);
//     updateGanttChartProjCom(updatedResourcesProj);
//     updateGanttChartCodeCol(updatedResourcesComm);
// }

// // Fonction de filtrage selon la disponibilité
// function filterCondition(resource) {

//     if (!window.availabilityFree && !window.availabilityPartial) return true; // Si aucun filtre activé, afficher tout

//     const totalementLibre = typeof resource.element_id === "undefined" || resource.element_id === null;
//     const affectePartiel = resource.element_id === resource.fk_projet;

//     return (window.availabilityFree && totalementLibre) || (window.availabilityPartial && affectePartiel);
// }

// (async function initialize() {
//     try {
//         // Les absences en premier
//         // const [{ ressourcesAbs }, filterData] = await Promise.all([fetchData(), fetchFilterData()]);
//         // En parallèle 
//         showLoader(true);
//         const dataPromise = fetchData();         // Données complètes
//         const filterPromise = fetchFilterData(); // Données filtres

//         //Affichage des absences en premier
//         dataPromise.then(({ ressourcesAbs }) => {
//             updateGanttChartAbs(ressourcesAbs); 
//             showLoader(false);
//         });
    
        
//         // Toutes les données pour les filtres
//         const [
//         {
//             ressources,
//             ressourcesProj,
//             ressourcesComm,
//             ressourcesProjAbs
//         },
//         filterData
//         ] = await Promise.all([dataPromise, filterPromise]);
//         // if (!Array.isArray(ressourcesAbs)) throw new Error("Données absences invalides.");
//         // Affichage immédiat des absences
//         setupFilterListeners(ressourcesAbs, filterData, updateGanttChartAbs, 'abs');
       
//         // Initialisation des filtres au chargement
//         setupFilterListeners(ressources, filterData, updateGanttChartColDev, 'dev');
//         setupFilterListeners(ressourcesProj, filterData, updateGanttChartColProj, 'proj');
//         setupFilterListeners(ressourcesProj, filterData, updateGanttChartProjCom, 'projcom');
//         setupFilterListeners(ressourcesComm, filterData, updateGanttChartCodeCol, 'comm');
//         setupFilterListeners(ressourcesProjAbs, filterData, updateGanttChartProjAbs, 'projabs');
        
        
//         // filterResourcesByAvailability(); // Mise à jour initiale des ressources
        

//     } catch (error) {
//         console.error("Erreur lors de l'initialisation :", error);
//         showLoader(false);
//     }
// })();






// strat file js to do 
</script>
<style>
[class^="custom-gradient-PROJ-"] {
    height: 5px;
}
.gCurDate {
    z-index: 10!important
}

#tabs6 .gtasktable th:nth-child(2), .gtasktable td:nth-child(2) {
    width: 40%!important;
}
#tabs6 .gres {
    text-align: justify;
}

#tabs6 .gmainleft {
    overflow: hidden;
    flex: 0 0 25%;
}

#fixedHeader_GanttChartDIV6 {
    width: 62.59%!important;
}
</style>
<script>
// // Fonction principale pour traiter les ressources et générer les membres pour le Gantt
// function processRessourcesProjAbs(ressources, filteredData) {
//     // const members = [];
//     let memberCursor = 0;

//     const ganttMode = document.getElementById('ganttMode');
//     const ganttContainer = document.getElementById('ganttContainer');

//     // Variable pour stocker les ressources filtrées
//     let filteredResources = ressources; // Initialisation avec toutes les ressources

//     // Génération initiale des membres (par défaut avec toutes les ressources)
//     const initialMembers = generateMembersProjAbs(filteredResources, filteredData);
//     // console.log("Membres initiaux générés :", initialMembers);

//     // Tri : non-structuré d'abord
//         initialMembers.sort((a, b) => {
//             const aIsStruct = a.member_str ? 1 : 0;
//             const bIsStruct = b.member_str ? 1 : 0;
//             return aIsStruct - bIsStruct;
//         });

//     // Membres traités
//     return initialMembers;
// }


// function generateMembersProjAbs(ressources, filteredData) {
//     let members = [];
//     let memberCursor = 0;
//     let dynamicStyles = "";

//     // if (!Array.isArray(ressources)) {
//     //     console.error("Erreur : 'ressources' est undefined ou n'est pas un tableau !");
//     //     return [];
//     // }

//     let structurelLabels = [];
//     let structureMembers = [];
//     let startMin = null;
//     let endMax = null;
   
//     if(!ressources) {
//         // Si les ressources sont nulles, afficher un message
//         console.log("Aucune donnée disponible");
//     // document.getElementById("message-container").innerHTML = "Aucune donnée disponible";
//     }else{
//         ressources.forEach(val => {
//             const condition = (val.fk_projet !== null || typeof val.fk_projet === "undefined");

//             if (condition) {
//                 const idParent = val.s ? val.fk_projet : `-${val.fk_projet}`;
//                 let todayPlusOneYear = new Date();
//                 todayPlusOneYear.setFullYear(todayPlusOneYear.getFullYear() + 1);
//                 let formattedDate = todayPlusOneYear.toISOString().split('T')[0];
//                 // isStructurel = val.str == 1;
//                 // structurelLabel = isStructurel ? `[Structure] ${val.projet_ref}` : val.projet_ref;
//                 // console.log('Label structurel : ', structurelLabel);
//                 let isStructurel = val.str == 1;
//                 let cleanRef = (val.projet_ref || '').replace(/\[Structure\]\s*/i, '').trim(); 
//                 let badgeStructure = `<span class="badge badge-warning" style="margin-right: 6px; background-color: #f4b300; color: #000; border-radius: 6px; padding: 2px 6px; font-weight: bold;">Structurel</span>`;
//                 let structurelLabel = isStructurel ? `${badgeStructure}${cleanRef}` : cleanRef;
               
//                 const member = {
//                     member_id: val.fk_projet,
//                     member_idref: val.idref,
//                     member_alternate_id: memberCursor + 1,
//                     member_member_id: val.fk_projet,
//                     member_name_html: val.name_html,
//                     member_projet_ref: structurelLabel,
//                     member_parent: idParent,
//                     member_str: isStructurel,
//                     member_is_group: 0,
//                     member_css: '', // Classe CSS générée dynamiquement
//                     member_milestone: '0',
//                     member_name: val.name_html,
//                     member_start_date: formatDate(val.date_start, 'yyyy-MM-dd'),
//                     member_end_date: val.date_end 
//                     ? formatDate(val.date_end, 'yyyy-MM-dd') 
//                     : formattedDate,

//                     member_color: 'b4d1ea',
//                     member_resources: '',
//                     member_rsc_detail: ''
//                 };

//                 if (!val.date_start) return;

//                 let start = new Date(val.date_start);
//                 let end = val.date_end ? new Date(val.date_end) : new Date();

//                 if (!startMin || start < startMin) startMin = start;
//                 if (!endMax || end > endMax) endMax = end;

//                 // let gradientCss = generateGradientForAbsences(val.periodes, val.date_start, member.member_end_date, val.idref);
//                 // const className = `custom-gradient-${val.idref}-${memberCursor}`;
//                 // dynamicStyles += `.${className} { width: 200px; height: 13px; background: ${gradientCss}; }\n`;
//                 let result = generateGradientForAbsences(val.periodes, val.date_start, member.member_end_date, val.idref);
//                 const className = `custom-gradient-${val.idref}-${memberCursor}`;
//                 member.member_resources = result.badges; // On affiche les badges
//                 member.member_rsc_detail = result.rsc;

//                 dynamicStyles += `.${className} { 
//                     height: 13px; 
//                     background: ${result.gradient}; 
//                 }\n`;
//                 // width: 200px; 
//                 // border: 1px solid rgba(0, 0, 0, 0.2); 
//                 // box-shadow: rgba(0, 0, 0, 0.3); 
//                 // background-blend-mode: multiply; 
//                 member.member_css = className;
//                 member.projet_ref = structurelLabel;

//                 // members.push(member);
//                 // Tri 
//                 if (isStructurel) {
//                     structureMembers.push(member);
//                 } else {
//                     members.push(member);
//                 }
//                 memberCursor++;
//             }
//         });
//     }

//     injectDynamicStyles(dynamicStyles);
    
//     // Concatènation des non-structurels puis les structurels
//     members = members.concat(structureMembers);

    
//     // Mise à jour les parents des tâches
//     members.forEach(member => {
//         const parent = members.find(m => m.member_id === member.member_parent);
//         member.member_parent_alternate_id = parent ? parent.member_alternate_id : member.member_parent;
//     });

//     return members;
// }

// function generateGradientForAbsences(absences, startDate, endDate, idref) {
//     if (!Array.isArray(absences) || absences.length === 0) {
//         let defaultColor = getDefaultColor(idref);
//         return {
//             // gradient: `linear-gradient(to right, ${defaultColor} 0% 100%)`,
//             // Pour rendre aussi compatible avec FireFox Navigateur 
//             gradient: `linear-gradient(to right, ${defaultColor} 0%, ${defaultColor} 100%)`,
//             badges: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333; text-align: left;" title="Aucune absence enregistrée">Aucune absence</span>`,
//             rsc: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333; text-align: left;" title="Aucune absence enregistrée">Aucune absence</span>`
//         };
//     }

//     // Tri des absences par date croissante
//     absences.sort((a, b) => new Date(a.date_start) - new Date(b.date_start));

//     let colors = [];
//     let rsc = [];
//     let totalDays = (new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24);

//     // console.log("ID", idref, "Start:", startDate, "End:", endDate, "TotalDays:", totalDays);  

//     // if (totalDays <= 0) {
//     //     return { gradient: "white", badges: "" };
//     // }

   


//     let lastEndPercent = 0;
//     let absenceHorsPeriode = false;
//     let hasAbsences = false; // Variable pour savoir s'il y a au moins une absence

    
//     absences.forEach(abs => {
//         let startDiff = (new Date(abs.date_start) - new Date(startDate)) / (1000 * 60 * 60 * 24);
//         let endDiff = (new Date(abs.date_end) - new Date(startDate)) / (1000 * 60 * 60 * 24);

//         let startPercent = (startDiff / totalDays) * 100;
//         let endPercent = (endDiff / totalDays) * 100;

//         startPercent = Math.max(0, Math.min(100, startPercent));
//         endPercent = Math.max(0, Math.min(100, endPercent));
     

//         // Si l'absence est totalement hors période
//         if (endDiff < 0 || startDiff > totalDays) {
//             absenceHorsPeriode = true;
//             return;
//         }

//         let color = getStatusColor(abs.status);
//         let badgeLabel = getStatusLabel(abs.status);

//         // Un espace entre deux absences si nécessaire
//         // if (startPercent > lastEndPercent) {
//         //     colors.push(`${getDefaultColor(idref)} ${lastEndPercent.toFixed(2)}% ${startPercent.toFixed(2)}%`);
//         // }

//         if (startPercent >= lastEndPercent) {
//             colors.push(`${getDefaultColor(idref)} ${lastEndPercent.toFixed(2)}% ${startPercent.toFixed(2)}%`);
//         }

//         // Ajout de la couleur de l'absence
//         // colors.push(`${color} ${startPercent.toFixed(2)}% ${endPercent.toFixed(2)}%`);
//         colors.push(`${color} ${startPercent.toFixed(2)}%, ${color} ${endPercent.toFixed(2)}%`);
//         lastEndPercent = endPercent;

//         // Au moins une absence
//         hasAbsences = true;

//         // Ajout du détail de l'absence dans "rsc"
//         rsc.push(`
//             <span class="badge badge-info" 
//                 style="background-color: ${color}; text-align: left; border-radius: 12px;
//                     box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); display: inline-flex;"
//                 title="Absence : ${abs.conge_label}
//                     Période : ${abs.date_start} → ${abs.date_end}
//                     Durée : ${abs.nb_open_day_calculated} jours
//                     Statut : ${badgeLabel}">
//                 <span style="font-weight: bold; margin-right: 5px;">${badgeLabel}</span> 
//                 <span style="opacity: 0.8; font-size: 0.85em; background: rgba(0, 0, 0, 0.1); padding: 1px 3px; border-radius: 8px;">
//                     ${abs.conge_label}
//                 </span>
//                 <span style="margin-left: 8px; font-size: 0.9em;">(${abs.nb_open_day_calculated} jours)</span>
//             </span>
//         `);

//     });

//     // Ajout badge spécifique si absences hors période
//     let badges = "";
//     if (hasAbsences) {
//         badges += `
//             <span class="badge badge-info" 
//                 style="background-color: RGB(0, 117, 168, 0.8); color: white; text-align: left;" 
//                 title="Ce salarié a des absences dans la période du projet">
//                 Présence d'absences
//             </span>
//         `;
//     }
    
//     if (absenceHorsPeriode) {
//         badges += `
//             <span class="badge badge-warning" 
//                 style="background-color: #ff9800; color: white; text-align: left;" 
//                 title="Certaines absences de ce salarié ne sont pas dans la période du projet">
//                 Absences hors période
//             </span>
//         `;
//     }

//     // Couleur par défaut si nécessaire
//     if (lastEndPercent < 100) {
//         colors.push(`${getDefaultColor(idref)} ${lastEndPercent.toFixed(2)}% 100%`);
//     }

//     return {
//         gradient: `linear-gradient(to right, ${colors.join(", ")})`,
//         badges: badges,
//         rsc: rsc.join(" ")
//     };
// }

// function generateGradientForAbsences2(absences, startDate, endDate, idref) {
//     if (!Array.isArray(absences) || absences.length === 0) {
//         let defaultColor = getDefaultColor(idref);
//         return {
//             // gradient: `linear-gradient(to right, ${defaultColor} 0% 100%)`,
//             // Pour rendre aussi compatible avec FireFox Navigateur 
//             gradient: `linear-gradient(to right, ${defaultColor} 0%, ${defaultColor} 100%)`,
//             badges: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333; text-align: left;" title="Aucune absence enregistrée">Aucune absence</span>`,
//             rsc: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333; text-align: left;" title="Aucune absence enregistrée">Aucune absence</span>`
//         };
//     }

//     // Tri des absences par date croissante
//     absences.sort((a, b) => new Date(a.date_start) - new Date(b.date_start));

//     let colors = [];
//     let rsc = [];
//     let totalDays = (new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24);

//     let lastEndPercent = 0;
//     let absenceHorsPeriode = false;
//     let hasAbsences = false;

//     absences.forEach(abs => {
//         let startAbs = new Date(abs.date_start);
//         let endAbs = new Date(abs.date_end);

//         // Calcul de la différence en jours, en tenant compte des heures milliseconde en jours (1000 * 60 * 60 * 24)
//         let startDiff = (startAbs - new Date(startDate)) / (1000 * 60 * 60 * 24);
//         let endDiff = (endAbs - new Date(startDate)) / (1000 * 60 * 60 * 24);

//         // Calcul de les fractions de jour à partir des heures
//         // const startHours = startAbs.getHours();
//         // const endHours = endAbs.getHours();
//         // console.log('startAbs', startAbs);
//         // console.log('endAbs', endAbs);
//         // // Conversion de l'heure en fraction de jour (exemple: 12h = 0.5 jour)
//         // startDiff += startHours / 24;
//         // endDiff += endHours / 24;

//         let startPercent = (startDiff / totalDays) * 100;
//         let endPercent = (endDiff / totalDays) * 100;

//         startPercent = Math.max(0, Math.min(100, startPercent));
//         endPercent = Math.max(0, Math.min(100, endPercent));

//         // Si l'absence est totalement hors période
//         if (endDiff < 0 || startDiff > totalDays) {
//             absenceHorsPeriode = true;
//             return;
//         }

//         let color = getStatusColor(abs.status);
//         let badgeLabel = getStatusLabel(abs.status);

//         // Un espace entre deux absences si nécessaire
//         if (startPercent > lastEndPercent) {
//             colors.push(`${getDefaultColor(idref)} ${lastEndPercent.toFixed(2)}% ${startPercent.toFixed(2)}%`);
//         }

//         // Ajout de la couleur de l'absence
//         // colors.push(`${color} ${startPercent.toFixed(2)}% ${endPercent.toFixed(2)}%`);
//         // Pour rendre compatible avec Navigateur Firefox 
//         colors.push(`${color} ${startPercent.toFixed(2)}%, ${color} ${endPercent.toFixed(2)}%`);
//         lastEndPercent = endPercent;

//         // Au moins une absence
//         hasAbsences = true;

//         // Ajout du détail de l'absence dans "rsc"
//         rsc.push(`
//             <span class="badge badge-info" 
//                 style="background-color: ${color}; text-align: left; border-radius: 12px;
//                     box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); display: inline-flex;"
//                 title="Absence : ${abs.conge_label}
//                     Période : ${abs.date_start} → ${abs.date_end}
//                     Durée : ${abs.nb_open_day_calculated} jours
//                     Statut : ${badgeLabel}">
//                 <span style="font-weight: bold; margin-right: 5px;">${badgeLabel}</span> 
//                 <span style="opacity: 0.8; font-size: 0.85em; background: rgba(0, 0, 0, 0.1); padding: 1px 3px; border-radius: 8px;">
//                     ${abs.conge_label}
//                 </span>
//                 <span style="margin-left: 8px; font-size: 0.9em;">(${abs.nb_open_day_calculated} jours)</span>
//             </span>
//         `);
//     });

//     let badges = "";
//     if (hasAbsences) {
//         badges += `
//             <span class="badge badge-info" 
//                 style="background-color: RGB(0, 117, 168, 0.8); color: white; text-align: left;" 
//                 title="Ce salarié a des absences dans la période du projet">
//                 Présence d'absences
//             </span>
//         `;
//     }

//     if (absenceHorsPeriode) {
//         badges += `
//             <span class="badge badge-warning" 
//                 style="background-color: #ff9800; color: white; text-align: left;" 
//                 title="Certaines absences de ce salarié ne sont pas dans la période du projet">
//                 Absences hors période
//             </span>
//         `;
//     }

//     if (lastEndPercent < 100) {
//         colors.push(`${getDefaultColor(idref)} ${lastEndPercent.toFixed(2)}% 100%`);
//     }

//     return {
//         gradient: `linear-gradient(to right, ${colors.join(", ")})`,
//         badges: badges,
//         rsc: rsc.join(" ")
//     };
// }


// function getDefaultColor(idref) {
//     return idref === "PROJ" ? "rgba(0, 0, 0, 0.4)" : "rgb(108, 152, 185)";
// }

// function getStatusColor(status) {
//     return status == 3 ? "rgba(60, 120, 20, 0.85)" : // Validé (Vert)
//            status == 6 ? "rgba(180, 30, 30, 0.9)" : // Appro. 2 (Rouge)
//            status == 2 ? "rgba(255, 200, 0, 0.85)" : // Appro. 1 (Jaune)
//            "black";
// }

// function getStatusLabel(status) {
//     return status == 3 ? "Validé" :
//            status == 6 ? "Appro. 2" :
//            status == 2 ? "Appro. 1" : "";
// }

// function formatDateTimeFR(date) {
//     return new Date(date).toLocaleString("fr-FR", { 
//         day: "2-digit", month: "long", year: "numeric", 
//         hour: "2-digit", minute: "2-digit", second: "2-digit"
//     });
// }

// function injectDynamicStyles(css) {
//     let styleTag = document.getElementById("dynamic-gantt-styles");
    
//     if (!styleTag) {
//         styleTag = document.createElement("style");
//         styleTag.id = "dynamic-gantt-styles";
//         document.head.appendChild(styleTag);
//     }

//     styleTag.innerHTML = css;
// }
// // Fonction pour mettre à jour le Gantt avec les données filtrées
// function updateGanttChartProjAbs(ressources, filteredData) {
//     // Traitement des ressources et mise à jour du Gantt
//     const processedMembers = processRessourcesProjAbs(ressources, filteredData); 

//     // Mise à jour du Gantt avec les nouveaux membres
//     setupGanttChartProjAbs(processedMembers);
// }

// function setupGanttChartProjAbs(members) {
//     if (!Array.isArray(members)) {
//         console.error("Erreur : 'members' n'est pas un tableau.");
//         return;
//     }

//     var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV6'), 'month');

//     var booShowRessources = 1;
// 	var booShowDurations = 1;
// 	var booShowComplete = 1;
// 	var barText = "Resource";
// 	var graphFormat = "day";
	
// 	g.setShowRes(0); 		// Show/Hide Responsible (0/1)
// 	g.setShowDur(0); 		// Show/Hide Duration (0/1)
// 	g.setShowComp(0); 		// Show/Hide % Complete(0/1)
// 	g.setShowStartDate(0); 	// Show/Hide % Complete(0/1)
// 	g.setShowEndDate(0); 	// Show/Hide % Complete(0/1)
// 	g.setShowTaskInfoLink(1);
// 	g.setFormatArr("day","week","month", "quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
// 	g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
// 	g.setUseFade(0);
// 	g.setDayColWidth(20);
// 	/* g.setShowTaskInfoLink(1) */
// 	g.addLang('<?php print $langs->getDefaultLang(1); ?>', vLangs['<?php print $langs->getDefaultLang(1); ?>']);
// 	g.setLang('<?php print $langs->getDefaultLang(1); ?>');
    
//     if (g.getDivId() != null) {
//         let level = 0;
//         const tnums = members.length;
//         let old_member_id = 0;
        
       
//         // Parcourir les membres triés
//         for (let tcursor = 0; tcursor < tnums; tcursor++) {
//             const t = members[tcursor];

//             if (!old_member_id || old_member_id !== t['member_member_id']) {
//                 const tmpt = {
//                     member_id: `-${t['member_member_id']}`,
//                     member_alternate_id: `-${t['member_member_id']}`,
//                     member_name: t['member_projet_ref'],
//                     member_idref: t['member_idref'],
//                     member_resources: 'ABS',
//                     member_start_date: '',
//                     member_start_date2: '',
//                     member_end_date: '',
//                     member_is_group: 1,
//                     member_position: 0,
//                     member_css: 'ggroupblack',
//                     member_milestone: 0,
//                     member_parent: 0,
//                     member_parent_alternate_id: 0,
//                     member_notes: '',
//                     member_planned_workload: 0
//                 };

//                 const result = constructGanttLineProjAbs(members, tmpt, [], 0, t['member_member_id']);
//                 // console.log("Groupe ajouté :", result);

//                 g.AddTaskItem(new JSGantt.TaskItem(
//                     result.id,
//                     result.name,
//                     result.start,
//                     result.end,
//                     result.class,
//                     result.link,
//                     result.milestone,
//                     result.resource,
//                     result.complete,
//                     result.group,
//                     result.parent,
//                     result.open,
//                     result.depends,
//                     result.caption,
//                     result.note,
//                     g
//                 ));

//                 old_member_id = t['member_member_id'];
//             }

//             if (t["member_parent"] <= 0) {
//                 const result = constructGanttLineProjAbs(members, t, [], level, t['member_member_id']);
//                 // console.log("Tâche ajoutée :", result);
          
//                 g.AddTaskItem(new JSGantt.TaskItem(
//                     result.id,
//                     result.name,
//                     result.start,
//                     result.end,
//                     result.class,
//                     result.link,
//                     result.milestone,
//                     result.resource,
//                     result.complete,
//                     result.group,
//                     result.parent,
//                     result.open,
//                     result.depends,
//                     result.caption,
//                     result.note,
//                     g
//                 ));

//                 findChildGanttLine(members, t["member_id"], [], level + 1);
//             }
//         }
       
//         g.Draw(jQuery("#tabs6").width() - 40);
//         setTimeout(() => g.DrawDependencies(), 100);
//     } else {
//         alert("Graphique Gantt introuvable !");
//     }
// }


// function constructGanttLineProjAbs(members, member, memberDependencies = [], level = 0, memberId = null) {
//     const dateFormatInput62 = "YYYY-MM-DD";
  
//     let startDate = member["member_start_date"];
//     let endDate = member["member_end_date"] || startDate;

//     startDate = formatDate(startDate, dateFormatInput62);
//     endDate = formatDate(endDate, dateFormatInput62);

//     const resources = member["member_resources"] || '';
//     const parent = memberId && level < 0 ? `-${memberId}` : member["member_parent_alternate_id"];
//     const percent = member['member_percent_complete'];
//     const css = member['member_css'];
	
// 	const name = member['member_name'];
	
   
//     const lineIsAutoGroup = member["member_is_group"];
//     const dependency = '';
//     const memberIdAlt = member["member_alternate_id"];

//     let note = member["member_rsc_detail"] || '';
//     // note += `\nPlan de charge : Cliquez sur Plus d\'informations pour être dirigé vers les contacts`;
//     note += `&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`;

// 	let link = ''; 
// 	var DOL_URL_ROOT = "<?php echo DOL_URL_ROOT; ?>";

// 	if (!member.hasOwnProperty("member_idref")) {
// 		// Si "member_idref" n'existe pas, on laisse link vide
// 		link = '';
// 	} else if (member["member_idref"] === "CO") {
// 		link = DOL_URL_ROOT + '/commande/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
// 	} else if (member["member_idref"] === "PR") {
// 		link = DOL_URL_ROOT + '/comm/propal/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
// 	} else if (member["member_idref"] === "PROJ") {
// 		link = DOL_URL_ROOT + '/projet/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
// 	}
   
//     return {
//         id: memberIdAlt,
//         name: name,
//         start: startDate,
//         end: endDate,
//         class: css,
//         link: link,
//         milestone: member['member_milestone'],
//         resource: resources,
//         complete: percent >= 0,
//         group: lineIsAutoGroup,
//         parent: parent,
//         open: 1,
//         depends: dependency,
//         caption: lineIsAutoGroup === 0 && percent >= 0 ? `${percent}%` : '',
//         note: note
//     };
// }


// // Simule la conversion de date (Dolibarr `dol_print_date`)
// function formatDate(date, format) {
// 	// Implémentez une fonction de conversion de date au besoin (ex: moment.js ou day.js)
// 	return date; // Placeholder
// }

// // échappe les chaénes pour JS
// function escapeJs(str) {
// 	return str.replace(/'/g, "\\'").replace(/\n/g, "\\n");
// }

// // Convertit les secondes en heures/minutes
// function convertSecondsToTime(seconds, format) {
// 	const hours = Math.floor(seconds / 3600);
// 	const minutes = Math.floor((seconds % 3600) / 60);
// 	return `${hours}h ${minutes}min`;
// }
// end file js to do







// function adjustGLineVHeight() {
//     // Récupération des conteneurs de tables spécifiés
//     const tableContainers = document.querySelectorAll('#GanttChartDIV, #GanttChartDIV1, #GanttChartDIV2, #GanttChartDIV3, #GanttChartDIV4, #GanttChartDIV5, #GanttChartDIV6');

//     // Vérification si des conteneurs sont trouvés
//     if (!tableContainers || tableContainers.length === 0) {
//          console.warn('Aucun conteneur de table trouvé.'); 
//         return;
//     }

//     // Parcourir chaque conteneur pour calculer et appliquer la hauteur
//     tableContainers.forEach(tableContainer => {
//         if (!tableContainer) {
//              console.warn('Un conteneur de table est introuvable.'); 
//             return;
//         }

//         // Calculer la hauteur totale de la table
//         let totalHeight = tableContainer.offsetHeight;

//         if (totalHeight === 0) {
//             //  console.warn(`La hauteur de la table avec ID "${tableContainer.id}" est calculée comme 0. Vérifiez la visibilité ou le chargement des éléments.`); 
//             return;
//         }

//         // Réduction de la hauteur par 3 cm (1 cm = 37.7952755906 px)
//         const reductionInPixels = 1.5 * 37.7952755906; // Conversion de cm en pixels
//         totalHeight = Math.max(0, totalHeight - reductionInPixels); 

//         // Récupération de tous les éléments ayant la classe glinev
//         const gLineVElements = document.querySelectorAll('.glinev');

//         if (gLineVElements.length === 0) {
//             // console.warn('Aucun élément trouvé avec la classe .glinev'); 
//             return;
//         }

//         //  Hauteur réduite à chaque élément de classe glinev
//         gLineVElements.forEach(element => {
//             element.style.height = `${totalHeight}px`;
//         });
//     });
// }



// // Appel de la fonction après que tout le contenu est chargé
// document.addEventListener('DOMContentLoaded', () => {
//     adjustGLineVHeight();
//     // Si des éléments sont ajoutés ou changent dynamiquement
//     const observer = new MutationObserver(() => {
//         adjustGLineVHeight();
//     });

//     // ObservATION des changements dans le conteneur principal
//     const target = document.body;
//     observer.observe(target, { childList: true, subtree: true });
// });

// document.addEventListener('DOMContentLoaded', function () {
//     const ganttHeader = document.querySelector('.gcharttableh'); 
//     const navbar = document.querySelector('#topmenu');

//     if (ganttHeader) {
//         // Si l'en-tête du Gantt est trouvé

//         let navbarHeight = 0;

//         if (navbar) {
//             // Si la barre de navigation est trouvée, on récupère sa hauteur
//             navbarHeight = navbar.offsetHeight;
//         } 

//         // Application du positionnement fixe à l'en-tête du Gantt
//         window.addEventListener('scroll', function () {
//             if (window.scrollY > navbarHeight) {
//                 // Si le défilement dépasse la hauteur de la barre de navigation
//                 ganttHeader.style.position = 'fixed';
//                 ganttHeader.style.top = `${navbarHeight}px`;  
//                 ganttHeader.style.zIndex = '1000';
//                 ganttHeader.style.backgroundColor = '#fff'; 
//             } else {
//                 // L'en-tête dans son état normal au début d la page
//                 ganttHeader.style.position = 'relative';
//                 ganttHeader.style.top = '0';
//             }
//         });
//     } 
// });
</script>
<?php