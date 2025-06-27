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
function processRessourcesProjAbs(ressources, filteredData) {
    // const members = [];
    let memberCursor = 0;

    const ganttMode = document.getElementById('ganttMode');
    const ganttContainer = document.getElementById('ganttContainer');

    // Variable pour stocker les ressources filtrées
    let filteredResources = ressources; // Initialisation avec toutes les ressources

    // Génération initiale des membres (par défaut avec toutes les ressources)
    const initialMembers = generateMembersProjAbs(filteredResources, filteredData);
    // console.log("Membres initiaux générés :", initialMembers);

    // Tri : non-structuré d'abord
        initialMembers.sort((a, b) => {
            const aIsStruct = a.member_str ? 1 : 0;
            const bIsStruct = b.member_str ? 1 : 0;
            return aIsStruct - bIsStruct;
        });

    // Membres traités
    return initialMembers;
}


function generateMembersProjAbs(ressources, filteredData) {
    let members = [];
    let memberCursor = 0;
    let dynamicStyles = "";

    // if (!Array.isArray(ressources)) {
    //     console.error("Erreur : 'ressources' est undefined ou n'est pas un tableau !");
    //     return [];
    // }

    let structurelLabels = [];
    let structureMembers = [];
    let startMin = null;
    let endMax = null;
   
    if(!ressources) {
        // Si les ressources sont nulles, afficher un message
        console.log("Aucune donnée disponible");
    // document.getElementById("message-container").innerHTML = "Aucune donnée disponible";
    }else{
        
        ressources.forEach(val => {
            const condition = (val.fk_projet !== null || typeof val.fk_projet === "undefined");

            if (condition) {
                const idParent = val.s ? val.fk_projet : `-${val.fk_projet}`;
                let todayPlusOneYear = new Date();
                todayPlusOneYear.setFullYear(todayPlusOneYear.getFullYear() + 1);
                let formattedDate = todayPlusOneYear.toISOString().split('T')[0];
                // isStructurel = val.str == 1;
                // structurelLabel = isStructurel ? `[Structure] ${val.projet_ref}` : val.projet_ref;
                // console.log('Label structurel : ', structurelLabel);
                let isStructurel = val.str == 1;
                let cleanRef = (val.projet_ref || '').replace(/\[Structure\]\s*/i, '').trim(); 
                let badgeStructure = `<span class="badge badge-warning" style="margin-right: 6px; background-color: #f4b300; color: #000; border-radius: 6px; padding: 2px 6px; font-weight: bold;">Structurel</span>`;
                let structurelLabel = isStructurel ? `${badgeStructure}${cleanRef}` : cleanRef;
               
                const member = {
                    member_id: val.fk_projet,
                    member_idref: val.idref,
                    member_alternate_id: memberCursor + 1,
                    member_member_id: val.fk_projet,
                    member_name_html: val.name_html,
                    member_projet_ref: structurelLabel,
                    member_parent: idParent,
                    member_str: isStructurel,
                    member_is_group: 0,
                    member_css: '', // Classe CSS générée dynamiquement
                    member_milestone: '0',
                    member_name: val.name_html,
                    member_start_date: formatDate(val.date_start, 'yyyy-MM-dd'),
                    member_end_date: val.date_end 
                    ? formatDate(val.date_end, 'yyyy-MM-dd') 
                    : formattedDate,

                    member_color: 'b4d1ea',
                    member_resources: '',
                    member_rsc_detail: ''
                };

                if (!val.date_start) return;

                let start = new Date(val.date_start);
                let end = val.date_end ? new Date(val.date_end) : new Date();

                if (!startMin || start < startMin) startMin = start;
                if (!endMax || end > endMax) endMax = end;

                // let gradientCss = generateGradientForAbsences(val.periodes, val.date_start, member.member_end_date, val.idref);
                // const className = `custom-gradient-${val.idref}-${memberCursor}`;
                // dynamicStyles += `.${className} { width: 200px; height: 13px; background: ${gradientCss}; }\n`;
                let result = generateGradientForAbsences(val.periodes, val.date_start, member.member_end_date, val.idref);
                const className = `custom-gradient-${val.idref}-${memberCursor}`;
                member.member_resources = result.badges; // On affiche les badges
                member.member_rsc_detail = result.rsc;

                dynamicStyles += `.${className} { 
                    height: 13px; 
                    background: ${result.gradient}; 
                }\n`;
                // width: 200px; 
                // border: 1px solid rgba(0, 0, 0, 0.2); 
                // box-shadow: rgba(0, 0, 0, 0.3); 
                // background-blend-mode: multiply; 
                member.member_css = className;
                member.projet_ref = structurelLabel;

                // members.push(member);
                // Tri 
                if (isStructurel) {
                    structureMembers.push(member);
                } else {
                    members.push(member);
                }
                memberCursor++;
            }
        });
    }

    injectDynamicStyles(dynamicStyles);
    
    // Concatènation des non-structurels puis les structurels
    members = members.concat(structureMembers);

    
    // Mise à jour les parents des tâches
    members.forEach(member => {
        const parent = members.find(m => m.member_id === member.member_parent);
        member.member_parent_alternate_id = parent ? parent.member_alternate_id : member.member_parent;
    });

    return members;
}

function generateGradientForAbsences2(absences, startDate, endDate, idref) {
    if (!Array.isArray(absences) || absences.length === 0) {
        const defaultColor = getDefaultColor(idref);
        return {
            gradient: `linear-gradient(to right, ${defaultColor} 0%, ${defaultColor} 100%)`,
            badges: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333;">Aucune absence</span>`,
            rsc: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333;">Aucune absence</span>`
        };
    }

    // Fonction pour détecter une demi-journée en fonction des heures
    function isHalfDayAbsence(abs) {
        const start = new Date(abs.date_start);
        const end = new Date(abs.date_end);
        const sameDay = start.toDateString() === end.toDateString();
        const startHour = start.getHours();
        const endHour = end.getHours();
        return sameDay && ((endHour <= 12 && startHour < endHour) || (startHour >= 12 && endHour > startHour));
    }

    function getMinWidthForAbsence(abs) {
        return isHalfDayAbsence(abs) ? 0.03 : 0.05;
    }

    absences.sort((a, b) => new Date(a.date_start) - new Date(b.date_start));

    const start = new Date(startDate);
    const end = new Date(endDate);
    const totalDays = (end - start) / (1000 * 60 * 60 * 24);

    let segments = [];
    let rsc = [];
    let hasAbsences = false;
    let absenceHorsPeriode = false;

    absences.forEach(abs => {
        let startAbs = new Date(abs.date_start);
        let endAbs = new Date(abs.date_end);

        if (endAbs < start || startAbs > end) {
            absenceHorsPeriode = true;
            return;
        }

        const clampedStart = startAbs < start ? start : startAbs;
        const clampedEnd = endAbs > end ? end : endAbs;

        const offsetStart = (clampedStart - start) / (1000 * 60 * 60 * 24);
        const offsetEnd = (clampedEnd - start) / (1000 * 60 * 60 * 24);

        let startPercent = (offsetStart / totalDays) * 100;
        let endPercent = (offsetEnd / totalDays) * 100;

        startPercent = Math.max(0, Math.min(100, startPercent));
        endPercent = Math.max(0, Math.min(100, endPercent));

        const minWidth = getMinWidthForAbsence(abs);
        if (endPercent - startPercent < minWidth) {
            endPercent = startPercent + minWidth;
        }

        const color = getStatusColor(abs.status);
        const badgeLabel = getStatusLabel(abs.status);

        segments.push({
            start: startPercent,
            end: endPercent,
            color
        });

        const absDaysOrNb = abs.idref === 'FH'
        ? `${countWorkingDays(abs.date_start, abs.date_end)} jour(s)`
        : `${abs.nb_open_day_calculated} jour(s)`;

        rsc.push(`
        <span class="badge badge-info" 
            style="background-color: ${color}; text-align: left; border-radius: 12px;
                box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); display: inline-flex;"
            title="Absence : ${abs.conge_label}
                Période : ${abs.date_start} → ${abs.date_end}
                Statut : ${badgeLabel}">
            <span style="font-weight: bold; margin-right: 5px;">${badgeLabel}</span> 
            <span style="opacity: 0.8; font-size: 0.85em; background: rgba(0, 0, 0, 0.1); padding: 1px 3px; border-radius: 8px;">
                ${abs.conge_label}
            </span>
            <span style="margin-left: 8px; font-size: 0.9em;">(${abs.date_start} → ${abs.date_end})</span>
            <span style="margin-left: 8px; font-size: 0.9em;">${absDaysOrNb}</span>
        </span>
    `);

        hasAbsences = true;
    });

    const defaultColor = getDefaultColor(idref);
    let colors = [];
    segments.sort((a, b) => a.start - b.start);
    let cursor = 0;

    segments.forEach(seg => {
        if (seg.start > cursor) {
            colors.push(`${defaultColor} ${cursor.toFixed(2)}% ${seg.start.toFixed(2)}%`);
        }
        colors.push(`${seg.color} ${seg.start.toFixed(2)}% ${seg.end.toFixed(2)}%`);
        cursor = Math.max(cursor, seg.end);
    });

    if (cursor < 100) {
        colors.push(`${defaultColor} ${cursor.toFixed(2)}% 100%`);
    }

    let badges = "";
    if (hasAbsences) {
        badges += `
            <span class="badge badge-info" 
                style="background-color: RGB(0, 117, 168, 0.8); color: white; text-align: left;" 
                title="Ce salarié a des absences dans la période du projet">
                Présence d'absences
            </span>
        `;
    }

    if (absenceHorsPeriode) {
        badges += `
            <span class="badge badge-warning" 
                style="background-color: #ff9800; color: white; text-align: left;" 
                title="Certaines absences de ce salarié ne sont pas dans la période du projet">
                Absences hors période
            </span>
        `;
    }

    return {
        gradient: `linear-gradient(to right, ${colors.join(", ")})`,
        badges: badges,
        rsc: rsc.join(" ")
    };
}

function generateGradientForAbsences(absences, startDate, endDate, idref) {
    if (!Array.isArray(absences) || absences.length === 0) {
        const defaultColor = getDefaultColor(idref);
        return {
            gradient: `linear-gradient(to right, ${defaultColor} 0%, ${defaultColor} 100%)`,
            badges: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333;">Aucune absence</span>`,
            rsc: `<span class="badge badge-secondary" style="background-color: #ccc; color: #333;">Aucune absence</span>`
        };
    }

    function isHalfDayAbsence(abs) {
        const start = new Date(abs.date_start);
        const end = new Date(abs.date_end);
        const sameDay = start.toDateString() === end.toDateString();
        const startHour = start.getHours();
        const endHour = end.getHours();
        return sameDay && ((endHour <= 12 && startHour < endHour) || (startHour >= 12 && endHour > startHour));
    }

    function getMinWidthForAbsence(abs) {
        return isHalfDayAbsence(abs) ? 0.03 : 0.05;
    }

    absences.sort((a, b) => new Date(a.date_start) - new Date(b.date_start));
    const start = new Date(startDate);
    const end = new Date(endDate);
    const totalDays = (end - start) / (1000 * 60 * 60 * 24);

    let rawSegments = [];
    let rsc = [];
    let hasAbsences = false;
    let absenceHorsPeriode = false;

    absences.forEach(abs => {
        let startAbs = new Date(abs.date_start);
        let endAbs = new Date(abs.date_end);
        if (endAbs < start || startAbs > end) {
            absenceHorsPeriode = true;
            return;
        }

        const clampedStart = startAbs < start ? start : startAbs;
        const clampedEnd = endAbs > end ? end : endAbs;

        const offsetStart = (clampedStart - start) / (1000 * 60 * 60 * 24);
        const offsetEnd = (clampedEnd - start) / (1000 * 60 * 60 * 24);

        let startPercent = (offsetStart / totalDays) * 100;
        let endPercent = (offsetEnd / totalDays) * 100;
        const minWidth = getMinWidthForAbsence(abs);

        startPercent = Math.max(0, Math.min(100, startPercent));
        endPercent = Math.max(startPercent + minWidth, Math.min(100, endPercent));

        const color = getStatusColor(abs.status);
        const badgeLabel = getStatusLabel(abs.status);

        rawSegments.push({ start: startPercent, end: endPercent, color, status: abs.status });

        const absDaysOrNb = abs.idref === 'FH'
            ? `${countWorkingDays(abs.date_start, abs.date_end)} jour(s)`
            : `${abs.nb_open_day_calculated} jour(s)`;

        rsc.push(`
        <span class="badge badge-info" 
            style="background-color: ${color}; text-align: left; border-radius: 12px;
                box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); display: inline-flex;"
            title="Absence : ${abs.conge_label}
                Période : ${abs.date_start} → ${abs.date_end}
                Statut : ${badgeLabel}">
            <span style="font-weight: bold; margin-right: 5px;">${badgeLabel}</span> 
            <span style="opacity: 0.8; font-size: 0.85em; background: rgba(0, 0, 0, 0.1); padding: 1px 3px; border-radius: 8px;">
                ${abs.conge_label}
            </span>
            <span style="margin-left: 8px; font-size: 0.9em;">(${abs.date_start} → ${abs.date_end})</span>
            <span style="margin-left: 8px; font-size: 0.9em;">${absDaysOrNb}</span>
        </span>
        `);
        hasAbsences = true;
    });

    // Séparer les segments orange (null) et les autres
    let orangeSegments = rawSegments.filter(s => s.status == null);
    let topSegments = rawSegments.filter(s => s.status != null);

    // Découper les segments orange chevauchés
    topSegments.forEach(top => {
        orangeSegments = orangeSegments.flatMap(base => {
            if (top.end <= base.start || top.start >= base.end) return [base];

            let result = [];
            if (top.start > base.start) {
                result.push({ start: base.start, end: top.start, color: "rgb(217, 123, 33)" });
            }
            if (top.end < base.end) {
                result.push({ start: top.end, end: base.end, color: "rgb(217, 123, 33)" });
            }
            return result;
        });
    });

    // Fusionner tous les segments
    const defaultColor = getDefaultColor(idref);
    let segments = [...topSegments.map(s => ({ start: s.start, end: s.end, color: s.color })), ...orangeSegments];
    segments.sort((a, b) => a.start - b.start);

    let colors = [];
    let cursor = 0;

    segments.forEach(seg => {
        if (seg.start > cursor) {
            colors.push(`${defaultColor} ${cursor.toFixed(2)}% ${seg.start.toFixed(2)}%`);
        }
        colors.push(`${seg.color} ${seg.start.toFixed(2)}% ${seg.end.toFixed(2)}%`);
        cursor = Math.max(cursor, seg.end);
    });

    if (cursor < 100) {
        colors.push(`${defaultColor} ${cursor.toFixed(2)}% 100%`);
    }

    let badges = "";
    if (hasAbsences) {
        badges += `<span class="badge badge-info" 
            style="background-color: RGB(0, 117, 168, 0.8); color: white; text-align: left;" 
            title="Ce salarié a des absences dans la période du projet">
            Présence d'absences
        </span>`;
    }

    if (absenceHorsPeriode) {
        badges += `<span class="badge badge-warning" 
            style="background-color: #a47148; color: white; text-align: left;" 
            title="Certaines absences de ce salarié ne sont pas dans la période du projet">
            Absences hors période
        </span>`;
    }

    return {
        gradient: `linear-gradient(to right, ${colors.join(", ")})`,
        badges: badges,
        rsc: rsc.join(" ")
    };
}


function getDefaultColor(idref) {
    return idref === "PROJ" ? "rgba(0, 0, 0, 0.4)" : "rgb(108, 152, 185)";
}

function getStatusColor(status) {
    return status == null ? "rgb(217, 123, 33)" : 
           status == 3 ? "rgba(60, 120, 20, 0.85)" : // Validé (Vert)
           status == 6 ? "rgba(180, 30, 30, 0.9)" : // Appro. 2 (Rouge)
           status == 2 ? "rgba(255, 200, 0, 0.85)" : // Appro. 1 (Jaune)
           "black";
}

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

function getStatusLabel(status) {
    return status == 3 ? "Validé" :
           status == 6 ? "Appro. 2" :
           status == 2 ? "Appro. 1" : "En formation";
}


function formatDateTimeFR(date) {
    return new Date(date).toLocaleString("fr-FR", { 
        day: "2-digit", month: "long", year: "numeric", 
        hour: "2-digit", minute: "2-digit", second: "2-digit"
    });
}

function injectDynamicStyles(css) {
    let styleTag = document.getElementById("dynamic-gantt-styles");
    
    if (!styleTag) {
        styleTag = document.createElement("style");
        styleTag.id = "dynamic-gantt-styles";
        document.head.appendChild(styleTag);
    }

    styleTag.innerHTML = css;
}
// Fonction pour mettre à jour le Gantt avec les données filtrées
function updateGanttChartProjAbs(ressources, filteredData) {
    // Traitement des ressources et mise à jour du Gantt
    const processedMembers = processRessourcesProjAbs(ressources, filteredData); 

    // Mise à jour du Gantt avec les nouveaux membres
    setupGanttChartProjAbs(processedMembers);
}

function setupGanttChartProjAbs(members) {
    if (!Array.isArray(members)) {
        console.error("Erreur : 'members' n'est pas un tableau.");
        return;
    }

    var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV6'), 'month');

    var booShowRessources = 1;
	var booShowDurations = 1;
	var booShowComplete = 1;
	var barText = "Resource";
	var graphFormat = "day";
	
	g.setShowRes(0); 		// Show/Hide Responsible (0/1)
	g.setShowDur(0); 		// Show/Hide Duration (0/1)
	g.setShowComp(0); 		// Show/Hide % Complete(0/1)
	g.setShowStartDate(0); 	// Show/Hide % Complete(0/1)
	g.setShowEndDate(0); 	// Show/Hide % Complete(0/1)
	g.setShowTaskInfoLink(1);
	g.setFormatArr("day","week","month", "quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
	g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
	g.setUseFade(0);
	g.setDayColWidth(20);
	/* g.setShowTaskInfoLink(1) */
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
                    member_id: `-${t['member_member_id']}`,
                    member_alternate_id: `-${t['member_member_id']}`,
                    member_name: t['member_projet_ref'],
                    member_idref: t['member_idref'],
                    member_resources: 'ABS',
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

                const result = constructGanttLineProjAbs(members, tmpt, [], 0, t['member_member_id']);
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
                const result = constructGanttLineProjAbs(members, t, [], level, t['member_member_id']);
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
       
        g.Draw(jQuery("#tabs6").width() - 40);
        setTimeout(() => g.DrawDependencies(), 100);
    } else {
        alert("Graphique Gantt introuvable !");
    }
}


function constructGanttLineProjAbs(members, member, memberDependencies = [], level = 0, memberId = null) {
    const dateFormatInput62 = "YYYY-MM-DD";
  
    let startDate = member["member_start_date"];
    let endDate = member["member_end_date"] || startDate;

    startDate = formatDate(startDate, dateFormatInput62);
    endDate = formatDate(endDate, dateFormatInput62);

    const resources = member["member_resources"] || '';
    const parent = memberId && level < 0 ? `-${memberId}` : member["member_parent_alternate_id"];
    const percent = member['member_percent_complete'];
    const css = member['member_css'];
	
	const name = member['member_name'];
	
   
    const lineIsAutoGroup = member["member_is_group"];
    const dependency = '';
    const memberIdAlt = member["member_alternate_id"];

    let note = member["member_rsc_detail"] || '';
    // note += `\nPlan de charge : Cliquez sur Plus d\'informations pour être dirigé vers les contacts`;
    note += `&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`;

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
	
</script>
<?php

