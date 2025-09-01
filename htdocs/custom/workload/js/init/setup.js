// function updateLabelState(ganttId) {
//     const selectedLabels = Array.from(document.querySelectorAll('.gformlabel.gselected'));
//         selectedLabels.forEach(selectedLabel => {
//         const labelId = selectedLabel.dataset.id || selectedLabel.id || '';
//         if (labelId && labelId.includes(ganttId)) {
//             console.log('ganttId', ganttId, 'selectedLabel', selectedLabel);
//             $("#userFilter, #jobFilter, #skillFilter, #projectFilter, #orderFilter, #propalFilter, #groupFilter, #agenceFilter, #domFilter, #respProjFilter, #resAntFilter, #absFilter")
//                 .on("select2:select select2:unselect", function () {
//                     // Supprimer gselected du label en cours (celui qui rentre dans la condition)
//                 const newSelected = document.querySelector(`.gformlabel[data-id="${labelId}"], .gformlabel#${labelId}`);
//                     if (newSelected) {
//                         newSelected.classList.remove('gselected');
//                         newSelected.classList.add('gselected');
                    
                    
//                         console.log('Réappliqué à', newSelected);
//                     }
//                 });
//         }
//     });
// }
 function addFixedHeader() {
    let ganttMapping = {
        "gant1": "GanttChartDIV",
        "gant2": "GanttChartDIV2",
        "gant3": "GanttChartDIV3",
        "gant4": "GanttChartDIV4",
        "gant5": "GanttChartDIV5",
        "gant6": "GanttChartDIV6"
    };

    let activeTab = document.querySelector('.tabsElem.tabactive a');
    if (!activeTab) {
        console.warn("Aucun onglet actif trouvé !");
        return;
    }

    let activeTabId = activeTab.id;
    let ganttId = ganttMapping[activeTabId];
    if (!ganttId) {
        console.warn("Aucun Gantt correspondant trouvé pour l'onglet :", activeTabId);
        return;
    }
    
//    updateLabelState(ganttId);

    let ganttContainer = document.getElementById(ganttId);
    let ganttTable = document.getElementById(ganttId + "chartTable");
    let ganttBody = document.getElementById(ganttId + "gchartbody");
    let ganttHead = document.getElementById(ganttId + "gcharthead");

    if (!ganttContainer || !ganttTable || !ganttBody || !ganttHead) {
        console.warn("Élément(s) introuvable(s) !");
        return;
    }
    if (document.getElementById("fixedHeader_" + ganttId)) return;

    let fixedHeader = ganttHead.cloneNode(true);
    fixedHeader.id = "fixedHeader_" + ganttId;
    fixedHeader.style.position = "fixed";
    fixedHeader.style.bottom = "25px";
    fixedHeader.style.width = "41.99%";
    fixedHeader.style.background = "white";
    fixedHeader.style.zIndex = "1000";
    fixedHeader.style.borderBottomRightRadius = "10px";
    fixedHeader.style.borderTopRightRadius = "10px";
    fixedHeader.style.overflowY = "hidden";
    fixedHeader.style.display = "block";
    fixedHeader.style.textAlign = "center";
    fixedHeader.style.float = "right";
    // fixedHeader.style.height = "10" - ganttHead.clientHeight + "px";
    // fixedHeader.style.height = ganttHead.clientHeight + "px";
    // fixedHeader.style.paddingTop = "10px";
    fixedHeader.style.overflowX = "auto";
    document.body.appendChild(fixedHeader);
    

    ganttTable.insertBefore(fixedHeader, ganttTable.firstChild);

    
    // Synchroniser le scrollLeft du ganttHead avec le fixedHeader et verse versa
    function syncScroll() {
        ganttHead.scrollLeft = fixedHeader.scrollLeft;
        fixedHeader.scrollLeft = ganttHead.scrollLeft;
    }

    // Écoute du défilement du ganttHead
    ganttHead.addEventListener("scroll", function () {
        syncScroll();
    });

    // Écoute du défilement du fixedHeader
    fixedHeader.addEventListener("scroll", function () {
        syncScroll();
    });
    
    // Centrer le graphique par rapport à la date d'aujourd'hui
    const lineElement5 = document.getElementById('GanttChartDIV5line1');
    centerScrollOnAllLines5(lineElement5, fixedHeader);
    
    const lineElement4 = document.getElementById('GanttChartDIV4line1');
    centerScrollOnAllLines4(lineElement4, fixedHeader);
    const lineElement3 = document.getElementById('GanttChartDIV3line1');
    centerScrollOnAllLines3(lineElement3, fixedHeader);
    const lineElement2 = document.getElementById('GanttChartDIV2line1');
    centerScrollOnAllLines2(lineElement2, fixedHeader);
    const lineElement1 = document.getElementById('GanttChartDIVline1');
    centerScrollOnAllLines1(lineElement1, fixedHeader);
    const lineElement6 = document.getElementById('GanttChartDIV6line1');
    centerScrollOnAllLines6(lineElement6, fixedHeader);

    document.getElementById('centerTodayContainer').addEventListener('click', function () {
        // Centrer le graphique par rapport à la date d'aujourd'hui
        const lineElement5 = document.getElementById('GanttChartDIV5line1');
        centerScrollOnAllLines5(lineElement5, fixedHeader);
    
        const lineElement4 = document.getElementById('GanttChartDIV4line1');
        centerScrollOnAllLines4(lineElement4, fixedHeader);
        const lineElement3 = document.getElementById('GanttChartDIV3line1');
        centerScrollOnAllLines3(lineElement3, fixedHeader);
        const lineElement2 = document.getElementById('GanttChartDIV2line1');
        centerScrollOnAllLines2(lineElement2, fixedHeader);
        const lineElement1 = document.getElementById('GanttChartDIVline1');
        centerScrollOnAllLines1(lineElement1, fixedHeader);
        const lineElement6 = document.getElementById('GanttChartDIV6line1');
        centerScrollOnAllLines6(lineElement6, fixedHeader);
    });

    function observeHeaderChanges() {
        let observer = new MutationObserver(() => {
            requestAnimationFrame(() => {
                fixedHeader.replaceWith(ganttHead.cloneNode(true)); 
            });
        });
        observer.observe(ganttHead, { childList: true, subtree: true, attributes: true });
    }
    
    ganttBody.addEventListener("scroll", syncScroll);
    observeHeaderChanges();
    

    // // Suppression d'anciennes lignes pour éviter la duplication
    // document.querySelectorAll(".gantt-holiday-line").forEach(line => line.remove());
    // document.querySelectorAll(".gantt-holiday-cell").forEach(cell => cell.classList.remove("gantt-holiday-cell"));

    let firstRow = ganttHead.querySelectorAll("tr:first-child td");
    let secondRow = ganttHead.querySelectorAll("tr:nth-child(2) td");
    let rows = ganttBody.querySelectorAll("tr");

    let weekRanges = [];
    firstRow.forEach((cell, index) => {
        let match = cell.innerText.match(/(\d{2}\/\d{2}\/\d{4}) - (\d{2}\/\d{2}\/\d{4})/);
        if (match) {
            let startDate = new Date(match[1].split('/').reverse().join('-'));
            let endDate = new Date(match[2].split('/').reverse().join('-'));
            weekRanges.push({ index, startDate, endDate });
        }
    });

    // let columnDates = [];
    window.columnDates = [...document.querySelectorAll(".gantt-column")].map(column => {
        let dateStr = column.getAttribute("data-date"); // colonne avec une date
        return { column, date: new Date(dateStr) };
    });
    window.columnDatesWeek = [...document.querySelectorAll(".gantt-column")].map(column => {
        let dateStrWeek = column.getAttribute("data-date"); // colonne avec une date
        return { column, date: new Date(dateStr) };
    });
    secondRow.forEach((column, colIndex) => {
        let dayNumber = parseInt(column.innerText.trim(), 10);

        if (!isNaN(dayNumber)) {
            let weekIndex = Math.floor(colIndex / 7);
            let foundWeek = weekRanges[weekIndex];

            if (foundWeek) {
                let fullDate = new Date(foundWeek.startDate);
                fullDate.setDate(dayNumber);
                columnDates.push({ colIndex, date: fullDate, column });
            }
        }
    });
    
    // let joursFeries = getJoursFeries(new Date().getFullYear());
    let uniqueYears = new Set(columnDates.map(({ date }) => date.getFullYear()));
    // les jours fériés pour toutes les années trouvées
    let joursFeries = [...uniqueYears].flatMap(year => getJoursFeries(year));

    columnDates.forEach(({ colIndex, date, column }) => {
            dateStr = date.toISOString().split('T')[0];

        if (joursFeries.includes(dateStr)) {
            rows.forEach(row => {
                let cell = row.children[colIndex + 1];
                if (cell) {
                    cell.classList.add("gantt-holiday-cell");
                }
            });

            // Création de la ligne rouge pour le jour férié
            let holidayLine = document.createElement("div");
            holidayLine.classList.add("gantt-holiday-line");

            // Position et largeur basées sur la cellule de la deuxième ligne
            let columnRect = column.getBoundingClientRect();
            let ganttRect = ganttBody.getBoundingClientRect();

            holidayLine.style.left = `${column.offsetLeft}px`;
            holidayLine.style.width = `${column.offsetWidth}px`;
            holidayLine.style.height = `${ganttBody.scrollHeight}px`;
            let nextColumn = secondRow[colIndex + 1];
            if (nextColumn) {
                holidayLine.style.left = `${nextColumn.offsetLeft}px`; // position de la case suivante
                holidayLine.style.width = `${nextColumn.offsetWidth}px`; // largeur de la case
                holidayLine.style.height = `${ganttBody.scrollHeight}px`; // hauteur totale du Gantt
                ganttBody.appendChild(holidayLine);
            }
        }


        // Traitement des jours des week-end
        if (column.classList.contains("gminorheadingwkend")) {
            // Création de la ligne verticale grise
            let weekendLine = document.createElement("div");
            weekendLine.classList.add("gantt-weekend-line");

            // Style de la ligne
            weekendLine.style.position = "absolute";
            weekendLine.style.left = `${column.offsetLeft}px`;
            weekendLine.style.width = `${column.offsetWidth}px`;
            weekendLine.style.height = `${ganttBody.scrollHeight}px`;
            weekendLine.style.backgroundColor = "rgba(136, 136, 136, 0.2)"; 
            weekendLine.style.opacity = "0.2"; 

            // Ajout de la ligne dans le Gantt
            ganttBody.appendChild(weekendLine);
        }
    });

    // Suppression d'anciennes lignes pour éviter la duplication
    document.querySelectorAll(".gantt-holiday-line-week").forEach(line => line.remove());
    document.querySelectorAll(".gantt-holiday-cell-week").forEach(cell => cell.classList.remove("gantt-holiday-cell"));

    let yearMap = [];
    firstRow.forEach((cell, index) => {
        let yearMatch = cell.innerText.match(/^(\d{4})$/); // si c'est une année seule (2024, 2025)
        if (yearMatch) {
            let year = parseInt(yearMatch[1], 10);
            yearMap.push({ index, year });
        }
    });

    // let columnDatesWeek = [];
    secondRow.forEach((column, colIndex) => {
        let dayMonthMatch = column.innerText.match(/^(\d{2})\/(\d{2})$/); // Ex: "24/02"
        if (dayMonthMatch) {
            let day = parseInt(dayMonthMatch[1], 10);
            let month = parseInt(dayMonthMatch[2], 10) - 1; // 0 = Janvier

            // L'année associée epar rapport à la première ligne
            let foundYear = yearMap.find(y => y.index <= colIndex);
            let year = foundYear ? foundYear.year : new Date().getFullYear(); // Si aucune année trouvée, année actuelle

            let fullDate = new Date(year, month, day);
            columnDatesWeek.push({ colIndex, date: fullDate, column });
        }
    });

    let uniqueYearsWeek = new Set(columnDatesWeek.map(({ date }) => date.getFullYear()));
    let joursFeriesWeek = [...uniqueYearsWeek].flatMap(year => getJoursFeries(year));

    columnDatesWeek.forEach(({ colIndex, date, column }) => {
        let weekDays = [];

        // Récupération des 7 jours de la semaine à partir du lundi
        for (let i = 0; i < 7; i++) {
            let fullDate = new Date(date);
            fullDate.setDate(fullDate.getDate() + i);
            weekDays.push(fullDate);
        }

        // Ajout des jours fériés sur la semaine
        weekDays.forEach((fullDate, i) => {
            let dateStr = fullDate.toISOString().split('T')[0];

            if (joursFeriesWeek.includes(dateStr)) {
                let holidayLineWeek = document.createElement("div");
                holidayLineWeek.classList.add("gantt-holiday-line-week");
                holidayLineWeek.style.position = "absolute";

                // Position exacte du jour férié dans la semaine
                let dayPosition = (i / 7) * column.offsetWidth;
                holidayLineWeek.style.left = `${column.offsetLeft + dayPosition}px`;

                holidayLineWeek.style.width = "1px";
                holidayLineWeek.style.height = `${ganttBody.scrollHeight}px`;
                holidayLineWeek.style.backgroundColor = "rgba(255, 0, 0, 0.3)";

                ganttBody.appendChild(holidayLineWeek);
            }
        });

    });


    // Observation de l'ajout de nouveaux éléments dans le DOM
    const observer = new MutationObserver((mutationsList, observer) => {
        if (document.querySelector(".gformlabel")) {
            attachEventListeners();
            observer.disconnect(); // On arrête d'observer une fois que les éléments sont trouvés
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Ajout des événements immédiatement si les éléments existent déjà
    if (document.querySelector(".gformlabel")) {
        attachEventListeners();
    }

    $("#userFilter, #jobFilter, #skillFilter, #projectFilter, #orderFilter, #propalFilter, #groupFilter, #agenceFilter, #domFilter, #respProjFilter, #resAntFilter, #absFilter").on("select2:select select2:unselect", function () {
        attachEventListeners();
    });

}


function getJoursFeries(year) {
    function getEasterDate(y) {
        let f = Math.floor,
            a = y % 19,
            b = f(y / 100),
            c = y % 100,
            d = f(b / 4),
            e = b % 4,
            g = f((8 * b + 13) / 25),
            h = (19 * a + b - d - g + 15) % 30,
            i = f(c / 4),
            k = c % 4,
            l = (32 + 2 * e + 2 * i - h - k) % 7,
            m = f((a + 11 * h + 22 * l) / 451),
            month = f((h + l - 7 * m + 114) / 31),
            day = ((h + l - 7 * m + 114) % 31) + 1;
        return new Date(y, month - 1, day);
    }

    let paques = getEasterDate(year);
    let joursFeries = [
        new Date(year, 0, 1), new Date(year, 4, 1), new Date(year, 4, 8),
        new Date(year, 6, 14), new Date(year, 7, 15), new Date(year, 10, 1),
        new Date(year, 10, 11), new Date(year, 11, 25),
        new Date(paques.getTime() + 1 * 24 * 60 * 60 * 1000),
        new Date(paques.getTime() + 39 * 24 * 60 * 60 * 1000),
        new Date(paques.getTime() + 50 * 24 * 60 * 60 * 1000)
    ];

    return joursFeries.map(date => date.toISOString().split('T')[0]);
}


function attachEventListeners() {
    // Sauvegarde de l'élément 'jour', 'mois' '...sélectionné
    // const currentSelectedText = document.querySelector(".gformlabel.gselected")?.textContent.trim();
    document.querySelectorAll(".gformlabel").forEach(element => {
        element.addEventListener("click", function () {
            // si l'élément est déjà sélectionné
            if (this.classList.contains("gselected")) {
                return; 
            };

            // Ajout de la classe 'gselected' à l'élément cliqué
            this.classList.add("gselected");

            EventBus.dispatch('gantt:update');
        });
    });
}


document.addEventListener("DOMContentLoaded", function () {
    
    //    Observables pour la mise à jour de l'en-tête fixe des dates et du centre graphique en fonction de la date d'aujourd'hui.
    observeUntilReady(['resetDates', 'startDate', 'endDate'], (resetBtn, startInput, endInput) => {
        const triggerScrollAndHeader = () => {
            EventBus.dispatch('gantt5:update');
        };

        resetBtn.addEventListener('click', triggerScrollAndHeader);
        startInput.addEventListener('change', triggerScrollAndHeader);
        endInput.addEventListener('change', triggerScrollAndHeader);
    });


    observeUntilReady(['toggleAvailabilityPartial', 'availabilityIconPartial'], (toggleBtn, icon) => {
        toggleBtn.addEventListener('click', () => {
           EventBus.dispatch('gantt2:update');
        });
    });

    observeUntilReady(['toggleAvailability', 'availabilityIcon'], (toggleBtn, icon) => {
        toggleBtn.addEventListener('click', () => {
            // const isActive = icon.classList.contains('fa-toggle-off');
            EventBus.dispatch('gantt2:update');
        });
    });    
    
    // Gestion du reset des filtres (excepté les dates d'absence)
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
             EventBus.dispatch('gantt:update');
        });
    }


    $(document).ready(function () {
        // Sélection de tous les filtres et mise a jours des jours fériés
        $("#userFilter, #jobFilter, #skillFilter, #projectFilter, #orderFilter, #propalFilter, #groupFilter, #agenceFilter, #domFilter, #respProjFilter, #resAntFilter, #absFilter, #domFilter").on("select2:select select2:unselect", function () {
             EventBus.dispatch('gantt:update');
        });
    });

    let idsToCheck = [
        "GanttChartDIVchartTableh",
        "GanttChartDIV2chartTableh",
        "GanttChartDIV3chartTableh",
        "GanttChartDIV4chartTableh",
        "GanttChartDIV5chartTableh",
        "GanttChartDIV6chartTableh"
    ];

    idsToCheck.forEach(id => {
        let interval = setInterval(() => {
            let el = document.getElementById(id);
            if (el) {
                addFixedHeader(); 
                clearInterval(interval); 
            }
        }, 100);
    });


    document.querySelectorAll(".tabsElem a").forEach(tab => {
        tab.addEventListener("click", function () {
             EventBus.dispatch('gantt:update');
        });
    });
    
});


// Pour assurer l'affichage de la ligne bleu sur la date d'aujourd'hui sur tous les modes de navigation. 
document.addEventListener('DOMContentLoaded', function () {
    const ganttHeader = document.querySelector('.gcharttableh'); 
    const navbar = document.querySelector('#topmenu');

    if (ganttHeader) {
        // Si l'en-tête du Gantt est trouvé
        let navbarHeight = 0;

        if (navbar) {
            // Si la barre de navigation est trouvée, on récupère sa hauteur
            navbarHeight = navbar.offsetHeight;
        } 

        // Application du positionnement fixe à l'en-tête du Gantt
        window.addEventListener('scroll', function () {
            if (window.scrollY > navbarHeight) {
                // Si le défilement dépasse la hauteur de la barre de navigation
                ganttHeader.style.position = 'fixed';
                ganttHeader.style.top = `${navbarHeight}px`;  
                ganttHeader.style.zIndex = '1000';
                ganttHeader.style.backgroundColor = '#fff'; 
            } else {
                // L'en-tête dans son état normal au début d la page
                ganttHeader.style.position = 'relative';
                ganttHeader.style.top = '0';
            }
        });
    } 
});