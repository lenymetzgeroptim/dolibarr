<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C)2024 Soufiane Fadel <s.fadel@optim-industries.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/projet/ganttview.php
 *	\ingroup    projet
 *	\brief      Gantt diagramm of a project
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/workload/class/charge.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';


$id = GETPOST('id', 'intcomma');
$ref = GETPOST('ref', 'alpha');
// $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'contributor'; // The action 'create'/'add', 'edit'/'update', 'view', ...

$mode = GETPOST('mode', 'alpha');
$mine = ($mode == 'mine' ? 1 : 0);
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$now = dol_now();

$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$date_start = dol_mktime(-1, -1, -1, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(-1, -1, -1, $date_endmonth, $date_endday, $date_endyear);

// $arr_responsible = GETPOST('arr_responsible');
$occupied =  GETPOST('case');

$object = new User($db);
// $rsc = new Charge($db);
// $listTrainingType = $rsc->getUsersInCurrentTraining();
// var_dump($listTrainingType);
// var_dump($genericDataAbs);
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once
if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) {
	$object->fetchComments();
}

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

if (empty($user->rights->workload->box_graph_plan_charge->afficher)) {
    accessforbidden();
}

// Load translation files required by the page
$langs->loadlangs(array('users', 'projects'));


$arrayofcss = array('/includes/jsgantt/jsgantt.css');


if (!empty($conf->use_javascript_ajax)) {
	$arrayofjs = array(
		'/includes/jsgantt/jsgantt.js',
		'/projet/jsgantt_language.js.php?lang='.$langs->defaultlang,
        '/custom/workload/js/data/dataStore.js',
        '/custom/workload/js/utils/domUtil.js',
		'/custom/workload/js/utils/dataUtils.js',
        '/custom/workload/js/workload.config.js',
        '/custom/workload/js/data/dataFetcher.js',
        '/custom/workload/js/workload.main.js',
        '/custom/workload/js/filters/filterManager.js',
        '/custom/workload/js/utils/tabNavigation.js',
        '/custom/workload/js/utils/eventBus.js',
        '/custom/workload/js/init/setup.js',
        '/custom/workload/js/filters/filterListeners.js',   
		// '/custom/workload/js/ganttFilterUtils.js',
		// '/custom/workload/js/fetch_gantt_data.js'
	);
}






//$title=$langs->trans("Gantt").($object->ref?' - '.$object->ref.' '.$object->name:'');
$title = $langs->trans("Plan de charge");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = ($object->ref ? $object->ref.' '.$object->name.' - ' : '').$langs->trans("Gantt");
}
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";

llxHeader("", $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);



// Link to create task
$linktocreatetaskParam = array();
$linktocreatetaskUserRight = false;
if ($user->rights->workload->all->creer || $user->rights->workload->creer) {
	if ($object->public || $userWrite > 0) {
		$linktocreatetaskUserRight = true;
	} else {
		$linktocreatetaskParam['attr']['title'] = $langs->trans("NotOwnerOfProject");
	}
}


// $linktotasks .= dolGetButtonTitle($langs->trans('Collaborateurs : devis & cde'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/ganttview.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));

$linktotasks .= dolGetButtonTitle($langs->trans('Cumul ETP'), '', 'fa fa-chart-line paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/workloadindex.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));
$linktotasks .= dolGetButtonTitle($langs->trans('Plan de charge'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/custom/workload/plancharge_modes_view.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly btnTitleSelected'));

print load_fiche_titre($title, $linktotasks.' &nbsp; '.$linktocreatetask, 'projecttask');


// HTML Pour le filtre
include_once DOL_DOCUMENT_ROOT.'/custom/workload/views/gantt_filter_nav.php';
include_once DOL_DOCUMENT_ROOT.'/custom/workload/views/gantts_navigation.php';

// Code Js
include_once DOL_DOCUMENT_ROOT.'/custom/workload/js/fetch_resources_data.js.php';

include_once DOL_DOCUMENT_ROOT.'/custom/workload/js/ressources/gantts_resources_abs.js.php';
include_once DOL_DOCUMENT_ROOT.'/custom/workload/js/ressources/gantts_resources_projabs.js.php';
include_once DOL_DOCUMENT_ROOT.'/custom/workload/js/ressources/gantts_resources_coldev.js.php';
include_once DOL_DOCUMENT_ROOT.'/custom/workload/js/ressources/gantts_resources_colproj.js.php';
include_once DOL_DOCUMENT_ROOT.'/custom/workload/js/ressources/gantts_resources_projcode.js.php';
include_once DOL_DOCUMENT_ROOT.'/custom/workload/js/ressources/gantts_resources_codecol.js.php';




	


session_write_close();
// End of page
llxFooter();
$db->close();



    
	
 





