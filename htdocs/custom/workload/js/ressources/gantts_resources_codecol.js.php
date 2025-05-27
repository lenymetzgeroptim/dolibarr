<?php
/**
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 *
 * File to be moved in a Header (to do)
 */
?>

<script>
    
// Fonction principale pour traiter les ressources et générer les membres pour le Gantt
function processRessourcesCodeCol(ressources, filteredData) {
    
    const members = [];
    let memberCursor = 0;

    const ganttMode = document.getElementById('ganttMode');
    const ganttContainer = document.getElementById('ganttContainer');

    // Variable pour stocker les ressources filtrées
    let filteredResources = ressources; // Initialisation avec toutes les ressources

    // Génération initiale des membres (par défaut avec toutes les ressources)
    const initialMembers = generateMembersCodeCol(filteredResources, filteredData);

    // Membres traités
    return initialMembers;
}

// Fonction pour générer les membres en fonction des ressources
function generateMembersCodeCol(ressources, filteredData) {

    let dateFormat = "yyyy-mm-dd"; // Format de date pour les données
    let members = [];
    let memberCursor = 0;
    console.log('My data', ressources);
    ressources.forEach((val) => {
        // let condition = val.element_id !== null || val.element_id !== 'undefined';
        let condition = val.element_id !== null && val.element_id !== undefined && val.element_id !== 'undefined';
     
        if (condition) {
            let idParent = val.s ? val.element_id : `-${val.element_id}`;
            let member = {
                member_id: val.fk_projet || null,
                member_alternate_id: memberCursor + 1,
                member_member_id: val.element_id || null,
                member_html: val.name_login,
                member_parent: idParent,
                member_ref: val.ref,
                member_start_date: val.date_start,
                member_end_date: val.date_end,
                member_is_group: 0,
                member_idref: val.idref,
                member_css: 'gtaskbluecomplete',
                member_milestone: '0'
            };

            // Projet référence et employé
            let refProj = `<a href="/projet/contact.php?id=${val.fk_projet}&withproject=1" title="${val.projet_ref}">${val.projet_ref}</a>`;

            if (val.id) {
                member.member_name = `<span title="Ressource dans les contacts de la commande">${val.name_abrvhtml}</span> - ${refProj}`;
            } else {
                member.member_name = `<span title="Pas de ressource sur cette commande">Aucune ressource</span> - ${refProj}`;
            }

            member.member_color = 'b4d1ea';

            // Gestion des ressources
            let missing2 = (val.pdc_etp_proj || 0) - val.pdc_etp_cde;
            let missing1 = val.pdc_proj - val.pdc_etp_cde;
            let pdcEtpCde = parseFloat(val.pdc_etp_cde);

            if (!val.id) {
                member.member_resources = missing2 > 0
                ? `<span class="badge badge-warning" title="Nombre d'employés sur projet ${val.pdc_etp_proj} (trouvé ${parseFloat(val.pdc_etp_cde)} sur commande)2 - ${missing2} manquant(s)."><i class="fa fa-exclamation-triangle"></i> ${parseFloat(val.pdc_etp_cde)} / ${val.pdc_etp_proj}</span>`
                : `<span class="badge badge-danger" title="Nombre d'employés sur projet ${val.pdc_etp_proj} (trouvé ${parseFloat(val.pdc_etp_cde)} sur commande)2 - ${missing2} en plus."><i class="fa fa-exclamation-triangle"></i>${parseFloat(val.pdc_etp_cde)} / ${val.pdc_etp_proj}</span>`;
                if(missing2 == 0) { 
                    member.member_resources = `<span class="badge badge-info" title="Nombre d'employés sur projet ${val.pdc_etp_proj} (trouvé ${parseFloat(val.pdc_etp_cde)} sur commande) - ${missing2} manquant(s)."> ${parseFloat(val.pdc_etp_cde)} / ${val.pdc_etp_proj}</span>`
                }
            } else {
                member.member_resources = Math.abs(val.missing1) > 0
                ? `<span class="badge badge-danger" title="Nombre d'employés sur projet ${val.pdc_proj} (trouvé ${parseFloat(val.pdc_etp_cde)} sur commande)1 - ${Math.abs(val.missing1)} manquant(s)."><i class="fa fa-exclamation-triangle"></i> ${parseFloat(val.pdc_etp_cde)} / ${val.pdc_proj}</span>`
                : `<span class="badge badge-danger" title="Nombre d'employés sur projet ${val.pdc_proj} (trouvé ${parseFloat(val.pdc_etp_cde)} sur commande)1 - ${Math.abs(val.missing1)} en plus."><i class="fa fa-exclamation-triangle"></i>${parseFloat(val.pdc_etp_cde)} / ${val.pdc_proj}</span>`;
                if(val.missing1 == 0) {
                    member.member_resources = `<span class="badge badge-info" title="Nombre d'employés sur projet ${val.pdc_proj} (trouvé ${parseFloat(val.pdc_etp_cde)} sur commande) - ${Math.abs(val.missing1)} manquant(s)"> ${parseFloat(val.pdc_etp_cde)} / ${val.pdc_proj}</span>`;
                }
            }

            members.push(member);
            memberCursor++;
        }
    });

    // Association des parents à leurs enfants
    members.forEach((member) => {
        let parent = members.find((m) => m.member_id === member.member_parent);
        member.member_parent_alternate_id = parent ? parent.member_alternate_id : member.member_parent;
    });

    return members;
}

// Fonction pour mettre é jour le Gantt avec les données filtrées
function updateGanttChartCodeCol(ressources, filteredData) {
    // Traitement des ressources et mise é jour du Gantt
    const processedMembers = processRessourcesCodeCol(ressources, filteredData); 
    // Mise é jour du Gantt avec les nouveaux membres
    setupGanttChartCodeCol(processedMembers);
    // setupGanttChart1(updatedMembers);
}

// Format de la date utilisé par JSGantt
const dateformatinput4 = 'yyyy-mm-dd';
// Format de la date utilisé par dol_print_date
const dateformatinput42 = 'standard';

// Initialisation de la variable pour le filtre
let moreforfilter4 = '<div class="liste_titre liste_titre_bydiv centpercent">';

// Construction du champ de recherche
moreforfilter4 += '<div class="divsearchfield">';
// Ajouter ici le texte pour un utilisateur ou un autre champ de filtre si nécessaire
// Ex: moreforfilter4 += 'User search: ' + form.selectDolusers(val.id > 0 ? val.id : '', 'search_user_id', 1);
moreforfilter4 += '&nbsp;';
moreforfilter4 += '</div>';

// Fermeture du div de filtre
moreforfilter4 += '</div>';

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
	g.Draw(jQuery("#tabs4").width()-40);
}

function setupGanttChartCodeCol(members) {
    if (!Array.isArray(members)) {
        console.error("Erreur : 'members' n'est pas un tableau.");
        return;
    }
  
    var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV4'), 'month');

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

        // Parcours des membres triés
        for (let tcursor = 0; tcursor < tnums; tcursor++) {
            const t = members[tcursor];

            if (!old_member_id || old_member_id !== t['member_member_id']) {
                const tmpt = {
                    member_id: `-${t['member_member_id']}`,
                    member_alternate_id: `-${t['member_member_id']}`,
                    member_name: t['member_ref'],
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

                const result = constructGanttLineCodeCol(members, tmpt, [], 0, t['member_member_id']);
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
                const result = constructGanttLineCodeCol(members, t, [], level, t['member_member_id']);
                // console.log("Téche ajoutée :", result);

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

        g.Draw(jQuery("#tabs4").width() - 40);
        setTimeout(() => g.DrawDependencies(), 100);
    } else {
        alert("Graphique Gantt introuvable !");
    }
}

function constructGanttLineCodeCol(members, member, memberDependencies = [], level = 0, memberId = null) {
    const dateFormatInput42 = "YYYY-MM-DD";

    let startDate = member["member_start_date"];
    let endDate = member["member_end_date"] || startDate;

    startDate = formatDate(startDate, dateFormatInput42);
    endDate = formatDate(endDate, dateFormatInput42);

    const resources = member["member_resources"] || '';
    const parent = memberId && level < 0 ? `-${memberId}` : member["member_parent_alternate_id"];
    const percent = member['member_percent_complete'];
    const css = member['member_css'];
	
	const name = member['member_name'];
	
   
    const lineIsAutoGroup = member["member_is_group"];
    const dependency = '';
    const memberIdAlt = member["member_alternate_id"];
    let note = '';
    note += `\nPlan de charge : Cliquez sur Plus d\'informations pour être dirigé vers les contacts`;

	let link = ''; 
	var DOL_URL_ROOT = "<?php echo DOL_URL_ROOT; ?>";

    if (member["member_id"] < 0) {
        link = DOL_URL_ROOT + '/commande/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
    } else if (member["member_idref"] === "PROJ") {
		link = DOL_URL_ROOT + '/projet/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
	} else {
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

// échappe les chaénes pour JS
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
			constructGanttLineCodeCol(taskArray, task, memberDependencies, level, null);

			// Recherche récursive des enfants
			findChildGanttLine(taskArray, task["member_id"], memberDependencies, level + 1);
		}
	}
}

// Fermzture de div principal
const principalContentDiv4= document.getElementById('principal_content4');
const ganttChartDiv4 = document.getElementById('GanttChartDIV4');

// Si les données sont disponibles, ajoutez le contenu au graphique Gantt
if (ganttChartDiv4) {
    // Vous pouvez ajouter ici des méthodes de tri ou de traitement des données comme `sortByValue` si nécessaire
    // Exemple : membertmp.sort(sortByValue);
    
    // Fermer le graphique Gantt
    ganttChartDiv4.innerHTML += '</div>\n'; // Ajouter le contenu dans le div
} else {
    // Si le graphique Gantt ou les données ne sont pas disponibles
    // Afficher un message si JavaScript ou Ajax est désactivé
    const noRecordsDiv = document.createElement('div');
    noRecordsDiv.classList.add('opacitymedium');
    // noRecordsDiv.textContent = lang.trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
    document.body.appendChild(noRecordsDiv);
}

// Si aucune donnée n'est trouvée
if (!ganttChartDiv4) {
    const noRecordFoundDiv = document.createElement('div');
    noRecordFoundDiv.classList.add('opacitymedium');
    // noRecordFoundDiv.textContent = lang.trans("NoRecordFound");
    document.body.appendChild(noRecordFoundDiv);
}
</script>
<?php