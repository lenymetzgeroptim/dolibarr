window.WorkloadConfig = {
  api: {
    dataUrl: '/custom/workload/ajax/workload_data.php',
    filterUrl: '/custom/workload/ajax/filter_data.php',
    },
    // ganttDivs: ['GanttChartDIV', 'GanttChartDIV2', 'GanttChartDIV3', 'GanttChartDIV4', 'GanttChartDIV5', 'GanttChartDIV6'],
    // modes: ['tabs', 'tabs2', 'tabs3', 'tabs4', 'tabs5', 'tabs6']
};

// Stockage global (objet/fonctions) pour les données (type singleton)
// # Namespace global de l'application (pour gérer dataFetch - dans le fichier fetchData et workload.main.js)
window.Workload = window.Workload || {};

Workload.DataStore = {
  ressources: [],
  ressourcesProj: [],
  ressourcesComm: [],
  ressourcesProjAbs: [],
  ressourcesAbs: []
};

window.availabilityFree = false; // Disponibilité totale
window.availabilityPartial = false; // Affecté partiel
