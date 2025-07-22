<?php
?>
<style>
.year-selector-container {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: Arial, sans-serif;
  font-size: 12px;
  color: #444;
  margin: 10px 5px;
}


/* Style pour l'icône Font Awesome */
.year-selector-container label i {
  font-size: 14px;
  color: #0a659e; 
}

/* Style pour le select */
.styled-select {
  /* padding: 8px 10px; */
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #f9f9f9;
  font-size: 14px;
  color: #333;
  margin: 5px;
}

.styled-select:hover {
  border-color: #0a659e;
  box-shadow: 0 0 5px rgba(10, 101, 158, 0.3);
}


.styled-select:focus {
  outline: none;
  border-color: #0a659e;
  box-shadow: 0 0 5px rgba(10, 101, 158, 0.6);
}

.styled-select option {
  cursor: pointer;
}

#yearSelectionContainer select {
    color: #0056b3;
    font-weight: 400;
}

    /* calandar */
    .date-picker {
      position: relative;
      display: inline-block;
    }

    input[type="text"] {
      border: 1px solid #ddd;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 14px;
      cursor: pointer;
      width: 180px;
      background-color: #fff;
      transition: border-color 0.3s ease;
    }

    input[type="text"]:focus {
      border-color: #0063a6;
      outline: none;
    }

    .calendar {
      display: none;
      position: absolute;
      top: 40px;
      left: 0;
      background: #fff;
      border: 1px solid #ddd;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      padding: 10px;
      border-radius: 6px;
    }

    .calendar.active {
      display: block;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      background-color: #0063a6;
      color: #fff;
      padding: 8px 12px;
      border-radius: 4px 4px 0 0;
    }

    .calendar-header button {
      background: #005294;
      color: #fff;
      border: none;
      padding: 5px 10px;
      font-size: 14px;
      border-radius: 3px;
      cursor: pointer;
    }

    .calendar-header button:hover {
      background: #003c71;
    }

    .calendar-header span {
      font-weight: bold;
    }

    .days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
      text-align: center;
    }

    .days span {
      padding: 5px;
      font-size: 14px;
      color: #333;
      background-color: #f4f4f4;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .days span:hover {
      background-color: #0063a6;
      color: #fff;
    }

    .days .selected {
      background-color: #0063a6;
      color: #fff;
    }

    .days span.disabled {
      background-color: #e9e9e9;
      color: #aaa;
      cursor: not-allowed;
    }

    .ui-datepicker-trigger {
      padding:3px;
      width: 15px;
    }

    
</style>
<script>



// $(document).ready(function () {
//     // État global pour suivre le type de graphique actif
//     let currentChartType = 'pie';

//     // Fonction pour basculer entre les types de graphiques
//     function switchChartType(newChartType) {
//         if (currentChartType === newChartType) {
//             console.log(`Le graphique est déjà de type ${newChartType}.`);
//             return; // Si le type est le même, ne rien faire
//         }

//         // Mettre à jour le type de graphique actuel
//         currentChartType = newChartType;

//         // Mettre à jour l'affichage des icônes (effet visuel)
//         updateButtonIcons(newChartType);

//         // Actualiser le graphique en appelant la fonction appropriée
//         fetchData((data) => createChartType(data, newChartType));
//     }

//     function switchChartTypeEvol(newChartType) {
//         if (currentChartType === newChartType) {
//             console.log(`Le graphique est déjà de type ${newChartType}.`);
//             return; // Si le type est le même, ne rien faire
//         }

//         // Meise à jour le type de graphique actuel
//         currentChartType = newChartType;

//         // Meise à jour l'affichage des icônes (effet visuel)
//         updateButtonIcons(newChartType);

//         // Actualiser le graphique en appelant la fonction appropriée
//         fetchDataEvol((data) => createChartType(data, newChartType));
//     }

//     // Fonction pour mettre à jour les icônes des boutons
//     function updateButtonIcons(activeType) {
//         // Réinitialiser toutes les icônes
//         $('#toggleButton').removeClass('active');
//         $('#toggleButtonBar').removeClass('active');
//         $('#toggleButtonLine').removeClass('active');

//         // Ajouter la classe active à l'icône correspondant
//         if (activeType === 'pie') {
//             $('#toggleButton').addClass('active');
//         } else if (activeType === 'bar') {
//             $('#toggleButtonBar').addClass('active');
//         } else if (activeType === 'line') {
//             $('#toggleButtonLine').addClass('active');
//         }
//     }

//     // Gestion des clics sur les boutons
//     $('#toggleButton').click(() => switchChartType('pie'));
//     $('#toggleButtonBar').click(() => switchChartType('bar'));
//     $('#toggleButtonLine').click(() => switchChartTypeEvol('line'));



//     // Initialisation : activer l'icône du graphique par défaut
//     updateButtonIcons(currentChartType);
//     displayYearSelection('pie');
//     fetchData((data) => createChartType(data, 'pie'));
// });

// // Calandar display 
// document.addEventListener('DOMContentLoaded', function () {
//     const dateDebutInput = document.getElementById('date-debut');
//     const dateFinInput = document.getElementById('date-fin');
//     const calendarDebut = document.getElementById('calendar-debut');
//     const calendarFin = document.getElementById('calendar-fin');
//     const currentMonthYearDebut = document.getElementById('current-month-year-debut');
//     const currentMonthYearFin = document.getElementById('current-month-year-fin');
//     const daysDebut = document.getElementById('days-debut');
//     const daysFin = document.getElementById('days-fin');

//     // Date par défaut pour le début (1er janvier de l'année en cours)
//     const currentDate = new Date();
//     const firstDayOfYear = new Date(currentDate.getFullYear(), 0, 1);

//     // Date par défaut pour la fin (date actuelle)
//     const today = new Date();

//     let selectedDateDebut = firstDayOfYear;
//     let selectedDateFin = today;
//     let currentMonthDebut = selectedDateDebut.getMonth();
//     let currentYearDebut = selectedDateDebut.getFullYear();
//     let currentMonthFin = selectedDateFin.getMonth();
//     let currentYearFin = selectedDateFin.getFullYear();

//     // Fonction pour afficher les jours dans le calendrier
//     function renderCalendar(inputType) {
//         let currentMonth, currentYear, selectedDate, calendar, currentMonthYear, daysContainer;

//         if (inputType === 'debut') {
//             currentMonth = currentMonthDebut;
//             currentYear = currentYearDebut;
//             selectedDate = selectedDateDebut;
//             calendar = calendarDebut;
//             currentMonthYear = currentMonthYearDebut;
//             daysContainer = daysDebut;
//         } else {
//             currentMonth = currentMonthFin;
//             currentYear = currentYearFin;
//             selectedDate = selectedDateFin;
//             calendar = calendarFin;
//             currentMonthYear = currentMonthYearFin;
//             daysContainer = daysFin;
//         }

//         daysContainer.innerHTML = '';
//         const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
//         const lastDateOfMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

//         currentMonthYear.textContent = `${new Date(currentYear, currentMonth).toLocaleString('fr-FR', { month: 'long' })} ${currentYear}`;

//         // Ajout des jours vides avant le début du mois
//         for (let i = 0; i < (firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1); i++) {
//             daysContainer.innerHTML += '<span class="disabled"></span>';
//         }

//         // Ajout des jours du mois
//         for (let day = 1; day <= lastDateOfMonth; day++) {
//             const dayElement = document.createElement('span');
//             dayElement.textContent = day;
//             if (
//                 day === selectedDate.getDate() &&
//                 currentMonth === selectedDate.getMonth() &&
//                 currentYear === selectedDate.getFullYear()
//             ) {
//                 dayElement.classList.add('selected');
//             }
//             dayElement.addEventListener('click', () => {
//                 if (inputType === 'debut') {
//                     selectedDateDebut = new Date(currentYear, currentMonth, day);
//                     dateDebutInput.value = selectedDateDebut.toLocaleDateString('fr-FR');
//                     // Vérification et ajustement de la date de fin si nécessaire
//                     adjustEndDate();
//                     renderCalendar('debut');
//                 } else {
//                     selectedDateFin = new Date(currentYear, currentMonth, day);
//                     dateFinInput.value = selectedDateFin.toLocaleDateString('fr-FR');
//                     adjustStartDate();  // Appel pour ajuster la date de début si nécessaire
//                 }
//                 calendar.classList.remove('active');
//                 renderCalendar(inputType);
                
//             });
//             daysContainer.appendChild(dayElement);
//         }
//     }

//     // Fonction pour ajuster la date de fin si elle est inférieure à la date de début
//     function adjustEndDate() {
//         if (selectedDateFin < selectedDateDebut) {
//             // Si la date de fin est avant la date de début, la date de fin devient la date de début + 1 jour
//             selectedDateFin = new Date(selectedDateDebut);
//             selectedDateFin.setDate(selectedDateDebut.getDate() + 1);
//             dateFinInput.value = selectedDateFin.toLocaleDateString('fr-FR');
//             // Mise à jour du calendrier de la date de fin
//             currentMonthFin = selectedDateFin.getMonth();
//             currentYearFin = selectedDateFin.getFullYear();
//             renderCalendar('fin');
//         }
//     }

//     // Fonction pour ajuster la date de début si la date de fin est inférieure à la date de début
//     // function adjustStartDate() {
//     //     if (selectedDateFin < selectedDateDebut) {
//     //         console.log("Ajustement de la date de début", selectedDateDebut, selectedDateFin);
//     //         // Ajustement de la date de début
//     //         selectedDateDebut = new Date(selectedDateFin);
//     //         selectedDateDebut.setDate(selectedDateFin.getDate() - 1);  // Mise à jour de la date de début avec la date de fin moins un jour
//     //         console.log("Nouvelle date début:", selectedDateDebut);
//     //         dateDebutInput.value = selectedDateDebut.toLocaleDateString('fr-FR');
//     //         currentMonthDebut = selectedDateDebut.getMonth();
//     //         currentYearDebut = selectedDateDebut.getFullYear();
//     //         renderCalendar('debut');
//     //     }
//     // }

//     // Gésion de l'affichage du calendrier
//     dateDebutInput.addEventListener('click', () => {
//         calendarDebut.classList.toggle('active');
//         renderCalendar('debut');
//     });

//     dateFinInput.addEventListener('click', () => {
//         calendarFin.classList.toggle('active');
//         renderCalendar('fin');
//     });

//     // Passage au mois précédent ou suivant
//     document.getElementById('prev-month-debut').addEventListener('click', () => {
//         currentMonthDebut--;
//         if (currentMonthDebut < 0) {
//             currentMonthDebut = 11;
//             currentYearDebut--;
//         }
//         renderCalendar('debut');
//     });

//     document.getElementById('next-month-debut').addEventListener('click', () => {
//         currentMonthDebut++;
//         if (currentMonthDebut > 11) {
//             currentMonthDebut = 0;
//             currentYearDebut++;
//         }
//         renderCalendar('debut');
//     });

//     document.getElementById('prev-month-fin').addEventListener('click', () => {
//         currentMonthFin--;
//         if (currentMonthFin < 0) {
//             currentMonthFin = 11;
//             currentYearFin--;
//         }
//         renderCalendar('fin');
//     });

//     document.getElementById('next-month-fin').addEventListener('click', () => {
//         currentMonthFin++;
//         if (currentMonthFin > 11) {
//             currentMonthFin = 0;
//             currentYearFin++;
//         }
//         renderCalendar('fin');
//     });

//     // Fonction pour écouter les changements manuels dans les champs de date
//     dateDebutInput.addEventListener('input', () => {
//         const dateParts = dateDebutInput.value.split('/');
//         if (dateParts.length === 3) {
//             const [day, month, year] = dateParts;
//             selectedDateDebut = new Date(year, month - 1, day);
//             currentMonthDebut = selectedDateDebut.getMonth();
//             currentYearDebut = selectedDateDebut.getFullYear();
//             // Vérification et ajustement de la date de fin si nécessaire
//             adjustEndDate();
//             renderCalendar('debut');
//         }
//     });

//     dateFinInput.addEventListener('input', () => {
//         const dateParts = dateFinInput.value.split('/');
//         if (dateParts.length === 3) {
//             const [day, month, year] = dateParts;
//             selectedDateFin = new Date(year, month - 1, day);
//             currentMonthFin = selectedDateFin.getMonth();
//             currentYearFin = selectedDateFin.getFullYear();
//             // adjustStartDate();  // Vérification et ajustement de la date de début
//             renderCalendar('fin');
//         }
//     });

//     // Initialisation des calendriers
//     renderCalendar('debut');
//     renderCalendar('fin');
// });


// // Fonction pour fermer le calendrier lorsque l'on clique en dehors
// document.addEventListener('click', function (event) {
//     const datePickers = document.querySelectorAll('.date-picker');
    
//     datePickers.forEach(function (datePicker) {
//         const inputField = datePicker.querySelector('input');
//         const calendar = datePicker.querySelector('.calendar');

//         // Vérification si le clic est en dehors du champ de saisie ou du calendrier
//         if (!datePicker.contains(event.target)) {
//             calendar.classList.remove('active'); // Cache le calendrier
//         }
//     });
// });



// document.querySelector('.ui-datepicker-trigger').addEventListener('click', function() {
//         calendarDebut.classList.toggle('active');
//     });

//     // Gérer la sélection d'une date dans le calendrier
//     calendarDebut.addEventListener('click', function(event) {
//         const selectedDay = event.target;
        
//         if (selectedDay.tagName.toLowerCase() === 'span' && selectedDay.classList.contains('day')) {
//             const selectedDate = selectedDay.textContent;
//             const currentMonthYear = document.getElementById('current-month-year-debut').textContent;
//             const [month, year] = currentMonthYear.split(' ');  // Format de la date : "mois année"
//             const selectedDateFormatted = `${selectedDate}/${month}/${year}`;

//             // Mettre à jour le champ visible et caché
//             dateDebutInput.value = selectedDateFormatted;
//             dateDebutHidden.value = selectedDateFormatted;

//             // Fermer le calendrier après la sélection
//             calendarDebut.classList.remove('active');
//       }
// });



// function createChartType(data, type = 'pie') {
   
//   // Recréation du sélecteur d'année pour le type de graphique actuel
//   displayYearSelection(type);
  
//   // Réappliquer l'année sélectionnée si elle existe
//   if (selectedYear) {
//     const yearSelector = document.getElementById('yearSelector');
//     yearSelector.value = selectedYear;

//       const dateDebutInput = document.getElementById('date-debut');
//       const dateFinInput = document.getElementById('date-fin');
//       const calendarDebut = document.getElementById('calendar-debut');
//       const calendarFin = document.getElementById('calendar-fin');
//     // console.log('selected year', selectedYear);
//     // // Charger les données pour l'année sélectionnée avec le nouveau graphique
//     // getSelectedYear(selectedYear, type);
//     // Écouteur sur date-debut ou date-fin
//     dateDebutInput.addEventListener('change', () => {
//         if (dateDebutInput.value && dateFinInput.value) {
//             getSelectedYear(`${dateDebutInput.value} - ${dateFinInput.value}`, type);
//         }
//     });

//     dateFinInput.addEventListener('change', () => {
//         if (dateDebutInput.value && dateFinInput.value) {
//             getSelectedYear(`${dateDebutInput.value} - ${dateFinInput.value}`, type);
//         }
//     });

//     // Écouteur sur yearSelector
//     yearSelector.addEventListener('change', () => {
//         getSelectedYear(yearSelector.value, type);
//     });

//     getSelectedYear(selectedYear, type);
//   }


//   if (type === 'pie') {
//     // // Si les données sont vides ou invalides
//     createChart(data, selectedYear);
//   }
//   // Si c'est un graphique en barres inversées
//   else if (type === 'bar') {

//     createInvertedBarChart(data, selectedYear);
//   }
//   // Si c'est un graphique en line
//   else if (type === 'line') {
   
//     createLineChart(data, selectedYear);
//   }
//   // Si c'est un graphique en pie
//   // else if (type === 'pie') {
//   //   createLineChart(data);
//   //   // createPieChart(data);
//   // }
// }

// let selectedYear = new Date().getFullYear().toString(); // Variable globale pour garder la valeur de l'année sélectionnée
// // let lastSelection = { type: 'year', value: null }; // Variable pour stocker le dernier changement (par défaut, une année)
// let lastSelection = { type: 'year', value: new Date().getFullYear().toString() }; // Initialisation par défaut

// function createChartType(data, type = 'pie') {
//     // Recréation du sélecteur d'année pour le type de graphique actuel
//     displayYearSelection(type);

//     // Réappliquer l'année sélectionnée ou la plage de dates si elle existe
//     const yearSelector = document.getElementById('yearSelector');
//     const dateDebutInput = document.getElementById('date-debut');
//     const dateFinInput = document.getElementById('date-fin');

//     if (lastSelection.type === 'year') {
//         yearSelector.value = lastSelection.value || new Date().getFullYear();
        
//         getSelectedYear(yearSelector.value, type);
//     } else if (lastSelection.type === 'range') {
//         const [startDate, endDate] = lastSelection.value.split(' - ');
//         dateDebutInput.value = startDate;
//         dateFinInput.value = endDate;
//         getSelectedYear(lastSelection.value, type);
//     }

    

//     // Écouteur sur yearSelector
//     yearSelector.addEventListener('change', () => {
//         lastSelection = { type: 'year', value: yearSelector.value };
//         dateDebutInput.value = `01/01/${yearSelector.value}`;
//         // dateFinInput.value = `31/12/${yearSelector.value}`;
//         if (yearSelector.value === new Date().getFullYear().toString()) {
//             const today = new Date();
//             dateFinInput.value = today.toLocaleDateString('fr-FR'); 
//         } else {
//             dateFinInput.value = `31/12/${yearSelector.value}`; // Fin d'année si année sélectionnée différente de l'année actuelle
//         }
      
//         getSelectedYear(yearSelector.value, type);
//     });

//     // Écouteur sur date-debut ou date-fin
//     dateDebutInput.addEventListener('change', () => {
//         if (dateDebutInput.value && dateFinInput.value) {
//             lastSelection = { type: 'range', value: `${dateDebutInput.value} - ${dateFinInput.value}` };
//             yearSelector.value = 'En période';
//             getSelectedYear(lastSelection.value, type);
//         }
//     });

//     dateFinInput.addEventListener('change', () => {
//         if (dateDebutInput.value && dateFinInput.value) {
//             lastSelection = { type: 'range', value: `${dateDebutInput.value} - ${dateFinInput.value}` };
//             yearSelector.value = 'En période';
//             getSelectedYear(lastSelection.value, type);
//         }
//     });

//     console.log('last selected date', lastSelection);
//     // Charger les données pour le type de graphique sélectionné
//     if (type === 'pie') {
//         createChart(data, lastSelection.value);
//     } else if (type === 'bar') {
//         createInvertedBarChart(data, lastSelection.value);
//     } else if (type === 'line') {
//         createLineChart(data, lastSelection.value);
//     }
// }


// function createChartType(data, type = 'pie') {
//     // Recréation du sélecteur d'année pour le type de graphique actuel
//     displayYearSelection(type);

//     // Récupération des éléments DOM
//     const yearSelector = document.getElementById('yearSelector');
//     const dateDebutInput = document.getElementById('date-debut');
//     const dateFinInput = document.getElementById('date-fin');

//     // Vérifier si `lastSelection` est défini
//     if (!lastSelection || !lastSelection.value) {
//         lastSelection = { type: 'year', value: new Date().getFullYear().toString() }; // Année par défaut
//     }

//     // Réappliquer l'année sélectionnée ou la plage de dates
//     if (lastSelection.type === 'year') {
//         yearSelector.value = lastSelection.value;
//         getSelectedYear(lastSelection.value, type);
//     } else if (lastSelection.type === 'range') {
//         const [startDate, endDate] = lastSelection.value.split(' - ');
//         dateDebutInput.value = startDate || '';
//         dateFinInput.value = endDate || '';
//         getSelectedYear(lastSelection.value, type);
//     }

//     // Ajout des écouteurs d'événements
//     addEventListeners(yearSelector, dateDebutInput, dateFinInput, type);

//     // Charger les données pour le type de graphique sélectionné
//     switch (type) {
//         case 'pie':
//             createChart(data, lastSelection.value);
//             break;
//         case 'bar':
//             createInvertedBarChart(data, lastSelection.value);
//             break;
//         case 'line':
//             createLineChart(data, lastSelection.value);
//             break;
//         default:
//             console.error(`Type de graphique non pris en charge : ${type}`);
//     }
// }

// // Fonction pour ajouter des écouteurs d'événements
// function addEventListeners(yearSelector, dateDebutInput, dateFinInput, type) {
//     // Écouteur sur date-debut ou date-fin
//     if (!dateDebutInput.dataset.listenerAdded) {
//         dateDebutInput.addEventListener('change', () => {
//             if (dateDebutInput.value && dateFinInput.value) {
//                 lastSelection = { type: 'range', value: `${dateDebutInput.value} - ${dateFinInput.value}` };
//                 yearSelector.value = 'En période'; // Placeholder
//                 getSelectedYear(lastSelection.value, type);
//             }
//         });
//         dateDebutInput.dataset.listenerAdded = true;
//     }

//     if (!dateFinInput.dataset.listenerAdded) {
//         dateFinInput.addEventListener('change', () => {
//             if (dateDebutInput.value && dateFinInput.value) {
//                 lastSelection = { type: 'range', value: `${dateDebutInput.value} - ${dateFinInput.value}` };
//                 yearSelector.value = 'En période'; // Placeholder
//                 getSelectedYear(lastSelection.value, type);
//             }
//         });
//         dateFinInput.dataset.listenerAdded = true;
//     }

//     // Écouteur sur yearSelector
//     if (!yearSelector.dataset.listenerAdded) {
//         yearSelector.addEventListener('change', () => {
//             lastSelection = { type: 'year', value: yearSelector.value };
//             getSelectedYear(yearSelector.value, type);
//         });
//         yearSelector.dataset.listenerAdded = true;
//     }
// }



// Fonction pour créer un graphique de type radial
// function createChart(data, selectedYear) {
//    // Supprimer l'ancien graphique et la légende
//    d3.select("#chart").selectAll("*").remove();
//   d3.select("#legend-container").selectAll("*").remove();
//   d3.select('svg').selectAll("*").remove();

//   const numAgences = new Set(data.map(d => d.agence)).size;
//   const numDomaines = new Set(data.map(d => d.domaine)).size;
  
//   // Dimensions dynamiques pour le graphique
//   const width = Math.max(600, numAgences * 40);
//   const heightGraph = Math.max(650, numAgences * 80);
//   const innerRadius = 50;
//   const outerRadius = Math.min(width, heightGraph) / 2 - 20;

//   // Regrouper les données par agence
//   const groupedData = d3.groups(data, d => d.agence);

//   // Récupérer tous les domaines uniques
//   const domaineKeys = Array.from(new Set(data.flatMap(d => d.domaine)));

//   // Préparation des données groupées sans domaines vides
//   const stackedData = groupedData.map(([agence, values]) => {
//       const entry = { agence };
//       domaineKeys.forEach(domaine => {
//           const montant1 = values.find(v => v.domaine === domaine)?.montant1 || 0.0;
//           entry[domaine] = montant1;
//           entry.color = values.find(v => v.domaine === domaine)?.color || '';
//       });
//       return entry;
//   });

//   const series = d3.stack()
//       .keys(domaineKeys)
//       (stackedData);

//   const x = d3.scaleBand()
//       .domain(stackedData.map(d => d.agence))
//       .range([0, 2 * Math.PI])
//       .align(0);

//   const y = d3.scaleRadial()
//       .domain([0, d3.max(series, d => d3.max(d, d => d[1]))])
//       .range([innerRadius, outerRadius]);

//   // const hasValidData = data && data.some(d => d.montant1 > 0);

//   // if (hasValidData) {
//     const svg = d3.select("#chart")
//         .append("svg")
//         .attr("width", "100%")
//         .attr("height", heightGraph + 20)
//         .attr("viewBox", "0 0 " + width + " " + (heightGraph + 50));

//         const fontSize = selectedYear.length > 5 ? "14px" : "20px"; // Si la longueur est > 5, font-size = 8px, sinon 20px
//         let displayText = selectedYear;
//           if (selectedYear.length > 5) {
//               // Split à l'endroit du tiret et retourner à la ligne
//               const parts = selectedYear.split(' - ');
//               displayText = ' Période : ' + parts[0] + " - "  + parts[1];  
//           }
//         // Ajout du libellé pour l'année sélectionnée
//         svg.append("text")
//         .attr("x", -15) 
//         .attr("y", 40) 
//         .attr("class", "selected-year-label")
//         .style("font-size", fontSize) 
//         .style("font-weight", "600") 
//         .style("font-family", "'Poppins', sans-serif") 
//         .style("fill", "#0056b3") 
//         // .style("text-shadow", "1px 1px 3px rgba(0, 0, 0, 0.3)") 
//         .html('📅' + displayText);



//     const chartGroup = svg.append("g")
//         .attr("transform", "translate(" + (width / 2) + "," + (heightGraph / 1.8) + ") scale(1.2, 1.2)");

//     const arcGroups = chartGroup.selectAll("g")
//         .data(series)
//         .join("g")
//         .attr("class", "arc-group");

//     arcGroups.selectAll("path")
//         .data(d => d.slice().sort((a, b) => {
//             const valueA = a[1] - a[0];
//             const valueB = b[1] - b[0];
//             return valueA === 0 ? 1 : valueB === 0 ? -1 : 0;
//         }))
//         .join("path")
//         .attr("fill", d => {
//             const agence = d.data.agence;
//             const montantCalcule = parseFloat((d[1] - d[0]).toFixed(2));
//             const domaine = Object.keys(d.data).find(key => {
//                 const montantDomaine = parseFloat(d.data[key]);
//                 return montantDomaine === montantCalcule && key !== 'agence' && key !== 'color';
//             });
//             const foundData = data.find(item => item.agence === agence && item.domaine === domaine);
//             return foundData ? foundData.color : "#ccc";  
//         })
//         .attr("d", d3.arc()
//         .innerRadius(d => y(d[0]))
//         .outerRadius(d => y(d[1]))
//         .startAngle(d => x(d.data.agence))
//         .endAngle(d => x(d.data.agence) + x.bandwidth())
//         .padAngle(0.01) // Ajout d'un espacement entre les agences
//     );

//     // Ajouter les labels d'agence
//     const uniqueAgences = new Set();
//     arcGroups.selectAll("text")
//         .data(d => d)
//         .join("text")
//         .attr("transform", d => {
//             const angle = (x(d.data.agence) + x.bandwidth() / 2) * (180 / Math.PI) - 90;
//             const radius = (y(d[0]) + y(d[1])) / 2 + 10;
//             return 'translate(' + (Math.cos(angle * (Math.PI / 180)) * radius) + ', ' + (Math.sin(angle * (Math.PI / 180)) * radius) + ')';
//         })
//         .attr("dy", "0.35em")
//         .text(d => {
//             const agence = d.data.agence;
//             if (!uniqueAgences.has(agence)) {
//                 uniqueAgences.add(agence);
//                 return agence;
//             }
//             return '';
//         })
//         .style("text-anchor", "middle")
//         .style("font-size", "12px")
//         .style("fill", "white");

//     // Ajout de l'infobulle avec les détails
//     arcGroups.selectAll("path")
//         .append("title")
//         .text(d => {
//             const montantCalcule = parseFloat((d[1] - d[0]).toFixed(2));
//             const agence = d.data.agence || 'Aucune Agence';
//             const domaine = Object.keys(d.data).find(key => d.data[key] === montantCalcule && key !== 'agence' && key !== 'color');
//             const montant1 = d.data[domaine] !== undefined ? d.data[domaine] : 0;
//             return agence + " - " + domaine + ": " + montant1 + "€";
//         });

//     // Légende dynamique structurée par agence
//     const legendContainer = d3.select("#legend-container");

//     // Regrouper les données par agence
//     const groupedLegendData = d3.groups(data, d => d.agence);

//     legendContainer.selectAll(".agency-legend")
//     .data(groupedLegendData)
//     .enter()
//     .append("div")
//     .attr("class", "agency-legend")
//     .style("margin", "10px 0")
//     .style("padding", "10px")
//     .style("width", "90%")
//     .style("background-color", "#f9f9f9")
//     .each(function([agence, items]) {
//         // Titre de l'agence
//         d3.select(this).append("div")
//             .attr("class", "agency-title")
//             .style("font-weight", "bold")
//             .style("font-size", "14px")
//             .style("margin-bottom", "5px")
//             .text(agence);

//         // Légende pour chaque domaine de l'agence
//         const domainContainer = d3.select(this).append("div")
//             .attr("class", "domain-legend")
//             .style("display", "flex")
//             .style("flex-wrap", "wrap")
//             .style("justify-content", "flex-start"); // Alignement des éléments à gauche

//         domainContainer.selectAll(".legend-item")
//             .data(items)
//             .enter()
//             .append("div")
//             .attr("class", "legend-item")
//             .style("display", "flex")
//             .style("align-items", "center") // Assurez-vous que les éléments sont alignés verticalement
//             .style("margin", "5px 10px")
//             .each(function(d) {
//                 // Carré de couleur pour chaque domaine avec taille fixe
//                 d3.select(this).append("div")
//                     .style("width", "15px")   
//                     .style("height", "15px")  
//                     .style("background-color", getDomainColor(d.agence, d.domaine, data))
//                     .style("margin-right", "8px")
//                     .style("flex-shrink", "0"); // Empéche le carré de se rétrécir

//                 // Texte du domaine et montant
//                 d3.select(this).append("div")
//                     .style("font-size", "12px")
//                     .style("flex-grow", "1")  // Permet au texte de s'étirer sans affecter le carré
//                     .style("font-family", "Arial, sans-serif")
//                     // .style("line-height", "12px")
//                     .style("text-align", "left")
//                     .text(d.domaine + ": " + formatMontant(d.montant1) + "€");
//             });
//     });
//     // return;
//   // }
// // renderChartOrEmpty(data, selectedYear);
// }

// function renderChartOrEmpty(data, selectedYear) {
//   console.log('test', selectedYear);
//     // Suppression de l'ancien contenu
//     d3.select("#chart").selectAll("*").remove();
//     d3.select("#legend-container").selectAll("*").remove();
//     d3.select('svg').selectAll("*").remove();
//     // // Ajout d'un message temporaire pendant le délai de 10 secondes
//     // const loadingMessage = d3.select("#chart")
//     //     .append("div")
//     //     .attr("id", "loading-message")
//     //     .style("text-align", "center")
//     //     .style("font-size", "16px")
//     //     .style("color", "#666")
//     //     .text("Chargement en cours... Veuillez patienter.");

//     // Délai de 10 secondes avant d'exécuter la logique principale
//     // setTimeout(() => {
//         // Supprimer le message de chargement
//         d3.select("#loading-message").remove();

//         // Dimensions du graphique
//         const width = 400;
//         const height = 300;

//         // Vérifier si les données sont valides
//         const hasValidData = data.some(d => d.montant1 > 0);

//         // Si les données sont invalides (vides ou avec des montants à 0)
//         if (!hasValidData) {
//           const fontSize = selectedYear.length > 5 ? "14px" : "20px";
//           let displayText = selectedYear;
//           if (selectedYear.length > 5) {
//               // Split à l'endroit du tiret et retourner à la ligne
//               const parts = selectedYear.split(' - ');
//               displayText = '📅 Début: ' + parts[0] + "<br>" + '📅 Fin: ' + parts[1]; 
//           }
//            // Ajout du libellé pour l'année sélectionnée
//            d3.select("#chart").append("text")
//                 .attr("x", width) 
//                 .attr("y", 40) 
//                 .attr("class", "selected-year-label")
//                 .style("font-size", fontSize) 
//                 .style("font-weight", "600") 
//                 .style("font-family", "'Poppins', sans-serif") 
//                 .style("text-align", "justify")
//                 .style("margin", "15px")
//                 .style("fill", "rgb(0, 86, 179")  
//                 .html(displayText);
                
//             const svg = d3.select("#chart")
//                 .append("svg")
//                 .attr("width", width)
//                 .attr("height", height)
//                 .attr("viewBox", `0 0 ${width} ${height}`)
//                 .style("background-color", "#f9f9f9") 
//                 .style("border", "1px solid #dcdcdc") 
//                 .style("box-shadow", "0px 4px 8px rgba(0, 0, 0, 0.1)"); 

//             // Ajout d'une icône de graphique au centre
//             svg.append("text")
//                 .attr("x", width / 2)
//                 .attr("y", height / 2 - 20)
//                 .attr("text-anchor", "middle")
//                 .style("font-size", "60px")
//                 .style("fill", "#d0d0d0")
//                 .style("font-family", "'Font Awesome 5 Free'")
//                 .style("font-weight", "900")
//                 .text("\uf080");

//             // Ajout d'un texte principal sous l'icône
//             svg.append("text")
//                 .attr("x", width / 2)
//                 .attr("y", height / 2 + 30)
//                 .attr("text-anchor", "middle")
//                 .style("font-size", "16px")
//                 .style("font-weight", "bold")
//                 .style("fill", "#999")
//                 .text("Aucune donnée disponible");

//             // Ajout d'un texte explicatif
//             svg.append("text")
//                 .attr("x", width / 2)
//                 .attr("y", height / 2 + 60)
//                 .attr("text-anchor", "middle")
//                 .style("font-size", "12px")
//                 .style("fill", "#bbb")
//                 .text("Veuillez vérifier les filtres ou ajouter des données.");

                

//         // Légende dynamique structurée par agence
//         const legendContainer = d3.select("#legend-container");
//         // Regrouper les données par agence
//         const groupedLegendData = d3.groups(data, d => d.agence);
//         legendContainer.selectAll(".agency-legend")
//           .data(groupedLegendData)
//           .enter()
//           .append("div")
//           .attr("class", "agency-legend")
//           .style("margin", "10px 0")
//           .style("padding", "10px")
//           .style("width", "90%")
//           .style("background-color", "#f9f9f9")
//           .each(function([agence, items]) {
//               // Titre de l'agence
//               d3.select(this).append("div")
//                   .attr("class", "agency-title")
//                   .style("font-weight", "bold")
//                   .style("font-size", "14px")
//                   .style("margin-bottom", "5px")
//                   .text(agence);

//               // Légende pour chaque domaine de l'agence
//               const domainContainer = d3.select(this).append("div")
//                   .attr("class", "domain-legend")
//                   .style("display", "flex")
//                   .style("flex-wrap", "wrap")
//                   .style("justify-content", "flex-start"); // Alignement des éléments à gauche

//               domainContainer.selectAll(".legend-item")
//                   .data(items)
//                   .enter()
//                   .append("div")
//                   .attr("class", "legend-item")
//                   .style("display", "flex")
//                   .style("align-items", "center") // Assurez-vous que les éléments sont alignés verticalement
//                   .style("margin", "5px 10px")
//                   .each(function(d) {
//                       // Carré de couleur pour chaque domaine avec taille fixe
//                       d3.select(this).append("div")
//                           .style("width", "15px")   
//                           .style("height", "15px")  
//                           .style("background-color", getDomainColor(d.agence, d.domaine, data))
//                           .style("margin-right", "8px")
//                           .style("flex-shrink", "0"); // Empéche le carré de se rétrécir

//                       // Texte du domaine et montant
//                       d3.select(this).append("div")
//                           .style("font-size", "12px")
//                           .style("flex-grow", "1")  // Permet au texte de s'étirer sans affecter le carré
//                           .style("font-family", "Arial, sans-serif")
//                           // .style("line-height", "12px")
//                           .style("text-align", "left")
//                           .text(d.domaine + ": " + formatMontant(d.montant1) + "€");
//                   });
//           });
//         } 
//     // }, 100); 
// }


// Fonction pour créer un graphique de type bar
// function createInvertedBarChart(data, selectedYear) {
//   // Suppression du graphique précédent
//   d3.select("#chart").html("");
//   d3.select('svg').selectAll("*").remove();
  
//   const width = 928; // Largeur fixe du graphique
//   const marginTop = 30;
//   const marginRight = 10;
//   const marginBottom = 60;
//   let marginLeft = 30; // On le rend dynamique

//   // Grouper les données par agence
//   const dataByAgence = d3.group(data, d => d.agence);
//   const normalizedData = [];



//   const agences = Array.from(dataByAgence.keys());
//   const domaines = Array.from(new Set(data.map(d => d.domaine))); // Liste des domaines uniques

// const totalM1 = Array.from(new Set(data.map(d => d.montant1)));
// const totalM2 = Array.from(new Set(data.map(d => d.montant2))).filter(value => value == 0);
//   // Calculer la somme des montants pour chaque agence
//   const maxSum = d3.max(Array.from(dataByAgence.values()), agenceData => {
//       return d3.sum(agenceData, d => d.montant2, d => d.montant1); // Somme des montants1 et montant2 pour chaque agence
//   });

//   // Calculer la largeur dynamique de `marginLeft`
//   const tempSvg = d3.select("body").append("svg"); // SVG temporaire pour mesurer
//   const maxLabelWidth = Math.max(...agences.map(agence => {
//       const textElement = tempSvg.append("text")
//           .attr("font-size", "12px")
//           .text(agence);
//       const width = textElement.node().getBBox().width;
//       textElement.remove(); // Supprime le texte temporaire
//       return width;
//   }));
//   tempSvg.remove(); // Suppression du SVG temporaire

//   marginLeft = maxLabelWidth + 10; // Ajout d'un espace de 10 px aprés le texte

//   // Suite du code avec le `marginLeft` dynamique
//   const height = 20 * domaines.length + marginTop + marginBottom;
//   const barHeight = Math.max(30, (height - marginTop - marginBottom) / domaines.length); // Ajustement de la hauteur des barres

//   const x = d3.scaleLinear()
//       .domain([0, maxSum])  // Utilisation de la somme maximale pour l'échelle
//       .range([marginLeft, width - marginRight]);

//       const isExist = height < 320; // si le nombre de domaines est impair
  
//       const y = d3.scaleBand()
//           .domain(agences)
//           .range([marginTop, isExist ? height + marginBottom - 30 : height - marginBottom + 60]) // Ajust de la plage selon impair/pair
//           .padding(isExist ? 0.3 : 0.4); // Ajuste le padding selon impair/pair

//     // const y = d3.scaleBand()
//     // .domain(agences)
//     // .range([marginTop, height + marginBottom]) // Ajust de la plage selon impair/pair
//     // .padding(0.3);

//   // Création d'un dictionnaire de couleurs basé sur les domaines (si chaque domaine a une couleur spécifique dans les données, sinon une génération automatique d'uen couleur)
//   const color = d3.scaleOrdinal()
//       .domain(domaines)
//       .range(domaines.map(domaine => {
//           // Ici on suppose que chaque domaine dans 'data' a une propriété 'color'
//           const domaineData = data.find(d => d.domaine === domaine);
          
//           return domaineData ? domaineData.color : d3.schemeCategory10[domaines.indexOf(domaine) % d3.schemeCategory10.length]; // Couleur par défaut
//       }));
  

//   const svg = d3.select("#chart").append("svg")
//       .attr("width", width)
//       .attr("height", height + marginBottom)
//       .attr("viewBox", [0, 0, width, height + marginBottom])
//       .attr("style", "max-width: 100%; height: auto;");
      
//       const fontSize = selectedYear.length > 5 ? "14px" : "20px";
//       let displayText = selectedYear;
//           if (selectedYear.length > 5) {
//               // Split à l'endroit du tiret et retourner à la ligne
//               const parts = selectedYear.split(' - ');
//               displayText = ' Période : ' + parts[0] + " - "  + parts[1]; 
//           }
//        // Ajout du libellé pour l'année sélectionnée
//        svg.append("text")
//         .attr("x", width - marginRight) 
//         .attr("y", height - marginBottom + marginBottom + 40) 
//         .attr("class", "selected-year-label")
//         .style("font-size", fontSize) 
//         .style("font-weight", "600") 
//         .style("font-family", "'Poppins', sans-serif") 
//         .style("fill", "#0056b3") 
//         .style("text-anchor", "end")  // Alignement à droite
//         .html('📅' + displayText);
  
//         const groupSpacing = 50;
//   // Création d'une barre pour chaque agence et chaque domaine pour 'montant1' et 'montant2'
//   dataByAgence.forEach((values, agence) => {
    
//       const agencesGroup = svg.append("g")
//         .attr("transform", `translate(0, ${y(agence)})`);
//           // .attr("transform", "translate(0," + y(agence) + ")");

//       // Calcul de la somme des montants1 et montants2 pour chaque agence
//       const totalMontant1 = d3.sum(values, d => d.montant1);
//       const totalMontant2 = d3.sum(values, d => d.montant2);

//       // Calcul de la différence en pourcentage pour l'agence
//       const differenceAgence = totalMontant1 - totalMontant2;
//       const percentageDifferenceAgence = totalMontant1 === 0 ? 0 : (differenceAgence / totalMontant1) * 100;
//       const formattedPercentageAgence = percentageDifferenceAgence.toFixed(1) + "%";

//       const percentageRentabiliteRecette = totalMontant1 === 0 ? 0 : (differenceAgence / totalMontant1) * 100;
//       const formattedRentabiliteRecette = percentageRentabiliteRecette.toFixed(1) + "%";

//       let currentX1 = 0; // Début de la position horizontale pour la barre montant1
//       let currentX2 = 0; // Début de la position horizontale pour la barre montant2
  

//       values.forEach(d => {
//           // Calcul de la différence en pourcentage
//           const difference = d.montant1 - d.montant2;
//           const percentageDifference = d.montant1 === 0 ? 0 : (difference / d.montant1) * 100;  // évite la division par zéro
//           const formattedPercentage = percentageDifference.toFixed(1) + "%";

//           // Calcul du pourcentage de montant2 du domaine par rapport au total montant1
//           const percentageCA = totalMontant2 === 0 ? 0 : (d.montant2 / totalMontant1) * 100;
//           const formattedPercentageCA = percentageCA.toFixed(1) + "%";

//           const percentRentRecette = d.montant1 === 0 ? 0 : (difference / d.montant1) * 100;  // pas de division par zéro
//           const formattedRentRecette = percentRentRecette.toFixed(1) + "%";

//           // Création de la barre pour 'montant1' CA
//           agencesGroup.append("rect")
//               .attr("x", x(currentX1))
//               .attr("y", 0)
//               .attr("width", x(d.montant1) - x(0))
//               .attr("height", barHeight / 2)
//               .attr("fill", getDomainColor(d.agence, d.domaine, data))
//               .append("title")
//               .text(
//                   d.agence + " - " + d.domaine +
//                   " - Recette : " + new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(d.montant1) + "€" +
//                   " | Rentabilité - " + d.domaine + " : " + (difference >= 0 ? "+" : "") + formattedRentRecette +
//                   " | Rentabilité - " + d.agence + " : " + (differenceAgence >= 0 ? "+" : "") + formattedRentabiliteRecette
//               );
//             currentX1 += d.montant1;
        
//           // Création de la barre pour 'montant2' Dépenses
//           agencesGroup.append("rect")
//               .attr("x", x(currentX2))
//               .attr("y", 20)
//               .attr("width", x(d.montant2) - x(0))
//               .attr("height", barHeight / 2)
//               .attr("fill", getDomainColor(d.agence, d.domaine, data))
//               .append("title")
//               .text(
//                   d.agence + " - " + d.domaine +
//                   " - Dépenses : " + new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(d.montant2) + "€" +
//                   " | Rentabilité - " + d.domaine + " : " + (difference >= 0 ? "+" : "") + formattedPercentage +
//                   " | Rentabilité - " + d.agence + " : " + (differenceAgence >= 0 ? "+" : "") + formattedPercentageAgence +
//                   " | Proportion du CA : " + formattedPercentageCA
//               );

//           currentX2 += d.montant2;
//       });
      
//   });

//   // Ajout des axes X et Y avec le `marginLeft` dynamique
//   svg.append("g")
//   .attr("transform", "translate(0," + marginTop + ")")
//   .call(d3.axisTop(x).ticks(width / 100 + 2, "s")) // Ajout d'une 1 graduation é la fin
//   .call(g => g.selectAll(".domain").remove());

//   svg.append("g")
//       .attr("transform", "translate(" + marginLeft + ",0)")
//       .call(d3.axisLeft(y).tickSizeOuter(0))
//       .call(g => g.selectAll(".domain").remove());

//   // Légende dynamique pour les domaines sous chaque agence
//   const legendContainer = d3.select("#legend-container");
//   legendContainer.html(""); // Pour vider le contenu de la légende avant de la remplir
  
//   agences.forEach(agence => {
//       const agencyLegend = legendContainer.append("div")
//           .attr("class", "agency-legend")
//           .style("margin", "10px 0")
//           .style("padding", "10px")
//           .style("width", "90%")
//           .style("border-radius", "5px")
//           .style("background-color", "#f9f9f9");
  
//       // Titre de l'agence
//       agencyLegend.append("div")
//           .attr("class", "agency-title")
//           .style("font-weight", "bold")
//           .style("font-size", "14px")
//           .style("margin-bottom", "8px")
//           .text(agence);
  
//       // Filtrer les données pour l'agence actuelle
//       const agenceData = data.filter(d => d.agence === agence);
  
//       // Conteneur pour les domaines
//       const domainesContainer = agencyLegend.append("div")
//           .attr("class", "domaines-container")
//           .style("display", "flex")
//           .style("flex-direction", "column");
  
//       // Parcourir les domaines et ajout des informations
//       agenceData.forEach(d => {
          
//           domainesContainer.append("div")
//           .attr("class", "domaine-item")
//           .style("display", "flex")
//           .style("align-items", "left")
//           .style("margin-bottom", "5px")
//           .each(function () {
//               // Vérification si le domaine n'est pas vide
//               // const domainColor = color(d.domaine) ? color(d.domaine) : "#ccc";
//               // Vérification si le domaine est défini et récupération de sa couleur
//               const domainColor = getDomainColor(d.agence, d.domaine, data);
//               // Carré de couleur pour le domaine avec taille fixe
//               d3.select(this).append("div")
//                   .style("width", "15px")    // Taille fixe du carré
//                   .style("height", "15px")   // Taille fixe du carré
//                   .style("background-color", domainColor)
//                   .style("margin-right", "10px")
//                   .style("flex-shrink", "0"); // Empéche le carré de se rétrécir
      
//               // Abréviation du domaine
//               // const abbrDomaine = d.domaine.split(' ')
//               //     .map(word => word.slice(0, 3).toUpperCase()) 
//               //     .join(' ');
//               const abbrDomaine = creerAbbreviation(d.domaine);
      
//               // Texte pour le domaine
//               d3.select(this).append("div")
//                   .style("font-size", "11px")
//                   .style("flex-grow", "1") 
//                   .style("text-align", "left") 
//                   .text(
//                       abbrDomaine + " : CA " + formatMontant(d.montant1) + "€ - Dép " + formatMontant(d.montant2) + "€"
//                   );
//           });
//       });
//   });
// }


// // Liste des mots é exclure (déterminants, prépositions, etc.)
// const motsExclus = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'd', 'l', 'au', 'aux', 'et', 'en', 'sur', 'sous', 'dans', 'avec', 'pour', 'par', 'à', 'chez'];
// // Fonction pour récupérer les trois premieres lettres d'un mot
// function creerAbbreviation(domaine) {
//     // Diviser le domaine en mots et exclure les mots non pertinents
//     return domaine
//         .split(/\s+|'/) // Diviser par espace ou apostrophe
//         .filter(word => word && !motsExclus.includes(word.toLowerCase())) // Exclure les mots inutiles
//         .map(word => word.slice(0, 3).toUpperCase()) // Prendre les 3 premiéres lettres en majuscules
//         .join(' '); // Joindre les résultats
// }

// // Fonction pour formater les montants avec les suffixes appropriés
// function formatMontant(value) {
//     if (value >= 1e9) {
//         return d3.format(".1f")(value / 1e9) + " B"; // Milliards
//     } else if (value >= 1e6) {
//         return d3.format(".1f")(value / 1e6) + " M"; // Millions
//     } else if (value >= 1e3) {
//         return d3.format(".1f")(value / 1e3) + " k"; // Milliers
//     } else {
//         return d3.format(".1f")(value); // Valeurs normales
//     }
// }

// // Fonction pour gérer les couleurs par agence et domaine
// function getDomainColor(agence, domaine, data) {
//     const matchingItem = data.find(item => item.agence === agence && item.domaine === domaine);
//     return matchingItem && matchingItem.color ? matchingItem.color : "#ccc";
// }

// // Fonction pour créer un graphique de type ligne 
// function createLineChart(dataReconstruct, selectedYear) {
//     // Clear previous chart (to avoid duplicates)
//     d3.select('svg').selectAll("*").remove();
//     d3.select("#chart").selectAll(".selected-year-label").remove();
    
    
//     // Calculate the number of months in the data
//     let data = calculateMonthlyData(dataReconstruct);
//     const numberOfMonths = data.length;

//     // Adjust margins dynamically based on the number of months
//     const margin = {
//         top: 20,
//         right: numberOfMonths > 11 ? 150 : 25, // Reduce margin if more than 10 months
//         bottom: 40,
//         left: 60
//     };

//     // Chart dimensions
//     const chartWidth = Math.min(700, window.innerWidth - margin.left - margin.right);
//     const chartHeight = 500 - margin.top - margin.bottom;

//     // Parse date
//     const parseDate = d3.timeParse("%Y-%m");
//     data.forEach(d => {
//       d.date = parseDate(d.date);
//     });

  

//   // Create SVG container
//   const svg = d3
//     .select('svg')
//     .attr('width', chartWidth + margin.left + margin.right)
//     .attr('height', chartHeight + margin.top + margin.bottom)
//     .attr("viewBox", `0 0 ${chartWidth + margin.left + margin.right} ${chartHeight + margin.top + margin.bottom}`)
//     .attr("style", "max-width: 100%; height: auto;")
//     .append('g')
//     .attr('transform', `translate(${margin.left},${margin.top})`);

//   // X scale
//   // let xScale = d3
//   //   .scaleTime()
//   //   .domain(d3.extent(data, d => d.date))
//   //   .range([0, chartWidth]);

//   // Les mois de l'année sélectionnée
//     const generateYearMonths = (year) => {
//       const months = [];
//       for (let i = 0; i < 12; i++) {
//         months.push(new Date(year, i, 1)); // On génère le 1er jour de chaque mois
//       }
//       return months;
//     };

//    // Generate months for a period
//    const generateYearMonthsPeriode = (startDateStr, endDateStr) => {
//         const months = [];
//         const startDate = new Date(startDateStr.split('-').reverse().join('-'));
//         const endDate = new Date(endDateStr.split('-').reverse().join('-'));
//         let currentDate = new Date(startDate);
//         while (currentDate <= endDate) {
//             months.push(new Date(currentDate));
//             currentDate.setMonth(currentDate.getMonth() + 1);
//         }
//         return months;
//     };

//     // Generate fullYearMonths based on selectedYear
//     let fullYearMonths;
//     if (selectedYear.length > 5) {
//         const [startDate, endDate] = selectedYear.split(' - ').map(date => date.trim());
//         fullYearMonths = generateYearMonthsPeriode(startDate, endDate);
//     } else {
//         fullYearMonths = generateYearMonths(selectedYear);
//     }
  
//   // X scale
//   const xScale = d3
//     .scaleTime()
//     // .domain([new Date(selectedYear, 0, 1), new Date(selectedYear, 11, 31)]) 
//     .domain([fullYearMonths[0], fullYearMonths[fullYearMonths.length - 1]])
//     .range([0, chartWidth]);

//   // Extract field keys
//   const fieldKeys = Object.keys(data[0]).filter(key => key !== 'date');
//   // Y scale
//   const yScale = d3
//     .scaleLinear()
//     .domain([0, d3.max(data, d => Math.max(...fieldKeys.map(key => d[key])))]).nice()
//     .range([chartHeight, 0]);

//   // Create a color palette by agency
//   const agencies = [...new Set(fieldKeys.map(key => key.split(" : ")[1]))]; // Extract unique agency names
//   const colorPalette = d3.scaleOrdinal(d3.schemeTableau10).domain(agencies);


//   // Prepare line data
//   const lineData = fieldKeys.map((field) => ({
//     key: field,
//     agency: field.split(" : ")[1], // Extract the agency name
//     type: field.split(" : ")[0], // Extract the type ("CA" or "Dépenses")
//     color: colorPalette(field.split(" : ")[1]), // Assign color by agency
//     data: data.map(d => ({ date: d.date, value: d[field] })),
//   }));

//   // Line generator
//   const lineGenerator = d3
//     .line()
//     .curve(d3.curveCatmullRom)
//     .x(d => xScale(d.date))
//     .y(d => yScale(d.value));

//   // Format currency (€)
//   const formatCurrency = d3.format(".2f");

//   // Store visibility state of each curve
//   let visibilityState = {};

//   // Tooltip creation
//   const tooltip = d3.select('body')
//     .append('div')
//     .style('position', 'absolute')
//     .style('background', '#fff')
//     .style('border', '1px solid #ccc')
//     .style('padding', '8px')
//     .style('border-radius', '4px')
//     .style('box-shadow', '0px 2px 4px rgba(0, 0, 0, 0.1)')
//     .style('visibility', 'hidden')
//     .style('font-size', '12px');

//   // Drag behavior for labels
//   const drag = d3.drag()
//     .on("drag", function (event, d) {
//       const draggedX = event.x; // Position relative to SVG's origin
//       const draggedY = event.y;

//       // Update label position
//       d3.select(this)
//         .attr("x", draggedX)
//         .attr("y", draggedY);
//     });

//   // Area generator for the "gap" between CA and Dépenses
//   const areaGenerator = d3
//     .area()
//     .curve(d3.curveCatmullRom)
//     .x(d => xScale(d.date))
//     .y0(d => yScale(d.ca)) // Start at CA
//     .y1(d => yScale(d.depenses)); // End at Dépenses


//   // Add lines and labels
//   lineData.forEach(d => {
//     // To determine if the line is for "CA" or "Dépenses"
//     const isCA = d.type === "CA";
//     const isDepenses = d.type === "Dépenses";

//     // Style specific for "Dépenses" (dashed line)
//     const strokeDasharray = isDepenses ? "4 4" : "none";

//     // Draw line
//     const line = svg
//       .append('path')
//       .datum(d.data)
//       .attr('class', 'line')
//       .attr('d', lineGenerator)
//       .attr('stroke', d.color) 
//       .attr('fill', 'none')
//       .attr('stroke-width', 2)
//       .attr('stroke-dasharray', strokeDasharray) 
//       .style('visibility', 'visible'); 
//       // Show tooltip on mouseover
//       svg.selectAll('.line')
//         .data(lineData) // Associe chaque ligne à ses données correspondantes
//         .on('mouseover', function (event, d) {
//           // Le point le plus proche sur la ligne
//           const [mouseX] = d3.pointer(event, this); // Récupère la position X de la souris relative à l'élément
//           const closestPoint = d.data.reduce((prev, curr) => {
//             const currDistance = Math.abs(xScale(curr.date) - mouseX);
//             const prevDistance = Math.abs(xScale(prev.date) - mouseX);
//             return currDistance < prevDistance ? curr : prev;
//           });

//         // Affichage de tooltip
//         tooltip.style('visibility', 'visible')
//           .style('top', `${event.pageY - 40}px`)
//           .style('left', `${event.pageX + 10}px`)
//           .html(`
//             <strong>${d.key}</strong><br>
//             Date: ${d3.timeFormat("%b %Y")(closestPoint.date)}<br>
//             Montant: €${formatCurrency(closestPoint.value)}
//           `);

       
//         svg.selectAll('.line')
//           .style('opacity', function () {
//             return this === event.target ? 1 : 0.4; // Mise en évidence la ligne survolée
//           });
//       })
//       .on('mousemove', function (event) {
//         // Meise à jour dynamiquement la position du tooltip
//         tooltip.style('top', `${event.pageY - 40}px`)
//           .style('left', `${event.pageX + 10}px`);
//       })
//       .on('mouseout', function () {
//         // Cache le tooltip
//         tooltip.style('visibility', 'hidden');

//         // Réinitialisation de l'opacité de toutes les lignes
//         svg.selectAll('.line')
//           .style('opacity', 1);
//       });


//       // Initialize visibility state
//       visibilityState[d.key] = true;

//       // Get the last point of the curve
//       const lastPoint = d.data[d.data.length - 1];
//       const xPosition = xScale(lastPoint.date);
//       const yPosition = yScale(lastPoint.value);

//       // Adding a point if there is only one data point
//       if (d.data.length === 1) {
//             const points = svg.selectAll('.point')
//               svg.append('circle')
//               .attr('class', 'point')
//               .attr('cx', xPosition)
//               .attr('cy', yPosition)
//               .data(d.data)
//               .attr('class', d => `point-${d.key}`)
//               .attr('r', 5) // Rayon du point
//               .attr('fill', d.color) 
//               .attr('stroke', d.color) 
//               .attr('stroke-width', 2)
//               .attr('stroke-dasharray', strokeDasharray) 
//               .attr('stroke-width', 4.5)
//               .style('visibility', 'visible');
              
//             svg.selectAll('.label')
//                 .data(d.data)
//                 .enter()
//                 .append('text')
//                 .text(`${d.key} (€${formatMontant(formatCurrency(lastPoint.value))})`)
//                 .attr('x', xPosition + 5)
//                 .attr('y', yPosition - 5)
//                 .attr('text-anchor', 'start')
//                 .style('font-family', 'sans-serif')
//                 .style('font-size', '12px')
//                 .style('fill', d.color)
//                 .style('font-weight', 'bold')
//                 .call(drag)
//                 .on('click', function(event, d) {
//                     // Basculer l'état de visibilité pour la ligne et les points
//                     const isVisible = visibilityState[d.key];
//                     visibilityState[d.key] = !isVisible;
                    
//                     // Met à jour la visibilité de la ligne
//                     line.style('visibility', visibilityState[d.key] ? 'visible' : 'hidden');

//                     // Met à jour la visibilité des points associés (y compris les points entillés)
//                     svg.selectAll(`.point-${d.key}`).style('visibility', visibilityState[d.key] ? 'visible' : 'hidden');

//                     // Basculer l'effet de strikethrough sur le libellé
//                     const labelText = d3.select(this);
//                     labelText.style('text-decoration', visibilityState[d.key] ? 'none' : 'line-through');
//             });
//       }else {
//            // Add the label
//         svg.append('text')
//           .text(`${d.key} (€${formatMontant(formatCurrency(lastPoint.value))})`)
//           .attr('x', xPosition + 5)
//           .attr('y', yPosition - 5)
//           .attr('text-anchor', 'start')
//           .style('font-family', 'sans-serif')
//           .style('font-size', '12px')
//           .style('fill', d.color)
//           .style('font-weight', 'bold')
//           .call(drag)
//           .on('click', function () {
//             // Bascule de l'état de visibilité pour la ligne et le point
//             const isVisible = visibilityState[d.key];
//             visibilityState[d.key] = !isVisible;
         
//             // Met à jour la visibilité de la ligne et du point
//             line.style('visibility', visibilityState[d.key] ? 'visible' : 'hidden');

//             // Basculer l'effet de strikethrough sur le libellé
//             const labelText = d3.select(this);
//             labelText.style('text-decoration', visibilityState[d.key] ? 'none' : 'line-through');
//         });
//       }
//     });

//    // Add areas for each agency
//   // Add areas for each agency
//   agencies.forEach(agency => {
//     const caData = lineData.find(d => d.type === "CA" && d.agency === agency);
//     const depensesData = lineData.find(d => d.type === "Dépenses" && d.agency === agency);

//     if (caData && depensesData) {
//       const combinedData = caData.data.map((d, i) => ({
//         date: d.date,
//         ca: d.value,
//         depenses: depensesData.data[i]?.value || 0,
//       }));

//       const area = svg.append("path")
//         .datum(combinedData)
//         .attr("class", "area")
//         .attr("d", areaGenerator)
//         .attr("fill", caData.color) 
//         .attr("fill-opacity", 0.1) // Semi-transparent fill for the areas
//         .style("cursor", "pointer");

//       // Add tooltip and hover effect
//       area.on("mouseover", function () {
//           // Highlight the area
//           d3.select(this)
//             .attr("fill-opacity", 0.3); 

//           // Show tooltip
//           tooltip.style("visibility", "visible");
//         })
//         .on("mousemove", function (event, d) {
//           // Get mouse position relative to the chart
//           const [mouseX] = d3.pointer(event, this);
//           const hoveredDate = xScale.invert(mouseX); // Convert mouseX to a date

//           // Find the closest data point
//           const closestPoint = d.reduce((prev, curr) => {
//             const prevDistance = Math.abs(prev.date - hoveredDate);
//             const currDistance = Math.abs(curr.date - hoveredDate);
//             return currDistance < prevDistance ? curr : prev;
//           });

//           // Update tooltip content
//           tooltip.style("top", `${event.pageY - 40}px`)
//             .style("left", `${event.pageX + 10}px`)
//             .html(`
//             <strong>${agency}</strong><br>
//             Date: ${d3.timeFormat("%b %Y")(closestPoint.date)}<br>
//             CA: €${formatCurrency(closestPoint.ca)}<br>
//             Dépenses: €${formatCurrency(closestPoint.depenses)}<br>
//             Résultat: ${(closestPoint.ca - closestPoint.depenses).toFixed(2)}<br>
//             Résultat en %: ${((closestPoint.ca - closestPoint.depenses) / closestPoint.ca * 100).toFixed(2)}%
//           `);
//         })
//         .on("mouseout", function () {
//           // Reset the area opacity
//           d3.select(this)
//             .attr("fill-opacity", 0.1); // Reset to default opacity

//           // Hide tooltip
//           tooltip.style("visibility", "hidden");
//         });
//     }
//   });

//   // Adding X axis
//   // const xAxis = d3.axisBottom(xScale).tickFormat(d3.timeFormat("%b %Y"));
//   // svg.append('g').attr('transform', `translate(0,${chartHeight})`).call(xAxis);
 
  
//     let xAxis = null;
//     if (selectedYear.length > 5) {
//         // Calculer l'intervalle de mois en fonction de l'espace disponible
//         const tickSpacing = chartWidth / fullYearMonths.length; // Largeur de chaque tick (mois)
//         const monthStep = tickSpacing > 60 ? 1 : tickSpacing > 30 ? 2 : 3; // Choisir d'afficher 1 mois, 2 mois ou 3 mois en fonction de l'espace


//         // Définir les valeurs de ticks pour l'axe X (en sautant certains mois)
//         const tickValues = fullYearMonths.filter((month, index) => index % monthStep === 0);

//         // Créer l'axe X
//       xAxis = d3.axisBottom(xScale)
//         .tickFormat(d => d3.timeFormat("%b %Y")(d)) // Format du mois et de l'année (janv 2025)
//         .tickValues(tickValues);
//     } else {
//       // Adding X axis
//         xAxis = d3.axisBottom(xScale)
//         .tickFormat(d => d3.timeFormat("%b %Y")(d)) // Format "janv 2025"
//         .tickValues(fullYearMonths); // Affichage de tous les mois explicitement
//     }

//       svg.append('g')
//         .attr('transform', `translate(0,${chartHeight})`)
//         .call(xAxis);


//       svg.append('g')
//         .attr('transform', `translate(0,${chartHeight})`)
//         .call(xAxis);

//       // Adding Y axis
//       const yAxis = d3.axisLeft(yScale);
//       svg.append('g').call(yAxis);

//       const fontSize = selectedYear.length > 5 ? "14px" : "20px"; // Si la longueur est > 5, font-size = 8px, sinon 20px
//       let displayText = selectedYear;
//         if (selectedYear.length > 5) {
//             // Split à l'endroit du tiret pour retourner à la ligne
//             const parts = selectedYear.split(' - ');
//             displayText = ' Période : ' + parts[0] + " - "  + parts[1];  
//         }

//       // Adding label for the selected year on the Y-axis
//       svg.append("text")
//         .attr("x", chartWidth - 40) 
//         .attr("y", chartHeight + 40) 
//         .attr("class", "selected-year-label")
//         .style("font-size", fontSize)
//         .style("font-weight", "600")
//         .style("font-family", "'Poppins', sans-serif")
//         .style("fill", "#0056b3")
//         .style("text-anchor", "middle")
//         .text('📅' + displayText);

//       createLegend(lineData);
// }



// function calculateMonthlyData(data) {
//   // Parse dates
//   const parseDate = d3.timeParse("%Y-%m");
//   const formatYear = d3.timeFormat("%Y");
//   const formatMonth = d3.timeFormat("%Y-%m");

//   // Regrouper les données par clé et mois
//   const groupedData = {};
//   data.forEach(entry => {
//     const date = parseDate(entry.date);
//     const year = formatYear(date);
//     const month = formatMonth(date);

//     Object.keys(entry).forEach(key => {
//       if (key !== "date") {
//         if (!groupedData[key]) groupedData[key] = {};
//         if (!groupedData[key][year]) groupedData[key][year] = {};
//         if (!groupedData[key][year][month]) groupedData[key][year][month] = 0;

//         // Ajouter les valeurs pour chaque clé, année et mois
//         groupedData[key][year][month] += entry[key];
//       }
//     });
//   });

//   // console.log("Grouped Data:", groupedData);

//   // Calcul des cumuls par année
//   const cumulativeSums = {};
//   Object.keys(groupedData).forEach(key => {
//     cumulativeSums[key] = {};
//     Object.keys(groupedData[key]).forEach(year => {
//       cumulativeSums[key][year] = {};
//       const months = Object.keys(groupedData[key][year]).sort(); // Trier les mois

//       let cumulativeSum = 0;

//       months.forEach((month) => {
//         cumulativeSum += groupedData[key][year][month];
//         cumulativeSums[key][year][month] = cumulativeSum; // Cumul par mois
//       });
//     });
//   });

//   // console.log("Cumulative Sums:", cumulativeSums);

//   // Reconstruire les données avec la même structure
//   const result = [];
//   const allMonths = [...new Set(data.map(d => d.date))].sort(); // Tous les mois uniques triés

//   allMonths.forEach(month => {
//     const entry = { date: month };
//     const date = parseDate(month);
//     const year = formatYear(date);

//     Object.keys(cumulativeSums).forEach(key => {
//       entry[key] = cumulativeSums[key][year]?.[month] || 0; // Valeur pour ce mois (ou 0 si inexistante)
//     });

//     result.push(entry);
//   });

//   // console.log("Final Result:", result);
//   return result;
// }

// // Fonction de légende
// function createLegend(lineData) {
//   const legendContainer = d3.select("#legend-container");
//   const visibilityState = {}; // Suivi de la visibilité

//   legendContainer.html(""); // Clear previous legend

//   // On regroupe les données pour chaque agence
//   const agencies = {};

//   lineData.forEach(d => {
//     const agencyName = d.key.split(" : ")[1]; // Extrait le nom de l'agence
//     visibilityState[d.key] = true; // Initialement visible

//     if (!agencies[agencyName]) {
//       agencies[agencyName] = {
//         caColor: 0,
//         depensesColor: 0,
//         caValue: 0,
//         depensesValue: 0,
//       };
//     }

//     if (d.key.includes("CA")) {
//       agencies[agencyName].caValue = d.data[d.data.length - 1].value;
//       agencies[agencyName].caColor = d.color;
//     } else if (d.key.includes("Dépenses")) {
//       agencies[agencyName].depensesValue = d.data[d.data.length - 1].value;
//       agencies[agencyName].depensesColor = d.color;
//     }
//   });

//   Object.keys(agencies).forEach(agencyName => {
//     const entry = legendContainer.append("div")
//       .attr("class", "agency-legend")
//       .style("margin", "10px 0")
//       .style("padding", "10px")
//       .style("width", "90%")
//       .style("border-radius", "5px")
//       .style("background-color", "#f9f9f9")
//       .style("display", "flex")
//       .style("flex-direction", "column")
//       .style("align-items", "left");

//     entry.append("span")
//       .text(agencyName)
//       .attr("class", "agency-title")
//       .style("font-weight", "bold")
//       .style("font-size", "14px")
//       .style("margin-bottom", "8px")
//       .style("text-align", "center");

//     const caSection = entry.append("div")
//       .style("display", "flex")
//       .style("align-items", "left")
//       .style("margin-bottom", "5px");

//     caSection.append("span")
//       .style("width", "15px")
//       .style("height", "15px")
//       .style("background-color", agencies[agencyName].caColor) // Couleur pleine pour "CA"
//       .style("display", "inline-block")
//       .style("margin-right", "10px");

//     caSection.append("span")
//       .text(`CA : €${d3.format(".2f")(agencies[agencyName].caValue)}`)
//       .style("font-size", "14px");

//     caSection.on("click", function() {
//       const key = `CA : ${agencyName}`;
//       visibilityState[key] = !visibilityState[key];

//       const line = d3.selectAll('.line').filter(function() {
//         return d3.select(this).attr('data-key') === key;
//       });

//       // if (visibilityState[key]) {
//       //   d3.select(this).style("text-decoration", "none");
//       //   line.style("visibility", "visible");
//       // } else {
//       //   d3.select(this).style("text-decoration", "line-through");
//       //   line.style("visibility", "hidden");
//       // }
//     });

//     const depensesSection = entry.append("div")
//       .style("display", "flex")
//       .style("align-items", "left");

//     depensesSection.append("span")
//       .style("width", "15px")
//       .style("height", "15px")
//       .style("background-image", `repeating-linear-gradient(
//         45deg,
//         ${agencies[agencyName].depensesColor},
//         ${agencies[agencyName].depensesColor} 5px,
//         transparent 5px,
//         transparent 10px
//       )`) // Hachures pour "Dépenses"
//       .style("display", "inline-block")
//       .style("margin-right", "10px");

//     depensesSection.append("span")
//       .text(`Dépenses : €${d3.format(".2f")(agencies[agencyName].depensesValue)}`)
//       .style("font-size", "14px");

//     depensesSection.on("click", function() {
//       const key = `Dépenses : ${agencyName}`;
//       visibilityState[key] = !visibilityState[key];

//       const line = d3.selectAll('.line').filter(function() {
//         return d3.select(this).attr('data-key') === key;
//       });

//       // if (visibilityState[key]) {
//       //   d3.select(this).style("text-decoration", "none");
//       //   line.style("visibility", "visible");
//       // } else {
//       //   d3.select(this).style("text-decoration", "line-through");
//       //   line.style("visibility", "hidden");
//       // }
//     });
//   });
// }

// function last(array) {
//   return array[array.length - 1];
// }


// Fonction d'affichage et gestion des sélections d'année
  // function displayYearSelection(chartType) {
  //     const dateDebutInput = document.getElementById('date-debut');
  //     const dateFinInput = document.getElementById('date-fin');
  //     const calendarDebut = document.getElementById('calendar-debut');
  //     const calendarFin = document.getElementById('calendar-fin');

  //     // Création et ajout de l'élément <select> pour la sélection d'année
  //     const selectElement = document.createElement('select');
  //     selectElement.id = 'yearSelector';
  //     selectElement.classList.add('styled-select');

  //     const currentYear = new Date().getFullYear();
  //     const years = Array.from({ length: 11 }, (_, i) => currentYear - i);
  //     years.forEach(year => {
  //         const option = document.createElement('option');
  //         option.value = year;
  //         option.textContent = year;
  //         selectElement.appendChild(option);
  //     });

  //     // Conteneur pour le sélecteur d'années
  //     const container = document.getElementById('yearSelectionContainer');
  //     container.innerHTML = '';
  //     container.appendChild(selectElement);

  //     // Icône de calendrier
  //     const icon = document.createElement('i');
  //     icon.classList.add('fas', 'fa-calendar-alt');
  //     icon.style.backgroundColor = '#0056b3';
  //     icon.style.opacity = '0.3';
  //     icon.title = "Résultat par année civile";
  //     container.appendChild(icon);

  //     // Sélection par défaut et gestion du changement d'année
  //     let selectedYear = currentYear;
  //     selectElement.addEventListener('change', function () {
  //         selectedYear = selectElement.value;
  //         getSelectedYear(selectedYear, chartType);
  //     });

  //     // Initialisation de l'année
  //     selectElement.value = selectedYear;
  //     getSelectedYear(selectedYear, chartType);

  //     // Ajout des écouteurs d'événements sur les inputs
  //     // dateDebutInput.addEventListener('input', () => handleDateChange(chartType));
  //     // dateFinInput.addEventListener('input', () => handleDateChange(chartType));

  //     // // Ajout des écouteurs d'événements pour les calendriers (début et fin)
  //     // addCalendarListeners(calendarDebut, dateDebutInput, chartType);
  //     // addCalendarListeners(calendarFin, dateFinInput, chartType);
  // }
  // function displayYearSelection(chartType) {

  //     const dateDebutInput = document.getElementById('date-debut');
  //     const dateFinInput = document.getElementById('date-fin');
  //     const calendarDebut = document.getElementById('calendar-debut');
  //     const calendarFin = document.getElementById('calendar-fin');
      
  //     // Création de l'élément <select>
  //     const selectElement = document.createElement('select');
  //     selectElement.id = 'yearSelector';
  //     selectElement.classList.add('styled-select');
    
  //     // Récupération de l'année en cours
  //     const currentYear = new Date().getFullYear();
    
  //     // Création des années de l'année en cours jusqu'à 10 ans en arrière
  //     const years = Array.from({ length: 11 }, (_, i) => currentYear - i);
      
  //     // Création de l'option "En période"
  //     const periodOption = document.createElement('option');
  //     periodOption.value = 'En période';
  //     periodOption.textContent = 'En période';
  //     periodOption.style.display = 'none'; // Option cachée
  //     selectElement.appendChild(periodOption);
    
  //     // Création des options pour chaque année
  //     years.forEach(year => {
  //       const option = document.createElement('option');
  //       option.value = year;
  //       option.textContent = year;
        
  //       selectElement.appendChild(option);
  //     });
    
  //     // Ajout de l'élément <select> 
  //     const container = document.getElementById('yearSelectionContainer'); 
  //     container.innerHTML = ''; // On vide la div avant d'ajouter les éléments
  //     container.appendChild(selectElement);
    
  //     // Changement de la couleur de l'élément sélectionné
  //     selectElement.addEventListener('change', function() {
  //         const selectedYear = selectElement.value;
  //         // On applique une couleur spécifique à l'élément sélectionné
  //         const selectedOption = selectElement.options[selectElement.selectedIndex];
  //         selectedOption.style.color = '#0056b3';  
  //     });
    
  //     const icon = document.createElement('i');
  //     icon.classList.add('fas', 'fa-calendar-alt');
  //     icon.style.backgroundColor = '#0056b3';
  //     icon.style.opacity = '0.3'; 
  //     icon.title = "Résultat par année civile";
  //     container.appendChild(icon);
      
  //     console.log('selected date', selectElement.value);
  //     // Réinitialisation de la valeur par défaut : soit l'année sélectionnée précédemment, soit l'année actuelle
  //     if (selectedYear) {
  //     //   //Ajout des écouteurs d'événements sur les inputs
  //     //   dateDebutInput.addEventListener('input', () => handleDateChange(chartType));
  //     //   dateFinInput.addEventListener('input', () => handleDateChange(chartType));

  //     //   // Ajout des écouteurs d'événements pour les calendriers (début et fin)
  //     //   addCalendarListeners(calendarDebut, dateDebutInput, chartType);
  //     //   addCalendarListeners(calendarFin, dateFinInput, chartType);
  
  //     // } else {
  //       // selectElement.value = currentYear; 
  //       // selectedYear = currentYear; 
  //       // getSelectedYear(selectedYear, chartType); 
        
  //     }
    
  //     // Ajout d'un gestionnaire d'événements pour la sélection
  //     selectElement.addEventListener('change', function () {
  //       selectedYear = selectElement.value; // Mettre à jour la variable globale
  //       if (selectedYear) {
  //         // getSelectedYear(selectedYear, chartType); // Chargement desdonnées pour l'année sélectionnée
  //       } else {
  //         console.log('Aucune année sélectionnée.');
  //       }
  //     });

      
  // }



// function getSelectedYear(selectedYear, chartType) {

//   // Check if selectedYear is a periode
//   if (selectedYear.length > 5) {
//     // Split à l'endroit du tiret et retourner les valeurs correctement formatées
//     const parts = selectedYear.split(' - ');
//     const formattedStartDate = parts[0].trim(); 
//     const formattedEndDate = parts[1].trim();  

//     // Envoyer les dates au backend
//     sendDateRangeToBackEnd(formattedStartDate, formattedEndDate, chartType, selectedYear);
//   } else {
//       // Calcul de la date de début et de fin pour une année simple
//       const startDate = new Date(selectedYear, 0, 1); // Le premier janvier de l'année sélectionnée
//       const today = new Date();
//       let endDate;

//       if (parseInt(selectedYear) === today.getFullYear()) {
//           // Si l'année sélectionnée est l'année en cours, la date de fin est aujourd'hui
//           endDate = today;
//       } else {
//           // Sinon, c'est le 31 décembre de l'année sélectionnée
//           endDate = new Date(selectedYear, 11, 31);
//       }

//       // Formater les dates
//       const formattedStartDate = formatDate(startDate);
//       const formattedEndDate = formatDate(endDate);

//       // Envoyer les dates au backend
//       sendDateRangeToBackEnd(formattedStartDate, formattedEndDate, chartType, selectedYear);
//   }
// }

// Fonction de formatage de la date
// function formatDate(date) {
//   const day = ("0" + date.getDate()).slice(-2); // Ajouter un 0 devant les jours < 10
//   const month = ("0" + (date.getMonth() + 1)).slice(-2); // Ajouter un 0 devant les mois < 10
//   const year = date.getFullYear();
  
//   return `${day}-${month}-${year}`;
// }

// Fonction fetch pour envoyer les dates au serveur Dolibarr
// function sendDateRangeToBackEnd(startDate, endDate, chartType) {
  
//   if(chartType == 'line') {
//     const url = '/custom/addoncomm/ajax/financial_month_data.php'; 
//     $.ajax({
//     url: url,
//     type: 'POST',
//     dataType: 'json',  
//     // data: JSON.stringify(requestData),
//     data: {
//             startDate: JSON.stringify(startDate),
//             endDate: JSON.stringify(endDate)
//         }, 
//     // contentType: 'application/json', 
//     contentType: 'application/x-www-form-urlencoded',
//     success: function(response) {
//       createChartType(response.dataFiananceEvol, chartType);
//       console.log('Réponse du serveur Dolibarr:', response);
//     },
//     error: function(xhr, status, error) {
//       console.error('Erreur lors de l\'envoi des données:', error);
//     }
//   });
//   }
//   if(chartType == 'pie' || chartType == 'bar') {
//     const url = '/custom/addoncomm/ajax/financial_year_data.php'; 

//     $.ajax({
//       url: url,
//       type: 'POST',
//       dataType: 'json',  
//       // data: JSON.stringify(requestData),
//       data: {
//               startDate: JSON.stringify(startDate),
//               endDate: JSON.stringify(endDate)
//           }, 
//       // contentType: 'application/json', 
//       contentType: 'application/x-www-form-urlencoded',
//       success: function(response) {
//         createChartType(response.dataFianance, chartType);
//         updateDisplay(response.totauxAgencesFinancial);
//         console.log('Réponse du serveur Dolibarr:', response);
//       },
//       error: function(xhr, status, error) {
//         console.error('Erreur lors de l\'envoi des données:', error);
//       }
//     });
//   }
  
// }

// function sendDateRangeToBackEnd(startDate, endDate, chartType, selectedYear) {
//   let url;
  
//   if (chartType === 'line') {
//     url = '/custom/addoncomm/ajax/financial_month_data.php';
//   } else if (chartType === 'pie' || chartType === 'bar') {
//     url = '/custom/addoncomm/ajax/financial_year_data.php';
//   }


//   $.ajax({
//     url: url,
//     type: 'POST',
//     dataType: 'json',
//     data: {
//       startDate: JSON.stringify(startDate),
//       endDate: JSON.stringify(endDate),
//     },
//     contentType: 'application/x-www-form-urlencoded',
//     success: function (response) {
//       console.log('Réponse du serveur Dolibarr:', response);

//       // Charger les données selon le type de graphique
        
//         if (chartType === 'pie') {
//           // Si les données sont vides ou invalides
//           const hasValidData = Array.isArray(response.dataFianance) && response.dataFianance.some(d => d.montant1 > 0);

//           if (!hasValidData) {
//               renderChartOrEmpty(response.dataFianance, selectedYear);
//               return;
//           }
//           createChart(response.dataFianance, selectedYear);
//         } else if (chartType === 'bar') {
//           // Si les données sont vides ou invalides
//           const hasValidData = Array.isArray(response.dataFianance) && (response.dataFianance.some(d => d.montant1 > 0) || response.dataFianance.some(d => d.montant2 > 0));

//           if (!hasValidData) {
//               renderChartOrEmpty(response.dataFianance, selectedYear);
//               return;
//           }
//           createInvertedBarChart(response.dataFianance, selectedYear);
//         }else if (chartType === 'line') {
//           //Si les données sont vides ou invalides
//           // const hasValidDataEvol = Array.isArray(response.dataFiananceEvol);
         
//           // if (!hasValidDataEvol) {
//           //     renderChartOrEmpty(response.dataFiananceEvol, selectedYear);
//           //     return;
//           // }
//           createLineChart(response.dataFiananceEvol, selectedYear);
//         } 

//         if (response.totauxAgencesFinancial) {
//           updateDisplay(response.totauxAgencesFinancial);
//         }
      
//     },
//     error: function (xhr, status, error) {
//       console.error('Erreur lors de l\'envoi des données:', error);
//     }
//   });
// }



  </script>