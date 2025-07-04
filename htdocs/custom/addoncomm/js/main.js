let currentChartType = 'pie'; // Type de graphique actif par défaut
let isLoading = false; // Indicateur pour empêcher les appels concurrents
let selectedYear = new Date().getFullYear().toString(); // Variable globale pour garder la valeur de l'année sélectionnée
// let lastSelection = { type: 'year', value: null }; // Variable pour stocker le dernier changement (par défaut, une année)
let lastSelection = { type: 'year', value: new Date().getFullYear().toString() }; // Initialisation par défaut

$(document).ready(function () {
  // Initialisation : charger le graphique par défaut
  fetchData((data) => createChartType(data, currentChartType));

  // Gestion des clics sur les boutons
  $('#toggleButton').click(() => switchChartType('pie'));
  $('#toggleButtonBar').click(() => switchChartType('bar'));
  $('#toggleButtonLine').click(() => switchChartType('line'));

  // Initialisation : activer l'icône du graphique par défaut
  updateButtonIcons(currentChartType);

});

let previousCalendarState = {
  debut: '',
  fin: ''
};

function switchChartType(newChartType) {
  if (currentChartType === newChartType) {
    console.log(`Le graphique est déjà de type ${newChartType}. Aucun changement nécessaire.`);
    return;
  }

  if (isLoading) {
    console.log(`Un chargement est déjà en cours. Patientez avant de changer le type de graphique.`);
    return;
  }

  isLoading = true; // Bloque les nouveaux appels pendant le chargement
  currentChartType = newChartType; // Met à jour le type de graphique actuel

  const fetchFunction = newChartType === 'line' ? fetchDataEvol : fetchData;
  fetchFunction((data) => {
    createChartType(data, newChartType);
    updateButtonIcons(newChartType); // Met à jour les icônes après le changement
    // displayCalandar();
    isLoading = false; // Débloque les appels une fois le chargement terminé
  });
}


// Fonction pour mettre à jour les icônes des boutons
function updateButtonIcons(activeType) {
  $('#toggleButton, #toggleButtonBar, #toggleButtonLine').removeClass('active'); // Réinitialiser toutes les icônes
  $(`#toggleButton${activeType === 'pie' ? '' : activeType === 'bar' ? 'Bar' : 'Line'}`).addClass('active'); // Activer l'icône correspondante
}

// Calandar display 
// function displayCalandar() { 
//   const dateDebutInput = document.getElementById('date-debut');
//   const dateFinInput = document.getElementById('date-fin');
//   const calendarDebut = document.getElementById('calendar-debut');
//   const calendarFin = document.getElementById('calendar-fin');
//   const currentMonthYearDebut = document.getElementById('current-month-year-debut');
//   const currentMonthYearFin = document.getElementById('current-month-year-fin');
//   const daysDebut = document.getElementById('days-debut');
//   const daysFin = document.getElementById('days-fin');

//   // Date par défaut pour le début (1er janvier de l'année en cours)
//   const currentDate = new Date();
//   const firstDayOfYear = new Date(currentDate.getFullYear(), 0, 1);

//   // Date par défaut pour la fin (date actuelle)
//   const today = new Date();

//   let selectedDateDebut = firstDayOfYear;
//   let selectedDateFin = today;
//   let currentMonthDebut = selectedDateDebut.getMonth();
//   let currentYearDebut = selectedDateDebut.getFullYear();
//   let currentMonthFin = selectedDateFin.getMonth();
//   let currentYearFin = selectedDateFin.getFullYear();

//   // Fonction pour afficher les jours dans le calendrier
//   function renderCalendar(inputType) {
//       let currentMonth, currentYear, selectedDate, calendar, currentMonthYear, daysContainer;

//       if (inputType === 'debut') {
//           currentMonth = currentMonthDebut;
//           currentYear = currentYearDebut;
//           selectedDate = selectedDateDebut;
//           calendar = calendarDebut;
//           currentMonthYear = currentMonthYearDebut;
//           daysContainer = daysDebut;
//       } else {
//           currentMonth = currentMonthFin;
//           currentYear = currentYearFin;
//           selectedDate = selectedDateFin;
//           calendar = calendarFin;
//           currentMonthYear = currentMonthYearFin;
//           daysContainer = daysFin;
//       }

//       daysContainer.innerHTML = '';
//       const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
//       const lastDateOfMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

//       currentMonthYear.textContent = `${new Date(currentYear, currentMonth).toLocaleString('fr-FR', { month: 'long' })} ${currentYear}`;

//       // Ajout des jours vides avant le début du mois
//       for (let i = 0; i < (firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1); i++) {
//           daysContainer.innerHTML += '<span class="disabled"></span>';
//       }

//       // Ajout des jours du mois
//       for (let day = 1; day <= lastDateOfMonth; day++) {
//           const dayElement = document.createElement('span');
//           dayElement.textContent = day;
//           if (
//               day === selectedDate.getDate() &&
//               currentMonth === selectedDate.getMonth() &&
//               currentYear === selectedDate.getFullYear()
//           ) {
//               dayElement.classList.add('selected');
//           }
//           dayElement.addEventListener('click', () => {
//               if (inputType === 'debut') {
//                   selectedDateDebut = new Date(currentYear, currentMonth, day);
//                   dateDebutInput.value = selectedDateDebut.toLocaleDateString('fr-FR');
//                   // Vérification et ajustement de la date de fin si nécessaire
//                   adjustEndDate();
//                   renderCalendar('debut');
//               } else {
//                   selectedDateFin = new Date(currentYear, currentMonth, day);
//                   dateFinInput.value = selectedDateFin.toLocaleDateString('fr-FR');
//                   // adjustStartDate();  // Appel pour ajuster la date de début si nécessaire
//               }
//               calendar.classList.remove('active');
//               renderCalendar(inputType);
              
//           });
//           daysContainer.appendChild(dayElement);
//       }
//   }

//   // Fonction pour ajuster la date de fin si elle est inférieure à la date de début
//   function adjustEndDate() {
//       if (selectedDateFin < selectedDateDebut) {
//           // Si la date de fin est avant la date de début, la date de fin devient la date de début + 1 jour
//           selectedDateFin = new Date(selectedDateDebut);
//           selectedDateFin.setDate(selectedDateDebut.getDate() + 1);
//           dateFinInput.value = selectedDateFin.toLocaleDateString('fr-FR');
//           // Mise à jour du calendrier de la date de fin
//           currentMonthFin = selectedDateFin.getMonth();
//           currentYearFin = selectedDateFin.getFullYear();
//           renderCalendar('fin');
//       }
//   }


//   // Gésion de l'affichage du calendrier
//   dateDebutInput.addEventListener('click', () => {
//       calendarDebut.classList.toggle('active');
//       renderCalendar('debut');
//   });

//   dateFinInput.addEventListener('click', () => {
//       calendarFin.classList.toggle('active');
//       renderCalendar('fin');
//   });

//   // Passage au mois précédent ou suivant
//   document.getElementById('prev-month-debut').addEventListener('click', () => {
//       currentMonthDebut--;
//       if (currentMonthDebut < 0) {
//           currentMonthDebut = 11;
//           currentYearDebut--;
//       }
//       renderCalendar('debut');
//   });

//   document.getElementById('next-month-debut').addEventListener('click', () => {
//       currentMonthDebut++;
//       if (currentMonthDebut > 11) {
//           currentMonthDebut = 0;
//           currentYearDebut++;
//       }
//       renderCalendar('debut');
//   });

//   document.getElementById('prev-month-fin').addEventListener('click', () => {
//       currentMonthFin--;
//       if (currentMonthFin < 0) {
//           currentMonthFin = 11;
//           currentYearFin--;
//       }
//       renderCalendar('fin');
//   });

//   document.getElementById('next-month-fin').addEventListener('click', () => {
//       currentMonthFin++;
//       if (currentMonthFin > 11) {
//           currentMonthFin = 0;
//           currentYearFin++;
//       }
//       renderCalendar('fin');
//   });

//   // Fonction pour écouter les changements manuels dans les champs de date
//   dateDebutInput.addEventListener('input', () => {
//       const dateParts = dateDebutInput.value.split('/');
//       if (dateParts.length === 3) {
//           const [day, month, year] = dateParts;
//           selectedDateDebut = new Date(year, month - 1, day);
//           currentMonthDebut = selectedDateDebut.getMonth();
//           currentYearDebut = selectedDateDebut.getFullYear();
//           // Vérification et ajustement de la date de fin si nécessaire
//           adjustEndDate();
//           renderCalendar('debut');
//       }
//   });

//   dateFinInput.addEventListener('input', () => {
//     const dateParts = dateFinInput.value.split('/');
//     if (dateParts.length === 3) {
//         const [day, month, year] = dateParts;
//         selectedDateFin = new Date(year, month - 1, day);
//         currentMonthFin = selectedDateFin.getMonth();
//         currentYearFin = selectedDateFin.getFullYear();
//         adjustStartDate();  // Vérification et ajustement de la date de début
//         renderCalendar('fin');
//     }
//   });
//     // Initialisation des calendriers
//     renderCalendar('debut');
//     renderCalendar('fin');
// }


// $(document).ready(function () {
  
//   let selectedDateDebut = firstDayOfYear;
//   let selectedDateFin = today;
//   let currentMonthDebut = selectedDateDebut.getMonth();
//   let currentYearDebut = selectedDateDebut.getFullYear();
//   let currentMonthFin = selectedDateFin.getMonth();
//   let currentYearFin = selectedDateFin.getFullYear();
  
//   const dateDebutInput = document.getElementById('date-debut');
//   const dateFinInput = document.getElementById('date-fin');
//   const calendarDebut = document.getElementById('calendar-debut');
//   const calendarFin = document.getElementById('calendar-fin');
//   const currentMonthYearDebut = document.getElementById('current-month-year-debut');
//   const currentMonthYearFin = document.getElementById('current-month-year-fin');
//   const daysDebut = document.getElementById('days-debut');
//   const daysFin = document.getElementById('days-fin');

//   const currentDate = new Date();
//   const firstDayOfYear = new Date(currentDate.getFullYear(), 0, 1);
//   const today = new Date();

  

//   // Fonction pour afficher les jours dans le calendrier
//   function renderCalendar(inputType) {
//     let currentMonth, currentYear, selectedDate, calendar, currentMonthYear, daysContainer;

//     if (inputType === 'debut') {
//         currentMonth = currentMonthDebut;
//         currentYear = currentYearDebut;
//         selectedDate = selectedDateDebut;
//         calendar = calendarDebut;
//         currentMonthYear = currentMonthYearDebut;
//         daysContainer = daysDebut;
//     } else {
//         currentMonth = currentMonthFin;
//         currentYear = currentYearFin;
//         selectedDate = selectedDateFin;
//         calendar = calendarFin;
//         currentMonthYear = currentMonthYearFin;
//         daysContainer = daysFin;
//     }

//     daysContainer.innerHTML = '';
//     const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
//     const lastDateOfMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

//     currentMonthYear.textContent = `${new Date(currentYear, currentMonth).toLocaleString('fr-FR', { month: 'long' })} ${currentYear}`;

//     // Ajout des jours vides avant le début du mois
//     for (let i = 0; i < (firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1); i++) {
//         daysContainer.innerHTML += '<span class="disabled"></span>';
//     }

//     // Ajout des jours du mois
//     for (let day = 1; day <= lastDateOfMonth; day++) {
//         const dayElement = document.createElement('span');
//         dayElement.textContent = day;

//         // Créer une date pour le jour actuel
//         const currentDayDate = new Date(currentYear, currentMonth, day);

//         // Vérifier si la date est dans le futur
//         if (currentDayDate > today) {
//             dayElement.classList.add('disabled'); // Désactiver les dates futures
//         } else {
//             // Ajouter la classe 'selected' si c'est la date sélectionnée
//             if (
//                 day === selectedDate.getDate() &&
//                 currentMonth === selectedDate.getMonth() &&
//                 currentYear === selectedDate.getFullYear()
//             ) {
//                 dayElement.classList.add('selected');
//             }

//             // Ajouter un événement de clic pour sélectionner la date
//             dayElement.addEventListener('click', () => {
//                 if (inputType === 'debut') {
//                     selectedDateDebut = new Date(currentYear, currentMonth, day);
//                     dateDebutInput.value = selectedDateDebut.toLocaleDateString('fr-FR');
//                     adjustEndDate();
//                 } else {
//                     selectedDateFin = new Date(currentYear, currentMonth, day);
//                     dateFinInput.value = selectedDateFin.toLocaleDateString('fr-FR');
//                     adjustStartDate();
//                 }
//                 calendar.classList.remove('active');
//                 renderCalendar(inputType);
//             });
//         }

//         daysContainer.appendChild(dayElement);
//     }
//   }

//   // Fonction pour ajuster la date de fin si elle est inférieure à la date de début
//   function adjustEndDate() {
//       if (selectedDateFin < selectedDateDebut) {
//           // Si la date de fin est avant la date de début, la date de fin devient la date de début + 1 jour
//           selectedDateFin = new Date(selectedDateDebut);
//           selectedDateFin.setDate(selectedDateDebut.getDate() + 1);
//           dateFinInput.value = selectedDateFin.toLocaleDateString('fr-FR');
//           // Mise à jour du calendrier de la date de fin
//           currentMonthFin = selectedDateFin.getMonth();
//           currentYearFin = selectedDateFin.getFullYear();
//           renderCalendar('fin');
//       }
//   }

//   function adjustStartDate() {
//       if (selectedDateDebut > selectedDateFin) {
//           selectedDateDebut = new Date(selectedDateFin);
//           selectedDateDebut.setDate(selectedDateFin.getDate() - 1);
//           dateDebutInput.value = selectedDateDebut.toLocaleDateString('fr-FR');
//           currentMonthDebut = selectedDateDebut.getMonth();
//           currentYearDebut = selectedDateDebut.getFullYear();
//           renderCalendar('debut');
//       }
//   }

//   // Gestion de l'affichage du calendrier
//   dateDebutInput.addEventListener('click', () => {
//       calendarDebut.classList.toggle('active');
//       renderCalendar('debut');
//   });

//   dateFinInput.addEventListener('click', () => {
//       calendarFin.classList.toggle('active');
//       renderCalendar('fin');
//   });

//   // Passage au mois précédent ou suivant
//   document.getElementById('prev-month-debut').addEventListener('click', () => {
//       currentMonthDebut--;
//       if (currentMonthDebut < 0) {
//           currentMonthDebut = 11;
//           currentYearDebut--;
//       }
//       renderCalendar('debut');
//   });

//   document.getElementById('next-month-debut').addEventListener('click', () => {
//       currentMonthDebut++;
//       if (currentMonthDebut > 11) {
//           currentMonthDebut = 0;
//           currentYearDebut++;
//       }
//       renderCalendar('debut');
//   });

//   document.getElementById('prev-month-fin').addEventListener('click', () => {
//       currentMonthFin--;
//       if (currentMonthFin < 0) {
//           currentMonthFin = 11;
//           currentYearFin--;
//       }
//       renderCalendar('fin');
//   });

//   document.getElementById('next-month-fin').addEventListener('click', () => {
//       currentMonthFin++;
//       if (currentMonthFin > 11) {
//           currentMonthFin = 0;
//           currentYearFin++;
//       }
//       renderCalendar('fin');
//   });

//   // Fonction pour écouter les changements manuels dans les champs de date
//   dateDebutInput.addEventListener('input', () => {
//       const dateParts = dateDebutInput.value.split('/');
//       if (dateParts.length === 3) {
//           const [day, month, year] = dateParts;
//           selectedDateDebut = new Date(year, month - 1, day);
//           currentMonthDebut = selectedDateDebut.getMonth();
//           currentYearDebut = selectedDateDebut.getFullYear();
//           adjustEndDate();
//           renderCalendar('debut');
//       }
//   });

//   dateFinInput.addEventListener('input', () => {
//     const dateParts = dateFinInput.value.split('/');
//     if (dateParts.length === 3) {
//         const [day, month, year] = dateParts;
//         selectedDateFin = new Date(year, month - 1, day);
//         currentMonthFin = selectedDateFin.getMonth();
//         currentYearFin = selectedDateFin.getFullYear();
//         adjustStartDate();
//         renderCalendar('fin');
//     }
//   });

//   // Initialisation des calendriers
//   renderCalendar('debut');
//   renderCalendar('fin');
// });
// Variables globales
let selectedDateDebut = '';
let selectedDateFin = '';
let currentMonthDebut = '';
let currentYearDebut = '';
let currentMonthFin = '';
let currentYearFin = '';

// Fonction pour afficher les jours dans le calendrier
function renderCalendar(inputType, calendarElement, monthYearElement, daysContainer, selectedDate, currentMonth, currentYear, today, dateInput, adjustFunction) {
  daysContainer.innerHTML = '';
  const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
  const lastDateOfMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

  monthYearElement.textContent = `${new Date(currentYear, currentMonth).toLocaleString('fr-FR', { month: 'long' })} ${currentYear}`;

  // Ajout des jours vides avant le début du mois
  for (let i = 0; i < (firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1); i++) {
    daysContainer.innerHTML += '<span class="disabled"></span>';
  }

  // Ajout des jours du mois
  for (let day = 1; day <= lastDateOfMonth; day++) {
    const dayElement = document.createElement('span');
    dayElement.textContent = day;

    // Créer une date pour le jour actuel
    const currentDayDate = new Date(currentYear, currentMonth, day);

    // Vérifier si la date est dans le futur
    if (currentDayDate > today) {
      dayElement.classList.add('disabled'); // Désactiver les dates futures
    } else {
      // Ajouter la classe 'selected' si c'est la date sélectionnée
      if (
        day === selectedDate.getDate() &&
        currentMonth === selectedDate.getMonth() &&
        currentYear === selectedDate.getFullYear()
      ) {
        dayElement.classList.add('selected');
      }

      // Ajouter un événement de clic pour sélectionner la date
      dayElement.addEventListener('click', () => {
        selectedDate = new Date(currentYear, currentMonth, day);
        dateInput.value = selectedDate.toLocaleDateString('fr-FR');
        adjustFunction(); // Appeler la fonction d'ajustement
        calendarElement.classList.remove('active');
        renderCalendar(inputType, calendarElement, monthYearElement, daysContainer, selectedDate, currentMonth, currentYear, today, dateInput, adjustFunction);
      });
    }

    daysContainer.appendChild(dayElement);
  }
}

$(document).ready(function () {
  const dateDebutInput = document.getElementById('date-debut');
  const dateFinInput = document.getElementById('date-fin');
  const calendarDebut = document.getElementById('calendar-debut');
  const calendarFin = document.getElementById('calendar-fin');
  const currentMonthYearDebut = document.getElementById('current-month-year-debut');
  const currentMonthYearFin = document.getElementById('current-month-year-fin');
  const daysDebut = document.getElementById('days-debut');
  const daysFin = document.getElementById('days-fin');

  const currentDate = new Date();
  const firstDayOfYear = new Date(currentDate.getFullYear(), 0, 1);
  const today = new Date();

  selectedDateDebut = firstDayOfYear;
  selectedDateFin = today;
  currentMonthDebut = selectedDateDebut.getMonth();
  currentYearDebut = selectedDateDebut.getFullYear();
  currentMonthFin = selectedDateFin.getMonth();
  currentYearFin = selectedDateFin.getFullYear();

  // Fonction pour ajuster la date de fin si elle est inférieure à la date de début
  function adjustEndDate() {
    if (selectedDateFin < selectedDateDebut) {
      selectedDateFin = new Date(selectedDateDebut);
      selectedDateFin.setDate(selectedDateDebut.getDate() + 1);
      dateFinInput.value = selectedDateFin.toLocaleDateString('fr-FR');
      currentMonthFin = selectedDateFin.getMonth();
      currentYearFin = selectedDateFin.getFullYear();
      renderCalendar('fin', calendarFin, currentMonthYearFin, daysFin, selectedDateFin, currentMonthFin, currentYearFin, today, dateFinInput, adjustStartDate);
    }
  }

  function adjustStartDate() {
    if (selectedDateDebut > selectedDateFin) {
      selectedDateDebut = new Date(selectedDateFin);
      selectedDateDebut.setDate(selectedDateFin.getDate() - 1);
      dateDebutInput.value = selectedDateDebut.toLocaleDateString('fr-FR');
      currentMonthDebut = selectedDateDebut.getMonth();
      currentYearDebut = selectedDateDebut.getFullYear();
      renderCalendar('debut', calendarDebut, currentMonthYearDebut, daysDebut, selectedDateDebut, currentMonthDebut, currentYearDebut, today, dateDebutInput, adjustEndDate);
    }
  }

  // Gestion de l'affichage du calendrier
  dateDebutInput.addEventListener('click', () => {
    calendarDebut.classList.toggle('active');
    renderCalendar('debut', calendarDebut, currentMonthYearDebut, daysDebut, selectedDateDebut, currentMonthDebut, currentYearDebut, today, dateDebutInput, adjustEndDate);
  });

  dateFinInput.addEventListener('click', () => {
    calendarFin.classList.toggle('active');
    renderCalendar('fin', calendarFin, currentMonthYearFin, daysFin, selectedDateFin, currentMonthFin, currentYearFin, today, dateFinInput, adjustStartDate);
  });

  // Passage au mois précédent ou suivant
  document.getElementById('prev-month-debut').addEventListener('click', () => {
    currentMonthDebut--;
    if (currentMonthDebut < 0) {
      currentMonthDebut = 11;
      currentYearDebut--;
    }
    renderCalendar('debut', calendarDebut, currentMonthYearDebut, daysDebut, selectedDateDebut, currentMonthDebut, currentYearDebut, today, dateDebutInput, adjustEndDate);
  });

  document.getElementById('next-month-debut').addEventListener('click', () => {
    currentMonthDebut++;
    if (currentMonthDebut > 11) {
      currentMonthDebut = 0;
      currentYearDebut++;
    }
    renderCalendar('debut', calendarDebut, currentMonthYearDebut, daysDebut, selectedDateDebut, currentMonthDebut, currentYearDebut, today, dateDebutInput, adjustEndDate);
  });

  document.getElementById('prev-month-fin').addEventListener('click', () => {
    currentMonthFin--;
    if (currentMonthFin < 0) {
      currentMonthFin = 11;
      currentYearFin--;
    }
    renderCalendar('fin', calendarFin, currentMonthYearFin, daysFin, selectedDateFin, currentMonthFin, currentYearFin, today, dateFinInput, adjustStartDate);
  });

  document.getElementById('next-month-fin').addEventListener('click', () => {
    currentMonthFin++;
    if (currentMonthFin > 11) {
      currentMonthFin = 0;
      currentYearFin++;
    }
    renderCalendar('fin', calendarFin, currentMonthYearFin, daysFin, selectedDateFin, currentMonthFin, currentYearFin, today, dateFinInput, adjustStartDate);
  });

  // Fonction pour écouter les changements manuels dans les champs de date
  dateDebutInput.addEventListener('input', () => {
    const dateParts = dateDebutInput.value.split('/');
    if (dateParts.length === 3) {
      const [day, month, year] = dateParts;
      selectedDateDebut = new Date(year, month - 1, day);
      currentMonthDebut = selectedDateDebut.getMonth();
      currentYearDebut = selectedDateDebut.getFullYear();
      adjustEndDate();
      renderCalendar('debut', calendarDebut, currentMonthYearDebut, daysDebut, selectedDateDebut, currentMonthDebut, currentYearDebut, today, dateDebutInput, adjustEndDate);
    }
  });

  dateFinInput.addEventListener('input', () => {
    const dateParts = dateFinInput.value.split('/');
    if (dateParts.length === 3) {
      const [day, month, year] = dateParts;
      selectedDateFin = new Date(year, month - 1, day);
      currentMonthFin = selectedDateFin.getMonth();
      currentYearFin = selectedDateFin.getFullYear();
      adjustStartDate();
      renderCalendar('fin', calendarFin, currentMonthYearFin, daysFin, selectedDateFin, currentMonthFin, currentYearFin, today, dateFinInput, adjustStartDate);
    }
  });

  // Initialisation des calendriers
  renderCalendar('debut', calendarDebut, currentMonthYearDebut, daysDebut, selectedDateDebut, currentMonthDebut, currentYearDebut, today, dateDebutInput, adjustEndDate);
  renderCalendar('fin', calendarFin, currentMonthYearFin, daysFin, selectedDateFin, currentMonthFin, currentYearFin, today, dateFinInput, adjustStartDate);
});

// Fonction pour fermer le calendrier lorsque l'on clique en dehors
document.addEventListener('click', function (event) {
  const datePickers = document.querySelectorAll('.date-picker');
  
  datePickers.forEach(function (datePicker) {
      const inputField = datePicker.querySelector('input');
      const calendar = datePicker.querySelector('.calendar');

      // Vérification si le clic est en dehors du champ de saisie ou du calendrier
      if (!datePicker.contains(event.target)) {
          calendar.classList.remove('active'); // Cache le calendrier
      }
  });
});

function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}



// function addCalendarListener(calendarDebut, calendarFin, dateDebutInput, dateFinInput) {
//   // Écouteur pour la date de début
//   if (calendarDebut) {
//     calendarDebut.addEventListener('change', () => {
//       if (calendarDebut.value) {
//         dateDebutInput.value = calendarDebut.value;
//         console.log(`Date de début sélectionnée : ${calendarDebut.value}`);
//         handleDateChange();
//       }
//     });
//   }

//   // Écouteur pour la date de fin
//   if (calendarFin) {
//     calendarFin.addEventListener('change', () => {
//       if (calendarFin.value) {
//         dateFinInput.value = calendarFin.value;
//         console.log(`Date de fin sélectionnée : ${calendarFin.value}`);
//         handleDateChange();
//       }
//     });
//   }
// }

// Fonction de formatage de la date pour l'input
function formatDateForInput(year, monthIndex, day) {
  const formattedDay = day.padStart(2, '0');
  const formattedMonth = (monthIndex + 1).toString().padStart(2, '0');
  return `${formattedDay}/${formattedMonth}/${year}`;
}

// Fonction pour obtenir l'index du mois depuis son nom
function getMonthIndexFromName(monthName) {
  const months = [
      'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
      'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
  ];
  return months.indexOf(monthName.toLowerCase());
}

function createChartType(data, type) {
  // Recréation du sélecteur d'année pour le type de graphique actuel
  displayYearSelection(type);
  // displayCalandar();


  // Sélection des éléments du DOM
  const yearSelector = document.getElementById('yearSelector');
  const dateDebutInput = document.getElementById('date-debut');
  const dateFinInput = document.getElementById('date-fin');
  const calendarDebut = document.getElementById('calendar-debut');
  const calendarFin = document.getElementById('calendar-fin');

  // Fonction pour ajouter des écouteurs aux calendriers
  function addCalendarListeners(calendarElement, inputElement) {
    calendarElement.addEventListener('click', function (event) {
        // Vérifie si l'utilisateur clique sur un jour valide dans le calendrier
        if (event.target.tagName === 'SPAN' && !event.target.classList.contains('disabled')) {
            const selectedDay = event.target.innerText;
            const calendarHeader = calendarElement.querySelector('.calendar-header');
  
            // Dynamique : déterminer si c'est le calendrier de début ou de fin
            const isStartDate = calendarElement.id === 'calendar-debut';
            const currentMonthYear = calendarHeader.querySelector(
                isStartDate ? '#current-month-year-debut' : '#current-month-year-fin'
            ).innerText;
  
            // Récupère le mois et l'année depuis le header
            const [monthName, year] = currentMonthYear.split(' ');
            const monthIndex = getMonthIndexFromName(monthName);
            const formattedDate = formatDateForInput(year, monthIndex, selectedDay);
  
            // Mise à jour de l'input
            inputElement.value = formattedDate;
            // getSelectedYear(lastSelection.value, type);
            handleDateChange();
        }
  });
  }
  
   // Ajouter les écouteurs pour les calendriers
  //  addCalendarListener(calendarDebut, calendarFin, dateDebutInput, dateFinInput);  

  // Mise à jour selon la dernière sélection
  if (lastSelection.type === 'year') {
    yearSelector.value = lastSelection.value;
    getSelectedYear(lastSelection.value, type);
  } else if (lastSelection.type === 'range') {
    const [startDate, endDate] = lastSelection.value.split(' - ');
    dateDebutInput.value = startDate;
    dateFinInput.value = endDate;
    getSelectedYear(lastSelection.value, type);
  }


  // Fonction pour gérer les changements de plage de dates
  const handleDateChange = debounce(() => {
    if (dateDebutInput.value && dateFinInput.value) {
      lastSelection = { type: 'range', value: `${dateDebutInput.value} - ${dateFinInput.value}` };
      yearSelector.value = 'En période';
      getSelectedYear(lastSelection.value, currentChartType);
    }
  }, 300); // Attente de 300 ms après le dernier changement

 
  // Ajout des écouteurs pour les changements de date
  dateDebutInput.addEventListener('change', () => {
      // Supprimer les anciens écouteurs
   dateDebutInput.removeEventListener('change', handleDateChange);
    handleDateChange();
  });
  dateFinInput.addEventListener('change', () => {
    handleDateChange();
  });


  

    // Fonction pour gérer les changements d'année
    function handleYearChange() {
      lastSelection = { type: 'year', value: yearSelector.value };
      dateDebutInput.value = `01/01/${yearSelector.value}`;
      dateFinInput.value = (yearSelector.value === new Date().getFullYear().toString())
        ? new Date().toLocaleDateString('fr-FR') // Aujourd'hui si c'est l'année actuelle
        : `31/12/${yearSelector.value}`; // Fin d'année si différente
        getSelectedYear(yearSelector.value, type);
       // Fonction pour valider et convertir une date au format jj/mm/aaaa
  function parseDate(dateString) {
    const [day, month, year] = dateString.split('/').map(Number);
    return new Date(year, month - 1, day); // Mois est 0-indexé dans JavaScript
  }

      // Mettre à jour les dates sélectionnées avec validation
      selectedDateDebut = parseDate(dateDebutInput.value);
      selectedDateFin = parseDate(dateFinInput.value);

      // Vérifier si les dates sont valides
      if (isNaN(selectedDateDebut.getTime())) {
        console.error('La date de début n\'est pas valide.');
        selectedDateDebut = new Date(); // Définir la date d'aujourd'hui comme valeur par défaut
      }

      if (isNaN(selectedDateFin.getTime())) {
        console.error('La date de fin n\'est pas valide.');
        selectedDateFin = new Date(); // Définir la date d'aujourd'hui comme valeur par défaut
      }

      // Mettre à jour les mois et années actuels pour les calendriers
      currentMonthDebut = selectedDateDebut.getMonth();
      currentYearDebut = selectedDateDebut.getFullYear();
      currentMonthFin = selectedDateFin.getMonth();
      currentYearFin = selectedDateFin.getFullYear();

      // Récupérer les éléments du DOM
      const currentMonthYearDebut = document.getElementById('current-month-year-debut');
      const currentMonthYearFin = document.getElementById('current-month-year-fin');
      const daysDebut = document.getElementById('days-debut');
      const daysFin = document.getElementById('days-fin');
      const today = new Date();

      // Re-rendre les calendriers avec les paramètres nécessaires
      renderCalendar(
        'debut',
        calendarDebut,
        currentMonthYearDebut,
        daysDebut,
        selectedDateDebut,
        currentMonthDebut,
        currentYearDebut,
        today,
        dateDebutInput,
        ''
      );

      renderCalendar(
        'fin',
        calendarFin,
        currentMonthYearFin,
        daysFin,
        selectedDateFin,
        currentMonthFin,
        currentYearFin,
        today,
        dateFinInput,
        ''
      );

      
    }

    addCalendarListeners(calendarDebut, dateDebutInput);
    addCalendarListeners(calendarFin, dateFinInput); 
    // Supprimer les anciens écouteurs
    yearSelector.removeEventListener('change', handleYearChange);

    // Ajout des écouteurs
    yearSelector.addEventListener('change', handleYearChange);


    

  // Chargement des données pour le type de graphique sélectionné
  switch (type) {
    case 'pie':
      createChart(data, lastSelection.value);
      break;
    case 'bar':
      createInvertedBarChart(data, lastSelection.value);
      break;
    case 'line':
      createLineChart(data, lastSelection.value);
      break;
  }
}


// Fonction pour créer un graphique de type radial
function createChart(data, selectedYear) {
 // Supprimer l'ancien graphique et la légende
 d3.select("#chart").selectAll("*").remove();
  d3.select("#legend-container").selectAll("*").remove();
  d3.select('svg').selectAll("*").remove();

  // const numAgences = new Set(data.map(d => d.agence)).size;
  // const numDomaines = new Set(data.map(d => d.domaine)).size;

  // // Dimensions dynamiques pour le graphique
  // const width = Math.max(600, numAgences * 40);
  // const heightGraph = Math.max(650, numAgences * 80);

  const numAgences = new Set(data.map(d => d.agence)).size; // Nombre d'agences uniques
  const numDomaines = new Set(data.map(d => d.domaine)).size; // Nombre de domaines uniques
  
  // Dimensions dynamiques pour le graphique
  const baseWidth = 600; // Largeur de base
  const baseHeight = 650; // Hauteur de base
  const agenceWidth = 40; // Largeur supplémentaire par agence
  const agenceHeight = 60; // Hauteur supplémentaire par agence
  
  // Ajuster les dimensions en fonction du nombre d'agences
  let width, heightGraph;
  
  if (numAgences === 1) {
      // Taille pour une seule agence
      width = Math.max(baseWidth / 1.2, numDomaines * 20); // Largeur réduite
      heightGraph = Math.max(baseHeight / 1.2, numDomaines); // Hauteur réduite
  } else {
      // Dimensions normales pour plusieurs agences
      width = Math.max(baseWidth / 1.1, numAgences * agenceWidth);
      heightGraph = Math.max(baseHeight / 1.1, numAgences * agenceHeight);
  }

  const innerRadius = 50;
  const outerRadius = Math.min(width, heightGraph) / 2 - 20;

  // const hasValidData = data && data.some(d => d.montant1 > 0);
  const svg = d3.select("#chart")
  .append("svg")
  .attr("width", "100%")
  .attr("height", heightGraph + 20)
  .attr("viewBox", `0 0 ${width} ${numAgences === 1 ? heightGraph - 80 : heightGraph + 40}`);


const fontSize = selectedYear.length > 5 ? "14px" : "20px"; // Si la longueur est > 5, font-size = 8px, sinon 20px
let displayText = selectedYear;
  if (selectedYear.length > 5) {
      // Split à l'endroit du tiret et retourner à la ligne
      const parts = selectedYear.split(' - ');
      displayText = ' Période : ' + parts[0] + " - "  + parts[1];  
  }


  if (numAgences === 1) {
    // Regroupement des données par domaine
    const groupedData = d3.groups(data, d => d.domaine);

    // Récupérer toutes les agences uniques
    const agenceKeys = Array.from(new Set(data.flatMap(d => d.agence)));

    // Préparation des données groupées par domaine
    const stackedData = groupedData.map(([domaine, values]) => {
        const entry = { domaine };
        agenceKeys.forEach(agence => {
            const montant1 = values[0]?.montant1 || 0;
            entry.montant1 = montant1;
            entry[agence] = montant1;
            entry.color = values.find(v => v.agence === agence)?.color || '';
        });
        return entry;
    });


  // Les séries pour chaque agence
  const series = d3.stack()
      .keys(agenceKeys) // Utiliser les agences comme clés
      (stackedData);

  // Configuration de l'échelle x pour les domaines
  const x = d3.scaleBand()
      .domain(stackedData.map(d => d.domaine)) // Utiliser les domaines comme domaine de l'échelle
      .range([0, 2 * Math.PI]) // Plage pour un graphique circulaire
      .align(0);

  // Configurer l'échelle y pour les valeurs
  const y = d3.scaleRadial()
      .domain([0, d3.max(series, d => d3.max(d, d => d[1]))]) // Utiliser les montants comme domaine
      .range([innerRadius, outerRadius]); // Plage pour le rayon

  // Créer le groupe principal pour le graphique
  let chartGroup = svg.append("g")
      .attr("transform", `translate(${width / 2},${heightGraph / 2})`);
      const padding = 60;
      chartGroup.append("rect")
        .attr("x", -outerRadius - padding) 
        .attr("y", -outerRadius - padding ) 
        .attr("width", (outerRadius + padding) * 2) 
        .attr("height", (outerRadius) * 2) 
        .attr("fill", "#fafafa") 
        .attr("fill-opacity", 0.9)
        .attr("rx", 10) 
        .attr("ry", 10); 
      // Ajouter le texte dans le rectangle
    chartGroup.append("text")
      .attr("x", -outerRadius - padding + 20) // Décalage de 20px à partir du bord gauche
      .attr("y", -outerRadius - padding + 40) // Décalage de 40px à partir du bord haut
      .attr("class", "selected-year-label")
      .style("font-size", fontSize)
      .style("font-weight", "600")
      .style("font-family", "'Poppins', sans-serif")
      .style("fill", "#0056b3")
      .html('📅' + displayText);

  // Créer les groupes pour chaque série
  const arcGroups = chartGroup.selectAll("g")
      .data(series)
      .join("g")
      .attr("class", "arc-group");

    // Ajouter les arcs pour chaque domaine
    arcGroups.selectAll("path")
        .data(d => d)
        .join("path")
        .attr("fill", d => {
            const domaine = d.data.domaine; // Accéder au domaine
            const foundData = data.find(item => item.domaine === domaine);
            return foundData ? foundData.color : "#ccc"; // Couleur par défaut si non trouvé
        })
        .attr("d", d3.arc()
            .innerRadius(d => y(d[0]))
            .outerRadius(d => y(d[1]))
            .startAngle(d => x(d.data.domaine)) 
            .endAngle(d => x(d.data.domaine) + x.bandwidth()) 
            .padAngle(0.01) // Espacement entre les arcs
        );

      // Ajout des labels pour les domaines
      const uniqueDomaines = new Set();
      arcGroups.selectAll("text")
          .data(d => d)
          .join("text")
          .attr("transform", d => {
              const domaine = d.data.domaine; 
              const angle = (x(domaine) + x.bandwidth() / 2) * (180 / Math.PI) - 90; 
              const radius = (y(d[0]) + y(d[1])) / 2 + 10; 

              // Ajust de la position pour éviter les chevauchements
              const offset = 15; // Décalage supplémentaire pour les labels
              const xOffset = Math.cos(angle * (Math.PI / 180)) * (radius + offset);
              const yOffset = Math.sin(angle * (Math.PI / 180)) * (radius + offset);

              return `translate(${xOffset}, ${yOffset})`;
          })
          .attr("dy", "0.35em")
          .text(d => {
              const domaine = d.data.domaine; // Accéder au domaine
              if (!uniqueDomaines.has(domaine)) {
                  uniqueDomaines.add(domaine);
                  return domaine;
              }
              return '';
          })
          .style("text-anchor", d => {
              const angle = (x(d.data.domaine) + x.bandwidth() / 2) * (180 / Math.PI) - 90;
              return (angle > 90 && angle < 270) ? "end" : "start"; // Ajuster l'ancrage du texte
          })
          .style("font-size", "12px")
          .style("font-weight", "bold")
          .style("fill", "#ff7f0e");

        // Ajouter des infobulles
        arcGroups.selectAll("path")
            .append("title")
            .text(d => {
              console.log('mon', d.data);
                const domaine = d.data.domaine || 'Domaine inconnu'; // Accéder au domaine
                const montant1 = d.data.montant1 || 0; 
                return `${domaine}: ${montant1}€`;
            });
  } else { 
    // Ajout du libellé pour l'année sélectionnée
     // Créer le groupe principal pour le graphique
    let chartGroup1 = svg.append("g")
     .attr("transform", `translate(${width / 2},${heightGraph / 2})`);
    let padding1 = 140;
     chartGroup1.append("rect")
      .attr("x", -outerRadius - padding1) 
      .attr("y", -outerRadius - padding1 ) 
      .attr("width", (outerRadius + padding1) * 2) 
      .attr("height", (outerRadius + padding1) * 2) 
      .attr("fill", "#fafafa") 
      .attr("fill-opacity", 0.9)
      .attr("rx", 10) 
      .attr("ry", 10); 
  // Ajouter le texte dans le rectangle
  chartGroup1.append("text")
    .attr("x", -outerRadius - padding1 + 60) // Décalage de 20px à partir du bord gauche
    .attr("y", -outerRadius - 10) // Décalage de 40px à partir du bord haut
    .attr("class", "selected-year-label")
    .style("font-size", fontSize) 
    .style("font-weight", "600") 
    .style("font-family", "'Poppins', sans-serif") 
    .style("fill",  "#0056b3") 
    // .style("text-shadow", "1px 1px 3px rgba(0, 0, 0, 0.3)") 
  .html('📅' + displayText);
  // Regrouper les données par agence
  const groupedData = d3.groups(data, d => d.agence);

  // Récupérer tous les domaines uniques
  const domaineKeys = Array.from(new Set(data.flatMap(d => d.domaine)));

  // Préparation des données groupées sans domaines vides
  const stackedData = groupedData.map(([agence, values]) => {
    const entry = { agence };
    domaineKeys.forEach(domaine => {
        const montant1 = values.find(v => v.domaine === domaine)?.montant1 || 0.0;
        entry[domaine] = montant1;
        entry.color = values.find(v => v.domaine === domaine)?.color || '';
    });
    return entry;
});

const series = d3.stack()
    .keys(domaineKeys)
    (stackedData);

const x = d3.scaleBand()
    .domain(stackedData.map(d => d.agence))
    .range([0, 2 * Math.PI])
    .align(0);

const y = d3.scaleRadial()
    .domain([0, d3.max(series, d => d3.max(d, d => d[1]))])
    .range([innerRadius, outerRadius]);



const chartGroup = svg.append("g")
  .attr("transform", "translate(" + (width / 2) + "," + (heightGraph / 1.8) + ") scale(1.2, 1.2)");

const arcGroups = chartGroup.selectAll("g")
  .data(series)
  .join("g")
  .attr("class", "arc-group");

arcGroups.selectAll("path")
  .data(d => d.slice().sort((a, b) => {
      const valueA = a[1] - a[0];
      const valueB = b[1] - b[0];
      return valueA === 0 ? 1 : valueB === 0 ? -1 : 0;
  }))
  .join("path")
  .attr("fill", d => {
    const agence = d.data.agence;
    const montantCalcule = parseFloat((d[1] - d[0]).toFixed(2));

    // Recherche du domaine correspondant au montant calculé
    let domaine = null;
    let montant1 = 0;

      for (const key in d.data) {
          if (key !== 'agence' && key !== 'color') {
              const montantDomaine = parseFloat(d.data[key]);
              if (Math.abs(montantDomaine - montantCalcule) < 0.01) {  // Tolérance pour la comparaison
                  domaine = key;
                  montant1 = montantDomaine;
                  break;
              }
          }
      }

      // Si le domaine est trouvé, on cherche la couleur associée
      let color = "#ccc"; // couleur par défaut
      if (domaine) {
          const foundData = data.find(item => item.agence === agence && item.domaine === domaine);
          color = foundData ? foundData.color : color;  // Si trouvé, utiliser la couleur, sinon garder la couleur par défaut
      }

      return color;
  })
  .attr("d", d3.arc()
  .innerRadius(d => y(d[0]))
  .outerRadius(d => y(d[1]))
  .startAngle(d => x(d.data.agence))
  .endAngle(d => x(d.data.agence) + x.bandwidth())
  .padAngle(0.01) // Ajout d'un espacement entre les agences
);


// Ajout de l'infobulle avec les détails
// arcGroups.selectAll("path")
//     .append("title")
//     .text(d => {
//         const montantCalcule = parseFloat((d[1] - d[0]).toFixed(2));
//         const agence = d.data.agence || 'Aucune Agence';
//         const domaine = Object.keys(d.data).find(key => d.data[key] === montantCalcule && key !== 'agence' && key !== 'color');
//         const montant1 = d.data[domaine] !== undefined ? d.data[domaine] : 0;
//         return agence + " - " + domaine + ": " + montant1 + "€";
//     });

arcGroups.selectAll("path")
  .append("title")
  .text(d => {
      // Calcul du montant
      const montantCalcule = parseFloat((d[1] - d[0]).toFixed(2));
      const agence = d.data.agence || 'Aucune Agence';

      // Chercher le domaine correspondant au montant calculé
      let domaine = null;
      let montant1 = 0;

      // Recherche du domaine qui correspond au montant calculé
      for (const key in d.data) {
          if (key !== 'agence' && key !== 'color') {
              // Comparaison avec tolérance
              const montantDomaine = parseFloat(d.data[key]);
              if (Math.abs(montantDomaine - montantCalcule) < 0.01) {  // Tolérance de 0.01
                  domaine = key;
                  montant1 = montantDomaine;
                  break;
              }
          }
      }

      // Le cas où le domaine n'est pas trouvé
      if (!domaine) {
          domaine = 'Domaine inconnu';
          montant1 = 0;
      }

      // Le texte formaté avec le domaine trouvé
      return `${agence} - ${domaine}: ${montant1}€`;
  });
//   const uniqueAgences = new Set(); // Pour éviter les répétitions
// const agenceDomains = new Map(); // Pour stocker les domaines (segments) par agence

// // Étape 1 : Regrouper les segments par agence
// arcGroups.selectAll("text")
//   .data(d => d)
//   .each(d => {
//     const agence = d.data.agence;
//     const angle = (x(d.data.agence) + x.bandwidth() / 2) * (180 / Math.PI) - 90; // Angle du segment
//     const radius = (y(d[0]) + y(d[1])) / 2 + 10; // Rayon moyen du segment

//     if (!agenceDomains.has(agence)) {
//       agenceDomains.set(agence, { angles: [], radii: [] });
//     }
//     agenceDomains.get(agence).angles.push(angle);
//     agenceDomains.get(agence).radii.push(radius);
//   });

// // Étape 2 : Afficher le texte une seule fois par agence, centré sur ses segments
// arcGroups.selectAll("text")
//   .data(d => d)
//   .join("text")
//   .text(d => {
//     const agence = d.data.agence;
//     if (!uniqueAgences.has(agence)) {
//       uniqueAgences.add(agence);
//       return agence;
//     }
//     return '';
//   })
//   .style("text-anchor", "middle")
//   .style("font-size", "12px")
//   .style("font-weight", "bold")
//   .style("pointer-event", "none")
//   .style("fill", "#ff7f0e")
//   .attr("transform", d => {
//     const agence = d.data.agence;
//     if (agenceDomains.has(agence)) {
//       const positions = agenceDomains.get(agence);
//       const avgAngle = positions.angles.reduce((a, b) => a + b, 0) / positions.angles.length; // Angle moyen
//       const avgRadius = positions.radii.reduce((a, b) => a + b, 0) / positions.radii.length; // Rayon moyen
//       return `translate(${Math.cos(avgAngle * (Math.PI / 180)) * avgRadius}, ${Math.sin(avgAngle * (Math.PI / 180)) * avgRadius})`;
//     }
//     return ''; // Si aucune position n'est trouvée
//   })
//   .attr("transform", d => {
//     const angle = (x(d.data.agence) + x.bandwidth() / 2) * (180 / Math.PI) - 90;
//     const radius = (y(d[0]) + y(d[1])) / 2;
//     return 'translate(' + (Math.cos(angle * (Math.PI / 180)) * radius) + ', ' + (Math.sin(angle * (Math.PI / 180)) * radius) + ')';
//   })
//   .attr("dy", "0.35em");
    // Ajouter les labels d'agence
    const uniqueAgences = new Set();
    arcGroups.selectAll("text")
        .data(d => d)
        .join("text")
        .attr("transform", d => {
            const angle = (x(d.data.agence) + x.bandwidth() / 2) * (180 / Math.PI) - 90;
            const radius = (y(d[0]) + y(d[1])) / 2 + 10;
            return 'translate(' + (Math.cos(angle * (Math.PI / 180)) * radius) + ', ' + (Math.sin(angle * (Math.PI / 180)) * radius) + ')';
        })
        .attr("dy", "0.35em")
        .text(d => {
            const agence = d.data.agence;
            if (!uniqueAgences.has(agence)) {
                uniqueAgences.add(agence);
                return agence;
            }
            return '';
        })
        .style("text-anchor", "middle")
        .style("font-size", "12px")
        .style("fill", "white");
  
}
  


  // Légende dynamique structurée par agence
  let legendContainer = d3.select("#legend-container");

  // Regrouper les données par agence
  const groupedLegendData = d3.groups(data, d => d.agence);

  if (numAgences === 1) {
      // const legendContainer = document.getElementById("legend-container");
      legendContainer.style.display = "contents";
      legendContainer.selectAll(".agency-legend")
      .data(groupedLegendData)
      .enter()
      .append("div")
      .attr("class", "agency-legend")
      .style("margin", "10px 0")
      .style("padding", "10px")
      .style("width", "95%")
      .style("background-color", "#f9f9f9")
      .each(function([agence, items]) {
          // Titre de l'agence
          d3.select(this).append("div")
              .attr("class", "agency-title")
              .style("font-weight", "bold")
              .style("font-size", "14px")
              .style("margin-bottom", "5px")
              .text(agence);

            let domainContainer;
            if(numAgences == 1) {
              const chartContainer = document.getElementById("legend-container");
              chartContainer.style.display = "contents";
               // Légende pour chaque domaine de l'agence
                 domainContainer = d3.select(this).append("div")
                  .attr("class", "domain-legend")
                  .style("display", "grid")
                  .style("grid-template-columns", "repeat(4,1fr)"); 
            }else {
              const chartContainer = document.getElementById("legend-container");
              chartContainer.style.display = "grid";
              // Légende pour chaque domaine de l'agence
              domainContainer = d3.select(this).append("div")
              .attr("class", "domain-legend")
              .style("display", "flex")
              .style("flex-wrap", "wrap")
              .style("justify-content", "flex-start"); // Alignement des éléments à gauche
            }
            
         
    
          domainContainer.selectAll(".legend-item")
              .data(items)
              .enter()
              .append("div")
              .attr("class", "legend-item")
              .style("display", "flex")
              .style("align-items", "center")
              .style("margin", "5px 10px")
              .each(function(d) {
                  // Carré de couleur pour chaque domaine avec taille fixe
                  d3.select(this).append("div")
                      .style("width", "15px")   
                      .style("height", "15px")  
                      .style("background-color", getDomainColor(d.agence, d.domaine, data))
                      .style("margin-right", "8px")
                      .style("flex-shrink", "0"); // Empéche le carré de se rétrécir
    
                  // Texte du domaine et montant
                  d3.select(this).append("div")
                      .style("font-size", "12px")
                      .style("flex-grow", "1")  // Permet au texte de s'étirer sans affecter le carré
                      .style("font-family", "Arial, sans-serif")
                      // .style("line-height", "12px")
                      .style("text-align", "left")
                      .text(d.domaine + ": " + formatMontant(d.montant1) + "€");
              });
      });
  } else {
    // const legendContainer = document.getElementById("legend-container");
    legendContainer.style.display = "flex";
    legendContainer.selectAll(".agency-legend")
    .data(groupedLegendData)
    .enter()
    .append("div")
    .attr("class", "agency-legend")
    .style("margin", "10px 0")
    .style("padding", "10px")
    .style("width", "90%")
    .style("background-color", "#f9f9f9")
    .each(function([agence, items]) {
        // Titre de l'agence
        d3.select(this).append("div")
            .attr("class", "agency-title")
            .style("font-weight", "bold")
            .style("font-size", "14px")
            .style("margin-bottom", "5px")
            .text(agence);
  
        // Légende pour chaque domaine de l'agence
        const domainContainer = d3.select(this).append("div")
            .attr("class", "domain-legend")
            .style("display", "flex")
            .style("flex-wrap", "wrap")
            .style("justify-content", "flex-start"); // Alignement des éléments à gauche
  
        domainContainer.selectAll(".legend-item")
            .data(items)
            .enter()
            .append("div")
            .attr("class", "legend-item")
            .style("display", "flex")
            .style("align-items", "center")
            .style("margin", "5px 10px")
            .each(function(d) {
                // Carré de couleur pour chaque domaine avec taille fixe
                d3.select(this).append("div")
                    .style("width", "15px")   
                    .style("height", "15px")  
                    .style("background-color", getDomainColor(d.agence, d.domaine, data))
                    .style("margin-right", "8px")
                    .style("flex-shrink", "0"); // Empéche le carré de se rétrécir
  
                // Texte du domaine et montant
                d3.select(this).append("div")
                    .style("font-size", "12px")
                    .style("flex-grow", "1")  // Permet au texte de s'étirer sans affecter le carré
                    .style("font-family", "Arial, sans-serif")
                    // .style("line-height", "12px")
                    .style("text-align", "left")
                    .text(d.domaine + ": " + formatMontant(d.montant1) + "€");
            });
    });
  }
}

function renderChartOrEmpty(data, selectedYear) {

  // Suppression de l'ancien contenu
  d3.select("#chart").selectAll("*").remove();
  d3.select("#legend-container").selectAll("*").remove();
  d3.select('svg').selectAll("*").remove();
  // // Ajout d'un message temporaire pendant le délai de 10 secondes
  // const loadingMessage = d3.select("#chart")
  //     .append("div")
  //     .attr("id", "loading-message")
  //     .style("text-align", "center")
  //     .style("font-size", "16px")
  //     .style("color", "#666")
  //     .text("Chargement en cours... Veuillez patienter.");

  // Délai de 10 secondes avant d'exécuter la logique principale
  // setTimeout(() => {
      // Supprimer le message de chargement
      d3.select("#loading-message").remove();

      // Dimensions du graphique
      const width = 400;
      const height = 300;

      // Vérifier si les données sont valides
      const hasValidData = data.some(d => d.montant1 > 0);

      // Si les données sont invalides (vides ou avec des montants à 0)
      if (!hasValidData) {
        const fontSize = selectedYear.length > 5 ? "14px" : "20px";
        let displayText = selectedYear;
        if (selectedYear.length > 5) {
            // Split à l'endroit du tiret et retourner à la ligne
            const parts = selectedYear.split(' - ');
            displayText = '📅 Début: ' + parts[0] + "<br>" + '📅 Fin: ' + parts[1]; 
        }
         // Ajout du libellé pour l'année sélectionnée
         d3.select("#chart").append("text")
              .attr("x", width) 
              .attr("y", 40) 
              .attr("class", "selected-year-label")
              .style("font-size", fontSize) 
              .style("font-weight", "600") 
              .style("font-family", "'Poppins', sans-serif") 
              .style("text-align", "justify")
              .style("margin", "15px")
              .style("fill", "rgb(0, 86, 179")  
              .html(displayText);
              
          const svg = d3.select("#chart")
              .append("svg")
              .attr("width", width)
              .attr("height", height)
              .attr("viewBox", `0 0 ${width} ${height}`)
              .style("background-color", "#f9f9f9") 
              .style("border", "1px solid #dcdcdc") 
              .style("box-shadow", "0px 4px 8px rgba(0, 0, 0, 0.1)"); 

          // Ajout d'une icône de graphique au centre
          svg.append("text")
              .attr("x", width / 2)
              .attr("y", height / 2 - 20)
              .attr("text-anchor", "middle")
              .style("font-size", "60px")
              .style("fill", "#d0d0d0")
              .style("font-family", "'Font Awesome 5 Free'")
              .style("font-weight", "900")
              .text("\uf080");

          // Ajout d'un texte principal sous l'icône
          svg.append("text")
              .attr("x", width / 2)
              .attr("y", height / 2 + 30)
              .attr("text-anchor", "middle")
              .style("font-size", "16px")
              .style("font-weight", "bold")
              .style("fill", "#999")
              .text("Aucune donnée disponible");

          // Ajout d'un texte explicatif
          svg.append("text")
              .attr("x", width / 2)
              .attr("y", height / 2 + 60)
              .attr("text-anchor", "middle")
              .style("font-size", "12px")
              .style("fill", "#bbb")
              .text("Veuillez vérifier les filtres ou ajouter des données.");

              

      // Légende dynamique structurée par agence
      // const legendContainer = d3.select("#legend-container");
      // // Regrouper les données par agence
      // const groupedLegendData = d3.groups(data, d => d.agence);
      // legendContainer.selectAll(".agency-legend")
      //   .data(groupedLegendData)
      //   .enter()
      //   .append("div")
      //   .attr("class", "agency-legend")
      //   .style("margin", "10px 0")
      //   .style("padding", "10px")
      //   .style("width", "95%")
      //   .style("background-color", "#f9f9f9")
      //   .each(function([agence, items]) {
      //       // Titre de l'agence
      //       d3.select(this).append("div")
      //           .attr("class", "agency-title")
      //           .style("font-weight", "bold")
      //           .style("font-size", "14px")
      //           .style("margin-bottom", "5px")
      //           .text(agence);

      //       // Légende pour chaque domaine de l'agence
      //       const domainContainer = d3.select(this).append("div")
      //           .attr("class", "domain-legend")
      //           .style("display", "flex")
      //           .style("flex-wrap", "wrap")
      //           .style("justify-content", "flex-start"); // Alignement des éléments à gauche

      //       domainContainer.selectAll(".legend-item")
      //           .data(items)
      //           .enter()
      //           .append("div")
      //           .attr("class", "legend-item")
      //           .style("display", "flex")
      //           .style("align-items", "center") // Assurez-vous que les éléments sont alignés verticalement
      //           .style("margin", "5px 10px")
      //           .each(function(d) {
      //               // Carré de couleur pour chaque domaine avec taille fixe
      //               d3.select(this).append("div")
      //                   .style("width", "15px")   
      //                   .style("height", "15px")  
      //                   .style("background-color", getDomainColor(d.agence, d.domaine, data))
      //                   .style("margin-right", "8px")
      //                   .style("flex-shrink", "0"); // Empéche le carré de se rétrécir

      //               // Texte du domaine et montant
      //               d3.select(this).append("div")
      //                   .style("font-size", "12px")
      //                   .style("flex-grow", "1")  // Permet au texte de s'étirer sans affecter le carré
      //                   .style("font-family", "Arial, sans-serif")
      //                   // .style("line-height", "12px")
      //                   .style("text-align", "left")
      //                   .text(d.domaine + ": " + formatMontant(d.montant1) + "€");
      //           });
      //   });
      } 
  // }, 100); 
}


// Fonction pour créer un graphique de type bar
function createInvertedBarChart(data, selectedYear) {
// Suppression du graphique précédent
d3.select("#chart").html("");
d3.select('svg').selectAll("*").remove();

const width = 928; // Largeur fixe du graphique
const marginTop = 30;
const marginRight = 10;
const marginBottom = 60;
let marginLeft = 30; // On le rend dynamique

// Grouper les données par agence
const dataByAgence = d3.group(data, d => d.agence);
const normalizedData = [];



const agences = Array.from(dataByAgence.keys());
const domaines = Array.from(new Set(data.map(d => d.domaine))); // Liste des domaines uniques

// const totalM1 = Array.from(new Set(data.map(d => d.montant1)));
// const totalM2 = Array.from(new Set(data.map(d => d.montant2))).filter(value => value == 0);
// Calculer la somme des montants pour chaque agence
// const maxSum = d3.max(Array.from(dataByAgence.values()), agenceData => {
//     return d3.sum(agenceData, d => d.montant2, d => d.montant1); // Somme des montants1 et montant2 pour chaque agence
// });
const maxSum = d3.max(Array.from(dataByAgence.values()), agenceData => {
  return d3.sum(agenceData, d => Math.max(d.montant1, d.montant2)); // le max entre montant1 et montant2 pour chaque domaine
});
// Calculer la largeur dynamique de `marginLeft`
const tempSvg = d3.select("body").append("svg"); // SVG temporaire pour mesurer
const maxLabelWidth = Math.max(...agences.map(agence => {
    const textElement = tempSvg.append("text")
        .attr("font-size", "12px")
        .text(agence);
    const width = textElement.node().getBBox().width;
    textElement.remove(); // Supprime le texte temporaire
    return width;
}));
tempSvg.remove(); // Suppression du SVG temporaire

marginLeft = maxLabelWidth + 10; // Ajout d'un espace de 10 px aprés le texte

// Suite du code avec le `marginLeft` dynamique
const height = 20 * domaines.length + marginTop + marginBottom;
const barHeight = Math.max(30, (height - marginTop - marginBottom) / domaines.length); // Ajustement de la hauteur des barres

const x = d3.scaleLinear()
    .domain([0, maxSum])  // Utilisation de la somme maximale pour l'échelle
    .range([marginLeft, width - marginRight]);

    const isExist = height < 320; 
    const hasMultipleAgences = agences.length < 3;
console.log('agence', agences);
    const yRange = isExist
    ? (hasMultipleAgences ? height + marginBottom - 120 : height + marginBottom - 30) // Cas où la hauteur est petite
    : height - marginBottom + 60; // Cas normal

    const padding = hasMultipleAgences
    ? 0.6 
    : (isExist ? 0.3 : 0.4); 

  const y = d3.scaleBand()
      .domain(agences)
      .range([marginTop, yRange]) // Utilisation de la plage calculée
      .padding(padding);

  // const y = d3.scaleBand()
  // .domain(agences)
  // .range([marginTop, height + marginBottom]) // Ajust de la plage selon impair/pair
  // .padding(0.3);

// Création d'un dictionnaire de couleurs basé sur les domaines (si chaque domaine a une couleur spécifique dans les données, sinon une génération automatique d'uen couleur)
const color = d3.scaleOrdinal()
    .domain(domaines)
    .range(domaines.map(domaine => {
        // Ici on suppose que chaque domaine dans 'data' a une propriété 'color'
        const domaineData = data.find(d => d.domaine === domaine);
        
        return domaineData ? domaineData.color : d3.schemeCategory10[domaines.indexOf(domaine) % d3.schemeCategory10.length]; // Couleur par défaut
    }));


const svg = d3.select("#chart").append("svg")
    .attr("width", width)
    .attr("height", height + marginBottom)
    .attr("viewBox", [0, 0, width, height + marginBottom])
    .attr("style", "max-width: 100%; height: auto;");
    
    const fontSize = selectedYear.length > 5 ? "14px" : "20px";
    let displayText = selectedYear;
        if (selectedYear.length > 5) {
            // Split à l'endroit du tiret et retourner à la ligne
            const parts = selectedYear.split(' - ');
            displayText = ' Période : ' + parts[0] + " - "  + parts[1]; 
        }
     // Ajout du libellé pour l'année sélectionnée
     svg.append("text")
      .attr("x", width - marginRight) 
      .attr("y", height - marginBottom + marginBottom + 40) 
      .attr("class", "selected-year-label")
      .style("font-size", fontSize) 
      .style("font-weight", "600") 
      .style("font-family", "'Poppins', sans-serif") 
      .style("fill", "#0056b3") 
      .style("text-anchor", "end")  // Alignement à droite
      .html('📅' + displayText);

      const groupSpacing = 50;
// Création d'une barre pour chaque agence et chaque domaine pour 'montant1' et 'montant2'
dataByAgence.forEach((values, agence) => {
  
    const agencesGroup = svg.append("g")
      .attr("transform", `translate(0, ${y(agence)})`);
        // .attr("transform", "translate(0," + y(agence) + ")");

    // Calcul de la somme des montants1 et montants2 pour chaque agence
    const totalMontant1 = d3.sum(values, d => d.montant1);
    const totalMontant2 = d3.sum(values, d => d.montant2);

    // Calcul de la différence en pourcentage pour l'agence
    const differenceAgence = totalMontant1 - totalMontant2;
    const percentageDifferenceAgence = totalMontant1 === 0 ? 0 : (differenceAgence / totalMontant1) * 100;
    const formattedPercentageAgence = percentageDifferenceAgence.toFixed(1) + "%";

    const percentageRentabiliteRecette = totalMontant1 === 0 ? 0 : (differenceAgence / totalMontant1) * 100;
    const formattedRentabiliteRecette = percentageRentabiliteRecette.toFixed(1) + "%";

    let currentX1 = 0; // Début de la position horizontale pour la barre montant1
    let currentX2 = 0; // Début de la position horizontale pour la barre montant2


    values.forEach(d => {
        // Calcul de la différence en pourcentage
        const difference = d.montant1 - d.montant2;
        const percentageDifference = d.montant1 === 0 ? 0 : (difference / d.montant1) * 100;  // évite la division par zéro
        const formattedPercentage = percentageDifference.toFixed(1) + "%";

        // Calcul du pourcentage de montant2 du domaine par rapport au total montant1
        const percentageCA = totalMontant2 === 0 ? 0 : (d.montant2 / totalMontant1) * 100;
        const formattedPercentageCA = percentageCA.toFixed(1) + "%";

        const percentRentRecette = d.montant1 === 0 ? 0 : (difference / d.montant1) * 100;  // pas de division par zéro
        const formattedRentRecette = percentRentRecette.toFixed(1) + "%";

        // Création de la barre pour 'montant1' CA
        agencesGroup.append("rect")
            .attr("x", x(currentX1))
            .attr("y", 0)
            .attr("width", x(d.montant1) - x(0))
            .attr("height", barHeight / 2)
            .attr("fill", getDomainColor(d.agence, d.domaine, data))
            .append("title")
            .text(
                d.agence + " - " + d.domaine +
                " - Recette : " + formatMontant(d.montant1) + "€" +
                " | Rentabilité - " + d.domaine + " : " + (difference >= 0 ? "+" : "") + formattedRentRecette +
                " | Rentabilité - " + d.agence + " : " + (differenceAgence >= 0 ? "+" : "") + formattedRentabiliteRecette
            );
          currentX1 += d.montant1;
      
        // Création de la barre pour 'montant2' Dépenses
        agencesGroup.append("rect")
            .attr("x", x(currentX2))
            .attr("y", 20)
            .attr("width", x(d.montant2) - x(0))
            .attr("height", barHeight / 2)
            .attr("fill", getDomainColor(d.agence, d.domaine, data))
            .append("title")
            .text(
                d.agence + " - " + d.domaine +
                " - Dépenses : " + formatMontant(d.montant2) + "€" +
                " | Rentabilité - " + d.domaine + " : " + (difference >= 0 ? "+" : "") + formattedPercentage +
                " | Rentabilité - " + d.agence + " : " + (differenceAgence >= 0 ? "+" : "") + formattedPercentageAgence +
                " | Proportion du CA : " + formattedPercentageCA
            );

        currentX2 += d.montant2;
    });
    
});

// Ajout des axes X et Y avec le `marginLeft` dynamique
svg.append("g")
.attr("transform", "translate(0," + marginTop + ")")
.call(d3.axisTop(x).ticks(width / 100 + 2, "s")) // Ajout d'une 1 graduation é la fin
.call(g => g.selectAll(".domain").remove());

svg.append("g")
    .attr("transform", "translate(" + marginLeft + ",0)")
    .call(d3.axisLeft(y).tickSizeOuter(0))
    .call(g => g.selectAll(".domain").remove());

// Légende dynamique pour les domaines sous chaque agence
const legendContainer = d3.select("#legend-container");
legendContainer.html(""); // Pour vider le contenu de la légende avant de la remplir


agences.forEach(agence => {
    const agencyLegend = legendContainer.append("div")
        .attr("class", "agency-legend")
        .style("margin", "10px 0")
        .style("padding", "10px")
        .style("width", "95%")
        .style("border-radius", "5px")
        .style("background-color", "#f9f9f9");

    // Titre de l'agence
    agencyLegend.append("div")
        .attr("class", "agency-title")
        .style("font-weight", "bold")
        .style("font-size", "14px")
        .style("margin-bottom", "8px")
        .text(agence);

    // Filtrer les données pour l'agence actuelle
    const agenceData = data.filter(d => d.agence === agence);
    const numAgences = new Set(data.map(d => d.agence)).size;
    let domainesContainer;
    if(numAgences == 1) {
      const chartContainer = document.getElementById("legend-container");
      chartContainer.style.display = "contents";
       // Légende pour chaque domaine de l'agence
       domainesContainer = agencyLegend.append("div")
          .attr("class", "domain-legend")
          .style("display", "grid")
          .style("grid-template-columns", "repeat(4,1fr)"); 
    }else{
      const chartContainer = document.getElementById("legend-container");
      chartContainer.style.display = "grid";
        // Conteneur pour les domaines
        domainesContainer = agencyLegend.append("div")
        .attr("class", "domaines-container")
        .style("display", "flex")
        .style("flex-direction", "column");
    }
    

    // Parcourir les domaines et ajout des informations
    agenceData.forEach(d => {
        
        domainesContainer.append("div")
        .attr("class", "domaine-item")
        .style("display", "flex")
        .style("align-items", "left")
        .style("margin-bottom", "5px")
        .each(function () {
            // Vérification si le domaine n'est pas vide
            // const domainColor = color(d.domaine) ? color(d.domaine) : "#ccc";
            // Vérification si le domaine est défini et récupération de sa couleur
            const domainColor = getDomainColor(d.agence, d.domaine, data);
            // Carré de couleur pour le domaine avec taille fixe
            d3.select(this).append("div")
                .style("width", "15px")    // Taille fixe du carré
                .style("height", "15px")   // Taille fixe du carré
                .style("background-color", domainColor)
                .style("margin-right", "10px")
                .style("flex-shrink", "0"); // Empéche le carré de se rétrécir
    
            // Abréviation du domaine
            // const abbrDomaine = d.domaine.split(' ')
            //     .map(word => word.slice(0, 3).toUpperCase()) 
            //     .join(' ');
            const abbrDomaine = creerAbbreviation(d.domaine);
    
            // Texte pour le domaine
            d3.select(this).append("div")
                .style("font-size", "11px")
                .style("flex-grow", "1") 
                .style("text-align", "left") 
                .text(
                    abbrDomaine + " : CA " + formatMontant(d.montant1) + "€ - Dép " + formatMontant(d.montant2) + "€"
                );
        });
    });
});
}


// Liste des mots é exclure (déterminants, prépositions, etc.)
const motsExclus = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'd', 'l', 'au', 'aux', 'et', 'en', 'sur', 'sous', 'dans', 'avec', 'pour', 'par', 'à', 'chez'];
// Fonction pour récupérer les trois premieres lettres d'un mot
function creerAbbreviation(domaine) {
  // Diviser le domaine en mots et exclure les mots non pertinents
  return domaine
      .split(/\s+|'/) // Diviser par espace ou apostrophe
      .filter(word => word && !motsExclus.includes(word.toLowerCase())) // Exclure les mots inutiles
      .map(word => word.slice(0, 3).toUpperCase()) // Prendre les 3 premiéres lettres en majuscules
      .join(' '); // Joindre les résultats
}

// Fonction pour formater les montants avec les suffixes appropriés
function formatMontant(value) {
  if (value >= 1e9) {
      return d3.format(".1f")(value / 1e9) + " B"; // Milliards
  } else if (value >= 1e6) {
      return d3.format(".1f")(value / 1e6) + " M"; // Millions
  } else if (value >= 1e3) {
      return d3.format(".1f")(value / 1e3) + " k"; // Milliers
  } else {
      return d3.format(".1f")(value); // Valeurs normales
  }
}

// Fonction pour gérer les couleurs par agence et domaine
function getDomainColor(agence, domaine, data) {
  const matchingItem = data.find(item => item.agence === agence && item.domaine === domaine);
  return matchingItem && matchingItem.color ? matchingItem.color : "#ccc";
}


// Fonction pour créer un graphique de type ligne 
function createLineChart(dataReconstruct, selectedYear) {
  // Clear previous chart (to avoid duplicates)
  d3.select('svg').selectAll("*").remove();
  d3.select("#chart").selectAll(".selected-year-label").remove();
  
  
  // Calculate the number of months in the data
  let data = calculateMonthlyData(dataReconstruct);
  const numberOfMonths = data.length;
  console.log(data);

  // Adjust margins dynamically based on the number of months
  const margin = {
      top: 20,
      right: numberOfMonths > 11 ? 150 : 150, // Reduce margin if more than 10 months
      bottom: 40,
      left: 60
  };

  // Chart dimensions
  const chartWidth = Math.min(700, window.innerWidth - margin.left - margin.right);
  const chartHeight = 500 - margin.top - margin.bottom;

  // Parse date
  const parseDate = d3.timeParse("%Y-%m");
  data.forEach(d => {
    d.date = parseDate(d.date);
  });



// Create SVG container
const svg = d3
  .select('svg')
  .attr('width', chartWidth + margin.left + margin.right)
  .attr('height', chartHeight + margin.top + margin.bottom)
  .attr("viewBox", `0 0 ${chartWidth + margin.left + margin.right} ${chartHeight + margin.top + margin.bottom}`)
  .attr("style", "max-width: 100%; height: auto;")
  .append('g')
  .attr('transform', `translate(${margin.left},${margin.top})`);

// X scale
// let xScale = d3
//   .scaleTime()
//   .domain(d3.extent(data, d => d.date))
//   .range([0, chartWidth]);

// Les mois de l'année sélectionnée
  const generateYearMonths = (year) => {
    const months = [];
    for (let i = 0; i < 12; i++) {
      months.push(new Date(year, i, 1)); // On génère le 1er jour de chaque mois
    }
    return months;
  };

 // Generate months for a period
 const generateYearMonthsPeriode = (startDateStr, endDateStr) => {
      const months = [];
      const startDate = new Date(startDateStr.split('/').reverse().join('/'));
      const endDate = new Date(endDateStr.split('/').reverse().join('/'));
      let currentDate = new Date(startDate);
      while (currentDate <= endDate) {
          months.push(new Date(currentDate));
          currentDate.setMonth(currentDate.getMonth() + 1);
      }
      return months;
  };

  // Generate fullYearMonths based on selectedYear
  let fullYearMonths;
  let xScale;
  if (selectedYear.length > 5) {
      const [startDate, endDate] = selectedYear.split(' - ').map(date => date.trim());
      fullYearMonths = generateYearMonthsPeriode(startDate, endDate);
  } else {
      fullYearMonths = generateYearMonths(selectedYear);
  }


if (selectedYear.length > 5) {
  const minDate = d3.min(data, d => d.date);

  const maxDate = d3.max(data, d => d.date);

    xScale = d3.scaleTime()
    .domain([minDate, maxDate])  // Du début à la fin
    // .domain([fullYearMonths[0], fullYearMonths[fullYearMonths.length - 1]])
    .range([0, chartWidth]);
} else {
  xScale = d3.scaleTime()
  // Utiliser le premier et le dernier mois de l'année sélectionnée
  .domain([fullYearMonths[0], fullYearMonths[fullYearMonths.length - 1]])
  .range([0, chartWidth]);
}
// Extract field keys
const fieldKeys = Object.keys(data[0]).filter(key => key !== 'date');
// Y scale
const yScale = d3
  .scaleLinear()
  .domain([0, d3.max(data, d => Math.max(...fieldKeys.map(key => d[key])))]).nice()
  .range([chartHeight, 0]);

// Create a color palette by agency
const agencies = [...new Set(fieldKeys.map(key => key.split(" : ")[1]))]; // Extract unique agency names
const colorPalette = d3.scaleOrdinal(d3.schemeTableau10).domain(agencies);


// Prepare line data
const lineData = fieldKeys.map((field) => ({
  key: field,
  agency: field.split(" : ")[1], // Extract the agency name
  type: field.split(" : ")[0], // Extract the type ("CA" or "Dépenses")
  color: colorPalette(field.split(" : ")[1]), // Assign color by agency
  data: data.map(d => ({ date: d.date, value: d[field] })),
}));

// Line generator
const lineGenerator = d3
  .line()
  .curve(d3.curveCatmullRom)
  .x(d => xScale(d.date))
  .y(d => yScale(d.value));

// Format currency (€)
const formatCurrency = d3.format(".2f");

// Store visibility state of each curve
let visibilityState = {};

// Tooltip creation
const tooltip = d3.select('body')
  .append('div')
  .style('position', 'absolute')
  .style('background', '#fff')
  .style('border', '1px solid #ccc')
  .style('padding', '8px')
  .style('border-radius', '4px')
  .style('box-shadow', '0px 2px 4px rgba(0, 0, 0, 0.1)')
  .style('visibility', 'hidden')
  .style('font-size', '12px');

// Drag behavior for labels
const drag = d3.drag()
  .on("drag", function (event, d) {
    const draggedX = event.x; // Position relative to SVG's origin
    const draggedY = event.y;

    // Update label position
    d3.select(this)
      .attr("x", draggedX)
      .attr("y", draggedY);
  });

// Area generator for the "gap" between CA and Dépenses
const areaGenerator = d3
  .area()
  .curve(d3.curveCatmullRom)
  .x(d => xScale(d.date))
  .y0(d => yScale(d.ca)) // Start at CA
  .y1(d => yScale(d.depenses)); // End at Dépenses


// Add lines and labels
lineData.forEach(d => {
  // To determine if the line is for "CA" or "Dépenses"
  const isCA = d.type === "CA";
  const isDepenses = d.type === "Dépenses";

  // Style specific for "Dépenses" (dashed line)
  const strokeDasharray = isDepenses ? "4 4" : "none";

  // Draw line
  const line = svg
    .append('path')
    .datum(d.data)
    .attr('class', 'line')
    .attr('d', lineGenerator)
    .attr('stroke', d.color) 
    .attr('fill', 'none')
    .attr('stroke-width', 2)
    .attr('stroke-dasharray', strokeDasharray) 
    .style('visibility', 'visible'); 
    // Show tooltip on mouseover
    svg.selectAll('.line')
      .data(lineData) // Associe chaque ligne à ses données correspondantes
      .on('mouseover', function (event, d) {
        // Le point le plus proche sur la ligne
        const [mouseX] = d3.pointer(event, this); // Récupère la position X de la souris relative à l'élément
        const closestPoint = d.data.reduce((prev, curr) => {
          const currDistance = Math.abs(xScale(curr.date) - mouseX);
          const prevDistance = Math.abs(xScale(prev.date) - mouseX);
          return currDistance < prevDistance ? curr : prev;
        });

      // Affichage de tooltip
      tooltip.style('visibility', 'visible')
        .style('top', `${event.pageY - 40}px`)
        .style('left', `${event.pageX + 10}px`)
        .html(`
          <strong>${d.key}</strong><br>
          Date: ${d3.timeFormat("%b %Y")(closestPoint.date)}<br>
          Montant: €${formatCurrency(closestPoint.value)}
        `);

     
      svg.selectAll('.line')
        .style('opacity', function () {
          return this === event.target ? 1 : 0.4; // Mise en évidence la ligne survolée
        });
    })
    .on('mousemove', function (event) {
      // Meise à jour dynamiquement la position du tooltip
      tooltip.style('top', `${event.pageY - 40}px`)
        .style('left', `${event.pageX + 10}px`);
    })
    .on('mouseout', function () {
      // Cache le tooltip
      tooltip.style('visibility', 'hidden');

      // Réinitialisation de l'opacité de toutes les lignes
      svg.selectAll('.line')
        .style('opacity', 1);
    });


    // Initialize visibility state
    visibilityState[d.key] = true;

    // Get the last point of the curve
    const lastPoint = d.data[d.data.length - 1];
    const xPosition = xScale(lastPoint.date);
    const yPosition = yScale(lastPoint.value);

    // Adding a point if there is only one data point
    if (d.data.length === 1) {
          const points = svg.selectAll('.point')
            svg.append('circle')
            .attr('class', 'point')
            .attr('cx', xPosition)
            .attr('cy', yPosition)
            .data(d.data)
            .attr('class', d => `point-${d.key}`)
            .attr('r', 5) // Rayon du point
            .attr('fill', d.color) 
            .attr('stroke', d.color) 
            .attr('stroke-width', 2)
            .attr('stroke-dasharray', strokeDasharray) 
            .attr('stroke-width', 4.5)
            .style('visibility', 'visible');
            
          svg.selectAll('.label')
              .data(d.data)
              .enter()
              .append('text')
              .text(`${d.key} (${formatMontant(formatCurrency(lastPoint.value))}€)`)
              .attr('x', xPosition + 5)
              .attr('y', yPosition - 5)
              .attr('text-anchor', 'start')
              .style('font-family', 'sans-serif')
              .style('font-size', '12px')
              .style('fill', d.color)
              .style('font-weight', 'bold')
              .call(drag)
              .on('click', function(event, d) {
                  // Basculer l'état de visibilité pour la ligne et les points
                  const isVisible = visibilityState[d.key];
                  visibilityState[d.key] = !isVisible;
                  
                  // Met à jour la visibilité de la ligne
                  line.style('visibility', visibilityState[d.key] ? 'visible' : 'hidden');

                  // Met à jour la visibilité des points associés (y compris les points entillés)
                  svg.selectAll(`.point-${d.key}`).style('visibility', visibilityState[d.key] ? 'visible' : 'hidden');

                  // Basculer l'effet de strikethrough sur le libellé
                  const labelText = d3.select(this);
                  labelText.style('text-decoration', visibilityState[d.key] ? 'none' : 'line-through');
          });
    }else {
         // Add the label
      svg.append('text')
        .text(`${d.key} (${formatMontant(formatCurrency(lastPoint.value))}€)`)
        .attr('x', xPosition + 5)
        .attr('y', yPosition - 5)
        .attr('text-anchor', 'start')
        .style('font-family', 'sans-serif')
        .style('font-size', '12px')
        .style('fill', d.color)
        .style('font-weight', 'bold')
        .call(drag)
        .on('click', function () {
          // Bascule de l'état de visibilité pour la ligne et le point
          const isVisible = visibilityState[d.key];
          visibilityState[d.key] = !isVisible;
       
          // Met à jour la visibilité de la ligne et du point
          line.style('visibility', visibilityState[d.key] ? 'visible' : 'hidden');

          // Basculer l'effet de strikethrough sur le libellé
          const labelText = d3.select(this);
          labelText.style('text-decoration', visibilityState[d.key] ? 'none' : 'line-through');
      });
    }
  });

 // Add areas for each agency
// Add areas for each agency
agencies.forEach(agency => {
  const caData = lineData.find(d => d.type === "CA" && d.agency === agency);
  const depensesData = lineData.find(d => d.type === "Dépenses" && d.agency === agency);

  if (caData && depensesData) {
    const combinedData = caData.data.map((d, i) => ({
      date: d.date,
      ca: d.value,
      depenses: depensesData.data[i]?.value || 0,
    }));

    const area = svg.append("path")
      .datum(combinedData)
      .attr("class", "area")
      .attr("d", areaGenerator)
      .attr("fill", caData.color) 
      .attr("fill-opacity", 0.1) // Semi-transparent fill for the areas
      .style("cursor", "pointer");

    // Add tooltip and hover effect
    area.on("mouseover", function () {
        // Highlight the area
        d3.select(this)
          .attr("fill-opacity", 0.3); 

        // Show tooltip
        tooltip.style("visibility", "visible");
      })
      .on("mousemove", function (event, d) {
        // Get mouse position relative to the chart
        const [mouseX] = d3.pointer(event, this);
        const hoveredDate = xScale.invert(mouseX); // Convert mouseX to a date

        // Find the closest data point
        const closestPoint = d.reduce((prev, curr) => {
          const prevDistance = Math.abs(prev.date - hoveredDate);
          const currDistance = Math.abs(curr.date - hoveredDate);
          return currDistance < prevDistance ? curr : prev;
        });

        // Update tooltip content
        tooltip.style("top", `${event.pageY - 40}px`)
          .style("left", `${event.pageX + 10}px`)
          .html(`
          <strong>${agency}</strong><br>
          Date: ${d3.timeFormat("%b %Y")(closestPoint.date)}<br>
          CA: ${formatMontant(formatCurrency(closestPoint.ca))} €<br>
          Dépenses: ${formatMontant(formatCurrency(closestPoint.depenses))} €<br>
          Résultat: ${formatMontant((closestPoint.ca - closestPoint.depenses).toFixed(2))}<br>
          Résultat en %: ${((closestPoint.ca - closestPoint.depenses) / closestPoint.ca * 100).toFixed(2)}%
        `);
      })
      .on("mouseout", function () {
        // Reset the area opacity
        d3.select(this)
          .attr("fill-opacity", 0.1); // Reset to default opacity

        // Hide tooltip
        tooltip.style("visibility", "hidden");
      });
  }
});

// Adding X axis
// const xAxis = d3.axisBottom(xScale).tickFormat(d3.timeFormat("%b %Y"));
// svg.append('g').attr('transform', `translate(0,${chartHeight})`).call(xAxis);


  let xAxis = null;
  // if (selectedYear.length > 5) {
  //     // Calculer l'intervalle de mois en fonction de l'espace disponible
  //     const tickSpacing = chartWidth / fullYearMonths.length; // Largeur de chaque tick (mois)
  //     const monthStep = tickSpacing > 60 ? 1 : tickSpacing > 30 ? 2 : 3; 


  //     // Les valeurs de ticks pour l'axe X (en sautant certains mois)
  //     const tickValues = fullYearMonths.filter((month, index) => index % monthStep === 0);

  //     // Créer l'axe X
  //   xAxis = d3.axisBottom(xScale)
  //     .tickFormat(d => d3.timeFormat("%b %Y")(d)) // Format du mois et de l'année (janv 2025)
  //     .tickValues(tickValues);
  // } else {
  //   // Adding X axis
  //     xAxis = d3.axisBottom(xScale)
  //     .tickFormat(d => d3.timeFormat("%b %Y")(d)) // Format "janv 2025"
  //     .tickValues(fullYearMonths); // Affichage de tous les mois explicitement
  // }

  if (selectedYear.length > 5) {
    // Split selectedYear pour obtenir les dates de début et de fin
    const [startDateStr, endDateStr] = selectedYear.split(' - ');

    // Convertir les dates en objets Date
    const startDate = new Date(startDateStr.split('/').reverse().join('/'));
    const endDate = new Date(endDateStr.split('/').reverse().join('/'));

    // Calculer la différence en mois
    const monthDifference = (endDate.getFullYear() - startDate.getFullYear()) * 12 + (endDate.getMonth() - startDate.getMonth());

    // Vérifier si l'intervalle est supérieur à 14 mois
    if (monthDifference > 12) {
        // Calculer l'intervalle de mois en fonction de l'espace disponible
        const tickSpacing = chartWidth / fullYearMonths.length; // Largeur de chaque tick (mois)
        // const monthStep = tickSpacing > 60 ? 1 : tickSpacing > 30 ? 2 : 3;
        const monthStep = tickSpacing > 60 
          ? 1 
          : tickSpacing > 30 
          ? 2 
          : tickSpacing > 20 
          ? 3 
          : tickSpacing > 10 
          ? 4 
          : 5;

        // Les valeurs de ticks pour l'axe X (en sautant certains mois)
        const tickValues = fullYearMonths.filter((month, index) => index % monthStep === 0);

        // Créer l'axe X
        xAxis = d3.axisBottom(xScale)
            .tickFormat(d => d3.timeFormat("%b %Y")(d)) // Format du mois et de l'année (janv 2025)
            .tickValues(tickValues);
    
    } else {
        // Ajouter l'axe X avec tous les mois
        xAxis = d3.axisBottom(xScale)
            .tickFormat(d => d3.timeFormat("%b %Y")(d)) // Format "janv 2025"
            .tickValues(fullYearMonths); // Affichage de tous les mois explicitement
    }
  } else { 
     // Ajouter l'axe X avec tous les mois
     xAxis = d3.axisBottom(xScale)
     .tickFormat(d => d3.timeFormat("%b %Y")(d)) // Format "janv 2025"
     .tickValues(fullYearMonths); // Affichage de tous les mois explicitement
  }

    svg.append('g')
      .attr('transform', `translate(0,${chartHeight})`)
      .call(xAxis);


    svg.append('g')
      .attr('transform', `translate(0,${chartHeight})`)
      .call(xAxis);

    // Adding Y axis
    const yAxis = d3.axisLeft(yScale);
    svg.append('g').call(yAxis);

    const fontSize = selectedYear.length > 5 ? "14px" : "20px"; // Si la longueur est > 5, font-size = 8px, sinon 20px
    const dateX = selectedYear.length > 5 ? "120" : "60";
    let displayText = selectedYear;
      if (selectedYear.length > 5) {
          // Split à l'endroit du tiret pour retourner à la ligne
          const parts = selectedYear.split(' - ');
          displayText = ' Période : ' + parts[0] + " - "  + parts[1];  
      }

    // Adding label for the selected year on the Y-axis
    svg.append("text")
      .attr("x", chartWidth - dateX) 
      .attr("y", chartHeight + 40) 
      .attr("class", "selected-year-label")
      .style("font-size", fontSize)
      .style("font-weight", "600")
      .style("font-family", "'Poppins', sans-serif")
      .style("fill", "#0056b3")
      .style("text-anchor", "middle")
      .text('📅' + displayText);

    createLegend(lineData);
}



function calculateMonthlyData(data) {
  // Parse dates
  const parseDate = d3.timeParse("%Y-%m");
  const formatYear = d3.timeFormat("%Y");
  const formatMonth = d3.timeFormat("%Y-%m");

  // Regrouper les données par clé, année et mois
  const groupedData = {};
  data.forEach(entry => {
    const date = parseDate(entry.date);
    const year = formatYear(date);
    const month = formatMonth(date);

    Object.keys(entry).forEach(key => {
      if (key !== "date") {
        // Initialiser les objets si nécessaires
        if (!groupedData[key]) groupedData[key] = {};
        if (!groupedData[key][year]) groupedData[key][year] = {};
        if (!groupedData[key][year][month]) groupedData[key][year][month] = 0;

        // Ajouter les valeurs pour chaque clé, année et mois
        groupedData[key][year][month] += entry[key];
      }
    });
  });

  // Calcul des cumuls par mois pour chaque clé
  const cumulativeSums = {};
    Object.keys(groupedData).forEach(key => {
      cumulativeSums[key] = {};
      let cumulativeSum = 0;
      
      // Traiter chaque année et mois
      Object.keys(groupedData[key]).forEach(year => {
        cumulativeSums[key][year] = {};
        const months = Object.keys(groupedData[key][year]).sort(); // Trier les mois

        months.forEach(month => {
          cumulativeSum += groupedData[key][year][month];
          cumulativeSums[key][year][month] = cumulativeSum; // Cumul par mois, sur plusieurs années
        });
      });
    });

  // Reconstruire les données avec la même structure, avec les cumuls
  const result = [];
  const allMonths = [...new Set(data.map(d => d.date))].sort(); // Tous les mois uniques triés

  allMonths.forEach(month => {
    const entry = { date: month };
    const date = parseDate(month);
    const year = formatYear(date);

    Object.keys(cumulativeSums).forEach(key => {
      entry[key] = cumulativeSums[key][year]?.[month] || 0; // Valeur cumulative ou 0 si pas trouvé
    });

    result.push(entry);
  });

  return result;
}


// Fonction de légende
function createLegend(lineData) {
const legendContainer = d3.select("#legend-container");
const visibilityState = {}; // Suivi de la visibilité

legendContainer.html(""); // Clear previous legend

// On regroupe les données pour chaque agence
const agencies = {};

lineData.forEach(d => {
  const agencyName = d.key.split(" : ")[1]; // Extrait le nom de l'agence
  visibilityState[d.key] = true; // Initialement visible

  if (!agencies[agencyName]) {
    agencies[agencyName] = {
      caColor: 0,
      depensesColor: 0,
      caValue: 0,
      depensesValue: 0,
    };
  }

  if (d.key.includes("CA")) {
    agencies[agencyName].caValue = d.data[d.data.length - 1].value;
    agencies[agencyName].caColor = d.color;
  } else if (d.key.includes("Dépenses")) {
    agencies[agencyName].depensesValue = d.data[d.data.length - 1].value;
    agencies[agencyName].depensesColor = d.color;
  }
});

Object.keys(agencies).forEach(agencyName => {
  const entry = legendContainer.append("div")
    .attr("class", "agency-legend")
    .style("margin", "10px 0")
    .style("padding", "10px")
    .style("width", "90%")
    .style("border-radius", "5px")
    .style("background-color", "#f9f9f9")
    .style("display", "flex")
    .style("flex-direction", "column")
    .style("align-items", "left");

  entry.append("span")
    .text(agencyName)
    .attr("class", "agency-title")
    .style("font-weight", "bold")
    .style("font-size", "14px")
    .style("margin-bottom", "8px")
    .style("text-align", "center");

  const caSection = entry.append("div")
    .style("display", "flex")
    .style("align-items", "left")
    .style("margin-bottom", "5px");

  caSection.append("span")
    .style("width", "15px")
    .style("height", "15px")
    .style("background-color", agencies[agencyName].caColor) // Couleur pleine pour "CA"
    .style("display", "inline-block")
    .style("margin-right", "10px");

  caSection.append("span")
    .text(`CA : ${formatMontant(agencies[agencyName].caValue)}€`)
    .style("font-size", "14px");

  caSection.on("click", function() {
    const key = `CA : ${agencyName}`;
    visibilityState[key] = !visibilityState[key];

    const line = d3.selectAll('.line').filter(function() {
      return d3.select(this).attr('data-key') === key;
    });

    // if (visibilityState[key]) {
    //   d3.select(this).style("text-decoration", "none");
    //   line.style("visibility", "visible");
    // } else {
    //   d3.select(this).style("text-decoration", "line-through");
    //   line.style("visibility", "hidden");
    // }
  });

  const depensesSection = entry.append("div")
    .style("display", "flex")
    .style("align-items", "left");

  depensesSection.append("span")
    .style("width", "15px")
    .style("height", "15px")
    .style("background-image", `repeating-linear-gradient(
      45deg,
      ${agencies[agencyName].depensesColor},
      ${agencies[agencyName].depensesColor} 5px,
      transparent 5px,
      transparent 10px
    )`) // Hachures pour "Dépenses"
    .style("display", "inline-block")
    .style("margin-right", "10px");

  depensesSection.append("span")
    .text(`Dépenses : ${formatMontant(agencies[agencyName].depensesValue)}€`)
    .style("font-size", "14px");

  depensesSection.on("click", function() {
    const key = `Dépenses : ${agencyName}`;
    visibilityState[key] = !visibilityState[key];

    const line = d3.selectAll('.line').filter(function() {
      return d3.select(this).attr('data-key') === key;
    });

    // if (visibilityState[key]) {
    //   d3.select(this).style("text-decoration", "none");
    //   line.style("visibility", "visible");
    // } else {
    //   d3.select(this).style("text-decoration", "line-through");
    //   line.style("visibility", "hidden");
    // }
  });
});
}

function last(array) {
return array[array.length - 1];
}


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

function displayYearSelection(chartType) {

  // const dateDebutInput = document.getElementById('date-debut');
  // const dateFinInput = document.getElementById('date-fin');
  // const calendarDebut = document.getElementById('calendar-debut');
  // const calendarFin = document.getElementById('calendar-fin');
  
  // Création de l'élément <select>
  const selectElement = document.createElement('select');
  selectElement.id = 'yearSelector';
  selectElement.classList.add('styled-select');

  // Récupération de l'année en cours
  const currentYear = new Date().getFullYear();

  // Création des années de l'année en cours jusqu'à 10 ans en arrière
  // const years = Array.from({ length: 35 }, (_, i) => currentYear - i);
  const years = Array.from({ length: 35 }, (_, i) => currentYear - i).filter(year => year >= 2015);
  
  // Création de l'option "En période"
  const periodOption = document.createElement('option');
  periodOption.value = 'En période';
  periodOption.textContent = 'En période';
  periodOption.style.display = 'none'; // Option cachée
  selectElement.appendChild(periodOption);

  // Création des options pour chaque année
  years.forEach(year => {
    const option = document.createElement('option');
    option.value = year;
    option.textContent = year;
    
    selectElement.appendChild(option);
  });

  // Ajout de l'élément <select> 
  const container = document.getElementById('yearSelectionContainer'); 
  container.innerHTML = ''; // On vide la div avant d'ajouter les éléments
  container.appendChild(selectElement);

  // Changement de la couleur de l'élément sélectionné
  selectElement.addEventListener('change', function() {
      const selectedYear = selectElement.value;
      // On applique une couleur spécifique à l'élément sélectionné
      const selectedOption = selectElement.options[selectElement.selectedIndex];
      selectedOption.style.color = '#0056b3';  
  });

  const icon = document.createElement('i');
  icon.classList.add('fas', 'fa-calendar-alt');
  icon.style.backgroundColor = '#0056b3';
  icon.style.opacity = '0.3'; 
  icon.title = "Résultat par année civile";
  container.appendChild(icon);

}




function getSelectedYear(selectedYear, chartType) {
// Check if selectedYear is a periode
  if (selectedYear.length > 5) {
    // Split à l'endroit du tiret et retourner les valeurs correctement formatées
    const parts = selectedYear.split(' - ');
    const formattedStartDate = convertirDate(parts[0]); 
    const formattedEndDate = convertirDate(parts[1]);  

    // Envoyer les dates au backend
    sendDateRangeToBackEnd(formattedStartDate, formattedEndDate, chartType, selectedYear);
  } else {
      // Calcul de la date de début et de fin pour une année simple
      const startDate = new Date(selectedYear, 0, 1); // Le premier janvier de l'année sélectionnée
      const today = new Date();
      let endDate;
      if (parseInt(selectedYear) === today.getFullYear()) {
          // Si l'année sélectionnée est l'année en cours, la date de fin est aujourd'hui
          endDate = today;
      } else {
          // Sinon, c'est le 31 décembre de l'année sélectionnée
          endDate = new Date(selectedYear, 11, 31);
      }

      // Formater les dates
      const formattedStartDate = formatDate(startDate);
      const formattedEndDate = formatDate(endDate);

      // Envoyer les dates au backend
      sendDateRangeToBackEnd(formattedStartDate, formattedEndDate, chartType, selectedYear);
  }
}

function convertirDate(dateStr) {
  // Séparer le jour, le mois et l'année
  const [jour, mois, annee] = dateStr.split('/');

  // Formater la date au format souhaité (année-mois-jour)
  return `${jour}-${mois}-${annee}`;
}

// Fonction de formatage de la date
function formatDate(date) {
const day = ("0" + date.getDate()).slice(-2); // Ajouter un 0 devant les jours < 10
const month = ("0" + (date.getMonth() + 1)).slice(-2); // Ajouter un 0 devant les mois < 10
const year = date.getFullYear();

return `${day}-${month}-${year}`;
}


// Dispaly and update total ca and depenses + DOM. 
// function updateDisplay(data) {
//   let totalMontant1Global = 0; // Total CA
//   let totalMontant2Global = 0; // Total Dépenses

//   // Regroupement des données par agence
//   const groupedData = data.reduce((acc, item) => {
//       const agence = item.agence || "Non attribué"; // Si l'agence est absente, utiliser "Non attribué"
      
//       // Initialisation des données pour l'agence si elle n'existe pas
//       if (!acc[agence]) {
//           acc[agence] = {
//               montant1: 0, // CA
//               montant2: 0, // Dépenses
//               domaines: [] 
//           };
//       }

//       // Convertion montant1 et montant2 en nombres 
//       const montant1 = parseFloat(item.montant1) || 0;
//       const montant2 = parseFloat(item.montant2) || 0;

//       // Cumul des montants
//       acc[agence].montant1 += montant1;
//       acc[agence].montant2 += montant2;

//       // Ajout de l'élément dans le tableau des domaines
//       acc[agence].domaines.push(item);

//       return acc;
//   }, {});


//   // Calcul des totaux globaux
//   Object.values(groupedData).forEach(group => {
//       totalMontant1Global += group.montant1;
//       totalMontant2Global += group.montant2;
//   });


//   // Initialisation de la sortie HTML
//   let output = '';

//   // Données regroupées par agence
//   Object.keys(groupedData).forEach(agence => {
//       const group = groupedData[agence];
//       const montant1 = group.montant1;
//       const montant2 = group.montant2;

//       // Calcul des résultats pour l'agence
//       const agDifference = montant1 - montant2;
//       const percentageAgDifference = montant1 > 0 ? (agDifference / montant1) * 100 : 0;
//       const proportionCA = totalMontant1Global > 0 ? (montant1 / totalMontant1Global) * 100 : 0;
//       const proportionDep = totalMontant2Global > 0 ? (montant2 / totalMontant2Global) * 100 : 0;
      
//       // Génération de la ligne pour chaque agence
//       output += '<tr class="liste_total">';
//       output += '<td>';
//       output += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; padding: 10px;">';

//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: flex-start; font-weight: bold;">' + agence + '</span>';
      
//       // Affichage du CA
//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//       output += 'CA : <span style="margin-left: 5px; color: #27ae60;">' + montant1.toLocaleString('fr-FR') + ' € <span style="margin-left: 5px; color: #2c3e50;border-right: 2px solid #d1d1d1;border-bottom: 2px solid #a6a6a6;box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;"> ' + proportionCA.toFixed(2) + '%</span></span>';
//       output += '</span>';
      
//       // Affichage des Dépenses
//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//       output += 'Dépenses : <span style="margin-left: 5px; color: #e74c3c;">' + montant2.toLocaleString('fr-FR') + ' € <span style="margin-left: 5px; color: #2c3e50;border-right: 2px solid #d1d1d1;border-bottom: 2px solid #a6a6a6;box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;"> ' + proportionDep.toFixed(2) + '%</span></span>';
//       output += '</span>';
      
//       // Affichage du Résultat
//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//       output += 'Résultat : <span style="margin-left: 5px; color: ' + (percentageAgDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + percentageAgDifference.toFixed(2) + '%</span>';
//       output += '</span>';

//       output += '</div>';
//       output += '</td>';
//       output += '</tr>';
//   });

//   // Calcul des totaux globaux
//   const totalDifference = totalMontant1Global - totalMontant2Global;
//   const percentageDifference = totalMontant1Global > 0 ? (totalDifference / totalMontant1Global) * 100 : 0;


//   // Ajout de la ligne des totaux généraux
//   output += '<tr class="liste_total">';
//   output += '<td>Total des montants';
//   output += '<div style="display: flex; justify-content: flex-end; gap: 15px;">';

//   output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//   output += 'CA : <span style="color: #27ae60;">' + totalMontant1Global.toLocaleString('fr-FR') + ' €</span>';
//   output += '</span>';

//   output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//   output += 'Dépenses : <span style="color: #e74c3c;">' + totalMontant2Global.toLocaleString('fr-FR') + ' €</span>';
//   output += '</span>';

//   output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//   output += 'Résultat : <span style="color: ' + (totalDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + percentageDifference.toFixed(2) + '%</span>';
//   output += '</span>';

//   output += '</div></td></tr>';

//   // Ajout de la ligne pour afficher la différence totale
//   output += '<tr class="liste_total">';
//   output += '<td>Résultat totale';
//   output += '<div style="float: right; text-align: right;">';
//   output += '<span style="display: inline-block; font-weight: bold; margin-right: 5px; width: 200px; color: ' + (totalDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + totalDifference.toLocaleString('fr-FR') + ' € </span>';
//   output += '</div></td></tr>';

//   // Inject les résultats dans le DOM
//   $('#total-data').html(output); 
// }


// function updateDisplayEvol(data) {
//   let totalMontant1Global = 0; // Total CA
//   let totalMontant2Global = 0; // Total Dépenses

//   // Regroupement des données par agence
//   const groupedData = data.reduce((acc, item) => {
//       // Récupérer uniquement le nom de l'agence
//       for (const key in item) {
//         // On assigne la clé actuelle de l'objet à la variable 'agence'
      
//       const fullString = key || "Non attribué";
//       const agence = fullString.split(" : ")[1]?.trim() || "Non attribué";
//       console.log('agence', agence);
//       }
//       // Initialisation des données pour l'agence si elle n'existe pas
//       if (!acc[agence]) {
//           acc[agence] = {
//               montant1: 0, // CA
//               montant2: 0, // Dépenses
//           };
//       }

//       // Ajouter le montant au bon type (CA ou Dépenses)
//       if (item.type === "CA") {
//           acc[agence].montant1 += parseFloat(item.amount) || 0;
//       } else if (item.type === "Dépenses") {
//           acc[agence].montant2 += parseFloat(item.amount) || 0;
//       }

//       return acc;
//   }, {});

// // Calcul des totaux globaux
// Object.entries(groupedData).forEach(([agence, group]) => {
//     totalMontant1Global += group.montant1;
//     totalMontant2Global += group.montant2;
// });

//   // Initialisation de la sortie HTML
//   let output = '';

//   // Données regroupées par agence
//   Object.keys(groupedData).forEach(agence => {
//       const group = groupedData[agence];
//       const montant1 = group.montant1;
//       const montant2 = group.montant2;

//       // Calcul des résultats pour l'agence
//       const agDifference = montant1 - montant2;
//       const percentageAgDifference = montant1 > 0 ? (agDifference / montant1) * 100 : 0;
//       const proportionCA = totalMontant1Global > 0 ? (montant1 / totalMontant1Global) * 100 : 0;
//       const proportionDep = totalMontant2Global > 0 ? (montant2 / totalMontant2Global) * 100 : 0;
      
//       // Génération de la ligne pour chaque agence
//       output += '<tr class="liste_total">';
//       output += '<td>';
//       output += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; padding: 10px;">';

//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: flex-start; font-weight: bold;">' + agence + '</span>';
      
//       // Affichage du CA
//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//       output += 'CA : <span style="margin-left: 5px; color: #27ae60;">' + montant1.toLocaleString('fr-FR') + ' € <span style="margin-left: 5px; color: #2c3e50;border-right: 2px solid #d1d1d1;border-bottom: 2px solid #a6a6a6;box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;"> ' + proportionCA.toFixed(2) + '%</span></span>';
//       output += '</span>';
      
//       // Affichage des Dépenses
//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//       output += 'Dépenses : <span style="margin-left: 5px; color: #e74c3c;">' + montant2.toLocaleString('fr-FR') + ' € <span style="margin-left: 5px; color: #2c3e50;border-right: 2px solid #d1d1d1;border-bottom: 2px solid #a6a6a6;box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;"> ' + proportionDep.toFixed(2) + '%</span></span>';
//       output += '</span>';
      
//       // Affichage du Résultat
//       output += '<span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//       output += 'Résultat : <span style="margin-left: 5px; color: ' + (percentageAgDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + percentageAgDifference.toFixed(2) + '%</span>';
//       output += '</span>';

//       output += '</div>';
//       output += '</td>';
//       output += '</tr>';
//   });

//   // Calcul des totaux globaux
//   const totalDifference = totalMontant1Global - totalMontant2Global;
//   const percentageDifference = totalMontant1Global > 0 ? (totalDifference / totalMontant1Global) * 100 : 0;


//   // Ajout de la ligne des totaux généraux
//   output += '<tr class="liste_total">';
//   output += '<td>Total des montants';
//   output += '<div style="display: flex; justify-content: flex-end; gap: 15px;">';

//   output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//   output += 'CA : <span style="color: #27ae60;">' + totalMontant1Global.toLocaleString('fr-FR') + ' €</span>';
//   output += '</span>';

//   output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//   output += 'Dépenses : <span style="color: #e74c3c;">' + totalMontant2Global.toLocaleString('fr-FR') + ' €</span>';
//   output += '</span>';

//   output += '<span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">';
//   output += 'Résultat : <span style="color: ' + (totalDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + percentageDifference.toFixed(2) + '%</span>';
//   output += '</span>';

//   output += '</div></td></tr>';

//   // Ajout de la ligne pour afficher la différence totale
//   output += '<tr class="liste_total">';
//   output += '<td>Résultat totale';
//   output += '<div style="float: right; text-align: right;">';
//   output += '<span style="display: inline-block; font-weight: bold; margin-right: 5px; width: 200px; color: ' + (totalDifference >= 0 ? '#27ae60' : '#e74c3c') + ';">' + totalDifference.toLocaleString('fr-FR') + ' € </span>';
//   output += '</div></td></tr>';

//   // Inject les résultats dans le DOM
//   $('#total-data').html(output); 
// }

function formatCurrency(value) {
  return value.toLocaleString('fr-FR') + ' €';
}

function calculatePercentage(part, total) {
  return total > 0 ? (part / total) * 100 : 0;
}

function generateAgencyRow(agence, montant1, montant2, totalMontant1Global, totalMontant2Global) {
  const agDifference = montant1 - montant2;
  const percentageAgDifference = calculatePercentage(agDifference, montant1);
  const proportionCA = calculatePercentage(montant1, totalMontant1Global);
  const proportionDep = calculatePercentage(montant2, totalMontant2Global);

  return `
    <tr class="liste_total">
      <td>
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; padding: 10px;">
          <span style="flex: 1; display: flex; align-items: center; justify-content: flex-start; font-weight: bold;">${agence}</span>
          <span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">
            CA : <span style="margin-left: 5px; color: #27ae60;">${formatCurrency(montant1)} <span style="margin-left: 5px; color: #2c3e50; border-right: 2px solid #d1d1d1; border-bottom: 2px solid #a6a6a6; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;">${proportionCA.toFixed(2)}%</span></span>
          </span>
          <span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">
            Dépenses : <span style="margin-left: 5px; color: #e74c3c;">${formatCurrency(montant2)} <span style="margin-left: 5px; color: #2c3e50; border-right: 2px solid #d1d1d1; border-bottom: 2px solid #a6a6a6; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); background-color: #f9f9f9;">${proportionDep.toFixed(2)}%</span></span>
          </span>
          <span style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">
            Résultat : <span style="margin-left: 5px; color: ${percentageAgDifference >= 0 ? '#27ae60' : '#e74c3c'};">${percentageAgDifference.toFixed(2)}%</span>
          </span>
        </div>
      </td>
    </tr>`;
}

function generateTotalRow(totalMontant1Global, totalMontant2Global, totalDifference) {
  const percentageDifference = calculatePercentage(totalDifference, totalMontant1Global);

  return `
    <tr class="liste_total">
      <td>Total des montants
        <div style="display: flex; justify-content: flex-end; gap: 15px;">
          <span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">
            CA : <span style="color: #27ae60;">${formatCurrency(totalMontant1Global)}</span>
          </span>
          <span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">
            Dépenses : <span style="color: #e74c3c;">${formatCurrency(totalMontant2Global)}</span>
          </span>
          <span style="padding: 6px 12px; background-color: #f4f4f4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); font-weight: bold;">
            Résultat : <span style="color: ${totalDifference >= 0 ? '#27ae60' : '#e74c3c'};">${percentageDifference.toFixed(2)}%</span>
          </span>
        </div>
      </td>
    </tr>`;
}

function generateFinalDifferenceRow(totalDifference) {
  return `
    <tr class="liste_total">
      <td>Résultat totale
        <div style="float: right; text-align: right;">
          <span style="display: inline-block; font-weight: bold; margin-right: 5px; width: 200px; color: ${totalDifference >= 0 ? '#27ae60' : '#e74c3c'};">${formatCurrency(totalDifference)}</span>
        </div>
      </td>
    </tr>`;
}

// Modification de la fonction updateDisplay
function updateDisplay(data) {
  let totalMontant1Global = 0;
  let totalMontant2Global = 0;
  let totalMontant1DomGlobal = 0;
  let totalMontant2DomGlobal = 0;

  // Regroupement des données par agence
  const groupedData = data.reduce((acc, item) => {
    const agence = item.agence || "Non attribué";
    
    
    if (!acc[agence]) {
      acc[agence] = { montant1: 0, montant2: 0, domaines: [] };
    }

    const montant1 = parseFloat(item.montant1) || 0;
    const montant2 = parseFloat(item.montant2) || 0;

    acc[agence].montant1 += montant1;
    acc[agence].montant2 += montant2;
    acc[agence].domaines.push(item);

    return acc;
  }, {});

  const numberOfKeys = Object.keys(groupedData).length;
  let output = '';
  if(numberOfKeys == 1) {
    Object.keys(groupedData).forEach(agence => {
      const group = groupedData[agence];
      // Parcourir les domaines de cette agence
      group.domaines.forEach(domaine => {
        totalMontant1DomGlobal += domaine.montant1;
        totalMontant2DomGlobal += domaine.montant2;
      });

      group.domaines.forEach(domaine => {
       
        console.log('total', domaine.montant1);
          output += generateAgencyRow(domaine.domaine, domaine.montant1, domaine.montant2, totalMontant1DomGlobal, totalMontant2DomGlobal);
      });
  });

    const totalDomlDifference = totalMontant1DomGlobal - totalMontant2DomGlobal;
    output += generateTotalRow(totalMontant1DomGlobal, totalMontant2DomGlobal, totalDomlDifference);
    output += generateFinalDifferenceRow(totalDomlDifference);
  }else {
    // Calcul des totaux globaux
    Object.values(groupedData).forEach(group => {
      totalMontant1Global += group.montant1;
      totalMontant2Global += group.montant2;
    });


    Object.keys(groupedData).forEach(agence => {
      const group = groupedData[agence];
      output += generateAgencyRow(agence, group.montant1, group.montant2, totalMontant1Global, totalMontant2Global);
    });

    const totalDifference = totalMontant1Global - totalMontant2Global;
    output += generateTotalRow(totalMontant1Global, totalMontant2Global, totalDifference);
    output += generateFinalDifferenceRow(totalDifference);
  }

 

  $('#total-data').html(output);
}

function updateDisplayEvol(data) {
  let totalMontant1Global = 0; // Total CA
  let totalMontant2Global = 0; // Total Dépenses
  let totalMontant1DomGlobal = 0;
  let totalMontant2DomGlobal = 0;
  
  // Regroupement des données par agence
  const groupedData = data.reduce((acc, item) => {
    // Extraire toutes les clés de l'objet item, excluant la clé 'date'
    const keys = Object.keys(item).filter(key => key !== "date");

    // Parcourir toutes les clés représentant des agences
    keys.forEach(agenceKey => {
        const fullString = agenceKey || "Non attribué";  // Utiliser la valeur de la clé comme nom d'agence
        const agence = fullString.split(" : ")[1]?.trim() || "Non attribué";
        const type = fullString.split(" : ")[0]?.trim() || "Non attribué";
        // Récupérer la valeur associée à la clé agenceKey
        const value = parseFloat(item[agenceKey]);
       
        // Initialisation des données pour l'agence si elle n'existe pas
        if (!acc[agence]) {
            acc[agence] = {
                montant1: 0, // CA
                montant2: 0, // Dépenses
                domaines: []
            };
        }

        // Ajouter le montant au bon type (CA ou Dépenses)
        if (type === "CA") {
            acc[agence].montant1 += value;
            totalMontant1DomGlobal += value;
            // Ajouter un objet pour montant1 dans domaines
            acc[agence].domaines.push({ date: item.date, montant1: value });
        } else if (type === "Dépenses") {
            acc[agence].montant2 += value;
            totalMontant2DomGlobal += value;
            // Ajouter un objet pour montant2 dans domaines
            acc[agence].domaines.push({ date: item.date, montant2: value });
        }
    });

    return acc;
}, {});
  
  console.log(groupedData);
  console.log("Total CA Global:", totalMontant1Global);
  console.log("Total Dépenses Global:", totalMontant2Global);

  const numberOfKeys = Object.keys(groupedData).length;
  let output = '';
  if(numberOfKeys == 1) {
    Object.keys(groupedData).forEach(agence => {
      const group = groupedData[agence];
      // Parcourir les domaines de cette agence
      group.domaines.forEach(domaine => {
        totalMontant1DomGlobal += domaine.montant1;
        totalMontant2DomGlobal += domaine.montant2;
      });

      group.domaines.forEach(domaine => {
        if (domaine.montant1 === undefined || domaine.montant2 === undefined) {
          return; // Passer à l'itération suivante
      }
        console.log('total', domaine.montant1);
          output += generateAgencyRow(domaine.agence, domaine.montant1, domaine.montant2, totalMontant1DomGlobal, totalMontant2DomGlobal);
      });
  });

    const totalDomlDifference = totalMontant1DomGlobal - totalMontant2DomGlobal;
    output += generateTotalRow(totalMontant1DomGlobal, totalMontant2DomGlobal, totalDomlDifference);
    output += generateFinalDifferenceRow(totalDomlDifference);
  }else {
    // Calcul des totaux globaux
    Object.entries(groupedData).forEach(([agence, group]) => {
        totalMontant1Global += group.montant1;
        totalMontant2Global += group.montant2;
    });

   
    // Générer les lignes de chaque agence
    Object.keys(groupedData).forEach(agence => {
        const group = groupedData[agence];
        output += generateAgencyRow(agence, group.montant1, group.montant2, totalMontant1Global, totalMontant2Global);
    });

    const totalDifference = totalMontant1Global - totalMontant2Global;
    output += generateTotalRow(totalMontant1Global, totalMontant2Global, totalDifference);
    output += generateFinalDifferenceRow(totalDifference);
}

  $('#total-data').html(output);
}






function sendDateRangeToBackEnd(startDate, endDate, chartType, selectedYear) {
let url;

// if (chartType === 'line') {
//   url = '/custom/addoncomm/ajax/financial_month_data.php';
// } else if (chartType === 'pie' || chartType === 'bar') {
//   url = '/custom/addoncomm/ajax/financial_year_data.php';
// }
url = '/custom/addoncomm/ajax/financial_year_data.php';

$.ajax({
  url: url,
  type: 'POST',
  dataType: 'json',
  data: {
    startDate: JSON.stringify(startDate),
    endDate: JSON.stringify(endDate),
  },
  contentType: 'application/x-www-form-urlencoded',
  success: function (response) {
    console.log('Réponse du serveur Dolibarr:', response);
    
    // Charger les données selon le type de graphique
      
      if (chartType === 'pie') {
        // Si les données sont vides ou invalides
        const hasValidData = Array.isArray(response.dataFianance) && response.dataFianance.some(d => d.montant1 > 0);

        if (!hasValidData) {
            renderChartOrEmpty(response.dataFianance, selectedYear);
            return;
        }
        createChart(response.dataFianance, selectedYear);
      } else if (chartType === 'bar') {
        // Si les données sont vides ou invalides
        const hasValidData = Array.isArray(response.dataFianance) && (response.dataFianance.some(d => d.montant1 > 0) || response.dataFianance.some(d => d.montant2 > 0));

        if (!hasValidData) {
            renderChartOrEmpty(response.dataFianance, selectedYear);
            return;
        }
        createInvertedBarChart(response.dataFianance, selectedYear);
      }else if (chartType === 'line') {
        //Si les données sont vides ou invalides
        // const hasValidDataEvol = Array.isArray(response.dataFiananceEvol);
       
        // if (!hasValidDataEvol) {
        //     renderChartOrEmpty(response.dataFiananceEvol, selectedYear);
        //     return;
        // }
        createLineChart(response.dataFiananceEvol, selectedYear);
      } 

      if (response.dataFianance) {
        updateDisplay(response.dataFianance);
      }else if (response.dataFiananceEvol) {
        updateDisplay(response.dataFiananceEvol);
      }
    
  },
  error: function (xhr, status, error) {
    console.error('Erreur lors de l\'envoi des données:', error);
  }
});
}
