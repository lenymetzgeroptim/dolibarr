function fetchFilterData(lazy = false) {
    return new Promise((resolve, reject) => {
        // Paramètres conditionnels (lazy loading)
        const datatofilter = lazy ? { limit: 100 } : {};

        // Indicateur de chargement
        // showLoader(true);

        $.ajax({
            url: '/custom/workload/ajax/filter_data.php',
            type: 'GET',
            dataType: 'json',
            data: datatofilter, 
            cache: false,
            success: function (responsefilter) {
                // showLoader(false); // Masquer le loader

                // Vérification des données
                if (responsefilter.dataUsers && Array.isArray(responsefilter.dataUsers) 
				    && responsefilter.dataJobs && Array.isArray(responsefilter.dataJobs)
					&& responsefilter.dataGroups && Array.isArray(responsefilter.dataGroups)
					&& responsefilter.dataRespProj && Array.isArray(responsefilter.dataRespProj)
					&& responsefilter.dataProjects && Array.isArray(responsefilter.dataProjects)
					&& responsefilter.dataOrders && Array.isArray(responsefilter.dataOrders)
					&& responsefilter.dataSkills && Array.isArray(responsefilter.dataSkills)
					&& responsefilter.dataAgencies && Array.isArray(responsefilter.dataAgencies)
					&& responsefilter.dataPropals && Array.isArray(responsefilter.dataPropals)
                    && responsefilter.dataAbsType && Array.isArray(responsefilter.dataAbsType)) {
                  
					// console.log(responsefilter.dataPropals);
					 populateSelect("#jobFilter", responsefilter.dataJobs, "job_id", "job_label");
					 populateSelect("#userFilter", responsefilter.dataUsers, "id", "fullname");
					 populateSelect("#groupFilter", responsefilter.dataGroups, "group_id", "nom");
					 populateSelect("#respProjFilter", responsefilter.dataRespProj, "id", "fullname");
					 populateSelect("#projectFilter", responsefilter.dataProjects, "fk_projet", "nom");
					 populateSelect("#orderFilter", responsefilter.dataOrders, "order_id", "nom");
					 populateSelect("#skillFilter", responsefilter.dataSkills, "skillid", "label");
					 populateSelect("#agenceFilter", responsefilter.dataAgencies, "socid", "name_alias");
					 populateSelect("#propalFilter", responsefilter.dataPropals, "propal_id", "nom");
                     populateSelect("#absFilter", responsefilter.dataAbsType, "fk_type", "conge_label");
                     populateSelect("#resAntFilter", responsefilter.dataAgencies, "socid", "name_alias");
                     populateSelect("#domFilter", responsefilter.dataProjects, "domaine", "domaine");
                     
                     
					 
                  
                    resolve(responsefilter);
                } else {
                    console.warn("Réponse non valide :", responsefilter);
                    reject("Données des filtres invalides reçues.");
                }
            },
            error: function (xhr, status, error) {
                // showLoader(false); // Masquer le loader

                // Log détaillé des erreurs
                console.error("Erreur AJAX :", error);
                reject({
                    message: "Erreur lors de l'appel AJAX",
                    status: status,
                    error: error,
                });
            }
        });
    });
}

function fetchData(lazy = false) {
    return new Promise((resolve, reject) => {
        // Paramètres conditionnels (lazy loading)
        const data = lazy ? { limit: 100 } : {};

        // Indicateur de chargement
        // showLoader(true);
        $.ajax({
            url: '/custom/workload/ajax/workload_data.php',
            type: 'GET',
            dataType: 'json',
            data: data, 
            cache: false,
            success: function (response) {
                // showLoader(false); // Masquer le loader

                // Vérification des données
                console.log("Données reçues :", response);

                // Si les données sont valides
                ressources = response.res; // Données globalement stocked
                ressourcesProj = response.resProj;
                ressourcesComm = response.resComm;
                ressourcesAbs = response.resAbs,
                resolve({
                    ressources: response.res,
                    ressourcesProj: response.resProj,
                    ressourcesComm: response.resComm,
                    ressourcesAbs: response.resAbs,
                    ressourcesProjAbs: response.resProjAbs
                });
            },
            error: function (xhr, status, error) {
                // showLoader(false); // Masquer le loader

                // Log détaillé des erreurs
                console.error("Erreur AJAX :", error);
                reject({
                    message: "Erreur lors de l'appel AJAX",
                    status: status,
                    error: error,
                });
            }
        });
    });
}

(async function initialize() {
    try {
        // Les absences en premier
        // const [{ ressourcesAbs }, filterData] = await Promise.all([fetchData(), fetchFilterData()]);
        // En parallèle 
        showLoader(true);
        const dataPromise = fetchData();         // Données complètes
        const filterPromise = fetchFilterData(); // Données filtres

        //Affichage des absences en premier
        dataPromise.then(({ ressourcesAbs }) => {
        updateGanttChartAbs(ressourcesAbs); 
    
        
        showLoader(false);
        });
    
        // Toutes les données pour les filtres
        const [
        {
            ressources,
            ressourcesProj,
            ressourcesComm,
            ressourcesProjAbs
        },
        filterData
        ] = await Promise.all([dataPromise, filterPromise]);
        // if (!Array.isArray(ressourcesAbs)) throw new Error("Données absences invalides.");

        // Affichage immédiat des absences
        // setupFilterListeners(ressourcesAbs, filterData, updateGanttChartAbs);
        // Initialisation des filtres au chargement
        setupFilterListeners(ressources, filterData, updateGanttChartColDev, 'dev');
        setupFilterListeners(ressourcesProj, filterData, updateGanttChartColProj, 'proj');
        setupFilterListeners(ressourcesProj, filterData, updateGanttChartProjCom, 'projcom');
        setupFilterListeners(ressourcesComm, filterData, updateGanttChartCodeCol, 'comm');
        setupFilterListeners(ressourcesProjAbs, filterData, updateGanttChartProjAbs, 'projabs');
        setupFilterListeners(ressourcesAbs, filterData, updateGanttChartAbs, 'abs');
        
        filterResourcesByAvailability(); // Mise à jour initiale des ressources

    } catch (error) {
        console.error("Erreur lors de l'initialisation :", error);
        showLoader(false);
    }
})();