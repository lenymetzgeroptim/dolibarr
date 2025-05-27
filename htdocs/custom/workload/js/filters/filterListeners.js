function setupFilterListeners(resources, filterData, updateGanttCallback, type) {
    function applyAllFilters(resourcesBase) {
        const selectedJobIds = $("#jobFilter").val() || [];
        const selectedSkillIds = $("#skillFilter").val() || [];
        const selectedAgencyIds = $("#agenceFilter").val() || [];
        const selectedEmployeeIds = $("#userFilter").val() || [];
        const selectedGroupIds = $("#groupFilter").val() || [];
        const selectedRespProjIds = $("#respProjFilter").val() || [];
        const selectedAffaireIds = $("#projectFilter").val() || [];
        const selectedOrderIds = $("#orderFilter").val() || [];
        const selectedPropalIds = $("#propalFilter").val() || [];
        const selectedAbsTypeIds = $("#absFilter").val() || [];
        const selectedResAntFilter = $("#resAntFilter").val() || [];
        const selectedDomFilter = $("#domFilter").val() || [];
        const startAbsDate = ($("#startDate").val() || "").trim();
        const endAbsDate = ($("#endDate").val() || "").trim();
      

        let filtered = [];
        let filteredAbs = [];

        const filters = {
            job: selectedJobIds,
            skill: selectedSkillIds,
            agency: selectedAgencyIds,
            employee: selectedEmployeeIds,
            group: selectedGroupIds,
            resp: selectedRespProjIds,
            affaire: selectedAffaireIds,
            abs: selectedAbsTypeIds,
            resAnt: selectedResAntFilter,
            dom: selectedDomFilter,
            order: selectedOrderIds,
            propal: selectedPropalIds,
        };

        // Vérification des données filtrées
        const hasFilter = keys => keys.some(k => filters[k]?.length > 0);

        // Les séléctions
        const filtersSelected = hasFilter(['job','skill','agency','employee','group','resp','affaire','abs','resAnt','dom']);
        const filtersSelectedLess = hasFilter(['order', 'propal']);
        const isFiltredAbs = filters.abs.length > 0 && !hasFilter(['job','skill','agency','employee','group','resp','affaire','resAnt','dom']);

        // Cas sans filtre actif
        if (!filtersSelected && !filtersSelectedLess) {
            filtered = [...resourcesBase];
        }


        // Les résultats des filtres sont additionnés
        if (selectedJobIds.length > 0) {
            selectedJobIds.forEach(jobId => {
                const users = getUsersFromDataArray(jobId, "job_id", filterData.dataJobs || []);
                filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
            });
        }

        if (selectedSkillIds.length > 0) {
            selectedSkillIds.forEach(skillId => {
                const users = getUsersFromDataArray(skillId, "skillid", filterData.dataSkills || []);
                filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
            });
        }

        if (selectedEmployeeIds.length > 0) {
            selectedEmployeeIds.forEach(employeeId => {
                const users = getUsersDataArray(employeeId, "id", filterData.dataUsers || []);
                filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
            });
        }

        if (selectedGroupIds.length > 0) {
            selectedGroupIds.forEach(groupId => {
                const users = getUsersFromDataArray(groupId, "group_id", filterData.dataGroups || []);
                filtered = filtered.concat(filterResourcesByUsers(users, resourcesBase));
            });
        }

        if (selectedRespProjIds.length > 0) {
            selectedRespProjIds.forEach(resProjId => {
                const projects = getProjectFromDataArray(resProjId, "id", filterData.dataRespProj || []);
                filtered = filtered.concat(filterResourcesByProjects(projects, resourcesBase));
            });
        }

        if (selectedAffaireIds.length > 0) {
            selectedAffaireIds.forEach(projId => {
                const projects = getProjectDataArray(projId, "fk_projet", filterData.dataProjects || []);
                filtered = filtered.concat(filterResourcesByProjects(projects, resourcesBase));
            });
        }

        
        if (type != 'abs' && type != 'projabs') {
            if (selectedOrderIds.length > 0) {
                selectedOrderIds.forEach(orderId => {
                    const orders = getOrderDataArray(orderId, "order_id", filterData.dataOrders || []);
                    filtered = filtered.concat(filterResourcesByOrders(orders, resourcesBase));
                });
            }
        }else if (!filtersSelected){
            filtered = [...resourcesBase];
        }

        if (selectedAgencyIds.length > 0) {
            selectedAgencyIds.forEach(agencyId => {
                const agencies = getAgencyDataArray(agencyId, "socid", filterData.dataAgencies || []);
                filtered = filtered.concat(filterResourcesByAgencies(agencies, resourcesBase));
            });
        }

        if (selectedPropalIds.length > 0) {
            selectedPropalIds.forEach(propalId => {
                const propals = getPropalDataArray(propalId, "propal_id", filterData.dataPropals || []);
                filtered = filtered.concat(filterResourcesByPropals(propals, resourcesBase));
            });
        }

        if (selectedDomFilter.length > 0) {
            selectedDomFilter.forEach(domId => {
                const doms = getDomDataArray(domId, "domaine", filterData.dataProjects || []);
                filtered = filtered.concat(filterResourcesByDoms(doms, resourcesBase));
            });
        }

        if (selectedResAntFilter.length > 0 )  {
            selectedResAntFilter.forEach(resAntId => {
                const resAnts = getResAntDataArray(resAntId, "socid", filterData.dataAgencies || []);
                filtered = filtered.concat(filterResourcesByResAnts(resAnts, resourcesBase));
            });
        }

        
        if ((type === 'abs' || type === 'projabs') && selectedAbsTypeIds.length > 0) {
            selectedAbsTypeIds.forEach(absTypeId => {
                const absType = getAbsTypeDataArray(absTypeId, "fk_type", filterData.dataAbsType || []);
                
                if (type === 'abs') {
                    filtered = filtered.concat(filterResourcesByAbsType(absType, resourcesBase));
                } else if (type === 'projabs') {
                    filtered = filtered.concat(filterResourcesByAbsPeriodeType(absType, resourcesBase));
                }
            });
        }
        
        if (isFiltredAbs && (type !== 'abs' && type !== 'projabs')) {
            filtered = [...resourcesBase];
        }
        // les dates d'absence en interaction avec les ressources filtrées
        const filterContainer = document.getElementById("dateFilterContainer");
        if (type == 'abs' || type == 'projabs') {
           
            if (type == 'abs') {
                if (startAbsDate !== "" && endAbsDate !== "") {
                    if (filtered.some(resource => resource.id && resource.id.trim() !== "")) {
                        filtered = filterResourcesByAbsDates(startAbsDate, endAbsDate, filtered);
                    }
                }

            }
            
            if (type == 'projabs') {
                if (startAbsDate !== "" && endAbsDate !== "") {
                    // if (filtered.some(resource => resource.id && resource.id.trim() !== "")) {
                        filtered = filterResourcesByAbsPeriodes(startAbsDate, endAbsDate, filtered);
                    // }
                }
            }
           
        }
        // Suppression des doublons après addition 
        const seen = new Set();
        filtered = filtered.filter(item => {
            const mainElement = item.element_id || item.element_id_abs || 'null';
            // Clé unique composite : id + idref + element_id|fk_projet
            const key = `${item.id}|${item.idref}|${item.fk_projet}|${mainElement}`;
            if (seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        });

                
        if (filtered.length === 0) {
            return [];
        }

        return filtered;
    }

    function updateFilteredResources() {
        let filteredResources = applyAllFilters(resources);
        // let filteredResources = applyAllFilters(Array.isArray(resources) ? resources : Object.values(resources));
        
        // Gestion de la disponibilité via les icônes
        const iconElementFree = document.getElementById("availabilityIcon");
        const iconElementPartial = document.getElementById("availabilityIconPartial");

        if (iconElementFree?.classList.contains("fa-toggle-on") || iconElementPartial?.classList.contains("fa-toggle-on")) {
            filteredResources = filteredResources.filter(filterCondition);
        }

        updateGanttCallback(filteredResources);  
        
    }

    $("#jobFilter, #skillFilter, #userFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter, #startDate, #endDate")
    .on("change", function () {
        updateFilteredResources();
    });

    
    // Gestion du reset des filtres (excepté les dates d'absence)
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            // Réinitialiser tous les filtres sauf les dates
            $("#jobFilter, #skillFilter, #userFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter")
            .val([]); 
            $('#jobFilter, #skillFilter, #userFilter, #groupFilter, #respProjFilter, #projectFilter, #orderFilter, #agenceFilter, #propalFilter, #absFilter, #resAntFilter, #domFilter').select2();
            updateFilteredResources();
        });
    }
     

    // Gestion du reset des dates
    const filterContainer = document.getElementById("dateFilterContainer");
    // if ($('#gant5').closest('.tabsElem').hasClass('tabactive')) {
    $(document).ready(function() {
        const resetBtn = document.getElementById('resetDates');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                let ressourcesAbs = resources;
                if (ressourcesAbs.some(resource => resource.id && resource.id.trim() !== "")) {
                    // setDefaultDateRangeFromResources(ressourcesAbs);
                    updateFilteredResources();
                } 
                
            });
        }
    });
        
    

    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const resetButton = document.getElementById('resetDates');

    let dateChanged = false;

    // Fonction pour vérifier et appliquer la couleur
    function updateBorderColor() {
        if (startDateInput.value || endDateInput.value) {
            filterContainer.classList.add('red-left-border');
            dateChanged = true;
        } else {
            filterContainer.classList.remove('red-left-border');
            dateChanged = false;
        }
    }

    // Appliquer la logique au changement de dates
    [startDateInput, endDateInput].forEach(input => {
        input.addEventListener('change', updateBorderColor);
    });

    // Réinitialiser les dates et la couleur
    resetButton.addEventListener('click', () => {
        startDateInput.value = '';
        endDateInput.value = '';
        filterContainer.classList.remove('red-left-border');
        dateChanged = false;
    });

    // Observation pour les icônes de disponibilité
    const iconElementFree = document.getElementById("availabilityIcon");
    const iconElementPartial = document.getElementById("availabilityIconPartial");

    observeIconState(iconElementFree, updateFilteredResources);
    observeIconState(iconElementPartial, updateFilteredResources);

    updateFilteredResources(); // Initialisation
}

const observers = new Map(); // Stock des observers existants

function observeIconState(iconElement, callback) {
    // Vérification s'il existe déjà un observer pour cet élément
    if (observers.has(iconElement)) {
        observers.get(iconElement).disconnect(); // Suppresion de l'ancien observer
    }

    const observer = new MutationObserver(() => {
        const isActive = iconElement.classList.contains("fa-toggle-on");
        callback(isActive);
    });

    observer.observe(iconElement, { attributes: true, attributeFilter: ["class"] });
    // observers.set(iconElement, observer); // Stock le nouvel observer
}


// Fonction pour mettre à jour l'état des icônes
function updateIconState(iconElement, isActive) {
    if (isActive) {
        iconElement.classList.replace("fa-toggle-off", "fa-toggle-on");
        iconElement.style.color = iconElement.id === "availabilityIcon" || iconElement === "availabilityIcon" ? "#007bff" : "#FFA500"; 
    } else {
        iconElement.classList.replace("fa-toggle-on", "fa-toggle-off");
        iconElement.style.color = "#ccc"; 
    }
}


document.addEventListener("DOMContentLoaded", async function () {
    // window.availabilityFree = false; // Disponibilité totale
    // window.availabilityPartial = false; // Affecté partiel

    const iconElementFree = document.getElementById("availabilityIcon"); // Icône totalement libre
    const iconElementPartial = document.getElementById("availabilityIconPartial"); // Icône partiellement affecté


    try {
        // Événement sur l'icône "Totalement libre"
        iconElementFree.addEventListener("click", function () {
            window.availabilityFree = !window.availabilityFree;
            updateIconState(iconElementFree, window.availabilityFree);
            filterResourcesByAvailability();
        });

        // Événement sur l'icône "Affecté partiel"
        iconElementPartial.addEventListener("click", function () {
            window.availabilityPartial = !window.availabilityPartial;
            updateIconState(iconElementPartial, window.availabilityPartial);
            filterResourcesByAvailability();
        });

    } catch (error) {
        console.error("Erreur lors du chargement des données :", error);
    }
});