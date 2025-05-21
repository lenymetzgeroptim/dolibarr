<?php
/**
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */
?>
<style>
[class^="custom-gradient-PROJ-"] {
    height: 5px;
}
.gCurDate {
    z-index: 10!important
}

#tabs6 .gtasktable th:nth-child(2), .gtasktable td:nth-child(2) {
    width: 40%!important;
}
#tabs6 .gres {
    text-align: justify;
}

#tabs6 .gmainleft {
    overflow: hidden;
    flex: 0 0 25%;
}

#fixedHeader_GanttChartDIV6 {
    width: 61.89%!important;
}
</style>

<script>

// Fonction principale pour traiter les ressources et générer les membres pour le Gantt
// function processRessourcesProjAbs(ressources, filteredData) {
//     // const members = [];
//     let memberCursor = 0;

//     const ganttMode = document.getElementById('ganttMode');
//     const ganttContainer = document.getElementById('ganttContainer');

//     // Variable pour stocker les ressources filtrées
//     let filteredResources = ressources; // Initialisation avec toutes les ressources

//     // Génération initiale des membres (par défaut avec toutes les ressources)
//     const initialMembers = generateMembersProjAbs(filteredResources, filteredData);
//     // console.log("Membres initiaux générés :", initialMembers);

//     // Membres traités
//     return initialMembers;
// }

// function generateMembersProjAbs(ressources, filteredData) {
//     let members = [];
//     let memberCursor = 0;
//     let dynamicStyles = "";

//     // if (!Array.isArray(ressources)) {
//     //     console.error("Erreur : 'ressources' est undefined ou n'est pas un tableau !");
//     //     return [];
//     // }

//     let structurelLabels = [];
//     let structureMembers = [];
   
//     if(!ressources) {
//         // Si les ressources sont nulles, afficher un message
//         console.log("Aucune donnée disponible");
//     // document.getElementById("message-container").innerHTML = "Aucune donnée disponible";
//     }else{
//         ressources.forEach(val => {
//             const condition = val.fk_projet !== null || typeof val.fk_projet === "undefined";

//             if (condition) {
//                 const idParent = val.s ? val.fk_projet : `-${val.fk_projet}`;
//                 let todayPlusOneYear = new Date();
//                 todayPlusOneYear.setFullYear(todayPlusOneYear.getFullYear() + 1);
//                 let formattedDate = todayPlusOneYear.toISOString().split('T')[0];
//                 // isStructurel = val.str == 1;
//                 // structurelLabel = isStructurel ? `[Structure] ${val.projet_ref}` : val.projet_ref;
//                 // console.log('Label structurel : ', structurelLabel);
//                 let isStructurel = val.str == 1;
//                 let cleanRef = (val.projet_ref || '').replace(/\[Structure\]\s*/i, '').trim(); 
//                 let badgeStructure = `<span class="badge badge-warning" style="margin-right: 6px; background-color: #f4b300; color: #000; border-radius: 6px; padding: 2px 6px; font-weight: bold;">Structurel</span>`;
//                 let structurelLabel = isStructurel ? `${badgeStructure}${cleanRef}` : cleanRef;
               
//                 const member = {
//                     member_id: val.fk_projet,
//                     member_idref: val.idref,
//                     member_alternate_id: memberCursor + 1,
//                     member_member_id: val.fk_projet,
//                     member_name_html: val.name_html,
//                     member_projet_ref: structurelLabel,
//                     member_parent: idParent,
//                     member_is_group: 0,
//                     member_css: '', // Classe CSS générée dynamiquement
//                     member_milestone: '0',
//                     member_name: val.name_html,
//                     member_start_date: formatDate(val.date_start, 'yyyy-MM-dd'),
//                     member_end_date: val.date_end 
//                     ? formatDate(val.date_end, 'yyyy-MM-dd') 
//                     : formattedDate,

//                     member_color: 'b4d1ea',
//                     member_resources: '',
//                     member_rsc_detail: ''
//                 };

//                 // let gradientCss = generateGradientForAbsences(val.periodes, val.date_start, member.member_end_date, val.idref);
//                 // const className = `custom-gradient-${val.idref}-${memberCursor}`;
//                 // dynamicStyles += `.${className} { width: 200px; height: 13px; background: ${gradientCss}; }\n`;
//                 let result = generateGradientForAbsences(val.periodes, val.date_start, member.member_end_date, val.idref);
//                 const className = `custom-gradient-${val.idref}-${memberCursor}`;
//                 member.member_resources = result.badges; // On affiche les badges
//                 member.member_rsc_detail = result.rsc;

//                 dynamicStyles += `.${className} { 
//                     width: 200px; 
//                     height: 13px; 
//                     background: ${result.gradient}; 
//                     border: 1px solid rgba(0, 0, 0, 0.2); 
//                     box-shadow: rgba(0, 0, 0, 0.3); 
//                     background-blend-mode: multiply; 
//                 }\n`;

//                 member.member_css = className;
//                 member.projet_ref = structurelLabel;

//                 // members.push(member);
//                 // Tri 
//                 if (isStructurel) {
//                     structureMembers.push(member);
//                 } else {
//                     members.push(member);
//                 }
//                 memberCursor++;
//             }
//         });
//     }

//     injectDynamicStyles(dynamicStyles);
    
//     // Concatènation des non-structurels puis les structurels
//     members = members.concat(structureMembers);

    
//     // Mise à jour les parents des tâches
//     members.forEach(member => {
//         const parent = members.find(m => m.member_id === member.member_parent);
//         member.member_parent_alternate_id = parent ? parent.member_alternate_id : member.member_parent;
//     });

//     return members;
// }

// function generateGradientForAbsences(absences, startDate, endDate, idref) {
//     if (!Array.isArray(absences) || absences.length === 0) {
//         let defaultColor = getDefaultColor(idref);
//         return {
//             gradient: `linear-gradient(to right, ${defaultColor} 0% 100%)`,
//             badges: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333; text-align: left;" title="Aucune absence enregistrée">Aucune absence</span>`,
//             rsc: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333; text-align: left;" title="Aucune absence enregistrée">Aucune absence</span>`
//         };
//     }

//     // Tri des absences par date croissante
//     absences.sort((a, b) => new Date(a.date_start) - new Date(b.date_start));

//     let colors = [];
//     let rsc = [];
//     let totalDays = (new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24);
//     // console.log("ID", idref, "Start:", startDate, "End:", endDate, "TotalDays:", totalDays);  

//     // if (totalDays <= 0) {
//     //     return { gradient: "white", badges: "" };
//     // }

//     let lastEndPercent = 0;
//     let absenceHorsPeriode = false;
//     let hasAbsences = false; // Variable pour savoir s'il y a au moins une absence

    
//     absences.forEach(abs => {
//         let startDiff = (new Date(abs.date_start) - new Date(startDate)) / (1000 * 60 * 60 * 24);
//         let endDiff = (new Date(abs.date_end) - new Date(startDate)) / (1000 * 60 * 60 * 24);

//         let startPercent = (startDiff / totalDays) * 100;
//         let endPercent = (endDiff / totalDays) * 100;

//         startPercent = Math.max(0, Math.min(100, startPercent));
//         endPercent = Math.max(0, Math.min(100, endPercent));

//         // Si l'absence est totalement hors période
//         if (endDiff < 0 || startDiff > totalDays) {
//             absenceHorsPeriode = true;
//             return;
//         }

//         let color = getStatusColor(abs.status);
//         let badgeLabel = getStatusLabel(abs.status);

//         // Un espace entre deux absences si nécessaire
//         if (startPercent > lastEndPercent) {
//             colors.push(`${getDefaultColor(idref)} ${lastEndPercent.toFixed(2)}% ${startPercent.toFixed(2)}%`);
//         }

//         // Ajout de la couleur de l'absence
//         colors.push(`${color} ${startPercent.toFixed(2)}% ${endPercent.toFixed(2)}%`);
//         lastEndPercent = endPercent;

//         // Au moins une absence
//         hasAbsences = true;

//         // Ajout du détail de l'absence dans "rsc"
//         rsc.push(`
//             <span class="badge badge-info" 
//                 style="background-color: ${color}; text-align: left; border-radius: 12px;
//                     box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); display: inline-flex;"
//                 title="Absence : ${abs.conge_label}
//                     Période : ${abs.date_start} → ${abs.date_end}
//                     Durée : ${abs.nb_open_day_calculated} jours
//                     Statut : ${badgeLabel}">
//                 <span style="font-weight: bold; margin-right: 5px;">${badgeLabel}</span> 
//                 <span style="opacity: 0.8; font-size: 0.85em; background: rgba(0, 0, 0, 0.1); padding: 1px 3px; border-radius: 8px;">
//                     ${abs.conge_label}
//                 </span>
//                 <span style="margin-left: 8px; font-size: 0.9em;">(${abs.nb_open_day_calculated} jours)</span>
//             </span>
//         `);

//     });

//     // Ajout badge spécifique si absences hors période
//     let badges = "";
//     if (hasAbsences) {
//         badges += `
//             <span class="badge badge-info" 
//                 style="background-color: RGB(0, 117, 168, 0.8); color: white; text-align: left;" 
//                 title="Ce salarié a des absences dans la période du projet">
//                 Présence d'absences
//             </span>
//         `;
//     }
    
//     if (absenceHorsPeriode) {
//         badges += `
//             <span class="badge badge-warning" 
//                 style="background-color: #ff9800; color: white; text-align: left;" 
//                 title="Certaines absences de ce salarié ne sont pas dans la période du projet">
//                 Absences hors période
//             </span>
//         `;
//     }

//     // Couleur par défaut si nécessaire
//     if (lastEndPercent < 100) {
//         colors.push(`${getDefaultColor(idref)} ${lastEndPercent.toFixed(2)}% 100%`);
//     }

//     return {
//         gradient: `linear-gradient(to right, ${colors.join(", ")})`,
//         badges: badges,
//         rsc: rsc.join(" ")
//     };
// }


// function getDefaultColor(idref) {
//     return idref === "CO" ? "rgb(108, 152, 185)" :
//            idref === "PR" ? "rgb(160, 173, 58)" :
//            idref === "PROJ" ? "rgba(0, 0, 0, 0.4)" : "rgb(108, 152, 185)";
// }

// function getStatusColor(status) {
//     return status == 3 ? "rgba(60, 120, 20, 0.85)" : // Validé (Vert)
//            status == 6 ? "rgba(180, 30, 30, 0.9)" : // Appro. 2 (Rouge)
//            status == 2 ? "rgba(255, 200, 0, 0.85)" : // Appro. 1 (Jaune)
//            "rgb(108, 152, 185)";
// }

// function getStatusLabel(status) {
//     return status == 3 ? "Validé" :
//            status == 6 ? "Appro. 2" :
//            status == 2 ? "Appro. 1" : "";
// }

// function formatDateTimeFR(date) {
//     return new Date(date).toLocaleString("fr-FR", { 
//         day: "2-digit", month: "long", year: "numeric", 
//         hour: "2-digit", minute: "2-digit", second: "2-digit"
//     });
// }

// function injectDynamicStyles(css) {
//     let styleTag = document.getElementById("dynamic-gantt-styles");
    
//     if (!styleTag) {
//         styleTag = document.createElement("style");
//         styleTag.id = "dynamic-gantt-styles";
//         document.head.appendChild(styleTag);
//     }

//     styleTag.innerHTML = css;
// }


// Fonction pour mettre à jour le Gantt avec les données filtrées
// function updateGanttChartProjAbs(ressources, filteredData) {
//     // Traitement des ressources et mise à jour du Gantt
//     const processedMembers = processRessourcesProjAbs(ressources, filteredData); 

//     // Mise à jour du Gantt avec les nouveaux membres
//     setupGanttChartProjAbs(processedMembers);
// }

// function setupGanttChartProjAbs(members) {
//     if (!Array.isArray(members)) {
//         console.error("Erreur : 'members' n'est pas un tableau.");
//         return;
//     }

//     var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV6'), 'month');

//     var booShowRessources = 1;
// 	var booShowDurations = 1;
// 	var booShowComplete = 1;
// 	var barText = "Resource";
// 	var graphFormat = "day";
	
// 	g.setShowRes(0); 		// Show/Hide Responsible (0/1)
// 	g.setShowDur(0); 		// Show/Hide Duration (0/1)
// 	g.setShowComp(0); 		// Show/Hide % Complete(0/1)
// 	g.setShowStartDate(0); 	// Show/Hide % Complete(0/1)
// 	g.setShowEndDate(0); 	// Show/Hide % Complete(0/1)
// 	g.setShowTaskInfoLink(1);
// 	g.setFormatArr("day","week","month", "quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
// 	g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
// 	g.setUseFade(0);
// 	g.setDayColWidth(20);
// 	/* g.setShowTaskInfoLink(1) */
// 	g.addLang('<?php print $langs->getDefaultLang(1); ?>', vLangs['<?php print $langs->getDefaultLang(1); ?>']);
// 	g.setLang('<?php print $langs->getDefaultLang(1); ?>');
    
//     if (g.getDivId() != null) {
//         let level = 0;
//         const tnums = members.length;
//         let old_member_id = 0;
        

//         // Parcourir les membres triés
//         for (let tcursor = 0; tcursor < tnums; tcursor++) {
//             const t = members[tcursor];

//             if (!old_member_id || old_member_id !== t['member_member_id']) {
//                 const tmpt = {
//                     member_id: `-${t['member_member_id']}`,
//                     member_alternate_id: `-${t['member_member_id']}`,
//                     member_name: t['member_projet_ref'],
//                     member_idref: t['member_idref'],
//                     member_resources: 'ABS',
//                     member_start_date: '',
//                     member_start_date2: '',
//                     member_end_date: '',
//                     member_is_group: 1,
//                     member_position: 0,
//                     member_css: 'ggroupblack',
//                     member_milestone: 0,
//                     member_parent: 0,
//                     member_parent_alternate_id: 0,
//                     member_notes: '',
//                     member_planned_workload: 0
//                 };

//                 const result = constructGanttLineColProj(members, tmpt, [], 0, t['member_member_id']);
//                 // console.log("Groupe ajouté :", result);

//                 g.AddTaskItem(new JSGantt.TaskItem(
//                     result.id,
//                     result.name,
//                     result.start,
//                     result.end,
//                     result.class,
//                     result.link,
//                     result.milestone,
//                     result.resource,
//                     result.complete,
//                     result.group,
//                     result.parent,
//                     result.open,
//                     result.depends,
//                     result.caption,
//                     result.note,
//                     g
//                 ));

//                 old_member_id = t['member_member_id'];
//             }

//             if (t["member_parent"] <= 0) {
//                 const result = constructGanttLineColProj(members, t, [], level, t['member_member_id']);
//                 // console.log("Tâche ajoutée :", result);
          
//                 g.AddTaskItem(new JSGantt.TaskItem(
//                     result.id,
//                     result.name,
//                     result.start,
//                     result.end,
//                     result.class,
//                     result.link,
//                     result.milestone,
//                     result.resource,
//                     result.complete,
//                     result.group,
//                     result.parent,
//                     result.open,
//                     result.depends,
//                     result.caption,
//                     result.note,
//                     g
//                 ));

//                 findChildGanttLine(members, t["member_id"], [], level + 1);
//             }
//         }
       
//         g.Draw(jQuery("#tabs6").width() - 40);
//         setTimeout(() => g.DrawDependencies(), 100);
//     } else {
//         alert("Graphique Gantt introuvable !");
//     }
// }


// function constructGanttLineColProj(members, member, memberDependencies = [], level = 0, memberId = null) {
//     const dateFormatInput62 = "YYYY-MM-DD";

//     let startDate = member["member_start_date"];
//     let endDate = member["member_end_date"] || startDate;

//     startDate = formatDate(startDate, dateFormatInput62);
//     endDate = formatDate(endDate, dateFormatInput62);

//     const resources = member["member_resources"] || '';
//     const parent = memberId && level < 0 ? `-${memberId}` : member["member_parent_alternate_id"];
//     const percent = member['member_percent_complete'];
//     const css = member['member_css'];
	
// 	const name = member['member_name'];
	
   
//     const lineIsAutoGroup = member["member_is_group"];
//     const dependency = '';
//     const memberIdAlt = member["member_alternate_id"];

//     let note = member["member_rsc_detail"] || '';
//     // note += `\nPlan de charge : Cliquez sur Plus d\'informations pour être dirigé vers les contacts`;
//     note += `&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`;

// 	let link = ''; 
// 	var DOL_URL_ROOT = "<?php echo DOL_URL_ROOT; ?>";

// 	if (!member.hasOwnProperty("member_idref")) {
// 		// Si "member_idref" n'existe pas, on laisse link vide
// 		link = '';
// 	} else if (member["member_idref"] === "CO") {
// 		link = DOL_URL_ROOT + '/commande/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
// 	} else if (member["member_idref"] === "PR") {
// 		link = DOL_URL_ROOT + '/comm/propal/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
// 	} else if (member["member_idref"] === "PROJ") {
// 		link = DOL_URL_ROOT + '/projet/contact.php?withproject=1&id=' + Math.abs(member["member_id"]);
// 	}
	
//     return {
//         id: memberIdAlt,
//         name: name,
//         start: startDate,
//         end: endDate,
//         class: css,
//         link: link,
//         milestone: member['member_milestone'],
//         resource: resources,
//         complete: percent >= 0,
//         group: lineIsAutoGroup,
//         parent: parent,
//         open: 1,
//         depends: dependency,
//         caption: lineIsAutoGroup === 0 && percent >= 0 ? `${percent}%` : '',
//         note: note
//     };
// }


// // Simule la conversion de date (Dolibarr `dol_print_date`)
// function formatDate(date, format) {
// 	// Implémentez une fonction de conversion de date au besoin (ex: moment.js ou day.js)
// 	return date; // Placeholder
// }

// function formatDate(dateString, format = 'yyyy-MM-dd') {
//     if (!dateString) return null;
//     const date = new Date(dateString);
//     if (isNaN(date.getTime())) return null; // Vérifie si la date est valide
//     return date.toISOString().split('T')[0]; // Retourne la date au format YYYY-MM-DD
// }


// // échappe les chaénes pour JS
// function escapeJs(str) {
// 	return str.replace(/'/g, "\\'").replace(/\n/g, "\\n");
// }

// // Convertit les secondes en heures/minutes
// function convertSecondsToTime(seconds, format) {
// 	const hours = Math.floor(seconds / 3600);
// 	const minutes = Math.floor((seconds % 3600) / 60);
// 	return `${hours}h ${minutes}min`;
// }

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
			constructGanttLineColProj(taskArray, task, memberDependencies, level, null);

			// Recherche récursive des enfants
			findChildGanttLine(taskArray, task["member_id"], memberDependencies, level + 1);
		}
	}
}

	
</script>
<?php

