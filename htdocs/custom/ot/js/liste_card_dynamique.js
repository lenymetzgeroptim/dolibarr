/**
 * Gestionnaire d'organigramme dynamique pour les Ordres de Travail (OT)
 * Ce script gère l'affichage et l'interaction avec les cartes et listes d'utilisateurs
 * dans l'organigramme de travail d'un Ordre de Travail (OT)
 */
document.addEventListener("DOMContentLoaded", function() {
    // Initialisation des variables globales depuis les données PHP
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
    
    // Filtrage des données pour séparer les sous-traitants des utilisateurs internes
    let jsdatasoustraitants = users.filter(user => user.source === "external" && user.fk_c_type_contact === "1031141");
    let jsdataFiltered = users.filter(user => user.source !== "external"); 
    jsdata = jsdataFiltered;

    // Logs de débogage
    console.log(cellData);
    console.log(userjson); 
    console.log("status", );
    console.log("les autres", jsdataFiltered);
    console.log("soustraitants", jsdatasoustraitants);

    // Initialisation du conteneur des colonnes
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

    // Filtrage des utilisateurs uniques par ID
    const uniqueJsData = jsdata.filter((value, index, self) => 
        index === self.findIndex((t) => (
            t.fk_socpeople === value.fk_socpeople
        ))
    );

    /**
     * Gestion des permissions et désactivation de l'interface en mode lecture seule
     * Désactive tous les éléments interactifs si l'OT est validé/archivé ou si l'utilisateur n'a pas les droits
     */
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

    /**
     * Affiche la liste unique des utilisateurs du projet
     * Cette fonction vérifie d'abord si des données existent en base de données,
     * sinon elle crée une nouvelle liste par défaut avec tous les utilisateurs du projet
     */
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
                console.log("Affichage de la liste unique depuis la BDD.");
                cellData.forEach(cell => {
                    if (cell.type === "listeunique") {
                        const listVersion = parseInt(cell.version || 1);
                        const domVersion = existingUniqueList ? parseInt(existingUniqueList.dataset.version || 0) : 0;

                        // Comparer les versions pour déterminer si une mise à jour est nécessaire
                        if (listVersion > domVersion) {
                            console.log(`Mise à jour de la liste unique (version ${listVersion}).`);
                            const list = createUniqueUserList();

                            // Remplir le titre de la liste
                            const titleInput = list.querySelector(".list-title-input");
                            titleInput.value = cell.title;

                            // Vérifier si `userDetails` est défini et est un tableau
                            if (Array.isArray(cell.userDetails)) {
                                // Remplir les utilisateurs de la liste depuis `cellData`
                                const ulElement = list.querySelector("ul");
                                ulElement.innerHTML = "";
                                cell.userDetails.forEach(user => {
                                    console.log("User data:", user);
                                    // Vérifier si l'utilisateur n'est pas Q3SE ou PCR
                                    if (user.type !== "ResponsableQ3SE" && user.type !== "PCRRéférent") {
                                        const li = document.createElement("li");
                                        li.setAttribute("data-user-id", user.userId);
                                        li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;";

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
                            } else {
                                console.warn(`userDetails est manquant ou n'est pas un tableau pour la cellule avec le titre : ${cell.title}`);
                            }

                            // Ajouter la version au DOM
                            list.dataset.version = listVersion;

                            attachUserRemoveListeners(list);

                            // Ajouter la liste au conteneur
                            columnsContainer.appendChild(list);
                        } else {
                            console.log("La version de la liste unique est déjà à jour.");
                        }
                    }
                });
            } else {
                console.log("Aucune liste unique trouvée dans la BDD, création d'une nouvelle liste.");
                const uniqueList = createUniqueUserList();
                uniqueList.style.marginTop = "20px";
                uniqueList.dataset.version = 1;
                columnsContainer.appendChild(uniqueList);

                // Sauvegarder la nouvelle liste dans la BDD
                saveData();
            }
        } else {
            console.log("Aucune donnée dans `cellData`, création d'une nouvelle liste par défaut.");
            const uniqueList = createUniqueUserList();
            uniqueList.style.marginTop = "20px";
            uniqueList.dataset.version = 1;
            columnsContainer.appendChild(uniqueList);

            // Sauvegarder la nouvelle liste dans la BDD
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

    // Générer les options de uniqueJsData
    let userOptions = `<option value="" disabled selected>Sélectionner un utilisateur</option>`;
    uniqueJsData.forEach(function(user) {
        userOptions += `<option value="${user.fk_socpeople}">${user.firstname} ${user.lastname}</option>`;
    });

    /**
     * Chargement et affichage des données existantes depuis la base de données
     * Parcourt cellData pour restaurer les cartes et listes précédemment sauvegardées
     */
    if (typeof cellData !== "undefined" && cellData.length > 0) {
        const addedCardTitles = new Set();
        const addedListTitles = new Set();

        cellData.forEach(function(cell) {
            let column = cell.x;

            // Traitement des cartes individuelles
            if (cell.type === "card") {                                          
                console.log(`Chargement de la carte ${cell.title} avec userId:`, cell.userId);

                if (!addedCardTitles.has(cell.title)) {
                    const card = createEmptyCard(column);
                    const titleInput = card.querySelector(".title-input");
                    titleInput.value = cell.title;

                    const nameDropdown = card.querySelector(".name-dropdown");
                    nameDropdown.innerHTML = alluser; // Utiliser alluser au lieu de userOptions

                    if (cell.userId) {  
                        const userId = cell.userId;  
                        console.log("User ID détecté :", userId);

                        // Chercher dans userjson au lieu de uniqueJsData
                        const user = userjson.find(u => u.rowid == userId);
                        if (user) {
                            nameDropdown.value = userId;
                            
                            // Déclencher l'événement change pour mettre à jour les informations
                            const changeEvent = new Event('change');
                            nameDropdown.dispatchEvent(changeEvent);
                            
                            // Afficher les habilitations et le contrat
                            const habilitationInfo = card.querySelector(".habilitation-info");
                            const contratInfo = card.querySelector(".contrat-info");
                            
                            if (habilitationInfo && contratInfo) {
                                 const habilitations = cell.habilitations || user.habilitations || "Non spécifié";
                                const formattedHabilitations = habilitations.split(",").map(h => h.trim()).join(",\n");
                                
                                habilitationInfo.innerHTML = `<strong>Habilitations:</strong><br>${formattedHabilitations}`;
                                contratInfo.innerHTML = `<strong>Contrat:</strong><br>${cell.contrat || user.contrat || "Non spécifié"}`;
                                
                                // Sauvegarder dans le dataset
                                card.dataset.habilitations = cell.habilitations || user.habilitations || "";
                                card.dataset.contrat = cell.contrat || user.contrat || "";
                            }
                        } else {
                            console.warn("⚠️ User introuvable avec ID :", userId);
                        }
                    }

                    const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
                    if (columnElement) {
                        columnElement.appendChild(card);
                    }

                    addedCardTitles.add(cell.title);
                }
            }
            // Traitement des listes d'utilisateurs
            else if (cell.type === "list") {
                // ...existing code...
            }
            // Traitement des listes uniques
            else if (cell.type === "listeunique") {
                // ...existing code...
            }
        });
    }

    /**
     * Crée une liste unique d'utilisateurs avec toutes les informations détaillées
     * Cette liste contient tous les utilisateurs du projet (sauf Q3SE et PCR)
     * avec leurs informations complètes (nom, fonction, contrat, habilitations, téléphone)
     * 
     * @returns {HTMLElement} L'élément DOM de la liste unique créée
     */
    function createUniqueUserList() {
        const list = document.createElement("div");
        list.className = "user-list card unique-list";

        const lineBreak = document.createElement("br");
        list.appendChild(lineBreak);

        // Ajouter un ID unique basé sur le timestamp
        const uniqueListId = `unique_${Date.now()}`;
        list.setAttribute("data-list-id", uniqueListId);

        // Créer un conteneur pour le titre
        const titleContainer = document.createElement("div");
        titleContainer.style = "text-align: center; padding-bottom: 10px; margin-bottom: 10px; color: #333; font-weight: bold;";

        const listTitleInput = document.createElement("input");
        listTitleInput.type = "text";
        listTitleInput.className = "list-title-input";
        listTitleInput.name = "listTitle";
        listTitleInput.placeholder = "";
        listTitleInput.required = true;
        listTitleInput.style = "width: 80%; padding: 5px; text-align: center; color: #333;";
        
        console.log("Status de la carte :", status);

        /**
         * Gère l'affichage du placeholder en fonction du statut de la carte
         */
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

        // Attacher les écouteurs d'événements pour la gestion du placeholder
        listTitleInput.addEventListener("focus", updatePlaceholder);
        listTitleInput.addEventListener("blur", updatePlaceholder);
        listTitleInput.addEventListener("input", updatePlaceholder);

        // Observer les changements d'attribut data-status sur la carte
        const observer = new MutationObserver(updatePlaceholder);
        const card = list.closest(".card");
        if (card) {
            observer.observe(card, { attributes: true, attributeFilter: ["data-status"] });
        }

        titleContainer.appendChild(listTitleInput); 

        // Créer une légende pour décrire les colonnes d'informations
        const legend = document.createElement("div");
        legend.className = "list-legend";
        legend.style = "display: flex; justify-content: space-between; padding: 10px; font-weight: bold; color: #333; margin-bottom: 10px; text-align: center;";
        legend.innerHTML = `
            <div style="flex: 1; text-align: center;">Nom Prénom</div>
            <div style="flex: 1; text-align: center;">Fonction</div>
            <div style="flex: 1; text-align: center;">Contrat</div>
            <div style="flex: 1; text-align: center;">Habilitations</div>
            <div style="flex: 1; text-align: center;">Téléphone</div>
        `;

        const ulElement = document.createElement("ul");
        ulElement.style = "list-style: none; padding: 0; margin: 0;";

        // Afficher la structure des données pour le débogage
        console.log("uniqueJsData structure:", uniqueJsData);

        // Remplir les utilisateurs de la liste depuis uniqueJsData
        uniqueJsData.forEach(user => {
            console.log("User in uniqueJsData:", user);
            // Vérifier si l'utilisateur n'est pas Q3SE ou PCR
            if (user.libelle !== "ResponsableQ3SE" && user.libelle !== "PCRRéférent") {
                const li = document.createElement("li");
                li.setAttribute("data-user-id", user.fk_socpeople);
                li.style = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; text-align: center;";

                // Créer une ligne avec les informations de l'utilisateur, réparties uniformément
                li.innerHTML = `
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.firstname} ${user.lastname}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.fonction || "Non définie"}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.contrat || "Non défini"}</div>
                    <div style="flex: 1; text-align: center; padding-right: 10px;">${user.habilitation || "Aucune habilitation"}</div>
                    <div style="flex: 1; text-align: center;">${user.phone || "Non défini"}</div>
                `;

                // Ajouter le bouton de suppression
                const removeSpan = document.createElement("span");
                removeSpan.textContent = "×";
                removeSpan.style = "color:red; cursor:pointer;";
                removeSpan.className = "remove-user";
                li.appendChild(removeSpan);

                ulElement.appendChild(li);
            }
        });

        // Assembler la structure complète de la liste
        const listBody = document.createElement("div");
        listBody.className = "list-body";
        listBody.style = "text-align: left; color: #333; padding-left: 20px; padding-right: 20px; margin-bottom: 20px;";
        listBody.appendChild(titleContainer);
        listBody.appendChild(legend);
        listBody.appendChild(ulElement);

        list.appendChild(listBody);

        // Attacher les écouteurs de suppression utilisateur
        attachUserRemoveListeners(list);

        return list;
    }

    /**
     * Supprime une liste unique spécifique du DOM
     * 
     * @param {string} uniqueListId - L'identifiant unique de la liste à supprimer
     * @param {HTMLElement} list - L'élément DOM de la liste à supprimer
     */
    function deleteUniqueList(uniqueListId, list) {
        list.remove();
    }

    /**
     * Met à jour les cartes principales (ResponsableAffaire, ResponsableQ3SE, PCRReferent)
     * avec les informations des contacts du projet
     * Récupère les données depuis cellData ou jsdata et met à jour l'affichage
     */
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
            saveData();
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
            saveData();
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
            saveData();
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
                                    <p style="visibility: hidden;">Ligne vide</p>
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
                            // Si aucune donnée n'est disponible, vider la carte
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

    /**
     * Attache un écouteur d'événement de suppression à une carte
     * 
     * @param {HTMLElement} card - L'élément carte auquel attacher l'écouteur
     */
    function attachDeleteListener(card) {
        var deleteButton = card.querySelector(".delete-button");
        if (deleteButton) {
            deleteButton.addEventListener("click", function () {
                deleteCard(card);
            });
        }
    }

    /**
     * Récupère les fournisseurs et contacts via une requête Ajax
     * Effectue une requête vers le serveur pour récupérer la liste des fournisseurs
     * et de leurs contacts depuis la base de données
     */
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

    /**
     * Crée l'interface de gestion des sous-traitants
     * Génère une table avec les sous-traitants du projet et leurs informations modifiables
     * Inclut la gestion des permissions selon le statut de l'OT
     */
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

        /**
         * Met à jour le style des champs en fonction du statut de l'OT
         * Désactive les champs si l'OT est validé/archivé ou si l'utilisateur n'a pas les droits
         */
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

        // Chargement et affichage des sous-traitants
        const subcontractorData = cellData.find(cell => cell.type === "soustraitantlist" || cell.type === "listesoustraitant");
       
        console.log("Données sous-traitants cellData:", subcontractorData);
        console.log("Données jsdatasoustraitants:", jsdatasoustraitants);
        
        if (subcontractorData && subcontractorData.subcontractors && subcontractorData.subcontractors.length > 0) {
            // Afficher les sous-traitants sauvegardés depuis cellData
            console.log("Affichage depuis cellData");
            subcontractorData.subcontractors.forEach(contact => {
                const dataRow = document.createElement("div");
                dataRow.className = "data-row";
                dataRow.setAttribute("data-contact-id", contact.soc_people);
                dataRow.style.cssText = "display: flex; text-align: center; padding: 5px 0;";

                const fields = [
                    `${contact.firstname} ${contact.lastname}`,
                    `${contact.supplier_name}`,
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

                // Ajouter à selectedContacts s'il n'y est pas déjà
                if (!selectedContacts.find(c => c.contact_id == contact.soc_people)) {
                    selectedContacts.push({
                        contact_id: contact.soc_people,
                        firstname: contact.firstname,
                        lastname: contact.lastname,
                        supplier_name: contact.supplier_name,
                        supplier_id: contact.supplier_id,
                        function: contact.fonction,
                        contract: contact.contrat,
                        qualifications: contact.habilitation
                    });
                }
            });
        } else if (jsdatasoustraitants && Array.isArray(jsdatasoustraitants) && jsdatasoustraitants.length > 0) {
            // Afficher les sous-traitants de `jsdatasoustraitants` si pas de données en BDD
            console.log("Affichage depuis jsdatasoustraitants");
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

                // Ajouter le contact dans `selectedContacts` pour éviter les doublons
                if (!selectedContacts.find(c => c.contact_id == contact.fk_socpeople)) {
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
                }
            });

            // Sauvegarder les données après affichage initial
            saveData();
        } else {
            console.log("Aucun sous-traitant trouvé");
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
                    selectedContact[fieldName] = inputField.value;
                }

                saveData(); // Sauvegarder les modifications
            }
        }, true);
    }

    // Appel de la fonction pour récupérer et afficher les fournisseurs
    fetchSuppliersAndContacts();

    /**
     * Supprime une carte du DOM
     * 
     * @param {HTMLElement} card - La carte à supprimer
     */
    function deleteCard(card) {
        card.remove();
    }

    /**
     * Crée une nouvelle carte vide pour un utilisateur
     * Génère une carte avec champ de titre, menu déroulant de sélection,
     * affichage des habilitations et contrat, et bouton de suppression
     * 
     * @param {number} column - Le numéro de la colonne où créer la carte
     * @returns {HTMLElement} L'élément DOM de la carte créée
     */
    function createEmptyCard(column) {
        const columnElement = document.querySelector(`.card-column:nth-child(${column})`);
        
        // Compter les cartes et listes existantes dans la colonne
        const itemsInColumn = columnElement.querySelectorAll(".card, .user-list").length;

        // Y commence à 2 si il y a déjà des éléments dans la colonne, sinon à 2
        const yPosition = itemsInColumn + 1;
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
                        ${alluser}
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

        /**
         * Gère l'affichage du placeholder du titre de la carte
         */
        function updatePlaceholder() {
            const titleInput = card.querySelector(".title-input");
            const cardStatus = parseInt(card.getAttribute("data-status"));
            
            if (titleInput.value === "" && cardStatus === 0) {
                titleInput.placeholder = "Titre de la carte";
            } else {
                titleInput.placeholder = "";
            }
        }

        // Ajouter les écouteurs d'événements pour le placeholder
        const titleInput = card.querySelector(".title-input");
        titleInput.addEventListener("focus", updatePlaceholder);
        titleInput.addEventListener("blur", updatePlaceholder);
        titleInput.addEventListener("input", updatePlaceholder);

        // Observer les changements d'attribut data-status sur la carte
        const observer = new MutationObserver(updatePlaceholder);
        observer.observe(card, { attributes: true, attributeFilter: ["data-status"] });

        // Ajouter l'écouteur d'événement pour le changement d'utilisateur
        const nameDropdown = card.querySelector(".name-dropdown");
        nameDropdown.addEventListener("change", function() {
            const selectedUserId = this.value;
            const selectedUser = userjson.find(user => user.rowid === selectedUserId);
            
            if (selectedUser) {
                const habilitationInfo = card.querySelector(".habilitation-info");
                const contratInfo = card.querySelector(".contrat-info");
                
                // Formater les habilitations avec des retours à la ligne
                const habilitations = selectedUser.habilitations || "Non spécifié";
                const formattedHabilitations = habilitations.split(",").map(h => h.trim()).join(",\n");
                
                habilitationInfo.innerHTML = `<strong>Habilitations:</strong><br>${formattedHabilitations}`;
                contratInfo.innerHTML = `<strong>Contrat:</strong><br>${selectedUser.contrat || "Non spécifié"}`;
                
                // Sauvegarder les informations dans le dataset de la carte
                card.dataset.habilitations = selectedUser.habilitations || "";
                card.dataset.contrat = selectedUser.contrat || "";
            }
        });

        // Gestion de la soumission du formulaire de carte
        card.querySelector(".card-form").addEventListener("submit", function (event) {
            event.preventDefault();
            const selectedUserId = card.querySelector(".name-dropdown").value;
            const selectedUser = userjson.find(user => user.rowid === selectedUserId);
            const name = selectedUser ? `${selectedUser.firstname} ${selectedUser.lastname}` : "Non spécifié";

            // Formater les habilitations avec des retours à la ligne
            const habilitations = selectedUser ? (selectedUser.habilitations || "Non spécifié") : "Non spécifié";
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

    /**
     * Crée une liste d'utilisateurs pour une colonne spécifique
     * Génère une liste avec titre modifiable, légende des colonnes,
     * tous les utilisateurs du projet (sauf Q3SE et PCR) et boutons de suppression
     * 
     * @param {number} column - Le numéro de la colonne où créer la liste
     * @returns {HTMLElement} L'élément DOM de la liste créée
     */
    function createUserList(column) {
        // ...existing code...
    }

    /**
     * Attache les écouteurs d'événements de suppression aux utilisateurs d'une liste
     * Ajoute la fonctionnalité de suppression individuelle des utilisateurs
     * 
     * @param {HTMLElement} list - L'élément liste contenant les utilisateurs
     */
    function attachUserRemoveListeners(list) {
        const removeButtons = list.querySelectorAll(".remove-user");

        removeButtons.forEach(removeButton => {
            removeButton.addEventListener("click", function() {
                const li = this.parentElement;
                li.remove(); // Retirer l'utilisateur de la liste
                saveData();  // Sauvegarder les données après suppression
            });
        });
    }

    /**
     * Met à jour les IDs d'utilisateurs pour une carte spécifique
     * Remplace l'ancien utilisateur par le nouveau dans une carte
     * 
     * @param {Object} cell - L'objet cellule à mettre à jour
     * @param {string} newUserId - Le nouvel ID utilisateur à assigner
     */
    function updateUserIdsForCard(cell, newUserId) {
        if (cell.userId && cell.userId.length > 0) {
            // Mettre à jour l'utilisateur avec le dernier ID
            cell.userId = [newUserId]; // On garde uniquement le dernier utilisateur ajouté
        } else {
            // Si aucune donnée dans userId, on l'initialise avec l'ID du nouvel utilisateur
            cell.userId = [newUserId];
        }
        console.log(`User ID mis à jour pour la carte avec le nouvel utilisateur: ${newUserId}`);
    }

    /**
     * Ajoute un nouvel élément (carte ou liste) à une colonne spécifique
     * Crée et ajoute un nouvel élément à la colonne spécifiée
     * 
     * @param {number} column - Le numéro de la colonne cible
     * @param {string} type - Le type d'élément à ajouter ("card" ou "list")
     */
    function addItemToColumn(column, type) {
        var columnElement = document.querySelector(`.card-column:nth-child(${column})`);
        if (columnElement) {
            var newItem = null;

            if (type === "card") {
                newItem = createEmptyCard(column);  // Appel pour créer une carte
            } else if (type === "list") {
                newItem = createUserList(column);
            }

            // Vérifie que newItem est un nœud valide
            if (newItem && newItem instanceof Node) {
                // Ajouter newItem à la fin de la colonne
                columnElement.appendChild(newItem);

                // Réattacher le listener de suppression si nécessaire
                attachDeleteListener(newItem);
            }
        }
    }

    // Attacher les écouteurs d'événements pour les listes uniques
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

    // Attacher les écouteurs pour les boutons d'ajout
    document.querySelectorAll(".dropdown-content button").forEach(button => {
        button.addEventListener("click", function () {
            var column = this.getAttribute("data-column");
            var type = this.getAttribute("data-type");
            addItemToColumn(parseInt(column), type);
        });
    });

    /**
     * Attache tous les écouteurs d'événements aux éléments de l'interface
     * Centralise l'attachement de tous les écouteurs d'événements pour éviter les doublons
     * Utilise un système de marquage pour éviter les écouteurs multiples
     */
    function attachEventListeners() {
        // Attacher un écouteur sur les boutons ajout de carte/liste
        document.querySelectorAll(".add-card-button").forEach(button => {
            if (!button.dataset.listenerAttached) {
                button.addEventListener("click", function() {
                    saveData();
                    attachEventListeners(); // Ré-attacher les événements après ajout
                });
                button.dataset.listenerAttached = true;
            }
        });

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
                    if (card) {
                        card.remove();
                        saveData();
                        attachEventListeners(); // Ré-attacher les événements après suppression
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
                    if (list) {
                        list.remove(); // Supprime la liste
                        saveData(); // Sauvegarder les modifications après suppression
                        attachEventListeners(); // Ré-attacher les événements après suppression
                    }
                });
                button.dataset.listenerAttached = true;
            }
        });

        // Attacher des écouteurs sur la suppression des éléments des listes uniques
        document.querySelectorAll(".unique-list .remove-user").forEach(removeButton => {
            if (!removeButton.dataset.listenerAttached) {
                removeButton.addEventListener("click", function() {
                    const li = this.closest("li");
                    if (li) {
                        li.remove();
                        saveData();
                        attachEventListeners(); // Ré-attacher les événements après suppression
                    }
                });
                removeButton.dataset.listenerAttached = true;
            }
        });

        // Attacher des écouteurs sur le changement d'utilisateur dans les cartes
        document.querySelectorAll(".card .name-dropdown").forEach(dropdown => {
            if (!dropdown.dataset.listenerAttached) {
                dropdown.addEventListener("change", function() {
                    const selectedUser = this.value; // Nouveau utilisateur sélectionné
                    const card = this.closest(".card");

                    if (card) {
                        const userDisplay = card.querySelector(".selected-user-display");
                        if (userDisplay) {
                            userDisplay.textContent = selectedUser; // Afficher le nom de l'utilisateur
                        }
                        card.dataset.userId = selectedUser; // Mettre à jour l'ID utilisateur dans dataset
                        console.log("Utilisateur sélectionné pour la carte :", selectedUser);
                        console.log("Dataset après modification :", card.dataset.userId);
                        saveData(); // Sauvegarder les changements
                        console.log("Sauvegarde après sélection");
                    }
                });
                dropdown.dataset.listenerAttached = true;
            }
        });
    }

    // Appeler `attachEventListeners()` immédiatement après la création ou la modification des cartes
    attachEventListeners();

    /**
     * Sauvegarde toutes les données de l'organigramme vers le serveur
     * Fonction principale de sauvegarde qui collecte toutes les données et les envoie au serveur
     * 
     * Structure des données sauvegardées:
     * - cardsData: tableau contenant toutes les cartes et listes avec leurs coordonnées
     * - selectedContacts: informations des sous-traitants sélectionnés
     * - otid: identifiant de l'Ordre de Travail
     */
    function saveData() { 
        let cardsData = [];
        
        // Parcours de toutes les cartes pour récupérer les informations
        document.querySelectorAll(".card-column .card").forEach(function (card) {
            let titleInput = card.querySelector(".title-input");
            let nameDropdown = card.querySelector(".name-dropdown");

            // Récupération des coordonnées X et Y
            let x = Array.from(card.closest(".card-column").parentNode.children).indexOf(card.closest(".card-column")) + 1;
            let y = Array.from(card.closest(".card-column").querySelectorAll(".card")).indexOf(card) + 1;

            if (titleInput) {
                let title = titleInput.value;
                // Améliorer la récupération de l'userId
                let userId = "";
                if (nameDropdown && nameDropdown.value) {
                    userId = nameDropdown.value;
                } else if (card.dataset.userId) {
                    userId = card.dataset.userId;
                }
                
                let cardId = card.querySelector(".card-id") ? card.querySelector(".card-id").value : `card_${Date.now()}`;

                // Récupérer les habilitations et le contrat depuis le dataset de la carte
                let habilitations = card.dataset.habilitations || "";
                let contrat = card.dataset.contrat || "";

                let cardCoordinates = {
                    title: title,
                    userId: userId, 
                    type: "card",
                    otid: otId, 
                    id: cardId, 
                    x: x || 0,
                    y: y || 0,
                    habilitations: habilitations,
                    contrat: contrat
                };

                cardsData.push(cardCoordinates);
                console.log("Carte sauvegardée:", cardCoordinates);
            }
        });

        // Traitement des données de contacts/sous-traitants
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
                existingSubcontractorList = {
                    type: "listesoustraitant",
                    soustraitants: []
                };
                cardsData.push(existingSubcontractorList);
            }

            // Parcourir les sous-traitants récupérés de la base de données
            contactsData.forEach(contact => {
                const existingContact = existingSubcontractorList.soustraitants.find(
                    c => c.soc_people == contact.soc_people
                );

                if (existingContact) {
                    // Mettre à jour les informations du sous-traitant existant
                } else {
                    // Ajouter un nouveau sous-traitant
                    existingSubcontractorList.soustraitants.push(contact);
                }
            });

            // Supprimer les doublons dans la liste des sous-traitants
            existingSubcontractorList.soustraitants = existingSubcontractorList.soustraitants.filter((contact, index, self) =>
                index === self.findIndex(c => c.soc_people === contact.soc_people)
            );
        }

        // Parcours de toutes les listes pour récupérer les informations
        document.querySelectorAll(".card-column .user-list").forEach(function (list) {
            let titleInput = list.querySelector(".list-title-input");
            let listId = list.getAttribute("data-list-id"); 

            // Récupération des coordonnées X et Y
            let x = Array.from(list.closest(".card-column").parentNode.children).indexOf(list.closest(".card-column")) + 1;
            let y = Array.from(list.closest(".card-column").querySelectorAll(".card")).indexOf(list) + 1;

            if (titleInput) {
                let title = titleInput.value;

                // Remplacer la collecte des IDs utilisateurs pour ne pas ajouter de doublons
                let userIds = Array.from(list.querySelectorAll("li[data-user-id]")).map(function (li) {
                    return li.getAttribute("data-user-id");
                }).filter((id, index, self) => self.indexOf(id) === index);

                let listCoordinates = {
                    title: title,
                    userIds: userIds,
                    type: "list",
                    otid: otId, 
                    id: listId, 
                    x: x || 0,
                    y: y || 0
                };

                cardsData.push(listCoordinates);
            }
        });

        // Traitement des listes uniques
        document.querySelectorAll(".user-list.unique-list").forEach(function (uniqueList) {
            let titleInput = uniqueList.querySelector(".list-title-input");
            let uniqueListId = uniqueList.getAttribute("data-list-id");

            if (titleInput) {
                let title = titleInput.value;
                let userIds = Array.from(uniqueList.querySelectorAll("li[data-user-id]")).map(function (li) {
                    return li.getAttribute("data-user-id");
                }).filter((id, index, self) => self.indexOf(id) === index);

                let uniqueListCoordinates = {
                    title: title,
                    userIds: userIds,
                    type: "listeunique",
                    otid: otId,
                    id: 1
                };

                cardsData.push(uniqueListCoordinates);
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

        // Préparation du payload pour l'envoi au serveur
        let payload = {
            otid: otId,
            cardsData: cardsData.length > 0 ? cardsData : null,
            selectedContacts: contactsData.length > 0 ? contactsData : null
        };
        
        console.log(cardsData);

        // Envoi des données au serveur via fetch API
        fetch("ajax/save_card.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(payload),
        })
        .then(response => response.text())
        .then(data => {
            console.log("Réponse du serveur :", data);
            onSaveSuccess();
        })
        .catch(error => {
            console.error("Erreur de sauvegarde :", error);
        });

        /**
         * Callback appelé après une sauvegarde réussie
         */
        function onSaveSuccess() {
            console.log("Sauvegarde réussie !");
            // Ici on peut ajouter d'autres actions post-sauvegarde
        }
    }

    // Initialisation de l'interface
    updateCards(); 
});





