<?php
/* Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
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
 * \file    gpeccustom/lib/gpeccustom.lib.php
 * \ingroup gpeccustom
 * \brief   Library files with common functions for Gpeccustom
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function gpeccustomAdminPrepareHead()
{
	global $langs, $conf;

	global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('hrm_evaluation');
	$extrafields->fetch_name_optionals_label('hrm_job');
	$extrafields->fetch_name_optionals_label('hrm_skill');
	$extrafields->fetch_name_optionals_label('hrm_job_user');
	$extrafields->fetch_name_optionals_label('hrm_cvtec');

	$langs->load("gpeccustom@gpeccustom");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/gpeccustom/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;


	$head[$h][0] = DOL_URL_ROOT.'/custom/gpeccustom/admin/admin_establishment.php';
	$head[$h][1] = $langs->trans("Establishments");
	$head[$h][2] = 'establishments';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/custom/gpeccustom/admin/skill_extrafields.php';
	$head[$h][1] = $langs->trans("SkillsExtraFields");
	$nbExtrafields = $extrafields->attributes['hrm_skill']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'skillsAttributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/custom/gpeccustom/admin/job_extrafields.php';
	$head[$h][1] = $langs->trans("JobsExtraFields");
	$nbExtrafields = $extrafields->attributes['hrm_job']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'jobsAttributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/custom/gpeccustom/admin/evaluation_extrafields.php';
	$head[$h][1] = $langs->trans("EvaluationsExtraFields");
	$nbExtrafields = $extrafields->attributes['hrm_evaluation']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'evaluationsAttributes';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT . '/custom/gpeccustom/admin/job_user_extrafields.php';
	$head[$h][1] = $langs->trans("JobProfilExtrafields");
	$nbExtrafields = $extrafields->attributes['hrm_job_user']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'JobProfilesAttributes';
	$h++;


	$head[$h][0] = dol_buildpath("/gpeccustom/admin/cvtec_extrafields.php", 1);
	$head[$h][1] = $langs->trans("CVTecExtrafields");
	$nbExtrafields = $extrafields->attributes['hrm_cvtec']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'CVTecAttributes';
	$h++;

	$head[$h][0] = dol_buildpath("/gpeccustom/admin/setupnative.php", 1);
	$head[$h][1] = $langs->trans("Settings").' '.$langs->trans("(module custom GRH/CV)");
	$head[$h][2] = 'settingscustom';
	$h++;

	$head[$h][0] = dol_buildpath("/gpeccustom/jobfield_list.php", 1);
	$head[$h][1] = $langs->trans("Gestion des spécialités");
	$head[$h][2] = 'domain';
	$h++;

	$head[$h][0] = dol_buildpath("/gpeccustom/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@gpeccustom:/gpeccustom/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@gpeccustom:/gpeccustom/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'gpeccustom@gpeccustom');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'gpeccustom@gpeccustom', 'remove');

	return $head;
}
