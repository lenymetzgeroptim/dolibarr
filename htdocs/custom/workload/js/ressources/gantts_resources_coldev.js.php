<?php
/**
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 * 
 * File to be moved in a Header (to do)
 */
?>
<script>

// Fonction principale pour traiter les ressources et générer les membres pour le Gantt
function processRessourcesColDev(ressources, filteredData) {
    
    const members = [];
    let memberCursor = 0;

    const ganttMode = document.getElementById('ganttMode');
    const ganttContainer = document.getElementById('ganttContainer');

    // Variable pour stocker les ressources filtrées
    let filteredResources = ressources; // Initialisation avec toutes les ressources

    // Génération initiale des membres (par défaut avec toutes les ressources)
    const initialMembers = generateMembersColDev(filteredResources, filteredData);
    // console.log("Membres initiaux générés :", initialMembers);

    // Membres traités
    return initialMembers;
}

// Fonction pour générer les membres en fonction des ressources
function generateMembersColDev(ressources, filteredData) {
    const employees = [];
    const availableUsers = [];
    const members = [];
    let memberCursor = 0;
   
    ressources.forEach((val) => {
        // Condition de filtrage
        const condition = val.element_id !== null && val.id !== null;

        if (condition) {
            const idParent = val.s ? val.id : `-${val.id}`;

            const member = {
                member_id: val.element_id,
                member_idref: val.idref,
                member_alternate_id: memberCursor + 1,
                member_member_id: val.id,
                member_html: val.name_html,
                member_parent: idParent,
                member_is_group: 0,
                member_start_date: val.date_start,
                member_end_date: val.date_end,
                member_color: 'b4d1ea',
                member_resources: val.s,
                member_milestone: '0',
            };

            // Traitement selon la valeur de idref
            if (val.idref === "CO") {
                member.member_css = 'gtaskblue';
                member.member_resources = `<span title="Les contacts prévus sur la commande">${val.pdc_etp_cde}</span>`;
                member.member_name = `<a href="/commande/contact.php?id=${val.element_id}&withproject=1">${val.ref}</a> - Aquis`;
            } else if (val.idref === "PR") {
                member.member_css = 'gtaskgreen';
                member.member_resources = `<span title="Les contacts prévus sur le devis">${val.pdc_etp_devis}</span>`;
                member.member_name = `<a href="/erp/comm/propal/contact.php?id=${val.element_id}&withproject=1">${val.ref}</a> - Potentiel`;
            } else if (typeof val.idref === "undefined") {
                member.member_resources = '0';
                member.member_name = 'Sans affectation sur commande';
            }

    
            if (member.member_name) {
                members.push(member);
                memberCursor++;
            }
        }
    });

    // Gestion des relations parents/enfants
    members.forEach((member, tmpKey) => {
        const parent = members.find((parentMember) => parentMember.member_id === member.member_parent);
        members[tmpKey].member_parent_alternate_id = parent ? parent.member_alternate_id : member.member_parent;
    });

    // Tri des membres par element_id 
    members.sort((a, b) => a.member_member_id - b.member_member_id);

    return members;
}

// Fonction pour mettre à jour le Gantt avec les données filtrées
function updateGanttChartColDev(ressources, filteredData) {
    // Traitement des ressources et mise à jour du Gantt
    const processedMembers = processRessourcesColDev(ressources, filteredData); 
    // Mise à jour du Gantt avec les nouveaux membres
    setupGanttChartColDev(processedMembers);
}

// Format de la date utilisé par JSGantt
const dateformatinput1 = 'yyyy-mm-dd';
// Format de la date utilisé par dol_print_date
const dateformatinput12 = 'standard';

// Initialisation de la variable pour le filtre
let moreforfilter = '<div class="liste_titre liste_titre_bydiv centpercent">';

// Construction du champ de recherche
moreforfilter += '<div class="divsearchfield">';
// Ajouter ici le texte pour un utilisateur ou un autre champ de filtre si nécessaire
// Ex: moreforfilter += 'User search: ' + form.selectDolusers(val.id > 0 ? val.id : '', 'search_user_id', 1);
moreforfilter += '&nbsp;';
moreforfilter += '</div>';

// Fermeture du div de filtre
moreforfilter += '</div>';

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
	g.Draw(jQuery("#tabs").width()-40);
}

function setupGanttChartColDev(members) {
    if (!Array.isArray(members)) {
        console.error("Erreur : 'members' n'est pas un tableau.");
        return;
    }
  

    var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV'), 'month');

    if (g.getDivId() != null) {
        
        g.setShowRes(1); 		// Show/Hide Responsible (0/1)
        g.setShowDur(1); 		// Show/Hide Duration (0/1)
        g.setShowComp(1); 		// Show/Hide % Complete(0/1)
        g.setShowStartDate(1); 	// Show/Hide % Complete(0/1)
        g.setShowEndDate(1); 	// Show/Hide % Complete(0/1)
        g.setShowTaskInfoLink(1);
        g.setFormatArr("day","week","month", "quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
        g.setCaptionType('Duration');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
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

                const result = constructGanttLine(members, tmpt, [], 0, t['member_member_id']);
                // console.log("Groupe ajouté :", result);

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
                const result = constructGanttLine(members, t, [], level, t['member_member_id']);
                // console.log("Tâche ajoutée :", result);

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

        g.Draw(jQuery("#tabs").width() - 40);
        setTimeout(() => g.DrawDependencies(), 100);
    } else {
        alert("Graphique Gantt introuvable !");
    }
}

function constructGanttLine(members, member, memberDependencies = [], level = 0, memberId = null) {
    const dateFormatInput12 = "YYYY-MM-DD";

    let startDate = member["member_start_date"];
    let endDate = member["member_end_date"] || startDate;

    startDate = formatDate(startDate, dateFormatInput12);
    endDate = formatDate(endDate, dateFormatInput12);

    const resources = member["member_resources"] || '';
    const parent = memberId && level < 0 ? `-${memberId}` : member["member_parent_alternate_id"];
    const percent = member['member_percent_complete'];
    const css = member['member_css'];
	
	const name = member['member_name'];
	
   
    const lineIsAutoGroup = member["member_is_group"];
    const dependency = '';
    const memberIdAlt = member["member_alternate_id"];
    let note = '';
    if (member["member_id"] > 0) {
        note += `\nPlan de charge : Cliquez sur Plus d\'informations pour être dirigé vers les contacts`;
    }else{
        note += `\nPlan de charge`;
    }

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

// Échappe les chaînes pour JS
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
				constructGanttLine(taskArray, tempTask, [], 0, task['member_member_id']);
				oldParentId = task['member_member_id'];
			}
			*/

			// Ajout de la ligne actuelle
			constructGanttLine(taskArray, task, memberDependencies, level, null);

			// Recherche récursive des enfants
			findChildGanttLine(taskArray, task["member_id"], memberDependencies, level + 1);
		}
	}
}

	
// Fermer le div principal
const principalContentDiv = document.getElementById('principal_content');
const ganttChartDiv1 = document.getElementById('GanttChartDIV');

// Si les données sont disponibles, ajoutez le contenu au graphique Gantt
if (ganttChartDiv1) {
    // Vous pouvez ajouter ici des méthodes de tri ou de traitement des données comme `sortByValue` si nécessaire
    // Exemple : membertmp.sort(sortByValue);
    
    // Fermer le graphique Gantt
    ganttChartDiv1.innerHTML += '</div>\n'; // Ajouter le contenu dans le div
} else {
    // Si le graphique Gantt ou les données ne sont pas disponibles
    
    // Afficher un message si JavaScript ou Ajax est désactivé
    const noRecordsDiv = document.createElement('div');
    noRecordsDiv.classList.add('opacitymedium');
    // noRecordsDiv.textContent = lang.trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
    document.body.appendChild(noRecordsDiv);
}

// Si aucune donnée n'est trouvée
if (!ganttChartDiv1) {
    const noRecordFoundDiv = document.createElement('div');
    noRecordFoundDiv.classList.add('opacitymedium');
    // noRecordFoundDiv.textContent = lang.trans("NoRecordFound");
    document.body.appendChild(noRecordFoundDiv);
}
</script>
<?php
