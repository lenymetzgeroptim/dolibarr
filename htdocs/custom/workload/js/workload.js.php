<?php
/* Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    workload/js/workload.js.php
 * \ingroup workload
 * \brief   JavaScript file for module Workload.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

/* Javascript library of module Workload */

function fetchData(lazy = false) {
    return new Promise((resolve, reject) => {
        // Paramètres conditionnels (lazy loading)
        const data = lazy ? { limit: 100 } : {};

        // Indicateur de chargement
        showLoader(true);

        $.ajax({
            url: '/custom/workload/ajax/workload_data.php',
            type: 'GET',
            dataType: 'json',
            data: data, 
            cache: false,
            success: function (response) {
                showLoader(false); // Masquer le loader

                // Vérification des données
                console.log("Données reçues :", response);
                if (response.res && Array.isArray(response.res) 
                && response.resProj && Array.isArray(response.resProj)
                && response.resComm && Array.isArray(response.resComm)
                && response.resAbs && Array.isArray(response.resAbs)) {
                    ressources = response.res; // Stocker les données globalement
                    ressourcesProj = response.resProj;
                    // resolve(response);
                    resolve({
                        ressources: response.res,
                        ressourcesProj: response.resProj,
                        ressourcesComm: response.resComm,
                        ressourcesAbs: response.resAbs
                    });
                } else {
                    console.warn("Réponse non valide :", response);
                    reject("Données invalides reçues.");
                }
            },
            error: function (xhr, status, error) {
                showLoader(false); // Masquer le loader

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

// Afficher d'un loader
function showLoader(visible) {
    const loader = document.getElementById('loader'); 
    if (loader) {
        loader.style.display = visible ? 'block' : 'none';
    }
}




// Fonction asynchrone pour initialiser les données
(async function initialize() {
    try {
        // Récupérer les données via AJAX
        // const data = await fetchData();
        // Appel à fetchData et récupération des données via `resolve`
        const { ressources, ressourcesProj, ressourcesComm,  ressourcesAbs } = await fetchData();

        //  récupération des données pour filtrer
        const filterData = await fetchFilterData();

        // Vérifier si les données sont valides
        if (!ressources || !Array.isArray(ressources)) {
            throw new Error("Les données reçues sont invalides ou non conformes.");
        }
        if (!ressourcesProj || !Array.isArray(ressourcesProj)) {
            throw new Error("Les données reçues sont invalides ou non conformes.");
        }
        if (!ressourcesComm || !Array.isArray(ressourcesComm)) {
            throw new Error("Les données reçues sont invalides ou non conformes.");
        }
        if (!ressourcesAbs || !Array.isArray(ressourcesAbs)) {
            throw new Error("Les données reçues sont invalides ou non conformes.");
        }

        // if (!filterData || !Array.isArray(filterData)) {
        //     throw new Error("Les données des filtres reéues sont invalides ou non conformes.");
        // }
  
        // Ecouteurs sur les évènements 
        setupFilterListeners(ressources, filterData, updateGanttChartColDev);
        setupFilterListeners(ressourcesProj, filterData, updateGanttChartColProj);
        setupFilterListeners(ressourcesProj, filterData, updateGanttChartProjCom);
        setupFilterListeners(ressourcesComm, filterData, updateGanttChartCodeCol);
        setupFilterListeners(ressourcesAbs, filterData, updateGanttChartAbs);
        // Afficher toutes les données dans le Gantt par défaut
      
        updateGanttChartColDev(ressources);
        updateGanttChartColProj(ressourcesProj);
        updateGanttChartProjCom(ressourcesProj);
        updateGanttChartCodeCol(ressourcesComm);
        updateGanttChartAbs(ressourcesAbs);

        // centerDateAbs(ressources, 'GanttChartDIV'); // Gantt 1
        // centerDateAbs(ressourcesProj, 'GanttChartDIV2'); // Gantt 2
        // centerDateAbs(ressourcesComm, 'GanttChartDIV3'); // Gantt 3
        centerDateAbs(ressourcesAbs); // Gantt 5
        
    } catch (error) {
        console.error("Erreur lors de l'initialisation :", error);
    }
})();

function adjustGLineVHeight() {
    // Récupération des conteneurs de tables spécifiés
    const tableContainers = document.querySelectorAll('#GanttChartDIV2, #GanttChartDIV3, #GanttChartDIV4, #GanttChartDIV5');

    // Vérification si des conteneurs sont trouvés
    if (!tableContainers || tableContainers.length === 0) {
        console.warn('Aucun conteneur de table trouvé.');
        return;
    }

    // Parcourir chaque conteneur pour calculer et appliquer la hauteur
    tableContainers.forEach(tableContainer => {
        if (!tableContainer) {
            console.warn('Un conteneur de table est introuvable.');
            return;
        }

        // Calculer la hauteur totale de la table
        let totalHeight = tableContainer.offsetHeight;

        if (totalHeight === 0) {
            console.warn(`La hauteur de la table avec ID "${tableContainer.id}" est calculée comme 0. Vérifiez la visibilité ou le chargement des éléments.`);
            return;
        }

        // Réduction de la hauteur par 3 cm (1 cm = 37.7952755906 px)
        const reductionInPixels = 1.5 * 37.7952755906; // Conversion de cm en pixels
        totalHeight = Math.max(0, totalHeight - reductionInPixels); 

        // Récupération de tous les éléments ayant la classe glinev
        const gLineVElements = document.querySelectorAll('.glinev');

        if (gLineVElements.length === 0) {
            console.warn('Aucun élément trouvé avec la classe .glinev');
            return;
        }

        //  Hauteur réduite à chaque élément de classe glinev
        gLineVElements.forEach(element => {
            element.style.height = `${totalHeight}px`;
        });
    });
}



// Appel de la fonction après que tout le contenu est chargé
document.addEventListener('DOMContentLoaded', () => {
    adjustGLineVHeight();

    // Si des éléments sont ajoutés ou changent dynamiquement
    const observer = new MutationObserver(() => {
        adjustGLineVHeight();
    });

    // ObservATION des changements dans le conteneur principal
    const target = document.body;
    observer.observe(target, { childList: true, subtree: true });
});

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
