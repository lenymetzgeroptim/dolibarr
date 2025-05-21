<?php
/**
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 *
 * File to be moved in a Haeder (to do)
 */
?>
<script>

// Fonction principale pour traiter les ressources et générer les membres pour le Gantt
function processRessourcesColProj(ressources, filteredData) {
    
    const members = [];
    let memberCursor = 0;

    const ganttMode = document.getElementById('ganttMode');
    const ganttContainer = document.getElementById('ganttContainer');

    // Variable pour stocker les ressources filtrées
    let filteredResources = ressources; // Initialisation avec toutes les ressources

    // Génération initiale des membres (par défaut avec toutes les ressources)
    const initialMembers = generateMembersColProj(filteredResources, filteredData);

    // Membres traités
    return initialMembers;
}

// Fonction pour générer les membres en fonction des ressources
function generateMembersColProj(ressources, filteredData) {
    const existkey = [];
    const etpProj = {};
    const etpCode = {};
    const count = {};
    const etp = {};
    const diff = {};
    const employees = [];
    const availableUsers = [];
    const members = [];
    let memberCursor = 0;
    const elementCounts = {}; 
    const elementProjCounts = {};
    const elementProjDevCounts = {};

    // Analyse des ressources et calcul des fréquences
    ressources.forEach(val => {
        existkey.push(val.id);
        count[val.id] = (count[val.id] || 0) + 1;

        if (val.missing) {
            etpProj[val.fk_projet] = { etp_proj: parseInt(val.pdc_etp_proj, 10) };
        }
        etpCode[val.fk_projet] = { etp_code: parseInt(val.pdc_etp_cde, 10) };

        if (val.element_id) {
            elementCounts[val.element_id] = (elementCounts[val.element_id] || 0) + 1;
        }

        if (val.fk_projet && val.idref !== "PR") {
            elementProjCounts[`${val.fk_projet}`] = (elementProjCounts[`${val.fk_projet}`] || 0) + 1;
        }

        if (val.idref !== "CO") {
            elementProjDevCounts[`${val.element_id}`] = (elementProjDevCounts[`${val.element_id}`] || 0) + 1;
        }


    });

    // Définition des conditions ETP et calculer les différences
    for (const projKey in etpProj) {
        if (etpProj.hasOwnProperty(projKey) && etpCode.hasOwnProperty(projKey)) {
            etp[projKey] = etpProj[projKey].etp_proj > etpCode[projKey].etp_code;
            diff[projKey] = etpProj[projKey].etp_proj - etpCode[projKey].etp_code;
        }
    }

   

    ressources.forEach(val => {
        const member = {};
        const idParent = val.s ? val.id : `-${val.id}`;

        member.member_id = val.element_id;
        member.member_idref = val.idref;
        member.member_alternate_id = memberCursor + 1;
        member.member_html = val.name_html;
        member.member_member_id = val.id;
        member.member_parent = idParent;
        member.member_is_group = 0;
        
        if (val.idref === "CO") {
            member.member_css = 'gtaskblue';
        } else if (val.idref === "PR") {
            member.member_css = 'gtaskgreen';
        } else if (val.idref === "PROJ") {
            member.member_css = 'gtaskbluecomplete';
        }

        member.member_milestone = '0';
        let totalCount = "";
        // Les noms et ressources
        const idrefLabels = {
            CO: "Commande",
            PR: "Devis",
            PROJ: "Vide"
        };

        if (val.idref in idrefLabels) {
            const projetRefHtml = val.projet_ref 
                ? `<span class="projet-ref" title="Réf. Projet : ${val.projet_ref}">${val.projet_ref}</span>` 
                : "Aucun projet";
            
            let refHtml = "";
            if (val.ref || val.projet_ref) {  // Si `projet_ref` existe, `ref` ne peut pas être vide
                const refType = idrefLabels[val.idref]; 
                refHtml = `<span class="ref" title="Réf. ${refType} : ${val.ref || val.projet_ref}">${val.ref || val.projet_ref}</span>`;
            }

            if (val.idref === "PROJ") {
                member.member_name = val.projet_ref ? `${projetRefHtml} - Vide` : "Aucun projet - Vide";
            } else {
                member.member_name = val.projet_ref ? `${projetRefHtml} - ${refHtml}` : `Aucun projet - ${refHtml}`;
            }
        }

        if (val.idref !== "CO" && val.idref !== "PR" && val.idref !== "PROJ" && typeof val.idref === "undefined") {
            member.member_name = "Sans affectation sur commande";
        }

        member.member_start_date = val.date_start;
        member.member_end_date = val.date_end && !isNaN(new Date(val.date_end)) 
        ? val.date_end 
        : new Date().toISOString().split('T')[0];

        member.member_color = 'b4d1ea';
        let dateEndText = val.date_end ? '' : " Date fin ouverte";
        const pdc_etp_proj = parseInt(val.pdc_etp_proj, 10);
        const pdc_etp_cde = parseInt(val.pdc_etp_cde, 10);
        const pdc_etp_devis = parseInt(val.pdc_etp_devis, 10);
        
        if (val.idref === "CO") {
                totalCount = elementCounts[val.element_id] || 0;
              
                let totalProjCount = elementProjCounts[`${val.fk_projet}`] || 0;
               
            // member.member_resources = !val.etp
            //     ? `<span class="badge badge-info" title="${pdc_etp_cde} contacts prévu(s) sur cette commande : aucun manquant par rapport au projet - ${totalCount} contacts réellement affectés à la commande">${pdc_etp_cde} / ${totalProjCount}</span>`
            //     : `<span class="badge badge-danger" title="${pdc_etp_cde} contact(s) prévus sur cette commande, avec un écart par rapport au projet (${totalProjCount} contacts) - ${totalCount} contacts réellement affectés à la commande">${pdc_etp_cde} / ${totalProjCount}</span>`;
            let badgeClass, badgeTitle, badgeIcon = "";

            if (!val.etp && pdc_etp_cde === totalProjCount) {
                badgeClass = "badge-info";
                badgeTitle = `${pdc_etp_cde} contacts prévu(s) sur cette commande : aucun manquant par rapport au projet - ${totalCount} contacts réellement affectés à la commande`;
            } else if (pdc_etp_cde !== totalProjCount && totalCount === totalProjCount) {
                badgeClass = "badge-warning";
                badgeIcon = `<i class="fa fa-exclamation-triangle"></i> `;
                badgeTitle = `Les contacts prévus (${pdc_etp_cde}) et les contacts réellement affectés (${totalCount}) sur cette commande ne sont pas égaux - ${totalCount} contacts réellement affectés à la commande - (${totalProjCount} contacts sur projet)`;
            } else {
                badgeClass = "badge-danger";
                badgeIcon = `<i class="fa fa-exclamation-triangle"></i> `;
                badgeTitle = `${pdc_etp_cde} contact(s) prévus sur cette commande, avec un écart par rapport au projet (${totalProjCount} contacts) - ${totalCount} contacts réellement affectés à la commande`;
            }

            member.member_resources = `<span class="badge ${badgeClass}" title="${badgeTitle}">${badgeIcon}${pdc_etp_cde} / ${totalProjCount}</span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;


        } else if (val.idref === "PR") {
            totalCount = elementCounts[val.element_id] || 0;
            let totalProjDevCount = elementProjDevCounts[`${val.element_id}`] || 0;
            member.member_resources = !val.etp
                ? `<span class="badge badge-info" title="${pdc_etp_devis} contacts prévu(s) sur ce devis : aucun manquant par rapport au projet - ${totalCount} contacts réellement affectés au devis">${pdc_etp_devis}</span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`
                : `<span class="badge badge-danger" title="${pdc_etp_devis} contact(s) prévu(s) sur ce devis, soit un manque par rapport au projet - ${totalCount} contacts réellement affectés au devis">${pdc_etp_devis}</span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;
        } else if (val.idref === "PROJ") {
           
            const missing = val.missing !== null && val.missing !== undefined ? val.missing : 0;
            totalCount = elementCounts[val.element_id] || 0;
            
            if (val.etp !== undefined && val.missing !== undefined) {
                member.member_resources = `<span class="badge badge-warning" 
                title="${missing} - ${pdc_etp_proj} affectation(s) au projet - Ce salarié n'est pas affecté aux commandes de ce projet"><i class="fa fa-exclamation-triangle"></i>${pdc_etp_proj}</span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;
            } else {
                member.member_resources = `<span class="badge badge-info" style="background-color: #17a2b8;" title="${missing} manquant(s) - ${pdc_etp_proj} affectation(s) au projet - Aucune commande affectée au projet">${pdc_etp_proj}</span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;
            }
        }

        if (member.member_member_id !== undefined && member.member_member_id !== null) {
            members.push(member);
            memberCursor++;
        }
    });


    const uniqueMembers = members.filter((member, index, self) =>
        index === self.findIndex(m => 
            m.member_html === member.member_html && m.member_id === member.member_id
        )
    );


    // Association des parents aux tâches
    members.forEach((task, index) => {
        const parentTask = members.find(parent => parent.member_id === task.member_parent);
        members[index].member_parent_alternate_id = parentTask
            ? parentTask.member_alternate_id
            : task.member_parent;
    });
    
    return uniqueMembers;
}



// Fonction pour mettre à jour le Gantt avec les données filtrées
function updateGanttChartColProj(ressources, filteredData) {
    // Traitement des ressources et mise à jour du Gantt
    const processedMembers = processRessourcesColProj(ressources, filteredData); 
   
    // Mise à jour du Gantt avec les nouveaux membres
    setupGanttChartColProj(processedMembers);
}


// Format de la date utilisé par JSGantt
const dateformatinput112 = 'yyyy-mm-dd';
// Format de la date utilisé par dol_print_date
const dateformatinput22 = 'standard';

// Initialisation de la variable pour le filtre
let moreforfilter1 = '<div class="liste_titre liste_titre_bydiv centpercent">';

// Construction du champ de recherche
moreforfilter1 += '<div class="divsearchfield">';
// Ajouter ici le texte pour un utilisateur ou un autre champ de filtre si nécessaire
// Ex: moreforfilter += 'User search: ' + form.selectDolusers(val.id > 0 ? val.id : '', 'search_user_id', 1);
moreforfilter1 += '&nbsp;';
moreforfilter1 += '</div>';

// Fermeture du div de filtre
moreforfilter1 += '</div>';


function DisplayHideRessources(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowRessources = 1;
	}
	else {
		booShowRessources = 0;
	}
	reloadGraph();
}

function DisplayHideDurations(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowDurations = 1;
	}
	else {
		booShowDurations = 0;
	}
	reloadGraph();
}

function DisplayHideComplete(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowComplete = 1;
	}
	else {
		booShowComplete = 0;
	}
	reloadGraph();
}

function selectBarText(value) {
	graphFormat = g.getFormat();
	id=value.options[value.selectedIndex].value;
	barText = id;
	reloadGraph();
}

function reloadGraph() {

	g.setShowRes(booShowRessources);
	g.setShowComp(booShowComplete);
	g.setShowDur(booShowDurations);
	g.setCaptionType(barText);
	g.setFormat(graphFormat);
	g.Draw(jQuery("#tabs2").width()-40);
}

function setupGanttChartColProj(members) {
    if (!Array.isArray(members)) {
        console.error("Erreur : 'members' n'est pas un tableau.");
        return;
    }
  
    var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV2'), 'month');
    
    if (g.getDivId() != null) {
        g.setShowRes(1); 		// Show/Hide Responsible (0/1)
        g.setShowDur(1); 		// Show/Hide Duration (0/1)
        g.setShowComp(1); 		// Show/Hide % Complete(0/1)
        g.setShowStartDate(1); 	// Show/Hide % Complete(0/1)
        g.setShowEndDate(1); 	// Show/Hide % Complete(0/1)
        g.setShowTaskInfoLink(1);
        g.setFormatArr("day","week","month", "quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
        g.setCaptionType('Caption');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
        g.setUseFade(0);
        g.setDayColWidth(20);
        /* g.setShowTaskInfoLink(1) */
        g.addLang('<?php print $langs->getDefaultLang(1); ?>', vLangs['<?php print $langs->getDefaultLang(1); ?>']);
        g.setLang('<?php print $langs->getDefaultLang(1); ?>');

        let level = 0;
        const tnums = members.length;
        let old_member_id = 0;
        

        // Parcourir les membres triés
        for (let tcursor = 0; tcursor < tnums; tcursor++) {
            const t = members[tcursor];

            if (!old_member_id || old_member_id !== t['member_member_id']) {
                const tmpt = {
                    member_id: `-${t['member_id']}`,
                    member_alternate_id: `-${t['member_member_id']}`,
                    member_name: t['member_html'],
                    member_idref: t['member_idref'],
                    member_resources: 'ETP',
                    member_start_date: '',
                    member_start_date2: '',
                    member_end_date: '',
                    member_is_group: 1,
                    member_position: 0,
                    member_css: 'ggroupblack',
                    member_milestone: 0,
                    member_parent: 0,
                    member_parent_alternate_id: 0,
                    member_notes: '',
                    member_planned_workload: 0
                };

                const result = constructGanttLineColProj(members, tmpt, [], 0, t['member_member_id']);

                g.AddTaskItem(new JSGantt.TaskItem(
                    result.id,
                    result.name,
                    result.start,
                    result.end,
                    result.class,
                    result.link,
                    result.milestone,
                    result.resource,
                    result.complete,
                    result.group,
                    result.parent,
                    result.open,
                    result.depends,
                    result.caption,
                    result.note,
                    g
                ));

                old_member_id = t['member_member_id'];
            }

            if (t["member_parent"] <= 0) {
                const result = constructGanttLineColProj(members, t, [], level, t['member_member_id']);

                g.AddTaskItem(new JSGantt.TaskItem(
                    result.id,
                    result.name,
                    result.start,
                    result.end,
                    result.class,
                    result.link,
                    result.milestone,
                    result.resource,
                    result.complete,
                    result.group,
                    result.parent,
                    result.open,
                    result.depends,
                    result.caption,
                    result.note,
                    g
                ));

                findChildGanttLine(members, t["member_id"], [], level + 1);
            }
        }

        g.Draw(jQuery("#tabs2").width() - 40);
        setTimeout(() => g.DrawDependencies(), 100);
    } else {
        alert("Graphique Gantt introuvable !");
    }
}

function constructGanttLineColProj(members, member, memberDependencies = [], level = 0, memberId = null) {
    const dateFormatInput22 = "YYYY-MM-DD";

    let startDate = member["member_start_date"];
    let endDate = member["member_end_date"] || startDate;

    startDate = formatDate(startDate, dateFormatInput22);
    endDate = formatDate(endDate, dateFormatInput22);

    const resources = member["member_resources"] || '';
    const parent = memberId && level < 0 ? `-${memberId}` : member["member_parent_alternate_id"];
    const percent = member['member_percent_complete'];
    const css = member['member_css'];
	
	const name = member['member_name'];
	
    const lineIsAutoGroup = member["member_is_group"];
    const dependency = '';
    const memberIdAlt = member["member_alternate_id"];
    let note = member['note'];
    if (member["member_id"] > 0) {
        note += `\nPlan de charge : Cliquez sur Plus d\'informations pour être dirigé vers les contacts`;
    }else{
        note += `\nPlan de charge`;
    }
     console.log(member["member_id"]);
	let link = ''; 
	var DOL_URL_ROOT = "<?php echo DOL_URL_ROOT; ?>";
 
	if (member["member_id"] < 0) {
		// Si "member_idref" n'existe pas, on laisse link vide
        link = ''; 
	} else if (member["member_idref"] === "CO") {
		link = DOL_URL_ROOT + '/commande/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
	} else if (member["member_idref"] === "PR") {
		link = DOL_URL_ROOT + '/comm/propal/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
	} else if (member["member_idref"] === "PROJ") {
		link = DOL_URL_ROOT + '/projet/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
	}
	
    return {
        id: memberIdAlt,
        name: name,
        start: startDate,
        end: endDate,
        class: css,
        link: link,
        milestone: member['member_milestone'],
        resource: resources,
        complete: percent >= 0,
        group: lineIsAutoGroup,
        parent: parent,
        open: 1,
        depends: dependency,
        caption: lineIsAutoGroup === 0 && percent >= 0 ? `${percent}%` : '',
        note: note
    };
}

// Simule la conversion de date (Dolibarr `dol_print_date`)
function formatDate(date, format) {
	// Implémentez une fonction de conversion de date au besoin (ex: moment.js ou day.js)
	return date; // Placeholder
}

// Échappement des chaînes pour JS
function escapeJs(str) {
	return str.replace(/'/g, "\\'").replace(/\n/g, "\\n");
}

// Convertit les secondes en heures/minutes
function convertSecondsToTime(seconds, format) {
	const hours = Math.floor(seconds / 3600);
	const minutes = Math.floor((seconds % 3600) / 60);
	return `${hours}h ${minutes}min`;
}

function findChildGanttLine(taskArray, parentId, memberDependencies, level) {
	const numTasks = taskArray.length;
	let oldParentId = 0;

	for (let i = 0; i < numTasks; i++) {
		const task = taskArray[i];

		if (
			task["member_parent"] === parentId && 
			task["member_parent"] !== task["member_id"]
		) {
			// Exemple de création d'une tâche parent fictive pour le nouveau niveau (commentée ici)
			/*
			if (!oldParentId || oldParentId !== task['member_member_id']) {
				const tempTask = {
					member_id: -98,
					member_name: `Level ${level}`,
					member_resources: '',
					member_start_date: '',
					member_end_date: '',
					member_is_group: 1,
					member_css: 'ggroupblack',
					member_milestone: 0,
					member_parent: task["member_parent"],
					member_notes: ''
				};
				constructGanttLineColProj(taskArray, tempTask, [], 0, task['member_member_id']);
				oldParentId = task['member_member_id'];
			}
			*/

			// Ajout de la ligne actuelle
			constructGanttLineColProj(taskArray, task, memberDependencies, level, null);

			// Recherche récursive des enfants
			findChildGanttLine(taskArray, task["member_id"], memberDependencies, level + 1);
		}
	}
}
	
// Fermer le div principal
const principalContentDiv2 = document.getElementById('principal_content2');
const ganttChartDiv2 = document.getElementById('GanttChartDIV2');

// Si les données sont disponibles, ajoutez le contenu au graphique Gantt
if (ganttChartDiv2) {
    ganttChartDiv2.innerHTML += '</div>\n'; // Ajouter le contenu dans le div
} else {
    // Si le graphique Gantt ou les données ne sont pas disponibles
    
    // Afficher un message si JavaScript ou Ajax est désactivé
    const noRecordsDiv2 = document.createElement('div');
    noRecordsDiv2.classList.add('opacitymedium');
    // noRecordsDiv.textContent = lang.trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
    document.body.appendChild(noRecordsDiv2);
}

// Si aucune donnée n'est trouvée
if (!ganttChartDiv2) {
    const noRecordFoundDiv2 = document.createElement('div');
    noRecordFoundDiv2.classList.add('opacitymedium');
    // noRecordFoundDiv.textContent = lang.trans("NoRecordFound");
    document.body.appendChild(noRecordFoundDiv2);
}
</script>
<?php

