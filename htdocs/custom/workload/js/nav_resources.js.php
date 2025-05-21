

/* Javascript library of module Workload */
document.addEventListener('DOMContentLoaded', function () {
    // Variables pour stocker les états seulement une fois
    let savedAvailabilityState = null;
    let savedPartialState = null;
    // Fonction pour activer un onglet et afficher son contenu
    function activateTab(tabId) {
        const activeTab = document.getElementById(tabId);
        const toggleAvailability = document.getElementById('toggleAvailability');
        const availabilityIcon = document.getElementById('availabilityIcon');
        const toggleAvailabilityPartial = document.getElementById('toggleAvailabilityPartial');
        const availabilityIconPartial = document.getElementById('availabilityIconPartial');
       
        // L'état actuel avant l'application du changement
        const isAvailabilityIconActive = availabilityIcon.classList.contains("fa-toggle-on");
        const isAvailabilityIconPartialActive = availabilityIconPartial.classList.contains("fa-toggle-on");
 
    
        if (activeTab) {
            const parent = activeTab.closest('.tabsElem'); // Le parent de l'onglet actif

            // Désactivation de tous les parents sauf le parent actif
            const allTabsParents = document.querySelectorAll('.tabsElem');
            allTabsParents.forEach(function (currentParent) {
                if (currentParent !== parent) {
                    currentParent.classList.remove('tabactive'); // Retirer la classe des autres parents
                }
            });

            // Ajout de la classe active au parent de l'onglet cliqué
            if (parent) {
                parent.classList.add('tabactive');
            }
        }

        // Gestion de l'affichage dans le filtre la selection de la période d'absence 
        const container = document.getElementById("dateFilterContainer");
        if (tabId === 'gant5' || tabId === 'gant6') {
            if (container && container.style.display === "none") {
                container.style.display = "flex";
            }
        }else{
            if (container && container.style.display === "flex") {
                container.style.display = "none";
            }
        }
     

        const orderFilterDiv = document.querySelector('#orderFilter')?.closest('div');
        const propalFilterDiv = document.querySelector('#propalFilter')?.closest('div');
        const absFilterDiv = document.querySelector('#absFilter')?.closest('div');

        
        if (tabId === 'gant5' || tabId === 'gant6') {
            if (orderFilterDiv) orderFilterDiv.style.display = 'none';
            if (propalFilterDiv) propalFilterDiv.style.display = 'none';
            if (absFilterDiv) absFilterDiv.style.display = 'flex';
        } else {
            if (orderFilterDiv) orderFilterDiv.style.display = 'flex';
            if (propalFilterDiv) propalFilterDiv.style.display = 'flex';
            if (absFilterDiv) absFilterDiv.style.display = 'none';
        }
        

        // Gestion des boutons de disponibilité
        if (tabId === 'gant4' || tabId === 'gant5' || tabId === 'gant3' || tabId === 'gant6') {
            if (savedAvailabilityState === null) {
                savedAvailabilityState = isAvailabilityIconActive;
                savedPartialState = isAvailabilityIconPartialActive;
        }

            disableButton(toggleAvailability, availabilityIcon);
            disableButton(toggleAvailabilityPartial, availabilityIconPartial);
            availabilityIconPartial.style.visibility = 'hidden';
            availabilityIcon.style.visibility = 'hidden';

            if (isAvailabilityIconActive || isAvailabilityIconPartialActive) {
                updateIconState(availabilityIcon, false);
                updateIconState(availabilityIconPartial, false);
                setEventMessagesJS("Les disponibilités ne s'appliquent pas sur ce mode.", 'info', tabId);
            }
        } else {
            availabilityIconPartial.style.visibility = 'visible';
            availabilityIcon.style.visibility = 'visible';

            if (savedAvailabilityState !== null) {
                enableButton(toggleAvailability, availabilityIcon, savedAvailabilityState);
                updateIconState(availabilityIcon, savedAvailabilityState);
            }
            if (savedPartialState !== null) {
                enableButton(toggleAvailabilityPartial, availabilityIconPartial, savedPartialState);
                updateIconState(availabilityIconPartial, savedPartialState);
            }

            savedAvailabilityState = null;
            savedPartialState = null;
        }
        
        // Masquer tous les Gantt avant d'afficher celui correspondant
        const gantts = document.querySelectorAll('.gantt'); // Sélectionner tous les éléments Gantt
        gantts.forEach(function(gantt) {
            gantt.style.display = 'none'; // Masquer tous les Gantt
        });

       // Afficher le Gantt correspondant en fonction de l'onglet cliqué
       if (tabId === 'gant1') {
            document.getElementById('tabs').style.display = 'block';
            document.getElementById('GanttChartDIV').style.display = 'block';
        } else if (tabId === 'gant2') {
            document.getElementById('tabs2').style.display = 'block';
            document.getElementById('GanttChartDIV2').style.display = 'block';
        } else if (tabId === 'gant3') {
            document.getElementById('tabs3').style.display = 'block';
            document.getElementById('GanttChartDIV3').style.display = 'block';
        } else if (tabId === 'gant4') {
            document.getElementById('tabs4').style.display = 'block';
            document.getElementById('GanttChartDIV4').style.display = 'block';
        } else if (tabId === 'gant5') {
            document.getElementById('tabs5').style.display = 'block';
            document.getElementById('GanttChartDIV5').style.display = 'block';
        } else if (tabId === 'gant6') {
            document.getElementById('tabs6').style.display = 'block';
            document.getElementById('GanttChartDIV6').style.display = 'block';
        }else {
            console.log('Onglet non trouvé : ' + tabId);
        }
    

    }

    // Ajout des écouteurs d'évènements pour chaque onglet
    const tabs = document.querySelectorAll('.tab');
    
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function () {
            activateTab(this.id); // Activation de l'onglet correspondant
        });
    });

    // Fonction pour griser 
    function disableButton(button, icon) {
        if (button && icon) {
            button.style.background = '#f8f9fa'; 
            button.style.display = 'none';
            button.style.opacity = '0.6'; // Effet d'opacité pour un style pro
            icon.style.opacity = '0.6';
            button.style.transition = 'background 0.3s ease-in-out, opacity 0.3s ease-in-out';
        }
    }

    // Fonction pour réactiver un bouton avec un style Dolibarr pro
    function enableButton(button, icon, isActive) {
    if (button && icon) {
        button.style.background = '#e9ecef';
        button.style.cursor = 'pointer';
        button.style.opacity = '1';
        button.style.display = 'block';
        button.style.transition = 'background 0.5s ease-in-out, opacity 0.5s ease-in-out';

        // Couleur de l'icône selon son état actif
        if (isActive) {
            icon.style.color = icon.id === "availabilityIcon" ? "#007bff" : "#FFA500";
        } else {
            icon.style.color = icon.id === "availabilityIcon" ? "#007bff" : "#FFA500";
        }

        icon.style.opacity = '1';
    }
}



    // Fonction toggle pour basculer entre toutes les ressources et les filtrées
    function toggleResources(element, data) {
        const toggleIcon = document.querySelector('.fa-toggle-on, .fa-toggle-off');

        if (element.style.display === 'none') {
            element.style.display = 'block';
            toggleIcon.classList.remove('fa-toggle-off');
            toggleIcon.classList.add('fa-toggle-on');
            // Filtrage des données si nécessaire
            return data.filter(item => item.id === undefined); 
        } else {
            element.style.display = 'none';
            toggleIcon.classList.remove('fa-toggle-on');
            toggleIcon.classList.add('fa-toggle-off');
            return data; // Retourne toutes les données
        }
    }  

    // Activation de l'onglet Gantt 5 par défaut
    activateTab('gant5');
   
});


// Stockage du message en cours
let currentMessage = null;

/**
 * Simule setEventMessages() de Dolibarr en JS
 * @param {string | string[]} message - Message ou tableau de messages
 * @param {string} type - Type ('success', 'warning', 'error', 'info')
 */
function setEventMessagesJS(message, type = 'info', tabId) {
    let activeTab = document.getElementById(tabId);
    let title = activeTab ? activeTab.getAttribute('title') : '';
    if (Array.isArray(message)) {
        message = message.join('<br>'); // Affiche plusieurs messages dans une seule boîte
    }

    if (title) {
        message += ` (${title})`;
    }

    // Supprime le message précédent avant d'afficher un nouveau
    if (currentMessage) {
        currentMessage.remove();
    }

    displayEventMessage(message, type);
}

function displayEventMessage(text, type) {
    let messageContainer = document.getElementById('eventMessageContainer');

    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.id = 'eventMessageContainer';
        messageContainer.style.position = 'fixed';
        messageContainer.style.top = '50px';
        messageContainer.style.right = '10px';
        messageContainer.style.zIndex = '1000';
        messageContainer.style.width = '600px';
        messageContainer.style.fontFamily = 'Arial, sans-serif';
        messageContainer.style.fontSize = '18px';
        document.body.appendChild(messageContainer);
    }

    // Création du message
    const messageBox = document.createElement('div');
    messageBox.style.display = 'flex';
    messageBox.style.justifyContent = 'space-between';
    messageBox.style.alignItems = 'center';
    messageBox.style.padding = '10px';
    messageBox.style.margin = '5px 0';
    messageBox.style.borderRadius = '5px';
    messageBox.style.border = '1px solid transparent';
    messageBox.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
    messageBox.style.position = 'relative';

    // Ajout du texte
    const messageText = document.createElement('span');
    messageText.innerHTML = text;
    messageBox.appendChild(messageText);

    // Bouton "X" pour fermer
    const closeButton = document.createElement('span');
    closeButton.innerHTML = '&times;';
    closeButton.style.cursor = 'pointer';
    closeButton.style.fontWeight = 'bold';
    closeButton.style.marginLeft = '10px';
    closeButton.style.fontSize = '16px';

    // Ferme le message au clic
    closeButton.onclick = function () {
        messageBox.remove();
        currentMessage = null;
    };

    messageBox.appendChild(closeButton);

    // Appliquer les couleurs exactes de Dolibarr
    switch (type) {
        case 'success': 
            messageBox.style.backgroundColor = '#d4edda';
            messageBox.style.color = '#155724';
            messageBox.style.borderColor = '#c3e6cb';
            break;
        case 'info': 
            messageBox.style.backgroundColor = '#d1ecf1';
            messageBox.style.color = '#0c5460';
            messageBox.style.borderColor = '#bee5eb';
            break;
        case 'warning': 
            messageBox.style.backgroundColor = '#fff3cd';
            messageBox.style.color = '#856404';
            messageBox.style.borderColor = '#ffeeba';
            break;
        case 'error': 
            messageBox.style.backgroundColor = '#f8d7da';
            messageBox.style.color = '#721c24';
            messageBox.style.borderColor = '#f5c6cb';
            break;
    }

    // Ajout au container
    messageContainer.appendChild(messageBox);

    // Stocke le message actuel pour suppression future
    currentMessage = messageBox;

    // Supprime automatiquement après 3s
    setTimeout(() => {
        if (currentMessage === messageBox) {
            messageBox.remove();
            currentMessage = null;
        }
    }, 3000);
}






