<?php
/**
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 *
 * (to do in a later version (file to be moved in a js use in a header).
 */
?>
<style>
    /* Général alignement central */
    .td-align-center {
        display: flex;
        align-items: center; 
        justify-content: space-between; 
        padding: 10px; 
        border: 1px solid #ddd; 
        background-color: #f9f9f9; 
    }


    .badgeCustom {
        display: inline-block;
        padding: 0.4em 0.75em;
        font-size: 90%; 
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.5rem; 
        text-transform: uppercase;
    }

    /* Badge warning (avertissement) */
    .badge-a1 {
        color: #856404; 
        background-color: #fff4cd; 
        border: 1px solid #ffcd38; 
    }

    /* Badge danger (critique) */
    .badge-a2 {
        color: #842029; 
        background-color: #f5c2c7; 
        border: 1px solid #dc3545;
    }

    /* Badge success (succès) */
    .badge-valide {
        color: #0f5132; 
        background-color: #d1e7dd; 
        border: 1px solid #198754; 
    }

    #tabs5 .user-info {
        display: flex;
        align-items: center;
    }

    #tabs5 .usertext {
        display: inline;
        width: 140px; 
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis; 
        margin-right: 10px; 
    }

    #tabs5 .userimg {
        margin-right: 5px; 
    }

    .badgeCustom {
        background-color: #28a745;
        color: white;
        padding: 0.25em 0.6em;
        border-radius: 12px;
        font-size: 0.9em;
    }

    #GanttChartDIV5JSGanttToolTip .gTILine:nth-of-type(3) {
        display: none !important;
    }

    #tabs5 .gtaskgreenCustom {
        /* background: rgba(60, 120, 20, 0.75)!important;*/
        background: linear-gradient(to bottom, rgba(80, 193, 58, 1) 0%, rgba(88, 209, 64, 1) 20%, rgba(102, 237, 75, 1) 40%, rgba(80, 193, 58, 1) 70%, rgba(53, 132, 37, 1) 100%) !important;
        height: 13px;
    }

    #tabs5 .gtaskFormation {
        background: linear-gradient(to bottom,#e69138,#f0a94e,#f7ba67,#f0a94e,#d97b21) !important;
            height: 13px;
    }


    #tabs5 .gtaskname {
        width: 70% !important;
    }
</style>
<script>
// Fonction principale pour traiter les ressources et générer les membres pour le Gantt
function processRessourcesAbs(ressources, filteredData) {
    
    const members = [];
    let memberCursor = 0;

    const ganttMode = document.getElementById('ganttMode');
    const ganttContainer = document.getElementById('ganttContainer');

    // Variable pour stocker les ressources filtrées
    let filteredResources = ressources; // Initialisation avec toutes les ressources

    // Génération initiale des membres (par défaut avec toutes les ressources)
    const initialMembers = generateMembersAbs(filteredResources, filteredData);
    // console.log("Membres initiaux générés :", initialMembers);

    // Membres traités
    return initialMembers;
}

// Fonction pour générer les membres en fonction des ressources
function generateMembersAbs(ressources, filteredData) {
   
    const array_contacts = [];
    const members = [];
    const member_dependencies = [];
    let membercursor = 0;
    
    // Parcours des ressources
    if(!ressources) {
        // Si les ressources sont nulles, afficher un message
        console.log("Aucune donnée disponible");
    }else{
        const today = new Date().toISOString().split('T')[0];
        ressources.forEach((val) => {
            console.log("val test ", val.idref);
            const condition =  (val.idref === "HL");
            // if (1=1) {
                const idparent = val.s ? val.id : `-${val.id}`;
            
                members[membercursor] = {
                    member_id: val.element_id_abs,
                    member_idref: val.idref,
                    member_alternate_id: membercursor + 1,
                    member_member_id: val.id,
                    member_parent: idparent,
                    member_is_group: 0,
                    member_start_date: val.date_start || today,
                    member_end_date: val.date_end || today,
                    member_milestone: "0",
                    member_resources: '',
                    member_member_html: val.name_html,
                    member_projet_ref: val.ref, 
                    member_hl_ref: val.holidayref,
                    member_user_id: val.id,
                    member_nb_open_day: val.nb_open_day_calculated || '',
                    member_name:''
                };

            
                if(val.idref === "HL" && val.status == 3){
                    members[membercursor].member_css = 'gtaskgreenCustom';
                }else if(val.idref == "HL" && val.status == 6){
                    members[membercursor].member_css = 'gtaskred';
                }else if(val.idref == "HL" && val.status == 2){
                    members[membercursor].member_css = 'gtaskyellow';
                }else if(val.idref == 'FH') {
                    members[membercursor].member_css = 'gtaskFormation';
                }else{
                    members[membercursor].member_css = 'gtaskblack';
                }

                labelProjets = val.projets == null || val.projets === '' ? '' : val.projets;
                labelHoliday = val.holidayref == null || val.holidayref === '' ? 'Aucune absence' : val.holidayref;
                <?php
                    $canReadHoliday = !empty($user->rights->holidaycustom->read);
                    $canReadFormation = !empty($user->rights->formationhabilitation->userformation->readall);
                ?>

                if (val.idref === 'HL') {
                    <?php if ($canReadHoliday) { ?>
                    members[membercursor].member_name = `
                        <a href="/custom/holidaycustom/card.php?id=${val.element_id_abs}&withproject=1"
                        title="Salarié affecté aux projets : ${val.projets} | Référence du congé : ${val.holidayref}">
                            ${labelHoliday}
                        </a> - <span>${labelProjets}</span>`;
                    <?php } else { ?>
                    members[membercursor].member_name = `
                        <span title="Salarié affecté aux projets : ${val.projets} | Référence du congé : ${val.holidayref}">
                            ${labelHoliday} - ${labelProjets}
                        </span>`;
                    <?php } ?>
                }
                else if (val.idref === 'FH') {
                    <?php if ($canReadFormation) { ?>
                    members[membercursor].member_name = `
                        <a href="/custom/formationhabilitation/userformation_card.php?id=${val.element_id_abs}&withproject=1"
                        title="Salarié affecté aux projets : ${val.projets} | Référence de la formation : ${val.holidayref}">
                            ${labelHoliday}
                        </a> - <span>${labelProjets}</span>`;
                    <?php } else { ?>
                    members[membercursor].member_name = `
                        <span title="Salarié affecté aux projets : ${val.projets} | Référence de la formation : ${val.holidayref}">
                            ${labelHoliday} - ${labelProjets}
                        </span>`;
                    <?php } ?>
                }
                else {
                    members[membercursor].member_name = `
                        <span title="Salarié affecté aux projets : ${val.projets} | Référence du congé : ${val.holidayref}">
                            ${labelHoliday} - ${labelProjets}
                        </span>`;
                }

            
            
                if (val.idref === "HL" && val.status == 3) {
                    members[membercursor].member_resources = `<span class="badge badge-large badge-valide" style="font-size: 1.2em;" title="Congé validé : ${val.conge_label}">Validé</span> 
                    <span class="badge badge-secondary" style="font-size: 0.9em;">${val.conge_label}</span> 
                    <span class="badge badge-info" style="background-color: #0075A8; color: white; padding: 3px 6px; border-radius: 4px; font-size: 0.9em;">🗓️ ${val.nb_open_day_calculated} jours</span>`;
                } else if (val.idref === "HL" && val.status == 6) {
                    members[membercursor].member_resources = `<span class="badge badge-large badge-a2" style="font-size: 1.2em;" title="Congé en approbation 2 : ${val.conge_label}">Appro. 2</span>
                    <span class="badge badge-secondary" style="font-size: 0.9em;">${val.conge_label}</span> 
                    <span class="badge badge-info" style="background-color: #0075A8; color: white; padding: 3px 6px; border-radius: 4px; font-size: 0.9em;">🗓️ ${val.nb_open_day_calculated} jours</span>`;
                } else if (val.idref === "HL" && val.status == 2) {
                    members[membercursor].member_resources = `<span class="badge badge-large badge-a1" style="font-size: 1.2em;" title="Congé en approbation 1 : ${val.conge_label}">Appro. 1</span>
                    <span class="badge badge-secondary" style="font-size: 0.9em;">${val.conge_label}</span> 
                    <span class="badge badge-info" style="background-color: #0075A8; color: white; padding: 3px 6px; border-radius: 4px; font-size: 0.9em;">🗓️ ${val.nb_open_day_calculated} jours</span>`;
                }  else if (val.idref === 'FH') {
                    members[membercursor].member_resources = `<span class="badge badge-large" 
                        style="background-color: #8a2be2; opacity:0.6; color: #fff; font-size: 0.9em; padding: 3px 6px; border-radius: 4px;" 
                        title="En formation : ${val.conge_label}">
                        🎓 En formation
                    </span>
                    <span class="badge badge-secondary" style="font-size: 0.9em;"> ${val.conge_label}</span>
                    <span class="badge badge-info" style="font-size: 0.9em;background-color: #0075A8; color: white; padding: 3px 6px; border-radius: 4px; font-size: 0.9em;">🗓️ ${countWorkingDays(val.date_start, val.date_end)} jours</span>`;
                }
                membercursor++;
            // }
        });
    }
    console.log('ressources data test', members);
    // Set parent IDs for Gantt chart
    members.forEach(member => {
        const parent = members.find(m => m.member_id === member.member_parent);
        member.member_parent_alternate_id = parent ? parent.member_alternate_id : member.member_parent;
    });


    return members;
}

/**
 * Calucler la période entre date de debut et date de fin
 * 
 * 
 */
function countWorkingDays(startDateStr, endDateStr) {
    const startDate = new Date(startDateStr);
    const endDate = new Date(endDateStr);
    let count = 0;
    
    // Parcours jour par jour
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        const day = d.getDay();
        if (day !== 0 && day !== 6) { // 0 = dimanche, 6 = samedi
            count++;
        }
    }

    return count;
}

// Fonction pour mettre à jour le Gantt avec les données filtrées
function updateGanttChartAbs(ressources, filteredData) {
    // Traitement des ressources et mise à jour du Gantt
    const processedMembers = processRessourcesAbs(ressources, filteredData); 
    // Mise à jour du Gantt avec les nouveaux membres
    setupGanttChartAbs(processedMembers);

}

// Format de la date utilisé par JSGantt
const dateformatinput5 = 'yyyy-mm-d %H:%i';
// Format de la date utilisé par dol_print_date
const dateformatinput52 = 'standard';

// Initialisation de la variable pour le filtre
let moreforfilter5 = '<div class="liste_titre liste_titre_bydiv centpercent">';

// Construction du champ de recherche
moreforfilter5 += '<div class="divsearchfield">';
// Ajouter ici le texte pour un utilisateur ou un autre champ de filtre si nécessaire
// Ex: moreforfilter5 += 'User search: ' + form.selectDolusers(val.id > 0 ? val.id : '', 'search_user_id', 1);
moreforfilter5 += '&nbsp;';
moreforfilter5 += '</div>';

// Fermeture du div de filtre
moreforfilter5 += '</div>';


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
	g.Draw(jQuery("#tabs5").width() + 100);
}


function setupGanttChartAbs(members) {
    if (!Array.isArray(members)) {
        console.error("Erreur : 'members' n'est pas un tableau.");
        return;
    }
  
    var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV5'), 'day');

    var booShowRessources = 1;
	var booShowDurations = 1;
	var booShowComplete = 1;
	var barText = "Resource";
	var graphFormat = "day";
	
	g.setShowRes(0); 		// Show/Hide Responsible (0/1)
	g.setShowDur(0); 		// Show/Hide Duration (0/1)
	g.setShowComp(0); 		// Show/Hide % Complete(0/1)
	g.setShowStartDate(1); 	// Show/Hide % Complete(0/1)
	g.setShowEndDate(1); 	// Show/Hide % Complete(0/1)
	g.setShowTaskInfoLink(1);
    g.setShowCost(0);
	g.setFormatArr("day","week","month", "quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
    g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
	g.setUseFade(0);
	g.setDayColWidth(20);
	// g.setShowTaskInfoLink(1);
	g.addLang('<?php print $langs->getDefaultLang(1); ?>', vLangs['<?php print $langs->getDefaultLang(1); ?>']);
	g.setLang('<?php print $langs->getDefaultLang(1); ?>');
    
    if (g.getDivId() != null) {

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
                    member_name: t['member_member_html'],
                    member_idref: t['member_idref'],
                    member_resources: t['member_idref'] === 'HL' ? 'Absence' : '',
                    member_start_date: t['member_start_date'],
                    member_end_date: t['member_end_date'],
                    member_is_group: 1,
                    member_position: 0,
                    member_css: 'ggroupblack',
                    member_milestone: 0,
                    member_parent: 0,
                    member_parent_alternate_id: 0,
                    member_notes: 'Test',
                    member_planned_workload: 0
                };

                const result = constructGanttLineAbs(members, tmpt, [], 0, t['member_member_id']);

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

                old_member_id = t['member_id'];
            }

            if (t["member_parent"] <= 0) {
                const result = constructGanttLineAbs(members, t, [], level, t['member_member_id']);
                
                // console.log("Tache ajoutée :", result);

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
      
                findChildGanttLine(members, t["member_user_id"], level + 1, g);
            }
        }

        g.Draw(jQuery("#tabs5").width() + 100);
        setTimeout(() => g.DrawDependencies(), 100);

    } else {
        alert("Graphique Gantt introuvable !");
    }
}


function constructGanttLineAbs(members, member, memberDependencies = [], level = 0, memberId = null) {
    const dateFormatInput52 = "YYYY-MM-DD";
    let startDate = member["member_start_date"];
    let endDate = member["member_end_date"] || startDate;

    startDate = formatDate(startDate, dateFormatInput52);
    endDate = formatDate(endDate, dateFormatInput52);

    const resources = member["member_resources"] || '';

    const parent = memberId && level < 0 ? `-${memberId}` : member["member_parent_alternate_id"];
    const percent = member["member_complete"];
    const css = member["member_css"];
    const name = member["member_name"];
    const lineIsAutoGroup = member["member_is_group"];
    const dependency = '';
    const memberIdAlt = member["member_alternate_id"];
    let note = '';
    note += `\nPlan de charge : Cliquez sur Plus d\'informations pour être dirigé vers les contacts`;

    let link = ''; 
    const DOL_URL_ROOT = "<?php echo DOL_URL_ROOT; ?>";
 
    if (!member.hasOwnProperty("member_idref")) {
		// Si "member_idref" n'existe pas, on laisse link vide
		link = '';
	} else if (member["member_idref"] === "HL") {
        link = DOL_URL_ROOT + '/custom/holidaycustom/card.php?id=' + Math.abs(member["member_id"]);
    } else if (member["member_idref"] === "FH") {
        link = DOL_URL_ROOT + '/custom/formationhabilitation/userformation_card.php?id=' + Math.abs(member["member_id"]);
    }
   

    return {
        id: memberIdAlt,
        name: name,
        start: startDate,
        end: endDate,
        class: css,
        link: link,
        milestone: member["member_milestone"],
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

	return date; 
}

// Echappe les chaines pour JS
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
const principalContentDiv5 = document.getElementById('principal_content5');
const ganttChartDiv5 = document.getElementById('GanttChartDIV5');

// Si les données sont disponibles, ajoutez le contenu au graphique Gantt
if (ganttChartDiv5) {
    // Fermer le graphique Gantt
    ganttChartDiv5.innerHTML += '</div>\n'; // Ajouter le contenu dans le div
} else {
    // Si le graphique Gantt ou les données ne sont pas disponibles
   
    // Afficher un message si JavaScript ou Ajax est désactivé
    const noRecordsDiv = document.createElement('div');
    noRecordsDiv.classList.add('opacitymedium');
    // noRecordsDiv.textContent = lang.trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
    document.body.appendChild(noRecordsDiv);
}

// Si aucune donnée n'est trouvée
if (!ganttChartDiv5) {
    const noRecordFoundDiv = document.createElement('div');
    noRecordFoundDiv.classList.add('opacitymedium');
    noRecordFoundDiv.textContent = "NoRecordFound";
    document.body.appendChild(noRecordFoundDiv);
}
</script>
<?php