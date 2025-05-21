
<?php

?>
<style>
/* Conteneur des filtres */
.container.filter_menu_wrapper {
    margin: 20px 0;
}

/* Sélecteurs multiples */
select {
    width: 100%; 
    max-width: 100%; 
    height: 30px; 
    padding: 5px 10px;
    font-size: 12px;
    color: #333333;
    background-color: #ffffff;
    border: 1px solid #cccccc;
    border-radius: 4px;
    appearance: none; /* Supprime l'apparence native sur certains navigateurs */
    /* box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); */
    /* transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; */
    border-top: none;
    border-left: none;
    border-right: none;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

/* Style au survol et à la sélection */
select:hover {
    border-color: #0073e6;
}

select:focus {
    border-color: #0056b3;
    box-shadow: 0 0 4px rgba(0, 115, 230, 0.5);
    outline: none; /* Supprime le contour par défaut */
}

/* Icônes à côté des sélecteurs */
.td_filter div {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pictofixedwidth {
    width: 20px;
    height: 20px;
    flex-shrink: 0; /* Empêche l'icône de se réduire */
}

/* Centrer les icônes */
td div img {
    vertical-align: middle;
}

/* Responsivité pour les petits écrans */
@media (max-width: 768px) {
    #fichecenter {
        width: 95%;
    }

    table.noborder td {
        display: block;
        width: 100%;
        padding: 10px;
    }

    td div {
        flex-direction: column;
        align-items: flex-start;
    }

    select {
        width: 100%; /* Force la largeur complète sur petits écrans */
    }
}

/* Dropdown de Select2 (la liste déroulante) */
.select2-dropdown {
    max-width: 100%; /* Assurez-vous que la liste déroulante ne dépasse pas */
    overflow-x: hidden; /* Pas de défilement horizontal */
    word-wrap: break-word; /* Coupe les mots trop longs */
}





.table_filter {
    width: 100%; /* Le tableau prend toute la largeur du conteneur */
    table-layout: fixed; /* Fixe la largeur des colonnes */
}

.td_filter {
    width: 33%; /* Chaque colonne prend 33% de la largeur totale */
    overflow: hidden; /* Empêche le contenu de dépasser */
    text-overflow: ellipsis; /* Ajoute des "..." si le contenu dépasse */
    white-space: nowrap; /* Empêche le texte de passer à la ligne */
}
</style>
<?php

print '<div id="fichecenter" class="fichecenter">';
print '<div class="container filter_menu_wrapper">';

print '<table class="table_filter noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="liste_titre" colspan="3">'.$langs->trans("Recherche avancée").'</td>';

print '</tr>';

// Ligne 1 : Salariés, Emploi, Compétence
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Salariés") . ' &nbsp;';
print img_picto('', 'user', 'class="pictofixedwidth"');
print '<select id="userFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Emplois") . ' &nbsp;';
print img_picto('', 'skill', 'class="pictofixedwidth"');
print '<select id="jobFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Compétences") . ' &nbsp;';
print img_picto('', 'skill', 'class="pictofixedwidth"');
print '<select id="skillFilter" multiple></select>';
print '</div>';
print '</td>';
print '</tr>';

// Ligne 2 : Projets, Commandes, Devis
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Projets") . ' &nbsp;';
print img_picto('', 'project', 'class="pictofixedwidth"');
print '<select id="projectFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Commandes") . ' &nbsp;';
print img_picto('', 'order', 'class="pictofixedwidth"');
print '<select id="orderFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Devis") . ' &nbsp;';
print img_picto('', 'propal', 'class="pictofixedwidth"');
print '<select id="propalFilter" multiple></select>';
print '</div>';
print '</td>';
print '</tr>';

// Ligne 3 : Groupes, Agences, Domaines
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Groupes") . ' &nbsp;';
print img_picto('', 'group', 'class="pictofixedwidth"');
print '<select id="groupFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Agences") . ' &nbsp;';
print img_picto('', 'building', 'class="pictofixedwidth"');
print '<select id="agenceFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Domaines") . ' &nbsp;';
print img_picto('', 'building', 'class="pictofixedwidth"');
print '<select id="domFilter" multiple></select>';
print '</div>';
print '</td>';
print '</tr>';

// Ligne 4 : Responsables projet, Responsable antenne
print '<tr>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Responsables de projet") . ' &nbsp;';
print img_picto('', 'user', 'class="pictofixedwidth"');
print '<select id="respProjFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';
print $langs->trans("Responsables d’antenne") . ' &nbsp;';
print img_picto('', 'user', 'class="pictofixedwidth"');
print '<select id="resAntFilter" multiple></select>';
print '</div>';
print '</td>';
print '<td class="td_filter">';
print '<div>';

print '</div>';
print '</td>';
print '</tr>';


print '</table>';
print '</div>'; // Fin de container
print '</div>'; // Fin de fichecenter
