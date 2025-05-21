<?php
/**
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 *
 * to do in a later version - file to be moved in a header
 */
?>
<style>
    
</style>

<script>
// Fonction principale pour traiter les ressources et générer les membres pour le Gantt
function processRessourcesProjCom(ressources, filteredData) {
    let memberCursor = 0;

    const ganttMode = document.getElementById('ganttMode');
    const ganttContainer = document.getElementById('ganttContainer');

    // Variable pour stocker les ressources filtrées
    let filteredResources = ressources; 

    // Génération initiale des membres (par défaut avec toutes les ressources)
    const initialMembers = generateMembersProjCom(filteredResources, filteredData);

    // Membres traités
    return initialMembers;
}

// Fonction pour générer les membres en fonction des ressources
function generateMembersProjCom(ressources, filteredData) {
    // Preparation de Gantt 
    let members = [];
    let memberCursor = 0;

    ressources.forEach(val => {
        const condition = val.fk_projet !== null || typeof val.fk_projet === "undefined";

        if (condition) {
            const idParent = typeof val.fk_projet === "undefined" ? val.id : `-${val.fk_projet}`;
            
            const member = {
                member_id: val.fk_projet,
                member_idref: val.idref,
                member_alternate_id: memberCursor + 1,
                member_member_id: val.fk_projet,
                member_name_html: val.name_html,
                member_projet_ref: val.projet_ref,
                member_parent: idParent,
                member_is_group: 0,
                member_css: '',
                member_milestone: '0',
                member_name: '',
                member_start_date: formatDate(val.date_start, 'yyyy-MM-dd'),
                // member_end_date: formatDate(val.date_end, 'yyyy-MM-dd'),
                member_end_date: val.date_end ? formatDate(val.date_end, 'yyyy-MM-dd') : new Date().toISOString().split('T')[0],
                member_color: 'b4d1ea',
                member_resources: ''
            };

            
            // Set CSS class based on idref
            if (val.idref === "CO") {
                member.member_css = 'gtaskblue';
            } else if (val.idref === "PR") {
                member.member_css = 'gtaskgreen';
            } else if (val.idref === "PROJ") {
                member.member_css = 'gtaskbluecomplete';
            }

            // Set member name based on idref
            // const employee = user.rights.user.user.lire ? getNomUrl(memberCursor) : val.login;
            let employeeName = val.name_login ? val.name_abrvhtml : 'Aucune ressource';
            if (val.name_login !== undefined && val.idref === "CO") {
                member.member_name = `<span class="member-inline">${val.ref}</span> <span class="member-inline">- ${employeeName}</span>`;
            } else if (val.name_login !== undefined && val.idref === "PR") {
                member.member_name = `<span class="member-inline">${val.ref}</span> <span class="member-inline">- ${employeeName}</span>`;
            } else if (val.name_login !== undefined && val.idref === "PROJ") {
                member.member_name = `<span class="member-inline">${val.ref}</span> <span class="member-inline" style="margin-left: 8px;">- ${employeeName}</span>`;
            } else if (val.name_login === undefined && val.idref === "PROJ") {
                member.member_name = `<span class="member-inline">${val.ref}</span> <span class="member-inline" style="margin-left: 8px;">- Vide</span>`;
            }

            member.member_resources = '';
            let dateEndText = val.date_end ? '' : " Date fin ouverte";

            if (val.idref === "CO") {
                member.member_resources = `<span title="Les contacts qui sont prévus sur la commande">${val.pdc_etp_cde}</span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;
                label = "la commande";
            }

            if (val.idref === "PR") {
                label = "le devis";
                member.member_resources = `<span title="Les contacts qui sont prévus sur le devis">${val.pdc_etp_devis}</span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;
            }

            const diff = val.pdc_etp_cde < val.pdc_etp_proj;

            if (val.idref === "PROJ") {
                label = "le projet";
                const missing = val.missing !== null ? Math.abs(val.missing) : 0;

                if (val.etp) {
                    member.member_resources = `<span class="classfortooltip badge badge-warning" title="${missing} manquant(s) (contacts réels sur le projet par rapport aux contacts prévus sur les commandes)">
                        <i class="fa fa-exclamation-triangle"></i>${val.pdc_etp_proj}</span>`;
                } else {
                    member.member_resources = `<span class="classfortooltip badge badge-info" title="${missing} manquant(s) (contacts réels sur le projet par rapport aux contacts prévus sur les commandes)">
                        ${val.pdc_etp_proj} </span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;
                }

                if (val.pdc_etp_proj === 0) {
                    member.member_resources = `<span class="classfortooltip badge badge-info" title="Aucune ressource ni commande ne sont affectées à ce projet">
                        ${val.pdc_etp_proj} </span>&nbsp;<span><i class="fas fa-infinity mr-1" title="${dateEndText}"></i></span>`;
                }
            }

            if (member.member_projet_ref !== undefined && member.member_projet_ref !== null) {
                members.push(member);
                memberCursor++;
            }
        }
    });

    members = members.filter((member, index, self) =>
        index === self.findIndex(m => m.member_name === member.member_name)
    );

    // Set parent IDs for Gantt chart
    members.forEach(member => {
        const parent = members.find(m => m.member_id === member.member_parent);
        member.member_parent_alternate_id = parent ? parent.member_alternate_id : member.member_parent;
    });

    return members;

}

// Fonction pour mettre à jour le Gantt avec les données filtrées
function updateGanttChartProjCom(ressources, filteredData) {
    // Traitement des ressources et mise à jour du Gantt
    const processedMembers = processRessourcesProjCom(ressources, filteredData); 
    // Mise à jour du Gantt avec les nouveaux membres
    setupGanttChartProjCol(processedMembers);
}

function setupGanttChartProjCol(members) {
    if (!Array.isArray(members)) {
        console.error("Erreur : 'members' n'est pas un tableau.");
        return;
    }

    var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV3'), 'month');

    var booShowRessources = 1;
	var booShowDurations = 1;
	var booShowComplete = 1;
	var barText = "Resource";
	var graphFormat = "day";
	
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
    
    if (g.getDivId() != null) {
        let level = 0;
        const tnums = members.length;
        let old_member_id = 0;

        // Parcour des membres triés
        for (let tcursor = 0; tcursor < tnums; tcursor++) {
            const t = members[tcursor];

            if (!old_member_id || old_member_id !== t['member_member_id']) {
                const tmpt = {
                    member_id: `-${t['member_member_id']}`,
                    member_alternate_id: `-${t['member_member_id']}`,
                    member_name: t['member_projet_ref'],
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
       
        g.Draw(jQuery("#tabs6").width() - 40);
        setTimeout(() => g.DrawDependencies(), 100);
    } else {
        alert("Graphique Gantt introuvable !");
    }
}

function constructGanttLineColProj(members, member, memberDependencies = [], level = 0, memberId = null) {
    const dateFormatInput2 = "YYYY-MM-DD";

    let startDate = member["member_start_date"];
    let endDate = member["member_end_date"] || startDate;

    startDate = formatDate(startDate, dateFormatInput2);
    endDate = formatDate(endDate, dateFormatInput2);

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

	if (!member.hasOwnProperty("member_idref")) {
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

// Simule de la conversion de date (Dolibarr `dol_print_date`)
function formatDate(date, format) {
	// Implémentez une fonction de conversion de date au besoin (ex: moment.js ou day.js)
	return date; // Placeholder
}

// On échappe les chaénes pour JS
function escapeJs(str) {
	return str.replace(/'/g, "\\'").replace(/\n/g, "\\n");
}

// Convertion des secondes en heures/minutes
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
			// Exemple de création d'une téche parent fictive pour le nouveau niveau (commentée ici)
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
			// constructGanttLineColProj(taskArray, task, memberDependencies, level, null);

			// Recherche récursive des enfants
			findChildGanttLine(taskArray, task["member_id"], memberDependencies, level + 1);
		}
	}
}
	
</script>
<?php

