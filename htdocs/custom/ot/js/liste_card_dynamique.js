document.addEventListener("DOMContentLoaded", function() {
    let cellData = window.cellData;
    let otId = window.otId;
    let userdata = window.userdata;
    let userjson = window.userjson;
    let status = window.status;
    let isUserProjectManager = window.isUserProjectManager;
    let jsdata = window.jsdata;
    let isDataSaved = false;
    let isUniqueListCreated = false;
    let users = typeof jsdata === "string" ? JSON.parse(jsdata) : jsdata;
    let selectedContacts = [];
    let jsdatasoustraitants = users.filter(user => user.source === "external" && user.fk_c_type_contact === "1031141");
    let jsdataFiltered = users.filter(user => user.source !== "external"); 
    jsdata = jsdataFiltered;




let columnsContainer = document.querySelector(".card-columns") ||
    document.querySelector("#card-columns") ||
    document.querySelector(".main-columns-container");

if (!columnsContainer) {
    console.warn("Le conteneur parent des colonnes a pas été trouvé, création un nouveau conteneur.");
    columnsContainer = document.createElement("div");
    columnsContainer.id = "card-columns";
    columnsContainer.className = "card-columns";
    document.body.appendChild(columnsContainer);
}


const uniqueJsData = jsdata.filter((value, index, self) => 
    index === self.findIndex((t) => (
        t.fk_socpeople === value.fk_socpeople
    ))
);




    if (status === 1 || status === 2 || !isUserProjectManager) {
    
    setTimeout(() => {
        // Masquer les boutons "Ajouter une carte" et "Ajouter une liste"
        document.querySelectorAll(".dropdown").forEach(function (dropdown) {
            dropdown.style.display = "none";
        });

        // Masquer les croix pour les lignes des listes
        document.querySelectorAll(".remove-user").forEach(function (removeButton) {
            removeButton.style.display = "none";
        });

        // Masquer les boutons "Supprimer" des cartes et des listes
        document.querySelectorAll(".delete-button, .delete-list-button").forEach(function (deleteButton) {
            deleteButton.style.display = "none";
        });

        // Désactiver la sélection utilisateur dans les cartes
        document.querySelectorAll(".name-dropdown").forEach(function (dropdown) {
            const selectedUserId = dropdown.value;
            const selectedUser = dropdown.options[dropdown.selectedIndex]?.text || "Utilisateur non défini";

            // Remplacer le menu déroulant par un texte affichant utilisateur sélectionné
            const userDisplay = document.createElement("p");
            userDisplay.textContent = selectedUser;
            userDisplay.style.textAlign = "center";
            userDisplay.style.color = "#333";

            dropdown.replaceWith(userDisplay);
        });

        // Désactiver les champs des sous-traitants
        document.querySelectorAll(".cardsoustraitant .form-input").forEach(function (input) {
            input.disabled = true;
        });

        // Désactiver les champs des cartes et listes
        document.querySelectorAll(".form-input, .list-title-input, .title-input").forEach(function (input) {
            input.disabled = true;
        });
    }, 500); 
}


function displayUserList() {
    const existingUniqueList = document.querySelector(".user-list.unique-list");

    // Supprimer la liste par défaut si elle existe, pour éviter des doublons
    if (existingUniqueList) {
        existingUniqueList.remove();
    }

    // Vérifier si des données de la BDD existent dans `cellData`
    if (typeof cellData !== "undefined" && cellData.length > 0) {
        const hasUniqueList = cellData.some(cell => cell.type === "listeunique");

        if (hasUniqueList) {
            cellData.forEach(cell => {
                if (cell.type === "listeunique") {
                    const list = createUniqueUserList();

                    // Remplir le titre de la liste
                    const titleInput = list.querySelector(".list-title-input");
                    if (titleInput) {
                        titleInput.value = cell.title || "Organigramme - Liste des utilisateurs"; // Titre par défaut si vide
                    }

                    // Ajouter la liste au conteneur
                    columnsContainer.appendChild(list);
                }
            });
        } else {
            // Créer une nouvelle liste unique avec titre par défaut
            const uniqueList = createUniqueUserList();
            uniqueList.style.marginTop = "20px";
            const titleInput = uniqueList.querySelector(".list-title-input");
            if (titleInput) {
                titleInput.value = "Organigramme - Liste des utilisateurs";
            }
            columnsContainer.appendChild(uniqueList);
            saveData();
        }
    } else {
        // Créer une nouvelle liste unique avec titre par défaut
        const uniqueList = createUniqueUserList();
        uniqueList.style.marginTop = "20px";
        const titleInput = uniqueList.querySelector(".list-title-input");
        if (titleInput) {
            titleInput.value = "Organigramme - Liste des utilisateurs";
        }
        columnsContainer.appendChild(uniqueList);
        saveData();
    }
}


// Appeler la fonction pour afficher la liste lors du chargement de la page
displayUserList();

    // Trier les utilisateurs par ordre croissant de nom (lastname)
    userjson.sort(function(a, b) {
        if (a.lastname < b.lastname) return -1;
        if (a.lastname > b.lastname) return 1;
        return 0;
    });

    // Générer les options du dropdown après tri des utilisateurs
    let alluser = `<option value="" disabled selected>Sélectionner un utilisateur</option>`;
    userjson.forEach(function(user) {
        alluser += `<option value="${user.rowid}">${user.lastname} ${user.firstname}</option>`;
    });

    // Générer les options de  uniqueJsData
    let userOptions = `<option value="" disabled selected>Sélectionner un utilisateur</option>`;
    uniqueJsData.forEach(function(user) {
        userOptions += `<option value="${user.fk_socpeople}">${user.firstname} ${user.lastname}</option>`;
    });

if (typeof cellData !== "undefined" && cellData.length > 0) {
    const addedCardTitles = new Set();
    const addedListTitles = new Set();

    cellData.forEach(function(cell) {
        let column = cell.x;

    if (cell.type === "card") {                                          
  
    if (!addedCardTitles.has(cell.title)) {
        const card = createEmptyCard(column);
        const titleInput = card.querySelector(".title-input");
        titleInput.value = cell.title;

        const nameDropdown = card.querySelector(".name-dropdown");
        nameDropdown.innerHTML = userOptions; // Use filtered project users

        if (cell.userId) {  
            const userId = cell.userId;  
       

            const user = userjson.find(u => u.rowid == userId);
            if (user) {
                nameDropdown.value = userId;
                
                // Afficher les habilitations et le contrat
                const habilitationInfo = card.querySelector(".habilitation-info");
                const contratInfo = card.querySelector(".contrat-info");
                
                if (habilitationInfo && contratInfo) {
                    habilitationInfo.textContent = `Habilitations: ${cell.habilitations || user.habilitations || "Non spécifié"}`;
                    contratInfo.textContent = `Contrat: ${cell.contrat || user.contrat || "Non spécifié"}`;
                    
                    // Sauvegarder dans le dataset
                    card.dataset.habilitations = cell.habilitations || user.habilitations || "";
                    card.dataset.contrat = cell.contrat || user.contrat || "";
                }
            } else {
                console.warn("⚠️ User introuvable avec ID :", userId);
            }
        } else {
        }

        const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
        if (columnElement) {
            columnElement.appendChild(card);
        }

        addedCardTitles.add(cell.title);
    }
}

else if (cell.type === "list") {
    if (!addedListTitles.has(cell.title)) {
        const list = createUserList(column);
        const titleInput = list.querySelector(".list-title-input");
        titleInput.value = cell.title;

        const ulElement = list.querySelector("ul");
        ulElement.innerHTML = "";

        cell.userIds.forEach(function (userId) {
            const user = uniqueJsData.find(u => u.fk_socpeople === userId);

            if (user) {
                const li = document.createElement("li");
                li.setAttribute("data-user-id", userId);
                li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;";

                // Utiliser le même affichage que dans createUserList
                li.innerHTML = `
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.lastname} ${user.firstname}">
                        ${user.lastname}<br>${user.firstname}
                    </div>
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.fonction || "Non définie"}">
                        ${user.fonction || "Non définie"}
                    </div>
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.contrat || "Non défini"}">
                        ${user.contrat || "Non défini"}
                    </div>
                    <div style="flex: 1; text-align: center; white-space: normal;" title="${user.habilitation || "Aucune habilitation"}">
                        ${user.habilitation || "Aucune habilitation"}
                    </div>
                    <div style="flex: 1; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${user.phone || "Non défini"}">
                        ${user.phone || "Non défini"}
                    </div>
                `;

                // Ajouter une fonction pour gérer le style en fonction du statut
                function updateListItemsStyle() {
                    const listStatus = parseInt(list.getAttribute("data-status"));
                    const items = list.querySelectorAll("li > div");
                    
                    items.forEach((item, index) => {
                        // Ne pas modifier le style du nom et du téléphone
                        if (index !== 0 && index !== 4) {
                            if (listStatus === 1 || listStatus === 2 || !isUserProjectManager) {
                                item.style.textAlign = "center";
                                item.style.whiteSpace = "normal";
                            }
                        }
                    });
                }

                // Observer les changements dattribut data-status sur la liste
                const observer = new MutationObserver(updateListItemsStyle);
                observer.observe(list, { attributes: true, attributeFilter: ["data-status"] });

                // Appliquer le style initial
                updateListItemsStyle();

                // Ajouter le bouton de suppression
                const removeSpan = document.createElement("span");
                removeSpan.textContent = "×";
                removeSpan.style = "color:red; cursor:pointer;";
                removeSpan.className = "remove-user";
                li.appendChild(removeSpan);

                ulElement.appendChild(li);

                // Ajouter une ligne vide entre les utilisateurs
                const emptyRow = document.createElement("li");
                emptyRow.style = "height: 10px;"; // Hauteur de la ligne vide
                ulElement.appendChild(emptyRow);
            } else {
                console.warn(`Utilisateur avec ID ${userId} introuvable dans uniqueJsData.`);
            }
        });

        attachUserRemoveListeners(list);
        const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
        if (columnElement) {
            columnElement.appendChild(list);
        }

        addedListTitles.add(cell.title);
    }
}
 else if (cell.type === "listeunique") {
    // Vérifie si une liste unique avec ce titre existe déjà dans le DOM
    const existingUniqueList = document.querySelector(`.user-list.unique-list[data-list-id="${cell.title}"]`);

    if (isUniqueListCreated && !existingUniqueList) {
      
        const list = createUniqueUserList(); // recréer la structure
        const titleInput = list.querySelector(".list-title-input");
        titleInput.value = cell.title;

        const ulElement = list.querySelector("ul");
        ulElement.innerHTML = ""; // Vider la liste

        // Remplir avec les utilisateurs de cellData
        cell.userIds.forEach(function(userId) {
            const user = uniqueJsData.find(u => u.fk_socpeople === userId);
            if (user) {
                const li = document.createElement("li");
                li.setAttribute("data-user-id", userId);
                li.innerHTML = `
                     <div style="flex: 1; text-align: center; padding-right: 10px;">${user.firstname} ${user.lastname}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.fonction || "Non définie"}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.contrat || "Non défini"}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.habilitation || "Aucune habilitation"}</div>
                    <div style="flex: 1; text-align: center;">${user.phone || "Non défini"}</div>
                `;
                ulElement.appendChild(li);
            } else {
                console.warn(`Utilisateur avec ID ${userId} introuvable dans uniqueJsData.`);
            }
        });

        if (columnsContainer) {
            columnsContainer.appendChild(list);
        } else {
            console.error("Le conteneur parent des colonnes a pas été trouvé.");
        }
        
    } 
}
    });
}



function createUniqueUserList() {
    const list = document.createElement("div");
    list.className = "user-list card unique-list";

    
    const lineBreak = document.createElement("br");
    list.appendChild(lineBreak);

    // Ajouter un ID unique
    const uniqueListId = `unique_${Date.now()}`;
    list.setAttribute("data-list-id", uniqueListId);

    // Créer un conteneur pour le titre avec le trait rouge
    const titleContainer = document.createElement("div");
    titleContainer.style = "text-align: center; padding-bottom: 10px; margin-bottom: 10px; color: #333; font-weight: bold;";

    const listTitleInput = document.createElement("input");
    listTitleInput.type = "text";
    listTitleInput.className = "list-title-input";
    listTitleInput.name = "listTitle";
    listTitleInput.placeholder = ""; // On enlève le placeholder par défaut
    listTitleInput.required = true;
    listTitleInput.style = "width: 80%; padding: 5px; text-align: center; color: #333;";
   // Fonction pour gérer laffichage du placeholder et le style
    function updatePlaceholder() {
        const card = list.closest(".card");
        const cardStatus = card ? parseInt(card.getAttribute("data-status")) : 0;
        
        if (listTitleInput.value === "" && cardStatus === 0) {
            listTitleInput.placeholder = "Titre de la liste";
            listTitleInput.style.textAlign = "center";
        } else {
            listTitleInput.placeholder = "";
            if (cardStatus === 1 || cardStatus === 2 || !isUserProjectManager) {
                listTitleInput.style.textAlign = "center";
            }
        }
    }

    // Ajouter les écouteurs dévénements
    listTitleInput.addEventListener("focus", updatePlaceholder);
    listTitleInput.addEventListener("blur", updatePlaceholder);
    listTitleInput.addEventListener("input", updatePlaceholder);

    // Observer les changements dattribut data-status sur la carte
    const observer = new MutationObserver(updatePlaceholder);
    const card = list.closest(".card");
    if (card) {
        observer.observe(card, { attributes: true, attributeFilter: ["data-status"] });
    }

    titleContainer.appendChild(listTitleInput); 

    // Créer une légende pour décrire les informations
    const legend = document.createElement("div");
    legend.className = "list-legend";
    legend.style = "display: flex; justify-content: space-between; padding: 10px; font-weight: bold; color: #333; margin-bottom: 10px; text-align: center;"; // Centrer la légende
    legend.innerHTML = `
        <div style="flex: 1; text-align: center;">Nom Prénom</div>
        <div style="flex: 1; text-align: center;">Fonction</div>
        <div style="flex: 1; text-align: center;">Contrat</div>
        <div style="flex: 1; text-align: center;">Habilitations</div>
        <div style="flex: 1; text-align: center;">Téléphone</div>
    `;

    const ulElement = document.createElement("ul");
    ulElement.style = "list-style: none; padding: 0; margin: 0;";



    // Remplir les utilisateurs de la liste depuis uniqueJsData
    uniqueJsData.forEach(user => {

        // Vérifier si lutilisateur nest pas Q3SE ou PCR
        if (user.libelle !== "ResponsableQ3SE" && user.libelle !== "PCRRéférent") {
            const li = document.createElement("li");
            li.setAttribute("data-user-id", user.fk_socpeople);
            li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;"; // Centrer les éléments de utilisateur

            // Créer une ligne avec les informations de utilisateur, réparties uniformément
            li.innerHTML = `
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.firstname} ${user.lastname}</div>
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.fonction || "Non définie"}</div>
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.contrat || "Non défini"}</div>
                <div style="flex: 1; text-align: center; padding-right: 10px;">${user.habilitation || "Aucune habilitation"}</div>
                <div style="flex: 1; text-align: center;">${user.phone || "Non défini"}</div>
            `;

            ulElement.appendChild(li);
        }
    });

    const listBody = document.createElement("div");
    listBody.className = "list-body";
    listBody.style = "text-align: left; color: #333; padding-left: 20px; padding-right: 20px; margin-bottom: 20px;"; // Ajouter des espaces à gauche et à droite
    listBody.appendChild(titleContainer);  // Ajouter le titre avec le trait rouge
    listBody.appendChild(legend);  // Ajouter la légende en haut de la liste
    listBody.appendChild(ulElement);

    list.appendChild(listBody);

    return list;
}


function deleteUniqueList(uniqueListId, list) {
      list.remove();
}


function updateCards() {
    var cardHeaders = {
        "ResponsableAffaire": null,
        "ResponsableQ3SE": null,
        "PCRReferent": null
    };

    // Vérifier si les données sont dans `cellData`
    cellData.forEach(function(cell) {
        if (cell.type === "cardprincipale" && cell.title) {
            switch (cell.title) {
                case "RA":
                    cardHeaders["ResponsableAffaire"] = cell;
                    break;
                case "Q3":
                    cardHeaders["ResponsableQ3SE"] = cell;
                    break;
                case "PCR":
                    cardHeaders["PCRReferent"] = cell;
                    break;
            }
        }
    });

    // Si les données ne sont pas dans `cellData`, les récupérer depuis `jsdata`
    jsdata.forEach(function(contact) {
        if (!cardHeaders["ResponsableAffaire"] && contact.fk_c_type_contact === "160") {
            cardHeaders["ResponsableAffaire"] = {
                type: "cardprincipale",
                title: "RA",
                firstname: contact.firstname,
                lastname: contact.lastname,
                phone: contact.phone || "N/A",
                userId: contact.fk_socpeople
            };
            saveData(); // Enregistrer dans la BDD
        }
        if (!cardHeaders["ResponsableQ3SE"] && contact.fk_c_type_contact === "1031142") {
            cardHeaders["ResponsableQ3SE"] = {
                type: "cardprincipale",
                title: "Q3",
                firstname: contact.firstname,
                lastname: contact.lastname,
                phone: contact.phone || "N/A",
                userId: contact.fk_socpeople
            };
            saveData(); // Enregistrer dans la BDD
        }
        if (!cardHeaders["PCRReferent"] && contact.fk_c_type_contact === "1031143") {
            cardHeaders["PCRReferent"] = {
                type: "cardprincipale",
                title: "PCR",
                firstname: contact.firstname,
                lastname: contact.lastname,
                phone: contact.phone || "N/A",
                userId: contact.fk_socpeople
            };
            saveData(); // Enregistrer dans la BDD
        }
    });

    // Ajouter des cartes vides si certains rôles sont absents dans `jsdata`
    if (!cardHeaders["ResponsableAffaire"]) {
        cardHeaders["ResponsableAffaire"] = {
            type: "cardprincipale",
            title: "RA",
            firstname: null,
            lastname: null,
            phone: null,
            userId: null
        };
        saveData(); // Enregistrer la carte vide dans la BDD
    }
    if (!cardHeaders["ResponsableQ3SE"]) {
        cardHeaders["ResponsableQ3SE"] = {
            type: "cardprincipale",
            title: "Q3",
            firstname: null,
            lastname: null,
            phone: null,
            userId: null
        };
        saveData(); // Enregistrer la carte vide dans la BDD
    }
    if (!cardHeaders["PCRReferent"]) {
        cardHeaders["PCRReferent"] = {
            type: "cardprincipale",
            title: "PCR",
            firstname: null,
            lastname: null,
            phone: null,
            userId: null
        };
        saveData(); // Enregistrer la carte vide dans la BDD
    }

    // Mettre à jour les cartes dans le DOM
    for (var role in cardHeaders) {
        if (cardHeaders.hasOwnProperty(role)) {
            var contact = cardHeaders[role];
            var selector = `.card[data-role="${role}"]`;
            var card = document.querySelector(selector);

            if (card) {
                var cardBody = card.querySelector(".card-body");

                if (cardBody) {
                if (contact && contact.type === "cardprincipale") {
                    // Vérifier si la carte est vide (pas de firstname, lastname ou phone)
                    if (!contact.firstname && !contact.lastname && !contact.phone) {
                        let message = "";
                        switch (role) {
                            case "ResponsableAffaire":
                                message = "Pas de Responsable Affaire";
                                break;
                            case "ResponsableQ3SE":
                                message = "Pas de Responsable Q3SE";
                                break;
                            case "PCRReferent":
                                message = "Pas de PCR Référent";
                                break;
                            default:
                                message = "Aucune donnée disponible";
                        }
                        cardBody.innerHTML = `
                            <p><strong>${role}</strong></p>
                            <p>${message}</p>
                            <p class="phone"> </p>
                            <p style="visibility: hidden;">Ligne vide</p> <!-- Ligne vide pour uniformiser la hauteur -->
                        `;
                    } else {
                        // Afficher les informations si elles sont présentes
                        cardBody.innerHTML = `
                            <p><strong>${role}</strong></p>
                            <p>${contact.firstname || ""} ${contact.lastname || ""}</p>
                            <p class="phone">Téléphone : ${contact.phone || ""}</p>
                            
                        `;
                    }
                } else {
                    // Si aucune donnée n\'est disponible, vider la carte
                    cardBody.innerHTML = `
                        <p><strong>${role}</strong></p>
                        <p>Aucune donnée disponible</p>
                    `;
                }
            

                    // Désactiver les champs pour empêcher la modification
                    card.querySelectorAll("input, select, button").forEach(function(field) {
                        field.disabled = true;
                    });
                }
            }
        }
    }
}



 function attachDeleteListener(card) {
    var deleteButton = card.querySelector(".delete-button");
    if (deleteButton) {
        deleteButton.addEventListener("click", function () {
            
            deleteCard(card);
        });
    }
}


//---------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * partie sous traitant
 * 
 */

    // Fonction pour récupérer les fournisseurs et contacts via Ajax
    function fetchSuppliersAndContacts() {
        $.ajax({
            url: "ajax/myobject.php",  
            type: "GET",
            data: { mode: "getSuppliersAndContacts" },
            dataType: "json",
            success: function(response) { 
                if (response.status === "success") {
                   
                    createSupplierDropdown(response.data);  
                } else {
                    console.error("Erreur dans la réponse:", response.message);  
                }
            },
            error: function(xhr, status, error) {
                console.error("Erreur Ajax :", error);  
            }
        });
    }

    // Appel de la fonction pour récupérer les données dès que la page est prête
    fetchSuppliersAndContacts();

function createSupplierDropdown() {
    const existingCard = document.querySelector(".cardsoustraitant");
    if (existingCard) {
        existingCard.remove();
    }
    const cardContainer = document.createElement("div");
    cardContainer.className = "cardsoustraitant";

    const cardTitle = document.createElement("h3");
    cardTitle.textContent = "Sous traitants";
    cardTitle.className = "card-header-soustraitant";
    cardContainer.appendChild(cardTitle);

    document.querySelector(".supplier-section").appendChild(cardContainer);

    // Conteneur pour regrouper la légende et les contacts
    const tableContainer = document.createElement("div");
    tableContainer.className = "table-container";
    cardContainer.appendChild(tableContainer);

    // Ajouter une légende pour le tableau
    const legendRow = document.createElement("div");
    legendRow.className = "legend-row";
    legendRow.style.cssText = "display: flex; text-align: center; padding: 5px 0; font-weight: bold;";

    const legendFields = ["Nom Prénom", "Entreprise", "Fonction", "Contrat", "Habilitations"];
    legendFields.forEach(field => {
        const fieldCell = document.createElement("div");
        fieldCell.style.flex = "1";
        fieldCell.textContent = field;
        legendRow.appendChild(fieldCell);
    });

    tableContainer.appendChild(legendRow);

    // Fonction pour mettre à jour le style des champs en fonction du status
    function updateFieldsStyle() {
        const status = parseInt(cardContainer.getAttribute("data-status") || "0");
        const inputs = tableContainer.querySelectorAll(".form-input");
        inputs.forEach(input => {
            if (status === 1 || status === 2 || !isUserProjectManager) {
                input.style.textAlign = "center";
                input.style.whiteSpace = "normal";
                input.style.width = "100%";
                input.style.padding = "0 5px";
                input.style.boxSizing = "border-box";
                input.disabled = true;
                input.style.backgroundColor = "#f5f5f5";
                input.style.cursor = "not-allowed";
            } else {
                input.style.textAlign = "left";
                input.style.whiteSpace = "nowrap";
                input.style.overflow = "hidden";
                input.style.textOverflow = "ellipsis";
                input.style.width = "100%";
                input.style.padding = "0 5px";
                input.style.boxSizing = "border-box";
                input.disabled = false;
                input.style.backgroundColor = "";
                input.style.cursor = "text";
            }
        });
    }

    // Observer les changements d'attribut data-status
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === "attributes" && mutation.attributeName === "data-status") {
                updateFieldsStyle();
            }
        });
    });
    observer.observe(cardContainer, { attributes: true, attributeFilter: ["data-status"] });

    // Vider la liste selectedContacts avant de la remplir
    selectedContacts = [];

    // **PRIORITÉ 1 : Récupérer d'abord les sous-traitants de la BDD (cellData)**
    const subcontractorData = cellData.find(cell => cell.type === "soustraitantlist");
   
    if (subcontractorData && subcontractorData.subcontractors && subcontractorData.subcontractors.length > 0) {
        console.log("✅ Affichage des sous-traitants depuis cellData:", subcontractorData.subcontractors);
        
        // Afficher UNIQUEMENT les sous-traitants de la BDD
        subcontractorData.subcontractors.forEach(contact => {
            console.log("📋 Contact traité:", contact);
            
            const dataRow = document.createElement("div");
            dataRow.className = "data-row";
            dataRow.setAttribute("data-contact-id", contact.fk_socpeople);
            dataRow.style.cssText = "display: flex; text-align: center; padding: 5px 0;";

            const fields = [
                `${contact.firstname || ''} ${contact.lastname || ''}`,
                `${contact.societe_nom || ''}`,
                `<input type="text" placeholder="Fonction" class="form-input" data-field="function" value="${contact.fonction || ''}">`,
                `<input type="text" placeholder="Contrat" class="form-input" data-field="contract" value="${contact.contrat || ''}">`,
                `<input type="text" placeholder="Habilitations" class="form-input" data-field="qualifications" value="${contact.habilitation || ''}">`
            ];

            fields.forEach(field => {
                const fieldCell = document.createElement("div");
                fieldCell.style.flex = "1";
                fieldCell.innerHTML = field;
                dataRow.appendChild(fieldCell);
            });

            tableContainer.appendChild(dataRow);

            // Ajouter le contact dans `selectedContacts`
            selectedContacts.push({
                contact_id: contact.fk_socpeople,
                firstname: contact.firstname || '',
                lastname: contact.lastname || '',
                supplier_name: contact.societe_nom || '',
                supplier_id: contact.fk_societe,
                function: contact.fonction || '',
                contract: contact.contrat || '',
                qualifications: contact.habilitation || ''
            });
        });
    } else {
        console.log("⚠️ Pas de sous-traitants dans cellData, utilisation de jsdatasoustraitants");
        
        // **PRIORITÉ 2 : S'il n'y a pas de données BDD, chercher les nouveaux contacts du projet**
        if (jsdatasoustraitants && Array.isArray(jsdatasoustraitants) && jsdatasoustraitants.length > 0) {
            jsdatasoustraitants.forEach(contact => {
                const dataRow = document.createElement("div");
                dataRow.className = "data-row";
                dataRow.setAttribute("data-contact-id", contact.fk_socpeople);
                dataRow.style.cssText = "display: flex; text-align: center; padding: 5px 0;";

                const fields = [
                    `${contact.firstname} ${contact.lastname}`,
                    `${contact.societe_nom}`,
                    `<input type="text" placeholder="Fonction" class="form-input" data-field="function" value="${contact.fonction || ""}">`,
                    `<input type="text" placeholder="Contrat" class="form-input" data-field="contract" value="${contact.contrat || ""}">`,
                    `<input type="text" placeholder="Habilitations" class="form-input" data-field="qualifications" value="${contact.habilitation || ""}">`
                ];

                fields.forEach(field => {
                    const fieldCell = document.createElement("div");
                    fieldCell.style.flex = "1";
                    fieldCell.innerHTML = field;
                    dataRow.appendChild(fieldCell);
                });

                tableContainer.appendChild(dataRow);

                // Ajouter le contact dans `selectedContacts`
                selectedContacts.push({
                    contact_id: contact.fk_socpeople,
                    firstname: contact.firstname,
                    lastname: contact.lastname,
                    supplier_name: contact.societe_nom,
                    supplier_id: contact.fk_societe,
                    function: contact.fonction,
                    contract: contact.contrat,
                    qualifications: contact.habilitation
                });
            });

            // Sauvegarder les données après affichage initial
            saveData();
        }
    }

    // Appliquer le style initial
    updateFieldsStyle();

    // Ajouter un écouteur pour sauvegarder les modifications
    document.querySelector(".table-container").addEventListener("blur", function (e) {
        if (e.target && e.target.classList.contains("form-input")) {
            const inputField = e.target;
            const dataRow = inputField.closest(".data-row");
            const contactId = dataRow.getAttribute("data-contact-id");

            // Trouver le contact correspondant dans `selectedContacts`
            const selectedContact = selectedContacts.find(c => c.contact_id == contactId);
            if (selectedContact) {
                const fieldName = inputField.getAttribute("data-field");
                selectedContact[fieldName] = inputField.value; // Mettre à jour la valeur
                console.log("💾 Sauvegarde champ:", fieldName, "=", inputField.value, "pour contact:", contactId);
            }

            saveData(); // Sauvegarder les modifications
        }
    }, true);
}

//---------------------------------------------------------------------------------------------------------------------------------------------------



function deleteCard(card) {
    card.remove(); // Supprime la carte du DOM
}

// Fonction pour créer une nouvelle carte vide
function createEmptyCard(column) {
    const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
    
    // Compter les cartes et listes existantes dans la colonne
    const itemsInColumn = columnElement.querySelectorAll(".card, .user-list").length;

    // Y commence à 2 si il y a déjà des éléments dans la colonne, sinon à 2
    const yPosition = itemsInColumn + 1;  // +1 car Y commence à 2
    const uniqueId = `${column}${yPosition}`;

    const card = document.createElement("div");
    card.className = "card";
    card.setAttribute("data-status", "0"); // Ajouter le statut initial

    card.innerHTML = `
        <div class="card-body" style="text-align: center; color: #333;">
            <form class="card-form" style="display: flex; flex-direction: column; align-items: center;">
                <input type="text" class="title-input" name="title" placeholder="" required
                    style="width: 80%; margin-bottom: 10px; padding: 5px; text-align: center; color: #333;">
                <select class="name-dropdown" name="name" required
                    style="width: 80%; margin-bottom: 10px; padding: 5px; text-align: center; color: #333;">
                    ${userOptions}
                </select>
                <div class="user-details" style="margin-top: 10px; width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <div class="habilitation-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; line-height: 1.4; width: 90%;"></div>
                    <div class="contrat-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; width: 90%;"></div>
                </div>
                <input type="hidden" class="card-id" value="${uniqueId}"> 
            </form>
            <button class="delete-button" style="margin-top: 10px;">Supprimer</button>
        </div>
    `;

    // Fonction pour gérer laffichage du placeholder
    function updatePlaceholder() {
        const titleInput = card.querySelector(".title-input");
        const cardStatus = parseInt(card.getAttribute("data-status"));
        
        if (titleInput.value === "" && cardStatus === 0) {
            titleInput.placeholder = "Titre de la carte";
        } else {
            titleInput.placeholder = "";
        }
    }

    // Ajouter les écouteurs dévénements
    const titleInput = card.querySelector(".title-input");
    titleInput.addEventListener("focus", updatePlaceholder);
    titleInput.addEventListener("blur", updatePlaceholder);
    titleInput.addEventListener("input", updatePlaceholder);

    // Observer les changements dattribut data-status sur la carte
    const observer = new MutationObserver(updatePlaceholder);
    observer.observe(card, { attributes: true, attributeFilter: ["data-status"] });

    // Ajouter lécouteur dévénement pour le changement utilisateur
    const nameDropdown = card.querySelector(".name-dropdown");
    nameDropdown.addEventListener("change", function() {
        const selectedUserId = this.value;
        // Chercher dans uniqueJsData au lieu de userjson pour les contacts du projet
        const selectedUser = uniqueJsData.find(user => user.fk_socpeople === selectedUserId);
        
        if (selectedUser) {
            const habilitationInfo = card.querySelector(".habilitation-info");
            const contratInfo = card.querySelector(".contrat-info");
            
            // Formater les habilitations avec des retours à la ligne
            const habilitations = selectedUser.habilitation || "Non spécifié";
            const formattedHabilitations = habilitations.split(",").map(h => h.trim()).join(",\n");
            
            habilitationInfo.innerHTML = `<strong>Habilitations:</strong><br>${formattedHabilitations}`;
            contratInfo.innerHTML = `<strong>Contrat:</strong><br>${selectedUser.contrat || "Non spécifié"}`;
            
            // Sauvegarder les informations dans le dataset de la carte
            card.dataset.habilitations = selectedUser.habilitation || "";
            card.dataset.contrat = selectedUser.contrat || "";
        }
    });

    card.querySelector(".card-form").addEventListener("submit", function (event) {
        event.preventDefault();
        const selectedUserId = card.querySelector(".name-dropdown").value;
        // Chercher dans uniqueJsData au lieu de userjson pour les contacts du projet
        const selectedUser = uniqueJsData.find(user => user.fk_socpeople === selectedUserId);
        const name = selectedUser ? `${selectedUser.firstname} ${selectedUser.lastname}` : "Non spécifié";

        // Formater les habilitations avec des retours à la ligne
        const habilitations = selectedUser ? (selectedUser.habilitation || "Non spécifié") : "Non spécifié";
        const formattedHabilitations = habilitations.split(",").map(h => h.trim()).join(",\n");

        card.innerHTML = `
            <div class="card-body" style="text-align: center; color: #333;">
                <input type="text" class="title-input" name="title" value="${card.querySelector(".title-input").value}" required
                    style="width: 80%; margin-bottom: 10px; padding: 5px; text-align: center; color: #333;">
                <p><strong>${name}</strong></p>
                <div class="user-details" style="margin-top: 10px; width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <div class="habilitation-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; line-height: 1.4; width: 90%;">
                        <strong>Habilitations:</strong><br>${formattedHabilitations}
                    </div>
                    <div class="contrat-info" style="margin-bottom: 5px; word-wrap: break-word; white-space: normal; text-align: center; padding: 5px; font-size: 0.9em; width: 90%;">
                        <strong>Contrat:</strong><br>${selectedUser ? (selectedUser.contrat || "Non spécifié") : "Non spécifié"}
                    </div>
                </div>
                <input type="hidden" class="card-id" value="${uniqueId}">
            </div>
            <button class="delete-button" style="margin-top: 10px;">Supprimer</button>
        `;

        attachDeleteListener(card);
    });

    attachDeleteListener(card);
    return card;
}


function createUserList(column) {
    const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
    
    // Compter les cartes et listes existantes dans la colonne
    const itemsInColumn = columnElement.querySelectorAll(".card, .user-list").length;

    // Y commence à 2 si il y a déjà des éléments dans la colonne, sinon à 2
    const yPosition = itemsInColumn + 1;
    const listId = `${column}${yPosition}`;

    const list = document.createElement("div");
    list.className = "user-list card";
    list.setAttribute("data-status", "0"); // Ajouter le statut initial

    // Ajouter un saut de ligne avant le titre
    const lineBreak = document.createElement("br");
    list.appendChild(lineBreak);

    // Ajouter un ID unique
    list.setAttribute("data-list-id", listId);

    // Créer un conteneur pour le titre avec le trait rouge
    const titleContainer = document.createElement("div");
    titleContainer.style = "text-align: center; padding-bottom: 10px; margin-bottom: 10px; color: #333; font-weight: bold;";

    const listTitleInput = document.createElement("input");
    listTitleInput.type = "text";
    listTitleInput.className = "list-title-input";
    listTitleInput.name = "listTitle";
    listTitleInput.placeholder = ""; // On enlève le placeholder par défaut
    listTitleInput.required = true;
    listTitleInput.style = "width: 80%; padding: 5px; text-align: center; color: #333;";

    // Fonction pour gérer laffichage du placeholder
    function updatePlaceholder() {
        const listStatus = parseInt(list.getAttribute("data-status"));
        
        if (listTitleInput.value === "" && listStatus === 0) {
            listTitleInput.placeholder = "Titre de la liste";
            listTitleInput.style.textAlign = "center";
        } else {
            listTitleInput.placeholder = "";
            if (listStatus === 1 || listStatus === 2 || !isUserProjectManager) {
                listTitleInput.style.textAlign = "center";
            }
        }
    }

    // Ajouter les écouteurs dévénements
    listTitleInput.addEventListener("focus", updatePlaceholder);
    listTitleInput.addEventListener("blur", updatePlaceholder);
    listTitleInput.addEventListener("input", updatePlaceholder);

    // Observer les changements dattribut data-status sur la liste
    const observer = new MutationObserver(updatePlaceholder);
    observer.observe(list, { attributes: true, attributeFilter: ["data-status"] });

    titleContainer.appendChild(listTitleInput);

    // Créer une légende pour décrire les informations
    const legend = document.createElement("div");
    legend.className = "list-legend";
    legend.style = "display: flex; justify-content: space-between; padding: 10px; font-weight: bold; color: #333; margin-bottom: 10px; text-align: center;";
    legend.innerHTML = `
        <div style="flex: 1; text-align: center;">Nom</div>
        <div style="flex: 1; text-align: center;">Fonction</div>
        <div style="flex: 1; text-align: center;">Contrat</div>
        <div style="flex: 1; text-align: center;">Habil</div>
        <div style="flex: 1; text-align: center;">Tél</div>
    `;

    const ulElement = document.createElement("ul");
    ulElement.style = "list-style: none; padding: 0; margin: 0;";

    // Remplir les utilisateurs de la liste depuis uniqueJsData
    uniqueJsData.forEach(user => {
        // Vérifier si lutilisateur nest pas Q3SE ou PCR
        if (user.libelle !== "ResponsableQ3SE" && user.libelle !== "PCRRéférent") {
            const li = document.createElement("li");
            li.setAttribute("data-user-id", user.fk_socpeople);
            li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;";

            // Créer une ligne avec les informations de lutilisateur
            li.innerHTML = `
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.lastname} ${user.firstname}">
                    ${user.lastname}<br>${user.firstname}
                </div>
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.fonction || "Non définie"}">
                    ${user.fonction || "Non définie"}
                </div>
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.contrat || "Non défini"}">
                    ${user.contrat || "Non défini"}
                </div>
                <div style="flex: 1; text-align: center; white-space: normal;" title="${user.habilitation || "Aucune habilitation"}">
                    ${user.habilitation || "Aucune habilitation"}
                </div>
                <div style="flex: 1; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${user.phone || "Non défini"}">
                    ${user.phone || "Non défini"}
                </div>
            `;

            // Ajouter une fonction pour gérer le style en fonction du statut
            function updateListItemsStyle() {
                const listStatus = parseInt(list.getAttribute("data-status"));
                const items = list.querySelectorAll("li > div");
                
                items.forEach((item, index) => {
                    // Ne pas modifier le style du nom et du téléphone
                    if (index !== 0 && index !== 4) {
                        if (listStatus === 1 || listStatus === 2 || !isUserProjectManager) {
                            item.style.textAlign = "center";
                            item.style.whiteSpace = "normal";
                        } else {
                            item.style.textAlign = "left";
                            item.style.whiteSpace = "nowrap";
                            item.style.overflow = "hidden";
                            item.style.textOverflow = "ellipsis";
                        }
                    }
                });
            }

            // Observer les changements dattribut data-status sur la liste
            const observer = new MutationObserver(updateListItemsStyle);
            observer.observe(list, { attributes: true, attributeFilter: ["data-status"] });

            // Appliquer le style initial
            updateListItemsStyle();


            ulElement.appendChild(li);

            // Ajouter une ligne vide entre les utilisateurs
            const emptyRow = document.createElement("li");
            emptyRow.style = "height: 10px;"; // Hauteur de la ligne vide
            ulElement.appendChild(emptyRow);
        }
    });

    const listBody = document.createElement("div");
    listBody.className = "list-body";
    listBody.style = "text-align: left; color: #333; padding-left: 20px; padding-right: 20px; margin-bottom: 20px;"; // Ajouter des espaces à gauche et à droite
    listBody.appendChild(titleContainer); // Ajouter le titre avec le trait rouge
    listBody.appendChild(legend); // Ajouter la légende en haut de la liste
    listBody.appendChild(ulElement);

    list.appendChild(listBody);

    const lineBreakAfter = document.createElement("br");
    list.appendChild(lineBreakAfter);

    // Attacher les écouteurs de suppression utilisateur
    attachUserRemoveListeners(list);

    // Ajouter un bouton de suppression pour la liste
    const deleteButton = document.createElement("button");
    deleteButton.textContent = "Supprimer";
    deleteButton.className = "delete-list-button btn btn-danger";
    deleteButton.style = "margin: 10px auto; display: block;"; // Centrer le bouton horizontalement
    deleteButton.addEventListener("click", () => {
        list.remove();
        saveData(); // Sauvegarder les modifications après suppression
    });

    list.appendChild(deleteButton);

    return list;
}



// Fonction pour attacher les écouteurs de suppression aux utilisateurs
function attachUserRemoveListeners(list) {
    const removeButtons = list.querySelectorAll(".remove-user");

    removeButtons.forEach(removeButton => {
        removeButton.addEventListener("click", function() {
            const li = this.parentElement;
            li.remove(); // Retirer utilisateur de la liste
            saveData();  // Sauvegarder les données après suppression
        });
    });
}


function updateUserIdsForCard(cell, newUserId) {
    if (cell.userId && cell.userId.length > 0) {
        // Mettre à jour utilisateur avec le dernier ID
        cell.userId = [newUserId]; // On garde uniquement le dernier utilisateur ajouté
    } else {
        // Si aucune donnée dans userId, on linitialise avec ID du nouvel utilisateur
        cell.userId = [newUserId];
    }
   
}

function addItemToColumn(column, type) {
  
    var columnElement = document.querySelector(`.card-column:nth-child(${column})`);
    
    if (columnElement) {
       
        var newItem = null;

        if (type === "card") {
            newItem = createEmptyCard(column);
          
        } else if (type === "list") {
            newItem = createUserList(column);
         
        }

        if (newItem && newItem instanceof Node) {
            columnElement.appendChild(newItem);
            attachDeleteListener(newItem);
            attachEventListeners();
            saveData();
           
        } else {
            console.error(`Échec de création du ${type}`);
        }
    } else {
        console.error(`Colonne ${column} non trouvée`);
    }
}

// Remplacer complètement la gestion des clics dropdown par ceci :
document.addEventListener("click", function(event) {

    // Vérifier si c'est un bouton dans .dropdown-content
    if (event.target.closest(".dropdown-content")) {
   
        
        var button = event.target;
        var column = button.getAttribute("data-column");
        var type = button.getAttribute("data-type");
        
     
        
        if (column && type) {
            event.preventDefault();
            event.stopPropagation();
            addItemToColumn(parseInt(column), type);
        }
    }
});

function attachEventListeners() {
    // Attacher des écouteurs sur les changements des champs de titre
    document.querySelectorAll(".title-input, .list-title-input").forEach(input => {
        if (!input.dataset.listenerAttached) {
            input.addEventListener("blur", saveData);
            input.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur la suppression des cartes
    document.querySelectorAll(".card .delete-button").forEach(button => {
        if (!button.dataset.listenerAttached) {
            button.addEventListener("click", function() {
                const card = button.closest(".card");
                if (card && !card.classList.contains("user-list")) {
                    card.remove();
                    saveData();
                }
            });
            button.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur la suppression des listes
    document.querySelectorAll(".delete-list-button").forEach(button => {
        if (!button.dataset.listenerAttached) {
            button.addEventListener("click", function() {
                const list = button.closest(".user-list");
                if (list && !list.classList.contains("unique-list")) {
                    list.remove();
                    saveData();
                }
            });
            button.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur la suppression des éléments des listes
    document.querySelectorAll(".user-list .remove-user").forEach(removeButton => {
        if (!removeButton.dataset.listenerAttached) {
            removeButton.addEventListener("click", function() {
                const li = this.closest("li");
                if (li) {
                    li.remove();
                    saveData();
                }
            });
            removeButton.dataset.listenerAttached = true;
        }
    });

    // Attacher des écouteurs sur le changement utilisateur dans les cartes
    document.querySelectorAll(".card .name-dropdown").forEach(dropdown => {
        if (!dropdown.dataset.listenerAttached) {
            dropdown.addEventListener("change", function() {
                const selectedUser = this.value;
                const card = this.closest(".card");

                if (card) {
                    card.dataset.userId = selectedUser;
                   
                    saveData();
                }
            });
            dropdown.dataset.listenerAttached = true;
        }
    });
}

    document.querySelectorAll(".unique-list .list-title-input").forEach(input => {
        if (!input.dataset.listenerAttached) {
            input.addEventListener("blur", saveData);
            input.dataset.listenerAttached = true;
        }
    });

    document.querySelectorAll(".unique-list .list-title-input").forEach(input => {
        if (!input.dataset.listenerAttached) {
            input.addEventListener("blur", function () {
                saveData(); 
            });
            input.dataset.listenerAttached = true;
        }
    });


// Appeler `attachEventListeners()` immédiatement après la création ou la modification des cartes.


attachEventListeners();
function saveData() { 
    let cardsData = [];
    
    // Parcours de toutes les cartes pour récupérer les informations
    document.querySelectorAll(".card-column .card").forEach(function (card) {
        let titleInput = card.querySelector(".title-input");
        let nameDropdown = card.querySelector(".name-dropdown");

        // Récupération des coordonnées X et Y
        let x = Array.from(card.closest(".card-column").parentNode.children).indexOf(card.closest(".card-column")) + 1; // Récupérer X
        let y = Array.from(card.closest(".card-column").querySelectorAll(".card")).indexOf(card) + 1; // Récupérer Y

        if (titleInput && nameDropdown) {
            let title = titleInput.value;
            let userId = nameDropdown.value || card.dataset.userId || "undefined";
            let cardId = card.querySelector(".card-id").value; 

            // Récupérer les habilitations et le contrat depuis le dataset de la carte
            let habilitations = card.dataset.habilitations || "";
            let contrat = card.dataset.contrat || "";

            let cardCoordinates = {
                title: title,
                userId: userId, 
                type: card.classList.contains("user-list") ? "list" : "card",
                otid: otId, 
                id: cardId, 
                x: x || 0,
                y: y || 0,
                habilitations: habilitations,
                contrat: contrat
            };

            cardsData.push(cardCoordinates);
        }
    });


    const contactsData = selectedContacts.map(contact => {
        
        return {
            soc_people: contact.contact_id,
            firstname: contact.firstname,
            lastname: contact.lastname,
            supplier_name: contact.supplier_name,
            supplier_id: contact.supplier_id,
            fonction: contact.function,
            contrat: contact.contract,
            habilitation: contact.qualifications
        };
    });


    // Ajouter ou mettre à jour les contacts sélectionnés dans cardsData
    if (contactsData.length > 0) {
        let existingSubcontractorList = cardsData.find(item => item.type === "listesoustraitant");

        if (!existingSubcontractorList) {
            //console.log("Aucune liste de sous-traitants existante, création dune nouvelle liste.");
            existingSubcontractorList = {
                type: "listesoustraitant",
                soustraitants: []
            };
            cardsData.push(existingSubcontractorList);
        }

        //console.log("Sous-traitants existants avant mise à jour :", existingSubcontractorList.soustraitants);

        // Parcourir les sous-traitants récupérés de la base de données
        contactsData.forEach(contact => {
            const existingContact = existingSubcontractorList.soustraitants.find(
                c => c.soc_people == contact.soc_people
            );

            if (existingContact) {
                
                // Mettre à jour les informations du sous-traitant existant
            // Object.assign(existingContact, contact);
            } else {
                
                // Ajouter un nouveau sous-traitant
                existingSubcontractorList.soustraitants.push(contact);
            }
        });

        // Supprimer les doublons dans la liste des sous-traitants
        existingSubcontractorList.soustraitants = existingSubcontractorList.soustraitants.filter((contact, index, self) =>
            index === self.findIndex(c => c.soc_people === contact.soc_people)
        );

        //console.log("Sous-traitants après suppression des doublons :", existingSubcontractorList.soustraitants);
    }

    // Parcours de toutes les listes pour récupérer les informations
    document.querySelectorAll(".card-column .user-list").forEach(function (list) {
        let titleInput = list.querySelector(".list-title-input");
        let listId = list.getAttribute("data-list-id"); 

        // Récupération des coordonnées X et Y
        let x = Array.from(list.closest(".card-column").parentNode.children).indexOf(list.closest(".card-column")) + 1; // Récupérer X
        let y = Array.from(list.closest(".card-column").querySelectorAll(".card")).indexOf(list) + 1; // Récupérer Y

        if (titleInput) {
            let title = titleInput.value;

            // Remplacer la collecte des IDs utilisateurs pour ne pas ajouter de doublons
            let userIds = Array.from(list.querySelectorAll("li[data-user-id]")).map(function (li) {
                return li.getAttribute("data-user-id");
            }).filter((id, index, self) => self.indexOf(id) === index); // Supprimer les doublons si nécessaire

            let listCoordinates = {
                title: title,
                userIds: userIds, // Stocker des IDs uniques des utilisateurs
                type: "list",
                otid: otId, 
                id: listId, 
                x: x || 0,
                y: y || 0
            };

            cardsData.push(listCoordinates);
        }
    });

    document.querySelectorAll(".user-list.unique-list").forEach(function (uniqueList) {
        let titleInput = uniqueList.querySelector(".list-title-input");
        let uniqueListId = uniqueList.getAttribute("data-list-id");

        if (titleInput) {
            let title = titleInput.value || "Organigramme - Liste des utilisateurs"; // Titre par défaut si vide
            let userIds = Array.from(uniqueList.querySelectorAll("li[data-user-id]")).map(function (li) {
                return li.getAttribute("data-user-id");
            }).filter((id, index, self) => self.indexOf(id) === index); // Supprimer les doublons si nécessaire

            let uniqueListCoordinates = {
                title: title,
                userIds: userIds,
                type: "listeunique",
                otid: otId,
                id: uniqueListId || "unique_list_1"
            };

            cardsData.push(uniqueListCoordinates); // Ajouter la liste unique à cardsData
        }
    });

// Ajouter les informations des rôles principaux (ResponsableAffaire, ResponsableQ3SE, PCRReferent)
    const roleMapping = {
        "160": "RA", // ResponsableAffaire
        "1031142": "Q3", // ResponsableQ3SE
        "1031143": "PCR" // PCRReferent
    };

    // Ajouter les cartes principales depuis jsdata
    jsdata.forEach(function(contact) {
        if (roleMapping[contact.fk_c_type_contact]) {
            cardsData.push({
                type: "cardprincipale",
                role: roleMapping[contact.fk_c_type_contact],
                userId: contact.fk_socpeople,
                userName: `${contact.firstname} ${contact.lastname}`
            });
        }
    });

    // Ajouter des cartes principales vides si elles ne sont pas présentes
    Object.keys(roleMapping).forEach(function(key) {
        const role = roleMapping[key];
        const existingCard = cardsData.find(card => card.type === "cardprincipale" && card.role === role);

        if (!existingCard) {
            cardsData.push({
                type: "cardprincipale",
                role: role,
                userId: null,
                userName: null
            });
        }
    });

    
    let payload = {
        otid: otId,
        cardsData: cardsData.length > 0 ? cardsData : null, // Mettre null si vide
        selectedContacts: contactsData.length > 0 ? contactsData : null // Inclure les contacts sélectionnés
    };
    
 

    fetch("ajax/save_card.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
    })
    .then(response => response.text()) // Assurez-vous que la réponse est en JSON ou texte
    .then(data => {
    
    
        onSaveSuccess();
    })
    .catch(error => {
        
    });
    // Callback après succès
    function onSaveSuccess() {
        
        // Par exemple : Afficher un message, masquer un loader, ou actualiser
    }
}
    updateCards(); 
});
