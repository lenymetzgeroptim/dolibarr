function centerScrollOnAllLines5(lineElement5, fixedHeader) {
    const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV5');
     
 if(fixedHeader !== fixedHeader5) return;
  
    if (lineElement5 && fixedHeader) {
        let linePosition = lineElement5.offsetLeft;
        let scrollCenter = linePosition - 60;
        fixedHeader.scrollLeft = scrollCenter;
    }

 
}

function centerScrollOnAllLines4(lineElement5, fixedHeader) {
    const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV4');
  
    if(fixedHeader !== fixedHeader5) return;

    if (lineElement5 && fixedHeader) {
    let linePosition = lineElement5.offsetLeft;
    let scrollCenter = linePosition - 60;
    fixedHeader.scrollLeft = scrollCenter;
    }

}

function centerScrollOnAllLines3(lineElement5, fixedHeader) {
    const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV3');
     
 if(fixedHeader !== fixedHeader5) return;
  
    if (lineElement5 && fixedHeader) {
        let linePosition = lineElement5.offsetLeft;
        let scrollCenter = linePosition - 60;
        fixedHeader.scrollLeft = scrollCenter;
    }

 
}

function centerScrollOnAllLines2(lineElement5, fixedHeader) {
    const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV2');
  
    if(fixedHeader !== fixedHeader5) return;

    if (lineElement5 && fixedHeader) {
    let linePosition = lineElement5.offsetLeft;
    let scrollCenter = linePosition - 60;
    fixedHeader.scrollLeft = scrollCenter;
    }

}

function centerScrollOnAllLines1(lineElement5, fixedHeader) {
    const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV');
     
 if(fixedHeader !== fixedHeader5) return;
  
    if (lineElement5 && fixedHeader) {
        let linePosition = lineElement5.offsetLeft;
        let scrollCenter = linePosition - 60;
        fixedHeader.scrollLeft = scrollCenter;
    }

 
}

function centerScrollOnAllLines6(lineElement5, fixedHeader) {
    const fixedHeader5 = document.getElementById('fixedHeader_GanttChartDIV6');
  
    if(fixedHeader !== fixedHeader5) return;

    if (lineElement5 && fixedHeader) {
    let linePosition = lineElement5.offsetLeft;
    let scrollCenter = linePosition - 60;
    fixedHeader.scrollLeft = scrollCenter;
    }

}

function observeUntilReady(elementIds, callback) {
    const observer = new MutationObserver(() => {
        const elements = elementIds.map(id => document.getElementById(id));
        const allFound = elements.every(el => el !== null);

        if (allFound) {
            callback(...elements);
            observer.disconnect();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

// Affichage du loader
function showLoader(visible) {
    const loader = document.getElementById('loader'); 
    if (loader) {
        loader.style.display = visible ? 'block' : 'none';
    }
}

function applyScrollWhenVisible1(ganttContainer, offset, monthWidth) {
    const parentElement = document.getElementById('tabs');
    const checkVisibility = () => {
    
        if (window.getComputedStyle(parentElement).display !== 'none') {
            setTimeout(() => {
                scrollPosition = offset * monthWidth;
    
                ganttContainer.scrollLeft = scrollPosition;
                observer.disconnect(); // On arrête d'observer
                console.log(ganttContainer.scrollLeft);
            }, 100);
        } else {
            // console.log("En attente que le parent devienne visible...");
        }
    };

    const observer = new MutationObserver(checkVisibility);
    observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

    checkVisibility();
}

function applyScrollWhenVisible3(ganttContainer, offset, monthWidth) {
    const parentElement = document.getElementById('tabs3');
    const checkVisibility = () => {
    
        if (window.getComputedStyle(parentElement).display !== 'none') {
            setTimeout(() => {
                scrollPosition = offset * monthWidth;
    
                ganttContainer.scrollLeft = scrollPosition;
                observer.disconnect(); // On arrête d'observer
                // console.log(ganttContainer.scrollLeft);
            }, 100);
        } else {
            // console.log("En attente que le parent devienne visible...");
        }
    };

    const observer = new MutationObserver(checkVisibility);
    observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });
    checkVisibility();
}

function applyScrollWhenVisible4(ganttContainer, offset, monthWidth) {
    const parentElement = document.getElementById('tabs4');
    const checkVisibility = () => {
    
        if (window.getComputedStyle(parentElement).display !== 'none') {
            setTimeout(() => {
                scrollPosition = offset * monthWidth;
    
                ganttContainer.scrollLeft = scrollPosition;
                observer.disconnect(); // On arrête d'observer
                // console.log(ganttContainer.scrollLeft);
            }, 100);
        } else {
            console.log("En attente que le parent devienne visible...");
        }
    };

    const observer = new MutationObserver(checkVisibility);
    observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

    checkVisibility();
}

function applyScrollWhenVisible5(ganttContainer, offset, monthWidth) {
    const parentElement = document.getElementById('tabs5');
    const checkVisibility = () => {
    
        if (window.getComputedStyle(parentElement).display !== 'none') {
            setTimeout(() => {
                scrollPosition = offset * monthWidth;
    
                ganttContainer.scrollLeft = scrollPosition;
                observer.disconnect(); // On arrête d'observer
                // console.log(ganttContainer.scrollLeft);
            }, 100);
        } else {
            // console.log("En attente que le parent devienne visible...");
        }
    };

    const observer = new MutationObserver(checkVisibility);
    observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

    checkVisibility();
}

function applyScrollWhenVisible6(ganttContainer, offset, monthWidth) {
    const parentElement = document.getElementById('tabs6');
    const checkVisibility = () => {
    
        if (window.getComputedStyle(parentElement).display !== 'none') {
            setTimeout(() => {
                scrollPosition = offset * monthWidth;
    
                ganttContainer.scrollLeft = scrollPosition;
                observer.disconnect(); // On arrête d'observer
                // console.log(ganttContainer.scrollLeft);
            }, 100);
        } else {
            // console.log("En attente que le parent devienne visible...");
        }
    };

    const observer = new MutationObserver(checkVisibility);
    observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

    checkVisibility();
}

function applyScrollWhenVisible(ganttContainer, offset, monthWidth) {
    const parentElement = document.getElementById('tabs2');
    const checkVisibility = () => {
    
        if (window.getComputedStyle(parentElement).display !== 'none') {
            setTimeout(() => {
                scrollPosition = offset * monthWidth;
    
                ganttContainer.scrollLeft = scrollPosition;
                observer.disconnect(); // On arrête d'observer
            }, 100);
        } else {
            // console.log("En attente que le parent devienne visible...");
        }
    };

    const observer = new MutationObserver(checkVisibility);
    observer.observe(parentElement, { attributes: true, attributeFilter: ['style', 'class'] });

    checkVisibility();
}

function adjustGLineVHeight() {
    // Récupération des conteneurs de tables spécifiés
    const tableContainers = document.querySelectorAll('#GanttChartDIV, #GanttChartDIV1, #GanttChartDIV2, #GanttChartDIV3, #GanttChartDIV4, #GanttChartDIV5, #GanttChartDIV6');

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
            //  console.warn(`La hauteur de la table avec ID "${tableContainer.id}" est calculée comme 0. Vérifiez la visibilité ou le chargement des éléments.`); 
            return;
        }

        // Réduction de la hauteur par 3 cm (1 cm = 37.7952755906 px)
        const reductionInPixels = 1.5 * 37.7952755906; // Conversion de cm en pixels
        totalHeight = Math.max(0, totalHeight - reductionInPixels); 

        // Récupération de tous les éléments ayant la classe glinev
        const gLineVElements = document.querySelectorAll('.glinev');

        if (gLineVElements.length === 0) {
            // console.warn('Aucun élément trouvé avec la classe .glinev'); 
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

