
// Fonction pour remplir un select avec des options
function populateSelect(selectId, dataArray, valueField, labelField) {
    const select = $(selectId);
    
    // On filtre des éléments pour qu'ils soient uniques en fonction de valueField
    const uniqueDataArray = dataArray.filter((item, index, self) =>
        index === self.findIndex(e => e[valueField] === item[valueField])
    );
   
    select.empty(); // Pour vider les options existantes
    initializeSelect2();
    
    // Ajout des options à la liste déroulante
    uniqueDataArray.forEach(item => {
        select.append(new Option(item[labelField], item[valueField]));
    });
}

// Fonction pour obtenir les utilisateurs associés à un critère spécifique
function getUsersFromDataArray(selectedId, criteriaField, dataArray) {
    const users = new Set();

    // Parcours des dataArray pour trouver les utilisateurs associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId && item.fk_user) {
            users.add(item.fk_user);
        }
    });

    return Array.from(users); // Le Set en Array
}

// Fonction pour obtenir les utilisateurs associés à un critère spécifique
function getUsersDataArray(selectedId, criteriaField, dataArray) {
    const users = new Set();

    // Parcours des dataArray pour trouver les utilisateurs associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            users.add(item.id);
        }
    });

    return Array.from(users); // Le Set en Array
}

// Fonction pour obtenir les projets associés à un critère spécifique
function getProjectFromDataArray(selectedId, criteriaField, dataArray) {
    const projects = new Set();
	
    // Parcours des dataArray pour trouver les projets associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId && item.id_project) {
            projects.add(item.id_project);
        }
    });

    return Array.from(projects); // Le Set en Array
}

function getProjectDataArray(selectedId, criteriaField, dataArray) {
    const projects = new Set();
	
    // Parcours des dataArray pour trouver les projets associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            projects.add(item.fk_projet);
        }
    });

    return Array.from(projects); // Le Set en Array
}

function getOrderDataArray(selectedId, criteriaField, dataArray) {
    const orders = new Set();
	
    // Parcours des dataArray pour trouver les commandes associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            orders.add(item.order_id);
        }
    });

    return Array.from(orders); // Le Set en Array
}

function getAgencyDataArray(selectedId, criteriaField, dataArray) {
    const agencies = new Set();
	
    // Parcours des dataArray pour trouver les agences associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            agencies.add(item.socid);
        }
    });

    return Array.from(agencies); // Le Set en Array
}

function getPropalDataArray(selectedId, criteriaField, dataArray) {
    const propals = new Set();
	
    // Parcours des dataArray pour trouver les agences associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            propals.add(item.propal_id);
        }
    });

    return Array.from(propals); // Le Set en Array
}

function getDomDataArray(selectedId, criteriaField, dataArray) {
    const doms = new Set();
	
    // Parcours des dataArray pour trouver les agences associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            doms.add(item.domaine);
        }
    });

    return Array.from(doms); // Le Set en Array
}

function getResAntDataArray(selectedId, criteriaField, dataArray) {
    const resAnt = new Set();
	
    // Parcours des dataArray pour trouver les agences associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            resAnt.add(item.socid);
        }
    });

    return Array.from(resAnt); // Le Set en Array
}

function getAbsTypeDataArray(selectedId, criteriaField, dataArray) {
    const absType = new Set();
	
    // Parcours des dataArray pour trouver les agences associés
    dataArray.forEach(item => {
        if (item[criteriaField] == selectedId) {
            absType.add(item.fk_type);
        }
    });

    return Array.from(absType); // Le Set en Array
}
// Fonction pour filtrer les ressourcesAbs comprises entre deux dates qui concernent la période d'absence d'un salarié. 
function filterResourcesByAbsPeriodes(startAbsPeriode, endAbsPeriode, resources) {
    const start = new Date(startAbsPeriode);
    const end = new Date(endAbsPeriode);
  
    // if (resources.periodes && Array.isArray(resources.periodes)) {
        const filtered = resources.filter(resource => {
            return resource.periodes?.some(abs => {
                const absStart = new Date(abs.date_start);
                const absEnd = new Date(abs.date_end);
                return absEnd >= start && absStart <= end;
            });
        });
        return filtered;
    // }
}

// Fonction pour filtrer les ressourcesAbs comprises entre deux dates qui concernent la période d'absence d'un salarié. 
function filterResourcesByAbsDates(startAbsDate, endAbsDate, resources) {
    const start = startAbsDate ? new Date(startAbsDate) : null;
    const end = endAbsDate ? new Date(endAbsDate) : null;
   
    const absencesFiltered = resources.filter(abs => {
        const absStart = new Date(abs.date_start);
        const absEnd = new Date(abs.date_end);

        const overlaps =
            (!start || absEnd >= start) &&
            (!end || absStart <= end);

        return overlaps;
    });
    return absencesFiltered;
}


// Fonction pour filtrer les ressources en fonction des IDs d'utilisateurs
function filterResourcesByUsers(userIds, resources) {
    return resources.filter(resource => userIds.includes(resource.id));
}

function filterResourcesByProjects(projectIds, resources) {
    return resources.filter(resource => {
        const projetsArray = resource.fk_projets ? resource.fk_projets.split(',').map(p => p.trim()) : [];
        return projetsArray.some(projet => projectIds.includes(projet)) || projectIds.includes(resource.fk_projet);
    });
}


function filterResourcesByOrders(orderIds, resources) {
    return resources.filter(resource => resource.idref == 'CO' && orderIds.includes(resource.element_id));
}

function filterResourcesByAgencies(agencyIds, resources) {
    return resources.filter(resource => {
        const agenciesArray = resource.agences ? resource.agences.split(',').map(p => p.trim()) : [];
        return agenciesArray.some(agency => agencyIds.includes(agency)) || agencyIds.includes(resource.agence);
    });
}


function filterResourcesByPropals(propalIds, resources) {
    return resources.filter(resource => resource.idref == 'PR' && propalIds.includes(resource.propalid));
}

function  filterResourcesByDoms(domIds, resources) {
    return resources.filter(resource => {
        const domsArray = resource.domaines ? resource.domaines.split(',').map(d => d.trim()) : [];
        return (resources.includes(resource.domaine)) || domsArray.some(domaine => domIds.includes(domaine));
    });
}

function filterResourcesByResAnts(resAntIds, resources) {
    return resources.filter(resource => resAntIds.includes(resource.antenne));
}

function filterResourcesByAbsType(absTypeIds, resources) {
    return resources.filter(resource => absTypeIds.includes(resource.fk_type));
}

function filterResourcesByAbsPeriodeType(absTypeIds, resources) {
    return resources.filter(resource => 
        Array.isArray(resource.periodes) &&
        resource.periodes.some(periode => absTypeIds.includes(periode.fk_type))
    );
}

//Fonction pour initialiser Select2 pour les filtres
function initializeSelect2() {
    $("#userFilter, #jobFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #skillFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter").select2({
        width: 'resolve', 
        placeholder: function() { return $(this).attr('placeholder'); },
        allowClear: false
    });
}


// Filtrer les périodes d'absences
function setDefaultDateRangeFromResources(resources) {
    // if (!resources || resources.length === 0) return;
    let minDate = null;
    let maxDate = null;

    resources.forEach(res => {
        const start = new Date(res.date_start);
        const end = new Date(res.date_end);

        if (!minDate || start < minDate) minDate = start;
        if (!maxDate || end > maxDate) maxDate = end;
    });
    

    if (minDate) {
        document.getElementById("startDate").value = minDate.toISOString().split('T')[0];
    }
    if (maxDate) {
        document.getElementById("endDate").value = maxDate.toISOString().split('T')[0];
    }
}

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

function filterResourcesByAvailability() {
    // Récupération de toutes les ressources d'origine sans filtre appliqué
    let updatedResources = [...ressources]; 
    let updatedResourcesProj = [...ressourcesProj];
    let updatedResourcesComm = [...ressourcesComm];
    // let updatedResourcesAbs = [...ressourcesAbs];
    // let updatedResourcesProjAbs = [...ressourcesProjAbs];

        
    // Filtres de disponibilité
    if (window.availabilityFree || window.availabilityPartial) {
        updatedResources = ressources.filter(filterCondition);
        updatedResourcesProj = ressourcesProj.filter(filterCondition);
        updatedResourcesComm = ressourcesComm.filter(filterCondition);
        // updatedResourcesAbs = ressourcesAbs.filter(filterCondition);
        // updatedResourcesProjAbs = ressourcesProjAbs.filter(filterCondition);
    }

    // Mise à jour des graphiques 
    updateGanttChartColDev(updatedResources);
    updateGanttChartColProj(updatedResourcesProj);
    updateGanttChartProjCom(updatedResourcesProj);
    updateGanttChartCodeCol(updatedResourcesComm);
}

// Fonction de filtrage selon la disponibilité
function filterCondition(resource) {
    if (!window.availabilityFree && !window.availabilityPartial) return true; // Si aucun filtre activé, afficher tout

    const totalementLibre = typeof resource.element_id === "undefined" || resource.element_id === null;
    const affectePartiel = resource.element_id === resource.fk_projet;

    return (window.availabilityFree && totalementLibre) || (window.availabilityPartial && affectePartiel);
}

// document.addEventListener("DOMContentLoaded", async function () {
//     window.availabilityFree = false; // Disponibilité totale
//     window.availabilityPartial = false; // Affecté partiel

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