// Vérification de DOM s'il est prêt
// Initilisation asynchrone 
(async function initialize() {
    try {
        // showLoader(true);
        // Requêtes (data et filtreData) en parallèle
        const dataPromise = Workload.DataFetcher.fetchAllData();

        // Affichage prioritaire en premier des absences dès que disponibles
        dataPromise.then(({ data }) => {
            ressourcesAbs = Array.isArray(data.ressourcesAbs) ? data.ressourcesAbs : Object.values(data.ressourcesAbs || {});
            updateGanttChartAbs(ressourcesAbs);
            showLoader(false);
        });

        // Attente complète des deux réponses
        const { data, filterData } = await dataPromise;

        // Conversion en tableaux si besoin
         ressources        = Array.isArray(data.ressources)        ? data.ressources        : Object.values(data.ressources || {});
         ressourcesProj    = Array.isArray(data.ressourcesProj)    ? data.ressourcesProj    : Object.values(data.ressourcesProj || {});
         ressourcesComm    = Array.isArray(data.ressourcesComm)    ? data.ressourcesComm    : Object.values(data.ressourcesComm || {});
         ressourcesProjAbs = Array.isArray(data.ressourcesProjAbs) ? data.ressourcesProjAbs : Object.values(data.ressourcesProjAbs || {});
        //  ressourcesAbs     = Array.isArray(data.ressourcesAbs)     ? data.ressourcesAbs     : Object.values(data.ressourcesAbs || {});

        // Filtres interactifs (après affichage initial des absences)
        setupFilterListeners(ressourcesAbs, filterData, updateGanttChartAbs, 'abs');
        setupFilterListeners(ressources, filterData, updateGanttChartColDev, 'dev');
        setupFilterListeners(ressourcesProj, filterData, updateGanttChartColProj, 'proj');
        setupFilterListeners(ressourcesProj, filterData, updateGanttChartProjCom, 'projcom');
        setupFilterListeners(ressourcesComm, filterData, updateGanttChartCodeCol, 'comm');
        setupFilterListeners(ressourcesProjAbs, filterData, updateGanttChartProjAbs, 'projabs');

        filterResourcesByAvailability(); 

    } catch (e) {
        console.error("Erreur d'initialisation :", e);
        showLoader(false);
    }
})();



