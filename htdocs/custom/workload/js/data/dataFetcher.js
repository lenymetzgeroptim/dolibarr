// Appel Ajax àaprtir du fichier workload.config.js
Workload.DataFetcher = (function () {
    // Pour récupérer la dtat des filtres
    function fetchFilterData(lazy = false) {
        return new Promise((resolve, reject) => {
            const datatofilter = lazy ? { limit: 100 } : {};

            $.ajax({
                url: WorkloadConfig.api.filterUrl,
                type: 'GET',
                dataType: 'json',
                data: datatofilter,
                cache: false,
                success: function (responsefilter) {
                    if (
                        responsefilter.dataUsers && Array.isArray(responsefilter.dataUsers) &&
                        responsefilter.dataJobs && Array.isArray(responsefilter.dataJobs) &&
                        responsefilter.dataGroups && Array.isArray(responsefilter.dataGroups) &&
                        responsefilter.dataRespProj && Array.isArray(responsefilter.dataRespProj) &&
                        responsefilter.dataProjects && Array.isArray(responsefilter.dataProjects) &&
                        responsefilter.dataOrders && Array.isArray(responsefilter.dataOrders) &&
                        responsefilter.dataSkills && Array.isArray(responsefilter.dataSkills) &&
                        responsefilter.dataAgencies && Array.isArray(responsefilter.dataAgencies) &&
                        responsefilter.dataPropals && Array.isArray(responsefilter.dataPropals) &&
                        responsefilter.dataAbsType && Array.isArray(responsefilter.dataAbsType)
                    ) {
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
                    console.error("Erreur AJAX :", error);
                    reject({ message: "Erreur AJAX", status, error });
                }
            });
        });
    }

    // Pour récupérer la data à afficher dans Gannt aprés traitement. 
    function fetchData(lazy = false) {
        return new Promise((resolve, reject) => {
            const data = lazy ? { limit: 100 } : {};

            $.ajax({
                url: WorkloadConfig.api.dataUrl,
                type: 'GET',
                dataType: 'json',
                data: data,
                cache: false,
                success: function (response) {
                    resolve({
                        ressources: response.res,
                        ressourcesProj: response.resProj,
                        ressourcesComm: response.resComm,
                        ressourcesAbs: response.resAbs,
                        ressourcesProjAbs: response.resProjAbs
                    });
                },
                error: function (xhr, status, error) {
                    console.error("Erreur AJAX :", error);
                    reject({ message: "Erreur AJAX", status, error });
                }
            });
        });
    }

    // Assemablgae des deux fonction fetch (Cette fonction est appelée dans la main.js)
    async function fetchAllData() {
        try {
            const [data, filterData] = await Promise.all([
                fetchData(),
                fetchFilterData()
            ]);
            return { data, filterData };
        } catch (error) {
            console.error("Erreur fetchAllData :", error);
            throw error;
        }
    }

    return {
        fetchFilterData,
        fetchData,
        fetchAllData 
    };
})();


