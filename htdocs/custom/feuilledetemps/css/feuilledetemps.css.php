<?php
/* Copyright (C) 2021 METZGER Leny <l.metzger@optim-industries.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    feuilledetemps/css/feuilledetemps.css.php
 * \ingroup feuilledetemps
 * \brief   CSS file for module FeuilleDeTemps.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if ( !defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if ( !defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}

if ( !defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}

if ( !defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login
}

//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if ( !defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}

if ( !defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.

// Load Dolibarr environment
$res =0;

// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( !$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res =@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}

// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp =empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 =realpath(__FILE__);
$i =strlen($tmp) - 1;
$j =strlen($tmp2) - 1;

while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) {
	$i--;
	$j--;
}

if ( !$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res =@include substr($tmp, 0, ($i + 1))."/main.inc.php";
}

if ( !$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res =@include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}

// Try main.inc.php using relative path
if ( !$res && file_exists("../../main.inc.php")) {
	$res =@include "../../main.inc.php";
}

if ( !$res && file_exists("../../../main.inc.php")) {
	$res =@include "../../../main.inc.php";
}

if ( !$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login'])) {
	$user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');

// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
}

else {
	header('Cache-Control: no-cache');
}

?>

div.mainmenu.feuilledetemps #mainmenuspan_feuilledetemps::before {
	content: url(../img/object_timesheet_20.png);
}

.fa-dol-feuilledetemps::before {
	content: url(../img/object_timesheet_32.png);
}

div.mainmenu.feuilledetemps {
	background-image: none;
}



/* Dimenssions */ 

#mainbody.feuilledetemps #id-container {
	height: calc(100vh - 54px);
  	display: block;
}

#mainbody.feuilledetemps #id-right {
	display: flex;
  	flex-direction: column;
 	height: calc(100% - 33px);
}

#mainbody.feuilledetemps #id-right div.fiche div.tabs {
	height: auto;
}

#mainbody.feuilledetemps #id-right div.fiche.tab {
	flex: 1 1 100%;
  	height: 0;
}

#mainbody.feuilledetemps #id-right div.fiche {
	margin-bottom: 20px;
}

#mainbody.feuilledetemps #id-right div.fiche.tab #feuilleDeTempsForm {
	height: 100%;
	display: flex;
	flex-direction: column;
}

#mainbody.feuilledetemps:not(.displaycolumn) #id-right div.fiche form:not(.notoptoleftroright) {
	height: calc(100vh - 400px);
	display: flex;
	flex-direction: column;
}

#mainbody.feuilledetemps.displaycolumn #id-right div.fiche form:not(.notoptoleftroright) {
	height: calc(100vh - 200px);
	display: flex;
	flex-direction: column;
}

#mainbody.feuilledetemps #id-right div.fiche.tab #feuilleDeTempsForm div.div-table-responsive {
	display: flex;
}

/* #feuilleDeTempsForm .div-table-responsive {
	overflow-x: unset;
} */

#observationFDT {
	max-height: 130px;
}



/* Définition des couleurs de fond pour chaque cases du tableau des feuilles de temps (weekend, anticipé, différents types de congés, fériés, ...) */

#tablelines_fdt td {
	background-color: #ffffff;
}

#tablelines_fdt td.public_holiday {
	background-color: <?php print $conf->global->FDT_FERIE_COLOR ?> !important;
}

#tablelines_fdt .conges1allday {
	background-color: <?php print $conf->global->HOLIDAY_DRAFT_COLOR ?> !important;
}

#tablelines_fdt .conges1morning,
#tablelines_fdt .conges1afternoon {
	background-color: <?php print $conf->global->HOLIDAY_DRAFT_COLOR ?> !important;
}

#tablelines_fdt .conges2allday {
	background-color: <?php print $conf->global->HOLIDAY_APPROBATION1_COLOR ?> !important;
}

#tablelines_fdt .conges2morning,
#tablelines_fdt .conges2afternoon {
	background-color: <?php print $conf->global->HOLIDAY_APPROBATION1_COLOR ?> !important;
}

#tablelines_fdt .conges3allday {
	background-color: <?php print $conf->global->HOLIDAY_VALIDATED_COLOR ?> !important;
}

#tablelines_fdt .conges3morning,
#tablelines_fdt .conges3afternoon {
	background-color: <?php print $conf->global->HOLIDAY_VALIDATED_COLOR ?> !important;
}

#tablelines_fdt .conges6allday {
	background-color: <?php print $conf->global->HOLIDAY_APPROBATION2_COLOR ?> !important;
}

#tablelines_fdt .conges6morning,
#tablelines_fdt .conges6afternoon {
	background-color: <?php print $conf->global->HOLIDAY_APPROBATION2_COLOR ?> !important;
}

#tablelines_fdt .conges5allday {
	background-color: #f5b7b1 !important;
}

#tablelines_fdt .conges5morning,
#tablelines_fdt .conges5afternoon {
	background-color: #f5b7b1 !important;
}

#tablelines_fdt .conges4allday {
	background-color: #566573 !important;
}

#tablelines_fdt .conges4morning,
#tablelines_fdt .conges4afternoon {
	background-color: #566573 !important;
}

#tablelines_fdt td.before {
	background-color: <?php print $conf->global->FDT_ANTICIPE_COLOR ?> !important;
}

#tablelines_fdt td.before.weekend {
	background-color: <?php print $conf->global->FDT_ANTICIPE_WEEKEND_COLOR ?> !important;
}

#tablelines_fdt td.weekend {
	background-color: <?php print $conf->global->FDT_WEEKEND_COLOR ?> !important;
}

th.daycolumn {
	background-color: var(--colorbacktitle1);
}

tr.conges th.statut1, td.statut1 {
	border: red 4px solid !important;
}

tr.conges th.statut2, td.statut2 {
	border: #0087ff 4px solid !important;
}

tr.conges th.statut3, td.statut3 {
	border: #0087ff 4px solid !important;
}


/* Définition des couleurs des bords de cases et des cases à cocher (heures sup, autres types d'heures) */

td > input.hs25 {
	border: 1px solid #3d85c6;
	margin-top: 2px;
	max-width: 37px;
	background-color: white;
}

td > input.hs50 {
	border: 1px solid #d97a00;
	margin-top: 2px;
	margin-left: 2px;
	max-width: 37px;
	background-color: white;
}

td > div > input.heure_compagnonnage {
	border: 1px solid red;
	margin-top: 2px;
	margin-left: 2px;
	background-color: white;
}

td > div > input.heure_nuit {
	border: 1px solid black;
	margin-top: 2px;
	margin-left: 2px;
	background-color: white;
}

td > div > input.heure_route {
	accent-color: blue;
	margin-top: 2px;
	margin-left: 2px;
	height: 17px;
	width: 17px;
}

td > div > input.heure_epi {
	border: 1px solid green;
	margin-top: 2px;
	margin-left: 2px;
	background-color: white;
}

#div_otherhour > input[name="heure_nuit_chkb"]:checked {
	accent-color: black;
}

#div_otherhour > input[name="port_epi_chkb"]:checked {
	accent-color: green;
}

#div_otherhour > input[name="port_epi"] {
	margin-left: 1em;
}

#div_otherhour {
	margin-top: 0.3em;
}




/* Définition des couleurs de texte pour l'historique des feuilles de temps (weekend, anticipé, différents types de congés, fériés, ...) */

.txt_hs25 {
	color: #3d85c6;
}

.txt_hs50 {
	color: #d97a00;
}

.txt_heure_compagnonnage {
	color: red;
}

.txt_heure_nuit {
	color: black;
}

.txt_heure_route {
	color: blue;
}

.txt_heure_epi {
	color: green;
}

.txt_before {
	color: <?php print $conf->global->FDT_ANTICIPE_WEEKEND_COLOR ?> !important;
}

.txt_ferie {
	color: <?php print $conf->global->FDT_FERIE_COLOR ?> !important;
}

.txt_conges_brouillon {
	color: <?php print $conf->global->HOLIDAY_DRAFT_COLOR ?>;
}

.txt_conges_valide {
	color: <?php print $conf->global->HOLIDAY_APPROBATION1_COLOR ?>;
}

.txt_conges_approuve1 {
	color: <?php print $conf->global->HOLIDAY_APPROBATION2_COLOR ?>;
}

.txt_conges_approuve2 {
	color: <?php print $conf->global->HOLIDAY_VALIDATED_COLOR ?>;
}

.txt_conges_refuse {
	color: #f5b7b1;
}

.txt_conges_annule {
	color: #566573;
}





/* Css pour les notes https://www.w3schools.com/howto/howto_css_modals.asp */

.modal {
	display: none;
	/* Hidden by default */
	position: fixed;
	/* Stay in place */
	z-index: 11;
	/* Sit on top */
	left: 0;
	top: 0;
	width: 100%;
	/* Full width */
	height: 100%;
	/* Full height */
	overflow: auto;
	/* Enable scroll if needed */
	background-color: rgb(0, 0, 0);
	/* Fallback color */
	background-color: rgba(0, 0, 0, 0.4);
	/* Black w/ opacity */
}

.modal-content {
	background-color: #fefefe;
	margin: 15% auto;
	/* 15% from the top and centered */
	padding: 5px;
	border: 1px solid #888;
	width: 400px;
	/* Could be more or less, depending on screen size */
}

.close {
	color: #aaa;
	float: right;
	font-size: 28px;
	font-weight: bold;
}

.close:hover,
.close:focus {
	color: black;
	text-decoration: none;
	cursor: pointer;
}





/* Lignes fixes du tableau */

.feuilledetemps .fixed {
	z-index: 1;
	position: sticky;
}

/* .feuilledetemps tr > td:nth-child(2).fixed,
tr > th:nth-child(2).fixed {
	left: 167px;
} */

.feuilledetemps tr > td:nth-child(1).fixed,
.feuilledetemps tr > th:nth-child(1).fixed {
	left: 0;
	border-right: 1px solid var(--colortopbordertitle1);
}

.feuilledetemps tr > td:last-child.fixed,
tr > th:last-child.fixed {
	right: 0;
}

.feuilledetemps thead {
	position: sticky;
    top: 0;
	z-index: 2;
}

.feuilledetemps .fixedcolumn2 {
	z-index: 1;
	position: sticky;
	left: 97px;
}

.feuilledetemps .fixedcolumn3 {
	z-index: 1;
	position: sticky;
	left: 213px;
}

.feuilledetemps .fixedcolumn4 {
	z-index: 1;
	position: sticky;
	left: 329px;
}

.feuilledetemps .fixedcolumn5 {
	z-index: 1;
	position: sticky;
	left: 400px;
}

.feuilledetemps .fixedcolumn6 {
	z-index: 1;
	position: sticky;
	left: 516px;
}

.feuilledetemps .fixedcolumn7 {
	z-index: 1;
	position: sticky;
	left: 592px;
}

.feuilledetemps .fixedcolumn8 {
	z-index: 1;
	position: sticky;
	left: 648px;
}

.feuilledetemps .fixedcolumn9 {
	z-index: 1;
	position: sticky;
	left: 704px;
}

.feuilledetemps .fixedcolumn10 {
	z-index: 1;
	position: sticky;
	left: 925px;
}






/* Gestion des bordures */

.feuilledetemps [class^="liste_total_semaine"] {
	border-top: 1px solid var(--colortopbordertitle1);
}

.feuilledetemps [class^="liste_total_semaine"]:not(.liste_total_semaine_1):not(.totalweekcolumn) {
	border-left: 1px solid var(--colortopbordertitle1);
}

.feuilledetemps .total_title, 
.feuilledetemps .total_week, 
.feuilledetemps .total_holiday {
	border-left: 1px solid var(--colortopbordertitle1);
}

#tablelines_fdt>tbody > tr > td.liste_total_task,
#tablelines_fdt>tbody > tr > td.liste_total:last-child {
	border-left: 1px solid var(--colortopbordertitle1);
}

.feuilledetemps div#filtre {
	border-bottom: 1px solid var(--colortopbordertitle1);
	display: inline-table;
	width: 100%
}

#tablelines_fdt > tbody > tr > td.totalweekcolumn {
	border-right: 1px solid var(--colortopbordertitle1);
	background-color: #f0f0f0;
	vertical-align: bottom
}

#tablelines_fdt td.totalweekcolumn {
    border-bottom: 1px solid var(--colortopbordertitle1);
}

#tablelines_fdt > tbody > tr:nth-last-child(8) > td.totalweekcolumn {
	border-bottom: unset;
}

#tablelines_fdt> tbody > tr > td.holidaycolumn {
	border-right: 1px solid rgb(82, 82, 82);
	border-bottom: none;
	background-color: #f0f0f0;
}

#tablelines_fdt> tbody > tr > td.holidaycolumnmultiple1 {
	border-right: 1px solid var(--colortopbordertitle1);
	border-bottom: none;
	background-color: #f0f0f0;
}

#tablelines_fdt> tbody > tr > td.holidaycolumnmultiple2 {
	border-right: 1px solid rgb(82, 82, 82);
	border-bottom: none;
	background-color: #f0f0f0;
}

#tablelines_fdt> thead > tr > th.columntitle {
	border-bottom: 1px solid var(--colortopbordertitle1);
}




/* Gestion du tableau en full screen */


.feuilledetemps #fullscreenContainer #tableau #tablelines_fdt {
	max-height: calc(-101px + 100vh) !important;
}

.feuilledetemps #fullScreen {
    border: solid 1px #d7d7d7;
    border-radius: 5px;
    font-size: 1.5em;
	vertical-align: middle;
}

.feuilledetemps button#fullScreen {
	margin-right: 10px;
}

.feuilledetemps #fullScreen:hover {
	background-color: #d7d7d7;
}

.feuilledetemps tr.liste_totalcolumn th {
	background-color: var(--colorbacktitle1);
  	border-top: 1px solid var(--colortopbordertitle1);
}



/* Autres */

.feuilledetemps thead th {
	/* top: 0;
	position: sticky;
	min-height: 30px;
	height: 30px; */
	text-align: center;
	background-color: var(--colorbacktitle1);
}

#tablelines_fdt {
	overflow: auto;
	width: auto;
	max-width: 100%;
}

.feuilledetemps form[name="addtime"] {
	width: fit-content;
    margin: auto;
	max-width: 100%;
}

/*#tablelines_fdt.liste {
	display: inline-block;
    margin: 0;
}*/

.feuilledetemps td.liste_total_task {
	color: var(--listetotal);
	text-align: center;
}

.feuilledetemps .dropdown dd ul {
	z-index: 4;
}

.feuilledetemps input:disabled {
	background: var(--inputbackgroundcolordisabled) !important;
}

.feuilledetemps #sendMailContent {
	width: 100%;
	height: 200px;
}

.feuilledetemps .maxwidth80 {
	max-width: 80px;
}

.feuilledetemps .textarea_observation {
	margin: 2%;
	width: 96%;
	border: var(--butactionbg) 1px solid !important;
	height: 5rem;
	resize: block;
}

.feuilledetemps .info_fdt {
	padding-left: 5rem;
	width: 500px;
	font-size: medium;
	vertical-align: middle;
	color: rgb(226 0 13);
    font-weight: 800;
}

.feuilledetemps:not(.timesheet) .fiche:not(.tab) {
	position: sticky;
	top: 0px;
	z-index: 10;
	background: white;
}

.feuilledetemps div.tabsAction {
	padding: none;
	margin: 0;
}

.feuilledetemps #dragDropAreaTabBar {
	margin: 0;
	overflow: hidden;
}

.feuilledetemps .tabsAction.center {
	text-align: center;
}

#regulD1, #regulD2, #regulD3, #regulD4, #regulGD1, #regulGD2, #regulDOM,
#regulHeureRoute, #regulRepas1, #regulRepas2, #regulKilometres, #regulIndemniteTT, 
#HeureNuit50, #HeureNuit75, #HeureNuit100, #HS, #regulHeureSup00, #regulHeureSup25, #regulHeureSup50 {
	border: 1px solid grey;
    background-color: white;
	margin-left: 4px;
	max-width: 37px;
	float: right;
}

.feuilledetemps .regulTypeDeplacement {
	max-width: 211px;
	float: right;
}

.feuilledetemps #observation {
	height: 30px;
}

body.feuilledetemps .ui-widget.ui-widget-content {
	z-index: 10 !important;
}

.button-save-fdt {
	background: var(--textbutaction) !important;
    color: var(--butactionbg) !important;
	border: var(--butactionbg) solid 1px !important;
}

.feuilledetemps .tabsAction {
	width: fit-content;
}

.feuilledetemps .tabsAction a,input {
	margin-right: 0 !important;
}

.feuilledetemps input.smallpadd {
	min-width: 40px;
}

.feuilledetemps div.fiche > form > div.div-table-responsive {
	min-height: unset;
}

.feuilledetemps .div-table-responsive-no-min.compta {
	height: 150px;
    overflow: auto;
	width: 100%;
}

#exportObservationCompta,
#exportFeuilleDeTemps {
	text-align: center;
}

form#exportFeuilleDeTemps #builddoc_generatebutton {
	visibility: hidden;
}

form#exportObservationCompta #builddoc_generatebutton {
	visibility: hidden;
}

.ml20 {
	margin-left: 20px;
}

#totalDeplacementPonctuel.noNull, #totalTypeDeplacement.noNull, #totalMoyenTransport.noNull, 
#totalHeureSup00.noNull, #totalHeureSup25.noNull, #totalHeureSup50.noNull, #totalHeureNuit.noNull, 
#totalHeureRoute.noNull, #totalKilometres.noNull, #totalIndemniteTT.noNull, #totalRepas.noNull {
	font-weight: bold;
	color: #0057dee8;
	font-size: medium;
}

input[name^="deplacement_ponctuel"].deplacement_holiday {
	accent-color: red;
}

select[name^="type_deplacement"].deplacement_holiday, select[name^="moyen_transport"].deplacement_holiday {
	border: red 2px solid;
}

.feuilledetemps .titlefield {
	min-width: unset !important;
 	width: fit-content;
}

#tablelines_fdt > tbody > tr > td > div.multipleLineColumn {
	margin-bottom: 4px;
  	margin-top: 4px;
}

.feuilledetemps .displaynone {
	display: none;
}

.feuilledetemps .visibilityhidden {
	visibility: hidden;
}

.feuilledetemps .mt0 {
	margin-top: 0px; !important
}

.feuilledetemps .ml0 {
	margin-left: 0px; !important
}

.feuilledetemps span.diffpositive {
	color: blue;
}

.feuilledetemps span.diffnegative {
	color: red;
}

.feuilledetemps tr.liste_totalcolumn {
	z-index: 1;
  	position: sticky;
  	bottom: 0;
}

#tablelines_fdt.liste.column {
	margin: 0 !important;
}

.minwidth80 {
	min-width: 80px;
}

.minwidth60 {
	min-width: 60px;
}

.minwidth55 {
	min-width: 55px;
}

.minwidth40 {
	min-width: 40px;
}

.height20 {
	height: 20px;
}

#tablelines_fdt.column .fas.fa-sticky-note {
	color: rgb(198,25,44);
}